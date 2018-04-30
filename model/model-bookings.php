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
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $user_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $state
 * @param int $booking_group_id
 * @return array
 */
function bookacti_booking_exists( $user_id, $event_id, $event_start, $event_end, $state, $booking_group_id = NULL ) {
	global $wpdb;
	
	$query = 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS 
			. ' WHERE user_id = %s '
			. ' AND event_id = %d '
			. ' AND event_start = %s '
			. ' AND event_end = %s '
			. ' AND state = %s '
			. ' AND ( expiration_date IS NULL OR expiration_date > UTC_TIMESTAMP() ) ';
	
	$variables = array( $user_id, $event_id, $event_start, $event_end, $state );
	
	if( $booking_group_id !== NULL ) {
		if( $booking_group_id === 0 ) {
			$query .= ' AND group_id IS NULL ';
		} else if( is_int( $booking_group_id ) && $booking_group_id > 0 ) {
			$query .= ' AND group_id = %d ';
			$variables[] = $booking_group_id;
		}
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$existing_bookings = $wpdb->get_results( $query, OBJECT );
	
	$booking_ids = array();
	if( $existing_bookings ) {
		foreach( $existing_bookings as $existing_booking ) {
			$booking_ids[] = $existing_booking->id;
		}
	}
	
	return $booking_ids;
}


/**
 * Update booking quantity
 * 
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int $new_quantity
 * @param string $expiration_date
 * @param string $context
 * @return array
 */
function bookacti_update_booking_quantity( $booking_id, $new_quantity, $expiration_date = '', $context = 'frontend' ) {
	global $wpdb;
	
	$booking		= bookacti_get_booking_by_id( $booking_id );
	$old_state		= $booking->state;
	$old_quantity	= intval( $booking->quantity );
	$new_quantity	= intval( $new_quantity );
	$return_array	= array( 'status' => '' );

	// Prepare variables
	$data = apply_filters( 'bookacti_update_booking_quantity_data', array( 
		'quantity' => $new_quantity,
		'state' => '', // Empty string to keep current value
		'active' => -1, // -1 to keep current value
		'expiration_date' => $expiration_date, // Empty string to keep current value
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
		$return_array['error'] = 'failed';
	}

	return $return_array;
}
	

/**
 * Get bookings according to filters. 
 * 
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return array
 */
function bookacti_get_bookings( $filters ) {
	global $wpdb;
	
	$query	= ' SELECT DISTINCT B.*, E.title as event_title, A.id as activity_id, A.title as activity_title, T.id as template_id, T.title as template_title, IFNULL( B.group_id, UUID() ) as unique_group_id ' 
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
					
	if( $filters[ 'event_group_id' ] ) {
		$query .= ', ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ';
	}
			
	$query	.= ' WHERE B.event_id = E.id '
			. ' AND E.activity_id = A.id '
			. ' AND E.template_id = T.id ';
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'from' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
				UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
			)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'to' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'status' ] ) {
		$query .= ' AND B.state IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'templates' ] ) {
		$query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $filters[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'templates' ] );
	}
	
	if( $filters[ 'activities' ] ) {
		$query .= ' AND E.activity_id IN ( %d ';
		$array_count = count( $filters[ 'activities' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'activities' ] );
	}
	
	if( $filters[ 'booking_id' ] ) {
		$query .= ' AND B.id = %d ';
		$variables[] = $filters[ 'booking_id' ];
	}
	
	if( $filters[ 'not_in__booking_id' ] ) {
		$query .= ' AND B.id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_id' ] );
	}
	
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
		$query .= ' AND B.group_id = %d ';
		$variables[] = intval( $filters[ 'booking_group_id' ] );
	} else if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}
	
	if( $filters[ 'event_group_id' ] ) {
		$query .= ' AND B.group_id = BG.id '
				. ' AND BG.event_group_id = %d ';
		$variables[] = $filters[ 'event_group_id' ];
	}
	
	if( $filters[ 'event_id' ] ) {
		$query .= ' AND B.event_id = %d ';
		$variables[] = $filters[ 'event_id' ];
	}

	if( $filters[ 'event_start' ] ) {
		$query .= ' AND B.event_start = %s ';
		$variables[] = $filters[ 'event_start' ];
	}

	if( $filters[ 'event_end' ] ) {
		$query .= ' AND B.event_end = %s ';
		$variables[] = $filters[ 'event_end' ];
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND B.user_id = %s ';
		$variables[] = $filters[ 'user_id' ];
	}

	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND B.user_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$query = apply_filters( 'bookacti_get_bookings_query', $query, $filters );
	
	$bookings = $wpdb->get_results( $query, OBJECT );
	
	return apply_filters( 'bookacti_get_bookings', $bookings, $filters, $query );
}


