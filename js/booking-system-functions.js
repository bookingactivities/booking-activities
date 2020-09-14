/**
 * Retrieve the events to display on the booking system
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 * @param {object} interval
 */
function bookacti_fetch_events( booking_system, interval ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	
	// Do not send useless data
	delete attributes[ 'events' ];
	delete attributes[ 'events_data' ];
	delete attributes[ 'events_interval' ];
	delete attributes[ 'bookings' ];
	delete attributes[ 'booking_lists' ];
	delete attributes[ 'activities_data' ];
	delete attributes[ 'groups_events' ];
	delete attributes[ 'groups_data' ];
	delete attributes[ 'group_categories_data' ];
	delete attributes[ 'picked_events' ];
	delete attributes[ 'rescheduled_booking_data' ];
	delete attributes[ 'templates_per_activities' ];
	
	interval = interval || attributes[ 'events_interval' ];

	// Update events interval before success to prevent to fetch the same interval twice
	bookacti.booking_system[ booking_system_id ][ 'events_interval' ] = bookacti_get_extended_events_interval( booking_system, interval );
	
	bookacti_start_loading_booking_system( booking_system );
	
    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiFetchEvents', 
			'attributes': JSON.stringify( attributes ),
			'is_admin': bookacti_localized.is_admin ? 1 : 0, 
			'interval': interval
		},
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				// Extend or replace the events array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'events' ] = response.events;
				} else {
					$j.extend( bookacti.booking_system[ booking_system_id ][ 'events' ], response.events );
				}
				
				// Extend or replace the events data array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events_data' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'events_data' ] = response.events_data;
				} else {
					$j.extend( bookacti.booking_system[ booking_system_id ][ 'events_data' ], response.events_data );
				}
				
				// Extend or replace the booking lists array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'booking_lists' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'booking_lists' ] = response.booking_lists;
				} else {
					$j.extend( true, bookacti.booking_system[ booking_system_id ][ 'booking_lists' ], response.booking_lists );
				}
				
				// Display new events
				if( response.events.length ) {
					bookacti_booking_method_display_events( booking_system, response.events );
				}
				
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}


/**
 * Reload a booking system
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 * @param {boolean} keep_picked_events
 */
function bookacti_reload_booking_system( booking_system, keep_picked_events ) {
	keep_picked_events = keep_picked_events || false;
	
	var booking_system_id		= booking_system.attr( 'id' );
	var original_attributes		= $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var attributes				= $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var picked_events			= keep_picked_events ? attributes.picked_events : [];
	
	// Specific data
	var rescheduled_booking_data= typeof attributes.rescheduled_booking_data !== 'undefined' ? attributes.rescheduled_booking_data : [];
	var templates_per_activities= typeof attributes.templates_per_activities !== 'undefined' ? attributes.templates_per_activities : [];
	
	// Do not send useless data
	delete attributes[ 'events' ];
	delete attributes[ 'events_data' ];
	delete attributes[ 'events_interval' ];
	delete attributes[ 'bookings' ];
	delete attributes[ 'booking_lists' ];
	delete attributes[ 'activities_data' ];
	delete attributes[ 'groups_events' ];
	delete attributes[ 'groups_data' ];
	delete attributes[ 'group_categories_data' ];
	delete attributes[ 'picked_events' ];
	delete attributes[ 'rescheduled_booking_data' ];
	delete attributes[ 'templates_per_activities' ];
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: {	
			'action': 'bookactiReloadBookingSystem', 
			'attributes': JSON.stringify( attributes ),
			'is_admin': bookacti_localized.is_admin ? 1 : 0
		},
        dataType: 'json',
        success: function( response ) {
			if( response.status === 'success' ) {
				// Clear booking system
				booking_system.empty();
				bookacti_clear_booking_system_displayed_info( booking_system, keep_picked_events );
				
				// Update events and settings
				bookacti.booking_system[ booking_system_id ] = response.booking_system_data;
				bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = picked_events;
				
				// Specific data
				if( rescheduled_booking_data ) { bookacti.booking_system[ booking_system_id ][ 'rescheduled_booking_data' ] = rescheduled_booking_data; }
				if( templates_per_activities ) { bookacti.booking_system[ booking_system_id ][ 'templates_per_activities' ] = templates_per_activities; }
				
				// Fill the booking method elements
				booking_system.append( response.html_elements );
				
				// Update nonce
				if( response.nonces ) {
					$j.each( response.nonces, function( input_name, input_value ) {
						if( $j( 'input[type="hidden"][name="' + input_name + '"]' ).length ) {
							$j( 'input[type="hidden"][name="' + input_name + '"]' ).val( input_value );
						}
					});
				}
				
				// Trigger action for plugins
				booking_system.trigger( 'bookacti_booking_system_reloaded', original_attributes );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system );
				
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });	
}


/**
 * Display events of a specific interval
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} desired_interval { 'start': moment.utc(), 'end': moment.utc() }
 */
