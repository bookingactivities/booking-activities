<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add missing permanent notifications to the database when the plugin is activated
 * @since 1.18.0
 */
add_action( 'bookacti_activate', 'bookacti_create_missing_permanent_notifications', 10 );
add_action( 'bookacti_activate', 'bookacti_migrate_legacy_notifications', 20 );


/**
 * Register a daily cron event to clean notification logs
 * @since 1.7.1
 */
function bookacti_register_cron_event_to_clean_latest_notifications() {
	if( ! wp_next_scheduled ( 'bookacti_clean_latest_notifications' ) ) {
		wp_schedule_event( time(), 'daily', 'bookacti_clean_latest_notifications' );
	}
}
add_action( 'bookacti_activate', 'bookacti_register_cron_event_to_clean_latest_notifications' );


/**
 * Deregister the daily cron event to clean notification logs
 * @since 1.7.1 (was bookacti_clear_houly_clean_expired_bookings)
 */
function bookacti_deregister_cron_event_to_clean_latest_notifications() {
	wp_clear_scheduled_hook( 'bookacti_clean_latest_notifications' );
}
add_action( 'bookacti_deactivate', 'bookacti_deregister_cron_event_to_clean_latest_notifications' );


/**
 * Controller - Send async notifications
 * @since 1.16.0
 * @version 1.16.45
 */
function bookacti_controller_send_async_notifications() {
	// Do not send on AJAX calls to avoid multiple calls and expired cache
	$is_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	if( $is_ajax ) { return; }
	
	// Check if CRON is running (so it doesn't slow down any users)
	if( ! wp_doing_cron() ) { return; }
	
	// Check if the desired action is to send the async notifications
	if( empty( $_REQUEST[ 'bookacti_send_async_notifications' ] ) ) { return; }
	
	// Check if the key is correct
	if( empty( $_REQUEST[ 'key' ] ) ) { return; }
	$sanitized_key = sanitize_title_with_dashes( $_REQUEST[ 'key' ] );
	$secret_key    = get_option( 'bookacti_cron_key' );
	if( $sanitized_key !== $secret_key ) { return; }
	
	// Check if async notifications are allowed
	$allow_async = apply_filters( 'bookacti_allow_async_notifications', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ) );
	if( ! $allow_async ) { return; }
	
	bookacti_send_async_notifications();
}
add_action( 'init', 'bookacti_controller_send_async_notifications', 100 );


/**
 * Send async notifications
 * @since 1.16.0
 * @version 1.16.37
 */
function bookacti_send_async_notifications() {
	$nb_sent = array();
	
	// Make sure to run this function once per page load
	if( defined( 'BOOKACTI_SENDING_ASYNC_NOTIFICATIONS' ) ) { return $nb_sent; }
	define( 'BOOKACTI_SENDING_ASYNC_NOTIFICATIONS', 1 );
	
	$alloptions = wp_load_alloptions();
	$async_notifications = isset( $alloptions[ 'bookacti_async_notifications' ] ) ? maybe_unserialize( $alloptions[ 'bookacti_async_notifications' ] ) : get_option( 'bookacti_async_notifications', array() );
	
	// Remove the async notifications from db right after retrieving them
	update_option( 'bookacti_async_notifications', array() );
	
	if( ! $async_notifications ) { return $nb_sent; }
	
	// Try to merge the notifications sent to the same user
	$merging_allowed = apply_filters( 'bookacti_async_notifications_merging_allowed', true, $async_notifications );
	if( $merging_allowed ) {
		$async_notifications = bookacti_merge_planned_notifications( $async_notifications );
	}
	
	// If there are a lot of notifications to send, this operation can take a while
	// So we need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 'send_async_notifications' );
	
	// Send the notifications
	foreach( $async_notifications as $async_notification ) {
		bookacti_send_notification( $async_notification[ 'notification_id' ], $async_notification[ 'booking_id' ], $async_notification[ 'booking_type'], $async_notification[ 'args' ], 0 );
	}
}
add_action( 'bookacti_cron_send_async_notifications', 'bookacti_send_async_notifications', 10 );


