<?php 
/**
 * Calendar editor dialogs
 * @version 1.15.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Templates options list
if( empty( $templates ) ) { $templates = bookacti_get_templates_data(); }
$templates_options = array();
foreach( $templates as $template ) { $templates_options[ $template[ 'id' ] ] = esc_html( $template[ 'title' ] ); }
?>

<!-- Edit event dialog -->
<div id='bookacti-event-data-dialog' class='bookacti-backend-dialog bookacti-template-dialog' data-event-id='0' title='<?php esc_html_e( 'Event parameters', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-event-data-form' >
		<?php wp_nonce_field( 'bookacti_update_event_data', 'nonce_update_event_data' ); ?>
		<input type='hidden' name='id' id='bookacti-event-data-form-event-id' value='' />
		<input type='hidden' name='start' id='bookacti-event-data-form-event-start' value='' />
		<input type='hidden' name='end' id='bookacti-event-data-form-event-end' value='' />
		<input type='hidden' name='action' id='bookacti-event-data-form-action' value='bookactiUpdateEvent' />
		
		<div id='bookacti-event-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		// Fill the array of tabs with their label, callback for content and display order
		$event_tabs = apply_filters( 'bookacti_event_dialog_tabs', array (
			array(	'label'			=> esc_html__( 'General', 'booking-activities' ),
					'callback'		=> 'bookacti_fill_event_tab_general',
					'parameters'	=> array(),
					'order'			=> 10 ),
			array(	'label'			=> esc_html__( 'Repetition', 'booking-activities' ),
					'callback'		=> 'bookacti_fill_event_tab_repetition',
					'parameters'	=> array(),
					'order'			=> 20 )
		) );

		// Display tabs
		bookacti_display_tabs( $event_tabs, 'event' );

		
		/**
		 * Fill the "event" tab of the Event settings dialog
		 * @version 1.14.0
		 * @param array $params
		 */
		function bookacti_fill_event_tab_general( $params ) {
			do_action( 'bookacti_event_tab_general_before', $params );
						
			$fields = array(
				'title' => array(
					'type'        => 'textarea',
					'name'        => 'title',
					'id'          => 'bookacti-event-title',
					'class'       => 'bookacti-translatable',
					'title'       => esc_html__( 'Title', 'booking-activities' ),
					'fullwidth'   => 1,
					'placeholder' => esc_html__( 'Give your event a specific title.', 'booking-activities' ) . ' ' 
					              . esc_html__( 'It will override the activity setting on this event only.', 'booking-activities' ),
					'tip'         => esc_html__( 'Give your event a specific title.', 'booking-activities' ) . ' ' 
					              . esc_html__( 'It will override the activity setting on this event only.', 'booking-activities' )
				),
				'availability' => array(
					'type'    => 'number',
					'name'    => 'availability',
					'id'      => 'bookacti-event-availability',
					'title'   => esc_html__( 'Availability', 'booking-activities' ),
					'options' => array( 'min' => 0, 'step' => 1 ),
					'tip'     => esc_html__( 'Set the amount of bookings that can be made on this event.', 'booking-activities' ) . ' '
					          . esc_html__( 'It will override the activity setting on this event only.', 'booking-activities' )
				)
			);
			
			bookacti_display_fields( $fields );
		
			do_action( 'bookacti_event_tab_general_after', $params );
			
			bookacti_promo_for_bapap_addon( 'event' );
		}

		
		/**
		 * Display the 'Repetition' tab content of event settings
		 * @version 1.14.0
		 * @param array $params
		 */
		function bookacti_fill_event_tab_repetition( $params ) {
			do_action( 'bookacti_event_tab_repetition_before', $params );
		?>
			<div class='bookacti-field-container' id='bookacti-event-repeat-freq-container'>
				<label for='bookacti-event-repeat-freq'>
				<?php 
					/* translators: Followed by a number of days / weeks / months. E.g.: Repeat every 2 days / weeks / months. */ 
					esc_html_e( 'Repeat every', 'booking-activities' );
				?>
				</label>
			<?php
				// Display the number input
				bookacti_display_field( array(
					'type'    => 'number',
					'name'    => 'repeat_step',
					'id'      => 'bookacti-event-repeat-step',
					'options' => array( 'min' => 1, 'max' => 9999, 'step' => 1 ),
					'value'   => 1,
				) ); 

				// Display the selectbox
				bookacti_display_field( array(
					'type'    => 'select',
					'name'    => 'repeat_freq',
					'id'      => 'bookacti-event-repeat-freq',
					'options' => bookacti_get_event_repeat_periods(),
					'value'   => 'none',
					'tip'     => esc_html__( 'Set the repetition frequency. This will create an occurrence of the event on each corresponding date.', 'booking-activities' )
				) );

				$start_of_week = intval( get_option( 'start_of_week' ) );
				$weekdays = array( 0 => esc_html__( 'Sunday' ), 1 => esc_html__( 'Monday' ), 2 => esc_html__( 'Tuesday' ), 3 => esc_html__( 'Wednesday' ), 4 => esc_html__( 'Thursday' ), 5 => esc_html__( 'Friday' ), 6 => esc_html__( 'Saturday' ) );
				$start_of_weekday = $weekdays[ $start_of_week ];
			?>
				<div class='bookacti-repeat-freq-start-of-week-notice bookacti-info' data-start-of-week='<?php echo $start_of_week; ?>'>
					<span class='dashicons dashicons-warning'></span>
					<span>
						<?php 
							/* translators: %1$s = "Week Starts On" option label. %2$s = "Week Starts On" option value (e.g.: Monday). %3$s = link to "WordPress Settings > General" */
							echo sprintf( esc_html__( 'You have set the "%1$s" option to "%2$s" in %3$s. This setting will be used to skip weeks.', 'booking-activities' ), 
									'<strong>' . esc_html__( 'Week Starts On' ) . '</strong>', 
									'<strong>' . $weekdays[ $start_of_week ] . '</strong>', 
									'<a href="' . admin_url( 'options-general.php' ) . '">' . esc_html__( 'WordPress Settings > General', 'booking-activities' ) . '</a>' );
						?>
					</span>
				</div>
			</div>
		<?php
			$fields = array(
				'repeat_days' => array( 
					'name'  => 'repeat_days',
					'type'  => 'checkboxes',
					'id'    => 'bookacti-event-repeat-days',
					'class' => 'bookacti-repeat-days',
					/* translators: followed by checkboxes having the names of the days of the week. E.g.: Repeat on Mondays, Tuesdays and Fridays. */
					'title' => esc_html_x( 'Repeat on', 'weekly repetition', 'booking-activities' ),
					'value' => array( '1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 1, '6' => 1, '0' => 1 ), 
					'options'	=> array( 
						array( 'id' => '1', 'label' => esc_html__( 'Mondays', 'booking-activities' ) ),
						array( 'id' => '2', 'label' => esc_html__( 'Tuesdays', 'booking-activities' ) ),
						array( 'id' => '3', 'label' => esc_html__( 'Wednesdays', 'booking-activities' ) ),
						array( 'id' => '4', 'label' => esc_html__( 'Thursdays', 'booking-activities' ) ),
						array( 'id' => '5', 'label' => esc_html__( 'Fridays', 'booking-activities' ) ),
						array( 'id' => '6', 'label' => esc_html__( 'Saturdays', 'booking-activities' ) ),
						array( 'id' => '0', 'label' => esc_html__( 'Sundays', 'booking-activities' ) )
					),
					'tip'   => esc_html__( 'Select the days of the week on which the event will be repeated.', 'booking-activities' )
				),
				'repeat_monthly_type' => array( 
					'name'    => 'repeat_monthly_type',
					'type'    => 'select',
					'id'      => 'bookacti-event-repeat-monthly_type',
					'class'   => 'bookacti-repeat-monthly_type',
					/* translators: followed by a selectox with the following values. E.g.: Repeat on the 21st of each month / Repeat on the 2nd Monday of each month / Repeat on the last day of each month / Repeat on the last Monday of each month */
					'title'   => esc_html_x( 'Repeat on', 'monthly repetition', 'booking-activities' ),
					'options' => array( 
						'nth_day_of_month'  => 'nth_day_of_month',
						'last_day_of_month' => 'last_day_of_month',
						'nth_day_of_week'   => 'nth_day_of_week',
						'last_day_of_week'  => 'last_day_of_week'
					),
					'attr'	=> array( 
						/* translators: Keep the {nth_day_of_month} tag as is. Selectbox option, comes after "Repeat on". E.g.: [Repeat on] on the 21st each month. */
						'nth_day_of_month'  => 'data-default-label="' . esc_html__( 'on the {nth_day_of_month} each month', 'booking-activities' ) . '"',
						/* translators: Selectbox option, comes after by "Repeat on the". E.g.: [Repeat on] on the last day of each month. */
						'last_day_of_month' => 'data-default-label="' . esc_html__( 'on the last day of each month', 'booking-activities' ) . '"',
						/* translators: Keep the {nth_day_of_week} and {day_of_week} tags as is. Selectbox option, comes after by "Repeat on". E.g.: [Repeat on] on the 2nd Monday of each month. */
						'nth_day_of_week'   => 'data-default-label="' . esc_html__( 'on the {nth_day_of_week} {day_of_week} of each month', 'booking-activities' ) . '"',
						/* translators: Keep the {day_of_week} tag as is. Selectbox option, comes after by "Repeat on". E.g.: [Repeat on] on the last Monday of each month. */
						'last_day_of_week'  => 'data-default-label="' . esc_html__( 'on the last {day_of_week} of each month', 'booking-activities' ) . '"'
					),
					'tip'		=> esc_html__( 'Select the day of the month on which the event will be repeated.', 'booking-activities' )
				),
				'repeat_from' => array(
					'type'    => 'date',
					'name'    => 'repeat_from',
					'id'      => 'bookacti-event-repeat-from',
					'class'   => 'bookacti-repeat-from',
					'title'   => esc_html__( 'Repeat from', 'booking-activities' ),
					'options' => array( 'max' => '2037-12-31' ),
					'tip'     => esc_html__( 'Set the starting date of the repetition. The occurrences of the event will be added from this date.', 'booking-activities' )
				),
				'repeat_to' => array(
					'type'    => 'date',
					'name'    => 'repeat_to',
					'id'      => 'bookacti-event-repeat-to',
					'class'   => 'bookacti-repeat-to',
					'title'   => esc_html__( 'Repeat to', 'booking-activities' ),
					'options' => array( 'max' => '2037-12-31' ),
					'tip'     => esc_html__( 'Set the ending date of the repetition. The occurrences of the event will be added until this date.', 'booking-activities' )
				),
				'repeat_exceptions' => array(
					'type'  => 'custom_date_intervals',
					'name'  => 'repeat_exceptions',
					'id'    => 'bookacti-event-repeat-exceptions',
					'class' => 'bookacti-repeat_exceptions',
					'value' => array(),
					'title' => esc_html__( 'Exceptions', 'booking-activities' ),
					'tip'   => esc_html__( 'No occurrences will be displayed between these dates.', 'booking-activities' )
				)
			);
			bookacti_display_fields( $fields );

			do_action( 'bookacti_event_tab_repetition_after', $params );
		}
		?>
	</form>
</div>


<!-- Template params -->
<div id='bookacti-template-data-dialog' class='bookacti-backend-dialog bookacti-template-dialog tabs' title='<?php esc_html_e( 'Calendar parameters', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-template-data-form' >
		<?php wp_nonce_field( 'bookacti_insert_or_update_template', 'nonce_insert_or_update_template' ); ?>
		<input type='hidden' name='template_id' id='bookacti-template-data-form-template-id' value='' />
		<input type='hidden' name='action' id='bookacti-template-data-form-action' value='' />
		<div id='bookacti-template-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php 
			// Fill the array of tabs with their label, callback for content and display order
			$template_tabs = apply_filters( 'bookacti_template_dialog_tabs', array (
				array(	'label'      => esc_html__( 'General', 'booking-activities' ),
						'callback'   => 'bookacti_fill_template_tab_general',
						'parameters' => array( 'templates_options' => $templates_options ),
						'order'      => 10 ),
				array(	'label'      => esc_html__( 'Editor', 'booking-activities' ),
						'callback'   => 'bookacti_fill_template_tab_editor',
						'parameters' => array(),
						'order'      => 40 ),
				array(	'label'      => esc_html__( 'Permissions', 'booking-activities' ),
						'callback'   => 'bookacti_fill_template_tab_permissions',
						'parameters' => array(),
						'order'      => 100 )
			) );
			
			// Display tabs
			bookacti_display_tabs( $template_tabs, 'template' );
			
			
			/**
			 * Display the 'General' tab content of template settings
			 * @version 1.14.0
			 * @param array $params
			 */
			function bookacti_fill_template_tab_general( $params = array() ) {
				$templates_options = isset( $params[ 'templates_options' ] ) ? $params[ 'templates_options' ] : array();
				do_action( 'bookacti_template_tab_general_before', $params );
				
				$fields = array(
					'title' => array(
						'type'  => 'text',
						'name'  => 'title',
						'id'    => 'bookacti-template-title',
						'class' => 'bookacti-translatable',
						'title' => esc_html__( 'Title', 'booking-activities' ),
						'tip'   => esc_html__( 'Give your calendar a title to easily recognize it in a list.', 'booking-activities' )
					),
					'duplicated_template_id' => array(
						'type'    => 'select',
						'name'    => 'duplicated_template_id',
						'id'      => 'bookacti-template-duplicated-template-id',
						'class'   => 'bookacti-template-select-box',
						'title'   => esc_html__( 'Duplicate from', 'booking-activities' ),
						'options' => array( 0 => esc_html__( 'Don\'t duplicate', 'booking-activities' ) ) + $templates_options,
						'tip'     => esc_html__( 'If you want to duplicate a calendar, select it in the list. It will copy its events, activities list, and its settings but not the bookings made on it.', 'booking-activities' )
					)
				);
				
				bookacti_display_fields( $fields );
				
				do_action( 'bookacti_template_tab_general_after', $params );
			}
			
			
			/**
			 * Fill the "Editor" tab in calendar settings
			 * @since 1.7.18 (was bookacti_fill_template_tab_agenda)
			 * @version 1.15.0
			 * @param array $params
			 */
			function bookacti_fill_template_tab_editor( $params = array() ) {
			?>
				<div class='bookacti-backend-settings-only-notice bookacti-warning'>
					<span class='dashicons dashicons-warning'></span>
					<span>
					<?php
						/* translators: %s is a link to the "booking form editor". */
						echo sprintf( esc_html__( 'These settings are used for the editor only.', 'booking-activities' ) . ' ' .  esc_html__( 'For your frontend calendars, use the "Calendar" field settings in the desired %s.', 'booking-activities' ), '<a href="' . admin_url( 'admin.php?page=bookacti_forms' ) . '">' . esc_html__( 'booking form editor', 'booking-activities' ) . '</a>' );
					?>
					</span>
				</div>
			<?php
				do_action( 'bookacti_template_tab_editor_before', $params );
			?>
				<fieldset>
					<legend><?php esc_html_e( 'Time Grid views', 'booking-activities' ); ?></legend>
					<?php
						$timeGrid_fields = array( 'slotMinTime', 'slotMaxTime', 'snapDuration' );
						$fields = apply_filters( 'bookacti_template_tab_editor_time_grid_fields', bookacti_get_fullcalendar_fields_default_data( $timeGrid_fields ) );
						bookacti_display_fields( $fields );
					?>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Display', 'booking-activities' ); ?></legend>
					<?php
						$display_fields = array( 'days_off' );
						$fields = apply_filters( 'bookacti_template_tab_editor_display_fields', bookacti_get_booking_system_fields_default_data( $display_fields ) );
						bookacti_display_fields( $fields );
					?>
				</fieldset>
			<?php
				do_action( 'bookacti_template_tab_editor_after', $params );
				
				bookacti_display_badp_promo();
			}
			
			
			/**
			 * Display the 'Permission' tab content of calendar settings
			 * @version 1.12.0
			 * @param array $params
			 */
			function bookacti_fill_template_tab_permissions( $params = array() ) {
				do_action( 'bookacti_template_tab_permissions_before', $params );
				
				$template_managers_cap = array( 'bookacti_manage_bookings', 'bookacti_edit_bookings', 'bookacti_edit_templates', 'bookacti_read_templates' );
				$template_managers_args = array(
					'option_label' => array( 'display_name', ' (', 'user_login', ')' ), 
					'id'           => 'bookacti-add-new-template-managers-select-box', 
					'name'         => '', 
					'class'        => 'bookacti-add-new-items-select-box bookacti-managers-selectbox',
					'role__in'     => apply_filters( 'bookacti_managers_roles', array_merge( bookacti_get_roles_by_capabilities( $template_managers_cap ), $template_managers_cap ), 'template' ),
					'role__not_in' => apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ), 'template' ),
					'meta'         => false,
					'ajax'         => 0
				);
			?>	
				<div id='bookacti-template-managers-container' class='bookacti-items-container' data-type='users' >
					<label id='bookacti-template-managers-title' class='bookacti-fullwidth-label' for='bookacti-add-new-template-managers-select-box' >
					<?php 
						esc_html_e( 'Who can manage this calendar?', 'booking-activities' );
						$tip  = esc_html__( 'Choose who is allowed to access this calendar.', 'booking-activities' );
						/* translators: %s = comma separated list of user roles */
						$tip .= '<br/>' . sprintf( esc_html__( 'These roles already have this privilege: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', array_intersect_key( bookacti_get_roles(), array_flip( $template_managers_args[ 'role__not_in' ] ) ) ) . '</code>' );
						/* translators: %s = capabilities name */
						$tip .= '<br/>' . sprintf( esc_html__( 'If the selectbox is empty, it means that no other users have these capabilities: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', $template_managers_cap ) . '</code>' );
						/* translators: %1$s = Order for Customers add-on link. */
						$tip .= '<br/>' . sprintf( esc_html__( 'Operators from %1$s add-on have these capabilities.', 'booking-activities' ), '<a href="https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=infobulle-permission" target="_blank" >Order for Customers</a>' );
						/* translators: %1$s = User Role Editor plugin link. */
						$tip .= ' ' . sprintf( esc_html__( 'If you want to grant a user these capabilities, use a plugin such as %1$s.', 'booking-activities' ), '<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank">User Role Editor</a>' );
						bookacti_help_tip( $tip );
					?>
					</label>
					<div id='bookacti-add-template-managers-container' class='bookacti-add-items-container' >
						<?php bookacti_display_user_selectbox( $template_managers_args ); ?>
						<button type='button' id='bookacti-add-template-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', 'booking-activities' ); ?></button>
					</div>
					<div id='bookacti-template-managers-list-container' class='bookacti-items-list-container' >
						<select name='managers[]' id='bookacti-template-managers-select-box' class='bookacti-items-select-box' multiple></select>
						<button type='button' id='bookacti-remove-template-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', 'booking-activities' ); ?></button>
					</div>
				</div>
			<?php 
				do_action( 'bookacti_template_tab_permissions_after', $params );
			} ?>
	</form>
