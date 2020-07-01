// TEMPLATE

/**
 * Change default template on change in the select box
 * @version 1.8.5
 * @param {int} selected_template_id
 */
function bookacti_switch_template( selected_template_id ) {
	if( ! selected_template_id ) {
		$j( '#bookacti-template-picker' ).val( '' );
		bookacti.selected_template = 0;
		
		// Display the create calendar tuto
		$j( '#bookacti-first-template-container' ).show();
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
		return;
	}
	
	$j( '#bookacti-template-picker' ).val( selected_template_id );

	// Prevent events to be loaded while templates are switched
	bookacti.load_events = false;
	var attributes = bookacti.booking_system[ 'bookacti-template-calendar' ];

	bookacti_start_template_loading();

	// Change the default template in the database to the selected one
	$j.ajax({
		url: ajaxurl,
		data: { 
			'action': 'bookactiSwitchTemplate', 
			'template_id': selected_template_id,
			'attributes': JSON.stringify( attributes ),
			'nonce': $j( '#nonce_edit_template' ).val()
		},
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				// Change the global var
				var is_first_template		= bookacti.selected_template ? false : true;
				var loading_number_temp		= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ];
				bookacti.selected_template	= parseInt( selected_template_id );
				bookacti.hidden_activities	= [];

				// Update data array
				bookacti.booking_system[ 'bookacti-template-calendar' ]	= response.booking_system_data;

				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]		= [];
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]			= [];
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]			= loading_number_temp;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'method' ]					= 'calendar';
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events' ]			= true;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events_bookable' ]	= true;

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


				// TEMPLATE SETTINGS
					// Update calendar settings
					$j( '#bookacti-template-calendar' ).replaceWith( '<div id="bookacti-template-calendar" class="bookacti-calendar"></div>' );
					bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );


				// VIEW
					// Go to today's date
					$j( '#bookacti-template-calendar' ).fullCalendar( 'gotoDate', moment.utc() );


				// EVENTS
					// Empty the calendar
					bookacti_booking_method_clear_events( $j( '#bookacti-template-calendar' ) );

					// Load events on calendar
					var view = $j( '#bookacti-template-calendar' ).fullCalendar( 'getView' );
					var interval = { 'start': moment.utc( moment.utc( view.intervalStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ).locale( 'en' ), 'end': moment.utc( moment.utc( view.intervalEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' ).locale( 'en' ) };
					bookacti_fetch_events_from_interval( $j( '#bookacti-template-calendar' ), interval );

					// Re-enable events to load when view changes
					bookacti.load_events = true;


			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				alert( error_message );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading(); 
		}
	});
}




// ACTIVITIES

/**
 * Initialize draggable activities
 * @version 1.7.17
 */
function bookacti_init_activities() {
    $j( '#bookacti-template-activities-container .fc-event' ).each( function() {
        // Make the event draggable using jQuery UI
        $j( this ).draggable({
            zIndex: 1000,
            revert: true,
            revertDuration: 100,
			appendTo: 'parent',
			helper: 'clone',
            start: function( event, ui ) { 
				bookacti.is_dragging = true; 
				$j( this ).css( 'visibility', 'hidden' ); 
				$j( this ).parent().css( 'overflow', 'visible' ); 
			},
            stop: function( event, ui ) { 
				bookacti.is_dragging = false;
				$j( this ).css( 'visibility', 'visible' );
				$j( this ).parent().css( 'overflow', '' );
            }
        });
    });
	if( bookacti.blocked_events === true ) {
		$j( '#bookacti-template-activities-container .dashicons' ).addClass( 'bookacti-disabled' );
		$j( '#bookacti-template-activities-container .fc-event' ).addClass( 'bookacti-event-unavailable' );
	}
	
	// Set a max height
	if( $j( '#bookacti-template-activity-list' ).outerHeight() > 200 ) {
		$j( '#bookacti-template-activity-list' ).css( 'height', 200 );
	} else {
		$j( '#bookacti-template-activity-list' ).css( 'height', 'auto' );
	}
	
	// Display tuto if there is no more activities available
	bookacti_display_activity_tuto_if_no_activity_available();
	
	// Update the show / hide icons
	bookacti_refresh_show_hide_activities_icons();
}


/**
 * Show / hide events when clicking the icon next to the activity
 * @version 1.7.17
 */
