<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Get woocommerce products
 * @global wpdb $wpdb
 * @return array
 */
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

/**
 * Get booking event data to store in order item meta
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return object
 */
function bookacti_get_booking_event_data( $booking_id ){
	global $wpdb;

	$query		= 'SELECT B.id, B.event_id, B.event_start, B.event_end, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
					. ' LEFT JOIN (
							SELECT id, title, activity_id, template_id FROM ' . BOOKACTI_TABLE_EVENTS . '
						) as E ON B.event_id = E.id'
				. ' WHERE B.id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$booking	= $wpdb->get_row( $prep, OBJECT );

	return $booking;
}


/**
 * Get booking order id
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string|null
 */
function bookacti_get_booking_order_id( $booking_id ) {
	global $wpdb;

	$query		= 'SELECT order_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$order_id	= $wpdb->get_var( $prep );

	return $order_id;
}


/**
 * Get booking expiration date
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string|null
 */
function bookacti_get_booking_expiration_date( $booking_id ) {
	global $wpdb;

	$query				= 'SELECT expiration_date FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$query_prep			= $wpdb->prepare( $query, $booking_id );
	$expiration_date	= $wpdb->get_var( $query_prep );

	return $expiration_date;
}


/**
 * Check if the booking has expired
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param type $booking_id
 * @return boolean
 */
function bookacti_is_expired_booking( $booking_id ) {
	global $wpdb;

	$query_expired	= 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d ';
	$prep_expired	= $wpdb->prepare( $query_expired, $booking_id );
	$booking		= $wpdb->get_row( $prep_expired, OBJECT );
	
	if( ! $booking ) { return true; }
	
	$expired = false;
	if( $booking->state === 'in_cart' && ( strtotime( $booking->expiration_date ) <= time() ) ) { 
		$expired = true;
		bookacti_deactivate_expired_bookings();
	}
	if( is_null( $booking ) || intval( $booking->active ) === 0 ) {
		$expired = true;
	}
	return $expired;
}


/**
 * Reset bookings expiration dates that are currently in cart
 * @global wpdb $wpdb
 * @param string $user_id
 * @param array $booking_id_array
 * @param string $expiration_date
 * @return int|false
 */
