$j( document ).ready( function() {
	/**
	 * Set the calendar up
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 * @param {Boolean} reload_events
	 */
	$j( 'body' ).on( 'bookacti_booking_method_set_up', '.bookacti-booking-system', function( e, booking_method, reload_events ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			bookacti_set_calendar_up( $j( this ), reload_events );
		}
	});
	
	
	/**
	 * Display the events on the calendar
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 * @param {Object} events
	 */
	$j( 'body' ).on( 'bookacti_booking_method_display_events', '.bookacti-booking-system', function( e, booking_method, events ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			bookacti_display_events_on_calendar( $j( this ), events );
		}
	});
	
	
	/**
	 * Refetch events on calendar
	 * @since 1.11.3
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_refetch_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			$j( this ).find( '.bookacti-calendar' ).fullCalendar( 'removeEvents' );
			bookacti_fetch_events( $j( this ) );
		}
	});
	
	
	/**
	 * Rerender events on calendar
	 * @since 1.11.3
	 * @version 1.11.4
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_rerender_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			$j( this ).find( '.bookacti-calendar' ).fullCalendar( 'rerenderEvents' );
		}
	});
	
	
	/**
	 * Clear events on calendar
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_clear_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			$j( this ).find( '.bookacti-calendar' ).fullCalendar( 'removeEvents' );
		}
	});
	
	
	/**
	 * Refresh picked events list - after booking system reloaded
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {Object} original_attributes
	 */
	$j( 'body' ).on( 'bookacti_booking_system_reloaded', '.bookacti-booking-system', function( e, original_attributes ) {
		if( $j( this ).find( '.bookacti-calendar' ).length ) {
			bookacti_refresh_picked_events_on_calendar( $j( this ) );
		}
	});
	
	
	/**
	 * Display a loading feedback on the calendar
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_start_loading', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			var booking_system = $j( this );
			if( booking_system.find( '.bookacti-calendar.fc' ).length ) {
				var booking_system_id = booking_system.attr( 'id' );
				if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 || ! booking_system.find( '.bookacti-loading-overlay' ).length ) {
					booking_system.find( '.bookacti-loading-alt' ).remove();
					bookacti_enter_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
				}
			} else if( ! booking_system.find( '.bookacti-loading-alt' ).length ) {
				var loading_div = '<div class="bookacti-loading-alt">' 
								+	'<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
								+	'<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				booking_system.append( loading_div );
			}
		}
	});
	
	
	/**
	 * Remove the loading feedback from the calendar
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_start_loading', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			$j( this ).find( '.bookacti-loading-alt' ).remove();
			bookacti_exit_calendar_loading_state( $j( this ).find( '.bookacti-calendar' ) );
		}
	});
	
	
	/**
	 * Go to a specific date in calendar
	 * @since 1.12.0
	 */
	$j( 'body' ).on( 'change', '.bookacti-go-to-datepicker', function() {
		var date = $j( this ).val();
		var calendar = $j( this ).closest( '.bookacti-calendar' );
		if( ! date || ! calendar ) { return; }
		if( ! calendar.hasClass( 'fc' ) ) { return; }
		calendar.fullCalendar( 'gotoDate', date );
	});
	
	
	/**
	 * Refresh the display of selected events when you click on the View More link
	 * @since 1.12.0
	 */
	$j( 'body' ).on( 'click', '.bookacti-booking-system .bookacti-calendar .fc-more', function(){
		bookacti_refresh_picked_events_on_calendar( booking_system );
	});
	
	
	/**
	 * Trigger an event when the user start touching an event
	 * @since 1.12.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'touchstart', '.bookacti-booking-system .bookacti-calendar .fc-event, .bookacti-booking-system .bookacti-calendar .fc-list-item', function( e ){
		var booking_system = $j( this ).closest( '.bookacti-booking-system' );
		var element = $j( this );
		var event = {
			'id': parseInt( element.data( 'event-id' ) ),
			'start': moment.utc( element.data( 'event-start' ) ),
			'end': moment.utc( element.data( 'event-end' ) )
		};
		booking_system.trigger( 'bookacti_event_touch_start', [ event, element, e ] );
	});
	
	
	/**
	 * Trigger an event when the user stop touching an event
	 * @since 1.12.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'touchend', '.bookacti-booking-system .bookacti-calendar .fc-event, .bookacti-booking-system .bookacti-calendar .fc-list-item', function( e ){
		var booking_system = $j( this ).closest( '.bookacti-booking-system' );
		var element = $j( this );
		var event = {
			'id': parseInt( element.data( 'event-id' ) ),
			'start': moment.utc( element.data( 'event-start' ) ),
			'end': moment.utc( element.data( 'event-end' ) )
		};
		booking_system.trigger( 'bookacti_event_touch_end', [ event, element, e ] );
	});
	
	
	/**
	 * Refresh the picked events on the calendar - on bookacti_pick_event
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {Object} picked_event
	 * @param {Int} group_id
	 * @param {String} group_date
	 */
	$j( 'body' ).on( 'bookacti_pick_event', '.bookacti-booking-system', function( e, picked_event, group_id, group_date ){
		if( ! $j( this ).find( '.bookacti-calendar' ).length ) { return; }
		bookacti_refresh_picked_events_on_calendar( $j( this ) );
	});
	
	
	/**
	 * Visually unpick all events of the calendar - on bookacti_unpick_all_events
	 * @since 1.12.0
	 */
	$j( 'body' ).on( 'bookacti_unpick_all_events', '.bookacti-booking-system', function() {
		if( ! $j( this ).find( '.bookacti-calendar' ).length ) { return; }
		bookacti_unpick_all_events_on_calendar( $j( this ) );
	});
});


