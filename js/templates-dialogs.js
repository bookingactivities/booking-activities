// INITIALIZATION

/**
 * Initialize calendar editor dialogs
 * @version 1.12.0
 */
function bookacti_init_template_dialogs() {
	// Common param
	$j( '.bookacti-template-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		520,
		"resize":		'auto',
		"show":			true,
		"hide":			true,
		"dialogClass":	'bookacti-dialog',
		"closeText":	'&#10006;',
		"beforeClose":	function() { 
			var scope = '.bookacti-template-dialog';
			var dialog_id = $j( this ).attr( 'id' );
			if( dialog_id ) { scope = '#' + dialog_id; }
			bookacti_empty_all_dialog_forms( scope ); 
		}
	});

	// Make dialogs close when the user click outside
	$j( 'body' ).on( 'click', '.ui-widget-overlay', function (){
		$j( 'div:ui-dialog:visible' ).dialog( 'close' );
	});

	// Press ENTER to bring focus on OK button
	$j( '.bookacti-template-dialog' ).on( 'keydown', function( e ) {
		if( ! $j( 'textarea' ).is( ':focus' ) && e.keyCode == $j.ui.keyCode.ENTER ) {
			$j( this ).parent().find( '.ui-dialog-buttonpane button:first' ).focus(); 
			return false; 
		}
	});	

	// Individual param
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog({
		beforeClose: function(){}
	});

	// Add and remove items in managers and templates select boxes
	bookacti_init_add_and_remove_items();

	// Load activities bound to selected template
	$j( 'select#template-import-bound-activities' ).on( 'change', function(){
		bookacti_load_activities_bound_to_template( $j( 'select#template-import-bound-activities' ).val() );
	});

	// Init new template dialog
	$j( '#bookacti-template-container' ).on( 'click', '#bookacti-insert-template, #bookacti-add-first-template-button', function() { 
		bookacti_dialog_add_new_template(); 
	});
	
	
	/**
	 * Fill template settings by default while duplicating a template (if current template is duplicated)
	 * @since 1.7.18
	 * @version 1.12.0
	 */
	$j( '#bookacti-template-duplicated-template-id' ).on( 'change', function() {
		var duplicated_template_id = parseInt( $j( this ).val() );
		if( ! duplicated_template_id ) { return; }
		
		var current_template_id = parseInt( $j( '#bookacti-template-picker' ).val() );
		var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
		
		if( ! $j.isEmptyObject( template_data.settings ) && current_template_id === duplicated_template_id ) {
			// Fill template settings
			bookacti_fill_fields_from_array( template_data.settings );
		}
	});
	
	
	/**
	 * Init update activity dialog
	 * @version 1.11.0
	 */
	$j( '#bookacti-template-activity-list' ).on( 'click', '.bookacti-activity-settings', function() {
		var activity_id = $j( this ).closest( '.bookacti-activity' ).data( 'activity-id' );
		bookacti_dialog_update_activity( activity_id ); 
	});

	// Init create group of events dialog
	$j( '#bookacti-template-groups-of-events-container' ).on( 'click', '#bookacti-template-add-first-group-of-events-button, #bookacti-insert-group-of-events', function() {
		bookacti_dialog_create_group_of_events();
	});

	// Init CTRL+G shortcut to display "create group of events" dialog
	$j( document ).on( 'keydown', function( e ) {
		if( e.ctrlKey && e.keyCode == 71 ) {
			e.preventDefault(); e.stopPropagation(); // Prevent browser's default action
			if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].length >= 2 ) {
				bookacti_dialog_create_group_of_events();
			}
		}
	});


	/**
	 * Init update group of events dialog
	 * @version 1.9.0
	 */
	$j( '#bookacti-template-groups-of-events-container' ).on( 'click', '.bookacti-update-group-of-events', function() {
		var group_id	= $j( this ).closest( '.bookacti-group-of-events' ).data( 'group-id' );
		var is_selected	= $j( this ).closest( '.bookacti-group-of-events' ).hasClass( 'bookacti-selected-group' );
		var are_selected = is_selected;
		if( ! is_selected ) {
			are_selected = bookacti_select_events_of_group( group_id );
		}
		if( are_selected ) { bookacti_dialog_update_group_of_events( group_id ); };
	});

	
	/**
	 * Init update group category dialog
	 * @version 1.9.0
	 */
	$j( '#bookacti-group-categories' ).on( 'click', '.bookacti-update-group-category', function() {
		var category_id = $j( this ).closest( '.bookacti-group-category' ).data( 'group-category-id' );
		bookacti_dialog_update_group_category( category_id ); 
	});

	/**
	 * Prevent sending form
	 * @param {event} e
	 */
	$j( '.bookacti-template-dialog form' ).on( 'submit', function( e ){
		e.preventDefault();
	});

	/**
	 * Display or hide new group category title field
	 */
	$j( '#bookacti-group-of-events-category-selectbox' ).on( 'change blur', function() {
		if( $j( this ).val() === 'new' ){
			$j( '#bookacti-group-of-events-new-category-title' ).show();
		} else {
			$j( '#bookacti-group-of-events-new-category-title' ).hide();
		}
	});
	
	/**
	 * Toggle week starts on notice according to repeat_every option in event dialog
	 * @since 1.11.0
	 * @version 1.12.0
	 */
	$j( 'body' ).on( 'change', 'input[name="repeat_step"], select[name="repeat_freq"]', function() { 
		var skip_weeks = parseInt( $j( this ).closest( '.bookacti-field-container' ).find( 'input[name="repeat_step"]' ).val() ) > 1 && $j( this ).closest( '.bookacti-field-container' ).find( 'select[name="repeat_freq"]' ).val() === 'weekly';
		$j( this ).closest( '.bookacti-field-container' ).find( '.bookacti-repeat-freq-start-of-week-notice' ).toggle( skip_weeks );
	});
	
	/**
	 * Toggle the "Send notifications" option according to the "Cancel bookings" option value - when an event is deleted
	 * @since 1.10.0
	 */
	$j( '#bookacti-delete-event-cancel_bookings' ).on( 'change', function() { 
		$j( '#bookacti-delete-event-send_notifications-container' ).toggle( $j( '#bookacti-delete-event-cancel_bookings' ).is( ':checked' ) );
	});
	
	/**
	 * Toggle the "Send notifications" option according to the "Cancel bookings" option value - when a group of events is deleted
	 * @since 1.10.0
	 */
	$j( '#bookacti-delete-group-of-events-cancel_bookings' ).on( 'change', function() { 
		$j( '#bookacti-delete-group-of-events-send_notifications-container' ).toggle( $j( '#bookacti-delete-group-of-events-cancel_bookings' ).is( ':checked' ) );
	});
	
	/**
	 * Toggle the BANP promo for the admin message according to the "Cancel bookings" and "Send notifications" options values
	 * @since 1.10.0
	 */
	$j( '#bookacti-delete-event-cancel_bookings, #bookacti-delete-event-send_notifications' ).on( 'change', function() { 
		$j( '#bookacti-delete-event-dialog .bookacti-banp-promo-admin-message' ).toggle( $j( '#bookacti-delete-event-cancel_bookings' ).is( ':checked' ) && $j( '#bookacti-delete-event-send_notifications' ).is( ':checked' ) );
	});
	$j( '#bookacti-delete-group-of-events-cancel_bookings, #bookacti-delete-group-of-events-send_notifications' ).on( 'change', function() { 
		$j( '#bookacti-delete-group-of-events-dialog .bookacti-banp-promo-admin-message' ).toggle( $j( '#bookacti-delete-group-of-events-cancel_bookings' ).is( ':checked' ) && $j( '#bookacti-delete-group-of-events-send_notifications' ).is( ':checked' ) );
	});
	$j( '#bookacti-update-booked-event-dates-send_notifications' ).on( 'change', function() { 
		$j( '#bookacti-update-booked-event-dates-dialog .bookacti-banp-promo-admin-message' ).toggle( $j( '#bookacti-update-booked-event-dates-send_notifications' ).is( ':checked' ) );
	});
}


// TEMPLATES

/**
 * Dialog Create Template
 * @version 1.12.0
 */
