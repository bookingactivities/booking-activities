<?php  
/**
 * Plugin Name: Booking Activities
 * Plugin URI: https://booking-activities.fr/en/?utm_source=plugin&utm_medium=plugin&utm_content=header
 * Description: Booking system specialized in activities (sports, cultural, leisure, events...). Works great with WooCommerce.
 * Version: 1.7.7
 * Author: Booking Activities Team
 * Author URI: https://booking-activities.fr/en/?utm_source=plugin&utm_medium=plugin&utm_content=header
 * Text Domain: booking-activities
 * Domain Path: /languages/
 * WC requires at least: 2.6
 * WC tested up to: 3.6
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
 * Copyright 2018 Yoan Cutillas
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// GLOBALS AND CONSTANTS
if( ! defined( 'BOOKACTI_VERSION' ) )		{ define( 'BOOKACTI_VERSION', '1.7.7' ); }
if( ! defined( 'BOOKACTI_PLUGIN_NAME' ) )	{ define( 'BOOKACTI_PLUGIN_NAME', 'booking-activities' ); }


// HEADER STRINGS (For translation)
__( 'Booking system specialized in activities (sports, cultural, leisure, events...). Works great with WooCommerce.', BOOKACTI_PLUGIN_NAME );


// INCLUDE LANGUAGES FILES
function bookacti_load_textdomain() { 
	
	$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
	$locale = apply_filters( 'plugin_locale', $locale, BOOKACTI_PLUGIN_NAME );
	
	unload_textdomain( BOOKACTI_PLUGIN_NAME );
	load_textdomain( BOOKACTI_PLUGIN_NAME, WP_LANG_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '-' . $locale . '.mo' );
	load_plugin_textdomain( BOOKACTI_PLUGIN_NAME, false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'bookacti_load_textdomain' );




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
 * Enqueue high priority scripts
 * @version 1.7.0
 * @global array $bookacti_translation_array
 */
