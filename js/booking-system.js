$j( document ).ready( function() {
	
	//Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) { 
		
		// Init the Dialogs
		bookacti_init_booking_system_dialogs();
		
		// Init on pick events actions
		$j( '.bookacti-booking-system' ).on( 'bookacti_events_picked', function(){
			bookacti_fill_picked_events_list( $j( this ) );
		});
		
		// Init on submit event action
		$j( '.bookacti-booking-system-container' ).on( 'bookacti_submit_booking_form', function() {
		});
		
		
		$j( '.bookacti-booking-system' ).each( function() { 
			
			// Retrieve the info required to show the desired events
			var booking_system		= $j( this );
			var booking_system_id	= booking_system.attr( 'id' );
			var attributes			= calendars_data[ booking_system_id ];
			
			loadingNumber[ booking_system_id ]	= 0;
			pickedEvents[ booking_system_id ]	= [];
			
			// Load the booking system
			if( attributes.auto_load && booking_system_id !== 'bookacti-booking-system-reschedule' ) {
				if( bookacti_localized.when_events_load === 'on_page_load' ) {
					bookacti_booking_method_set_up( booking_system, attributes.method, false );
				} else {
					bookacti_reload_booking_system( booking_system );
				}
			}
		});		
	}

}); // end of document ready


