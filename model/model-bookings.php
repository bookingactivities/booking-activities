<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Book an event
 * 
 * @since 1.0.0
 * @version 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $user_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param int $quantity
 * @param string $state
 * @param string $expiration_date
 * @param int $booking_group_id
 * @return int|null
 */
function bookacti_insert_booking( $user_id, $event_id, $event_start, $event_end, $quantity, $state, $expiration_date = NULL, $booking_group_id = NULL ) {
	global $wpdb;
	$return_booking = array();

	$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;

	$wpdb->insert( 
		BOOKACTI_TABLE_BOOKINGS, 
		array( 
			'group_id'			=> $booking_group_id,
			'event_id'			=> $event_id,
			'user_id'			=> $user_id,
			'event_start'		=> $event_start,
			'event_end'			=> $event_end,
			'quantity'			=> $quantity,
			'state'				=> $state,
			'expiration_date'	=> $expiration_date,
			'active'			=> $active
		),
		array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
	);
	
	$booking_id = $wpdb->insert_id;
	
	if( $booking_id !== false ) {
		do_action( 'bookacti_booking_inserted', $booking_id );
	}
	
	return $booking_id;
}


/**
 * Check if a booking exists and return its id
 * 
 * @since 1.0.0
 * @version 1.1.0
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
			. ' WHERE user_id = %d '
			. ' AND event_id = %d '
			. ' AND event_start = %s '
			. ' AND event_end = %s '
			. ' AND state = %s '
			. ' AND expiration_date > UTC_TIMESTAMP() '
			. ' AND active = 1 ';
	
	$variables = array( $user_id, $event_id, $event_start, $event_end, $state );
	
	if( $booking_group_id !== NULL ) {
		if( $booking_group_id === 0 ) {
			$bookings_query  .= ' AND group_id IS NULL ';
		} else if( is_int( $booking_group_id ) && $booking_group_id > 0 ) {
			$bookings_query  .= ' AND group_id = %d ';
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


// UPDATE BOOKING QUNATITY IF POSSIBLE
/**
 * Update booking qunatity if possible
 * 
 * @global wpdb $wpdb
 * @param int $booking_id
 * @param int $new_quantity
 * @param string $expiration_date
 * @return array
 */
