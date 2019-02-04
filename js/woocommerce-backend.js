if( typeof $j === 'undefined' ) { $j=jQuery.noConflict(); }

$j( document ).ready( function() {

	// Show or hide the activity tab on product page in the backend
	bookacti_show_hide_activity_tab();
	$j( '.type_box select, .type_box input' ).on( 'change', function(){ 
		bookacti_show_hide_activity_tab();
	});
	
	// Change form link according to selected form
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
	
	// Show or Hide deprecated fields
	$j( '#woocommerce-product-data' ).on( 'click', '.bookacti-show-deprecated', function( e ){ 
		e.preventDefault();
		$j( '.bookacti-deprecated-hidden' ).toggle();
	});
	
	// Show or hide activity fields on variation page in the backend
	// On load
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
	// On change
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_is_activity', function(){ 
		bookacti_show_hide_activity_variation_fields( this );
	});
	
	/** START BACKWARD COMPATIBILITY < 1.5 **/
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_template', function(){ 
		var template_ids = $j( this ).val();
		if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( this ).data( 'parent' ); }
		var options = $j( '[name$="[' + $j( this ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( this ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options_deprecated( template_ids, options );
	});
	/** END BACKWARD COMPATIBILITY < 1.5 **/
	
	// Force virtual on activities variations
	$j( '#woocommerce-product-data' ).on( 'change', '.variable_is_virtual', function(){ 
		if( $j( this ).parents( '.options' ).find( '.bookacti_variable_is_activity' ).is( ':checked' ) ) {
			if( ! $j( this ).is( ':checked' ) ) {
				$j( this ).prop( 'checked', true ).trigger( 'change' );
			}
		}
	});
	
	// Show / Hide WC field in delete booking dialog
	$j( '.bookacti-user-bookings-list, .bookacti-order-item-activity, #bookacti-bookings-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
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
	
	// Migrate old product setting to Booking Activities 1.5 forms
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
});


// Show or hide the activity tab on product page in the backend
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


// Show or hide activity fields on variation page in the backend
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