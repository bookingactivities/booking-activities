$j( document ).ready( function() {
	// ORDER DETAILS
	
	/**
	 * Add data to booking actions
	 * @since 1.0.12
	 * @version 1.12.0
	 * @param {Event} e
	 * @param {Object} data
	 * @param {Int} booking_id
	 * @param {String} booking_type
	 * @param {String} action
	 */
	$j( '.woocommerce' ).on( 'bookacti_booking_action_data', '.woocommerce-table tr.order_item', function( e, data, booking_id, booking_type, action ) {
		var is_FormData = false;
		if( typeof data.form_data !== 'undefined' ) { if( data.form_data instanceof FormData ) { is_FormData = true; } }
		if( is_FormData ) {
			data.form_data.append( 'context', 'wc_order_items' );
		} else {
			data.context = 'wc_order_items';
		}
	});




	// SINGLE PRODUCT
	
	// Init variables
	if( typeof bookacti.form_fields === 'undefined' ) { bookacti.form_fields = []; }
	
	
	/**
	 * Do not init booking system automatically if is supposed to be loaded while switching WC variations
	 * @since 1.7.0
	 * @version 1.7.4
	 * @param {Event} e
	 * @param {Object} load
	 * @param {Object} attributes
	 */
	$j( '.woocommerce' ).on( 'bookacti_init_booking_sytem', 'form.cart.variations_form .bookacti-booking-system', function( e, load, attributes ) {
		if( load.load === false ) { return; }
		if( typeof $j( this ).closest( '.bookacti-wc-form-fields' ) !== 'undefined' ) { 
			if( $j( this ).closest( '.bookacti-wc-form-fields' ).data( 'default-variation-id' ) ) { load.load = false; }
		}
	});


	/**
	 * Empty the booking form - on reset variation
	 * @version 1.15.5
	 */
	$j( '.woocommerce' ).on( 'reset_data', 'form.cart.variations_form', function() { 
		if( ! $j( this ).find( '.bookacti-wc-form-fields' ).length ) { return; }
		var form_container = $j( this ).find( '.bookacti-wc-form-fields' );
		form_container.data( 'form-id', '' );
		form_container.attr( 'data-form-id', '' );
		form_container.data( 'variation-id', '' );
		form_container.attr( 'data-variation-id', '' );
		form_container.empty();
	});


	/**
	 * Switch the booking form according to the selected product variation - on switch variation
	 * @version 1.15.5
	 * @param {Event} e
	 * @param {Object} variation
	 */
	$j( '.woocommerce' ).on( 'show_variation', 'form.cart.variations_form', function( e, variation ) { 
		if( ! $j( this ).find( '.bookacti-wc-form-fields' ).length ) { return; }

		var form_container = $j( this ).find( '.bookacti-wc-form-fields' );
		bookacti_switch_product_variation_form( form_container, variation );

		// Change Add to cart button label
		var new_button_text = variation[ 'bookacti_is_activity' ] ? bookacti_localized.add_booking_to_cart_button_text : bookacti_localized.add_product_to_cart_button_text;
		$j( this ).find( '.single_add_to_cart_button' ).text( new_button_text );
	});


	/**
	 * Enable add-to-cart button
	 * @version 1.15.5
	 */
	$j( '.woocommerce' ).on( 'bookacti_displayed_info_cleared', 'form.cart .bookacti-booking-system', function() {
		$j( this ).closest( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', false );
		$j( this ).closest( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	/**
	 * Disable add-to-cart button
	 * @version 1.15.5
	 */
	$j( '.woocommerce' ).on( 'bookacti_error_displayed', 'form.cart .bookacti-booking-system', function() {
		$j( this ).closest( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', true );
		$j( this ).closest( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', true );
	});


	if( $j( '.woocommerce form.cart .single_add_to_cart_button' ).length ) {
		/**
		 * Add to cart dynamic check
		 * @version 1.15.0
		 */
		$j( '.woocommerce form.cart' ).on( 'submit', function() { 
			var form = $j( this );
			
			var proceed_to_validation = false;

			if( form.hasClass( 'variations_form' ) ) {
				var variation_id = form.find( '.variation_id' ).val();
				if( variation_id !== '' && typeof variation_id !== 'undefined' ) {
					proceed_to_validation = bookacti.is_variation_activity[ variation_id ];
				}
			} else if( form.find( '.bookacti-booking-system-container' ).length ) {
				proceed_to_validation = true;
			}

			if( proceed_to_validation ) {
				// Submit Add to cart form only once at a time
				if( form.hasClass( 'bookacti-adding-to-cart' ) ) { 
					form.find( 'button[type="submit"]' ).attr( 'disabled', true ); 
					return false;
				}
				
				if( form.find( '.bookacti-booking-system' ).length ) {
					// Submit form if all is OK
					var is_valid = bookacti_validate_picked_events( form.find( '.bookacti-booking-system' ), form.find( 'input.qty' ).val() );
					if( is_valid ) {
						// Trigger action before sending form
						var data = { 'form_data': new FormData( form.get(0) ) };
						
						form.trigger( 'bookacti_before_submit_booking_form', [ data ] );
						
						if( ! ( data.form_data instanceof FormData ) ) { return false; }
						
						form.addClass( 'bookacti-adding-to-cart' );
						return true;
					} else {
						// Scroll to error message
						bookacti_scroll_to( form.find( '.bookacti-booking-system-container .bookacti-notices' ), 500, 'middle' );
						return false; // Prevent form submission
					}
				}
			}
		});
	}


	/**
	 * Change picked events list, set min and max quantity, and refresh total price field - on WC product page quantity change
	 * @version 1.15.5
	 */
	$j( '.woocommerce' ).on( 'keyup mouseup change', 'form.cart input.qty', function() {
		var form = $j( this ).closest( 'form' ).length ? $j( this ).closest( 'form' ) : $j( this ).closest( '.bookacti-form-fields' );
		if( ! form.length ) { return; }
		
		form.trigger( 'bookacti_booking_form_quantity_change', [ $j( this ).val(), $j( this ) ] );
		
		var booking_system = form.find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_set_min_and_max_quantity( booking_system );
			bookacti_fill_picked_events_list( booking_system );
		}
	});
	
	
	/**
	 * Add price data to selected events
	 * @since 1.12.4
	 * @param {Event} e
	 * @param {Object} list_item_data
	 * @param {Object} picked_event
	 */
	$j( 'body' ).on( 'bookacti_picked_events_list_item_data', '.bookacti-booking-system', function( e, list_item_data, picked_event ) {
		if( parseInt( list_item_data.price ) !== 0 || list_item_data.quantity <= 0 || list_item_data.has_price ) { return; }
		
		var booking_system = $j( this );
		var booking_system_id = $j( this ).attr( 'id' );
		var attributes = bookacti.booking_system[ booking_system_id ];
		var form = booking_system.closest( 'form' ).length ? booking_system.closest( 'form' ) : booking_system.closest( '.bookacti-form-fields' );
		
		if( form.find( 'input[data-name="price"]' ).length ) {
			list_item_data.price = parseFloat( form.find( 'input[data-name="price"]' ).val() ) * parseInt( list_item_data.quantity );
		}
		
		if( $j.inArray( attributes[ 'form_action' ], [ 'add_product_to_cart', 'redirect_to_product_page' ] ) >= 0 ) {
			if( parseInt( list_item_data.group_id ) > 0 ) {
				if( typeof attributes[ 'product_price_by_group_category' ] !== 'undefined' ) {
					if( typeof attributes[ 'product_price_by_group_category' ][ list_item_data.category_id ] !== 'undefined' ) {
						list_item_data.price = parseFloat( attributes[ 'product_price_by_group_category' ][ list_item_data.category_id ] ) * parseInt( list_item_data.quantity );
						list_item_data.has_price = true;
					}
				}
			} else {
				if( typeof attributes[ 'product_price_by_activity' ] !== 'undefined' ) {
					if( typeof attributes[ 'product_price_by_activity' ][ list_item_data.activity_id ] !== 'undefined' ) {
						list_item_data.price = parseFloat( attributes[ 'product_price_by_activity' ][ list_item_data.activity_id ] ) * parseInt( list_item_data.quantity );
						list_item_data.has_price = true;
					}
				}
			}
		}
		
		if( list_item_data.price > 0 && ! list_item_data.price_to_display ) { list_item_data.price_to_display = bookacti_format_price( list_item_data.price ); }
	});
	
	
	/**
	 * Empty the form fields after adding a booking to cart - on page load
	 * @since 1.15.0
	 */
	$j( '.bookacti-wc-form-fields-reset' ).each( function() {
		var booking_system = $j( this ).find( '.bookacti-booking-system' );
		if( ! booking_system.length ) { return; }
		
		// Clear booking system displayed info
		bookacti_clear_booking_system_displayed_info( booking_system );
		
		// Clear form feedback messages
		var error_div = $j( this ).find( '> .bookacti-notices' ).length ? $j( this ).find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
		error_div.empty();
	});




	// CART
	
	/**
	 * Create a countdown on cart
	 */
	if( $j( '.bookacti-countdown' ).length ) {
		setInterval( bookacti_countdown, 1000 );
	}
	
	
	
	
	// WC QUICK VIEW PLUGINS SUPPORT
	
	/**
	 * Load booking system in YITH WooCommerce Quick View popup
	 * https://wordpress.org/plugins/yith-woocommerce-quick-view/
	 * @since 1.15.5
	 */
	$j( document ).on( 'qv_loader_stop', function() {
		var popup = $j( '#yith-quick-view-modal' );
		var booking_system = popup.find( ' .bookacti-booking-system' );
		if( booking_system.length && ! popup.find( ' .variations_form' ).length ) {
			bookacti_reload_booking_system( booking_system );
		}
	});
});


/**
 * Switch form according to variation
 * @version 1.15.0
 * @param {HTMLElement} form_container
 * @param {object} variation
 */
function bookacti_switch_product_variation_form( form_container, variation ) {
	// Remove current form
	form_container.empty();
	
	// Switch form if the variation is actually an activity and if it is active, in stock and visible
	if( ! variation[ 'bookacti_is_activity' ] 
	||  ! variation[ 'is_in_stock' ] 
	||  ! variation[ 'variation_is_active' ] 
	||  ! variation[ 'variation_is_visible' ] ) { return; }
	
	bookacti.is_variation_activity[ variation[ 'variation_id' ] ] = true;
	
	var form_id = parseInt( variation[ 'bookacti_form_id' ] );
	if( ! form_id ) { return; }
	
	// Check if the form has already been loaded earlier
	if( typeof bookacti.form_fields[ form_id ] !== 'undefined' ) { 
		bookacti_fill_product_variation_form( form_container, variation, bookacti.form_fields[ form_id ] );
		return;
	}
	
	// Display a loading feedback
	bookacti_add_loading_html( form_container );
	
	var data = {	
		'action': 'bookactiGetForm', 
		'form_id': form_id, 
		'instance_id': 'product-variation-' + variation[ 'variation_id' ], 
		'context': 'wc_switch_variation'
	};
	
	// Get selected event from URL parameters if the variation attributes match the URL attributes
	var is_requested_in_url = true;
	$j.each( variation.attributes, function( attr_name, attr_value ) {
		if( attr_value !== bookacti_get_url_parameter( attr_name ) ) {
			is_requested_in_url = false; 
			return false; // Break
		}
	});
	
	// If the variation is requested via the URL, pass the URL data to prefill the fields
	if( is_requested_in_url ) {
		var serialized_data = $j.param( data );
		var url_serialized_parameters = window.location.search.substring( 1 );
		data = url_serialized_parameters + '&' + serialized_data;
	}
	
	// Load new form fields
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				bookacti.form_fields[ form_id ] = response.form_html;
				bookacti_fill_product_variation_form( form_container, variation, response.form_html );
				
			} else {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error );
            console.log( e );
        },
        complete: function() { 
			bookacti_remove_loading_html( form_container );
		}
    });	
}


/**
 * Replace a old variation form with a new one
 * @version 1.15.5
 * @param {HTMLElement} form_container
 * @param {object} variation
 * @param {HTMLElement} form_html
 */
function bookacti_fill_product_variation_form( form_container, variation, form_html ) {
	// Remove current form
	form_container.empty();
	
	// Insert new form fields
	form_container.append( form_html );

	// Change fields container metadata
	form_container.data( 'form-id', variation[ 'bookacti_form_id' ] );
	form_container.attr( 'data-form-id', variation[ 'bookacti_form_id' ] );
	form_container.data( 'variation-id', variation[ 'variation_id' ] );
	form_container.attr( 'data-variation-id', variation[ 'variation_id' ] );
	
	// Load the booking system
	var booking_system = form_container.find( '.bookacti-booking-system' );
	bookacti_booking_method_set_up( booking_system );
	
	// Initialize dialog
	bookacti_init_jquery_ui_dialogs( '.bookacti-booking-system-dialog' );
	
	// Initialize tooltip
	bookacti_init_tooltip();
	
	// Empty the form fields after adding a booking to cart
	if( form_container.hasClass( 'bookacti-wc-form-fields-reset' ) ) {
		// Clear booking system displayed info
		bookacti_clear_booking_system_displayed_info( booking_system );
		
		// Clear form feedback messages
		var error_div = form_container.find( '> .bookacti-notices' ).length ? form_container.find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
		error_div.empty();
	}
	
	// Remove initial loading feedback
	bookacti_remove_loading_html( booking_system );

	form_container.trigger( 'bookacti_product_variation_form_switched', [ variation ] );
}


/**
 * Create a countdown on cart items
 * @version 1.8.0
 */
function bookacti_countdown() {
	$j( '.bookacti-countdown' ).each( function() {
		if( $j( this ).hasClass( 'bookacti-expired' ) ) { $j( this ).html( bookacti_localized.expired ); return true; } // continue
			
		var expiration_date = $j( this ).data( 'expiration-date' );
		if( ! expiration_date ) { return true; } // continue
		
		expiration_date = moment.utc( expiration_date );
		var current_date = moment.utc();

		var current_time = expiration_date.diff( current_date, 'seconds' );

		// Calculate (and subtract) whole days
		var days = Math.max( Math.floor(current_time / 86400), 0 );
		current_time -= days * 86400;

		// Calculate (and subtract) whole hours
		var hours = Math.max( Math.floor(current_time / 3600) % 24, 0 );
		current_time -= hours * 3600;

		// Calculate (and subtract) whole minutes
		var minutes = Math.max( Math.floor(current_time / 60) % 60, 0 );
		current_time -= minutes * 60;

		// What's left is seconds
		var seconds = Math.max( current_time % 60, 0 );

		// Format
		var countdown = '';
		if( days > 0 ) {
			var days_word = bookacti_localized.days;
			if( days === 1 ) { days_word = bookacti_localized.day; }
			countdown += days + ' ' + days_word  + ' ';
		}
		if( hours > 0 || days > 0 ) {
			countdown += hours + ':' ;
		}

		countdown += bookacti_pad( minutes, 2 ) + ':' + bookacti_pad( seconds, 2 );

		if( days === 0 && hours === 0 && minutes === 0 && seconds === 0 ) {
			countdown = bookacti_localized.expired;
			$j( this ).addClass( 'bookacti-expired' );
			var countdown_div = this;
			setTimeout( function() { bookacti_refresh_cart_after_expiration( countdown_div ); }, 2000 );
		}

		$j( this ).html( countdown );
	});
}


/**
 * Refresh cart after expiration
 * @version 1.8.0
 * @param {HTMLElement} countdown
 */
function bookacti_refresh_cart_after_expiration( countdown ) {
	var is_checkout = $j( countdown ).closest( '.checkout' ).length;
	var woodiv = $j( countdown ).closest( '.woocommerce' );
	var url = woodiv.find( 'form' ).attr( 'action' );
	
	if( $j( countdown ).closest( '.bookacti-cart-expiration-container' ).length ) {
		$j( countdown ).closest( '.bookacti-cart-expiration-container' ).html( bookacti_localized.error_cart_expired );
	}
	
	if( ! is_checkout ) {
		if( $j( countdown ).hasClass( 'bookacti-cart-expiration' ) ) {
			woodiv.find( '.bookacti-cart-item-expires-with-cart' ).closest( '.cart_item' ).empty();
		} else {
			$j( countdown ).closest( '.cart_item' ).empty();
		}
		woodiv.find( '.cart-subtotal .amount, .shipping .amount, .order-total .amount, .includes_tax .amount' ).empty();
	}

	$j.ajax({
		url: url,
		type: 'POST',
		data: {},
		dataType: 'html',
		success: function( response ) {
			// Tell woocommerce to update checkout
			if( is_checkout ) {
				$j( 'body' ).trigger( 'update_checkout' );
				return;
			}
			
			// If cart doesn't contains items anymore, update the whole woocommerce div 
			if( $j( response ).find( '.woocommerce form' ).length <= 0 ) {
				woodiv.html( $j( response ).find( '.woocommerce' ).html() );
				return;
			}
			
			// Copy error messages
			if( $j( response ).find( '.woocommerce-error' ).length ) {
				if( woodiv.find( '.woocommerce-error' ).length ){
					$j( response ).find( '.woocommerce-error li' ).clone().appendTo( woodiv.find( '.woocommerce-error' ) );
				} else {
					$j( response ).find( '.woocommerce-error' ).clone().prependTo( woodiv );
				}
			}

			// Replace totals amounts
			woodiv.find( '.cart-subtotal .amount' ).html( $j( response ).find( '.cart-subtotal .amount' ).html() );
			woodiv.find( '.shipping .amount' ).html( $j( response ).find( '.shipping .amount' ).html() );
			woodiv.find( '.order-total .amount' ).html( $j( response ).find( '.order-total .amount' ).html() );
			woodiv.find( '.includes_tax .amount' ).html( $j( response ).find( '.includes_tax .amount' ).html() );
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {}
	});
}