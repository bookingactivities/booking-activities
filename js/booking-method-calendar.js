// Init calendar
function bookacti_set_calendar_up( booking_system, reload_events ) {
	
	reload_events = reload_events ? 1 : 0;
	
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar:first' );
	
	calendar.fullCalendar({

		// Header : Functionnality to Display above the calendar
		header:  {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},

		// OPTIONS
		locale:					bookacti_localized.current_lang_code,
		
		defaultView:            'agendaWeek',
		allDaySlot:             false,
		allDayDefault:          false,
		fixedWeekCount:         false,
		contentHeight:			'auto',
		editable:               false,
		droppable:              false,
		eventDurationEditable:  false,
		showNonCurrentDates:	false,
		eventLimit:             2,
		eventLimitClick:        'popover',
		dragRevertDuration:     0,
		slotDuration:           '00:30',
		minTime:                '08:00',
		maxTime:                '20:00',
		
		views: { week: { eventLimit: false }, day: { eventLimit: false } },

		//Load an empty array to allow the callback 'loading' to work
		events: function( start, end, timezone, callback ) {
			var empty_array = [];
			callback( empty_array );
		},

		viewRender: function( view ){
		},

		// When an event is rendered
		eventRender: function( event, element, view ) { 

			// Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'event-start',		event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-start',	event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.data( 'event-end',			event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-end',		event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.data( 'activity-id',		event.activity_id );
			element.attr( 'data-activity-id',	event.activity_id );
			event.render = 1;
			
			if( view.name.indexOf( 'basic' ) > -1 || view.name.indexOf( 'month' ) > -1 ){
				element.find( 'span.fc-time' ).text( event.start.format( 'HH:mm' ) + ' - ' + event.end.format( 'HH:mm' ) );
			}			
			
			// Add availability div
			if( event.bookings !== undefined && event.availability !== undefined ) {

				var is_bookings		= 0; if( parseInt( event.bookings ) > 0 ) { is_bookings = 1; }
				var availability	= bookacti_get_event_availability( event );
				var avail_div		= bookacti_get_event_availability_div( availability, is_bookings, event.activity_id );

				if( availability <= 0 )  {
					element.addClass( 'event-unavailable' );
				}

				element.append( avail_div );
			}
			
			// Add background to basic views
			if( view.name === 'month' || view.name === 'basicWeek' || view.name === 'basicDay' ) {
				var bg_div = $j( '<div />', {
					class: 'fc-bg'
				});
				element.append( bg_div );
			}
			
			booking_system.trigger( 'bookacti_event_render', [ event, element ] );
			
			if( ! event.render ) { return false; }
		},
		
		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
		},
		

		eventAfterAllRender: function( view ) {
			//Display element as picked or selected if they actually are
			$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ) {
				calendar.find( '.fc-event[data-event-id="' + picked_event[ 'id' ] + '"][data-event-start="' + picked_event[ 'start' ] + '"]' ).addClass( 'bookacti-picked-event' );
			});
			
			bookacti_refresh_picked_events_display( booking_system );
		},

		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			//Fill the form fields (activity info bound to the product)
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( event.id );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( event.start.format('YYYY-MM-DD HH:mm:ss')  );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( event.end.format('YYYY-MM-DD HH:mm:ss') );

			//Fill an intelligible field to feedback the user about his choice
			bookacti_fill_picked_event_summary( calendar.parent(), event.start, event.end, event.activity_id );
			
			// Because of popover and long events (spreading on multiple days), 
			// the same event can appears twice, so we need to apply changes on each
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
			
			//Format the selected event
			$j( '.fc-event' ).removeClass( 'bookacti-picked-event' );
			elements.addClass( 'bookacti-picked-event' );
			
			pickedEvents[ booking_system_id ] = [];
			
			// Check if the event is part of a group
			var groups = [];
			$j.each( json_groups[ booking_system_id ], function( group_id, group_events ){
				$j.each( group_events, function( i, group_event ){
					if( group_event[ 'id' ] === event.id
					&&  group_event[ 'start' ] === event.start.format( 'YYYY-MM-DD HH:mm:ss' )
					&&  group_event[ 'end' ] === event.end.format( 'YYYY-MM-DD HH:mm:ss' ) ) {
						groups.push( group_event[ 'group_id' ] );
					}
				});
			});
			
			// If the event is part of (a) group(s)
			if( groups.length > 0 ) {
				// If there is only one choice (one group and pick group only)
				if( groups.length === 1 && calendars_data[ booking_system_id ][ 'groups_only' ] ) {
					// Select all the events of the group
					bookacti_pick_events_of_group( booking_system, groups[ 0 ] );
				
				// If the event is in several groups
				} else {
					
					// Ask what group to pick
					bookacti_dialog_choose_group_of_events( booking_system, groups );
					
				}
				
			// If event is single
			} else {
				// Pick single event
				bookacti_pick_event( booking_system, event );
			}
			
			booking_system.trigger( 'bookacti_event_click', [ event ] );
		}

	}); 
	
	// Update calendar settings
	bookacti_update_calendar_settings( calendar, calendars_data[ booking_system_id ][ 'settings' ] );
	
	// Load events on calendar
	if( ! reload_events && json_events[ booking_system_id ].length ) {
		// Fill calendar with events already fetched
		bookacti_fill_calendar_with_events( booking_system );
		
	} else {
		// Fetch events from database
		bookacti_fetch_events( booking_system );
	}
	
	// Refresh the display of selected events when you click on the View More link
	calendar.off().on( 'click', '.fc-more', function(){
		bookacti_refresh_picked_events_display( booking_system );
	});
	
	booking_system.trigger( 'bookacti_after_calendar_set_up' );
}


