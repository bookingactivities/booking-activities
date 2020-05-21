<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Init Booking Activities settings
 * @version 1.8.0
 */
function bookacti_init_settings() { 
	/* General settings Section */
	add_settings_section( 
		'bookacti_settings_section_general',
		esc_html__( 'General settings', 'booking-activities' ),
		'bookacti_settings_section_general_callback',
		'bookacti_general_settings'
	);
	
	add_settings_field(  
		'timezone', 
		esc_html__( 'Calendars timezone', 'booking-activities' ), 
		'bookacti_settings_field_timezone_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_calendar_view_threshold', 
		esc_html__( 'Load the "Day" view if the calendar width is less than', 'booking-activities' ), 
		'bookacti_settings_field_default_calendar_view_threshold_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	add_settings_field(  
		'when_events_load', 
		esc_html__( 'When to load the events?', 'booking-activities' ), 
		'bookacti_settings_field_when_events_load_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'event_load_interval', 
		esc_html__( 'Load events every', 'booking-activities' ), 
		'bookacti_settings_field_event_load_interval_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);

	add_settings_field(  
		'started_events_bookable', 
		esc_html__( 'Are started events bookable?', 'booking-activities' ), 
		'bookacti_settings_field_started_events_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'started_groups_bookable', 
		esc_html__( 'Are started groups of events bookable?', 'booking-activities' ), 
		'bookacti_settings_field_started_groups_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	add_settings_field(  
		'default_booking_state', 
		esc_html__( 'Default booking state', 'booking-activities' ), 
		'bookacti_settings_field_default_booking_state_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_payment_status', 
		esc_html__( 'Default payment status', 'booking-activities' ), 
		'bookacti_settings_field_default_payment_status_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	add_settings_field(  
		'display_private_columns', 
		esc_html__( 'Allow private columns in frontend booking lists', 'booking-activities' ), 
		'bookacti_settings_field_display_private_columns_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	add_settings_field(  
		'delete_data_on_uninstall', 
		esc_html__( 'Delete data on uninstall', 'booking-activities' ), 
		'bookacti_settings_field_delete_data_on_uninstall_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	
	
	/* Cancellation settings Section */
	add_settings_section( 
		'bookacti_settings_section_cancellation',
		esc_html__( 'Cancellation settings', 'booking-activities' ),
		'bookacti_settings_section_cancellation_callback',
		'bookacti_cancellation_settings'
	);
	
	add_settings_field(  
		'allow_customers_to_cancel',                      
		esc_html__( 'Allow customers to cancel their bookings', 'booking-activities' ),               
		'bookacti_settings_field_activate_cancel_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'allow_customers_to_reschedule',                      
		esc_html__( 'Allow customers to reschedule their bookings', 'booking-activities' ),               
		'bookacti_settings_field_activate_reschedule_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'booking_changes_deadline', 
		/* translators: Followed by a field indicating a number of days, hours and minutes from now. E.g.: "Changes are allowed for bookings starting in at least 2 days, 12 hours, 25 minutes". */
		esc_html__( 'Changes are allowed for bookings starting in at least', 'booking-activities' ),               
		'bookacti_settings_field_cancellation_delay_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'refund_actions_after_cancellation',                      
		esc_html__( 'Possible actions customers can take to be refunded', 'booking-activities' ),               
		'bookacti_settings_field_cancellation_refund_actions_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	
	
	/* Notifications settings Section - 1 - General settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_general',
		esc_html__( 'Notifications', 'booking-activities' ),
		'bookacti_settings_section_notifications_general_callback',
		'bookacti_notifications_settings'
	);
	
	add_settings_field( 
		'notifications_async',
		esc_html__( 'Asynchronous notifications', 'booking-activities' ),
		'bookacti_settings_field_notifications_async_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_general'
	);
	
	
	/* Notifications settings Section - 2 - Email settings */
	add_settings_section( 
		'bookacti_settings_section_notifications_email',
		esc_html__( 'Email notifications settings', 'booking-activities' ),
		'bookacti_settings_section_notifications_email_callback',
		'bookacti_notifications_settings'
	);
		
	add_settings_field( 
		'notifications_from_name',
		esc_html__( 'From name', 'booking-activities' ),
		'bookacti_settings_field_notifications_from_name_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	add_settings_field( 
		'notifications_from_email',
		esc_html__( 'From email', 'booking-activities' ),
		'bookacti_settings_field_notifications_from_email_callback',
		'bookacti_notifications_settings',
		'bookacti_settings_section_notifications_email'
	);
	
	
	
	/* Messages settings Section */
	add_settings_section( 
		'bookacti_settings_section_messages',
		esc_html__( 'Messages', 'booking-activities' ),
		'bookacti_settings_section_messages_callback',
		'bookacti_messages_settings'
	);
	
	add_settings_field( 
		'calendar_localization',
		esc_html__( 'Calendar localization', 'booking-activities' ),
		'bookacti_settings_field_calendar_localization_callback',
		'bookacti_messages_settings',
		'bookacti_settings_section_messages'
	);
	
	
	/* System settings Section */
	add_settings_section( 
		'bookacti_settings_section_system',
		esc_html__( 'System', 'booking-activities' ),
		'bookacti_settings_section_system_callback',
		'bookacti_system_settings'
	);
	
	
	
	/* Licenses settings Section */
	add_settings_section( 
		'bookacti_settings_section_licenses',
		esc_html__( 'Licenses settings', 'booking-activities' ),
		'bookacti_settings_section_licenses_callback',
		'bookacti_licenses_settings'
	);
	
	register_setting( 'bookacti_general_settings',			'bookacti_general_settings' );
	register_setting( 'bookacti_cancellation_settings',		'bookacti_cancellation_settings' );
	register_setting( 'bookacti_notifications_settings',	'bookacti_notifications_settings' );
	register_setting( 'bookacti_messages_settings',			'bookacti_messages_settings' );
	register_setting( 'bookacti_system_settings',			'bookacti_system_settings' );
	register_setting( 'bookacti_licenses_settings',			'bookacti_licenses_settings' );
		
	/* Allow plugins to add settings and sections */
	do_action( 'bookacti_add_settings' );
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

		<h2><?php echo __( 'Notification', 'booking-activities' ) . ' - ' . $notification_settings[ 'title' ]; ?></h2>

		<p>
			<a href='<?php echo esc_url( '?page=bookacti_settings&tab=notifications' ); ?>' >
				<?php _e( 'Go back to notifications settings', 'booking-activities' ); ?>
			</a>
		</p>

		<p><?php echo $notification_settings[ 'description' ]; ?></p>

		<?php do_action( 'bookacti_notification_settings_page_before', $notification_settings, $notification_id ); ?>

		<h3><?php _e( 'Global notifications settings', 'booking-activities' ); ?></h3>
		<table class='form-table' id='bookacti-notification-global-settings' >
			<tbody>
				<tr>
					<th scope='row' ><?php _e( 'Enable', 'booking-activities' ); ?></th>
					<td>
						<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'bookacti_notification[active]',
							'id'	=> 'bookacti_notification_' . $notification_id . 'active',
							'value'	=> $notification_settings[ 'active' ] ? $notification_settings[ 'active' ] : 0,
							'tip'	=> __( 'Enable or disable this automatic notification.', 'booking-activities' )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<?php do_action( 'bookacti_notification_settings_page_global', $notification_settings, $notification_id ); ?>
			</tbody>
		</table>

		<h3><?php _e( 'Email notifications settings', 'booking-activities' ); ?></h3>
		<table class='form-table' id='bookacti-notification-email-settings' >
			<tbody>
				<?php 

				do_action( 'bookacti_notification_settings_page_email_before', $notification_settings, $notification_id );

				if( substr( $notification_id, 0, 8 ) !== 'customer' ) { ?>
				<tr>
					<th scope='row' ><?php _e( 'Recipient(s)', 'booking-activities' ); ?></th>
					<td>
						<?php
						$args = array(
							'type'	=> 'text',
							'name'	=> 'bookacti_notification[email][to]',
							'id'	=> 'bookacti_notification_' . $notification_id . '_email_to',
							'value'	=> is_array( $notification_settings[ 'email' ][ 'to' ] ) ? implode( ',', $notification_settings[ 'email' ][ 'to' ] ) : strval( $notification_settings[ 'email' ][ 'to' ] ),
							'tip'	=> __( 'Recipient(s) email address(es) (comma separated).', 'booking-activities' )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<th scope='row' ><?php _ex( 'Subject', 'email subject', 'booking-activities' ); ?></th>
					<td>
						<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'bookacti_notification[email][subject]',
							'id'	=> 'bookacti_notification_' . $notification_id . '_email_subject',
							'value'	=> $notification_settings[ 'email' ][ 'subject' ] ? $notification_settings[ 'email' ][ 'subject' ] : '',
							'tip'	=> __( 'The email subject.', 'booking-activities' )
						);
						bookacti_display_field( $args );
						?>
					</td>
				</tr>
				<tr>
					<th scope='row' >
					<?php 
						_ex( 'Email content', 'email message', 'booking-activities' ); 
						$tags = bookacti_get_notifications_tags( $notification_id );
						if( $tags ) {
					?>
						<div class='bookacti-notifications-tags-list' >
							<p><?php _e( 'Use these tags:', 'booking-activities' ); ?></p>
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
								$tip = sprintf( esc_html__( 'You can set a specific message on events, activities, groups of events and group categories and use it in your notifications thanks to %1$s add-on.', 'booking-activities' ), $addon_link );
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
							'height'=> 470,
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
		esc_html__( 'Events', 'booking-activities' ) => array(),
		esc_html__( 'Repetition exceptions', 'booking-activities' ) => array(),
		esc_html__( 'Repeated events to be truncated', 'booking-activities' ) => array(),
		esc_html__( 'Groups of events', 'booking-activities' ) => array(),
		esc_html__( 'Grouped events', 'booking-activities' ) => array(),
		esc_html__( 'Bookings', 'booking-activities' ) => array(),
		esc_html__( 'Booking groups', 'booking-activities' ) => array(),
		esc_html__( 'Metadata', 'booking-activities' ) => array()
	);
	if( $events )					{ $no_data = false; foreach( $events as $event )						{ $ids_per_type[ esc_html__( 'Events', 'booking-activities' ) ][] = $event->id; } }
	if( $exceptions )				{ $no_data = false; foreach( $exceptions as $exception )				{ $ids_per_type[ esc_html__( 'Repetition exceptions', 'booking-activities' ) ][] = $exception->id; } }
	if( $started_repeated_events )	{ $no_data = false; foreach( $started_repeated_events as $event )		{ $ids_per_type[ esc_html__( 'Repeated events to be truncated', 'booking-activities' ) ][] = $event->id; } }
	if( $groups_of_events )			{ $no_data = false; foreach( $groups_of_events as $group_of_events )	{ $ids_per_type[ esc_html__( 'Groups of events', 'booking-activities' ) ][] = $group_of_events->id; } }
	if( $grouped_events )			{ $no_data = false; foreach( $grouped_events as $grouped_event )		{ $ids_per_type[ esc_html__( 'Grouped events', 'booking-activities' ) ][] = $grouped_event->id; } }
	if( $bookings )					{ $no_data = false; foreach( $bookings as $booking )					{ $ids_per_type[ esc_html__( 'Bookings', 'booking-activities' ) ][] = $booking->id; } }
	if( $booking_groups )			{ $no_data = false; foreach( $booking_groups as $booking_group )		{ $ids_per_type[ esc_html__( 'Booking groups', 'booking-activities' ) ][] = $booking_group->id; } }
	if( $metadata )					{ $no_data = false; foreach( $metadata as $meta_row )					{ $ids_per_type[ esc_html__( 'Metadata', 'booking-activities' ) ][] = $meta_row->id; } }
	
	if( $no_data ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_data_to_archive', 'message' => esc_html__( 'No data to archive.', 'booking-activities' ) ), 'archive_analyse_data' );
	}
	
	// Check if an archive already exists
	$uploads_dir	= wp_upload_dir();
	$archives_dir	= trailingslashit( str_replace( '\\', '/', $uploads_dir[ 'basedir' ] ) ) . BOOKACTI_PLUGIN_NAME . '/archives/';
	$already_exists	= file_exists( $archives_dir . $date . '-booking-activities-archive.zip' );
	
	bookacti_send_json( array( 'status' => 'success', 'ids_per_type' => $ids_per_type, 'file_already_exists' => $already_exists, 'message' => esc_html__( 'These data can be archived:', 'booking-activities' ) ), 'archive_analyse_data' );
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
		esc_html__( 'Events', 'booking-activities' )					=> $dumped_events === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_events,
		esc_html__( 'Repetition exceptions', 'booking-activities' )		=> $dumped_repeated_events_exceptions === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_repeated_events_exceptions,
		esc_html__( 'Truncated repeated events', 'booking-activities' ) => $dumped_repeated_events === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_repeated_events,
		esc_html__( 'Groups of events', 'booking-activities' )			=> $dumped_groups_of_events === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_groups_of_events,
		esc_html__( 'Grouped events', 'booking-activities' )			=> $dumped_grouped_events === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_grouped_events,
		esc_html__( 'Bookings', 'booking-activities' )					=> $dumped_bookings === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_bookings,
		esc_html__( 'Booking groups', 'booking-activities' )			=> $dumped_booking_groups === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_booking_groups,
		esc_html__( 'Metadata', 'booking-activities' )					=> $dumped_meta === false ? esc_html__( 'Error', 'booking-activities' ) : $dumped_meta
	);

	if( $dumped_events === false || $dumped_repeated_events_exceptions === false || $dumped_repeated_events === false || $dumped_groups_of_events === false || $dumped_bookings === false || $dumped_booking_groups === false || $dumped_meta === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'dump_failed', 'results' => $results, 'message' => esc_html__( 'An error occurred while trying to archive data.', 'booking-activities' ) ), 'archive_dump_data' );
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
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'zip_failed', 'results' => $results, 'message' => esc_html__( 'An error occurred while trying to zip the files.', 'booking-activities' ) ), 'archive_dump_data' );
	}
	
	$secret_key		= get_option( 'bookacti_archive_secret_key' );
	$download_link	= '<a href="' . $zip_file_url . '?key=' . $secret_key . '" target="_blank">' . esc_html_x( 'Download', 'verb', 'booking-activities' ) . '</a>';
	
	// Get the archive list table
	ob_start();
	bookacti_display_database_archive_list();
	$archive_list = ob_get_clean();
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully archived.', 'booking-activities' ) . '<br/>' 
			. esc_html__( 'Make sure the data in the backup files are consistent with data reported in step 1:', 'booking-activities' ) 
			. ' <strong>' . $download_link . '</strong>';
	
	bookacti_send_json( array( 'status' => 'success', 'archive_list' => $archive_list, 'download_link' => $download_link, 'results' => $results, 'message' => $message ), 'archive_dump_data' );
}
add_action( 'wp_ajax_bookactiArchiveDataDump', 'bookacti_controller_archive_data_dump' );


