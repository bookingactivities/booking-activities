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




// BOOKINGS ACTIONS

/**
 * AJAX Controller - Cancel bookings
 * @since 1.16.0
 * @version 1.16.5
 */
function bookacti_controller_cancel_bookings() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'cancel_booking' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'cancel_booking' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'cancel_booking' ); }
		if( ! bookacti_booking_can_be_cancelled( $booking, ! $is_admin ) ) {
			/* translators: %s = Booking ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_cancelled', 'message' => sprintf( esc_html__( 'Booking #%s cannot be cancelled.', 'booking-activities' ), $booking->id ) ), 'cancel_booking' );
		}
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_refund_actions_html' ); }
		if( ! bookacti_booking_group_can_be_cancelled( $booking_group, $group_bookings, ! $is_admin ) ) {
			/* translators: %s = Booking group ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_cancelled', 'message' => sprintf( esc_html__( 'Booking group #%s cannot be cancelled.', 'booking-activities' ), $booking_group_id ) ), 'cancel_booking' );
		}
	}
	
	$groups_updated   = $group_ids ? bookacti_update_booking_groups_status( $group_ids, 'cancelled' ) : 0;
	$bookings_updated = $booking_ids ? bookacti_update_bookings_status( $booking_ids, 'cancelled' ) : 0;

	if( $bookings_updated === false || $groups_updated === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking', 'message' => esc_html__( 'An error occurred while trying to cancel the booking.', 'booking-activities' ) ), 'cancel_booking' );
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$updated = array( 'bookings' => array(), 'booking_groups' => array() );
	foreach( $bookings as $booking_id => $booking ) {
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		if( $booking->state !== $new_booking->state ) {
			do_action( 'bookacti_booking_status_changed', $new_booking->state, $booking, array() );
			bookacti_send_booking_status_change_notification( $new_booking->state, $new_booking, $booking );
		}
		$updated[ 'bookings' ][ $booking_id ] = array(
			'old_status' => $booking->state,
			'new_status' => $new_booking->state
		);
	}
	foreach( $booking_groups as $group_id => $booking_group ) {
		if( empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ) ) { continue; }
		$new_booking_group = $new_selected_bookings[ 'booking_groups' ][ $group_id ];
		$group_bookings    = isset( $groups_bookings[ $group_id ] ) ? $groups_bookings[ $group_id ] : array();
		if( $booking_group->state !== $new_booking_group->state ) {
			do_action( 'bookacti_booking_group_status_changed', $new_booking_group->state, $booking_group, $group_bookings, array() );
			bookacti_send_booking_group_status_change_notification( $new_booking_group->state, $new_booking_group, $booking_group );
		}
		$updated[ 'booking_groups' ][ $group_id ] = array(
			'old_status' => $booking_group->state,
			'new_status' => $new_booking_group->state
		);
	}
	
	$updated = apply_filters( 'bookacti_bookings_cancelled', $updated, $selected_bookings, $new_selected_bookings );
	
	// Check refund capabilities for each booking (group)
	$allow_refund = true;
	$new_bookings = ! empty( $new_selected_bookings[ 'bookings' ] ) ? $new_selected_bookings[ 'bookings' ] : array();
	foreach( $new_bookings as $new_booking ) {
		if( ! bookacti_booking_can_be_refunded( $new_booking, ! $is_admin ) ) {
			$allow_refund = false;
			break;
		}
	}
	if( $allow_refund ) {
		$new_booking_groups = ! empty( $new_selected_bookings[ 'booking_groups' ] ) ? $new_selected_bookings[ 'booking_groups' ] : array();
		foreach( $new_booking_groups as $booking_group_id => $new_booking_group ) {
			$new_group_bookings = ! empty( $new_selected_bookings[ 'groups_bookings' ][ $booking_group_id ] ) ? $new_selected_bookings[ 'groups_bookings' ][ $booking_group_id ] : array();
			if( ! bookacti_booking_group_can_be_refunded( $new_booking_group, $new_group_bookings, ! $is_admin ) ) {
				$allow_refund = false;
				break;
			}
		}
	}
	
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	$group_by    = ! empty( $row_filters[ 'booking_group_id' ] ) || ! empty( $row_filters[ 'in__booking_group_id' ] ) ? 'booking_group' : 'none';
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => $group_by, 'fetch_meta' => true ) ), 'cancel_booking', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows, 'updated' => $updated, 'allow_refund' => $allow_refund ), 'cancel_booking' );
}
add_action( 'wp_ajax_bookactiCancelBookings', 'bookacti_controller_cancel_bookings' );
add_action( 'wp_ajax_nopriv_bookactiCancelBookings', 'bookacti_controller_cancel_bookings' );


/**
 * AJAX Controller - Get possible actions to refund the selected bookings
 * @since 1.16.0
 */
function bookacti_controller_get_bookings_refund_actions_html() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_refund_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_refund_actions_html' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'get_refund_actions_html' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_refund_actions_html' ); }
		if( ! bookacti_booking_can_be_refunded( $booking, ! $is_admin ) ) {
			/* translators: %s = Booking ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => sprintf( esc_html__( 'Booking #%s cannot be refunded.', 'booking-activities' ), $booking->id ) ), 'get_refund_actions_html' );
		}
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_refund_actions_html' ); }
		if( ! bookacti_booking_group_can_be_refunded( $booking_group, $group_bookings, ! $is_admin ) ) {
			/* translators: %s = Booking group ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => sprintf( esc_html__( 'Booking group #%s cannot be refunded.', 'booking-activities' ), $booking_group_id ) ), 'get_refund_actions_html' );
		}
	}
	
	$refund_actions_array = bookacti_get_selected_bookings_refund_actions( $selected_bookings, ! $is_admin );
	$refund_actions_html  = bookacti_get_refund_actions_html( $refund_actions_array );
	$refund_amount        = bookacti_get_selected_bookings_total_price( $selected_bookings, true );
	
	bookacti_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array, 'amount' => $refund_amount ), 'get_refund_actions_html' );
}
add_action( 'wp_ajax_bookactiGetBookingsRefundActionsHTML', 'bookacti_controller_get_bookings_refund_actions_html' );
add_action( 'wp_ajax_nopriv_bookactiGetBookingsRefundActionsHTML', 'bookacti_controller_get_bookings_refund_actions_html' );


/**
 * AJAX Controller - Refund bookings
 * @since 1.16.0
 * @version 1.16.1
 */
function bookacti_controller_refund_bookings() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_refund_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'refund_booking' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'refund_booking' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'refund_booking' ); }
		if( ! bookacti_booking_can_be_refunded( $booking, ! $is_admin ) ) {
			/* translators: %s = Booking ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => sprintf( esc_html__( 'Booking #%s cannot be refunded.', 'booking-activities' ), $booking->id ) ), 'refund_booking' );
		}
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'refund_booking' ); }
		if( ! bookacti_booking_group_can_be_refunded( $booking_group, $group_bookings, ! $is_admin ) ) {
			/* translators: %s = Booking group ID */
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'cannot_be_refunded', 'message' => sprintf( esc_html__( 'Booking group #%s cannot be refunded.', 'booking-activities' ), $booking_group_id ) ), 'refund_booking' );
		}
	}
	
	$refund_action  = ! empty( $_POST[ 'refund_action' ] ) ? sanitize_title_with_dashes( $_POST[ 'refund_action' ] ) : '';
	$refund_message = ! empty( $_POST[ 'refund_message' ] ) ? ( function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( stripslashes( $_POST[ 'refund_message' ] ) ) : sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) ) ) : '';
	
	// Check if the refund action exists
	$refund_actions_array = bookacti_get_selected_bookings_refund_actions( $selected_bookings, ! $is_admin );
	if( ! isset( $refund_actions_array[ $refund_action ] ) ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_refund_action', 'message' => esc_html__( 'The selected refund method is not valid.', 'booking-activities' ) ), 'refund_booking' );	
	}
	
	$refunded = array();
	if( $refund_action === 'email' ) {
		// The refund request notification is sent by bookacti_send_notification_when_booking_status_changes() on the hook 'bookacti_booking_status_changed'
		$refunded = array( 
			'status'     => 'success', 
			'new_status' => 'refund_requested', 
			'message'    => esc_html__( 'Your refund request has been sent. We will contact you soon.', 'booking-activities' )
		);
	} else {
		$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $selected_bookings, $refund_action, $refund_message, ! $is_admin );
	}

	if( $refunded[ 'status' ] !== 'success' ) {
		if( empty( $refunded[ 'message' ] ) ) { 
			$refunded[ 'message' ] = esc_html__( 'Error occurs while trying to request a refund. Please contact the administrator.', 'booking-activities' );
		}
		bookacti_send_json( $refunded, 'refund_booking' );
	}
	
	$updated_booking_ids       = isset( $refunded[ 'booking_ids' ] ) ? bookacti_ids_to_array( $refunded[ 'booking_ids' ] ) : $booking_ids;
	$updated_booking_group_ids = isset( $refunded[ 'booking_group_ids' ] ) ? bookacti_ids_to_array( $refunded[ 'booking_group_ids' ] ) : $group_ids;
	
	// Save the refund message
	if( $refund_message ) {
		if( $updated_booking_ids ) {
			bookacti_update_metadata( 'booking', $updated_booking_ids, array( 'refund_message' => $refund_message ) );
		}
		if( $updated_booking_group_ids ) {
			bookacti_update_metadata( 'booking_group', $updated_booking_group_ids, array( 'refund_message' => $refund_message ) );
		}
	}

	if( empty( $refunded[ 'do_not_update_status' ] ) ) {
		$new_booking_status = ! empty( $refunded[ 'new_status' ] ) ? $refunded[ 'new_status' ] : 'refunded';
		$groups_updated     = $updated_booking_group_ids ? bookacti_update_booking_groups_status( $updated_booking_group_ids, $new_booking_status ) : 0;
		$bookings_updated   = $updated_booking_ids ? bookacti_update_bookings_status( $updated_booking_ids, $new_booking_status ) : 0;
		
		if( $bookings_updated === false || $groups_updated === false ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_status', 'message' => esc_html__( 'An error occurred while trying to change the booking status.', 'booking-activities' ) ), 'refund_action' );
		}
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$updated_bookings       = array_intersect_key( $bookings, array_flip( $updated_booking_ids ) );
	$updated_booking_groups = array_intersect_key( $booking_groups, array_flip( $updated_booking_group_ids ) );
	
	if( ! isset( $refunded[ 'bookings' ] ) )       { $refunded[ 'bookings' ] = array(); }
	if( ! isset( $refunded[ 'booking_groups' ] ) ) { $refunded[ 'booking_groups' ] = array(); }
	
	foreach( $updated_bookings as $booking_id => $booking ) {
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		if( $booking->state !== $new_booking->state && empty( $refunded[ 'do_not_update_status' ] ) ) {
			do_action( 'bookacti_booking_status_changed', $new_booking->state, $booking, array( 'refund_action' => $refund_action ) );
			if( empty( $refunded[ 'do_not_send_notification' ] ) ) {
				bookacti_send_booking_status_change_notification( $new_booking->state, $new_booking, $booking, 'both', array( 'refund_action' => $refund_action ) );
			}
		}
		if( ! isset( $refunded[ 'bookings' ][ $booking_id ] ) ) { $refunded[ 'bookings' ][ $booking_id ] = array(); }
		$refunded[ 'bookings' ][ $booking_id ][ 'old_status' ] = $booking->state;
		$refunded[ 'bookings' ][ $booking_id ][ 'new_status' ] = $new_booking->state;
	}
	foreach( $updated_booking_groups as $group_id => $booking_group ) {
		if( empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ) ) { continue; }
		$new_booking_group = $new_selected_bookings[ 'booking_groups' ][ $group_id ];
		$group_bookings    = isset( $groups_bookings[ $group_id ] ) ? $groups_bookings[ $group_id ] : array();
		if( $booking_group->state !== $new_booking_group->state && empty( $refunded[ 'do_not_update_status' ] ) ) {
			do_action( 'bookacti_booking_group_status_changed', $new_booking_group->state, $booking_group, $group_bookings, array( 'refund_action' => $refund_action ) );
			if( empty( $refunded[ 'do_not_send_notification' ] ) ) {
				bookacti_send_booking_group_status_change_notification( $new_booking_group->state, $new_booking_group, $booking_group, 'both', array( 'refund_action' => $refund_action ) );
			}
		}
		if( ! isset( $refunded[ 'booking_groups' ][ $group_id ] ) ) { $refunded[ 'booking_groups' ][ $group_id ] = array(); }
		$refunded[ 'booking_groups' ][ $group_id ][ 'old_status' ] = $booking_group->state;
		$refunded[ 'booking_groups' ][ $group_id ][ 'new_status' ] = $new_booking_group->state;
	}
	
	$refunded = apply_filters( 'bookacti_bookings_refunded', $refunded, $selected_bookings, $new_selected_bookings );
	
	$message     = ! empty( $refunded[ 'message' ] ) ? $refunded[ 'message' ] : '';
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	$group_by    = ! empty( $row_filters[ 'booking_group_id' ] ) || ! empty( $row_filters[ 'in__booking_group_id' ] ) ? 'booking_group' : 'none';
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => $group_by, 'fetch_meta' => true ) ), 'refund_booking', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows, 'refunded' => $refunded, 'message' => $message ), 'refund_booking' );
}
add_action( 'wp_ajax_bookactiRefundBookings', 'bookacti_controller_refund_bookings' );
add_action( 'wp_ajax_nopriv_bookactiRefundBookings', 'bookacti_controller_refund_bookings' );


