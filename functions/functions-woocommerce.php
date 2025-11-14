<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CART

/**
 * Add bookings to cart item or merge the bookings to an existing cart item
 * @since 1.9.0
 * @version 1.16.30
 * @global woocommerce $woocommerce
 * @param array $product_bookings_data
 * @return array
 */
function bookacti_wc_add_bookings_to_cart( $product_bookings_data ) {
	$return_array = array( 'status' => 'failed', 'bookings' => array(), 'booking_ids' => array(), 'booking_group_ids' => array() );
	
	global $woocommerce;
	$cart_items          = $woocommerce->cart->get_cart();
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings( $cart_items );
	
	// Check if one of the cart items is identical
	foreach( $cart_items as $cart_item_key => $cart_item ) {
		// Same product
		if( $product_bookings_data[ 'product_id' ] !== $cart_item[ 'product_id' ] ) { continue; }
		
		// Same variation
		if( ( empty( $product_bookings_data[ 'variation_id' ] ) && ! empty( $cart_item[ 'variation_id' ] ) )
		||  ( ! empty( $product_bookings_data[ 'variation_id' ] ) && ( empty( $cart_item[ 'variation_id' ] ) || $product_bookings_data[ 'variation_id' ] !== $cart_item[ 'variation_id' ] ) ) ) { continue; }
		
		// Same status and same events
		$cart_item_bookings = isset( $cart_items_bookings[ $cart_item_key ] ) ? $cart_items_bookings[ $cart_item_key ] : array();
		$cart_item_events   = array();
		foreach( $cart_item_bookings as $cart_item_booking ) {
			foreach( $cart_item_booking[ 'bookings' ] as $booking ) {
				if( ! ( isset( $booking->state ) && $booking->state === 'in_cart' ) ) { continue; }
				$cart_item_events[] = array(
					'group_id'   => $cart_item_booking[ 'type' ] === 'group' && ! empty( $booking->event_group_id ) ? $booking->event_group_id : 0,
					'group_date' => $cart_item_booking[ 'type' ] === 'group' && ! empty( $booking->group_date ) ? $booking->group_date : '',
					'id'         => ! empty( $booking->event_id ) ? $booking->event_id : 0,
					'start'      => ! empty( $booking->event_start ) ? $booking->event_start : '',
					'end'        => ! empty( $booking->event_end ) ? $booking->event_end : '',
				);
			}
		}
		if( ! $cart_item_events ) { continue; }
		
		// Same booked events
		$cart_item_events = bookacti_format_picked_events( $cart_item_events );
		if( bookacti_diff_picked_events( $product_bookings_data[ 'picked_events' ], $cart_item_events ) ) { continue; }
		
		// Same Third-party data
		if( ! apply_filters( 'bookacti_merge_cart_item', true, $cart_item, $product_bookings_data ) ) { continue; }
		
		// If a cart item is identical, we just need to increase its quantity and its bookings quantities
		$new_quantity = $cart_item[ 'quantity' ] + $product_bookings_data[ 'quantity' ];
		$updated = bookacti_wc_update_cart_item_bookings_quantity( $cart_item_key, $new_quantity );
		
		if( $updated ) {
			$return_array[ 'status' ] = 'success';
			$return_array[ 'bookings' ] = bookacti_maybe_decode_json( $cart_item[ '_bookacti_options' ][ 'bookings' ], true );
			$return_array[ 'merged_cart_item_key' ] = $cart_item_key;
			
			foreach( $return_array[ 'bookings' ] as $cart_item_booking ) {
				if( empty( $cart_item_booking[ 'id' ] ) ) { continue; }
				if( $cart_item_booking[ 'type' ] === 'group' ) {
					$return_array[ 'booking_group_ids' ][] = intval( $cart_item_booking[ 'id' ] );
				} else {
					$return_array[ 'booking_ids' ][] = intval( $cart_item_booking[ 'id' ] );
				}
			}
		}
		
		break;
	}
	
	if( ! empty( $return_array[ 'merged_cart_item_key' ] ) ) { return $return_array; }
	
	// Keep one entry per group
	$picked_events = bookacti_format_picked_events( $product_bookings_data[ 'picked_events' ], true );
	
	foreach( $picked_events as $picked_event ) {
		// Single Booking
		if( ! $picked_event[ 'group_id' ] ) {
			$booking_data = bookacti_sanitize_booking_data( array( 
				'user_id'         => $product_bookings_data[ 'user_id' ],
				'form_id'         => $product_bookings_data[ 'form_id' ],
				'event_id'        => $picked_event[ 'events' ][ 0 ][ 'id' ],
				'event_start'     => $picked_event[ 'events' ][ 0 ][ 'start' ],
				'event_end'       => $picked_event[ 'events' ][ 0 ][ 'end' ],
				'quantity'        => $product_bookings_data[ 'quantity' ],
				'status'          => $product_bookings_data[ 'status' ],
				'payment_status'  => $product_bookings_data[ 'payment_status' ],
				'expiration_date' => $product_bookings_data[ 'expiration_date' ],
				'active'          => 'according_to_status'
			) );
			$booking_id = bookacti_insert_booking( $booking_data );
			if( $booking_id ) {
				do_action( 'bookacti_wc_product_booking_form_booking_inserted', $booking_id, 'single', $picked_event, $product_bookings_data );
				$return_array[ 'bookings' ][] = array( 'id' => $booking_id, 'type' => 'single', 'picked_event' => $picked_event );
				$return_array[ 'booking_ids' ][] = $booking_id;
			}

		// Booking group
		} else {
			// Book all events of the group
			$booking_group_data = bookacti_sanitize_booking_group_data( array( 
				'user_id'         => $product_bookings_data[ 'user_id' ],
				'form_id'         => $product_bookings_data[ 'form_id' ],
				'event_group_id'  => $picked_event[ 'group_id' ],
				'group_date'      => $picked_event[ 'group_date' ],
				'grouped_events'  => $picked_event[ 'events' ],
				'quantity'        => $product_bookings_data[ 'quantity' ],
				'status'          => $product_bookings_data[ 'status' ],
				'payment_status'  => $product_bookings_data[ 'payment_status' ],
				'expiration_date' => $product_bookings_data[ 'expiration_date' ],
				'active'          => 'according_to_status'
			) );
			$booking_group_id = bookacti_book_group_of_events( $booking_group_data );
			if( $booking_group_id ) {
				do_action( 'bookacti_wc_product_booking_form_booking_inserted', $booking_group_id, 'group', $picked_event, $product_bookings_data );
				$return_array[ 'bookings' ][] = array( 'id' => $booking_group_id, 'type' => 'group', 'picked_event' => $picked_event );
				$return_array[ 'booking_group_ids' ][] = $booking_group_id;
			}
		}
	}
	
	
	// Return success
	if( $return_array[ 'bookings' ] ) {
		$return_array[ 'status' ] = 'success';
		$return_array = apply_filters( 'bookacti_wc_product_booking_form_validated_response', $return_array, $product_bookings_data );
		do_action( 'bookacti_wc_product_booking_form_validated', $return_array, $product_bookings_data );
	}
	else {
		$return_array[ 'message' ] = esc_html__( 'An error occurred while trying to add a booking to cart.', 'booking-activities' );
	}
	
	return $return_array;
}


/**
 * Get in cart bookings per cart item
 * @since 1.9.0
 * @version 1.16.0
 * @global woocommerce $woocommerce
 * @param array $cart_items
 * @param array $filters
 * @return array
 */
function bookacti_wc_get_cart_items_bookings( $cart_items = array(), $filters = array() ) {
	global $woocommerce;
	if( ! $cart_items && ! empty( $woocommerce->cart ) ) { $cart_items = $woocommerce->cart->get_cart(); }
	$in__cart_item_key = ! empty( $filters[ 'in__cart_item_key' ] ) ? $filters[ 'in__cart_item_key' ] : array();
	$cart_item_keys_by_booking_id = array();
	$cart_item_keys_by_booking_group_id = array();
	if( $cart_items ) {
		foreach( $cart_items as $cart_item_key => $cart_item ) {
			if( empty( $cart_item[ '_bookacti_options' ][ 'bookings' ] ) ) { continue; }
			if( $in__cart_item_key && ! in_array( $cart_item_key, $in__cart_item_key, true ) ) { continue; }
			$cart_item_bookings_ids = bookacti_maybe_decode_json( $cart_item[ '_bookacti_options' ][ 'bookings' ], true );
			foreach( $cart_item_bookings_ids as $cart_item_booking_id ) {
				if( $cart_item_booking_id[ 'type' ] === 'single' )     { $cart_item_keys_by_booking_id[ $cart_item_booking_id[ 'id' ] ] = $cart_item_key; }
				else if( $cart_item_booking_id[ 'type' ] === 'group' ) { $cart_item_keys_by_booking_group_id[ $cart_item_booking_id[ 'id' ] ] = $cart_item_key; }
			}
		}
	}
	
	$bookings = array();
	if( $cart_item_keys_by_booking_id || $cart_item_keys_by_booking_group_id ) {
		$filters = apply_filters( 'bookacti_wc_cart_items_bookings_filters', bookacti_format_booking_filters( array_merge( $filters, array( 
			'in__booking_id'            => array_keys( $cart_item_keys_by_booking_id ), 
			'in__booking_group_id'      => array_keys( $cart_item_keys_by_booking_group_id ), 
			'booking_group_id_operator' => 'OR',
			'order_by'                  => array( 'event_start' ),
			'order'                     => 'asc',
			'fetch_meta'                => true
		) ) ) );
		$bookings = bookacti_get_bookings( $filters );
	}
	
	$cart_items_bookings = array();
	if( $bookings ) {
		// Get booking groups
		$group_ids = array();
		foreach( $bookings as $booking ) {
			$group_id = $booking->group_id ? intval( $booking->group_id ) : 0;
			if( $group_id ) { $group_ids[] = $group_id; }
		}
		$group_ids      = bookacti_ids_to_array( $group_ids );
		$group_filters  = $group_ids ? bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'fetch_meta' => true ) ) : array();
		$booking_groups = $group_filters ? bookacti_get_booking_groups( $group_filters ) : array();
		
		foreach( $bookings as $booking ) {
			$booking_id = intval( $booking->id );
			$group_id = intval( $booking->group_id );
			
			$cart_item_key = '';
			$booking_type  = '';
			if( $group_id && ! empty( $cart_item_keys_by_booking_group_id[ $group_id ] ) ) { 
				$cart_item_key = $cart_item_keys_by_booking_group_id[ $group_id ];
				$booking_type  = 'group';
			} else if( ! $group_id && ! empty( $cart_item_keys_by_booking_id[ $booking_id ] ) ) { 
				$cart_item_key = $cart_item_keys_by_booking_id[ $booking_id ];
				$booking_type  = 'single';
			}
			
			if( $cart_item_key ) {
				if( ! isset( $cart_items_bookings[ $cart_item_key ] ) ) { $cart_items_bookings[ $cart_item_key ] = array(); }
				if( $booking_type === 'single' ) { $cart_items_bookings[ $cart_item_key ][] = array( 'id' => $booking_id, 'type' => 'single', 'bookings' => array( $booking ), 'booking_group' => array() ); }
				else if( $booking_type === 'group' ) { 
					$group_exists = false;
					foreach( $cart_items_bookings[ $cart_item_key ] as $i => $cart_item_booking ) {
						if( $cart_item_booking[ 'type' ] === 'group' && $cart_item_booking[ 'id' ] === $group_id ) {
							$group_exists = true;
							$cart_items_bookings[ $cart_item_key ][ $i ][ 'bookings' ][] = $booking;
						}
					}
					if( ! $group_exists ) {
						$cart_items_bookings[ $cart_item_key ][] = array( 'id' => $group_id, 'type' => 'group', 'bookings' => array( $booking ), 'booking_group' => ! empty( $booking_groups[ $group_id ] ) ? $booking_groups[ $group_id ] : array() );
					}
				}
			}
		}
	}
	
	return apply_filters( 'bookacti_wc_cart_items_bookings', $cart_items_bookings, $cart_items, $filters );
}


/**
 * Get in cart bookings per cart item
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param string $cart_item_key
 * @param array $filters
 * @return array
 */
function bookacti_wc_get_cart_item_bookings( $cart_item_key, $filters = array() ) {
	$filters[ 'in__cart_item_key' ] = array( $cart_item_key );
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings( array(), $filters );
	if( empty( $cart_items_bookings[ $cart_item_key ] ) ) { return array(); }
	return $cart_items_bookings[ $cart_item_key ];
}


/**
 * Check if we can update the quantity of a cart item bookings
 * @since 1.9.0
 * @version 1.15.11
 * @param array $cart_item_bookings
 * @param int $new_quantity
 * @return array
 */
function bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $new_quantity ) {
	$response = array( 'status' => 'failed', 'messages' => array() );
	foreach( $cart_item_bookings as $cart_item_booking ) {
		if( $cart_item_booking[ 'type' ] === 'single' ) {
			$response = bookacti_booking_quantity_can_be_changed( $cart_item_booking[ 'bookings' ][ 0 ], $new_quantity );
		}
		else if( $cart_item_booking[ 'type' ] === 'group' ) {
			$response = bookacti_booking_group_quantity_can_be_changed( $cart_item_booking[ 'bookings' ], $new_quantity );
		}
		if( $response[ 'status' ] === 'failed' ) { break; }
	}
	return apply_filters( 'bookacti_wc_validate_cart_item_bookings_new_quantity', $response, $cart_item_bookings, $new_quantity );
}


/**
 * Check if we can update the user of a cart item bookings
 * @since 1.9.0
 * @version 1.15.11
 * @param array $cart_item_bookings
 * @param string|int $new_user_id
 * @return array
 */
function bookacti_wc_validate_cart_item_bookings_new_user( $cart_item_bookings, $new_user_id ) {
	$response = array( 'status' => 'failed', 'messages' => array() );
	foreach( $cart_item_bookings as $cart_item_booking ) {
		if( $cart_item_booking[ 'type' ] === 'single' ) {
			$response = bookacti_booking_user_can_be_changed( $cart_item_booking[ 'bookings' ][ 0 ], $new_user_id );
		}
		else if( $cart_item_booking[ 'type' ] === 'group' ) {
			$response = bookacti_booking_group_user_can_be_changed( $cart_item_booking[ 'bookings' ], $new_user_id );
		}
		if( $response[ 'status' ] === 'failed' ) { break; }
	}
	return apply_filters( 'bookacti_wc_validate_cart_item_bookings_new_user', $response, $cart_item_bookings, $new_user_id );
}


/**
 * Update the bookings bound to a cart item
 * @since 1.9.0
 * @version 1.16.45
 * @global woocommerce $woocommerce
 * @param string $cart_item_key
 * @param array $new_data
 * @param array $args
 * @return int
 */
function bookacti_wc_update_cart_item_bookings( $cart_item_key, $new_data, $args = array() ) {
	global $woocommerce;
	$item = is_string( $cart_item_key ) ? $woocommerce->cart->get_cart_item( $cart_item_key ) : array();
	if( ! $item ) { return 0; }
	if( empty( $item[ '_bookacti_options' ][ 'bookings' ] ) ) { return 0; }
	
	$default_args = array( 'is_admin' => false, 'context' => 'wc_update_cart_item' );
	$args         = wp_parse_args( $args, $default_args );
	
	// Get expiration data
	$cart_expiration_date      = bookacti_wc_get_cart_expiration_date();
	$is_cart_expired           = strtotime( $cart_expiration_date ) <= time();
	$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	$reset_timeout_on_change   = bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
	$is_expiration_active      = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	$timeout                   = bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
	$new_expiration_date       = $is_expiration_active && ( $reset_timeout_on_change || $is_cart_expired ) ? date( 'Y-m-d H:i:s', strtotime( '+' . $timeout . ' minutes' ) ) : '';
	
	// Update each booking
	$updated = 0;
	$item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	foreach( $item_bookings as $item_booking ) {
		$is_updated = false;
		if( $item_booking[ 'type' ] === 'single' ) {
			$sanitized_data = bookacti_sanitize_booking_data( array_merge( array( 'id' => $item_booking[ 'id' ], 'expiration_date' => $new_expiration_date ), $new_data ) );
			$is_updated     = bookacti_update_booking( $sanitized_data );
			$booking        = reset( $item_booking[ 'bookings' ] );
			
			// Trigger booking quantity change hook
			if( $is_updated && $sanitized_data[ 'quantity' ] && intval( $sanitized_data[ 'quantity' ] ) !== intval( $booking->quantity ) ) {
				do_action( 'bookacti_booking_quantity_updated', $sanitized_data[ 'quantity' ], $booking, $args );
			}

			// Trigger booking status change hook
			if( $is_updated && $sanitized_data[ 'status' ] && $booking->state !== $sanitized_data[ 'status' ] ) {
				do_action( 'bookacti_booking_status_changed', $sanitized_data[ 'status' ], $booking, $args );
			}
		}
		else if( $item_booking[ 'type' ] === 'group' ) {
			$sanitized_data = bookacti_sanitize_booking_group_data( array_merge( array( 'id' => $item_booking[ 'id' ], 'expiration_date' => $new_expiration_date ), $new_data ) );
			$is_updated1    = bookacti_update_booking_group_bookings( $sanitized_data );
			$is_updated2    = bookacti_update_booking_group( $sanitized_data );
			$is_updated     = $is_updated1 || $is_updated2;
			
			$old_group_status   = ! empty( $item_booking[ 'booking_group' ]->state ) ? $item_booking[ 'booking_group' ]->state : $item_booking[ 'bookings' ][ 0 ]->state;
			$old_group_quantity = ! empty( $item_booking[ 'booking_group' ]->quantity ) ? intval( $item_booking[ 'booking_group' ]->quantity ) : intval( $item_booking[ 'bookings' ][ 0 ]->quantity );
			$new_group_quantity = $sanitized_data[ 'quantity' ] > 0 ? $sanitized_data[ 'quantity' ] : 0;
			
			// Trigger booking group quantity change hook
			if( $is_updated && $new_group_quantity && $old_group_quantity !== $new_group_quantity ) {
				do_action( 'bookacti_booking_group_quantity_updated', $new_group_quantity, $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], $args );
			}

			// Trigger booking group status change hook
			if( $is_updated && $sanitized_data[ 'status' ] && $old_group_status !== $sanitized_data[ 'status' ] ) {
				do_action( 'bookacti_booking_group_status_changed', $sanitized_data[ 'status' ], $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], $args );
			}
		}
		
		if( $is_updated ) { 
			do_action( 'bookacti_wc_cart_item_booking_updated', $item_booking, $sanitized_data, $item, $new_data, $args );
			++$updated;
		}
	}
	
	// Update cart expiration date if needed
	if( $updated
	&&  $is_expiration_active 
	&&  ( $reset_timeout_on_change || $is_cart_expired ) 
	&&  ! $is_per_product_expiration ) {
		bookacti_wc_reset_cart_expiration_date( $new_expiration_date );
	}
	
	return $updated;
}


