<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Book an event
 * 
 * @version 1.3.1
 * 
 * @global wpdb $wpdb
 * @param int $user_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param int $quantity
 * @param string $state
 * @param string $payment_status
 * @param string $expiration_date
 * @param int $booking_group_id
 * @return int|null
 */
function bookacti_insert_booking( $user_id, $event_id, $event_start, $event_end, $quantity, $state, $payment_status, $expiration_date = NULL, $booking_group_id = NULL ) {
	global $wpdb;
	
	$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
	
	$creation_date = substr( date( 'c' ), 0, 19 );
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_BOOKINGS 
			. ' ( group_id, event_id, user_id, event_start, event_end, quantity, state, payment_status, creation_date, expiration_date, active ) ' 
			. ' VALUES ( NULLIF( %d, 0), %d, %s, %s, %s, %d, %s, %s, %s, NULLIF( %s, ""), %d )';
	
	$variables = array( 
		is_numeric( $booking_group_id ) ? intval( $booking_group_id ) : 0,
		$event_id,
		$user_id,
		$event_start,
		$event_end,
		$quantity,
		$state,
		$payment_status,
		$creation_date,
		$expiration_date,
		$active
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	$booking_id = $wpdb->insert_id;
	
	if( $booking_id !== false ) {
		do_action( 'bookacti_booking_inserted', $booking_id );
	}
	
	return $booking_id;
}


/**
 * Check if a booking exists and return its id
 * 
 * @version 1.3.0
 * 
 * @global wpdb $wpdb
 * @param int $user_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $state
 * @param int $booking_group_id
 * @return int
 */
function bookacti_booking_exists( $user_id, $event_id, $event_start, $event_end, $state, $booking_group_id = NULL ) {
	global $wpdb;
	
	$query ='SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS 
			. ' WHERE user_id = %s '
			. ' AND event_id = %d '
			. ' AND event_start = %s '
			. ' AND event_end = %s '
			. ' AND state = %s '
			. ' AND ( expiration_date IS NULL OR expiration_date > UTC_TIMESTAMP() ) ';
	
	$variables = array( $user_id, $event_id, $event_start, $event_end, $state );
	
	if( $booking_group_id !== NULL ) {
		if( $booking_group_id === 0 ) {
			$query  .= ' AND group_id IS NULL ';
		} else if( is_int( $booking_group_id ) && $booking_group_id > 0 ) {
			$query  .= ' AND group_id = %d ';
			$variables[] =  $booking_group_id;
		}
	}
	
	$query .= ' LIMIT 1 ';
	
	$query = $wpdb->prepare( $query, $variables );
	$booking_id = $wpdb->get_var( $query );
	
	if( ! is_null( $booking_id ) ) {
		return $booking_id;
	}
	
	return 0;
}


/**
 * Update booking quantity if possible
 * 
 * @version 1.2.0
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int $new_quantity
 * @param string $expiration_date
 * @param string $context
 * @return array
 */
function bookacti_update_booking_quantity( $booking_id, $new_quantity, $expiration_date = '', $context = 'frontend' ) {
	global $wpdb;
	
	$booking = bookacti_get_booking_by_id( $booking_id );
	
	$old_state = $booking->state;
	
	$new_quantity = intval( $new_quantity );
	$old_quantity = intval( $booking->quantity );
	$availability = intval( bookacti_get_event_availability( $booking->event_id, $booking->event_start, $booking->event_end ) );

	$return_array = array();
	$return_array['status'] = '';

	// If the updated booking is active, you must count the quantity already booked by this user in the total quantity available for him
	if( $booking->active ) {
		$new_availability = $availability + $old_quantity - $new_quantity;
		$return_array['availability'] = $availability + $old_quantity;
	} else {
		$new_availability = $availability - $new_quantity;
		$return_array['availability'] = $availability;
	}

	if( intval( $new_availability ) >= 0 ) {
		
		// Prepare variables
		$data = apply_filters( 'bookacti_update_booking_quantity_data', array( 
			'quantity' => $new_quantity,
			'state' => NULL,
			'active' => -1,
			'expiration_date' => $expiration_date,
			'context' => $context
		), $booking );
		
		$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET quantity = %d, '
				. ' state = IFNULL( NULLIF( %s, "" ), state ), '
				. ' active = IFNULL( NULLIF( %d, -1 ), active ), '
				. ' expiration_date = IFNULL( NULLIF( %s, "" ), expiration_date ) '
				. ' WHERE id = %d ';

		$prep_query	= $wpdb->prepare( $query, $data[ 'quantity' ], $data[ 'state' ], $data[ 'active' ], $data[ 'expiration_date' ], $booking_id );
		$updated	= $wpdb->query( $prep_query );

		if( $updated > 0 ){
			
			// If state has changed
			if( ! $booking->group_id && $data[ 'state' ] && is_string( $data[ 'state' ] ) && $old_state !== $data[ 'state' ] ) {
				$is_admin = $context === 'admin' ? true : false;
				do_action( 'bookacti_booking_state_changed', $booking_id, $data[ 'state' ], array( 'is_admin' => $is_admin ) );
			}
			
			// If quantity has changed
			if( intval( $data[ 'quantity' ] ) > 0 && intval( $data[ 'quantity' ] ) !== $old_quantity ) {
				do_action( 'bookacti_booking_quantity_updated', $booking_id, intval( $data[ 'quantity' ] ), $old_quantity );
			}

			$return_array['status'] = 'success';
		} else if( $updated === 0 ) {
			$return_array['status'] = 'no_change';
		} else {
			$return_array['status'] = 'failed';
		}

	} else {
		
		$return_array['status'] = 'failed';
		
		if( $return_array['availability'] > 0 ) {
			$return_array['error'] = 'qty_sup_to_avail';
		} else {
			$return_array['error'] = 'no_availability';
		}
	}

	return $return_array;
}
	

/**
 * Get bookings according to filters
 * 
 * @version 1.3.1
 * @global wpdb $wpdb
 * @param array $filters
 * @param boolean $group_by_booking_groups Whether to retrieve only the first event of groups
 * @return array
 */
function bookacti_get_bookings( $filters, $group_by_booking_groups = false ) {
	global $wpdb;
	
	$bookings_query = ' SELECT DISTINCT B.*, E.title as event_title, A.id as activity_id, A.title as activity_title, T.id as template_id, T.title as template_title ' 
					. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
					
	if( $filters[ 'event_group_id' ] ) {
		$bookings_query		.= ', ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ';
	}
			
	$bookings_query .= ' WHERE B.event_id = E.id '
					. ' AND B.event_id = E.id '
					. ' AND E.activity_id = A.id '
					. ' AND E.template_id = T.id ';
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$bookings_query  .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'from' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'to' ] ) {
		$bookings_query  .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
				UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
			)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'to' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'status' ] ) {
		$bookings_query .= ' AND B.state IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %s ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'templates' ] ) {
		$bookings_query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $filters[ 'templates' ] );
		if( $array_count >= 2 )  {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %d ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'templates' ] );
	}
	
	if( $filters[ 'activities' ] ) {
		$bookings_query .= ' AND E.activity_id IN ( %d ';
		$array_count = count( $filters[ 'activities' ] );
		if( $array_count >= 2 )  {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %d ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'activities' ] );
	}
	
	if( $filters[ 'booking_id' ] ) {
		$bookings_query .=	' AND B.id = %d ';
		$variables[] = $filters[ 'booking_id' ];
	}
	
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
		$bookings_query  .= ' AND B.group_id = %d ';
		$variables[] =  intval( $filters[ 'booking_group_id' ] );
	} else if( $filters[ 'booking_group_id' ] === 'none' ) {
		$bookings_query  .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'event_group_id' ] ) {
		$bookings_query .=	' AND B.group_id = BG.id '
						.	' AND BG.event_group_id = %d ';
		$variables[] = $filters[ 'event_group_id' ];
	}
	
	if( $filters[ 'event_id' ] ) {
		$bookings_query .= ' AND B.event_id = %d ';
		$variables[] = $filters[ 'event_id' ];
	}

	if( $filters[ 'event_start' ] ) {
		$bookings_query .= ' AND B.event_start = %s ';
		$variables[] = $filters[ 'event_start' ];
	}

	if( $filters[ 'event_end' ] ) {
		$bookings_query .= ' AND B.event_end = %s ';
		$variables[] = $filters[ 'event_end' ];
	}
	
	if( $filters[ 'user_id' ] ) {
		$bookings_query .= ' AND B.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $group_by_booking_groups ) {
		$bookings_query  .= ' GROUP BY IFNULL( B.group_id , B.id ) ';
	}
	
	if( $filters[ 'order_by' ] ) {
		$bookings_query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$bookings_query  .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $bookings_query  .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $bookings_query  .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$bookings_query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$bookings_query .= '%d';
			if( $filters[ 'per_page' ] ) { $bookings_query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$bookings_query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) {
		$bookings_query = $wpdb->prepare( $bookings_query, $variables );
	}
	
	$bookings = $wpdb->get_results( $bookings_query, OBJECT );
	
	return $bookings;
}