/**
 * AJAX Controller - Change bookings status
 * @since 1.16.0
 * @version 1.16.5
 */
function bookacti_controller_change_bookings_status() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_status', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'change_booking_status' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'change_booking_status' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_status' ); }
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_status' ); }
	}
	
	$new_booking_status = ! empty( $_POST[ 'booking_status' ] ) ? sanitize_title_with_dashes( $_POST[ 'booking_status' ] ) : '';
	$new_payment_status = ! empty( $_POST[ 'payment_status' ] ) ? sanitize_title_with_dashes( $_POST[ 'payment_status' ] ) : '';
	$send_notifications = ! empty( $_POST[ 'send_notifications' ] ) ? 1 : 0;
	
	if( ! array_key_exists( $new_booking_status, bookacti_get_booking_statuses() ) ) { $new_booking_status = ''; }
	if( ! array_key_exists( $new_payment_status, bookacti_get_payment_statuses() ) ) { $new_payment_status = ''; }
	
	if( ! $new_booking_status && ! $new_payment_status ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_booking_status', 'message' => esc_html__( 'The selected status is not valid.', 'booking-activities' ) ), 'change_booking_status' );
	}
	
	// Change booking status
	if( $new_booking_status ) {
		// Check the new status for each booking (group)
		foreach( $bookings as $booking ) {
			$can_change = bookacti_booking_status_can_be_changed_to( $booking, $new_booking_status, ! $is_admin );
			if( ! $can_change ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_booking_status', 'message' => esc_html__( 'Some of the selected bookings cannot be changed to this status.', 'booking-activities' ) ), 'change_booking_status' );
			}
		}
		foreach( $booking_groups as $booking_group ) {
			$group_bookings = ! empty( $groups_bookings[ $booking_group->id ] ) ? $groups_bookings[ $booking_group->id ] : array();
			$can_change = bookacti_booking_group_status_can_be_changed_to( $booking_group, $group_bookings, $new_booking_status, ! $is_admin );
			if( ! $can_change ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_booking_group_status', 'message' => esc_html__( 'Some of the selected booking groups cannot be changed to this status.', 'booking-activities' ) ), 'change_booking_status' );
			}
		}
		
		$groups_updated   = $group_ids ? bookacti_update_booking_groups_status( $group_ids, $new_booking_status ) : 0;
		$bookings_updated = $booking_ids ? bookacti_update_bookings_status( $booking_ids, $new_booking_status ) : 0;
		
		if( $bookings_updated === false || $groups_updated === false ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_status', 'message' => esc_html__( 'An error occurred while trying to change the booking status.', 'booking-activities' ) ), 'change_booking_status' );
		}
	}
	
	// Change payment status
	if( $new_payment_status ) {
		$groups_updated   = $group_ids ? bookacti_update_booking_groups_payment_status( $group_ids, $new_payment_status ) : 0;
		$bookings_updated = $booking_ids ? bookacti_update_bookings_payment_status( $booking_ids, $new_payment_status ) : 0;
		
		if( $bookings_updated === false || $groups_updated === false ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_payment_status', 'message' => esc_html__( 'An error occurred while trying to change the booking payment status.', 'booking-activities' ) ), 'change_booking_status' );
		}
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$updated = array( 'bookings' => array(), 'booking_groups' => array() );
	foreach( $bookings as $booking_id => $booking ) {
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		if( $booking->state !== $new_booking->state ) {
			do_action( 'bookacti_booking_status_changed', $new_booking->state, $booking, array() );
			if( $send_notifications ) {
				bookacti_send_booking_status_change_notification( $new_booking->state, $new_booking, $booking );
			}
		}
		if( $booking->payment_status !== $new_booking->payment_status ) {
			do_action( 'bookacti_booking_payment_status_changed', $booking, $new_payment_status, array( 'is_admin' => $is_admin, 'send_notifications' => $send_notifications ) );
		}
		$updated[ 'bookings' ][ $booking_id ] = array(
			'old_status'         => $booking->state,
			'new_status'         => $new_booking->state,
			'old_payment_status' => $booking->payment_status,
			'new_payment_status' => $new_booking->payment_status
		);
	}
	foreach( $booking_groups as $group_id => $booking_group ) {
		if( empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ) ) { continue; }
		$new_booking_group = $new_selected_bookings[ 'booking_groups' ][ $group_id ];
		$group_bookings    = isset( $groups_bookings[ $group_id ] ) ? $groups_bookings[ $group_id ] : array();
		if( $booking_group->state !== $new_booking_group->state ) {
			do_action( 'bookacti_booking_group_status_changed', $new_booking_group->state, $booking_group, $group_bookings, array() );
			if( $send_notifications ) {
				bookacti_send_booking_group_status_change_notification( $new_booking_group->state, $new_booking_group, $booking_group );
			}
		}
		if( $booking_group->payment_status !== $new_booking_group->payment_status ) {
			do_action( 'bookacti_booking_group_payment_status_changed', $group_id, $group_bookings, $new_booking_group->payment_status, array( 'is_admin' => $is_admin, 'send_notifications' => $send_notifications ) );
		}
		$updated[ 'booking_groups' ][ $group_id ] = array(
			'old_status'         => $booking_group->state,
			'new_status'         => $new_booking_group->state,
			'old_payment_status' => $booking_group->payment_status,
			'new_payment_status' => $new_booking_group->payment_status
		);
	}
	
	$updated = apply_filters( 'bookacti_bookings_status_updated', $updated, $selected_bookings, $new_selected_bookings );
	
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	$group_by    = ! empty( $row_filters[ 'booking_group_id' ] ) || ! empty( $row_filters[ 'in__booking_group_id' ] ) ? 'booking_group' : 'none';
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => $group_by, 'fetch_meta' => true ) ), 'change_booking_status', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows, 'updated' => $updated ), 'change_booking_status' );
}
add_action( 'wp_ajax_bookactiChangeBookingsStatus', 'bookacti_controller_change_bookings_status' );