function bookacti_init_show_hide_activities_switch() {
	$j( 'body' ).on( 'click', '#bookacti-template-activity-list .activity-show-hide', function() { 
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
	});
}


/**
 * Update the show / hide icon next to the activity to reflect its current state
 * @since 1.7.17
 */
function bookacti_refresh_show_hide_activities_icons() {
	// Make all icons "visible"
	var icons = $j( '#bookacti-template-activity-list .activity-show-hide' );
	icons.addClass( 'dashicons-visibility' );
	icons.removeClass( 'dashicons-hidden' );
	icons.data( 'activity-visible', 1 );
	icons.attr( 'data-activity-visible', 1 );
	
	// Set the hidden activities icons to "hidden"
	$j.each( bookacti.hidden_activities, function( i, activity_id ) { 
		if( $j( '#bookacti-template-activity-list .activity-show-hide[data-activity-id="' + activity_id + '"]' ).length ) {
			var icon = $j( '#bookacti-template-activity-list .activity-show-hide[data-activity-id="' + activity_id + '"]' );
			icon.removeClass( 'dashicons-visibility' );
			icon.addClass( 'dashicons-hidden' );
			icon.data( 'activity-visible', 0 );
			icon.attr( 'data-activity-visible', 0 );
		}
	});
}




// GROUPS OF EVENTS

/**
 * Init groups of events
 * @version 1.7.6
 */
function bookacti_init_groups_of_events() {
	// Refresh the display of selected events when you click on the View More link
	$j( 'body' ).on( 'click', '#bookacti-template-calendar .fc-more', function(){
		bookacti_refresh_selected_events_display();
	});

	// Maybe display groups of events tuto
	$j( 'body' ).on( 'bookacti_select_event bookacti_unselect_event bookacti_unselect_all_events', '#bookacti-template-calendar', function(){
		bookacti_maybe_display_add_group_of_events_button();
	});

	// Exit group editing mode
	$j( 'body' ).on( 'bookacti_select_event bookacti_unselect_event', '#bookacti-template-calendar', function(){
		if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].length <= 0 ) {
			bookacti_unselect_all_events();
			bookacti_refresh_selected_events_display();
		}
	});
	
	// Expand groups of events
	$j( 'body' ).on( 'click', '#bookacti-group-categories .bookacti-group-category-title', function(){
		var category_id = $j( this ).parent().data( 'group-category-id' );
		bookacti_expand_collapse_groups_of_events( category_id );
	});
	
	// Select / Unselect events of a group
	$j( 'body' ).on( 'click', '#bookacti-group-categories .bookacti-group-of-events-title', function(){
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


/**
 * Add a group category to the categories list
 * @version 1.7.6
 * @param {string} id
 * @param {string} title
 */
function bookacti_add_group_category( id, title ) {
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


/**
 * Add a group of events to a category list
 * @version 1.7.3
 * @param {int} id
 * @param {string} title
 * @param {int} category_id
 */
function bookacti_add_group_of_events( id, title, category_id ) {
	
	// If the category id is not found, add a category
	if( ! $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).length ) {
		bookacti_add_group_category( category_id, 'Untitled' );
	}
	
	// Add the group row to the category
	$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).append(
		"<div class='bookacti-group-of-events' data-group-id='" + id + "' >"
	+		"<div class='bookacti-group-of-events-title' title='" + title + "'>"
	+			title
	+		"</div>"
	+		"<div class='bookacti-update-group-of-events dashicons dashicons-admin-generic' ></div>"
	+	"</div>"
	);
	
	// Expand the group category
	bookacti_expand_collapse_groups_of_events( category_id, 'expand', true );
}


/**
 * Select all events of a group onto the calendar
 * @param {int} group_id
 */
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


/**
 * Select an event
 * @version 1.8.5
 * @param {Object} raw_event
 */
function bookacti_select_event( raw_event ) {
	// Return false if we don't have both event id and event start
	if( ( typeof raw_event !== 'object' )
	||  ( typeof raw_event === 'object' && ( typeof raw_event.id === 'undefined' || typeof raw_event.start === 'undefined' ) ) ) {
		return false;
	}
	
	var activity_title = '';
	if( ! raw_event.title && typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ raw_event.id ] !== 'undefined' ) {
		var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ raw_event.id ];
		if( event_data.title ) {
			activity_title = event_data.title;
		} else if( $j( '.activity-row .fc-event[data-activity-id="' + event_data.activity_id + '"]' ).length ) {
			activity_title = $j( '.activity-row .fc-event[data-activity-id="' + event_data.activity_id + '"]' ).text();
		}
	}
	
	// Format event object
	var event = {
		'id': raw_event.id,
		'title': raw_event.title ? raw_event.title : activity_title,
		'start': moment.utc( raw_event.start ).clone().locale( 'en' ),
		'end': moment.utc( raw_event.end ).clone().locale( 'en' )
	};
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	
	// Format the selected event (because of popover, the same event can appears twice)
	if( elements.length ) {
		elements.addClass( 'bookacti-selected-event' );
		elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );
		elements.find( '.bookacti-event-actions' ).show();
		elements.find( '.bookacti-event-action-select' ).show();
	}

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