</div>


<!-- Activity param -->
<div id='bookacti-activity-data-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Activity parameters', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-activity-data-form' >
		<?php wp_nonce_field( 'bookacti_insert_or_update_activity', 'nonce_insert_or_update_activity' ); ?>
		<input type='hidden' name='template_id' id='bookacti-activity-template-id' />
		<input type='hidden' name='activity_id' id='bookacti-activity-activity-id' />
		<input type='hidden' name='action'		id='bookacti-activity-action' />
		
		<div id='bookacti-activity-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
			
			<?php
				// Fill the array of tabs with their label, callback for content and display order
				$activity_tabs = apply_filters( 'bookacti_activity_dialog_tabs', array (
					array(	'label'      => esc_html__( 'General', 'booking-activities' ),
							'callback'   => 'bookacti_fill_activity_tab_general',
							'parameters' => array(),
							'order'      => 10 ),
					array(	'label'      => esc_html__( 'Availability', 'booking-activities' ),
							'callback'   => 'bookacti_fill_activity_tab_availability',
							'parameters' => array(),
							'order'      => 20 ),
					array(	'label'      => esc_html__( 'Text', 'booking-activities' ),
							'callback'   => 'bookacti_fill_activity_tab_text',
							'parameters' => array(),
							'order'      => 30 ),
					array(	'label'      => esc_html__( 'Permissions', 'booking-activities' ),
							'callback'   => 'bookacti_fill_activity_tab_permissions',
							'parameters' => array(),
							'order'      => 100 )
				) );
				
				// Display tabs
				bookacti_display_tabs( $activity_tabs, 'activity' );
			?>
			
			<?php
			/**
			 * Display the 'General' tab content of activity settings
			 * @version 1.14.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_general( $params = array() ) {
				do_action( 'bookacti_activity_tab_general_before', $params );
				
				$fields = array(
					'activity-title' => array(
						'type'  => 'text',
						'name'  => 'title',
						'id'    => 'bookacti-activity-title',
						'class'	=> 'bookacti-translatable',
						'title' => esc_html__( 'Title', 'booking-activities' ),
						'tip'   => esc_html__( 'Choose a short and relevant title for your activity. It will be shown on each events.', 'booking-activities' )
					),
					'activity-availability' => array(
						'type'    => 'number',
						'name'    => 'availability',
						'id'      => 'bookacti-activity-availability',
						'title'   => esc_html__( 'Default availability', 'booking-activities' ),
						'label'   => bookacti_help_tip( esc_html__( 'The default amount of bookings that can be made on each event of this activity. This can be overriden on each event independantly.', 'booking-activities' ), false ) 
						          . '<br/><small><em>' . esc_html__( 'Used when an event is created only', 'booking-activities' ) . '</em></small>',
						'options' => array( 'min' => 0, 'step' => 1 )
					),
					'activity-duration' => array(
						'type'  => 'duration',
						'name'  => 'duration',
						'id'    => 'bookacti-activity-duration',
						'title' => esc_html__( 'Default duration', 'booking-activities' ),
						'label' => bookacti_help_tip( esc_html__( 'The default duration of an event when you drop this activity onto the calendar. For a better readability, try not to go over your working hours. Best practice for events of several days is to create one event per day and then group them.', 'booking-activities' ), false )
						        . '<br/><small><em>' . esc_html__( 'Used when an event is created only', 'booking-activities' ) . '</em></small>'
					),
					'activity-color' => array(
						'type'  => 'color',
						'name'  => 'color',
						'id'    => 'bookacti-activity-color',
						'value' => '#3a87ad',
						'title' => esc_html__( 'Color', 'booking-activities' ),
						'tip'   => esc_html__( 'Choose a color for the events of this activity.', 'booking-activities' )
					)
				);
				
				bookacti_display_fields( $fields );
				
				do_action( 'bookacti_activity_tab_general_after', $params );
			}
			
			
			/**
			 * Display the fields in the "Availability" tab of the Activity dialog
			 * @since 1.4.0
			 * @version 1.12.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_availability( $params = array() ) {
				do_action( 'bookacti_activity_tab_availability_before', $params );
				
				$fields = array(
					'min_bookings_per_user' => array(
						'type'    => 'number',
						'name'    => 'min_bookings_per_user',
						'id'      => 'bookacti-activity-min-bookings-per-user',
						'title'   => esc_html__( 'Min bookings per user', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'The minimum booking quantity a user has to make on an event of this activity. E.g.: "3", the customer must book at least 3 places of the desired event.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'max_bookings_per_user' => array(
						'type'    => 'number',
						'name'    => 'max_bookings_per_user',
						'id'      => 'bookacti-activity-max-bookings-per-user',
						'title'   => esc_html__( 'Max bookings per user', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'The maximum booking quantity a user can make on an event of this activity. E.g.: "1", the customer can only book one place of the desired event, and he / she won\'t be allowed to book it twice.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'max_users_per_event' => array(
						'type'    => 'number',
						'name'    => 'max_users_per_event',
						'id'      => 'bookacti-activity-max-users-per-event',
						'title'   => esc_html__( 'Max users per event', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'Set how many different users can book the same event. E.g.: "1", only one user can book a specific event; once he / she has booked it, the event won\'t be available for anyone else anymore, even if it isn\'t full. Useful for private events.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'booking_changes_deadline' => array(
						'type'  => 'duration',
						'name'  => 'booking_changes_deadline',
						'id'    => 'bookacti-activity-booking-changes-deadline',
						/* translators: Followed by a field indicating a number of days, hours and minutes from now. E.g.: "Changes are allowed for bookings starting in at least 2 days, 12 hours, 25 minutes". */
						'title' => esc_html__( 'Changes are allowed for bookings starting in at least', 'booking-activities' ),
						'label' => bookacti_help_tip( esc_html__( 'Define when a customer can change a booking (cancel, reschedule). E.g.: "2 days 5 hours 30 minutes", your customers will be able to change the bookings starting in 2 days, 5 hours and 30 minutes at least. They won\'t be allowed to cancel a booking starting tomorrow for example.', 'booking-activities' )
						        . '<br/>' . esc_html__( 'This parameter applies to the events of this activity only. A global parameter is available in global settings.', 'booking-activities' )
						        . ' ' . esc_html__( 'Leave it empty to use the global value.', 'booking-activities' ), false )
								/* translators: %s = [bookingactivities_list] */
						        .  '<br/><small><em>' . sprintf( esc_html__( 'Bookings can be changed from the booking list only (%s)', 'booking-activities' ), '<a href="https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-customers-bookings-list-on-the-frontend/" target="_blank"><code style="font-size: inherit;">[bookingactivities_list]</code></a>' ) . '</em></small>'
					)
				);
				
				bookacti_display_fields( $fields );
			
				do_action( 'bookacti_activity_tab_availability_after', $params );
			}
			
			
			/**
			 * Display the fields in the "Text" tab of the Activity dialog
			 * @since 1.7.4 (was bookacti_fill_activity_tab_terminology)
			 * @version 1.14.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_text( $params = array() ) {
				do_action( 'bookacti_activity_tab_text_before', $params );
			
				$unit = '<strong><em>' . esc_html( _n( 'unit', 'units', 1, 'booking-activities' ) ) . '</em></strong>';
				$units = '<strong><em>' . esc_html( _n( 'unit', 'units', 2, 'booking-activities' ) ) . '</em></strong>';
				
				$fields = array(
					'unit_name_singular' => array(
						'type'  => 'text',
						'name'  => 'unit_name_singular',
						'id'    => 'bookacti-activity-unit-name-singular',
						'class' => 'bookacti-translatable',
						'title' => esc_html__( 'Unit name (singular)', 'booking-activities' ),
						/* translators: %s is the singular for "unit" */
						'tip'   => sprintf( esc_html__( 'Name of the unit the customers will actually book for this activity. Set the singular here. Leave blank to hide this piece of information. E.g.: "You have booked 1 %s".', 'booking-activities' ), $unit )
					),
					'unit_name_plural' => array(
						'type'  => 'text',
						'name'  => 'unit_name_plural',
						'id'    => 'bookacti-activity-unit-name-plural',
						'class' => 'bookacti-translatable',
						'title' => esc_html__( 'Unit name (plural)', 'booking-activities' ),
						/* translators: %s is the plural for "unit" */
						'tip'   => sprintf( esc_html__( 'Name of the unit the customers will actually book for this activity. Set the plural here. Leave blank to hide this piece of information. E.g.: "You have booked 2 %s".', 'booking-activities' ), $units )
					),
					'show_unit_in_availability' => array(
						'type'  => 'checkbox',
						'name'  => 'show_unit_in_availability',
						'id'    => 'bookacti-activity-show-unit-in-availability',
						'title' => esc_html__( 'Show unit in availability', 'booking-activities' ),
						/* translators: %s is the plural for "units" */
						'tip'   => sprintf( esc_html__( 'Show the unit in the availability boxes. E.g.: "2 %s available" instead of "2".', 'booking-activities' ), $units )
					),
					'places_number' => array(
						'type'    => 'number',
						'name'    => 'places_number',
						'id'      => 'bookacti-activity-places-number',
						'title'   => esc_html__( 'Number of places per booking', 'booking-activities' ),
						'options' => array( 'min' => 0 ),
						/* translators: %s is a number superior than or equal to 2. E.g.: 2. */
						'tip'     => sprintf( esc_html__( 'The number of people who can do the activity with 1 booking. Set 0 to hide this piece of information. E.g.: "You have booked 1 unit for %s people".', 'booking-activities' ), '<strong><em>2</em></strong>' )
					)
				);
				
				bookacti_display_fields( $fields );
				
				do_action( 'bookacti_activity_tab_text_after', $params );
			}
			
			/**
			 * Display the fields in the "Permissions" tab of the Activity dialog
			 * @version 1.13.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_permissions( $params = array() ) {
				do_action( 'bookacti_activity_tab_permissions_before', $params );
				
				// Allowed roles field
				$roles = get_editable_roles();
				$roles_options = array();
				foreach( $roles as $role_id => $role ) { $roles_options[ $role_id ] = $role[ 'name' ]; }
				?>
				<div class='bookacti-field-container' id='bookacti-activity-roles-container'>
					<input type='checkbox' name='is_restricted' id='bookacti-display-activity-user-roles'/>
					<label for='bookacti-display-activity-user-roles' class='bookacti-fullwidth-label'>
						<strong><?php esc_html_e( 'I want to restrict this activity to certain users only', 'booking-activities' ); ?></strong>
					</label>
					<?php
					$tip = esc_html__( 'Choose who is allowed to book the events of this activity.', 'booking-activities' )
						 . '<br/>' . esc_html__( 'Use CTRL+Click to pick or unpick a role.', 'booking-activities' ) 
						 . ' ' . esc_html__( 'Don\'t pick any role to allow everybody.', 'booking-activities' );
					bookacti_help_tip( $tip );
				
					$allowed_roles = array( 
						'type'      => 'select',
						'multiple'  => 1,
						'name'      => 'allowed_roles',
						'id'        => 'bookacti-activity-roles',
						'fullwidth' => 1,
						'options'   => array_merge( $roles_options, array( 'all' => esc_html__( 'Everybody', 'booking-activities' ) ) )
					);
					bookacti_display_field( $allowed_roles );
					?>
				
					<div class='bookacti-roles-notice bookacti-warning' style='margin-bottom:0;'>
						<span class='dashicons dashicons-info'></span>
						<span><?php esc_html_e( 'Don\'t pick any role to allow everybody.', 'booking-activities' ); ?></span>
					</div>
					<div class='bookacti-roles-notice bookacti-info'>
						<span class='dashicons dashicons-info'></span>
						<span><?php esc_html_e( 'Use CTRL+Click to pick or unpick a role.', 'booking-activities' ); ?></span>
					</div>
				</div>
				
				<?php
				// Managers
				$activity_managers_cap = array( 'bookacti_edit_bookings', 'bookacti_edit_templates', 'bookacti_read_templates' );
				$activity_managers_args = array(
					'option_label' => array( 'display_name', ' (', 'user_login', ')' ), 
					'id'           => 'bookacti-add-new-activity-managers-select-box', 
					'name'         => '', 
					'class'        => 'bookacti-add-new-items-select-box bookacti-managers-selectbox',
					'role__in'     => apply_filters( 'bookacti_managers_roles', array_merge( bookacti_get_roles_by_capabilities( $activity_managers_cap ), $activity_managers_cap ), 'activity' ),
					'role__not_in' => apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ), 'activity' ),
					'meta'         => false,
					'ajax'         => 0
				);
				?>
				<div id='bookacti-activity-managers-container' class='bookacti-items-container' data-type='users' >
					<label id='bookacti-activity-managers-title' class='bookacti-fullwidth-label' >
					<?php 
						esc_html_e( 'Who can manage this activity?', 'booking-activities' );
						$tip  = esc_html__( 'Choose who is allowed to access this activity.', 'booking-activities' );
						/* translators: %s = comma separated list of user roles */
						$tip .= '<br/>' . sprintf( esc_html__( 'These roles already have this privilege: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', array_intersect_key( bookacti_get_roles(), array_flip( $activity_managers_args[ 'role__not_in' ] ) ) ) . '</code>' );
						/* translators: %s = capabilities name */
						$tip .= '<br/>' . sprintf( esc_html__( 'If the selectbox is empty, it means that no other users have these capabilities: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', $activity_managers_cap ) . '</code>' );
						/* translators: %1$s = Order for Customers add-on link. */
						$tip .= '<br/>' . sprintf( esc_html__( 'Operators from %1$s add-on have these capabilities.', 'booking-activities' ), '<a href="https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=infobulle-permission" target="_blank" >Order for Customers</a>' );
						/* translators: %1$s = User Role Editor plugin link. */
						$tip .= ' ' . sprintf( esc_html__( 'If you want to grant a user these capabilities, use a plugin such as %1$s.', 'booking-activities' ), '<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>' );
						bookacti_help_tip( $tip );
					?>
					</label>
					<div id='bookacti-add-activity-managers-container' >
						<?php bookacti_display_user_selectbox( $activity_managers_args ); ?>
						<button type='button' id='bookacti-add-activity-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', 'booking-activities' ); ?></button>
					</div>
					<div id='bookacti-activity-managers-list-container' class='bookacti-items-list-container' >
						<select name='managers[]' id='bookacti-activity-managers-select-box' class='bookacti-items-select-box' multiple></select>
						<button type='button' id='bookacti-remove-activity-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', 'booking-activities' ); ?></button>
					</div>
				</div>
			<?php
				do_action( 'bookacti_activity_tab_permissions_after', $params );
			}
			?>
	</form>
