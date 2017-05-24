<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// GENERAL
	//Register an hourly reccuring event to hook to clean expired bookings
	add_action( 'woocommerce_installed', 'bookacti_register_houly_clean_expired_bookings' );
	add_action( 'bookacti_activate', 'bookacti_register_houly_clean_expired_bookings' );
	function bookacti_register_houly_clean_expired_bookings() {
		wp_schedule_event( time(), 'hourly', 'bookacti_hourly_event' );
	}


	//Deregister the hourly reccuring event
	add_action( 'wp_roles_init', 'bookacti_clear_houly_clean_expired_bookings_on_woocommerce_uninstall' );
	function bookacti_clear_houly_clean_expired_bookings_on_woocommerce_uninstall() {
		if( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN === 'woocommerce/woocommerce.php' ) {
			bookacti_cancel_in_cart_bookings();
			bookacti_clear_houly_clean_expired_bookings();
		}
	}

	add_action( 'bookacti_deactivate', 'bookacti_clear_houly_clean_expired_bookings' );
	function bookacti_clear_houly_clean_expired_bookings() {
		wp_clear_scheduled_hook( 'bookacti_hourly_event' );
	}

	
	// Add 'in_cart' state to active states
	add_filter( 'bookacti_active_booking_states', 'bookacti_add_woocommerce_active_booking_states' );
	function bookacti_add_woocommerce_active_booking_states( $active_states ) {
		$active_states[] = 'in_cart';
		return $active_states;
	}
	
	
	// Add 'in_cart' booking state label
	add_filter( 'bookacti_booking_states_labels_array', 'bookacti_add_in_cart_state_label', 10, 1 );
	function bookacti_add_in_cart_state_label( $labels ) {
		$labels[ 'in_cart' ] =  array( 'display_state' => 'warning',	'label' => __( 'In cart', BOOKACTI_PLUGIN_NAME ) );
		$labels[ 'expired' ] =  array( 'display_state' => 'bad',		'label' => __( 'Expired', BOOKACTI_PLUGIN_NAME ) );
		$labels[ 'removed' ] =  array( 'display_state' => 'bad',		'label' => __( 'Removed', BOOKACTI_PLUGIN_NAME ) );
		
		return $labels;
	}
	
	
	/**
	 * Deactivate expired bookings
	 *
	 * @since	1.0.6
	 */
	function bookacti_controller_deactivate_expired_bookings() {

		$deactivated_ids = bookacti_deactivate_expired_bookings();

		if ( $deactivated_ids === false ) { 
			/* translators: 'cron' is a robot that execute scripts every X hours. Don't try to translate it. */
			$log = esc_html__( 'The expired bookings were not correctly deactivated by cron.', BOOKACTI_PLUGIN_NAME );
			bookacti_log( $log, 'error' );
		} else {
			// Yell the booking state change 
			// (important for consistancy between WooCommerce order items and Booking Activities bookings)
			foreach( $deactivated_ids as $booking_id ) {
				do_action( 'bookacti_booking_state_changed', $booking_id, 'expired', array( 'is_admin' => true ) );
			}
		}
	}
	add_action( 'bookacti_hourly_event', 'bookacti_controller_deactivate_expired_bookings' );
	
	
