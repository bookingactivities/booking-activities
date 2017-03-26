<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

//DEFINE DEFAULT SETTINGS VALUES
add_action( 'plugins_loaded', 'bookacti_define_default_settings_constants' );
function bookacti_define_default_settings_constants() {
	define( 'DEFAULT_TEMPLATE_PER_USER', '0' );
	define( 'SHOW_PAST_EVENTS', '1' );
	define( 'ALLOW_TEMPLATES_FILTER', '1' );
	define( 'ALLOW_ACTIVITIES_FILTER', '1' );
	define( 'SHOW_INACTIVE_BOOKINGS', '0' );
	define( 'STARTED_EVENTS_BOOKABLE', '0' );
	define( 'BOOKING_METHOD', 'calendar' );
	define( 'ALLOW_CUSTOMERS_TO_CANCEL', '1' );
	define( 'ALLOW_CUSTOMERS_TO_RESCHEDULE', '1' );
	define( 'CANCELLATION_MIN_DELAY_BEFORE_EVENT', '7' );
	define( 'REFUND_ACTIONS_AFTER_CANCELLATION', 'email' );
	
	do_action( 'bookacti_define_settings_constants' );
}


//SET SETTINGS VALUES TO THEIR DEFAULT VALUE IF NULL
function bookacti_init_settings_values() {
	
	$default_template_settings = get_option( 'bookacti_template_settings' );
	if( ! isset( $default_template_settings['default_template_per_user'] ) ){ $default_template_settings['default_template_per_user']	= DEFAULT_TEMPLATE_PER_USER; }
	update_option( 'bookacti_template_settings', $default_template_settings );
	
	$default_bookings_settings = get_option( 'bookacti_bookings_settings' );
	if( ! isset( $default_bookings_settings['show_past_events'] ) )			{ $default_bookings_settings['show_past_events']			= SHOW_PAST_EVENTS; }
	if( ! isset( $default_bookings_settings['allow_templates_filter'] ) )	{ $default_bookings_settings['allow_templates_filter']		= ALLOW_TEMPLATES_FILTER; }
	if( ! isset( $default_bookings_settings['allow_activities_filter'] ) )	{ $default_bookings_settings['allow_activities_filter']		= ALLOW_ACTIVITIES_FILTER; }
	if( ! isset( $default_bookings_settings['show_inactive_bookings'] ) )	{ $default_bookings_settings['show_inactive_bookings']		= SHOW_INACTIVE_BOOKINGS; }
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	$default_cancellation_settings = get_option( 'bookacti_cancellation_settings' );
	if( ! isset( $default_cancellation_settings['allow_customers_to_cancel'] ) )			{ $default_bookings_settings['allow_customers_to_cancel']					= ALLOW_CUSTOMERS_TO_CANCEL; }
	if( ! isset( $default_cancellation_settings['allow_customers_to_reschedule'] ) )		{ $default_cancellation_settings['allow_customers_to_reschedule']			= ALLOW_CUSTOMERS_TO_RESCHEDULE; }
	if( ! isset( $default_cancellation_settings['cancellation_min_delay_before_event'] ) )	{ $default_cancellation_settings['cancellation_min_delay_before_event']		= CANCELLATION_MIN_DELAY_BEFORE_EVENT; }
	if( ! isset( $default_cancellation_settings['refund_actions_after_cancellation'] ) )	{ $default_cancellation_settings['refund_actions_after_cancellation']		= REFUND_ACTIONS_AFTER_CANCELLATION; }
	update_option( 'bookacti_cancellation_settings', $default_cancellation_settings );
	
	$default_general_settings = get_option( 'bookacti_general_settings' );
	if( ! isset( $default_general_settings['booking_method'] ) )			{ $default_general_settings['booking_method']				= BOOKING_METHOD; }
	if( ! isset( $default_general_settings['started_events_bookable'] ) )	{ $default_general_settings['started_events_bookable']		= STARTED_EVENTS_BOOKABLE; }
	update_option( 'bookacti_general_settings', $default_general_settings );
	
	do_action( 'bookacti_init_settings_value' );
}