/**
 * Unselect an event
 * @version 1.8.5
 * @param {object} event
 * @param {boolean} all
 * @returns {boolean}
 */
function bookacti_unselect_event( event, all ) {
	// Determine if all event should be unselected
	all = all ? true : false;
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' && ! $j.isNumeric( event ) )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) )
	||  ( $j.isNumeric( event ) && ! all ) ) {
		return false;
	}
	
	// Format start values to object
	if( typeof event !== 'object' ) {
		var event_id = event;
		event = { 'id': event_id };
	}
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_start_date = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' );
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-start="' + event_start + '"]' );
	
	if( elements.length ) {
		// Format the selected event(s)
		elements.removeClass( 'bookacti-selected-event' );

		// Specific treatment for calendar editor
		elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', false );
		elements.find( '.bookacti-event-action-select' ).hide();
	}

	// Remove selected event(s) from memory 
	var selected_events = $j.grep( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( selected_event ){
		if( selected_event.id == event.id 
		&&  (  all 
			|| selected_event.start.substr( 0, 10 ) === event_start_date ) ) {
			
			// Unselect the event
			return false;
		}
		// Keep the event selected
		return true;
	});
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = selected_events;
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_event', [ event, all ] );
	
	return true;
}


/**
 * Unselect all events
 */
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


/**
 * Make sure selected events appears as selected and vice-versa
 */
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




// CREATE FORM

/**
 * Update the template_id parameter of the URL to create a booking form
 * @version 1.7.17
 * @param {int} new_template_id
 */
function bookacti_update_create_form_link_template_id( new_template_id ) {
	// Replace the old template id with the new one
	$j( '#bookacti-calendar-integration-tuto-container input[name="calendar_field[calendars]"]' ).val( new_template_id );
	bookacti_update_create_form_link_url();
}

/**
 * Update the URL of the create a booking form according to the hidden inputs
 * @version 1.7.17
 */
function bookacti_update_create_form_link_url() {
	var base_url	= $j( '#bookacti-create-form-link' ).data( 'base-url' );
	var parameters	= $j( '#bookacti-calendar-integration-tuto-container input' ).serialize();
	$j( '#bookacti-create-form-link' ).attr( 'href', base_url + '?' + parameters );
}




// CALENDAR and EVENTS

/**
 * Fetch events on template calendar
 * @version 1.8.0
 * @param {int} event_id
 * @param {object} interval
 */
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
        data: { 
			'action': 'bookactiFetchTemplateEvents', 
			'template_id': bookacti.selected_template, 
			'event_id': event_id,
			'interval': interval,
			'nonce': $j( '#nonce_fetch_template_events' ).val()
		},
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				// Extend or replace the events array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events' ] ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events' ] = response.events;
				} else {
					$j.extend( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events' ], response.events );
				}
				
				// Extend or replace the events data array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ] ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ] = response.events_data;
				} else {
					$j.extend( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ], response.events_data );
				}
				
				// Load new events on calendar
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
				
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				alert( error_message );
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_template_loading();
		}
    });
}


/**
 * Refresh completly the template calendar
 * @param {object} event
 */
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


/**
 * Delete event on the calendar
 * @param {object} event
 */
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

/**
 * Bind template dialog to their action buttons
 */
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


/**
 * Check if the event is locked
 * @param {int} event_id
 */
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


/**
 * Unbind occurrences of a booked event
 * @version 1.8.5
 * @param {object} event
 * @param {string} occurrences
 */
