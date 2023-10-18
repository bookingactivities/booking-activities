<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS CALENDAR

/**
 * AJAX Controller - Update bookings page calendar settings
 * @since 1.8.0
 * @version 1.15.5
 */
function bookacti_controller_update_bookings_calendar_settings() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_update_bookings_calendar_settings', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'update_bookings_calendar_settings' ); }
	
	// Check capabilities
	$is_allowed = current_user_can( 'bookacti_manage_bookings' );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'update_bookings_calendar_settings' ); }
	
	$calendar_settings = bookacti_format_bookings_calendar_settings( $_POST );
	if( ! $calendar_settings ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_fields' ), 'update_bookings_calendar_settings' ); }
	
	update_user_meta( get_current_user_id(), 'bookacti_bookings_calendar_settings', $calendar_settings );
	
	do_action( 'bookacti_bookings_calendar_settings_updated', $calendar_settings );
	
	// Isolate display data
	$default_display_data = bookacti_get_booking_system_default_display_data();
	$display_data = array_intersect_key( $calendar_settings, $default_display_data );
	
	bookacti_send_json( array( 'status' => 'success', 'calendar_settings' => $calendar_settings, 'display_data' => $display_data ), 'update_bookings_calendar_settings' );
}
add_action( 'wp_ajax_bookactiUpdateBookingsCalendarSettings', 'bookacti_controller_update_bookings_calendar_settings' );




// BOOKINGS LIST

/**
 * AJAX Controller - Update bookings page calendar settings
 * @since 1.8.0
 * @version 1.15.5
 */
function bookacti_controller_get_booking_list() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_list', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_booking_list' ); }
	
	// Check capabilities
	$is_allowed = current_user_can( 'bookacti_manage_bookings' );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_booking_list' ); }
	
	$bookings_list_table = new Bookings_List_Table();
	$bookings_list_table->prepare_items();
	
	$default_status  = get_user_meta( get_current_user_id(), 'bookacti_status_filter', true );
	$default_status  = is_array( $default_status ) ? $default_status : array( 'delivered', 'booked', 'pending', 'cancelled', 'refunded', 'refund_requested' );
	$selected_status = $bookings_list_table->filters[ 'status' ];
	
	// Update user default status filter
	if( $selected_status != $default_status && empty( $_POST[ 'keep_default_status' ] ) ) {
		update_user_meta( get_current_user_id(), 'bookacti_status_filter', $selected_status );
	}
	
	ob_start();
	$bookings_list_table->display();
	$booking_list = ob_get_clean();
	
	bookacti_send_json( array( 'status' => 'success', 'booking_list' => $booking_list, 'new_url' => $bookings_list_table->url ), 'get_booking_list' );
}
add_action( 'wp_ajax_bookactiGetBookingList', 'bookacti_controller_get_booking_list' );




// BOOKING ACTIONS

// SINGLE BOOKING

/**
 * AJAX Controller - Cancel a booking
 * @version 1.12.0
 */
function bookacti_controller_cancel_booking() {
	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'cancel_booking' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'cancel_booking' ); }
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'cancel_booking' );
	}
	
	if( $booking->state === 'cancelled' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_already_cancelled', 'message' => esc_html__( 'The booking is already cancelled.', 'booking-activities' ) ), 'cancel_booking' );
	}

	$can_be_cancelled = bookacti_booking_can_be_cancelled( $booking, 'front' );
	if( ! $can_be_cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking', 'message' => esc_html__( 'The booking cannot be cancelled.', 'booking-activities' ) ), 'cancel_booking' );
	}

	$cancelled = bookacti_cancel_booking( $booking_id );
	if( ! $cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking', 'message' => esc_html__( 'An error occurred while trying to cancel the booking.', 'booking-activities' ) ), 'cancel_booking' );
	}

	do_action( 'bookacti_booking_state_changed', $booking, 'cancelled', array( 'is_admin' => false ) );

	$new_booking  = bookacti_get_booking_by_id( $booking_id, true );
	$context      = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns      = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters  = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $booking_id ), array( $new_booking ), 'cancel_booking', $context );
	$row          = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );
	$allow_refund = bookacti_booking_can_be_refunded( $new_booking, 'front' );
	
	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'allow_refund' => $allow_refund ), 'cancel_booking' );
}
add_action( 'wp_ajax_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );
add_action( 'wp_ajax_nopriv_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );


/**
 * AJAX Controller - Get possible actions to refund a booking
 * @version 1.12.0
 */
function bookacti_controller_get_refund_actions_html() {
	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) { bookacti_send_json_invalid_nonce( 'get_refund_actions_html' ); }
	
	// Check capabilities
	if( ! bookacti_user_can_manage_booking( $booking_id ) ) { bookacti_send_json_not_allowed( 'get_refund_actions_html' ); }
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}
	
	$front_or_admin = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	if( ! bookacti_booking_can_be_refunded( $booking, $front_or_admin ) ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}
	
	$refund_actions_array = bookacti_get_booking_refund_actions( array( $booking ), 'single', $front_or_admin );
	$refund_actions_html  = bookacti_get_booking_refund_options_html( array( $booking ), 'single', $refund_actions_array, $front_or_admin );
	$refund_amount        = bookacti_get_booking_refund_amount( array( $booking ), 'single' );
	
	bookacti_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array, 'amount' => $refund_amount ), 'get_refund_actions_html' );
}
add_action( 'wp_ajax_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );


/**
 * AJAX Controller - Refund a booking
 * @version 1.12.1
 */
function bookacti_controller_refund_booking() {
	$booking_id = intval( $_POST[ 'booking_id' ] );
	
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) { bookacti_send_json_invalid_nonce( 'refund_booking' ); }
	
	// Check capabilities
	if( ! bookacti_user_can_manage_booking( $booking_id ) ) { bookacti_send_json_not_allowed( 'refund_booking' ); }
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'refund_booking' );
	}
	
	$is_admin         = intval( $_POST[ 'is_admin' ] );
	$front_or_admin   = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	$sanitized_action = sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
	
	$refund_actions = bookacti_get_booking_refund_actions( array( $booking ), 'single', $front_or_admin );
	$refund_action  = array_key_exists( $sanitized_action, $refund_actions ) ? $sanitized_action : 'email';

	if( ! bookacti_booking_can_be_refunded( $booking, $front_or_admin, $refund_action ) ) {
		bookacti_send_json( array( 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'refund_booking' );
	}

	$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );
	
	if( $refund_action === 'email' ) {
		// The refund request notification is send by bookacti_send_notification_when_booking_state_changes() on the hook 'bookacti_booking_state_changed'
		$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested', 'message' => esc_html__( 'Your refund request has been sent. We will contact you soon.', 'booking-activities' ) );
	} else {
		$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), array( $booking ), 'single', $refund_action, $refund_message, $front_or_admin );
	}

	if( $refunded[ 'status' ] !== 'success' ) {
		if( empty( $refunded[ 'message' ] ) ) { 
			$refunded[ 'message' ] = esc_html__( 'Error occurs while trying to request a refund. Please contact the administrator.', 'booking-activities' );
		}
		bookacti_send_json( $refunded, 'refund_booking' );
	}
	
	// Save the refund message
	if( $refund_message ) {
		bookacti_update_metadata( 'booking', $booking_id, array( 'refund_message' => $refund_message ) );
	}

	if( empty( $refunded[ 'do_not_update_status' ] ) ) {
		if( empty( $refunded[ 'new_state' ] ) )	{ $refunded[ 'new_state' ] = 'refunded'; }
		$updated = bookacti_update_booking_state( $booking_id, $refunded[ 'new_state' ] );

		// Hook status changes
		if( $updated ) {
			do_action( 'bookacti_booking_state_changed', $booking, $refunded[ 'new_state' ], array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
		}
	}
	
	$new_booking       = bookacti_get_booking_by_id( $booking_id, true );
	$context           = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns           = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters       = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $booking_id ), array( $new_booking ), 'refund_booking', $context );
	$row               = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );
	$refunded[ 'row' ] = $row;

	if( empty( $refunded[ 'message' ] ) ) { $refunded[ 'message' ] = esc_html__( 'Your booking has been successfully refunded.', 'booking-activities' ); }
	
	bookacti_send_json( $refunded, 'refund_booking' );
}
add_action( 'wp_ajax_bookactiRefundBooking', 'bookacti_controller_refund_booking' );
add_action( 'wp_ajax_nopriv_bookactiRefundBooking', 'bookacti_controller_refund_booking' );


