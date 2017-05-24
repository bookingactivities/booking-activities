<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// GET WOOCOMMERCE PRODUCTS
function bookacti_fetch_woo_products() {
	global $wpdb;

	$table_posts	= $wpdb->prefix . 'posts';
	$query_products = 'SELECT ID, post_title FROM ' . $table_posts . ' WHERE post_type = "product" AND post_status = "publish" ';
	$woo_products	= $wpdb->get_results( $query_products, OBJECT );

	$products_array = array();
	if( $woo_products ) {
		foreach( $woo_products as $woo_product ) {
			$is_activity = get_post_meta( $woo_product->ID, '_bookacti_is_activity', true );
			if( $is_activity === 'yes' ) {
				$thumb_id  = get_post_thumbnail_id( $woo_product->ID );
				$thumb_url = wp_get_attachment_image_src( $thumb_id, 'thumbnail', true );

				$product_array = array();
				$product_array['id']	= $woo_product->ID;
				$product_array['title'] = $woo_product->post_title;
				$product_array['image'] = $thumb_url[0];

				array_push( $products_array, $product_array );
			}
		}
	}

	return $products_array;
}



// BOOKINGS

// GET BOOKING ORDER ID
function bookacti_get_booking_order_id( $booking_id ) {
	global $wpdb;

	$query		= 'SELECT order_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$order_id	= $wpdb->get_var( $prep );

	return $order_id;
}


// GET BOOKING EXPIRATION DATE
function bookacti_get_booking_expiration_date( $booking_id ) {
	global $wpdb;

	$query				= 'SELECT expiration_date FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$query_prep			= $wpdb->prepare( $query, $booking_id );
	$expiration_date	= $wpdb->get_var( $query_prep );

	return $expiration_date;
}


// INSERT BOOKING IN CART
function bookacti_insert_booking_in_cart( $user_id, $event_id, $event_start, $event_end, $quantity, $state, $expiration_date = NULL ) {
	global $wpdb;
	$return_booking = array();
	
	//Check if the booking already exists (in that case, we will just update quantity and expiration)
	if( $state === 'in_cart' ) {
		$query_exists =	'SELECT id, quantity FROM ' . BOOKACTI_TABLE_BOOKINGS 
						. ' WHERE user_id = %d '
						. ' AND event_id = %d '
						. ' AND event_start = %s '
						. ' AND event_end = %s '
						. ' AND state = %s '
						. ' AND expiration_date > UTC_TIMESTAMP() '
						. ' AND active = 1 '
						. ' LIMIT 1 ';
		$prep_exists = $wpdb->prepare( $query_exists, $user_id, $event_id, $event_start, $event_end, $state );
		$booking = $wpdb->get_row( $prep_exists, OBJECT );
	}

	if( ! is_null( $booking ) ) {

		$new_qtt = intval( $booking->quantity ) + intval( $quantity );
		$active  = $new_qtt <= 0 ? 0 : -1;
		$reset_date = bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );

		if( $reset_date ) {
			$query_update	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
							. ' SET quantity = %d, expiration_date = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
							. ' WHERE id = %d ';
			$prep_update	= $wpdb->prepare( $query_update, $new_qtt, $expiration_date, $active, $booking->id );
		} else {
			$query_update	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
							. ' SET quantity = %d, active = IFNULL( NULLIF( %d, -1 ), active ) '
							. ' WHERE id = %d ';
			$prep_update	= $wpdb->prepare( $query_update, $new_qtt, $active, $booking->id );
		}

		$has_updated = $wpdb->query( $prep_update );

		if( $has_updated ) {
			$return_booking['action'] = 'updated';
			$return_booking['id'] = $booking->id;
			do_action( 'bookacti_booking_quantity_updated', $booking->id );
		}
	} else {
		$return_booking = bookacti_insert_booking( $user_id, $event_id, $event_start, $event_end, $quantity, $state, $expiration_date );
	}
	
	return $return_booking;
}


