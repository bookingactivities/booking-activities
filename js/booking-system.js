$j( document ).ready( function() {
	/**
	 * Remove error messages after pciking new events
	 * @since 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ) {
		if( $j( this ).siblings( '.bookacti-notices' ).length ) {
			$j( this ).siblings( '.bookacti-notices' ).empty();
		}
	});
	
	
	/**
	 * Init actions to perfoms when the user picks an event
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ){
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		bookacti_fill_booking_system_fields( booking_system, event, group_id );
		bookacti_fill_picked_events_list( booking_system );
		
		// Do not perform form actions on form editor
		if( ! $j( this ).closest( '#bookacti-form-editor-page-form' ).length ) {
			var group_ids = bookacti_get_event_group_ids( booking_system, event );
			var open_dialog = false;
			if( $j.isArray( group_ids )
				&&	(	( group_ids.length > 1 )
					||  ( group_ids.length === 1 && attributes[ 'groups_single_events' ] ) ) ) {
				open_dialog = true;
			}
			if( ! open_dialog ) {
				if( group_id === 'single' && attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
					if( attributes[ 'form_action' ] === 'redirect_to_url' ) {
						bookacti_redirect_to_activity_url( booking_system, event );
					} else if( attributes[ 'form_action' ] === 'default' ) {
						if( ! booking_system.closest( 'form' ).length && booking_system.closest( '.bookacti-form-fields' ).length ) {
							booking_system.closest( '.bookacti-form-fields' ).wrap( '<form id="bookacti-temporary-form"></form>' );
						}
						if( booking_system.closest( 'form' ).length ) {
							bookacti_submit_booking_form( booking_system.closest( 'form' ) );
							return;
						}
					}
				}
			}
		}
		
		booking_system.trigger( 'bookacti_events_picked_after', [ group_id, event ] );
	});
	
	
	/**
	 * Init actions to perfoms when the user picks a group of events
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen', '.bookacti-booking-system', function( e, group_id, event ) {
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		// Do not perform form actions on form editor
		if( ! $j( this ).closest( '#bookacti-form-editor-page-form' ).length ) {
			if( attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
				if( attributes[ 'form_action' ] === 'redirect_to_url' ) {
					if( group_id === 'single' ) {
						bookacti_redirect_to_activity_url( booking_system, event );
					} else if( $j.isNumeric( group_id ) ) {
						bookacti_redirect_to_group_category_url( booking_system, group_id );
					}
				} else if( attributes[ 'form_action' ] === 'default' ) {
					if( ! booking_system.closest( 'form' ).length && booking_system.closest( '.bookacti-form-fields' ).length ) {
						booking_system.closest( '.bookacti-form-fields' ).wrap( '<form id="bookacti-temporary-form"></form>' );
					}
					if( booking_system.closest( 'form' ).length ) {
						bookacti_submit_booking_form( booking_system.closest( 'form' ) );
						return;
					}
				}
			}
		}
		
		booking_system.trigger( 'bookacti_group_of_events_chosen_after', [ group_id, event ] );
	});
	
	
	/**
	 * Remove temporary form after submit
	 * @since 1.7.19
	 * @param {object} response
	 * @param {object} form_data_object
	 */
	$j( 'body' ).on( 'bookacti_booking_form_submitted', 'form#bookacti-temporary-form', function( e, response, form_data_object ) {
		if( $j( this ).find( '.bookacti-form-fields' ).length ) {
			$j( this ).find( '.bookacti-form-fields' ).unwrap( 'form#bookacti-temporary-form' );
		}
	});
	
	
	/**
	 * Do not init reschedule booking system automatically
	 * @since 1.7.0
	 */
	$j( 'body' ).on( 'bookacti_init_booking_sytem', '.bookacti-booking-system#bookacti-booking-system-reschedule', function( e, load, attributes ) {
		load.load = false;
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
			var load = { 'load': true, 'auto_load': attributes.auto_load ? true : false };
			booking_system.trigger( 'bookacti_init_booking_sytem', [ load, attributes ] );
			
			if( load.load ) {
				if( load.auto_load ) {
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