/**
 * Update the bookings quantity bound to a cart item
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param string|array $cart_item_key Cart item key or Cart item itself
 * @param int $new_quantity
 * @return int
 */
function bookacti_wc_update_cart_item_bookings_quantity( $cart_item_key, $new_quantity ) {
	$new_data = array( 'quantity' => $new_quantity );
	$updated = bookacti_wc_update_cart_item_bookings( $cart_item_key, $new_data );
	return $updated;
}


/**
 * Update the bookings quantity bound to a cart item
 * @since 1.9.0
 * @version 1.16.0
 * @global woocommerce $woocommerce
 * @param string||array $cart_item_key Cart item key or Cart item itself
 * @param string $new_status
 * @param string $new_expiration_date
 * @return int
 */
function bookacti_wc_update_cart_item_bookings_status( $cart_item_key, $new_status, $new_expiration_date = '' ) {
	$new_data = array( 
		'status' => $new_status,
		'active' => in_array( $new_status, bookacti_get_active_booking_statuses(), true ) ? 1 : 0
	);
	if( $new_expiration_date ) { $new_data[ 'expiration_date' ] = $new_expiration_date; }
	$updated = bookacti_wc_update_cart_item_bookings( $cart_item_key, $new_data );
	return $updated;
}


/**
 * Update in_cart bookings status to removed if they are no longer in cart
 * @since 1.16.4
 * @version 1.16.30
 * @param array $cart_items
 * @param string $new_status
 * @return int
 */
function bookacti_wc_update_in_cart_bookings_status_not_in_cart_items( $cart_items, $new_status = 'removed' ) {
	$customer_id = is_user_logged_in() ? get_current_user_id() : WC()->session->get_customer_id();
	if( ! $customer_id ) { return 0; }
	
	$not_in__booking_id = $not_in__booking_group_id = array();
	if( $cart_items && is_array( $cart_items ) ) {
		foreach( $cart_items as $cart_item ) {
			$cart_item_bookings = ! empty( $cart_item[ '_bookacti_options' ][ 'bookings' ] ) ? bookacti_maybe_decode_json( $cart_item[ '_bookacti_options' ][ 'bookings' ], true ) : array();
			foreach( $cart_item_bookings as $cart_item_booking ) {
				if( $cart_item_booking[ 'type' ] === 'single' ) {
					$not_in__booking_id[] = intval( $cart_item_booking[ 'id' ] );
				}
				else if( $cart_item_booking[ 'type' ] === 'group' ) {
					$not_in__booking_group_id[] = intval( $cart_item_booking[ 'id' ] );
				}
			}
		}
	}
	
	$filters  = bookacti_format_booking_filters( array( 'user_id' => $customer_id, 'status' => array( 'in_cart' ), 'not_in__booking_id' => $not_in__booking_id, 'not_in__booking_group_id' => $not_in__booking_group_id, 'fetch_meta' => true ) );
	$bookings = bookacti_get_bookings( $filters );
	$groups   = bookacti_get_booking_groups( $filters );
	
	$updated_bookings_nb = $bookings ? bookacti_update_bookings_status( array_keys( $bookings ), $new_status ) : 0;
	$updated_groups_nb   = $groups ? bookacti_update_booking_groups_status( array_keys( $groups ), $new_status ) : 0;
	
	if( $new_status === 'removed' && ( $bookings || $groups ) && ( $updated_bookings_nb || $updated_groups_nb ) ) {
		$group_filters      = $groups ? bookacti_format_booking_filters( array( 'in__booking_group_id' => array_keys( $groups ), 'fetch_meta' => true ) ) : array();
		$groups_bookings    = $group_filters ? bookacti_get_bookings( $group_filters ) : array();
		$bookings_per_group = array();
		foreach( $groups_bookings as $booking ) {
			if( ! isset( $bookings_per_group[ $booking->group_id ] ) ) { $bookings_per_group[ $booking->group_id ] = array(); }
			$bookings_per_group[ $booking->group_id ][] = $booking;
		}
		
		do_action( 'bookacti_wc_in_cart_bookings_not_in_cart_items_removed', $updated_bookings_nb ? $bookings : array(), $updated_groups_nb ? $groups : array(), $updated_groups_nb ? $bookings_per_group : array() );
	}
	
	return intval( $updated_bookings_nb ) + intval( $updated_groups_nb );
}


/**
 * Load WooCommerce cart from a WP_REST_Request
 * @param WP_REST_Request $request
 * @since 1.16.0
 * @version 1.16.6
 * @param WP_REST_Request $request
 * @return boolean
 */
function bookacti_wc_load_cart_from_rest_request( $request ) {
	if( class_exists( 'Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken' )
	&&  class_exists( 'Automattic\WooCommerce\StoreApi\SessionHandler' ) ) {
		$cart_token = $request->get_header( 'Cart-Token' );
		if( $cart_token && Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken::validate( $cart_token, '@' . wp_salt() ) ) {
			// Overrides the core session class.
			add_filter(
				'woocommerce_session_handler',
				function() {
					return 'Automattic\WooCommerce\StoreApi\SessionHandler';
				}
			);
		}
	}
	
	if( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}
	
	$cart = wc()->cart;
	if( ! $cart ) { return false; }
	
	$cart->get_cart();
	$cart->calculate_fees();
	$cart->calculate_shipping();
	$cart->calculate_totals();
	
	return $cart;
}




// CART EXPIRATION

/**
 * Check if the booking has expired
 * @version 1.12.0
 * @param object $booking
 * @return boolean
 */
function bookacti_is_expired_booking( $booking ) {
	if( empty( $booking->expiration_date ) || empty( $booking->active ) ) { return true; }
	
	$expired = false;
	$now_dt = new DateTime();
	$expiry_dt = new DateTime( $booking->expiration_date );
	
	if( $booking->state === 'in_cart' && $expiry_dt <= $now_dt ) { 
		$expired = true;
		bookacti_deactivate_expired_bookings(); // Deactivate the expired booking now
	}
	
	return $expired;
}


/**
 * Reset expiration dates of all cart items
 * @since 1.9.0 (was bookacti_reset_cart_expiration_dates)
 * @global woocommerce $woocommerce
 * @param string $expiration_date
 * @return int|false
 */
function bookacti_wc_reset_cart_expiration_date( $expiration_date ) {
	global $woocommerce;

	$updated = 0;
	$cart_contents = $woocommerce->cart->get_cart();
	if( ! $cart_contents ) { bookacti_wc_set_cart_expiration_date( null ); return $updated; }

	bookacti_wc_set_cart_expiration_date( $expiration_date );
	
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings();
	$booking_id_array = array();
	
	foreach( $cart_items_bookings as $cart_item_key => $cart_item_bookings ) {
		foreach( $cart_item_bookings as $cart_item_booking ) {
			foreach( $cart_item_booking[ 'bookings' ] as $booking ) {
				$booking_id_array[] = $booking->id;
			}
		}
	}

	$user_id = $woocommerce->session->get_customer_id();
	if( is_user_logged_in() ) { $user_id = get_current_user_id(); }

	$updated = bookacti_update_in_cart_bookings_expiration_date( $user_id, $booking_id_array, $expiration_date );

	return $updated;
}


/**
 * Get the expiration time for a newly created cart item
 * @since 1.9.0 (was bookacti_get_expiration_time)
 * @global WooCommerce $woocommerce
 * @param string $date_format
 * @return string
 */
function bookacti_wc_get_new_cart_item_expiration_date() {
	$expiration_date = '';
	
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return $expiration_date; }
	
	$timeout = bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
	$expiration_date = date( 'Y-m-d H:i:s', strtotime( '+' . $timeout . ' minutes' ) );
	
	$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	if( ! $is_per_product_expiration ) {
		global $woocommerce;
		
		$cart_expiration_date = bookacti_wc_get_cart_expiration_date();

		if( ! $cart_expiration_date
		|| strtotime( $cart_expiration_date ) <= time() 
		|| $woocommerce->cart->get_cart_contents_count() === 0 ) {
			bookacti_wc_set_cart_expiration_date( $expiration_date );
		} else {
			$expiration_date = $cart_expiration_date;
		}
	}

	return $expiration_date;
}


/**
 * Get cart timeout
 * @since 1.9.0 (was bookacti_get_cart_timeout)
 * @global woocommerce $woocommerce
 * @param int $user_id
 * @return string
 */
function bookacti_wc_get_cart_expiration_date( $user_id = 0 ) {
	if( is_user_logged_in() || $user_id ) {
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		$cart_expiration_date = get_user_meta( $user_id, 'bookacti_expiration_cart', true );
	} else {
		global $woocommerce;
		$cart_expiration_date = $woocommerce->session->get( 'bookacti_expiration_cart', '' );
	}
	return $cart_expiration_date;
}


/**
 * Set cart timeout
 * @since 1.9.0 (was bookacti_set_cart_timeout)
 * @global woocommerce $woocommerce
 * @param string|null $expiration_date
 * @param int $user_id
 */
function bookacti_wc_set_cart_expiration_date( $expiration_date, $user_id = 0 ) {
	if( is_user_logged_in() || $user_id ) {
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		update_user_meta( $user_id, 'bookacti_expiration_cart', $expiration_date );
	} else {
		global $woocommerce;
		$woocommerce->session->set( 'bookacti_expiration_cart', $expiration_date );
	}
}


/**
 * Get timeout for a cart item
 * @since 1.9.0 (was bookacti_get_cart_item_timeout)
 * @version 1.16.9
 * @global woocommerce $woocommerce
 * @param string $cart_item_key
 * @return string
 */
function bookacti_wc_get_cart_item_countdown_html( $cart_item_key ) {
	global $woocommerce;
	$item = $woocommerce->cart->get_cart_item( $cart_item_key );
	if( empty( $item[ '_bookacti_options' ][ 'bookings' ] ) ) { return ''; }
	
	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	if( empty( $cart_item_bookings[ 0 ][ 'bookings' ] ) ) { return ''; }
	
	// All bookings should have the same status and expiration date
	$status = '';
	$expiration_dt = null;
	foreach( $cart_item_bookings[ 0 ][ 'bookings' ] as $booking ) {
		$status = $booking->state;
		$booking_expiration = new DateTime( $booking->expiration_date );
		if( ! $expiration_dt || ( $expiration_dt && $booking_expiration < $expiration_dt ) ) { $expiration_dt = clone $booking_expiration; }
	}
	
	if( ! $expiration_dt || ! in_array( $status, array( 'in_cart', 'pending' ), true ) ) { return ''; }

	$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	$timeout = '<div class="bookacti-cart-item-expires-with-cart"></div>';

	if( $is_per_product_expiration && $status === 'in_cart' ) {
		$timeout = '<div class="bookacti-countdown-container">'
					. '<div class="bookacti-countdown" data-expiration-date="' . esc_attr( $expiration_dt->format( 'Y-m-d H:i:s' ) ) . '" ></div>'
				. '</div>';

	} else if( $status === 'pending' ) {
		$timeout = '<div class="bookacti-wc-cart-item-status bookacti-wc-cart-item-status-pending">' . esc_html__( 'Pending payment', 'booking-activities' ) . '</div>';
	}

	return $timeout;
}


/**
 * Get formatted remaining time before expiration
 * @since 1.2.0
 * @version 1.12.9
 * @param string $expiration_date 
 * @param int $precision 
 * @return string
 */
function bookacti_get_formatted_time_before_expiration( $expiration_date, $precision = 3 ) {
	$seconds = round( abs( intval( strtotime( $expiration_date ) ) - time() ) );
	$remaining_time = bookacti_format_delay( $seconds, $precision );
	return apply_filters( 'bookacti_formatted_time_before_expiration', $remaining_time, $expiration_date, $precision );
}


/**
 * Get product price as is it should be displayed in cart (with or without tax according to settings)
 * @since 1.9.0
 * @version 1.16.45
 * @global woocommerce $woocommerce
 * @param WC_Product $product
 * @param float $price
 * @param int $qty
 * @param boolean $formatted
 * @return string|float
 */
function bookacti_wc_get_displayed_product_price( $product, $price = '', $qty = 1, $formatted = true ) {
	global $woocommerce;
	
	$display_price = $price !== '' ? floatval( $price ) : 0;
	if( $product->is_taxable() ) {
		$is_incl_tax      = ! empty( $woocommerce->cart ) ? $woocommerce->cart->display_prices_including_tax() : get_option( 'woocommerce_tax_display_cart' ) === 'incl';
		$has_subtotal_tax = ! empty( $woocommerce->cart ) ? $woocommerce->cart->get_subtotal_tax() > 0 : true;
		if( $is_incl_tax ) {
			$display_price   = wc_get_price_including_tax( $product, array( 'price' => $price, 'qty' => $qty ) );
			$formatted_price = html_entity_decode( wc_price( $display_price ) );
			if( ! wc_prices_include_tax() && $has_subtotal_tax ) {
				$formatted_price .= ' <small class="tax_label">' . $woocommerce->countries->inc_tax_or_vat() . '</small>';
			}
		} else {
			$display_price   = wc_get_price_excluding_tax( $product, array( 'price' => $price, 'qty' => $qty ) );
			$formatted_price = html_entity_decode( wc_price( $display_price ) );
			if( wc_prices_include_tax() && $has_subtotal_tax ) {
				$formatted_price .= ' <small class="tax_label">' . $woocommerce->countries->ex_tax_or_vat() . '</small>';
			}
		}
	} else {
		$display_price   = $price !== '' ? max( 0.0, (float) $price ) : $product->get_price();
		$display_price  *= $qty;
		$formatted_price = html_entity_decode( wc_price( $price ) );
	}
	
	return $formatted ? $formatted_price : $display_price;
}




// CART AND ORDER

/**
 * Get booking events list as WC item attributes
 * @since 1.9.0 (was bookacti_wc_get_item_bookings_events_list_html)
 * @version 1.16.38
 * @param array $item_booking
 * @param boolean $show_quantity
 * @param string $context
 * @return array
 */
function bookacti_wc_get_item_booking_events_attributes( $item_booking, $show_quantity = true, $context = '' ) {
	// Get the custom formatting values
	$messages            = bookacti_get_messages();
	$datetime_format     = isset( $messages[ 'date_format_short' ][ 'value' ] )   ? $messages[ 'date_format_short' ][ 'value' ] : '';
	$time_format         = isset( $messages[ 'time_format' ][ 'value' ] )         ? $messages[ 'time_format' ][ 'value' ] : '';
	$date_time_separator = isset( $messages[ 'date_time_separator' ][ 'value' ] ) ? $messages[ 'date_time_separator' ][ 'value' ] : '';
	$dates_separator     = isset( $messages[ 'dates_separator' ][ 'value' ] )     ? $messages[ 'dates_separator' ][ 'value' ] : '';
	$quantity_separator  = isset( $messages[ 'quantity_separator' ][ 'value' ] )  ? $messages[ 'quantity_separator' ][ 'value' ] : '';
	
	// Sort the bookings by dates
	$sorted_bookings = bookacti_sort_events_array_by_dates( $item_booking[ 'bookings' ], false, false, array( 'start' => 'event_start', 'end' => 'event_end' ) );
	
	// Format events
	$group_title = '';
	$formatted_events = array();
	foreach( $sorted_bookings as $booking ) {
		// Get the group title if it is a group of events
		if( ! empty( $booking->group_id ) && ! $group_title ) {
			$group_title = ! empty( $booking->group_title ) ? apply_filters( 'bookacti_translate_text', $booking->group_title ) : sprintf( esc_html__( 'Booking group #%d', 'booking-activities' ), $booking->group_id );
		}
		
		$start = isset( $booking->event_start ) ? bookacti_sanitize_datetime( $booking->event_start ) : '';
		$end   = isset( $booking->event_end ) ? bookacti_sanitize_datetime( $booking->event_end ) : '';
		
		// Format the event duration
		$duration = '';
		if( $start && $end ) {
			$start_and_end_same_day = substr( $start, 0, 10 ) === substr( $end, 0, 10 );
			
			$event_start = bookacti_format_datetime( $start, $datetime_format );
			$event_end   = $start_and_end_same_day ? bookacti_format_datetime( $end, $time_format ) : bookacti_format_datetime( $end, $datetime_format );
			$separator   = $start_and_end_same_day ? $date_time_separator : $dates_separator;
			
			$duration = $event_start . $separator . $event_end;
		}
		
		$formatted_events[] = array( 
			'uid'      => 'booking_' . $booking->id,
			'title'    => ! empty( $booking->event_title ) ? apply_filters( 'bookacti_translate_text', $booking->event_title ) : '',
			'start'    => isset( $booking->event_start ) ? bookacti_sanitize_datetime( $booking->event_start ) : '',
			'end'      => isset( $booking->event_end ) ? bookacti_sanitize_datetime( $booking->event_end ) : '',
			'quantity' => isset( $booking->quantity ) ? intval( $booking->quantity ) : '',
			'duration' => $duration
		);
	}
	
	// Add a default row to visually simulate a parent
	$events_attributes = array( 
		'bookings' => array( 
			'label' => esc_html( _n( 'Event', 'Events', count( $item_booking[ 'bookings' ] ), 'booking-activities' ) ), 
			'value' => $group_title ? $group_title : '&nbsp;',
		)
	);
	
	// Add one line per event
	foreach( $formatted_events as $i => $event ) {
		if( ! $event[ 'title' ] && ! $event[ 'duration' ] ) { continue; }
		
		$value = '';
		
		if( $event[ 'title' ] ) {
			$value .= $event[ 'title' ];
			if( $event[ 'duration' ] ) {
				$value .= ' ';
			}
		}
		
		if( $event[ 'duration' ] ) {
			$value .= $event[ 'duration' ];
		}

		if( $event[ 'quantity' ] && $show_quantity ) {
			$value .= $quantity_separator . $event[ 'quantity' ];
		}
		
		if( count( $item_booking[ 'bookings' ] ) > 1 ) {
			$events_attributes[ $event[ 'uid' ] ] = array( 
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;&bull;', 
				'value' => $value
			);
		}
		else {
			$events_attributes[ 'bookings' ][ 'value' ] = $value;
		}
	}
	
	return apply_filters( 'bookacti_wc_item_booking_events_attributes', $events_attributes, $item_booking, $show_quantity, $context );
}


