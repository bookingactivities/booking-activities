<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS
	
	// Get the HTML booking list for a given event
	/**
	 * AJAX Controller - Get booking rows
	 * 
	 * @version 1.3.0
	 */
	function bookacti_controller_get_booking_rows() {
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_booking_rows', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_bookings' );

		if( $is_nonce_valid && $is_allowed ) {
			$Bookings_List_Table = new Bookings_List_Table();
			$Bookings_List_Table->prepare_items( array(), true );
			$rows = $Bookings_List_Table->get_rows_or_placeholder();
			
			if( $rows ) {
				wp_send_json( array( 'status' => 'success', 'rows' => $rows ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'no_rows' ) );
			}
        } else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiGetBookingRows', 'bookacti_controller_get_booking_rows' );

	

// BOOKING ACTIONS
	// SINGLE BOOKING
		/**
		 * AJAX Controller - Cancel a booking
		 * @version 1.5.9
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
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_already_cancelled', 'message' => esc_html__( 'The booking is already cancelled.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking' );
			}
			
			$can_be_cancelled = bookacti_booking_can_be_cancelled( $booking_id );
			if( ! $can_be_cancelled ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking', 'message' => esc_html__( 'The booking cannot be cancelled.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking' );
			}

			$cancelled = bookacti_cancel_booking( $booking_id );
			if( ! $cancelled ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking', 'message' => esc_html__( 'An error occured while trying to cancel the booking.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking' );
			}

			do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => false ) );

			$allow_refund	= bookacti_booking_can_be_refunded( $booking_id );
			$actions_html	= bookacti_get_booking_actions_html( $booking_id, 'front' );
			$formatted_state= bookacti_format_booking_state( 'cancelled', false );
			
			bookacti_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state ), 'cancel_booking' );
		}
		add_action( 'wp_ajax_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );
		add_action( 'wp_ajax_nopriv_bookactiCancelBooking', 'bookacti_controller_cancel_booking' );


		/**
		 * AJAX Controller - Get possible actions to refund a booking
		 */
		function bookacti_controller_get_refund_actions_html() {

			$booking_id = intval( $_POST[ 'booking_id' ] );

			// Check nonce, capabilities and other params
			$is_nonce_valid	= check_ajax_referer( 'bookacti_get_refund_actions_html', 'nonce', false );
			$is_allowed		= bookacti_user_can_manage_booking( $booking_id );
			$can_be_refund	= bookacti_booking_can_be_refunded( $booking_id );

			if( $is_nonce_valid && $is_allowed && $can_be_refund ) {

				$refund_actions_array	= bookacti_get_refund_actions_by_booking_id( $booking_id );
				$refund_actions_html	= bookacti_get_refund_dialog_html_by_booking_id( $booking_id );

				if( ! empty( $refund_actions_html ) ) {

					wp_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array ) );

				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );
		add_action( 'wp_ajax_nopriv_bookactiGetBookingRefundActionsHTML', 'bookacti_controller_get_refund_actions_html' );


		/**
		 * AJAX Controller - Refund a booking
		 * @version 1.5.8
		 */
		function bookacti_controller_refund_booking() {

			$booking_id			= intval( $_POST[ 'booking_id' ] );
			$is_admin			= intval( $_POST[ 'is_admin' ] );
			$sanitized_action	= sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
			$refund_action		= array_key_exists( $sanitized_action, bookacti_get_refund_actions_by_booking_id( $booking_id ) ) ? $sanitized_action : 'email';

			// Check nonce, capabilities and other params
			$is_nonce_valid	= check_ajax_referer( 'bookacti_refund_booking', 'nonce', false );
			$is_allowed		= bookacti_user_can_manage_booking( $booking_id );
			$can_be_refund	= bookacti_booking_can_be_refunded( $booking_id, $refund_action );

			if( $is_nonce_valid && $is_allowed && $can_be_refund ) {

				$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );

				if( $refund_action === 'email' ) {
					$refunded = bookacti_send_email_refund_request( $booking_id, 'single', $refund_message );
					if( $refunded ) {
						$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested' );
					} else {
						$refunded = array( 
							'status'	=> 'failed', 
							'error'		=> 'cannot_send_email', 
							'message'	=> esc_html__( 'An error occured while trying to send the email.', BOOKACTI_PLUGIN_NAME ) 
						);
					}
				} else {
					$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $booking_id, 'single', $refund_action, $refund_message );
				}

				if( $refunded[ 'status' ] === 'success' ) {
					$new_state = ! empty( $refunded[ 'new_state' ] ) ? $refunded[ 'new_state' ] : 'refunded';
					$updated = bookacti_update_booking_state( $booking_id, $new_state );

					// Hook status changes
					if( $updated ) {
						do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
					}

					// Get new booking actions
					$admin_or_front = $is_admin ? 'admin' : 'front';
					$actions_html	= bookacti_get_booking_actions_html( $booking_id, $admin_or_front );
					$refunded[ 'new_actions_html' ] = $actions_html;

					// Get new booking state formatted
					$refunded[ 'formatted_state' ] = bookacti_format_booking_state( $new_state, $is_admin );
				}

				bookacti_send_json( $refunded, 'refund_booking' );

			} else {
				bookacti_send_json_not_allowed( 'refund_booking' );
			}
		}
		add_action( 'wp_ajax_bookactiRefundBooking', 'bookacti_controller_refund_booking' );
		add_action( 'wp_ajax_nopriv_bookactiRefundBooking', 'bookacti_controller_refund_booking' );


		/**
		 * AJAX Controller - Change booking state
		 * @version 1.5.9
		 */
		function bookacti_controller_change_booking_state() {

			$booking_id			= intval( $_POST[ 'booking_id' ] );
			$booking_state		= sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
			$payment_status		= sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
			$send_notifications	= $_POST[ 'send_notifications' ] ? 1 : 0;
			$is_bookings_page	= intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false;
			
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
				$state_can_be_changed = bookacti_booking_state_can_be_changed_to( $booking_id, $new_booking_state );
				if( ! $state_can_be_changed ) {
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_status', 'message' => esc_html__( 'The booking status cannot be changed.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_status' );
				}
				
				$was_active	= bookacti_is_booking_active( $booking_id ) ? 1 : 0;
				$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
				if( $active !== $was_active ) { $active_changed = true; }
				
				$updated = bookacti_update_booking_state( $booking_id, $new_booking_state, $active );
				if( $updated === false ) { 
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_status', 'message' => esc_html__( 'An error occured while trying to change the booking status.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_status' );
				}
				
				do_action( 'bookacti_booking_state_changed', $booking_id, $new_booking_state, array( 'is_admin' => $is_bookings_page, 'active' => $active, 'send_notifications' => $send_notifications ) );
			}
			
			// Change payment status
			if( $new_payment_status && $booking->payment_status !== $new_payment_status ) {
				$updated = bookacti_update_booking_payment_status( $booking_id, $new_payment_status );
				if( $updated === false ) { 
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_payment_status', 'message' => esc_html__( 'An error occured while trying to change the booking payment status.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_status' );
				}
				
				do_action( 'bookacti_booking_payment_status_changed', $booking_id, $new_payment_status, array( 'is_admin' => $is_bookings_page ) );
			}
			
			$Bookings_List_Table = new Bookings_List_Table();
			$Bookings_List_Table->prepare_items( array( 'booking_id' => $booking_id ), true );
			$row = $Bookings_List_Table->get_rows_or_placeholder();
			
			bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'active_changed' => $active_changed ), 'change_booking_status' );
		}
		add_action( 'wp_ajax_bookactiChangeBookingState', 'bookacti_controller_change_booking_state' );


		/**
		 * AJAX Controller - Get booking system data by booking ID
		 * @version 1.5.2
		 */
		function bookacti_controller_get_booking_data() {
			// Check nonce, no need to check capabilities
			$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_data', 'nonce', false );

			if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'get_booking_data' ); }

			$booking_id	= intval( $_POST[ 'booking_id' ] );
			$booking_data = bookacti_get_booking_data( $booking_id );

			if( is_array( $booking_data ) && ! empty( $booking_data ) ) {
				wp_send_json( array( 'status' => 'success', 'booking_data' => $booking_data ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'empty_data' ) );
			}
		}
		add_action( 'wp_ajax_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );
		add_action( 'wp_ajax_nopriv_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );


		/**
		 * AJAX Controller - Reschedule a booking
		 * @version 1.6.0
		 */
		function bookacti_controller_reschedule_booking() {

			$booking_id			= intval( $_POST[ 'booking_id' ] );
			$event_id			= intval( $_POST[ 'event_id' ] );
			$event_start		= bookacti_sanitize_datetime( $_POST[ 'event_start' ] );
			$event_end			= bookacti_sanitize_datetime( $_POST[ 'event_end' ] );

			// Check nonce, capabilities and other params
			$is_nonce_valid		= check_ajax_referer( 'bookacti_reschedule_booking', 'nonce', false );
			$is_allowed			= bookacti_user_can_manage_booking( $booking_id );
			
			if( ! $is_nonce_valid || ! $is_allowed ) {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed', 'message' => esc_html__( 'You are not allowed to do that.', BOOKACTI_PLUGIN_NAME ) ) );
			}
			
			// Check if the desired event is eligible according to the current booking
			$can_be_rescheduled	= bookacti_booking_can_be_rescheduled_to( $booking_id, $event_id, $event_start, $event_end );
			if( $can_be_rescheduled[ 'status' ] !== 'success' ) {
				bookacti_send_json( $can_be_rescheduled );
			}
			
			// Validate availability
			$booking	= bookacti_get_booking_by_id( $booking_id );
			$form_id	= ! empty( $booking->form_id ) && ! current_user_can( 'bookacti_edit_bookings' ) ? $booking->form_id : 0;
			$validated	= bookacti_validate_booking_form( 'single', $event_id, $event_start, $event_end, $booking->quantity, $form_id );

			if( $validated[ 'status' ] !== 'success' ) {
				wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated[ 'message' ] ) ) );
			}

			$rescheduled = bookacti_reschedule_booking( $booking_id, $event_id, $event_start, $event_end );
			
			if( $rescheduled === 0 ) {
				$message = __( 'You must select a different time slot than the current one.', BOOKACTI_PLUGIN_NAME );
				wp_send_json( array( 'status' => 'no_changes', 'error' => 'no_changes', 'message' => $message ) );
			}
			
			if( ! $rescheduled ) {
				wp_send_json( array( 'status' => 'failed' ) );
			}

			$is_bookings_page	= intval( $_POST[ 'is_bookings_page' ] );
			$send_notifications	= $is_bookings_page ? intval( $_POST[ 'send_notifications' ] ) : 1;

			do_action( 'bookacti_booking_rescheduled', $booking_id, $booking, array( 'is_admin' => $is_bookings_page, 'send_notifications' => $send_notifications ) );

			$admin_or_front		= $is_bookings_page ? 'admin' : 'front';
			$actions_html		= bookacti_get_booking_actions_html( $booking_id, $admin_or_front );

			if( $is_bookings_page ) {
				$Bookings_List_Table = new Bookings_List_Table();
				$Bookings_List_Table->prepare_items( array( 'booking_id' => $booking_id ), true );
				$row = $Bookings_List_Table->get_rows_or_placeholder();
			} else {
				$user_id	= get_current_user_id();
				$booking	= bookacti_get_booking_by_id( $booking_id );
				$columns	= bookacti_get_booking_list_columns( $user_id );
				$row		= bookacti_get_booking_list_rows( array( $booking ), $columns, $user_id );
			}

			wp_send_json( array( 'status' => 'success', 'actions_html' => $actions_html, 'row' => $row ) );
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
					'message'	=> esc_html__( 'An error occurred while trying to delete the booking.', BOOKACTI_PLUGIN_NAME )
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
		 * @version 1.5.9
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
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'booking_group_already_cancelled', 'message' => esc_html__( 'The booking group is already cancelled.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking_group' );
			}
			
			$can_be_cancelled = bookacti_booking_group_can_be_cancelled( $booking_group_id );
			if( ! $can_be_cancelled ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_cancel_booking_group', 'message' => esc_html__( 'The booking group cannot be cancelled.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking_group' );
			}

			$cancelled = bookacti_update_booking_group_state( $booking_group_id, 'cancelled', 'auto', true );
			if( ! $cancelled ) {
				bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_cancel_booking_group', 'message' => esc_html__( 'An error occured while trying to cancel the booking group.', BOOKACTI_PLUGIN_NAME ) ), 'cancel_booking_group' );
			}

			do_action( 'bookacti_booking_group_state_changed', $booking_group_id, 'cancelled', array( 'is_admin' => false ) );

			$allow_refund	= bookacti_booking_group_can_be_refunded( $booking_group_id );
			$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, 'front' );
			$formatted_state= bookacti_format_booking_state( 'cancelled', false );

			bookacti_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state ), 'cancel_booking_group' );
		}
		add_action( 'wp_ajax_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );
		add_action( 'wp_ajax_nopriv_bookactiCancelBookingGroup', 'bookacti_controller_cancel_booking_group' );


		/**
		 * AJAX Controller - Get possible actions to refund a booking group
		 * 
		 * @since 1.1.0
		 */
		function bookacti_controller_get_booking_group_refund_actions_html() {

			$booking_group_id = intval( $_POST[ 'booking_id' ] );

			// Check nonce, capabilities and other params
			$is_nonce_valid	= check_ajax_referer( 'bookacti_get_refund_actions_html', 'nonce', false );
			$is_allowed		= bookacti_user_can_manage_booking_group( $booking_group_id );
			$can_be_refund	= bookacti_booking_group_can_be_refunded( $booking_group_id );

			if( $is_nonce_valid && $is_allowed && $can_be_refund ) {

				$refund_actions_array	= bookacti_get_refund_actions_by_booking_group_id( $booking_group_id );
				$refund_actions_html	= bookacti_get_refund_dialog_html_by_booking_group_id( $booking_group_id );

				if( ! empty( $refund_actions_html ) ) {

					wp_send_json( array( 'status' => 'success', 'actions_html' => $refund_actions_html, 'actions_array' => $refund_actions_array ) );

				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );
		add_action( 'wp_ajax_nopriv_bookactiGetBookingGroupRefundActionsHTML', 'bookacti_controller_get_booking_group_refund_actions_html' );


		/**
		 * AJAX Controller - Refund a booking group
		 * 
		 * @since 1.1.0
		 * @version 1.3.0
		 */
		function bookacti_controller_refund_booking_group() {

			$booking_group_id	= intval( $_POST[ 'booking_id' ] );
			$is_admin			= intval( $_POST[ 'is_admin' ] );
			$sanitized_action	= sanitize_title_with_dashes( stripslashes( $_POST[ 'refund_action' ] ) );
			$refund_action		= array_key_exists( $sanitized_action, bookacti_get_refund_actions_by_booking_group_id( $booking_group_id ) ) ? $sanitized_action : 'email';
			
			// Check nonce, capabilities and other params
			$is_nonce_valid	= check_ajax_referer( 'bookacti_refund_booking', 'nonce', false );
			$is_allowed		= bookacti_user_can_manage_booking_group( $booking_group_id );
			$can_be_refund	= bookacti_booking_group_can_be_refunded( $booking_group_id, $refund_action );

			if( $is_nonce_valid && $is_allowed && $can_be_refund ) {

				$refund_message	= sanitize_text_field( stripslashes( $_POST[ 'refund_message' ] ) );

				if( $refund_action === 'email' ) {
					$refunded = bookacti_send_email_refund_request( $booking_group_id, 'group', $refund_message );
					if( $refunded ) {
						$refunded = array( 'status' => 'success', 'new_state' => 'refund_requested' );
					} else {
						$refunded = array( 'status' => 'failed', 'error' => 'cannot_send_email' );
					}
				} else {
					$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $booking_group_id, 'group', $refund_action, $refund_message );
				}
				
				if( $refunded[ 'status' ] === 'success' ) {
					
					$new_state	= $refunded[ 'new_state' ] ? $refunded[ 'new_state' ] : 'refunded';
					$updated	= bookacti_update_booking_group_state( $booking_group_id, $new_state, 'auto', true );

					// Hook status changes
					if( $updated ) {
						do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array( 'is_admin' => $is_admin, 'refund_action' => $refund_action ) );
					}
					
					// Get new booking actions
					$admin_or_front = $is_admin ? 'admin' : 'front';
					$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, $admin_or_front );
					$refunded[ 'new_actions_html' ] = $actions_html;

					// Get new booking state formatted
					$refunded[ 'formatted_state' ] = bookacti_format_booking_state( $new_state, $is_admin );
					
					// Get grouped booking rows if they are displayed and need to be refreshed
					$rows = '';
					$reload_grouped_bookings = intval( $_POST[ 'reload_grouped_bookings' ] ) === 1 ? true : false;
					if( $reload_grouped_bookings ) {
						$Bookings_List_Table = new Bookings_List_Table();
						$Bookings_List_Table->prepare_items( array( 'booking_group_id' => $booking_group_id ), true );
						$rows = $Bookings_List_Table->get_rows_or_placeholder();
					}
					
					$refunded[ 'grouped_booking_rows' ] = $rows;
				}

				wp_send_json( $refunded );

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );
		add_action( 'wp_ajax_nopriv_bookactiRefundBookingGroup', 'bookacti_controller_refund_booking_group' );


		/**
		 * AJAX Controller - Change booking group state
		 * @since 1.1.0
		 * @version 1.5.9
		 */
		function bookacti_controller_change_booking_group_state() {

			$booking_group_id		= intval( $_POST[ 'booking_id' ] );
			$booking_state			= sanitize_title_with_dashes( $_POST[ 'new_booking_state' ] );
			$payment_status			= sanitize_title_with_dashes( $_POST[ 'new_payment_status' ] );
			$send_notifications		= $_POST[ 'send_notifications' ] ? 1 : 0;
			$is_bookings_page		= intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false;
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
				
				$state_can_be_changed = bookacti_booking_group_state_can_be_changed_to( $booking_group_id, $new_booking_state );
				if( ! $state_can_be_changed ) {
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_allowed_to_update_booking_group_status', 'message' => esc_html__( 'The booking group status cannot be changed.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_group_status' );
				}
					
				$was_active	= $booking_group->active ? 1 : 0;
				$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
				if( $active !== $was_active ) { $active_changed = true; }
				
				$updated = bookacti_update_booking_group_state( $booking_group_id, $new_booking_state, $active, true, true );
				if( ! $updated ) { 
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_status', 'message' => esc_html__( 'An error occured while trying to change the booking group status.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_group_status' );
				}

				do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_booking_state, array( 'is_admin' => $is_bookings_page, 'active' => $active, 'send_notifications' => $send_notifications ) );
			}
			
			// Change payment status
			if( $new_payment_status && $booking_group->payment_status !== $new_payment_status ) {
				$updated = bookacti_update_booking_group_payment_status( $booking_group_id, $new_payment_status, true, true );
				if( $updated === false ) { 
					bookacti_send_json( array( 'status' => 'failed', 'error' => 'error_update_booking_group_payment_status', 'message' => esc_html__( 'An error occured while trying to change the booking group payment status.', BOOKACTI_PLUGIN_NAME ) ), 'change_booking_group_status' );
				}
				
				do_action( 'bookacti_booking_group_payment_status_changed', $booking_group_id, $new_payment_status, array( 'is_admin' => $is_bookings_page ) );
			}
			
			$Bookings_List_Table = new Bookings_List_Table();
			$Bookings_List_Table->prepare_items( array( 'booking_group_id' => $booking_group_id, 'group_by' => 'booking_group' ), true );
			$row = $Bookings_List_Table->get_rows_or_placeholder();

			$rows = '';
			if( $reload_grouped_bookings ) {
				$Bookings_List_Table->prepare_items( array( 'booking_group_id' => $booking_group_id ), true );
				$rows = $Bookings_List_Table->get_rows_or_placeholder();
			}
			
			bookacti_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows, 'active_changed' => $active_changed ), 'change_booking_group_status' );
		}
		add_action( 'wp_ajax_bookactiChangeBookingGroupState', 'bookacti_controller_change_booking_group_state' );
		
		
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
					'message'	=> esc_html__( 'An error occurred while trying to delete the bookings of the group.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_send_json( $return_array, 'delete_booking_group' );
			}
			
			$group_deleted = bookacti_delete_booking_group( $booking_group_id );

			if( ! $group_deleted ) {
				$return_array = array( 
					'status'	=> 'failed', 
					'error'		=> 'not_deleted', 
					'message'	=> esc_html__( 'An error occurred while trying to delete the booking group.', BOOKACTI_PLUGIN_NAME )
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
			$message = esc_html__( 'The link has been correctly generated. Use the link above to export your bookings.', BOOKACTI_PLUGIN_NAME );
			
			// Get or generate current user export secret key
			$current_user_id = get_current_user_id();
			$secret_key = get_user_meta( $current_user_id, 'bookacti_secret_key', true );
			if( ! $secret_key || ! empty( $_POST[ 'reset_key' ] ) ) {
				$secret_key = $current_user_id . '-' . md5( microtime().rand() );
				update_user_meta( $current_user_id, 'bookacti_secret_key', $secret_key );
				if( ! empty( $_POST[ 'reset_key' ] ) ) {
					$message .= '<br/><em>' . esc_html__( 'Your secret key has been changed. The old links that you have generated won\'t work anymore.', BOOKACTI_PLUGIN_NAME ) . '</em>';
				}
			}
			
			// Get formatted booking filters
			if( isset( $_POST[ 'booking_filters' ][ 'templates' ] ) && $_POST[ 'booking_filters' ][ 'templates' ][ 0 ] === 'all' ) {
				unset( $_POST[ 'booking_filters' ][ 'templates' ][ 0 ] );
				if( empty( $_POST[ 'booking_filters' ][ 'templates' ] ) ) { $_POST[ 'booking_filters' ][ 'templates' ] = ''; }
			}
			$default_fitlers = bookacti_format_booking_filters();
			$booking_filters = bookacti_format_booking_filters( $_POST[ 'booking_filters' ] );
			
			if( isset( $_POST[ 'export_groups' ] ) ) {
				$booking_filters[ 'group_by' ] = $_POST[ 'export_groups' ] === 'groups' ? 'booking_group' : 'none';
			}
			if( isset( $_POST[ 'per_page' ] ) ) {
				$booking_filters[ 'per_page' ] = is_numeric( $_POST[ 'per_page' ] ) ? intval( $_POST[ 'per_page' ] ) : 20;
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
			if( isset( $_POST[ 'columns' ] ) ) {
				if( ! is_array( $_POST[ 'columns' ] ) ) { $_POST[ 'columns' ] = bookacti_get_bookings_export_default_columns(); }
				else {
					$columns = bookacti_get_bookings_export_columns();
					foreach( $_POST[ 'columns' ] as $i => $column_name ) {
						if( ! isset( $columns[ $column_name ] ) ) { unset( $_POST[ 'columns' ][ $i ] ); }
					}
				}
			}
			
			// Add the required settings to the URL
			$csv_url = home_url( 'booking-activities-bookings-' . $current_user_id . '.csv?action=bookacti_export_bookings&key=' . $secret_key . '&lang=' . $lang );
			if( $booking_filters ) {
				$csv_url = add_query_arg( $booking_filters, $csv_url );
			}
			if( ! empty( $_POST[ 'columns' ] ) ) {
				$csv_url = add_query_arg( array( 'columns' => $_POST[ 'columns' ] ), $csv_url );
			}
			
			bookacti_send_json( array( 'status' => 'success', 'url' => esc_url_raw( $csv_url ), 'message' => $message ), 'export_bookings_url' ); 
		}
		add_action( 'wp_ajax_bookactiExportBookingsUrl', 'bookacti_controller_generate_export_bookings_url' );
		
		
		/**
		 * Export events of a specifc form
		 * @since 1.6.0
		 */
		function bookacti_export_bookings_page() {
			if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'bookacti_export_bookings' ) { return; }

			// Check if the secret key exists
			$key = ! empty( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
			if( ! $key ) { esc_html_e( 'Missing key.', BOOKACTI_PLUGIN_NAME ); exit; }
			
			// Check if the user exists
			$user_id = intval( substr( $key, 0, strpos( $key, '-' ) ) );
			$user = get_user_by( 'id', $user_id );
			if( ! $user ) { esc_html_e( 'Unknown user.', BOOKACTI_PLUGIN_NAME ); exit; }
			
			// Check the filename
			$parsed_url = parse_url( $_SERVER[ 'REQUEST_URI' ] ); 
			$filename	= 'booking-activities-bookings-' . $user_id . '.csv';
			if( basename( $parsed_url[ 'path' ] ) !== $filename ) { esc_html_e( 'Invalid filename.', BOOKACTI_PLUGIN_NAME ); exit; }

			// Check if the secret key is correct
			$secret_key = get_user_meta( $user_id, 'bookacti_secret_key', true );
			if( $key !== $secret_key ) { esc_html_e( 'Invalid key.', BOOKACTI_PLUGIN_NAME ); exit; }
			
			// Format the booking filters
			if( empty( $_REQUEST[ 'templates' ] ) ) { $_REQUEST[ 'templates' ] = ''; }
			$filters = bookacti_format_booking_filters( $_REQUEST );
			if( empty( $_REQUEST[ 'templates' ] ) ) { 
				$filters[ 'templates' ] = array_keys( bookacti_fetch_templates( array(), false, $user_id ) );
			}
			
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
			
//			header( 'Content-type: text/csv; charset=utf-8' );
//			header( 'Content-Disposition: attachment; filename=' . $filename );
//			header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
//			header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Expired date to force third-party apps to refresh soon
			
			echo '<pre>';
			echo bookacti_convert_bookings_to_csv( $filters, $columns );
			echo '</pre>';
			
			exit;
		}
		add_action( 'wp_loaded', 'bookacti_export_bookings_page', 10 );




// BOOKING LIST
	
	/**
	 * Change Customer name in bookings list
	 * @version 1.5.4
	 * @param array $booking_item
	 * @param object $booking
	 * @param WP_User $user
	 * @return array
	 */
	function bookacti_change_customer_name_in_bookings_list( $booking_item, $booking, $user, $list ) {
		if( is_numeric( $booking->user_id ) ) {
			if( isset( $user->first_name ) && $user->last_name ) {
				$customer = '<a  href="' . esc_url( get_admin_url() . 'user-edit.php?user_id=' . $booking->user_id ) . '" '
							.  ' target="_blank" >'
								. esc_html( $user->first_name . ' ' . $user->last_name )
						.   '</a>';
				$booking_item[ 'customer' ] = $customer;
			}
		}
		return $booking_item;
	}
	add_filter( 'bookacti_booking_list_booking_columns', 'bookacti_change_customer_name_in_bookings_list', 10, 4 );