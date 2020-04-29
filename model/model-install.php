<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Create Booking Activities database tables
 * @version 1.8.0
 * @global wpdb $wpdb
 */
function bookacti_create_tables() {
	global $wpdb;
	$wpdb->hide_errors();
	$collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		$collate = $wpdb->get_charset_collate();
	}
	
	$table_templates_query = 'CREATE TABLE ' . BOOKACTI_TABLE_TEMPLATES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title TEXT, 
		start_date DATE, 
		end_date DATE,  
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_activities_query = 'CREATE TABLE ' . BOOKACTI_TABLE_ACTIVITIES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title TEXT, 
		duration VARCHAR(12) NOT NULL DEFAULT "000.01:00:00", 
		is_resizable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, 
		color VARCHAR(9) NOT NULL DEFAULT "#3a87ad", 
		availability MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0, 
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_templates_activities_query = 'CREATE TABLE ' . BOOKACTI_TABLE_TEMP_ACTI . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		template_id MEDIUMINT(9) UNSIGNED, 
		activity_id MEDIUMINT(9) UNSIGNED,
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	$table_events_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EVENTS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		template_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		activity_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title TEXT, 
		start DATETIME, 
		end DATETIME, 
		availability MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0, 
		repeat_freq VARCHAR(8) NOT NULL DEFAULT "none", 
		repeat_from DATE, 
		repeat_to DATE,
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	$table_exceptions_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EXCEPTIONS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		exception_type VARCHAR(10) NOT NULL DEFAULT "date",
		exception_value VARCHAR(10),
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	$table_event_groups_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		category_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title TEXT, 
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_groups_events_query = 'CREATE TABLE ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		group_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_start DATETIME, 
		event_end DATETIME, 
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_group_categories_query = 'CREATE TABLE ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		template_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		title TEXT, 
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_forms_query = 'CREATE TABLE ' . BOOKACTI_TABLE_FORMS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		title TEXT,
		user_id MEDIUMINT(9),
		creation_date DATETIME,
		status VARCHAR(32) NOT NULL DEFAULT "publish",
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	$table_form_fields_query = 'CREATE TABLE ' . BOOKACTI_TABLE_FORM_FIELDS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		form_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		name VARCHAR(128), 
		type VARCHAR(128), 
		title TEXT, 
		label TEXT, 
		options TEXT, 
		value TEXT, 
		placeholder TEXT, 
		tip TEXT, 
		required TINYINT(1) NOT NULL DEFAULT 0, 
		active TINYINT(1) NOT NULL DEFAULT 1, 
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	// user_id can accept hashes of 32 chars and email addresses, that is why it is a VARCHAR(64)
	$table_bookings_query = 'CREATE TABLE ' . BOOKACTI_TABLE_BOOKINGS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		user_id VARCHAR(64), 
		order_id MEDIUMINT(9) UNSIGNED, 
		form_id MEDIUMINT(9) UNSIGNED, 
		group_id MEDIUMINT(9) UNSIGNED, 
		event_id MEDIUMINT(9) UNSIGNED NOT NULL, 
		event_start DATETIME, 
		event_end DATETIME, 
		state VARCHAR(32) NOT NULL DEFAULT "booked", 
		payment_status VARCHAR(32) NOT NULL DEFAULT "none", 
		creation_date DATETIME, 
		expiration_date DATETIME, 
		quantity MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 1, 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_booking_groups_query = 'CREATE TABLE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		event_group_id MEDIUMINT(9) UNSIGNED, 
		user_id VARCHAR(64), 
		order_id MEDIUMINT(9) UNSIGNED, 
		form_id MEDIUMINT(9) UNSIGNED, 
		state VARCHAR(32) NOT NULL DEFAULT "booked",
		payment_status VARCHAR(32) NOT NULL DEFAULT "none", 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_meta_query = 'CREATE TABLE ' . BOOKACTI_TABLE_META . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		object_type VARCHAR(128), 
		object_id MEDIUMINT(9) UNSIGNED, 
		meta_key VARCHAR(255), 
		meta_value LONGTEXT,
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_exports_query = 'CREATE TABLE ' . BOOKACTI_TABLE_EXPORTS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		user_id MEDIUMINT(9) UNSIGNED, 
		type VARCHAR(128), 
		args TEXT, 
		creation_date DATETIME, 
		expiration_date DATETIME, 
		sequence MEDIUMINT(9) UNSIGNED DEFAULT 0, 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1, 
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	$table_permissions_query = 'CREATE TABLE ' . BOOKACTI_TABLE_PERMISSIONS . ' ( 
		id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, 
		object_type VARCHAR(128), 
		object_id MEDIUMINT(9) UNSIGNED, 
		user_id MEDIUMINT(9) UNSIGNED,
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	// Execute the queries
	if( ! function_exists( 'dbDelta' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	dbDelta( $table_templates_query 
			. $table_activities_query 
			. $table_templates_activities_query 
			. $table_events_query 
			. $table_exceptions_query 
			. $table_event_groups_query 
			. $table_groups_events_query 
			. $table_group_categories_query 
			. $table_forms_query
			. $table_form_fields_query
			. $table_bookings_query
			. $table_booking_groups_query
			. $table_meta_query	
			. $table_exports_query	
			. $table_permissions_query );
}


/**
 * Remove Bookings activities tables from database
 * @version 1.8.0
 * @global wpdb $wpdb
 */
function bookacti_drop_tables() {
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_TEMPLATES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_ACTIVITIES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_TEMP_ACTI . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EVENTS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EXCEPTIONS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EVENT_GROUPS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_GROUPS_EVENTS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_GROUP_CATEGORIES . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_FORMS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_FORM_FIELDS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_BOOKINGS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_BOOKING_GROUPS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_META . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_EXPORTS . '; ' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_PERMISSIONS . '; ' );
}


/**
 * Remove Bookings activities user data
 * @since 1.6.0
 * @global wpdb $wpdb
 */
function bookacti_delete_user_data() {
	global $wpdb;
	$wpdb->hide_errors();
	
	$query = 'DELETE FROM ' . $wpdb->usermeta . ' WHERE meta_key LIKE "bookacti_%" ';
	$wpdb->query( $query );
}