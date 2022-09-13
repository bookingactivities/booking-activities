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
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 * @param {Object} events
	 */
	$j( 'body' ).on( 'bookacti_booking_method_display_events', '.bookacti-booking-system', function( e, booking_method, events ) {
		if( booking_method === 'calendar' && $j( this ).find( '.bookacti-calendar' ).length ) {
			bookacti_fc_add_events( $j( this ), events );
		}
	});
	
	
	/**
	 * Refetch events on calendar
	 * @since 1.11.3
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_refetch_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' ) {
			var booking_system_id = $j( this ).attr( 'id' );
			if( typeof bookacti.fc_calendar[ booking_system_id ] !== 'undefined' ) {
				bookacti.fc_calendar[ booking_system_id ].removeAllEvents();
			}
			bookacti_get_booking_system_data_by_interval( $j( this ) );
		}
	});
	
	
	/**
	 * Rerender events on calendar
	 * @since 1.11.3
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_rerender_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' ) {
			var booking_system_id = $j( this ).attr( 'id' );
			if( typeof bookacti.fc_calendar[ booking_system_id ] !== 'undefined' ) {
				bookacti.fc_calendar[ booking_system_id ].render();
			}
		}
	});
	
	
	/**
	 * Clear events on calendar
	 * @since 1.12.0
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_clear_events', '.bookacti-booking-system', function( e, booking_method ) {
		if( booking_method === 'calendar' ) {
			var booking_system_id = $j( this ).attr( 'id' );
			if( typeof bookacti.fc_calendar[ booking_system_id ] !== 'undefined' ) {
				bookacti.fc_calendar[ booking_system_id ].removeAllEvents();
			}
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
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_start_loading', '.bookacti-booking-system, .bookacti-booking-system-editor', function( e, booking_method ) {
		if( booking_method === 'calendar' ) {
			var booking_system = $j( this );
			if( booking_system.find( '.bookacti-calendar.fc' ).length ) {
				var booking_system_id = booking_system.attr( 'id' );
				if( bookacti.booking_system[ booking_system_id ][ 'loading_number' ] === 0 || ! booking_system.find( '.bookacti-loading-overlay' ).length ) {
					bookacti_remove_loading_html( booking_system );
					bookacti_enter_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
				}
			} else if( ! booking_system.find( '.bookacti-loading-container' ).length ) {
				bookacti_add_loading_html( booking_system );
			}
		}
	});
	
	
	/**
	 * Remove the loading feedback from the calendar
	 * @since 1.12.0
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_exit_loading_state', '.bookacti-booking-system, .bookacti-booking-system-editor', function( e, booking_method ) {
		if( booking_method === 'calendar' ) {
			bookacti_remove_loading_html( $j( this ) );
			if( $j( this ).find( '.bookacti-calendar' ).length ) { 
				bookacti_exit_calendar_loading_state( $j( this ).find( '.bookacti-calendar' ) );
			}
		}
	});
	
	
	/**
	 * Go to a specific date in calendar
	 * @since 1.12.0
	 * @version 1.15.0
	 */
	$j( 'body' ).on( 'change', '.bookacti-go-to-datepicker', function() {
		var date = $j( this ).val();
		var booking_system_id = $j( this ).closest( '.bookacti-booking-system' ).length ? $j( this ).closest( '.bookacti-booking-system' ).attr( 'id' ) : 'bookacti-template-calendar';
		if( ! date || typeof bookacti.fc_calendar[ booking_system_id ] === 'undefined' ) { return; }
		if( parseInt( date.substr( 0, 4 ) ) < 1970 ) { return; }
		bookacti.fc_calendar[ booking_system_id ].gotoDate( date );
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
	 * @version 1.15.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'touchstart', '.bookacti-booking-system .bookacti-calendar .fc-event, .bookacti-booking-system .bookacti-calendar .fc-list-event', function( e ){
		var booking_system = $j( this ).closest( '.bookacti-booking-system' );
		var element = $j( this );
		var event = {
			'id': parseInt( element.data( 'event-id' ) ),
			'start': moment.utc( element.data( 'event-start' ) ),
			'end': moment.utc( element.data( 'event-end' ) )
		};
		booking_system.trigger( 'bookacti_calendar_event_touch_start', [ { 'event': event, 'el': element, 'jsEvent': e } ] );
	});
	
	
	/**
	 * Trigger an event when the user stop touching an event
	 * @since 1.12.0
	 * @version 1.15.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'touchend', '.bookacti-booking-system .bookacti-calendar .fc-event, .bookacti-booking-system .bookacti-calendar .fc-list-event', function( e ){
		var booking_system = $j( this ).closest( '.bookacti-booking-system' );
		var element = $j( this );
		var event = {
			'id': parseInt( element.data( 'event-id' ) ),
			'start': moment.utc( element.data( 'event-start' ) ),
			'end': moment.utc( element.data( 'event-end' ) )
		};
		booking_system.trigger( 'bookacti_calendar_event_touch_end', [ { 'event': event, 'el': element, 'jsEvent': e } ] );
	});
	
	
	/**
	 * Refresh the picked events on the calendar - on bookacti_pick_event
	 * @since 1.12.0
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {Object} picked_event
	 * @param {Int} group_id
	 * @param {String} group_date
	 */
	$j( 'body' ).on( 'bookacti_pick_event', '.bookacti-booking-system, #bookacti-template-calendar', function( e, picked_event, group_id, group_date ){
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
 * @version 1.15.3
 * @param {HTMLElement} booking_system
 * @param {boolean} reload_events
 */
function bookacti_set_calendar_up( booking_system, reload_events ) {
	reload_events = reload_events ? 1 : 0;
	var booking_system_id = booking_system.attr( 'id' );
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = false;
	
	// Get calendar display_data
	var availability_period = bookacti_get_availability_period( booking_system );
	var display_data        = typeof bookacti.booking_system[ booking_system_id ][ 'display_data' ] !== 'undefined' ? bookacti.booking_system[ booking_system_id ][ 'display_data' ] : {};
	var event_min_height    = typeof bookacti_localized.event_tiny_height !== 'undefined' ? parseInt( bookacti_localized.event_tiny_height ) : 32;
	var slot_min_time       = typeof display_data.slotMinTime !== 'undefined' ? display_data.slotMinTime : '00:00';
	var slot_max_time       = typeof display_data.slotMaxTime !== 'undefined' ? display_data.slotMaxTime : '24:00';
	var next_day_threshold  = moment.utc( '1970-02-01 ' + slot_min_time ).add( 1, 'minutes' ).format( 'HH:mm' ); // One minute after slot_min_time
	
	// See https://fullcalendar.io/docs/
	var init_data = {
		locale:                bookacti_localized.fullcalendar_locale,
		timeZone:              bookacti_localized.fullcalendar_timezone,
		initialView:           booking_system.find( '.bookacti-calendar:first' ).width() < bookacti_localized.initial_view_threshold ? 'timeGridDay' : 'timeGridWeek',
		allDaySlot:            false,
		defaultAllDay:         false,
		fixedWeekCount:        false,
		height:                'auto',
		contentHeight:         'auto',
		editable:              false,
		droppable:             false,
		eventDurationEditable: false,
		showNonCurrentDates:   false,
		dayMaxEvents:          false,
		moreLinkClick:         'popover',
		eventDisplay:          'block',
		dragRevertDuration:    0,
		eventShortHeight:      0,
		slotDuration:          '00:30',
		slotEventOverlap:       false,
		eventMinHeight:         event_min_height,
		nextDayThreshold:       next_day_threshold,
		slotMinTime:            slot_min_time,
		slotMaxTime:            slot_max_time,
		
		validRange: {
            start: availability_period.start ? moment.utc( availability_period.start.substr( 0, 10 ) ).format( 'YYYY-MM-DD' ) : '',
            end: availability_period.end ? moment.utc( availability_period.end.substr( 0, 10 ) ).add( 1, 'days' ).format( 'YYYY-MM-DD' ) : ''
        },
		
		headerToolbar: {
			start: 'prev,next today',
			center: 'title',
			end: 'dayGridMonth,timeGridWeek,timeGridDay'
		},
		
		
		/**
		 * Default events (empty because events will be added with bookacti_fc_add_events())
		 * Always call successCallback or failureCallback for proper operations, even with an empty array of events
		 * @version 1.15.0
		 * @param {Object} info
		 * @param {Function} successCallback
		 * @param {Function} failureCallback
		 */
		events: function( info, successCallback, failureCallback ) {
			successCallback( [] );
		},
		
		
		/**
		 * Add classes to the view
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.ViewApi} view
		 *  @type {HTMLElement} el
		 * }
		 * @returns {Array}
		 */
		viewClassNames: function( info ) {
			var return_object = { 'class_names': [] };
			
			booking_system.trigger( 'bookacti_calendar_view_class_names', [ return_object, info ] );
			
			return return_object.class_names;
		},
		
		
		/**
		 * Add classes to the day header
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {Date} date
		 *  @type {String} dayNumberText
		 *  @type {Boolean} isPast
		 *  @type {Boolean} isFuture
		 *  @type {Boolean} isToday
		 *  @type {Boolean} isOther
		 * }
		 * @returns {Array}
		 */
		dayHeaderClassNames: function( info ) {
			var return_object = { 'class_names': [] };
			
			// Gray out days off
			if( typeof bookacti.booking_system[ booking_system_id ][ 'days_off' ] !== 'undefined' ) {
				var days_off = bookacti.booking_system[ booking_system_id ][ 'days_off' ];
				var day_date = moment.utc( info.date );
				$j.each( days_off, function ( i, day_off ) {
					var day_off_from = moment.utc( day_off.from + ' 00:00:00' );
					var day_off_to = moment.utc( day_off.to + ' 23:59:59' );
					if( day_date.isBetween( day_off_from, day_off_to, 'second', '[]' ) ) {
						return_object.class_names.push( 'fc-day-disabled' );
						return false; // break
					}
				});
			}
			
			booking_system.trigger( 'bookacti_calendar_day_header_class_names', [ return_object, info ] );
			
			return return_object.class_names;
		},
		
		
		/**
		 * Add classes to the day cell
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {Date} date
		 *  @type {String} dayNumberText
		 *  @type {Boolean} isPast
		 *  @type {Boolean} isFuture
		 *  @type {Boolean} isToday
		 *  @type {Boolean} isOther
		 * }
		 * @returns {Array}
		 */
		dayCellClassNames: function( info ) {
			var return_object = { 'class_names': [] };
			
			// Gray out days off
			if( typeof bookacti.booking_system[ booking_system_id ][ 'days_off' ] !== 'undefined' ) {
				var days_off = bookacti.booking_system[ booking_system_id ][ 'days_off' ];
				var day_date = moment.utc( info.date );
				$j.each( days_off, function ( i, day_off ) {
					var day_off_from = moment.utc( day_off.from + ' 00:00:00' );
					var day_off_to = moment.utc( day_off.to + ' 23:59:59' );
					if( day_date.isBetween( day_off_from, day_off_to, 'second', '[]' ) ) {
						return_object.class_names.push( 'fc-day-disabled' );
						return false; // break
					}
				});
			}
			
			booking_system.trigger( 'bookacti_calendar_day_cell_class_names', [ return_object, info ] );
			
			return return_object.class_names;
		},
		
		
		/**
		 * Called after the calendar’s date range has been initially set or changed in some way and the DOM has been updated.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {Date} start                A Date for the beginning of the range the calendar needs events for.
		 *  @type {Date} end                  A Date for the end of the range the calendar needs events for. Note: This value is exclusive.
		 *  @type {String} startStr           An ISO8601 string representation of the start date. Will have a time zone offset according to the calendar’s timeZone like 2018-09-01T12:30:00-05:00.
		 *  @type {String} endStr             Just like startStr, but for the end date.
		 *  @type {String} timeZone           The exact value of the calendar’s timeZone setting.
		 *  @type {FullCalendar.ViewApi} view The current View Object.
		 * }
		 */
		datesSet: function( info ){ 
			// Maybe fetch the events on the view (if not already)
			if( bookacti.booking_system[ booking_system_id ][ 'load_events' ] === true ) {
				var interval = { 
					'start': moment.utc( moment.utc( info.view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
					'end': moment.utc( moment.utc( info.view.currentEnd ).clone().subtract( 1, 'days' ).locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
				};
				var new_interval = bookacti_get_interval_of_events( booking_system, interval );
				if( ! $j.isEmptyObject( new_interval ) ) { bookacti_get_booking_system_data_by_interval( booking_system, new_interval ); }
			}
			
			booking_system.trigger( 'bookacti_calendar_view_render', [ info ] );
		},
		
		
		/**
		 * Called right after the element has been added to the DOM.
		 * If the event data changes, this is NOT called again.
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event
		 *  @type {String} timeText
		 *  @type {Boolean} isStart
		 *  @type {Boolean} isEnd
		 *  @type {Boolean} isMirror
		 *  @type {Boolean} isPast
		 *  @type {Boolean} isFuture
		 *  @type {Boolean} isToday
		 *  @type {HTMLElement} el
		 *  @type {FullCalendar.ViewApi} view The current View Object.
		 * }
		 */
		eventDidMount: function( info ) {
			// Directly return if the event is resizing or dragging to avoid overload
			if( info.isMirror ) { return; }
			
			// Add data to the event element
			var event_start_formatted = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted   = moment.utc( info.event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );

			// Add some info to the event
			$j( info.el ).data( 'event-id',         info.event.groupId );
			$j( info.el ).attr( 'data-event-id',    info.event.groupId );
			$j( info.el ).data( 'event-start',      event_start_formatted );
			$j( info.el ).attr( 'data-event-start', event_start_formatted );
			$j( info.el ).data( 'event-end',        event_end_formatted );
			$j( info.el ).attr( 'data-event-end',   event_end_formatted );			
			
			var event_data = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ info.event.groupId ];
			if( typeof event_data.activity_id !== 'undefined' ) {
				$j( info.el ).data( 'activity-id', event_data.activity_id );
				$j( info.el ).attr( 'data-activity-id', event_data.activity_id );
			}
			
			booking_system.trigger( 'bookacti_calendar_event_did_mount', [ info ] );
		},
		
		
		/**
		 * Add classes to the event
		 * It is called every time the associated event data changes
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event
		 *  @type {String} timeText
		 *  @type {Boolean} isStart
		 *  @type {Boolean} isEnd
		 *  @type {Boolean} isMirror
		 *  @type {Boolean} isPast
		 *  @type {Boolean} isFuture
		 *  @type {Boolean} isToday
		 *  @type {FullCalendar.ViewApi} view The current View Object.
		 * }
		 * @returns {Array}
		 */
		eventClassNames: function( info ) {
			var return_object = { 'class_names': [] };
			
			// Directly return if the event is hidden, or resizing or dragging to avoid overload
			if( info.isMirror || info.event.display === 'none' ) { return return_object; }
			
			// Display element as picked if they actually are
			var event_start = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
				var picked_event_start = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				if( picked_event.id == info.event.groupId && event_start === picked_event_start ) { 
					return_object.class_names.push( 'bookacti-picked-event' );
				}
			});
			
			// Add a class if the current user has booked this event
			var event_start_formatted = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ info.event.groupId ] !== 'undefined' ) {
				if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ info.event.groupId ][ event_start_formatted ] !== 'undefined' ) {
					var current_user_bookings = parseInt( bookacti.booking_system[ booking_system_id ][ 'bookings' ][ info.event.groupId ][ event_start_formatted ][ 'current_user_bookings' ] );
					if( current_user_bookings ) { return_object.class_names.push( 'bookacti-event-booked-by-current-user' ); }
				}
			}
			
			// If the event or its group is not available, disable the event
			var bookings_only = bookacti.booking_system[ booking_system_id ][ 'bookings_only' ] == 1 ? true : false;
			if( ! bookings_only ) {
				var is_available = bookacti_is_event_available( booking_system, info.event );
				if( ! is_available ) { return_object.class_names.push( 'bookacti-event-unavailable' ); }
			}
			
			// Add classes to the event according to its expected size
			if( info.view.type.indexOf( 'dayGrid' ) > -1 || info.view.type.indexOf( 'timeGrid' ) > -1 ) { 
				var event_size_classes = bookacti_fc_get_event_size_classes( booking_system, info.event, info.view );
				if( event_size_classes.length ) {
					return_object.class_names = $j.merge( return_object.class_names, event_size_classes );
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_event_class_names', [ return_object, info ] );
			
			return return_object.class_names;
		},
		
		
		/**
		 * Add HTML elements in the event
		 * It is called every time the associated event data changes
		 * @since 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event
		 *  @type {String} timeText
		 *  @type {Boolean} isStart
		 *  @type {Boolean} isEnd
		 *  @type {Boolean} isMirror
		 *  @type {Boolean} isPast
		 *  @type {Boolean} isFuture
		 *  @type {Boolean} isToday
		 *  @type {FullCalendar.ViewApi} view The current View Object.
		 * }
		 * @returns {Array}
		 */
		eventContent: function( info ) {
			var return_object = { 'domNodes': [] };
			
			// Directly return if the event is hidden to avoid overload
			if( info.event.display === 'none' ) { return return_object; }
			
			// Display start and end time in spans
			var time_format	= 'LT';
			if( info.view.type.indexOf( 'timeGrid' ) > -1 ) {
				// Remove trailing AM/PM in Time Grid views
				var lt_format = moment.localeData().longDateFormat( 'LT' );
				time_format = lt_format.replace( /[aA]/g, '' );
			}
			if( bookacti_localized.calendar_localization === 'wp_settings' ) {
				time_format = bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
			}
			var time_div = $j( '<div></div>', { 
				'class': 'fc-event-time', 
				'html': '<span class="bookacti-event-time-start">' + moment.utc( info.event.start ).format( time_format ) + '</span>' 
				      + '<span class="bookacti-event-time-separator"> - </span>' 
					  + '<span class="bookacti-event-time-end">' + moment.utc( info.event.end ).format( time_format ) + '</span>'
			} );
			return_object.domNodes.push( time_div[ 0 ] );
			
			// Display event title
			var title_div = $j( '<div></div>', { 'class': 'fc-event-title-container', 'html': '<div class="fc-event-title">' + info.event.title + '</div>' } );
			return_object.domNodes.push( title_div[ 0 ] );
			
			// Add availability div
			var bookings_only = bookacti.booking_system[ booking_system_id ][ 'bookings_only' ] == 1 ? true : false;
			var avail_div = bookings_only ? bookacti_get_event_number_of_bookings_div( booking_system, info.event ) : bookacti_get_event_availability_div( booking_system, info.event );
			if( avail_div ) { return_object.domNodes.push( avail_div[ 0 ] ); }
			
			booking_system.trigger( 'bookacti_calendar_event_content', [ return_object, info ] );

			return return_object;
		},
		
		
		/**
		 * Triggered when the user clicks an event.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event The associated Event Object.
		 *  @type {HTMLElement} el              The HTML element for this event.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventClick: function( info ) {
			var trigger = { 'click': true };
			
			// Don't pick the event if it is not available
			if( $j( info.el ).hasClass( 'bookacti-event-unavailable' ) ) { trigger.click = false; }
			
			// Allow plugins to prevent the event click
			booking_system.trigger( 'bookacti_trigger_event_click', [ trigger, info ] );
			
			if( trigger.click ) { bookacti_event_click( booking_system, info.event ); }
		},
		
		
		/**
		 * Triggered when the user mouses over an event. Similar to the native mouseenter.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event The associated Event Object.
		 *  @type {HTMLElement} el              The HTML element for this event.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventMouseEnter: function( info ) {
			booking_system.trigger( 'bookacti_calendar_event_mouse_enter', [ info ] );
		},
		
		
		/**
		 * Triggered when the user mouses out of an event. Similar to the native mouseleave.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event The associated Event Object.
		 *  @type {HTMLElement} el              The HTML element for this event.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventMouseLeave: function( info ) {
			booking_system.trigger( 'bookacti_calendar_event_mouse_leave', [ info ] );
		}
	};
	
	if( bookacti_localized.calendar_localization === 'wp_settings' ) {
		var fc_time_format_obj    = bookacti_convert_php_datetime_format_to_fc_date_formatting_object( bookacti_localized.wp_time_format );
		init_data.firstDay        = bookacti_localized.wp_start_of_week;
		init_data.slotLabelFormat = fc_time_format_obj;
		init_data.eventTimeFormat = fc_time_format_obj;
	}
	
	// Let third-party plugin change initial calendar data
	booking_system.trigger( 'bookacti_calendar_init_data', [ init_data ] );
	
	// Generate the calendar
	bookacti.fc_calendar[ booking_system_id ] = new FullCalendar.Calendar( booking_system.find( '.bookacti-calendar:first' )[ 0 ], init_data );
	bookacti.fc_calendar[ booking_system_id ].render();
	
	var view = bookacti.fc_calendar[ booking_system_id ].view;
	var interval = {
		'start': moment.utc( moment.utc( view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
		'end': moment.utc( moment.utc( view.currentEnd ).clone().subtract( 1, 'days' ).locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
	};
	
	// Make sure the event interval fit the view
	var is_view_larger_than_interval = false;
	if( typeof bookacti.booking_system[ booking_system_id ][ 'events_interval' ] !== 'undefined' ) {
		var event_interval_start = moment.utc( bookacti.booking_system[ booking_system_id ][ 'events_interval' ][ 'start' ] );
		var event_interval_end   = moment.utc( bookacti.booking_system[ booking_system_id ][ 'events_interval' ][ 'end' ] );
		if( event_interval_start.isAfter( interval.start ) || event_interval_end.isBefore( interval.end ) ) {
			is_view_larger_than_interval = true;
		}
	}
	
	// Load events on calendar
	if( ( ! reload_events || is_view_larger_than_interval ) && typeof bookacti.booking_system[ booking_system_id ][ 'events' ] !== 'undefined' ) {
		bookacti_fc_add_events( booking_system, bookacti.booking_system[ booking_system_id ][ 'events' ] );
	}
	if( reload_events || is_view_larger_than_interval ) {
		// Fetch events from database
		var new_interval = bookacti_get_interval_of_events( booking_system, interval );
		if( ! $j.isEmptyObject( new_interval ) ) { bookacti_get_booking_system_data_by_interval( booking_system, new_interval ); }
	}
	
	bookacti.booking_system[ booking_system_id ][ 'load_events' ] = true;
	
	// Go to the first picked events
	var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
	if( ! $j.isEmptyObject( bookacti.booking_system[ booking_system_id ][ 'picked_events' ] ) ) {
		bookacti.fc_calendar[ booking_system_id ].gotoDate( moment.utc( picked_events[ 0 ][ 'start' ] ) );
	}
	
	// Add a class to the calendar according to its width
	var calendar_width_classes = bookacti_localized.calendar_width_classes;
	var calendar_width = booking_system.find( '.bookacti-calendar:first' ).width();
	$j.each( calendar_width_classes, function( threshold, css_class ) {
		if( calendar_width <= threshold ) {
			booking_system.find( '.bookacti-calendar:first' ).addClass( css_class );
			return false; // break
		}
	});
	
	booking_system.trigger( 'bookacti_calendar_after_set_up' );
}


/**
 * Get FC events by groupId
 * @since 1.15.0
 * @param {HTMLElement} booking_system
 * @param {Int} groupId
 * @returns {Array}
 */
function bookacti_fc_get_events_by_groupId( booking_system, groupId ) {
	var booking_system_id = booking_system.attr( 'id' );
	var fc_events = $j.grep( bookacti.fc_calendar[ booking_system_id ].getEvents(), function( fc_event ) {
		return fc_event.groupId == groupId;
	});
	return fc_events;
}


/**
 * Fill calendar with events
 * @since 1.15.0 (was bookacti_display_events_on_calendar)
 * @version 1.15.1
 * @param {HTMLElement} booking_system
 * @param {Object} events
 */
function bookacti_fc_add_events( booking_system, events ) {
	if( ! events.length ) { return; }
	
	// Convert events for FullCalendar
	var new_events = [];
	$j.each( events, function( i, event ) {
		var new_event = $j.extend( true, {}, event );
		new_event.groupId = parseInt( event.id );
		new_event.id = event.id + '_' + event.start;
		new_events.push( new_event );
	});
	
	var booking_system_id = booking_system.attr( 'id' );
	var source = { 'events': new_events, 'editable': booking_system_id === 'bookacti-template-calendar' };
	booking_system.trigger( 'bookacti_fc_events', [ source ] );
	
	bookacti.fc_calendar[ booking_system_id ].addEventSource( source );
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
 * @version 1.15.0
 * @param {HTMLElement} booking_system
 */
function bookacti_unpick_all_events_on_calendar( booking_system ) {
	booking_system.find( '.bookacti-picked-event' ).removeClass( 'bookacti-picked-event' );
	booking_system.trigger( 'bookacti_unpick_all_events_on_calendar' );
}


/**
 * Get CSS classes accoding to the event expected size on timeGrid or dayGrid view
 * @since 1.15.0 (was bookacti_add_class_according_to_event_size)
 * @param {HTMLElement} booking_system
 * @param {FullCalendar.EventApi} fc_event
 * @param {FullCalendar.ViewApi} view
 */
function bookacti_fc_get_event_size_classes( booking_system, fc_event, view ) {
	var classes = [];
	if( view.type.indexOf( 'timeGrid' ) < 0 && view.type.indexOf( 'dayGrid' ) < 0 ) { return classes; }
	
	var custom_size = {
		'tiny_height':  typeof bookacti_localized.event_tiny_height !== 'undefined' ? parseInt( bookacti_localized.event_tiny_height ) : 32,
		'small_height': typeof bookacti_localized.event_small_height !== 'undefined' ? parseInt( bookacti_localized.event_small_height ) : 75,
		'narrow_width': typeof bookacti_localized.event_narrow_width !== 'undefined' ? parseInt( bookacti_localized.event_narrow_width ) : 70,
		'wide_width':   typeof bookacti_localized.event_wide_width !== 'undefined' ? parseInt( bookacti_localized.event_wide_width ) : 250
	};
	
	if( view.type.indexOf( 'timeGrid' ) > -1 ) {
		// Compute expected height
		var booking_system_id = booking_system.attr( 'id' );
		var event_min_height  = bookacti.fc_calendar[ booking_system_id ].getOption( 'eventMinHeight' );
		var slot_duration     = bookacti.fc_calendar[ booking_system_id ].getOption( 'slotDuration' );
		if( typeof event_min_height === 'undefined' ) { event_min_height = 0; }
		if( typeof slot_duration === 'undefined' )    { slot_duration = '00:30'; }
		
		var slot_minutes  = ( parseInt( slot_duration.substr( 0, 2 ) ) * 60 ) + parseInt( slot_duration.substr( -2 ) );
		var event_minutes = parseInt( moment.duration( moment.utc( fc_event.end ).diff( moment.utc( fc_event.start ) ) ).asMinutes() );
		var slot_height   = booking_system.find( '.fc-timegrid-slot' ).length ? booking_system.find( '.fc-timegrid-slot' ).outerHeight() : 0;
		
		// If the slot is not rendered, compute its expected height from the line-height
		if( ! slot_height ) {
			var line_height = booking_system.css( 'line-height' ).replace( 'px', '' );
			line_height     = $j.isNumeric( line_height ) ? parseFloat( line_height ) : 0;
			if( ! line_height ) {
				var font_size = booking_system.css( 'font-size' ).replace( 'px', '' );
				line_height   = $j.isNumeric( font_size ) ? parseFloat( font_size ) * 1.5 : 0; // Usually, line-height = font-size * 1.5
			}
			if( line_height ) { slot_height = line_height; }
			else { slot_height = 20; } // Use 20px by default
		}
		
		var expected_height = Math.max( parseInt( event_min_height ), ( slot_height / slot_minutes ) * event_minutes );
		
		     if( expected_height <= custom_size.tiny_height )  { classes.push( 'bookacti-tiny-event' ); }
		else if( expected_height <= custom_size.small_height ) { classes.push( 'bookacti-small-event' ); }
	}
	
	// Compute expected width
	var column_width = booking_system.find( '.fc-col-header-cell.fc-day' ).innerWidth();
	
	// Withdraw the margins (0 2.5% 0 2px)
	var expected_width = column_width - ( column_width * 0.025 ) - 2;
	
	     if( expected_width >= custom_size.wide_width )   { classes.push( 'bookacti-wide-event' ); }
	else if( expected_width <= custom_size.narrow_width ) { classes.push( 'bookacti-narrow-event' ); }
	
	return classes;
}


/**
 * Enter loading state and prevent user from doing anything else
 * @version 1.15.0
 * @param {HTMLElement} calendar
 */
function bookacti_enter_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).attr( 'disabled', true );
	calendar.find( '.fc-view-harness' ).append( '<div class="bookacti-loading-overlay"><div class="bookacti-loading-overlay-content">' + bookacti_get_loading_html() + '</div></div>' );
}


/**
 * Exit loading state and allow user to keep editing templates
 * @version 1.15.0
 * @param {HTMLElement} calendar
 */
function bookacti_exit_calendar_loading_state( calendar ) {
	calendar.find( '.fc-toolbar button' ).attr( 'disabled', false );
	calendar.find( '.bookacti-loading-overlay' ).remove();
}