function bookacti_fetch_events_from_interval( booking_system, desired_interval ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var current_interval	= bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	var availability_period	= bookacti_get_availability_period( booking_system );
	
	var calendar_start	= moment.utc( availability_period.start );
	var calendar_end	= moment.utc( availability_period.end );
	
	var desired_interval_start	= moment.utc( desired_interval.start ).clone();
	var desired_interval_end	= moment.utc( desired_interval.end ).clone();
	if( desired_interval_start.isBefore( calendar_start ) )	{ desired_interval_start = calendar_start.clone(); }
	if( desired_interval_end.isAfter( calendar_end ) )		{ desired_interval_end = calendar_end.clone(); }
	
	var new_interval		= false;
	var event_load_interval	= parseInt( bookacti_localized.event_load_interval );
	var min_interval		= {
		"start" : desired_interval_start.clone(),
		"end" : desired_interval_end.clone()
	};
	
	// Compute the new interval of events to load
	// If no events has ever been loaded, compute the first interval to load
	if( $j.isEmptyObject( current_interval ) ) { 
		new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval, event_load_interval );
	} 

	// Else, check if the desired_interval contain unloaded days, and if so, load events for this new interval
	else { 
		var current_interval_start	= moment.utc( current_interval.start ).clone().locale( 'en' );
		var current_interval_end	= moment.utc( current_interval.end ).clone().locale( 'en' );

		if( desired_interval_start.isBefore( current_interval_start ) || desired_interval_end.isAfter( current_interval_end ) ) {
			var new_interval_start	= current_interval_start.clone();
			var new_interval_end	= current_interval_end.clone();
			
			var day_before_desired_interval_start	= moment.utc( desired_interval.start ).clone().subtract( 1, 'days' ).locale( 'en' );
			var day_after_desired_interval_end		= moment.utc( desired_interval.end ).clone().add( 1, 'days' ).locale( 'en' );
			
			// If the current desired_interval include the old interval or if they are not connected at all,
			// Remove the current events and fetch events of the new interval
			if((	( desired_interval_start.isBefore( current_interval_start ) && desired_interval_end.isAfter( current_interval_end ) ) 
				||  ( desired_interval_end.isBefore( current_interval_start ) )	
				||  ( desired_interval_start.isAfter( current_interval_end ) ) )
				&&	day_before_desired_interval_start.format( 'YYYY-MM-DD' ) + ' 23:59:59' !== current_interval_end.format( 'YYYY-MM-DD HH:mm:ss' )
				&&	day_after_desired_interval_end.format( 'YYYY-MM-DD' ) + ' 00:00:00' !== current_interval_start.format( 'YYYY-MM-DD HH:mm:ss' ) ){

				// Remove events
				bookacti_booking_method_clear_events( booking_system );

				// Compute new interval
				new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval, event_load_interval );
			}

			else {
				// If the desired interval starts before current interval of events, loads previous bunch of events
				if( desired_interval_start.isBefore( current_interval_start )
				 || day_after_desired_interval_end.format( 'YYYY-MM-DD' ) + ' 00:00:00' === current_interval_start.format( 'YYYY-MM-DD HH:mm:ss' ) 
				) {
					new_interval_start.subtract( event_load_interval, 'days' );
					if( desired_interval_start.isBefore( new_interval_start ) ) {
						new_interval_start = desired_interval_start.clone();
					}
					if( new_interval_start.isBefore( calendar_start ) ) { 
						new_interval_start = calendar_start.clone();
					}
					new_interval_end = moment.utc( current_interval_start.clone().subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' );
				}

				// If the desired interval ends after current interval of events, loads next bunch of events
				else if( desired_interval_end.isAfter( current_interval_end ) 
					  || day_before_desired_interval_start.format( 'YYYY-MM-DD' ) + ' 23:59:59' === current_interval_end.format( 'YYYY-MM-DD HH:mm:ss' )
					 ) {
					new_interval_end.add( event_load_interval, 'days' );
					if( desired_interval_end.isAfter( new_interval_end ) ) {
						new_interval_end = desired_interval_end.clone();
					}
					if( new_interval_end.isAfter( calendar_end ) ) { 
						new_interval_end = calendar_end.clone();
					}
					new_interval_start = moment.utc( current_interval_end.clone().add( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' );
				}

				new_interval = {
					"start": new_interval_start.format( 'YYYY-MM-DD HH:mm:ss' ),
					"end": new_interval_end.format( 'YYYY-MM-DD HH:mm:ss' )
				};
			}
		}
	}

	// Fetch events of the interval
	if( new_interval !== false ) {
		if( booking_system_id === 'bookacti-template-calendar' ) {
			bookacti_fetch_events_on_template( null, new_interval );
		} else {
			bookacti_fetch_events( booking_system, new_interval );
		}
	}
}


/**
 * Get the first events interval
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} min_interval
 * @param {int} interval_duration
 * @returns {object}
 */
function bookacti_get_new_interval_of_events( booking_system, min_interval, interval_duration ) {
	var booking_system_id = booking_system.attr( 'id' );
	var availability_period = bookacti_get_availability_period( booking_system );
	
	if( typeof availability_period.start === 'undefined' || typeof availability_period.end === 'undefined' ) { return {}; }
	
	var past_events		= bookacti.booking_system[ booking_system_id ][ 'past_events' ];
	var current_time	= moment.utc( bookacti_localized.current_time ).locale( 'en' );
	var current_date	= current_time.format( 'YYYY-MM-DD HH:mm:ss' );
	
	var calendar_start	= moment.utc( availability_period.start ).locale( 'en' );
	var calendar_end	= moment.utc( availability_period.end ).locale( 'en' );
	
	if( ! past_events && calendar_end.isBefore( current_time ) ) { return []; }
	
	if( $j.isEmptyObject( min_interval ) ) {
		if( calendar_start.isAfter( current_time ) ) {
			min_interval = { "start": availability_period.start, "end": availability_period.start };
		} else if( calendar_end.isBefore( current_time ) ) {
			min_interval = { "start": availability_period.end, "end": availability_period.end };
		} else {
			min_interval = { "start": current_date, "end": current_date };
		}
	}
	
	interval_duration = parseInt( interval_duration ) || parseInt( bookacti_localized.event_load_interval );
	
	var interval_start	= moment.utc( moment.utc( min_interval.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' );
	var interval_end	= moment.utc( moment.utc( min_interval.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' );
	var min_interval_duration = parseInt( Math.abs( moment.utc( min_interval.end ).diff( min_interval.start, 'days' ) ) );
	
	if( min_interval_duration > interval_duration ) { interval_duration = min_interval_duration; }
	
	var half_interval = Math.round( ( interval_duration - min_interval_duration ) / 2 );
	var interval_end_days_to_add = half_interval;
	
	// Compute Interval start
	if( past_events ) {
		interval_start.subtract( half_interval, 'days' );
		if( calendar_start.isAfter( interval_start ) ) {
			interval_end_days_to_add += Math.abs( interval_start.diff( calendar_start, 'days' ) );
		}
	} else {
		interval_end_days_to_add += half_interval;
	}
	if( calendar_start.isAfter( interval_start ) ) { interval_start = calendar_start.clone(); }
	
	// Compute interval end
	interval_end.add( interval_end_days_to_add, 'days' );
	if( calendar_end.isBefore( interval_end ) ) { interval_end = calendar_end; }

	var interval = {
		"start"	: interval_start.format( 'YYYY-MM-DD HH:mm:ss' ), 
		"end"	: interval_end.format( 'YYYY-MM-DD HH:mm:ss' ) 
	};

	return interval;
}


/**
 * Get the updated events interval based on the old one and one that has been added
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {object} interval
 * @returns {object}
 */
function bookacti_get_extended_events_interval( booking_system, interval ) {
	var booking_system_id = booking_system.attr( 'id' );
	var old_interval = bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	if( $j.isEmptyObject( interval ) ) { return old_interval; }
	
	var new_interval = [];
	if( $j.isEmptyObject( old_interval ) ) {
		new_interval = { "start" : interval.start, "end" : interval.end };
	} else {
		new_interval = {
			"start"	: moment.utc( interval.start ).isBefore( moment.utc( old_interval.start ) ) ? interval.start : old_interval.start,
			"end"	: moment.utc( interval.end ).isAfter( moment.utc( old_interval.end ) ) ? interval.end : old_interval.end
		};
	}
	return new_interval;
}


/**
 * Get availability period
 * @version 1.7.17
 * @param {HTMLElement} booking_system
 * @returns {object}
 */
function bookacti_get_availability_period( booking_system ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_system_data	= bookacti.booking_system[ booking_system_id ];
	return { "start": booking_system_data.start, "end": booking_system_data.end };
}


/**
 * Refresh booking numbers
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {array} event_ids
 */
function bookacti_refresh_booking_numbers( booking_system, event_ids ) {
	event_ids = event_ids || null;
	if( event_ids && ! $j.isArray( event_ids ) ) { event_ids = [ event_ids ]; }
	
	var booking_system_id	= booking_system.attr( 'id' );
	var template_ids		= bookacti.booking_system[ booking_system_id ][ 'calendars' ];
	
	if( booking_system_id === 'bookacti-template-calendar' ) {
		bookacti_start_template_loading();
	} else {
		bookacti_start_loading_booking_system( booking_system );
	}

    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiGetBookingNumbers', 
			'template_id': template_ids, 
			'event_id': event_ids
		},
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				if( event_ids != null ) {
					$j.each( event_ids, function( i, event_id ) {
						if( bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event_id ] ) {
							delete bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event_id ];
						}
						bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event_id ] = response[ 'bookings' ][ event_id ];
					});
				} else {
					bookacti.booking_system[ booking_system_id ][ 'bookings' ] = response[ 'bookings' ];
				}
				
				bookacti_booking_method_rerender_events( booking_system );
			}
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() { 
			if( booking_system_id === 'bookacti-template-calendar' ) {
				bookacti_stop_template_loading();
			} else {
				bookacti_stop_loading_booking_system( booking_system );
			}
		}
    });
}