function bookacti_dialog_add_new_template() {
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-template-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-template-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.create_new + ')'
	});

	// Set default values
	$j( '#bookacti-mintime' ).val( '00:00' );
	$j( '#bookacti-maxtime' ).val( '00:00' );
	$j( '#bookacti-snapduration' ).val( '00:05' );

	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_default_template_settings' );

	// Show and activate the duplicate fields
	if( $j( '#bookacti-template-duplicated-template-id option' ).length > 1 ) {
		$j( '#bookacti-template-duplicated-template-id-container' ).show();
		$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', false );
	} else {
		$j( '#bookacti-template-duplicated-template-id-container' ).hide();
		$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', true );
	}

	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_data_dialog' );

	// Open the modal dialog
	$j( '#bookacti-template-data-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-template-data-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				// Remove old feedback
				$j( '#bookacti-template-data-dialog .bookacti-notices' ).remove();
				
				// Prepare fields
				$j( '#bookacti-template-data-form-action' ).val( 'bookactiInsertTemplate' );
				$j( '#bookacti-template-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				// Get the data to save
				var title = $j( '#bookacti-template-title' ).val();
				
				if( typeof tinyMCE !== 'undefined' ) { if( tinyMCE ) { tinyMCE.triggerSave(); } }

				var isFormValid = bookacti_validate_template_form();

				if( isFormValid ) {
					var data = $j( '#bookacti-template-data-form' ).serializeObject();
					
					// Display a loader
					if( $j( '#bookacti-template-calendar' ).length ) {
						bookacti_start_template_loading();
					} else if( $j( '#bookacti-add-first-template-button' ).length ) {
						$j( '#bookacti-add-first-template-button' ).removeClass( 'dashicons dashicons-plus-alt' ).addClass( 'spinner' );
					}

					$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_insert_template_before', [ data ] );
					
					var loading_div = '<div class="bookacti-loading-alt">' 
										+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
										+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
									+ '</div>';
					$j( '#bookacti-template-data-dialog' ).append( loading_div );
					
					// Save the new template in database
					$j.ajax({
						url: ajaxurl, 
						data: data, 
						type: 'POST',
						dataType: 'json',
						success: function( response ){
							//If success
							if( response.status === 'success' ) {
								// If it is the first template, change the bookacti-first-template-container div to bookacti-template-calendar div
								if( $j( '#bookacti-first-template-container' ).is( ':visible' ) ) {
									$j( '#bookacti-first-template-container' ).before( $j( '<div id="bookacti-template-calendar" ></div>' ) );
									$j( '#bookacti-first-template-container' ).hide();
									$j( '.bookacti-no-template' ).removeClass( 'bookacti-no-template' );
									bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );
									bookacti_start_template_loading();
								}

								// Add the template to the template select box
								$j( '#bookacti-template-picker' ).append(
									"<option value='" + response.template_id + "'>" + title + "</option>"
								);

								// Add the template to other template select boxes
								$j( 'select.bookacti-template-select-box' ).append(
									"<option value='" + response.template_id + "'>" + title + "</option>"
								);

								// If the created template is the second one, you need to refresh dialog bounds
								// because clicking on new activity will now ask whether to create or import activity
								if( $j( '#bookacti-template-picker option' ).length === 2 ) {
									bookacti_bind_template_dialogs();
								}

								$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_inserted', [ response, data ] );
								
								// Close the modal dialog
								$j( '#bookacti-template-data-dialog' ).dialog( 'close' );
								
								// Switch template the new created one
								bookacti_switch_template( response.template_id );


							// If error
							} else if( response.status === 'failed' ) {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								$j( '#bookacti-template-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
								$j( '#bookacti-template-data-dialog .bookacti-notices' ).show();
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error );        
							console.log( e );
						},
						complete: function() {
							if( $j( '#bookacti-add-first-template-button' ).length ) {
								$j( '#bookacti-add-first-template-button' ).removeClass( 'spinner' ).addClass( 'dashicons dashicons-plus-alt' );
							}
							bookacti_stop_template_loading();
							$j( '#bookacti-template-data-dialog .bookacti-loading-alt' ).remove();
						}
					});
				}
			}
		}]
	);
}


/**
 * Dialog Update Template
 * @version 1.12.0
 * @param {int} template_id
 */
function bookacti_dialog_update_template( template_id ) {
	if( ! template_id ) { return false; }

	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-template-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-template-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + template_id + ')'
	});

	// Hide and deactivate duplicate fields
	$j( '#bookacti-template-duplicated-template-id-container' ).hide();
	$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', true );

	var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
	var events = $j( '#bookacti-template-calendar' ).fullCalendar( 'clientEvents' );

	// General tab
	$j( '#bookacti-template-title' ).val( template_data.title );

	// Permissions tab
	if( template_data.admin.length ) {
		var items_container = $j( '#bookacti-template-managers-container' );
		bookacti_fill_items_selectbox( items_container, template_data.admin );
	}

	// Settings tabs
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_default_template_settings' );
	if( ! $j.isEmptyObject( template_data.settings ) ) {
		bookacti_fill_fields_from_array( template_data.settings );
	}
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_data_dialog', [ template_id ] );

	// Add buttons
	$j( '#bookacti-template-data-dialog' ).dialog( 'option', 'buttons',
		// Add the 'OK' button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Remove old feedback
				$j( '#bookacti-template-data-dialog .bookacti-notices' ).remove();
				
				// Prepare fields
				$j( '#bookacti-template-data-form-template-id' ).val( template_id );
				$j( '#bookacti-template-data-form-action' ).val( 'bookactiUpdateTemplate' );
				$j( '#bookacti-template-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				if( typeof tinyMCE !== 'undefined' ) { if( tinyMCE ) { tinyMCE.triggerSave(); } }

				var isFormValid = bookacti_validate_template_form();
				if( ! isFormValid ) { return; }
				
				var data = $j( '#bookacti-template-data-form' ).serializeObject();

				$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_update_template_before', [ data ] );
				
				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-template-data-dialog' ).append( loading_div );

				// Save changes in database
				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						// If success
						if( response.status === 'success' ) {
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ]	= response.template_data;
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ]	= response.template_data.settings;

							// Change template metas in the select box
							$j( '#bookacti-template-picker option[value=' + template_id + ']' ).html( response.template_data.title );

							// Dynamically update template settings
							$j( '#bookacti-template-calendar' ).replaceWith( '<div id="bookacti-template-calendar" class="bookacti-calendar"></div>' );
							bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', events );

							$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_updated', [ response, data ] );
							
							// Close the modal dialog
							$j( '#bookacti-template-data-dialog' ).dialog( 'close' );
							
						// If error
						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-template-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-template-data-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ) {
						alert( 'AJAX ' + bookacti_localized.error );        
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading();
						$j( '#bookacti-template-data-dialog .bookacti-loading-alt' ).remove();
					}
				});
			}
		},
		
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			click: function() {
				bookacti_dialog_deactivate_template( template_id );
			}
		}]
	);

	// Open the modal dialog
	$j( '#bookacti-template-data-dialog' ).dialog( 'open' );
}


/**
 * Dialog Deactivate Template
 * @version 1.12.0
 * @param {int} template_id
 * @returns {false}
 */
function bookacti_dialog_deactivate_template( template_id ) {
	if( ! template_id ) { return false; }

	// Add the 'OK' button
	$j( '#bookacti-delete-template-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				// Remove old feedback
				$j( '#bookacti-delete-template-dialog .bookacti-notices' ).remove();
				
				var data = { 
					'action': 'bookactiDeactivateTemplate', 
					'template_id': template_id,
					'nonce': $j( '#nonce_edit_template' ).val()
				};

				$j( '#bookacti-delete-template-dialog' ).trigger( 'bookacti_deactivate_template_before', [ data ] );
				
				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-delete-template-dialog' ).append( loading_div );

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Remove the template from the select box
							$j( '#bookacti-template-picker option[value=' + template_id + ']' ).remove();
							var new_template_id = $j( '#bookacti-template-picker option:first' ).val();

							// Remove the template from other template select boxes
							$j( 'select.bookacti-template-select-box option[value=' + template_id + ']' ).remove();

							// If there is only 1 template left, you need to refresh dialog bounds
							// because clicking on new activity has to stop offer to import activity
							if( $j( '#bookacti-template-picker option' ).length === 1 ) {
								bookacti_bind_template_dialogs();
							}

							$j( '#bookacti-delete-template-dialog' ).trigger( 'bookacti_template_deactivated', [ response, data ] );
							
							// Close the modal dialog
							$j( '#bookacti-delete-template-dialog' ).dialog( 'close' );
							$j( '#bookacti-template-data-dialog' ).dialog( 'close' );
							
							// Switch template to the first one in the select box
							bookacti_switch_template( new_template_id );

						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-delete-template-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-delete-template-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					}
					,
					complete: function() { 
						bookacti_stop_template_loading();
						$j( '#bookacti-delete-template-dialog .bookacti-loading-alt' ).remove();
					}
				});
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				// Close the modal dialog
				$j( '#bookacti-delete-template-dialog' ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-delete-template-dialog' ).dialog( 'open' );
}




