<?php
/**
 * Form editor page
 * @since 1.5.0
 * @version 1.14.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( empty( $_REQUEST[ 'action' ] ) && ! empty( $_REQUEST[ 'form_id' ] ) ) {
	$_REQUEST[ 'action' ] = is_numeric( $_REQUEST[ 'form_id' ] ) ? 'edit' : 'new';
}

$form_id = ! empty( $_REQUEST[ 'form_id' ] ) ? intval( $_REQUEST[ 'form_id' ] ) : 0;

if( ! $form_id ) { exit; }

// Exit if not allowed to edit current form
$can_manage_form = bookacti_user_can_manage_form( $form_id );
$can_edit_form   = current_user_can( 'bookacti_edit_forms' );
if ( ! $can_edit_form || ! $can_manage_form ) { echo __( 'You are not allowed to do that.', 'booking-activities' ); exit; }

// Get form data
$form_raw = bookacti_get_form_data( $form_id, true );
if( ! $form_raw ) { exit; }

// Get form fields data
$form_fields      = array();
$form_fields_edit = array();
$form_fields_raw  = bookacti_get_form_fields_data( $form_id, true, false, true );
foreach( $form_fields_raw as $field_id => $field_raw ) {
	$form_fields[ $field_id ] = bookacti_format_form_field_data( $field_raw );
}

// Get edit data in the default language
$lang_switched = bookacti_switch_locale( bookacti_get_site_default_locale() );

$form_edit = bookacti_format_form_data( $form_raw, 'edit' );
foreach( $form_fields_raw as $field_id => $field_raw ) {
	$form_fields_edit[ $field_id ] = bookacti_format_form_field_data( $field_raw, 'edit' );
}

if( $lang_switched ) { bookacti_restore_locale(); }

if( ! $form_edit ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Edit Booking Form', 'booking-activities' ); ?></h1>
	<?php do_action( 'bookacti_form_editor_page_header', $form_edit ); ?>
	<hr class='wp-header-end'/>
	
	<?php
	// Display contextual notices
	if( ! empty( $_REQUEST[ 'notice' ] ) ) {
	?>
		<div class='notice notice-success is-dismissible bookacti-form-notice'>
			<p>
			<?php 
				     if( $_REQUEST[ 'notice' ] === 'published' ) { esc_html_e( 'The booking form is published.', 'booking-activities' ); }
				else if( $_REQUEST[ 'notice' ] === 'updated' )   { esc_html_e( 'The booking form has been updated.', 'booking-activities' ); } 
			?>
			</p>
		</div>
	<?php
	}
	if( ! $form_edit[ 'active' ] && $form_edit[ 'status' ] !== 'trash' ) {
	?>
		<div class='notice notice-warning is-dismissible bookacti-form-notice'>
			<p>
			<?php esc_attr_e( 'This booking form is not published yet. You need to publish it to make it available and permanent.', 'booking-activities' ); ?>
			</p>
		</div>
	<?php
	}
	?>
	<div id='bookacti-form-editor-page-container'>
		<?php
			do_action( 'bookacti_form_editor_page_before', $form_edit );
			$redirect_url = 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id;
		?>
		<form name='post' action='<?php echo $redirect_url; ?>' method='post' id='bookacti-form-editor-page-form' novalidate>
			<?php
			/* Used to save closed meta boxes and their order */
			wp_nonce_field( 'bookacti_update_form', 'nonce_update_form', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			?>
			<input type='hidden' name='page' value='bookacti_forms'/>
			<input type='hidden' name='action' value='bookactiUpdateForm'/>
			<input type='hidden' name='is_active' value='<?php echo $form_edit[ 'active' ]; ?>'/>
			<input type='hidden' name='form_id' value='<?php echo $form_id; ?>' id='bookacti-form-id'/>
			
			<div id='bookacti-form-editor-page-lang-switcher' class='bookacti-lang-switcher' ></div>
			
			<div id='poststuff'>
				<div id='post-body' class='metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>'>
					<div id='post-body-content'>
						<div id='titlediv'>
							<div id='titlewrap'>
								<?php $title_placeholder = esc_html__( 'Enter form title here', 'booking-activities' ); ?>
								<label class='screen-reader-text' id='title-prompt-text' for='title'><?php echo $title_placeholder; ?></label>
								<input type='text' name='form_title' size='30' value='<?php echo esc_attr( $form_edit[ 'title' ] ); ?>' id='title' spellcheck='true' autocomplete='off' placeholder='<?php echo $title_placeholder; ?>' required/>
							</div>
						</div>
						
						<div id='postdivrich' class='postarea' >
						<?php
							// Check if the form editor shall be displayed
							$error_message = '';
							// Check if the form is published
							if( empty( $form_id ) || ! is_numeric( $form_id ) ) {
								$error_message = esc_html__( 'Please set a title and publish your form first.', 'booking-activities' );
							
							// Check if the user has available calendars
							} else {
								$templates = bookacti_fetch_templates();
								if( ! $templates ) {
									$editor_path   = 'admin.php?page=bookacti_calendars';
									$editor_url    = admin_url( $editor_path );
									$error_message = sprintf( 
										esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %1$sCalendar Editor%2$s to create your first calendar.', 'booking-activities' ),
										'<a href="' . esc_url( $editor_url ) . '" >', 
										'</a>' 
									);
								}
							}
							
							// Form editor not available error message
							if( $error_message ) {
							?>
								<div id='bookacti-form-editor-not-available'><h2><?php echo $error_message; ?></h2></div>
							<?php
							
							// FORM EDITOR
							} else {
								// Display a nonce for form field order
								wp_nonce_field( 'bookacti_form_field_order', 'bookacti_nonce_form_field_order', false );
								?>
								
								<script>
									// Compatibility with Optimization plugins
									if( typeof bookacti === 'undefined' ) { var bookacti = { booking_system:[] }; }
									// Pass fields data to JS
									bookacti.form_editor        = [];
									bookacti.form_editor.form   = <?php echo json_encode( $form_edit ); ?>;
									bookacti.form_editor.fields = <?php echo json_encode( $form_fields_edit ); ?>;
								</script>
								
								<div id='bookacti-form-editor-container'>
									<div id='bookacti-form-editor-header'>
										<div id='bookacti-form-editor-title'>
											<h2><?php esc_html_e( 'Form editor', 'booking-activities' ) ?></h2>
										</div>
										<div id='bookacti-form-editor-actions'>
											<?php do_action( 'bookacti_form_editor_actions_before', $form_edit ); ?>
											<div id='bookacti-update-form-meta' class='bookacti-form-editor-action dashicons dashicons-admin-generic' title='<?php _e( 'Change form settings', 'booking-activities' ); ?>'></div>
											<div id='bookacti-insert-form-field' class='bookacti-form-editor-action button button-secondary' title='<?php esc_html_e( 'Add a new field to your form', 'booking-activities' ); ?>'><?php esc_html_e( 'Add a field', 'booking-activities' ); ?></div>
											<?php do_action( 'bookacti_form_editor_actions_after', $form_edit ); ?>
										</div>
									</div>
									<div id='bookacti-form-editor-description'>
										<p>
										<?php 
											/* translators: the placeholders are icons related to the action */
											echo sprintf( __( 'Click on %1$s to add, %2$s to edit, %3$s to remove and %4$s to preview your form fields.<br/>Drag and drop fields to switch their positions.', 'booking-activities' ),
											    '<span class="dashicons dashicons-plus-alt"></span>',
											    '<span class="dashicons dashicons-admin-generic"></span>',
											    '<span class="dashicons dashicons-trash"></span>',
											    '<span class="dashicons dashicons-arrow-down"></span>' ); 
											do_action( 'bookacti_form_editor_description_after', $form_edit );
										?>
										</p>
									</div>
									<div id='bookacti-fatal-error' class='bookacti-notices' style='display:none;'>
										<ul class='bookacti-error-list'>
											<li><strong><?php echo sprintf( esc_html__( 'A fatal error occurred. Please try to refresh the page. If the error persists, follow the process under "Booking Activities doesn’t work as it should" here: %s.', 'booking-activities' ), '<a href="https://booking-activities.fr/en/documentation/faq">' . esc_html__( 'FAQ', 'booking-activities' ) . '</a>' ); ?></strong>
											<li><em><?php esc_html_e( 'Advanced users, you can stop loading and free the fields to try to solve your problem:', 'booking-activities' ); ?></em>
												<input type='button' id='bookacti-exit-loading' value='<?php esc_attr_e( 'Stop loading and free fields', 'booking-activities' ) ?>'/>
										</ul>
									</div>
									<div id='bookacti-form-editor'>
										<?php
										do_action( 'bookacti_form_editor_before', $form_edit );
										
										// Display form fields in the custom order
										$ordered_form_fields = bookacti_sort_form_fields_array( $form_id, $form_fields );
										foreach( $ordered_form_fields as $field ) {
											if( ! $field ) { continue; }
											bookacti_display_form_field_for_editor( $field );
										}
										
										do_action( 'bookacti_form_editor_after', $form_edit );
										
										// START ADVANCED FORMS ADD-ON PROMO
										$is_plugin_active = bookacti_is_plugin_active( 'ba-advanced-forms/ba-advanced-forms.php' );
										if( ! $is_plugin_active ) {
											$addon_link = '<strong><a href="https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=form-editor-fields" target="_blank" >Advanced Forms</a></strong>';
											?>
											<hr/>
											<div class='bookacti-addon-promo'>
												<p>
												<?php 
													/* translators: %1$s is the placeholder for Advanced Forms add-on link */
													echo sprintf( esc_html__( 'Add any custom fields to your booking form thanks to the %1$s add-on:', 'booking-activities' ), $addon_link ); 
												?>
												</p>
												<div id='bookacti-form-editor-promo-field-height' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header'>
														<div class='bookacti-form-editor-field-title'>
															<h3><?php echo esc_html__( 'Example:', 'booking-activities' ) . ' ' . esc_html__( 'Height', 'booking-activities' ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions'>
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', 'booking-activities' ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<div>
															<div class='bookacti-form-field-label'>
																<label><?php esc_html_e( 'Your height:', 'booking-activities' ); ?></label>
																<?php 
																	/* translators: %1$s is the placeholder for Advanced Forms add-on link */
																	$tip = sprintf( esc_html__( 'In this example, a number field has been added to the form in order to ask the customer\'s height. Thanks to the %1$s add-on, you can add any kind of field and ask any information to your customers.', 'booking-activities' ), $addon_link );
																	bookacti_help_tip( $tip ); 
																?>
															</div>
															<div class='bookacti-form-field-content'>
																<?php
																	$args = array( 
																		'type'		=> 'number',
																		'name'		=> 'promo_height',
																		'options'	=> array( 'min' => 110, 'max' => 240, 'step' => 1 )
																	);
																	bookacti_display_field( $args );
																?>
																<span><?php echo esc_html_x( 'cm', 'short for centimeters', 'booking-activities' ); ?></span>
															</div>
														</div>
													</div>
												</div>
												<div id='bookacti-form-editor-promo-field-file' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header'>
														<div class='bookacti-form-editor-field-title'>
															<h3><?php echo esc_html__( 'Example:', 'booking-activities' ) . ' ' . esc_html__( 'Document(s)', 'booking-activities' ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions'>
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', 'booking-activities' ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<div>
															<div class='bookacti-form-field-label'>
																<label><?php esc_html_e( 'Any document(s):', 'booking-activities' ); ?></label>
																<?php 
																	$tip = esc_html__( 'You can even ask your customers for digital files.', 'booking-activities' );
																	bookacti_help_tip( $tip ); 
																?>
															</div>
															<div class='bookacti-form-field-content'>
																<?php
																	$args = array( 
																		'type'     => 'file',
																		'name'     => 'promo_file',
																		'multiple' => 1
																	);
																	bookacti_display_field( $args );
																?>
															</div>
														</div>
													</div>
												</div>
												<div id='bookacti-form-editor-promo-field-participants' class='bookacti-form-editor-field bookacti-form-editor-promo-field'>
													<div class='bookacti-form-editor-field-header'>
														<div class='bookacti-form-editor-field-title'>
															<h3><?php echo esc_html__( 'Example:', 'booking-activities' ) . ' ' . esc_html__( 'Fields for each participants', 'booking-activities' ); ?></h3>
														</div>
														<div class='bookacti-form-editor-field-actions'>
															<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', 'booking-activities' ); ?>'></div>
														</div>
													</div>
													<div class='bookacti-form-editor-field-body' style='display:none;'>
														<fieldset style='border: 1px solid #bbb;margin-bottom:20px;padding:10px;'>
															<legend style='margin:auto;padding:0 10px;font-weight:bold;'><?php esc_html_e( 'Participant #1', 'booking-activities' ); ?></legend>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php esc_html_e( 'Your height:', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'    => 'number',
																			'name'    => 'promo_height',
																			'options' => array( 'min' => 110, 'max' => 240, 'step' => 1 )
																		);
																		bookacti_display_field( $args );
																	?>
																	<span><?php echo esc_html_x( 'cm', 'short for centimeters', 'booking-activities' ); ?></span>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php esc_html_e( 'Any document(s):', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'     => 'file',
																			'name'     => 'promo_file',
																			'multiple' => 1
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php esc_html_e( 'Any other field:', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'    => 'select',
																			'name'    => 'promo_select1',
																			'options' => array( 
																				'value1' => esc_html__( 'Any value', 'booking-activities' ),
																				'value2' => esc_html__( 'Fully customizable', 'booking-activities' ),
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
															<legend style='margin:auto;padding:0 10px;font-weight:bold;'><?php esc_html_e( 'Participant #2', 'booking-activities' ); ?></legend>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php /* translators: asking a human for his height */ esc_html_e( 'Your height:', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'    => 'number',
																			'name'    => 'promo_height',
																			'options' => array( 'min' => 110, 'max' => 240, 'step' => 1 )
																		);
																		bookacti_display_field( $args );
																	?>
																	<span><?php echo esc_html_x( 'cm', 'short for centimeters', 'booking-activities' ); ?></span>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php esc_html_e( 'Any document(s):', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'     => 'file',
																			'name'     => 'promo_file',
																			'multiple' => 1
																		);
																		bookacti_display_field( $args );
																	?>
																</div>
															</div>
															<div>
																<div class='bookacti-form-field-label'>
																	<label><?php esc_html_e( 'Any other field:', 'booking-activities' ); ?></label>
																</div>
																<div class='bookacti-form-field-content'>
																	<?php
																		$args = array( 
																			'type'    => 'select',
																			'name'    => 'promo_select',
																			'options' => array( 
																				'value1' => esc_html__( 'Any value', 'booking-activities' ),
																				'value2' => esc_html__( 'Fully customizable', 'booking-activities' ),
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
																esc_html_e( 'You can request data from each participant. The number of participants is proportional to the quantity reserved.', 'booking-activities' );
															?>
														</div>
													</div>
												</div>
												<div style='text-align:center;'><a href='https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=form-editor-fields' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
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
						do_meta_boxes( null, 'side', $form_edit );
					?>
					</div>
					<div id='postbox-container-2' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'normal', $form_edit );
						do_meta_boxes( null, 'advanced', $form_edit );
					?>
					</div>
				</div>
				<br class='clear' />
			</div>
		</form>
		<?php
			do_action( 'bookacti_form_editor_page_after', $form_edit );
		?>
	</div>
</div>
<?php
// Include form editor dialogs
if( ! $error_message ) { include_once( 'view-form-editor-dialogs.php' ); }