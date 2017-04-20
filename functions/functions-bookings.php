<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// Return the HTML code to display activities by templates in the bookings page
function bookacti_get_activities_html_for_booking_page( $template_ids, $activity_ids = array() ) {

	$activities = bookacti_get_activities_by_template_ids( $template_ids, false );
	$j = 0;
	$html = '';
	foreach ( $activities as $activity ) {	
		if( ( empty( $activity_ids )  && $j === 0 ) || in_array( $activity->id, $activity_ids ) ) { $selected = 'selected'; } else { $selected = ''; }

		// Retrieve activity title
		$title = apply_filters( 'bookacti_translate_text', stripslashes( $activity->title ) );

		// Display activity
		$html.=	"<div class='bookacti-bookings-filter-activity bookacti-bookings-filter' "
			.		"data-activity-id='" . esc_attr( $activity->id ) . "' "
			.		"style='background-color: " . esc_attr( $activity->color ) . "; border-color: " . esc_attr( $activity->color ) . "' " 
			.		esc_attr( $selected )
			.	" >"
			.		"<div class='bookacti-bookings-filter-content' >"
			.			"<div class='bookacti-bookings-filter-activity-title' >"
			.				"<strong>" . esc_html( $title ). "</strong>"
			.			"</div>"
			.		"</div>"
			.		"<div class='bookacti-bookings-filter-bg' ></div>"
			.	"</div>";

		$j++;
	}

	return apply_filters( 'bookacti_activities_html_by_templates', $html, $template_ids, $activity_ids );
}


// CHECK IF USER IS ALLOWED TO MANAGE A BOOKING
function bookacti_user_can_manage_booking( $booking_id, $user_id = false ) {

	$user_can_manage_booking = false;
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	
	if( user_can( $user_id, 'bookacti_edit_bookings' ) 
	||  bookacti_get_booking_owner( $booking_id ) == $user_id ) { $user_can_manage_booking = true; }
	
	return apply_filters( 'bookacti_user_can_manage_booking', $user_can_manage_booking, $booking_id, $user_id );
}


// CHECK IF CANCEL OR RESCHEDULE A BOOKING IS ALLOWED
function bookacti_booking_can_be_cancelled_or_rescheduled( $booking_id, $return_single = false ) {
	
	// Default to true
	$booking					= bookacti_get_booking_by_id( $booking_id );
	$is_allowed['cancel']		= $booking->state !== 'cancelled';
	$is_allowed['reschedule']	= true;
	
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		// Init variable
		$is_cancel_allowed			= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' );
		$is_reschedule_allowed		= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' );
		$is_active					= bookacti_is_booking_active( $booking_id );
		$is_in_delay				= apply_filters( 'bookacti_bypass_delay', false, $booking_id );
		
		if( ! $is_in_delay ) {
			// Check delay
			$delay = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'cancellation_min_delay_before_event' );
			if( ! is_numeric( $delay ) || $delay < 1 ) { $delay = 1; } 

			$event_datetime		= DateTime::createFromFormat( 'Y-m-d H:i:s', $booking->event_start );
			$delay_datetime		= $event_datetime->sub( new DateInterval( 'P' . $delay . 'D' ) );
			$current_datetime	= new DateTime();

			if( $current_datetime < $delay_datetime ) { $is_in_delay = true; }
		}
		
		// Final check and return the actions array without invalid entries
		if( ! $is_cancel_allowed	 || ! $is_active || ! $is_in_delay ) { $is_allowed['cancel'] = false; }
		if( ! $is_reschedule_allowed || ! $is_active || ! $is_in_delay ) { $is_allowed['reschedule'] = false; }
	}
	
	$is_allowed = apply_filters( 'bookacti_booking_can_be_cancelled_or_rescheduled', $is_allowed, $booking );
	
	if( $return_single === 'cancel' )			{ return $is_allowed['cancel']; }
	else if( $return_single === 'reschedule' )	{ return $is_allowed['reschedule']; }
	
	return $is_allowed;
}


