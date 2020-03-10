<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS CALENDAR
/**
 * AJAX Controller - Update bookings page calendar settings
 * @since 1.8.0
 */
function bookacti_controller_update_bookings_calendar_settings() {
	// Check nonce and capabilities
	$is_allowed = current_user_can( 'bookacti_manage_bookings' );
	$is_nonce_valid = check_ajax_referer( 'bookacti_update_bookings_calendar_settings', 'nonce_update_bookings_calendar_settings', false );
	if( ! $is_nonce_valid || ! $is_allowed ) { bookacti_send_json_not_allowed( 'update_bookings_calendar_settings' ); }
	
	$calendar_settings = bookacti_format_bookings_calendar_settings( $_POST );
	if( ! $calendar_settings ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_fields' ), 'update_bookings_calendar_settings' ); }
	
	$updated = update_user_meta( get_current_user_id(), 'bookacti_bookings_calendar_settings', $calendar_settings );
	if( ! $updated ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ), 'update_bookings_calendar_settings' ); }
	
	do_action( 'bookacti_bookings_calendar_settings_updated', $calendar_settings );
	
	// Isolate display data
	$default_display_data = bookacti_get_booking_system_default_display_data();
	$display_data = array_intersect_key( $calendar_settings, $default_display_data );
	
	bookacti_send_json( array( 'status' => 'success', 'calendar_settings' => $calendar_settings, 'display_data' => $display_data ), 'update_bookings_calendar_settings' );
}
add_action( 'wp_ajax_bookactiUpdateBookingsCalendarSettings', 'bookacti_controller_update_bookings_calendar_settings' );




// BOOKING ACTIONS

// SINGLE BOOKING
/**
 * AJAX Controller - Cancel a booking
 * @version 1.7.14
 */
function bookacti_controller_cancel_booking() {
	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce, capabilities and other params
	$is_nonce_valid	= check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
	$is_allowed		= bookacti_user_can_manage_booking( $booking_id );
	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'cancel_booking' );
	}

	$booking = bookacti_get_booking_by_id( $booking_id );
	if( $booking->state === 'cancelled' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_already_cancelled', 'message' => esc_html__( 'The booking is already cancelled.', 'booking-activities' ) ), 'cancel_booking' );
	}

	$can_be_cancelled = bookacti_booking_can_be_cancelled( $booking, false, 'front' );
	if( ! $can_be_cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking', 'message' => esc_html__( 'The booking cannot be cancelled.', 'booking-activities' ) ), 'cancel_booking' );
	}

	$cancelled = bookacti_cancel_booking( $booking_id );
	if( ! $cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking', 'message' => esc_html__( 'An error occured while trying to cancel the booking.', 'booking-activities' ) ), 'cancel_booking' );
	}

	do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => false ) );

	$new_booking	= bookacti_get_booking_by_id( $booking_id );
	$allow_refund	= bookacti_booking_can_be_refunded( $new_booking, false, 'front' );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $new_booking->id ), $new_booking, 'cancel_booking', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'allow_refund' => $allow_refund ), 'cancel_booking' );
}
add_action( 'wp_ajax_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );
add_action( 'wp_ajax_nopriv_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );


/**
 * AJAX Controller - Get possible actions to refund a booking
 * @version 1.7.14
 */
function bookacti_controller_get_refund_actions_html() {

	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce, capabilities and other params
	if( ! check_ajax_referer( 'bookacti_get_refund_actions_html', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'get_refund_actions_html' );
	}

	if( ! bookacti_user_can_manage_booking( $booking_id ) ) {
		bookacti_send_json_not_allowed( 'get_refund_actions_html' );
	}

	$front_or_admin = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	if( ! bookacti_booking_can_be_refunded( $booking_id, false, $front_or_admin ) ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}

	$refund_actions_array	= bookacti_get_refund_actions_by_booking_id( $booking_id, $front_or_admin );
	$refund_actions_html	= bookacti_get_refund_dialog_html_by_booking_id( $booking_id, $front_or_admin );

	if( ! empty( $refund_actions_html ) ) {
		bookacti_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array ), 'get_refund_actions_html' );

	} else {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_retrieve_actions_html' ), 'get_refund_actions_html' );
	}
}
add_action( 'wp_ajax_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );


/**
 * AJAX Controller - Refund a booking
 * @version 1.7.14
 */
function bookacti_controller_refund_booking() {
	$booking_id			= intval( $_POST[ 'booking_id' ] );
	$is_admin			= intval( $_POST[ 'is_admin' ] );
	$front_or_admin		= ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	$sanitized_action	= sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
	$refund_action		= array_key_exists( $sanitized_action, bookacti_get_refund_actions_by_booking_id( $booking_id, $front_or_admin ) ) ? $sanitized_action : 'email';

	// Check nonce, capabilities and other params
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'refund_booking' );
	}

	if( ! bookacti_user_can_manage_booking( $booking_id ) ) {
		bookacti_send_json_not_allowed( 'refund_booking' );
	}

	if( ! bookacti_booking_can_be_refunded( $booking_id, $refund_action, $front_or_admin ) ) {
		bookacti_send_json( 'refund_booking' );
	}

	$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );

	if( $refund_action === 'email' ) {
		$refunded = bookacti_send_email_refund_request( $booking_id, 'single', $refund_message );
		if( $refunded ) {
			$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested' );
		} else {
			$refunded = array( 
				'status'	=> 'failed', 
				'error'		=> 'cannot_send_email', 
				'message'	=> esc_html__( 'An error occured while trying to send the email.', 'booking-activities' ) 
			);
		}
	} else {
		$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $booking_id, 'single', $refund_action, $refund_message, $front_or_admin );
	}

	if( $refunded[ 'status' ] === 'success' ) {
		$new_state = ! empty( $refunded[ 'new_state' ] ) ? $refunded[ 'new_state' ] : 'refunded';
		$updated = bookacti_update_booking_state( $booking_id, $new_state );

		// Hook status changes
		if( $updated ) {
			do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
		}

		$new_booking = bookacti_get_booking_by_id( $booking_id );

		$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
		$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
		$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $new_booking->id ), $new_booking, 'refund_booking', $context );
		$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

		$refunded[ 'row' ] = $row;
	}

	bookacti_send_json( $refunded, 'refund_booking' );
}
add_action( 'wp_ajax_bookactiRefundBooking', 'bookacti_controller_refund_booking' );
add_action( 'wp_ajax_nopriv_bookactiRefundBooking', 'bookacti_controller_refund_booking' );


