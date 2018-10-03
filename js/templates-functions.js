// TEMPLATE

// Change default template on change in the select box
function bookacti_switch_template( selected_template_id ) {
	
	if( selected_template_id ) {
		
		// Prevent events to be loaded while templates are switched
		bookacti.load_events = false;
		
		bookacti_start_template_loading();
		
		// Change the default template in the database to the selected one
		$j.ajax({
			url: ajaxurl,
			data: { 'action': 'bookactiSwitchTemplate', 
					'template_id': selected_template_id,
					'nonce': bookacti_localized.nonce_switch_template
				},
			type: 'POST',
			dataType: 'json',
			success: function( response ){
				
				if( response.status === 'success' ) {
					
					// Change the global var
					var is_first_template		= bookacti.selected_template ? false : true;
					bookacti.selected_template	= parseInt( selected_template_id );
					bookacti.hidden_activities	= [];
					
					// Update data array
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'calendars' ]				= [ bookacti.selected_template ];
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ]				= response.bookings;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ]				= response.exceptions;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ]		= response.activities_data;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]			= response.groups_events;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ]			= response.groups_data;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ]	= response.group_categories_data;
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]			= response.template_data;
					
					
					// Unlock dialogs triggering after first template is created and selected
					if( is_first_template ) { 
						bookacti_bind_template_dialogs();
						bookacti_init_groups_of_events();
					}
					
					
					// ACTIVITIES
						// Replace current activities with activities bound to the selected template
						$j( '#bookacti-template-activity-list .activity-row' ).remove();
						$j( '#bookacti-template-activity-list' ).append( response.activities_list );

						bookacti_init_activities();
						

					// GROUPS
						// Replace current groups with groups bound to the selected template
						$j( '#bookacti-group-categories' ).empty();
						$j( '#bookacti-group-categories' ).append( response.groups_list );
						if( response.groups_list === '' ) {
							$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).show();
						} else {
							$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).hide();
						}

						// Empty selected events
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
						$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
						$j( '#bookacti-template-add-first-group-of-events-container' ).hide();

						// Update group categories selectbox
						$j( '#bookacti-group-of-events-category-selectbox option[value!="new"]' ).remove();
						var category_ids = [];
						$j( '#bookacti-group-categories .bookacti-group-category' ).each( function(){
							$j( '#bookacti-group-of-events-category-selectbox' ).append( 
								'<option value="' + $j( this ).data( 'group-category-id' ) + '">' + $j( this ).find( '.bookacti-group-category-title' ).attr( 'title' ) + '</option>'
							);
							if( $j( this ).data( 'visible' ) == 1 ) {
								category_ids.push( $j( this ).data( 'group-category-id' ) );
							}
						});


					// SHORTCODE GENERATOR
						// Update create form link template id
						bookacti_update_create_form_link_template_id( bookacti.selected_template );
						
						// Update create form link activities
						var activity_ids = [];
						$j( '#bookacti-template-activity-list .activity-row .activity-show-hide' ).each( function(){
							if( $j( this ).data( 'activity-visible' ) == 1 ) {
								activity_ids.push( $j( this ).data( 'activity-id' ) );
							}
						});
						bookacti_remove_activities_from_create_form_link( 'all' );
						bookacti_add_activities_to_create_form_link( activity_ids );


					// TEMPLATE SETTINGS
						// Update calendar settings
						bookacti_update_calendar_settings( $j( '#bookacti-template-calendar' ) );
					
					
					// VIEW
						// Go to today's date
						$j( '#bookacti-template-calendar' ).fullCalendar( 'gotoDate', moment() );
						
					
					// EVENTS
						// Empty the calendar
						bookacti_booking_method_clear_events( $j( '#bookacti-template-calendar' ) );
						
						// Load events on calendar
						var view = $j( '#bookacti-template-calendar' ).fullCalendar( 'getView' );
						var interval = { 'start': moment.utc( view.intervalStart ), 'end': moment.utc( view.intervalEnd ).subtract( 1, 'days' ) };
						bookacti_fetch_events_from_interval( $j( '#bookacti-template-calendar' ), interval );
						
						// Re-enable events to load when view changes
						bookacti.load_events = true;
					
					
				} else if( response.status === 'failed' ) {
					var message_error = bookacti_localized.error_switch_template;
					if( response.error === 'not_allowed' ) {
						message_error += '\n' + bookacti_localized.error_not_allowed;
					}
					alert( message_error );
					console.log( response );
				}
			},
			error: function( e ) {
				console.log( 'AJAX ' + bookacti_localized.error_switch_template );
				console.log( e );
			},
			complete: function() { 
				bookacti_stop_template_loading(); 
			}
		});
	
	} else {
		$j( '#bookacti-template-picker' ).val( '' );
		bookacti.selected_template = 0;
		
		// Display the create calendar tuto
		$j( '#bookacti-template-calendar' ).after( $j( "<div id='bookacti-first-template-container'><h2>" + bookacti_localized.create_first_calendar + "</h2><div id='bookacti-add-first-template-button' class='dashicons dashicons-plus-alt' ></div></div>" ) );
		$j( '#bookacti-template-sidebar, #bookacti-calendar-integration-tuto-container' ).addClass( 'bookacti-no-template' );
		$j( '#bookacti-template-calendar' ).remove();
		$j( '.activity-row' ).remove();
		
		// Display tuto if there is no more activities available
		bookacti_display_activity_tuto_if_no_activity_available();
		
		// Display group tuto and reset selected events array
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
		$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
		$j( '#bookacti-template-add-first-group-of-events-container' ).hide();
		$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).show();
		
		// Prevent dialogs to be opened if no template is selected
		bookacti_bind_template_dialogs();
	}
}




