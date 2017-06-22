// Retrieve the events to show and fill the the booking system
function bookacti_fetch_events( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= calendars_data[ booking_system_id ];
	
	bookacti_start_loading_booking_system( booking_system );
	
    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiFetchEvents', 
			'attributes': JSON.stringify( attributes ),
			'is_admin': bookacti_localized.is_admin, 
			'nonce': bookacti_localized.nonce_fetch_events 
		},
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Update calendar content data
				json_events[booking_system_id]	= response.events;
				json_activities					= response.activities;
				json_groups[booking_system_id]	= response.groups;
				
				if( response.events.length ) {
					// Fill events according to booking method
					bookacti_booking_method_fill_with_events( booking_system, attributes.method );
				} else {
					// If no events are bookable, display an error
					bookacti_add_error_message( booking_system, bookacti_localized.error_no_events_bookable );
				}
				
			} else {
				var error_message = bookacti_localized.error_display_event;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
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
	var attributes			= calendars_data[ booking_system_id ];
	
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
				json_events[ booking_system_id ]					= response.events;
				json_activities										= response.activities;
				json_groups[ booking_system_id ]					= response.groups;
				calendars_data[ booking_system_id ][ 'settings' ]	= response.settings;
				
				// Fill the booking method elements
				booking_system.append( response.html_elements );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system, calendars_data[ booking_system_id ][ 'method' ] );
				
				
			} else {
				var error_message = bookacti_localized.error_reload_booking_system;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
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
	if( method === calendars_data[ booking_system_id ][ 'method' ] ) {
		return false;
	}
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= calendars_data[ booking_system_id ];
	
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
				calendars_data[ booking_system_id ][ 'method' ] = method;
				
				// Fill the booking method elements
				booking_system.empty();
				booking_system.append( response.html_elements );
				
				// Load the booking method
				bookacti_booking_method_set_up( booking_system, method );
				
				
			} else {
				var error_message = bookacti_localized.error_switch_booking_method;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
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


// Pick all events of a group onto the calendar
function bookacti_pick_events_of_group( booking_system, group_id, event ) {
	
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
		$j.each( json_groups[ booking_system_id ][ group_id ], function( i, grouped_event ){
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
		'activity_id':	event.activity_id,
		'title':		event.title,
		'start':		event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start,
		'end':			event.end instanceof moment ? event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end,
		'bookings':		event.bookings,
		'availability':	event.availability
	};
	
	// Keep picked events in memory 
	pickedEvents[ booking_system_id ].push( picked_event );
	
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
	$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ){
		if( typeof picked_event !== 'undefined' ) {
			if( picked_event.id == event_to_unpick.id 
			&&  (  all 
				|| picked_event.start.substr( 0, 10 ) === event_to_unpick.start.substr( 0, 10 ) ) ) {
				
				// Remove the event from the pickedEvents array
				pickedEvents[ booking_system_id ].splice( i, 1 );
				
				// If only one event should be unpicked, break the loop
				if( ! all ) {
					return false;
				}
			}
		}
	});
	
	booking_system.trigger( 'bookacti_unpick_event', [ event, all ] );
}


// Reset picked events
function bookacti_unpick_all_events( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	pickedEvents[ booking_system_id ] = [];
	
	booking_system.trigger( 'bookacti_unpick_all_events' );
}


// Display a list of picked events
function bookacti_fill_picked_events_list( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var event_list_title	= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list-title' );
	var event_list			= booking_system.siblings( '.bookacti-picked-events' ).find( '.bookacti-picked-events-list' );
	
	event_list.empty();
	
	if( typeof pickedEvents[ booking_system_id ] !== 'undefined' ) {
		if( pickedEvents[ booking_system_id ].length > 0 ) {
		
			// Fill title with singular or plural
			if( pickedEvents[ booking_system_id ].length > 1 ) {
				event_list_title.html( bookacti_localized.selected_events );
			} else {
				event_list_title.html( bookacti_localized.selected_event );
			}

			// Fill the picked events list
			$j.each( pickedEvents[ booking_system_id ], function( i, event ) {

				var start_and_end_same_day = event.start.substr( 0, 10 ) === event.end.substr( 0, 10 );

				var event_start = moment( event.start ).locale( bookacti_localized.current_lang_code );
				var event_end = moment( event.end ).locale( bookacti_localized.current_lang_code );

				var event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( bookacti_localized.date_format );
				if( start_and_end_same_day ) {

					event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( 'LT' );
				}
				
				var event_data = {
					'title': event.title,
					'duration': event_duration,
					'quantity': 1
				};
				
				booking_system.trigger( 'bookacti_picked_events_list_data', [ event_data ] );
				
				var unit = bookacti_get_activity_unit( event.activity_id, event_data.quantity );
				
				if( unit !== '' ) {
					unit = ' - ' + unit;
				}
				
				var list_element = $j( '<li />', {
					html: event.title + ' - ' + event_duration + unit
				});

				event_list.append( list_element );
			});

			booking_system.siblings( '.bookacti-picked-events' ).show();

			booking_system.trigger( 'bookacti_picked_events_list_filled' );
		}
	}
}


