<?php 
/**
 * Calendar editor dialogs
 * @version 1.7.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// INIT VARIABLES
	// Templates options list
	$templates = bookacti_fetch_templates();
	$templates_options = '';
	foreach( $templates as $template ) {
		$templates_options .= '<option value="' . esc_attr( $template[ 'id' ] ) . '" >' . esc_html( $template[ 'title' ] ) . '</option>';
	}

	// Users options list
	$in_roles		= apply_filters( 'bookacti_managers_roles', array() );
	$not_in_roles	= apply_filters( 'bookacti_managers_roles_exceptions', is_multisite() ? array() : array( 'administrator' ) );
	$user_query		= new WP_User_Query( array( 'role__in' => $in_roles, 'role__not_in' => $not_in_roles ) );
	$users			= $user_query->get_results();
	$users_options_for_activities	= '';
	$users_options_for_templates	= '';
	if ( ! empty( $users ) ) {
		foreach( $users as $user ) {
			if( $user->has_cap( 'bookacti_edit_activities' ) || $user->has_cap( 'bookacti_edit_templates' ) || $user->has_cap( 'bookacti_read_templates' ) ) {
				$user_info = get_userdata( $user->ID );
				$display_name = $user_info->user_login;
				if( $user_info->first_name && $user_info->last_name ){
					$display_name = $user_info->first_name  . ' ' . $user_info->last_name . ' (' . $user_info->user_login . ')';
				}
				$display_name = apply_filters( 'bookacti_managers_name_display', $display_name, $user_info );
				
				if( $user->has_cap( 'bookacti_edit_activities' ) ) {
					$users_options_for_activities .= '<option value="' . esc_attr( $user->ID ) . '" >' . esc_html( $display_name ) . '</option>';
				}
				
				if( $user->has_cap( 'bookacti_edit_templates' ) || $user->has_cap( 'bookacti_read_templates' ) ) {
					$users_options_for_templates .= '<option value="' . esc_attr( $user->ID ) . '" >' . esc_html( $display_name ) . '</option>';
				}
			}
		}
	}
?>

<!-- Delete event -->
<div id='bookacti-delete-event-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div><?php esc_html_e( 'Are you sure to delete this event permanently?', BOOKACTI_PLUGIN_NAME ); ?></div>
</div>

<!-- Delete booked event -->
<div id='bookacti-delete-booked-event-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div><?php esc_html_e( 'This event is booked. Do you still want to delete it?', BOOKACTI_PLUGIN_NAME ); ?></div>
</div>

<!-- Delete template -->
<div id='bookacti-delete-template-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div><?php esc_html_e( 'Are you sure to delete this calendar?', BOOKACTI_PLUGIN_NAME ); ?></div>
</div>

<!-- Delete activity -->
<div id='bookacti-delete-activity-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
	<div>
		<?php esc_html_e( 'Are you sure to delete this activity permanently?', BOOKACTI_PLUGIN_NAME ); ?><br/>
		<em><?php esc_html_e( 'You won\'t be able to place new events from this activity anymore.', BOOKACTI_PLUGIN_NAME ); ?></em>
	</div>
	<div id='bookacti-delete-activity-options'>
		<input type='checkbox' id='bookacti-delete-activity-events' name='bookacti_delete_activity_events' />
		<label for='bookacti-delete-activity-events' ><?php _e( 'Also delete permanently all events from this activity.', BOOKACTI_PLUGIN_NAME ); ?></label>
	</div>
</div>

<!-- Delete group of events -->
<div id='bookacti-delete-group-of-events-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div><p><?php esc_html_e( 'Are you sure to delete this group of events permanently?', BOOKACTI_PLUGIN_NAME ); ?></p></div>
    <div><p><em><?php esc_html_e( 'Events will NOT be deleted.', BOOKACTI_PLUGIN_NAME ); ?></em></p></div>
</div>

<!-- Delete group category -->
<div id='bookacti-delete-group-category-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div><?php esc_html_e( 'Are you sure to delete this category and all its groups of events permanently?', BOOKACTI_PLUGIN_NAME ); ?></div>
	<div><p><em><?php esc_html_e( 'Events will NOT be deleted.', BOOKACTI_PLUGIN_NAME ); ?></em></p></div>
</div>

<!-- Edit event dialog -->
<div id='bookacti-event-data-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' data-event-id='0' style='display:none;' >
	<form id='bookacti-event-data-form' >
		<?php wp_nonce_field( 'bookacti_update_event_data', 'nonce_update_event_data' ); ?>
		<input type='hidden' name='event-id'	id='bookacti-event-data-form-event-id'		value='' />
		<input type='hidden' name='event-start'	id='bookacti-event-data-form-event-start'	value='' />
		<input type='hidden' name='event-end'	id='bookacti-event-data-form-event-end'		value='' />
		<input type='hidden' name='action'		id='bookacti-event-data-form-action'		value='' />
		
		<div id='bookacti-event-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		//Fill the array of tabs with their label, callback for content and display order
		$event_tabs = apply_filters( 'bookacti_event_dialog_tabs', array (
			array(	'label'			=> __( 'General', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'general',
					'callback'		=> 'bookacti_fill_event_tab_general',
					'parameters'	=> array(),
					'order'			=> 10 ),
			array(	'label'			=> __( 'Repetition', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'repetition',
					'callback'		=> 'bookacti_fill_event_tab_repetition',
					'parameters'	=> array(),
					'order'			=> 20 )
		) );

		// Display tabs
		bookacti_display_tabs( $event_tabs, 'event' );

		
		/**
		 * Fill the "event" tab of the Event settings dialog
		 * @version 1.7.0
		 * @param array $params
		 */
		function bookacti_fill_event_tab_general( $params ) {
			do_action( 'bookacti_event_tab_general_before', $params );
		?>
			<div>
				<?php
					$tip = esc_html__( 'Give your event a specific title.', BOOKACTI_PLUGIN_NAME ) . ' ' . esc_html__( 'It will override the activity setting on this event only.', BOOKACTI_PLUGIN_NAME );
					$args = array(
						'type'			=> 'textarea',
						'name'			=> 'event-title',
						'id'			=> 'bookacti-event-title',
						'placeholder'	=> $tip,
						'value'			=> ''
					);
				?>
				<div>
					<label for='<?php echo $args[ 'id' ]; ?>' class='bookacti-fullwidth-label' >
					<?php 
						esc_html_e( 'Title', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
					</label>
					<?php bookacti_display_field( $args ); ?>
				</div>
			</div>
			<div>
				<label for='bookacti-event-availability' ><?php esc_html_e( 'Availability', BOOKACTI_PLUGIN_NAME ); ?></label>
				<input type='number' min='0' value='0' id='bookacti-event-availability' 
					   data-verified='false'
					   onkeypress='return event.charCode >= 48 && event.charCode <= 57' name='event-availability' />
				<?php
					$tip = __( 'Set the amount of bookings that can be made on this event.', BOOKACTI_PLUGIN_NAME );
					$tip .= ' ' . __( 'It will override the activity setting on this event only.', BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
				?>
			</div>
		<?php
			
			do_action( 'bookacti_event_tab_general_after', $params );
			
			bookacti_promo_for_bapap_addon( 'event' );
		}

		function bookacti_fill_event_tab_repetition( $params ) {
			do_action( 'bookacti_event_tab_repetition_before', $params );
		?>
			<div id='bookacti-event-repeat-freq-container'>
				<label for='bookacti-event-repeat-freq' ><?php esc_html_e( 'Repetition Frequency', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select name='event-repeat-freq' id='bookacti-event-repeat-freq' data-initial-freq='none' >
					<option value='none' selected='selected'>   <?php esc_html_e( 'Do not repeat', BOOKACTI_PLUGIN_NAME ); ?>  </option>
					<option value='daily'>                      <?php esc_html_e( 'Daily', BOOKACTI_PLUGIN_NAME ); ?>          </option>
					<option value='weekly'>                     <?php esc_html_e( 'Weekly', BOOKACTI_PLUGIN_NAME ); ?>         </option>
					<option value='monthly'>                    <?php esc_html_e( 'Monthly', BOOKACTI_PLUGIN_NAME ); ?>        </option>
				</select>
				<?php
					$tip = __( 'Set the repetition frequency. This will create an occurence of the event every day, week or month.', BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
				?>
			</div>
			<div id='bookacti-event-repeat-period-container'>
				<div>
					<label for='bookacti-event-repeat-from' ><?php esc_html_e( 'Repeat from', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='date' name='event-repeat-from' id='bookacti-event-repeat-from' data-verified='false' max='2037-12-31' />
					<?php
						$tip = __( 'Set the starting date of the repetition. The occurences of the event will be added from this date.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-event-repeat-to' ><?php esc_html_e( 'Repeat to', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='date' name='event-repeat-to' id='bookacti-event-repeat-to' data-verified='false' max='2037-12-31' />
					<?php
						$tip = __( 'Set the ending date of the repetition. The occurences of the event will be added until this date.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			</div>
			<div id='bookacti-event-exceptions-container'>
				<label class='bookacti-fullwidth-label'>
				<?php 
					esc_html_e( 'Exceptions', BOOKACTI_PLUGIN_NAME );
					$tip = __( 'You can add exception dates to the repetition. No event occurences will be displayed on the exception dates.', BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
				?>
				</label>
				<div id='bookacti-event-add-exception-container' >
					<input type='date' id='bookacti-event-exception-date-picker' max='2037-12-31' >
					<button type='button' id='bookacti-event-add-exception-button' ><?php esc_html_e( 'Add', BOOKACTI_PLUGIN_NAME ); ?></button>
				</div>
				<div>
					<select multiple id='bookacti-event-exceptions-selectbox' name='event-repeat-excep[]' ></select>
					<button type='button' id='bookacti-event-delete-exceptions-button' ><?php esc_html_e( 'Delete selected', BOOKACTI_PLUGIN_NAME ); ?></button>
				</div>
			</div>
		<?php 
			do_action( 'bookacti_event_tab_repetition_after', $params );
		} 
		?>
	</form>
</div>


<!-- Template params -->
<div id='bookacti-template-data-dialog' class='bookacti-backend-dialog bookacti-template-dialogs tabs' style='display:none;' >
	<form id='bookacti-template-data-form' >
		<?php wp_nonce_field( 'bookacti_insert_or_update_template', 'nonce_insert_or_update_template' ); ?>
		<input type='hidden' name='template-id'	id='bookacti-template-data-form-template-id'	value='' />
		<input type='hidden' name='action'		id='bookacti-template-data-form-action'			value='' />
		<div id='bookacti-template-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php 
			//Fill the array of tabs with their label, callback for content and display order
			$template_tabs = apply_filters( 'bookacti_template_dialog_tabs', array (
				array(	'label'			=> __( 'General', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_template_tab_general',
						'parameters'	=> array( 'template_options' => $templates_options ),
						'order'			=> 10 ),
				array(	'label'			=> __( 'Agenda', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_template_tab_agenda',
						'parameters'	=> array(),
						'order'			=> 40 ),
				array(	'label'			=> __( 'Events', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_template_tab_events',
						'parameters'	=> array(),
						'order'			=> 50 ),
				array(	'label'			=> __( 'Permissions', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_template_tab_permissions',
						'parameters'	=> array( 'users_options_for_templates' => $users_options_for_templates ),
						'order'			=> 100 )
			) );
			
			// Display tabs
			bookacti_display_tabs( $template_tabs, 'template' );

			//Tab content for template general tab
			function bookacti_fill_template_tab_general( $params = array() ) {
				$templates_options = $params[ 'template_options' ];
				do_action( 'bookacti_template_tab_general_before', $params );
			?>
				<div>
					<label for='bookacti-template-title' ><?php esc_html_e( 'Title', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text' name='template-title' id='bookacti-template-title' />
					<?php
						$tip = __( 'Give your calendar a title to easily recognize it in a list.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div id='bookacti-duplicate-template-fields'>
					<label for='bookacti-template-duplicated-template-id' ><?php esc_html_e( 'Duplicate from', BOOKACTI_PLUGIN_NAME ); ?></label>
					<select name='duplicated-template-id' id='bookacti-template-duplicated-template-id' class='bookacti-template-select-box' >
						<option value='0' ><?php esc_html_e( "Don't duplicate", BOOKACTI_PLUGIN_NAME ); ?></option>
						<?php echo $templates_options; ?>
					</select>
					<?php
						$tip = __( 'If you want to duplicate a calendar, select it in the list. It will copy its events, activities list, and its settings but not the bookings made on it.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-template-opening' ><?php esc_html_e( 'Opening', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='date' name='template-opening' id='bookacti-template-opening' max='2037-12-31' >
					<?php
						$tip = __( 'The starting date of your calendar. Basically it should be the date of your first event.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-template-closing' ><?php esc_html_e( 'Closing', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='date' name='template-closing' id='bookacti-template-closing' max='2037-12-31' >
					<?php
						$tip = __( 'The ending date of your calendar. Basically it should be the date of your last event.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			<?php 
					do_action( 'bookacti_template_tab_general_after', $params );
			} 
			
			
			function bookacti_fill_template_tab_agenda( $params = array() ) {
				do_action( 'bookacti_template_tab_agenda_before', $params );
			?>
				<div>
					<label for='bookacti-template-data-minTime' >
						<?php 
						/* translators: Refers to the first hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/minTime/ */
						_e( 'Day begin', BOOKACTI_PLUGIN_NAME );
						?>
					</label>
					<input type="time" name="templateOptions[minTime]" id='bookacti-template-data-minTime' value='08:00'>
					<?php
					$tip = __( "Set when you want the days to begin on the calendar. Ex: '06:00' Days will begin at 06:00am.", BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-template-data-maxTime' >
						<?php 
						/* translators: Refers to the last hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/maxTime/ */
						_e( 'Day end', BOOKACTI_PLUGIN_NAME );  
						?>
					</label>
					<input type="time" name="templateOptions[maxTime]" id='bookacti-template-data-maxTime' value='20:00' >
					<?php
					$tip = __( "Set when you want the days to end on the calendar. Ex: '18:00' Days will end at 06:00pm.", BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-template-data-snapDuration' >
						<?php 
						/* translators: Refers to the time interval at which a dragged event will snap to the agenda view time grid. Ex: 00:20', you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...). More information: http://fullcalendar.io/docs/agenda/snapDuration/ */
						_e( 'Snap frequency', BOOKACTI_PLUGIN_NAME );
						?>
					</label>
					<input type="time" name="templateOptions[snapDuration]" id='bookacti-template-data-snapDuration' value='00:05' min='00:01' >
					<?php
					$tip = __( "The time interval at which a dragged event will snap to the agenda view time grid. Ex: '00:20', you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...).", BOOKACTI_PLUGIN_NAME );
					bookacti_help_tip( $tip );
					?>
				</div>
			<?php
				do_action( 'bookacti_template_tab_agenda_after', $params );
				
				bookacti_display_badp_promo();
			}
			
			/** 
			 * Tab content for "Events" tab
			 * 
			 * @since 1.4.0
			 * @param array $params
			 */
			function bookacti_fill_template_tab_events( $params = array() ) {
				do_action( 'bookacti_template_tab_events_before', $params );
			?>
				<div>
					<label for='bookacti-template-availability-period-start' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */ esc_html_e( 'Events will be bookable in', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='templateOptions[availability_period_start]' 
							id='bookacti-template-availability-period-start' 
							min='-1' step='1' 
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						/* translators: Arrives after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
						_e( 'days from today', BOOKACTI_PLUGIN_NAME );
						$tip = __( 'Set the beginning of the availability period. E.g.: "2", your customers may book events starting in 2 days at the earliest. They are no longer allowed to book events starting earlier (like today or tomorrow).', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'This parameter applies to the events of this calendar only. A global parameter is available in global settings.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-template-availability-period-end' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Events are bookable for up to 30 days from today". */ esc_html_e( 'Events are bookable for up to', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='templateOptions[availability_period_end]' 
							id='bookacti-template-availability-period-end' 
							min='-1' step='1'
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						/* translators: Arrives after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
						_e( 'days from today', BOOKACTI_PLUGIN_NAME );
						$tip = __( 'Set the end of the availability period. E.g.: "30", your customers may book events starting within 30 days at the latest. They are not allowed yet to book events starting later.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'This parameter applies to the events of this calendar only. A global parameter is available in global settings.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			<?php
				do_action( 'bookacti_template_tab_events_after', $params );
				
				bookacti_display_badp_promo();
			}
			
			
			// Tab content for permission tab
			function bookacti_fill_template_tab_permissions( $params = array() ) {
				$users_options_for_templates = $params[ 'users_options_for_templates' ];
				do_action( 'bookacti_template_tab_permissions_before', $params );
			?>	
				<div id='bookacti-template-managers-container' class='bookacti-items-container' data-type='users' >
					<label id='bookacti-template-managers-title' class='bookacti-fullwidth-label' for='bookacti-add-new-template-managers-select-box' >
					<?php 
						esc_html_e( 'Who can manage this calendar?', BOOKACTI_PLUGIN_NAME );
						$tip  = __( 'Choose who is allowed to access this calendar.', BOOKACTI_PLUGIN_NAME );
						/* translators: %s = capabilities name */
						$tip .= ' ' . sprintf( __( 'All administrators already have this privilege. If the selectbox is empty, it means that no users have capabilities such as %s.', BOOKACTI_PLUGIN_NAME ), '"bookacti_edit_templates" / "bookacti_read_templates"' );
						/* translators: %1$s = Order for Customers add-on link. %2$s = Points of sale add-on link. %3$s = User role editor plugin name. */
						$tip .= '<br/>' 
							 .  sprintf( __( 'Operators from %1$s add-on and Point of sale managers from %2$s add-on have these capabilities. If you want to grant a user these capabilities, use a plugin such as %3$s.', BOOKACTI_PLUGIN_NAME ), 
									'<a href="https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=infobulle-permission" target="_blank" >Order for Customers</a>',
									'<a href="https://booking-activities.fr/en/downloads/points-of-sale/?utm_source=plugin&utm_medium=plugin&utm_campaign=points-of-sale&utm_content=infobulle-permission" target="_blank" >Points of Sale</a>',
									'<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>'
								);
						bookacti_help_tip( $tip );
					?>
					</label>
					<div id='bookacti-add-template-managers-container' class='bookacti-add-items-container' >
						<select id='bookacti-add-new-template-managers-select-box' class='bookacti-add-new-items-select-box' >
							<?php echo $users_options_for_templates; ?>
						</select>
						<button type='button' id='bookacti-add-template-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', BOOKACTI_PLUGIN_NAME ); ?></button>
					</div>
					<div id='bookacti-template-managers-list-container' class='bookacti-items-list-container' >
						<select name='template-managers[]' id='bookacti-template-managers-select-box' class='bookacti-items-select-box' multiple >
						</select>
						<button type='button' id='bookacti-remove-template-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', BOOKACTI_PLUGIN_NAME ); ?></button>
					</div>
				</div>
			<?php 
				do_action( 'bookacti_template_tab_permissions_after', $params );
			} ?>
	</form>
</div>


<!-- Activity param -->
<div id='bookacti-activity-data-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
	<form id='bookacti-activity-data-form' >
		<?php wp_nonce_field( 'bookacti_insert_or_update_activity', 'nonce_insert_or_update_activity' ); ?>
		<input type='hidden' name='activity-id' id='bookacti-activity-activity-id' />
		<input type='hidden' name='action'		id='bookacti-activity-action' />
		<input type='hidden' name='template-id' id='bookacti-activity-template-id' />
		
		<div id='bookacti-activity-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
			
			<?php
				// Fill the array of tabs with their label, callback for content and display order
				$activity_tabs = apply_filters( 'bookacti_activity_dialog_tabs', array (
					array(	'label'			=> __( 'General', BOOKACTI_PLUGIN_NAME ),
							'callback'		=> 'bookacti_fill_activity_tab_general',
							'parameters'	=> array(),
							'order'			=> 10 ),
					array(	'label'			=> __( 'Availability', BOOKACTI_PLUGIN_NAME ),
							'callback'		=> 'bookacti_fill_activity_tab_availability',
							'parameters'	=> array(),
							'order'			=> 20 ),
					array(	'label'			=> __( 'Terminology', BOOKACTI_PLUGIN_NAME ),
							'callback'		=> 'bookacti_fill_activity_tab_terminology',
							'parameters'	=> array(),
							'order'			=> 30 ),
					array(	'label'			=> __( 'Permissions', BOOKACTI_PLUGIN_NAME ),
							'callback'		=> 'bookacti_fill_activity_tab_permissions',
							'parameters'	=> array(	'users_options_for_activities' => $users_options_for_activities,
														'templates_options' => $templates_options ),
							'order'			=> 100 )
				) );
				
				// Display tabs
				bookacti_display_tabs( $activity_tabs, 'activity' );
			?>
			
			<?php
			function bookacti_fill_activity_tab_general( $params = array() ) {
				do_action( 'bookacti_activity_tab_general_before', $params );
			?>
				<div>
					<label for='bookacti-activity-title' ><?php esc_html_e( 'Title', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text'		name='activity-title'		id='bookacti-activity-title' class='bookacti-translate-input' />
					<input type='hidden'	name='activity-old-title'	id='bookacti-activity-old-title' />
					<?php
						$tip = __( 'Choose a short and relevant title for your activity. It will be shown on each events.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-availability' ><?php esc_html_e( 'Availability', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='number' 
						   name='activity-availability' 
						   id='bookacti-activity-availability' 
						   min='0' step='1' value='0' 
						   onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'The default amount of bookings that can be made on each event of this activity. This can be overriden on each event independantly.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<input type='hidden' name='activity-duration' id='bookacti-activity-duration' />
					<label><?php esc_html_e( 'Duration', BOOKACTI_PLUGIN_NAME ); ?></label>
					<div class='bookacti-display-inline-block' >
						<input type='number' 
							   name='activity-duration-days'
							   id='bookacti-activity-duration-days' 
							   min='0' max='365' step='1' value='000'
							   onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
						<?php /* translators: 'd' stand for days */ 
						echo esc_html( _x( 'd', 'd for days', BOOKACTI_PLUGIN_NAME ) ); ?>
						<input type='number' 
							   name='activity-duration-hours' 
							   id='bookacti-activity-duration-hours' 
							   min='0' max='23' step='1' value='01'
							   onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
						<?php /* translators: 'h' stand for hours */ 
						echo esc_html( _x( 'h', 'h for hours', BOOKACTI_PLUGIN_NAME ) ); ?>
						<input type='number' 
							   name='activity-duration-minutes' 
							   id='bookacti-activity-duration-minutes' 
							   min='0' max='59' step='5' value='00'
							   onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
						<?php /* translators: 'm' stand for minutes */ 
						echo esc_html( _x( 'm', 'm for minutes', BOOKACTI_PLUGIN_NAME ) );
						
						$tip = __( 'The default duration of an event when you drop this activity onto the calendar. Type an amount of days (d), hours (h) and minutes (m). For a better readability, try not to go over your working hours. Best practice for events of several days is to create one event per day and then group them.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
						?>
					</div>
				</div>
				<div>
					<label for='bookacti-activity-resizable' ><?php esc_html_e( 'Change duration on calendar', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php
						$name	= 'activity-resizable';
						$id		= 'bookacti-activity-resizable';
						bookacti_onoffswitch( $name, 0, $id );
					
						$tip = __( "Allow to resize an event directly on calendar.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-color' ><?php esc_html_e( 'Color', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='color' name='activity-color' id='bookacti-activity-color' value='#3a87ad' />
					<?php
						$tip = __( 'Choose a color for the events of this activity.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			<?php
				do_action( 'bookacti_activity_tab_general_after', $params );
			}
			
			
			/**
			 * Display the fields in the "Availability" tab of the Activity dialog
			 * 
			 * @since 1.4.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_availability( $params = array() ) {
				do_action( 'bookacti_activity_tab_availability_before', $params );
			?>
				<div>
					<label for='bookacti-activity-min-bookings-per-user' ><?php esc_html_e( 'Min bookings per user', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='activityOptions[min_bookings_per_user]' 
							id='bookacti-activity-min-bookings-per-user' 
							min='0' step='1' 
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'The minimum booking quantity a user has to make on an event of this activity. E.g.: "3", the customer must book at least 3 places of the desired event.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-max-bookings-per-user' ><?php esc_html_e( 'Max bookings per user', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='activityOptions[max_bookings_per_user]' 
							id='bookacti-activity-max-bookings-per-user' 
							min='0' step='1'
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'The maximum booking quantity a user can make on an event of this activity. E.g.: "1", the customer can only book one place of the desired event, and he won\'t be allowed to book it twice.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-max-users-per-event' ><?php esc_html_e( 'Max users per event', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='activityOptions[max_users_per_event]' 
							id='bookacti-activity-max-users-per-event' 
							min='0' step='1' 
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'Set how many different users can book the same event. E.g.: "1", only one user can book a specific event; once he has booked it, the event won\'t be available for anyone else anymore, even if it isn\'t full. Usefull for private events.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-booking-changes-deadline' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Changes permitted up to 2 days before the event". */ esc_html_e( 'Booking changes are permitted up to', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='activityOptions[booking_changes_deadline]' 
							id='bookacti-activity-booking-changes-deadline' 
							min='-1' step='1'
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						_e( 'days before the event', BOOKACTI_PLUGIN_NAME );
						$tip = __( 'Set the end of the period during which reservation changes are allowed (cancellation, rescheduling). E.g.: "7", your customers may change their reservations at least 7 days before the start of the event. After that, they won\'t be allowed to change them anymore.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'This parameter applies to the events of this activity only. A global parameter is available in global settings.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			<?php
				do_action( 'bookacti_activity_tab_availability_after', $params );
			}
			
			
			function bookacti_fill_activity_tab_terminology( $params = array() ) {
				do_action( 'bookacti_activity_tab_terminology_before', $params );
			?>
				<div>
					<label for='bookacti-activity-unit-name-singular' ><?php esc_html_e( 'Unit name (singular)', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text' name='activityOptions[unit_name_singular]' id='bookacti-activity-unit-name-singular' />
					<?php
						$tip = __( "Name of the unit the customers will actually book for this activity. Set the singular here. Leave blank to hide this piece of information. Ex: 'You have booked 1 <strong><em>unit</em></strong>'.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-unit-name-plural' ><?php esc_html_e( 'Unit name (plural)', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text' name='activityOptions[unit_name_plural]' id='bookacti-activity-unit-name-plural' />
					<?php
						$tip = __( "Name of the unit the customers will actually book for this activity. Set the plural here. Leave blank to hide this piece of information. Ex: 'You have booked 2 <strong><em>units</em></strong>'.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<?php /* translators: We are asking here if the user want to display the unit next to the total availability on the event. Ex: '14 units' instead of '14' */ ?>
					<label for='bookacti-activity-show-unit-in-availability' ><?php esc_html_e( 'Show unit in availability', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php
						$name	= 'activityOptions[show_unit_in_availability]';
						$id		= 'bookacti-activity-show-unit-in-availability';
						bookacti_onoffswitch( $name, 0, $id );
					
						$tip = __( "Show the unit in the availability boxes. Ex: '2 <strong><em>units</em></strong> available' instead of '2'.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-activity-places-number' ><?php esc_html_e( 'Number of places per booking', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='number' name='activityOptions[places_number]' id='bookacti-activity-places-number' min='0' />
					<?php
						$tip = __( "The number of persons who can do the activity with 1 booking. Set 0 to hide this piece of information. Ex: 'You have booked 1 unit for <em>2</em> persons'.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
			<?php
				do_action( 'bookacti_activity_tab_terminology_after', $params );
			}
			
			/**
			 * Display the fields in the "Permissions" tab of the Activity dialog
			 * 
			 * @version 1.4.0
			 * @param array $params
			 */
			function bookacti_fill_activity_tab_permissions( $params = array() ) {
				do_action( 'bookacti_activity_tab_permissions_before', $params );
			?>
				<div>
					<label for='bookacti-activity-roles' class='bookacti-fullwidth-label' >
						<?php 
						esc_html_e( 'Who can book this activity?', BOOKACTI_PLUGIN_NAME );
						
						$tip  = __( 'Choose who is allowed to book the events of this activity.', BOOKACTI_PLUGIN_NAME );
						$tip  .= '<br/>' . __( 'Use CTRL+Click to pick or unpick a role. Don\'t pick any role to allow everybody.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
					</label>
					<div>
						<select name='activityOptions[allowed_roles][]'  id='bookacti-activity-roles' multiple >
						<?php 
							$roles = get_editable_roles();
							foreach( $roles as $role_id => $role ) {
								echo '<option value="' . esc_attr( $role_id ) . '" >' . esc_html( $role[ 'name' ] ) . '</option>'; 
							}
						?>
						</select>
					</div>
				</div>
				<div id='bookacti-activity-managers-container' class='bookacti-items-container' data-type='users' >
					<label id='bookacti-activity-managers-title' class='bookacti-fullwidth-label' >
						<?php 
						esc_html_e( 'Who can manage this activity?', BOOKACTI_PLUGIN_NAME );
						
						$tip  = __( 'Choose who is allowed to change this activity parameters.', BOOKACTI_PLUGIN_NAME );
						/* translators: %s = capabilities name */
						$tip .= ' ' . sprintf( __( 'All administrators already have this privilege. If the selectbox is empty, it means that no users have capabilities such as %s.', BOOKACTI_PLUGIN_NAME ), '"bookacti_edit_activities"' );
						$tip .= '<br/>' 
							 .  sprintf( __( 'Operators from %1$s add-on and Point of sale managers from %2$s add-on have these capabilities. If you want to grant a user these capabilities, use a plugin such as %3$s.', BOOKACTI_PLUGIN_NAME ), 
									'<a href="https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=infobulle-permission" target="_blank" >Order for Customers</a>',
									'<a href="https://booking-activities.fr/en/downloads/points-of-sale/?utm_source=plugin&utm_medium=plugin&utm_campaign=points-of-sale&utm_content=infobulle-permission" target="_blank" >Points of Sale</a>',
									'<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>'
								);
						bookacti_help_tip( $tip );
					?>
					</label>
					<div id='bookacti-add-activity-managers-container' >
						<select id='bookacti-add-new-activity-managers-select-box' class='bookacti-add-new-items-select-box' >
							<?php echo $params[ 'users_options_for_activities' ]; ?>
						</select>
						<button type='button' id='bookacti-add-activity-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', BOOKACTI_PLUGIN_NAME ); ?></button>
					</div>
					<div id='bookacti-activity-managers-list-container' class='bookacti-items-list-container' >
						<select name='activity-managers[]' id='bookacti-activity-managers-select-box' class='bookacti-items-select-box' multiple >
						</select>
						<button type='button' id='bookacti-remove-activity-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', BOOKACTI_PLUGIN_NAME ); ?></button>
					</div>
				</div>
			<?php
				do_action( 'bookacti_activity_tab_permissions_after', $params );
			}
			?>
	</form>
</div>

<!-- Locked event error -->
<div id='bookacti-unbind-booked-event-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div id='bookacti-unbind-booked-event-error-list-container' >
        <?php 
			/* translators: This is followed by "You can't:", and then a list of bans. */
			esc_html_e( 'There are bookings on at least one of the occurence of this event.', BOOKACTI_PLUGIN_NAME ); 
			/* translators: This is preceded by 'There are bookings on at least one of the occurence of this event.', and flollowed by a list of bans. */
			echo '<br/><b>' . esc_html__( "You can't:", BOOKACTI_PLUGIN_NAME ) . '</b>'; 
		?>
        <ul></ul>
    </div>
    <div>
        <?php 
			/* translators: This is preceded by 'There are bookings on at least one of the occurence of this event. You can't: <list of bans>' and followed by "You can:", and then a list of capabilities. */
			esc_html_e( 'If you want to edit independantly the occurences of the event that are not booked:', BOOKACTI_PLUGIN_NAME );
			/* translators: This is preceded by 'There are bookings on at least one of the occurence of this event.', and flollowed by a list of capabilities. */
			echo '<br/><b>' . esc_html__( 'You can:', BOOKACTI_PLUGIN_NAME ) . '</b><br/>';
		?>
        <ul>
            <?php 
						/* translators: This is one of the capabilities following the text 'There are bookings on at least one of the occurence of this event. You can:'. */
				echo  '<li>' . esc_html__( 'Unbind the selected occurence only.', BOOKACTI_PLUGIN_NAME ) . '</li>'
						/* translators: This is one of the capabilities following the text 'There are bookings on at least one of the occurence of this event. You can:'. */
					. '<li>' . esc_html__( 'Unbind all the booked occurences.', BOOKACTI_PLUGIN_NAME ) . '</li>';
//						/* translators: This is one of the capabilities following the text 'There are bookings on at least one of the occurence of this event. You can:'. */
//				echo  '<li>' . __( 'Unbind all occurences.', BOOKACTI_PLUGIN_NAME ) . '</li>';
			?>
        </ul>
        <b><?php esc_html_e( 'Warning: These actions will be irreversibles after the first booking.', BOOKACTI_PLUGIN_NAME ); ?></b>
    </div>
</div>


<!-- Choose between creating a brand new activity or import an existing one -->
<div id='bookacti-activity-create-method-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div id='bookacti-activity-create-method-container' >
        <?php 
			/* translators: This is followed by "You can't:", and then a list of bans. */
			esc_html_e( 'Do you want to create a brand new activity or use on that calendar an activity you already created on an other calendar ?', BOOKACTI_PLUGIN_NAME ); 
		?>
    </div>
</div>


<!-- Import an existing activity -->
<div id='bookacti-activity-import-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
    <div id='bookacti-activity-import-container' >
		<div>
			<?php esc_html_e( 'Import an activity that you have already created on an other calendar:', BOOKACTI_PLUGIN_NAME ); ?>
		</div>
        <div id='bookacti-template-import-bound-activities' >
			<label for='template-import-bound-activities' >
				<?php 
				/* translators: the user is asked to select a calendar to display its bound activities. This is the label of the select box. */
				esc_html_e( 'From calendar', BOOKACTI_PLUGIN_NAME ); 
				?>
			</label>
			<select name="template-import-bound-activities" id='template-import-bound-activities' class='bookacti-template-select-box' >
				<?php echo $templates_options; ?>
			</select>
		</div>
        <div id='bookacti-activities-bound-to-template' >
			<label for='activities-to-import' >
				<?php 
				/* translators: the user is asked to select an activity he already created on an other calendar in order to use it on the current calendar. This is the label of the select box. */
				esc_html_e( 'Activities to import', BOOKACTI_PLUGIN_NAME ); 
				?>
			</label>
			<select name="activities-to-import" id='activities-to-import' multiple >
			</select>
		</div>
    </div>
</div>


<!-- Group of events -->
<div id='bookacti-group-of-events-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
	<form id='bookacti-group-of-events-form' >
		<input type='hidden' name='action' id='bookacti-group-of-events-action' />
		<?php wp_nonce_field( 'bookacti_insert_or_update_group_of_events', 'nonce_insert_or_update_group_of_events' ); ?>
		
		<div id='bookacti-group-of-events-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
			
		<?php
			//Fill the array of tabs with their label, callback for content and display order
			$group_of_events_tabs = apply_filters( 'bookacti_group_of_events_dialog_tabs', array (
				array(	'label'			=> __( 'General', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_group_of_events_tab_general',
						'parameters'	=> array(),
						'order'			=> 10 )
			) );

			// Display tabs
			bookacti_display_tabs( $group_of_events_tabs, 'group-of-events' );
			
			
			/**
			 * Fill "General" tab of "Group of Events" dialog
			 * 
			 * @version 1.7.0
			 * @param array $params
			 */
			function bookacti_fill_group_of_events_tab_general( $params = array() ) {
				do_action( 'bookacti_group_of_events_tab_general_before', $params );
		?>
				<div>
					<?php
						$tip = __( "Name this group of events. Your cutomers may see this name if they have several booking choices (if the event is in two groups, or if you also allow to book the event alone). Choose a short and relevant name.", BOOKACTI_PLUGIN_NAME );
						$args = array(
							'type'			=> 'textarea',
							'name'			=> 'group-of-events-title',
							'id'			=> 'bookacti-group-of-events-title-field',
							'placeholder'	=> $tip,
							'value'			=> ''
						);
					?>
					<div>
						<label for='<?php echo $args[ 'id' ]; ?>' class='bookacti-fullwidth-label' >
						<?php 
							esc_html_e( 'Group title', BOOKACTI_PLUGIN_NAME );
							bookacti_help_tip( $tip );
						?>
						</label>
						<?php bookacti_display_field( $args ); ?>
					</div>
				</div>
				<div>
					<label for='bookacti-group-of-events-category-selectbox' ><?php esc_html_e( 'Group category', BOOKACTI_PLUGIN_NAME ); ?></label>
					<select name='group-of-events-category' id='bookacti-group-of-events-category-selectbox' >
						<option value='new' ><?php _e( 'New category', BOOKACTI_PLUGIN_NAME ); ?></option>
						<?php
							$template_id = get_user_meta( get_current_user_id(), 'bookacti_default_template', true );
							if( ! empty( $template_id ) ) {
								$categories	= bookacti_get_group_categories( $template_id );
								foreach( $categories as $category ) {
									echo "<option value='" . $category[ 'id' ] . "' >" . $category[ 'title' ] . "</option>";
								}
							}
						?>
					</select>
					<?php
						$tip = __( "Pick a category for your group of events.", BOOKACTI_PLUGIN_NAME );
						$tip .= __( "Thanks to categories, you will be able to choose what groups of events are available on what booking forms.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div id='bookacti-group-of-events-new-category-title' >
					<label for='bookacti-group-of-events-category-title-field' ><?php esc_html_e( 'New category title', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text' name='group-of-events-category-title' id='bookacti-group-of-events-category-title-field' />
					<?php
						$tip = __( "Name the group of events category.", BOOKACTI_PLUGIN_NAME );
						$tip .= __( "Thanks to categories, you will be able to choose what groups of events are available on what booking forms.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<!-- This field is only used for feedback, it is not used to pass any AJAX data, events list is passed through an array made with JS -->
					<label for='bookacti-group-of-events-summary' ><?php esc_html_e( 'Events list', BOOKACTI_PLUGIN_NAME ); ?></label>
					<select multiple id='bookacti-group-of-events-summary' class='bookacti-custom-scrollbar' ></select>
				</div>
		<?php
				
				do_action( 'bookacti_group_of_events_tab_general_after', $params );
				
				bookacti_promo_for_bapap_addon( 'group-of-events' );
			}
		?>
	</form>
</div>


<!-- Group category -->
<div id='bookacti-group-category-dialog' class='bookacti-backend-dialog bookacti-template-dialogs' style='display:none;' >
	<form id='bookacti-group-category-form' >
		<input type='hidden' name='action' id='bookacti-group-category-action' />
		<?php wp_nonce_field( 'bookacti_insert_or_update_group_category', 'nonce_insert_or_update_group_category' ); ?>
		
		<div id='bookacti-group-category-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
			//Fill the array of tabs with their label, callback for content and display order
			$group_category_tabs = apply_filters( 'bookacti_group_category_dialog_tabs', array (
				array(	'label'			=> __( 'General', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_group_category_tab_general',
						'parameters'	=> array(),
						'order'			=> 10 ),
				array(	'label'			=> __( 'Availability', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_group_category_tab_availability',
						'parameters'	=> array(),
						'order'			=> 20 ),
				array(	'label'			=> __( 'Permissions', BOOKACTI_PLUGIN_NAME ),
						'callback'		=> 'bookacti_fill_group_category_tab_permissions',
						'parameters'	=> array(),
						'order'			=> 100 )
			) );
			
			// Display tabs
			bookacti_display_tabs( $group_category_tabs, 'group-category' );
			
			// GENERAL tab
			function bookacti_fill_group_category_tab_general( $params = array() ) {
				do_action( 'bookacti_group_category_tab_general_before', $params );
		?>
				<div>
					<label for='bookacti-group-category-title-field' ><?php esc_html_e( 'Category title', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input type='text' name='group-category-title' id='bookacti-group-category-title-field' />
					<?php
						$tip = __( "Name the group of events category.", BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
		<?php
				do_action( 'bookacti_group_category_tab_general_after', $params );
			}
			
			/**
			 * Display the fields in the "Availability" tab of the Group Category dialog
			 * 
			 * @since 1.4.0
			 * @param array $params
			 */
			function bookacti_fill_group_category_tab_availability( $params = array() ) {
				do_action( 'bookacti_group_category_tab_availability_before', $params );
			?>
				<div>
					<label for='bookacti-group-category-min-bookings-per-user' ><?php esc_html_e( 'Min bookings per user', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='groupCategoryOptions[min_bookings_per_user]' 
							id='bookacti-group-category-min-bookings-per-user' 
							min='0' step='1' 
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'The minimum booking quantity a user has to make on a group of events of this category. E.g.: "3", the customer must book at least 3 places of the desired group of events.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-group-category-max-bookings-per-user' ><?php esc_html_e( 'Max bookings per user', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='groupCategoryOptions[max_bookings_per_user]' 
							id='bookacti-group-category-max-bookings-per-user' 
							min='0' step='1'
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'The maximum booking quantity a user can make on a group of events of this category. E.g.: "1", the customer can only book one place of the desired group of events, and he won\'t be allowed to book it twice.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-group-category-max-users-per-event' ><?php esc_html_e( 'Max users per event', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='groupCategoryOptions[max_users_per_event]' 
							id='bookacti-group-category-max-users-per-event' 
							min='0' step='1' 
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						$tip = __( 'Set how many different users can book the same group of events. E.g.: "1", only one user can book a specific group of events; once he has booked it, the group of events won\'t be available for anyone else anymore, even if it isn\'t full. Usefull for private events.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "0" to ignore this parameter.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-group-category-booking-changes-deadline' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Changes permitted up to 2 days before the event". */ esc_html_e( 'Booking changes are permitted up to', BOOKACTI_PLUGIN_NAME ); ?></label>
					<input	type='number' 
							name='groupCategoryOptions[booking_changes_deadline]' 
							id='bookacti-group-category-booking-changes-deadline' 
							min='-1' step='1'
							onkeypress='return event.charCode >= 48 && event.charCode <= 57' />
					<?php
						_e( 'days before the first event', BOOKACTI_PLUGIN_NAME );
						$tip = __( 'Set the end of the period during which reservation changes are allowed (cancellation). E.g.: "7", your customers may change their reservations at least 7 days before the start of the first event of the group. After that, they won\'t be allowed to change them anymore.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'This parameter applies to the groups of events of this category only. A global parameter is available in global settings.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
				</div>
				<div>
					<label for='bookacti-group-category-started-groups-bookable' ><?php esc_html_e( 'Are started groups bookable?', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php
						$tip = __( 'Allow or disallow users to book a group of events that has already begun.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'This parameter applies to the groups of events of this category only. A global parameter is available in global settings.', BOOKACTI_PLUGIN_NAME );
						$tip .= '<br/>' . __( 'Set it to "Site setting" to use the global value.', BOOKACTI_PLUGIN_NAME );
						
						$args = array(
							'type'	=> 'select',
							'name'	=> 'groupCategoryOptions[started_groups_bookable]',
							'id'	=> 'bookacti-group-category-started-groups-bookable',
							'options'	=> array( 
								'-1' => __( 'Site setting', BOOKACTI_PLUGIN_NAME ),
								'0' => __( 'No', BOOKACTI_PLUGIN_NAME ),
								'1' => __( 'Yes', BOOKACTI_PLUGIN_NAME )
							),
							'value'	=> -1,
							'tip'	=> $tip
						);
						bookacti_display_field( $args );
					?>
				</div>
			<?php
				do_action( 'bookacti_group_category_tab_availability_after', $params );
			}
			
			
			/**
			 * Display the fields in the "Permissions" tab of the Group Category dialog
			 * 
			 * @version 1.4.0
			 * @param array $params
			 */
			function bookacti_fill_group_category_tab_permissions( $params = array() ) {
				do_action( 'bookacti_group_category_tab_permissions_before', $params );
			?>
				<div>
					<label for='bookacti-group-category-roles' class='bookacti-fullwidth-label' >
						<?php 
						esc_html_e( 'Who can book this category of groups?', BOOKACTI_PLUGIN_NAME );
						
						$tip  = __( 'Choose who is allowed to book the groups of this category.', BOOKACTI_PLUGIN_NAME );
						$tip  .= '<br/>' . __( 'Use CTRL+Click to pick or unpick a role. Don\'t pick any role to allow everybody.', BOOKACTI_PLUGIN_NAME );
						bookacti_help_tip( $tip );
					?>
					</label>
					<div>
						<select name='groupCategoryOptions[allowed_roles][]'  id='bookacti-group-category-roles' multiple >
						<?php 
							$roles = get_editable_roles();
							foreach( $roles as $role_id => $role ) {
								echo '<option value="' . esc_attr( $role_id ) . '" >' . esc_html( $role[ 'name' ] ) . '</option>'; 
							}
						?>
						</select>
					</div>
				</div>
			<?php
				do_action( 'bookacti_group_category_tab_permissions_after', $params );
			}
		?>
	</form>
</div>

<?php
do_action( 'bookacti_calendar_editor_dialogs', $templates );