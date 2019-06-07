
/**
 * Initialize form editor actions
 * @since 1.5.0
 * @version 1.6.0
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
 * @version 1.7.5
 */
function bookacti_dialog_update_form_meta() {
	
	var form_id	= $j( '#bookacti-form-id' ).val();
	
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
	
	// Open the modal dialog
    $j( '#bookacti-form-meta-dialog' ).dialog( 'open' );
	
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
				
				var data = $j( 'form#bookacti-update-form-meta-form' ).serializeObject();
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ){
						
						if( response.status === 'success' ) {
							
							bookacti.form_editor.form = response.form_data;
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_form_meta_updated', [ form_id ] );
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_update_form;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_form );
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
}


/**
 * Insert form field
 * @since 1.5.0
 * @version 1.5.3
 */
function bookacti_dialog_insert_form_field() {
	
	// Open the modal dialog
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'open' );
	
	// Add the buttons
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				
				var form_id		= $j( '#bookacti-form-id' ).val();
				var field_name	= $j( '#bookacti-field-to-insert' ).val();
				var nonce		= $j( '#nonce_insert_form_field' ).val();
				if( ! field_name || ! form_id || ! nonce ) { return; }
				
				// Clear errors
				$j( '#bookacti-insert-form-field-dialog' ).find( '.bookacti-loading-alt,.bookacti-notices' ).remove();
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-insert-form-field-dialog' ).append( loading_div );
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiInsertFormField', 
							'form_id': form_id,
							'field_name': field_name,
							'nonce': nonce
						},
					dataType: 'json',
					success: function( response ){
						
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
							$j( '#bookacti-field-to-insert' ).val( $j( '#bookacti-field-to-insert option:not([disabled]):first' ).val() );
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_inserted', [ response.field_id, field_name ] );
							
							// Close the modal dialog
							$j( '#bookacti-insert-form-field-dialog' ).dialog( 'close' );
							
						} else if( response.status === 'failed' ) {
							$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + '</li></ul></div>' );
							console.log( response );
						}
						
					},
					error: function( e ){
						$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error_insert_form_field + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error_insert_form_field );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-insert-form-field-dialog .bookacti-notices' ).show();
						$j( '#bookacti-insert-form-field-dialog .bookacti-loading-alt' ).remove();
						bookacti_form_editor_exit_loading_state();
					}
				});
			}
		}]
    );
}


/**
 * Remove form field
 * @since 1.5.0
 * @param {int} field_id
 * @param {string} field_name
 */
function bookacti_dialog_remove_form_field( field_id, field_name ) {
	
	// Open the modal dialog
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'open' );
	
	// Add the buttons
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				
				var nonce = $j( '#nonce_remove_form_field' ).val();
				if( ! field_id || ! nonce ) { return; }
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiRemoveFormField', 
							'field_id': field_id,
							'nonce': nonce
						},
					dataType: 'json',
					success: function( response ){
						
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
							var message_error = bookacti_localized.error_remove_form_field;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_remove_form_field );
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
}


/**
 * Update Form Field
 * @since 1.5.0
 * @version 1.7.5
 * @param {int} field_id
 * @param {string} field_name
 */
