<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL

/**
 * Register an hourly event to clean expired bookings
 * @since 1.7.1 (was bookacti_register_houly_clean_expired_bookings)
 * @version 1.7.3
 */
function bookacti_register_cron_event_to_clean_expired_bookings() {
	if( ! wp_next_scheduled ( 'bookacti_hourly_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'bookacti_hourly_event' );
	}
	if( ! wp_next_scheduled ( 'bookacti_delete_expired_bookings' ) ) {
		wp_schedule_event( time(), 'daily', 'bookacti_delete_expired_bookings' );
	}
}
add_action( 'woocommerce_installed', 'bookacti_register_cron_event_to_clean_expired_bookings' );
add_action( 'bookacti_activate', 'bookacti_register_cron_event_to_clean_expired_bookings' );


/**
 * Deregister the hourly event to clean expired bookings when WooCommerce is uninstalled (to be called on wp_roles_init)
 * @since 1.7.1 (was bookacti_clear_houly_clean_expired_bookings_on_woocommerce_uninstall)
 * @version 1.7.3
 */
function bookacti_clear_cron_event_to_clean_expired_bookings_on_woocommerce_uninstall() {
	if( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN === 'woocommerce/woocommerce.php' ) {
		bookacti_turn_in_cart_bookings_to_removed();
		bookacti_clear_cron_event_to_clean_expired_bookings();
	}
}
add_action( 'wp_roles_init', 'bookacti_clear_cron_event_to_clean_expired_bookings_on_woocommerce_uninstall' );


/**
 * Deregister the hourly event to clean expired bookings
 * @since 1.7.1 (was bookacti_clear_houly_clean_expired_bookings)
 * @version 1.7.3
 */
function bookacti_clear_cron_event_to_clean_expired_bookings() {
	wp_clear_scheduled_hook( 'bookacti_hourly_event' );
	wp_clear_scheduled_hook( 'bookacti_delete_expired_bookings' );
}
add_action( 'bookacti_deactivate', 'bookacti_clear_cron_event_to_clean_expired_bookings' );
add_action( 'bookacti_uninstall', 'bookacti_clear_cron_event_to_clean_expired_bookings' );


/**
 * Get customer id for non-logged in users
 * 
 * @since 1.4.0
 * @global woocommerce $woocommerce
 * @param int $current_user_id
 * @return string|0
 */
function bookacti_get_customer_id_for_non_logged_in_users( $current_user_id ) {
	if( $current_user_id ) { return $current_user_id; }

	global $woocommerce;
	if( isset( $woocommerce->session ) ) {
		return $woocommerce->session->get_customer_id();
	}

	return 0;
}
add_filter( 'bookacti_current_user_id', 'bookacti_get_customer_id_for_non_logged_in_users', 10, 1 );


/**
 * Add 'in_cart' state to active states
 * 
 * @param array $active_states
 * @return array
 */
function bookacti_add_woocommerce_active_booking_states( $active_states ) {
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( $is_expiration_active ) {
		$active_states[] = 'in_cart';
	}
	return $active_states;
}
add_filter( 'bookacti_active_booking_states', 'bookacti_add_woocommerce_active_booking_states' );


/**
 * Add booking states labels related to cart
 * @version 1.6.0
 * @param array $labels
 * @return array
 */
function bookacti_add_in_cart_state_label( $labels ) {
	$labels[ 'in_cart' ] =  array( 'display_state' => 'warning',	'label' => esc_html__( 'In cart', 'booking-activities' ) );
	$labels[ 'expired' ] =  array( 'display_state' => 'bad',		'label' => esc_html__( 'Expired', 'booking-activities' ) );
	$labels[ 'removed' ] =  array( 'display_state' => 'bad',		'label' => esc_html__( 'Removed', 'booking-activities' ) );

	return $labels;
}
add_filter( 'bookacti_booking_states_labels_array', 'bookacti_add_in_cart_state_label', 10, 1 );


/**
 * Deactivate expired bookings with cron
 * @since 1.0.6
 * @version 1.8.0
 */
function bookacti_controller_deactivate_expired_bookings() {
	$deactivated_ids = bookacti_deactivate_expired_bookings();
	if( ! is_array( $deactivated_ids ) ) { 
		/* translators: 'cron' is a robot that execute scripts every X hours. Don't try to translate it. */
		$log = esc_html__( 'The expired bookings were not correctly deactivated by cron.', 'booking-activities' );
		if( is_string( $deactivated_ids ) ) { $log .= ' ' . $deactivated_ids; }
		bookacti_log( $log, 'error' );
	}
}
add_action( 'bookacti_hourly_event', 'bookacti_controller_deactivate_expired_bookings' );


/**
 * Delete expired bookings with cron
 * @since 1.7.3
 */
function bookacti_controller_delete_expired_bookings() {
	$delay = apply_filters( 'bookacti_delay_before_deleting_expired_bookings', 10 );
	$deleted_ids = bookacti_delete_expired_bookings( $delay );

	if( $deleted_ids === false ) { 
		/* translators: 'cron' is a robot that execute scripts every X days. Don't try to translate it. */
		$log = esc_html__( 'The expired bookings were not correctly deleted by cron.', 'booking-activities' );
		bookacti_log( $log, 'error' );
	}
}
add_action( 'bookacti_delete_expired_bookings', 'bookacti_controller_delete_expired_bookings' );


/**
 * Change booking state to 'removed' or 'in_cart' depending on its quantity
 * 
 * @since 1.1.0
 * @version 1.3.0
 * 
 * @param array $data
 * @param object $booking
 * @return array
 */
function bookacti_change_booking_state_to_removed_or_in_cart_depending_on_its_quantity( $data, $booking ) {
	// If quantity is null, change booking state to 'removed' and keep the booking quantity
	if( $data[ 'quantity' ] <= 0 ) { 
		$data[ 'state' ]	= $data[ 'context' ] === 'frontend' ? 'removed' : 'cancelled'; 
		$data[ 'quantity' ]	= intval( $booking->quantity );
		$data[ 'active' ]	= in_array( $data[ 'state' ], bookacti_get_active_booking_states(), true ) ? 1 : 0;

	// If the booking was removed and its quantity is raised higher than 0, turn its state back to 'in_cart'
	} else if( $booking->state === 'removed' ) {
		$data[ 'state' ]	= $data[ 'context' ] === 'frontend' ? 'in_cart' : 'pending';
		$data[ 'active' ]	= in_array( $data[ 'state' ], bookacti_get_active_booking_states(), true ) ? 1 : 0;
	}
	return $data;
}
add_filter( 'bookacti_update_booking_quantity_data', 'bookacti_change_booking_state_to_removed_or_in_cart_depending_on_its_quantity', 10, 2 );




// ORDER AND BOOKING STATUS

/**
 * Turn a temporary booking to permanent if order gets complete
 * @version 1.8.0
 * @param int $order_id
 * @param WC_Order $order
 * @param string $booking_status
 * @param string $payment_status
 * @param boolean $force_status_notification
 */
function bookacti_turn_temporary_booking_to_permanent( $order_id, $order = null, $booking_status = 'booked', $payment_status = 'paid', $force_status_notification = false ) {
	if( ! $order ) { $order = wc_get_order( $order_id ); }

	// Change state of all bookings of the order from 'pending' to 'booked'
	$updated = bookacti_turn_order_bookings_to( $order, $booking_status, $payment_status, true, array( 'states_in' => array( 'pending', 'in_cart' ), 'force_status_notification' => $force_status_notification ) );

	// It is possible that pending bookings remain bound to the order if the user change his mind after he placed the order, but before he paid it.
	// He then changed his cart, placed a new order, paid it, and only part of the old order is booked (or even nothing), the rest is still 'pending'
	// Then we just turn 'pending' booking bound to this order to 'cancelled'
	bookacti_cancel_order_pending_bookings( $order_id, $updated[ 'booking_ids' ], $updated[ 'booking_group_ids' ] );
}
add_action( 'woocommerce_order_status_completed', 'bookacti_turn_temporary_booking_to_permanent', 5, 2 );


/**
 * Turn failed order bookings to complete when the order states changes
 * @since 1.7.13
 * @param int $order_id
 * @param string $old_status
 * @param string $new_status
 * @param WC_order $order
 */
function bookacti_turn_failed_order_bookings_status_to_complete( $order_id, $old_status, $new_status, $order = null ) {
	if( $old_status !== 'failed' || ! in_array( $new_status, array( 'completed', 'pending', 'on-hold', 'processing' ), true ) ) { return; }
	if( ! $order ) { $order = wc_get_order( $order_id ); }

	if( $order->get_date_paid() ) {
		$booking_status = 'booked';
		$payment_status = 'paid';
	} else {
		$booking_status = 'pending';
		$payment_status = 'owed';
	}

	// Change state of all bookings of the order from 'pending' to 'booked'
	$updated = bookacti_turn_order_bookings_to( $order, $booking_status, $payment_status, true, array( 'states_in' => array( 'pending', 'in_cart', 'cancelled' ) ) );
}
add_action( 'woocommerce_order_status_changed', 'bookacti_turn_failed_order_bookings_status_to_complete', 5, 4 );


/**
 * Cancel the order bookings if the order is cancelled or if it fails
 * @version 1.7.13
 * @param int $order_id
 * @param WC_Order $order
 */
function bookacti_cancelled_order( $order_id, $order = null ) {
	if( ! $order ) { $order = wc_get_order( $order_id ); }

	// Change state of all bookings of the order to 'cancelled' and free the bookings
	$response = bookacti_turn_order_bookings_to( $order, 'cancelled', NULL, false, array( 'states_in' => array( 'booked', 'pending', 'in_cart' ) ) );

	// It is possible that 'pending' bookings remain if the user has changed his cart before payment, we must cancel them
	bookacti_cancel_order_pending_bookings( $order_id );
}
add_action( 'woocommerce_order_status_cancelled', 'bookacti_cancelled_order', 5, 4 );
add_action( 'woocommerce_order_status_failed', 'bookacti_cancelled_order', 5, 4 );


/**
 * Turn paid order status to complete if the order has only virtual activities
 * @version 1.8.0
 * @param string $order_status
 * @param int $order_id
 * @return string
 */
function bookacti_set_order_status_to_completed_after_payment( $order_status, $order_id ) {
	if( ! in_array( $order_status, array( 'completed', 'processing', 'pending' ), true ) ) { return $order_status; }

	$order = wc_get_order( $order_id );
	if( empty( $order ) ) { return $order_status; }

	// Retrieve bought items
	$items = $order->get_items();

	// Check if the order has at least 1 activity
	$has_activities = false;
	foreach( $items as $item ) {
		if( isset( $item[ 'bookacti_booking_id' ] ) || isset( $item[ 'bookacti_booking_group_id' ] ) ) {
			$has_activities = true;
			break;
		}
	}

	// Check if the order is only composed of (virtual) activities
	$are_activities = true;
	$are_virtual_activities = true;
	foreach( $items as $item ) {
		// Is activity
		if( ! isset( $item[ 'bookacti_booking_id' ] ) && ! isset( $item[ 'bookacti_booking_group_id' ] )  ) {
			$are_activities = false;
		}

		// Is virtual
		$product = $item[ 'variation_id' ] ? wc_get_product( $item[ 'variation_id' ] ) : wc_get_product( $item[ 'product_id' ] );
		if( $product && ! $product->is_virtual() ) {
			$are_virtual_activities = false;
		}
	}

	// If there are only virtual activities, mark the order as 'completed' and 
	// a function hooked to woocommerce_order_status_completed will mark the activities as 'booked'
	if( $are_activities && $are_virtual_activities ) {
		$order_status = 'completed';

	// If there are only activities, but not virtuals, mark the order as 'processing' and 
	// a function hooked to woocommerce_order_status_pending_to_processing will mark the activities as 'booked'
	} else if( $are_activities ) {
		$order_status = 'processing';

	// If there are at least one activity in the middle of other products, 
	// we won't mark the order as 'completed', but we still need to mark the bookings as 'pending' and 'owed'
	// until the order changes state. At that time the bookings state will be redefined by other hooks
	// such as "woocommerce_order_status_pending_to_processing" and "woocommerce_order_status_completed"
	} else if( $has_activities ) {
		bookacti_turn_temporary_booking_to_permanent( $order_id, $order, 'pending', 'owed' );
	}

	return $order_status;
}
add_filter( 'woocommerce_payment_complete_order_status', 'bookacti_set_order_status_to_completed_after_payment', 20, 2 );


/**
 * Turn the bookings bound to order items of a paid order to booked
 * @since 1.7.18 (was bookacti_turn_non_activity_order_bookings_to_permanent)
 * @version 1.8.0
 * @param int $order_id
 * @param WC_Order $order
 */
function bookacti_turn_paid_order_item_bookings_to_permanent( $order_id, $order = null ) {
	if( ! $order ) { $order = wc_get_order( $order_id ); }
	if( ! $order ) { return; }

	// If the order hasn't been paid, the bookings are "pending" (virtual or not), we need to send the "pending" notification here
	if( ! $order->get_date_paid( 'edit' ) ) { 
		bookacti_send_notification_when_order_status_changes( $order_id, 'pending', array( 'force_status_notification' => true ) );
		return;
	}

	// Retrieve bought items
	$items = $order->get_items();

	// Get virtual order item booking ids
	$virtual_item_booking_ids		= array();
	$virtual_item_booking_group_ids	= array();
	$non_virtual_item_booking_ids		= array();
	$non_virtual_item_booking_group_ids	= array();
	foreach( $items as $item ) {
		// Is activity
		if( ! isset( $item[ 'bookacti_booking_id' ] ) && ! isset( $item[ 'bookacti_booking_group_id' ] )  ) { continue; }

		// Is virtual
		$product = $item[ 'variation_id' ] ? wc_get_product( $item[ 'variation_id' ] ) : wc_get_product( $item[ 'product_id' ] );
		if( ! $product ) { continue; }
		if( $product->is_virtual() ) {
			if( isset( $item[ 'bookacti_booking_id' ] ) )		{ $virtual_item_booking_ids[] = $item[ 'bookacti_booking_id' ]; }
			if( isset( $item[ 'bookacti_booking_group_id' ] ) ) { $virtual_item_booking_group_ids[] = $item[ 'bookacti_booking_group_id' ]; }
		} else {
			if( isset( $item[ 'bookacti_booking_id' ] ) )		{ $non_virtual_item_booking_ids[] = $item[ 'bookacti_booking_id' ]; }
			if( isset( $item[ 'bookacti_booking_group_id' ] ) ) { $non_virtual_item_booking_group_ids[] = $item[ 'bookacti_booking_group_id' ]; }	
		}
	}

	// Allow plugins to change the default booking status of paid non virtual bookings
	$non_virtual_booking_status = apply_filters( 'bookacti_paid_non_virtual_booking_status', 'booked' );
	// Turn all order bookings to booked
	if( $non_virtual_booking_status === 'booked' ) {
		bookacti_turn_temporary_booking_to_permanent( $order_id, $order, 'booked', 'paid' );

	} else {
		$updated_bookings = array( 'booking_ids' => array(), 'booking_group_ids' => array() );
		// Turn non virtual activities to their permanent booking status
		if( $non_virtual_item_booking_ids || $non_virtual_item_booking_group_ids ) {
			$updated_bookings = bookacti_turn_order_bookings_to( $order, $non_virtual_booking_status, 'paid', true, array( 'states_in' => array( 'in_cart', 'pending' ), 'in__booking_id' => $non_virtual_item_booking_ids, 'in__booking_group_id' => $non_virtual_item_booking_group_ids, 'force_status_notification' => true ) );
			if( $updated_bookings && is_numeric( $updated_bookings[ 'updated' ] ) && intval( $updated_bookings[ 'updated' ] ) === 0 ) {
				$notification_args = array_merge( $updated_bookings, array( 'force_status_notification' => true ) );
				bookacti_send_notification_when_order_status_changes( $order_id, $non_virtual_booking_status, $notification_args );
			}
		}

		// Turn virtual activities to booked in any case
		if( $virtual_item_booking_ids || $virtual_item_booking_group_ids ) {
			$updated_virtual_bookings = bookacti_turn_order_bookings_to( $order, 'booked', 'paid', true, array( 'states_in' => array( 'in_cart', 'pending' ), 'in__booking_id' => $virtual_item_booking_ids, 'in__booking_group_id' => $virtual_item_booking_group_ids ) );
			if( $updated_virtual_bookings ) {
				$updated_bookings[ 'booking_ids' ]		= array_merge( $updated_bookings[ 'booking_ids' ], $updated_virtual_bookings[ 'booking_ids' ] );
				$updated_bookings[ 'booking_group_ids' ]= array_merge( $updated_bookings[ 'booking_group_ids' ], $updated_virtual_bookings[ 'booking_group_ids' ] );
			}
		}

		// Remove remaining undesired bookings
		bookacti_cancel_order_pending_bookings( $order_id, $updated_bookings[ 'booking_ids' ], $updated_bookings[ 'booking_group_ids' ] );
	}
}
add_action( 'woocommerce_order_status_pending_to_processing', 'bookacti_turn_paid_order_item_bookings_to_permanent', 5, 2 );
add_action( 'woocommerce_order_status_pending_to_on-hold', 'bookacti_turn_paid_order_item_bookings_to_permanent', 5, 2 );




// MY ACCOUNT

/**
 * Include dialogs related to bookings
 * 
 * @param int $order_id
 */
function bookacti_add_booking_dialogs( $order_id ){
	include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-bookings-dialogs.php' );
}
add_action( 'woocommerce_view_order', 'bookacti_add_booking_dialogs', 100, 1 );
add_action( 'woocommerce_thankyou', 'bookacti_add_booking_dialogs', 100, 1 );


/**
 * Add actions html elements to booking rows
 * @version 1.7.11
 * @global boolean $bookacti_is_email
 * @param int $item_id
 * @param WC_Order_item $item
 * @param WC_Order $order
 * @param boolean $plain_text
 */
function bookacti_add_actions_to_bookings( $item_id, $item, $order, $plain_text = true ) {
	global $bookacti_is_email;

	// Don't display booking actions in emails, in plain text and in payment page
	if( ( isset( $bookacti_is_email ) && $bookacti_is_email ) || $plain_text || ( isset( $_GET[ 'pay_for_order' ] ) && $_GET[ 'pay_for_order' ] ) ) { 
		return;
	}

	if( isset( $item['bookacti_booking_id'] ) ) {
		echo bookacti_get_booking_actions_html( $item['bookacti_booking_id'], 'front', array(), false, true );
	} else if( isset( $item['bookacti_booking_group_id'] ) ) {
		echo bookacti_get_booking_group_actions_html( $item['bookacti_booking_group_id'], 'front', array(), false, true );
	}
}
add_action( 'woocommerce_order_item_meta_end', 'bookacti_add_actions_to_bookings', 10, 4 );


/**
 * Set a flag before displaying order items to decide whether to display booking actions
 * @since 1.4.0
 * @version 1.7.11
 * global boolean $bookacti_is_email
 * @param array $args
 * @return array
 */
function bookacti_order_items_set_email_flag( $args ) {
	if( defined( 'bookacti_is_email' ) ) {
		global $bookacti_is_email;
		$bookacti_is_email = true;
	} else {
		$GLOBALS[ 'bookacti_is_email' ] = true;
	}
	return $args;
}
add_filter( 'woocommerce_email_order_items_args', 'bookacti_order_items_set_email_flag', 10, 1 );


/**
 * Set a flag before displaying order items to decide whether to display booking actions
 * @since 1.7.11
 * @param string $html
 * @param WC_Order $order
 * @return string
 */
function bookacti_order_items_unset_email_flag( $html, $order ) {
	if( defined( 'bookacti_is_email' ) ) {
		global $bookacti_is_email;
		$bookacti_is_email = false;
	} else {
		$GLOBALS[ 'bookacti_is_email' ] = false;
	}
	return $html;
}
add_filter( 'woocommerce_email_order_items_table', 'bookacti_order_items_unset_email_flag', 10, 2 );




// BOOKING LIST

/**
 * Add WC data to the booking list
 * @since 1.6.0 (was bookacti_woocommerce_fill_booking_list_custom_columns before)
 * @version 1.8.0
 * @param array $booking_list_items
 * @param array $bookings
 * @param array $booking_groups
 * @param array $displayed_groups
 * @param array $users
 * @param Bookings_List_Table $booking_list
 * @return array
 */
function bookacti_add_wc_data_to_booking_list_items( $booking_list_items, $bookings, $booking_groups, $displayed_groups, $users, $booking_list ) {
	if( ! $booking_list_items ) { return $booking_list_items; }

	$admin_url = admin_url();

	$order_ids = array();
	$booking_ids = array();
	$booking_group_ids = array();
	foreach( $booking_list_items as $booking_id => $booking_list_item ) {
		// Get booking which are part of an order
		if( $booking_list_item[ 'order_id' ] ) {
			if( ! in_array( $booking_list_item[ 'order_id' ], $order_ids, true ) ) { $order_ids[] = $booking_list_item[ 'order_id' ]; }

			if( $booking_list_item[ 'booking_type' ] === 'group' ) { $booking_group_ids[] = $booking_list_item[ 'raw_id' ]; }
			else { $booking_ids[] = $booking_list_item[ 'raw_id' ]; }

			// Set a link for "view-order" action
			if( isset( $booking_list_item[ 'actions' ][ 'view-order' ] ) ) {
				$booking_list_items[ $booking_id ][ 'actions' ][ 'view-order' ][ 'link' ] = $admin_url . 'post.php?action=edit&post=' . $booking_list_item[ 'order_id' ];
			}
		} else {
			// Remove "view-order" action
			if( isset( $booking_list_item[ 'actions' ][ 'view-order' ] ) ) {
				unset( $booking_list_items[ $booking_id ][ 'actions' ][ 'view-order' ] );
			}
		}

		if( empty( $users[ $booking_list_item[ 'user_id' ] ] ) ) { continue; }
		$user = $users[ $booking_list_item[ 'user_id' ] ];
		if( $user ) {
			if( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'customer' ] ) ) {
				$display_name	= ! empty( $user->first_name ) && ! empty( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->display_name;
				$customer_name	= ! empty( $user->billing_first_name ) && ! empty( $user->billing_last_name ) ? $user->billing_first_name . ' ' . $user->billing_last_name : $display_name;
				$customer = '<a '
							. ' href="' . esc_url( $admin_url . 'user-edit.php?user_id=' . $booking_list_item[ 'user_id' ] ) . '" '
							. ' target="_blank" '
							. ' >'
								. esc_html( $customer_name )
						. ' </a>';
				$booking_list_items[ $booking_id ][ 'customer' ] = $customer;
			}

			if( ! empty( $user->billing_email ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'email' ] ) ) )	{ $booking_list_items[ $booking_id ][ 'email' ]	= $user->billing_email; }
			if( ! empty( $user->billing_phone ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'phone' ] ) ) )	{ $booking_list_items[ $booking_id ][ 'phone' ]	= $user->billing_phone; }
		}
	}

	// Get order item data
	$order_items_data = bookacti_get_order_items_data_by_bookings( $booking_ids, $booking_group_ids );
	if( ! $order_items_data ) { return $booking_list_items; }

	// Get WC orders by order item id
	$orders = array();
	$orders_array = wc_get_orders( array( 'post__in' => $order_ids ) );
	foreach( $orders_array as $order ) {
		$order_id = $order->get_id();
		$orders[ $order_id ] = $order;
	}

	// Get WC refund actions
	$wc_refund_actions = array_keys( bookacti_add_woocommerce_refund_actions( array() ) );

	// Add order item data to the booking list
	foreach( $order_items_data as $order_item_data ) {
		// Booking group
		if( ! empty( $order_item_data->bookacti_booking_group_id ) ) {
			$booking_group_id = $order_item_data->bookacti_booking_group_id;
			if( ! isset( $displayed_groups[ $booking_group_id ] ) ) { continue; }
			$booking_id = $displayed_groups[ $booking_group_id ];
		}

		// Single booking
		else if( ! empty( $order_item_data->bookacti_booking_id ) ) {
			$booking_id = $order_item_data->bookacti_booking_id;
		}

		if( ! isset( $booking_list_items[ $booking_id ] ) ) { continue; }

		// Fill product column
		$product_title = '';
		if( ! empty( $order_item_data->order_item_name ) ) {
			$product_title = apply_filters( 'bookacti_translate_text', $order_item_data->order_item_name );
		}
		if( ! empty( $order_item_data->_product_id ) ) {
			$product_id = $order_item_data->_product_id;
			$product_title = '<a href="' . esc_url( $admin_url . 'post.php?action=edit&post=' . $product_id ) . '" target="_blank">' . $product_title . '</a>';
		}
		$booking_list_items[ $booking_id ][ 'product' ] = $product_title;

		// Fill price column
		$booking_list_items[ $booking_id ][ 'price_details' ][ 'order_item' ] = array(
			'title' => esc_html__( 'WC item', 'booking-activities' ),
			'value' => $order_item_data->_line_total + $order_item_data->_line_tax,
			'display_value' => wc_price( $order_item_data->_line_total + $order_item_data->_line_tax )
		);

		// Specify refund method in status column
		if( $bookings[ $booking_id ]->state === 'refunded' && ! empty( $order_item_data->bookacti_refund_coupon ) ) {
			$coupon_code = $order_item_data->bookacti_refund_coupon;
			/* translators: %s is the coupon code used for the refund */
			$coupon_label = sprintf( esc_html__( 'Refunded with coupon %s', 'booking-activities' ), $coupon_code );
			$booking_list_items[ $booking_id ][ 'state' ] = '<span class="bookacti-booking-state bookacti-booking-state-bad bookacti-booking-state-refunded bookacti-converted-to-coupon bookacti-tip" data-booking-state="refunded" data-tip="' . $coupon_label . '" ></span><span class="bookacti-refund-coupon-code bookacti-custom-scrollbar">' . $coupon_code . '</span>';
		}

		// Filter refund actions
		if( ! empty( $booking_list_items[ $booking_id ][ 'actions' ][ 'refund' ] ) && ! empty( $orders[ $order_item_data->order_id ] ) ) {
			$order		= $orders[ $order_item_data->order_id ];
			$is_paid	= $order->get_date_paid( 'edit' );
			$total		= isset( $order_item_data->_line_total ) ? $order_item_data->_line_total + $order_item_data->_line_tax : '';

			if( $order->get_status() !== 'pending' && $is_paid && $total > 0 ) {
				$booking_list_items[ $booking_id ][ 'refund_actions' ] = array_unique( array_merge( $booking_list_items[ $booking_id ][ 'refund_actions' ], $wc_refund_actions ) );
			}
		}
	}

	return apply_filters( 'bookacti_booking_list_items_with_wc_data', $booking_list_items, $bookings, $booking_groups, $displayed_groups, $users, $booking_list, $orders, $order_items_data );
}
add_filter( 'bookacti_booking_list_items', 'bookacti_add_wc_data_to_booking_list_items', 10, 6 );


