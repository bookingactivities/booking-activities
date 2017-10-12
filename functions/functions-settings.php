<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Define default settings values
 * 
 * @version 1.2.0
 */
function bookacti_define_default_settings_constants() {
	if( ! defined( 'BOOKACTI_DEFAULT_TEMPLATE_PER_USER' ) )				{ define( 'BOOKACTI_DEFAULT_TEMPLATE_PER_USER', '0' ); }
	if( ! defined( 'BOOKACTI_SHOW_PAST_EVENTS' ) )						{ define( 'BOOKACTI_SHOW_PAST_EVENTS', '1' ); }
	if( ! defined( 'BOOKACTI_ALLOW_TEMPLATES_FILTER' ) )				{ define( 'BOOKACTI_ALLOW_TEMPLATES_FILTER', '1' ); }
	if( ! defined( 'BOOKACTI_ALLOW_ACTIVITIES_FILTER' ) )				{ define( 'BOOKACTI_ALLOW_ACTIVITIES_FILTER', '1' ); }
	if( ! defined( 'BOOKACTI_SHOW_INACTIVE_BOOKINGS' ) )				{ define( 'BOOKACTI_SHOW_INACTIVE_BOOKINGS', '0' ); }
	
	if( ! defined( 'BOOKACTI_BOOKING_METHOD' ) )						{ define( 'BOOKACTI_BOOKING_METHOD', 'calendar' ); }
	if( ! defined( 'BOOKACTI_WHEN_EVENTS_LOAD' ) )						{ define( 'BOOKACTI_WHEN_EVENTS_LOAD', 'on_page_load' ); }
	if( ! defined( 'BOOKACTI_STARTED_EVENTS_BOOKABLE' ) )				{ define( 'BOOKACTI_STARTED_EVENTS_BOOKABLE', '0' ); }
	if( ! defined( 'BOOKACTI_DEFAULT_BOOKING_STATE' ) )					{ define( 'BOOKACTI_DEFAULT_BOOKING_STATE', 'pending' ); }
	if( ! defined( 'BOOKACTI_TIMEZONE' ) )								{ $date = new DateTime(); $tz = $date->getTimezone()->getName(); define( 'BOOKACTI_TIMEZONE', $tz ); }
	if( ! defined( 'BOOKACTI_DATE_FORMAT' ) )							{	/* translators: Date format, please display only day month hours and minutes. This can be displayed as a period like Sep, 05th at 4:30 PM → 5:30 PM. "→ 5:30PM" will be displayed as is at the end of your date format. Use JS moment documentation to choose your tags https://momentjs.com/docs/#/displaying/format/ */
																			define( 'BOOKACTI_DATE_FORMAT', __( 'MMM, Do - LT', BOOKACTI_PLUGIN_NAME ) ); }
	
	if( ! defined( 'BOOKACTI_ALLOW_CUSTOMERS_TO_CANCEL' ) )				{ define( 'BOOKACTI_ALLOW_CUSTOMERS_TO_CANCEL', '1' ); }
	if( ! defined( 'BOOKACTI_ALLOW_CUSTOMERS_TO_RESCHEDULE' ) )			{ define( 'BOOKACTI_ALLOW_CUSTOMERS_TO_RESCHEDULE', '1' ); }
	if( ! defined( 'BOOKACTI_CANCELLATION_MIN_DELAY_BEFORE_EVENT' ) )	{ define( 'BOOKACTI_CANCELLATION_MIN_DELAY_BEFORE_EVENT', '7' ); }
	if( ! defined( 'BOOKACTI_REFUND_ACTIONS_AFTER_CANCELLATION' ) )		{ define( 'BOOKACTI_REFUND_ACTIONS_AFTER_CANCELLATION', 'do_nothing' ); }
	
	if( ! defined( 'BOOKACTI_NOTIFICATIONS_FROM_NAME' ) )				{ define( 'BOOKACTI_NOTIFICATIONS_FROM_NAME', get_bloginfo( 'name' ) ); }
	if( ! defined( 'BOOKACTI_NOTIFICATIONS_FROM_EMAIL' ) )				{ define( 'BOOKACTI_NOTIFICATIONS_FROM_EMAIL', get_bloginfo( 'admin_email' ) ); }
	if( ! defined( 'BOOKACTI_NOTIFICATIONS_ASYNC_EMAIL' ) )				{ define( 'BOOKACTI_NOTIFICATIONS_ASYNC_EMAIL', '1' ); }
	
	do_action( 'bookacti_define_settings_constants' );
}
add_action( 'plugins_loaded', 'bookacti_define_default_settings_constants' );


