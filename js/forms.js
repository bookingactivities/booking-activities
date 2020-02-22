$j( document ).ready( function() {
	// Init the Dialogs
	bookacti_init_form_dialogs();
	
	// Init tooltip on frontend booking forms
	bookacti_init_tooltip();
	
	/**
	 * Check password strength
	 * @version 1.7.16
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-booking-form input[name=password], .bookacti-form-fields input[name=password]', function( e ) {
		var password_field			= $j( this );
		var password_confirm_field	= null;
		var password_strength_meter	= $j( this ).closest( '.bookacti-form-field-container' ).find( '.bookacti-password-strength-meter' );
		var blacklisted_words		= [];
		var login_type				= password_field.closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( 'input[name="login_type"]:checked' ).val();
		
		if( password_strength_meter.length && login_type === 'new_account' ) {
			var pwd_strength = bookacti_check_password_strength( password_field, password_confirm_field, password_strength_meter, blacklisted_words );
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
	$j( 'body' ).on( 'change', '.bookacti-form-field-container.bookacti-form-field-type-login input[name="login_type"]', function( e ){
		var login_field_container = $j( this ).closest( '.bookacti-form-field-container.bookacti-form-field-type-login' );
		bookacti_show_hide_register_fields( login_field_container );
	});
	
	
	/**
	 * Perform the desired action on form submission
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'submit', '.bookacti-booking-form', function( e ){
		// Prevent submission
		e.preventDefault();
		
		// Retrieve the info required to show the desired events
		var form				= $j( this );
		var booking_system		= form.find( '.bookacti-form-field-type-calendar .bookacti-booking-system' );
		var booking_system_id	= booking_system.attr( 'id' );
		if( typeof bookacti.booking_system[ booking_system_id ] === 'undefined' ) { return; }
		
		// Retrieve form action
		var attributes	= bookacti.booking_system[ booking_system_id ];
		var form_action = typeof attributes[ 'form_action' ] !== 'undefined';
		if( form_action ) { if( attributes[ 'form_action' ] ) { form_action = attributes[ 'form_action' ]; } }
		var when_perform_form_action = typeof attributes[ 'when_perform_form_action' ] !== 'undefined';
		if( when_perform_form_action ) { if( attributes[ 'when_perform_form_action' ] ) { when_perform_form_action = attributes[ 'when_perform_form_action' ]; } }
		
		if( ! form_action || form_action === 'default' 
		||  ! when_perform_form_action || when_perform_form_action !== 'on_submit' ) {
			bookacti_submit_booking_form( form );
			return;
		}
		
		// Redirect to activity / group category URL
		if( form_action === 'redirect_to_url' ) {
			var group_id	= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val();
			var event_id	= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val();
			var event_start	= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val();
			var event_end	= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val();
			
			// Redirect to activity URL if a single event is selected
			if( group_id === 'single' && event_id && event_start && event_end ) {
				var event = {
					'id': event_id,
					'start': event_start,
					'end': event_end
				};
				bookacti_redirect_to_activity_url( booking_system, event );
			}
			
			// Redirect to activity URL if a single event is selected
			else if( $j.isNumeric( group_id ) ) {
				bookacti_redirect_to_group_category_url( booking_system, group_id );
			}
		}
		
		$j( this ).trigger( 'bookacti_submit_booking_form' );
	});
	
	
	/**
	 * Display booking system fields and submit button if the user want to make a new booking
	 * @version 1.7.19
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
		
		// Display form fields and submit button, and then, delete the "Make a new booking" button
		form.find( '.bookacti-form-field-container:not(.bookacti-hidden-field), input[type="submit"]' ).show();
		$j( this ).remove();
		
		form.trigger( 'bookacti_make_new_booking' );
	});
	
	
	/**
	 * Change activity summary on qty change
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-booking-form input.bookacti-quantity, .bookacti-form-fields input.bookacti-quantity', function() {
		var booking_system = $j( this ).closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_fill_picked_events_list( booking_system );
		}
	});
	
	
	/**
	 * Set quantity on eventClick
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'bookacti_picked_events_list_data', '.bookacti-booking-form .bookacti-booking-system, .bookacti-form-fields .bookacti-booking-system', function( e, event_summary_data, event ) {
		var booking_system = $j( this );
		var qty_field = booking_system.closest( '.bookacti-booking-form, .bookacti-form-fields' ).find( 'input.bookacti-quantity' );
		if( qty_field.length ) {
			bookacti_set_min_and_max_quantity( booking_system, qty_field, event_summary_data );
		}
	});
	
	
	/**
	 * Enable submit booking button
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', '.bookacti-booking-form .bookacti-booking-system, .bookacti-form-fields .bookacti-booking-system', function( e ) {
		var booking_form = $j( this ).closest( '.bookacti-booking-form, .bookacti-form-fields' );
		booking_form.find( 'input[name="quantity"]' ).attr( 'disabled', false );
		booking_form.find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	/**
	 * Disable submit booking button
	 * @version 1.7.6
	 */
	$j( 'body' ).on( 'bookacti_error_displayed', '.bookacti-booking-form .bookacti-booking-system, .bookacti-form-fields .bookacti-booking-system', function( e ) {
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
 * @version 1.6.0
 */
function bookacti_show_hide_register_fields( login_field_container ) {
	var login_type			= login_field_container.find( 'input[name="login_type"]:checked' ).val();
	var password_strength	= login_field_container.find( '.bookacti-password-strength' );
	var password_forgotten	= login_field_container.find( '.bookacti-forgotten-password' );
	var password_field		= login_field_container.find( '.bookacti-login-field-password' );
	var register_fieldset	= login_field_container.find( '.bookacti-register-fields' );
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
	} else if( login_type === 'no_account' ) { 
		password_field.hide();
		password_field.find( 'input[name="password"]' ).prop( 'required', false );
		register_fieldset.show(); 
		register_fieldset.find( '.bookacti-required-field' ).prop( 'required', true );
	}
}


/**
 * Get password strength and display a password strength meter
 * @since 1.5.0
 * @since 1.7.16
 * @param {html_element} password_field
 * @param {html_element} password_confirm_field
 * @param {html_element} password_strength_meter
 * @param {array} blacklisted_words
 * @returns {int}
 */
function bookacti_check_password_strength( password_field, password_confirm_field, password_strength_meter, blacklisted_words ) {
	if( typeof window.zxcvbn === 'undefined' || typeof wp.passwordStrength === 'undefined' || typeof pwsL10n === 'undefined' ) { return 4; }
	
	var pwd = password_field.val();
	var confirm_pwd = password_confirm_field != null ? password_confirm_field.val() : pwd;
	
	// extend the blacklisted words array with those from the site data
	blacklisted_words = blacklisted_words.concat( wp.passwordStrength.userInputBlacklist() );

	// reset the strength meter status
	password_field.removeClass( 'short bad good strong' );
	password_strength_meter.removeClass( 'short bad good strong' );

	// calculate the password strength
	var pwd_strength = wp.passwordStrength.meter( pwd, blacklisted_words, confirm_pwd );
	
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
 * Submit booking form
 * @since 1.7.6 (was bookacti_sumbit_booking_form)
 * @version 1.7.20
 * @param {html_element} form
 * @returns {Boolean}
 */
function bookacti_submit_booking_form( form ) {
	var booking_system = form.find( '.bookacti-booking-system' );
	
	// Use the error div of the booking system by default, or if possible, the error div of the form
	var error_div = form.find( '> .bookacti-notices' ).length ? form.find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
	
	// Disable the submit button to avoid multiple booking
	var submit_button = form.find( 'input[type="submit"]' ).length ? form.find( 'input[type="submit"]' ) : null;
	if( submit_button ) { submit_button.prop( 'disabled', true ); }

	// Check if user is logged in
	var is_logged_in = false;
	if( typeof bookacti_localized.current_user_id !== 'undefined' ) {
		if( parseInt( bookacti_localized.current_user_id ) ) { is_logged_in = true; }
	}
	var are_login_fields = form.find( '.bookacti-email' ).length ? true : false;
	
	if( ! is_logged_in && ! are_login_fields ) {
		// Display the error message
		error_div.empty().append( "<ul class='bookacti-error-list'><li>" + bookacti_localized.error_user_not_logged_in + "</li></ul>" ).show();
		// Re-enable the submit button
		if( submit_button ) { submit_button.prop( 'disabled', false ); }
		// Scroll to error message
		bookacti_scroll_to( error_div, 500, 'middle' );
		return false; // End script		
	}
	
	// Check password strength
	if( are_login_fields ) {
		if( form.find( 'input[name="login_type"][value="new_account"]' ).is( ':checked' ) 
		&& ! form.find( '.bookacti-generated-password' ).length
		&& parseInt( form.find( '.bookacti-password_strength' ).val() ) < parseInt( form.find( '.bookacti-password_strength' ).attr( 'min' ) ) ) {
			// Display the error message
			error_div.empty().append( "<ul class='bookacti-error-list'><li>" + bookacti_localized.error_password_not_strong_enough + "</li></ul>" ).show();
			// Re-enable the submit button
			if( submit_button ) { submit_button.prop( 'disabled', false ); }
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
		if( submit_button ) { submit_button.prop( 'disabled', false ); }
		return false; // End script
	}

	var data = new FormData( form.get(0) );
	
	// Trigger action before sending form
	form.trigger( 'bookacti_before_submit_booking_form', [ data ] );
	
	// Set the form action
	if( data instanceof FormData ) {
		data.set( 'action', 'bookactiSubmitBookingForm' );
	} else {
		return;
	}
	
	// Display a loader after the submit button too
	if( submit_button ) { 
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
		data: data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ) {
			var redirect_url = form.attr( 'action' );
			if( typeof redirect_url !== 'undefined' ) {
				if( redirect_url === false || ! redirect_url ) { redirect_url = ''; }
			}
			
			var message = '';
			if( response.status !== 'success' ) {
				message = "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>";
				
			} else {
				// Hide fields and submit button to avoid duplicated bookings
				form.find( '.bookacti-form-field-container:not(.bookacti-form-field-name-submit):not(.bookacti-form-field-name-calendar), input[type="submit"]' ).hide();

				// Show a "Make a new booking" button to avoid refreshing the page to make a new booking
				var reload_page_class = '';
				if( response.has_logged_in ) { reload_page_class = 'bookacti-reload-page'; }
				form.find( '.bookacti-form-field-name-submit' ).append( '<input type="button" class="bookacti-new-booking-button ' + reload_page_class + '" value="' + bookacti_localized.booking_form_new_booking_button + '" />' );

				message = "<ul class='bookacti-success-list bookacti-persistent-notice'><li>" + response.message + "</li></ul>";

				if( ! redirect_url ) {
					// Reload booking numbers
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
				// Scroll to error message
				bookacti_scroll_to( error_div, 500, 'middle' );
			}
			
			// Make form data readable
			var form_data_object = {};
			if( typeof data.forEach === 'function' ) { 
				data.forEach( function( value, key ){
					form_data_object[ key ] = value;
				});
			} else {
				form_data_object = form.serializeObject();
			}
			
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
			var message = "<ul class='bookacti-error-list'><li>AJAX " + bookacti_localized.error_book + "</li></ul>";
			error_div.empty().append( message ).show();
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
			console.log( 'AJAX ' + bookacti_localized.error_book );
			console.log( e );
		},
		complete: function() { 
			if( submit_button ) { 
				submit_button.next( '.bookacti-loading-alt' ).remove();
				// Re-enable the submit button
				submit_button.prop( 'disabled', false );
			}
			bookacti_stop_loading_booking_system( booking_system );
		}
	});	
}


/**
 * Forgotten password dialog
 * @since 1.5.0
 * @version 1.6.0
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