/**
 * Fill WC bookings export columns
 * @since 1.6.0
 * @version 1.8.0
 * @param array $booking_items
 * @param array $bookings
 * @param array $booking_groups
 * @param array $displayed_groups
 * @param array $users
 * @param array $args
 * @return array
 */
function bookacti_fill_wc_columns_in_bookings_export( $booking_items, $bookings, $booking_groups, $displayed_groups, $users, $args ) {
	if( ! $booking_items ) { return $booking_items; }

	if( array_intersect( $args[ 'columns' ], array( 'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone', 'product_id', 'variation_id', 'order_item_title', 'order_item_price', 'order_item_tax' ) ) ) {
		$booking_ids = array();
		$booking_group_ids = array();
		foreach( $booking_items as $booking_id => $booking_item ) {
			// Get booking which are part of an order
			if( $booking_item[ 'order_id' ] ) {
				if( $booking_item[ 'booking_type' ] === 'group' ) { $booking_group_ids[] = $booking_item[ 'booking_id' ]; }
				else { $booking_ids[] = $booking_item[ 'booking_id' ]; }
			}

			if( empty( $users[ $booking_item[ 'customer_id' ] ] ) ) { continue; }
			$user = $users[ $booking_item[ 'customer_id' ] ];
			if( $user ) {
				if( ! empty( $user->billing_first_name ) && ( $booking_item[ 'order_id' ] || empty( $booking_item[ 'customer_first_name' ] ) ) ){ $booking_items[ $booking_id ][ 'customer_first_name' ] = $user->billing_first_name; }
				if( ! empty( $user->billing_last_name ) && ( $booking_item[ 'order_id' ] || empty( $booking_item[ 'customer_last_name' ] ) ) )	{ $booking_items[ $booking_id ][ 'customer_last_name' ] = $user->billing_last_name; }
				if( ! empty( $user->billing_email ) && ( $booking_item[ 'order_id' ] || empty( $booking_item[ 'customer_email' ] ) ) )			{ $booking_items[ $booking_id ][ 'customer_email' ] = $user->billing_email; }
				if( ! empty( $user->billing_phone ) && ( $booking_item[ 'order_id' ] || empty( $booking_item[ 'customer_phone' ] ) ) )			{ $booking_items[ $booking_id ][ 'customer_phone' ] = $user->billing_phone; }
			}
		}
	}

	if( array_intersect( $args[ 'columns' ], array( 'product_id', 'variation_id', 'order_item_title', 'order_item_price', 'order_item_tax' ) ) ) {
		// Order item data
		$order_items_data = bookacti_get_order_items_data_by_bookings( $booking_ids, $booking_group_ids );
		if( ! $order_items_data ) { return $booking_items; }

		// Add order item data to the booking list
		foreach( $order_items_data as $order_item_data ) {
			// Booking group
			if( ! empty( $order_item_data->bookacti_booking_group_id ) ) {
				$booking_group_id = $order_item_data->bookacti_booking_group_id;
				if( ! isset( $displayed_groups[ $booking_group_id ] ) ) { continue; }
				$booking_id = $displayed_groups[ $booking_group_id ];
				if( ! isset( $booking_items[ $booking_id ] ) ) { continue; }
			}

			// Single booking
			else if( ! empty( $order_item_data->bookacti_booking_id ) ) {
				$booking_id = $order_item_data->bookacti_booking_id;
			}

			$booking_items[ $booking_id ][ 'product_id' ]		= ! empty( $order_item_data->_product_id ) ? $order_item_data->_product_id : '';
			$booking_items[ $booking_id ][ 'variation_id' ]		= ! empty( $order_item_data->_variation_id ) ? $order_item_data->_variation_id : '';
			$booking_items[ $booking_id ][ 'order_item_title' ]	= ! empty( $order_item_data->order_item_name ) ? apply_filters( 'bookacti_translate_text', $order_item_data->order_item_name, $args[ 'locale' ] ) : '';
			$booking_items[ $booking_id ][ 'order_item_price' ]	= isset( $order_item_data->_line_total ) ? ( $args[ 'raw' ] ? $order_item_data->_line_total : wc_price( $order_item_data->_line_total ) ) : '';
			$booking_items[ $booking_id ][ 'order_item_tax' ]	= isset( $order_item_data->_line_tax ) ? ( $args[ 'raw' ] ? $order_item_data->_line_tax : wc_price( $order_item_data->_line_tax ) ) : '';
		}
	}

	return $booking_items;
}
add_filter( 'bookacti_booking_items_to_export', 'bookacti_fill_wc_columns_in_bookings_export', 10, 6 );


