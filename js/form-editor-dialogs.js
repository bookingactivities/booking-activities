/**
 * Initialize form editor actions
 * @since 1.5.0
 * @version 1.8.0
 */
function bookacti_init_form_editor_actions() {
	// Open form dialog boxes
	$j( '#bookacti-form-editor-actions' ).on( 'click', '.bookacti-form-editor-action', function( e ) {
		if( $j( this ).is( '#bookacti-update-form-meta' ) ){
			bookacti_dialog_update_form_meta();
		}
		if( $j( this ).is( '#bookacti-insert-form-field' ) ){
			bookacti_dialog_insert_form_field();
		}
	});
	
	// Open form field dialog boxes
	$j( '#bookacti-form-editor' ).on( 'click', '.bookacti-form-editor-field-action', function( e ) {
		var field_name	= $j( this ).closest( '.bookacti-form-editor-field' ).data( 'field-name' );
		var field_id	= $j( this ).closest( '.bookacti-form-editor-field' ).data( 'field-id' );
		
		if( $j( this ).hasClass( 'bookacti-remove-form-field' ) ){
			bookacti_dialog_remove_form_field( field_id, field_name );
		}
		if( $j( this ).hasClass( 'bookacti-edit-form-field' ) ){
			bookacti_dialog_update_form_field( field_id, field_name );
		}
		if( $j( this ).hasClass( 'bookacti-export-events' ) ){
			var form_id	= $j( '#bookacti-form-id' ).val();
			bookacti_dialog_export_events( form_id );
		}
		if( $j( this ).hasClass( 'bookacti-login-form-shortcode' ) ){
			bookacti_dialog_login_form_shortcode();
		}
		if( $j( this ).hasClass( 'bookacti-display-help' ) ){
			bookacti_dialog_calendar_field_help();
		}
	});
	
	// Init export button
	$j( '.bookacti_export_button input[type="button"]' ).on( 'click', function() {
		var url = $j( this ).closest( '.bookacti_export_url' ).find( '.bookacti_export_url_field input' ).val();
		if( url ) {
			window.open( url, '_blank' );
		}
	});
}


// DIALOGS

/**
 * Update Form Meta
 * @since 1.5.0
 * @version 1.15.13
 */
function bookacti_dialog_update_form_meta() {
	var form_id	= $j( '#bookacti-form-id' ).val();
	
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-form-meta-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-form-meta-dialog' ).dialog({ 
		"title": dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + form_id + ')'
	});
	
	// Fill field id
	$j( 'form#bookacti-update-form-meta-form input[name="action"]' ).val( 'bookactiUpdateFormMeta' );
	$j( 'form#bookacti-update-form-meta-form input[name="form_id"]' ).val( form_id );
	
	// Fill the fields with current data
	bookacti_fill_fields_from_array( bookacti.form_editor.form, '', 'form#bookacti-update-form-meta-form' );
	
	$j( '#bookacti-form-meta-dialog' ).trigger( 'bookacti_form_meta_update_dialog', [ form_id ] );
	
	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( 'form#bookacti-update-form-meta-form .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Add the buttons
    $j( '#bookacti-form-meta-dialog' ).dialog( 'option', 'buttons',
		// OK button
		[{
			'text': bookacti_localized.dialog_button_ok,			
			'click': function() { 
				
				// Save tineMCE editors content 
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var data = bookacti_serialize_object( $j( 'form#bookacti-update-form-meta-form' ) );
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							bookacti.form_editor.form = response.form_data;
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_form_meta_updated', [ form_id ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						bookacti_form_editor_exit_loading_state();
					}
				});
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-form-meta-dialog' ).dialog( 'open' );
}


/**
 * Insert form field
 * @since 1.5.0
 * @version 1.15.5
 */
function bookacti_dialog_insert_form_field() {
	// Select the first available field
	$j( '#bookacti-field-to-insert option:not(:disabled):first' ).prop( 'selected', true );
	$j( '#bookacti-field-to-insert' ).trigger( 'change' );
	
	// Add the buttons
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				var form_id    = $j( '#bookacti-form-id' ).val();
				var field_name = $j( '#bookacti-field-to-insert' ).val();
				var nonce      = $j( '#bookacti-insert-form-field-form input[name="nonce"]' ).val();
				if( ! field_name || ! form_id || ! nonce ) { return; }
				
				// Clear errors
				$j( '#bookacti-insert-form-field-dialog' ).find( '.bookacti-notices' ).remove();
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				bookacti_add_loading_html( $j( '#bookacti-insert-form-field-dialog' ) );
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 
						'action': 'bookactiInsertFormField', 
						'form_id': form_id,
						'field_name': field_name,
						'nonce': nonce
					},
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Insert the field
							$j( '#bookacti-form-editor > .bookacti-form-editor-field:last' ).after( response.field_html );
							
							// Reload tooltip for generated content
							bookacti_init_tooltip();
							
							// Update the field data
							bookacti.form_editor.fields[ response.field_id ] = response.field_data;
							bookacti.form_editor.form.field_order = response.field_order;
							
							// Prevent this field from being inserted again (if unique)
							$j( '#bookacti-field-to-insert option[value="' + field_name + '"][data-unique="1"]' ).attr( 'disabled', true );
							$j( '#bookacti-field-to-insert' ).val( $j( '#bookacti-field-to-insert option:not([disabled]):first' ).val() ).trigger( 'change' );
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_inserted', [ response.field_id, field_name ] );
							
							// Close the modal dialog
							$j( '#bookacti-insert-form-field-dialog' ).dialog( 'close' );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ) {
						$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-insert-form-field-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-insert-form-field-dialog' ) );
						bookacti_form_editor_exit_loading_state();
					}
				});
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'open' );
}


