<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * AJAX Controller - Fetch events in order to display them
 * @version	1.8.0
 */
function bookacti_controller_fetch_events() {
	// Check nonce
	$is_admin	= intval( $_POST[ 'is_admin' ] );
	$raw_atts	= json_decode( stripslashes( $_POST[ 'attributes' ] ), true );
	$atts		= bookacti_format_booking_system_attributes( $raw_atts );
	
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
	
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'fetch_events' ); }

	$events_interval= bookacti_sanitize_events_interval( $_POST[ 'interval' ] );
	$events = array( 'events' => array(), 'data' => array() );

	if( $atts[ 'groups_only' ] ) {
		$groups_data	= isset( $raw_atts[ 'groups_data' ] ) ? (array) $raw_atts[ 'groups_data' ] : array();
		$groups_ids		= $groups_data ? array_keys( $groups_data ) : array();
		if( $groups_ids && ! in_array( 'none', $atts[ 'group_categories' ], true ) ) {
			$events	= bookacti_fetch_grouped_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'groups' => $groups_ids, 'group_categories' => $atts[ 'group_categories' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
		}
	} else if( $atts[ 'bookings_only' ] ) {
		$events = bookacti_fetch_booked_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'status' => $atts[ 'status' ], 'users' => $atts[ 'user_id' ] ? array( $atts[ 'user_id' ] ) : array(), 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
	} else {
		$events	= bookacti_fetch_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );	
	}
	
	$events = apply_filters( 'bookacti_events_data_from_interval', $events, $events_interval, $atts );
	
	// Get the booking list for each events
	$booking_lists = array();
	if( $atts[ 'tooltip_booking_list' ] && $events[ 'events' ] && $events[ 'data' ] ) {
		$booking_filters = array(
			'from'			=> $events_interval[ 'start' ],
			'to'			=> $events_interval[ 'end' ],
			'in__event_id'	=> array_keys( $events[ 'data' ] ),
		);
		$booking_lists = bookacti_get_events_booking_lists( $booking_filters, $atts[ 'tooltip_booking_list_columns' ], $atts );
	}
	
	bookacti_send_json( array( 
		'status'		=> 'success', 
		'events'		=> $events[ 'events' ] ? $events[ 'events' ] : array(), 
		'events_data'	=> $events[ 'data' ] ? $events[ 'data' ] : array(),
		'booking_lists'	=> $booking_lists
	), 'fetch_events' );
}
add_action( 'wp_ajax_bookactiFetchEvents', 'bookacti_controller_fetch_events' );
add_action( 'wp_ajax_nopriv_bookactiFetchEvents', 'bookacti_controller_fetch_events' );


/**
 * Reload booking system with new attributes via AJAX
 * @since 1.1.0
 * @version 1.7.15
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
	
	// Encrypt user id
	$public_user_id = ! empty( $atts[ 'user_id' ] ) ? $atts[ 'user_id' ] : 0;
	if( $public_user_id && ( ( is_numeric( $public_user_id ) && strlen( (string) $public_user_id ) < 16 ) || is_email( $public_user_id ) ) ) { $public_user_id = bookacti_encrypt( $public_user_id ); }
	
	// Let plugins define what data should be passed to JS
	$public_booking_system_data = apply_filters( 'bookacti_public_booking_system_data', array_merge( $booking_system_data, array( 'user_id' => $public_user_id ) ), $atts );
	
	bookacti_send_json( array( 
		'status'				=> 'success', 
		'html_elements'			=> $html_elements, 
		'booking_system_data'	=> $public_booking_system_data
	), 'reload_booking_system' );
}
add_action( 'wp_ajax_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );
add_action( 'wp_ajax_nopriv_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );


/**
 * AJAX Controller - Get booking numbers for a given template and / or event
 * @version 1.8.0
 */
function bookacti_controller_get_booking_numbers() {
	$template_ids	= isset( $_POST[ 'template_ids' ] ) ? intval( $_POST[ 'template_ids' ] ) : array();
	$event_ids		= isset( $_POST[ 'event_ids' ] ) ? intval( $_POST[ 'event_ids' ] ) : array();

	$booking_numbers = bookacti_get_number_of_bookings_by_events( $template_ids, $event_ids );
	if( ! $booking_numbers ) { bookacti_send_json( array( 'status' => 'no_bookings' ), 'get_booking_numbers' ); }
	
	bookacti_send_json( array( 'status' => 'success', 'bookings' => $booking_numbers ), 'get_booking_numbers' );
}
add_action( 'wp_ajax_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );