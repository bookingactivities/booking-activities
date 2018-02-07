<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * AJAX Controller - Fetch events in order to display them
 * 
 * @version	1.3.0
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
		
		$events_interval = bookacti_sanitize_events_interval( $_POST[ 'interval' ] );
		
		if( $attributes[ 'groups_only' ] ) {
			$events	= bookacti_fetch_grouped_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], array(), $attributes[ 'group_categories' ], $attributes[ 'past_events' ], $events_interval );
		} else if( $attributes[ 'bookings_only' ] ) {
			$events = bookacti_fetch_booked_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'status' ], $attributes[ 'user_id' ], $attributes[ 'past_events' ], $events_interval );
		} else {
			$events	= bookacti_fetch_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'past_events' ], $events_interval );	
		}
		
		wp_send_json( array( 
			'status'		=> 'success', 
			'events'		=> $events[ 'events' ] ? $events[ 'events' ] : array(), 
			'events_data'	=> $events[ 'data' ] ? $events[ 'data' ] : array()
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
 * @version 1.3.2
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
		
		$template_data		= bookacti_get_mixed_template_data( $attributes[ 'calendars' ], $attributes[ 'past_events' ] );
		$events_interval	= bookacti_get_new_interval_of_events( $template_data, array(), false, $attributes[ 'past_events' ] );
		
		// Get HTML elements used by the booking method
		$html_elements = bookacti_get_booking_method_html( $attributes[ 'method' ], $attributes );
		
		// Gets calendar content: events, activities and groups
		$user_ids = array();
		if( $attributes[ 'groups_only' ] ) {
			$events		= bookacti_fetch_grouped_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], array(), $attributes[ 'group_categories' ], $attributes[ 'past_events' ], $events_interval );
		} else if( $attributes[ 'bookings_only' ] ) {
			$events		= bookacti_fetch_booked_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'status' ], $attributes[ 'user_id' ], $attributes[ 'past_events' ], $events_interval );
			$user_ids	= $attributes[ 'user_id' ];
		} else {
			$events		= bookacti_fetch_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'past_events' ], $events_interval );	
		}
		
		$groups_events	= array();
		if( $attributes[ 'group_categories' ] !== false ) { 
			$groups_events		= bookacti_get_groups_events( $attributes[ 'calendars' ], $attributes[ 'group_categories' ] );
		}
		
		if( empty( $attributes[ 'group_categories' ] ) ) {
			$groups_data		= bookacti_get_groups_of_events_by_template( $attributes[ 'calendars' ] );
			$categories_data	= bookacti_get_group_categories_by_template( $attributes[ 'calendars' ] );
		} else {
			$groups_data		= bookacti_get_groups_of_events_by_category( $attributes[ 'group_categories' ] );
			$categories_data	= bookacti_get_group_categories( $attributes[ 'group_categories' ] );
		}
		
		$activities_data	= bookacti_get_activities_by_template( $attributes[ 'calendars' ], true );
		$exceptions			= bookacti_get_exceptions( $attributes[ 'calendars' ] );
		$bookings			= bookacti_get_number_of_bookings_by_events( $attributes[ 'calendars' ], array(), $user_ids );
		
		wp_send_json( array( 
			'status'				=> 'success', 
			'html_elements'			=> $html_elements, 
			'events'				=> $events[ 'events' ] ? $events[ 'events' ] : array(), 
			'events_data'			=> $events[ 'data' ] ? $events[ 'data' ] : array(), 
			'events_interval'		=> $events_interval, 
			'exceptions'			=> $exceptions, 
			'bookings'				=> $bookings, 
			'activities_data'		=> $activities_data, 
			'groups_events'			=> $groups_events,
			'groups_data'			=> $groups_data,
			'group_categories_data'	=> $categories_data,
			'template_data'			=> $template_data
		) );
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );
add_action( 'wp_ajax_nopriv_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );


/**
 * AJAX Controller - Get booking numbers for a given template and / or event
 * 
 * @version 1.3.2
 */
function bookacti_controller_get_booking_numbers() {

	$template_ids	= isset( $_POST['template_ids'] ) ? intval( $_POST['template_ids'] ) : array();
	$event_ids		= isset( $_POST['event_ids'] ) ? intval( $_POST['event_ids'] ) : array();

	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_get_booking_numbers', 'nonce', false );

	if( $is_nonce_valid ) {

		$booking_numbers = bookacti_get_number_of_bookings_by_events( $template_ids, $event_ids );

		if( count( $booking_numbers ) > 0 ) {
			wp_send_json( array( 'status' => 'success', 'bookings' => $booking_numbers ) );
		} else if( count( $booking_numbers ) === 0 ) {
			wp_send_json( array( 'status' => 'no_bookings' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
		}

	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );