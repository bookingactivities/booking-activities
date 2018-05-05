<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$db_prefix = $wpdb->prefix;

// TABLES NAMES
if( ! defined( 'BOOKACTI_TABLE_TEMPLATES' ) )		{ define( 'BOOKACTI_TABLE_TEMPLATES',		$db_prefix . 'bookacti_templates' ); }
if( ! defined( 'BOOKACTI_TABLE_ACTIVITIES' ) )		{ define( 'BOOKACTI_TABLE_ACTIVITIES',		$db_prefix . 'bookacti_activities' ); }
if( ! defined( 'BOOKACTI_TABLE_TEMP_ACTI' ) )		{ define( 'BOOKACTI_TABLE_TEMP_ACTI',		$db_prefix . 'bookacti_templates_activities' ); }
if( ! defined( 'BOOKACTI_TABLE_EVENTS' ) )			{ define( 'BOOKACTI_TABLE_EVENTS',			$db_prefix . 'bookacti_events' ); }
if( ! defined( 'BOOKACTI_TABLE_EXCEPTIONS' ) )		{ define( 'BOOKACTI_TABLE_EXCEPTIONS',		$db_prefix . 'bookacti_exceptions' ); }
if( ! defined( 'BOOKACTI_TABLE_EVENT_GROUPS' ) )	{ define( 'BOOKACTI_TABLE_EVENT_GROUPS',	$db_prefix . 'bookacti_event_groups' ); }
if( ! defined( 'BOOKACTI_TABLE_GROUPS_EVENTS' ) )	{ define( 'BOOKACTI_TABLE_GROUPS_EVENTS',	$db_prefix . 'bookacti_groups_events' ); }
if( ! defined( 'BOOKACTI_TABLE_GROUP_CATEGORIES' ) ){ define( 'BOOKACTI_TABLE_GROUP_CATEGORIES',$db_prefix . 'bookacti_group_categories' ); }
if( ! defined( 'BOOKACTI_TABLE_FORMS' ) )			{ define( 'BOOKACTI_TABLE_FORMS',			$db_prefix . 'bookacti_forms' ); }
if( ! defined( 'BOOKACTI_TABLE_FORM_FIELDS' ) )		{ define( 'BOOKACTI_TABLE_FORM_FIELDS',		$db_prefix . 'bookacti_form_fields' ); }
if( ! defined( 'BOOKACTI_TABLE_BOOKINGS' ) )		{ define( 'BOOKACTI_TABLE_BOOKINGS',		$db_prefix . 'bookacti_bookings' ); }
if( ! defined( 'BOOKACTI_TABLE_BOOKING_GROUPS' ) )	{ define( 'BOOKACTI_TABLE_BOOKING_GROUPS',	$db_prefix . 'bookacti_booking_groups' ); }
if( ! defined( 'BOOKACTI_TABLE_META' ) )			{ define( 'BOOKACTI_TABLE_META',			$db_prefix . 'bookacti_meta' ); }
if( ! defined( 'BOOKACTI_TABLE_PERMISSIONS' ) )		{ define( 'BOOKACTI_TABLE_PERMISSIONS',		$db_prefix . 'bookacti_permissions' ); }


/**
 * Check if user id exists
 * 
 * @global wpdb $wpdb
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_id_exists( $user_id ) {
	global $wpdb;

	$query		= 'SELECT COUNT(*) FROM ' . $wpdb->users . ' WHERE ID = %d ';
	$query_prep	= $wpdb->prepare( $query, $user_id );
	$count		= $wpdb->get_var( $query_prep );

	return $count === 1;
}


/**
 * Get users metadata
 * 
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param array $args
 * @return array
 */
