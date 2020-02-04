// INITIALIZATION

/**
 * Initialize calendar editor dialogs
 * @version 1.7.18
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

	// Init update activity dialog
	$j( '#bookacti-template-activity-list' ).on( 'click', '.activity-gear', function() {
		var activity_id = $j( this ).data( 'activity-id' );
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

	// Init update group of events dialog
	$j( '#bookacti-template-groups-of-events-container' ).on( 'click', '.bookacti-update-group-of-events', function() {
		var group_id	= $j( this ).parents( '.bookacti-group-of-events' ).data( 'group-id' );
		var is_selected	= $j( this ).parents( '.bookacti-group-of-events' ).hasClass( 'bookacti-selected-group' );
		var are_selected = is_selected;
		if( ! is_selected ) {
			are_selected = bookacti_select_events_of_group( group_id );
		}
		if( are_selected ) { bookacti_dialog_update_group_of_events( group_id ) };
	});

	// Init update group category dialog
	$j( '#bookacti-group-categories' ).on( 'click', '.bookacti-update-group-category', function() {
		var category_id = $j( this ).parents( '.bookacti-group-category' ).data( 'group-category-id' );
		bookacti_dialog_update_group_category( category_id ); 
	});

	// Prevent sending form
	$j( '.bookacti-template-dialog form' ).on( 'submit', function( e ){
		e.preventDefault();
	});

	// Display or hide new group category title field
	$j( '#bookacti-group-of-events-category-selectbox' ).on( 'change blur', function() {
		if( $j( this ).val() === 'new' ){
			$j( '#bookacti-group-of-events-new-category-title' ).show();
		} else {
			$j( '#bookacti-group-of-events-new-category-title' ).hide();
		}
	});
}


// TEMPLATES

/**
 * Dialog Create Template
 * @version 1.7.16
 */
function bookacti_dialog_add_new_template() {
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-template-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-template-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.create_new + ')'
	});

	// Set default values
	$j( '#bookacti-template-opening' ).val( moment().format( 'YYYY-MM-DD' ) );
	$j( '#bookacti-template-closing' ).val( moment().add( 7, 'days' ).format( 'YYYY-MM-DD' ) );
	$j( '#bookacti-template-data-minTime' ).val( '00:00' );
	$j( '#bookacti-template-data-maxTime' ).val( '00:00' );
	$j( '#bookacti-template-data-snapDuration' ).val( '00:05' );
	$j( '#bookacti-template-availability-period-start' ).val( 0 );
	$j( '#bookacti-template-availability-period-end' ).val( 0 );

	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_default_template_settings' );

	// Show and activate the duplicate fields
	if( $j( '#bookacti-template-duplicated-template-id option' ).length > 1 ) {
		$j( '#bookacti-duplicate-template-fields' ).show();
		$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', false );

		// Change the opening and closing date to those of the template to duplicate
		$j( '#bookacti-template-duplicated-template-id' ).off().on( 'change blur', function() {

			var duplicated_template_id = $j( '#bookacti-template-duplicated-template-id' ).val();
			if( duplicated_template_id ) {
				var opening = $j( '#bookacti-template-picker option[value="' + duplicated_template_id + '"]' ).data( 'template-start' );
				var closing = $j( '#bookacti-template-picker option[value="' + duplicated_template_id + '"]' ).data( 'template-end' );
				if( opening && closing ) {
					if( opening.length && closing.length ) {
						$j( '#bookacti-template-opening' ).val( opening );
						$j( '#bookacti-template-closing' ).val( closing );
					}
				}
			}
		});
	} else {
		$j( '#bookacti-duplicate-template-fields' ).hide();
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
				// Prepare fields
				$j( '#bookacti-template-data-form-action' ).val( 'bookactiInsertTemplate' );
				$j( '#bookacti-template-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				// Get the data to save
				var title	= $j( '#bookacti-template-title' ).val();
				var start	= moment( $j( '#bookacti-template-opening' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );
				var end		= moment( $j( '#bookacti-template-closing' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );

				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}

				var isFormValid = bookacti_validate_template_form();

				if( isFormValid ) {
					var data = $j( '#bookacti-template-data-form' ).serializeObject();

					if( $j( '#bookacti-template-calendar' ).length ) {
						bookacti_start_template_loading();
					} else if( $j( '#bookacti-first-template-container' ).length ) {
						$j( '#bookacti-add-first-template-button' ).removeClass( 'dashicons dashicons-plus-alt' ).addClass( 'spinner' );
					}

					$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_insert_template_before', [ data ] );

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
								if( $j( '#bookacti-first-template-container' ).length ) {
									$j( '#bookacti-first-template-container' ).before( $j( '<div id="bookacti-template-calendar" ></div>' ) );
									$j( '#bookacti-first-template-container' ).remove();
									$j( '.bookacti-no-template' ).removeClass( 'bookacti-no-template' );
									bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );
									bookacti_start_template_loading();
								}

								// Add the template to the template select box
								$j( '#bookacti-template-picker' ).append(
									"<option value='"		+ response.template_id
										+ "' data-template-start='" + start
										+ "' data-template-end='"   + end
									+ "' >"
											+ title
									+ "</option>"
								);

								// Add the template to other template select boxes
								$j( 'select.bookacti-template-select-box' ).append(
									"<option value='" + response.template_id + "' >"
										+ title
									+ "</option>"
								);

								// If the created template is the second one, you need to refresh dialog bounds
								// because clicking on new activity will now ask whether to create or import activity
								if( $j( '#bookacti-template-picker option' ).length === 2 ) {
									bookacti_bind_template_dialogs();
								}

								$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_inserted', [ response, data ] );

								// Switch template the new created one
								bookacti_switch_template( response.template_id );


							// If error
							} else if( response.status === 'failed' ) {
								var error_message = bookacti_localized.error_create_template;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_create_template );        
							console.log( e );
						},
						complete: function() { 

							if( $j( '#bookacti-first-template-container' ).length ) {
								$j( '#bookacti-add-first-template-button' ).removeClass( 'spinner' ).addClass( 'dashicons dashicons-plus-alt' );
							}

							bookacti_stop_template_loading();
						}
					});

					// Close the modal dialog
					$j( this ).dialog( 'close' );
				}
			}
		}]
	);
}


