<?php
/**
 * Form editor page
 * @since 1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( empty( $_REQUEST[ 'action' ] ) && ! empty( $_REQUEST[ 'form_id' ] ) ) {
	$_REQUEST[ 'action' ] = is_numeric( $_REQUEST[ 'form_id' ] ) ? 'edit' : 'new';
}

$form_id = ! empty( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'new' ? 'new' : intval( $_REQUEST[ 'form_id' ] );

if( ! $form_id ) { exit; }

// Create a new form
if( $form_id === 'new' ) {
	// Exit if not allowed to create a form
	$can_create_form = current_user_can( 'bookacti_create_forms' );
	if( ! $can_create_form ) { echo __( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Insert form
	$form_id = bookacti_insert_form( '', 'auto-draft', 0 );
	
	// Insert default form fields
	bookacti_insert_default_form_fields( $form_id );
	
	// Insert default form managers
	$form_managers = bookacti_format_form_managers();
	bookacti_update_managers( 'form', $form_id, $form_managers );
	
	// Change current url to the edit url
	$form_url = is_multisite() ? network_admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id ) : admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id );
	header( 'Location: ' . $form_url );
}

// Exit if not allowed to edit current form
$can_manage_form	= bookacti_user_can_manage_form( $form_id );
$can_edit_form		= current_user_can( 'bookacti_edit_forms' );
if ( ! $can_edit_form || ! $can_manage_form ) { echo __( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ); exit; }

// Get form data by id
$form = bookacti_get_form_data( $form_id );

if( ! $form ) { return; }

?>
<div class='wrap'>
	<h1>
	<?php 
		echo $form_id === 'new' ? esc_html__( 'Add New Booking Form', BOOKACTI_PLUGIN_NAME ) : esc_html__( 'Edit Booking Form', BOOKACTI_PLUGIN_NAME ); 
	?>
	</h1>
	<hr class='wp-header-end' />
	
	<?php
		// Display contextual notices
		if( ! empty( $_REQUEST[ 'notice' ] ) ) {
		?>
			<div class='notice notice-success is-dismissible bookacti-form-notice' >
				<p>
				<?php 
					if( $_REQUEST[ 'notice' ] === 'published' ) { _e( 'The booking form is published.', BOOKACTI_PLUGIN_NAME ); }
					else if( $_REQUEST[ 'notice' ] === 'updated' ) { _e( 'The booking form has been updated.', BOOKACTI_PLUGIN_NAME ); } 
				?>
				</p>
			</div>
		<?php
		}
	?>
	
	<div id='bookacti-form-editor-page-container' >
		<?php
			do_action( 'bookacti_form_editor_page_before', $form );
			$form_action = $form_id === 'new' ? 'new' : 'edit';
			$redirect_url = 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id;
		?>
		<form name='post' action='<?php echo $redirect_url; ?>' method='post' id='bookacti-form-editor-page-form' novalidate >
			<?php
			/* Used to save closed meta boxes and their order */
			wp_nonce_field( 'bookacti_update_form', 'nonce_update_form', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			?>
			<input type='hidden' name='page' value='bookacti_forms' />
			<input type='hidden' name='action' value='bookactiUpdateForm' />
			<input type='hidden' name='is_active' value='<?php echo $form[ 'active' ]; ?>' />
			<input type='hidden' name='form_id' value='<?php echo $form_id; ?>' id='bookacti-form-id' />
			
			<div id='poststuff'>
				<div id='post-body' class='metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>'>
					<div id='post-body-content'>
						<div id='titlediv'>
							<div id='titlewrap'>
								<?php $title_placeholder = __( 'Enter form title here', BOOKACTI_PLUGIN_NAME ); ?>
								<label class='screen-reader-text' id='title-prompt-text' for='title'><?php echo $title_placeholder; ?></label>
								<input type='text' name='form_title' size='30' value='<?php echo esc_attr( $form[ 'title' ] ); ?>' id='title' spellcheck='true' autocomplete='off' placeholder='<?php echo $title_placeholder; ?>' required />
							</div>
						</div>
						
						<div id='postdivrich' class='postarea' >
						<?php
							// Check if the form editor shall be displayed
							$error_message = '';
							// Check if the form is published
							if( empty( $form_id ) || ! is_numeric( $form_id ) ) {
								$error_message = esc_html__( 'Please set a title and publish your form first.', BOOKACTI_PLUGIN_NAME );
							
							// Check if the user has available calendars
							} else {
								
								$templates = bookacti_fetch_templates();
								if( ! $templates ) {
									$editor_path	= 'admin.php?page=bookacti_calendars';
									$editor_url		= admin_url( $editor_path );
									$error_message	= sprintf( esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %1$sCalendar Editor%2$s to create your first calendar.', BOOKACTI_PLUGIN_NAME ),
														'<a href="' . esc_url( $editor_url ) . '" >', 
														'</a>' 
													);
								}
							}
							
							// Form editor not available error message
							if( $error_message ) {
							?>
								<div id='bookacti-form-editor-not-available' ><h2><?php echo $error_message; ?></h2></div>
							<?php
							
							// FORM EDITOR
							} else {
								
								// Display a nonce for form field order
								wp_nonce_field( 'bookacti_form_field_order', 'bookacti_nonce_form_field_order', false );
								
								// Get form fields in the custom order
								$form_fields = bookacti_get_form_fields_data( $form_id );
								?>
								
								<script>
									// Compatibility with Optimization plugins
									if( typeof bookacti === 'undefined' ) { var bookacti = { booking_system:[] }; }
									// Pass fields data to JS
									bookacti.form_editor = [];
									bookacti.form_editor.form	= <?php echo json_encode( $form ); ?>;
									bookacti.form_editor.fields = <?php echo json_encode( $form_fields ); ?>;
								</script>
								
								<div id='bookacti-form-editor-container' >
									<div id='bookacti-form-editor-header' >
										<div id='bookacti-form-editor-title' >
											<h2><?php _e( 'Form editor', BOOKACTI_PLUGIN_NAME ) ?></h2>
										</div>
										<div id='bookacti-form-editor-actions' >
											<?php do_action( 'bookacti_form_editor_actions_before', $form ); ?>
											<div id='bookacti-update-form-meta' class='bookacti-form-editor-action dashicons dashicons-admin-generic' title='<?php _e( 'Change form settings', BOOKACTI_PLUGIN_NAME ); ?>'></div>
											<div id='bookacti-insert-form-field' class='bookacti-form-editor-action dashicons dashicons-plus-alt' title='<?php _e( 'Add a new field to your form', BOOKACTI_PLUGIN_NAME ); ?>'></div>
											<?php do_action( 'bookacti_form_editor_actions_after', $form ); ?>
										</div>
									</div>
									<div id='bookacti-form-editor-description' >
										<p>
										<?php 
											/* translators: the placeholders are icons related to the action */
											echo sprintf( __( 'Click on %1$s to add, %2$s to edit, %3$s to remove and %4$s to preview your form fields.<br/>Drag and drop fields to switch their positions.', BOOKACTI_PLUGIN_NAME ),
												'<span class="dashicons dashicons-plus-alt"></span>',
												'<span class="dashicons dashicons-admin-generic"></span>',
												'<span class="dashicons dashicons-trash"></span>',
												'<span class="dashicons dashicons-arrow-down"></span>' ); 
											do_action( 'bookacti_form_editor_description_after', $form );
										?>
										</p>
									</div>
									<div id='bookacti-form-editor' >
										<?php
										do_action( 'bookacti_form_editor_before', $form );
										
										// Display form fields 
										$ordered_form_fields = bookacti_sort_form_fields_array( $form_id, $form_fields );
										foreach( $ordered_form_fields as $field ) {
											bookacti_diplay_form_field_for_editor( $field, $form_id );
										}
										
										do_action( 'bookacti_form_editor_after', $form );
										?>
									</div>
								</div>
								<?php
							}
						?>
						</div>
					</div>
					<div id='postbox-container-1' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'side', $form );
					?>
					</div>
					<div id='postbox-container-2' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'normal', $form );
						do_meta_boxes( null, 'advanced', $form );
					?>
					</div>
				</div>
				<br class='clear' />
			</div>
		</form>
		<?php
			do_action( 'bookacti_form_editor_page_after', $form );
		?>
	</div>
</div>
<?php
// Include form editor dialogs
if( ! $error_message ) {
	include_once( 'view-form-editor-dialogs.php' );
}