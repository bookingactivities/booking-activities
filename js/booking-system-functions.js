// Retrieve the events to show and fill the the booking system
function bookacti_fetch_events( booking_system, interval ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
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
			'is_admin': bookacti_localized.is_admin, 
			'interval': interval, 
			'nonce': bookacti_localized.nonce_fetch_events 
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
				
				// Display new events
				if( response.events.length ) {
					bookacti_booking_method_display_events( booking_system, response.events );
				}
				
			} else {
				var error_message = bookacti_localized.error_display_event;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_display_event );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}


// Reload a booking system
function bookacti_reload_booking_system( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: {	
			'action': 'bookactiReloadBookingSystem', 
			'attributes': JSON.stringify( attributes ),
			'is_admin': bookacti_localized.is_admin,
			'nonce': bookacti_localized.nonce_reload_booking_system
		},
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Clear booking system
				booking_system.empty();
				bookacti_clear_booking_system_displayed_info( booking_system );
				
				// Update events and settings
				bookacti.booking_system[ booking_system_id ][ 'events' ]				= response.events;
				bookacti.booking_system[ booking_system_id ][ 'events_data' ]			= response.events_data;
				bookacti.booking_system[ booking_system_id ][ 'events_interval' ]		= response.events_interval;
				bookacti.booking_system[ booking_system_id ][ 'exceptions' ]			= response.exceptions;
				bookacti.booking_system[ booking_system_id ][ 'bookings' ]				= response.bookings;
				bookacti.booking_system[ booking_system_id ][ 'activities_data' ]		= response.activities_data;
				bookacti.booking_system[ booking_system_id ][ 'groups_events' ]			= response.groups_events;
				bookacti.booking_system[ booking_system_id ][ 'groups_data' ]			= response.groups_data;
				bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ]	= response.group_categories_data;
				bookacti.booking_system[ booking_system_id ][ 'template_data' ]			= response.template_data;
				
				// Fill the booking method elements
				booking_system.append( response.html_elements );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system );
				
				
			} else {
				var error_message = bookacti_localized.error_reload_booking_system;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_reload_booking_system );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });	
}


// Switch a booking method
function bookacti_switch_booking_method( booking_system, method ) {
	
	// Sanitize parameters
	method = $j.inArray( method, bookacti_localized.available_booking_methods ) === -1 ? 'calendar' : method;
	
	// If no changes are made, do not perform the function
	if( method === bookacti.booking_system[ booking_system_id ][ 'method' ] ) {
		return false;
	}
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: {	
			'action': 'bookactiSwitchBookingMethod', 
			'booking_system_id': booking_system_id,
			'attributes': JSON.stringify( attributes ),
			'method': method,
			'nonce': bookacti_localized.nonce_switch_booking_method
		},
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Change the booking system attribute
				bookacti.booking_system[ booking_system_id ][ 'method' ] = method;
				
				// Fill the booking method elements
				booking_system.empty();
				booking_system.append( response.html_elements );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system );
				
				
			} else {
				var error_message = bookacti_localized.error_switch_booking_method;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_switch_booking_method );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });	
}


