<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add WooCommerce backend translation to be use in JS
 * @since 1.5.0
 * @param array $translation_array
 * @return array
 */
function bookacti_wc_backend_translation_array( $translation_array ) {
	$translation_array[ 'nonce_dismiss_guest_checkout_notice' ]	= wp_create_nonce( 'bookacti_dismiss_guest_checkout_notice' );
	return $translation_array;
}
add_filter( 'bookacti_translation_array', 'bookacti_wc_backend_translation_array', 10, 1 );


/**
 * Add settings tab
 * @param array $tabs
 * @return array
 */
function bookacti_add_cart_settings_tab( $tabs ) {
	$tabs['cart'] = __( 'Cart', BOOKACTI_PLUGIN_NAME );
	
	return $tabs;
}
add_filter( 'bookacti_settings_tabs', 'bookacti_add_cart_settings_tab', 5 ); 


/**
 * Add cart tab content
 * @param string $active_tab
 */
function bookacti_add_cart_tab_content( $active_tab ) {
	if( $active_tab === 'cart' ) {
		settings_fields( 'bookacti_cart_settings' );
		do_settings_sections( 'bookacti_cart_settings' ); 
	}
}
add_action( 'bookacti_settings_tab_content', 'bookacti_add_cart_tab_content' );


/**
 * Add Cart settings section
 */
