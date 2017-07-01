<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', 'bookacti_shortcodes_init');
function bookacti_shortcodes_init() {
	add_shortcode( 'bookingactivities_calendar', 'bookacti_shortcode_calendar' );
	add_shortcode( 'bookingactivities_form', 'bookacti_shortcode_booking_form' );
	add_shortcode( 'bookingactivities_list', 'bookacti_shortcode_bookings_list' );
}

/**
 * Display a calendar of activities / templates via shortcode
 * Eg: [bookingactivities_calendar	id='my-cal'					// Any id you want
 *									classes='full-width'			// Any class you want
 *									calendars='2'				// Comma separated calendar ids
 *									activities='1,2,10'			// Comma separated activity ids
 *									groups='5,10'				// Comma separated group category ids
 *									groups_only='1'				// Only display groups
 *									groups_single_events='0'		// Allow to book grouped events as single events
 *									method='calendar' ]			// Display method
 * 
 * @since 1.0.0
 * @version 1.1.0
 * 
 * @param array $atts [id, classes, calendars, activities, groups, method]
 * @param string $content
 * @param string $tag Should be "bookingactivities_calendar"
 * @return string The calendar corresponding to given parameters
 */
function bookacti_shortcode_calendar( $atts = [], $content = null, $tag = '' ) {
	
	// normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
	
	$output = '<div class="bookacti-booking-system-calendar-only" >'
			.		bookacti_get_booking_system( $atts )
			. '</div>';
	
    return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $output, $atts, $content );
}


/**
 * Display a booking form via shortcode
 * Eg: [bookingactivities_calendar	id='my-cal'				// Any id you want
 *									classes='full-width'		// Any class you want
 *									calendars='2'			// Comma separated calendar ids
 *									activities='1,2,10'		// Comma separated activity ids
 *									groups='5,10'			// Comma separated group category ids
 *									groups_only				// Only display groups
 *									groups_single_events	// Allow to book single events within groups
 *									method='waterfall' 
 *									url='http://...']		// URL to be redirected after submission
 *									button='Book']			// Name of the booking form submit button
 * 
 * @since 1.0.0
 * @version 1.1.0
 * 
 * @param array $atts [id, classes, calendars, activities, groups, method, url, button]
 * @param string $content
 * @param string $tag Should be "bookingactivities_form"
 * @return string The booking form corresponding to given parameters
 */
function bookacti_shortcode_booking_form( $atts = [], $content = null, $tag = '' ) {
	
	// Format attributes
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
	$atts = bookacti_format_booking_system_attributes( $atts );
	
	$output = "<form action='" . $atts[ 'url' ] . "' 
					class='bookacti-booking-system-form' 
					id='bookacti-booking-system-form-" . $atts[ 'id' ] . "' >
				  <input type='hidden' name='action' value='bookactiSubmitBookingForm' />
				  <input type='hidden' name='bookacti_booking_system_id' value='" . $atts[ 'id' ] . "' />"

				  . wp_nonce_field( 'bookacti_booking_form', 'nonce_booking_form', true, false )

				  . bookacti_get_booking_system( $atts ) .

				  "<div class='bookacti-booking-system-field-container' >
					  <label for='bookacti-quantity-booking-form-" . $atts[ 'id' ] . "' class='bookacti-booking-system-label' >"
						  . __( 'Quantity', BOOKACTI_PLUGIN_NAME ) .
					  "</label>
					  <input	name='bookacti_quantity'
							  id='bookacti-quantity-booking-form-" . $atts[ 'id' ] . "'
							  class='bookacti-booking-system-field bookacti-quantity'
							  type='number' 
							  min='1'
							  value='1' />
				  </div>"

				  .  apply_filters( 'bookacti_booking_form_fields', '', $atts, $content ) .

				  "<div class='bookacti-booking-system-field-container bookacti-booking-system-field-submit-container' >
					  <input type='submit' 
							 class='button' 
							 value='" . $atts[ 'button' ] . "' />
				  </div>
			  </form>";
	
    return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $output, $atts, $content );
}


/**
 * Display a user related booking list via shortcode
 * Eg: [bookingactivities_list user='1'] // Single user id, if empty default to current user id
 * 
 * @since 1.0.0
 * @version 1.1.0
 * 
 * @param array $atts [user]
 * @param string $content
 * @param string $tag Should be "bookingactivities_list"
 * @return string The booking list corresponding to given parameters
 */