/**
 * Get the total amount of booking rows according to filters
 * 
 * @since 1.3.1
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_booking_rows( $filters = array() ) {
	global $wpdb;
	
	$query	= ' SELECT COUNT( list_items_count ) FROM ( '
				. ' SELECT COUNT( DISTINCT B.id ) as list_items_count, IFNULL( B.group_id, UUID() ) as unique_group_id ' 
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
	
	if( $filters[ 'event_group_id' ] ) {
		$query .= ', ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ';
	}
	
	$query	.= ' WHERE B.event_id = E.id '
			. ' AND E.activity_id = A.id '
			. ' AND E.template_id = T.id ';
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'from' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
				UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
			)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'to' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'status' ] ) {
		$query .= ' AND B.state IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'templates' ] ) {
		$query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $filters[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'templates' ] );
	}
	
	if( $filters[ 'activities' ] ) {
		$query .= ' AND E.activity_id IN ( %d ';
		$array_count = count( $filters[ 'activities' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'activities' ] );
	}
	
	if( $filters[ 'booking_id' ] ) {
		$query .= ' AND B.id = %d ';
		$variables[] = $filters[ 'booking_id' ];
	}
	
	if( $filters[ 'not_in__booking_id' ] ) {
		$query .= ' AND B.id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_id' ] );
	}
	
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
		$query .= ' AND B.group_id = %d ';
		$variables[] = intval( $filters[ 'booking_group_id' ] );
	} else if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}
	
	if( $filters[ 'event_group_id' ] ) {
		$query	.= ' AND B.group_id = BG.id '
				. ' AND BG.event_group_id = %d ';
		$variables[] = $filters[ 'event_group_id' ];
	}
	
	if( $filters[ 'event_id' ] ) {
		$query .= ' AND B.event_id = %d ';
		$variables[] = $filters[ 'event_id' ];
	}

	if( $filters[ 'event_start' ] ) {
		$query .= ' AND B.event_start = %s ';
		$variables[] = $filters[ 'event_start' ];
	}

	if( $filters[ 'event_end' ] ) {
		$query .= ' AND B.event_end = %s ';
		$variables[] = $filters[ 'event_end' ];
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND B.user_id = %s ';
		$variables[] = $filters[ 'user_id' ];
	}

	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND B.user_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	// Whether to count bookings of the same groups as one item
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
	}
	
	$query .= ' ) as C ';
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$query = apply_filters( 'bookacti_get_number_of_booking_rows_query', $query, $filters );
	
	$count = $wpdb->get_var( $query );
	
	if( ! $count ) { $count = 0; }

	return apply_filters( 'bookacti_get_number_of_booking_rows', $count, $filters, $query );
}


/**
 * Get number of booking of a specific event or a specific occurrence
 * 
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_bookings( $filters ) {
	global $wpdb;
	
	$query = '';
	
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query	= ' SELECT SUM( quantity ) FROM ( '
					. ' SELECT MAX( B.quantity ) as quantity, IFNULL( B.group_id, UUID() ) as unique_group_id ';
	} else {
		$query = ' SELECT SUM( B.quantity ) as quantity ';
	}
	
	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
	
	if( $filters[ 'event_group_id' ] ) {
		$query .= ', ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ';
	}
	
	$query	.= ' WHERE B.event_id = E.id '
			. ' AND E.activity_id = A.id '
			. ' AND E.template_id = T.id ';
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'from' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' 
		AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
				UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
			)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $filters[ 'to' ];
		$variables[] = $user_timestamp_offset;
	}
	
	if( $filters[ 'status' ] ) {
		$query .= ' AND B.state IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'templates' ] ) {
		$query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $filters[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'templates' ] );
	}
	
	if( $filters[ 'activities' ] ) {
		$query .= ' AND E.activity_id IN ( %d ';
		$array_count = count( $filters[ 'activities' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'activities' ] );
	}
	
	if( $filters[ 'booking_id' ] ) {
		$query .= ' AND B.id = %d ';
		$variables[] = $filters[ 'booking_id' ];
	}
	
	if( $filters[ 'not_in__booking_id' ] ) {
		$query .= ' AND B.id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_id' ] );
	}
	
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
		$query .= ' AND B.group_id = %d ';
		$variables[] = intval( $filters[ 'booking_group_id' ] );
	} else if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}
	
	if( $filters[ 'event_group_id' ] ) {
		$query	.= ' AND B.group_id = BG.id '
				. ' AND BG.event_group_id = %d ';
		$variables[] = $filters[ 'event_group_id' ];
	}
	
	if( $filters[ 'event_id' ] ) {
		$query .= ' AND B.event_id = %d ';
		$variables[] = $filters[ 'event_id' ];
	}

	if( $filters[ 'event_start' ] ) {
		$query .= ' AND B.event_start = %s ';
		$variables[] = $filters[ 'event_start' ];
	}

	if( $filters[ 'event_end' ] ) {
		$query .= ' AND B.event_end = %s ';
		$variables[] = $filters[ 'event_end' ];
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND B.user_id = %s ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND B.user_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	// Whether to count bookings of the same groups as one item
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
		$query .= ' ) as C '; // Close the first SELECT FROM
	}
	
	$query = $wpdb->prepare( $query, $variables );
	
	$query = apply_filters( 'bookacti_get_number_of_bookings_query', $query, $filters );
	
	$bookings = $wpdb->get_var( $query );

	if( ! $bookings ) { $bookings = 0; }

	return apply_filters( 'bookacti_get_number_of_bookings', $bookings, $filters, $query );
}


/**
 * Get number of bookings ordered by events
 * 
 * @version 1.5.0
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

	$query = 'SELECT B.event_id, B.event_start, B.event_end, SUM( B.quantity ) as quantity '
					. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE B.active = 1 '
					. ' AND B.event_id = E.id ';
	
	$variables = array();
	
	// Filter by template
	if( $template_ids ) {
		$query	.= ' AND E.template_id IN ( %d ';
		$array_count = count( $template_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $template_ids );
	}
	
	// Filter by event
	if( $event_ids ) {
		$query	.= ' AND B.event_id IN ( %d ';
		$array_count = count( $event_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $event_ids );
	}
	
	// Filter by user
	if( $user_ids ) {
		$query	.= ' AND B.user_id IN ( %d ';
		$array_count = count( $user_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $user_ids );
	}
	
	$query .= ' GROUP BY B.event_id, B.event_start, B.event_end '
					. ' ORDER BY B.event_id, B.event_start, B.event_end ';
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$events_booking_data = $wpdb->get_results( $query, ARRAY_A );
	
	$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
	
	// Order the array by event id
	$return_array = array();
	foreach( $events_booking_data as $event_booking_data ) {
		$event_id = $event_booking_data[ 'event_id' ];
		if( ! isset( $return_array[ $event_id ] ) ) {
			$return_array[ $event_id ] = array();
		}
		
		// Add info about booking per users
		$quantity_per_user = bookacti_get_number_of_bookings_per_user_by_event( $event_id, $event_booking_data[ 'event_start' ], $event_booking_data[ 'event_end' ] );
		$event_booking_data[ 'distinct_users' ]			= count( $quantity_per_user );
		$event_booking_data[ 'current_user_bookings' ]	= $current_user_id && isset( $quantity_per_user[ $current_user_id ] ) ? $quantity_per_user[ $current_user_id ] : 0;
		
		$return_array[ $event_id ][] = $event_booking_data;
	}
	
	return $return_array;
}


/**
 * Get every distinct users who booked a specific event
 * 
 * @since 1.4.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param int $active 0|1
 * @return false|array of user ids
 */