/**
 * Get the total amount of bookings according to filters
 * 
 * @since 1.3.1
 * @global wpdb $wpdb
 * @param array $filters
 * @param boolean $group_by_booking_groups Whether to count a group as one booking
 * @return int
 */
function bookacti_get_number_of_booking_rows( $filters = array(), $group_by_booking_groups = false ) {
	global $wpdb;
	
	$bookings_query	= ' SELECT SUM( list_items_count ) FROM ( '
						. ' SELECT COUNT( DISTINCT B.id ) as list_items_count' 
						. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
	
	if( $filters[ 'event_group_id' ] ) {
		$bookings_query		.= ', ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ';
	}
	
	$bookings_query		.= ' WHERE B.event_id = E.id '
						. ' AND B.event_id = E.id '
						. ' AND E.activity_id = A.id '
						. ' AND E.template_id = T.id ';
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$bookings_query  .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'from' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'to' ] ) {
		$bookings_query  .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
				UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
			)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'to' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'status' ] ) {
		$bookings_query .= ' AND B.state IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %s ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'templates' ] ) {
		$bookings_query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $filters[ 'templates' ] );
		if( $array_count >= 2 )  {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %d ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'templates' ] );
	}
	
	if( $filters[ 'activities' ] ) {
		$bookings_query .= ' AND E.activity_id IN ( %d ';
		$array_count = count( $filters[ 'activities' ] );
		if( $array_count >= 2 )  {
			for( $i=1; $i<$array_count; ++$i ) {
				$bookings_query  .= ', %d ';
			}
		}
		$bookings_query  .= ') ';
		$variables = array_merge( $variables, $filters[ 'activities' ] );
	}
	
	if( $filters[ 'booking_id' ] ) {
		$bookings_query .=	' AND B.id = %d ';
		$variables[] = $filters[ 'booking_id' ];
	}
	
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
		$bookings_query  .= ' AND B.group_id = %d ';
		$variables[] =  intval( $filters[ 'booking_group_id' ] );
	} else if( $filters[ 'booking_group_id' ] === 'none' ) {
		$bookings_query  .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'event_group_id' ] ) {
		$bookings_query .=	' AND B.group_id = BG.id '
						.	' AND BG.event_group_id = %d ';
		$variables[] = $filters[ 'event_group_id' ];
	}
	
	if( $filters[ 'event_id' ] ) {
		$bookings_query .= ' AND B.event_id = %d ';
		$variables[] = $filters[ 'event_id' ];
	}

	if( $filters[ 'event_start' ] ) {
		$bookings_query .= ' AND B.event_start = %s ';
		$variables[] = $filters[ 'event_start' ];
	}

	if( $filters[ 'event_end' ] ) {
		$bookings_query .= ' AND B.event_end = %s ';
		$variables[] = $filters[ 'event_end' ];
	}
	
	if( $filters[ 'user_id' ] ) {
		$bookings_query .= ' AND B.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	// Whether to count bookings of the same groups as one item
	if( $group_by_booking_groups ) {
		$bookings_query  .= ' GROUP BY IFNULL( B.group_id , B.id ) ';
	}
	
	$bookings_query  .= ' ) as C ';
	
	if( $variables ) {
		$bookings_query = $wpdb->prepare( $bookings_query, $variables );
	}

	$count = $wpdb->get_var( $bookings_query );

	return $count ? $count : 0;
}


