$j( document ).ready( function() {
	/**
	 * Perform WC action defined in Calendar field settings
	 * @since 1.8.10
	 */
	$j( 'body' ).on( 'bookacti_perform_form_action', '.bookacti-booking-system', function() {
		var booking_system = $j( this );
		bookacti_wc_perform_form_action( booking_system );
	});
});


/**
 * Perform WC form action
 * @since 1.7.19
 * @version 1.8.10
 * @param {HTMLElement} booking_system
 */
function bookacti_wc_perform_form_action( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	var attributes = bookacti.booking_system[ booking_system_id ];
	var form_action = attributes[ 'form_action' ];
	
	if( $j.inArray( form_action, [ 'default', 'redirect_to_product_page', 'add_product_to_cart' ] ) === -1 ) { return; }
	
	// Default: Send the add to cart form
	if( form_action === 'default' && booking_system.closest( 'form.cart' ).length ) {
		if( booking_system.closest( 'form.cart' ).find( '.single_add_to_cart_button' ).length ) {
			booking_system.closest( 'form.cart' ).find( '.single_add_to_cart_button' ).trigger( 'click' );
		}
		return;
	}
	
	if( typeof attributes[ 'picked_events' ][ 0 ] === 'undefined' ) { return; }
	var group_id = parseInt( attributes[ 'picked_events' ][ 0 ][ 'group_id' ] );
	var event = attributes[ 'picked_events' ][ 0 ];
	
	// Redirect to activity / group category URL
	if( form_action === 'redirect_to_product_page' ) {
		if( group_id > 0 ) {
			bookacti_redirect_to_group_category_product_page( booking_system, group_id );
		} else {
			bookacti_redirect_to_activity_product_page( booking_system, event );
		}
	}
	
	// Add the product bound to the activity / group category to cart
	else if( form_action === 'add_product_to_cart' ) {
		bookacti_add_product_to_cart_via_booking_system( booking_system );
	}
}




// REDIRECT

/**
 * Redirect to activity product page
 * @since 1.7.0
 * @param {HTMLElement} booking_system
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
 * @param {HTMLElement} booking_system
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
 * Add a product to cart from a booking form
 * @since 1.7.0
 * @version 1.8.10
 * @param {HTMLElement} booking_system
 */
function bookacti_add_product_to_cart_via_booking_system( booking_system ) {
	// Use the error div of the booking system by default, or if possible, the error div of the form
	var error_div = booking_system.siblings( '.bookacti-notices' );
	if( booking_system.closest( 'form' ).length ) {
		if( booking_system.closest( 'form' ).find( '> .bookacti-notices' ).length ) {
			error_div = booking_system.closest( 'form' ).find( '> .bookacti-notices' );
		}
	}
	
	// Remove the previous feedbacks
	error_div.empty();
	
	// Get form or create a temporary one
	var has_form = booking_system.closest( 'form' ).length;
	if( ! has_form ) { booking_system.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' ); }
	var form = booking_system.closest( 'form' );
	
	// Change form action field value
	var has_form_action = form.find( 'input[name="action"]' ).length;
	var old_form_action = has_form_action ? form.find( 'input[name="action"]' ).val() : '';
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( 'bookactiAddBoundProductToCart' ); } 
	else { form.append( '<input type="hidden" name="action" value="bookactiAddBoundProductToCart"/>' ); }
	
	// Get form field values
	var data = new FormData( form.get(0) );
	
	// Restore form action field value
	if( has_form_action ) { form.find( 'input[name="action"]' ).val( old_form_action ); } 
	else { form.find( 'input[name="action"]' ).remove(); }
	
	// Trigger action before sending form
	booking_system.trigger( 'bookacti_before_add_product_to_cart', [ data ] );
	
	// Remove temporary form
	if( ! has_form ) { booking_system.closest( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' ); }
	
	if( ! ( data instanceof FormData ) ) { return; }
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ) {
			var redirect_url = typeof response.redirect_url !== 'undefined' ? response.redirect_url : '';
			if( response.status === 'success' ) {
				// Reload booking numbers if page is not reloaded
				if( redirect_url.indexOf( '://' ) < 0 ) {
					bookacti_refresh_booking_numbers( booking_system );
				}
				
				booking_system.trigger( 'bookacti_product_added_to_cart', [ response, data ] );
				
				// Redirect to the desired page or to cart
				if( redirect_url ) {
					bookacti_start_loading_booking_system( booking_system );
					window.location.replace( redirect_url );
					bookacti_stop_loading_booking_system( booking_system );
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
				if( ! redirect_url ) {
					bookacti_scroll_to( error_div, 500, 'middle' );
				}
			}
        },
        error: function( e ){
			// Fill error message
			var message = '<ul class="bookacti-error-list"><li>AJAX error occurred while trying to add the product to cart</li></ul>';
			error_div.empty().append( message ).show();
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
            console.log( 'AJAX error occurred while trying to add the product to cart' );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}