/**
 * Dialog Update Template
 * @version 1.7.17
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
	$j( '#bookacti-duplicate-template-fields' ).hide();
	$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', true );

	var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];

	// General tab
	$j( '#bookacti-template-title' ).val( template_data.title );
	$j( '#bookacti-template-opening' ).val( template_data.start );
	$j( '#bookacti-template-closing' ).val( template_data.end );

	// Permission tab
	if( template_data.admin.length ) {
		$j.each( template_data.admin, function( i, manager_id ) {
			$j( '#bookacti-add-new-template-managers-select-box option[value="' + manager_id + '"]' ).clone().appendTo( '#bookacti-template-managers-select-box' );
			$j( '#bookacti-add-new-template-managers-select-box option[value="' + manager_id + '"]' ).hide().attr( 'disabled', true );
			if( $j( '#bookacti-add-new-template-managers-select-box' ).val() == manager_id || ! $j( '#bookacti-add-new-template-managers-select-box' ).val() ) {
				$j( '#bookacti-add-new-template-managers-select-box' ).val( $j( '#bookacti-add-new-template-managers-select-box option:enabled:first' ).val() );
			}
		});
	}

	// Settings tabs
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_default_template_settings' );
	if( ! $j.isEmptyObject( template_data.settings ) ) {
		bookacti_fill_fields_from_array( template_data.settings, 'templateOptions' );
	}
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_data_dialog', [ template_id ] );

	// Add buttons
	$j( '#bookacti-template-data-dialog' ).dialog( 'option', 'buttons',
		// Add the 'OK' button
		[{
			text: bookacti_localized.dialog_button_ok, 

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {

				// Prepare fields
				$j( '#bookacti-template-data-form-template-id' ).val( template_id );
				$j( '#bookacti-template-data-form-action' ).val( 'bookactiUpdateTemplate' );
				$j( '#bookacti-template-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}

				var isFormValid = bookacti_validate_template_form();

				if( isFormValid ) {
					var data = $j( '#bookacti-template-data-form' ).serializeObject();

					$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_update_template_before', [ data ] );

					bookacti_start_template_loading();

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
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'start' ]			= response.template_data.start;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'end' ]			= response.template_data.end;
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'display_data' ]	= response.template_data.settings;

								// Change template metas in the select box
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).html( response.template_data.title );
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).data( 'template-start', response.template_data.start );
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).data( 'template-end', response.template_data.end );
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).attr( 'data-template-start', response.template_data.start );
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).attr( 'data-template-end', response.template_data.end );

								// Dynamically update template settings
								var events = $j( '#bookacti-template-calendar' ).fullCalendar( 'clientEvents' );
								$j( '#bookacti-template-calendar' ).replaceWith( '<div id="bookacti-template-calendar" class="bookacti-calendar"></div>' );
								bookacti_load_template_calendar( $j( '#bookacti-template-calendar' ) );
								$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', events );

								$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_template_updated', [ response, data ] );

							// If no changes
							} else if ( response.status === 'nochanges' ) {

							// If error
							} else {
								var error_message = bookacti_localized.error_update_template;
								if( response.errors ) {
									if( response.errors.length ) {
										$j.each( response.errors, function( i, error ) {
											error_message += '\n\u00B7 ' + bookacti_localized[ error ];

											if( response.error === 'not_allowed' ) {
												error_message += '\n' + bookacti_localized.error_not_allowed + '\n';
											}
										});
									}
									alert( error_message );
									console.log( response );
								}
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_update_template );        
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading(); 
						}
					});

					// Close the dialog
					$j( this ).dialog( 'close' );
				}
			}
		},

		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',

			// On click on the OK Button, new values are send to a script that update the database
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
 * @version 1.7.10
 * @param {int} template_id
 * @returns {false}
 */
