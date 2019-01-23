<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// DATABASE BACKUP AND ARCHIVE
// 1. DB BACKUP ANALYSIS

/**
 * Get mysql bin path
 * @since 1.7.0
 * @global type $bookacti_mysql_bin_path
 * @global wpdb $wpdb
 * @return false|string
 */
function bookacti_get_mysql_bin_path() {
	global $bookacti_mysql_bin_path;
	if( isset( $bookacti_mysql_bin_path ) ) { return $bookacti_mysql_bin_path; }
	
	global $wpdb;
	
	$mysql_dir = $wpdb->get_var( 'SELECT @@basedir' );
	
	if( ! $mysql_dir ) { 
		$bookacti_mysql_bin_path = false; 
		return false;
	}
	
	$bookacti_mysql_bin_path = str_replace( '\\', '/', $mysql_dir );
	
	if( substr( $bookacti_mysql_bin_path, -1 ) !== '/' ) {
		$bookacti_mysql_bin_path .= '/';
	}
	
	$bookacti_mysql_bin_path .= 'bin/';
	
	return $bookacti_mysql_bin_path;
}


/**
 * Get mysql temp path
 * @since 1.7.0
 * @global type $bookacti_mysql_temp_path
 * @global wpdb $wpdb
 * @return false|string
 */
function bookacti_get_mysql_temp_path() {
	global $bookacti_mysql_temp_path;
	if( isset( $bookacti_mysql_temp_path ) ) { return $bookacti_mysql_temp_path; }
	
	global $wpdb;
	
	$temp_dir = $wpdb->get_var( 'SELECT @@tmpdir' );
	
	if( ! $temp_dir ) { 
		$bookacti_mysql_temp_path = false; 
		return false;
	}
	
	$bookacti_mysql_temp_path = str_replace( '\\', '/', $temp_dir );
	
	if( substr( $bookacti_mysql_temp_path, -1 ) !== '/' ) {
		$bookacti_mysql_temp_path .= '/';
	}
	
	return $bookacti_mysql_temp_path;
}


/**
 * Get events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_events_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT E.id, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE ( ( E.repeat_freq IS NULL OR E.repeat_freq = "none" ) AND E.end < %s ) '
			. ' OR ( ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) AND E.repeat_to < %s )'
			. ' ORDER BY E.id DESC';
	
	$variables	= array( $date . ' 00:00:00', $date );
	$query		= $wpdb->prepare( $query, $variables );
	$events		= $wpdb->get_results( $query, OBJECT );
	
	return $events;
}


/**
 * Get repeated events that have started as of a specific date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_started_repeated_events_as_of( $date ) {
	global $wpdb;
	
	$query	= ' SELECT E.id, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) '
			. ' AND E.repeat_to > %s '
			. ' AND E.repeat_from < %s '
			. ' ORDER BY E.id DESC';
	
	$variables	= array( $date, $date );
	$query		= $wpdb->prepare( $query, $variables );
	$events		= $wpdb->get_results( $query, OBJECT );
	
	return $events;
}


/**
 * Get groups of events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_group_of_events_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT GE.group_id as id, MIN( GE.event_start ) as min_event_start, MAX( GE.event_end ) as max_event_end FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
			. ' GROUP BY GE.group_id '
			. ' HAVING max_event_end < %s '
			. ' ORDER BY GE.group_id DESC';
	
	$variables	= array( $date . ' 00:00:00' );
	$query		= $wpdb->prepare( $query, $variables );
	$groups		= $wpdb->get_results( $query, OBJECT );
	
	return $groups;
}


/**
 * Get bookings prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_bookings_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT B.id, B.event_start, B.event_end, B.group_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' WHERE B.event_end < %s '
			. ' ORDER BY B.id DESC';
	
	$variables	= array( $date . ' 00:00:00' );
	$query		= $wpdb->prepare( $query, $variables );
	$bookings	= $wpdb->get_results( $query, OBJECT );
	
	return $bookings;
}


/**
 * Get booking groups prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_booking_groups_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT B.group_id as id, MIN( event_start ) as min_event_start, MAX( event_end ) as max_event_end FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' WHERE B.group_id IS NOT NULL '
			. ' GROUP BY B.group_id '
			. ' HAVING max_event_end < %s '
			. ' ORDER BY B.group_id DESC';
	
	$variables	= array( $date . ' 00:00:00' );
	$query		= $wpdb->prepare( $query, $variables );
	$booking_groups	= $wpdb->get_results( $query, OBJECT );
	
	return $booking_groups;
}




// 2. DB BACKUP DUMP

/**
 * Create a .sql file to archive events prior to a date
 * @since 1.7.0
 * @param string $date
 * @return int|false
 */