/**
 * Add WC bookings export columns
 * @since 1.6.0
 * @param array $columns_labels
 * @return array
 */
function bookacti_wc_bookings_export_columns( $columns_labels ) {
	$columns_labels[ 'product_id' ]			= esc_html__( 'Product ID', 'booking-activities' );
	$columns_labels[ 'variation_id' ]		= esc_html__( 'Product variation ID', 'booking-activities' );
	$columns_labels[ 'order_item_title' ]	= esc_html__( 'Product title', 'booking-activities' );
	$columns_labels[ 'order_item_price' ]	= esc_html__( 'Product price', 'booking-activities' );
	$columns_labels[ 'order_item_tax' ]		= esc_html__( 'Product tax', 'booking-activities' );
	return $columns_labels;
}
add_filter( 'bookacti_bookings_export_columns_labels', 'bookacti_wc_bookings_export_columns', 10, 1 );


/**
 * Add columns to booking list
 * @version 1.5.0
 * @param array $columns
 * @return array
 */
function bookacti_woocommerce_add_booking_list_custom_columns( $columns ) {
	$columns[ 'product' ] = __( 'Product', 'booking-activities' );
	return $columns;
}
add_filter( 'bookacti_booking_list_columns', 'bookacti_woocommerce_add_booking_list_custom_columns', 10, 1 );


