<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get default settings values
 * @since 1.3.0 (was bookacti_define_default_settings_constants)
 * @version 1.12.4
 */
function bookacti_get_default_settings() {
	$date = new DateTime(); 
	$tz = $date->getTimezone()->getName();
	
	$default = array(
		'when_events_load'						=> 'after_page_load',
		'event_load_interval'					=> 92,
		'started_events_bookable'				=> false,
		'started_groups_bookable'				=> false,
		'default_booking_state'					=> 'pending',
		'default_payment_status'				=> 'none',
		'timezone'								=> $tz,
		'default_calendar_view_threshold'		=> 640,
		'display_private_columns'				=> 0,
		'delete_data_on_uninstall'				=> 0,
		'allow_customers_to_cancel'				=> true,
		'allow_customers_to_reschedule'			=> true,
		'booking_changes_deadline'				=> 604800, // 7 days
		'refund_actions_after_cancellation'		=> array(),
		'notifications_from_name'				=> wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'notifications_from_email'				=> get_bloginfo( 'admin_email' ),
		'notifications_async'					=> true,
		'calendar_localization'					=> 'default'
	);
	
	return apply_filters( 'bookacti_default_settings', $default );
}


/**
 * Delete settings
 * @version 1.12.0
 */
function bookacti_delete_settings() {
	delete_option( 'bookacti_version' );
	delete_option( 'bookacti_db_version' );
	delete_option( 'bookacti_template_settings' ); // Deprecated
	delete_option( 'bookacti_bookings_settings' ); // Deprecated
	delete_option( 'bookacti_general_settings' );
	delete_option( 'bookacti_cancellation_settings' );
	delete_option( 'bookacti_notifications_settings' );
	delete_option( 'bookacti_messages_settings' );
	delete_option( 'bookacti_archive_secret_key' ); // Deprecated
	delete_option( 'bookacti_latest_emails_sent' );
	
	do_action( 'bookacti_delete_settings' );
}


/**
 * Get setting value
 * @version 1.14.0
 * @param string $setting_group
 * @param string $setting_name
 * @param boolean $raw
 * @return mixed|null
 */
function bookacti_get_setting_value( $setting_group, $setting_name, $raw = false ) {
	// Get raw value from database
	$settings = array();
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		if( isset( $alloptions[ $setting_group ] ) ) {
			$settings = maybe_unserialize( $alloptions[ $setting_group ] );
		}
	}

	// Else, get message settings through a normal get_option
	else {
		$settings = get_option( $setting_group );
	}
	if( ! is_array( $settings ) ) { $settings = array(); }
	
	$setting_value = isset( $settings[ $setting_name ] ) ? $settings[ $setting_name ] : null;
	
	if( ! isset( $settings[ $setting_name ] ) ) {
		$default = bookacti_get_default_settings();
		if( isset( $default[ $setting_name ] ) ) {
			$setting_value = maybe_unserialize( $default[ $setting_name ] );
		}
	}
	
	return apply_filters( 'bookacti_get_setting_value', $setting_value, $setting_group, $setting_name, $raw );
}




// SECTION CALLBACKS
function bookacti_settings_section_general_callback() {}
function bookacti_settings_section_template_callback() {}
function bookacti_settings_section_bookings_callback() {}


/**
 * Display a description in the Licenses settings tab
 * @version 1.9.0 (was bookacti_display_licenses_settings_description)
 */
function bookacti_settings_section_licenses_callback() {
	$active_add_ons = bookacti_get_active_add_ons();
	if( ! $active_add_ons ) { 
		?>
		<div class='bookacti-licenses-settings-description'>
			<p><?php esc_html_e( 'Here you will be able to activate your add-ons license keys.', 'booking-activities' ); ?></p>
			<strong>
				<?php 
				/* translators: %s is a link to "Booking Activities add-ons" (link label) shop */
				echo sprintf( esc_html__( 'Look at %s.', 'booking-activities' ), ' <a href="https://booking-activities.fr/en/add-ons/" target="_blank">' . esc_html__( 'Booking Activities add-ons', 'booking-activities' ) . '</a>' );
				?>
			</strong>
		</div>
		<?php 
	}
	
	if( bookacti_is_plugin_active( 'ba-licenses-and-updates/ba-licenses-and-updates.php' ) ) { return; }
	
	if( $active_add_ons ) {
		$active_add_ons_titles = array();
		foreach( $active_add_ons as $prefix => $add_on_data ) {
			$active_add_ons_titles[] = $add_on_data[ 'title' ];
		}
		?>
		<div class='bookacti-licenses-settings-description'>
			<p>
				<em><?php esc_html_e( 'The following add-ons are installed on your site:', 'booking-activities' ); ?></em>
				<strong><?php echo implode( '</strong>, <strong>', $active_add_ons_titles ); ?></strong>
			</p>
			<h3>
				<?php 
				/* translators: %s is a link to download "Licenses and Updates" (link label) add-on */
				echo sprintf( esc_html__( 'Please install the "%s" add-on in order to activate your license keys.', 'booking-activities' ), '<a href="https://booking-activities.fr/wp-content/uploads/downloads/public/ba-licenses-and-updates.zip">Licenses and Updates</a>' ); ?>
			</h3>
		</div>
		<?php
	}
}


/**
 * Settings section callback - Cancellation settings (displayed before settings)
 * @version 1.8.7
 */
