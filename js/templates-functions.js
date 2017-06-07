//Change default template on change in the select box
function bookacti_switch_template( selected_template_id ) {
	if( selected_template_id ) {
		if( parseInt( selected_template_id ) !== parseInt( template_id ) ) {
		
			bookacti_start_template_loading();

			//Change the default template in the database to the selected one
			$j.ajax({
				url: ajaxurl,
				data: { 'action': 'bookactiSwitchTemplate', 
						'template_id': selected_template_id,
						'nonce': bookacti_localized.nonce_switch_template
					},
				type: 'POST',
				dataType: 'json',
				success: function( response ){

					if( response.status === 'success' || response.status === 'no_change' ) {
						
						// Change the template_id global var
						var is_first_template = template_id ? false : true;
						template_id = parseInt( selected_template_id );
						
						// Unlock dialogs triggering after first template is created and selected
						if( is_first_template ) { bookacti_bind_template_dialogs(); }
						
						// Replace current activities with activities bound to the selected template
						$j( '#bookacti-template-activity-list .activity-row' ).remove();
						$j( '#bookacti-template-activity-list' ).append( response.activities );
						
						bookacti_init_activities();
						
						// Update calendar settings
						bookacti_update_settings_from_database( $j( '#bookacti-template-calendar' ), template_id );
						
						//Prevent user from dropping / resizing activity out of template period
						var new_range = {
							start: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) ),
							end: moment( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) ).add( 1, 'days' )
						};
						$j( '#bookacti-template-calendar' ).fullCalendar( 'option', 'validRange', new_range );
						
						// Update shortcode generator calendars list
						$j( '.bookacti-shortcode-calendar-ids' ).each( function(){
							// Display new activities list
							$j( this ).text( template_id );
						});
						
						// Update shortcode generator activities list
						var activity_ids = [];
						$j( '#bookacti-template-activity-list .activity-row .activity-show-hide img' ).each( function(){
							if( $j( this ).data( 'activity-visible' ) == 1 ) {
								activity_ids.push( $j( this ).data( 'activity-id' ) );
							}
						});
						bookacti_update_shortcode_generator_activity_ids( activity_ids, true, true );
						
						//Clear the calendar and fetch events from the selected template
						//Load exceptions of the events of the selected calendar
						bookacti_update_exceptions( template_id );
						
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
		}
	} else {
		$j( '#bookacti-template-picker' ).val( '' );
		template_id = 0;
		
		// Display the create calendar tuto
		$j( '#bookacti-template-calendar' ).after( $j( "<div id='bookacti-first-template-container'><h2>" + bookacti_localized.create_first_calendar + "</h2><div id='bookacti-add-first-template-button' ><img src='" + bookacti_localized.plugin_path + "/img/add.png' /></div></div>" ) );
		$j( '#bookacti-template-sidebar, #bookacti-shortcode-generator-container' ).addClass( 'bookacti-no-template' );
		$j( '#bookacti-template-calendar' ).remove();
		$j( '.activity-row' ).remove();
		
		// Display tuto if there is no more activities available
		bookacti_display_activity_tuto_if_no_activity_available();
		
		// Prevent dialogs to be opened if no template is selected
		bookacti_bind_template_dialogs();
	}
}


function bookacti_init_activities() {
    $j( '#bookacti-template-activities-container .fc-event' ).each( function() {
        var resizable = false;
        if( $j( this ).data( 'resizable' ) === '1' ){ resizable = true; }
       
        // store data so the calendar knows to render an event upon drop
        $j( this ).data( 'event', {
            title:              $j.trim( $j( this ).text() ),
            durationEditable:   resizable,
            color:              $j( this ).data( 'color' ),
            stick:              true
        });

        //Set also the color for the draggable event
        $j(this).css( 'background-color', $j(this).data( 'color' ) );
        $j(this).css( 'border-color', $j(this).data( 'color' ) );

        // make the event draggable using jQuery UI
        $j(this).draggable({
            zIndex: 1000,
            revert: true, // will cause the event to go back to its original position after the drag
            revertDuration: 100, // (millisecond) interpolation duration between event drop and original position
            start: function( event, ui ) { 
				isDragging = true; 
				$j( this ).parent().css( 'overflow', 'visible' ); 
			},
            stop: function( event, ui ) { 
				isDragging = false;
				$j( this ).parent().css( 'overflow', '' );
            }
        });
    });
	if( blockEvents === true ) {
		$j( '#bookacti-template-activities-container img' ).addClass( 'bookacti-disabled-img' );
		$j( '#bookacti-template-activities-container .fc-event' ).addClass( 'event-unavailable' );
	}
	
	// Display tuto if there is no more activities available
	bookacti_display_activity_tuto_if_no_activity_available();
}


function bookacti_init_show_hide_activities_switch() {
    
    var srcPath = bookacti_localized.plugin_path + '/img/';
    
    $j( '#bookacti-template-activity-list' ).on( 'click', '.activity-show-hide img', function() { 
        
        var activity_id = $j( this ).data( 'activity-id' );
        var idx = $j.inArray( activity_id, hiddenActivities );
        
        if( $j( this ).data( 'activity-visible' ) === 1 ) {
            $j( this ).attr( 'src', srcPath + 'hide.png' );
            $j( this ).data( 'activity-visible', 0 );
            $j( this ).attr( 'data-activity-visible', 0 );
            if ( idx === -1 ) { hiddenActivities.push( activity_id ); }
            
        } else {
            $j( this ).attr( 'src', srcPath + 'show.png' );
            $j( this ).data( 'activity-visible', 1 );
            $j( this ).attr( 'data-activity-visible', 1 );
            if ( idx !== -1 ) {  hiddenActivities.splice( idx, 1 ); }
        }
        
        $j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
		
		//Update shortcode generator activities list
		var is_visible = $j( this ).data( 'activity-visible' );
		bookacti_update_shortcode_generator_activity_ids( activity_id, is_visible, false );
    });
}


// Show / Hide switch for groups
function bookacti_init_show_hide_groups_switch() {
    
    var srcPath = bookacti_localized.plugin_path + '/img/';
    
    $j( '#bookacti-group-categories' ).on( 'click', '.bookacti-group-category-show-hide img', function() { 
        
        var category_element	= $j( this ).parents( '.bookacti-group-category' );
        var category_id			= category_element.data( 'group-category-id' );
        
		if( category_element.data( 'visible' ) === 1 ) {
			$j( this ).attr( 'src', srcPath + 'hide.png' );
			category_element.data( 'visible', 0 );
			category_element.attr( 'data-visible', 0 );

		} else {
			$j( this ).attr( 'src', srcPath + 'show.png' );
			category_element.data( 'visible', 1 );
			category_element.attr( 'data-visible', 1 );
		}
		
		//Update shortcode generator groups list
		var is_visible = category_element.data( 'visible' );
		bookacti_update_shortcode_generator_group_ids( category_id, is_visible, false );
    });
}


// Make sure selected events appears as selected and vice-versa
function bookacti_refresh_selected_events_display() {
	$j( '.fc-event' ).removeClass( 'bookacti-selected-event' );
	$j( '.fc-event .bookacti-event-action-select-checkbox' ).prop( 'checked', false );

	$j.each( selectedEvents[ 'template' ], function( i, selected_event ) {
		var element = $j( '.fc-event[data-event-id="' + selected_event.event_id + '"][data-event-date="' + selected_event.event_start.substr( 0, 10 ) + '"]' );
		// Format selected events
		element.addClass( 'bookacti-selected-event' );

		// Check the box
		element.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );

		// Show select actions
		element.find( '.bookacti-event-actions' ).show();
		element.find( '.bookacti-event-action[data-hide-on-mouseout="1"]' ).hide();
		element.find( '.bookacti-event-action-select' ).show();
	});
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_refresh_selected_events' );
}


