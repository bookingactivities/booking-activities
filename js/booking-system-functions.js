/**
 * Get booking system data by interval (events, groups, and bookings) 
 * @since 1.12.0 (was bookacti_fetch_events)
 * @version 1.14.0
 * @param {HTMLElement} booking_system
 * @param {object} interval
 */
function bookacti_get_booking_system_data_by_interval( booking_system, interval ) {
	var booking_system_id   = booking_system.attr( 'id' );
	var original_attributes = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var attributes          = bookacti_get_booking_system_attributes_without_data( booking_system );
	
	interval = interval ? interval : $j.extend( true, {}, original_attributes[ 'events_interval' ] );
	
	// Update events interval before success to prevent to fetch the same interval twice
	bookacti.booking_system[ booking_system_id ][ 'events_interval' ] = bookacti_get_extended_events_interval( booking_system, interval );
	
	bookacti_start_loading_booking_system( booking_system );
	
    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiGetBookingSystemDataByInterval', 
			'attributes': JSON.stringify( attributes ),
			'interval': JSON.stringify( interval )
		},
        dataType: 'json',
        success: function( response ) {
			if( response.status === 'success' ) {
				// Extend or replace the events array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'events' ] = response.booking_system_data.events;
				} else {
					$j.extend( bookacti.booking_system[ booking_system_id ][ 'events' ], response.booking_system_data.events );
				}
				
				// Extend or replace the events data array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events_data' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'events_data' ] = response.booking_system_data.events_data;
				} else {
					$j.extend( bookacti.booking_system[ booking_system_id ][ 'events_data' ], response.booking_system_data.events_data );
				}
				
				// Extend or replace the groups array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'groups_events' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'groups_events' ] = response.booking_system_data.groups_events;
				} else {
					$j.extend( true, bookacti.booking_system[ booking_system_id ][ 'groups_events' ], response.booking_system_data.groups_events );
				}
				
				// Extend or replace the groups data array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'groups_data' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'groups_data' ] = response.booking_system_data.groups_data;
				} else {
					$j.extend( bookacti.booking_system[ booking_system_id ][ 'groups_data' ], response.booking_system_data.groups_data );
				}
				
				// Extend or replace the bookings array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'bookings' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'bookings' ] = response.booking_system_data.bookings;
				} else {
					$j.extend( true, bookacti.booking_system[ booking_system_id ][ 'bookings' ], response.booking_system_data.bookings );
				}
				
				// Extend or replace the bookings of groups array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'groups_bookings' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'groups_bookings' ] = response.booking_system_data.groups_bookings;
				} else {
					$j.extend( true, bookacti.booking_system[ booking_system_id ][ 'groups_bookings' ], response.booking_system_data.groups_bookings );
				}
				
				// Extend or replace the booking lists array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'booking_lists' ] ) ) {
					bookacti.booking_system[ booking_system_id ][ 'booking_lists' ] = response.booking_system_data.booking_lists;
				} else {
					$j.extend( true, bookacti.booking_system[ booking_system_id ][ 'booking_lists' ], response.booking_system_data.booking_lists );
				}
				
				// Display new events
				if( response.booking_system_data.events.length ) {
					bookacti_booking_method_display_events( booking_system, response.booking_system_data.events );
				}
				
				booking_system.trigger( 'bookacti_booking_system_interval_data_loaded', [ response, original_attributes, attributes, interval ] );
				
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
 * @version 1.14.0
 * @param {HTMLElement} booking_system
 * @param {boolean} keep_picked_events
 */