/**
 * AJAX Controller - Change bookings quantity
 * @since 1.16.0
 * @version 1.16.1
 */
function bookacti_controller_change_bookings_quantity() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_change_booking_quantity', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'change_booking_quantity' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'change_booking_quantity' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_quantity' ); }
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_quantity' ); }
	}
	
	$new_quantity = ! empty( $_POST[ 'new_quantity' ] ) ? intval( $_POST[ 'new_quantity' ] ) : 0;
	
	// Check if the quantity is valid
	if( $new_quantity < 1 ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_new_quantity', 'message' => esc_html__( 'The new quantity is not valid.', 'booking-activities' ) ), 'change_booking_quantity' );
	}
	
	// Update bookings (groups) quantity
	$groups_updated   = $group_ids ? bookacti_update_booking_groups_bookings_quantity( $group_ids, $new_quantity ) : 0;
	$bookings_updated = $booking_ids ? bookacti_update_bookings_quantity( $booking_ids, $new_quantity ) : 0;
	
	if( $bookings_updated === false || $groups_updated === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_quantity', 'message' => esc_html__( 'An error occurred while trying to change the booking quantity.', 'booking-activities' ) ), 'change_booking_quantity' );
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$updated = array( 'bookings' => array(), 'booking_groups' => array() );
	foreach( $bookings as $booking_id => $booking ) {
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		if( $booking->quantity !== $new_booking->quantity ) {
			do_action( 'bookacti_booking_quantity_updated', $booking, $new_booking->quantity, $booking->quantity, array( 'is_admin' => $is_admin ) );
		}
		$updated[ 'bookings' ][ $booking_id ] = array(
			'old_quantity' => $booking->quantity,
			'new_quantity' => $new_booking->quantity
		);
	}
	foreach( $booking_groups as $group_id => $booking_group ) {
		if( empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ) ) { continue; }
		$new_booking_group = $new_selected_bookings[ 'booking_groups' ][ $group_id ];
		$group_bookings    = isset( $groups_bookings[ $group_id ] ) ? $groups_bookings[ $group_id ] : array();
		if( $booking_group->quantity !== $new_booking_group->quantity ) {
			do_action( 'bookacti_booking_group_quantity_updated', $group_id, $group_bookings, $new_booking_group->quantity, $booking_group->quantity, array( 'is_admin' => $is_admin ) );
		}
		$updated[ 'booking_groups' ][ $group_id ] = array(
			'old_quantity' => $booking_group->quantity,
			'new_quantity' => $new_booking_group->quantity
		);
	}
	
	$updated = apply_filters( 'bookacti_bookings_quantity_updated', $updated, $selected_bookings, $new_selected_bookings );
	
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	$group_by    = ! empty( $row_filters[ 'booking_group_id' ] ) || ! empty( $row_filters[ 'in__booking_group_id' ] ) ? 'booking_group' : 'none';
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => $group_by, 'fetch_meta' => true ) ), 'change_booking_quantity', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows, 'updated' => $updated ), 'change_booking_quantity' );
}
add_action( 'wp_ajax_bookactiChangeBookingsQuantity', 'bookacti_controller_change_bookings_quantity' );


