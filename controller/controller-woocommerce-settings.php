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
	define( 'SHOW_TEMPORARY_BOOKINGS', '1' );
	
	// Cart
	define( 'IS_CART_EXPIRATION_ACTIVE', '1' );
	define( 'IS_CART_EXPIRATION_PER_PRODUCT', '0' );
	define( 'CART_TIMEOUT', '30' );
	define( 'RESET_CART_TIMEOUT_ON_CHANGE', '0' );
}


// Init cart settings value to default
add_action( 'bookacti_init_settings_value', 'bookacti_init_cart_settings_value' );
function bookacti_init_cart_settings_value() {
	// Bookings
	$default_bookings_settings = get_option( 'bookacti_bookings_settings' );
	if( ! isset( $default_bookings_settings['show_temporary_bookings'] ) )	{ $default_bookings_settings['show_temporary_bookings']		= SHOW_TEMPORARY_BOOKINGS; }
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	// Cart
	$default_cart_settings = get_option( 'bookacti_cart_settings' );
	if( ! isset( $default_cart_settings['is_cart_expiration_active'] ) )	{ $default_cart_settings['is_cart_expiration_active']		= IS_CART_EXPIRATION_ACTIVE; }
	if( ! isset( $default_cart_settings['is_cart_expiration_per_product'] )){ $default_cart_settings['is_cart_expiration_per_product']	= IS_CART_EXPIRATION_PER_PRODUCT; }
	if( ! isset( $default_cart_settings['cart_timeout'] ) )					{ $default_cart_settings['cart_timeout']					= CART_TIMEOUT; }
	if( ! isset( $default_cart_settings['reset_cart_timeout_on_change'] ) )	{ $default_cart_settings['reset_cart_timeout_on_change']	= RESET_CART_TIMEOUT_ON_CHANGE; }
	update_option( 'bookacti_cart_settings', $default_cart_settings );
}


// Reset cart settings values to default
add_action( 'bookacti_reset_settings', 'bookacti_reset_cart_settings' );
function bookacti_reset_cart_settings() {
	// Bookings
	$default_bookings_settings = array();
	$default_bookings_settings['show_temporary_bookings']		= SHOW_TEMPORARY_BOOKINGS;
	update_option( 'bookacti_bookings_settings', $default_cart_settings );
	
	// Cart
	$default_cart_settings = array();
	$default_cart_settings['is_cart_expiration_active']			= IS_CART_EXPIRATION_ACTIVE;
	$default_cart_settings['is_cart_expiration_per_product']	= IS_CART_EXPIRATION_PER_PRODUCT;
	$default_cart_settings['cart_timeout']						= CART_TIMEOUT;
	$default_cart_settings['reset_cart_timeout_on_change']		= RESET_CART_TIMEOUT_ON_CHANGE;
	update_option( 'bookacti_cart_settings', $default_cart_settings );
}


// Delete cart settings
add_action( 'bookacti_delete_settings', 'bookacti_delete_cart_settings' );
function bookacti_delete_cart_settings() {
	// Bookings
	$default_bookings_settings = get_option( 'bookacti_bookings_settings' );
	if( isset( $default_bookings_settings['show_temporary_bookings'] ) )	{ unset( $default_bookings_settings['show_temporary_bookings'] ); }
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	// Cart
	delete_option( 'bookacti_cart_settings' );
}


// Add bookings list settings
add_action( 'bookacti_booking_list_tab_filter_after', 'bookacti_add_booking_list_in_cart_filter', 10, 1 );
function bookacti_add_booking_list_in_cart_filter( $params ) {
	$user_id = $params[ 'user_id' ];
	?>
	<div>
		<label for='bookacti-bookings-show-temporary-bookings' ><?php esc_html_e( 'Show temporary bookings', BOOKACTI_PLUGIN_NAME ); ?></label>
		<?php
		$show_temporary_bookings_array	= bookacti_get_setting_value( 'bookacti_bookings_settings', 'show_temporary_bookings' );
		$show_temporary_bookings		= 0;
		if( is_array( $show_temporary_bookings_array ) && isset( $show_temporary_bookings_array[ $user_id ] ) && ! is_null( $show_temporary_bookings_array[ $user_id ] ) ) {
			$show_temporary_bookings	= $show_temporary_bookings_array[ $user_id ];
		}

		$name	= 'bookings-show-temporary-bookings';
		$id		= 'bookacti-bookings-show-temporary-bookings';
		bookacti_onoffswitch( $name, $show_temporary_bookings, $id );

		$tip = __( "Show temporary bookings in the booking list (in cart bookings).", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
		?>
	</div>
<?php
}