function bookacti_update_booking_quantity( $booking_id, $new_quantity, $expiration_date = '' ) {
	global $wpdb;
	
	$booking = bookacti_get_booking_by_id( $booking_id );
	
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
			'expiration_date' => $expiration_date
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

			if( $new_quantity > 0 && $new_quantity === $old_quantity ) {
				do_action( 'bookacti_booking_restored', $booking_id );
			} else if( $new_quantity > 0 && intval( $data[ 'quantity' ] ) !== $old_quantity ) {
				do_action( 'bookacti_booking_quantity_updated', $booking_id );
			} else {
				do_action( 'bookacti_booking_state_changed', $booking_id, 'removed', array() );
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
 * Get all bookings of a template or an event
 * 
 * @version 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $template_id
 * @param int $booking_group_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param boolean $active_only
 * @param array $state_not_in
 * @return array
 */
function bookacti_get_bookings( $template_id = NULL, $booking_group_id = NULL, $event_id = NULL, $event_start = NULL, $event_end = NULL, $active_only = true, $state_not_in = array() ) {
	global $wpdb;

	$bookings_query = ' SELECT B.* ' 
					. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE B.event_id = E.id ';

	if( $active_only ) {
		$bookings_query .= ' AND B.active = 1 ';
	}

	$array_of_variables = array();

	if( ! empty( $state_not_in ) ) {
		$bookings_query .= ' AND B.state NOT IN ( %s ';
		if( count( $state_not_in ) >= 2 )  {
			for( $i = 0; $i < count( $state_not_in ) - 1; $i++ ) {
				$bookings_query  .= ', %s ';
			}
		}
		$bookings_query  .= ') ';
		$array_of_variables = array_merge( $array_of_variables, $state_not_in );
	}

	if( $booking_group_id !== NULL ) {
		if( $booking_group_id === 0 ) {
			$bookings_query  .= ' AND B.group_id IS NULL ';
		} else if( is_int( $booking_group_id ) && $booking_group_id > 0 ) {
			$bookings_query  .= ' AND B.group_id = %d ';
			$array_of_variables[] =  $booking_group_id;
		}
	}
	
	if( $event_id ) {
		$bookings_query .= ' AND B.event_id = %d ';
		$array_of_variables[] = $event_id;
	}

	if( $template_id ) {
		$bookings_query .= ' AND E.template_id = %d ';
		$array_of_variables[] =  $template_id;
	}

	if( $event_start ) {
		$bookings_query .= ' AND B.event_start = %s ';
		$array_of_variables[] =  $event_start;
	}

	if( $event_end ) {
		$bookings_query .= ' AND B.event_end = %s ';
		$array_of_variables[] =  $event_end;
	}

	$bookings_query .= ' ORDER BY B.event_start, B.event_end ASC ';

	$prepare_bookings_query = $wpdb->prepare( $bookings_query, $array_of_variables );
	$bookings = $wpdb->get_results( $prepare_bookings_query, OBJECT );
	
	return $bookings;
}


// GET NUMBER OF BOOKINGS
function bookacti_get_number_of_bookings( $event_id, $event_start = NULL, $event_end = NULL ) {
	global $wpdb;

	if( $event_start !== NULL && $event_end !== NULL ) {
		$bookings_query = 'SELECT SUM(quantity) FROM ' . BOOKACTI_TABLE_BOOKINGS
						. ' WHERE event_id = %d'
						. ' AND event_start = %s'
						. ' AND event_end = %s'
						. ' AND active = 1';
		$bookings_prep = $wpdb->prepare( $bookings_query, $event_id, $event_start, $event_end );
	} else {
		$bookings_query = 'SELECT SUM(quantity) FROM ' . BOOKACTI_TABLE_BOOKINGS
						. ' WHERE event_id = %d'
						. ' AND active = 1';
		$bookings_prep = $wpdb->prepare( $bookings_query, $event_id );
	}

	$bookings = $wpdb->get_var( $bookings_prep );

	if( is_null( $bookings ) ) { $bookings = 0; }

	return $bookings;
}


// CHECK IF BOOKING IS ACTIVE
function bookacti_is_booking_active( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT active FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$active	= $wpdb->get_var( $prep );

	return $active;
}


// GET ACTIVITY BY BOOKING
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


// GET TEMPLATE BY BOOKING
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


// GET BOOKING
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


// GET BOOKING DATA
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


// GET BOOKING BY USER ID
function bookacti_get_bookings_by_user_id( $user_id = null ) {
	
	$user_id = $user_id ? $user_id : get_current_user_id();
	
	global $wpdb;

	$query		= 'SELECT B.*, E.title, E.activity_id, E.template_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
				. ' LEFT JOIN (
						SELECT id, title, activity_id, template_id FROM ' . BOOKACTI_TABLE_EVENTS . '
					) as E ON B.event_id = E.id'
				. ' WHERE B.user_id = %d ORDER BY id DESC';
	$prep		= $wpdb->prepare( $query, $user_id );
	$booking	= $wpdb->get_results( $prep, OBJECT );

	return $booking;
}


// GET BOOKING STATE
function bookacti_get_booking_state( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT state FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$state	= $wpdb->get_var( $prep );

	return $state;
}


// GET BOOKING OWNER
function bookacti_get_booking_owner( $booking_id ) {
	global $wpdb;

	$query	= 'SELECT user_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep	= $wpdb->prepare( $query, $booking_id );
	$owner	= $wpdb->get_var( $prep );

	return $owner;
}


// CANCEL A BOOKING
function bookacti_cancel_booking( $booking_id ) {
	global $wpdb;

	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = "cancelled", active = 0 WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$cancelled	= $wpdb->query( $prep );

	return $cancelled;
}


// UPDATE BOOKING STATE
function bookacti_update_booking_state( $booking_id, $state, $active = 'auto' ) {
	
	global $wpdb;
	
	if( $active === 'auto' ) {
		$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;
	}
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' '
				. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
				. ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $state, $active, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


// UPDATE BOOKING QUANTITY (FORCE MODE)
function bookacti_force_update_booking_quantity( $booking_id, $quantity ) {
	
	global $wpdb;
	
	$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET quantity = %d WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $quantity, $booking_id );
	$updated	= $wpdb->query( $prep );
	
	return $updated;
}


// RESCHEDULE BOOKING
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
	 * 
	 * @global wpdb $wpdb
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param string $state
	 * @return int
	 */
	function bookacti_insert_booking_group( $user_id, $event_group_id, $state ) {
		global $wpdb;

		$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;

		$wpdb->insert( 
			BOOKACTI_TABLE_BOOKING_GROUPS, 
			array( 
				'event_group_id'	=> $event_group_id,
				'user_id'			=> $user_id,
				'state'				=> $state,
				'active'			=> $active
			),
			array( '%d', '%d', '%s', '%d' )
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
			$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;
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
			// Cancel bundled bookings
			$query2		= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
						. ' SET state = %s, active = IFNULL( NULLIF( %d, -1 ), active ) '
						. ' WHERE group_id = %d';
			
			$variables_array = array( $state, $active, $booking_group_id );
			
			if( $same_state && ! empty( $old_state ) ) {
				$query2	.= ' AND state = %s ';
				$variables_array[] = $old_state;
			}
			
			$prep2		= $wpdb->prepare( $query2, $variables_array );
			$updated2	= $wpdb->query( $prep2 );
			
			if( is_int( $updated1 ) && is_int( $updated2 ) ) {
				$updated = $updated1 + $updated2;
			} else {
				return false;
			}
		}
		
		return $updated;
	}
	
	
	/**
	 * Update booking group
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @param string $state
	 * @param 0|1|'auto' $active
	 * @return boolean|null
	 */
	function bookacti_update_booking_group( $booking_group_id, $state = NULL, $user_id = NULL, $order_id = NULL, $event_group_id = NULL, $active = 'auto' ) {

		global $wpdb;
		
		$query		= 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' SET ';
		
		$variables_array = array();
		
		if( ! empty( $state ) ) {
			$query .= 'state = %s, ';
			$variables_array[] = $state;
		}
		
		if( ! empty( $user_id ) ) {
			$query .= 'user_id = %d, ';
			$variables_array[] = $user_id;
		}
		
		if( ! empty( $order_id ) ) {
			$query .= 'order_id = %d, ';
			$variables_array[] = $order_id;
		}
		
		if( ! empty( $event_group_id ) ) {
			$query .= 'event_group_id = %d, ';
			$variables_array[] = $event_group_id;
		}
		
		if( ! empty( $state ) && $active === 'auto' ) {
			$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;
		} else if( ( empty( $state ) && $active !== 0 && $active !== 1 ) ) {
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
	 * Get booking groups by group of events id
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $booking_group_id
	 * @return object
	 */
	function bookacti_get_booking_groups_by_group_of_events( $group_of_events_id, $active_only = true, $state_not_in = array() ) {
		global $wpdb;

		$query	= 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS
				. ' WHERE event_group_id = %d';
		
		if( $active_only ) {
			$bookings_query .= ' AND active = 1 ';
		}

		$array_of_variables = array( $group_of_events_id );

		if( ! empty( $state_not_in ) ) {
			$bookings_query .= ' AND state NOT IN ( %s ';
			if( count( $state_not_in ) >= 2 )  {
				for( $i = 0; $i < count( $state_not_in ) - 1; $i++ ) {
					$bookings_query  .= ', %s ';
				}
			}
			$bookings_query  .= ') ';
			$array_of_variables = array_merge( $array_of_variables, $state_not_in );
		}
		
		$prep	= $wpdb->prepare( $query, $array_of_variables );
		$groups	= $wpdb->get_results( $prep, OBJECT );

		return $groups;
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

		$query		= 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS 
					. ' WHERE user_id = %d'
					. ' AND event_group_id = %d';

		$parameters	= array( $user_id, $event_group_id );

		if( ! empty( $state ) ) {
			$query .=  ' AND state = %s';
			$parameters[] = $state;
		}

		$prep		= $wpdb->prepare( $query, $parameters );
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