$j( document ).ready( function() {
	/**
	 * Perform WC action defined in Calendar field settings
	 * @since 1.9.0
	 */
	$j( 'body' ).on( 'bookacti_perform_form_action', '.bookacti-booking-system', function() {
		var booking_system = $j( this );
		bookacti_wc_perform_form_action( booking_system );
	});
});


/**
 * Perform WC form action
 * @since 1.7.19
 * @version 1.15.0
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
	
	// Check if selected event is valid
	var quantity = booking_system.closest( '.bookacti-form-fields' ).length ? ( booking_system.closest( '.bookacti-form-fields' ).find( '.bookacti-quantity' ).length ? parseInt( booking_system.closest( '.bookacti-form-fields' ).find( '.bookacti-quantity' ).val() ) : 1 ) : 1;
	var wc_quantity = booking_system.closest( 'form.cart' ).length ? ( booking_system.closest( 'form.cart' ).find( 'input[name="quantity"]' ).length ? parseInt( booking_system.closest( 'form.cart' ).find( 'input[name="quantity"]' ).val() ) : 1 ) : 1;
	var is_valid_event = bookacti_validate_picked_events( booking_system, Math.max( quantity, wc_quantity ) );
	if( ! is_valid_event ) {
		// Scroll to error message
		bookacti_scroll_to( booking_system.siblings( '.bookacti-notices' ), 500, 'middle' );
		return;
	}
	
	var group_id = parseInt( attributes[ 'picked_events' ][ 0 ][ 'group_id' ] );
	var picked_event = attributes[ 'picked_events' ][ 0 ];
	
	// Redirect to activity / group category URL
	if( form_action === 'redirect_to_product_page' ) {
		if( group_id > 0 ) {
			bookacti_redirect_to_group_category_product_page( booking_system, group_id );
		} else {
			bookacti_redirect_to_activity_product_page( booking_system, picked_event.id );
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
 * @version 1.9.0
 * @param {HTMLElement} booking_system
 * @param {int} event_id
 */
function bookacti_redirect_to_activity_product_page( booking_system, event_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event_id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event_id ][ 'activity_id' ];
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
 * @version 1.15.15
 * @param {HTMLElement} booking_system
 */
function bookacti_add_product_to_cart_via_booking_system( booking_system ) {
	// Get form or create a temporary one
	var has_form = booking_system.closest( 'form' ).length;
	if( ! has_form ) { booking_system.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' ); }
	var form = booking_system.closest( 'form' );
	
	// Disable the submit button to avoid multiple booking
	var submit_button = form.find( 'input[type="submit"]' );
	if( submit_button.length ) { submit_button.prop( 'disabled', true ); }
	
	// Use the error div of the booking system by default, or if possible, the error div of the form
	var error_div = form.find( '> .bookacti-notices' ).length ? form.find( '> .bookacti-notices' ) : booking_system.siblings( '.bookacti-notices' );
	
	// Remove the previous feedbacks
	error_div.empty();
	
	// Get form field values
	var data = { 'form_data': new FormData( form.get(0) ) };
	
	// Trigger action before sending form
	booking_system.trigger( 'bookacti_before_add_product_to_cart', [ data ] );
	
	// Remove temporary form
	if( ! has_form ) { booking_system.closest( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' ); }
	
	if( ! ( data.form_data instanceof FormData ) ) { 
		// Re-enable the submit button
		if( submit_button.length ) { submit_button.prop( 'disabled', false ); }
		return false;
	}
	
	// Change action
	data.form_data.set( 'action', 'bookactiAddBoundProductToCart' );
	
	bookacti_start_loading_booking_system( booking_system );
	
	// Display a loader after the submit button too
	if( submit_button.length ) { bookacti_add_loading_html( submit_button, 'after' ); }
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data.form_data,
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
					window.location.assign( redirect_url );
					bookacti_stop_loading_booking_system( booking_system );
				}
				
			} else {
				console.log( response );
			}
			
			// Display feedback message
			if( response.message ) {
				var feedback_class = response.status === 'success' ? 'bookacti-success-list woocommerce' : 'bookacti-error-list';
				var message = '<ul class="' + feedback_class + '"><li>' + response.message + '</li></ul>';
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
			if( submit_button.length ) { 
				bookacti_remove_loading_html( submit_button.parent() );
				submit_button.prop( 'disabled', false );
			}
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}