/**
 * Get number of booking of a specific event or a specific occurrence
 * 
 * @version 1.3.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start Optional. Used for an ccurrence of a repeated event.
 * @param string $event_end Optional. Used for an occurrence of a repeated event.
 * @param array $include_states Optional. Count bookings with desired inactive states in total.
 * @return int
 */
function bookacti_get_number_of_bookings( $event_id, $event_start = NULL, $event_end = NULL, $include_states = array() ) {
	global $wpdb;

	$query = 'SELECT SUM(quantity) FROM ' . BOOKACTI_TABLE_BOOKINGS
					. ' WHERE event_id = %d';
	
	$variables = array( $event_id );
	
	if( $event_start ) {
		$query .= ' AND event_start = %s';
		$variables[] = $event_start;
	}
	
	if( $event_end ) {
		$query .= ' AND event_end = %s';
		$variables[] = $event_end;
	}
	
	if( $include_states ) {
		$query .= ' AND ( active = 1 OR state IN ( %s';
		for( $i=1, $len=count($include_states); $i < $len; ++$i ) {
			$query  .= ', %s';
		}
		$query .= ') )';

		$variables = array_merge( $variables, $include_states );
	} else {
		$query .= ' AND active = 1 ';
	}
	
	$query		= $wpdb->prepare( $query, $variables );
	$bookings	= $wpdb->get_var( $query );

	if( is_null( $bookings ) ) { $bookings = 0; }

	return $bookings;
}


