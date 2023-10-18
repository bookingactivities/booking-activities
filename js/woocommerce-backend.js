$j( document ).ready( function() {
	/**
	 * Show or hide the activity tab on product page in the backend - on load
	 */
	bookacti_show_hide_activity_tab();
	
	
	/**
	 * Show or hide the activity tab on product page in the backend - on change
	 * @version 1.12.6
	 */
	$j( '.type_box select, .type_box input' ).on( 'change', function() {
		if( $j( this ).is( '#_bookacti_is_activity' ) && $j( '#_bookacti_is_activity' ).is( ':checked' ) && ! $j( '#_virtual' ).is( ':checked' ) ) {
			$j( '#_virtual' ).prop( 'checked', true ).trigger( 'change' );
		}
		bookacti_show_hide_activity_tab();
	});
	
	
	/**
	 * Change form link according to selected form
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#_bookacti_form, .bookacti_variable_form', function(){ 
		var link = $j( '.bookacti-form-selectbox-link[data-form-selectbox-id="' + $j( this ).attr( 'id' ) + '"] a' );
		if( ! link.length ) { return; }
		if( $j( this ).val() == 0 ) {
			link.parent().hide();
		} else {
			link.attr( 'href', bookacti_localized.admin_url + 'admin.php?page=bookacti_forms&action=edit&form_id=' + $j( this ).val() );
			link.parent().show();
		}
	});
	
	
	/**
	 * Show or hide activity fields on variation page in the backend on load
	 * @version 1.8.0
	 */
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_show_hide_activity_variation_fields();
	});
	
	
	/**
	 * Show or hide activity fields on variation page in the backend on change
	 * @version 1.7.18
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_is_activity', function(){ 
		bookacti_show_hide_activity_variation_fields( this );
		var virtual_cb = $j( this ).closest( '.options' ).find( '.variable_is_virtual' );
		if( $j( this ).is( ':checked' ) && ! virtual_cb.is( ':checked' ) ) {
			virtual_cb.prop( 'checked', true ).trigger( 'change' );
		}
	});
	
	
	/**
	 * Show / Hide WC field in delete booking dialog
	 * @version 1.9.0
	 * @param {Event} e
	 */
	$j( '.bookacti-user-booking-list-table, .woocommerce-table, #bookacti-booking-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
		e.preventDefault();
		// Reset WC fields
		if( $j( this ).hasClass( 'bookacti-delete-booking' ) || $j( this ).hasClass( 'bookacti-delete-booking-group' ) ) {
			$j( '.bookacti-delete-wc-order-item-container select' ).val( 'none' );
		}
		
		var has_wc_order = false;
		// Single Bookings
		if( $j( this ).hasClass( 'bookacti-delete-booking' ) ) {
			var booking_id = $j( this ).data( 'booking-id' );
			if( $j( '.bookacti-view-booking-order[data-booking-id="' + booking_id + '"]' ).length 
			&&  ! $j( '.bookacti-view-booking-order[data-booking-id="' + booking_id + '"]' ).closest( '.bookacti-gouped-booking' ).length ) { 
				has_wc_order = true; 
			}
			
		// Booking Groups
		} else if( $j( this ).hasClass( 'bookacti-delete-booking-group' ) ) {
			var booking_group_id = $j( this ).data( 'booking-group-id' );
			if( $j( '.bookacti-view-booking-order[data-booking-group-id="' + booking_group_id + '"]' ).length ) { has_wc_order = true; }
		}
		
		if( has_wc_order ) { $j( '.bookacti-delete-wc-order-item-container' ).show(); } 
		else { $j( '.bookacti-delete-wc-order-item-container' ).hide(); }
	});
	
	
	/**
	 * Calendar field settings: Toggle the WC form actions notices - on change
	 * @since 1.11.3
	 */
	$j( '#bookacti-form-field-dialog-calendar' ).on( 'change', 'select#bookacti-form_action', function() {
		var form_action = $j( this ).val();
		$j( '.bookacti-form-action-with-wc-notice' ).toggle( form_action !== 'default' );
		$j( '.bookacti-add-product-to-cart-form-action-notice' ).toggle( form_action === 'add_product_to_cart' );
	});
	
	
	
	
	// Empty price notice
	
	/**
	 * Show or hide a notice if the price is empty when changing the product price
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'keyup mouseup change', '#_regular_price', function() {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when changing the variation price
	 * @since 1.7.14
	 * @version 1.7.17
	 */
	$j( '#woocommerce-product-data' ).on( 'keyup mouseup change', '.woocommerce_variation .wc_input_price[name^="variable_regular_price["]', function() {
		var variation_menu_order = $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).length ? $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).val() : 0;
		bookacti_show_hide_empty_price_notice( variation_menu_order );
	});
	
	/**
	 * Show or hide a notice if the price is empty when changing product type
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#product-type', function() {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when the product is flagged as "Activity"
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#_bookacti_is_activity', function() {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when the variation is flagged as "Activity"
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '.woocommerce_variation .bookacti_variable_is_activity', function() {
		var variation_menu_order = $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).length ? $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).val() : 0;
		bookacti_show_hide_empty_price_notice( variation_menu_order );
	});
	
	/**
	 * Show or hide a notice if the price is empty when the product edit page is loaded
	 * @since 1.7.14
	 */
	bookacti_show_hide_empty_price_notice();
	
	/**
	 * Show or hide a notice if the price is empty when the variations are loaded
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		$j( '#woocommerce-product-data .woocommerce_variation' ).each( function() {
			var variation_menu_order = $j( this ).find( '.variation_menu_order' ).length ? $j( this ).find( '.variation_menu_order' ).val() : 0;
			bookacti_show_hide_empty_price_notice( variation_menu_order );
		});
	});
	
	
	/**
	 * Lock the WPML fields in translated product data
	 * @since 1.14.0
	 */
	if( $j( '#woocommerce-product-data' ).length ) {
		bookacti_wpml_wc_lock_product_fields( '#woocommerce-product-data [name^="_bookacti"]:input' );
	}
	
	/**
	 * Lock the WPML fields in translated product data - variations tab
	 * @since 1.14.0
	 */
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_wpml_wc_lock_product_fields( '#variable_product_options [name^="bookacti_"]:input' );
	});
});