/**
 * Initialize the calendar
 * @version 1.12.0
 * @param {HTMLElement} booking_system
 * @param {boolean} reload_events
 */
function bookacti_set_calendar_up( booking_system, reload_events ) {
	reload_events			= reload_events ? 1 : 0;
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar:first' );
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = false;
	
	// Get calendar display_data
	var availability_period	= bookacti_get_availability_period( booking_system );
	var display_data		= typeof bookacti.booking_system[ booking_system_id ][ 'display_data' ] !== 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'display_data' ] : {};
	var min_time			= typeof display_data.minTime !== 'undefined' ? display_data.minTime : '00:00';
	var max_time			= typeof display_data.maxTime !== 'undefined' ? display_data.maxTime : '24:00';
	
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
		slotEventOverlap:		true,
		minTime:                min_time,
		maxTime:                max_time,
		validRange: {
            start: availability_period.start ? moment.utc( availability_period.start.substr( 0, 10 ) ) : '',
            end: availability_period.end ? moment.utc( availability_period.end.substr( 0, 10 ) ).add( 1, 'days' ) : ''
        },
		
		events: function( start, end, timezone, callback ) {
			callback( [] );
		},

		viewRender: function( view, element ){ 
			if( bookacti.booking_system[ booking_system_id ][ 'load_events' ] === true ) {
				var interval = { 'start': moment.utc( moment.utc( view.intervalStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' ), 'end': moment.utc( moment.utc( view.intervalEnd ).clone().subtract( 1, 'days' ).locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' ) };
				var new_interval = bookacti_get_interval_of_events( booking_system, interval );
				if( ! $j.isEmptyObject() ) { bookacti_fetch_events( booking_system, new_interval ); }
			}
			
			// Add a class if the events are overlapping
			if( view.name.indexOf( 'agenda' ) > -1 ){
				var event_overlap = typeof display_data.slotEventOverlap !== 'undefined' ? display_data.slotEventOverlap : calendar.fullCalendar( 'option', 'slotEventOverlap' );
				if( event_overlap ) { element.addClass( 'bookacti-events-overlap' ); }
			}
			
			booking_system.trigger( 'bookacti_view_render', [ view, element ] );
		},
		
		eventRender: function( event, element, view ) { 
			// Do not render the event if it has no start or no end or no duration
			if( ! event.start || ! event.end || event.start === event.end ) { return false; }
			
			var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			
			// Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'event-start',		event_start_formatted );
			element.attr( 'data-event-start',	event_start_formatted );
			element.data( 'event-end',			event_end_formatted );
			element.attr( 'data-event-end',		event_end_formatted );
			event.render = 1;
			
			if( typeof event_data !== 'undefined' ) {
				element.data( 'activity-id', event_data.activity_id );
				element.attr( 'data-activity-id', event_data.activity_id );
			}
			
			// Allow HTML in titles
			var title = element.find( '.fc-title' );
			title.html( title.text() );
			
			// Display start and end time in spans
			var time_format	= 'LT';
			// Remove trailing AM/PM in agenda views
			if( view.name.indexOf( 'agenda' ) > -1 ){
				time_format = calendar.fullCalendar( 'option', 'noMeridiemTimeFormat' );
			}
			if( bookacti_localized.calendar_localization === 'wp_settings' ){
				time_format = bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
			}
			element.find( '.fc-time' ).html( '<span class="bookacti-event-time-start">' + event.start.format( time_format ) + '</span><span class="bookacti-event-time-separator"> - </span><span class="bookacti-event-time-end">' + event.end.format( time_format ) + '</span>' );
			
			// Add availability div
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

			// Add a class if the current user has booked this event
			if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ] !== 'undefined' ) {
				if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ] !== 'undefined' ) {
					var current_user_bookings = parseInt( bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ][ 'current_user_bookings' ] );
					if( current_user_bookings ) { element.addClass( 'bookacti-event-booked-by-current-user' ); }
				}
			}

			if( avail_div ) { element.append( avail_div ); }
			
			// Add background to basic views
			if( view.hasOwnProperty( 'dayGrid' ) ) {
				var bg_div = $j( '<div></div>', {
					'class': 'fc-bg'
				});
				element.append( bg_div );
			}
			
			booking_system.trigger( 'bookacti_event_render', [ event, element, view ] );
			
			if( ! event.render ) { return false; }
		},
		
		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
			
			booking_system.trigger( 'bookacti_event_after_render', [ event, element, view ] );
		},
		
		eventAfterAllRender: function( view ) {
			// Display element as picked or selected if they actually are
			$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
				var picked_event_start = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				calendar.find( '.fc-event[data-event-id="' + picked_event[ 'id' ] + '"][data-event-start="' + picked_event_start + '"]' ).addClass( 'bookacti-picked-event' );
			});
			
			bookacti_refresh_picked_events_on_calendar( booking_system );
			
			booking_system.trigger( 'bookacti_event_after_all_render', [ view ] );
		},
		
		eventClick: function( event, jsEvent, view ) {
			var trigger = { 'click': true };
			
			// Don't pick the event if it is not available
			if( $j( this ).hasClass( 'bookacti-event-unavailable' ) ) { trigger.click = false; }
			
			// Allow plugins to prevent the event click
			booking_system.trigger( 'bookacti_trigger_event_click', [ trigger, event, jsEvent, view ] );
			
			if( trigger.click ) { bookacti_event_click( booking_system, event ); }
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
	
	if( bookacti_localized.calendar_localization === 'wp_settings' ) {
		init_data.firstDay			= bookacti_localized.wp_start_of_week;
		init_data.slotLabelFormat	= bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
		init_data.timeFormat		= bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
	}
	
	// Let third-party plugin change initial calendar data
	booking_system.trigger( 'bookacti_calendar_init_data', [ init_data ] );
	
	// Generate the calendar
	calendar.fullCalendar( init_data ); 
	
	var view = calendar.fullCalendar( 'getView' );
	var interval = { 'start': moment.utc( moment.utc( view.intervalStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' ), 'end': moment.utc( moment.utc( view.intervalEnd ).clone().subtract( 1, 'days' ).locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' ) };
	
	// Make sure the event interval fit the view
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
		var new_interval = bookacti_get_interval_of_events( booking_system, interval );
		if( ! $j.isEmptyObject() ) { bookacti_fetch_events( booking_system, new_interval ); }
	}
	
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = true;
	
	// Go to the first picked events
	var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
	if( ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'picked_events' ] ) ) {
		calendar.fullCalendar( 'gotoDate', moment.utc( picked_events[ 0 ][ 'start' ] ) );
	}
	
	booking_system.trigger( 'bookacti_after_calendar_set_up' );
}


