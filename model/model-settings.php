<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// DATABASE BACKUP AND ARCHIVE

// 1. DB BACKUP ANALYSIS

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
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_events_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT E.id, E.template_id, E.activity_id, E.title, E.start, E.end, E.availability, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.active '
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE ( ( E.repeat_freq IS NULL OR E.repeat_freq = "none" ) AND E.end < %s ) '
			. ' OR ( ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) AND E.repeat_to < %s )'
			. ' ORDER BY E.id DESC';
	
	$variables	= array( $date . ' 00:00:00', $date );
	$query		= $wpdb->prepare( $query, $variables );
	$events		= $wpdb->get_results( $query, OBJECT );
	
	return $events;
}

/**
 * Get repeated events exceptions prior to a date
 * @since 1.7.0
 * @version 1.12.0
 * @param string $date
 * @return int|false
 */
function bookacti_get_repeated_events_exceptions_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT X.id, X.object_type, X.object_id, X.exception_value '
			. ' FROM ' . BOOKACTI_TABLE_EXCEPTIONS .' as X '
			. ' WHERE X.exception_value < %s '
			. ' ORDER BY id DESC';
	
	$variables	= array( $date );
	$query		= $wpdb->prepare( $query, $variables );
	$exceptions	= $wpdb->get_results( $query, OBJECT );
	
	return $exceptions;
}


/**
 * Get repeated events that have started as of a specific date
 * @since 1.7.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_started_repeated_events_as_of( $date ) {
	global $wpdb;
	
	$query	= ' SELECT E.id, E.template_id, E.activity_id, E.title, E.start, E.end, E.availability, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.active '
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE E.repeat_freq IS NOT NULL '
			. ' AND E.repeat_freq != "none" '
			. ' AND E.repeat_to >= %s '
			. ' AND E.repeat_from < %s '
			. ' ORDER BY E.id DESC';
	
	$variables	= array( $date, $date );
	$query		= $wpdb->prepare( $query, $variables );
	$events		= $wpdb->get_results( $query, OBJECT );
	
	return $events;
}


/**
 * Get repeated groups of events that have started as of a specific date
 * @since 1.12.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_started_repeated_groups_of_events_as_of( $date ) {
	global $wpdb;
	
	$query	= ' SELECT G.id, G.category_id, G.title, G.repeat_freq, G.repeat_step, G.repeat_on, G.repeat_from, G.repeat_to, G.active '
			. ' FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
			. ' WHERE G.repeat_freq IS NOT NULL '
			. ' AND G.repeat_freq != "none" '
			. ' AND G.repeat_to >= %s '
			. ' AND G.repeat_from < %s '
			. ' ORDER BY G.id DESC';
	
	$variables	= array( $date, $date );
	$query		= $wpdb->prepare( $query, $variables );
	$events		= $wpdb->get_results( $query, OBJECT );
	
	return $events;
}


/**
 * Get groups of events prior to a date
 * @since 1.7.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_group_of_events_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT G.id, G.category_id, G.title, G.repeat_freq, G.repeat_step, G.repeat_on, G.repeat_from, G.repeat_to, G.active, MIN( GE.event_start ) as min_event_start, MAX( GE.event_end ) as max_event_end '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
			. ' WHERE G.id = GE.group_id '
			. ' GROUP BY GE.group_id '
			. ' HAVING ( ( G.repeat_freq IS NULL OR G.repeat_freq = "none" ) AND max_event_end < %s ) '
			. ' OR ( ( G.repeat_freq IS NOT NULL AND G.repeat_freq != "none" ) AND G.repeat_to < %s )'
			. ' ORDER BY GE.group_id DESC';
	
	$variables	= array( $date . ' 00:00:00', $date );
	$query		= $wpdb->prepare( $query, $variables );
	$groups		= $wpdb->get_results( $query, OBJECT );
	
	return $groups;
}


/**
 * Get groups events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return array
 */