/**
 * Reorder columns from booking list
 * @version 1.5.0
 * @param array $columns_order
 * @return array
 */
function bookacti_woocommerce_order_booking_list_custom_columns( $columns_order ) {
	$columns_order[ 65 ] = 'product';
	return $columns_order;
}
add_filter( 'bookacti_booking_list_columns_order', 'bookacti_woocommerce_order_booking_list_custom_columns', 10, 1 );


/**
 * Edit default hidden columns in booking list
 * 
 * @since 1.5.0
 * @param array $hidden_columns
 * @return array
 */
function bookacti_woocommerce_booking_list_hidden_columns( $hidden_columns ) {
	$hidden_columns[] = 'product';
	return $hidden_columns;
}
add_filter( 'bookacti_booking_list_default_hidden_columns', 'bookacti_woocommerce_booking_list_hidden_columns', 10, 1 );


/**
 * Controller - Get WC order items rows
 * @since 1.7.4
 * @param string $rows
 * @param string $context
 * @param array $filters
 * @param array $columns
 * @return string
 */
function bookacti_controller_get_order_items_rows( $rows, $context, $filters, $columns ) {
	if( $context !== 'wc_order_items' ) { return $rows; }

	$order_items = array();
	if( ! empty( $filters[ 'booking_id' ] ) ) {
		$order_items[] = bookacti_get_order_item_by_booking_id( $filters[ 'booking_id' ] );
	}
	if( ! empty( $filters[ 'in__booking_id' ] ) ) {
		foreach( $filters[ 'in__booking_id' ] as $booking_id ) {
			$order_items[] = bookacti_get_order_item_by_booking_id( $booking_id );
		}
	}
	if( ! empty( $filters[ 'booking_group_id' ] ) ) {
		$order_items[] = bookacti_get_order_item_by_booking_group_id( $filters[ 'booking_group_id' ] );
	}
	if( ! empty( $filters[ 'in__booking_group_id' ] ) ) {
		foreach( $filters[ 'in__booking_group_id' ] as $booking_group_id ) {
			$order_items[] = bookacti_get_order_item_by_booking_group_id( $booking_group_id );
		}
	}

	return bookacti_get_order_items_rows( $order_items );
}
add_filter( 'booking_list_rows_according_to_context', 'bookacti_controller_get_order_items_rows', 10, 4 );