/**
 * Show or hide the activity tab on product page in the backend
 * @version 1.7.18
 */
function bookacti_show_hide_activity_tab() {
	$j( '.bookacti_show_if_activity' ).hide();
	
	if( $j( '#_bookacti_is_activity' ).is( ':checked' ) ) {
		if( $j( 'label[for="_bookacti_is_activity"]' ).is( ':visible' ) ) {
			$j( '.bookacti_show_if_activity' ).show();
		}
		$j( '.bookacti_hide_if_activity' ).hide();
		
	} else {
		$j( '.bookacti_hide_if_activity' ).show();
		if( $j( '#_virtual' ).is( ':checked' ) ) {
			$j( '.bookacti_hide_if_activity.hide_if_virtual' ).hide();
		}
	}
}


/**
 * Show or hide activity fields on variation page in the backend
 * @version 1.7.18
 * @param {HTMLElement} checkbox
 */
function bookacti_show_hide_activity_variation_fields( checkbox ) {
	checkbox = checkbox || null;
	
	if( ! checkbox ) {
		$j( '.show_if_variation_activity' ).hide();
		$j( '.bookacti_variable_is_activity' ).each( function() {
			if( $j( this ).is( ':checked' ) ) {
				$j( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
			} 
		});
		
	} else {
		if( $j( checkbox ).is( ':checked' ) ) {
			$j( checkbox ).closest( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
		} else {
			$j( checkbox ).closest( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).hide();
		}
	}
}


/**
 * Show or hide a notice when the product price is not set
 * @since 1.7.14
 * @version 1.15.15
 * @param {int} variation_menu_order
 */
function bookacti_show_hide_empty_price_notice( variation_menu_order ) {
	variation_menu_order = parseInt( variation_menu_order ) || 'product';
	
	// Remove notices
	$j( '#woocommerce-product-data .bookacti-empty-product-price-notice' ).remove();
	
	var notice_div = '<div class="bookacti-empty-product-price-notice bookacti-warning"><span class="dashicons dashicons-warning"></span>' + bookacti_localized.empty_product_price + '</div>';
	
	var wc_decimal_point = ',';
	if( typeof woocommerce_admin !== 'undefined' ) {
		if( typeof woocommerce_admin.mon_decimal_point !== 'undefined' ) {
			wc_decimal_point = woocommerce_admin.mon_decimal_point;
		}
	}
	
	// Display notice if the price is empty and if the product / variation is an activity
	if( variation_menu_order === 'product' ) {
		var price_val = $j( '#_regular_price' ).length ? $j( '#_regular_price' ).val().replace( wc_decimal_point, '.' ) : '';
		var product_price = $j.isNumeric( price_val ) ? parseFloat( price_val ) : '';
		if( ! product_price && product_price !== 0 && product_price !== '0' && $j( '#_bookacti_is_activity' ).is( ':checked' ) ) {
			$j( '#_regular_price, #_bookacti_form' ).after( notice_div );
		}
	} else if( variation_menu_order ) {
		var var_nb = variation_menu_order - 1;
		var price_val = $j( 'input[name="variable_regular_price[' + var_nb + ']"]' ).length ? $j( 'input[name="variable_regular_price[' + var_nb + ']"]' ).val().replace( wc_decimal_point, '.' ) : '';
		var variation_price = $j.isNumeric( price_val ) ? parseFloat( price_val ) : '';
		if( ! variation_price && variation_price !== 0 && variation_price !== '0' && $j( '#bookacti_variable_is_activity_' + var_nb ).is( ':checked' ) ) {
			$j( 'input[name="variable_regular_price[' + var_nb + ']"], #bookacti_variable_form_' + var_nb ).after( notice_div );
		}
	}
}


/**
 * Lock Booking Activities fields in product translated by WPML
 * Temp fix adapted from woocommerce-multilingual\res\js\lock_fields.js (waiting for hooks)
 * @since 1.14.0
 * @param {String} selector
 */
function bookacti_wpml_wc_lock_product_fields( selector ) {
	if( ! $j( '.wcml_lock_img' ).length || ! $j( selector ).length ) { return; }
	$j( selector ).each( function() {
		// Checkboxes and selectboxes
		if( $j( this ).attr( 'type' ) === 'checkbox' || $j( this ).is( 'select' ) ) {
			$j( this ).prop( 'disabled', true );
			$j( this ).after( $j( '.wcml_lock_img' ).clone().removeClass( 'wcml_lock_img' ).show() );

			// Copy the field value into an hidden input to save its value
			if( $j( 'input[name="' + $j( this ).attr( 'name' ) + '"]' ).length ) {
				$j( 'input[name="' + $j( this ).attr( 'name' ) + '"]' ).val( $j( this ).val() );
			} else {
				$j( this ).after( '<input type="hidden" name="' + $j( this ).attr( 'name' ) + '" value="' + $j( this ).val() + '" />' );
			}
		} 
		// Other inputs
		else if( $j( this ).attr( 'type' ) !== 'hidden' ) {
			$j( this ).prop( 'readonly', true );
			$j( this ).after( $j( '.wcml_lock_img' ).clone().removeClass( 'wcml_lock_img' ).show() );
		}
	});
}