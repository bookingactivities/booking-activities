$j( document ).ready( function() {
	// Load this file only on form editor page
	if( ! $j( 'form#bookacti-form-editor-page-form' ).length || typeof bookacti.form_editor === 'undefined' ) { return; }
	
	// Add / remove form managers
	bookacti_init_add_and_remove_items();
	
	// Init the Dialogs
	bookacti_init_form_editor_dialogs();

	// Init form editor actions
	bookacti_init_form_editor_actions();
	
	// Minimize / Maximize field
	$j( '#bookacti-form-editor' ).on( 'click', '.bookacti-form-editor-field-header', function( e ) {
		if( $j( e.target ).hasClass( 'bookacti-form-editor-field-action' ) ) { return; }
	
		var icon = $j( this ).find( '.bookacti-field-toggle' );
		icon.toggleClass( 'dashicons-arrow-up dashicons-arrow-down' );
		icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-form-editor-field-body' ).toggle();
		
		var is_visible		= icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-form-editor-field-body' ).is( ':visible' );
		var booking_system	= icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-booking-system' );
		if( is_visible && booking_system.length ) {
			bookacti_booking_method_rerender_events( booking_system );
		}
    });
	
	// Sort form fields in editor
	$j( '#bookacti-form-editor' ).sortable( { 
		items: '.bookacti-form-editor-field:not(.ui-state-disabled)',
		handle: '.bookacti-form-editor-field-header',
		placeholder: 'bookacti-form-editor-field-placeholder',
		update: function( e, ui ) { bookacti_save_form_field_order(); }
	});
	$j( '#bookacti-form-editor' ).disableSelection();
	
	// Save a form (create or update)
	$j( 'form#bookacti-form-editor-page-form' ).on( 'submit', function( e ) {
		if( ! $j( 'form#bookacti-form-editor-page-form' ).length ) { return; }
		e.preventDefault();
		bookacti_save_form();
	});
});


/**
 * Save form data
 * @since 1.5.0
 */
function bookacti_save_form() {
	// Select all form managers
	$j( '#bookacti-form-managers-select-box option' ).prop( 'selected', true );
	
	// Move form editor outside the <form> before serialize
	$j( '#bookacti-form-editor-container' ).appendTo( '#bookacti-form-editor-page-container' );
	
	// Serialize form values
	var form		= $j( 'form#bookacti-form-editor-page-form' );
	var is_active	= form.find( 'input[name="is_active"]' ).val();
	var data		= form.serialize();
	
	// Move form editor back inside the <form> after serialize
	$j( '#bookacti-form-editor-container' ).appendTo( '#postdivrich' );
	
	// Display spinner
	$j( '#publishing-action .spinner' ).css( 'visibility', 'visible' );
	bookacti_form_editor_enter_loading_state();

	// Save the new form in database
	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ){

			// Remove current notices about the form
			$j( '.bookacti-form-notice' ).remove();

			if( response.status === 'success' ) {
				
				$j( 'body' ).trigger( 'bookacti_form_updated' );
				
				// If the form was inactive, redirect
				if( is_active == 0 ) { window.location.replace( form.attr( 'action' ) + '&notice=published' ); }
				
				// Else, Display feedback
				else { $j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-success is-dismissible bookacti-form-notice" ><p>' + response.message + '</p></div>' ); }
				
			} else if( response.status === 'failed' ) {
				var error_message = bookacti_localized.error_update_form;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}

				// Display feedback
				$j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );

				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX ' + bookacti_localized.error_update_form;

			// Display feedback
			$j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );

			console.log( e );
		},
		complete: function() { 
			// Stop the spinner
			$j( '#publishing-action .spinner' ).css( 'visibility', 'hidden' );
			bookacti_form_editor_exit_loading_state();
		}
	});
}


/**
 * Save field order
 * @since 1.5.0
 */
function bookacti_save_form_field_order() {
	var form_id = $j( '#bookacti-form-id' ).val();
	
	if( ! $j.isNumeric( form_id ) ) { return; }
	
	// Get field in document order
	var field_order = [];
	$j( '.bookacti-form-editor-field' ).each( function(){
		field_order.push( $j( this ).data( 'field-id' ) );
	});
	
	if( ! field_order.length ) { return; }
	
	var nonce = $j( '#bookacti_nonce_form_field_order' ).val();
	var data = {
			'action': 'bookactiSaveFormFieldOrder',
			'form_id': form_id,
			'field_order': field_order,
			'nonce': nonce
		};
	
	bookacti_form_editor_enter_loading_state();
	
	// Save the new field order in database
	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ){
			
			if( response.status === 'success' ) {
				bookacti.form_editor.form.field_order = response.field_order;
				
				$j( '#bookacti-form-editor' ).trigger( 'bookacti_form_field_order_updated' );
				
			} else if( response.status === 'failed' ) {
				var error_message = bookacti_localized.error_order_form_fields;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX ' + bookacti_localized.error_order_form_fields;

			console.log( error_message );
			console.log( e );
		},
		complete: function() { bookacti_form_editor_exit_loading_state(); }
	});
}


function bookacti_form_editor_enter_loading_state() {
	$j( '.bookacti-form-editor-action, .bookacti-form-editor-field-action' ).addClass( 'bookacti-disabled' );
}


function bookacti_form_editor_exit_loading_state() {
	$j( '.bookacti-form-editor-action, .bookacti-form-editor-field-action' ).removeClass( 'bookacti-disabled' );
}