// BOOKING ACTIONS: CANCEL / REFUND / RESCHEDULE / DELETE

/**
 * Add WC booking actions
 * @since 1.6.0
 * @param array $actions
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_wc_booking_actions( $actions, $admin_or_front ) {
	if( in_array( $admin_or_front, array( 'admin', 'both' ), true ) && ! isset( $actions[ 'view-order' ] ) ) {
		$actions[ 'view-order' ] = array( 
			'class'			=> 'bookacti-view-booking-order _blank',
			'label'			=> __( 'View order', 'booking-activities' ),
			'description'	=> __( 'Go to the related WooCommerce admin order page.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' 
		);
	}
	return $actions;
}
add_filter( 'bookacti_booking_actions', 'bookacti_wc_booking_actions', 10, 2 );
add_filter( 'bookacti_booking_group_actions', 'bookacti_wc_booking_actions', 10, 2 );


/**
 * Get booking actions according to the order bound to the booking
 * @since 1.6.0 (replace bookacti_display_actions_buttons_on_booking_items)
 * @param array $actions
 * @param object $booking
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_wc_booking_actions_per_booking( $actions, $booking, $admin_or_front ) {
	if( ! $actions || ! $booking ) { return $actions; }
	return bookacti_wc_booking_actions_per_order_id( $actions, $booking->order_id );
}
add_filter( 'bookacti_booking_actions_by_booking', 'bookacti_wc_booking_actions_per_booking', 10, 3 );


/**
 * Get booking group actions according to the order bound to the booking group
 * @since 1.6.0 (replace bookacti_display_actions_buttons_on_booking_group_items)
 * @param array $actions
 * @param object $booking_group
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_wc_booking_group_actions_per_booking_group( $actions, $booking_group, $admin_or_front ) {
	if( ! $actions || ! $booking_group ) { return $actions; }
	return bookacti_wc_booking_actions_per_order_id( $actions, $booking_group->order_id );
}
add_filter( 'bookacti_booking_group_actions_by_booking_group', 'bookacti_wc_booking_group_actions_per_booking_group', 10, 3 );


/**
 * Filter refund actions by booking
 * @version 1.7.14
 * @param array $possible_actions
 * @param int|object $booking
 * @param string $context
 * @return array
 */