/**
 * Remove form field
 * @since 1.5.0
 * @version 1.15.5
 * @param {int} field_id
 * @param {string} field_name
 */
function bookacti_dialog_remove_form_field( field_id, field_name ) {
	// Set the dialog title
	var dialog_title_raw = $j.trim( $j( '#bookacti-remove-form-field-dialog' ).dialog( 'option', 'title' ).replace( /\(.*?\)/, '' ) );
	$j( '#bookacti-remove-form-field-dialog' ).dialog({ 
		"title": dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + field_id + ')'
	});
	
	// Add the buttons
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				
				var nonce = $j( '#bookacti-remove-form-field-form input[name="nonce"]' ).val();
				if( ! field_id || ! nonce ) { return; }
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 
						'action': 'bookactiRemoveFormField', 
						'field_id': field_id,
						'nonce': nonce
					},
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Remove the field form the form
							$j( '#bookacti-form-editor-field-' + field_id ).remove();
							
							// Remove field data
							delete bookacti.form_editor.fields[ field_id ];
							bookacti.form_editor.form.field_order = response.field_order;
							
							// Enable this field to be inserted again
							$j( '#bookacti-field-to-insert option[value="' + field_name + '"]' ).attr( 'disabled', false );
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_removed', [ field_id, field_name ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						bookacti_form_editor_exit_loading_state();
					}
				});
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'open' );
}


/**
 * Update Form Field
 * @since 1.5.0
 * @version 1.15.13
 * @param {int} field_id
 * @param {string} field_name
 */
