$j( document ).ready( function() {
	// Init the Dialogs
	bookacti_init_form_dialogs();
	
	// Forgotten password dialog
	// Open form dialog boxes
	$j( 'body' ).on( 'click', '.bookacti-forgotten-password-link', function( e ) {
		e.preventDefault();
		var field_id = $j( this ).data( 'field-id' );
		bookacti_dialog_forgotten_password( field_id );
	});
	
});


// Initialize form editor dialogs
function bookacti_init_form_dialogs() {
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


// Forgotten password dialog
function bookacti_dialog_forgotten_password( field_id ) {
	
	var link = $j( '.bookacti-forgotten-password-link[data-field-id="' + field_id + '"]' );
	var dialog = $j( '.bookacti-forgotten-password-dialog[data-field-id="' + field_id + '"]' );
	if( ! dialog.length ) { dialog = $j( '.bookacti-forgotten-password-dialog:first' ); }
	
	// Open the modal dialog
	dialog.dialog( 'open' );
	
	// Add the buttons
	dialog.dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,			
			click: function() { 
				
				// Clear feedbacks
				dialog.find( '.bookacti-loading-alt, .bookacti-notices' ).remove();
				
				var email = dialog.find( '.bookacti-forgotten-password-email' ).val();
				var nonce = dialog.find( '.bookacti-nonce-forgotten-password' ).val();
				
				if( ! email || ! nonce ) { return; }
				
				// Display a loader
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				dialog.append( loading_div );
				
				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiForgottenPassword',
							'email': email,
							'nonce': nonce
						},
					dataType: 'json',
					success: function( response ){
						
						if( response.status === 'success' ) {
							if( typeof response.message !== 'undefined' ) {
								dialog.append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' );
							}
							
							$j( 'body' ).trigger( 'bookacti_forgotten_password_email_sent', [ email, response ] );
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_send_email;
							if( response.error === 'not_allowed' ) {
								message_error += '<br/>' + bookacti_localized.error_not_allowed;
							}
							if( typeof response.message !== 'undefined' ) {
								message_error = response.message;
							}
							dialog.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + message_error + '</li></ul></div>' );
							console.log( response );
						}
						
					},
					error: function( e ){
						dialog.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error_send_email + '</li></ul></div>' );
						console.log( e );
					},
					complete: function() {
						dialog.find( '.bookacti-notices' ).show();
						dialog.find( '.bookacti-loading-alt' ).remove();
					}
				});
			}
		}]
    );
}