/**
 * Set settings values to their default value if null
 * 
 * @version 1.2.0
 */
function bookacti_init_settings_values() {
	
	$default_template_settings = get_option( 'bookacti_template_settings' );
	if( ! isset( $default_template_settings['default_template_per_user'] ) ){ $default_template_settings['default_template_per_user']	= BOOKACTI_DEFAULT_TEMPLATE_PER_USER; }
	update_option( 'bookacti_template_settings', $default_template_settings );
	
	$default_bookings_settings = get_option( 'bookacti_bookings_settings' );
	if( ! isset( $default_bookings_settings['show_past_events'] ) )			{ $default_bookings_settings['show_past_events']		= BOOKACTI_SHOW_PAST_EVENTS; }
	if( ! isset( $default_bookings_settings['allow_templates_filter'] ) )	{ $default_bookings_settings['allow_templates_filter']	= BOOKACTI_ALLOW_TEMPLATES_FILTER; }
	if( ! isset( $default_bookings_settings['allow_activities_filter'] ) )	{ $default_bookings_settings['allow_activities_filter']	= BOOKACTI_ALLOW_ACTIVITIES_FILTER; }
	if( ! isset( $default_bookings_settings['show_inactive_bookings'] ) )	{ $default_bookings_settings['show_inactive_bookings']	= BOOKACTI_SHOW_INACTIVE_BOOKINGS; }
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	$default_cancellation_settings = get_option( 'bookacti_cancellation_settings' );
	if( ! isset( $default_cancellation_settings['allow_customers_to_cancel'] ) )			{ $default_bookings_settings['allow_customers_to_cancel']				= BOOKACTI_ALLOW_CUSTOMERS_TO_CANCEL; }
	if( ! isset( $default_cancellation_settings['allow_customers_to_reschedule'] ) )		{ $default_cancellation_settings['allow_customers_to_reschedule']		= BOOKACTI_ALLOW_CUSTOMERS_TO_RESCHEDULE; }
	if( ! isset( $default_cancellation_settings['cancellation_min_delay_before_event'] ) )	{ $default_cancellation_settings['cancellation_min_delay_before_event']	= BOOKACTI_CANCELLATION_MIN_DELAY_BEFORE_EVENT; }
	if( ! isset( $default_cancellation_settings['refund_actions_after_cancellation'] ) )	{ $default_cancellation_settings['refund_actions_after_cancellation']	= BOOKACTI_REFUND_ACTIONS_AFTER_CANCELLATION; }
	update_option( 'bookacti_cancellation_settings', $default_cancellation_settings );
	
	$default_general_settings = get_option( 'bookacti_general_settings' );
	if( ! isset( $default_general_settings['booking_method'] ) )			{ $default_general_settings['booking_method']			= BOOKACTI_BOOKING_METHOD; }
	if( ! isset( $default_general_settings['when_events_load'] ) )			{ $default_general_settings['when_events_load']			= BOOKACTI_WHEN_EVENTS_LOAD; }
	if( ! isset( $default_general_settings['started_events_bookable'] ) )	{ $default_general_settings['started_events_bookable']	= BOOKACTI_STARTED_EVENTS_BOOKABLE; }
	if( ! isset( $default_general_settings['default_booking_state'] ) )		{ $default_general_settings['default_booking_state']	= BOOKACTI_DEFAULT_BOOKING_STATE; }
	if( ! isset( $default_general_settings['date_format'] ) )				{ $default_general_settings['date_format']				= BOOKACTI_DATE_FORMAT; }
	if( ! isset( $default_general_settings['timezone'] ) )					{ $default_general_settings['timezone']					= BOOKACTI_TIMEZONE; }
	update_option( 'bookacti_general_settings', $default_general_settings );
	
	
	$default_notifications_settings = get_option( 'bookacti_notifications_settings' );
	if( ! isset( $default_notifications_settings['notifications_from_name'] ) )		{ $default_notifications_settings['notifications_from_name']	= BOOKACTI_NOTIFICATIONS_FROM_NAME; }
	if( ! isset( $default_notifications_settings['notifications_from_email'] ) )	{ $default_notifications_settings['notifications_from_email']	= BOOKACTI_NOTIFICATIONS_FROM_EMAIL; }
	if( ! isset( $default_notifications_settings['notifications_async_email'] ) )	{ $default_notifications_settings['notifications_async_email']	= BOOKACTI_NOTIFICATIONS_ASYNC_EMAIL; }
	update_option( 'bookacti_notifications_settings', $default_notifications_settings );
	
	do_action( 'bookacti_init_settings_value' );
}