/**
 * AJAX Controller - Get reschedule booking system data by booking selection
 * @since 1.8.0 (was bookacti_controller_get_booking_data)
 * @version 1.16.24
 */
function bookacti_controller_get_reschedule_booking_system_data() {
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	
	if( ! $bookings ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'get_reschedule_booking_system_data' );
	}
	
	// Check capabilities for each booking (ignore booking groups)
	$highest_quantity = 1;
	$form_ids = $activity_ids = array();
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'change_booking_quantity' ); }
		
		if( ! empty( $booking->form_id ) ) {
			$form_ids[] = intval( $booking->form_id );
		}
		if( ! empty( $booking->activity_id ) ) {
			$activity_ids[] = intval( $booking->activity_id );
		}
		if( $booking->quantity > $highest_quantity ) {
			$highest_quantity = intval( $booking->quantity );
		}
	}
	$form_ids     = bookacti_ids_to_array( $form_ids );
	$activity_ids = bookacti_ids_to_array( $activity_ids );
	
	// Get calendar form field data
	$calendar_fields_data = array();
	foreach( $form_ids as $form_id ) {
		$calendar_field_data = bookacti_get_form_field_data_by_name( $form_id, 'calendar' );
		if( $calendar_field_data ) {
			$calendar_fields_data[ $form_id ] = $calendar_field_data;
		}
	}
	
	// Get booking system data based on calendar form field data
	$admin_reschedule_scope = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'admin_reschedule_scope' );
	$activities_meta        = $activity_ids ? bookacti_get_metadata( 'activity', $activity_ids ) : array();
	$calendar_field_data    = count( $calendar_fields_data ) === 1 ? reset( $calendar_fields_data ) : array();
	$init_atts              = bookacti_get_calendar_field_booking_system_attributes( $calendar_field_data );
	$atts                   = $init_atts;
	$mixed_data             = array();

	// Set compulsory data
	$atts[ 'id' ]                       = 'bookacti-booking-system-reschedule';
	$atts[ 'form_action' ]              = 'default';
	$atts[ 'when_perform_form_action' ] = 'on_submit';
	$atts[ 'multiple_bookings' ]        = 0;
	$atts[ 'auto_load' ]                = 0;
	
	// Find the calendars and activities corresponding to the reschedule scope
	$_any_nb = $all_nb = $form_without_calendars_nb = 0;
	$allowed_activities_per_booking = $allowed_calendars_per_booking = array();
	foreach( $bookings as $booking_id => $booking ) {
		$form_id       = ! empty( $booking->form_id ) ? intval( $booking->form_id ) : 0;
		$activity_id   = ! empty( $booking->activity_id ) ? intval( $booking->activity_id ) : 0;
		$activity_meta = ! empty( $activities_meta[ $activity_id ] ) ? $activities_meta[ $activity_id ] : array();
		if( ! $activity_meta ) { continue; }
		
		$reschedule_scope        = ! empty( $activity_meta[ 'reschedule_scope' ] ) ? $activity_meta[ 'reschedule_scope' ] : 'form_self';
		$reschedule_activity_ids = ! empty( $activity_meta[ 'reschedule_activity_ids' ] ) ? bookacti_ids_to_array( $activity_meta[ 'reschedule_activity_ids' ] ) : array();

		// For administrators, keep the widest reschedule scope
		if( $is_admin ) {
			$booking_admin_reschedule_scope = $admin_reschedule_scope;
			if( strpos( $reschedule_scope, 'all_' ) !== false && strpos( $admin_reschedule_scope, 'form_' ) !== false ) {
				$booking_admin_reschedule_scope = str_replace( 'form_', 'all_', $admin_reschedule_scope );
			}
			if( strpos( $reschedule_scope, '_custom' ) !== false && strpos( $admin_reschedule_scope, '_self' ) !== false ) {
				$booking_admin_reschedule_scope = str_replace( '_self', '_custom', $admin_reschedule_scope );
			}
			else if( strpos( $reschedule_scope, '_any' ) !== false && strpos( $admin_reschedule_scope, '_self' ) !== false ) {
				$booking_admin_reschedule_scope = str_replace( '_self', '_any', $admin_reschedule_scope );
			}
			$reschedule_scope = $booking_admin_reschedule_scope;
		}
		
		if( strpos( $reschedule_scope, '_any' ) !== false ) {
			++$_any_nb;
			if( strpos( $reschedule_scope, 'form_' ) !== false && ! empty( $calendar_fields_data[ $form_id ][ 'activities' ] ) ) {
				$allowed_activities_per_booking[ $booking_id ] = $calendar_fields_data[ $form_id ][ 'activities' ];
			}
		} else if( strpos( $reschedule_scope, '_custom' ) !== false ) {
			$allowed_activities_per_booking[ $booking_id ] = array_unique( array_merge( array( $activity_id ), $reschedule_activity_ids ) );
			if( strpos( $reschedule_scope, 'form_' ) !== false && ! empty( $calendar_fields_data[ $form_id ][ 'activities' ] ) ) {
				$allowed_activities_per_booking[ $booking_id ] = array_intersect( $calendar_fields_data[ $form_id ][ 'activities' ], $allowed_activities_per_booking[ $booking_id ] );
			}
		} else if( strpos( $reschedule_scope, '_self' ) !== false ) {
			$allowed_activities_per_booking[ $booking_id ] = array( $activity_id );
			if( strpos( $reschedule_scope, 'form_' ) !== false && ! empty( $calendar_fields_data[ $form_id ][ 'activities' ] ) ) {
				$allowed_activities_per_booking[ $booking_id ] = array_intersect( $calendar_fields_data[ $form_id ][ 'activities' ], $allowed_activities_per_booking[ $booking_id ] );
			}
		}
		
		if( strpos( $reschedule_scope, 'all_' ) !== false ) {
			++$all_nb;
		} else if( strpos( $reschedule_scope, 'form_' ) !== false ) {
			if( ! empty( $calendar_fields_data[ $form_id ][ 'calendars' ] ) ) {
				$allowed_calendars_per_booking[ $booking_id ] = $calendar_fields_data[ $form_id ][ 'calendars' ];
			} else {
				++$form_without_calendars_nb;
			}
		}
	}
	
	$i = 0;
	$allowed_activities = array();
	foreach( $allowed_activities_per_booking as $activity_ids ) {
		if( $i === 0 ) {
			$allowed_activities = $activity_ids;
		} else {
			$allowed_activities = array_intersect( $allowed_activities, $activity_ids );
		}
		++$i;
	}
	
	// Keep only the activities that are compatible with all the selected bookings
	$atts[ 'activities' ] = $allowed_activities ? array_values( bookacti_ids_to_array( $allowed_activities ) ) : array();
	if( ! $atts[ 'activities' ] && count( $bookings ) !== $_any_nb ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_activities', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) . ' ' . esc_html__( 'No activities match all the selected bookings.', 'booking-activities' ) ), 'get_reschedule_booking_system_data' );
	}
	
	// Display events from all calendars if the reschedule scope is set accordingly
	if( count( $bookings ) === $all_nb ) {
		$atts[ 'form_id' ]      = 0;
		$forms                  = $form_ids ? bookacti_get_forms( bookacti_format_form_filters( array( 'id' => $form_ids ) ) ) : array();
		$templates_per_activity = bookacti_fetch_activities_with_templates_association();
		
		$all_calendar_ids = bookacti_ids_to_array( array_keys( bookacti_fetch_templates( array(), 0 ) ) );
		$allowed_calendars_per_user = $allowed_calendars_per_form = $allowed_calendars_for_current_user = array();
		if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
			foreach( $forms as $form ) {
				$form_id = ! empty( $form->id ) ? intval( $form->id ) : 0;
				$form_author_id = ! empty( $form->user_id ) ? intval( $form->user_id ) : 0;
				if( $form_author_id && ! isset( $allowed_calendars_per_user[ $form_author_id ] ) ) {
					$allowed_calendars_per_user[ $form_author_id ] = bookacti_ids_to_array( array_keys( bookacti_fetch_templates( array(), $form_author_id ) ) );
				}
				$allowed_calendars_per_form[ $form_id ] = $allowed_calendars_per_user[ $form_author_id ];
			}
		} else {
			$allowed_calendars_for_current_user = bookacti_ids_to_array( array_keys( bookacti_fetch_templates() ) );
		}
		
		foreach( $bookings as $booking_id => $booking ) {
			$form_id = ! empty( $booking->form_id ) ? intval( $booking->form_id ) : 0;
			if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
				$allowed_calendars_per_booking[ $booking_id ] = isset( $allowed_calendars_per_form[ $form_id ] ) ? $allowed_calendars_per_form[ $form_id ] : $all_calendar_ids;
			} else {
				$allowed_calendars_per_booking[ $booking_id ] = $allowed_calendars_for_current_user;
			}
			
			if( empty( $allowed_activities_per_booking[ $booking_id ] ) ) { continue; }

			$template_ids = array();
			foreach( $allowed_activities_per_booking[ $booking_id ] as $activity_id ) {
				if( ! empty( $templates_per_activity[ $activity_id ][ 'template_ids' ] ) ) {
					$template_ids = array_merge( $template_ids, $templates_per_activity[ $activity_id ][ 'template_ids' ] );
				}
			}

			$template_ids = bookacti_ids_to_array( $template_ids );
			if( ! $template_ids ) { continue; }
			
			$allowed_calendars_per_booking[ $booking_id ] = empty( $allowed_calendars_per_booking[ $booking_id ] ) ? $template_ids : bookacti_ids_to_array( array_intersect( $template_ids, $allowed_calendars_per_booking[ $booking_id ] ) );
		}
	}
	
	$i = 0;
	$allowed_calendars = array();
	foreach( $allowed_calendars_per_booking as $calendar_ids ) {
		if( $i === 0 ) {
			$allowed_calendars = $calendar_ids;
		} else {
			$allowed_calendars = array_intersect( $allowed_calendars, $calendar_ids );
		}
		++$i;
	}
	
	// Keep only the calendars that are compatible with all the selected bookings
	$atts[ 'calendars' ] = $allowed_calendars ? array_values( bookacti_ids_to_array( $allowed_calendars ) ) : array();
	if( ! $atts[ 'calendars' ] ) {
		if( $form_without_calendars_nb ) {
			bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_form', 'message' => esc_html__( 'The selected booking\'s booking form could not be retrieved.', 'booking-activities' ) ), 'get_reschedule_booking_system_data' );
		}
		
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_calendars', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) . ' ' . esc_html__( 'No calendars match all the selected bookings.', 'booking-activities' ) ), 'get_reschedule_booking_system_data' );
	}
	
	// Change the display data according to the displayed calendars
	if( count( $atts[ 'calendars' ] ) > 1 && array_diff( $atts[ 'calendars' ], $init_atts[ 'calendars' ] ) ) {
		$mixed_data = bookacti_get_mixed_template_data( $atts[ 'calendars' ] );
		$atts[ 'display_data' ][ 'slotMinTime' ] = ! empty( $mixed_data[ 'settings' ][ 'slotMinTime' ] ) ? $mixed_data[ 'settings' ][ 'slotMinTime' ] : '00:00';
		$atts[ 'display_data' ][ 'slotMaxTime' ] = ! empty( $mixed_data[ 'settings' ][ 'slotMaxTime' ] ) ? $mixed_data[ 'settings' ][ 'slotMaxTime' ] : '00:00';
	}
	
	// On the backend, display past events and grouped events and make them all bookable
	if( $is_admin ) {
		$atts[ 'groups_single_events' ] = 1;
		$atts[ 'start' ]                = '';
		$atts[ 'end' ]                  = '';
		$atts[ 'trim' ]                 = 1;
		$atts[ 'past_events' ]          = 1;
		$atts[ 'past_events_bookable' ] = 1;
	}

	// Add the rescheduled booking data to the booking system data
	$atts[ 'rescheduled_bookings_data' ] = apply_filters( 'bookacti_rescheduled_bookings_data', $bookings );

	$atts = apply_filters( 'bookacti_reschedule_booking_system_attributes', $atts, $bookings, $init_atts, $mixed_data );
	
	bookacti_send_json( array( 'status' => 'success', 'booking_system_data' => $atts, 'quantity' => $highest_quantity ), 'get_reschedule_booking_system_data' );
}
add_action( 'wp_ajax_bookactiGetRescheduleBookingSystemData', 'bookacti_controller_get_reschedule_booking_system_data' );
add_action( 'wp_ajax_nopriv_bookactiGetRescheduleBookingSystemData', 'bookacti_controller_get_reschedule_booking_system_data' );


