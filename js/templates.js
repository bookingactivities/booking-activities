$j( document ).ready( function() {    
	
	// Check if the calendar exist before anything
	if ( $j( '#bookacti-template-calendar' ).length || $j( '#bookacti-first-template-container' ).length ) { 
		
		// Init calendar editor specific globals
		bookacti.selected_template	= parseInt( $j( '#bookacti-template-picker' ).val() ) || 0;
		bookacti.hidden_activities	= [];
		bookacti.selected_category	= 'new';
		bookacti.is_dragging		= false;
		bookacti.is_resizing		= false;
		bookacti.blocked_events		= false;
		bookacti.load_events		= false;
		
		// Init globals
		bookacti.booking_system[ 'bookacti-template-calendar' ]								= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'calendars' ]				= bookacti.selected_template ? [ bookacti.selected_template ] : [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ]				= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ]				= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ]		= {};
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ]		= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ]	= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]			= []; 
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]			= { 
			'start' : $j( '#bookacti-template-picker option[value="' + bookacti.selected_template + '"]' ).data( 'template-start' ) || false, 
			'end' : $j( '#bookacti-template-picker option[value="' + bookacti.selected_template + '"]' ).data( 'template-end' ) || false 
		};
		
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]		= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]			= 0;
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'method' ]					= 'calendar';
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events' ]			= true;
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events_bookable' ]	= true;
		
		
		// initialize activities and groups
		bookacti_init_activities();
		bookacti_init_groups_of_events();

		// Show and Hide activities
		bookacti_init_show_hide_activities_switch();
		
		// DIALOGS
		// Init the Dialogs
		bookacti_init_template_dialogs();

		// Launch dialogs when...
		bookacti_bind_template_dialogs();

		// Change default template on change in the select box
		$j( '#bookacti-template-picker' ).on( 'change', function(){
			bookacti_switch_template( $j( this ).val() );
		});
		
		// Load Template calendar
		if ( $j( '#bookacti-template-calendar' ).length ) {
			bookacti_load_template_calendar();
		}
		
		// Load the template
		if( bookacti.selected_template ) {
			bookacti_switch_template( bookacti.selected_template );
		}
		
		// If an error occurs, stop loading and allow every interactions
		window.onerror = function ( errorMsg, url, lineNumber, column, errorObj ) {
			$j( '#bookacti-fatal-error' ).show();
		};
		$j( '#bookacti-exit-loading' ).on( 'click', function(){
			bookacti_exit_template_loading_state( true );
			bookacti.load_events = true;
		});
	}
});


