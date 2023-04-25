// TEMPLATE

/**
 * Change default template on change in the select box
 * @version 1.15.5
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
		$j( '#bookacti-template-activity-list .bookacti-activity' ).remove();
		
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
	var attributes = $j.extend( {}, bookacti.booking_system[ 'bookacti-template-calendar' ] );
	var attributes = bookacti_get_booking_system_attributes_without_data( $j( '#bookacti-template-calendar' ) );

	delete attributes[ 'display_data' ];
	delete attributes[ 'picked_events' ];
	
	bookacti_start_template_loading();

	// Change the default template in the database to the selected one
	$j.ajax({
		url: ajaxurl,
		data: { 
			'action': 'bookactiSwitchTemplate', 
			'template_id': selected_template_id,
			'attributes': JSON.stringify( attributes ),
			'nonce': $j( '#bookacti-edit-template-nonce' ).val()
		},
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				// Change the global var
				var is_first_template      = bookacti.selected_template ? false : true;
				var loading_number_temp    = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ];
				bookacti.selected_template = parseInt( selected_template_id );
				bookacti.hidden_activities = [];

				// Update data array
				bookacti.booking_system[ 'bookacti-template-calendar' ]                           = response.booking_system_data;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]      = [];
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ]        = [];
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ]       = loading_number_temp;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'method' ]               = 'calendar';
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events' ]          = true;
				bookacti.booking_system[ 'bookacti-template-calendar' ][ 'past_events_bookable' ] = true;

				// Unlock dialogs triggering after first template is created and selected
				if( is_first_template ) { 
					bookacti_bind_template_dialogs();
					bookacti_init_groups_of_events();
				}


				// TEMPLATE SETTINGS
					// Update calendar settings
					bookacti_load_template_calendar();

				// ACTIVITIES
					// Replace current activities with activities bound to the selected template
					$j( '#bookacti-template-activity-list .bookacti-activity' ).remove();
					$j( '#bookacti-template-activity-list' ).append( response.activities_list );
					bookacti_refresh_activity_list();

				// GROUPS
					// Replace current groups with groups bound to the selected template
					$j( '#bookacti-group-categories' ).empty();
					$j( '#bookacti-group-categories' ).append( response.groups_list );
					$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).toggle( response.groups_list === '' );
					$j( '#bookacti-template-groups-of-events-container .dashicons' ).toggleClass( 'bookacti-disabled', bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] > 0 );
					
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
					
					// Allow to sort group categories and group of events
					bookacti_make_group_categories_sortable();
					bookacti_make_groups_of_events_sortable();
					

				// SHORTCODE GENERATOR
					// Update create form link template id
					bookacti_update_create_form_link_template_id( bookacti.selected_template );
				
				
				bookacti_start_template_loading();

				// VIEW
					// Go to today's date
					bookacti.fc_calendar[ 'bookacti-template-calendar' ].gotoDate( moment.utc().locale( 'en' ).format( 'YYYY-MM-DD' ) );


				// EVENTS
					// Empty the calendar
					bookacti_clear_events_on_calendar_editor();

					// Load events on calendar
					var view = bookacti.fc_calendar[ 'bookacti-template-calendar' ].view;
					var interval = {
						'start': moment.utc( moment.utc( view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
						'end': moment.utc( moment.utc( view.currentEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
					};
					var new_interval = bookacti_get_interval_of_events( $j( '#bookacti-template-calendar' ), interval );
					if( ! $j.isEmptyObject( new_interval ) ) { bookacti_get_calendar_editor_data_by_interval( new_interval ); }

					// Re-enable events to load when view changes
					bookacti.load_events = true;
				
				$j( '#bookacti-template-calendar' ).trigger( 'bookacti_calendar_editor_template_switched' );
				
				bookacti_stop_template_loading();
				
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
 * Refresh activity list
 * @since 1.15.0
 */
function bookacti_refresh_activity_list() {
	// Hide the srollbar if the activity list is small enough
	$j( '#bookacti-template-activity-list' ).css( 'height', $j( '#bookacti-template-activity-list' ).outerHeight() > 200 ? 200 : 'auto' );
	
	// Display tuto if there is no more activities available
	bookacti_display_activity_tuto_if_no_activity_available();
	
	// Update the show / hide icons
	bookacti_refresh_show_hide_activities_icons();
}


/**
 * Show / hide events when clicking the icon next to the activity
 * @version 1.15.0
 */
