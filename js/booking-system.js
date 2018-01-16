$j( document ).ready( function() {
	
	//Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) { 
		
		// Init the Dialogs
		bookacti_init_booking_system_dialogs();
		
		// Init on pick events actions
		$j( '.bookacti-booking-system' ).on( 'bookacti_events_picked', function( e, group_id, event ){
			bookacti_fill_form_fields( $j( this ), event, group_id );
			bookacti_fill_picked_events_list( $j( this ) );
		});
				
		$j( '.bookacti-booking-system' ).each( function() { 
			
			// Retrieve the info required to show the desired events
			var booking_system		= $j( this );
			var booking_system_id	= booking_system.attr( 'id' );
			var attributes			= bookacti.booking_system[ booking_system_id ];
			
			if( typeof bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 'undefined' ) {
				bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0;
			}
			if( typeof bookacti.booking_system[ booking_system_id ][ 'picked_events' ] === 'undefined' ) {
				bookacti.booking_system[ booking_system_id ][ 'picked_events' ]	= [];
			}
			
			// Load the booking system
			if( attributes.auto_load && booking_system_id !== 'bookacti-booking-system-reschedule' ) {
				if( bookacti_localized.when_events_load === 'on_page_load' ) {
					bookacti_booking_method_set_up( booking_system, false );
				} else {
					bookacti_reload_booking_system( booking_system );
				}
			}
		});		
	}

}); // end of document ready