// Display events of a specific interval
function bookacti_fetch_events_from_interval( booking_system, desired_interval ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	
	var current_interval	= bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	var settings			= bookacti.booking_system[ booking_system_id ][ 'template_data' ];
	
	var calendar_start		= moment.utc( settings.start );
	var calendar_end		= moment.utc( settings.end );

	var desired_interval_start	= desired_interval.start.isBefore( calendar_start ) ? calendar_start.clone() : desired_interval.start.clone();
	var desired_interval_end	= desired_interval.end.isAfter( calendar_end ) ? calendar_end.clone() : desired_interval.end.clone();
	
	var new_interval		= false;
	var event_load_interval	= parseInt( bookacti_localized.event_load_interval );
	var min_interval		= {
		'start' : desired_interval_start.clone(),
		'end' : desired_interval_end.clone()
	};
	
	// Compute the new interval of events to load
	// If no events has ever been loaded, compute the first interval to load
	if( $j.isEmptyObject( current_interval ) ) { 
		new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval, event_load_interval );
	} 

	// Else, check if the desired_interval contain unloaded days, and if so, load events for this new interval
	else { 

		var current_interval_start	= moment.utc( current_interval.start );
		var current_interval_end	= moment.utc( current_interval.end );

		if( desired_interval_start.isBefore( current_interval_start ) || desired_interval_end.isAfter( current_interval_end ) ) {
			
			var new_interval_start	= current_interval_start.clone();
			var new_interval_end	= current_interval_end.clone();
			
			var day_before_desired_interval_start	= desired_interval.start.clone().subtract( 1, 'days' );
			var day_after_desired_interval_end		= desired_interval.end.clone().add( 1, 'days' );
			
			// If the current desired_interval include the old interval or if they are not connected at all,
			// Remove the current events and fetch events of the new interval
			if((	( desired_interval_start.isBefore( current_interval_start ) && desired_interval_end.isAfter( current_interval_end ) ) 
				||  ( desired_interval_end.isBefore( current_interval_start ) )	
				||  ( desired_interval_start.isAfter( current_interval_end ) ) )
				&&	! day_before_desired_interval_start.isSame( current_interval_end )
				&&	! day_after_desired_interval_end.isSame( current_interval_start ) ){

				// Remove events
				bookacti_booking_method_clear_events( booking_system );

				// Compute new interval
				new_interval = bookacti_get_new_interval_of_events( booking_system, min_interval, event_load_interval );
			}

			else {
				// If the desired interval starts before current interval of events, loads previous bunch of events
				if( desired_interval_start.isBefore( current_interval_start ) || day_after_desired_interval_end.isSame( current_interval_start ) ) {
					new_interval_start.subtract( event_load_interval, 'days' );
					if( desired_interval_start.isBefore( new_interval_start ) ) {
						new_interval_start = desired_interval_start.clone();
					}
					if( new_interval_start.isBefore( calendar_start ) ) { 
						new_interval_start = calendar_start.clone();
					}
					new_interval_end = current_interval_start.clone().subtract( 1, 'days' );
				}

				// If the desired interval ends after current interval of events, loads next bunch of events
				else if( desired_interval_end.isAfter( current_interval_end ) || day_before_desired_interval_start.isSame( current_interval_end ) ) {
					new_interval_end.add( event_load_interval, 'days' );
					if( desired_interval_end.isAfter( new_interval_end ) ) {
						new_interval_end = desired_interval_end.clone();
					}
					if( new_interval_end.isAfter( calendar_end ) ) { 
						new_interval_end = calendar_end.clone();
					}
					new_interval_start = current_interval_end.clone().add( 1, 'days' );
				}

				new_interval = {
					'start': new_interval_start.format( 'YYYY-MM-DD' ),
					'end': new_interval_end.format( 'YYYY-MM-DD' )
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


// Get the first events interval
function bookacti_get_new_interval_of_events( booking_system, min_interval, interval_duration ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var template_interval = bookacti.booking_system[ booking_system_id ][ 'template_data' ];
	
	if( typeof template_interval.start === 'undefined' || typeof template_interval.end === 'undefined' ) { return {}; }
	
	var past_events		= bookacti.booking_system[ booking_system_id ][ 'past_events' ];
	var current_time	= moment( bookacti_localized.current_time );
	var current_date	= current_time.format( 'YYYY-MM-DD' );

	var calendar_start	= moment( template_interval.start );
	var calendar_end	= moment( template_interval.end ).add( 1, 'days' );
	
	if( ! past_events && calendar_end.isBefore( current_time ) ) { return []; }
	
	if( $j.isEmptyObject( min_interval ) ) {
		if( calendar_start.isAfter( current_time ) ) {
			min_interval = { 'start': template_interval.start, 'end': template_interval.start };
		} else if( calendar_end.isBefore( current_time ) ) {
			min_interval = { 'start': template_interval.end, 'end': template_interval.end };
		} else {
			min_interval = { 'start': current_date, 'end': current_date };
		}
	}
	
	interval_duration	= parseInt( interval_duration ) || parseInt( bookacti_localized.event_load_interval );
	
	var interval_start	= moment( min_interval.start );
	var interval_end	= moment( min_interval.end ).add( 1, 'days' );
	var min_interval_duration = parseInt( Math.abs( moment( min_interval.end ).diff( min_interval.start, 'days' ) ) );
	
	if( min_interval_duration > interval_duration ) { interval_duration = min_interval_duration; }
	
	var half_interval	= Math.round( ( interval_duration - min_interval_duration ) / 2 );
	var interval_end_days_to_add = half_interval;
	
	// Compute Interval start
	if( past_events ) {
		interval_start.subtract( half_interval, 'days' );
		if( calendar_start.isAfter( interval_start ) ) {
			interval_end_days_to_add += Math.abs( interval_start.diff( calendar_start, 'days' ) );
			interval_start = calendar_start.clone();
		}
	} else {
		interval_end_days_to_add += half_interval;
	}
	
	// Compute interval end
	interval_end.add( interval_end_days_to_add, 'days' );
	if( calendar_end.isBefore( interval_end ) ) {
		interval_end = calendar_end;
	}

	var interval = {
		'start'	: interval_start.format( 'YYYY-MM-DD' ), 
		'end'	: interval_end.subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) 
	};

	return interval;
}


// Get the updated events interval based on the old one and one that has been added
function bookacti_get_extended_events_interval( booking_system, interval ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var old_interval = bookacti.booking_system[ booking_system_id ][ 'events_interval' ];
	
	if( $j.isEmptyObject( interval ) ) { return old_interval; }
	
	var new_interval = [];
	if( $j.isEmptyObject( old_interval ) ) {
		new_interval = { 'start' : interval.start, 'end' : interval.end };
	} else {
		new_interval = {
			'start'	: moment( interval.start ).isBefore( old_interval.start ) ? interval.start : old_interval.start,
			'end'	: moment( interval.end ).isAfter( old_interval.end ) ? interval.end : old_interval.end
		};
	}
	
	return new_interval;
}


// Refresh booking numbers
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
        url: ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiGetBookingNumbers', 
                'template_id': template_ids, 
				'event_id': event_ids,
				'nonce': bookacti_localized.nonce_get_booking_numbers
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
				
			} else if( response.error === 'not_allowed' ) {
				
				alert( bookacti_localized.error_retrieve_booking_numbers + '\n' + bookacti_localized.error_not_allowed );
				console.log( response );
			}
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error_retrieve_booking_numbers );
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


// An event is clicked
function bookacti_event_click( booking_system, event ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Get the group id of an event or open a dialog to choose a group
	var group_ids = bookacti_get_event_group_ids( booking_system, event );
	
	// Open a dialog to choose the group of events 
	// if there are several groups, or if users can choose between the single event and at least one group
	if( ( $j.isArray( group_ids ) && group_ids.length > 1 )
	||  ( $j.isArray( group_ids ) && group_ids.length === 1 && bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) ) {
		bookacti_dialog_choose_group_of_events( booking_system, group_ids, event );
	} else {
		// Pick events (single or whole group)
		bookacti_pick_events_of_group( booking_system, group_ids, event );
	}
	
	// If there is only one event in the array, extract it
	group_ids = $j.isArray( group_ids ) && group_ids.length === 1 ? group_ids[ 0 ] : group_ids;
	
	// Yell the event has been clicked
	booking_system.trigger( 'bookacti_event_click', [ event, group_ids ] );
}


// Return groups ids of an event
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
	var event_start = event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
	var event_end	= event.end instanceof moment ? event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;
	
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


// Fill form fields
function bookacti_fill_form_fields( booking_system, event, group_id ) {
	
	group_id = $j.isArray( group_id ) && group_id.length === 1 ? group_id[ 0 ] : group_id;
	group_id = $j.isNumeric( group_id ) || group_id === 'single' ? group_id : false;
	
	var start	= event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
	var end		= event.end instanceof moment ?  event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;
	
	// Fill the form fields
	if( group_id !== false ) {
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( group_id );
	}
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( event.id );
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( start );
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( end );
}


// Pick all events of a group onto the calendar
function bookacti_pick_events_of_group( booking_system, group_id, event ) {
	
	group_id = $j.isArray( group_id ) && group_id.length === 1 ? group_id[ 0 ] : group_id;
	group_id = $j.isNumeric( group_id ) || group_id === 'single' ? group_id : false;
	
	// Sanitize inputs
	if(   group_id === 'single'	&& typeof event !== 'object' ) { return false; }
	if( ! group_id				&& typeof event !== 'object' ) { return false; }
	if( ! group_id				&& typeof event === 'object' ) { group_id = 'single'; }
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Empty the picked events and refresh them
	bookacti_unpick_all_events( booking_system );
	
	// Pick a single event or the whol group
	if( group_id === 'single' ) {
		bookacti_pick_event( booking_system, event );
	} else {
		// Pick the events of the group
		$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, grouped_event ){
			bookacti_pick_event( booking_system, grouped_event );
		});
	}
	
	booking_system.trigger( 'bookacti_events_picked', [ group_id, event ] );
}


