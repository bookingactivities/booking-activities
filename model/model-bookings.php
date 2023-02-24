<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Book an event
 * @version 1.11.0
 * @global wpdb $wpdb
 * @param array $booking_data Sanitized with bookacti_sanitize_booking_data
 * @return int
 */
function bookacti_insert_booking( $booking_data ) {
	global $wpdb;
	
	// Get the default activity_id
	$activity_id = $booking_data[ 'activity_id' ];
	if( ! $activity_id && $booking_data[ 'event_id' ] ) {
		$query = 'SELECT activity_id FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d;';
		$query = $wpdb->prepare( $query, $booking_data[ 'event_id' ] );
		$activity_id = $wpdb->get_var( $query );
		if( ! $activity_id ) { $activity_id = $booking_data[ 'activity_id' ]; }
	}
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_BOOKINGS
	       . ' ( group_id, activity_id, event_id, user_id, form_id, order_id, event_start, event_end, quantity, state, payment_status, creation_date, expiration_date, active ) ' 
	       . ' VALUES ( NULLIF( %d, 0 ), NULLIF( %d, 0 ), %d, %s, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, %s, %d, %s, %s, %s, NULLIF( %s, "" ), %d ) ';
	
	$variables = array( 
		$booking_data[ 'group_id' ],
		$activity_id,
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
 * @since 1.9.0
 * @version 1.11.0
 * @global wpdb $wpdb
 * @param array $booking_data Sanitized with bookacti_sanitize_booking_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking( $booking_data, $where = array() ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
	       . ' SET '
	       . ' group_id = NULLIF( IFNULL( NULLIF( %d, 0 ), group_id ), -1 ), '
	       . ' user_id = IFNULL( NULLIF( %s, "0" ), user_id ), '
	       . ' form_id = NULLIF( IFNULL( NULLIF( %d, 0 ), form_id ), -1 ), '
	       . ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
	       . ' activity_id = IFNULL( NULLIF( %d, 0 ), activity_id ), '
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
		$booking_data[ 'activity_id' ],
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
	
	$query = apply_filters( 'bookacti_update_booking_query', $wpdb->prepare( $query, $variables ), $booking_data, $where );
	$updated = $wpdb->query( $query );
	
	if( $updated ) {
		do_action( 'bookacti_booking_updated', $booking_data, $where );
	}
	
	return $updated;
}


/**
 * Get bookings according to filters
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return array
 */
function bookacti_get_bookings( $filters ) {
	global $wpdb;
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )               { $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )   { $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )       { $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] ) { $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'event_id' ] ) && $filters[ 'event_id' ] )                   { $filters[ 'in__event_id' ][] = $filters[ 'event_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )                     { $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( $filters[ 'user_id' ] )                                                            { $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query_select = ' SELECT DISTINCT B.id, B.user_id, B.order_id, B.form_id, B.group_id, B.event_id, B.event_start, B.event_end, B.state, B.payment_status, B.creation_date, B.expiration_date, B.quantity, B.active, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id,'
	              . ' E.title as event_title, E.active as event_active,'
	              . ' A.id as activity_id, A.title as activity_title, A.active as activity_active,'
	              . ' T.id as template_id, T.title as template_title, T.active as template_active,'
	              . ' BG.group_date, BG.event_group_id, BG.state as group_state, BG.payment_status as group_payment_status, BG.user_id as group_user_id, BG.order_id as group_order_id, BG.form_id as group_form_id, BG.active as group_active,'
	              . ' EG.category_id, EG.title as group_title, EG.active as event_group_active ';
	
	// Get event / group of event total availability
	$query_select .= $filters[ 'group_by' ] === 'booking_group' ? ', MIN( E.availability ) as availability ' : ', E.availability ';
	
	$query_join = ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
	            . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
	            . ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
	            . ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id '
	            . ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id '
	            . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
		
	$query = $query_select . $query_join . ' WHERE TRUE ';
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND B.event_start <= %s ';
		$variables[] = $filters[ 'to' ];
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
	
	if( $filters[ 'in__event_id' ] ) {
		$query .= ' AND B.event_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_id' ] );
	}
	
	if( $filters[ 'not_in__event_id' ] ) {
		$query .= ' AND ( B.event_id IS NULL OR B.event_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_id' ] );
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
	
	if( $filters[ 'in__order_id' ] ) {
		$query .= ' AND B.order_id IN ( %d ';
		$array_count = count( $filters[ 'in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__order_id' ] );
	}
	
	if( $filters[ 'not_in__order_id' ] ) {
		$query .= ' AND ( B.order_id IS NULL OR B.order_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__order_id' ] );
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
	
	$bookings_array    = array();
	$booking_ids       = array();
	$booking_group_ids = array();
	foreach( $bookings as $booking ) {
		$bookings_array[ $booking->id ] = $booking;
		$booking_ids[] = $booking->id;
		if( $booking->group_id && ! in_array( $booking->group_id, $booking_group_ids, true ) ) {
			$booking_group_ids[] = $booking->group_id;
		}
	}
	
	if( $filters[ 'fetch_meta' ] ) {
		// Get the bookings meta and the booking groups meta
		$bookings_meta       = $booking_ids ? bookacti_get_metadata( 'booking', $booking_ids ) : array();
		$booking_groups_meta = $booking_group_ids ? bookacti_get_metadata( 'booking_group', $booking_group_ids ) : array();
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
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_booking_rows( $filters ) {
	global $wpdb;
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'event_id' ] ) && $filters[ 'event_id' ] )					{ $filters[ 'in__event_id' ][] = $filters[ 'event_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( $filters[ 'user_id' ] )																{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query = ' SELECT COUNT( list_items_count ) FROM ( '
	           . ' SELECT COUNT( DISTINCT B.id ) as list_items_count, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id ';
	
	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id ';
	
	if( $filters[ 'in__event_group_id' ] || $filters[ 'not_in__event_group_id' ] 
	||  $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id ';
	}
	
	if( $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
	}
	
	$query .= ' WHERE TRUE ';
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND B.event_start <= %s ';
		$variables[] = $filters[ 'to' ];
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
	
	if( $filters[ 'in__event_id' ] ) {
		$query .= ' AND B.event_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_id' ] );
	}
	
	if( $filters[ 'not_in__event_id' ] ) {
		$query .= ' AND ( B.event_id IS NULL OR B.event_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_id' ] );
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
	
	if( $filters[ 'in__order_id' ] ) {
		$query .= ' AND B.order_id IN ( %d ';
		$array_count = count( $filters[ 'in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__order_id' ] );
	}
	
	if( $filters[ 'not_in__order_id' ] ) {
		$query .= ' AND ( B.order_id IS NULL OR B.order_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__order_id' ] );
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
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_booking_filters() before
 * @return int
 */
