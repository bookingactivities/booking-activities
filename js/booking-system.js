$j( document ).ready( function() {
	/**
	 * Remove error messages after pciking new events
	 * @since 1.7.19
	 * @param {Event} e
	 * @param {Int|String} group_id
	 * @param {Object} event
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ) {
		if( $j( this ).siblings( '.bookacti-notices' ).length ) {
			$j( this ).siblings( '.bookacti-notices' ).empty();
		}
	});
	
	
	/**
	 * Init actions to perfoms when the user picks an event
	 * @version 1.8.10
	 * @param {Event} e
	 * @param {Int|String} group_id
	 * @param {Object} event
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, group_id, event ){
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		bookacti_set_min_and_max_quantity( booking_system );
		bookacti_fill_booking_system_fields( booking_system );
		bookacti_fill_picked_events_list( booking_system );
		
		// Perform form action for single events only (for groups, see bookacti_group_of_events_chosen)
		if( group_id === 'single' && attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			var group_ids = bookacti_get_event_group_ids( booking_system, event );
			var open_dialog = false;
			if( $j.isArray( group_ids )
				&&	(	( group_ids.length > 1 )
					||  ( group_ids.length === 1 && attributes[ 'groups_single_events' ] ) ) ) {
				open_dialog = true;
			}
			if( ! open_dialog ) {
				bookacti_perform_form_action( booking_system );
			}
		}
		
		booking_system.trigger( 'bookacti_events_picked_after', [ group_id, event ] );
	});
	
	
	/**
	 * Init actions to perfoms when the user picks a group of events
	 * @version 1.8.10
	 * @param {Event} e
	 * @param {Int|String} group_id
	 * @param {Object} event
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen', '.bookacti-booking-system', function( e, group_id, event ) {
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		// Perform form action for groups only (for single events, see bookacti_events_picked)
		if( attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			bookacti_perform_form_action( booking_system );
		}
		
		booking_system.trigger( 'bookacti_group_of_events_chosen_after', [ group_id, event ] );
	});
	
	
	/**
	 * Unpick an event from the picked events list - on click on their trash icon
	 * @since 1.8.10
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '.bookacti-unpick-event-icon', function( e ) {
		var booking_system = $j( this ).closest( '.bookacti-picked-events' ).siblings( '.bookacti-booking-system' );
		
		// Groups
		var group_id = $j( this ).closest( 'li' ).data( 'group-id' );
		if( group_id ) { bookacti_unpick_events_of_group( booking_system, group_id ); return; }
		
		// Single events
		var event_row = $j( this ).closest( 'li' );
		event = {
			'id': event_row.data( 'event-id' ),
			'start': event_row.data( 'event-start' ),
			'end': event_row.data( 'event-end' )
		};
		bookacti_unpick_events_of_group( booking_system, 'single', event );
	});
	
	
	/**
	 * Refresh the picked events list and display on calendar - on bookacti_unpick_event
	 * @since 1.8.10
	 * @param {Event} e
	 * @param {Object} event
	 */
	$j( 'body' ).on( 'bookacti_unpick_event', '.bookacti-booking-system', function( e, event ) {
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
		
		bookacti_set_min_and_max_quantity( booking_system );
		bookacti_fill_booking_system_fields( booking_system );
		bookacti_fill_picked_events_list( booking_system );
		
		if( booking_method === 'calendar' ) {
			bookacti_refresh_picked_events_on_calendar( $j( this ) );
		}
	});
	
	
	/**
	 * Remove temporary form after submit
	 * @since 1.7.19
	 * @version 1.8.0
	 * @param {Event} e
	 * @param {Object} response
	 * @param {Object} form_data_object
	 */
	$j( 'body' ).on( 'bookacti_booking_form_submitted', 'form.bookacti-temporary-form', function( e, response, form_data_object ) {
		if( $j( this ).find( '.bookacti-form-fields' ).length ) {
			$j( this ).find( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' );
		}
	});
	
	
	/**
	 * Do not init reschedule booking system automatically
	 * @since 1.7.0
	 * @param {Event} e
	 * @param {Object} load
	 * @param {Object} attributes
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
					bookacti_booking_method_set_up( booking_system );
					
					// Remove initial loading feedback
					booking_system.find( '.bookacti-loading-alt' ).remove();
					
				} else {
					bookacti_reload_booking_system( booking_system, true );
				}
			}
		});		
	}
	
	
	
	
	// TOOLTIPS
	
	/**
	 * Display the booking list tooltip when an event is hovered
	 * @since 1.8.0
	 * @version 1.8.10
	 * @param {Event} e
	 * @param {Object} event
	 * @param {HTMLElement} element
	 */
	$j( 'body' ).on( 'bookacti_event_mouse_over bookacti_event_touch_start', '.bookacti-booking-system', function( e, event, element ) {
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		// Check if the booking list should be displayed
		if( ! attributes[ 'tooltip_booking_list' ] ) { return; }
		
		// Check if the booking list exists
		var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		if( typeof attributes[ 'booking_lists' ][ event.id ] === 'undefined' ) { return; }
		if( typeof attributes[ 'booking_lists' ][ event.id ][ event_start ] === 'undefined' ) { return; }
		
		var booking_list = attributes[ 'booking_lists' ][ event.id ][ event_start ];
		if( ! booking_list ) { return; }
		
		var tooltip_mouseover_timeout = parseInt( bookacti_localized.bookings_tooltip_mouseover_timeout );
		if( tooltip_mouseover_timeout < 0 ) { return; }
		
		// Clear the timeout to remove the old pop up (it will be removed by bookacti_display_bookings_tooltip_monitor)
		if( typeof bookacti_remove_mouseover_tooltip_monitor !== 'undefined' ) { 
			if( bookacti_remove_mouseover_tooltip_monitor ) { clearTimeout( bookacti_remove_mouseover_tooltip_monitor ); }
		}
		
		bookacti_display_bookings_tooltip_monitor = setTimeout( function() {
			// Remove old tooltip
			booking_system.siblings( '.bookacti-tooltips-container' ).find( '.bookacti-booking-list-tooltip.bookacti-tooltip-mouseover' ).remove();
			
			// Display the tooltip
			booking_system.siblings( '.bookacti-tooltips-container' ).append( '<div class="bookacti-tooltip-container bookacti-booking-list-tooltip bookacti-tooltip-mouseover"><div class="bookacti-tooltip-content bookacti-custom-scrollbar">' + booking_list + '</div></div>' );

			// Display the tooltip above the event
			var tooltip_container = booking_system.siblings( '.bookacti-tooltips-container' ).find( '.bookacti-booking-list-tooltip.bookacti-tooltip-mouseover' );
			bookacti_set_tooltip_position( element, tooltip_container, 'above' );
			
			// Hook for plugins
			$j( 'body' ).trigger( 'bookacti_event_booking_list_displayed', [ tooltip_container, booking_system, event, element ] );
		}, tooltip_mouseover_timeout );
	});
	
	
	/**
	 * Remove the tooltips on mouse out
	 * @since 1.8.0
	 * @param {Event} e
	 * @param {Object} event
	 * @param {HTMLElement} element
	 */
	$j( 'body' ).on( 'bookacti_event_mouse_out', '.bookacti-booking-system', function( e, event, element ) {
		// Clear the timeout
		if( typeof bookacti_display_bookings_tooltip_monitor !== 'undefined' ) { 
			if( bookacti_display_bookings_tooltip_monitor ) { clearTimeout( bookacti_display_bookings_tooltip_monitor ); }
		}
		
		// Remove mouseover tooltip
		var tooltip = $j( this ).siblings( '.bookacti-tooltips-container' ).find( '.bookacti-tooltip-mouseover' );
		if( tooltip.length ) {
			var tooltip_mouseover_timeout = Math.min( Math.max( parseInt( bookacti_localized.bookings_tooltip_mouseover_timeout ), 0 ), 200 );
			bookacti_remove_mouseover_tooltip_monitor = setTimeout( function() {
				tooltip.remove();
			}, tooltip_mouseover_timeout );
		}
	});
	
	
	/**
	 * Remove the tooltips on click anywhere else than the tooltip
	 * @since 1.8.0
	 * @param {Event} e
	 */
	$j( document ).on( 'click', function( e ) {
		// Do nothing if the click hit the tooltip
		if( $j( e.target ).closest( '.bookacti-booking-list-tooltip.bookacti-tooltip-mouseover' ).length ) { return; }
		
		// Clear the timeout
		if( typeof bookacti_display_bookings_tooltip_monitor !== 'undefined' ) { 
			if( bookacti_display_bookings_tooltip_monitor ) { clearTimeout( bookacti_display_bookings_tooltip_monitor ); }
		}
	});
	
	
	/**
	 * Remove the tooltips on click anywhere else than the tooltip
	 * @since 1.8.0
	 * @param {Event} e
	 */
	$j( document ).on( 'click', function( e ) {
		// Do nothing if the click hit the tooltip
		if( $j( e.target ).closest( '.bookacti-tooltip-mouseover' ).length ) { return; }
		
		// Remove mouseover tooltips
		if( $j( '.bookacti-tooltip-mouseover' ).length ) {
			$j( '.bookacti-tooltip-mouseover' ).remove();
		}
	});
	
	
	/**
	 * Keep the tooltips displayed if the user mouseover it
	 * @since 1.8.0
	 */
	$j( 'body' ).on( 'mouseover', '.bookacti-tooltip-mouseover', function() {
		if( typeof bookacti_remove_mouseover_tooltip_monitor !== 'undefined' ) { 
			if( bookacti_remove_mouseover_tooltip_monitor ) { clearTimeout( bookacti_remove_mouseover_tooltip_monitor ); }
		}
	});
	
	
	/**
	 * Remove the tooltips displayed on mouseover - on mouseout
	 * @since 1.8.0
	 */
	$j( 'body' ).on( 'mouseout', '.bookacti-tooltip-mouseover', function() {
		var tooltip = $j( this ).closest( '.bookacti-tooltips-container' ).find( '.bookacti-tooltip-mouseover' );
		if( tooltip.length ) {
			var tooltip_mouseover_timeout = Math.min( Math.max( parseInt( bookacti_localized.bookings_tooltip_mouseover_timeout ), 0 ), 200 );
			bookacti_remove_mouseover_tooltip_monitor = setTimeout( function() {
				tooltip.remove();
			}, tooltip_mouseover_timeout );
		}
	});
});


