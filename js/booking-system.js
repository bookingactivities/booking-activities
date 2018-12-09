$j( document ).ready( function() {
	
	/**
	 * Init actions to perfoms when the user picks an event
	 * @version 1.7.0
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ){
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		bookacti_fill_booking_system_fields( booking_system, event, group_id );
		bookacti_fill_picked_events_list( booking_system );
		
		if( group_id === 'single' && attributes[ 'when_perform_form_action' ] === 'on_event_click' && attributes[ 'form_action' ] === 'redirect_to_url' ) {
			bookacti_redirect_to_activity_url( booking_system, event );
		}
	});
	
	/**
	 * Init actions to perfoms when the user picks a group of events
	 * @version 1.7.0
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen', '.bookacti-booking-system', function( e, group_id, event ) {
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		if( attributes[ 'when_perform_form_action' ] === 'on_event_click' && attributes[ 'form_action' ] === 'redirect_to_url' ) {
			bookacti_redirect_to_group_category_url( booking_system, group_id );
		}
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
					
					// Remove initial loading feedback
					booking_system.find( '.bookacti-loading-alt' ).remove();
					
				} else {
					bookacti_reload_booking_system( booking_system, true );
				}
			}
		});		
	}

}); // end of document ready