// EVENTS

/**
 * Warning Dialog when trying to update a booked event dates (move or resize an event)
 * @since 1.10.0
 * @param {object} event
 * @param {object} delta
 * @param {callable} revertFunc
 */
function bookacti_dialog_update_booked_event_dates( event, delta, revertFunc ) {
	// Sanitize params
	delta = typeof delta !== 'undefined' ? delta : { '_days': 0, '_milliseconds': 0 };
	revertFunc = typeof revertFunc !== 'undefined' && revertFunc !== false ? revertFunc : false;
	
	// Reset the dialog
	$j( '#bookacti-update-booked-event-dates-dialog .bookacti-bookings-nb' ).remove();
	$j( '#bookacti-update-booked-event-dates-send_notifications-container .bookacti-notifications-nb' ).remove();
	$j( '.bookacti-update-booked-repeated-event-dates-warning' ).hide();
	$j( '#bookacti-update-booked-event-dates-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-update-booked-event-dates-send_notifications' ).prop( 'checked', false ).trigger( 'change' );
	
	$j( '#bookacti-update-booked-event-dates-dialog' ).trigger( 'bookacti_update_booked_event_dates_dialog', [ event, delta, revertFunc ] );
	
	// Init the OK and Cancel buttons
	var buttons = 
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				bookacti_update_event_dates( event, delta, revertFunc, 'booked' );
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() { 
				if( revertFunc !== false ) { revertFunc(); } 
				$j( this ).dialog( 'close' );
			}
		}];
	
	// Add the Unbind button if the event is repeated
	var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
	if( is_repeated ) {
		$j( '.bookacti-update-booked-repeated-event-dates-warning' ).show();
		buttons.push({
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-left-button',
			click: function() { 
				// Revert event
				if( revertFunc !== false ) { revertFunc(); }
				var old_event_start_time = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'start' ].substr( 11, 8 );
				var old_event_end_time = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'end' ].substr( 11, 8 );
				event.start = moment.utc( moment.utc( event.start ).clone().subtract( delta._days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + old_event_start_time );
				event.end = moment.utc( moment.utc( event.end ).clone().subtract( delta._days, 'days' ).format( 'YYYY-MM-DD' ) + ' ' + old_event_end_time );
				
				// Close the dialog
				$j( this ).dialog( 'close' ); 
				
				// Open the unbind dialog
				bookacti_dialog_unbind_occurrences( event );
			}
		});
	}
	
	// Revert the event if the dialog is closed
	$j( '#bookacti-update-booked-event-dates-dialog' ).on( 'dialogbeforeclose', function() { if( revertFunc !== false ) { revertFunc(); } } );
	
	// Add the buttons
	$j( '#bookacti-update-booked-event-dates-dialog' ).dialog( 'option', 'buttons', buttons );
	
	// Open the modal dialog
	$j( '#bookacti-update-booked-event-dates-dialog' ).dialog( 'open' );
}


/**
 * Dialog Update Event
 * @version 1.12.0
 * @param {object} event
 */
function bookacti_dialog_update_event( event ) {
	// Remove old feedback
	$j( '#bookacti-event-data-dialog .bookacti-notices' ).remove();
	
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-event-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-event-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + event.id + ')'
	});
	
	var event_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ];
	
	// General tab
	$j( '#bookacti-event-data-dialog' ).data( 'event-id', event.id );
	$j( '#bookacti-event-data-dialog' ).attr( 'data-event-id', event.id );
	$j( '#bookacti-event-title' ).val( event_data.multilingual_title );
	$j( '#bookacti-event-availability' ).val( event_data.availability );

	// Repetition tab
	bookacti_fill_repetition_fields( event.id, 'event' );
	
	// Other settings
	if( typeof event_data.settings !== 'undefined' ) {
		bookacti_fill_fields_from_array( event_data.settings, '', '#bookacti-event-data-dialog' );
	}

	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_event_update_dialog', [ event ] );

	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( '#bookacti-event-data-dialog .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}

	// Validate the fields
	bookacti_validate_event_form();

	// Prepare buttons
	var buttons = [];
	// OK button
	var ok_button = {
		text: bookacti_localized.dialog_button_ok,
		click: function() {
			// Remove old feedback
			$j( '#bookacti-event-data-dialog .bookacti-notices' ).remove();
			
			// Prepare fields
			var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
			$j( '#bookacti-event-data-form-event-id' ).val( event.id );
			$j( '#bookacti-event-data-form-event-start' ).val( event_start_formatted );
			$j( '#bookacti-event-data-form-event-end' ).val( event_end_formatted );
			$j( '#bookacti-event-data-form-action' ).val( 'bookactiUpdateEvent' );
			$j( '#bookacti-event-data-form select[multiple]#bookacti-event-exceptions-selectbox option' ).prop( 'selected', true );

			if( typeof tinyMCE !== 'undefined' ) { if( tinyMCE ) { tinyMCE.triggerSave(); } }

			var isFormValid = bookacti_validate_event_form();
			if( ! isFormValid ) { return; }
			
			var data = $j( '#bookacti-event-data-form' ).serializeObject();
			data.interval = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];

			$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_update_event_before', [ event, data ] );

			// Display a loader
			bookacti_start_template_loading();
			var loading_div = '<div class="bookacti-loading-alt">' 
								+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
								+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
							+ '</div>';
			$j( '#bookacti-event-data-dialog' ).append( loading_div );

			// Write new param in database
			$j.ajax({
				url: ajaxurl, 
				data: data,
				type: 'POST',
				dataType: 'json',
				success: function( response ) {
					// If success
					if( response.status === 'success' ) {
						var event_id = event.id;

						// Unselect the event or occurrences of the event
						bookacti_unselect_event( event, true );

						// Update event data
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ] = response.events_data[ event_id ];

						// Update groups events
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] = response.groups_events;

						// Update the exceptions list
						bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ] = [];
						$j.each( response.exceptions_dates, function( i, new_exception ) {
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ].push( new_exception );
						});
						
						// Delete old event
						bookacti_clear_events_on_calendar( $j( '#bookacti-template-calendar' ), event );

						// Add new event
						$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event_id );
						$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );

						// addEventSource will rerender events, new exceptions will then be taken into account

						$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_event_updated', [ event, response, data ] );
						

					// If error
					} else if( response.status === 'failed' )  {
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						$j( '#bookacti-event-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
						$j( '#bookacti-event-data-dialog .bookacti-notices' ).show();
						console.log( response );
					}
					
					// Close the dialog
					if( response.status !== 'failed' ) {
						$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
					}
				},
				error: function( e ) {
					alert( 'AJAX ' + bookacti_localized.error );        
					console.log( e );
				},
				complete: function() { 
					$j( '#bookacti-event-data-dialog .bookacti-loading-alt' ).remove();
					bookacti_stop_template_loading();
				}
			});
		}
	};
	buttons.push( ok_button );

	// DELETE button
	var delete_button = {
		text: bookacti_localized.dialog_button_delete,
		'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
		click: function() {
			// Remove old feedback
			$j( '#bookacti-event-data-dialog .bookacti-notices' ).remove();
			
			// Display a loader
			bookacti_start_template_loading();
			var loading_div = '<div class="bookacti-loading-alt">' 
								+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
								+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
							+ '</div>';
			$j( '#bookacti-event-data-dialog' ).append( loading_div );
			
			var data = {
				'action': 'bookactiBeforeDeleteEvent',
				'event_id': event.id,
				'nonce': $j( '#nonce_delete_event' ).val()
			};
			
			$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_before_deactivate_event', [ event, data ] );
			
			$j.ajax({
				url: ajaxurl, 
				data: data,
				type: 'POST',
				dataType: 'json',
				success: function( response ) {
					// If success
					if( response.status === 'success' ) {
						// Close current dialog
						$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
						
						// Open the dialog to confirm the event deletion
						bookacti_dialog_delete_event( event );
						
						// Display the number of bookings to be rescheduled and the number of users to be notified
						$j( '#bookacti-delete-booked-event-options' ).toggle( parseInt( response.has_bookings ) ? true : false );
						$j( '#bookacti-delete-event-cancel_bookings' ).prop( 'checked', parseInt( response.has_bookings ) ? true : false ).trigger( 'change' );
						$j( '#bookacti-delete-event-cancel_bookings-container' ).append( '<span class="bookacti-bookings-nb">' + response.bookings_nb + '</span>' );
						$j( '#bookacti-delete-event-send_notifications-container' ).append( '<span class="bookacti-notifications-nb">' + response.notifications_nb + '</span>' );
						
					// If error
					} else if( response.status === 'failed' )  {
						var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
						$j( '#bookacti-event-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
						$j( '#bookacti-event-data-dialog .bookacti-notices' ).show();
						console.log( response );
					}
				},
				error: function( e ) {
					alert( 'AJAX ' + bookacti_localized.error );        
					console.log( e );
				},
				complete: function() { 
					$j( '#bookacti-event-data-dialog .bookacti-loading-alt' ).remove();
					bookacti_stop_template_loading();
				}
			});
		}
	};
	buttons.push( delete_button );

	// UNBIND button
	var unbind_button =	{};
	if( event_data.repeat_freq !== 'none' ) {
		unbind_button =	{
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-unbind-button bookacti-dialog-left-button',
			click: function() {
				// Close current dialog
				$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
				// Open the dialog to unbind events
				bookacti_dialog_unbind_occurrences( event );
			}
		};
		buttons.push( unbind_button );
	}
	
	// MOVE button
	var move_button = {
		text: bookacti_localized.dialog_button_move,
		'class': 'bookacti-dialog-move-button bookacti-dialog-left-button',
		click: function() {
			// Close current dialog
			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
			// Open the dialog to move the event
			bookacti_dialog_update_event_dates( event );
		}
	};
	buttons.push( move_button );

	// Add dialog buttons
	$j( '#bookacti-event-data-dialog' ).dialog( 'option', 'buttons', buttons );

	// Open the modal dialog
	$j( '#bookacti-event-data-dialog' ).dialog( 'open' );
}


