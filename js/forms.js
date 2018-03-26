$j( document ).ready( function() {
	// Load this file only on form editor page
	if( ! $j( 'form#bookacti-form-editor-page-form' ).length ) { return; }
	
	// Add / remove form managers
	bookacti_init_add_and_remove_items();
	
	// Minimize / Maximize field
	$j( '.bookacti-form-editor-field-header' ).on( 'click', function() {
		var icon = $j( this ).find( '.bookacti-field-toggle' );
		icon.toggleClass( 'dashicons-arrow-up dashicons-arrow-down' );
		icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-form-editor-field-body' ).toggle();
		
		var is_visible		= icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-form-editor-field-body' ).is( ':visible' );
		var booking_system	= icon.closest( '.bookacti-form-editor-field' ).find( '.bookacti-booking-system' );
		if( is_visible && booking_system.length ) {
			bookacti_booking_method_rerender_events( booking_system );
		}
    });
	
	// Open edit field dialog box
	$j( '.bookacti-edit-form-field' ).on( 'click', function( e ) {
		e.stopPropagation();
	});
	
	// Open delete field dialog box
	$j( '.bookacti-remove-form-field' ).on( 'click', function( e ) {
		e.stopPropagation();
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
		e.preventDefault();
		bookacti_save_form( $j( this ) );
	});
});


/**
 * Save form data
 * @since 1.5.0
 * @param html_element form
 */
function bookacti_save_form( form ) {
	// Select all form managers
	$j( '#bookacti-form-managers-select-box option' ).attr( 'selected', true );
	
	// Serialize form values
	var action	= form.find( 'input[name="action"]' ).val();
	var data	= form.serialize();

	// Display spinner
	$j( '#publishing-action .spinner' ).css( 'visibility', 'visible' );

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
				// If newly created, go to edit page
				if( action === 'bookactiInsertForm' ) {
					window.location.replace( form.attr( 'action' ) + '&action=edit&form_id=' + response.form_id + '&notice=created' );

				// If on edit page, display feedback
				} else {
					$j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-success is-dismissible bookacti-form-notice" ><p>' + response.message + '</p></div>' );
				}

			} else if( response.status === 'failed' ) {
				var error_message = action === 'bookactiInsertForm' ? bookacti_localized.error_create_form : bookacti_localized.error_update_form;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}

				// Display feedback
				$j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );

				console.log( response );
			}
		},
		error: function( e ){
			var error_message = action === 'bookactiInsertForm' ? 'AJAX ' + bookacti_localized.error_create_form : 'AJAX ' + bookacti_localized.error_update_form;

			// Display feedback
			$j( '#bookacti-form-editor-page-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );

			console.log( e );
		},
		complete: function() { 
			// Stop the spinner
			$j( '#publishing-action .spinner' ).css( 'visibility', 'hidden' );
		}
	});
}


/**
 * Save field order
 */
function bookacti_save_form_field_order() {
	var form_id = $j( '#bookacti-form-id' ).val();
	
	if( ! $j.isNumeric( form_id ) ) { return; }
	
	// Get field in document order
	var field_order = [];
	$j( '.bookacti-form-editor-field' ).each( function(){
		field_order.push( $j( this ).data( 'field-name' ) );
	});
	
	if( ! field_order.length ) { return; }
	
	var nonce = $j( '#bookacti_nonce_form_field_order' ).val();
	var data = {
			'action': 'bookactiSaveFormFieldOrder',
			'form_id': form_id,
			'field_order': field_order,
			'nonce': nonce
		};
	console.log( data );
	
	// Save the new field order in database
	$j.ajax({
		url: ajaxurl, 
		data: data, 
		type: 'POST',
		dataType: 'json',
		success: function( response ){

			// Remove current notices about the form
			$j( '.bookacti-form-notice' ).remove();

			if( response.status === 'success' ) {
				

			} else if( response.status === 'failed' ) {
				var error_message = bookacti_localized.error_order_form_fields;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}

				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX ' + bookacti_localized.error_order_form_fields;

			console.log( e );
		},
		complete: function() { 
			// Stop the spinner
			$j( '#publishing-action .spinner' ).css( 'visibility', 'hidden' );
		}
	});
}