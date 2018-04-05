<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// FORM FIELDS

/**
 * Display the form field 'calendar'
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_calendar( $field, $form_id, $instance_id, $context ) {
	$field[ 'id' ] = ! empty( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'field_id' ] . '-' . $instance_id;
	bookacti_get_booking_system( $field, true );
}
add_action( 'bookacti_diplay_form_field_calendar', 'bookacti_display_form_field_calendar', 10, 4 );


/**
 * Display the form field 'login'
 * @since 1.5.0
 * @param string $html
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 * @return string
 */
function bookacti_display_form_field_login( $html, $field, $form_id, $instance_id, $context ) {
	$field_id = ! empty( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'field_id' ] . '-' . $instance_id;
?>
	<div class='bookacti-form-field-container' id='<?php echo $field_id; ?>' id='<?php echo $field[ 'class' ]; ?>' >
		<div class='bookacti-form-field-label' >
			<label for='<?php echo $field_id . '-email'; ?>' ><?php echo $field[ 'label' ][ 'email' ]; ?></label>
		<?php if( ! empty( $field[ 'tip' ][ 'email' ] ) ) { bookacti_help_tip( $field[ 'tip' ][ 'email' ] ); } ?>
		</div>
		<div class='bookacti-form-field-content' >
		<input name='bookacti_email'
			id='<?php echo $field_id . '-email'; ?>'
			class='bookacti-form-field bookacti-email'
			type='email' />
		</div>
		
		<?php if( empty( $field[ 'generate_password' ] ) ) { ?>
			<div class='bookacti-form-field-label' >
				<label for='<?php echo $field_id . '-password'; ?>' ><?php _e( 'Password', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php if( ! empty( $field[ 'tip' ][ 'password' ] ) ) { bookacti_help_tip( $field[ 'tip' ][ 'password' ] ); } ?>
			</div>
			<div class='bookacti-form-field-content' >
			<input name='bookacti_password'
				id='<?php echo $field_id . '-password'; ?>'
				class='bookacti-form-field bookacti-password'
				type='password'/>
			</div>
		<?php } ?>
	</div>
<?php
}
add_filter( 'bookacti_html_form_field_login', 'bookacti_display_form_field_login', 10, 5 );


/**
 * Display the form field 'quantity'
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_quantity( $field, $form_id, $instance_id, $context ) {
	$field_id = ! empty( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'field_id' ] . '-' . $instance_id;
?>
	<input	name='bookacti_quantity'
			id='<?php echo $field_id; ?>'
			class='bookacti-form-field bookacti-quantity <?php echo $field[ 'class' ]; ?>'
			type='number' 
			min='1'
			value='1' />
<?php
}
add_action( 'bookacti_diplay_form_field_quantity', 'bookacti_display_form_field_quantity', 10, 4 );


/**
 * Display the form field 'submit'
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_submit( $field, $form_id, $instance_id, $context ) {
	$field_id = ! empty( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'field_id' ] . '-' . $instance_id;
?>
	<input type='<?php echo $context === 'edit' ? 'button' : 'submit'; ?>'
		id='<?php echo $field_id; ?>' 
		class='bookacti-submit-form button <?php echo $field[ 'class' ]; ?>' 
		value='<?php echo $field[ 'label' ]; ?>' />
<?php
}
add_action( 'bookacti_diplay_form_field_submit', 'bookacti_display_form_field_submit', 10, 4 );


/**
 * Display the form field 'submit'
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_free_text( $field, $form_id, $instance_id, $context ) {
	$field_id = ! empty( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'field_id' ] . '-' . $instance_id;
?>
	<div id='<?php echo $field_id; ?>' class='bookacti-form-free-text' ><?php echo $field[ 'value' ]; ?></div>
<?php
}
add_action( 'bookacti_diplay_form_field_free_text', 'bookacti_display_form_field_free_text', 10, 4 );




// FORM EDITOR PAGE

/**
 * Add form editor meta boxes
 * @since 1.5.0
 */
function bookacti_form_editor_meta_boxes() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
	// Sidebar
	add_meta_box( 'bookacti_form_publish', __( 'Publish', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_publish_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'high' );
	add_meta_box( 'bookacti_form_managers', __( 'Managers', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_managers_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'default' );

	add_meta_box( 'bookacti_form_integration_tuto', __( 'How to integrate this form', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_integration_tuto_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'low' );
}
add_action( 'add_meta_boxes_booking-activities_page_bookacti_forms', 'bookacti_form_editor_meta_boxes' );


/*
 * Allow metaboxes on for editor
 * @since 1.5.0
 */
function bookacti_allow_meta_boxes_in_form_editor() {
	
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action( 'add_meta_boxes_booking-activities_page_bookacti_forms', null );
    do_action( 'add_meta_boxes', 'booking-activities_page_bookacti_forms', null );
	
	/* Enqueue WordPress' script for handling the meta boxes */
	wp_enqueue_script( 'postbox' );
}
add_action( 'load-booking-activities_page_bookacti_forms', 'bookacti_allow_meta_boxes_in_form_editor' );
 

/**
 * Print metabox script to make it work on form editor
 * @since 1.5.0
 */
function bookacti_print_metabox_script_in_form_editor_footer() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'create' ), true ) ) { return; }
	?>
		<script>$j( document ).ready( function(){ postboxes.add_postbox_toggles(pagenow); } );</script>
	<?php
}
add_action( 'admin_footer-booking-activities_page_bookacti_forms', 'bookacti_print_metabox_script_in_form_editor_footer' );




