<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Update changes to 1.14.0
 * This function is temporary
 * @since 1.14.0
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_move_options_when_updating_to_1_14_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.13.0
	if( version_compare( $old_version, '1.14.0', '>=' ) ) { return; }
	
	// Move calendar_localization option from Messages tab to General tab
	$alloptions = wp_load_alloptions();
	if( isset( $alloptions[ 'bookacti_general_settings' ] ) && isset( $alloptions[ 'bookacti_messages_settings' ][ 'calendar_localization' ] ) ) {
		update_option( 'bookacti_general_settings', array_merge( $alloptions[ 'bookacti_general_settings' ], array( 'calendar_localization' => $alloptions[ 'bookacti_messages_settings' ][ 'calendar_localization' ] ) ) );
	}
	
}
add_action( 'bookacti_updated', 'bookacti_move_options_when_updating_to_1_14_0', 80 );


/**
 * Update changes to 1.13.0
 * This function is temporary
 * @since 1.13.0
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_move_repeat_exceptions_when_updating_to_1_13_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.13.0
	if( version_compare( $old_version, '1.13.0', '>=' ) ) { return; }
	
	// Increase max ececution time in case there are a lot of repeat exceptions to convert
	bookacti_increase_max_execution_time( 'upgrade_database_to_1_13_0' );
	
	global $wpdb;
	
	// Get repeat exceptions per event / group
	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' ORDER BY object_type, object_id';
	$results = $wpdb->get_results( $query );
	if( ! $results ) { return; }
	
	// Build repeat exceptions array per event / group
	$exceptions_per_event = array();
	$exceptions_per_group = array();
	foreach( $results as $result ) {
		$id = intval( $result->object_id );
		$date = bookacti_sanitize_date( $result->exception_value );
		if( ! $id || ! $date ) { continue; }
		if( $result->object_type === 'group_of_events' ) {
			if( ! isset( $exceptions_per_group[ $id ] ) ) { $exceptions_per_group[ $id ] = array(); }
			$exceptions_per_group[ $id ][] = array( 'from' => $date, 'to' => $date );
		} else {
			if( ! isset( $exceptions_per_event[ $id ] ) ) { $exceptions_per_event[ $id ] = array(); }
			$exceptions_per_event[ $id ][] = array( 'from' => $date, 'to' => $date );
		}
	}
	
	// Update events / groups repeat exceptions
	foreach( $exceptions_per_event as $event_id => $repeat_exceptions ) {
		$repeat_exceptions_sanitized = bookacti_sanitize_days_off( $repeat_exceptions );
		if( ! $repeat_exceptions_sanitized ) { continue; }
		$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET repeat_exceptions = %s WHERE id = %d';
		$query = $wpdb->prepare( $query, array( maybe_serialize( $repeat_exceptions_sanitized ), $event_id ) );
		$wpdb->query( $query );
	}
	foreach( $exceptions_per_group as $group_id => $repeat_exceptions ) {
		$repeat_exceptions_sanitized = bookacti_sanitize_days_off( $repeat_exceptions );
		if( ! $repeat_exceptions_sanitized ) { continue; }
		$query = 'UPDATE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' SET repeat_exceptions = %s WHERE id = %d';
		$query = $wpdb->prepare( $query, array( maybe_serialize( $repeat_exceptions_sanitized ), $group_id ) );
		$wpdb->query( $query );
	}
}
add_action( 'bookacti_updated', 'bookacti_move_repeat_exceptions_when_updating_to_1_13_0', 70 );


/**
 * Update changes to 1.12.0
 * This function is temporary
 * @since 1.12.0
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_fill_bookings_new_columns_when_updating_to_1_12_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.12.0-beta1
	if( version_compare( $old_version, '1.12.0-beta1', '>=' ) ) { return; }
	
	global $wpdb;
	
	// Update the groups events new activity_id column
	$query_bookings_activity_id = 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON GE.event_id = E.id SET GE.activity_id = E.activity_id WHERE GE.activity_id IS NULL;';
	$wpdb->query( $query_bookings_activity_id );
	
	// Delete inactive grouped events
	$query_delete_inactive_grouped_events = 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE active = 0;';
	$wpdb->query( $query_delete_inactive_grouped_events );
	
	// Check if the event_id column exists in exceptions table
	$query_event_id_column_exists = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . DB_NAME . '" AND TABLE_NAME = "' . BOOKACTI_TABLE_EXCEPTIONS . '" AND COLUMN_NAME = "event_id";';
	$event_id_column_exists = $wpdb->get_var( $query_event_id_column_exists );
	
	if( ! empty( $event_id_column_exists ) ) {
		// Rename event_id column to object_id and fill object_type column in exceptions table
		$query_update_exceptions = 'UPDATE ' . BOOKACTI_TABLE_EXCEPTIONS . ' SET object_type = "event", object_id = event_id;';
		$wpdb->query( $query_update_exceptions );
		// Delete event_id and exception_type columns
		$query_delete_columns = 'ALTER TABLE ' . BOOKACTI_TABLE_EXCEPTIONS . ' DROP COLUMN event_id, DROP COLUMN exception_type;';
		$wpdb->query( $query_delete_columns );
	}
}
add_action( 'bookacti_updated', 'bookacti_fill_bookings_new_columns_when_updating_to_1_12_0', 60 );


/**
 * Update changes to 1.11.0
 * This function is temporary
 * @since 1.11.0
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_fill_bookings_new_columns_when_updating_to_1_11_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.11.0-beta1
	if( version_compare( $old_version, '1.11.0-beta1', '>=' ) ) { return; }
	
	global $wpdb;
	
	// Update the bookings new activity_id column
	$query_bookings_activity_id = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' as B LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id SET B.activity_id = E.activity_id WHERE B.activity_id IS NULL';
	$wpdb->query( $query_bookings_activity_id );
	
	// Update the booking groups new category_id column
	$query_booking_groups_category_id = 'UPDATE ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as BG LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON BG.event_group_id = EG.id SET BG.category_id = EG.category_id WHERE BG.category_id IS NULL';
	$wpdb->query( $query_booking_groups_category_id );
	
	// Set repeat_on to "last_day_of_month" for the monthly events occuring on the last day of their month
	$query_monthly_events_on_last_day_of_month = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET repeat_on = "last_day_of_month" WHERE repeat_freq = "monthly" AND CAST( start AS DATE ) = LAST_DAY( CAST( start AS DATE ) )';
	$wpdb->query( $query_monthly_events_on_last_day_of_month );
}
add_action( 'bookacti_updated', 'bookacti_fill_bookings_new_columns_when_updating_to_1_11_0', 50 );


/**
 * Update changes to 1.9.0
 * This function is temporary
 * @since 1.9.0
 * @param string $old_version
 */