function bookacti_unbind_occurrences( event, occurrences ) {
	var data = { 
		'action': 'bookactiUnbindOccurences', 
		'unbind': occurrences,
		'event_id': event.id,
		'event_start': moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		'event_end': moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
		'interval': bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ],
		'nonce': $j( '#nonce_unbind_occurrences' ).val()
	};

	$j( '#bookacti-template-container' ).trigger( 'bookacti_unbind_occurrences_before', [ data, event, occurrences ] );

	bookacti_start_template_loading();

	$j.ajax({
		url: ajaxurl, 
		data: data,
		type: 'POST',
		dataType: 'json',
		success: function( response ){
			if( response.status === 'success' ) {
				var new_event_id = response.new_event_id;

				// Unselect the event or occurrences of the event
				bookacti_unselect_event( event, true );

				// Update affected calendar data
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ]						= response.exceptions;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ]					= response.groups_events;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ]	= response.events_data[ new_event_id ];
				if( typeof response.events_data[ event.id ] !== 'undefined' ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ]	= response.events_data[ event.id ];
				}

				// If we unbound all booked occurrences, we need to replace the old events by the new ones
				if( occurrences === 'booked' ) {
					$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event.id );
				}

				// Load new events on calendar
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );

				// Calling addEventSource will rerender events and then new exceptions will be taken into account
				
				$j( '#bookacti-template-container' ).trigger( 'bookacti_occurrences_unbound', [ response, data, event, occurrences ] );
				
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				alert( error_message );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ){
			alert( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading();
		}
	});

	// Close the modal dialog
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'close' );
}


/**
 * Start a template loading (or keep on loading if already loading)
 */
function bookacti_start_template_loading() {
	
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 ) {
		bookacti_enter_template_loading_state();
	}
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]++;
}


/**
 * Stop a template loading (but keep on loading if there are other loadings)
 */
function bookacti_stop_template_loading() {
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]--;
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] = Math.max( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ], 0 );
	
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 ) {
		bookacti_exit_template_loading_state();
	}
}


/**
 * Enter template loading state and prevent user from doing anything else
 * @version 1.7.18
 */
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
	$j( '.bookacti-template-dialog' ).find( 'input, select, button' ).attr( 'disabled', true );
	$j( '#bookacti-template-picker' ).attr( 'disabled', true );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
}


/**
 * Exit template loading state and allow user to continue editing templates
 * @version 1.7.18
 * @param {boolean} force_exit
 */
function bookacti_exit_template_loading_state( force_exit ) {
	
	force_exit = force_exit || false;
	
	if( force_exit ) { bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] = 0; }
	
	bookacti_exit_calendar_loading_state( $j( '#bookacti-template-calendar' ) );
	$j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).remove();
	
	bookacti.blocked_events = false;
	$j( '#bookacti-template-sidebar .dashicons' ).removeClass( 'bookacti-disabled' );
	$j( '.bookacti-template-dialog' ).find( 'input, select, button' ).attr( 'disabled', false );
	$j( '#bookacti-template-picker' ).attr( 'disabled', false );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
}


/**
 * Display tuto if there is no more activities available
 */
function bookacti_display_activity_tuto_if_no_activity_available() {
	if( $j( '#bookacti-template-first-activity-container' ).length ) {
		if( ! $j( '.activity-row' ).length  ) {
			$j( '#bookacti-template-first-activity-container' ).show();
		} else {
			$j( '#bookacti-template-first-activity-container' ).hide();
		}
	}
}


/**
 * Display tuto if there is there is at least two events selected and no group categories yet
 */
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


/**
 * Expand or Collapse groups of events
 * @version 1.7.14
 * @param {int} category_id
 * @param {string} force_to
 * @param {boolean} one_by_one
 */
function bookacti_expand_collapse_groups_of_events( category_id, force_to, one_by_one ) {
	one_by_one	= one_by_one ? true : false;
	force_to	= $j.inArray( force_to, [ 'expand', 'collapse' ] ) >= 0 ? force_to : false;
	
	var is_shown = $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups' );
	if( ( is_shown || force_to === 'collapse' ) && force_to !== 'expand' ) {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).slideUp( 200, function() {
			bookacti_set_editor_group_of_events_max_height( category_id );
		});
	} else if( ( ! is_shown || force_to === 'expand' ) && force_to !== 'collapse' ) {
		
		// Collapse the others if one_by_one is set to true
		if( one_by_one ) { bookacti_expand_collapse_all_groups_of_events( 'collapse', category_id ); }
		
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).slideDown( 200, function() {
			bookacti_set_editor_group_of_events_max_height( category_id );
		});
	}
}