</div>


<!-- Choose between creating a brand new activity or import an existing one -->
<div id='bookacti-activity-create-method-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Create a new activity or use an existing activity ?', 'booking-activities' ); ?>'  style='display:none;'>
	<div id='bookacti-activity-create-method-container' >
		<?php 
			/* translators: This is followed by "You can't:", and then a list of bans. */
			esc_html_e( 'Do you want to create a brand new activity or use on that calendar an activity you already created on an other calendar ?', 'booking-activities' ); 
		?>
	</div>
</div>


<!-- Import an existing activity -->
<div id='bookacti-activity-import-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Import existing activity', 'booking-activities' ); ?>' style='display:none;' >
    <div id='bookacti-activity-import-container' >
		<div>
			<?php esc_html_e( 'Import an activity that you have already created on an other calendar:', 'booking-activities' ); ?>
		</div>
		<?php 
			wp_nonce_field( 'bookacti_import_activity', 'nonce_import_activity', false );
			
			$fields = array(
				'template_to_import_activities_from' => array(
					'type'    => 'select',
					'name'    => 'template_to_import_activities_from',
					'id'      => 'template-import-bound-activities',
					'class'   => 'bookacti-template-select-box',
					/* translators: the user is asked to select a calendar to display its bound activities. This is the label of the select box. */
					'title'   => esc_html__( 'From calendar', 'booking-activities' ),
					'options' => $templates_options
				),
				'activities_to_import' => array(
					'type'     => 'select',
					'multiple' => 1,
					'name'     => 'activities_to_import',
					'id'       => 'bookacti-activities-to-import',
					/* translators: the user is asked to select an activity he already created on an other calendar in order to use it on the current calendar. This is the label of the select box. */
					'title'    => esc_html__( 'Activities to import', 'booking-activities' ),
					'options'  => array()
				)
			);
			bookacti_display_fields( $fields );
		?>
    </div>
