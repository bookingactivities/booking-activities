$j( document ).ready( function() {
	
	// Intercept settings form submission
	$j( 'form#bookacti-settings.bookacti_save_settings_with_ajax' ).on( 'submit', function( e ) {
		
		// Prevent submission
		e.preventDefault();
		
		// Update tinemce editor content 
		if( typeof tinyMCE !== 'undefined' ) {
			if( typeof tinyMCE.activeEditor !== 'undefined' ) {
				if( typeof tinyMCE.activeEditor.id !== 'undefined' ) {
					if( $j( 'textarea#' + tinyMCE.activeEditor.id ).length ) {
						$j( 'textarea#' + tinyMCE.activeEditor.id ).val( tinyMCE.activeEditor.getContent() );
					}
				}
			}
		}
		
		var form	= $j( this );
		var data	= form.serialize();
		
		$j.ajax({
			url: bookacti_localized.ajaxurl,
			type: 'POST',
			data: data,
			dataType: 'json',
			success: function( response ){
				
				if( response.status === 'success' ) {
					
					if( form.attr( 'action' ) ) {
						window.location.replace( form.attr( 'action' ) );
					} else {
						window.location.reload( true ); 
					}
					
				} else {
					console.log( 'AJAX ' + bookacti_localized.error_update_settings );
					console.log( response );
					alert( 'AJAX ' + bookacti_localized.error_update_settings );
				}
				
			},
			error: function( e ){
				console.log( 'AJAX ' + bookacti_localized.error_update_settings );
				console.log( e );
				alert( 'AJAX ' + bookacti_localized.error_update_settings );
			},
			complete: function() {}
		});	
		
	});
	
});