/**
 * Get booking refunds as WC item attributes
 * @since 1.16.38
 * @param array $refunds
 * @param string $context
 * @return string
 */
function bookacti_wc_get_item_booking_refunds_attributes( $refunds, $context = '' ) {
	$html = '';
	if( ! $refunds ) { return $html; }
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
	try { $timezone_obj = new DateTimeZone( $timezone ); }
	catch ( Exception $ex ) { $timezone_obj = clone $utc_timezone_obj; }
	
	// Add a default row to visually simulate a parent
	$refunds_attributes = array();
	
	foreach( $refunds as $i => $refund ) {
		$refund_id      = $i;
		$date_formatted = '';
		if( ! empty( $refund[ 'date' ] ) && bookacti_sanitize_datetime( $refund[ 'date' ] ) ) { 
			$datetime_obj = DateTime::createFromFormat( 'Y-m-d H:i:s', $refund[ 'date' ], $utc_timezone_obj );
			$datetime_obj->setTimezone( $timezone_obj );
			$date_formatted = bookacti_format_datetime( $datetime_obj->format( 'Y-m-d H:i:s' ) );
		}
		
		$refund_attributes = array(
			// Add a default row to visually simulate a parent
			'refund_' . $refund_id => array( 
				'label' => esc_html__( 'Refund', 'booking-activities' ), 
				'value' => '&nbsp;',
			),
			'refund_' . $refund_id . '_date' => array(
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html__( 'Date', 'booking-activities' ),
				'value' => $date_formatted,
			),
			'refund_' . $refund_id . '_quantity' => array(
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html__( 'Quantity', 'booking-activities' ),
				'value' => ! empty( $refund[ 'quantity' ] ) ? $refund[ 'quantity' ] : '',
			),
			'refund_' . $refund_id . '_method' => array(
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html__( 'Method', 'booking-activities' ),
				'value' => ! empty( $refund[ 'method' ] ) ? bookacti_get_refund_label( $refund[ 'method' ] ) : '',
			)
		);
		
		if( isset( $refund[ 'coupon' ] ) ) {
			// Check if the coupon code is valid
			$coupon_code        = strtoupper( $refund[ 'coupon' ] );
			$coupon_valid       = $coupon_code ? bookacti_wc_is_coupon_code_valid( $coupon_code ) : true;
			$coupon_class       = is_wp_error( $coupon_valid ) ? 'bookacti-refund-coupon-not-valid bookacti-refund-coupon-error-' . esc_attr( $coupon_valid->get_error_code() ) : 'bookacti-refund-coupon-valid';
			$coupon_error_label = is_wp_error( $coupon_valid ) ? $coupon_valid->get_error_message() : '';

			$coupon_tip   = $coupon_error_label ? esc_attr( $coupon_error_label ) : $coupon_code;
			$coupon_label = '<span class="bookacti-refund-coupon-code ' . esc_attr( $coupon_class ) . '" title="' . $coupon_tip . '">' . $coupon_code . '</span>';

			$refund_attributes[ 'refund_' . $refund_id . '_coupon' ] = array(
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html__( 'Coupon code', 'booking-activities' ),
				'value' => $coupon_label
			);
		}
		
		if( isset( $refund[ 'amount' ] ) )	{ 
			$refund_attributes[ 'refund_' . $refund_id . '_amount' ] = array(
				'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html__( 'Amount', 'booking-activities' ),
				'value' => bookacti_format_price( $refund[ 'amount' ], array( 'plain_text' => true ) )
			);
		}
		
		$refunds_attributes = array_merge( $refunds_attributes, $refund_attributes );
	}
	
	return apply_filters( 'bookacti_wc_item_booking_refunds_attributes', $refunds_attributes, $refunds, $context );
}


/**
 * Get array of displayed attributes per booking
 * @since 1.9.0
 * @version 1.16.38
 * @global boolean $bookacti_is_email
 * @param array $item_bookings
 * @param string $context
 * @return array
 */
function bookacti_wc_get_item_bookings_attributes( $item_bookings, $context = '' ) {
	$bookings_attributes = array();
	if( ! $item_bookings ) { return $bookings_attributes; }
	
	foreach( $item_bookings as $i => $item_booking ) {
		if( empty( $item_booking[ 'id' ] ) || empty( $item_booking[ 'type' ] ) ) { continue; }
		
		// Booking ID
		$booking_attributes = array(
			'id' => array( 
				'label' => $item_booking[ 'type' ] === 'group' ? esc_html__( 'Booking group ID', 'booking-activities' ) : esc_html__( 'Booking ID', 'booking-activities' ), 
				'value' => $item_booking[ 'id' ],
				'type'  => $item_booking[ 'type' ]
			)
		);
		
		if( ! empty( $item_booking[ 'bookings' ] ) ) {
			// Booking status
			$status = $item_booking[ 'type' ] === 'group' ? ( ! empty( $item_booking[ 'bookings' ][ 0 ]->group_state ) ? $item_booking[ 'bookings' ][ 0 ]->group_state : '' ) : $item_booking[ 'bookings' ][ 0 ]->state;
			if( $status ) {
				$booking_attributes[ 'status' ] = array( 
					'label' => esc_html__( 'Status', 'booking-activities' ), 
					'value' => bookacti_format_booking_status( $status )
				);
			}
		
			// Booking events
			$booking_attributes += bookacti_wc_get_item_booking_events_attributes( $item_booking, true, $context );
			
			// Refund data
			if( ! empty( $item_booking[ 'bookings' ][ 0 ]->refunds ) ) {
				$refunds_formatted   = bookacti_format_booking_refunds( $item_booking[ 'bookings' ][ 0 ]->refunds );
				$booking_attributes += bookacti_wc_get_item_booking_refunds_attributes( $refunds_formatted, $context );
			}
		
			// Allow plugins to add more item booking attributes to be displayed (before the booking actions)
			$booking_attributes = apply_filters( 'bookacti_wc_item_booking_attributes', $booking_attributes, $item_booking, $context );
		
			// Display admin actions
			$is_order_edit_page = ( ! empty( $_REQUEST[ 'action' ] ) 
			&& in_array( $_REQUEST[ 'action' ], array( 'edit', 'woocommerce_refund_line_items', 'woocommerce_load_order_items' ), true )
			&& ! did_action( 'woocommerce_order_fully_refunded_notification' ) 
			&& ! did_action( 'woocommerce_order_partially_refunded_notification' ) );
			if( $is_order_edit_page ) {
				$actions_html_array = bookacti_wc_get_item_booking_actions_html( $item_booking, true );
				if( $actions_html_array ) {
					$booking_attributes[ 'actions' ] = array( 
						'label' => esc_html( _n( 'Action', 'Actions', count( $actions_html_array ), 'booking-activities' ) ),
						'value' => implode( ' | ', $actions_html_array ),
					);
				}
			}

			// Booking actions
			// Don't display booking actions in emails, on the backend, and on payment page
			global $bookacti_is_email;
			if( ! $bookacti_is_email && ! $is_order_edit_page && empty( $_REQUEST[ 'pay_for_order' ] ) ) {
				$actions_html_array = $item_booking[ 'type' ] === 'group' ? bookacti_get_booking_group_actions_html( $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], 'front', array(), true, true ) : ( $item_booking[ 'type' ] === 'single' ? bookacti_get_booking_actions_html( $item_booking[ 'bookings' ][ 0 ], 'front', array(), true, true ) : '' );
				if( $actions_html_array ) {
					$booking_attributes[ 'actions' ] = array( 
						'label' => esc_html( _n( 'Action', 'Actions', count( $actions_html_array ), 'booking-activities' ) ),
						'value' => implode( ' | ', $actions_html_array ),
					);
				}
			}
		}
		
		if( $booking_attributes ) {
			$index = ( $item_booking[ 'type' ] === 'group' ? 'G' : 'B' ) . $item_booking[ 'id' ];
			$bookings_attributes[ $index ] = $booking_attributes;
		}
	}
	
	return apply_filters( 'bookacti_wc_item_bookings_attributes', $bookings_attributes, $item_bookings, $context );
}


/**
 * Get item booking array of possible actions
 * @since 1.9.0
 * @version 1.13.0
 * @param array $item_booking
 * @return array
 */
function bookacti_wc_get_item_booking_actions( $item_booking ) {
	if( empty( $item_booking[ 'id' ] ) || empty( $item_booking[ 'type' ] ) ) { return array(); }
	
	// Get the link to the booking edit page
	$link_to_booking = admin_url( 'admin.php?page=bookacti_bookings&status%5B0%5D=all&keep_default_status=1' );
	if( $item_booking[ 'type' ] === 'group' ) {
		$link_to_booking .= '&booking_group_id=' . $item_booking[ 'id' ] . '&group_by=booking_group';
	} else if( $item_booking[ 'type' ] === 'single' ) {
		$link_to_booking .= '&booking_id=' . $item_booking[ 'id' ];
	}
	
	// Build the array of possible actions
	$actions = array(
		'edit_booking' => array(
			'class'       => 'bookacti-wc-order-item-edit-booking-button',
			'label'       => esc_html__( 'Edit the booking', 'booking-activities' ),
			'description' => esc_html__( 'Go to the edit page.', 'booking-activities' ),
			'link'        => $link_to_booking,
		)
	);
	
	return apply_filters( 'bookacti_wc_item_booking_actions', $actions, $item_booking );
}


/**
 * Get item booking possible actions as HTML buttons
 * @since 1.9.0
 * @version 1.16.0
 * @param array $item_booking
 * @param boolean $return_array Whether to return an array of buttons, or the concatenated buttons HTML
 * @return string
 */
function bookacti_wc_get_item_booking_actions_html( $item_booking, $return_array = false ) {
	// Get the array of possible actions
	$actions = bookacti_wc_get_item_booking_actions( $item_booking );
	
	$actions_html         = '';
	$actions_html_array   = array();
	$booking_action_class = $item_booking[ 'type' ] === 'group' ? ' bookacti-booking-group-action' : ' bookacti-booking-action';
	
	foreach( $actions as $action_id => $action ) {
		$action_html = '<a '
			. 'href="' . esc_url( $action[ 'link' ] ) . '" '
			. 'id="' . $booking_action_class . '-' . esc_attr( $action_id ) . '-' . $item_booking[ 'id' ] . '" '
			. 'class="button ' . esc_attr( $action[ 'class' ] ) . $booking_action_class . ' tips" '
			. 'data-action="' . esc_attr( $action_id ) . '" '
			. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
			. 'data-booking-type="' . $item_booking[ 'type' ] . '" '
			. 'data-booking-id="' . $item_booking[ 'id' ] . '" >'
			. esc_html( $action[ 'label' ] )
		. '</a>';
		$actions_html_array[ $action_id ] = $action_html;
	}

	// Return the array of html actions
	if( $return_array ) {
		return apply_filters( 'bookacti_wc_item_booking_actions_html_array', $actions_html_array, $actions, $item_booking );
	}

	$actions_html = implode( ' | ', $actions_html_array );
	
	return apply_filters( 'bookacti_wc_item_booking_actions_html', $actions_html, $actions, $item_booking );
}


/**
 * Get item bookings attributes IDs that shoud not be displayed on cart items
 * @since 1.9.0
 * @return array
 */
function bookacti_wc_get_hidden_cart_item_bookings_attributes() {
	return apply_filters( 'bookacti_wc_hidden_cart_item_bookings_attributes', array( 'id', 'status', 'actions' ) );
}




// ORDERS

/**
 * Format order item bookings ids array
 * @since 1.9.0
 * @version 1.16.39
 * @param array|WC_Order_Item_Product $order_item
 * @return array
 */
function bookacti_wc_format_order_item_bookings_ids( $order_item ) {
	if( ! $order_item ) { return array(); }
	
	$order_item_bookings_ids = array();
	
	// Check for deprecated data (bookacti_booking_id and bookacti_booking_group_id)
	if( empty( $order_item[ 'bookacti_bookings' ] ) ) {
		if( ! empty( $order_item[ 'bookacti_booking_id' ] ) ) { 
			$order_item_bookings_ids[] = array( 'id' => intval( $order_item[ 'bookacti_booking_id' ] ), 'type' => 'single' );
		}
		else if( ! empty( $order_item[ 'bookacti_booking_group_id' ] ) ) { 
			$order_item_bookings_ids[] = array( 'id' => intval( $order_item[ 'bookacti_booking_group_id' ] ), 'type' => 'group' );
		}
	}
	
	// Check for bookacti_bookings
	else if( ! empty( $order_item[ 'bookacti_bookings' ] ) ) {
		$order_item_bookings_ids = bookacti_maybe_decode_json( $order_item[ 'bookacti_bookings' ], true );
	}

	return is_array( $order_item_bookings_ids ) ? $order_item_bookings_ids : array();
}


/**
 * Get order bookings per order item
 * @since 1.9.0
 * @version 1.16.0
 * @global woocommerce $woocommerce
 * @param int|WC_Order|WC_Order_Item_Product[] $order_id
 * @param array $filters
 * @return array
 */
function bookacti_wc_get_order_items_bookings( $order_id, $filters = array() ) {
	// Get order items
	$order_items = is_array( $order_id ) ? $order_id : array();
	if( ! $order_items ) {
		$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : ( is_a( $order_id, 'WC_Order' ) ? $order_id : null );
		if( ! $order ) { return array(); }
		
		$order_items = $order->get_items();
		if( ! $order_items ) { return array(); }
	}
	
	$in__order_item_id = ! empty( $filters[ 'in__order_item_id' ] ) ? array_map( 'intval', $filters[ 'in__order_item_id' ] ) : array();
	$order_item_ids_by_booking_id = array();
	$order_item_ids_by_booking_group_id = array();
	foreach( $order_items as $order_item_default_id => $order_item ) {
		$order_item_raw_id = $order_item->get_id();
		$order_item_id = $order_item_raw_id ? intval( $order_item_raw_id ) : $order_item_default_id;
		if( $in__order_item_id && ! $order_item_raw_id ) { continue; }
		if( $in__order_item_id && ! in_array( $order_item_id, $in__order_item_id, true ) ) { continue; }

		$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $order_item );
		if( ! $order_item_bookings_ids ) { continue; }
		
		foreach( $order_item_bookings_ids as $order_item_booking_id ) {
			if( $order_item_booking_id[ 'type' ] === 'single' )     { $order_item_ids_by_booking_id[ $order_item_booking_id[ 'id' ] ] = $order_item_id; }
			else if( $order_item_booking_id[ 'type' ] === 'group' ) { $order_item_ids_by_booking_group_id[ $order_item_booking_id[ 'id' ] ] = $order_item_id; }
		}
	}
	
	$bookings = array();
	if( $order_item_ids_by_booking_id || $order_item_ids_by_booking_group_id ) {
		$filters = apply_filters( 'bookacti_wc_order_items_bookings_filters', bookacti_format_booking_filters( array_merge( $filters, array( 
			'in__booking_id'            => array_keys( $order_item_ids_by_booking_id ), 
			'in__booking_group_id'      => array_keys( $order_item_ids_by_booking_group_id ), 
			'booking_group_id_operator' => 'OR',
			'fetch_meta'                => true
		) ) ) );
		$bookings = bookacti_get_bookings( $filters );
	}
	if( ! $bookings ) { return array(); }
	
	// Get booking groups
	$group_ids = array();
	foreach( $bookings as $booking ) {
		$group_id = $booking->group_id ? intval( $booking->group_id ) : 0;
		if( $group_id ) { $group_ids[] = $group_id; }
	}
	$group_ids      = bookacti_ids_to_array( $group_ids );
	$group_filters  = $group_ids ? bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'fetch_meta' => true ) ) : array();
	$booking_groups = $group_filters ? bookacti_get_booking_groups( $group_filters ) : array();
	
	$order_items_bookings = array();
	
	foreach( $bookings as $booking ) {
		$booking_id = intval( $booking->id );
		$group_id   = intval( $booking->group_id );

		$order_item_id = 0;
		$booking_type  = '';
		if( $group_id && ! empty( $order_item_ids_by_booking_group_id[ $group_id ] ) ) { 
			$order_item_id = $order_item_ids_by_booking_group_id[ $group_id ];
			$booking_type  = 'group';
		} else if( ! $group_id && ! empty( $order_item_ids_by_booking_id[ $booking_id ] ) ) { 
			$order_item_id = $order_item_ids_by_booking_id[ $booking_id ];
			$booking_type  = 'single';
		}

		if( $order_item_id ) {
			if( ! isset( $order_items_bookings[ $order_item_id ] ) ) { $order_items_bookings[ $order_item_id ] = array(); }
			if( $booking_type === 'single' ) { $order_items_bookings[ $order_item_id ][] = array( 'id' => $booking_id, 'type' => 'single', 'bookings' => array( $booking ), 'booking_group' => array() ); }
			else if( $booking_type === 'group' ) { 
				$group_exists = false;
				foreach( $order_items_bookings[ $order_item_id ] as $i => $order_item_booking ) {
					if( $order_item_booking[ 'type' ] === 'group' && $order_item_booking[ 'id' ] === $group_id ) {
						$group_exists = true;
						$order_items_bookings[ $order_item_id ][ $i ][ 'bookings' ][] = $booking;
					}
				}
				if( ! $group_exists ) {
					$order_items_bookings[ $order_item_id ][] = array( 'id' => $group_id, 'type' => 'group', 'bookings' => array( $booking ), 'booking_group' => ! empty( $booking_groups[ $group_id ] ) ? $booking_groups[ $group_id ] : array() );
				}
			}
		}
	}
	
	return apply_filters( 'bookacti_wc_order_items_bookings', $order_items_bookings, $order_items, $filters );
}