/**
 * AJAX Controller - Change booking state
 * @version 1.14.0
 */
function bookacti_controller_change_booking_state() {
	$booking_id = intval( $_POST[ 'booking_id' ] );
	
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'change_booking_status' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id, false );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_status' ); }

	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'change_booking_status' );
	}
	
	$booking_state      = sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
	$payment_status     = sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
	$send_notifications = $_POST[ 'send_notifications' ] ? 1 : 0;
	$is_admin           = intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;

	$new_booking_state  = array_key_exists( $booking_state, bookacti_get_booking_state_labels() ) ? $booking_state : false;
	$new_payment_status = array_key_exists( $payment_status, bookacti_get_payment_status_labels() ) ? $payment_status : false;
	$active_changed     = false;
	
	// Change booking state
	if( $new_booking_state && $booking->state !== $new_booking_state ) {
		$state_can_be_changed = bookacti_booking_state_can_be_changed_to( $booking, $new_booking_state, 'admin' );
		if( ! $state_can_be_changed ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_status', 'message' => esc_html__( 'The booking status cannot be changed.', 'booking-activities' ) ), 'change_booking_status' );
		}

		$was_active = ! empty( $booking->active ) ? 1 : 0;
		$active     = in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		if( $active !== $was_active ) { $active_changed = true; }
		
		$updated = bookacti_update_booking_state( $booking_id, $new_booking_state, $active );
		if( $updated === false ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_status', 'message' => esc_html__( 'An error occurred while trying to change the booking status.', 'booking-activities' ) ), 'change_booking_status' );
		}

		do_action( 'bookacti_booking_state_changed', $booking, $new_booking_state, array( 'is_admin' => $is_admin, 'send_notifications' => $send_notifications ) );
	}

	// Change payment status
	if( $new_payment_status && $booking->payment_status !== $new_payment_status ) {
		$updated = bookacti_update_booking_payment_status( $booking_id, $new_payment_status );
		if( $updated === false ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_payment_status', 'message' => esc_html__( 'An error occurred while trying to change the booking payment status.', 'booking-activities' ) ), 'change_booking_status' );
		}

		do_action( 'bookacti_booking_payment_status_changed', $booking_id, $new_payment_status, array( 'is_admin' => $is_admin ) );
	}
	
	$new_booking = bookacti_get_booking_by_id( $booking_id, true );
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns     = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $booking_id ), array( $new_booking ), 'change_booking_status', $context );
	$row         = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'active_changed' => $active_changed ), 'change_booking_status' );
}
add_action( 'wp_ajax_bookactiChangeBookingState', 'bookacti_controller_change_booking_state' );


/**
 * AJAX Controller - Change booking quantity
 * @since 1.7.10
 * @version 1.15.5
 */
function bookacti_controller_change_booking_quantity() {
	$booking_id   = intval( $_POST[ 'booking_id' ] );
	$new_quantity = intval( $_POST[ 'new_quantity' ] );
	$is_admin     = intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_quantity', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'change_booking_quantity' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id, false );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_quantity' ); }

	// Check if the quantity is valid
	if( $new_quantity < 1 ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_new_quantity', 'message' => esc_html__( 'The new quantity is not valid.', 'booking-activities' ) ), 'change_booking_quantity' );
	}
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'change_booking_quantity' );
	}

	// Update booking quantity
	$updated = bookacti_force_update_booking_quantity( $booking_id, $new_quantity );
	if( $updated === false ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_quantity', 'message' => esc_html__( 'An error occurred while trying to change the booking quantity.', 'booking-activities' ) ), 'change_booking_quantity' );
	}

	do_action( 'bookacti_booking_quantity_updated', $booking_id, $new_quantity, $booking->quantity, array( 'is_admin' => $is_admin ) );

	$new_booking = bookacti_get_booking_by_id( $booking_id, true );
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns     = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters_row = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $booking_id ), array( $new_booking ), 'change_booking_quantity', $context );
	$row         = bookacti_get_booking_list_rows_according_to_context( $context, $filters_row, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row ), 'change_booking_quantity' );
}
add_action( 'wp_ajax_bookactiChangeBookingQuantity', 'bookacti_controller_change_booking_quantity' );


/**
 * AJAX Controller - Get reschedule booking system data by booking ID
 * @since 1.8.0 (was bookacti_controller_get_booking_data)
 * @version 1.15.0
 */