/**
 * Clean the latest emails logs
 * @since 1.7.1
 */
function bookacti_clean_latest_emails_log() {
	$latest_emails_sent = get_option( 'bookacti_latest_emails_sent' );
	if( ! $latest_emails_sent ) { return; }
	
	$current_datetime	= new DateTime( 'now' );
	$yesterday_datetime	= clone $current_datetime;
	$yesterday_datetime->sub( new DateInterval( 'P1D' ) );
	
	foreach( $latest_emails_sent as $recipient => $emails_sent ) {
		// Remove values before yesterday
		foreach( $emails_sent as $i => $email_sent ) {
			$email_datetime = new DateTime( $email_sent );
			if( $email_datetime < $yesterday_datetime ) {
				unset( $latest_emails_sent[ $recipient ][ $i ] );
			}
		}
		// Remove the whole recipient array if no emails have been sent to him since yesterday
		if( empty( $latest_emails_sent[ $recipient ] ) ) {
			unset( $latest_emails_sent[ $recipient ] );
		}
	}
	
	update_option( 'bookacti_latest_emails_sent', $latest_emails_sent );
}
add_action( 'bookacti_clean_latest_notifications', 'bookacti_clean_latest_emails_log' );


/**
 * Send a notification to admin and customer when a new booking is made
 * @since 1.2.2 (was bookacti_send_notification_admin_new_booking in 1.2.1)
 * @version 1.16.45
 * @param array $return_array
 * @param array $booking_form_values
 * @param int $form_id
 */
function bookacti_send_notification_when_booking_is_made( $return_array, $booking_form_values, $form_id ) {
	foreach( $return_array[ 'bookings' ] as $booking ) {
		$booking_data = array();
		if( $booking[ 'type' ] === 'group' ) {
			$booking_data = bookacti_sanitize_booking_group_data( array_merge( array( 
				'event_group_id' => $booking[ 'picked_event' ][ 'group_id' ],
				'group_date'     => $booking[ 'picked_event' ][ 'group_date' ],
				'grouped_events' => $booking[ 'picked_event' ][ 'events' ]
			), $booking_form_values ) );
		} else {
			$booking_data = bookacti_sanitize_booking_data( array_merge( array(
				'event_id'    => $booking[ 'picked_event' ][ 'events' ][ 0 ][ 'id' ],
				'event_start' => $booking[ 'picked_event' ][ 'events' ][ 0 ][ 'start' ],
				'event_end'   => $booking[ 'picked_event' ][ 'events' ][ 0 ][ 'end' ],
			), $booking_form_values ) );
		}
		$booking_data[ 'id' ] = $booking[ 'id' ];
		
		// Send a booking confirmation to admin and customers
		bookacti_send_booking_status_change_notification( 'new', (object) $booking_data, null, $booking[ 'type' ] );
		bookacti_send_booking_status_change_notification( $booking_data[ 'status' ], (object) $booking_data, null, $booking[ 'type' ] );
	}
}
add_action( 'bookacti_booking_form_validated', 'bookacti_send_notification_when_booking_is_made', 100, 3 );


/**
 * Format some rescheduled notifications tags
 * @since 1.10.0
 * @version 1.16.0
 * @param array $tags
 * @param object $booking
 * @param string $booking_type
 * @param array $notification
 * @param array $args
 * @return array
 */
function bookacti_format_reschedule_notifications_tags_values( $tags, $booking, $booking_type, $notification, $args ) {
	if( strpos( $notification[ 'id' ], '_rescheduled' ) === false ) { return $tags; }
	
	// Set the {booking_old_start} and {booking_old_end} from their unformatted counterpart
	$datetime_format = bookacti_get_message( 'date_format_long' );
	if( isset( $tags[ '{booking_old_start_raw}' ] ) ) { $tags[ '{booking_old_start}' ] = bookacti_format_datetime( $tags[ '{booking_old_start_raw}' ], $datetime_format ); }
	if( isset( $tags[ '{booking_old_end_raw}' ] ) )   { $tags[ '{booking_old_end}' ]   = bookacti_format_datetime( $tags[ '{booking_old_end_raw}' ], $datetime_format ); }
	
	return $tags;
}
add_filter( 'bookacti_notifications_tags_values', 'bookacti_format_reschedule_notifications_tags_values', 10, 5 );