function bookacti_init_show_hide_activities_switch() {
	$j( 'body' ).on( 'click', '#bookacti-template-activity-list .bookacti-activity-visibility', function() { 
		var activity_id = parseInt( $j( this ).closest( '.bookacti-activity' ).data( 'activity-id' ) );
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
		
		if( typeof bookacti.fc_calendar[ 'bookacti-template-calendar' ] !== 'undefined' ) {
			bookacti.fc_calendar[ 'bookacti-template-calendar' ].render();
		}
	});
}


/**
 * Update the show / hide icon next to the activity to reflect its current state
 * @since 1.7.17
 * @version 1.11.0
 */
function bookacti_refresh_show_hide_activities_icons() {
	// Make all icons "visible"
	var icons = $j( '#bookacti-template-activity-list .bookacti-activity-visibility' );
	icons.addClass( 'dashicons-visibility' );
	icons.removeClass( 'dashicons-hidden' );
	icons.data( 'activity-visible', 1 );
	icons.attr( 'data-activity-visible', 1 );
	
	// Set the hidden activities icons to "hidden"
	$j.each( bookacti.hidden_activities, function( i, activity_id ) { 
		if( $j( '#bookacti-template-activity-list .bookacti-activity[data-activity-id="' + activity_id + '"] .bookacti-activity-visibility' ).length ) {
			var icon = $j( '#bookacti-template-activity-list .bookacti-activity[data-activity-id="' + activity_id + '"] .bookacti-activity-visibility' );
			icon.removeClass( 'dashicons-visibility' );
			icon.addClass( 'dashicons-hidden' );
			icon.data( 'activity-visible', 0 );
			icon.attr( 'data-activity-visible', 0 );
		}
	});
}


/**
 * Make activities draggable
 * @since 1.15.0
 */
function bookacti_make_activities_draggable() {
	new FullCalendar.Draggable( 
		document.getElementById( 'bookacti-template-activity-list' ),
		{ "itemSelector": '.bookacti-activity-draggable:not(.bookacti-activity-disabled)' }
	);
}


/**
 * Make activities sortable in editor by drag n' drop
 * @since 1.11.0
 * @version 1.15.12
 */
function bookacti_make_activities_sortable() {
	$j( '#bookacti-template-activity-list' ).sortable( { 
		items: '.bookacti-activity',
		handle: '.bookacti-activity-visibility',
		placeholder: 'bookacti-calendar-editor-sortable-placeholder',
		delay: 300,
		update: function( e, ui ) { bookacti_save_template_items_order( 'activities' ); }
	});
	$j( '#bookacti-template-activity-list' ).disableSelection();
}


/**
 * Save activities / group categories / groups of events order
 * @since 1.11.0
 * @version 1.15.5
 * @param {String} item_type 'activities', 'groups_of_events' or 'group_categories'
 * @param {Int} item_id
 */
function bookacti_save_template_items_order( item_type, item_id ) {
	item_type = typeof item_type !== 'undefined' ? item_type : '';
	item_id = typeof item_id !== 'undefined' ? parseInt( item_id ) : 0;
	
	var item_selector, data_name = '';
	switch( item_type ) {
		case 'activities':
			item_selector = '#bookacti-template-activity-list .bookacti-activity';
			data_name = 'activity-id';
			break;
		case 'group_categories':
			item_selector = '#bookacti-group-categories .bookacti-group-category';
			data_name = 'group-category-id';
			break;
		case 'groups_of_events':
			item_selector = '.bookacti-group-category[data-group-category-id="' + item_id + '"] .bookacti-groups-of-events-editor-list .bookacti-group-of-events';
			data_name = 'group-id';
			break;
	}
	
	// Get the items order
	var items_order = [];
	if( item_selector && data_name ) {
		if( $j( item_selector ).length ) {
			$j( item_selector ).each( function() {
				if( typeof $j( this ).data( data_name ) !== 'undefined' ) { 
					items_order.push( $j( this ).data( data_name ) );
				}
			});
		}
	}
	
	if( ! items_order.length ) { return; }
	
	var data = {
		'action': 'bookactiSaveTemplateItemsOrder',
		'template_id': bookacti.selected_template,
		'item_type': item_type,
		'item_id': item_id,
		'items_order': items_order,
		'nonce': $j( '#bookacti-edit-template-nonce' ).val()
	};
	
	$j( '#bookacti-template-container' ).trigger( 'bookacti_update_template_items_order_data', [ data, item_type, item_id ] );
	
	bookacti_start_template_loading();
	
	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				$j( '#bookacti-template-container' ).trigger( 'bookacti_template_items_order_updated', [ response, data, item_type, item_id ] );
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX ' + bookacti_localized.error;

			console.log( error_message );
			console.log( e );
		},
		complete: function() { bookacti_stop_template_loading();  }
	});
}




