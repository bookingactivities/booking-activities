<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Remove corrupted repeated events (groups)
 * This function is temporary
 * @since 1.16.24
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_db_to_1_16_24( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time to 1.16.24
	if( ! $old_version || version_compare( $old_version, '1.16.24', '>=' ) ) { return; }
	
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET repeat_freq = "none", repeat_step = NULL, repeat_on = NULL, repeat_from = NULL, repeat_to = NULL, repeat_exceptions = NULL WHERE repeat_from = "0000-00-00" OR repeat_to = "0000-00-00"';
	$wpdb->query( $query );
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' SET repeat_freq = "none", repeat_step = NULL, repeat_on = NULL, repeat_from = NULL, repeat_to = NULL, repeat_exceptions = NULL WHERE repeat_from = "0000-00-00" OR repeat_to = "0000-00-00"';
	$wpdb->query( $query );
}
add_action( 'bookacti_db_updated', 'bookacti_update_db_to_1_16_24', 110 );


/**
 * Update changes to 1.15.0
 * This function is temporary
 * @since 1.15.0
 * @version 1.16.2
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_db_to_1_15_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time to 1.15.0
	if( ! $old_version || version_compare( $old_version, '1.15.0', '>=' ) ) { return; }
	
	global $wpdb;
	
	// Rename minTime, maxTime in calendar fields settings and calendar editor settings
	$query = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_key = "slotMinTime" WHERE meta_key = "minTime"';
	$wpdb->query( $query );
	$query = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_key = "slotMaxTime" WHERE meta_key = "maxTime"';
	$wpdb->query( $query );
}
add_action( 'bookacti_db_updated', 'bookacti_update_db_to_1_15_0', 100 );