function bookacti_clear_sessions_when_updating_to_1_9_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.9.0
	if( version_compare( $old_version, '1.9.0', '>=' ) ) { return; }
	
	// Clear all WC customer sessions to empty carts, since cart items data are formatted differently
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$action = 'clear_sessions';
		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$tools = $tools_controller->get_tools();
		$response = $tools_controller->execute_tool( $action );
		$tool = array_merge( array(
			'id'          => $action,
			'name'        => $tools[ $action ][ 'name' ],
			'action'      => $tools[ $action ][ 'button' ],
			'description' => $tools[ $action ][ 'desc' ],
		), $response );
		
		do_action( 'woocommerce_system_status_tool_executed', $tool );
	}
}
add_action( 'bookacti_updated', 'bookacti_clear_sessions_when_updating_to_1_9_0', 40 );


/**
 * Update the refactored settings in 1.8.0
 * This function is temporary
 * @since 1.8.0
 * @version 1.8.4
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_refactored_settings_in_1_8_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.8.0
	if( version_compare( $old_version, '1.8.0', '<' ) ) {
		// Rename cancellation_min_delay_before_event option to booking_changes_deadline and 
		// Convert its value to seconds
		$cancellation_options = get_option( 'bookacti_cancellation_settings' );
		if( isset( $cancellation_options[ 'cancellation_min_delay_before_event' ] ) ) {
			$cancellation_options[ 'booking_changes_deadline' ] = intval( $cancellation_options[ 'cancellation_min_delay_before_event' ] ) * 86400;
			unset( $cancellation_options[ 'cancellation_min_delay_before_event' ] );
			update_option( 'bookacti_cancellation_settings', $cancellation_options );
		}
		
		global $wpdb;
		
		// Convert the "booking_changes_deadline" options values to seconds
		$query_booking_changes_deadline_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), IF( meta_value < 0, "", meta_value ) ) WHERE meta_key = "booking_changes_deadline"';
		$wpdb->query( $query_booking_changes_deadline_value );
		
		// Convert the "availability_period_start" options values to seconds
		$query_availability_period_start_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), meta_value ) WHERE meta_key = "availability_period_start"';
		$wpdb->query( $query_availability_period_start_value );
		
		// Convert the "availability_period_end" options values to seconds
		$query_availability_period_end_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), meta_value ) WHERE meta_key = "availability_period_end"';
		$wpdb->query( $query_availability_period_end_value );
	}
}
add_action( 'bookacti_updated', 'bookacti_update_refactored_settings_in_1_8_0', 30 );


/**
 * Remove the template settings removed in 1.7.17
 * This function is temporary
 * @since 1.7.17
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_delete_removed_template_settings_in_1_7_17( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.7.17
	if( version_compare( $old_version, '1.7.17', '<' ) ) {
		global $wpdb;
		
		// Delete templates availability_period_start
		$availability_period_start_deleted = $wpdb->delete( 
			BOOKACTI_TABLE_META, 
			array( 
				'object_type' => 'template',
				'meta_key' => 'availability_period_start'
			), 
			array( '%s', '%s' ) 
		);
		
		// Delete templates availability_period_end
		$availability_period_end_deleted = $wpdb->delete( 
			BOOKACTI_TABLE_META, 
			array( 
				'object_type' => 'template',
				'meta_key' => 'availability_period_end'
			), 
			array( '%s', '%s' ) 
		);
	}
}
add_action( 'bookacti_updated', 'bookacti_delete_removed_template_settings_in_1_7_17', 20 );


/**
 * Update the form settings and the template settings that relies on global settings removed in 1.7.16
 * This function is temporary
 * @since 1.7.16
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_removed_global_settings_in_1_7_16( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.7.16
	if( version_compare( $old_version, '1.7.16', '<' ) ) {
		// Get the global values
		$global_booking_method				= bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
		$global_availability_period_start	= bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_start' );
		$global_availability_period_end		= bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_end' );
		
		global $wpdb;
		
		// Update the "Booking method" setting (Calendar form fields)
		$booking_method_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => 'method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		$wc_product_booking_method_updated = $wpdb->update( 
			$wpdb->postmeta, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => '_bookacti_booking_method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		$wc_variation_booking_method_updated = $wpdb->update( 
			$wpdb->postmeta, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => 'bookacti_variable_booking_method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		
		// Update the "Events will be bookable in" setting (Templates, Calendar form fields)
		$availability_period_start_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_availability_period_start ? $global_availability_period_start : 0 ),
			array( 'meta_key' => 'availability_period_start', 'meta_value' => -1 ),
			array( '%d' ),
			array( '%s', '%d' )
		);
		
		// Update the "Events will be bookable in" setting (Templates, Calendar form fields)
		$availability_period_end_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_availability_period_end ? $global_availability_period_end : 0 ),
			array( 'meta_key' => 'availability_period_end', 'meta_value' => -1 ),
			array( '%d' ),
			array( '%s', '%d' )
		);
	}
}
add_action( 'bookacti_updated', 'bookacti_update_removed_global_settings_in_1_7_16', 10 );