function bookacti_reload_booking_system( booking_system, keep_picked_events ) {
	keep_picked_events = keep_picked_events || false;
	
	var booking_system_id   = booking_system.attr( 'id' );
	var original_attributes = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var attributes          = bookacti_get_booking_system_attributes_without_data( booking_system );
	
	if( ! keep_picked_events ) { delete attributes[ 'picked_events' ]; }
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: {	
			'action': 'bookactiReloadBookingSystem', 
			'attributes': JSON.stringify( attributes )
		},
        dataType: 'json',
        success: function( response ) {
			if( response.status === 'success' ) {
				// Clear booking system
				booking_system.empty();
				bookacti_clear_booking_system_displayed_info( booking_system );
				
				// Update events and settings
				bookacti.booking_system[ booking_system_id ] = response.booking_system_data;
				
				// Specific data
				if( typeof original_attributes.rescheduled_booking_data !== 'undefined' ) { bookacti.booking_system[ booking_system_id ][ 'rescheduled_booking_data' ] = $j.extend( true, {}, original_attributes.rescheduled_booking_data ); }
				if( typeof original_attributes.templates_per_activities !== 'undefined' ) { bookacti.booking_system[ booking_system_id ][ 'templates_per_activities' ] = $j.extend( true, {}, original_attributes.templates_per_activities ); }
				
				// Fill the booking method elements
				booking_system.append( response.html_elements );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system );
				
				// Refresh picked events
				bookacti_fill_booking_system_fields( booking_system );
				bookacti_fill_picked_events_list( booking_system );
				
				// Trigger action for plugins
				booking_system.trigger( 'bookacti_booking_system_reloaded', [ response, original_attributes, attributes ] );
				
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
 * Get a copy of the booking system attributes but without data (events, groups, bookings, activities, categories...)
 * @since 1.12.0
 * @param {HTMLElement} booking_system
 * @returns {Object}
 */
function bookacti_get_booking_system_attributes_without_data( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var attributes_without_data = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var booking_system_attributes_keys = bookacti_localized.booking_system_attributes_keys;
	
	$j.each( attributes, function( key, value ) {
		if( $j.inArray( key, booking_system_attributes_keys ) === -1 ) {
			delete attributes_without_data[ key ];
		}
	});
	
	return attributes_without_data;
}


/**
 * Get the interval of events to be loaded according to the desired view interval
 * @since 1.12.0 (was bookacti_fetch_events_from_interval)
 * @version 1.15.6
 * @param {HTMLElement} booking_system
 * @param {object} desired_interval { 'start': moment.utc(), 'end': moment.utc() }
 * @returns {Object}
 */
function bookacti_get_interval_of_events( booking_system, desired_interval ) {
	var booking_system_id   = booking_system.attr( 'id' );
	var current_interval    = bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	var availability_period = bookacti_get_availability_period( booking_system );
	var event_load_interval = parseInt( bookacti_localized.event_load_interval );
	
	var calendar_start = availability_period.start ? moment.utc( availability_period.start ) : false;
	var calendar_end   = availability_period.end ? moment.utc( availability_period.end ) : false;
	
	var desired_interval_start = moment.utc( desired_interval.start ).clone();
	var desired_interval_end   = moment.utc( desired_interval.end ).clone();
	if( calendar_start ) { if( desired_interval_start.isBefore( calendar_start ) ) { desired_interval_start = calendar_start.clone(); } }
	if( calendar_end )   { if( desired_interval_end.isAfter( calendar_end ) )      { desired_interval_end = calendar_end.clone(); } }
	
	var new_interval = {};
	var min_interval = {
		'start' : desired_interval_start.clone(),
		'end' : desired_interval_end.clone()
	};
	
	// Compute the new interval of events to load
	
	// If no events has ever been loaded, compute the first interval to load
	if( $j.isEmptyObject( current_interval ) ) { 
		new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval );
	} 

	// Else, check if the desired_interval contain unloaded days, and if so, load events for this new interval
	else { 
		var current_interval_start = moment.utc( current_interval.start ).clone().locale( 'en' );
		var current_interval_end   = moment.utc( current_interval.end ).clone().locale( 'en' );

		if( desired_interval_start.isBefore( current_interval_start ) || desired_interval_end.isAfter( current_interval_end ) ) {
			var new_interval_start = current_interval_start.clone();
			var new_interval_end   = current_interval_end.clone();
			
			var day_before_desired_interval_start = moment.utc( desired_interval.start ).clone().subtract( 1, 'days' ).locale( 'en' );
			var day_after_desired_interval_end    = moment.utc( desired_interval.end ).clone().add( 1, 'days' ).locale( 'en' );
			
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
				new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval );
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
					if( calendar_start ) { if( new_interval_start.isBefore( calendar_start ) ) { new_interval_start = calendar_start.clone(); } }
					new_interval_end = moment.utc( current_interval_start.clone().subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' );
				}

				// If the desired interval ends after current interval of events, loads next bunch of events
				else if( desired_interval_end.isAfter( current_interval_end ) 
					  || day_before_desired_interval_start.format( 'YYYY-MM-DD' ) + ' 23:59:59' === current_interval_end.format( 'YYYY-MM-DD HH:mm:ss' )
					 ) {
					new_interval_end.add( event_load_interval, 'days' );
					if( desired_interval_end.isAfter( new_interval_end ) ) {
						new_interval_end = desired_interval_end.clone();
					}
					if( calendar_end ) { if( new_interval_end.isAfter( calendar_end ) ) { new_interval_end = calendar_end.clone(); } }
					new_interval_start = moment.utc( current_interval_end.clone().add( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' );
				}

				new_interval = {
					'start': new_interval_start.locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
					'end': new_interval_end.locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' )
				};
			}
		}
	}
	
	return new_interval;
}


/**
 * Get the first events interval
 * @version 1.15.6
 * @param {HTMLElement} booking_system
 * @param {Object} min_interval
 * @returns {Object}
 */
function bookacti_get_new_interval_of_events( booking_system, min_interval ) {
	var booking_system_id = booking_system.attr( 'id' );
	var availability_period = bookacti_get_availability_period( booking_system );
	
	if( typeof availability_period.start === 'undefined' || typeof availability_period.end === 'undefined' ) { return {}; }
	
	var past_events  = bookacti.booking_system[ booking_system_id ][ 'past_events' ];
	var current_time = moment.utc( bookacti_localized.current_time );
	var current_date = current_time.locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	var calendar_start = availability_period.start ? moment.utc( availability_period.start ) : false;
	var calendar_end   = availability_period.end ? moment.utc( availability_period.end ) : false;
	
	if( ! past_events && calendar_end ) { if( calendar_end.isBefore( current_time ) ) { return []; } }
	
	if( $j.isEmptyObject( min_interval ) ) {
		if( calendar_start ) {
			if( calendar_start.isAfter( current_time ) ) {
				min_interval = { 'start': availability_period.start, 'end': availability_period.start };
			}
		}
		if( calendar_end && $j.isEmptyObject( min_interval ) ) {
			if( calendar_end.isBefore( current_time ) ) {
				min_interval = { 'start': availability_period.end, 'end': availability_period.end };
			}
		}
		if( $j.isEmptyObject( min_interval ) ) {
			min_interval = { 'start': current_date, 'end': current_date };
		}
	}
	
	var interval_duration = parseInt( bookacti_localized.event_load_interval );
	var interval_start = moment.utc( moment.utc( min_interval.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' );
	var interval_end   = moment.utc( moment.utc( min_interval.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' );
	var min_interval_duration = parseInt( Math.abs( moment.utc( min_interval.end ).diff( min_interval.start, 'days' ) ) );
	
	if( min_interval_duration > interval_duration ) { interval_duration = min_interval_duration; }
	
	var half_interval = Math.round( ( interval_duration - min_interval_duration ) / 2 );
	var interval_end_days_to_add = half_interval;
	
	// Compute Interval start
	if( past_events ) {
		interval_start.subtract( half_interval, 'days' );
		if( calendar_start ) {
			if( calendar_start.isAfter( interval_start ) ) {
				interval_end_days_to_add += Math.abs( interval_start.diff( calendar_start, 'days' ) );
			}
		}
	} else {
		interval_end_days_to_add += half_interval;
	}
	if( calendar_start ) { if( calendar_start.isAfter( interval_start ) ) { interval_start = calendar_start.clone(); } }
	
	// Compute interval end
	interval_end.add( interval_end_days_to_add, 'days' );
	if( calendar_end ) { if( calendar_end.isBefore( interval_end ) ) { interval_end = calendar_end.clone(); } }

	var new_interval = {
		'start': interval_start.locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ), 
		'end':   interval_end.locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) 
	};

	return new_interval;
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
			'start': moment.utc( interval.start ).isBefore( moment.utc( old_interval.start ) ) ? interval.start : old_interval.start,
			'end':   moment.utc( interval.end ).isAfter( moment.utc( old_interval.end ) ) ? interval.end : old_interval.end
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
	return { 'start': booking_system_data.start, 'end': booking_system_data.end };
}


/**
 * Refresh booking numbers
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_refresh_booking_numbers( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var template_ids      = bookacti.booking_system[ booking_system_id ][ 'calendars' ];
	var groups_data       = bookacti.booking_system[ booking_system_id ][ 'groups_data' ];
	var groups_events     = bookacti.booking_system[ booking_system_id ][ 'groups_events' ];
	
	bookacti_start_loading_booking_system( booking_system );

    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiGetBookingNumbers', 
			'template_ids': template_ids,
			'groups_data': groups_data,
			'groups_events': groups_events
		},
        dataType: 'json',
        success: function( response ) {
			bookacti.booking_system[ booking_system_id ][ 'bookings' ] = [];
			if( typeof response[ 'bookings' ] !== 'undefined' ) {
				bookacti.booking_system[ booking_system_id ][ 'bookings' ] = response[ 'bookings' ];
				bookacti.booking_system[ booking_system_id ][ 'groups_bookings' ] = response[ 'groups_bookings' ];
			}

			bookacti_booking_method_rerender_events( booking_system );
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() {
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}


/**
 * An event is clicked
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 */
function bookacti_event_click( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var multiple_bookings = bookacti.booking_system[ booking_system_id ][ 'multiple_bookings' ];
	
	// If the event is picked, just unpick it (or its group)
	if( multiple_bookings ) {
		var unpicked_nb = bookacti_unpick_events( booking_system, event );
		if( unpicked_nb ) { return; }
	}
	
	// Get the group id of an event or open a dialog to choose a group
	var groups = {};
	var group_ids = [];
	var groups_nb = 0;
	if( booking_system_id !== 'bookacti-booking-system-reschedule' ) {
		groups = bookacti_get_event_groups( booking_system, event );
		groups_nb = bookacti_get_event_groups_nb( groups );
		group_ids = Object.keys( groups );
	}
	
	// Open a dialog to choose the group of events 
	// if there are several groups, or if users can choose between the single event and at least one group
	var open_dialog = false;
	if( groups_nb > 1 || ( groups_nb === 1 && bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) ) {
		open_dialog = true;
		bookacti_dialog_choose_group_of_events( booking_system, groups, event );
	} else {
		// Pick events (single or whole group)
		var group_id = group_ids.length ? group_ids[ 0 ] : 0;
		var group_dates = group_id ? Object.keys( groups[ group_id ] ) : [];
		var group_date = group_dates.length ? group_dates[ 0 ] : '';
		
		if( ! multiple_bookings ) { bookacti_unpick_all_events( booking_system ); }
		
		bookacti_pick_events( booking_system, event, group_id, group_date );

		if( group_id ) {
			booking_system.trigger( 'bookacti_group_of_events_chosen', [ group_id, group_date, event ] );
		}
	}
	
	booking_system.trigger( 'bookacti_event_click', [ event, groups, open_dialog ] );
}


/**
 * Get the groups of an event
 * @since 1.12.0 (was bookacti_get_event_group_ids)
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {Object}
 */
function bookacti_get_event_groups( booking_system, event ) {
	var groups = {};
	
	// Sanitize event
	if( typeof event !== 'object' ) { return groups; } 
	else if( typeof event.id === 'undefined' || typeof event.start === 'undefined' || typeof event.end === 'undefined' ) { return groups; }
	
	var booking_system_id = booking_system.attr( 'id' );
	var event_id    = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end   = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ], function( group_id, group_occurrences ) {
		$j.each( group_occurrences, function( group_date, group_events ) {
			$j.each( group_events, function( i, group_event ) {
				if( parseInt( group_event[ 'id' ] ) === event_id
				&&  group_event[ 'start' ] === event_start
				&&  group_event[ 'end' ] === event_end ) {
					if( typeof groups[ group_id ] === 'undefined' ) { groups[ group_id ] = {}; }
					groups[ group_id ][ group_date ] = group_events;
					return false; // Break the loop
				}
			});
		});
	});
	
	return groups;
}


/**
 * Get the number of groups of an event
 * @since 1.12.0
 * @param {Object} groups See bookacti_get_event_groups
 * @returns {Int}
 */
function bookacti_get_event_groups_nb( groups ) {
	var groups_nb = 0;
	$j.each( groups, function( group_id, groups_per_dates ) {
		$j.each( groups_per_dates, function( group_date, group_events ) {
			++groups_nb;
		});
	});
	return groups_nb;
}


/**
 * Fill form fields
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_fill_booking_system_fields( booking_system ) {
	var inputs_container = booking_system.siblings( '.bookacti-booking-system-inputs' );
	if( ! inputs_container.length ) { return; }
	
	// Remove the current inputs
	inputs_container.find( 'input[name^="selected_events"]' ).remove();
	
	var i = 0;
	var booking_system_id = booking_system.attr( 'id' );
	
	// Add an hidden input for each selected events
	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( j, picked_event ) {
		inputs_container.append( '<input type="hidden" name="selected_events[' + i + '][group_id]" value="' + picked_event.group_id + '"/>' );
		inputs_container.append( '<input type="hidden" name="selected_events[' + i + '][group_date]" value="' + picked_event.group_date + '"/>' );
		inputs_container.append( '<input type="hidden" name="selected_events[' + i + '][id]" value="' + picked_event.id + '"/>' );
		inputs_container.append( '<input type="hidden" name="selected_events[' + i + '][start]" value="' + picked_event.start + '"/>' );
		inputs_container.append( '<input type="hidden" name="selected_events[' + i + '][end]" value="' + picked_event.end + '"/>' );
		++i;
		
		// Backward compatibility
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( picked_event.group_id ? picked_event.group_id : 0 );
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( picked_event.id );
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( picked_event.start );
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( picked_event.end );
	});
	
	booking_system.trigger( 'bookacti_fill_booking_system_fields' );
}


/**
 * Pick a single event or a all events of a group
 * @since 1.12.0 (was bookacti_pick_events_of_group)
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object|Int)} event
 * @param {Int} group_id
 * @param {String} group_date
 * @returns {Int}
 */
function bookacti_pick_events( booking_system, event, group_id, group_date ) {
	if( $j.isNumeric( event ) ) { event = { 'id': parseInt( event ), 'start': '', 'end': '' }; }
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : ( typeof event.id !== 'undefined' ? parseInt( event.id ) : 0 );
	
	var picked_nb = 0;
	if( ! group_id && ! event_id ) { return picked_nb; }
	
	// Pick a single event or the whole group
	if( ! group_id ) {
		picked_nb = bookacti_pick_event( booking_system, event );
	} 
	// Pick the events of the group
	else {
		var booking_system_id = booking_system.attr( 'id' );
		if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ][ group_date ] !== 'undefined' ) {
				var group_events = bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ][ group_date ].slice();
				$j.each( group_events, function( i, group_event ){
					group_picked_nb = bookacti_pick_event( booking_system, group_event, group_id, group_date );
					picked_nb += group_picked_nb;
				});
			}
		}
	}
	
	booking_system.trigger( 'bookacti_events_picked', [ event, group_id, group_date ] );
	
	return picked_nb;
}


