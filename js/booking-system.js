$j( document ).ready( function() {
	
	// Init on pick events actions
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ){
		bookacti_fill_booking_system_fields( $j( this ), event, group_id );
		bookacti_fill_picked_events_list( $j( this ) );
	});
	
	// Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) { 
		
		// Init the Dialogs
		bookacti_init_booking_system_dialogs();
				
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
			if( booking_system_id !== 'bookacti-booking-system-reschedule' ) {
				if( attributes.auto_load ) {
					bookacti_booking_method_set_up( booking_system, false );
					
					// remove initial loading feedback
					booking_system.find( '.bookacti-loading-alt' ).remove();
					
				} else {
					bookacti_reload_booking_system( booking_system );
				}
			}
		});		
	}

}); // end of document ready