function bookacti_filter_refund_actions_by_booking( $possible_actions, $booking, $context = '' ) {
	$order_id = is_numeric( $booking ) ? bookacti_get_booking_order_id( $booking ) : $booking->order_id;
	return bookacti_filter_refund_actions_by_order( $possible_actions, $order_id );
}
add_filter( 'bookacti_refund_actions_by_booking', 'bookacti_filter_refund_actions_by_booking', 10, 3 );


/**
 * Filter refund actions by booking group
 * @since 1.1.0
 * @version 1.7.14
 * @param array $possible_actions
 * @param int|object $booking_group
 * @param string $context
 * @return array
 */
function bookacti_filter_refund_actions_by_booking_group( $possible_actions, $booking_group, $context = '' ) {
	$order_id = is_numeric( $booking_group ) ? bookacti_get_booking_group_order_id( $booking_group ) : $booking_group->order_id;
	return bookacti_filter_refund_actions_by_order( $possible_actions, $order_id );
}
add_filter( 'bookacti_refund_actions_by_booking_group', 'bookacti_filter_refund_actions_by_booking_group', 10, 3 );


/**
 * Add price to be refunded in refund dialog
 * @version 1.8.0
 * @param string $refund_amount
 * @param int $booking_id
 * @param string $booking_type
 * @return string
 */
function bookacti_display_price_to_be_refunded( $refund_amount, $booking_id, $booking_type ) {
	if( $booking_type === 'single' ) {
		$item = bookacti_get_order_item_by_booking_id( $booking_id );
	} else if( $booking_type === 'group' ) {
		$item = bookacti_get_order_item_by_booking_group_id( $booking_id );
	}
	if( $item ) {
		$item_price	= (float) $item->get_total() + (float) $item->get_total_tax();
		if( $item_price ) {
			$refund_amount = wc_price( $item_price );
		}
	}
	return $refund_amount;
}
add_filter( 'bookacti_booking_refund_amount', 'bookacti_display_price_to_be_refunded', 20, 3 );


/**
 * Add WooCommerce related refund actions
 * 
 * @param array $possible_actions_array
 * @return array
 */
function bookacti_add_woocommerce_refund_actions( $possible_actions_array ) {

	$possible_actions_array[ 'coupon' ] = array(
			'id'			=> 'coupon',
			'label'			=> __( 'Coupon', 'booking-activities' ),
			'description'	=> __( 'Create a coupon worth the price paid. The coupon can be used once for any orders at any time. ', 'booking-activities' )
		);
	$possible_actions_array[ 'auto' ] = array(
			'id'			=> 'auto',
			'label'			=> __( 'Auto refund', 'booking-activities' ),
			'description'	=> __( 'Refund automatically via the gateway used for payment.', 'booking-activities' )
		);

	return $possible_actions_array;
}
add_filter( 'bookacti_refund_actions', 'bookacti_add_woocommerce_refund_actions', 10, 1 );


/**
 * Turn order item booking status meta to new status according to given booking id
 * @since 1.2.0 (was bookacti_woocommerce_turn_booking_meta_state_to_new_state before)
 * @version 1.5.4
 * @param int $booking_id
 * @param string $new_state
 * @param array $args
 */
function bookacti_update_order_item_booking_status_by_booking_id( $booking_id, $new_state, $args = array() ) {

	if( ! $booking_id ) { return; }

	$order_id = bookacti_get_booking_order_id( $booking_id );

	if( ! $order_id ) { return; }

	$order = wc_get_order( $order_id );

	if( ! $order ) { return; }

	$item = bookacti_get_order_item_by_booking_id( $booking_id );

	if( ! $item ) { return; }

	bookacti_update_order_item_booking_status( $item, $new_state, $order, $args );
}
add_action( 'bookacti_booking_state_changed', 'bookacti_update_order_item_booking_status_by_booking_id', 10 , 3 );


/**
 * Turn order item booking status meta to new status according to given booking group id
 *
 * @since 1.2.0 (was named bookacti_woocommerce_turn_booking_group_meta_state_to_new_state before)
 * 
 * @param int $booking_group_id
 * @param string $new_state
 * @param array $args
 * @return void
 */
function bookacti_update_order_item_booking_group_status_by_booking_group_id( $booking_group_id, $new_state, $args = array() ) {

	if( ! $booking_group_id ) { return; }

	$order_id = bookacti_get_booking_group_order_id( $booking_group_id );

	if( ! $order_id ) {	return;	}

	$order = wc_get_order( $order_id );

	if( ! $order ) { return; }

	$item = bookacti_get_order_item_by_booking_group_id( $booking_group_id );

	if( ! $item || ! isset( $item[ 'bookacti_booking_group_id' ] ) ) { return; }

	bookacti_update_order_item_booking_status( $item, $new_state, $order, $args );
}
add_action( 'bookacti_booking_group_state_changed', 'bookacti_update_order_item_booking_group_status_by_booking_group_id', 10 , 3 );


/**
 * Turn order items booking status meta to new status
 * @since 1.2.0
 * @version 1.8.0
 * @param WC_Order $order
 * @param string $new_state
 * @param array $args
 */
function bookacti_update_order_items_booking_status_by_order_id( $order, $new_state, $args ) {
	if( is_numeric( $order ) ) { $order = wc_get_order( $order ); }
	if( ! $order ) { return; }

	$order_items = $order->get_items();

	if( ! $order_items ) { return; }

	$in__booking_id			= ! empty( $args[ 'booking_ids' ] ) ? $args[ 'booking_ids' ] : array();
	$in__booking_group_id	= ! empty( $args[ 'booking_group_ids' ] ) ? $args[ 'booking_group_ids' ] : array();

	foreach( $order_items as $item_id => $item ) {
		// Make sure the order item has a booking
		if( ! isset( $item[ 'bookacti_booking_id' ] ) && ! isset( $item[ 'bookacti_booking_group_id' ] ) ) { continue; }

		// Make sure the booking is part of those updated
		if( isset( $item[ 'bookacti_booking_id' ] ) && ( $in__booking_id || $in__booking_group_id ) && ! in_array( $item[ 'bookacti_booking_id' ], $in__booking_id ) ) { continue; }
		if( isset( $item[ 'bookacti_booking_group_id' ] ) && ( $in__booking_id || $in__booking_group_id ) && ! in_array( $item[ 'bookacti_booking_group_id' ], $in__booking_group_id ) ) { continue; }

		// Do not allow to update order status based on new bookings status 
		// because this current function was already triggered by an order status change
		$args[ 'keep_order_status' ] = 1;

		bookacti_update_order_item_booking_status( $item, $new_state, $order, $args );
	}
}
add_action( 'bookacti_order_bookings_state_changed', 'bookacti_update_order_items_booking_status_by_order_id', 20, 3 );


/**
 * Trigger WooCommerce refund process according to the refund action
 * @version 1.8.0
 * @param array $return_array
 * @param int $booking_id
 * @param string $refund_action
 * @param string $refund_message
 * @param string $context
 * @return array
 */