// ACTIVITIES

function bookacti_init_activities() {
    $j( '#bookacti-template-activities-container .fc-event' ).each( function() {
		
        // Make the event draggable using jQuery UI
        $j(this).draggable({
            zIndex: 1000,
            revert: true, // will cause the event to go back to its original position after the drag
            revertDuration: 100, // (millisecond) interpolation duration between event drop and original position
            start: function( event, ui ) { 
				bookacti.is_dragging = true; 
				$j( this ).parent().css( 'overflow', 'visible' ); 
			},
            stop: function( event, ui ) { 
				bookacti.is_dragging = false;
				$j( this ).parent().css( 'overflow', '' );
            }
        });
    });
	if( bookacti.blocked_events === true ) {
		$j( '#bookacti-template-activities-container .dashicons' ).addClass( 'bookacti-disabled' );
		$j( '#bookacti-template-activities-container .fc-event' ).addClass( 'bookacti-event-unavailable' );
	}
	
	// Display tuto if there is no more activities available
	bookacti_display_activity_tuto_if_no_activity_available();
}


function bookacti_init_show_hide_activities_switch() {

	$j( '#bookacti-template-activity-list' ).on( 'click', '.activity-show-hide', function() { 

		var activity_id = $j( this ).data( 'activity-id' );
		var idx = $j.inArray( activity_id, bookacti.hidden_activities );

		if( $j( this ).data( 'activity-visible' ) === 1 ) {
			$j( this ).removeClass( 'dashicons-visibility' );
			$j( this ).addClass( 'dashicons-hidden' );
			$j( this ).data( 'activity-visible', 0 );
			$j( this ).attr( 'data-activity-visible', 0 );
			if ( idx === -1 ) { bookacti.hidden_activities.push( activity_id ); }

		} else {
			$j( this ).addClass( 'dashicons-visibility' );
			$j( this ).removeClass( 'dashicons-hidden' );
			$j( this ).data( 'activity-visible', 1 );
			$j( this ).attr( 'data-activity-visible', 1 );
			if ( idx !== -1 ) {  bookacti.hidden_activities.splice( idx, 1 ); }
		}

		$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );

		// Update create form link
		var is_visible = $j( this ).data( 'activity-visible' );
		if( is_visible ) {
			bookacti_add_activities_to_create_form_link( activity_id );
		} else {
			bookacti_remove_activities_from_create_form_link( activity_id );
		}
	});
}