/**
 * Check if an event is picked
 * @since 1.9.0
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object|Int)} event
 * @returns {Object|False}
 */
function bookacti_is_event_picked( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var picked_events = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ][ 'picked_events' ] );
	var event_start_date = typeof event.start !== 'undefined' ? moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : '';
	var event_id = $j.isNumeric( event ) ? parseInt( event ) : ( typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id ) );
	
	var is_picked = false;
	if( picked_events ) {
		$j.each( picked_events, function( i, picked_event ) {
			if( picked_event.id == event_id 
			&&  (  $j.isNumeric( event ) 
				|| picked_event.start.substr( 0, 10 ) === event_start_date ) ) {
				is_picked = picked_event;
				return false; // break
			}
		});
	}
	
	return is_picked;
}


/**
 * Pick an event
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object|Int)} event
 * @param {Int} group_id
 * @param {String} group_date
 * @returns {Int}
 */
function bookacti_pick_event( booking_system, event, group_id, group_date ) {
	if( $j.isNumeric( event ) ) { event = { 'id': parseInt( event ), 'start': '', 'end': '' }; };
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : ( typeof event.id !== 'undefined' ? parseInt( event.id ) : 0 );
	
	var picked_nb = 0;
	if( ! group_id && ! event_id ) { return picked_nb; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Find activity ID
	var title       = typeof event.title !== 'undefined' ? event.title : '';
	var activity_id = typeof event.activity_id !== 'undefined' ? parseInt( event.activity_id ) : 0;
	if( ! activity_id ) {
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ] !== 'undefined' ) {
			title = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'title' ];
			activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'activity_id' ];
		}
	}
	
	// Format event object
	var event_start  = typeof event.start !== 'undefined' ? event.start : '';
	var event_end    = typeof event.end !== 'undefined' ? event.end : '';
	var picked_event = {
		'group_id':    group_id,
		'group_date':  group_date ? moment.utc( group_date ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : '',
		'id':          event_id,
		'start':       event_start ? moment.utc( event_start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) : '',
		'end':         event_end ? moment.utc( event_end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) : '',
		'title':       title,
		'activity_id': parseInt( activity_id )
	};
	
	// Keep picked events in memory 
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ].push( picked_event );
	++picked_nb;
	
	// Sort the picked events
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ].sort( function ( a, b ) {
		var x = moment.utc( a[ 'start' ] ); var y = moment.utc( b[ 'start' ] );
		return ( ( x.isBefore( y ) ) ? -1 : ( ( x.isAfter( y ) ) ? 1 : 0 ) );
	});
	
	booking_system.trigger( 'bookacti_pick_event', [ event, group_id, group_date ] );
	
	return picked_nb;
}


/**
 * Unpick a specific event or all events of a group
 * @since 1.12.0 (was bookacti_unpick_events_of_group and bookacti_unpick_event)
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object|Int)} event
 * @param {Int} group_id
 * @param {String} group_date
 * @returns {Int}
 */