// Select an event
function bookacti_select_event( event, start ) {
	
	// Return false if we don't have both event id and event start
	if( ( typeof event !== 'object' && ! $j.isNumeric( event ) )
	||  ( typeof event === 'object' && ( typeof event.id === 'undefined' || typeof event.start === 'undefined' ) )
	||  ( $j.isNumeric( event ) && typeof start === 'undefined' ) ) {
		return false;
	}
	
	if( typeof event !== 'object' ) {
		// Format start values to object
		var event_id = event;
		start	= start.isMoment() ? start : moment( start );
		event	= {
			'id': event_id,
			'start': start
		};
	}
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-date="' + event.start.format( 'YYYY-MM-DD' ) + '"]' );
	
	// Format the selected event (because of popover, the same event can appears twice)
	elements.addClass( 'bookacti-selected-event' );
	elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', true );
	elements.find( '.bookacti-event-actions' ).show();
	elements.find( '.bookacti-event-action-select' ).show();

	// Keep picked events in memory 
	selectedEvents[ 'template' ].push( 
	{ 'event_id'			: event.id,
	'activity_id'			: event.activity_id,
	'event_title'			: event.title, 
	'event_start'			: event.start.format( 'YYYY-MM-DD HH:mm:ss' ), 
	'event_end'				: event.end.format( 'YYYY-MM-DD HH:mm:ss' ) } );

	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_select_event', [ event ] );
}