function bookacti_update_in_cart_bookings_expiration_date( $user_id, $booking_id_array, $expiration_date ) {
	global $wpdb;

	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
			. ' SET expiration_date = %s '
			. ' WHERE user_id = %s '
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


/**
 * Get cart expiration of a specified user. Return null if there is no activity in cart
 * @global wpdb $wpdb
 * @param string $user_id
 * @return string|null
 */
function bookacti_get_cart_expiration_date_per_user( $user_id ) {
	global $wpdb;

	$query		= 'SELECT expiration_date FROM ' . BOOKACTI_TABLE_BOOKINGS 
				. ' WHERE user_id = %s ' 
				. ' AND ( state = "in_cart" OR state = "pending" ) ' 
				. ' ORDER BY expiration_date DESC ' 
				. ' LIMIT 1 ';
	$query_prep	= $wpdb->prepare( $query, $user_id );
	$exp_date	= $wpdb->get_var( $query_prep );

	return $exp_date;
}


/**
 * Change bookings state and fill user and order id
 * 
 * @version 1.5.6
 * @global wpdb $wpdb
 * @param int $user_id
 * @param int $order_id
 * @param array $booking_id_array
 * @param string $state
 * @param string $payment_status
 * @param array $states_in
 * @return string
 */
function bookacti_change_order_bookings_state( $user_id = NULL, $order_id = NULL, $booking_id_array = array(), $state = 'booked', $payment_status = NULL, $states_in = array() ) {

	global $wpdb;

	$response = array(); 
	
	if( empty( $booking_id_array ) || ! is_array( $booking_id_array ) ) { return false;	}
	
	//Init variables
	$response[ 'status' ]	= 'success';
	$response[ 'errors' ]	= array();
	
	if( $states_in === 'active' )	{ $states_in	= bookacti_get_active_booking_states(); }
	if( ! is_int( $user_id ) )		{ $user_id		= NULL; }
	if( ! is_int( $order_id ) )		{ $order_id		= NULL; }
	$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = %s, active = %d, ';

	$array_of_variables = array( 'state' => $state, 'active' => $active );

	// Update payment status
	if( $payment_status ){
		$query .= ' payment_status = CASE WHEN ( payment_status = "none" AND %s = "paid" ) THEN payment_status ELSE %s END ';
		if( $user_id || $order_id ) { $query .= ', '; }
		$array_of_variables[] = $payment_status;
		$array_of_variables[] = $payment_status;
	}

	// Update user id
	if( $user_id ){
		$query .= ' user_id = %s ';
		if( $order_id ) { $query .= ', '; }
		$array_of_variables[] = $user_id;
	}

	// Update order id
	if( $order_id ){
		$query .= ' order_id = %d ';
		$array_of_variables[] = $order_id;
	}

	// Complete the query with all the booking ids
	$query  .= ' WHERE id IN ( %d ';
	if( count( $booking_id_array ) >= 2 )  {
		for( $i = 0; $i < count( $booking_id_array ) - 1; $i++ ) {
			$query  .= ', %d ';
		}
	}
	$query  .= ') ';

	$array_of_variables = array_merge( $array_of_variables, $booking_id_array );

	if( $states_in && is_array( $states_in ) ) {
		$query  .= ' AND state IN ( ';
		$len = count( $states_in );
		for( $i=1; $i <= $len; ++$i ) {
			$query  .= '%s';
			if( $i < $len ) { $query  .= ', '; }
		}
		$query  .= ' ) ';
		$array_of_variables = array_merge( $array_of_variables, $states_in );
	}

	// Prepare and execute the query

	$query_prep = $wpdb->prepare( $query, $array_of_variables );
	$updated	= $wpdb->query( $query_prep );

	return $updated;
}


/**
 * Turn 'pending' bookings of an order to 'cancelled'
 * 
 * @version 1.3.0
 * 
 * @global wpdb $wpdb
 * @param int $order_id
 * @param array $not_booking_ids
 * @param array $not_booking_group_ids
 * @return array|0|false|null
 */
function bookacti_cancel_order_pending_bookings( $order_id, $not_booking_ids = array(), $not_booking_group_ids = array() ) {
	
	global $wpdb;
	
	// Get affected booking ids
	$query_updated_ids	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE order_id = %d AND state = "pending"';
	$prep_updated_ids	= $wpdb->prepare( $query_updated_ids, $order_id );
	$cancelled_bookings	= $wpdb->get_results( $prep_updated_ids, OBJECT );
	
	// Turn bookings state to 'cancelled'
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET state = "cancelled", active = 0 '
				. ' WHERE order_id = %d '
				. ' AND state = "pending" ';
	
	$variables = array( $order_id );
	
	if( $not_booking_ids ) {
		$query .= ' AND id NOT IN ( %s ';
		$array_count = count( $not_booking_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query  .= ', %s ';
			}
		}
		$query  .= ') ';
		$variables = array_merge( $variables, $not_booking_ids );
	}
	
	$query_prep	= $wpdb->prepare( $query, $variables );
	$cancelled	= $wpdb->query( $query_prep );
	
	
	// Turn booking groups state to 'cancelled'
	$query_cancel_groups	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
							. ' SET state = "cancelled", active = 0 '
							. ' WHERE order_id = %d '
							. ' AND state = "pending" ';
	
	$variables_group = array( $order_id );
	
	if( $not_booking_group_ids ) {
		$query_cancel_groups .= ' AND id NOT IN ( %s ';
		$array_count = count( $not_booking_group_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query_cancel_groups  .= ', %s ';
			}
		}
		$query_cancel_groups  .= ') ';
		$variables_group = array_merge( $variables_group, $not_booking_group_ids );
	}
	
	$prep_cancel_groups	= $wpdb->prepare( $query_cancel_groups, $variables_group );
	$wpdb->query( $prep_cancel_groups );
	
	$return = $cancelled;
	if( $cancelled ){
		$return = array();
		foreach( $cancelled_bookings as $cancelled_booking ) {
			$return[] = $cancelled_booking->id;
		}
	}
	
	return apply_filters( 'bookacti_order_pending_bookings_cancelled', $return );
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
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $user_id
 * @param string $customer_id
 * @return false|int
 */
function bookacti_update_bookings_user_id( $user_id, $customer_id ) {

	global $wpdb;
	// Single Bookings
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET user_id = %s '
				. ' WHERE user_id = %s '
				. ' AND expiration_date >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL 31 DAY ) ';
	$query_prep	= $wpdb->prepare( $query, $user_id, $customer_id );
	$updated1	= $wpdb->query( $query_prep );
	
	// Booking Groups
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' SET user_id = %s '
				. ' WHERE user_id = %s ';
	$query_prep	= $wpdb->prepare( $query, $user_id, $customer_id );
	$updated2	= $wpdb->query( $query_prep );
	
	if( $updated1 === false || $updated2 === false ) { return false; }
	
	return $updated1 + $updated2;
}


/**
 * Deactivate expired bookings
 *
 * @since	1.0.0
 * @version	1.2.0
 * 
 * @global type $wpdb
 * @return array|0|false
 */