// ORDER AND BOOKING STATUS
	//Turn a temporary booking to permanent if order gets complete
	add_action( 'woocommerce_order_status_completed', 'bookacti_turn_temporary_booking_to_permanent', 10, 1 );
	function bookacti_turn_temporary_booking_to_permanent( $order_id ) {
		
		//Change state of all bookings of the order from 'pending' to 'booked'
		bookacti_turn_order_bookings_to( $order_id, 'booked', true );
		
		//It is possible that pending bookings remain bound to the order if the user change his mind after he placed the order, but before he paid it.
		//He then changed his cart, placed a new order, paid it, and only part of the old order is booked (or even nothing), the rest is still 'pending'
		//Then we just turn 'pending' booking bound to this order to 'cancelled'
		bookacti_cancel_order_pending_bookings( $order_id );
	}
	
	
	//Cancel the temporary booking if it failed
	add_action( 'woocommerce_order_status_cancelled', 'bookacti_cancelled_order', 10, 1 );
	function bookacti_cancelled_order( $order_id ) {
		
		//Change state of all bookings of the order to 'cancelled' and free the bookings
		bookacti_turn_order_bookings_to( $order_id, 'cancelled', false );
		
		// It is possible that 'pending' bookings remain if the user has changed his cart before payment, we must cancel them
		bookacti_cancel_order_pending_bookings( $order_id );
	}
	
	
	//Turn paid order status to complete if the order has only activities
	add_filter( 'woocommerce_payment_complete_order_status', 'bookacti_set_order_status_to_completed_after_payment', 10, 2 );
	function bookacti_set_order_status_to_completed_after_payment( $order_status, $order_id ) {
		
		if( $order_status === 'processing' || $order_status === 'pending' ) {
		
			$order = new WC_Order( $order_id );
			if( ! is_null( $order ) ) {

				//Retrieve bought items
				$items = $order->get_items();

				//Determine if the order has at least 1 activity
				$has_activities = false;
				foreach( $items as $item ) {
					if( isset( $item[ 'bookacti_booking_id' ] ) ) {
						$has_activities = true;
						break;
					}
				}
				
				//Determine if the order is only composed of activities
				$are_activities = true;
				foreach( $items as $item ) {
					if( ! isset( $item[ 'bookacti_booking_id' ] ) ) {
						$are_activities = false;
						break;
					}
				}
				
				// If there are only activities, mark the order as 'completed' and the previous hook will mark the activities as 'booked'
				if( $are_activities ) {
					return 'completed';
					
				// If there are at least one activity in the middle of other products, 
				// we won't mark the order as 'completed', but we still need to mark the activities as 'booked'
				} else if( $has_activities ) {
					bookacti_turn_temporary_booking_to_permanent( $order_id );
				}
			}
		}
		
		return $order_status;
	}
	
	
// MY ACCOUNT
	// Add booking dialogs
	add_action( 'woocommerce_view_order', 'bookacti_add_booking_dialogs', 100, 1 );
	add_action( 'woocommerce_thankyou', 'bookacti_add_booking_dialogs', 100, 1 );
	function bookacti_add_booking_dialogs( $order_id ){
		include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-bookings-dialogs.php' );
	}
	
	
	// Add actions to bookings
	add_action( 'woocommerce_order_item_meta_end', 'bookacti_add_actions_to_bookings', 10, 4 );
	function bookacti_add_actions_to_bookings( $item_id, $item, $order, $plain_text = true ) {
		if( isset( $item['bookacti_booking_id'] ) && $plain_text ) {
			echo bookacti_get_booking_actions_html( $item['bookacti_booking_id'], 'front', true );
		}
	}
	

	
