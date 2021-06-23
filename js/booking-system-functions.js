/**
 * Retrieve the events to display on the booking system
 * @version 1.12.0
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
	delete attributes[ 'groups_bookings' ];
	delete attributes[ 'booking_lists' ];
	delete attributes[ 'activities_data' ];
	delete attributes[ 'groups_events' ];
	if( ! attributes[ 'groups_only' ] ) { delete attributes[ 'groups_data' ]; }
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {boolean} keep_picked_events
 */
function bookacti_reload_booking_system( booking_system, keep_picked_events ) {
	keep_picked_events = keep_picked_events || false;
	
	var booking_system_id		= booking_system.attr( 'id' );
	var original_attributes		= $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	var attributes				= $j.extend( true, {}, bookacti.booking_system[ booking_system_id ] );
	
	// Specific data
	var rescheduled_booking_data= typeof attributes.rescheduled_booking_data !== 'undefined' ? attributes.rescheduled_booking_data : [];
	var templates_per_activities= typeof attributes.templates_per_activities !== 'undefined' ? attributes.templates_per_activities : [];
	
	// Do not send useless data
	delete attributes[ 'events' ];
	delete attributes[ 'events_data' ];
	delete attributes[ 'events_interval' ];
	delete attributes[ 'bookings' ];
	delete attributes[ 'groups_bookings' ];
	delete attributes[ 'booking_lists' ];
	delete attributes[ 'activities_data' ];
	delete attributes[ 'groups_events' ];
	delete attributes[ 'groups_data' ];
	delete attributes[ 'group_categories_data' ];
	delete attributes[ 'rescheduled_booking_data' ];
	delete attributes[ 'templates_per_activities' ];
	if( ! keep_picked_events ) { delete attributes[ 'picked_events' ]; }
	
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
				bookacti_clear_booking_system_displayed_info( booking_system );
				
				// Update events and settings
				bookacti.booking_system[ booking_system_id ] = response.booking_system_data;
								
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
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system );
				
				// Refresh picked events
				bookacti_fill_booking_system_fields( booking_system );
				bookacti_fill_picked_events_list( booking_system );
				
				// Trigger action for plugins
				booking_system.trigger( 'bookacti_booking_system_reloaded', original_attributes );
				
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
 * Get the interval of events to be loaded according to the desired view interval
 * @since 1.12.0 (was bookacti_fetch_events_from_interval)
 * @param {HTMLElement} booking_system
 * @param {object} desired_interval { 'start': moment.utc(), 'end': moment.utc() }
 * @return {Object}
 */
function bookacti_get_interval_of_events( booking_system, desired_interval ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var current_interval	= bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	var availability_period	= bookacti_get_availability_period( booking_system );
	var event_load_interval	= parseInt( bookacti_localized.event_load_interval );
	
	var calendar_start	= moment.utc( availability_period.start );
	var calendar_end	= moment.utc( availability_period.end );
	
	var desired_interval_start	= moment.utc( desired_interval.start ).clone();
	var desired_interval_end	= moment.utc( desired_interval.end ).clone();
	if( desired_interval_start.isBefore( calendar_start ) )	{ desired_interval_start = calendar_start.clone(); }
	if( desired_interval_end.isAfter( calendar_end ) )		{ desired_interval_end = calendar_end.clone(); }
	
	var new_interval = {};
	var min_interval = {
		"start" : desired_interval_start.clone(),
		"end" : desired_interval_end.clone()
	};
	
	// Compute the new interval of events to load
	
	// If no events has ever been loaded, compute the first interval to load
	if( $j.isEmptyObject( current_interval ) ) { 
		new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval );
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
	
	return new_interval;
}


/**
 * Get the first events interval
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {Object} min_interval
 * @returns {Object}
 */
