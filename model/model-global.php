<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$db_prefix = $wpdb->prefix;

// TABLES NAMES
if( ! defined( 'BOOKACTI_TABLE_TEMPLATES' ) )	{ define( 'BOOKACTI_TABLE_TEMPLATES',	$db_prefix . 'bookacti_templates' ); }
if( ! defined( 'BOOKACTI_TABLE_ACTIVITIES' ) )	{ define( 'BOOKACTI_TABLE_ACTIVITIES',	$db_prefix . 'bookacti_activities' ); }
if( ! defined( 'BOOKACTI_TABLE_EVENTS' ) )		{ define( 'BOOKACTI_TABLE_EVENTS',		$db_prefix . 'bookacti_events' ); }
if( ! defined( 'BOOKACTI_TABLE_META' ) )		{ define( 'BOOKACTI_TABLE_META',		$db_prefix . 'bookacti_meta' ); }
if( ! defined( 'BOOKACTI_TABLE_PERMISSIONS' ) )	{ define( 'BOOKACTI_TABLE_PERMISSIONS',	$db_prefix . 'bookacti_permissions' ); }
if( ! defined( 'BOOKACTI_TABLE_TEMP_ACTI' ) )	{ define( 'BOOKACTI_TABLE_TEMP_ACTI',	$db_prefix . 'bookacti_templates_activities' ); }
if( ! defined( 'BOOKACTI_TABLE_EXCEPTIONS' ) )	{ define( 'BOOKACTI_TABLE_EXCEPTIONS',	$db_prefix . 'bookacti_exceptions' ); }
if( ! defined( 'BOOKACTI_TABLE_BOOKINGS' ) )	{ define( 'BOOKACTI_TABLE_BOOKINGS',	$db_prefix . 'bookacti_bookings' ); }


// Check if user id exists
function bookacti_user_id_exists( $user_id ) {
	global $wpdb;

	$query		= 'SELECT COUNT(*) FROM ' . $wpdb->users . ' WHERE ID = %d ';
	$query_prep	= $wpdb->prepare( $query, $user_id );
	$count		= $wpdb->get_var( $query_prep );

	return $count === 1;
}


// Get user metadata
function bookacti_get_users_data( $user_ids ) {

	global $wpdb;

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

	$users_info_query	= " SELECT U.*, " . $select_usermeta_columns . " 
							FROM " . $wpdb->users . " as U, " . $wpdb->usermeta . " as M 
							WHERE U.id = M.user_id
							AND U.id IN ( %d ";

	if( count( $user_ids ) >= 2 )  {
		for( $i = 0; $i < count( $user_ids ) - 1; $i++ ) {
			$users_info_query  .= ', %d ';
		}
	}

	$users_info_query	.= " ) GROUP BY U.id ; ";
	$users_info_prepare	= $wpdb->prepare( $users_info_query, $user_ids );
	$users_info = $wpdb->get_results( $users_info_prepare, OBJECT );

	$return_array = array();
	foreach( $users_info as $user_info ) {
		$return_array[ $user_info->ID ] = apply_filters( 'bookacti_user_data', $user_info );
	}

	return $return_array;
}