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
if( ! defined( 'BOOKACTI_TABLE_EXPORTS' ) )			{ define( 'BOOKACTI_TABLE_EXPORTS',			$db_prefix . 'bookacti_exports' ); }
if( ! defined( 'BOOKACTI_TABLE_PERMISSIONS' ) )		{ define( 'BOOKACTI_TABLE_PERMISSIONS',		$db_prefix . 'bookacti_permissions' ); }


// USERS

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


/**
 * Get the user id corresponding to a secret key
 * @since 1.8.10
 * @param string $secret_key
 * @return int User ID or 0 if not found
 */
function bookacti_get_user_id_by_secret_key( $secret_key ) {
	global $wpdb;
	$query = 'SELECT user_id FROM ' . $wpdb->prefix . 'usermeta WHERE meta_key = "bookacti_secret_key" AND meta_value = %s;';
	$query = $wpdb->prepare( $query, $secret_key );
	$user_id = $wpdb->get_var( $query );
	return $user_id ? intval( $user_id ) : 0;
}




// METADATA

/**
 * Get metadata
 * @version 1.7.4
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param string $meta_key
 * @param boolean $single
 * @return mixed
 */
function bookacti_get_metadata( $object_type, $object_id, $meta_key = '', $single = false ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) ) { return false; }
	
	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	
	if( ! $object_id ) { return false; }
	
	$query	= 'SELECT object_id, meta_key, meta_value FROM ' . BOOKACTI_TABLE_META
			. ' WHERE object_type = %s';

	$variables = array( $object_type );
	
	if( is_numeric( $object_id ) ) {
		$query .= ' AND object_id = %d';
		$variables[] = $object_id;
		
	} else if( is_array( $object_id ) ) {
		$query .= ' AND object_id IN ( %d ';
		$array_count = count( $object_id );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $object_id );
	}
	
	if( $meta_key !== '' ) {
		$query .= ' AND meta_key = %s';
		$variables[] = $meta_key;
	}

	$query = $wpdb->prepare( $query, $variables );

	if( $single ) {
		$metadata = $wpdb->get_row( $query );
		return isset( $metadata->meta_value ) ? maybe_unserialize( $metadata->meta_value ) : false;
	}

	$metadata = $wpdb->get_results( $query );

	if( is_null( $metadata ) ) { return false; }

	$metadata_array = array();
	foreach( $metadata as $metadata_pair ) {
		if( is_array( $object_id ) ) {
			if( ! isset( $metadata_array[ $metadata_pair->object_id ] ) ) { $metadata_array[ $metadata_pair->object_id ] = array(); }
			$metadata_array[ $metadata_pair->object_id ][ $metadata_pair->meta_key ] = maybe_unserialize( $metadata_pair->meta_value );
		} else {
			$metadata_array[ $metadata_pair->meta_key ] = maybe_unserialize( $metadata_pair->meta_value );
		}
	}

	return $metadata_array;
}


/**
 * Update metadata
 * @version 1.7.17
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $object_id
 * @param array $metadata_array
 * @return int|false
 */
function bookacti_update_metadata( $object_type, $object_id, $metadata_array ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ! is_numeric( $object_id ) || ! is_array( $metadata_array ) ) { return false; }
	
	if( is_array( $metadata_array ) && empty( $metadata_array ) ) { return 0; }

	$object_id = absint( $object_id );
	if( ! $object_id ) { return false; }

	$current_metadata = bookacti_get_metadata( $object_type, $object_id );

	// Insert new metadata
	$inserted =  0;
	$new_metadata = array_diff_key( $metadata_array, $current_metadata );
	if( ! empty( $new_metadata ) ) {
		$inserted = bookacti_insert_metadata( $object_type, $object_id, $new_metadata );
	}

	// Update existing metadata
	$updated = 0;
	$existing_metadata = array_intersect_key( $metadata_array, $current_metadata );
	if( ! empty( $existing_metadata ) ) {
		$update_metadata_query		= 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = ';
		$update_metadata_query_end	= ' WHERE object_type = %s AND object_id = %d AND meta_key = %s;';

		foreach( $existing_metadata as $meta_key => $meta_value ) {
			$update_metadata_query_n = $update_metadata_query;

			if( is_int( $meta_value ) )			{ $update_metadata_query_n .= '%d'; }
			else if( is_float( $meta_value ) )	{ $update_metadata_query_n .= '%f'; }
			else								{ $update_metadata_query_n .= '%s'; }
			
			if( is_array( $meta_value ) || is_object( $meta_value ) ) { $meta_value = maybe_serialize( $meta_value ); }
			
			$update_metadata_query_n .= $update_metadata_query_end;

			$update_variables_array = array( $meta_value, $object_type, $object_id, $meta_key );

			$update_query_prep = $wpdb->prepare( $update_metadata_query_n, $update_variables_array );
			$updated_n = $wpdb->query( $update_query_prep );

			if( is_int( $updated_n ) && is_int( $updated ) ) {
				$updated += $updated_n;
			} else if( $updated_n === false ) {
				$updated = false;
			}
		}
	}

	if( is_int( $inserted ) && is_int( $updated ) ) {
		return $inserted + $updated;
	}

	return false;
}


