<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get default settings values
 * 
 * @since 1.3.0 (was bookacti_define_default_settings_constants)
 * @version 1.4.0
 */
function bookacti_get_default_settings() {
	$date = new DateTime(); 
	$tz = $date->getTimezone()->getName();
	
	$default = array(
		'booking_method'						=> 'calendar',
		'when_events_load'						=> 'on_page_load',
		'event_load_interval'					=> 92,
		'started_events_bookable'				=> false,
		'started_groups_bookable'				=> false,
		'availability_period_start'				=> 0,
		'availability_period_end'				=> 0,
		'default_booking_state'					=> 'pending',
		'default_payment_status'				=> 'none',
		'timezone'								=> $tz,
		'allow_customers_to_cancel'				=> true,
		'allow_customers_to_reschedule'			=> true,
		'cancellation_min_delay_before_event'	=> 7,
		'refund_actions_after_cancellation'		=> 'do_nothing',
		'notifications_from_name'				=> get_bloginfo( 'name' ),
		'notifications_from_email'				=> get_bloginfo( 'admin_email' ),
		'notifications_async'					=> true
	);
	
	return apply_filters( 'bookacti_default_settings', $default );
}


/**
 * Delete settings
 * 
 * @version 1.3.0
 */
function bookacti_delete_settings() {
	delete_option( 'bookacti_template_settings' ); // Deprecated
	delete_option( 'bookacti_bookings_settings' ); // Deprecated
	delete_option( 'bookacti_general_settings' );
	delete_option( 'bookacti_cancellation_settings' );
	delete_option( 'bookacti_notifications_settings' );
	delete_option( 'bookacti_messages_settings' );
	
	bookacti_delete_user_meta( 'bookacti_default_template' );
	bookacti_delete_user_meta( 'bookacti_status_filter' );
	
	do_action( 'bookacti_delete_settings' );
}


/**
 * Get setting value
 * 
 * @version 1.3.0
 * 
 * @param string $setting_page
 * @param string $setting_field
 * @param boolean $translate
 * @return mixed
 */
