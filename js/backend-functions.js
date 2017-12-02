$j( document ).ready( function() { 
	
	// Update multilangual fields with Qtranslate X
	$j( '.qtranxs-translatable' ).on( 'keyup', function() {
		bookacti_update_qtx_field( this );
	});
	
	// Tabs
	$j( '.bookacti-tabs' ).tabs();

	// Tooltip
	bookacti_init_tooltip();
	
	// Dismiss notices
	$j( '#bookacti-dismiss-5stars-rating' ).on( 'click', function(){ bookacti_dismiss_5stars_rating_notice(); });
	
});

// Init tooltip
function bookacti_init_tooltip() {
	$j( '.bookacti-tip' ).tooltip({
		items:	'[data-tip]',
		content: function () {
			return $j( this ).data( 'tip' );
		},
		show:	{ effect: 'fadeIn', duration: 200 },
		hide:	{ effect: 'fadeOut', duration: 200 },
		close: function(event, ui) {
			ui.tooltip.hover( function() {
				$j( this ).stop( true ).fadeTo( 200, 1 ); 
			},
			function() {
				$j( this ).fadeOut( '200', function() {
					$j( this ).remove();
				});
			});
		}
	});
	
	$j( '.bookacti-tip' ).tooltip( 'close' );
}


// Update multilangual fields with qtranslate X
function bookacti_update_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		var active_lang = qtx.getActiveLanguage();
		var input_name	= $j( field ).attr( 'name' );

		if( active_lang !== undefined ) {
			$j( 'input[name="qtranslate-fields[' + input_name + '][' + active_lang + ']"]' ).val( $j( field ).val() );
		}
	}
}

//Refresh multilingual field to make a correct display 
//( '[:en]Hello[:fr]Bonjour[:]' become 'Hello' and 'Bonjour' each in its own switchable field (with the LSB) )
function bookacti_refresh_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		$j( field ).removeClass('qtranxs-translatable');
		qtx.refreshContentHook( field );
		$j( field ).addClass('qtranxs-translatable');
	}
}


// Dismiss 5Stars rating notice
function bookacti_dismiss_5stars_rating_notice() {
	$j( '.bookacti-5stars-rating-notice' ).remove();
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiDismiss5StarsRatingNotice',
				'nonce': bookacti_localized.nonce_dismiss_5stars_rating_notice
			},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_update_settings;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( message_error );
				console.log( response );
			}
		},
		error: function( e ){
			console.log( e );
		},
		complete: function() { 
		}
	});
}

