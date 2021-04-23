<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CART

/**
 * Add bookings to cart item or merge the bookings to an existing cart item
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param array $product_bookings_data
 * @return array
 */
function bookacti_wc_add_bookings_to_cart( $product_bookings_data ) {
	$return_array = array( 'status' => 'failed', 'bookings' => array() );
	
	// Check if one of the cart items is identical
	global $woocommerce;
	$cart_contents = $woocommerce->cart->get_cart();
	foreach( $cart_contents as $cart_item_key => $cart_item ) {
		// Same product
		if( $product_bookings_data[ 'product_id' ] !== $cart_item[ 'product_id' ] ) { continue; }
		// Same variation
		if( ( empty( $product_bookings_data[ 'variation_id' ] ) && ! empty( $cart_item[ 'variation_id' ] ) )
		||  ( ! empty( $product_bookings_data[ 'variation_id' ] ) && ( empty( $cart_item[ 'variation_id' ] ) || $product_bookings_data[ 'variation_id' ] !== $cart_item[ 'variation_id' ] ) ) ) { continue; }
		// Same booked events
		$cart_item_events = bookacti_wc_get_cart_item_picked_events( $cart_item_key );
		if( ! $cart_item_events ) { continue; }
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
				'user_id'			=> $product_bookings_data[ 'user_id' ],
				'form_id'			=> $product_bookings_data[ 'form_id' ],
				'event_id'			=> $picked_event[ 'id' ],
				'event_start'		=> $picked_event[ 'start' ],
				'event_end'			=> $picked_event[ 'end' ],
				'quantity'			=> $product_bookings_data[ 'quantity' ],
				'status'			=> $product_bookings_data[ 'status' ],
				'payment_status'	=> $product_bookings_data[ 'payment_status' ],
				'expiration_date'	=> $product_bookings_data[ 'expiration_date' ],
				'active'			=> 'according_to_status'
			) );
			$booking_id = bookacti_insert_booking( $booking_data );
			if( $booking_id ) {
				do_action( 'bookacti_wc_product_booking_form_booking_inserted', $booking_id, 'single', $picked_event, $product_bookings_data );
				$return_array[ 'bookings' ][] = array( 'id' => $booking_id, 'type' => 'single' );
				$return_array[ 'booking_ids' ][] = $booking_id;
			}

		// Booking group
		} else {
			// Book all events of the group
			$booking_group_data = bookacti_sanitize_booking_group_data( array( 
				'user_id'			=> $product_bookings_data[ 'user_id' ],
				'form_id'			=> $product_bookings_data[ 'form_id' ],
				'event_group_id'	=> $picked_event[ 'group_id' ],
				'grouped_events'	=> $picked_event[ 'events' ],
				'quantity'			=> $product_bookings_data[ 'quantity' ],
				'status'			=> $product_bookings_data[ 'status' ],
				'payment_status'	=> $product_bookings_data[ 'payment_status' ],
				'expiration_date'	=> $product_bookings_data[ 'expiration_date' ],
				'active'			=> 'according_to_status'
			) );
			$booking_group_id = bookacti_book_group_of_events( $booking_group_data );
			if( $booking_group_id ) {
				do_action( 'bookacti_wc_product_booking_form_booking_inserted', $booking_group_id, 'group', $picked_event, $product_bookings_data );
				$return_array[ 'bookings' ][] = array( 'id' => $booking_group_id, 'type' => 'group' );
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
		$return_array[ 'message' ] = esc_html__( 'An error occured while trying to add a booking to cart.', 'booking-activities' );
	}
	
	return $return_array;
}


/**
 * Get in cart bookings per cart item
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param array $cart_items
 * @param array $filters
 * @return array
 */
function bookacti_wc_get_cart_items_bookings( $cart_items = array(), $filters = array() ) {
	global $woocommerce;
	if( ! $cart_items ) { $cart_items = $woocommerce->cart->get_cart(); }
	$in__cart_item_key = ! empty( $filters[ 'in__cart_item_key' ] ) ? $filters[ 'in__cart_item_key' ] : array();
	$cart_item_keys_by_booking_id = array();
	$cart_item_keys_by_booking_group_id = array();
	if( $cart_items ) {
		foreach( $cart_items as $cart_item_key => $cart_item ) {
			if( empty( $cart_item[ '_bookacti_options' ][ 'bookings' ] ) ) { continue; }
			if( $in__cart_item_key && ! in_array( $cart_item_key, $in__cart_item_key, true ) ) { continue; }
			$cart_item_bookings_ids = bookacti_maybe_decode_json( $cart_item[ '_bookacti_options' ][ 'bookings' ], true );
			foreach( $cart_item_bookings_ids as $cart_item_booking_id ) {
				if( $cart_item_booking_id[ 'type' ] === 'single' )		{ $cart_item_keys_by_booking_id[ $cart_item_booking_id[ 'id' ] ] = $cart_item_key; }
				else if( $cart_item_booking_id[ 'type' ] === 'group' )	{ $cart_item_keys_by_booking_group_id[ $cart_item_booking_id[ 'id' ] ] = $cart_item_key; }
			}
		}
	}
	
	$bookings = array();
	if( $cart_item_keys_by_booking_id || $cart_item_keys_by_booking_group_id ) {
		$filters = apply_filters( 'bookacti_wc_cart_items_bookings_filters', bookacti_format_booking_filters( array_merge( $filters, array( 
			'in__booking_id' => array_keys( $cart_item_keys_by_booking_id ), 
			'in__booking_group_id' => array_keys( $cart_item_keys_by_booking_group_id ), 
			'booking_group_id_operator' => 'OR' ) ) ) );
		$bookings = bookacti_get_bookings( $filters );
	}
	
	$cart_items_bookings = array();
	if( $bookings ) {
		foreach( $bookings as $booking ) {
			$booking_id = intval( $booking->id );
			$group_id = intval( $booking->group_id );
			
			$cart_item_key = '';
			$booking_type = '';
			if( $group_id && ! empty( $cart_item_keys_by_booking_group_id[ $group_id ] ) ) { 
				$cart_item_key = $cart_item_keys_by_booking_group_id[ $group_id ];
				$booking_type = 'group';
			} else if( ! $group_id && ! empty( $cart_item_keys_by_booking_id[ $booking_id ] ) ) { 
				$cart_item_key = $cart_item_keys_by_booking_id[ $booking_id ];
				$booking_type = 'single';
			}
			
			if( $cart_item_key ) {
				if( ! isset( $cart_items_bookings[ $cart_item_key ] ) ) { $cart_items_bookings[ $cart_item_key ] = array(); }
				if( $booking_type === 'single' ) { $cart_items_bookings[ $cart_item_key ][] = array( 'id' => $booking_id, 'type' => 'single', 'bookings' => array( $booking ) ); }
				else if( $booking_type === 'group' ) { 
					$group_exists = false;
					foreach( $cart_items_bookings[ $cart_item_key ] as $i => $cart_item_booking ) {
						if( $cart_item_booking[ 'type' ] === 'group' && $cart_item_booking[ 'id' ] === $group_id ) {
							$group_exists = true;
							$cart_items_bookings[ $cart_item_key ][ $i ][ 'bookings' ][] = $booking;
						}
					}
					if( ! $group_exists ) {
						$cart_items_bookings[ $cart_item_key ][] = array( 'id' => $group_id, 'type' => 'group', 'bookings' => array( $booking ) );
					}
				}
			}
		}
	}
	
	return $cart_items_bookings;
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
 * Format cart item booked events like a picked events array
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param string $cart_item_key
 * @param boolean $one_entry_per_group
 * @return array
 */
function bookacti_wc_get_cart_item_picked_events( $cart_item_key, $one_entry_per_group = false ) {
	global $woocommerce;
	$item = $woocommerce->cart->get_cart_item( $cart_item_key );
	if( ! $item ) { return array(); }
	if( empty( $item[ '_bookacti_options' ][ 'bookings' ] ) ) { return array(); }
	
	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	if( ! $cart_item_bookings ) { return ''; }
	
	$events = array();
	foreach( $cart_item_bookings as $cart_item_booking ) {
		foreach( $cart_item_booking[ 'bookings' ] as $booking ) {
			$events[] = array(
				'group_id' => $cart_item_booking[ 'type' ] === 'group' && ! empty( $booking->event_group_id ) ? $booking->event_group_id : 0,
				'id' => ! empty( $booking->event_id ) ? $booking->event_id : 0,
				'start' => ! empty( $booking->event_start ) ? $booking->event_start : '',
				'end' => ! empty( $booking->event_end ) ? $booking->event_end : '',
			);
		}
	}
	
	return bookacti_format_picked_events( $events, $one_entry_per_group );
}


/**
 * Check if we can update the quantity of a cart item bookings
 * @since 1.9.0
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
	return $response;
}


/**
 * Check if we can update the user of a cart item bookings
 * @since 1.9.0
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
	return $response;
}


/**
 * Update the bookings bound to a cart item
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param string|array $cart_item_key Cart item key or Cart item itself
 * @param array $new_data
 * @return int
 */
function bookacti_wc_update_cart_item_bookings( $cart_item_key, $new_data ) {
	global $woocommerce;
	$item = is_string( $cart_item_key ) ? $woocommerce->cart->get_cart_item( $cart_item_key ) : ( is_array( $cart_item_key ) ? $cart_item_key : array() );
	if( ! $item ) { return 0; }
	
	// Get expiration data
	$cart_expiration_date		= bookacti_wc_get_cart_expiration_date();
	$is_cart_expired			= strtotime( $cart_expiration_date ) <= time();
	$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	$reset_timeout_on_change	= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
	$is_expiration_active		= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	$timeout					= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
	$new_expiration_date		= $is_expiration_active && ( $reset_timeout_on_change || $is_cart_expired ) ? date( 'Y-m-d H:i:s', strtotime( '+' . $timeout . ' minutes' ) ) : '';

	// Update each booking
	$updated = 0;
	if( ! empty( $item[ '_bookacti_options' ][ 'bookings' ] ) ) {
		$cart_item_bookings = bookacti_maybe_decode_json( $item[ '_bookacti_options' ][ 'bookings' ], true );
		foreach( $cart_item_bookings as $cart_item_booking ) {
			$is_updated = false;
			if( $cart_item_booking[ 'type' ] === 'single' ) {
				$sanitized_data = bookacti_sanitize_booking_data( array_merge( array( 'id' => $cart_item_booking[ 'id' ], 'expiration_date' => $new_expiration_date ), $new_data ) );
				$is_updated = bookacti_update_booking( $sanitized_data );
			}
			else if( $cart_item_booking[ 'type' ] === 'group' ) {
				$sanitized_data = bookacti_sanitize_booking_group_data( array_merge( array( 'id' => $cart_item_booking[ 'id' ], 'expiration_date' => $new_expiration_date ), $new_data ) );
				$is_updated = bookacti_update_booking_group_bookings( $sanitized_data );
				bookacti_update_booking_group( $sanitized_data );
			}
			if( $is_updated ) { 
				do_action( 'bookacti_wc_cart_item_booking_updated', $cart_item_booking, $sanitized_data, $item, $new_data );
				++$updated;
			}
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
 * @global woocommerce $woocommerce
 * @param string|array $cart_item_key Cart item key or Cart item itself
 * @param string $new_status
 * @param string $new_expiration_date
 * @return int
 */
function bookacti_wc_update_cart_item_bookings_status( $cart_item_key, $new_status, $new_expiration_date = '' ) {
	$new_data = array( 
		'status' => $new_status,
		'active' => in_array( $new_status, bookacti_get_active_booking_states(), true ) ? 1 : 0
	);
	if( $new_expiration_date ) { $new_data[ 'expiration_date' ] = $new_expiration_date; }
	$updated = bookacti_wc_update_cart_item_bookings( $cart_item_key, $new_data );
	return $updated;
}




// CART EXPIRATION

/**
 * Check if the booking has expired
 * @version 1.9.0
 * @param int|object $booking
 * @return boolean
 */
function bookacti_is_expired_booking( $booking ) {
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	
	if( ! $booking ) { return true; }
	if( ! $booking->expiration_date || ! $booking->active ) { return true; }
	
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
		$timeout = '<div class="bookacti-cart-item-state bookacti-cart-item-state-pending">' . esc_html__( 'Pending payment', 'booking-activities' ) . '</div>';
	}

	return $timeout;
}


/**
 * Get formatted remaining time before expiration
 * @since 1.2.0
 * @version 1.9.0
 * @param string $expiration_date 
 * @param int $precision 
 * @return string
 */
function bookacti_get_formatted_time_before_expiration( $expiration_date, $precision = 3 ) {
	$seconds = round( abs( strtotime( $expiration_date ) - time() ) );
	$remaining_time = bookacti_format_delay( $seconds, $precision );
	return apply_filters( 'bookacti_formatted_time_before_expiration', $remaining_time, $expiration_date, $precision );
}


/**
 * Get product price as is it should be displayed in cart (with or without tax according to settings)
 * @since 1.9.0
 * @global woocommerce $woocommerce
 * @param WC_Product $product
 * @param float $price
 * @param int $qty
 * @return string
 */
function bookacti_wc_get_displayed_product_price( $product, $price = '', $qty = 1 ) {
	global $woocommerce;
	
	if( $product->is_taxable() ) {
		if( $woocommerce->cart->display_prices_including_tax() ) {
			$price_inc_tax		= wc_get_price_including_tax( $product, array( 'price' => $price, 'qty' => $qty ) );
			$formatted_price	= wc_price( $price_inc_tax );
			if( ! wc_prices_include_tax() && $woocommerce->cart->get_subtotal_tax() > 0 ) {
				$formatted_price .= ' <small class="tax_label">' . $woocommerce->countries->inc_tax_or_vat() . '</small>';
			}
		} else {
			$price_exc_tax		= wc_get_price_excluding_tax( $product, array( 'price' => $price, 'qty' => $qty ) );
			$formatted_price	= wc_price( $price_exc_tax );
			if( wc_prices_include_tax() && $woocommerce->cart->get_subtotal_tax() > 0 ) {
				$formatted_price .= ' <small class="tax_label">' . $woocommerce->countries->ex_tax_or_vat() . '</small>';
			}
		}
	} else {
		$price = $price !== '' ? max( 0.0, (float) $price ) : $product->get_price();
		$formatted_price = wc_price( $price * $qty );
	}
	
	return $formatted_price;
}




// CART AND ORDER

/**
 * Build a user-friendly events list based on item bookings
 * @since 1.9.0
 * @param array $item_bookings
 * @param boolean $show_quantity
 * @param string $locale Optional. Default to site locale.
 * @return string
 */
function bookacti_wc_get_item_bookings_events_list_html( $item_bookings, $show_quantity = false, $locale = 'site' ) {
	if( ! $item_bookings ) { return ''; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	$messages			= bookacti_get_messages( true );
	$datetime_format	= isset( $messages[ 'date_format_long' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'date_format_long' ][ 'value' ], $locale ) : '';
	$time_format		= isset( $messages[ 'time_format' ][ 'value' ] )		? apply_filters( 'bookacti_translate_text', $messages[ 'time_format' ][ 'value' ], $locale ) : '';
	$date_time_separator= isset( $messages[ 'date_time_separator' ][ 'value' ] )? apply_filters( 'bookacti_translate_text', $messages[ 'date_time_separator' ][ 'value' ], $locale ) : '';
	$dates_separator	= isset( $messages[ 'dates_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'dates_separator' ][ 'value' ], $locale ) : '';
	$quantity_separator = isset( $messages[ 'quantity_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'quantity_separator' ][ 'value' ], $locale ) : '';
	
	$list = '';
	foreach( $item_bookings as $item_booking ) {
		if( $item_booking[ 'type' ] === 'group' ) {
			/* translators: %d = the booking group ID */
			$group_title = ! empty( $item_booking[ 'bookings' ][ 0 ]->group_title ) ? apply_filters( 'bookacti_translate_text', $item_booking[ 'bookings' ][ 0 ]->group_title, $locale ) : sprintf( esc_html__( 'Booking group #%d', 'booking-activities' ), $item_booking[ 'id' ] );
			$list .= '<li><span class="bookacti-booking-group-of-events-title">' . $group_title . '</span><ul class="bookacti-booking-grouped-events-list">';
		}
		
		foreach( $item_booking[ 'bookings' ] as $booking ) {
			/* translators: %d = the booking ID */
			$title = ! empty( $booking->event_title ) ? apply_filters( 'bookacti_translate_text', $booking->event_title, $locale ) : sprintf( esc_html__( 'Booking #%d', 'booking-activities' ), $booking->id );
			$dates = bookacti_get_formatted_event_dates( $booking->event_start, $booking->event_end, true, $locale );
			
			if( ! $title && ! $dates ) { continue; }
			
			$list .= '<li>';

			if( $title ) {
				$list .= '<span class="bookacti-booking-event-title" >' . $title . '</span>';
				if( $dates ) {
					$list .= '<span class="bookacti-booking-event-title-separator" >' . ' - ' . '</span>';
				}
			}
			if( $dates ) {
				$list .= $dates;
			}

			if( $show_quantity ) {
				$list .= '<span class="bookacti-booking-event-quantity-separator" >' . $quantity_separator . '</span>';
				$list .= '<span class="bookacti-booking-event-quantity" >' . $booking->quantity . '</span>';
			}

			$list .= '</li>';
		}
		
		if( $item_booking[ 'type' ] === 'group' ) { $list .= '</ul></li>'; }
	}
	
	// Wrap the list only if it is not empty
	if( $list ) {
		$list = '<ul class="bookacti-booking-events-list bookacti-custom-scrollbar" style="clear:both;" >' . $list . '</ul>';
	}
	
	return apply_filters( 'bookacti_wc_item_bookings_events_list_html', $list, $item_bookings, $show_quantity, $locale );
}


/**
 * Get array of displayed attributes per booking
 * @since 1.9.0
 * @version 1.11.0
 * @global boolean $bookacti_is_email
 * @param array $item_bookings
 * @return array
 */
function bookacti_wc_get_item_bookings_attributes( $item_bookings ) {
	$bookings_attributes = array();
	if( ! $item_bookings ) { return $bookings_attributes; }
	
	foreach( $item_bookings as $i => $item_booking ) {
		// Handle item_booking_id arrays too
		$dummy_booking = array();
		if( ! isset( $item_booking[ 'bookings' ] ) ) {
			$dummy_booking = $item_booking[ 'type' ] === 'group' ? array( 'group_id' => $item_booking[ 'id' ] ) : array( 'id' => $item_booking[ 'id' ] );
			$dummy_booking_obj = (object) bookacti_sanitize_booking_data( $dummy_booking );
			$item_booking[ 'bookings' ] = array( $dummy_booking_obj );
			$item_bookings[ $i ][ 'bookings' ] = $item_booking[ 'bookings' ];
		}
		
		$booking_attributes = array();
		
		// Booking ID
		$booking_attributes[ 'id' ] = array( 
			'label' => $item_booking[ 'type' ] === 'group' ? esc_html__( 'Booking group ID', 'booking-activities' ) : esc_html__( 'Booking ID', 'booking-activities' ), 
			'value' => $item_booking[ 'id' ],
			'type' => $item_booking[ 'type' ]
		);
		
		// Booking status
		$status = $item_booking[ 'type' ] === 'group' ? ( ! empty( $item_booking[ 'bookings' ][ 0 ]->group_state ) ? $item_booking[ 'bookings' ][ 0 ]->group_state : '' ) : $item_booking[ 'bookings' ][ 0 ]->state;
		if( $status ) {
			$booking_attributes[ 'status' ] = array( 
				'label' => esc_html__( 'Status', 'booking-activities' ), 
				'value' => bookacti_format_booking_state( $status )
			);
		}
		
		// Booking events
		if( ! $dummy_booking ) {
			$booking_attributes[ 'events' ] = array( 
				'label' => esc_html( _n( 'Event', 'Events', count( $item_booking[ 'bookings' ] ), 'booking-activities' ) ), 
				'value' => bookacti_wc_get_item_bookings_events_list_html( array( $item_booking ), true ),
				'fullwidth' => 1
			);
		}
		
		// Refund data
		if( ! empty( $item_booking[ 'bookings' ][ 0 ]->refunds ) ) {
			$refunds_formatted = bookacti_format_booking_refunds( $item_booking[ 'bookings' ][ 0 ]->refunds );
			$refunds_html = bookacti_get_booking_refunds_html( $refunds_formatted );
			if( $refunds_html ) {
				$booking_attributes[ 'refunds' ] = array( 
					'label' => esc_html( _n( 'Refund', 'Refunds', count( $refunds_formatted ), 'booking-activities' ) ), 
					'value' => $refunds_html,
					'fullwidth' => 1
				);
			}
		}
		
		// Allow plugins to add more item booking attributes to be displayed (before the booking actions)
		$booking_attributes = apply_filters( 'bookacti_wc_item_booking_attributes', $booking_attributes, $item_booking );
		
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
					'fullwidth' => 1
				);
			}
		}
		
		// Booking actions
		// Don't display booking actions in emails, on the backend, and on payment page
		global $bookacti_is_email;
		if( ! $bookacti_is_email && ! $is_order_edit_page && empty( $_REQUEST[ 'pay_for_order' ] ) ) {
			$actions_html_array = $item_booking[ 'type' ] === 'group' ? bookacti_get_booking_group_actions_html( $item_booking[ 'bookings' ], 'front', array(), true, true ) : ( $item_booking[ 'type' ] === 'single' ? bookacti_get_booking_actions_html( $item_booking[ 'bookings' ][ 0 ], 'front', array(), true, true ) : '' );
			if( $actions_html_array ) {
				$booking_attributes[ 'actions' ] = array( 
					'label' => esc_html( _n( 'Action', 'Actions', count( $actions_html_array ), 'booking-activities' ) ),
					'value' => implode( ' | ', $actions_html_array ),
					'fullwidth' => 1
				);
			}
		}
		
		if( $booking_attributes ) {
			$index = ( $item_booking[ 'type' ] === 'group' ? 'G' : 'B' ) . $item_booking[ 'id' ];
			$bookings_attributes[ $index ] = $booking_attributes;
		}
	}
	
	return apply_filters( 'bookacti_wc_item_bookings_attributes', $bookings_attributes, $item_bookings );
}


/**
 * Get array of displayed attributes per item per booking
 * @since 1.9.0
 * @version 1.11.0
 * @param array $item_bookings
 * @param string $locale Optional. Default to site locale.
 * @return array
 */
function bookacti_wc_get_item_bookings_attributes_html( $item_bookings, $locale = 'site' ) {
	$html = '';
	if( ! $item_bookings ) { return $html; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	$switched = bookacti_switch_locale( $locale );
	
	$bookings_attributes = bookacti_wc_get_item_bookings_attributes( $item_bookings );
	if( ! $bookings_attributes ) { if( $switched ) { bookacti_restore_locale(); } return $html; }
	
	$i = 0;
	$last_i = count( $bookings_attributes );
	$text_dir = is_rtl() ? 'right' : 'left';
	$margin_dir = is_rtl() ? 'left' : 'right';
	
	$html .= '<div class="bookacti-wc-order-item-bookings-attributes" style="clear: both; border: none; padding: 0; margin-' . $text_dir . ': 15px; text-align: ' . $text_dir . ';">';
	foreach( $bookings_attributes as $booking_attributes ) {
		$i++;
		$container_margin = $i !== $last_i ? 'margin-bottom:25px;' : '';
		$attr_booking_id = ! empty( $booking_attributes[ 'id' ] ) ? ( $booking_attributes[ 'id' ][ 'type' ] === 'group' ? 'data-booking-group-id="' . $booking_attributes[ 'id' ][ 'value' ] . '"' : 'data-booking-id="' . $booking_attributes[ 'id' ][ 'value' ] . '"' ) : '';
		$html .= '<div class="bookacti-wc-order-item-booking-attributes bookacti-booking-row" ' . $attr_booking_id . ' style="' . $container_margin . '">';
		foreach( $booking_attributes as $booking_attribute_id => $booking_attribute ) {
			if( $booking_attribute[ 'label' ] === '' && $booking_attribute[ 'value' ] === '' ) { continue; }
			$fullwidth_class = ! empty( $booking_attribute[ 'fullwidth' ] ) ? 'bookacti-fullwidth-label' : '';
			$fullwidth_style = ! empty( $booking_attribute[ 'fullwidth' ] ) ? '' : 'display: inline-block; vertical-align:middle;';
			$label_style = $booking_attribute[ 'label' ] === '' ? 'display: none;' : $fullwidth_style . ' margin-' . $margin_dir . ': .25em;';
			$html .= '<div class="bookacti-wc-order-item-booking-attribute" data-attribute="' . $booking_attribute_id . '">'
						. '<div class="bookacti-wc-order-item-booking-attribute-label ' . $fullwidth_class . '" style="' . $label_style . '">'
							. '<strong>' . $booking_attribute[ 'label' ] . ':</strong>'
						. '</div>'
						. '<div class="bookacti-wc-order-item-booking-attribute-value" style="' . $fullwidth_style . '">' 
							. $booking_attribute[ 'value' ] 
						. '</div>'
					. '</div>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';
	
	$html = apply_filters( 'bookacti_wc_item_bookings_attributes_html', $html, $bookings_attributes, $item_bookings, $locale );
	
	if( $switched ) { bookacti_restore_locale(); }
	
	return $html;
}


/**
 * Get item booking array of possible actions
 * @since 1.9.0
 * @param array $item_booking
 * @return array
 */
function bookacti_wc_get_item_booking_actions( $item_booking ) {
	if( ! isset( $item_booking[ 'id' ] ) || ! isset( $item_booking[ 'type' ] ) ) { return array(); }
	
	// Handle item_booking_id arrays too
	if( ! isset( $item_booking[ 'bookings' ] ) ) {
		$dummy_booking = $item_booking[ 'type' ] === 'group' ? array( 'group_id' => $item_booking[ 'id' ] ) : array( 'id' => $item_booking[ 'id' ] );
		$dummy_booking_obj = (object) bookacti_sanitize_booking_data( $dummy_booking );
		$item_booking[ 'bookings' ] = array( $dummy_booking_obj );
		$item_bookings[ $i ][ 'bookings' ] = $item_booking[ 'bookings' ];
	}
	
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
			'class'			=> 'bookacti-wc-order-item-edit-booking-button',
			'label'			=> esc_html__( 'Edit the booking', 'booking-activities' ),
			'description'	=> esc_html__( 'Go to the edit page.', 'booking-activities' ),
			'link'			=> $link_to_booking,
		)
	);
	
	return apply_filters( 'bookacti_wc_item_booking_actions', $actions, $item_booking );
}


/**
 * Get item booking possible actions as HTML buttons
 * @since 1.9.0
 * @param array $item_booking
 * @param boolean $return_array Whether to return an array of buttons, or the concatenated buttons HTML
 * @return string
 */
function bookacti_wc_get_item_booking_actions_html( $item_booking, $return_array = false ) {
	// Get the array of possible actions
	$actions = bookacti_wc_get_item_booking_actions( $item_booking );
	
	$actions_html = '';
	$actions_html_array	= array();
	$booking_action_class = $item_booking[ 'type' ] === 'group' ? ' bookacti-booking-group-action' : ' bookacti-booking-action';
	
	foreach( $actions as $action_id => $action ) {
			$action_html = '<a '
							. 'href="' . esc_url( $action[ 'link' ] ) . '" '
							. 'id="bookacti-booking-action-' . esc_attr( $action_id ) . '-' . $item_booking[ 'id' ] . '" '
							. 'class="button ' . $action_id . ' ' . esc_attr( $action[ 'class' ] ) . $booking_action_class . ' tips" '
							. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
							. 'data-booking-type="' . $item_booking[ 'type' ] . '" '
							. 'data-booking-id="' . $item_booking[ 'id' ] . '" >'
							.	esc_html( $action[ 'label' ] )
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

	return $order_item_bookings_ids;
}


/**
 * Get in order bookings per order item
 * @since 1.9.0
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
			if( $order_item_booking_id[ 'type' ] === 'single' )		{ $order_item_ids_by_booking_id[ $order_item_booking_id[ 'id' ] ] = $order_item_id; }
			else if( $order_item_booking_id[ 'type' ] === 'group' )	{ $order_item_ids_by_booking_group_id[ $order_item_booking_id[ 'id' ] ] = $order_item_id; }
		}
	}
	
	$bookings = array();
	if( $order_item_ids_by_booking_id || $order_item_ids_by_booking_group_id ) {
		$filters = apply_filters( 'bookacti_wc_cart_items_bookings_filters', bookacti_format_booking_filters( array_merge( $filters, array( 
			'in__booking_id' => array_keys( $order_item_ids_by_booking_id ), 
			'in__booking_group_id' => array_keys( $order_item_ids_by_booking_group_id ), 
			'booking_group_id_operator' => 'OR' ) ) ) );
		$bookings = bookacti_get_bookings( $filters );
	}
	if( ! $bookings ) { return array(); }
	
	$order_items_bookings = array();
	
	foreach( $bookings as $booking ) {
		$booking_id = intval( $booking->id );
		$group_id = intval( $booking->group_id );

		$order_item_id = 0;
		$booking_type = '';
		if( $group_id && ! empty( $order_item_ids_by_booking_group_id[ $group_id ] ) ) { 
			$order_item_id = $order_item_ids_by_booking_group_id[ $group_id ];
			$booking_type = 'group';
		} else if( ! $group_id && ! empty( $order_item_ids_by_booking_id[ $booking_id ] ) ) { 
			$order_item_id = $order_item_ids_by_booking_id[ $booking_id ];
			$booking_type = 'single';
		}

		if( $order_item_id ) {
			if( ! isset( $order_items_bookings[ $order_item_id ] ) ) { $order_items_bookings[ $order_item_id ] = array(); }
			if( $booking_type === 'single' ) { $order_items_bookings[ $order_item_id ][] = array( 'id' => $booking_id, 'type' => 'single', 'bookings' => array( $booking ) ); }
			else if( $booking_type === 'group' ) { 
				$group_exists = false;
				foreach( $order_items_bookings[ $order_item_id ] as $i => $order_item_booking ) {
					if( $order_item_booking[ 'type' ] === 'group' && $order_item_booking[ 'id' ] === $group_id ) {
						$group_exists = true;
						$order_items_bookings[ $order_item_id ][ $i ][ 'bookings' ][] = $booking;
					}
				}
				if( ! $group_exists ) {
					$order_items_bookings[ $order_item_id ][] = array( 'id' => $group_id, 'type' => 'group', 'bookings' => array( $booking ) );
				}
			}
		}
	}
	
	return $order_items_bookings;
}


/**
 * Check if we can update the quantity of an order item bookings
 * @since 1.9.0
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
	return $response;
}


/**
 * Update bookings attached to all order items
 * @since 1.9.0
 * @param int|WC_Order $order_id
 * @param array $new_data
 * @param array $where
 * @return array
 */
function bookacti_wc_update_order_items_bookings( $order_id, $new_data, $where = array() ) {
	$updated = array( 'updated' => 0, 'booking_ids' => array(), 'booking_group_ids' => array() );
	
	$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : ( is_a( $order_id, 'WC_Order' ) ? $order_id : null );
	if( ! $order || ! $new_data ) { return $updated; }
	
	// Sanitize where clauses
	$in__order_item_id		= ! empty( $where[ 'in__order_item_id' ] ) ? array_map( 'intval', $where[ 'in__order_item_id' ] ) : array();
	$in__booking_id			= ! empty( $where[ 'in__booking_id' ] ) ? array_map( 'intval', $where[ 'in__booking_id' ] ) : array();
	$in__booking_group_id	= ! empty( $where[ 'in__booking_group_id' ] ) ? array_map( 'intval', $where[ 'in__booking_group_id' ] ) : array();
	$in__status				= ! empty( $where[ 'in__status' ] ) ? $where[ 'in__status' ] : array();
	
	// Get bookings
	$order_items_bookings = bookacti_wc_get_order_items_bookings( $order_id, array( 'in__order_item_id' => $in__order_item_id ) );
	if( ! $order_items_bookings ) { return $updated; }
	
	foreach( $order_items_bookings as $order_item_id => $order_item_bookings ) {
		if( $in__order_item_id && ! in_array( $order_item_id, $in__order_item_id, true ) ) { continue; }
		if( ! $order_item_bookings ) { continue; }
		
		foreach( $order_item_bookings as $order_item_booking ) {
			$is_updated = false;
			if( $order_item_booking[ 'type' ] === 'single' ) {
				$status = $order_item_booking[ 'bookings' ][ 0 ]->state;
				if( $in__status && ! in_array( $status, $in__status, true ) ) { continue; }
				if( $in__booking_id && ! in_array( $order_item_booking[ 'id' ], $in__booking_id, true ) ) { continue; }
				
				$sanitized_data = bookacti_sanitize_booking_data( array_merge( array( 'id' => $order_item_booking[ 'id' ] ), $new_data ) );
				$is_updated = bookacti_update_booking( $sanitized_data );
				if( $is_updated ) { $updated[ 'booking_ids' ][] = $order_item_booking[ 'id' ]; }
			}
			else if( $order_item_booking[ 'type' ] === 'group' ) {
				$status = $order_item_booking[ 'bookings' ][ 0 ]->group_state;
				if( $in__status && ! in_array( $status, $in__status, true ) ) { continue; }
				if( $in__booking_group_id && ! in_array( $order_item_booking[ 'id' ], $in__booking_group_id, true ) ) { continue; }
				
				$sanitized_data = bookacti_sanitize_booking_group_data( array_merge( array( 'id' => $order_item_booking[ 'id' ] ), $new_data ) );
				$is_updated = bookacti_update_booking_group_bookings( $sanitized_data );
				bookacti_update_booking_group( $sanitized_data );
				if( $is_updated ) { $updated[ 'booking_group_ids' ][] = $order_item_booking[ 'id' ]; }
			}
			if( $is_updated ) { 
				do_action( 'bookacti_wc_order_item_booking_updated', $order_item_booking, $sanitized_data, $order, $new_data, $where );
				++$updated[ 'updated' ];
			}
		}
	}
	
	return $updated;
}


/**
 * Update the order status according to the bookings status bound to its items
 * @since 1.9.0 (was bookacti_change_order_state_based_on_its_bookings_state)
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

	// Get items booking states and
	// Determine if the order is only composed of activities
	$states = array();
	$only_activities = true;
	$only_virtual_activities = true;
	foreach( $items as $item_id => $item ) {
		// Is activity
		if( empty( $items_bookings[ $item_id ] ) ) { $only_activities = false; break; }

		// Is virtual
		$product = method_exists( $item, 'get_product' ) ? $item->get_product() : null;
		if( $product && ! $product->is_virtual() ) { $only_virtual_activities = false; }
		
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$states[] = $item_booking[ 'type' ] === 'group' && ! empty( $booking->group_state ) ? $booking->group_state : $booking->state;
			}
		}
	}
	
	if( ! $only_activities || ! $states || in_array( 'in_cart', $states, true ) ) { return; }

	$states = array_unique( $states );
	$order_status = $order->get_status();
	$new_order_status = $order_status;
	$completed_booking_states = array( 'delivered', 'booked' );
	$cancelled_booking_states = array( 'cancelled', 'refund_requested', 'expired', 'removed' );
	$refunded_booking_states = array( 'refunded' );
	$are_completed = ! array_diff( $states, $completed_booking_states );
	$are_cancelled = ! array_diff( $states, $cancelled_booking_states );
	$are_refunded = ! array_diff( $states, $refunded_booking_states );

	if( in_array( $order_status, array( 'pending' ), true ) && in_array( 'pending', $states, true ) ) {
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
 * Get order items holding one of the desired booking or booking group ID
 * @since 1.9.0
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
		if( ! $order_ids ) { return $order_items; }

		// Get WC orders
		$orders = wc_get_orders( array( 'post__in' => $order_ids ) );
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
		'email'		=> $order->get_billing_email( 'edit' ),
		'first_name'=> $order->get_billing_first_name( 'edit' ),
		'last_name'	=> $order->get_billing_last_name( 'edit' ),
		'phone'		=> $order->get_billing_phone( 'edit' )
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
				$item[ 'id' ]		= $order_item_id;
				$item[ 'order_id' ]	= $order_id;
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
				$item[ 'id' ]		= $order_item_id;
				$item[ 'order_id' ]	= $order_id;
			}
		}
	}

	return $item;
}


/**
 * Get booking actions according to its order status
 * @since 1.6.0 (replace bookacti_display_actions_buttons_on_items)
 * @param array $booking_actions
 * @param int $order_id
 * @return array
 */
function bookacti_wc_booking_actions_per_order_id( $booking_actions, $order_id ) {

	if( ! $order_id || ! is_numeric( $order_id ) ) {
		return $booking_actions;
	}

	$order = wc_get_order( $order_id );

	// Check view order
	if( empty( $order ) ) {
		if( isset( $booking_actions['view-order'] ) ) { unset( $booking_actions['view-order'] ); } 
		return $booking_actions;
	} else {
		if( isset( $booking_actions['view-order'] ) ) { $booking_actions[ 'view-order' ][ 'link' ] = get_edit_post_link( $order_id ); }
	}

	// Check cancel / reschedule
	if( ! current_user_can( 'bookacti_edit_bookings' ) && $order->get_status() === 'pending' )	{ 
		if( isset( $booking_actions['cancel'] ) )		{ unset( $booking_actions['cancel'] ); } 
		if( isset( $booking_actions['reschedule'] ) )	{ unset( $booking_actions['reschedule'] ); }
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
 * @version 1.8.0
 * @param array $raw_args
 * @return string
 */
function bookacti_display_product_selectbox( $raw_args = array() ) {
	$defaults = array(
		'field_name'		=> 'product_id',
		'selected'			=> '',
		'id'				=> '',
		'class'				=> '',
		'allow_tags'		=> 0,
		'allow_clear'		=> 1,
		'ajax'				=> 1,
		'select2'			=> 1, 
		'echo'				=> 1
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
		data-placeholder='<?php esc_html_e( 'Search for a product', 'booking-activities' ); ?>'
		data-type='products' >
		<option><!-- Used for the placeholder --></option>
	<?php
		do_action( 'bookacti_add_product_selectbox_options', $args, $products_titles );

		$is_selected = false;
		if( $products_titles ) {
			foreach( $products_titles as $product_id => $product ) {
				// Display simple products options
				if( empty( $product[ 'variations' ] ) ) {
					$_selected = selected( $product_id, $args[ 'selected' ] );
					if( $_selected ) { $is_selected = true; }
					?><option class='bookacti-wc-product-option' value='<?php echo esc_attr( $product_id ); ?>' <?php echo $_selected; ?>><?php echo esc_html( apply_filters( 'bookacti_translate_text', $product[ 'title' ] ) ); ?></option><?php

				// Display variations options
				} else {
				?>
					<optgroup class='bookacti-wc-variable-product-option-group' label='<?php echo esc_attr( apply_filters( 'bookacti_translate_text', $product[ 'title' ] ) ); ?>'>
					<?php
						foreach( $product[ 'variations' ] as $variation_id => $variation ) {
							$_selected = selected( $variation_id, $args[ 'selected' ] );
							if( $_selected ) { $is_selected = true; }
							$variation_title = esc_html( apply_filters( 'bookacti_translate_text', $variation[ 'title' ] ) );
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
 * @version 1.7.8
 * @param WC_Product|int $product
 * @return boolean
 */
function bookacti_product_is_activity( $product ) {
	// Get product or variation from ID
	if( ! is_object( $product ) ) {
		$product_id	= intval( $product );
		$product	= wc_get_product( $product_id );
		if( ! $product ) { return false; }
	}

	$is_activity = false;

	if( $product->is_type( 'simple' ) ) {
		$is_activity = get_post_meta( $product->get_id(), '_bookacti_is_activity', true ) === 'yes';
	} 

	else if( $product->is_type( 'variation' ) ) {
		$is_activity = get_post_meta( $product->get_id(), 'bookacti_variable_is_activity', true ) === 'yes';
	}

	else if( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();
		foreach( $variations as $variation ) {
			if( empty( $variation[ 'bookacti_is_activity' ] ) ) { continue; }
			$is_activity = $variation[ 'bookacti_is_activity' ];
			break;
		}
	}

	return apply_filters( 'bookacti_product_is_activity', $is_activity ? true : false, $product );
}


/**
 * Find matching product variation
 * @since 1.5.0
 * @version 1.8.0
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
			if( $key !== apply_filters( 'bookacti_translate_text', $product_attribute_key ) 
			&&  $key !== $product_attribute_key ) { continue; }

			$options = $product_attribute->get_options();
			// If it failed, try to retrieve it from database (doesn't work with custom attributes)
			if( ! $options ) { $options = wc_get_product_terms( $product->get_id(), $product_attribute_key, array( 'fields' => 'slugs' ) ); }

			if( is_array( $options ) ) {
				foreach( $options as $option_value ) {
					if( $value !== apply_filters( 'bookacti_translate_text', $option_value ) 
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
 * @param int $product_id
 * @param boolean $is_variation
 * @return int
 */
function bookacti_get_product_form_id( $product_id, $is_variation = 'check' ) {
	// Check if the product is simple or a variation
	if( $is_variation === 'check' ) {
		$is_variation = false;
		$product = wc_get_product( $product_id );
		if( $product ) {
			$is_variation = $product->get_type() === 'variation';
		}
	}

	$form_id = $is_variation ? get_post_meta( $product_id, 'bookacti_variable_form', true ) : get_post_meta( $product_id, '_bookacti_form', true );
	
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
			'id'			=> 'coupon',
			'label'			=> esc_html__( 'Coupon', 'booking-activities' ),
			'description'	=> esc_html__( 'Create a coupon worth the price paid. The coupon can be used once for any orders at any time. ', 'booking-activities' )
		),
		'auto' => array(
			'id'			=> 'auto',
			'label'			=> esc_html__( 'Auto refund', 'booking-activities' ),
			'description'	=> esc_html__( 'Refund automatically via the gateway used for payment.', 'booking-activities' )
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
 * @version 1.9.0
 * @param WC_Order_Refund $refund
 */
function bookacti_update_order_bookings_on_items_refund( $refund ) {
	$refund_items = $refund->get_items();
	if( ! $refund_items ) { return; }
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	$price_decimals = wc_get_price_decimals();
	
	$refund_id		= $refund->get_id();
	$refund_action	= $refund->get_refunded_payment() ? 'auto' : 'manual';
	$refund_date	= $refund->get_date_created() ? $refund->get_date_created() : new DateTime();
	if( is_a( $refund_date, 'DateTime' ) ) {
		$refund_date->setTimezone( $utc_timezone_obj );
		$refund_date = $refund_date->format( 'Y-m-d H:i:s' );
	}
	
	$order_id		= $refund->get_parent_id();
	$order			= wc_get_order( $refund->get_parent_id() );
	$order_items	= $order->get_items();
	
	// Get the bookings attached to refunded items
	$refunded_items = array();
	$items_refunded_qty = array();
	$items_refunded_amount = array();
	foreach( $refund_items as $refund_item ) {
		$item_id = intval( $refund_item->get_meta( '_refunded_item_id', true ) );
		if( ! isset( $order_items[ $item_id ] ) ) { continue; }
		
		$refunded_qty = abs( intval( $refund_item->get_quantity() ) );
		$refunded_amount = abs( round( $refund_item->get_total() + $refund_item->get_total_tax(), $price_decimals ) );
		if( ! $refunded_qty || ! $refunded_amount ) { continue; }
		
		$refunded_items[ $item_id ] = $order_items[ $item_id ];
		$items_refunded_qty[ $item_id ]	= $refunded_qty;
		$items_refunded_amount[ $item_id ] = $refunded_amount;
	}
	
	$items_bookings = bookacti_wc_get_order_items_bookings( $refunded_items );
	
	// Update each booking qty or status
	foreach( $items_bookings as $item_id => $item_bookings ) {
		foreach( $item_bookings as $item_booking ) {
			// Prepare the refund record
			$refund_record = apply_filters( 'bookacti_wc_booking_refund_data', array( 'date' => $refund_date, 'quantity' => isset( $items_refunded_qty[ $item_id ] ) ? $items_refunded_qty[ $item_id ] : 0, 'amount' => isset( $items_refunded_amount[ $item_id ] ) ? wc_format_decimal( $items_refunded_amount[ $item_id ] ) : 0, 'method' => $refund_action ), $refund, $item_booking );
			
			$group_quantity = 0;
			$new_group_quantity = 0;
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$quantity = intval( $booking->quantity );
				$new_quantity = $quantity - $items_refunded_qty[ $item_id ];
				if( $item_booking[ 'type' ] === 'group' && $quantity > $group_quantity ){ $group_quantity = $quantity; }
				if( $item_booking[ 'type' ] === 'group' && $new_quantity > $new_group_quantity ){ $new_group_quantity = $new_quantity; }
				
				$new_data = $new_quantity > 0 ? array( 'id' => $booking->id, 'quantity' => $new_quantity ) : array( 'id' => $booking->id, 'status' => 'refunded', 'active' => 0 );
				
				$booking_data = bookacti_sanitize_booking_data( $new_data );
				$updated = bookacti_update_booking( $booking_data );
				
				if( $updated && $item_booking[ 'type' ] === 'single' ) {
					// Update refunds records array bound to the booking
					$refunds = bookacti_get_metadata( 'booking', $booking->id, 'refunds', true );
					$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $booking->id ) : array();
					$refunds[ $refund_id ] = $refund_record;
					bookacti_update_metadata( 'booking', $booking->id, array( 'refunds' => $refunds ) );
					
					// Trigger booking status change
					if( $booking->state !== $booking_data[ 'status' ] ) {
						do_action( 'bookacti_booking_state_changed', $booking, $booking_data[ 'status' ], array( 'refund_action' => $refund_action ) );
					}
				}
			}
			
			if( $item_booking[ 'type' ] === 'group' ) {
				// Update refunds records array bound to the booking group
				$refunds = bookacti_get_metadata( 'booking_group', $item_booking[ 'id' ], 'refunds', true );
				$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $item_booking[ 'id' ], 'group' ) : array();
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );
				
				if( $new_group_quantity <= 0 ) {
					$booking_group_data = bookacti_sanitize_booking_group_data( array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 ) );
					$updated = bookacti_update_booking_group( $booking_group_data );

					// Trigger booking group status change
					$group_status = isset( $item_booking[ 'bookings' ][ 0 ]->group_state ) ? $item_booking[ 'bookings' ][ 0 ]->group_state : $item_booking[ 'bookings' ][ 0 ]->state;
					if( $updated && $group_status !== $booking_group_data[ 'status' ] ) {
						do_action( 'bookacti_booking_group_state_changed', $item_booking[ 'id' ], $item_booking[ 'bookings' ], $booking_group_data[ 'status' ], array( 'refund_action' => $refund_action ) );
					}
				}
			}
		}
	}
}


/**
 * Update order bookings if a total refund is perfomed (refund of the whole order)
 * @since 1.2.0 (was part of bookacti_update_booking_when_order_item_is_refunded before)
 * @version 1.9.0
 * @param WC_Order_Refund $refund
 */
function bookacti_update_order_bookings_on_order_refund( $refund ) {
	// Double check that the refund is total
	$order_id			= $refund->get_parent_id();
	$order				= wc_get_order( $order_id );
	$is_total_refund	= floatval( $order->get_total() ) == floatval( $order->get_total_refunded() );
	if( ! $is_total_refund ) { return; }
	
	$utc_timezone_obj = new DateTimeZone( 'UTC' );
	
	$refund_id		= $refund->get_id();
	$refund_action	= $refund->get_refunded_payment() ? 'auto' : 'manual';
	$refund_date	= $refund->get_date_created() ? $refund->get_date_created() : new DateTime();
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
		$refunded_qty = abs( $item->get_quantity() ) - abs( $order->get_qty_refunded_for_item( $item_id ) );
		$refunded_amount = bookacti_wc_get_item_remaining_refund_amount( $item );
		
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			// Do not treat bookings already marked as refunded
			$status = $item_booking[ 'type' ] === 'group' && ! empty( $item_booking[ 'bookings' ][ 0 ]->group_state ) ? $item_booking[ 'bookings' ][ 0 ]->group_state : $item_booking[ 'bookings' ][ 0 ]->state;
			if( $status === 'refunded' ) { continue; }
			
			// Prepare the refund record
			$refund_record = apply_filters( 'bookacti_wc_booking_refund_data', array( 'date' => $refund_date, 'quantity' => $refunded_qty, 'amount' => wc_format_decimal( $refunded_amount ), 'method' => $refund_action ), $refund, $item_booking );
			
			// Single booking
			if( $item_booking[ 'type' ] === 'single' ) {
				// Update booking state to 'refunded'
				$booking_data = bookacti_sanitize_booking_data( array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 ) );
				$updated = bookacti_update_booking( $booking_data );
				
				// Update refunds records array bound to the booking
				$refunds = bookacti_get_metadata( 'booking', $item_booking[ 'id' ], 'refunds', true );
				$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $item_booking[ 'id' ] ) : array();
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );

				if( $updated && $status !== $booking_data[ 'status' ] ) {
					do_action( 'bookacti_booking_state_changed', $item_booking[ 'bookings' ][ 0 ], 'refunded', array( 'refund_action' => $refund_action ) );
				}

			// Booking group
			} else if( $item_booking[ 'type' ] === 'group' ) {
				// Update bookings states to 'refunded'
				$booking_group_data = bookacti_sanitize_booking_group_data( array( 'id' => $item_booking[ 'id' ], 'status' => 'refunded', 'active' => 0 ) );
				$updated = bookacti_update_booking_group( $booking_group_data );
				bookacti_update_booking_group_bookings( $booking_group_data );
				
				// Update refunds records array bound to the booking
				$refunds = bookacti_get_metadata( 'booking_group', $item_booking[ 'id' ], 'refunds', true );
				$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $item_booking[ 'id' ], 'group' ) : array();
				$refunds[ $refund_id ] = $refund_record;
				bookacti_update_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' => $refunds ) );

				if( $updated && $status !== $booking_group_data[ 'status' ] ) {
					do_action( 'bookacti_booking_group_state_changed', $item_booking[ 'id' ], $item_booking[ 'bookings' ], 'refunded', array( 'refund_action' => $refund_action ) );
				}
			}
		}
	}
}


/**
 * Create a coupon to refund a booking
 * @version 1.9.0
 * @param array $bookings
 * @param string $booking_type Determine if the given id is a booking id or a booking group. Accepted values are 'single' or 'group'.
 * @param string $refund_message
 * @return array
 */
function bookacti_refund_booking_with_coupon( $bookings, $booking_type, $refund_message ) {
	// Include & load API classes
	if( ! class_exists( 'WC_API_Coupons' ) ) {
		WC()->api->includes();
		WC()->api->register_resources( new WC_API_Server( '/' ) );
	}

	// Get variables
	if( $booking_type === 'single' ) {
		$user_id	= $bookings[ 0 ]->user_id;
		$order_id	= $bookings[ 0 ]->order_id;
		$item		= bookacti_get_order_item_by_booking_id( $bookings[ 0 ] );
	} else if( $booking_type === 'group' ) {
		$user_id	= ! empty( $bookings[ 0 ]->group_user_id ) ? $bookings[ 0 ]->group_user_id : $bookings[ 0 ]->user_id;
		$order_id	= ! empty( $bookings[ 0 ]->group_order_id ) ? $bookings[ 0 ]->group_order_id : $bookings[ 0 ]->order_id;
		$item		= bookacti_get_order_item_by_booking_group_id( $bookings[ 0 ] );
	}
	
	$order = wc_get_order( $order_id );
	
	if( ! $item || ! $order_id ) { 
		return array( 
			'status'	=> 'failed', 
			'error'		=> 'no_order_item_found',
			'message'	=> esc_html__( 'The order item bound to the booking was not found.', 'booking-activities' )
		);
	}

	$user = is_numeric( $user_id ) ? get_user_by( 'id', $user_id ) : null;
	$user_billing_email = get_user_meta( $user_id, 'billing_email', true );

	// Write code description
	$refund_desc = esc_html__( 'Coupon created as a refund for:', 'booking-activities' );
	if( $user ) {
		$refund_desc .= PHP_EOL . esc_html__( 'User', 'booking-activities' ) . ' ' . $user->ID . ' (' . $user->user_login . ' / ' . $user->user_email . ')';
	}
	$refund_desc .= PHP_EOL . esc_html__( 'Order', 'booking-activities' ) . ' ' . $item->get_order_id();

	if( $booking_type === 'single' ) {
		$refund_desc .= PHP_EOL . esc_html__( 'Booking number', 'booking-activities' )	. ' ' . $bookings[ 0 ]->id;
	} else if( $booking_type === 'group' ) {
		$refund_desc .= PHP_EOL . esc_html__( 'Booking group number', 'booking-activities' )	. ' ' . $bookings[ 0 ]->group_id;
	}

	$refund_desc .= PHP_EOL . '     ' . $item->get_name();

	if( ! empty( $refund_message ) ) {
		$refund_desc .= PHP_EOL . PHP_EOL . esc_html__( 'User message:', 'booking-activities' ) . PHP_EOL . $refund_message;
	}

	// Sanitize
	// WP < 4.7 backward compatibility
	if( function_exists( 'sanitize_textarea_field' ) ) {
		$refund_desc = sanitize_textarea_field( stripslashes( $refund_desc ) );
	} else {
		$refund_desc = sanitize_text_field( stripslashes( $refund_desc ) );
	}

	// Coupon data
	$data = array();
	$data[ 'coupon' ] = array(
		'type'                         => 'fixed_cart',
		'code'                         => '',
		'amount'                       => bookacti_wc_get_item_remaining_refund_amount( $item ),
		'individual_use'               => false,
		'product_ids'                  => array(),
		'exclude_product_ids'          => array(),
		'usage_limit'                  => 1,
		'usage_limit_per_user'         => '',
		'limit_usage_to_x_items'       => '',
		'usage_count'                  => '',
		'expiry_date'                  => '',
		'enable_free_shipping'         => false,
		'product_category_ids'         => array(),
		'exclude_product_category_ids' => array(),
		'exclude_sale_items'           => false,
		'minimum_amount'               => '',
		'maximum_amount'               => '',
		'customer_emails'              => array( $user_billing_email ),
		'description'                  => stripslashes( $refund_desc )
	);


	// If coupon already exists, return it
	$existing_coupon_code = '';
	$object_id = $booking_type === 'group' ? $bookings[ 0 ]->group_id : $bookings[ 0 ]->id;
	$object_type = $booking_type === 'group' ? 'booking_group' : 'booking';
	$refunds = bookacti_get_metadata( $object_type, $object_id, 'refunds', true );
	$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $object_id, $booking_type ) : array();
	foreach( $refunds as $refund ) {
		if( isset( $refund[ 'coupon' ] ) ) { $existing_coupon_code = $refund[ 'coupon' ]; break; }
	}
	
	// Backward compatibility
	if( ! $existing_coupon_code ) {
		$existing_coupon_code = wc_get_order_item_meta( $item->get_id(), 'bookacti_refund_coupon', true );
	}

	if( $existing_coupon_code ) {
		$existing_coupon = WC()->api->WC_API_Coupons->get_coupon_by_code( $existing_coupon_code );

		return array( 
			'status' => 'success', 
			'coupon_amount' => is_array( $existing_coupon ) && isset( $existing_coupon[ 'coupon' ][ 'amount' ] ) ? wc_price( $existing_coupon[ 'coupon' ][ 'amount' ] ) : '', 
			'coupon_code' => $existing_coupon_code, 
			'new_state' => 'refunded' 
		);
	}


	// Grant user cap to create coupon
	$current_user = wp_get_current_user();
	$user_basically_can_publish_shop_coupons		= current_user_can( 'publish_shop_coupons' );
	$user_basically_can_read_private_shop_coupons	= current_user_can( 'read_private_shop_coupons' );
	if( ! $user_basically_can_publish_shop_coupons )		{ $current_user->add_cap( 'publish_shop_coupons' ); }
	if( ! $user_basically_can_read_private_shop_coupons )	{ $current_user->add_cap( 'read_private_shop_coupons' ); }

	// Generate coupon code and create the coupon
	$i = 1;
	$coupon = array();
	$booking_id_str = $booking_type === 'group' ? 'G' . $bookings[ 0 ]->group_id : 'B' . $bookings[ 0 ]->id;
	$code_template = apply_filters( 'bookacti_refund_coupon_code_template', 'R{user_id}N{refund_number}' );
	$code_template = str_replace( '{user_id}', '%1$s', $code_template );
	$code_template = str_replace( '{refund_number}', '%2$s', $code_template );

	$data = apply_filters( 'bookacti_refund_coupon_data', $data, $user, $item );

	do {
		// For the first occurrence, try to use the code that may have been changed with 'bookacti_refund_coupon_data' hook
		$data[ 'coupon' ][ 'code' ] = apply_filters( 'bookacti_refund_coupon_code', sprintf( $code_template, $booking_id_str, $i ), $data, $user, $item );
		$coupon = WC()->api->WC_API_Coupons->create_coupon( $data );
		$i++;
	}
	while( is_wp_error( $coupon ) && $coupon->get_error_code() === 'woocommerce_api_coupon_code_already_exists' );

	if( ! empty( $coupon ) && ! is_wp_error( $coupon ) ) {
		// Format the refunded date
		$refund_date = DateTime::createFromFormat( 'Y-m-d\TH:i:s\Z', $coupon[ 'coupon' ][ 'created_at' ] );
		if( ! is_a( $refund_date, 'DateTime' ) ) { $refund_date = new DateTime(); }
		$utc_timezone_obj = new DateTimeZone( 'UTC' );
		$refund_date->setTimezone( $utc_timezone_obj );
		$refund_date = $refund_date->format( 'Y-m-d H:i:s' );
		
		// Calculate the refunded quantity
		$refunded_qty = abs( $item->get_quantity() ) - abs( $order->get_qty_refunded_for_item( $item->get_id() ) );
		
		// Update refunds records array bound to the booking
		$refunds[ $coupon[ 'coupon' ][ 'code' ] ] = apply_filters( 'bookacti_wc_booking_refund_data_coupon', array( 'date' => $refund_date, 'quantity' => $refunded_qty, 'amount' => $coupon[ 'coupon' ][ 'amount' ], 'method' => 'coupon', 'coupon' => $coupon[ 'coupon' ][ 'code' ] ), $coupon, $bookings, $booking_type, $refund_message );
		bookacti_update_metadata( $object_type, $object_id, array( 'refunds' => $refunds ) );
		
		$return_data = array( 
			'status' => 'success', 
			'coupon_amount' => wc_price( $data[ 'coupon' ][ 'amount' ] ), 
			'coupon_code' => $coupon[ 'coupon' ][ 'code' ], 
			'new_state' => 'refunded' 
		);

	} else if( is_wp_error( $coupon ) ) {
		$return_data = array( 
			'status' => 'failed', 
			'error' => $coupon, 
			'message' => $coupon->get_error_message() 
		);
	}

	// Remove user cap to create coupon
	if( ! $user_basically_can_publish_shop_coupons )		{ $current_user->remove_cap( 'publish_shop_coupons' );	}
	if( ! $user_basically_can_read_private_shop_coupons )	{ $current_user->remove_cap( 'read_private_shop_coupons' );	}

	return $return_data;
}


/**
 * Auto refund (for supported gateway)
 * @version 1.9.0
 * @param array $bookings
 * @param string $booking_type Determine if the given id is a booking id or a booking group id. Accepted values are 'single' or 'group'.
 * @param string $refund_message
 * @return array
 */
function bookacti_auto_refund_booking( $bookings, $booking_type, $refund_message ) {
	// Get variables
	if( $booking_type === 'single' ) {
		$order_id	= $bookings[ 0 ]->order_id;
		$item		= bookacti_get_order_item_by_booking_id( $bookings[ 0 ] );
	} else if( $booking_type === 'group' ) {
		$order_id	= ! empty( $bookings[ 0 ]->group_order_id ) ? $bookings[ 0 ]->group_order_id : $bookings[ 0 ]->order_id;
		$item		= bookacti_get_order_item_by_booking_group_id( $bookings[ 0 ] );
	}
	
	$order = wc_get_order( $order_id );
	
	if( ! $order || ! $item ) {
		return array( 
			'status'	=> 'failed', 
			'error'		=> 'no_order_item_found',
			'message'	=> esc_html__( 'The order item bound to the booking was not found.', 'booking-activities' )
		);
	}
	
	$order_item_id = $item->get_id();
	$refunded_amounts = bookacti_wc_get_item_total_refunded( $item, true );
	$price_decimals = wc_get_price_decimals();
	
	$reason = esc_html__( 'Auto refund proceeded by user.', 'booking-activities' );
	if( $refund_message !== '' ) {
		$reason	.= PHP_EOL . esc_html__( 'User message:', 'booking-activities' ) . PHP_EOL . $refund_message;
	}
	
	$item_taxes = $item->get_taxes();
	$refund_tax = array();
	if( isset( $item_taxes[ 'total' ] ) ) {
		foreach( $item_taxes[ 'total' ] as $tax_id => $total ) {
			$refunded_tax_amount = abs( $order->get_tax_refunded_for_item( $order_item_id, $tax_id ) );
			$refund_tax[ $tax_id ] = wc_format_decimal( $total - $refunded_tax_amount );
		}
	} else {
		$refund_tax[] = round( $item->get_total_tax(), $price_decimals ) - $refunded_amounts[ 'tax' ];
	}
	
	$line_items	= array();
	$line_items[ $order_item_id ] = array(
		'qty'			=> $item->get_quantity() - abs( $order->get_qty_refunded_for_item( $order_item_id ) ),
		'refund_total'	=> round( $item->get_total(), $price_decimals ) - $refunded_amounts[ 'total' ],
		'refund_tax'	=> $refund_tax
	);

	$data = array(
		'amount'			=> bookacti_wc_get_item_remaining_refund_amount( $item ),
		'reason'			=> $reason,
		'order_id'			=> $order_id,
		'line_items'		=> $line_items,
		'refund_payment'	=> true
	);
	
	$refund = wc_create_refund( $data );

	if( is_wp_error( $refund ) ) {
		return array( 'status' => 'failed', 'error' => $refund->get_error_code(), 'message' => $refund->get_error_message() );
	}

	return array( 'status' => 'success', 'do_not_update_status' => 1, 'refund' => $refund );
}


/**
 * Get refunded amounts total and tax for an order item
 * @since 1.9.0
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
				$refunded[ 'total' ] += abs( $refunded_item->get_total() );
				$refunded[ 'tax' ] += abs( $refunded_item->get_total_tax() );
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
 * Get WC unsupported form fields names
 * @since 1.5.0
 * @return array
 */
function bookacti_get_wc_unsupported_form_fields() {
	return apply_filters( 'bookacti_wc_unsupported_form_fields', array( 'login', 'quantity', 'submit' ) );
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
		'type'		=> 'select',
		'name'		=> 'bookacti_products_settings[wc_product_pages_booking_form_location]',
		'id'		=> 'wc_product_pages_booking_form_location',
		'options'	=> array( 
							'default' => esc_html__( 'Inline (original layout)', 'booking-activities' ),
							'form_below' => esc_html__( 'Full width', 'booking-activities' ),
						),
		'value'		=> bookacti_get_setting_value( 'bookacti_products_settings', 'wc_product_pages_booking_form_location' ),
					/* translators: %s is the name of the described option: "Inline (original layout)" */
		'tip'		=> sprintf( esc_html__( '%s: display the booking form before the add to cart button, without changing your theme layout.', 'booking-activities' ), '<strong>' . esc_html__( 'Inline (original layout)', 'booking-activities' ) . '</strong>' )
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
 * @version 1.7.19
 */
function bookacti_settings_wc_my_account_bookings_page_id_callback() {
	$options = array(
		'-1' => esc_html__( 'Disabled' ),
		'0' => esc_html__( 'Default booking list', 'booking-activities' ),
	);
	$pages = get_pages( array( 'sort_column' => 'menu_order', 'sort_order' => 'ASC' ) );
	foreach( $pages as $page ) {
		$options[ $page->ID ] = apply_filters( 'bookacti_translate_text', $page->post_title );
	}

	$args = array(
		'type'		=> 'select',
		'name'		=> 'bookacti_account_settings[wc_my_account_bookings_page_id]',
		'id'		=> 'wc_my_account_bookings_page_id',
		'class'		=> 'bookacti-select2-no-ajax',
		'options'	=> $options,
		'value'		=> bookacti_get_setting_value( 'bookacti_account_settings', 'wc_my_account_bookings_page_id' ),
		'tip'		=> esc_html__( 'Select the page to display in the "Bookings" tab of the "My account" area. You can also display the default booking list, or completely disable this tab.', 'booking-activities' )
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
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_cart_settings[is_cart_expiration_active]',
		'id'	=> 'is_cart_expiration_active',
		'value'	=> bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' ),
		'tip'	=> esc_html__( "If cart expiration is off, the booking is made at the end of the checkout process. It means that an event available at the moment you add it to cart can be no longer available at the moment you wish to complete the order. With cart expiration on, the booking is made when it is added to cart and remains temporary until the end of the checkout process.", 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Activate per product expiration
 * @version 1.7.16
 */
function bookacti_settings_field_per_product_expiration_callback() {
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_cart_settings[is_cart_expiration_per_product]',
		'id'	=> 'is_cart_expiration_per_product',
		'value'	=> bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' ),
		'tip'	=> esc_html__( 'The expiration time will be set for each product independantly, each with their own countdown before being removed from cart.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Set amount of time before expiration
 * @version 1.7.16
 */
function bookacti_settings_field_cart_timeout_callback() { 
	$args = array(
		'type'		=> 'number',
		'name'		=> 'bookacti_cart_settings[cart_timeout]',
		'id'		=> 'cart_expiration_time',
		'options'	=> array( 'min' => 1, 'step' => 1 ),
		'value'		=> bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' ),
		'tip'		=> esc_html__( 'Define the amount of time a user has before his cart gets empty.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Setting for: Reset the countdown each time a change occur to cart
 * @version 1.7.16
 */
function bookacti_settings_field_reset_cart_timeout_on_change_callback() {
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_cart_settings[reset_cart_timeout_on_change]',
		'id'	=> 'reset_cart_timeout_on_change',
		'value'	=> bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' ),
		'tip'	=> esc_html__( 'The countdown will be reset each time a product is added, or when a product quantity is changed.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}




// GENERAL 

/**
 * Determines if user is shop manager
 * @version 1.6.0
 * @param int $user_id
 * @return boolean
 */
function bookacti_is_shop_manager( $user_id = 0 ) {

	if( $user_id === 0 ) {
		$user_id = get_current_user_id();
	}

	$user = get_user_by( 'id', $user_id );
	if ( isset( $user->roles ) && in_array( 'shop_manager', $user->roles, true ) ) {
		return true;
	}
	return false;
}


/**
 * Check if the current page is a WooCommerce screen
 * @since 1.7.3 (was bookacti_is_wc_edit_product_screen)
 * @version 1.8.0
 * @return boolean
 */
function bookacti_is_wc_screen( $screen_ids = array() ) {
	$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
	if( empty( $current_screen ) ) { return false; }
	if( ! $screen_ids || ! is_array( $screen_ids ) ) { $screen_ids = wc_get_screen_ids(); }
	if( isset( $current_screen->id ) && in_array( $current_screen->id, $screen_ids, true ) ) { return true; }
	return false;
}