function bookacti_get_setting_value( $setting_page, $setting_field, $translate = true ) {
	
	$settings = get_option( $setting_page );
	
	if( ! is_array( $settings ) ) { $settings = array(); }
	
	if( ! isset( $settings[ $setting_field ] ) ) {
		$default = bookacti_get_default_settings();
		if( isset( $default[ $setting_field ] ) ) {
			$settings[ $setting_field ] = $default[ $setting_field ];
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




// SECTION CALLBACKS
function bookacti_settings_section_general_callback() { }
function bookacti_settings_section_cancellation_callback() { }
function bookacti_settings_section_template_callback() { }
function bookacti_settings_section_bookings_callback() { }


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
			'label' => __( 'Bookings per page:', BOOKACTI_PLUGIN_NAME ),
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
		
		if( ! empty( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], array( 'edit', 'create' ), true ) ) {
			// Layout columns number
			add_screen_option( 'layout_columns', array( 
				'max' => 2, 
				'default' => 2 
			));
		} else {
			// Bookings per page
			add_screen_option( 'per_page', array(
				'label' => __( 'Forms per page:', BOOKACTI_PLUGIN_NAME ),
				'default' => 20,
				'option' => 'bookacti_forms_per_page'
			));
		}
	}
	

// GENERAL SETTINGS 

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
	 * Display Event Load Interval setting
	 * 
	 * @since 1.2.2
	 */
	function bookacti_settings_field_event_load_interval_callback() {
		$args = array(
			'type'		=> 'number',
			'name'		=> 'bookacti_general_settings[event_load_interval]',
			'id'		=> 'event_load_interval',
			'options'	=> array( 'min' => 1 ),
			'label'		=> ' ' . esc_html__( 'days', BOOKACTI_PLUGIN_NAME ),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ),
			'tip'		=> __( 'Events are loaded at intervals as the user navigates the calendar. E.g.: If you set "92", events will be loaded for 92 days. When the user reaches the 92nd day on the calendar, events of the next 92 days will be loaded.', BOOKACTI_PLUGIN_NAME )
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
			'tip'	=> __( 'Allow or disallow users to book an event that has already begun.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display "Is the user allowed to book a group of events that has already begun?" setting
	 * 
	 * @since 1.4.0
	 */
	function bookacti_settings_field_started_groups_bookable_callback() {
		$tip = __( 'Allow or disallow users to book a group of events that has already begun.', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( 'This parameter applies to all groups of events. An group category-specific parameter is available in group category settings, in the calendar editor.', BOOKACTI_PLUGIN_NAME );
		
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
	 * Display event availability period start setting
	 * 
	 * @since 1.4.0
	 */
	function bookacti_settings_field_availability_period_start_callback() {
		
		$tip = __( 'Set the beginning of the availability period. E.g.: "2", your customers may book events starting in 2 days at the earliest. They are no longer allowed to book events starting earlier (like today or tomorrow).', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( 'This parameter applies to all events. An calendar-specific parameter is available in calendar settings, in the calendar editor.', BOOKACTI_PLUGIN_NAME );
		
		$args = array(
			'type'		=> 'number',
			'name'		=> 'bookacti_general_settings[availability_period_start]',
			'id'		=> 'availability_period_start',
			'options'	=> array( 'min' => 0 ),
			/* translators: Arrive after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
			'label'		=> ' ' . esc_html__( 'days from today', BOOKACTI_PLUGIN_NAME ),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_start' ),
			'tip'		=> $tip
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display event availability period end setting
	 * 
	 * @since 1.4.0
	 */
	function bookacti_settings_field_availability_period_end_callback() {
		
		$tip = __( 'Set the end of the availability period. E.g.: "30", your customers may book events starting within 30 days at the latest. They are not allowed yet to book events starting later.', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( 'This parameter applies to all events. An calendar-specific parameter is available in calendar settings.', BOOKACTI_PLUGIN_NAME );
		
		$args = array(
			'type'		=> 'number',
			'name'		=> 'bookacti_general_settings[availability_period_end]',
			'id'		=> 'availability_period_end',
			'options'	=> array( 'min' => 0 ),
			/* translators: Arrive after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
			'label'		=> ' ' . esc_html__( 'days from today', BOOKACTI_PLUGIN_NAME ),
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_end' ),
			'tip'		=> $tip
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
			'tip'		=> __( 'Choose what status a booking should have when a customer complete the booking form.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display "default payment status" setting
	 * 
	 * @since 1.3.0
	 */
	function bookacti_settings_field_default_payment_status_callback() {
		
		$payment_status = bookacti_get_payment_status_labels();
		$payment_status_array = array();
		foreach( $payment_status as $payment_status_id => $payment_status_data ) {
			$payment_status_array[ esc_attr( $payment_status_id ) ] = esc_html( $payment_status_data[ 'label' ] );
		}
		
		$args = array(
			'type'		=> 'select',
			'name'		=> 'bookacti_general_settings[default_payment_status]',
			'id'		=> 'default_payment_status',
			'options'	=> $payment_status_array,
			'value'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' ),
			/* translators: The word 'Calendar' refers to a booking method you have to translate too. Make sure you use the same word for both translation. */
			'tip'		=> __( 'Choose what payment status a booking should have when a customer complete the booking form.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
	
	
	/**
	 * Display Timezone setting
	 * 
	 * @since 1.1.0
	 * @version 1.4.0
	 */
	function bookacti_settings_field_timezone_callback() {
		$regions = array(
			'UTC'			=> DateTimeZone::UTC,
			'Africa'		=> DateTimeZone::AFRICA,
			'America'		=> DateTimeZone::AMERICA,
			'Antarctica'	=> DateTimeZone::ANTARCTICA,
			'Arctic'		=> DateTimeZone::ARCTIC,
			'Asia'			=> DateTimeZone::ASIA,
			'Atlantic'		=> DateTimeZone::ATLANTIC,
			'Australia'		=> DateTimeZone::AUSTRALIA,
			'Europe'		=> DateTimeZone::EUROPE,
			'Indian'		=> DateTimeZone::INDIAN,
			'Pacific'		=> DateTimeZone::PACIFIC
		);
		
		$timezones = array();
		
		foreach ( $regions as $name => $mask ) {
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
		echo '<select name="bookacti_general_settings[timezone]" >';
		foreach( $timezones as $region => $list ) {
			echo '<optgroup label="' . $region . '" >';
			foreach( $list as $timezone => $name ) {
				echo '<option value="' . $timezone . '" ' . selected( $selected_timezone, $timezone ) . '>' . $name . '</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
		
		// Display the help tip 
		$tip  = __( 'Pick the timezone corresponding to where your business takes place.', BOOKACTI_PLUGIN_NAME );
		bookacti_help_tip( $tip );
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
	 * @version 1.4.0
	 */
	function bookacti_settings_field_activate_reschedule_callback() {
		$tip  = __( 'Allow or disallow customers to reschedule a booking after they order it.', BOOKACTI_PLUGIN_NAME );
		$tip .= '<br/>' . __( "This won't apply to groups of bookings.", BOOKACTI_PLUGIN_NAME );
		
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
			'tip'		=> __( 'Set the end of the allowed changes period (cancellation, rescheduling). E.g.: "7", your customers may change their reservations at least 7 days before the start of the event. After that, they won\'t be allowed to change them anymore.', BOOKACTI_PLUGIN_NAME )
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
	 * Settings section callback - Notifications - General settings (displayed before settings)
	 * 
	 * @since 1.2.1 (was bookacti_settings_section_notifications_callback in 1.2.0)
	 * @version 1.5.0
	 */
   function bookacti_settings_section_notifications_general_callback() { 

		// Display a table of configurable notifications
		// Set up booking list columns
		$columns_titles = apply_filters( 'bookacti_notifications_list_columns_titles', array(
			10	=> array( 'id' => 'active',		'title' => esc_html_x( 'Active', 'is the notification active', BOOKACTI_PLUGIN_NAME ) ),
			20	=> array( 'id' => 'title',		'title' => esc_html_x( 'Trigger', 'what triggers a notification', BOOKACTI_PLUGIN_NAME ) ),
			30	=> array( 'id' => 'recipients',	'title' => esc_html_x( 'Send to', 'who the notification is sent to', BOOKACTI_PLUGIN_NAME ) ),
			100 => array( 'id' => 'actions',	'title' => esc_html__( 'Actions', BOOKACTI_PLUGIN_NAME ) )
		) );

		// Order columns
		ksort( $columns_titles, SORT_NUMERIC );
	   
	   ?>
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
			$notifications = array_keys( bookacti_get_notifications_default_settings() );
			asort( $notifications, SORT_STRING );
			
			foreach( $notifications as $notification_id ) {
				
				$notification_settings = bookacti_get_notification_settings( $notification_id, false );
				
				$active_icon	= $notification_settings[ 'active' ] ? 'dashicons-yes' : 'dashicons-no';
				$description	= $notification_settings[ 'description' ] ? bookacti_help_tip( $notification_settings[ 'description' ], false ) : '';
				
				$columns_values = apply_filters( 'bookacti_notifications_list_columns_values', array(
					'active'		=> '<span class="dashicons ' . $active_icon . '"></span>',
					'title'			=> '<a href="' . esc_url( '?page=bookacti_settings&tab=notifications&notification_id=' . sanitize_title_with_dashes( $notification_id ) ) . '" >' . esc_html( $notification_settings[ 'title' ] ) . '</a>' . $description,
					'recipients'	=> substr( $notification_id, 0, 8 ) === 'customer' ? esc_html__( 'Customer', BOOKACTI_PLUGIN_NAME ) : esc_html__( 'Administrator', BOOKACTI_PLUGIN_NAME ),
					'actions'		=> '<a href="' . esc_url( '?page=bookacti_settings&tab=notifications&notification_id=' . sanitize_title_with_dashes( $notification_id ) ) . '" title="' . esc_attr__( 'Edit this notification', BOOKACTI_PLUGIN_NAME ) . '" ><span class="dashicons dashicons-admin-generic" ></span></a>'
				), $notification_settings, $notification_id );
				
				?>
				<tr>
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
				$addon_link = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >';
				$addon_link .= esc_html__( 'Notification Pack', BOOKACTI_PLUGIN_NAME );
				$addon_link .= '</a>';
				$columns_values = array(
					'active'		=> '<span class="dashicons dashicons-no"></span>',
					'title'			=> '<strong>' . esc_html__( '1 day before a booked event (reminder)', BOOKACTI_PLUGIN_NAME ) . '</strong>' 
										/* translators: %1$s is the placeholder for Notification Pack add-on link */
										. bookacti_help_tip( sprintf( esc_html__( 'You can send automatic reminders with %1$s add-on some days before booked events (you set the amount of days). This add-on also allow you to send all notifications through SMS and Push.', BOOKACTI_PLUGIN_NAME ), $addon_link ), false ),
					'recipients'	=> esc_html__( 'Customer', BOOKACTI_PLUGIN_NAME ),
					'actions'		=> "<a href='https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list' class='button' target='_blank' >" . esc_html__( 'Learn more', BOOKACTI_PLUGIN_NAME ) . "</a>"
				);
				
				?>
				<tr>
				<?php foreach( $columns_titles as $column ) { ?>
					<td class='bookacti-notifications-list-column-value-<?php echo sanitize_title_with_dashes( $column[ 'id' ] ); ?>' >
					<?php
						if( isset( $columns_values[ $column[ 'id' ] ] ) ) { 
							echo $columns_values[ $column[ 'id' ] ];
						}
					?>
					</td>
				<?php }
			}
			?>
			</tbody>
		</table>
	   <?php
	   bookacti_display_banp_promo();
	}
	
	
	/**
	 * Settings section callback - Notifications - Email settings (displayed before settings)
	 * 
	 * @since 1.2.1
	 */
	function bookacti_settings_section_notifications_email_callback() {}
	
	
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
			'tip'	=> __( 'Whether to send notifications asynchronously. If enabled, notifications will be sent the next time any page of this website is loaded. No one will have to wait any longer. Else, the loadings will last until notifications are sent.', BOOKACTI_PLUGIN_NAME )
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
	 * @version 1.3.0
	 */
	function bookacti_get_default_messages() {
		$wp_date_format_link = '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank" >' .  esc_html__( 'Formatting Date and Time', BOOKACTI_PLUGIN_NAME ) . '</a>';
		
		$messages = array(
			'date_format_long' => array(
				/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
				'value'			=> esc_html__( 'l, F jS, Y g:i A', BOOKACTI_PLUGIN_NAME ),
				'description'	=> sprintf( esc_html__( 'Complete date format. See the tags here: %1$s.', BOOKACTI_PLUGIN_NAME ), $wp_date_format_link )
			),
			'date_format_short' => array(
				/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
				'value'			=> esc_html__( 'M, jS - g:i A', BOOKACTI_PLUGIN_NAME ),
				'description'	=> sprintf( esc_html__( 'Short date format. See the tags here: %1$s.', BOOKACTI_PLUGIN_NAME ), $wp_date_format_link )
			),
			'time_format' => array(
				/* translators: Time format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
				'value'			=> esc_html__( 'g:i A', BOOKACTI_PLUGIN_NAME ),
				'description'	=> sprintf( esc_html__( 'Time format. It will be used when a time is displayed alone. See the tags here: %1$s.', BOOKACTI_PLUGIN_NAME ), $wp_date_format_link )
			),
			'dates_separator' => array(
				'value'			=> '&nbsp;&rarr;&nbsp;',
				'description'	=> esc_html__( 'Separator between two dates. Write "&amp;nbsp;" to make a space.', BOOKACTI_PLUGIN_NAME )
			),
			'date_time_separator' => array(
				'value'			=> '&nbsp;&rarr;&nbsp;',
				'description'	=> esc_html__( 'Separator between a date and a time. Write "&amp;nbsp;" to make a space.', BOOKACTI_PLUGIN_NAME )
			),
			'quantity_separator' => array(
				'value'			=> '&nbsp;x',
				'description'	=> esc_html__( 'Separator between the event dates and its quantity. Write "&amp;nbsp;" to make a space.', BOOKACTI_PLUGIN_NAME )
			),
			'calendar_title' => array(
				'value'			=> esc_html__( 'Pick an event on the calendar:', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Instructions displayed before the calendar.', BOOKACTI_PLUGIN_NAME )
			),
			'booking_success' => array(
				'value'			=> esc_html__( 'Your event has been booked successfully!', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'When a reservation has been successfully registered.', BOOKACTI_PLUGIN_NAME )
			),
			'booking_form_submit_button' => array(
				'value'			=> esc_html__( 'Book', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Submit button label.', BOOKACTI_PLUGIN_NAME )
			),
			'booking_form_new_booking_button' => array(
				'value'			=> esc_html__( 'Make a new booking', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Button label to make a new booking after the booking form has been submitted.', BOOKACTI_PLUGIN_NAME )
			),
			'choose_group_dialog_title' => array(
				'value'			=> esc_html__( 'This event is available in several bundles', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Choose a group of events dialog title. It appears when a user clicks on an event bundled in multiple groups.', BOOKACTI_PLUGIN_NAME )
			),
			'choose_group_dialog_content' => array(
				'value'			=> esc_html__( 'Which group of events do you want to pick?', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Choose a group of events dialog content.', BOOKACTI_PLUGIN_NAME ),
				'input_type'	=> 'textarea'
			),
			'cancel_dialog_button' => array(
				'value'			=> esc_html_x( 'Cancel', 'Cancel bookings button label. It opens the dialog.', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Cancel bookings button label.', BOOKACTI_PLUGIN_NAME )
			),
			'cancel_dialog_title' => array(
				'value'			=> esc_html_x( 'Cancel the booking', 'Dialog title', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Cancel bookings dialog title.', BOOKACTI_PLUGIN_NAME )
			),
			'cancel_dialog_content' => array(
				'value'			=> esc_html__( 'Do you really want to cancel this booking?', BOOKACTI_PLUGIN_NAME ) . '
' . esc_html__( 'If you have already paid, you will be able to request a refund.', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Cancel bookings dialog content.', BOOKACTI_PLUGIN_NAME ),
				'input_type'	=> 'textarea'
			),
			'reschedule_dialog_button' => array(
				'value'			=> esc_html_x( 'Reschedule', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Reschedule booking button label.', BOOKACTI_PLUGIN_NAME )
			),
			'reschedule_dialog_title' => array(
				'value'			=> esc_html_x( 'Reschedule the booking', 'Dialog title', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Reschedule booking dialog title.', BOOKACTI_PLUGIN_NAME )
			),
			'refund_dialog_button' => array(
				'value'			=> esc_html_x( 'Request a refund', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Refund booking button label.', BOOKACTI_PLUGIN_NAME )
			),
			'refund_dialog_title' => array(
				'value'			=> esc_html_x( 'Request a refund', 'Dialog title', BOOKACTI_PLUGIN_NAME ),
				'description'	=> esc_html__( 'Refund booking dialog title.', BOOKACTI_PLUGIN_NAME )
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

		// Else, get message settings through a normal get_option
		else {
			$saved_messages	= get_option( 'bookacti_messages_settings' );
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
		
		return apply_filters( 'bookacti_messages', $messages, $raw );
	}
	
	
	/**
	 * Get a custom message by id
	 * 
	 * @since 1.2.0
	 * @version 1.3.0
	 * @param string $message_id
	 * @param boolean $raw Whether to retrieve the raw value from database or the option parsed through get_option
	 * @return string
	 */
	function bookacti_get_message( $message_id, $raw = false ) {
		$messages = bookacti_get_messages( $raw );
		
		$message = isset( $messages[ $message_id ] ) ? $messages[ $message_id ][ 'value' ] : '';
		
		if( ! $raw ) {
			$message = apply_filters( 'bookacti_translate_text', $message );
		}
		
		return $message;
	}



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
		$administrator->add_cap( 'bookacti_manage_forms' );
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
		$administrator->add_cap( 'bookacti_create_forms' );
		$administrator->add_cap( 'bookacti_edit_forms' );
		$administrator->add_cap( 'bookacti_delete_forms' );

		do_action( 'bookacti_set_capabilities' );
	}


	// Remove roles and capabilities
	function bookacti_unset_role_and_cap() {
		$administrator	= get_role( 'administrator' );
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
		$administrator->remove_cap( 'bookacti_create_bookings' );
		$administrator->remove_cap( 'bookacti_edit_bookings' );
		$administrator->remove_cap( 'bookacti_create_forms' );
		$administrator->remove_cap( 'bookacti_edit_forms' );
		$administrator->remove_cap( 'bookacti_delete_forms' );
		
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
					echo sprintf( __( 'Thank you for purchasing %s add-on!', BOOKACTI_PLUGIN_NAME ), 
								 '<strong>' . esc_html( __( 'Display Pack', BOOKACTI_PLUGIN_NAME ) ) . '</strong>' ); 
				?>
				</p><p>
					<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", BOOKACTI_PLUGIN_NAME ); ?>
				</p><p>
					<strong>
						<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-display-pack-add-on/prerequisite-installation-license-activation-of-display-pack-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-calendar' target='_blank' >
							<?php 
							/* translators: %s = add-on name */
								echo sprintf( __( 'How to activate %s license?', BOOKACTI_PLUGIN_NAME ), 
											  esc_html( __( 'Display Pack', BOOKACTI_PLUGIN_NAME ) ) ); 
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
				$addon_link = '<a href="https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=encart-promo-calendar" target="_blank" >';
				$addon_link .= esc_html( __( 'Display Pack', BOOKACTI_PLUGIN_NAME ) );
				$addon_link .= '</a>';
				/* transmators: %1$s is the placeholder for Display Pack add-on link */
				echo sprintf( esc_html( __( 'Get other essential customization options with %1$s add-on!', BOOKACTI_PLUGIN_NAME ) ), $addon_link ); 
				?>
				<div><a href='https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=encart-promo-calendar' class='button' target='_blank' ><?php esc_html_e( 'Learn more', BOOKACTI_PLUGIN_NAME ); ?></a></div>
			</div>
			<?php
		}
	}
	
	
	/**
	 * Display a promotional area for Notification Pack add-on
	 * @since 1.2.0
	 */
	function bookacti_display_banp_promo() {
		$is_plugin_active	= bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
		$license_status		= get_option( 'banp_license_status' );

		// If the plugin is activated but the license is not active yet
		if( $is_plugin_active && ( ! $license_status || $license_status !== 'valid' ) ) {
			?>
			<div id='bookacti-banp-promo' class='bookacti-addon-promo' >
				<p>
				<?php 
					/* translators: %s = add-on name */
					echo sprintf( __( 'Thank you for purchasing %s add-on!', BOOKACTI_PLUGIN_NAME ), 
								 '<strong>' . esc_html__( 'Notification Pack', BOOKACTI_PLUGIN_NAME ) . '</strong>' ); 
				?>
				</p><p>
					<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", BOOKACTI_PLUGIN_NAME ); ?>
				</p><p>
					<strong>
						<a href='https://booking-activities.fr/en/docs/user-documentation/notification-pack/prerequisite-installation-license-activation-notification-pack-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-settings' target='_blank' >
							<?php 
							/* translators: %s = add-on name */
								echo sprintf( __( 'How to activate %s license?', BOOKACTI_PLUGIN_NAME ), 
											  esc_html__( 'Notification Pack', BOOKACTI_PLUGIN_NAME ) ); 
							?>
						</a>
					</strong>
				</p>
			</div>
			<?php
		}

		else if( ! $license_status || $license_status !== 'valid' ) {
			?>
			<div id='bookacti-banp-promo' class='bookacti-addon-promo' >
				<?php 
				$addon_link = '<strong><a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=encart-promo-settings" target="_blank" >';
				$addon_link .= esc_html__( 'Notification Pack', BOOKACTI_PLUGIN_NAME );
				$addon_link .= '</a></strong>';
				/* translators: %1$s is the placeholder for Notification Pack add-on link */
				echo sprintf( esc_html__( 'You can send all these notifications and booking reminders via email, SMS and Push with %1$s add-on!', BOOKACTI_PLUGIN_NAME ), $addon_link ); 
				?>
				<div><a href='https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=encart-promo-settings' class='button' target='_blank' ><?php esc_html_e( 'Learn more', BOOKACTI_PLUGIN_NAME ); ?></a></div>
			</div>
			<?php
		}
	}