function bookacti_get_grouped_events_prior_to( $date ) {
	global $wpdb;
	
	$query	= ' SELECT id FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS
			. ' WHERE event_end < %s '
			. ' ORDER BY id DESC';
	
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


/**
 * Get events, group of events, booking and booking groups meta prior to a date
 * @since 1.7.0
 * @version 1.12.0
 * @param string $date
 * @return int|false
 */
function bookacti_get_metadata_prior_to( $date ) {
	global $wpdb;
		
	// Get IDs of each elements
	$select_events			= 'SELECT E.id FROM ' . BOOKACTI_TABLE_EVENTS . ' as E WHERE ( ( E.repeat_freq IS NULL OR E.repeat_freq = "none" ) AND E.end < %s ) OR ( ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) AND E.repeat_to < %s )';
	$select_bookings		= 'SELECT B.id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.event_end < %s';
	$select_group_of_events	= 'SELECT GID.group_id FROM ( SELECT GE.group_id, G.repeat_freq, G.repeat_to, MAX( GE.event_end ) as max_event_end FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G WHERE G.id = GE.group_id GROUP BY GE.group_id HAVING ( ( G.repeat_freq IS NULL OR G.repeat_freq = "none" ) AND max_event_end < %s ) OR ( ( G.repeat_freq IS NOT NULL AND G.repeat_freq != "none" ) AND G.repeat_to < %s ) ) as GID';
	$select_booking_groups	= 'SELECT B.group_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.group_id IS NOT NULL GROUP BY B.group_id HAVING MAX( event_end ) < %s';
	
	$query	= ' SELECT M.id, M.object_id, M.object_type FROM ' . BOOKACTI_TABLE_META . ' as M '
			. ' WHERE ( object_type = "event" AND object_id IN ( ' . $select_events . ' ) ) '
			. ' OR ( object_type = "booking" AND object_id IN ( ' . $select_bookings . ' ) ) '
			. ' OR ( object_type = "group_of_events" AND object_id IN ( ' . $select_group_of_events . ' ) ) '
			. ' OR ( object_type = "booking_group" AND object_id IN ( ' . $select_booking_groups . ' ) ) ';
	
	
	$variables = array( $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00' );
	
	$query	= $wpdb->prepare( $query, $variables );
	$meta	= $wpdb->get_results( $query, OBJECT );
	
	return $meta;
}




// 2. DB BACKUP DUMP

/**
 * Dump data of a specific table (with specific conditions) to a sql file with wpdb
 * @since 1.7.0
 * @param string $filename
 * @param string $table
 * @param string $where
 * @return boolean|int
 */
function bookacti_archive_database( $filename, $table, $where = 'TRUE' ) {
	global $wpdb;

	// Sanitize the filename
	$filename = sanitize_file_name( $filename );
	if( substr( $filename, -4 ) !== '.sql' ) { $filename .= '.sql'; }

	// Set the file directory
	$uploads_dir	= wp_upload_dir();
	$file			= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/' . $filename;

	// Select the data to backup
	$query_select	= " SELECT * FROM " . $table
					. " WHERE " . $where;
	$rows = $wpdb->get_results( $query_select, ARRAY_A );

	if( $rows === false )	{ return false; }
	if( empty( $rows ) )	{ return 0; }

	$inserted_rows_count = count( $rows );
	$columns_count = count( $rows[0] );
	$columns_names = array_keys( $rows[0] );

	// Build the backup query
	$backup_query	= "INSERT INTO `" . $table . "` (`" . implode( '`,`', $columns_names ) . "`) "
					. "VALUES ";

	$variables = array();
	$i = 1;
	foreach( $rows as $row ) {
		$backup_query .= "(";
		$j = 1;
		foreach( $row as $value )  {
			if( is_numeric( $value ) ) {
				$backup_query .= "%d";
				$variables[] = $value;
			} else if( is_null( $value ) ) {
				$backup_query .= "NULL";
			} else {
				$backup_query .= "%s";
				$variables[] = $value;
			}
			if( $j < $columns_count ) {
				$backup_query .= ",";
			}
			++$j;
		}
		$backup_query .= ")";
		if( $i < $inserted_rows_count ) {
			$backup_query .= ",";
		}
		++$i;
	}
	$backup_query .= ';';

	if( $variables ) {
		$backup_query = $wpdb->prepare( $backup_query, $variables );
	}

	// Write the backup query in the file
	$handle	= fopen( $file, 'a' );
	$write	= 0;
	if( $handle !== false ) {
		$write	= fwrite( $handle, $backup_query );
		fclose( $handle );
	}

	if( ! $write ) { return false; }

	return $inserted_rows_count;
}


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
 * Create a .sql file to archive repeated events exceptions prior to a date
 * @since 1.7.0
 * @version 1.12.0
 * @param string $date
 * @return int|false
 */
function bookacti_archive_repeated_events_exceptions_prior_to( $date ) {
	$filename	= $date . '-repeated-events-exceptions.sql';
	$table		= BOOKACTI_TABLE_EXCEPTIONS;
	$where		= sprintf( "exception_value < '%s'", $date );
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
	$delete_query = 'DROP TABLE IF EXISTS `' . $temp_table . '`; ';
	$wpdb->query( $delete_query );
	
	// Create a table to store the old repeat_from values
	$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
	$temp_table_events_query	= 'CREATE TABLE `' . $temp_table . '` ( '
								. '`id` MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, '
								. '`repeat_from` DATE, '
								. 'PRIMARY KEY (`id`) ) ' . $collate . ';';
	
	if( ! function_exists( 'dbDelta' ) ) { require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); }
	
	dbDelta( $temp_table_events_query );
	
	// Fill the temp table
	$insert_query	= ' INSERT INTO ' . $temp_table . ' (id, repeat_from) '
					. ' SELECT E.id, E.repeat_from FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) '
					. ' AND E.repeat_to >= %s '
					. ' AND E.repeat_from < %s ';
	$insert_query		= $wpdb->prepare( $insert_query, array( $date, $date ) );
	$inserted	= $wpdb->query( $insert_query );
	
	if( ! $inserted ) { return $inserted; }
	
	// Add the CREATE query to the backup file
	$filename	= $date . '-truncated-repeated-events.sql';
	$uploads_dir= wp_upload_dir();
	$file		= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/' . $filename;
	
	$handle	= fopen( $file, 'a' );
	$write	= 0;
	if( $handle !== false ) {
		$text	= $delete_query;
		$text	.= PHP_EOL . PHP_EOL;
		$text	.= $temp_table_events_query;
		$text	.= PHP_EOL . PHP_EOL;
		$write	= fwrite( $handle, $text );
		fclose( $handle );
	}

	if( ! $write ) { return false; }
	
	// Dump the table
	$dumped = bookacti_archive_database( $filename, $temp_table, 'TRUE' );
	
	// Remove the table
	$wpdb->query( $delete_query );
	
	if( $dumped === false ) { return $dumped; }
	
	// Add the UPDATE and DELETE queries to the backup file
	$update_query	= 'UPDATE `' . BOOKACTI_TABLE_EVENTS . '` as E'
					. ' INNER JOIN `' . $temp_table . '` as TE ON E.id = TE.id'
					. ' SET E.repeat_from = TE.repeat_from'
					. ' WHERE TE.repeat_from < E.repeat_from;';
	
	$handle	= fopen( $file, 'a' );
	$write	= 0;
	if( $handle !== false ) {
		$text	= PHP_EOL . PHP_EOL;
		$text	.= $update_query;
		$text	.= PHP_EOL . PHP_EOL;
		$text	.= $delete_query;
		$write	= fwrite( $handle, $text );
		fclose( $handle );
	}
	
	return $write ? $inserted : false;
}


/**
 * Create a .sql file to archive group of events prior to a date
 * @since 1.7.0
 * @param string $date
 * @return boolean
 */
function bookacti_archive_group_of_events_prior_to( $date ) {
	$filename_groups	= $date . '-groups-of-events.sql';
	$table_groups		= BOOKACTI_TABLE_EVENT_GROUPS;
	$where_groups		= sprintf( "id IN ( SELECT group_id FROM " . BOOKACTI_TABLE_GROUPS_EVENTS . " GROUP BY group_id HAVING MAX( event_end ) < '%s' )", $date . ' 00:00:00' );
	$archive_groups		= bookacti_archive_database( $filename_groups, $table_groups, $where_groups );
	return $archive_groups;
}


/**
 * Create a .sql file to archive grouped events prior to a date
 * @since 1.7.0
 * @param string $date
 * @return boolean
 */
function bookacti_archive_grouped_events_prior_to( $date ) {
	$filename_events	= $date . '-grouped-events.sql';
	$table_events		= BOOKACTI_TABLE_GROUPS_EVENTS;
	$where_events		= sprintf( "event_end < '%s'", $date . ' 00:00:00' );
	$archive_events		= bookacti_archive_database( $filename_events, $table_events, $where_events );
	return $archive_events;
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
	
	// Where clauses
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
 * Delete a large amount of rows from a table without using DELETE FROM query
 * Create a temp table, insert non-deleted rows, drop original table, rename temp table to the original table name
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $original_table
 * @param string $where
 * @param array $variables
 * @return boolean
 */
function bookacti_delete_rows_from_table( $original_table, $where, $variables ) {
	global $wpdb;
	$wpdb->hide_errors();
	$temp_table = $original_table . '_temp';
	
	// Remove the temporary table if it already exists
	$delete_temp_table_query = 'DROP TABLE IF EXISTS ' . $temp_table . '; ';
	$wpdb->query( $delete_temp_table_query );
	
	// Create a table with only the non-deleted data
	$create_temp_table_query = 'CREATE TABLE ' . $temp_table . ' LIKE ' . $original_table . ';';
	if( ! function_exists( 'dbDelta' ) ) { require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); }
	dbDelta( $create_temp_table_query );
	
	// Fill the temp table
	$insert_query	= ' INSERT INTO ' . $temp_table
					. ' SELECT * FROM ' . $original_table . ' as E '
					. ' WHERE NOT(' . $where . ' )';
	if( $variables ) {
		$insert_query	= $wpdb->prepare( $insert_query, $variables );
	}
	$inserted = $wpdb->query( $insert_query );
	
	if( $inserted === false ) { return false; }
	
	// Change the auto_increment value
	$ai_query	= ' SELECT `AUTO_INCREMENT` ' 
				. ' FROM  INFORMATION_SCHEMA.TABLES '
				. ' WHERE TABLE_SCHEMA = "' . DB_NAME . '"'
				. ' AND   TABLE_NAME   = "' . $original_table . '";';
	$ai_value	= intval( $wpdb->get_var( $ai_query ) );
	
	if( ! $ai_value ) { return false; }
	
	$update_temp_table_ai_query = 'ALTER TABLE ' . $temp_table . ' AUTO_INCREMENT = %d;';
	$update_temp_table_ai_query = $wpdb->prepare( $update_temp_table_ai_query, $ai_value );
	$wpdb->query( $update_temp_table_ai_query );
	
	// Remove original table
	$delete_table_query = 'DROP TABLE IF EXISTS ' . $original_table . '; ';
	$wpdb->query( $delete_table_query );
	
	// Rename temp table to original table name
	$delete_table_query = 'RENAME TABLE ' . $temp_table . ' TO ' . $original_table . '; ';
	$wpdb->query( $delete_table_query );
	
	return true;
}


/**
 * Delete repeated event exceptions prior to a date
 * @since 1.7.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param string $date
 * @return int|false
 */
function bookacti_delete_repeated_events_exceptions_prior_to( $date ) {
	global $wpdb;
	
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_EXCEPTIONS ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove repetead events exceptions
	$deleted = bookacti_delete_rows_from_table( BOOKACTI_TABLE_EXCEPTIONS, 'exception_value < %s', array( $date ) );
	
	if( $deleted === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Delete events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @param boolean $delete_meta
 * @return int|false
 */
function bookacti_delete_events_prior_to( $date, $delete_meta = true ) {
	global $wpdb;
	
	$variables = array( $date . ' 00:00:00', $date );
	
	// Remove metadata first
	if( $delete_meta ) {
		$where_meta	= 'object_type = "event" AND object_id IN( '
						. ' SELECT id FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
						. ' WHERE ( ( E.repeat_freq IS NULL OR E.repeat_freq = "none" ) AND E.end < %s ) '
						. ' OR ( ( E.repeat_freq IS NOT NULL AND E.repeat_freq != "none" ) AND E.repeat_to < %s )'
					. ' )';
		bookacti_delete_rows_from_table( BOOKACTI_TABLE_META, $where_meta, $variables );
	}
	
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_EVENTS;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove the rows
	$where	= ' ( ( repeat_freq IS NULL OR repeat_freq = "none" ) AND end < %s ) '
			. ' OR ( ( repeat_freq IS NOT NULL AND repeat_freq != "none" ) AND repeat_to < %s )';
	$deleted = bookacti_delete_rows_from_table( BOOKACTI_TABLE_EVENTS, $where, $variables );
	
	if( $deleted === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Retrict repeated events that have started before a specific date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return int|false
 */
function bookacti_restrict_started_repeated_events_to( $date ) {
	global $wpdb;
	
	$query	= ' UPDATE ' . BOOKACTI_TABLE_EVENTS
			. ' SET repeat_from = %s '
			. ' WHERE ( repeat_freq IS NOT NULL AND repeat_freq != "none" ) '
			. ' AND repeat_to >= %s '
			. ' AND repeat_from < %s ';
	
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
 * @param boolean $delete_meta
 * @return int|false
 */
function bookacti_delete_group_of_events_prior_to( $date, $delete_meta = true ) {
	global $wpdb;
	
	$select_query	= ' SELECT GE.group_id FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
					. ' GROUP BY GE.group_id '
					. ' HAVING MAX( GE.event_end ) < %s ';
	
	$variables	= array( $date . ' 00:00:00' );
	
	// Remove metadata first
	if( $delete_meta ) {
		$where_meta	= 'object_type = "group_of_events" AND object_id IN( ' . $select_query . ' )';
		bookacti_delete_rows_from_table( BOOKACTI_TABLE_META, $where_meta, $variables );
	}
	
	// Remove group of events before the events themselves!
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_EVENT_GROUPS ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove the rows
	$where_groups_of_events	= 'id IN( ' . $select_query . ' )';
	$deleted_groups_of_events = bookacti_delete_rows_from_table( BOOKACTI_TABLE_EVENT_GROUPS, $where_groups_of_events, $variables );
	
	if( $deleted_groups_of_events === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Delete groups of events prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @return int|false
 */
function bookacti_delete_grouped_events_prior_to( $date ) {
	global $wpdb;
	
	// Remove group of events before the events themselves!
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	$variables	= array( $date . ' 00:00:00' );
	$where_events_of_groups	= 'event_end < %s';
	$deleted_events_of_groups = bookacti_delete_rows_from_table( BOOKACTI_TABLE_GROUPS_EVENTS, $where_events_of_groups, $variables );
	
	if( $deleted_events_of_groups === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Delete bookings prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @param boolean $delete_meta
 * @return int|false
 */
function bookacti_delete_bookings_prior_to( $date, $delete_meta = true ) {
	global $wpdb;
	
	$variables = array( $date . ' 00:00:00' );
	
	// Remove metadata first
	if( $delete_meta ) {
		$where_meta	= 'object_type = "booking" '
					. ' AND object_id IN( ' 
						. 'SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.event_end < %s '
					. ' )';
		bookacti_delete_rows_from_table( BOOKACTI_TABLE_META, $where_meta, $variables );
	}
	
	// Remove bookings
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_BOOKINGS ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove the rows
	$where	= 'event_end < %s';
	$deleted = bookacti_delete_rows_from_table( BOOKACTI_TABLE_BOOKINGS, $where, $variables );
	
	if( $deleted === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Delete booking groups prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @param boolean $delete_meta
 * @return int|false
 */
function bookacti_delete_booking_groups_prior_to( $date, $delete_meta = true ) {
	global $wpdb;
	
	$select_query	= ' SELECT B.group_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
					. ' WHERE B.group_id IS NOT NULL '
					. ' GROUP BY B.group_id '
					. ' HAVING MAX( B.event_end ) < %s ';
	
	$variables	= array( $date . ' 00:00:00' );
	
	// Remove metadata first
	if( $delete_meta ) {
		$where_meta	= 'object_type = "booking_group" AND object_id IN( ' . $select_query . ' )';
		bookacti_delete_rows_from_table( BOOKACTI_TABLE_META, $where_meta, $variables );
	}
	
	// Remove booking groups
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove the rows
	$where	= 'id IN ( ' . $select_query . ' )';
	$deleted = bookacti_delete_rows_from_table( BOOKACTI_TABLE_BOOKING_GROUPS, $where, $variables );
	
	if( $deleted === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}


/**
 * Delete metadata for bookings and events (groups) prior to a date
 * @since 1.7.0
 * @global wpdb $wpdb
 * @param string $date
 * @param boolean $delete_meta
 * @return int|false
 */
function bookacti_delete_bookings_and_events_meta_prior_to( $date ) {
	global $wpdb;
	
	// Where clauses
	$where_events			= '( ( repeat_freq IS NULL OR repeat_freq = "none" ) AND end < %s ) OR ( ( repeat_freq IS NOT NULL AND repeat_freq != "none" ) AND repeat_to < %s )';
	$where_group_of_events	= 'GROUP BY group_id HAVING MAX( event_end ) < %s';
	$where_bookings			= 'event_end < %s';
	$where_booking_groups	= 'id IN ( SELECT B.group_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B WHERE B.group_id IS NOT NULL GROUP BY B.group_id HAVING MAX( event_end ) < %s )';
	
	$where	= '( object_type = "event" AND object_id IN ( SELECT id FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE ' . $where_events . ' ) ) ';
	$where .= 'OR ( object_type = "group_of_events" AND object_id IN ( SELECT group_id FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' ' . $where_group_of_events . ' ) ) ';
	$where .= 'OR ( object_type = "booking" AND object_id IN ( SELECT id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE ' . $where_bookings . ' ) ) ';
	$where .= 'OR ( object_type = "booking_group" AND object_id IN ( SELECT id FROM ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' WHERE ' . $where_booking_groups . ' ) ) ';
	
	$variables = array( $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00', $date . ' 00:00:00' );
	
	// Remove meta
	// Count the initial amount of rows
	$count_query	= 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_META ;
	$count_before	= intval( $wpdb->get_var( $count_query ) );
	
	// Remove the rows
	$deleted = bookacti_delete_rows_from_table( BOOKACTI_TABLE_META, $where, $variables );
	
	if( $deleted === false ) { return false; }
	
	// Count the current amount of rows
	$count_after = intval( $wpdb->get_var( $count_query ) );
	
	return $count_before - $count_after;
}




// 4. DB BACKUP RESTORATION

/**
 * Import archived data in the database
 * @since 1.7.0
 * @param array|string $file Full path to a .sql file
 * @return boolean|int
 */
function bookacti_import_sql_file( $file ) {
	// Check if the file exists
	if( ! file_exists( $file ) || ! substr( $file, -4 ) === '.sql' ) { return false; }

	global $wpdb;
	$wpdb->hide_errors();

	$file_content = file_get_contents( $file );
	$queries = explode( PHP_EOL . PHP_EOL, $file_content );
	$global_result = true;
	foreach( $queries as $query ) {
		$result = $wpdb->query( $query );
		if( $global_result !== false ) {
			if( $result === false )					{ $global_result = false; }
			else if( ! is_int( $global_result ) )	{ $global_result = $result; }
		}
	}

	return $global_result;
}