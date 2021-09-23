<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add WooCommerce settings tab
 * @since 1.8.0 (was bookacti_add_cart_settings_tab)
 * @version 1.12.3
 * @param array $tabs
 * @return array
 */
function bookacti_add_wc_settings_tab( $tabs ) {
	$i = array_search( 'licenses', array_keys( $tabs ), true );
	if( ! $i ) { $i = count( $tabs ); }
	$new_tab = array( 'woocommerce' => esc_html__( 'WooCommerce', 'booking-activities' ) );
	$tabs = array_merge( array_slice( $tabs, 0, $i, true ), $new_tab, array_slice( $tabs, $i, null, true ) );
	return $tabs;
}
add_filter( 'bookacti_settings_tabs', 'bookacti_add_wc_settings_tab', 10, 1 ); 


/**
 * Add cart tab content
 * @version 1.7.16
 * @param string $active_tab
 */
function bookacti_add_cart_tab_content( $active_tab ) {
	if( $active_tab === 'woocommerce' ) {
		settings_fields( 'bookacti_woocommerce_settings' );
		do_settings_sections( 'bookacti_woocommerce_settings' );
	}
}
add_action( 'bookacti_settings_tab_content', 'bookacti_add_cart_tab_content' );


/**
 * Add WC settings section
 * @since 1.7.16
 * @version 1.9.0
 */
function bookacti_add_woocommerce_settings_section() {
	/* WC products settings Section */
	add_settings_section( 
		'bookacti_settings_section_wc_products',
		esc_html__( 'Products settings', 'booking-activities' ),
		'bookacti_settings_section_wc_products_callback',
		'bookacti_woocommerce_settings'
	);
	
	add_settings_field( 
		'wc_product_pages_booking_form_location',
		esc_html__( 'Booking form location on product pages', 'booking-activities' ),
		'bookacti_settings_wc_product_pages_booking_form_location_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_wc_products'
	);
		
	
	/* WC My Account settings Section */
	add_settings_section( 
		'bookacti_settings_section_wc_account',
		esc_html__( 'Account settings', 'booking-activities' ),
		'bookacti_settings_section_wc_account_callback',
		'bookacti_woocommerce_settings'
	);
	
	add_settings_field( 
		'wc_my_account_bookings_page_id',
		esc_html__( 'Bookings page in My Account', 'booking-activities' ),
		'bookacti_settings_wc_my_account_bookings_page_id_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_wc_account'
	);
	
	
	/* Cart settings Section */
	add_settings_section( 
		'bookacti_settings_section_cart',
		esc_html__( 'Cart settings', 'booking-activities' ),
		'bookacti_settings_section_cart_callback',
		'bookacti_woocommerce_settings'
	);
	
	add_settings_field( 
		'is_cart_expiration_active',
		esc_html__( 'Activate cart expiration', 'booking-activities' ),
		'bookacti_settings_field_activate_cart_expiration_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_cart'
	);
	
	add_settings_field( 
		'cart_timeout', 
		/* translators: 'Timeout' stands for the amount of time a user has to proceed to checkout before his cart gets empty. */
		esc_html__( 'Timeout (minutes)', 'booking-activities' ),
		'bookacti_settings_field_cart_timeout_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_cart'
	);
	
	add_settings_field( 
		'is_cart_expiration_per_product',
		/* translators: It is an option meaning that each product in cart has its own expiration date, and they expire one after the other, not all the cart content at once */
		esc_html__( 'Per product expiration', 'booking-activities' ),
		'bookacti_settings_field_per_product_expiration_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_cart'
	);
	
	add_settings_field( 
		'reset_cart_timeout_on_change',
		esc_html__( 'Reset countdown when cart changes', 'booking-activities' ),
		'bookacti_settings_field_reset_cart_timeout_on_change_callback',
		'bookacti_woocommerce_settings',
		'bookacti_settings_section_cart'
	);
	
	register_setting( 'bookacti_woocommerce_settings', 'bookacti_products_settings' );
	register_setting( 'bookacti_woocommerce_settings', 'bookacti_account_settings' );
	register_setting( 'bookacti_woocommerce_settings', 'bookacti_cart_settings' );
}
add_action( 'bookacti_add_settings', 'bookacti_add_woocommerce_settings_section' );


/**
 * Define default cart settings values
 * @since 1.7.16 (was bookacti_cart_default_settings)
 * @version 1.12.0
 * @param array $settings
 * @return array
 */
function bookacti_wc_default_settings( $settings ) {
	$settings[ 'is_cart_expiration_active' ]				= true;
	$settings[ 'is_cart_expiration_per_product' ]			= false;
	$settings[ 'cart_timeout' ]								= 30;
	$settings[ 'reset_cart_timeout_on_change' ]				= false;
	$settings[ 'wc_product_pages_booking_form_location' ]	= 'form_below';
	$settings[ 'wc_my_account_bookings_page_id' ]			= 0;
	return $settings;
}
add_filter( 'bookacti_default_settings', 'bookacti_wc_default_settings' );


