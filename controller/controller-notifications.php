<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Send a notification to admin and customer when a new booking is made
 * 
 * @since 1.2.2 (was bookacti_send_notification_admin_new_booking in 1.2.1)
 * @version 1.5.0
 * @param int $booking_id
 * @param array $booking_form_values
 * @param string $booking_type
 * @param int $form_id
 */
function bookacti_send_notification_when_booking_is_made( $booking_id, $booking_form_values, $booking_type, $form_id = 0 ) {
	// Send a booking confirmation to the customer
	$status = $booking_type === 'group' ? bookacti_get_booking_group_state( $booking_id ) : bookacti_get_booking_state( $booking_id );
	bookacti_send_notification( 'customer_' . $status . '_booking', $booking_id, $booking_type );

	// Alert administrators that a new booking has been made
	bookacti_send_notification( 'admin_new_booking', $booking_id, $booking_type );
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_notification_when_booking_is_made', 10, 4 );



/**
 * Send a notification to admin and customer when a single booking status changes
 * 
 * @since 1.2.1 (was bookacti_send_email_when_booking_state_changes in 1.2.0)
 * @version 1.5.6
 * @param int $booking_id
 * @param string $status
 * @param array $args
 */
function bookacti_send_notification_when_booking_state_changes( $booking_id, $status, $args ) {
	
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If the booking is part of a group and the whole group is affected by this change, do not send notification here
	if( isset( $args[ 'booking_group_state_changed' ] ) && $args[ 'booking_group_state_changed' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to = apply_filters( 'bookacti_booking_state_change_notification_recipient', isset( $args[ 'is_admin' ] ) ? ( $args[ 'is_admin' ] ? 'customer' : 'admin' ) : 'both', $booking_id, $status, $args );
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to === 'customer' || $send_to === 'both' ) {
		bookacti_send_notification( 'customer_' . $status . '_booking', $booking_id, 'single' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to === 'admin' || $send_to === 'both' ) {
		bookacti_send_notification( 'admin_' . $status . '_booking', $booking_id, 'single' );
	}
}
add_action( 'bookacti_booking_state_changed', 'bookacti_send_notification_when_booking_state_changes', 10, 3 );


/**
 * Send a notification to admin and customer when a booking group status changes
 * 
 * @since 1.2.1 (was bookacti_send_email_when_booking_group_state_changes in 1.2.0)
 * @version 1.5.6
 * @param int $booking_group_id
 * @param string $status
 * @param array $args
 */
function bookacti_send_notification_when_booking_group_state_changes( $booking_group_id, $status, $args ) {
	
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to = apply_filters( 'bookacti_booking_group_state_change_notification_recipient', isset( $args[ 'is_admin' ] ) ? ( $args[ 'is_admin' ] ? 'customer' : 'admin' ) : 'both', $booking_group_id, $status, $args );
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to === 'customer' || $send_to === 'both' ) {
		bookacti_send_notification( 'customer_' . $status . '_booking', $booking_group_id, 'group' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to === 'admin' || $send_to === 'both' ) {
		bookacti_send_notification( 'admin_' . $status . '_booking', $booking_group_id, 'group' );
	}
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_send_notification_when_booking_group_state_changes', 10, 3 );


/**
 * Send a notification to admin and customer when a booking is rescheduled
 * 
 * @since 1.2.1 (was bookacti_send_email_when_booking_is_rescheduled in 1.2.0)
 * @param int $booking_id
 * @param object $old_booking
 * @param array $args
 */
function bookacti_send_notification_when_booking_is_rescheduled( $booking_id, $old_booking, $args ) {

	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to_both = false;
	if( ! isset( $args[ 'is_admin' ] ) ) { $send_to_both = true; }
	
	$notification_args = array(); $notification_args[ 'tags' ] = array();
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to_both || $args[ 'is_admin' ] ) {
		$notification_id	= 'customer_rescheduled_booking';
		$user_id			= bookacti_get_booking_owner( $booking_id );
		$locale				= bookacti_get_user_locale( $user_id );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to_both || ! $args[ 'is_admin' ] ) {
		$notification_id	= 'admin_rescheduled_booking';
		$locale				= bookacti_get_site_locale();
	}
	
	// Temporarilly switch locale user default's
	$locale = apply_filters( 'bookacti_notification_locale', $locale, $notification_id, $booking_id, 'single', $notification_args );
	bookacti_switch_locale( $locale );

	// Add reschedule specific tags
	$notification_args[ 'tags' ][ '{booking_old_start}' ]	= bookacti_format_datetime( $old_booking->event_start );
	$notification_args[ 'tags' ][ '{booking_old_end}' ]		= bookacti_format_datetime( $old_booking->event_end );

	// Switch locale back to normal
	bookacti_restore_locale();

	bookacti_send_notification( $notification_id, $booking_id, 'single', $notification_args );
}
add_action( 'bookacti_booking_rescheduled', 'bookacti_send_notification_when_booking_is_rescheduled', 10, 3 );