// BOOKING LIST
	// Change Customer name in bookings list
	add_filter( 'bookacti_booking_list_booking_columns', 'bookacti_change_customer_name_in_bookings_list', 10, 3 );
	function bookacti_change_customer_name_in_bookings_list( $booking_item, $booking, $user ) {
		
		if( is_numeric( $booking->user_id ) ) {
			$users	= bookacti_get_users_data_by_bookings( $booking->id );
			$user	= $users[ $booking->user_id ];
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
	
	
	// Add columns to bookings list
	add_filter( 'bookacti_booking_list_columns', 'bookacti_woocommerce_add_booking_list_custom_columns', 10, 1 );
	function bookacti_woocommerce_add_booking_list_custom_columns( $columns ) {
		
		$columns[ 'email' ] = __( 'Email', BOOKACTI_PLUGIN_NAME );
		$columns[ 'phone' ] = __( 'Phone', BOOKACTI_PLUGIN_NAME );
		
		return $columns;
	}
	
	
	// Reorder columns from booking list
	add_filter( 'bookacti_booking_list_columns_order', 'bookacti_woocommerce_order_booking_list_custom_columns', 10, 1 );
	function bookacti_woocommerce_order_booking_list_custom_columns( $columns_order ) {
		
		$columns_order[ 24 ] = 'email';
		$columns_order[ 27 ] = 'phone';
		
		return $columns_order;
	}
	
	
	// Filter booking list before retrieving bookings
	add_filter( 'bookacti_get_bookings_data_for_bookings_list', 'bookacti_filter_bookings_list_before_retrieving_bookings', 10, 1 );
	function bookacti_filter_bookings_list_before_retrieving_bookings( $bookings_data ) {
		$show_temporary_bookings = bookacti_get_setting_value_by_user( 'bookacti_bookings_settings', 'show_temporary_bookings' );
		if( intval( $show_temporary_bookings ) === 0 ) { $bookings_data[ 'state_not_in' ][] = 'in_cart'; }
		
		return $bookings_data;
	}
	
	
	// Fill booking list columns
	add_filter( 'bookacti_booking_list_booking_columns', 'bookacti_woocommerce_fill_booking_list_custom_columns', 10, 3 );
	function bookacti_woocommerce_fill_booking_list_custom_columns( $booking_item, $booking, $user ) {
		
		if( $user ) {
			if( is_numeric( $booking->user_id ) && $user->billing_first_name && $user->billing_last_name ) {
				$customer = '<a '
							. ' href="' . esc_url( get_admin_url() . 'user-edit.php?user_id=' . $booking->user_id ) . '" '
							. ' target="_blank" '
							. ' >'
								. esc_html( $user->billing_first_name . ' ' . $user->billing_last_name )
						. ' </a>';
			}
		}
		
		$booking_item[ 'customer' ]	= $customer ? $customer : $booking_item[ 'customer' ];
		$booking_item[ 'email' ]	= $user->billing_email;
		$booking_item[ 'phone' ]	= $user->billing_phone;
		
		return $booking_item;
	}
	
	
	
// BOOKING ACTIONS: CANCEL / REFUND / RESCHEDULE
	// Whether to give the possibility to a user to cancel or reschedule a booking
	// Also add woocommerce specifique actions
	add_filter( 'bookacti_booking_actions', 'bookacti_display_actions_buttons_on_items', 10, 2 );
	function bookacti_display_actions_buttons_on_items( $booking_actions, $booking_id ){
		
		// Init var
		$order_id	= bookacti_get_booking_order_id( $booking_id );
		
		if( $order_id && is_numeric( $order_id ) ) {
			$order		= new WC_Order( $order_id );

			// Check cancel / reschedule
			if( ! current_user_can( 'bookacti_edit_bookings' ) && $order->get_status() === 'pending' )	{ 
				unset( $booking_actions['cancel'] ); 
				unset( $booking_actions['reschedule'] );
			}

			// Add woocommerce specifique actions
			$booking_actions[ 'view-order' ] = array( 
				'class'			=> 'bookacti-view-booking-order _blank',
				'label'			=> __( 'View order', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'View the booking order.', BOOKACTI_PLUGIN_NAME ),
				'link'			=> get_admin_url() . 'post.php?post=' . $order_id . '&action=edit',
				'admin_or_front'=> 'admin' 
			);
		}
		
		return $booking_actions;
	}
	
	
	// Add refund actions
	add_filter( 'bookacti_refund_actions', 'bookacti_add_woocommerce_refund_actions', 10, 1 );
	function bookacti_add_woocommerce_refund_actions( $possible_actions_array ) {

		$possible_actions_array[ 'coupon' ] = array(
				'id'			=> 'coupon',
				'label'			=> __( 'Coupon', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'Create a coupon worth the price paid. The coupon can be used once for any orders at any time. ', BOOKACTI_PLUGIN_NAME )
			);
		$possible_actions_array[ 'auto' ] = array(
				'id'			=> 'auto',
				'label'			=> __( 'Auto refund', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'Refund automatically via the gateway used for payment.', BOOKACTI_PLUGIN_NAME )
			);

		return $possible_actions_array;
	}
	
	
	// Filter refund actions by bookings
	add_filter( 'bookacti_refund_actions_by_booking', 'bookacti_filter_refund_actions_by_booking', 10, 2 );
	function bookacti_filter_refund_actions_by_booking( $possible_actions, $booking_id ) {
		
		$order_id = bookacti_get_booking_order_id( $booking_id );
		if( $order_id ) {
			foreach( $possible_actions as $key => $possible_action ){
				// Allow auto-refund only if gateway allows it
				if( $possible_action['id'] === 'auto' && ! bookacti_does_order_support_auto_refund( $order_id ) ){
					unset( $possible_actions[ $key ] );
				}
			}
		} else {
			// If the booking has not been taken with WooCommerce, remove WooCommerce refund methods
			$woocommerce_actions = bookacti_add_woocommerce_refund_actions( array() );
			foreach( $woocommerce_actions as $woocommerce_action ) {
				unset( $possible_actions[ $woocommerce_action[ 'id' ] ] );
			}
		}
		
		return $possible_actions;
	}
	

	// Add price to be refund in refund dialog
	add_filter( 'bookacti_before_refund_actions', 'bookacti_display_price_to_be_refund', 10, 2 );
	function bookacti_display_price_to_be_refund( $text, $booking_id ) {
		$item = bookacti_get_order_item_by_booking_id( $booking_id );
		if( $item ) {
			$refund_amount = wc_price( (float) $item[ 'line_total' ] + (float) $item[ 'line_tax' ] );
			$text .= '<div id="bookacti-refund-amount">' . esc_html__( 'Refund amount:', BOOKACTI_PLUGIN_NAME ) . ' <strong>' . $refund_amount . '</strong></div>';
		}
		return $text;
	}
	

	// Turn meta state to new status
	add_action( 'bookacti_booking_state_changed', 'bookacti_woocommerce_turn_booking_meta_state_to_new_state', 10 , 3 );
	function bookacti_woocommerce_turn_booking_meta_state_to_new_state( $booking_id, $new_state, $args = array() ) {
		$item = bookacti_get_order_item_by_booking_id( $booking_id );
		
		// Turn meta state to new state
		$old_booking_state = wc_get_order_item_meta( $item[ 'id' ], 'bookacti_state', true );
		wc_update_order_item_meta( $item[ 'id' ], 'bookacti_state', $new_state );
		if( in_array( $new_state, array( 'refunded', 'refund_requested' ) ) ) {
			$refund_action = $args[ 'refund_action' ] ? $args[ 'refund_action' ] : 'manual';
			wc_update_order_item_meta( $item[ 'id' ], '_bookacti_refund_method', $refund_action );
		}
		
		
		$order_id = bookacti_get_booking_order_id( $booking_id );
		
		if( $order_id ) {
			$order = new WC_Order( $order_id );


			// Log booking state change
			if( $old_booking_state ) {
				if( $old_booking_state !== $new_state ) {
					$status_labels = bookacti_get_booking_state_labels();
					$is_customer_action = get_current_user_id() == bookacti_get_booking_owner( $booking_id );		
					/* translators: %1$s is booking id, %2$s is old state, %3$s is new state */
					$order->add_order_note( 
						sprintf( __( 'Booking #%1$s state has been updated from %2$s to %3$s.', BOOKACTI_PLUGIN_NAME ), 
								$booking_id, 
								$status_labels[ $old_booking_state ][ 'label' ], 
								$status_labels[ $new_state ][ 'label' ] ), 
						0, 
						$is_customer_action );
				}
			}


			// Turn the order state if it is composed of inactive / pending / booked bookings only
			if( in_array( $order->get_status(), array( 'processing', 'on-hold', 'completed' ) ) ) {

				$items = $order->get_items();

				// Get items booking states and
				// Determine if the order is only composed of activities
				$states = array();
				$only_activities = true;
				foreach( $items as $item ) {
					if( ! isset( $item[ 'bookacti_booking_id' ] ) ) { $only_activities = false; }
					$states[] = $item[ 'bookacti_state' ];
				}

				if( $only_activities ) {
					if( ! in_array( 'in_cart', $states ) ) {

						$new_order_status = 'cancelled';

						if( in_array( 'pending', $states ) ) {
							// Turn order status to pending payment
							$new_order_status = 'processing';
						} else if( in_array( 'booked', $states ) ) {
							// Turn order status to completed
							$new_order_status = 'completed';
						} else if( in_array( 'refunded', $states ) && ! in_array( 'refund_requested', $states ) ) {
							// Turn order status to completed
							$new_order_status = 'refunded';
						}

						$new_order_status = apply_filters( 'bookacti_woocommerce_order_status_updated_after_booking_state_update', $new_order_status, $order, $booking_id );

						if( $new_order_status !== $order->get_status() ) {
							$order->update_status( $new_order_status );
						}
					}
				}
			}
		}
	}
	
	
	// Add refund action process
	add_filter( 'bookacti_refund_booking', 'bookacti_woocommerce_refund_booking', 10 , 4 );
	function bookacti_woocommerce_refund_booking( $return_array, $booking_id, $refund_action, $refund_message ) {
		
		$order_id = bookacti_get_booking_order_id( $booking_id );
		$possibles_actions = array_keys( bookacti_get_refund_actions_by_booking_id( $booking_id ) );
		
		if( in_array( $refund_action, $possibles_actions ) ) {
			if( $refund_action === 'coupon' ) {
				$return_array = bookacti_refund_booking_with_coupon( $booking_id, $refund_message );
			} else if( $refund_action === 'auto' && bookacti_does_order_support_auto_refund( $order_id ) ) {
				$return_array = bookacti_auto_refund_booking( $booking_id, $refund_message );
			}
		}
		
		return $return_array;
	}
	
	
	// Check if a booking can be refunded
	add_filter( 'bookacti_booking_can_be_refunded', 'bookacti_woocommerce_booking_can_be_refunded', 10, 2 );
	function bookacti_woocommerce_booking_can_be_refunded( $true, $booking_id ) {
		
		if( $true && ! current_user_can( 'bookacti_edit_bookings' ) ) {
			// Init var
			$order_id	= bookacti_get_booking_order_id( $booking_id );
			$order		= wc_get_order( $order_id );
			if( $order ) {
				$item		= bookacti_get_order_item_by_booking_id( $booking_id );
				$is_paid	= get_post_meta( $order_id, '_paid_date', true );
				
				if( $order->get_status() === 'pending' 
				||  $item[ 'line_total' ] <= 0
				||  ! $is_paid ) { $true = false; }
			}
		}
		
		return $true;
	}
	
	
	// Check if a booking can be completed
	add_filter( 'bookacti_booking_state_can_be_changed_to_booked', 'bookacti_booking_state_can_be_changed_to_booked', 10, 2 );
	function bookacti_booking_state_can_be_changed_to_booked( $true, $booking_id ) {
		
		if( $true && ! current_user_can( 'bookacti_edit_bookings' ) ) {
			
			$order_id	= bookacti_get_booking_order_id( $booking_id );
			
			if( ! $order_id ) { return $true; }
			
			$order = new WC_Order( $order_id );
			
			if( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold' ) ) ) { $true = false; }
		}
		
		return $true;
	}
	
	
	// Refund request email to
	add_filter( 'bookacti_refund_request_email_to', 'bookacti_woocommerce_add_refund_request_email_recipients', 10, 2 );
	function bookacti_woocommerce_add_refund_request_email_recipients( $recipients, $booking_id ) {
		
		$recipients[] = get_option( 'woocommerce_stock_email_recipient' );
		
		return $recipients;
	}
	
	
	// Refund request email data
	add_filter( 'bookacti_refund_request_email_data', 'bookacti_woocommerce_add_refund_request_email_data', 10, 2 );
	function bookacti_woocommerce_add_refund_request_email_data( $data, $booking_id ) {
		
		$item = bookacti_get_order_item_by_booking_id( $booking_id );
		
		if( empty( $item ) ) { return $data; }
		
		$data['product']			= array();
		$data['product']['name']	= $item['name'];
		if( $item['variation_id'] ){
			$variation	= new WC_Product_Variation( $item['variation_id'] );
			$attributes	= $variation->get_variation_attributes();
			$data['product']['attributes'] = implode( ' / ', $attributes );
		}
		$data['product']['price'] = wc_price( (float) $item['line_total'] + (float) $item['line_tax'] );
		
		return $data;
	}
	
	
	// Refund request email message
	add_filter( 'bookacti_refund_request_email_message', 'bookacti_woocommerce_add_refund_request_email_message', 10, 4 );
	function bookacti_woocommerce_add_refund_request_email_message( $message, $booking_id, $data, $user_message ) {
		
		$order_id = bookacti_get_booking_order_id( $booking_id );
		
		if( ! $order_id ) { return $message; } 
		
		$go_to_order =   '<div style="background-color: #f5faff; padding: 10px; border: 1px solid #abc; margin-bottom: 30px;" >' 
						. esc_html__( 'Click here to go to the order page and process the refund:', BOOKACTI_PLUGIN_NAME ) 
						. ' <a target="_blank" href="' . esc_url( get_edit_post_link( $order_id ) ) . '">' 
							. esc_html__( 'Go to refund page', BOOKACTI_PLUGIN_NAME ) 
						. '</a>'
					. '</div>';
		
		return $go_to_order . $message;
	}
	
	
	// Update dates after reschedule
	add_action( 'bookacti_booking_rescheduled', 'bookacti_woocommerce_update_booking_dates', 10, 3 );
	function bookacti_woocommerce_update_booking_dates( $booking_id, $event_start, $event_end ) {
		
		$item = bookacti_get_order_item_by_booking_id( $booking_id );
		
		if( ! empty( $item ) ) {
			wc_update_order_item_meta( $item[ 'id' ], 'bookacti_event_start', $event_start );
			wc_update_order_item_meta( $item[ 'id' ], 'bookacti_event_end',   $event_end );
		}
	}