// GROUPS OF EVENTS

function bookacti_init_groups_of_events() {
	if( $j( '#bookacti-template-calendar' ).length ) { 
		// Refresh the display of selected events when you click on the View More link
		$j( '#bookacti-template-calendar' ).on( 'click', '.fc-more', function(){
			bookacti_refresh_selected_events_display();
		});

		// Maybe display groups of events tuto
		$j( '#bookacti-template-calendar' ).on( 'bookacti_select_event bookacti_unselect_event bookacti_unselect_all_events', function(){
			bookacti_maybe_display_add_group_of_events_button();
		});

		// Exit group editing mode
		$j( '#bookacti-template-calendar' ).on( 'bookacti_select_event bookacti_unselect_event', function(){
			if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].length <= 0 ) {
				bookacti_unselect_all_events();
				bookacti_refresh_selected_events_display();
			}
		});
	}
	
	// Expand groups of events
	$j( '#bookacti-group-categories' ).on( 'click', '.bookacti-group-category-title', function(){
		var category_id = $j( this ).parent().data( 'group-category-id' );
		bookacti_expand_collapse_groups_of_events( category_id );
	});
	
	// Select / Unselect events of a group
	$j( '#bookacti-group-categories' ).on( 'click', '.bookacti-group-of-events-title', function(){
		var is_selected	= $j( this ).parents( '.bookacti-group-of-events' ).hasClass( 'bookacti-selected-group' );
		if( ! is_selected ) {
			var group_id = $j( this ).parents( '.bookacti-group-of-events' ).data( 'group-id' );
			bookacti_select_events_of_group( group_id );
		} else {
			bookacti_unselect_all_events();
			bookacti_refresh_selected_events_display();
		}
	});
}


// Add a group category to the categories list
function bookacti_add_group_category( id, title ) {
	
	var plugin_path = bookacti_localized.plugin_path;
	
	// Add the category row
	$j( '#bookacti-group-categories' ).append(
		"<div class='bookacti-group-category'  data-group-category-id='" + id + "' data-show-groups='0' data-visible='1' >"
	+       "<div class='bookacti-group-category-title' title='" + title + "' >"
	+			"<span>" + title + "</span>"
	+		"</div>"
	+		"<div class='bookacti-update-group-category dashicons dashicons-admin-generic' ></div>"
	+		"<div class='bookacti-groups-of-events-editor-list bookacti-custom-scrollbar'>"
	+		"</div>"
	+   "</div>"
	);

	// Add the category to the selectbox
	$j( '#bookacti-group-of-events-category-selectbox' ).append( 
		"<option value='"+ id + "' >" + title + "</option>"
	);

	// Define this category as default
	bookacti.selected_category = id;
}


// Add a group of events to a category list
function bookacti_add_group_of_events( id, title, category_id ) {
	
	// If the category id is not found, add a category
	if( ! $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).length ) {
		bookacti_add_group_category( category_id, 'Untitled' );
	}
	
	// Add the group row to the category
	var group_short_title = title.length > 16 ? title.substr( 0, 16 ) + '&#8230;' : title;
	$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).append(
		"<div class='bookacti-group-of-events' data-group-id='" + id + "' >"
	+		"<div class='bookacti-group-of-events-title' title='" + title + "'>"
	+			group_short_title
	+		"</div>"
	+		"<div class='bookacti-update-group-of-events dashicons dashicons-admin-generic' ></div>"
	+	"</div>"
	);
	
	// Expand the group category
	bookacti_expand_collapse_groups_of_events( category_id, 'expand', true );
}


// Select all events of a group onto the calendar
function bookacti_select_events_of_group( group_id ) {
	
	if( ! group_id ) { return false; }
	
	// Unselect the events
	bookacti_unselect_all_events();
	
	// Empty the selected events and refresh them
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
	
	// Change view to the 1st event selected to make sure that at least 1 event is in the view
	if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ] !== 'undefined' ) {
		$j( '#bookacti-template-calendar' ).fullCalendar( 'gotoDate', bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ][0]['start'] );
	}

	// Select the events of the group
	var are_selected = true;
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ], function( i, event ){
		var is_selected = bookacti_select_event( event );
		if( is_selected === false ) { are_selected = false; }
	});
	
	// Change group settings icon and wait for the user to validate the selected events
	if( are_selected ) {
		$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).addClass( 'bookacti-selected-group' );
	}
	
	return are_selected;
}