/**
 * Dialog Move Event
 * @since 1.10.0
 * @param {object} event
 */
function bookacti_dialog_update_event_dates( event ) {
	// Reset the dialog
	$j( '#bookacti-update-event-dates-dialog .bookacti-selected-event-start, #bookacti-update-event-dates-dialog .bookacti-selected-event-end' ).empty();
	$j( '.bookacti-update-repeated-event-dates-warning' ).hide();
	$j( '#bookacti-update-event-dates-dialog .bookacti-notices' ).remove();
	
	// Fill the information about the selected event
	$j( '#bookacti-update-event-dates-dialog .bookacti-selected-event-start' ).html( event.start.format( 'LLLL' ) );
	$j( '#bookacti-update-event-dates-dialog .bookacti-selected-event-end' ).html( event.end.format( 'LLLL' ) );

	// Fill the fields
	$j( '#bookacti-update-event-dates-start_date' ).val( moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) );
	$j( '#bookacti-update-event-dates-start_time' ).val( moment.utc( event.start ).clone().locale( 'en' ).format( 'HH:mm' ) );
	$j( '#bookacti-update-event-dates-end_date' ).val( moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) );
	$j( '#bookacti-update-event-dates-end_time' ).val( moment.utc( event.end ).clone().locale( 'en' ).format( 'HH:mm' ) );
	
	
	var buttons = [
		{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Remove old feedbacks
				$j( '#bookacti-update-event-dates-dialog .bookacti-notices' ).remove();
				
				// Compute the delta
				var old_event_start	= moment.utc( moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) );
				var old_event_end	= moment.utc( moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ) );
				var new_event_start	= moment.utc( $j( '#bookacti-update-event-dates-start_date' ).val() + ' ' + $j( '#bookacti-update-event-dates-start_time' ).val() );
				var new_event_end	= moment.utc( $j( '#bookacti-update-event-dates-end_date' ).val() + ' ' + $j( '#bookacti-update-event-dates-end_time' ).val() );
				var delta = moment.duration( new_event_start.diff( old_event_start ) );
				delta._days = moment.utc( moment.utc( new_event_start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) ).diff( moment.utc( moment.utc( old_event_start ).clone().locale( 'en' ).format( 'YYYY-MM-DD' ) ), 'days' );
				
				// Set the new event
				var new_event = $j.extend( {}, event );
				new_event.start = new_event_start;
				new_event.end = new_event_end;
				
				// Check if event start is after event end
				var error_message = '';
				if( new_event_end.isSameOrBefore( new_event_start ) )	{ error_message = bookacti_localized.error_end_before_start; }
				if( old_event_start.isSame( new_event_start ) 
				&&  old_event_end.isSame( new_event_end ) )				{ error_message = bookacti_localized.error_fill_field; }
				
				// Display the error messages
				if( error_message ) {
					$j( '#bookacti-update-event-dates-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
					$j( '#bookacti-update-event-dates-dialog .bookacti-notices' ).show();
					return;
				}
				
				// Update event dates
				bookacti_update_event_dates( new_event, delta, false, 'normal' );
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}];
	
	// Add the Unbind button if the event is repeated
	var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
	if( is_repeated ) {
		$j( '.bookacti-update-repeated-event-dates-warning' ).show();
		buttons.push({
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-left-button',
			click: function() { 
				// Open the unbind dialog
				$j( this ).dialog( 'close' ); 
				bookacti_dialog_unbind_occurrences( event );
			}
		});
	}
	
	// Add the buttons
	$j( '#bookacti-update-event-dates-dialog' ).dialog( 'option', 'buttons', buttons );
	
	// Open the modal dialog
	$j( '#bookacti-update-event-dates-dialog' ).dialog( 'open' );
}


/**
 * Dialog Delete Event
 * @version 1.10.0
 * @param {object} event
 */
function bookacti_dialog_delete_event( event ) {
	// Reset the dialog
	$j( '#bookacti-delete-event-cancel_bookings-container .bookacti-bookings-nb' ).remove();
	$j( '#bookacti-delete-event-send_notifications-container .bookacti-notifications-nb' ).remove();
	$j( '.bookacti-delete-booked-repeated-event-warning' ).hide();
	$j( '#bookacti-delete-event-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-delete-booked-event-options' ).show();
	$j( '#bookacti-delete-event-cancel_bookings' ).prop( 'checked', true ).trigger( 'change' );
	$j( '#bookacti-delete-event-send_notifications' ).prop( 'checked', false ).trigger( 'change' );
	
	$j( '#bookacti-delete-event-dialog' ).trigger( 'bookacti_delete_event_dialog', [ event ] );
	
	var buttons = [
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',
			click: function() {
				// Remove old feedbacks
				$j( '#bookacti-delete-event-dialog .bookacti-notices' ).remove();
				
				var data = $j( '#bookacti-delete-event-form' ).serializeObject();
				data.event_id = event.id;
				
				$j( '#bookacti-delete-event-dialog' ).trigger( 'bookacti_deactivate_event_before', [ event, data ] );
				
				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-delete-event-dialog' ).append( loading_div );

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Remove the event from the calendar
							bookacti_delete_event( event );
							
							$j( '#bookacti-delete-event-dialog' ).trigger( 'bookacti_event_deactivated', [ event, response, data ] );
							
							// Close the dialog
							$j( '#bookacti-delete-event-dialog' ).dialog( 'close' );
							
						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-delete-event-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-delete-event-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() { 
						$j( '#bookacti-delete-event-dialog .bookacti-loading-alt' ).remove();
						bookacti_stop_template_loading(); 
					}
				});
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}];
	
	// Add the Unbind button if the event is repeated
	var is_repeated = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] !== 'none';
	if( is_repeated ) {
		$j( '.bookacti-delete-booked-repeated-event-warning' ).show();
		buttons.push({
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-left-button',
			click: function() { 
				// Open the unbind dialog
				$j( this ).dialog( 'close' ); 
				bookacti_dialog_unbind_occurrences( event );
			}
		});
	}
	
	// Add the buttons
	$j( '#bookacti-delete-event-dialog' ).dialog( 'option', 'buttons', buttons );
	
	// Open the modal dialog
	$j( '#bookacti-delete-event-dialog' ).dialog( 'open' );
}


/**
 * Dialog Unbind occurrence of a locked repeating event
 * @since 1.8.4 (was bookacti_dialog_unbind_occurences)
 * @version 1.10.0
 * @param {object} event
 */
