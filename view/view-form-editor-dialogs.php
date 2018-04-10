<?php
/**
 * Form editor dialogs
 * @since 1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$fields_data = bookacti_get_default_form_fields_data();
$fields_meta = bookacti_get_default_form_fields_meta();
foreach( $fields_data as $field_name => $field_data ) {
	if( $fields_meta[ $field_name ] ) { $fields_data[ $field_name ] = array_merge( $field_data, $fields_meta[ $field_name ] ); }
}
?>


<!-- Add a new field dialog -->
<div id='bookacti-insert-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php _e( 'Add a field to the form', BOOKACTI_PLUGIN_NAME ); ?>' >
	<form id='bookacti-insert-form-field-form' >
		<input type='hidden' name='action' value='bookactiInsertFormField' />
		<?php 
			wp_nonce_field( 'bookacti_insert_form_field', 'nonce_insert_form_field', false ); 
			do_action( 'bookacti_insert_form_field_dialog_before', $form, $form_fields );
		?>
		<div>
			<p class='bookacti-dialog-intro'><?php _e( 'Pick the field to add to your form:', BOOKACTI_PLUGIN_NAME ); ?></p>
			<label for='bookacti-field-to-insert'><?php _e( 'Field to insert', BOOKACTI_PLUGIN_NAME ); ?></label>
			<select name='field_to_insert' id='bookacti-field-to-insert' >
			<?php 
				// Get fields already added
				$field_already_added = array();
				foreach( $form_fields as $form_field ) { $field_already_added[] = $form_field[ 'name' ]; }

				// Display available fields options
				foreach( $fields_data as $field_data ) {
					// Add the field if it isn't already in the form, or if it is not unique
					$disabled = in_array( $field_data[ 'name' ], $field_already_added, true ) && $field_data[ 'unique' ] ? 'disabled' : '';
					if( ! $field_data[ 'compulsory' ] ) {
						echo '<option value="' . $field_data[ 'name' ] . '" data-unique="' . $field_data[ 'unique' ] . '" ' . $disabled . '>' . $field_data[ 'title' ] . '</option>';
					}
				}
			?>
			</select>
		<div>
		<?php 
			do_action( 'bookacti_insert_form_field_dialog_after', $form, $form_fields );
		?>
	</form>
</div>

			
<!-- Remove field dialog -->
<div id='bookacti-remove-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php _e( 'Remove this field from the form', BOOKACTI_PLUGIN_NAME ); ?>' >
	<form id='bookacti-remove-form-field-form' >
		<?php wp_nonce_field( 'bookacti_remove_form_field', 'nonce_remove_form_field', false ); ?>
		<input type='hidden' name='action' value='bookactiRemoveFormField' />
		<div><?php esc_html_e( 'Are you sure to delete this field permanently?', BOOKACTI_PLUGIN_NAME ); ?></div>
	</form>
</div>

			
<!-- Edit form meta dialog -->
<div id='bookacti-form-meta-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php _e( 'Form options', BOOKACTI_PLUGIN_NAME ); ?>' >
	<form id='bookacti-update-form-meta-form' >
		<input type='hidden' name='action' value='bookactiUpdateFormMeta' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form' ); ?>' />
		<input type='hidden' name='form_id' value='<?php $form_id ?>' />
		<?php 
			do_action( 'bookacti_form_meta_dialog_before', $form, $form_fields );
		?>
		<div>
			<label for='bookacti-form-meta-id'><?php _e( 'ID', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'id',
					'id'	=> 'bookacti-form-meta-id',
					'value'	=> $form[ 'id' ],
					'tip'	=> __( 'Form id. Leave this empty if you display this form multiple times on the same page.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-form-meta-class'><?php _e( 'Class', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'class',
					'id'	=> 'bookacti-form-meta-class',
					'value'	=> $form[ 'class' ],
					'tip'	=> __( 'Form class. Leave an empty space between each classes.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-form-meta-redirect_url'><?php _e( 'Redirect URL', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'redirect_url',
					'id'	=> 'bookacti-form-meta-redirect_url',
					'value'	=> $form[ 'redirect_url' ],
					'tip'	=> __( 'Page URL where the customer will be redirected after submitting the booking form.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_form_meta_dialog_after', $form, $form_fields );
		?>
	</form>
</div>


<!-- Calendar field dialog -->
<div id='bookacti-form-field-dialog-calendar' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'calendar' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-calendar' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		
		<div id='bookacti-form-field-dialog-calendar-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		
		$calendar_field_id = 0;
		foreach( $form_fields as $field_id => $field_data ) {
			if( $field_data[ 'name' ] === 'calendar' ) { $calendar_field_id = $field_id; break; }
		}
		
		// Fill the array of tabs with their label, callback for content and display order
		$calendar_tabs = apply_filters( 'bookacti_form_field_calendar_dialog_tabs', array (
			array(	'label'			=> __( 'Filters', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'filters',
					'callback'		=> 'bookacti_fill_calendar_dialog_filters_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 10 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $calendar_tabs, 'calendar' );
		
		/**
		 * Display the content of the "Login" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_filters_tab( $params ) {
			$form			= $params[ 'form' ];
			$calendar_data	= $params[ 'calendar_data' ];
			$fields_data	= $params[ 'fields_data' ];
			do_action( 'bookacti_calendar_dialog_filters_tab_before', $params );
		?>
			<div>
			<?php
				$method_field_id = 'method'; 
				$booking_methods_array = array_merge( array( 'site' => __( 'Site setting', BOOKACTI_PLUGIN_NAME ) ), bookacti_get_available_booking_methods() );
			?>
				<label for='<?php echo $method_field_id; ?>'><?php esc_html_e( 'Booking method', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select id='<?php echo $method_field_id; ?>' name='<?php echo $method_field_id; ?>' >
				<?php 
					foreach( $booking_methods_array as $booking_method_id => $booking_method_title ) {
						echo '<option value="' . esc_attr( $booking_method_id ) . '" >'
								. esc_html( $booking_method_title )
							. '</option>';
					}
				?>
				</select>
			<?php
				$tip = __( 'Choose a method for your customers to access your events.', BOOKACTI_PLUGIN_NAME );

				$license_status = get_option( 'badp_license_status' );
				if( ! $license_status || $license_status !== 'valid' ) {
					$tip .= '<br/>' 
						. sprintf( 
							esc_html__( 'Get more display methods with %1$sDisplay Pack%2$s add-on!', BOOKACTI_PLUGIN_NAME ),
							'<a href="https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=landing" target="_blank" >', 
							'</a>' );
				}
				bookacti_help_tip( $tip );
			?>
			</div>
		
		<fieldset>
			<legend><?php _e( 'Event sources', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<div>
			<?php
				$template_field_id	= '_bookacti_template'; 
				$templates			= bookacti_fetch_templates();
			?>
				<label for='<?php echo $template_field_id; ?>'><?php esc_html_e( 'Calendar', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select id='<?php echo $template_field_id; ?>' 
						name='calendars' 
						<?php if( count( $templates ) > 1 ) { echo 'style="margin-right:5px;"'; } ?> >
				<?php 
					foreach( $templates as $template ) {
						$template_title = apply_filters( 'bookacti_translate_text', $template[ 'title' ] );
						echo '<option value="' . esc_attr( $template[ 'id' ] ) . '" >'
								. esc_html( $template_title )
							. '</option>';
					}
				?>
				</select>
			<?php if( count( $templates ) > 1 ) { ?>
				<span class='bookacti-multiple-select-container' >
					<label for='bookacti-multiple-select-<?php echo $template_field_id; ?>' ><span class='dashicons dashicons-plus' title='<?php esc_attr_e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?>'></span></label>
					<input type='checkbox' 
						   class='bookacti-multiple-select' 
						   id='bookacti-multiple-select-<?php echo $template_field_id; ?>' 
						   data-select-id='<?php echo $template_field_id; ?>'
						   style='display:none' />
				</span>
			<?php } 
				$tip = esc_html__( 'Retrieve events from the selected calendars only.', BOOKACTI_PLUGIN_NAME );
				/* translators: %s is the "+" icon to click on. */
				if( count( $templates ) > 1 ) { $tip .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s', BOOKACTI_PLUGIN_NAME ), '<span class="dashicons dashicons-plus"></span>' ); }
				bookacti_help_tip( $tip );
			?>
			</div>
		
		
			<div>
			<?php 
				$activity_field_id	= 'activities'; 
				$activities			= bookacti_fetch_activities_with_templates_association();
			?>
				<label for="<?php echo $activity_field_id; ?>"><?php esc_html_e( 'Activity', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select id='<?php echo $activity_field_id; ?>' 
						name='<?php echo $activity_field_id; ?>'
						<?php if( count( $activities ) > 1 ) { echo 'style="margin-right:5px;"'; } ?> >
				<?php 
					foreach( $activities as $activity ) {
						$activity_title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
						echo '<option '
								.  'value="' . esc_attr( $activity[ 'id' ] ) . '" '
								.  'data-bookacti-show-if-templates="' . esc_attr( implode( ',', $activity[ 'template_ids' ] ) ) . '" >'
									. esc_html( $activity_title )
							.  '</option>';
					}
				?>
				</select>
			<?php if( count( $activities ) > 1 ) { ?>
				<span class='bookacti-multiple-select-container' >
					<label for='bookacti-multiple-select-<?php echo $activity_field_id; ?>' ><span class='dashicons dashicons-plus' title='<?php esc_attr_e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?>'></span></label>
					<input type='checkbox' 
						   class='bookacti-multiple-select' 
						   id='bookacti-multiple-select-<?php echo $activity_field_id; ?>' 
						   data-select-id='<?php echo $activity_field_id; ?>'
						   style='display:none' />
				</span>
			<?php } 
				$tip = esc_html__( 'Retrieve events from the selected activities only.', BOOKACTI_PLUGIN_NAME );
				/* translators: %s is the "+" icon to click on. */
				if( count( $activities ) > 1 ) { $tip .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s', BOOKACTI_PLUGIN_NAME ), '<span class="dashicons dashicons-plus"></span>' ); }
				bookacti_help_tip( $tip );
			?>
			</div>
		</fieldset>
		
		<fieldset>
			<legend><?php _e( 'Groups of events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<div>
			<?php 
				$groups_field_id	= 'group_categories'; 
				$categories			= bookacti_get_group_categories();
			?>
				<label for='<?php echo $groups_field_id; ?>'><?php esc_html_e( 'Group category', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select id='<?php echo $groups_field_id; ?>' 
						name='<?php echo $groups_field_id; ?>'
						<?php if( count( $categories ) > 1 ) { echo 'style="margin-right:5px;"'; } ?> >
					<option value='none' ><?php _ex( 'None', 'About group category', BOOKACTI_PLUGIN_NAME ); ?></option>
				<?php 
					foreach( $categories as $category ) {
						$category_title = apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
						echo '<option '
								.  'value="' . esc_attr( $category[ 'id' ] ) . '" '
								.  'data-bookacti-show-if-templates="' . $category[ 'template_id' ] . '" >'
									. esc_html( $category_title )
							.  '</option>';
					}
				?>
				</select>
			<?php if( count( $categories ) > 1 ) { ?>
				<span class='bookacti-multiple-select-container' >
					<label for='bookacti-multiple-select-<?php echo $groups_field_id; ?>' ><span class='dashicons dashicons-plus' title='<?php esc_attr_e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?>'></span></label>
					<input type='checkbox' 
						   class='bookacti-multiple-select' 
						   id='bookacti-multiple-select-<?php echo $groups_field_id; ?>' 
						   data-select-id='<?php echo $groups_field_id; ?>'
						   style='display:none' />
				</span>
			<?php } 
				$tip = esc_html__( 'Retrieve groups of events from the selected group categories only.', BOOKACTI_PLUGIN_NAME );
				/* translators: %s is the "+" icon to click on. */
				if( count( $categories ) > 1 ) { $tip .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s', BOOKACTI_PLUGIN_NAME ), '<span class="dashicons dashicons-plus"></span>' ); }
				bookacti_help_tip( $tip );
			?>
			</div>
		
		
			<div>
				<label for='_bookacti_groups_only' ><?php esc_html_e( 'Groups only', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
				// Groups only checkbox
				$args = array(
					'type' => 'checkbox',
					'name' => 'groups_only',
					'id' => '_bookacti_groups_only',
					'value' => 0,
					'tip' => esc_html__( 'Display only groups of events if checked. Else, also display the other single events (if any).', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
		
		
			<div>
				<label for='groups_single_events' ><?php esc_html_e( 'Book grouped events alone', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
				// Groups events alone checkbox
				$args = array(
					'type' => 'checkbox',
					'name' => 'groups_single_events',
					'id' => 'groups_single_events',
					'value' => 0,
					'tip' => esc_html__( 'When a customer picks an event belonging to a group, let him choose between the group or the event alone.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
		</fieldset>
		
		<fieldset>
			<legend><?php _e( 'Availability period', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<div>
				<label for='availability_period_start' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */ esc_html_e( 'Events will be bookable in', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$tip = __( 'Set the beginning of the availability period. E.g.: "2", your customers may book events starting in 2 days at the earliest. They are no longer allowed to book events starting earlier (like today or tomorrow).', BOOKACTI_PLUGIN_NAME );
				$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
				
				$args = array(
					'type' => 'number',
					'name' => 'availability_period_start',
					'id' => 'availability_period_start',
					'options' => array( 'min' => -1, 'step' => 1 ),
					/* translators: Arrives after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
					'label' => esc_html__( 'days from today', BOOKACTI_PLUGIN_NAME ),
					'tip' => $tip
				);
				bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='availability_period_end' ><?php /* translators: Followed by a field indicating a number of days before the event. E.g.: "Events are bookable for up to 30 days from today". */ esc_html_e( 'Events are bookable for up to', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$tip = __( 'Set the end of the availability period. E.g.: "30", your customers may book events starting within 30 days at the latest. They are not allowed yet to book events starting later.', BOOKACTI_PLUGIN_NAME );
				$tip .= '<br/>' . __( 'Set it to "-1" to use the global value.', BOOKACTI_PLUGIN_NAME );
						
				$args = array(
					'type' => 'number',
					'name' => 'availability_period_end',
					'id' => 'availability_period_end',
					'options' => array( 'min' => -1, 'step' => 1 ),
					/* translators: Arrives after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
					'label' => esc_html__( 'days from today', BOOKACTI_PLUGIN_NAME ),
					'tip' => $tip
				);
				bookacti_display_field( $args );
				?>
			</div>
			<div class='bookacti-advanced-option'>
				<label for='start' ><?php esc_html_e( 'Opening', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$args = array(
					'type' => 'date',
					'name' => 'start',
					'id' => 'start',
					'tip' => __( 'The calendar will start at this date.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
			<div class='bookacti-advanced-option'>
				<label for='end' ><?php esc_html_e( 'Closing', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$args = array(
					'type' => 'date',
					'name' => 'end',
					'id' => 'end',
					'tip' => __( 'The calendar will end at this date.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
		</fieldset>
		
		<fieldset class='bookacti-advanced-option'>
			<legend><?php _e( 'Past events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<div>
				<label for='past_events' ><?php echo esc_html_x( 'Display them?', 'About past events', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$args = array(
					'type' => 'checkbox',
					'name' => 'past_events',
					'id' => 'past_events',
					'value' => 0,
					'tip' => esc_html__( 'Whether to display past events.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='past_events_bookable' ><?php echo esc_html_x( 'Are they bookable?', 'About past events', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
				$args = array(
					'type' => 'checkbox',
					'name' => 'past_events_bookable',
					'id' => 'past_events_bookable',
					'value' => 0,
					'tip' => esc_html__( 'Whether to allow customers to select past events and book them.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
		</fieldset>
		
		<fieldset class='bookacti-advanced-option'>
			<legend><?php _e( 'Booked events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<div>
				<label for='bookings_only' ><?php esc_html_e( 'Booked only', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				$args = array(
					'type' => 'checkbox',
					'name' => 'bookings_only',
					'id' => 'bookings_only',
					'value' => 0,
					'tip' => esc_html__( 'Display only events that has been booked.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
				?>
			</div>
		
		
			<div>
				<?php $status_field_id = 'status'; ?>
				<label for='<?php echo $status_field_id; ?>' ><?php esc_html_e( 'Bookings status', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select id='<?php echo $status_field_id; ?>'
						name='<?php echo $status_field_id; ?>'
						<?php if( count( $categories ) > 1 ) { echo 'style="margin-right:5px;"'; } ?> >
					<option value='none' ><?php _ex( 'None', 'About booking status', BOOKACTI_PLUGIN_NAME ); ?></option>
				<?php
					$statuses = bookacti_get_booking_state_labels();
					foreach ( $statuses as $status_id => $status ) { 
						echo '<option value="' . esc_attr( $status_id ) . '" >'
								. esc_html( $status[ 'label' ] )
							.  '</option>';
					} 
				?>
				</select>
				<?php if( count( $statuses ) > 1 ) { ?>
				<span class='bookacti-multiple-select-container' >
					<label for='bookacti-multiple-select-<?php echo $status_field_id; ?>' ><span class='dashicons dashicons-plus' title='<?php esc_attr_e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?>'></span></label>
					<input type='checkbox' 
						   class='bookacti-multiple-select' 
						   id='bookacti-multiple-select-<?php echo $status_field_id; ?>' 
						   data-select-id='<?php echo $status_field_id; ?>'
						   style='display:none' />
				</span>
			<?php } 
				$tip = esc_html__( 'Retrieve booked events with the selected booking status only.', BOOKACTI_PLUGIN_NAME );
				$tip .= ' ' . esc_html__( '"Booked only" option must be activated.', BOOKACTI_PLUGIN_NAME );
				/* translators: %s is the "+" icon to click on. */
				if( count( $statuses ) > 1 ) { $tip .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s', BOOKACTI_PLUGIN_NAME ), '<span class="dashicons dashicons-plus"></span>' ); }
				bookacti_help_tip( $tip );
			?>
			</div>
		
		
			<div>
			<?php $status_field_id = 'user_id'; ?>
				<label for='<?php echo $status_field_id; ?>' ><?php esc_html_e( 'Customer', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php
				$args = apply_filters( 'bookacti_booking_list_user_selectbox_args', array(
					'name'					=> $status_field_id,
					'id'					=> $status_field_id,
					'show_option_all'		=> __( 'None', BOOKACTI_PLUGIN_NAME ),
					'show_option_current'	=> __( 'Current user', BOOKACTI_PLUGIN_NAME ),
					'option_label'			=> array( 'user_login', ' (', 'user_email', ')' ),
					'selected'				=> 0,
					'echo'					=> true
				));
				bookacti_display_user_selectbox( $args );
				
				$tip = esc_html__( 'Retrieve events booked by the selected user only.', BOOKACTI_PLUGIN_NAME );
				$tip .= ' ' . esc_html__( '"Booked only" option must be activated.', BOOKACTI_PLUGIN_NAME );
				bookacti_help_tip( $tip );
			?>
			</div>
		</fieldset>
		<?php
			do_action( 'bookacti_calendar_dialog_filters_tab_after', $params );
		}
		?>
		<div class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' 
			 data-show-title='<?php _e( 'Show advanced options', BOOKACTI_PLUGIN_NAME ); ?>'
			 data-hide-title='<?php _e( 'Hide advanced options', BOOKACTI_PLUGIN_NAME ); ?>'>
			<?php _e( 'Show advanced options', BOOKACTI_PLUGIN_NAME ); ?>
	   </div>
	</form>
</div>


<!-- Login field dialog -->
<div id='bookacti-form-field-dialog-login' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'login' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-login' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		
		<div id='bookacti-form-field-dialog-login-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		//Fill the array of tabs with their label, callback for content and display order
		$login_tabs = apply_filters( 'bookacti_form_field_login_dialog_tabs', array (
			array(	'label'			=> __( 'Login', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'login',
					'callback'		=> 'bookacti_fill_login_dialog_login_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 10 ),
			array(	'label'			=> __( 'Register', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'register',
					'callback'		=> 'bookacti_fill_login_dialog_register_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 20 ),
			array(	'label'			=> __( 'User data', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'user_meta',
					'callback'		=> 'bookacti_fill_login_dialog_user_meta_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 30 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $login_tabs, 'login' );
		
		/**
		 * Display the content of the "Login" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_login_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields			= $params[ 'fields' ];
			$fields_data	= $params[ 'fields_data' ];
			do_action( 'bookacti_login_dialog_login_tab_before', $params );
		?>
			<fieldset>
				<legend><?php _e( 'Email address', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-email-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[email]',
							'id'	=> 'bookacti-email-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-email-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[email]',
							'id'	=> 'bookacti-email-placeholder',
							'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-email-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[email]',
							'id'	=> 'bookacti-email-tip',
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Password', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-password-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[password]',
							'id'	=> 'bookacti-password-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option' >
					<label for='bookacti-password-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[password]',
							'id'	=> 'bookacti-password-placeholder',
							'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option' >
					<label for='bookacti-password-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[password]',
							'id'	=> 'bookacti-password-tip',
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
		<?php 
			do_action( 'bookacti_login_dialog_login_tab_after', $params );
		}
		
		
		/**
		 * Display the content of the "Register" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_register_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields			= $params[ 'fields' ];
			$fields_data	= $params[ 'fields_data' ];
			do_action( 'bookacti_login_dialog_register_tab_before', $params );
		?>
			<div>
				<label for='bookacti-displayed_fields-register'><?php _e( 'Allow users to create an account?', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'displayed_fields[register]',
						'id'	=> 'bookacti-displayed_fields-register',
						'tip'	=> __( 'Are users allowed to create an account while submitting this booking form?', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-register-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'text',
						'name'	=> 'label[register]',
						'id'	=> 'bookacti-register-label',
						'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div class='bookacti-advanced-option'>
				<label for='bookacti-register-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'text',
						'name'	=> 'tip[register]',
						'id'	=> 'bookacti-register-tip',
						'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-generate-password'><?php _e( 'Generate Password?', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'generate_password',
						'id'	=> 'bookacti-generate-password',
						'value'	=> 0,
						'tip'	=> __( 'Whether to automatically generate the password.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-send-new-account-email'><?php _e( 'Send new account email?', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'send_new_account_email',
						'id'	=> 'bookacti-send-new-account-email',
						'value'	=> 0,
						'tip'	=> __( 'Whether to automatically send an email to the user if he has created an account with the booking form.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
		<?php
			do_action( 'bookacti_login_dialog_register_tab_after', $params );
		}
		
		
		/**
		 * Display the content of the "User meta" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_user_meta_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields			= $params[ 'fields' ];
			$fields_data	= $params[ 'fields_data' ];
			do_action( 'bookacti_login_dialog_user_meta_tab_before', $params );
		?>
			<fieldset>
				<legend><?php _e( 'First name', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-displayed_fields-first_name'><?php _e( 'Displayed', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'displayed_fields[first_name]',
							'id'	=> 'bookacti-displayed_fields-first_name',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is displayed in the form.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div>
					<label for='bookacti-first_name-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[first_name]',
							'id'	=> 'bookacti-first_name-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-first_name-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[first_name]',
							'id'	=> 'bookacti-first_name-placeholder',
							'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-first_name-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[first_name]',
							'id'	=> 'bookacti-first_name-tip',
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-required_fields-first_name'><?php _e( 'Required', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'required_fields[first_name]',
							'id'	=> 'bookacti-required_fields-first_name',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is compulsory.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Last name', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-displayed_fields-last_name'><?php _e( 'Displayed', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'displayed_fields[last_name]',
							'id'	=> 'bookacti-displayed_fields-last_name',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is displayed in the form.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div>
					<label for='bookacti-last_name-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[last_name]',
							'id'	=> 'bookacti-last_name-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-last_name-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[last_name]',
							'id'	=> 'bookacti-last_name-placeholder',
							'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-last_name-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[last_name]',
							'id'	=> 'bookacti-last_name-tip',
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-required_fields-last_name'><?php _e( 'Required', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'required_fields[last_name]',
							'id'	=> 'bookacti-required_fields-last_name',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is compulsory.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Phone number', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-displayed_fields-phone'><?php _e( 'Displayed', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'displayed_fields[phone]',
							'id'	=> 'bookacti-displayed_fields-phone',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is displayed in the form.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div>
					<label for='bookacti-phone-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[phone]',
							'id'	=> 'bookacti-phone-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-phone-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[phone]',
							'id'	=> 'bookacti-phone-placeholder',
							'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-phone-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[phone]',
							'id'	=> 'bookacti-phone-tip',
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-advanced-option'>
					<label for='bookacti-required_fields-phone'><?php _e( 'Required', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'required_fields[phone]',
							'id'	=> 'bookacti-required_fields-phone',
							'value'	=> 0,
							'tip'	=> __( 'Whether this field is compulsory.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
		<?php
			do_action( 'bookacti_login_dialog_user_meta_tab_after', $params );
		}
		?>
		<div class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' 
			 data-show-title='<?php _e( 'Show advanced options', BOOKACTI_PLUGIN_NAME ); ?>'
			 data-hide-title='<?php _e( 'Hide advanced options', BOOKACTI_PLUGIN_NAME ); ?>'>
			<?php _e( 'Show advanced options', BOOKACTI_PLUGIN_NAME ); ?>
	   </div>
	</form>
</div>


<!-- Quantity field dialog -->
<div id='bookacti-form-field-dialog-quantity' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'quantity' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-quantity' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_quantity_dialog_before', $form, $form_fields );
		?>
		<div>
			<label for='bookacti-quantity-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'label',
					'id'	=> 'bookacti-quantity-label',
					'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-quantity-placeholder'><?php _e( 'Placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'placeholder',
					'id'	=> 'bookacti-quantity-placeholder',
					'tip'	=> __( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-quantity-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'tip',
					'id'	=> 'bookacti-quantity-tip',
					'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_quantity_dialog_after', $form, $form_fields );
		?>
		<p>
		<?php
			// Warning about min and max values
			_e( 'Min and Max values are dynamilly set according to the selected event and its availability settings.', BOOKACTI_PLUGIN_NAME );
		?>
		</p>
	</form>
</div>


<!-- Submit button dialog -->
<div id='bookacti-form-field-dialog-submit' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'submit' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-submit' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_submit_dialog_before', $form, $form_fields );
		?>
		<div>
			<label for='bookacti-submit-value'><?php _e( 'Button text', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'value',
					'id'	=> 'bookacti-submit-value',
					'tip'	=> __( 'Text displayed on the button.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_submit_dialog_after', $form, $form_fields );
		?>
	</form>
</div>


<!-- Free text field dialog -->
<div id='bookacti-form-field-dialog-free_text' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'free_text' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-free_text' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_free_text_dialog_before', $form, $form_fields );
		?>
		<div>
			<label for='bookacti-free_text-title'><?php _e( 'Title', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'title',
					'id'	=> 'bookacti-free_text-title',
					'tip'	=> __( 'Field title displayed in form editor only.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-free_text-value' class='bookacti-fullwidth-label' ><?php _e( 'Free text', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'editor',
					'name'	=> 'value',
					'id'	=> 'bookacti-free_text-value',
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_free_text_dialog_after', $form, $form_fields );
		?>
	</form>
</div>


<?php
do_action( 'bookacti_form_editor_dialogs', $form, $form_fields );