/**
 * An event is clicked
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 * @param {object} event
 */
function bookacti_event_click( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	// Get the group id of an event or open a dialog to choose a group
	var group_ids = 'single';
	if( booking_system_id !== 'bookacti-booking-system-reschedule' ) {
		group_ids = bookacti_get_event_group_ids( booking_system, event );
	}
	
	// Open a dialog to choose the group of events 
	// if there are several groups, or if users can choose between the single event and at least one group
	var open_dialog = false;
	if( $j.isArray( group_ids )
	&&	(	( group_ids.length > 1 )
		||  ( group_ids.length === 1 && bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) ) ) {
		open_dialog = true;
		bookacti_dialog_choose_group_of_events( booking_system, group_ids, event );
	} else {
		// Pick events (single or whole group)
		bookacti_unpick_all_events( booking_system );
		bookacti_pick_events_of_group( booking_system, group_ids, event );
		
		if( ! open_dialog && $j.isArray( group_ids ) ) {
			booking_system.trigger( 'bookacti_group_of_events_chosen', [ group_ids[ 0 ], event ] );
		}
	}
	
	// If there is only one event in the array, extract it
	group_ids = $j.isArray( group_ids ) && group_ids.length === 1 ? group_ids[ 0 ] : group_ids;
	
	// Yell the event has been clicked
	booking_system.trigger( 'bookacti_event_click', [ event, group_ids, open_dialog ] );
}


/**
 * Get the groups ids of an event
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {array|"single"|false}
 */
function bookacti_get_event_group_ids( booking_system, event ) {
	// Check required data
	if( typeof event !== 'object' ) {
		return false;
	} else if( typeof event.id === 'undefined' || typeof event.start === 'undefined' || typeof event.end === 'undefined' ) {
		return false;
	}
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Format data
	var event_id	= event.id;
	var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end	= moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	var group_ids = [];
	
	$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ], function( group_id, group_events ){
		$j.each( group_events, function( i, group_event ){
			if( group_event[ 'id' ] == event_id
			&&  group_event[ 'start' ] === event_start
			&&  group_event[ 'end' ] === event_end ) {
				group_ids.push( group_id );
				return false; // Break the loop
			}
		});
	});
	
	// If event is single
	if( ! group_ids.length ) {
		group_ids = 'single';
	}
	
	return group_ids;
}


/**
 * Fill form fields
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @param {int} group_id
 */
function bookacti_fill_booking_system_fields( booking_system, event, group_id ) {
	group_id = $j.isArray( group_id ) && group_id.length === 1 ? group_id[ 0 ] : group_id;
	group_id = $j.isNumeric( group_id ) || group_id === 'single' ? group_id : false;
	
	var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end	= moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	// Fill the form fields
	if( group_id !== false ) {
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( group_id );
	}
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( event.id );
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( event_start );
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( event_end );
}


/**
 * Pick all events of a group onto the calendar
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 * @param {int|string} group_id
 * @param {object} event
 */
function bookacti_pick_events_of_group( booking_system, group_id, event ) {
	group_id = $j.isArray( group_id ) && group_id.length === 1 ? group_id[ 0 ] : group_id;
	group_id = $j.isNumeric( group_id ) || group_id === 'single' ? group_id : false;
	
	// Sanitize inputs
	if(   group_id === 'single'	&& typeof event !== 'object' ) { return false; }
	if( ! group_id				&& typeof event !== 'object' ) { return false; }
	if( ! group_id				&& typeof event === 'object' ) { group_id = 'single'; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Pick a single event or the whole group
	if( group_id === 'single' ) {
		bookacti_pick_event( booking_system, event );
	} 
	// Pick the events of the group
	else if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] !== 'undefined' ) {
		$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, grouped_event ){
			bookacti_pick_event( booking_system, grouped_event, group_id );
		});
	}
	
	booking_system.trigger( 'bookacti_events_picked', [ group_id, event ] );
}


/**
 * Pick an event
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @param {int} group_id
 */
