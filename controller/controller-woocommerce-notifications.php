<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Send one notification per booking to admin and customer when an order contining bookings is made or when its status changes
 * @since 1.2.2
 * @version 1.8.6
 * @param WC_Order $order
 * @param string $new_status
 * @param array $args
 */
function bookacti_send_notification_when_order_status_changes( $order, $new_status, $args = array() ) {
	if( is_numeric( $order ) ) { $order = wc_get_order( $order ); }
	if( ! $order ) { return; }
	
	$action = isset( $_REQUEST[ 'action' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'action' ] ) : '';
	
	// Check if the administrator must be notified
	// If the booking status is pending or booked, notify administrator, unless if the administrator made this change
	$notify_admin = 0;
	if(	 in_array( $new_status, array( 'booked', 'pending' ) )
	&& ! in_array( $action, array( 'woocommerce_mark_order_status', 'editpost' ) ) ) {
		$admin_notification	= bookacti_get_notification_settings( 'admin_new_booking' );
		$notify_admin		= $admin_notification[ 'active_with_wc' ] ? 1 : 0;
	}
	
	// Check if the customer must be notified
	$customer_notification	= bookacti_get_notification_settings( 'customer_' . $new_status . '_booking' );
	$notify_customer		= $customer_notification[ 'active_with_wc' ] ? 1 : 0;
	
	// If nobody needs to be notified, do nothing
	if( ! $notify_admin && ! $notify_customer ) { return; }
	
	// Do not send notifications at all for transitionnal order status, 
	// because the booking is still considered as temporary
	$order_status = $order->get_status();
	if( ( $order_status === 'pending' && $new_status === 'pending' )
	||  ( $order_status === 'failed' && $new_status === 'cancelled' ) ) { return; }
	
	$order_items = $order->get_items();
	if( ! $order_items ) { return; }
	
	$in__booking_id			= ! empty( $args[ 'booking_ids' ] ) ? $args[ 'booking_ids' ] : array();
	$in__booking_group_id	= ! empty( $args[ 'booking_group_ids' ] ) ? $args[ 'booking_group_ids' ] : array();
	
	foreach( $order_items as $order_item_id => $item ) {
		// Check if the order item is a booking, or skip it
		if( ! $item || ( ! isset( $item[ 'bookacti_booking_id' ] ) && ! isset( $item[ 'bookacti_booking_group_id' ] ) ) ) { continue; }
		
		// Make sure the booking is part of those updated
		if( isset( $item[ 'bookacti_booking_id' ] ) && ( $in__booking_id || $in__booking_group_id ) && ! in_array( $item[ 'bookacti_booking_id' ], $in__booking_id ) ) { continue; }
		if( isset( $item[ 'bookacti_booking_group_id' ] ) && ( $in__booking_id || $in__booking_group_id ) && ! in_array( $item[ 'bookacti_booking_group_id' ], $in__booking_group_id ) ) { continue; }
		
		// If the state hasn't changed, do not send the notifications, unless it is a new order
		$old_status = isset( $args[ 'old_status' ] ) && $args[ 'old_status' ] ? $args[ 'old_status' ] : wc_get_order_item_meta( $order_item_id, 'bookacti_state', true );
		if( $old_status === $new_status && empty( $args[ 'force_status_notification' ] ) ) { continue; }
		
		// Get booking ID and booking type ('single' or 'group')
		$booking_id		= isset( $item[ 'bookacti_booking_id' ] ) ? $item[ 'bookacti_booking_id' ] : ( isset( $item[ 'bookacti_booking_group_id' ] ) ? $item[ 'bookacti_booking_group_id' ] : 0 );
		$booking_type	= isset( $item[ 'bookacti_booking_id' ] ) ? 'single' : ( isset( $item[ 'bookacti_booking_group_id' ] ) ? 'group' : '' );
		if( ! $booking_id || ! $booking_type ) { continue; }
		
		// Send a booking confirmation to the customer
		if( $notify_customer ) {
			bookacti_send_notification( 'customer_' . $new_status . '_booking', $booking_id, $booking_type );
		}
		
		// Notify administrators that a new booking has been made
		if( $notify_admin ) {
			bookacti_send_notification( 'admin_new_booking', $booking_id, $booking_type );
		}
		
		do_action( 'bookacti_send_order_bookings_status_notifications', $booking_id, $booking_type, $item, $order, $new_status, $args );
	}
}
add_action( 'bookacti_order_bookings_state_changed', 'bookacti_send_notification_when_order_status_changes', 10, 3 );


/**
 * Add a mention to notifications
 * @since 1.8.6 (was bookacti_add_admin_refunded_booking_notification before)
 * @param array $notifications
 * @return array
 */
