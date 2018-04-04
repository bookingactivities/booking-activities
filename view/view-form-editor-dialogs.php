<?php
/**
 * Form editor dialogs
 * @since 1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<!-- Add a new field dialog -->
<div id='bookacti-insert-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' >
	<form id='bookacti-insert-form-field-form' >
		<?php wp_nonce_field( 'bookacti_insert_form_field', 'nonce_insert_form_field', false ); ?>
		<input type='hidden' name='action' value='bookactiInsertFormField' />
		<p class='bookacti-dialog-intro'><?php _e( 'Pick the field to add to your form:', BOOKACTI_PLUGIN_NAME ); ?></p>
		<div>
			<label for='bookacti-field-to-insert'><?php _e( 'Field to insert', BOOKACTI_PLUGIN_NAME ); ?></label>
			<select name='field_to_insert' id='bookacti-field-to-insert' >
			<?php 
				// Get fields already added
				$field_already_added = array();
				foreach( $form_fields as $form_field ) { $field_already_added[] = $form_field[ 'name' ]; }

				// Display available fields options
				foreach( $fields_data as $field_data ) {
					// Add the field if it isn't already in the form, or if it is multiuse
					if( ! in_array( $field_data[ 'name' ], $field_already_added, true ) || empty( $field_data[ 'unique' ] ) ) {
						echo '<option value="' . $field_data[ 'name' ] . '" >' . $field_data[ 'title' ] . '</option>';
					}
				}
			?>
			</select>
		<div>
	</form>
</div>

<!-- Add a new field dialog -->
<div id='bookacti-remove-form-field-dialog' class='bookacti-backend-dialog bookacti-form-dialog' style='display:none;' >
	<form id='bookacti-remove-form-field-form' >
		<?php wp_nonce_field( 'bookacti_remove_form_field', 'nonce_remove_form_field', false ); ?>
		<input type='hidden' name='action' value='bookactiRemoveFormField' />
		<div><?php esc_html_e( 'Are you sure to delete this field permanently?', BOOKACTI_PLUGIN_NAME ); ?></div>
	</form>
</div>

<!-- Calendar data dialog -->
