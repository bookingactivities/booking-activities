<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * AJAX Controller - Fetch events in order to display them
 * @version	1.7.4
 */
function bookacti_controller_fetch_events() {
	// Check nonce
	$is_admin		= intval( $_POST[ 'is_admin' ] );
	$raw_attributes	= json_decode( stripslashes( $_POST[ 'attributes' ] ), true );
	$attributes		= bookacti_format_booking_system_attributes( $raw_attributes );
	
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
	
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'fetch_events' ); }

	$events_interval	= bookacti_sanitize_events_interval( $_POST[ 'interval' ] );
	$events				= array( 'events' => array(), 'data' => array() );

	if( $attributes[ 'groups_only' ] ) {
		$groups_data	= isset( $raw_attributes[ 'groups_data' ] ) ? (array) $raw_attributes[ 'groups_data' ] : array();
		$groups_ids		= $groups_data ? array_keys( $groups_data ) : array();
		if( $groups_ids ) {
			$events	= bookacti_fetch_grouped_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $groups_ids, $attributes[ 'group_categories' ], $attributes[ 'past_events' ], $events_interval );
		}
	} else if( $attributes[ 'bookings_only' ] ) {
		$events = bookacti_fetch_booked_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'status' ], $attributes[ 'user_id' ], $attributes[ 'past_events' ], $events_interval );
	} else {
		$events	= bookacti_fetch_events( $attributes[ 'calendars' ], $attributes[ 'activities' ], $attributes[ 'past_events' ], $events_interval );	
	}
	
	$events = apply_filters( 'bookacti_events_data_from_interval', $events, $events_interval, $attributes );
	
	bookacti_send_json( array( 
		'status'		=> 'success', 
		'events'		=> $events[ 'events' ] ? $events[ 'events' ] : array(), 
		'events_data'	=> $events[ 'data' ] ? $events[ 'data' ] : array()
	), 'fetch_events' );
}
add_action( 'wp_ajax_bookactiFetchEvents', 'bookacti_controller_fetch_events' );
add_action( 'wp_ajax_nopriv_bookactiFetchEvents', 'bookacti_controller_fetch_events' );


/**
 * Reload booking system with new attributes via AJAX
 * @since 1.1.0
 * @version 1.7.4
 */
function bookacti_controller_reload_booking_system() {
	$is_admin = intval( $_POST[ 'is_admin' ] );
	$atts = bookacti_format_booking_system_attributes( json_decode( stripslashes( $_POST[ 'attributes' ] ), true ) );
	
	// On admin side only, check capabilities
	$is_allowed = true;
	if( $is_admin && ! is_super_admin() ) {		
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $bypass_template_managers_check ){
			// Remove templates current user is not allowed to manage
			foreach( $atts[ 'calendars' ] as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ) {
					unset( $atts[ 'calendars' ][ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $atts[ 'calendars' ] ) ) { $is_allowed = false; }
		}
	}
	
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'reload_booking_system' ); }
	
	$atts[ 'auto_load' ] = 1;
	$booking_system_data = bookacti_get_booking_system_data( $atts );
	
	// Get HTML elements used by the booking method
	$html_elements = bookacti_get_booking_method_html( $booking_system_data[ 'method' ], $booking_system_data );
	
	bookacti_send_json( array( 
		'status'				=> 'success', 
		'html_elements'			=> $html_elements, 
		'booking_system_data'	=> $booking_system_data
	), 'reload_booking_system' );
}
add_action( 'wp_ajax_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );
add_action( 'wp_ajax_nopriv_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );


/**
 * AJAX Controller - Get booking numbers for a given template and / or event
 * 
 * @version 1.5.0
 */
function bookacti_controller_get_booking_numbers() {

	$template_ids	= isset( $_POST['template_ids'] ) ? intval( $_POST['template_ids'] ) : array();
	$event_ids		= isset( $_POST['event_ids'] ) ? intval( $_POST['event_ids'] ) : array();

	$booking_numbers = bookacti_get_number_of_bookings_by_events( $template_ids, $event_ids );

	if( count( $booking_numbers ) > 0 ) {
		wp_send_json( array( 'status' => 'success', 'bookings' => $booking_numbers ) );
	} else if( count( $booking_numbers ) === 0 ) {
		wp_send_json( array( 'status' => 'no_bookings' ) );
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
	}
}
add_action( 'wp_ajax_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );