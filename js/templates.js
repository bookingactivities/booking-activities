$j( document ).ready( function() { 
	// Check if the calendar exist before anything
	if( ! $j( '#bookacti-template-container' ).length ) { return; }
	
	// Init calendar editor specific globals
	bookacti.selected_template	= parseInt( $j( '#bookacti-template-picker' ).val() ) || 0;
	bookacti.hidden_activities	= [];
	bookacti.selected_category	= 'new';
	bookacti.is_dragging		= false;
	bookacti.is_resizing		= false;
	bookacti.is_hovering		= false;
	bookacti.blocked_events		= false;
	bookacti.load_events		= false;
	
	var template_start_date, template_end_date = '';
	if( $j( '#bookacti-template-picker option[value="' + bookacti.selected_template + '"]' ).length ) {
		template_start_date = $j( '#bookacti-template-picker option[value="' + bookacti.selected_template + '"]' ).data( 'template-start' );
		template_end_date = $j( '#bookacti-template-picker option[value="' + bookacti.selected_template + '"]' ).data( 'template-end' );
	}
	
	// Init globals
	bookacti.booking_system[ 'bookacti-template-calendar' ]								= {};
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'calendars' ]				= bookacti.selected_template ? [ bookacti.selected_template ] : [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ]				= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ]				= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ]		= {};
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ]		= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ]	= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'start' ]					= template_start_date ? template_start_date + ' 00:00:00' : '';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'end' ]					= template_end_date ? template_end_date + ' 23:59:59' : '';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]			= { 
		'start' : template_start_date,
		'end' : template_end_date
	};

	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]		= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]			= [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]			= 0;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'method' ]					= 'calendar';
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events' ]			= true;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events_bookable' ]	= true;

	// Initialize activities and groups
	bookacti_init_activities();
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

	// Load Template calendar
	if ( $j( '#bookacti-template-calendar' ).length ) {
		bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );
	}

	// Load the template
	if( bookacti.selected_template ) {
		bookacti_switch_template( bookacti.selected_template );
	}

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
	 */
	$j( 'body' ).on( 'click', '#bookacti-exit-loading', function(){
		bookacti_exit_template_loading_state( true );
		bookacti.load_events = true;
	});
});


/**
 * Initialize and display the template calendar
 * @version 1.8.5
 * @param {HTMLElement} calendar
 */
