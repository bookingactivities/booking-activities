<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Book an event
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $booking_data Sanitized with bookacti_sanitize_booking_data
 * @return int
 */
function bookacti_insert_booking( $booking_data ) {
	global $wpdb;
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_BOOKINGS 
			. ' ( group_id, event_id, user_id, form_id, order_id, event_start, event_end, quantity, state, payment_status, creation_date, expiration_date, active ) ' 
			. ' VALUES ( NULLIF( %d, 0 ), %d, %s, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, %s, %d, %s, %s, %s, NULLIF( %s, "" ), %d )';
	
	$variables = array( 
		$booking_data[ 'group_id' ],
		$booking_data[ 'event_id' ],
		$booking_data[ 'user_id' ],
		$booking_data[ 'form_id' ],
		$booking_data[ 'order_id' ],
		$booking_data[ 'event_start' ],
		$booking_data[ 'event_end' ],
		$booking_data[ 'quantity' ],
		$booking_data[ 'status' ],
		$booking_data[ 'payment_status' ],
		date( 'Y-m-d H:i:s' ),
		$booking_data[ 'expiration_date' ],
		$booking_data[ 'active' ]
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	$booking_id = ! empty( $wpdb->insert_id ) ? $wpdb->insert_id : 0;
	
	if( $booking_id ) {
		$booking_data[ 'id' ] = $booking_id;
		do_action( 'bookacti_booking_inserted', $booking_data );
	}
	
	return $booking_id;
}


/**
 * Update a booking
 * @since 1.8.10
 * @global wpdb $wpdb
 * @param array $booking_data Sanitized with bookacti_sanitize_booking_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking( $booking_data, $where = array() ) {
	global $wpdb;
	
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
			. ' SET '
			. ' group_id = NULLIF( IFNULL( NULLIF( %d, 0 ), group_id ), -1 ), '
			. ' user_id = IFNULL( NULLIF( %s, "0" ), user_id ), '
			. ' form_id = NULLIF( IFNULL( NULLIF( %d, 0 ), form_id ), -1 ), '
			. ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
			. ' event_id = IFNULL( NULLIF( %d, 0 ), event_id ), '
			. ' event_start = IFNULL( NULLIF( %s, "" ), event_start ), '
			. ' event_end = IFNULL( NULLIF( %s, "" ), event_end ), '
			. ' quantity = IFNULL( NULLIF( %d, 0 ), quantity ), '
			. ' state = IFNULL( NULLIF( %s, "" ), state ), '
			. ' payment_status = IFNULL( NULLIF( %s, "" ), payment_status ), '
			. ' expiration_date = NULLIF( IFNULL( NULLIF( %s, "" ), expiration_date ), "0000-00-00 00:00:00" ), '
			. ' active = IFNULL( NULLIF( %d, -1 ), active ) '
			. ' WHERE id = %d ';
	
	$variables = array( 
		! is_null( $booking_data[ 'group_id' ] ) ? $booking_data[ 'group_id' ] : -1,
		$booking_data[ 'user_id' ],
		! is_null( $booking_data[ 'form_id' ] ) ? $booking_data[ 'form_id' ] : -1,
		! is_null( $booking_data[ 'order_id' ] ) ? $booking_data[ 'order_id' ] : -1,
		$booking_data[ 'event_id' ],
		$booking_data[ 'event_start' ],
		$booking_data[ 'event_end' ],
		$booking_data[ 'quantity' ],
		$booking_data[ 'status' ],
		$booking_data[ 'payment_status' ],
		! is_null( $booking_data[ 'expiration_date' ] ) ? $booking_data[ 'expiration_date' ] : '0000-00-00 00:00:00',
		$booking_data[ 'active' ],
		! empty( $where[ 'id' ] ) ? $where[ 'id' ] : $booking_data[ 'id' ]
	);
	
	if( ! empty( $where[ 'status__in' ] ) ) {
		$query .= ' AND state IN ( %s ';
		$array_count = count( $where[ 'status__in' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
	}
	
	$query		= apply_filters( 'bookacti_update_booking_query', $wpdb->prepare( $query, $variables ), $booking_data, $where );
	$updated	= $wpdb->query( $query );
	
	if( $updated ) {
		do_action( 'bookacti_booking_updated', $booking_data, $where );
	}
	
	return $updated;
}


/**
 * Update booking quantity
 * @version 1.7.10
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
	$is_admin		= $context === 'admin' ? true : false;

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
			do_action( 'bookacti_booking_state_changed', $booking_id, $data[ 'state' ], array( 'is_admin' => $is_admin ) );
		}

		// If quantity has changed
		if( intval( $data[ 'quantity' ] ) > 0 && intval( $data[ 'quantity' ] ) !== $old_quantity ) {
			do_action( 'bookacti_booking_quantity_updated', $booking_id, intval( $data[ 'quantity' ] ), $old_quantity, array( 'is_admin' => $is_admin ) );
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
 * Get bookings according to filters
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return array
 */
function bookacti_get_bookings( $filters ) {
	global $wpdb;
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( is_numeric( $filters[ 'user_id' ] ) && $filters[ 'user_id' ] )						{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query_select	= ' SELECT DISTINCT B.*, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id,'
					. ' E.title as event_title,'
					. ' A.id as activity_id, A.title as activity_title,'
					. ' T.id as template_id, T.title as template_title,'
					. ' BG.event_group_id, BG.state as group_state, BG.payment_status as group_payment_status, BG.user_id as group_user_id, BG.order_id as group_order_id, BG.form_id as group_form_id, BG.active as group_active,'
					. ' EG.category_id, EG.title as group_title ';
	
	// Get event / group of event total availability
	$query_select .= $filters[ 'group_by' ] === 'booking_group' ? ', MIN( E.availability ) as availability ' : ', E.availability ';
	
	$query_join = ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
				. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
				. ' JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
				. ' JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id '
				. ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id '
				. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
		
	$query = $query_select . $query_join . ' WHERE TRUE ';
	
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
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
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
	
	if( $filters[ 'payment_status' ] ) {
		$query .= ' AND B.payment_status IN ( %s ';
		$array_count = count( $filters[ 'payment_status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'payment_status' ] );
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
	
	if( $filters[ 'in__booking_id' ] ) {
		$query .= $filters[ 'in__booking_group_id' ] ? ' AND (' : ' AND';
		$query .= ' B.id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__booking_id' ] );
	}
	
	if( $filters[ 'in__booking_group_id' ] ) {
		$query .= ' ' . $filters[ 'booking_group_id_operator' ];
		$query .= ' B.group_id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		if( $filters[ 'in__booking_id' ] ) { $query .= ') '; }
		$variables = array_merge( $variables, $filters[ 'in__booking_group_id' ] );
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
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}

	if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'in__event_group_id' ] ) {
		$query .= ' AND BG.event_group_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_group_id' ] );
	}
	
	if( $filters[ 'not_in__event_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR BG.event_group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_group_id' ] );
	}
	
	if( $filters[ 'in__group_category_id' ] ) {
		$query .= ' AND EG.category_id IN ( %d ';
		$array_count = count( $filters[ 'in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__group_category_id' ] );
	}
	
	if( $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR EG.category_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__group_category_id' ] );
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
	
	if( $filters[ 'in__form_id' ] ) {
		$query .= ' AND B.form_id IN ( %d ';
		$array_count = count( $filters[ 'in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__form_id' ] );
	}
	
	if( $filters[ 'not_in__form_id' ] ) {
		$query .= ' AND ( B.form_id IS NULL OR B.form_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__form_id' ] );
	}
	
	if( $filters[ 'in__user_id' ] ) {
		$query .= ' AND B.user_id IN ( %s ';
		$array_count = count( $filters[ 'in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__user_id' ] );
	}
	
	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND ( B.user_id IS NULL OR B.user_id NOT IN ( %s ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
	} else {
		$query .= ' GROUP BY B.id ';
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			if( $filters[ 'order_by' ][ $i ] === 'id' ) { $filters[ 'order_by' ][ $i ] = 'B.id'; }
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
	
	$bookings_array		= array();
	$booking_ids		= array();
	$booking_group_ids	= array();
	foreach( $bookings as $booking ) {
		$bookings_array[ $booking->id ] = $booking;
		$booking_ids[] = $booking->id;
		if( $booking->group_id && ! in_array( $booking->group_id, $booking_group_ids, true ) ) {
			$booking_group_ids[] = $booking->group_id;
		}
	}
	
	if( $filters[ 'fetch_meta' ] ) {
		// Get the bookings meta and the booking groups meta
		$bookings_meta			= $booking_ids ? bookacti_get_metadata( 'booking', $booking_ids ) : array();
		$booking_groups_meta	= $booking_group_ids ? bookacti_get_metadata( 'booking_group', $booking_group_ids ) : array();
		foreach( $bookings_array as $booking_id => $booking ) {
			// Merge the booking group meta with the booking meta
			$booking_meta = array();
			if( ! empty( $bookings_meta[ $booking->id ] ) ) {
				$booking_meta = $bookings_meta[ $booking->id ];
			}
			if( $booking->group_id && ! empty( $booking_groups_meta[ $booking->group_id ] ) ) {
				$booking_meta = empty( $bookings_meta[ $booking->id ] ) ? $booking_groups_meta[ $booking->group_id ] : array_merge( $booking_groups_meta[ $booking->group_id ], $bookings_meta[ $booking->id ] );
			}
			// Add the booking meta to booking data
			foreach( $booking_meta as $meta_key => $meta_value ) {
				$bookings_array[ $booking_id ]->{$meta_key} = $meta_value;
			}
		}
	}
	
	return apply_filters( 'bookacti_get_bookings', $bookings_array, $filters, $query );
}


/**
 * Get the total amount of booking rows according to filters
 * @since 1.3.1
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_booking_rows( $filters ) {
	global $wpdb;
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( is_numeric( $filters[ 'user_id' ] ) && $filters[ 'user_id' ] )						{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query	= ' SELECT COUNT( list_items_count ) FROM ( '
				. ' SELECT COUNT( DISTINCT B.id ) as list_items_count, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id ';
	
	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
			. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
			. ' JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
			. ' JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id ';
	
	if( $filters[ 'in__event_group_id' ] || $filters[ 'not_in__event_group_id' ] 
	||  $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id ';
	}
	
	if( $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
	}
	
	$query	.= ' WHERE TRUE ';
	
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
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
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
	
	if( $filters[ 'payment_status' ] ) {
		$query .= ' AND B.payment_status IN ( %s ';
		$array_count = count( $filters[ 'payment_status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'payment_status' ] );
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
	
	if( $filters[ 'in__booking_id' ] ) {
		$query .= $filters[ 'in__booking_group_id' ] ? ' AND (' : ' AND';
		$query .= ' B.id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__booking_id' ] );
	}
	
	if( $filters[ 'in__booking_group_id' ] ) {
		$query .= ' ' . $filters[ 'booking_group_id_operator' ];
		$query .= ' B.group_id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		if( $filters[ 'in__booking_id' ] ) { $query .= ') '; }
		$variables = array_merge( $variables, $filters[ 'in__booking_group_id' ] );
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
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}
	
	if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'in__event_group_id' ] ) {
		$query .= ' AND BG.event_group_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_group_id' ] );
	}
	
	if( $filters[ 'not_in__event_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR BG.event_group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_group_id' ] );
	}
	
	if( $filters[ 'in__group_category_id' ] ) {
		$query .= ' AND EG.category_id IN ( %d ';
		$array_count = count( $filters[ 'in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__group_category_id' ] );
	}
	
	if( $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR EG.category_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__group_category_id' ] );
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
	
	if( $filters[ 'in__form_id' ] ) {
		$query .= ' AND B.form_id IN ( %d ';
		$array_count = count( $filters[ 'in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__form_id' ] );
	}
	
	if( $filters[ 'not_in__form_id' ] ) {
		$query .= ' AND ( B.form_id IS NULL OR B.form_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__form_id' ] );
	}
	
	if( $filters[ 'in__user_id' ] ) {
		$query .= ' AND B.user_id IN ( %s ';
		$array_count = count( $filters[ 'in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__user_id' ] );
	}

	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND ( B.user_id IS NULL OR B.user_id NOT IN ( %s ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	// Whether to count bookings of the same groups as one item
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
	} else {
		$query .= ' GROUP BY B.id ';
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
 * Get number of bookings of a specific event or a specific occurrence
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_bookings( $filters ) {
	global $wpdb;
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( is_numeric( $filters[ 'user_id' ] ) && $filters[ 'user_id' ] )						{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query = '';
	
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query	= ' SELECT SUM( quantity ) FROM ( '
					. ' SELECT MAX( B.quantity ) as quantity, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id ';
	} else {
		$query = ' SELECT SUM( quantity ) FROM ( '
					. ' SELECT SUM( B.quantity ) as quantity ';
	}
	
	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
			. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
			. ' JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
			. ' JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id ';
	
	if( $filters[ 'in__event_group_id' ] || $filters[ 'not_in__event_group_id' ] 
	||  $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id ';
	}
	
	if( $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
	}
	
	$query	.= ' WHERE TRUE ';
	
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
				UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
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
	
	if( $filters[ 'payment_status' ] ) {
		$query .= ' AND B.payment_status IN ( %s ';
		$array_count = count( $filters[ 'payment_status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'payment_status' ] );
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
	
	if( $filters[ 'in__booking_id' ] ) {
		$query .= $filters[ 'in__booking_group_id' ] ? ' AND (' : ' AND';
		$query .= ' B.id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__booking_id' ] );
	}
	
	if( $filters[ 'in__booking_group_id' ] ) {
		$query .= ' ' . $filters[ 'booking_group_id_operator' ];
		$query .= ' B.group_id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		if( $filters[ 'in__booking_id' ] ) { $query .= ') '; }
		$variables = array_merge( $variables, $filters[ 'in__booking_group_id' ] );
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
	
	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR B.group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}
	
	if( $filters[ 'booking_group_id' ] === 'none' ) {
		$query .= ' AND B.group_id IS NULL ';
	}
	
	if( $filters[ 'in__event_group_id' ] ) {
		$query .= ' AND BG.event_group_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_group_id' ] );
	}
	
	if( $filters[ 'not_in__event_group_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR BG.event_group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_group_id' ] );
	}
	
	if( $filters[ 'in__group_category_id' ] ) {
		$query .= ' AND EG.category_id IN ( %d ';
		$array_count = count( $filters[ 'in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__group_category_id' ] );
	}
	
	if( $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' AND ( B.group_id IS NULL OR EG.category_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__group_category_id' ] );
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
	
	if( $filters[ 'in__form_id' ] ) {
		$query .= ' AND B.form_id IN ( %d ';
		$array_count = count( $filters[ 'in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__form_id' ] );
	}
	
	if( $filters[ 'not_in__form_id' ] ) {
		$query .= ' AND ( B.form_id IS NULL OR B.form_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__form_id' ] );
	}
	
	if( $filters[ 'in__user_id' ] ) {
		$query .= ' AND B.user_id IN ( %s ';
		$array_count = count( $filters[ 'in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__user_id' ] );
	}
	
	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND ( B.user_id IS NULL OR B.user_id NOT IN ( %s ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	// Whether to count bookings of the same groups as one item
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query .= ' GROUP BY unique_group_id ';
	} else {
		$query .= ' GROUP BY B.id ';
	}
	
	$query .= ' ) as Q'; // Close the first SELECT FROM
	
	$query = $wpdb->prepare( $query, $variables );
	
	$query = apply_filters( 'bookacti_get_number_of_bookings_query', $query, $filters );
	
	$bookings = $wpdb->get_var( $query );

	if( ! $bookings ) { $bookings = 0; }

	return apply_filters( 'bookacti_get_number_of_bookings', $bookings, $filters, $query );
}


/**
 * Get number of bookings ordered by events
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $event_ids
 * @param array $user_ids
 * @return array
 */
function bookacti_get_number_of_bookings_by_events( $template_ids = array(), $event_ids = array(), $user_ids = array() ) {
	global $wpdb;

	// Convert ids to array
	$template_ids	= bookacti_ids_to_array( $template_ids );
	$event_ids		= bookacti_ids_to_array( $event_ids );
	$user_ids		= bookacti_ids_to_array( $user_ids );

	$query	= 'SELECT B.event_id, B.event_start, B.event_end, SUM( B.quantity ) as quantity, COUNT( DISTINCT B.user_id ) as distinct_users, SUM( IF( B.user_id = %s, B.quantity, 0 ) ) as current_user_bookings '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE B.active = 1 '
			. ' AND B.event_id = E.id ';
	
	$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
	$variables = array( $current_user_id );
	
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
		$query	.= ' AND B.user_id IN ( %s ';
		$array_count = count( $user_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
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
	
	$query = apply_filters( 'bookacti_get_number_of_bookings_by_events_query', $query, $template_ids, $event_ids, $user_ids );
	
	$events_booking_data = $wpdb->get_results( $query, ARRAY_A );
	
	// Order the array by event id
	$return_array = array();
	foreach( $events_booking_data as $event_booking_data ) {
		$event_id = $event_booking_data[ 'event_id' ];
		if( ! isset( $return_array[ $event_id ] ) ) { $return_array[ $event_id ] = array(); }
		$return_array[ $event_id ][ $event_booking_data[ 'event_start' ] ] = $event_booking_data;
	}
	
	return apply_filters( 'bookacti_get_number_of_bookings_by_events', $return_array, $template_ids, $event_ids, $user_ids, $query );
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
 * Get the number of bookings per distinct users who booked specific events
 * @since 1.8.10
 * @global wpdb $wpdb
 * @param array $events
 * @param int $active -1|0|1
 * @return array
 */
function bookacti_get_number_of_bookings_per_user_by_events( $events, $active = 1 ) {
	global $wpdb;
	
	$query	= 'SELECT B2.user_id, MAX( B2.quantity_per_event_per_user ) as max_quantity '
			. ' FROM ( ' 
				. ' SELECT B1.user_id, SUM( B1.quantity ) as quantity_per_event_per_user,'
				. ' CONCAT( B1.user_id, "_", B1.event_id, "_", B1.event_start, "_", B1.event_end ) as event_per_user '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS .' as B1 '
				. ' WHERE ';
	
	$variables = array();
	$i = 0;
	foreach( $events as $event ) {
		if( $i !== 0 ) { $query .= ' OR '; }
		$query .= ' ('
				. ' B1.event_id = %d'
				. ' AND B1.event_start = %s'
				. ' AND B1.event_end = %s'
				. ' AND B1.active = IFNULL( NULLIF( %d, -1 ), B1.active )'
				. ' ) ';
		$variables = array_merge( $variables, array( $event[ 'id' ], $event[ 'start' ], $event[ 'end' ], $active ) );
		++$i;
	}
	
	$query .=	  ' GROUP BY event_per_user '
			. ' ) as B2 '
			. ' GROUP BY B2.user_id ';
	
	$query		= apply_filters( 'bookacti_number_of_bookings_per_user_by_events_query', $wpdb->prepare( $query, $variables ), $events, $active );
	$results	= $wpdb->get_results( $query, OBJECT );
	
	$quantity_per_user = array();
	if( $results ) {
		foreach( $results as $result ) {
			$user_id = ! empty( $result->user_id ) ? $result->user_id : 0;
			$quantity_per_user[ $user_id ] = $result->max_quantity;
		}
	}
	
	return apply_filters( 'bookacti_number_of_bookings_per_user_by_events', $quantity_per_user, $events, $active, $query );
}


/**
 * Get every distinct users who booked a specific group of events
 * @since 1.4.0
 * @version 1.7.1
 * @global wpdb $wpdb
 * @param int|array $group_of_events_ids
 * @param int $active 0|1
 * @return false|array of user ids
 */
function bookacti_get_number_of_bookings_per_user_by_group_of_events( $group_of_events_ids, $active = 1 ) {
	// Sanitize the array of group of events ID
	if( ! is_array( $group_of_events_ids ) ) {
		$variables = array( intval( $group_of_events_ids ) );
	} else {
		$variables = array_filter( array_map( 'intval', $group_of_events_ids ) );
	}

	if( ! $variables ) { return false; }
	
	global $wpdb;
	
	$query	= 'SELECT BG.event_group_id, BG.user_id, SUM( IFNULL( B.max_quantity, 0 ) ) as quantity '
			. ' FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ' 
			. ' LEFT JOIN ( '
				. ' SELECT group_id, MAX( quantity ) as max_quantity '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS
				. ' WHERE active = 1 '
				. ' AND group_id IS NOT NULL '
				. ' GROUP BY group_id '
			. ' ) as B ON B.group_id = BG.id '
			. ' WHERE BG.id = B.group_id '
			. ' AND BG.event_group_id IN ( %d';
	
	$array_count = count( $variables );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$query .= ', %d';
		}
	}
	
	$query .= ' )'
			. ' AND BG.active = %d '
			. ' GROUP BY BG.event_group_id, BG.user_id;';
	
	$variables[] = $active;
	
	$query		= $wpdb->prepare( $query, $variables );
	$results	= $wpdb->get_results( $query );
	
	if( $results === false ) { return false; }
	
	$quantity_per_user = array();
	foreach( $results as $result ) {
		$quantity_per_user[ $result->event_group_id ][ $result->user_id ] = $result->quantity;
	}
	
	// Return the single value if only one group was given
	if( ! is_array( $group_of_events_ids ) ) {
		return isset( $quantity_per_user[ $group_of_events_ids ] ) ? $quantity_per_user[ $group_of_events_ids ] : array();
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
 * @version 1.7.18
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return array
 */
function bookacti_get_booking_data( $booking_id ) {
	global $wpdb;
	
	$query	= 'SELECT B.*, E.template_id, E.activity_id '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE B.event_id = E.id '
			. ' AND B.id = %d ';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$booking_data = $wpdb->get_row( $prep, ARRAY_A );
	
	if( empty( $booking_data[ 'template_id' ] ) ) { $booking_data[ 'template_id' ] = 0; }
	if( empty( $booking_data[ 'activity_id' ] ) ) { $booking_data[ 'activity_id' ] = 0; }
	
	$booking_data[ 'booking_settings' ] = bookacti_get_metadata( 'booking', $booking_id );
	
	return apply_filters( 'bookacti_booking_data', $booking_data, $booking_id );
}


/**
 * Get all user's bookings
 * 
 * @global wpdb $wpdb
 * @param int|string $user_id
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
 * Get booking form id
 * @since 1.5.4
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return string|null
 */
function bookacti_get_booking_form_id( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT form_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$form_id= $wpdb->get_var( $prep );

	return intval( $form_id );
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
 * Update booking user id
 * @since 1.6.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int|string $user_id
 * @return int|false
 */
function bookacti_update_booking_user_id( $booking_id, $user_id ) {
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET user_id = %s '
				. ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $user_id, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


/** 
 * Update all bookings of a customer_id with a new user_id
 * 
 * When not logged-in people add a booking to cart or go to checkout, their booking and order are associated with their customer id
 * This changes customer id by user id for all bookings made whithin the 31 past days as they log in which correspond to WC cart cookie
 * We can't go further because customer ids are generated randomly, regardless of existing ones in database
 * Limiting to 31 days make it very improbable that two customers with the same id create an account or log in
 * 
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param int|string $user_id
 * @param int|string $old_user_id
 * @param int $expiration_date_delay
 * @return false|int
 */
function bookacti_update_bookings_user_id( $user_id, $old_user_id, $expiration_date_delay = 31 ) {
	global $wpdb;
	
	// Single Bookings
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET user_id = %s '
				. ' WHERE user_id = %s ';
	
	$variables = array( $user_id, $old_user_id );
	
	// Whether to check expiration date
	if( is_numeric( $expiration_date_delay ) ) {
		$query .= ' AND IF( expiration_date IS NOT NULL, expiration_date >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY ), TRUE ) ';
		$variables[] = intval( $expiration_date_delay );
	}
	
	$query		= $wpdb->prepare( $query, $variables );
	$updated1	= $wpdb->query( $query );
	
	// Booking Groups
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS 
				. ' SET user_id = %s '
				. ' WHERE user_id = %s ';
	$query		= $wpdb->prepare( $query, $user_id, $old_user_id );
	$updated2	= $wpdb->query( $query );
	
	if( $updated1 === false || $updated2 === false ) { return false; }
	
	return $updated1 + $updated2;
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
 * @version 1.7.10
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
 * @version 1.5.8
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return int|false
 */
function bookacti_delete_booking( $booking_id ) {
	global $wpdb;
	
	// Delete booking
	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d ';
	$query	= $wpdb->prepare( $query, $booking_id );
	$deleted= $wpdb->query( $query );
	
	if( $deleted ) {
		// Delete booking group metadata
		bookacti_delete_metadata( 'booking', $booking_id );
	}
	
	return $deleted;
}




// BOOKING GROUPS

/**
 * Insert a booking group
 * @since 1.1.0
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @return int
 */
function bookacti_insert_booking_group( $booking_group_data ) {
	global $wpdb;

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_BOOKING_GROUPS 
			. ' ( event_group_id, user_id, form_id, order_id, state, payment_status, active ) ' 
			. ' VALUES ( NULLIF( %d, 0 ), %s, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, %s, %d )';

	$variables = array( 
		$booking_group_data[ 'event_group_id' ],
		$booking_group_data[ 'user_id' ],
		$booking_group_data[ 'form_id' ],
		$booking_group_data[ 'order_id' ],
		$booking_group_data[ 'status' ],
		$booking_group_data[ 'payment_status' ],
		$booking_group_data[ 'active' ]
	);

	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	$booking_group_id = ! empty( $wpdb->insert_id ) ? $wpdb->insert_id : 0;

	if( $booking_group_id ) {
		do_action( 'bookacti_booking_group_inserted', $booking_group_id, $booking_group_data );
	}

	return $booking_group_id;
}


/**
 * Update booking group
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking_group( $booking_group_data, $where = array() ) {
	global $wpdb;
	
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS
			. ' SET '
			. ' event_group_id = NULLIF( IFNULL( NULLIF( %d, 0 ), event_group_id ), -1 ), '
			. ' user_id = IFNULL( NULLIF( %s, "0" ), user_id ), '
			. ' form_id = NULLIF( IFNULL( NULLIF( %d, 0 ), form_id ), -1 ), '
			. ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
			. ' state = IFNULL( NULLIF( %s, "" ), state ), '
			. ' payment_status = IFNULL( NULLIF( %s, "" ), payment_status ), '
			. ' active = IFNULL( NULLIF( %d, -1 ), active ) '
			. ' WHERE id = %d ';
	
	$variables = array( 
		! is_null( $booking_group_data[ 'event_group_id' ] ) ? $booking_group_data[ 'event_group_id' ] : -1,
		$booking_group_data[ 'user_id' ],
		! is_null( $booking_group_data[ 'form_id' ] ) ? $booking_group_data[ 'form_id' ] : -1,
		! is_null( $booking_group_data[ 'order_id' ] ) ? $booking_group_data[ 'order_id' ] : -1,
		$booking_group_data[ 'status' ],
		$booking_group_data[ 'payment_status' ],
		$booking_group_data[ 'active' ],
		! empty( $where[ 'id' ] ) ? $where[ 'id' ] : $booking_group_data[ 'id' ]
	);
	
	if( ! empty( $where[ 'status__in' ] ) ) {
		$query .= ' AND state IN ( %s ';
		$array_count = count( $where[ 'status__in' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
	}
	
	$query		= apply_filters( 'bookacti_update_booking_group_query', $wpdb->prepare( $query, $variables ), $booking_group_data, $where );
	$updated	= $wpdb->query( $query );
	
	if( $updated ) {
		do_action( 'bookacti_booking_group_updated', $booking_group_data, $where );
	}
	
	return $updated;
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
 * @return int|boolean
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
 * Update booking group user id
 * @since 1.8.10
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param int|string $user_id
 * @return int|false
 */
function bookacti_update_booking_group_user_id( $booking_group_id, $user_id ) {
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
				. ' SET user_id = %s '
				. ' WHERE id = %d';
	$query		= $wpdb->prepare( $query, $user_id, $booking_group_id );
	$updated	= $wpdb->query( $query );
	
	return $updated;
}


/**
 * Update booking group bookings
 * @since 1.8.10
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking_group_bookings( $booking_group_data, $where = array() ) {
	global $wpdb;
	
	$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
			. ' SET '
			. ' user_id = IFNULL( NULLIF( %s, "0" ), user_id ), '
			. ' form_id = NULLIF( IFNULL( NULLIF( %d, 0 ), form_id ), -1 ), '
			. ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
			. ' state = IFNULL( NULLIF( %s, "" ), state ), '
			. ' payment_status = IFNULL( NULLIF( %s, "" ), payment_status ), '
			. ' expiration_date = NULLIF( IFNULL( NULLIF( %s, "" ), expiration_date ), "0000-00-00 00:00:00" ), '
			. ' quantity = IFNULL( NULLIF( %d, 0 ), quantity ), '
			. ' active = IFNULL( NULLIF( %d, -1 ), active ) '
			. ' WHERE group_id = %d ';
	
	$variables = array( 
		$booking_group_data[ 'user_id' ],
		! is_null( $booking_group_data[ 'form_id' ] ) ? $booking_group_data[ 'form_id' ] : -1,
		! is_null( $booking_group_data[ 'order_id' ] ) ? $booking_group_data[ 'order_id' ] : -1,
		$booking_group_data[ 'status' ],
		$booking_group_data[ 'payment_status' ],
		! is_null( $booking_group_data[ 'expiration_date' ] ) ? $booking_group_data[ 'expiration_date' ] : '0000-00-00 00:00:00',
		$booking_group_data[ 'quantity' ],
		$booking_group_data[ 'active' ],
		! empty( $where[ 'id' ] ) ? $where[ 'id' ] : $booking_group_data[ 'id' ]
	);
	
	if( ! empty( $where[ 'status__in' ] ) ) {
		$query .= ' AND state IN ( %s ';
		$array_count = count( $where[ 'status__in' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $where[ 'status__in' ] );
	}
	
	$query		= apply_filters( 'bookacti_update_booking_group_bookings_query', $wpdb->prepare( $query, $variables ), $booking_group_data, $where );
	$updated	= $wpdb->query( $query );
	
	if( $updated ) {
		do_action( 'bookacti_booking_group_bookings_updated', $booking_group_data, $where );
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
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param string $state
 * @param string|false $where_state
 * @return int|false
 */
function bookacti_update_booking_group_bookings_payment_status( $booking_group_id, $state, $where_state = false ) {

	global $wpdb;

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
 * Update booking group bookings user id
 * @since 1.6.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param int|string $user_id
 * @param string|int|false $where_user_id
 * @return int|false
 */
function bookacti_update_booking_group_bookings_user_id( $booking_group_id, $user_id, $where_user_id = false ) {
	global $wpdb;

	// Change bundled bookings payment status
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET user_id = %s '
				. ' WHERE group_id = %d';

	$variables_array = array( $user_id, $booking_group_id );

	if( ! empty( $where_user_id ) ) {
		$query	.= ' AND user_id = %s ';
		$variables_array[] = $where_user_id;
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
 * @return int|false
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
 * Cancel a booking group and all its bookings
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param type $booking_id
 * @return int|false
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
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return array
 */
function bookacti_get_booking_groups( $filters ) {
	global $wpdb;

	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( is_numeric( $filters[ 'user_id' ] ) && $filters[ 'user_id' ] )						{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }

	$query	= 'SELECT BG.*, EG.title as group_title, EG.category_id, C.title as category_title, C.template_id, GE.start, GE.end, B.quantity ';

	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ' 
			. ' JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id '
			. ' JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON EG.category_id = C.id ';

	// Get the first and the last event of the group and keep respectively their start and end datetime
	$query .= ' LEFT JOIN ( SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' GROUP BY group_id ' . ' ) as GE '
			. ' ON GE.group_id = BG.event_group_id ';

	// Get the max booking quantity
	$query .= ' LEFT JOIN ( SELECT group_id as booking_group_id, MAX( quantity ) as quantity '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS 
			. ' GROUP BY group_id ' . ' ) as B '
			. ' ON BG.id = B.booking_group_id ';

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

	if( $filters[ 'payment_status' ] ) {
		$query .= ' AND BG.payment_status IN ( %s ';
		$array_count = count( $filters[ 'payment_status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'payment_status' ] );
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

	if( $filters[ 'in__booking_group_id' ] ) {
		$query .= ' AND BG.id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__booking_group_id' ] );
	}

	if( $filters[ 'not_in__booking_group_id' ] ) {
		$query .= ' AND BG.id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__booking_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'not_in__booking_group_id' ] );
	}

	if( $filters[ 'in__event_group_id' ] ) {
		$query .= ' AND BG.event_group_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_group_id' ] );
	}

	if( $filters[ 'not_in__event_group_id' ] ) {
		$query .= ' AND ( BG.event_group_id IS NULL OR BG.event_group_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_group_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_group_id' ] );
	}

	if( $filters[ 'in__group_category_id' ] ) {
		$query .= ' AND EG.category_id IN ( %d ';
		$array_count = count( $filters[ 'in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__group_category_id' ] );
	}

	if( $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' AND ( EG.category_id IS NULL OR EG.category_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__group_category_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__group_category_id' ] );
	}

	if( $filters[ 'in__form_id' ] ) {
		$query .= ' AND BG.form_id IN ( %d ';
		$array_count = count( $filters[ 'in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__form_id' ] );
	}

	if( $filters[ 'not_in__form_id' ] ) {
		$query .= ' AND ( BG.form_id IS NULL OR BG.form_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__form_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__form_id' ] );
	}

	if( $filters[ 'in__user_id' ] ) {
		$query .= ' AND BG.user_id IN ( %s ';
		$array_count = count( $filters[ 'in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__user_id' ] );
	}

	if( $filters[ 'not_in__user_id' ] ) {
		$query .= ' AND ( BG.user_id IS NULL OR BG.user_id NOT IN ( %s ';
		$array_count = count( $filters[ 'not_in__user_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__user_id' ] );
	}

	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND BG.active = %d ';
		$variables[] = $filters[ 'active' ];
	}

	$query .= ' GROUP BY BG.id ';

	$query .= ' ORDER BY BG.id ASC ';

	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	$query = apply_filters( 'bookacti_get_booking_groups_query', $query, $filters );

	$booking_groups = $wpdb->get_results( $query, OBJECT );

	$booking_groups_array	= array();
	$booking_group_ids		= array();
	foreach( $booking_groups as $booking_group ) {
		$booking_groups_array[ $booking_group->id ] = $booking_group;
		$booking_group_ids[] = $booking_group->id;
	}

	if( $filters[ 'fetch_meta' ] ) {
		// Get the booking groups meta
		$booking_groups_meta = $booking_group_ids ? bookacti_get_metadata( 'booking_group', $booking_group_ids ) : array();
		foreach( $booking_groups_array as $booking_group_id => $booking_group ) {
			$booking_group_meta = ! empty( $booking_groups_meta[ $booking_group->id ] ) ? $booking_groups_meta[ $booking_group->id ] : array();
			// Add the booking group meta to booking group data
			foreach( $booking_group_meta as $meta_key => $meta_value ) {
				$booking_groups_array[ $booking_group_id ]->{$meta_key} = $meta_value;
			}
		}
	}

	return apply_filters( 'bookacti_get_booking_groups', $booking_groups_array, $filters, $query );
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
 * @version 1.5.4
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int|null
 */
function bookacti_get_booking_group_owner( $booking_group_id ) {
	global $wpdb;

	$query	= 'SELECT user_id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_group_id );
	$owner	= $wpdb->get_var( $prep );

	return $owner;
}


/**
 * Get booking group's form id
 * @since 1.5.4
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int
 */
function bookacti_get_booking_group_form_id( $booking_group_id ) {
	global $wpdb;

	$query	= 'SELECT form_id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_group_id );
	$form_id= $wpdb->get_var( $prep );

	return intval( $form_id );
}


/**
 * Check if booking group is active
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return int|null
 */
function bookacti_is_booking_group_active( $booking_group_id ) {
	global $wpdb;

	$query	= 'SELECT active FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_group_id );
	$active	= $wpdb->get_var( $prep );

	return $active;
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
 * Get ids of bookings included in a booking group
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
 * @version 1.5.8
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int|false
 */
function bookacti_delete_booking_group( $booking_group_id ) {
	global $wpdb;

	// Delete booking group
	$query		= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$query		= $wpdb->prepare( $query, $booking_group_id );
	$deleted	= $wpdb->query( $query );

	// Delete booking group metadata
	if( $deleted ) {
		bookacti_delete_metadata( 'booking_group', $booking_group_id );
	}

	return $deleted;
}


/**
 * Delete the bookings of a booking group 
 * @since 1.5.0
 * @version 1.5.8
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int|false
 */
function bookacti_delete_booking_group_bookings( $booking_group_id ) {
	global $wpdb;

	// Delete bookings metadata
	$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );

	// Delete bookings
	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE group_id = %d ';
	$query	= $wpdb->prepare( $query, $booking_group_id );
	$deleted= $wpdb->query( $query );

	if( $deleted && $booking_ids ) {
		$query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = "booking" AND object_id IN( %d';
		for( $i=1,$len=count($booking_ids); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$query	= $wpdb->prepare( $query, $booking_ids );
		$deleted= $wpdb->query( $query );
	}

	return $deleted;
}




/** EXPORT **/

/**
 * Retrieve exports from database
 * @since 1.8.0
 * @param array $raw_filters
 * @return array
 */
function bookacti_get_exports( $raw_filters = array() ) {
	$default_filters = array(
		'export_ids' => array(),
		'types' => array(),
		'user_ids' => array(),
		'expiration_delay' => 0,	// INT or FALSE (0 for non-expired, INT for expire in n days, FALSE for all)
		'active_only' => 1			// 1 for active only, 0 for all
	);
	$filters = wp_parse_args( $raw_filters, $default_filters );
	
	global $wpdb;
	
	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_EXPORTS . ' as XP WHERE TRUE ';
	
	$variables = array();
	$exports = array();
	
	if( $filters[ 'export_ids' ] ) {
		$query .= ' AND XP.id IN ( %d ';
		$array_count = count( $filters[ 'export_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'export_ids' ] );
	}
	
	if( $filters[ 'types' ] ) {
		$query .= ' AND XP.types IN ( %s ';
		$array_count = count( $filters[ 'types' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'types' ] );
	}
	
	if( $filters[ 'user_ids' ] ) {
		$query .= ' AND XP.user_id IN ( %d ';
		$array_count = count( $filters[ 'user_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'user_ids' ] );
	}
	
	if( is_numeric( $filters[ 'expiration_delay' ] ) ) {
		$query .= ' AND XP.expiration_date >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY ) ';
		$variables[] = $filters[ 'expiration_delay' ];
	}
	
	if( $filters[ 'active_only' ] ) {
		$query .= ' AND XP.active = 1 ';
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$results = $wpdb->get_results( $query, ARRAY_A );
	
	if( ! $results ) { return $exports; }
	
	// Index by ID
	foreach( $results as $result ) {
		$exports[ $result[ 'id' ] ] = array();
		foreach( $result as $key => $value ) {
			$exports[ $result[ 'id' ] ][ $key ] = maybe_unserialize( $value );
		}
	}
	
	return $exports;
}


/**
 * Get an export by ID
 * @since 1.8.0
 * @param int $export_id
 * @param array $filters
 * @return array|false
 */
function bookacti_get_export( $export_id, $filters = array() ) {
	$filters[ 'export_ids' ] = array( $export_id );
	$exports = bookacti_get_exports( $filters );
	
	if( ! $exports ) { return $exports; }
	if( empty( $exports[ $export_id ] ) ) { return false; }
	
	return $exports[ $export_id ];
}


/**
 * Insert a new export
 * @since 1.8.0
 * @global wpdb $wpdb
 * @param array $raw_args
 * @return int|false
 */
function bookacti_insert_export( $raw_args = array() ) {
	$now_dt = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$expiration_delay = apply_filters( 'bookacti_export_expiry_delay', 31 );
	$default_args = array(
		'type' => '',
		'user_id' => 0,
		'creation_date' => date( 'Y-m-d H:i:s' ),
		'expiration_date' => date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_delay . ' days' ) ),
		'sequence' => 0,
		'args' => array(),
		'active' => 1
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_EXPORTS 
			. ' ( user_id, type, args, creation_date, expiration_date, sequence, active ) ' 
			. ' VALUES ( NULLIF( %d, 0 ), %s, %s, %s, NULLIF( %s, "" ), %d, %d )';
	
	$variables = array(
		$args[ 'user_id' ],
		$args[ 'type' ],
		maybe_serialize( $args[ 'args' ] ),
		$args[ 'creation_date' ],
		$args[ 'expiration_date' ],
		$args[ 'sequence' ],
		$args[ 'active' ]
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$inserted = $wpdb->query( $query );
	
	if( ! $inserted ) { return false; }
	
	$export_id = $wpdb->insert_id;
	
	return $export_id;
}


/**
 * Update an export
 * @since 1.8.0
 * @param array $args
 * @return int|false
 */
function bookacti_update_export( $export_id, $raw_args = array() ) {
	$expiration_delay = apply_filters( 'bookacti_export_expiry_delay', 31 );
	$default_args = array(
		'type' => false,
		'user_id' => false,
		'creation_date' => false,
		'expiration_date' => date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_delay . ' days' ) ),
		'sequence_inc' => 1,
		'args' => false,
		'active' => false
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_EXPORTS . ' SET ';
	
	$variables = array();
	
	if( $args[ 'type' ] !== false )				{ $query .= ' type = %s, '; $variables[] = $args[ 'type' ]; }
	if( $args[ 'user_id' ] !== false )			{ $query .= ' user_id = %d, '; $variables[] = $args[ 'user_id' ]; }
	if( $args[ 'creation_date' ] !== false )	{ $query .= ' creation_date = %s, '; $variables[] = $args[ 'creation_date' ]; }
	if( $args[ 'expiration_date' ] !== false )	{ $query .= ' expiration_date = %s, '; $variables[] = $args[ 'expiration_date' ]; }
	if( $args[ 'sequence_inc' ] !== false )		{ $query .= ' sequence = sequence + %d, '; $variables[] = intval( $args[ 'sequence_inc' ] ); }
	if( $args[ 'args' ] !== false )				{ $query .= ' args = %s, '; $variables[] = maybe_serialize( $args[ 'args' ] ); }
	if( $args[ 'active' ] !== false )			{ $query .= ' active = %d, '; $variables[] = $args[ 'active' ]; }
	
	if( ! $variables ) { return 0; }
	
	// Remove trailing comma
	$query = rtrim( $query, ', ' );
	
	$query .= ' WHERE id = %d ';
	$variables[] = $export_id;

	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Delete expired exports
 * @since 1.8.0
 * @global wpdb $wpdb
 * @param array $args_raw
 * @return array|false
 */
function bookacti_delete_exports( $raw_filters = array() ) {
	$default_filters = array(
		'export_ids' => array(),
		'types' => array(),
		'user_ids' => array(),
		'expiration_delay' => false,	// INT or FALSE (0 for expired, INT for expired since n days, FALSE for all)
		'inactive_only' => 0			// 1 for inactive only, 0 for all
	);
	$filters = wp_parse_args( $raw_filters, $default_filters );
	
	global $wpdb;
	
	// Get exports matching the filters
	$query = 'SELECT id FROM ' . BOOKACTI_TABLE_EXPORTS . ' as XP WHERE TRUE ';
	
	$variables = array();
	
	if( $filters[ 'export_ids' ] ) {
		$query .= ' AND XP.id IN ( %d ';
		$array_count = count( $filters[ 'export_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'export_ids' ] );
	}
	
	if( $filters[ 'types' ] ) {
		$query .= ' AND XP.types IN ( %s ';
		$array_count = count( $filters[ 'types' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'types' ] );
	}
	
	if( $filters[ 'user_ids' ] ) {
		$query .= ' AND XP.user_id IN ( %d ';
		$array_count = count( $filters[ 'user_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'user_ids' ] );
	}
	
	if( is_numeric( $filters[ 'expiration_delay' ] ) ) {
		$query .= ' AND XP.expiration_date <= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY ) ';
		$variables[] = intval( $filters[ 'expiration_delay' ] );
	}
	
	if( $filters[ 'inactive_only' ] ) {
		$query .= ' AND XP.active = 0 ';
	}
	
	$query = $wpdb->prepare( $query, $variables );

	$expired_exports = $wpdb->get_results( $query );
	
	$expired_ids = array();
	foreach( $expired_exports as $expired_export ) {
		$expired_ids[] = $expired_export->id;
	}
	
	$expired_ids = apply_filters( 'bookacti_expired_exports_to_delete', $expired_ids );
	$return = $expired_ids;
	
	if( $expired_ids ) {
		$count = count( $expired_ids );
		$ids_placeholder_list = '%d';
		for( $i=1; $i < $count; ++$i ) {
			$ids_placeholder_list .= ', %d';
		}
		
		// Delete expired exports
		$query= 'DELETE FROM ' . BOOKACTI_TABLE_EXPORTS . ' WHERE id IN( ' . $ids_placeholder_list . ' );';
		$query = $wpdb->prepare( $query, $expired_ids );
		$deleted = $wpdb->query( $query );
		
		if( $deleted === false ) { $return = false; }
		
		// Delete bookings meta
		bookacti_delete_metadata( 'export', $expired_ids );

		do_action( 'bookacti_expired_exports_deleted', $expired_ids );
	}
	
	return $return;
}