/**
 * Fill calendar with events
 * @param {HTMLElement} booking_system
 * @param {Object} events
 */
function bookacti_display_events_on_calendar( booking_system, events ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var calendar = booking_system.find( '.bookacti-calendar:first' );
	events = typeof events === 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'events' ] : events;
	
	// Add events on calendar
	calendar.fullCalendar( 'addEventSource', events );
}


/**
 * Display an event source on the calendar
 * @param {HTMLElement} booking_system
 * @param {Object} event_source
 */
function bookacti_display_event_source_on_calendar( booking_system, event_source ) {
	var calendar = booking_system.hasClass( 'fc' ) ? booking_system : booking_system.find( '.bookacti-calendar:first' );
	calendar.fullCalendar( 'addEventSource', event_source );
}


/**
 * Add CSS class to the picked events on calendar, remove it from the others
 * @since 1.8.9
 * @version 1.9.0
 * @param {HTMLElement} booking_system
 */
function bookacti_refresh_picked_events_on_calendar( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
	
	// Unpick all event on the calendar
	bookacti_unpick_all_events_on_calendar( booking_system );
	
	// Pick only the currently picked events
	if( picked_events ) {
		$j.each( picked_events, function( i, picked_event ) {
			var picked_event_start = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );

			// Because of popover and long events (spreading on multiple days), 
			// the same event can appears twice, so we need to apply changes on each
			var elements = booking_system.find( '.fc-event[data-event-id="' + picked_event.id + '"][data-event-start="' + picked_event_start + '"]' );
			
			// Format the pciked event (because of popover, the same event can appears twice)
			elements.addClass( 'bookacti-picked-event' );
		});
	}
	
	booking_system.trigger( 'bookacti_refresh_picked_events_on_calendar' );
}


