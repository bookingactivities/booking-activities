$j( document ).ready( function() {
	if( ! window?.wc?.blocksCheckout ) { return; }
	const { registerCheckoutFilters } = window.wc.blocksCheckout;
	if( ! registerCheckoutFilters ) { return; }
	
	/**
	 * Add cart item class
	 * @since 1.16.9
	 */
	registerCheckoutFilters( 'booking-activities', {
		cartItemClass: ( value, extensions, args ) => {
			var is_booking = extensions?.[ 'booking-activities' ]?.is_booking;
			if( is_booking ) { 
				value += ' bookacti-wc-cart-item-is-booking';
			}
			
			var booking_status = extensions?.[ 'booking-activities' ]?.booking_status;
			if( booking_status ) { 
				value += ' bookacti-wc-cart-item-booking-status-' + booking_status;
			}
			
			var expiration_date = extensions?.[ 'booking-activities' ]?.expiration_date;
			if( expiration_date ) { 
				value += ' bookacti-wc-cart-item-expires bookacti-wc-cart-item-expires-at-' + expiration_date;
			}
			
			return value;
		}
	});
	
	
	/**
	 * TEMP FIX - Add a countdown in cart items
	 * Waiting for WC to provide an official way to add data to cart block and cart item components
	 * @since 1.16.9
	 */
	setInterval( bookacti_wc_blocks_inject_cart_items_countdown, 1000 );
	
	
	/**
	 * Refresh cart and checkout when countdown ends
	 * @since 1.16.9
	 * @param {Event} e
	 * @param {HTMLElement} countdown_div
	 */
	$j( 'body' ).on( 'bookacti_wc_countdown_expired', function( e, countdown_div ) {
		setTimeout( function() { 
			countdown_div.parents( '.bookacti-countdown-container' ).html( bookacti_localized.cart_item_expired );
		}, 1000 );
	});
});


/**
 * Inject a countdown in cart items blocks
 * @since 1.16.9
 */
function bookacti_wc_blocks_inject_cart_items_countdown() {
	if( ! $j( '.bookacti-wc-cart-item-expires' ).length ) { return; }
	
	$j( '.bookacti-wc-cart-item-expires' ).each( function() {
		// Extract the data from the classname
		var booking_status  = '';
		var expiration_date = '';
		var classes_array   = $j( this ).prop( 'class' ).split( ' ' );
		$j.each( classes_array, function( i, classname ) {
			var expiration_date_raw = bookacti_get_string_between( classname.replaceAll( '_', ':' ), 'bookacti-wc-cart-item-expires-at-' );
			if( expiration_date_raw ) {
				expiration_date = expiration_date_raw;
			}
			var booking_status_raw = bookacti_get_string_between( classname, 'bookacti-wc-cart-item-booking-status-' );
			if( booking_status_raw ) {
				booking_status = booking_status_raw;
			}
		});
		if( ! expiration_date || $j.inArray( booking_status, [ 'in_cart', 'pending' ] ) === -1 ) { return; }
		
		var countdown_html = '';
		if( booking_status === 'in_cart' ) {
			$j( this ).find( '.bookacti-wc-cart-item-status' ).remove();
			if( $j( this ).find( '.bookacti-countdown' ).length ) {
				$j( this ).find( '.bookacti-countdown' ).data( 'expiration-date', expiration_date ).attr( 'expiration-date', expiration_date );
			} else if( ! $j( this ).find( '.bookacti-countdown-container' ).length ) {
				countdown_html = '<div class="bookacti-countdown-container">' + bookacti_localized.cart_item_expires.replace( '%s', '<span class="bookacti-countdown" data-expiration-date="' + expiration_date + '" ></span>' ) + '</div>';
			}
		} 
		else if( booking_status === 'pending' && ! $j( this ).find( '.bookacti-wc-cart-item-status-pending' ).length ) {
			$j( this ).find( '.bookacti-countdown-container' ).remove();
			countdown_html = '<div class="bookacti-wc-cart-item-status bookacti-wc-cart-item-status-pending">' + bookacti_localized.cart_item_pending + '</div>';
		}
		
		if( countdown_html !== '' ) {
			$j( this ).find( '.wc-block-cart-item__remove-link' ).after( countdown_html );
			$j( this ).find( '.wc-block-components-order-summary-item__description' ).append( countdown_html );
		}
	});
}


