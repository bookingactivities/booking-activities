$j( document ).ready( function() {    
	
	//Check if the calendar exist before anything
	if ( $j( '#bookacti-template-calendar' ).length || $j( '#bookacti-first-template-container' ).length ) { 

		// Capture mouse x and y coordinates to global variables
		$j( document ).off().on( 'mousemove', function ( event ) 
		{
			//Get current mouse position
			currentMousePos.x = event.pageX;
			currentMousePos.y = event.pageY;

			//Visual feedabck : the trash open itself when mouse drag an event or an activity over it
			bookacti_open_or_close_trash();
		});

		// initialize the activities
		bookacti_init_activities();

		//Show and Hide activities
		bookacti_init_show_hide_activities_switch();

		// Template Icon appearence on hover
		bookacti_hover_template_icons();


		//DIALOGS
		//Init the Dialogs
		bookacti_init_template_dialogs();

		//Launch dialogs when...
		bookacti_bind_template_dialogs();

		//Change default template on change in the select box
		$j( '#bookacti-template-picker' ).on( 'change', function(){
			bookacti_switch_template( $j( this ).val() );
		});
		
		// Load Template calendar
		if ( $j( '#bookacti-template-calendar' ).length ) {
			bookacti_load_template_calendar();
		}

		//Change the default settings
		if( template_id ) {
			bookacti_update_settings_from_database( $j( '#bookacti-template-calendar' ), template_id );

			//Init the events exceptions for the current template
			bookacti_update_exceptions( template_id, null, false );

			//Init the bookings for the current template
			bookacti_update_bookings( template_id );
			
			//Load the events on the calendar
			bookacti_fetch_events_on_template( template_id, null );
		}

	}
});

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


		//Prevent user from dropping / resizing activity out of template period
		eventConstraint :{
			start: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) + 'T00:00:00' ),
			end: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) + 'T00:00:00' ).add( 1, 'days' )
		},


		//Load an empty array to allow the callback 'loading' to work
		events: function( start, end, timezone, callback ) {
			var empty_array = [];
			callback(empty_array);
		},


		viewRender: function( view ){
			//Change the height of the calendar to match the available hours in agenda views
			if( view.name === 'agendaWeek' || view.name === 'agendaDay' ) {
				calendar.fullCalendar( 'option', 'contentHeight', 'auto' );
			} else { 
				calendar.fullCalendar( 'option', 'contentHeight', null ); 
			}
		},


		// When an event is rendered
		eventRender: function( event, element, view ) { 

			//Add some info to the event
			element.data( 'event-id',			event.id );
			element.attr( 'data-event-id',		event.id );
			element.data( 'occurence-id',		event.occurence_id );
			element.attr( 'data-occurence-id',	event.occurence_id );
			element.data( 'event-date',			event.start.format( 'YYYY-MM-DD' ) );
			element.attr( 'data-event-date',	event.start.format( 'YYYY-MM-DD' ) );
			event.render = 1;

			if( event.activity_id != null ) {
				element.data( 'activity-id', event.activity_id );
				element.attr( 'data-activity-id', event.activity_id );
			}

			if( view.name.indexOf( 'basic' ) > -1 || view.name.indexOf( 'month' ) > -1 ){
				element.find( 'span.fc-time' ).text( event.start.format( 'HH:mm' ) + ' - ' + event.end.format( 'HH:mm' ) );
			}

			//Add availability div
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

			//Check if the event is on an exception
			if( exceptions[ template_id ] !== undefined && exceptions[ template_id ][ event.id ] !== undefined ) {
				$j.each( exceptions[ template_id ][ event.id ], function ( i, excep ) {
					if( excep.type === 'date' && excep.value === event.start.format( 'YYYY-MM-DD' ) ) {
						element.addClass( 'event-exception' );
						event.render = 0;
					}
				});
			}

			//Check if the event is hidden
			if( hiddenActivities != null && event.activity_id != null ) {
				$j.each( hiddenActivities, function ( i, activity_id_to_hide ) {
					if( parseInt( event.activity_id ) === activity_id_to_hide ) {
						element.addClass( 'event-exception' );
						event.render = 0;
					}
				});
			}

			calendar.trigger( 'eventRender', [ event, element ] );

			if( ! event.render ) { return false; }
		},


		eventAfterRender: function( event, element, view ) { 
			bookacti_add_class_according_to_event_size( element );
		},


		eventAfterAllRender: function( view ) {

			//Limit the calendar to the template period and hide events out of the template period
			bookacti_refresh_current_template_view();

			//Block the event if no other operation on it is allowed until the running ones are finished
			if( blockEvents === true ) { 
				$j( '.fc-event' ).addClass( 'event-unavailable' );
			} else if ( $j.isNumeric( blockEvents ) ) {
				$j( '.fc-event[data-event-id="' + blockEvents + '"]' ).addClass( 'event-unavailable' );
			} else {
				$j( '.fc-event' ).removeClass( 'event-unavailable' );
			}

			//remove exceptions
			$j( '.fc-event.event-exception' ).remove();

			//Display element as selected if they actually are
			$j.each( selectedEvents[ 'template' ], function( i, selected_event ) {
				var selected_event_id = selected_event['event-id'];
				var selected_occurence_id = selected_event['occurence-id'];
				$j( '.fc-event[data-event-id="' + selected_event_id + '"][data-occurence-id="' + selected_occurence_id + '"]' ).addClass( 'bookacti-selected-event' );
			});
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
				var start       = event.start.format( 'YYYY-MM-DD[T]HH:mm:ss' );
				var end         = event.end.format( 'YYYY-MM-DD[T]HH:mm:ss' );


			//Temporarily save the event on calendar, so that it survives to the loading state refresh
			event.id = 0;
			calendar.fullCalendar( 'updateEvent', event );

			bookacti_start_template_loading();

			$j.ajax({
				url: ajaxurl,
				data: { 'action': 'bookactiInsertEvent', 
						'activity_id': activity_id, 
						'template_id': template_id, 
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
					console.log( e.responseText );
				},
				complete: function() { 
					bookacti_stop_template_loading();
				}
			});
		},


		// eventResize : When an event is resized
		eventResize: function( event, delta, revertFunc ) {
			var is_locked = bookacti_is_locked_event( event.id );
			if( ! is_locked ) {

				// Get event var to save in db
				var id     = event.id;
				var start  = event.start.format('YYYY-MM-DD[T]HH:mm:ss');
				var end    = event.end.format('YYYY-MM-DD[T]HH:mm:ss');

				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl,
					data: { 'action': 'bookactiResizeEvent', 
							'event_id': id, 
							'event_start': start, 
							'event_end': end,
							'nonce': bookacti_localized.nonce_move_or_resize_event
						}, 
					type: 'POST',
					dataType: 'json',
					success: function( response ){
						if( response.status === 'failed' ) 
						{ 
							revertFunc();
							if( response.error === 'has_bookings' && event.occurence_id !== undefined ) {
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
					error: function(e){
						revertFunc();
						alert( 'AJAX ' + bookacti_localized.error_resize_event );
						console.log(e.responseText);
					},
					complete: function() { 
						bookacti_stop_template_loading();
					}
				});

			} else {
				revertFunc();
				if( event.bookings > 0 ) {
					alert( bookacti_localized.error_edit_locked_event );
				} else {
					bookacti_dialog_unbind_occurences( event, [ 'resize' ] );
				}
			}
		},


		// eventDrop : When an event is moved to an other day / hour
		eventDrop: function( event, delta, revertFunc, e ) {
			var is_locked = bookacti_is_locked_event( event.id );
			if( ! is_locked ) {

				var is_alt_key_pressed = 0;
				if( e.altKey ) { is_alt_key_pressed = 1; }

				// Update the event changes in database
				var id      = event.id;
				var start   = event.start.format( 'YYYY-MM-DD[T]HH:mm:ss' );
				var end     = ( event.end === null ) ? start : event.end.format( 'YYYY-MM-DD[T]HH:mm:ss' );
				var delta_days = delta._days;

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

						if( is_alt_key_pressed ) {
							revertFunc();
							if( response.status === 'success' ) { 
								var new_event = { 'id': response.event_id };
								bookacti_update_exceptions( template_id, new_event );
							}
						} else {
							if( response.status === 'nochanges' ) { 

							}
						}

						if( response.status === 'failed' ) { 
							revertFunc();
							if( response.error === 'has_bookings' && event.occurence_id !== undefined ) {
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
						console.log( e.responseText );
					},
					complete: function() { 
						bookacti_stop_template_loading();
					}
				});

			} else {
				revertFunc();
				if( event.bookings > 0 ) {
					alert( bookacti_localized.error_edit_locked_event );
				} else {
					bookacti_dialog_unbind_occurences( event, [ 'move' ] );
				}
			}
		},

		//When the user drag an event
		eventDragStart: function ( event, jsEvent, ui, view ) 
		{
			isDragging = true;
		},


		//When the user drop an event, even if it is not on the calendar or if there is no change of date / hour
		eventDragStop: function ( event, jsEvent, ui, view ) 
		{
			isDragging = false;

			if( bookacti_is_mouse_over_elem( $j( '#bookacti-template-trash-container' ) ) ) 
			{
				var is_locked = bookacti_is_locked_event( event.id );

				if( ! is_locked ) {

					bookacti_dialog_delete_event( event );

					// Force the bin to close
					$j( '#bookacti-template-trash-open' ).hide();
					$j( '#bookacti-template-trash-closed' ).show();

				} else {
					if( event.bookings > 0 ) {
						alert( bookacti_localized.error_edit_locked_event );
					} else {
						bookacti_dialog_unbind_occurences( event, [ 'delete' ] );
					}
				}
			}
		},


		// eventClick : When an event is clicked
		eventClick: function( event, jsEvent, view ) 
		{
			var selected_event_id		= $j( this ).data( 'event-id' );
			var selected_occurence_id	= $j( this ).data( 'occurence-id' );

			//Format the selected event
			$j( '.fc-event' ).removeClass( 'bookacti-updated-event' );
			$j( '.fc-event' ).removeClass( 'bookacti-selected-event' );
			$j( '.fc-event[data-event-id="' + selected_event_id + '"][data-occurence-id="' + selected_occurence_id + '"]' ).addClass( 'bookacti-selected-event' );

			selectedEvents[ 'template' ] = [];
			selectedEvents[ 'template' ].push( 
			{ 'event-id'			: selected_event_id, 
			'occurence-id'			: selected_occurence_id, 
			'activity-id'			: event.activity_id, 
			'event-availability'	: bookacti_get_event_availability( event ), 
			'event-start'			: event.start, 
			'event-end'				: event.end } );

			//Display the dialog to modify the event
			if( event.editable !== false ){
				bookacti_dialog_update_event( event );
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