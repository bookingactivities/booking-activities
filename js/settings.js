$j( document ).ready( function() {
	/**
	 * Intercept settings form submission
	 * @version 1.15.13
	 * @param {Event} e
	 */
	$j( 'form#bookacti-settings.bookacti_save_settings_with_ajax' ).on( 'submit', function( e ) {
		// Prevent submission
		e.preventDefault();
		
		// Save tineMCE editor content 
		if( typeof tinyMCE !== 'undefined' ) { 
			if( tinyMCE ) { tinyMCE.triggerSave(); }
		}
	
		var form = $j( this );
		var form_data = bookacti_serialize_object( form );
		
		bookacti_add_loading_html( form );
		
		$j.ajax({
			url: bookacti_localized.ajaxurl,
			type: 'POST',
			data: form_data,
			dataType: 'json',
			success: function( response ) {
				if( response.status === 'success' ) {
					if( form.attr( 'action' ) ) {
						window.location.replace( form.attr( 'action' ) );
					} else {
						window.location.reload(); 
					}
				} else {
					bookacti_remove_loading_html( form );
					console.log( bookacti_localized.error );
					console.log( response );
				}
			},
			error: function( e ) {
				bookacti_remove_loading_html( form );
				console.log( 'AJAX ' + bookacti_localized.error );
				console.log( e );
			},
			complete: function() {}
		});	
		
	});
});