/**
 * Delete bookings and events prior to specific date
 * @since 1.7.0
 * @version 1.7.8
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
		esc_html__( 'Events', 'booking-activities' )					=> $deleted_events === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_events,
		esc_html__( 'Repetition exceptions', 'booking-activities' )		=> $deleted_repeated_events_exceptions === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_repeated_events_exceptions,
		esc_html__( 'Truncated repeated events', 'booking-activities' ) => $truncated_events === false ? esc_html__( 'Error', 'booking-activities' ) : $truncated_events,
		esc_html__( 'Groups of events', 'booking-activities' )			=> $deleted_groups_of_events === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_groups_of_events,
		esc_html__( 'Grouped events', 'booking-activities' )			=> $deleted_grouped_events === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_grouped_events,
		esc_html__( 'Bookings', 'booking-activities' )					=> $deleted_bookings === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_bookings,
		esc_html__( 'Booking groups', 'booking-activities' )			=> $deleted_booking_groups === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_booking_groups,
		esc_html__( 'Metadata', 'booking-activities' )					=> $deleted_meta === false ? esc_html__( 'Error', 'booking-activities' ) : $deleted_meta
	);
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully deleted.', 'booking-activities' ) . '<br/>' 
			/* translators: %s is the label of the "Restore data" button */
			. sprintf( esc_html__( 'You can restore your archived data with the "%s" function in the table below, or within phpMyAdmin, with the "Import" function.', 'booking-activities' ), esc_html__( 'Restore data', 'booking-activities' ) );
	
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
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'import_failed', 'results' => $imported[ 'results' ], 'message' => esc_html__( 'An error occured while trying to restore backup data.', 'booking-activities' ) ), 'archive_restore_data' );
	}
	
	// Feedback message
	$message = esc_html__( 'Your data has been successfully restored.', 'booking-activities' );
	
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
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'unlink_failed', 'message' => esc_html__( 'An error occured while trying to delete the backup file.', 'booking-activities' ) ), 'archive_delete_file' );
	}
	
	// Get the archive list table
	ob_start();
	bookacti_display_database_archive_list();
	$archive_list = ob_get_clean();
	
	bookacti_send_json( array( 'status' => 'success', 'archive_list' => $archive_list ), 'archive_delete_file' );
}
add_action( 'wp_ajax_bookactiArchiveDeleteFile', 'bookacti_controller_archive_delete_file' );