function bookacti_dialog_deactivate_template( template_id ) {
	if( ! template_id ) { return false; }

	// Open the modal dialog
	$j( '#bookacti-delete-template-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-template-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				var data = { 
					'action': 'bookactiDeactivateTemplate', 
					'template_id': template_id,
					'nonce': bookacti_localized.nonce_deactivate_template
				};

				$j( '#bookacti-delete-template-dialog' ).trigger( 'bookacti_deactivate_template_before', [ data ] );

				bookacti_start_template_loading();

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

							// Switch template to the first one in the select box
							bookacti_switch_template( new_template_id );

						} else {
							var error_message = bookacti_localized.error_delete_template;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_template );
						console.log( e );
					}
					,
					complete: function() { 
						bookacti_stop_template_loading(); 
					}
				});

				// Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-template-data-dialog' ).dialog( 'close' );
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
}




// EVENTS

/**
 * Dialog Update Event
 * @version 1.7.18
 * @param {object} event
 */
function bookacti_dialog_update_event( event ) {
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-event-data-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-event-data-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + event.id + ')'
	});
	
	// Fill the form with known param
	$j( '#bookacti-event-data-dialog' ).data( 'event-id', event.id );
	$j( '#bookacti-event-data-dialog' ).attr( 'data-event-id', event.id );

	// Usefull var
	var event_data		= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ];
	var event_bookings	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'bookings' ][ event.id ];
	var event_exceptions= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event.id ];
	var bookings_number	= bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), event );

	var template_start  = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'start' ];
	var template_end    = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'end' ];

	var event_day		= event.start;
	var event_28_days	= moment( event_day ).add( 28, 'd' );
	var repeat_from     = event_day.format( 'YYYY-MM-DD' );
	var repeat_to       = event_28_days.isBefore( moment( template_end ) ) ? event_28_days.format( 'YYYY-MM-DD' ) : template_end;

	if( event_data.repeat_from && event_data.repeat_from !== '0000-00-00' )	{ repeat_from = event_data.repeat_from; };
	if( event_data.repeat_to   && event_data.repeat_to   !== '0000-00-00' )	{ repeat_to = event_data.repeat_to; };

	// Fill the form with database param
	$j( '#bookacti-event-title' ).val( event_data.multilingual_title );
	$j( '#bookacti-event-availability' ).val( event_data.availability );
	$j( '#bookacti-event-availability' ).attr( 'min', bookings_number );
	$j( '#bookacti-event-repeat-freq option[value="' + event_data.repeat_freq + '"]' ).prop( 'selected', true );
	$j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).attr( 'min', template_start );
	$j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).attr( 'max', template_end );
	$j( '#bookacti-event-repeat-from' ).val( repeat_from );
	$j( '#bookacti-event-repeat-to' ).val( repeat_to );
	$j( '#bookacti-event-exception-date-picker' ).val( repeat_from );
	$j( '#bookacti-event-exception-date-picker' ).attr( 'min', repeat_from );
	$j( '#bookacti-event-exception-date-picker' ).attr( 'max', repeat_to );

	if( typeof event_bookings !== 'undefined' ) {
		event_bookings = bookacti_sort_events_array_by_dates( event_bookings, false, false, { 'start': 'event_start', 'end': 'event_end' } );
		$j( '#bookacti-event-repeat-from' ).attr( 'max', event_bookings[ 0 ][ 'event_start' ].substr( 0, 10 ) );
		$j( '#bookacti-event-repeat-to' ).attr( 'min', event_bookings[ event_bookings.length - 1 ][ 'event_start' ].substr( 0, 10 ) );
	}

	// Fill the exceptions field
	if( typeof event_exceptions !== 'undefined' ) {
		$j.each( event_exceptions, function( i, value ) {
			$j( '#bookacti-event-exceptions-selectbox' ).append( 
				"<option class='exception' value='" + value.exception_value + "' >"
					+ value.exception_value +  
				"</option>" );
		});
	}

	// Fill additional settings
	if( typeof event_data.settings !== 'undefined' ) {
		bookacti_fill_fields_from_array( event_data.settings, 'eventOptions' );
	}

	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_event_update_dialog', [ event ] );

	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( '#bookacti-event-data-dialog .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}

	// Validate the title and availability fields
	bookacti_validate_event_general_data();

	// Enable or disable repetition and exception parts of the form
	bookacti_validate_event_repetition_data( event );


	// Prepare buttons
	var buttons = [];
	// OK button
	var ok_button = {
		text: bookacti_localized.dialog_button_ok,

		// On click on the OK Button, new values are send to a script that update the database
		click: function() {

			// Prepare fields
			$j( '#bookacti-event-data-form-event-id' ).val( event.id );
			$j( '#bookacti-event-data-form-event-start' ).val( event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
			$j( '#bookacti-event-data-form-event-end' ).val( event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
			$j( '#bookacti-event-data-form-action' ).val( 'bookactiUpdateEvent' );
			$j( '#bookacti-event-data-form select[multiple]#bookacti-event-exceptions-selectbox option' ).prop( 'selected', true );

			// Store new exceptions list
			var new_exceptions = $j( '#bookacti-event-exceptions-selectbox' ).val() ? $j( '#bookacti-event-exceptions-selectbox' ).val() : [];

			if( typeof tinyMCE !== 'undefined' ) { 
				if( tinyMCE ) { tinyMCE.triggerSave(); }
			}

			var isFormValid = bookacti_validate_event_form( event );

			if( isFormValid ) {
				var data = $j( '#bookacti-event-data-form' ).serializeObject();
				data.interval = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_interval' ];
				
				$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_update_event_before', [ event, data ] );
				
				bookacti_start_template_loading();

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

							// Unselect the event or occurences of the event
							bookacti_unselect_event( event, true );

							// Update event data
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event_id ] = response.events_data[ event_id ];

							// Update groups events
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_events' ] = response.groups_events;

							// Update the exceptions list
							bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ] = [];
							$j.each( new_exceptions, function( i, new_exception ) {
								bookacti.booking_system[ 'bookacti-template-calendar' ][ 'exceptions' ][ event_id ].push( { 'exception_type': 'date', 'exception_value': new_exception } );
							});

							// Delete old event
							bookacti_clear_events_on_calendar( $j( '#bookacti-template-calendar' ), event );

							// Add new event
							$j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event_id );
							$j( '#bookacti-template-calendar' ).fullCalendar( 'addEventSource', response.events );

							// addEventSource will rerender events, new exceptions will then be taken into account
							
							$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_event_updated', [ event, response, data ] );
							
						// If no changes
						} else if ( response.status === 'nochanges' ) {

						// If error
						} else if ( response.status === 'failed' )  {

							alert( bookacti_localized.error_update_event_param ); 
							var error_message = '';
							error_message += 'Status : '			+ response.status + '\n';
							error_message += 'Update event : '		+ response.updated_event + '\n';
							error_message += 'Update event meta : '	+ response.updated_event_meta + '\n';
							error_message += 'Insert excep : '		+ response.inserted_excep + '\n';
							error_message += 'Delete excep : '		+ response.deleted_excep;
							console.log( error_message );
							console.log( response );

						} else if ( response.status === 'not_valid' )  {

							if( response.errors.length ) {
								var error_message = '';
								$j.each( response.errors, function( i, error ) {
									error_message += '\u00B7 ' + bookacti_localized[ error ] + '\n';
								});
								error_message += '\n' + bookacti_localized.advice_switch_to_maintenance;

								alert( error_message );
								console.log( response );
							} else {
								alert( bookacti_localized.error_update_event_param );
								console.log( response );
							}

						} else if ( response.status === 'not_allowed' ) {

							alert( bookacti_localized.error_update_event_param + '\n' + bookacti_localized.error_not_allowed );
							console.log( bookacti_localized.error_update_event_param + ' ' + bookacti_localized.error_not_allowed );
							console.log( response );

						} else {
							console.log( response );
							alert( bookacti_localized.error_update_event_param );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_update_event_param );        
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading();
					}
				});

				//Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}
	};
	buttons.push( ok_button );

	// DELETE button
	var delete_button = {
		text: bookacti_localized.dialog_button_delete,
		'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',

		// On click on the OK Button, new values are send to a script that update the database
		click: function() {
			bookacti_dialog_delete_event( event );
		}
	};
	buttons.push( delete_button );

	// UNBIND button
	var unbind_button =	{};
	if( event_data.repeat_freq !== 'none' ) {
		unbind_button =	{
			text: bookacti_localized.dialog_button_unbind,
			'class': 'bookacti-dialog-unbind-button bookacti-dialog-left-button',

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				bookacti_dialog_unbind_occurences( event );
			}
		};
		buttons.push( unbind_button );
	}

	// Add dialog buttons
	$j( '#bookacti-event-data-dialog' ).dialog( 'option', 'buttons', buttons );

	// Open the modal dialog
	$j( '#bookacti-event-data-dialog' ).dialog( 'open' );
}


