$j( document ).ready( function() { 
	// Check if the calendar exist before anything
	if( ! $j( '#bookacti-template-container' ).length ) { return; }
	
	// Init calendar editor specific globals
	bookacti.selected_template = parseInt( $j( '#bookacti-template-picker' ).val() ) || 0;
	bookacti.hidden_activities = [];
	bookacti.selected_category = 'new';
	bookacti.load_events       = false;
	
	// Init globals
	bookacti.booking_system[ 'bookacti-template-calendar' ]                            = {};
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'calendars' ]             = bookacti.selected_template ? [ bookacti.selected_template ] : [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ]              = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]         = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ]           = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ]       = {};
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ]           = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ]       = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ] = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'start' ]                 = '1970-02-01 00:00:00';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'end' ]                   = '2037-12-31 23:59:59';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ]          = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]         = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]       = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]         = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]        = 0;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'method' ]                = 'calendar';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events' ]           = true;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events_bookable' ]  = true;
	
	// Make activities draggable and sortable (needs to be done only once, even if activities are added afterwards to the DOM)
	bookacti_make_activities_draggable();
	bookacti_make_activities_sortable();
	
	// Initialize groups
	bookacti_init_groups_of_events();

	// Show and Hide activities
	bookacti_init_show_hide_activities_switch();

	// Duplicate events feedbacks
	bookacti_init_event_duplication_feedbacks();

	// DIALOGS
	// Init the Dialogs
	bookacti_init_template_dialogs();

	// Launch dialogs when...
	bookacti_bind_template_dialogs();

	// Change default template on change in the select box
	$j( 'body' ).on( 'change', '#bookacti-template-picker', function(){
		bookacti_switch_template( $j( this ).val() );
	});

	// Load the template
	if( bookacti.selected_template ) {
		bookacti_switch_template( bookacti.selected_template );
	}
	
	/**
	 * Do not init calendar editor booking system automatically
	 * @since 1.15.0
	 * @param {Event} e
	 * @param {Object} load
	 * @param {Object} attributes
	 */
	$j( 'body' ).on( 'bookacti_init_booking_sytem', '#bookacti-template-calendar', function( e, load, attributes ) {
		load.load = false;
	});

	/**
	 * Resize draggable external events helper to the original event size
	 * @version 1.8.0
	 * @param {Event} e
	 * @param {Object} ui
	 */
	$j( 'body' ).on( 'dragstart', '#bookacti-template-activities-container .fc-event', function( e, ui ) {
		ui.helper.css( 'maxWidth', $j( this ).width() );
	});
	
	
	/**
	 * Clear events on calendar editor - on bookacti_clear_events
	 * @since 1.12.0
	 * @param {Event} e
	 * @param {String} booking_method
	 */
	$j( 'body' ).on( 'bookacti_clear_events', '#bookacti-template-calendar', function( e, booking_method ) {
		bookacti_clear_events_on_calendar_editor();
	});
	

	/**
	 * If an error occurs, stop loading and allow every interactions
	 * @version 1.7.6
	 * @param {Event|String} errorMsg
	 * @param {String} url
	 * @param {Int} lineNumber
	 * @param {Int} column
	 * @param {Error} errorObj
	 */
	window.onerror = function ( errorMsg, url, lineNumber, column, errorObj ) {
		$j( '#bookacti-fatal-error' ).show();
	};
	
	/**
	 * Exit template loading (forced) - on click on button
	 * @version 1.15.0
	 */
	$j( 'body' ).on( 'click', '#bookacti-exit-loading', function(){
		bookacti_stop_template_loading( true );
		bookacti.load_events = true;
	});
});


/**
 * Initialize and display the template calendar
 * @version 1.15.5
 */