// Pick an event
function bookacti_pick_event( booking_system, event ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) ) ) {
		return false;
	}
	
	// Format event object
	var picked_event = {
		'id':			event.id,
		'title':		event.title,
		'start':		event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start,
		'end':			event.end instanceof moment ? event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end,
		'bookings':		event.bookings
	};
	
	// Keep picked events in memory 
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ].push( picked_event );
	
	booking_system.trigger( 'bookacti_pick_event', [ event ] );
}


// Unpick an event
function bookacti_unpick_event( booking_system, event, start, all ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Determine if all event should be unpicked
	all = all ? true : false;
	start = typeof start !== 'undefined' ? start : false;
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' && ! $j.isNumeric( event ) )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) )
	||  ( $j.isNumeric( event ) && ! all && typeof start === 'undefined' ) ) {
		return false;
	}
	
	// If no start value and event is an object, take the event object start value
	if( ! start && typeof event === 'object' ) {
		start = all ? false : event.start;
	}
	
	// Format event object
	var event_to_unpick = {
		'id':	typeof event === 'object' ? event.id : event,
		'start':start instanceof moment ? start.format( 'YYYY-MM-DD HH:mm:ss' ) : start
	};
	
	// Remove picked event(s) from memory 
	var picked_events = $j.grep( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( picked_event ){
		if( picked_event.id == event_to_unpick.id 
		&&  (  all 
			|| picked_event.start.substr( 0, 10 ) === event_to_unpick.start.substr( 0, 10 ) ) ) {

			// Remove the event from the picked_events array
			return false;
		}
		// Keep the event from the picked_events array
		return true;
	});
	
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = picked_events;
	
	booking_system.trigger( 'bookacti_unpick_event', [ event, all ] );
}