function bookacti_unpick_events( booking_system, event, group_id, group_date ) {
	if( $j.isNumeric( event ) ) { event = { 'id': parseInt( event ), 'start': '', 'end': '' }; };
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : ( typeof event.id !== 'undefined' ? parseInt( event.id ) : 0 );
	
	var unpicked_nb = 0;
	if( ! group_id && ! event_id ) { return unpicked_nb; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Format event object
	var event_start = typeof event.start !== 'undefined' ? event.start : '';
	var event_to_unpick = {
		'id': event_id,
		'start': event_start ? moment.utc( event_start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) : '',
		'group_id': group_id,
		'group_date': group_date ? moment.utc( group_date ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : ''
	};
	
	// An event can be in multiple groups
	var groups_to_unpick = [];
	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
		if( ! picked_event.group_id ) { return true; } // continue;
		
		var group_identifier= picked_event.group_id + '_' + picked_event.group_date;
		var same_group_id	= event_to_unpick.group_id && picked_event.group_id == event_to_unpick.group_id ? 1 : 0;
		var same_group_date	= event_to_unpick.group_date && picked_event.group_date === event_to_unpick.group_date ? 1 : 0;
		var same_event_id	= event_to_unpick.id && picked_event.id == event_to_unpick.id ? 1 : 0;
		var same_event_date	= event_to_unpick.start && picked_event.start.substr( 0, 10 ) === event_to_unpick.start.substr( 0, 10 ) ? 1 : 0;
		
		if( ! event_to_unpick.id && same_group_id ) {
			if( ! event_to_unpick.group_date || same_group_date ) { groups_to_unpick.push( group_identifier ); }
		}
		if( ! event_to_unpick.group_id && same_event_id ) {
			if( ! event_to_unpick.start || same_event_date ) { groups_to_unpick.push( group_identifier ); }
		}
		if( same_event_id && same_group_id ) {
			if( ( ! event_to_unpick.group_date || same_group_date ) && ( ! event_to_unpick.start || same_event_date ) ) { groups_to_unpick.push( group_identifier ); }
		}
	});
	
	// Remove picked event(s) from memory 
	var picked_events = $j.grep( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( picked_event ) {
		var group_identifier= picked_event.group_id + '_' + picked_event.group_date;
		var same_event_id	= event_to_unpick.id && picked_event.id == event_to_unpick.id ? 1 : 0;
		var same_event_date	= event_to_unpick.start && picked_event.start.substr( 0, 10 ) === event_to_unpick.start.substr( 0, 10 ) ? 1 : 0;
		
		// Unpick all events of the groups
		if( $j.inArray( group_identifier, groups_to_unpick ) >= 0
		
		// Unpick a specific event
		|| ( same_event_id && ! event_to_unpick.start )
		|| ( same_event_id && same_event_date ) ) { ++unpicked_nb; return false; }
		
		// Else, keep the event from the picked_events array
		return true;
	});
	
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = picked_events;
	
	booking_system.trigger( 'bookacti_events_unpicked', [ event, group_id, group_date ] );
	
	return unpicked_nb;
}


/**
 * Reset picked events
 * @param {HTMLElement} booking_system
 */
function bookacti_unpick_all_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = [];
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name^="selected_events"]' ).remove();
	
	// Backward compatibility
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input' ).val( '' );
	
	// Remove picked events list
	booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' ).empty();
	booking_system.siblings( '.bookacti-picked-events' ).hide();
	
	booking_system.trigger( 'bookacti_unpick_all_events' );
}


/**
 * Get the picked events list items
 * @since 1.12.4
 * @version 1.15.5
 * @param {HTMLElement} booking_system
 * @returns {Object}
 */
function bookacti_get_picked_events_list_items( booking_system ) {
	var list_items = {};
	var booking_system_id = booking_system.attr( 'id' );
	
	// Get quantity
	var form = booking_system.closest( 'form' ).length ? booking_system.closest( 'form' ) : booking_system.closest( '.bookacti-form-fields' );
	qty_field = form.find( 'input[name="quantity"]' );
	var quantity = qty_field.length ? parseInt( qty_field.val() ) : 1;
	
	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
		var activity_id = 0;
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ] !== 'undefined' && parseInt( picked_event.id ) > 0 ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ picked_event.id ] !== 'undefined' ) {
				activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ picked_event.id ][ 'activity_id' ];
			}
		}
		var category_id = 0;
		if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ] !== 'undefined' && parseInt( picked_event.group_id ) > 0 ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ picked_event.group_id ] !== 'undefined' ) {
				category_id = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ picked_event.group_id ][ 'category_id' ];
			}
		}
		
		var list_item_data = {
			'id':               picked_event.id,
			'title':            picked_event.title,
			'group_id':         picked_event.group_id,
			'group_date':       picked_event.group_date,
			'start':            moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
			'end':              moment.utc( picked_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
			'activity_id':      parseInt( activity_id ),
			'category_id':      parseInt( category_id ),
			'quantity':         quantity,
			'price':            0.00,
			'price_to_display': '',
			'has_price':        false
		};
		
		booking_system.trigger( 'bookacti_picked_events_list_item_data', [ list_item_data, picked_event ] );
		
		var list_item_id = parseInt( list_item_data.group_id ) > 0 ? 'group_' + list_item_data.group_id + '_' + list_item_data.group_date : 'event_' + list_item_data.id + '_' + list_item_data.start + '_' + list_item_data.end;
		
		var list_element = $j( '<li></li>', {
			'html': '<span class="bookacti-booking-event-title" >' + list_item_data.title + '</span>'
		});
		
		var event_duration = bookacti_format_event_duration( list_item_data.start, list_item_data.end );
		if( event_duration ) {
			list_element.append( '<span class="bookacti-booking-event-title-separator" > - </span>' + event_duration );
		}
		
		if( list_item_data.quantity > 0 ) {
			var activity_unit = bookacti_get_activity_unit( booking_system, list_item_data.activity_id, list_item_data.quantity );
			if( activity_unit ) {
				list_element.append( '<span class="bookacti-booking-event-quantity-separator" > - </span><span class="bookacti-booking-event-quantity" >' + list_item_data.quantity + ' ' + activity_unit + '</span>' );
			}
		}
		
		list_element.data( 'event-id', list_item_data.id ).attr( 'data-event-id', list_item_data.id );
		list_element.data( 'event-start', list_item_data.start ).attr( 'data-event-start', list_item_data.start );
		list_element.data( 'event-end', list_item_data.end ).attr( 'data-event-end', list_item_data.end );
		
		// Add grouped event to the list
		if( parseInt( list_item_data.group_id ) > 0 ) {
			// Add the grouped events list
			if( typeof list_items[ list_item_id ] === 'undefined' ) {
				// Get the group title
				var group_title = '';
				if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ list_item_data.group_id ] !== 'undefined' ) {
					var group = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ list_item_data.group_id ];
					group_title = '<span class="bookacti-picked-group-of-events-title">' + group.title + '</span>';
				}
				
				var group_list_element = $j( '<li></li>', { 'html': group_title + '<ul class="bookacti-picked-group-of-events-list">' + list_element[0].outerHTML + '</ul>' } );
				group_list_element.data( 'group-id', list_item_data.group_id ).attr( 'data-group-id', list_item_data.group_id );
				group_list_element.data( 'group-date', list_item_data.group_date ).attr( 'data-group-date', list_item_data.group_date );
				
				list_items[ list_item_id ] = $j.extend( true, {}, list_item_data );
				list_items[ list_item_id ].list_element = group_list_element;
			} 
			// The grouped events list was already added
			else {
				list_items[ list_item_id ][ 'end' ] = list_item_data.end;
				list_items[ list_item_id ].list_element.find( 'ul' ).append( list_element );
			}
		}
		
		// Add a single event to the list
		else {
			list_items[ list_item_id ] = $j.extend( true, {}, list_item_data );
			list_items[ list_item_id ].list_element = list_element;
		}
	});
	
	booking_system.trigger( 'bookacti_picked_events_list_items', [ list_items ] );
	
	return list_items;
}


/**
 * Display a list of picked events
 * @version 1.13.0
 * @param {HTMLElement} booking_system
 */