function bookacti_pick_event( booking_system, event, group_id ) {
	group_id = group_id || false;
	var booking_system_id = booking_system.attr( 'id' );
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) ) ) {
		return false;
	}
	
	// Format event object
	var picked_event = {
		"id": event.id,
		"title": event.title,
		"start": moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		"end": moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		"group_id": parseInt( group_id )
	};
	
	// Keep picked events in memory 
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ].push( picked_event );
	
	// Sort the picked events
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ].sort( function ( a, b ) {
		var x = moment.utc( a['start'] ); var y = moment.utc( b['start'] );
		return ((x.isBefore(y)) ? -1 : ((x.isAfter(y)) ? 1 : 0));
	});
	
	booking_system.trigger( 'bookacti_pick_event', [ event ] );
}


/**
 * Reset picked events
 * @param {HTMLElement} booking_system
 */
function bookacti_unpick_all_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = [];
	
	booking_system.trigger( 'bookacti_unpick_all_events' );
}


/**
 * Display a list of picked events
 * @version 1.8.9
 * @param {HTMLElement} booking_system
 */
function bookacti_fill_picked_events_list( booking_system ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var event_list_title	= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list-title' );
	var event_list			= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' );
	
	event_list.empty();
	booking_system.siblings( '.bookacti-picked-events' ).hide();
	
	if( typeof bookacti.booking_system[ booking_system_id ][ 'picked_events' ] === 'undefined' ) { return; }
	if( ! bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length ) { return; }

	// Fill title with singular or plural
	var title = bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length === 1 ? bookacti_localized.selected_event : bookacti_localized.selected_events;
	event_list_title.html( title );

	// Fill the picked events list
	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, event ) {
		var event_duration = bookacti_format_event_duration( event.start, event.end );
		var event_data = {
			'title': event.title,
			'duration': event_duration,
			'quantity': 1
		};

		booking_system.trigger( 'bookacti_picked_events_list_data', [ event_data, event ] );

		var activity_id = 0;
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ] !== 'undefined' ) {
				activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ];
			}
		}

		var unit = bookacti_get_activity_unit( booking_system, activity_id, event_data.quantity );
		if( unit !== '' ) {
			unit = '<span class="bookacti-booking-event-quantity-separator" > - </span>' 
				 + '<span class="bookacti-booking-event-quantity" >' + unit + '</span>';
		}
		var list_element_data = {
			'html': '<span class="bookacti-booking-event-title" >' + event_data.title + '</span><span class="bookacti-booking-event-title-separator" > - </span>' + event_data.duration + unit
		};
		
		var list_element = $j( '<li></li>', list_element_data );
		
		// Add attributes to list elements to identify them
		var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		
		list_element.data( 'event-id', event.id ).attr( 'data-event-id', event.id );
		list_element.data( 'event-start', event_start_formatted ).attr( 'data-event-start', event_start_formatted );
		list_element.data( 'event-end', event_end_formatted ).attr( 'data-event-end', event_end_formatted );
		
		// Add grouped event to the list
		if( event.group_id && event.group_id !== 'single' ) {
			// Add the grouped events list
			if( ! event_list.find( 'li[data-group-id="' + event.group_id + '"] ul' ).length ) {
				// Get the group title
				var group_title = '';
				if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ event.group_id ] !== 'undefined' ) {
					var group = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ event.group_id ];
					group_title = '<span class="bookacti-picked-group-of-events-title">' + group.title + '</span>';
				}
				
				var group_list_element = $j( '<li></li>', { 'html': group_title + '<ul class="bookacti-picked-group-of-events-list">' + list_element[0].outerHTML + '</ul>' } );
				group_list_element.data( 'group-id', event.group_id ).attr( 'data-group-id', event.group_id );
				event_list.append( group_list_element );
			} 
			// The grouped events list was already added
			else {
				var group_list_element = event_list.find( 'li[data-group-id="' + event.group_id + '"] ul' );
				group_list_element.append( list_element );
			}
		}
		
		// Add a single event to the list
		else {
			event_list.append( list_element );
		}
	});
	
	if( ! event_list.is( ':empty' ) ) {
		booking_system.siblings( '.bookacti-picked-events' ).show();
	}
	
	booking_system.trigger( 'bookacti_picked_events_list_filled' );
}


/**
 * Place the tooltip div below or above the element
 * @since 1.8.0
 * @param {HTMLElement} element
 * @param {HTMLElement} tooltip_container
 * @param {string} position "below" or "above"
 */
function bookacti_set_tooltip_position( element, tooltip_container, position ) {
	position = $j.inArray( position, [ 'below', 'above' ] ) >= 0 ? position : 'below';
	tooltip_container.css( 'position', 'absolute' );

	// Resize if larger than the viewport
	var viewport_width = $j( window ).width();
	if( tooltip_container.outerWidth() > viewport_width ) { tooltip_container.outerWidth( viewport_width ); }

	// Place the tooltip in the center of the event
	var new_offset = element.offset();
	var center = element.offset().left + ( element.outerWidth() / 2 );

	new_offset.top += position === 'above' ? ( ( tooltip_container.outerHeight() + 15 ) * -1 ) : element.height() + 15;
	new_offset.left = center - ( tooltip_container.outerWidth() / 2 );

	// Add an offset if the tooltip is offscreen
	// Offscreen from the left
	if( new_offset.left < 0 ) { new_offset.left = 0; }

	// Offscreen from the right
	var tooltip_container_right = new_offset.left + tooltip_container.outerWidth();
	if( tooltip_container_right > viewport_width ) { new_offset.left -= ( tooltip_container_right - viewport_width ); }
	
	tooltip_container.offset( new_offset );
	
	// Arrow position
	var arrow_class = position === 'above' ? 'bookacti-tooltip-arrow-bottom' : 'bookacti-tooltip-arrow-top';
	if( ! tooltip_container.find( '.bookacti-tooltip-arrow.' + arrow_class ).length ) { 
		tooltip_container.find( '.bookacti-tooltip-arrow' ).remove();
		tooltip_container.append( '<div class="bookacti-tooltip-arrow ' + arrow_class + '"></div>' );
	}
	var arrow_container = tooltip_container.find( '.bookacti-tooltip-arrow' );
	var arrow_position = center - new_offset.left - ( arrow_container.outerWidth() / 2 );
	arrow_container.css( 'left', arrow_position + 'px' );
}