/**
 * Dialog Delete Event
 * @version 1.7.10
 * @param {object} event
 */
function bookacti_dialog_delete_event( event ) {
	// Open the modal dialog
	$j( '#bookacti-delete-event-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-event-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				var data = { 
					'action': 'bookactiDeleteEvent', 
					'event_id': event.id,
					'nonce': bookacti_localized.nonce_delete_event
				}

				$j( '#bookacti-delete-event-dialog' ).trigger( 'bookacti_deactivate_event_before', [ event, data ] );

				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {

							bookacti_delete_event( event );

							$j( '#bookacti-delete-event-dialog' ).trigger( 'bookacti_event_deactivated', [ event, response, data ] );

						} else {
							if( response.error === 'has_bookings' ) {
								// If the event's booking number is not up to date, refresh it
								if( ! bookacti_get_event_number_of_bookings( $j( '#bookacti-template-calendar' ), event ) ) {
									bookacti_refresh_booking_numbers( $j( '#bookacti-template-calendar' ), event.id );
								}

								// If the event is repeated, display unbind dialog
								var repeat_freq = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ];
								if( repeat_freq !== 'none' ) {
									bookacti_dialog_unbind_occurences( event, [ 'delete' ] );
								} 

								// If the event is single, diplay confirmation box to 
								else {
									bookacti_dialog_delete_booked_event( event );
								}

							} else {
								var error_message = bookacti_localized.error_delete_event;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_event );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading(); 
					}
				});

				// Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
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
}