// BOOKING FORMS

/**
 * AJAX Controller - Update a booking form
 * @since 1.5.0
 */
function bookacti_controller_update_form() {
	// Check nonce and capabilities
	$form_id		= intval( $_REQUEST['form_id'] );
	$is_nonce_valid	= check_ajax_referer( 'bookacti_update_form', 'nonce_update_form', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );

	if( ! $is_nonce_valid || ! $is_allowed ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
	
	$was_active		= intval( $_REQUEST['is_active'] );
	$form_title		= sanitize_text_field( stripslashes( $_REQUEST['form_title'] ) );
	$managers_array	= isset( $_REQUEST['form-managers'] ) ? bookacti_ids_to_array( $_REQUEST['form-managers'] ) : array();
	$form_managers	= bookacti_format_form_managers( $managers_array );
	
	// Create the form
	$updated = bookacti_update_form( $form_id, $form_title, -1, '', 'publish', 1 );

	// Feedback error
	if( $updated === false ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'query_failed' ) );
	}

	// Insert Managers
	bookacti_update_managers( 'form', $form_id, $form_managers );
	
	add_action( 'bookacti_form_updated', $form_id );

	wp_send_json( array( 'status' => 'success', 'message' => ! $was_active ? esc_html__( 'The booking form is published.', BOOKACTI_PLUGIN_NAME ) : esc_html__( 'The booking form has been updated.', BOOKACTI_PLUGIN_NAME ) ) );
}
add_action( 'wp_ajax_bookactiUpdateForm', 'bookacti_controller_update_form' );


/**
 * Trash / Remove / Restore a booking form according to URL parameters and display an admin notice to feedback
 * @since 1.5.0
 */
