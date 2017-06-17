<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * AJAX Controller - Fetch events in order to display them
 *
 * @since	1.0.0
 * @version	1.1.0
 */
function bookacti_controller_fetch_events() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'bookacti_fetch_events', 'nonce', false );
	$is_admin		= intval( $_POST[ 'is_admin' ] );
	
	$attributes		= bookacti_format_booking_system_attributes( json_decode( stripslashes( $_POST[ 'attributes' ] ), true ) );
	
	// On admin side only, check capabilities
	$is_allowed = true;
	if( $is_admin && ! is_super_admin() ) {		
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $bypass_template_managers_check ){
			// Remove templates current user is not allowed to manage
			foreach( $attributes[ 'calendars' ] as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ) {
					unset( $attributes[ 'calendars' ][ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $attributes[ 'calendars' ] ) ) { $is_allowed = false; }
		}
	}
	
	if( $is_nonce_valid && $is_allowed ) {
		
			$events		= bookacti_fetch_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'groups' ], $attributes[ 'past_events' ], $attributes[ 'context' ] );
			$activities	= bookacti_get_activities_by_template_ids( $attributes[ 'calendars' ] );
			$groups		= bookacti_get_groups_events( $attributes[ 'calendars' ] );

			wp_send_json( array( 
				'status'		=> 'success', 
				'events'		=> $events, 
				'activities'	=> $activities, 
				'groups'		=> $groups
			) );
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiFetchEvents', 'bookacti_controller_fetch_events' );
add_action( 'wp_ajax_nopriv_bookactiFetchEvents', 'bookacti_controller_fetch_events' );


/**
 * Reload booking system with new attributes via AJAX
 * 
 * @since 1.1.0
 */
function bookacti_controller_reload_booking_system() {
	
	// Check nonce and if the booking system has been initialized correctly
	$is_nonce_valid	= check_ajax_referer( 'bookacti_reload_booking_system', 'nonce', false );
	$is_admin		= intval( $_POST[ 'is_admin' ] );
	
	$attributes		= bookacti_format_booking_system_attributes( json_decode( stripslashes( $_POST[ 'attributes' ] ), true ) );
	
	// On admin side only, check capabilities
	$is_allowed = true;
	if( $is_admin && ! is_super_admin() ) {		
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $bypass_template_managers_check ){
			// Remove templates current user is not allowed to manage
			foreach( $attributes[ 'calendars' ] as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ) {
					unset( $attributes[ 'calendars' ][ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $attributes[ 'calendars' ] ) ) { $is_allowed = false; }
		}
	}
	
	if( $is_nonce_valid && $is_allowed ) {
		
		// Get HTML elements used by the booking method
		$html_elements = bookacti_get_booking_method_html( $attributes[ 'method' ], $attributes );
		
		// Get calendar settings
		$settings	= bookacti_get_mixed_template_settings( $attributes[ 'calendars' ] );
		
		// Gets calendar content: events, activities and groups
		$events		= bookacti_fetch_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'groups' ], $attributes[ 'past_events' ], $attributes[ 'context' ] );
		$activities	= bookacti_get_activities_by_template_ids( $attributes[ 'calendars' ] );
		$groups		= bookacti_get_groups_events( $attributes[ 'calendars' ] );

		wp_send_json( array( 
			'status'		=> 'success', 
			'html_elements'	=> $html_elements, 
			'events'		=> $events, 
			'activities'	=> $activities, 
			'groups'		=> $groups,
			'settings'		=> $settings
		) );
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );
add_action( 'wp_ajax_nopriv_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );


/**
 * Retrieve HTML elements of the desired booking method
 * 
 * @since 1.1.0
 */
function bookacti_controller_switch_booking_method() {
	
	// Check nonce and if the booking system has been initialized correctly
	$is_nonce_valid	= check_ajax_referer( 'bookacti_switch_booking_method', 'nonce', false );
	
	if( $is_nonce_valid ) {
		
		$method		= sanitize_title_with_dashes( $_POST[ 'method' ] );
		$attributes	= bookacti_format_booking_system_attributes( json_decode( stripslashes( $_POST[ 'attributes' ] ), true ) );
		
		// Get HTML elements used by the booking method
		$available_booking_methods = bookacti_get_available_booking_methods();
		if( $method === 'calendar' || ! in_array( $method, array_keys( $available_booking_methods ) ) ) {
			$html_elements = bookacti_retrieve_calendar_elements( $attributes );
		} else {
			$html_elements = apply_filters( 'bookacti_get_booking_method_html_elements', '', $method, $attributes );
		}
		
		wp_send_json( array( 
			'status'		=> 'success', 
			'html_elements'	=> $html_elements
		) );
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiSwitchBookingMethod', 'bookacti_controller_switch_booking_method' );
add_action( 'wp_ajax_nopriv_bookactiSwitchBookingMethod', 'bookacti_controller_switch_booking_method' );


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
		if( count( $template_ids ) > 0 ) {
			$settings = bookacti_get_mixed_template_settings( $template_ids );
		}
		
		if( ! empty( $settings ) ){
			wp_send_json( array( 'status' => 'success', 'settings' => $settings ) );
		} else {
			wp_send_json( array( 'status' => 'failed' ) );
		}
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}