// Select an event
function bookacti_select_event( event ) {
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) )
	||  ( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ] === 'undefined' ) ) {
		return false;
	}
	
	var activity_id = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'activity_id' ];
	
	// Format event object
	event = {
		'id': event.id,
		'title': event.title ? event.title : $j( '.activity-row .fc-event[data-activity-id="' + activity_id + '"]' ).text(),
		'start': moment( event.start ),
		'end': moment( event.end )
	};
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	
	// Format the selected event (because of popover, the same event can appears twice)
	elements.addClass( 'bookacti-selected-event' );
	
	elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );
	elements.find( '.bookacti-event-actions' ).show();
	elements.find( '.bookacti-event-action-select' ).show();

	// Keep picked events in memory 
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].push({ 
		'id'			: event.id,
		'title'			: event.title, 
		'start'			: event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
		'end'			: event.end.format( 'YYYY-MM-DD HH:mm:ss' ) 
	});

	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_select_event', [ event ] );
	
	return true;
}


// Unselect an event
function bookacti_unselect_event( event, start, all ) {
	
	// Determine if all event should be unselected
	all = all ? true : false;
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' && ! $j.isNumeric( event ) )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) )
	||  ( $j.isNumeric( event ) && ! all && typeof start === 'undefined' ) ) {
		return false;
	}
	
	if( typeof event !== 'object' ) {
		// Format start values to object
		var event_id = event;
		start	= start instanceof moment ? start : moment( start ).isValid() ? moment( start ) : false;
		event	= {
			'id': event_id,
			'start': start
		};
	}
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"]' );
	if( ! all && event.start ) {
		elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	}
	
	// Format the selected event(s)
	elements.removeClass( 'bookacti-selected-event' );
	
	// Specific treatment for calendar editor
	elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	elements.find( '.bookacti-event-action-select' ).hide();
	
	// Remove selected event(s) from memory 
	var selected_events = $j.grep( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( selected_event ){
		if( selected_event.id == event.id 
		&&  (  all 
			|| selected_event.start.substr( 0, 10 ) === event.start.format( 'YYYY-MM-DD' ) ) ) {
			
			// Unselect the event
			return false;
		}
		// Keep the event selected
		return true;
	});
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = selected_events;
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_event', [ event, all ] );
}


// Unselect all events
function bookacti_unselect_all_events() {
	// Unselect on screen events
	$j( '.fc-event' ).removeClass( 'bookacti-selected-event' );
	
	// Specific treatment for calendar editor
	$j( '.fc-event' ).find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	$j( '.fc-event' ).find( '.bookacti-event-action-select' ).hide();
	
	// Remove selected event(s) from memory 
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
	
	// Remove "selected" classes
	$j( '.bookacti-group-of-events.bookacti-selected-group' ).removeClass( 'bookacti-selected-group' );
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_all_events' );
}


// Make sure selected events appears as selected and vice-versa
function bookacti_refresh_selected_events_display() {
	
	$j( '.fc-event' ).removeClass( 'bookacti-selected-event' );
	
	// Specific treatment for calendar editor
	$j( '.fc-event .bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, event ) {
		var element = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start + '"]' );
		// Format selected events
		element.addClass( 'bookacti-selected-event' );
		
		// Specific treatment for calendar editor
			// Check the box
			element.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );

			// Show select actions
			element.find( '.bookacti-event-actions' ).show();
			element.find( '.bookacti-event-action[data-hide-on-mouseout="1"]' ).hide();
			element.find( '.bookacti-event-action-select' ).show();
	});
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_refresh_selected_events' );
}




// Create form link

// Update create form link template_id
function bookacti_update_create_form_link_template_id( new_template_id ) {
	// Replace the old template id with the new one
	$j( '#bookacti-calendar-integration-tuto-container input[name="calendar_field[calendars][]"]' ).val( new_template_id );
	bookacti_update_create_form_link_url();
}


// Add activities to the create form link parameters
function bookacti_add_activities_to_create_form_link( activity_ids ) {
	if( $j.isNumeric( activity_ids ) ) { activity_ids = [ activity_ids ]; }
	// Add activity ids
	$j.each( activity_ids, function( i, activity_id ){
		var field = "<input type='hidden' name='calendar_field[activities][]' value='" + activity_id + "'/>";
		$j( '#bookacti-calendar-integration-tuto-container' ).prepend( field );
	});
	bookacti_update_create_form_link_url();
}


// Remove activities to the create form link parameters
function bookacti_remove_activities_from_create_form_link( activity_ids ) {
	// Remove all activity ids
	if( activity_ids === 'all' ) {
		$j( '#bookacti-calendar-integration-tuto-container input[name="calendar_field[activities][]"]' ).remove();
	} 
	
	// Remove specific activity ids
	else {
		if( $j.isNumeric( activity_ids ) ) { activity_ids = [ activity_ids ]; }
		$j.each( activity_ids, function( i, activity_id ){
			$j( '#bookacti-calendar-integration-tuto-container input[name="calendar_field[activities][]"][value="' + activity_id + '"]' ).remove();
		});
	}
	bookacti_update_create_form_link_url();
}


// Update the URL of the create form link according to the hidden inputs
function bookacti_update_create_form_link_url() {
	var base_url	= $j( '#bookacti-create-form-link' ).data( 'base-url' );
	var parameters	= $j( '#bookacti-calendar-integration-tuto-container input' ).serialize();
	$j( '#bookacti-create-form-link' ).attr( 'href', base_url + '?' + parameters );
}




// CALENDAR and EVENTS

// Fetch events on template calendar
function bookacti_fetch_events_on_template( event_id, interval ) {
   
	event_id = event_id || null;
	interval = interval || bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
	
	if( $j.isEmptyObject( interval ) ) {
		var current_view = $j( '#bookacti-template-calendar' ).fullCalendar( 'getView' );
		interval = bookacti_get_new_interval_of_events( $j( '#bookacti-template-calendar' ), current_view );
	}
	
	// Update events interval before success to prevent to fetch the same interval twice
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ] = bookacti_get_extended_events_interval( $j( '#bookacti-template-calendar' ), interval );
	
    bookacti_start_template_loading(); 
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiFetchTemplateEvents', 
                'template_id': bookacti.selected_template, 
				'event_id': event_id,
				'interval': interval,
				'nonce': bookacti_localized.nonce_fetch_template_events
			},
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				
				// Extend or replace the events data array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ] ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ] = response.events_data;
				} else {
					$j.extend( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ], response.events_data );
				}
				
				// Load new events on calendar
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
				
			} else if( response.error === 'not_allowed' ) {
				
				alert( bookacti_localized.error_display_event + '\n' + bookacti_localized.error_not_allowed );
				console.log( response );
			}
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error_display_event );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_template_loading();
		}
    });
}


// Refresh completly the calendar
function bookacti_refetch_events_on_template( event ) {
	event = event || null;
	var event_id = event != null ? event.id : null;

	// Clear the calendar
	bookacti_booking_method_clear_events( $j( '#bookacti-template-calendar' ), event );

	// Fetch events from the selected template
	var min_interval	= $j( '#bookacti-template-calendar' ).fullCalendar( 'getView' );
	var interval		= bookacti_get_new_interval_of_events( $j( '#bookacti-template-calendar' ), min_interval );
	bookacti_fetch_events_on_template( event_id, interval );
}


// Delete event on the calendar
function bookacti_delete_event( event ) {
	// Unselect the event if it was selected
	bookacti_unselect_event( event );

	// Delete this event from all groups
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_events ){
		var remaining_group_events = $j.grep( group_events, function( group_event ){
			if( group_event && group_event.id == event.id ) {
				return false;
			}
			return true;
		});

		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ] = remaining_group_events;
	});

	// We use both event._id and event.id to make sure both existing and newly added event are deleted
	if( event._id !== undefined ) {
		if( event._id.indexOf('_') >= 0 ) {
			$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event._id );
		}
	}
	$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event.id );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'refetchEvents' );
}



