<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

//Fetch events in order to display them
add_action( 'wp_ajax_bookactiFetchEvents', 'bookacti_controller_fetch_events' );
add_action( 'wp_ajax_nopriv_bookactiFetchEvents', 'bookacti_controller_fetch_events' );
function bookacti_controller_fetch_events() {
	
	$is_admin	= intval( $_POST[ 'is_admin' ] );
	$templates	= bookacti_ids_to_array( $_POST[ 'templates' ] );

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_fetch_events', 'nonce', false );
	
	// On admin side only, check capabilities
	$is_allowed = true;
	if( $is_nonce_valid && $is_admin && ! is_super_admin() ) {		
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $bypass_template_managers_check ){
			// Remove templates current user is not allowed to manage
			foreach( $templates as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ) {
					unset( $templates[ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $templates ) ) { $is_allowed = false; }
		}
	}
	
	if( $is_nonce_valid && $is_allowed ) {
		$activities			= bookacti_ids_to_array( $_POST[ 'activities' ] );
		$user_datetime		= bookacti_sanitize_datetime( $_POST[ 'user_datetime' ] );
		$fetch_past_events	= intval( $_POST[ 'fetch_past_events' ] );
		
		$user_datetime_object = DateTime::createFromFormat( 'Y-m-d H:i:s', $user_datetime );
		$user_datetime_object->setTimezone( new DateTimeZone( 'UTC' ) );
		
		$events = bookacti_fetch_calendar_events( $activities, $templates, $user_datetime_object, boolval( $fetch_past_events ) );
		$activities_array = bookacti_get_activities_by_template_ids( $templates, false );

		wp_send_json( array( 'status' => 'success', 'events' => $events, 'activities' => $activities_array ) );
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
	

// Retrieve HTML elements of the calendar booking system
add_action( 'wp_ajax_bookactiRetrieveCalendarElements', 'bookacti_controller_retrieve_calendar_elements' );
add_action( 'wp_ajax_nopriv_bookactiRetrieveCalendarElements', 'bookacti_controller_retrieve_calendar_elements' );
function bookacti_controller_retrieve_calendar_elements() {
	
	// Check nonce
	// No need to check capabilities since everyone can see calendars
	$is_nonce_valid = check_ajax_referer( 'bookacti_retrieve_calendar_elements', 'nonce', false );
	
	if( $is_nonce_valid ) {
		
		$calendar_id		= sanitize_title_with_dashes( $_POST[ 'calendar_id' ] );
		$calendar_elements	= bookacti_retrieve_calendar_elements( $calendar_id );
		wp_send_json( array( 'status' => 'success', 'calendar_elements' => $calendar_elements ) );
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}


// Get booking system data
add_action( 'wp_ajax_bookactiGetBookingSystemData', 'bookacti_controller_get_booking_system_data' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingSystemData', 'bookacti_controller_get_booking_system_data' );
function bookacti_controller_get_booking_system_data() {

	$is_admin		= intval( $_POST[ 'is_admin' ] );
	$template_ids	= bookacti_ids_to_array( $_POST[ 'template_ids' ] );
	
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_system_data', 'nonce', false );
	
	// On admin side, check capabilities
	$is_allowed = true;
	if( $is_nonce_valid && $is_admin && ! is_super_admin() ) {		
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $bypass_template_managers_check ){
			// Remove templates current user is not allowed to manage
			foreach( $template_ids as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ) {
					unset( $template_ids[ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $template_ids ) ) { $is_allowed = false; }
		}
	}
	
	if( $is_nonce_valid && $is_allowed ) {
		$settings = array();
		if( count( $template_ids ) !== 1 ) {
			$settings = bookacti_get_mixed_template_settings( $template_ids );
		} else {
			$settings = bookacti_get_templates_settings( $template_ids );
		}

		if( is_array( $settings ) ){
			wp_send_json( array( 'status' => 'success', 'settings' => $settings ) );
		} else {
			wp_send_json( array( 'status' => 'failed' ) );
		}
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}