function bookacti_archive_events_prior_to( $date ) {
	$filename	= $date . '-events.sql';
	$table		= BOOKACTI_TABLE_EVENTS;
	$where		= sprintf( "( ( repeat_freq IS NULL OR repeat_freq = 'none' ) AND end < '%s' ) OR ( ( repeat_freq IS NOT NULL AND repeat_freq != 'none' ) AND repeat_to < '%s' )", $date . ' 00:00:00', $date . ' 00:00:00' );
	return bookacti_archive_database( $filename, $table, $where );
}


/**
 * Create a .sql file to archive repeated events that have started as of a specific date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return int|false
 */
function bookacti_archive_started_repeated_events_as_of( $date ) {
	global $wpdb;
	$wpdb->hide_errors();
	
	// Remove the temporary table if it already exists
	$temp_table = $wpdb->prefix . 'bookacti_temp_events';
	$delete_query = 'DROP TABLE IF EXISTS ' . $temp_table . '; ';
	$wpdb->query( $delete_query );
	
	// Create a table to store the old repeat_from values
	$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
	$temp_table_events_query = 'CREATE TABLE ' . $temp_table . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		repeat_from DATE, 
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	if( ! function_exists( 'dbDelta' ) ) { require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); }
	
	dbDelta( $temp_table_events_query );
	
	// Fill the temp table
	$insert_query	= ' INSERT INTO ' . $temp_table . ' (id, repeat_from) '
					. ' SELECT E.id, E.repeat_from FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) '
					. ' AND E.repeat_to >= %s '
					. ' AND E.repeat_from <= %s ';
	$insert_query		= $wpdb->prepare( $insert_query, array( $date, $date ) );
	$inserted	= $wpdb->query( $insert_query );
	
	if( ! $inserted ) { return $inserted; }
	
	// Dump the table
	$filename = $date . '-started-repeated-events.sql';
	$dumped = bookacti_archive_database( $filename, $temp_table, '', false );
	
	// Remove the table
	$delete_query = 'DROP TABLE IF EXISTS ' . $temp_table . '; ';
	$wpdb->query( $delete_query );
	
	if( $dumped !== true ) { return $dumped; }
	
	// Add the UPDATE and DELETE queries to the backup file
	$update_query	= 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' INNER JOIN ' . $temp_table . ' as TE ON E.id = TE.id'
					. ' SET E.repeat_from = TE.repeat_from'
					. ' WHERE TE.repeat_from < E.repeat_from;';
	
	$uploads_dir= wp_upload_dir();
	$file		= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/' . $filename;
	$handle		= fopen( $file, 'a' );
	$write		= 0;
	if( $handle !== false ) {
		$text	= PHP_EOL . '-- Update `' . BOOKACTI_TABLE_EVENTS . '` repeat_from with the values of the temporary table `' . $temp_table . '`';
		$text	.= PHP_EOL . $update_query . PHP_EOL;
		$text	.= PHP_EOL . '-- Delete the temporary table `' . $temp_table . '`';
		$text	.= PHP_EOL . $delete_query. PHP_EOL;
		$write	= fwrite( $handle, $text );
		fclose( $handle );
	}
	
	return $write ? true : false;
}


/**
 * Create a .sql file to archive group of events prior to a date
 * @since 1.7.0
 * @param string $date
 * @return boolean
 */
function bookacti_archive_group_of_events_prior_to( $date ) {
	$filename_groups	= $date . '-group-of-events.sql';
	$table_groups		= BOOKACTI_TABLE_EVENT_GROUPS;
	$where_groups		= sprintf( "id IN ( SELECT group_id FROM " . BOOKACTI_TABLE_GROUPS_EVENTS . " GROUP BY group_id HAVING MAX( event_end ) < '%s' )", $date . ' 00:00:00' );
	$archive_groups		= bookacti_archive_database( $filename_groups, $table_groups, $where_groups );
	
	$filename_events	= $date . '-groups-events.sql';
	$table_events		= BOOKACTI_TABLE_GROUPS_EVENTS;
	$where_events		= sprintf( "TRUE GROUP BY group_id HAVING MAX( event_end ) < '%s'", $date . ' 00:00:00' );
	$archive_events		= bookacti_archive_database( $filename_events, $table_events, $where_events );
	
	return $archive_groups === true && $archive_events === true;
}


/**
 * Create a .sql file to archive bookings prior to a date
 * @since 1.7.0
 * @param string $date
 * @return int|false
 */
function bookacti_archive_bookings_prior_to( $date ) {
	$filename	= $date . '-bookings.sql';
	$table		= BOOKACTI_TABLE_BOOKINGS;
	$where		= sprintf( "event_end < '%s'", $date . ' 00:00:00' );
	return bookacti_archive_database( $filename, $table, $where );
}


