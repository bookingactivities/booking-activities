$j( document ).ready( function() {
	// Update booking row after coupon refund
	$j( 'body' ).on( 'bookacti_booking_refunded', function( e, booking_id, booking_type, refund_action, refund_message, refund_data, response ){
		if( refund_action === 'auto' ) {
			// Set a message to feedback refunds
			refund_data.message += bookacti_localized.advice_booking_refunded;
		
		} else if( refund_action === 'coupon' ) {
			
			// Set a message to feedback refunds
			refund_data.message += bookacti_localized.advice_booking_refunded;
			refund_data.message += '<br/>' + bookacti_localized.advice_coupon_created.replace( '%1$s', '<strong>' + response.coupon_amount + '</strong>' );
			refund_data.message += '<br/>' + bookacti_localized.advice_coupon_code.replace( '%1$s', '<strong>' + response.coupon_code + '</strong>' );
			
			// Update booking row on frontend only
			if( ! bookacti_localized.is_admin ) {
				
				if( booking_type === 'single' ) {
					var row = $j( '.bookacti-booking-actions[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
				} else {
					var row = $j( '.bookacti-booking-group-actions[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
				}
				
				// Add coupon code on order details
				if( row.length && response.coupon_code ) {

					var meta_list = row.find( 'ul.wc-item-meta' );
					if( meta_list.length ) {
						if( meta_list.find( '.wc-item-meta-value.wc-item-meta-bookacti_refund_coupon' ).length ) {
							meta_list.find( '.wc-item-meta-value.wc-item-meta-bookacti_refund_coupon' ).html( response.coupon_code );
						} else {
							var coupon_code_meta = 
								'<li>'
							+		'<strong class="wc-item-meta-label wc-item-meta-bookacti_refund_coupon">' + bookacti_localized.coupon_code + ':</strong>'
							+		'<span class="wc-item-meta-value wc-item-meta-bookacti_refund_coupon" >' + response.coupon_code + '</span>'
							+	'</li>';
							meta_list.append( coupon_code_meta );
						}
					}

					// WOOCOMMERCE 3.0.0 backward compatibility
					if( row.find( '.variation' ).length ) {
						if( $j( 'dd.variation-bookacti_refund_coupon' ).length ) {
							$j( 'dd.variation-bookacti_refund_coupon p' ).html( response.coupon_code );
						} else {
							var coupon_code_label = $j( '<dt />', {
								'class': 'variation-bookacti_refund_coupon',
								'text': bookacti_localized.coupon_code + ':'
							});
							var coupon_code_value = $j( '<dd />', {
								'class': 'variation-bookacti_refund_coupon'
							}).append( $j( '<p />', { 'text': response.coupon_code } ) );
							row.find( '.variation' ).append( coupon_code_label );
							row.find( '.variation' ).append( coupon_code_value );
						}
					}
				}
			}
			
		}
	});
	
	
	/**
	 * Init WC actions to perfoms when the user picks an event
	 * @since 1.7.0
	 */
	$j( 'body' ).on( 'bookacti_events_picked_after_form_action', '.bookacti-booking-system', function( e, group_id, event ){
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		if( group_id === 'single' && attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			if( attributes[ 'form_action' ] === 'redirect_to_product_page' ) {
				bookacti_redirect_to_activity_product_page( booking_system, event );
			} else if( attributes[ 'form_action' ] === 'add_product_to_cart' ) {
				bookacti_add_activity_product_to_cart( booking_system, event );
			}
		}
	});
	
	
	/**
	 * Init WC actions to perfoms when the user picks a group of events
	 * @since 1.7.0
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen_after_form_action', '.bookacti-booking-system', function( e, group_id, event ) {
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		if( attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
			if( attributes[ 'form_action' ] === 'redirect_to_product_page' ) {
				bookacti_redirect_to_group_category_product_page( booking_system, group_id );
			} else if( attributes[ 'form_action' ] === 'add_product_to_cart' ) {
				bookacti_add_group_category_product_to_cart( booking_system, group_id );
			}
		}
	});
});




// REDIRECT

/**
 * Redirect to activity product page
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {object} event
 */
function bookacti_redirect_to_activity_product_page( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event.id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event.id ][ 'activity_id' ];
	if( typeof attributes[ 'product_by_activity' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_activity' ][ activity_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_activity' ][ activity_id ];
	if( typeof attributes[ 'products_page_url' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'products_page_url' ][ product_id ] === 'undefined' ) { return; }

	var redirect_url = attributes[ 'products_page_url' ][ product_id ];
	
	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}


/**
 * Redirect to group category product page
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {int} group_id
 */
function bookacti_redirect_to_group_category_product_page( booking_system, group_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'product_by_group_category' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_group_category' ][ category_id ];
	if( typeof attributes[ 'products_page_url' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'products_page_url' ][ product_id ] === 'undefined' ) { return; }
	
	var redirect_url = attributes[ 'products_page_url' ][ product_id ];
	
	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}




// ADD TO CART

/**
 * Add the product bound to the activity to cart from a booking form
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {object} event
 */
function bookacti_add_activity_product_to_cart( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event.id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event.id ][ 'activity_id' ];
	if( typeof attributes[ 'product_by_activity' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_activity' ][ activity_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_activity' ][ activity_id ];
	
	bookacti_add_product_to_cart_via_booking_system( booking_system, product_id );
}


/**
 * Add the product bound to the group category to cart from a booking form
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {int} group_id
 */
function bookacti_add_group_category_product_to_cart( booking_system, group_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'product_by_group_category' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_group_category' ][ category_id ];
	
	bookacti_add_product_to_cart_via_booking_system( booking_system, product_id );
}


/**
 * Add a product to cart from a booking form
 * @since 1.7.0
 * @param {type} booking_system
 * @param {type} product_id
 * @returns {undefined}
 */
function bookacti_add_product_to_cart_via_booking_system( booking_system, product_id ) {
	// Use the error div of he booking system by default, or if possible, the error div of the form
	var error_div = booking_system.siblings( '.bookacti-notices' );
	if( booking_system.closest( 'form' ).length ) {
		if( booking_system.closest( 'form' ).find( '> .bookacti-notices' ).length ) {
			error_div = booking_system.closest( 'form' ).find( '> .bookacti-notices' );
		}
	}
	
	// Remove the previous feedbacks
	error_div.empty();
	
	// Add form parameters to the URL
	var data = [];
	if( ! booking_system.closest( 'form' ).length ) {
		booking_system.closest( '.bookacti-booking-system-container' ).wrap( '<form id="bookacti-temporary-form"></form>' );
		data = new FormData( booking_system.closest( 'form' ).get(0) );
		booking_system.closest( '.bookacti-booking-system-container' ).unwrap( 'form#bookacti-temporary-form' );
	} else {
		var old_action = booking_system.closest( 'form' ).find( 'input[name="action"]' ).val();
		booking_system.closest( 'form' ).find( 'input[name="action"]' ).val( 'bookactiAddBoundProductToCart' );
		data = new FormData( booking_system.closest( 'form' ).get(0) );
		booking_system.closest( 'form' ).find( 'input[name="action"]' ).val( old_action );
	}
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ){
			
			if( response.status === 'success' ) {
				// Reload booking numbers if we are not redirected
				if( ! response.redirect_url ) {
					bookacti_refresh_booking_numbers( booking_system );
				}
				// Redirect to the desired page or to cart
				else {
					window.location.replace( response.redirect_url );
				}
				
			} else {
				console.log( response );
			}
			
			// Display feedback message
			if( response.messages ) {
				var feedback_class = response.status === 'success' ? 'bookacti-success-list woocommerce' : 'bookacti-error-list';
				var message = '<ul class="' + feedback_class + '"><li>' + response.messages + '</li></ul>';
				// Fill error message
				error_div.empty().append( message ).show();
				// Scroll to error message
				if( response.status !== 'success' || ! response.redirect_url ) {
					bookacti_scroll_to( error_div, 500, 'middle' );
				}
			}
        },
        error: function( e ){
			// Fill error message
			var message = '<ul class="bookacti-error-list"><li>AJAX error occured while trying to add the product to cart</li></ul>';
			error_div.empty().append( message ).show();
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
            console.log( 'AJAX error occured while trying to add the product to cart' );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}