// Unselect an event
function bookacti_unselect_event( event, start, all ) {
	
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
		start	= start.isMoment() ? start : moment( start );
		event	= {
			'id': event_id,
			'start': start
		};
	}
	
	// Because of popover and long events (spreading on multiple days), 
	// the same event can appears twice, so we need to apply changes on each
	var elements = $j( '.fc-event[data-event-id="' + event.id + '"]' );
	if( ! all ) {
		elements = $j( '.fc-event[data-event-id="' + event.id + '"][data-event-date="' + event.start.format( 'YYYY-MM-DD' ) + '"]' );
	}
	
	// Format the selected event(s)
	elements.removeClass( 'bookacti-selected-event' );
	elements.find( '.bookacti-event-action-select-checkbox' ).prop( 'checked', false );
	elements.find( '.bookacti-event-action-select' ).hide();
	
	// Remove selected event(s) from memory 
	$j.each( selectedEvents[ 'template' ], function( i, selected_event ){
		if( typeof selected_event !== 'undefined' ) {
			if( selected_event.event_id == event.id 
			&&  (  all 
				|| selected_event.event_start.substr( 0, 10 ) === event.start.format( 'YYYY-MM-DD' ) ) ) {
				
				// Remove the event from the selected events array
				selectedEvents[ 'template' ].splice( i, 1 );
				
				// If only one event should be unselected, break the loop
				if( ! all ) {
					return false;
				}
			}
		}
	});
	
	$j( '#bookacti-template-calendar' ).trigger( 'bookacti_unselect_event', [ event, all ] );
}


//Update shortcode generator activities list
function bookacti_update_shortcode_generator_activity_ids( activity_ids, is_visible, remove_others ) {
	
	// Init and format parameters
	activity_ids	= $j.isNumeric( activity_ids ) ? [ activity_ids ] : activity_ids;
	is_visible		= is_visible ? true : false;
	remove_others	= remove_others ? true : false;
	
	$j( '.bookacti-shortcode-activity-ids' ).each( function(){
		// Retrieve activities displayed in shortcode
		var shortcode_activity_ids = [];
		if(  remove_others !== true && ! $j( this ).is( ':empty' ) ) {
			shortcode_activity_ids = $j( this ).text().split(',');
		}
		
		// Add or remove activity ids
		$j.each( activity_ids, function( i, activity_id ){
			var idx = $j.inArray( activity_id.toString(), shortcode_activity_ids );
			if( is_visible === true && idx === -1 ) {
				shortcode_activity_ids.push( activity_id );
			} else if( idx !== -1 ) {
				shortcode_activity_ids.splice( idx, 1 );
			}
		});

		// Display new activities list
		$j( this ).text( shortcode_activity_ids.join() );
	});
}


