<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register a daily cron event to clean notification logs
 * @since 1.7.1
 */
function bookacti_register_cron_event_to_clean_latest_notifications() {
	if( ! wp_next_scheduled ( 'bookacti_clean_latest_notifications' ) ) {
		wp_schedule_event( time(), 'daily', 'bookacti_clean_latest_notifications' );
	}
}
add_action( 'bookacti_activate', 'bookacti_register_cron_event_to_clean_latest_notifications' );


/**
 * Deregister the daily cron event to clean notification logs
 * @since 1.7.1 (was bookacti_clear_houly_clean_expired_bookings)
 */
function bookacti_deregister_cron_event_to_clean_latest_notifications() {
	wp_clear_scheduled_hook( 'bookacti_clean_latest_notifications' );
}
add_action( 'bookacti_deactivate', 'bookacti_deregister_cron_event_to_clean_latest_notifications' );


/**
 * Controller - Send async notifications
 * @since 1.16.0
 * @version 1.16.45
 */
function bookacti_controller_send_async_notifications() {
	// Do not send on AJAX calls to avoid multiple calls and expired cache
	$is_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	if( $is_ajax ) { return; }
	
	// Check if CRON is running (so it doesn't slow down any users)
	if( ! wp_doing_cron() ) { return; }
	
	// Check if the desired action is to send the async notifications
	if( empty( $_REQUEST[ 'bookacti_send_async_notifications' ] ) ) { return; }
	
	// Check if the key is correct
	if( empty( $_REQUEST[ 'key' ] ) ) { return; }
	$sanitized_key = sanitize_title_with_dashes( $_REQUEST[ 'key' ] );
	$secret_key    = get_option( 'bookacti_cron_key' );
	if( $sanitized_key !== $secret_key ) { return; }
	
	// Check if async notifications are allowed
	$allow_async = apply_filters( 'bookacti_allow_async_notifications', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ) );
	if( ! $allow_async ) { return; }
	
	bookacti_send_async_notifications();
}
add_action( 'init', 'bookacti_controller_send_async_notifications', 100 );


/**
 * Send async notifications
 * @since 1.16.0
 * @version 1.16.37
 */
function bookacti_send_async_notifications() {
	$nb_sent = array();
	
	// Make sure to run this function once per page load
	if( defined( 'BOOKACTI_SENDING_ASYNC_NOTIFICATIONS' ) ) { return $nb_sent; }
	define( 'BOOKACTI_SENDING_ASYNC_NOTIFICATIONS', 1 );
	
	$alloptions = wp_load_alloptions();
	$async_notifications = isset( $alloptions[ 'bookacti_async_notifications' ] ) ? maybe_unserialize( $alloptions[ 'bookacti_async_notifications' ] ) : get_option( 'bookacti_async_notifications', array() );
	
	// Remove the async notifications from db right after retrieving them
	update_option( 'bookacti_async_notifications', array() );
	
	if( ! $async_notifications ) { return $nb_sent; }
	
	// Try to merge the notifications sent to the same user
	$merging_allowed = apply_filters( 'bookacti_async_notifications_merging_allowed', true, $async_notifications );
	if( $merging_allowed ) {
		$async_notifications = bookacti_merge_planned_notifications( $async_notifications );
	}
	
	// If there are a lot of notifications to send, this operation can take a while
	// So we need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 'send_async_notifications' );
	
	// Send the notifications
	foreach( $async_notifications as $async_notification ) {
		bookacti_send_notification( $async_notification[ 'notification_id' ], $async_notification[ 'booking_id' ], $async_notification[ 'booking_type'], $async_notification[ 'args' ], 0 );
	}
}
add_action( 'bookacti_cron_send_async_notifications', 'bookacti_send_async_notifications', 10 );


/**
 * Clean the latest emails logs
 * @since 1.7.1
 */