/**
 * Check if we can update the quantity of an order item bookings
 * @since 1.9.0
 * @version 1.15.11
 * @param array $order_item_bookings
 * @param int $new_quantity
 * @return array
 */
function bookacti_wc_validate_order_item_bookings_new_quantity( $order_item_bookings, $new_quantity ) {
	$response = array( 'status' => 'failed', 'messages' => array() );
	foreach( $order_item_bookings as $order_item_booking ) {
		if( $order_item_booking[ 'type' ] === 'single' ) {
			$response = bookacti_booking_quantity_can_be_changed( $order_item_booking[ 'bookings' ][ 0 ], $new_quantity );
		}
		else if( $order_item_booking[ 'type' ] === 'group' ) {
			$response = bookacti_booking_group_quantity_can_be_changed( $order_item_booking[ 'bookings' ], $new_quantity );
		}
		if( $response[ 'status' ] === 'failed' ) { break; }
	}
	return apply_filters( 'bookacti_wc_validate_order_item_bookings_new_quantity', $response, $order_item_bookings, $new_quantity );
}


/**
 * Update bookings attached to all order items
 * @since 1.9.0
 * @version 1.16.45
 * @param int|WC_Order $order_id
 * @param array $new_data
 * @param array $where
 * @param array $args
 * @return array
 */
function bookacti_wc_update_order_items_bookings( $order_id, $new_data, $where = array(), $args = array() ) {
	$updated = array( 'updated' => 0, 'booking_ids' => array(), 'booking_group_ids' => array() );
	
	$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : ( is_a( $order_id, 'WC_Order' ) ? $order_id : null );
	if( ! $order || ! $new_data ) { return $updated; }
	
	$default_args = array( 'context' => 'wc_order_status_changed' );
	$args         = wp_parse_args( $args, $default_args );
	
	// Sanitize where clauses
	$in__order_item_id    = ! empty( $where[ 'in__order_item_id' ] ) ? array_map( 'intval', $where[ 'in__order_item_id' ] ) : array();
	$in__booking_id       = ! empty( $where[ 'in__booking_id' ] ) ? array_map( 'intval', $where[ 'in__booking_id' ] ) : array();
	$in__booking_group_id = ! empty( $where[ 'in__booking_group_id' ] ) ? array_map( 'intval', $where[ 'in__booking_group_id' ] ) : array();
	$in__status           = ! empty( $where[ 'in__status' ] ) ? $where[ 'in__status' ] : array();
	$in__order_id         = ! empty( $where[ 'in__order_id' ] ) ? $where[ 'in__order_id' ] : array();
	
	// Get bookings
	$order_items_bookings = bookacti_wc_get_order_items_bookings( $order_id, array( 'in__order_item_id' => $in__order_item_id, 'in__order_id' => $in__order_id ) );
	if( ! $order_items_bookings ) { return $updated; }
	
	foreach( $order_items_bookings as $order_item_id => $item_bookings ) {
		if( $in__order_item_id && ! in_array( $order_item_id, $in__order_item_id, true ) ) { continue; }
		if( ! $item_bookings ) { continue; }
		
		foreach( $item_bookings as $item_booking ) {
			$is_updated = false;
			if( $item_booking[ 'type' ] === 'single' ) {
				$booking = reset( $item_booking[ 'bookings' ] );
				$status  = $booking->state;
				if( $in__status && ! in_array( $status, $in__status, true ) ) { continue; }
				if( $in__booking_id && ! in_array( $item_booking[ 'id' ], $in__booking_id, true ) ) { continue; }
				
				$sanitized_data = bookacti_sanitize_booking_data( array_merge( array( 'id' => $item_booking[ 'id' ] ), $new_data ) );
				$is_updated     = bookacti_update_booking( $sanitized_data );
				
				// Trigger booking quantity change hook
				if( $is_updated && $sanitized_data[ 'quantity' ] && intval( $sanitized_data[ 'quantity' ] ) !== intval( $booking->quantity ) ) {
					do_action( 'bookacti_booking_quantity_updated', $sanitized_data[ 'quantity' ], $booking, $args );
				}

				// Trigger booking status change hook
				if( $is_updated && $sanitized_data[ 'status' ] && $booking->state !== $sanitized_data[ 'status' ] ) {
					do_action( 'bookacti_booking_status_changed', $sanitized_data[ 'status' ], $booking, $args );
				}
				
				if( $is_updated ) {
					$updated[ 'booking_ids' ][] = $item_booking[ 'id' ];
				}
			}
			else if( $item_booking[ 'type' ] === 'group' ) {
				$old_group_status = ! empty( $item_booking[ 'booking_group' ]->state ) ? $item_booking[ 'booking_group' ]->state : $item_booking[ 'bookings' ][ 0 ]->state;
				if( $in__status && ! in_array( $old_group_status, $in__status, true ) ) { continue; }
				if( $in__booking_group_id && ! in_array( $item_booking[ 'id' ], $in__booking_group_id, true ) ) { continue; }
				
				$sanitized_data = bookacti_sanitize_booking_group_data( array_merge( array( 'id' => $item_booking[ 'id' ] ), $new_data ) );
				$is_updated1    = bookacti_update_booking_group_bookings( $sanitized_data );
				$is_updated2    = bookacti_update_booking_group( $sanitized_data );
				$is_updated     = $is_updated1 || $is_updated2;
			
				$old_group_quantity = ! empty( $item_booking[ 'booking_group' ]->quantity ) ? intval( $item_booking[ 'booking_group' ]->quantity ) : intval( $item_booking[ 'bookings' ][ 0 ]->quantity );
				$new_group_quantity = $sanitized_data[ 'quantity' ] > 0 ? $sanitized_data[ 'quantity' ] : 0;

				// Trigger booking group quantity change hook
				if( $is_updated && $new_group_quantity && $old_group_quantity !== $new_group_quantity ) {
					do_action( 'bookacti_booking_group_quantity_updated', $new_group_quantity, $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], $args );
				}

				// Trigger booking group status change hook
				if( $is_updated && $sanitized_data[ 'status' ] && $old_group_status !== $sanitized_data[ 'status' ] ) {
					do_action( 'bookacti_booking_group_status_changed', $sanitized_data[ 'status' ], $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], $args );
				}
				
				if( $is_updated ) {
					$updated[ 'booking_group_ids' ][] = $item_booking[ 'id' ];
				}
			}
			
			if( $is_updated ) { 
				do_action( 'bookacti_wc_order_item_booking_updated', $item_booking, $sanitized_data, $order, $new_data, $where, $args );
				++$updated[ 'updated' ];
			}
		}
	}
	
	return $updated;
}


/**
 * Update the order status according to the bookings status bound to its items
 * @since 1.9.0 (was bookacti_change_order_state_based_on_its_bookings_state)
 * @version 1.16.45
 * @param int $order_id
 */
function bookacti_wc_update_order_status_according_to_its_bookings( $order_id ) {
	// Get a fresh instance of WC_Order because some of its items may have changed
	$order = wc_get_order( $order_id );
	if( ! $order ) { return; }

	$items = $order->get_items();
	if( ! $items ) { return; }

	$items_bookings = bookacti_wc_get_order_items_bookings( $items );
	if( ! $items_bookings ) { return; }

	// Get items booking status and
	// Determine if the order is only composed of activities
	$statuses = array();
	$only_activities = true;
	$only_virtual_activities = true;
	foreach( $items as $item_id => $item ) {
		// Is activity
		if( empty( $items_bookings[ $item_id ] ) ) { $only_activities = false; break; }

		// Is virtual
		$product = method_exists( $item, 'get_product' ) ? $item->get_product() : null;
		if( $product && ! $product->is_virtual() ) { $only_virtual_activities = false; }
		
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			if( $item_booking[ 'type' ] === 'group' ) {
				if( ! empty( $item_booking[ 'booking_group' ]->state ) ) {
					$statuses[] = $item_booking[ 'booking_group' ]->state;
				}
			} else {
				foreach( $item_booking[ 'bookings' ] as $booking ) {
					$statuses[] = $booking->state;
				}
			}
		}
	}
	
	if( ! $only_activities || ! $statuses || in_array( 'in_cart', $statuses, true ) ) { return; }

	$statuses         = array_unique( $statuses );
	$order_status     = $order->get_status();
	$new_order_status = $order_status;
	$completed_booking_statuses = array( 'delivered', 'booked' );
	$cancelled_booking_statuses = array( 'cancelled', 'refund_requested', 'expired', 'removed', 'waiting_list_rejected' );
	$refunded_booking_statuses  = array( 'refunded' );
	$are_completed = ! array_diff( $statuses, $completed_booking_statuses );
	$are_cancelled = ! array_diff( $statuses, $cancelled_booking_statuses );
	$are_refunded  = ! array_diff( $statuses, $refunded_booking_statuses );

	if( in_array( $order_status, array( 'pending' ), true ) && in_array( 'pending', $statuses, true ) ) {
		// Turn order status to processing
		$new_order_status = 'processing';
	} else if( ! in_array( $order_status, array( 'cancelled', 'refunded', 'failed', 'completed' ), true ) && $are_completed ) {
		// Turn order status to completed
		$non_virtual_bookings_order_status = apply_filters( 'bookacti_wc_completed_non_virtual_bookings_order_status', 'processing' );
		$new_order_status = $only_virtual_activities ? 'completed' : $non_virtual_bookings_order_status;
	} else if( ! in_array( $order_status, array( 'cancelled', 'refunded', 'failed' ), true ) && $are_cancelled ) {
		// Turn order status to cancelled
		$new_order_status = 'cancelled';
	} else if( $are_refunded ) {
		// Turn order status to refunded if all bookings are refunded
		$new_order_status = 'refunded';
	}

	$new_order_status = apply_filters( 'bookacti_wc_order_status_according_to_its_bookings', $new_order_status, $order, $items_bookings );

	if( $new_order_status !== $order_status ) {
		$order->update_status( $new_order_status );
	}
}


/**
 * Remove booking ids from order item meta to unbind a booking from an item
 * @since 1.9.0
 * @param WC_Order_Item_Product $item
 * @param array $item_bookings_ids_to_delete Leave it empty to unbind all bookings from the item
 * @return int|false Returns the number of bookings unbound. Returns false in case of error. Be careful, returns 0 if no bookings were bound to the item in the first place.
 */
function bookacti_wc_remove_order_item_bookings( $item, $item_bookings_ids_to_delete = array() ) {
	$removed = 0;
	
	// Backward compatibility for bookings made before 1.9.0
	$removed = bookacti_delete_order_item_booking_meta( $item->get_id() );
	if( $removed ) { return $removed; }
	
	$item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
	
	// Remove only the specified bookings
	if( $item_bookings_ids_to_delete ) {
		foreach( $item_bookings_ids_to_delete as $item_booking_id_to_delete ) {
			foreach( $item_bookings_ids as $i => $item_booking_id ) {
				if( intval( $item_booking_id[ 'id' ] ) === intval( $item_booking_id_to_delete[ 'id' ] )
				&&  $item_booking_id[ 'type' ] === $item_booking_id_to_delete[ 'type' ] ) {
					unset( $item_bookings_ids[ $i ] );
					++$removed;
					break;
				}
			}
		}
	} 
	// Remove all bookings if there are no specified bookings
	else {
		$removed = count( $item_bookings_ids );
		$item_bookings_ids = array();
	}

	// Update the bookings meta
	if( $removed ) {
		$updated = $item_bookings_ids ? wc_update_order_item_meta( $item->get_id(), 'bookacti_bookings', json_encode( array_values( $item_bookings_ids ) ) ) : wc_delete_order_item_meta( $item->get_id(), 'bookacti_bookings' );
		if( ! $updated ) { return false; }
	}

	return $removed;
}


/**
 * Set order bookings status to 'removed' if they have no corresponding order item
 * @since 1.16.40 (was bookacti_cancel_order_remaining_bookings)
 * @verison 1.16.45
 * @param int $order_id
 * @param array $args
 * @return int
 */
function bookacti_wc_remove_order_bookings_not_in_order_items( $order_id, $args = array() ) {
	$nb_removed            = 0;
	$not_booking_ids       = array();
	$not_booking_group_ids = array();
	$removed_bookings      = array();
	$removed_groups        = array();
	
	$default_args = array( 'context' => 'wc_order_status_changed' );
	$args         = wp_parse_args( $args, $default_args );
	
	$new_data = array( 'order_id' => -1, 'status' => 'removed', 'active' => 0 );
	
	// Do not remove bookings attached to the order
	$order = wc_get_order( $order_id );
	if( $order ) {
		$items = $order->get_items();
		if( $items ) {
			foreach( $items as $item_id => $item ) {
				$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
				if( ! $order_item_bookings_ids ) { continue; }
				foreach( $order_item_bookings_ids as $order_item_booking_id ) {
					     if( $order_item_booking_id[ 'type' ] === 'single' ) { $not_booking_ids[]       = $order_item_booking_id[ 'id' ]; }
					else if( $order_item_booking_id[ 'type' ] === 'group' )  { $not_booking_group_ids[] = $order_item_booking_id[ 'id' ]; }
				}
			}
		}
	}
	
	// Get bookings
	$booking_filters = bookacti_format_booking_filters( array( 'in__order_id' => array( $order_id ), 'not_in__booking_id' => $not_booking_ids, 'booking_group_id' => false, 'fetch_meta' => true ) );
	$bookings        = bookacti_get_bookings( $booking_filters );
	$group_filters   = bookacti_format_booking_filters( array( 'in__order_id' => array( $order_id ), 'not_in__booking_group_id' => $not_booking_group_ids, 'fetch_meta' => true ) );
	$groups          = bookacti_get_booking_groups( $group_filters );
	
	// Get grouped bookings
	$group_booking_filters = $groups ? bookacti_format_booking_filters( array( 'in__booking_group_id' => array_keys( $groups ), 'fetch_meta' => true ) ) : array();
	$groups_bookings       = $group_booking_filters ? bookacti_get_bookings( $group_booking_filters ) : array();
	$bookings_per_group    = array();
	foreach( $groups_bookings as $booking ) {
		if( ! isset( $bookings_per_group[ $booking->group_id ] ) ) { $bookings_per_group[ $booking->group_id ] = array(); }
		$bookings_per_group[ $booking->group_id ][] = $booking;
	}
	
	// Set orphan bookings status to "removed"
	if( $bookings ) {
		foreach( $bookings as $booking ) {
			$sanitized_data = bookacti_sanitize_booking_data( array_merge( array( 'id' => $booking->id ), $new_data ) );
			$is_updated     = bookacti_update_booking( $sanitized_data );
			
			// Trigger booking status change hook
			if( $is_updated && $sanitized_data[ 'status' ] && $booking->state !== $sanitized_data[ 'status' ] ) {
				do_action( 'bookacti_booking_status_changed', $sanitized_data[ 'status' ], $booking, $args );
			}
			
			if( $is_updated ) {
				$removed_bookings[ $booking->id ] = $booking;
			}
		}
	}
	
	// Set orphan booking groups status to "removed"
	if( $groups ) {
		foreach( $groups as $group ) {
			$sanitized_data = bookacti_sanitize_booking_group_data( array_merge( array( 'id' => $group->id ), $new_data ) );
			$is_updated1    = bookacti_update_booking_group_bookings( $sanitized_data );
			$is_updated2    = bookacti_update_booking_group( $sanitized_data );
			$is_updated     = $is_updated1 || $is_updated2;

			$old_group_status = ! empty( $group->state ) ? $group->state : '';
			$group_bookings   = isset( $bookings_per_group[ $group->id ] ) ? $bookings_per_group[ $group->id ] : array();

			// Trigger booking group status change hook
			if( $is_updated && $sanitized_data[ 'status' ] && $old_group_status !== $sanitized_data[ 'status' ] ) {
				do_action( 'bookacti_booking_group_status_changed', $sanitized_data[ 'status' ], $group, $group_bookings, $args );
			}
			
			if( $is_updated ) {
				$removed_groups[ $group->id ] = $group;
			}
		}
	}
	
	do_action( 'bookacti_wc_order_bookings_not_in_order_items_removed', $order_id, $removed_bookings, $removed_groups, array_intersect_key( $bookings_per_group, $removed_groups ), $args );
	
	return count( $removed_bookings ) + count( $removed_groups );
}


/**
 * Get order items holding one of the desired booking or booking group ID
 * @since 1.9.0
 * @version 1.11.2
 * @param array $booking_ids
 * @param array $booking_group_ids
 * @param WC_Order[] $orders
 * @return WC_Order_Item_Product[]
 */
