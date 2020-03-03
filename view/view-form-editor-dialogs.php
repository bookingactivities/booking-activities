<?php
/**
 * Form editor dialogs
 * @since 1.5.0
 * @version 1.7.13
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
<div id='bookacti-insert-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php esc_html_e( 'Add a field to the form', 'booking-activities' ); ?>' >
	<form id='bookacti-insert-form-field-form' >
		<input type='hidden' name='action' value='bookactiInsertFormField' />
		<?php 
			wp_nonce_field( 'bookacti_insert_form_field', 'nonce_insert_form_field', false ); 
			do_action( 'bookacti_insert_form_field_dialog_before', $form, $form_fields );
		?>
		<div>
			<p class='bookacti-dialog-intro'><?php esc_html_e( 'Pick the field to add to your form:', 'booking-activities' ); ?></p>
			<label for='bookacti-field-to-insert'><?php esc_html_e( 'Field to insert', 'booking-activities' ); ?></label>
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
						echo '<option value="' . $field_data[ 'name' ] . '" data-unique="' . $field_data[ 'unique' ] . '" ' . $disabled . '>' . apply_filters( 'bookacti_translate_text', $field_data[ 'title' ] ) . '</option>';
					}
				}
			?>
			</select>
		</div>
		<?php 
			do_action( 'bookacti_insert_form_field_dialog_after', $form, $form_fields );
			
			bookacti_display_baaf_promo();
		?>
	</form>
</div>

			
<!-- Remove field dialog -->
<div id='bookacti-remove-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php esc_html_e( 'Remove this field from the form', 'booking-activities' ); ?>' >
	<form id='bookacti-remove-form-field-form' >
		<?php wp_nonce_field( 'bookacti_remove_form_field', 'nonce_remove_form_field', false ); ?>
		<input type='hidden' name='action' value='bookactiRemoveFormField' />
		<div><?php esc_html_e( 'Are you sure to delete this field permanently?', 'booking-activities' ); ?></div>
	</form>
</div>

			
<!-- Edit form meta dialog -->
<div id='bookacti-form-meta-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php esc_html_e( 'Form options', 'booking-activities' ); ?>' >
	<form id='bookacti-update-form-meta-form' >
		<input type='hidden' name='action' value='bookactiUpdateFormMeta' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form' ); ?>' />
		<input type='hidden' name='form_id' value='<?php $form_id ?>' />
		<?php
			do_action( 'bookacti_form_meta_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-meta-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-form-meta-id'><?php esc_html_e( 'ID', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'id',
					'id'	=> 'bookacti-form-meta-id',
					'value'	=> $form[ 'id' ],
					'tip'	=> esc_html__( 'Set the form CSS id. Leave this empty if you display this form multiple times on the same page.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-form-meta-class'><?php esc_html_e( 'Class', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'class',
					'id'	=> 'bookacti-form-meta-class',
					'value'	=> $form[ 'class' ],
					'tip'	=> esc_html__( 'Set the form CSS classes. Leave an empty space between each class.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-form-meta-redirect_url'><?php esc_html_e( 'Redirect URL', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'redirect_url',
					'id'	=> 'bookacti-form-meta-redirect_url',
					'value'	=> $form[ 'redirect_url' ],
					'tip'	=> esc_html__( 'Page URL where the customer will be redirected after submitting the booking form.', 'booking-activities' )
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
<div id='bookacti-form-field-dialog-calendar' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( esc_html__( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'calendar' ][ 'title' ] ) ); ?>' >
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
			array(	'label'			=> esc_html__( 'Filters', 'booking-activities' ),
					'id'			=> 'filters',
					'callback'		=> 'bookacti_fill_calendar_dialog_filters_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 10 ),
			array(	'label'			=> esc_html__( 'Actions', 'booking-activities' ),
					'id'			=> 'actions',
					'callback'		=> 'bookacti_fill_calendar_dialog_actions_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 20 ),
			array(	'label'			=> esc_html__( 'Display', 'booking-activities' ),
					'id'			=> 'display',
					'callback'		=> 'bookacti_fill_calendar_dialog_display_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 30 ),
			array(	'label'			=> esc_html__( 'Availability', 'booking-activities' ),
					'id'			=> 'availability',
					'callback'		=> 'bookacti_fill_calendar_dialog_availability_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 40 ),
			array(	'label'			=> esc_html__( 'Calendar', 'booking-activities' ),
					'id'			=> 'calendar',
					'callback'		=> 'bookacti_fill_calendar_dialog_calendar_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 50 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $calendar_tabs, 'calendar' );
		
		/**
		 * Display the content of the "Filters" tab of the "Calendar" dialog
		 * @since 1.5.0
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_filters_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_filters_tab_before', $params );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Event sources', 'booking-activities' ); ?></legend>
		<?php 
			$fields = bookacti_get_booking_system_fields_default_data( array( 'calendars', 'activities' ) );
			bookacti_display_fields( $fields );
		?>
		</fieldset>
		
		<fieldset>
			<legend><?php esc_html_e( 'Groups of events', 'booking-activities' ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'group_categories', 'groups_only', 'groups_single_events' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		
		<fieldset class='bookacti-hidden-field'>
			<legend><?php esc_html_e( 'Booked events', 'booking-activities' ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'bookings_only', 'status', 'user_id' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		<?php
			do_action( 'bookacti_calendar_dialog_filters_tab_after', $params );
		}
		
		/**
		 * Display the content of the "Actions" tab of the "Calendar" dialog
		 * @since 1.7.0
		 * @version 1.7.19
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_actions_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_actions_tab_before', $params );
		?>
		<div>
			<label for='bookacti-form_action'><?php esc_html_e( 'Form action', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'		=> 'select',
					'name'		=> 'form_action',
					'id'		=> 'bookacti-form_action',
					'options'	=> apply_filters( 'bookacti_form_action_options', array( 
						'default' => esc_html__( 'Default behavior', 'booking-activities' ),
						'redirect_to_url' => esc_html__( 'Redirect to a URL', 'booking-activities' )
					), $params ),
					'tip'		=> esc_html__( 'What action should this form perform?', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div class='bookacti-when-perform-form-action-container'>
			<label for='bookacti-when_perform_form_action'><?php esc_html_e( 'When to perform the action', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'		=> 'select',
					'name'		=> 'when_perform_form_action',
					'id'		=> 'bookacti-when_perform_form_action',
					'options'	=> apply_filters( 'bookacti_when_perform_form_action_options', array( 
						'on_submit' => esc_html__( 'When the form is submitted', 'booking-activities' ),
						'on_event_click' => esc_html__( 'When an event is clicked', 'booking-activities' )
					), $params ),
					'tip'		=> esc_html__( 'When do you want to perform the form action?', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div class='bookacti-activities-actions-options-table'>
			<h4><?php esc_html_e( 'Activities', 'booking-activities' ); ?></h4>
			<?php
				$activities_url_rows = array();
				$redirect_url_by_activity = ! empty( $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] ) && is_array( $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] ) ? $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] : array( 0 => '' );
				foreach( $redirect_url_by_activity as $activity_id => $redirect_url ) {
					$activities_url_rows[] = array( 
						'activity' => intval( $activity_id ),
						'redirect_url' => '<input type="text" name="redirect_url_by_activity[' . intval( $activity_id ) . ']" value="' . esc_url( $redirect_url ) . '" />'
					);
				}
				
				$activities_url_array = apply_filters( 'bookacti_activity_redirect_url_table', array(
					'head' => array( 
						'activity' => esc_html__( 'Activity', 'booking-activities' ),
						'redirect_url' => esc_html__( 'Redirect URL', 'booking-activities' )
					),
					'body' => $activities_url_rows,
				), $params );
				bookacti_display_table_from_array( $activities_url_array );
			?>
		</div>
		<div class='bookacti-group-categories-actions-options-table'>
			<h4><?php esc_html_e( 'Group categories', 'booking-activities' ); ?></h4>
			<?php 
				$group_categories_url_rows = array();
				$redirect_url_by_group_category = ! empty( $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] ) && is_array( $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] ) ? $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] : array( 0 => '' );
				foreach( $redirect_url_by_group_category as $group_category_id => $redirect_url ) {
					$group_categories_url_rows[] = array( 
						'group_category' => intval( $group_category_id ),
						'redirect_url' => '<input type="text" name="redirect_url_by_group_category[' . intval( $group_category_id ) . ']" value="' . esc_url( $redirect_url ) . '" />'
					);
				}
				
				$categories_url_array = apply_filters( 'bookacti_group_category_redirect_url_table', array(
					'head' => array( 
						'group_category' => esc_html__( 'Category', 'booking-activities' ),
						'redirect_url' => esc_html__( 'Redirect URL', 'booking-activities' )
					),
					'body' => $group_categories_url_rows,
				), $params );
				bookacti_display_table_from_array( $categories_url_array );
			?>
		</div>
		<?php
			do_action( 'bookacti_calendar_dialog_actions_tab_after', $params );
		}		
		
		/**
		 * Display the content of the "Display" tab of the "Calendar" dialog
		 * @since 1.5.0
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_display_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_display_tab_before', $params );
		?>
			<fieldset>
				<legend><?php esc_html_e( 'CSS selectors', 'booking-activities' ); ?></legend>
				<?php 
					$fields = bookacti_get_booking_system_fields_default_data( array( 'id', 'class' ) );
					bookacti_display_fields( $fields );
				?>
			</fieldset>
		<?php 
			do_action( 'bookacti_calendar_dialog_display_tab_after', $params );
		} 
		
		/**
		 * Display the content of the "Availability" tab of the "Calendar" dialog
		 * @since 1.5.0
		 * @version 1.8.0
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_availability_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_availability_tab_before', $params );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Availability period', 'booking-activities' ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'availability_period_start', 'availability_period_end', 'start', 'end', 'trim' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		
		<fieldset>
			<legend><?php esc_html_e( 'Past events', 'booking-activities' ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'past_events', 'past_events_bookable' ) );
				bookacti_display_fields( $fields, array( 'hidden' => array( 'past_events_bookable' ) ) );
			?>
		</fieldset>
		<?php 
			do_action( 'bookacti_calendar_dialog_availability_tab_after', $params );
		} 
		
		/**
		 * Display the content of the "Calendar" tab of the "Calendar" dialog
		 * @since 1.5.0
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_calendar_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_calendar_tab_before', $params );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Working time', 'booking-activities' ); ?></legend>
			<?php 
				$fields = bookacti_get_calendar_fields_default_data( array( 'minTime', 'maxTime' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		<?php 
			do_action( 'bookacti_calendar_dialog_calendar_tab_after', $params );
		} 
		?>
		<div class='bookacti-hidden-field'>
			<?php bookacti_display_badp_promo(); ?>
		</div>
		<div class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' 
			 data-show-title='<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>'
			 data-hide-title='<?php esc_html_e( 'Hide advanced options', 'booking-activities' ); ?>'>
			<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>
	   </div>
	</form>
</div>


<!-- Login field dialog -->
<div id='bookacti-form-field-dialog-login' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( esc_html__( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'login' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-login' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		
		<div id='bookacti-form-field-dialog-login-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		//Fill the array of tabs with their label, callback for content and display order
		$login_tabs = apply_filters( 'bookacti_form_field_login_dialog_tabs', array (
			array(	'label'			=> esc_html__( 'Fields', 'booking-activities' ),
					'id'			=> 'fields',
					'callback'		=> 'bookacti_fill_login_dialog_fields_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 10 ),
			array(	'label'			=> esc_html__( 'Login', 'booking-activities' ),
					'id'			=> 'login',
					'callback'		=> 'bookacti_fill_login_dialog_login_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 20 ),
			array(	'label'			=> esc_html__( 'Registration', 'booking-activities' ),
					'id'			=> 'register',
					'callback'		=> 'bookacti_fill_login_dialog_register_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 30 ),
			array(	'label'			=> esc_html__( 'No account', 'booking-activities' ),
					'id'			=> 'no_account',
					'callback'		=> 'bookacti_fill_login_dialog_no_account_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 40 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $login_tabs, 'login' );
		
		
		/**
		 * Display the content of the "Fields" tab of the "Login" dialog
		 * @since 1.6.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_fields_tab( $params ) {
			do_action( 'bookacti_login_dialog_fields_tab_before', $params );
		?>
			<fieldset>
				<legend><?php _e( 'Email address', 'booking-activities' ); ?></legend>
				<div>
					<label for='bookacti-email-label'><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[email]',
							'id'	=> 'bookacti-email-label',
							'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field'>
					<label for='bookacti-email-placeholder'><?php esc_html_e( 'Placeholder', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[email]',
							'id'	=> 'bookacti-email-placeholder',
							'tip'	=> esc_html__( 'Text displayed in transparency in the field when it is empty.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field'>
					<label for='bookacti-email-tip'><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[email]',
							'id'	=> 'bookacti-email-tip',
							'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php esc_html_e( 'Password', 'booking-activities' ); ?></legend>
				<div>
					<label for='bookacti-password-label'><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[password]',
							'id'	=> 'bookacti-password-label',
							'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field' >
					<label for='bookacti-password-placeholder'><?php esc_html_e( 'Placeholder', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'placeholder[password]',
							'id'	=> 'bookacti-password-placeholder',
							'tip'	=> esc_html__( 'Text displayed in transparency in the field when it is empty.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field' >
					<label for='bookacti-password-tip'><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[password]',
							'id'	=> 'bookacti-password-tip',
							'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
		<?php 
			$register_fields = apply_filters( 'bookacti_login_dialog_register_fields', bookacti_get_register_fields_default_data(), $params );
			foreach( $register_fields as $register_field_name => $register_field ) {
		?>		
				<fieldset>
					<legend><?php echo esc_html( $register_field[ 'label' ] ); ?></legend>
					<?php
						$sub_fields = array(
							'displayed_fields' => array(
								'type'	=> 'checkbox',
								'name'	=> 'displayed_fields[' . $register_field_name . ']',
								'id'	=> 'bookacti-displayed_fields-' . $register_field_name,
								'value'	=> 0,
								'title'	=> esc_html__( 'Displayed', 'booking-activities' ),
								'tip'	=> esc_html__( 'Whether this field is displayed in the form.', 'booking-activities' )
							),
							'label' => array(
								'type'	=> 'text',
								'name'	=> 'label[' . $register_field_name . ']',
								'id'	=> 'bookacti-label-' . $register_field_name,
								'title'	=> esc_html__( 'Label', 'booking-activities' ),
								'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
							),
							'placeholder' => array(
								'type'	=> 'text',
								'name'	=> 'placeholder[' . $register_field_name . ']',
								'id'	=> 'bookacti-placeholder-' . $register_field_name,
								'title'	=> esc_html__( 'Placeholder', 'booking-activities' ),
								'tip'	=> esc_html__( 'Text displayed in transparency in the field when it is empty.', 'booking-activities' )
							),
							'tip' => array(
								'type'	=> 'text',
								'name'	=> 'tip[' . $register_field_name . ']',
								'id'	=> 'bookacti-tip-' . $register_field_name,
								'title'	=> esc_html__( 'Tooltip', 'booking-activities' ),
								'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
							),
							'required_fields' => array(
								'type'	=> 'checkbox',
								'name'	=> 'required_fields[' . $register_field_name . ']',
								'id'	=> 'bookacti-required_fields-' . $register_field_name,
								'value'	=> 0,
								'title'	=> esc_html__( 'Required', 'booking-activities' ),
								'tip'	=> esc_html__( 'Whether this field is compulsory.', 'booking-activities' )
							)
						);
						
						$field_options = apply_filters( 'bookacti_login_dialog_register_field_fields', array(
							'fields' => $sub_fields,
							'param' => array( 'hidden' => array( 'placeholder', 'tip', 'required_fields' ) )
						), $register_field, $register_field_name );

						bookacti_display_fields( $field_options[ 'fields' ], $field_options[ 'param' ] );
					?>
				</fieldset>
			<?php
			}
			do_action( 'bookacti_login_dialog_fields_tab_after', $params );
		}
		
		
		/**
		 * Get the login types edit fields HTML
		 * @since 1.6.0
		 * @param array $keys
		 * @return string
		 */
		function bookacti_display_login_type_fields( $keys ) {
			$login_types = bookacti_get_login_type_field_default_options( $keys );
			if( ! $login_types ) { return ''; }
			
			foreach( $login_types as $login_type_name => $login_type ) {
			?>
				<fieldset>
					<legend>
					<?php 
						$login_type_title = ! empty( $login_type[ 'title' ] ) ? $login_type[ 'title' ] : $login_type_name;
						/* translators: %s is the login option short title (e.g.: "New Account") */
						echo sprintf( esc_html__( 'Login type: %s', 'booking-activities' ), $login_type_title ); 
					?>
					</legend>
					<div>
						<label for='bookacti-displayed_fields-<?php echo $login_type_name; ?>'><?php esc_html_e( 'Allowed', 'booking-activities' ); ?></label>
						<?php 
							$args = array(
								'type'	=> 'checkbox',
								'name'	=> 'displayed_fields[' . $login_type_name . ']',
								'id'	=> 'bookacti-displayed_fields-' . $login_type_name,
								'value'	=> 1,
								'tip'	=> esc_html__( 'Whether to allow this login type. If only one login type is allowed, it will be selected by default and the field will be hidden.', 'booking-activities' )
							);
							bookacti_display_field( $args );
						?>
					</div>
					<div>
						<label for='bookacti-new_account-label'><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
						<?php 
							$args = array(
								'type'	=> 'text',
								'name'	=> 'label[' . $login_type_name . ']',
								'id'	=> 'bookacti-label-' . $login_type_name,
								'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
							);
							bookacti_display_field( $args );
						?>
					</div>
					<div class='bookacti-hidden-field' >
						<label for='bookacti-new_account-tip'><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></label>
						<?php 
							$args = array(
								'type'	=> 'text',
								'name'	=> 'tip[' . $login_type_name . ']',
								'id'	=> 'bookacti-tip-' . $login_type_name,
								'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
							);
							bookacti_display_field( $args );
						?>
					</div>
					<?php do_action( 'bookacti_' . $login_type_name . '_login_type_fields', $login_type ); ?>
				</fieldset>
			<?php
			}
		}
		
		
		/**
		 * Display the content of the "Login" tab of the "Login" dialog
		 * @since 1.5.0
		 * @version 1.6.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_login_tab( $params ) {
			do_action( 'bookacti_login_dialog_login_tab_before', $params );
			
			bookacti_display_login_type_fields( array( 'my_account' ) );
		?>
			<fieldset>
				<legend><?php esc_html_e( 'Forgotten password', 'booking-activities' ); ?></legend>
				<div>
					<label for='bookacti-displayed_fields-forgotten_password'><?php esc_html_e( 'Displayed', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'displayed_fields[forgotten_password]',
							'id'	=> 'bookacti-displayed_fields-forgotten_password',
							'value'	=> 0,
							'title'	=> esc_html__( 'Displayed', 'booking-activities' ),
							'tip'	=> esc_html__( 'Whether this field is displayed in the form.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div>
					<label for='bookacti-forgotten_password-label'><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[forgotten_password]',
							'id'	=> 'bookacti-forgotten_password-label',
							'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field' >
					<label for='bookacti-forgotten_password-tip'><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[forgotten_password]',
							'id'	=> 'bookacti-forgotten_password-tip',
							'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
			<div>
				<label for='bookacti-password-required'><?php esc_html_e( 'Password required', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'required_fields[password]',
						'id'	=> 'bookacti-required_fields-password',
						'value'	=> 1,
						'tip'	=> esc_html__( 'Disable this option to allow your customers to book without password authentication. They will simply have to give their e-mail address for the reservation to be made on their account. Becareful, anyone will be able to book on someone else\'s behalf with his email address only.', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-automatic-login'><?php esc_html_e( 'Automatic login', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'automatic_login',
						'id'	=> 'bookacti-automatic-login',
						'value'	=> 1,
						'tip'	=> esc_html__( 'Whether to automatically log the customer into his account after making a reservation.', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
		<?php 
			do_action( 'bookacti_login_dialog_login_tab_after', $params );
		}
		
		
		/**
		 * Display the content of the "Register" tab of the "Login" dialog
		 * @since 1.5.0
		 * @version 1.6.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_register_tab( $params ) {
			do_action( 'bookacti_login_dialog_register_tab_before', $params );
			
			bookacti_display_login_type_fields( array( 'new_account' ) );
			?>
			<div>
				<label for='bookacti-generate-password'><?php esc_html_e( 'Generate Password', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'generate_password',
						'id'	=> 'bookacti-generate-password',
						'value'	=> 0,
						'tip'	=> esc_html__( 'Whether to automatically generate the password.', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div class='bookacti-hidden-field'>
				<label for='bookacti-min_password_strength'><?php esc_html_e( 'Min. password strength', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'		=> 'select',
						'name'		=> 'min_password_strength',
						'id'		=> 'bookacti-min_password_strength',
						'options'	=> array(
											1 => esc_html_x( 'Very weak', 'password strength' ),
											2 => esc_html_x( 'Weak', 'password strength' ),
											3 => esc_html_x( 'Medium', 'password strength' ),
											4 => esc_html_x( 'Strong', 'password strength' )
										),
						'value'		=> 1,
						'tip'		=> esc_html__( 'How strong the user password must be if it is not generated?', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-send-new-account-email'><?php esc_html_e( 'Send new account email', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'send_new_account_email',
						'id'	=> 'bookacti-send-new-account-email',
						'value'	=> 0,
						'tip'	=> esc_html__( 'Whether to automatically send an email to the user if he has created an account with the booking form.', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div class='bookacti-hidden-field'>
				<label for='bookacti-new-user-role'>
					<?php /* translators: Option name corresponding to this description: Choose a role to give to a user who has registered while booking an event with this form.  */ 
					esc_html_e( 'New user role', 'booking-activities' ); ?>
				</label>
				<?php 
					// Get roles options
					$roles = get_editable_roles();
					$roles_options = array();
					foreach( $roles as $role_id => $role ) { $roles_options[ $role_id ] = $role[ 'name' ]; }

					$args = array(
						'type'		=> 'select',
						'name'		=> 'new_user_role',
						'id'		=> 'bookacti-new-user-role',
						'options'	=> $roles_options,
						'value'		=> 'subscriber',
						'tip'		=> esc_html__( 'Choose a role to give to a user who has registered while booking an event with this form.', 'booking-activities' )
					);
					bookacti_display_field( $args );
				?>
			</div>
			
			<?php
			do_action( 'bookacti_login_dialog_register_tab_after', $params );
		}
		
		
		/**
		 * Display the content of the "No account" tab of the "Login" dialog
		 * @since 1.6.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_no_account_tab( $params ) {
			do_action( 'bookacti_login_dialog_no_account_tab_before', $params );
			
			bookacti_display_login_type_fields( array( 'no_account' ) );
			
			do_action( 'bookacti_login_dialog_no_account_tab_after', $params );
		}
		
		?>
		<div class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' 
			 data-show-title='<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>'
			 data-hide-title='<?php esc_html_e( 'Hide advanced options', 'booking-activities' ); ?>'>
			<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>
	   </div>
	</form>
</div>


<!-- Quantity field dialog -->
<div id='bookacti-form-field-dialog-quantity' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( esc_html__( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'quantity' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-quantity' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_quantity_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-field-dialog-quantity-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-quantity-label'><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'label',
					'id'	=> 'bookacti-quantity-label',
					'tip'	=> esc_html__( 'Text displayed before the field.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-quantity-placeholder'><?php esc_html_e( 'Placeholder', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'placeholder',
					'id'	=> 'bookacti-quantity-placeholder',
					'tip'	=> esc_html__( 'Text displayed in transparency in the field when it is empty.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-quantity-tip'><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'tip',
					'id'	=> 'bookacti-quantity-tip',
					'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', 'booking-activities' )
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
			esc_html_e( 'Min and Max values are dynamically set according to the selected event and its availability settings.', 'booking-activities' );
		?>
		</p>
	</form>
</div>


<!-- Submit button dialog -->
<div id='bookacti-form-field-dialog-submit' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'submit' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-submit' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_submit_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-field-dialog-submit-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-submit-value'><?php esc_html_e( 'Button text', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'value',
					'id'	=> 'bookacti-submit-value',
					'tip'	=> esc_html__( 'Text displayed on the button.', 'booking-activities' )
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
<div id='bookacti-form-field-dialog-free_text' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( esc_html__( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'free_text' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-free_text' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_free_text_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-field-dialog-free_text-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-free_text-title'><?php esc_html_e( 'Title', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'title',
					'id'	=> 'bookacti-free_text-title',
					'tip'	=> esc_html__( 'Field title displayed in form editor only.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-free_text-value' class='bookacti-fullwidth-label' ><?php esc_html_e( 'Free text', 'booking-activities' ); ?></label>
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


<!-- Terms field dialog -->
<div id='bookacti-form-field-dialog-terms' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( esc_html__( '%s options', 'booking-activities' ), strip_tags( $fields_data[ 'terms' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-terms' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_terms_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-field-dialog-terms-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-terms-value'><?php esc_html_e( 'Checked by default', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'value',
					'id'	=> 'bookacti-terms-value',
					'tip'	=> esc_html__( 'Whether the checkbox should be checked by default.', 'booking-activities' )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-terms-label' class='bookacti-fullwidth-label' ><?php esc_html_e( 'Label', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'editor',
					'name'	=> 'label',
					'id'	=> 'bookacti-terms-label',
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_terms_dialog_after', $form, $form_fields );
		?>
	</form>
</div>

<!-- Export for events dialog -->
<div id='bookacti-export-events-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php esc_html_e( 'Export events from this calendar', 'booking-activities' ); ?>' >
	<form id='bookacti-export-events-form' >
		<?php wp_nonce_field( 'bookacti_reset_export_events_url', 'nonce_reset_export_events_url', false ); ?>
		<input type='hidden' name='action' value='' />
		<?php
			$lang = bookacti_get_current_lang_code();
			$secret_key = bookacti_get_metadata( 'form', $form_id, 'secret_key', true );
			if( ! $secret_key ) {
				$secret_key = md5( microtime().rand() );
				bookacti_update_metadata( 'form', $form_id, array( 'secret_key' => $secret_key ) );
			}
			
			$ical_url = esc_url( home_url( '?action=bookacti_export_form_events&filename=booking-activities-events-form-' . $form_id . '&form_id=' . $form_id . '&key=' . $secret_key . '&past_events=auto&lang=' . $lang ) );
		?>
		<div>
			<p><strong><?php esc_html_e( 'Secret address in iCal format', 'booking-activities' ); ?></strong></p>
			<div class='bookacti_export_url'>
				<div class='bookacti_export_url_field' ><input type='text' id='bookacti_export_events_url_secret' data-value='<?php echo $ical_url; ?>' value='<?php echo $ical_url; ?>' readonly onfocus='this.select();' /></div>
				<div class='bookacti_export_button' ><input type='button' value='<?php esc_html( _ex( 'Export', 'action', 'booking-activities' ) ); ?>' /></div>
			</div>
			<p>
				<small>
					<?php esc_html_e( 'Use this address to synchronize the events of this calendar on other applications without making it public.', 'booking-activities' ); ?>
				</small>
			</p>
			<p class='bookacti-warning'>
				<span class='dashicons dashicons-warning' ></span>
				<small>
					<?php 
						esc_html_e( 'This link provides real-time data. However, some apps may synchronize only every 24h, or more.', 'booking-activities' ); 
						echo ' ';
					?>
					<strong>
					<?php
						esc_html_e( 'That\'s why your changes won\'t be applied in real time on your synched apps.', 'booking-activities' ); 
					?>
					</strong>
				</small>
			</p>
			<p class='bookacti-warning'>
				<span class='dashicons dashicons-warning' ></span>
				<small>
					<?php 
						esc_html_e( 'Only share this address with those you trust to see all your events details.', 'booking-activities' );
						echo ' ';
						esc_html_e( 'You can reset your secret key with the "Reset" button below. This will nullify the previously generated export links.', 'booking-activities' );
					?>
				</small>
			</p>
		</div>
	</form>
</div>

<?php
do_action( 'bookacti_form_editor_dialogs', $form, $form_fields );