function bookacti_settings_section_cancellation_callback() { 
?>
	<div class='bookacti-sos'>
		<?php esc_html_e( 'The customers will be able to cancel, reschedule or request a refund from their booking list.', 'booking-activities' ); ?>
		<span class='dashicons dashicons-sos' data-label='<?php echo esc_html_x( 'Help', 'button label', 'booking-activities' ); ?>'></span>
		<span>
			<ul class='bookacti-help-list'>
				<li><?php /* translators: %s = [bookingactivities_list] */ echo sprintf( esc_html__( 'The customers\' booking list can be displayed thanks to the %s shortcode', 'booking-activities' ), '<code>[bookingactivities_list]</code>' ); ?> (<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-customers-bookings-list-on-the-frontend/' target='_blank'><?php esc_html_e( 'documentation', 'booking-activities' ); ?></a>)
				<li><?php esc_html_e( 'The customers will need to be logged in to see their booking list, so it is recommended to display a login form on the same page', 'booking-activities' ); ?> (<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-a-login-registration-form/' target='_blank'><?php esc_html_e( 'documentation', 'booking-activities' ); ?></a>)
				<?php do_action( 'bookacti_email_cancellation_help_after' ); ?>
			</ul>
		</span>
	</div>
<?php
}




// BOOKINGS SETTINGS

/**
 * Display Booking page options in screen options area
 * 
 * @since 1.3.0
 */
function bookacti_display_bookings_screen_options() {
	$screen = get_current_screen();

	// Don't do anything if we are not on the booking page
	if( ! is_object( $screen ) || $screen->id != 'booking-activities_page_bookacti_bookings' ) { return; }

	// Bookings per page
	add_screen_option( 'per_page', array(
		'label' => __( 'Bookings per page:', 'booking-activities' ),
		'default' => 20,
		'option' => 'bookacti_bookings_per_page'
	));
}


// FORMS SETTINGS

/**
 * Display Form page options in screen options area
 * 
 * @since 1.5.0
 */
function bookacti_display_forms_screen_options() {
	$screen = get_current_screen();

	// Don't do anything if we are not on the booking page
	if( ! is_object( $screen ) || $screen->id != 'booking-activities_page_bookacti_forms' ) { return; }

	if( ! empty( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) {
		// Layout columns number
		add_screen_option( 'layout_columns', array( 
			'max' => 2, 
			'default' => 2 
		));
	} else {
		// Bookings per page
		add_screen_option( 'per_page', array(
			'label' => __( 'Forms per page:', 'booking-activities' ),
			'default' => 20,
			'option' => 'bookacti_forms_per_page'
		));
	}
}


// GENERAL SETTINGS

/**
 * Display Timezone setting
 * @since 1.1.0
 * @version 1.4.0
 */
function bookacti_settings_field_timezone_callback() {
	$regions = array(
		'UTC'        => DateTimeZone::UTC,
		'Africa'     => DateTimeZone::AFRICA,
		'America'    => DateTimeZone::AMERICA,
		'Antarctica' => DateTimeZone::ANTARCTICA,
		'Arctic'     => DateTimeZone::ARCTIC,
		'Asia'       => DateTimeZone::ASIA,
		'Atlantic'   => DateTimeZone::ATLANTIC,
		'Australia'  => DateTimeZone::AUSTRALIA,
		'Europe'     => DateTimeZone::EUROPE,
		'Indian'     => DateTimeZone::INDIAN,
		'Pacific'    => DateTimeZone::PACIFIC
	);

	$timezones = array();

	foreach( $regions as $name => $mask ) {
		$zones = DateTimeZone::listIdentifiers( $mask );
		foreach( $zones as $timezone ) {
			$time = new DateTime( NULL, new DateTimeZone( $timezone ) );
			$ampm = $time->format( 'H' ) > 12 ? ' (' . $time->format( 'g:i a' ) . ')' : '';
			$label = $name === 'UTC' ? $name : substr( $timezone, strlen( $name ) + 1 );
			$timezones[ $name ][ $timezone ] = $label . ' - ' . $time->format( 'H:i' ) . $ampm;
		}
	}

	$selected_timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );

	// Display selectbox
	echo '<select name="bookacti_general_settings[timezone]" class="bookacti-select2-no-ajax">';
	foreach( $timezones as $region => $list ) {
		echo '<optgroup label="' . $region . '" >';
		foreach( $list as $timezone => $name ) {
			echo '<option value="' . $timezone . '" ' . selected( $selected_timezone, $timezone ) . '>' . $name . '</option>';
		}
		echo '</optgroup>';
	}
	echo '</select>';

	// Display the help tip 
	$tip = __( 'Pick the timezone corresponding to where your business takes place.', 'booking-activities' );
	bookacti_help_tip( $tip );
}


/**
 * Display "Calendar localization" setting
 * @since 1.7.16
 * @version 1.14.0
 */
function bookacti_settings_field_calendar_localization_callback() {
	$args = array(
		'type'    => 'select',
		'name'    => 'bookacti_general_settings[calendar_localization]',
		'id'      => 'calendar_localization',
		'options' => array( 
						'default' => esc_html__( 'Based on the Site Language only (default)', 'booking-activities' ),
						/* translators: %s is a comma separated list of option name */
						'wp_settings' => sprintf( esc_html__( 'Based on more WP settings (%s)', 'booking-activities' ), implode( ', ', array( __( 'Site Language' ), __( 'Time Format' ), __( 'Week Starts On' ) ) ) )
					),
		'value'   => bookacti_get_setting_value( 'bookacti_general_settings', 'calendar_localization' ),
		/* translators: %s = "Site Language (WordPress Settings > General)" with a link to WP general settings */
		'tip'     => sprintf( esc_html__( 'Many elements of the calendar are localized according to your %s: time format (12 or 24-hour), date format, first day of the week, text in buttons, names of the days and the months, and RTL display.', 'booking-activities' ), __( 'Site Language' ) . ' (<a href="' . admin_url( 'options-general.php' ) . '">' . esc_html__( 'WordPress Settings > General', 'booking-activities' ) . '</a>)' )
	);
	bookacti_display_field( $args );
}


/**
 * Display "When to load the events?" setting
 * @since 1.1.0
 * @version 1.9.0
 */
function bookacti_settings_field_when_events_load_callback() {
	$args = array(
		'type'		=> 'select',
		'name'		=> 'bookacti_general_settings[when_events_load]',
		'id'		=> 'when_events_load',
		'options'	=> array( 
							'on_page_load' => esc_html__( 'On page load', 'booking-activities' ),
							'after_page_load' => esc_html__( 'After page load', 'booking-activities' )
						),
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'when_events_load' ),
		'tip'		=> apply_filters( 'bookacti_when_events_load_tip', esc_html__( 'Choose whether you want to load events when the page is loaded or after.', 'booking-activities' ) )
	);
	bookacti_display_field( $args );
?>
	<div class='bookacti-backend-settings-only-notice bookacti-warning' style='margin-top: 10px;'>
		<span class='dashicons dashicons-warning'></span>
		<span>
			<?php 
				/* translators: %s = "After page load" (it's an option name) */
				echo sprintf( esc_html__( 'You must choose "%s" if you are using a caching sytem (via a plugin, your webhost, a CDN...).', 'booking-activities' ), esc_html__( 'After page load', 'booking-activities' ) );
			?>
		</span>
	</div>
<?php
}


/**
 * Display Event Load Interval setting
 * @since 1.2.2
 * @version 1.12.2
 */
function bookacti_settings_field_event_load_interval_callback() {
	$args = array(
		'type'		=> 'number',
		'name'		=> 'bookacti_general_settings[event_load_interval]',
		'id'		=> 'event_load_interval',
		'options'	=> array( 'min' => 14 ),
		'label'		=> ' ' . esc_html__( 'days', 'booking-activities' ),
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ),
		'tip'		=> __( 'Events are loaded at intervals as the user navigates the calendar. E.g.: If you set "92", events will be loaded for 92 days. When the user reaches the 92nd day on the calendar, events of the next 92 days will be loaded.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Display "Is the user allowed to book an event that has already begun?" setting
 * 
 * @version 1.4.0
 */
function bookacti_settings_field_started_events_bookable_callback() {
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_general_settings[started_events_bookable]',
		'id'	=> 'started_events_bookable',
		'value'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' ),
		'tip'	=> __( 'Allow or disallow users to book an event that has already begun.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Display "Is the user allowed to book a group of events that has already begun?" setting
 * 
 * @since 1.4.0
 */
function bookacti_settings_field_started_groups_bookable_callback() {
	$tip = __( 'Allow or disallow users to book a group of events that has already begun.', 'booking-activities' );
	$tip .= '<br/>' . __( 'This parameter applies to all groups of events. An group category-specific parameter is available in group category settings, in the calendar editor.', 'booking-activities' );

	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_general_settings[started_groups_bookable]',
		'id'	=> 'started_groups_bookable',
		'value'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'started_groups_bookable' ),
		'tip'	=> $tip
	);
	bookacti_display_field( $args );
}


/**
 * Display "default booking state" setting
 * @version 1.6.0
 */
function bookacti_settings_field_default_booking_state_callback() {
	$booking_state_labels = bookacti_get_booking_state_labels();
	$allowed_booking_states = array( 'pending', 'booked' );
	$options = array();
	foreach( $allowed_booking_states as $state_key ) {
		$options[ $state_key ] = ! empty( $booking_state_labels[ $state_key ][ 'label' ] ) ? $booking_state_labels[ $state_key ][ 'label' ] : $state_key;
	}

	$args = array(
		'type'		=> 'select',
		'name'		=> 'bookacti_general_settings[default_booking_state]',
		'id'		=> 'default_booking_state',
		'options'	=> $options,
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' ),
		'tip'		=> __( 'Choose what status a booking should have when a customer complete the booking form.', 'booking-activities' )
					. '<br/>' . __( 'This option has no effect on bookings made with WooCommerce.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Display "default payment status" setting
 * @since 1.3.0
 * @version 1.7.18
 */
function bookacti_settings_field_default_payment_status_callback() {
	$payment_status = bookacti_get_payment_status_labels();
	$payment_status_array = array();
	foreach( $payment_status as $payment_status_id => $payment_status_data ) {
		$payment_status_array[ esc_attr( $payment_status_id ) ] = esc_html( $payment_status_data[ 'label' ] );
	}
	bookacti_display_field( array(
		'type'		=> 'select',
		'name'		=> 'bookacti_general_settings[default_payment_status]',
		'id'		=> 'default_payment_status',
		'options'	=> $payment_status_array,
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' ),
		'tip'		=> esc_html__( 'Choose what payment status a booking should have when a customer complete the booking form.', 'booking-activities' )
					. '<br/>' . esc_html__( 'This option has no effect on bookings made with WooCommerce.', 'booking-activities' )
	));
}


/**
 * Display "Calendar default view: width threshold" setting
 * @since 1.5.0
 * @version 1.9.0
 */
function bookacti_settings_field_default_calendar_view_threshold_callback() {
	$addon_link = '<a href="https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=settings" target="_blank" >Display Pack</a>';

	$args = array(
		'type'		=> 'number',
		'name'		=> 'bookacti_general_settings[default_calendar_view_threshold]',
		'id'		=> 'default_calendar_view_threshold',
		'options'	=> array( 'min' => 0, 'step' => 1 ),
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_calendar_view_threshold' ),
		'label'		=> esc_html_x( 'px', 'pixel short', 'booking-activities' ),
		'tip'		=> esc_html__( 'The day view will be displayed by default if the calendar width is under that threshold when it is loaded. Else, it will be the week view.', 'booking-activities' )
					/* translators: %s is the add-on name */
					. '<br/>' . sprintf( esc_html__( 'Get more views and granularity with the %s add-on!', 'booking-activities' ), $addon_link )
	);
	bookacti_display_field( $args );
}


/**
 * Display "Booking list private columns" setting
 * @since 1.8.0
 */
function bookacti_settings_field_display_private_columns_callback() {
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_general_settings[display_private_columns]',
		'id'	=> 'display_private_columns',
		'value'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'display_private_columns' ),
		'tip'	=> esc_html__( 'Allow to display private data in frontend booking lists (customers ids, names, emails, phones, roles...).', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Display "Delete data while uninstalling" setting
 * @since 1.6.0
 */
function bookacti_settings_field_delete_data_on_uninstall_callback() {
	$args = array(
		'type'		=> 'checkbox',
		'name'		=> 'bookacti_general_settings[delete_data_on_uninstall]',
		'id'		=> 'delete_data_on_uninstall',
		'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'delete_data_on_uninstall' ),
		'tip'		=> esc_html__( 'Delete all Booking Activities data (calendars, forms, bookings, settings...) when you uninstall Booking Activities.', 'booking-activities' )
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
		'tip'	=> __( 'Allow or disallow customers to cancel a booking after they order it.', 'booking-activities' )
				. '<br/>' . __( 'This option has no effect for administrators.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Activate reschedule for customers
 * 
 * @version 1.4.0
 */
function bookacti_settings_field_activate_reschedule_callback() {
	$tip  = __( 'Allow or disallow customers to reschedule a booking after they order it.', 'booking-activities' );
	$tip .= '<br/>' . __( "This won't apply to groups of bookings.", 'booking-activities' );
	$tip .= '<br/>' . __( 'This option has no effect for administrators.', 'booking-activities' );

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
 * @version 1.8.0
 */
function bookacti_settings_field_cancellation_delay_callback() {
	$args = array(
		'type'	=> 'duration',
		'name'	=> 'bookacti_cancellation_settings[booking_changes_deadline]',
		'id'	=> 'booking_changes_deadline',
		'value'	=> floatval( bookacti_get_setting_value( 'bookacti_cancellation_settings', 'booking_changes_deadline' ) ),
		'tip'	=> esc_html__( 'Define when a customer can change a booking (cancel, reschedule). E.g.: "2 days 5 hours 30 minutes", your customers will be able to change the bookings starting in 2 days, 5 hours and 30 minutes at least. They won\'t be allowed to cancel a booking starting tomorrow for example.', 'booking-activities' )
				. '<br/>' . esc_html__( 'This option has no effect for administrators.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Possible actions to take after cancellation needing refund
 * @version 1.9.0
 */
function bookacti_settings_field_cancellation_refund_actions_callback() {

	$actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );

	if( is_string( $actions ) ) { $actions = array( $actions ); }
	if( ! is_array( $actions ) ) { $actions = array(); }

	$args = array(
		'type'		=> 'checkboxes',
		'name'		=> 'bookacti_cancellation_settings[refund_actions_after_cancellation]',
		'id'		=> 'refund_action_after_cancellation',
		'options'	=> bookacti_get_refund_actions(),
		'value'		=> $actions,
		'tip'		=> esc_html__( 'Define the actions a customer will be able to take to be refunded after he / she cancels a booking.', 'booking-activities' )
					. '<br/>' . esc_html__( 'This option has no effect for administrators.', 'booking-activities' )
	);

	?>
	<div id='bookacti_refund_actions'>
		<?php bookacti_display_field( $args ); ?>
	</div>
	<?php
}



// NOTIFICATIONS SETTINGS 

/**
 * Settings section callback - Notifications - General settings (displayed before settings)
 * @since 1.2.1 (was bookacti_settings_section_notifications_callback in 1.2.0)
 * @version 1.8.6
 */
function bookacti_settings_section_notifications_general_callback() { 
	// Display a table of configurable notifications
	// Set up booking list columns
	$columns_titles = bookacti_get_notifications_list_columns_titles();
	
	do_action( 'bookacti_before_notifications_table' );
	
	?>
	<div id='bookacti-notifications-list-container' class='bookacti-custom-scrollbar'>
		<table class='bookacti-settings-table' id='bookacti-notifications-list' >
			<thead>
				<tr>
				<?php foreach( $columns_titles as $column ) { ?>
					<th id='bookacti-notifications-list-column-<?php echo sanitize_title_with_dashes( $column[ 'id' ] ); ?>' >
						<?php echo esc_html( $column[ 'title' ] ); ?>
					</th>
				<?php } ?>
				</tr>
			</thead>
			<tbody>
		<?php
			// Get notifications IDs and their settings
			$notifications_ids = array();
			$notifications_settings = array();
			$notifications_ids = array_keys( bookacti_get_notifications_default_settings() );
			foreach( $notifications_ids as $notification_id ) {
				$notifications_settings[ $notification_id ] = bookacti_get_notification_settings( $notification_id, false );
			}
			
			// Sort notifications: admin's first, customer's next
			$notifications_ids_admin = array();
			$notifications_ids_customer = array();
			foreach( $notifications_ids as $i => $notification_id ) {
				$added = false;
				if( substr( $notification_id, 0, 9 ) === 'customer_' ) { $notifications_ids_customer[] = $notification_id; $added = true; }
				else if( substr( $notification_id, 0, 6 ) === 'admin_' ) { $notifications_ids_admin[] = $notification_id; $added = true; } 
				if( $added ) { unset( $notifications_ids[ $i ] ); }
			}
			$notifications_ids_sorted = apply_filters( 'bookacti_notifications_list_order', array_values( array_merge( $notifications_ids_admin, $notifications_ids_customer, $notifications_ids ) ), $notifications_settings );

			foreach( $notifications_ids_sorted as $notification_id ) {
				$notification_settings = isset( $notifications_settings[ $notification_id ] ) ? $notifications_settings[ $notification_id ] : array();
				$active_icon = $notification_settings[ 'active' ] ? 'dashicons-yes' : 'dashicons-no';
				$description = $notification_settings[ 'description' ] ? bookacti_help_tip( $notification_settings[ 'description' ], false ) : '';

				$columns_values = apply_filters( 'bookacti_notifications_list_columns_values', array(
					'active'		=> '<span class="dashicons ' . $active_icon . '"></span>',
					'title'			=> '<a href="' . esc_url( '?page=bookacti_settings&tab=notifications&notification_id=' . sanitize_title_with_dashes( $notification_id ) ) . '" >' . esc_html( $notification_settings[ 'title' ] ) . '</a>' . $description,
					'recipients'	=> substr( $notification_id, 0, 8 ) === 'customer' ? esc_html__( 'Customer', 'booking-activities' ) : esc_html__( 'Administrator', 'booking-activities' ),
					'actions'		=> '<a href="' . esc_url( '?page=bookacti_settings&tab=notifications&notification_id=' . sanitize_title_with_dashes( $notification_id ) ) . '" title="' . esc_attr__( 'Edit this notification', 'booking-activities' ) . '" class="button button-secondary" >' . esc_html__( 'Settings', 'booking-activities' ) . '</a>'
				), $notification_settings, $notification_id );

				?>
				<tr id='bookacti-notification-row-<?php echo $notification_id; ?>' class='bookacti-notification-row'>
				<?php foreach( $columns_titles as $column ) { ?>
					<td class='bookacti-notifications-list-column-value-<?php echo sanitize_title_with_dashes( $column[ 'id' ] ); ?>' >
					<?php
						if( isset( $columns_values[ $column[ 'id' ] ] ) ) { 
							echo $columns_values[ $column[ 'id' ] ];
						}
					?>
					</td>
				<?php } ?>
				</tr>
			<?php } 
			$is_plugin_active = bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
			if( ! $is_plugin_active ) {
				$addon_link = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >Notification Pack</a>';
				$columns_values = array(
					'active'		=> '<span class="dashicons dashicons-no"></span>',
					'title'			=> '<strong>' . esc_html__( '1 hour before / after a booked event', 'booking-activities' ) . '</strong>' 
									/* translators: %1$s is the placeholder for Notification Pack add-on link */
									.  bookacti_help_tip( sprintf( esc_html__( 'You can send automated notifications with the %1$s add-on before or after booked events (you can set the desired delay). This add-on also allows you to send all notifications through SMS and Push.', 'booking-activities' ), $addon_link ), false )
									.  '<br/><small>' . esc_html__( 'Set up automated notifications (booking reminders, request a feedback, marketing automation...)', 'booking-activities' ) . '</small>',
					'recipients'	=> esc_html__( 'Customer', 'booking-activities' ) . ' / ' . esc_html__( 'Administrator', 'booking-activities' ),
					'actions'		=> "<a href='https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list' class='button' target='_blank' >" . esc_html__( 'Learn more', 'booking-activities' ) . "</a>"
				);

				?>
				<tr id='bookacti-notification-row-customer_reminder' class='bookacti-notification-row'>
				<?php foreach( $columns_titles as $column ) { ?>
					<td class='bookacti-notifications-list-column-value-<?php echo sanitize_title_with_dashes( $column[ 'id' ] ); ?>' >
					<?php
						if( isset( $columns_values[ $column[ 'id' ] ] ) ) { 
							echo $columns_values[ $column[ 'id' ] ];
						}
					?>
					</td>
				<?php } ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
	<?php
	bookacti_display_banp_promo();
	
	do_action( 'bookacti_after_notifications_table' );
}


/**
 * Get notification list columns titles
 * @since 1.8.5
 * @return array
 */
function bookacti_get_notifications_list_columns_titles() {
	$columns_titles = apply_filters( 'bookacti_notifications_list_columns_titles', array(
		10	=> array( 'id' => 'active',		'title' => esc_html_x( 'Active', 'is the notification active', 'booking-activities' ) ),
		20	=> array( 'id' => 'title',		'title' => esc_html_x( 'Trigger', 'what triggers a notification', 'booking-activities' ) ),
		30	=> array( 'id' => 'recipients',	'title' => esc_html_x( 'Send to', 'who the notification is sent to', 'booking-activities' ) ),
		100 => array( 'id' => 'actions',	'title' => esc_html__( 'Actions', 'booking-activities' ) )
	) );

	// Order columns
	ksort( $columns_titles, SORT_NUMERIC );
	
	return $columns_titles;
}


/**
 * Settings section callback - Notifications - Email settings (displayed before settings)
 * @since 1.2.1
 * @version 1.8.0
 */
function bookacti_settings_section_notifications_email_callback() {
?>
	<div class='bookacti-sos'>
		<?php esc_html_e( 'Click on the problem you are experiencing to try to fix it:', 'booking-activities' ); ?>
		<span class='dashicons dashicons-sos' data-label='<?php echo esc_html_x( 'Help', 'button label', 'booking-activities' ); ?>'></span>
		<span>
			<ul class='bookacti-help-list'>
				<li><a href='https://booking-activities.fr/en/faq/i-do-not-receive-the-notifications/' target='_blank'><?php esc_html_e( 'I don\'t receive the notifications', 'booking-activities' ); ?></a>
				<?php do_action( 'bookacti_email_notifications_help_after' ); ?>
			</ul>
		</span>
	</div>
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
		'tip'	=> __( 'How the sender name appears in outgoing emails.', 'booking-activities' )
	);
	bookacti_display_field( $args );
}


/**
 * Notification from email setting field
 * @version 1.14.0
 * @global PHPMailer\PHPMailer\PHPMailer $phpmailer
 */
function bookacti_settings_field_notifications_from_email_callback() {
	// Display a message: The email address must be known by the SMTP server used
	global $phpmailer;
	$smtp_host = 'localhost';
	if( $phpmailer ) { 
		do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );
		if( ! empty( $phpmailer->Host ) ) { $smtp_host = $phpmailer->Host; }
	}
	
	$message = esc_html__( 'You must create an email address at your webhost and use it here.', 'booking-activities' );
	if( $smtp_host !== 'localhost' ) {
		$message = esc_html__( 'You must use your SMTP server\'s email address.', 'booking-activities' ) . ' (' . $smtp_host . ')';
	}
	
	$args = array(
		'type'	=> 'text',
		'name'	=> 'bookacti_notifications_settings[notifications_from_email]',
		'id'	=> 'notifications_from_email',
		'value'	=> bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' ),
		'tip'	=> esc_html__( 'The sender email address.', 'booking-activities' ) . ' ' . $message
	);
	bookacti_display_field( $args );
?>
	<br/>
	<span class='bookacti-warning bookacti-from-email-warning'>
		<span class='dashicons dashicons-warning'></span>
		<span><em><?php echo $message ?></em></span>
	</span>
<?php
}


/**
 * Notification async setting field
 * 
 * @version 1.2.1 (was bookacti_settings_field_notifications_async_email_callback in 1.2.0)
 */
function bookacti_settings_field_notifications_async_callback() {
	$args = array(
		'type'	=> 'checkbox',
		'name'	=> 'bookacti_notifications_settings[notifications_async]',
		'id'	=> 'notifications_async',
		'value'	=> bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ),
		'tip'	=> __( 'Whether to send notifications asynchronously. If enabled, notifications will be sent the next time any page of this website is loaded. No one will have to wait any longer. Else, the loadings will last until notifications are sent.', 'booking-activities' )
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
		<?php _e( 'Edit messages used in the following situations.', 'booking-activities' ); ?>
	</p>
<?php
}


/**
 * Get all default messages
 * @since 1.2.0
 * @version 1.12.3
 */
function bookacti_get_default_messages() {
	$wp_date_format_link = '<a href="https://wordpress.org/support/article/formatting-date-and-time/" target="_blank" >' .  esc_html__( 'Formatting Date and Time', 'booking-activities' ) . '</a>';

	$messages = array(
		'date_format_long' => array(
			/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://wordpress.org/support/article/formatting-date-and-time/ */
			'value'			=> esc_html__( 'l, F jS, Y g:i A', 'booking-activities' ),
			/* translators: %1$s si a link to wp date_i18n documentation */
			'description'	=> sprintf( esc_html__( 'Complete date and time format. See the tags here: %1$s.', 'booking-activities' ), $wp_date_format_link )
		),
		'date_format_short' => array(
			/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://wordpress.org/support/article/formatting-date-and-time/ */
			'value'			=> esc_html__( 'M, jS - g:i A', 'booking-activities' ),
			/* translators: %1$s si a link to wp date_i18n documentation */
			'description'	=> sprintf( esc_html__( 'Short date and time format. See the tags here: %1$s.', 'booking-activities' ), $wp_date_format_link )
		),
		'time_format' => array(
			/* translators: Time format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://wordpress.org/support/article/formatting-date-and-time/ */
			'value'			=> esc_html__( 'g:i A', 'booking-activities' ),
			/* translators: %1$s si a link to wp date_i18n documentation */
			'description'	=> sprintf( esc_html__( 'Time format. It will be used when a time is displayed alone. See the tags here: %1$s.', 'booking-activities' ), $wp_date_format_link )
		),
		'dates_separator' => array(
			'value'			=> '&nbsp;&rarr;&nbsp;',
			'description'	=> esc_html__( 'Separator between two dates. Write "&amp;nbsp;" to make a space.', 'booking-activities' )
		),
		'date_time_separator' => array(
			'value'			=> '&nbsp;&rarr;&nbsp;',
			'description'	=> esc_html__( 'Separator between a date and a time. Write "&amp;nbsp;" to make a space.', 'booking-activities' )
		),
		'quantity_separator' => array(
			'value'			=> '&nbsp;x',
			'description'	=> esc_html__( 'Separator between the event dates and its quantity. Write "&amp;nbsp;" to make a space.', 'booking-activities' )
		),
		'calendar_title' => array(
			'value'			=> esc_html__( 'Pick an event on the calendar:', 'booking-activities' ),
			'description'	=> esc_html__( 'Instructions displayed before the calendar.', 'booking-activities' )
		),
		'selected_event' => array(
			'value'			=> esc_html__( 'Selected event', 'booking-activities' ),
			/* translators: %s can be either "singular" or "plural" */
			'description'	=> sprintf( esc_html__( 'Title displayed before the selected events list (%s).', 'booking-activities' ), esc_html__( 'singular', 'booking-activities' ) )
		),
		'selected_events' => array(
			'value'			=> esc_html__( 'Selected events', 'booking-activities' ),
			'description'	=> sprintf( esc_html__( 'Title displayed before the selected events list (%s).', 'booking-activities' ), esc_html__( 'plural', 'booking-activities' ) )
		),
		'no_events' => array(
			'value'			=> esc_html__( 'No events available.', 'booking-activities' ),
			'description'	=> esc_html__( 'Message displayed instead of the calendar when no events are available.', 'booking-activities' )
		),
		'avail' => array(
			/* translators: This particle is used right after the quantity of available bookings. Put the singular here. E.g.: 1 avail. . */
			'value'			=> esc_html_x( 'avail.', 'Short for availability [singular noun]', 'booking-activities' ),
			/* translators: %s can be either "singular" or "plural" */
			'description'	=> sprintf( esc_html__( 'Particle displayed after the number of available places onto the events (%s).', 'booking-activities' ), esc_html__( 'singular', 'booking-activities' ) )
		),
		'avails' => array(
			/* translators: This particle is used right after the quantity of available bookings. Put the plural here. E.g.: 2 avail. . */
			'value'			=> esc_html_x( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ),
			'description'	=> sprintf( esc_html__( 'Particle displayed after the number of available places onto the events (%s).', 'booking-activities' ), esc_html__( 'plural', 'booking-activities' ) )
		),
		'booking_success' => array(
			'value'			=> esc_html__( 'Your reservation has been processed!', 'booking-activities' ),
			'description'	=> esc_html__( 'When a reservation has been successfully registered.', 'booking-activities' )
		),
		'booking_form_new_booking_button' => array(
			'value'			=> esc_html__( 'Make a new booking', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to make a new booking after the booking form has been submitted.', 'booking-activities' )
		),
		'choose_group_dialog_title' => array(
			'value'			=> esc_html__( 'This event is available in several bundles', 'booking-activities' ),
			'description'	=> esc_html__( 'Choose a group of events dialog title. It appears when a user clicks on an event bundled in multiple groups.', 'booking-activities' )
		),
		'choose_group_dialog_content' => array(
			'value'			=> esc_html__( 'Which group of events do you want to pick?', 'booking-activities' ),
			'description'	=> esc_html__( 'Choose a group of events dialog content.', 'booking-activities' ),
			'input_type'	=> 'textarea'
		),
		'choose_group_dialog_single_event' => array(
			/* translators: When the user is asked whether to pick the single event or the whole group it is part of */
			'value'			=> esc_html__( 'Single event', 'booking-activities' ),
			'description'	=> esc_html__( 'Single event option label in the group of events selection dialog.', 'booking-activities' ),
			'input_type'	=> 'textarea'
		),
		'refund_request_dialog_feedback_label' => array(
			'value'			=> esc_html__( 'Tell us why? (Details, reasons, comments...)', 'booking-activities' ),
			'description'	=> esc_html__( 'Refund request dialog\'s "Customer feedback" field label.', 'booking-activities' ),
			'input_type'	=> 'textarea'
		),
		'cancel_dialog_button' => array(
			'value'			=> esc_html_x( 'Close', 'Button label to close a dialog.', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to close a dialog.', 'booking-activities' )
		),
		'cancel_booking_open_dialog_button' => array(
			'value'			=> esc_html_x( 'Cancel', 'Button label to open the dialog to cancel a booking.', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to open the dialog to cancel a booking.', 'booking-activities' )
		),
		'cancel_booking_dialog_button' => array(
			'value'			=> esc_html_x( 'Cancel booking', 'Button label to trigger the cancellation of a booking.', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to trigger the cancellation of a booking.', 'booking-activities' )
		),
		'cancel_dialog_title' => array(
			'value'			=> esc_html_x( 'Cancel the booking', 'Dialog title', 'booking-activities' ),
			'description'	=> esc_html__( 'Cancel bookings dialog title.', 'booking-activities' )
		),
		'cancel_dialog_content' => array(
			'value'			=> esc_html__( 'Do you really want to cancel this booking?', 'booking-activities' ) . '<br/>' . esc_html__( 'If you have already paid, you will be able to request a refund.', 'booking-activities' ),
			'description'	=> esc_html__( 'Cancel bookings dialog content.', 'booking-activities' ),
			'input_type'	=> 'textarea'
		),
		'reschedule_dialog_button' => array(
			'value'			=> esc_html_x( 'Reschedule', 'Button label to reschedule a booking.', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to reschedule a booking.', 'booking-activities' )
		),
		'reschedule_dialog_title' => array(
			'value'			=> esc_html_x( 'Reschedule the booking', 'Dialog title', 'booking-activities' ),
			'description'	=> esc_html__( 'Reschedule booking dialog title.', 'booking-activities' )
		),
		'refund_dialog_button' => array(
			'value'			=> esc_html_x( 'Request a refund', 'Button label to refund a booking', 'booking-activities' ),
			'description'	=> esc_html__( 'Button label to refund a booking.', 'booking-activities' )
		),
		'refund_dialog_title' => array(
			'value'			=> esc_html_x( 'Request a refund', 'Dialog title', 'booking-activities' ),
			'description'	=> esc_html__( 'Refund booking dialog title.', 'booking-activities' )
		),
	);

	return apply_filters( 'bookacti_default_messages', $messages );
}


/**
 * Get all custom messages
 * @since 1.2.0
 * @version 1.14.0
 * @param boolean $raw Whether to retrieve the raw value from database or the option parsed through get_option
 * @return array
 */
function bookacti_get_messages( $raw = false ) {
	// Get raw value from database
	$saved_messages = array();
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		if( isset( $alloptions[ 'bookacti_messages_settings' ] ) ) {
			$saved_messages = maybe_unserialize( $alloptions[ 'bookacti_messages_settings' ] );
		}
	}

	// Else, get message settings through a normal get_option
	else {
		$saved_messages = get_option( 'bookacti_messages_settings' );
	}
	if( ! is_array( $saved_messages ) ) { $saved_messages = array(); }

	$default_messages = bookacti_get_default_messages();
	$messages = $default_messages;

	if( ! empty( $saved_messages ) ) {
		foreach( $default_messages as $message_id => $message ) {
			if( isset( $saved_messages[ $message_id ] ) ) {
				$messages[ $message_id ][ 'value' ] = $raw ? $saved_messages[ $message_id ] : apply_filters( 'bookacti_translate_text', $saved_messages[ $message_id ], '', true, array( 'string_name' => 'Message - ' . $message_id ) );
			}
		}
	}

	return apply_filters( 'bookacti_messages', $messages, $raw );
}


/**
 * Get a custom message by id
 * @since 1.2.0
 * @version 1.14.0
 * @param string $message_id
 * @param boolean $raw Whether to retrieve the raw value from database or the option parsed through get_option
 * @return string
 */
function bookacti_get_message( $message_id, $raw = false ) {
	$messages = bookacti_get_messages( $raw );
	$message = ! empty( $messages[ $message_id ][ 'value' ] ) ? $messages[ $message_id ][ 'value' ] : '';
	return $message;
}




// MISC

/**
 * Reset notices
 */
function bookacti_reset_notices() {
	delete_option( 'bookacti-install-date' );
	delete_option( 'bookacti-5stars-rating-notice-dismissed' );
}


/**
 * Get Booking Activities admin screen ids
 * @version 1.5.0
 */
function bookacti_get_screen_ids() {
	$screens = array(
		'toplevel_page_booking-activities',
		'booking-activities_page_bookacti_calendars',
		'booking-activities_page_bookacti_forms',
		'booking-activities_page_bookacti_bookings',
		'booking-activities_page_bookacti_settings'
	);

	return apply_filters( 'bookacti_screen_ids', $screens );
}


/**
 * Check if the current page is a Booking Activities screen
 * @since 1.7.0
 * @version 1.8.0
 * @return boolean
 */
function bookacti_is_booking_activities_screen( $screen = '' ) {
	if( ! function_exists( 'get_current_screen' ) ) { return false; }
	$current_screen = get_current_screen();
	if( empty( $current_screen ) ) { return false; }
	$bookacti_screens = bookacti_get_screen_ids();
	if( isset( $current_screen->id ) ) { 
		if( $screen && $current_screen->id !== $screen ) { return false; }
		if( in_array( $current_screen->id, $bookacti_screens, true ) ) { return true; }
	}
	return false;
}




// ROLES AND CAPABILITIES
/**
 * Add roles and capabilities
 * @version 1.8.0
 */
function bookacti_set_role_and_cap() {
	$administrator = get_role( 'administrator' );
	if( $administrator ) {
		$administrator->add_cap( 'bookacti_manage_booking_activities' );
		$administrator->add_cap( 'bookacti_manage_bookings' );
		$administrator->add_cap( 'bookacti_manage_templates' );
		$administrator->add_cap( 'bookacti_manage_forms' );
		$administrator->add_cap( 'bookacti_manage_booking_activities_settings' );
		$administrator->add_cap( 'bookacti_read_templates' );
		$administrator->add_cap( 'bookacti_create_templates' );
		$administrator->add_cap( 'bookacti_edit_templates' );
		$administrator->add_cap( 'bookacti_delete_templates' );
		$administrator->add_cap( 'bookacti_create_activities' );
		$administrator->add_cap( 'bookacti_edit_activities' );
		$administrator->add_cap( 'bookacti_delete_activities' );
		$administrator->add_cap( 'bookacti_edit_bookings' );
		$administrator->add_cap( 'bookacti_delete_bookings' );
		$administrator->add_cap( 'bookacti_create_forms' );
		$administrator->add_cap( 'bookacti_edit_forms' );
		$administrator->add_cap( 'bookacti_delete_forms' );
	}
	
	do_action( 'bookacti_set_capabilities' );
}


/**
 * Remove roles and capabilities
 * @version 1.8.0
 */
function bookacti_unset_role_and_cap() {
	$administrator	= get_role( 'administrator' );
	if( $administrator ) {
		$administrator->remove_cap( 'bookacti_manage_booking_activities' );
		$administrator->remove_cap( 'bookacti_manage_bookings' );
		$administrator->remove_cap( 'bookacti_manage_templates' );
		$administrator->remove_cap( 'bookacti_manage_forms' );
		$administrator->remove_cap( 'bookacti_manage_booking_activities_settings' );
		$administrator->remove_cap( 'bookacti_read_templates' );
		$administrator->remove_cap( 'bookacti_create_templates' );
		$administrator->remove_cap( 'bookacti_edit_templates' );
		$administrator->remove_cap( 'bookacti_delete_templates' );
		$administrator->remove_cap( 'bookacti_create_activities' );
		$administrator->remove_cap( 'bookacti_edit_activities' );
		$administrator->remove_cap( 'bookacti_delete_activities' );
		$administrator->remove_cap( 'bookacti_edit_bookings' );
		$administrator->remove_cap( 'bookacti_delete_bookings' );
		$administrator->remove_cap( 'bookacti_create_forms' );
		$administrator->remove_cap( 'bookacti_edit_forms' );
		$administrator->remove_cap( 'bookacti_delete_forms' );
	}
	
	do_action( 'bookacti_unset_capabilities' );
}




// PROMO

/**
 * Display a promotional area for Display Pack add-on
 * @since 1.2.0
 */
function bookacti_display_badp_promo() {
	$is_plugin_active	= bookacti_is_plugin_active( 'ba-display-pack/ba-display-pack.php' );
	$license_status		= get_option( 'badp_license_status' );

	// If the plugin is activated but the license is not active yet
	if( $is_plugin_active && ( empty( $license_status ) || $license_status !== 'valid' ) ) {
		?>
		<div class='bookacti-addon-promo' >
			<p>
			<?php 
				/* translators: %s = add-on name */
				echo sprintf( __( 'Thank you for purchasing %s add-on!', 'booking-activities' ), '<strong>Display Pack</strong>' ); 
			?>
			</p><p>
				<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", 'booking-activities' ); ?>
			</p><p>
				<strong>
					<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-display-pack-add-on/prerequisite-installation-license-activation-of-display-pack-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-calendar' target='_blank' >
						<?php 
						/* translators: %s = add-on name */
							echo sprintf( __( 'How to activate %s license?', 'booking-activities' ), 'Display Pack' ); 
						?>
					</a>
				</strong>
			</p>
		</div>
		<?php
	}

	else if( empty( $license_status ) || $license_status !== 'valid' ) {
		?>
		<div class='bookacti-addon-promo' >
			<?php 
			$addon_link = '<a href="https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=encart-promo-calendar" target="_blank" >Display Pack</a>';
			/* translators: %1$s is the placeholder for Display Pack add-on link */
			echo sprintf( esc_html( __( 'Get other essential customization options with %1$s add-on!', 'booking-activities' ) ), $addon_link ); 
			?>
			<div><a href='https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=encart-promo-calendar' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
		</div>
		<?php
	}
}


/**
 * Display a promotional area for Notification Pack add-on
 * @since 1.2.0
 * @version 1.8.5
 */
function bookacti_display_banp_promo() {
	$is_plugin_active	= bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
	$license_status		= get_option( 'banp_license_status' );
	$is_license_valid	= $license_status === 'valid';

	// If the plugin is activated but the license is not active yet
	if( $is_plugin_active && ! $is_license_valid ) {
		?>
		<div id='bookacti-banp-promo' class='bookacti-addon-promo' >
			<p>
			<?php 
				/* translators: %s = add-on name */
				echo sprintf( __( 'Thank you for purchasing %s add-on!', 'booking-activities' ), '<strong>Notification Pack</strong>' ); 
			?>
			</p><p>
				<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", 'booking-activities' ); ?>
			</p><p>
				<strong>
					<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-notification-pack-add-on/prerequisite-installation-license-activation-notification-pack-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-settings' target='_blank' >
						<?php 
						/* translators: %s = add-on name */
							echo sprintf( __( 'How to activate %s license?', 'booking-activities' ), 'Notification Pack' ); 
						?>
					</a>
				</strong>
			</p>
		</div>
		<?php
	}
	
	// If the license is not active yet
	else if( ! $is_license_valid ) {
		?>
		<div id='bookacti-banp-promo' class='bookacti-addon-promo' >
			<?php 
			$addon_link = '<strong><a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=encart-promo-settings" target="_blank" >Notification Pack</a></strong>';
			/* translators: %1$s is the placeholder for Notification Pack add-on link */
			echo sprintf( esc_html__( 'You can send all these notifications and booking reminders via email, SMS and Push with %1$s add-on!', 'booking-activities' ), $addon_link ); 
			?>
			<div><a href='https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=encart-promo-settings' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
		</div>
		<?php
	}
}


/**
 * Display the admin message field to promote the Notification Pack add-on
 * @since 1.10.0
 */
function bookacti_display_banp_promo_admin_message() {
	$is_plugin_active = bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
	if( $is_plugin_active ) { return; }
	
	$addon_link		= '<strong><a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=admin-message" target="_blank" >Notification Pack</a></strong>';
	/* translators: %s = the add-on name "Notification Pack" */
	$label			= sprintf( esc_html__( 'Add a message for your customers thanks to the %s add-on', 'booking-activities' ), $addon_link );
	$placeholder	= esc_html__( 'This message will be displayed in your notification where the {admin_message} tag will be.', 'booking-activities' );
	
	?>
		<div class='bookacti-field-container bookacti-addon-promo bookacti-banp-promo-admin-message'>
			<label class='bookacti-fullwidth-label'><?php echo $label; ?></label>
			<textarea placeholder='<?php echo $placeholder; ?>'></textarea>
		</div>
	<?php
}


/**
 * Display a promotional area for Advanced Forms add-on
 * @since 1.5.4
 */
function bookacti_display_baaf_promo() {
	$is_plugin_active	= bookacti_is_plugin_active( 'ba-advanced-forms/ba-advanced-forms.php' );
	$license_status		= get_option( 'baaf_license_status' );

	// If the plugin is activated but the license is not active yet
	if( $is_plugin_active && ( ! $license_status || $license_status !== 'valid' ) ) {
		?>
		<div id='bookacti-baaf-promo' class='bookacti-addon-promo' >
			<p>
			<?php 
				/* translators: %s = add-on name */
				echo sprintf( __( 'Thank you for purchasing %s add-on!', 'booking-activities' ), '<strong>Advanced Forms</strong>' ); 
			?>
			</p><p>
				<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", 'booking-activities' ); ?>
			</p><p>
				<strong>
					<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-advanced-forms-add-on/prerequisite-installation-and-license-activation-of-advanced-forms-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-form-dialog' target='_blank' >
						<?php 
						/* translators: %s = add-on name */
							echo sprintf( __( 'How to activate %s license?', 'booking-activities' ), 'Advanced Forms' ); 
						?>
					</a>
				</strong>
			</p>
		</div>
		<?php
	}

	else if( ! $license_status || $license_status !== 'valid' ) {
		?>
		<div id='bookacti-baaf-promo' class='bookacti-addon-promo' >
			<?php 
			$addon_link = '<strong><a href="https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=encart-promo-form-dialog" target="_blank" >Advanced Forms</a></strong>';
			/* translators: %1$s is the placeholder for Advanced Forms add-on link */
			echo sprintf( esc_html__( 'Create any kind of custom fields to get any information from your customers (text, number, file, checkboxes, selectbox, etc.) with %1$s add-on!', 'booking-activities' ), $addon_link ); 
			?>
			<div><a href='https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=encart-promo-form-dialog' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
		</div>
		<?php
	}
}




// PRIVACY OPTIONS

/**
 * Export additional user metadata with WP privacy export tool
 * @since 1.7.0
 * @version 1.7.8
 * @param string $email_address
 * @param int $page
 * @return array
 */
function bookacti_privacy_exporter_user_data( $email_address, $page = 1 ) {
	$user			= get_user_by( 'email', $email_address );
	$data_to_export = array();
	
	if( $user instanceof WP_User ) {
		$user_meta = get_user_meta( $user->ID );
		
		$user_meta_to_export = apply_filters( 'bookacti_privacy_export_user_columns', array(
			'phone' => esc_html__( 'Phone', 'booking-activities' )
		), $user_meta, $email_address, $page );
		
		$user_personal_data = array();
		foreach( $user_meta_to_export as $key => $name ) {
			if( empty( $user_meta[ $key ] ) ) { continue; }
			$user_personal_data[] = array(
				'name'  => $name,
				'value' => $user_meta[ $key ][0]
			);
		}
		
		if( ! empty( $user_personal_data ) ) {
			$data_to_export[] = array(
				'group_id'    => 'user',
				'group_label' => __( 'User' ),
				'item_id'     => 'user-' . $user->ID,
				'data'        => $user_personal_data,
			);
		}
	}
	
	return array(
		'data' => apply_filters( 'bookacti_privacy_export_user_data', $data_to_export, $email_address, $page ),
		'done' => true,
	);
}


/**
 * Export bookings user metadata with WP privacy export tool
 * @since 1.7.0
 * @version 1.14.0
 * @param string $email_address
 * @param int $page
 * @return array
 */
function bookacti_privacy_exporter_bookings_data( $email_address, $page = 1 ) {
	$user                = get_user_by( 'email', $email_address );
	$number              = 200; // Limit to avoid timing out
	$page                = (int) $page;
	$data_to_export      = array();
	$bookings            = array(); 
	$group_by_booking    = array();
	$bookings_meta       = array();
	$booking_groups_meta = array();
	
	// Get bookings either by email or id if the user is registered
	$user_ids = array( $email_address );
	if( $user instanceof WP_User ) {
		$user_ids[] = $user->ID;
	}
	
	// Count the total number of bookings to know when the exporter is done
	$filters_count = bookacti_format_booking_filters( array(
		'in__user_id' => $user_ids
	) );
	$bookings_count = bookacti_get_number_of_booking_rows( $filters_count );
	
	if( $bookings_count ) {
		// Get the bookings
		$filters = bookacti_format_booking_filters( array(
			'in__user_id' => $user_ids,
			'offset'      => ($page-1)*$number,
			'per_page'    => $number
		) );
		$bookings = bookacti_get_bookings( $filters );

		if( $bookings ) {
			// Store booking ids and booking groups ids separatly to retrieve metadata
			$bookings_ids = array();
			$booking_groups_ids = array();
			foreach( $bookings as $booking ) {
				if( ! empty( $booking->group_id ) ) {
					$group_by_booking[ $booking->id ] = $booking->group_id;
					if( ! in_array( $booking->group_id, $booking_groups_ids, true ) ) {
						$booking_groups_ids[] = $booking->group_id;
					}
				}
				$bookings_ids[] = $booking->id;
			}

			$bookings_meta       = bookacti_get_metadata( 'booking', $bookings_ids );
			$booking_groups_meta = bookacti_get_metadata( 'booking_group', $booking_groups_ids );

			// Allow third party to change the exported data
			$booking_meta_to_export = apply_filters( 'bookacti_privacy_export_bookings_columns', array(
				'id'              => esc_html__( 'ID', 'booking-activities' ),
				'group_id'        => esc_html__( 'Group ID', 'booking-activities' ),
				'creation_date'   => esc_html__( 'Date', 'booking-activities' ),
				'event_title'     => esc_html__( 'Title', 'booking-activities' ),
				'event_start'     => esc_html__( 'Start', 'booking-activities' ),
				'event_end'       => esc_html__( 'End', 'booking-activities' ),
				'state'           => esc_html__( 'Status', 'booking-activities' ),
				'user_id'         => esc_html__( 'User ID', 'booking-activities' ),
				'user_email'      => esc_html__( 'Email', 'booking-activities' ),
				'user_first_name' => esc_html__( 'First name', 'booking-activities' ),
				'user_last_name'  => esc_html__( 'Last name', 'booking-activities' ),
				'user_phone'      => esc_html__( 'Phone', 'booking-activities' )
			), $bookings, $bookings_meta, $booking_groups_meta, $email_address, $page );

			// Set the name / value data to export for each booking
			$date_format = bookacti_get_message( 'date_format_long' );
			$states = bookacti_get_booking_state_labels();
			foreach( $bookings as $booking ) {
				$booking_personal_data = array();
				$booking_meta		= ! empty( $bookings_meta[ $booking->id ] ) ? $bookings_meta[ $booking->id ] : array();
				$booking_group_meta = ! empty( $booking->group_id ) && ! empty( $booking_groups_meta[ $booking->group_id ] ) ? $booking_groups_meta[ $booking->group_id ] : array();
				
				foreach( $booking_meta_to_export as $key => $name ) {
					$value = '';
					if( ! empty( $booking->$key ) ) { 
						switch ( $key ) {
							case 'user_id':
								$value = is_numeric( $booking->$key ) ? '' : $booking->$key;
								break;
							case 'event_title':
								$value = ! empty( $booking->$key ) ? apply_filters( 'bookacti_translate_text', $booking->$key ) : '';
								break;
							case 'creation_date':
							case 'event_start':
							case 'event_end':
								$value = bookacti_format_datetime( $booking->$key, $date_format );
								break;
							case 'state':
								$value = ! empty( $states[ $booking->$key ][ 'label' ] ) ? $states[ $booking->$key ][ 'label' ] : $booking->$key;
								break;
							default:
								$value = $booking->$key;
						}
					}
					else if( isset( $booking_meta[ $key ] ) ) {
						$value = $booking_meta[ $key ];
					}
					else if( isset( $booking_group_meta[ $key ] ) ) {
						$value = $booking_group_meta[ $key ];
					}

					$value = apply_filters( 'bookacti_privacy_export_booking_value', $value, $key, $booking, $booking_meta, $booking_group_meta, $email_address, $page );

					if( $value === '' || ( ! is_string( $value ) && ! is_numeric( $value ) ) ) { continue; }

					$booking_personal_data[] = array(
						'name'  => $name,
						'value' => $value
					);
				}

				if ( ! empty( $booking_personal_data ) ) {
					$data_to_export[] = array(
						'group_id'    => 'bookacti_bookings',
						'group_label' => esc_html__( 'Bookings', 'booking-activities' ),
						'item_id'     => 'bookacti_booking_' . $booking->id,
						'data'        => apply_filters( 'bookacti_privacy_export_booking_data', $booking_personal_data, $booking, $booking_meta, $booking_group_meta, $email_address, $page )
					);
				}
			}
		}
	}
	
	return array(
		'data' => apply_filters( 'bookacti_privacy_export_bookings_data', $data_to_export, $bookings, $group_by_booking, $bookings_meta, $booking_groups_meta, $email_address, $page ),
		'done' => ($page*$number) >= $bookings_count
	);
}


/**
 * Erase additional user metadata with WP privacy export tool
 * @since 1.7.0
 * @version 1.7.8
 * @param string $email_address
 * @param int $page
 * @return array
 */
function bookacti_privacy_eraser_user_data( $email_address, $page = 1 ) {
	$user		= get_user_by( 'email', $email_address );
	$response	= array(
		'items_removed' => false,
		'items_retained' => false,
		'messages' => array(),
		'done' => true
	);
	
	if( $user instanceof WP_User ) {
		$user_meta = get_user_meta( $user->ID );
		
		$user_meta_to_erase = apply_filters( 'bookacti_privacy_erase_user_columns', array(
			'phone' => esc_html__( 'Phone', 'booking-activities' )
		), $user_meta, $email_address, $page );
		
		foreach( $user_meta_to_erase as $key => $label ) {
			if( empty( $user_meta[ $key ] ) ) { continue; }
			$deleted = delete_user_meta( $user->ID, $key );
			if( ! $deleted ) {
				$field_label = $label ? $label : $key;
				$response[ 'items_retained' ] = true;
				/* translators: %s is the name of the data */
				$response[ 'messages' ][] = sprintf( esc_html__( 'This data couldn\'t be deleted: %s.', 'booking-activities' ), $field_label );
			} else { 
				$response[ 'items_removed' ] = true;
			}
		}
		
		if( $response[ 'items_removed' ] ) {
			$response[ 'messages' ][] = esc_html__( 'Personal data attached to the account by Booking Activities have been successfully deleted.', 'booking-activities' );
		}
	}
	
	return apply_filters( 'bookacti_privacy_erase_user_data', $response, $email_address, $page );
}


/**
 * Erase bookings user metadata with WP privacy erase tool
 * @since 1.7.0
 * @param string $email_address
 * @param int $page
 * @return array
 */
function bookacti_privacy_eraser_bookings_data( $email_address, $page = 1 ) {
	$user	= get_user_by( 'email', $email_address );
	$number	= 200; // Limit to avoid timing out
	$page	= (int) $page;
	$bookings = array();
	$response = array(
		'items_removed' => false,
		'items_retained' => false,
		'messages' => array(),
		'done' => true
	);
	
	// Get bookings either by email or id if the user is registered
	$anonymized_user_id = 'anon_' . md5( microtime().rand() );
	$user_ids = array( $email_address );
	if( $user instanceof WP_User ) {
		$user_ids[] = $user->ID;
		$anonymized_user_id = $user->ID;
	}
	
	// Count the total number of bookings to know when the exporter is done
	$filters_count = bookacti_format_booking_filters( array(
		'in__user_id'	=> $user_ids
	) );
	$bookings_count = bookacti_get_number_of_booking_rows( $filters_count );
	$response[ 'done' ] = ($page*$number) >= $bookings_count;
	
	if( $bookings_count ) {
		// Get the bookings
		$filters = bookacti_format_booking_filters( array(
			'in__user_id'	=> $user_ids,
			'offset'		=> ($page-1)*$number,
			'per_page'		=> $number
		) );
		$bookings = bookacti_get_bookings( $filters );

		if( $bookings ) {
			// Store booking ids and booking groups ids separatly to delete metadata
			$bookings_ids = array();
			$booking_groups_ids = array();
			$group_by_booking = array();
			foreach( $bookings as $booking ) {
				if( ! empty( $booking->group_id ) ) {
					$group_by_booking[ $booking->id ] = $booking->group_id;
					if( ! in_array( $booking->group_id, $booking_groups_ids, true ) ) {
						$booking_groups_ids[] = $booking->group_id;
					}
				}
				$bookings_ids[] = $booking->id;
			}
			
			// Let add-ons add metadata to remove
			$booking_meta_to_erase = apply_filters( 'bookacti_privacy_erase_bookings_columns', array(
				'user_email'		=> esc_html__( 'Email', 'booking-activities' ),
				'user_first_name'	=> esc_html__( 'First name', 'booking-activities' ),
				'user_last_name'	=> esc_html__( 'Last name', 'booking-activities' ),
				'user_phone'		=> esc_html__( 'Phone', 'booking-activities' )
			), $bookings, $email_address, $page );
			
			$response = apply_filters( 'bookacti_privacy_erase_bookings_data_before', $response, $bookings, $booking_meta_to_erase, $email_address, $page );
			
			// Delete the bookings metadata
			$deleted_booking_meta = bookacti_delete_metadata( 'booking', $bookings_ids, array_keys( $booking_meta_to_erase ) );
			if( $deleted_booking_meta ) { $response[ 'items_removed' ] = true; }
			else if( $deleted_booking_meta === false ) { 
				$response[ 'items_retained' ] = true;
				$response[ 'messages' ][] = esc_html__( 'Some booking personal metadata may have not be deleted.', 'booking-activities' );
			}
			
			// Delete the booking groups metadata
			if( $booking_groups_ids ) {
				$deleted_booking_group_meta = bookacti_delete_metadata( 'booking_group', $booking_groups_ids, array_keys( $booking_meta_to_erase ) );
				if( $deleted_booking_group_meta ) { $response[ 'items_removed' ] = true; }
				else if( $deleted_booking_group_meta === false ) { 
					$response[ 'items_retained' ] = true;
					$response[ 'messages' ][] = esc_html__( 'Some booking group personal metadata may have not be deleted.', 'booking-activities' );
				}
			}
			
			// Feedback the user
			if( $response[ 'items_removed' ] ) {
				$response[ 'messages' ][] = esc_html__( 'Personal data attached to the bookings have been successfully deleted.', 'booking-activities' );
			}
		}
	}
	
	$response = apply_filters( 'bookacti_privacy_erase_bookings_data', $response, $bookings, $email_address, $page );
	
	// Anonymize the bookings made without account when everything else is finished
	if( $response[ 'done' ] ) {
		$anonymized = false;
		$anonymize_allowed = apply_filters( 'bookacti_privacy_anonymize_bookings_without_account', true, $email_address, $page );
		if( $anonymize_allowed ) {
			$anonymized_user_id = apply_filters( 'bookacti_privacy_anonymized_user_id', $anonymized_user_id, $email_address, $page );
			$anonymized = bookacti_update_bookings_user_id( $anonymized_user_id, $email_address, false );
			if( $anonymized ) {
				$response[ 'messages' ][] = esc_html__( 'The bookings made without account have been successfully anonymized.', 'booking-activities' );
			}
		}
		if( $anonymized === false ) {
			$response[ 'items_retained' ] = true;
			$response[ 'messages' ][] = esc_html__( 'The bookings made without account may have not been anonymized.', 'booking-activities' );
		}
	}
	
	return $response;
}