/**
 * Dialog Delete a booked Event
 * @version 1.7.10
 * @param {object} event
 */
function bookacti_dialog_delete_booked_event( event ) {
	// Open the modal dialog
	$j( '#bookacti-delete-booked-event-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-booked-event-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				var data = { 
					'action': 'bookactiDeleteEventForced', 
					'event_id': event.id,
					'nonce': bookacti_localized.nonce_delete_event_forced
				};

				$j( '#bookacti-delete-booked-event-dialog' ).trigger( 'bookacti_deactivate_event_before', [ event, data ] );

				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {

							bookacti_delete_event( event );

							$j( '#bookacti-delete-booked-event-dialog' ).trigger( 'bookacti_event_deactivated', [ event, response, data ] );

						} else {
							var error_message = bookacti_localized.error_delete_event;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_event );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading(); 
					}
				});

				// Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
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
}


/**
 * Dialog Unbind occurence of a locked repeating event
 * @version 1.7.10
 * @param {object} event
 * @param {array} errors
 */
function bookacti_dialog_unbind_occurences( event, errors ) {
	errors = errors || [];

	// Open the modal dialog
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'open' );

	// Fill the errors so that the user understand what triggered the dialog
	if( errors.length > 0 ) {
		$j( '#bookacti-unbind-booked-event-error-list-container ul' ).empty();
		$j.each( errors, function( i, error ){ 
			if( error === 'move' )		{ $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_move_locked_event + '</li>' ); }
			if( error === 'resize' )	{ $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_resize_locked_event + '</li>' ); }
			if( error === 'delete' )	{ $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_delete_locked_event + '</li>' ); }
		});
		$j( '#bookacti-unbind-booked-event-error-list-container' ).show();
	} else {
		$j( '#bookacti-unbind-booked-event-error-list-container' ).hide();
	}

	// Add buttons
	var unbind_selected_button = {
		text: bookacti_localized.dialog_button_unbind_selected,
		'class': 'bookacti-dialog-delete-button',
		//On click on the OK Button, new values are send to a script that update the database
		click: function() {
			bookacti_unbind_occurrences( event, 'selected' );
			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
		}
	};
	var unbind_booked_button = {
		text: bookacti_localized.dialog_button_unbind_all_booked,
		'class': 'bookacti-dialog-delete-button',
		// On click on the OK Button, new values are send to a script that update the database
		click: function() {
			bookacti_unbind_occurrences( event, 'booked' );
			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
		}
	};
	var cancel_button = {
		text: bookacti_localized.dialog_button_cancel,
		click: function() {
			//Close the modal dialog
			$j( this ).dialog( 'close' );
		}
	};

	var buttons = [ unbind_selected_button, unbind_booked_button, cancel_button ];

	$j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'option', 'buttons', buttons );
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
 * @version 1.7.17
 */