/**
 * Delete WC settings
 * @since 1.7.16 (was bookacti_delete_cart_settings)
 */
function bookacti_delete_woocommerce_settings() {
	delete_option( 'bookacti_products_settings' );
	delete_option( 'bookacti_account_settings' );
	delete_option( 'bookacti_cart_settings' );
}
add_action( 'bookacti_delete_settings', 'bookacti_delete_woocommerce_settings' );


/**
 * Add notification global settings
 * @since 1.2.2
 * @version 1.8.6
 * @param array $notification_settings
 * @param string $notification_id
 */
function bookacti_display_wc_notification_global_settings( $notification_settings, $notification_id ) {
	$active_with_wc_settings = array( 
		'admin_new'			=> array( 
			'label'			=>  esc_html__( 'Send when an order is made', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a new WooCommerce order is made, for each booking (group) of the order.', 'booking-activities' ) ), 
		'admin_cancelled'	=> array( 
			'label'			=>  esc_html__( 'Send when an order is cancelled', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a WooCommerce order is "Cancelled", for each booking (group) affected in the order. It will not be sent for "Failed" orders (when a pending payment fails), because the booking is still considered as temporary.', 'booking-activities' ) ),
		'customer_new'		=> array( 
			'label'			=>  esc_html__( 'Send when an order is made', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a new WooCommerce order is made, for each booking (group) of the order.', 'booking-activities' ) ), 
		'customer_pending'	=> array( 
			'label'			=>  esc_html__( 'Send when an order is processing', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a WooCommerce order is "Processing", for each booking (group) affected in the order. It will not be sent for "Pending" orders (when an order is pending payment), because the booking is still considered as temporary. It may be sent along the WooCommerce confirmation email.', 'booking-activities' ) ), 
		'customer_booked'	=> array( 
			'label'			=>  esc_html__( 'Send when an order is completed', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a WooCommerce order is "Completed", for each booking (group) affected in the order. It may be sent along the WooCommerce confirmation email.', 'booking-activities' ) ), 
		'customer_cancelled'=> array( 
			'label'			=>  esc_html__( 'Send when an order is cancelled', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a WooCommerce order is "Cancelled", for each booking (group) affected in the order. It will not be sent for "Failed" orders (when a pending payment fails), because the booking is still considered as temporary.', 'booking-activities' ) ),
		'customer_refunded'	=> array( 
			'label'			=>  esc_html__( 'Send when an order is refunded', 'booking-activities' ), 
			'description'	=>  esc_html__( 'Wether to send this automatic notification when a WooCommerce order is "Refunded", for each booking (group) affected in the order. It may be sent along the WooCommerce refund email.', 'booking-activities' ) )
	);
	
	foreach( $active_with_wc_settings as $notification_id_starts_with => $active_with_wc_setting ) {
		if( strpos( $notification_id, $notification_id_starts_with ) === false ) { continue; }
	?>
		<tr>
			<th scope='row' ><?php echo $active_with_wc_setting[ 'label' ]; ?></th>
			<td>
				<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'bookacti_notification[active_with_wc]',
					'id'	=> 'bookacti_notification_' . $notification_id . 'active_with_wc',
					'value'	=> $notification_settings[ 'active_with_wc' ] ? $notification_settings[ 'active_with_wc' ] : 0,
					'tip'	=> $active_with_wc_setting[ 'description' ]
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
 * @since 1.2.0
 * @version 1.12.3
 * @param array $messages
 * @return array
 */
function bookacti_wc_default_messages( $messages ) {
	$wc_messages = array( 
		'booking_form_submit_button' => array(
			'value'			=> esc_html__( 'Book', 'booking-activities' ),
			'description'	=> esc_html__( 'Add to cart button label on "Activity" products pages only.', 'booking-activities' )
		),
		'temporary_booking_success' => array(
			/* translators: {time} tag is a variable standing for an amount of days, hours and minutes. E.g.: {time}' can be '1 day, 6 hours, 30 minutes'. */
			'value'			=> esc_html__( 'Your activity is temporarily booked for {time}. Please proceed to checkout.', 'booking-activities' ),
			'description'	=> esc_html__( 'When a temporary booking is added to cart. Use the {time} tag to display the remaining time before expiration.', 'booking-activities' )
		),
		'cart_countdown' => array(
			/* translators: {countdown} tag is to be replaced by a real-time countdown: E.g.: 'Your cart expires in 3 days 12:35:26' or 'Your cart expires in 01:30:05'*/
			'value'			=> esc_html__( 'Your cart expires in {countdown}', 'booking-activities' ),
			'description'	=> esc_html__( 'This message will be displayed above your cart. Use the {countdown} tag to display the real-time countdown.', 'booking-activities' )
		)
	);
	return array_merge( $messages, $wc_messages );
}
add_filter( 'bookacti_default_messages', 'bookacti_wc_default_messages', 10, 1 );