/**
 * Get number of bookings ordered by events
 * 
 * @version 1.3.0
 * 
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $event_ids
 * @param array $user_ids
 * @return array
 */
function bookacti_get_number_of_bookings_by_events( $template_ids = array(), $event_ids = array(), $user_ids = array() ) {
	global $wpdb;

	// Convert numeric to array
	if( ! is_array( $template_ids ) ){
		$template_id = intval( $template_ids );
		$template_ids = array();
		if( $template_id ) { $template_ids[] = $template_id; }
	}
	if( ! is_array( $event_ids ) ){
		$event_id = intval( $event_ids );
		$event_ids = array();
		if( $event_id ) { $event_ids[] = $event_id; }
	}
	if( ! is_array( $user_ids ) ){
		$user_id = intval( $user_ids );
		$user_ids = array();
		if( $user_id ) { $user_ids[] = $user_id; }
	}

	$bookings_query = 'SELECT B.event_id, B.event_start, B.event_end, SUM( B.quantity ) as quantity '
					. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE B.active = 1 '
					. ' AND B.event_id = E.id ';
	
	$variables = array();
	
	// Filter by template
	if( $template_ids ) {
		$bookings_query	.= ' AND E.template_id IN ( ';
		
		$i = 1;
		foreach( $template_ids as $template_id ){
			$bookings_query .= ' %d';
			if( $i < count( $template_ids ) ) { $bookings_query .= ','; }
			++$i;
		}
		
		$bookings_query	.= ' ) ';
		
		$variables = $template_ids;
	}
	
	// Filter by event
	if( $event_ids ) {
		$bookings_query	.= ' AND B.event_id IN ( ';
		
		$i = 1;
		foreach( $event_ids as $event_id ){
			$bookings_query .= ' %d';
			if( $i < count( $event_ids ) ) { $bookings_query .= ','; }
			++$i;
		}
		
		$bookings_query	.= ' ) ';
		
		$variables = $event_ids;
	}
	
	// Filter by user
	if( $user_ids ) {
		$bookings_query	.= ' AND B.user_id IN ( ';
		
		$i = 1;
		foreach( $user_ids as $user_id ){
			$bookings_query .= ' %d';
			if( $i < count( $user_ids ) ) { $bookings_query .= ','; }
			++$i;
		}
		
		$bookings_query	.= ' ) ';
		
		$variables = $user_ids;
	}
	
	$bookings_query .= ' GROUP BY B.event_id, B.event_start, B.event_end '
					. ' ORDER BY B.event_id, B.event_start, B.event_end ';
	
	if( $variables ) {
		$bookings_query = $wpdb->prepare( $bookings_query, $variables );
	}
	
	$bookings = $wpdb->get_results( $bookings_query, ARRAY_A );
	
	// Order the array by event id
	$return_array = array();
	foreach( $bookings as $booking ) {
		$event_id = $booking[ 'event_id' ];
		unset( $booking[ 'event_id' ] );
		if( ! isset( $return_array[ $event_id ] ) ) {
			$return_array[ $event_id ] = array();
		}
		$return_array[ $event_id ][] = $booking;
	}
	
	return $return_array;
}


/**
 * Check if a booking is active
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string|null
 */
function bookacti_is_booking_active( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT active FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$active	= $wpdb->get_var( $prep );

	return $active;
}


/**
 * Get activity by booking
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return object|null
 */
function bookacti_get_activity_by_booking_id( $booking_id ) {
	global $wpdb;

	$query		= 'SELECT A.* FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' WHERE B.event_id = E.id '
				. ' AND E.activity_id = A.id '
				. ' AND B.id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$activity	= $wpdb->get_row( $prep, OBJECT );

	return $activity;
}


/**
 * Get template by booking
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return object|null
 */
function bookacti_get_template_by_booking_id( $booking_id ) {
	global $wpdb;

	$query		= 'SELECT T.* FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' WHERE B.event_id = E.id '
				. ' AND E.template_id = T.id '
				. ' AND B.id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$template	= $wpdb->get_row( $prep, OBJECT );

	return $template;
}


/**
 * Get booking by id
 * 
 * @version 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return object|null
 */
function bookacti_get_booking_by_id( $booking_id ) {
	global $wpdb;

	$query		= 'SELECT B.*, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
					. ' LEFT JOIN (
							SELECT id, title, activity_id, template_id FROM ' . BOOKACTI_TABLE_EVENTS . '
						) as E ON B.event_id = E.id'
				. ' WHERE B.id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$booking	= $wpdb->get_row( $prep, OBJECT );

	return $booking;
}


/**
 * Get booking data
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return array
 */