function bookacti_fill_picked_events_list( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var event_list_title  = booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list-title' );
	var event_list        = booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' );
	
	event_list.empty();
	booking_system.siblings( '.bookacti-picked-events' ).hide();
	
	if( typeof bookacti.booking_system[ booking_system_id ][ 'picked_events' ] === 'undefined' ) { return; }
	if( ! bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length ) { return; }
	
	var multiple_bookings = bookacti.booking_system[ booking_system_id ][ 'multiple_bookings' ];
	
	// Fill title with singular or plural
	var title = bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length === 1 ? bookacti_localized.selected_event : bookacti_localized.selected_events;
	event_list_title.html( title );
	
	var list_items = bookacti_get_picked_events_list_items( booking_system );
	
	$j.each( list_items, function( i, list_item_data ) {
		var list_element = list_item_data.list_element;
		if( multiple_bookings ) {
			if( list_element.find( '.bookacti-picked-group-of-events-title' ).length ) {
				list_element.find( '.bookacti-picked-group-of-events-title' ).after( '<span class="bookacti-unpick-event-icon"></span>' );
			} else {
				list_element.append( '<span class="bookacti-unpick-event-icon"></span>' );
			}
		}
		event_list.append( list_element );
	});
	
	if( ! event_list.is( ':empty' ) ) { booking_system.siblings( '.bookacti-picked-events' ).show(); }
	
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
 * Get min and max quantity according to the selected events
 * @since 1.9.0
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @returns {Object}
 */
function bookacti_get_min_and_max_quantity( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	
	var group_uids = [];
	var highest_min_quantity    = 1;
	var lowest_max_quantity     = 999999999;
	var lowest_available_places = 999999999;
	
	var available_places, quantity_booked, min_quantity, max_quantity;
	$j.each( attributes[ 'picked_events' ], function( j, picked_event ) {
		available_places = 0;
		quantity_booked  = 0;
		min_quantity     = 1;
		max_quantity     = false;
		
		// Groups of events
		if( parseInt( picked_event.group_id ) > 0 ) {
			// Skip the group if it was already processed
			var group_uid = picked_event.group_id + '' + picked_event.group_date;
			if( $j.inArray( group_uid, group_uids ) > -1 ) { return true; }
			group_uids.push( group_uid );
			
			if( typeof attributes[ 'groups_data' ] !== 'undefined' ) {
				if( typeof attributes[ 'groups_data' ][ picked_event.group_id ] !== 'undefined' ) {
					var category_id = parseInt( attributes[ 'groups_data' ][ picked_event.group_id ][ 'category_id' ] );
					if( typeof attributes[ 'group_categories_data' ] !== 'undefined' ) {
						if( typeof attributes[ 'group_categories_data' ][ category_id ] !== 'undefined' ) {
							if( typeof attributes[ 'group_categories_data' ][ category_id ][ 'settings' ] !== 'undefined' ) {
								var category_data = attributes[ 'group_categories_data' ][ category_id ][ 'settings' ];
								min_quantity = typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 1 );
								max_quantity = typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : false );
							}
						}
					}
				}
			}
			
			if( picked_event.group_date ) {
				if( typeof attributes[ 'groups_bookings' ][ picked_event.group_id ] !== 'undefined' ) {
					if( typeof attributes[ 'groups_bookings' ][ picked_event.group_id ][ picked_event.group_date ] !== 'undefined' ) {
						available_places = parseInt( attributes[ 'groups_bookings' ][ picked_event.group_id ][ picked_event.group_date ][ 'availability' ] );
						if( min_quantity || max_quantity ) {
							quantity_booked = parseInt( attributes[ 'groups_bookings' ][ picked_event.group_id ][ picked_event.group_date ][ 'current_user_bookings' ] );
						}
					}
				}
			}

		// Single events
		} else {
			if( typeof attributes[ 'events_data' ] !== 'undefined' ) {
				if( typeof attributes[ 'events_data' ][ picked_event.id ] !== 'undefined' ) {
					
					available_places = bookacti_get_event_availability( booking_system, picked_event );
					var activity_id = parseInt( attributes[ 'events_data' ][ picked_event.id ][ 'activity_id' ] );
					
					if( typeof attributes[ 'activities_data' ] !== 'undefined' ) {
						if( typeof attributes[ 'activities_data' ][ activity_id ] !== 'undefined' ) {
							if( typeof attributes[ 'activities_data' ][ activity_id ][ 'settings' ] !== 'undefined' ) {
								var activity_data = attributes[ 'activities_data' ][ activity_id ][ 'settings' ];
								min_quantity = typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 1 );
								max_quantity = typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : false );
								
								if( min_quantity || max_quantity ) {
									var event_start_formatted = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
									if( typeof attributes[ 'bookings' ][ picked_event.id ] !== 'undefined' ) {
										if( typeof attributes[ 'bookings' ][ picked_event.id ][ event_start_formatted ] !== 'undefined' ) {
											var occurrence  = attributes[ 'bookings' ][ picked_event.id ][ event_start_formatted ];
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
				
		max_quantity = max_quantity && max_quantity != 0 && ( max_quantity - quantity_booked ) < available_places ? Math.max( ( max_quantity - quantity_booked ), 0 ) : available_places;
		min_quantity = min_quantity && min_quantity != 0 && min_quantity > 1 && quantity_booked < min_quantity ? Math.max( ( min_quantity - quantity_booked ), 0 ) : 1;
		
		if( min_quantity > highest_min_quantity )                { highest_min_quantity = min_quantity; }
		if( max_quantity && max_quantity < lowest_max_quantity ) { lowest_max_quantity = max_quantity; }
		if( available_places < lowest_available_places )         { lowest_available_places = available_places; }
	});
	
	var qty_data = {
		'avail': lowest_available_places,
		'min': highest_min_quantity,
		'max': lowest_max_quantity
	};
	
	booking_system.trigger( 'bookacti_min_and_max_quantity', [ qty_data ] );
	
	return qty_data;
}


/**
 * Set min and max quantity on the quantity field
 * @version 1.12.4
 * @param {HTMLElement} booking_system
 */
function bookacti_set_min_and_max_quantity( booking_system ) {
	var qty_data = bookacti_get_min_and_max_quantity( booking_system );
	var form = booking_system.closest( 'form' ).length ? booking_system.closest( 'form' ) : booking_system.closest( '.bookacti-form-fields' );
	qty_data.field = form.find( 'input[name="quantity"]' );
	
	booking_system.trigger( 'bookacti_update_quantity', [ qty_data ] );
	
	if( ! qty_data.field.length ) { return qty_data; }
	
	var old_quantity = parseInt( qty_data.field.val() );
	qty_data.value = old_quantity;
	
	// Limit the max quantity
	if( old_quantity > qty_data.max ) {
		qty_data.value = qty_data.max;
	}
	
	// Force a min quantity
	if( old_quantity < qty_data.min ) {
		// If min required bookings is higher than available places, 
		// keep the higher amount to feedback that there are not enough places
		if( qty_data.min > qty_data.avail ) { qty_data.max = qty_data.min; }
		qty_data.value = qty_data.min;
	}
	
	// Reset quantity field min and max attributes
	qty_data.field.attr( 'min', 1 );
	qty_data.field.removeAttr( 'max' );
	
	var booking_system_id = booking_system.attr( 'id' );
	if( ! bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length ) { return qty_data; }
	
	// Set min, max and value attributes
	qty_data.field.attr( 'max', qty_data.max );
	qty_data.field.attr( 'min', qty_data.min );
	qty_data.field.val( qty_data.value );
	
	if( old_quantity !== parseInt( qty_data.value ) ) {
		qty_data.field.trigger( 'bookacti_quantity_updated', [ old_quantity, qty_data ] );
	}
	
	return qty_data;
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
	end   = moment.utc( end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
	var event_start = moment.utc( start ).clone().locale( bookacti_localized.current_lang_code );
	var event_end   = moment.utc( end ).clone().locale( bookacti_localized.current_lang_code );
	
	var start_and_end_same_day = start.substr( 0, 10 ) === end.substr( 0, 10 );
	var class_same_day         = start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
	var event_end_formatted    = start_and_end_same_day ? event_end.formatPHP( bookacti_localized.time_format ) : event_end.formatPHP( bookacti_localized.date_format );
	var separator              = start_and_end_same_day ? bookacti_localized.date_time_separator : bookacti_localized.dates_separator;
	
	var event_duration = '<span class="bookacti-booking-event-start">' + event_start.formatPHP( bookacti_localized.date_format ) + '</span>' 
	                   + '<span class="bookacti-booking-event-date-separator ' + class_same_day + '">' + separator +  '</span>' 
	                   + '<span class="bookacti-booking-event-end ' + class_same_day + '">' + event_end_formatted + '</span>';
	
	return event_duration;
}


/**
 * Get activity unit value
 * @version 1.12.6
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
	
	var activity_unit = qty === 1 ? activity_data[ 'settings' ][ 'unit_name_singular' ] : activity_data[ 'settings' ][ 'unit_name_plural' ];
	
	// Display people per booking
	if( typeof activity_data[ 'settings' ][ 'places_number' ] === 'undefined' ) { return activity_unit; }
	if( activity_data[ 'settings' ][ 'places_number' ] === '' 
	||  parseInt( activity_data[ 'settings' ][ 'places_number' ] ) === 0 ) { return activity_unit; }
	
	activity_unit += parseInt( activity_data[ 'settings' ][ 'places_number' ] ) === 1 ? ' ' + bookacti_localized.one_person_per_booking : ' ' + bookacti_localized.n_people_per_booking.replace( '%1$s', activity_data[ 'settings' ][ 'places_number' ] );
	
	return activity_unit;
}


/**
 * Clear booking system displayed info
 * @version 1.9.0
 * @param {HTMLElement} booking_system
 * @param {boolean} keep_picked_events
 */
function bookacti_clear_booking_system_displayed_info( booking_system, keep_picked_events ) {
	keep_picked_events = keep_picked_events || false;
	
	// Empty the picked events info
	if( ! keep_picked_events ) { bookacti_unpick_all_events( booking_system ); }
	
	// Clear errors
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
	booking_system.show();
	
	booking_system.trigger( 'bookacti_displayed_info_cleared' );
}


/**
 * Get event booking numbers
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {Int}
 */
function bookacti_get_event_number_of_bookings( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes  = bookacti.booking_system[ booking_system_id ];
	var event_id    = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var event_start	= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var bookings_nb = 0;
	
	if( typeof attributes[ 'bookings' ] !== 'undefined' ) {
		if( typeof attributes[ 'bookings' ][ event_id ] !== 'undefined' ) {
			if( typeof attributes[ 'bookings' ][ event_id ][ event_start ] !== 'undefined' ) {
				if( typeof attributes[ 'bookings' ][ event_id ][ event_start ][ 'quantity' ] !== 'undefined' ) { 
					if( $j.isNumeric( attributes[ 'bookings' ][ event_id ][ event_start ][ 'quantity' ] ) ) {
						bookings_nb = parseInt( attributes[ 'bookings' ][ event_id ][ event_start ][ 'quantity' ] );
					}
				}
			}
		}
	}
	
	return bookings_nb;
}


/**
 * Get event available places
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {Int}
 */
function bookacti_get_event_availability( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var event_availability = 0;
	if( typeof event.availability !== 'undefined' ) { event_availability = event.availability; }
	if( typeof attributes[ 'events_data' ][ event_id ] !== 'undefined' ) { 
		if( typeof attributes[ 'events_data' ][ event_id ][ 'availability' ] !== 'undefined' ) { 
			if( $j.isNumeric( attributes[ 'events_data' ][ event_id ][ 'availability' ] ) ) { 
				event_availability = parseInt( attributes[ 'events_data' ][ event_id ][ 'availability' ] );
			}
		}
	}
	
	var event_bookings = bookacti_get_event_number_of_bookings( booking_system, event );
	
	return event_availability - event_bookings;
}


/**
 * Check if an event is event available
 * @version 1.15.6
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {Boolean}
 */
function bookacti_is_event_available( booking_system, event ) {
	var booking_system_id    = booking_system.attr( 'id' );
	var attributes           = bookacti.booking_system[ booking_system_id ];
	var past_events          = attributes[ 'past_events' ];
	var past_events_bookable = attributes[ 'past_events_bookable' ];
	
	var event_id     = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var event_start  = moment.utc( event.start ).clone().locale( 'en' );
	var event_end    = moment.utc( event.end ).clone().locale( 'en' );
	var current_time = moment.utc( bookacti_localized.current_time );
	
	var availability        = bookacti_get_event_availability( booking_system, event );
	var availability_period = bookacti_get_availability_period( booking_system );
	var is_available        = false;
	
	if( availability <= 0 ) { return false; }
	
	// Check if the event is part of a group
	var groups               = bookacti_get_event_groups( booking_system, event );
	var groups_nb            = bookacti_get_event_groups_nb( groups );
	var is_in_group          = groups_nb > 0;
	var groups_single_events = attributes[ 'groups_single_events' ];
	
	// On the reschedule calendar
	if( booking_system_id === 'bookacti-booking-system-reschedule' ) {
		// If the event is part of a group (and not bookable alone), it cannot be available
		if( is_in_group && ! groups_single_events ) { return false; }
		
		var rescheduled_booking_data = typeof attributes[ 'rescheduled_booking_data' ] !== 'undefined' ? attributes[ 'rescheduled_booking_data' ] : [];
		
		// Don't display self event
		if( typeof rescheduled_booking_data.event_id !== 'undefined'
		&&  typeof rescheduled_booking_data.event_start !== 'undefined'
		&&  typeof rescheduled_booking_data.event_end !== 'undefined' ) {
			if( rescheduled_booking_data.event_id == event_id
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
	&&  typeof attributes[ 'events_data' ][ event_id ] !== 'undefined' ) {
		// Check if the event is past or out of the availability period
		var is_past = false;
		if( past_events ) {
			// Check if the event is past
			if( ! past_events_bookable && event_start.isBefore( current_time ) 
			&& ! ( bookacti_localized.started_events_bookable && event_end.isAfter( current_time ) ) ) {
				is_past = true;
			}
			
			// Check if the event is in the availability period
			if( ! past_events_bookable ) {
				if( availability_period.start ) { if( event_start.isBefore( moment.utc( availability_period.start ) ) ) { is_past = true; } }
				if( availability_period.end )   { if( event_end.isAfter( moment.utc( availability_period.end ) ) )      { is_past = true; } }
			}
		}
		
		if( ! is_past ) {
			// Check the min required quantity
			var activity_id   = parseInt( attributes[ 'events_data' ][ event_id ][ 'activity_id' ] );
			var activity_data = attributes[ 'activities_data' ][ activity_id ][ 'settings' ];

			// Check the max quantity allowed AND
			// Check the max number of different users allowed
			var min_qty_ok = max_qty_ok = max_users_ok = true;
			event_start_formatted = event_start.format( 'YYYY-MM-DD HH:mm:ss' );
			if( typeof attributes[ 'bookings' ][ event_id ] !== 'undefined' ) {
				if( typeof attributes[ 'bookings' ][ event_id ][ event_start_formatted ] !== 'undefined' ) {
					var min_quantity = typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 0 );
					var max_quantity = typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : 0 );
					var max_users    = typeof activity_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( activity_data[ 'max_users_per_event' ] ? parseInt( activity_data[ 'max_users_per_event' ] ) : 0 );

					if( min_quantity || max_quantity || max_users ) {
						var occurrence = attributes[ 'bookings' ][ event_id ][ event_start_formatted ];
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
		$j.each( groups, function( group_id, groups_per_date ) {
			var group         = attributes[ 'groups_data' ][ group_id ];
			var category_id   = parseInt( group[ 'category_id' ] );
			var category_data = attributes[ 'group_categories_data' ][ category_id ][ 'settings' ];
			var started_groups_bookable	= parseInt( bookacti_localized.started_groups_bookable );
			if( typeof category_data[ 'started_groups_bookable' ] !== 'undefined' ) {
				if( $j.inArray( category_data[ 'started_groups_bookable' ], [ 0, 1, '0', '1', true, false ] ) >= 0 ) {
					started_groups_bookable	= $j.isNumeric( category_data[ 'started_groups_bookable' ] ) ? parseInt( category_data[ 'started_groups_bookable' ] ) : ( category_data[ 'started_groups_bookable' ] ? 1 : 0 );
				}
			}
			
			$j.each( groups_per_date, function( group_date, group_events ) {
				// Check if the group is past
				var group_start	= moment.utc( group_events[ 0 ].start ).clone();
				var group_end	= moment.utc( group_events[ group_events.length - 1 ].end ).clone();
				if( ! past_events_bookable && group_start.isBefore( current_time ) 
				&& ! ( started_groups_bookable && group_end.isAfter( current_time ) ) ) {
					return true; // Skip this group
				}

				// Check if the group of events is in the availability period
				if( ! past_events_bookable ) {
					if( availability_period.start ) { if( group_start.isBefore( moment.utc( availability_period.start ) ) ) { return true; } } // Skip this group
					if( availability_period.end )   { if( group_start.isAfter( moment.utc( availability_period.end ) ) )    { return true; } } // Skip this group
				}
				
				// Get group availability
				var group_availability, current_user_bookings, distinct_users;
				group_availability = current_user_bookings = distinct_users = 0;
				if( typeof attributes[ 'groups_bookings' ][ group_id ] !== 'undefined' ) {
					if( typeof attributes[ 'groups_bookings' ][ group_id ][ group_date ] !== 'undefined' ) {
						group_availability    = attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'availability' ];
						current_user_bookings = attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'current_user_bookings' ];
						distinct_users        = attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'distinct_users' ];
					}
				}
				
				if( parseInt( group_availability ) > 0 ) {
					// Check the min and max quantity allowed AND
					// Check the max number of different users allowed
					var min_qty_ok = max_qty_ok = max_users_ok = true;
					if( group != null ) {
						var max_users    = typeof category_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( category_data[ 'max_users_per_event' ] ? parseInt( category_data[ 'max_users_per_event' ] ) : 0 );
						var max_quantity = typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : 0 );
						var min_quantity = typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 0 );

						if( min_quantity || max_quantity || max_users ) {
							var qty_booked = parseInt( current_user_bookings );
							if( max_users && qty_booked === 0 && parseInt( distinct_users ) >= max_users ) {
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
		});
	}
	
	return is_available;
}


/**
 * Get group available places
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @param {array} groups
 * @returns {Number}
 */
function bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, groups ) {
	groups = ! $j.isEmptyObject( groups ) ? groups : bookacti_get_event_groups( booking_system, event );
	
	var booking_system_id = booking_system.attr( 'id' );
	var attributes        = bookacti.booking_system[ booking_system_id ];
	var event_bookings_nb = bookacti_get_event_number_of_bookings( booking_system, event );
	
	var group_bookings_nb = 0;
	$j.each( groups, function( group_id, groups_per_date ) {
		$j.each( groups_per_date, function( group_date, group_events ) {
			if( typeof attributes[ 'groups_bookings' ][ group_id ] !== 'undefined' ) {
				if( typeof attributes[ 'groups_bookings' ][ group_id ][ group_date ] !== 'undefined' ) {
					group_bookings_nb += attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'quantity' ];
				}
			}
		});
	});
	
	return event_bookings_nb - group_bookings_nb;
}


/**
 * Get a div with event available places
 * @version 1.15.5
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {String}
 */
function bookacti_get_event_availability_div( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes        = bookacti.booking_system[ booking_system_id ];
	var available_places  = bookacti_get_event_availability( booking_system, event );
	
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var activity_id = 0;
	var total_availability = 0;
	if( typeof attributes[ 'events_data' ][ event_id ] !== 'undefined' ) {
		if( typeof attributes[ 'events_data' ][ event_id ][ 'activity_id' ] !== 'undefined' ) {
			activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'activity_id' ];
		}
		if( typeof attributes[ 'events_data' ][ event_id ][ 'availability' ] !== 'undefined' ) {
			total_availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'availability' ];
		}
	}
	
	var unit_name = '';
	if( activity_id ) {
		var activity_data = bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ];
		if( activity_data !== undefined ) {
			if( activity_data[ 'settings' ] !== undefined ) {
				if( typeof activity_data[ 'settings' ][ 'unit_name_plural' ] !== 'undefined'
				&&  typeof activity_data[ 'settings' ][ 'unit_name_singular' ] !== 'undefined' 
				&&  typeof activity_data[ 'settings' ][ 'show_unit_in_availability' ] !== 'undefined' ) {
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
	
	// Detect if the event is available or full, and if it is booked or not
	var availability_classes = available_places < total_availability ? 'bookacti-booked' : 'bookacti-not-booked';
	availability_classes    += available_places <= 0 ? ' bookacti-full' : '';
	
	// Maybe hide event availability
	var hide_availability_class	= '';
	var bookings_only     = bookacti.booking_system[ booking_system_id ][ 'bookings_only' ] == 1 ? true : false;
	var percent_remaining = parseInt( ( available_places / total_availability ) * 100 );
	var percent_threshold = parseInt( bookacti.booking_system[ booking_system_id ][ 'hide_availability' ] );
	var fixed_threshold   = parseInt( bookacti_localized.hide_availability_fixed );
	var hide_percent      = percent_threshold < 100 && percent_remaining > percent_threshold;
	var hide_fixed        = fixed_threshold > 0 && available_places > fixed_threshold;
	
	if( ! bookings_only 
	&& ( ( fixed_threshold <= 0 && hide_percent ) || ( percent_threshold >= 100 && hide_fixed ) || ( hide_percent && hide_fixed ) ) ) {
		available_places = '';
		hide_availability_class	= 'bookacti-hide-availability';
	}
	
	var unit_name_class = '';
	if( unit_name ) { unit_name_class = 'bookacti-has-unit-name'; }
	
	var avail_div     = $j( '<div></div>',   { 'class': 'bookacti-availability-container ' + hide_availability_class } );
	var places_span   = $j( '<div></div>',   { 'class': 'bookacti-available-places ' + availability_classes } );
	var nb_span       = $j( '<span></span>', { 'class': 'bookacti-available-places-number', 'html': available_places } );
	var unit_span     = $j( '<span></span>', { 'class': 'bookacti-available-places-unit-name ' + unit_name_class, 'html': unit_name } );
	var particle_span = $j( '<span></span>', { 'class': 'bookacti-available-places-avail-particle', 'html': avail } );

	places_span.append( nb_span );
	places_span.append( unit_span );
	places_span.append( particle_span );
	avail_div.append( places_span );
	
	return avail_div;
}


/**
 * Get a div with event booking number
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 * @returns {String}
 */
function bookacti_get_event_number_of_bookings_div( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var bookings_number   = bookacti_get_event_number_of_bookings( booking_system, event );
	var available_places  = bookacti_get_event_availability( booking_system, event );
	
	var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	var total_availability = 0;
	if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ] !== 'undefined' ) {
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'availability' ] !== 'undefined' ) {
			total_availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'availability' ];
		}
	}
	
	var availability_classes = total_availability === 0 ? 'bookacti-no-availability' : '';
	availability_classes += bookings_number > 0 ? ' bookacti-booked' : ' bookacti-not-booked';
	availability_classes += available_places <= 0 ? ' bookacti-full' : '';
	
	var avail_div   = $j( '<div></div>',   { 'class': 'bookacti-availability-container' } );
	var places_span = $j( '<div></div>',   { 'class': 'bookacti-available-places ' + availability_classes } );
	var booked_span = $j( '<span></span>', { 'class': 'bookacti-active-bookings-number', 'html': bookings_number } );

	places_span.append( booked_span );
	avail_div.append( places_span );
	
	return avail_div;
}


/**
 * Sort an array of events by dates
 * @version 1.9.0
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
		// If start date is the same, then sort by end date ASC
		if( sort_by_end || a[ labels.start ] === b[ labels.start ] ) {
			var a_date = moment.utc( a[ labels.end ] );
			var b_date = moment.utc( b[ labels.end ] );
		} 
		// Sort by start date ASC by default
		else {
			var a_date = moment.utc( a[ labels.start ] );
			var b_date = moment.utc( b[ labels.start ] );
		}
		
		var sort = 0;
		if( a_date.isAfter( b_date ) )  { sort = 1; }
		if( a_date.isBefore( b_date ) ) { sort = -1; }
		if( desc === true )             { sort = sort * -1; }
		
		return sort;
	});
	
	return array;
}


// Booking system actions based on booking method

/**
 * Load the booking system according to booking method
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {boolean} reload_events
 */
function bookacti_booking_method_set_up( booking_system, reload_events ) {
	reload_events = reload_events ? 1 : 0;
	
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	if( bookacti.booking_system[ booking_system_id ][ 'no_events' ] ) { return; }
	
	booking_system.trigger( 'bookacti_booking_method_set_up', [ booking_method, reload_events ] );
	
	// Display picked events list if events were selected by default
	if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length
	&&  typeof bookacti.booking_system[ booking_system_id ][ 'events' ] !== 'undefined' ) {
		bookacti_set_min_and_max_quantity( booking_system );
		bookacti_fill_picked_events_list( booking_system );
	}
}


/**
 * Fill the events according to the booking method
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {object} events
 */
function bookacti_booking_method_display_events( booking_system, events ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	booking_system.trigger( 'bookacti_booking_method_display_events', [ booking_method, events ] );
}


/**
 * Refetch events according to booking method
 * @version 1.11.3
 * @param {HTMLElement} booking_system
 */
function bookacti_booking_method_refetch_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	booking_system.trigger( 'bookacti_refetch_events', [ booking_method ] );
}


/**
 * Rerender events according to booking method
 * @version 1.11.3
 * @param {HTMLElement} booking_system
 */
function bookacti_booking_method_rerender_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	booking_system.trigger( 'bookacti_rerender_events', [ booking_method ] );
}


/**
 * Clear events according to booking method
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_booking_method_clear_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	// Reset global arrays
	bookacti.booking_system[ booking_system_id ][ 'events' ]			= [];
	bookacti.booking_system[ booking_system_id ][ 'events_data' ]		= [];
	bookacti.booking_system[ booking_system_id ][ 'events_interval' ]	= [];
	
	booking_system.trigger( 'bookacti_clear_events', [ booking_method ] );
}



// LOADING

/**
 * Start a loading (or keep on loading if already loading)
 * @version 1.15.5
 * @param {HTMLElement} booking_system
 */
function bookacti_start_loading_booking_system( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	if( typeof bookacti.booking_system[ booking_system_id ] === 'undefined' ) { return; }
	
	var booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	if( ! $j.isNumeric( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] ) ) {
		bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0;
	}
	
	booking_system.trigger( 'bookacti_start_loading', [ booking_method ] );
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]++;
}


/**
 * Stop a loading (but keep on loading if there are other loadings)
 * @version 1.15.5
 * @param {HTMLElement} booking_system
 * @param {Boolean} force_exit 
 */
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	force_exit = force_exit ? force_exit : false;
	
	var booking_system_id = booking_system.attr( 'id' );
	if( typeof bookacti.booking_system[ booking_system_id ] === 'undefined' ) { return; }
	
	var booking_method = bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) { booking_method = 'calendar'; }
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]--;
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = Math.max( bookacti.booking_system[ booking_system_id ][ 'loading_number' ], 0 );
	
	if( force_exit ) { bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0; }
	
	// Action to do after everything has loaded
	if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 ) {
		booking_system.trigger( 'bookacti_exit_loading_state', [ booking_method, force_exit ] );
	}
}




