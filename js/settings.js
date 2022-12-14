$j( document ).ready( function() {
	/**
	 * Intercept settings form submission
	 * @version 1.15.6
	 * @param {Event} e
	 */
	$j( 'form#bookacti-settings.bookacti_save_settings_with_ajax' ).on( 'submit', function( e ) {
		// Prevent submission
		e.preventDefault();
		
		// Save tineMCE editor content 
		if( typeof tinyMCE !== 'undefined' ) { 
			if( tinyMCE ) { tinyMCE.triggerSave(); }
		}
	
		var form		= $j( this );
		var form_data	= form.serializeObject(); // Need to use the homemade serializeObject to support multidimentionnal array
		
		$j.ajax({
			url: bookacti_localized.ajaxurl,
			type: 'POST',
			data: form_data,
			dataType: 'json',
			success: function( response ){
				if( response.status === 'success' ) {
					if( form.attr( 'action' ) ) {
						window.location.replace( form.attr( 'action' ) );
					} else {
						window.location.reload(); 
					}
				} else {
					console.log( bookacti_localized.error );
					console.log( response );
				}
			},
			error: function( e ){
				console.log( 'AJAX ' + bookacti_localized.error );
				console.log( e );
			},
			complete: function() {}
		});	
		
	});
});