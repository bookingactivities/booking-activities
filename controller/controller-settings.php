<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Init Booking Activities settings
 * @version 1.7.0
 */
function bookacti_init_settings() { 

	/* General settings Section */
	add_settings_section( 
		'bookacti_settings_section_general',
		esc_html__( 'General settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_general_callback',
		'bookacti_general_settings'
	);
	
	add_settings_field(  
		'booking_method', 
		esc_html__( 'Booking method', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_booking_method_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'when_events_load', 
		esc_html__( 'When to load the events?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_when_events_load_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'event_load_interval', 
		esc_html__( 'Load events every', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_event_load_interval_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);

	add_settings_field(  
		'started_events_bookable', 
		esc_html__( 'Are started events bookable?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_started_events_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'started_groups_bookable', 
		esc_html__( 'Are started groups of events bookable?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_started_groups_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'availability_period_start', 
		/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
		esc_html__( 'Events will be bookable in', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_availability_period_start_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'availability_period_end', 
		/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events are bookable for up to 30 days from today". */
		esc_html__( 'Events are bookable for up to', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_availability_period_end_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_booking_state', 
		esc_html__( 'Default booking state', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_booking_state_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_payment_status', 
		esc_html__( 'Default payment status', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_payment_status_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'timezone', 
		esc_html__( 'Calendars timezone', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_timezone_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_calendar_view_threshold', 
		esc_html__( 'Responsive calendar view threshold', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_calendar_view_threshold_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'delete_data_on_uninstall', 
		esc_html__( 'Delete data on uninstall', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_delete_data_on_uninstall_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	
	
	/* Cancellation settings Section */
	add_settings_section( 
		'bookacti_settings_section_cancellation',
		esc_html__( 'Cancellation settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_cancellation_callback',
		'bookacti_cancellation_settings'
	);
	
	add_settings_field(  
		'allow_customers_to_cancel',                      
		esc_html__( 'Allow customers to cancel their bookings', BOOKACTI_PLUGIN_NAME ),               
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
		esc_html__( 'Changes permitted up to', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cancellation_delay_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'refund_actions_after_cancellation',                      
		esc_html__( 'Possible actions customers can take to be refunded', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cancellation_refund_actions_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	
	
	/* Notifications settings Section - 1 - General settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_general',
		esc_html__( 'Notifications', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_notifications_general_callback',
		'bookacti_notifications_settings'
	);
	
	add_settings_field( 
		'notifications_async',
		esc_html__( 'Asynchronous notifications', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_async_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_general'
	);
	
	
	/* Notifications settings Section - 2 - Email settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_email',
		esc_html__( 'Email notifications settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_notifications_email_callback',
		'bookacti_notifications_settings'
	);
		
	add_settings_field( 
		'notifications_from_name',
		esc_html__( 'From name', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_from_name_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	add_settings_field( 
		'notifications_from_email',
		esc_html__( 'From email', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_field_notifications_from_email_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	
	
	/* Messages settings Section */
	add_settings_section( 
		'bookacti_settings_section_messages',
		esc_html__( 'Messages', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_messages_callback',
		'bookacti_messages_settings'
	);
	
	
	
	/* Messages settings Section */
	add_settings_section( 
		'bookacti_settings_section_system',
		esc_html__( 'System', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_system_callback',
		'bookacti_system_settings'
	);
	
	
	do_action( 'bookacti_add_settings' );
	
	
	register_setting( 'bookacti_general_settings',			'bookacti_general_settings' );
	register_setting( 'bookacti_cancellation_settings',		'bookacti_cancellation_settings' );
	register_setting( 'bookacti_notifications_settings',	'bookacti_notifications_settings' );
	register_setting( 'bookacti_messages_settings',			'bookacti_messages_settings' );
	register_setting( 'bookacti_system_settings',			'bookacti_system_settings' );
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
 * @version 1.6.0
 */
function bookacti_add_booking_page_screen_option() {
	$booking_list = new Bookings_List_Table();
	$booking_list->process_bulk_action();
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
								$addon_link  = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >Notification Pack</a>';
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




// MESSAGES

/**
 * Display messages fields
 * @since 1.2.0
 * @version 1.5.7
 */
function bookacti_display_messages_fields() {
	$messages = bookacti_get_messages( true );
	foreach( $messages as $message_id => $message ) {
?>
		<div class='bookacti-message-setting' >
			<em><?php echo $message[ 'description' ] ?></em><br/>
			<?php if( isset( $message[ 'input_type' ] ) && $message[ 'input_type' ] === 'textarea' ) { ?>
				<textarea id='bookacti_messages_settings_<?php echo $message_id; ?>' name='bookacti_messages_settings[<?php echo $message_id; ?>]' ><?php echo esc_textarea( $message[ 'value' ] ); ?></textarea>
			<?php } else { ?>
				<input type='text' id='bookacti_messages_settings_<?php echo $message_id; ?>' name='bookacti_messages_settings[<?php echo $message_id; ?>]' value='<?php esc_attr_e( $message[ 'value' ] ); ?>' />
			<?php } ?>
		</div>
<?php
	}
}
add_action( 'bookacti_messages_settings', 'bookacti_display_messages_fields' );




// SYSTEM

/**
 * Analyse what data can be archive prior to specific date
 * @since 1.7.0
 */
function bookacti_controller_archive_data_analyse() {
	$date = bookacti_sanitize_date( $_POST[ 'date' ] );
	$user_can_archive = bookacti_user_can_archive_data( $date );
	if( $user_can_archive !== true ) { bookacti_send_json( $user_can_archive, 'archive_analyse_data' );	}
	
	// If there are a lot of data, this operation can be long
	// We need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 600, '512M' );
	
	// Get the data prior to the desired date
	// Get events IDs
	$events = bookacti_get_events_prior_to( $date );
	// Repeated events exceptions
	$exceptions = bookacti_get_repeated_events_exceptions_prior_to( $date );
	// Get started repeated events
	$started_repeated_events = bookacti_get_started_repeated_events_as_of( $date );
	// Get group of events
	$groups_of_events = bookacti_get_group_of_events_prior_to( $date );
	// Get grouped events
	$grouped_events = bookacti_get_grouped_events_prior_to( $date );
	// Get bookings
	$bookings = bookacti_get_bookings_prior_to( $date );
	// Get booking groups
	$booking_groups = bookacti_get_booking_groups_prior_to( $date );
	// Get metadata
	$metadata = bookacti_get_metadata_prior_to( $date );
	
	// Format the results to feedback the user
	$no_data = true;
	$ids_per_type = array(
		esc_html__( 'Events', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Repetition exceptions', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Repeated events to be truncated', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Groups of events', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Grouped events', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Booking groups', BOOKACTI_PLUGIN_NAME ) => array(),
		esc_html__( 'Metadata', BOOKACTI_PLUGIN_NAME ) => array()
	);
	if( $events )					{ $no_data = false; foreach( $events as $event )						{ $ids_per_type[ esc_html__( 'Events', BOOKACTI_PLUGIN_NAME ) ][] = $event->id; } }
	if( $exceptions )				{ $no_data = false; foreach( $exceptions as $exception )				{ $ids_per_type[ esc_html__( 'Repetition exceptions', BOOKACTI_PLUGIN_NAME ) ][] = $exception->id; } }
	if( $started_repeated_events )	{ $no_data = false; foreach( $started_repeated_events as $event )		{ $ids_per_type[ esc_html__( 'Repeated events to be truncated', BOOKACTI_PLUGIN_NAME ) ][] = $event->id; } }
	if( $groups_of_events )			{ $no_data = false; foreach( $groups_of_events as $group_of_events )	{ $ids_per_type[ esc_html__( 'Groups of events', BOOKACTI_PLUGIN_NAME ) ][] = $group_of_events->id; } }
	if( $grouped_events )			{ $no_data = false; foreach( $grouped_events as $grouped_event )		{ $ids_per_type[ esc_html__( 'Grouped events', BOOKACTI_PLUGIN_NAME ) ][] = $grouped_event->id; } }
	if( $bookings )					{ $no_data = false; foreach( $bookings as $booking )					{ $ids_per_type[ esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME ) ][] = $booking->id; } }
	if( $booking_groups )			{ $no_data = false; foreach( $booking_groups as $booking_group )		{ $ids_per_type[ esc_html__( 'Booking groups', BOOKACTI_PLUGIN_NAME ) ][] = $booking_group->id; } }
	if( $metadata )					{ $no_data = false; foreach( $metadata as $meta_row )					{ $ids_per_type[ esc_html__( 'Metadata', BOOKACTI_PLUGIN_NAME ) ][] = $meta_row->id; } }
	
	if( $no_data ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_data_to_archive', 'message' => esc_html__( 'No data to archive.', BOOKACTI_PLUGIN_NAME ) ), 'archive_analyse_data' );
	}
	
	// Check if an archive already exists
	$uploads_dir	= wp_upload_dir();
	$archives_dir	= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/';
	$already_exists	= file_exists( $archives_dir . $date . '-booking-activities-archive.zip' );
	
	bookacti_send_json( array( 'status' => 'success', 'ids_per_type' => $ids_per_type, 'file_already_exists' => $already_exists, 'message' => esc_html__( 'These data can be archived:', BOOKACTI_PLUGIN_NAME ) ), 'archive_analyse_data' );
}
add_action( 'wp_ajax_bookactiArchiveDataAnalyse', 'bookacti_controller_archive_data_analyse' );


/**
 * Dump bookings and events prior to specific date
 * @since 1.7.0
 */
function bookacti_controller_archive_data_dump() {
	$date = bookacti_sanitize_date( $_POST[ 'date' ] );
	$user_can_archive = bookacti_user_can_archive_data( $date );
	if( $user_can_archive !== true ) { bookacti_send_json( $user_can_archive, 'archive_dump_data' ); }
	
	// If there are a lot of data, this operation can be long
	// We need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 600, '512M' );
	
	// Dump the data prior to the desired date
	// Events
	$dumped_events = bookacti_archive_events_prior_to( $date );
	// Repeated events exceptions
	$dumped_repeated_events_exceptions = bookacti_archive_repeated_events_exceptions_prior_to( $date );
	// Started repeated events
	$dumped_repeated_events = bookacti_archive_started_repeated_events_as_of( $date );
	// Groups of events
	$dumped_groups_of_events = bookacti_archive_group_of_events_prior_to( $date );
	// Groups of events
	$dumped_grouped_events = bookacti_archive_grouped_events_prior_to( $date );
	// Bookings
	$dumped_bookings = bookacti_archive_bookings_prior_to( $date );
	// Booking groups
	$dumped_booking_groups = bookacti_archive_booking_groups_prior_to( $date );
	// Metadata
	$dumped_meta = bookacti_archive_metadata_prior_to( $date );
	
	// Format the results to feedback the user
	$results = array( 
		esc_html__( 'Events', BOOKACTI_PLUGIN_NAME )					=> $dumped_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_events,
		esc_html__( 'Repetition exceptions', BOOKACTI_PLUGIN_NAME )		=> $dumped_repeated_events_exceptions === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_repeated_events_exceptions,
		esc_html__( 'Truncated repeated events', BOOKACTI_PLUGIN_NAME ) => $dumped_repeated_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_repeated_events,
		esc_html__( 'Groups of events', BOOKACTI_PLUGIN_NAME )			=> $dumped_groups_of_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_groups_of_events,
		esc_html__( 'Grouped events', BOOKACTI_PLUGIN_NAME )			=> $dumped_grouped_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_grouped_events,
		esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME )					=> $dumped_bookings === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_bookings,
		esc_html__( 'Booking groups', BOOKACTI_PLUGIN_NAME )			=> $dumped_booking_groups === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_booking_groups,
		esc_html__( 'Metadata', BOOKACTI_PLUGIN_NAME )					=> $dumped_meta === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $dumped_meta
	);

	if( $dumped_events === false || $dumped_repeated_events_exceptions === false || $dumped_repeated_events === false || $dumped_groups_of_events === false || $dumped_bookings === false || $dumped_booking_groups === false || $dumped_meta === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'dump_failed', 'results' => $results, 'message' => esc_html__( 'An error occurred while trying to archive data.', BOOKACTI_PLUGIN_NAME ) ), 'archive_dump_data' );
	}
	
	// Zip the files into a single one
	$current_date	= date( 'YmdHis' );
	$uploads_dir	= wp_upload_dir();
	$archives_dir	= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/';
	$zip_filename	= $current_date . '-' . $date  . '-booking-activities-' . BOOKACTI_VERSION . '.zip';
	$zip_file		= $archives_dir . $zip_filename;
	$zip_file_url	= esc_url( trailingslashit( $uploads_dir[ 'baseurl' ] ) . BOOKACTI_PLUGIN_NAME . '/archives/' . $zip_filename );
	$zip_created	= false;
	if( is_dir( $archives_dir ) ) {
		$archives_handle = opendir( $archives_dir );
		if( $archives_handle ) {
			$files = array();
			while( false !== ( $filename = readdir( $archives_handle ) ) ) {
				if( substr( $filename, 0, 10 ) !== $date ) { continue; }
				$files[] = $archives_dir . $filename;
			}
			if( $files ) {
				$zip_created = bookacti_create_zip( $files, $zip_file, true, true );
			}
		}
	}
	
	if( ! $zip_created ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'zip_failed', 'results' => $results, 'message' => esc_html__( 'An error occurred while trying to zip the files.', BOOKACTI_PLUGIN_NAME ) ), 'archive_dump_data' );
	}
	
	$secret_key		= get_option( 'bookacti_archive_secret_key' );
	$download_link	= '<a href="' . $zip_file_url . '?key=' . $secret_key . '" target="_blank">' . esc_html_x( 'Download', 'verb', BOOKACTI_PLUGIN_NAME ) . '</a>';
	
	// Get the archive list table
	ob_start();
	bookacti_display_database_archive_list();
	$archive_list = ob_get_clean();
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully archived.', BOOKACTI_PLUGIN_NAME ) . '<br/>' 
			. esc_html__( 'Make sure the data in the backup files are consistent with data reported in step 1:', BOOKACTI_PLUGIN_NAME ) 
			. ' <strong>' . $download_link . '</strong>';
	
	bookacti_send_json( array( 'status' => 'success', 'archive_list' => $archive_list, 'download_link' => $download_link, 'results' => $results, 'message' => $message ), 'archive_dump_data' );
}
add_action( 'wp_ajax_bookactiArchiveDataDump', 'bookacti_controller_archive_data_dump' );


/**
 * Delete bookings and events prior to specific date
 * @since 1.7.0
 */
function bookacti_controller_archive_data_delete() {
	$date = bookacti_sanitize_date( $_POST[ 'date' ] );
	$user_can_archive = bookacti_user_can_archive_data( $date );
	if( $user_can_archive !== true ) { bookacti_send_json( $user_can_archive, 'archive_delete_data' );	}
	
	// If there are a lot of data, this operation can be long
	// We need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 600, '512M' );
	
	// Delete the data prior to the desired date
	// Delete metadata first
	$deleted_meta = bookacti_delete_bookings_and_events_meta_prior_to( $date );
	// We must delete bookings before events and groups before single
	// Booking groups
	$deleted_booking_groups = bookacti_delete_booking_groups_prior_to( $date, false );
	// Bookings
	$deleted_bookings = bookacti_delete_bookings_prior_to( $date, false );
	// Groups of events
	$deleted_groups_of_events = bookacti_delete_group_of_events_prior_to( $date, false );
	// Grouped events
	$deleted_grouped_events = bookacti_delete_grouped_events_prior_to( $date );
	// Repeated events exceptions
	$deleted_repeated_events_exceptions = bookacti_delete_repeated_events_exceptions_prior_to( $date );
	// Events
	$deleted_events = bookacti_delete_events_prior_to( $date, false );
	// Started repeated events
	$truncated_events = bookacti_restrict_started_repeated_events_to( $date );
	
	// Format the results to feedback the user
	$nb_per_type = array( 
		esc_html__( 'Events', BOOKACTI_PLUGIN_NAME )					=> $deleted_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_events,
		esc_html__( 'Repetition exceptions', BOOKACTI_PLUGIN_NAME )		=> $deleted_repeated_events_exceptions === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_repeated_events_exceptions,
		esc_html__( 'Truncated repeated events', BOOKACTI_PLUGIN_NAME ) => $truncated_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $truncated_events,
		esc_html__( 'Groups of events', BOOKACTI_PLUGIN_NAME )			=> $deleted_groups_of_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_groups_of_events,
		esc_html__( 'Grouped events', BOOKACTI_PLUGIN_NAME )			=> $deleted_grouped_events === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_grouped_events,
		esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME )					=> $deleted_bookings === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_bookings,
		esc_html__( 'Booking groups', BOOKACTI_PLUGIN_NAME )			=> $deleted_booking_groups === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_booking_groups,
		esc_html__( 'Metadata', BOOKACTI_PLUGIN_NAME )					=> $deleted_meta === false ? esc_html__( 'Error', BOOKACTI_PLUGIN_NAME ) : $deleted_meta
	);
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully deleted.', BOOKACTI_PLUGIN_NAME ) . '<br/>' 
			. sprintf( esc_html__( 'You can restore your archived data with the "%s" function in the table below, or within phpMyAdmin, with the "Import" function.', BOOKACTI_PLUGIN_NAME ), esc_html__( 'Restore data', BOOKACTI_PLUGIN_NAME ) );
	
	bookacti_send_json( array( 'status' => 'success', 'nb_per_type' => $nb_per_type, 'message' => $message ), 'archive_delete_data' );
}
add_action( 'wp_ajax_bookactiArchiveDataDelete', 'bookacti_controller_archive_data_delete' );


/**
 * Restore bookings and events from backup files
 * @since 1.7.0
 */
function bookacti_controller_archive_restore_data() {
	$filename = sanitize_file_name( $_POST[ 'filename' ] );
	$user_can_archive = bookacti_user_can_manage_archive_file( $filename );
	if( $user_can_archive !== true ) { bookacti_send_json( $user_can_archive, 'archive_restore_data' );	}
	
	// If there are a lot of data, this operation can be long
	// We need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 600, '512M' );
	
	// Import SQL files
	$uploads_dir	= wp_upload_dir();
	$archives_dir	= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/';
	$zip_file		= $archives_dir . $filename;
	$imported		= bookacti_import_sql_files( $zip_file );
	
	if( $imported[ 'status' ] !== 'success' ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'import_failed', 'results' => $imported[ 'results' ], 'message' => esc_html__( 'An error occured while trying to restore backup data.', BOOKACTI_PLUGIN_NAME ) ), 'archive_restore_data' );
	}
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully restored.', BOOKACTI_PLUGIN_NAME );
	
	bookacti_send_json( array( 'status' => 'success', 'results' => $imported[ 'results' ], 'message' => $message ), 'archive_restore_data' );
}
add_action( 'wp_ajax_bookactiArchiveRestoreData', 'bookacti_controller_archive_restore_data' );


/**
 * Delete backup file
 * @since 1.7.0
 */
function bookacti_controller_archive_delete_file() {
	$filename = sanitize_file_name( $_POST[ 'filename' ] );
	$user_can_archive = bookacti_user_can_manage_archive_file( $filename );
	if( $user_can_archive !== true ) { bookacti_send_json( $user_can_archive, 'archive_delete_file' );	}
	
	// Import SQL files
	$uploads_dir	= wp_upload_dir();
	$archives_dir	= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/';
	$zip_file		= $archives_dir . $filename;
	$deleted		= unlink( $zip_file );
	
	if( ! $deleted ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'unlink_failed', 'message' => esc_html__( 'An error occured while trying to delete the backup file.', BOOKACTI_PLUGIN_NAME ) ), 'archive_delete_file' );
	}
	
	// Get the archive list table
	ob_start();
	bookacti_display_database_archive_list();
	$archive_list = ob_get_clean();
	
	bookacti_send_json( array( 'status' => 'success', 'archive_list' => $archive_list ), 'archive_delete_file' );
}
add_action( 'wp_ajax_bookactiArchiveDeleteFile', 'bookacti_controller_archive_delete_file' );




// USER PROFILE

/**
 * Add user contact methods (Add fields to the user profile)
 * @since 1.7.0
 * @param array $methods
 * @param WP_User $user
 * @return array
 */
function bookacti_add_user_contact_methods( $methods, $user ) {
	if( in_array( 'phone', $methods, true ) ) { return $methods; }
	$methods[ 'phone' ] = esc_html__( 'Phone', BOOKACTI_PLUGIN_NAME );
	return $methods;
}
add_filter( 'user_contactmethods', 'bookacti_add_user_contact_methods', 100, 2 );




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
 * @version 1.5.7
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
				if( $nb_days >= 92 ) {
					?>
					<div class='notice notice-info bookacti-5stars-rating-notice is-dismissible' >
						<p>
							<?php 
							_e( '<strong>Booking Activities</strong> has been helping you for three months now.', BOOKACTI_PLUGIN_NAME );
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