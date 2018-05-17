$j( document ).ready( function() {
	
	// Intercept settings form submission
	$j( 'form#bookacti-settings.bookacti_save_settings_with_ajax' ).on( 'submit', function( e ) {
		
		// Prevent submission
		e.preventDefault();
		
		// Save tineMCE editor content 
		if( tinyMCE ) { tinyMCE.triggerSave(); }
		
		var form		= $j( this );
		var form_data	= form.serializeObject(); // Need to use the homemade serializeObject to support multidimentionnal array
		
		$j.ajax({
			url: bookacti_localized.ajaxurl,
			type: 'POST',
			data: form_data,
			dataType: 'json',
			success: function( response ){
				
				if( response.status !== 'success' ) {
					console.log( bookacti_localized.error_update_settings );
					console.log( response );
				} else {
					if( form.attr( 'action' ) ) {
						window.location.replace( form.attr( 'action' ) );
					} else {
						window.location.reload( true ); 
					}
				}
			},
			error: function( e ){
				console.log( 'AJAX ' + bookacti_localized.error_update_settings );
				console.log( e );
			},
			complete: function() {}
		});	
		
	});
	
});