function bookacti_controller_remove_form() {
	if( empty( $_REQUEST[ 'form_id' ] ) || empty( $_REQUEST[ 'action' ] ) || empty( $_REQUEST[ 'page' ] ) 
		|| $_REQUEST[ 'page' ] !== 'bookacti_forms' 
		|| ! is_numeric( $_REQUEST[ 'form_id' ] )
		|| ! in_array( $_REQUEST[ 'action' ], array( 'trash', 'restore', 'delete' ), true ) ) { return; }
	
	$form_id = intval( $_REQUEST[ 'form_id' ] );
	
	// Check nonces
	if( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $_REQUEST[ 'action' ] . '-form_' . $form_id ) ) {
	?>
		<div class='notice notice-error is-dismissible bookacti-form-notice' >
			<p>
				<?php _e( 'You are not allowed to do that.', BOOKACTI_PLUGIN_NAME ); ?>
			</p>
		</div>
	<?php
		return;
	}
	
	// Remove a booking form
	if( $_REQUEST[ 'action' ] === 'trash' || $_REQUEST[ 'action' ] === 'delete' ) {
		
		// Check if current user is allowed to remove the booking form
		$can_delete_form = current_user_can( 'bookacti_delete_forms' ) && bookacti_user_can_manage_form( $form_id );
		if( ! $can_delete_form ) {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'You are not allowed to remove a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
			return;
		}
		
		// Whether to trash or remove permanently
		$removed = false;
		if( $_REQUEST[ 'action' ] === 'trash' && $form_id ) {
			$removed = bookacti_deactivate_form( $form_id );
		}
		else if( $_REQUEST[ 'action' ] === 'delete' && $form_id ) {
			$removed = bookacti_delete_form( $form_id );
		}
		
		// Feedback success
		if( $removed ) {
		?>
			<div class='notice notice-success is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'The booking form has been removed.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		
		// Feedback failure
		} else {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'An error occured while trying to delete a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		}
	}
	
	// Restore a booking form
	else if( $_REQUEST[ 'action' ] === 'restore' ) {
		
		// Check if current user is allowed to restore the booking form
		$can_edit_form = current_user_can( 'bookacti_edit_forms' );
		if( ! $can_edit_form ) {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'You are not allowed to restore a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
			return;
		}
		
		$restored = bookacti_activate_form( $form_id );
		
		// Feedback success
		if( $restored ) {
		?>
			<div class='notice notice-success is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'The booking form has been restored.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		
		// Feedback failure
		} else {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'An error occured while trying to restore a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		}
	}
}
add_action( 'all_admin_notices', 'bookacti_controller_remove_form', 10 );




// FORM FIELDS

/**
 * AJAX Controller - Insert a form field
 * @since 1.5.0
 */
function bookacti_controller_insert_form_field() {
	$form_id = intval( $_POST[ 'form_id' ] );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_form_field', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		$field_name = $_POST[ 'field_name' ];
		
		// Check if the field is known
		$fields_data = bookacti_get_default_form_fields_data();
		if( ! in_array( $field_name, array_keys( $fields_data ), true ) ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'unknown_field' ) );
		}
		
		// Check if the field already exists
		$form_fields = bookacti_get_form_fields_data( $form_id );
		$field_already_added = array();
		foreach( $form_fields as $form_field ) { $field_already_added[] = $form_field[ 'name' ]; }
		if( in_array( $field_name, $field_already_added, true ) && $fields_data[ $field_name ][ 'unique' ] ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'field_already_added' ) );
		}
		
		// Insert form field
		$field_id = bookacti_insert_form_field( $form_id, $field_name );
		
		if( $field_id !== false ) {
			
			$fields_data[ $field_name ][ 'field_id' ] = $field_id;
			
			// Update field order
			$field_order	= bookacti_get_metadata( 'form', $form_id, 'field_order', true );
			$field_order[]	= $field_id;
			bookacti_update_metadata( 'form', $form_id, array( 'field_order' => $field_order ) );
			
			// Get field editor HTML
			$field_html = bookacti_diplay_form_field_for_editor( $fields_data[ $field_name ], $form_id, false );
			
			// Get field data
			$field_data	= bookacti_get_form_field_data( $field_id );
			
			wp_send_json( array( 'status' => 'success', 'field_id' => $field_id, 'field_data' => $field_data, 'field_html' => $field_html ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiInsertFormField', 'bookacti_controller_insert_form_field', 10 );

/**
 * AJAX Controller - Remove a form field
 * @since 1.5.0
 */
function bookacti_controller_remove_form_field() {
	
	$field_id	= intval( $_POST[ 'field_id' ] );
	$field		= bookacti_get_form_field( $field_id );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_remove_form_field', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $field[ 'form_id' ] );
	
	if( $is_nonce_valid && $is_allowed && $field_id ) {
		
		// Remove the form field and its metadata
		$removed = bookacti_delete_form_field( $field_id );
		
		if( $removed !== false ) {
			
			// Update field order
			$field_order	= bookacti_get_metadata( 'form', $field[ 'form_id' ], 'field_order', true );
			$order_index	= array_search( $field_id, $field_order );
			
			if( $order_index !== false ) {
				unset( $field_order[ $order_index ] );
				bookacti_update_metadata( 'form', $field[ 'form_id' ], array( 'field_order' => array_values( $field_order ) ) );
			}
			
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiRemoveFormField', 'bookacti_controller_remove_form_field', 10 );


/**
 * AJAX Controller - Save form field order
 * @since 1.5.0
 */
function bookacti_controller_save_form_field_order() {
	$form_id = intval( $_POST[ 'form_id' ] );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_form_field_order', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		$field_order	= bookacti_sanitize_form_field_order( $form_id, $_POST[ 'field_order' ] );
		$updated		= bookacti_update_metadata( 'form', $form_id, array( 'field_order' => $field_order ) );
		
		if( $updated !== false ) {
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiSaveFormFieldOrder', 'bookacti_controller_save_form_field_order', 10 );


/**
 * AJAX Controller - Update a field
 * @since 1.5.0
 */
function bookacti_controller_update_form_field() {
	
	$field_id	= intval( $_POST[ 'field_id' ] );
	$field		= bookacti_get_form_field( $field_id );
	$form_id	= $field[ 'form_id' ];
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_update_form_field', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		// Sanitize data
		$raw_data	= array_merge( $field, $_POST );
		$field_data	= bookacti_format_form_field_data( $raw_data );
		
		// Update form field
		$updated = bookacti_update_form_field( $field_data );
		
		if( $updated !== false ) {
			
			// Extract metadata only
			$field_meta = array_intersect_key( $field_data, bookacti_get_default_form_fields_meta( $field[ 'name' ] ) );
			if( $field_meta ) {
				// Update field metadata
				bookacti_update_metadata( 'form_field', $field_id, $field_meta );
			}
			
			// Get field editor HTML
			$field_html = bookacti_diplay_form_field_for_editor( $field_data, $form_id, false );
			
			wp_send_json( array( 'status' => 'success', 'field_data' => $field_data, 'field_html' => $field_html ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiUpdateFormField', 'bookacti_controller_update_form_field', 10 );