function bookacti_get_number_of_bookings( $filters ) {
	global $wpdb;
	
	// Merge single id to multiple ids array
	if( is_numeric( $filters[ 'booking_id' ] ) && $filters[ 'booking_id' ] )				{ $filters[ 'in__booking_id' ][] = $filters[ 'booking_id' ]; }
	if( is_numeric( $filters[ 'booking_group_id' ] ) && $filters[ 'booking_group_id' ] )	{ $filters[ 'in__booking_group_id' ][] = $filters[ 'booking_group_id' ]; }
	if( is_numeric( $filters[ 'event_group_id' ] ) && $filters[ 'event_group_id' ] )		{ $filters[ 'in__event_group_id' ][] = $filters[ 'event_group_id' ]; }
	if( is_numeric( $filters[ 'group_category_id' ] ) && $filters[ 'group_category_id' ] )	{ $filters[ 'in__group_category_id' ][] = $filters[ 'group_category_id' ]; }
	if( is_numeric( $filters[ 'event_id' ] ) && $filters[ 'event_id' ] )					{ $filters[ 'in__event_id' ][] = $filters[ 'event_id' ]; }
	if( is_numeric( $filters[ 'form_id' ] ) && $filters[ 'form_id' ] )						{ $filters[ 'in__form_id' ][] = $filters[ 'form_id' ]; }
	if( $filters[ 'user_id' ] )																{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query = '';
	
	if( $filters[ 'group_by' ] === 'booking_group' ) {
		$query = ' SELECT SUM( quantity ) FROM ( '
		           . ' SELECT MAX( B.quantity ) as quantity, IF( B.group_id IS NULL, B.id, CONCAT( "G", B.group_id ) ) as unique_group_id ';
	} else {
		$query = ' SELECT SUM( quantity ) FROM ( '
		           . ' SELECT SUM( B.quantity ) as quantity ';
	}
	
	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id ' 
	        . ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id ';
	
	if( $filters[ 'in__event_group_id' ] || $filters[ 'not_in__event_group_id' ] 
	||  $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ON B.group_id = BG.id ';
	}
	
	if( $filters[ 'in__group_category_id' ] || $filters[ 'not_in__group_category_id' ] ) {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id ';
	}
	
	$query .= ' WHERE TRUE ';
	
	$variables = array();
	
	// Do not fetch events out of the desired interval
	if( $filters[ 'from' ] ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND B.event_start <= %s ';
		$variables[] = $filters[ 'to' ];
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
	
	if( $filters[ 'in__event_id' ] ) {
		$query .= ' AND B.event_id IN ( %d ';
		$array_count = count( $filters[ 'in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__event_id' ] );
	}
	
	if( $filters[ 'not_in__event_id' ] ) {
		$query .= ' AND ( B.event_id IS NULL OR B.event_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__event_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__event_id' ] );
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
	
	if( $filters[ 'in__order_id' ] ) {
		$query .= ' AND B.order_id IN ( %d ';
		$array_count = count( $filters[ 'in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__order_id' ] );
	}
	
	if( $filters[ 'not_in__order_id' ] ) {
		$query .= ' AND ( B.order_id IS NULL OR B.order_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__order_id' ] );
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
 * Get number of bookings per event
 * @since 1.12.0 (was bookacti_get_number_of_bookings_for_booking_system)
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates Array of template IDs
 *  @type array $events Array of events IDs
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type array $users Array of users IDs
 *  @type array $status Array of booking status
 * }
 * @return array
 */
