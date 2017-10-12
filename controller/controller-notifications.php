<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Send an email to admin and customer when a new booking is made
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param array $booking_form_values
 * @param string $booking_type
 */
function bookacti_send_email_admin_new_booking( $booking_id, $booking_form_values, $booking_type ) {
	// Send a booking confirmation to the customer
	$status = $booking_type === 'group' ? bookacti_get_booking_group_state( $booking_id ) : bookacti_get_booking_state( $booking_id );
	bookacti_send_email( 'customer_' . $status . '_booking', $booking_id, $booking_type );

	// Alert administrators that a new booking has been made
	bookacti_send_email( 'admin_new_booking', $booking_id, $booking_type );
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_email_admin_new_booking', 10, 3 );



/**
 * Send an email to admin and customer when a single booking status changes
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param string $status
 * @param array $args
 */
function bookacti_send_email_when_booking_state_changes( $booking_id, $status, $args ) {
	
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If the booking is part of a group and the whole group is affected by this change, do not send email here
	if( $args[ 'booking_group_state_changed' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to_both = false;
	if( ! isset( $args[ 'is_admin' ] ) ) { $send_to_both = true; }
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to_both || $args[ 'is_admin' ] ) {
		
		bookacti_send_email( 'customer_' . $status . '_booking', $booking_id, 'single' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to_both || ! $args[ 'is_admin' ] ) {
		
		bookacti_send_email( 'admin_' . $status . '_booking', $booking_id, 'single' );
	}
}
add_action( 'bookacti_booking_state_changed', 'bookacti_send_email_when_booking_state_changes', 10, 3 );


/**
 * Send an email to admin and customer when a booking group status changes
 * 
 * @since 1.2.0
 * @param int $booking_group_id
 * @param string $status
 * @param array $args
 */
function bookacti_send_email_when_booking_group_state_changes( $booking_group_id, $status, $args ) {
	
	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to_both = false;
	if( ! isset( $args[ 'is_admin' ] ) ) { $send_to_both = true; }
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to_both || $args[ 'is_admin' ] ) {
		
		bookacti_send_email( 'customer_' . $status . '_booking', $booking_group_id, 'group' );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to_both || ! $args[ 'is_admin' ] ) {
		
		bookacti_send_email( 'admin_' . $status . '_booking', $booking_group_id, 'group' );
	}
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_send_email_when_booking_group_state_changes', 10, 3 );


/**
 * Send an email to admin and customer when a booking is rescheduled
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param object $old_booking
 * @param array $args
 */
function bookacti_send_email_when_booking_is_rescheduled( $booking_id, $old_booking, $args ) {

	// Do not send notification if explicitly said
	if( isset( $args[ 'send_notifications' ] ) && ! $args[ 'send_notifications' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to_both = false;
	if( ! isset( $args[ 'is_admin' ] ) ) { $send_to_both = true; }
	
	$email_args = array(); $email_args[ 'tags' ] = array();
	
	// If $args[ 'is_admin' ] is true, the customer need to be notified
	if( $send_to_both || $args[ 'is_admin' ] ) {
		
		// Temporarilly switch locale user default's
		$user_id	= bookacti_get_booking_owner( $booking_id );
		$locale		= apply_filters( 'bookacti_email_locale', bookacti_get_user_locale( $user_id ), 'customer_rescheduled_booking', $booking_id, 'single', $email_args );
		bookacti_switch_locale( $locale );
		
		// Add reschedule specific tags
		$email_args[ 'tags' ][ '{booking_old_start}' ]	= bookacti_format_datetime( $old_booking->event_start );
		$email_args[ 'tags' ][ '{booking_old_end}' ]	= bookacti_format_datetime( $old_booking->event_end );
		
		// Switch locale back to normal
		bookacti_restore_locale();
		
		bookacti_send_email( 'customer_rescheduled_booking', $booking_id, 'single', $email_args );
	}
	
	// If $args[ 'is_admin' ] is false, the administrator need to be notified
	if( $send_to_both || ! $args[ 'is_admin' ] ) {
		
		// Temporarilly switch locale to site default's
		$locale	= apply_filters( 'bookacti_email_locale', bookacti_get_site_locale(), 'admin_rescheduled_booking', $booking_id, 'single', $email_args );
		bookacti_switch_locale( $locale );
		
		// Add reschedule specific tags
		$email_args[ 'tags' ][ '{booking_old_start}' ]	= bookacti_format_datetime( $old_booking->event_start );
		$email_args[ 'tags' ][ '{booking_old_end}' ]	= bookacti_format_datetime( $old_booking->event_end );
		
		// Switch locale back to normal
		bookacti_restore_locale();
		
		bookacti_send_email( 'admin_rescheduled_booking', $booking_id, 'single', $email_args );
	}
}
add_action( 'bookacti_booking_rescheduled', 'bookacti_send_email_when_booking_is_rescheduled', 10, 3 );