function bookacti_woocommerce_refund_booking( $return_array, $booking_id, $booking_type, $refund_action, $refund_message, $context = '' ) {
	if( $booking_type === 'single' ) {
		$order_id = bookacti_get_booking_order_id( $booking_id );
	} else if( $booking_type === 'group' ) {
		$order_id = bookacti_get_booking_group_order_id( $booking_id );
	}
	$possibles_actions = array_keys( bookacti_get_booking_refund_actions( $booking_id, $booking_type, $context ) );

	if( in_array( $refund_action, $possibles_actions, true ) ) {
		if( $refund_action === 'coupon' ) {
			$return_array = bookacti_refund_booking_with_coupon( $booking_id, $booking_type, $refund_message );
			if( $return_array[ 'status' ] === 'success' && isset( $return_array[ 'coupon_code' ] ) && isset( $return_array[ 'coupon_amount' ] ) ) {
				/* translators: %s is the amount of the coupon. E.g.: $10. */
				$return_array[ 'message' ] = sprintf( esc_html__( 'A %s coupon has been created. You can use it once for any order at any time.', 'booking-activities' ), $return_array[ 'coupon_amount' ] );
				/* translators: %s is the coupon code. E.g.: AAB12. */
				$return_array[ 'message' ] .= '<br/>' . sprintf(  esc_html__( 'The coupon code is %s. Use it on your next cart!', 'booking-activities' ), $return_array[ 'coupon_code' ] );
			}
		} else if( $refund_action === 'auto' && bookacti_does_order_support_auto_refund( $order_id ) ) {
			$return_array = bookacti_auto_refund_booking( $booking_id, $booking_type, $refund_message );
		}
	}

	return $return_array;
}
add_filter( 'bookacti_refund_booking', 'bookacti_woocommerce_refund_booking', 10, 6 );


/**
 * Check if a booking can be refunded
 * @version 1.8.3
 * @param boolean $true
 * @param object $booking
 * @return boolean
 */
function bookacti_woocommerce_booking_can_be_refunded( $true, $booking ) {
	if( ! $true ) { return $true; }

	// Init var
	$order = wc_get_order( $booking->order_id );

	if( ! $order ) { return $true; }
	if( $order->get_status() === 'pending' ) { return false; }

	$is_paid = $order->get_date_paid( 'edit' );
	if( ! $is_paid ) { return false; }

	$item = bookacti_get_order_item_by_booking_id( $booking->id );
	if( ! $item ) { return false; }

	$total = (float) $item->get_total() + (float) $item->get_total_tax();
	if( $total <= 0 ) { return false; }

	return apply_filters( 'bookacti_woocommerce_booking_can_be_refunded', $true, $booking, $order, $item );
}
add_filter( 'bookacti_booking_can_be_refunded', 'bookacti_woocommerce_booking_can_be_refunded', 10, 2 );


/**
 * Check if a booking group can be refunded
 * @version 1.8.3
 * @param boolean $true
 * @param object $booking_group
 * @return boolean
 */
function bookacti_woocommerce_booking_group_can_be_refunded( $true, $booking_group ) {
	if( ! $true || current_user_can( 'bookacti_edit_bookings' ) ) { return $true; }

	$order = wc_get_order( $booking_group->order_id );
	if( ! $order ) { return $true; }
	if( $order->get_status() === 'pending' ) { $true = false; }

	$is_paid = $order->get_date_paid( 'edit' );
	if( ! $is_paid ) { $true = false; }	

	$item = bookacti_get_order_item_by_booking_group_id( $booking_group );
	if( ! $item ) { return false; }

	$total = (float) $item->get_total() + (float) $item->get_total_tax();
	if( $total <= 0 ) { $true = false; }

	return $true;
}
add_filter( 'bookacti_booking_group_can_be_refunded', 'bookacti_woocommerce_booking_group_can_be_refunded', 10, 2 );


/**
 * Check if a booking can be completed
 * @version 1.6.0
 * @param boolean $true
 * @param object|int $booking_id
 * @return boolean
 */