function bookacti_controller_get_reschedule_booking_system_data() {
	$booking_id	= intval( $_POST[ 'booking_id' ] );
	
	// No need to check nonce
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_reschedule_booking_system_data' ); }
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'get_reschedule_booking_system_data' );
	}
	
	$calendar_field_data = $booking->form_id ? bookacti_get_form_field_data_by_name( $booking->form_id, 'calendar' ) : array();
	$init_atts = bookacti_get_calendar_field_booking_system_attributes( $calendar_field_data );
	$atts = $init_atts;
	$mixed_data = array();

	// Set compulsory data
	$atts[ 'id' ]                       = 'bookacti-booking-system-reschedule';
	$atts[ 'form_action' ]              = 'default';
	$atts[ 'when_perform_form_action' ] = 'on_submit';
	$atts[ 'multiple_bookings' ]        = 0;
	$atts[ 'auto_load' ]                = 0;

	// Load only the events from the same activity as the booked event
	$atts[ 'activities' ] = intval( $booking->activity_id ) ? array( intval( $booking->activity_id ) ) : array( 0 );

	// On the backend, display past events and grouped events, from all calendars, and make them all bookable
	$is_admin = intval( $_POST[ 'is_admin' ] );
	if( $is_admin ) {
		$atts[ 'groups_single_events' ] = 1;
		$atts[ 'start' ]                = '';
		$atts[ 'end' ]                  = '';
		$atts[ 'trim' ]                 = 1;
		$atts[ 'past_events' ]          = 1;
		$atts[ 'past_events_bookable' ] = 1;

		// Make sure display data doesn't prevent events from being displayed
		if( $booking->activity_id ) {
			$atts[ 'calendars' ] = bookacti_get_templates_by_activity( $atts[ 'activities' ], true );
			if( count( $atts[ 'calendars' ] ) !== 1 ) {
				$mixed_data = bookacti_get_mixed_template_data( $atts[ 'calendars' ] );
				$atts[ 'display_data' ][ 'slotMinTime' ] = ! empty( $mixed_data[ 'settings' ][ 'slotMinTime' ] ) ? $mixed_data[ 'settings' ][ 'slotMinTime' ] : '00:00';
				$atts[ 'display_data' ][ 'slotMaxTime' ] = ! empty( $mixed_data[ 'settings' ][ 'slotMaxTime' ] ) ? $mixed_data[ 'settings' ][ 'slotMaxTime' ] : '00:00';
			}
		}
	}

	// Add the rescheduled booking data to the booking system data
	$atts[ 'rescheduled_booking_data' ] = (array) apply_filters( 'bookacti_rescheduled_booking_data', $booking );

	$atts = apply_filters( 'bookacti_reschedule_booking_system_attributes', $atts, $booking, $init_atts, $mixed_data );

	bookacti_send_json( array( 'status' => 'success', 'booking_system_data' => $atts ), 'get_reschedule_booking_system_data' );
}
add_action( 'wp_ajax_bookactiGetRescheduleBookingSystemData', 'bookacti_controller_get_reschedule_booking_system_data' );
add_action( 'wp_ajax_nopriv_bookactiGetRescheduleBookingSystemData', 'bookacti_controller_get_reschedule_booking_system_data' );


/**
 * AJAX Controller - Reschedule a booking
 * @version 1.15.15
 */
function bookacti_controller_reschedule_booking() {
	$booking_id = intval( $_POST[ 'booking_id' ] );
	
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_reschedule_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'reschedule_booking' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'reschedule_booking' ); }
	
	$picked_events = ! empty( $_POST[ 'picked_events' ] ) ? bookacti_format_picked_events( $_POST[ 'picked_events' ] ) : array();
	if( ! $picked_events || ! empty( $picked_events[ 0 ][ 'group_id' ] ) || empty( $picked_events[ 0 ][ 'id' ] ) ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_event_selected', 'message' => esc_html__( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' ) ), 'reschedule_booking' );
	}
	
	$booking = bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'reschedule_booking' );
	}
	
	// Validate the reschedule booking form fields
	$form_fields_validated = bookacti_validate_reschedule_form_fields( $booking );
	if( $form_fields_validated[ 'status' ] !== 'success' ) {
		$return_array[ 'error' ]    = 'invalid_form_fields';
		$return_array[ 'messages' ] = $form_fields_validated[ 'messages' ];
		$return_array[ 'message' ]  = implode( '</li><li>', $form_fields_validated[ 'messages' ] );
		bookacti_send_json( $return_array, 'reschedule_booking' );
	}
	
	$reschedule_form_values = apply_filters( 'bookacti_reschedule_booking_form_values', array(
		'picked_events'      => $picked_events,
		'quantity'           => intval( $booking->quantity ),
		'form_id'            => ! empty( $booking->form_id ) && ! current_user_can( 'bookacti_edit_bookings' ) ? $booking->form_id : 0,
		'is_admin'           => ! empty( $_POST[ 'is_admin' ] ), 
		'context'            => 'reschedule', 
		'send_notifications' => ! empty( $_POST[ 'is_admin' ] ) && isset( $_POST[ 'send_notifications' ] ) ? intval( $_POST[ 'send_notifications' ] ) : 1
	), $booking );
	
	// Check if the desired event is eligible according to the current booking
	$can_be_rescheduled	= bookacti_booking_can_be_rescheduled_to( $booking, $reschedule_form_values[ 'picked_events' ][ 0 ][ 'id' ], $reschedule_form_values[ 'picked_events' ][ 0 ][ 'start' ], $reschedule_form_values[ 'picked_events' ][ 0 ][ 'end' ], $reschedule_form_values[ 'context' ] );
	if( $can_be_rescheduled[ 'status' ] !== 'success' ) {
		bookacti_send_json( $can_be_rescheduled, 'reschedule_booking' );
	}

	// Validate picked events
	$validated = bookacti_validate_picked_events( $reschedule_form_values[ 'picked_events' ], $reschedule_form_values );

	if( $validated[ 'status' ] !== 'success' ) {
		$messages = ! empty( $validated[ 'message' ] ) ? array( $validated[ 'message' ] ) : array();
		foreach( $validated[ 'messages' ] as $error => $error_messages ) {
			if( ! is_array( $error_messages ) ) { $error_messages = array( $error_messages ); }
			$messages = array_merge( $messages, $error_messages );
		}
		bookacti_send_json( array( 'status' => 'failed', 'error' => $validated[ 'error' ], 'message' => implode( '</li><li>', $messages ) ), 'reschedule_booking' );
	}
	
	// Let third party plugins change form values before rescheduling
	$reschedule_form_values = apply_filters( 'bookacti_reschedule_booking_form_values_before_rescheduling', $reschedule_form_values, $booking );
	
	// Let third party plugins do their stuff before rescheduling
	do_action( 'bookacti_reschedule_booking_form_before_rescheduling', $reschedule_form_values, $booking );
	
	$rescheduled = bookacti_reschedule_booking( $booking_id, $reschedule_form_values[ 'picked_events' ][ 0 ][ 'id' ], $reschedule_form_values[ 'picked_events' ][ 0 ][ 'start' ], $reschedule_form_values[ 'picked_events' ][ 0 ][ 'end' ] );

	if( $rescheduled === 0 ) {
		$message = esc_html__( 'You must select a different event than the current one.', 'booking-activities' );
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'same_event', 'message' => $message ), 'reschedule_booking' );
	}

	if( ! $rescheduled ) {
		bookacti_send_json( array( 'status' => 'failed' ), 'reschedule_booking' );
	}

	$new_booking = bookacti_get_booking_by_id( $booking_id, true );

	do_action( 'bookacti_booking_rescheduled', $new_booking, $booking, $reschedule_form_values );

	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns     = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $booking_id ), array( $new_booking ), 'reschedule_booking', $context );
	$row         = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'old_booking' => $booking, 'new_booking' => $new_booking, 'form_values' => $reschedule_form_values ), 'reschedule_booking' );
}
add_action( 'wp_ajax_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );
add_action( 'wp_ajax_nopriv_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );


/**
 * AJAX Controller - Delete a booking
 * @since 1.5.0
 * @version 1.15.5
 */
function bookacti_controller_delete_booking() {
	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_delete_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'delete_booking' ); }

	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking( $booking_id, false, array( 'bookacti_delete_bookings' ) );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'delete_booking' ); }

	do_action( 'bookacti_before_delete_booking', $booking_id );

	$deleted = bookacti_delete_booking( $booking_id );

	if( ! $deleted ) {
		$return_array = array( 
			'status'  => 'failed', 
			'error'   => 'not_deleted', 
			'message' => esc_html__( 'An error occurred while trying to delete the booking.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking' );
	}

	do_action( 'bookacti_booking_deleted', $booking_id );
	
	bookacti_send_json( array( 'status' => 'success' ), 'delete_booking' );
}
add_action( 'wp_ajax_bookactiDeleteBooking', 'bookacti_controller_delete_booking' );