function bookacti_wc_get_order_items_by_bookings( $booking_ids = array(), $booking_group_ids = array(), $orders = array() ) {
	$order_items = array();
	if( ! $booking_ids && ! $booking_group_ids ) { return $order_items; }
	
	if( ! $orders ) {
		// Get the bookings by booking or booking group IDs
		$filters = bookacti_format_booking_filters( array( 
			'in__booking_id' => $booking_ids, 
			'in__booking_group_id' => $booking_group_ids, 
			'booking_group_id_operator' => 'OR' ) );
		$bookings = bookacti_get_bookings( $filters );
		if( ! $bookings ) { return $order_items; }

		// Get the order attached to the bookings
		$order_ids = array();
		foreach( $bookings as $booking ) {
			$order_id = ! empty( $booking->order_id ) ? intval( $booking->order_id ) : 0;
			if( $order_id && ! in_array( $order_id, $order_ids, true ) ) { $order_ids[] = $order_id; }
		}
		
		// Get WC orders
		$orders = $order_ids ? wc_get_orders( array( 'post__in' => $order_ids, 'limit' => -1 ) ) : array();
	}
	
	if( ! $orders ) { return $order_items; }
	
	// Sanitize booking ids, and store them in a temporary array
	$remaining = array( 
		'booking_ids' => array_map( 'intval', $booking_ids ), 
		'booking_group_ids' => array_map( 'intval', $booking_group_ids )
	);
	
	foreach( $orders as $order ) {
		$items = $order->get_items();
		if( ! $items ) { continue; }
		
		foreach( $items as $item_id => $item ) {
			$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
			if( ! $order_item_bookings_ids ) { continue; }
			
			foreach( $order_item_bookings_ids as $order_item_booking_id ) {
				if( $order_item_booking_id[ 'type' ] === 'single' && $remaining[ 'booking_ids' ] ) {
					$i = array_search( $order_item_booking_id[ 'id' ], $remaining[ 'booking_ids' ], true );
					if( $i !== false ) { 
						$order_items[ $item_id ] = $item;
						unset( $remaining[ 'booking_ids' ][ $i ] );
					}
				}
				else if( $order_item_booking_id[ 'type' ] === 'group' && $remaining[ 'booking_group_ids' ] ) {
					$i = array_search( $order_item_booking_id[ 'id' ], $remaining[ 'booking_group_ids' ], true );
					if( $i !== false ) { 
						$order_items[ $item_id ] = $item;
						unset( $remaining[ 'booking_group_ids' ][ $i ] );
					}
				}
				if( ! $remaining[ 'booking_ids' ] && ! $remaining[ 'booking_group_ids' ] ) { break; }
			}
			if( ! $remaining[ 'booking_ids' ] && ! $remaining[ 'booking_group_ids' ] ) { break; }
		}
		if( ! $remaining[ 'booking_ids' ] && ! $remaining[ 'booking_group_ids' ] ) { break; }
	}
	
	return apply_filters( 'bookacti_wc_order_items_by_bookings', $order_items, $booking_ids, $booking_group_ids, $orders );
}


/**
 * Save the order user data as booking meta
 * @since 1.9.0 (was bookacti_save_order_data_as_booking_meta)
 * @version 1.9.1
 * @param WC_Order $order
 */
function bookacti_wc_save_no_account_user_data_as_booking_meta( $order ) {
	// Get user data to save
	$user_data = apply_filters( 'bookacti_wc_no_account_user_data_to_save_as_booking_meta', array(
		'email'      => $order->get_billing_email( 'edit' ),
		'first_name' => $order->get_billing_first_name( 'edit' ),
		'last_name'  => $order->get_billing_last_name( 'edit' ),
		'phone'      => $order->get_billing_phone( 'edit' )
	), $order );

	// Do not save empty values
	$user_data = array_filter( $user_data, function( $value ) { return $value !== '' && $value !== array(); } );
	if( ! $user_data ) { return; }

	// Prefix array keys with 'user_'
	$user_data = array_combine( array_map( function( $key ) { return 'user_' . $key; }, array_keys( $user_data ) ), $user_data );
	
	$items = $order->get_items();
	if( $items ) {
		foreach( $items as $key => $item ) {
			$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
			if( ! $order_item_bookings_ids ) { continue; }
			
			foreach( $order_item_bookings_ids as $order_item_booking_id ) {
				// Get item booking id and type
				$booking_id = $order_item_booking_id[ 'id' ];
				$object_type = $order_item_booking_id[ 'type' ] === 'single' ? 'booking' : ( $order_item_booking_id[ 'type' ] === 'group' ? 'booking_group' : '' );
				if( ! $booking_id || ! $object_type ) { continue; }

				// Add user data to the booking meta
				bookacti_update_metadata( $object_type, $booking_id, $user_data );
			}
		}
	}
}


/**
 * Get woocommerce order item id by booking id
 * @version 1.9.0
 * @param int|object $booking_id
 * @return WC_Order_Item_Product|array Empty array if not found
 */
function bookacti_get_order_item_by_booking_id( $booking_id ) {
	if( ! $booking_id ) { return false; }
	
	if( is_object( $booking_id ) ) {
		$booking = $booking_id;
		$booking_id = $booking->id;
		$order_id = $booking->order_id;
	} else {
		$order_id = bookacti_get_booking_order_id( $booking_id );
	}
	if( ! $order_id ) { return false; }

	$order = wc_get_order( $order_id );
	if( ! $order ) { return false; }

	$order_items = $order->get_items();
	if( ! $order_items ) { return false; }

	$item = array();
	foreach( $order_items as $order_item_id => $order_item ) {
		$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $order_item );
		if( ! $order_item_bookings_ids ) { continue; }
		
		$is_in_item = false;
		foreach( $order_item_bookings_ids as $order_item_booking_id ) {
			// Check if the item is bound to a the desired booking
			if( $order_item_booking_id[ 'type' ] === 'single' && $order_item_booking_id[ 'id' ] === intval( $booking_id ) ) {
				$is_in_item = true; break;

			// Check if the item is bound to a group of bookings
			} else if( $order_item_booking_id[ 'type' ] === 'group' ) {
				$booking_ids = bookacti_get_booking_group_bookings_ids( $order_item_booking_id[ 'id' ] );
				if( in_array( $booking_id, $booking_ids ) ) { $is_in_item = true; break; }
			}
		}
		
		if( $is_in_item ) {
			$item = $order_items[ $order_item_id ];
			if( is_array( $item ) ) {
				$item[ 'id' ]       = $order_item_id;
				$item[ 'order_id' ] = $order_id;
			}
		}
	}

	return $item;
}


/**
 * Get woocommerce order item id by booking group id
 * @since 1.1.0
 * @version 1.9.0
 * @param int|object $booking_group_id
 * @return WC_Order_Item_Product|array|false
 */
function bookacti_get_order_item_by_booking_group_id( $booking_group_id ) {
	if( ! $booking_group_id ) { return false; }

	if( is_object( $booking_group_id ) ) {
		$booking = $booking_group_id;
		$booking_group_id = ! empty( $booking->group_id ) ? $booking->group_id : $booking->id;
		$order_id = ! empty( $booking->group_order_id ) ? $booking->group_order_id : $booking->order_id;
	} else {
		$order_id = bookacti_get_booking_group_order_id( $booking_group_id );
	}
	if( ! $order_id ) { return false; }

	$order = wc_get_order( $order_id );
	if( ! $order ) { return false; }

	$order_items = $order->get_items();
	if( ! $order_items ) { return false; }

	$item = array();
	foreach( $order_items as $order_item_id => $order_item ) {
		$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $order_item );
		if( ! $order_item_bookings_ids ) { continue; }
		
		$is_in_item = false;
		foreach( $order_item_bookings_ids as $order_item_booking_id ) {
			if( $order_item_booking_id[ 'type' ] === 'group' && $order_item_booking_id[ 'id' ] === intval( $booking_group_id ) ) {
				$is_in_item = true; break;
			}
		}
		
		if( $is_in_item ) {
			$item = $order_items[ $order_item_id ];
			if( is_array( $item ) ) {
				$item[ 'id' ]       = $order_item_id;
				$item[ 'order_id' ] = $order_id;
			}
		}
	}

	return $item;
}


/**
 * Get booking actions according to its order status
 * @since 1.6.0 (replace bookacti_display_actions_buttons_on_items)
 * @version 1.12.3
 * @param array $booking_actions
 * @param int $order_id
 * @return array
 */
function bookacti_wc_booking_actions_per_order_id( $booking_actions, $order_id ) {
	if( ! $order_id || ! is_numeric( $order_id ) ) { return $booking_actions; }

	$order = wc_get_order( $order_id );

	// Check view order
	if( ! $order || ! current_user_can( 'edit_others_shop_orders' ) ) {
		if( isset( $booking_actions[ 'view-order' ] ) ) { unset( $booking_actions[ 'view-order' ] ); } 
	}
	if( ! $order ) { return $booking_actions; }
	
	if( isset( $booking_actions[ 'view-order' ] ) ) { $booking_actions[ 'view-order' ][ 'link' ] = get_edit_post_link( $order_id ); }

	// Check cancel / reschedule
	if( ! current_user_can( 'bookacti_edit_bookings' ) && $order->get_status() === 'pending' ) { 
		if( isset( $booking_actions[ 'cancel' ] ) )     { unset( $booking_actions[ 'cancel' ] ); } 
		if( isset( $booking_actions[ 'reschedule' ] ) ) { unset( $booking_actions[ 'reschedule' ] ); }
	}

	return $booking_actions;
}


/**
 * Get WC order items rows
 * @since 1.7.4
 * @version 1.9.0
 * @param WC_Order_Item_Product[] $order_items
 * @return string
 */
function bookacti_get_order_items_rows( $order_items = array() ) {
	ob_start();
	foreach( $order_items as $item ) {
		wc_get_template(
			'order/order-details-item.php',
			array(
				'order'              => $item->get_order(),
				'item_id'            => $item->get_id(),
				'item'               => $item,
				'show_purchase_note' => false,
				'purchase_note'      => '',
				'product'            => $item->get_product(),
			)
		);
	}
	return ob_get_clean();
}




// PRODUCT

/**
 * Display a products selectbox
 * @since 1.7.0
 * @version 1.16.44
 * @param array $raw_args
 * @return string
 */