// Reset picked events
function bookacti_unpick_all_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	bookacti.booking_system[ booking_system_id ][ 'picked_events' ] = [];
	
	booking_system.trigger( 'bookacti_unpick_all_events' );
}


// Display a list of picked events
function bookacti_fill_picked_events_list( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var event_list_title	= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list-title' );
	var event_list			= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' );
	
	event_list.empty();
	
	if( typeof bookacti.booking_system[ booking_system_id ][ 'picked_events' ] !== 'undefined' ) {
		if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length > 0 ) {
		
			// Fill title with singular or plural
			if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length > 1 ) {
				event_list_title.html( bookacti_localized.selected_events );
			} else {
				event_list_title.html( bookacti_localized.selected_event );
			}

			// Fill the picked events list
			$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, event ) {
				
				var event_duration = bookacti_format_event_duration( event.start, event.end );
				
				var event_data = {
					'title': '<span class="bookacti-booking-event-title" >'  + event.title + '</span>',
					'duration': event_duration,
					'quantity': 1
				};

				booking_system.trigger( 'bookacti_picked_events_list_data', [ event_data ] );
				
				var activity_id = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ];
				var unit = bookacti_get_activity_unit( booking_system, activity_id, event_data.quantity );

				if( unit !== '' ) {
					unit = '<span class="bookacti-booking-event-quantity-separator" > - </span>' 
						 + '<span class="bookacti-booking-event-quantity" >' + unit + '</span>';
				}

				var list_element = $j( '<li />', {
					'html': event_data.title + '<span class="bookacti-booking-event-title-separator" > - </span>' + event_duration + unit
				});

				event_list.append( list_element );
			});

			booking_system.siblings( '.bookacti-picked-events' ).show();

			booking_system.trigger( 'bookacti_picked_events_list_filled' );
		}
	}
}


// Format an event
function bookacti_format_event_duration( start, end ) {
	
	start	= start instanceof moment ? start.format( 'YYYY-MM-DD HH:mm:ss' ) : start;
	end		= end instanceof moment ? end.format( 'YYYY-MM-DD HH:mm:ss' ) : end;
	
	var start_and_end_same_day	= start.substr( 0, 10 ) === end.substr( 0, 10 );
	var class_same_day			= start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
	var end_format				= start_and_end_same_day ? 'LT' : bookacti_localized.date_format;
	
	var event_start = moment( start ).locale( bookacti_localized.current_lang_code );
	var event_end = moment( end ).locale( bookacti_localized.current_lang_code );
	
	var event_duration	= '<span class="bookacti-booking-event-start">' + event_start.format( bookacti_localized.date_format ) + '</span>' 
						+ '<span class="bookacti-booking-event-date-separator ' + class_same_day + '"> &rarr; ' + '</span>' 
						+ '<span class="bookacti-booking-event-end ' + class_same_day + '">' + event_end.format( end_format ) + '</span>';
	
	return event_duration;
}


