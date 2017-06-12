<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// BOOK AN EVENT
function bookacti_insert_booking( $user_id, $event_id, $event_start, $event_end, $quantity, $state, $expiration_date = NULL ) {
	global $wpdb;
	$return_booking = array();

	$active = in_array( $state, bookacti_get_active_booking_states() ) ? 1 : 0;

	$wpdb->insert( 
		BOOKACTI_TABLE_BOOKINGS, 
		array( 
			'event_id'			=> $event_id,
			'user_id'			=> $user_id,
			'event_start'		=> $event_start,
			'event_end'			=> $event_end,
			'quantity'			=> $quantity,
			'state'				=> $state,
			'expiration_date'	=> $expiration_date,
			'active'			=> $active
		),
		array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
	);

	if( $wpdb->insert_id !== false ) {
		$return_booking['action'] = 'inserted';
		$return_booking['id'] = $wpdb->insert_id;
		do_action( 'bookacti_booking_inserted', $wpdb->insert_id );
	}

	return $return_booking;
}
	

//UPDATE BOOKING QUNATITY IF POSSIBLE
function bookacti_update_booking_quantity( $booking_id, $new_quantity, $expiration_date = NULL ) {
	global $wpdb;

	$query_booking	= 'SELECT event_id, event_start, event_end, quantity, active FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d ';
	$prep_booking	= $wpdb->prepare( $query_booking, $booking_id );
	$results		= $wpdb->get_row( $prep_booking, OBJECT );

	$old_quantity = $results->quantity;
	$availability = bookacti_get_event_availability( $results->event_id, $results->event_start, $results->event_end );

	$return_array = array();
	$return_array['status'] = '';

	// If the updated booking is active, you must count the quantity already booked by this user in the total quantity available for him
	if( $results->active ) {
		$new_availability = intval( $availability ) + intval( $old_quantity ) - intval( $new_quantity );
		$return_array['availability'] = intval( $availability ) + intval( $old_quantity );
	} else {
		$new_availability = intval( $availability ) - intval( $new_quantity );
		$return_array['availability'] = intval( $availability );
	}

	if( intval( $new_availability ) >= 0 ) {

		$state = NULL; 
		$active = -1; 
		$update_quantity = $new_quantity;
		if( intval( $new_quantity ) <= 0 ) { $state = 'removed'; $update_quantity = $old_quantity; $active = 0; } 

		$query	= 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS 
				. ' SET quantity = %d, '
				. ' state = IFNULL( NULLIF( %s, "" ), state ), '
				. ' active = IFNULL( NULLIF( %d, -1 ), active ), '
				. ' expiration_date = IFNULL( NULLIF( %s, "" ), expiration_date ) '
				. ' WHERE id = %d ';

		$prep_query	= $wpdb->prepare( $query, $update_quantity, $state, $active, $expiration_date, $booking_id );
		$updated	= $wpdb->query( $prep_query );

		if( $updated > 0 ){

			if( intval( $new_quantity ) > 0 && intval( $new_quantity ) === intval( $old_quantity ) ) {
				do_action( 'bookacti_booking_restored', $booking_id );
			} else if( intval( $new_quantity ) > 0 && intval( $update_quantity ) !== intval( $old_quantity ) ) {
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

		if( $return_array['availability'] > 0 ) {
			$return_array['status'] = 'qty_sup_to_avail';
		} else {
			$return_array['status'] = 'no_availability';
		}
	}

	return $return_array;
}
	

//GET ALL BOOKINGS OF A TEMPLATE OR AN EVENT
function bookacti_get_bookings( $template_id = NULL, $event_id = NULL, $event_start = NULL, $event_end = NULL, $active_only = true, $state_not_in = array() ) {
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

	if( $event_id ) {
		$bookings_query .= ' AND B.event_id = %d ';
		array_push( $array_of_variables, $event_id );
	}

	if( $template_id ) {
		$bookings_query .= ' AND E.template_id = %d ';
		array_push( $array_of_variables, $template_id );
	}

	if( $event_start ) {
		$bookings_query .= ' AND B.event_start = %s ';
		array_push( $array_of_variables, $event_start );
	}

	if( $event_end ) {
		$bookings_query .= ' AND B.event_end = %s ';
		array_push( $array_of_variables, $event_end );
	}

	$bookings_query .= ' ORDER BY B.event_start, B.event_end ASC ';

	$prepare_bookings_query = $wpdb->prepare( $bookings_query, $array_of_variables );
	$bookings = $wpdb->get_results( $prepare_bookings_query, OBJECT );

	// Order bookings by event id
	$bookings_array = array();
	if( $bookings ) {
		foreach( $bookings as $booking ) {

			if( ! is_array( $bookings_array[ $booking->event_id ] ) ) {
				$bookings_array[ $booking->event_id ] = array();
			}

			array_push( $bookings_array[ $booking->event_id ], apply_filters( 'bookacti_booking_data', $booking ) );
		}
	}

	return apply_filters( 'bookacti_get_bookings', $bookings_array, $template_id, $event_id, $event_start, $event_end, $active_only, $state_not_in );
}


//GET NUMBER OF BOOKINGS
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

	$query		= 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $booking_id );
	$booking	= $wpdb->get_row( $prep, OBJECT );

	return $booking;
}


// GET BOOKING DATA
function bookacti_get_booking_data( $booking_id ) {
	global $wpdb;
	
	$query	= 'SELECT B.quantity, E.id as event_id, E.template_id, E.activity_id, EG.category_id '
			. ' FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG '
			. ' WHERE E.activity_id = A.id '
			. ' AND B.event_id = E.id '
			. ' AND B.group_id = BG.id '
			. ' AND BG.event_group_id = EG.id '
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

	$query		= 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE user_id = %d ORDER BY id DESC';
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