function bookacti_booking_state_can_be_changed_to_booked( $true, $booking, $new_state ) {

	if( $true && $new_state === 'booked' && ! current_user_can( 'bookacti_edit_bookings' ) ) {

		if( is_int( $booking ) ) {
			$order_id = bookacti_get_booking_order_id( $booking );
		} else {
			$order_id = $booking->order_id;
		}

		if( ! $order_id || ! is_numeric( $order_id ) ) { return $true; }

		$order = wc_get_order( $order_id );

		if( empty( $order ) ) { return false; }

		if( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold' ), true ) ) { $true = false; }
	}

	return $true;
}
add_filter( 'bookacti_booking_state_can_be_changed', 'bookacti_booking_state_can_be_changed_to_booked', 10, 3 );


/**
 * Check if a booking group can be completed
 * @since 1.1.0
 * @version 1.6.0
 * @param boolean $true
 * @param object $booking_group
 * @return boolean
 */
function bookacti_booking_group_state_can_be_changed_to_booked( $true, $booking_group, $new_state ) {
	if( $true && $new_state === 'booked' && ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( ! $booking_group->order_id || ! is_numeric( $booking_group->order_id ) ) { return $true; }

		$order = wc_get_order( $booking_group->order_id );
		if( empty( $order ) ) { return false; }

		if( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold' ), true ) ) { $true = false; }
	}
	return $true;
}
add_filter( 'bookacti_booking_group_state_can_be_changed', 'bookacti_booking_group_state_can_be_changed_to_booked', 10, 3 );


/**
 * Update dates after reschedule
 * @version 1.8.0
 * @param object $booking
 * @param object $old_booking
 * @param array $args
 * @return void
 */
function bookacti_woocommerce_update_booking_dates( $booking, $old_booking, $args ) {
	if( ! $booking ) { return; }

	$item = bookacti_get_order_item_by_booking_id( $booking );
	if( ! $item ) { return; }

	$order_item_id = $item->get_id();

	$booked_events = wc_get_order_item_meta( $order_item_id, 'bookacti_booked_events' );

	if( ! empty( $booked_events ) ) {

		$booked_events = (array) json_decode( $booked_events );

		foreach( $booked_events as $i => $booked_event ) {
			if( intval( $booked_event->id ) === intval( $booking->id ) ) {
				$key = $i;
				break;
			}
		}

		if( ! isset( $key ) ) { return;	}

		$booked_events[ $key ]->event_start	= $booking->event_start;
		$booked_events[ $key ]->event_end	= $booking->event_end;

		wc_update_order_item_meta( $order_item_id, 'bookacti_booked_events', json_encode( $booked_events ) );

	// For bookings made before Booking Activities 1.1.0
	} else {
		// Delete old data
		wc_delete_order_item_meta( $order_item_id, 'bookacti_event_start' );
		wc_delete_order_item_meta( $order_item_id, 'bookacti_event_end' );

		// Insert new booking data
		$event = bookacti_get_booking_event_data( $booking->id );
		wc_add_order_item_meta( $order_item_id, 'bookacti_booked_events', json_encode( array( $event ) ) );
	}
}
add_action( 'bookacti_booking_rescheduled', 'bookacti_woocommerce_update_booking_dates', 10, 3 );


/**
 * Add WC fields to delete booking form
 * @since 1.5.0
 * @version 1.8.0
 */
function bookacti_add_wc_fields_to_delete_booking_form() {
?>
	<div class='bookacti-delete-wc-order-item-container' style='display:none;'>
		<hr/>
		<p class='bookacti-error bookacti-delete-wc-order-item-description'>
			<span class='dashicons dashicons-warning'></span>
			<span>
				<?php esc_html_e( 'This booking is bound to an item in a WooCommerce order. Do you want to remove the booking data from this item as well?', 'booking-activities' ); ?>
			</span>
		</p>
		<?php
			$args = array(
				'type' => 'select',
				'name' => 'order-item-action',
				'value' => 'none',
				'options' => array(
					'none' => esc_html__( 'Do nothing', 'booking-activities' ),
					'delete_meta' => esc_html__( 'Delete the booking metadata', 'booking-activities' ),
					'delete_item' => esc_html__( 'Delete the whole item', 'booking-activities' )
				),
				/* translators: %s is the option name corresponding to this description */
				'tip' => sprintf( esc_html__( '%s: The WooCommerce order item will be kept as is.', 'booking-activities' ), '<strong>' . esc_html__( 'Do nothing', 'booking-activities' ) . '</strong>' )
				/* translators: %s is the option name corresponding to this description */
				. '<br/>' . sprintf( esc_html__( '%s: The order item will be kept as a normal product. All its metadata concerning the booking will be removed.', 'booking-activities' ), '<strong>' . esc_html__( 'Delete the booking metadata', 'booking-activities' ) . '</strong>' )
				/* translators: %s is the option name corresponding to this description */
				. '<br/>' . sprintf( esc_html__( '%s: The item will be totally removed from the order.', 'booking-activities' ), '<strong>' . esc_html__( 'Delete the whole item', 'booking-activities' ) . '</strong>' )
			);
			bookacti_display_field( $args );
		?>
	</div>
<?php
}
add_action( 'bookacti_delete_booking_form_after', 'bookacti_add_wc_fields_to_delete_booking_form', 10 );


/**
 * AJAX Controller - Delete an order item (or only its metadata)
 * @since 1.5.0
 * @version 1.8.0
 * @param WC_Order_Item $item
 * @param string $action "delete_meta" to delete only Booking Activities data from the item. "delete_item" to delete the whole item.
 */
function bookacti_controller_delete_order_item( $item, $action ) {
	if( ! $action ) { 
		$array = array(
			'status'	=> 'failed',
			'error'		=> 'no_action',
			'message'	=> esc_html__( 'The booking is bound to an item in a WooCommerce Order, but no action has been set about it.', 'booking-activities' )
		);
		bookacti_send_json( $array, 'delete_order_item' );
	}

	if( $action === 'none' ) { return; }

	// Get item id and order id
	$item_id	= $item->get_id();
	$order_id	= $item->get_order_id();

	$order = wc_get_order( $order_id );

	// Remove all metadata related to Booking Activities from the order item
	if( $action === 'delete_meta' ) {
		$deleted = bookacti_delete_order_item_booking_meta( $item_id );

		if( $deleted === false ) {
			$array = array(
				'status'	=> 'failed',
				'error'		=> 'not_deleted',
				'message'	=> esc_html__( 'An error occurred while trying to delete the booking meta from the order item.', 'booking-activities' ) 
							   . ' ' . '<a href="' . get_edit_post_link( $order_id ) . '">' . esc_html__( 'Please proceed manually.', 'booking-activities' ) . '</a>'
			);
			bookacti_send_json( $array, 'delete_order_item_booking_meta' );
		}

		if( $order ) { 
			/* translators: %s is the item id. */
			$message = sprintf( esc_html__( 'The order item %s booking metadata have been deleted while deleting the corresponding booking.', 'booking-activities' ), $item_id );
			$order->add_order_note( $message, 0, 0 );
		}

	// Remove the whole order item
	} else if( $action === 'delete_item' ) {
		$deleted = wc_delete_order_item( $item_id );

		if( ! $deleted ) {
			$array = array(
				'status'	=> 'failed',
				'error'		=> 'not_deleted',
				'message'	=> esc_html__( 'An error occurred while trying to delete the order item.', 'booking-activities' ) 
							   . ' ' . '<a href="' . get_edit_post_link( $order_id ) . '">' . esc_html__( 'Please proceed manually.', 'booking-activities' ) . '</a>'
			);
			bookacti_send_json( $array, 'delete_order_item' );
		}

		if( $order ) { 
			/* translators: %s is the item id. */
			$message = sprintf( esc_html__( 'The order item %s has been deleted while deleting the corresponding booking.', 'booking-activities' ), $item_id );
			$order->add_order_note( $message, 0, 0 );
		}
	}
}


/**
 * AJAX Controller - Delete an order item (or only its metadata) bound to a specific booking group
 * @since 1.5.0
 * @param int $booking_group_id
 */
function bookacti_controller_delete_order_item_bound_to_booking_group( $booking_group_id ) {
	$action = ! empty( $_POST[ 'order-item-action' ] ) ? $_POST[ 'order-item-action' ] : 'none';

	$item = bookacti_get_order_item_by_booking_group_id( $booking_group_id );

	if( ! $item ) { return; }

	bookacti_controller_delete_order_item( $item, $action );
}
add_action( 'bookacti_before_delete_booking_group', 'bookacti_controller_delete_order_item_bound_to_booking_group', 10, 1 );


/**
 * AJAX Controller - Delete an order item (or only its metadata) bound to a specific booking
 * @since 1.5.0
 * @param int $booking_id
 */
function bookacti_controller_delete_order_item_bound_to_booking( $booking_id ) {
	$action = ! empty( $_POST[ 'order-item-action' ] ) ? $_POST[ 'order-item-action' ] : 'none';

	$item = bookacti_get_order_item_by_booking_id( $booking_id );

	if( $item ) { 
		bookacti_controller_delete_order_item( $item, $action );
		return;
	}
}
add_action( 'bookacti_before_delete_booking', 'bookacti_controller_delete_order_item_bound_to_booking', 10, 1 );


/**
 * Remove a grouped booking from order item metadata
 * @since 1.5.0
 * @version 1.8.0
 * @param int $booking_id
 */
function bookacti_remove_grouped_booking_from_order_item( $booking_id ) {
	// If the booking is part of a group...
	$booking = bookacti_get_booking_by_id( $booking_id );
	if( ! $booking || ! $booking->group_id ) { return; }

	// ...And if this group is bound to a WC order item
	$item = bookacti_get_order_item_by_booking_group_id( $booking->group_id );
	if( ! $item ) { return; }

	// Get item id
	$item_id	= $item->get_id();
	$order_id	= $item->get_order_id();

	// Get the booking list
	$grouped_bookings_raw = wc_get_order_item_meta( $item_id, 'bookacti_booked_events', true );
	if( ! bookacti_is_json( $grouped_bookings_raw ) ) { return; }

	// Format booking list as an array of objects
	$grouped_bookings = json_decode( $grouped_bookings_raw );
	if( ! $grouped_bookings ) { return; }

	// Remove the desired booking from the list
	$grouped_bookings_nb = count( $grouped_bookings );
	foreach( $grouped_bookings as $i => $grouped_booking ) {
		if( $grouped_booking->event_id === $booking->event_id 
		&&  $grouped_booking->event_start === $booking->event_start 
		&&  $grouped_booking->event_end === $booking->event_end ) {
			unset( $grouped_bookings[ $i ] );
			break;
		}
	}

	// If the booking has been deleted, update the order item list of bookings
	if( $grouped_bookings_nb !== count( $grouped_bookings ) ) {
		wc_update_order_item_meta( $item_id, 'bookacti_booked_events', json_encode( array_values( $grouped_bookings ) ) );

		$order = wc_get_order( $order_id );
		if( $order ) { 
			/* translators: %s is the item id. */
			$message = sprintf( esc_html__( 'The order item %s booking metadata have been updated after one of its booking was deleted.', 'booking-activities' ), $item_id );
			$order->add_order_note( $message, 0, 0 );
		}
	}
}
add_action( 'bookacti_before_delete_booking', 'bookacti_remove_grouped_booking_from_order_item', 20, 1 );