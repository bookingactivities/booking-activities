$j( document ).ready( function() {
	
	// Intercept booking form submission
	$j( '.bookacti-booking-system-form' ).on( 'submit', function( e ){
		// Prevent submission
		e.preventDefault();
		
		var form			= $j( this );
		var booking_system	= form.find( '.bookacti-booking-system' );
		
		// If not logged in
		if( typeof bookacti_localized.current_user_id === 'undefined'
		||  bookacti_localized.current_user_id == null
		||  bookacti_localized.current_user_id == 0 ) {
			
			booking_system.siblings( '.bookacti-notices' ).empty().append( "<ul class='bookacti-error-list'><li>" + bookacti_localized.error_user_not_logged_in + "</li></ul>" ).show();
			return;			
		}
		
		var is_valid = bookacti_validate_selected_booking_event( booking_system, form.find( '.bookacti-quantity' ).val() );
		
		if( is_valid ) {
			
			var data	= form.serialize();
			var settings= form.serializeObject();
			
			bookacti_start_loading_booking_system( booking_system );

			$j.ajax({
				url: bookacti_localized.ajaxurl,
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function( response ){
					var message = '';
					if( response.status === 'success' ) {
						message = "<ul class='bookacti-success-list bookacti-persistent-notice'><li>" + response.message + "</li></ul>";
						
						if( form.attr( 'action' ) !== '' ) {
							// Go to URL
							form.unbind( 'submit' ).submit();
						} else {
							// Reload events
							var booking_system_id	= booking_system.data( 'booking-system-id' );
							var calendar			= $j( '#bookacti-calendar-' + booking_system_id );
							var booking_method		= booking_system.data( 'booking-method' );
							if( booking_method === 'calendar' || ! ( booking_method in bookacti_localized.available_booking_methods ) ) {
								calendar.fullCalendar( 'removeEvents' );
								bookacti_fetch_calendar_events( calendar );
							} else {
								booking_system.trigger( 'bookacti_refetch_events', [ booking_method, false ] );
							}
						}
						
					} else {
						message = "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>";
					}
					booking_system.siblings( '.bookacti-notices' ).empty().append( message ).show();
				},
				error: function( e ){
					var message = "<ul class='bookacti-error-list'><li>AJAX " + bookacti_localized.error_book + "</li></ul>";
					booking_system.siblings( '.bookacti-notices' ).empty().append( message ).show();
					console.log( 'AJAX ' + bookacti_localized.error_book );
					console.log( e.responseText );
				},
				complete: function() { 
					bookacti_stop_loading_booking_system( booking_system );
				}
			});	
		}
	});
	
	
	// Change activity summary on qty change
	$j( '.bookacti-booking-system-form' ).on( 'change', 'input.bookacti-quantity', function() {
		var booking_system = $j( this ).parents( 'form' ).find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_fill_picked_activity_summary( booking_system, false, $j( this ).val() );
		}
	});
	
	
	// Set quantity on eventClick
	$j( '.bookacti-booking-system-form' ).on( 'bookacti_fill_picked_event_summary', function( e, event_summary_data ) {
		var qty = $j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).val();
		if( $j.isNumeric( qty ) ) {
			event_summary_data.quantity = qty;
		}
	});
	
	
	// Limit quantity field on click on an event
	$j( '.bookacti-booking-system-form' ).on( 'bookacti_event_click', '.bookacti-booking-system', function( e, event ) {
		if( $j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).length ) {
			//Limit the max quantity
			var available_places = bookacti_get_event_availability( event );
			$j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).attr( 'max', available_places );
			if( $j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).val() > available_places ) {
				$j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).val( available_places ).trigger( 'change' );
			}
		}
	});
	
	
	// Update booking row after frontend rechedule
	$j( 'body' ).on( 'bookacti_booking_rescheduled', function( e, booking_id, start, end, response ){
		if( $j( '.bookacti-user-bookings-list' ).length ) {
			
			var row	= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
			var date_container = row.find( '.bookacti-booking-dates' );
			var event_start	= moment( start );
			var event_end	= moment( end );

			//Make 'from' and 'to' intelligible values
			date_container.find( '.bookacti-date-picked-to' ).removeClass( 'to_hour to_date' );
			var from_val = event_start.locale( bookacti_localized.current_lang_code ).format( 'LLLL' );
			var sep_val	= '';
			var to_val = '';
			if( event_start.format( 'YYYY-MM-DD' ) === event_end.format( 'YYYY-MM-DD' ) ) { 
				sep_val		= ' ' + bookacti_localized.to_hour + ' ';
				to_val		= event_end.locale( bookacti_localized.current_lang_code ).format( 'LT' );
				date_container.find( '.bookacti-date-picked-to' ).addClass( 'to_hour' );
			} else {
				sep_val		= ' ' + bookacti_localized.to_date + ' ';
				to_val		= event_end.locale( bookacti_localized.current_lang_code ).format( 'LLLL' );
				date_container.find( '.bookacti-date-picked-to' ).addClass( 'to_date' );
			}

			//Fill a intelligible field to feedback the user about his choice
			date_container.find( '.bookacti-booking-start' ).html( from_val );
			date_container.find( '.bookacti-booking-date-separator' ).html( sep_val );
			date_container.find( '.bookacti-booking-end' ).html( to_val );
			
			// Change actions
			row.find( '.bookacti-booking-actions' ).html( response.actions_html );
		}
	});
	
	
	//Enable submit booking button
	$j( 'form.bookacti-booking-system-form .bookacti-booking-system' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', function( e ) {
		var booking_form = $j( this ).parents( 'form' );
		booking_form.find( 'input[name="bookacti_quantity"]' ).attr( 'disabled', false );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	//Disable submit booking button
	$j( 'form.bookacti-booking-system-form .bookacti-booking-system' ).on( 'bookacti_error_displayed', function( e ) {
		var booking_form = $j( this ).parents( 'form' );
		booking_form.find( 'input[name="bookacti_quantity"]' ).attr( 'disabled', true );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', true );
	});
});