function bookacti_display_product_selectbox( $raw_args = array() ) {
	$defaults = array(
		'field_name'  => 'product_id',
		'selected'    => '',
		'id'          => '',
		'class'       => '',
		'allow_tags'  => 0,
		'allow_clear' => 1,
		'ajax'        => 1,
		'select2'     => 1, 
		'sortable'    => 0, 
		'echo'        => 1,
		'placeholder' => esc_html__( 'Search...', 'booking-activities' )
	);
	$args = apply_filters( 'bookacti_product_selectbox_args', wp_parse_args( $raw_args, $defaults ), $raw_args );

	$products_titles = ! $args[ 'ajax' ] ? bookacti_get_products_titles() : ( $args[ 'selected' ] ? bookacti_get_products_titles( $args[ 'selected' ] ) : array() );
	$args[ 'class' ] = $args[ 'ajax' ] ? 'bookacti-select2-ajax ' . trim( $args[ 'class' ] ) : ( $args[ 'select2' ] ? 'bookacti-select2-no-ajax ' . trim( $args[ 'class' ] ) : trim( $args[ 'class' ] ) );

	ob_start();
	?>
	<select <?php if( $args[ 'id' ] ) { echo 'id="' . $args[ 'id' ] . '"'; } ?> 
		name='<?php echo $args[ 'field_name' ]; ?>' 
		class='bookacti-wc-products-selectbox <?php echo $args[ 'class' ]; ?>'
		data-tags='<?php echo ! empty( $args[ 'allow_tags' ] ) ? 1 : 0; ?>'
		data-allow-clear='<?php echo ! empty( $args[ 'allow_clear' ] ) ? 1 : 0; ?>'
		data-placeholder='<?php echo ! empty( $args[ 'placeholder' ] ) ? esc_attr( $args[ 'placeholder' ] ) : ''; ?>'
		data-sortable='<?php echo ! empty( $args[ 'sortable' ] ) ? 1 : 0; ?>'
		data-type='products'>
		<option><!-- Used for the placeholder --></option>
	<?php
		do_action( 'bookacti_add_product_selectbox_options', $args, $products_titles );

		$is_selected = false;
		if( $products_titles ) {
			foreach( $products_titles as $product_id => $product ) {
				// Display simple products options
				if( empty( $product[ 'variations' ] ) ) {
					$_selected = selected( $product_id, $args[ 'selected' ], false );
					if( $_selected ) { $is_selected = true; }
					?><option class='bookacti-wc-product-option' value='<?php echo esc_attr( $product_id ); ?>' <?php echo $_selected; ?>><?php echo $product[ 'title' ] ? esc_html( apply_filters( 'bookacti_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : $product[ 'title' ]; ?></option><?php

				// Display variations options
				} else {
				?>
					<optgroup class='bookacti-wc-variable-product-option-group' label='<?php echo $product[ 'title' ] ? esc_attr( apply_filters( 'bookacti_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : $product[ 'title' ]; ?>'>
					<?php
						foreach( $product[ 'variations' ] as $variation_id => $variation ) {
							$_selected = selected( $variation_id, $args[ 'selected' ], false );
							if( $_selected ) { $is_selected = true; }
							$variation_title = $variation[ 'title' ] ? esc_html( apply_filters( 'bookacti_translate_text_external', $variation[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_variation', 'object_id' => $variation_id, 'field' => 'post_excerpt', 'product_id' => $product_id ) ) ) : $variation[ 'title' ];
							$formatted_variation_title = trim( preg_replace( '/,[\s\S]+?:/', ',', ',' . $variation_title ), ', ' );
							?><option class='bookacti-wc-product-variation-option' value='<?php echo esc_attr( $variation_id ); ?>' <?php echo $_selected; ?>><?php echo $formatted_variation_title; ?></option><?php
						}
					?>
					</optgroup>
				<?php
				}
			}
		}

		if( $args[ 'allow_tags' ] && $args[ 'selected' ] !== '' && ! $is_selected ) {
			?><option value='<?php echo esc_attr( $args[ 'selected' ] ); ?>' selected="selected"><?php echo esc_html( $args[ 'selected' ] ); ?></option><?php
		}
	?>
	</select>
	<?php
	$output = ob_get_clean();

	if( empty( $args[ 'echo' ] ) ) { return $output; }
	echo $output;
}


/**
 * Tell if the product is activity or has variations that are activities
 * @version 1.15.17
 * @param WC_Product|int $product
 * @return boolean
 */
function bookacti_product_is_activity( $product ) {
	// Get product or variation from ID
	if( ! is_object( $product ) ) {
		$product_id = intval( $product );
		$product    = wc_get_product( $product_id );
		if( ! $product ) { return false; }
	}

	$is_activity = false;
	
	if( $product->is_type( 'variation' ) ) {
		$is_activity = $product->get_meta( 'bookacti_variable_is_activity' ) === 'yes';
	}
	else if( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();
		foreach( $variations as $variation ) {
			if( ! empty( $variation[ 'bookacti_is_activity' ] ) ) { $is_activity = true; break; }
		}
	}
	else if( ! $product->is_type( 'grouped' ) && ! $product->is_type( 'external' ) ) {
		$is_activity = $product->get_meta( '_bookacti_is_activity' ) === 'yes';
	}

	return apply_filters( 'bookacti_product_is_activity', $is_activity, $product );
}


/**
 * Find matching product variation
 * @since 1.5.0
 * @version 1.14.0
 * @param WC_Product $product
 * @param array $attributes
 * @return int Matching variation ID or 0.
 */
function bookacti_get_product_variation_matching_attributes( $product, $attributes ) {
	$product_attributes = $product->get_attributes();

	// Format attributes array
	foreach( $attributes as $key => $value ) {
		// Take the untranslated value (in case of translated attributes)
		foreach( $product_attributes as $product_attribute_key => $product_attribute ) {
			if( $product_attribute_key === '' ) { continue; }
			if( $key !== apply_filters( 'bookacti_translate_text_external', $product_attribute_key, false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_attribute_key', 'object_id' => $product_attribute_key, 'field' => 'key', 'product_id' => $product->get_id() ) ) 
			&&  $key !== $product_attribute_key ) { continue; }

			$options = $product_attribute->get_options();
			// If it failed, try to retrieve it from database (doesn't work with custom attributes)
			if( ! $options ) { $options = wc_get_product_terms( $product->get_id(), $product_attribute_key, array( 'fields' => 'slugs' ) ); }

			if( is_array( $options ) ) {
				foreach( $options as $option_key => $option_value ) {
					if( $option_value === '' ) { continue; }
					if( $value !== apply_filters( 'bookacti_translate_text_external', $option_value, false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_attribute_option', 'object_id' => $option_key, 'field' => 'value', 'product_id' => $product->get_id(), 'product_attribute_key' => $product_attribute_key ) ) 
					&&  $value !== $option_value ) { continue; }
					$value = $option_value;
				}
			}
		}

		// Make sure the attributes array is properly formatted (key begins with attribute_ and values matches)
		if( strpos( $key, 'attribute_' ) === 0 && $attributes[ $key ] === $value ) { continue; }
		unset( $attributes[ $key ] );
		$attributes[ 'attribute_' . $key ] = $value;
	}

	// Find matching variation
	$data_store = WC_Data_Store::load( 'product' );
	$variation_id = $data_store->find_matching_product_variation( $product, $attributes );

	return $variation_id;
}


/**
 * Get variation default attributes
 * @since 1.5.0
 * @param WC_Product $product
 * @return array
 */
function bookacti_get_product_default_attributes( $product ) {
	if( method_exists( $product, 'get_default_attributes' ) ) {
		return $product->get_default_attributes();
	} else {
		return $product->get_variation_default_attributes();
	}
}


/**
 * Get the form ID bound to a product / variation
 * @since 1.9.0
 * @version 1.15.17
 * @param int $product_id
 * @param boolean $is_variation
 * @return int
 */
function bookacti_get_product_form_id( $product_id, $is_variation = 'check' ) {
	$form_id = 0;
	$product = wc_get_product( $product_id );
	
	if( $product ) {
		// Check if the product is simple or a variation
		if( $is_variation === 'check' ) {
			$is_variation = $product->get_type() === 'variation';
		}
		
		$meta_key = $is_variation ? 'bookacti_variable_form' : '_bookacti_form';
		$form_id  = $product->get_meta( $meta_key );
	}
	
	return apply_filters( 'bookacti_product_booking_form_id', intval( $form_id ), $product_id, $is_variation );
}




// REFUND

/**
 * Get WC additional refund actions
 * @since 1.9.0
 * @return array
 */
function bookacti_wc_get_refund_actions() {
	$wc_refund_actions = array(
		'coupon' => array(
			'id'          => 'coupon',
			'label'       => esc_html__( 'Coupon', 'booking-activities' ),
			'description' => esc_html__( 'Create a coupon worth the price paid. The coupon can be used once for any orders at any time. ', 'booking-activities' )
		),
		'auto' => array(
			'id'          => 'auto',
			'label'       => esc_html__( 'Auto refund', 'booking-activities' ),
			'description' => esc_html__( 'Refund automatically via the gateway used for payment.', 'booking-activities' )
		)
	);
	return apply_filters( 'bookacti_wc_refund_actions', $wc_refund_actions );
}


/**
 * Filter refund actions by order id
 * @since 1.1.0
 * @version 1.9.0
 * @param array $possible_actions
 * @param int $order_id
 * @return type
 */
function bookacti_filter_refund_actions_by_order( $possible_actions, $order_id ) {
	$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : $order_id;
	if( is_a( $order, 'WC_Order' ) ) {
		foreach( $possible_actions as $key => $possible_action ){
			// Allow auto-refund only if gateway allows it
			if( $possible_action[ 'id' ] === 'auto' && ! bookacti_does_order_support_auto_refund( $order ) ){
				unset( $possible_actions[ $key ] );
			}
		}
	} else {
		// If the booking has not been taken with WooCommerce, remove WooCommerce refund methods
		$woocommerce_actions = bookacti_wc_get_refund_actions();
		foreach( $woocommerce_actions as $woocommerce_action ) {
			unset( $possible_actions[ $woocommerce_action[ 'id' ] ] );
		}
	}

	return $possible_actions;
}


/**
 * Check if an order supports auto refund
 * @version 1.9.0
 * @param WC_Order|int $order
 * @return boolean
 */
function bookacti_does_order_support_auto_refund( $order ) {
	if( is_numeric( $order ) ) { $order = wc_get_order( intval( $order ) ); }
	if( ! is_a( $order, 'WC_Order' ) ) { return false; }
	
	$payment_gateway = wc_get_payment_gateway_by_order( $order );
	return $payment_gateway ? $payment_gateway->can_refund_order( $order ) : false;
}


/**
 * Update order bookings if a partial refund is perfomed (refund of one or more items)
 * @since 1.2.0 (was part of bookacti_update_booking_when_order_item_is_refunded before)
 * @version 1.16.45
 * @param WC_Order_Refund $refund
 */
function bookacti_update_order_bookings_on_items_refund( $refund ) {
	$refund_items = $refund->get_items();
	if( ! $refund_items ) { return; }
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$price_decimals   = wc_get_price_decimals();
	
	$refund_id     = $refund->get_id();
	$refund_action = $refund->get_refunded_payment() ? 'auto' : 'manual';
	$refund_date   = $refund->get_date_created() ? $refund->get_date_created() : new DateTime();
	if( is_a( $refund_date, 'DateTime' ) ) {
		$refund_date->setTimezone( $utc_timezone_obj );
		$refund_date = $refund_date->format( 'Y-m-d H:i:s' );
	}
	
	$order_id    = $refund->get_parent_id();
	$order       = wc_get_order( $refund->get_parent_id() );
	$order_items = $order->get_items();
	
	// Get the bookings attached to refunded items
	$refunded_items = array();
	$items_refunded_qty = array();
	$items_refunded_amount = array();
	foreach( $refund_items as $refund_item ) {
		$item_id = intval( $refund_item->get_meta( '_refunded_item_id', true ) );
		if( ! isset( $order_items[ $item_id ] ) ) { continue; }
		
		$refunded_qty    = abs( intval( $refund_item->get_quantity() ) );
		$refunded_amount = abs( round( (float) $refund_item->get_total() + (float) $refund_item->get_total_tax(), $price_decimals ) );
		
		// If the refunded quantity was not given, try to compute it from the refunded amount (only if the refunded amount is a multiple of the unit price)
		if( ! $refunded_qty ) {
			$item_unit_price = abs( round( ( (float) $order_items[ $item_id ]->get_total() + (float) $order_items[ $item_id ]->get_total_tax() ) / $order_items[ $item_id ]->get_quantity(), $price_decimals ) );
			if( ( $refunded_amount % $item_unit_price  ) === 0 ) { $refunded_qty = $refunded_amount / $item_unit_price; }
		}
		
		if( ! $refunded_qty || ! $refunded_amount ) { continue; }
		
		$refunded_items[ $item_id ]        = $order_items[ $item_id ];
		$items_refunded_qty[ $item_id ]    = $refunded_qty;
		$items_refunded_amount[ $item_id ] = $refunded_amount;
	}
	
	$items_bookings = bookacti_wc_get_order_items_bookings( $refunded_items );
	
	// Update each booking qty or status
	foreach( $items_bookings as $item_id => $item_bookings ) {
		foreach( $item_bookings as $item_booking ) {
			// Prepare the refund record
			$refund_record = apply_filters( 'bookacti_wc_booking_refund_data', array( 'date' => $refund_date, 'quantity' => isset( $items_refunded_qty[ $item_id ] ) ? $items_refunded_qty[ $item_id ] : 0, 'amount' => isset( $items_refunded_amount[ $item_id ] ) ? wc_format_decimal( $items_refunded_amount[ $item_id ] ) : 0, 'method' => $refund_action ), $refund, $item_booking );
			
			$new_group_quantity = 0;
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$new_quantity = intval( $booking->quantity ) - $items_refunded_qty[ $item_id ];
				$new_data     = $new_quantity > 0 ? array( 'id' => $booking->id, 'quantity' => $new_quantity ) : array( 'id' => $booking->id, 'status' => 'refunded', 'active' => 0 );
				$booking_data = bookacti_sanitize_booking_data( $new_data );
				$updated      = bookacti_update_booking( $booking_data );
				
				$new_group_quantity = $updated ? max( $new_group_quantity, intval( $booking_data[ 'quantity' ] ) ) : $new_group_quantity;
				
				if( $updated && $item_booking[ 'type' ] === 'single' ) {
					// Update refunds records array bound to the booking
					$refunds = bookacti_get_metadata( 'booking', $booking->id, 'refunds', true );
					if( ! is_array( $refunds ) ) { $refunds = array(); }
					$refunds[ $refund_id ] = $refund_record;
					bookacti_update_metadata( 'booking', $booking->id, array( 'refunds' => $refunds ) );
					
					// Trigger booking quantity change hook
					if( intval( $booking_data[ 'quantity' ] ) !== intval( $booking->quantity ) && $booking_data[ 'quantity' ] ) {
						do_action( 'bookacti_booking_quantity_updated', $booking_data[ 'quantity' ], $booking, array( 'is_admin' => true, 'context' => 'wc_order_item_refund' ) );
					}
					
					// Trigger booking status change
					if( $booking->state !== $booking_data[ 'status' ] && $booking_data[ 'status' ] ) {
						do_action( 'bookacti_booking_status_changed', $booking_data[ 'status' ], $booking, array( 'is_admin' => true, 'context' => 'wc_order_item_refund', 'refund_action' => $refund_action ) );
						
						$new_booking         = $booking;
						$new_booking->state  = $booking_data[ 'status' ];
						if( isset( $new_data[ 'active' ] ) )    { $new_booking->active   = $booking_data[ 'active' ]; }
						if( isset( $new_data[ 'quantity' ] ) )  { $new_booking->quantity = $booking_data[ 'quantity' ]; }
						if( ! isset( $new_booking->settings ) ) { $new_booking->settings = array(); }
						$new_booking->settings[ 'refunds' ] = $refunds;
						
						$send_to = $refund_action === 'manual' ? 'customer' : 'both';
						bookacti_send_booking_status_change_notification( $booking_data[ 'status' ], $new_booking, $booking, $send_to, array( 'refund_action' => $refund_action ) );
					}
				}
			}
			
			if( $item_booking[ 'type' ] === 'group' ) {
				// Update refunds records array bound to the booking group
				$refunds = bookacti_get_metadata( 'booking_group', $item_booking[ 'id' ], 'refunds', true );
				if( ! is_array( $refunds ) ) { $refunds = array(); }
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );
				
				// Trigger booking group quantity change hook
				if( $new_group_quantity > 0 && $item_booking[ 'booking_group' ]->quantity !== $new_group_quantity ) {
					do_action( 'bookacti_booking_group_quantity_updated', $new_group_quantity, $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], array( 'is_admin' => true, 'context' => 'wc_order_item_refund' ) );
				}
				
				// Update the booking group status
				if( $new_group_quantity <= 0 ) {
					$new_data           = array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 );
					$booking_group_data = bookacti_sanitize_booking_group_data( $new_data );
					$updated            = bookacti_update_booking_group( $booking_group_data );
					
					// Trigger booking group status change
					$group_status = isset( $item_booking[ 'booking_group' ]->state ) ? $item_booking[ 'booking_group' ]->state : $item_booking[ 'bookings' ][ 0 ]->state;
					if( $updated && $group_status !== $booking_group_data[ 'status' ] && $booking_group_data[ 'status' ] ) {
						// Update the group bookings status
						bookacti_update_booking_group_bookings( $booking_group_data );
						
						do_action( 'bookacti_booking_group_status_changed', $booking_group_data[ 'status' ], $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], array( 'is_admin' => true, 'context' => 'wc_order_item_refund', 'refund_action' => $refund_action ) );
						
						$new_booking_group        = $item_booking[ 'booking_group' ];
						$new_booking_group->state = $booking_group_data[ 'status' ];
						if( isset( $new_data[ 'active' ] ) )          { $new_booking_group->active = $booking_group_data[ 'active' ]; }
						if( ! isset( $new_booking_group->settings ) ) { $new_booking_group->settings = array(); }
						$new_booking_group->settings[ 'refunds' ] = $refunds;
						
						$send_to = $refund_action === 'manual' ? 'customer' : 'both';
						bookacti_send_booking_group_status_change_notification( $booking_group_data[ 'status' ], $new_booking_group, $item_booking[ 'booking_group' ], $send_to, array( 'refund_action' => $refund_action ) );
					}
				}
			}
		}
	}
}


/**
 * Update order bookings if a total refund is perfomed (refund of the whole order)
 * @since 1.2.0 (was part of bookacti_update_booking_when_order_item_is_refunded before)
 * @version 1.16.45
 * @param WC_Order_Refund $refund
 */
function bookacti_update_order_bookings_on_order_refund( $refund ) {
	// Double check that the refund is total
	$order_id        = $refund->get_parent_id();
	$order           = wc_get_order( $order_id );
	$is_total_refund = floatval( $order->get_total() ) == floatval( $order->get_total_refunded() );
	if( ! $is_total_refund ) { return; }
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	
	$refund_id     = $refund->get_id();
	$refund_action = $refund->get_refunded_payment() ? 'auto' : 'manual';
	$refund_date   = $refund->get_date_created() ? $refund->get_date_created() : new DateTime();
	if( is_a( $refund_date, 'DateTime' ) ) {
		$refund_date->setTimezone( $utc_timezone_obj );
		$refund_date = $refund_date->format( 'Y-m-d H:i:s' );
	}
	
	$items = $order->get_items();
	foreach( $items as $item_id => $item ) {
		$item_id = $item->get_id();
		$items_bookings = bookacti_wc_get_order_items_bookings( array( $item ) );
		if( empty( $items_bookings[ $item_id ] ) ) { continue; }
		
		// Get refunded qty and amount for each item
		$refunded_qty = abs( intval( $item->get_quantity() ) ) - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) );
		$refunded_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
		
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			// Do not treat bookings already marked as refunded
			$status = $item_booking[ 'type' ] === 'group' && ! empty( $item_booking[ 'booking_group' ]->state ) ? $item_booking[ 'booking_group' ]->state : $item_booking[ 'bookings' ][ 0 ]->state;
			if( $status === 'refunded' ) { continue; }
			
			// Prepare the refund record
			$refund_record = apply_filters( 'bookacti_wc_booking_refund_data', array( 'date' => $refund_date, 'quantity' => $refunded_qty, 'amount' => wc_format_decimal( $refunded_amount ), 'method' => $refund_action ), $refund, $item_booking );
			
			// Single booking
			if( $item_booking[ 'type' ] === 'single' ) {
				// Update booking status to 'refunded'
				$booking      = $item_booking[ 'bookings' ][ 0 ];
				$new_data     = array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 );
				$booking_data = bookacti_sanitize_booking_data( $new_data );
				$updated      = bookacti_update_booking( $booking_data );
				
				// Update refunds records array bound to the booking
				$refunds = bookacti_get_metadata( 'booking', $item_booking[ 'id' ], 'refunds', true );
				if( ! is_array( $refunds ) ) { $refunds = array(); }
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );

				if( $updated && $status !== $booking_data[ 'status' ] && $booking_data[ 'status' ] ) {
					do_action( 'bookacti_booking_status_changed', $booking_data[ 'status' ], $item_booking[ 'bookings' ][ 0 ], array( 'is_admin' => true, 'context' => 'wc_order_refund', 'refund_action' => $refund_action ) );
					
					$new_booking         = $booking;
					$new_booking->state  = $booking_data[ 'status' ];
					if( isset( $new_data[ 'active' ] ) )    { $new_booking->active   = $booking_data[ 'active' ]; }
					if( ! isset( $new_booking->settings ) ) { $new_booking->settings = array(); }
					$new_booking->settings[ 'refunds' ] = $refunds;
					
					$send_to = $refund_action === 'manual' ? 'customer' : 'both';
					bookacti_send_booking_status_change_notification( $booking_data[ 'status' ], $new_booking, $booking, $send_to, array( 'refund_action' => $refund_action ) );
				}

			// Booking group
			} else if( $item_booking[ 'type' ] === 'group' ) {
				// Update bookings statuses to 'refunded'
				$new_data           = array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 );
				$booking_group_data = bookacti_sanitize_booking_group_data( $new_data );
				$updated            = bookacti_update_booking_group( $booking_group_data );
				
				// Update refunds records array bound to the booking
				$refunds = bookacti_get_metadata( 'booking_group', $item_booking[ 'id' ], 'refunds', true );
				if( ! is_array( $refunds ) ) { $refunds = array(); }
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );

				if( $updated && $status !== $booking_group_data[ 'status' ] && $booking_group_data[ 'status' ] ) {
					// Update the group bookings status
					bookacti_update_booking_group_bookings( $booking_group_data );
					
					do_action( 'bookacti_booking_group_status_changed', $booking_group_data[ 'status' ], $item_booking[ 'booking_group' ], $item_booking[ 'bookings' ], array( 'is_admin' => true, 'context' => 'wc_order_refund', 'refund_action' => $refund_action ) );
					
					$new_booking_group        = $item_booking[ 'booking_group' ];
					$new_booking_group->state = $booking_group_data[ 'status' ];
					if( isset( $new_data[ 'active' ] ) )          { $new_booking_group->active = $booking_group_data[ 'active' ]; }
					if( ! isset( $new_booking_group->settings ) ) { $new_booking_group->settings = array(); }
					$new_booking_group->settings[ 'refunds' ] = $refunds;

					$send_to = $refund_action === 'manual' ? 'customer' : 'both';
					bookacti_send_booking_group_status_change_notification( $booking_group_data[ 'status' ], $new_booking_group, $item_booking[ 'booking_group' ], $send_to, array( 'refund_action' => $refund_action ) );
				}
			}
		}
	}
}


/**
 * Create coupons to refund selected bookings (1 coupon per user)
 * @since 1.16.0 (was bookacti_refund_booking_with_coupon)
 * @version 1.16.15
 * @param array $selected_bookings
 * @param string $booking_type Determine if the given id is a booking id or a booking group. Accepted values are 'single' or 'group'.
 * @param string $refund_message
 * @return array
 */
