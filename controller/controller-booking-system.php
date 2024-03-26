<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * AJAX Controller - Get booking system data by interval (events, groups, and bookings) 
 * @since 1.12.0 (was bookacti_controller_fetch_events)
 * @version 1.16.1
 */
function bookacti_controller_get_booking_system_data_by_interval() {
	$atts     = isset( $_POST[ 'attributes' ] ) ? ( is_array( $_POST[ 'attributes' ] ) ? $_POST[ 'attributes' ] : ( is_string( $_POST[ 'attributes' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'attributes' ] ), true ) : array() ) ) : array();
	$atts     = bookacti_format_booking_system_attributes( $atts );
	$interval = isset( $_POST[ 'interval' ] ) ? ( is_array( $_POST[ 'interval' ] ) ? $_POST[ 'interval' ] : ( is_string( $_POST[ 'interval' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'interval' ] ), true ) : array() ) ) : array();
	$interval = $interval ? bookacti_sanitize_events_interval( $interval ) : array();
	
	$start_dt = $atts[ 'start' ] ? new DateTime( $atts[ 'start' ] ) : '';
	$end_dt   = $atts[ 'end' ] ? new DateTime( $atts[ 'end' ] ) : '';
	
	$atts[ 'start' ]               = ! empty( $interval[ 'start' ] ) ? $interval[ 'start' ] : '';
	$atts[ 'end' ]                 = ! empty( $interval[ 'end' ] ) ? $interval[ 'end' ] : '';
	$atts[ 'events_min_interval' ] = $atts[ 'start' ] || $atts[ 'end' ] ? array( 'start' => $atts[ 'start' ], 'end' => $atts[ 'end' ] ) : array();
	$atts[ 'auto_load' ]           = 1;
	
	$booking_system_data = bookacti_get_booking_system_data( $atts );
	
	// Trim the availability period
	$trimmed_period = array();
	if( $booking_system_data[ 'trim' ] ) {
		$interval_start_dt    = new DateTime( $interval[ 'start' ] );
		$interval_end_dt      = new DateTime( $interval[ 'end' ] );
		$is_first_interval    = $start_dt && $interval_start_dt <= $start_dt;
		$is_last_interval     = $end_dt && $interval_end_dt >= $end_dt;
		
		$events_by_start_date = bookacti_sort_events_array_by_dates( $booking_system_data[ 'events' ] );
		$events_by_end_date   = bookacti_sort_events_array_by_dates( $booking_system_data[ 'events' ], true, true );
		$first_event_dt       = ! empty( $events_by_start_date[ 0 ][ 'start' ] ) ? new DateTime( $events_by_start_date[ 0 ][ 'start' ] ) : null;
		$last_event_dt        = ! empty( $events_by_end_date[ 0 ][ 'end' ] ) ? new DateTime( $events_by_end_date[ 0 ][ 'end' ] ) : null;
		
		if( $is_first_interval && $first_event_dt && $first_event_dt > $start_dt ) {
			$trimmed_period[ 'start' ] = $first_event_dt->format( 'Y-m-d H:i:s' );
		}
		
		if( $is_last_interval && $last_event_dt && $last_event_dt < $end_dt ) {
			$trimmed_period[ 'end' ] = $last_event_dt->format( 'Y-m-d H:i:s' );
		}
	}
	
	// Encrypt user_id
	$public_user_ids = array();
	if( ! empty( $atts[ 'user_id' ] ) ) {
		foreach( $atts[ 'user_id' ] as $user_id ) {
			if( $user_id && ( ( is_numeric( $user_id ) && strlen( (string) $user_id ) < 16 ) || is_email( $user_id ) ) ) { 
				$public_user_ids[] = bookacti_encrypt( $user_id );
			}
		}
	}
	
	// Let plugins define what data should be passed to JS
	$public_booking_system_data = apply_filters( 'bookacti_public_booking_system_data', array_merge( $booking_system_data, array( 'user_id' => $public_user_ids ) ), $atts );
	
	bookacti_send_json( array( 
		'status'              => 'success', 
		'booking_system_data' => $public_booking_system_data,
		'trimmed_period'      => $trimmed_period
	), 'get_booking_system_data_by_interval' );
}
add_action( 'wp_ajax_bookactiGetBookingSystemDataByInterval', 'bookacti_controller_get_booking_system_data_by_interval' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingSystemDataByInterval', 'bookacti_controller_get_booking_system_data_by_interval' );


/**
 * Reload booking system with new attributes via AJAX
 * @since 1.1.0
 * @version 1.16.0
 */
function bookacti_controller_reload_booking_system() {
	$atts = isset( $_POST[ 'attributes' ] ) ? ( is_array( $_POST[ 'attributes' ] ) ? $_POST[ 'attributes' ] : ( is_string( $_POST[ 'attributes' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'attributes' ] ), true ) : array() ) ) : array();
	$atts = bookacti_format_booking_system_attributes( $atts );
	
	$atts[ 'auto_load' ] = 1;
	$booking_system_data = bookacti_get_booking_system_data( $atts );
	
	// Get HTML elements used by the booking method
	$html_elements = bookacti_get_booking_method_html( $booking_system_data[ 'method' ], $booking_system_data );
	
	// Encrypt user_id
	$public_user_ids = array();
	if( ! empty( $atts[ 'user_id' ] ) ) {
		foreach( $atts[ 'user_id' ] as $user_id ) {
			if( $user_id && ( ( is_numeric( $user_id ) && strlen( (string) $user_id ) < 16 ) || is_email( $user_id ) ) ) { 
				$public_user_ids[] = bookacti_encrypt( $user_id );
			}
		}
	}
	
	// Let plugins define what data should be passed to JS
	$public_booking_system_data = apply_filters( 'bookacti_public_booking_system_data', array_merge( $booking_system_data, array( 'user_id' => $public_user_ids ) ), $atts );
	
	bookacti_send_json( array( 
		'status'              => 'success', 
		'html_elements'       => $html_elements, 
		'booking_system_data' => $public_booking_system_data
	), 'reload_booking_system' );
}
add_action( 'wp_ajax_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );
add_action( 'wp_ajax_nopriv_bookactiReloadBookingSystem', 'bookacti_controller_reload_booking_system' );


/**
 * AJAX Controller - Get booking numbers for a given template and / or event
 * @version 1.12.0
 */
function bookacti_controller_get_booking_numbers() {
	$template_ids  = isset( $_POST[ 'template_ids' ] ) ? bookacti_ids_to_array( $_POST[ 'template_ids' ] ) : array();
	$groups_data   = isset( $_POST[ 'groups_data' ] ) && is_array( $_POST[ 'groups_data' ] ) ? $_POST[ 'groups_data' ] : array();
	$groups_events = isset( $_POST[ 'groups_events' ] ) && is_array( $_POST[ 'groups_events' ] ) ? $_POST[ 'groups_events' ] : array();
	$groups        = array( 'data' => $groups_data, 'groups' => $groups_events );
	
	$bookings_nb_per_event = bookacti_get_number_of_bookings_per_event( array( 'templates' => $template_ids ) );
	if( ! $bookings_nb_per_event ) { bookacti_send_json( array( 'status' => 'no_bookings' ), 'get_booking_numbers' ); }
	
	$bookings_nb_per_group = bookacti_get_number_of_bookings_per_group_of_events( $groups );
	
	bookacti_send_json( array( 'status' => 'success', 'bookings' => $bookings_nb_per_event, 'groups_bookings' => $bookings_nb_per_group ), 'get_booking_numbers' );
}
add_action( 'wp_ajax_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingNumbers', 'bookacti_controller_get_booking_numbers' );