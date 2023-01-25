$j( document ).ready( function() {
	/**
	 * Init actions to perform when the user picks an event
	 * @version 1.15.7
	 * @param {Event} e
	 * @param {(FullCalendar.EventApi|Object)} picked_event
	 * @param {Int} group_id
	 * @param {String} group_date
	 */
	$j( 'body' ).on( 'bookacti_events_picked', '.bookacti-booking-system', function( e, picked_event, group_id, group_date ) {
		// Retrieve the info required to show the desired events
		var booking_system    = $j( this );
		var booking_system_id = booking_system.attr( 'id' );
		var attributes        = bookacti.booking_system[ booking_system_id ];
		
		// Remove error messages after picking new events
		if( booking_system.siblings( '.bookacti-notices' ).length ) {
			booking_system.siblings( '.bookacti-notices' ).empty();
		}
		
		bookacti_set_min_and_max_quantity( booking_system );
		bookacti_fill_booking_system_fields( booking_system );
		bookacti_fill_picked_events_list( booking_system );
		
		// Perform form action for single events only (for groups, see bookacti_group_of_events_chosen)
		if( ! group_id && attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			var groups = bookacti_get_event_groups( booking_system, picked_event );
			var groups_nb = bookacti_get_event_groups_nb( groups );
			var open_dialog = groups_nb > 1 || ( groups_nb === 1 && attributes[ 'groups_single_events' ] ) ? true : false;
			if( ! open_dialog ) {
				bookacti_perform_form_action( booking_system );
			}
		}
		
		booking_system.trigger( 'bookacti_events_picked_after', [ picked_event, group_id, group_date ] );
	});
	
	
	/**
	 * Init actions to perfoms when the user picks a group of events
	 * @version 1.12.0
	 * @param {Event} e
	 * @param {Int|String} group_id
	 * @param {String} group_date
	 * @param {Object} event
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen', '.bookacti-booking-system', function( e, group_id, group_date, event ) {
		// Retrieve the info required to show the desired events
		var booking_system    = $j( this );
		var booking_system_id = booking_system.attr( 'id' );
		var attributes        = bookacti.booking_system[ booking_system_id ];
		
		// Perform form action for groups only (for single events, see bookacti_events_picked)
		if( attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			bookacti_perform_form_action( booking_system );
		}
		
		booking_system.trigger( 'bookacti_group_of_events_chosen_after', [ group_id, group_date, event ] );
	});
	
	
	/**
	 * Unpick an event from the picked events list - on click on their trash icon
	 * @since 1.9.0
	 * @version 1.12.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '.bookacti-unpick-event-icon', function( e ) {
		var booking_system = $j( this ).closest( '.bookacti-picked-events' ).siblings( '.bookacti-booking-system' );
		
		// Groups
		var group_id = $j( this ).closest( 'li' ).data( 'group-id' );
		var group_date = $j( this ).closest( 'li' ).data( 'group-date' );
		if( group_id ) { bookacti_unpick_events( booking_system, 0, group_id, group_date ); return; }
		
		// Single events
		var event_row = $j( this ).closest( 'li' );
		event = {
			'id': event_row.data( 'event-id' ),
			'start': event_row.data( 'event-start' ),
			'end': event_row.data( 'event-end' )
		};
		bookacti_unpick_events( booking_system, event );
	});
	
	
	/**
	 * Refresh the picked events list and display on calendar - on bookacti_events_unpicked
	 * @since 1.9.0
	 * @version 1.12.0
	 * @param {Event} e
	 * @param {Object} event
	 * @param {Int} group_id
	 * @param {String} group_date
	 */
	$j( 'body' ).on( 'bookacti_events_unpicked', '.bookacti-booking-system', function( e, event, group_id, group_date ) {
		var booking_system    = $j( this );
		var booking_system_id = booking_system.attr( 'id' );
		var booking_method    = bookacti.booking_system[ booking_system_id ][ 'method' ];
		
		bookacti_set_min_and_max_quantity( booking_system );
		bookacti_fill_booking_system_fields( booking_system );
		bookacti_fill_picked_events_list( booking_system );
		
		if( booking_method === 'calendar' ) {
			bookacti_refresh_picked_events_on_calendar( $j( this ) );
		}
	});
	
	
	/**
	 * Refresh total price field - on events picked / unpicked
	 * @since 1.12.4
	 * @param {Event} e
	 * @param {Object} picked_event
	 * @param {Int} group_id
	 * @param {String} group_date
	 */
	$j( 'body' ).on( 'bookacti_events_picked bookacti_events_unpicked', '.bookacti-booking-system', function( e, picked_event, group_id, group_date ) {
		var form = $j( this ).closest( 'form' ).length ? $j( this ).closest( 'form' ) : $j( this ).closest( '.bookacti-form-fields' );
		if( form.length ) { bookacti_update_total_price_field_data_and_refresh( form ); }
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
	
	
	
	
	// TOOLTIPS
	
	/**
	 * Display the booking list tooltip when an event is hovered
	 * @since 1.8.0
	 * @version 1.15.7
	 * @param {Event} e
	 * @param {Object} info {
		* @type {(FullCalendar.EventApi|Object)} event
		* @type {HTMLElement} el
		* @type {Event} jsEvent
	 * }
	 */
	$j( 'body' ).on( 'bookacti_calendar_event_mouse_enter bookacti_calendar_event_touch_start', '.bookacti-booking-system', function( e, info ) {
		var booking_system    = $j( this );
		var booking_system_id = booking_system.attr( 'id' );
		var attributes        = bookacti.booking_system[ booking_system_id ];
		
		// Check if the booking list should be displayed
		if( ! attributes[ 'tooltip_booking_list' ] ) { return; }
		
		// Check if the booking list exists
		var event_id = typeof info.event.groupId !== 'undefined' ? parseInt( info.event.groupId ) : parseInt( info.event.id );
		var event_start = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		if( typeof attributes[ 'booking_lists' ][ event_id ] === 'undefined' ) { return; }
		if( typeof attributes[ 'booking_lists' ][ event_id ][ event_start ] === 'undefined' ) { return; }
		
		var booking_list = attributes[ 'booking_lists' ][ event_id ][ event_start ];
		if( ! booking_list ) { return; }
		
		var event_touch_press_delay = parseInt( bookacti_localized.event_touch_press_delay );
		if( event_touch_press_delay < 0 ) { return; }
		
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
			if( tooltip_container.length ) {
				bookacti_set_tooltip_position( $j( info.el ), tooltip_container, 'above' );

				// Hook for plugins
				$j( 'body' ).trigger( 'bookacti_event_booking_list_displayed', [ tooltip_container, booking_system, info.event, $j( info.el ) ] );
			}
		}, event_touch_press_delay );
	});
	
	
	/**
	 * Remove the tooltips on mouse out
	 * @since 1.8.0
	 * @version 1.15.7
	 * @param {Event} e
	 * @param {Object} info {
		* @type {(FullCalendar.EventApi|Object)} event
		* @type {HTMLElement} el
		* @type {Event} jsEvent
	 * }
	 */
	$j( 'body' ).on( 'bookacti_calendar_event_mouse_leave', '.bookacti-booking-system', function( e, info ) {
		// Clear the timeout
		if( typeof bookacti_display_bookings_tooltip_monitor !== 'undefined' ) { 
			if( bookacti_display_bookings_tooltip_monitor ) { clearTimeout( bookacti_display_bookings_tooltip_monitor ); }
		}
		
		// Remove mouseover tooltip
		var tooltip = $j( this ).siblings( '.bookacti-tooltips-container' ).find( '.bookacti-tooltip-mouseover' );
		if( tooltip.length ) {
			var event_touch_press_delay = Math.min( Math.max( parseInt( bookacti_localized.event_touch_press_delay ), 0 ), 200 );
			bookacti_remove_mouseover_tooltip_monitor = setTimeout( function() {
				tooltip.remove();
			}, event_touch_press_delay );
		}
	});
	
	
	/**
	 * Clear tooltip timeout on touch release, cancel or swip
	 * @since 1.15.7
	 * @param {Event} e
	 * @param {Object} info {
		* @type {(FullCalendar.EventApi|Object)} event
		* @type {HTMLElement} el
		* @type {Event} jsEvent
	 * }
	 */
	$j( 'body' ).on( 'bookacti_calendar_event_touch_move bookacti_calendar_event_touch_end bookacti_calendar_event_touch_cancel', '.bookacti-booking-system', function( e, info ) {
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
	 * @version 1.15.7
	 */
	$j( 'body' ).on( 'mouseout', '.bookacti-tooltip-mouseover', function() {
		var tooltip = $j( this ).closest( '.bookacti-tooltips-container' ).find( '.bookacti-tooltip-mouseover' );
		if( tooltip.length ) {
			var event_touch_press_delay = Math.min( Math.max( parseInt( bookacti_localized.event_touch_press_delay ), 0 ), 200 );
			bookacti_remove_mouseover_tooltip_monitor = setTimeout( function() {
				tooltip.remove();
			}, event_touch_press_delay );
		}
	});
	
	
	// Load the booking systems
	
	// Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) {
		/**
		 * Load booking system on page load
		 * @version 1.15.0
		 */
		$j( '.bookacti-booking-system' ).each( function() {
			// Retrieve the info required to show the desired events
			var booking_system    = $j( this );
			var booking_system_id = booking_system.attr( 'id' );
			var attributes        = bookacti.booking_system[ booking_system_id ];
			
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
					// Remove initial loading feedback
					bookacti_remove_loading_html( booking_system );
					
					// Load booking system with existing data
					bookacti_booking_method_set_up( booking_system );
					
				} else {
					// Load booking system from scratch
					bookacti_reload_booking_system( booking_system, true );
				}
			}
		});		
	}
});