// GROUPS OF EVENTS

/**
 * Init groups of events
 * @version 1.9.0
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
		var is_selected	= $j( this ).closest( '.bookacti-group-of-events' ).hasClass( 'bookacti-selected-group' );
		if( ! is_selected ) {
			var group_id = $j( this ).closest( '.bookacti-group-of-events' ).data( 'group-id' );
			bookacti_select_events_of_group( group_id );
		} else {
			bookacti_unselect_all_events();
			bookacti_refresh_selected_events_display();
		}
	});
}

/**
 * Sort group categories in editor by drag n' drop
 * @since 1.11.0
 * @version 1.15.12
 */
function bookacti_make_group_categories_sortable() {
	$j( '#bookacti-group-categories' ).sortable( { 
		items: '.bookacti-group-category',
		handle: '.bookacti-group-category-title',
		placeholder: 'bookacti-calendar-editor-sortable-placeholder',
		delay: 300,
		update: function( e, ui ) { bookacti_save_template_items_order( 'group_categories' ); }
	});
	$j( '#bookacti-group-categories' ).disableSelection();
}


/**
 * Sort group categories in editor by drag n' drop
 * @since 1.11.0
 * @version 1.15.12
 */
function bookacti_make_groups_of_events_sortable() {
	$j( '.bookacti-groups-of-events-editor-list' ).sortable( { 
		items: '.bookacti-group-of-events',
		handle: '.bookacti-group-of-events-title',
		placeholder: 'bookacti-calendar-editor-sortable-placeholder',
		delay: 300,
		update: function( e, ui ) { 
			var category_id = $j( ui.item ).closest( '.bookacti-group-category' ).data( 'group-category-id' );
			bookacti_save_template_items_order( 'groups_of_events', category_id );
		}
	});
	$j( '.bookacti-groups-of-events-editor-list' ).disableSelection();
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
 * @version 1.15.0
 * @param {Int} group_id
 * @param {String} group_date
 */
function bookacti_select_events_of_group( group_id, group_date ) {
	group_id = group_id ? parseInt( group_id ) : 0;
	group_date = group_date ? group_date : '';
	if( ! group_id ) { return false; }
	
	// Unselect the events
	bookacti_unselect_all_events();
	
	// Empty the selected events and refresh them
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
	bookacti.fc_calendar[ 'bookacti-template-calendar' ].render();
	
	// select the events of the desired occurrence if specified...
	var group_events = [];
	if( group_date ) {
		if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ][ group_date ] !== 'undefined' ) {
				group_events = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ][ group_date ].slice();
			}
		}
	}
	
	// ...else select the events by default
	if( ! group_events.length ) {
		if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ] !== 'undefined' ) {
			if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ][ 'events' ] !== 'undefined' ) {
				group_events = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ][ 'events' ].slice();
			}
		}
	}
	
	// Change view to the 1st event selected to make sure that at least 1 event is in the view
	if( group_events.length ) {
		bookacti.fc_calendar[ 'bookacti-template-calendar' ].gotoDate( group_events[ 0 ][ 'start' ] );
	}

	// Select the events of the group
	var are_selected = true;
	$j.each( group_events, function( i, group_event ){
		var is_selected = bookacti_select_event( group_event );
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
 * @version 1.15.0
 * @param {(FullCalendar.EventApi|Object)} raw_event
 * @returns {Boolean}
 */
function bookacti_select_event( raw_event ) {
	// Return false if we don't have both event id and event start
	if( ( typeof raw_event !== 'object' )
	||  ( typeof raw_event === 'object' && ( typeof raw_event.id === 'undefined' || typeof raw_event.start === 'undefined' ) ) ) {
		return false;
	}
	
	var event_id   = typeof raw_event.groupId !== 'undefined' ? parseInt( raw_event.groupId ) : parseInt( raw_event.id );
	var event_data = typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ] !== 'undefined' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ] : [];
	var activity_id = raw_event.activity_id ? raw_event.activity_id : ( event_data.activity_id ? event_data.activity_id : 0 );
	
	var activity_title = '';
	if( ! raw_event.title ) {
		if( event_data.title ) {
			activity_title = event_data.title;
		} else if( $j( '.bookacti-activity[data-activity-id="' + activity_id + '"] .bookacti-activity-draggable' ).length ) {
			activity_title = $j( '.bookacti-activity[data-activity-id="' + activity_id + '"] .bookacti-activity-draggable' ).text();
		}
	}
	
	// Format event object
	var selected_event = {
		'id': event_id,
		'activity_id': activity_id,
		'title': raw_event.title ? raw_event.title : activity_title,
		'start': moment.utc( raw_event.start ).clone().locale( 'en' ),
		'end': moment.utc( raw_event.end ).clone().locale( 'en' )
	};
	
	// Because of popover and long events (spreading over multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + selected_event.id + '"][data-event-start="' + selected_event.start.format( 'YYYY-MM-DD HH:mm:ss' ) + '"]' );
	
	// Format the selected event (because of popover, the same event can appears twice)
	if( elements.length ) {
		elements.addClass( 'bookacti-selected-event' );
		elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );
	}

	// Keep picked events in memory 
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].push({ 
		'id' : selected_event.id,
		'activity_id' : selected_event.activity_id,
		'title' : selected_event.title, 
		'start' : selected_event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
		'end' : selected_event.end.format( 'YYYY-MM-DD HH:mm:ss' ) 
	});
	
	// Sort the selected events
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].sort( function ( a, b ) {
		var x = moment.utc( a[ 'start' ] ); var y = moment.utc( b[ 'start' ] );
		return ( ( x.isBefore( y ) ) ? -1 : ( ( x.isAfter( y ) ) ? 1 : 0 ) );
	});
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_select_event', [ selected_event ] );
	
	return true;
}