// Check if the booking has expired
function bookacti_is_expired_booking( $booking_id ) {
	global $wpdb;

	$query_expired	= 'SELECT * '
					. ' FROM ' . BOOKACTI_TABLE_BOOKINGS 
					. ' WHERE id = %d ';
	$prep_expired	= $wpdb->prepare( $query_expired, $booking_id );
	$booking		= $wpdb->get_row( $prep_expired, OBJECT );

	$expired = false;
	if( $booking->state === 'in_cart' && ( strtotime( $booking->expiration_date ) <= time() )  )
	{ 
		$expired = true;
		$deleted = bookacti_deactivate_expired_bookings();
		if( $deleted ) { 
			do_action( 'bookacti_booking_expired', $booking_id );
		}
	}
	if( is_null( $booking ) || intval( $booking->active ) === 0 ) {
		$expired = true;
	}
	return $expired;
}


// Reset bookings expiration dates that are currently in cart
function bookacti_update_in_cart_bookings_expiration_date( $user_id, $booking_id_array, $expiration_date ) {
	global $wpdb;

	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
			. ' SET expiration_date = %s '
			. ' WHERE user_id = %d '
			. ' AND expiration_date > UTC_TIMESTAMP() '
			. ' AND state = "in_cart" '
			. ' AND active = 1 ';

	if( ! empty( $booking_id_array ) ) {
		$query  .= ' AND ( id = %d ';
		if( count( $booking_id_array ) >= 2 )  {
			for( $i = 0; $i < count( $booking_id_array ) - 1; $i++ ) {
				$query  .= ' OR id = %d ';
			}
		}
		$query  .= ' ) ';
	}

	$first_variables = array( $expiration_date, $user_id );
	$variables_array = array_merge( $first_variables, $booking_id_array );

	$prep_query	= $wpdb->prepare( $query, $variables_array );
	$updated	= $wpdb->query( $prep_query );

	return $updated;
}


// Get cart expiration of a specified user. Return null if there is no activity in cart
function bookacti_get_cart_expiration_date_per_user( $user_id ) {
	global $wpdb;

	$query		= 'SELECT expiration_date FROM ' . BOOKACTI_TABLE_BOOKINGS 
				. ' WHERE user_id = %d ' 
				. ' AND ( state = "in_cart" OR state = "pending" ) ' 
				. ' ORDER BY expiration_date DESC ' 
				. ' LIMIT 1 ';
	$query_prep	= $wpdb->prepare( $query, $user_id );
	$exp_date	= $wpdb->get_var( $query_prep );

	return $exp_date;
}


//Validate bookings (from temporary to booked)
function bookacti_change_order_bookings_state( $user_id = NULL, $order_id = NULL, $booking_id_array = array(), $state = 'booked', $in_cart_or_pending_only = true ) {

	global $wpdb;

	$response				= array(); 
	$response[ 'status' ]	= 'success';
	$response[ 'errors' ]	= array();

	if( ! is_int( $user_id ) )	{ $user_id = NULL; }
	if( ! is_int( $order_id ) )	{ $order_id = NULL; }
	if( is_array( $booking_id_array ) && ! empty( $booking_id_array ) ) {

		//Init variables
		$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;

		$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = %s, active = %d, ';

		$array_of_variables = array( 'state' => $state, 'active' => $active );

		//check user id
		if( ! is_null( $user_id ) ){

			if( ! is_null( $order_id ) ) { $query .= ' user_id = %d, '; } 
									else { $query .= ' user_id = %d '; }
			$array_of_variables = array_merge( $array_of_variables, array( $user_id ) );

		} else {

			$response[ 'status' ] = $state . '_with_errors';
			array_push( $response[ 'errors' ], 'invalid_user_id' );
		}

		//Check order id
		if( ! is_null( $order_id ) ){

			$query .= ' order_id = %d ';
			$array_of_variables = array_merge( $array_of_variables, array( $order_id ) );

		} else {

			$response[ 'status' ] = $state . '_with_errors';
			array_push( $response[ 'errors' ], 'invalid_order_id' );
		}

		//Complete the query with all the booking ids
		$query  .= ' WHERE id IN ( %d ';
		if( count( $booking_id_array ) >= 2 )  {
			for( $i = 0; $i < count( $booking_id_array ) - 1; $i++ ) {
				$query  .= ', %d ';
			}
		}
		$query  .= ') ';

		if( $in_cart_or_pending_only ) {
			$query  .= ' AND state IN ( "in_cart", "pending" ) ';
		}
		
		//Prepare and execute the query
		$array_of_variables = array_merge( $array_of_variables, $booking_id_array );
		$query_prep = $wpdb->prepare( $query, $array_of_variables );
		$updated	= $wpdb->query( $query_prep );

		if( is_numeric( $updated ) ){

			if( $updated > 0 ) {
				foreach( $booking_id_array as $booking_id ) {
					if( is_numeric( $booking_id ) ){
						do_action( 'bookacti_booking_state_changed', $booking_id, $state, array() );
					}
				}
			}

			if( $updated > 0 && $updated < count( $booking_id_array ) ) {
				$response[ 'status' ] = $state . '_with_errors';
				array_push( $response[ 'errors' ], 'invalid_booking_ids' );
			}

		} else if( $updated === false ) {
			$response[ 'status' ] = 'failed';
			array_push( $response[ 'errors' ], 'update_failed' );
		}

		$response[ 'updated' ] = $updated;

	} else {

		$response[ 'status' ]	= 'failed';
		$response[ 'errors' ]	= array( 'no_booking_ids' );
	}

	return $response;
}