//Update shortcode generator groups list
function bookacti_update_shortcode_generator_group_ids( category_ids, is_visible, remove_others ) {
	
	// Init and format parameters
	category_ids	= $j.isNumeric( category_ids ) ? [ category_ids ] : category_ids;
	is_visible		= is_visible ? true : false;
	remove_others	= remove_others ? true : false;
	
	$j( '.bookacti-shortcode-group-ids' ).each( function(){
		// Retrieve activities displayed in shortcode
		var shortcode_category_ids = [];
		if(  remove_others !== true && ! $j( this ).is( ':empty' ) ) {
			shortcode_category_ids = $j( this ).text().split(',');
		}
		
		// Add or remove activity ids
		$j.each( category_ids, function( i, category_id ){
			var idx = $j.inArray( category_id.toString(), shortcode_category_ids );
			if( is_visible === true && idx === -1 ) {
				shortcode_category_ids.push( category_id );
			} else if( idx !== -1 ) {
				shortcode_category_ids.splice( idx, 1 );
			}
		});

		// Display new activities list
		$j( this ).text( shortcode_category_ids.join() );
	});
}


function bookacti_fetch_events_on_template( template_id, event_id ) {
    event_id = event_id || null;
    
    bookacti_start_template_loading(); 
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiFetchTemplateEvents', 
                'template_id': template_id, 
				'event_id': event_id,
				'nonce': bookacti_localized.nonce_fetch_template_events
			},
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				json_events = response.events;
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', json_events );
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


//Refresh completly the calendar
function bookacti_refetch_events_on_template( event ) {
    event = event || null;
    var event_id = null;
	
    //Clear the calendar
    if( event !== null) {
        if( event._id !== undefined ) {
			if( event._id.indexOf('_') >= 0 ) {
				$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event._id );
			}
        }
        $j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event.id );
        event_id = event.id;
    } else {
        $j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents' );
    }
    
    //Fetch events from the selected template
    bookacti_fetch_events_on_template( template_id, event_id );
}


//Launch dialogs
function bookacti_bind_template_dialogs() {
	if( template_id ) {
		$j( '#bookacti-update-template' ).off().on( 'click', 'img', function() { 
			bookacti_dialog_update_template( template_id ); 
		}); 
		$j( '#bookacti-template-activities-container' ).off().on( 'click', '#bookacti-insert-activity img, #bookacti-template-add-first-activity-button img', function() {
			if( $j( '#bookacti-template-picker option' ).length > 1 ) {
				bookacti_dialog_choose_activity_creation_type();
			} else {
				bookacti_dialog_create_activity();
			}
		});
	} else {
		$j( '#bookacti-update-template' ).off().on( 'click', 'img', function() {
			alert( bookacti_localized.error_no_template_selected );
		});
		$j( '#bookacti-template-activities-container' ).off().on( 'click', '#bookacti-insert-activity img, #bookacti-template-add-first-activity-button img', function() {
			alert( bookacti_localized.error_no_template_selected );
		});
	}
}


function bookacti_init_add_and_remove_items() {
	// Add a item to the items list
	$j( '.bookacti-items-container' ).on( 'click', '.bookacti-add-items', function( e ){
		var wrap = $j( this ).parents( '.bookacti-items-container' );
		
		// Retrieve data
		var is_multiple			= wrap.find( '.bookacti-add-new-items-select-box' ).is( '[multiple]' );
		var selected_item_ids	= wrap.find( '.bookacti-add-new-items-select-box' ).val();
		
		// Build an array of item ids
		var items_ids = selected_item_ids;
		if( ! is_multiple ) { items_ids = [ selected_item_ids ]; }
		
		$j.each( items_ids, function( i, item_id ){
			wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).clone().appendTo( wrap.find( '.bookacti-items-select-box' ) );
			wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).hide().attr( 'disabled', true );
			wrap.find( '.bookacti-add-new-items-select-box' ).val( wrap.find( '.bookacti-add-new-items-select-box option:enabled:first' ).val() );
		});
	});
	
	// Remove an item from the items list
	$j( '.bookacti-items-container' ).on( 'click', '.bookacti-remove-items', function( e ){
		
		var wrap = $j( this ).parents( '.bookacti-items-container' );
		var type = wrap.data( 'type' );
		var cannot_delete = '';
				if( type === 'users' )		{ cannot_delete = bookacti_localized.current_user_id; } 
		else	if( type === 'templates' )	{ cannot_delete = template_id; } 
		
		// Retrieve data
		var is_multiple			= wrap.find( '.bookacti-items-select-box' ).is( '[multiple]' );
		var selected_item_ids	= wrap.find( '.bookacti-items-select-box' ).val();
		
		// Build an array of item ids
		var items_ids = selected_item_ids;
		if( ! is_multiple ) { items_ids = [ selected_item_ids ]; }
		
		$j.each( items_ids, function( i, item_id ){
			if( item_id != cannot_delete ) {
				wrap.find( '.bookacti-items-select-box option[value="' + item_id + '"]' ).remove();
				wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).show().attr( 'disabled', false );
				wrap.find( '.bookacti-add-new-items-select-box' ).val( item_id );
			}
		});
	});
}