/**
 * Remove CSS class from all picked events on calendar
 * @param {HTMLElement} booking_system
 */
function bookacti_unpick_all_events_on_calendar( booking_system ) {
	booking_system.find( '.bookacti-picked-event' ).removeClass( 'bookacti-picked-event' );
}


/**
 * Add CSS classes to events accoding to their size
 * @version 1.8.4
 * @param {HTMLElement} element
 */
function bookacti_add_class_according_to_event_size( element ) {
	element.removeClass( 'bookacti-tiny-event bookacti-small-event bookacti-narrow-event bookacti-wide-event' );

	var custom_size = $j.extend( {}, bookacti.event_sizes );
	$j( element ).trigger( 'bookacti_event_sizes', [ element, custom_size ] );

	var add_classes = '';
	var remove_classes = '';
	
	if( $j( element ).innerHeight() < custom_size.small_height ){ add_classes += ' bookacti-small-event'; }
	if( $j( element ).innerHeight() < custom_size.tiny_height )	{ add_classes += ' bookacti-tiny-event'; remove_classes += ' bookacti-small-event'; }
	if( $j( element ).innerWidth() < custom_size.narrow_width )	{ add_classes += ' bookacti-narrow-event'; }
	if( $j( element ).innerWidth() > custom_size.wide_width )	{ add_classes += ' bookacti-wide-event'; remove_classes += ' bookacti-narrow-event fc-short'; }

	if( add_classes )	{ element.addClass( add_classes ); }
	if( remove_classes ){ element.removeClass( remove_classes ); }
}


/**
 * Enter loading state and prevent user from doing anything else
 * @param {HTMLElement} calendar
 */
function bookacti_enter_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).addClass( 'fc-state-disabled' ).attr( 'disabled', true );
	bookacti_append_loading_overlay( calendar.find( '.fc-view-container' ) );
}


/**
 * Exit loading state and allow user to keep editing templates
 * @param {HTMLElement} calendar
 */
function bookacti_exit_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).removeClass( 'fc-state-disabled' ).attr( 'disabled', false );
	bookacti_remove_loading_overlay( calendar.find( '.fc-view-container' ) );
}


/**
 * Append loading overlay
 * @param {HTMLElement} element
 */
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


/**
 * Remove loading overlay
 * @param {HTMLElement} element
 */
function bookacti_remove_loading_overlay( element ) {
	element.find( '.bookacti-loading-overlay' ).remove().css( 'position', 'static' );
}