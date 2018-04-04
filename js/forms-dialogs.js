$j( document ).ready( function() {
	// Load this file only on form editor page
	if( ! $j( 'form#bookacti-form-editor-page-form' ).length ) { return; }
	
	// Init the Dialogs
	bookacti_init_form_editor_dialogs();

	// Init booking actions
	bookacti_init_form_editor_actions();
});


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
		"close":		function() {}
	});
	
	// Make dialogs close when the user click outside
	$j( '.ui-widget-overlay' ).live( 'click', function (){
		$j( 'div:ui-dialog:visible' ).dialog( 'close' );
	});

	// Individual params
	$j( '#bookacti-insert-form-field-dialog' ).dialog({ 
		title: bookacti_localized.form_field_action_insert
	});
	$j( '#bookacti-remove-form-field-dialog' ).dialog({ 
		title: bookacti_localized.form_field_action_remove
	});
}


// Initialize form editor actions
function bookacti_init_form_editor_actions() {
	// Open form dialog boxes
	$j( '#bookacti-form-editor-actions' ).on( 'click', '.bookacti-form-editor-action', function( e ) {
		e.stopPropagation();
		if( $j( this ).is( '#bookacti-insert-form-field' ) ){
			bookacti_dialog_insert_form_field();
		}
	});
	
	// Open form field dialog boxes
	$j( '#bookacti-form-editor' ).on( 'click', '.bookacti-form-editor-field-action', function( e ) {
		e.stopPropagation();
		var field_id = $j( this ).closest( '.bookacti-form-editor-field' ).data( 'field-id' );
		if( $j( this ).hasClass( 'bookacti-remove-form-field' ) ){
			bookacti_dialog_remove_form_field( field_id );
		}
	});
}


// DIALOGS

// Insert form field
function bookacti_dialog_insert_form_field() {
	
	// Open the modal dialog
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'open' );
	
	// Add the buttons
    $j( '#bookacti-insert-form-field-dialog' ).dialog( 'option', 'buttons',
		// Cancel button    
		[{
            text: bookacti_localized.dialog_button_cancel,
            click: function() { $j( this ).dialog( 'close' ); }
        },
		// OK button
		{
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
							
							$j( '#bookacti-form-editor' ).append( response.field_html );
							
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
function bookacti_dialog_remove_form_field( field_id ) {
	
	// Open the modal dialog
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'open' );
	
	// Add the buttons
    $j( '#bookacti-remove-form-field-dialog' ).dialog( 'option', 'buttons',
		// Cancel button    
		[{
            text: bookacti_localized.dialog_button_cancel,
            click: function() { $j( this ).dialog( 'close' ); }
        },
		// OK button
		{
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
							
							$j( '#bookacti-form-editor-field-' + field_id ).remove();
							
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