/**
 * AJAX Controller - Change booking state
 * @version 1.7.14
 */
function bookacti_controller_change_booking_state() {
	$booking_id			= intval( $_POST[ 'booking_id' ] );
	$booking_state		= sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
	$payment_status		= sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
	$send_notifications	= $_POST[ 'send_notifications' ] ? 1 : 0;
	$is_admin			= intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;

	$new_booking_state	= array_key_exists( $booking_state, bookacti_get_booking_state_labels() ) ? $booking_state : false;
	$new_payment_status	= array_key_exists( $payment_status, bookacti_get_payment_status_labels() ) ? $payment_status : false;
	$active_changed		= false;

	// Check nonce, capabilities and other params
	$is_nonce_valid			= check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
	$is_allowed				= current_user_can( 'bookacti_edit_bookings' );		

	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'change_booking_status' );
	}

	$booking = bookacti_get_booking_by_id( $booking_id );

	// Change booking state
	if( $new_booking_state && $booking->state !== $new_booking_state ) {
		$state_can_be_changed = bookacti_booking_state_can_be_changed_to( $booking, $new_booking_state, 'admin' );
		if( ! $state_can_be_changed ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_status', 'message' => esc_html__( 'The booking status cannot be changed.', 'booking-activities' ) ), 'change_booking_status' );
		}

		$was_active	= bookacti_is_booking_active( $booking_id ) ? 1 : 0;
		$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		if( $active !== $was_active ) { $active_changed = true; }

		$updated = bookacti_update_booking_state( $booking_id, $new_booking_state, $active );
		if( $updated === false ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_status', 'message' => esc_html__( 'An error occured while trying to change the booking status.', 'booking-activities' ) ), 'change_booking_status' );
		}

		do_action( 'bookacti_booking_state_changed', $booking_id, $new_booking_state, array( 'is_admin' => $is_admin, 'active' => $active, 'send_notifications' => $send_notifications ) );
	}

	// Change payment status
	if( $new_payment_status && $booking->payment_status !== $new_payment_status ) {
		$updated = bookacti_update_booking_payment_status( $booking_id, $new_payment_status );
		if( $updated === false ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_payment_status', 'message' => esc_html__( 'An error occured while trying to change the booking payment status.', 'booking-activities' ) ), 'change_booking_status' );
		}

		do_action( 'bookacti_booking_payment_status_changed', $booking_id, $new_payment_status, array( 'is_admin' => $is_admin ) );
	}

	$new_booking = bookacti_get_booking_by_id( $booking_id );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $new_booking->id ), $new_booking, 'change_booking_status', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'active_changed' => $active_changed ), 'change_booking_status' );
}
add_action( 'wp_ajax_bookactiChangeBookingState', 'bookacti_controller_change_booking_state' );


/**
 * AJAX Controller - Change booking quantity
 * @since 1.7.10
 */
function bookacti_controller_change_booking_quantity() {
	$booking_id		= intval( $_POST[ 'booking_id' ] );
	$new_quantity	= intval( $_POST[ 'new_quantity' ] );
	$is_admin		= intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;

	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_change_booking_quantity', 'nonce_change_booking_quantity', false );
	$is_allowed		= current_user_can( 'bookacti_edit_bookings' );		

	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'change_booking_quantity' );
	}

	// Check if the quantity is valid
	if( ! $new_quantity ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_new_quantity', 'message' => esc_html__( 'The new quantity is not valid.', 'booking-activities' ) ), 'change_booking_quantity' );
	}

	$old_booking = bookacti_get_booking_by_id( $booking_id );

	// Update booking quantity
	$updated = bookacti_force_update_booking_quantity( $booking_id, $new_quantity );
	if( $updated === false ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_quantity', 'message' => esc_html__( 'An error occured while trying to change the booking quantity.', 'booking-activities' ) ), 'change_booking_quantity' );
	}

	do_action( 'bookacti_booking_quantity_updated', $booking_id, $new_quantity, $old_booking->quantity, array( 'is_admin' => $is_admin ) );

	$new_booking = bookacti_get_booking_by_id( $booking_id );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $new_booking->id ), $new_booking, 'change_booking_quantity', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row ), 'change_booking_quantity' );
}
add_action( 'wp_ajax_bookactiChangeBookingQuantity', 'bookacti_controller_change_booking_quantity' );


/**
 * AJAX Controller - Get booking system data by booking ID
 * @version 1.7.18
 */