function bookacti_refund_selected_bookings_with_coupon( $selected_bookings, $refund_message = '' ) {
	if( ! class_exists( 'WC_Coupon' ) ) {
		return array( 
			'status'  => 'failed', 
			'error'   => 'coupon_api_not_loaded',
			'message' => esc_html__( 'The coupon API failed to be loaded.', 'booking-activities' )
		);
	}
	
	$coupons_data = array();
	
	// Sort bookings by user (to create only one coupon per user)
	$selected_bookings_per_user   = bookacti_sort_selected_bookings_by_user( $selected_bookings );
	$already_refunded_with_coupon = false;
	
	foreach( $selected_bookings_per_user as $user_id => $user_selected_bookings ) {
		// Calculate coupon amount
		$coupon_amount = 0;
		$items = $items_refunds = $order_ids = $item_names = array();
		$refund_data = array( 'user_id' => $user_id, 'selected_bookings' => $user_selected_bookings, 'user_message' => $refund_message, 'bookings' => array(), 'booking_groups' => array() );
		foreach( $user_selected_bookings[ 'bookings' ] as $booking_id => $booking ) {
			$order = wc_get_order( $booking->order_id );
			$item  = bookacti_get_order_item_by_booking_id( $booking );
			if( ! $item || ! $order ) { continue; }
			$item_id = $item->get_id();
			
			// Check if coupon already exists
			$existing_coupon_code = '';
			$refunds = ! empty( $booking->refunds ) && is_array( $booking->refunds ) ? bookacti_format_booking_refunds( $booking->refunds, $booking_id, 'single' ) : array();
			foreach( $refunds as $refund ) {
				if( isset( $refund[ 'coupon' ] ) ) {
					$existing_coupon_code = $refund[ 'coupon' ];
					break;
				}
			}
			// Backward compatibility
			if( ! $existing_coupon_code ) { $existing_coupon_code = wc_get_order_item_meta( $item_id, 'bookacti_refund_coupon', true ); }
			if( $existing_coupon_code )   { $already_refunded_with_coupon = true; continue; }
			
			$item_refund_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
			if( $item_refund_amount ) {
				$coupon_amount += $item_refund_amount;
				$refund_data[ 'bookings' ][ $booking_id ] = array(
					'refunds'   => $refunds,
					'order_id'  => $booking->order_id,
					'item_id'   => $item_id,
					'item_name' => $item->get_name(),
					'quantity'  => abs( intval( $item->get_quantity() ) ) - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) ),
				);
				$order_ids[] = $booking->order_id;
				$item_names[ $item_id ] = $item->get_name();
			}
		}
		foreach( $user_selected_bookings[ 'booking_groups' ] as $group_id => $booking_group ) {
			$order = wc_get_order( $booking_group->order_id );
			$item  = bookacti_get_order_item_by_booking_group_id( $booking_group );
			if( ! $item || ! $order ) { continue; }
			$item_id = $item->get_id();
			
			// Check if coupon already exists
			$existing_coupon_code = '';
			$refunds = ! empty( $booking_group->refunds ) && is_array( $booking_group->refunds ) ? bookacti_format_booking_refunds( $booking_group->refunds, $booking_id, 'single' ) : array();
			foreach( $refunds as $refund ) {
				if( isset( $refund[ 'coupon' ] ) ) {
					$existing_coupon_code = $refund[ 'coupon' ];
					break;
				}
			}
			// Backward compatibility
			if( ! $existing_coupon_code ) { $existing_coupon_code = wc_get_order_item_meta( $item_id, 'bookacti_refund_coupon', true ); }
			if( $existing_coupon_code )   { $already_refunded_with_coupon = true; continue; }
			
			$item_refund_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
			if( $item_refund_amount ) {
				$coupon_amount += $item_refund_amount;
				$refund_data[ 'booking_groups' ][ $group_id ] = array(
					'order_id'  => $booking_group->order_id,
					'item_id'   => $item_id,
					'item_name' => $item->get_name(),
					'quantity'  => abs( intval( $item->get_quantity() ) ) - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) ),
				);
				$order_ids[] = $booking_group->order_id;
				$item_names[ $item_id ] = $item->get_name();
			}
		}
		
		if( ! $coupon_amount || $coupon_amount <= 0 ) { continue; }
		
		// Find user emails
		$user = is_numeric( $user_id ) ? get_user_by( 'id', $user_id ) : null;
		$customer_emails = array();
		if( $user && ! empty( $user->billing_email ) && is_email( $user->billing_email ) ) {
			$customer_emails[] = $user->billing_email;
		}
		
		// Coupon description
		$coupon_description = esc_html__( 'Coupon created as a refund for:', 'booking-activities' );
		if( $user ) {
			/* translators: %s = User ID (integer) */
			$coupon_description .= PHP_EOL . sprintf( esc_html__( 'User #%s', 'booking-activities' ), $user->ID . ' (' . $user->user_login . ' / ' . $user->user_email . ')' );
		}
		$order_ids = bookacti_ids_to_array( $order_ids );
		if( $order_ids ) {
			/* translators: %s = Order ID (integer) */
			$coupon_description .= PHP_EOL . sprintf( esc_html__( 'Order #%s', 'booking-activities' ), implode( ', ', $order_ids ) );
		}
		$booking_ids = array_keys( $refund_data[ 'bookings' ] );
		if( $booking_ids ) {
			/* translators: %s = Booking ID (integer) */
			$coupon_description .= PHP_EOL . sprintf( esc_html__( 'Booking #%s', 'booking-activities' ), implode( ', ', $booking_ids ) );
		}
		$booking_group_ids = array_keys( $refund_data[ 'booking_groups' ] );
		if( $booking_group_ids ) {
			/* translators: %s = Booking group ID (integer) */
			$coupon_description .= PHP_EOL . sprintf( esc_html__( 'Booking group #%s', 'booking-activities' ), implode( ', ', $booking_group_ids ) );
		}
		$product_titles = array_filter( array_unique( $item_names ) );
		if( $product_titles ) {
			/* translators: %s = Product title */
			$coupon_description .= PHP_EOL . sprintf( esc_html__( 'Product: %s', 'booking-activities' ), implode( ', ', $product_titles ) );
		}
		if( $refund_message ) {
			$coupon_description .= PHP_EOL . PHP_EOL . esc_html__( 'Note:', 'booking-activities' ) . PHP_EOL . esc_html( $refund_message );
		}
		
		// Coupon data
		$coupon_data_n = array(
			'discount_type'      => 'fixed_cart',
			'usage_limit'        => 1,
			'amount'             => $coupon_amount,
			'email_restrictions' => array_filter( array_unique( $customer_emails ) ),
			'description'        => $coupon_description,
			'refund_data'        => $refund_data
		);
		
		$coupons_data[] = $coupon_data_n;
	}
	
	if( ! $coupons_data ) {
		if( $already_refunded_with_coupon ) {
			return array( 
				'status'  => 'failed', 
				'error'   => 'bookings_already_refunded_with_coupon',
				'message' => esc_html__( 'The selected bookings have already been refunded with a coupon.', 'booking-activities' )
			);
		}
		
		return array( 
			'status'  => 'failed', 
			'error'   => 'no_bookings_to_refund_with_coupon',
			'message' => esc_html__( 'The selected bookings cannot be refunded with a coupon.', 'booking-activities' )
		);
	}
	
	$return_data = array( 'coupons' => array(), 'booking_ids' => array(), 'booking_group_ids' => array() );
	
	foreach( $coupons_data as $coupon_data ) {
		$refund_data = $coupon_data[ 'refund_data' ];
		unset( $coupon_data[ 'refund_data' ] );
		
		// Generate coupon code and create the coupon
		$i             = 1;
		$user_id_str   = is_numeric( $refund_data[ 'user_id' ] ) ? $refund_data[ 'user_id' ] : substr( hash( 'sha256', $refund_data[ 'user_id' ] ), 0, 8 );
		$code_template = apply_filters( 'bookacti_wc_refund_coupon_code_template', 'R{user_id}N{refund_number}' );
		$code_template = str_replace( '{user_id}', '%1$s', $code_template );
		$code_template = str_replace( '{refund_number}', '%2$s', $code_template );

		$coupon_data = apply_filters( 'bookacti_wc_refund_coupon_data', $coupon_data, $refund_data );
		
		do {
			$coupon_data[ 'code' ] = apply_filters( 'bookacti_wc_refund_coupon_code', sprintf( $code_template, $user_id_str, $i ), $coupon_data, $refund_data );
			$code_already_exists   = wc_get_coupon_id_by_code( $coupon_data[ 'code' ] );
			++$i;
		}
		while( $code_already_exists );
		
		// Create the coupon
		$coupon = new WC_Coupon();
		foreach( $coupon_data as $key => $value ) {
			if( method_exists( $coupon, 'set_' . $key ) ) {
				$coupon->{ 'set_' . $key }( $value );
			}
		}
		$coupon_id     = $coupon->save();
		$coupon_code   = $coupon->get_code( 'edit' );
		$coupon_amount = $coupon->get_amount( 'edit' );
		$coupon_dt     = $coupon->get_date_created( 'edit' );
		$coupon_dt->setTimezone( new DateTimeZone( 'UTC' ) );
		
		// Update bookings (group) refunds records
		$refund_data_per_type = array( 'single' => $refund_data[ 'bookings' ], 'group' => $refund_data[ 'booking_groups' ] );
		foreach( $refund_data_per_type as $booking_type => $bookings_refund_data ) {
			foreach( $bookings_refund_data as $booking_id => $booking_refund_data ) {
				$_selected_booking = array( 
					'bookings'        => $booking_type === 'single' ? $selected_bookings[ 'bookings' ][ $booking_id ] : array(),
					'booking_groups'  => $booking_type === 'group' ? $selected_bookings[ 'booking_groups' ][ $booking_id ] : array(),
					'groups_bookings' => $booking_type === 'group' && isset( $selected_bookings[ 'groups_bookings' ][ $booking_id ] ) ? $selected_bookings[ 'groups_bookings' ][ $booking_id ] : array()
				);

				$booking = $booking_type === 'group' ? $selected_bookings[ 'booking_groups' ][ $booking_id ] : $selected_bookings[ 'bookings' ][ $booking_id ];
				$refunds = ! empty( $booking->refunds ) && is_array( $booking->refunds ) ? $booking->refunds : array();

				$refunds[ $coupon_code ] = apply_filters( 'bookacti_wc_booking_refund_coupon_data', array( 
					'date'     => $coupon_dt->format( 'Y-m-d H:i:s' ), 
					'quantity' => $booking_refund_data[ 'quantity' ], 
					'amount'   => $coupon_amount, 
					'method'   => 'coupon', 
					'coupon'   => $coupon_code
				), $coupon, $coupon_data, $booking_refund_data, $_selected_booking );

				bookacti_update_metadata( $booking_type === 'group' ? 'booking_group' : 'booking', $booking_id, array( 'refunds' => $refunds ) );
			}
		}

		$return_data[ 'booking_ids' ]       = bookacti_ids_to_array( array_merge( $return_data[ 'booking_ids' ], array_keys( $refund_data[ 'bookings' ] ) ) );
		$return_data[ 'booking_group_ids' ] = bookacti_ids_to_array( array_merge( $return_data[ 'booking_group_ids' ], array_keys( $refund_data[ 'booking_groups' ] ) ) );
		$return_data[ 'coupons' ][] = array(
			'code'        => $coupon_code,
			'amount'      => $coupon_amount,
			'price'       => html_entity_decode( wc_price( $coupon_amount ) ),
			'coupon'      => $coupon,
			'coupon_data' => $coupon_data,
			'refund_data' => $refund_data
		);
	}
	
	// Return data
	if( $return_data[ 'coupons' ] ) {
		$return_data[ 'status' ]     = 'success';
		$return_data[ 'new_status' ] = 'refunded';
		
		$rows        = '';
		$bookings_nb = 0; 
		$coupons_nb  = count( $return_data[ 'coupons' ] );
		foreach( $return_data[ 'coupons' ] as $coupon_return_data ) {
			$booking_ids       = array_keys( $coupon_return_data[ 'refund_data' ][ 'bookings' ] );
			$booking_group_ids = array_keys( $coupon_return_data[ 'refund_data' ][ 'booking_groups' ] );
			$refunded_bookings_txt = '';
			if( $booking_ids ) {
				$bookings_nb += count( $booking_ids );
				$refunded_bookings_txt .= sprintf( esc_html__( 'Booking #%s', 'booking-activities' ), implode( ', ', $booking_ids ) );
			}
			if( $booking_group_ids ) {
				$bookings_nb += count( $booking_group_ids );
				if( $refunded_bookings_txt ) { $refunded_bookings_txt .= '<br/>'; }
				$refunded_bookings_txt .= sprintf( esc_html__( 'Booking group #%s', 'booking-activities' ), implode( ', ', $booking_group_ids ) );
			}
			
			$rows .= '<tr><td><strong>' . strtoupper( $coupon_return_data[ 'code' ] ) . '</strong></td><td>' . $coupon_return_data[ 'price' ] . '</td><td>' . $refunded_bookings_txt . '</td></tr>';
		}
		
		$return_data[ 'message' ] = '<span>' . sprintf( 
			/* translators: %1$s = number of bookings refunded. E.g.: "2 bookings". %2$s = number of the coupons created, e.g.: "2 coupons".  */
			esc_html( _n( '%1$s has been refunded with %2$s.', '%1$s have been refunded with %2$s.', $bookings_nb, 'booking-activities' ) ),
			'<strong>' . sprintf( esc_html( _n( '%s booking', '%s bookings', $bookings_nb, 'booking-activities' ) ), $bookings_nb ) . '</strong>',
			/* translators: %s = number of the coupon(s). */
			'<strong>' . sprintf( esc_html( _n( '%s coupon', '%s coupons', $coupons_nb, 'booking-activities' ) ), $coupons_nb ) . '</strong>'
		) . '</span>';
		$return_data[ 'message' ] .= '<table class="bookacti-refund-table bookacti-refund-table-coupon"><thead><tr><th>' . esc_html__( 'Coupon code', 'booking-activities' ) . '</th><th>' . esc_html__( 'Coupon value', 'booking-activities' ) . '</th><th>' . esc_html__( 'Refunded bookings', 'booking-activities' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table>';
		$return_data[ 'message' ] .= '<span><em>' . esc_html__( 'You can use your coupon code the next time you place an order.', 'booking-activities' ) . '</em></span>';
	} 
	else {
		$return_data[ 'status' ]  = 'failed';
		$return_data[ 'error' ]   = 'cannot_create_coupon';
		$return_data[ 'message' ] = esc_html__( 'The coupon code could not be generated.', 'booking-activities' );
	}
	
	return $return_data;
}


/**
 * Check if a coupon code can be used
 * @since 1.11.3
 * @version 1.16.45
 * @param string $coupon_code
 * @return WP_Error|true
 */
function bookacti_wc_is_coupon_code_valid( $coupon_code ) {
	$coupon        = new WC_Coupon( $coupon_code );   
	$error_code    = 0;
	$error_message = '';
	
	// Check if the coupon exists and is published
	if( ( ! $coupon->get_id() && ! $coupon->get_virtual() ) || 'trash' === $coupon->get_status() ) {
		$error_code = WC_Coupon::E_WC_COUPON_NOT_EXIST;
	}

	// Check if the coupon has been already used
	if( ! $error_code && $coupon->get_usage_limit() ) {
		$usage_count           = $coupon->get_usage_count();
		$data_store            = $coupon->get_data_store();
		$tentative_usage_count = is_callable( array( $data_store, 'get_tentative_usage_count' ) ) ? $data_store->get_tentative_usage_count( $coupon->get_id() ) : 0;
		if( $usage_count + $tentative_usage_count >= $coupon->get_usage_limit() ) {
			$error_code = WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED;
		}
	}
	
	// Check if the coupon is expired
	if( ! $error_code && $coupon->get_date_expires() && time() > $coupon->get_date_expires()->getTimestamp() ) {
		$error_code = WC_Coupon::E_WC_COUPON_EXPIRED;
	}
	
	// Check if the coupon has been disabled by a third-party plugin
	if( ! $error_code ) {
		$is_valid = true;
		try {
			$is_valid = apply_filters( 'woocommerce_coupon_is_valid', $is_valid, $coupon, null );
		} catch ( Exception $e ) {
			$is_valid      = false;
			$error_code    = $e->getCode() ? $e->getCode() : WC_Coupon::E_WC_COUPON_INVALID_FILTERED;
			$error_message = apply_filters( 'woocommerce_coupon_error', $e->getMessage(), $e->getCode(), $coupon );
		}
		
		if( ! $is_valid && ! $error_code ) {
			$error_code = WC_Coupon::E_WC_COUPON_INVALID_FILTERED;
		}
	}
	
	// Get the corresponding error message
	if( $error_code && ! $error_message ) {
		$error_message = is_numeric( $error_code ) ? $coupon->get_coupon_error( $error_code ) : $error_code;
		$error_code    = is_numeric( $error_code ) ? intval( $error_code ) : 0;
		$error_message = apply_filters( 'woocommerce_coupon_error', $error_message, $error_code, $coupon );
	}
	
	return apply_filters( 'bookacti_wc_is_coupon_code_valid', $error_message ? new WP_Error( $error_code, $error_message ) : true, $coupon, $coupon_code );
}


/**
 * Refund selected bookings through the payment gateway (for supported gateways)
 * @since 1.16.0 (was bookacti_auto_refund_booking)
 * @param array $selected_bookings
 * @param string $refund_message
 * @return array
 */
