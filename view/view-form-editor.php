<?php
/**
 * Form editor page
 * @since 1.5.0
 * @version 1.5.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( empty( $_REQUEST[ 'action' ] ) && ! empty( $_REQUEST[ 'form_id' ] ) ) {
	$_REQUEST[ 'action' ] = is_numeric( $_REQUEST[ 'form_id' ] ) ? 'edit' : 'new';
}

$form_id = ! empty( $_REQUEST[ 'form_id' ] ) ? intval( $_REQUEST[ 'form_id' ] ) : 0;

if( ! $form_id ) { exit; }

// Exit if not allowed to edit current form
$can_manage_form	= bookacti_user_can_manage_form( $form_id );
$can_edit_form		= current_user_can( 'bookacti_edit_forms' );
if ( ! $can_edit_form || ! $can_manage_form ) { echo __( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ); exit; }


// Get form data by id
$form = bookacti_get_form_data( $form_id );

if( ! $form ) { exit; }

?>
<div class='wrap'>
	<h1><?php esc_html_e( 'Edit Booking Form', BOOKACTI_PLUGIN_NAME ); ?></h1>
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
	if( ! $form[ 'active' ] && $form[ 'status' ] !== 'trash' ) {
	?>
		<div class='notice notice-warning is-dismissible bookacti-form-notice' >
			<p>
			<?php esc_attr_e( 'This booking form is not published yet. You need to publish it to make it available and permanent.', BOOKACTI_PLUGIN_NAME ); ?>
			</p>
		</div>
	<?php
	}
	?>
	<div id='bookacti-form-editor-page-container' >
		<?php
			do_action( 'bookacti_form_editor_page_before', $form );
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
			
			<div id='bookacti-form-editor-page-lang-switcher' class='bookacti-lang-switcher' ></div>
			
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
											if( ! $field ) { continue; }
											bookacti_display_form_field_for_editor( $field );
										}
										
										do_action( 'bookacti_form_editor_after', $form );
										
										// START ADVANCED FORMS ADD-ON PROMO
										$is_plugin_active = bookacti_is_plugin_active( 'ba-advanced-forms/ba-advanced-forms.php' );
										if( ! $is_plugin_active ) {
											$addon_link = '<strong><a href="https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=form-editor-fields" target="_blank" >';
											$addon_link .= esc_html__( 'Advanced Forms', BOOKACTI_PLUGIN_NAME );
											$addon_link .= '</a></strong>';
											?>
											<hr/>
											<div class='bookacti-addon-promo'>
												<p>
												<?php 
													/* translators: %1$s is the placeholder for Advanced Forms add-on link */
													echo sprintf( esc_html__( 'Add any custom fields to your booking form thanks to the %1$s add-on:' ), $addon_link ); 
												?>
												</p>
												<div id='bookacti-form-editor-promo-field-height' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header' >
														<div class='bookacti-form-editor-field-title' >
															<h3><?php echo esc_html__( 'Example:', BOOKACTI_PLUGIN_NAME ) . ' ' . esc_html__( 'Height', BOOKACTI_PLUGIN_NAME ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions' >
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', BOOKACTI_PLUGIN_NAME ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<div>
															<div class='bookacti-form-field-label' >
																<label><?php esc_html_e( 'Your height:', BOOKACTI_PLUGIN_NAME ); ?></label>
																<?php 
																	/* translators: %1$s is the placeholder for Advanced Forms add-on link */
																	$tip = sprintf( esc_html__( 'In this example, a number field has been added to the form in order to ask the customer\'s height. Thanks to the %1$s add-on, you can add any kind of field and ask any information to your customers.', BOOKACTI_PLUGIN_NAME ), $addon_link );
																	bookacti_help_tip( $tip ); 
																?>
															</div>
															<div class='bookacti-form-field-content' >
																<?php
																	$args = array( 
																		'type'		=> 'number',
																		'name'		=> 'promo_height',
																		'options'	=> array( 'min' => 110, 'max' => 240, 'step' => 1 )
																	);
																	bookacti_display_field( $args );
																?>
																<span><?php echo esc_html_x( 'cm', 'short for centimeters', BOOKACTI_PLUGIN_NAME ); ?></span>
															</div>
														</div>
													</div>
												</div>
												<div id='bookacti-form-editor-promo-field-file' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header' >
														<div class='bookacti-form-editor-field-title' >
															<h3><?php echo esc_html__( 'Example:', BOOKACTI_PLUGIN_NAME ) . ' ' . esc_html__( 'Document(s)', BOOKACTI_PLUGIN_NAME ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions' >
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', BOOKACTI_PLUGIN_NAME ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<div>
															<div class='bookacti-form-field-label' >
																<label><?php esc_html_e( 'Any document(s):', BOOKACTI_PLUGIN_NAME ); ?></label>
																<?php 
																	$tip = esc_html__( 'You can even ask your customers for digital files.', BOOKACTI_PLUGIN_NAME );
																	bookacti_help_tip( $tip ); 
																?>
															</div>
															<div class='bookacti-form-field-content' >
																<?php
																	$args = array( 
																		'type'		=> 'file',
																		'name'		=> 'promo_file',
																		'multiple'	=> 1
																	);
																	bookacti_display_field( $args );
																?>
															</div>
														</div>
													</div>
												</div>
												<div id='bookacti-form-editor-promo-field-participants' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header' >
														<div class='bookacti-form-editor-field-title' >
															<h3><?php echo esc_html__( 'Example:', BOOKACTI_PLUGIN_NAME ) . ' ' . esc_html__( 'Fields for each participants', BOOKACTI_PLUGIN_NAME ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions' >
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', BOOKACTI_PLUGIN_NAME ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<fieldset style='border: 1px solid #bbb;margin-bottom:20px;padding:10px;'>
															<legend style='margin:auto;padding:0 10px;font-weight:bold;'><?php esc_html_e( 'Participant #1', BOOKACTI_PLUGIN_NAME ); ?></legend>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php esc_html_e( 'Your height:', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'number',
																			'name'		=> 'promo_height',
																			'options'	=> array( 'min' => 110, 'max' => 240, 'step' => 1 )
																		);
																		bookacti_display_field( $args );
																	?>
																	<span><?php echo esc_html_x( 'cm', 'short for centimeters', BOOKACTI_PLUGIN_NAME ); ?></span>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php esc_html_e( 'Any document(s):', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'file',
																			'name'		=> 'promo_file',
																			'multiple'	=> 1
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php esc_html_e( 'Any other field:', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'select',
																			'name'		=> 'promo_select1',
																			'options'	=> array( 
																				'value1' => esc_html__( 'Any value', BOOKACTI_PLUGIN_NAME ),
																				'value2' => esc_html__( 'Fully customizable', BOOKACTI_PLUGIN_NAME ),
																				'value3' => '...'
																			)
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div><span>...</span></div>
														</fieldset>
														<fieldset style='border: 1px solid #bbb;margin-bottom:20px;padding:10px;'>
															<legend style='margin:auto;padding:0 10px;font-weight:bold;'><?php esc_html_e( 'Participant #2', BOOKACTI_PLUGIN_NAME ); ?></legend>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php /* translators: asking a human for his height */ esc_html_e( 'Your height:', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'number',
																			'name'		=> 'promo_height',
																			'options'	=> array( 'min' => 110, 'max' => 240, 'step' => 1 )
																		);
																		bookacti_display_field( $args );
																	?>
																	<span><?php echo esc_html_x( 'cm', 'short for centimeters', BOOKACTI_PLUGIN_NAME ); ?></span>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php esc_html_e( 'Any document(s):', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'file',
																			'name'		=> 'promo_file',
																			'multiple'	=> 1
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label' >
																	<label><?php esc_html_e( 'Any other field:', BOOKACTI_PLUGIN_NAME ); ?></label>
																</div>
																<div class='bookacti-form-field-content' >
																	<?php
																		$args = array( 
																			'type'		=> 'select',
																			'name'		=> 'promo_select2',
																			'options'	=> array( 
																				'value1' => esc_html__( 'Any value', BOOKACTI_PLUGIN_NAME ),
																				'value2' => esc_html__( 'Fully customizable', BOOKACTI_PLUGIN_NAME ),
																				'value3' => '...'
																			)
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div><span>...</span></div>
														</fieldset>
														<div>
															<?php
																esc_html_e( 'You can request data from each participant. The number of participants is proportional to the quantity reserved.', BOOKACTI_PLUGIN_NAME );
															?>
														</div>
													</div>
												</div>
												<div style='text-align:center;'><a href='https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=form-editor-fields' class='button' target='_blank' ><?php esc_html_e( 'Learn more', BOOKACTI_PLUGIN_NAME ); ?></a></div>
											</div>
											<?php
										} // END OF ADVANCED FORMS ADD-ON PROMO
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
?>