// Get activity unit value
function bookacti_get_activity_unit( booking_system, activity_id, qty ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var activity_data = bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ];
	
	qty	= $j.isNumeric( qty ) ? parseInt( qty ) : 1;
	var activity_val = '';
	
	if( ! activity_id || typeof activity_data === 'undefined' || qty === 0 ) {
		return '';
	}
	
	if( typeof activity_data !== 'undefined' ) {
		if( typeof activity_data[ 'settings' ] !== 'undefined' ) {
			if( typeof activity_data[ 'settings' ][ 'unit_name_plural' ] !== 'undefined'
			&&  typeof activity_data[ 'settings' ][ 'unit_name_singular' ] !== 'undefined' 
			&&  typeof activity_data[ 'settings' ][ 'places_number' ] !== 'undefined' ) {

				if( activity_data[ 'settings' ][ 'unit_name_plural' ] !== ''
				&&  activity_data[ 'settings' ][ 'unit_name_singular' ] !== '' ) { 
					activity_val += qty + ' ';
					if( qty > 1 ) {
						activity_val += activity_data[ 'settings' ][ 'unit_name_plural' ];
					} else {
						activity_val += activity_data[ 'settings' ][ 'unit_name_singular' ];
					}
				}
				if( activity_data[ 'settings' ][ 'places_number' ] !== '' 
				&&  parseInt( activity_data[ 'settings' ][ 'places_number' ] ) > 0 )
				{
					if( parseInt( activity_data[ 'settings' ][ 'places_number' ] ) > 1 ) {
						activity_val += ' ' + bookacti_localized.n_persons_per_booking.replace( '%1$s', activity_data[ 'settings' ][ 'places_number' ] );
					} else {
						activity_val += ' ' + bookacti_localized.one_person_per_booking;
					}
				}

				if((activity_data[ 'settings' ][ 'unit_name_plural' ] !== ''
				&&	activity_data[ 'settings' ][ 'unit_name_singular' ] !== '' )
				|| (activity_data[ 'settings' ][ 'places_number' ] !== ''
				&&	parseInt( activity_data[ 'settings' ][ 'places_number' ] ) !== 0 ) ) {
					activity_val += '<br/>';
				}
			}
		}
	}
	
	return activity_val;
}


// Clear booking system displayed info
function bookacti_clear_booking_system_displayed_info( booking_system ) {
	
	// Empty the picked events info
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input' ).val('');
	booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' ).empty();
	booking_system.siblings( '.bookacti-picked-events' ).hide();
	bookacti_unpick_all_events( booking_system );
	
	// Clear errors
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
	booking_system.show();
	
	booking_system.trigger( 'bookacti_displayed_info_cleared' );
}


// Get event booking numbers
function bookacti_get_event_number_of_bookings( booking_system, event ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var bookings = bookacti.booking_system[ booking_system_id ][ 'bookings' ];
	
	if( ! bookings ) { return 0; }
	
	var booked_events = bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ];
	
	if( ! booked_events ) { return 0; }
	
	var event_start	= event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
	var event_end	= event.end instanceof moment ?  event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;
	
	var event_bookings = 0;
	$j.each( booked_events, function( i, booked_event ) {
		if( event_start === booked_event.event_start
		&&  event_end === booked_event.event_end ) {
			event_bookings = parseInt( booked_event.quantity );
			return false; // Break the loop
		}
	});

	return event_bookings;
}


// Get event available places
function bookacti_get_event_availability( booking_system, event ) {
	var booking_system_id = booking_system.attr( 'id' );
	var event_availability	= bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ];
	var event_bookings		= bookacti_get_event_number_of_bookings( booking_system, event );
	return parseInt( event_availability ) - parseInt( event_bookings );
}


// Get group available places
function bookacti_get_group_availability( booking_system, group_events ) {
	
	if( ! $j.isArray( group_events ) || group_events.length <= 0 ) {
		return 0;
	}
	
	var min_availability = 999999999999; // Any big int
	$j.each( group_events, function( i, event ) {
		var event_availability = bookacti_get_event_availability( booking_system, event );
		min_availability = event_availability < min_availability ? event_availability : min_availability;
	});
	
	return min_availability;
}


// Check if an event is event available
function bookacti_is_event_available( booking_system, event ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var availability		= bookacti_get_event_availability( booking_system, event );
	var is_available		= false;
	
	if( availability > 0 )  {
		is_available = true;
		// If grouped events can only be book with their whole group
		if( ! bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) {
			// Check if the event is part of a group
			var group_ids = bookacti_get_event_group_ids( booking_system, event );
			if( $j.isArray( group_ids ) && group_ids.length > 0 ) {
				// Check if the event is available in one group at least
				is_available = false;
				$j.each( group_ids, function( i, group_id ) {
					var group_availability = bookacti_get_group_availability( booking_system, bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] );
					if( group_availability > 0 ) {
						is_available = true;
						return false; // Break the loop
					}
				});
			}
		}
	}
	
	return is_available;
}