function bookacti_dialog_update_form_field( field_id, field_name ) {
	// Set the dialog title
	if( $j( '#bookacti-form-field-dialog-' + field_name ).length ) {
		var dialog_title_current = $j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'option', 'title' );
		var dialog_title_raw = $j.type( dialog_title_current ) === 'string' ? $j.trim( dialog_title_current.replace( /\(.*?\)/, '' ) ) : '';
		if( dialog_title_raw ) {
			$j( '#bookacti-form-field-dialog-' + field_name ).dialog({ 
				"title": dialog_title_raw + ' (' + bookacti_localized.edit_id + ': ' + field_id + ')'
			});
		}
	}
	
	// Fill field id
	$j( 'form#bookacti-form-field-form-' + field_name + ' input[name="action"]' ).val( 'bookactiUpdateFormField' );
	$j( 'form#bookacti-form-field-form-' + field_name + ' input[name="field_id"]' ).val( field_id );
		
	// Fill the fields with current data
	bookacti_fill_fields_from_array( bookacti.form_editor.fields[ field_id ], '', 'form#bookacti-form-field-form-' + field_name );
	
	$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_update_dialog', [ field_id, field_name ] );
	
	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( 'form#bookacti-form-field-form-' + field_name + ' .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Add the buttons
    $j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				// Clear errors
				$j( '#bookacti-form-field-dialog-' + field_name ).find( '.bookacti-notices' ).remove();
				
				// Save tineMCE editors content 
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				var data = bookacti_serialize_object( $j( 'form#bookacti-form-field-form-' + field_name ) );
				var is_visible = $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-body' ).is( ':visible' );
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				bookacti_add_loading_html( $j( '#bookacti-form-field-dialog-' + field_name ) );
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ){
						if( response.status === 'success' ) {
							// Update the field content
							if( field_name !== 'calendar' ) {
								$j( '#bookacti-form-editor-field-' + field_id ).replaceWith( response.field_html );
								
								// Toogle field
								if( is_visible ) { $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-header' ).trigger( 'click' ); }
							}
							
							// Update the field data
							bookacti.form_editor.fields[ field_id ] = response.field_data;
							
							// Reload tooltip for generated content
							bookacti_init_tooltip();
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_updated', [ field_id, field_name, response ] );
							
							// Close the modal dialog
							$j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'close' );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-form-field-dialog-' + field_name ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ) {
						$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-form-field-dialog-' + field_name + ' .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-form-field-dialog-' + field_name ) );
						bookacti_form_editor_exit_loading_state();
					}
				});
			}
		},
		// Reset Button
		{
			'text': bookacti_localized.dialog_button_reset,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			'click': function() {
				// Save tineMCE editors content 
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				$j( 'form#bookacti-form-field-form-' + field_name + ' input[name="action"]' ).val( 'bookactiResetFormField' );
				var data = bookacti_serialize_object( $j( 'form#bookacti-form-field-form-' + field_name ) );
				var is_visible = $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-body' ).is( ':visible' );
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Update the field content
							if( field_name !== 'calendar' ) {
								$j( '#bookacti-form-editor-field-' + field_id ).replaceWith( response.field_html );
								
								// Toogle field
								if( is_visible ) { $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-header' ).trigger( 'click' ); }
							}
							
							// Update the field data
							bookacti.form_editor.fields[ field_id ] = response.field_data;
							
							// Reload tooltip for generated content
							bookacti_init_tooltip();
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_reset', [ field_id, field_name, response ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ) {
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						bookacti_form_editor_exit_loading_state();
					}
				});
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'open' );
}


/**
 * Export events
 * @since 1.6.0
 * @version 1.15.5
 * @param {int} form_id
 */
function bookacti_dialog_export_events( form_id ) {
	// Fill the field
	$j( '#bookacti_export_events_url_secret' ).val( $j( '#bookacti_export_events_url_secret' ).data( 'value' ) );
	
	// Add the buttons
    $j( '#bookacti-export-events-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() {
				// Reset error notices
				$j( '#bookacti-export-events-dialog .bookacti-notices' ).remove();
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		},
		
		// Reset the address
		{
			text: bookacti_localized.dialog_button_reset,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			click: function() { 
				var nonce = $j( '#bookacti-export-events-form input[name="nonce"]' ).val();
				if( ! form_id || ! nonce ) { return; }
				
				// Reset error notices
				$j( '#bookacti-export-events-dialog .bookacti-notices' ).remove();
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiResetExportEventsUrl', 
							'form_id': form_id,
							'nonce': nonce
						},
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							var new_export_events_url = $j( '#bookacti_export_events_url_secret' ).data( 'value' ).replace( response.old_secret_key, response.new_secret_key );
							$j( '#bookacti_export_events_url_secret' ).data( 'value', new_export_events_url );
							$j( '#bookacti_export_events_url_secret' ).attr( 'data-value', new_export_events_url );
							$j( '#bookacti_export_events_url_secret' ).val( new_export_events_url );
							
							$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' ).show();
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_export_events_url_reset', [ form_id, response ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ){
						$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-export-events-dialog .bookacti-notices' ).show();
						bookacti_form_editor_exit_loading_state();
					}
				});
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-export-events-dialog' ).dialog( 'open' );
}


/**
 * Display the calendar field help dialog
 * @since 1.8.0
 */
function bookacti_dialog_calendar_field_help() {
	// Add the buttons
    $j( '#bookacti-calendar-field-help-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() {
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
	// Open the modal dialog
    $j( '#bookacti-calendar-field-help-dialog' ).dialog( 'open' );
}


/**
 * Display the login form shortcode dialog
 * @since 1.8.0
 */
function bookacti_dialog_login_form_shortcode() {
	// Add the buttons
    $j( '#bookacti-login-form-shortcode-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() {
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
	// Open the modal dialog
    $j( '#bookacti-login-form-shortcode-dialog' ).dialog( 'open' );
}