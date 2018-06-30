<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Init Booking Activities settings
 * 
 * @version 1.4.0
 */
function bookacti_init_settings() { 

	/* General settings Section */
	add_settings_section( 
		'bookacti_settings_section_general',
		__( 'General settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_general_callback',
		'bookacti_general_settings'
	);
	
	add_settings_field(  
		'booking_method', 
		__( 'Booking method', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_booking_method_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'when_events_load', 
		__( 'When to load the events?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_when_events_load_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'event_load_interval', 
		__( 'Load events every', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_event_load_interval_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);

	add_settings_field(  
		'started_events_bookable', 
		__( 'Are started events bookable?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_started_events_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'started_groups_bookable', 
		__( 'Are started groups of events bookable?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_started_groups_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'availability_period_start', 
		/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
		__( 'Events will be bookable in', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_availability_period_start_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'availability_period_end', 
		/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events are bookable for up to 30 days from today". */
		__( 'Events are bookable for up to', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_availability_period_end_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_booking_state', 
		__( 'Default booking state', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_booking_state_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_payment_status', 
		__( 'Default payment status', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_payment_status_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'timezone', 
		__( 'Calendars timezone', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_timezone_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_calendar_view_threshold', 
		__( 'Responsive calendar view threshold', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_calendar_view_threshold_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	
	
	/* Cancellation settings Section */
	add_settings_section( 
		'bookacti_settings_section_cancellation',
		__( 'Cancellation settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_cancellation_callback',
		'bookacti_cancellation_settings'
	);
	
	add_settings_field(  
		'allow_customers_to_cancel',                      
		__( 'Allow customers to cancel their bookings', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_activate_cancel_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'allow_customers_to_reschedule',                      
		__( 'Allow customers to reschedule their bookings', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_activate_reschedule_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'cancellation_min_delay_before_event', 
		/* translators: Followed by a field indicating a number of days before the event. E.g.: "Changes permitted up to 2 days before the event". */
		__( 'Changes permitted up to', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cancellation_delay_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'refund_actions_after_cancellation',                      
		__( 'Possible actions customers can take to be refunded', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cancellation_refund_actions_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	
	
	/* Notifications settings Section - 1 - General settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_general',
		__( 'Notifications', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_notifications_general_callback',
		'bookacti_notifications_settings'
	);
	
	add_settings_field( 
		'notifications_async',
		__( 'Asynchronous notifications', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_async_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_general'
	);
	
	
	/* Notifications settings Section - 2 - Email settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_email',
		__( 'Email notifications settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_notifications_email_callback',
		'bookacti_notifications_settings'
	);
		
	add_settings_field( 
		'notifications_from_name',
		__( 'From name', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_from_name_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	add_settings_field( 
		'notifications_from_email',
		__( 'From email', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_from_email_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	
	
	/* Messages settings Section */
	add_settings_section( 
		'bookacti_settings_section_messages',
		__( 'Messages', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_messages_callback',
		'bookacti_messages_settings'
	);
	
	
	do_action( 'bookacti_add_settings' );
	
	
	register_setting( 'bookacti_general_settings',			'bookacti_general_settings' );
	register_setting( 'bookacti_cancellation_settings',		'bookacti_cancellation_settings' );
	register_setting( 'bookacti_notifications_settings',	'bookacti_notifications_settings' );
	register_setting( 'bookacti_messages_settings',			'bookacti_messages_settings' );
}
add_action( 'admin_init', 'bookacti_init_settings' );



// SCREEN OPTIONS

/**
 * Add screen options
 * 
 * @since 1.3.0
 * @version 1.5.0
 */
function bookacti_add_screen_options() {
	add_action( 'load-booking-activities_page_bookacti_bookings', 'bookacti_display_bookings_screen_options' );
	add_action( 'load-booking-activities_page_bookacti_forms', 'bookacti_display_forms_screen_options' );
}
add_action( 'admin_menu', 'bookacti_add_screen_options', 20 );


/**
 * Add booking page columns screen options
 * @since 1.3.0
 */
function bookacti_add_booking_page_screen_option() {
	new Bookings_List_Table();
}
add_action( 'admin_head-booking-activities_page_bookacti_bookings', 'bookacti_add_booking_page_screen_option' );


/**
 * Add form page columns screen options
 * @since 1.5.0
 */
function bookacti_add_form_page_screen_option() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) {
		new Forms_List_Table();
	}
}
add_action( 'admin_head-booking-activities_page_bookacti_forms', 'bookacti_add_form_page_screen_option' );


/**
 * Save screen options
 * @since 1.5.0 (was bookacti_save_bookings_screen_options)
 */
function bookacti_save_screen_options( $status, $option, $value ) {
	if( 'bookacti_bookings_per_page' == $option || 'bookacti_forms_per_page' == $option ) {
		return $value;
	}
	return $status;
}
add_filter( 'set-screen-option', 'bookacti_save_screen_options', 10, 3 );



// NOTIFICATIONS

/**
 * Create a settings page for each notification
 * 
 * @since 1.2.1 (was bookacti_fill_notifications_settings_section in 1.2.1)
 * @param string $notification_id
 */
function bookacti_fill_notification_settings_page( $notification_id ) {
	
	if( ! $notification_id ) { return; }
	
	$notification_settings = bookacti_get_notification_settings( $notification_id );
	?>

		<h2><?php echo __( 'Notification', BOOKACTI_PLUGIN_NAME ) . ' - ' . $notification_settings[ 'title' ]; ?></h2>

		<p>
			<a href='<?php echo esc_url( '?page=bookacti_settings&tab=notifications' ); ?>' >
				<?php _e( 'Go back to notifications settings', BOOKACTI_PLUGIN_NAME ); ?>
			</a>
		</p>

		<p><?php echo $notification_settings[ 'description' ]; ?></p>

		<?php do_action( 'bookacti_notification_settings_page_before', $notification_settings, $notification_id ); ?>

		<h3><?php _e( 'Global notifications settings', BOOKACTI_PLUGIN_NAME ); ?></h3>
		<table class='form-table' id='bookacti-notification-global-settings' >
			<tbody>
				<tr>
					<th scope='row' ><?php _e( 'Enable', BOOKACTI_PLUGIN_NAME ); ?></th>
					<td>
						<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'bookacti_notification[active]',
							'id'	=> 'bookacti_notification_' . $notification_id . 'active',
							'value'	=> $notification_settings[ 'active' ] ? $notification_settings[ 'active' ] : 0,
							'tip'	=> __( 'Enable or disable this automatic notification.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<?php do_action( 'bookacti_notification_settings_page_global', $notification_settings, $notification_id ); ?>
			</tbody>
		</table>

		<h3><?php _e( 'Email notifications settings', BOOKACTI_PLUGIN_NAME ); ?></h3>
		<table class='form-table' id='bookacti-notification-email-settings' >
			<tbody>
				<?php 

				do_action( 'bookacti_notification_settings_page_email_before', $notification_settings, $notification_id );

				if( substr( $notification_id, 0, 8 ) !== 'customer' ) { ?>
				<tr>
					<th scope='row' ><?php _e( 'Recipient(s)', BOOKACTI_PLUGIN_NAME ); ?></th>
					<td>
						<?php
						$args = array(
							'type'	=> 'text',
							'name'	=> 'bookacti_notification[email][to]',
							'id'	=> 'bookacti_notification_' . $notification_id . '_email_to',
							'value'	=> is_array( $notification_settings[ 'email' ][ 'to' ] ) ? implode( ',', $notification_settings[ 'email' ][ 'to' ] ) : strval( $notification_settings[ 'email' ][ 'to' ] ),
							'tip'	=> __( 'Recipient(s) email address(es) (comma separated).', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th scope='row' ><?php _ex( 'Subject', 'email subject', BOOKACTI_PLUGIN_NAME ); ?></th>
					<td>
						<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'bookacti_notification[email][subject]',
							'id'	=> 'bookacti_notification_' . $notification_id . '_email_subject',
							'value'	=> $notification_settings[ 'email' ][ 'subject' ] ? $notification_settings[ 'email' ][ 'subject' ] : '',
							'tip'	=> __( 'The email subject.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<tr>
					<th scope='row' >
					<?php 
						_ex( 'Email content', 'email message', BOOKACTI_PLUGIN_NAME ); 
						$tags = bookacti_get_notifications_tags( $notification_id );
						if( $tags ) {
					?>
						<div class='bookacti-notifications-tags-list' >
							<p><?php _e( 'Use these tags:', BOOKACTI_PLUGIN_NAME ); ?></p>
					<?php
							foreach( $tags as $tag => $tip ) {
					?>
								<div class='bookacti-notifications-tag' >
									<code><?php echo $tag; ?></code>
									<?php bookacti_help_tip( $tip ); ?>
								</div>
					<?php
							}
							
							// Notification Pack promo
							$is_plugin_active = bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
							if( ! $is_plugin_active ) {
								$addon_link  = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >';
								$addon_link .= esc_html__( 'Notification Pack', BOOKACTI_PLUGIN_NAME );
								$addon_link .= '</a>';
								/* translators: %1$s is the placeholder for Notification Pack add-on link */
								$tip = sprintf( esc_html__( 'You can set a specific message on events, activities, groups of events and group categories and use it in your notifications thanks to %1$s add-on.', BOOKACTI_PLUGIN_NAME ), $addon_link );
								?>
								<div class='bookacti-notifications-tag' >
									<code class='bookacti-notifications-tag-promo' >{specific_message}</code>
									<?php bookacti_help_tip( $tip ); ?>
								</div>
								<?php
							}
							
					?>
						</div>
					<?php
						}
					?>
					</th>
					<td>
						<?php 
						$args = array(
							'type'	=> 'editor',
							'name'	=> 'bookacti_notification[email][message]',
							'id'	=> 'bookacti_notification_' . $notification_id . '_email_message',
							'value'	=> $notification_settings[ 'email' ][ 'message' ] ? $notification_settings[ 'email' ][ 'message' ] : ''
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<?php do_action( 'bookacti_notification_settings_page_email_after', $notification_settings, $notification_id ); ?>
			</tbody>
		</table>
	<?php
	do_action( 'bookacti_notification_settings_page_after', $notification_settings, $notification_id );
}
add_action( 'bookacti_notification_settings_page', 'bookacti_fill_notification_settings_page', 10, 1 );


/**
 * Update notifications data
 * 
 * @since 1.2.0
 * @version 1.2.1
 */
function bookacti_controller_update_notification() {
	
	$option_page = sanitize_title_with_dashes( $_POST[ 'option_page' ] );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( $option_page, '_wpnonce', false );
	$is_allowed		= current_user_can( 'bookacti_manage_booking_activities_settings' );

	if( $is_nonce_valid && $is_allowed ) {
		
		if( ! $_POST[ 'bookacti_notification' ] || ! $_POST[ 'notification_id' ] ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'missing_data' ) );
		}
		
		// Sanitize values
		$notification_settings = bookacti_sanitize_notification_settings( $_POST[ 'bookacti_notification' ], $_POST[ 'notification_id' ] );
		$updated = update_option( $option_page, $notification_settings );
		
		if( $updated ) {
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	}
	
	wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
}
add_action( 'wp_ajax_bookactiUpdateNotification', 'bookacti_controller_update_notification' );


/**
 * Ensure backward compatibility between 1.2.0 and 1.2.1+
 * 
 * @version 1.4.3
 */
function bookacti_format_old_notifications_settings( $old_version ) {
	if( version_compare( $old_version, '1.2.1', '<' ) ) {
		// get all db options
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		
		// Convert async setting format
		if( isset( $alloptions[ 'bookacti_notifications_settings' ] ) ) {
			$notifications_settings = maybe_unserialize( $alloptions[ 'bookacti_notifications_settings' ] );
			if( isset( $notifications_settings[ 'notifications_async_email' ] ) ) {
				// Put the old value in the new setting
				$notifications_settings[ 'notifications_async' ] = $notifications_settings[ 'notifications_async_email' ];
				unset( $notifications_settings[ 'notifications_async_email' ] );
				
				// Add new notification settings and delete the old one
				delete_option( 'bookacti_notifications_settings' );
				add_option( 'bookacti_notifications_settings', $notifications_settings );
			}
		}
		
		// Convert notification settings format
		$notification_ids = array_keys( bookacti_get_notifications_default_settings() );
		foreach( $notification_ids as $notification_id ) {
			// If old notifications settings exists, convert it to the new format
			if( ! isset( $alloptions[ 'bookacti_notifications_settings_email_' . $notification_id ] ) ) { continue; }
			
			// Get notifications settings
			$notification_settings	= maybe_unserialize( $alloptions[ 'bookacti_notifications_settings_email_' . $notification_id ] );
			$new_settings			= $notification_settings;
			
			if( ! $notification_settings ) { continue; }
			
			// If the format is already the good one, stop processing
			if( isset( $notification_settings[ 'email' ] ) ) { continue; }
			
			// Transform old notification settings format into the new one
			$new_settings[ 'id' ] = $notification_id;
			
			if( isset( $notification_settings[ 'title' ] ) ) { unset( $new_settings[ 'title' ] ); }
			
			$new_settings[ 'email' ] = array();
			$new_settings[ 'email' ][ 'active' ] = 1;
			if( isset( $notification_settings[ 'to' ] ) )		{ $new_settings[ 'email' ][ 'to' ] = $notification_settings[ 'to' ]; unset( $new_settings[ 'to' ] ); }
			if( isset( $notification_settings[ 'subject' ] ) )	{ $new_settings[ 'email' ][ 'subject' ] = $notification_settings[ 'subject' ]; unset( $new_settings[ 'subject' ] ); }
			if( isset( $notification_settings[ 'message' ] ) )	{ $new_settings[ 'email' ][ 'message' ] = $notification_settings[ 'message' ]; unset( $new_settings[ 'message' ] ); }
			
			// Add new notification settings and delete the old one
			add_option( 'bookacti_notifications_settings_' . $notification_id, $new_settings );
			delete_option( 'bookacti_notifications_settings_email_' . $notification_id );		
		}
	}
}
add_action( 'bookacti_updated', 'bookacti_format_old_notifications_settings', 10, 1 );




// MESSAGES

/**
 * Display messages fields
 * 
 * @since 1.2.0
 * @version 1.3.0
 */
function bookacti_display_messages_fields() {
	$messages = bookacti_get_messages( true );
	foreach( $messages as $message_id => $message ) {
?>
		<div class='bookacti-message-setting' >
			<em><?php echo $message[ 'description' ] ?></em><br/>
			<?php if( isset( $message[ 'input_type' ] ) && $message[ 'input_type' ] === 'textarea' ) { ?>
			<textarea id='bookacti_messages_settings_<?php echo $message_id; ?>' name='bookacti_messages_settings[<?php echo $message_id; ?>]' ><?php echo $message[ 'value' ]; ?></textarea>
			<?php } else { ?>
				<input type='text' id='bookacti_messages_settings_<?php echo $message_id; ?>' name='bookacti_messages_settings[<?php echo $message_id; ?>]' value='<?php echo $message[ 'value' ]; ?>' />
			<?php } ?>
		</div>
<?php
	}
}
add_action( 'bookacti_messages_settings', 'bookacti_display_messages_fields' );



// CUSTOM LINKS

/** 
 * Add actions to Booking Activities in plugins list
 * 
 * @param array $links
 * @return array
 */
function bookacti_action_links_in_plugins_table( $links ) {
   $links = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=bookacti_settings' ) . '" title="' . esc_attr( __( 'Manage Booking Activities Settings', BOOKACTI_PLUGIN_NAME ) ) . '">' . __( 'Settings', BOOKACTI_PLUGIN_NAME ) . '</a>' ) + $links;
   return $links;
}
add_filter( 'plugin_action_links_' . BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '.php', 'bookacti_action_links_in_plugins_table', 10, 1 );


/** 
 * Add meta links in plugins list
 * 
 * @param array $links
 * @param string $file
 * @return string
 */
function bookacti_meta_links_in_plugins_table( $links, $file ) {
   if ( $file == BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '.php' ) {
		$links[ 'docs' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_user_docs_url',	'https://booking-activities.fr/en/documentation/user-documentation/?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) ) . '" title="' . esc_attr( __( 'View Booking Activities Documentation', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Docs', BOOKACTI_PLUGIN_NAME ) . '</a>';
		$links[ 'report' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_report_url',		'https://github.com/bookingactivities/booking-activities/issues/' ) ) . '" title="' . esc_attr( __( 'Report a bug or request a feature', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Report & Request', BOOKACTI_PLUGIN_NAME ) . '</a>';
		$links[ 'contact' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_contact_url',		'https://booking-activities.fr/en/#contact?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) ) . '" title="' . esc_attr( __( 'Contact us directly', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Contact us', BOOKACTI_PLUGIN_NAME ) . '</a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'bookacti_meta_links_in_plugins_table', 10, 2 );



// ADMIN PROMO NOTICES

/** 
 * Ask to rate the plugin 5 stars
 */
function bookacti_5stars_rating_notice() {
	$dismissed = get_option( 'bookacti-5stars-rating-notice-dismissed' );
	if( ! $dismissed ) {
		if( current_user_can( 'bookacti_manage_booking_activities' ) ) {
			$install_date = get_option( 'bookacti-install-date' );
			if( ! empty( $install_date ) ) {
				$install_datetime	= DateTime::createFromFormat( 'Y-m-d H:i:s', $install_date );
				$current_datetime	= new DateTime();
				$nb_days			= floor( $install_datetime->diff( $current_datetime )->days );
				if( $nb_days >= 31 ) {
					?>
					<div class='notice notice-info bookacti-5stars-rating-notice is-dismissible' >
						<p>
							<?php 
							_e( '<strong>Booking Activities</strong> has been helping you for one month now.', BOOKACTI_PLUGIN_NAME );
							/* translators: %s: five stars */
							echo '<br/>' 
								. sprintf( esc_html__( 'Would you help it back leaving us a %s rating? We need you now to make it last!', BOOKACTI_PLUGIN_NAME ), 
								  '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
							?>
						</p>
						<p>
							<a class='button' href='<?php echo esc_url( 'https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post' ); ?>' target='_blank' ><?php esc_html_e( "Ok, I'll rate you five stars!", BOOKACTI_PLUGIN_NAME ); ?></a>
							<span class='button' id='bookacti-dismiss-5stars-rating' ><?php esc_html_e( "I already rated you, hide this message", BOOKACTI_PLUGIN_NAME ); ?></span>
						</p>
					</div>
					<?php
				}
			}
		}
	}
}
add_action( 'all_admin_notices', 'bookacti_5stars_rating_notice' );


/**
 *  Remove Rate-us-5-stars notice
 */
function bookacti_dismiss_5stars_rating_notice() {
	
	// Check nonce, no need to check capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_dismiss_5stars_rating_notice', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_manage_booking_activities' );

	if( $is_nonce_valid && $is_allowed ) {
	
		$updated = update_option( 'bookacti-5stars-rating-notice-dismissed', 1 );
		if( $updated ) {
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiDismiss5StarsRatingNotice', 'bookacti_dismiss_5stars_rating_notice' );


/**
 * Display a custom message in the footer
 * 
 * @param string $footer_text
 * @return string
 */
function bookacti_admin_footer_text( $footer_text ) {
	if ( ! current_user_can( 'bookacti_manage_booking_activities' ) || ! function_exists( 'bookacti_get_screen_ids' ) ) {
		return $footer_text;
	}
	
	$current_screen	= get_current_screen();
	$bookacti_pages	= bookacti_get_screen_ids();
	
	// Check to make sure we're on a BA admin page.
	if ( isset( $current_screen->id ) && in_array( $current_screen->id, $bookacti_pages ) ) {
		// Change the footer text
		if ( ! get_option( 'woocommerce_admin_footer_text_rated' ) ) {
			/* translators: %s: five stars */
			$footer_text = sprintf( __( 'If <strong>Booking Activities</strong> helps you, help it back leaving us a %s rating. We need you now to make it last!', BOOKACTI_PLUGIN_NAME ), '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
		}
	}

	return $footer_text;
}
add_filter( 'admin_footer_text', 'bookacti_admin_footer_text', 10, 1 );