function bookacti_shortcode_bookings_list( $atts = [], $content = null, $tag = '' ) {
	
	// normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
	
	// override default attributes with user attributes
	if( isset( $atts[ 'user' ] ) ) {
		$atts[ 'user' ] = intval( $atts[ 'user' ] );
	}
    $atts = shortcode_atts( array( 'user' => get_current_user_id() ), $atts, $tag );
	
	// If no user, return an empty string
	if( empty( $atts[ 'user' ] ) ) {
		return apply_filters( 'bookacti_shortcode_' . $tag . '_output', '', $atts, $content );
	}
	
	// Set up booking list columns
	$columns = apply_filters( 'bookacti_user_bookings_list_columns_titles', array(
		10	=> array( 'id' => 'id',			'title' => esc_html_x( 'id', 'An id is a unique identification number' ), BOOKACTI_PLUGIN_NAME ),
		20	=> array( 'id' => 'activity',	'title' => esc_html__( 'Activity', BOOKACTI_PLUGIN_NAME ) ),
		40	=> array( 'id' => 'quantity',	'title' => esc_html__( 'Quantity', BOOKACTI_PLUGIN_NAME ) ),
		50	=> array( 'id' => 'state',		'title' => esc_html_x( 'State', 'State of a booking', BOOKACTI_PLUGIN_NAME ) ),
		100 => array( 'id' => 'actions',	'title' => esc_html__( 'Actions', BOOKACTI_PLUGIN_NAME ) )
	), $atts[ 'user' ] );
	
	// Order columns
	ksort( $columns );
	
	
	// TABLE HEADER
	$head_columns = '';
	foreach( $columns as $column ) {
		$head_columns .= "<th class='bookacti-column-" . sanitize_title_with_dashes( $column[ 'id' ] ) . "' ><div class='bookacti-booking-" . $column[ 'id' ] . "-title' >" . $column[ 'title' ] . "</div></th>";
	} 
	
	
	// TABLE CONTENT
	$bookings				= bookacti_get_bookings_by_user_id( $atts[ 'user' ] ); 
	$list_items				= array();
	$groups_already_added	= array();
	$hidden_states			= apply_filters( 'bookacti_bookings_list_hidden_states', array( 'in_cart', 'expired', 'removed' ) );
	
	
	// Build an array of bookings rows
	foreach( $bookings as $booking ) {

		// Single Bookings
		if( empty( $booking->group_id ) ) {

			if( ! in_array( $booking->state, $hidden_states ) ) {

				$list_items[] = apply_filters( 'bookacti_user_bookings_list_columns_value', array(
					'id'		=> $booking->id,
					'activity'	=> bookacti_get_formatted_booking_events_list( array( $booking ) ),
					'quantity'	=> $booking->quantity,
					'state'		=> bookacti_format_booking_state( $booking->state ),
					'actions'	=> bookacti_get_booking_actions_html( $booking->id, 'front' ),
					'type'		=> 'single'
				), $booking, $atts[ 'user' ] );
			}

		// Booking groups
		} else if( ! in_array( $booking->group_id, $groups_already_added ) ) {

			$state = bookacti_get_booking_group_state( $booking->group_id ); 

			if( ! in_array( $state, $hidden_states ) ) {

				$quantity		= bookacti_get_booking_group_quantity( $booking->group_id ); 
				$group_bookings = bookacti_get_bookings_by_booking_group_id( $booking->group_id ); 

				$list_items[] = apply_filters( 'bookacti_user_bookings_list_columns_value', array(
					'id'		=> $booking->group_id,
					'activity'	=> bookacti_get_formatted_booking_events_list( $group_bookings ),
					'quantity'	=> $quantity,
					'state'		=> bookacti_format_booking_state( $state ),
					'actions'	=> bookacti_get_booking_group_actions_html( $booking->group_id, 'front' ),
					'type'		=> 'group'
				), $booking, $atts[ 'user' ] );

				// Flag the group as 'already added' to make it appears only once in the list
				$groups_already_added[] = $booking->group_id;
			}
		}

	}

	
	// Build the HTML booking rows
	$body_columns = '';
	foreach( $list_items as $list_item ) {
		$body_columns .= "<tr>";
		foreach( $columns as $column ) {

			// Format output values
			switch ( $column[ 'id' ] ) {
				case 'id':
					$value = isset( $list_item[ 'id' ] ) ? intval( $list_item[ 'id' ] ) : '';
					break;
				case 'activity':
					$value = isset( $list_item[ 'activity' ] ) ? $list_item[ 'activity' ] : '';
					break;
				case 'quantity':
					$value = isset( $list_item[ 'quantity' ] ) ? intval( $list_item[ 'quantity' ] ) : '';
					break;
				case 'state':
				case 'actions':
				default:
					$value = isset( $list_item[ $column[ 'id' ] ] ) ? $list_item[ $column[ 'id' ] ] : '';
			}

			$class_empty = empty( $value ) ? 'bookacti-empty-column' : '';
			$body_columns .=  "<td data-title='" . esc_attr( $column[ 'title' ] ) . "' class='bookacti-column-" . sanitize_title_with_dashes( $column[ 'id' ] ) . ' ' . $class_empty . "' ><div class='bookacti-booking-" . $column[ 'id' ] . "' >"  . $value . "</div></td>";
		} 
		$body_columns .= "</tr>";
	}
	
	// If there are no booking rows
	if( empty( $list_items ) ) {
		$body_columns	.= "<tr>"
						.	"<td colspan='" . esc_attr( count( $columns ) ) . "'>" . esc_html__( 'You don\'t have any bookings.', BOOKACTI_PLUGIN_NAME ) . "</td>"
						. "</tr>";
	}
	
	
	// TABLE OUTPUT
	$output = "<div id='bookacti-user-bookings-list-" . $atts[ 'user' ] . "' class='bookacti-user-bookings-list'>
					<table>
						<thead>
							<tr>" 
								. $head_columns .
							"</tr>
						</thead>
						<tbody>"
							. $body_columns .
						"</tbody>
					</table>
				</div>";

	// Include bookings dialogs if they are not already
	include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-bookings-dialogs.php' );
	
	return apply_filters( 'bookacti_shortcode_' . $tag . '_output', $output, $atts, $content );
}


