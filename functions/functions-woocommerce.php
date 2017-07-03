<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CART

	/**
	 * Insert or update a booking in cart
	 * 
	 * @since 1.1.0 (replace bookacti_insert_booking_in_cart)
	 * 
	 * @param int $user_id
	 * @param int $event_id
	 * @param string $event_start
	 * @param string $event_end
	 * @param int $quantity
	 * @param int $booking_group_id
	 * @return array
	 */
	function bookacti_add_booking_to_cart( $user_id, $event_id, $event_start, $event_end, $quantity, $booking_group_id = NULL ) {

		$return_array = array( 'status' => 'failed' );

		//Check if the booking already exists 
		$booking_id = bookacti_booking_exists( $user_id, $event_id, $event_start, $event_end, 'in_cart', $booking_group_id );

		// if booking already exist in cart, just update its quantity and expiration date
		if( $booking_id ) {

			// Get booking and add new quantity to old one
			$booking		= bookacti_get_booking_by_id( $booking_id );
			$new_quantity	= intval( $booking->quantity ) + intval( $quantity );

			// Update quantity
			$updated = bookacti_controller_update_booking_quantity( $booking_id, $new_quantity );

			if( $updated[ 'status' ] === 'success' ) {
				$return_array[ 'status' ] = 'success';
				$return_array[ 'action' ] = 'updated';
				$return_array[ 'id' ] = $booking->id;
			}

		} else {

			$expiration_date = bookacti_get_expiration_time();

			// Insert a new booking
			$booking_id = bookacti_insert_booking( $user_id, $event_id, $event_start, $event_end, $quantity, 'in_cart', $expiration_date );

			if( ! is_null( $booking_id ) ) {
				$return_array[ 'status' ] = 'success';
				$return_array[ 'action' ] = 'inserted';
				$return_array[ 'id' ] = $booking_id;
			}
		}

		// If failed, write a message
		if( $return_array[ 'status' ] === 'failed' ) {
			$return_array[ 'message' ] = __( 'Error occurs when trying to temporarily book your event. Please try later.', BOOKACTI_PLUGIN_NAME );
		}

		return $return_array;
	}


	/**
	 * Insert a booking group in cart
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param int $quantity
	 * @return array
	 */
	function bookacti_add_booking_group_to_cart( $user_id, $event_group_id, $quantity ) {

		$return_array = array( 'status' => 'failed' );

		//Check if the booking already exists 
		$booking_group_id	= bookacti_booking_group_exists( $user_id, $event_group_id, 'in_cart' );

		// if booking group already exist in cart, just update its bookings quantity and expiration date
		if( $booking_group_id ) {

			// Update quantity of each bookings
			$group_updated = bookacti_controller_update_booking_group_quantity( $booking_group_id, $quantity, true );

			// If each event has been updated return $booking_group_id
			if( $group_updated[ 'status' ] === 'success' ) {
				$return_array[ 'status' ] = 'success';
				$return_array[ 'action' ] = 'updated';
				$return_array[ 'id' ] = $booking_group_id;
			} else {
				$return_array[ 'status' ]	= 'failed';
				$return_array[ 'message' ]	= __( 'An error occurs while trying to change a product quantity. Please try again later.', BOOKACTI_PLUGIN_NAME );
			}

		} else {

			$expiration_date = bookacti_get_expiration_time();

			// Book all events of the group
			$booking_group_id = bookacti_book_group_of_events( $user_id, $event_group_id, $quantity, 'in_cart', $expiration_date );

			if( ! is_null( $booking_group_id ) ) {
				$return_array['status'] = 'success';
				$return_array['action'] = 'inserted';
				$return_array['id']		= $booking_group_id;
			}
		}

		// If failed, write a message
		if( $return_array['status'] === 'failed' ) {
			$return_array['message'] = __( 'Error occurs when trying to temporarily book the group of events. Please try later.', BOOKACTI_PLUGIN_NAME );
		}

		return $return_array;
	}


	/**
	 * Update quantity, control the results ans display feedback accordingly
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @global woocommerce $woocommerce
	 * @param int $booking_id
	 * @param int $new_quantity
	 * @param string $context
	 * @return array
	 */
	function bookacti_controller_update_booking_quantity( $booking_id, $new_quantity, $context = 'frontend' ) {
		global $woocommerce;
		
		// Get cart data and the expiration date
		if( $context === 'frontend' ) {
			$current_cart_expiration_date = bookacti_get_cart_timeout();

			$is_cart_empty_and_expired		= ( $woocommerce->cart->get_cart_contents_count() === $new_quantity && strtotime( $current_cart_expiration_date ) <= time() );
			$is_per_product_expiration		= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
			$reset_timeout_on_change		= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
			$is_expiration_active			= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
			$timeout						= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );

			$new_expiration_date = NULL;
			if( $reset_timeout_on_change || $is_cart_empty_and_expired ) {
				$new_expiration_date = date( 'c', strtotime( '+' . $timeout . ' minutes' ) );
			}
		}

		$response = bookacti_update_booking_quantity( $booking_id, $new_quantity, $new_expiration_date, $context );
		
		// Update cart expiration date if needed
		if( $context === 'frontend' ) {
			if( $response[ 'status' ] === 'success'
			&&  $is_expiration_active 
			&&  ( $reset_timeout_on_change || $is_cart_empty_and_expired ) 
			&&  ! $is_per_product_expiration ) {

				bookacti_reset_cart_expiration_dates( $new_expiration_date );
				
			} else if( $response[ 'status' ] === 'failed' ) {

				if( $response[ 'error' ] === 'qty_sup_to_avail' ) {
					wc_add_notice( 
								/* translators: %1$s is a variable number of bookings. This sentence is followed by two others : 'but only %1$s is available on this schedule.' and 'Please choose another schedule or decrease the quantity.' */
								sprintf( _n( 'You want to add %1$s booking to your cart', 'You want to add %1$s bookings to your cart', $new_quantity, BOOKACTI_PLUGIN_NAME ), $new_quantity )
								/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to add %1$s booking to your cart' and followed by 'Please choose another schedule or decrease the quantity.' */
						. ' ' . sprintf( _n( 'but only %1$s is available on this schedule.', 'but only %1$s are available on this schedule. ', $response[ 'availability' ], BOOKACTI_PLUGIN_NAME ), $response[ 'availability' ] )
								/* translators: This sentence is preceded by two others : 'You want to add %1$s booking to your cart' and 'but only %1$s is available on this schedule.' */
						. ' ' . __( 'Please choose another schedule or decrease the quantity.', BOOKACTI_PLUGIN_NAME )
					, 'error' );

				} else if( $response[ 'error' ] === 'no_availability' ) {
					// If the event is no longer available, notify the user
					wc_add_notice( __( 'This schedule is no longer available. Please choose another schedule.', BOOKACTI_PLUGIN_NAME ), 'error' );

				} else if( $response[ 'error' ] === 'failed' ) {

					wc_add_notice( __( 'An error occurs while trying to change a product quantity. Please try again later.', BOOKACTI_PLUGIN_NAME ), 'error' );

				}
			}
		}

		return $response;
	}


	/**
	 * Update quantity, control the results ans display feedback accordingly
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $booking_group_id
	 * @param int $quantity
	 * @param boolean $add_quantity
	 * @param string $context
	 * @return boolean
	 */
	function bookacti_controller_update_booking_group_quantity( $booking_group_id, $quantity, $add_quantity = false, $context = 'frontend' ) {

		// Sanitize
		$quantity		= intval( $quantity );
		$add_quantity	= boolval( $add_quantity );

		$response = array( 'status' => 'success' );

		// Get bookings of the group
		$bookings			= bookacti_get_bookings_by_booking_group_id( $booking_group_id );

		// Get group availability
		$group				= bookacti_get_booking_group_by_id( $booking_group_id );
		$group_availability	= bookacti_get_group_of_events_availability( $group->event_group_id );

		// Make sure all events have enough places available
		// Look for the most booked event of the booking group
		$max_booked = 0;
		foreach( $bookings as $booking ) {
			if( $booking->active && $booking->quantity > $max_booked ) {
				$max_booked = intval( $booking->quantity );
			}
		}

		$booking_max_new_quantity = $add_quantity ? $quantity + $max_booked : $quantity;
		
		// If quantity is superior to availablity, return the error
		if( $booking_max_new_quantity > ( $group_availability + $max_booked ) ) {
			
			$response[ 'status' ]		= 'failed';
			$response[ 'error' ]		= 'qty_sup_to_avail';
			$response[ 'availability' ]	= $add_quantity ? $group_availability : $group_availability + $max_booked;
			
			if( $context === 'frontend' ) {
				if( $response[ 'availability' ] > 0 ) {
					wc_add_notice( 
								/* translators: %1$s is a variable number of bookings. This sentence is followed by two others : 'but only %1$s is available on this schedule.' and 'Please choose another schedule or decrease the quantity.' */
								sprintf( _n( 'You want to add %1$s booking to your cart', 'You want to add %1$s bookings to your cart', $quantity, BOOKACTI_PLUGIN_NAME ), $quantity )
								/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to add %1$s booking to your cart' and followed by 'Please choose another schedule or decrease the quantity.' */
						. ' ' . sprintf( _n( 'but only %1$s is available on this schedule.', 'but only %1$s are available on this schedule. ', $response[ 'availability' ], BOOKACTI_PLUGIN_NAME ), $response[ 'availability' ] )
								/* translators: This sentence is preceded by two others : 'You want to add %1$s booking to your cart' and 'but only %1$s is available on this schedule.' */
						. ' ' . __( 'Please choose another schedule or decrease the quantity.', BOOKACTI_PLUGIN_NAME )
					, 'error' );
				} else {
					wc_add_notice( __( 'This schedule is no longer available. Please choose another schedule.', BOOKACTI_PLUGIN_NAME ), 'error' );
				}
			}

			return $response;
		}

		// Update each booking quantity
		foreach( $bookings as $booking ) {

			$booking_qty	= $booking->active ? intval( $booking->quantity ) : 0;

			// Make sure new quantity isn't over group availability
			$new_quantity = $add_quantity ? $quantity + $booking_qty : $quantity;
			if( $new_quantity > ( $group_availability + $booking_qty ) ){
				$new_quantity = $add_quantity ? $group_availability : $group_availability + $booking_qty;
			}

			// Update quantity
			$updated1 = bookacti_controller_update_booking_quantity( $booking->id, $new_quantity, $context );
			
			if( ! isset( $updated1[ 'status' ] ) || $updated1[ 'status' ] === 'failed' ) {
				$response[ 'status' ]	= 'failed';
				$response[ 'error' ]	= $updated1[ 'error' ];
			}
		}

		// Change booking group state
		if( $response[ 'status' ] === 'success' ) {

			// Change booking group state to remove if quantity = 0
			if( ! $add_quantity && $quantity === 0 ) {
				$new_state = $context === 'frontend' ? 'removed' : 'cancelled';
				$updated2 = bookacti_update_booking_group_state( $booking_group_id, $new_state );
				do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array() );
			}

			// If the group used to be removed (quantity = 0), turn its state to in_cart
			if( $group->state === 'removed' && $quantity > 0 ) {
				$new_state = $context === 'frontend' ? 'in_cart' : 'pending';
				$updated2 = bookacti_update_booking_group_state( $booking_group_id, $new_state );
				do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array() );
			}

			if( isset( $updated2 ) && ! $updated2 ) {
				$response[ 'status' ]	= 'failed';
				$response[ 'error' ]	= 'update_booking_group_state';
			}
		}


		return $response;
	}


	/**
	 * Check if the booking group has expired
	 * 
	 * @since 1.1.0
	 * 
	 * @param type $booking_group_id
	 * @return boolean
	 */
	function bookacti_is_expired_booking_group( $booking_group_id ) {

		$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );

		$is_expired = false;
		foreach( $booking_ids as $booking_id ) {
			$is_booking_expired = bookacti_is_expired_booking( $booking_id );

			// If one booking of the group is expired, consider the whole group has expired
			if( $is_booking_expired ) {
				$is_expired = true;
				break;
			}
		}

		return $is_expired;
	}
	
	
	/**
	 * Reset expiration dates of all cart items
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @global woocommerce $woocommerce
	 * @param string $expiration_date
	 * @return int|false
	 */
	function bookacti_reset_cart_expiration_dates( $expiration_date ) {
		global $woocommerce;

		$cart_contents = $woocommerce->cart->get_cart();
		$updated = null;
		if( ! empty( $cart_contents ) ) {

			bookacti_set_cart_timeout( $expiration_date );

			$cart_keys = array_keys( $cart_contents );

			$booking_id_array = array();
			foreach ( $cart_keys as $key ) {
				// Single event
				if( isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] ) && ! empty( $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] ) ) {
					array_push( $booking_id_array, $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] );

				// Group of events
				} else if( isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_group_id'] ) && ! empty( $cart_contents[$key]['_bookacti_options']['bookacti_booking_group_id'] ) ) {
					// Add the group booking ids to the bookings array to change state
					$booking_group_id	= $cart_contents[$key]['_bookacti_options']['bookacti_booking_group_id'];
					$booking_ids		= bookacti_get_booking_group_bookings_ids( $booking_group_id );
					$booking_id_array	= array_merge( $booking_id_array, $booking_ids );
				}
			}

			$user_id = $woocommerce->session->get_customer_id();
			if( is_user_logged_in() ) { $user_id = get_current_user_id(); }

			$updated = bookacti_update_in_cart_bookings_expiration_date( $user_id, $booking_id_array, $expiration_date );

		} else {

			bookacti_set_cart_timeout( null );
		}

		return $updated;
	}


	/**
	 * Get expiration time
	 * 
	 * @global woocommerce $woocommerce
	 * @param string $date_format
	 * @return string
	 */
	function bookacti_get_expiration_time( $date_format = 'c' ) {

		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

		if( $is_expiration_active ) {

			$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );

			if( ! $is_per_product_expiration ) {

				global $woocommerce;

				$expiration_date = bookacti_get_cart_timeout();

				if( is_null ( $expiration_date ) 
				|| strtotime( $expiration_date ) <= time() 
				|| $woocommerce->cart->get_cart_contents_count() === 0 ) {

					$timeout = bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
					$expiration_date = date( $date_format, strtotime( '+' . $timeout . ' minutes' ) );

					bookacti_set_cart_timeout( $expiration_date );
				}

			} else {
				$timeout = bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
				$expiration_date = date( $date_format, strtotime( '+' . $timeout . ' minutes' ) );
			}

		} else {
			$expiration_date = NULL;
		}

		return $expiration_date;
	}


	/**
	 * Get timeout for a cart item
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @global woocommerce $woocommerce
	 * @param type $cart_item_key
	 * @return string
	 */
	function bookacti_get_cart_item_timeout( $cart_item_key ) {

		global $woocommerce;

		$item = $woocommerce->cart->get_cart_item( $cart_item_key );

		// Single event
		if( isset( $item[ '_bookacti_options' ][ 'bookacti_booking_id' ] ) ) {

			$booking_id			= $item[ '_bookacti_options' ][ 'bookacti_booking_id' ];
			$expiration_date	= bookacti_get_booking_expiration_date( $booking_id );
			$state				= bookacti_get_booking_state( $booking_id );

		// group of events
		} else if( isset( $item[ '_bookacti_options' ][ 'bookacti_booking_group_id' ] ) ) {

			$booking_group_id	= $item[ '_bookacti_options' ][ 'bookacti_booking_group_id' ];
			$expiration_date	= bookacti_get_booking_group_expiration_date( $booking_group_id );
			$state				= bookacti_get_booking_group_state( $booking_group_id );

		}

		if( ! isset( $expiration_date ) || empty( $expiration_date ) || ! in_array( $state, array( 'in_cart', 'pending' ) ) ) {
			return '';
		}

		$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );

		$timeout = '<div class="bookacti-cart-item-expires-with-cart"></div>';

		if( $is_per_product_expiration && $state === 'in_cart' ) {

			$timeout = '<div class="bookacti-countdown-container">'
						. '<div class="bookacti-countdown" data-expiration-date="' . esc_attr( $expiration_date ) . '" ></div>'
					. '</div>';

		} else if( $state === 'pending' ) {

			$state_text = __( 'Pending payment', BOOKACTI_PLUGIN_NAME );
			$timeout = '<div class="bookacti-cart-item-state bookacti-cart-item-state-pending">' . esc_html( $state_text ) . '</div>';
		}

		return $timeout;
	}


	/**
	 * Get cart timeout
	 * 
	 * @global woocommerce $woocommerce
	 * @param int $user_id
	 * @return string
	 */
	function bookacti_get_cart_timeout( $user_id = NULL ) {

		global $woocommerce;

		if( is_user_logged_in() || ! is_null( $user_id ) ) {
			if( is_null ( $user_id ) ) { $user_id = get_current_user_id(); }
			$cart_expiration_date	= get_user_meta( $user_id, 'bookacti_expiration_cart', true );
		} else {
			$cart_expiration_date	= $woocommerce->session->get( 'bookacti_expiration_cart' );
		}

		return $cart_expiration_date;
	}


	/**
	 * Set cart timeout
	 * 
	 * @global woocommerce $woocommerce
	 * @param type $expiration_date
	 * @param type $user_id
	 */
	function bookacti_set_cart_timeout( $expiration_date, $user_id = NULL ) {

		global $woocommerce;

		if( is_user_logged_in() || ! is_null( $user_id ) ) {
			if( is_null ( $user_id ) ) { $user_id = get_current_user_id(); }
			update_user_meta( $user_id, 'bookacti_expiration_cart', $expiration_date );
		} else {
			$woocommerce->session->set( 'bookacti_expiration_cart', $expiration_date );
		}
	}




