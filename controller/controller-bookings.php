<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS
	
	// Get the HTML booking list for a given event
    add_action( 'wp_ajax_bookactiGetBookingRows', 'bookacti_controller_get_booking_rows' );
	function bookacti_controller_get_booking_rows() {
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_booking_rows', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_bookings' );
		
		if( $is_nonce_valid && $is_allowed ) {
			$Bookings_List_Table = new Bookings_List_Table();
			$Bookings_List_Table->prepare_items();
			$rows = $Bookings_List_Table->get_rows_or_placeholder();
			
			if( ! empty( $rows ) ) {
				wp_send_json( array( 'status' => 'success', 'rows' => $rows ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'no_rows' ) );
			}
        } else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}




// BOOKINGS FILTERS
	// Change selected template
	add_action( 'wp_ajax_bookactiSelectTemplateFilter', 'bookacti_controller_select_template_filter' );
    function bookacti_controller_select_template_filter() {
		
		// Check nonce
		$is_nonce_valid = check_ajax_referer( 'bookacti_selected_template_filter', 'nonce', false );
		
		// Check capabilities
		$is_allowed = current_user_can( 'bookacti_read_templates' );
		if( $is_allowed ){
			// Get selected templates and format them
			$template_ids = bookacti_ids_to_array( $_POST[ 'template_ids' ] );

			// Remove templates current user is not allowed to manage
			foreach( $template_ids as $i => $template_id ){
				if( ! bookacti_user_can_manage_template( $template_id ) ){
					unset( $template_ids[ $i ] );
				}
			}
			// If none remains, disallow the action
			if( empty( $template_ids ) ) { $is_allowed = false; }
		}
		
		if( $is_nonce_valid && $is_allowed ) {
			
			// Change default template to the first selected
			bookacti_update_user_default_template( $template_ids[ 0 ] );
			
			// Actvity filters change depending on the templates selection, 
			// this retrieve the HTML for activity filters corresponding to templates selection
			$activities_html = bookacti_get_activities_html_for_booking_page( $template_ids );
			
			// Get calendar settings
			$settings			= bookacti_get_mixed_template_settings( $template_ids );
			$activity_ids		= bookacti_get_activity_ids_by_template( $template_ids );
			$group_categories	= bookacti_get_group_category_ids_by_template( $template_ids );
			
			// Gets calendar content: events, activities and groups
			$args = array(
				'calendars' => $template_ids,
				'activities' => array(),
				'categories' => array(),
				'groups_only' => false,
				'past_events' => true,
				'context' => 'booking_page'
			);
			
			$events		= bookacti_fetch_events( $args );
			$activities	= bookacti_get_activities_by_template( $template_ids );
			$groups		= bookacti_get_groups_events( $template_ids, $group_categories, array(), true );
			
			wp_send_json( array( 
				'status'			=> 'success', 
				'activities_html'	=> $activities_html, 
				'events'			=> $events, 
				'activities'		=> $activities, 
				'groups'			=> $groups,
				'calendar_ids'		=> $template_ids,
				'activity_ids'		=> $activity_ids,
				'group_categories'	=> $group_categories,
				'settings'			=> $settings
			) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}




// BOOKINGS SETTINGS
	// Update booking filters settings
	add_action( 'wp_ajax_bookactiBookingFiltersSettings', 'bookacti_controller_change_booking_filter_settings' );
    function bookacti_controller_change_booking_filter_settings() {
        
		// Check nonce
		$is_nonce_valid = check_ajax_referer( 'bookacti_update_booking_filters_settings', 'nonce', false );
		
		// Check capabilities
		if( $is_nonce_valid ) {
			if( current_user_can( 'bookacti_read_templates' ) && current_user_can( 'bookacti_manage_bookings' ) ){
		
				$show_past_events		= intval( $_POST[ 'show_past_events' ] );
				$allow_templates_filter	= intval( $_POST[ 'allow_templates_filter' ] );
				$allow_activities_filter= intval( $_POST[ 'allow_activities_filter' ] );

				$bookings_settings = get_option( 'bookacti_bookings_settings' );
				$user_id = get_current_user_id();

				if( ! is_array( $bookings_settings['show_past_events'] ) )			{ $bookings_settings['show_past_events']		= array(); } 
				if( ! is_array( $bookings_settings['allow_templates_filter'] ) )	{ $bookings_settings['allow_templates_filter']	= array(); } 
				if( ! is_array( $bookings_settings['allow_activities_filter'] ) )	{ $bookings_settings['allow_activities_filter']	= array(); } 

				$bookings_settings[ 'show_past_events' ][ $user_id ]		= $show_past_events;
				$bookings_settings[ 'allow_templates_filter' ][ $user_id ]	= $allow_templates_filter;
				$bookings_settings[ 'allow_activities_filter' ][ $user_id ]	= $allow_activities_filter;

				update_option( 'bookacti_bookings_settings', $bookings_settings );
				
				wp_send_json( array( 'status' => 'success' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
	
	// Update booking list settings
	add_action( 'wp_ajax_bookactiBookingListSettings', 'bookacti_controller_change_booking_list_settings' );
    function bookacti_controller_change_booking_list_settings() {
        
		// Check nonce
		$is_nonce_valid = check_ajax_referer( 'bookacti_update_booking_list_settings', 'nonce', false );
		
		// Check capabilities
		if( $is_nonce_valid ) {
			if( current_user_can( 'bookacti_edit_bookings' ) && current_user_can( 'bookacti_manage_bookings' ) ){

				$show_inactive_bookings		= intval( $_POST[ 'show_inactive_bookings' ] );
				$show_temporary_bookings	= intval( $_POST[ 'show_temporary_bookings' ] );

				$bookings_settings = get_option( 'bookacti_bookings_settings' );
				$user_id = get_current_user_id();

				if( ! is_array( $bookings_settings['show_inactive_bookings'] ) )	{ $bookings_settings['show_inactive_bookings']	= array(); } 
				if( ! is_array( $bookings_settings['show_temporary_bookings'] ) )	{ $bookings_settings['show_temporary_bookings']	= array(); } 

				$bookings_settings[ 'show_inactive_bookings' ][ $user_id ]	= $show_inactive_bookings;
				$bookings_settings[ 'show_temporary_bookings' ][ $user_id ]	= $show_temporary_bookings;

				update_option( 'bookacti_bookings_settings', $bookings_settings );
				
				wp_send_json( array( 'status' => 'success' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }




// BOOKING ACTIONS
	
	// SINGLE BOOKING
		/**
		 * AJAX Controller - Cancel a booking
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

					// Is bookings page ?
					$is_bookings_page	= false;
					if( isset( $_POST[ 'is_bookings_page' ] ) ) { 
						$is_bookings_page = intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false; 
					}

					do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => $is_bookings_page ) );

					$admin_or_front	= $is_bookings_page ? 'both' : 'front';
					$allow_refund	= bookacti_booking_can_be_refunded( $booking_id );
					$actions_html	= bookacti_get_booking_actions_html( $booking_id, $admin_or_front );

					wp_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html ) );

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
		 * @version 1.1.0
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
						do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'refund_action' => $refund_action ) );
					}

					// Get new booking actions
					$admin_or_front = $is_admin ? 'both' : 'front';
					$actions_html	= bookacti_get_booking_actions_html( $booking_id, $admin_or_front );
					$refunded[ 'new_actions_html' ] = $actions_html;

					// Get new booking state formatted
					$refunded[ 'formatted_state' ] = bookacti_format_booking_state( $new_state );
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
		 */
		function bookacti_controller_change_booking_state() {

			$booking_id			= intval( $_POST[ 'booking_id' ] );
			$sanitized_state	= sanitize_title_with_dashes( $_POST[ 'new_state' ] );
			$new_state			= array_key_exists( $sanitized_state, bookacti_get_booking_state_labels() ) ? $sanitized_state : false;

			// Check nonce, capabilities and other params
			$is_nonce_valid			= check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
			$is_allowed				= current_user_can( 'bookacti_edit_bookings' );		
			$state_can_be_changed	= bookacti_booking_state_can_be_changed_to( $booking_id, $new_state );

			if( $is_nonce_valid && $is_allowed && $state_can_be_changed && $new_state ) {

				$was_active	= bookacti_is_booking_active( $booking_id ) ? 1 : 0;
				$active		= in_array( $new_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;

				if( ! $was_active && $active ) {
					$booking	= bookacti_get_booking_by_id( $booking_id );
					$validated	= bookacti_validate_booking_form( 'single', $booking->event_id, $booking->event_start, $booking->event_end, $booking->quantity );
				} else {
					$validated['status'] = 'success';
				}

				if( $validated['status'] === 'success' ) {
					$updated= bookacti_update_booking_state( $booking_id, $new_state );

					if( $updated ) {

						$is_bookings_page	= intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false;
						do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'is_admin' => $is_bookings_page, 'active' => $active ) );

						$actions_html	= bookacti_get_booking_actions_html( $booking_id, 'admin' );
						$formatted_state= bookacti_format_booking_state( $new_state );

						$active_changed = $active === $was_active ? false : true;

						wp_send_json( array( 'status' => 'success', 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state, 'active_changed' => $active_changed ) );
					} else {
						wp_send_json( array( 'status' => 'failed' ) );
					}
				} else {
					wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated['message'] ) ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiChangeBookingState', 'bookacti_controller_change_booking_state' );


		/**
		 * AJAX Controller - Get booking system data by booking ID
		 */
		function bookacti_controller_get_booking_data() {

			// Check nonce, no need to check capabilities
			$is_nonce_valid = check_ajax_referer( 'bookacti_get_booking_data', 'nonce', false );

			if( $is_nonce_valid ) {

				$booking_id	= intval( $_POST[ 'booking_id' ] );
				$booking_data = bookacti_get_booking_data( $booking_id );

				if( is_array( $booking_data ) && ! empty( $booking_data ) ) {
					$booking_data[ 'status' ] = 'success';
					wp_send_json( $booking_data );
				} else {
					wp_send_json( array( 'status' => 'failed', 'error' => 'empty_data' ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );
		add_action( 'wp_ajax_nopriv_bookactiGetBookingData', 'bookacti_controller_get_booking_data' );


		/**
		 * AJAX Controller - Reschedule a booking
		 * 
		 * @version 1.1.0
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

			if( $is_nonce_valid && $is_allowed && $can_be_rescheduled ) {

				// Validate availability
				$booking	= bookacti_get_booking_by_id( $booking_id );
				$validated	= bookacti_validate_booking_form( 'single', $event_id, $event_start, $event_end, $booking->quantity );

				if( $validated['status'] === 'success' ) {

					$rescheduled = bookacti_reschedule_booking( $booking_id, $event_id, $event_start, $event_end );

					if( $rescheduled ) {

						do_action( 'bookacti_booking_rescheduled', $booking_id, $event_start, $event_end );
						
						$is_bookings_page	= intval( $_POST[ 'is_bookings_page' ] );
						$admin_or_front		= $is_bookings_page ? 'both' : 'front';
						$actions_html		= bookacti_get_booking_actions_html( $booking_id, $admin_or_front );

						wp_send_json( array( 'status' => 'success', 'actions_html' => $actions_html ) );
					} else if( $rescheduled === 0 ) {
						$message = __( 'You must select a different schedule than the current one.', BOOKACTI_PLUGIN_NAME );
						wp_send_json( array( 'status' => 'no_changes', 'error' => 'no_changes', 'message' => $message ) );

					} else {
						wp_send_json( array( 'status' => 'failed' ) );
					}

				} else {

					wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated[ 'message' ] ) ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed', 'message' => esc_html__( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ) ) );
			}
		}
		add_action( 'wp_ajax_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );
		add_action( 'wp_ajax_nopriv_bookactiRescheduleBooking', 'bookacti_controller_reschedule_booking' );
	
	
	
	// BOOKING GROUPS
		
		/**
		 * AJAX Controller - Cancel a booking group
		 * 
		 * @since 1.1.0
		 */
		function bookacti_controller_cancel_booking_group() {

			$booking_group_id = intval( $_POST[ 'booking_id' ] );
			
			// Check nonce, capabilities and other params
			$is_nonce_valid		= check_ajax_referer( 'bookacti_cancel_booking', 'nonce', false );
			$is_allowed			= bookacti_user_can_manage_booking_group( $booking_group_id );
			$can_be_cancelled	= bookacti_booking_group_can_be_cancelled( $booking_group_id );

			if( $is_nonce_valid && $is_allowed && $can_be_cancelled ) {
				
				$booking_ids	= bookacti_get_booking_group_bookings_ids( $booking_group_id );
				$cancelled		= bookacti_update_booking_group_state( $booking_group_id, 'cancelled', 'auto', true );

				if( $cancelled ) {

					// Is bookings page ?
					$is_bookings_page	= false;
					if( isset( $_POST[ 'is_bookings_page' ] ) ) { 
						$is_bookings_page = intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false; 
					}
					
					foreach( $booking_ids as $booking_id ) {
						do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => $is_bookings_page ) );
					}
					
					do_action( 'bookacti_booking_group_state_changed', $booking_group_id, 'cancelled', array( 'is_admin' => $is_bookings_page ) );
					
					$admin_or_front	= $is_bookings_page ? 'both' : 'front';
					$allow_refund	= bookacti_booking_group_can_be_refunded( $booking_group_id );
					$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, $admin_or_front );
					
					wp_send_json( array( 'status' => 'success', 'allow_refund' => $allow_refund, 'new_actions_html' => $actions_html ) );

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
					
					$booking_ids= bookacti_get_booking_group_bookings_ids( $booking_group_id );
					$new_state	= $refunded[ 'new_state' ] ? $refunded[ 'new_state' ] : 'refunded';
					$updated	= bookacti_update_booking_group_state( $booking_group_id, $new_state, 'auto', true );

					// Hook status changes
					if( $updated ) {
						foreach( $booking_ids as $booking_id ) {
							do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'refund_action' => $refund_action, 'is_admin' => $is_admin ) );
						}
						do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array( 'refund_action' => $refund_action, 'is_admin' => $is_admin ) );
					}
					
					// Get new booking actions
					$admin_or_front = $is_admin ? 'both' : 'front';
					$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, $admin_or_front );
					$refunded[ 'new_actions_html' ] = $actions_html;

					// Get new booking state formatted
					$refunded[ 'formatted_state' ] = bookacti_format_booking_state( $new_state );
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
		 */
		function bookacti_controller_change_booking_group_state() {

			$booking_group_id	= intval( $_POST[ 'booking_id' ] );
			$sanitized_state	= sanitize_title_with_dashes( $_POST[ 'new_state' ] );
			$new_state			= array_key_exists( $sanitized_state, bookacti_get_booking_state_labels() ) ? $sanitized_state : false;

			// Check nonce, capabilities and other params
			$is_nonce_valid			= check_ajax_referer( 'bookacti_change_booking_state', 'nonce', false );
			$is_allowed				= current_user_can( 'bookacti_edit_bookings' );		
			$state_can_be_changed	= bookacti_booking_group_state_can_be_changed_to( $booking_group_id, $new_state );
			
			if( $is_nonce_valid && $is_allowed && $state_can_be_changed && $new_state ) {

				$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
				$was_active		= $booking_group->active ? 1 : 0;
				$active			= in_array( $new_state, bookacti_get_active_booking_states(), true ) ? 1 : 0;
				
				// If the booking group was inactive and become active, we need to check availability
				if( ! $was_active && $active ) {
					$quantity	= bookacti_get_booking_group_quantity( $booking_group_id );
					$validated	= bookacti_validate_booking_form( $booking_group->event_group_id, null, null, null, $quantity );
				} else {
					$validated['status'] = 'success';
				}

				if( $validated['status'] === 'success' ) {
					
					$booking_ids= bookacti_get_booking_group_bookings_ids( $booking_group_id );
					$updated	= bookacti_update_booking_group_state( $booking_group_id, $new_state, $active, true, true );
					
					if( $updated ) {

						$is_bookings_page = intval( $_POST[ 'is_bookings_page' ] ) === 1 ? true : false;
						
						foreach( $booking_ids as $booking_id ) {
							do_action( 'bookacti_booking_state_changed', $booking_id, $new_state, array( 'is_admin' => $is_bookings_page, 'active' => $active ) );
						}
						
						do_action( 'bookacti_booking_group_state_changed', $booking_group_id, $new_state, array( 'is_admin' => $is_bookings_page, 'active' => $active ) );
						
						$actions_html	= bookacti_get_booking_group_actions_html( $booking_group_id, 'admin' );
						$formatted_state= bookacti_format_booking_state( $new_state );

						$active_changed = $active === $was_active ? false : true;

						wp_send_json( array( 'status' => 'success', 'new_actions_html' => $actions_html, 'formatted_state' => $formatted_state, 'active_changed' => $active_changed ) );
					} else {
						wp_send_json( array( 'status' => 'failed' ) );
					}
				} else {
					wp_send_json( array( 'status' => 'failed', 'error' => $validated['error'], 'message' => esc_html( $validated['message'] ) ) );
				}

			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			}
		}
		add_action( 'wp_ajax_bookactiChangeBookingGroupState', 'bookacti_controller_change_booking_group_state' );




// BOOKING LIST
	
	/**
	 * Change Customer name in bookings list
	 *  
	 * @param array $booking_item
	 * @param object $booking
	 * @param WP_User $user
	 * @return array
	 */
	function bookacti_change_customer_name_in_bookings_list( $booking_item, $booking, $user ) {
		
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
	add_filter( 'bookacti_booking_list_booking_columns', 'bookacti_change_customer_name_in_bookings_list', 10, 3 );