/**
 * Unselect an event
 * @version 1.15.0
 * @param {object} raw_event
 * @param {boolean} all false to unselect only the desired occurrence. true to unselected all occurrences.
 * @returns {boolean}
 */
function bookacti_unselect_event( raw_event, all ) {
	// Determine if all event should be unselected
	all = all ? true : false;
	
	// Return false if we don't have both event id and event start
	if( ( typeof raw_event !== 'object' && ! $j.isNumeric( raw_event ) )
	||  ( typeof raw_event === 'object' && ( typeof raw_event.id === 'undefined' || typeof raw_event.start === 'undefined' ) )
	||  ( $j.isNumeric( raw_event ) && ! all ) ) {
		return false;
	}
	
	// Format event object
	var unselected_event = {
		'id':    $j.isNumeric( raw_event ) ? raw_event : ( typeof raw_event.groupId !== 'undefined' ? raw_event.groupId : raw_event.id ),
		'start': typeof raw_event.start !== 'undefined' ? moment.utc( raw_event.start ).clone().locale( 'en' ) : '',
		'end':   typeof raw_event.end !== 'undefined' ? moment.utc( raw_event.end ).clone().locale( 'en' ) : ''
	};
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var event_start      = unselected_event.start ? moment.utc( unselected_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) : '';
	var event_start_date = unselected_event.start ? moment.utc( unselected_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) : '';
	var elements         = all ? $j( '.fc-event[data-event-id="' + unselected_event.id + '"]' ) : $j( '.fc-event[data-event-id="' + unselected_event.id + '"][data-event-start="' + event_start + '"]' );
	
	if( elements.length ) {
		// Format the selected event(s)
		elements.removeClass( 'bookacti-selected-event' );
		elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	}

	// Remove selected event(s) from memory 
	var selected_events = $j.grep( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( selected_event ) {
		if( selected_event.id == unselected_event.id 
		&&  (  all 
			|| selected_event.start.substr( 0, 10 ) === event_start_date ) ) {
			
			// Unselect the event
			return false;
		}
		// Keep the event selected
		return true;
	});
	
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = selected_events;
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_event', [ unselected_event, all ] );
	
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
	
	// Remove selected event(s) from memory 
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
	
	// Remove "selected" classes
	$j( '.bookacti-group-of-events.bookacti-selected-group' ).removeClass( 'bookacti-selected-group' );
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_all_events' );
}


/**
 * Make sure selected events appears as selected and vice-versa
 * @version 1.15.0
 */