/**
 * Set min and max quantity on the quantity field
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {HTMLElement} qty_field
 * @param {object} event_summary_data
 */
function bookacti_set_min_and_max_quantity( booking_system, qty_field, event_summary_data ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var quantity			= parseInt( qty_field.val() );
	var new_quantity		= parseInt( qty_field.val() );
	var available_places	= 0; 
	var quantity_booked		= 0; 
	var min_quantity		= 1;
	var max_quantity		= false;
	
	// Groups of events
	if( $j.isNumeric( booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val() ) ) {
		var group_id = booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val();
		if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ] !== 'undefined' ) {
				var category_id = parseInt( bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'category_id' ] );
				if( typeof bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ] !== 'undefined' ) {
					if( typeof bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ][ category_id ] !== 'undefined' ) {
						if( typeof bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ][ category_id ][ 'settings' ] !== 'undefined' ) {
							var category_data	= bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ][ category_id ][ 'settings' ];
							min_quantity		= typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 1 );
							max_quantity		= typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : false );
							available_places	= bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'availability' ];

							if( min_quantity || max_quantity ) {
								quantity_booked = parseInt( bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'current_user_bookings' ] );
							}
						}
					}
				}
			}
		}
		
	// Single events
	} else {
		var event = bookacti.booking_system[ booking_system_id ][ 'picked_events' ][ 0 ];
		var event_id = parseInt( event.id );
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ] !== 'undefined' ) {
				var activity_id = parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'activity_id' ] );
				if( typeof bookacti.booking_system[ booking_system_id ][ 'activities_data' ] !== 'undefined' ) {
					if( typeof bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ] !== 'undefined' ) {
						if( typeof bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ][ 'settings' ] !== 'undefined' ) {
							var activity_data	= bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ][ 'settings' ];
							min_quantity		= typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 1 );
							max_quantity		= typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : false );
							available_places	= bookacti_get_event_availability( booking_system, event );

							if( min_quantity || max_quantity ) {
								var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
								if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ] !== 'undefined' ) {
									if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ] !== 'undefined' ) {
										var occurrence = bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ];
										quantity_booked = parseInt( occurrence[ 'current_user_bookings' ] );
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	// Limit the max quantity
	max_quantity = max_quantity && max_quantity != 0 && ( max_quantity - quantity_booked ) < available_places ? Math.max( ( max_quantity - quantity_booked ), 0 ) : available_places;
	qty_field.attr( 'max', max_quantity );
	if( quantity > max_quantity ) {
		qty_field.val( max_quantity );
		new_quantity = max_quantity;
	}
	
	// Force a min quantity
	min_quantity = min_quantity && min_quantity != 0 && min_quantity > 1 && quantity_booked < min_quantity ? Math.max( ( min_quantity - quantity_booked ), 0 ) : 1;
	qty_field.attr( 'min', min_quantity );
	if( quantity < min_quantity ) {
		// If min required bookings is higher than available places, 
		// keep the higher amount to feedback that there are not enough places
		if( min_quantity > available_places ) { qty_field.attr( 'max', min_quantity ); }
		qty_field.val( min_quantity );
		new_quantity = min_quantity;
	}

	event_summary_data.quantity = new_quantity;
	
	if( quantity !== new_quantity ) { qty_field.trigger( 'bookacti_quantity_updated', [ quantity, event_summary_data ] ); }
}


/**
 * Format an event duration
 * @version 1.8.5
 * @param {moment|string} start
 * @param {moment|string} end
 * @returns {String}
 */
function bookacti_format_event_duration( start, end ) {
	start = moment.utc( start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	end = moment.utc( end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	var event_start = moment.utc( start ).clone().locale( bookacti_localized.current_lang_code );
	var event_end = moment.utc( end ).clone().locale( bookacti_localized.current_lang_code );
	
	var start_and_end_same_day	= start.substr( 0, 10 ) === end.substr( 0, 10 );
	var class_same_day			= start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
	var event_end_formatted		= start_and_end_same_day ? event_end.formatPHP( bookacti_localized.time_format ) : event_end.formatPHP( bookacti_localized.date_format );
	var separator				= start_and_end_same_day ? bookacti_localized.date_time_separator : bookacti_localized.dates_separator;
	
	var event_duration	= '<span class="bookacti-booking-event-start">' + event_start.formatPHP( bookacti_localized.date_format ) + '</span>' 
						+ '<span class="bookacti-booking-event-date-separator ' + class_same_day + '">' + separator +  '</span>' 
						+ '<span class="bookacti-booking-event-end ' + class_same_day + '">' + event_end_formatted + '</span>';
	
	return event_duration;
}


/**
 * Get activity unit value
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {int} activity_id
 * @param {int} qty
 * @returns {string}
 */
function bookacti_get_activity_unit( booking_system, activity_id, qty ) {
	qty	= $j.isNumeric( qty ) ? parseInt( qty ) : 1;
	if( ! activity_id || qty === 0 ) { return ''; }
	
	var booking_system_id = booking_system.attr( 'id' );
	if( typeof bookacti.booking_system[ booking_system_id ][ 'activities_data' ] === 'undefined' ) { return ''; }
	if( typeof bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ] === 'undefined' ) { return ''; }
	
	var activity_data = bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ];
	if( typeof activity_data[ 'settings' ] === 'undefined' ) { return ''; }
	
	// Display qty and unit name
	if( typeof activity_data[ 'settings' ][ 'unit_name_plural' ] === 'undefined'
	||  typeof activity_data[ 'settings' ][ 'unit_name_singular' ] === 'undefined' ) { return ''; }
	if( activity_data[ 'settings' ][ 'unit_name_plural' ] === ''
	||  activity_data[ 'settings' ][ 'unit_name_singular' ] === '' ) { return ''; }
	
	var activity_val = qty + ' ';
	activity_val += qty === 1 ? activity_data[ 'settings' ][ 'unit_name_singular' ] : activity_data[ 'settings' ][ 'unit_name_plural' ];
	
	// Display people per booking
	if( typeof activity_data[ 'settings' ][ 'places_number' ] === 'undefined' ) { return activity_val + '<br/>'; }
	if( activity_data[ 'settings' ][ 'places_number' ] === '' 
	||  parseInt( activity_data[ 'settings' ][ 'places_number' ] ) === 0 ) { return activity_val + '<br/>'; }
	
	activity_val += ' ';
	activity_val += parseInt( activity_data[ 'settings' ][ 'places_number' ] ) === 1 ? bookacti_localized.one_person_per_booking : bookacti_localized.n_people_per_booking.replace( '%1$s', activity_data[ 'settings' ][ 'places_number' ] );
	activity_val += '<br/>';
	
	return activity_val;
}


