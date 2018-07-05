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
		 * 
		 * @version 1.3.0
		 */
		function bookacti_controller_cancel_booking() {

			$booking_id = intval( $_POST[ 'booking_id' ] );

			// Check nonce, capabilities and other params
			$is_nonce_valid		= check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
			$is_allowed			= bookacti_user_can_manage_booking( $booking_id );
			$can_be_cancelled	= bookacti_booking_can_be_cancelled( $booking_id );

			if( $is_nonce_valid && $is_allowed && $can_be_cancelled ) {

				$cancelled = bookacti_cancel_booking( $booking_id );

				if( $cancelled ) {

					do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => false ) );

					$allow_refund	= bookacti_booking_can_be_refunded( $booking_id );
					$actions_html	= bookacti_get_booking_actions_html( $booking_id, 'front' );
					$formatted_state= bookacti_format_booking_state( 'cancelled', false );

					wp_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state ) );

				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
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
		 * 
		 * @version 1.3.0
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
						$refunded = array( 'status' => 'failed', 'error' => 'cannot_send_email' );
					}
				} else {
					$refunded = apply_filters( 'bookacti_refund_booking', array( 'status' => 'failed' ), $booking_id, 'single', $refund_action, $refund_message );
				}

				if( $refunded[ 'status' ] === 'success' ) {
					$new_state = $refunded[ 'new_state' ] ? $refunded[ 'new_state' ] : 'refunded';
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

				wp_send_json( $refunded );

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiRefundBooking', 'bookacti_controller_refund_booking' );
		add_action( 'wp_ajax_nopriv_bookactiRefundBooking', 'bookacti_controller_refund_booking' );


		/**
		 * AJAX Controller - Change booking state
		 * 
		 * @version 1.5.6
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
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
			
			// Change booking state
			if( $new_booking_state ) {
				
				$state_can_be_changed = bookacti_booking_state_can_be_changed_to( $booking_id, $new_booking_state );
				
				if( $state_can_be_changed ) {
					$was_active	= bookacti_is_booking_active( $booking_id ) ? 1 : 0;
					$active		= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;

					$validated['status'] = 'success';
					if( ! $was_active && $active ) {
						$booking	= bookacti_get_booking_by_id( $booking_id );
						$validated	= bookacti_validate_booking_form( 'single', $booking->event_id, $booking->event_start, $booking->event_end, $booking->quantity );
					}

					if( $validated['status'] !== 'success' ) {
						wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated['message'] ) ) );
					}

					$updated = bookacti_update_booking_state( $booking_id, $new_booking_state );

					if( ! $updated ) { wp_send_json( array( 'status' => 'failed' ) ); }
					
					if( $active !== $was_active ) { $active_changed = true; }
					
					do_action( 'bookacti_booking_state_changed', $booking_id, $new_booking_state, array( 'is_admin' => $is_bookings_page, 'active' => $active, 'send_notifications' => $send_notifications ) );
				}
			}
			
			// Change payment status
			if( $new_payment_status ) {
				$updated = bookacti_update_booking_payment_status( $booking_id, $new_payment_status );
				
				if( $updated === false ) { wp_send_json( array( 'status' => 'failed' ) ); }
				
				do_action( 'bookacti_booking_payment_status_changed', $booking_id, $new_payment_status, array( 'is_admin' => $is_bookings_page ) );
			}
			
			$Bookings_List_Table = new Bookings_List_Table();
			$Bookings_List_Table->prepare_items( array( 'booking_id' => $booking_id ), true );
			$row = $Bookings_List_Table->get_rows_or_placeholder();
			
			wp_send_json( array( 'status' => 'success', 'row' => $row, 'active_changed' => $active_changed ) );
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
		 * 
		 * @version 1.3.0
		 */
		function bookacti_controller_reschedule_booking() {

			$booking_id			= intval( $_POST[ 'booking_id' ] );
			$event_id			= intval( $_POST[ 'event_id' ] );
			$event_start		= bookacti_sanitize_datetime( $_POST[ 'event_start' ] );
			$event_end			= bookacti_sanitize_datetime( $_POST[ 'event_end' ] );

			// Check nonce, capabilities and other params
			$is_nonce_valid		= check_ajax_referer( 'bookacti_reschedule_booking', 'nonce', false );
			$is_allowed			= bookacti_user_can_manage_booking( $booking_id );
			$can_be_rescheduled	= bookacti_booking_can_be_rescheduled_to( $booking_id, $event_id, $event_start, $event_end );
			
			if( ! $is_nonce_valid || ! $is_allowed || !$can_be_rescheduled ) {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed', 'message' => esc_html__( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ) ) );
			}

			// Validate availability
			$booking	= bookacti_get_booking_by_id( $booking_id );
			$validated	= bookacti_validate_booking_form( 'single', $event_id, $event_start, $event_end, $booking->quantity );

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
			
			// Delete booking metadata, if any
			bookacti_delete_metadata( 'booking', $booking_id );

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
		 * 
		 * @since 1.1.0
		 * @version 1.3.0
		 */
		function bookacti_controller_cancel_booking_group() {

			$booking_group_id = intval( $_POST[ 'booking_id' ] );
			
			// Check nonce, capabilities and other params
			$is_nonce_valid		= check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
			$is_allowed			= bookacti_user_can_manage_booking_group( $booking_group_id );
			$can_be_cancelled	= bookacti_booking_group_can_be_cancelled( $booking_group_id );

			if( $is_nonce_valid && $is_allowed && $can_be_cancelled ) {
				
				$cancelled = bookacti_update_booking_group_state( $booking_group_id, 'cancelled', 'auto', true );

				if( $cancelled ) {

					do_action( 'bookacti_booking_group_state_changed', $booking_group_id, 'cancelled', array( 'is_admin' => false ) );
					
					$allow_refund	= bookacti_booking_group_can_be_refunded( $booking_group_id );
					$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, 'front' );
					$formatted_state= bookacti_format_booking_state( 'cancelled', false );
					
					wp_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state ) );

				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
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
		 * 
		 * @since 1.1.0
		 * @version 1.4.0
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
			
			// Check nonce, capabilities and other params
			$is_nonce_valid	= check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
			$is_allowed		= current_user_can( 'bookacti_edit_bookings' );		
			
			if( ! $is_nonce_valid || ! $is_allowed ) {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
			
			// Change booking group states
			$active_changed = false;
			if( $new_booking_state ) {
				
				$state_can_be_changed = bookacti_booking_group_state_can_be_changed_to( $booking_group_id, $new_booking_state );
				
				if( $state_can_be_changed ) {
					$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
					$was_active		= $booking_group->active ? 1 : 0;
					$active			= in_array( $new_booking_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
					if( $active !== $was_active ) { $active_changed = true; }

					// If the booking group was inactive and become active, we need to check availability
					$validated['status'] = 'success';
					if( ! $was_active && $active ) {
						$quantity	= bookacti_get_booking_group_quantity( $booking_group_id );
						$validated	= bookacti_validate_booking_form( $booking_group->event_group_id, null, null, null, $quantity );
					}

					if( $validated['status'] !== 'success' ) {
						wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated['message'] ) ) );
					}

					$updated = bookacti_update_booking_group_state( $booking_group_id, $new_booking_state, $active, true, true );

					if( ! $updated ) { wp_send_json( array( 'status' => 'failed' ) ); }

					do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_booking_state, array( 'is_admin' => $is_bookings_page, 'active' => $active, 'send_notifications' => $send_notifications ) );
				}
			}
			
			// Change payment status
			if( $new_payment_status ) {
				$updated = bookacti_update_booking_group_payment_status( $booking_group_id, $new_payment_status, true, true );
				
				if( ! $updated ) { wp_send_json( array( 'status' => 'failed' ) ); }
				
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
			
			wp_send_json( array( 'status' => 'success', 'row' => $row, 'grouped_booking_rows' => $rows, 'active_changed' => $active_changed ) );
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