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
		weekNumbersWithinDays:	1,
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
		
		views: { 
			week:		{ eventLimit: false }, 
			day:		{ eventLimit: false },
			listDay:	{ buttonText: bookacti_localized.calendar_button_list_day },
			listWeek:	{ buttonText: bookacti_localized.calendar_button_list_week },
			listMonth:	{ buttonText: bookacti_localized.calendar_button_list_month },
			listYear:	{ buttonText: bookacti_localized.calendar_button_list_year } 
		},

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
			event.bookings = bookacti_get_event_number_of_bookings( event, booking_system_id );
			if( event.bookings != null && event.availability != null ) {

				var is_available = bookacti_is_event_available( booking_system, event );
				
				// If the event or its group is not available, disable the event
				if( ! is_available ) {
					element.addClass( 'bookacti-event-unavailable' );
				}
				
				var avail_div = bookacti_get_event_availability_div( booking_system, event );
				element.append( avail_div );
			}
			
			// Add background to basic views
			if( view.name === 'month' || view.name === 'basicWeek' || view.name === 'basicDay' ) {
				var bg_div = $j( '<div />', {
					'class': 'fc-bg'
				});
				element.append( bg_div );
			}
			
			// Check if the event is on an exception
			if( event.repeat_freq ) {
				if( event.repeat_freq !== 'none' ) {
					if( bookacti.booking_system[ booking_system_id ][ 'exceptions' ] !== undefined 
					&&  bookacti.booking_system[ booking_system_id ][ 'exceptions' ][ event.id ] !== undefined ) {
						$j.each( bookacti.booking_system[ booking_system_id ][ 'exceptions' ][ event.id ], function ( i, excep ) {
							if( excep.exception_type === 'date' && excep.exception_value === event.start.format( 'YYYY-MM-DD' ) ) {
								event.render = 0;
							}
						});
					}
				}
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

		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			bookacti_event_click( booking_system, event );
		}

	}); 
	
	// Update calendar settings
	bookacti_update_calendar_settings( calendar, bookacti.booking_system[ booking_system_id ][ 'settings' ] );
	
	// Check if events array is empty
	var are_events = false;
	if( ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events' ][ 'single' ] )
	||  ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'events' ][ 'repeated' ] ) ) { are_events = true; }
	
	// Load events on calendar
	if( ! reload_events && are_events ) {
		// Fill calendar with events already fetched
		bookacti_fill_calendar_with_events( booking_system );
		
	} else if( reload_events ) {
		// Fetch events from database
		bookacti_fetch_events( booking_system );
		
	} else if( ! are_events ) {
		// If no events are bookable, display an error
		bookacti_add_error_message( booking_system, bookacti_localized.error_no_events_bookable );
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
	
	booking_system.trigger( 'bookacti_after_calendar_set_up' );
}