/**
 * Clear booking system displayed info
 * @version 1.7.0
 * @param {HTMLElement} booking_system
 * @param {boolean} keep_picked_events
 */
function bookacti_clear_booking_system_displayed_info( booking_system, keep_picked_events ) {
	keep_picked_events = keep_picked_events || false;
	
	// Empty the picked events info
	if( ! keep_picked_events ) { 
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input' ).val('');
		booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' ).empty();
		booking_system.siblings( '.bookacti-picked-events' ).hide();
		bookacti_unpick_all_events( booking_system ); 
	}
	
	// Clear errors
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
	booking_system.show();
	
	booking_system.trigger( 'bookacti_displayed_info_cleared' );
}


/**
 * Get event booking numbers
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {Object} event
 * @returns {Int}
 */
function bookacti_get_event_number_of_bookings( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var event_start	= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ] === 'undefined' ) { return 0; }
	if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ] === 'undefined' ) { return 0; }
	if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start ] === 'undefined' ) { return 0; }
	if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] === 'undefined' ) { return 0; }
	if( ! $j.isNumeric( bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] ) ) { return 0; }
	
	return parseInt( bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] );
}


/**
 * Get event available places
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {int}
 */
function bookacti_get_event_availability( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	var event_availability = 0;
	if( typeof event.availability !== 'undefined' ) { event_availability = event.availability; }
	if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ] !== 'undefined' ) { event_availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ]; }
	
	var event_bookings = bookacti_get_event_number_of_bookings( booking_system, event );
	return parseInt( event_availability ) - parseInt( event_bookings );
}


/**
 * Check if an event is event available
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {boolean}
 */
function bookacti_is_event_available( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var past_events			= bookacti.booking_system[ booking_system_id ][ 'past_events' ];
	var past_events_bookable= bookacti.booking_system[ booking_system_id ][ 'past_events_bookable' ];
	
	var current_time	= moment.utc( bookacti_localized.current_time );
	var event_start		= moment.utc( event.start ).clone().locale( 'en' );
	var event_end		= moment.utc( event.end ).clone().locale( 'en' );
	
	var availability		= bookacti_get_event_availability( booking_system, event );
	var availability_period	= bookacti_get_availability_period( booking_system );
	var is_available		= false;
	
	if( availability <= 0 ) { return false; }
	
	// Check if the event is part of a group
	var group_ids				= bookacti_get_event_group_ids( booking_system, event );
	var is_in_group				= $j.isArray( group_ids ) && group_ids.length > 0;
	var groups_single_events	= bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ];
	
	// On the reschedule calendar
	if( booking_system_id === 'bookacti-booking-system-reschedule' ) {
		// If the event is part of a group (and not bookable alone), it cannot be available
		if( is_in_group && ! groups_single_events ) { return false; }
		
		var rescheduled_booking_data = typeof bookacti.booking_system[ booking_system_id ][ 'rescheduled_booking_data' ] !== 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'rescheduled_booking_data' ] : [];
		
		// Don't display self event
		if( typeof rescheduled_booking_data.event_id !== 'undefined'
		&&  typeof rescheduled_booking_data.event_start !== 'undefined'
		&&  typeof rescheduled_booking_data.event_end !== 'undefined' ) {
			if( rescheduled_booking_data.event_id == event.id
			&&  rescheduled_booking_data.event_start === event_start.format( 'YYYY-MM-DD HH:mm:ss' )
			&&  rescheduled_booking_data.event_end === event_end.format( 'YYYY-MM-DD HH:mm:ss' ) ) { return false; }
		}

		// Don't display event if it hasn't enough availability
		if( typeof rescheduled_booking_data.quantity !== 'undefined' ) {
			if( parseInt( rescheduled_booking_data.quantity ) > availability ) { return false; }
		}
	}
	
	// Single events
	if( ( ! is_in_group || ( is_in_group && groups_single_events ) ) 
	&&  typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ] !== 'undefined' ) {
		// Check if the event is past or out of the availability period
		var is_past = false;
		if( past_events ) {
			// Check if the event is past
			if( ! past_events_bookable && event_start.isBefore( current_time ) 
			&& ! ( bookacti_localized.started_events_bookable && event_end.isAfter( current_time ) ) ) {
				is_past = true;
			}
			
			// Check if the event is in the availability period
			if( ! past_events_bookable && ( event_start.isBefore( moment.utc( availability_period.start ) ) || event_end.isAfter( moment.utc( availability_period.end ) ) ) ) {
				is_past = true;
			}
		}
		
		if( ! is_past ) {
			// Check the min required quantity
			var activity_id		= parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ] );
			var activity_data	= bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ][ 'settings' ];

			// Check the max quantity allowed AND
			// Check the max number of different users allowed
			var min_qty_ok = max_qty_ok = max_users_ok = true;
			event_start_formatted = event_start.format( 'YYYY-MM-DD HH:mm:ss' );
			if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ] !== 'undefined' ) {
				if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ] !== 'undefined' ) {
					var min_quantity	= typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 0 );
					var max_quantity	= typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : 0 );
					var max_users		= typeof activity_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( activity_data[ 'max_users_per_event' ] ? parseInt( activity_data[ 'max_users_per_event' ] ) : 0 );

					if( min_quantity || max_quantity || max_users ) {
						var occurrence = bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ];
						var qty_booked = parseInt( occurrence[ 'current_user_bookings' ] );
						if( max_users && qty_booked === 0 && occurrence[ 'distinct_users' ] >= max_users ) {
							max_users_ok = false;
						}
						if( max_quantity && qty_booked >= max_quantity ) {
							max_qty_ok = false;
						}
						if( min_quantity && min_quantity > availability + qty_booked ) { 
							min_qty_ok = false; 
						}
					}
				}
			}

			if( min_qty_ok && max_qty_ok && max_users_ok ) { is_available = true; }
		}
	}
	
	// Check if at least one group is available
	if( is_in_group && ! is_available ) {
		$j.each( group_ids, function( i, group_id ) {
			var group					= bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ];
			var category_id				= parseInt( group[ 'category_id' ] );
			var category_data			= bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ][ category_id ][ 'settings' ];
			var started_groups_bookable	= bookacti_localized.started_groups_bookable;
			if( typeof category_data[ 'started_groups_bookable' ] !== 'undefined' ) {
				if( $j.inArray( category_data[ 'started_groups_bookable' ], [ 0, 1, '0', '1', true, false ] ) >= 0 ) {
					started_groups_bookable	= parseInt( category_data[ 'started_groups_bookable' ] );
				}
			}
			
			// Check if the group is past
			var group_start	= moment.utc( group.start ).clone();
			var group_end	= moment.utc( group.end ).clone();
			if( ! past_events_bookable && group_start.isBefore( current_time ) 
			&& ! ( started_groups_bookable && group_end.isAfter( current_time ) ) ) {
				return true; // Skip this group
			}
			
			// Check if the group of events is in the availability period
			if( ! past_events_bookable && ( group_start.isBefore( moment.utc( availability_period.start ) ) || group_end.isAfter( moment.utc( availability_period.end ) ) ) ) {
				return true; // Skip this group
			}
			
			var group_availability = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'availability' ];
			if( group_availability > 0 ) {
				
				// Check the min and max quantity allowed AND
				// Check the max number of different users allowed
				var min_qty_ok = max_qty_ok = max_users_ok = true;
				if( group != null ) {
					var max_users		= typeof category_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( category_data[ 'max_users_per_event' ] ? parseInt( category_data[ 'max_users_per_event' ] ) : 0 );
					var max_quantity	= typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : 0 );
					var min_quantity	= typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 0 );
					
					if( min_quantity || max_quantity || max_users ) {
						var qty_booked = parseInt( group[ 'current_user_bookings' ] );
						if( max_users && qty_booked === 0 && group[ 'distinct_users' ] >= max_users ) {
							max_users_ok = false;
						}
						if( max_quantity && qty_booked >= max_quantity ) {
							max_qty_ok = false;
						}
						if( min_quantity && min_quantity > group_availability + qty_booked ) { 
							min_qty_ok = false; 
						}
					}
				}
				if( min_qty_ok && max_qty_ok && max_users_ok ) { 
					is_available = true; 
					return false;  // Break the loop
				} 
			}
		});
	}
	
	return is_available;
}


