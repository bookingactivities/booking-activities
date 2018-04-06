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


<!-- Login field dialog -->
<div id='bookacti-form-field-dialog-login' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' title='<?php /* translators: Title of the Update field dialog. %s is the field title. */ echo sprintf( __( '%s options', BOOKACTI_PLUGIN_NAME ), strip_tags( $fields_data[ 'login' ][ 'title' ] ) ); ?>' >
	<form id='bookacti-form-field-form-login' >
		<input type='hidden' name='action' value='bookactiUpdateFormField' />
		<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_form_field' ); ?>' />
		<input type='hidden' name='field_id' value='' />
		<?php 
			do_action( 'bookacti_login_dialog_before', $form );
		?>
		<div>
			<label for='bookacti-email-label'><?php _e( 'Email label', BOOKACTI_PLUGIN_NAME ); ?></label>
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
		<div>
			<label for='bookacti-email-placeholder'><?php _e( 'Email placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
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
		<div>
			<label for='bookacti-email-tip'><?php _e( 'Email tip', BOOKACTI_PLUGIN_NAME ); ?></label>
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
		<div>
			<label for='bookacti-password-label'><?php _e( 'Password label', BOOKACTI_PLUGIN_NAME ); ?></label>
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
		<div>
			<label for='bookacti-password-placeholder'><?php _e( 'Password placeholder', BOOKACTI_PLUGIN_NAME ); ?></label>
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
		<div>
			<label for='bookacti-password-tip'><?php _e( 'Password tip', BOOKACTI_PLUGIN_NAME ); ?></label>
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
			do_action( 'bookacti_login_dialog_after', $form );
		?>
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
			<label for='bookacti-quantity-tip'><?php _e( 'Tip', BOOKACTI_PLUGIN_NAME ); ?></label>
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