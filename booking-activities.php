<?php  
/**
 * Plugin Name: Booking Activities
 * Plugin URI: https://booking-activities.fr/en/?utm_source=plugin&utm_medium=plugin&utm_content=header
 * Description: Booking system specialized in activities (sports, cultural, leisure, events...). Works great with WooCommerce.
 * Version: 1.8.9
 * Author: Booking Activities Team
 * Author URI: https://booking-activities.fr/en/?utm_source=plugin&utm_medium=plugin&utm_content=header
 * Text Domain: booking-activities
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 4.5
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * This file is part of Booking Activities.
 * 
 * Booking Activities is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * 
 * Booking Activities is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Booking Activities. If not, see <http://www.gnu.org/licenses/>.
 * 
 * @package Booking Activities
 * @category Core
 * @author Booking Activities Team
 * 
 * Copyright 2020 Yoan Cutillas
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// GLOBALS AND CONSTANTS
if( ! defined( 'BOOKACTI_VERSION' ) )		{ define( 'BOOKACTI_VERSION', '1.8.9' ); }
if( ! defined( 'BOOKACTI_PLUGIN_NAME' ) )	{ define( 'BOOKACTI_PLUGIN_NAME', 'booking-activities' ); }


// HEADER STRINGS (For translation)
esc_html__( 'Booking system specialized in activities (sports, cultural, leisure, events...). Works great with WooCommerce.', 'booking-activities' );


// INCLUDE LANGUAGES FILES

/**
 * Load or reload Booking Activities language files
 * @version 1.8.0
 * @param string $locale
 */
