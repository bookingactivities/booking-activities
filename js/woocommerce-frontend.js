$j( document ).ready( function() {
	// ORDER DETAILS
	
	/**
	 * Add data to booking actions
	 * @since 1.0.12
	 * @param {Event} e
	 * @param {Object} data
	 * @param {Int} booking_id
	 * @param {String} booking_type
	 * @param {String} action
	 */
	$j( '.woocommerce-table' ).on( 'bookacti_booking_action_data', 'tr.order_item', function( e, data, booking_id, booking_type, action ) {
		data.context = 'wc_order_items';
	});




	// SINGLE PRODUCT
	
	// Handle variations
	if( $j( '.woocommerce form.cart.variations_form' ).length ) { 
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
		 * Switch the booking form according to the selected product variation
		 * @version 1.8.0
		 */
		$j( '.woocommerce form.cart.variations_form' ).each( function() {
			var wc_form = $j( this );

			if( wc_form.find( '.bookacti-booking-system' ).length && ! wc_form.find( '.bookacti-wc-form-fields' ).length ) { return true; }

			if( typeof bookacti.form_fields === 'undefined' ) { bookacti.form_fields = []; }

			// Empty the form
			wc_form.on( 'reset_data', function( e ) { 
				var form_container = wc_form.find( '.bookacti-wc-form-fields' );
				form_container.data( 'form-id', '' );
				form_container.attr( 'data-form-id', '' );
				form_container.data( 'variation-id', '' );
				form_container.attr( 'data-variation-id', '' );
				form_container.empty();
			});

			// Switch form
			wc_form.find( '.single_variation_wrap' ).on( 'show_variation', function( e, variation ) { 
				var form_container = wc_form.find( '.bookacti-wc-form-fields' );
				bookacti_switch_product_variation_form( form_container, variation );

				// Change Add to cart button label
				var new_button_text = variation[ 'bookacti_is_activity' ] ? bookacti_localized.add_booking_to_cart_button_text : bookacti_localized.add_product_to_cart_button_text;
				wc_form.find( '.single_add_to_cart_button' ).text( new_button_text );
			});
		});
	}


	/**
	 * Enable add-to-cart button
	 * @version 1.7.4
	 */
	$j( '.woocommerce form.cart' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', '.bookacti-booking-system', function() {
		$j( this ).parents( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', false );
		$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', false );
	});


	/**
	 * Disable add-to-cart button
	 * @version 1.7.4
	 */
	$j( '.woocommerce form.cart' ).on( 'bookacti_error_displayed', '.bookacti-booking-system', function() {
		$j( this ).parents( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', true );
		$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', true );
	});


	if( $j( '.woocommerce form.cart .single_add_to_cart_button' ).length ) {
		/**
		 * Add to cart dynamic check
		 * @version 1.7.4
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
				if( form.find( '.bookacti-booking-system' ).length ) {
					// Submit form if all is OK
					var is_valid = bookacti_validate_picked_events( form.find( '.bookacti-booking-system' ), form.find( '.quantity input.qty' ).val() );
					if( is_valid ) {
						// Trigger action before sending form
						form.trigger( 'bookacti_before_submit_booking_form' );
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
	 * Change activity summary on qty change
	 * @version 1.7.4
	 */
	$j( '.woocommerce form.cart' ).on( 'keyup mouseup change', 'input.qty', function() {
		var booking_system = $j( this ).parents( 'form.cart' ).find( '.bookacti-booking-system' );
		if( booking_system.length ) {
			bookacti_fill_picked_events_list( booking_system );
		}
	});
	
	
	/**
	 * Set picked events list quantity according to the product form quantity - on bookacti_picked_events_list_data
	 * @version 1.8.10
	 * @param {Event} e
	 * @param {Object} event_data
	 * @param {Object} event
	 */
	$j( '.woocommerce form.cart' ).on( 'bookacti_picked_events_list_data', '.bookacti-booking-system', function( e, event_data, event ) {
		var booking_system = $j( this );
		var qty_field = booking_system.parents( 'form' ).find( '.quantity .qty' );
		if( qty_field.length ) {
			event_data.quantity = parseInt( qty_field.val() );
		}
	});
	

	/**
	 * Set product form quantity field - on bookacti_update_quantity
	 * @since 1.8.10
	 * @param {Event} e
	 * @param {Object} qty_data
	 */
	$j( '.woocommerce form.cart' ).on( 'bookacti_update_quantity', '.bookacti-booking-system', function( e, qty_data ) {
		var booking_system = $j( this );
		var qty_field = booking_system.parents( 'form' ).find( '.quantity .qty' );
		if( qty_field.length ) {
			qty_data.field = qty_field;
		}
	});




	// CART
	
	/**
	 * Create a countdown on cart
	 */
	if( $j( '.bookacti-countdown' ).length ) {
		setInterval( bookacti_countdown, 1000 );
	}
});


/**
 * Switch form according to variation
 * @version 1.8.10
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
	var loading_div = '<div class="bookacti-loading-alt">' 
					+	'<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+	'<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	form_container.append( loading_div );
	
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
        complete: function() { form_container.find( '.bookacti-loading-alt' ).remove(); }
    });	
}


/**
 * Replace a old variation form with a new one
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
	bookacti_init_booking_system_dialogs();
	
	// Initialize tooltip
	bookacti_init_tooltip();
	
	// Remove initial loading feedback
	booking_system.find( '.bookacti-loading-alt' ).remove();

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