/**
 * AJAX Controller - Reschedule bookings
 * @since 1.16.0
 * @version 1.16.24
 */
function bookacti_controller_reschedule_bookings() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_reschedule_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'reschedule_booking' ); }
	
	$is_admin           = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings  = bookacti_get_selected_bookings();
	$bookings           = $selected_bookings[ 'bookings' ];
	$booking_ids        = bookacti_ids_to_array( array_keys( $bookings ) );
	$picked_events_raw  = isset( $_POST[ 'picked_events' ] ) ? ( is_array( $_POST[ 'picked_events' ] ) ? $_POST[ 'picked_events' ] : ( is_string( $_POST[ 'picked_events' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'picked_events' ] ), true ) : array() ) ) : array();
	$picked_events      = $picked_events_raw ? bookacti_format_picked_events( $picked_events_raw ) : array();
	$send_notifications = $is_admin && isset( $_POST[ 'send_notifications' ] ) ? boolval( $_POST[ 'send_notifications' ] ) : true;
	
	if( ! $bookings || ! $booking_ids ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'reschedule_booking' );
	}
	
	// Check capabilities for each booking
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'reschedule_booking' ); }
	}
	
	// Check picked event
	if( ! $picked_events || ! empty( $picked_events[ 0 ][ 'group_id' ] ) || empty( $picked_events[ 0 ][ 'id' ] ) ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_event_selected', 'message' => esc_html__( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' ) ), 'reschedule_booking' );
	}
	
	// Validate the reschedule booking form fields
	$form_fields_validated = bookacti_validate_reschedule_form_fields( $bookings );
	if( $form_fields_validated[ 'status' ] !== 'success' ) {
		$return_array[ 'error' ]    = 'invalid_form_fields';
		$return_array[ 'messages' ] = $form_fields_validated[ 'messages' ];
		$return_array[ 'message' ]  = implode( '</li><li>', $form_fields_validated[ 'messages' ] );
		bookacti_send_json( $return_array, 'reschedule_booking' );
	}
	
	// Check if each booking can be rescheduled to the picked event
	foreach( $bookings as $booking_id => $booking ) {
		// Check if the desired event is eligible according to the current booking
		$can_be_rescheduled = bookacti_booking_can_be_rescheduled_to( $booking, $picked_events[ 0 ][ 'id' ], $picked_events[ 0 ][ 'start' ], $picked_events[ 0 ][ 'end' ], ! $is_admin );
		if( $can_be_rescheduled[ 'status' ] !== 'success' ) {
			bookacti_send_json( $can_be_rescheduled, 'reschedule_booking' );
		}
		
		// Validate picked events
		$reschedule_form_values = apply_filters( 'bookacti_reschedule_booking_form_values', array(
			'quantity' => intval( $booking->quantity ),
			'is_admin' => $is_admin, 
			'context'  => 'reschedule', 
		), $booking, $picked_events );
		
		$validated = bookacti_validate_picked_events( $picked_events, $reschedule_form_values );

		if( $validated[ 'status' ] !== 'success' ) {
			$message_prefix = count( $bookings ) > 1 ? sprintf( esc_html__( 'Booking #%s', 'booking-activities' ), $booking_id ) . ': ' : '';
			$messages = ! empty( $validated[ 'message' ] ) ? array( $validated[ 'message' ] ) : array();
			foreach( $validated[ 'messages' ] as $error => $error_messages ) {
				if( ! is_array( $error_messages ) ) { $error_messages = array( $error_messages ); }
				$messages = array_merge( $messages, $error_messages );
			}
			$message = $messages ? $message_prefix . implode( '</li><li>' . $message_prefix, $messages ) : '';
			bookacti_send_json( array( 'status' => 'failed', 'error' => $validated[ 'error' ], 'message' => $message ), 'reschedule_booking' );
		}
	}
	
	// Let third party plugins do their stuff before rescheduling
	do_action( 'bookacti_before_rescheduling_bookings', $selected_bookings, $picked_events );
	
	// Change the bookings event
	$rescheduled = bookacti_reschedule_bookings( $booking_ids, $picked_events[ 0 ][ 'id' ], $picked_events[ 0 ][ 'start' ], $picked_events[ 0 ][ 'end' ] );

	if( $rescheduled === 0 ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_changes', 'message' => esc_html__( 'You must select a different event than the current one.', 'booking-activities' ) ), 'reschedule_booking' );
	} else if( $rescheduled === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'reschedule_failed', 'message' => esc_html__( 'An error occurred while trying to reschedule the bookings.', 'booking-activities' ) ), 'reschedule_booking' );
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$updated = array( 'bookings' => array() );
	foreach( $bookings as $booking_id => $booking ) {
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		
		$sent = false;
		if( $send_notifications ) {
			if( $booking->event_id != $new_booking->event_id
			||  $booking->event_start !== $new_booking->event_start
			||  $booking->event_end !== $new_booking->event_end ) {
				$sent = true;
			}
		}
		
		$updated[ 'bookings' ][ $booking_id ] = array(
			'old_event_id'      => $booking->event_id,
			'new_event_id'      => $new_booking->event_id,
			'old_event_start'   => $booking->event_start,
			'new_event_start'   => $new_booking->event_start,
			'old_event_end'     => $booking->event_end,
			'new_event_end'     => $new_booking->event_end,
			'notification_sent' => $sent
		);
	}
	
	$updated = apply_filters( 'bookacti_bookings_rescheduled', $updated, $selected_bookings, $new_selected_bookings );
	
	// Send notifications
	foreach( $updated[ 'bookings' ] as $booking_id => $booking_updated_data ) {
		if( empty( $booking_updated_data[ 'notification_sent' ] )
		||  empty( $selected_bookings[ 'bookings' ][ $booking_id ] )
		||  empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) { continue; }

		$old_booking = $selected_bookings[ 'bookings' ][ $booking_id ];
		$new_booking = $new_selected_bookings[ 'bookings' ][ $booking_id ];
		
		bookacti_send_booking_rescheduled_notification( $new_booking, $old_booking );
	}
	
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	$group_by    = ! empty( $row_filters[ 'booking_group_id' ] ) || ! empty( $row_filters[ 'in__booking_group_id' ] ) ? 'booking_group' : 'none';
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => $group_by, 'fetch_meta' => true ) ), 'reschedule_booking', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows, 'updated' => $updated ), 'reschedule_booking' );
}
add_action( 'wp_ajax_bookactiRescheduleBookings', 'bookacti_controller_reschedule_bookings' );
add_action( 'wp_ajax_nopriv_bookactiRescheduleBookings', 'bookacti_controller_reschedule_bookings' );