function bookacti_enqueue_high_priority_global_scripts() {
	// On backend, only include these scripts on Booking Activities pages
	if( is_admin() && ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE LIBRARIES
	wp_enqueue_script( 'bookacti-js-moment',					plugins_url( 'lib/fullcalendar/moment.min.js', __FILE__ ),				array( 'jquery' ), BOOKACTI_VERSION, true );
	wp_enqueue_style ( 'bookacti-css-fullcalendar',				plugins_url( 'lib/fullcalendar/fullcalendar.min.css', __FILE__ ),		array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-fullcalendar-print',		plugins_url( 'lib/fullcalendar/fullcalendar.print.min.css', __FILE__ ),	array( 'bookacti-css-fullcalendar' ), BOOKACTI_VERSION, 'print' );
	wp_enqueue_script( 'bookacti-js-fullcalendar',				plugins_url( 'lib/fullcalendar/fullcalendar.min.js', __FILE__ ),		array( 'jquery', 'bookacti-js-moment' ), BOOKACTI_VERSION, true );
	wp_enqueue_script( 'bookacti-js-fullcalendar-locale-all',	plugins_url( 'lib/fullcalendar/locale-all.js', __FILE__ ),				array( 'jquery', 'bookacti-js-fullcalendar' ), BOOKACTI_VERSION, true );
	
	// INCLUDE JAVASCRIPT FILES
	wp_register_script( 'bookacti-js-global-var',				plugins_url( 'js/global-var.min.js', __FILE__ ),				array( 'jquery' ), BOOKACTI_VERSION, false ); // Load in header
	wp_register_script( 'bookacti-js-global-functions',			plugins_url( 'js/global-functions.min.js', __FILE__ ),			array( 'jquery', 'bookacti-js-global-var', 'jquery-ui-autocomplete', 'jquery-ui-tooltip' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-booking-system-functions',	plugins_url( 'js/booking-system-functions.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'jquery-effects-highlight' ), BOOKACTI_VERSION, true );
	
	// LOCALIZE SCRIPTS
	global $bookacti_translation_array;
	require_once( 'languages/script-translation.php' );
	wp_localize_script( 'bookacti-js-global-var',				'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-global-functions',			'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-booking-system-functions',	'bookacti_localized', $bookacti_translation_array );
	
	// ENQUEUE SCRIPTS
	wp_enqueue_script ( 'bookacti-js-global-var' );
	wp_enqueue_script ( 'bookacti-js-global-functions' );
	wp_enqueue_script ( 'bookacti-js-booking-system-functions' );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_high_priority_global_scripts', 10 );
add_action( 'wp_enqueue_scripts',	'bookacti_enqueue_high_priority_global_scripts', 10 );


/**
 * Enqueue normal priority scripts
 * @version 1.7.3
 * @global array $bookacti_translation_array
 */
function bookacti_enqueue_global_scripts() {
	global $bookacti_translation_array;
	if( empty( $bookacti_translation_array ) ) { require_once( 'languages/script-translation.php' ); }
	
	// Include WooCommerce style and scripts
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( ! is_admin() || bookacti_is_wc_screen( array( 'product', 'shop_order' ) ) ) {
			wp_enqueue_style ( 'bookacti-css-woocommerce',	plugins_url( 'css/woocommerce.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
		}
		if( ! is_admin() ) {
			wp_register_script( 'bookacti-js-woocommerce-frontend', plugins_url( 'js/woocommerce-frontend.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions', 'bookacti-js-booking-method-calendar' ), BOOKACTI_VERSION, true );
			wp_localize_script( 'bookacti-js-woocommerce-frontend', 'bookacti_localized', $bookacti_translation_array );
			wp_enqueue_script ( 'bookacti-js-woocommerce-frontend' );
		}
		wp_register_script( 'bookacti-js-woocommerce-global', plugins_url( 'js/woocommerce-global.min.js', __FILE__ ), array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-moment', 'jquery-ui-dialog' ), BOOKACTI_VERSION, true );
		wp_localize_script( 'bookacti-js-woocommerce-global', 'bookacti_localized', $bookacti_translation_array );
		wp_enqueue_script( 'bookacti-js-woocommerce-global' );
	}
		
	// On backend, only include these scripts on Booking Activities pages
	if( is_admin() && ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE STYLESHEETS
	wp_enqueue_style ( 'bookacti-css-global',		plugins_url( 'css/global.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-fonts',		plugins_url( 'css/fonts.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-bookings',		plugins_url( 'css/bookings.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-forms',		plugins_url( 'css/forms.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'jquery-ui-bookacti-theme',	plugins_url( 'lib/jquery-ui/themes/booking-activities/jquery-ui.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	
	// INCLUDE JAVASCRIPT FILES
	wp_register_script( 'bookacti-js-booking-system',			plugins_url( 'js/booking-system.min.js', __FILE__ ),			array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-booking-system-dialogs',	plugins_url( 'js/booking-system-dialogs.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-moment', 'jquery-ui-dialog' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-booking-method-calendar',	plugins_url( 'js/booking-method-calendar.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-bookings-functions',		plugins_url( 'js/bookings-functions.min.js', __FILE__ ),		array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions', ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-bookings-dialogs',			plugins_url( 'js/bookings-dialogs.min.js', __FILE__ ),			array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-moment', 'jquery-ui-dialog' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-forms',					plugins_url( 'js/forms.min.js', __FILE__ ),						array( 'jquery', 'bookacti-js-global-functions', 'jquery-ui-dialog', 'password-strength-meter' ), BOOKACTI_VERSION, true );
	
	// LOCALIZE SCRIPTS
	wp_localize_script( 'bookacti-js-booking-system',			'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-booking-system-dialogs',	'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-booking-method-calendar',	'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-bookings-functions',		'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-bookings-dialogs',			'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-forms',					'bookacti_localized', $bookacti_translation_array );
	
	// ENQUEUE SCRIPTS
	wp_enqueue_script( 'bookacti-js-booking-system' );
	wp_enqueue_script( 'bookacti-js-booking-system-dialogs' );
	wp_enqueue_script( 'bookacti-js-booking-method-calendar' );
	wp_enqueue_script( 'bookacti-js-bookings-functions' );
	wp_enqueue_script( 'bookacti-js-bookings-dialogs' );
	wp_enqueue_script( 'bookacti-js-forms' );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_global_scripts', 20 );
add_action( 'wp_enqueue_scripts',	'bookacti_enqueue_global_scripts', 20 );


/**
 * Enqueue high priority scripts in backend only
 * @version 1.7.0
 * @global array $bookacti_translation_array
 */
function bookacti_enqueue_high_priority_backend_scripts() {
	// On backend, only include these scripts on Booking Activities pages
	if( ! bookacti_is_booking_activities_screen() ) { return; }
	
	// INCLUDE JAVASCRIPT FILES
	wp_register_script( 'bookacti-js-backend-functions',	plugins_url( 'js/backend-functions.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-tooltip' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-bookings',				plugins_url( 'js/bookings.min.js', __FILE__ ),			array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions', 'bookacti-js-backend-functions' ), BOOKACTI_VERSION, true );
	
	// LOCALIZE SCRIPTS
	global $bookacti_translation_array;
	wp_localize_script( 'bookacti-js-bookings', 'bookacti_localized', $bookacti_translation_array );
	
	// ENQUEUE SCRIPTS
	wp_enqueue_script ( 'bookacti-js-backend-functions' );
	wp_enqueue_script ( 'bookacti-js-bookings' );
}
add_action( 'admin_enqueue_scripts','bookacti_enqueue_high_priority_backend_scripts', 15 );


/**
 * Enqueue low priority scripts in backend only
 * @version 1.7.6
 * @global array $bookacti_translation_array
 */
function bookacti_enqueue_backend_scripts() {
	global $bookacti_translation_array;
	if( empty( $bookacti_translation_array ) ) { require_once( 'languages/script-translation.php' ); }
	
	// Include WooCommerce scripts
	if( bookacti_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		if( bookacti_is_wc_screen( array( 'product' ) ) || bookacti_is_booking_activities_screen() ) {
			wp_register_script( 'bookacti-js-woocommerce-backend', plugins_url( 'js/woocommerce-backend.min.js', __FILE__ ), array( 'jquery' ), BOOKACTI_VERSION, true );
			wp_localize_script( 'bookacti-js-woocommerce-backend', 'bookacti_localized', $bookacti_translation_array );
			wp_enqueue_script ( 'bookacti-js-woocommerce-backend' );
		}
	}
	
	// On backend, only include these scripts on Booking Activities pages
	if( ! bookacti_is_booking_activities_screen() ) { return; }

	// INCLUDE STYLESHEETS
	wp_enqueue_style ( 'bookacti-css-backend',	plugins_url( 'css/backend.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-templates',plugins_url( 'css/templates.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	wp_enqueue_style ( 'bookacti-css-landing',	plugins_url( 'css/landing.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
	
	// INCLUDE JAVASCRIPT FILES
	wp_register_script( 'bookacti-js-templates-forms-control',	plugins_url( 'js/templates-forms-control.min.js', __FILE__ ),	array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-moment' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-templates-functions',		plugins_url( 'js/templates-functions.min.js', __FILE__ ),		array( 'jquery', 'jquery-touch-punch', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'jquery-effects-highlight' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-templates-dialogs',		plugins_url( 'js/templates-dialogs.min.js', __FILE__ ),			array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-global-functions', 'bookacti-js-backend-functions', 'bookacti-js-templates-forms-control', 'bookacti-js-moment', 'jquery-ui-dialog', 'jquery-ui-selectmenu' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-templates',				plugins_url( 'js/templates.min.js', __FILE__ ),					array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-fullcalendar', 'bookacti-js-global-functions', 'bookacti-js-templates-functions', 'bookacti-js-templates-dialogs' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-settings',					plugins_url( 'js/settings.min.js', __FILE__ ),					array( 'jquery' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-form-editor-dialogs',		plugins_url( 'js/form-editor-dialogs.min.js', __FILE__ ),		array( 'jquery', 'bookacti-js-global-var', 'bookacti-js-moment', 'jquery-ui-dialog', 'bookacti-js-backend-functions' ), BOOKACTI_VERSION, true );
	wp_register_script( 'bookacti-js-form-editor',				plugins_url( 'js/form-editor.min.js', __FILE__ ),				array( 'jquery', 'jquery-touch-punch', 'bookacti-js-global-var', 'bookacti-js-booking-system-functions', 'jquery-ui-sortable', 'bookacti-js-forms', 'bookacti-js-form-editor-dialogs' ), BOOKACTI_VERSION, true );
	
	// LOCALIZE SCRIPTS
	wp_localize_script( 'bookacti-js-templates-forms-control',	'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-templates-functions',		'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-templates-dialogs',		'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-templates',				'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-settings',					'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-form-editor-dialogs',		'bookacti_localized', $bookacti_translation_array );
	wp_localize_script( 'bookacti-js-form-editor',				'bookacti_localized', $bookacti_translation_array );
	
	// ENQUEUE SCRIPTS
	wp_enqueue_script ( 'bookacti-js-templates-forms-control' );
	wp_enqueue_script ( 'bookacti-js-templates-functions' );
	wp_enqueue_script ( 'bookacti-js-templates-dialogs' );
	wp_enqueue_script ( 'bookacti-js-templates' );
	wp_enqueue_script ( 'bookacti-js-settings' );
	wp_enqueue_script ( 'bookacti-js-form-editor-dialogs' );
	wp_enqueue_script ( 'bookacti-js-form-editor' );
}
add_action( 'admin_enqueue_scripts', 'bookacti_enqueue_backend_scripts', 30 );


/**
 * Enqueue low priority scripts in frontend only
 * @since 1.5.0
 * @version 1.7.0
 * @global array $bookacti_translation_array
 */
function bookacti_enqueue_frontend_scripts() {
	// Include stylesheets
	wp_enqueue_style ( 'bookacti-css-frontend', plugins_url( 'css/frontend.min.css', __FILE__ ), array(), BOOKACTI_VERSION );
}
add_action( 'wp_enqueue_scripts', 'bookacti_enqueue_frontend_scripts', 30 );




// ACTIVATE / DEACTIVATE / UPDATE / UNINSTALL

/**
 * Activate Booking Activities
 * @version 1.7.1
 */
function bookacti_activate() {
	// Allow users to manage Bookings
	bookacti_set_role_and_cap();

	// Create tables in database
    bookacti_create_tables();
	
	// Keep in memory the first installed date
	$install_date = get_option( 'bookacti-install-date' );
	if( ! $install_date ) {
		update_option( 'bookacti-install-date', date( 'Y-m-d H:i:s' ) );
	}
	
	// Check if the plugin is being updated
	bookacti_check_version( true );
	
	// Update current version
	delete_option( 'bookacti_version' );
	add_option( 'bookacti_version', BOOKACTI_VERSION );
		
	do_action( 'bookacti_activate' );
	
	// Flush rules after install
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bookacti_activate' );


/**
 * Deactivate Booking Activities
 * @version 1.7.1
 */
function bookacti_deactivate() {
	do_action( 'bookacti_deactivate' );
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
 * @version 1.4.3
 */
function bookacti_check_version( $from_activate = false ) {
	$old_version = get_option( 'bookacti_version' );
	if( $old_version !== BOOKACTI_VERSION ) {
		if( ! $from_activate ) { bookacti_activate(); }
		do_action( 'bookacti_updated', $old_version );
	}
}
add_action( 'init', 'bookacti_check_version', 5 );




// ADMIN MENU

/**
 * Create the Admin Menu
 * @version 1.5.0
 */
function bookacti_create_menu() {
    // Add a menu and submenus
    $icon_url = 'dashicons-calendar-alt';
    add_menu_page( 'Booking Activities', 'Booking Activities', 'bookacti_manage_booking_activities', 'booking-activities', null, $icon_url, '56.5' );
    add_submenu_page( 'booking-activities',	'Booking Activities',							_x( 'Home', 'Landing page tab name', BOOKACTI_PLUGIN_NAME ),'bookacti_manage_booking_activities',			'booking-activities',	'bookacti_landing_page' );
	add_submenu_page( 'booking-activities',	__( 'Calendar editor', BOOKACTI_PLUGIN_NAME ),	__( 'Calendar editor', BOOKACTI_PLUGIN_NAME ),				'bookacti_manage_templates',					'bookacti_calendars',	'bookacti_templates_page' );
	add_submenu_page( 'booking-activities',	__( 'Booking forms', BOOKACTI_PLUGIN_NAME ),	__( 'Booking forms', BOOKACTI_PLUGIN_NAME ),				'bookacti_manage_forms',						'bookacti_forms',		'bookacti_forms_page' );
	add_submenu_page( 'booking-activities',	__( 'Bookings', BOOKACTI_PLUGIN_NAME ),			__( 'Bookings', BOOKACTI_PLUGIN_NAME ),						'bookacti_manage_bookings',						'bookacti_bookings',	'bookacti_bookings_page' );
    
	do_action( 'bookacti_admin_menu' );
	
	add_submenu_page( 'booking-activities',	__( 'Settings', BOOKACTI_PLUGIN_NAME ),			__( 'Settings', BOOKACTI_PLUGIN_NAME ),						'bookacti_manage_booking_activities_settings',	'bookacti_settings',	'bookacti_settings_page' );
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