function bookacti_refresh_selected_events_display() {
	$j( '.fc-event' ).removeClass( 'bookacti-selected-event' );
	
	// Specific treatment for calendar editor
	$j( '.fc-event .bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ) {
		var element = $j( '.fc-event[data-event-id="' + selected_event.id + '"][data-event-start="' + selected_event.start + '"]' );
		// Format selected events
		element.addClass( 'bookacti-selected-event' );
		element.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );
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
 * Get calendar editor data by interval (events and bookings) 
 * @since 1.12.0 (was bookacti_fetch_events_on_calendar_editor)
 * @version 1.15.5
 * @param {object} interval
 */
function bookacti_get_calendar_editor_data_by_interval( interval ) {
	interval = interval ? interval : bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
	
	if( $j.isEmptyObject( interval ) ) {
		var view = bookacti.fc_calendar[ 'bookacti-template-calendar' ].view;
		var min_interval = { 
			'start': moment.utc( moment.utc( view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
			'end':   moment.utc( moment.utc( view.currentEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
		};
		interval = bookacti_get_interval_of_events( $j( '#bookacti-template-calendar' ), min_interval );
	}
	
	// Update events interval before success to prevent to fetch the same interval twice
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ] = bookacti_get_extended_events_interval( $j( '#bookacti-template-calendar' ), interval );
	
	// Update groups that have been retrieved
	var group_ids = ! $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] ) ? Object.keys( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] ) : [];
	
    bookacti_start_template_loading();
	
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 
			'action': 'bookactiGetCalendarEditorDataByInterval', 
			'template_id': bookacti.selected_template,
			'interval': JSON.stringify( interval ),
			'group_ids': JSON.stringify( group_ids ),
			'nonce': $j( '#bookacti-edit-template-nonce' ).val()
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
				
				// Extend or replace the groups array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] = response.groups_events;
				} else {
					$j.extend( true, bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], response.groups_events );
				}
				
				// Extend or replace the bookings array if it was empty
				if( $j.isEmptyObject( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ] ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ] = response.bookings;
				} else {
					$j.extend( true, bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ], response.bookings );
				}
				
				// Load new events on calendar
				bookacti_fc_add_events( $j( '#bookacti-template-calendar' ), response.events );
				
				$j( '#bookacti-template-calendar' ).trigger( 'bookacti_calendar_editor_interval_data_loaded', [ response, interval ] );
				
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
 * @since 1.12.0 (was bookacti_refetch_events_on_template)
 * @version 1.15.0
 */
function bookacti_refetch_events_on_calendar_editor() {
	// Clear the calendar
	bookacti_clear_events_on_calendar_editor();

	// Fetch events from the selected template
	var view = bookacti.fc_calendar[ 'bookacti-template-calendar' ].view;
	var min_interval = { 
		'start': moment.utc( moment.utc( view.currentStart ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) + ' 00:00:00' ), 
		'end':   moment.utc( moment.utc( view.currentEnd ).clone().locale( 'en' ).subtract( 1, 'days' ).format( 'YYYY-MM-DD' ) + ' 23:59:59' )
	};
	var interval = bookacti_get_interval_of_events( $j( '#bookacti-template-calendar' ), min_interval );
	
	bookacti_get_calendar_editor_data_by_interval( interval );
}


/**
 * Clear events on calendar editor
 * @since 1.12.0
 * @version 1.15.0
 */
function bookacti_clear_events_on_calendar_editor() {
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events' ]          = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ]     = [];
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ] = [];
	
	if( typeof bookacti.fc_calendar[ 'bookacti-template-calendar' ] !== 'undefined' ) {
		bookacti.fc_calendar[ 'bookacti-template-calendar' ].removeAllEvents();
	}
}


/**
 * Update event dates (move or resize an event)
 * @since 1.10.0
 * @version 1.15.5
 * @param {(FullCalendar.EventApi|Object)} new_event
 * @param {(FullCalendar.EventApi|Object)} old_event
 * @param {callable} revertFunc
 * @param {string} is_dialog 'normal' or 'booked'
 */