function bookacti_load_template_calendar( calendar ) {
	calendar = calendar || $j( '#bookacti-template-calendar' );
	
	// Get calendar settings
	var availability_period	= bookacti_get_availability_period( calendar );
	var display_data		= typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ] !== 'undefined' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ] : {};
	var min_time			= typeof display_data.minTime !== 'undefined' ? display_data.minTime : '00:00';
	var max_time			= typeof display_data.maxTime !== 'undefined' ? ( display_data.maxTime === '00:00' ? '24:00' : display_data.maxTime ) : '24:00';
	var snap_duration		= typeof display_data.snapDuration !== 'undefined' ? display_data.snapDuration : '00:05';
	
	// See https://fullcalendar.io/docs/
	var init_data = {
		// OPTIONS
		locale:					bookacti_localized.fullcalendar_locale,

		defaultView:            'agendaWeek',
		minTime:                min_time,
		maxTime:                max_time,
		slotLabelFormat:		'LT',
		slotDuration:           '00:30',
		snapDuration:           snap_duration,
		scrollTime:				'00:00',
		aspectRatio:			'auto',
		
		validRange: {
            start: availability_period.start ? moment.utc( availability_period.start.substr( 0, 10 ) ) : moment.utc( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) ),
            end: availability_period.end ? moment.utc( availability_period.end.substr( 0, 10 ) ).add( 1, 'days' ) : moment.utc( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) ).add( 1, 'days' )
        },
		
		nowIndicator:           0,
		weekNumbers:	        0,
		weekNumbersWithinDays:	1,
		navLinks:		        1,

		slotEventOverlap:		0,
		eventLimit:				false,
		eventLimitClick:		'popover',

		allDaySlot:             false,
		allDayDefault:          false,

		fixedWeekCount:         false,
		showNonCurrentDates:	false,

		editable:               true,
		droppable:              true,
		dropAccept:             '.fc-event',
		eventDurationEditable:  false,
		dragRevertDuration:     0,
		
		
		// Header : Functionnality to Display above the calendar
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},

		
		// Always call "callback" for proper operations, even with an empty array of events
		events: function( start, end, timezone, callback ) {
			callback( [] );
		},


		viewRender: function( view ) {
			// Maybe fetch the events on the view (if not already)
			if( bookacti.load_events === true ) { 
				var interval = { 'start': moment.utc( moment.utc( view.intervalStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' ), 'end': moment.utc( moment.utc( view.intervalEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' ) };
				bookacti_fetch_events_from_interval( $j( '#bookacti-template-calendar' ), interval );
			}
		},

		
		/**
		 * When an event is rendered
		 * @version 1.8.5
		 * @param {object} event
		 * @param {HTMLElement} element
		 * @param {object} view
		 * @returns {Boolean}
		 */
		eventRender: function( event, element, view ) { 
			// Do not render the event if it has no start or no end or no duration
			if( ! event.start || ! event.end || event.start === event.end ) { return false; }
			
			// Directly return true if the event is resizing or dragging to avoid overload
			if( bookacti.is_dragging || bookacti.is_resizing ) { return true; }
			
			var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ];
			var activity_id = 0;
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
			
			if( typeof event_data !== 'undefined' && event_data.activity_id ) {
				activity_id = event_data.activity_id;
				element.data( 'activity-id', activity_id );
				element.attr( 'data-activity-id', activity_id );
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
			if( typeof event_data !== 'undefined' && event_data.availability ) {
				var availability = parseInt( event_data.availability );
				event.bookings = bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), event );
				if( event.bookings != null ) {
					var class_no_availability = '', class_booked = '', class_full = '';

					if( availability === 0 ) { class_no_availability = 'bookacti-no-availability'; }
					else {  
						if( event.bookings > 0 ) { 
							class_booked = 'bookacti-booked'; 
						} else { 
							class_booked = 'bookacti-not-booked'; 
						}
						if( event.bookings >= availability )  { 
							class_full = 'bookacti-full'; 
						} 
					}

					var avail_div	= '<div class="bookacti-availability-container" >' 
									+	'<span class="bookacti-available-places ' + class_no_availability + ' ' + class_booked + ' ' + class_full + '" >' 
									+		'<span class="bookacti-bookings" >' + event.bookings + '</span>' 
									+		'<span class="bookacti-total-availability" >/' + availability + '</span>'
									+	'</span>'
									+ '</div>';

					element.append( avail_div );
				}
			}
			
			
			// Add event actions div
			// Init var
			var event_actions_div	= element.find( '.bookacti-event-actions' ).length > 0 ? element.find( '.bookacti-event-actions' ) : $j( '<div></div>', { 'class': 'bookacti-event-actions' } );
			var event_actions		= [];
			
			// EDIT ACTION
			if( ! event_actions_div.find( '.bookacti-event-action-edit' ).length ) {
				var edit_div	=	$j( '<div></div>', {
										'class': 'bookacti-event-action bookacti-event-action-edit',
										'data-hide-on-mouseout': '1'
									} );
				var edit_button =	$j( '<span></span>', {
										'type': 'checkbox',
										'class': 'dashicons dashicons-admin-generic bookacti-event-action-edit-button',
										'aria-hidden': 'true'
									} );
				event_actions.push( edit_div.prepend( edit_button ) );
			}
			
			// SELECT ACTION
			if( ! event_actions_div.find( '.bookacti-event-action-select' ).length ) {
				
				// Check if the event is selected
				var event_start_date = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' );
				var is_selected = false;
				$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
					if( selected_event.id == event.id 
					&&  selected_event.start.substr( 0, 10 ) === event_start_date ) {
						is_selected = true;
						return false; // break the loop
					}
				});
				
				var select_div		=	$j( '<div></div>', {
											'class': 'bookacti-event-action bookacti-event-action-select',
											'data-hide-on-mouseout': '0'
										} );
				var select_checkbox =	$j( '<input />', {
											'type': 'checkbox',
											'class': 'bookacti-event-action-select-checkbox',
											'value': '0',
											'checked': is_selected
										} );
				event_actions.push( select_div.append( select_checkbox ) );
			}
			
			// Fill the event actions array
			$j.each( event_actions, function( i, event_action ) {
				event_actions_div.append( event_action );
			});
			
			// Allow thir party to edit the list of event actions
			calendar.trigger( 'bookacti_event_actions', [ event_actions_div, event, element, view ] );
			
			// Append the event actions list to the event
			element.append( event_actions_div );
			
			
			// Add background to basic views
			if( view.name === 'month' || view.name === 'basicWeek' || view.name === 'basicDay' ) {
				var bg_div = $j( '<div></div>', {
					'class': 'fc-bg'
				});
				element.append( bg_div );
			}
			
			
			// Check if the event is on an exception
			if( typeof event_data !== 'undefined' && event_data.repeat_freq ) {
				if( event_data.repeat_freq !== 'none' ) {
					if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ] !== undefined 
					&&  bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event.id ] !== undefined ) {
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event.id ], function ( i, excep ) {
							if( excep.exception_type === 'date' && excep.exception_value === event_start_date ) {
								event.render = 0;
							}
						});
					}
				}
			}
			

			// Check if the event is hidden
			if( bookacti.hidden_activities && activity_id ) {
				if( $j.inArray( parseInt( activity_id ), bookacti.hidden_activities ) >= 0 ) {
					event.render = 0;
				}
			}

			calendar.trigger( 'bookacti_event_render', [ event, element, view ] );

			if( ! event.render ) { return false; }
		},


		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
		},


		eventAfterAllRender: function( view ) {
			// Block the event if no other operation on it is allowed until the running ones are finished
			if( bookacti.blocked_events === true ) { 
				$j( '.fc-event' ).addClass( 'bookacti-event-unavailable' );
			} else if ( $j.isNumeric( bookacti.blocked_events ) ) {
				$j( '.fc-event[data-event-id="' + bookacti.blocked_events + '"]' ).addClass( 'bookacti-event-unavailable' );
			} else {
				$j( '.fc-event' ).removeClass( 'bookacti-event-unavailable' );
			}

			// Remove exceptions
			$j( '.fc-event.event-exception' ).remove();
			
			// Hide event actions
			$j( '.bookacti-event-action[data-hide-on-mouseout="1"]' ).hide();
			
			// Display element as picked or selected if they actually are
			$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ], function( i, picked_event ) {
				calendar.find( '.fc-event[data-event-id="' + picked_event[ 'id' ] + '"][data-event-start="' + picked_event[ 'start' ] + '"]' ).addClass( 'bookacti-picked-event' );
			});
			
			bookacti_refresh_selected_events_display();
		},

		
		/**
		 * When an extern draggable event is dropped on the calendar. "this" refer to the new created event on the calendar.
		 * @version 1.8.5
		 * @param {object} event
		 */
		eventReceive: function( event ) {
			var activity_id		= parseInt( event.activity_id );
			var activity_data	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ];		
			var view			= calendar.fullCalendar( 'getView' );
			
			// If the activity was not found, return false
			if( ! activity_data ) { return false; }
			
			// If the event is dropped on a non-agenda view, make it begins at the minTime
			if( view.name.substr( 0, 6 ) !== 'agenda' ) {
				var minTime	= calendar.fullCalendar( 'option', 'minTime' );
				event.start.set( { 'hours': minTime.substr( 0, 2 ), 'minutes': minTime.substr( 3, 2 ), 'seconds': 0 } );
			}
			
			// Calculate the end datetime thanks to start datetime and duration
			var activity_duration = activity_data.duration ? activity_data.duration : '000.01:00:00';
			event.end = event.start.clone();
			event.end.add( moment.duration( activity_duration ) );
			
			// Whether the event is resizable 
			if( parseInt( activity_data[ 'is_resizable' ] ) === 1 ) { event.durationEditable = true; }
			
			var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			
			var data = { 
				'action': 'bookactiInsertEvent', 
				'activity_id': activity_id, 
				'template_id': bookacti.selected_template, 
				'title': activity_data[ 'multilingual_title' ], 
				'start': event_start_formatted, 
				'end': event_end_formatted,
				'availability': activity_data[ 'availability' ],
				'nonce': $j( '#nonce_edit_template' ).val()
			};
			
			calendar.trigger( 'bookacti_insert_event_before', [ event, data ] );
			
			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: data, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					if( response.status === 'success' ) {
						// Give the database generated id to the event, 
						// so that any further changes to this event before page refresh will be also saved
						event.id = response.event_id;
						event.bookings = 0;
						
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ] = response.event_data;
						
						calendar.fullCalendar( 'updateEvent', event );
						
						calendar.trigger( 'bookacti_event_inserted', [ event, response, data ] );
						
					} else {
						// Remove event
						if( event._id !== undefined ) {
							if( event._id.indexOf('_') >= 0 ) {
								calendar.fullCalendar( 'removeEvents', event._id );
							}
						}
						calendar.fullCalendar( 'removeEvents', event.id );
						calendar.fullCalendar( 'refetchEvents' );

						// Display error message
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						alert( error_message );
						console.log( error_message );
						console.log( response );
					}
				},
				error: function( e ) {
					alert( 'AJAX ' + bookacti_localized.error );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},

		
		/**
		 * eventResize : When an event is resized
		 * @version 1.8.5
		 * @param {object} event
		 * @param {object} delta
		 * @param {callable} revertFunc
		 */
		eventResize: function( event, delta, revertFunc ) {
			// Do not allow to edit booked events
			var origin_event = {
				"id": event.id,
				"start": event.start.clone(),
				"end": event.end.clone().subtract( delta._data )
			};
			var bookings = bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), origin_event );
			if( bookings > 0 ) {
				revertFunc();
				var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
				if( is_repeated ) {
					bookacti_dialog_unbind_occurrences( event, [ 'resize' ] );
				} else {
					alert( bookacti_localized.error_edit_locked_event );
				}
				return false;
			}
			
			// Get event var to save in db
			var id			= event.id;
			var start		= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var end			= moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var delta_days	= delta._days;
			var data = { 
				'action': 'bookactiResizeEvent', 
				'delta_days': delta_days,
				'event_id': id, 
				'event_start': start, 
				'event_end': end,
				'nonce': $j( '#nonce_edit_template' ).val()
			};
			
			calendar.trigger( 'bookacti_resize_event_before', [ event, data, delta ] );
			
			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: data, 
				type: 'POST',
				dataType: 'json',
				success: function( response ) {
					if( response.status === 'success' ) { 
						var end_time = moment.utc( event.end ).clone().locale( 'en' ).format( 'HH:mm:ss' );
						
						// Update selected events
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
							if( selected_event.id == event.id ) {
								var event_end	= moment.utc( selected_event.end ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
								selected_event.end = event_end;
							}
						});
						
						// Update groups of events if the event belong to one of them
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_events ){
							$j.each( group_events, function( i, group_event ){
								if( group_event.id == event.id ) {
									var event_end	= moment.utc( group_event.end ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
									group_event.end = event_end;
								}
							});
						});
						
						calendar.trigger( 'bookacti_event_resized', [ event, response, data ] );
					}
					
					else if( response.status === 'failed' ) { 
						revertFunc();
						if( response.error === 'has_bookings' ) {
							// If the event's booking number is not up to date, refresh it
							if( ! event.bookings ) {
								bookacti_refresh_booking_numbers( $j( '#bookacti-template-calendar' ), event.id );
							}
							// If the event is repeated, display unbind dialog
							if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none' ) {
								bookacti_dialog_unbind_occurrences( event, [ 'resize' ] );
							} 
						}
						
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						alert( error_message );
						console.log( error_message );
						console.log( response );
					}
				},
				error: function( e ){
					revertFunc();
					alert( 'AJAX ' + bookacti_localized.error );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},

		
		/**
		 * eventDrop : When an event is moved to an other day / hour
		 * @version 1.8.5
		 * @param {object} event
		 * @param {object} delta
		 * @param {callable} revertFunc
		 * @param {object} e
		 */
		eventDrop: function( event, delta, revertFunc, e ) {
			// Check if the event is duplicated
			var is_alt_key_pressed = 0;
			if( e.altKey ) { is_alt_key_pressed = 1; }

			if( is_alt_key_pressed ) {
				revertFunc();
			} else {
				// Do not allow to edit a booked event
				var origin_event = {
					'id': event.id,
					'start': event.start.clone().subtract( delta._data ),
					'end': event.end.clone().subtract( delta._data )
				};
				var bookings = bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), origin_event );
				if( bookings > 0 ) {
					revertFunc();
					var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
					if( is_repeated ) {
						bookacti_dialog_unbind_occurrences( event, [ 'move' ] );
					} else {
						alert( bookacti_localized.error_edit_locked_event );
					}
					return false;
				}
			}
			
			// Update the event changes in database
			var id			= event.id;
			var start		= moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var end			= ! event.end ? start : moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var delta_days	= delta._days;
			var interval	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
			var data = { 
				'action': 'bookactiMoveEvent',
				'delta_days': delta_days, 
				'event_id': id, 
				'event_start': start, 
				'event_end': end,
				'interval': interval,
				'is_duplicated': is_alt_key_pressed,
				'nonce': $j( '#nonce_edit_template' ).val()
			};
			
			calendar.trigger( 'bookacti_move_event_before', [ event, data, delta ] );
			
			bookacti_start_template_loading();
			
			$j.ajax({
				url: ajaxurl, 
				data: data, 
				type: 'POST',
				dataType: 'json',
				success: function( response ) {
					if( response.status === 'success' ) { 
						// Display duplicated event(s)
						if( is_alt_key_pressed ) {
							var new_event_id = response.event_id;
							
							// Update exceptions
							if( typeof response.exceptions[ new_event_id ] !== 'undefined' ) {
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ new_event_id ] = response.exceptions[ new_event_id ];
							}
							
							// Add new event data
							if( ! $j.isEmptyObject( response.event_data ) ) {
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ] = response.event_data;
							}
							
							// Load the new event on calendar
							// AddEventSource will rerender events, then, new exceptions will also be taken into account
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
														
							calendar.trigger( 'bookacti_event_duplicated', [ event, response, data ] );
							
							return false; // Exit function
						}
						
						var start_time	= moment.utc( event.start ).clone().locale( 'en' ).format( 'HH:mm:ss' );
						var end_time	= moment.utc( event.end ).clone().locale( 'en' ).format( 'HH:mm:ss' );
						
						// Update event data
						if( ! $j.isEmptyObject( response.event_data ) ) {
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ id ] = response.event_data;
						}
						
						// Update selected events
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
							if( selected_event.id == event.id ) {
								var event_start	= moment.utc( selected_event.start ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + start_time;
								var event_end	= moment.utc( selected_event.end ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
								selected_event.start = event_start;
								selected_event.end = event_end;
							}
						});
						
						// Update groups of events if the event belong to one of them
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_events ){
							$j.each( group_events, function( i, group_event ){
								if( group_event.id == event.id ) {
									var event_start	= moment.utc( group_event.start ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + start_time;
									var event_end	= moment.utc( group_event.end ).clone().locale( 'en' ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
			
									group_event.start = event_start;
									group_event.end = event_end;
								}
							});
						});
						
						// Render updated event to make sure it fits in events interval
						if( response.events.length > 0 ) {
							$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', id );
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
						}
						
						calendar.trigger( 'bookacti_event_moved', [ event, response, data ] );
					}

					else if( response.status === 'failed' ) { 
						revertFunc();
						if( response.error === 'has_bookings' ) {
							// If the event's booking number is not up to date, refresh it
							if( ! event.bookings ) {
								bookacti_refresh_booking_numbers( $j( '#bookacti-template-calendar' ), event.id );
							}
							// If the event is repeated, display unbind dialog
							if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none' ) {
								bookacti_dialog_unbind_occurrences( event, [ 'move' ] );
							}
						}
						
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						alert( error_message );
						console.log( error_message );
						console.log( response );
					}
				},
				error: function( e ) {
					revertFunc();
					alert( 'AJAX ' + bookacti_localized.error );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},
		
		
		/**
		 * When the user drag an event
		 * @since 1.7.14
		 * @param {object} event
		 * @param {object} jsEvent
		 * @param {object} ui - deprecated
		 * @param {object} view
		 */
		eventDragStart: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = true;
			
			// Add a class to all events having this ID
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"]' );
			elements.addClass( 'bookacti-event-dragged' );
		},

		
		/**
		 * When the user drop an event, even if it is not on the calendar or if there is no change of date / hour
		 * @since 1.7.14
		 * @param {object} event
		 * @param {object} jsEvent
		 * @param {object} ui - deprecated
		 * @param {object} view
		 */
		eventDragStop: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = false;
			
			// Add a class to all events having this ID
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"]' );
			elements.removeClass( 'bookacti-event-dragged' );
		},

		// When the user resize an event
		eventResizeStart: function ( event, jsEvent, ui, view ) {
			bookacti.is_resizing = true;
		},


		// When the user re an event, even if it is not on the calendar or if there is no change of date / hour
		eventResizeStop: function ( event, jsEvent, ui, view ) {
			bookacti.is_resizing = false;
		},


		/**
		 * When an event is clicked
		 * @version 1.8.5
		 * @param {object} event
		 * @param {object} jsEvent
		 * @param {object} view
		 */
		eventClick: function( event, jsEvent, view ) {
			var element = $j( this );
			
			var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			
			// Because of popover and long events (spreading on multiple days), 
			// the same event can appears twice, so we need to apply changes on each
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event_start_formatted + '"]' );
			
			// Display event action on touch devices because they cannot be displayed on hover
			if( bookacti.is_touch_device ) {
				if( ! element.find( '.bookacti-event-action' ).is( ':visible' ) ) {
					$j( '.bookacti-event-over' ).removeClass( 'bookacti-event-over' );
					bookacti_show_event_actions( element );
				} else {
					bookacti_hide_event_actions( element, event );
				}
			}
			
			// Format the picked events
			$j( '.fc-event' ).removeClass( 'bookacti-picked-event' );
			elements.addClass( 'bookacti-picked-event' );

			// Keep picked events in memory 
			bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ] = [];
			bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ].push({ 
				'id'			: event.id,
				'start'			: event_start_formatted, 
				'end'			: event_end_formatted
			});
			
			// If the user click on an event action, execute it
			if( $j( jsEvent.target ).parents( '.bookacti-event-actions' ).length ) {
				
				// EDIT ACTION
				if( $j( jsEvent.target ).is( '.bookacti-event-action-edit' ) 
				||  $j( jsEvent.target ).parents( '.bookacti-event-action-edit' ).length ) {
					// Display the dialog to modify the event
					if( event.editable !== false ){
						bookacti_dialog_update_event( event );
					}
					
				// SELECT ACTION
				} else if( $j( jsEvent.target ).is( '.bookacti-event-action-select' )
						|| $j( jsEvent.target ).parents( '.bookacti-event-action-select' ).length ) {
					
					// Format selected events and keep them / remove them from memory
					if( element.find( '.bookacti-event-action-select-checkbox' ).is( ':checked' ) ) {
						bookacti_select_event( event );
					
					} else {
						bookacti_unselect_event( event );
						element.find( '.bookacti-event-action-select' ).show();
					}
				}
			}
			
			calendar.trigger( 'bookacti_pick_event', [ event, jsEvent, view ] );
		},
		
		
		/**
		 * eventMouseover : When your mouse get over an event
		 * @version 1.7.14
		 * @param {object} event
		 * @param {object} jsEvent
		 * @param {object} view
		 */
		eventMouseover: function( event, jsEvent, view ) { 
			// Add the "over" class
			var element = $j( this );
			bookacti_show_event_actions( element );
			
			bookacti.is_hovering = true;
		},
		
		
		/**
		 * eventMouseover : When your mouse move out an event
		 * @version 1.7.14
		 * @param {object} event
		 * @param {object} jsEvent
		 * @param {object} view
		 */
		eventMouseout: function( event, jsEvent, view ) { 
			// Remove the "over" class
			var element = $j( this );
			bookacti_hide_event_actions( element, event );
			
			bookacti.is_hovering = false;
		},
		
		
		loading: function( isLoading ) {
			if( ! isLoading && bookacti.is_touch_device ) {
				// Since the draggable events are lazy(bind)loaded, we need to
				// trigger them all so they're all ready for us to drag/drop
				// on the iPad.
				$j( '.fc-event' ).each( function(){
					var e = $j.Event( "mouseover", { target: this.firstChild, _dummyCalledOnStartup: true } );
					$j( this ).trigger( e );
				});
			}
		}
	};
	
	if( bookacti_localized.calendar_localization === 'wp_settings' ) {
		init_data.firstDay			= bookacti_localized.wp_start_of_week;
		init_data.slotLabelFormat	= bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
		init_data.timeFormat		= bookacti_convert_php_datetime_format_to_moment_js( bookacti_localized.wp_time_format );
	}
	
	// Let third-party plugin change initial calendar data
	calendar.trigger( 'bookacti_calendar_editor_init_data', [ init_data ] );
	
	// Load the calendar
	calendar.fullCalendar( init_data );
}