function bookacti_dialog_unbind_occurrences( event ) {
	// Remove old feedbacks
	$j( '#bookacti-unbind-booked-event-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-unbind-booked-event-dialog .bookacti-form-error' ).remove();
	$j( '#bookacti-unbind-booked-event-dialog .bookacti-input-warning' ).removeClass( 'bookacti-input-warning' );
	
	// Fill the information about the selected event
	$j( '#bookacti-unbind-booked-event-dialog .bookacti-selected-event-start' ).html( event.start.format( 'LLLL' ) );
	$j( '#bookacti-unbind-booked-event-dialog .bookacti-selected-event-end' ).html( event.end.format( 'LLLL' ) );
	
	// Toggle the description when an option is selected
	$j( 'input[type="radio"][name="unbind_action"]' ).on( 'change', function() {
		$j( '.bookacti-unbind-action small' ).hide();
		$j( 'input[type="radio"][name="unbind_action"]:checked' ).closest( '.bookacti-unbind-action' ).find( 'small' ).show();
	});
	
	// Default values
	$j( 'input[type="radio"][name="unbind_action"][value="selected"]' ).prop( 'checked', true ).trigger( 'change' );
	
	// Add buttons
	var buttons = [
		{
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-delete-button',
			click: function() {
				// Remove old feedbacks
				$j( '#bookacti-unbind-booked-event-dialog .bookacti-notices' ).remove();
				$j( '#bookacti-unbind-booked-event-dialog .bookacti-form-error' ).remove();
				$j( '#bookacti-unbind-booked-event-dialog .bookacti-input-warning' ).removeClass( 'bookacti-input-warning' );
				
				var form_data = $j( '#bookacti-unbind-booked-event-form' ).serializeObject();
				
				var event_start = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				var data = $j.extend({
					'event_id': event.id,
					'event_start': event_start,
					'event_end': moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' ),
					'interval': bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ]
				}, form_data );

				$j( '#bookacti-template-container' ).trigger( 'bookacti_unbind_occurrences_before', [ data, event ] );

				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-unbind-booked-event-dialog' ).append( loading_div );

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ){
						if( response.status === 'success' ) {
							// Unselect all occurrences of the event
							bookacti_unselect_event( event, true );

							// Update grouped events
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] = response.groups_events;
							
							// Update new events data
							var new_events_ids = response.new_events_ids;
							$j.each( new_events_ids, function( i, new_event_id ) {
								// Update new event data
								if( typeof response.events_data[ new_event_id ] !== 'undefined' ) {
									bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ] = response.events_data[ new_event_id ];
								} else if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ] !== 'undefined' ) {
									delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ new_event_id ];
								}
								// Update new event repeat exceptions
								if( typeof response.exceptions[ new_event_id ] !== 'undefined' ) {
									bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ new_event_id ] = response.exceptions[ new_event_id ];
								} else if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ new_event_id ] !== 'undefined' ) {
									delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ new_event_id ];
								}
								// Update new event bookings
								if( typeof response.bookings[ new_event_id ] !== 'undefined' ) {
									bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ new_event_id ] = response.bookings[ new_event_id ];
								} else if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ new_event_id ] !== 'undefined' ) {
									delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ new_event_id ];
								}
							});
							
							
							// Load the new events on calendar and delete the old ones
							// Calling addEventSource will rerender events and then new exceptions will be taken into account
							$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event.id );
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );

							$j( '#bookacti-template-container' ).trigger( 'bookacti_occurrences_unbound', [ response, data, event ] );

							// Close the modal dialog
							$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'close' );

						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-unbind-booked-event-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-unbind-booked-event-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() { 
						$j( '#bookacti-unbind-booked-event-dialog .bookacti-loading-alt' ).remove();
						bookacti_stop_template_loading();
					}
				});
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}];

	// Add the buttons
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'option', 'buttons', buttons );
	
	// Open the modal dialog
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'open' );
}


/**
 * Fill the repetition fields of the (group of) events dialog
 * @since 1.12.0
 * @param {Int} object_id
 * @param {String} object_type 'event' or 'group'
 */
function bookacti_fill_repetition_fields( object_id, object_type ) {
	if( object_type !== 'group' && object_type !== 'event' ) { return; }
	if( object_type === 'group' && typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ][ 0 ] === 'undefined' ) { return; }
	else if( object_type === 'event' && typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ] === 'undefined' ) { return; }
	
	var event = object_type === 'group' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ][ 0 ] : bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ];
	var event_start = moment.utc( event.start ).clone();
	var repeat_data = object_type === 'group' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ object_id ] : bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ object_id ];
	
	var exceptions_key	= object_type === 'group' ? 'G' + object_id : object_id;
	var exception_dates	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ exceptions_key ];

	var event_28_days	= event_start.clone().add( 28, 'd' ).locale( 'en' ); // The default repeat period duration is 28 days
	var repeat_from     = event_start.clone().locale( 'en' ).format( 'YYYY-MM-DD' );
	var repeat_to       = event_28_days.isBefore( moment.utc( '2037-12-31' ) ) ? event_28_days.format( 'YYYY-MM-DD' ) : '2037-12-31';

	if( repeat_data.repeat_from && repeat_data.repeat_from !== '0000-00-00' )	{ repeat_from = repeat_data.repeat_from; };
	if( repeat_data.repeat_to   && repeat_data.repeat_to   !== '0000-00-00' )	{ repeat_to = repeat_data.repeat_to; };
	
	var repeat_step = repeat_data.repeat_step && $j.isNumeric( repeat_data.repeat_step ) ? Math.max( 1, parseInt( repeat_data.repeat_step ) ) : 1;
	var repeat_days = repeat_data.repeat_freq === 'weekly' && repeat_data.repeat_on ? repeat_data.repeat_on.split( '_' ) : [ parseInt( event_start.format( 'd' ) ) ];
	var repeat_monthly_type = repeat_data.repeat_freq === 'monthly' && repeat_data.repeat_on && $j.inArray( repeat_data.repeat_on, [ 'nth_day_of_month', 'nth_day_of_week', 'last_day_of_month', 'last_day_of_week' ] ) >= 0 ? repeat_data.repeat_on : 'nth_day_of_month';
	
	var exceptions_disabled = false;
	var exceptions_min = moment.utc( repeat_from ).add( 1, 'd' ).locale( 'en' );
	var exceptions_max = moment.utc( repeat_to ).subtract( 1, 'd' ).locale( 'en' );
	if( exceptions_min.isAfter( exceptions_max ) ) { exceptions_disabled = true; };
	
	var scope = object_type === 'group' ? '#bookacti-group-of-events-dialog' : '#bookacti-event-data-dialog';
	
	// Fill the form with database param
	$j( scope + ' select[name="repeat_freq"] option[value="' + repeat_data.repeat_freq + '"]' ).prop( 'selected', true );
	$j( scope + ' select[name="repeat_monthly_type"] option[value="' + repeat_monthly_type + '"]' ).prop( 'selected', true );
	$j( scope + ' input[name="repeat_step"]' ).val( repeat_step ).trigger( 'change' );
	$j( scope + ' input[name="repeat_from"]' ).val( repeat_from );
	$j( scope + ' input[name="repeat_to"]' ).val( repeat_to );
	$j( scope + ' input[name="exceptions_dates[]"]' ).empty();
	$j( scope + ' .bookacti-exception-date-picker' ).val( repeat_from );
	if( ! exceptions_disabled ) {
		$j( scope + ' .bookacti-exception-date-picker' ).attr( 'disabled', false );
		$j( scope + ' .bookacti-exception-date-picker' ).attr( 'min', exceptions_min.format( 'YYYY-MM-DD' ) );
		$j( scope + ' .bookacti-exception-date-picker' ).attr( 'max', exceptions_max.format( 'YYYY-MM-DD' ) );
	} else {
		$j( scope + ' .bookacti-exception-date-picker' ).attr( 'disabled', true );
	}
	
	// Fill the exceptions field
	if( typeof exception_dates !== 'undefined' ) {
		$j.each( exception_dates, function( i, value ) {
			$j( scope + ' input[name="exceptions_dates[]"]' ).append( "<option class='bookacti-exception' value='" + value + "' >" + value + "</option>" );
		});
	}
	
	// Fill the repeat_days checkboxes
	if( $j.isArray( repeat_days ) ){
		$j.each( repeat_days, function( i, checkbox_value ) {
			$j( scope + ' input[type="checkbox"][name="repeat_days[]"][value="' + checkbox_value + '"]' ).prop( 'checked', true ).trigger( 'change' );
		});
	}
	
	// Fill the repeat_monthly_type options labels
	var nth_day_of_month = event_start.format( 'Do' );
	var day_of_week = event_start.format( 'dddd' );
	event_start.locale( 'en' );
	var nth_day_of_week_int = Math.ceil( parseInt( event_start.format( 'DD' ) ) / 7 );
	var nth_day_of_week = moment.utc( event_start.format( 'YYYY' ) + '-0' + nth_day_of_week_int + '-01' ).format( 'Mo' );
	var is_last_day_of_month = parseInt( event_start.format( 'D' ) ) === parseInt( event_start.daysInMonth() ) ? true : false;
	var is_last_day_of_week = parseInt( event_start.format( 'D' ) ) >= ( parseInt( event_start.daysInMonth() ) - 6 ) ? true : false;
	
	$j( scope + ' select[name="repeat_monthly_type"] option' ).each( function() {
		var default_label = $j( this ).data( 'default-label' );
		if( default_label ) {
			var custom_label = default_label.replace( '{nth_day_of_month}', nth_day_of_month ).replace( '{nth_day_of_week}', nth_day_of_week ).replace( '{day_of_week}', day_of_week );
			$j( this ).html( custom_label );
		}
	});
	
	// Display the last day of... options if the selected event is on it
	$j( scope + ' select[name="repeat_monthly_type"] option[value="last_day_of_month"]' ).toggle( is_last_day_of_month );
	$j( scope + ' select[name="repeat_monthly_type"] option[value="last_day_of_week"]' ).toggle( is_last_day_of_week );
}




