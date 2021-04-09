<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// PRODUCTS

/**
 * Get array of woocommerce products and product variations titles ordered by ids
 * @since 1.7.10
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param string $product_search
 * @return array
 */
function bookacti_get_products_titles( $search = '' ) {
	global $wpdb;
	
	$search_product_id = is_numeric( $search ) ? intval( $search ) : 0;
	
	// Try to retrieve the array from cache
	$cache_products_array = ! $search || $search_product_id ? wp_cache_get( 'products_titles', 'bookacti_wc' ) : array();
	if( $cache_products_array ) { 
		if( ! $search_product_id ) { return $cache_products_array; }
		else {
			foreach( $cache_products_array as $product_id => $product ) {
				if( $product_id === $search_product_id ) { return array( $product_id => $product ); }
				if( ! empty( $product[ 'variations' ][ $search_product_id ] ) ) { 
					return array( $product_id => array( 
						'text' => ! empty( $product[ 'title' ] ) ? $product[ 'title' ] : '',
						'variations' => array( $search_product_id => $product[ 'variations' ][ $search_product_id ] )
					));
				}
			}
		}
	}
	
	$query	= 'SELECT DISTINCT P.ID as id, P.post_title as title, P.post_excerpt as variations_title, P.post_type, T.name as product_type, P.post_parent as parent FROM ' . $wpdb->posts . ' as P '
			. ' LEFT JOIN ' . $wpdb->term_relationships . ' as TR ON TR.object_id = P.ID '
			. ' LEFT JOIN ' . $wpdb->term_taxonomy . ' as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id AND TT.taxonomy = "product_type" '
			. ' LEFT JOIN ' . $wpdb->terms . ' as T ON T.term_id = TT.term_id '
			. ' WHERE ( ( P.post_type = "product" AND T.name IS NOT NULL ) OR P.post_type = "product_variation" )'
			. ' AND P.post_status = "publish"';
	
	if( $search ) {
		$search_conditions = $search_product_id ? 'ID = %d' : 'P.post_title LIKE %s OR ( P.post_type = "product_variation" AND P.post_excerpt LIKE %s )';
		
		// Include the variations' parents so the user knows to what product it belongs
		$parent_ids_query = 'SELECT P.post_parent FROM ' . $wpdb->posts . ' as P WHERE P.post_type = "product_variation" AND P.post_status = "publish" AND ' . $search_conditions;
		
		$query .= ' AND ( ' . $search_conditions . ' OR ID IN ( ' . $parent_ids_query . ' ) )';
		
		$sanitized_search = $search_product_id ? $search_product_id : '%' . $wpdb->esc_like( $search ) . '%';
		$variables = $search_product_id ? array( $sanitized_search, $sanitized_search ) : array( $sanitized_search, $sanitized_search, $sanitized_search, $sanitized_search );
	
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$products = $wpdb->get_results( $query, OBJECT );

	$products_array = array();
	if( $products ) {
		foreach( $products as $product ) {
			if( $product->post_type !== 'product_variation' ){
				if( ! isset( $products_array[ $product->id ] ) ) { $products_array[ $product->id ] = array(); }
				$products_array[ $product->id ][ 'title' ] = $product->title;
				$products_array[ $product->id ][ 'type' ] = $product->product_type;
			}
			else {
				if( ! isset( $products_array[ $product->parent ][ 'variations' ] ) ) { $products_array[ $product->parent ][ 'variations' ] = array(); }
				$products_array[ $product->parent ][ 'variations' ][ $product->id ][ 'title' ] = $product->variations_title ? $product->variations_title : $product->id;
			}
		}
	}
	
	if( ! $search ) { wp_cache_set( 'products_titles', $products_array, 'bookacti_wc' ); }
	
	return $products_array;
}




// BOOKINGS

/**
 * Update all in cart bookings to "removed"
 * @since 1.9.0
 * @global wpdb $wpdb
 * @return int
 */
function bookacti_wc_update_in_cart_bookings_to_removed() {
	global $wpdb;
	
	$query_bookings	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
					. ' SET state = "removed", active = 0 '
					. ' WHERE state = "in_cart" ';
	$updated_bookings = $wpdb->query( $query_bookings );
	
	$query_booking_groups	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS
							. ' SET state = "removed", active = 0 '
							. ' WHERE state = "in_cart" ';
	$updated_booking_groups = $wpdb->query( $query_booking_groups );
	
	return intval( $updated_bookings ) + intval( $updated_booking_groups );
}


/**
 * Update in cart bookings to "removed" for a certain event
 * @since 1.9.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_wc_update_event_in_cart_bookings_to_removed( $event_id ) {
	global $wpdb;

	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' SET B.state = "removed", B.active = 0 '
			. ' WHERE B.event_id = %d '
			. ' AND B.state = "in_cart" ';
	
	$query		= $wpdb->prepare( $query, $event_id );
	$removed	= $wpdb->query( $query );

	return $removed;
}


/** 
 * Update in cart bookings to "removed" for a certain group of events (both booking groups and their bookings)
 * @since 1.9.0
 * @global wpdb $wpdb
 * @param int $event_group_id
 * @return int
 */
function bookacti_wc_update_group_of_events_in_cart_bookings_to_removed( $event_group_id ) {
	global $wpdb;

	// Single Bookings
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as G ON B.group_id = G.id '
			. ' SET B.state = "removed", B.active = 0 '
			. ' WHERE B.state = "in_cart" '
			. ' AND G.event_group_id = %d ';

	$query		= $wpdb->prepare( $query, $event_group_id );
	$removed1	= $wpdb->query( $query );
	
	// Booking Groups
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as B '
			. ' SET B.state = "removed", B.active = 0 '
			. ' WHERE B.state = "in_cart" '
			. ' AND B.event_group_id = %d ';

	$query		= $wpdb->prepare( $query, $event_group_id );
	$removed2	= $wpdb->query( $query );
	
	return intval( $removed1 ) + intval( $removed2 );
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
 * Reset bookings expiration dates that are currently in cart
 * @global wpdb $wpdb
 * @param int|string $user_id
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
 * @param int|string $user_id
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
 * Turn 'pending' bookings of an order to 'cancelled'
 * @since 1.9.0 (was bookacti_cancel_order_pending_bookings)
 * @global wpdb $wpdb
 * @param int $order_id
 * @param array $not_booking_ids
 * @param array $not_booking_group_ids
 * @return int|false
 */
function bookacti_cancel_order_remaining_bookings( $order_id, $not_booking_ids = array(), $not_booking_group_ids = array() ) {
	// Do not cancel bookings attached to the order
	$order = wc_get_order( $order_id );
	if( $order ) { 
		$items = $order->get_items();
		if( $items ) {
			foreach( $items as $item_id => $item ) {
				$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
				if( ! $order_item_bookings_ids ) { continue; }
				foreach( $order_item_bookings_ids as $order_item_booking_id ) {
					if( $order_item_booking_id[ 'type' ] === 'single' ) { $not_booking_ids[] = $order_item_booking_id[ 'id' ]; }
					else if( $order_item_booking_id[ 'type' ] === 'group' ) { $not_booking_group_ids[] = $order_item_booking_id[ 'id' ]; }
				}
			}
		}
	}
	
	global $wpdb;
	
	// Turn bookings state to 'cancelled'
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
			. ' SET order_id = NULL, state = "cancelled", active = 0 '
			. ' WHERE order_id = %d ';
	
	$variables = array( $order_id );
	
	if( $not_booking_ids ) {
		$query .= ' AND id NOT IN ( %d ';
		$array_count = count( $not_booking_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query  .= ', %d ';
			}
		}
		$query  .= ') ';
		$variables = array_merge( $variables, $not_booking_ids );
	}
	
	if( $not_booking_group_ids ) {
		$query .= ' AND group_id NOT IN ( %d ';
		$array_count = count( $not_booking_group_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query  .= ', %d ';
			}
		}
		$query  .= ') ';
		$variables = array_merge( $variables, $not_booking_group_ids );
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$nb_cancelled = $wpdb->query( $query );
	
	// Turn booking groups state to 'cancelled'
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS
			. ' SET order_id = NULL, state = "cancelled", active = 0 '
			. ' WHERE order_id = %d ';
	
	$variables = array( $order_id );
	
	if( $not_booking_group_ids ) {
		$query .= ' AND id NOT IN ( %d ';
		$array_count = count( $not_booking_group_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query  .= ', %d ';
			}
		}
		$query  .= ') ';
		$variables = array_merge( $variables, $not_booking_group_ids );
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	return $nb_cancelled;
}


/**
 * Deactivate expired bookings
 * @version	1.8.6
 * @global wpdb $wpdb
 * @return array|string|false
 */
function bookacti_deactivate_expired_bookings() {
	global $wpdb;
	
	$incompatible_collations = get_transient( 'bookacti_wc_incompatible_collations' );
	$last_error = $incompatible_collations ? 'Illegal mix of collations' : false;
	$expired_bookings = false;
	
	if( ! $last_error ) {
		// Get expired in cart bookings
		$query	= 'SELECT B.id, B.group_id '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
				. ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_sessions as S ON B.user_id = S.session_key '
				. ' WHERE B.state = "in_cart" '
				// Expired with Booking Activities expiration system
				. ' AND ( ( B.expiration_date <= UTC_TIMESTAMP() AND B.active = 1 )'
				// Expired with WC session expiration system
				. ' OR ( S.session_expiry IS NULL OR S.session_expiry <= UNIX_TIMESTAMP( UTC_TIMESTAMP() ) ) )';

		$expired_bookings = $wpdb->get_results( $query );
		$last_error = $wpdb->last_error;
	}
	
	// If WC's and BA's tables have incompatible collations, ignore WC session expiration
	if( $last_error && substr( $last_error, 0, 25 ) === 'Illegal mix of collations' ) {
		// Set a transient to avoid repeating the same error
		set_transient( 'bookacti_wc_incompatible_collations', 1 );
		
		$query	= 'SELECT B.id, B.group_id '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
				. ' WHERE B.state = "in_cart" '
				. ' AND B.expiration_date <= UTC_TIMESTAMP() '
				. ' AND B.active = 1 ';
		$expired_bookings = $wpdb->get_results( $query );
		$last_error = $wpdb->last_error;
	}
	
	if( ! $expired_bookings && $last_error ){ return $last_error; }
	if( $expired_bookings === false )		{ return false; }
	
	// Check if expired bookings belong to groups
	$expired_ids = array();
	$expired_group_ids = array();
	if( $expired_bookings ) {
		foreach( $expired_bookings as $expired_booking ) {
			$expired_ids[] = $expired_booking->id;
			if( $expired_booking->group_id && ! in_array( $expired_booking->group_id, $expired_group_ids, true ) ) {
				$expired_group_ids[] = $expired_booking->group_id;
			}
		}
	}
	
	$return = $expired_ids;
	
	// Turn bookings state to 'expired'
	if( $expired_ids ) {
		$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET active = 0, state = "expired" '
				. ' WHERE id IN ( %d';
		for( $i=1,$len=count($expired_ids); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$query	= $wpdb->prepare( $query, $expired_ids );
		$deactivated = $wpdb->query( $query );
		
		if( $deactivated === false ) { $return = false; }
		
		foreach( $expired_bookings as $expired_booking ) {
			if( ! $expired_booking->group_id ) {
				do_action( 'bookacti_booking_expired', $expired_booking->id );
			}
		}
	}
	
	// Turn booking groups state to 'expired'
	if( $return !== false && $expired_group_ids ) {
		$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
				. ' SET active = 0, state = "expired" '
				. ' WHERE id IN ( %d';
		for( $i=1,$len=count($expired_group_ids); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$query	= $wpdb->prepare( $query, $expired_group_ids );
		$deactivated = $wpdb->query( $query );
		
		if( $deactivated === false ) { $return = false; }
		
		foreach( $expired_group_ids as $expired_group_id ) {
			do_action( 'bookacti_booking_group_expired', $expired_group_id );
		}
	}
	
	return $return;
}


/**
 * Delete expired bookings few days after their expiration date
 * @since 1.7.4
 * @global wpdb $wpdb
 * @param int $delay
 * @return array|false
 */
function bookacti_delete_expired_bookings( $delay = 10 ) {
	global $wpdb;
	
	// Get expired booking and booking groups ids
	$query	= 'SELECT id, group_id '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS
			. ' WHERE expiration_date <= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY ) '
			. ' AND state IN ( "expired", "removed" ) '
			. ' AND active = 0 ';
	$query	= $wpdb->prepare( $query, $delay );
	$expired_bookings = $wpdb->get_results( $query );
	
	$expired_ids = array();
	$expired_group_ids = array();
	foreach( $expired_bookings as $expired_booking ) {
		$expired_ids[] = $expired_booking->id;
		if( $expired_booking->group_id && ! in_array( $expired_booking->group_id, $expired_group_ids, true ) ) {
			$expired_group_ids[] = $expired_booking->group_id;
		}
	}
		
	// Bookings
	$expired_ids = apply_filters( 'bookacti_expired_bookings_to_delete', $expired_ids );
	$return = $expired_ids;
	if( $expired_ids ) {
		$ids_placeholder_list = '%d';
		for( $i=1,$len=count($expired_ids); $i < $len; ++$i ) {
			$ids_placeholder_list .= ', %d';
		}
		
		// Delete expired bookings
		$query= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id IN( ' . $ids_placeholder_list . ' );';
		$query = $wpdb->prepare( $query, $expired_ids );
		$deleted = $wpdb->query( $query );
		
		if( $deleted === false ) { $return = false; }
		
		// Delete bookings meta
		bookacti_delete_metadata( 'booking', $expired_ids );

		do_action( 'bookacti_expired_bookings_deleted', $expired_ids );
	}
	
	// Booking groups
	$expired_group_ids = apply_filters( 'bookacti_expired_booking_groups_to_delete', $expired_group_ids );
	if( $return !== false && $expired_group_ids ) {
		$ids_placeholder_list = '%d';
		for( $i=1,$len=count($expired_group_ids); $i < $len; ++$i ) {
			$ids_placeholder_list .= ', %d';
		}
		
		// Delete expired booking groups
		$query = 'DELETE FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id IN ( ' . $ids_placeholder_list . ' );';
		$query = $wpdb->prepare( $query, $expired_group_ids );
		$deleted = $wpdb->query( $query );
		
		if( $deleted === false ) { $return = false; }
		
		// Delete booking groups meta
		bookacti_delete_metadata( 'booking_group', $expired_group_ids );
		
		do_action( 'bookacti_expired_booking_groups_deleted', $expired_group_ids );
	}
	
	return $return;
}


/**
 * Turn 'in_cart' bookings to 'removed'
 * @since 1.7.3 (was bookacti_cancel_in_cart_bookings)
 * @global wpdb $wpdb
 * @return int|false
 */
function bookacti_turn_in_cart_bookings_to_removed() {
	global $wpdb;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET active = 0, state = "removed" WHERE state = "in_cart";';
	$updated = $wpdb->query( $query );

	return $updated;
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
 * Delete all booking meta from a WC order item
 * @since 1.7.6
 * @version 1.9.0
 * @global wpdb $wpdb
 * @param int $item_id
 * @return int|false
 */
function bookacti_delete_order_item_booking_meta( $item_id ) {
	global $wpdb;
	
	$query	= 'DELETE FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta '
			. 'WHERE order_item_id = %d '
			. 'AND meta_key != %s '
			. 'AND meta_key LIKE %s';
	
	$variables = array( $item_id, 'bookacti_bookings', '%' . $wpdb->esc_like( 'bookacti' ) . '%' );
	
	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );
	
	return apply_filters( 'bookacti_delete_wc_order_item_booking_meta', $deleted, $item_id );
}