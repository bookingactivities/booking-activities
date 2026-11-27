<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Delete serialized PHP objects from metadata
 * This function is temporary
 * @since 1.18.8
 * @version 1.19.9
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_db_to_1_18_8( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time to 1.18.8
	if( ! $old_version || version_compare( $old_version, '1.18.8', '>=' ) ) { return; }
	
	global $wpdb;
	
	$query     = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE meta_value LIKE %s;';
	$variables = array( '%' . $wpdb->esc_like( 'O:' ) . '%' );
	$wpdb->query( $wpdb->prepare( $query, $variables ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
add_action( 'bookacti_db_updated', 'bookacti_update_db_to_1_18_8', 140 );


/**
 * Rename form meta
 * This function is temporary
 * @since 1.18.2
 * @version 1.19.9
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_db_to_1_18_2( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time to 1.18.2
	if( ! $old_version || version_compare( $old_version, '1.18.2', '>=' ) ) { return; }
	
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_key = "css_id" WHERE object_type = "form" AND meta_key = "id"';
	$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$query = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_key = "css_class" WHERE object_type = "form" AND meta_key = "class"';
	$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
add_action( 'bookacti_db_updated', 'bookacti_update_db_to_1_18_2', 130 );


/**
 * Migrate notifications data to their own tables
 * This function is temporary
 * @since 1.18.0
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_db_to_1_18_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time to 1.18.0
	if( ! $old_version || version_compare( $old_version, '1.18.0', '>=' ) ) { return; }
	
	// Migrate notifications data to their own tables
	bookacti_migrate_legacy_notifications();
}
add_action( 'bookacti_db_updated', 'bookacti_update_db_to_1_18_0', 120 );