// Get group available places
function bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, event_groups ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	event_groups = event_groups || bookacti_get_event_group_ids( booking_system, event );
	
	var start	= event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
	var end		= event.end instanceof moment ?  event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;

	var event_bookings	= bookacti_get_event_number_of_bookings( booking_system, event );
	var all_groups		= bookacti.booking_system[ booking_system_id ][ 'groups_events' ];
	
	var group_bookings = 0;
	$j.each( event_groups, function( i, group_id ){
		$j.each( all_groups[ group_id ], function( i, grouped_event ){
			if( event.id === grouped_event.id
			&&  start === grouped_event.start 
			&&  end === grouped_event.end ) {
				group_bookings += parseInt( grouped_event.group_bookings );
			}
		});
	});
	
	return event_bookings - group_bookings;
}


// Get a div with event available places
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
						if( available_places > 1 ) {
							unit_name = activity_data[ 'settings' ][ 'unit_name_plural' ];
						} else {
							unit_name = activity_data[ 'settings' ][ 'unit_name_singular' ];
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


// Sort an array of events by dates
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

// Load the booking system according to booking method
function bookacti_booking_method_set_up( booking_system, reload_events, booking_method ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
	reload_events = reload_events ? 1 : 0;
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_set_calendar_up( booking_system, reload_events );
	} else {
		booking_system.trigger( 'bookacti_booking_method_set_up', [ booking_method, reload_events ] );
	}
}


// Fill the events in the booking method
function bookacti_booking_method_display_events( booking_system, events, booking_method ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_display_events_on_calendar( booking_system, events );
	} else {
		booking_system.trigger( 'bookacti_booking_method_display_events', [ booking_method, events ] );
	}
}


// Update the calendar settings according to booking method
function bookacti_booking_method_update_template_data( booking_system, template_data, booking_method ) {
	var booking_system_id	= booking_system.attr( 'id' );
	booking_method			= booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
	template_data			= template_data || bookacti.booking_system[ booking_system_id ][ 'template_data' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_update_calendar_settings( booking_system.find( '.bookacti-calendar:first' ), bookacti.booking_system[ booking_system_id ][ 'template_data' ] );
	} else {
		booking_system.trigger( 'bookacti_update_template_data', [ booking_method, template_data ] );
	}
}


// Refetch events according to booking method
function bookacti_booking_method_refetch_events( booking_system, booking_method ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'removeEvents' );
		bookacti_fetch_events( booking_system );
	} else {
		booking_system.trigger( 'bookacti_refetch_events', [ booking_method ] );
	}
}


// Rerender events according to booking method
function bookacti_booking_method_rerender_events( booking_system, booking_method ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'rerenderEvents' );
	} else {
		booking_system.trigger( 'bookacti_rerender_events', [ booking_method ] );
	}
}


// Clear events according to booking method
function bookacti_booking_method_clear_events( booking_system, event, booking_method ) {
	var booking_system_id = booking_system.attr( 'id' );
	booking_method = booking_method || bookacti.booking_system[ booking_system_id ][ 'method' ];
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

// Start a loading (or keep on loading if already loading)
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
		
	} else {
		booking_system.trigger( 'bookacti_start_loading', [ booking_method, loading_div ] );
	}
	
	if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 ) {
		booking_system.trigger( 'bookacti_enter_loading_state' );
	}
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]++;
}


// Stop a loading (but keep on loading if there are other loadings )
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	
	force_exit = force_exit || false;
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= bookacti.booking_system[ booking_system_id ][ 'method' ];
	
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ]--;
	bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = Math.max( bookacti.booking_system[ booking_system_id ][ 'loading_number' ], 0 );
	
	if( force_exit ) { bookacti.booking_system[ booking_system_id ][ 'loading_number' ] = 0; }
	
	// Action to do after everything has loaded
	if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 ) {
		
		if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {		
			bookacti_exit_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
		} else {
			booking_system.trigger( 'bookacti_stop_loading', [ booking_method ] );
		}
		
		booking_system.find( '.bookacti-loading-alt' ).remove();
		booking_system.trigger( 'bookacti_exit_loading_state' );
		
	}
}