/**
 * Display private columns of bookings list in notifications
 * @since 1.8.6
 * @param boolean $allowed
 * @param array $filters
 * @param array $columns
 * @return boolean
 */
function bookacti_display_private_columns_in_notifications( $allowed, $filters, $columns ) {
	if( ! empty( $GLOBALS[ 'bookacti_notification_private_columns' ] ) ) { $allowed = 1; }
	return $allowed;
}
add_filter( 'bookacti_user_booking_list_display_private_columns', 'bookacti_display_private_columns_in_notifications', 10, 3 );
add_filter( 'bookacti_user_booking_list_can_manage_bookings', 'bookacti_display_private_columns_in_notifications', 10, 3 );




// NOTIFICATION EDIT PAGE

/**
 * Display Notifications page options in screen options area
 * @since 1.18.0
 */
function bookacti_display_notifications_screen_options() {
	$screen = get_current_screen();

	// Don't do anything if we are not on the booking page
	if( ! is_object( $screen ) || $screen->id != 'booking-activities_page_bookacti_notifications' ) { return; }

	if( ! empty( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) {
		// Layout columns number
		add_screen_option( 'layout_columns', array( 
			'max' => 2, 
			'default' => 2 
		));
	} else {
		// Bookings per page
		add_screen_option( 'per_page', array(
			'label' => __( 'Notifications per page:', 'booking-activities' ),
			'default' => 20,
			'option' => 'bookacti_notifications_per_page'
		));
	}
}


/**
 * Add notification edit page meta boxes
 * @since 1.18.0
 */
function bookacti_notification_editor_meta_boxes() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
	// Main
	add_meta_box( 'bookacti_notification_global', esc_html__( 'Global settings', 'booking-activities' ), 'bookacti_display_notification_global_meta_box', 'booking-activities_page_bookacti_notifications', 'normal', 'high' );
	add_meta_box( 'bookacti_notification_email', esc_html__( 'Email', 'booking-activities' ), 'bookacti_display_notification_email_meta_box', 'booking-activities_page_bookacti_notifications', 'normal', 'default' );
	
	// Sidebar
	add_meta_box( 'bookacti_notification_publish', esc_html__( 'Publish' ), 'bookacti_display_notification_publish_meta_box', 'booking-activities_page_bookacti_notifications', 'side', 'high' );
	add_meta_box( 'bookacti_notification_managers', esc_html__( 'Managers', 'booking-activities' ), 'bookacti_display_notification_managers_meta_box', 'booking-activities_page_bookacti_notifications', 'side', 'default' );
	add_meta_box( 'bookacti_notification_tags', esc_html__( 'Tags', 'booking-activities' ), 'bookacti_display_notification_tags_meta_box', 'booking-activities_page_bookacti_notifications', 'side', 'default' );
}
add_action( 'add_meta_boxes_booking-activities_page_bookacti_notifications', 'bookacti_notification_editor_meta_boxes' );


/**
 * Allow metaboxes on notification edit page
 * @since 1.18.0
 */
function bookacti_allow_meta_boxes_in_notification_editor() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action( 'add_meta_boxes_booking-activities_page_bookacti_notifications', null );
    do_action( 'add_meta_boxes', 'booking-activities_page_bookacti_notifications', null );
	
	/* Enqueue WordPress' script for handling the meta boxes */
	if( wp_script_is( 'postbox', 'registered' ) ) { wp_enqueue_script( 'postbox' ); }
}
add_action( 'load-booking-activities_page_bookacti_notifications', 'bookacti_allow_meta_boxes_in_notification_editor' );


/**
 * Print metabox script to make it work on notification edit page
 * @since 1.18.0
 */
add_action( 'admin_footer-booking-activities_page_bookacti_notifications', 'bookacti_print_metabox_script' );


/**
 * Display 'Publish' metabox content in notification edit page
 * @since 1.18.0
 * @param array $notification_edit
 */