/**
 * Get group available places
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @param {array} event_groups
 * @returns {Number}
 */
function bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, event_groups ) {
	var booking_system_id = booking_system.attr( 'id' );
	event_groups = event_groups || bookacti_get_event_group_ids( booking_system, event );
	
	var event_start	= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end	= moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );

	var event_bookings	= bookacti_get_event_number_of_bookings( booking_system, event );
	var all_groups		= bookacti.booking_system[ booking_system_id ][ 'groups_events' ];
	
	var group_bookings = 0;
	$j.each( event_groups, function( i, group_id ){
		$j.each( all_groups[ group_id ], function( i, grouped_event ){
			if( event.id === grouped_event.id
			&&  event_start === grouped_event.start 
			&&  event_end === grouped_event.end ) {
				group_bookings += parseInt( grouped_event.group_bookings );
			}
		});
	});
	
	return event_bookings - group_bookings;
}


/**
 * Get a div with event available places
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {String}
 */
function bookacti_get_event_availability_div( booking_system, event ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	var available_places = bookacti_get_event_availability( booking_system, event );
	
	var unit_name = '';
	var activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ];
	if( activity_id ) {
		
		var activity_data = bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ];
		
		if( activity_data !== undefined ) {
			if( activity_data[ 'settings' ] !== undefined ) {
				if( activity_data[ 'settings' ][ 'unit_name_plural' ] !== undefined
				&&  activity_data[ 'settings' ][ 'unit_name_singular' ] !== undefined 
				&&  activity_data[ 'settings' ][ 'show_unit_in_availability' ] !== undefined ) {
					if( parseInt( activity_data[ 'settings' ][ 'show_unit_in_availability' ] ) ) {
						if( available_places === 1 ) {
							unit_name = activity_data[ 'settings' ][ 'unit_name_singular' ];
						} else {
							unit_name = activity_data[ 'settings' ][ 'unit_name_plural' ];
						}
					}
				}
			}
		}
	}
	
	var avail = available_places > 1 ? bookacti_localized.avails : bookacti_localized.avail;
	
	//Detect if the event is available or full, and if it is booked or not
	var availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ];
	var class_booked = available_places < availability ? 'bookacti-booked' : 'bookacti-not-booked';
	var class_full = available_places <= 0 ? 'bookacti-full' : '';
	
	//Build a div with availability
	var div = '<div class="bookacti-availability-container" >' 
				+ '<span class="bookacti-available-places ' + class_booked + ' ' + class_full + '" >'
					+ '<span class="bookacti-available-places-number">' + available_places + '</span>' 
					+ '<span class="bookacti-available-places-unit-name"> ' + unit_name + '</span>' 
					+ '<span class="bookacti-available-places-avail-particle"> ' + avail + '</span>'
				+ '</span>'
			+ '</div>';
	
	return div;
}


/**
 * Get a div with event booking number
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {String}
 */
function bookacti_get_event_number_of_bookings_div( booking_system, event ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var availability		= parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ] );
	var bookings_number		= bookacti_get_event_number_of_bookings( booking_system, event );
	var available_places	= bookacti_get_event_availability( booking_system, event );
	
	var class_no_availability	= availability === 0 ? 'bookacti-no-availability' : '';
	var class_booked			= bookings_number > 0 ? 'bookacti-booked' : 'bookacti-not-booked';
	var class_full				= available_places <= 0 ? 'bookacti-full' : '';
	
	var div	= '<div class="bookacti-availability-container" >' 
				+ '<span class="bookacti-available-places ' + class_booked + ' ' + class_full + ' ' + class_no_availability + '" >'
					+ '<span class="bookacti-active-bookings-number">' + bookings_number + '</span>'
				+ '</span>'
			+ '</div>';
	
	return div;
}


/**
 * Sort an array of events by dates
 * @param {array} array
 * @param {boolean} sort_by_end
 * @param {boolean} desc
 * @param {objct} labels
 * @returns {array}
 */