// BOOKING GROUPS

/**
 * AJAX Controller - Get grouped bookings rows
 * @since 1.7.4 (was bookacti_controller_get_booking_rows)
 * @version 1.12.3
 */
function bookacti_controller_get_grouped_bookings_rows() {
	// Check nonce and capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_rows', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_grouped_bookings_rows' ); }
	
	$booking_group_id = intval( $_POST[ 'booking_group_id' ] );
	$is_allowed = bookacti_user_can_manage_booking_group( $booking_group_id, true, array( 'bookacti_manage_bookings', 'bookacti_edit_bookings' ) );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_grouped_bookings_rows' ); }

	$context = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$rows    = $booking_group_id ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id, 'group_by' => 'none' ), $columns ) : '';

	if( ! $rows ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_rows', 'message' => esc_html__( 'No bookings.', 'booking-activities' ) ), 'get_grouped_bookings_rows' ); }

	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows ), 'get_grouped_bookings_rows' );
}
add_action( 'wp_ajax_bookactiGetGroupedBookingsRows', 'bookacti_controller_get_grouped_bookings_rows' );


/**
 * Trigger bookacti_booking_state_changed for each bookings of the group when bookacti_booking_group_state_changed is called
 * @since 1.2.0
 * @version 1.12.0
 * @param int $booking_group_id
 * @param array $bookings
 * @param string $status
 * @param array $args
 */
function bookacti_trigger_booking_state_change_for_each_booking_of_a_group( $booking_group_id, $bookings, $status, $args ) {
	if( ! $status ) { return; }
	if( ! $bookings ) { $bookings = bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ); }
	$args[ 'booking_group_state_changed' ] = true;
	foreach( $bookings as $booking ) {
		do_action( 'bookacti_booking_state_changed', $booking, $status, $args );
	}
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_trigger_booking_state_change_for_each_booking_of_a_group', 10, 4 );


/**
 * AJAX Controller - Cancel a booking group
 * @since 1.1.0
 * @version 1.12.0
 */
function bookacti_controller_cancel_booking_group() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'cancel_booking_group' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking_group( $booking_group_id );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'cancel_booking_group' ); }

	$bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	if( ! $bookings ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'cancel_booking_group' );
	}
	
	if( $bookings && $bookings[ 0 ]->group_state === 'cancelled' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_group_already_cancelled', 'message' => esc_html__( 'The booking group is already cancelled.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	$can_be_cancelled = bookacti_booking_group_can_be_cancelled( $bookings, 'front' );
	if( ! $can_be_cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking_group', 'message' => esc_html__( 'The booking group cannot be cancelled.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	$cancelled = bookacti_update_booking_group_state( $booking_group_id, 'cancelled', 'auto', true );
	if( ! $cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking_group', 'message' => esc_html__( 'An error occurred while trying to cancel the booking group.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $bookings, 'cancelled', array( 'is_admin' => false ) );
	
	$new_bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	$context      = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns      = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters  = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), $new_bookings, 'cancel_booking_group', $context );
	$row          = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );
	$allow_refund = bookacti_booking_group_can_be_refunded( $new_bookings, false, 'front' );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'allow_refund' => $allow_refund ), 'cancel_booking_group' );
}
add_action( 'wp_ajax_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );
add_action( 'wp_ajax_nopriv_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );


/**
 * AJAX Controller - Get possible actions to refund a booking group
 * @since 1.1.0
 * @version 1.12.0
 */
function bookacti_controller_get_booking_group_refund_actions_html() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) { bookacti_send_json_invalid_nonce( 'get_refund_actions_html' ); }
	
	// Check capabilities
	if( ! bookacti_user_can_manage_booking_group( $booking_group_id ) ) { bookacti_send_json_not_allowed( 'get_refund_actions_html' ); }
	
	$bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	if( ! $bookings ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}
	
	$front_or_admin = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	if( ! bookacti_booking_group_can_be_refunded( $bookings, false, $front_or_admin ) ) {
		bookacti_send_json( array( 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}

	$refund_actions_array = bookacti_get_booking_refund_actions( $bookings, 'group', $front_or_admin );
	$refund_actions_html  = bookacti_get_booking_refund_options_html( $bookings, 'group', $refund_actions_array, $front_or_admin );
	$refund_amount        = bookacti_get_booking_refund_amount( $bookings, 'group' );
	
	bookacti_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array, 'amount' => $refund_amount ), 'get_refund_actions_html' );
}
add_action( 'wp_ajax_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );


/**
 * AJAX Controller - Refund a booking group
 * @since 1.1.0
 * @version 1.12.0
 */
function bookacti_controller_refund_booking_group() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );
	
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) { bookacti_send_json_invalid_nonce( 'refund_booking_group' ); }
	
	// Check capabilities
	if( ! bookacti_user_can_manage_booking_group( $booking_group_id ) ) { bookacti_send_json_not_allowed( 'refund_booking_group' ); }
	
	$bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	if( ! $bookings ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'refund_booking_group' );
	}
	
	$is_admin         = intval( $_POST[ 'is_admin' ] );
	$front_or_admin   = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	$sanitized_action = sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
	$refund_actions   = bookacti_get_booking_refund_actions( $bookings, 'group', $front_or_admin );
	$refund_action    = array_key_exists( $sanitized_action, $refund_actions ) ? $sanitized_action : 'email';

	if( ! bookacti_booking_group_can_be_refunded( $bookings, $refund_action, $front_or_admin ) ) {
		bookacti_send_json( array( 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'refund_booking_group' );
	}

	$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );

	if( $refund_action === 'email' ) {
		// The refund request notification is send by bookacti_send_notification_when_booking_group_state_changes() on the hook 'bookacti_booking_group_state_changed'
		$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested' );
	} else {
		$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $bookings, 'group', $refund_action, $refund_message, $front_or_admin );
	}

	if( $refunded[ 'status' ] !== 'success' ) {
		if( empty( $refunded[ 'message' ] ) ) { 
			$refunded[ 'message' ] = esc_html__( 'Error occurs while trying to request a refund. Please contact the administrator.', 'booking-activities' );
		}
		bookacti_send_json( $refunded, 'refund_booking_group' );
	}
	
	// Save the refund message
	if( $refund_message ) {
		bookacti_update_metadata( 'booking_group', $booking_group_id, array( 'refund_message' => $refund_message ) );
	}

	if( empty( $refunded[ 'do_not_update_status' ] ) ) {
		if( empty( $refunded[ 'new_state' ] ) )	{ $refunded[ 'new_state' ] = 'refunded'; }
		$updated = bookacti_update_booking_group_state( $booking_group_id, $refunded[ 'new_state' ], 'auto', true );

		// Hook status changes
		if( $updated ) {
			do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $bookings, $refunded[ 'new_state' ], array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
		}
	}
	
	$new_bookings      = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	$context           = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns           = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters       = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), $new_bookings, 'refund_booking_group', $context );
	$row               = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );
	$refunded[ 'row' ] = $row;

	// Get grouped booking rows if they are displayed and need to be refreshed
	$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;
	$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';
	$refunded[ 'grouped_booking_rows' ] = $rows;
	
	if( empty( $refunded[ 'message' ] ) ) { $refunded[ 'message' ] = esc_html__( 'Your booking has been successfully refunded.', 'booking-activities' ); }
	
	bookacti_send_json( $refunded, 'refund_booking_group' );
}
add_action( 'wp_ajax_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );
add_action( 'wp_ajax_nopriv_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );


/**
 * AJAX Controller - Change booking group state
 * @since 1.1.0
 * @version 1.14.0
 */
function bookacti_controller_change_booking_group_state() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );
	
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_not_allowed( 'change_booking_group_status' ); }

	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking_group( $booking_group_id, false );	
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_group_status' ); }

	// Get bookings
	$bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	if( ! $bookings ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'change_booking_group_status' );
	}
	
	$booking_state           = sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
	$payment_status          = sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
	$send_notifications      = $_POST[ 'send_notifications' ] ? 1 : 0;
	$is_admin                = intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;
	$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;

	$new_booking_state  = array_key_exists( $booking_state, bookacti_get_booking_state_labels() ) ? $booking_state : false;
	$new_payment_status = array_key_exists( $payment_status, bookacti_get_payment_status_labels() ) ? $payment_status : false;
	$active_changed     = false;
	
	// Change booking group states
	if( $new_booking_state && $bookings && $bookings[ 0 ]->group_state !== $new_booking_state ) {
		$state_can_be_changed = bookacti_booking_group_state_can_be_changed_to( $bookings, $new_booking_state, 'admin' );
		if( ! $state_can_be_changed ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_group_status', 'message' => esc_html__( 'The booking group status cannot be changed.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		$was_active	= $bookings[ 0 ]->group_active ? 1 : 0;
		$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		if( $active !== $was_active ) { $active_changed = true; }

		$updated = bookacti_update_booking_group_state( $booking_group_id, $new_booking_state, $active, true, $bookings[ 0 ]->group_state );
		if( ! $updated ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_status', 'message' => esc_html__( 'An error occurred while trying to change the booking group status.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $bookings, $new_booking_state, array( 'is_admin' => $is_admin, 'send_notifications' => $send_notifications ) );
	}

	// Change payment status
	if( $new_payment_status && $bookings && $bookings[ 0 ]->group_payment_status !== $new_payment_status ) {
		$updated = bookacti_update_booking_group_payment_status( $booking_group_id, $new_payment_status, true, $bookings[ 0 ]->group_payment_status );
		if( ! $updated ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_payment_status', 'message' => esc_html__( 'An error occurred while trying to change the booking group payment status.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		do_action( 'bookacti_booking_group_payment_status_changed', $booking_group_id, $new_payment_status, array( 'is_admin' => $is_admin ) );
	}

	$new_bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	$context      = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns      = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters  = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), $new_bookings, 'change_booking_group_status', $context );
	$row          = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );
	
	// Get grouped booking rows if they are displayed and need to be refreshed
	$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows, 'active_changed' => $active_changed ), 'change_booking_group_status' );
}
add_action( 'wp_ajax_bookactiChangeBookingGroupState', 'bookacti_controller_change_booking_group_state' );