function bookacti_update_event_dates( new_event, old_event, revertFunc, is_dialog ) {
	// Sanitize params
	revertFunc = typeof revertFunc !== 'undefined' ? revertFunc : false;
	is_dialog = typeof is_dialog !== 'undefined' ? is_dialog : '';
	
	// If the booked event dialog is open, get the form data
	var dialog_id = '';
	var form_data = {};
	form_data.forced_update = 0;
	
	if( is_dialog === 'normal' ) { dialog_id = 'bookacti-update-event-dates-dialog'; }
	if( is_dialog === 'booked' ) { 
		dialog_id = 'bookacti-update-booked-event-dates-dialog';
		form_data = $j( '#bookacti-update-booked-event-dates-form' ).serializeObject();
		form_data.forced_update = 1;
	}
	
	// Remove old feedbacks
	if( dialog_id ) { $j( '#' + dialog_id + ' .bookacti-notices' ).remove(); }

	
	// Update the event changes in database
	var event_id  = typeof new_event.groupId !== 'undefined' ? parseInt( new_event.groupId ) : parseInt( new_event.id );
	var start     = moment.utc( new_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var end       = ! new_event.end ? start : moment.utc( new_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var old_start = moment.utc( old_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var old_end   = ! old_event.end ? old_start : moment.utc( old_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var interval  = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
	var data      = $j.extend( { 
		'action': 'bookactiUpdateEventDates',
		'event_id': event_id, 
		'event_start': start, 
		'event_end': end,
		'old_event_start': old_start, 
		'old_event_end': old_end,
		'interval': interval,
		'nonce': $j( '#bookacti-edit-template-nonce' ).val()
	}, form_data );
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_update_event_dates_before', [ new_event, data, old_event, revertFunc ] );

	// Display a loading feedback
	bookacti_start_template_loading();
	if( dialog_id ) { bookacti_add_loading_html( $j( '#' + dialog_id ) ); }

	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) { 
				var delta_start = moment.duration( moment.utc( start ).diff( moment.utc( old_start ) ) );
				var delta_end   = moment.duration( moment.utc( end ).diff( moment.utc( old_end ) ) );

				// Update event data
				if( ! $j.isEmptyObject( response.event_data ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ] = response.event_data;
				}
				
				// Update selected events
				$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, selected_event ) {
					if( selected_event.id == event_id ) {
						var event_start = moment.utc( selected_event.start ).clone().locale( 'en' ).add( delta_start ).format( 'YYYY-MM-DD HH:mm:ss' );
						var event_end   = moment.utc( selected_event.end ).clone().locale( 'en' ).add( delta_end ).format( 'YYYY-MM-DD HH:mm:ss' );
						selected_event.start = event_start;
						selected_event.end   = event_end;
					}
				});

				// Update groups of events if the event belong to one of them
				$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ], function( group_id, group_occurrences ) {
					$j.each( group_occurrences, function( group_date, group_events ) {
						$j.each( group_events, function( i, group_event ) {
							if( group_event.id == event_id ) {
								group_event.start = moment.utc( group_event.start ).clone().locale( 'en' ).add( delta_start ).format( 'YYYY-MM-DD HH:mm:ss' );
								group_event.end   = moment.utc( group_event.end ).clone().locale( 'en' ).add( delta_end ).format( 'YYYY-MM-DD HH:mm:ss' );
							}
						});
					});
				});
				$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ], function( group_id, group_data ) {
					$j.each( group_data[ 'events' ], function( i, group_event ) {
						if( group_event.id == event_id ) {
							group_event.start = moment.utc( group_event.start ).clone().locale( 'en' ).add( delta_start ).format( 'YYYY-MM-DD HH:mm:ss' );
							group_event.end   = moment.utc( group_event.end ).clone().locale( 'en' ).add( delta_end ).format( 'YYYY-MM-DD HH:mm:ss' );
						}
					});
				});
				
				// Update event bookings
				var event_bookings = {};
				if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ event_id ] !== 'undefined' ) {
					$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ event_id ], function( old_event_start, bookings_nb_data ) {
						var new_event_start = moment.utc( old_event_start ).clone().locale( 'en' ).add( delta_start ).format( 'YYYY-MM-DD HH:mm:ss' );
						event_bookings[ new_event_start ] = $j.extend( {}, bookings_nb_data );
					});
					if( $j.isEmptyObject( event_bookings ) ) { delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ event_id ]; }
				}
				if( ! $j.isEmptyObject( event_bookings ) ) { bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ event_id ] = event_bookings; }
				
				// Render updated event to make sure it fits in events interval
				if( response.events.length ) {
					bookacti_fc_remove_events_by_groupId( $j( '#bookacti-template-calendar' ), event_id );
					bookacti_fc_add_events( $j( '#bookacti-template-calendar' ), response.events );
				}
				
				// Close the dialog if it was opened
				if( dialog_id ) { 
					$j( '#' + dialog_id ).off( 'dialogbeforeclose' ); // Do not trigger revertFunc() in that case
					$j( '#' + dialog_id ).dialog( 'close' );
				}

				$j( '#bookacti-template-calendar' ).trigger( 'bookacti_event_dates_updated', [ new_event, old_event, response, data ] );
			}

			else if( response.status === 'failed' ) { 
				// If the event is booked, display a dialog to confirm
				if( response.error === 'has_bookings' && ! form_data.forced_update ) {
					// Close the dialog if it was opened
					if( dialog_id ) { $j( '#' + dialog_id ).dialog( 'close' ); }
					
					// Open the booked event dialog
					bookacti_dialog_update_booked_event_dates( new_event, old_event, revertFunc );
					
					// Display the number of bookings to be rescheduled and the number of users to be notified
					$j( '#bookacti-update-booked-event-dates-intro' ).append( '<span class="bookacti-bookings-nb">' + response.bookings_nb + '</span>' );
					$j( '#bookacti-update-booked-event-dates-send_notifications-container' ).append( '<span class="bookacti-notifications-nb">' + response.notifications_nb + '</span>' );
					
				} else {
					if( revertFunc !== false ) { revertFunc(); }
					var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
					if( dialog_id ) { 
						$j( '#' + dialog_id ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ); 
						$j( '#' + dialog_id + ' .bookacti-notices' ).show();
					} else { alert( error_message ); }
					console.log( response );
				}
			}
		},
		error: function( e ) {
			if( revertFunc !== false ) { revertFunc(); }
			alert( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			if( dialog_id ) { bookacti_remove_loading_html( $j( '#' + dialog_id ) ); }
			bookacti_stop_template_loading();
		}
	});
}