// CHECK IF A BOOKING CAN BE RESCHEDULED TO ANOTHER EVENT
function bookacti_booking_can_be_cancelled( $booking_id ) {
	
	$is_allowed = bookacti_booking_can_be_cancelled_or_rescheduled( $booking_id, 'cancel' );
	
	return apply_filters( 'bookacti_booking_can_be_cancelled', $is_allowed, $booking_id );
}


// CHECK IF A BOOKING CAN BE RESCHEDULED TO ANOTHER EVENT
function bookacti_booking_can_be_rescheduled( $booking_id, $event_id, $event_start, $event_end ) {
	
	$is_allowed = bookacti_booking_can_be_cancelled_or_rescheduled( $booking_id, 'reschedule' );
	
	return apply_filters( 'bookacti_booking_can_be_rescheduled', $is_allowed, $booking_id, $event_id, $event_start, $event_end );
}



// CHECK IF A BOOKING CAN BE REFUNDED
function bookacti_booking_can_be_refunded( $booking_id, $refund_action = false ) {

	$true			= true;
	$state			= bookacti_get_booking_state( $booking_id );
	$refund_actions	= bookacti_get_refund_actions_by_booking_id( $booking_id );
	
	// Disallow refund in those cases:
	// -> If the booking is already marked as refunded, 
	if( $state === 'refunded' 
	// -> If there are no refund action available
	||  empty( $refund_actions )
	// -> If the refund action is set but doesn't exist in available refund actions list
	|| ( ! empty( $refund_action ) && ! array_key_exists( $refund_action, $refund_actions ) ) 
	// -> If the user is not an admin, the booking state has to be 'cancelled' in the first place
	|| ( ! current_user_can( 'bookacti_edit_bookings' ) && $state !== 'cancelled' ) )	{ 
		
		$true = false; 
		
	}
	
	return apply_filters( 'bookacti_booking_can_be_refunded', $true, $booking_id );
}


// CHECK IF A BOOKING STATE CAN BE CHANGED TO ANOTHER
function bookacti_booking_state_can_be_changed_to( $booking_id, $new_state ) {
	
	$true = true;
	switch ( $new_state ) {
		case 'cancelled':
			$true = bookacti_booking_can_be_cancelled( $booking_id );
			break;
		case 'refund_requested':
			if( current_user_can( 'bookacti_edit_bookings' ) ) {
				$state = bookacti_get_booking_state( $booking_id );
				if ( $state === 'refund_requested' ) {
					$true = false; 
				}
			} else {
				$true = bookacti_booking_can_be_refunded( $booking_id );
			}
			break;
		case 'refunded':
			$true = bookacti_booking_can_be_refunded( $booking_id );
			break;
	}
	
	return apply_filters( 'bookacti_booking_state_can_be_changed', $true, $booking_id, $new_state );
}