//Empty all dialog forms
function bookacti_empty_all_dialog_forms() {
    $j( '.bookacti-backend-dialogs .form-error' ).remove();
	$j( '.bookacti-backend-dialogs input[type="hidden"]:not([name^="nonce"]):not([name="_wp_http_referer"])' ).val( '' );
	$j( '.bookacti-backend-dialogs input[type="text"]' ).val( '' );
    $j( '.bookacti-backend-dialogs input[type="number"]' ).val( '' );
    $j( '.bookacti-backend-dialogs input[type="color"]' ).val( '#3a87ad' );
    $j( '.bookacti-backend-dialogs input[type="checkbox"]' ).attr( 'checked', false );
    $j( '.bookacti-backend-dialogs option' ).prop( 'selected', false );
    $j( '.bookacti-backend-dialogs .exception' ).remove();
    $j( '.bookacti-backend-dialogs select.bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );
    $j( '.bookacti-backend-dialogs select.bookacti-items-select-box option' ).remove();
}



// Fill custom settings fields in a form
function bookacti_fill_settings_fields( settings, prefix ) {
	$j.each( settings, function( key, value ) {
		if( settings[ key ] ) {
			if( $j( 'input[name="' + prefix + '[' + key + '][]"]' ).is( ':checkbox' ) 
			||  $j( 'input[name="' + prefix + '[' + key + ']"]' ).is( ':checkbox' ) ) {
				if( $j.isArray( value ) ){
					$j( 'input[name="' + prefix + '[' + key + '][]"]' ).attr( 'checked', false );
					$j.each( value, function( i, checkbox_value ){
						$j( 'input[name="' + prefix + '[' + key + '][]"][value="' + checkbox_value + '"]' ).attr( 'checked', true );
					});
				} else if( value == 1 ) {
					$j( 'input[name="' + prefix + '[' + key + ']"]' ).attr( 'checked', true );
				} else {
					$j( 'input[name="' + prefix + '[' + key + ']"]' ).attr( 'checked', false );
				}
			} else if( $j( 'input[name="' + prefix + '[' + key + ']"]' ).is( ':radio' ) ) {
				$j( 'input[name="' + prefix + '[' + key + ']"][value="' + value + '"]' ).prop( 'checked', true );
			} else if( $j( 'select[name="' + prefix + '[' + key + ']"]' ).length ) {
				$j( 'select[name="' + prefix + '[' + key + ']"] option[value="' + value + '"]' ).attr( 'selected', true );
			} else if( $j( 'select[name="' + prefix + '[' + key + '][]"]' ).length ) {
				$j.each( value, function( i, option ){
					$j( 'select[name="' + prefix + '[' + key + ']"] option[value="' + option + '"]' ).attr( 'selected', true );
				});
			} else {
				$j( 'input[name="' + prefix + '[' + key + ']"]' ).val( value );
				$j( 'textarea[name="' + prefix + '[' + key + ']"]' ).val( value );
			}
		}
	});
}


//Update Exception
function bookacti_update_exceptions( excep_template_id, event, forced_update ) {
    excep_template_id = excep_template_id || template_id;
	event = event || null;
    
    if( forced_update === null || forced_update === undefined ) { forced_update = true; }
    
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
                    exceptions[ excep_template_id ] = response.exceptions;
                } else {
                    if( exceptions[ excep_template_id ] === undefined ) { exceptions[ excep_template_id ] = []; }
                    exceptions[ excep_template_id ][ event_id ] = response.exceptions[ event_id ];
                }
                
            } else if( response.status === 'no_exception' ) {
                if( event === null ) {
                    exceptions[ excep_template_id ] = [];
                } else {
                    if( exceptions[ excep_template_id ] === undefined ) { exceptions[ excep_template_id ] = []; }
                    exceptions[ excep_template_id ][ event_id ] = [];
                }
                
            } else {
				var message_error = bookacti_localized.error_retrieve_exceptions;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
            }
            
            if( forced_update === true ) {
                bookacti_refetch_events_on_template( event );
            }
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