function bookacti_get_booking_data( $booking_id ) {
	global $wpdb;
	
	$query	= 'SELECT B.quantity, E.id as event_id, E.template_id, E.activity_id, B.group_id as booking_group '
			. ' FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' WHERE E.activity_id = A.id '
			. ' AND B.event_id = E.id '
			. ' AND B.id = %d ';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$booking_system_info = $wpdb->get_row( $prep, ARRAY_A );
	
	$booking_system_info[ 'event_settings' ]	= bookacti_get_metadata( 'event', $booking_system_info[ 'event_id' ] );
	$booking_system_info[ 'activity_settings' ]	= bookacti_get_metadata( 'activity', $booking_system_info[ 'activity_id' ] );
	
	return $booking_system_info;
}


/**
 * Get all user's bookings
 * 
 * @global wpdb $wpdb
 * @param int $user_id
 * @return object|null
 */
function bookacti_get_bookings_by_user_id( $user_id = null ) {
	
	$user_id = $user_id ? $user_id : get_current_user_id();
	
	global $wpdb;

	$query		= 'SELECT B.*, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
				. ' LEFT JOIN (
						SELECT id, title, activity_id, template_id FROM ' . BOOKACTI_TABLE_EVENTS . '
					) as E ON B.event_id = E.id'
				. ' WHERE B.user_id = %s ORDER BY id DESC';
	$prep		= $wpdb->prepare( $query, $user_id );
	$booking	= $wpdb->get_results( $prep, OBJECT );

	return $booking;
}


/**
 * Get booking state
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string
 */
function bookacti_get_booking_state( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT state FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$state	= $wpdb->get_var( $prep );

	return $state;
}


/**
 * Get booking user id
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string|null
 */
function bookacti_get_booking_owner( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT user_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$owner	= $wpdb->get_var( $prep );

	return $owner;
}


/**
 * Cancel a booking
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return int|false
 */
function bookacti_cancel_booking( $booking_id ) {
	global $wpdb;

	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = "cancelled", active = 0 WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$cancelled	= $wpdb->query( $prep );

	return $cancelled;
}


/**
 * Update booking state
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param string $state
 * @param int|string $active
 * @return int|false
 */