// GET BOOKING ACTIONS ARRAY
function bookacti_get_booking_actions_array( $booking_id ){
	
	$is_allowed = bookacti_booking_can_be_cancelled_or_rescheduled( $booking_id );
	
	$possible_actions_array = array();
	
	if( current_user_can( 'bookacti_edit_bookings' ) ) {
		$possible_actions_array[ 'change-state' ] = array( 
			'class'			=> 'bookacti-change-booking-state',
			'label'			=> __( 'Change booking state',  BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Change the booking state to any available state.', BOOKACTI_PLUGIN_NAME ),
			'link'			=> '',
			'admin_or_front'=> 'admin' );
	}
	
	if( $is_allowed[ 'cancel' ] ) {
		$possible_actions_array[ 'cancel' ] = array( 
			'class'			=> 'bookacti-cancel-booking',
			'label'			=> __( 'Cancel', BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Cancel the booking.', BOOKACTI_PLUGIN_NAME ),
			'link'			=> '',
			'admin_or_front'=> 'front' );
	}
	
	if( $is_allowed[ 'reschedule' ] ) {
		$possible_actions_array[ 'reschedule' ] = array( 
			'class'			=> 'bookacti-reschedule-booking',
			'label'			=> __( 'Reschedule', BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Reschedule the booking.', BOOKACTI_PLUGIN_NAME ),
			'link'			=> '',
			'admin_or_front'=> 'both' );
	}
	
	if( bookacti_booking_can_be_refunded( $booking_id ) ) {
		$possible_actions_array[ 'refund' ] = array( 
			'class'			=> 'bookacti-refund-booking',
			'label'			=> __( 'Request a refund', BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Request a refund.', BOOKACTI_PLUGIN_NAME ),
			'link'			=> '',
			'admin_or_front'=> 'both' );
	}
	
	return apply_filters( 'bookacti_booking_actions', $possible_actions_array, $booking_id );
}


// GET BOOKING ACTIONS HTML
function bookacti_get_booking_actions_html( $booking_id, $admin_or_front = 'both', $with_container = false ) {
	
	$booking_actions = bookacti_get_booking_actions_array( $booking_id );
	
	$booking_actions_html = '';
	if( $with_container ) {
		$booking_actions_html .= '<div class="bookacti-booking-actions" data-booking-id="' . esc_attr( $booking_id ) . '" >';
	}
	$booking_actions_html .= apply_filters( 'bookacti_before_booking_actions', '', $booking_id );
	
	foreach( $booking_actions as $action_id => $action ){
		if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
			$booking_actions_html	.= '<a '
										. 'href="' . esc_url( $action[ 'link' ] ) . '" '
										. 'id="bookacti-booking-action-' . esc_attr( $action_id ) . '-' . esc_attr( $booking_id ) . '" '
										. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-action bookacti-tip" '
										. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
										. 'data-booking-id="' . esc_attr( $booking_id ) . '" >' 
											. esc_html( $action[ 'label' ] )
									. '</a> | ';
		}
	}
	
	if( substr( $booking_actions_html, -3 ) === ' | ' ) {
		$booking_actions_html = substr( $booking_actions_html, 0, -3 );
	}
	
	$booking_actions_html .= apply_filters( 'bookacti_after_booking_actions', '', $booking_id );
	if( $with_container ) {
		$booking_actions_html .= '</div>';
	}

	return apply_filters( 'bookacti_booking_actions_html', $booking_actions_html, $booking_id, $admin_or_front );
}


// GET BOOKING GLOBAL ACTIONS ARRAY
function bookacti_get_booking_global_actions_array( $bookings ) {
	$global_actions = array();
	
	return apply_filters( 'bookacti_booking_global_actions', $global_actions, $bookings );
}


// GET BOOKING GLOBAL ACTIONS HTML
function bookacti_get_booking_global_actions_html( $bookings, $admin_or_front = 'both' ) {
	
	$global_actions = bookacti_get_booking_global_actions_array( $bookings );
	
	$global_actions_html = '';
	foreach( $global_actions as $action_id => $action ){
		if( $action[ 'admin_or_front' ] === 'both' || $action[ 'admin_or_front' ] === $admin_or_front ) {
			$global_actions_html .= '<a '
									. 'href="' . esc_url( $action[ 'link' ] ) . '" '
									. 'id="bookacti-booking-action-' . esc_attr( $action_id ) . '" '
									. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-action bookacti-tip" '
									. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" >'
										. esc_html( $action[ 'label' ] )
								.   '</a> ';
		}
	}
	
	return apply_filters( 'bookacti_bookings_global_actions', $global_actions_html, $bookings );
}


// GET BOOKING INFOS
function bookacti_get_bookings_for_bookings_list( $event_id, $event_start, $event_end, $template_id = NULL ) {
	
	// Retrieve inactive and temporary bookings ?
	$active_only	= true;
	$show_inactive_bookings	= bookacti_get_setting_value_by_user( 'bookacti_bookings_settings', 'show_inactive_bookings' );
	if( intval( $show_inactive_bookings ) === 1 ) { $active_only = false; }
	
	$booking_data = apply_filters( 'bookacti_get_bookings_data_for_bookings_list', array(
		'template_id'	=> $template_id, 
		'event_id'		=> $event_id, 
		'event_start'	=> $event_start, 
		'event_end'		=> $event_end, 
		'active_only'	=> $active_only, 
		'state_not_in'	=> array()
	) );
	
return bookacti_get_bookings( $booking_data[ 'template_id' ], $booking_data[ 'event_id' ], $booking_data[ 'event_start' ], $booking_data[ 'event_end' ], $booking_data[ 'active_only' ], $booking_data[ 'state_not_in' ] );
}


// GET USERS INFO FOR BOOKINGS
function bookacti_get_users_data_by_bookings( $bookings ) {
	
	// Retrieve all the different users
	$user_ids = array();
	foreach( $bookings as $bookings_array ) {
		foreach( $bookings_array as $booking ) {
			if( ! in_array( $booking->user_id, $user_ids ) ){
				$user_ids[] = $booking->user_id;
			}
		}
	}

	// Retrieve information about those users and stock them into an array sorted by user id
	return apply_filters( 'bookacti_users_data', bookacti_get_users_data( $user_ids ) );
}


// GET AVAILABLE ACTIONS USER CAN TAKE TO BE REFUNDED 
function bookacti_get_refund_actions(){
	$possible_actions_array = array(
		'email' => array( 
			'id'			=> 'email',
			'label'			=> __( 'Email', BOOKACTI_PLUGIN_NAME ),
			'description'	=> __( 'Send a refund request by email to the administrator.', BOOKACTI_PLUGIN_NAME ) )
	);

	return apply_filters( 'bookacti_refund_actions', $possible_actions_array );
}


// GET REFUND ACTIONS FOR A SPECIFIC BOOKING
function bookacti_get_refund_actions_by_booking_id( $booking_id ) {
	
	$possible_actions = apply_filters( 'bookacti_refund_actions_by_booking', bookacti_get_refund_actions(), $booking_id );
	
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		$allowed_actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
		if( ! is_array( $allowed_actions ) ) {
			if( ! empty( $allowed_actions ) ) {
				$allowed_actions = array( $allowed_actions => 1 );
			} else {
				$allowed_actions = array();
			}
		}
		// Keep all possible actions that are allowed
		$possible_actions = array_intersect_key( $possible_actions, $allowed_actions );
	}
	
	return $possible_actions;
}


// GET DIALOG REFUND TEXT FOR A SPECIFIC BOOKING
function bookacti_get_refund_dialog_html_by_booking_id( $booking_id ) {
	
	$possible_actions = bookacti_get_refund_actions_by_booking_id( $booking_id );
	
	$actions_list = '';
	foreach( $possible_actions as $possible_action ){
		$actions_list .= '<div class="bookacti-refund-option" >'
							. '<span class="bookacti-refund-option-radio" >'
								. '<input '
									. ' type="radio" '
									. ' name="refund-action" '
									. ' value="' . esc_attr( $possible_action['id'] ) . '" '
									. ' id="bookacti-refund-action-' . esc_attr( $possible_action['id'] ) . '" '
									. ' class="bookacti-refund-action" '
								. '/>'
							. '</span>'
							. '<label for="bookacti-refund-action-' . esc_attr( $possible_action['id'] ) . '" class="bookacti-refund-option-label-and-description" >'
								. '<strong class="bookacti-refund-option-label" >' . esc_html( $possible_action['label'] ). ':</strong> '
								. '<span class="bookacti-refund-option-description" >' . esc_html( $possible_action['description'] ) . '</span>'
							. '</label>'
						. '</div>';
	}

	// Define title and add actions list
	$html_to_return		= '';
	if( empty( $possible_actions ) ) {
		$html_to_return .= '<div id="bookacti-no-refund-option" >';
		$html_to_return .= esc_html__( 'Sorry, no available refund option were found. Please contact the administrator.', BOOKACTI_PLUGIN_NAME );
		$html_to_return .= '</div>';
	} else {

		$html_to_return .= apply_filters( 'bookacti_before_refund_actions', '', $booking_id );

		$html_to_return .= '<div id="bookacti-refund-option-title" >';
		if( count( $possible_actions ) === 1 ) {
			$html_to_return .= esc_html__( 'There is only one available refund option:', BOOKACTI_PLUGIN_NAME );
		} else {
			$html_to_return .= esc_html__( 'Pick a refund option:', BOOKACTI_PLUGIN_NAME );
		}
		$html_to_return .= '</div>';

		$html_to_return .= '</div><form id="bookacti-refund-options" >';
		$html_to_return .= wp_nonce_field( 'bookacti_refund_booking', 'nonce_refund_booking', true, false );
		$html_to_return .= $actions_list;
		$html_to_return .= '</form>';
	}
	
	return $html_to_return;
}


// SEND A REFUND REQUEST BY EMAIL FOR A SPECIFIC BOOKING
function bookacti_send_email_refund_request( $booking_id, $user_message = false ) {
	
	$user		= get_userdata( get_current_user_id() );
	$booking	= bookacti_get_booking_by_id( $booking_id );
	$activity	= bookacti_get_activity_by_booking_id( $booking_id );
	$template	= bookacti_get_template_by_booking_id( $booking_id );
	
	$to			= apply_filters( 'bookacti_refund_request_email_to', array( get_option( 'admin_email' ) ), $booking_id );
	$subject	= apply_filters( 'bookacti_refund_request_email_subject', '/!\\ ' . sprintf( __(  'Refund request for booking %1$s', BOOKACTI_PLUGIN_NAME ), $booking_id ), $booking_id );
	
	$data = array();
	
	$data['user']			= array();
	$data['user']['name']	= isset( $user->first_name ) && isset( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->user_login;
	$data['user']['name']	= '<a href="' . esc_url( get_edit_user_link() ) . '">' . esc_html( $data['user']['name'] ) . '</a>';
	$data['user']['email']	= '<a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a>';
	
	$data['booking']					= array();
	$data['booking']['template_name']	= stripslashes( $template->title ) . ' (' . _x( 'id', 'An id is a unique identification number' ) . ': ' . $template->id . ')';
	$data['booking']['activity_name']	= apply_filters( 'bookacti_translate_text', stripslashes( $activity->title ) ) . ' (' . _x( 'id', 'An id is a unique identification number' ) . ': ' . $activity->id . ')';
	$data['booking']['event_start']		= bookacti_format_datetime( $booking->event_start );
	$data['booking']['event_end']		= bookacti_format_datetime( $booking->event_end );
	$data['booking']['quantity']		= $booking->quantity;
	$data['booking']['status']			= $booking->state;
	
	$data = apply_filters( 'bookacti_refund_request_email_data', $data, $booking_id );
	
	/* translators: %1$s is a user name and %2$s is the booking ID. */
	$message = '<h3>' . esc_html( sprintf( __( '%1$s wants to be refund for booking %2$s', BOOKACTI_PLUGIN_NAME ), $data['user']['name'], $booking_id ) ) . '</h3>';
	foreach( $data as $category_name => $category_data ) {
		$message .= '<h4>' . esc_html( ucfirst ( str_replace( '_', ' ', $category_name ) ) ) . '</h4>';
		$message .= '<table style="border: none;" >';
		foreach( $category_data as $name => $value ) {
			$message .= '<tr><td style="border: none; width: 135px; padding-right: 15px;">' . esc_html( ucfirst ( str_replace( '_', ' ', $name ) ) ) . '</td><td>' . esc_html( $value ) . '</td>';
		}
		$message .= '</table>';
	}
	
	/* translators: Message left by the user */
	if( $user_message ) {
		$message	.= '<h4>' . esc_html__( 'User message', BOOKACTI_PLUGIN_NAME ). '</h4>';
		$message	.= '<em>' . esc_html( $user_message ) . '</em><br/>';
	}
	
	$message	= apply_filters( 'bookacti_refund_request_email_message', $message, $booking_id, $data, $user_message );
	$headers	= apply_filters( 'bookacti_refund_request_email_headers', array( 'Content-Type: text/html; charset=UTF-8' ) );
	
	$sent = wp_mail( $to, $subject, $message, $headers );
	
	return $sent;
}


// FORMAT BOOKING STATE
// Retrieve booking states labels and display data
function bookacti_get_booking_state_labels() {
	$booking_states_labels = apply_filters( 'bookacti_booking_states_labels_array', array(
		'cancelled'			=> array( 'display_state' => 'bad',		'label' => __( 'Cancelled', BOOKACTI_PLUGIN_NAME ) ),
		'refunded'			=> array( 'display_state' => 'bad',		'label' => __( 'Refunded', BOOKACTI_PLUGIN_NAME ) ),
		'refund_requested'	=> array( 'display_state' => 'bad',		'label' => __( 'Refund requested', BOOKACTI_PLUGIN_NAME ) ),
		'pending'			=> array( 'display_state' => 'warning',	'label' => __( 'Pending', BOOKACTI_PLUGIN_NAME ) ),
		'booked'			=> array( 'display_state' => 'good',	'label' => __( 'Booked', BOOKACTI_PLUGIN_NAME ) )
	) );
	
	return $booking_states_labels;
}


// Give a the formatted and translated booking state
function bookacti_format_booking_state( $state ) {
	$booking_states_labels = bookacti_get_booking_state_labels();

	if( isset( $booking_states_labels[ $state ] ) ) {
		$formatted_value = '<span class="bookacti-booking-state bookacti-booking-state-' . esc_attr( $booking_states_labels[ $state ][ 'display_state' ] ) . '" data-booking-state="' . esc_attr( $state ) . '" >' . esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '</span>';
	} else {
		$formatted_value = '<span class="bookacti-booking-state" data-booking-state="' . esc_attr( $state ) . '" >' . esc_html__( $state, BOOKACTI_PLUGIN_NAME ) . '</span>';
	}
	
	return apply_filters( 'bookacti_booking_states_display', $formatted_value, $state, $booking_states_labels );
}


// Give an array of all ACTIVE booking state, every other booking states will be considered as INACTIVE
function bookacti_get_active_booking_states() {
	return apply_filters( 'bookacti_active_booking_states', array( 'booked', 'pending' ) );
}


// Format a booking date 
function bookacti_format_booking_dates( $start, $end ) {
	
	// Make 'from' and 'to' intelligible values
	$from_val		= bookacti_format_datetime( $start );
	$sep_val		= '';
	$to_val			= '';
	$to_hour_or_date= '';
	if( substr( $start, 0, 10 ) === substr( $end, 0, 10 ) ) { 
		$sep_val= ' ' . _x( 'to', 'between two hours', BOOKACTI_PLUGIN_NAME ) . ' ';
		/* translators: Datetime format. Must be adapted to each country. Use strftime documentation to find the appropriated combinaison http://php.net/manual/en/function.strftime.php */
		$to_val = strftime( __( '%I:%M %p', BOOKACTI_PLUGIN_NAME ), strtotime( $end ) );
		$to_val = ! mb_check_encoding( $to_val, 'UTF-8' ) ? utf8_encode( $to_val ) : $to_val;
		$to_hour_or_date = 'to_hour';
	} else {
		$sep_val= ' ' . _x( 'to', 'between two dates', BOOKACTI_PLUGIN_NAME ) . ' ';
		$to_val	= bookacti_format_datetime( $end );
		$to_hour_or_date = 'to_date';
	}
	
	return array( 'start' => $from_val, 'separator' => $sep_val, 'end' => $to_val, 'to_hour_or_date' => $to_hour_or_date );
}