//RESET SETTINGS TO DEFAULT VALUES
function bookacti_reset_settings() {
	
	$default_template_settings = array();
	$default_template_settings['default_template_per_user']		= DEFAULT_TEMPLATE_PER_USER;
	
	update_option( 'bookacti_template_settings', $default_template_settings );
	
	$default_bookings_settings = array();
	$default_bookings_settings['show_past_events']			= SHOW_PAST_EVENTS;
	$default_bookings_settings['allow_templates_filter']	= ALLOW_TEMPLATES_FILTER;
	$default_bookings_settings['allow_activities_filter']	= ALLOW_ACTIVITIES_FILTER;
	$default_bookings_settings['show_inactive_bookings']	= SHOW_INACTIVE_BOOKINGS;
	
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	$default_cancellation_settings = array();
	$default_cancellation_settings['allow_customers_to_cancel']				= ALLOW_CUSTOMERS_TO_CANCEL;
	$default_cancellation_settings['allow_customers_to_reschedule']			= ALLOW_CUSTOMERS_TO_RESCHEDULE;
	$default_cancellation_settings['cancellation_min_delay_before_event']	= CANCELLATION_MIN_DELAY_BEFORE_EVENT;
	$default_cancellation_settings['refund_actions_after_cancellation']		= REFUND_ACTIONS_AFTER_CANCELLATION;
	
	update_option( 'bookacti_cancellation_settings', $default_cancellation_settings );
	
	$default_general_settings = array();
	$default_general_settings['booking_method']					= BOOKING_METHOD;
	$default_general_settings['started_events_bookable']		= STARTED_EVENTS_BOOKABLE;
	
	update_option( 'bookacti_general_settings', $default_general_settings );
	
	do_action( 'bookacti_reset_settings' );
}


//RESET SETTINGS TO DEFAULT VALUES
function bookacti_delete_settings() {
	delete_option( 'bookacti_template_settings' );
	delete_option( 'bookacti_bookings_settings' );
	delete_option( 'bookacti_cancellation_settings' );
	delete_option( 'bookacti_general_settings' );
	
	do_action( 'bookacti_delete_settings' );
}


//GET SETTING VALUE
function bookacti_get_setting_value( $setting_page, $setting_field ) {
	
	$settings = get_option( $setting_page );
	
	if( ! isset( $settings[ $setting_field ] ) || ( empty( $settings[ $setting_field ] ) &&  $settings[ $setting_field ] !== '0' ) ) {
		if( defined( strtoupper( $setting_field ) ) ) {
			$settings[ $setting_field ] = constant( strtoupper( $setting_field ) );
			update_option( $setting_page, $settings );
		} else {
			$settings[ $setting_field ] = false;
		}
	}
	
	return $settings[ $setting_field ];
}

// GET BOOKING PARAMS BY USER
function bookacti_get_setting_value_by_user( $setting_page, $setting_field, $user_id = false ) {

	$user_id = $user_id ? $user_id : get_current_user_id();
	$settings = get_option( $setting_page );
	
	if( ! is_array( $settings ) ){
		$settings = array();
	}
	
	if( ! isset( $settings[ $setting_field ] ) || ! is_array( $settings[ $setting_field ] ) ) {
		$settings[ $setting_field ] = array();
	}
	
	if( ! isset( $settings[ $setting_field ][ $user_id ] ) || empty( $settings[ $setting_field ] ) ) {
		if( defined( strtoupper( $setting_field ) ) ) {
			$settings[ $setting_field ][ $user_id ] = constant( strtoupper( $setting_field ) );
			update_option( $setting_page, $settings );
		} else {
			$settings[ $setting_field ][ $user_id ] = false;
		}
	}
	
	return $settings[ $setting_field ][ $user_id ];
}


// SECTION CALLBACKS
function bookacti_settings_section_general_callback() { }
function bookacti_settings_section_cancellation_callback() { }
function bookacti_settings_section_template_callback() { }
function bookacti_settings_section_bookings_callback() { }