// DIALOGS

// Launch dialogs
function bookacti_bind_template_dialogs() {
	if( bookacti.selected_template ) {
		$j( '#bookacti-update-template' ).off().on( 'click', 'span', function() { 
			bookacti_dialog_update_template( bookacti.selected_template ); 
		}); 
		$j( '#bookacti-template-activities-container' ).off().on( 'click', '#bookacti-insert-activity, #bookacti-template-add-first-activity-button', function() {
			if( $j( '#bookacti-template-picker option' ).length > 1 ) {
				bookacti_dialog_choose_activity_creation_type();
			} else {
				bookacti_dialog_create_activity();
			}
		});
	} else {
		$j( '#bookacti-update-template' ).off().on( 'click', 'span', function() {
			alert( bookacti_localized.error_no_template_selected );
		});
		$j( '#bookacti-template-activities-container' ).off().on( 'click', '#bookacti-insert-activity, #bookacti-template-add-first-activity-button', function() {
			alert( bookacti_localized.error_no_template_selected );
		});
	}
}


// Update Exception
function bookacti_update_exceptions( excep_template_id, event ) {
    excep_template_id = excep_template_id || bookacti.selected_template;
	event = event || null;
    
    var event_id= null;
    if( event !== null ) { event_id = event.id; }

    bookacti_start_template_loading();
    
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiGetExceptions', 
                'template_id': excep_template_id, 
                'event_id': event_id,
				'nonce': bookacti_localized.nonce_get_exceptions
			},
        dataType: 'json',
        success: function( response ){
            
            if( response.status === 'success' ) {
                if( event === null ) {
                    bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ] = response.exceptions;
                } else {
                    bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ] = response.exceptions[ event_id ];
                }
                
            } else if( response.status === 'no_exception' ) {
                if( event === null ) {
                    bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ] = [];
                } else {
                    bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ] = [];
                }
                
            } else {
				var message_error = bookacti_localized.error_retrieve_exceptions;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
            }
			
			// Refresh events to take new exceptions into account
			$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
        },
        error: function( e ){
            alert( 'AJAX ' + bookacti_localized.error_retrieve_exceptions );
            console.log( e );
        },
        complete: function() { 
            bookacti_stop_template_loading();
        }
    });
}


// Determine if event is locked or not
function bookacti_is_locked_event( event_id ) {
    var is_locked = false;
    $j.each( lockedEvents, function( i, blocked_event_id ) {
        if( parseInt( event_id ) === parseInt( blocked_event_id ) ) {
            is_locked = true;
            return false;
        }
    });
    return is_locked;
}


// Unbind occurences of a booked event
function bookacti_unbind_occurrences( event, occurences ) {
	
    bookacti_start_template_loading();
    
    $j.ajax({
        url: ajaxurl, 
        data: { 'action': 'bookactiUnbindOccurences', 
                'unbind': occurences,
                'event_id': event.id,
                'event_start': event.start.format( 'YYYY-MM-DD HH:mm:ss' ),
                'event_end': event.end.format( 'YYYY-MM-DD HH:mm:ss' ),
				'interval': bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ],
				'nonce': bookacti_localized.nonce_unbind_occurences
            },
        type: 'POST',
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ){
				
				var new_event_id = response.new_event_id;
				
				// Unselect the event or occurences of the event
				bookacti_unselect_event( event, undefined, true );
				
				// Update affected calendar data
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ]						= response.exceptions;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]					= response.groups_events;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ]	= response.events_data[ new_event_id ];
				if( typeof response.events_data[ event.id ] !== 'undefined' ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ]	= response.events_data[ event.id ];
				}
				
				// If we unbound all booked occurences, we need to replace the old events by the new ones
				if( occurences === 'booked' ) {
					$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event.id );
				}
				
				// Load new events on calendar
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
				
				// Calling addEventSource will rerender events and then new exceptions will be taken into account
				
            } else {
				var message_error = bookacti_localized.error_unbind_occurences;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( message_error );
            }
        },
        error: function( e ){
            alert( 'AJAX ' + bookacti_localized.error_unbind_occurences );
            console.log( e );
        },
        complete: function() { 
            bookacti_stop_template_loading();
        }
    });

    //Close the modal dialog
    $j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'close' );
}