function bookacti_controller_get_booking_data() {
	// Check nonce, no need to check capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_data', 'nonce', false );

	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_booking_data' ); }

	$booking_id	= intval( $_POST[ 'booking_id' ] );
	$booking_data = bookacti_get_booking_data( $booking_id );

	if( ! empty( $booking_data ) && is_array( $booking_data ) ) {
		$calendar_field_data = ! empty( $booking_data[ 'form_id' ] ) ? bookacti_get_form_field_data_by_name( $booking_data[ 'form_id' ], 'calendar' ) : bookacti_get_default_form_fields_data( 'calendar' );

		bookacti_send_json( array( 'status' => 'success', 'booking_data' => $booking_data, 'calendar_field_data' => $calendar_field_data ), 'get_booking_data' );
	} else {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'empty_data' ), 'get_booking_data' );
	}
}
add_action( 'wp_ajax_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );


/**
 * AJAX Controller - Reschedule a booking
 * @version 1.7.14
 */
function bookacti_controller_reschedule_booking() {
	$booking_id		= intval( $_POST[ 'booking_id' ] );
	$event_id		= intval( $_POST[ 'event_id' ] );
	$event_start	= bookacti_sanitize_datetime( $_POST[ 'event_start' ] );
	$event_end		= bookacti_sanitize_datetime( $_POST[ 'event_end' ] );
	$front_or_admin	= ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';

	// Check nonce, capabilities and other params
	$is_nonce_valid	= check_ajax_referer( 'bookacti_reschedule_booking', 'nonce', false );
	$is_allowed		= bookacti_user_can_manage_booking( $booking_id );

	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'reschedule_booking' );
	}

	$booking = bookacti_get_booking_by_id( $booking_id );

	// Check if the desired event is eligible according to the current booking
	$can_be_rescheduled	= bookacti_booking_can_be_rescheduled_to( $booking, $event_id, $event_start, $event_end, $front_or_admin );
	if( $can_be_rescheduled[ 'status' ] !== 'success' ) {
		bookacti_send_json( $can_be_rescheduled, 'reschedule_booking' );
	}

	// Validate availability
	$form_id	= ! empty( $booking->form_id ) && ! current_user_can( 'bookacti_edit_bookings' ) ? $booking->form_id : 0;
	$validated	= bookacti_validate_booking_form( 'single', $event_id, $event_start, $event_end, $booking->quantity, $form_id );

	if( $validated[ 'status' ] !== 'success' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated[ 'message' ] ) ), 'reschedule_booking' );
	}

	$rescheduled = bookacti_reschedule_booking( $booking_id, $event_id, $event_start, $event_end );

	if( $rescheduled === 0 ) {
		$message = __( 'You must select a different time slot than the current one.', 'booking-activities' );
		bookacti_send_json( array( 'status' => 'no_changes', 'error' => 'no_changes', 'message' => $message ), 'reschedule_booking' );
	}

	if( ! $rescheduled ) {
		bookacti_send_json( array( 'status' => 'failed' ), 'reschedule_booking' );
	}

	$is_admin			= intval( $_POST[ 'is_admin' ] );
	$send_notifications	= $is_admin ? intval( $_POST[ 'send_notifications' ] ) : 1;
	$new_booking		= bookacti_get_booking_by_id( $booking_id );

	do_action( 'bookacti_booking_rescheduled', $new_booking, $booking, array( 'is_admin' => $is_admin, 'send_notifications' => $send_notifications ) );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_id' => $new_booking->id ), $new_booking, 'reschedule_booking', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row ), 'reschedule_booking' );
}
add_action( 'wp_ajax_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );
add_action( 'wp_ajax_nopriv_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );


/**
 * AJAX Controller - Delete a booking
 * @since 1.5.0
 * @version 1.5.8
 */
function bookacti_controller_delete_booking() {

	$booking_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	if( ! check_ajax_referer( 'bookacti_delete_booking', 'nonce_delete_booking', false ) ) { 
		bookacti_send_json_invalid_nonce( 'delete_booking' ); 
	}

	// Check capabilities
	if( ! current_user_can( 'bookacti_delete_bookings' ) || ! bookacti_user_can_manage_booking( $booking_id ) ) { 
		bookacti_send_json_not_allowed( 'delete_booking' ); 
	}

	do_action( 'bookacti_before_delete_booking', $booking_id );

	$deleted = bookacti_delete_booking( $booking_id );

	if( ! $deleted ) {
		$return_array = array( 
			'status'	=> 'failed', 
			'error'		=> 'not_deleted', 
			'message'	=> esc_html__( 'An error occurred while trying to delete the booking.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking' );
	}

	do_action( 'bookacti_booking_deleted', $booking_id );

	$return_array = array( 'status' => 'success' );
	bookacti_send_json( $return_array, 'delete_booking' );
}
add_action( 'wp_ajax_bookactiDeleteBooking', 'bookacti_controller_delete_booking' );




// BOOKING GROUPS

/**
 * AJAX Controller - Get grouped bookings rows
 * @since 1.7.4 (was bookacti_controller_get_booking_rows)
 */
function bookacti_controller_get_grouped_bookings_rows() {
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_get_booking_rows', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_bookings' );

	if( ! $is_nonce_valid || ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_grouped_bookings_rows' ); }

	$booking_group_id	= intval( $_POST[ 'booking_group_id' ] );
	$context			= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns			= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$rows				= $booking_group_id ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

	if( ! $rows ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_rows' ), 'get_grouped_bookings_rows' ); }

	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows ), 'get_grouped_bookings_rows' );
}
add_action( 'wp_ajax_bookactiGetGroupedBookingsRows', 'bookacti_controller_get_grouped_bookings_rows' );


/**
 * Trigger bookacti_booking_state_changed for each bookings of the group when bookacti_booking_group_state_changed is called
 * 
 * @since 1.2.0
 * @param int $booking_group_id
 * @param string $status
 * @param array $args
 */
function bookacti_trigger_booking_state_change_for_each_booking_of_a_group( $booking_group_id, $status , $args ) {
	$bookings = bookacti_get_bookings_by_booking_group_id( $booking_group_id );
	$args[ 'booking_group_state_changed' ] = true;
	foreach( $bookings as $booking ) {
		do_action( 'bookacti_booking_state_changed', $booking->id, $status, $args );
	}
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_trigger_booking_state_change_for_each_booking_of_a_group', 10, 3 );


/**
 * AJAX Controller - Cancel a booking group
 * @since 1.1.0
 * @version 1.7.14
 */
function bookacti_controller_cancel_booking_group() {
	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce, capabilities and other params
	$is_nonce_valid	= check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
	$is_allowed		= bookacti_user_can_manage_booking_group( $booking_group_id );
	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'cancel_booking_group' );
	}

	$booking_group = bookacti_get_booking_group_by_id( $booking_group_id );
	if( $booking_group->state === 'cancelled' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_group_already_cancelled', 'message' => esc_html__( 'The booking group is already cancelled.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	$can_be_cancelled = bookacti_booking_group_can_be_cancelled( $booking_group, 'front' );
	if( ! $can_be_cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking_group', 'message' => esc_html__( 'The booking group cannot be cancelled.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	$cancelled = bookacti_update_booking_group_state( $booking_group_id, 'cancelled', 'auto', true );
	if( ! $cancelled ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking_group', 'message' => esc_html__( 'An error occured while trying to cancel the booking group.', 'booking-activities' ) ), 'cancel_booking_group' );
	}

	do_action( 'bookacti_booking_group_state_changed', $booking_group_id, 'cancelled', array( 'is_admin' => false ) );

	$new_booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
	$allow_refund		= bookacti_booking_group_can_be_refunded( $new_booking_group, false, 'front' );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $new_booking_group->id, 'group_by' => 'booking_group' ), $new_booking_group, 'cancel_booking_group', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'allow_refund' => $allow_refund ), 'cancel_booking_group' );
}
add_action( 'wp_ajax_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );
add_action( 'wp_ajax_nopriv_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );


/**
 * AJAX Controller - Get possible actions to refund a booking group
 * @since 1.1.0
 * @version 1.7.14
 */
function bookacti_controller_get_booking_group_refund_actions_html() {

	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce, capabilities and other params
	if( ! check_ajax_referer( 'bookacti_get_refund_actions_html', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'get_refund_actions_html' );
	}

	if( ! bookacti_user_can_manage_booking_group( $booking_group_id ) ) {
		bookacti_send_json_not_allowed( 'get_refund_actions_html' );
	}

	$front_or_admin = ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	if( ! bookacti_booking_group_can_be_refunded( $booking_group_id, false, $front_or_admin ) ) {
		bookacti_send_json( array( 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}

	$refund_actions_array	= bookacti_get_refund_actions_by_booking_group_id( $booking_group_id, $front_or_admin );
	$refund_actions_html	= bookacti_get_refund_dialog_html_by_booking_group_id( $booking_group_id, $front_or_admin );

	if( ! empty( $refund_actions_html ) ) {
		bookacti_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array ), 'get_refund_actions_html' );

	} else {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_retrieve_actions_html' ), 'get_refund_actions_html' );
	}
}
add_action( 'wp_ajax_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );


/**
 * AJAX Controller - Refund a booking group
 * @since 1.1.0
 * @version 1.7.14
 */
function bookacti_controller_refund_booking_group() {
	$booking_group_id	= intval( $_POST[ 'booking_id' ] );
	$is_admin			= intval( $_POST[ 'is_admin' ] );
	$front_or_admin		= ! empty( $_POST[ 'is_admin' ] ) ? 'admin' : 'front';
	$sanitized_action	= sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
	$refund_action		= array_key_exists( $sanitized_action, bookacti_get_refund_actions_by_booking_group_id( $booking_group_id, $front_or_admin ) ) ? $sanitized_action : 'email';

	// Check nonce, capabilities and other params
	if( ! check_ajax_referer( 'bookacti_refund_booking', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'refund_booking_group' );
	}

	if( ! bookacti_user_can_manage_booking_group( $booking_group_id ) ) {
		bookacti_send_json_not_allowed( 'refund_booking_group' );
	}

	if( ! bookacti_booking_group_can_be_refunded( $booking_group_id, $refund_action, $front_or_admin ) ) {
		bookacti_send_json( array( 'error' => 'cannot_be_refunded', 'message' => esc_html__( 'This booking cannot be refunded.', 'booking-activities' ) ), 'refund_booking_group' );
	}

	$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );

	if( $refund_action === 'email' ) {
		$refunded = bookacti_send_email_refund_request( $booking_group_id, 'group', $refund_message );
		if( $refunded ) {
			$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested' );
		} else {
			$refunded = array( 'status' => 'failed', 'error' => 'cannot_send_email' );
		}
	} else {
		$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $booking_group_id, 'group', $refund_action, $refund_message, $front_or_admin );
	}

	if( $refunded[ 'status' ] === 'success' ) {
		$new_state	= $refunded[ 'new_state' ] ? $refunded[ 'new_state' ] : 'refunded';
		$updated	= bookacti_update_booking_group_state( $booking_group_id, $new_state, 'auto', true );

		// Hook status changes
		if( $updated ) {
			do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
		}

		$new_booking_group = bookacti_get_booking_group_by_id( $booking_group_id );
		$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
		$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
		$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $new_booking_group->id, 'group_by' => 'booking_group' ), $new_booking_group, 'refund_booking_group', $context );
		$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

		$refunded[ 'row' ] = $row;

		// Get grouped booking rows if they are displayed and need to be refreshed
		$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;
		$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

		$refunded[ 'grouped_booking_rows' ] = $rows;
	}

	bookacti_send_json( $refunded, 'refund_booking_group' );
}
add_action( 'wp_ajax_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );
add_action( 'wp_ajax_nopriv_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );


/**
 * AJAX Controller - Change booking group state
 * @since 1.1.0
 * @version 1.7.14
 */
function bookacti_controller_change_booking_group_state() {
	$booking_group_id		= intval( $_POST[ 'booking_id' ] );
	$booking_state			= sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
	$payment_status			= sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
	$send_notifications		= $_POST[ 'send_notifications' ] ? 1 : 0;
	$is_admin				= intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;
	$reload_grouped_bookings= intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;

	$new_booking_state	= array_key_exists( $booking_state, bookacti_get_booking_state_labels() ) ? $booking_state : false;
	$new_payment_status	= array_key_exists( $payment_status, bookacti_get_payment_status_labels() ) ? $payment_status : false;
	$active_changed		= false;

	// Check nonce, capabilities and other params
	$is_nonce_valid	= check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_bookings' );		
	if( ! $is_nonce_valid || ! $is_allowed ) {
		bookacti_send_json_not_allowed( 'change_booking_group_status' );
	}

	// Change booking group states
	$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
	if( $new_booking_state && $booking_group->state !== $new_booking_state ) {

		$state_can_be_changed = bookacti_booking_group_state_can_be_changed_to( $booking_group, $new_booking_state, 'admin' );
		if( ! $state_can_be_changed ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_group_status', 'message' => esc_html__( 'The booking group status cannot be changed.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		$was_active	= $booking_group->active ? 1 : 0;
		$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
		if( $active !== $was_active ) { $active_changed = true; }

		$updated = bookacti_update_booking_group_state( $booking_group_id, $new_booking_state, $active, true, true );
		if( ! $updated ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_status', 'message' => esc_html__( 'An error occured while trying to change the booking group status.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_booking_state, array( 'is_admin' => $is_admin, 'active' => $active, 'send_notifications' => $send_notifications ) );
	}

	// Change payment status
	if( $new_payment_status && $booking_group->payment_status !== $new_payment_status ) {
		$updated = bookacti_update_booking_group_payment_status( $booking_group_id, $new_payment_status, true, true );
		if( $updated === false ) { 
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_payment_status', 'message' => esc_html__( 'An error occured while trying to change the booking group payment status.', 'booking-activities' ) ), 'change_booking_group_status' );
		}

		do_action( 'bookacti_booking_group_payment_status_changed', $booking_group_id, $new_payment_status, array( 'is_admin' => $is_admin ) );
	}

	$new_booking_group = bookacti_get_booking_group_by_id( $booking_group_id );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $new_booking_group->id, 'group_by' => 'booking_group' ), $new_booking_group, 'change_booking_group_status', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows, 'active_changed' => $active_changed ), 'change_booking_group_status' );
}
add_action( 'wp_ajax_bookactiChangeBookingGroupState', 'bookacti_controller_change_booking_group_state' );


