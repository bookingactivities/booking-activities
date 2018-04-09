// Initialize form editor dialogs
function bookacti_init_form_editor_dialogs() {
	//Common param
	$j( '.bookacti-form-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		440,
		"resize":		'auto',
		"show":			true,
		"hide":			true,
		"dialogClass":	'bookacti-dialog',
		"closeText":	'&#10006;',
		"beforeClose":	function() { 
			var scope = '.bookacti-form-dialog';
			var dialog_id = $j( this ).attr( 'id' );
			if( dialog_id ) { scope = '#' + dialog_id; }
			bookacti_empty_all_dialog_forms( scope ); 
		}
	});
	
	// Specific param
	$j( '#bookacti-form-field-dialog-free_text' ).dialog( 'option', 'width', 540 );
	
	// Make dialogs close when the user click outside
	$j( '.ui-widget-overlay' ).live( 'click', function (){
		$j( 'div:ui-dialog:visible' ).dialog( 'close' );
	});
	
	// Press ENTER to bring focus on OK button
	$j( '.bookacti-form-dialog' ).on( 'keydown', function( e ) {
		if( ! $j( 'textarea' ).is( ':focus' ) && e.keyCode == $j.ui.keyCode.ENTER ) {
			$j( this ).parent().find( '.ui-dialog-buttonpane button:first' ).focus(); 
			return false; 
		}
	});	
}


// Initialize form editor actions
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
	});
}


// DIALOGS

// Update Form Meta
function bookacti_dialog_update_form_meta() {
	
	var form_id	= $j( '#bookacti-form-id' ).val();
	
	// Fill field id
	$j( 'form#bookacti-update-form-meta-form input[name="action"]' ).val( 'bookactiUpdateFormMeta' );
	$j( 'form#bookacti-update-form-meta-form input[name="form_id"]' ).val( form_id );
	
	// Fill the fields with current data
	bookacti_fill_fields_from_array( bookacti.form_editor.form, '', 'form#bookacti-update-form-meta-form' );
	
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
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				
				// Save tineMCE editors content 
				if( tinyMCE ) { tinyMCE.triggerSave(); }
				
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
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_form_meta_updated' );
							
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


// Insert form field
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
				
				// Display a loader
				bookacti_form_editor_enter_loading_state();
				
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
							$j( '#bookacti-form-editor' ).append( response.field_html );
							
							// Update the field data
							bookacti.form_editor.fields[ response.field_id ] = response.field_data;
							bookacti.form_editor.form.field_order = response.field_order;
							
							// Prevent this field from being inserted again (if unique)
							$j( '#bookacti-field-to-insert option[value="' + field_name + '"][data-unique="1"]' ).attr( 'disabled', true );
							$j( '#bookacti-field-to-insert' ).val( $j( '#bookacti-field-to-insert option:not([disabled]):first' ).val() );
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_inserted', [ response.field_id ] );
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_insert_form_field;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_insert_form_field );
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


// Remove form field
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
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_removed', [ field_id ] );
							
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


// Update Form Field
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
				
				// Save tineMCE editors content 
				if( tinyMCE ) { tinyMCE.triggerSave(); }
				
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
							$j( '#bookacti-form-editor-field-' + field_id ).replaceWith( response.field_html );
							
							// Update the field data
							bookacti.form_editor.fields[ field_id ] = response.field_data;
							
							// Reload tooltip for generated content
							bookacti_init_tooltip();
							
							// Toogle field
							if( is_visible ) { $j( '#bookacti-form-editor-field-' + field_id + ' .bookacti-form-editor-field-header' ).trigger( 'click' ); }
							
							$j( '#bookacti-form-editor' ).trigger( 'bookacti_field_updated', [ field_id ] );
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_update_form_field;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_form_field );
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