function bookacti_dialog_import_activity() {
	if( ! bookacti.selected_template ) { return; }
	
	// Open the modal dialog
	$j( '#bookacti-activity-import-dialog' ).dialog( 'open' );

	// Deactivate current template in template selector
	$j( '#template-import-bound-activities option' ).attr( 'disabled', false );
	$j( '#template-import-bound-activities option[value="' + bookacti.selected_template + '"]' ).attr( 'disabled', true );

	// Select the first enabled template
	$j( '#template-import-bound-activities' ).children( 'option:enabled' ).eq( 0 ).prop( 'selected', true );
	$j( '#template-import-bound-activities' ).trigger( 'change' );

	//Add the 'OK' button
	$j( '#bookacti-activity-import-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {

				$j( '#bookacti-activity-import-dialog .bookacti-input-error' ).removeClass( 'bookacti-input-error' );
				$j( '#bookacti-activity-import-dialog .bookacti-form-error' ).remove();

				var activity_ids = $j( 'select#activities-to-import' ).val();

				if( ! $j.isEmptyObject( activity_ids ) ) {
					var data = { 
						'action': 'bookactiImportActivities', 
						'activity_ids': activity_ids,
						'template_id': bookacti.selected_template,
						'nonce': bookacti_localized.nonce_import_activity
					};
					
					$j( '#bookacti-activity-import-dialog' ).trigger( 'bookacti_import_activities_before', [ data ] );
					
					bookacti_start_template_loading();

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
									$j( 'select#activities-to-import option[value="' + activity_id + '"]' ).remove();
								});
								
								// Reinitialize the activities to apply changes
								bookacti_init_activities();
								
								$j( '#bookacti-activity-import-dialog' ).trigger( 'bookacti_activities_imported', [ response, data ] );
								
								// Close the modal dialogs
								$j( '#bookacti-activity-import-dialog' ).dialog( 'close' );
								$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );

							} else if ( response.status === 'no_activity' ) {
								$j( '#activities-to-import' ).addClass( 'bookacti-input-error' );
								$j( '#bookacti-activities-bound-to-template' ).append( '<div class="bookacti-form-error" >' + bookacti_localized.error_no_activity_selected + '</div>' );
							} else {
								var error_message = bookacti_localized.error_import_activity;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_import_activity );
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading(); 
						}
					});

				} else {
					$j( '#activities-to-import' ).addClass( 'bookacti-input-error' );
					$j( '#bookacti-activities-bound-to-template' ).append( '<div class="bookacti-form-error" >' + bookacti_localized.error_no_activity_selected + '</div>' );
				}
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