function bookacti_get_new_interval_of_events( booking_system, min_interval ) {
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
	
	var interval_duration = parseInt( bookacti_localized.event_load_interval );
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

	var new_interval = {
		"start"	: interval_start.format( 'YYYY-MM-DD HH:mm:ss' ), 
		"end"	: interval_end.format( 'YYYY-MM-DD HH:mm:ss' ) 
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_refresh_booking_numbers( booking_system ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var template_ids		= bookacti.booking_system[ booking_system_id ][ 'calendars' ];
	var groups_data			= bookacti.booking_system[ booking_system_id ][ 'groups_data' ];
	var groups_events		= bookacti.booking_system[ booking_system_id ][ 'groups_events' ];
	
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {object} event
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
		
		if( multiple_bookings ) { bookacti_unpick_events( booking_system, event ); }
		else { bookacti_unpick_all_events( booking_system ); }
		
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
 * @param {HTMLElement} booking_system
 * @param {Object} event
 * @returns {Object}
 */
function bookacti_get_event_groups( booking_system, event ) {
	var groups = {};
	
	// Sanitize event
	if( typeof event !== 'object' ) { return groups; } 
	else if( typeof event.id === 'undefined' || typeof event.start === 'undefined' || typeof event.end === 'undefined' ) { return group_ids; }
	
	var booking_system_id	= booking_system.attr( 'id' );
	var event_id			= parseInt( event.id );
	var event_start			= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end			= moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	
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
 * @param {HTMLElement} booking_system
 * @param {Object|Int} event
 * @param {Int} group_id
 * @param {String} group_date
 * @return {Int}
 */
function bookacti_pick_events( booking_system, event, group_id, group_date ) {
	event = typeof event === 'object' ? event : ( $j.isNumeric( event ) ? { 'id': event } : {} );
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	
	var picked_nb = 0;
	if( typeof event.id === 'undefined' ) { event.id = 0; }
	if( typeof event.start === 'undefined' ) { event.start = ''; }
	if( ! group_id && ! event.id ) { return picked_nb; }
	
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
 * @param {HTMLElement} booking_system
 * @param {Object|Integer} event
 * @returns {Object|False}
 */
function bookacti_is_event_picked( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var picked_events = $j.extend( true, {}, bookacti.booking_system[ booking_system_id ][ 'picked_events' ] );
	var event_start_date = typeof event === 'object' ? moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : '';
	var event_id = typeof event === 'object' ? event.id : event;
	
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {Object|Int} event
 * @param {Int} group_id
 * @param {String} group_date
 * @return {Int}
 */
function bookacti_pick_event( booking_system, event, group_id, group_date ) {
	event = typeof event === 'object' ? event : ( $j.isNumeric( event ) ? { 'id': event } : {} );
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	
	var picked_nb = 0;
	if( typeof event.id === 'undefined' ) { event.id = 0; }
	if( typeof event.start === 'undefined' ) { event.start = ''; }
	if( ! group_id && ! event.id ) { return picked_nb; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Find activity ID
	var activity_id = typeof event.activity_id !== 'undefined' ? parseInt( event.activity_id ) : 0;
	if( ! activity_id ) {
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ] !== 'undefined' ) {
			activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ];
		}
	}
	
	// Format event object
	var picked_event = {
		"group_id": group_id,
		"group_date": group_date ? moment.utc( group_date ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : '',
		"id": parseInt( event.id ),
		"start": moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		"end": moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		"title": event.title,
		"activity_id": parseInt( activity_id )
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
 * @param {HTMLElement} booking_system
 * @param {Object|Int} event
 * @param {Int} group_id
 * @param {String} group_date
 * @return {Int}
 */
function bookacti_unpick_events( booking_system, event, group_id, group_date ) {
	event = typeof event === 'object' ? event : ( $j.isNumeric( event ) ? { 'id': event } : {} );
	group_id = $j.isNumeric( group_id ) ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	
	var unpicked_nb = 0;
	if( typeof event.id === 'undefined' ) { event.id = 0; }
	if( typeof event.start === 'undefined' ) { event.start = ''; }
	if( ! group_id && ! event.id ) { return unpicked_nb; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Format event object
	var event_to_unpick = {
		'id': parseInt( event.id ),
		'start': event.start ? moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) : '',
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
 * Display a list of picked events
 * @version 1.12.0
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
	
	var multiple_bookings = bookacti.booking_system[ booking_system_id ][ 'multiple_bookings' ];
	
	// Get quantity
	var qty_field = booking_system.closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( 'input.bookacti-quantity' );
	var quantity = qty_field.length ? parseInt( qty_field.val() ) : 1;
	
	// Fill title with singular or plural
	var title = bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length === 1 ? bookacti_localized.selected_event : bookacti_localized.selected_events;
	event_list_title.html( title );

	// Fill the picked events list
	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
		var event_duration = bookacti_format_event_duration( picked_event.start, picked_event.end );
		var event_data = {
			'title': picked_event.title,
			'duration': event_duration,
			'quantity': quantity
		};

		booking_system.trigger( 'bookacti_picked_events_list_data', [ event_data, event ] );

		var activity_id = 0;
		if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ picked_event.id ] !== 'undefined' ) {
				activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ picked_event.id ][ 'activity_id' ];
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
		var event_start_formatted = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		var event_end_formatted = moment.utc( picked_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
		
		list_element.data( 'event-id', picked_event.id ).attr( 'data-event-id', picked_event.id );
		list_element.data( 'event-start', event_start_formatted ).attr( 'data-event-start', event_start_formatted );
		list_element.data( 'event-end', event_end_formatted ).attr( 'data-event-end', event_end_formatted );
		
		// Add grouped event to the list
		if( parseInt( picked_event.group_id ) > 0 ) {
			// Add the grouped events list
			if( ! event_list.find( 'li[data-group-id="' + picked_event.group_id + '"][data-group-date="' + picked_event.group_date + '"] ul' ).length ) {
				// Get the group title
				var group_title = '';
				if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ picked_event.group_id ] !== 'undefined' ) {
					var group = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ picked_event.group_id ];
					group_title = '<span class="bookacti-picked-group-of-events-title">' + group.title + '</span>';
				}
				
				var group_list_element = $j( '<li></li>', { 'html': group_title + '<ul class="bookacti-picked-group-of-events-list">' + list_element[0].outerHTML + '</ul>' } );
				group_list_element.data( 'group-id', picked_event.group_id ).attr( 'data-group-id', picked_event.group_id );
				group_list_element.data( 'group-date', picked_event.group_date ).attr( 'data-group-date', picked_event.group_date );
				if( multiple_bookings ) { group_list_element.prepend( '<span class="bookacti-unpick-event-icon"></<span>' ); }
				event_list.append( group_list_element );
			} 
			// The grouped events list was already added
			else {
				var group_list_element = event_list.find( 'li[data-group-id="' + picked_event.group_id + '"][data-group-date="' + picked_event.group_date + '"] ul' );
				group_list_element.append( list_element );
			}
		}
		
		// Add a single event to the list
		else {
			if( multiple_bookings ) { list_element.prepend( '<span class="bookacti-unpick-event-icon"></<span>' ); }
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
 * Get min and max quantity according to the selected events
 * @since 1.9.0
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @returns {Object}
 */
function bookacti_get_min_and_max_quantity( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	
	var group_uids = [];
	var highest_min_quantity	= 1;
	var lowest_max_quantity		= 999999999;
	var lowest_available_places	= 999999999;
	
	var available_places, quantity_booked, min_quantity, max_quantity;
	$j.each( attributes[ 'picked_events' ], function( j, picked_event ) {
		available_places	= 0;
		quantity_booked		= 0;
		min_quantity		= 1;
		max_quantity		= false;
		
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
								var category_data	= attributes[ 'group_categories_data' ][ category_id ][ 'settings' ];
								min_quantity		= typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 1 );
								max_quantity		= typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : false );
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
								var activity_data	= attributes[ 'activities_data' ][ activity_id ][ 'settings' ];
								min_quantity		= typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 1 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 1 );
								max_quantity		= typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? false : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : false );
								
								if( min_quantity || max_quantity ) {
									var event_start_formatted = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
									if( typeof attributes[ 'bookings' ][ picked_event.id ] !== 'undefined' ) {
										if( typeof attributes[ 'bookings' ][ picked_event.id ][ event_start_formatted ] !== 'undefined' ) {
											var occurrence = attributes[ 'bookings' ][ picked_event.id ][ event_start_formatted ];
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
		
		if( min_quantity > highest_min_quantity )					{ highest_min_quantity = min_quantity; }
		if( max_quantity && max_quantity < lowest_max_quantity )	{ lowest_max_quantity = max_quantity; }
		if( available_places < lowest_available_places )			{ lowest_available_places = available_places; }
	});
	
	var qty_data = {
		avail: lowest_available_places,
		min: highest_min_quantity,
		max: lowest_max_quantity
	};
	
	booking_system.trigger( 'bookacti_min_and_max_quantity', [ qty_data ] );
	
	return qty_data;
}


/**
 * Set min and max quantity on the quantity field
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_set_min_and_max_quantity( booking_system ) {
	var qty_data = bookacti_get_min_and_max_quantity( booking_system );
	qty_data.field = booking_system.closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( 'input.bookacti-quantity' );
	
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {Object} event
 * @returns {Int}
 */
function bookacti_get_event_number_of_bookings( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	var event_start	= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var bookings_nb = 0;
	
	if( typeof attributes[ 'bookings' ] !== 'undefined' ) {
		if( typeof attributes[ 'bookings' ][ event.id ] !== 'undefined' ) {
			if( typeof attributes[ 'bookings' ][ event.id ][ event_start ] !== 'undefined' ) {
				if( typeof attributes[ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] !== 'undefined' ) { 
					if( $j.isNumeric( attributes[ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] ) ) {
						bookings_nb = parseInt( attributes[ 'bookings' ][ event.id ][ event_start ][ 'quantity' ] );
					}
				}
			}
		}
	}
	
	return bookings_nb;
}


/**
 * Get event available places
 * @version 1.9.0
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {int}
 */
function bookacti_get_event_availability( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	
	var event_availability = 0;
	if( typeof event.availability !== 'undefined' ) { event_availability = event.availability; }
	if( typeof attributes[ 'events_data' ][ event.id ] !== 'undefined' ) { 
		if( typeof attributes[ 'events_data' ][ event.id ][ 'availability' ] !== 'undefined' ) { 
			if( $j.isNumeric( attributes[ 'events_data' ][ event.id ][ 'availability' ] ) ) { 
				event_availability = parseInt( attributes[ 'events_data' ][ event.id ][ 'availability' ] );
			}
		}
	}
	
	var event_bookings = bookacti_get_event_number_of_bookings( booking_system, event );
	
	return event_availability - event_bookings;
}


/**
 * Check if an event is event available
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {boolean}
 */
function bookacti_is_event_available( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	var past_events			= attributes[ 'past_events' ];
	var past_events_bookable= attributes[ 'past_events_bookable' ];
	
	var current_time	= moment.utc( bookacti_localized.current_time );
	var event_start		= moment.utc( event.start ).clone().locale( 'en' );
	var event_end		= moment.utc( event.end ).clone().locale( 'en' );
	
	var availability		= bookacti_get_event_availability( booking_system, event );
	var availability_period	= bookacti_get_availability_period( booking_system );
	var is_available		= false;
	
	if( availability <= 0 ) { return false; }
	
	// Check if the event is part of a group
	var groups					= bookacti_get_event_groups( booking_system, event );
	var groups_nb				= bookacti_get_event_groups_nb( groups );
	var is_in_group				= groups_nb > 0;
	var groups_single_events	= attributes[ 'groups_single_events' ];
	
	// On the reschedule calendar
	if( booking_system_id === 'bookacti-booking-system-reschedule' ) {
		// If the event is part of a group (and not bookable alone), it cannot be available
		if( is_in_group && ! groups_single_events ) { return false; }
		
		var rescheduled_booking_data = typeof attributes[ 'rescheduled_booking_data' ] !== 'undefined' ? attributes[ 'rescheduled_booking_data' ] : [];
		
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
	&&  typeof attributes[ 'events_data' ][ event.id ] !== 'undefined' ) {
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
			var activity_id		= parseInt( attributes[ 'events_data' ][ event.id ][ 'activity_id' ] );
			var activity_data	= attributes[ 'activities_data' ][ activity_id ][ 'settings' ];

			// Check the max quantity allowed AND
			// Check the max number of different users allowed
			var min_qty_ok = max_qty_ok = max_users_ok = true;
			event_start_formatted = event_start.format( 'YYYY-MM-DD HH:mm:ss' );
			if( typeof attributes[ 'bookings' ][ event.id ] !== 'undefined' ) {
				if( typeof attributes[ 'bookings' ][ event.id ][ event_start_formatted ] !== 'undefined' ) {
					var min_quantity	= typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 0 );
					var max_quantity	= typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : 0 );
					var max_users		= typeof activity_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( activity_data[ 'max_users_per_event' ] ? parseInt( activity_data[ 'max_users_per_event' ] ) : 0 );

					if( min_quantity || max_quantity || max_users ) {
						var occurrence = attributes[ 'bookings' ][ event.id ][ event_start_formatted ];
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
			var group			= attributes[ 'groups_data' ][ group_id ];
			var category_id		= parseInt( group[ 'category_id' ] );
			var category_data	= attributes[ 'group_categories_data' ][ category_id ][ 'settings' ];
			var started_groups_bookable	= bookacti_localized.started_groups_bookable;
			if( typeof category_data[ 'started_groups_bookable' ] !== 'undefined' ) {
				if( $j.inArray( category_data[ 'started_groups_bookable' ], [ 0, 1, '0', '1', true, false ] ) >= 0 ) {
					started_groups_bookable	= parseInt( category_data[ 'started_groups_bookable' ] );
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
				if( ! past_events_bookable && ( group_start.isBefore( moment.utc( availability_period.start ) ) || group_start.isAfter( moment.utc( availability_period.end ) ) ) ) {
					return true; // Skip this group
				}
				
				
				// Get group availability
				var group_availability, current_user_bookings, distinct_users;
				group_availability = current_user_bookings = distinct_users = 0;
				if( typeof attributes[ 'groups_bookings' ][ group_id ] !== 'undefined' ) {
					if( typeof attributes[ 'groups_bookings' ][ group_id ][ group_date ] !== 'undefined' ) {
						group_availability		= attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'availability' ];
						current_user_bookings	= attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'current_user_bookings' ];
						distinct_users			= attributes[ 'groups_bookings' ][ group_id ][ group_date ][ 'distinct_users' ];
					}
				}
				
				if( parseInt( group_availability ) > 0 ) {
					// Check the min and max quantity allowed AND
					// Check the max number of different users allowed
					var min_qty_ok = max_qty_ok = max_users_ok = true;
					if( group != null ) {
						var max_users		= typeof category_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( category_data[ 'max_users_per_event' ] ? parseInt( category_data[ 'max_users_per_event' ] ) : 0 );
						var max_quantity	= typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : 0 );
						var min_quantity	= typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 0 );

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
 * @param {object} event
 * @param {array} groups
 * @returns {Number}
 */
function bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, groups ) {
	groups = ! $j.isEmptyObject( groups ) ? groups : bookacti_get_event_groups( booking_system, event );
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	var event_bookings_nb	= bookacti_get_event_number_of_bookings( booking_system, event );
	
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
 * @version 1.9.3
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
	
	// Detect if the event is available or full, and if it is booked or not
	var total_availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ];
	var class_booked = available_places < total_availability ? 'bookacti-booked' : 'bookacti-not-booked';
	var class_full = available_places <= 0 ? 'bookacti-full' : '';
	
	// Maybe hide event availability
	var hide_availability_class	= '';
	var bookings_only		= bookacti.booking_system[ booking_system_id ][ 'bookings_only' ] == 1 ? true : false;
	var percent_remaining	= parseInt( ( available_places / total_availability ) * 100 );
	var percent_threshold	= parseInt( bookacti.booking_system[ booking_system_id ][ 'hide_availability' ] );
	var fixed_threshold		= parseInt( bookacti_localized.hide_availability_fixed );
	var hide_percent		= percent_threshold < 100 && percent_remaining > percent_threshold;
	var hide_fixed			= fixed_threshold > 0 && available_places > fixed_threshold;
	
	if( ! bookings_only 
	&& ( ( fixed_threshold <= 0 && hide_percent ) || ( percent_threshold >= 100 && hide_fixed ) || ( hide_percent && hide_fixed ) ) ) {
		available_places = '';
		hide_availability_class	= 'bookacti-hide-availability';
	}
	
	// Build a div with availability
	var div = '<div class="bookacti-availability-container ' + hide_availability_class + '" >' 
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
 * @version 1.9.3
 * @param {HTMLElement} booking_system
 * @param {object} event
 * @returns {String}
 */
function bookacti_get_event_number_of_bookings_div( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var total_availability	= parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ] );
	var bookings_number		= bookacti_get_event_number_of_bookings( booking_system, event );
	var available_places	= bookacti_get_event_availability( booking_system, event );
	
	var class_no_availability	= total_availability === 0 ? 'bookacti-no-availability' : '';
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
		if( a_date.isAfter( b_date ) )	{ sort = 1; }
		if( a_date.isBefore( b_date ) )	{ sort = -1; }
		if( desc === true )				{ sort = sort * -1; }
		
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 */
function bookacti_start_loading_booking_system( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
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
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {Boolean} force_exit 
 */
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	force_exit = force_exit ? force_exit : false;
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
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
 * @version 1.11.0
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
	
	var redirect = { 'url': redirect_url, 'redirect_url': redirect_url, 'params': url_params, 'anchor': '' };
	var anchor_pos = redirect.url.indexOf( '#' );
	if( anchor_pos >= 0 ) {
		redirect.anchor = redirect.url.substring( anchor_pos );
		redirect.url = redirect.url.substring( 0, anchor_pos );
	}
	redirect.url += redirect.url.indexOf( '?' ) >= 0 ? '&' + url_params : '?' + url_params;
	redirect.url += redirect.anchor;
	
	booking_system.trigger( 'bookacti_before_redirect', [ redirect ] );
	
	// Redirect to URL
	bookacti_start_loading_booking_system( booking_system );
	window.location.href = redirect.url;
	bookacti_stop_loading_booking_system( booking_system );
}