/**
 * Initialize the calendar
 * @version 1.7.6
 * @param {dom_element} booking_system
 * @param {boolean} reload_events
 */
function bookacti_set_calendar_up( booking_system, reload_events ) {
	reload_events			= reload_events ? 1 : 0;
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar:first' );
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = false;
	
	// Get calendar settings
	var availability_period	= bookacti_get_availability_period( booking_system );
	var settings			= typeof bookacti.booking_system[ booking_system_id ][ 'template_data' ][ 'settings' ] !== 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'template_data' ][ 'settings' ] : {};
	var min_time			= typeof settings.minTime !== 'undefined' ? settings.minTime : '00:00';
	var max_time			= typeof settings.maxTime !== 'undefined' ? ( settings.maxTime === '00:00' ? '24:00' : settings.maxTime ) : '24:00';
	
	// See https://fullcalendar.io/docs/
	var init_data = {
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},
		
		locale:					bookacti_localized.fullcalendar_locale,
		defaultView:            calendar.width() < bookacti_localized.default_view_threshold ? 'agendaDay' : 'agendaWeek',
		weekNumbersWithinDays:	1,
		allDaySlot:             false,
		allDayDefault:          false,
		fixedWeekCount:         false,
		aspectRatio:			'auto',
		editable:               false,
		droppable:              false,
		eventDurationEditable:  false,
		showNonCurrentDates:	false,
		eventLimit:             false,
		eventLimitClick:        'popover',
		dragRevertDuration:     0,
		slotLabelFormat:		'LT',
		slotDuration:           '00:30',
		minTime:                min_time,
		maxTime:                max_time,
		validRange: {
            start: moment( availability_period.start ),
            end: moment( availability_period.end ).add( 1, 'days' )
        },
		
		events: function( start, end, timezone, callback ) {
			callback( [] );
		},

		viewRender: function( view ){ 
			if( bookacti.booking_system[ booking_system_id ][ 'load_events' ] === true ) {
				var interval = { 'start': moment.utc( view.intervalStart ), 'end': moment.utc( view.intervalEnd ).subtract( 1, 'days' ) };
				bookacti_fetch_events_from_interval( booking_system, interval );
			}
		},
		
		eventRender: function( event, element, view ) { 
			// Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'event-start',		event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-start',	event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.data( 'event-end',			event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-end',		event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.data( 'activity-id',		bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ] );
			element.attr( 'data-activity-id',	bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ] );
			event.render = 1;
			
			// Allow HTML in titles
			var title = element.find( '.fc-title' );
			title.html( title.text() );
			
			// Display start and end time in spans
			var time_format	= 'LT';
			// Remove trailing AM/PM in agenda views
			if( view.name.indexOf( 'agenda' ) > -1 ){
				time_format = calendar.fullCalendar( 'option', 'noMeridiemTimeFormat' );
			}
			element.find( '.fc-time' ).html( '<span class="bookacti-event-time-start">' + event.start.format( time_format ) + '</span><span class="bookacti-event-time-separator"> - </span><span class="bookacti-event-time-end">' + event.end.format( time_format ) + '</span>' );
			
			// Add availability div
			if( bookacti_get_event_number_of_bookings( booking_system, event ) != null ) {

				var bookings_only = bookacti.booking_system[ booking_system_id ][ 'bookings_only' ] == 1 ? true : false;
				var avail_div = '';
				
				// If the event or its group is not available, disable the event
				if( bookings_only ) {
					avail_div = bookacti_get_event_number_of_bookings_div( booking_system, event );
				} else {
					var is_available = bookacti_is_event_available( booking_system, event );
					if( ! is_available ) { element.addClass( 'bookacti-event-unavailable' ); }
					avail_div = bookacti_get_event_availability_div( booking_system, event );
				}
				
				element.append( avail_div );
			}
			
			// Add background to basic views
			if( view.hasOwnProperty( 'dayGrid' ) ) {
				var bg_div = $j( '<div />', {
					'class': 'fc-bg'
				});
				element.append( bg_div );
			}
						
			booking_system.trigger( 'bookacti_event_render', [ event, element, view ] );
			
			if( ! event.render ) { return false; }
		},
		
		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
		},
		
		eventAfterAllRender: function( view ) {
			//Display element as picked or selected if they actually are
			$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
				calendar.find( '.fc-event[data-event-id="' + picked_event[ 'id' ] + '"][data-event-start="' + picked_event[ 'start' ] + '"]' ).addClass( 'bookacti-picked-event' );
			});
			
			bookacti_refresh_picked_events_on_calendar( booking_system );
		},
		
		eventClick: function( event, jsEvent, view ) {
			bookacti_event_click( booking_system, event );
		},
		
		eventMouseover: function( event, jsEvent, view ) { 
			var element = $j( this );
			booking_system.trigger( 'bookacti_event_mouse_over', [ event, element ] );
		},
		
		eventMouseout: function( event, jsEvent, view ) { 
			var element = $j( this );
			booking_system.trigger( 'bookacti_event_mouse_out', [ event, element ] );
		}
	};
	
	// Let third-party plugin change initial calendar data
	booking_system.trigger( 'bookacti_calendar_init_data', [ init_data ] );
	
	// Generate the calendar
	calendar.fullCalendar( init_data ); 
	
	// Make sure the event interval fit the view
	var view = calendar.fullCalendar( 'getView' );
	var interval = { 'start': moment.utc( view.start ), 'end': moment.utc( view.end ).subtract( 1, 'days' ) };
	var is_view_larger_than_interval = false;
	if( typeof bookacti.booking_system[ booking_system_id ][ 'events_interval' ] !== 'undefined' ) {
		var event_interval_start= moment.utc( bookacti.booking_system[ booking_system_id ][ 'events_interval' ][ 'start' ] );
		var event_interval_end	= moment.utc( bookacti.booking_system[ booking_system_id ][ 'events_interval' ][ 'end' ] );
		if( event_interval_start.isAfter( interval.start ) || event_interval_end.isBefore( interval.end ) ) {
			is_view_larger_than_interval = true;
		}
	}
	
	// Load events on calendar
	if( ( ! reload_events || is_view_larger_than_interval ) && typeof bookacti.booking_system[ booking_system_id ][ 'events' ] !== 'undefined' ) {
		// Fill calendar with events already fetched
		if( bookacti.booking_system[ booking_system_id ][ 'events' ].length ) {
			bookacti_display_events_on_calendar( booking_system );
		}
	}
	if( reload_events || is_view_larger_than_interval ) {
		// Fetch events from database
		bookacti_fetch_events_from_interval( booking_system, interval );
	}
	
	// Refresh the display of selected events when you click on the View More link
	calendar.off( 'click', '.fc-more' ).on( 'click', '.fc-more', function(){
		bookacti_refresh_picked_events_on_calendar( booking_system );
	});
	
	// Init on pick events actions
	booking_system.off( 'bookacti_pick_event' ).on( 'bookacti_pick_event', function( e, picked_event ){
		bookacti_pick_event_on_calendar( $j( this ), picked_event );
	});
	booking_system.off( 'bookacti_unpick_event' ).on( 'bookacti_unpick_event', function( e, event_to_unpick, all ){
		bookacti_unpick_event_on_calendar( $j( this ), event_to_unpick, all );
	});
	booking_system.off( 'bookacti_unpick_all_events' ).on( 'bookacti_unpick_all_events', function(){
		bookacti_unpick_all_events_on_calendar( $j( this ) );
	});
	
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = true;
	
	// Go to the first picked events
	var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
	if( ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'picked_events' ] ) ) {
		calendar.fullCalendar( 'gotoDate', moment( picked_events[ 0 ][ 'start' ] ) );
	}
	
	booking_system.trigger( 'bookacti_after_calendar_set_up' );
}


