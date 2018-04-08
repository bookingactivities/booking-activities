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
			do_action( 'bookacti_insert_form_field_dialog_before', $form );
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
			do_action( 'bookacti_insert_form_field_dialog_after', $form );
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
			do_action( 'bookacti_form_meta_dialog_before', $form );
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
			do_action( 'bookacti_form_meta_dialog_after', $form );
		?>
	</form>
</div>


<!-- Login field dialog -->
<div id='bookacti-form-field-dialog-login' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'login' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-login' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		
		<div id='bookacti-event-dialog-lang-switcher' class='bookacti-lang-switcher' ></div>
		
		<?php
		//Fill the array of tabs with their label, callback for content and display order
		$login_tabs = apply_filters( 'bookacti_login_dialog_tabs', array (
			array(	'label'			=> __( 'Login', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'login',
					'callback'		=> 'bookacti_fill_login_dialog_login_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $fields_data ),
					'order'			=> 10 ),
			array(	'label'			=> __( 'Register', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'register',
					'callback'		=> 'bookacti_fill_login_dialog_register_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $fields_data ),
					'order'			=> 20 ),
			array(	'label'			=> __( 'User data', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'user_meta',
					'callback'		=> 'bookacti_fill_login_dialog_user_meta_tab',
					'parameters'	=> array( 'form' => $form, 'fields' => $fields_data ),
					'order'			=> 20 )
		) );
		
		// Display tabs
		bookacti_display_tabs( $login_tabs, 'login' );
		
		/**
		 * Display the content of the "Login" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_login_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields_data	= $params[ 'fields' ];
			do_action( 'bookacti_login_dialog_login_tab_before', $form );
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
							'value'	=> $fields_data[ 'login' ][ 'label' ][ 'email' ],
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
							'value'	=> $fields_data[ 'login' ][ 'placeholder' ][ 'email' ],
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
							'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'email' ],
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
							'value'	=> $fields_data[ 'login' ][ 'label' ][ 'password' ],
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
							'value'	=> $fields_data[ 'login' ][ 'placeholder' ][ 'password' ],
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
							'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'password' ],
							'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
		<?php 
			do_action( 'bookacti_login_dialog_login_tab_after', $form );
		}
		
		
		/**
		 * Display the content of the "Register" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_register_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields_data	= $params[ 'fields' ];
			do_action( 'bookacti_login_dialog_register_tab_before', $form );
		?>
			<div>
				<label for='bookacti-displayed_fields-register'><?php _e( 'Allow users to create an account?', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php 
					$args = array(
						'type'	=> 'checkbox',
						'name'	=> 'displayed_fields[register]',
						'id'	=> 'bookacti-displayed_fields-register',
						'value'	=> $fields_data[ 'login' ][ 'displayed_fields' ][ 'register' ],
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
						'value'	=> $fields_data[ 'login' ][ 'label' ][ 'register' ],
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
						'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'register' ],
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
						'value'	=> $fields_data[ 'login' ][ 'generate_password' ],
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
						'value'	=> $fields_data[ 'login' ][ 'send_new_account_email' ],
						'tip'	=> __( 'Whether to automatically send an email to the user if he has created an account with the booking form.', BOOKACTI_PLUGIN_NAME )
					);
					bookacti_display_field( $args );
				?>
			</div>
		<?php
			do_action( 'bookacti_login_dialog_register_tab_after', $form );
		}
		
		
		/**
		 * Display the content of the "User meta" tab of the "Login" dialog
		 * @param array $params
		 */
		function bookacti_fill_login_dialog_user_meta_tab( $params ) {
			$form			= $params[ 'form' ];
			$fields_data	= $params[ 'fields' ];
			do_action( 'bookacti_login_dialog_user_meta_tab_before', $form );
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
							'value'	=> $fields_data[ 'login' ][ 'displayed_fields' ][ 'first_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'label' ][ 'first_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'placeholder' ][ 'first_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'first_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'required_fields' ][ 'first_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'displayed_fields' ][ 'last_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'label' ][ 'last_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'placeholder' ][ 'last_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'last_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'required_fields' ][ 'last_name' ],
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
							'value'	=> $fields_data[ 'login' ][ 'displayed_fields' ][ 'phone' ],
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
							'value'	=> $fields_data[ 'login' ][ 'label' ][ 'phone' ],
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
							'value'	=> $fields_data[ 'login' ][ 'placeholder' ][ 'phone' ],
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
							'value'	=> $fields_data[ 'login' ][ 'tip' ][ 'phone' ],
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
							'value'	=> $fields_data[ 'login' ][ 'required_fields' ][ 'phone' ],
							'tip'	=> __( 'Whether this field is compulsory.', BOOKACTI_PLUGIN_NAME )
						);
						bookacti_display_field( $args );
					?>
				</div>
			</fieldset>
		<?php
			do_action( 'bookacti_login_dialog_user_meta_tab_after', $form );
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
			do_action( 'bookacti_quantity_dialog_before', $form );
		?>
		<div>
			<label for='bookacti-quantity-label'><?php _e( 'Label', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'label',
					'id'	=> 'bookacti-quantity-label',
					'value'	=> $fields_data[ 'quantity' ][ 'label' ],
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
					'value'	=> $fields_data[ 'quantity' ][ 'placeholder' ],
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
					'value'	=> $fields_data[ 'quantity' ][ 'tip' ],
					'tip'	=> __( 'Text displayed in the tooltip next to the field.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_quantity_dialog_after', $form );
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
			do_action( 'bookacti_submit_dialog_before', $form );
		?>
		<div>
			<label for='bookacti-submit-value'><?php _e( 'Button text', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'value',
					'id'	=> 'bookacti-submit-value',
					'value'	=> $fields_data[ 'submit' ][ 'value' ],
					'tip'	=> __( 'Text displayed on the button.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_submit_dialog_after', $form );
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
			do_action( 'bookacti_free_text_dialog_before', $form );
		?>
		<div>
			<label for='bookacti-free_text-title'><?php _e( 'Title', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'text',
					'name'	=> 'title',
					'id'	=> 'bookacti-free_text-title',
					'value'	=> $fields_data[ 'free_text' ][ 'title' ],
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
					'value'	=> $fields_data[ 'free_text' ][ 'value' ]
				);
				bookacti_display_field( $args );
			?>
		</div>
		<?php 
			do_action( 'bookacti_free_text_dialog_after', $form );
		?>
	</form>
</div>


<?php
do_action( 'bookacti_form_editor_dialogs', $form, $form_fields, $fields_data );