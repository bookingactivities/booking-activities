<?php
/**
 * Form editor dialogs
 * @since 1.5.0
 * @version 1.5.2
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
		<div id='bookacti-form-meta-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-form-meta-id'><?php _e( 'ID', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'id',
					'id'	=> 'bookacti-form-meta-id',
					'value'	=> $form[ 'id' ],
					'tip'	=> __( 'Set the form CSS id. Leave this empty if you display this form multiple times on the same page.', BOOKACTI_PLUGIN_NAME )
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
					'tip'	=> __( 'Set the form CSS classes. Leave an empty space between each class.', BOOKACTI_PLUGIN_NAME )
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
					'order'			=> 10 ),
			array(	'label'			=> __( 'Display', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'display',
					'callback'		=> 'bookacti_fill_calendar_dialog_display_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 20 ),
			array(	'label'			=> __( 'Availability', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'availability',
					'callback'		=> 'bookacti_fill_calendar_dialog_availability_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 30 ),
			array(	'label'			=> __( 'Calendar', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'calendar',
					'callback'		=> 'bookacti_fill_calendar_dialog_calendar_tab',
					'parameters'	=> array( 'form' => $form, 'calendar_data' => $form_fields[ $calendar_field_id ], 'fields_data' => $fields_data ),
					'order'			=> 40 )
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
			<legend><?php _e( 'Event sources', BOOKACTI_PLUGIN_NAME ); ?></legend>
		<?php 
			$fields = bookacti_get_booking_system_fields_default_data( array( 'calendars', 'activities' ) );
			bookacti_display_fields( $fields );
		?>
		</fieldset>
		
		<fieldset>
			<legend><?php _e( 'Groups of events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'group_categories', 'groups_only', 'groups_single_events' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		
		<fieldset class='bookacti-hidden-field'>
			<legend><?php _e( 'Booked events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'bookings_only', 'status', 'user_id' ) );
				bookacti_display_fields( $fields );
			?>
		</fieldset>
		<?php
			do_action( 'bookacti_calendar_dialog_filters_tab_after', $params );
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
				<legend><?php _e( 'CSS selectors', BOOKACTI_PLUGIN_NAME ); ?></legend>
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
		 * @param array $params
		 */
		function bookacti_fill_calendar_dialog_availability_tab( $params ) {
			do_action( 'bookacti_calendar_dialog_availability_tab_before', $params );
		?>
		<fieldset>
			<legend><?php _e( 'Availability period', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'availability_period_start', 'availability_period_end', 'start', 'end' ) );
				bookacti_display_fields( $fields, array( 'hidden' => array( 'start', 'end' ) ) );
			?>
		</fieldset>
		
		<fieldset class='bookacti-hidden-field'>
			<legend><?php _e( 'Past events', BOOKACTI_PLUGIN_NAME ); ?></legend>
			<?php 
				$fields = bookacti_get_booking_system_fields_default_data( array( 'past_events', 'past_events_bookable' ) );
				bookacti_display_fields( $fields );
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
			<legend><?php _e( 'Working time', BOOKACTI_PLUGIN_NAME ); ?></legend>
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
			array(	'label'			=> __( 'Options', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'options',
					'callback'		=> 'bookacti_fill_login_dialog_options_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $form_fields, 'fields_data' => $fields_data ),
					'order'			=> 30 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $login_tabs, 'login' );
		
		/**
		 * Display the content of the "Login" tab of the "Login" dialog
		 * @since 1.5.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_login_tab( $params ) {
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
				<div class='bookacti-hidden-field'>
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
				<div class='bookacti-hidden-field'>
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
				<div class='bookacti-hidden-field' >
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
				<div class='bookacti-hidden-field' >
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
			<fieldset>
				<legend><?php _e( 'Forgotten password', BOOKACTI_PLUGIN_NAME ); ?></legend>
				<div>
					<label for='bookacti-forgotten_password-label'><?php _e( 'Displayed', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'checkbox',
							'name'	=> 'displayed_fields[forgotten_password]',
							'id'	=> 'bookacti-displayed_fields-forgotten_password',
							'value'	=> 0,
							'title'	=> esc_html__( 'Displayed', BOOKACTI_PLUGIN_NAME ),
							'tip'	=> esc_html__( 'Whether this field is displayed in the form.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div>
					<label for='bookacti-forgotten_password-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'label[forgotten_password]',
							'id'	=> 'bookacti-forgotten_password-label',
							'tip'	=> __( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
				<div class='bookacti-hidden-field' >
					<label for='bookacti-forgotten_password-tip'><?php _e( 'Tooltip', BOOKACTI_PLUGIN_NAME ); ?></label>
					<?php 
						$args = array(
							'type'	=> 'text',
							'name'	=> 'tip[forgotten_password]',
							'id'	=> 'bookacti-forgotten_password-tip',
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
		 * @since 1.5.0
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_register_tab( $params ) {
			do_action( 'bookacti_login_dialog_register_tab_before', $params );
			
			$new_account_field = array(
				'new_account' => array(
					'name'			=> 'new_account', 
					'type'			=> 'text', 
					'label'			=> esc_html__( 'New account', BOOKACTI_PLUGIN_NAME ), 
					'placeholder'	=> '', 
					'tip'			=> '', 
					'required'		=> 0, 
					'displayed'		=> 1
				)
			);
			$register_fields = apply_filters( 'bookacti_login_dialog_register_fields', array_merge( $new_account_field, bookacti_get_register_fields_default_data() ), $params );
			
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
								'title'	=> esc_html__( 'Displayed', BOOKACTI_PLUGIN_NAME ),
								'tip'	=> esc_html__( 'Whether this field is displayed in the form.', BOOKACTI_PLUGIN_NAME )
							),
							'label' => array(
								'type'	=> 'text',
								'name'	=> 'label[' . $register_field_name . ']',
								'id'	=> 'bookacti-label-' . $register_field_name,
								'title'	=> esc_html__( 'Label', BOOKACTI_PLUGIN_NAME ),
								'tip'	=> esc_html__( 'Text displayed before the field.', BOOKACTI_PLUGIN_NAME )
							),
							'placeholder' => array(
								'type'	=> 'text',
								'name'	=> 'placeholder[' . $register_field_name . ']',
								'id'	=> 'bookacti-placeholder-' . $register_field_name,
								'title'	=> esc_html__( 'Placeholder', BOOKACTI_PLUGIN_NAME ),
								'tip'	=> esc_html__( 'Text displayed in transparency in the field when it is empty.', BOOKACTI_PLUGIN_NAME )
							),
							'tip' => array(
								'type'	=> 'text',
								'name'	=> 'tip[' . $register_field_name . ']',
								'id'	=> 'bookacti-tip-' . $register_field_name,
								'title'	=> esc_html__( 'Tooltip', BOOKACTI_PLUGIN_NAME ),
								'tip'	=> esc_html__( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
							),
							'required_fields' => array(
								'type'	=> 'checkbox',
								'name'	=> 'required_fields[' . $register_field_name . ']',
								'id'	=> 'bookacti-required_fields-' . $register_field_name,
								'value'	=> 0,
								'title'	=> esc_html__( 'Required', BOOKACTI_PLUGIN_NAME ),
								'tip'	=> esc_html__( 'Whether this field is compulsory.', BOOKACTI_PLUGIN_NAME )
							)
						);
						
						if( $register_field_name === 'new_account' ) {
							unset( $sub_fields[ 'placeholder' ] );
							unset( $sub_fields[ 'required_fields' ] );
						}
						
						$field_options = apply_filters( 'bookacti_login_dialog_register_field_fields', array(
							'fields' => $sub_fields,
							'param' => array( 'hidden' => array( 'placeholder', 'tip', 'required_fields' ) )
						), $register_field, $register_field_name );
						
						bookacti_display_fields( $field_options[ 'fields' ], $field_options[ 'param' ] );
					?>
				</fieldset>
			<?php 
			}
			
			do_action( 'bookacti_login_dialog_register_tab_after', $params );
		}
		
		
		/**
		 * Display the content of the "Options" tab of the "Login" dialog
		 * @since 1.5.0
		 * @version 1.5.1
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_options_tab( $params ) {
			do_action( 'bookacti_login_dialog_options_tab_before', $params );
		?>
			<div>
				<label for='bookacti-automatic-login'><?php _e( 'Automatic login', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'automatic_login',
						'id'	=> 'bookacti-automatic-login',
						'value'	=> 1,
						'tip'	=> __( 'Whether to automatically log the customer into his account after making a reservation.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-password-required'><?php _e( 'Password required', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'required_fields[password]',
						'id'	=> 'bookacti-required_fields-password',
						'value'	=> 1,
						'tip'	=> __( 'Disable this option to allow your customers to book without password authentication. They will simply have to give their e-mail address for the reservation to be made on their account. Becareful, anyone will be able to book on someone else\'s behalf with his email address only.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-generate-password'><?php _e( 'Generate Password', BOOKACTI_PLUGIN_NAME ); ?></label>
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
				<label for='bookacti-min_password_strength'><?php _e( 'Min. password strength', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'		=> 'select',
						'name'		=> 'min_password_strength',
						'id'		=> 'bookacti-min_password_strength',
						'options'	=> array(
											1 => _x( 'Very weak', 'password strength' ),
											2 => _x( 'Weak', 'password strength' ),
											3 => _x( 'Medium', 'password strength' ),
											4 => _x( 'Strong', 'password strength' )
										),
						'value'		=> 1,
						'tip'		=> __( 'How strong the user password must be if it is not generated?', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
			<div>
				<label for='bookacti-send-new-account-email'><?php _e( 'Send new account email', BOOKACTI_PLUGIN_NAME ); ?></label>
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
			<div>
				<label for='bookacti-new-user-role'>
					<?php /* translators: Option name corresponding to this description: Choose a role to give to a user who has registered while booking an event with this form.  */ 
					_e( 'New user role', BOOKACTI_PLUGIN_NAME ); ?>
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
						'tip'		=> __( 'Choose a role to give to a user who has registered while booking an event with this form.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
		<?php
			do_action( 'bookacti_login_dialog_options_tab_after', $params );
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
		<div id='bookacti-form-field-dialog-quantity-lang-switcher' class='bookacti-lang-switcher' ></div>
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
		<div id='bookacti-form-field-dialog-submit-lang-switcher' class='bookacti-lang-switcher' ></div>
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
		<div id='bookacti-form-field-dialog-free_text-lang-switcher' class='bookacti-lang-switcher' ></div>
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


<!-- Terms field dialog -->
<div id='bookacti-form-field-dialog-terms' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'terms' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-terms' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_terms_dialog_before', $form, $form_fields );
		?>
		<div id='bookacti-form-field-dialog-terms-lang-switcher' class='bookacti-lang-switcher' ></div>
		<div>
			<label for='bookacti-terms-value'><?php _e( 'Checked by default', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'value',
					'id'	=> 'bookacti-terms-value',
					'tip'	=> __( 'Whether the checkbox should be checked by default.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<div>
			<label for='bookacti-terms-label' class='bookacti-fullwidth-label' ><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
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


<?php
do_action( 'bookacti_form_editor_dialogs', $form, $form_fields );