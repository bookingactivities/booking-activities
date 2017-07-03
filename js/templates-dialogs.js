// INITIALIZATION

function bookacti_init_template_dialogs() {
    //Common param
    $j( '.bookacti-template-dialogs' ).dialog({ 
		modal:      true,
		autoOpen:   false,
		minHeight:  300,
		minWidth:   520,
		resize:		'auto',
		show:       true,
		hide:       true,
		closeText:  '&#10006;',
		beforeClose: function() { bookacti_empty_all_dialog_forms(); }
    });
	
	
    //Individual param
	$j( '#bookacti-activity-create-method-dialog' ).dialog({ 
		title: bookacti_localized.dialog_choice_activity_title
	});
	$j( '#bookacti-activity-import-dialog' ).dialog({ 
		title: bookacti_localized.dialog_import_activity_title
	});
	$j( '#bookacti-delete-event-dialog' ).dialog({ 
		title: bookacti_localized.dialog_delete_event_title
	});
	$j( '#bookacti-event-data-dialog' ).dialog({ 
		title: bookacti_localized.dialog_update_event_title
	});
	$j( '#bookacti-delete-template-dialog' ).dialog({ 
		title: bookacti_localized.dialog_delete_template_title
	});
	$j( '#bookacti-delete-activity-dialog' ).dialog({ 
		title: bookacti_localized.dialog_delete_activity_title
	});
	$j( '#bookacti-unbind-booked-event-dialog' ).dialog({ 
		title: bookacti_localized.dialog_locked_event,
		beforeClose: function(){}
	});
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_create_group_of_events_title
	});
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_delete_group_of_events_title
	});
	$j( '#bookacti-group-category-dialog' ).dialog({ 
		title: bookacti_localized.dialog_update_group_category_title
	});
	$j( '#bookacti-delete-group-category-dialog' ).dialog({ 
		title: bookacti_localized.dialog_delete_group_category_title
	});
	
	// Add and remove items in managers and templates select boxes
	bookacti_init_add_and_remove_items();
	
	// Load activities bound to selected template
	$j( 'select#template-import-bound-activities' ).on( 'change', function(){
		bookacti_load_activities_bound_to_template( $j( 'select#template-import-bound-activities' ).val() );
	});
	
	// Init new template dialog
	$j( '#bookacti-template-container' ).on( 'click', '#bookacti-insert-template img, #bookacti-add-first-template-button img', function() { 
        bookacti_dialog_add_new_template(); 
    });
	
	// Init update activity dialog
	$j( '#bookacti-template-activity-list' ).on( 'click', '.activity-gear img', function() {
        var activity_id = $j( this ).data( 'activity-id' );
        bookacti_dialog_update_activity( activity_id ); 
    });
	
	// Init create group of events dialog
	$j( '#bookacti-template-groups-of-events-container' ).on( 'click', '#bookacti-template-add-first-group-of-events-button img, #bookacti-insert-group-of-events img', function() {
		bookacti_dialog_create_group_of_events();
    });
	
	// Init update group of events dialog
	$j( '#bookacti-template-groups-of-events-container' ).on( 'click', '.bookacti-group-of-events-title, .bookacti-update-group-of-events img', function() {
		var group_id = $j( this ).parents( '.bookacti-group-of-events' ).data( 'group-id' );
		bookacti_update_group_of_events( group_id );
    });
	
	// Init update group category dialog
	$j( '#bookacti-group-categories' ).on( 'click', '.bookacti-update-group-category img', function() {
        var category_id = $j( this ).parents( '.bookacti-group-category' ).data( 'group-category-id' );
        bookacti_dialog_update_group_category( category_id ); 
    });
	
	// Prevent sending form
	$j( '.bookacti-backend-dialog form' ).on( 'submit', function( e ){
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

//Dialog Create Template
function bookacti_dialog_add_new_template() {
    //Set the dialog title
    $j( '#bookacti-template-data-dialog' ).dialog({ 
        title: bookacti_localized.dialog_create_template_title
    });
	
	//Set default values
    $j( '#bookacti-template-opening' ).val( moment().format( 'YYYY-MM-DD' ) );
    $j( '#bookacti-template-closing' ).val( moment().add( 7, 'days' ).format( 'YYYY-MM-DD' ) );
	$j( '#bookacti-template-data-minTime' ).val( '08:00' );
	$j( '#bookacti-template-data-maxTime' ).val( '20:00' );
	
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
    
	//Open the modal dialog
    $j( '#bookacti-template-data-dialog' ).dialog( 'open' );
	
    //Add the 'OK' button
    $j( '#bookacti-template-data-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				// Prepare fields
				$j( '#bookacti-template-data-form-action' ).val( 'bookactiInsertTemplate' );
				$j( '#bookacti-template-data-form select[multiple] option' ).attr( 'selected', true );
				
                //Get the data to save
                var title	= $j( '#bookacti-template-title' ).val();
                var start	= moment( $j( '#bookacti-template-opening' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );
                var end		= moment( $j( '#bookacti-template-closing' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );
                var duplicated_template_id	= $j( '#bookacti-template-duplicated-template-id' ).val();
				
				var data = $j( '#bookacti-template-data-form' ).serialize();
				
                var isFormValid = bookacti_validate_template_form();
                
                if( isFormValid ) {
					if( $j( '#bookacti-template-calendar' ).length ) {
						bookacti_start_template_loading();
					} else if( $j( '#bookacti-first-template-container' ).length ) {
						$j( '#bookacti-add-first-template-button img' ).attr( 'src', bookacti_localized.plugin_path + '/img/ajax-loader.gif' );
					}
					
					//Save the new template in database
                    $j.ajax({
                        url: ajaxurl, 
                        data: data, 
                        type: 'POST',
                        dataType: 'json',
                        success: function( response ){
                            //If success
                            if( response.status === 'success' ) {
								
								//If it is the first template, change the bookacti-first-template-container div to bookacti-template-calendar div
								if( $j( '#bookacti-first-template-container' ).length ) {
									$j( '#bookacti-first-template-container' ).before( $j( '<div id="bookacti-template-calendar" ></div>' ) );
									$j( '#bookacti-first-template-container' ).remove();
									$j( '.bookacti-no-template' ).removeClass( 'bookacti-no-template' );
									bookacti_load_template_calendar();
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
								
                                // Switch template the new created one
								$j( '#bookacti-template-picker' ).val( response.template_id ).trigger( 'change' );

                            //If error
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
								$j( '#bookacti-add-first-template-button img' ).attr( 'src', bookacti_localized.plugin_path + '/img/add.png' );
							}
							
                            bookacti_stop_template_loading();
                        }
                    });
                    
                    //Close the modal dialog
                    $j( this ).dialog( 'close' );
                }
            }
        }]
    );
}


//Dialog Update Template
function bookacti_dialog_update_template( template_id ) {
	if( template_id ) {
		//Set the dialog title
		$j( '#bookacti-template-data-dialog' ).dialog({ 
			title: bookacti_localized.dialog_update_template_title
		});

		// Hide and deactivate duplicate fields
		$j( '#bookacti-duplicate-template-fields' ).hide();
		$j( '#bookacti-template-duplicated-template-id' ).attr( 'disabled', true );

		bookacti_start_template_loading();

		// Retrieve template info and fill fields
		$j.ajax({
			url: ajaxurl, 
			data: { 'action': 'bookactiGetTemplateData', 
					'template_id': template_id,
					'nonce': bookacti_localized.nonce_get_template_data
				},
			type: 'POST',
			dataType: 'json',
			success: function( response ){
				// If success
				if( response.status === 'success' ) {
					
					// General tab
					var title	= response.title		? response.title		: $j( '#bookacti-template-picker option[value="' + template_id + '"]' ).html();
					var start	= response.start_date	? response.start_date	: $j( '#bookacti-template-picker option[value="' + template_id + '"]' ).data( 'template-start' );
					var end		= response.end_date		? response.end_date		: $j( '#bookacti-template-picker option[value="' + template_id + '"]' ).data( 'template-end' );
					$j( '#bookacti-template-title' ).val( title );
					$j( '#bookacti-template-opening' ).val( start );
					$j( '#bookacti-template-closing' ).val( end );

					// Permission tab
					if( response.admin ) {
						$j.each( response.admin, function( i, manager_id ) {
							$j( '#bookacti-add-new-template-managers-select-box option[value="' + manager_id + '"]' ).clone().appendTo( '#bookacti-template-managers-select-box' );
							$j( '#bookacti-add-new-template-managers-select-box option[value="' + manager_id + '"]' ).hide().attr( 'disabled', true );
							if( $j( '#bookacti-add-new-template-managers-select-box' ).val() == manager_id || ! $j( '#bookacti-add-new-template-managers-select-box' ).val() ) {
								$j( '#bookacti-add-new-template-managers-select-box' ).val( $j( '#bookacti-add-new-template-managers-select-box option:enabled:first' ).val() );
							}
						});
					}

					// Settings tabs
					if( response.settings ) {
						bookacti_fill_settings_fields( response.settings, 'templateOptions' );
					}

				// If error
				} else {
					var error_message = bookacti_localized.error_retrieve_template_data;
					if( response.error === 'not_allowed' ) {
						error_message += '\n' + bookacti_localized.error_not_allowed;
					}
					alert( error_message );
					console.log( response );
				}
			},
			error: function(  e){
				alert( 'AJAX ' + bookacti_localized.error_retrieve_template_data );        
				console.log( e );
			},
			complete: function() { 
				bookacti_stop_template_loading(); 

				//Open the modal dialog
				$j( '#bookacti-template-data-dialog' ).dialog( 'open' );
			}
		});

		//Add buttons
		$j( '#bookacti-template-data-dialog' ).dialog( 'option', 'buttons',
			//Add the 'OK' button
			[{
				text: bookacti_localized.dialog_button_ok, 

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {

					// Prepare fields"
					$j( '#bookacti-template-data-form-template-id' ).val( template_id );
					$j( '#bookacti-template-data-form-action' ).val( 'bookactiUpdateTemplate' );
					$j( '#bookacti-template-data-form select[multiple] option' ).attr( 'selected', true );

					//Gether the data to save
					var title   = $j( '#bookacti-template-title' ).val();
					var start   = moment( $j( '#bookacti-template-opening' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );
					var end     = moment( $j( '#bookacti-template-closing' ).val(), [ 'MM-DD-YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD' ] ).format( 'YYYY-MM-DD' );
					var data	= $j( '#bookacti-template-data-form' ).serialize();
					var settings= $j( '#bookacti-template-data-form' ).serializeObject();

					if( settings[ 'templateOptions' ] ) {
						settings= settings[ 'templateOptions' ];
					} else {
						settings[ 'templateOptions' ] = {};
					}

					var isFormValid = bookacti_validate_template_form();

					if( isFormValid ) {
						bookacti_start_template_loading();

						//Save changes in database
						$j.ajax({
							url: ajaxurl, 
							data: data,
							type: 'POST',
							dataType: 'json',
							success: function( response ){

								//If success
								if( response.status === 'success' ) {
									//Change template metas in the select box
									$j( '#bookacti-template-picker option[value=' + template_id + ']' ).html( title );
									//This change the jquery stored data...
									$j( '#bookacti-template-picker option[value=' + template_id + ']' ).data( 'template-start', start );
									$j( '#bookacti-template-picker option[value=' + template_id + ']' ).data( 'template-end', end );
									//...And this change visually the html code... stupid... what about a 2 in 1 function ?!
									$j( '#bookacti-template-picker option[value=' + template_id + ']' ).attr( 'data-template-start', start );
									$j( '#bookacti-template-picker option[value=' + template_id + ']' ).attr( 'data-template-end', end );

									//Dynamically update template settings
									settings.start = start;
									settings.end = end;
									bookacti_update_calendar_settings( $j( '#bookacti-template-calendar' ), settings );

									//Change the view to match start and end date of the template
									var start_template = moment( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) );
									var end_template = moment( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) );
									bookacti_refresh_view( $j( '#bookacti-template-calendar' ), start_template, end_template );

								//If no changes
								} else if ( response.status === 'nochanges' ) {

								//If error
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

						//Close the dialog
						$j( this ).dialog( 'close' );
					}
				}
			},

			// Add the 'delete' button
			{
				text: bookacti_localized.dialog_button_delete,
				class: 'bookacti-dialog-delete-button bookacti-dialog-left-button',

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {
					bookacti_dialog_deactivate_template( template_id );
				}
			}]
		);
	}
}


//Dialog Deactivate Template
function bookacti_dialog_deactivate_template( template_id ) {
    if( template_id ) {
		//Open the modal dialog
		$j( '#bookacti-delete-template-dialog' ).dialog( 'open' );

		//Add the 'OK' button
		$j( '#bookacti-delete-template-dialog' ).dialog( 'option', 'buttons',
			[{
				text: bookacti_localized.dialog_button_delete,
				class: 'bookacti-dialog-delete-button',

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {
					bookacti_start_template_loading();

					$j.ajax({
						url: ajaxurl, 
						data: { 'action': 'bookactiDeactivateTemplate', 
								'template_id': template_id,
								'nonce': bookacti_localized.nonce_deactivate_template
							},
						type: 'POST',
						dataType: 'json',
						success: function( response ) {
							if( response.status === 'success' ) {
								//Remove the template from the select box
								$j( '#bookacti-template-picker option[value=' + template_id + ']' ).remove();
								var new_template_id = $j( '#bookacti-template-picker option:first' ).val();

								// Remove the template from other template select boxes
								$j( 'select.bookacti-template-select-box option[value=' + template_id + ']' ).remove();
								
								// If there is only 1 template left, you need to refresh dialog bounds
								// because clicking on new activity has to stop offer to import activity
								if( $j( '#bookacti-template-picker option' ).length === 1 ) {
									bookacti_bind_template_dialogs();
								}
								
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

					//Close the modal dialog
					$j( this ).dialog( 'close' );
					$j( '#bookacti-template-data-dialog' ).dialog( 'close' );
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
}




// EVENTS

//Dialog Update Event
function bookacti_dialog_update_event( event ) {
    //Fill the form with known param
    $j( '#bookacti-event-data-dialog' ).data( 'event-id', event.id );
    $j( '#bookacti-event-data-dialog' ).attr( 'data-event-id', event.id );
    	
    bookacti_start_template_loading();
	
    //Retrieve event data to fill the dialog form
    $j.ajax({
        url: ajaxurl, 
        data: { 'action': 'bookactiGetEventData', 
                'event_id': event.id,
				'nonce': bookacti_localized.nonce_get_event_data
			},
        type: 'POST',
        dataType: 'json',
        success: function( response ){
            if( response.status === 'success' ) {
                
				// Usefull var
				var event_day		= event.start;
				var event_28_days	= moment( event_day ).add( 28, 'd' );
				
				//Set default value
				var template_start  = $j( '#bookacti-template-picker :selected' ).data( 'template-start' );
                var template_end    = $j( '#bookacti-template-picker :selected' ).data( 'template-end' );
                var repeat_from     = event_day.format( 'YYYY-MM-DD' );
                var repeat_to       = event_28_days.isBefore( moment( template_end ) ) ? event_28_days.format( 'YYYY-MM-DD' ) : template_end;
                if( response.repeat_from && response.repeat_from !== '0000-00-00' )	{ repeat_from = response.repeat_from; };
                if( response.repeat_to   && response.repeat_to   !== '0000-00-00' )	{ repeat_to = response.repeat_to; };
                
                //Fill the form with database param
				$j( '#bookacti-event-title' ).val( response.title );
                $j( '#bookacti-event-availability' ).val( response.availability );
                $j( '#bookacti-event-availability' ).attr( 'min', response.min_availability );
                $j( "#bookacti-event-repeat-freq option[value='" + response.repeat_freq + "']" ).prop( 'selected', true );
                $j( '#bookacti-event-repeat-freq' ).data( 'initial-freq', response.repeat_freq );
                $j( '#bookacti-event-repeat-freq' ).attr( 'data-initial-freq', response.repeat_freq );
				$j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).attr( 'min', template_start );
                $j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).attr( 'max', template_end );
                $j( '#bookacti-event-repeat-from' ).val( repeat_from );
                $j( '#bookacti-event-repeat-to' ).val( repeat_to );
                $j( '#bookacti-event-exception-date-picker' ).val( repeat_from );
                $j( '#bookacti-event-exception-date-picker' ).attr( 'min', repeat_from );
                $j( '#bookacti-event-exception-date-picker' ).attr( 'max', repeat_to );
                
                if( response.is_bookings ) {
                    $j( '#bookacti-event-repeat-from' ).attr( 'max', response.max_from );
                    $j( '#bookacti-event-repeat-to' ).attr( 'min', response.min_to );
                }
				
                //Fill the exceptions field
                if( response.exceptions.length > 0 )
                {
                    $j.each( response.exceptions, function( i, value )
                    {
                        $j( '#bookacti-event-exceptions-selectbox' ).append( 
                            "<option class='exception' value='" + value.exception_value + "' >"
                                + value.exception_value +  
                            "</option>" );
                    });
                }
                
				//Refresh qtranslate fields to make a correct display of multilingual fields
				if( bookacti_localized.is_qtranslate ) {
					$j( '.qtranxs-translatable' ).each( function() { 
						bookacti_refresh_qtx_field( this ); 
					});
				}
				
				// Fill additional settings
				if( response.settings ) {
					bookacti_fill_settings_fields( response.settings, 'eventOptions' );
				}
				
				//Validate the title and availability fields
				bookacti_validate_event_general_data();
				
				//Enable or disable repetition and exception parts of the form
				bookacti_validate_event_repetition_data( event.start.format( 'YYYY-MM-DD HH:mm:ss' ), event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
				
				
				// Prepare buttons
				var buttons = [];
				// OK button
				var ok_button = {
					text: bookacti_localized.dialog_button_ok,

					//On click on the OK Button, new values are send to a script that update the database
					click: function() {
						
						// Prepare fields
						$j( '#bookacti-event-data-form-event-id' ).val( event.id );
						$j( '#bookacti-event-data-form-event-start' ).val( event.start.format( 'YYYY-MM-DD HH:mm:ss' ) );
						$j( '#bookacti-event-data-form-event-end' ).val( event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
						$j( '#bookacti-event-data-form-action' ).val( 'bookactiUpdateEventData' );
						$j( '#bookacti-event-data-form select[multiple] option' ).attr( 'selected', true );
						
						var data = $j( '#bookacti-event-data-form' ).serialize();
						
						var isFormValid = bookacti_validate_event_form( event.start.format( 'YYYY-MM-DD HH:mm:ss' ), event.end.format( 'YYYY-MM-DD HH:mm:ss' ) );
						if( isFormValid ) { 
							
							bookacti_start_template_loading();
							
							//Write new param in database
							$j.ajax({
								url: ajaxurl, 
								data: data,
								type: 'POST',
								dataType: 'json',
								success: function( response ){
									
									//If success
									if( response.status === 'success' ) {
										//Update the exceptions list and refetch event
										bookacti_update_exceptions( null, event );

									//If no changes
									} else if ( response.status === 'nochanges' ) {

									//If error
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
												if( error === 'error_set_excep_on_booked_occur' ) {
													$j.each( response.booked_exceptions, function( i, exception_date ) {
														error_message += '     \u00B7 ' + exception_date + '\n';
													});
												}
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
					class: 'bookacti-dialog-delete-button bookacti-dialog-left-button',

					//On click on the OK Button, new values are send to a script that update the database
					click: function() {
						bookacti_dialog_delete_event( event );
					}
				};
				buttons.push( delete_button );
				
				// UNBIND button
				var unbind_button =	{};
				if( response.repeat_freq !== 'none' ) {
					unbind_button =	{
						text: bookacti_localized.dialog_button_unbind,
						class: 'bookacti-dialog-unbind-button bookacti-dialog-left-button',

						//On click on the OK Button, new values are send to a script that update the database
						click: function() {
							bookacti_dialog_unbind_occurences( event );
						}
					};
					buttons.push( unbind_button );
				}
				
				// Add dialog buttons
				$j( '#bookacti-event-data-dialog' ).dialog( 'option', 'buttons', buttons );
				
            } else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_retrieve_event_data;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
            }
        },
        error: function( e ){
            alert( 'AJAX ' + bookacti_localized.error_retrieve_event_data );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_template_loading();
			
			//Open the modal dialog
			$j( '#bookacti-event-data-dialog' ).dialog( 'open' );
		}
    });
}


// Dialog Delete Event
function bookacti_dialog_delete_event( event ) {
    //Open the modal dialog
    $j( '#bookacti-delete-event-dialog' ).dialog( 'open' );
    
    //Add the 'OK' button
    $j( '#bookacti-delete-event-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_delete,
			class: 'bookacti-dialog-delete-button',
			
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
                
				bookacti_start_template_loading();

                $j.ajax({
                    url: ajaxurl, 
                    data: { 'action': 'bookactiDeleteEvent', 
                            'event_id': event.id,
							'nonce': bookacti_localized.nonce_delete_event
						},
                    type: 'POST',
                    dataType: 'json',
                    success: function( response ) {
                        if( response.status === 'success' ) {
                            //We use event._id because it works with both existing and newly added event
                            $j( '#bookacti-template-calendar' ).fullCalendar( 'removeEvents', event._id );
                            $j( '#bookacti-template-calendar' ).fullCalendar( 'refetchEvents' );
                            
                        } else {
							if( response.error === 'has_bookings' ) {
								bookacti_refetch_events_on_template( event );
								bookacti_dialog_unbind_occurences( event, [ 'delete' ] );
							} else {
								var error_message = bookacti_localized.error_delete_event;
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
                        alert( 'AJAX ' + bookacti_localized.error_delete_event );
                        console.log( e );
                    },
                    complete: function() { 
						bookacti_stop_template_loading(); 
					}
                });

                //Close the modal dialog
                $j( this ).dialog( 'close' );
				$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
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


// Unbind occurence of a locked repeating event
function bookacti_dialog_unbind_occurences( event, errors ) {
    errors = errors || [];
	
	//Open the modal dialog
    $j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'open' );
    
    //Fill the errors so that the user understand what triggered the dialog
	if( errors.length > 0 ) {
		$j( '#bookacti-unbind-booked-event-error-list-container ul' ).empty();
		$j.each( errors, function( i, error ){ 
			if( error === 'move' )          { $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_move_locked_event + '</li>' ); }
			if( error === 'resize' )        { $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_resize_locked_event + '</li>' ); }
			if( error === 'delete' )        { $j( '#bookacti-unbind-booked-event-error-list-container ul' ).append( '<li>' + bookacti_localized.error_delete_locked_event + '</li>' ); }
		});
		$j( '#bookacti-unbind-booked-event-error-list-container' ).show();
	} else {
		$j( '#bookacti-unbind-booked-event-error-list-container' ).hide();
	}
    
    //Add buttons
	var unbind_selected_button = {
		text: bookacti_localized.dialog_button_unbind_selected,
		class: 'bookacti-dialog-delete-button',
		//On click on the OK Button, new values are send to a script that update the database
		click: function() {
			bookacti_unbind_occurrences( event, 'selected' );
			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
		}
	};
	var unbind_booked_button = {
		text: bookacti_localized.dialog_button_unbind_all_booked,
		class: 'bookacti-dialog-delete-button',
		//On click on the OK Button, new values are send to a script that update the database
		click: function() {
			bookacti_unbind_occurrences( event, 'booked' );
			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
		}
	};
//	var unbind_all_button = {
//		text: bookacti_localized.dialog_button_unbind_all,
//		class: 'bookacti-dialog-delete-button',
//		//On click on the OK Button, new values are send to a script that update the database
//		click: function() {
//			bookacti_unbind_occurrences( event, 'all' );
//			$j( '#bookacti-event-data-dialog' ).dialog( 'close' );
//		}
//	};
	var cancel_button = {
		text: bookacti_localized.dialog_button_cancel,
		click: function() {
			//Close the modal dialog
			$j( this ).dialog( 'close' );
		}
	};
	
	var buttons = [ unbind_booked_button, cancel_button ];
	if( parseInt( event.bookings ) === 0 ) {
		buttons.unshift( unbind_selected_button );
	}
	
    $j( '#bookacti-unbind-booked-event-dialog' ).dialog( 'option', 'buttons', buttons );
}




// ACTIVITIES

// Choose between creating a brand new activity or binding an existing activity to current template
function bookacti_dialog_choose_activity_creation_type() {
	if( template_id ) {
		//Add buttons
		var create_activity_button = {
			text: bookacti_localized.dialog_button_create_activity,
			click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
				
				// Open create activity dialog
				bookacti_dialog_create_activity();
			}
		};
		var import_activity_button = {
			text: bookacti_localized.dialog_button_import_activity,
			click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
				
				// Open import activity dialog
				bookacti_dialog_import_activity();
			}
		};
		var cancel_button = {
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		};

		var buttons = [ create_activity_button, import_activity_button, cancel_button ];
		if( $j( '#bookacti-template-picker option' ).length <= 1 ) {
			buttons.unshift( import_activity_button );
		}

		$j( '#bookacti-activity-create-method-dialog' ).dialog( 'option', 'buttons', buttons );

		//Open the modal dialog
		$j( '#bookacti-activity-create-method-dialog' ).dialog( 'open' );
	}
}


// Import Activity
function bookacti_dialog_import_activity() {
	if( template_id ) {
		// Open the modal dialog
		$j( '#bookacti-activity-import-dialog' ).dialog( 'open' );
		
		// Deactivate current template in template selector
		$j( '#template-import-bound-activities option' ).attr( 'disabled', false );
		$j( '#template-import-bound-activities option[value="' + template_id + '"]' ).attr( 'disabled', true );
		
		// Select the first enabled template
		$j( '#template-import-bound-activities' ).children( 'option:enabled' ).eq( 0 ).prop( 'selected', true );
		$j( '#template-import-bound-activities' ).trigger( 'change' );
		
		//Add the 'OK' button
		$j( '#bookacti-activity-import-dialog' ).dialog( 'option', 'buttons',
			[{
				text: bookacti_localized.dialog_button_ok,

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {
					
					$j( '#bookacti-activity-import-dialog .input-error' ).removeClass( 'input-error' );
					$j( '#bookacti-activity-import-dialog .form-error' ).remove();
					
					var activity_ids = $j( 'select#activities-to-import' ).val();
					
					if( ! $j.isEmptyObject( activity_ids ) ) {
						
						bookacti_start_template_loading();

						$j.ajax({
							url: ajaxurl, 
							data: { 'action': 'bookactiImportActivities', 
									'activity_ids': activity_ids,
									'template_id': template_id,
									'nonce': bookacti_localized.nonce_import_activity
								},
							type: 'POST',
							dataType: 'json',
							success: function(response) {
								if( response.status === 'success' ) {
									
									var plugin_path = bookacti_localized.plugin_path;
									var activity_list = '';

									$j.each( activity_ids, function( i, activity_id ) {
										// Add the selectd activity to draggable activity list
										activity_list	+= "<div class='activity-row'>"
														+       "<div class='activity-show-hide' >"
														+           "<img src='" + plugin_path + "/img/show.png' data-activity-id='" + activity_id + "' data-activity-visible='1' />"
														+       "</div>"
														+       "<div class='activity-container'>"
														+           "<div class='fc-event ui-draggable ui-draggable-handle' "
														+           "data-title='"			+ $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).attr( 'data-title' ) + "' "
														+           "data-activity-id='"	+ activity_id + "' "
														+           "data-color='"			+ $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).attr( 'data-color' ) + "' "
														+           "data-availability='"	+ $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).attr( 'data-availability' ) + "' "
														+           "data-duration='"		+ $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).attr( 'data-duration' ) + "' "
														+           "data-resizable='"		+ $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).attr( 'data-resizable' ) + "' "
														+           ">"
														+               $j( 'select#activities-to-import option[value="' + activity_id + '"]' ).html()
														+           "</div>"
														+       "</div>"
														+       "<div class='activity-gear' >"
														+           "<img src='" + plugin_path + "/img/gear.png' data-activity-id='" + activity_id + "' />"
														+       "</div>"
														+  "</div>";
										
										// Remove the added activity from the select box
										$j( 'select#activities-to-import option[value="' + activity_id + '"]' ).remove();
									});

									$j( '#bookacti-template-activity-list' ).append( activity_list );
									
									//Reinitialize the activities to apply changes
									bookacti_init_activities();
									
									// Update shortcode generator
									bookacti_update_shortcode_generator_activity_ids( activity_ids, true, false );
									
									// Close the modal dialogs
									$j( '#bookacti-activity-import-dialog' ).dialog( 'close' );
									$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );
									
								} else if ( response.status === 'no_activity' ) {
									$j( '#activities-to-import' ).addClass( 'input-error' );
									$j( '#bookacti-activities-bound-to-template' ).append( '<div class="form-error" >' + bookacti_localized.error_no_activity_selected + '</div>' );
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
						$j( '#activities-to-import' ).addClass( 'input-error' );
						$j( '#bookacti-activities-bound-to-template' ).append( '<div class="form-error" >' + bookacti_localized.error_no_activity_selected + '</div>' );
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
}


//Dialog Create Activity
function bookacti_dialog_create_activity() {
	if( template_id ) {
		//Set the dialog title
		$j( '#bookacti-activity-data-dialog' ).dialog({ 
			title: bookacti_localized.dialog_create_activity_title
		});

		// Set init value
		$j( '#bookacti-activity-template-id' ).val( template_id );
		$j( '#bookacti-activity-activity-id' ).val( '' );
		$j( '#bookacti-activity-action' ).val( 'bookactiInsertActivity' );
		$j( '#bookacti-activity-old-title' ).val( '' );
		
		// Add current template in activity bound template select box if it isn't yet
		if( ( ! $j( '#bookacti-activity-templates-select-box' ).val()
			  || $j.inArray( template_id, $j( '#bookacti-activity-templates-select-box' ).val() ) === -1 )
			&&  $j( '#bookacti-add-new-activity-templates-select-box option[value="' + template_id + '"]' ).length ) {
			
				$j( '#bookacti-add-new-activity-templates-select-box' ).val( template_id );
				$j( '#bookacti-activity-templates-container .bookacti-add-items' ).trigger( 'click' );
		
		}

		//Open the modal dialog
		$j( '#bookacti-activity-data-dialog' ).dialog( 'open' );

		//Add the 'OK' button
		$j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'buttons',
			[{
				text: bookacti_localized.dialog_button_ok,

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {
					
					if( ! $j( '#bookacti-activity-templates-select-box' ).val() ) {
						
					}
					
					// Prepare fields
					$j( '#bookacti-activity-data-form select[multiple] option' ).attr( 'selected', true );
					
					//Get the data to save
					var title           = $j( '#bookacti-activity-title' ).val();
					var color           = $j( '#bookacti-activity-color' ).val();
					var availability    = $j( '#bookacti-activity-availability' ).val();
					var days            = $j( '#bookacti-activity-duration-days' ).val();
					var hours           = $j( '#bookacti-activity-duration-hours' ).val();
					var minutes         = $j( '#bookacti-activity-duration-minutes' ).val();
					var duration        = bookacti_pad( days, 3 ) + '.' + bookacti_pad( hours, 2 ) + ':' + bookacti_pad( minutes, 2 ) + ':00';
					var resizable       = $j( '#bookacti-activity-resizable' ).prop('checked');
					if( resizable ) { resizable = '1'; } else { resizable = '0'; }
					resizable = resizable.toString();

					$j( '#bookacti-activity-duration' ).val( duration );

					var data = $j( '#bookacti-activity-data-form' ).serialize();

					var is_form_valid = bookacti_validate_activity_form();

					if( is_form_valid ) {
						bookacti_start_template_loading();

						//Save the new activity in database
						$j.ajax({
							url: ajaxurl, 
							data: data,
							type: 'POST',
							dataType: 'json',
							success: function( response ){
								//If success

								//Retrieve plugin path to display the gear
								var plugin_path = bookacti_localized.plugin_path;

								if( response.status === 'success' ) {
									if( $j.inArray( template_id + '', response.templates ) !== -1 
									||  $j.inArray( template_id		, response.templates ) !== -1 ) {
										
										// Display activity row
										$j( '#bookacti-template-activity-list' ).append(
											"<div class='activity-row'>"
										+       "<div class='activity-show-hide' >"
										+           "<img src='" + plugin_path + "/img/show.png' data-activity-id='" + response.activity_id + "' data-activity-visible='1' />"
										+       "</div>"
										+       "<div class='activity-container'>"
										+           "<div class='fc-event ui-draggable ui-draggable-handle' "
										+           "data-title='" + response.multilingual_title + "' "
										+           "data-activity-id='" + response.activity_id + "' "
										+           "data-color='" + color + "' "
										+           "data-availability='" + availability + "' "
										+           "data-duration='" + duration + "' "
										+           "data-resizable='" + resizable + "' "
										+           ">"
										+               response.title
										+           "</div>"
										+       "</div>"
										+       "<div class='activity-gear' >"
										+           "<img src='" + plugin_path + "/img/gear.png' data-activity-id='" + response.activity_id + "' />"
										+       "</div>"
										+   "</div>"
										);
										
										//Reinitialize the activities to apply changes
										bookacti_init_activities();
										
										// Update shortcode generator
										bookacti_update_shortcode_generator_activity_ids( response.activity_id, true, false );
										
									} else {
										alert( bookacti_localized.advice_activity_created_elsewhere );
										console.log( response );
									}

								//If error
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

						//Close the modal dialogs
						$j( '#bookacti-activity-data-dialog' ).dialog( 'close' );
						$j( '#bookacti-activity-create-method-dialog' ).dialog( 'close' );
					}
				}
			}]
		);
	}
}


//Dialog Update Activity
function bookacti_dialog_update_activity( activity_id ) {
	if( template_id && activity_id ) {
		//Set the dialog title
		$j( '#bookacti-activity-data-dialog' ).dialog({ 
			title: bookacti_localized.dialog_update_activity_title
		});

		// Set init value
		$j( '#bookacti-activity-template-id' ).val( template_id );
		$j( '#bookacti-activity-activity-id' ).val( activity_id );
		$j( '#bookacti-activity-action' ).val( 'bookactiUpdateActivity' );
		$j( '#bookacti-activity-old-title' ).val( $j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'title' ) );
		$j( '#bookacti-activity-data-dialog .bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );

		bookacti_start_template_loading();

		// Retrieve activity info and fill fields
		$j.ajax({
			url: ajaxurl, 
			data: { 'action': 'bookactiGetActivityData', 
					'activity_id': activity_id,
					'nonce': bookacti_localized.nonce_get_activity_data
				},
			type: 'POST',
			dataType: 'json',
			success: function( response ){

				// If success
				if( response.status === 'success' ) {

					// General tab
					$j( '#bookacti-activity-title' ).val( response.activity.title ); 
					$j( '#bookacti-activity-color' ).val( response.activity.color );
					$j( '#bookacti-activity-availability' ).val( response.activity.availability );
					$j( '#bookacti-activity-duration-days' ).val( response.activity.duration.substr( 0, 3 ) );
					$j( '#bookacti-activity-duration-hours' ).val( response.activity.duration.substr( 4, 2 ) );
					$j( '#bookacti-activity-duration-minutes' ).val( response.activity.duration.substr( 7, 2 ) );
					if( response.activity.is_resizable == 1 ) { $j( '#bookacti-activity-resizable' ).prop( 'checked', true ); }
					else { $j( '#bookacti-activity-resizable' ).prop( 'checked', false ); }

					// Permission tab
					if( response.activity.admin ) {
						$j.each( response.activity.admin, function( i, manager_id ) {
							$j( '#bookacti-add-new-activity-managers-select-box option[value="' + manager_id + '"]' ).clone().appendTo( '#bookacti-activity-managers-select-box' );
							$j( '#bookacti-add-new-activity-managers-select-box option[value="' + manager_id + '"]' ).hide().attr( 'disabled', true );
							if( $j( '#bookacti-add-new-activity-managers-select-box' ).val() == manager_id || ! $j( '#bookacti-add-new-activity-managers-select-box' ).val() ) {
								$j( '#bookacti-add-new-activity-managers-select-box' ).val( $j( '#bookacti-add-new-activity-managers-select-box option:enabled:first' ).val() );
							}
						});
					}
					if( response.activity.templates ) {
						$j.each( response.activity.templates, function( i, template_id ) {
							$j( '#bookacti-add-new-activity-templates-select-box option[value="' + template_id + '"]' ).clone().appendTo( '#bookacti-activity-templates-select-box' );
							$j( '#bookacti-add-new-activity-templates-select-box option[value="' + template_id + '"]' ).hide().attr( 'disabled', true );
							if( $j( '#bookacti-add-new-activity-templates-select-box' ).val() == template_id || ! $j( '#bookacti-add-new-activity-templates-select-box' ).val() ) {
								$j( '#bookacti-add-new-activity-templates-select-box' ).val( $j( '#bookacti-add-new-activity-templates-select-box option:enabled:first' ).val() );
							}
						});
					}

					// Settings tabs
					if( response.activity.settings ) {
						bookacti_fill_settings_fields( response.activity.settings, 'activityOptions' );
					}

					//Refresh qtranslate fields to make a correct display of multilingual fields
					if( bookacti_localized.is_qtranslate ) {
						$j( '#bookacti-activity-data-dialog .qtranxs-translatable' ).each( function() { 
							bookacti_refresh_qtx_field( this ); 
						});
					}

				// If error
				} else {
					var error_message = bookacti_localized.error_retrieve_activity_data;
					if( response.error === 'not_allowed' ) {
						error_message += '\n' + bookacti_localized.error_not_allowed;
					}
					alert( error_message );
					console.log( response );
				}
			},
			error: function( e ){
				alert( 'AJAX ' + bookacti_localized.error_retrieve_activity_data );        
				console.log( e );
			},
			complete: function() { 
				bookacti_stop_template_loading(); 

				//Open the modal dialog
				$j( '#bookacti-activity-data-dialog' ).dialog( 'open' );
			}
		});

		// Add buttons
		$j( '#bookacti-activity-data-dialog' ).dialog( 'option', 'buttons',
			[
			//Add the 'OK' button	
			{
				text: bookacti_localized.dialog_button_ok,

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {

					// Prepare fields
					$j( '#bookacti-activity-data-form select[multiple] option' ).attr( 'selected', true );

					//Get the data to save
					var title           = $j( '#bookacti-activity-title' ).val();
					var color           = $j( '#bookacti-activity-color' ).val();
					var availability    = $j( '#bookacti-activity-availability' ).val();
					var days            = $j( '#bookacti-activity-duration-days' ).val();
					var hours           = $j( '#bookacti-activity-duration-hours' ).val();
					var minutes         = $j( '#bookacti-activity-duration-minutes' ).val();
					var duration        = bookacti_pad( days, 3 ) + '.' + bookacti_pad( hours, 2 ) + ':' + bookacti_pad( minutes, 2 ) + ':00';
					var resizable       = $j( '#bookacti-activity-resizable' ).prop( 'checked' );
					if( resizable )		{ resizable = '1'; } else { resizable = '0'; }
					resizable = resizable.toString();

					$j( '#bookacti-activity-duration' ).val( duration );

					var data = $j( '#bookacti-activity-data-form' ).serialize();

					var is_form_valid = bookacti_validate_activity_form();

					if( is_form_valid ) {
						bookacti_start_template_loading();

						//Save updated values in database
						$j.ajax({
							url: ajaxurl, 
							data: data,
							type: 'POST',
							dataType: 'json',
							success: function( response ){

								//If success
								if( response.status === 'success' ) {
									//Update the data in the activities list
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).html( response.title );

									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'title', response.multilingual_title );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'color', color );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'availability', availability );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'duration', duration );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).data( 'resizable', resizable );

									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).attr( 'data-title', response.multilingual_title );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).attr( 'data-color', color );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).attr( 'data-availability', availability );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).attr( 'data-duration', duration );
									$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).attr( 'data-resizable', resizable );

									//Reinitialize the activities to apply changes
									bookacti_init_activities();

									//Clear the calendar and refetch events
									bookacti_refetch_events_on_template();

								//If error
								} else if( response.status === 'no_templates' ) {
									alert( bookacti_localized.error_no_templates_for_activity );
									console.log( response );
									
								} else if (response.status === 'failed_update_activity' ) {
									alert( bookacti_localized.error_update_activity );
									console.log( response );

								} else if (response.status === 'failed_update_bound_events' ) {
									alert( bookacti_localized.error_update_bound_events );
									console.log( response );

								} else if (response.status === 'no_changes' ) {
									
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
				class: 'bookacti-dialog-delete-button bookacti-dialog-left-button',

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {
					bookacti_dialog_delete_activity( activity_id );
				}
			}]
		);
	}
}


// Dialog Delete Activity
function bookacti_dialog_delete_activity( activity_id ) {
	if( activity_id ) {
		//Open the modal dialog
		$j( '#bookacti-delete-activity-dialog' ).dialog( 'open' );

		//Add the 'OK' button
		$j( '#bookacti-delete-activity-dialog' ).dialog( 'option', 'buttons',
			[{
				text: bookacti_localized.dialog_button_delete,
				class: 'bookacti-dialog-delete-button',

				//On click on the OK Button, new values are send to a script that update the database
				click: function() {

					bookacti_start_template_loading();

					$j.ajax({
						url: ajaxurl, 
						data: { 'action': 'bookactiDeactivateActivity', 
								'activity_id': activity_id,
								'template_id': template_id,
								'nonce': bookacti_localized.nonce_deactivate_activity
							},
						type: 'POST',
						dataType: 'json',
						success: function(response) {
							if( response.status === 'success' ) {
								$j( '.fc-event[data-activity-id="' + activity_id + '"]' ).parents( '.activity-row' ).remove();
								
								// Display tuto if there is no more activities available
								bookacti_display_activity_tuto_if_no_activity_available();
								
								// Update shortcode generator
								bookacti_update_shortcode_generator_activity_ids( activity_id, false, false );
								
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

					//Close the modal dialog
					$j( this ).dialog( 'close' );
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
}




// GROUPS OF EVENTS

// Create a group of events
function bookacti_dialog_create_group_of_events( category_id ) {
	
	category_id = category_id ? category_id : selectedCategory;
	
	// Change dialog title
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_create_group_of_events_title
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
	selectedEvents[ 'template' ] = bookacti_sort_events_array_by_dates( selectedEvents[ 'template' ] );
	$j.each( selectedEvents[ 'template' ], function( i, event ){
		
		var event_start = moment( event.start );
		var event_end = moment( event.end );
		
		var event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( 'LT' );
		}
		var option = $j( '<option />', {
						html: event.title + ' - ' + event_duration
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});

	// Open the modal dialog
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );

	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				
				// Prepare fields
				$j( '#bookacti-group-of-events-action' ).val( 'bookactiInsertGroupOfEvents' );
				$j( '#bookacti-group-of-events-form select[multiple] option' ).attr( 'selected', true );
				
				//Get the data to save
				var selected_category_id	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
				selectedCategory			= selected_category_id;
				
				var data = $j( '#bookacti-group-of-events-form' ).serializeArray();
				data.push( { name: 'template_id', value: template_id } );
				data.push( { name: 'events', value: JSON.stringify( selectedEvents[ 'template' ] ) } );
				
				var is_form_valid = bookacti_validate_group_of_events_form();
				
				if( is_form_valid ) {
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
								
								// If it is the first group of events, hide tuto and show groups list
								$j( '#bookacti-template-add-first-group-of-events-container' ).hide();
								
								// If the user has created a group category
								if( selected_category_id === 'new' ) {
									bookacti_add_group_category( response.category_id, response.category_title );
								}
								
								// Add the group row to the category
								bookacti_add_group_of_events( response.group_id, response.group_title, response.category_id );
								
								// Store the events of the groups
								json_groups[ 'template' ][ response.group_id ] = [];
								$j.each( selectedEvents[ 'template' ], function( i, event ){
									// Add event data
									event.group_id	= response.group_id;
									event.active	= 1;
									// Store event in json_groups[ 'template' ] global var
									json_groups[ 'template' ][ response.group_id ].push( event );
								});
								
								// Empty the selected events and refresh them
								selectedEvents[ 'template' ] = [];
								$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
								$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
								
								// Exit group editing mode
								bookacti_exit_group_edition();
								
							//If error
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

					//Close the modal dialogs
					$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
				}
			}
		}]
	);
}


// Whether to let the user update the selected events or open the group of events update dialog
function bookacti_update_group_of_events( group_id ) {
	var open_dialog = $j( '.bookacti-group-of-events[data-group-id="' + group_id + '"] .bookacti-update-group-of-events img' ).hasClass( 'validate-group' );
	if( open_dialog ) {
		bookacti_dialog_update_group_of_events( group_id );
	} else {
		bookacti_select_events_of_group( group_id );
	}
}


// Update a group of events with selected events 
function bookacti_dialog_update_group_of_events( group_id ) {
	
	// Change dialog title
	$j( '#bookacti-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_update_group_of_events_title
	});
	
	// Select the group category
	var category_id = $j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).parents( '.bookacti-group-category' ).data( 'group-category-id' );
	var initial_category_id = category_id ? category_id : 'new';
	$j( '#bookacti-group-of-events-category-selectbox' ).val( category_id ).trigger( 'change' );

	// Fill the events list as a feedback for user
	$j( '#bookacti-group-of-events-summary' ).empty();
	selectedEvents[ 'template' ] = bookacti_sort_events_array_by_dates( selectedEvents[ 'template' ] );
	$j.each( selectedEvents[ 'template' ], function( i, event ){
		var event_start = moment( event.start );
		var event_end = moment( event.end );
		
		var event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( bookacti_localized.date_format );
		if( event.start.substr( 0, 10 ) === event.end.substr( 0, 10 ) ) {
			event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( 'LT' );
		}
		var option = $j( '<option />', {
						html: event.title + ' - ' + event_duration
					} );
		option.appendTo( '#bookacti-group-of-events-summary' );
	});
	
	
	// Retrieve group of events data and fill fields
	bookacti_start_template_loading();
	$j.ajax({
		url: ajaxurl, 
		data: { 'action': 'bookactiGetGroupOfEventsData', 
				'group_id': group_id,
				'nonce': bookacti_localized.nonce_get_group_of_events_data
			},
		type: 'POST',
		dataType: 'json',
		success: function( response ){
			// If success
			if( response.status === 'success' ) {
				
				// General tab
				$j( '#bookacti-group-of-events-title-field' ).val( response.title ); 
				
				// Other settings
				if( response.settings.length ) {
					bookacti_fill_settings_fields( response.settings, 'groupOfEventsOptions' );
				}
				
				//Refresh qtranslate fields to make a correct display of multilingual fields
				if( bookacti_localized.is_qtranslate ) {
					$j( '#bookacti-group-of-events-dialog .qtranxs-translatable' ).each( function() { 
						bookacti_refresh_qtx_field( this ); 
					});
				}

			// If error
			} else {
				var error_message = bookacti_localized.error_retrieve_group_of_events_data;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
				console.log( response );
			}
		},
		error: function(  e){
			alert( 'AJAX ' + bookacti_localized.error_retrieve_group_of_events_data );        
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading(); 

			//Open the modal dialog
			$j( '#bookacti-group-of-events-dialog' ).dialog( 'open' );
		}
	});
	

	// Add the 'OK' button
	$j( '#bookacti-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				
				// Prepare fields
				$j( '#bookacti-group-of-events-action' ).val( 'bookactiUpdateGroupOfEvents' );
				$j( '#bookacti-group-of-events-form select[multiple] option' ).attr( 'selected', true );
				
				//Get the data to save
				var selected_category_id	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
				selectedCategory			= selected_category_id;
				
				var data = $j( '#bookacti-group-of-events-form' ).serializeArray();
				data.push( { name: 'group_id', value: group_id } );
				data.push( { name: 'events', value: JSON.stringify( selectedEvents[ 'template' ] ) } );
				
				var is_form_valid = bookacti_validate_group_of_events_form();
				
				if( is_form_valid ) {
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
								
								// If the user has created a group category
								if( selected_category_id === 'new' ) {
									bookacti_add_group_category( response.category_id, response.category_title );
								}
								
								// If user changed category
								if( initial_category_id != selected_category_id ) {
									// Remove the group from the old categroy and add it to the new one
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).remove();
									bookacti_add_group_of_events( group_id, response.group_title, response.category_id );
									
								} else {
									// Update group title in groups list
									var group_short_title = response.group_title.length > 16 ? response.group_title.substr( 0, 16 ) + '&#8230;' : response.group_title;
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"] .bookacti-group-of-events-title' ).attr( 'title', response.group_title );
									$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"] .bookacti-group-of-events-title' ).html( group_short_title );
								}
								
								// Update the events of the groups
								json_groups[ 'template' ][ group_id ] = [];
								$j.each( selectedEvents[ 'template' ], function( i, event ){
									// Add event data
									event.group_id	= group_id;
									event.active	= 1;
									// Store event in json_groups[ 'template' ] global var
									json_groups[ 'template' ][ group_id ].push( event );
								});
								
								// Empty the selected events and refresh them
								selectedEvents[ 'template' ] = [];
								$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
								$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
								
								// Exit group editing mode
								bookacti_exit_group_edition();
								
								
							//If error
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

					//Close the modal dialogs
					$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
				}
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			class: 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			
			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				bookacti_dialog_delete_group_of_events( group_id );
			}
		}]
	);
}


// Delete a group of events
function bookacti_dialog_delete_group_of_events( group_id ) {
	//Open the modal dialog
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'open' );

	//Add the 'OK' button
	$j( '#bookacti-delete-group-of-events-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			class: 'bookacti-dialog-delete-button',

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {

				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: { 'action': 'bookactiDeleteGroupOfEvents', 
							'group_id': group_id,
							'nonce': bookacti_localized.nonce_delete_group_of_events
						},
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							
							// Remove the group of events from its category
							$j( '.bookacti-group-of-events[data-group-id="' + group_id + '"]' ).remove();
							
							// Empty the selected events and refresh them
							selectedEvents[ 'template' ] = [];
							$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
							$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
							
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

				//Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-group-of-events-dialog' ).dialog( 'close' );
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



// GROUP CATEGORIES

// Update a group category
function bookacti_dialog_update_group_category( category_id ) {
	
	// Retrieve category data and fill fields
	bookacti_start_template_loading();
	$j.ajax({
		url: ajaxurl, 
		data: { 'action': 'bookactiGetGroupCategoryData', 
				'category_id': category_id,
				'nonce': bookacti_localized.nonce_get_group_category_data
			},
		type: 'POST',
		dataType: 'json',
		success: function( response ){
			// If success
			if( response.status === 'success' ) {
				
				// General tab
				$j( '#bookacti-group-category-title-field' ).val( response.title ); 
				
				// Other settings
				if( response.settings.length ) {
					bookacti_fill_settings_fields( response.settings, 'groupCategoryOptions' );
				}
				
				//Refresh qtranslate fields to make a correct display of multilingual fields
				if( bookacti_localized.is_qtranslate ) {
					$j( '#bookacti-group-category-dialog .qtranxs-translatable' ).each( function() { 
						bookacti_refresh_qtx_field( this ); 
					});
				}

			// If error
			} else {
				var error_message = bookacti_localized.error_retrieve_group_category_data;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				alert( error_message );
				console.log( response );
			}
		},
		error: function(  e){
			alert( 'AJAX ' + bookacti_localized.error_retrieve_group_category_data );        
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_template_loading(); 

			//Open the modal dialog
			$j( '#bookacti-group-category-dialog' ).dialog( 'open' );
		}
	});
	
	
	//Add the 'OK' button
	$j( '#bookacti-group-category-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {

				// Prepare fields
				$j( '#bookacti-group-category-action' ).val( 'bookactiUpdateGroupCategory' );
				$j( '#bookacti-group-category-form select[multiple] option' ).attr( 'selected', true );

				//Get the data to save
				var data = $j( '#bookacti-group-category-form' ).serializeArray();
				data.push( { name: 'category_id', value: category_id } );

				var is_form_valid = bookacti_validate_group_category_form();

				if( is_form_valid ) {
					bookacti_start_template_loading();

					//Save the new activity in database
					$j.ajax({
						url: ajaxurl, 
						data: data,
						type: 'POST',
						dataType: 'json',
						success: function( response ){
							
							// If success
							if( response.status === 'success' ) {
								
								// Update category title in groups list
								var category_short_title = response.title.length > 16 ? response.title.substr( 0, 16 ) + '&#8230;' : response.title;
								$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-group-category-title' ).attr( 'title', response.title );
								$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"] .bookacti-group-category-title span' ).html( category_short_title );
								
								// Update category title in selectbox
								$j( '#bookacti-group-of-events-category-selectbox option[value="' + category_id + '"]' ).html( response.title );
								
							//If error
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

					//Close the modal dialogs
					$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
				}
			}
		},
		// Add the 'delete' button
		{
			text: bookacti_localized.dialog_button_delete,
			class: 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			
			//On click on the OK Button, new values are send to a script that update the database
			click: function() {
				bookacti_dialog_delete_group_category( category_id );
			}
		}]
	);
}


// Delete a group category
function bookacti_dialog_delete_group_category( category_id ) {
	//Open the modal dialog
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'open' );

	//Add the 'OK' button
	$j( '#bookacti-delete-group-category-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_delete,
			class: 'bookacti-dialog-delete-button',

			//On click on the OK Button, new values are send to a script that update the database
			click: function() {

				bookacti_start_template_loading();

				$j.ajax({
					url: ajaxurl, 
					data: { 'action': 'bookactiDeleteGroupCategory', 
							'category_id': category_id,
							'nonce': bookacti_localized.nonce_delete_group_category
						},
					type: 'POST',
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							
							// Remove the group category and all its groups
							$j( '.bookacti-group-category[data-group-category-id="' + category_id + '"]' ).remove();
							
							// Remove the category from the selectbox
							$j( '#bookacti-group-of-events-category-selectbox option[value="' + category_id + '"]' ).remove();
							
							// Empty the selected events and refresh them
							selectedEvents[ 'template' ] = [];
							$j( '#bookacti-template-calendar' ).fullCalendar( 'rerenderEvents' );
							$j( '#bookacti-insert-group-of-events' ).css( 'visibility', 'hidden' );
							
							// If it was the last category, display the tuto
							if( ! $j( '.bookacti-group-category' ).length ) {
								$j( '#bookacti-template-add-group-of-events-tuto-select-events' ).show();
							}
							
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

				//Close the modal dialog
				$j( this ).dialog( 'close' );
				$j( '#bookacti-group-category-dialog' ).dialog( 'close' );
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