/**
 * Check if booking form is correct and then book the event, or send the error message
 * 
 * @since 1.0.0
 * @version 1.1.0
 */
function bookacti_controller_validate_booking_form() {
	
	// Check nonce and capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_booking_form', 'nonce_booking_form', false );
	$is_allowed		= is_user_logged_in();
	
	if( $is_nonce_valid && $is_allowed ) { 

		//Gether the form variables
		$booking_form_values = apply_filters( 'bookacti_booking_form_values', array(
		'user_id'			=> intval( get_current_user_id() ),
		'booking_system_id'	=> sanitize_title_with_dashes( $_POST[ 'bookacti_booking_system_id' ] ),
		'group_id'			=> intval( $_POST[ 'bookacti_group_id' ] ),
		'event_id'			=> intval( $_POST[ 'bookacti_event_id' ] ),
		'event_start'		=> bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] ),
		'event_end'			=> bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] ),
		'quantity'			=> intval( $_POST[ 'bookacti_quantity' ] ),
		'default_state'		=> 'pending' ) );

		//Check if the form is ok and if so Book temporarily the event
		$response = bookacti_validate_booking_form( $booking_form_values[ 'group_id' ], $booking_form_values[ 'event_id' ], $booking_form_values[ 'event_start' ], $booking_form_values[ 'event_end' ], $booking_form_values[ 'quantity' ] );
		
		if( $booking_form_values[ 'user_id' ] != get_current_user_id() && ! current_user_can( 'bookacti_edit_bookings' ) ) {
			$response[ 'status' ] = 'failed';
			$response[ 'message' ] = __( 'You can\'t make a booking for someone else.', BOOKACTI_PLUGIN_NAME );
		}
		
		if( $response[ 'status' ] === 'success' ) {
			
			$booking_id = bookacti_insert_booking(	$booking_form_values[ 'user_id' ], 
													$booking_form_values[ 'event_id' ], 
													$booking_form_values[ 'event_start' ],
													$booking_form_values[ 'event_end' ], 
													$booking_form_values[ 'quantity' ], 
													$booking_form_values[ 'default_state' ],
													$booking_form_values[ 'group_id' ] );
			
			if( ! is_null( $booking_id ) ) {

				do_action( 'bookacti_booking_form_validated', $booking_id, $booking_form_values );

				$message = __( 'Your event has been booked successfully!', BOOKACTI_PLUGIN_NAME );
				wp_send_json( array( 'status' => 'success', 'message' => esc_html( $message ), 'booking_id' => $booking_id ) );
			
			} else {
				$message = __( 'An error occurred, please try again.', BOOKACTI_PLUGIN_NAME );
			}
			
		} else {
			$message = $response[ 'message' ];
		}
		
	} else {
		$message = __( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME );
		if( ! $is_allowed ) {
			$message = __( 'You are not logged in. Please create an account and log in first.', BOOKACTI_PLUGIN_NAME );
		}
		
		$response = array( 'status' => 'failed', 'error' => 'not_allowed', 'message' => $message );
	}
	
	$return_array = apply_filters( 'bookacti_booking_form_error', array( 'status' => 'failed', 'message' => $message ), $response );
	
	wp_send_json( array( 'status' =>  $return_array[ 'status' ], 'message' => esc_html( $return_array[ 'message' ] ) ) );
}
add_action( 'wp_ajax_bookactiSubmitBookingForm', 'bookacti_controller_validate_booking_form' );
add_action( 'wp_ajax_nopriv_bookactiSubmitBookingForm', 'bookacti_controller_validate_booking_form' );