// ACTIVITIES

/**
 * Dialog Choose between creating a brand new activity or binding an existing activity to current template
 * @version 1.7.10
 */
function bookacti_dialog_choose_activity_creation_type() {
	if( ! bookacti.selected_template ) { return; }
	
	// Add buttons
	var create_activity_button = {
		text: bookacti_localized.dialog_button_create_activity,
		click: function() {
			// Close the modal dialog
			$j( this ).dialog( 'close' );

			// Open create activity dialog
			bookacti_dialog_create_activity();
		}
	};
	var import_activity_button = {
		text: bookacti_localized.dialog_button_import_activity,
		click: function() {
			// Close the modal dialog
			$j( this ).dialog( 'close' );

			// Open import activity dialog
			bookacti_dialog_import_activity();
		}
	};
	var cancel_button = {
		text: bookacti_localized.dialog_button_cancel,
		click: function() {
			// Close the modal dialog
			$j( this ).dialog( 'close' );
		}
	};

	var buttons = [ create_activity_button, import_activity_button, cancel_button ];
	if( $j( '#bookacti-template-picker option' ).length <= 1 ) {
		buttons.unshift( import_activity_button );
	}

	$j( '#bookacti-activity-create-method-dialog' ).dialog( 'option', 'buttons', buttons );

	// Open the modal dialog
	$j( '#bookacti-activity-create-method-dialog' ).dialog( 'open' );
}


/**
 * Dialog Import Activity
 * @version 1.12.0
 */
function bookacti_dialog_import_activity() {
	if( ! bookacti.selected_template ) { return; }
	
	// Deactivate current template in template selector
	$j( '#template-import-bound-activities option' ).attr( 'disabled', false );
	$j( '#template-import-bound-activities option[value="' + bookacti.selected_template + '"]' ).attr( 'disabled', true );

	// Select the first enabled template
	$j( '#template-import-bound-activities' ).children( 'option:enabled' ).eq( 0 ).prop( 'selected', true );
	$j( '#template-import-bound-activities' ).trigger( 'change' );

	// Add the 'OK' button
	$j( '#bookacti-activity-import-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Remove old feedback
				$j( '#bookacti-activity-import-dialog .bookacti-notices' ).remove();

				var activity_ids = $j( 'select#bookacti-activities-to-import' ).val();

				if( $j.isEmptyObject( activity_ids ) ) {
					$j( '#bookacti-activity-import-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + bookacti_localized.error_fill_field + '</li></ul></div>' );
					return;
				}
				
				var data = { 
					'action': 'bookactiImportActivities', 
					'activity_ids': activity_ids,
					'template_id': bookacti.selected_template,
					'nonce': $j( '#nonce_import_activity' ).val()
				};

				$j( '#bookacti-activity-import-dialog' ).trigger( 'bookacti_import_activities_before', [ data ] );

				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-activity-import-dialog' ).append( loading_div );

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function(response) {
						if( response.status === 'success' ) {
							// Update activities data array
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ] = response.activities_data;

							// Refresh the draggable activity list
							if( response.activity_list ) {
								$j( '#bookacti-template-activity-list' ).empty().append( response.activity_list );
							}

							// Remove the added activity from the import activity select box
							$j.each( activity_ids, function( i, activity_id ) {
								$j( 'select#bookacti-activities-to-import option[value="' + activity_id + '"]' ).remove();
							});

							// Reinitialize the activities to apply changes
							bookacti_init_activities();

							$j( '#bookacti-activity-import-dialog' ).trigger( 'bookacti_activities_imported', [ response, data ] );

							// Close the modal dialogs
							$j( '#bookacti-activity-import-dialog' ).dialog( 'close' );
							$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );

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
						$j( '#bookacti-activity-import-dialog .bookacti-loading-alt' ).remove();
					}
				});
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				// Close the modal dialog
				$j( '#bookacti-activity-import-dialog' ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-activity-import-dialog' ).dialog( 'open' );
}


/**
 * Dialog Create Activity
 * @version 1.8.0
 */
function bookacti_dialog_create_activity() {
	if( ! bookacti.selected_template ) { return; }
	
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-activity-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.create_new + ')'
	});

	// Set init value
	$j( '#bookacti-activity-template-id' ).val( bookacti.selected_template );
	$j( '#bookacti-activity-activity-id' ).val( '' );
	$j( '#bookacti-activity-color' ).val( '#3a87ad' );
	$j( '#bookacti-activity-action' ).val( 'bookactiInsertActivity' );
	
	// Add the 'OK' button
	$j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Prepare fields
				$j( '#bookacti-activity-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_activity_form();
				if( ! is_form_valid ) { return; }
				
				var data = $j( '#bookacti-activity-data-form' ).serializeObject();

				$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_insert_activity_before', [ data ] );

				bookacti_start_template_loading();

				// Save the new activity in database
				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						// Retrieve plugin path to display the gear
						if( response.status === 'success' ) {
							// Update activities data array
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ response.activity_id ] = response.activity_data;

							// Refresh the draggable activity list
							if( response.activity_list ) {
								$j( '#bookacti-template-activity-list' ).empty().append( response.activity_list );
							}

							// Reinitialize the activities to apply changes
							bookacti_init_activities();

							$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_activity_inserted', [ response, data ] );

						// If error
						} else if( response.status === 'failed' ) {
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

				// Close the modal dialogs
				$j( '#bookacti-activity-data-dialog' ).dialog( 'close' );
				$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-activity-data-dialog' ).dialog( 'open' );
}


/**
 * Open a dialog to update an activity
 * @version 1.10.0
 * @param {Int} activity_id
 */
function bookacti_dialog_update_activity( activity_id ) {
	if( ! bookacti.selected_template || ! activity_id ) { return; }
	
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-activity-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + activity_id + ')'
	});

	var activity_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ];
								
	// Set init value
	$j( '#bookacti-activity-template-id' ).val( bookacti.selected_template );
	$j( '#bookacti-activity-activity-id' ).val( activity_id );
	$j( '#bookacti-activity-action' ).val( 'bookactiUpdateActivity' );
	$j( '#bookacti-activity-data-dialog .bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );

	// General tab
	$j( '#bookacti-activity-title' ).val( activity_data.multilingual_title ); 
	$j( '#bookacti-activity-color' ).val( activity_data.color );
	$j( '#bookacti-activity-availability' ).val( activity_data.availability );
	
	var activity_duration = activity_data.duration ? activity_data.duration : '000.01:00:00';
	$j( '#bookacti-activity-duration-days' ).val( activity_duration.substr( 0, 3 ) ).trigger( 'change' );
	$j( '#bookacti-activity-duration-hours' ).val( activity_duration.substr( 4, 2 ) ).trigger( 'change' );
	$j( '#bookacti-activity-duration-minutes' ).val( activity_duration.substr( 7, 2 ) ).trigger( 'change' );
	
	// Permissions tab
	if( activity_data.admin.length ) {
		var items_container = $j( '#bookacti-activity-managers-container' );
		bookacti_fill_items_selectbox( items_container, activity_data.admin );
	}

	// Settings tabs
	if( activity_data.settings ) {
		bookacti_fill_fields_from_array( activity_data.settings, 'activityOptions' );
		$j( '#bookacti-activity-unit-name-singular' ).val( activity_data.settings.multilingual_unit_name_singular ); 
		$j( '#bookacti-activity-unit-name-plural' ).val( activity_data.settings.multilingual_unit_name_plural ); 
	}
	
	$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_activity_update_dialog', [ activity_id ] );
	
	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( '#bookacti-activity-data-dialog .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Add buttons
	$j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'buttons',
		//Add the 'OK' button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Prepare fields
				$j( '#bookacti-activity-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_activity_form();
				if( ! is_form_valid ) { return; }
				
				var data = $j( '#bookacti-activity-data-form' ).serializeObject();

				$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_update_activity_before', [ data ] );

				bookacti_start_template_loading();

				// Save updated values in database
				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						// If success
						if( response.status === 'success' ) {
							// Update activities data array
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ] = response.activity_data;

							// Refresh the draggable activity list
							if( response.activity_list ) {
								$j( '#bookacti-template-activity-list' ).empty().append( response.activity_list );
							}

							// Reinitialize the activities to apply changes
							bookacti_init_activities();

							// Clear the calendar and refetch events
							bookacti_refetch_events_on_template();

							$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_activity_updated', [ response, data ] );

						// If error
						} else if( response.status === 'failed' ) {
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
				$j( this ).dialog( 'close' );
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			click: function() {
				bookacti_dialog_delete_activity( activity_id );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-activity-data-dialog' ).dialog( 'open' );
}


/**
 * Dialog Delete Activity
 * @version 1.11.0
 * @param {int} activity_id
 */
function bookacti_dialog_delete_activity( activity_id ) {
	if( ! activity_id ) { return; }

	// Check default option
	$j( '#bookacti-delete-activity-events' ).prop( 'checked', false );

	// Open the modal dialog
	$j( '#bookacti-delete-activity-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-activity-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				// Check if events must be deleted too
				var data = { 
					'action': 'bookactiDeactivateActivity', 
					'activity_id': activity_id,
					'template_id': bookacti.selected_template,
					'delete_events': $j( '#bookacti-delete-activity-events' ).is( ':checked' ) ? 1 : 0,
					'nonce': $j( '#nonce_deactivate_activity' ).val()
				};
				
				$j( '#bookacti-delete-activity-dialog' ).trigger( 'bookacti_deactivate_activity_before', [ data ] );
				
				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function(response) {
						if( response.status === 'success' ) {
							// Update activities data array
							delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'activities_data' ][ activity_id ];

							$j( '.bookacti-activity[data-activity-id="' + activity_id + '"]' ).remove();

							// Refresh events if user chose to deleted them
							if( data.delete_events ) { bookacti_refetch_events_on_template(); }

							// Display tuto if there is no more activities available
							bookacti_display_activity_tuto_if_no_activity_available();
							
							$j( '#bookacti-delete-activity-dialog' ).trigger( 'bookacti_activity_deactivated', [ response, data ] );
							
						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							alert( error_message );
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
				$j( this ).dialog( 'close' );
				$j( '#bookacti-activity-data-dialog' ).dialog( 'close' );
				$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,

			click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
	);
}




// GROUPS OF EVENTS

/**
 * Create a group of events
 * @version 1.12.0
 * @param {int} category_id
 */
function bookacti_dialog_create_group_of_events( category_id ) {
	category_id = category_id ? category_id : bookacti.selected_category;
	
	// Change dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.create_new + ')'
	});
	
	// Disable the "New category title" field if a category as been chosen
	if( $j( '#bookacti-group-of-events-category-selectbox option' ).length > 1 ) {
		// Select the category provided
		category_id = $j( '#bookacti-group-of-events-category-selectbox option[value="' + category_id + '"]' ).length ? category_id : 'new';
		$j( '#bookacti-group-of-events-category-selectbox' ).val( category_id ).trigger( 'change' );
		
	} else {
		$j( '#bookacti-group-of-events-new-category-title' ).show();
	}
	
	// Fill the events list as a feedback for user
	$j( '#bookacti-group-of-events-summary' ).empty();
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = bookacti_sort_events_array_by_dates( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, event ){
		
		var event_start = moment.utc( event.start );
		var event_end = moment.utc( event.end );
		
		var event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + event_end.formatPHP( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + event_end.formatPHP( bookacti_localized.time_format );
		}
		var option = $j( '<option></option>', {
						'html': event_duration + ' - ' + event.title
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});
	
	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Prepare fields
				$j( '#bookacti-group-of-events-action' ).val( 'bookactiInsertGroupOfEvents' );
				$j( '#bookacti-group-of-events-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				// Get the data to save
				var selected_category_id	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
				bookacti.selected_category	= selected_category_id;
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_group_of_events_form();
				
				if( is_form_valid ) {
					var data = $j( '#bookacti-group-of-events-form' ).serializeObject();
					data.template_id = bookacti.selected_template;
					data.events = JSON.stringify( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
					
					$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_insert_group_of_events_before', [ data ] );
					
					bookacti_start_template_loading();
					
					// Save the new group of events in database
					$j.ajax({
						url: ajaxurl, 
						data: data,
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							// If success
							if( response.status === 'success' ) {
								// Store the events of the groups and update group and category data
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ response.group_id ]				= response.group_events;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ response.group_id ]				= response.group;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ][ response.category_id ]	= response.category;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]								= [];
								
								// If it is the first group of events, hide tuto and show groups list
								$j( '#bookacti-template-add-first-group-of-events-container' ).hide();
								
								// If the user has created a group category
								if( selected_category_id === 'new' ) {
									bookacti_add_group_category( response.category_id, response.category.title );
								}
								
								// Add the group row to the category
								bookacti_add_group_of_events( response.group_id, response.group_title_raw, response.category_id );
								
								// Unselect the events
								bookacti_unselect_all_events();
								
								// Refresh events
								$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
								
								$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_group_of_events_inserted', [ response, data ] );
								
								// Close the modal dialog
								$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
								
							// If error
							} else {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								alert( error_message );
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
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );
}


/**
 * Update a group of events with selected events 
 * @version 1.12.0
 * @param {int} group_id
 */
function bookacti_dialog_update_group_of_events( group_id ) {
	// Remove old feedback
	$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).remove();
	
	// Change dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + group_id + ')'
	});
	
	// Select the group category
	var category_id = $j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).closest( '.bookacti-group-category' ).data( 'group-category-id' );
	var initial_category_id = category_id ? category_id : 'new';
	$j( '#bookacti-group-of-events-category-selectbox' ).val( category_id ).trigger( 'change' );

	// Fill the events list as a feedback for user
	$j( '#bookacti-group-of-events-summary' ).empty();
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = bookacti_sort_events_array_by_dates( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, event ){
		var event_start = moment.utc( event.start );
		var event_end = moment.utc( event.end );
		
		var event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + event_end.formatPHP( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + event_end.formatPHP( bookacti_localized.time_format );
		}
		var option = $j( '<option></option>', {
						'html': event_duration + ' - ' + event.title
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});
	
	
	var group_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ];
				
	// General tab
	$j( '#bookacti-group-of-events-title-field' ).val( group_data.multilingual_title ); 
	
	// Repetition tab
	bookacti_fill_repetition_fields( group_id, 'group' );
	
	// Other settings
	if( group_data.settings ) {
		bookacti_fill_fields_from_array( group_data.settings, 'groupOfEventsOptions' );
	}
	
	$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_group_of_events_update_dialog', [ group_id ] );

	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( '#bookacti-group-of-events-dialog .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Validate the fields
	bookacti_validate_group_of_events_form();
	
	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Remove old feedback
				$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).remove();
				
				// Prepare fields
				$j( '#bookacti-group-of-events-action' ).val( 'bookactiUpdateGroupOfEvents' );
				$j( '#bookacti-group-of-events-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				$j( '#bookacti-group-of-events-form select[multiple]#bookacti-group-of-events-exceptions-selectbox option' ).prop( 'selected', true );
				
				// Get the data to save
				var selected_category_id	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
				bookacti.selected_category	= selected_category_id;
				
				if( typeof tinyMCE !== 'undefined' ) { if( tinyMCE ) { tinyMCE.triggerSave(); } }
				
				var is_form_valid = bookacti_validate_group_of_events_form();
				
				if( is_form_valid ) {
					var data = $j( '#bookacti-group-of-events-form' ).serializeObject();
					data.group_id = group_id;
					data.events = JSON.stringify( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
					
					$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_update_group_of_events_before', [ data ] );
					
					bookacti_start_template_loading();
					
					// Save the new group of events in database
					$j.ajax({
						url: ajaxurl, 
						data: data,
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							// If success
							if( response.status === 'success' ) {
								// Update the events of the groups and the group and category data
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ]						= response.group_events;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ]						= response.group;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ][ response.category_id ]	= response.category;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ]								= [];
								
								// If the user has created a group category
								if( selected_category_id === 'new' ) {
									bookacti_add_group_category( response.category_id, response.category.title );
								}
								
								// If user changed category
								if( initial_category_id != selected_category_id ) {
									// Remove the group from the old categroy and add it to the new one
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).remove();
									bookacti_add_group_of_events( group_id, response.group_title_raw, response.category_id );
									
								} else {
									// Update group title in groups list
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"] .bookacti-group-of-events-title' ).attr( 'title', response.group_title_raw );
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"] .bookacti-group-of-events-title' ).html( response.group_title_raw );
								}
								
								// Unselect the events
								bookacti_unselect_all_events();
								
								// Refresh events
								$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
								
								$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_group_of_events_updated', [ response, data ] );
								
							} else if( response.status === 'failed' ) {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								$j( '#bookacti-group-of-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
								$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).show();
								console.log( response );
							}
							
							// Close the dialog
							if( response.status !== 'failed' ) {
								$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
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
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			click: function() {
				// Remove old feedback
				$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).remove();

				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-group-of-events-dialog' ).append( loading_div );

				var data = {
					'action': 'bookactiBeforeDeleteGroupOfEvents',
					'group_id': group_id,
					'nonce': $j( '#nonce_delete_group_of_events' ).val()
				};

				$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_before_deactivate_group_of_events', [ event, data ] );

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						// If success
						if( response.status === 'success' ) {
							// Close current dialog
							$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );

							// Open the dialog to confirm the event deletion
							bookacti_dialog_delete_group_of_events( group_id );

							// Display the number of bookings to be rescheduled and the number of users to be notified
							$j( '#bookacti-delete-booked-group-of-events-options' ).toggle( parseInt( response.has_bookings ) ? true : false );
							$j( '#bookacti-delete-group-of-events-cancel_bookings' ).prop( 'checked', parseInt( response.has_bookings ) ? true : false ).trigger( 'change' );
							$j( '#bookacti-delete-group-of-events-cancel_bookings-container' ).append( '<span class="bookacti-bookings-nb">' + response.booking_groups_nb + ' (' + response.bookings_nb + ')</span>' );
							$j( '#bookacti-delete-group-of-events-send_notifications-container' ).append( '<span class="bookacti-notifications-nb">' + response.notifications_nb + '</span>' );

						// If error
						} else if( response.status === 'failed' )  {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-group-of-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ) {
						alert( 'AJAX ' + bookacti_localized.error );        
						console.log( e );
					},
					complete: function() { 
						$j( '#bookacti-group-of-events-dialog .bookacti-loading-alt' ).remove();
						bookacti_stop_template_loading();
					}
				});
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );
}