/**
 * AJAX Controller - Change booking group quantity
 * @since 1.7.10
 */
function bookacti_controller_change_booking_group_quantity() {
	$booking_group_id		= intval( $_POST[ 'booking_id' ] );
	$new_quantity	= intval( $_POST[ 'new_quantity' ] );
	$is_admin		= intval( $_POST[ 'is_admin' ] ) === 1 ? true : false;
	$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;

	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_change_booking_quantity', 'nonce_change_booking_quantity', false );
	$is_allowed		= current_user_can( 'bookacti_edit_bookings' );		

	if( ! $is_nonce_valid || ! $is_allowed  ) {
		bookacti_send_json_not_allowed( 'change_booking_group_quantity' );
	}

	// Check if the quantity is valid
	if( ! $new_quantity ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_new_quantity', 'message' => esc_html__( 'The new quantity is not valid.', 'booking-activities' ) ), 'change_booking_group_quantity' );
	}

	$old_quantity = bookacti_get_booking_group_quantity( $booking_group_id );

	// Update booking quantity
	$updated = bookacti_force_update_booking_group_bookings_quantity( $booking_group_id, $new_quantity );
	if( $updated === false ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_quantity', 'message' => esc_html__( 'An error occured while trying to change the booking quantity.', 'booking-activities' ) ), 'change_booking_group_quantity' );
	}

	do_action( 'bookacti_booking_group_quantity_updated', $booking_group_id, $new_quantity, $old_quantity, array( 'is_admin' => $is_admin ) );

	$new_booking_group = bookacti_get_booking_group_by_id( $booking_group_id );

	$context	= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : '';
	$columns	= ! empty( $_POST[ 'columns' ] ) && is_array( $_POST[ 'columns' ] ) ? array_map( 'sanitize_title_with_dashes', $_POST[ 'columns' ] ) : array();
	$filters	= apply_filters( 'bookacti_booking_action_row_filters', array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), $new_booking_group, 'change_booking_group_quantity', $context );
	$row		= bookacti_get_booking_list_rows_according_to_context( $context, $filters, $columns );

	$rows = $reload_grouped_bookings ? bookacti_get_booking_list_rows_according_to_context( $context, array( 'booking_group_id' => $booking_group_id ), $columns ) : '';

	bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows ), 'change_booking_group_quantity' );
}
add_action( 'wp_ajax_bookactiChangeBookingGroupQuantity', 'bookacti_controller_change_booking_group_quantity' );


