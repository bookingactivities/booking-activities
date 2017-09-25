<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Send an email to admin when a new booking is made
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param array $booking_form_values
 * @param string $booking_type
 */
function bookacti_send_email_admin_new_booking( $booking_id, $booking_form_values, $booking_type ) {
	
	$email = bookacti_get_email_settings( 'admin_new_booking', true );
	
	if( ! $email || ! $email[ 'active' ] ) { return false; }
	
	// Temporarilly switch locale to site default's
	bookacti_switch_to_site_locale();
	
	$tags = bookacti_get_notifications_tags_values( $booking_id, $booking_type, 'admin_new_booking' );
	
	// Replace tags in message and replace linebreaks with html tags
	$message	= wpautop( str_replace( array_keys( $tags ), array_values( $tags ), $email[ 'message' ] ) );
	
	$subject	= $email[ 'subject' ];
	$to			= $email[ 'to' ];
	$from_name	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_name' );
	$from_email	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' );
	$headers	= apply_filters( 'bookacti_notifications_email_headers', array( 'Content-Type: text/html; charset=UTF-8;', 'From:' . $from_name . ' <' . $from_email . '>' ) );
	
	wp_mail( $to, $subject, $message, $headers );
	
	// Switch locale back to normal
	bookacti_restore_locale();
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_email_admin_new_booking', 10, 3 );


/**
 * Send an email to admin and customer when a booking status changes
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param string $status
 * @param array $args
 */
function bookacti_send_email_when_booking_state_changes( $booking_id, $status, $args ) {
	
	// If the booking is part of a group and the whole group is affected by this change, do not send email here
	if( $args[ 'booking_group_state_changed' ] ) { return; }
	
	// If we cannot know if the action was made by customer or admin, send to both
	$send_to_both = false;
	if( ! isset( $args[ 'is_admin' ] ) ) { $send_to_both = true; }
	
	
	
}
add_action( 'bookacti_booking_state_changed', 'bookacti_send_email_when_booking_state_changes', 10, 3 );