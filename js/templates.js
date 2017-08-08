$j( document ).ready( function() {    
	
	// Check if the calendar exist before anything
	if ( $j( '#bookacti-template-calendar' ).length || $j( '#bookacti-first-template-container' ).length ) { 
		
		// Init globals
		bookacti.booking_system[ 'bookacti-template-calendar' ]								= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events' ]					= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ]		= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ]	= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'settings' ]				= [];
		
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]			= [];
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]			= 0;
		
		// Calendar editor specific globals
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]		= [];
		bookacti.selected_template	= parseInt( $j( '#bookacti-template-picker' ).val() ) || 0;
		bookacti.selected_category	= 'new';
		bookacti.is_dragging		= false;
		bookacti.blocked_events		= false;
		bookacti.exceptions			= [];
		
		
		// initialize activities and groups
		bookacti_init_activities();
		bookacti_init_groups_of_events();

		// Show and Hide activities / groups
		bookacti_init_show_hide_activities_switch();
		bookacti_init_show_hide_groups_switch();
		
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

		slotEventOverlap:		0,
		eventLimit:				2,
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

		views: { week: { eventLimit: false }, day: { eventLimit: false } },

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


		//Load an empty array to allow the callback 'loading' to work
		events: function( start, end, timezone, callback ) {
			callback( [] );
		},


		viewRender: function( view ) {
			//Change the height of the calendar to match the available hours in agenda views
			if( view.name === 'agendaWeek' || view.name === 'agendaDay' ) {
				calendar.fullCalendar( 'option', 'contentHeight', 'auto' );
			} else { 
				calendar.fullCalendar( 'option', 'contentHeight', null ); 
			}
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
			event.render = 1;
			
			if( event.activity_id != null ) {
				element.data( 'activity-id', event.activity_id );
				element.attr( 'data-activity-id', event.activity_id );
			}

			if( view.name.indexOf( 'basic' ) > -1 || view.name.indexOf( 'month' ) > -1 ){
				element.find( 'span.fc-time' ).text( event.start.format( 'HH:mm' ) + ' - ' + event.end.format( 'HH:mm' ) );
			}

			// Add availability div
			if( event.bookings != null && event.availability != null ) {
				var class_no_availability = '', class_booked = '', class_full = '';

				if( parseInt( event.availability ) === 0 ) { class_no_availability = 'bookacti-no-availability'; }
				else {  
					if( parseInt( event.bookings ) > 0 ) { 
						class_booked = 'bookacti-booked'; 
					} else { 
						class_booked = 'bookacti-not-booked'; 
					}
					if( parseInt( event.bookings ) >= parseInt( event.availability ) )  { 
						class_full = 'bookacti-full'; 
					} 
				}

				element.find( 'div.fc-content' ).after( 
					'<div class="bookacti-availability-container" >' 
					+	'<span class="bookacti-available-places ' + class_no_availability + ' ' + class_booked + ' ' + class_full + '" >' 
					+		'<span class="bookacti-bookings" >' + event.bookings + '</span>' 
					+		'<span class="bookacti-total-availability" >/' + event.availability + '</span>'
					+	'</span>'
					+ '</div>');
			}
			
			
			// Add event actions div
			// Init var
			var event_actions_div	= element.find( '.bookacti-event-actions' ).length > 0 ? element.find( '.bookacti-event-actions' ) : $j( '<div />', { class: 'bookacti-event-actions' } );
			var event_actions		= [];
			
			// EDIT ACTION
			if( ! event_actions_div.find( '.bookacti-event-action-edit' ).length ) {
				var edit_div	=	$j( '<div />', {
										class: 'bookacti-event-action bookacti-event-action-edit',
										'data-hide-on-mouseout': '1'
									} );
				var edit_button =	$j( '<span />', {
										type: 'checkbox',
										class: 'dashicons dashicons-admin-generic bookacti-event-action-edit-button',
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
											class: 'bookacti-event-action bookacti-event-action-select',
											'data-hide-on-mouseout': '0'
										} );
				var select_checkbox =	$j( '<input />', {
											type: 'checkbox',
											class: 'bookacti-event-action-select-checkbox',
											value: '0',
											checked: is_selected
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
					class: 'fc-bg'
				});
				element.append( bg_div );
			}
			
			
			// Check if the event is on an exception
			if( bookacti.exceptions[ bookacti.selected_template ] !== undefined && bookacti.exceptions[ bookacti.selected_template ][ event.id ] !== undefined ) {
				$j.each( bookacti.exceptions[ bookacti.selected_template ][ event.id ], function ( i, excep ) {
					if( excep.type === 'date' && excep.value === event.start.format( 'YYYY-MM-DD' ) ) {
						element.addClass( 'event-exception' );
						event.render = 0;
					}
				});
			}

			// Check if the event is hidden
			if( bookacti.hidden_activities != null && event.activity_id != null ) {
				$j.each( bookacti.hidden_activities, function ( i, activity_id_to_hide ) {
					if( parseInt( event.activity_id ) === activity_id_to_hide ) {
						element.addClass( 'event-exception' );
						event.render = 0;
					}
				});
			}

			calendar.trigger( 'bookacti_event_render', [ event, element ] );

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
			
			//Calculate the end datetime thanks to start datetime and duration
				//Init variables
				event.end		= moment( event.start );
				var title		= event.title;
				var activity_id = '0';
				var availability= '0';
				var duration    = '000.00:00:00';
				var resizable   = 0;

				// Retrieve the event param
				$j( '#bookacti-template-activities-container .fc-event' ).each( function(){
					if( $j( this ).html() === title ) {
						title		= $j( this ).data( 'title' );
						activity_id = $j( this ).data( 'activity-id' );
						availability= $j( this ).data( 'availability' );
						duration    = $j( this ).data( 'duration' );
						resizable   = $j( this ).data( 'resizable' );
					}
				});

				//Retrieve day / hours / mn / s from duration
				var days    = parseInt(duration.substr( 0, 3 ) );
				var hours   = parseInt(duration.substr( 4, 5 ) );
				var minutes = parseInt(duration.substr( 7, 8 ) );
				var seconds = parseInt(duration.substr( 10, 11) );

				event.end.add( { d: days, h: hours, m: minutes, s: seconds } );

				if( parseInt( resizable ) === 1 ) { event.durationEditable = true; }

				// Gether event variables to save in db
				var start       = event.start.format( 'YYYY-MM-DD HH:mm:ss' );
				var end         = event.end.format( 'YYYY-MM-DD HH:mm:ss' );


			// Temporarily save the event on calendar, so that it survives to the loading state refresh
			event.id = 0;
			event.activity_id = activity_id;
			calendar.fullCalendar( 'updateEvent', event );

			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: { 'action': 'bookactiInsertEvent', 
						'activity_id': activity_id, 
						'template_id': bookacti.selected_template, 
						'event_title': title, 
						'event_start': start, 
						'event_end': end,
						'event_availability': availability,
						'nonce': bookacti_localized.nonce_insert_event
					}, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					if( response.status === 'success' ) {
						// Give the database generated id to the event, 
						// so that any further changes to this event before page refresh will be also saved
						event.id = response.eventid;
						event.activity_id  = activity_id;  if( ! activity_id )	{ event.activity_id  = '0'; }
						event.availability = availability; if( ! availability ) { event.availability = '0'; }
						event.bookings     = '0';
						calendar.fullCalendar( 'updateEvent', event );

					} else {
						// Remove event
						$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event._id );
						$j( '#bookacti-template-calendar' ).fullCalendar( 'refetchEvents' );

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
							bookacti_refetch_events_on_template( event );
							bookacti_dialog_unbind_occurences( event, [ 'resize' ] );
						} else {
							var error_message = bookacti_localized.error_resize_event;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							} else if( response.error === 'has_bookings' ) {
								bookacti_refetch_events_on_template( event );
								error_message += '\n' + bookacti_localized.error_edit_locked_event;
								error_message += '\n' + bookacti_localized.advice_switch_to_maintenance + '\n';
							}
							alert( error_message );
							console.log( response );
						}
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
			var is_alt_key_pressed = 0;
			if( e.altKey ) { is_alt_key_pressed = 1; }

			if( is_alt_key_pressed ) {
				revertFunc();
			}

			// Update the event changes in database
			var id			= event.id;
			var start		= event.start.format( 'YYYY-MM-DD HH:mm:ss' );
			var end			= ( event.end === null ) ? start : event.end.format( 'YYYY-MM-DD HH:mm:ss' );
			var delta_days	= delta._days;

			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl, 
				data: { 'action': 'bookactiMoveEvent', 
						'delta_days': delta_days, 
						'event_id': id, 
						'event_start': start, 
						'event_end': end,
						'is_duplicated': is_alt_key_pressed,
						'nonce': bookacti_localized.nonce_move_or_resize_event
					}, 
				type: 'POST',
				dataType: 'json',
				success: function( response ){
					
					if( response.status === 'success' ) { 
						
						var start_time	= event.start.format( 'HH:mm:ss' );
						var end_time	= event.end.format( 'HH:mm:ss' );
						
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
						
						// Update exception if the event is duplicated
						if( is_alt_key_pressed ) {
							var new_event = { 'id': response.event_id };
							bookacti_update_exceptions( bookacti.selected_template, new_event );
						}
					}

					if( response.status === 'failed' ) { 
						revertFunc();
						if( response.error === 'has_bookings' ) {
							bookacti_refetch_events_on_template( event );
							bookacti_dialog_unbind_occurences( event, [ 'move' ] );
						} else {
							var error_message = bookacti_localized.error_move_event;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							} else if( response.error === 'has_bookings' ) {
								bookacti_refetch_events_on_template( event );
								error_message += '\n' + bookacti_localized.error_edit_locked_event;
								error_message += '\n' + bookacti_localized.advice_switch_to_maintenance + '\n';
							}
							alert( error_message );
							console.log( response );
						}
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

		//When the user drag an event
		eventDragStart: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = true;
		},


		//When the user drop an event, even if it is not on the calendar or if there is no change of date / hour
		eventDragStop: function ( event, jsEvent, ui, view ) {
			bookacti.is_dragging = false;
		},


		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) {
			var element = $j( this );
			// Because of popover and long events (spreading on multiple days), 
			// the same event can appears twice, so we need to apply changes on each
			var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
			
			// Format the picked events
			$j( '.fc-event' ).removeClass( 'bookacti-picked-event' );
			elements.addClass( 'bookacti-picked-event' );

			// Keep picked events in memory 
			bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ] = [];
			bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ].push({ 
				'id'			: event.id,
				'activity_id'	: event.activity_id,
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
		},
		
		// eventMouseover : When your mouse get over an event
		eventMouseover: function( event, jsEvent, view ) { 
			// Add the "over" class
			var element = $j( this );
			element.addClass( 'bookacti-event-over' );
			
			element.find( '.bookacti-event-action' ).show();
		},
		
		// eventMouseover : When your mouse move out an event
		eventMouseout: function( event, jsEvent, view ) { 
			// Remove the "over" class
			var element = $j( this );
			element.removeClass( 'bookacti-event-over' );
			
			element.find( '.bookacti-event-action[data-hide-on-mouseout="1"]' ).hide();
			
			// Check if the event is selected
			var is_selected = false
			$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ){
				if( selected_event.id == event.id 
				&&  selected_event.start.substr( 0, 10 ) === event.start.format( 'YYYY-MM-DD' ) ) {
					is_selected = true;
					return false; // break the loop
				}
			});
			
			// If the event is selected, do not hide the 'selected' checkbox
			if( is_selected ) {
				element.find( '.bookacti-event-actions' ).show();
				element.find( '.bookacti-event-action-select' ).show();
			} else {
				element.find( '.bookacti-event-action-select' ).hide();
			}
		},
		
		
		loading: function( isLoading ) {
			if( ! isLoading && bookacti_is_touch_device() ) {
				// Since the draggable events are lazy(bind)loaded, we need to
				// trigger them all so they're all ready for us to drag/drop
				// on the iPad. w00t!
				$j( '.fc-event' ).each( function(){
					var e = $j.Event( "mouseover", { target: this.firstChild, _dummyCalledOnStartup: true } );
					$j( this ).trigger( e );
				});
			}
		}
	}); 
}