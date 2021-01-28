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
 * @version 1.9.0
 * @param array $return_array
 * @param array $booking_form_values
 * @param int $form_id
 */
function bookacti_send_notification_when_booking_is_made( $return_array, $booking_form_values, $form_id ) {
	foreach( $return_array[ 'bookings' ] as $booking ) {
		// Send a booking confirmation to the customer
		bookacti_send_notification( 'customer_' . $booking_form_values[ 'status' ] . '_booking', $booking[ 'id' ], $booking[ 'type' ] );
		// Alert administrators that a new booking has been made
		bookacti_send_notification( 'admin_new_booking', $booking[ 'id' ], $booking[ 'type' ] );
	}
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_notification_when_booking_is_made', 100, 3 );



/**
 * Send a notification to admin and customer when a single booking status changes
 * @since 1.2.1 (was bookacti_send_email_when_booking_state_changes in 1.2.0)
 * @version 1.9.0
 * @param object $booking
 * @param string $status
 * @param array $args
 */
function bookacti_send_notification_when_booking_state_changes( $booking, $status, $args ) {
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If the booking is part of a group and the whole group is affected by this change, do not send notification here
	if( isset( $args[ 'booking_group_state_changed' ] ) && $args[ 'booking_group_state_changed' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to = apply_filters( 'bookacti_booking_state_change_notification_recipient', 'both', $booking->id, $status, $args );
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to === 'customer' || $send_to === 'both' ) {
		bookacti_send_notification( 'customer_' . $status . '_booking', $booking->id, 'single' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to === 'admin' || $send_to === 'both' ) {
		bookacti_send_notification( 'admin_' . $status . '_booking', $booking->id, 'single' );
	}
}
add_action( 'bookacti_booking_state_changed', 'bookacti_send_notification_when_booking_state_changes', 10, 3 );


/**
 * Send a notification to admin and customer when a booking group status changes
 * @since 1.2.1 (was bookacti_send_email_when_booking_group_state_changes in 1.2.0)
 * @version 1.9.0
 * @param int $booking_group_id
 * @param array $bookings
 * @param string $status
 * @param array $args
 */
function bookacti_send_notification_when_booking_group_state_changes( $booking_group_id, $bookings, $status, $args ) {
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to = apply_filters( 'bookacti_booking_group_state_change_notification_recipient', 'both', $booking_group_id, $status, $args );
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to === 'customer' || $send_to === 'both' ) {
		bookacti_send_notification( 'customer_' . $status . '_booking', $booking_group_id, 'group' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to === 'admin' || $send_to === 'both' ) {
		bookacti_send_notification( 'admin_' . $status . '_booking', $booking_group_id, 'group' );
	}
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_send_notification_when_booking_group_state_changes', 10, 4 );


/**
 * Send a notification to admin and customer when a booking is rescheduled
 * 
 * @since 1.2.1 (was bookacti_send_email_when_booking_is_rescheduled in 1.2.0)
 * @version 1.8.6
 * @param object $booking
 * @param object $old_booking
 * @param array $args
 */
function bookacti_send_notification_when_booking_is_rescheduled( $booking, $old_booking, $args ) {
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to = apply_filters( 'bookacti_reschedule_notification_recipient', 'both', $booking, $old_booking, $args );
	
	$notification_args = array( 'tags' => array(
		'booking_old_start_raw' => $old_booking->event_start,
		'booking_old_end_raw' => $old_booking->event_end
	));
	
	$datetime_format = bookacti_get_message( 'date_format_long' );
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to === 'both' || $send_to === 'customer' ) {
		$notification_id = 'customer_rescheduled_booking';
		
		// Temporarilly switch locale to the user's
		$locale	= apply_filters( 'bookacti_notification_locale', is_numeric( $booking->user_id ) ? bookacti_get_user_locale( $booking->user_id ) : bookacti_get_site_locale(), $notification_id, $booking, 'single', $notification_args );
		bookacti_switch_locale( $locale );

		// Add reschedule specific tags
		$notification_args[ 'tags' ][ '{booking_old_start}' ]	= bookacti_format_datetime( $old_booking->event_start, $datetime_format );
		$notification_args[ 'tags' ][ '{booking_old_end}' ]		= bookacti_format_datetime( $old_booking->event_end, $datetime_format );

		// Switch locale back to normal
		bookacti_restore_locale();

		bookacti_send_notification( $notification_id, $booking->id, 'single', $notification_args );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to === 'both' || $send_to === 'admin' ) {
		$notification_id = 'admin_rescheduled_booking';
		
		// Temporarilly switch locale user default's
		$locale = apply_filters( 'bookacti_notification_locale', bookacti_get_site_locale(), $notification_id, $booking, 'single', $notification_args );
		bookacti_switch_locale( $locale );

		// Add reschedule specific tags
		$notification_args[ 'tags' ][ '{booking_old_start}' ]	= bookacti_format_datetime( $old_booking->event_start, $datetime_format );
		$notification_args[ 'tags' ][ '{booking_old_end}' ]		= bookacti_format_datetime( $old_booking->event_end, $datetime_format );

		// Switch locale back to normal
		bookacti_restore_locale();

		bookacti_send_notification( $notification_id, $booking->id, 'single', $notification_args );
	}
}
add_action( 'bookacti_booking_rescheduled', 'bookacti_send_notification_when_booking_is_rescheduled', 10, 3 );


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