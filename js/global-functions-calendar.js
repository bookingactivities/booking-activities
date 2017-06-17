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

			//Add some info to the event
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
			//Display element as picked or selected if they actually are
			$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ) {
				calendar.find( '.fc-event[data-event-id="' + picked_event['event_id'] + '"][data-event-start="' + picked_event['event_start'] + '"]' ).addClass( 'bookacti-picked-event' );
			});
		},

		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			//Fill the form fields (activity info bound to the product)
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( event.id );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( event.start.format('YYYY-MM-DD HH:mm:ss')  );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( event.end.format('YYYY-MM-DD HH:mm:ss') );

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
			'event_start'			: event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
			'event_end'				: event.end.format( 'YYYY-MM-DD HH:mm:ss' ) } );
		
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