/**
 * Reset settings to default values
 * 
 * @version 1.2.0
 */
function bookacti_reset_settings() {
	
	$default_template_settings = array();
	$default_template_settings['default_template_per_user']	= BOOKACTI_DEFAULT_TEMPLATE_PER_USER;
	
	update_option( 'bookacti_template_settings', $default_template_settings );
	
	$default_bookings_settings = array();
	$default_bookings_settings['show_past_events']			= BOOKACTI_SHOW_PAST_EVENTS;
	$default_bookings_settings['allow_templates_filter']	= BOOKACTI_ALLOW_TEMPLATES_FILTER;
	$default_bookings_settings['allow_activities_filter']	= BOOKACTI_ALLOW_ACTIVITIES_FILTER;
	$default_bookings_settings['show_inactive_bookings']	= BOOKACTI_SHOW_INACTIVE_BOOKINGS;
	
	update_option( 'bookacti_bookings_settings', $default_bookings_settings );
	
	$default_cancellation_settings = array();
	$default_cancellation_settings['allow_customers_to_cancel']				= BOOKACTI_ALLOW_CUSTOMERS_TO_CANCEL;
	$default_cancellation_settings['allow_customers_to_reschedule']			= BOOKACTI_ALLOW_CUSTOMERS_TO_RESCHEDULE;
	$default_cancellation_settings['cancellation_min_delay_before_event']	= BOOKACTI_CANCELLATION_MIN_DELAY_BEFORE_EVENT;
	$default_cancellation_settings['refund_actions_after_cancellation']		= BOOKACTI_REFUND_ACTIONS_AFTER_CANCELLATION;
	
	update_option( 'bookacti_cancellation_settings', $default_cancellation_settings );
	
	$default_general_settings = array();
	$default_general_settings['booking_method']				= BOOKACTI_BOOKING_METHOD;
	$default_general_settings['when_events_load']			= BOOKACTI_WHEN_EVENTS_LOAD;
	$default_general_settings['started_events_bookable']	= BOOKACTI_STARTED_EVENTS_BOOKABLE;
	$default_general_settings['default_booking_state']		= BOOKACTI_DEFAULT_BOOKING_STATE;
	$default_general_settings['date_format']				= BOOKACTI_DATE_FORMAT;
	$default_general_settings['timezone']					= BOOKACTI_TIMEZONE;
	
	update_option( 'bookacti_general_settings', $default_general_settings );
	
	$default_notifications_settings = array();
	$default_notifications_settings['notifications_from_name']		= BOOKACTI_NOTIFICATIONS_FROM_NAME;
	$default_notifications_settings['notifications_from_email']		= BOOKACTI_NOTIFICATIONS_FROM_EMAIL;
	$default_notifications_settings['notifications_async_email']	= BOOKACTI_NOTIFICATIONS_ASYNC_EMAIL;
	
	update_option( 'bookacti_notifications_settings', $default_notifications_settings );
	
	do_action( 'bookacti_reset_settings' );
}