function bookacti_deactivate_expired_bookings() {
	global $wpdb;
	
	$query_expired_ids	= 'SELECT id, group_id '
						. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' '
						. ' WHERE expiration_date <= UTC_TIMESTAMP() '
						. ' AND state = "in_cart" '
						. ' AND active = 1';
	$deactivated_bookings = $wpdb->get_results( $query_expired_ids, OBJECT );
	
	// Check if expired bookings belong to groups
	$expired_group_ids = array();
	foreach( $deactivated_bookings as $deactivated_booking ) {
		if( ! in_array( $deactivated_booking->group_id, $expired_group_ids, true ) ) {
			$expired_group_ids[] = $deactivated_booking->group_id;
		}
	}
	
	// Turn bookings state to 'expired'
	$query_deactivate_expired_bookings	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
										. ' SET active = 0, state = "expired" '
										. ' WHERE expiration_date <= UTC_TIMESTAMP() '
										. ' AND state = "in_cart" '
										. ' AND active = 1';
	$deactivated = $wpdb->query( $query_deactivate_expired_bookings );
	
	// Turn booking groups state to 'expired'
	if( ! empty( $expired_group_ids ) ) {
		$query_deactivate_expired_groups	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
											. ' SET active = 0, state = "expired" '
											. ' WHERE state = "in_cart" '
											. ' AND active = 1'
											. ' AND id IN ( ';
		
		$i = 1;
		foreach( $expired_group_ids as $expired_group_id ) {
			$query_deactivate_expired_groups .= '%d';
			if( $i < count( $expired_group_ids ) ) { 
				$query_deactivate_expired_groups .= ','; 
			}
			$i++;
		}
		
		$query_deactivate_expired_groups	.= ' ) ';
		
		$prep_deactivate_expired_groups	= $wpdb->prepare( $query_deactivate_expired_groups, $expired_group_ids );
		$wpdb->query( $prep_deactivate_expired_groups );
	}
	
	$return = $deactivated;
	if( $deactivated ){
		$return = array();
		foreach( $deactivated_bookings as $deactivated_booking ) {
			$return[] = $deactivated_booking->id;
			if( ! $deactivated_booking->group_id ) {
				do_action( 'bookacti_booking_expired', $deactivated_booking->id, 'expired', array() );
			}
		}
		foreach( $expired_group_ids as $expired_group_id ) {
			do_action( 'bookacti_booking_group_expired', $expired_group_id, 'expired', array() );
		}
	}
	
	return $return;
}


/**
 * Cancel all 'in_cart' bookings
 * @global wpdb $wpdb
 * @return int|false
 */
function bookacti_cancel_in_cart_bookings() {

	global $wpdb;

	$query_cancel_in_cart = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET active = 0, state = "cancelled" WHERE state = "in_cart" AND active = 1';
	$cancelled = $wpdb->query( $query_cancel_in_cart );

	return $cancelled;
}


/**
 * Get booking group events data to store in order item meta
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return array of object
 */
function bookacti_get_booking_group_events_data( $booking_group_id ) {
	global $wpdb;

	$query		= 'SELECT B.id, B.event_id, B.event_start, B.event_end, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
				. ' LEFT JOIN (
						SELECT id, title, activity_id, template_id FROM ' . BOOKACTI_TABLE_EVENTS . '
					) as E ON B.event_id = E.id'
				. ' WHERE B.group_id = %d '
				. ' ORDER BY B.event_start, B.event_id, E.activity_id DESC';
	$prep		= $wpdb->prepare( $query, $booking_group_id );
	$bookings	= $wpdb->get_results( $prep, OBJECT );

	return $bookings;
}


/**
 * Get booking group order id
 * 
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int|null
 */
function bookacti_get_booking_group_order_id( $booking_group_id ) {
	global $wpdb;

	$query		= 'SELECT order_id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_group_id );
	$order_id	= $wpdb->get_var( $prep );

	return $order_id;
}


/**
 * Get booking group expiration date
 * 
 * @since 1.1.0
 * 
 * @param int $booking_group_id
 * @return string|null
 */
function bookacti_get_booking_group_expiration_date( $booking_group_id ) {
	global $wpdb;
	
	$query				= 'SELECT MIN( expiration_date ) as expiration_date'
						. ' FROM ' . BOOKACTI_TABLE_BOOKINGS
						. ' WHERE group_id = %d ';
	$query_prep			= $wpdb->prepare( $query, $booking_group_id );
	$expiration_date	= $wpdb->get_var( $query_prep );
	
	return $expiration_date;
}