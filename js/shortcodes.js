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
			
			// End script
			return false;			
		}
		
		var is_valid_event = bookacti_validate_picked_events( booking_system, form.find( '.bookacti-quantity' ).val() );
		
		if( ! is_valid_event ) {
			// End script
			return false;
		} else {
			
			// Trigger action before sending form
			form.find( '.bookacti-booking-system-container' ).trigger( 'bookacti_submit_booking_form' );
			
			var data = form.serialize();
			
			bookacti_start_loading_booking_system( booking_system );

			$j.ajax({
				url: bookacti_localized.ajaxurl,
				type: 'POST',
				data: data,
				dataType: 'json',
				success: function( response ){
					
					// Trigger action after sending form
					form.find( '.bookacti-booking-system-container' ).trigger( 'bookacti_booking_form_submitted', [ response ] );
					
					var message = '';
					if( response.status === 'success' ) {
						
						// Hide fields and submit button to avoid duplicated bookings
						form.find( '.bookacti-booking-system-field-container, .bookacti-booking-system-field-submit-container' ).hide();
							
						message = "<ul class='bookacti-success-list bookacti-persistent-notice'><li>" + response.message + "</li></ul>";
						
						if( form.attr( 'action' ) !== '' ) {
							// Go to URL
							window.location.replace( form.attr( 'action' ) );
						} else {
							
							// Reload events
							var booking_system_id	= booking_system.attr( 'id' );
							var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
							
							bookacti_booking_method_refetch_events( booking_system, booking_method );
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
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_loading_booking_system( booking_system );
				}
			});	
		}
	});
	
	
	// Change activity summary on qty change
	$j( '.bookacti-booking-system-form' ).on( 'keyup mouseup', 'input.bookacti-quantity', function() {
		var booking_system = $j( this ).parents( 'form' ).find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_fill_picked_events_list( booking_system );
		}
	});
	
	
	// Set quantity on eventClick
	$j( '.bookacti-booking-system-form' ).on( 'bookacti_picked_events_list_data', '.bookacti-booking-system', function( e, event_summary_data ) {
		if( $j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).length ) {
			
			var booking_system_id	= $j( this ).attr( 'id' );
			var quantity			= parseInt( $j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).val() );
			var available_places	= 0; 
			
			// Limit the max quantity
			if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length > 1 ) {
				available_places = bookacti_get_group_availability( bookacti.booking_system[ booking_system_id ][ 'picked_events' ] );
			} else {
				available_places = bookacti_get_event_availability( bookacti.booking_system[ booking_system_id ][ 'picked_events' ][ 0 ] );
			}
			
			$j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).attr( 'max', available_places );
			if( quantity > available_places ) {
				$j( this ).parents( 'form' ).find( 'input.bookacti-quantity' ).val( available_places );
				quantity = available_places;
			}
			
			event_summary_data.quantity = quantity;
		}
	});
	
	
	// Update booking row after frontend rechedule
	$j( 'body' ).on( 'bookacti_booking_rescheduled', function( e, booking_id, start, end, response ){
		if( $j( '.bookacti-user-bookings-list' ).length ) {
			
			var row	= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
			var event_list = row.find( 'ul.bookacti-booking-events-list' );
			if( event_list.length ) {
				
				// Delete old duration
				event_list.find( '.bookacti-booking-event-start, .bookacti-booking-event-date-separator, .bookacti-booking-event-end' ).remove();
				
				// Get new duration
				var event_duration_formatted = bookacti_format_event_duration( start, end );
				
				// Display new duration
				event_list.find( 'li' ).append( event_duration_formatted );
			}
			
			// Backward compatibility - For bookings made before Booking Activities 1.1.0
			var date_container = row.find( '.bookacti-booking-dates' );
			if( date_container.length ) {
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
		}
	});
	
	
	// Enable submit booking button
	$j( 'form.bookacti-booking-system-form .bookacti-booking-system' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', function( e ) {
		var booking_form = $j( this ).parents( 'form' );
		booking_form.find( 'input[name="bookacti_quantity"]' ).attr( 'disabled', false );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	// Disable submit booking button
	$j( 'form.bookacti-booking-system-form .bookacti-booking-system' ).on( 'bookacti_error_displayed', function( e ) {
		var booking_form = $j( this ).parents( 'form' );
		booking_form.find( 'input[name="bookacti_quantity"]' ).attr( 'disabled', true );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', true );
	});
});