function bookacti_add_price_info_to_admin_refund_notifications( $notifications ) {
	$refund_message = preg_replace( '/\t+/', '', 
			PHP_EOL
		.	__( '<h4>Refund</h4>
			Price: {price}
			Coupon: {refund_coupon_code}
			', 'booking-activities' ) );
	if( isset( $notifications[ 'admin_refund_requested_booking' ] ) ) { 
		$notifications[ 'admin_refund_requested_booking' ][ 'email' ][ 'message' ] .= $refund_message;
	}
	if( isset( $notifications[ 'admin_refunded_booking' ] ) ) { 
		$notifications[ 'admin_refunded_booking' ][ 'email' ][ 'message' ] .= $refund_message;
	}
	return $notifications;
}
add_filter( 'bookacti_notifications_default_settings', 'bookacti_add_price_info_to_admin_refund_notifications', 10, 1 );


/**
 * Add WC-specific default notification settings
 * @since 1.2.2
 * @version 1.8.6
 * @param array $notifications
 * @return array
 */
function bookacti_add_wc_default_notification_settings( $notifications ) {
	// Add the active_with_wc option only for certain triggers (booking status changes, and new booking made)
	$active_with_wc_triggers = array( 'new', 'pending', 'booked', 'cancelled', 'refunded' );
	
	// Get WC stock email recipient
	$wc_stock_email_recipient = get_option( 'woocommerce_stock_email_recipient' );
	
	foreach( $notifications as $notification_id => $notification ) {
		// Check if the active_with_wc option should be added
		$add_active_with_wc_option = false;
		foreach( $active_with_wc_triggers as $active_with_wc_trigger ) {
			if( strpos( $notification_id, '_' . $active_with_wc_trigger ) !== false ) { $add_active_with_wc_option = true; } 
		}
		if( ! $add_active_with_wc_option ) { continue; }
		
		// Add the active_with_wc option
		if( ! isset( $notifications[ $notification_id ][ 'active_with_wc' ] ) ) {
			$notifications[ $notification_id ][ 'active_with_wc' ] = 0;
		}
		
		// Add the recipients to the refund request emails
		if( strpos( $notification_id, 'admin_refund_requested' ) !== false 
		&&  ! in_array( $wc_stock_email_recipient, $notifications[ $notification_id ][ 'email' ][ 'to' ], true ) ) {
			$notifications[ $notification_id ][ 'email' ][ 'to' ][] = $wc_stock_email_recipient;
		}
	}
	
	return $notifications;
}
add_filter( 'bookacti_notifications_default_settings', 'bookacti_add_wc_default_notification_settings', 100, 1 );


/**
 * Sanitize WC-specific notifications settings
 * 
 * @since 1.2.2
 * @param array $notification
 * @param string $notification_id
 * @return array
 */
function bookacti_sanitize_wc_notification_settings( $notification, $notification_id ) {
	if( isset( $notification[ 'active_with_wc' ] ) ) {
		$notification[ 'active_with_wc' ] = intval( $notification[ 'active_with_wc' ] ) ? 1 : 0;
	}
	return $notification;
}
add_filter( 'bookacti_notification_sanitized_settings', 'bookacti_sanitize_wc_notification_settings', 20, 2 );


/**
 * Make sure that WC order data are up to date when a WC notification is sent
 * 
 * @since 1.2.2
 * @version 1.8.0
 * @param array $args
 * @return array
 */
function bookacti_wc_email_order_item_args( $args ) {
	// Check if the order contains bookings
	$has_bookings = false;
	foreach( $args[ 'items' ] as $item ) {
		if( isset( $item[ 'bookacti_booking_id' ] ) || isset( $item[ 'bookacti_booking_group_id' ] ) ) {
			$has_bookings = true;
			break;
		}
	}
	
	// If the order has no bookings, change nothing
	if( ! $has_bookings ) { return $args; }
		
	// If the order has bookings, refresh the order instance to make sure data are up to date
	$order_id = $args[ 'order' ]->get_id();
	$fresh_order_instance = wc_get_order( $order_id );
	
	$args[ 'order' ] = $fresh_order_instance;
	$args[ 'items' ] = $fresh_order_instance->get_items();
	
	return $args;
}
add_filter( 'woocommerce_email_order_items_args', 'bookacti_wc_email_order_item_args', 10, 1 );


/**
 * Add WC notifications tags descriptions
 * @since 1.6.0
 * @version 1.8.6
 * @param array $tags
 * @param int $notification_id
 * @return array
 */
function bookacti_wc_notifications_tags( $tags, $notification_id ) {
	if( strpos( $notification_id, 'refund' ) !== false ) {
		$tags[ '{refund_coupon_code}' ] = esc_html__( 'The WooCommerce coupon code generated when the booking was refunded.', 'booking-activities' );
	}
	$tags[ '{price}' ] = esc_html__( 'Booking price, with currency.', 'booking-activities' );
	return $tags;
}
add_filter( 'bookacti_notifications_tags', 'bookacti_wc_notifications_tags', 15, 2 );


/**
 * Set WC notifications tags values
 * @since 1.6.0
 * @version 1.8.6
 * @param array $tags
 * @param object $booking
 * @param string $booking_type
 * @param array $notification
 * @param string $locale
 * @return array
 */
function bookacti_wc_notifications_tags_values( $tags, $booking, $booking_type, $notification, $locale ) {
	if( ! $booking ) { return $tags; }
	
	$item = $booking_type === 'group' ? bookacti_get_order_item_by_booking_group_id( $booking ) : bookacti_get_order_item_by_booking_id( $booking );
	
	// Use WC user data if the booking was made with WC, or if we only have these data
	$user = is_numeric( $booking->user_id ) ? get_user_by( 'id', $booking->user_id ) : null;
	if( $user ) {
		if( ! empty( $user->billing_first_name ) && ( $item || empty( $tags[ '{user_firstname}' ] ) ) )	{ $tags[ '{user_firstname}' ]	= $user->billing_first_name; }
		if( ! empty( $user->billing_last_name ) && ( $item || empty( $tags[ '{user_lastname}' ] ) ) )	{ $tags[ '{user_lastname}' ]	= $user->billing_last_name; }
		if( ! empty( $user->billing_email ) && ( $item || empty( $tags[ '{user_email}' ] ) ) )			{ $tags[ '{user_email}' ]		= $user->billing_email; }
		if( ! empty( $user->billing_phone ) && ( $item || empty( $tags[ '{user_phone}' ] ) ) )			{ $tags[ '{user_phone}' ]		= $user->billing_phone; }
	}
	
	if( ! $item ) { return $tags; }
	
	$item_id = $item->get_id();
	$item_price = (float) $item->get_total() + (float) $item->get_total_tax();
	$currency = get_post_meta( $booking->order_id, '_order_currency', true );
	$tags[ '{price}' ]	= $currency ? wc_price( $item_price, array( 'currency' => $currency ) ) : $item_price;
	
	if( strpos( $notification[ 'id' ], 'refund' ) !== false ) {
		$tags[ '{refund_coupon_code}' ]	= wc_get_order_item_meta( $item_id, 'bookacti_refund_coupon', true );
	}
	
	return $tags;
}
add_filter( 'bookacti_notifications_tags_values', 'bookacti_wc_notifications_tags_values', 15, 5 );


/**
 * Whether to send the BA refund notification when the order (item) is manually refunded
 * @since 1.8.3
 * @param string $recipients
 * @param int $booking_id
 * @param string $status
 * @param array $args
 * @return string
 */
function bookacti_wc_refund_notification_recipients( $recipients, $booking_id, $status, $args ) {
	if( ! empty( $args[ 'refund_action' ] ) && $args[ 'refund_action' ] === 'manual' ) {
		$recipients = 'none';
		$customer_notification = bookacti_get_notification_settings( 'customer_' . $status . '_booking' );
		if( ! empty( $customer_notification[ 'active_with_wc' ] ) ) {
			$recipients = 'customer';
		}
	}
	return $recipients;
}
add_filter( 'bookacti_booking_state_change_notification_recipient', 'bookacti_wc_refund_notification_recipients', 10, 4 );


/**
 * Add WC message to the refund requested notification sent to administrators
 * @version 1.8.6
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @param object $booking
 * @param string $booking_type
 * @param array $args
 * @return array
 */
function bookacti_woocommerce_add_refund_request_email_message( $notification, $tags, $locale, $booking, $booking_type, $args ) {
	if( strpos( $notification[ 'id' ], 'admin_refund_requested' ) === false || empty( $booking->order_id ) ) { return $notification; }
	
	$go_to_order =	'<div style="background-color: #f5faff; padding: 10px; border: 1px solid #abc; margin-bottom: 30px;" >' 
						. esc_html__( 'Click here to go to the order page and process the refund:', 'booking-activities' ) 
						. ' <a href="' . admin_url( 'post.php?post=' . absint( $booking->order_id ) . '&action=edit' ) . '" target="_blank" >' 
							. esc_html__( 'Go to refund page', 'booking-activities' ) 
						. '</a>'
					. '</div>';

	$notification[ 'email' ][ 'message' ] = $go_to_order . $notification[ 'email' ][ 'message' ];
	return $notification;
}
add_filter( 'bookacti_notification_data', 'bookacti_woocommerce_add_refund_request_email_message', 10, 6 );