function bookacti_get_number_of_bookings_per_user_by_event( $event_id, $event_start, $event_end, $active = 1 ) {
	global $wpdb;
	
	$query	= 'SELECT B.user_id, SUM( B.quantity ) as quantity FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' WHERE B.event_id = %d '
			. ' AND B.event_start = %s '
			. ' AND B.event_end = %s '
			. ' AND B.active = %d '
			. ' GROUP BY B.user_id ';
	
	$variables = array( $event_id, $event_start, $event_end, $active );
	
	$query		= $wpdb->prepare( $query, $variables );
	$results	= $wpdb->get_results( $query, OBJECT );
	
	if( $results === false ) { return false; }
	
	$quantity_per_user = array();
	foreach( $results as $result ) {
		$quantity_per_user[ $result->user_id ] = $result->quantity;
	}
	
	return $quantity_per_user;
}


/**
 * Get every distinct users who booked a specific group of events
 * 
 * @since 1.4.0
 * @global wpdb $wpdb
 * @param int $group_of_events_id
 * @param int $active 0|1
 * @return false|array of user ids
 */
function bookacti_get_number_of_bookings_per_user_by_group_of_events( $group_of_events_id, $active = 1 ) {
	global $wpdb;
	
	$query	= 'SELECT BG.user_id, SUM( IFNULL( B.max_quantity, 0 ) ) as quantity '
			. ' FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ' 
			. ' LEFT JOIN ( '
				. ' SELECT group_id, MAX( quantity ) as max_quantity '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS
				. ' WHERE active = 1 '
				. ' AND group_id IS NOT NULL '
				. ' GROUP BY group_id '
			. ' ) as B ON B.group_id = BG.id '
			. ' WHERE BG.id = B.group_id '
			. ' AND BG.event_group_id = %d '
			. ' AND BG.active = %d '
			. ' GROUP BY BG.user_id ';
	
	$variables = array( $group_of_events_id, $active );
	
	$query		= $wpdb->prepare( $query, $variables );
	$results	= $wpdb->get_results( $query, OBJECT );
	
	if( $results === false ) { return false; }
	
	$quantity_per_user = array();
	foreach( $results as $result ) {
		$quantity_per_user[ $result->user_id ] = $result->quantity;
	}
	
	return $quantity_per_user;
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


/**
 * Delete a booking
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return int|false
 */
function bookacti_delete_booking( $booking_id ) {
	global $wpdb;
	
	// Delete booking group metadata
	bookacti_delete_metadata( 'booking', $booking_id );
	
	// Delete booking
	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d ';
	$query	= $wpdb->prepare( $query, $booking_id );
	$deleted= $wpdb->query( $query );
	
	return $deleted;
}




// BOOKING GROUPS

	/**
	 * Insert a booking group
	 * 
	 * @since 1.1.0
	 * @version 1.4.0
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
			array( '%d', '%s', '%s', '%s', '%d' )
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
	 * Update booking group payment status
	 * 
	 * @since 1.3.0
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
	 * @version 1.4.1
	 * @global wpdb $wpdb
	 * @param array $filters Use bookacti_format_booking_filters() before
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
					$query .= ', %s ';
				}
			}
			$query .= ') ';
			$variables = array_merge( $variables, $filters[ 'status' ] );
		}

		if( $filters[ 'templates' ] ) {
			$query .= ' AND C.template_id IN ( %d ';
			$array_count = count( $filters[ 'templates' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d ';
				}
			}
			$query .= ') ';
			$variables = array_merge( $variables, $filters[ 'templates' ] );
		}

		if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] != 0 ) {
			$query .= ' AND BG.id = %d ';
			$variables[] = intval( $filters[ 'booking_group_id' ] );
		}

		if( $filters[ 'event_group_id' ] ) {
			$query .= ' AND BG.event_group_id = %d ';
			$variables[] = $filters[ 'event_group_id' ];
		}

		if( $filters[ 'user_id' ] ) {
			$query .= ' AND BG.user_id = %s ';
			$variables[] = $filters[ 'user_id' ];
		}

		if( $filters[ 'active' ] !== false ) {
			$query .= ' AND BG.active = %d ';
			$variables[] = $filters[ 'active' ];
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
	 * @version 1.4.0
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return int|false|null
	 */
	function bookacti_get_booking_group_owner( $booking_group_id ) {
		global $wpdb;

		$query	= 'SELECT user_id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %s';
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
	 * @version 1.4.0
	 * @global wpdb $wpdb
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param string $state
	 * @return array
	 */
	function bookacti_booking_group_exists( $user_id, $event_group_id, $state = NULL ) {
		global $wpdb;

		$query	= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' WHERE user_id = %s'
				. ' AND event_group_id = %d';

		$variables = array( $user_id, $event_group_id );

		if( ! empty( $state ) ) {
			$query .= ' AND state = %s';
			$variables[] = $state;
		}

		$query = $wpdb->prepare( $query, $variables );
		$existing_booking_groups = $wpdb->get_results( $query, OBJECT );
	
		$booking_group_ids = array();
		if( $existing_booking_groups ) {
			foreach( $existing_booking_groups as $existing_booking_group ) {
				$booking_group_ids[] = $existing_booking_group->id;
			}
		}

		return $booking_group_ids;
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
	
	
	/**
	 * Delete a booking group 
	 * @since 1.5.0
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return int|false
	 */
	function bookacti_delete_booking_group( $booking_group_id ) {
		global $wpdb;
		
		// Delete booking group metadata
		bookacti_delete_metadata( 'booking_group', $booking_group_id );
		
		// Delete booking group
		$query		= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
		$query		= $wpdb->prepare( $query, $booking_group_id );
		$deleted	= $wpdb->query( $query );
		
		return $deleted;
	}
	
	
	/**
	 * Delete the bookings of a booking group 
	 * @since 1.5.0
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return int|false
	 */
	function bookacti_delete_booking_group_bookings( $booking_group_id ) {
		global $wpdb;
		
		// Delete bookings metadata
		$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );
		if( $booking_ids ) {
			$query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = "booking" AND object_id IN( %d';
			for( $i=1,$len=count($booking_ids); $i < $len; ++$i ) {
				$query .= ', %d';
			}
			$query .= ' ) ';
			$query	= $wpdb->prepare( $query, $booking_ids );
			$deleted= $wpdb->query( $query );
		}
		
		// Delete bookings
		$query	= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE group_id = %d ';
		$query	= $wpdb->prepare( $query, $booking_group_id );
		$deleted= $wpdb->query( $query );
		
		return $deleted;
	}