/**
 * AJAX Controller - Send a notification for bookings (groups)
 * @since 1.16.0
 */
function bookacti_controller_send_bookings_notification() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_send_booking_notification', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'send_booking_notification' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'send_booking_notification' );
	}
	
	// Check capabilities for each booking (group)
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'send_booking_notification' ); }
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'send_booking_notification' ); }
	}
	
	$notification_id = ! empty( $_POST[ 'notification_id' ] ) ? sanitize_title_with_dashes( $_POST[ 'notification_id' ] ) : '';
	
	// Check if the notification is valid
	if( ! $notification_id ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'invalid_notification', 'message' => esc_html__( 'The selected notification is not valid.', 'booking-activities' ) ), 'send_booking_notification' );
	}
	
	do_action( 'bookacti_before_send_bookings_notification', $notification_id, $selected_bookings );
	
	$sent = array( 'bookings' => array(), 'booking_groups' => array() );
	foreach( $selected_bookings[ 'bookings' ] as $booking_id => $booking ) {
		bookacti_send_notification( $notification_id, $booking_id, 'single', array( 'notification' => array( 'active' => true ) ) );
		$sent[ 'bookings' ][ $booking_id ] = true;
	}
	foreach( $selected_bookings[ 'booking_groups' ] as $booking_group_id => $booking_group ) {
		bookacti_send_notification( $notification_id, $booking_group_id, 'group', array( 'notification' => array( 'active' => true ) ) );
		$sent[ 'booking_groups' ][ $booking_group_id ] = true;
	}
	
	$sent = apply_filters( 'bookacti_bookings_notification_sent', $sent, $notification_id, $selected_bookings );
	
	$sent_nb = count( $sent[ 'bookings' ] ) + count( $sent[ 'booking_groups' ] );
	/* translators: %s = number of notifications sent */
	$message = sprintf( esc_html__( '%s notifications have been sent.', 'booking-activities' ), $sent_nb );
	$async   = apply_filters( 'bookacti_allow_async_notifications', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ) );
	if( $async ) {
		/* translators: %s = link labelled "Trigger manually" */
		$message .= '</li><li>' . sprintf( esc_html__( 'Please be patient, the notifications are sent asynchronously (%s).', 'booking-activities' ), '<a href="' . esc_url( get_site_url() . '/wp-cron.php?bookacti_send_async_notifications=1' ) . '" target="_blank">' . esc_html__( 'Trigger manually', 'booking-activities' ) . '</a>' );
	
		if( $sent_nb > 1 ) {
			$message .= '</li><li>' . esc_html__( 'Notifications sent to the same recipient will be merged.', 'booking-activities' );
		}
		$message .= '</li><li>' . esc_html__( 'You must wait 3 minutes before you can send the same notification again to the same recipient.', 'booking-activities' );
	}
	
	bookacti_send_json( array( 'status' => 'success', 'message' => $message, 'sent' => $sent ), 'send_booking_notification' );
}
add_action( 'wp_ajax_bookactiSendBookingsNotification', 'bookacti_controller_send_bookings_notification' );


