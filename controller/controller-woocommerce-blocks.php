<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// PRODUCT PAGES

/**
 * Move the add-to-cart form block below the multiple columns block on product pages
 * @since 1.16.8 (was bookacti_wc_move_add_to_cart_form_block)
 * @global WC_Product $product
 * @param array $parsed_block
 * @param array $source_block
 * @param WP_Block $parent_block
 * @return array
 */
function bookacti_wc_blocks_move_add_to_cart_form( $parsed_block, $source_block, $parent_block ) {
	global $product;
	if( ! $product ) { return $parsed_block; }
	if( ! ( ! empty( $parsed_block[ 'attrs' ][ 'className' ] ) && $parsed_block[ 'attrs' ][ 'className' ] === 'woocommerce product' ) ) { return $parsed_block; }
	if( ! bookacti_product_is_activity( $product ) ) { return $parsed_block; }
	if( bookacti_get_setting_value( 'bookacti_products_settings', 'wc_product_pages_booking_form_location' ) !== 'form_below' ) { return $parsed_block; }
	
	// Find blocks
	$add_to_cart_form_block = bookacti_find_parsed_block_recursively( $parsed_block, 'woocommerce/add-to-cart-form' );
	$product_details_block  = bookacti_find_parsed_block_recursively( $parsed_block, 'woocommerce/product-details' );
	$columns_block          = bookacti_find_parsed_block_recursively( $parsed_block, 'core/columns' );
	if( ! $add_to_cart_form_block ) {
		$add_to_cart_form_block = (array) new WP_Block_Parser_Block( 'woocommerce/add-to-cart-form', array(), array(), '', array() );
	}
	
	// Remove add-to-cart form block from undesired positions
	if( $product_details_block || $columns_block ) {
		$parsed_block = bookacti_remove_parsed_block_recursively( $parsed_block, 'woocommerce/add-to-cart-form' );
	}
	
	// Insert add-to-cart form block at the desired position
	if( $product_details_block ) {
		$parsed_block = bookacti_insert_parsed_block_recursively( $parsed_block, $add_to_cart_form_block, 'woocommerce/product-details', 'before', true );
	} else if( $columns_block ) {
		$parsed_block = bookacti_insert_parsed_block_recursively( $parsed_block, $add_to_cart_form_block, 'core/columns', 'after', true );
	}
	
	return $parsed_block;
}
add_filter( 'render_block_data', 'bookacti_wc_blocks_move_add_to_cart_form', 100, 3 );




// CART

/**
 * Register WC Store API endpoint for Cart Item to add cart item data
 * @since 1.16.9
 */
function bookacti_wc_store_api_register_endpoint_cart_item() {
	if( ! did_action( 'woocommerce_blocks_loaded' ) ) { return; }
	
	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => 'cart-item', // Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER
			'namespace'       => 'booking-activities',
			'data_callback'   => function( $cart_item ) {
				$is_booking         = false;
				$status             = '';
				$expiration_dt      = null;
				$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item[ 'key' ] );
				
				if( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ] ) ) {
					// All bookings should have the same status and expiration date
					foreach( $cart_item_bookings[ 0 ][ 'bookings' ] as $booking ) {
						$status = $booking->state;
						if( ! empty( $booking->expiration_date ) ) {
							$booking_expiration = new DateTime( $booking->expiration_date, new DateTimeZone( 'UTC' ) );
							if( ! $expiration_dt || ( $expiration_dt && $booking_expiration < $expiration_dt ) ) { 
								$expiration_dt = clone $booking_expiration;
							}
						}
					}
					$is_booking = true;
				}
				if( ! $expiration_dt || ! in_array( $status, array( 'in_cart', 'pending' ), true ) ) { 
					$expiration_dt = null;
				}
				
				return array(
					'is_booking'      => $is_booking,
					'booking_status'  => $status ? $status : '',
					'expiration_date' => $expiration_dt ? $expiration_dt->format( 'Y-m-d\TH_i_s' ) : ''
				);
			},
			'schema_callback' => function() {
				return array(
					'properties' => array(
						'is_booking' => array(
							'type' => 'boolean'
						),
						'booking_status' => array(
							'type' => 'string'
						),
						'expiration_date' => array(
							'type' => 'string'
						)
					),
				);
			},
			'schema_type'     => ARRAY_A,
		)
	);
}
add_action( 'init', 'bookacti_wc_store_api_register_endpoint_cart_item' );


/**
 * If quantity changes in cart via Strore API, temporarily book the extra quantity if possible
 * TEMP FIX - Waiting for a quantity validation filter (https://github.com/woocommerce/woocommerce/pull/45489)
 * @since 1.16.0
 * @version 1.16.21
 * @param mixed $result
 * @param WP_REST_Server $server
 * @param WP_REST_Request $request
 * @return mixed
 */
function bookacti_wc_store_api_update_cart_item_quantity( $result, $server, $request ) {
	if( $request->get_route() !== '/wc/store/v1/cart/update-item' ) { return $result; }
	
	$cart_item_key    = $request->get_param( 'key' );
	$desired_quantity = intval( $request->get_param( 'quantity' ) );
	if( ! $cart_item_key || ! $desired_quantity ) { return $result; }
	
	// Do not calculate total now, it will be calculated on woocommerce_stock_amount_cart_item
	$removed_hook = remove_action( 'woocommerce_before_calculate_totals', 'bookacti_wc_update_cart_items_price', 100 );
	
	// Load WooCommerce cart from a WP_REST_Request
	$cart = bookacti_wc_load_cart_from_rest_request( $request );
	
	// Add back the hook we removed earlier
	if( $removed_hook ) {
		add_action( 'woocommerce_before_calculate_totals', 'bookacti_wc_update_cart_items_price', 100, 1 );
	}
	
	$item = $cart ? $cart->get_cart_item( $cart_item_key ) : false;
	if( ! $item ) { return $result; }
	
	// Trigger woocommerce_stock_amount_cart_item like in the normal process to update cart quantity 
	$new_quantity = apply_filters( 'woocommerce_stock_amount_cart_item', $desired_quantity, $cart_item_key );
	
	$errors     = array();
	$wc_notices = wc_get_notices( 'error' );
	if( $wc_notices ) {
		foreach( $wc_notices as $wc_notice ) {
			$errors[] = $wc_notice[ 'notice' ];
		}
		wc_clear_notices();
	}
	
	if( $errors ) {
		$wp_error = new WP_Error( 'bookacti_wc_store_api_invalid_booking_quantity', '<ul><li>' . implode( '<li>', $errors ) . '</ul>', array( 'status' => 400, 'data' => array() ) );
		$result   = rest_convert_error_to_response( $wp_error );
	}
	
	return $result;
}
add_filter( 'rest_pre_dispatch', 'bookacti_wc_store_api_update_cart_item_quantity', 10, 3 );




// CHECKOUT

/**
 * Change order bookings status after the customer validates checkout via Store API
 * @since 1.16.0
 * @param WC_Order $order
 */
function bookacti_wc_store_api_checkout_order_processed_booking_status( $order ) {
	bookacti_wc_checkout_order_processed_booking_status( $order->get_id(), array(), $order );
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bookacti_wc_store_api_checkout_order_processed_booking_status', 10, 1 );