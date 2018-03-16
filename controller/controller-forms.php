<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


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




// BOOKING FORMS CRUD

/**
 * AJAX Controller - Insert a booking form
 * @since 1.5.0
 */
function bookacti_controller_insert_form() {
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_form', 'nonce_insert_or_update_form', false );
	$is_allowed		= current_user_can( 'bookacti_create_forms' );

	if( ! $is_nonce_valid || ! $is_allowed ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
	
	$form_title		= sanitize_text_field( stripslashes( $_REQUEST['form_title'] ) );
	$managers_array	= isset( $_REQUEST['form-managers'] ) ? bookacti_ids_to_array( $_REQUEST['form-managers'] ) : array();
	$form_managers	= bookacti_format_form_managers( $managers_array );
	
	// Create the form
	$form_id = bookacti_create_form( $form_title );

	// Feedback error
	if( $form_id === false ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'query_failed' ) );
	}

	// Insert Managers
	bookacti_insert_managers( 'form', $form_id, $form_managers );
	
	add_action( 'bookacti_form_created', $form_id );
	
	wp_send_json( array( 'status' => 'success', 'form_id' => $form_id, 'message' => esc_html__( 'The booking form has been created.', BOOKACTI_PLUGIN_NAME ) ) );
}
add_action( 'wp_ajax_bookactiInsertForm', 'bookacti_controller_insert_form' );


/**
 * AJAX Controller - Update a booking form
 * @since 1.5.0
 */
function bookacti_controller_update_form() {
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_form', 'nonce_insert_or_update_form', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' );

	if( ! $is_nonce_valid || ! $is_allowed ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
	
	$form_id		= intval( $_REQUEST['form_id'] );
	$form_title		= sanitize_text_field( stripslashes( $_REQUEST['form_title'] ) );
	$managers_array	= isset( $_REQUEST['form-managers'] ) ? bookacti_ids_to_array( $_REQUEST['form-managers'] ) : array();
	$form_managers	= bookacti_format_form_managers( $managers_array );
	
	// Create the form
	$updated = bookacti_update_form( $form_id, $form_title );

	// Feedback error
	if( $updated === false ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'query_failed' ) );
	}

	// Insert Managers
	bookacti_update_managers( 'form', $form_id, $form_managers );
	
	add_action( 'bookacti_form_updated', $form_id );

	wp_send_json( array( 'status' => 'success', 'message' => esc_html__( 'The booking form has been updated.', BOOKACTI_PLUGIN_NAME ) ) );
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
		$can_delete_form = current_user_can( 'bookacti_delete_forms' );
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