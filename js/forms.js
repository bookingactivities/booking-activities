$j( document ).ready( function() {
	// Load this file only on form editor page
	if( ! $j( '#bookacti-booking-form' ).length ) { return; }
	
	// Add / remove form managers
	bookacti_init_add_and_remove_items();
	
	// Save a form (create or update)
	$j( '#bookacti-booking-form' ).on( 'submit', function( e ) {
		e.preventDefault();
		// Select all form managers
		$j( '#bookacti-form-managers-select-box option' ).attr( 'selected', true );
		
		var form	= $j( this );
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
						$j( '#bookacti-form-editor-container' ).before( '<div class="notice notice-success is-dismissible bookacti-form-notice" ><p>' + response.message + '</p></div>' );
					}
					
				} else if( response.status === 'failed' ) {
					var error_message = action === 'bookactiInsertForm' ? bookacti_localized.error_create_form : bookacti_localized.error_update_form;
					if( response.error === 'not_allowed' ) {
						error_message += '\n' + bookacti_localized.error_not_allowed;
					}
					
					// Display feedback
					$j( '#bookacti-form-editor-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );
				
					console.log( response );
				}
			},
			error: function( e ){
				var error_message = action === 'bookactiInsertForm' ? 'AJAX ' + bookacti_localized.error_create_form : 'AJAX ' + bookacti_localized.error_update_form;
				
				// Display feedback
				$j( '#bookacti-form-editor-container' ).before( '<div class="notice notice-error is-dismissible bookacti-form-notice" ><p>' + error_message + '</p></div>' );
				
				console.log( e );
			},
			complete: function() { 
				// Stop the spinner
				$j( '#publishing-action .spinner' ).css( 'visibility', 'hidden' );
			}
		});
	});
});