//Determine if event is locked or not
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


//Unbind occurences of a booked event
function bookacti_unbind_occurrences( event, occurences ) {
	
    bookacti_start_template_loading();
    
    $j.ajax({
        url: ajaxurl, 
        data: { 'action': 'bookactiUnbindOccurences', 
                'unbind': occurences,
                'event_id': event.id,
                'event_start': event.start.format( 'YYYY-MM-DD[T]HH:mm:ss' ),
                'event_end': event.end.format( 'YYYY-MM-DD[T]HH:mm:ss' ),
				'nonce': bookacti_localized.nonce_unbind_occurences
            },
        type: 'POST',
        dataType: 'json',
        success: function( response ){
			
			if( response.status === 'success' ){
                
				$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );
                bookacti_update_exceptions( null, event );
                
                if( occurences === 'booked' ) {
                    bookacti_update_exceptions( null, response.events[0] );
                }
				
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


//Update Bookings
function bookacti_update_bookings( template_id, event_id ) {
    template_id = template_id || null;
    event_id    = event_id || null;
    
    bookacti_start_template_loading();
    
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { 'action': 'bookactiGetBookings', 
                'template_id': template_id, 
                'event_id': event_id,
				'nonce': bookacti_localized.nonce_get_bookings
			},
        dataType: 'json',
        success: function( response ){
            
			if( response.status === 'success' ) {
                if( event_id === null ) {
                    bookings[ template_id ] = response.bookings;
                } else {
                    if( bookings[ template_id ] === undefined ) { bookings[ template_id ] = []; }
                    bookings[ template_id ][ event_id ] = response.bookings[ event_id ];
                }
                
				// Lock booked events
				$j.each( bookings[ template_id ], function( booking_event_id, bookings ) {
					if( $j.inArray( booking_event_id, lockedEvents ) === -1 ) {
						lockedEvents.push( booking_event_id );
					}
				});
				
            } else if( response.status === 'no_bookings' ) {
                if( event_id === null ) {
                    bookings[ template_id ] = [];
                } else {
                    if( bookings[ template_id ] === undefined ) { bookings[ template_id ] = []; }
                    bookings[ template_id ][ event_id ] = [];
                }
                
            } else {
				var error_message = bookacti_localized.error_retrieve_bookings;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
				console.log( response );
            }
            
        },
        error: function( e ){
            alert ( 'AJAX ' + bookacti_localized.error_retrieve_bookings );
            console.log( e.responseText );
        },
        complete: function() { 
            bookacti_stop_template_loading();
        }
    });
}


//Start a loading (or keep on loading if already loading)
function bookacti_start_template_loading() {
	
	if( loadingNumber['template'] === 0 ) {
		bookacti_enter_template_loading_state();
	}
	
	loadingNumber['template']++;
}

//Stop a loading (but keep on loading if there are other loadings )
function bookacti_stop_template_loading() {
	
	loadingNumber['template']--;
	loadingNumber['template'] = Math.max( loadingNumber['template'], 0 );
	
	if( loadingNumber['template'] === 0 ) {
		bookacti_exit_template_loading_state();
	}
}

//Enter loading state and prevent user from doing anything else
function bookacti_enter_template_loading_state() {
	
	var loading_div =	'<div class="bookacti-loading-alt">' 
							+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
							+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
						+ '</div>';
	
	if( ! $j.isNumeric( loadingNumber[ 'template' ] ) ) {
		loadingNumber[ 'template' ] = 0;
	}
	
	if( $j( '#bookacti-template-calendar' ).find( '.fc-view-container' ).length ) {
		if( loadingNumber[ 'template' ] === 0 || ! $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-overlay' ).length ) {
			 $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).remove();
			bookacti_enter_calendar_loading_state( $j( '#bookacti-template-calendar' ) );
		}
	} else if( !  $j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).length ) {
		 $j( '#bookacti-template-calendar' ).prepend( loading_div );
	}
	
	
	blockEvents = true;
	$j( '#bookacti-template-sidebar img' ).addClass( 'bookacti-disabled-img' );
	$j( '.bookacti-template-dialogs' ).find( 'input, select, button' ).attr( 'disabled', true );
	$j( '#bookacti-template-picker' ).attr( 'disabled', true );
	$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
}