// ORDERS
	
	/**
	 * Check if an id is a WooCommerce Order
	 * 
	 * @since 1.1.0
	 */
	function bookacti_is_wc_order( $order_id ) {
		$post_type = get_post_type( $order_id );
		
		if( empty( $post_type ) ) {
			return false;
		}
		
		return $post_type === 'shop_order';
	}
	
	
	/**
	 * Turn all bookings of an order to the desired status. 
	 * Also make sure that bookings are bound to the order and the associated user.
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @param int $order_id
	 * @param string $state
	 * @param boolean $alert_if_fails
	 * @return int|false
	 */
	function bookacti_turn_order_bookings_to( $order_id, $state = 'booked', $alert_if_fails = false ) {

		// Retrieve order data
		$order = wc_get_order( $order_id );

		if( empty( $order ) ) {
			return false;
		}

		// Retrieve bought items and user id
		$items		= $order->get_items();
		$user_id	= $order->get_user_id();
		
		// Create an array with order booking ids
		$booking_id_array = array();
		foreach( $items as $key => $item ) {
			// Single event
			if( isset( $item[ 'bookacti_booking_id' ] ) && ! empty( $item[ 'bookacti_booking_id' ] ) ) {				
				// Add the booking id to the bookings array to change state
				$booking_id = $item[ 'bookacti_booking_id' ];
				array_push( $booking_id_array, $booking_id );

			// Group of events
			} else if( isset( $item[ 'bookacti_booking_group_id' ] ) && ! empty( $item[ 'bookacti_booking_group_id' ] ) ) {
				// Add the group booking ids to the bookings array to change state
				$booking_group_id	= $item[ 'bookacti_booking_group_id' ];
				
				$booking_ids		= bookacti_get_booking_group_bookings_ids( $booking_group_id );
				$booking_id_array	= array_merge( $booking_id_array, $booking_ids );
				
				// Change the booking group state accordingly
				// Also change its user_id and order_id to make sure it is up to date
				$booking_group_state	= $item[ 'bookacti_state' ];
				$is_active				= in_array( $booking_group_state, bookacti_get_active_booking_states() ) ? 1 : 0;
				$update_state			= $is_active ? $state : null;
				$group_updated			= bookacti_update_booking_group( $booking_group_id, $update_state, $user_id, $order_id );
				
				if( $group_updated && ! empty( $update_state ) ) {
					do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $update_state, array() );
				}
				
			}
		}

		if( ! empty( $booking_id_array ) ) {

			$updated = bookacti_change_order_bookings_state( $user_id, $order_id, $booking_id_array, $state );

			// If bookings have not updated correctly, send an e-mail to alert the administrator
			if( $alert_if_fails && $updated[ 'status' ] !== 'success' ) {

				$errors_list = '<ul>';
				foreach( $updated[ 'errors' ] as $error ) {
					if( $error === 'invalid_user_id' )		{ $errors_list .= '<li>' . esc_html__( 'Invalid user ID.', BOOKACTI_PLUGIN_NAME ) . '</li>'; }
					if( $error === 'invalid_order_id' )		{ $errors_list .= '<li>' . esc_html__( 'Invalid order ID.', BOOKACTI_PLUGIN_NAME ) . '</li>'; }
					if( $error === 'invalid_booking_ids' )	{ $errors_list .= '<li>' . esc_html__( 'The order contains invalid booking IDs.', BOOKACTI_PLUGIN_NAME ) . '</li>'; }
					if( $error === 'update_failed' )		{ $errors_list .= '<li>' . esc_html__( 'Database failed to update.', BOOKACTI_PLUGIN_NAME ) . '</li>'; }
					if( $error === 'no_booking_ids' )		{ $errors_list .= '<li>' . esc_html__( 'The order doesn\'t contains any booking IDs.', BOOKACTI_PLUGIN_NAME ) . '</li>'; }
				}
				$errors_list .= '</ul>';

				$booking_ids	= implode( ', ', $booking_id_array );

				$to				= array( get_option( 'woocommerce_stock_email_recipient' ), get_option( 'admin_email' ) );

				/* translators: %1$s stands for booking ids and %2$s stands for the order id */
				$subject		= '/!\\ ' . sprintf( _n( 'Booking %1$s has not been correctly validated for order %2$s', 'Bookings %1$s have not been correctly validated for order %2$s.', count( $booking_id_array ), BOOKACTI_PLUGIN_NAME ), $booking_ids, $order_id );

				/* translators: %1$s stands for booking ids and %2$s stands for the order id */
				$message		= esc_html( sprintf( _n( 'Booking %1$s has not been correctly validated for order %2$s', 'Bookings %1$s have not been correctly validated for order %2$s.', count( $booking_id_array ), BOOKACTI_PLUGIN_NAME ), $booking_ids, $order_id ) )
								. ' ' . esc_html__( 'Here is the errors list:', BOOKACTI_PLUGIN_NAME );
				$message	   .= '<br/>' . $errors_list;
				$message	   .= '<br/>' . esc_html__( 'Please verify the order and its bookings, and validate bookings manually if necessary.', BOOKACTI_PLUGIN_NAME );

				$headers		= array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $to, $subject, $message, $headers );
			}

			return $updated;
		}
	}
	
	
	/**
	 * Turn the order state if it is composed of inactive / pending / booked bookings only
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $order_id
	 * @return void
	 */
	function bookacti_change_order_state_based_on_its_bookings_state( $order_id ) {
		
		$order = wc_get_order( $order_id );
		
		if( empty( $order ) ) {
			return;	
		}
		
		if( ! in_array( $order->get_status(), array( 'processing', 'on-hold', 'completed' ) ) ) {
			return;
		}
		
		$items = $order->get_items();
		
		if( empty( $items ) ) {
			return;
		}
		
		// Get items booking states and
		// Determine if the order is only composed of activities
		$states = array();
		$only_activities = true;
		foreach( $items as $item ) {
			if( ! isset( $item[ 'bookacti_booking_id' ] ) && ! isset( $item[ 'bookacti_booking_group_id' ] ) ) { 
				$only_activities = false; 
				break;
			}
			$states[] = $item[ 'bookacti_state' ];
		}
		
		if( ! $only_activities || empty( $states ) || in_array( 'in_cart', $states ) ) {
			return;	
		}
		
		$new_order_status = 'cancelled';
		
		if( in_array( 'pending', $states ) ) {
			// Turn order status to pending payment
			$new_order_status = 'processing';
		} else if( in_array( 'booked', $states ) ) {
			// Turn order status to completed
			$new_order_status = 'completed';
		} else if( in_array( 'refunded', $states ) && ! in_array( 'refund_requested', $states ) ) {
			// Turn order status to refunded
			$new_order_status = 'refunded';
		}
		
		$new_order_status = apply_filters( 'bookacti_woocommerce_order_status_automatically_updated', $new_order_status, $order );
		
		if( $new_order_status !== $order->get_status() ) {
			$order->update_status( $new_order_status );
		}
	}
	
	
	/**
	 * Get woocommerce order item id by booking id
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @param int $booking_id
	 * @return array
	 */
	function bookacti_get_order_item_by_booking_id( $booking_id ) {

		if( ! $booking_id ) { return false; }

		$order_id = bookacti_get_booking_order_id( $booking_id );

		if( ! $order_id ) { return false; }

		$order = wc_get_order( $order_id );

		if( empty( $order ) ) { return false; }

		$order_items = $order->get_items();

		$item = array();
		foreach( $order_items as $order_item_id => $order_item ) {

			$is_in_item = false;
			// Check if the item is bound to a the desired booking
			if( isset( $order_item[ 'bookacti_booking_id' ] ) && $order_item[ 'bookacti_booking_id' ] == $booking_id ) {
				$is_in_item = true;

			// Check if the item is bound to a group of bookings
			} else if( isset( $order_item[ 'bookacti_booking_group_id' ] ) ) {
				$booking_group_id = $order_item[ 'bookacti_booking_group_id' ];
				$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );
				// Check if the desired booking is in the group
				if( in_array( $booking_id, $booking_ids ) ) {
					$is_in_item = true;
				}
			}

			if( $is_in_item ) {
				$item				= $order_items[ $order_item_id ];
				$item[ 'id' ]		= $order_item_id;
				$item[ 'order_id' ]	= $order_id;
			}
		}

		return $item;
	}

	
	/**
	 * Get woocommerce order item id by booking group id
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $booking_group_id
	 * @return array
	 */
	function bookacti_get_order_item_by_booking_group_id( $booking_group_id ) {

		if( ! $booking_group_id ) { return false; }

		$order_id = bookacti_get_booking_group_order_id( $booking_group_id );
		
		if( ! $order_id ) { return false; }

		$order = wc_get_order( $order_id );

		if( empty( $order ) ) { return false; }

		$order_items = $order->get_items();

		$item = array();
		foreach( $order_items as $order_item_id => $order_item ) {

			$is_in_item = false;
			// Check if the item is bound to a the desired booking
			if( isset( $order_item[ 'bookacti_booking_group_id' ] ) && $order_item[ 'bookacti_booking_group_id' ] == $booking_group_id ) {
				$is_in_item = true;
			}

			if( $is_in_item ) {
				$item				= $order_items[ $order_item_id ];
				$item[ 'id' ]		= $order_item_id;
				$item[ 'order_id' ]	= $order_id;
			}
		}

		return $item;
	}
	
	
	/**
	 * Whether to give the possibility to a user to cancel or reschedule a booking
	 * Also add woocommerce specifique actions
	 * 
	 * @version 1.1.0
	 * 
	 * @param array $booking_actions
	 * @param int $order_id
	 * @return array
	 */
	function bookacti_display_actions_buttons_on_items( $booking_actions, $order_id ) {
		
		if( ! $order_id || ! is_numeric( $order_id ) || ! bookacti_is_wc_order( $order_id ) ) {
			return $booking_actions;
		}
		
		$order = wc_get_order( $order_id );

		if( empty( $order ) ) {
			return $booking_actions;
		}

		// Check cancel / reschedule
		if( ! current_user_can( 'bookacti_edit_bookings' ) && $order->get_status() === 'pending' )	{ 
			if( isset( $booking_actions['cancel'] ) )		{ unset( $booking_actions['cancel'] ); } 
			if( isset( $booking_actions['reschedule'] ) )	{ unset( $booking_actions['reschedule'] ); }
		}

		// Add woocommerce specifique actions
		$booking_actions[ 'view-order' ] = array( 
			'class'			=> 'bookacti-view-booking-order _blank',
			'label'			=> __( 'View order', BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Go to the related WooCommerce admin order page.', BOOKACTI_PLUGIN_NAME ),
			'link'			=> get_admin_url() . 'post.php?post=' . $order_id . '&action=edit',
			'admin_or_front'=> 'admin' 
		);
		
		return $booking_actions;
	}

	


// PRODUCT

	/**
	 * Get the product id bound to a booking
	 * 
	 * @since 1.0.4
	 * 
	 * @param type $booking_id
	 * @return int
	 */
	function bookacti_get_booking_product_id( $booking_id ) {

		$item = bookacti_get_order_item_by_booking_id( $booking_id );

		if( empty( $item ) ) { return false; }

		// WOOCOMMERCE 3.0.0 backward compatibility
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$product_id = $item->get_product_id();
		} else {
			$order_id = bookacti_get_booking_order_id( $booking_id );

			if( ! $order_id ) { return false; }

			$order = wc_get_order( $order_id );
			
			if( empty( $order ) ) { return false; }
			
			$_product  = $order->get_product_from_item( $item );

			if( empty( $_product ) ) { return false; }

			$product_id = absint( $_product->id );
		}

		return $product_id;
	}
	
	
	/**
	 * Tell if the product is activity or has variations that are activities
	 * 
	 * @version 1.0.4
	 * 
	 * @param WC_Product|int $product
	 * @return boolean
	 */
	function bookacti_product_is_activity( $product ) {
		if( ! is_object( $product ) ) {
			$product_id = intval( $product );

			$is_product_activity	= get_post_meta( $product_id, '_bookacti_is_activity', true ) === 'yes';
			$is_variation_activity	= get_post_meta( $product_id, 'bookacti_variable_is_activity', true ) === 'yes';

			if( $is_product_activity || $is_variation_activity ) {
				return true;
			}

		} else {
			// WOOCOMMERCE 3.0.0 BW compability
			if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
				if( method_exists( $product, 'get_visible_children' ) ) {
					$variation_ids = $product->get_visible_children();
				}
			} else {
				$variation_ids = $product->get_children( true );
			}

			$has_variation_activity = false;
			if( ! empty( $variation_ids ) ) {
				foreach( $variation_ids as $variation_id ) {
					if( get_post_meta( $variation_id, 'bookacti_variable_is_activity', true ) === 'yes' ) {
						$has_variation_activity = true;
						break;
					}
				}
			}

			$is_activity = get_post_meta( $product->get_id(), '_bookacti_is_activity', true ) === 'yes';

			if(( $product->is_type( 'simple' ) && $is_activity ) 
			|| ( $product->is_type( 'variable' ) && $has_variation_activity )) {
				return true;
			}
		}

		return false;
	}




// REFUND

	/**
	 * Filter refund actions by order id
	 * 
	 * @since 1.1.0
	 * 
	 * @param array $possible_actions
	 * @param int $order_id
	 * @return type
	 */
	function bookacti_filter_refund_actions_by_order( $possible_actions, $order_id ) {
		if( $order_id ) {
			foreach( $possible_actions as $key => $possible_action ){
				// Allow auto-refund only if gateway allows it
				if( $possible_action['id'] === 'auto' && ! bookacti_does_order_support_auto_refund( $order_id ) ){
					unset( $possible_actions[ $key ] );
				}
			}
		} else {
			// If the booking has not been taken with WooCommerce, remove WooCommerce refund methods
			$woocommerce_actions = bookacti_add_woocommerce_refund_actions( array() );
			foreach( $woocommerce_actions as $woocommerce_action ) {
				unset( $possible_actions[ $woocommerce_action[ 'id' ] ] );
			}
		}

		return $possible_actions;
	}



	/**
	 * Check if an order support auto refund
	 * 
	 * @param WC_Order|int $order_id
	 * @return boolean
	 */
	function bookacti_does_order_support_auto_refund( $order_id ) {
		
		if( ! is_numeric( $order_id ) || ! bookacti_is_wc_order( $order_id ) ){
			return false;
		}
		
		$order = wc_get_order( intval( $order_id ) );
		
		if( empty( $order ) ) {
			return false;
		}
		
		// WOOCOMMERCE 3.0.0 BW compability
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$payment_method = $order->get_payment_method();
		} else {
			$payment_method = $order->payment_method;
		}
		
		$allow_auto_refund = false;
		if ( WC()->payment_gateways() ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
		}
		if ( isset( $payment_gateways[ $payment_method ] ) && $payment_gateways[ $payment_method ]->supports( 'refunds' ) ) {
			$allow_auto_refund = true;
		}
		
		return $allow_auto_refund;
	}

	
	/**
	 * Create a coupon to refund a booking
	 * 
	 * @version 1.1.0
	 * 
	 * @param int $booking_id
	 * @param string $booking_type Determine if the given id is a booking id or a booking group. Accepted values are 'single' or 'group'.
	 * @param string $refund_message
	 * @return array
	 */
	function bookacti_refund_booking_with_coupon( $booking_id, $booking_type, $refund_message ) {

		// Include & load API classes
		if( ! class_exists( 'WC_API_Coupons' ) ) {
			WC()->api->includes();
			WC()->api->register_resources( new WC_API_Server( '/' ) );
		}

		// Get variables
		if( $booking_type === 'single' ) {
			$user_id	= bookacti_get_booking_owner( $booking_id );
			$item		= bookacti_get_order_item_by_booking_id( $booking_id );
		} else if( $booking_type === 'group' ) {
			$user_id	= bookacti_get_booking_group_owner( $booking_id );
			$item		= bookacti_get_order_item_by_booking_group_id( $booking_id );
		}
		
		$user_data		= get_userdata( $user_id );
		
		$amount				= (float) $item[ 'line_total' ] + (float) $item[ 'line_tax' ];
		$user_billing_email = get_user_meta( $user_id, 'billing_email', true );

		// Write code description
		$refund_desc	= __( 'Coupon created as a refund for:', BOOKACTI_PLUGIN_NAME );
		$refund_desc	.= PHP_EOL . __( 'User', BOOKACTI_PLUGIN_NAME )	. ' ' . $user_data->ID . ' (' . $user_data->user_login . ' / ' . $user_data->user_email . ')';
		$refund_desc	.= PHP_EOL . __( 'Order', BOOKACTI_PLUGIN_NAME )	. ' ' . $item[ 'order_id' ];
		
		if( $booking_type === 'single' ) {
			$refund_desc	.= PHP_EOL . __( 'Booking number', BOOKACTI_PLUGIN_NAME )	. ' ' . $booking_id;
		} else if( $booking_type === 'group' ) {
			$refund_desc	.= PHP_EOL . __( 'Booking group number', BOOKACTI_PLUGIN_NAME )	. ' ' . $booking_id;
		}
		
		$refund_desc	.= PHP_EOL . '     ' . $item[ 'name' ];
		
		// Deprecated data
		if( $booking_type === 'single' && isset( $item[ 'bookacti_event_start' ] ) && isset( $item[ 'bookacti_event_end' ] ) ) {
			$refund_desc	.= PHP_EOL . '     ' . bookacti_format_datetime( $item[ 'bookacti_event_start' ] );
			$refund_desc	.= PHP_EOL . '     ' . bookacti_format_datetime( $item[ 'bookacti_event_end' ] );
		}
		
		if( ! empty( $refund_message ) ) {
			$refund_desc .= PHP_EOL . PHP_EOL . __( 'User message:', BOOKACTI_PLUGIN_NAME ) . PHP_EOL . $refund_message;
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
		$data['coupon'] = array(
			'type'                         => 'fixed_cart',
			'amount'                       => $amount,
			'individual_use'               => false,
			'product_ids'                  => array(),
			'exclude_product_ids'          => array(),
			'usage_limit'                  => '1',
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
		$existing_coupon_code = wc_get_order_item_meta( $item[ 'id' ], 'bookacti_refund_coupon', true );
		if( $existing_coupon_code ) {
			$existing_coupon = WC()->api->WC_API_Coupons->get_coupon_by_code( $existing_coupon_code );

			$return_data = array( 'status' => 'success', 'coupon_amount' => wc_price( $existing_coupon['coupon']['amount'] ), 'coupon_code' => $existing_coupon['coupon']['code'], 'new_state' => 'refunded' );
			return $return_data;
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
		$code_template = apply_filters( 'bookacti_refund_coupon_code_template', 'R{user_id}N{refund_number}' );
		$code_template = str_replace( '{user_id}', '%1$d', $code_template );
		$code_template = str_replace( '{refund_number}', '%2$d', $code_template );
		do {
			$data['coupon']['code'] = sprintf( $code_template, $user_id, $i );
			$coupon = WC()->api->WC_API_Coupons->create_coupon( $data );
			$i++;
		}
		while( is_wp_error( $coupon ) && $coupon->get_error_code() === 'woocommerce_api_coupon_code_already_exists' );

		if( ! empty( $coupon ) && ! is_wp_error( $coupon ) ) {

			// Bind coupon to order item
			$code = apply_filters( 'bookacti_refund_coupon_code', $coupon[ 'coupon' ][ 'code' ], $data, $user_data, $item );
			wc_update_order_item_meta( $item[ 'id' ], 'bookacti_refund_coupon', $code );

			$return_data = array( 'status' => 'success', 'coupon_amount' => wc_price( $data['coupon']['amount'] ), 'coupon_code' => $code, 'new_state' => 'refunded' );

		} else if( is_wp_error( $coupon ) ) {
			$return_data = array( 'status' => 'failed', 'error' => $coupon, 'message' => $coupon->get_error_message() );
		}

		// Remove user cap to create coupon
		if( ! $user_basically_can_publish_shop_coupons )		{ $current_user->remove_cap( 'publish_shop_coupons' );	}
		if( ! $user_basically_can_read_private_shop_coupons )	{ $current_user->remove_cap( 'read_private_shop_coupons' );	}
		
		return $return_data;
	}

	
	/**
	 * Auto refund (for supported gateway)
	 * 
	 * @version 1.1.0
	 * 
	 * @param int $booking_id
	 * @param string $booking_type Determine if the given id is a booking id or a booking group id. Accepted values are 'single' or 'group'.
	 * @param string $refund_message
	 * @return array|false
	 */
	function bookacti_auto_refund_booking( $booking_id, $booking_type, $refund_message ) {

		// Include & load API classes
		if( ! class_exists( 'WC_API_Orders' ) ) {
			WC()->api->includes();
			WC()->api->register_resources( new WC_API_Server( '/' ) );
		}

		// Get variables
		if( $booking_type === 'single' ) {
			$order_id	= bookacti_get_booking_order_id( $booking_id );
			$item		= bookacti_get_order_item_by_booking_id( $booking_id );
		} else if( $booking_type === 'group' ) {
			$order_id	= bookacti_get_booking_group_order_id( $booking_id );
			$item		= bookacti_get_order_item_by_booking_group_id( $booking_id );
		}
		
		if( ! $item ) {
			return array( 'status' => 'failed', 'error' => 'no_order_item_found' );
		}
		
		$amount		= (float) $item[ 'line_total' ] + (float) $item[ 'line_tax' ];

		$reason		= __( 'Auto refund proceeded by user.', BOOKACTI_PLUGIN_NAME );
		if( $refund_message !== '' ) {
			$reason	.= PHP_EOL . __( 'User message:', BOOKACTI_PLUGIN_NAME ) . PHP_EOL . $refund_message;
		}

		$line_items	= array();
		$line_items[ $item[ 'id' ] ] = array(
			'qty'			=> $item[ 'qty' ],
			'refund_total'	=> $item[ 'line_total' ],
			'refund_tax'	=> $item[ 'line_tax' ]
		);

		$data = array();
		$data['order_refund'] = array(
			'amount'     => $amount,
			'reason'     => $reason,
			'order_id'   => $order_id,
			'line_items' => $line_items,
		);

		// Grant user cap to process refund
		$current_user		= wp_get_current_user();
		$user_basically_can = current_user_can( 'publish_shop_orders' );
		if( ! $user_basically_can ) {
			$current_user->add_cap( 'publish_shop_orders' );
		}

		// Process refund
		$refund = WC()->api->WC_API_Orders->create_order_refund( $order_id, $data, true );

		// Remove user cap to create coupon
		if( ! $user_basically_can ) { $current_user->remove_cap( 'publish_shop_orders' ); }

		if( is_array( $refund ) && ! is_wp_error( $refund ) ) {

			// Trigger notifications and status changes
			$order = wc_get_order( $order_id );
			if ( $order->get_remaining_refund_amount() > 0 || ( $order->has_free_item() && $order->get_remaining_refund_items() > 0 ) ) {
				do_action( 'woocommerce_order_partially_refunded', $order_id, $refund->id, $refund->id );
			} else {
				do_action( 'woocommerce_order_fully_refunded', $order_id, $refund->id );
				$order->update_status( apply_filters( 'woocommerce_order_fully_refunded_status', 'refunded', $order_id, $refund->id ) );
			}

			do_action( 'woocommerce_order_refunded', $order_id, $refund->id );

			$return_data = array( 'status' => 'success', 'new_state' => 'refunded' );
			return $return_data;

		} else if( is_wp_error( $refund ) ) {

			// Delete order refund
			$order_refunds = WC()->api->WC_API_Orders->get_order_refunds( $order_id );
			if( ! empty( $order_refunds['order_refunds'] ) ) {
				foreach( $order_refunds['order_refunds'] as $order_refund ) {
					if( $order_refund['line_items'][0]['refunded_item_id'] === $item['id'] ) {
						WC()->api->WC_API_Orders->delete_order_refund( $order_id, $order_refund['id'] );
					}
				}
			}

			// Return error
			return array( 'status' => 'failed', 'message' => $refund->get_error_message() );
		}

		return false;
	}

	
	/**
	 * Delete a refund and die
	 * 
	 * @param type $refund_id
	 */
	function bookacti_delete_refund_and_die( $refund_id ) {
		// Delete the refund
		if ( $refund_id && 'shop_order_refund' === get_post_type( $refund_id ) ) {
			$order_id = wp_get_post_parent_id( $refund_id );
			wc_delete_shop_order_transients( $order_id );
			wp_delete_post( $refund_id );
			do_action( 'woocommerce_refund_deleted', $refund_id, $order_id );
		}

		// Stop the script execution
		$message = __( 'Error occurs while trying to refund a booking.', BOOKACTI_PLUGIN_NAME );
		wp_die( $message );
	}




// SETTINGS
	
	function bookacti_settings_field_show_temporary_bookings_callback() { }
	function bookacti_settings_section_cart_callback() {}
	
	/**
	 * Setting for: Activate cart expiration
	 */
	function bookacti_settings_field_activate_cart_expiration_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
		
		// Display the field
		$name	= 'bookacti_cart_settings[is_cart_expiration_active]';
		$id		= 'is_cart_expiration_active';
		bookacti_onoffswitch( $name, $is_active, $id );
		
		// Display the tip
		$tip = __( "If you deactivate cart expiration, the temporary bookings made when a user add an activity to cart will become permanent, even if the user doesn't proceed to checkout.", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	
	/**
	 * Setting for: Activate per product expiration
	 */
	function bookacti_settings_field_per_product_expiration_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
		
		//Display the field
		$name	= 'bookacti_cart_settings[is_cart_expiration_per_product]';
		$id		= 'is_cart_expiration_per_product';
		
		bookacti_onoffswitch( $name, $is_active, $id );
		
		//Display the tip
		$tip = __( "The expiration time will be set for each product independantly, each with their own countdown before being removed from cart.", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	
	/**
	 * Setting for: Set amount of time before expiration
	 */
	function bookacti_settings_field_cart_timeout_callback() { 
		
		$timeout = bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
		
		//Display the field
		?>
		<input name='bookacti_cart_settings[cart_timeout]' 
			   id='cart_expiration_time' 
			   type='number' 
			   min='1'
			   value='<?php echo $timeout; ?>' />
		<?php
		
		//Display the tip
		$tip = __( 'Define the amount of time a user has before his cart gets empty.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}

	
	/**
	 * Setting for: Reset the countdown each time a change occur to cart
	 */
	function bookacti_settings_field_reset_cart_timeout_on_change_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
		
		//Display the field
		$name	= 'bookacti_cart_settings[reset_cart_timeout_on_change]';
		$id		= 'reset_cart_timeout_on_change';
		
		bookacti_onoffswitch( $name, $is_active, $id );
		
		//Display the tip
		$tip = __( 'The countdown will be reset each time a product is added, or when a product quantity is changed.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}




// GENERAL 

	/**
	 * Determines if user is shop manager
	 * 
	 * @param int $user_id
	 * @return boolean
	 */
	function bookacti_is_shop_manager( $user_id = 0 ) {

		if( $user_id === 0 ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( isset( $user->roles ) && in_array( 'shop_manager', $user->roles ) ) {
			return true;
		}
		return false;
	}