function bookacti_display_notification_publish_meta_box( $notification_edit ) {
	$is_new = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'new';
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions'>
			<?php
				do_action( 'bookacti_notification_publish_meta_box_before', $notification_edit );
			?>
			<div id='publishing-action'>
				<span class='spinner'></span>
				<input id='bookacti-save-notification-button' name='save' type='submit' class='button button-primary button-large' id='publish' value='<?php echo $is_new || $notification_edit[ 'status' ] === 'trash' ? esc_attr__( 'Publish' ) : esc_attr__( 'Update' ); ?>'/>
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}


/**
 * Display 'Managers' metabox content in notification edit page
 * @since 1.18.0
 * @param array $notification_edit
 */
function bookacti_display_notification_managers_meta_box( $notification_edit ) {
	$manager_ids  = ! empty( $notification_edit[ 'db_id' ] ) ? bookacti_get_notification_managers( $notification_edit[ 'db_id' ] ) : array();
	$capabilities = array( 'bookacti_edit_notifications' );
	$role_in      = apply_filters( 'bookacti_managers_roles', array_merge( bookacti_get_roles_by_capabilities( $capabilities ), $capabilities ), 'notification' );
	$role_not_in  = apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ), 'notification' );
	
	$fields = array( 'managers' => array( 
		'type'      => 'user_id', 
		'name'      => 'managers',
		'id'        => 'bookacti-notification-managers', 
		'fullwidth' => 1, 
		'options'   => array(
			'option_label' => array( 'display_name', ' (', 'user_login', ')' ),
			'selected'     => $manager_ids,
			'role__in'     => $role_in ? $role_in : array( 'none' ),
			'role__not_in' => $role_not_in,
			'meta'         => false,
			'multiple'     => 1,
			'ajax'         => 0
		),
		'title'     => esc_html__( 'Who can manage this notification?', 'booking-activities' ),
		'tip'       => esc_html__( 'Choose who is allowed to access this notification.', 'booking-activities' )
					. '<br/>' . sprintf( esc_html__( 'These roles already have this privilege: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', array_intersect_key( bookacti_get_roles(), array_flip( $role_not_in ) ) ) . '</code>' )
					. '<br/>' . sprintf( esc_html__( 'If the selectbox is empty, it means that no other users have these capabilities: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', $capabilities ) . '</code>' )
					. ' ' . sprintf( esc_html__( 'If you want to grant a user these capabilities, use a plugin such as %1$s.', 'booking-activities' ), '<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>' )
	) );
	bookacti_display_fields( $fields );
}


/**
 * Display 'Tags' metabox content in notification edit page
 * @since 1.18.0
 * @param array $notification_edit
 */
function bookacti_display_notification_tags_meta_box( $notification_edit ) {
	$tags = bookacti_get_notifications_tags( $notification_edit[ 'id' ] );
	if( $tags ) { ?>
		<p><?php esc_html_e( 'You can use the following tags in the notification subject and content:', 'booking-activities' ); ?></p>
		<div class='bookacti-notification-tags'>
		<?php
			foreach( $tags as $tag => $tip ) {
			?>
				<span class='bookacti-notification-tag'>
					<input type='text' onfocus='this.select(); document.execCommand("Copy");' readonly='readonly' value='<?php echo esc_attr( $tag ); ?>' class='code bookacti-tip' data-tip='<?php echo esc_attr( $tip ); ?>'/>
				</span>
			<?php
			}

			// Notification Pack promo
			$is_plugin_active = bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
			if( ! $is_plugin_active ) {
				$addon_link  = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >Notification Pack</a>';
				/* translators: %1$s is the placeholder for Notification Pack add-on link */
				$tip = sprintf( esc_html__( 'You can set a specific message on events, activities, groups of events and group categories and use it in your notifications thanks to %1$s add-on.', 'booking-activities' ), $addon_link );
			?>
				<span class='bookacti-notification-tag'>
					<code class='bookacti-notification-tag-promo bookacti-tip' data-tip='<?php echo esc_attr( $tip ); ?>'>{specific_message}</code>
				</span>
			<?php
			}
		?>
		</div>
	<?php }
}