/**
 * AJAX Controller - Delete a booking group
 * @since 1.5.0
 */
function bookacti_controller_delete_booking_group() {

	$booking_group_id = intval( $_POST[ 'booking_id' ] );

	// Check nonce
	if( ! check_ajax_referer( 'bookacti_delete_booking', 'nonce_delete_booking', false ) ) { 
		bookacti_send_json_invalid_nonce( 'delete_booking_group' ); 
	}

	// Check capabilities
	if( ! current_user_can( 'bookacti_delete_bookings' ) || ! bookacti_user_can_manage_booking_group( $booking_group_id ) ) { 
		bookacti_send_json_not_allowed( 'delete_booking_group' ); 
	}

	do_action( 'bookacti_before_delete_booking_group', $booking_group_id );

	$bookings_deleted = bookacti_delete_booking_group_bookings( $booking_group_id );

	if( $bookings_deleted === false ) {
		$return_array = array( 
			'status'	=> 'failed', 
			'error'		=> 'grouped_bookings_not_deleted', 
			'message'	=> esc_html__( 'An error occurred while trying to delete the bookings of the group.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking_group' );
	}

	$group_deleted = bookacti_delete_booking_group( $booking_group_id );

	if( ! $group_deleted ) {
		$return_array = array( 
			'status'	=> 'failed', 
			'error'		=> 'not_deleted', 
			'message'	=> esc_html__( 'An error occurred while trying to delete the booking group.', 'booking-activities' )
		);
		bookacti_send_json( $return_array, 'delete_booking_group' );
	}

	do_action( 'bookacti_booking_group_deleted', $booking_group_id );

	$return_array = array( 'status' => 'success' );
	bookacti_send_json( $return_array, 'delete_booking_group' );
}
add_action( 'wp_ajax_bookactiDeleteBookingGroup', 'bookacti_controller_delete_booking_group' );




// BULK ACTIONS
/**
 * Generate the export bookings URL according to current filters and export settings
 * @since 1.6.0
 * @version 1.8.0
 */
function bookacti_controller_generate_export_bookings_url() {

	// Check nonce
	if( ! check_ajax_referer( 'bookacti_export_bookings_url', 'nonce_export_bookings_url', false ) ) { 
		bookacti_send_json_invalid_nonce( 'export_bookings_url' ); 
	}

	// Check capabilities
	if( ! current_user_can( 'bookacti_manage_bookings' ) ) { 
		bookacti_send_json_not_allowed( 'export_bookings_url' ); 
	}

	$lang = bookacti_get_current_lang_code();
	$message = esc_html__( 'The link has been correctly generated. Use the link above to export your bookings.', 'booking-activities' );

	// Get or generate current user export secret key
	$current_user_id = get_current_user_id();
	$secret_key = get_user_meta( $current_user_id, 'bookacti_secret_key', true );
	if( ! $secret_key || ! empty( $_POST[ 'reset_key' ] ) ) {
		$secret_key = md5( microtime().rand() );
		update_user_meta( $current_user_id, 'bookacti_secret_key', $secret_key );
		if( ! empty( $_POST[ 'reset_key' ] ) ) {
			$message .= '<br/><em>' . esc_html__( 'Your secret key has been changed. The old links that you have generated won\'t work anymore.', 'booking-activities' ) . '</em>';
		}
	}

	// Get formatted booking filters
	if( isset( $_POST[ 'booking_filters' ][ 'templates' ] ) && $_POST[ 'booking_filters' ][ 'templates' ][ 0 ] === 'all' ) {
		unset( $_POST[ 'booking_filters' ][ 'templates' ][ 0 ] );
		if( empty( $_POST[ 'booking_filters' ][ 'templates' ] ) ) { $_POST[ 'booking_filters' ][ 'templates' ] = ''; }
	}
	// Accepts two different parameter names for booking system related parameters
	if( ! isset( $_POST[ 'booking_filters' ][ 'event_group_id' ] ) && isset( $_POST[ 'booking_filters' ][ 'bookacti_group_id' ] ) && $_POST[ 'booking_filters' ][ 'bookacti_group_id' ] !== 'single' )	{ $_POST[ 'booking_filters' ][ 'event_group_id' ] = intval( $_POST[ 'booking_filters' ][ 'bookacti_group_id' ] ); }
	if( empty( $_POST[ 'booking_filters' ][ 'event_group_id' ] ) ) {
		if( ! isset( $_POST[ 'booking_filters' ][ 'event_id' ] ) && isset( $_POST[ 'booking_filters' ][ 'bookacti_event_id' ] ) )		{ $_POST[ 'booking_filters' ][ 'event_id' ] = intval( $_POST[ 'booking_filters' ][ 'bookacti_event_id' ] ); }
		if( ! isset( $_POST[ 'booking_filters' ][ 'event_start' ] ) && isset( $_POST[ 'booking_filters' ][ 'bookacti_event_start' ] ) )	{ $_POST[ 'booking_filters' ][ 'event_start' ] = bookacti_sanitize_datetime( $_POST[ 'booking_filters' ][ 'bookacti_event_start' ] ); }
		if( ! isset( $_POST[ 'booking_filters' ][ 'event_end' ] ) && isset( $_POST[ 'booking_filters' ][ 'bookacti_event_end' ] ) )		{ $_POST[ 'booking_filters' ][ 'event_end' ] = bookacti_sanitize_datetime( $_POST[ 'booking_filters' ][ 'bookacti_event_end' ] ); }
	}

	$default_fitlers = bookacti_format_booking_filters();
	$booking_filters = bookacti_format_booking_filters( $_POST[ 'booking_filters' ] );

	if( isset( $_POST[ 'export_groups' ] ) ) {
		$booking_filters[ 'group_by' ] = $_POST[ 'export_groups' ] === 'groups' ? 'booking_group' : 'none';
	}
	if( isset( $_POST[ 'per_page' ] ) ) {
		$booking_filters[ 'per_page' ] = is_numeric( $_POST[ 'per_page' ] ) ? intval( $_POST[ 'per_page' ] ) : ( empty( $_POST[ 'per_page' ] ) ? '' : 20 );
	}

	// Keep only the required data to keep the URL as short as possible
	foreach( $booking_filters as $filter_name => $filter_value ) {
		if( is_numeric( $filter_value ) && is_string( $filter_value ) ) { 
			$filter_value = is_float( $filter_value + 0 ) ? floatval( $filter_value ) : intval( $filter_value );
		}
		if( $default_fitlers[ $filter_name ] === $filter_value ) {
			unset( $booking_filters[ $filter_name ] );
		}
	}

	// Format the columns
	$columns = array();
	if( isset( $_POST[ 'columns' ] ) ) {
		if( ! is_array( $_POST[ 'columns' ] ) ) { $columns = bookacti_get_bookings_export_default_columns(); }
		else {
			$allowed_columns = bookacti_get_bookings_export_columns();
			foreach( $_POST[ 'columns' ] as $i => $column_name ) {
				if( isset( $allowed_columns[ $column_name ] ) ) { $columns[] = $column_name; }
			}
		}
	}

	// Let third party plugins change the booking filters
	$booking_atts = apply_filters( 'bookacti_export_bookings_url_attributes', $booking_filters );

	// Define a filename
	$filename = 'booking-activities-bookings';
	if( ! empty( $booking_atts[ 'filename' ] ) ) {
		$sanitized_filename = sanitize_title_with_dashes( $booking_atts[ 'filename' ] );
		if( $sanitized_filename ) { 
			$filename = $sanitized_filename;
		}
	}

	// Add the required settings to the URL
	$csv_url = home_url( '?action=bookacti_export_bookings&filename=' . $filename . '&key=' . $secret_key . '&lang=' . $lang );
	if( $booking_atts ) {
		$csv_url = add_query_arg( $booking_atts, $csv_url );
	}
	if( $columns ) {
		$csv_url = add_query_arg( array( 'columns' => $columns ), $csv_url );
		update_user_meta( get_current_user_id(), 'bookacti_bookings_export_default_columns', $columns );
	}

	bookacti_send_json( array( 'status' => 'success', 'url' => esc_url_raw( $csv_url ), 'message' => $message ), 'export_bookings_url' ); 
}
add_action( 'wp_ajax_bookactiExportBookingsUrl', 'bookacti_controller_generate_export_bookings_url' );


/**
 * Export booking list according to filters as CSV
 * @since 1.6.0
 * @version 1.7.13
 */
function bookacti_export_bookings_page() {
	if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'bookacti_export_bookings' ) { return; }

	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : 'booking-activities-bookings';
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
	if( substr( $filename, -4 ) !== '.csv' ) { $filename .= '.csv'; }

	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
	if( ! $key ) { esc_html_e( 'Missing key.', 'booking-activities' ); exit; }

	// Check if the user exists
	$users = get_users( array( 'meta_key' => 'bookacti_secret_key', 'meta_value' => $key ) );
	if( ! $users ) { esc_html_e( 'Invalid key.', 'booking-activities' ); exit; }
	$user = $users[ 0 ];

	// Format the booking filters
	if( empty( $_REQUEST[ 'templates' ] ) ) { $_REQUEST[ 'templates' ] = ''; }
	$filters = bookacti_format_booking_filters( bookacti_format_string_booking_filters( $_REQUEST ) );

	// If an event has been selected, do not retrieve groups of events containing this event
	if( $filters[ 'event_id' ] && ! $filters[ 'booking_group_id' ] ) {
		$filters[ 'booking_group_id' ] = 'none';
	}

	// Restrict to allowed templates
	$allowed_templates = array_keys( bookacti_fetch_templates( array(), false, $user->ID ) );
	$filters[ 'templates' ] = empty( $_REQUEST[ 'templates' ] ) ? $allowed_templates : array_intersect( $allowed_templates, $_REQUEST[ 'templates' ] );

	// Let third party plugins change the booking filters
	$filters = apply_filters( 'bookacti_export_bookings_attributes', $filters );

	// Format the columns
	$all_columns = bookacti_get_bookings_export_columns();
	$columns = array();
	if( ! empty( $_REQUEST[ 'columns' ] ) && is_array( $_REQUEST[ 'columns' ] ) ) {
		foreach( $_REQUEST[ 'columns' ] as $column_name ) {
			if( ! isset( $all_columns[ $column_name ] ) ) { continue; }
			$columns[] = $column_name;
		}
	}
	if( ! $columns ) {
		$columns = bookacti_get_bookings_export_default_columns();
	}

	$columns = apply_filters( 'bookacti_export_bookings_columns', $columns );

	header( 'Content-type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Expired date to force third-party apps to refresh soon

	echo bookacti_convert_bookings_to_csv( $filters, $columns );

	exit;
}
add_action( 'init', 'bookacti_export_bookings_page', 10 );


/**
 * Export booked events of a specific user as ICS
 * @since 1.6.0
 * @version 1.7.13
 */
function bookacti_export_user_booked_events_page() {
	if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'bookacti_export_user_booked_events' ) { return; }

	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
	if( ! $key ) { esc_html_e( 'Missing key.', 'booking-activities' ); exit; }

	// Check if the user exists
	$users = get_users( array( 'meta_key' => 'bookacti_secret_key', 'meta_value' => $key ) );
	if( ! $users ) { esc_html_e( 'Invalid key.', 'booking-activities' ); exit; }

	$user = $users[ 0 ];

	$atts = apply_filters( 'bookacti_export_events_attributes', array_merge( bookacti_format_booking_system_url_attributes(), array(
		'bookings_only' => 1,
		'user_id' => $user->ID,
		'status' => array( 'delivered', 'booked', 'pending' ),
		'groups_single_events' => 1,
		'past_events' => 1,
		'past_events_bookable' => 1
	)));

	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : ( ! empty( $atts[ 'filename' ] ) ? sanitize_title_with_dashes( $atts[ 'filename' ] ) : 'my-bookings' );
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
	$atts[ 'filename' ] = $filename;
	if( substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }

	$calname = esc_html__( 'My bookings', 'booking-activities' );
	$caldesc = $calname . '.';

	// Increment the sequence number each time to make sure that the events will be updated
	$sequence = intval( get_user_meta( $user->ID, 'bookacti_ical_sequence', true ) ) + 1;
	update_user_meta( $user->ID, 'bookacti_ical_sequence', $sequence );

	bookacti_export_events_page( $atts, $calname, $caldesc, $sequence );
}
add_action( 'init', 'bookacti_export_user_booked_events_page', 10 );