/**
 * Dialog Create Activity
 * @version 1.7.17
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
	$j( '#bookacti-activity-action' ).val( 'bookactiInsertActivity' );
	$j( '#bookacti-activity-old-title' ).val( '' );
	
	// Add the 'OK' button
	$j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				
				// Prepare fields
				$j( '#bookacti-activity-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				// Get the data to save
				var days		= $j( '#bookacti-activity-duration-days' ).val();
				var hours		= $j( '#bookacti-activity-duration-hours' ).val();
				var minutes		= $j( '#bookacti-activity-duration-minutes' ).val();
				var duration	= bookacti_pad( days, 3 ) + '.' + bookacti_pad( hours, 2 ) + ':' + bookacti_pad( minutes, 2 ) + ':00';
				var resizable	= $j( '#bookacti-activity-resizable' ).prop('checked');
				if( resizable ) { resizable = '1'; } else { resizable = '0'; }
				resizable = resizable.toString();

				$j( '#bookacti-activity-duration' ).val( duration );
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_activity_form();

				if( is_form_valid ) {
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
								var error_message = bookacti_localized.error_create_activity;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								} else if( response.error === 'no_templates' ) {
									error_message += '\n' + bookacti_localized.error_no_templates_for_activity;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_create_activity );        
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
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-activity-data-dialog' ).dialog( 'open' );
}


/**
 * Open a dialog to update an activity
 * @version 1.7.17
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
	$j( '#bookacti-activity-old-title' ).val( activity_data.multilingual_title );
	$j( '#bookacti-activity-data-dialog .bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );

	// General tab
	$j( '#bookacti-activity-title' ).val( activity_data.multilingual_title ); 
	$j( '#bookacti-activity-color' ).val( activity_data.color );
	$j( '#bookacti-activity-availability' ).val( activity_data.availability );
	
	var activity_duration = activity_data.duration ? activity_data.duration : '000.01:00:00';
	$j( '#bookacti-activity-duration-days' ).val( activity_duration.substr( 0, 3 ) );
	$j( '#bookacti-activity-duration-hours' ).val( activity_duration.substr( 4, 2 ) );
	$j( '#bookacti-activity-duration-minutes' ).val( activity_duration.substr( 7, 2 ) );
	
	if( activity_data.is_resizable == 1 ) { $j( '#bookacti-activity-resizable' ).prop( 'checked', true ); }
	else { $j( '#bookacti-activity-resizable' ).prop( 'checked', false ); }

	// Permission tab
	if( activity_data.admin ) {
		$j.each( activity_data.admin, function( i, manager_id ) {
			$j( '#bookacti-add-new-activity-managers-select-box option[value="' + manager_id + '"]' ).clone().appendTo( '#bookacti-activity-managers-select-box' );
			$j( '#bookacti-add-new-activity-managers-select-box option[value="' + manager_id + '"]' ).hide().attr( 'disabled', true );
			if( $j( '#bookacti-add-new-activity-managers-select-box' ).val() == manager_id || ! $j( '#bookacti-add-new-activity-managers-select-box' ).val() ) {
				$j( '#bookacti-add-new-activity-managers-select-box' ).val( $j( '#bookacti-add-new-activity-managers-select-box option:enabled:first' ).val() );
			}
		});
	}

	// Settings tabs
	if( activity_data.settings ) {
		bookacti_fill_fields_from_array( activity_data.settings, 'activityOptions' );
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
		[
		//Add the 'OK' button	
		{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				
				// Prepare fields
				$j( '#bookacti-activity-data-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );

				// Get the data to save
				var days            = $j( '#bookacti-activity-duration-days' ).val();
				var hours           = $j( '#bookacti-activity-duration-hours' ).val();
				var minutes         = $j( '#bookacti-activity-duration-minutes' ).val();
				var duration        = bookacti_pad( days, 3 ) + '.' + bookacti_pad( hours, 2 ) + ':' + bookacti_pad( minutes, 2 ) + ':00';
				var resizable       = $j( '#bookacti-activity-resizable' ).prop( 'checked' );
				if( resizable )		{ resizable = '1'; } else { resizable = '0'; }
				resizable = resizable.toString();
				
				$j( '#bookacti-activity-duration' ).val( duration );
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_activity_form();

				if( is_form_valid ) {
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
							} else if ( response.status === 'failed_update_activity' ) {
								alert( bookacti_localized.error_update_activity );
								console.log( response );

							} else if ( response.status === 'failed_update_bound_events' ) {
								alert( bookacti_localized.error_update_bound_events );
								console.log( response );

							} else if ( response.status === 'no_changes' ) {

							} else if ( response.status === 'failed' ) {
								var error_message = bookacti_localized.error_update_activity;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_update_activity );        
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading(); 
						}
					});

					//Close the modal dialog
					$j( this ).dialog( 'close' );
				}
			}
		},


		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',

			//On click on the OK Button, new values are send to a script that update the database
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
 * @version 1.7.17
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
				var delete_events = $j( '#bookacti-delete-activity-events' ).is( ':checked' ) ? 1 : 0;
				var data = { 
					'action': 'bookactiDeactivateActivity', 
					'activity_id': activity_id,
					'template_id': bookacti.selected_template,
					'delete_events': delete_events,
					'nonce': bookacti_localized.nonce_deactivate_activity
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

							$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).parents( '.activity-row' ).remove();

							// refresh events if user chose to deleted them
							if( delete_events ) {
								bookacti_refetch_events_on_template();
							}

							// Display tuto if there is no more activities available
							bookacti_display_activity_tuto_if_no_activity_available();
							
							$j( '#bookacti-delete-activity-dialog' ).trigger( 'bookacti_activity_deactivated', [ response, data ] );
							
						} else {
							var error_message = bookacti_localized.error_delete_activity;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_activity );
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
 * @version 1.7.15
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
		
		var event_start = moment( event.start );
		var event_end = moment( event.end );
		
		var event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + event_end.formatPHP( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + event_end.formatPHP( bookacti_localized.time_format );
		}
		var option = $j( '<option />', {
						'html': event_duration + ' - ' + event.title
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});

	// Open the modal dialog
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			// On click on the OK Button, new values are send to a script that update the database
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
								
							// If error
							} else {
								var error_message = bookacti_localized.error_create_group_of_events;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_create_group_of_events );        
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading();
						}
					});

					// Close the modal dialogs
					$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
				}
			}
		}]
	);
}


/**
 * Update a group of events with selected events 
 * @version 1.7.15
 * @param {int} group_id
 */