/**
 * AJAX Controller - Change booking group quantity
 * @since 1.7.10
 * @version 1.15.5
 */
function bookacti_controller_change_booking_group_quantity() {
	$booking_group_id        = intval( $_POST[ 'booking_id' ] );
	$new_quantity            = intval( $_POST[ 'new_quantity' ] );
	$is_admin                = intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;
	$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_quantity', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'change_booking_group_quantity' ); }
	
	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking_group( $booking_group_id, false );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_group_quantity' ); }

	// Check if the quantity is valid
	if( $new_quantity < 1 ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_new_quantity', 'message' => esc_html__( 'The new quantity is not valid.', 'booking-activities' ) ), 'change_booking_group_quantity' );
	}
	
	$booking_group = bookacti_get_booking_group_by_id( $booking_group_id, true );
	if( ! $booking_group ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking ID.', 'booking-activities' ) ), 'change_booking_group_quantity' );
	}
	
	// Update booking quantity
	$updated = bookacti_force_update_booking_group_bookings_quantity( $booking_group_id, $new_quantity );
	if( $updated === false ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_quantity', 'message' => esc_html__( 'An error occurred while trying to change the booking quantity.', 'booking-activities' ) ), 'change_booking_group_quantity' );
	}

	do_action( 'bookacti_booking_group_quantity_updated', $booking_group_id, $new_quantity, $booking_group->quantity, array( 'is_admin' => $is_admin ) );

	$new_bookings = array_values( bookacti_get_booking_group_bookings_by_id( $booking_group_id, true ) );
	$context      = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns      = ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$row_filters  = apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), $new_bookings, 'change_booking_group_quantity', $context );
	$row          = bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns );

	// Get grouped booking rows if they are displayed and need to be refreshed
	$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows ), 'change_booking_group_quantity' );
}
add_action( 'wp_ajax_bookactiChangeBookingGroupQuantity', 'bookacti_controller_change_booking_group_quantity' );


/**
 * AJAX Controller - Delete a booking group
 * @since 1.5.0
 * @version 1.15.5
 */