// REDIRECT

/**
 * Redirect to activity url
 * @since 1.7.0
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {(FullCalendar.EventApi|Object)} event
 */
function bookacti_redirect_to_activity_url( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes        = bookacti.booking_system[ booking_system_id ];
	var event_id          = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
	
	if( typeof attributes[ 'events_data' ][ event_id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event_id ][ 'activity_id' ];
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
	var booking_system_id = booking_system.attr( 'id' );
	var attributes        = bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'redirect_url_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var redirect_url = attributes[ 'redirect_url_by_group_category' ][ category_id ];

	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}


/**
 * Redirect to url with the booking form values as parameters
 * @since 1.7.10
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 * @param {string} redirect_url
 */
function bookacti_redirect_booking_system_to_url( booking_system, redirect_url ) {
	if( ! redirect_url ) { return; }
	
	// Disable the submit button to avoid multiple redirect
	var form = booking_system.closest( 'form' ).length ? booking_system.closest( 'form' ) : booking_system.closest( '.bookacti-form-fields' );	
	var submit_button = form.find( 'input[type="submit"]' );
	if( submit_button.length ) { submit_button.prop( 'disabled', true ); }
	
	// Start loading
	bookacti_start_loading_booking_system( booking_system );
	
	// Display a loader after the submit button too
	if( submit_button.length ) { bookacti_add_loading_html( submit_button, 'after' ); }
	
	var redirect_form_attr = { 'method': 'post', 'action': redirect_url, 'class': 'bookacti-temporary-form', 'id': '', 'data-redirect-timeout': 15000 };
	
	booking_system.trigger( 'bookacti_before_redirect', [ redirect_form_attr ] );
	
	// Redirect via POST method
	if( booking_system.closest( 'form' ).length ) {
		var form_attr = {};
		$j.each( redirect_form_attr, function( attr_name, attr_value ) { form_attr[ attr_name ] = form.attr( attr_name ); });
		
		form.attr( redirect_form_attr );
		form.submit();
		form.attr( form_attr );
		
	} else {
		booking_system.closest( '.bookacti-form-fields' ).wrap( '<form></form>' );
		booking_system.closest( 'form' ).attr( redirect_form_attr ).submit();
		booking_system.closest( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' );
	}
	
	booking_system.trigger( 'bookacti_after_redirect', [ redirect_form_attr ] );
	
	// Stop loading if nothing happened after 15 seconds
	setTimeout( function() { 
		bookacti_stop_loading_booking_system( booking_system ); 
		if( submit_button.length ) { 
			bookacti_remove_loading_html( booking_system );
			submit_button.prop( 'disabled', false );
		}
	}, redirect_form_attr[ 'data-redirect-timeout' ] );
}