function bookacti_dialog_update_group_of_events( group_id ) {
	// Change dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + group_id + ')'
	});
	
	// Select the group category
	var category_id = $j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).parents( '.bookacti-group-category' ).data( 'group-category-id' );
	var initial_category_id = category_id ? category_id : 'new';
	$j( '#bookacti-group-of-events-category-selectbox' ).val( category_id ).trigger( 'change' );

	// Fill the events list as a feedback for user
	$j( '#bookacti-group-of-events-summary' ).empty();
	bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] = bookacti_sort_events_array_by_dates( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
	$j.each( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ], function( i, event ){
		var event_start = moment( event.start );
		var event_end = moment( event.end );
		
		var event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + event_end.formatPHP( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + event_end.formatPHP( bookacti_localized.time_format );
		}
		var option = $j( '<option />', {
						'html': event_duration + ' - ' + event.title
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});
	
	
	var group_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'groups_data' ][ group_id ];
				
	// General tab
	$j( '#bookacti-group-of-events-title-field' ).val( group_data.multilingual_title ); 

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
	
	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				// Prepare fields
				$j( '#bookacti-group-of-events-action' ).val( 'bookactiUpdateGroupOfEvents' );
				$j( '#bookacti-group-of-events-form select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				//Get the data to save
				var selected_category_id	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
				bookacti.selected_category	= selected_category_id;
				
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var is_form_valid = bookacti_validate_group_of_events_form();
				
				if( is_form_valid ) {
					var data = $j( '#bookacti-group-of-events-form' ).serializeObject();
					data.group_id = group_id;
					data.events = JSON.stringify( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ] );
					
					$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_update_group_of_events_before', [ data ] );
					
					bookacti_start_template_loading();
					
					//Save the new group of events in database
					$j.ajax({
						url: ajaxurl, 
						data: data,
						type: 'POST',
						dataType: 'json',
						success: function( response ){
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
								
							// If error
							} else {
								if( response.status === 'failed' ) {
									var error_message = bookacti_localized.error_update_group_of_events;
									if( response.error === 'not_allowed' ) {
										error_message += '\n' + bookacti_localized.error_not_allowed;
									}
									alert( error_message );
								}
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_update_group_of_events );        
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading();
						}
					});

					// Close the modal dialogs
					$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
				}
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			
			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				bookacti_dialog_delete_group_of_events( group_id );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );
}


/**
 * Dialog Delete a group of events
 * @version 1.7.10
 * @param {int} group_id
 */
function bookacti_dialog_delete_group_of_events( group_id ) {
	// Open the modal dialog
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				var data = { 
					'action': 'bookactiDeleteGroupOfEvents', 
					'group_id': group_id,
					'nonce': bookacti_localized.nonce_delete_group_of_events
				};
				
				$j( '#bookacti-delete-group-of-events-dialog' ).trigger( 'bookacti_deactivate_group_of_events_before', [ data ] );
				
				bookacti_start_template_loading();

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
							$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
							$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
							
							$j( '#bookacti-delete-group-of-events-dialog' ).trigger( 'bookacti_group_of_events_deactivated', [ response, data ] );
							
						} else {
							var error_message = bookacti_localized.error_delete_group_of_events;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_group_of_events );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading(); 
					}
				});

				// Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
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
}



// GROUP CATEGORIES

/**
 * Update a group category
 * @version 1.7.15
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

			// On click on the OK Button, new values are send to a script that update the database
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
								var error_message = bookacti_localized.error_update_group_category;
								if( response.error === 'not_allowed' ) {
									error_message += '\n' + bookacti_localized.error_not_allowed;
								}
								alert( error_message );
								console.log( response );
							}
						},
						error: function( e ){
							alert( 'AJAX ' + bookacti_localized.error_update_group_category );        
							console.log( e );
						},
						complete: function() { 
							bookacti_stop_template_loading();
						}
					});

					// Close the modal dialogs
					$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
				}
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			
			// On click on the OK Button, new values are send to a script that update the database
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
 * @version 1.7.10
 * @param {int} category_id
 */
function bookacti_dialog_delete_group_category( category_id ) {
	// Open the modal dialog
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',

			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				var data = { 
					'action': 'bookactiDeleteGroupCategory', 
					'category_id': category_id,
					'nonce': bookacti_localized.nonce_delete_group_category
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
							var error_message = bookacti_localized.error_delete_group_category;
							if( response.error === 'not_allowed' ) {
								error_message += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						alert( 'AJAX ' + bookacti_localized.error_delete_group_category );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_template_loading(); 
					}
				});

				// Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
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
}