/**
 * Delete settings
 * 
 * @version 1.2.0
 */
function bookacti_delete_settings() {
	delete_option( 'bookacti_template_settings' );
	delete_option( 'bookacti_bookings_settings' );
	delete_option( 'bookacti_cancellation_settings' );
	delete_option( 'bookacti_general_settings' );
	delete_option( 'bookacti_notifications_settings' );
	
	do_action( 'bookacti_delete_settings' );
}


/**
 * Get setting value
 * 
 * @version 1.1.0
 * 
 * @param string $setting_page
 * @param string $setting_field
 * @return mixed
 */
function bookacti_get_setting_value( $setting_page, $setting_field, $translate = true ) {
	
	$settings = get_option( $setting_page );
	
	if( ! isset( $settings[ $setting_field ] ) 
	||  ( ! $settings[ $setting_field ] && $settings[ $setting_field ] !== '0' && $settings[ $setting_field ] !== 0 ) ) {
		if( defined( 'BOOKACTI_' . strtoupper( $setting_field ) ) ) {
			$settings[ $setting_field ] = constant( 'BOOKACTI_' . strtoupper( $setting_field ) );
			update_option( $setting_page, $settings );
		} else {
			$settings[ $setting_field ] = false;
		}
	}
	
	if( ! $translate ) {
		return $settings[ $setting_field ];
	}
	
	if( is_string( $settings[ $setting_field ] ) ) {
		$settings[ $setting_field ] = apply_filters( 'bookacti_translate_text', $settings[ $setting_field ] );
	}
		
	return $settings[ $setting_field ];
}


/**
 * Get setting value by user
 * 
 * @version 1.1.0
 * @param string $setting_page
 * @param string $setting_field
 * @param int $user_id
 * @return mixed
 */
function bookacti_get_setting_value_by_user( $setting_page, $setting_field, $user_id = false, $translate = true ) {

	$user_id = $user_id ? $user_id : get_current_user_id();
	$settings = get_option( $setting_page );
	
	if( ! is_array( $settings ) ){
		$settings = array();
	}
	
	if( ! isset( $settings[ $setting_field ] ) || ! is_array( $settings[ $setting_field ] ) ) {
		$settings[ $setting_field ] = array();
	}
	
	if( ! isset( $settings[ $setting_field ][ $user_id ] ) 
	||  ( ! $settings[ $setting_field ] && $settings[ $setting_field ] !== '0' && $settings[ $setting_field ] !== 0 ) ) {
		if( defined( 'BOOKACTI_' . strtoupper( $setting_field ) ) ) {
			$settings[ $setting_field ][ $user_id ] = constant( 'BOOKACTI_' . strtoupper( $setting_field ) );
			update_option( $setting_page, $settings );
		} else {
			$settings[ $setting_field ][ $user_id ] = false;
		}
	}
	
	if( ! $translate ) {
		return $settings[ $setting_field ][ $user_id ];
	}
	
	if( is_string( $settings[ $setting_field ][ $user_id ] ) ) {
		$settings[ $setting_field ][ $user_id ] = apply_filters( 'bookacti_translate_text', $settings[ $setting_field ][ $user_id ] );
	}
		
	return $settings[ $setting_field ][ $user_id ];
}