function bookacti_controller_delete_booking_group() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_delete_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'delete_booking_group' ); }

	// Check capabilities
	$is_allowed = bookacti_user_can_manage_booking_group( $booking_group_id, false, array( 'bookacti_delete_bookings' ) );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'delete_booking_group' ); }

	do_action( 'bookacti_before_delete_booking_group', $booking_group_id );
	
	$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );
	
	if( $booking_ids ) {
		foreach( $booking_ids as $booking_id ) {
			do_action( 'bookacti_before_delete_booking', $booking_id );
		}
	}
	
	$bookings_deleted = bookacti_delete_booking_group_bookings( $booking_group_id );

	if( $bookings_deleted === false ) {
		$return_array = array( 
			'status'  => 'failed', 
			'error'   => 'grouped_bookings_not_deleted', 
			'message' => esc_html__( 'An error occurred while trying to delete the bookings of the group.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking_group' );
	}
	
	if( $bookings_deleted && $booking_ids ) {
		foreach( $booking_ids as $booking_id ) {
			do_action( 'bookacti_booking_deleted', $booking_id );
		}
	}
	
	$group_deleted = bookacti_delete_booking_group( $booking_group_id );

	if( ! $group_deleted ) {
		$return_array = array( 
			'status'  => 'failed', 
			'error'   => 'not_deleted', 
			'message' => esc_html__( 'An error occurred while trying to delete the booking group.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking_group' );
	}

	do_action( 'bookacti_booking_group_deleted', $booking_group_id );
	
	bookacti_send_json( array( 'status' => 'success' ), 'delete_booking_group' );
}
add_action( 'wp_ajax_bookactiDeleteBookingGroup', 'bookacti_controller_delete_booking_group' );




// EXPORT

/**
 * Register a daily cron event to clean expired exports
 * @since 1.8.0
 */
function bookacti_register_cron_event_to_clean_expired_exports() {
	if( ! wp_next_scheduled ( 'bookacti_clean_expired_exports' ) ) {
		wp_schedule_event( time(), 'daily', 'bookacti_clean_expired_exports' );
	}
}
add_action( 'bookacti_activate', 'bookacti_register_cron_event_to_clean_expired_exports' );


/**
 * Deregister the daily cron event to clean expired exports
 * @since 1.8.0
 */
function bookacti_deregister_cron_event_to_clean_expired_exports() {
	wp_clear_scheduled_hook( 'bookacti_clean_expired_exports' );
}
add_action( 'bookacti_deactivate', 'bookacti_deregister_cron_event_to_clean_expired_exports' );


/**
 * Clean expired exports
 * @since 1.8.0
 */
function bookacti_clean_expired_exports() {
	bookacti_delete_exports( array( 'expiration_delay' => 0 ) );
}
add_action( 'bookacti_clean_expired_exports', 'bookacti_clean_expired_exports' );


/**
 * Generate the export bookings URL according to current filters and export settings
 * @since 1.6.0
 * @version 1.15.5
 */
function bookacti_controller_generate_export_bookings_url() {
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_export_bookings_url', 'nonce', false ) ) { bookacti_send_json_invalid_nonce( 'export_bookings_url' ); }

	// Check capabilities
	if( ! current_user_can( 'bookacti_manage_bookings' ) ) { bookacti_send_json_not_allowed( 'export_bookings_url' ); }
	
	$message = esc_html__( 'The link has been correctly generated. Use the link above to export your bookings.', 'booking-activities' );
	
	// Get or generate current user export secret key
	$current_user_id = get_current_user_id();
	$secret_key = get_user_meta( $current_user_id, 'bookacti_secret_key', true );
	if( ( ! $secret_key || ! empty( $_POST[ 'reset_key' ] ) ) && ( $current_user_id && is_numeric( $current_user_id ) ) ) {
		// Update secret key
		$secret_key = md5( microtime().rand() );
		update_user_meta( $current_user_id, 'bookacti_secret_key', $secret_key );
		
		// Remove existing exports
		bookacti_delete_exports( array( 'user_ids' => array( $current_user_id ) ) );
		
		// Feedback user
		if( ! empty( $_POST[ 'reset_key' ] ) ) {
			$message .= '<br/><em>' . esc_html__( 'Your secret key has been changed. The old links that you have generated won\'t work anymore.', 'booking-activities' ) . '</em>';
		}
	}

	// Get formatted booking filters
	$booking_filters_raw = ! empty( $_POST[ 'booking_filters' ] ) ? $_POST[ 'booking_filters' ] : array();
	if( isset( $booking_filters_raw[ 'templates' ][ 0 ] ) && $booking_filters_raw[ 'templates' ][ 0 ] === 'all' ) {
		unset( $booking_filters_raw[ 'templates' ] );
	}
	
	// Picked events
	$picked_events = ! empty( $_POST[ 'booking_filters' ][ 'selected_events' ] ) ? bookacti_format_picked_events( $_POST[ 'booking_filters' ][ 'selected_events' ] ) : array();
	if( ! empty( $picked_events[ 0 ][ 'group_id' ] ) )  { $booking_filters_raw[ 'event_group_id' ] = $picked_events[ 0 ][ 'group_id' ]; }
	else {
		if( ! empty( $picked_events[ 0 ][ 'id' ] ) )    { $booking_filters_raw[ 'event_id' ]    = $picked_events[ 0 ][ 'id' ]; }
		if( ! empty( $picked_events[ 0 ][ 'start' ] ) ) { $booking_filters_raw[ 'event_start' ] = $picked_events[ 0 ][ 'start' ]; }
		if( ! empty( $picked_events[ 0 ][ 'end' ] ) )   { $booking_filters_raw[ 'event_end' ]   = $picked_events[ 0 ][ 'end' ]; }
	}

	$default_fitlers = bookacti_get_default_booking_filters();
	$booking_filters = bookacti_format_booking_filters( $booking_filters_raw );
	
	$default_settings = bookacti_get_bookings_export_default_settings();
	$export_settings  = bookacti_sanitize_bookings_export_settings( $_POST );
	
	// Keep only the required data to keep the URL as short as possible
	foreach( $booking_filters as $filter_name => $filter_value ) {
		if( is_numeric( $filter_value ) && is_string( $filter_value ) ) { 
			$filter_value = is_float( $filter_value + 0 ) ? floatval( $filter_value ) : intval( $filter_value );
		}
		if( $default_fitlers[ $filter_name ] === $filter_value ) {
			unset( $booking_filters[ $filter_name ] );
		}
	}
	
	$export_type = sanitize_title_with_dashes( $_POST[ 'export_type' ] );
	
	// Additional URL attributes
	$add_url_atts = array(
		'action'      => 'bookacti_export_bookings',
		'export_type' => $export_type,
		'filename'    => '',
		'key'         => $secret_key ? $secret_key : '',
		'locale'      => bookacti_get_current_lang_code( true ),
		'per_page'    => $export_settings[ 'per_page' ],
		'short_url'   => 1
	);
	
	// Add CSV specific args
	if( $export_type === 'csv' ) {
		$booking_filters[ 'group_by' ] = $export_settings[ 'csv_export_groups' ] === 'groups' ? 'booking_group' : 'none';
		$booking_filters[ 'raw' ]      = $export_settings[ 'csv_raw' ];
		if( array_diff_assoc( array_values( $default_settings[ 'csv_columns' ] ), $export_settings[ 'csv_columns' ] ) ) {
			$add_url_atts[ 'columns' ] = $export_settings[ 'csv_columns' ];
		}
	}
	
	// Add iCal specific args
	else if( $export_type === 'ical' ) {
		$add_url_atts[ 'vevent_summary' ]      = urlencode( utf8_encode( trim( $export_settings[ 'vevent_summary' ] ) ) );
		$add_url_atts[ 'vevent_description' ]  = urlencode( utf8_encode( str_replace( array( PHP_EOL, '\n' ), '%0A', trim( $export_settings[ 'vevent_description' ] ) ) ) );
		$add_url_atts[ 'booking_list_header' ] = $export_settings[ 'ical_booking_list_header' ];
		$booking_filters[ 'raw' ]              = $export_settings[ 'ical_raw' ];
		if( array_diff_assoc( array_values( $default_settings[ 'ical_columns' ] ), $export_settings[ 'ical_columns' ] ) ) {
			$add_url_atts[ 'columns' ] = $export_settings[ 'ical_columns' ];
		}
	}
	
	// Let third party plugins change the URL attributes
	$url_atts = apply_filters( 'bookacti_export_bookings_url_attributes', array_merge( $add_url_atts, $booking_filters ), $export_type, $export_settings, array_merge( $default_fitlers, $default_settings ) );
	
	$export_url = '';
	
	// Add the URL attributes
	if( $url_atts )	{ 
		$home_url = home_url();
		// Short URL
		if( ! empty( $url_atts[ 'short_url' ] ) ) {
			$export_id = bookacti_insert_export( array( 'type' => 'booking_' . $export_type, 'user_id' => $current_user_id, 'args' => $url_atts ) );
			$short_url_atts = array(
				'action'    => $url_atts[ 'action' ],
				'key'       => $url_atts[ 'key' ],
				'export_id' => $export_id
			);
			$export_url = add_query_arg( $short_url_atts, $home_url );
		} 
		// Long URL
		else {
			if( isset( $url_atts[ 'short_url' ] ) ) { unset( $url_atts[ 'short_url' ] ); }
			$export_url = add_query_arg( $url_atts, $home_url );
		}
	}
	
	// Update settings
	update_user_meta( $current_user_id, 'bookacti_bookings_export_settings', $export_settings );
	
	bookacti_send_json( array( 'status' => 'success', 'url' => esc_url_raw( $export_url ), 'message' => $message ), 'export_bookings_url' ); 
}
add_action( 'wp_ajax_bookactiExportBookingsUrl', 'bookacti_controller_generate_export_bookings_url' );


/**
 * Export bookings according to filters
 * @since 1.6.0
 * @version 1.15.6
 */
function bookacti_export_bookings_page() {
	if( empty( $_REQUEST[ 'action' ] ) ) { return; }
	if( $_REQUEST[ 'action' ] !== 'bookacti_export_bookings' ) { return; }
	
	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'key' ] ) : '';
	if( ! $key ) { esc_html_e( 'Missing secret key.', 'booking-activities' ); exit; }
	
	// Check if the user exists
	$user_id = bookacti_get_user_id_by_secret_key( $key );
	if( ! $user_id ) { esc_html_e( 'Invalid secret key.', 'booking-activities' ); exit; }
	
	// Check if the export URL has been shortened
	$args = $_REQUEST;
	$export_id = ! empty( $_REQUEST[ 'export_id' ] ) ? intval( $_REQUEST[ 'export_id' ] ) : 0;
	if( $export_id ) {
		$export = bookacti_get_export( $export_id );
		
		if( ! $export ) { esc_html_e( 'Invalid or expired export ID.', 'booking-activities' ); exit; }
		if( intval( $export[ 'user_id' ] ) !== intval( $user_id ) ) { esc_html_e( 'The secret key doesn\'t match the user who has generated the export.', 'booking-activities' ); exit; }
		
		$args = array_merge( $export[ 'args' ], $_REQUEST );
		$args[ 'sequence' ] = $export[ 'sequence' ];
		if( empty( $args[ 'filename' ] ) ) { $args[ 'filename' ] = 'booking-activities-bookings-' . $export_id; }
	}
	
	// Check the export type
	$export_type = sanitize_title_with_dashes( $args[ 'export_type' ] );
	if( ! in_array( $export_type, array( 'csv', 'ical' ), true ) ) { esc_html_e( 'Invalid export type.', 'booking-activities' ); exit; }
	
	// Check the filename
	$filename = ! empty( $args[ 'filename' ] ) ? sanitize_title_with_dashes( $args[ 'filename' ] ) : 'booking-activities-bookings';
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
		 if( $export_type === 'csv' && substr( $filename, -4 ) !== '.csv' ) { $filename .= '.csv'; }
	else if( $export_type === 'ical' && substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }
	
	// Format the booking filters
	$formatted_args = bookacti_format_string_booking_filters( $args );
	if( isset( $formatted_args[ 'templates' ] ) ) { unset( $formatted_args[ 'templates' ] ); } // Restrict to allowed templates later
	$filters = bookacti_format_booking_filters( $formatted_args );
	
	// Check if the user can export bookings
	$is_allowed = user_can( $user_id, 'bookacti_manage_bookings' );
	$is_own = intval( $filters[ 'user_id' ] ) === $user_id || ( count( $filters[ 'in__user_id' ] ) === 1 && intval( $filters[ 'in__user_id' ][ 0 ] ) === $user_id );
	if( ! $is_allowed && ! $is_own ) { esc_html_e( 'You are not allowed to do that.', 'booking-activities' ); exit; }
	$filters[ 'display_private_columns' ] = 1;
	
	// If an event has been selected, do not retrieve groups of events containing this event
	if( $filters[ 'event_id' ] && ! $filters[ 'booking_group_id' ] ) { $filters[ 'booking_group_id' ] = 'none'; }
	
	// Restrict to allowed templates
	$allowed_templates = array_keys( bookacti_fetch_templates( array(), $user_id ) );
	$filters[ 'templates' ] = empty( $args[ 'templates' ] ) ? $allowed_templates : array_intersect( $allowed_templates, $args[ 'templates' ] );
	
	// Let third party plugins change the booking filters and the file headers
	$filters = apply_filters( 'bookacti_export_bookings_filters', $filters, $export_type );
	$headers = apply_filters( 'bookacti_export_bookings_headers', array(
		'Content-type'        => $export_type === 'ical' ? 'text/calendar' : 'text/csv',
		'charset'             => 'utf-8',
		'Content-Disposition' => 'attachment',
		'filename'            => $filename,
		'Cache-Control'       => 'no-cache, must-revalidate',  // HTTP/1.1
		'Expires'             => 'Sat, 26 Dec 1992 00:50:00 GMT'  // Expired date to force third-party apps to refresh soon
	), $export_type );
	
	// Get the user export settings (to use as defaults)
	$user_settings = bookacti_get_bookings_export_settings( $user_id );
	
	// Format the booking list columns
	$columns = ! empty( $args[ 'columns' ] ) && is_array( $args[ 'columns' ] ) ? $args[ 'columns' ] : ( ! empty( $user_settings[ $export_type . '_columns' ] ) ? $user_settings[ $export_type . '_columns' ] : array() );
	
	// Temporarily switch locale to the desired one or user default's
	$locale = ! empty( $args[ 'locale' ] ) ? sanitize_text_field( $args[ 'locale' ] ) : bookacti_get_user_locale( $user_id, 'site' );
	$lang_switched = bookacti_switch_locale( $locale );
	
	header( 'Content-type: ' . $headers[ 'Content-type' ] . '; charset=' . $headers[ 'charset' ] );
	header( 'Content-Disposition: ' . $headers[ 'Content-Disposition' ] . '; filename=' . $headers[ 'filename' ] );
	header( 'Cache-Control: ' . $headers[ 'Cache-Control' ] );
	header( 'Expires: ' . $headers[ 'Expires' ] );
	
	// Generate export according to type
	if( $export_type === 'csv' ) { 
		$csv_args = apply_filters( 'bookacti_export_bookings_csv_args', array(
			'columns' => $columns,
			'raw'     => ! empty( $args[ 'raw' ] ) ? 1 : 0,
			'locale'  => $locale
		) );
		echo bookacti_convert_bookings_to_csv( $filters, $csv_args );
	
	} else if( $export_type === 'ical' ) { 
		$ical_args = apply_filters( 'bookacti_export_bookings_ical_args', array( 
			'vevent_summary'               => isset( $args[ 'vevent_summary' ] ) ? utf8_decode( urldecode( trim( $args[ 'vevent_summary' ] ) ) ) : $user_settings[ 'vevent_summary' ],
			'vevent_description'           => isset( $args[ 'vevent_description' ] ) ? utf8_decode( urldecode( str_replace( array( '%0A', '%250A' ), '\n', trim( $args[ 'vevent_description' ] ) ) ) ) : $user_settings[ 'vevent_description' ],
			'tooltip_booking_list_columns' => $columns,
			'booking_list_header'          => ! empty( $args[ 'booking_list_header' ] ) ? 1 : 0,
			'raw'                          => ! empty( $args[ 'raw' ] ) ? 1 : 0,
			'sequence'                     => ! empty( $args[ 'sequence' ] ) ? intval( $args[ 'sequence' ] ) : 0,
			'locale'                       => $locale
		) );
		echo bookacti_convert_bookings_to_ical( $filters, $ical_args );
	}
	
	// Switch locale back to normal
	if( $lang_switched ) { bookacti_restore_locale(); }
	
	// Increment the expiry date and sequence
	if( $export_id ) { bookacti_update_export( $export_id ); }
	
	exit;
}
add_action( 'init', 'bookacti_export_bookings_page', 10 );