// Get activity unit value
function bookacti_get_activity_unit( activity_id, qty ) {
	
	qty	= $j.isNumeric( qty ) ? parseInt( qty ) : 1;
	var activity_val = '';
	
	if( ! activity_id || typeof json_activities[ activity_id ] === 'undefined' || qty === 0 ) {
		return '';
	}
	
	if( typeof json_activities[ activity_id ] !== 'undefined' ) {
		if( typeof json_activities[ activity_id ][ 'settings' ] !== 'undefined' ) {
			if( typeof json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== 'undefined'
			&&  typeof json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== 'undefined' 
			&&  typeof json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== 'undefined' ) {

				if( json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== ''
				&&  json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== '' ) { 
					activity_val += qty + ' ';
					if( qty > 1 ) {
						activity_val += json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ];
					} else {
						activity_val += json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ];
					}
				}
				if( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== '' 
				&&  parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) > 0 )
				{
					if( parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) > 1 ) {
						activity_val += ' ' + bookacti_localized.n_persons_per_booking.replace( '%1$s', json_activities[ activity_id ][ 'settings' ][ 'places_number' ] );
					} else {
						activity_val += ' ' + bookacti_localized.one_person_per_booking;
					}
				}

				if((json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== ''
				&&	json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== '' )
				|| (json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== ''
				&&	parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) !== 0 ) ) {
					activity_val += '<br/>';
				}
			}
		}
	}
	
	return activity_val;
}


// Clear booking system displayed info
function bookacti_clear_booking_system_displayed_info( booking_system ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	//Empty the picked events info
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input' ).val('');
	booking_system.siblings( '.bookacti-picked-events-list' ).empty().hide();
	bookacti_unpick_all_events( booking_system );
	
	// Clear errors
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
	booking_system.show();
	
	booking_system.trigger( 'bookacti_displayed_info_cleared' );
}


// Update booking system settings
function bookacti_update_settings_from_database( booking_system, template_ids ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	if( booking_system_id === 'bookacti-template-calendar' ) {
		bookacti_start_template_loading();
	} else {
		bookacti_start_loading_booking_system( booking_system );
	}
	
    // Retrieve template data
    $j.ajax({
        url: bookacti_localized.ajaxurl, 
        data: { 'action': 'bookactiGetBookingSystemData', 
                'template_ids': template_ids,
                'is_admin': bookacti_localized.is_admin,
                'nonce': bookacti_localized.nonce_get_booking_system_data
			}, 
        type: 'POST',
        dataType: 'json',
        success: function( response ){
			
            // If success
            if( response.status === 'success' && response.settings ) {
				
				// Update calendar settings
				if( booking_system.attr( 'id' ) === 'bookacti-template-calendar' ) {
					bookacti_update_calendar_settings( booking_system, response.settings );
				} else {
					var method = calendars_data[ booking_system_id ][ 'method' ];
					bookacti_booking_method_update_settings( booking_system, method );
				}
				
				
            // If error
            } else {
				var message_error = bookacti_localized.error_retrieve_template_data;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
            }
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_retrieve_template_data );        
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


//Get event available places
function bookacti_get_event_availability( event ) {
	return parseInt( event.availability ) - parseInt( event.bookings );
}


//Get group available places
function bookacti_get_group_availability( group_events ) {
	
	if( ! $j.isArray( group_events ) || group_events.length <= 0 ) {
		return 0;
	}
	
	var min_availability = 999999999999; // Any big int
	$j.each( group_events, function( i, event ) {
		var event_availability = bookacti_get_event_availability( event );
		min_availability = event_availability < min_availability ? event_availability : min_availability;
	});
	
	return min_availability;
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
	
	var groups = [];
	$j.each( json_groups[ booking_system_id ], function( group_id, group_events ){
		$j.each( group_events, function( i, group_event ){
			if( group_event[ 'id' ] === event_id
			&&  group_event[ 'start' ] === event_start
			&&  group_event[ 'end' ] === event_end ) {
				groups.push( group_event[ 'group_id' ] );
			}
		});
	});
	
	return groups;
}


// Get a div with event available places
function bookacti_get_event_availability_div( available_places, is_bookings, activity_id ) {
	
	var unit_name = '';
	if( activity_id ) {
		if( json_activities[ activity_id ] !== undefined ) {
			if( json_activities[ activity_id ][ 'settings' ] !== undefined ) {
				if( json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== undefined
				&&  json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== undefined 
				&&  json_activities[ activity_id ][ 'settings' ][ 'show_unit_in_availability' ] !== undefined ) {
					if( parseInt( json_activities[ activity_id ][ 'settings' ][ 'show_unit_in_availability' ] ) ) {
						if( available_places > 1 ) {
							unit_name = json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ];
						} else {
							unit_name = json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ];
						}
					}
				}
			}
		}
	}
	
	var avail = available_places > 1 ? bookacti_localized.avails : bookacti_localized.avail;
	
	//Detect if the event is available or full, and if it is booked or not
	var class_booked = is_bookings ? 'bookacti-booked' : 'bookacti-not-booked';
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


//Display an error message if no availability was found
function bookacti_add_error_message( booking_system, message ) {
	
	message = message || bookacti_localized.error_no_events_bookable;
	
	booking_system.hide();
	booking_system.siblings( '.bookacti-notices' ).empty().append( "<ul class='bookacti-error-list'><li>" + message + "</li></ul>" ).show();
	
	booking_system.trigger( 'bookacti_error_displayed', [ message ] );
}


// Sort an array of events by dates
function bookacti_sort_events_array_by_dates( array, desc ) {
	
	desc = desc || false;
	
	array.sort( function( a, b ) {
		
		//Sort by start date ASC
		var sort = new Date( a.start ) - new Date( b.start );
		
		//If start date is the same, then sort by end date ASC
		if( sort === 0 ) {
			sort = new Date( a.end ) - new Date( b.end );
		}
		
		if( desc === true ) { sort = ! sort; }
		
		return sort;
	});
	
	return array;
}


// Booking system actions based on booking method

// Load the booking system according to booking method
function bookacti_booking_method_set_up( booking_system, booking_method, reload_events ) {
	reload_events = reload_events ? 1 : 0;
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_set_calendar_up( booking_system, reload_events );
	} else {
		booking_system.trigger( 'bookacti_booking_method_set_up', [ booking_method, reload_events ] );
	}
}


// Fill the events in the booking method
function bookacti_booking_method_fill_with_events( booking_system, booking_method ) {
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_fill_calendar_with_events( booking_system );
	} else {
		booking_system.trigger( 'bookacti_booking_method_fill_with_events', [ booking_method ] );
	}
}