//Exit loading state and allow user to keep editing templates
function bookacti_exit_template_loading_state( force_exit ) {
	
	force_exit = force_exit || false;
	
	if( force_exit ) { loadingNumber[ 'template' ] = 0; }
	
	bookacti_exit_calendar_loading_state( $j( '#bookacti-template-calendar' ) );
	$j( '#bookacti-template-calendar' ).find( '.bookacti-loading-alt' ).remove();
	
	blockEvents = false;
	$j( '#bookacti-template-sidebar img' ).removeClass( 'bookacti-disabled-img' );
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
		if( selectedEvents[ 'template' ].length >= 2 ) {
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
function bookacti_expand_collapse_groups_of_events( category_id, force_to ) {
	force_to = $j.inArray( force_to, [ 'expand', 'collapse' ] ) !== -1 ? force_to : false;
	var is_shown = $j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups' );
	if( ( is_shown || force_to === 'collapse' ) && force_to !== 'expand' ) {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 0 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-list' ).hide( 200 );
	} else if( ( ! is_shown || force_to === 'expand' ) && force_to !== 'collapse' ) {
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).attr( 'data-show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).data( 'show-groups', 1 );
		$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-groups-of-events-list' ).show( 200 );
	}
}

// Load activities bound to selected template
function bookacti_load_activities_bound_to_template( selected_template_id ) {
	
	if( parseInt( selected_template_id ) !== parseInt( template_id ) ) {

		$j( '#bookacti-activities-bound-to-template .form-error' ).remove();

		bookacti_start_template_loading();

		$j.ajax({
			url: ajaxurl, 
			data: { 'action': 'bookactiGetActivitiesByTemplate', 
					'selected_template_id': selected_template_id,
					'current_template_id': template_id,
					'nonce': bookacti_localized.nonce_get_activities_by_template
				},
			type: 'POST',
			dataType: 'json',
			success: function(response) {
				if( response.status === 'success' ) {
					// Fill the available activities select box
					var activity_options = '';
					$j.each( response.activities, function( activity_id, activity ){
						if( ! $j( '#bookacti-template-activity-list .activity-row .fc-event[data-activity-id="' + activity_id + '"]' ).length ) {
							activity_options += '<option '
												+ 'value="' + activity_id + '" '
												+ 'data-title="' + activity.multilingual_title + '" ' 
												+ 'data-color="' + activity.color + '" ' 
												+ 'data-availability="' + activity.availability + '" ' 
												+ 'data-duration="' + activity.duration + '" ' 
												+ 'data-resizable="' + activity.is_resizable + '" >' 
													+ activity.title 
											+ '</option>';
						}
					});
					if( activity_options !== '' ) {
						$j( 'select#activities-to-import' ).empty().append( activity_options );
					} else {
						$j( '#bookacti-activities-bound-to-template' ).append( '<div class="form-error">' + bookacti_localized.error_no_avail_activity_bound + '</div>' );
					}
				} else if ( response.status === 'no_activity' ) {
					$j( '#bookacti-activities-bound-to-template' ).append( '<div class="form-error">' + bookacti_localized.error_no_avail_activity_bound + '</div>' );
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