/**
 * AJAX Controller - Delete bookings
 * @since 1.16.0
 * @version 1.16.1
 */
function bookacti_controller_delete_bookings() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_delete_booking', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'delete_booking' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$bookings          = $selected_bookings[ 'bookings' ];
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	$booking_ids       = bookacti_ids_to_array( array_keys( $bookings ) );
	
	if( ! $bookings && ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'delete_booking' );
	}
	
	// Check capabilities for each booking (group)
	$all_bookings = $bookings;
	foreach( $bookings as $booking ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'delete_booking' ); }
	}
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$all_bookings += $group_bookings;
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings, false );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'delete_booking' ); }
	}
	
	do_action( 'bookacti_before_delete_bookings', $selected_bookings );

	// Delete bookings (groups)
	$all_booking_ids  = $all_bookings ? bookacti_ids_to_array( array_keys( $all_bookings ) ) : array();
	$groups_deleted   = $group_ids ? bookacti_delete_booking_groups( $group_ids ) : 0;
	$bookings_deleted = $all_booking_ids ? bookacti_delete_bookings( $all_booking_ids ) : 0;
	
	if( $bookings_deleted === false || $groups_deleted === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_delete_bookings', 'message' => esc_html__( 'An error occurred while trying to delete the bookings.', 'booking-activities' ) ), 'delete_booking' );
	}
	
	$new_selected_bookings = bookacti_get_selected_bookings( true );
	
	// Check if the bookings have been updated
	$deleted = array( 'bookings' => array(), 'booking_groups' => array() );
	foreach( $bookings as $booking_id => $booking ) {
		$deleted[ 'bookings' ][ $booking_id ] = array(
			'booking' => empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] )
		);
		if( empty( $new_selected_bookings[ 'bookings' ][ $booking_id ] ) ) {
			do_action( 'bookacti_booking_deleted', $booking );
		}
	}
	foreach( $booking_groups as $group_id => $booking_group ) {
		$deleted[ 'booking_groups' ][ $group_id ] = array(
			'group'          => empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ),
			'group_bookings' => empty( $new_selected_bookings[ 'groups_bookings' ][ $group_id ] )
		);
		if( empty( $new_selected_bookings[ 'booking_groups' ][ $group_id ] ) ) {
			$group_bookings = isset( $groups_bookings[ $group_id ] ) ? $groups_bookings[ $group_id ] : array();
			do_action( 'bookacti_booking_group_deleted', $booking_group, $group_bookings );
		}
	}
	
	$deleted = apply_filters( 'bookacti_bookings_deleted', $deleted, $selected_bookings, $new_selected_bookings );
	
	bookacti_send_json( array( 'status' => 'success', 'deleted' => $deleted ), 'delete_booking' );
}
add_action( 'wp_ajax_bookactiDeleteBookings', 'bookacti_controller_delete_bookings' );


/**
 * Allow non logged in users to edit their bookings thanks to an authentication key in the URL
 * @since 1.16.0
 * @param bool $is_allowed
 * @param object $booking
 * @param string $context
 * @return bool
 */
function bookacti_allow_to_manage_bookings_with_auth_key( $is_allowed, $booking, $context = '' ) {
	if( empty( $_REQUEST[ 'user_auth_key' ] ) || $is_allowed ) { return $is_allowed; }
	
	$user_email = sanitize_email( bookacti_decrypt( sanitize_text_field( $_REQUEST[ 'user_auth_key' ] ), 'user_auth' ) );
	if( ! is_email( $user_email ) ) { $user_email = ''; }
	if( ! $user_email ) { return $is_allowed; }
	
	$booking_user_id    = ! empty( $booking->user_id ) ? $booking->user_id : 0;
	$booking_user_email = is_email( $booking_user_id ) ? $booking_user_id : '';
	if( is_numeric( $booking_user_id ) ) {
		$user = get_user_by( 'id', $booking_user_id );
		if( $user && ! empty( $user->user_email ) && is_email( $user->user_email ) ) {
			$booking_user_email = $user->user_email;
		}
	}
	
	if( $booking_user_email === $user_email ) {
		$is_allowed = true;
	}
	
	return $is_allowed;
}
add_filter( 'bookacti_allow_others_booking_changes', 'bookacti_allow_to_manage_bookings_with_auth_key', 10, 3 );