// Update the calendar settings according to booking method
function bookacti_booking_method_update_settings( booking_system, booking_method ) {
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_update_calendar_settings( booking_system, calendars_data[ booking_system.attr( 'id' ) ][ 'settings' ] );
	} else {
		booking_system.trigger( 'bookacti_update_settings', [ booking_method ] );
	}
}


// Refetch events according to booking method
function bookacti_booking_method_refetch_events( booking_system, booking_method ) {
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'removeEvents' );
		bookacti_fetch_events( booking_system );
	} else {
		booking_system.trigger( 'bookacti_refetch_events', [ booking_method ] );
	}
}


// Rerender events according to booking method
function bookacti_booking_method_rerender_events( booking_system, booking_method ) {
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		bookacti_refresh_calendar_view( booking_system );
		booking_system.fullCalendar( 'rerenderEvents' );
	} else {
		booking_system.trigger( 'bookacti_rerender_events', [ booking_method ] );
	}
}


//Start a loading (or keep on loading if already loading)
function bookacti_start_loading_booking_system( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= calendars_data[ booking_system_id ][ 'method' ];
	
	var loading_div =	'<div class="bookacti-loading-alt">' 
							+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
							+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
						+ '</div>';
	
	if( ! $j.isNumeric( loadingNumber[ booking_system_id ] ) ) {
		loadingNumber[ booking_system_id ] = 0;
	}
	
	if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {
		if( booking_system.find( '.bookacti-calendar' ).length ) {
			if( loadingNumber[ booking_system_id ] === 0 || ! booking_system.find( '.bookacti-loading-overlay' ).length ) {
				booking_system.find( '.bookacti-loading-alt' ).remove();
				bookacti_enter_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
			}
		} else if( ! booking_system.find( '.bookacti-loading-alt' ).length ) {
			booking_system.prepend( loading_div );
		}
		
	} else {
		booking_system.trigger( 'bookacti_start_loading', [ booking_method, loading_div ] );
	}
	
	if( loadingNumber[ booking_system_id ] === 0 ) {
		booking_system.trigger( 'bookacti_enter_loading_state' );
	}
	
	loadingNumber[ booking_system_id ]++;
}


//Stop a loading (but keep on loading if there are other loadings )
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	
	force_exit = force_exit || false;
	
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_method		= calendars_data[ booking_system_id ][ 'method' ];
	
	loadingNumber[ booking_system_id ]--;
	loadingNumber[ booking_system_id ] = Math.max( loadingNumber[ booking_system_id ], 0 );
	
	if( force_exit ) { loadingNumber[ booking_system_id ] = 0; }
	
	// Action to do after everything has loaded
	if( loadingNumber[ booking_system_id ] === 0 ) {
		
		if( booking_method === 'calendar' || $j.inArray( booking_method, bookacti_localized.available_booking_methods ) === -1 ) {		
			bookacti_exit_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
		} else {
			booking_system.trigger( 'bookacti_stop_loading', [ booking_method ] );
		}
		
		booking_system.find( '.bookacti-loading-alt' ).remove();
		booking_system.trigger( 'bookacti_exit_loading_state' );
		
	}
}