function bookacti_get_users_data( $args = array() ) {

	global $wpdb;
	
	$defaults = array(
		'include'		=> array(), 
		'exclude'		=> array(),
		'role'			=> array(), 
		'role__in'		=> array(), 
		'role__not_in'	=> array(),
		'order_by'		=> array( 'display_name' ),
		'order'			=> 'ASC'
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	// This query transform the meta_key / meta_value pair (of usermeta table) into columns / values for each distinct wanted user
	$wpdb->query( 'SET SESSION group_concat_max_len = 1000000' );
	$select_usermeta_columns_query	= " SELECT
										  GROUP_CONCAT( DISTINCT
											CONCAT(
											  'MAX( IF ( M.meta_key = ''',
											  meta_key,
											  ''', M.meta_value, NULL)) AS `',
											  meta_key, '`'
											)
										  ) as select_usermeta
										FROM " . $wpdb->usermeta . " as M;";
	$select_usermeta_columns = $wpdb->get_var( $select_usermeta_columns_query );

	$users_info_query	= ' SELECT U.*, ' . $select_usermeta_columns 
						. ' FROM ' . $wpdb->users . ' as U, ' . $wpdb->usermeta . ' as M '
						. ' WHERE U.id = M.user_id ';
	
	$variables = array();
	
	// Included user ids
	$include_nb = count( $args[ 'include' ] );
	if( $include_nb ) {
		$users_info_query .= ' AND U.id IN ( %d ';
		if( $include_nb >= 2 )  {
			for( $i=1; $i < $include_nb; ++$i ) {
				$users_info_query  .= ', %d ';
			}
		}
		$users_info_query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'include' ] );
	}
	
	// Excluded user ids
	$exclude_nb = count( $args[ 'exclude' ] );
	if( $exclude_nb ) {
		$users_info_query .= ' AND U.id NOT IN ( %d ';
		if( $exclude_nb >= 2 ) {
			for( $i=1; $i < $exclude_nb; ++$i ) {
				$users_info_query  .= ', %d ';
			}
		}
		$users_info_query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'exclude' ] );
	}
	
	// Group by id to avoid duplicated entries
	$users_info_query .= ' GROUP BY U.id ';
	
	// We must use HAVING instead of WHERE to filter by a column alias (which is the case for roles)
	$users_info_query .= ' HAVING true ';
	
	// Match roles exactly (all)
	$roles_nb = count( $args[ 'role' ] );
	if( $roles_nb ) {
		for( $i=0; $i < $roles_nb; ++$i ) {
			$users_info_query .= ' AND ' . $wpdb->prefix . 'capabilities LIKE %s ';
		}
		// Prefix and suffix each element of the array
		foreach( $args[ 'role' ] as $i => $role ) {
			$args[ 'role' ][ $i ] = '%' . $wpdb->esc_like( $role ) . '%';
		}
		$variables = array_merge( $variables, $args[ 'role' ] );
	}
	
	// Match at least one of the role
	$roles_in_nb = count( $args[ 'role__in' ] );
	if( $roles_in_nb ) {
		$users_info_query .= ' AND ( ' . $wpdb->prefix . 'capabilities LIKE %s ';
		if( $roles_in_nb >= 2 ) {
			for( $i=1; $i < $roles_in_nb; ++$i ) {
				$users_info_query .= ' OR ' . $wpdb->prefix . 'capabilities LIKE %s ';
			}
		}
		$users_info_query .= ' ) ';
		// Prefix and suffix each element of the array
		foreach( $args[ 'role__in' ] as $i => $role ) {
			$args[ 'role__in' ][ $i ] = '%' . $wpdb->esc_like( $role ) . '%';
		}
		$variables = array_merge( $variables, $args[ 'role__in' ] );
	}
	
	// Exclude roles
	$roles_not_in_nb = count( $args[ 'role__not_in' ] );
	if( $roles_not_in_nb ) {
		for( $i=0; $i < $roles_not_in_nb; ++$i ) {
			$users_info_query .= ' AND ' . $wpdb->prefix . 'capabilities NOT LIKE %s ';
		}
		// Prefix and suffix each element of the array
		foreach( $args[ 'role__not_in' ] as $i => $role ) {
			$args[ 'role__not_in' ][ $i ] = '%' . $wpdb->esc_like( $role ) . '%';
		}
		$variables = array_merge( $variables, $args[ 'role__not_in' ] );
	}
	
	// Order results
	$order_by_nb = count( $args[ 'order_by' ] );
	if( $order_by_nb ) {
		$users_info_query .= ' ORDER BY ' . $args[ 'order_by' ][ 0 ];
		if( $order_by_nb >= 2 ) {
			for( $i=1; $i < $order_by_nb; ++$i ) {
				$users_info_query  .= ', ' . $args[ 'order_by' ][ $i ];
			}
		}
		if( $args[ 'order' ] ) {
			$users_info_query  .= ' ' . $args[ 'order' ];
		}
	}
	
	// Prepare the query
	if( $variables ) {
		$users_info_query = $wpdb->prepare( $users_info_query, $variables );
	}
	
	// Get users data
	$users_data = $wpdb->get_results( $users_info_query, OBJECT );

	$return_array = array();
	foreach( $users_data as $user_data ) {
		$return_array[ $user_data->ID ] = apply_filters( 'bookacti_user_data', $user_data );
	}

	return $return_array;
}


/**
 * Delete a user meta for all users
 * 
 * @since 1.3.0
 * @global wpdb $wpdb
 * @param string $meta_key
 * @param string $meta_value
 * @param int $user_id
 * @return false|int
 */
function bookacti_delete_user_meta( $meta_key, $user_id = 0, $meta_value = '' ) {
	
	global $wpdb;
	
	$query = 'DELETE FROM ' . $wpdb->prefix . 'usermeta WHERE meta_key = %s ';
	
	$variables = array( $meta_key );
	
	if( $user_id ) {
		$query .= ' AND user_id = %d ';
		$variables[] = $user_id;
	}
	
	if( $meta_value ) {
		$query .= ' AND meta_value = %s ';
		$variables[] = $meta_value;
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );
	
	return $deleted;
}