// Start a loading (or keep on loading if already loading)
function bookacti_start_template_loading() {
	
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 ) {
		bookacti_enter_template_loading_state();
	}
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]++;
}

// Stop a loading (but keep on loading if there are other loadings )
function bookacti_stop_template_loading() {
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]--;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] = Math.max( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ], 0 );
	
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 ) {
		bookacti_exit_template_loading_state();
	}
}

// Enter loading state and prevent user from doing anything else
function bookacti_enter_template_loading_state() {
	
	var loading_div =	'<div class="bookacti-loading-alt">' 
							+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
							+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
						+ '</div>';
	
	if( ! $j.isNumeric( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] ) ) {
		bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] = 0;
	}
	
	if( $j( '#bookacti-template-calendar' ).find( '.fc-view-container' ).length ) {
		if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 || ! $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-overlay' ).length ) {
			 $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).remove();
			bookacti_enter_calendar_loading_state( $j( '#bookacti-template-calendar' ) );
		}
	} else if( !  $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).length ) {
		 $j( '#bookacti-template-calendar' ).append( loading_div );
	}
	
	
	bookacti.blocked_events = true;
	$j( '#bookacti-template-sidebar .dashicons' ).addClass( 'bookacti-disabled' );
	$j( '.bookacti-template-dialogs' ).find( 'input, select, button' ).attr( 'disabled', true );
	$j( '#bookacti-template-picker' ).attr( 'disabled', true );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
}

// Exit loading state and allow user to keep editing templates
function bookacti_exit_template_loading_state( force_exit ) {
	
	force_exit = force_exit || false;
	
	if( force_exit ) { bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] = 0; }
	
	bookacti_exit_calendar_loading_state( $j( '#bookacti-template-calendar' ) );
	$j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).remove();
	
	bookacti.blocked_events = false;
	$j( '#bookacti-template-sidebar .dashicons' ).removeClass( 'bookacti-disabled' );
	$j( '.bookacti-template-dialogs' ).find( 'input, select, button' ).attr( 'disabled', false );
	$j( '#bookacti-template-picker' ).attr( 'disabled', false );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
}


// Display tuto if there is no more activities available
function bookacti_display_activity_tuto_if_no_activity_available() {
	if( $j( '#bookacti-template-first-activity-container' ).length ) {
		if( ! $j( '.activity-row' ).length  ) {
			$j( '#bookacti-template-first-activity-container' ).show();
		} else {
			$j( '#bookacti-template-first-activity-container' ).hide();
		}
	}
}


// Display tuto if there is there is at least two events selected and no group categories yet
function bookacti_maybe_display_add_group_of_events_button() {
	if( $j( '#bookacti-template-add-first-group-of-events-container' ).length ) {
		
		// If there are at least 2 selected events...
		if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].length >= 2 ) {
			$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'visible' );
			// And there are no groups of events yet
			if( ! $j( '.bookacti-group-category' ).length ) {
				$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).hide();
				$j( '#bookacti-template-add-first-group-of-events-container' ).show();
			}
			
		// Else, hide the add group category button
		} else {
			$j( '#bookacti-template-add-first-group-of-events-container' ).hide();
			$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
			if( ! $j( '.bookacti-group-category' ).length ) {
				$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).show();
			}
		}
	}
}