/**
 * Display 'Global settings' metabox content in notification edit page
 * @since 1.18.0
 * @param array $notification_edit
 */
function bookacti_display_notification_global_meta_box( $notification_edit ) {
	$is_new = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'new';
	
	// Get trigger options
	$notifications_default_values = bookacti_get_notifications_default_values();
	$trigger_options              = array();
	foreach( $notifications_default_values as $notification_default_values ) {
		$trigger = ! empty( $notification_default_values[ 'trigger' ] ) ? $notification_default_values[ 'trigger' ] : '';
		if( ! $trigger ) { continue; }

		$trigger_options[ $trigger ] = ! empty( $notification_default_values[ 'title' ] ) ? $notification_default_values[ 'title' ] : $trigger;
	}
?>
	<div id='bookacti-notification-global-fields'>
	<?php 
		do_action( 'bookacti_notification_global_fields_before', $notification_edit );
		
		bookacti_display_fields( apply_filters( 'bookacti_notification_global_fields', array(
			'active' => array( 
				'name'    => 'active',
				'type'    => 'select',
				'title'   => esc_html__( 'Enable', 'booking-activities' ),
				'options' => array( 
					1 => esc_html__( 'Yes', 'booking-activities' ),
					0 => esc_html__( 'No', 'booking-activities' )
				),
				'value'   => $notification_edit[ 'active' ],
				'tip'     => esc_html__( 'Enable or disable this notification.', 'booking-activities' )
			),
			'object_type' => array(
				'type'    => 'hidden',
				'name'    => 'object_type',
				'id'	  => 'bookacti-object_type',
				'value'   => $notification_edit[ 'object_type' ]
			),
			'target' => array(
				'type'    => 'select',
				'name'    => 'target',
				'id'	  => 'bookacti-target',
				'title'   => esc_html__( 'Recipient', 'booking-activities' ),
				'options' => array(
					'admin'    => esc_html__( 'Administrator', 'booking-activities' ),
					'customer' => esc_html__( 'Customer', 'booking-activities' )
				),
				'attr'    => array( '<select>' => 'disabled="disabled"' ),
				'value'   => $notification_edit[ 'target' ],
				'tip'     => esc_html__( 'Recipient of the notification.', 'booking-activities' )
			),
			'trigger' => array(
				'type'    => 'select',
				'name'    => 'trigger',
				'id'	  => 'bookacti-trigger',
				'title'   => esc_html__( 'Trigger', 'booking-activities' ),
				'options' => $trigger_options,
				'attr'    => array( '<select>' => 'disabled="disabled"' ),
				'value'   => $notification_edit[ 'trigger' ],
				'tip'     => esc_html__( 'The notification is sent when this action occurs.', 'booking-activities' )
			)
		), $notification_edit ) );
		
		do_action( 'bookacti_notification_global_fields_after', $notification_edit );
	?>
	</div>
<?php
}


/**
 * Display 'Email' metabox content in notification edit page
 * @since 1.18.0
 * @param array $notification_edit
 */