// Initialize and display the template calendar
function bookacti_load_template_calendar() {
	var calendar = $j( '#bookacti-template-calendar' );
	calendar.fullCalendar( {

		// OPTIONS
		locale:					bookacti_localized.current_lang_code,

		defaultView:            'agendaWeek',
		minTime:                '08:00',
		maxTime:                '20:00',
		slotDuration:           '00:30',
		snapDuration:           '00:30',
		scrollTime:				'08:00',
		nowIndicator:           0,
		weekNumbers:	        0,
		weekNumbersWithinDays:	1,
		navLinks:		        0,

		slotEventOverlap:		0,
		eventLimit:				2,
		eventLimitClick:		'popover',
		showNonCurrentDates:	0,

		allDaySlot:             false,
		allDayDefault:          false,

		fixedWeekCount:         false,
		showNonCurrentDates:	false,

		editable:               true,
		droppable:              true,
		dropAccept:             '.fc-event',
		eventDurationEditable:  false,
		dragRevertDuration:     0,
		
		views: { 
			week:		{ eventLimit: false }, 
			day:		{ eventLimit: false }, 
			listDay:	{ buttonText: bookacti_localized.calendar_button_list_day },
			listWeek:	{ buttonText: bookacti_localized.calendar_button_list_week },
			listMonth:	{ buttonText: bookacti_localized.calendar_button_list_month },
			listYear:	{ buttonText: bookacti_localized.calendar_button_list_year } 
		},

		// Header : Functionnality to Display above the calendar
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},

		
		// Prevent user from navigating, dropping or resizing events, out of template period
		validRange: {
			start: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) ),
			end: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) ).add( 1, 'days' )
		},


		// Always call "callback" for proper operations, even with an empty array of events
		events: function( start, end, timezone, callback ) {
			callback( [] );
		},


		viewRender: function( view ) {
			// Maybe fetch the events on the view (if not already)
			if( bookacti.load_events === true ) { 
				var interval = { 'start': moment.utc( view.intervalStart ), 'end': moment.utc( view.intervalEnd ).subtract( 1, 'days' ) };
				bookacti_fetch_events_from_interval( $j( '#bookacti-template-calendar' ), interval );
			}
			
			// Change the height of the calendar to match the available hours in agenda views
			if( view.name === 'agendaWeek' || view.name === 'agendaDay' ) {
				calendar.fullCalendar( 'option', 'contentHeight', 'auto' );
			} else { 
				calendar.fullCalendar( 'option', 'contentHeight', null ); 
			}
		},


		// When an event is rendered
		eventRender: function( event, element, view ) { 
			// Directly return true if the event is resizing or dragging to avoid overload
			if( bookacti.is_dragging || bookacti.is_resizing ) { return true; }
			
			var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ];
			
			// Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'event-start',		event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-start',	event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.data( 'event-end',			event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			element.attr( 'data-event-end',		event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			event.render = 1;
			
			if( typeof event_data !== 'undefined' && event_data.activity_id ) {
				var activity_id = event_data.activity_id;
				element.data( 'activity-id', activity_id );
				element.attr( 'data-activity-id', activity_id );
			}

			if( view.name.indexOf( 'basic' ) > -1 || view.name.indexOf( 'month' ) > -1 ){
				element.find( 'span.fc-time' ).text( event.start.format( 'HH:mm' ) + ' - ' + event.end.format( 'HH:mm' ) );
			}
			
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
			var event_actions_div	= element.find( '.bookacti-event-actions' ).length > 0 ? element.find( '.bookacti-event-actions' ) : $j( '<div />', { 'class': 'bookacti-event-actions' } );
			var event_actions		= [];
			
			// EDIT ACTION
			if( ! event_actions_div.find( '.bookacti-event-action-edit' ).length ) {
				var edit_div	=	$j( '<div />', {
										'class': 'bookacti-event-action bookacti-event-action-edit',
										'data-hide-on-mouseout': '1'
									} );
				var edit_button =	$j( '<span />', {
										'type': 'checkbox',
										'class': 'dashicons dashicons-admin-generic bookacti-event-action-edit-button',
										'aria-hidden': 'true'
									} );
				event_actions.push( edit_div.prepend( edit_button ) );
			}
			
			// SELECT ACTION
			if( ! event_actions_div.find( '.bookacti-event-action-select' ).length ) {
				
				// Check if the event is selected
				var is_selected = false
				$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
					if( selected_event.id == event.id 
					&&  selected_event.start.substr( 0, 10 ) === event.start.format( 'YYYY-MM-DD' ) ) {
						is_selected = true;
						return false; // break the loop
					}
				});
				
				var select_div		=	$j( '<div />', {
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
				var bg_div = $j( '<div />', {
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
							if( excep.exception_type === 'date' && excep.exception_value === event.start.format( 'YYYY-MM-DD' ) ) {
								event.render = 0;
							}
						});
					}
				}
			}
			

			// Check if the event is hidden
			if( bookacti.hidden_activities != null && activity_id != null ) {
				$j.each( bookacti.hidden_activities, function ( i, activity_id_to_hide ) {
					if( parseInt( activity_id ) === activity_id_to_hide ) {
						element.addClass( 'event-exception' );
						event.render = 0;
					}
				});
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


		// eventReceive : When an extern draggable event is dropped on the calendar. "this" refer to the new created event on the calendar.
		eventReceive: function( event ) {
			
			var activity_id		= parseInt( event.activity_id );
			var activity_data	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ];
			
			// If the activity was not found, return false
			if( ! activity_data ) { return false; }
			
			// Calculate the end datetime thanks to start datetime and duration
			event.end = event.start.clone();
			event.end.add( moment.duration( activity_data[ 'duration' ] ) );
			
			// Whether the event is resizable 
			if( parseInt( activity_data[ 'is_resizable' ] ) === 1 ) { event.durationEditable = true; }
			
			
			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: { 'action': 'bookactiInsertEvent', 
						'activity_id': activity_id, 
						'template_id': bookacti.selected_template, 
						'event_title': activity_data[ 'multilingual_title' ], 
						'event_start': event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
						'event_end': event.end.format( 'YYYY-MM-DD HH:mm:ss' ),
						'event_availability': activity_data[ 'availability' ],
						'nonce': bookacti_localized.nonce_insert_event
					}, 
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
						var error_message = bookacti_localized.error_insert_event;
						if( response.error === 'not_allowed' ) {
							error_message += '\n' + bookacti_localized.error_not_allowed;
						}
						alert( error_message );
						console.log( response );
					}
				},
				error: function( e ) {
					alert( 'AJAX ' + bookacti_localized.error_insert_event );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},


		// eventResize : When an event is resized
		eventResize: function( event, delta, revertFunc ) {
			// Do not allow to edit booked events
			var origin_event = {
					"id": event.id,
					"start": event.start.clone(),
					"end": event.end.clone().subtract( delta._data ),
				};
			var bookings = bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), origin_event );
			if( bookings > 0 ) {
				revertFunc();
				var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
				if( is_repeated ) {
					bookacti_dialog_unbind_occurences( event, [ 'resize' ] );
				} else {
					alert( bookacti_localized.error_edit_locked_event );
				}
				return false;
			}
			
			// Get event var to save in db
			var id			= event.id;
			var start		= event.start.format('YYYY-MM-DD HH:mm:ss');
			var end			= event.end.format('YYYY-MM-DD HH:mm:ss');
			var delta_days	= delta._days;
			
			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: { 'action': 'bookactiResizeEvent', 
						'delta_days': delta_days,
						'event_id': id, 
						'event_start': start, 
						'event_end': end,
						'nonce': bookacti_localized.nonce_move_or_resize_event
					}, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					
					if( response.status === 'success' ) { 
						var end_time = event.end.format( 'HH:mm:ss' );
						
						// Update selected events
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
							if( selected_event.id == event.id ) {
								var event_end	= moment( selected_event.end ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
								selected_event.end = event_end;
							}
						});
						
						// Update groups of events if the event belong to one of them
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_events ){
							$j.each( group_events, function( i, group_event ){
								if( group_event.id == event.id ) {
									var event_end	= moment( group_event.end ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
									group_event.end = event_end;
								}
							});
						});	
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
								bookacti_dialog_unbind_occurences( event, [ 'resize' ] );
							} 
						}
						
						var error_message = bookacti_localized.error_resize_event;
						if( response.error === 'not_allowed' ) {
							error_message += '\n' + bookacti_localized.error_not_allowed;
						} else if( response.error === 'has_bookings' ) {
							bookacti_refresh_booking_numbers( $j( '#bookacti-template-calendar' ), event.id );
							error_message += '\n' + bookacti_localized.error_edit_locked_event;
							error_message += '\n' + bookacti_localized.advice_switch_to_maintenance + '\n';
						}
						alert( error_message );
						console.log( response );
					}
				},
				error: function( e ){
					revertFunc();
					alert( 'AJAX ' + bookacti_localized.error_resize_event );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},


		// eventDrop : When an event is moved to an other day / hour
		eventDrop: function( event, delta, revertFunc, e ) {
			
			// Check if the event is duplicated
			var is_alt_key_pressed = 0;
			if( e.altKey ) { is_alt_key_pressed = 1; }

			if( is_alt_key_pressed ) {
				revertFunc();
				
			} else {
				// Do not allow to edit a booked event
				var origin_event = {
					"id": event.id,
					"start": event.start.clone().subtract( delta._data ),
					"end": event.end.clone().subtract( delta._data ),
				};
				var bookings = bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), origin_event );
				if( bookings > 0 ) {
					revertFunc();
					var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
					if( is_repeated ) {
						bookacti_dialog_unbind_occurences( event, [ 'move' ] );
					} else {
						alert( bookacti_localized.error_edit_locked_event );
					}
					return false;
				}
			}
			
			// Update the event changes in database
			var id			= event.id;
			var start		= event.start.format( 'YYYY-MM-DD HH:mm:ss' );
			var end			= ( event.end === null ) ? start : event.end.format( 'YYYY-MM-DD HH:mm:ss' );
			var delta_days	= delta._days;
			var interval	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
			
			bookacti_start_template_loading();
			
			$j.ajax({
				url: ajaxurl, 
				data: { 'action': 'bookactiMoveEvent', 
						'delta_days': delta_days, 
						'event_id': id, 
						'event_start': start, 
						'event_end': end,
						'interval': interval,
						'is_duplicated': is_alt_key_pressed,
						'nonce': bookacti_localized.nonce_move_or_resize_event
					}, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					
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
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
							
							// AddEventSource will rerender events, then, new exceptions will also be taken into account
							
							return false; // Exit function
						}
						
						var start_time	= event.start.format( 'HH:mm:ss' );
						var end_time	= event.end.format( 'HH:mm:ss' );
						
						// Update event data
						if( ! $j.isEmptyObject( response.event_data ) ) {
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ id ] = response.event_data;
						}
						
						// Update selected events
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
							if( selected_event.id == event.id ) {
								var event_start	= moment( selected_event.start ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + start_time;
								var event_end	= moment( selected_event.end ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
								selected_event.start = event_start;
								selected_event.end = event_end;
							}
						});
						
						// Update groups of events if the event belong to one of them
						$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_events ){
							$j.each( group_events, function( i, group_event ){
								if( group_event.id == event.id ) {
									var event_start	= moment( group_event.start ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + start_time;
									var event_end	= moment( group_event.end ).add( delta_days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + end_time;
			
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
								bookacti_dialog_unbind_occurences( event, [ 'move' ] );
							}
						}
						
						var error_message = bookacti_localized.error_move_event;
						if( response.error === 'not_allowed' ) {
							error_message += '\n' + bookacti_localized.error_not_allowed;
						} else if( response.error === 'has_bookings' ) {
							bookacti_refresh_booking_numbers( $j( '#bookacti-template-calendar' ), event.id );
							error_message += '\n' + bookacti_localized.error_edit_locked_event;
							error_message += '\n' + bookacti_localized.advice_switch_to_maintenance + '\n';
						}
						alert( error_message );
						console.log( response );
					}
				},
				error: function( e ) {
					revertFunc();
					alert( 'AJAX ' + bookacti_localized.error_move_event );
					console.log( e );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},

		// When the user drag an event
		eventDragStart: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = true;
		},


		// When the user drop an event, even if it is not on the calendar or if there is no change of date / hour
		eventDragStop: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = false;
		},

		// When the user resize an event
		eventResizeStart: function ( event, jsEvent, ui, view ) {
			bookacti.is_resizing = true;
		},


		// When the user re an event, even if it is not on the calendar or if there is no change of date / hour
		eventResizeStop: function ( event, jsEvent, ui, view ) {
			bookacti.is_resizing = false;
		},


		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			var element = $j( this );
			// Because of popover and long events (spreading on multiple days), 
			// the same event can appears twice, so we need to apply changes on each
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
			
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
				'start'			: event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
				'end'			: event.end.format( 'YYYY-MM-DD HH:mm:ss' ) 
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
		
		// eventMouseover : When your mouse get over an event
		eventMouseover: function( event, jsEvent, view ) { 
			// Add the "over" class
			var element = $j( this );
			bookacti_show_event_actions( element );
		},
		
		// eventMouseover : When your mouse move out an event
		eventMouseout: function( event, jsEvent, view ) { 
			// Remove the "over" class
			var element = $j( this );
			bookacti_hide_event_actions( element, event );
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
	}); 
}