// Fill calendar with events
function bookacti_fill_calendar_with_events( booking_system ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var calendar = booking_system.find( '.bookacti-calendar:first' );
	
	// Empty the calendar
	calendar.fullCalendar( 'removeEvents' );
	
	// Add events on calendar
	calendar.fullCalendar( 'addEventSource', json_events[ booking_system_id ] );
	
	// Set calendar period
	bookacti_set_calendar_period( booking_system );
}


// Pick all events of a group onto the calendar
function bookacti_pick_events_of_group( booking_system, group_id ) {
	
	if( ! group_id ) {
		return false;
	}
	
	var booking_system_id = booking_system.attr( 'id' );
	var calendar = booking_system.find( '.bookacti-calendar:first' );
	
	// Empty the picked events and refresh them
	pickedEvents[ booking_system_id ] = [];
	
	// Pick the events of the group
	$j.each( json_groups[ booking_system_id ][ group_id ], function( i, event ){
		bookacti_pick_event( booking_system, event );
	});
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
	event = {
		'id': event.id,
		'activity_id': event.activity_id,
		'title': event.title,
		'start': moment( event.start ),
		'end': moment( event.end )
	};
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	
	// Format the pciked event (because of popover, the same event can appears twice)
	elements.addClass( 'bookacti-picked-event' );
	
	// Keep picked events in memory 
	pickedEvents[ booking_system_id ].push({ 
		'id'			: event.id, 
		'activity_id'	: event.activity_id, 
		'title'			: event.title, 
		'start'			: event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
		'end'			: event.end.format( 'YYYY-MM-DD HH:mm:ss' ) 
		// availability TO DO
	});

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
	
	if( typeof event !== 'object' ) {
		// Format start values to object
		var event_id = event;
		start	= start.isMoment() ? start : moment( start ).isValid() ? moment( start ) : false;
		event	= {
			'id': event_id,
			'start': start 
		};
	}
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"]' );
	if( ! all && event.start ) {
		elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	}
	
	// Format the picked event(s)
	elements.removeClass( 'bookacti-picked-event' );

	// Remove picked event(s) from memory 
	$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ){
		if( typeof picked_event !== 'undefined' ) {
			if( picked_event.id == event.id 
			&&  (  all 
				|| picked_event.start.substr( 0, 10 ) === event.start.format( 'YYYY-MM-DD' ) ) ) {
				
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


// Make sure picked events appears as picked and vice-versa
function bookacti_refresh_picked_events_display( booking_system ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	$j( '.fc-event' ).removeClass( 'bookacti-picked-event' );

	$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ) {
		var element = $j( '.fc-event[data-event-id="' + picked_event.id + '"][data-event-start="' + picked_event.start + '"]' );
		// Format picked events
		element.addClass( 'bookacti-picked-event' );
	});
	
	booking_system.trigger( 'bookacti_refresh_picked_events' );
}


//Get calendar period
function bookacti_set_calendar_period( booking_system, refresh ) {
	
	// Sanitize params
	refresh = typeof refresh === 'undefined' ? 1 : parseInt( refresh );
	
	
	// Init variables
	var calendar			= booking_system.find( '.bookacti-calendar' );
	var booking_system_id	= booking_system.attr( 'id' );
	var new_start_template	= false;
	var new_end_template	= false;
	
	var is_template_range	= false;
	var template_range		= calendar.fullCalendar( 'option', 'validRange' );
	if( template_range ) {
		var start_template	= template_range.start;
		var end_template	= template_range.end.subtract( 1, 'days' );
		is_template_range	= true;
	}
	
	json_events[ booking_system_id ] = bookacti_sort_events_array_by_dates( json_events[ booking_system_id ] );
	var is_event_range = false;
	if( json_events[ booking_system_id ].length > 0 ) {
		var start_first_event	= moment( json_events[ booking_system_id ][ 0 ][ 'start' ] );
		var end_last_event		= moment( json_events[ booking_system_id ][ json_events[ booking_system_id ].length - 1][ 'end' ] );
		is_event_range		= true;
	}
	
	// Choose between template start VS first event, and template end VS last event
	if( is_template_range && is_event_range ) {
		// On booking page, always show all booked event, even outside of templates range
		if( booking_system_id === 'bookacti-booking-system-bookings-page' ) {
			new_start_template	= start_first_event;
			new_end_template	= end_last_event;
			
		} else {
			
			// If template start < event start,	keep event start, 
			// If template start > event start,	keep template start,
			if( start_template.isBefore( start_first_event, 'day' ) ) {
				new_start_template	= start_first_event;
			} else {
				new_start_template	= start_template;
			}

			// If template end < event end,	keep template end, 
			// If template end > event end,	keep event end
			if( end_template.isBefore( end_last_event, 'day' ) ) {
				new_end_template	= end_template;
			} else {
				new_end_template	= end_last_event;
			}
		}
		
	// If template range or event range is missing, just keep the existing one
	} else if( ! is_template_range && is_event_range ) {
		new_start_template	= start_first_event;
		new_end_template	= end_last_event;
	} else if( is_template_range && ! is_event_range ) {
		new_start_template	= start_template;
		new_end_template	= end_template;
	}
	
	// If kept start < now and ! fetch_past_event,	keep now date
	if( new_start_template && ! calendars_data[ booking_system_id ][ 'past_events' ] && new_start_template.isBefore( moment(), 'day' ) ) {
		new_start_template = moment();
	}
	
	// Format range
	if( new_start_template ) {
		new_start_template	= new_start_template.format( 'YYYY-MM-DD' );
	}
	if( new_end_template ) {
		new_end_template	= new_end_template.format( 'YYYY-MM-DD' );
	}
	
	if( calendars_data[ booking_system_id ][ 'period' ] === undefined ) {
		calendars_data[ booking_system_id ][ 'period' ] = [];
	}

	calendars_data[ booking_system_id ][ 'period' ][ 'start' ]	= new_start_template;
	calendars_data[ booking_system_id ][ 'period' ][ 'end' ]	= new_end_template;

	if( refresh ) {
		bookacti_refresh_calendar_view( booking_system );
	}
}


//Refresh calendar view
function bookacti_refresh_calendar_view( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar' );
	
	if( calendars_data[ booking_system_id ][ 'period' ] !== undefined ) {

		var start_template	= calendars_data[ booking_system_id ][ 'period' ][ 'start' ];
		var end_template	= calendars_data[ booking_system_id ][ 'period' ][ 'end' ];

		var start, end = '';
		if( start_template && end_template ) {
			start = moment( start_template );
			end = moment( end_template );
		}

		if( start !== '' && end !== '' && start <= end && start_template && end_template ) {

			calendar.show();

			booking_system.siblings( '.bookacti-notices ul:not(.bookacti-persistent-notice)' ).remove();

			bookacti_refresh_view( calendar, start, end );

			booking_system.trigger( 'bookacti_view_refreshed' );

		} else {

			bookacti_add_error_message( booking_system, bookacti_localized.error_no_events_bookable );
		}
	}
}

//Refresh view after a change of start and end
function bookacti_refresh_view( calendar, start_template, end_template ) {
	// Update calendar valid range 
	calendar.fullCalendar( 'option', 'validRange', {
		start: start_template,
		end: end_template.add( 1, 'days' )
	});
}


// Add class for formatting
function bookacti_add_class_according_to_event_size( element ) {
	
	var custom_size = event_sizes;
	
	$j( element ).trigger( 'bookacti_event_sizes', [ element, custom_size ] );
	
	if( $j( element ).innerHeight() < custom_size.tinyHeight )	{ element.addClass( 'bookacti-tiny-event' ); }
	if( $j( element ).innerHeight() < custom_size.smallHeight )	{ element.addClass( 'bookacti-small-event' ); }
	if( $j( element ).innerWidth() < custom_size.narrowWidth )	{ element.addClass( 'bookacti-narrow-event' ); }
	if( $j( element ).innerWidth() > custom_size.wideWidth )	{ element.addClass( 'bookacti-wide-event' ); element.removeClass( 'fc-short' ); }
}


// Dynamically update calendar settings
function bookacti_update_calendar_settings( calendar, settings ) {
	
	var settings_to_update = {};
	
	if( settings.start && settings.end ) {
		settings_to_update.validRange = {
            start: moment( settings.start ),
            end: moment( settings.end ).add( 1, 'days' )
        };
	}
	
	if( settings.minTime )	{ settings_to_update.minTime	= settings.minTime; }
	if( settings.maxTime )	{ settings_to_update.maxTime	= settings.maxTime === '00:00' ? '24:00' : settings.maxTime; }	
	
	calendar.trigger( 'bookacti_before_update_calendar_settings', [ settings_to_update, settings ] );
	
	if( ! $j.isEmptyObject( settings_to_update ) ) {
		calendar.fullCalendar( 'option', settings_to_update );
	}
	
	calendar.fullCalendar( 'option', 'validRange', settings_to_update.validRange );
	
	calendar.trigger( 'bookacti_calendar_settings_updated', [ settings_to_update, settings ] );
}


//Enter loading state and prevent user from doing anything else
function bookacti_enter_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).addClass( 'fc-state-disabled' ).attr( 'disabled', true );
	bookacti_append_loading_overlay( calendar.find( '.fc-view-container' ) );
}


//Exit loading state and allow user to keep editing templates
function bookacti_exit_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).removeClass( 'fc-state-disabled' ).attr( 'disabled', false );
	bookacti_remove_loading_overlay( calendar.find( '.fc-view-container' ) );
}


// Append loading overlay
function bookacti_append_loading_overlay( element ) {
	element.append(
		'<div class="bookacti-loading-overlay" >'
			+ '<div class="bookacti-loading-content" >'
				+ '<div class="bookacti-loading-box" >'
					+ '<div class="bookacti-loading-image" >'
						+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+ '</div>' 
					+ '<div class="bookacti-loading-text" >'
						+ bookacti_localized.loading
					+ '</div>' 
				+ '</div>' 
			+ '</div>' 
		+ '</div>'
	).css( 'position', 'relative' );
}


// Remove loading overlay
function bookacti_remove_loading_overlay( element ) {
	element.find( '.bookacti-loading-overlay' ).remove().css( 'position', 'static' );
}