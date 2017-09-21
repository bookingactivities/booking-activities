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
	
	if( ! $email ) { return false; }
	
	// Temporarilly switch locale to site default's
	$origin_locale	= get_locale();
	$site_locale	= bookacti_get_site_locale();
	if( $origin_locale !== $site_locale ) { bookacti_switch_locale( $site_locale ); }
	
	$message	= $email[ 'message' ];
	
	// TO DO replace tags in message
	$tags = bookacti_get_notifications_tags( $booking_id, $booking_type );
	
	
	$subject	= $email[ 'subject' ];
	$to			= $email[ 'to' ];
	$from_name	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_name' );
	$from_email	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' );
	$headers	= apply_filters( 'bookacti_notifications_email_headers', array( 'Content-Type: text/html; charset=UTF-8;', 'From:' . $from_name . ' <' . $from_email . '>' ) );
	
	wp_mail( $to, $subject, $message, $headers );
	
	// Switch locale back to normal
	if( $origin_locale !== $site_locale ) { bookacti_switch_locale( $origin_locale ); }
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_email_admin_new_booking', 10, 3 );