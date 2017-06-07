//Load fullcalendar on a calendar with 'calendar' booking method
function bookacti_load_calendar( booking_system, load_events ) {
	
	load_events = load_events || false;
	
	var booking_system_id = booking_system.data( 'booking-system-id' );
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiRetrieveCalendarElements', 
				'calendar_id': booking_system_id,
				'nonce': bookacti_localized.nonce_retrieve_calendar_elements
			},
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' && response.calendar_elements.length ) {
				
				//Create the calendar
				booking_system.empty();
				booking_system.append( response.calendar_elements );
				
				// Load fullcalendar
				bookacti_set_calendar_up( booking_system_id, load_events );
				
			} else {
				var error_message = bookacti_localized.error_retrieve_booking_system;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_retrieve_booking_system );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });	
}


// Init calendar
function bookacti_set_calendar_up( calendar_id, load_events ) {
	
	load_events = load_events || false;
	
	var booking_system		= $j( '#bookacti-calendar-' + calendar_id ).parent();
	var booking_system_id	= booking_system.data( 'booking-system-id' );
	var calendar			= $j( '#bookacti-calendar-' + calendar_id );
	
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

			//Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'event-date',			event.start.format( 'YYYY-MM-DD' ) );
			element.attr( 'data-event-date',	event.start.format( 'YYYY-MM-DD' ) );
			element.data( 'event-start',		event.start.format( 'HH:mm' ) );
			element.attr( 'data-event-start',	event.start.format( 'HH:mm' ) );
			element.data( 'event-end',			event.end.format( 'HH:mm' ) );
			element.attr( 'data-event-end',		event.end.format( 'HH:mm' ) );
			element.data( 'activity-id',		event.activity_id );
			element.attr( 'data-activity-id',	event.activity_id );
			event.render = 1;
			
			if( view.name.indexOf( 'basic' ) > -1 || view.name.indexOf( 'month' ) > -1 ){
				element.find( 'span.fc-time' ).text( event.start.format( 'HH:mm' ) + ' - ' + event.end.format( 'HH:mm' ) );
			}			
			
			//Add availability div
			if( event.bookings !== undefined && event.availability !== undefined ) {

				var is_bookings		= 0; if( parseInt( event.bookings ) > 0 ) { is_bookings = 1; }
				var availability	= bookacti_get_event_availability( event );
				var avail_div		= bookacti_get_event_availability_div( availability, is_bookings, event.activity_id );

				if( availability <= 0 )  {
					element.addClass( 'event-unavailable' );
				}

				element.append( avail_div );
			}
			
			booking_system.trigger( 'bookacti_event_render', [ event, element ] );
			
			if( ! event.render ) { return false; }
		},
		
		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
		},
		

		eventAfterAllRender: function( view ) {
			//Display element as selected if they actually are
			$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ) {
				$j( '.fc-event[data-event-id="' + picked_event['event_id'] + '"][data-event-date="' + picked_event['event_start'].format( 'YYYY-MM-DD' ) + '"]' ).addClass( 'bookacti-picked-event' );
			});
		},

		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			//Fill the form fields (activity info bound to the product)
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( event.id );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( event.start.format('YYYY-MM-DD[T]HH:mm:ss')  );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( event.end.format('YYYY-MM-DD[T]HH:mm:ss') );

			//Fill an intelligible field to feedback the user about his choice
			bookacti_fill_picked_event_summary( calendar.parent(), event.start, event.end, event.activity_id );

			//Format the selected event
			$j( '.fc-event' ).removeClass( 'bookacti-picked-event' );
			$j( this ).addClass( 'bookacti-picked-event' );
			
			pickedEvents[ booking_system_id ] = [];
			pickedEvents[ booking_system_id ].push( 
			{ 'event_id'			: event.id,
			'activity_id'			: event.activity_id, 
			'event_availability'	: bookacti_get_event_availability( event ), 
			'event_start'			: event.start, 
			'event_end'				: event.end } );
		
			booking_system.trigger( 'bookacti_event_click', [ event ] );
		}

	}); 
	
	//Change the default settings
	bookacti_update_settings_from_database( booking_system, templates_array[calendar_id] );
	
	//Load events on calendar
	if( load_events ) {
		
		calendar.fullCalendar( 'removeEvents' );
		
		var context = 'frontend';
		var fetch_past_events = 0;
		if( calendar_id === 'bookings-page' 
		||  ( calendar_id === 'reschedule' && bookacti_localized.is_admin ) ) {
			fetch_past_events = 1;
			context = 'booking_page';
		}
		
		bookacti_fetch_calendar_events( calendar, fetch_past_events, context );
	}
	
}