function bookacti_refund_selected_bookings_with_gateway( $selected_bookings, $refund_message = '' ) {
	// Sort bookings by order (to refund all the order items at once)
	$selected_bookings_per_order = bookacti_sort_selected_bookings_by_order( $selected_bookings );
	
	// Order refund data
	$orders_refund_data = array();
	$default_order_refund_data = array(
		'amount'         => 0,
		'order_id'       => 0,
		'line_items'     => array(),
		'reason'         => esc_html__( 'Auto refund triggered by the customer.', 'booking-activities' )
		                 . ( $refund_message ? ' ' . esc_html__( 'Note:', 'booking-activities' ) . ' ' . esc_html( $refund_message ) : '' ),
		'refund_payment' => true
	);
	
	$price_decimals = wc_get_price_decimals();
	
	foreach( $selected_bookings_per_order as $order_id => $order_selected_bookings ) {
		// Get the order and check if the the gateway supports refunds
		$order = wc_get_order( $order_id );
		if( ! $order ) { continue; }
		if( ! bookacti_does_order_support_auto_refund( $order ) ) { continue; }
		
		// Calculate refund amount
		$refund_amount = 0;
		$line_items	   = array();
		$refund_data   = array( 'order_id' => $order_id, 'selected_bookings' => $order_selected_bookings, 'user_message' => $refund_message, 'bookings' => array(), 'booking_groups' => array() );
		foreach( $order_selected_bookings[ 'bookings' ] as $booking_id => $booking ) {
			$item = bookacti_get_order_item_by_booking_id( $booking );
			if( ! $item ) { continue; }
			
			$item_refund_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
			if( ! $item_refund_amount ) { continue; }
			
			$refund_amount += $item_refund_amount;
			
			$item_id          = $item->get_id();
			$refunded_amounts = bookacti_wc_get_item_total_refunded( $item, true );
			$item_taxes       = $item->get_taxes();
			if( isset( $item_taxes[ 'total' ] ) ) {
				foreach( $item_taxes[ 'total' ] as $tax_id => $total ) {
					$refunded_tax_amount = abs( (float) $order->get_tax_refunded_for_item( $item_id, $tax_id ) );
					$refund_tax[ $tax_id ] = wc_format_decimal( $total - $refunded_tax_amount );
				}
			} else {
				$refund_tax[] = round( $item->get_total_tax(), $price_decimals ) - $refunded_amounts[ 'tax' ];
			}

			$line_items[ $item_id ] = array(
				'qty'          => abs( intval( $item->get_quantity() ) ) - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) ),
				'refund_total' => round( $item->get_total(), $price_decimals ) - $refunded_amounts[ 'total' ],
				'refund_tax'   => $refund_tax
			);

			$refund_data[ 'bookings' ][ $booking_id ] = array(
				'order_id'     => $order_id,
				'item_id'      => $item_id,
				'item_name'    => $item->get_name(),
				'quantity'     => $line_items[ $item_id ][ 'qty' ],
				'refund_total' => $line_items[ $item_id ][ 'refund_total' ],
				'refund_tax'   => $line_items[ $item_id ][ 'refund_tax' ],
			);
		}
		foreach( $order_selected_bookings[ 'booking_groups' ] as $group_id => $booking_group ) {
			$item = bookacti_get_order_item_by_booking_group_id( $booking_group );
			if( ! $item ) { continue; }
			
			$item_refund_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
			if( ! $item_refund_amount ) { continue; }
			
			$refund_amount += $item_refund_amount;
			
			$item_id          = $item->get_id();
			$refunded_amounts = bookacti_wc_get_item_total_refunded( $item, true );
			$item_taxes       = $item->get_taxes();
			if( isset( $item_taxes[ 'total' ] ) ) {
				foreach( $item_taxes[ 'total' ] as $tax_id => $total ) {
					$refunded_tax_amount = abs( (float) $order->get_tax_refunded_for_item( $item_id, $tax_id ) );
					$refund_tax[ $tax_id ] = wc_format_decimal( $total - $refunded_tax_amount );
				}
			} else {
				$refund_tax[] = round( $item->get_total_tax(), $price_decimals ) - $refunded_amounts[ 'tax' ];
			}

			$line_items[ $item_id ] = array(
				'qty'          => abs( intval( $item->get_quantity() ) ) - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) ),
				'refund_total' => round( $item->get_total(), $price_decimals ) - $refunded_amounts[ 'total' ],
				'refund_tax'   => $refund_tax
			);

			$refund_data[ 'booking_groups' ][ $group_id ] = array(
				'order_id'     => $order_id,
				'item_id'      => $item_id,
				'item_name'    => $item->get_name(),
				'quantity'     => $line_items[ $item_id ][ 'qty' ],
				'refund_total' => $line_items[ $item_id ][ 'refund_total' ],
				'refund_tax'   => $line_items[ $item_id ][ 'refund_tax' ],
			);
		}
		if( ! $line_items || ! $refund_amount ) { continue; }
		
		// Order refund data
		$order_refund_data = $default_order_refund_data;
		$order_refund_data[ 'amount' ]      = $refund_amount;
		$order_refund_data[ 'order_id' ]    = $order_id;
		$order_refund_data[ 'line_items' ]  = $line_items;
		$order_refund_data[ 'refund_data' ] = $refund_data;
		
		$orders_refund_data[ $order_id ] = $order_refund_data;
	}
	
	if( ! $orders_refund_data ) {
		return array( 
			'status'  => 'failed', 
			'error'   => 'no_bookings_to_refund_with_gateway',
			'message' => esc_html__( 'The selected bookings cannot be refunded with the payment gateway.', 'booking-activities' )
		);
	}
	
	$return_data = array( 'refunds' => array(), 'booking_ids' => array(), 'booking_group_ids' => array() );
	$wp_errors   = array();
	
	foreach( $orders_refund_data as $order_id => $order_refund_data ) {
		$refund_data = $order_refund_data[ 'refund_data' ];
		unset( $order_refund_data[ 'refund_data' ] );
		
		$order_refund_data = apply_filters( 'bookacti_wc_order_refund_gateway_data', $order_refund_data, $refund_data );
		
		$refund = wc_create_refund( $order_refund_data );
		
		if( $refund && ! is_wp_error( $refund ) ) {
			$return_data[ 'booking_ids' ]       = bookacti_ids_to_array( array_merge( $return_data[ 'booking_ids' ], array_keys( $refund_data[ 'bookings' ] ) ) );
			$return_data[ 'booking_group_ids' ] = bookacti_ids_to_array( array_merge( $return_data[ 'booking_group_ids' ], array_keys( $refund_data[ 'booking_groups' ] ) ) );
			$return_data[ 'refunds' ][] = array(
				'order_id'          => $order_id,
				'amount'            => $order_refund_data[ 'amount' ],
				'price'             => html_entity_decode( wc_price( $order_refund_data[ 'amount' ] ) ),
				'refund'            => $refund,
				'order_refund_data' => $order_refund_data,
				'refund_data'       => $refund_data
			);
		
		} else if( is_wp_error( $refund ) ) {
			$wp_errors[] = $refund;
		}
	}
	
	// Return data
	if( $return_data[ 'refunds' ] ) {
		$return_data[ 'status' ]               = 'success';
		$return_data[ 'new_status' ]           = 'refunded';
		$return_data[ 'do_not_update_status' ] = true;
		
		$rows        = '';
		$bookings_nb = 0;
		$refunds_nb  = count( $return_data[ 'refunds' ] );
		foreach( $return_data[ 'refunds' ] as $refund_return_data ) {
			$booking_ids       = array_keys( $refund_return_data[ 'refund_data' ][ 'bookings' ] );
			$booking_group_ids = array_keys( $refund_return_data[ 'refund_data' ][ 'booking_groups' ] );
			$refunded_bookings_txt = '';
			if( $booking_ids ) {
				$bookings_nb += count( $booking_ids );
				$refunded_bookings_txt .= sprintf( esc_html__( 'Booking #%s', 'booking-activities' ), implode( ', ', $booking_ids ) );
			}
			if( $booking_group_ids ) {
				$bookings_nb += count( $booking_group_ids );
				if( $refunded_bookings_txt ) { $refunded_bookings_txt .= '<br/>'; }
				$refunded_bookings_txt .= sprintf( esc_html__( 'Booking group #%s', 'booking-activities' ), implode( ', ', $booking_group_ids ) );
			}
			
			$rows .= '<tr><td><strong>' . intval( $refund_return_data[ 'order_id' ] ) . '</strong></td><td>' . $refund_return_data[ 'price' ] . '</td><td>' . $refunded_bookings_txt . '</td></tr>';
		}
		
		$return_data[ 'message' ] = '<span>' . sprintf( 
			/* translators: %1$s = number of bookings refunded. E.g.: "2 bookings". %2$s = number of the orders, e.g.: "2 orders".  */
			esc_html( _n( '%1$s has been refunded in %2$s.', '%1$s have been refunded in %2$s.', $bookings_nb, 'booking-activities' ) ),
			'<strong>' . sprintf( esc_html( _n( '%s booking', '%s bookings', $bookings_nb, 'booking-activities' ) ), $bookings_nb ) . '</strong>',
			/* translators: %s = number of the orders. */
			'<strong>' . sprintf( esc_html( _n( '%s order', '%s orders', $refunds_nb, 'booking-activities' ) ), $refunds_nb ) . '</strong>'
		) . '</span>';
		$return_data[ 'message' ] .= '<table class="bookacti-refund-table bookacti-refund-table-gateway"><thead><tr><th>' . esc_html__( 'Order ID', 'booking-activities' ) . '</th><th>' . esc_html__( 'Refunded amount', 'booking-activities' ) . '</th><th>' . esc_html__( 'Refunded bookings', 'booking-activities' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table>';
	} 
	else {
		$return_data[ 'status' ]    = 'failed';
		$return_data[ 'error' ]     = 'cannot_refund_via_gateway';
		$return_data[ 'wp_errors' ] = $wp_errors;
		$messages = array();
		foreach( $wp_errors as $wp_error ) {
			$messages[] = $wp_error->get_error_message();
		}
		$return_data[ 'message' ] = implode( '</li><li>', array_unique( $messages ) );
	}
	
	return $return_data;
}


/**
 * Get refunded amounts total and tax for an order item
 * @since 1.9.0
 * @version 1.12.9
 * @param WC_Order_Item_Product $item
 * @param boolean $return_array
 * @return float|array
 */
function bookacti_wc_get_item_total_refunded( $item, $return_array = false ) {
	$refunded = array( 'total' => 0, 'tax' => 0 );
	
	$order = wc_get_order( $item->get_order_id() );
	if( ! $order ) { return $refunded; }
	
	$item_id = $item->get_id();
	
	foreach( $order->get_refunds() as $refund ) {
		foreach( $refund->get_items( 'line_item' ) as $refunded_item ) {
			if( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === intval( $item_id ) ) {
				$refunded[ 'total' ] += abs( (float) $refunded_item->get_total() );
				$refunded[ 'tax' ] += abs( (float) $refunded_item->get_total_tax() );
			}
		}
	}
	
	if( $return_array ) { return $refunded; }
	
	return round( $refunded[ 'total' ] + $refunded[ 'tax' ], wc_get_price_decimals() );
}


/**
 * Get the remaining amount to refund for an item
 * @since 1.9.0
 * @param WC_Order_Item_Product $item
 * @param boolean $return_array
 * @return float|array
 */
function bookacti_wc_get_item_remaining_refund_amount( $item, $return_array = false ) {
	$refunded = bookacti_wc_get_item_total_refunded( $item, true );
	$price_decimals = wc_get_price_decimals();
	
	$to_refund = array( 
		'total' => round( $item->get_total(), $price_decimals ) - $refunded[ 'total' ],
		'tax' => round( $item->get_total_tax(), $price_decimals ) - $refunded[ 'tax' ],
	);
	
	if( $return_array ) { return $to_refund; }
	
	return round( $to_refund[ 'total' ] + $to_refund[ 'tax' ], $price_decimals );
}




// FORMS

/**
 * Check if the form field is supported by WC
 * @since 1.15.0
 * @param array $field_data
 * @return boolean
 */
function bookacti_wc_is_form_field_supported( $field_data ) {
	$true = ! in_array( $field_data[ 'name' ], array( 'login', 'quantity', 'submit' ), true );
	return apply_filters( 'bookacti_wc_is_form_field_supported', $true, $field_data );
}


/**
 * Send WC Reset Password notification
 * @since 1.15.5
 * @return string|true
 */
function bookacti_wc_send_reset_password_notification() {
	$response = WC_Shortcode_My_Account::retrieve_password();
	if( ! $response ) {
		$wc_notices = wc_get_notices( 'error' );
		if( $wc_notices ) {
			$response = array();
			foreach( $wc_notices as $wc_notice ) {
				$response[] = $wc_notice[ 'notice' ];
			}
			wc_clear_notices();
		}
	}
	return $response;
}




// SETTINGS
// WOOCOMMERCE PRODUCTS SETTINGS

/**
 * Display WC products settings section
 * @since 1.7.16
 */
function bookacti_settings_section_wc_products_callback() {}


/**
 * Setting for: Booking form location on product pages
 * @since 1.7.16
 */
function bookacti_settings_wc_product_pages_booking_form_location_callback() {
	$args = array(
		'type'    => 'select',
		'name'    => 'bookacti_products_settings[wc_product_pages_booking_form_location]',
		'id'      => 'wc_product_pages_booking_form_location',
		'options' => array( 
			'default'    => esc_html__( 'Inline (original layout)', 'booking-activities' ),
			'form_below' => esc_html__( 'Full width', 'booking-activities' ),
		),
		'value'   => bookacti_get_setting_value( 'bookacti_products_settings', 'wc_product_pages_booking_form_location' ),
		          /* translators: %s is the name of the described option: "Inline (original layout)" */
		'tip'     => sprintf( esc_html__( '%s: display the booking form before the add to cart button, without changing your theme layout.', 'booking-activities' ), '<strong>' . esc_html__( 'Inline (original layout)', 'booking-activities' ) . '</strong>' )
		          . '<br/>' 
		          /* translators: %s is the name of the described option: "Full width (default)" */
		          . sprintf( esc_html__( '%s: move the add to cart form below the product summary. This option may not work properly with certain themes.', 'booking-activities' ), '<strong>' . esc_html__( 'Full width', 'booking-activities' ) . '</strong>' )
	);
	bookacti_display_field( $args );
}




// WOOCOMMERCE ACCOUNT SETTINGS

/**
 * Display WC account settings section
 * @since 1.7.16
 */
function bookacti_settings_section_wc_account_callback() {}


/**
 * Setting for: Bookings page in My Account
 * @since 1.7.16
 * @version 1.16.38
 */
function bookacti_settings_wc_my_account_bookings_page_id_callback() {
	$options = array(
		'-1' => esc_html__( 'Disabled' ),
		'0'  => esc_html__( 'Default booking list', 'booking-activities' ),
	);
	$pages = get_pages( array( 'sort_column' => 'menu_order', 'sort_order' => 'ASC', 'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ) ) );
	foreach( $pages as $page ) {
		$options[ $page->ID ] = $page->post_title ? apply_filters( 'bookacti_translate_text_external', $page->post_title, false, true, array( 'domain' => 'wordpress', 'object_type' => 'page', 'object_id' => $page->ID, 'field' => 'post_title' ) ) : $page->post_title;
	}

	$args = array(
		'type'    => 'select',
		'name'    => 'bookacti_account_settings[wc_my_account_bookings_page_id]',
		'id'      => 'wc_my_account_bookings_page_id',
		'class'   => 'bookacti-select2-no-ajax',
		'options' => $options,
		'value'   => bookacti_get_setting_value( 'bookacti_account_settings', 'wc_my_account_bookings_page_id' ),
		'tip'     => esc_html__( 'Select the page to display in the "Bookings" tab of the "My account" area. You can also display the default booking list, or completely disable this tab.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}




// WOOCOMMERCE CART SETTINGS

/**
 * Display WC cart settings section
 */
function bookacti_settings_section_cart_callback() {}

/**
 * Setting for: Activate cart expiration
 * @version 1.7.16
 */
function bookacti_settings_field_activate_cart_expiration_callback() {
	$args = array(
		'type'  => 'checkbox',
		'name'  => 'bookacti_cart_settings[is_cart_expiration_active]',
		'id'    => 'is_cart_expiration_active',
		'value' => bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' ),
		'tip'   => esc_html__( "If cart expiration is off, the booking is made at the end of the checkout process. It means that an event available at the moment you add it to cart can be no longer available at the moment you wish to complete the order. With cart expiration on, the booking is made when it is added to cart and remains temporary until the end of the checkout process.", 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Activate per product expiration
 * @version 1.7.16
 */
function bookacti_settings_field_per_product_expiration_callback() {
	$args = array(
		'type'  => 'checkbox',
		'name'  => 'bookacti_cart_settings[is_cart_expiration_per_product]',
		'id'    => 'is_cart_expiration_per_product',
		'value' => bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' ),
		'tip'   => esc_html__( 'The expiration time will be set for each product independantly, each with their own countdown before being removed from cart.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Set amount of time before expiration
 * @version 1.7.16
 */
function bookacti_settings_field_cart_timeout_callback() { 
	$args = array(
		'type'    => 'number',
		'name'    => 'bookacti_cart_settings[cart_timeout]',
		'id'      => 'cart_expiration_time',
		'options' => array( 'min' => 1, 'step' => 1 ),
		'value'   => bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' ),
		'tip'     => esc_html__( 'Define the amount of time a user has before his cart gets empty.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Reset the countdown each time a change occur to cart
 * @version 1.7.16
 */
function bookacti_settings_field_reset_cart_timeout_on_change_callback() {
	$args = array(
		'type'  => 'checkbox',
		'name'  => 'bookacti_cart_settings[reset_cart_timeout_on_change]',
		'id'    => 'reset_cart_timeout_on_change',
		'value' => bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' ),
		'tip'   => esc_html__( 'The countdown will be reset each time a product is added, or when a product quantity is changed.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}




// GENERAL 

/**
 * Determines if user is shop manager
 * @version 1.12.8
 * @param int $user_id Default to current user
 * @return boolean
 */
function bookacti_is_shop_manager( $user_id = 0 ) {
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	$user = get_user_by( 'id', $user_id );
	return apply_filters( 'bookacti_is_shop_manager', isset( $user->roles ) && in_array( 'shop_manager', $user->roles, true ), $user_id );
}


/**
 * Check if the current page is a WooCommerce screen
 * @since 1.7.3 (was bookacti_is_wc_edit_product_screen)
 * @version 1.12.8
 * @return boolean
 */
function bookacti_is_wc_screen( $screen_ids = array() ) {
	$is_wc_screen = false;
	$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
	if( ! empty( $current_screen ) ) {
		if( ! $screen_ids || ! is_array( $screen_ids ) ) { $screen_ids = wc_get_screen_ids(); }
		if( isset( $current_screen->id ) && in_array( $current_screen->id, $screen_ids, true ) ) { $is_wc_screen = true; }
	}
	return apply_filters( 'bookacti_is_wc_screen', $is_wc_screen, $screen_ids );
}