// LICENSES

/**
 * Display a description in the Licenses settings tab
 * @since 1.7.14
 */
function bookacti_display_licenses_settings_description() {
	$active_add_ons = bookacti_get_active_add_ons();
	if( ! $active_add_ons ) { 
		?>
		<div class='bookacti-licenses-settings-description'>
			<p><?php esc_html_e( 'Here you will be able to activate your add-ons license keys.', 'booking-activities' ); ?></p>
			<h3>
				<?php 
				/* translators: %s is a link to "Booking Activities add-ons" (link label) shop */
				echo sprintf( esc_html__( 'Purchase %s now!', 'booking-activities' ), ' <a href="https://booking-activities.fr/en/add-ons/" target="_blank">' . esc_html__( 'Booking Activities add-ons', 'booking-activities' ) . '</a>' );
				?>
			</h3>
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
add_action( 'bookacti_licenses_settings', 'bookacti_display_licenses_settings_description', 10, 0 );




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
	$methods[ 'phone' ] = esc_html__( 'Phone', 'booking-activities' );
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
   $links = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=bookacti_settings' ) . '" title="' . esc_attr( __( 'Manage Booking Activities Settings', 'booking-activities' ) ) . '">' . __( 'Settings', 'booking-activities' ) . '</a>' ) + $links;
   return $links;
}
add_filter( 'plugin_action_links_' . BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '.php', 'bookacti_action_links_in_plugins_table', 10, 1 );


/** 
 * Add meta links in plugins table
 * @version 1.7.3
 * @param array $links
 * @param string $file
 * @return array
 */
function bookacti_meta_links_in_plugins_table( $links, $file ) {
   if ( $file == BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '.php' ) {
		$links[ 'docs' ]	= '<a href="' . esc_url( 'https://booking-activities.fr/en/documentation/user-documentation/?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) . '" title="' . esc_attr( __( 'View Booking Activities Documentation', 'booking-activities' ) ) . '" target="_blank" >' . esc_html__( 'Docs', 'booking-activities' ) . '</a>';
		$links[ 'report' ]	= '<a href="' . esc_url( 'https://github.com/bookingactivities/booking-activities/issues/' ) . '" title="' . esc_attr( __( 'Report a bug or request a feature', 'booking-activities' ) ) . '" target="_blank" >' . esc_html__( 'Report & Request', 'booking-activities' ) . '</a>';
		$links[ 'contact' ]	= '<a href="' . esc_url( 'https://booking-activities.fr/en/?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list#contact' ) . '" title="' . esc_attr( __( 'Contact us directly', 'booking-activities' ) ) . '" target="_blank" >' . esc_html__( 'Contact us', 'booking-activities' ) . '</a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'bookacti_meta_links_in_plugins_table', 10, 2 );




// ADMIN PROMO NOTICES

/** 
 * Ask to rate the plugin 5 stars
 * @version 1.7.16
 */
function bookacti_5stars_rating_notice() {
	if( ! bookacti_is_booking_activities_screen() ) { return; }
	$dismissed = get_option( 'bookacti-5stars-rating-notice-dismissed' );
	if( ! $dismissed ) {
		if( current_user_can( 'bookacti_manage_booking_activities' ) ) {
			$install_date = get_option( 'bookacti-install-date' );
			if( ! empty( $install_date ) ) {
				$install_datetime	= DateTime::createFromFormat( 'Y-m-d H:i:s', $install_date );
				$current_datetime	= new DateTime();
				$nb_days			= floor( $install_datetime->diff( $current_datetime )->days );
				if( $nb_days >= 61 ) {
					?>
					<div class='notice notice-info bookacti-5stars-rating-notice is-dismissible' >
						<p>
							<?php 
							_e( '<strong>Booking Activities</strong> has been helping you for two months now.', 'booking-activities' );
							echo '<br/>' 
								/* translators: %s: five stars */
								. sprintf( esc_html__( 'Would you help us back leaving a %s rating? We need you too.', 'booking-activities' ), 
								  '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
							?>
						</p>
						<p>
							<a class='button' href='<?php echo esc_url( 'https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post' ); ?>' target='_blank' ><?php esc_html_e( 'Ok, I\'ll rate you five stars!', 'booking-activities' ); ?></a>
							<span class='button' id='bookacti-dismiss-5stars-rating' ><?php esc_html_e( 'I already rated you, hide this message', 'booking-activities' ); ?></span>
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
 * Remove Rate-us-5-stars notice
 * @version 1.8.0
 */
function bookacti_dismiss_5stars_rating_notice() {
	// Check nonce, no need to check capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_dismiss_5stars_rating_notice', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'dismiss_5stars_rating_notice' ); }
	
	$is_allowed = current_user_can( 'bookacti_manage_booking_activities' );
	if( ! $is_allowed ) { bookacti_send_json_not_allowed( 'dismiss_5stars_rating_notice' ); }
	
	$updated = update_option( 'bookacti-5stars-rating-notice-dismissed', 1 );
	if( ! $updated ) { 
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ), 'dismiss_5stars_rating_notice' );
	}
	
	bookacti_send_json( array( 'status' => 'success' ), 'dismiss_5stars_rating_notice' );
}
add_action( 'wp_ajax_bookactiDismiss5StarsRatingNotice', 'bookacti_dismiss_5stars_rating_notice' );


/**
 * Display a custom message in the footer
 * @version 1.7.16
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
			$footer_text = sprintf( __( 'Does <strong>Booking Activities</strong> help you? Help us back leaving a %s rating. We need you too.', 'booking-activities' ), '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
		}
	}

	return $footer_text;
}
add_filter( 'admin_footer_text', 'bookacti_admin_footer_text', 10, 1 );




// PRIVACY

/**
 * Register the personal data exporters for privacy
 * @since 1.7.0
 * @param array $exporters
 * @return array
 */
function bookacti_register_privacy_exporters( $exporters ) {
	$exporters[ 'bookacti-user' ] = array(
		'exporter_friendly_name' => 'Booking Activities user data',
		'callback' => 'bookacti_privacy_exporter_user_data',
	);
	$exporters[ 'bookacti-bookings' ] = array(
		'exporter_friendly_name' => 'Booking Activities user bookings data',
		'callback' => 'bookacti_privacy_exporter_bookings_data',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'bookacti_register_privacy_exporters', 10 );


/**
 * Register the personal data erasers for privacy
 * @since 1.7.0
 * @param array $erasers
 * @return array
 */
function bookacti_register_privacy_erasers( $erasers ) {
	$erasers[ 'bookacti-user' ] = array(
		'eraser_friendly_name' => 'Booking Activities user data',
		'callback' => 'bookacti_privacy_eraser_user_data',
	);
	$erasers[ 'bookacti-bookings' ] = array(
		'eraser_friendly_name' => 'Booking Activities user bookings data',
		'callback' => 'bookacti_privacy_eraser_bookings_data',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'bookacti_register_privacy_erasers', 10 );




// PLUGIN EDITOR

/**
 * Allow to edit Booking Activities log files (for easier debugging process)
 * @since 1.7.0
 * @param array $editable_extensions
 * @param string $plugin
 * @return array
 */
function bookacti_add_editable_extensions( $editable_extensions, $plugin ) {
	if( $plugin !== BOOKACTI_PLUGIN_NAME . '/' . BOOKACTI_PLUGIN_NAME . '.php' 
	||  in_array( 'log', $editable_extensions, true ) ) { 
		return $editable_extensions; 
	}
	
	$editable_extensions[] = 'log';
	return $editable_extensions;
}
add_filter( 'editable_extensions', 'bookacti_add_editable_extensions', 10, 2 );




// AJAX SELECTBOXES

/**
 * Search users for AJAX selectbox
 * @since 1.7.19
 * @version 1.8.3
 */
function bookacti_controller_search_select2_users() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'bookacti_query_select2_options', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_invalid_nonce( 'search_select2_users' ); }
	
	// Check permission
	if( ! current_user_can( 'list_users' ) && ! current_user_can( 'edit_users' ) ) { bookacti_send_json_not_allowed( 'search_select2_users' ); }
	
	// Sanitize search
	$term			= isset( $_REQUEST[ 'term' ] ) ? sanitize_text_field( stripslashes( $_REQUEST[ 'term' ] ) ) : '';
	$id__in			= ! empty( $_REQUEST[ 'id__in' ] ) ? bookacti_ids_to_array( $_REQUEST[ 'id__in' ] ) : array();
	$id__not_in		= ! empty( $_REQUEST[ 'id__not_in' ] ) ? bookacti_ids_to_array( $_REQUEST[ 'id__not_in' ] ) : array();
	$role			= ! empty( $_REQUEST[ 'role' ] ) ? bookacti_str_ids_to_array( $_REQUEST[ 'role' ] ) : array();
	$role__in		= ! empty( $_REQUEST[ 'role__in' ] ) ? bookacti_str_ids_to_array( $_REQUEST[ 'role__in' ] ) : array();
	$role__not_in	= ! empty( $_REQUEST[ 'role__not_in' ] ) ? bookacti_str_ids_to_array( $_REQUEST[ 'role__not_in' ] ) : array();
	
	// Check if the search is not empty
	if( ! $term && ! $id__in && ! $role && ! $role__in ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'empty_query' ), 'search_select2_users' ); }
	
	$defaults = array(
		'name' => isset( $_REQUEST[ 'name' ] ) ? sanitize_title_with_dashes( stripslashes( $_REQUEST[ 'name' ] ) ) : '', // Used for developers to identify the selectbox
		'id' => isset( $_REQUEST[ 'id' ] ) ? sanitize_title_with_dashes( stripslashes( $_REQUEST[ 'id' ] ) ) : '',		 // Used for developers to identify the selectbox
		'search' => $term !== '' ? '*' . esc_attr( $term ) . '*' : '',
		'search_columns' => array( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
		'option_label' => array( 'first_name', ' ', 'last_name', ' (', 'user_login', ' / ', 'user_email', ')' ),
		'allow_current' => 0,
		'include' => $id__in, 'exclude' => $id__not_in,
		'role' => $role, 'role__in' => $role__in, 'role__not_in' => $role__not_in,
		'meta' => true, 'meta_single' => true,
		'orderby' => 'display_name', 'order' => 'ASC'
	);
	$args = apply_filters( 'bookacti_ajax_select2_users_args', $defaults );
	
	$users = bookacti_get_users_data( $args );
	$options = array();
	
	// Add "Current user" option
	if( ! empty( $_REQUEST[ 'allow_current' ] ) ) {
		$options[] = array( 'id' => 'current', 'text' => esc_html__( 'Current user', 'booking-activities' ) );
	}
	
	// Add user options
	foreach( $users as $user ) {
		// Build the option label based on the array
		$label = '';
		foreach( $args[ 'option_label' ] as $show ) {
			// If the key contain "||" display the first not empty value
			if( strpos( $show, '||' ) !== false ) {
				$keys = explode( '||', $show );
				$show = $keys[ 0 ];
				foreach( $keys as $key ) {
					if( ! empty( $user->{ $key } ) ) { $show = $key; break; }
				}
			}

			// Display the value if the key exists, else display the key as is, as a separator
			if( isset( $user->{ $show } ) ) {
				$label .= $user->{ $show };
			} else {
				$label .= $show;
			}
		}
		$options[] = array( 'id' => $user->ID, 'text' => esc_html( $label ) );
	}
	
	// Allow plugins to add their values
	$select2_options = apply_filters( 'bookacti_ajax_select2_users_options', $options, $args );
	
	if( ! $select2_options ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_results' ), 'search_select2_users' );
	}
	
	bookacti_send_json( array( 'status' => 'success', 'options' => $select2_options ), 'search_select2_users' );
}
add_action( 'wp_ajax_bookactiSelect2Query_users', 'bookacti_controller_search_select2_users' );