/**
 * Insert metadata
 * @version 1.7.17
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $object_id
 * @param array $metadata_array
 * @return int|boolean
 */
function bookacti_insert_metadata( $object_type, $object_id, $metadata_array ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ! is_numeric( $object_id ) || ! is_array( $metadata_array ) || empty( $metadata_array ) ) { return false; }

	$object_id = absint( $object_id );
	if( ! $object_id ) { return false; }

	$insert_metadata_query = 'INSERT INTO ' . BOOKACTI_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) VALUES ';
	$insert_variables_array = array();
	$i = 0;
	foreach( $metadata_array as $meta_key => $meta_value ) {
		$insert_metadata_query .= '( %s, %d, %s, ';

		if( is_int( $meta_value ) )			{ $insert_metadata_query .= '%d'; }
		else if( is_float( $meta_value ) )	{ $insert_metadata_query .= '%f'; }
		else								{ $insert_metadata_query .= '%s'; }

		if( ++$i === count( $metadata_array ) ) {
			$insert_metadata_query .= ' );';
		} else {
			$insert_metadata_query .= ' ), ';
		}
		$insert_variables_array[] = $object_type;
		$insert_variables_array[] = $object_id;
		$insert_variables_array[] = $meta_key;
		$insert_variables_array[] = is_array( $meta_value ) || is_object( $meta_value ) ? maybe_serialize( $meta_value ) : $meta_value;
	}

	$insert_query_prep = $wpdb->prepare( $insert_metadata_query, $insert_variables_array );
	$inserted = $wpdb->query( $insert_query_prep );

	return $inserted;
}


/**
 * Duplicate metadata
 * 
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $source_id
 * @param int $recipient_id
 * @return int|boolean
 */
function bookacti_duplicate_metadata( $object_type, $source_id, $recipient_id ) {
	global $wpdb;
	
	if( ! $object_type || ! is_numeric( $source_id ) || ! is_numeric( $recipient_id ) ) { return false; }
	
	$source_id		= absint( $source_id );
	$recipient_id	= absint( $recipient_id );
	if( ! $source_id || ! $recipient_id ) { return false; }
	
	$query		= 'INSERT INTO ' . BOOKACTI_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) '
				. ' SELECT object_type, %d, meta_key, meta_value '
				. ' FROM ' . BOOKACTI_TABLE_META
				. ' WHERE object_type = %s ' 
				. ' AND object_id = %d';
	$query_prep	= $wpdb->prepare( $query, $recipient_id, $object_type, $source_id );
	$inserted	= $wpdb->query( $query_prep );
	
	return $inserted;
}


/**
 * Delete metadata
 * @version 1.7.4
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param array $metadata_key_array Array of metadata keys to delete. Leave it empty to delete all metadata of the desired object.
 * @return int|boolean
 */
function bookacti_delete_metadata( $object_type, $object_id, $metadata_key_array = array() ) {
	global $wpdb;
	
	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) ) { return false; }
	
	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = %s ';

	$variables = array( $object_type );
	
	if( is_numeric( $object_id ) ) {
		$query .= ' AND object_id = %d';
		$variables[] = $object_id;
		
	} else if( is_array( $object_id ) ) {
		$query .= ' AND object_id IN ( %d ';
		$array_count = count( $object_id );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $object_id );
	}
	
	if( $metadata_key_array ) {
		$query .= ' AND meta_key IN( %s';
		for( $i=1,$len=count($metadata_key_array); $i < $len; ++$i ) {
			$query .= ', %s';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, array_values( $metadata_key_array ) );
	}
	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );
	
	return $deleted;
}




// MISC

/**
 * Retrieve a cron job by hook from database (no cache)
 * Used for debug purposes
 * @since 1.7.13
 * @global wpdb $wpdb
 * @param string $hook
 * @return array
 */
function bookacti_get_cron_from_db( $hook = '' ) {
	global $wpdb;
	
	$cron = maybe_unserialize( $wpdb->get_var( 'SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = "cron"' ) );
	
	if( ! $hook ) { return $cron; }
	
	$results = array();
	if( ! is_array( $cron ) ) { return $results; }
	
	foreach( $cron as $timestamp => $tasks ) {
		if( isset( $tasks[ $hook ] ) ) {
			$results[ $timestamp ] = $tasks[ $hook ];
		}
	}
	
	return $results;
}