// BOOKING GROUPS

/**
 * AJAX Controller - Get grouped bookings rows
 * @since 1.7.4 (was bookacti_controller_get_booking_rows)
 * @version 1.16.1
 */
function bookacti_controller_get_grouped_bookings_rows() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_rows', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_grouped_bookings_rows' ); }
	
	$is_admin          = current_user_can( 'bookacti_edit_bookings' ) && ! empty( $_POST[ 'is_admin' ] );
	$selected_bookings = bookacti_get_selected_bookings();
	$booking_groups    = $selected_bookings[ 'booking_groups' ];
	$groups_bookings   = $selected_bookings[ 'groups_bookings' ];
	$group_ids         = bookacti_ids_to_array( array_keys( $booking_groups ) );
	
	if( ! $booking_groups ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_not_found', 'message' => esc_html__( 'Invalid booking selection.', 'booking-activities' ) ), 'get_grouped_bookings_rows' );
	}
	
	// Check capabilities for each booking group
	foreach( $booking_groups as $booking_group_id => $booking_group ) {
		$group_bookings = ! empty( $groups_bookings[ $booking_group_id ] ) ? $groups_bookings[ $booking_group_id ] : array();
		$is_allowed = bookacti_user_can_manage_booking_group( $group_bookings );
		if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'get_grouped_bookings_rows' ); }
	}
	
	$context     = ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : ( $is_admin ? 'admin_booking_list' : 'user_booking_list' );
	$columns_raw = isset( $_POST[ 'columns' ] ) ? ( is_array( $_POST[ 'columns' ] ) ? $_POST[ 'columns' ] : ( is_string( $_POST[ 'columns' ] ) ? bookacti_maybe_decode_json( stripslashes( $_POST[ 'columns' ] ), true ) : array() ) ) : array();
	$columns     = $columns_raw ? bookacti_str_ids_to_array( $columns_raw ) : array();
	$row_filters = bookacti_get_selected_bookings_filters();
	if( ! empty( $row_filters[ 'in__booking_id' ] ) ) { $row_filters[ 'in__booking_id' ] = array(); }
	$row_filters = $row_filters ? apply_filters( 'bookacti_booking_action_row_filters', array_merge( $row_filters, array( 'group_by' => 'none', 'fetch_meta' => true ) ), 'get_grouped_bookings_rows', $context ) : array();
	$rows        = $row_filters ? bookacti_get_booking_list_rows_according_to_context( $context, $row_filters, $columns ) : '';
	
	if( ! $rows ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_rows', 'message' => esc_html__( 'An error occurred while trying to retrieve the selected groups bookings.', 'booking-activities' ) ), 'get_grouped_bookings_rows' );
	}

	bookacti_send_json( array( 'status' => 'success', 'rows' => $rows ), 'get_grouped_bookings_rows' );
}
add_action( 'wp_ajax_bookactiGetGroupedBookingsRows', 'bookacti_controller_get_grouped_bookings_rows' );


/**
 * Trigger bookacti_booking_status_changed for each bookings of a group
 * @since 1.16.0 (was bookacti_trigger_booking_state_change_for_each_booking_of_a_group)
 * @version 1.16.5
 * @param string $new_status
 * @param object $booking_group
 * @param array $grouped_bookings
 * @param array $args
 */
function bookacti_trigger_group_bookings_status_changed_hook( $new_status, $booking_group, $grouped_bookings, $args = array() ) {
	if( ! $new_status ) { return; }
	$args[ 'booking_group_status_changed' ] = true;
	foreach( $grouped_bookings as $grouped_booking ) {
		if( $grouped_booking->state !== $new_status ) {
			do_action( 'bookacti_booking_status_changed', $new_status, $grouped_booking, $args );
		}
	}
}
add_action( 'bookacti_booking_group_status_changed', 'bookacti_trigger_group_bookings_status_changed_hook', 10, 4 );




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
 * @version 1.16.0
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
		'per_page'    => $export_settings[ 'per_page' ]
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
	if( $url_atts ) { 
		$home_url  = home_url();
		$export_id = bookacti_insert_export( array( 'type' => 'booking_' . $export_type, 'user_id' => $current_user_id, 'args' => $url_atts ) );
		$short_url_atts = array(
			'action'    => $url_atts[ 'action' ],
			'key'       => $url_atts[ 'key' ],
			'export_id' => $export_id
		);
		$export_url = add_query_arg( $short_url_atts, $home_url );
	}
	
	// Update settings
	update_user_meta( $current_user_id, 'bookacti_bookings_export_settings', $export_settings );
	
	bookacti_send_json( array( 'status' => 'success', 'url' => esc_url_raw( $export_url ), 'message' => $message ), 'export_bookings_url' ); 
}
add_action( 'wp_ajax_bookactiExportBookingsUrl', 'bookacti_controller_generate_export_bookings_url' );


/**
 * Export bookings according to filters
 * @since 1.6.0
 * @version 1.16.0
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
	
	// Get the export
	$args      = array();
	$export_id = ! empty( $_REQUEST[ 'export_id' ] ) ? intval( $_REQUEST[ 'export_id' ] ) : 0;
	$export    = $export_id ? bookacti_get_export( $export_id ) : array();
	if( ! $export ) { esc_html_e( 'Invalid or expired export ID.', 'booking-activities' ); exit; }
	
	// Check the user secret key
	if( intval( $export[ 'user_id' ] ) !== intval( $user_id ) ) { esc_html_e( 'The secret key doesn\'t match the user who has generated the export.', 'booking-activities' ); exit; }
	
	// Allow to override the booking filters and some parameters with URL parameters
	$override_args = array_intersect_key( $_REQUEST, bookacti_get_default_booking_filters() );
	if( ! empty( $_REQUEST[ 'columns' ] ) ) {
		$override_args[ 'columns' ] = ! empty( $_REQUEST[ 'columns' ] ) ? bookacti_str_ids_to_array( $_REQUEST[ 'columns' ] ) : array();
	}
	if( ! empty( $_REQUEST[ 'raw' ] ) ) {
		$override_args[ 'raw' ] = intval( $_REQUEST[ 'raw' ] );
	}
	if( ! empty( $_REQUEST[ 'filename' ] ) ) {
		$override_args[ 'filename' ] = sanitize_title_with_dashes( $_REQUEST[ 'filename' ] );
	}
	if( ! empty( $_REQUEST[ 'locale' ] ) ) {
		$override_args[ 'locale' ] = sanitize_title_with_dashes( $_REQUEST[ 'locale' ] );
	}
	$args = array_merge( $export[ 'args' ], $override_args );
	
	$args[ 'sequence' ] = $export[ 'sequence' ];
	if( empty( $args[ 'filename' ] ) ) { $args[ 'filename' ] = 'booking-activities-bookings-' . $export_id; }
	
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
	if( $filters[ 'event_id' ] && ! $filters[ 'booking_group_id' ] && $filters[ 'group_by' ] !== 'none' ) { $filters[ 'booking_group_id' ] = 'none'; }
	
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