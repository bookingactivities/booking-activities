<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Create the tables to save the events states
function bookacti_create_tables() {
	global $wpdb;
	$wpdb->hide_errors();

	// Prepare the creation queries
	$table_templates_query = 'CREATE TABLE ' . BOOKACTI_TABLE_TEMPLATES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		title VARCHAR(128), 
		start_date DATE, 
		end_date DATE,  
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_activities_query = 'CREATE TABLE ' . BOOKACTI_TABLE_ACTIVITIES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		title VARCHAR(128), 
		duration VARCHAR(12) NOT NULL DEFAULT "000.01:00:00", 
		is_resizable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, 
		color VARCHAR(9) NOT NULL DEFAULT "#3a87ad", 
		availability MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0, 
		active TINYINT(1) NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_events_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EVENTS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		template_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		activity_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title VARCHAR(128), 
		start DATETIME, 
		end DATETIME, 
		availability MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0, 
		repeat_freq VARCHAR(8) NOT NULL DEFAULT "none", 
		repeat_from DATE, 
		repeat_to DATE,
		active TINYINT(1) NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_event_groups_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		category_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title VARCHAR(128), 
		active TINYINT(1) NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_groups_events_query = 'CREATE TABLE ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		group_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_start DATETIME, 
		event_end DATETIME, 
		active TINYINT(1) NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_group_categories_query = 'CREATE TABLE ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		template_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title VARCHAR(128), 
		active TINYINT(1) NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_meta_query = 'CREATE TABLE ' . BOOKACTI_TABLE_META . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		object_type VARCHAR(128), 
		object_id MEDIUMINT(9) UNSIGNED, 
		meta_key VARCHAR(255), 
		meta_value LONGTEXT ) ' . $wpdb->get_charset_collate() . ';';

	$table_permissions_query = 'CREATE TABLE ' . BOOKACTI_TABLE_PERMISSIONS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		object_type VARCHAR(128), 
		object_id MEDIUMINT(9) UNSIGNED, 
		user_id MEDIUMINT(9) UNSIGNED ) ' . $wpdb->get_charset_collate() . ';';

	$table_templates_activities_query = 'CREATE TABLE ' . BOOKACTI_TABLE_TEMP_ACTI . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		template_id MEDIUMINT(9) UNSIGNED, 
		activity_id MEDIUMINT(9) UNSIGNED ) ' . $wpdb->get_charset_collate() . ';';

	$table_exceptions_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EXCEPTIONS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		exception_type VARCHAR(10) NOT NULL DEFAULT "date",
		exception_value VARCHAR(10) ) ' . $wpdb->get_charset_collate() . ';';

	//user_id can accept hashes of 32 chars, that is why it is a VARCHAR(32)
	$table_bookings_query = 'CREATE TABLE ' . BOOKACTI_TABLE_BOOKINGS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		user_id VARCHAR(32), 
		order_id MEDIUMINT(9) UNSIGNED, 
		group_id MEDIUMINT(9) UNSIGNED, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_start DATETIME, 
		event_end DATETIME, 
		state VARCHAR(32) NOT NULL DEFAULT "booked", 
		creation_date DATETIME, 
		expiration_date DATETIME, 
		quantity MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 1, 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	$table_booking_groups_query = 'CREATE TABLE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		event_group_id MEDIUMINT(9) UNSIGNED, 
		user_id VARCHAR(32), 
		order_id MEDIUMINT(9) UNSIGNED, 
		state VARCHAR(32) NOT NULL DEFAULT "booked", 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ) ' . $wpdb->get_charset_collate() . ';';

	// Execute the queries
	if( ! function_exists( 'dbDelta' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	dbDelta( $table_templates_query 
			. $table_activities_query 
			. $table_events_query 
			. $table_event_groups_query 
			. $table_groups_events_query 
			. $table_group_categories_query 
			. $table_permissions_query
			. $table_meta_query
			. $table_templates_activities_query 
			. $table_exceptions_query 
			. $table_bookings_query
			. $table_booking_groups_query );
}


// Drop Booking Activities tables
function bookacti_drop_tables() {
	global $wpdb;
	$wpdb->hide_errors();

	// Prepare the creation queries
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_TEMPLATES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_ACTIVITIES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EVENTS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EVENT_GROUPS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_GROUPS_EVENTS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_GROUP_CATEGORIES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_META . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_PERMISSIONS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_TEMP_ACTI . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EXCEPTIONS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_BOOKINGS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_BOOKING_GROUPS . '; ' );
}