<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Initialize Booking Activities shortcodes
 * @version 1.8.0
 */
add_shortcode( 'bookingactivities_form', 'bookacti_shortcode_booking_form' );
add_shortcode( 'bookingactivities_list', 'bookacti_shortcode_booking_list' );
add_shortcode( 'bookingactivities_login', 'bookacti_shortcode_login_form' );


/**
 * Display a booking form via shortcode
 * Eg: [bookingactivities_form form="Your form ID" id="Your form instance CSS ID"]
 * @version 1.8.0
 * @param array $raw_atts [form, id]
 * @param string $content
 * @param string $tag Should be "bookingactivities_form"
 * @return string The booking form corresponding to given parameters
 */
function bookacti_shortcode_booking_form( $raw_atts = array(), $content = null, $tag = '' ) {
	$default_atts = array(
		'form' => 0,
		'id' => ''
	);
	$atts = shortcode_atts( $default_atts, array_change_key_case( (array) $raw_atts, CASE_LOWER ), $tag );
	$output = '';

	// Retrieve the booking form
	if( ! empty( $atts[ 'form' ] ) ) {
		$form_id = intval( $atts[ 'form' ] );
		$instance_id = sanitize_title_with_dashes( $atts[ 'id' ] );
		$output = bookacti_display_form( $form_id, $instance_id, 'display', false );
	}

	return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $output, $raw_atts, $content );
}


/**
 * Display a login form via shortcode
 * Eg: [bookingactivities_login form="Your form ID" id="Your form instance CSS ID"]
 * @since 1.8.0
 * @param array $raw_atts [form, id]
 * @param string $content
 * @param string $tag Should be "bookingactivities_login"
 * @return string The login form corresponding to given parameters
 */
function bookacti_shortcode_login_form( $raw_atts = array(), $content = null, $tag = '' ) {
	$default_atts = array(
		'form' => 0,
		'id' => ''
	);
	$atts = shortcode_atts( $default_atts, array_change_key_case( (array) $raw_atts, CASE_LOWER ), $tag );
	$output = '';
	
	// Retrieve the booking form
	if( ! is_user_logged_in() && ! empty( $atts[ 'form' ] ) ) {
		$form_id = intval( $atts[ 'form' ] );
		$form_css_id = sanitize_title_with_dashes( $atts[ 'id' ] );
		$instance_id = $form_css_id ? esc_attr( $form_css_id ) : esc_attr( 'login-form-' . rand() );
		$output = bookacti_display_form( $form_id, $instance_id, 'login_form', false );
	}

	return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $output, $raw_atts, $content );
}


/**
 * Display a user related booking list via shortcode
 * @since 1.7.4 (was bookacti_shortcode_booking_list)
 * @version 1.8.0
 * @param array $atts [user_id, per_page, status, and any booking filter such as 'from', 'to', 'activities'...]
 * @param string $content
 * @param string $tag Should be "bookingactivities_list"
 * @return string The booking list corresponding to given parameters
 */
function bookacti_shortcode_booking_list( $atts = array(), $content = null, $tag = '' ) {
	// Normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
	
	// If the user is not logged in, and if a login form is defined, show it instead of the booking list
	if( ! is_user_logged_in() && ! empty( $atts[ 'login_form' ] ) ) {
		$atts[ 'form' ] = $atts[ 'login_form' ];
		return bookacti_shortcode_login_form( $atts, $content, $tag );
	}
	
	// Format 'user_id' attribute
	if( isset( $atts[ 'user_id' ] ) ) {
		$atts[ 'user_id' ] = esc_attr( $atts[ 'user_id' ] );
		
	// Backward Compatibility for "user" attribute (instead of "user_id")
	} else if( isset( $atts[ 'user' ] ) ) {
		$atts[ 'user_id' ] = esc_attr( $atts[ 'user' ] );
		unset( $atts[ 'user' ] );
	}
	
	// Set default values
	$default_atts = array_merge( bookacti_get_default_booking_filters(), array(
		'user_id'	=> get_current_user_id(),
		'per_page'	=> 10,
		'status'	=> apply_filters( 'bookacti_booking_list_displayed_status', array( 'delivered', 'booked', 'pending', 'cancelled', 'refunded', 'refund_requested' ) ),
		'group_by'	=> 'booking_group',
		'columns'	=> bookacti_get_user_booking_list_default_columns()
	) );
    $atts = shortcode_atts( $default_atts, $atts, $tag );
	
	// Format values
	$atts = bookacti_format_string_booking_filters( $atts );
	if( empty( $atts[ 'user_id' ] ) || $atts[ 'user_id' ] === 'current' ) { $atts[ 'user_id' ] = get_current_user_id(); }
	
	// If the user ID is not specified
	if( ! is_user_logged_in() && empty( $atts[ 'user_id' ] ) && empty( $atts[ 'in__user_id' ] ) && empty( $atts[ 'not_in__user_id' ] ) ) {
		return apply_filters( 'bookacti_shortcode_' . $tag . '_output', '', $atts, $content );
	}
	
	if( $atts[ 'user_id' ] === 'all'
	|| ! empty( $atts[ 'in__user_id' ] )
	|| ! empty( $atts[ 'not_in__user_id' ] ) ) { $atts[ 'user_id' ] = ''; }
	
	if( empty( $atts[ 'columns' ] ) ) { $atts[ 'columns' ] = $default_atts[ 'columns' ]; }
	
	$templates = array();
	if( isset( $atts[ 'templates' ] ) ) { 
		$templates = $atts[ 'templates' ];
		unset( $atts[ 'templates' ] );
	}
	
	// Format booking filters
	$filters = bookacti_format_booking_filters( $atts );
	
	// Allow to filter by any template
	if( ! empty( $templates ) && is_array( $templates ) ) { $filters[ 'templates' ] = $templates; }
	
	// Let third party change the filters
	$filters = apply_filters( 'bookacti_user_booking_list_booking_filters', $filters, $atts, $content );
	
	$booking_list = bookacti_get_user_booking_list( $filters, $atts[ 'columns' ], $atts[ 'per_page' ] );
	
	return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $booking_list, $atts, $content );
}