// Turn 'pending' bookings of an order to 'cancelled'
function bookacti_cancel_order_pending_bookings( $order_id ) {

	global $wpdb;

	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET state = "cancelled", active = 0 '
				. ' WHERE order_id = %d '
				. ' AND state = "pending" ';
	$query_prep	= $wpdb->prepare( $query, $order_id );
	$updated	= $wpdb->query( $query_prep );

	if( $updated ) {

		$query_updated_ids	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE state = "cancelled" AND order_id = %d';
		$prep_updated_ids	= $wpdb->prepare( $query_updated_ids, $order_id );
		$bookings_updated	= $wpdb->get_results( $prep_updated_ids, OBJECT );

		foreach( $bookings_updated as $booking ) {
			do_action( 'bookacti_booking_cancelled', $booking->id );
		}
	}

	return $updated;
}

/** 
 * Update all bookings of a customer_id with a new user_id
 * 
 * When not logged-in people add a booking ot cart or go to checkout, their booking and order are associated with their customer id
 * This changes customer id by user id for all bookings made whithin the 31 past days as they log in which correspond to WC cart cookie
 * We can't go further because customer ids are generated randomly, regardless of existing ones in database
 * Limiting to 31 days make it very improbable that two customers with the same id create an account or log in
 * 
 * @since 1.0.0
 */
function bookacti_update_bookings_user_id( $user_id, $customer_id ) {

	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET user_id = %d '
				. ' WHERE user_id = %s '
				. ' AND expiration_date >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL 31 DAY ) ';
	$query_prep	= $wpdb->prepare( $query, $user_id, $customer_id );
	$updated	= $wpdb->query( $query_prep );

	return $updated;
}


/**
 * Deactivate expired bookings
 *
 * @since	1.0.0
 * @version	1.0.6
 */
function bookacti_deactivate_expired_bookings() {
	global $wpdb;
	
	$query_expired_ids	= 'SELECT id '
						. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' '
						. ' WHERE expiration_date <= UTC_TIMESTAMP() '
						. ' AND state = "in_cart" '
						. ' AND active = 1';
	$deactivated_ids = $wpdb->get_results( $query_expired_ids, OBJECT );
	
	$query_deactivate_expired	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
								. ' SET active = 0, state = "expired" '
								. ' WHERE expiration_date <= UTC_TIMESTAMP() '
								. ' AND state = "in_cart" '
								. ' AND active = 1';
	$deactivated = $wpdb->query( $query_deactivate_expired );
	
	$return = false;
	if( $deactivated ){
		foreach( $deactivated_ids as $deactivated_id ) {
			$return[] = $deactivated_id->id;
		}
	}
	
	return $return;
}


// Cancel all 'in_cart' bookings
function bookacti_cancel_in_cart_bookings() {

	global $wpdb;

	$query_cancel_in_cart = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET active = 0, state = "cancelled" WHERE state = "in_cart" AND active = 1';
	$cancelled = $wpdb->query( $query_cancel_in_cart );

	return $cancelled;
}