function bookacti_dialog_update_form_field( field_id, field_name ) {
	
	// Fill field id
	$j( 'form#bookacti-form-field-form-' + field_name + ' input[name="action"]' ).val( 'bookactiUpdateFormField' );
	$j( 'form#bookacti-form-field-form-' + field_name + ' input[name="field_id"]' ).val( field_id );
	
	// Transform multiple select
	if( $j( 'form#bookacti-form-field-form-' + field_name + ' .bookacti-multiple-select' ).length ) {
		$j( 'form#bookacti-form-field-form-' + field_name + ' .bookacti-multiple-select' ).each( function(){
			var select_id = $j( this ).data( 'select-id' );
			var select_name = $j( '#' + select_id ).attr( 'name' );
			if( ! ( select_name in bookacti.form_editor.fields[ field_id ] ) ) { return; }
			if( $j.isPlainObject( bookacti.form_editor.fields[ field_id ][ select_name ] )
			 || (  $j.isArray( bookacti.form_editor.fields[ field_id ][ select_name ] ) 
				&& bookacti.form_editor.fields[ field_id ][ select_name ].length > 1 ) ) {
				$j( this ).prop( 'checked', true );
			} else {
				$j( this ).prop( 'checked', false );
			}
			bookacti_switch_select_to_multiple( this );
		});
	}
	
	// Fill the fields with current data
	bookacti_fill_fields_from_array( bookacti.form_editor.fields[ field_id ], '', 'form#bookacti-form-field-form-' + field_name );
	
	$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_update_dialog', [ field_id, field_name ] );
	
	// Refresh qtranslate fields to make a correct display of multilingual fields
	if( bookacti_localized.is_qtranslate ) {
		$j( 'form#bookacti-form-field-form-' + field_name + ' .qtranxs-translatable' ).each( function() { 
			bookacti_refresh_qtx_field( this ); 
		});
	}
	
	// Open the modal dialog
    $j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'open' );
	
	// Add the buttons
    $j( '#bookacti-form-field-dialog-' + field_name ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				// Clear errors
				$j( '#bookacti-form-field-dialog-' + field_name ).find( '.bookacti-loading-alt,.bookacti-notices' ).remove();
				
				// Save tineMCE editors content 
				if( typeof tinyMCE !== 'undefined' ) { 
					if( tinyMCE ) { tinyMCE.triggerSave(); }
				}
				
				// Prepare the fields
				$j( 'form#bookacti-form-field-form-' + field_name + ' select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				var data = $j( 'form#bookacti-form-field-form-' + field_name ).serializeObject();
				var is_visible = $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-body' ).is( ':visible' );
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-form-field-dialog-' + field_name ).append( loading_div );
				
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
							$j( '#bookacti-form-field-dialog-' + field_name ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + '</li></ul></div>' );
							console.log( response );
						}
						
					},
					error: function( e ){
						$j( '#bookacti-insert-form-field-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error_update_form_field + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error_update_form_field );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-form-field-dialog-' + field_name + ' .bookacti-notices' ).show();
						$j( '#bookacti-form-field-dialog-' + field_name + ' .bookacti-loading-alt' ).remove();
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
				var data = $j( 'form#bookacti-form-field-form-' + field_name ).serializeObject();
				var is_visible = $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-body' ).is( ':visible' );
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
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
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_reset', [ field_id, field_name, response ] );
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_reset_form;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_reset_form );
						console.log( e );
					},
					complete: function() {
						bookacti_form_editor_exit_loading_state();
					}
				});
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}
		]
    );
}


/**
 * Export events
 * @since 1.6.0
 * @param {int} field_id
 */
function bookacti_dialog_export_events( form_id ) {
	
	// Reset error notices
	$j( '#bookacti-export-events-dialog .bookacti-notices' ).remove();
	
	// Fill the field
	$j( '#bookacti_export_events_url_secret' ).val( $j( '#bookacti_export_events_url_secret' ).data( 'value' ) );
	
	// Open the modal dialog
    $j( '#bookacti-export-events-dialog' ).dialog( 'open' );
	
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
				var nonce = $j( '#nonce_reset_export_events_url' ).val();
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
					success: function( response ){
						
						if( response.status === 'success' ) {
							
							var new_export_events_url = $j( '#bookacti_export_events_url_secret' ).data( 'value' ).replace( response.old_secret_key, response.new_secret_key );
							$j( '#bookacti_export_events_url_secret' ).data( 'value', new_export_events_url );
							$j( '#bookacti_export_events_url_secret' ).attr( 'data-value', new_export_events_url );
							$j( '#bookacti_export_events_url_secret' ).val( new_export_events_url );
							
							$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' ).show();
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_export_events_url_reset', [ form_id, response ] );
							
						} else if( response.status === 'failed' ) {
							
							var error_message = typeof response.message !== 'undefined' ? response.message : '';
							if( ! error_message ) {
								error_message += bookacti_localized.error_reset_export_events_url;
								var error_code = typeof response.error !== 'undefined' ? response.error : '';
								if( error_code ) {
									error_message += ' (' + error_code + ')';
								}
							}
							$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ){
						$j( '#bookacti-export-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error_reset_export_events_url + '</li></ul></div>' ).show();
						console.log( 'AJAX ' + bookacti_localized.error_reset_export_events_url );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-export-events-dialog .bookacti-notices' ).show();
						bookacti_form_editor_exit_loading_state();
					}
				});
			}
		}
		]
    );
}