function bookacti_update_booking_state( $booking_id, $state, $active = 'auto' ) {
	
	global $wpdb;
	
	if( $active === 'auto' ) {
		$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
	}
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
				. ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $state, $active, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


/**
 * Update booking payment status
 * 
 * @since 1.3.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param string $status
 * @return int|false
 */
function bookacti_update_booking_payment_status( $booking_id, $status ) {
	
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET payment_status = %s '
				. ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $status, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


/**
 * Forced update of booking quantity (do not check availability)
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int $quantity
 * @return int|false
 */
function bookacti_force_update_booking_quantity( $booking_id, $quantity ) {
	
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET quantity = %d WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $quantity, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


/**
 * Reschedule a booking
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return int|false
 */
function bookacti_reschedule_booking( $booking_id, $event_id, $event_start, $event_end ) {
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET event_id = %d, event_start = %s, event_end = %s WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $event_id, $event_start, $event_end, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}




// BOOKING GROUPS

	/**
	 * Insert a booking group
	 * 
	 * @since 1.1.0
	 * @version 1.3.0
	 * @global wpdb $wpdb
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param string $state
	 * @param string $payment_status
	 * @return int
	 */
	function bookacti_insert_booking_group( $user_id, $event_group_id, $state, $payment_status ) {
		global $wpdb;

		$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;

		$wpdb->insert( 
			BOOKACTI_TABLE_BOOKING_GROUPS, 
			array( 
				'event_group_id'	=> $event_group_id,
				'user_id'			=> $user_id,
				'state'				=> $state,
				'payment_status'	=> $payment_status,
				'active'			=> $active
			),
			array( '%d', '%d', '%s', '%s', '%d' )
		);

		$booking_group_id = $wpdb->insert_id;

		if( $booking_group_id !== false ) {
			do_action( 'bookacti_booking_group_inserted', $booking_group_id );
		}

		return $booking_group_id;
	}
	
	
	/**
	 * Update booking group state
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param 0|1|'auto' $active
	 * @param boolean $update_bookings Whether to updates bookings state of the group.
	 * @param boolean $same_state Whether bookings state must be the same as the group to be updated.
	 * @return int|boolean|null
	 */
	function bookacti_update_booking_group_state( $booking_group_id, $state, $active = 'auto', $update_bookings = false, $same_state = false ) {

		global $wpdb;

		if( $active === 'auto' ) {
			$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		}
		
		if( $same_state ) {
			$old_state = bookacti_get_booking_group_state( $booking_group_id );
		}

		$query1		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
					. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
					. ' WHERE id = %d';
		$prep1		= $wpdb->prepare( $query1, $state, $active, $booking_group_id );
		$updated1	= $wpdb->query( $prep1 );
		
		$updated = $updated1;
		
		if( $update_bookings ) {
			
			$where_state = false;
			if( $same_state && ! empty( $old_state ) ) {
				$where_state = $old_state;
			}
			
			$updated2 = bookacti_update_booking_group_bookings_state( $booking_group_id, $state, $active, $where_state );
			
			if( is_int( $updated1 ) && is_int( $updated2 ) ) {
				$updated = $updated1 + $updated2;
			} else {
				return false;
			}
		}
		
		return $updated;
	}
	
	
	/**
	 * Update booking group state
	 * 
	 * @since 1.3.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param 0|1|'auto' $active
	 * @param boolean $update_bookings Whether to updates bookings state of the group.
	 * @param boolean $same_status Whether bookings payment status must be the same as the group to be updated.
	 * @return int|boolean|null
	 */
	function bookacti_update_booking_group_payment_status( $booking_group_id, $status, $update_bookings = false, $same_status = false ) {

		global $wpdb;

		if( $same_status ) {
			$old_status = bookacti_get_booking_group_payment_status( $booking_group_id );
		}

		$query1		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
					. ' SET payment_status = %s '
					. ' WHERE id = %d';
		$prep1		= $wpdb->prepare( $query1, $status, $booking_group_id );
		$updated1	= $wpdb->query( $prep1 );
		
		$updated = $updated1;
		
		if( $update_bookings ) {
			
			$where_status = false;
			if( $same_status && $old_status ) {
				$where_status = $old_status;
			}
			
			$updated2 = bookacti_update_booking_group_bookings_payment_status( $booking_group_id, $status, $where_status );
			
			if( is_int( $updated1 ) && is_int( $updated2 ) ) {
				$updated = $updated1 + $updated2;
			} else {
				return false;
			}
		}
		
		return $updated;
	}
	
	
	/**
	 * Update booking group bookings state
	 * 
	 * @since 1.1.0
	 * @version 1.2.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param boolean|'auto' $active
	 * @param string|false $where_state
	 * @return type
	 */
	function bookacti_update_booking_group_bookings_state( $booking_group_id, $state, $active = 'auto', $where_state = false ) {

		global $wpdb;

		if( $active === 'auto' ) {
			$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		}
		
		// Get booking ids
		$query_bookings	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS 
						. ' WHERE group_id = %d ';
		
		$variables_bookings = array( $booking_group_id );
		
		if( ! empty( $where_state ) ) {
			$query_bookings .= ' AND state = %s ';
			$variables_bookings[] = $where_state;
		}
		
		$prep_bookings	= $wpdb->prepare( $query_bookings, $variables_bookings );
		$bookings		= $wpdb->get_results( $prep_bookings, OBJECT );
		
		if( empty( $bookings ) ) {
			return 0;
		}
		
		// Change bundled bookings state
		$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
					. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
					. ' WHERE group_id = %d';

		$variables_array = array( $state, $active, $booking_group_id );
		
		if( ! empty( $where_state ) ) {
			$query	.= ' AND state = %s ';
			$variables_array[] = $where_state;
		}

		$prep		= $wpdb->prepare( $query, $variables_array );
		$updated	= $wpdb->query( $prep );
		
		return $updated;
	}
	
	
	/**
	 * Update booking group bookings payment status
	 * 
	 * @since 1.3.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param string|false $where_state
	 * @return type
	 */
	function bookacti_update_booking_group_bookings_payment_status( $booking_group_id, $state, $where_state = false ) {

		global $wpdb;

		// Get booking ids
		$query_bookings	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS 
						. ' WHERE group_id = %d ';
		
		$variables_bookings = array( $booking_group_id );
		
		if( ! empty( $where_state ) ) {
			$query_bookings .= ' AND payment_status = %s ';
			$variables_bookings[] = $where_state;
		}
		
		$prep_bookings	= $wpdb->prepare( $query_bookings, $variables_bookings );
		$bookings		= $wpdb->get_results( $prep_bookings, OBJECT );
		
		if( ! $bookings ) { return 0; }
		
		// Change bundled bookings payment status
		$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
					. ' SET payment_status = %s '
					. ' WHERE group_id = %d';

		$variables_array = array( $state, $booking_group_id );
		
		if( ! empty( $where_state ) ) {
			$query	.= ' AND payment_status = %s ';
			$variables_array[] = $where_state;
		}

		$prep		= $wpdb->prepare( $query, $variables_array );
		$updated	= $wpdb->query( $prep );
		
		return $updated;
	}
	
	
	/**
	 * Update booking group bookings quantity (forced update)
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param int $quantity
	 * @param boolean $add_quantity
	 * @return int|false|null
	 */
	function bookacti_force_update_booking_group_bookings_quantity( $booking_group_id, $quantity, $add_quantity = false ) {

		global $wpdb;
				
		$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET quantity = %d ';
		
		if( $add_quantity ) {
			$query .= '+ quantity';
		}
				
		$query .= ' WHERE group_id = %d';
		
		$prep		= $wpdb->prepare( $query, $quantity, $booking_group_id );
		$updated	= $wpdb->query( $prep );
		
		return $updated;
	}
	
	
	/**
	 * Update booking group
	 * 
	 * @since 1.1.0
	 * @version 1.3.0
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param 0|1|'auto' $active
	 * @return boolean|null
	 */
	function bookacti_update_booking_group( $booking_group_id, $state = NULL, $payment_status = NULL, $user_id = NULL, $order_id = NULL, $event_group_id = NULL, $active = 'auto' ) {

		global $wpdb;
		
		$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' SET ';
		
		$variables_array = array();
		
		if( $state ) {
			$query .= 'state = %s, ';
			$variables_array[] = $state;
		}
		
		if( $payment_status ) {
			$query .= ' payment_status = %s, ';
			$variables_array[] = $payment_status;
		}
		
		if( $user_id ) {
			$query .= ' user_id = %s, ';
			$variables_array[] = $user_id;
		}
		
		if( $order_id ) {
			$query .= ' order_id = %d, ';
			$variables_array[] = $order_id;
		}
		
		if( $event_group_id ) {
			$query .= ' event_group_id = %d, ';
			$variables_array[] = $event_group_id;
		}
		
		if( $state && $active === 'auto' ) {
			$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		} else if( ! $state && $active !== 0 && $active !== 1 ) {
			$active = -1;
		}
		
		$query .= ' active = IFNULL( NULLIF( %d, -1 ), active ) '
				. ' WHERE id = %d ';
		
		$variables_array[] = $active;
		$variables_array[] = $booking_group_id;
		
		$prep		= $wpdb->prepare( $query, $variables_array );
		$updated	= $wpdb->query( $prep );

		return $updated;
	}
	
	
	/**
	 * Cancel a booking group and all its bookings
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param type $booking_id
	 * @return type
	 */
	function bookacti_cancel_booking_group_and_its_bookings( $booking_group_id ) {
		global $wpdb;
		
		// Cancel bundled bookings
		$query1		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = "cancelled", active = 0 WHERE group_id = %d';
		$prep1		= $wpdb->prepare( $query1, $booking_group_id );
		$cancelled1	= $wpdb->query( $prep1 );
		
		// Cancel booking group
		$query2		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' SET state = "cancelled", active = 0 WHERE id = %d';
		$prep2		= $wpdb->prepare( $query2, $booking_group_id );
		$cancelled2	= $wpdb->query( $prep2 );
		
		$cancelled = false;
		if( is_int( $cancelled1 ) && is_int( $cancelled2 ) ) {
			$cancelled = $cancelled1 + $cancelled2;
		}
		
		return $cancelled;
	}
	
	
	/**
	 * Get booking groups according to filters
	 * 
	 * @since 1.3.0 (was bookacti_get_booking_groups_by_group_of_events)
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return object
	 */
	function bookacti_get_booking_groups( $filters ) {
		global $wpdb;

		$query	= 'SELECT BG.*, EG.title as group_title, EG.category_id, C.title as category_title, C.template_id, GE.start, GE.end '
				. ' FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ' 
				. ' JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id '
				. ' JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON EG.category_id = C.id ';
				
		// Get the first and the last event of the group and keep respectively their start and end datetime
		$query .= ' LEFT JOIN ( SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end '
				. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS 
				. ' GROUP BY group_id ' . ' ) as GE '
				. ' ON GE.group_id = BG.event_group_id ';
				
		$query .= ' WHERE true ';
		
		$variables = array();

		if( $filters[ 'status' ] ) {
			$query .= ' AND BG.state IN ( %s ';
			$array_count = count( $filters[ 'status' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query  .= ', %s ';
				}
			}
			$query  .= ') ';
			$variables = array_merge( $variables, $filters[ 'status' ] );
		}

		if( $filters[ 'templates' ] ) {
			$query .= ' AND C.template_id IN ( %d ';
			$array_count = count( $filters[ 'templates' ] );
			if( $array_count >= 2 )  {
				for( $i=1; $i<$array_count; ++$i ) {
					$query  .= ', %d ';
				}
			}
			$query  .= ') ';
			$variables = array_merge( $variables, $filters[ 'templates' ] );
		}

		if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
			$query  .= ' AND BG.id = %d ';
			$variables[] =  intval( $filters[ 'booking_group_id' ] );
		}

		if( $filters[ 'event_group_id' ] ) {
			$query .= ' AND BG.event_group_id = %d ';
			$variables[] = $filters[ 'event_group_id' ];
		}

		if( $filters[ 'user_id' ] ) {
			$query .= ' AND BG.user_id = %d ';
			$variables[] = $filters[ 'user_id' ];
		}

		$query .= ' ORDER BY id ASC ';
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
		
		$booking_groups = $wpdb->get_results( $query, OBJECT );
		
		$booking_groups_array = array();
		foreach( $booking_groups as $booking_group ) {
			$booking_groups_array[ $booking_group->id ] = $booking_group;
		}
		
		return $booking_groups_array;
	}
	
	
	/**
	 * Get a booking group by its id
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return object
	 */
	function bookacti_get_booking_group_by_id( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT G.* FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as G '
				. ' WHERE G.id = %d';
		$prep	= $wpdb->prepare( $query, $booking_group_id );
		$group	= $wpdb->get_row( $prep, OBJECT );

		return $group;
	}

	
	/**
	 * Get a booking group state
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return object
	 */
	function bookacti_get_booking_group_state( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT state FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' WHERE id = %d';
		$prep	= $wpdb->prepare( $query, $booking_group_id );
		$state	= $wpdb->get_var( $prep );
		
		return $state;
	}

	
	/**
	 * Get a booking group payment status
	 * 
	 * @since 1.3.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return object
	 */
	function bookacti_get_booking_group_payment_status( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT payment_status FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' WHERE id = %d';
		$prep	= $wpdb->prepare( $query, $booking_group_id );
		$state	= $wpdb->get_var( $prep );
		
		return $state;
	}
	
	
	/**
	 * Get booking group's user id
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return int|false|null
	 */
	function bookacti_get_booking_group_owner( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT user_id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
		$prep	= $wpdb->prepare( $query, $booking_group_id );
		$owner	= $wpdb->get_var( $prep );

		return $owner;
	}
	
	/**
	 * Check if booking group is active
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_id
	 * @return int|boolean|null
	 */
	function bookacti_is_booking_group_active( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT active FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
		$prep	= $wpdb->prepare( $query, $booking_group_id );
		$active	= $wpdb->get_var( $prep );

		return $active;
	}

	
	/**
	 * Check if a booking group exists and return its id
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param string $state
	 * @return int
	 */
	function bookacti_booking_group_exists( $user_id, $event_group_id, $state = NULL ) {
		global $wpdb;

		$query	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' WHERE user_id = %s'
				. ' AND event_group_id = %d';

		$variables = array( $user_id, $event_group_id );

		if( ! empty( $state ) ) {
			$query .=  ' AND state = %s';
			$variables[] = $state;
		}

		$prep		= $wpdb->prepare( $query, $variables );
		$group_id	= $wpdb->get_var( $prep );

		if( ! is_null( $group_id ) ) {
			return $group_id;
		}

		return 0;
	}


	/**
	 * Get bookings by booking group id
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return array
	 */
	function bookacti_get_bookings_by_booking_group_id( $booking_group_id ) {
		global $wpdb;

		$query		= 'SELECT B.*, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
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
	 * Get ids of bookings included in a gbooking group
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return array
	 */
	function bookacti_get_booking_group_bookings_ids( $booking_group_id ) {
		global $wpdb;

		$query		= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS
					. ' WHERE group_id = %d '
					. ' ORDER BY id DESC';
		$prep		= $wpdb->prepare( $query, $booking_group_id );
		$bookings	= $wpdb->get_results( $prep, OBJECT );
		
		$booking_ids = array();
		foreach( $bookings as $booking ) {
			$booking_ids[] = $booking->id;
		}
		
		return $booking_ids;
	}
	
	
	/**
	 * Get booking group quantity (= max quantity of its bookings)
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return int 
	 */
	function bookacti_get_booking_group_quantity( $booking_group_id ) {
		global $wpdb;

		$query		= 'SELECT MAX( quantity ) as max_quantity FROM ' . BOOKACTI_TABLE_BOOKINGS
					. ' WHERE group_id = %d ';
		$prep		= $wpdb->prepare( $query, $booking_group_id );
		$max_qty	= $wpdb->get_var( $prep );
		
		if( empty( $max_qty ) ) {
			return 0;
		}
		
		return $max_qty;
	}