/**
 * Export events of a specific booking (group)
 * @since 1.6.0
 * @version 1.8.0
 */
function bookacti_export_booked_events_page() {
	if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'bookacti_export_booked_events' ) { return; }

	// Check if a booking ID was given
	if( empty( $_REQUEST[ 'booking_id' ] ) && empty( $_REQUEST[ 'booking_group_id' ] ) ) { esc_html_e( 'Invalid booking ID.', 'booking-activities' ); exit; }

	$atts = apply_filters( 'bookacti_export_events_attributes', array_merge( bookacti_get_booking_system_default_attributes(), array(
		'status' => array( 'delivered', 'booked', 'pending' ),
		'past_events' => 1
	)));

	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : ( ! empty( $atts[ 'filename' ] ) ? sanitize_title_with_dashes( $atts[ 'filename' ] ) : 'my-bookings' );
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'booking-activities' ); exit; }
	if( substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }

	$events = array( 'events' => array() );
	if( ! empty( $_REQUEST[ 'booking_id' ] ) ) {
		$booking_id = intval( $_REQUEST[ 'booking_id' ] );
		$booking = bookacti_get_booking_by_id( $booking_id );
		if( ! $booking ) { esc_html_e( 'Invalid booking ID.', 'booking-activities' ); exit; }
		if( ! in_array( $booking->state, $atts[ 'status' ], true ) ) { esc_html_e( 'No events found.', 'booking-activities' ); exit; }

		$event = bookacti_get_event_by_id( $booking->event_id );

		// Check if the event is past
		if( ! $atts[ 'past_events' ] ) {
			$timezone		= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
			$current_time	= new DateTime( 'now', $timezone );
			$start_datetime = new DateTime( $event->start, $timezone );
			if( $start_datetime < $current_time ) { esc_html_e( 'No events found.', 'booking-activities' ); exit; }
		}

		$events_args = array(
			'past_events' => true,
			'interval' => array( 'start' => substr( $booking->event_start, 0, 10 ), 'end' => substr( $booking->event_end, 0, 10 ) ),
		);
		$events = bookacti_get_events_array_from_db_events( array( $event ), $events_args );

	} else if( ! empty( $_REQUEST[ 'booking_group_id' ] ) ) {
		$booking_group_id = intval( $_REQUEST[ 'booking_group_id' ] );
		$booking_group = bookacti_get_booking_group_by_id( $booking_group_id );
		$bookings = bookacti_get_bookings_by_booking_group_id( $booking_group_id );
		if( ! $booking_group || ! $bookings ) { esc_html_e( 'Invalid booking ID.', 'booking-activities' ); exit; }

		$events = bookacti_fetch_grouped_events( array( 'groups' => array( $booking_group->event_group_id ), 'past_events' => $atts[ 'past_events' ] ) );

		// Remove events of the group which are no longer booked
		if( $events[ 'events' ] ) { 
			foreach( $events[ 'events' ] as $i => $grouped_event ) {
				$has_booking = false;
				foreach( $bookings as $booking ) {
					if( $booking->event_id === $grouped_event[ 'id' ] 
					&&  $booking->event_start === $grouped_event[ 'start' ] 
					&&  $booking->event_end === $grouped_event[ 'end' ]
					&&  in_array( $booking->state, $atts[ 'status' ], true ) ) { 
						$has_booking = true;
						break;
					}
				}
				if( ! $has_booking ) { unset( $events[ 'events' ][ $i ] ); }
			}
		}
	}

	if( ! $events[ 'events' ] ) { esc_html_e( 'Events not found.', 'booking-activities' ); exit; }

	$calname = esc_html__( 'My bookings', 'booking-activities' );
	$caldesc = $calname . '.';

	header( 'Content-type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Expired date to force third-party calendars to refresh soon

	echo bookacti_convert_events_to_ical( $events, $calname, $caldesc, 0 );

	exit;
}
add_action( 'init', 'bookacti_export_booked_events_page', 10 );