function bookacti_load_template_calendar() {
	var booking_system = $j( '#bookacti-template-calendar' );

	// Get calendar settings
	var availability_period = bookacti_get_availability_period( booking_system );
	var display_data        = typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ] !== 'undefined' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ] : {};
	var event_min_height    = typeof bookacti_localized.event_tiny_height !== 'undefined' ? parseInt( bookacti_localized.event_tiny_height ) : 32;
	var slot_min_time       = typeof display_data.slotMinTime !== 'undefined' ? display_data.slotMinTime : '00:00';
	var slot_max_time       = typeof display_data.slotMaxTime !== 'undefined' ? display_data.slotMaxTime : '24:00';
	var snap_duration       = typeof display_data.snapDuration !== 'undefined' ? display_data.snapDuration : '00:05';
	var next_day_threshold  = moment.utc( '1970-02-01 ' + slot_min_time ).add( 1, 'minutes' ).format( 'HH:mm' ); // One minute after slot_min_time
	
	// See https://fullcalendar.io/docs/
	var init_data = {
		locale:                  bookacti_localized.fullcalendar_locale,
		timeZone:                bookacti_localized.fullcalendar_timezone,
		now:                     new Date( bookacti_localized.current_time.substr( 0, 10 ) ),
		initialView:             'timeGridWeek',
		eventShortHeight:        0,
		eventMinHeight:          event_min_height,
		nextDayThreshold:        next_day_threshold,
		slotMinTime:             slot_min_time,
		slotMaxTime:             slot_max_time,
		slotDuration:            '00:30',
		snapDuration:            snap_duration,
		scrollTime:              '00:00',
		height:                  'auto',
		contentHeight:           'auto',
		nowIndicator:            false,
		weekNumbers:	         false,
		navLinks:		         true,
		slotEventOverlap:        false,
		dayMaxEvents:            false,
		moreLinkClick:           'popover',
		eventDisplay:            'block',
		allDaySlot:              false,
		defaultAllDay:           false,
		fixedWeekCount:          false,
		showNonCurrentDates:     true,
		eventResizableFromStart: false,
		rerenderDelay:           100,
		editable:                true,
		droppable:               true,
		dropAccept:              '.fc-event, .bookacti-activity-draggable',
		eventDurationEditable:   true,
		dragRevertDuration:      0,
		
		validRange: {
            start: availability_period.start ? moment.utc( availability_period.start.substr( 0, 10 ) ).format( 'YYYY-MM-DD' ) : '1970-02-01',
            end:   availability_period.end ? moment.utc( availability_period.end.substr( 0, 10 ) ).add( 1, 'days' ).format( 'YYYY-MM-DD' ) : '2038-01-01'
        },
		
		customButtons: {
			goTo: {
				text: bookacti_localized.go_to_button,
				click: function() {
					if( ! $j( '.bookacti-go-to-datepicker' ).length ) {
						$j( '.fc-goTo-button' ).after( '<input type="date" class="bookacti-go-to-datepicker" min="1970-01-01"/>' );
						$j( '.bookacti-go-to-datepicker' ).hide();
					}
					$j( '.bookacti-go-to-datepicker' ).toggle( 200 );
				}
			}
		},
		
		headerToolbar: {
			start: 'prevYear,prev,next,nextYear today goTo',
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
		 * @version 1.15.4
		 * @param {Object} info {
		 *  @type {FullCalendar.ViewApi} view
		 *  @type {HTMLElement} el
		 * }
		 * @returns {Array}
		 */
		viewClassNames: function( info ) {
			var return_object = { 'class_names': [] };
			
			// Always enable "Today" button, except on today's view
			if( booking_system.find( '.fc-today-button' ).length && ! booking_system.find( '.fc-day-today' ).length ) {
				if( ! moment().isBetween( info.view.currentStart, info.view.currentEnd, 'day', '[)' ) ) {
					booking_system.find( '.fc-today-button' ).attr( 'disabled', false );
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_view_class_names', [ return_object, info ] );
			
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
			var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
			if( typeof template_data !== 'undefined' ) {
				if( typeof template_data.settings !== 'undefined' ) {
					if( typeof template_data.settings.days_off !== 'undefined' ) {
						var day_date = moment.utc( info.date );
						$j.each( template_data.settings.days_off, function ( i, day_off ) {
							var day_off_from = moment.utc( day_off.from + ' 00:00:00' );
							var day_off_to = moment.utc( day_off.to + ' 23:59:59' );
							if( day_date.isBetween( day_off_from, day_off_to, 'second', '[]' ) ) {
								return_object.class_names.push( 'fc-day-disabled' );
								return false; // break
							}
						});
					}
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_day_header_class_names', [ return_object, info ] );
			
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
			var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
			if( typeof template_data !== 'undefined' ) {
				if( typeof template_data.settings !== 'undefined' ) {
					if( typeof template_data.settings.days_off !== 'undefined' ) {
						var day_date = moment.utc( info.date );
						$j.each( template_data.settings.days_off, function ( i, day_off ) {
							var day_off_from = moment.utc( day_off.from + ' 00:00:00' );
							var day_off_to = moment.utc( day_off.to + ' 23:59:59' );
							if( day_date.isBetween( day_off_from, day_off_to, 'second', '[]' ) ) {
								return_object.class_names.push( 'fc-day-disabled' );
								return false; // break
							}
						});
					}
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_day_cell_class_names', [ return_object, info ] );
			
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
		datesSet: function( info ) {
			// Maybe fetch the events on the view (if not already)
			if( bookacti.load_events === true ) { 
				var interval = { 
					'start': moment.utc( moment.utc( info.view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
					'end': moment.utc( moment.utc( info.view.currentEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
				};
				var new_interval = bookacti_get_interval_of_events( booking_system, interval );
				if( ! $j.isEmptyObject( new_interval ) ) { bookacti_get_calendar_editor_data_by_interval( new_interval ); }
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_view_render', [ info ] );
		},
		
		
		/**
		 * Called right after the element has been added to the DOM.
		 * If the event data changes, this is NOT called again.
		 * @since 1.15.0
		 * @version 1.15.1
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
			var event_id    = info.event.groupId;
			var event_data  = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ];
			var activity_id = 0;
			if( typeof event_data !== 'undefined' ) {
				activity_id = parseInt( event_data.activity_id );
			} else if( typeof info.event.extendedProps.activity_id !== 'undefined' ) {
				activity_id = parseInt( info.event.extendedProps.activity_id );
			}
			
			var event_start_formatted = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted   = moment.utc( info.event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			
			// Add data to the event element
			$j( info.el ).data( 'event-id', event_id ? event_id : '' ).attr( 'data-event-id', event_id ? event_id : '' );
			$j( info.el ).data( 'event-start', event_start_formatted ).attr( 'data-event-start', event_start_formatted );
			$j( info.el ).data( 'event-end', event_end_formatted ).attr( 'data-event-end', event_end_formatted );			
			$j( info.el ).data( 'activity-id', activity_id ? activity_id : '' ).attr( 'data-activity-id', activity_id ? activity_id : '' );

			booking_system.trigger( 'bookacti_calendar_editor_event_did_mount', [ info ] );
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
			
			// Check if the activity is hidden
			var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ info.event.groupId ];
			var activity_id = typeof event_data !== 'undefined' ? parseInt( event_data.activity_id ) : 0;
			if( bookacti.hidden_activities && activity_id ) {
				if( $j.inArray( activity_id, bookacti.hidden_activities ) >= 0 ) {
					return_object.class_names.push( 'bookacti-event-hidden' );
				}
			}
			
			// Display element as picked or selected if they actually are
			var event_start = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ], function( i, picked_event ) {
				var picked_event_start = moment.utc( picked_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				if( picked_event.id == info.event.groupId && event_start === picked_event_start ) { 
					return_object.class_names.push( 'bookacti-picked-event' );
				}
			});
			
			// Make sure selected events appears as selected
			$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ) {
				var selected_event_start = moment.utc( selected_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				if( selected_event.id == info.event.groupId && event_start === selected_event_start ) { 
					return_object.class_names.push( 'bookacti-selected-event' );
				}
			});
			
			// Add classes to the event according to its expected size
			if( info.view.type.indexOf( 'dayGrid' ) > -1 || info.view.type.indexOf( 'timeGrid' ) > -1 ) { 
				var event_size_classes = bookacti_fc_get_event_size_classes( booking_system, info.event, info.view );
				if( event_size_classes.length ) {
					return_object.class_names = $j.merge( return_object.class_names, event_size_classes );
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_event_class_names', [ return_object, info ] );
			
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
			
			// Directly return if the event is hidden
			if( info.event.display === 'none' ) { return return_object; }
			
			// Display a dot in dayGrid views
			if( info.view.type.indexOf( 'dayGrid' ) > -1 ) {
				var multi_day     = moment.utc( info.event.start ).locale( 'en' ).format( 'YYYY-MM-DD' ) !== moment.utc( info.event.end ).locale( 'en' ).format( 'YYYY-MM-DD' );
				var event_display = bookacti.fc_calendar[ 'bookacti-template-calendar' ].getOption( 'eventDisplay' );
				if( info.event.display === 'list-item' || ( info.event.display === 'auto' && ( event_display === 'list-item' || ( event_display === 'auto' && ! info.event.allDay && ! multi_day ) ) ) ) {
					var dot_div = $j( '<div></div>', { 'class': 'fc-daygrid-event-dot' } );
					return_object.domNodes.push( dot_div[ 0 ] );
				}
			}
			
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
			
			// Directly return if the event is resizing or dragging or hidden to avoid overload
			if( info.isMirror || info.event.display === 'none' ) { return return_object; }
			
			// Add availability div
			var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ info.event.groupId ];
			var availability = typeof event_data !== 'undefined' ? parseInt( event_data.availability ) : 0;
			var event_bookings = bookacti_get_event_number_of_bookings( booking_system, info.event );
			if( event_bookings != null ) {
				var availability_classes = '';
				if( availability === 0 ) { availability_classes += 'bookacti-no-availability'; }
				else {
					availability_classes += event_bookings > 0 ? ' bookacti-booked' : ' bookacti-not-booked';
					if( event_bookings >= availability ) { availability_classes += ' bookacti-full'; } 
				}

				var avail_div   = $j( '<div></div>', { 'class': 'bookacti-availability-container' } );
				var places_span = $j( '<div></div>', { 'class': 'bookacti-available-places ' + availability_classes } );
				var booked_span = $j( '<span></span>', { 'class': 'bookacti-bookings', 'html': event_bookings } );
				var total_span  = $j( '<span></span>', { 'class': 'bookacti-total-availability', 'html': availability } );
				
				places_span.append( booked_span );
				places_span.append( total_span );
				avail_div.append( places_span );
				
				return_object.domNodes.push( avail_div[ 0 ] );
			}
			
			
			// Add event actions div
			// Init var
			var event_actions_div = $j( '<div></div>', { 'class': 'bookacti-event-actions' } );
			var event_actions = { 'actions': [] };
			
			// EDIT ACTION
			var edit_div = $j( '<div></div>', { 'class': 'bookacti-event-action bookacti-event-action-edit' } );
			var edit_button = $j( '<span></span>', {
				'type': 'checkbox',
				'class': 'dashicons dashicons-admin-generic bookacti-event-action-edit-button'
			});
			edit_div.prepend( edit_button );
			event_actions.actions.push( edit_div );
			
			// SELECT ACTION
			// Check if the event is selected
			var event_start_date = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' );
			var is_selected = false;
			$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
				if( selected_event.id == info.event.groupId 
				&&  selected_event.start.substr( 0, 10 ) === event_start_date ) {
					is_selected = true;
					return false; // break the loop
				}
			});

			var select_div = $j( '<div></div>', { 'class': 'bookacti-event-action bookacti-event-action-select' });
			var select_checkbox = $j( '<input/>', {
				'type': 'checkbox',
				'class': 'bookacti-event-action-select-checkbox',
				'value': '0',
				'checked': is_selected
			});
			select_div.append( select_checkbox );
			event_actions.actions.push( select_div );
			
			// Allow third party to edit the list of event actions
			booking_system.trigger( 'bookacti_calendar_editor_event_actions', [ event_actions, info ] );
			
			// Fill the event actions array
			if( event_actions.actions.length ) {
				$j.each( event_actions.actions, function( i, event_action ) {
					event_actions_div.append( event_action );
				});

				// Append the event actions list to the event
				return_object.domNodes.push( event_actions_div[ 0 ] );
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_event_content', [ return_object, info ] );

			return return_object;
		},
		
		
		/**
		 * Exact programmatic control over where an event can be dropped
		 * @since 1.13.0
		 * @version 1.15.0
		 * @param {object} drop_info {
		 *  @type {Boolean} allDay  true or false whether the event was dropped on one of the all-day cells.
		 *  @type {Date} end        Date. The end of where the draggable event was dropped.
		 *  @type {String} endStr   The ISO8601 string representation of the end of where the draggable event was dropped.
		 *  @type {Date} start      Date. The beginning of where the draggable event was dropped.
		 *  @type {String} startStr The ISO8601 string representation of the start of where the draggable event was dropped.
		 * }
		 * @param {FullCalendar.EventApi} dragged_event
		 * @returns {Boolean}
		 */
		eventAllow: function( drop_info, dragged_event ) {
			var allow_drop = { 'allow': true };
			
			// Do not allow to drop events on days off
			var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
			if( typeof template_data !== 'undefined' ) {
				if( typeof template_data.settings !== 'undefined' ) {
					if( typeof template_data.settings.days_off !== 'undefined' ) {
						$j.each( template_data.settings.days_off, function ( i, day_off ) {
							var day_off_from = moment.utc( day_off.from + ' 00:00:00' );
							var day_off_to = moment.utc( day_off.to + ' 23:59:59' );
							if( moment.utc( drop_info.start ).isBetween( day_off_from, day_off_to, 'second', '[]' ) ) { 
								allow_drop.allow = false;
								return false; // break
							}
						});
					}
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_allow_drop', [ allow_drop.allow, drop_info, dragged_event ] );
			
			return allow_drop.allow;
		},
		
		
		/**
		 * Called when an external draggable element with associated event data was dropped onto the calendar. Or an event from another calendar.
		 * This callback is fired before the eventAdd callback is fired.
		 * @version 1.15.5
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event           An Event object containing the newly created/received event.
		 *  @type {FullCalendar.EventApi[]} relatedEvents An array of other related Event Objects that have also been received. an event might have other recurring event instances or might be linked to other events with the same groupId
		 *  @type {Function} revert                       A function that can be called to reverse this action
		 *  @type {HTMLElement} draggedEl                 The HTML element that was being received.
		 *  @type {FullCalendar.ViewApi} view             The current View Object.
		 * }
		 */
		eventReceive: function( info ) {
			if( typeof info.event.extendedProps.activity_id === 'undefined' ) { info.revert(); return; }
			
			var activity_id   = parseInt( info.event.extendedProps.activity_id );
			var activity_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ];		
			
			// If the activity was not found, return false
			if( ! activity_data ) { info.revert(); return; }
			
			// If the event is dropped on a non-timeGrid view, make it begins at the slotMinTime
			var new_event_start = moment.utc( info.event.start );
			if( info.view.type.substr( 0, 8 ) !== 'timeGrid' ) {
				var slot_min_time = bookacti.fc_calendar[ 'bookacti-template-calendar' ].getOption( 'slotMinTime' );
				new_event_start.set( { 'hours': slot_min_time.substr( 0, 2 ), 'minutes': slot_min_time.substr( 3, 2 ), 'seconds': 0 } );
			}
			
			// Calculate the end datetime thanks to start datetime and duration
			var activity_duration = activity_data.duration ? activity_data.duration : '000.01:00:00';
			var new_event_end = new_event_start.clone();
			new_event_end.add( moment.duration( activity_duration ) );
			
			// Set the new event dates
			info.event.setDates( new_event_start.toDate(), new_event_end.toDate() );
			
			var event_start_formatted = moment.utc( info.event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted = moment.utc( info.event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			
			var data = { 
				'action': 'bookactiInsertEvent', 
				'activity_id': activity_id, 
				'template_id': bookacti.selected_template, 
				'title': activity_data[ 'multilingual_title' ], 
				'start': event_start_formatted, 
				'end': event_end_formatted,
				'availability': activity_data[ 'availability' ],
				'nonce': $j( '#bookacti-edit-template-nonce' ).val()
			};
			
			booking_system.trigger( 'bookacti_calendar_editor_event_before_insert', [ info, data ] );
			
			bookacti_start_template_loading();
			
			$j.ajax({
				url: ajaxurl,
				data: data, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					if( response.status === 'success' ) {
						// Set the generated ID to the event
						info.event.setProp( 'groupId', response.event_id );
						$j( '.fc-event[data-event-id=""][data-activity-id="' + activity_id + '"][data-event-start="' + event_start_formatted + '"]' ).data( 'event-id', response.event_id ).attr( 'data-event-id', response.event_id );
						
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ response.event_id ] = response.event_data;
						
						booking_system.trigger( 'bookacti_calendar_editor_event_inserted', [ info, response, data ] );
						
					} else {
						info.revert();

						// Display error message
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						alert( error_message );
						console.log( error_message );
						console.log( response );
					}
				},
				error: function( e ) {
					info.revert();
					alert( 'AJAX ' + bookacti_localized.error );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},

		
		/**
		 * Triggered when resizing stops and the event has changed in duration.
		 * This callback is fired before the eventChange callback is fired.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event           An Event Object that holds information about the event (date, title, etc) after the resize.
		 *  @type {FullCalendar.EventApi[]} relatedEvents An array of other related Event Objects that were also resized. an event might have other recurring event instances or might be linked to other events with the same groupId
		 *  @type {FullCalendar.EventApi} oldEvent        An Event Object that holds information about the event before the resize.
		 *  @type {Object} endDelta                       A Duration Object that represents the amount of time the event’s end date was moved by.
		 *  @type {Object} startDelta                     A Duration Object that represents the amount of time the event’s start date was moved by.
		 *  @type {Function} revert                       A function that, if called, reverts the event’s start/end date to the values before the drag. This is useful if an ajax call should fail.
		 *  @type {FullCalendar.ViewApi} view             The current View Object.
		 *  @type {HTMLElement} el                        The HTML element that was being resized.
		 *  @type {Event} jsEvent                         The native JavaScript event with low-level information such as click coordinates.
		 * }
		 */
		eventResize: function( info ) {
			bookacti_update_event_dates( info.event, info.oldEvent, info.revert );
		},

		
		/**
		 * Triggered when dragging stops and the event has moved to a different day/time.
		 * This callback is fired before the eventChange callback is fired.
		 * eventDrop does not get called when an external event lands on the calendar. eventReceive is called instead.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event           An Event Object that holds information about the event (date, title, etc) after the drop.
		 *  @type {FullCalendar.EventApi[]} relatedEvents An array of other related Event Objects that were also dropped. an event might have other recurring event instances or might be linked to other events with the same groupId
		 *  @type {FullCalendar.EventApi} oldEvent        An Event Object that holds information about the event before the drop.
		 *  @type {Object} delta                          A Duration Object that represents the amount of time the event was moved by.
		 *  @type {Function} revert                       A function that, if called, reverts the event’s start/end date to the values before the drag. This is useful if an ajax call should fail.
		 *  @type {FullCalendar.ViewApi} view             The current View Object.
		 *  @type {HTMLElement} el                        The HTML element that was dragged.
		 *  @type {Event} jsEvent                         The native JavaScript event with low-level information such as click coordinates.
		 * }
		 */
		eventDrop: function( info ) {
			// The event is duplicated
			if( info.jsEvent.altKey ) {
				info.revert();
				bookacti_duplicate_event( info.event, info.oldEvent );
			}
			// The event is moved
			else {
				bookacti_update_event_dates( info.event, info.oldEvent, info.revert );
			}
		},
		
		
		/**
		 * Triggered when event dragging begins.
		 * @since 1.7.14
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event An Event Object that holds information about the event (date, title, etc) after the drop.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventDragStart: function( info ) {
			// Add a class to all events having this ID
			var elements = $j( '.fc-event[data-event-id="' + info.event.groupId + '"]' );
			elements.addClass( 'bookacti-event-dragged' );
			
			booking_system.trigger( 'bookacti_calendar_editor_drag_start', [ info ] );
		},

		
		/**
		 * Triggered when event dragging stops.
		 * @since 1.7.14
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event An Event Object that holds information about the event (date, title, etc) before the drop.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventDragStop: function( info ) {
			// Remove the class from all events having this ID
			var elements = $j( '.fc-event.bookacti-event-dragged[data-event-id="' + info.event.groupId + '"]' );
			elements.removeClass( 'bookacti-event-dragged' );
			
			booking_system.trigger( 'bookacti_calendar_editor_drag_stop', [ info ] );
		},


		/**
		 * Triggered when event resizing begins.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event An Event Object that holds information about the event (date, title, etc) after the resize.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventResizeStart: function( info ) {
			booking_system.trigger( 'bookacti_calendar_editor_resize_start', [ info ] );
		},


		/**
		 * Triggered when event resizing stops.
		 * This callback is guaranteed to be triggered after the user resizes an event, even if the event doesn’t change in duration. 
		 * It is triggered before the event’s information has been modified (if changed in duration) and before the eventResize callback is triggered.
		 * If you want to get the event’s information after it has changed, use the eventResize callback.
		 * @version 1.15.0
		 * @param {Object} info {
		 *  @type {FullCalendar.EventApi} event An Event Object that holds information about the event (date, title, etc) before the resize.
		 *  @type {Event} jsEvent               The native JavaScript event with low-level information such as click coordinates.
		 *  @type {FullCalendar.ViewApi} view   The current View Object.
		 * }
		 */
		eventResizeStop: function ( info ) {
			booking_system.trigger( 'bookacti_calendar_editor_resize_stop', [ info ] );
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
			// Unpick all the other events
			bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ] = [];
			bookacti_unpick_all_events_on_calendar( booking_system );
			
			// Pick this event
			bookacti_pick_event( booking_system, info.event );
			
			// If the user click on an event action, execute it
			if( $j( info.jsEvent.target ).closest( '.bookacti-event-actions' ).length ) {
				// EDIT ACTION
				if( $j( info.jsEvent.target ).is( '.bookacti-event-action-edit' ) 
				||  $j( info.jsEvent.target ).closest( '.bookacti-event-action-edit' ).length ) {
					// Display the dialog to modify the event
					if( info.event.editable !== false ){
						bookacti_dialog_update_event( info.event );
					}
					
				// SELECT ACTION
				} else if( $j( info.jsEvent.target ).is( '.bookacti-event-action-select' )
						|| $j( info.jsEvent.target ).closest( '.bookacti-event-action-select' ).length ) {
					
					// Format selected events and keep them / remove them from memory
					if( $j( info.el ).find( '.bookacti-event-action-select-checkbox' ).is( ':checked' ) ) {
						bookacti_select_event( info.event );
					
					} else {
						bookacti_unselect_event( info.event );
						$j( info.el ).find( '.bookacti-event-action-select' ).show();
					}
				}
			}
			
			booking_system.trigger( 'bookacti_calendar_editor_event_click', [ info ] );
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
			booking_system.trigger( 'bookacti_calendar_editor_event_mouse_enter', [ info ] );
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
			booking_system.trigger( 'bookacti_calendar_editor_event_mouse_leave', [ info ] );
		},
		
		
		/**
		 * Triggered when event or resource fetching starts/stops.
		 * Triggered with a true argument when the calendar begins fetching events via AJAX. Triggered with false when done.
		 * @version 1.15.0
		 * @param {Boolean} isLoading
		 */
		loading: function( isLoading ) {
			if( ! isLoading && bookacti.is_touch_device ) {
				// Trigger mouseover when an event is clicked on touch devices
				$j( '.fc-event' ).each( function(){
					var e = $j.Event( 'mouseover', { target: this.firstChild, _dummyCalledOnStartup: true } );
					$j( this ).trigger( e );
				});
			}
		}
	};
	
	if( bookacti_localized.calendar_localization === 'wp_settings' ) {
		var fc_time_format_obj    = bookacti_convert_php_datetime_format_to_fc_date_formatting_object( bookacti_localized.wp_time_format );
		init_data.firstDay        = bookacti_localized.wp_start_of_week;
		init_data.slotLabelFormat = fc_time_format_obj;
		init_data.eventTimeFormat = fc_time_format_obj;
	}
	
	// Let third-party plugin change initial calendar data
	booking_system.trigger( 'bookacti_calendar_editor_init_data', [ init_data ] );
	
	// Load the calendar
	if( typeof bookacti.fc_calendar[ 'bookacti-template-calendar' ] !== 'undefined' ) { bookacti.fc_calendar[ 'bookacti-template-calendar' ].destroy(); }
	bookacti.fc_calendar[ 'bookacti-template-calendar' ] = new FullCalendar.Calendar( booking_system.find( '.bookacti-calendar:first' )[ 0 ], init_data );
	bookacti.fc_calendar[ 'bookacti-template-calendar' ].render();
	
	booking_system.trigger( 'bookacti_calendar_editor_after_set_up' );
}