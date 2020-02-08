if( typeof $j === 'undefined' ) { $j=jQuery.noConflict(); }

$j( document ).ready( function() {
	/**
	 * Show the bind / unbind order item booking button on mouseover
	 * @since 1.7.10
	 */
	$j( 'body' ).on( 'mouseover', '#woocommerce-order-items tr.item', function( e ) {
		$j( this ).find( '.bookacti-order-item-action-buttons' ).show();
	});
	
	
	/**
	 * Hide the bind / unbind order item booking button on mouseout
	 * @since 1.7.10
	 */
	$j( 'body' ).on( 'mouseout', '#woocommerce-order-items tr.item', function( e ) {
		$j( this ).find( '.bookacti-order-item-action-buttons' ).hide();
	});
	
	
	/**
	 * Show or hide the activity tab on product page in the backend
	 * @version 1.7.18
	 */
	$j( '#product-type, ._bookacti_is_activity' ).on( 'change', function() {
		bookacti_show_hide_activity_tab();
		if( $j( '#_bookacti_is_activity' ).is( ':checked' ) && ! $j( '#_virtual' ).is( ':checked' ) ) {
			$j( '#_virtual' ).prop( 'checked', true ).trigger( 'change' );
		}
	});
	bookacti_show_hide_activity_tab();
	
	
	/**
	 * Change form link according to selected form
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#_bookacti_form, .bookacti_variable_form', function( e ){ 
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
	 * Show or Hide deprecated fields
	 */
	$j( '#woocommerce-product-data' ).on( 'click', '.bookacti-show-deprecated', function( e ){ 
		e.preventDefault();
		$j( '.bookacti-deprecated-hidden' ).toggle();
	});
	
	
	/**
	 * Show or hide activity fields on variation page in the backend on load
	 */
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_show_hide_activity_variation_fields();
		/** START BACKWARD COMPATIBILITY < 1.5 **/
		$j( '#woocommerce-product-data .bookacti_variable_template' ).each( function() {
			var template_ids = $j( this ).val();
			if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( this ).data( 'parent' ); }
			var options = $j( '[name$="[' + $j( this ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( this ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
			bookacti_show_hide_template_related_options_deprecated( template_ids, options );
		});
		/** END BACKWARD COMPATIBILITY < 1.5 **/
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
	
	
	/** START BACKWARD COMPATIBILITY < 1.5 **/
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_template', function(){ 
		var template_ids = $j( this ).val();
		if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( this ).data( 'parent' ); }
		var options = $j( '[name$="[' + $j( this ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( this ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options_deprecated( template_ids, options );
	});
	/** END BACKWARD COMPATIBILITY < 1.5 **/
	
	
	/**
	 * Migrate old product setting to Booking Activities 1.5 forms
	 */
	$j( '#woocommerce-product-data' ).on( 'click', '.bookacti-generate-product-booking-form, .bookacti-generate-variation-booking-form', function( e ) {
		e.preventDefault();
		var product_id = $j( '#post_ID' ).val();
		var variation_id = 0;
		if( $j( this ).hasClass( 'bookacti-generate-variation-booking-form' ) ) {
			var loop = $j( this ).data( 'loop' );
			variation_id = $j( 'input[name="variable_post_id[' + loop + ']"]' ).val();
		}
		bookacti_migrate_product_activity_settings_to_booking_form( product_id, variation_id, $j( this ) );
	});
	
	
	/**
	 * Show / Hide WC field in delete booking dialog
	 * @version 1.7.4
	 */
	$j( '.bookacti-user-booking-list, .woocommerce-table, #bookacti-booking-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
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
			&&  ! $j( '.bookacti-view-booking-order[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-gouped-booking' ).length ) { 
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
	
	
	
	
	// Empty price notice
	
	/**
	 * Show or hide a notice if the price is empty when changing the product price
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'keyup mouseup change', '#_regular_price', function( e ) {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when changing the variation price
	 * @since 1.7.14
	 * @version 1.7.17
	 */
	$j( '#woocommerce-product-data' ).on( 'keyup mouseup change', '.woocommerce_variation .wc_input_price[name^="variable_regular_price["]', function( e ) {
		var variation_menu_order = $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).length ? $j( this ).closest( '.woocommerce_variation' ).find( '.variation_menu_order' ).val() : 0;
		bookacti_show_hide_empty_price_notice( variation_menu_order );
	});
	
	/**
	 * Show or hide a notice if the price is empty when changing product type
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#product-type', function( e ) {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when the product is flagged as "Activity"
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '#_bookacti_is_activity', function( e ) {
		bookacti_show_hide_empty_price_notice();
	});
	
	/**
	 * Show or hide a notice if the price is empty when the variation is flagged as "Activity"
	 * @since 1.7.14
	 */
	$j( '#woocommerce-product-data' ).on( 'change', '.woocommerce_variation .bookacti_variable_is_activity', function( e ) {
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
 * @param {dom_element} checkbox
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
 * @version 1.7.17
 * @param {int} variation_menu_order
 */
function bookacti_show_hide_empty_price_notice( variation_menu_order ) {
	variation_menu_order = parseInt( variation_menu_order ) || 'product';
	
	// Remove notices
	$j( '#woocommerce-product-data .bookacti-empty-product-price-notice' ).remove();
	
	var notice_div = '<div class="bookacti-empty-product-price-notice">' + 'You must set a price for your product, otherwise the booking form won’t appear on the product page.' + '</div>';
	
	// Display notice if the price is empty and if the product / variation is an activity
	if( variation_menu_order === 'product' ) {
		var product_price = $j.isNumeric( $j( '#_regular_price' ).val() ) ? parseFloat( $j( '#_regular_price' ).val() ) : '';
		if( ! product_price && product_price !== 0 && product_price !== '0' && $j( '#_bookacti_is_activity' ).is( ':checked' ) ) {
			$j( '#_regular_price, #_bookacti_form' ).after( notice_div );
		}
	} else if( variation_menu_order ) {
		var var_nb = variation_menu_order - 1;
		var variation_price = $j.isNumeric( $j( 'input[name="variable_regular_price[' + var_nb + ']"]' ).val() ) ? parseFloat( $j( 'input[name="variable_regular_price[' + var_nb + ']"]' ).val() ) : '';
		if( ! variation_price && variation_price !== 0 && variation_price !== '0' && $j( '#bookacti_variable_is_activity_' + var_nb ).is( ':checked' ) ) {
			$j( 'input[name="variable_regular_price[' + var_nb + ']"], #bookacti_variable_form_' + var_nb ).after( notice_div );
		}
	}
}


/**
 * Migrate product settings to a new booking form. 
 * This is a helper for WooCommerce users to migrate to Booking Activities 1.5.
 * @since 1.5.0
 * @returns {void}
 */
function bookacti_migrate_product_activity_settings_to_booking_form( product_id, variation_id, button ) {
	
	// Remove old feedbacks
	var container = button.parent();
	container.find( '.bookacti-notices' ).remove();
	
	// Display a loader
	var loading_div = '<div class="bookacti-loading-alt">' 
					+	'<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+	'<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	button.after( loading_div );
	
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { "action": 'bookactiMigrateProductSettings',
				"product_id": product_id,
				"variation_id": variation_id
			},
		dataType: 'json',
		success: function( response ){
			
			// Add the form to the selectbox and select it
			if( typeof response.form_id !== 'undefined' ) {
				var form_selectbox = ''; 
				if( variation_id ) { form_selectbox = $j( '#bookacti_variable_form_' + button.data( 'loop' ) ); } 
				else { form_selectbox = $j( '#_bookacti_form' ); }
				
				if( form_selectbox.length ) {
					form_selectbox.append( '<option value="' + response.form_id + '">' + response.form_title + '</option>' );
					form_selectbox.find( 'option[value="' + response.form_id + '"]' ).prop( 'selected', true ).trigger( 'change' );
				}
			}
			
			// Display the feedback message
			if( typeof response.message !== 'undefined' ) {
				var message_class = '';
				if( response.status === 'failed' ) { message_class = 'bookacti-error-list'; }
				if( response.status === 'warning' ) { message_class = 'bookacti-warning-list'; }
				if( response.status === 'success' ) { message_class = 'bookacti-success-list'; }
				
				// Display feedbacks
				button.after( '<div class="bookacti-notices"><ul class="' + message_class + '"><li>' + response.message + '</li></ul></div>' );
			}
			
			if( response.status === 'success' ) {
				button.remove();
			} else {
				console.log( response );
			}
			
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_create_form );
			console.log( e );
		},
		complete: function() {
			container.find( '.bookacti-notices' ).show();
			container.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Show or hide activities depending on the selected template
 * @since 1.7.0
 * @param {array} template_ids
 * @param {dom_element} options
 */
function bookacti_show_hide_template_related_options_deprecated( template_ids, options ) {
	
	// Init variables
	var change_selected = [];
	
	if( $j.isNumeric( template_ids ) ) { template_ids = [ template_ids ]; }
	
	// Show all
	options.prop( 'disabled', false );
	options.removeClass( 'bookacti-hide-fields' );
	
	// Hide not allowed
	options.each( function() {
		
		var option = $j( this );
		
		// Retrieve allowed templates array
		var allowed_templates = option.data( 'bookacti-show-if-templates' ).toString();
		if( allowed_templates.indexOf( ',' ) >= 0 ) {
			allowed_templates = allowed_templates.split( ',' );
		} else {
			allowed_templates = [ allowed_templates ];
		}
		
		// Hide not allowed data and flag if one of them was selected
		var hide = true;
		$j.each( template_ids, function( i, template_id ) {
			if( $j.inArray( template_id.toString(), allowed_templates ) >= 0 ) {
				hide = false;
			}
		});
		
		if( hide ) {
			if( option.is( ':selected' ) ) { 
				change_selected.push( option ); 
			}
			option.addClass( 'bookacti-hide-fields' );
			option.prop( 'disabled', true );
		}
	});

	// Change selected activity automatically if it gets hidden
	$j.each( change_selected, function( i, old_selected_option ) {
		old_selected_option.removeAttr( 'selected' );
		old_selected_option.siblings( 'option:not(.bookacti-hide-fields):not(:disabled):first' ).prop( 'selected', true );
	});
}