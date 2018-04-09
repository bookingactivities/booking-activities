$j( document ).ready( function() {
	
	//Show or hide the activity tab on product page in the backend
	bookacti_show_hide_activity_tab();
	$j( '.type_box select, .type_box input' ).on( 'change', function(){ 
		bookacti_show_hide_activity_tab();
	});
	
	//Show or hide activities depending on the selected template
	// On change
	$j( 'bookacti_variable_template' ).on( 'change', function(){ 
		var template_ids	= $j( '#_bookacti_template' ).val();
		var options			= $j( '[data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options( template_ids, options );
	});
	
	//Show or hide activity fields on variation page in the backend
	// On load
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_show_hide_activity_variation_fields();
		$j( '#woocommerce-product-data .bookacti_variable_template' ).each( function() {
			var template_ids = $j( this ).val();
			if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( this ).data( 'parent' ); }
			var options = $j( '[name$="[' + $j( template ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( this ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
			bookacti_show_hide_template_related_options( template_ids, options );
		});
	});
	// On change
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_is_activity', function(){ 
		bookacti_show_hide_activity_variation_fields( this );
	});
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_template', function(){ 
		var template_ids = $j( this ).val();
		if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( this ).data( 'parent' ); }
		var options = $j( '[name$="[' + $j( template ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( this ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options( template_ids, options );
	});
	
	// Force virtual on activities variations
	$j( '#woocommerce-product-data' ).on( 'change', '.variable_is_virtual', function(){ 
		if( $j( this ).parents( '.options' ).find( '.bookacti_variable_is_activity' ).is( ':checked' ) ) {
			if( ! $j( this ).is( ':checked' ) ) {
				$j( this ).prop( 'checked', true ).trigger( 'change' );
			}
		}
	});
	
	// Dismiss notices
	$j( '.bookacti-guest-checkout-notice .notice-dismiss' ).on( 'click', function(){ bookacti_dismiss_guest_checkout_notice(); });
});


//Show or hide the activity tab on product page in the backend
function bookacti_show_hide_activity_tab() {
	
	$j( '.bookacti_show_if_activity' ).hide();
	
	if( $j( '#_bookacti_is_activity' ).is( ':checked' ) ) {
		
		if( ( $j( '#product-type' ).val() === 'simple' || $j( '#product-type' ).val() === 'variable' ) ) {
			$j( '.bookacti_show_if_activity' ).show();
			
			if( ! $j( '#_virtual' ).is( ':checked' ) ) {
				$j( '#_virtual' ).prop( 'checked', true ).trigger( 'change' );
			}
		}
		$j( '.bookacti_hide_if_activity' ).hide();
		
	} else {
		
		$j( '.bookacti_hide_if_activity' ).show();
		
		if( $j( '#_virtual' ).is( ':checked' ) ) {
			$j( '.bookacti_hide_if_activity.hide_if_virtual' ).hide();
		}
	}
}


//Show or hide activity fields on variation page in the backend
function bookacti_show_hide_activity_variation_fields( checkbox ) {
	
	checkbox = checkbox || undefined;
	
	if( checkbox === undefined ) {
		
		$j( '.show_if_variation_activity' ).hide();
		
		$j( '.bookacti_variable_is_activity' ).each( function() {
			if( $j( this ).is( ':checked' ) ) {
				$j( this ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
			} 
		});
		
	} else {
		
		if( $j( checkbox ).is( ':checked' ) ) {
			$j( checkbox ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
			
			if( ! $j( checkbox ).parents( '.options' ).find( '.variable_is_virtual' ).is( 'checked' ) ) {
				$j( checkbox ).parents( '.options' ).find( '.variable_is_virtual' ).prop( 'checked', true ).trigger( 'change' );
			}
			
		} else {
			$j( checkbox ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).hide();
		}
	}
}


// Dismiss guest checkout notice
function bookacti_dismiss_guest_checkout_notice() {
	$j( '.bookacti-guest-checkout-notice' ).remove();
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { "action": 'bookactiDismissGuestCheckoutNotice',
				"nonce": bookacti_localized.nonce_dismiss_guest_checkout_notice
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