</div>


<!-- Group of events -->
<div id='bookacti-group-of-events-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Group of events parameters', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-group-of-events-form' >
		<input type='hidden' name='action' id='bookacti-group-of-events-action' />
		<?php wp_nonce_field( 'bookacti_insert_or_update_group_of_events', 'nonce_insert_or_update_group_of_events' ); ?>
		
		<div id='bookacti-group-of-events-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
			
		<?php
		//Fill the array of tabs with their label, callback for content and display order
		$group_of_events_tabs = apply_filters( 'bookacti_group_of_events_dialog_tabs', array (
			array(	'label'      => esc_html__( 'General', 'booking-activities' ),
					'callback'   => 'bookacti_fill_group_of_events_tab_general',
					'parameters' => array(),
					'order'      => 10 ),
			array(	'label'      => esc_html__( 'Repetition', 'booking-activities' ),
					'callback'   => 'bookacti_fill_group_of_events_tab_repetition',
					'parameters' => array(),
					'order'      => 20 )
		) );

		// Display tabs
		bookacti_display_tabs( $group_of_events_tabs, 'group-of-events' );


		/**
		 * Fill "General" tab of "Group of Events" dialog
		 * @version 1.14.0
		 * @param array $params
		 */
		function bookacti_fill_group_of_events_tab_general( $params = array() ) {
			do_action( 'bookacti_group_of_events_tab_general_before', $params );
		?>
			<div>
				<?php
					$tip = esc_html__( 'Name this group of events. Your cutomers may see this name if they have several booking choices (if the event is in two groups, or if you also allow to book the event alone). Choose a short and relevant name.', 'booking-activities' );
					$args = array(
						'type'        => 'textarea',
						'name'        => 'title',
						'id'          => 'bookacti-group-of-events-title-field',
						'class'	      => 'bookacti-translatable',
						'placeholder' => $tip,
						'value'       => ''
					);
				?>
				<div>
					<label for='<?php echo $args[ 'id' ]; ?>' class='bookacti-fullwidth-label' >
					<?php 
						esc_html_e( 'Group title', 'booking-activities' );
						bookacti_help_tip( $tip );
					?>
					</label>
					<?php bookacti_display_field( $args ); ?>
				</div>
			</div>
			<div>
				<label for='bookacti-group-of-events-category-selectbox' ><?php esc_html_e( 'Group category', 'booking-activities' ); ?></label>
				<select name='category_id' id='bookacti-group-of-events-category-selectbox' >
					<option value='new' ><?php esc_html_e( 'New category', 'booking-activities' ); ?></option>
					<?php
						$template_id = get_user_meta( get_current_user_id(), 'bookacti_default_template', true );
						if( $template_id ) {
							$categories	= bookacti_get_group_categories( array( 'templates' => array( $template_id ) ) );
							foreach( $categories as $category ) {
								echo "<option value='" . $category[ 'id' ] . "' >" . $category[ 'title' ] . "</option>";
							}
						}
					?>
				</select>
				<?php
					$tip = esc_html__( 'Pick a category for your group of events.', 'booking-activities' );
					$tip .= esc_html__( 'Thanks to categories, you will be able to choose what groups of events are available on what booking forms.', 'booking-activities' );
					bookacti_help_tip( $tip );
				?>
			</div>
			<div id='bookacti-group-of-events-new-category-title' >
				<label for='bookacti-group-of-events-category-title-field' ><?php esc_html_e( 'New category title', 'booking-activities' ); ?></label>
				<input type='text' name='category_title' id='bookacti-group-of-events-category-title-field' class='bookacti-translatable'/>
				<?php
					$tip = esc_html__( 'Name the group of events category.', 'booking-activities' );
					$tip .= esc_html__( 'Thanks to categories, you will be able to choose what groups of events are available on what booking forms.', 'booking-activities' );
					bookacti_help_tip( $tip );
				?>
			</div>
			<div>
				<div id='bookacti-group-of-events-summary-label-container'>
					<label id='bookacti-group-of-events-summary-label' for='bookacti-group-of-events-summary'><?php esc_html_e( 'Selected events', 'booking-activities' ); ?></label>
					<div id='bookacti-group-of-events-occurrences-navigation'>
						<span id='bookacti-group-of-events-occurrences-undo' class='button' title='<?php esc_html_e( 'Events that will be saved', 'booking-activities' ) ?>'><span class='dashicons dashicons-undo'></span></span>
						<span id='bookacti-group-of-events-occurrences-reset' class='button' title='<?php esc_html_e( 'Currently saved events', 'booking-activities' ) ?>'><span class='dashicons dashicons-database-view'></span></span>
						<span id='bookacti-group-of-events-occurrences-prev' class='button' title='<?php esc_html_e( 'Previous occurrence', 'booking-activities' ) ?>'><span class='dashicons dashicons-arrow-left-alt2'></span></span>
						<span id='bookacti-group-of-events-occurrences-next' class='button' title='<?php esc_html_e( 'Next occurrence', 'booking-activities' ) ?>'><span class='dashicons dashicons-arrow-right-alt2'></span></span>
					</div>
				</div>
				<!-- This field is only used for feedback, it is not used to pass any AJAX data, events list is passed through an array made with JS -->
				<select multiple id='bookacti-group-of-events-summary' class='bookacti-custom-scrollbar bookacti-selected-events-list'></select>
				
				<div id='bookacti-group-of-events-summary-preview-notice' class='bookacti-backend-settings-only-notice bookacti-warning'>
					<span class='dashicons dashicons-warning'></span>
					<span><?php /* translators: %s = "undo" dashicons */ echo sprintf( esc_html__( 'This is a preview of a currently saved occurrence of the group, click the "%s" button to see the events that will be actually saved.', 'booking-activities' ), '<span class="dashicons dashicons-undo" title="' . esc_html__( 'Events that will be saved', 'booking-activities' ) . '"></span>' ); ?></span>
				</div>
			</div>
		<?php
			do_action( 'bookacti_group_of_events_tab_general_after', $params );

			bookacti_promo_for_bapap_addon( 'group-of-events' );
		}
		
			
		/**
		 * Display the 'Repetition' tab content of group of events settings
		 * @since 1.12.0
		 * @version 1.14.0
		 * @param array $params
		 */
		function bookacti_fill_group_of_events_tab_repetition( $params ) {
			do_action( 'bookacti_group_of_events_tab_repetition_before', $params );
		?>
			<div class='bookacti-field-container' id='bookacti-group-of-events-repeat-freq-container'>
				<label for='bookacti-group-of-events-repeat-freq'><?php esc_html_e( 'Repeat every', 'booking-activities' ); ?></label>
				<?php
					// Display the number input
					bookacti_display_field( array(
						'type'    => 'number',
						'name'    => 'repeat_step',
						'id'      => 'bookacti-group-of-events-repeat-step',
						'options' => array( 'min' => 1, 'max' => 9999, 'step' => 1 ),
						'value'   => 1,
					) ); 

					// Display the selectbox
					bookacti_display_field( array(
						'type'    => 'select',
						'name'    => 'repeat_freq',
						'id'      => 'bookacti-group-of-events-repeat-freq',
						'options' => bookacti_get_event_repeat_periods(),
						'value'   => 'none',
						'tip'     => esc_html__( 'Set the repetition frequency. The occurrences of the group of events starting on the corresponding dates will be generated.', 'booking-activities' )
					) );

					$start_of_week = intval( get_option( 'start_of_week' ) );
					$weekdays = array( 0 => esc_html__( 'Sunday' ), 1 => esc_html__( 'Monday' ), 2 => esc_html__( 'Tuesday' ), 3 => esc_html__( 'Wednesday' ), 4 => esc_html__( 'Thursday' ), 5 => esc_html__( 'Friday' ), 6 => esc_html__( 'Saturday' ) );
					$start_of_weekday = $weekdays[ $start_of_week ];
				?>
				<div class='bookacti-repeat-freq-start-of-week-notice bookacti-info' data-start-of-week='<?php echo $start_of_week; ?>'>
					<span class='dashicons dashicons-warning'></span>
					<span>
						<?php 
							echo sprintf( esc_html__( 'You have set the "%1$s" option to "%2$s" in %3$s. This setting will be used to skip weeks.', 'booking-activities' ), 
									'<strong>' . esc_html__( 'Week Starts On' ) . '</strong>', 
									'<strong>' . $weekdays[ $start_of_week ] . '</strong>', 
									'<a href="' . admin_url( 'options-general.php' ) . '">' . esc_html__( 'WordPress Settings > General', 'booking-activities' ) . '</a>' );
						?>
					</span>
				</div>
			</div>
		
			<div id='bookacti-group-of-events-repetition-first-event-notice' class='bookacti-backend-settings-only-notice bookacti-info'>
				<span class='dashicons dashicons-info'></span>
				<span><?php esc_html_e( 'The following options are based on the first event of the group.', 'booking-activities' ); ?></span>
			</div>
		
			<?php
				$fields = array(
					'repeat_days' => array( 
						'name'    => 'repeat_days',
						'type'    => 'checkboxes',
						'id'      => 'bookacti-group-of-events-repeat-days',
						'class'   => 'bookacti-repeat-days',
						/* translators: followed by checkboxes having the names of the days of the week. E.g.: Repeat on Mondays, Tuesdays and Fridays. */
						'title'   => esc_html_x( 'Repeat on', 'weekly repetition', 'booking-activities' ),
						'value'   => array( '1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 1, '6' => 1, '0' => 1 ), 
						'options' => array( 
							array( 'id' => '1', 'label' => esc_html__( 'Mondays', 'booking-activities' ) ),
							array( 'id' => '2', 'label' => esc_html__( 'Tuesdays', 'booking-activities' ) ),
							array( 'id' => '3', 'label' => esc_html__( 'Wednesdays', 'booking-activities' ) ),
							array( 'id' => '4', 'label' => esc_html__( 'Thursdays', 'booking-activities' ) ),
							array( 'id' => '5', 'label' => esc_html__( 'Fridays', 'booking-activities' ) ),
							array( 'id' => '6', 'label' => esc_html__( 'Saturdays', 'booking-activities' ) ),
							array( 'id' => '0', 'label' => esc_html__( 'Sundays', 'booking-activities' ) )
						),
						'tip'     => esc_html__( 'Select the days of the week on which the group of events will be repeated.', 'booking-activities' )
					),
					'repeat_monthly_type' => array( 
						'name'    => 'repeat_monthly_type',
						'type'    => 'select',
						'id'      => 'bookacti-group-of-events-repeat-monthly_type',
						'class'   => 'bookacti-repeat-monthly_type',
						/* translators: followed by a selectox with the following values. E.g.: Repeat on the 21st of each month / Repeat on the 2nd Monday of each month / Repeat on the last day of each month / Repeat on the last Monday of each month */
						'title'   => esc_html_x( 'Repeat on', 'monthly repetition', 'booking-activities' ),
						'options' => array( 
							'nth_day_of_month'  => 'nth_day_of_month',
							'last_day_of_month' => 'last_day_of_month',
							'nth_day_of_week'   => 'nth_day_of_week',
							'last_day_of_week'  => 'last_day_of_week'
						),
						'attr'    => array( 
							/* translators: Keep the {nth_day_of_month} tag as is. Selectbox option, comes after "Repeat on". E.g.: [Repeat on] on the 21st each month. */
							'nth_day_of_month'  => 'data-default-label="' . esc_html__( 'on the {nth_day_of_month} each month', 'booking-activities' ) . '"',
							/* translators: Selectbox option, comes after by "Repeat on the". E.g.: [Repeat on] on the last day of each month. */
							'last_day_of_month' => 'data-default-label="' . esc_html__( 'on the last day of each month', 'booking-activities' ) . '"',
							/* translators: Keep the {nth_day_of_week} and {day_of_week} tags as is. Selectbox option, comes after by "Repeat on". E.g.: [Repeat on] on the 2nd Monday of each month. */
							'nth_day_of_week'   => 'data-default-label="' . esc_html__( 'on the {nth_day_of_week} {day_of_week} of each month', 'booking-activities' ) . '"',
							/* translators: Keep the {day_of_week} tag as is. Selectbox option, comes after by "Repeat on". E.g.: [Repeat on] on the last Monday of each month. */
							'last_day_of_week'  => 'data-default-label="' . esc_html__( 'on the last {day_of_week} of each month', 'booking-activities' ) . '"'
						),
						'tip'     => esc_html__( 'Select the day of the month on which the group of events will be repeated.', 'booking-activities' )
					),
					'repeat_from' => array(
						'type'    => 'date',
						'name'    => 'repeat_from',
						'id'      => 'bookacti-group-of-events-repeat-from',
						'class'   => 'bookacti-repeat-from',
						'title'   => esc_html__( 'Repeat from', 'booking-activities' ),
						'options' => array( 'max' => '2037-12-31' ),
						'tip'     => esc_html__( 'Set the starting date of the repetition. The occurrences of the group of events starting after that date will be generated.', 'booking-activities' )
					),
					'repeat_to' => array(
						'type'    => 'date',
						'name'    => 'repeat_to',
						'id'      => 'bookacti-group-of-events-repeat-to',
						'class'   => 'bookacti-repeat-to',
						'title'   => esc_html__( 'Repeat to', 'booking-activities' ),
						'options' => array( 'max' => '2037-12-31' ),
						'tip'     => esc_html__( 'Set the ending date of the repetition. The occurrences of the group of events starting after that date won\'t be generated.', 'booking-activities' )
					),
					'repeat_exceptions' => array(
						'type'  => 'custom_date_intervals',
						'name'  => 'repeat_exceptions',
						'id'    => 'bookacti-group-of-events-repeat-exceptions',
						'class' => 'bookacti-repeat_exceptions',
						'value' => array(),
						'title' => esc_html__( 'Exceptions', 'booking-activities' ),
						'tip'   => esc_html__( 'No occurrences will be displayed between these dates.', 'booking-activities' )
					)
				);
				bookacti_display_fields( $fields );
			
				do_action( 'bookacti_group_of_events_tab_repetition_after', $params );
			?>
		
			<div class='bookacti-backend-settings-only-notice bookacti-info'>
				<span class='dashicons dashicons-info'></span>
				<span><?php esc_html_e( 'You must create all the events first. If an event is missing to generate an occurrence of the group, that occurrence will be skipped.', 'booking-activities' ); ?></span>
			</div>
			<div class='bookacti-backend-settings-only-notice bookacti-info'>
				<span class='dashicons dashicons-info'></span>
				<span><?php esc_html_e( 'Each occurrence of the group must have strictly the same events: same activity, same time, same duration, same spacing between events.', 'booking-activities' ); ?></span>
			</div>
		<?php
		}
		?>
	</form>
</div>


<!-- Group category -->
<div id='bookacti-group-category-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Group category parameters', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-group-category-form' >
		<input type='hidden' name='template_id' id='bookacti-group-category-template-id'/>
		<input type='hidden' name='category_id' id='bookacti-group-category-category-id'/>
		<input type='hidden' name='action' id='bookacti-group-category-action' />
		<?php wp_nonce_field( 'bookacti_insert_or_update_group_category', 'nonce_insert_or_update_group_category' ); ?>
		
		<div id='bookacti-group-category-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
			// Fill the array of tabs with their label, callback for content and display order
			$group_category_tabs = apply_filters( 'bookacti_group_category_dialog_tabs', array (
				array(	'label'      => esc_html__( 'General', 'booking-activities' ),
						'callback'   => 'bookacti_fill_group_category_tab_general',
						'parameters' => array(),
						'order'      => 10 ),
				array(	'label'      => esc_html__( 'Availability', 'booking-activities' ),
						'callback'   => 'bookacti_fill_group_category_tab_availability',
						'parameters' => array(),
						'order'      => 20 ),
				array(	'label'      => esc_html__( 'Permissions', 'booking-activities' ),
						'callback'   => 'bookacti_fill_group_category_tab_permissions',
						'parameters' => array(),
						'order'      => 100 )
			) );
			
			// Display tabs
			bookacti_display_tabs( $group_category_tabs, 'group-category' );
			
			/**
			 * Fill "General" tab of "Group category" dialog
			 * @version 1.14.0
			 * @param array $params
			 */
			function bookacti_fill_group_category_tab_general( $params = array() ) {
				do_action( 'bookacti_group_category_tab_general_before', $params );
				?>
				<div>
					<label for='bookacti-group-category-title-field' ><?php esc_html_e( 'Category title', 'booking-activities' ); ?></label>
					<input type='text' name='title' id='bookacti-group-category-title-field' class='bookacti-translatable'/>
					<?php
						$tip = esc_html__( 'Name the group of events category.', 'booking-activities' );
						bookacti_help_tip( $tip );
					?>
				</div>
				<?php
				do_action( 'bookacti_group_category_tab_general_after', $params );
			}
			
			/**
			 * Display the fields in the "Availability" tab of the Group Category dialog
			 * @since 1.4.0
			 * @version 1.12.0
			 * @param array $params
			 */
			function bookacti_fill_group_category_tab_availability( $params = array() ) {
				do_action( 'bookacti_group_category_tab_availability_before', $params );
				
				$fields = array(
					'min_bookings_per_user' => array(
						'type'    => 'number',
						'name'    => 'min_bookings_per_user',
						'id'      => 'bookacti-group-category-min-bookings-per-user',
						'title'   => esc_html__( 'Min bookings per user', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'The minimum booking quantity a user has to make on a group of events of this category. E.g.: "3", the customer must book at least 3 places of the desired group of events.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'max_bookings_per_user' => array(
						'type'    => 'number',
						'name'    => 'max_bookings_per_user',
						'id'      => 'bookacti-group-category-max-bookings-per-user',
						'title'   => esc_html__( 'Max bookings per user', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'The maximum booking quantity a user can make on a group of events of this category. E.g.: "1", the customer can only book one place of the desired group of events, and he / she won\'t be allowed to book it twice.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'max_users_per_event' => array(
						'type'    => 'number',
						'name'    => 'max_users_per_event',
						'id'      => 'bookacti-group-category-max-users-per-event',
						'title'   => esc_html__( 'Max users per event', 'booking-activities' ),
						'options' => array( 'min' => 0, 'step' => 1 ),
						'tip'     => esc_html__( 'Set how many different users can book the same group of events. E.g.: "1", only one user can book a specific group of events; once he / she has booked it, the group of events won\'t be available for anyone else anymore, even if it isn\'t full. Useful for private events.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "0" to ignore this parameter.', 'booking-activities' )
					),
					'booking_changes_deadline' => array(
						'type'  => 'duration',
						'name'  => 'booking_changes_deadline',
						'id'    => 'bookacti-group-category-booking-changes-deadline',
								/* translators: Followed by a field indicating a number of days, hours and minutes from now. E.g.: "Changes are allowed for bookings starting in at least 2 days, 12 hours, 25 minutes". */
						'title' => esc_html__( 'Changes are allowed for bookings starting in at least', 'booking-activities' ),
						'label' => bookacti_help_tip( esc_html__( 'Define when a customer can change a booking (cancel, reschedule). E.g.: "2 days 5 hours 30 minutes", your customers will be able to change the bookings starting in 2 days, 5 hours and 30 minutes at least. They won\'t be allowed to cancel a booking starting tomorrow for example.', 'booking-activities' )
						        . '<br/>' . esc_html__( 'This parameter applies to the groups of events of this category only. A global parameter is available in global settings.', 'booking-activities' )
						        . ' ' . esc_html__( 'Leave it empty to use the global value.', 'booking-activities' ), false )
						        . '<br/><small><em>' . sprintf( esc_html__( 'Bookings can be changed from the booking list only (%s)', 'booking-activities' ), '<a href="https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-customers-bookings-list-on-the-frontend/" target="_blank"><code style="font-size: inherit;">[bookingactivities_list]</code></a>' ) . '</em></small>'
					),
					'started_groups_bookable' => array(
						'type'    => 'select',
						'name'    => 'started_groups_bookable',
						'id'      => 'bookacti-group-category-started-groups-bookable',
						'title'   => esc_html__( 'Are started groups bookable?', 'booking-activities' ),
						'options' => array( 
							'-1' => esc_html__( 'Site setting', 'booking-activities' ),
							'0'  => esc_html__( 'No', 'booking-activities' ),
							'1'  => esc_html__( 'Yes', 'booking-activities' )
						),
						'tip'     => esc_html__( 'Allow or disallow users to book a group of events that has already begun.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'This parameter applies to the groups of events of this category only. A global parameter is available in global settings.', 'booking-activities' )
						          . '<br/>' . esc_html__( 'Set it to "Site setting" to use the global value.', 'booking-activities' )
					)
				);
				
				bookacti_display_fields( $fields );
				
				do_action( 'bookacti_group_category_tab_availability_after', $params );
			}
			
			
			/**
			 * Display the fields in the "Permissions" tab of the Group Category dialog
			 * @version 1.13.0
			 * @param array $params
			 */
			function bookacti_fill_group_category_tab_permissions( $params = array() ) {
				do_action( 'bookacti_group_category_tab_permissions_before', $params );
				
				// Allowed roles field
				$roles = get_editable_roles();
				$roles_options = array();
				foreach( $roles as $role_id => $role ) { $roles_options[ $role_id ] = $role[ 'name' ]; }
				?>
				<div class='bookacti-field-container' id='bookacti-group-category-roles-container'>
					<input type='checkbox' name='is_restricted' id='bookacti-display-group-category-user-roles'/>
					<label for='bookacti-display-group-category-user-roles' class='bookacti-fullwidth-label'>
						<strong><?php esc_html_e( 'I want to restrict this group category to certain users only', 'booking-activities' ); ?></strong>
					</label>
					<?php
					$tip = esc_html__( 'Choose who is allowed to book the groups of this category.', 'booking-activities' )
						 . '<br/>' . esc_html__( 'Use CTRL+Click to pick or unpick a role.', 'booking-activities' ) 
						 . ' ' . esc_html__( 'Don\'t pick any role to allow everybody.', 'booking-activities' );
					bookacti_help_tip( $tip );

					$allowed_roles = array( 
						'type'      => 'select',
						'multiple'  => 1,
						'name'      => 'allowed_roles',
						'id'        => 'bookacti-group-category-roles',
						'fullwidth' => 1,
						'options'   => array_merge( $roles_options, array( 'all' => esc_html__( 'Everybody', 'booking-activities' ) ) )
					);
					bookacti_display_field( $allowed_roles );
				?>		
					<div class='bookacti-roles-notice bookacti-warning' style='margin-bottom:0;'>
						<span class='dashicons dashicons-info'></span>
						<span><?php esc_html_e( 'Don\'t pick any role to allow everybody.', 'booking-activities' ); ?></span>
					</div>
					<div class='bookacti-roles-notice bookacti-info'>
						<span class='dashicons dashicons-info'></span>
						<span><?php esc_html_e( 'Use CTRL+Click to pick or unpick a role.', 'booking-activities' ); ?></span>
					</div>
				</div>
				<?php
				do_action( 'bookacti_group_category_tab_permissions_after', $params );
			}
		?>
	</form>
</div>


<!-- Delete template -->
<div id='bookacti-delete-template-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Delete calendar', 'booking-activities' ); ?>' style='display:none;' >
	<div><?php esc_html_e( 'Are you sure to delete this calendar?', 'booking-activities' ); ?></div>
	<div class='bookacti-error'>
		<span class='dashicons dashicons-warning'></span>
		<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
	</div>
</div>


<!-- Delete activity -->
<div id='bookacti-delete-activity-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Delete activity', 'booking-activities' ); ?>' style='display:none;' >
	<?php wp_nonce_field( 'bookacti_deactivate_activity', 'nonce_deactivate_activity', false ); ?>
	<div>
		<?php esc_html_e( 'Are you sure to delete this activity permanently?', 'booking-activities' ); ?><br/>
		<em><?php esc_html_e( 'You won\'t be able to place new events from this activity anymore.', 'booking-activities' ); ?></em>
	</div>
	<div class='bookacti-error'>
		<span class='dashicons dashicons-warning'></span>
		<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
	</div>
	<hr/>
	
	<div id='bookacti-delete-activity-options'>
		<p><?php esc_html_e( 'You can also delete the events of that activity on that calendar.', 'booking-activities' ); ?></p>
		<?php 
			$fields = array(
				'delete_activity_events' => array(
					'type'  => 'checkbox',
					'name'  => 'delete_activity_events',
					'id'    => 'bookacti-delete-activity-events',
					'title' => esc_html__( 'Delete the events', 'booking-activities' ),
					'value' => 0,
					'tip'   => esc_html__( 'The events of this activity will be permanently deleted from that calendar.', 'booking-activities' )
				)
			);
			bookacti_display_fields( $fields );
		?>
	</div>
	<div class='bookacti-error'>
		<span class='dashicons dashicons-warning'></span>
		<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
	</div>
</div>


<!-- Unbind an occurrence of a repeated event -->
<div id='bookacti-unbind-event-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Make an occurrence of a repeated event independent', 'booking-activities' ); ?>' style='display:none;'>
	<form id='bookacti-unbind-event-form'>
		<?php wp_nonce_field( 'bookacti_unbind_event_occurrences', 'nonce_unbind_event_occurrences', false ); ?>
		<input type='hidden' name='action' id='bookacti-unbind-event-form-action' value='bookactiUnbindEventOccurrences'/>
		<input type='hidden' name='unbind_action' value='selected'/>
		
		<p><?php esc_html_e( 'In order to edit the occurrences of the event independently, you can:', 'booking-activities' ); ?></p>
				
		<div id='bookacti-unbind-event-actions'>
			<div id='bookacti-unbind-event-action-selected-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='selected' id='bookacti-unbind-event-action-selected' checked='checked'/>
				<label for='bookacti-unbind-event-action-selected'><?php esc_html_e( 'Unbind the selected occurrence', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate events: the selected occurrence, and the original event without the selected occurrence.', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-event-action-future-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='future' id='bookacti-unbind-event-action-future'/>
				<label for='bookacti-unbind-event-action-future'><?php esc_html_e( 'Unbind the next occurrences (including the selected one)', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate events: one with the occurrences prior to the selected one, the other with the occurrences subsequent to the selected one (included).', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-event-action-booked-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='booked' id='bookacti-unbind-event-action-booked'/>
				<label for='bookacti-unbind-event-action-booked'><?php esc_html_e( 'Unbind the booked occurrences', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate events: one with the booked occurrences, the other with the occurrences that don\'t have active bookings.', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-event-action-all-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='all' id='bookacti-unbind-event-action-all'/>
				<label for='bookacti-unbind-event-action-all'><?php esc_html_e( 'Unbind each occurrence', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in one separate event per occurrence.', 'booking-activities' ); ?></em></small>
			</div>
			<?php do_action( 'bookacti_unbind_event_actions_after' ); ?>
		</div>
		
		<div class='bookacti-selected-event-dates'>
			<label class='bookacti-fullwidth-label'><strong><?php esc_html_e( 'The currently selected occurrence is:', 'booking-activities' ); ?></strong></label>
			<span class='bookacti-selected-event-start'></span> - <span class='bookacti-selected-event-end'></span>
		</div>
		
		<div class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
		</div>
	</form>
</div>


<!-- Unbind an occurrence of a repeated group of events -->
<div id='bookacti-unbind-group-of-events-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Make an occurrence of a repeated group of events independent', 'booking-activities' ); ?>' style='display:none;'>
	<form id='bookacti-unbind-group-of-events-form'>
		<?php wp_nonce_field( 'bookacti_unbind_group_of_events_occurrences', 'nonce_unbind_group_of_events_occurrences', false ); ?>
		<input type='hidden' name='action' id='bookacti-unbind-group-of-events-form-action' value='bookactiUnbindGroupOfEventsOccurrences'/>
		<input type='hidden' name='unbind_action' value='selected'/>
		
		<p><?php esc_html_e( 'In order to edit the occurrences of the group of events independently, you can:', 'booking-activities' ); ?></p>
				
		<div id='bookacti-unbind-group-of-events-actions'>
			<div id='bookacti-unbind-group-of-events-action-selected-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='selected' id='bookacti-unbind-group-of-events-action-selected' checked='checked'/>
				<label for='bookacti-unbind-group-of-events-action-selected'><?php esc_html_e( 'Unbind the selected occurrence', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate groups of events: the selected occurrence, and the original group of events without the selected occurrence.', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-group-of-events-action-future-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='future' id='bookacti-unbind-group-of-events-action-future'/>
				<label for='bookacti-unbind-group-of-events-action-future'><?php esc_html_e( 'Unbind the next occurrences (including the selected one)', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate groups of events: one with the occurrences prior to the selected one, the other with the occurrences subsequent to the selected one (included).', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-group-of-events-action-booked-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='booked' id='bookacti-unbind-group-of-events-action-booked'/>
				<label for='bookacti-unbind-group-of-events-action-booked'><?php esc_html_e( 'Unbind the booked occurrences', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in two separate groups of events: one with the booked occurrences, the other with the occurrences that don\'t have active bookings.', 'booking-activities' ); ?></em></small>
			</div>
			<div id='bookacti-unbind-group-of-events-action-all-container' class='bookacti-unbind-action'>
				<input type='radio' name='unbind_action' value='all' id='bookacti-unbind-group-of-events-action-all'/>
				<label for='bookacti-unbind-group-of-events-action-all'><?php esc_html_e( 'Unbind each occurrence', 'booking-activities' ); ?></label>
				<br/><small><em><?php esc_html_e( 'This will result in one separate group of events per occurrence.', 'booking-activities' ); ?></em></small>
			</div>
			<?php do_action( 'bookacti_unbind_group_of_events_actions_after' ); ?>
		</div>
		
		<div class='bookacti-selected-group-of-events-container'>
			<label for='bookacti-unbind-selected-group-of-events' class='bookacti-fullwidth-label'><strong><?php esc_html_e( 'The currently selected occurrence is:', 'booking-activities' ); ?></strong></label>
			<select multiple id='bookacti-unbind-selected-group-of-events' class='bookacti-custom-scrollbar bookacti-selected-events-list'></select>
		</div>
		
		<div class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
		</div>
	</form>
</div>


<!-- Dialog to move / resize an event -->
<div id='bookacti-update-event-dates-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Change the event\'s dates', 'booking-activities' ); ?>' style='display:none;'>
	<form id='bookacti-update-event-dates-form'>
		<div class='bookacti-info'>
			<span class='dashicons dashicons-info'></span>
			<span>
				<?php esc_html_e( 'You can also drag and drop the event on the calendar to move it, and drag the handle at the bottom of the event to resize it.', 'booking-activities' ); ?>
			</span>
		</div>
		<p class='bookacti-selected-event-dates'>
			<strong><?php esc_html_e( 'Selected event:', 'booking-activities' ); ?></strong>
			<span class='bookacti-selected-event-start'></span> - <span class='bookacti-selected-event-end'></span>
		</p>
		<div class='bookacti-field-container' id='bookacti-update-event-dates-start-container'>
			<label for='bookacti-update-event-dates-start_date'>
				<?php esc_html_e( 'Start', 'booking-activities' ); ?>
			</label>
			<?php 
				$start_date = array( 'type' => 'date', 'name' => 'start_date', 'id' => 'bookacti-update-event-dates-start_date' );
				bookacti_display_field( $start_date );
				$start_time = array( 'type' => 'time', 'name' => 'start_time', 'id' => 'bookacti-update-event-dates-start_time' );
				bookacti_display_field( $start_time );
				bookacti_help_tip( esc_html__( 'Set the new event start date and time. The event will be moved accordingly.', 'booking-activities' ) );
			?>
		</div>
		<div class='bookacti-field-container' id='bookacti-update-event-dates-end-container'>
			<label for='bookacti-update-event-dates-end_date'>
				<?php esc_html_e( 'End', 'booking-activities' ); ?>
			</label>
			<?php 
				$end_date = array( 'type' => 'date', 'name' => 'end_date', 'id' => 'bookacti-update-event-dates-end_date' );
				bookacti_display_field( $end_date );
				$end_time = array( 'type' => 'time', 'name' => 'end_time', 'id' => 'bookacti-update-event-dates-end_time' );
				bookacti_display_field( $end_time );
				bookacti_help_tip( esc_html__( 'Set the new event end date and time. The event will be resized accordingly.', 'booking-activities' ) );
			?>
		</div>
		<?php do_action( 'bookacti_update_event_dates_options_after' ); ?>
		<div class='bookacti-error bookacti-update-repeated-event-dates-warning'>
			<span class='dashicons dashicons-warning'></span>
			<span>
				<?php esc_html_e( 'This is a repeated event, all the occurrences will be affected. If you want to apply these changes to only one occurrence, you need to unbind it first.', 'booking-activities' ); ?>
			</span>
		</div>
	</form>
</div>


<!-- Warning before moving / resizing a booked event -->
<div id='bookacti-update-booked-event-dates-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Move a booked event', 'booking-activities' ); ?>' style='display:none;'>
	<form id='bookacti-update-booked-event-dates-form'>
		<p id='bookacti-update-booked-event-dates-intro'><?php esc_html_e( 'You are about to change the dates of a booked event. The event\'s bookings will be rescheduled to match the new dates.', 'booking-activities' ); ?></p>
		<?php 
			$fields = array(
				'send_notifications' => array(
					'type'  => 'checkbox',
					'name'  => 'send_notifications',
					'id'    => 'bookacti-update-booked-event-dates-send_notifications',
					'title' => esc_html__( 'Send notifications', 'booking-activities' ),
					'value' => 0,
					/* translators: %1$s = title of the notification (E.g.: "Booking is rescheduled") */
					'tip'   => sprintf( esc_html__( 'Send the "%1$s" notification to your customer. No notification will be sent for past bookings and to administrators.', 'booking-activities' ), esc_html__( 'Booking is rescheduled', 'booking-activities' ) )
				)
			);
			bookacti_display_fields( $fields );
			
			bookacti_display_banp_promo_admin_message();
			
			do_action( 'bookacti_update_booked_event_dates_options_after' );
		?>
		<div class='bookacti-error bookacti-update-booked-repeated-event-dates-warning'>
			<span class='dashicons dashicons-warning'></span>
			<span>
				<?php esc_html_e( 'This is a repeated event, all the occurrences will be affected. If you want to apply these changes to only one occurrence, you need to unbind it first.', 'booking-activities' ); ?>
			</span>
		</div>
	</form>
</div>


<!-- Delete event -->
<div id='bookacti-delete-event-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Delete event', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-delete-event-form'>
		<?php wp_nonce_field( 'bookacti_delete_event', 'nonce_delete_event', false ); ?>
		<input type='hidden' name='action' id='bookacti-delete-event-form-action' value='bookactiDeleteEvent'/>
		
		<div><?php esc_html_e( 'Are you sure to delete this event permanently?', 'booking-activities' ); ?></div>
		<div class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
		</div>
		<div class='bookacti-error bookacti-delete-booked-repeated-event-warning'>
			<span class='dashicons dashicons-warning'></span>
			<span>
				<?php esc_html_e( 'This is a repeated event, all the occurrences will be affected. If you want to apply these changes to only one occurrence, you need to unbind it first.', 'booking-activities' ); ?>
			</span>
		</div>
		
		<hr/>
		
		<div id='bookacti-delete-booked-event-options'>
			<p><?php esc_html_e( 'You can also cancel the bookings made for this event.', 'booking-activities' ); ?></p>
			<?php 
				$send_notifications_tip = sprintf( esc_html__( 'Send the "%1$s" notification to your customer. No notification will be sent for past bookings and to administrators.', 'booking-activities' ), sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Cancelled', 'booking-activities' ) ) );
				$fields = array(
					'cancel_bookings' => array(
						'type'  => 'checkbox',
						'name'  => 'cancel_bookings',
						'id'    => 'bookacti-delete-event-cancel_bookings',
						'title' => esc_html__( 'Cancel the bookings', 'booking-activities' ),
						'value' => 0,
						'tip'   => esc_html__( 'The bookings of this event will be cancelled. For repeated events, only bookings for events that haven\'t yet started will be cancelled, bookings for past events won\'t be changed.', 'booking-activities' )
					),
					'send_notifications' => array(
						'type'  => 'checkbox',
						'name'  => 'send_notifications',
						'id'    => 'bookacti-delete-event-send_notifications',
						'title' => esc_html__( 'Send notifications', 'booking-activities' ),
						'value' => 0,
						'tip'   => $send_notifications_tip
					)
				);
				bookacti_display_fields( $fields );
				
				bookacti_display_banp_promo_admin_message();
				
				do_action( 'bookacti_delete_booked_event_options_after' );
			?>
		</div>
	</form>
</div>


<!-- Delete group of events -->
<div id='bookacti-delete-group-of-events-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Delete a group of events', 'booking-activities' ); ?>' style='display:none;' >
	<form id='bookacti-delete-group-of-events-form'>
		<?php wp_nonce_field( 'bookacti_delete_group_of_events', 'nonce_delete_group_of_events', false ); ?>
		<input type='hidden' name='action' id='bookacti-delete-group-of-events-form-action' value='bookactiDeleteGroupOfEvents'/>
		
		<div><p><?php esc_html_e( 'Are you sure to delete this group of events permanently?', 'booking-activities' ); ?></p></div>
		<div class='bookacti-info'>
			<span class='dashicons dashicons-info'></span>
			<span><?php esc_html_e( 'Events will NOT be deleted.', 'booking-activities' ); ?></span>
		</div>
		<div class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
		</div>
		
		<hr/>
		
		<div id='bookacti-delete-booked-group-of-events-options'>
			<p><?php esc_html_e( 'You can also cancel the bookings made for this group of events.', 'booking-activities' ); ?></p>
			<?php 
				$fields = array(
					'cancel_bookings' => array(
						'type'  => 'checkbox',
						'name'  => 'cancel_bookings',
						'id'    => 'bookacti-delete-group-of-events-cancel_bookings',
						'title' => esc_html__( 'Cancel the bookings', 'booking-activities' ),
						'value' => 0,
						'tip'   => esc_html__( 'The bookings of this group of events will be cancelled. Only bookings for events that haven\'t yet started will be cancelled, bookings for past events won\'t be changed.', 'booking-activities' )
					),
					'send_notifications' => array(
						'type'  => 'checkbox',
						'name'  => 'send_notifications',
						'id'    => 'bookacti-delete-group-of-events-send_notifications',
						'title' => esc_html__( 'Send notifications', 'booking-activities' ),
						'value' => 0,
						'tip'   => $send_notifications_tip
					)
				);
				bookacti_display_fields( $fields );
				
				bookacti_display_banp_promo_admin_message();
				
				do_action( 'bookacti_delete_booked_group_of_events_options_after' );
			?>
		</div>
	</form>
</div>


<!-- Delete group category -->
<div id='bookacti-delete-group-category-dialog' class='bookacti-backend-dialog bookacti-template-dialog' title='<?php esc_html_e( 'Delete a group category', 'booking-activities' ); ?>' style='display:none;' >
	<?php wp_nonce_field( 'bookacti_delete_group_category', 'nonce_delete_group_category', false ); ?>
	<div><?php esc_html_e( 'Are you sure to delete this category and all its groups of events permanently?', 'booking-activities' ); ?></div>
	<div class='bookacti-info'>
		<span class='dashicons dashicons-info'></span>
		<span><?php esc_html_e( 'Events will NOT be deleted.', 'booking-activities' ); ?></span>
	</div>
	<div class='bookacti-error'>
		<span class='dashicons dashicons-warning'></span>
		<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
	</div>
</div>


<?php
do_action( 'bookacti_calendar_editor_dialogs', $templates );