/**
 * Set the category list max height and the events groups max height dynamically
 * @since 1.7.14
 * @param {int} category_id
 */
function bookacti_set_editor_group_of_events_max_height( category_id ) {
	// Set a max height to the group list
	if( $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).outerHeight() >= 150 ) {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).css( 'height', 150 );
	} else {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-editor-list' ).css( 'height', 'auto' );
	}
	
	// Set a max height to the category list
	if( $j( '#bookacti-group-categories' ).outerHeight() > 200 ) {
		$j( '#bookacti-group-categories' ).css( 'height', 200 );
	} else {
		$j( '#bookacti-group-categories' ).css( 'height', 'auto' );
	}
}


/**
 * Expand or Collapse all group categories
 * @param {string} action
 * @param {array} exceptions
 */
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


/**
 * Load activities bound to selected template
 * @version 1.8.0
 * @param {int} selected_template_id
 */
function bookacti_load_activities_bound_to_template( selected_template_id ) {
	if( parseInt( selected_template_id ) === parseInt( bookacti.selected_template ) ) { return; }

	$j( '#bookacti-activities-bound-to-template .bookacti-form-error' ).remove();

	bookacti_start_template_loading();

	$j.ajax({
		url: ajaxurl, 
		data: { 
			'action': 'bookactiGetActivitiesByTemplate', 
			'selected_template_id': selected_template_id,
			'current_template_id': bookacti.selected_template,
			'nonce': $j( '#nonce_edit_template' ).val()
		},
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			// Empty current list of activity
			$j( 'select#activities-to-import' ).empty();

			if( response.status === 'success' ) {
				// Fill the available activities select box
				if( response.activities ) {
					$j.each( response.activities, function( activity_id, activity ){
						if( ! $j( '#bookacti-template-activity-list .activity-row .fc-event[data-activity-id="' + activity_id + '"]' ).length ) {
							$j( 'select#activities-to-import' ).append( '<option value="' + activity_id + '" >' + activity.title + '</option>' );
						}
					});
				}
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-activities-bound-to-template' ).append( '<div class="bookacti-form-error">' + response.message + '</div>' );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ){
			alert( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading(); 
		}
	});
}


/**
 * Show event actions
 * @param {HTMLElement} element
 */
function bookacti_show_event_actions( element ) {
	element.addClass( 'bookacti-event-over' );
	element.find( '.bookacti-event-action' ).show();
}


/**
 * Hide event actions
 * @version 1.8.5
 * @param {HTMLElement} element
 * @param {Object} event
 */
function bookacti_hide_event_actions( element, event ) {
	element.removeClass( 'bookacti-event-over' );

	element.find( '.bookacti-event-action[data-hide-on-mouseout="1"]' ).hide();

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

	// If the event is selected, do not hide the 'selected' checkbox
	if( is_selected ) {
		element.find( '.bookacti-event-actions' ).show();
		element.find( '.bookacti-event-action-select' ).show();
	} else {
		element.find( '.bookacti-event-action-select' ).hide();
	}
}


/**
 * Initialize visual feedbacks when an event is duplicated
 * @since 1.7.14
 * @version 1.7.15
 */
function bookacti_init_event_duplication_feedbacks() {
	$j( document ).on( 'keydown', function( e ) {
		if( e.altKey ) {
			alt_key_down = true;
			if( bookacti.is_hovering || bookacti.is_dragging ) {
				$j( '#bookacti-template-container .bookacti-event-dragged, #bookacti-template-container .bookacti-event-over, #bookacti-template-container .fc-helper' ).addClass( 'bookacti-duplicate-event' );
			}
			e.stopPropagation();
			e.preventDefault();
		}
	});
	$j( document ).on( 'keyup', function( e ) {
		if( e.keyCode == 18 ) {
			$j( '#bookacti-template-container .bookacti-duplicate-event' ).removeClass( 'bookacti-duplicate-event' );
			e.stopPropagation();
			e.preventDefault();
		}
	});
}