// Fill calendar with events
function bookacti_display_events_on_calendar( booking_system, events ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var calendar = booking_system.find( '.bookacti-calendar:first' );
	events = typeof events === 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'events' ] : events;
	
	// Add events on calendar
	calendar.fullCalendar( 'addEventSource', events );
}


// Clear all events on the calendar
function bookacti_clear_events_on_calendar( booking_system, event ) {
	event = event || null;
	var event_id = null;
	var calendar = booking_system.hasClass( 'fc' ) ? booking_system : booking_system.find( '.fc' );
	
	if( event !== null) {
		if( event._id !== undefined ) {
			if( event._id.indexOf('_') >= 0 ) {
				calendar.fullCalendar( 'removeEvents', event._id );
			}
		}
		calendar.fullCalendar( 'removeEvents', event.id );
		event_id = event.id;
	} else {
		calendar.fullCalendar( 'removeEvents' );
	}
	
	return event_id;
}


// Display an event source on the calendar
function bookacti_display_event_source_on_calendar( booking_system, event_source ) {
	var calendar = booking_system.hasClass( 'fc' ) ? booking_system : booking_system.find( '.bookacti-calendar:first' );
	calendar.fullCalendar( 'addEventSource', event_source );
}


// Pick event visually on calendar
function bookacti_pick_event_on_calendar( booking_system, picked_event ) {
	
	var start = picked_event.start instanceof moment ? picked_event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : picked_event.start;
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = booking_system.find( '.fc-event[data-event-id="' + picked_event.id + '"][data-event-start="' + start + '"]' );
	
	// Format the pciked event (because of popover, the same event can appears twice)
	elements.addClass( 'bookacti-picked-event' );
}