//GENERAL SETTINGS 
	//Booking method
	function bookacti_settings_field_booking_method_callback() {
		$selected_booking_method = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
		
		$available_booking_methods = bookacti_get_available_booking_methods();
		//Display the field
		?>
		<select name='bookacti_general_settings[booking_method]' >
		<?php
		foreach( $available_booking_methods as $booking_method_id => $booking_method_label ) {
			$selected = $selected_booking_method === $booking_method_id ? 'selected' : '';
			echo '<option value="' . esc_attr( $booking_method_id ) . '"' .  $selected . ' >'. esc_html( $booking_method_label ) . '</option>';
		}
		?>
		</select>
		<?php
		
		//Display the tip 
		/* translators: The word 'Calendar' refers to a booking method you have to translate too. Make sure you use the same word for both translation. */
		$tip  = apply_filters( 'bookacti_booking_methods_tip',
				__( "'Calendar': The user will have to pick the schedule directly on a calendar.", BOOKACTI_PLUGIN_NAME ) );
		
		$license_status = get_option( 'badp_license_status' );
		if( empty( $license_status ) || $license_status !== 'valid' ) {
			$tip .= '<br/>';
			$tip .= sprintf( __( 'Get more display methods with %1$sDisplay Pack%2$s add-on!', BOOKACTI_PLUGIN_NAME ),
							'<a href="' . __( 'http://booking-activities.fr/en/downloads/display-pack/', BOOKACTI_PLUGIN_NAME ) . '" target="_blank" >', '</a>');
		}
		
		bookacti_help_tip( $tip );
	}

	
	//Can the user book an event that began?
	function bookacti_settings_field_started_events_bookable_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
		
		//Display the field
		$name	= 'bookacti_general_settings[started_events_bookable]';
		$id		= 'started_events_bookable';
		bookacti_onoffswitch( $name, $is_active, $id );
		
		//Display the tip
		$tip = __( "Allow or disallow users to book an event that already began.", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	

	
//CANCELLATION SETTINGS 
	// Activate cancellation for customers
	function bookacti_settings_field_activate_cancel_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' );
		
		//Display the field
		$name	= 'bookacti_cancellation_settings[allow_customers_to_cancel]';
		$id		= 'allow_customers_to_cancel';
		bookacti_onoffswitch( $name, $is_active, $id );
		
		//Display the tip
		$tip = __( "Allow or disallow customers to cancel a booking after they order it.", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	// Activate reschedule for customers
	function bookacti_settings_field_activate_reschedule_callback() {
		
		$is_active = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' );
		
		//Display the field
		$name	= 'bookacti_cancellation_settings[allow_customers_to_reschedule]';
		$id		= 'allow_customers_to_reschedule';
		bookacti_onoffswitch( $name, $is_active, $id );
		
		//Display the tip
		$tip = __( "Allow or disallow customers to reschedule a booking after they order it.", BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	
	// Minimum delay before event a user can cancel or reschedule a booking
	function bookacti_settings_field_cancellation_delay_callback() {
		
		$delay = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'cancellation_min_delay_before_event' );
		
		//Display the field
		?>
		<input name='bookacti_cancellation_settings[cancellation_min_delay_before_event]' 
			   id='cancellation_min_delay_before_event' 
			   type='number' 
			   min='1'
			   value='<?php echo esc_attr( $delay ); ?>' />
		<?php
		/* translators: The user set an amount of time before this sentence. Ex: '2' days before the event */
		echo ' ' . esc_html__( 'days before the event', BOOKACTI_PLUGIN_NAME );
		
		//Display the tip
		$tip = __( 'Define the delay before the event in wich the customer will not be able to cancel his booking no more. Ex: "7": Customers will be able to cancel their booking at least 7 days before the event starts. After that, it will be to late.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	// possible actions to take after cancellation needing refund
	function bookacti_settings_field_cancellation_refund_actions_callback() {
		
		$actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
		if( ! is_array( $actions ) ) {
			if( ! empty( $actions ) ) {
				$actions = array( $actions => 1 );
			} else {
				$actions = array();
			}
		}
		
		//Display the field
		
		$possible_actions = bookacti_get_refund_actions();
		?>
		<div id='bookacti_refund_actions'>
			<input name='bookacti_cancellation_settings[refund_actions_after_cancellation][do_nothing]' 
				type='hidden' 
				value='1'
			/>
			<?php
			foreach( $possible_actions as $possible_action ) {
			?>
				<div class='bookacti_refund_action'>
					<input name='bookacti_cancellation_settings[refund_actions_after_cancellation][<?php echo esc_attr( $possible_action['id'] ); ?>]' 
					   id='refund_action_after_cancellation_<?php echo esc_attr( $possible_action['id'] ); ?>' 
					   type='checkbox' 
					   value='1'
					   <?php 
							if( isset( $actions[ $possible_action['id'] ] ) ) {
								checked( esc_attr( $actions[ $possible_action['id'] ] ), 1, true ); 
							}
						?>
					/>
				<?php
					echo ' ' . apply_filters( 'bookacti_translate_text', esc_html( $possible_action['label'] ) );
					
					if( $possible_action['description'] ) {
						//Display the tip
						$tip = apply_filters( 'bookacti_translate_text', $possible_action['description'] );
						bookacti_help_tip( $tip );
					}
				?>
				</div>
			<?php
			}
			?>
		</div>
		<?php
		
		//Display the tip
		$tip = __( 'Define the actions a customer will be able to take to be refunded after he cancels a booking.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	
	
// TEMPLATE SETTINGS
	function bookacti_settings_field_default_template_callback() { }
	
	
	
// BOOKINGS SETTINGS
	function bookacti_settings_field_show_past_events_callback() { }
	function bookacti_settings_field_templates_filter_callback() { }
	function bookacti_settings_field_activities_filter_callback() { }
	function bookacti_settings_field_show_inactive_bookings_callback() { }
	
	
// RESET NOTICES
function bookacti_reset_notices() {
	delete_option( 'bookacti-install-date' );
	delete_option( 'bookacti-first20-notice-viewed' );
	delete_option( 'bookacti-first20-notice-dismissed' );
	delete_option( 'bookacti-5stars-rating-notice-dismissed' );
}


// ROLES AND CAPABILITIES
	// Add roles and capabilities
	function bookacti_set_role_and_cap() {
		$administrator = get_role( 'administrator' );
		$administrator->add_cap( 'bookacti_manage_booking_activities' );
		$administrator->add_cap( 'bookacti_manage_bookings' );
		$administrator->add_cap( 'bookacti_manage_templates' );
		$administrator->add_cap( 'bookacti_manage_booking_activities_settings' );
		$administrator->add_cap( 'bookacti_read_templates' );
		$administrator->add_cap( 'bookacti_create_templates' );
		$administrator->add_cap( 'bookacti_edit_templates' );
		$administrator->add_cap( 'bookacti_delete_templates' );
		$administrator->add_cap( 'bookacti_create_activities' );
		$administrator->add_cap( 'bookacti_edit_activities' );
		$administrator->add_cap( 'bookacti_delete_activities' );
		$administrator->add_cap( 'bookacti_create_bookings' );
		$administrator->add_cap( 'bookacti_edit_bookings' );

		do_action( 'bookacti_set_capabilities' );
	}


	// Remove roles and capabilities
	function bookacti_unset_role_and_cap() {
		$administrator	= get_role( 'administrator' );
		$administrator->remove_cap( 'bookacti_manage_booking_activities' );
		$administrator->remove_cap( 'bookacti_manage_bookings' );
		$administrator->remove_cap( 'bookacti_manage_templates' );
		$administrator->remove_cap( 'bookacti_manage_booking_activities_settings' );
		$administrator->remove_cap( 'bookacti_read_templates' );
		$administrator->remove_cap( 'bookacti_create_templates' );
		$administrator->remove_cap( 'bookacti_edit_templates' );
		$administrator->remove_cap( 'bookacti_delete_templates' );
		$administrator->remove_cap( 'bookacti_create_activities' );
		$administrator->remove_cap( 'bookacti_edit_activities' );
		$administrator->remove_cap( 'bookacti_delete_activities' );
		$administrator->remove_cap( 'bookacti_create_bookings' );
		$administrator->remove_cap( 'bookacti_edit_bookings' );

		do_action( 'bookacti_unset_capabilities' );
	}