/**
 * Duplicate an event
 * @since 1.10.0
 * @version 1.15.5
 * @param {(FullCalendar.EventApi|Object)} new_event
 * @param {(FullCalendar.EventApi|Object)} old_event
 */
function bookacti_duplicate_event( new_event, old_event ) {
	var event_id  = typeof new_event.groupId !== 'undefined' ? parseInt( new_event.groupId ) : parseInt( new_event.id );
	var start     = moment.utc( new_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var end       = ! new_event.end ? start : moment.utc( new_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var old_start = moment.utc( old_event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var old_end   = ! old_event.end ? old_start : moment.utc( old_event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
	var interval  = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
	var data      = { 
		'action': 'bookactiDuplicateEvent',
		'event_id': event_id, 
		'event_start': start, 
		'event_end': end,
		'old_event_start': old_start, 
		'old_event_end': old_end,
		'interval': interval,
		'nonce': $j( '#bookacti-edit-template-nonce' ).val()
	};

	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_duplicate_event_before', [ new_event, old_event, data ] );

	bookacti_start_template_loading();

	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) { 
				// Add new event data
				if( ! $j.isEmptyObject( response.event_data ) ) {
					bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ response.event_id ] = response.event_data;
				}

				// Load the new event on calendar
				if( response.events.length ) {
					bookacti_fc_add_events( $j( '#bookacti-template-calendar' ), response.events );
				}
				
				$j( '#bookacti-template-calendar' ).trigger( 'bookacti_event_duplicated', [ new_event, old_event, response, data ] );
			}

			else if( response.status === 'failed' ) {
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
}


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
 * Start a template loading (or keep on loading if already loading)
 * @version 1.15.0
 */
function bookacti_start_template_loading() {
	$j( '#bookacti-template-sidebar .dashicons' ).addClass( 'bookacti-disabled' );
	$j( '#bookacti-template-activities-container .bookacti-activity-draggable' ).addClass( 'bookacti-activity-disabled' );
	$j( '.bookacti-template-dialog' ).find( 'input, select, button' ).attr( 'disabled', true );
	$j( '#bookacti-template-picker' ).attr( 'disabled', true );
	bookacti_start_loading_booking_system( $j( '#bookacti-template-calendar' ) );
}


/**
 * Stop a template loading (but keep on loading if there are other loadings)
 * @version 1.15.0
 * @param {boolean} force_exit
 */
function bookacti_stop_template_loading( force_exit ) {
	force_exit = force_exit || false;
	bookacti_stop_loading_booking_system( $j( '#bookacti-template-calendar' ), force_exit );
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'loading_number' ] === 0 ) {
		$j( '#bookacti-template-sidebar .dashicons' ).removeClass( 'bookacti-disabled' );
		$j( '#bookacti-template-activities-container .bookacti-activity-draggable' ).removeClass( 'bookacti-activity-disabled' );
		$j( '.bookacti-template-dialog' ).find( 'input, select, button' ).attr( 'disabled', false );
		$j( '#bookacti-template-picker' ).attr( 'disabled', false );
	}
}


/**
 * Display tuto if there is no more activities available
 * @version 1.11.0
 */
function bookacti_display_activity_tuto_if_no_activity_available() {
	if( $j( '#bookacti-template-first-activity-container' ).length ) {
		$j( '#bookacti-template-first-activity-container' ).toggle( $j( '.bookacti-activity' ).length <= 0 );
	}
}


/**
 * Display tuto if there is there is at least two events selected and no group categories yet
 * @version 1.9.3
 */
function bookacti_maybe_display_add_group_of_events_button() {
	if( ! $j( '#bookacti-template-add-first-group-of-events-container' ).length ) { return; }
	
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
	
	// If a group is already selected, change its settings icon as a feedback that it is possible to update the grouped events
	$j( '.bookacti-group-of-events .bookacti-update-group-of-events' ).removeClass( 'dashicons-insert' ).addClass( 'dashicons-admin-generic' );
	if( $j( '.bookacti-group-of-events.bookacti-selected-group' ).length ) {
		$j( '.bookacti-group-of-events.bookacti-selected-group .bookacti-update-group-of-events' ).removeClass( 'dashicons-admin-generic' ).addClass( 'dashicons-insert' );
	}
}


/**
 * Expand or Collapse groups of events
 * @version 1.15.12
 * @param {int} category_id
 * @param {string} force_to
 * @param {boolean} one_by_one
 */
function bookacti_expand_collapse_groups_of_events( category_id, force_to, one_by_one ) {
	one_by_one = one_by_one ? true : false;
	force_to   = $j.inArray( force_to, [ 'expand', 'collapse' ] ) >= 0 ? force_to : false;
	
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


/**
 * Expand or Collapse all group categories
 * @version 1.12.0
 * @param {String} action
 * @param {Array} except_category_ids
 */
function bookacti_expand_collapse_all_groups_of_events( action, except_category_ids ) {
	except_category_ids = $j.isArray( except_category_ids ) ? except_category_ids : ( $j.isNumeric( except_category_ids ) ? [ except_category_ids ] : [] );
	
	var categories_selector = '.bookacti-group-category';
	$j.each( except_category_ids, function( i, category_id ){
		categories_selector += ':not([data-group-category-id="' + category_id + '"])';
	});
	
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
 * @version 1.15.5
 * @param {int} selected_template_id
 */
function bookacti_load_activities_bound_to_template( selected_template_id ) {
	if( parseInt( selected_template_id ) === parseInt( bookacti.selected_template ) ) { return; }

	$j( '#bookacti-activity-import-dialog .bookacti-form-error' ).remove();

	bookacti_start_template_loading();
	bookacti_add_loading_html( $j( '#bookacti-activity-import-dialog' ) );

	$j.ajax({
		url: ajaxurl, 
		data: { 
			'action': 'bookactiGetActivitiesByTemplate', 
			'selected_template_id': selected_template_id,
			'current_template_id': bookacti.selected_template,
			'nonce': $j( '#bookacti-edit-template-nonce' ).val()
		},
		type: 'POST',
		dataType: 'json',
		success: function( response ) {
			// Empty current list of activity
			$j( 'select#bookacti-activities-to-import option' ).remove();

			if( response.status === 'success' ) {
				// Fill the available activities select box
				if( response.activities ) {
					$j.each( response.activities, function( activity_id, activity ){
						if( ! $j( '#bookacti-template-activity-list .bookacti-activity[data-activity-id="' + activity_id + '"]' ).length ) {
							$j( 'select#bookacti-activities-to-import' ).append( '<option value="' + activity_id + '" >' + activity.title + '</option>' );
						}
					});
				}
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-activity-import-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
				$j( '#bookacti-activity-import-dialog .bookacti-notices' ).show();
				console.log( response );
			}
		},
		error: function( e ){
			alert( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading();
			bookacti_remove_loading_html( $j( '#bookacti-activity-import-dialog' ) );
		}
	});
}


/**
 * Initialize visual feedbacks when an event is duplicated
 * @since 1.7.14
 * @version 1.15.0
 */
function bookacti_init_event_duplication_feedbacks() {
	$j( document ).on( 'keydown', function( e ) {
		if( e.altKey ) {
			$j( '#bookacti-template-container .bookacti-event-dragged, #bookacti-template-container .fc-event:hover, #bookacti-template-container .fc-helper' ).addClass( 'bookacti-duplicate-event' );
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


/**
 * Remove FC events by groupId
 * @since 1.15.0
 * @param {HTMLElement} booking_system
 * @param {Int} groupId
 * @returns {Array}
 */
function bookacti_fc_remove_events_by_groupId( booking_system, groupId ) {
	var booking_system_id = booking_system.attr( 'id' );
	bookacti.fc_calendar[ booking_system_id ].batchRendering( function() {
		$j.each( bookacti.fc_calendar[ booking_system_id ].getEvents(), function( i, fc_event ) {
			if( fc_event.groupId == groupId ) {
				fc_event.remove();
			}
		});
	});
}