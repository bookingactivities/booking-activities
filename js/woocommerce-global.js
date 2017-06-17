$j( document ).ready( function() {
	// Update booking row after coupon refund
	$j( 'body' ).on( 'bookacti_booking_refunded', function( e, booking_id, refund_action, refund_message, refund_data, response ){
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
				var row = $j( '.bookacti-booking-actions[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );

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
								class: 'variation-bookacti_refund_coupon',
								text: bookacti_localized.coupon_code + ':'
							});
							var coupon_code_value = $j( '<dd />', {
								class: 'variation-bookacti_refund_coupon'
							}).append( $j( '<p />', { text: response.coupon_code } ) );
							row.find( '.variation' ).append( coupon_code_label );
							row.find( '.variation' ).append( coupon_code_value );
						}
					}
				}
			}
			
		}
	});
});