function bookacti_clean_latest_emails_log() {
	$latest_emails_sent = get_option( 'bookacti_latest_emails_sent' );
	if( ! $latest_emails_sent ) { return; }
	
	$current_datetime	= new DateTime( 'now' );
	$yesterday_datetime	= clone $current_datetime;
	$yesterday_datetime->sub( new DateInterval( 'P1D' ) );
	
	foreach( $latest_emails_sent as $recipient => $emails_sent ) {
		// Remove values before yesterday
		foreach( $emails_sent as $i => $email_sent ) {
			$email_datetime = new DateTime( $email_sent );
			if( $email_datetime < $yesterday_datetime ) {
				unset( $latest_emails_sent[ $recipient ][ $i ] );
			}
		}
		// Remove the whole recipient array if no emails have been sent to him since yesterday
		if( empty( $latest_emails_sent[ $recipient ] ) ) {
			unset( $latest_emails_sent[ $recipient ] );
		}
	}
	
	update_option( 'bookacti_latest_emails_sent', $latest_emails_sent );
}
add_action( 'bookacti_clean_latest_notifications', 'bookacti_clean_latest_emails_log' );


/**
 * Send a notification to admin and customer when a new booking is made
 * @since 1.2.2 (was bookacti_send_notification_admin_new_booking in 1.2.1)
 * @version 1.14.1
 * @param array $return_array
 * @param array $booking_form_values
 * @param int $form_id
 */
function bookacti_send_notification_when_booking_is_made( $return_array, $booking_form_values, $form_id ) {
	$booking_status = ! empty( $booking_form_values[ 'status' ] ) ? $booking_form_values[ 'status' ] : 'booked';
	foreach( $return_array[ 'bookings' ] as $booking ) {
		$notification_args = apply_filters( 'bookacti_new_booking_notification_args', array(), $booking, $booking_form_values );
		
		// Send a booking confirmation to the customer
		bookacti_send_notification( 'customer_' . $booking_status . '_booking', $booking[ 'id' ], $booking[ 'type' ], $notification_args );
		
		// Alert administrators that a new booking has been made
		bookacti_send_notification( 'admin_new_booking', $booking[ 'id' ], $booking[ 'type' ], $notification_args );
	}
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_notification_when_booking_is_made', 100, 3 );


/**
 * Format some rescheduled notifications tags
 * @since 1.10.0
 * @version 1.16.0
 * @param array $tags
 * @param object $booking
 * @param string $booking_type
 * @param array $notification
 * @param array $args
 * @return array
 */
function bookacti_format_reschedule_notifications_tags_values( $tags, $booking, $booking_type, $notification, $args ) {
	if( strpos( $notification[ 'id' ], '_rescheduled' ) === false ) { return $tags; }
	
	// Set the {booking_old_start} and {booking_old_end} from their unformatted counterpart
	$datetime_format = bookacti_get_message( 'date_format_long' );
	if( isset( $tags[ '{booking_old_start_raw}' ] ) ) { $tags[ '{booking_old_start}' ] = bookacti_format_datetime( $tags[ '{booking_old_start_raw}' ], $datetime_format ); }
	if( isset( $tags[ '{booking_old_end_raw}' ] ) )   { $tags[ '{booking_old_end}' ]   = bookacti_format_datetime( $tags[ '{booking_old_end_raw}' ], $datetime_format ); }
	
	return $tags;
}
add_filter( 'bookacti_notifications_tags_values', 'bookacti_format_reschedule_notifications_tags_values', 10, 5 );


/**
 * Display private columns of bookings list in notifications
 * @since 1.8.6
 * @param boolean $allowed
 * @param array $filters
 * @param array $columns
 * @return boolean
 */
function bookacti_display_private_columns_in_notifications( $allowed, $filters, $columns ) {
	if( ! empty( $GLOBALS[ 'bookacti_notification_private_columns' ] ) ) { $allowed = 1; }
	return $allowed;
}
add_filter( 'bookacti_user_booking_list_display_private_columns', 'bookacti_display_private_columns_in_notifications', 10, 3 );
add_filter( 'bookacti_user_booking_list_can_manage_bookings', 'bookacti_display_private_columns_in_notifications', 10, 3 );