function bookacti_load_textdomain( $locale = '' ) { 
	if( ! $locale ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : ( is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		$locale = apply_filters( 'plugin_locale', $locale, 'booking-activities' );
	}
	
	unload_textdomain( 'booking-activities' );
	// Load .mo from wp-content/languages/booking-activities/
	load_textdomain( 'booking-activities', WP_LANG_DIR . '/booking-activities/booking-activities-' . $locale . '.mo' );
	// Load .mo from wp-content/languages/plugins/
	// Fallback on .mo from wp-content/plugins/booking-activities/languages
	load_plugin_textdomain( 'booking-activities', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'bookacti_load_textdomain' );
add_action( 'bookacti_locale_switched', 'bookacti_load_textdomain' );




// INCLUDE PHP FUNCTIONS
include_once( 'functions/functions-global.php' ); 
include_once( 'functions/functions-booking-system.php' ); 
include_once( 'functions/functions-templates.php' );
include_once( 'functions/functions-templates-forms-control.php' );
include_once( 'functions/functions-bookings.php' );
include_once( 'functions/functions-forms.php' );
include_once( 'functions/functions-settings.php' );
include_once( 'functions/functions-notifications.php' );

include_once( 'controller/controller-templates.php' );
include_once( 'controller/controller-booking-system.php' );
include_once( 'controller/controller-settings.php' );
include_once( 'controller/controller-notifications.php' );
include_once( 'controller/controller-bookings.php' );
include_once( 'controller/controller-forms.php' );
include_once( 'controller/controller-shortcodes.php' );


// INCLUDE DATABASE FUNCTIONS
require_once( 'model/model-global.php' );
require_once( 'model/model-install.php' );
require_once( 'model/model-settings.php' );
require_once( 'model/model-templates.php' );
require_once( 'model/model-booking-system.php' );
require_once( 'model/model-bookings.php' );
require_once( 'model/model-forms.php' );


// INCLUDE CLASSES
if( is_admin() ) {
	require_once( 'class/class-bookings-list.php' );
	require_once( 'class/class-forms-list.php' );
}


// If woocommerce is active, include functions
if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	include_once( 'controller/controller-woocommerce-bookings.php' );
	include_once( 'controller/controller-woocommerce-backend.php' );
	include_once( 'controller/controller-woocommerce-frontend.php' );
	include_once( 'controller/controller-woocommerce-settings.php' );
	include_once( 'controller/controller-woocommerce-notifications.php' );
	include_once( 'controller/controller-woocommerce-forms.php' );
	include_once( 'functions/functions-woocommerce.php' );
	require_once( 'model/model-woocommerce.php' );
}




// INCLUDE SCRIPTS

/**
 * Enqueue the javascript variables early once and for all scripts
 * @since 1.8.0
 * @global array $bookacti_localized
 */
function bookacti_enqueue_js_variables() {
?>
	<script>
		var bookacti_localized = <?php echo json_encode( bookacti_get_js_variables() ); ?>;
	</script>
<?php
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_js_variables', 5 );
add_action( 'wp_enqueue_scripts',	'bookacti_enqueue_js_variables', 5 );


/**
 * Enqueue high priority scripts
 * @version 1.8.5
 */
function bookacti_enqueue_high_priority_global_scripts() {
	// Include global var on WC products and orders screens
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( bookacti_is_wc_screen( array( 'product', 'product_variation', 'shop_order' ) ) ) {
			wp_enqueue_script( 'bookacti-js-global-var', plugins_url( 'js/global-var.min.js', __FILE__ ), array( 'jquery' ), BOOKACTI_VERSION, false ); // Load in header
		}
	}
	
	// On backend, only include these scripts on Booking Activities pages
	if( is_admin() && ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE LIBRARIES
	$moment_version = '2.25.3';
	if( ! wp_script_is( 'moment', 'registered' ) )	{ wp_register_script( 'moment', plugins_url( 'lib/fullcalendar/moment.min.js', __FILE__ ), array( 'jquery' ), $moment_version, true ); }
	if( ! wp_script_is( 'moment', 'enqueued' ) )	{ wp_enqueue_script( 'moment' ); }
	
	$fullcalendar_version = '3.10.2';
	if( ! wp_script_is( 'fullcalendar', 'enqueued' ) ) { wp_enqueue_script( 'fullcalendar', plugins_url( 'lib/fullcalendar/fullcalendar.min.js', __FILE__ ), array( 'jquery', 'moment' ), $fullcalendar_version, true ); }
	wp_enqueue_script( 'bookacti-js-fullcalendar-locale-all', plugins_url( 'lib/fullcalendar/locale-all.js', __FILE__ ), array( 'jquery', 'fullcalendar' ), $fullcalendar_version, true );
	
	// INCLUDE STYLESHEETS
	wp_enqueue_style ( 'bookacti-css-fullcalendar',				plugins_url( 'lib/fullcalendar/fullcalendar.min.css', __FILE__ ),		array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-fullcalendar-print',		plugins_url( 'lib/fullcalendar/fullcalendar.print.min.css', __FILE__ ),	array( 'bookacti-css-fullcalendar' ), BOOKACTI_VERSION, 'print' );
	
	// INCLUDE JAVASCRIPT FILES
	wp_enqueue_script( 'bookacti-js-global-var',				plugins_url( 'js/global-var.min.js', __FILE__ ),				array( 'jquery' ), BOOKACTI_VERSION, false ); // Load in header
	wp_enqueue_script( 'bookacti-js-global-functions',			plugins_url( 'js/global-functions.min.js', __FILE__ ),			array( 'jquery', 'jquery-ui-tooltip', 'bookacti-js-global-var' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-booking-system-functions',	plugins_url( 'js/booking-system-functions.min.js', __FILE__ ),	array( 'jquery', 'fullcalendar', 'jquery-effects-highlight', 'bookacti-js-global-var' ), BOOKACTI_VERSION, true );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_high_priority_global_scripts', 10 );
add_action( 'wp_enqueue_scripts',	'bookacti_enqueue_high_priority_global_scripts', 10 );


/**
 * Enqueue normal priority scripts
 * @version 1.8.0
 */
function bookacti_enqueue_global_scripts() {
	// Include WooCommerce style and scripts
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( ! is_admin() || bookacti_is_wc_screen( array( 'product', 'product_variation', 'shop_order' ) ) ) {
			wp_enqueue_style ( 'bookacti-css-woocommerce',	plugins_url( 'css/woocommerce.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
		}
		if( ! is_admin() ) {
			wp_enqueue_script( 'bookacti-js-woocommerce-frontend', plugins_url( 'js/woocommerce-frontend.min.js', __FILE__ ), array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-booking-method-calendar' ), BOOKACTI_VERSION, true );
		}
		wp_enqueue_script( 'bookacti-js-woocommerce-global', plugins_url( 'js/woocommerce-global.min.js', __FILE__ ), array( 'jquery', 'moment', 'jquery-ui-dialog', 'bookacti-js-global-var', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	}
		
	// On backend, only include these scripts on Booking Activities pages
	if( is_admin() && ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE STYLESHEETS
	wp_enqueue_style( 'bookacti-css-global',		plugins_url( 'css/global.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style( 'bookacti-css-fonts',			plugins_url( 'css/fonts.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style( 'bookacti-css-bookings',		plugins_url( 'css/bookings.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style( 'bookacti-css-forms',			plugins_url( 'css/forms.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style( 'jquery-ui-bookacti-theme',	plugins_url( 'lib/jquery-ui/themes/booking-activities/jquery-ui.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	
	// INCLUDE JAVASCRIPT FILES
	wp_enqueue_script( 'bookacti-js-booking-system',			plugins_url( 'js/booking-system.min.js', __FILE__ ),			array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-booking-system-dialogs',	plugins_url( 'js/booking-system-dialogs.min.js', __FILE__ ),	array( 'jquery', 'moment', 'jquery-ui-dialog', 'bookacti-js-global-var' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-booking-method-calendar',	plugins_url( 'js/booking-method-calendar.min.js', __FILE__ ),	array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-bookings-functions',		plugins_url( 'js/bookings-functions.min.js', __FILE__ ),		array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions', ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-bookings-dialogs',			plugins_url( 'js/bookings-dialogs.min.js', __FILE__ ),			array( 'jquery', 'moment', 'jquery-ui-dialog', 'bookacti-js-global-var', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-forms',						plugins_url( 'js/forms.min.js', __FILE__ ),						array( 'jquery', 'jquery-ui-dialog', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_global_scripts', 20 );
add_action( 'wp_enqueue_scripts',	'bookacti_enqueue_global_scripts', 20 );


/**
 * Enqueue high priority scripts in backend only
 * @version 1.8.0
 */
function bookacti_enqueue_high_priority_backend_scripts() {
	// On backend, only include these scripts on Booking Activities pages
	if( ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE LIBRARIES
	$select2_version = '4.0.13';
	wp_enqueue_script( 'select2', plugins_url( 'lib/select2/select2.min.js', __FILE__ ), array( 'jquery' ), $select2_version, true );
	wp_enqueue_style( 'select2', plugins_url( 'lib/select2/select2.min.css', __FILE__ ), array(), $select2_version );
	
	// INCLUDE JAVASCRIPT FILES
	wp_enqueue_script( 'bookacti-js-backend-functions',	plugins_url( 'js/backend-functions.min.js', __FILE__ ),	array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'bookacti-js-global-var', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-bookings',			plugins_url( 'js/bookings.min.js', __FILE__ ),			array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-backend-functions' ), BOOKACTI_VERSION, true );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_high_priority_backend_scripts', 15 );


/**
 * Enqueue low priority scripts in backend only
 * @version 1.8.0
 */
function bookacti_enqueue_backend_scripts() {
	// Include WooCommerce scripts
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( bookacti_is_wc_screen( array( 'product', 'product_variation', 'shop_order' ) ) || bookacti_is_booking_activities_screen() ) {
			wp_enqueue_script( 'bookacti-js-woocommerce-backend', plugins_url( 'js/woocommerce-backend.min.js', __FILE__ ), array( 'jquery' ), BOOKACTI_VERSION, true );
		}
	}
	
	// On backend, only include these scripts on Booking Activities pages
	if( ! bookacti_is_booking_activities_screen() ) { return; }

	// INCLUDE STYLESHEETS
	wp_enqueue_style ( 'bookacti-css-backend',	plugins_url( 'css/backend.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-templates',plugins_url( 'css/templates.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-landing',	plugins_url( 'css/landing.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	
	// INCLUDE JAVASCRIPT FILES
	wp_enqueue_script( 'bookacti-js-templates-forms-control',	plugins_url( 'js/templates-forms-control.min.js', __FILE__ ),	array( 'jquery', 'moment', 'bookacti-js-global-var' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-templates-functions',		plugins_url( 'js/templates-functions.min.js', __FILE__ ),		array( 'jquery', 'fullcalendar', 'jquery-touch-punch', 'jquery-effects-highlight', 'bookacti-js-global-var' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-templates-dialogs',			plugins_url( 'js/templates-dialogs.min.js', __FILE__ ),			array( 'jquery', 'moment', 'jquery-ui-dialog', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-backend-functions', 'bookacti-js-templates-forms-control' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-templates',					plugins_url( 'js/templates.min.js', __FILE__ ),					array( 'jquery', 'fullcalendar', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-templates-functions', 'bookacti-js-templates-dialogs' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-settings',					plugins_url( 'js/settings.min.js', __FILE__ ),					array( 'jquery' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-form-editor-dialogs',		plugins_url( 'js/form-editor-dialogs.min.js', __FILE__ ),		array( 'jquery', 'moment', 'jquery-ui-dialog', 'bookacti-js-global-var', 'bookacti-js-backend-functions' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-form-editor',				plugins_url( 'js/form-editor.min.js', __FILE__ ),				array( 'jquery', 'jquery-touch-punch', 'jquery-ui-sortable', 'bookacti-js-global-var', 'bookacti-js-booking-system-functions', 'bookacti-js-forms', 'bookacti-js-form-editor-dialogs' ), BOOKACTI_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'bookacti_enqueue_backend_scripts', 30 );


/**
 * Enqueue low priority scripts in frontend only
 * @since 1.5.0
 * @version 1.8.0
 */
function bookacti_enqueue_frontend_scripts() {
	// Include stylesheets
	wp_enqueue_style( 'bookacti-css-frontend', plugins_url( 'css/frontend.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
}
add_action( 'wp_enqueue_scripts', 'bookacti_enqueue_frontend_scripts', 30 );




// ACTIVATE / DEACTIVATE / UPDATE / UNINSTALL

/**
 * Activate Booking Activities
 * @version 1.8.0
 */
function bookacti_activate() {
	if( ! is_blog_installed() ) { return; }
	
	// Make sure not to run this function twice
	if( get_transient( 'bookacti_installing' ) === 'yes' ) { return; }
	set_transient( 'bookacti_installing', 'yes', MINUTE_IN_SECONDS * 10 );
	
	// Allow users to manage Bookings
	bookacti_set_role_and_cap();

	// Create tables in database
    bookacti_create_tables();
	
	// Keep in memory the first installed date
	$install_date = get_option( 'bookacti-install-date' );
	if( ! $install_date ) { update_option( 'bookacti-install-date', date( 'Y-m-d H:i:s' ) ); }
	
	// Update current version
	delete_option( 'bookacti_version' );
	add_option( 'bookacti_version', BOOKACTI_VERSION );
	
	delete_transient( 'bookacti_installing' );
	
	do_action( 'bookacti_activate' );
	
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bookacti_activate' );


/**
 * Deactivate Booking Activities
 * @version 1.8.0
 */
function bookacti_deactivate() {
	do_action( 'bookacti_deactivate' );
	
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bookacti_deactivate' );


/**
 * Uninstall Booking Activities
 * @version 1.7.3
 */
function bookacti_uninstall() {
	// Delete notices acknowledgement
	bookacti_reset_notices();
	
	if( bookacti_get_setting_value( 'bookacti_general_settings', 'delete_data_on_uninstall' ) ) {
		// Delete plugin settings
		bookacti_delete_settings();
		
		// Delete user meta related to Booking Activities
		bookacti_delete_user_data();
		
		// Drop tables and every Booking Activities Data
		bookacti_drop_tables();
		
		// Delete Booking Activities files
		$uploads_dir = wp_upload_dir();
		$bookacti_upload_dir = trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/';
		if( is_dir( $bookacti_upload_dir ) ) {
			bookacti_delete_files( $bookacti_upload_dir, true );
		}
	}
	
	// Unset roles and capabilities
	bookacti_unset_role_and_cap();
	
	do_action( 'bookacti_uninstall' );
	
	// Clear any cached data that has been removed
	wp_cache_flush();
}
register_uninstall_hook( __FILE__, 'bookacti_uninstall' );


/**
 * Update Booking Activities
 * @version 1.8.0
 */
function bookacti_check_version() {
	if( defined( 'IFRAME_REQUEST' ) ) { return; }
	$old_version = get_option( 'bookacti_version' );
	if( $old_version !== BOOKACTI_VERSION ) {
		bookacti_activate();
		do_action( 'bookacti_updated', $old_version );
	}
}
add_action( 'init', 'bookacti_check_version', 5 );


/**
 * Update the form settings and the template settings that relies on global settings removed in 1.7.16
 * This function is temporary
 * @since 1.7.16
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_removed_global_settings_in_1_7_16( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.7.16
	if( version_compare( $old_version, '1.7.16', '<' ) ) {
		// Get the global values
		$global_booking_method				= bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
		$global_availability_period_start	= bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_start' );
		$global_availability_period_end		= bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_end' );
		
		global $wpdb;
		
		// Update the "Booking method" setting (Calendar form fields)
		$booking_method_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => 'method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		$wc_product_booking_method_updated = $wpdb->update( 
			$wpdb->postmeta, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => '_bookacti_booking_method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		$wc_variation_booking_method_updated = $wpdb->update( 
			$wpdb->postmeta, 
			array( 'meta_value' => $global_booking_method ? $global_booking_method : 'calendar' ),
			array( 'meta_key' => 'bookacti_variable_booking_method', 'meta_value' => 'site' ),
			array( '%s' ),
			array( '%s', '%s' )
		);
		
		// Update the "Events will be bookable in" setting (Templates, Calendar form fields)
		$availability_period_start_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_availability_period_start ? $global_availability_period_start : 0 ),
			array( 'meta_key' => 'availability_period_start', 'meta_value' => -1 ),
			array( '%d' ),
			array( '%s', '%d' )
		);
		
		// Update the "Events will be bookable in" setting (Templates, Calendar form fields)
		$availability_period_end_updated = $wpdb->update( 
			BOOKACTI_TABLE_META, 
			array( 'meta_value' => $global_availability_period_end ? $global_availability_period_end : 0 ),
			array( 'meta_key' => 'availability_period_end', 'meta_value' => -1 ),
			array( '%d' ),
			array( '%s', '%d' )
		);
	}
}
add_action( 'bookacti_updated', 'bookacti_update_removed_global_settings_in_1_7_16', 10 );


/**
 * Remove the template settings removed in 1.7.17
 * This function is temporary
 * @since 1.7.17
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_delete_removed_template_settings_in_1_7_17( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.7.17
	if( version_compare( $old_version, '1.7.17', '<' ) ) {
		global $wpdb;
		
		// Delete templates availability_period_start
		$availability_period_start_deleted = $wpdb->delete( 
			BOOKACTI_TABLE_META, 
			array( 
				'object_type' => 'template',
				'meta_key' => 'availability_period_start'
			), 
			array( '%s', '%s' ) 
		);
		
		// Delete templates availability_period_end
		$availability_period_end_deleted = $wpdb->delete( 
			BOOKACTI_TABLE_META, 
			array( 
				'object_type' => 'template',
				'meta_key' => 'availability_period_end'
			), 
			array( '%s', '%s' ) 
		);
	}
}
add_action( 'bookacti_updated', 'bookacti_delete_removed_template_settings_in_1_7_17', 20 );


/**
 * Update the refactored settings in 1.8.0
 * This function is temporary
 * @since 1.8.0
 * @version 1.8.4
 * @global wpdb $wpdb
 * @param string $old_version
 */
function bookacti_update_refactored_settings_in_1_8_0( $old_version ) {
	// Do it only once, when Booking Activities is updated for the first time after 1.8.0
	if( version_compare( $old_version, '1.8.0', '<' ) ) {
		// Rename cancellation_min_delay_before_event option to booking_changes_deadline and 
		// Convert its value to seconds
		$cancellation_options = get_option( 'bookacti_cancellation_settings' );
		if( isset( $cancellation_options[ 'cancellation_min_delay_before_event' ] ) ) {
			$cancellation_options[ 'booking_changes_deadline' ] = intval( $cancellation_options[ 'cancellation_min_delay_before_event' ] ) * 86400;
			unset( $cancellation_options[ 'cancellation_min_delay_before_event' ] );
			update_option( 'bookacti_cancellation_settings', $cancellation_options );
		}
		
		global $wpdb;
		
		// Convert the "booking_changes_deadline" options values to seconds
		$query_booking_changes_deadline_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), IF( meta_value < 0, "", meta_value ) ) WHERE meta_key = "booking_changes_deadline"';
		$wpdb->query( $query_booking_changes_deadline_value );
		
		// Convert the "availability_period_start" options values to seconds
		$query_availability_period_start_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), meta_value ) WHERE meta_key = "availability_period_start"';
		$wpdb->query( $query_availability_period_start_value );
		
		// Convert the "availability_period_end" options values to seconds
		$query_availability_period_end_value = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = IF( ( meta_value > 0 AND meta_value < 86400 ), ( CAST( meta_value AS UNSIGNED ) * 86400 ), meta_value ) WHERE meta_key = "availability_period_end"';
		$wpdb->query( $query_availability_period_end_value );
	}
}
add_action( 'bookacti_updated', 'bookacti_update_refactored_settings_in_1_8_0', 30 );




// ADMIN MENU

/**
 * Create the Admin Menu
 * @version 1.8.5
 */
function bookacti_create_menu() {
    // Add a menu and submenus
    $icon_url = 'dashicons-calendar-alt';
    add_menu_page( 'Booking Activities', 'Booking Activities', 'bookacti_manage_booking_activities', 'booking-activities', null, $icon_url, '58.5' );
    add_submenu_page( 'booking-activities',	'Booking Activities',							_x( 'Home', 'Landing page tab name', 'booking-activities' ),'bookacti_manage_booking_activities',			'booking-activities',	'bookacti_landing_page' );
	add_submenu_page( 'booking-activities',	__( 'Calendar editor', 'booking-activities' ),	__( 'Calendar editor', 'booking-activities' ),				'bookacti_manage_templates',					'bookacti_calendars',	'bookacti_templates_page' );
	add_submenu_page( 'booking-activities',	__( 'Booking forms', 'booking-activities' ),	__( 'Booking forms', 'booking-activities' ),				'bookacti_manage_forms',						'bookacti_forms',		'bookacti_forms_page' );
	add_submenu_page( 'booking-activities',	__( 'Bookings', 'booking-activities' ),			__( 'Bookings', 'booking-activities' ),						'bookacti_manage_bookings',						'bookacti_bookings',	'bookacti_bookings_page' );
    
	do_action( 'bookacti_admin_menu' );
	
	add_submenu_page( 'booking-activities',	__( 'Settings', 'booking-activities' ),			__( 'Settings', 'booking-activities' ),						'bookacti_manage_booking_activities_settings',	'bookacti_settings',	'bookacti_settings_page' );
}
add_action( 'admin_menu', 'bookacti_create_menu' );


/**
 * Include content of Booking Activities landing page
 */
function bookacti_landing_page() {
    include_once( 'view/view-landing.php' );
}

/**
 * Include content of Calendar editor top-level menu page
 */
function bookacti_templates_page() {
    include_once( 'view/view-templates.php' );
}

/**
 * Include content of Forms top-level menu page
 * @since 1.5.0
 */
function bookacti_forms_page() {
	
	$can_create_form	= current_user_can( 'bookacti_create_forms' );
	$can_edit_form		= current_user_can( 'bookacti_edit_forms' );
	$load_form_editor	= false;
	
	if( ! empty( $_GET[ 'action' ] ) ) {
		if(		( $_GET[ 'action' ] === 'new' && $can_create_form )
			||	( $_GET[ 'action' ] === 'edit' && ! empty( $_GET[ 'form_id' ] ) && is_numeric( $_GET[ 'form_id' ] ) && $can_edit_form ) ) {
			$load_form_editor = true;
		}
	}
	
	if( $load_form_editor ) {
		include_once( 'view/view-form-editor.php' );
	} else {
		include_once( 'view/view-form-list.php' );
	}
}

/**
 * Include content of Bookings top-level menu page
 */
function bookacti_bookings_page() {
    include_once( 'view/view-bookings.php' );
}

/**
 * Include content of Settings top-level menu page
 */
function bookacti_settings_page() {
    include_once( 'view/view-settings.php' );
}