/**
 * Dialog Delete a group of events
 * @version 1.10.0
 * @param {int} group_id
 */
function bookacti_dialog_delete_group_of_events( group_id ) {
	// Reset the dialog
	$j( '#bookacti-delete-group-of-events-cancel_bookings-container .bookacti-bookings-nb' ).remove();
	$j( '#bookacti-delete-group-of-events-send_notifications-container .bookacti-notifications-nb' ).remove();
	$j( '#bookacti-delete-group-of-events-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-delete-booked-group-of-events-options' ).show();
	$j( '#bookacti-delete-group-of-events-cancel_bookings' ).prop( 'checked', true ).trigger( 'change' );
	$j( '#bookacti-delete-group-of-events-send_notifications' ).prop( 'checked', false ).trigger( 'change' );
	
	$j( '#bookacti-delete-group-of-events-dialog' ).trigger( 'bookacti_delete_group_of_events_dialog', [ group_id ] );
	
	// Add the 'OK' button
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',
			click: function() {
				// Remove old feedbacks
				$j( '#bookacti-delete-group-of-events-dialog .bookacti-notices' ).remove();
				
				var data = $j( '#bookacti-delete-group-of-events-form' ).serializeObject();
				data.group_id = group_id;
				
				$j( '#bookacti-delete-group-of-events-dialog' ).trigger( 'bookacti_deactivate_group_of_events_before', [ data ] );
				
				// Display a loader
				bookacti_start_template_loading();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-delete-group-of-events-dialog' ).append( loading_div );
				
				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Update global
							delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ];
							delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_id ];
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
							
							// Remove the group of events from its category
							$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).remove();
							
							// Refresh events
							if( data.cancel_bookings && typeof response.bookings !== 'undefined' ) {
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ] = [];
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ] = response.bookings;
							}
							
							$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
							$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
							
							$j( '#bookacti-delete-group-of-events-dialog' ).trigger( 'bookacti_group_of_events_deactivated', [ response, data ] );
							
							// Close the dialog
							$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'close' );
							
						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-delete-group-of-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							$j( '#bookacti-delete-group-of-events-dialog .bookacti-notices' ).show();
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() { 
						$j( '#bookacti-delete-group-of-events-dialog .bookacti-loading-alt' ).remove();
						bookacti_stop_template_loading(); 
					}
				});
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
	);
	
	// Open the dialog
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'open' );
}




// GROUP CATEGORIES

/**
 * Update a group category
 * @version 1.8.0
 * @param {int} category_id
 */
function bookacti_dialog_update_group_category( category_id ) {
	// Change dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-group-category-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-group-category-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + category_id + ')'
	});
	
	var category_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ][ category_id ];
	
	// General tab
	$j( '#bookacti-group-category-title-field' ).val( category_data.multilingual_title ); 

	// Other settings
	if( category_data.settings ) {
		bookacti_fill_fields_from_array( category_data.settings, 'groupCategoryOptions' );
	}
	
	$j( '#bookacti-group-category-dialog' ).trigger( 'bookacti_group_category_update_dialog', [ category_id ] );
	
	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( '#bookacti-group-category-dialog .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Add the 'OK' button
	$j( '#bookacti-group-category-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Prepare fields
				$j( '#bookacti-group-category-action' ).val( 'bookactiUpdateGroupCategory' );
				$j( '#bookacti-group-category-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_group_category_form();

				if( is_form_valid ) {
					var data = $j( '#bookacti-group-category-form' ).serializeObject();
					data.category_id = category_id;
					
					$j( '#bookacti-group-category-dialog' ).trigger( 'bookacti_update_group_category_before', [ data ] );
					
					bookacti_start_template_loading();

					// Save the new activity in database
					$j.ajax({
						url: ajaxurl, 
						data: data,
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							// If success
							if( response.status === 'success' ) {
								// Update global
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ][ category_id ] = response.category;
								
								// Update category title in groups list
								var category_short_title = response.category.title.length > 16 ? response.category.title.substr( 0, 16 ) + '&#8230;' : response.category.title;
								$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-group-category-title' ).attr( 'title', response.category.title );
								$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-group-category-title span' ).html( category_short_title );
								
								// Update category title in selectbox
								$j( '#bookacti-group-of-events-category-selectbox option[value="' + category_id + '"]' ).html( response.category.title );
								
								$j( '#bookacti-group-category-dialog' ).trigger( 'bookacti_group_category_updated', [ response, data ] );
								
							// If error
							} else if( response.status === 'failed' ) {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								alert( error_message );
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

					// Close the modal dialog
					$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
				}
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			click: function() {
				bookacti_dialog_delete_group_category( category_id );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-group-category-dialog' ).dialog( 'open' );
}


/**
 * Delete a group category
 * @version 1.8.0
 * @param {int} category_id
 */
function bookacti_dialog_delete_group_category( category_id ) {
	// Add the 'OK' button
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() {
				var data = { 
					'action': 'bookactiDeleteGroupCategory', 
					'category_id': category_id,
					'nonce': $j( '#nonce_delete_group_category' ).val()
				};
				
				$j( '#bookacti-delete-group-category-dialog' ).trigger( 'bookacti_deactivate_group_category_before', [ data ] );
				
				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Update global
							delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'group_categories_data' ][ category_id ];
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = [];
							
							var groups_to_delete = [];
							$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ], function( i, group_data ) {
								if( group_data.category_id == category_id ) {
									groups_to_delete.push( group_data.id );
								}
							});
							$j.each( groups_to_delete, function( i, group_to_delete ) {
								delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ][ group_to_delete ];
								delete bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_to_delete ];
							});
							
							// Remove the group category and all its groups
							$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).remove();
							
							// Remove the category from the selectbox
							$j( '#bookacti-group-of-events-category-selectbox option[value="' + category_id + '"]' ).remove();
							
							// Refresh events
							$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
							$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
							
							// If it was the last category, display the tuto
							if( ! $j( '.bookacti-group-category' ).length ) {
								$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).show();
							}
							
							$j( '#bookacti-delete-group-category-dialog' ).trigger( 'bookacti_group_category_deactivated', [ response, data ] );
							
						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							alert( error_message );
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

				// Close the modal dialogs
				$j( this ).dialog( 'close' );
				$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
			}
		},
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'open' );
}