function bookacti_sort_events_array_by_dates( array, sort_by_end, desc, labels ) {
	
	sort_by_end = sort_by_end || false;
	desc = desc || false;
	labels = labels || { 'start': 'start', 'end': 'end' };
	
	array.sort( function( a, b ) {
		
		// Sort by start date ASC
		var sort = sort_by_end ? 0 : new Date( a[ labels.start ] ) - new Date( b[ labels.start ] );
		
		// If start date is the same, then sort by end date ASC
		if( sort === 0 ) {
			sort = new Date( a[ labels.end ] ) - new Date( b[ labels.end ] );
		}
		
		if( desc === true ) { sort = ! sort; }
		
		return sort;
	});
	
	return array;
}


// Booking system actions based on booking method

/**
 * Load the booking system according to booking method
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {boolean} reload_events
 */
function bookacti_booking_method_set_up( booking_system, reload_events ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	reload_events = reload_events ? 1 : 0;
	
	if( bookacti.booking_system[ booking_system_id ][ 'no_events' ] ) { return; }
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_set_calendar_up( booking_system, reload_events );
	} else {
		booking_system.trigger( 'bookacti_booking_method_set_up', [ booking_method, reload_events ] );
	}
	
	// Display picked events list if events were selected by default
	if( ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'picked_events' ] ) 
	&&  typeof bookacti.booking_system[ booking_system_id ][ 'events' ] !== 'undefined' ) {
		bookacti_fill_picked_events_list( booking_system );
	}
}


/**
 * Fill the events according to the booking method
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {object} events
 */
function bookacti_booking_method_display_events( booking_system, events ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_display_events_on_calendar( booking_system, events );
	} else {
		booking_system.trigger( 'bookacti_booking_method_display_events', [ booking_method, events ] );
	}
}


/**
 * Refetch events according to booking method
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 */
function bookacti_booking_method_refetch_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'removeEvents' );
		bookacti_fetch_events( booking_system );
	} else {
		booking_system.trigger( 'bookacti_refetch_events', [ booking_method ] );
	}
}


/**
 * Rerender events according to booking method
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 */
function bookacti_booking_method_rerender_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'rerenderEvents' );
	} else {
		booking_system.trigger( 'bookacti_rerender_events', [ booking_method ] );
	}
}


/**
 * Clear events according to booking method
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {object} event
 */
function bookacti_booking_method_clear_events( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	event = event || null;
	
	// Reset global arrays
	if( ! event ) {
		bookacti.booking_system[ booking_system_id ][ 'events' ]			= [];
		bookacti.booking_system[ booking_system_id ][ 'events_data' ]		= [];
		bookacti.booking_system[ booking_system_id ][ 'events_interval' ]	= [];
	} else {
		delete bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ];
	}
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_clear_events_on_calendar( booking_system, event );
	} else {
		booking_system.trigger( 'bookacti_clear_events', [ booking_method, event ] );
	}
}



// LOADING

/**
 * Start a loading (or keep on loading if already loading)
 * @param {HTMLElement} booking_system
 */
function bookacti_start_loading_booking_system( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
	
	var loading_div = '<div class="bookacti-loading-alt">' 
					+	'<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+	'<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	
	if( ! $j.isNumeric( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] ) ) {
		bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0;
	}
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		if( booking_system.find( '.bookacti-calendar.fc' ).length ) {
			if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 || ! booking_system.find( '.bookacti-loading-overlay' ).length ) {
				booking_system.find( '.bookacti-loading-alt' ).remove();
				bookacti_enter_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
			}
		} else if( ! booking_system.find( '.bookacti-loading-alt' ).length ) {
			booking_system.append( loading_div );
		}
		
	}
	
	if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 ) {
		booking_system.trigger( 'bookacti_enter_loading_state' );
	}
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]++;
	
	booking_system.trigger( 'bookacti_start_loading', [ booking_method, loading_div ] );
}


/**
 * Stop a loading (but keep on loading if there are other loadings)
 * @param {HTMLElement} booking_system
 * @param {boolean} force_exit 
 */
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	
	force_exit = force_exit || false;
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]--;
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = Math.max( bookacti.booking_system[ booking_system_id ][ 'loading_number' ], 0 );
	
	if( force_exit ) { bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0; }
	
	booking_system.trigger( 'bookacti_stop_loading' );
	
	// Action to do after everything has loaded
	if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 ) {
		
		if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {		
			bookacti_exit_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
		}
		
		booking_system.find( '.bookacti-loading-alt' ).remove();
		booking_system.trigger( 'bookacti_exit_loading_state', [ booking_method, force_exit ] );
	}
}




// REDIRECT

/**
 * Redirect to activity url
 * @since 1.7.0
 * @param {HTMLElement} booking_system
 * @param {object} event
 */
function bookacti_redirect_to_activity_url( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event.id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event.id ][ 'activity_id' ];
	if( typeof attributes[ 'redirect_url_by_activity' ][ activity_id ] === 'undefined' ) { return; }
	
	var redirect_url = attributes[ 'redirect_url_by_activity' ][ activity_id ];
	
	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}


/**
 * Redirect to group category url
 * @since 1.7.0
 * @param {HTMLElement} booking_system
 * @param {int} group_id
 */
function bookacti_redirect_to_group_category_url( booking_system, group_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'redirect_url_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var redirect_url = attributes[ 'redirect_url_by_group_category' ][ category_id ];

	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}


/**
 * Redirect to url with the booking form values as parameters
 * @since 1.7.10
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {string} redirect_url
 */
function bookacti_redirect_booking_system_to_url( booking_system, redirect_url ) {
	if( ! redirect_url ) { return; }
	
	// Add form parameters to the URL
	var url_params = '';
	if( ! booking_system.closest( 'form' ).length ) {
		booking_system.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' );
		url_params	= booking_system.closest( 'form' ).serialize();
		booking_system.closest( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' );
	} else {
		url_params	= booking_system.closest( 'form' ).serialize();
	}
	
	var redirect = { 'url': redirect_url, 'params': url_params };
	redirect.url += redirect.url.indexOf( '?' ) >= 0 ? '&' + url_params : '?' + url_params;
	
	booking_system.trigger( 'bookacti_before_redirect', [ redirect ] );
	
	// Redirect to URL
	bookacti_start_loading_booking_system( booking_system );
	window.location.href = redirect.url;
	bookacti_stop_loading_booking_system( booking_system );
}