function bookacti_add_woocommerce_cart_settings_section() {
	
	/* Cart settings Section */
	add_settings_section( 
		'bookacti_settings_section_cart_expiration',
		__( 'Cart expiration setting', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_cart_callback',
		'bookacti_cart_settings'
	);
	
	add_settings_field(  
		'is_cart_expiration_active',                      
		__( 'Activate cart expiration', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_activate_cart_expiration_callback',   
		'bookacti_cart_settings',                     
		'bookacti_settings_section_cart_expiration' 
	);
	
	add_settings_field(  
		'cart_timeout', 
		/* translators: 'Timeout' stands for the amount of time a user has to proceed to checkout before his cart gets empty. */
		__( 'Timeout (minutes)', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cart_timeout_callback',   
		'bookacti_cart_settings',                     
		'bookacti_settings_section_cart_expiration' 
	);
	
	add_settings_field(  
		'is_cart_expiration_per_product',
		/* translators: It is an option meaning that each product in cart has its own expiration date, and they expire one after the other, not all the cart content at once */
		__( 'Per product expiration', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_per_product_expiration_callback',   
		'bookacti_cart_settings',                     
		'bookacti_settings_section_cart_expiration' 
	);
	
	add_settings_field(  
		'reset_cart_timeout_on_change', 
		__( 'Reset countdown when cart changes', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_reset_cart_timeout_on_change_callback',   
		'bookacti_cart_settings',                     
		'bookacti_settings_section_cart_expiration' 
	);
	
	register_setting('bookacti_cart_settings', 'bookacti_cart_settings' );
}
add_action( 'bookacti_add_settings', 'bookacti_add_woocommerce_cart_settings_section' );


// 
/**
 * Define default cart settings values
 * 
 * @since 1.3.0 (was bookacti_define_cart_settings_constants)
 * @param array $settings
 * @return array
 */
function bookacti_cart_default_settings( $settings ) {
	
	$settings[ 'is_cart_expiration_active' ]		= true;
	$settings[ 'is_cart_expiration_per_product' ]	= false;
	$settings[ 'cart_timeout' ]						= 30;
	$settings[ 'reset_cart_timeout_on_change' ]		= false;
	
	return $settings;
}
add_filter( 'bookacti_default_settings', 'bookacti_cart_default_settings' );


/**
 * Delete cart settings
 */
function bookacti_delete_cart_settings() {
	// Delete Cart Settings
	delete_option( 'bookacti_cart_settings' );
}
add_action( 'bookacti_delete_settings', 'bookacti_delete_cart_settings' );


/**
 * Add a mention to booking method tip
 * 
 * @param string $tip
 * @return string
 */
function bookacti_add_wc_mention_to_booking_method_tip( $tip ) {
	$tip .= '<br/>';
	$tip .= esc_html__( 'This parameter can be overriden by products settings in woocommerce.', BOOKACTI_PLUGIN_NAME );
	return $tip;
}
add_filter( 'bookacti_booking_methods_tip', 'bookacti_add_wc_mention_to_booking_method_tip', 20, 1 );


/**
 * Add notification global settings
 * 
 * @since 1.2.2
 * @param array $notification_settings
 * @param string $notification_id
 */
function bookacti_display_wc_notification_global_settings( $notification_settings, $notification_id ) {
	
	$active_with_wc_settings = array( 
		'admin_new_booking'				=> array( 
			'label'			=>  __( 'Send when an order is made', BOOKACTI_PLUGIN_NAME ), 
			'description'	=>  __( 'Wether to send this automatic notification when a new WooCommerce order is made, for each booking (group) of the order.', BOOKACTI_PLUGIN_NAME ) ), 
		'customer_pending_booking'		=> array( 
			'label'			=>  __( 'Send when an order is processing', BOOKACTI_PLUGIN_NAME ), 
			'description'	=>  __( 'Wether to send this automatic notification when a WooCommerce order is "Processing", for each booking (group) affected in the order. It will not be sent for "Pending" orders (when an order is pending payment), because the booking is still considered as temporary. It may be sent along the WooCommerce confirmation email.', BOOKACTI_PLUGIN_NAME ) ), 
		'customer_booked_booking'		=> array( 
			'label'			=>  __( 'Send when an order is completed', BOOKACTI_PLUGIN_NAME ), 
			'description'	=>  __( 'Wether to send this automatic notification when a WooCommerce order is "Completed", for each booking (group) affected in the order. It may be sent along the WooCommerce confirmation email.', BOOKACTI_PLUGIN_NAME ) ), 
		'customer_cancelled_booking'	=> array( 
			'label'			=>  __( 'Send when an order is cancelled', BOOKACTI_PLUGIN_NAME ), 
			'description'	=>  __( 'Wether to send this automatic notification when a WooCommerce order is "Cancelled", for each booking (group) affected in the order. It will not be sent for "Failed" orders (when a pending payment fails), because the booking is still considered as temporary.', BOOKACTI_PLUGIN_NAME ) ),
		'customer_refunded_booking'		=> array( 
			'label'			=>  __( 'Send when an order is refunded', BOOKACTI_PLUGIN_NAME ), 
			'description'	=>  __( 'Wether to send this automatic notification when a WooCommerce order is "Refunded", for each booking (group) affected in the order. It may be sent along the WooCommerce refund email.', BOOKACTI_PLUGIN_NAME ) )
	);
	
	if( in_array( $notification_id, array_keys( $active_with_wc_settings ) ) ) {
	?>
		<tr>
			<th scope='row' ><?php echo $active_with_wc_settings[ $notification_id ][ 'label' ]; ?></th>
			<td>
				<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'bookacti_notification[active_with_wc]',
					'id'	=> 'bookacti_notification_' . $notification_id . 'active_with_wc',
					'value'	=> $notification_settings[ 'active_with_wc' ] ? $notification_settings[ 'active_with_wc' ] : 0,
					'tip'	=> $active_with_wc_settings[ $notification_id ][ 'description' ]
				);
				bookacti_display_field( $args );
				?>
			</td>
		</tr>
	<?php
	}
}
add_action( 'bookacti_notification_settings_page_global', 'bookacti_display_wc_notification_global_settings', 10, 2 );


/**
 * Add customizable messages
 * 
 * @since 1.2.0
 * @version 1.3.0
 * @param array $messages
 * @return array
 */
function bookacti_wc_default_messages( $messages ) {
	
	$wc_messages = array( 
		'temporary_booking_success' => array(
			/* translators: {time} tag is a variable standing for an amount of days, hours and minutes. Ex: {time}' can be '1 day, 6 hours, 30 minutes'. */
			'value'			=> esc_html__( 'Your activity is temporarily booked for {time}. Please proceed to checkout.', BOOKACTI_PLUGIN_NAME ),
			'description'	=> esc_html__( 'When a temporary booking is added to cart. Use the {time} tag to display the remaining time before expiration.', BOOKACTI_PLUGIN_NAME )
		),
		'cart_countdown' => array(
			/* translators: {countdown} tag is to be replaced by a real-time countdown: E.g.: 'Your cart expires in 3 days 12:35:26' or 'Your cart expires in 01:30:05'*/
			'value'			=> esc_html__( 'Your cart expires in {countdown}', BOOKACTI_PLUGIN_NAME ),
			'description'	=> esc_html__( 'This message will be displayed above your cart. Use the {countdown} tag to display the real-time countdown.', BOOKACTI_PLUGIN_NAME )
		)
	);
	
	return array_merge( $messages, $wc_messages );
}
add_filter( 'bookacti_default_messages', 'bookacti_wc_default_messages', 10, 1 );


/**
 * Display an error admin notice if guest checkout is allowed
 * @since 1.5.0
 */
function bookacti_wc_guest_checkout_not_allowed_notice() {
	$is_dismissed = get_option( 'bookacti-guest-checkout-notice-dismissed' );
	if( ! $is_dismissed ) {
		$guest_checkout_allowed = get_option( 'woocommerce_enable_guest_checkout' );
		if( ! empty( $guest_checkout_allowed ) && $guest_checkout_allowed === 'yes' ) {
		?>
			<div class='notice notice-error bookacti-guest-checkout-notice is-dismissible' >
				<p>
				<?php 
					$wc_settings_url = esc_url( get_admin_url() . 'admin.php?page=wc-settings&tab=checkout' );
					echo __( 'Booking Activities doesn\'t support WooCommerce\'s <strong>guest checkout option</strong>. Please deactivate this option.', BOOKACTI_PLUGIN_NAME )
						. ' ' . '<a href="' . $wc_settings_url . '" >' . __( 'Go to WooCommerce settings.', BOOKACTI_PLUGIN_NAME ) . '</a>'; 
				?>
				</p>
			</div>
		<?php
		}
	}
}
add_action( 'all_admin_notices', 'bookacti_wc_guest_checkout_not_allowed_notice' );


/**
 * Remove WC Guest Checkout option notice
 * @since 1.5.0
 */
function bookacti_dismiss_guest_checkout_notice() {
	
	// Check nonce, no need to check capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_dismiss_guest_checkout_notice', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_manage_booking_activities' );

	if( $is_nonce_valid && $is_allowed ) {
	
		$updated = update_option( 'bookacti-guest-checkout-notice-dismissed', 1 );
		if( $updated ) {
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiDismissGuestCheckoutNotice', 'bookacti_dismiss_guest_checkout_notice' );


/**
 * Restore WC Guest Checkout option notice if the guest checkout box is checked again
 * @since 1.5.0
 * @global string $current_tab
 */
function bookacti_restore_guest_checkout_notice() {
	global $current_tab;
	if( $current_tab === 'checkout' ) {
		$guest_checkout_allowed = get_option( 'woocommerce_enable_guest_checkout' );
		if( ! empty( $guest_checkout_allowed ) && $guest_checkout_allowed === 'yes' ) {
			delete_option( 'bookacti-guest-checkout-notice-dismissed' );
		}
	}
}
add_action( 'woocommerce_settings_saved', 'bookacti_restore_guest_checkout_notice' );