function bookacti_get_number_of_bookings_per_event( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'events'    => array(),
		'interval'  => array(),
		'users'     => array(),
		'status'    => array()
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;

	$query = 'SELECT B.event_id, B.event_start, B.event_end, E.availability as total_availability, SUM( B.quantity ) as quantity, COUNT( DISTINCT B.user_id ) as distinct_users, SUM( IF( B.user_id = %s, B.quantity, 0 ) ) as current_user_bookings '
	       . ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
	       . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id '
	       . ' WHERE B.active = 1 ';
	
	$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
	$variables = array( $current_user_id );
	
	// Filter by template
	if( $args[ 'templates' ] ) {
		$query .= ' AND E.template_id IN ( %d ';
		$array_count = count( $args[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}
	
	// Filter by event
	if( $args[ 'events' ] ) {
		$query .= ' AND B.event_id IN ( %d ';
		$array_count = count( $args[ 'events' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'events' ] );
	}
	
	// Do not fetch bookings out of the desired interval
	if( ! empty( $args[ 'interval' ][ 'start' ] ) ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $args[ 'interval' ][ 'start' ];
	}
	
	if( ! empty( $args[ 'interval' ][ 'end' ] ) ) {
		$query .= ' AND B.event_start <= %s ';
		$variables[] = $args[ 'interval' ][ 'end' ];
	}
	
	// Filter by user
	if( $args[ 'users' ] ) {
		$query .= ' AND B.user_id IN ( %s ';
		$array_count = count( $args[ 'users' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'users' ] );
	}
	
	// Filter by status
	if( $args[ 'status' ] ) {
		$query .= ' AND B.state IN ( %s ';
		$array_count = count( $args[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'status' ] );
	}
	
	$query .= ' GROUP BY B.event_id, B.event_start, B.event_end '
	        . ' ORDER BY B.event_id, B.event_start, B.event_end ';
	
	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	
	$query = apply_filters( 'bookacti_get_number_of_bookings_per_event_query', $query, $args );
	
	$events_booking_data = $wpdb->get_results( $query );
	
	// Order the array by event id
	$return_array = array();
	foreach( $events_booking_data as $event_booking_data ) {
		$event_id = $event_booking_data->event_id;
		if( ! isset( $return_array[ $event_id ] ) ) { $return_array[ $event_id ] = array(); }
		$return_array[ $event_id ][ $event_booking_data->event_start ] = array(
			'total_availability'    => intval( $event_booking_data->total_availability ),
			'availability'          => intval( $event_booking_data->total_availability ) - intval( $event_booking_data->quantity ),
			'quantity'              => intval( $event_booking_data->quantity ),
			'distinct_users'        => intval( $event_booking_data->distinct_users ),
			'current_user_bookings' => intval( $event_booking_data->current_user_bookings )
		);
	}
	
	return apply_filters( 'bookacti_get_number_of_bookings_per_event', $return_array, $args, $query );
}


/**
 * Get number of bookings for the desired events, per event and per user, with the total availability
 * @since 1.9.2
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $events [ [ 'id' => int, 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' ], ... ]
 * @return array
 */
function bookacti_get_number_of_bookings_per_event_per_user( $events ) {
	if( ! $events ) { return array(); }
	
	global $wpdb;
	
	$variables = array();
	
	// Prepare the SQL query conditions to filter for the desired events
	$event_ids = array();
	$event_ids_placeholders = array();
	$events_query = '';
	$i = 0;
	foreach( $events as $event ) {
		if( $i !== 0 ) { $events_query .= ' OR '; }
		$events_query .= '( B.event_id = %d AND B.event_start = %s AND B.event_end = %s )';
		$variables = array_merge( $variables, array( $event[ 'id' ], $event[ 'start' ], $event[ 'end' ] ) );
		if( ! in_array( $event[ 'id' ], $event_ids, true ) ) {
			$event_ids_placeholders[] = '%d';
			$event_ids[] = $event[ 'id' ];
		}
		++$i;
	}
	$variables = array_merge( $variables, $event_ids );
	
	// Retrieve the quantity booked per event, per user
	$query = 'SELECT E.id as event_id, B.event_start, B.event_end, B.user_id, E.availability as total_availability, SUM( B.quantity ) as quantity '
	       . ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
	       . ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKINGS . ' as B ON B.event_id = E.id AND B.active = 1 AND ( ' . $events_query . ' ) '
	       . ' WHERE E.id IN ( ' . implode( ', ', $event_ids_placeholders ) . ' ) '
	       . ' GROUP BY E.id, B.event_start, B.event_end, B.user_id  '
	       . ' ORDER BY E.id, B.event_start, B.event_end, B.user_id  ';
	
	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	
	$query = apply_filters( 'bookacti_number_of_bookings_per_event_per_user_query', $query, $events );
	$events_booking_data = $wpdb->get_results( $query );
	
	// Order the array by event id
	$return_events = array();
	foreach( $events as $i => $event ) {
		// Default values
		$return_event = $event;
		$return_event[ 'bookings_nb_per_user' ] = array();
		$return_event[ 'total_availability' ] = 0;
		
		// Fill values
		foreach( $events_booking_data as $event_booking_data ) {
			if( intval( $event_booking_data->event_id ) === intval( $event[ 'id' ] ) && ! $return_event[ 'total_availability' ] ) {
				$return_event[ 'total_availability' ] = $event_booking_data->total_availability;
			}
			
			if( intval( $event_booking_data->event_id ) === intval( $event[ 'id' ] )
			&&  $event_booking_data->event_start === $event[ 'start' ]
			&&  $event_booking_data->event_end === $event[ 'end' ] ) {
				if( ! isset( $return_event[ 'bookings_nb_per_user' ][ $event_booking_data->user_id ] ) ) {
					$return_event[ 'bookings_nb_per_user' ][ $event_booking_data->user_id ] = 0;
				}
				$return_event[ 'bookings_nb_per_user' ][ $event_booking_data->user_id ] += $event_booking_data->quantity;
			}
		}
		
		$return_events[ $i ] = $return_event;
	}
	
	return apply_filters( 'bookacti_number_of_bookings_per_event_per_user', $return_events, $events );
}


/**
 * Cancel a booking
 * @version 1.9.0
 * @global wpdb $wpdb
 * @param int $booking_id
 * @return int|false
 */
function bookacti_cancel_booking( $booking_id ) {
	global $wpdb;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = "cancelled", active = 0 WHERE id = %d AND active = 1';
	$prep  = $wpdb->prepare( $query, $booking_id );
	$cancelled = $wpdb->query( $prep );

	return $cancelled;
}


/**
 * Cancel all bookings of an event
 * @since 1.9.0
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param int $event_id
 * @param array $filters
 * @return int|false
 */
function bookacti_cancel_event_bookings( $event_id, $filters = array() ) {
	global $wpdb;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
	       . ' SET B.state = "cancelled", B.active = 0 '
	       . ' WHERE B.event_id = %d ';
	
	$variables = array( $event_id );
	
	// Active only (by default)
	$active = isset( $filters[ 'active' ] ) ? $filters[ 'active' ] : 1;
	if( in_array( $active, array( 0, 1, '0', '1' ), true ) ) { $query .= ' AND B.active = %d '; $variables[] = $active; }
	
	// Future only (by default)
	$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$now_dt   = new DateTime( 'now', $timezone );
	$from     = isset( $filters[ 'from' ] ) ? $filters[ 'from' ] : $now_dt->format( 'Y-m-d H:i:s' );
	if( $from ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $from;
	}
	
	// Only certain bookings
	if( ! empty( $filters[ 'in__booking_id' ] ) ) {
		$query .= ' AND B.id IN ( %d ';
		$array_count = count( $filters[ 'in__booking_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__booking_id' ] );
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$cancelled = $wpdb->query( $query );
	
	return $cancelled;
}


/** 
 * Cancel all bookings of a group of events (both booking groups and their bookings)
 * @since 1.9.0
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param int $event_group_id
 * @param array $filters
 * @return int
 */
function bookacti_cancel_group_of_events_bookings( $event_group_id, $filters = array() ) {
	global $wpdb;

	// Booking Groups
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG '
	       . ' SET BG.state = "cancelled", BG.active = 0 '
	       . ' WHERE BG.event_group_id = %d ';
	
	$variables = array( $event_group_id );
	
	// Active only (by default)
	$active = isset( $filters[ 'active' ] ) ? $filters[ 'active' ] : 1;
	if( in_array( $active, array( 0, 1, '0', '1' ), true ) ) { $query .= ' AND BG.active = %d '; $variables[] = $active; }
	
	// Future only (by default)
	$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$now_dt   = new DateTime( 'now', $timezone );
	$from     = isset( $filters[ 'from' ] ) ? $filters[ 'from' ] : $now_dt->format( 'Y-m-d H:i:s' );
	if( $from ) {
		// Check if the last active booking of the group starts after the desired datetime
		$query	.= ' AND ( SELECT MAX( B.event_start ) as last_event_start FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.group_id = BG.id AND B.active = 1 ) >= %s ';
		$variables[] = $from;
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$cancelled1 = $wpdb->query( $query );
	
	// Single Bookings
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
	       . ' LEFT JOIN ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as G ON B.group_id = G.id '
	       . ' SET B.state = "cancelled", B.active = 0 '
	       . ' WHERE G.event_group_id = %d ';
	
	$variables = array( $event_group_id );
	
	// Active only (by default)
	if( in_array( $active, array( 0, 1, '0', '1' ), true ) ) { $query .= ' AND B.active = %d '; $variables[] = $active; }
	
	// Future only (by default)
	if( $from ) {
		$query .= ' AND B.event_start >= %s ';
		$variables[] = $from;
	}
	
	$query		= $wpdb->prepare( $query, $variables );
	$cancelled2	= $wpdb->query( $query );
	
	return intval( $cancelled1 ) + intval( $cancelled2 );
}


/** 
 * Replace all bookings (groups) user_id with another ID
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param int|string $user_id
 * @param int|string $old_user_id
 * @param int|false $expiration_date_delay
 * @return false|int
 */
function bookacti_update_bookings_user_id( $user_id, $old_user_id, $expiration_date_delay = false ) {
	global $wpdb;
	
	// Single Bookings
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
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
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS 
	       . ' SET user_id = %s '
	       . ' WHERE user_id = %s ';
	$query = $wpdb->prepare( $query, $user_id, $old_user_id );
	$updated2 = $wpdb->query( $query );
	
	if( $updated1 === false || $updated2 === false ) { return false; }
	
	return $updated1 + $updated2;
}


/** 
 * Update bookings event_id 
 * @since 1.10.0
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param int $old_event_id
 * @param int $new_event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $from Y-m-d H:i:s
 * @param string $to Y-m-d H:i:s
 * @return int|false
 */
function bookacti_update_bookings_event_id( $old_event_id, $new_event_id, $event_start = '', $event_end = '', $from = '', $to = '' ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
	       . ' SET event_id = %d '
	       . ' WHERE event_id = %d ';
	
	$variables = array( $new_event_id, $old_event_id );
	
	if( $event_start ) {
		$query .= ' AND event_start = %s ';
		$variables[] = $event_start;
	}

	if( $event_end ) {
		$query .= ' AND event_end = %s ';
		$variables[] = $event_end;
	}
	
	if( $from ) {
		$query .= ' AND event_start >= %s ';
		$variables[] = $from;
	}
	
	if( $to ) {
		$query .= ' AND event_start <= %s ';
		$variables[] = $to;
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
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
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
				. ' WHERE id = %d';
	$prep  = $wpdb->prepare( $query, $state, $active, $booking_id );
	$updated = $wpdb->query( $prep );
	
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
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
	       . ' SET payment_status = %s '
	       . ' WHERE id = %d';
	$prep  = $wpdb->prepare( $query, $status, $booking_id );
	$updated = $wpdb->query( $prep );
	
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
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET quantity = %d WHERE id = %d';
	$prep  = $wpdb->prepare( $query, $quantity, $booking_id );
	$updated = $wpdb->query( $prep );
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
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET event_id = %d, event_start = %s, event_end = %s WHERE id = %d';
	$prep  = $wpdb->prepare( $query, $event_id, $event_start, $event_end, $booking_id );
	$updated = $wpdb->query( $prep );
	
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
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d ';
	$query = $wpdb->prepare( $query, $booking_id );
	$deleted = $wpdb->query( $query );
	
	if( $deleted ) {
		// Delete booking group metadata
		bookacti_delete_metadata( 'booking', $booking_id );
	}
	
	return $deleted;
}


/**
 * Update specific bookings dates with a relative amount of seconds
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param array $booking_ids
 * @param int $delta_seconds_start
 * @param int $delta_seconds_end
 * @return int|false
 */
function bookacti_shift_bookings_dates( $booking_ids, $delta_seconds_start = 0, $delta_seconds_end = 0 ) {
	global $wpdb;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS
	       . ' SET  event_start = DATE_ADD( event_start, INTERVAL %d SECOND ), '
	             .  ' event_end = DATE_ADD( event_end, INTERVAL %d SECOND ) '
	       . ' WHERE id IN ( ';
	
	$variables = array( $delta_seconds_start, $delta_seconds_end );
	
	if( $booking_ids ) {
		$query .= '%d';
		for( $i=1,$len=count($booking_ids); $i<$len; ++$i ) {
			$query .= ', %d';
		}
		$variables = array_merge( $variables, $booking_ids );
	}
	$query .= ' ) ';
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

	return $updated;   
}




// BOOKING GROUPS

/**
 * Insert a booking group
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @return int
 */
function bookacti_insert_booking_group( $booking_group_data ) {
	global $wpdb;
	
	// Get the default category_id
	$category_id = $booking_group_data[ 'category_id' ];
	if( ! $category_id && $booking_group_data[ 'event_group_id' ] ) {
		$query = 'SELECT category_id FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' WHERE id = %d;';
		$query = $wpdb->prepare( $query, $booking_group_data[ 'event_group_id' ] );
		$category_id = $wpdb->get_var( $query );
		if( ! $category_id ) { $category_id = $booking_group_data[ 'category_id' ]; }
	}

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_BOOKING_GROUPS
	       . ' ( group_date, category_id, event_group_id, user_id, form_id, order_id, state, payment_status, active ) ' 
	       . ' VALUES ( %s, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, NULLIF( %d, 0 ), NULLIF( %d, 0 ), %s, %s, %d )';

	$variables = array( 
		$booking_group_data[ 'group_date' ],
		$category_id,
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
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking_group( $booking_group_data, $where = array() ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS
	       . ' SET '
	       . ' group_date = IFNULL( NULLIF( %s, "" ), group_date ), '
	       . ' category_id = NULLIF( IFNULL( NULLIF( %d, 0 ), category_id ), -1 ), '
	       . ' event_group_id = NULLIF( IFNULL( NULLIF( %d, 0 ), event_group_id ), -1 ), '
	       . ' user_id = IFNULL( NULLIF( %s, "0" ), user_id ), '
	       . ' form_id = NULLIF( IFNULL( NULLIF( %d, 0 ), form_id ), -1 ), '
	       . ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
	       . ' state = IFNULL( NULLIF( %s, "" ), state ), '
	       . ' payment_status = IFNULL( NULLIF( %s, "" ), payment_status ), '
	       . ' active = IFNULL( NULLIF( %d, -1 ), active ) '
	       . ' WHERE id = %d ';
	
	$variables = array( 
		$booking_group_data[ 'group_date' ],
		! is_null( $booking_group_data[ 'category_id' ] ) ? $booking_group_data[ 'category_id' ] : -1,
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
	
	$query = apply_filters( 'bookacti_update_booking_group_query', $wpdb->prepare( $query, $variables ), $booking_group_data, $where );
	$updated = $wpdb->query( $query );
	
	if( $updated ) {
		do_action( 'bookacti_booking_group_updated', $booking_group_data, $where );
	}
	
	return $updated;
}


/** 
 * Update booking groups event_group_id 
 * @since 1.12.0
 * @version 1.15.6
 * @global wpdb $wpdb
 * @param int $old_event_group_id
 * @param int $new_event_group_id
 * @param string $group_date Y-m-d
 * @param string $from Y-m-d
 * @param string $to Y-m-d
 * @return int|false
 */
function bookacti_update_booking_groups_event_group_id( $old_event_group_id, $new_event_group_id, $group_date = '', $from = '', $to = '' ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS
	       . ' SET event_group_id = %d '
	       . ' WHERE event_group_id = %d ';
	
	$variables = array( $new_event_group_id, $old_event_group_id );
	
	if( $group_date ) {
		$query .= ' AND group_date = %s ';
		$variables[] = $group_date;
	}

	if( $from ) {
		$query .= ' AND group_date >= %s ';
		$variables[] = $from;
	}
	
	if( $to ) {
		$query .= ' AND group_date <= %s ';
		$variables[] = $to;
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Update booking group state
 * @since 1.1.0
 * @version 1.10.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param string $state
 * @param 0|1|'auto' $active
 * @param boolean $update_bookings Whether to updates bookings state of the group.
 * @param boolean $old_state The bookings must be have the given status.
 * @return int|boolean|null
 */
function bookacti_update_booking_group_state( $booking_group_id, $state, $active = 'auto', $update_bookings = false, $old_state = '' ) {
	global $wpdb;

	if( $active === 'auto' ) {
		$active = in_array( $state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
	}

	$query1 = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
	        . ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
	        . ' WHERE id = %d';
	$prep1  = $wpdb->prepare( $query1, $state, $active, $booking_group_id );
	$updated1 = $wpdb->query( $prep1 );

	$updated = $updated1;

	if( $update_bookings ) {
		$updated2 = bookacti_update_booking_group_bookings_state( $booking_group_id, $state, $active, $old_state );

		if( is_int( $updated1 ) && is_int( $updated2 ) ) {
			$updated = $updated1 + $updated2;
		} else {
			$updated = false;
		}
	}

	return $updated;
}


/**
 * Update booking group payment status
 * @since 1.3.0
 * @version 1.10.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @param string $state
 * @param 0|1|'auto' $active
 * @param boolean $update_bookings Whether to updates bookings state of the group.
 * @param string $old_status The bookings payment status must be the same as the given one.
 * @return int|boolean
 */
function bookacti_update_booking_group_payment_status( $booking_group_id, $status, $update_bookings = false, $old_status = '' ) {
	global $wpdb;

	$query1 = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' '
	        . ' SET payment_status = %s '
	        . ' WHERE id = %d';
	$prep1  = $wpdb->prepare( $query1, $status, $booking_group_id );
	$updated1 = $wpdb->query( $prep1 );

	$updated = $updated1;

	if( $update_bookings ) {
		$updated2 = bookacti_update_booking_group_bookings_payment_status( $booking_group_id, $status, $old_status );

		if( is_int( $updated1 ) && is_int( $updated2 ) ) {
			$updated = $updated1 + $updated2;
		} else {
			$updated = false;
		}
	}

	return $updated;
}


/**
 * Update booking group bookings
 * @since 1.9.0
 * @global wpdb $wpdb
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @param array $where
 * @return int|false
 */
function bookacti_update_booking_group_bookings( $booking_group_data, $where = array() ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
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
	
	$query = apply_filters( 'bookacti_update_booking_group_bookings_query', $wpdb->prepare( $query, $variables ), $booking_group_data, $where );
	$updated = $wpdb->query( $query );
	
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
	$query_bookings = 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS 
	                . ' WHERE group_id = %d ';

	$variables_bookings = array( $booking_group_id );

	if( ! empty( $where_state ) ) {
		$query_bookings .= ' AND state = %s ';
		$variables_bookings[] = $where_state;
	}

	$prep_bookings = $wpdb->prepare( $query_bookings, $variables_bookings );
	$bookings = $wpdb->get_results( $prep_bookings, OBJECT );

	if( empty( $bookings ) ) {
		return 0;
	}

	// Change bundled bookings state
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
	       . ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
	       . ' WHERE group_id = %d';

	$variables_array = array( $state, $active, $booking_group_id );

	if( ! empty( $where_state ) ) {
		$query .= ' AND state = %s ';
		$variables_array[] = $where_state;
	}

	$prep = $wpdb->prepare( $query, $variables_array );
	$updated = $wpdb->query( $prep );

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
	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
	       . ' SET payment_status = %s '
	       . ' WHERE group_id = %d';

	$variables_array = array( $state, $booking_group_id );

	if( ! empty( $where_state ) ) {
		$query	.= ' AND payment_status = %s ';
		$variables_array[] = $where_state;
	}

	$prep = $wpdb->prepare( $query, $variables_array );
	$updated = $wpdb->query( $prep );

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

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
	       . ' SET quantity = %d ';

	if( $add_quantity ) {
		$query .= '+ quantity';
	}

	$query .= ' WHERE group_id = %d';

	$prep = $wpdb->prepare( $query, $quantity, $booking_group_id );
	$updated = $wpdb->query( $prep );

	return $updated;
}


/**
 * Get booking groups according to filters
 * @since 1.3.0 (was bookacti_get_booking_groups_by_group_of_events)
 * @version 1.15.8
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
	if( $filters[ 'user_id' ] )																{ $filters[ 'in__user_id' ][] = $filters[ 'user_id' ]; }
	
	$query = 'SELECT BG.id, BG.group_date, BG.event_group_id, BG.user_id, BG.order_id, BG.form_id, BG.state, BG.payment_status, BG.active, IFNULL( NULLIF( BG.category_id, 0 ), EG.category_id ) as category_id,'
	       . ' EG.title as group_title, EG.active as event_group_active,'
	       . ' C.title as category_title, C.template_id, C.active as category_active,'
	       . ' B.start, B.end, B.last_start, B.quantity, B.bookings_nb, B.booking_ids ';

	$query .= ' FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG ' 
	       . ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id '
	       . ' LEFT JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON IFNULL( NULLIF( BG.category_id, 0 ), EG.category_id ) = C.id ';

	// Get the first and the last event of the booking group and keep respectively their start and end datetime
	// Get the max booking quantity
	$query .= ' LEFT JOIN ( '
	           . ' SELECT group_id as booking_group_id, JSON_ARRAYAGG( id ) as booking_ids, COUNT( id ) as bookings_nb, MAX( quantity ) as quantity, MIN( event_start ) as start, MAX( event_end ) as end, MAX( event_start ) as last_start '
	           . ' FROM ' . BOOKACTI_TABLE_BOOKINGS 
	           . ' GROUP BY group_id'
	       . ' ) as B ON BG.id = B.booking_group_id ';

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
	
	if( $filters[ 'in__order_id' ] ) {
		$query .= ' AND BG.order_id IN ( %d ';
		$array_count = count( $filters[ 'in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__order_id' ] );
	}
	
	if( $filters[ 'not_in__order_id' ] ) {
		$query .= ' AND ( BG.order_id IS NULL OR BG.order_id NOT IN ( %d ';
		$array_count = count( $filters[ 'not_in__order_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ) ';
		$variables = array_merge( $variables, $filters[ 'not_in__order_id' ] );
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

	$booking_groups_array = array();
	$booking_group_ids = array();
	foreach( $booking_groups as $booking_group ) {
		$booking_group->booking_ids = bookacti_ids_to_array( bookacti_maybe_decode_json( $booking_group->booking_ids, true ) );
		sort( $booking_group->booking_ids );
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
 * Get ids of bookings included in a booking group
 * @since 1.1.0
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return array
 */
function bookacti_get_booking_group_bookings_ids( $booking_group_id ) {
	global $wpdb;

	$query = 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS
	       . ' WHERE group_id = %d '
	       . ' ORDER BY id DESC';
	$prep  = $wpdb->prepare( $query, $booking_group_id );
	$bookings = $wpdb->get_results( $prep, OBJECT );

	$booking_ids = array();
	foreach( $bookings as $booking ) {
		$booking_ids[] = $booking->id;
	}

	return $booking_ids;
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
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE id = %d';
	$query = $wpdb->prepare( $query, $booking_group_id );
	$deleted = $wpdb->query( $query );

	// Delete booking group metadata
	if( $deleted ) {
		bookacti_delete_metadata( 'booking_group', $booking_group_id );
	}

	return $deleted;
}


/**
 * Delete the bookings of a booking group 
 * @since 1.5.0
 * @version 1.15.8
 * @global wpdb $wpdb
 * @param int $booking_group_id
 * @return int|false
 */
function bookacti_delete_booking_group_bookings( $booking_group_id ) {
	global $wpdb;

	// Delete bookings metadata
	$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );

	// Delete bookings
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE group_id = %d ';
	$query = $wpdb->prepare( $query, $booking_group_id );
	$deleted = $wpdb->query( $query );
	
	if( $deleted && $booking_ids ) {
		$query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = "booking" AND object_id IN( %d';
		for( $i=1,$len=count($booking_ids); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$query	= $wpdb->prepare( $query, $booking_ids );
		$wpdb->query( $query );
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
		'export_ids'       => array(),
		'types'            => array(),
		'user_ids'         => array(),
		'expiration_delay' => 0, // INT or FALSE (0 for non-expired, INT for expire in n days, FALSE for all)
		'active_only'      => 1  // 1 for active only, 0 for all
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
		'type'            => '',
		'user_id'         => 0,
		'creation_date'   => date( 'Y-m-d H:i:s' ),
		'expiration_date' => date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_delay . ' days' ) ),
		'sequence'        => 0,
		'args'            => array(),
		'active'          => 1
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
		'type'            => false,
		'user_id'         => false,
		'creation_date'   => false,
		'expiration_date' => date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_delay . ' days' ) ),
		'sequence_inc'    => 1,
		'args'            => false,
		'active'          => false
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_EXPORTS . ' SET ';
	
	$variables = array();
	
	if( $args[ 'type' ] !== false )            { $query .= ' type = %s, '; $variables[] = $args[ 'type' ]; }
	if( $args[ 'user_id' ] !== false )         { $query .= ' user_id = %d, '; $variables[] = $args[ 'user_id' ]; }
	if( $args[ 'creation_date' ] !== false )   { $query .= ' creation_date = %s, '; $variables[] = $args[ 'creation_date' ]; }
	if( $args[ 'expiration_date' ] !== false ) { $query .= ' expiration_date = %s, '; $variables[] = $args[ 'expiration_date' ]; }
	if( $args[ 'sequence_inc' ] !== false )    { $query .= ' sequence = sequence + %d, '; $variables[] = intval( $args[ 'sequence_inc' ] ); }
	if( $args[ 'args' ] !== false )            { $query .= ' args = %s, '; $variables[] = maybe_serialize( $args[ 'args' ] ); }
	if( $args[ 'active' ] !== false )          { $query .= ' active = %d, '; $variables[] = $args[ 'active' ]; }
	
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
		'export_ids'       => array(),
		'types'            => array(),
		'user_ids'         => array(),
		'expiration_delay' => false, // INT or FALSE (0 for expired, INT for expired since n days, FALSE for all)
		'inactive_only'    => 0      // 1 for inactive only, 0 for all
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
		$query = 'DELETE FROM ' . BOOKACTI_TABLE_EXPORTS . ' WHERE id IN( ' . $ids_placeholder_list . ' );';
		$query = $wpdb->prepare( $query, $expired_ids );
		$deleted = $wpdb->query( $query );
		
		if( $deleted === false ) { $return = false; }
		
		// Delete bookings meta
		bookacti_delete_metadata( 'export', $expired_ids );

		do_action( 'bookacti_expired_exports_deleted', $expired_ids );
	}
	
	return $return;
}


/**
 * Get the booking id corresponding to a secret key
 * @since 1.12.0
 * @param string $secret_key
 * @return array [ "id" => int, "type" => string ]
 */
function bookacti_get_booking_id_by_secret_key( $secret_key ) {
	global $wpdb;
	$query = 'SELECT object_type, object_id FROM ' . BOOKACTI_TABLE_META 
	       . ' WHERE meta_key = "secret_key" '
	       . ' AND object_type IN ( "booking", "booking_group" )'
	       . ' AND meta_value = %s;';
	$query = $wpdb->prepare( $query, $secret_key );
	$booking = $wpdb->get_row( $query );
	return $booking ? array( 'id' => intval( $booking->object_id ), 'type' => $booking->object_type === 'booking_group' ? 'group' : 'single' ) : array();
}