// Expand or Collapse groups of events
function bookacti_expand_collapse_groups_of_events( category_id, force_to, one_by_one ) {
	one_by_one	= one_by_one ? true : false;
	force_to	= $j.inArray( force_to, [ 'expand', 'collapse' ] ) >= 0 ? force_to : false;
	
	var is_shown = $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups' );
	if( ( is_shown || force_to === 'collapse' ) && force_to !== 'expand' ) {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).slideUp( 200 );
	} else if( ( ! is_shown || force_to === 'expand' ) && force_to !== 'collapse' ) {
		
		// Collapse the others if one_by_one is set to true
		if( one_by_one ) { bookacti_expand_collapse_all_groups_of_events( 'collapse', category_id ); }
		
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).slideDown( 200 );
	}
}


// Expand or Collapse all group categories
function bookacti_expand_collapse_all_groups_of_events( action, exceptions ) {
	
	exceptions = exceptions ? exceptions : false;
	
	var categories_selector = '.bookacti-group-category';
	if( exceptions ) {
		if( $j.isArray( exceptions ) ) {
			$j.each( exceptions, function( i, exception ){
				categories_selector += ':not([data-group-category-id="' + exception + '"])';
			});
		} else {
			categories_selector = '.bookacti-group-category:not([data-group-category-id="' + exceptions + '"])';
		}
	}
	
	if( action === 'collapse' ) {
		$j( categories_selector ).attr( 'data-show-groups', 0 );
		$j( categories_selector ).data( 'show-groups', 0 );
		$j( categories_selector + ' .bookacti-groups-of-events-editor-list' ).slideUp( 200 );
	} else if( action === 'expand' ) {
		$j( categories_selector ).attr( 'data-show-groups', 1 );
		$j( categories_selector ).data( 'show-groups', 1 );
		$j( categories_selector + ' .bookacti-groups-of-events-editor-list' ).slideDown( 200 );
	}
}


// Load activities bound to selected template
function bookacti_load_activities_bound_to_template( selected_template_id ) {
	
	if( parseInt( selected_template_id ) !== parseInt( bookacti.selected_template ) ) {

		$j( '#bookacti-activities-bound-to-template .bookacti-form-error' ).remove();

		bookacti_start_template_loading();

		$j.ajax({
			url: ajaxurl, 
			data: { 'action': 'bookactiGetActivitiesByTemplate', 
					'selected_template_id': selected_template_id,
					'current_template_id': bookacti.selected_template,
					'nonce': bookacti_localized.nonce_get_activities_by_template
				},
			type: 'POST',
			dataType: 'json',
			success: function(response) {
				// Empty current list of activity
				$j( 'select#activities-to-import' ).empty();
				
				if( response.status === 'success' ) {
					// Fill the available activities select box
					var activity_options = '';
					$j.each( response.activities, function( activity_id, activity ){
						if( ! $j( '#bookacti-template-activity-list .activity-row .fc-event[data-activity-id="' + activity_id + '"]' ).length ) {
							activity_options += '<option value="' + activity_id + '" >' + activity.title + '</option>';
						}
					});
					if( activity_options !== '' ) {
						$j( 'select#activities-to-import' ).append( activity_options );
					} else {
						$j( '#bookacti-activities-bound-to-template' ).append( '<div class="bookacti-form-error">' + bookacti_localized.error_no_avail_activity_bound + '</div>' );
					}
				} else if ( response.status === 'no_activity' ) {
					$j( '#bookacti-activities-bound-to-template' ).append( '<div class="bookacti-form-error">' + bookacti_localized.error_no_avail_activity_bound + '</div>' );
				} else {
					var message_error = bookacti_localized.error_retrieve_activity_bound;
					if( response.error === 'not_allowed' ) {
						message_error += '\n' + bookacti_localized.error_not_allowed;
					}
					console.log( response );
					alert( message_error );
				}
			},
			error: function( e ){
				alert( 'AJAX ' + bookacti_localized.error_retrieve_activity_bound );
				console.log( e );
			},
			complete: function() { 
				bookacti_stop_template_loading(); 
			}
		});
	}
}


/**
 * Show event actions
 */
function bookacti_show_event_actions( element ) {
	element.addClass( 'bookacti-event-over' );
	element.find( '.bookacti-event-action' ).show();
}


/**
 * Hide event actions
 */
function bookacti_hide_event_actions( element, event ) {
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
}