/**
 * Create a .sql file to archive booking groups prior to a date
 * @since 1.7.0
 * @param string $date
 * @return int|false
 */
function bookacti_archive_booking_groups_prior_to( $date ) {
	$filename	= $date . '-booking-groups.sql';
	$table		= BOOKACTI_TABLE_BOOKING_GROUPS;
	$where		= sprintf( "id IN ( SELECT B.group_id FROM " . BOOKACTI_TABLE_BOOKINGS . " as B WHERE B.group_id IS NOT NULL GROUP BY B.group_id HAVING MAX( event_end ) < '%s' )", $date . ' 00:00:00' );
	return bookacti_archive_database( $filename, $table, $where );
}


/**
 * Create a .sql file to archive events, group of events, booking and booking groups meta prior to a date
 * @since 1.7.0
 * @param string $date
 * @return int|false
 */
function bookacti_archive_metadata_prior_to( $date ) {
	$filename	= $date . '-metadata.sql';
	$table		= BOOKACTI_TABLE_META;
	
	// Events
	$where_events			= sprintf( "( ( repeat_freq IS NULL OR repeat_freq = 'none' ) AND end < '%s' ) OR ( ( repeat_freq IS NOT NULL AND repeat_freq != 'none' ) AND repeat_to < '%s' )", $date . ' 00:00:00', $date . ' 00:00:00' );
	$where_group_of_events	= sprintf( "GROUP BY group_id HAVING MAX( event_end ) < '%s'", $date . ' 00:00:00' );
	$where_bookings			= sprintf( "event_end < '%s'", $date . ' 00:00:00' );
	$where_booking_groups	= sprintf( "id IN ( SELECT B.group_id FROM " . BOOKACTI_TABLE_BOOKINGS . " as B WHERE B.group_id IS NOT NULL GROUP BY B.group_id HAVING MAX( event_end ) < '%s' )", $date . ' 00:00:00' );
	
	$where	= "( object_type = 'event' AND object_id IN ( SELECT id FROM " . BOOKACTI_TABLE_EVENTS . " WHERE " . $where_events . " ) ) ";
	$where .= "OR ( object_type = 'group_of_events' AND object_id IN ( SELECT group_id FROM " . BOOKACTI_TABLE_GROUPS_EVENTS . " " . $where_group_of_events . " ) ) ";
	$where .= "OR ( object_type = 'booking' AND object_id IN ( SELECT id FROM " . BOOKACTI_TABLE_BOOKINGS . " WHERE " . $where_bookings . " ) ) ";
	$where .= "OR ( object_type = 'booking_group' AND object_id IN ( SELECT id FROM " . BOOKACTI_TABLE_BOOKING_GROUPS . " WHERE " . $where_booking_groups . " ) ) ";
	
	return bookacti_archive_database( $filename, $table, $where );
}



// 3. DB BACKUP DELETION