function bookacti_display_notification_email_meta_box( $notification_edit ) {
	$is_new = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'new';
?>
	<div id='bookacti-notification-email-fields'>
	<?php 
		do_action( 'bookacti_notification_email_fields_before', $notification_edit );
		
		$fields = array(
			'to' => array(
				'type'     => 'select',
				'multiple' => true,
				'name'     => 'email[to]',
				'id'       => 'bookacti-email-to',
				'title'    => esc_html__( 'Recipient(s)', 'booking-activities' ),
				'class'    => 'bookacti-select2-no-ajax',
				'attr'     => array( '<select>' => ' data-allow-clear="1" data-tags="1"' ),
				'options'  => array_combine( $notification_edit[ 'email' ][ 'to' ], $notification_edit[ 'email' ][ 'to' ] ),
				'value'    => $notification_edit[ 'email' ][ 'to' ],
				'tip'      => esc_html__( 'Recipient(s) email address(es).', 'booking-activities' ),
			),
			'subject' => array(
				'type'     => 'text',
				'name'     => 'email[subject]',
				'fullwidth' => true,
				'id'       => 'bookacti-email-subject',
				'class'    => 'bookacti-translatable',
				'title'    => esc_html__( 'Subject', 'booking-activities' ),
				'value'    => $notification_edit[ 'email' ][ 'subject' ],
				'tip'      => esc_html__( 'The email subject.', 'booking-activities' )
			),
			'message' => array(
				'type'     => 'editor',
				'name'     => 'email[message]',
				'id'       => 'bookacti-email-message',
				'class'    => 'bookacti-translatable',
				'height'   => 470,
				'options'  => array( 'default_editor' => wp_default_editor() ),
				'title'    => esc_html__( 'Email content', 'booking-activities' ),
				'value'    => $notification_edit[ 'email' ][ 'message' ],
				'tip'      => esc_html__( 'The email message.', 'booking-activities' )
			)
		);
		
		// Display "Recipient(s)" field only for notification aimed to Administrators
		if( $notification_edit[ 'target' ] && $notification_edit[ 'target' ] !== 'admin' ) {
			unset( $fields[ 'to' ] );
		}
		
		bookacti_display_fields( apply_filters( 'bookacti_notification_email_fields', $fields, $notification_edit ) );
		
		do_action( 'bookacti_notification_email_fields_after', $notification_edit );
	?>
	</div>
<?php
}


/**
 * Update a notification
 * @since 1.18.0
 */
function bookacti_controller_update_notification_data() {
	if( empty( $_POST[ 'action' ] ) || empty( $_POST[ 'notification_db_id' ] ) ) { return; }
	if( $_POST[ 'action' ] !== 'edit' ) { return; }
	
	// Exit if wrong nonce
	check_admin_referer( 'bookacti_update_notification', 'nonce' );
	
	$notification_db_id = intval( $_POST[ 'notification_db_id' ] );
	
	// Exit if not allowed to create a notification
	$can_edit_notification   = current_user_can( 'bookacti_edit_notifications' );
	$can_manage_notification = bookacti_user_can_manage_notification( $notification_db_id );
	if( ! $can_edit_notification || ! $can_manage_notification ) { esc_html_e( 'You are not allowed to do that.', 'booking-activities' ); exit; }
	
	// Check if the notification exists
	$old_notification = $notification_db_id ? bookacti_get_notification_data( $notification_db_id ) : array();
	if( ! $old_notification ) { esc_html_e( 'Invalid notification.', 'booking-activities' ); exit; }
	
	// Sanitize data
	$notification_data = bookacti_sanitize_notification_data( array_merge( $old_notification, $_POST, array( 'db_id' => $notification_db_id ) ) );
	
	// Do not update certain data
	$notification_data[ 'object_type' ] = '';
	$notification_data[ 'target' ]      = '';
	$notification_data[ 'trigger' ]     = '';
	$notification_data[ 'user_id' ]     = -1;
	$notification_data[ 'status' ]      = '';
	
	// Update the notification data
	$updated = bookacti_update_notification_data( $notification_data );
	
	$GLOBALS[ 'bookacti_notification_updated' ] = $updated;
}
add_action( 'load-booking-activities_page_bookacti_notifications', 'bookacti_controller_update_notification_data', 5 );


/**
 * Display an admin notice to feedback the result of an action taken on a notification
 * @since 1.18.0
 */
function bookacti_controller_notification_admin_notices() {
	$message = '';

	// Update
	if( isset( $GLOBALS[ 'bookacti_notification_updated' ] ) ) {
		if( $GLOBALS[ 'bookacti_notification_updated' ] !== false ) {
			$message_type = 'success';
			$message = esc_html__( 'The notification has been updated.', 'booking-activities' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to update the notification.', 'booking-activities' );
		}
	}
	
	if( $message ) {
	?>
		<div class='notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible bookacti-notification-notice' >
			<p><?php echo $message; ?></p>
		</div>
	<?php
	}
}
add_action( 'all_admin_notices', 'bookacti_controller_notification_admin_notices', 10 );