// Unpick event visually on calendar
function bookacti_unpick_event_on_calendar( booking_system, event_to_unpick, all ) {
	
	var start = event_to_unpick.start instanceof moment ? event_to_unpick.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event_to_unpick.start;
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = booking_system.find( '.fc-event[data-event-id="' + event_to_unpick.id + '"]' );
	if( ! all && event_to_unpick.start ) {
		elements = booking_system.find( '.fc-event[data-event-id="' + event_to_unpick.id + '"][data-event-start="' + start + '"]' );
	}
	
	// Format the picked event(s)
	elements.removeClass( 'bookacti-picked-event' );
}


// Unpick all events visually on calendar
function bookacti_unpick_all_events_on_calendar( booking_system ) {
	booking_system.find( '.bookacti-picked-event' ).removeClass( 'bookacti-picked-event' );
}


// Make sure picked events appears as picked and vice-versa
function bookacti_refresh_picked_events_on_calendar( booking_system ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	bookacti_unpick_all_events_on_calendar( booking_system );

	$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
		var element = booking_system.find( '.fc-event[data-event-id="' + picked_event.id + '"][data-event-start="' + picked_event.start + '"]' );
		// Format picked events
		element.addClass( 'bookacti-picked-event' );
	});
	
	booking_system.trigger( 'bookacti_refresh_picked_events_on_calendar' );
}


// Get first and last events on calendar
function bookacti_get_first_and_last_events_on_calendar( calendar ) {

	var event_sources	= calendar.fullCalendar( 'getEventSources' );
	var return_events	= { 'first': false, 'last': false };

	if( ! event_sources.length ) { return return_events; }

	$j.each( event_sources, function( i, event_source ) {
		// If the source doesn't have any events, skip to the next one
		if( typeof event_source.rawEventDefs === 'undefined' || event_source.rawEventDefs.length === 0 ) { return true; }
		
		var events_of_source = event_source.rawEventDefs;
		
		// Sort activity event sources by date (repeated event source are already ordered by date)
		if( event_source.id.substr( 0, 8 ) === 'activity' ) {
			events_of_source = bookacti_sort_events_array_by_dates( events_of_source );
		}
				
		// Check if the first event is before the old one
		if( ! return_events.first || ( return_events.first && moment( events_of_source[ 0 ].start ).isBefore( return_events.first.start ) ) ) {
			return_events.first = events_of_source[ 0 ];
		}
		
		// Sort activity event sources by end date because we need to get the event with the highest end date
		// Indeed, the event with the highest start date has not necessarily the highest end date
		if( event_source.id.substr( 0, 8 ) === 'activity' ) {
			events_of_source = bookacti_sort_events_array_by_dates( events_of_source, true );
		}
		
		// Check if the last event is after the old one
		if( ! return_events.last || ( return_events.last && moment( events_of_source[ events_of_source.length - 1 ].end ).isAfter( return_events.last.end ) ) ) {
			return_events.last = events_of_source[ events_of_source.length - 1 ];
		}
	});
	
	return return_events;
}


/**
 * Add CSS classes to events accoding to their size
 * @version 1.5.9
 * @param {dom_element} element
 */
function bookacti_add_class_according_to_event_size( element ) {
	
	var custom_size = bookacti.event_sizes;
	
	$j( element ).trigger( 'bookacti_event_sizes', [ element, custom_size ] );
	
	if( $j( element ).innerHeight() < custom_size.tiny_height )	{ element.addClass( 'bookacti-tiny-event' ).removeClass( 'bookacti-small-event' ); }
	if( $j( element ).innerHeight() < custom_size.small_height ){ element.addClass( 'bookacti-small-event' ).removeClass( 'bookacti-tiny-event' ); }
	if( $j( element ).innerWidth() < custom_size.narrow_width )	{ element.addClass( 'bookacti-narrow-event' ).removeClass( 'bookacti-wide-event' ); }
	if( $j( element ).innerWidth() > custom_size.wide_width )	{ element.addClass( 'bookacti-wide-event' ).removeClass( 'bookacti-narrow-event' ); element.removeClass( 'fc-short' ); }
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