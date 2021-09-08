$j( document ).ready( function() {
	// Init the Dialogs
	bookacti_init_form_dialogs();
	
	// Init tooltip on frontend booking forms
	bookacti_init_tooltip();
	
	/**
	 * Check password strength
	 * @version 1.8.8
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-booking-form input[name=password], .bookacti-form-fields input[name=password]', function() {
		var password_field			= $j( this );
		var password_confirm_field	= null;
		var password_strength_meter	= $j( this ).closest( '.bookacti-form-field-container' ).find( '.bookacti-password-strength-meter' );
		var forbidden_words			= [];
		var login_type				= password_field.closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( 'input[name="login_type"]:checked' ).val();
		
		if( password_strength_meter.length && login_type === 'new_account' ) {
			var pwd_strength = bookacti_check_password_strength( password_field, password_confirm_field, password_strength_meter, forbidden_words );
			$j( this ).closest( '.bookacti-form-field-container' ).find( 'input[name=password_strength]' ).val( pwd_strength );
		} else {
			password_field.removeClass( 'short bad good strong' );
		}
	});
	
	// Forgotten password dialog
	$j( 'body' ).on( 'click', '.bookacti-forgotten-password-link', function( e ) {
		e.preventDefault();
		var field_id = $j( this ).data( 'field-id' );
		bookacti_dialog_forgotten_password( field_id );
	});
	
	
	/**
	 * Login type: Show / Hide register fields on load
	 * @since 1.5.0
	 * @version 1.6.0
	 */
	$j( '.bookacti-form-field-container.bookacti-form-field-type-login' ).each( function(){
		bookacti_show_hide_register_fields( $j( this ) );
	});
	
	
	/**
	 * Login type: Show / Hide register fields on change
	 * @since 1.5.0
	 * @version 1.6.0
	 */
	$j( 'body' ).on( 'change', '.bookacti-form-field-container.bookacti-form-field-type-login input[name="login_type"]', function() {
		var login_field_container = $j( this ).closest( '.bookacti-form-field-container.bookacti-form-field-type-login' );
		bookacti_show_hide_register_fields( login_field_container );
	});
	
	
	/**
	 * Login submit: Login / Register on submit
	 * @since 1.8.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '.bookacti-form-field-login-field-container .bookacti-login-button', function( e ) {
		// Prevent form submission
		e.preventDefault();
		
		// Check if the user is already logged in
		if( typeof bookacti_localized.current_user_id !== 'undefined' ) {
			if( bookacti_localized.current_user_id ) { return; }
		}
		
		// Submit login form
		bookacti_submit_login_form( $j( this ) );
	});
	
	
	/**
	 * Perform the desired action on form submission
	 * @version 1.9.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'submit', '.bookacti-booking-form', function( e ) {
		// Prevent submission
		e.preventDefault();
		
		// Retrieve the info required to show the desired events
		var booking_system = $j( this ).find( '.bookacti-form-field-type-calendar .bookacti-booking-system' );

		// Perform action defined in Calendar field settings
		bookacti_perform_form_action( booking_system );
		
		$j( this ).trigger( 'bookacti_submit_booking_form' );
	});
	
	
	/**
	 * Display booking system fields and submit button if the user want to make a new booking
	 * @version 1.8.0
	 */
	$j( 'body' ).on( 'click', '.bookacti-booking-form .bookacti-new-booking-button', function() {
		// Reload page if necessary
		if( $j( this ).hasClass( 'bookacti-reload-page' ) ) { 
			window.location.reload( true ); 
			$j( this ).prop( 'disabled', true ); 
			return; 
		}
		
		var form = $j( this ).closest( 'form' );
		var booking_system	= form.find( '.bookacti-booking-system' );
		
		// Clear booking system displayed info
		bookacti_clear_booking_system_displayed_info( booking_system );
		
		// Clear form feedback messages
		var error_div = form.find( '> .bookacti-notices' ).length ? form.find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
		error_div.empty();
		
		// Display form fields and submit button, and then, hide the "Make a new booking" button
		form.find( '.bookacti-form-field-container:not(.bookacti-hidden-field), input[type="submit"]' ).show();
		$j( this ).hide();
		
		form.trigger( 'bookacti_make_new_booking' );
	});
	
	
	/**
	 * Change activity summary on qty change
	 * @version 1.11.3
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-booking-form input.bookacti-quantity, .bookacti-form-fields input.bookacti-quantity', function() {
		var booking_system = $j( this ).closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_set_min_and_max_quantity( booking_system );
			bookacti_fill_picked_events_list( booking_system );
		}
	});
	
	
	/**
	 * Enable submit booking button
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', '.bookacti-booking-form .bookacti-booking-system, .bookacti-form-fields .bookacti-booking-system', function() {
		var booking_form = $j( this ).closest( '.bookacti-booking-form, .bookacti-form-fields' );
		booking_form.find( 'input[name="quantity"]' ).attr( 'disabled', false );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	/**
	 * Disable submit booking button
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'bookacti_error_displayed', '.bookacti-booking-form .bookacti-booking-system, .bookacti-form-fields .bookacti-booking-system', function() {
		var booking_form = $j( this ).closest( '.bookacti-booking-form, .bookacti-form-fields' );
		booking_form.find( 'input[name="quantity"]' ).attr( 'disabled', true );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', true );
	});
});


/**
 * Initialize form dialogs
 * @version 1.7.0
 */
function bookacti_init_form_dialogs() {
	//Common param
	$j( '.bookacti-form-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		460,
		"resize":		'auto',
		"show":			true,
		"hide":			true,
		"dialogClass":	'bookacti-dialog',
		"closeText":	'&#10006;',
		"beforeClose":	function() { 
			if( ! bookacti_localized.is_admin ) { return; }
			var scope = '.bookacti-form-dialog';
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
	$j( '.bookacti-form-dialog' ).on( 'keydown', function( e ) {
		if( ! $j( 'textarea' ).is( ':focus' ) && e.keyCode == $j.ui.keyCode.ENTER ) {
			$j( this ).parent().find( '.ui-dialog-buttonpane button:first' ).focus(); 
			return false; 
		}
	});
}


/**
 * Display or hide the register fields according to the login type value
 * @since 1.5.0
 * @version 1.8.0
 * @param {HTMLElement} login_field_container
 */
function bookacti_show_hide_register_fields( login_field_container ) {
	var login_type			= login_field_container.find( 'input[name="login_type"]:checked' ).val();
	var password_strength	= login_field_container.find( '.bookacti-password-strength' );
	var password_forgotten	= login_field_container.find( '.bookacti-forgotten-password' );
	var password_field		= login_field_container.find( '.bookacti-login-field-password' );
	var register_fieldset	= login_field_container.find( '.bookacti-register-fields' );
	var login_button		= login_field_container.find( '.bookacti-login-button' );
	var button_container	= login_field_container.find( '.bookacti-login-field-submit-button' );
	if( login_type === 'new_account' ) { 
		password_strength.show(); 
		password_forgotten.hide(); 
		if( password_field.hasClass( 'bookacti-generated-password' ) ) {
			password_field.hide(); 
			password_field.find( 'input[name="password"]' ).prop( 'required', false );
		} else {
			password_field.show();
			password_field.find( 'input[name="password"]' ).prop( 'required', true );
		}
		register_fieldset.show(); 
		register_fieldset.find( '.bookacti-required-field' ).prop( 'required', true );
		login_button.val( login_button.data( 'register-label' ) ).prop( 'disabled', false );
		button_container.show();
	} else if( login_type === 'my_account' ) { 
		password_strength.hide(); 
		password_forgotten.show(); 
		if( password_field.hasClass( 'bookacti-password-not-required' ) ) {
			password_field.hide();
			password_field.find( 'input[name="password"]' ).prop( 'required', false );
		} else {
			password_field.show();
			password_field.find( 'input[name="password"]' ).prop( 'required', true );
		}
		register_fieldset.hide(); 
		register_fieldset.find( '.bookacti-required-field' ).prop( 'required', false );
		login_button.val( login_button.data( 'login-label' ) ).prop( 'disabled', false );
		button_container.show();
	} else if( login_type === 'no_account' ) { 
		password_field.hide();
		password_field.find( 'input[name="password"]' ).prop( 'required', false );
		register_fieldset.show(); 
		register_fieldset.find( '.bookacti-required-field' ).prop( 'required', true );
		login_button.prop( 'disabled', true );
		button_container.hide();
	}
}


/**
 * Get password strength and display a password strength meter
 * @since 1.5.0
 * @version 1.12.0
 * @param {HTMLElement} password_field
 * @param {HTMLElement} password_confirm_field
 * @param {HTMLElement} password_strength_meter
 * @param {array} forbidden_words
 * @returns {int}
 */
function bookacti_check_password_strength( password_field, password_confirm_field, password_strength_meter, forbidden_words ) {
	if( typeof window.zxcvbn === 'undefined' || typeof wp.passwordStrength === 'undefined' || typeof pwsL10n === 'undefined' ) { return 4; }
	
	var pwd = password_field.val();
	var confirm_pwd = password_confirm_field != null ? password_confirm_field.val() : pwd;
	
	// extend the forbidden words array with those from the site data (Backward Compatibility)
	forbidden_words = typeof wp.passwordStrength.userInputDisallowedList === 'function' ? forbidden_words.concat( wp.passwordStrength.userInputDisallowedList() ) : forbidden_words.concat( wp.passwordStrength.userInputBlacklist() );

	// reset the strength meter status
	password_field.removeClass( 'short bad good strong' );
	password_strength_meter.removeClass( 'short bad good strong' );

	// calculate the password strength
	var pwd_strength = wp.passwordStrength.meter( pwd, forbidden_words, confirm_pwd );
	
	// check the password strength
	switch( pwd_strength ) {
		case 2:
			password_field.addClass( 'bad' );
			password_strength_meter.addClass( 'bad' ).html( pwsL10n[ 'bad' ] );
			break;
		case 3:
			password_field.addClass( 'good' );
			password_strength_meter.addClass( 'good' ).html( pwsL10n[ 'good' ] );
			break;
		case 4:
			password_field.addClass( 'strong' );
			password_strength_meter.addClass( 'strong' ).html( pwsL10n[ 'strong' ] );
			break;
		case 5:
			password_field.addClass( 'short' );
			password_strength_meter.addClass( 'short' ).html( pwsL10n[ 'mismatch' ] );
			break;
		default:
			password_field.addClass( 'short' );
			password_strength_meter.addClass( 'short' ).html( pwsL10n[ 'short' ] );
	}

	return pwd_strength;
}


/**
 * Submit login form
 * @since 1.8.0
 * @version 1.12.2
 * @param {HTMLElement} submit_button
 */
function bookacti_submit_login_form( submit_button ) {
	// Check if the login field container exists
	if( ! submit_button.closest( '.bookacti-form-field-container' ).length ) { return; }
	if( ! submit_button.closest( '.bookacti-form-field-container' ).find( '.bookacti-email' ).length ) { return; }
	var field_container = submit_button.closest( '.bookacti-form-field-container' );
	
	// Temporarily disable the submit button
	submit_button.prop( 'disabled', true );
	
	// Find the closest form or create a temporary form
	if( ! submit_button.closest( 'form' ).length ) {
		if( field_container.closest( '.bookacti-form-fields' ).length ) {
			field_container.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' );
		} else {
			field_container.wrap( '<form class="bookacti-temporary-form"></form>' );
		}
	}
	var form = submit_button.closest( 'form' );
	
	// Find the error div or create a temporary one
	if( ! form.find( '> .bookacti-notices' ).length ) { form.append( '<div class="bookacti-notices"></div>' ); }
	var error_div = form.find( '> .bookacti-notices' );
	error_div.empty();
	
	// Check password strength
	if( form.find( 'input[name="login_type"][value="new_account"]' ).is( ':checked' ) 
	&& ! form.find( '.bookacti-generated-password' ).length
	&& parseInt( form.find( '.bookacti-password_strength' ).val() ) < parseInt( form.find( '.bookacti-password_strength' ).attr( 'min' ) ) ) {
		// Display the error message
		error_div.append( '<ul class="bookacti-error-list"><li>' + bookacti_localized.error_password_not_strong_enough + '</li></ul>' ).show();
		// Re-enable the submit button
		submit_button.prop( 'disabled', false );
		// Scroll to error message
		bookacti_scroll_to( error_div, 500, 'middle' );
		return;
	}
	
	// Change form action field value
	var has_form_action = form.find( 'input[name="action"]' ).length;
	var old_form_action = has_form_action ? form.find( 'input[name="action"]' ).val() : '';
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( 'bookactiSubmitLoginForm' ); } 
	else { form.append( '<input type="hidden" name="action" value="bookactiSubmitLoginForm"/>' ); }
	
	// Get form field values
	var data = { 'form_data': new FormData( form.get(0) ) };
	
	// Trigger action before sending form
	field_container.trigger( 'bookacti_before_submit_login_form', [ data ] );
	
	// Restore form action field value
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( old_form_action ); } 
	else { form.find( 'input[name="action"]' ).remove(); }
	
	if( ! ( data.form_data instanceof FormData ) ) { 
		// Re-enable the submit button
		submit_button.prop( 'disabled', false );
		return false;
	}
	
	// Display a loader after the submit button too
	var loading_div = '<div class="bookacti-loading-alt">' 
					+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
				+ '</div>';
	submit_button.after( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data.form_data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ) {
			// Display feedback message
			var message = response.message ? response.message : ( response.messages ? response.messages : '' );
			if( message ) {
				var list_class = response.status === 'success' ? 'bookacti-success-list' : 'bookacti-error-list';
				var list = '<ul class="' + list_class + '"><li>' + message + '</li></ul>';
				error_div.append( list ).show();
				bookacti_scroll_to( error_div, 500, 'middle' );
			}
			
			if( response.status === 'success' ) {
				// Trigger action after sending form
				form.trigger( 'bookacti_login_form_submitted', [ response, data ] );

				// Redirect
				// Do not serialize user data
				form.find( '.bookacti-form-field-name-login :input' ).prop( 'disabled', true );
				var url_params = form.serialize();
				form.find( '.bookacti-form-field-name-login :input' ).prop( 'disabled', false );
				var form_redirect_url = typeof form.attr( 'action' ) !== 'undefined' && old_form_action === 'bookactiSubmitLoginForm' ? form.attr( 'action' ) : '';
				var redirect_url = response.redirect_url ? response.redirect_url : form_redirect_url;
				redirect_url += redirect_url.indexOf( '?' ) >= 0 ? '&' + url_params : '?' + url_params;
				window.location.replace( redirect_url );
			}
		},
		error: function( e ){
			// Fill error message
			var message = '<ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul>';
			error_div.empty().append( message ).show();
			
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			// Re-enable the submit button
			submit_button.prop( 'disabled', false );
			// Stop loading
			submit_button.next( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Submit booking form
 * @since 1.7.6 (was bookacti_sumbit_booking_form)
 * @version 1.12.2
 * @param {HTMLElement} form
 */
function bookacti_submit_booking_form( form ) {
	var booking_system = form.find( '.bookacti-booking-system' );
	
	// Disable the submit button to avoid multiple booking
	var submit_button = form.find( 'input[type="submit"]' ).length ? form.find( 'input[type="submit"]' ) : null;
	if( submit_button.length ) { submit_button.prop( 'disabled', true ); }
	
	// Use the error div of the booking system by default, or if possible, the error div of the form
	var error_div = form.find( '> .bookacti-notices' ).length ? form.find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
	
	// Check if user is logged in
	var is_logged_in = false;
	if( typeof bookacti_localized.current_user_id !== 'undefined' ) {
		if( bookacti_localized.current_user_id ) { is_logged_in = true; }
	}
	var are_login_fields = form.find( '.bookacti-email' ).length ? true : false;
	
	if( ! is_logged_in && ! are_login_fields ) {
		// Display the error message
		error_div.empty().append( '<ul class="bookacti-error-list"><li>' + bookacti_localized.error_user_not_logged_in + '</li></ul>' ).show();
		// Re-enable the submit button
		if( submit_button.length ) { submit_button.prop( 'disabled', false ); }
		// Scroll to error message
		bookacti_scroll_to( error_div, 500, 'middle' );
		return false;
	}
	
	// Check password strength
	if( are_login_fields ) {
		if( form.find( 'input[name="login_type"][value="new_account"]' ).is( ':checked' ) 
		&& ! form.find( '.bookacti-generated-password' ).length
		&& parseInt( form.find( '.bookacti-password_strength' ).val() ) < parseInt( form.find( '.bookacti-password_strength' ).attr( 'min' ) ) ) {
			// Display the error message
			error_div.empty().append( "<ul class='bookacti-error-list'><li>" + bookacti_localized.error_password_not_strong_enough + "</li></ul>" ).show();
			// Re-enable the submit button
			if( submit_button.length ) { submit_button.prop( 'disabled', false ); }
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
			return false; // End script	
		}
	}
	
	// Check if selected event is valid
	var is_valid_event = bookacti_validate_picked_events( booking_system, form.find( '.bookacti-quantity' ).val() );
	if( ! is_valid_event ) {
		// Scroll to error message
		bookacti_scroll_to( booking_system.siblings( '.bookacti-notices' ), 500, 'middle' );
		// Re-enable the submit button
		if( submit_button.length ) { submit_button.prop( 'disabled', false ); }
		return false; // End script
	}

	// Change form action field value
	var has_form_action = form.find( 'input[name="action"]' ).length;
	var old_form_action = has_form_action ? form.find( 'input[name="action"]' ).val() : '';
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( 'bookactiSubmitBookingForm' ); } 
	else { form.append( '<input type="hidden" name="action" value="bookactiSubmitBookingForm"/>' ); }
	
	// Get form field values
	var data = { 'form_data': new FormData( form.get(0) ) };
	
	// Trigger action before sending form
	form.trigger( 'bookacti_before_submit_booking_form', [ data ] );
	
	// Restore form action field value
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( old_form_action ); } 
	else { form.find( 'input[name="action"]' ).remove(); }
	
	if( ! ( data.form_data instanceof FormData ) ) { 
		// Re-enable the submit button
		if( submit_button.length ) { submit_button.prop( 'disabled', false ); }
		return false;
	}
	
	// Display a loader after the submit button too
	if( submit_button.length ) { 
		var loading_div = '<div class="bookacti-loading-alt">' 
						+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
						+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
		submit_button.after( loading_div );
	}
	
	bookacti_start_loading_booking_system( booking_system );

	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data.form_data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ) {
			var redirect_url = typeof response.redirect_url !== 'undefined' ? response.redirect_url : '';
			
			var message = '';
			if( response.status !== 'success' ) {
				message = "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>";
				
			} else {
				// Hide fields and submit button to avoid duplicated bookings
				form.find( '.bookacti-form-field-container:not(.bookacti-form-field-name-submit):not(.bookacti-form-field-name-calendar), input[type="submit"]' ).hide();

				// Show a "Make a new booking" button to avoid refreshing the page to make a new booking
				if( response.has_logged_in ) { form.find( '.bookacti-new-booking-button' ).addClass( 'bookacti-reload-page' ); }
				else { form.find( '.bookacti-new-booking-button' ).removeClass( 'bookacti-reload-page' ); }
				form.find( '.bookacti-new-booking-button' ).show();

				message = "<ul class='bookacti-success-list bookacti-persistent-notice'><li>" + response.message + "</li></ul>";

				// Reload booking numbers if page is not reloaded
				if( redirect_url.indexOf( '://' ) < 0 ) {
					bookacti_refresh_booking_numbers( booking_system );
				}
			}
			
			// Update nonce
			if( typeof response.nonce !== 'undefined' ) {
				if( response.nonce ) { form.find( 'input[name="nonce_booking_form"]' ).val( response.nonce ); }
			}
		
			// Display feedback message
			if( message ) {
				// Fill error message
				error_div.empty().append( message ).show();
				if( ! redirect_url ) {
					// Scroll to error message
					bookacti_scroll_to( error_div, 500, 'middle' );
				}
			}
			
			// Make form data readable
			var form_data_object = form.serializeObject();
			
			// Trigger action after sending form
			form.trigger( 'bookacti_booking_form_submitted', [ response, form_data_object ] );
			
			// Redirect
			if( response.status === 'success' && redirect_url ) {
				bookacti_start_loading_booking_system( booking_system );
				window.location.replace( redirect_url );
				bookacti_stop_loading_booking_system( booking_system );
			}
			
		},
		error: function( e ){
			// Fill error message
			var message = '<ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul>';
			error_div.empty().append( message ).show();
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() { 
			if( submit_button.length ) { 
				submit_button.next( '.bookacti-loading-alt' ).remove();
				// Re-enable the submit button
				submit_button.prop( 'disabled', false );
			}
			bookacti_stop_loading_booking_system( booking_system );
		}
	});	
}


/**
 * Perform form action
 * @since 1.9.0
 * @version 1.10.1
 * @param {HTMLElement} booking_system
 */
function bookacti_perform_form_action( booking_system ) {
	var trigger = { trigger: true };
	
	// Do not perform form actions on form editor
	if( booking_system.closest( '#bookacti-form-editor-page-form' ).length ) { trigger.trigger = false; }
	
	booking_system.trigger( 'bookacti_trigger_perform_form_action', [ trigger ] );
	if( ! trigger.trigger ) { return; }
	
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'form_action' ] === 'undefined' ) { return; }
	
	// Default: Send the booking form
	if( attributes[ 'form_action' ] === 'default' ) {
		if( ! booking_system.closest( 'form' ).length && booking_system.closest( '.bookacti-form-fields' ).length ) {
			booking_system.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' );
		}
		if( booking_system.closest( 'form.bookacti-booking-form' ).length || booking_system.closest( 'form.bookacti-temporary-form' ).length ) {
			bookacti_submit_booking_form( booking_system.closest( 'form' ) );
		}
	}
	
	// Redirect to URL
	else if( attributes[ 'form_action' ] === 'redirect_to_url' ) {
		// Check if selected event is valid
		var quantity = booking_system.closest( '.bookacti-form-fields' ).length ? ( booking_system.closest( '.bookacti-form-fields' ).find( '.bookacti-quantity' ).length ? booking_system.closest( '.bookacti-form-fields' ).find( '.bookacti-quantity' ).val() : 1 ) : 1;
		var is_valid_event = bookacti_validate_picked_events( booking_system, quantity );
		if( ! is_valid_event ) {
			// Scroll to error message
			bookacti_scroll_to( booking_system.siblings( '.bookacti-notices' ), 500, 'middle' );
			return;
		}
		
		var group_id = parseInt( attributes[ 'picked_events' ][ 0 ][ 'group_id' ] );
		var event = attributes[ 'picked_events' ][ 0 ];

		if( group_id > 0 ) {
			bookacti_redirect_to_group_category_url( booking_system, group_id );
		} else {
			bookacti_redirect_to_activity_url( booking_system, event );
		}
	}
	
	booking_system.trigger( 'bookacti_perform_form_action' );
}


/**
 * Forgotten password dialog
 * @since 1.5.0
 * @version 1.8.0
 * @param {string} field_id
 */
function bookacti_dialog_forgotten_password( field_id ) {
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
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: { 
						'action': 'bookactiForgottenPassword',
						'email': email,
						'nonce': nonce
					},
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							if( typeof response.message !== 'undefined' ) {
								dialog.append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' );
							}
							
							$j( 'body' ).trigger( 'bookacti_forgotten_password_email_sent', [ email, response ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							dialog.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						dialog.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
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