//Retrieve the events to show on calendar
function bookacti_fetch_calendar_events( calendar, fetch_past_events, context, callback ) {
	callback			= callback || undefined;
    fetch_past_events	= fetch_past_events || 0;
    fetch_past_events	= fetch_past_events ? 1 : 0;
	context				= context || 'frontend';
	context				= $j.inArray( context, [ 'frontend', 'editor', 'booking_page' ] ) !== -1 ? context : 'frontend';
	
	var booking_system	= calendar.parent();
	var calendar_id		= booking_system.data( 'booking-system-id' );
	
	// Get user current datetime in order to fetch only present and future events
	var user_datetime	= moment().format( 'YYYY-MM-DD HH:mm:ss' );
	
	bookacti_start_loading_booking_system( booking_system );
	
    $j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiFetchEvents', 
                'templates': templates_array[calendar_id], 
				'activities': activities_array[calendar_id],
				'user_datetime': user_datetime,
				'fetch_past_events': fetch_past_events,
				'is_admin': bookacti_localized.is_admin,
				'booking_system_id': calendar_id,
				'context': context,
				'nonce': bookacti_localized.nonce_fetch_events
			},
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ) {
				
				json_events = response.events;
				json_activities = response.activities;
				
				if( callback !== undefined ) {
					callback( json_events );
				} else {
					calendar.fullCalendar( 'addEventSource', json_events );
				}

				if( ! response.events.length ) {
					bookacti_add_error_message( booking_system, bookacti_localized.error_no_events_bookable );
				} else {
					bookacti_set_calendar_period( calendar, fetch_past_events, true );
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


//Get calendar period
function bookacti_set_calendar_period( calendar, fetch_past_events, refresh ) {
	// Sanitize params
	refresh				= refresh || false;
	fetch_past_events	= fetch_past_events || false;
	
	// Init variables
	var new_start_template	= false;
	var new_end_template	= false;
	var calendar_id			= calendar.parent().data( 'booking-system-id' );
	
	var is_template_range	= false;
	var template_range		= calendar.fullCalendar( 'option', 'validRange' );
	if( template_range ) {
		var start_template	= template_range.start;
		var end_template	= template_range.end.subtract( 1, 'days' );
		is_template_range	= true;
	}
	
	json_events = bookacti_sort_events_array_by_dates( json_events );
	var is_event_range = false;
	if( json_events.length > 0 ) {
		var start_first_event	= moment( json_events[0]['start'] );
		var end_last_event		= moment( json_events[json_events.length-1]['end'] );
		is_event_range		= true;
	}
	
	// Choose between template start VS first event, and template end VS last event
	if( is_template_range && is_event_range ) {
		// On booking page, always show all booked event, even outside of templates range
		if( calendar_id === 'bookings-page' ) {
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
	if( new_start_template && ! fetch_past_events && new_start_template.isBefore( moment(), 'day' ) ) {
		new_start_template = moment();
	}
	
	// Format range
	if( new_start_template ) {
		new_start_template	= new_start_template.format( 'YYYY-MM-DD' );
	}
	if( new_end_template ) {
		new_end_template	= new_end_template.format( 'YYYY-MM-DD' );
	}
	
	if( calendarPeriod[ activities_array[calendar_id] + '' ] === undefined ) {
		calendarPeriod[ activities_array[calendar_id] + '' ] = [];
	}
	if( calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ] === undefined ) {
		calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ] = [];
	}

	calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ][ 'start' ] = new_start_template;
	calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ][ 'end' ] = new_end_template;

	if( refresh ) {
		bookacti_refresh_calendar_view( calendar );
	}
}


//Refresh calendar view
function bookacti_refresh_calendar_view( calendar ) {
	
	var booking_system	= calendar.parent();
	var calendar_id		= booking_system.data( 'booking-system-id' );
	
	if( calendarPeriod[ activities_array[calendar_id] + '' ] !== undefined ) {
		if( calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ] !== undefined ) {

			var start_template	= calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ][ 'start' ];
			var end_template	= calendarPeriod[ activities_array[calendar_id] + '' ][ templates_array[calendar_id] + '' ][ 'end' ];

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
	
	var custom_size = size;
	
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