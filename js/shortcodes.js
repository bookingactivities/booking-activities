$j( document ).ready( function() {
	
	// Intercept booking form submission
	$j( '.bookacti-booking-system-form' ).on( 'submit', function( e ){
		// Prevent submission
		e.preventDefault();
		
		var form			= $j( this );
		var booking_system	= form.find( '.bookacti-booking-system' );
		
		// Disable the submit button to avoid multiple booking
		form.find( '.bookacti-booking-system-field-submit-container input[type="submit"]' ).prop( 'disabled', true );
		
		// If not logged in
		if( typeof bookacti_localized.current_user_id === 'undefined'
		||  bookacti_localized.current_user_id == null
		||  bookacti_localized.current_user_id == 0 ) {
			
			booking_system.siblings( '.bookacti-notices' ).empty().append( "<ul class='bookacti-error-list'><li>" + bookacti_localized.error_user_not_logged_in + "</li></ul>" ).show();
			
			// Re-enable the submit button
			form.find( '.bookacti-booking-system-field-submit-container input[type="submit"]' ).prop( 'disabled', false );
			
			// End script
			return false;			
		}
		
		var is_valid_event = bookacti_validate_picked_events( booking_system, form.find( '.bookacti-quantity' ).val() );
		
		if( ! is_valid_event ) {
			// Re-enable the submit button
			form.find( '.bookacti-booking-system-field-submit-container input[type="submit"]' ).prop( 'disabled', false );
			
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
						form.find( '.bookacti-booking-system-field-container:not(.bookacti-booking-system-field-submit-container), .bookacti-booking-system-field-submit-container input[type="submit"]' ).hide();
						
						// Show a "Make a new booking" button to avoid refreshing the page to make a new booking
						form.find( '.bookacti-booking-system-field-submit-container' ).append( '<input type="button" class="bookacti-new-booking-button" value="' + bookacti_localized.booking_form_new_booking_button + '" />' );
						
						message = "<ul class='bookacti-success-list bookacti-persistent-notice'><li>" + response.message + "</li></ul>";
						
						if( form.attr( 'action' ) !== '' ) {
							// Go to URL
							window.location.replace( form.attr( 'action' ) );
						} else {
							
							// Reload booking numbers
							bookacti_refresh_booking_numbers( booking_system );
						}
						
					} else {
						message = "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>";
					}
					
					// Display feedback message
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
					
					// Re-enable the submit button
					form.find( '.bookacti-booking-system-field-submit-container input[type="submit"]' ).prop( 'disabled', false );
				}
			});	
		}
	});
	
	
	// Display booking system fields and submit button if the user want to make a new booking
	$j( '.bookacti-booking-system-form' ).on( 'click', '.bookacti-new-booking-button', function(){
		var form = $j( this ).parents( 'form' );
		var booking_system	= form.find( '.bookacti-booking-system' );
		
		// Clear booking system displayed info
		bookacti_clear_booking_system_displayed_info( booking_system );
		
		// Display form fields and submit button, and then, delete the "Make a new booking" button
		form.find( '.bookacti-booking-system-field-container:not(.bookacti-booking-system-field-submit-container), .bookacti-booking-system-field-submit-container input[type="submit"]' ).show();
		$j( this ).remove();
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
		var booking_system = $j( this );
		var qty_field = booking_system.parents( 'form' ).find( 'input.bookacti-quantity' );
		if( qty_field.length ) {
			bookacti_set_min_and_max_quantity( booking_system, qty_field, event_summary_data );
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