// Fill calendar with events
function bookacti_fill_calendar_with_events( booking_system ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var calendar = booking_system.find( '.bookacti-calendar:first' );
	
	// Empty the calendar
	calendar.fullCalendar( 'removeEvents' );
	
	// Add events on calendar
	if( bookacti.booking_system[ booking_system_id ][ 'groups_only' ] ) {
		bookacti_display_group_events( booking_system );
	} else {
		bookacti_display_events( booking_system );
	}
	
	// Set calendar period
	var period = bookacti_get_calendar_period( booking_system );
	bookacti.booking_system[ booking_system_id ][ 'period' ] = period;
	
	// Refresh the calendar
	bookacti_refresh_calendar_view( booking_system );
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


// Get calendar period based on displayed events
function bookacti_get_calendar_period( booking_system ) {
	
	// Init variables
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar' );
	
	var template_range = {
		'start'	: moment( bookacti.booking_system[ booking_system_id ][ 'settings' ][ 'start' ] ),
		'end'	: moment( bookacti.booking_system[ booking_system_id ][ 'settings' ][ 'end' ] )
	};
	
	var event_range	= bookacti_get_first_and_last_events_on_calendar( calendar );
	event_range = {
		'start'	: moment( event_range.first.start ),
		'end'	: moment( event_range.last.end )
	};
	
	var period = event_range;
	
	// Choose between template start VS first event, and template end VS last event
	if( template_range.start && event_range.start ) {
		// On booking page, always show all booked event, even outside of templates range
		if( booking_system_id !== 'bookacti-booking-system-bookings-page' ) {
			
			// If template start < event start,	keep event start, 
			// If template start > event start,	keep template start,
			if( template_range.start.isBefore( event_range.start, 'day' ) ) {
				period.start	= event_range.start;
			} else {
				period.start	= template_range.start;
			}

			// If template end < event end,	keep template end, 
			// If template end > event end,	keep event end
			if( template_range.end.isBefore( event_range.end, 'day' ) ) {
				period.end	= template_range.end;
			} else {
				period.end	= event_range.end;
			}
		}
		
	// If template range or event range is missing, just keep the existing one
	} else if( ! template_range.start && event_range.start ) {
		period.start	= event_range.start;
		period.end		= event_range.end;
	} else if( template_range.start && ! event_range.start ) {
		period.start	= template_range.start;
		period.end		= template_range.end;
	}
	
	// If kept start < now and ! fetch_past_event,	keep now date
	if( period.start && ! bookacti.booking_system[ booking_system_id ][ 'past_events' ] && period.start.isBefore( moment(), 'day' ) ) {
		period.start = moment();
	}
	
	// Format range
	if( period.start ) {
		period.start = period.start.format( 'YYYY-MM-DD' );
	}
	if( period.end ) {
		period.end	= period.end.format( 'YYYY-MM-DD' );
	}
	
	return period;
}


// Refresh calendar view
function bookacti_refresh_calendar_view( booking_system ) {
	
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar' );
	
	if( typeof bookacti.booking_system[ booking_system_id ][ 'period' ] !== 'undefined' ) {

		var start_template	= bookacti.booking_system[ booking_system_id ][ 'period' ][ 'start' ];
		var end_template	= bookacti.booking_system[ booking_system_id ][ 'period' ][ 'end' ];

		var start, end = '';
		if( start_template && end_template ) {
			start = moment( start_template );
			end = moment( end_template );
		}

		if( start !== '' && end !== '' && start <= end && start_template && end_template ) {
			booking_system.siblings( '.bookacti-notices ul:not(.bookacti-persistent-notice)' ).remove();
			bookacti_set_valid_range( calendar, start, end );
			calendar.show();
			
			booking_system.trigger( 'bookacti_view_refreshed' );

		} else {

			bookacti_add_error_message( booking_system, bookacti_localized.error_no_events_bookable );
		}
	}
}


// Refresh view after a change of start and end
function bookacti_set_valid_range( calendar, start, end ) {
	// Update calendar valid range 
	calendar.fullCalendar( 'option', 'validRange', {
		start: start,
		end: end.add( 1, 'days' )
	});
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


// Add class for formatting
function bookacti_add_class_according_to_event_size( element ) {
	
	var custom_size = bookacti.event_sizes;
	
	$j( element ).trigger( 'bookacti_event_sizes', [ element, custom_size ] );
	
	if( $j( element ).innerHeight() < custom_size.tiny_height )	{ element.addClass( 'bookacti-tiny-event' ); }
	if( $j( element ).innerHeight() < custom_size.small_height ){ element.addClass( 'bookacti-small-event' ); }
	if( $j( element ).innerWidth() < custom_size.narrow_width )	{ element.addClass( 'bookacti-narrow-event' ); }
	if( $j( element ).innerWidth() > custom_size.wide_width )	{ element.addClass( 'bookacti-wide-event' ); element.removeClass( 'fc-short' ); }
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
	
	if( settings.minTime )		{ settings_to_update.minTime		= settings.minTime; }
	if( settings.maxTime )		{ settings_to_update.maxTime		= settings.maxTime === '00:00' ? '24:00' : settings.maxTime; }
	if( settings.snapDuration ) { settings_to_update.snapDuration	= settings.snapDuration; }
	
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