/**
 * Export a user's bookings events as iCal
 * @since 1.12.0 (was bookacti_export_user_booked_events_page)
 * @version 1.15.6
 */
function bookacti_export_user_bookings_events_page() {
	if( empty( $_REQUEST[ 'action' ] ) ) { return; }
	if( $_REQUEST[ 'action' ] !== 'bookacti_export_user_booked_events' ) { return; }

	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'key' ] ) : '';
	if( ! $key ) { esc_html_e( 'Missing secret key.', 'booking-activities' ); exit; }

	// Check if the user exists
	$user_id = bookacti_get_user_id_by_secret_key( $key );
	if( ! $user_id ) { esc_html_e( 'Invalid secret key.', 'booking-activities' ); exit; }
	
	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : 'my-bookings';
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
	$atts[ 'filename' ] = $filename;
	if( substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }

	// Increment the sequence number each time to make sure that the events will be updated
	$sequence = intval( get_user_meta( $user_id, 'bookacti_ical_sequence', true ) ) + 1;
	update_user_meta( $user_id, 'bookacti_ical_sequence', $sequence );
	
	// Format the booking filters
	$additional_url_args = bookacti_format_string_booking_filters( $_GET );
	if( isset( $additional_url_args[ 'templates' ] ) ) { unset( $additional_url_args[ 'templates' ] ); }
	$filters = array_merge( array( 'active' => 1, 'per_page' => 200 ), $additional_url_args, array( 'user_id' => $user_id ) );
	$filters = apply_filters( 'bookacti_export_user_bookings_events_filters', bookacti_format_booking_filters( $filters ), $user_id );
	
	// Temporarily switch locale to the desired one or user default's
	$locale = ! empty( $_REQUEST[ 'locale' ] ) ? sanitize_text_field( $_REQUEST[ 'locale' ] ) : bookacti_get_user_locale( $user_id, 'site' );
	$lang_switched = bookacti_switch_locale( $locale );
	
	$ical_args = apply_filters( 'bookacti_export_user_bookings_events_ical_args', array( 
		'vevent_summary'               => '{event_title}',
		'vevent_description'           => '',
		'tooltip_booking_list_columns' => array(),
		'booking_list_header'          => 0,
		'raw'                          => 0,
		'sequence'                     => $sequence,
		'locale'                       => $locale
	) );
	
	header( 'Content-type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
	header( 'Expires: Sat, 26 Dec 1992 00:50:00 GMT' ); // Expired date to force third-party calendars to refresh soon
	
	echo bookacti_convert_bookings_to_ical( $filters, $ical_args );

	// Switch locale back to normal
	if( $lang_switched ) { bookacti_restore_locale(); }
	
	exit;
}
add_action( 'init', 'bookacti_export_user_bookings_events_page', 10 );


