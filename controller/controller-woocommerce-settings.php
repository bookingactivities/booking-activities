<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// Add settings tab
add_filter( 'bookacti_settings_tabs', 'bookacti_add_cart_settings_tab', 5 ); 
function bookacti_add_cart_settings_tab( $tabs ) {
	$tabs['cart'] = __( 'Cart', BOOKACTI_PLUGIN_NAME );
	
	return $tabs;
}


// Add cart tab content
add_action( 'bookacti_settings_tab_content', 'bookacti_add_cart_tab_content' );
function bookacti_add_cart_tab_content( $active_tab ) {
	if( $active_tab === 'cart' ) {
		settings_fields( 'bookacti_cart_settings' );
		do_settings_sections( 'bookacti_cart_settings' ); 
	}
}


// Add Cart settings section
add_action( 'bookacti_add_settings', 'bookacti_add_woocommerce_cart_settings_section' );
function bookacti_add_woocommerce_cart_settings_section() {
	/* Bookings */
	add_settings_field(  
		'show_temporary_bookings', 
		__( 'Show temporary bookings', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_show_temporary_bookings_callback', 
		'bookacti_bookings_settings', 
		'bookacti_settings_section_bookings' 
	);
	
	
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


// Define default cart settings value
add_action( 'bookacti_define_settings_constants', 'bookacti_define_cart_settings_constants' );
function bookacti_define_cart_settings_constants() {
	// Bookings
	if( ! defined( 'BOOKACTI_SHOW_TEMPORARY_BOOKINGS' ) )		{ define( 'BOOKACTI_SHOW_TEMPORARY_BOOKINGS', '1' ); }
	
	// Cart
	if( ! defined( 'BOOKACTI_IS_CART_EXPIRATION_ACTIVE' ) )		{ define( 'BOOKACTI_IS_CART_EXPIRATION_ACTIVE', '1' ); }
	if( ! defined( 'BOOKACTI_IS_CART_EXPIRATION_PER_PRODUCT' ) ){ define( 'BOOKACTI_IS_CART_EXPIRATION_PER_PRODUCT', '0' ); }
	if( ! defined( 'BOOKACTI_CART_TIMEOUT' ) )					{ define( 'BOOKACTI_CART_TIMEOUT', '30' ); }
	if( ! defined( 'BOOKACTI_RESET_CART_TIMEOUT_ON_CHANGE' ) )	{ define( 'BOOKACTI_RESET_CART_TIMEOUT_ON_CHANGE', '0' ); }
}


// Init cart settings value to default
add_action( 'bookacti_init_settings_value', 'bookacti_init_cart_settings_value' );
function bookacti_init_cart_settings_value() {
	// Bookings
	$default_bookings_settings = get_option( 'bookacti_bookings_settings' );
	if( ! isset( $default_bookings_settings['show_temporary_bookings'] ) )	{ $default_bookings_settings['show_temporary_bookings']		= BOOKACTI_SHOW_TEMPORARY_BOOKINGS; }
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	// Cart
	$default_cart_settings = get_option( 'bookacti_cart_settings' );
	if( ! isset( $default_cart_settings['is_cart_expiration_active'] ) )	{ $default_cart_settings['is_cart_expiration_active']		= BOOKACTI_IS_CART_EXPIRATION_ACTIVE; }
	if( ! isset( $default_cart_settings['is_cart_expiration_per_product'] )){ $default_cart_settings['is_cart_expiration_per_product']	= BOOKACTI_IS_CART_EXPIRATION_PER_PRODUCT; }
	if( ! isset( $default_cart_settings['cart_timeout'] ) )					{ $default_cart_settings['cart_timeout']					= BOOKACTI_CART_TIMEOUT; }
	if( ! isset( $default_cart_settings['reset_cart_timeout_on_change'] ) )	{ $default_cart_settings['reset_cart_timeout_on_change']	= BOOKACTI_RESET_CART_TIMEOUT_ON_CHANGE; }
	update_option( 'bookacti_cart_settings', $default_cart_settings );
}


// Reset cart settings values to default
add_action( 'bookacti_reset_settings', 'bookacti_reset_cart_settings' );
function bookacti_reset_cart_settings() {
	// Bookings
	$default_bookings_settings = array();
	$default_bookings_settings['show_temporary_bookings']		= BOOKACTI_SHOW_TEMPORARY_BOOKINGS;
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	// Cart
	$default_cart_settings = array();
	$default_cart_settings['is_cart_expiration_active']			= BOOKACTI_IS_CART_EXPIRATION_ACTIVE;
	$default_cart_settings['is_cart_expiration_per_product']	= BOOKACTI_IS_CART_EXPIRATION_PER_PRODUCT;
	$default_cart_settings['cart_timeout']						= BOOKACTI_CART_TIMEOUT;
	$default_cart_settings['reset_cart_timeout_on_change']		= BOOKACTI_RESET_CART_TIMEOUT_ON_CHANGE;
	update_option( 'bookacti_cart_settings', $default_cart_settings );
}


// Delete cart settings
add_action( 'bookacti_delete_settings', 'bookacti_delete_cart_settings' );
function bookacti_delete_cart_settings() {
	// Delete Cart Settings
	delete_option( 'bookacti_cart_settings' );
}


/**
 * Add bookings list settings
 * 
 * @version 1.2.0
 * @param array $params
 */
function bookacti_add_booking_list_in_cart_filter( $params ) {
	$user_id = $params[ 'user_id' ];
	$show_temporary_bookings_array	= bookacti_get_setting_value( 'bookacti_bookings_settings', 'show_temporary_bookings' );
	$show_temporary_bookings		= 0;
	if( is_array( $show_temporary_bookings_array ) && isset( $show_temporary_bookings_array[ $user_id ] ) && ! is_null( $show_temporary_bookings_array[ $user_id ] ) ) {
		$show_temporary_bookings	= $show_temporary_bookings_array[ $user_id ];
	}
		
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookings-show-temporary-bookings',
		'id'	=> 'bookacti-bookings-show-temporary-bookings',
		'value'	=> $show_temporary_bookings,
		'tip'	=> __( 'Show temporary bookings in the booking list (in cart bookings).', BOOKACTI_PLUGIN_NAME )
	);
	
	?>
	<div>
		<label for='bookacti-bookings-show-temporary-bookings' ><?php esc_html_e( 'Show temporary bookings', BOOKACTI_PLUGIN_NAME ); ?></label>
		<?php bookacti_display_field( $args ); ?>
	</div>
<?php
}
add_action( 'bookacti_booking_list_tab_filter_after', 'bookacti_add_booking_list_in_cart_filter', 10, 1 );


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
add_filter( 'bookacti_booking_methods_tip', 'bookacti_add_wc_mention_to_booking_method_tip', 1, 10 );


/**
 * Add a mention to when events load setting tip
 * 
 * @since 1.1.0
 */
function bookacti_add_wc_mention_to_when_events_load_tip( $tip ) {
	$tip .= '<br/>';
	$tip .= esc_html__( 'WC Variable products calendars will always load after page load.', BOOKACTI_PLUGIN_NAME );
	return $tip;
}
add_filter( 'bookacti_when_events_load_tip', 'bookacti_add_wc_mention_to_when_events_load_tip', 1, 10 );


/**
 * Add a mention to notifications
 * 
 * @since 1.2.0
 */
function bookacti_add_wc_mention_to_notifications( $emails ) {
	
	if( ! isset( $emails[ 'admin_refunded_booking' ] ) ) {
		$emails[ 'admin_refunded_booking' ] = array(
				'active'		=> 1,
				'title'			=> __( 'Customer has been refunded', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking refunded', BOOKACTI_PLUGIN_NAME ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is refunded */
				'message'		=> __( '<p>A customer has been reimbursed for this booking:</p>
										<p>{booking_list}</p>
										<p>Contact him: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the administrator when a customer is successfully reimbursed for a booking.', BOOKACTI_PLUGIN_NAME ) 
			);
	}
	
	if( isset( $emails[ 'admin_new_booking' ] ) ) {
		$emails[ 'admin_new_booking' ][ 'description' ]				.= '<br/>' . __( 'To avoid double notification, this will not be sent if WooCommerce triggered this change.', BOOKACTI_PLUGIN_NAME );
	}
	
	if( isset( $emails[ 'customer_pending_booking' ] ) ) {
		$emails[ 'customer_pending_booking' ][ 'description' ]		.= '<br/>' . __( 'To avoid double notification, this will not be sent if WooCommerce triggered this change.', BOOKACTI_PLUGIN_NAME );
	}
	
	if( isset( $emails[ 'customer_booked_booking' ] ) ) {
		$emails[ 'customer_booked_booking' ][ 'description' ]		.= '<br/>' . __( 'To avoid double notification, this will not be sent if WooCommerce triggered this change.', BOOKACTI_PLUGIN_NAME );
	}
	
	if( isset( $emails[ 'customer_cancelled_booking' ] ) ) {
		$emails[ 'customer_cancelled_booking' ][ 'description' ]	.= '<br/>' . __( 'To avoid double notification, this will not be sent if WooCommerce triggered this change.', BOOKACTI_PLUGIN_NAME );
	}
	
	if( isset( $emails[ 'customer_refunded_booking' ] ) ) {
		$emails[ 'customer_refunded_booking' ][ 'description' ]		.= '<br/>' . __( 'To avoid double notification, this will not be sent if WooCommerce triggered this change.', BOOKACTI_PLUGIN_NAME );
	}
	
	return $emails;
}
add_filter( 'bookacti_emails_default_settings', 'bookacti_add_wc_mention_to_notifications', 1, 10 );


/**
 * Add customizable messages
 * 
 * @since 1.2.0
 * @param array $messages
 * @return array
 */
function bookacti_wc_default_messages( $messages ) {
	
	$messages[ 'temporary_booking_success' ] = array(
		/* translators: '{time}' is a variable standing for an amount of days, hours and minutes. Ex: {time}' can be '1 day, 6 hours, 30 minutes'. */
		'value'			=> __( 'Your activity is temporarily booked for {time}. Please proceed to checkout.', BOOKACTI_PLUGIN_NAME ),
		'description'	=> __( 'When a temporary booking is added to cart. Use {time} tag to display the remaining time before expiration.', BOOKACTI_PLUGIN_NAME )
	);
	
	return $messages;
}
add_filter( 'bookacti_default_messages', 'bookacti_wc_default_messages', 10, 1 );