// SECTION CALLBACKS
function bookacti_settings_section_general_callback() { }
function bookacti_settings_section_cancellation_callback() { }
function bookacti_settings_section_template_callback() { }
function bookacti_settings_section_bookings_callback() { }




//GENERAL SETTINGS 

	/**
	 * Display "Booking method" setting
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_booking_method_callback() {
		
		/* translators: The word 'Calendar' refers to a booking method you have to translate too. Make sure you use the same word for both translation. */
		$tip  = apply_filters( 'bookacti_booking_methods_tip',
				__( "'Calendar': The user will have to pick the event directly on a calendar.", BOOKACTI_PLUGIN_NAME ) );
		
		$license_status = get_option( 'badp_license_status' );
		if( ! $license_status || $license_status !== 'valid' ) {
			$tip .= '<br/>';
			$tip .= sprintf( __( 'Get more display methods with %1$sDisplay Pack%2$s add-on!', BOOKACTI_PLUGIN_NAME ),
							'<a href="https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=landing" target="_blank" >', '</a>');
		}
		
		$args = array(
			'type'		=> 'select',
			'name'		=> 'bookacti_general_settings[booking_method]',
			'id'		=> 'booking_method',
			'options'	=> bookacti_get_available_booking_methods(),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' ),
			'tip'		=> $tip
		);
		bookacti_display_field( $args );
	}

	
	/**
	 * Display "When to load the events?" setting
	 * 
	 * @since 1.1.0
	 * @version 1.2.0
	 */
	function bookacti_settings_field_when_events_load_callback() {
		$args = array(
			'type'		=> 'select',
			'name'		=> 'bookacti_general_settings[when_events_load]',
			'id'		=> 'when_events_load',
			'options'	=> array( 
								'on_page_load' => __( 'On page load', BOOKACTI_PLUGIN_NAME ),
								'after_page_load' => __( 'After page load', BOOKACTI_PLUGIN_NAME )
							),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'when_events_load' ),
			'tip'		=> apply_filters( 'bookacti_when_events_load_tip', __( 'Choose whether you want to load events when the page is loaded (faster) or after.', BOOKACTI_PLUGIN_NAME ) )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display "Can the user book an event that began?" setting
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_started_events_bookable_callback() {
		$args = array(
			'type'	=> 'checkbox',
			'name'	=> 'bookacti_general_settings[started_events_bookable]',
			'id'	=> 'started_events_bookable',
			'value'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' ),
			'tip'	=> __( 'Allow or disallow users to book an event that already began.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display "default booking state" setting
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_default_booking_state_callback() {
		$args = array(
			'type'		=> 'select',
			'name'		=> 'bookacti_general_settings[default_booking_state]',
			'id'		=> 'default_booking_state',
			'options'	=> array( 
								'pending' => __( 'Pending', BOOKACTI_PLUGIN_NAME ),
								'booked' => __( 'Booked', BOOKACTI_PLUGIN_NAME )
							),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' ),
			/* translators: The word 'Calendar' refers to a booking method you have to translate too. Make sure you use the same word for both translation. */
			'tip'		=> __( 'Choose what status a booking should have when a customer complete the booking form.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display Timezone setting
	 * 
	 * @since 1.1.0
	 */
	function bookacti_settings_field_timezone_callback() {
		$regions = array(
			'UTC' => DateTimeZone::UTC,
			'Africa' => DateTimeZone::AFRICA,
			'America' => DateTimeZone::AMERICA,
			'Antarctica' => DateTimeZone::ANTARCTICA,
			'Asia' => DateTimeZone::ASIA,
			'Atlantic' => DateTimeZone::ATLANTIC,
			'Europe' => DateTimeZone::EUROPE,
			'Indian' => DateTimeZone::INDIAN,
			'Pacific' => DateTimeZone::PACIFIC
		);
		
		$timezones = array();
		
		foreach ( $regions as $name => $mask ) {
			$zones = DateTimeZone::listIdentifiers( $mask );
			foreach( $zones as $timezone ) {
				// Lets sample the time there right now
				$time = new DateTime( NULL, new DateTimeZone( $timezone ) );
				// Us dumb Americans can't handle millitary time
				$ampm = $time->format( 'H' ) > 12 ? ' (' . $time->format( 'g:i a' ) . ')' : '';
				// Remove region name and add a sample time
				$label = $name === 'UTC' ? $name : substr( $timezone, strlen( $name ) + 1 );
				$timezones[ $name ][ $timezone ] = $label . ' - ' . $time->format( 'H:i' ) . $ampm;
			}
		}
		
		$selected_timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
		
		// Display selectbox
		echo '<select name="bookacti_general_settings[timezone]" >';
		foreach( $timezones as $region => $list ) {
			echo '<optgroup label="' . $region . '" >';
			foreach( $list as $timezone => $name ) {
				echo '<option value="' . $timezone . '" ' . selected( $selected_timezone, $timezone ) . '>' . $name . '</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
		
		// Display the tip 
		$tip  = __( 'Pick the timezone corresponding to where your business takes place.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
	}
	
	
	/**
	 * Display date format setting
	 * 
	 * @since 1.1.0
	 * @version 1.2.0
	 */
	function bookacti_settings_field_date_format_callback() {
		$link = '<a href="https://momentjs.com/docs/#/displaying/format/" target="_blank" >';
		/* translators: Label of a link to JS moment documentation (format chapter): https://momentjs.com/docs/#/displaying/format/ */
		$link .= __( 'JS moment documentation', BOOKACTI_PLUGIN_NAME );
		$link .= '</a>';
		
		/* translators: %1$s is a link to JS moment documentation */
		$tip = sprintf( __( 'Set the date format displayed on picked events lists. Leave empty to use the default locale-related format. Go to %1$s to know what tag you can use.', BOOKACTI_PLUGIN_NAME ), $link );

		
		$args = array(
			'type'	=> 'text',
			'name'	=> 'bookacti_general_settings[date_format]',
			'id'	=> 'bookacti-settings-date-format',
			'value'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'date_format', false ),
			'tip'	=> $tip
		);
		bookacti_display_field( $args );
	}




// CANCELLATION SETTINGS 

	/**
	 * Activate cancellation for customers
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_activate_cancel_callback() {
		$args = array(
			'type'	=> 'checkbox',
			'name'	=> 'bookacti_cancellation_settings[allow_customers_to_cancel]',
			'id'	=> 'allow_customers_to_cancel',
			'value'	=> bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' ),
			'tip'	=> __( 'Allow or disallow customers to cancel a booking after they order it.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Activate reschedule for customers
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_activate_reschedule_callback() {
		$tip  = __( 'Allow or disallow customers to reschedule a booking after they order it.', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( 'This won\'t apply to group of bookings.', BOOKACTI_PLUGIN_NAME );
		
		$args = array(
			'type'	=> 'checkbox',
			'name'	=> 'bookacti_cancellation_settings[allow_customers_to_reschedule]',
			'id'	=> 'allow_customers_to_reschedule',
			'value'	=> bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' ),
			'tip'	=> $tip
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Minimum delay before event a user can cancel or reschedule a booking
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_cancellation_delay_callback() {
		$args = array(
			'type'		=> 'number',
			'name'		=> 'bookacti_cancellation_settings[cancellation_min_delay_before_event]',
			'id'		=> 'cancellation_min_delay_before_event',
			'options'	=> array( 'min' => 0 ),
			'value'		=> bookacti_get_setting_value( 'bookacti_cancellation_settings', 'cancellation_min_delay_before_event' ),
			'label'		=> ' ' . esc_html__( 'days before the event', BOOKACTI_PLUGIN_NAME ),
			'tip'		=> __( 'Define the delay before the event in which the customer will not be able to cancel his booking no more. Ex: "7": Customers will be able to cancel their booking at least 7 days before the event starts. After that, it will be to late.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Possible actions to take after cancellation needing refund
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_cancellation_refund_actions_callback() {
		
		$actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
		
		if( ! is_array( $actions ) ) {
			$actions = $actions ? array( $actions => 1 ) : array();
		}
		
		$args = array(
			'type'		=> 'checkboxes',
			'name'		=> 'bookacti_cancellation_settings[refund_actions_after_cancellation]',
			'id'		=> 'refund_action_after_cancellation',
			'options'	=> bookacti_get_refund_actions(),
			'value'		=> $actions,
			'tip'		=> __( 'Define the actions a customer will be able to take to be refunded after he cancels a booking.', BOOKACTI_PLUGIN_NAME )
		);
		
		?>
		<div id='bookacti_refund_actions'>
			<input name='bookacti_cancellation_settings[refund_actions_after_cancellation][do_nothing]' 
				type='hidden' 
				value='1'
			/>
			<?php bookacti_display_field( $args ); ?>
		</div>
		<?php
	}
	
	
	
// NOTIFICATIONS SETTINGS 
	
	/**
	* Settings section callback - Notifications (displayed before settings)
	* 
	* @since 1.2.0
	*/
   function bookacti_settings_section_notifications_callback() { 

		// Display a table of configurable emails
	   ?>

		<table class='bookacti-settings-table' >
			<thead>
				<tr>
					<th><?php _ex( 'Active', 'is the notification active', BOOKACTI_PLUGIN_NAME ); ?></th>
					<th><?php _ex( 'Trigger', 'what triggers a notification', BOOKACTI_PLUGIN_NAME ); ?></th>
					<th><?php _ex( 'Sent to', 'who is sent the notification to', BOOKACTI_PLUGIN_NAME ); ?></th>
					<th><?php _e( 'Actions', BOOKACTI_PLUGIN_NAME ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php
			$emails = bookacti_get_emails_default_settings();
			foreach( $emails as $email_id => $email_settings ) {
				// Use saved option if available, else use default
				$description	= $email_settings[ 'description' ] ? $email_settings[ 'description' ] : '';
				$email_settings = get_option( 'bookacti_notifications_settings_email_' . $email_id, $email_settings );
				$active_icon	= $email_settings[ 'active' ] ? 'tick.png' : 'cross.png';
			?>
				<tr>
					<td><img src='<?php echo plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/' . $active_icon; ?>' /></td>
					<td>
						<a href='<?php echo esc_url( '?page=bookacti_settings&tab=notifications&section=email_' . sanitize_title_with_dashes( $email_id ) ); ?>'><?php echo $email_settings[ 'title' ]; ?></a>
						<?php if( $description ) { bookacti_help_tip( $description ); } ?>
					</td>
					<td><?php echo substr( $email_id, 0, 8 ) === 'customer' ? __( 'Customer', BOOKACTI_PLUGIN_NAME ) : implode( ', ', $email_settings[ 'to' ] ); ?></td>
					<td>
						<a href='<?php echo esc_url( '?page=bookacti_settings&tab=notifications&section=email_' . sanitize_title_with_dashes( $email_id ) ); ?>' >
							<img src='<?php echo plugins_url() . '/' . BOOKACTI_PLUGIN_NAME; ?>/img/gear.png' />
						</a>
					</td>
				</tr>
			<?php	
			}
		?>
			</tbody>
		</table>
	   <?php
   }
	
	
	/**
	 * Notification from name setting field
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_notifications_from_name_callback() {
		$args = array(
			'type'	=> 'text',
			'name'	=> 'bookacti_notifications_settings[notifications_from_name]',
			'id'	=> 'notifications_from_name',
			'value'	=> bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_name' ),
			'tip'	=> __( 'How the sender name appears in outgoing emails.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Notification from email setting field
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_notifications_from_email_callback() {
		$args = array(
			'type'	=> 'text',
			'name'	=> 'bookacti_notifications_settings[notifications_from_email]',
			'id'	=> 'notifications_from_email',
			'value'	=> bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' ),
			'tip'	=> __( 'How the sender email address appears in outgoing emails.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Notification async email setting field
	 * 
	 * @version 1.2.0
	 */
	function bookacti_settings_field_notifications_async_email_callback() {
		$args = array(
			'type'	=> 'checkbox',
			'name'	=> 'bookacti_notifications_settings[notifications_async_email]',
			'id'	=> 'notifications_async_email',
			'value'	=> bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async_email' ),
			'tip'	=> __( 'Whether to send the email asynchronously. If enabled, emails will be sent the next time any page of this website is loaded. No one will have to wait any longer. Else, the loadings will last until emails are sent.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}



// MESSAGES SETTINGS
	/**
	 * Settings section callback - Messages (displayed before settings)
	 * 
	 * @since 1.2.0
	 */
	function bookacti_settings_section_messages_callback() {
	?>
		<p>
			<?php _e( 'Edit messages used in the following situations.', BOOKACTI_PLUGIN_NAME ); ?>
		</p>
	<?php
	}
	
	
	/**
	 * Get all default messages
	 * 
	 * @since 1.2.0
	 */
	function bookacti_get_default_messages() {
		$messages = array(
			'calendar_title' => array(
				'value'			=> __( 'Pick an event on the calendar:', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'Instructions displayed before the calendar.', BOOKACTI_PLUGIN_NAME )
			),
			'booking_success' => array(
				'value'			=> __( 'Your event has been booked successfully!', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'When a reservation has been successfully registered.', BOOKACTI_PLUGIN_NAME )
			),
			'booking_form_submit_button' => array(
				'value'			=> __( 'Book', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'Submit button label.', BOOKACTI_PLUGIN_NAME )
			),
		);
		
		return apply_filters( 'bookacti_default_messages', $messages );
	}
	
	
	/**
	 * Get all custom messages
	 * 
	 * @since 1.2.0
	 * @param boolean $raw Whether to retrieve the raw value from database or the option parsed through get_option
	 * @return array
	 */
	function bookacti_get_messages( $raw = false ) {
		
		// Get raw value from database
		if( $raw ) {
			$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
			if( isset( $alloptions[ 'bookacti_messages_settings' ] ) ) {
				$saved_messages	= maybe_unserialize( $alloptions[ 'bookacti_messages_settings' ] );
			}
		} 

		// Else, get email settings through a normal get_option
		else {
			$saved_messages		= get_option( 'bookacti_messages_settings' );
		}
		
		$default_messages = bookacti_get_default_messages();
		$messages = $default_messages;
		
		if( $saved_messages ) {
			foreach( $default_messages as $message_id => $message ) {
				if( isset( $saved_messages[ $message_id ] ) ) {
					$messages[ $message_id ][ 'value' ] = $saved_messages[ $message_id ];
				}
			}
		}
		
		return apply_filters( 'bookacti_messages', $messages );
	}
	
	
	/**
	 * Get a custom message by id
	 * 
	 * @since 1.2.0
	 * @param string $message_id
	 * @param boolean $raw Whether to retrieve the raw value from database or the option parsed through get_option
	 * @return string
	 */
	function bookacti_get_message( $message_id, $raw = false ) {
		$messages = bookacti_get_messages( $raw );
		return $messages[ $message_id ] ? $messages[ $message_id ][ 'value' ] : '';
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


/**
 * Get Booking Activities admin screen ids
 */
function bookacti_get_screen_ids() {
	$screens = array(
		'toplevel_page_booking-activities',
		'booking-activities_page_bookacti_calendars',
		'booking-activities_page_bookacti_bookings',
		'booking-activities_page_bookacti_settings'
	);
	
	return apply_filters( 'bookacti_screen_ids', $screens );
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