/**
 * Delete events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_delete_events_prior_to( $date ) {
	global $wpdb;
	
	$variables = array( $date . ' 00:00:00', $date );
	
	// Remove metadata first
	$delete_meta_query	= ' DELETE FROM ' . BOOKACTI_TABLE_META
						. ' WHERE object_type = "event" AND object_id IN( '
							. ' SELECT id FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
							. ' WHERE ( ( E.repeat_freq IS NULL OR E.repeat_freq = "none" ) AND E.end < %s ) '
							. ' OR ( ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) AND E.repeat_to < %s )'
						. ' )'
						. ' LIMIT 1000';
	
	$delete_meta_query	= $wpdb->prepare( $delete_meta_query, $variables );
	$deleted_meta = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_meta_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted_meta += $deleted_chunk; }
		else { $deleted_meta = $deleted_chunk; }
	}
	
	// Remove events
	$delete_query	= ' DELETE FROM ' . BOOKACTI_TABLE_EVENTS
					. ' WHERE ( ( repeat_freq IS NULL OR repeat_freq = "none" ) AND end < %s ) '
					. ' OR ( ( repeat_freq IS NOT NULL AND repeat_freq != "none" ) AND repeat_to < %s )'
					. ' LIMIT 1000';
	
	$delete_query	= $wpdb->prepare( $delete_query, $variables );
	$deleted = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted += $deleted_chunk; }
		else { $deleted = $deleted_chunk; }
	}
	
	return $deleted;
}


/**
 * Retrict repeated events that have started before a specific date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_restrict_started_repeated_events_to( $date ) {
	global $wpdb;
	
	$query	= ' UPDATE ' . BOOKACTI_TABLE_EVENTS
			. ' SET repeat_from = %s '
			. ' WHERE ( repeat_freq IS NOT NULL AND repeat_freq != "none" ) '
			. ' AND repeat_to >= %s '
			. ' AND repeat_from <= %s ';
	
	$variables	= array( $date, $date, $date );
	$query		= $wpdb->prepare( $query, $variables );
	$updated	= $wpdb->query( $query, OBJECT );
	
	return $updated;
}


/**
 * Delete groups of events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_delete_group_of_events_prior_to( $date ) {
	global $wpdb;
	
	$select_query	= ' SELECT GE.group_id FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
					. ' GROUP BY GE.group_id '
					. ' HAVING MAX( GE.event_end ) < %s ';
	
	$variables	= array( $date . ' 00:00:00' );
	
	// Remove metadata first
	$delete_meta_query	= ' DELETE FROM ' . BOOKACTI_TABLE_META
						. ' WHERE object_type = "group_of_events" AND object_id IN( ' . $select_query . ' )'
						. ' LIMIT 1000';
	
	$delete_meta_query	= $wpdb->prepare( $delete_meta_query, $variables );
	$deleted_meta = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_meta_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted_meta += $deleted_chunk; }
		else { $deleted_meta = $deleted_chunk; }
	}
	
	// Remove group of events before the events themselves!
	$delete_query	= ' DELETE FROM ' . BOOKACTI_TABLE_EVENT_GROUPS
					. ' WHERE id IN( ' . $select_query . ' )'
					. ' LIMIT 1000';
	
	$delete_query	= $wpdb->prepare( $delete_query, $variables );
	$deleted = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted += $deleted_chunk; }
		else { $deleted = $deleted_chunk; }
	}
	
	// Remove events of groups
	$delete_events_query	= ' DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS
							. ' WHERE group_id IN( '
								. ' SELECT group_id FROM ( ' . $select_query . ' ) as TEMPTABLE '
							. ' )'
							. ' LIMIT 1000';
	
	$delete_events_query	= $wpdb->prepare( $delete_events_query, $variables );
	$deleted_events = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_events_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted_events += $deleted_chunk; }
		else { $deleted_events = $deleted_chunk; }
	}
	
	return $deleted;
}


/**
 * Delete bookings prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_delete_bookings_prior_to( $date ) {
	global $wpdb;
	
	$variables = array( $date . ' 00:00:00' );
	
	// Remove metadata first
	$delete_meta_query	= ' DELETE FROM ' . BOOKACTI_TABLE_META
						. ' WHERE object_type = "booking" '
						. ' AND object_id IN( ' 
							. 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.event_end < %s '
						. ' )'
						. ' LIMIT 1000';
	
	$delete_meta_query	= $wpdb->prepare( $delete_meta_query, $variables );
	$deleted_meta = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_meta_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted_meta += $deleted_chunk; }
		else { $deleted_meta = $deleted_chunk; }
	}
	
	// Remove
	$delete_query	= ' DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS
					. ' WHERE event_end < %s'
					. ' LIMIT 1000';
	
	$delete_query	= $wpdb->prepare( $delete_query, $variables );
	$deleted = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted += $deleted_chunk; }
		else { $deleted = $deleted_chunk; }
	}
	
	return $deleted;
}


/**
 * Delete booking groups prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_delete_booking_groups_prior_to( $date ) {
	global $wpdb;
	
	$select_query	= ' SELECT B.group_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
					. ' WHERE B.group_id IS NOT NULL '
					. ' GROUP BY B.group_id '
					. ' HAVING MAX( B.event_end ) < %s ';
	
	$variables	= array( $date . ' 00:00:00' );
	
	// Remove metadata first
	$delete_meta_query	= ' DELETE FROM ' . BOOKACTI_TABLE_META
						. ' WHERE object_type = "booking_group" AND object_id IN( ' . $select_query . ' )'
						. ' LIMIT 1000';
	
	$delete_meta_query	= $wpdb->prepare( $delete_meta_query, $variables );
	
	$deleted_meta = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_meta_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted_meta += $deleted_chunk; }
		else { $deleted_meta = $deleted_chunk; }
	}
	
	// Remove booking group
	$delete_query	= ' DELETE FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS
					. ' WHERE id IN ( ' . $select_query . ' )'
					. ' LIMIT 1000';
	
	$delete_query	= $wpdb->prepare( $delete_query, $variables );
	
	$deleted = 0; $deleted_chunk = true;
	while( $deleted_chunk !== 0 && $deleted_chunk !== false ) {
		$deleted_chunk = $wpdb->query( $delete_query, OBJECT );
		if( is_numeric( $deleted_chunk ) ) { $deleted += $deleted_chunk; }
		else { $deleted = $deleted_chunk; }
	}
	
	return $deleted;
}