/**
 * Export a booking (group) event(s) as iCal
 * @since 1.12.0 (was bookacti_export_booked_events_page)
 * @version 1.15.6
 */
function bookacti_export_booking_events_page() {
	if( empty( $_REQUEST[ 'action' ] ) ) { return; }
	if( $_REQUEST[ 'action' ] !== 'bookacti_export_booked_events' ) { return; }

	// Sanitize the booking ID
	$booking_type = ! empty( $_REQUEST[ 'booking_group_id' ] ) ? 'group' : 'single';
	$booking_id   = $booking_type === 'group' ? intval( $_REQUEST[ 'booking_group_id' ] ) : ( ! empty( $_REQUEST[ 'booking_id' ] ) ? intval( $_REQUEST[ 'booking_id' ] ) : 0 );
	if( ! $booking_id ) { esc_html_e( 'Invalid booking ID.', 'booking-activities' ); exit; }
	
	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'key' ] ) : '';
	if( ! $key ) { esc_html_e( 'Missing secret key.', 'booking-activities' ); exit; }
	
	// Check if the secret key is valid for the desired booking
	$is_valid = bookacti_is_booking_secret_key_valid( $key, $booking_id, $booking_type );
	if( ! $is_valid ) { esc_html_e( 'Invalid secret key.', 'booking-activities' ); exit; }
	
	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : 'my-bookings';
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
	if( substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }

	$filters = $booking_type === 'group' ? array( 'booking_group_id' => $booking_id, 'active' => 1 ) : array( 'booking_id' => $booking_id, 'active' => 1 );
	$filters = apply_filters( 'bookacti_export_booking_events_filters', bookacti_format_booking_filters( $filters ), $booking_id, $booking_type );
	
	// Temporarily switch locale to the desired one or site default's
	$locale = ! empty( $_REQUEST[ 'locale' ] ) ? sanitize_text_field( $_REQUEST[ 'locale' ] ) : bookacti_get_site_locale();
	$lang_switched = bookacti_switch_locale( $locale );
	
	$ical_args = apply_filters( 'bookacti_export_booking_events_ical_args', array( 
		'vevent_summary'               => '{event_title}',
		'vevent_description'           => '',
		'tooltip_booking_list_columns' => array(),
		'booking_list_header'          => 0,
		'raw'                          => 0,
		'sequence'                     => ! empty( $_REQUEST[ 'sequence' ] ) ? intval( $_REQUEST[ 'sequence' ] ) : 0,
		'locale'                       => $locale
	) );
	
	header( 'Content-type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
	header( 'Expires: Sat, 26 Dec 1992 00:50:00 GMT' ); // Expired date to force third-party calendars to refresh soon
	
	echo bookacti_convert_bookings_to_ical( $filters, $ical_args );
	
	// Switch locale back to normal
	if( $lang_switched ) { bookacti_restore_locale(); }
	
	exit;
}
add_action( 'init', 'bookacti_export_booking_events_page', 10 );