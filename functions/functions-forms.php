<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// FORM LIST

/**
 * Format form filters
 * @since 1.5.0
 * @param array $filters 
 * @return array
 */
function bookacti_format_form_filters( $filters = array() ) {

	$default_filters = apply_filters( 'bookacti_default_booking_filters', array(
		'id'			=> array(), 
		'title'			=> '', 
		'active'		=> false,
		'order_by'		=> array( 'id' ), 
		'order'			=> 'desc',
		'offset'		=> 0,
		'per_page'		=> 0
	));

	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];

		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'templates' ) ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( is_array( $current_value ) ) {
				// Check if current user is allowed to manage desired templates, or unset them
				if( ! empty( $current_value ) ) {
					foreach( $current_value as $i => $template_id ) {
					if( ! is_numeric( $template_id ) || ! bookacti_user_can_manage_template( $template_id ) ) {
							unset( $current_value[ $i ] );
						}
					}
				}
				// Re-check if the template list is empty because some template filters may have been removed
				// and get all allowed templates if it is empty
				if( empty( $current_value ) ) {
					$current_value = array_keys( bookacti_fetch_templates() );
				}
			}
			else { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'id' ), true ) ) {
			if( is_numeric( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( $i = array_search( 'all', $current_value ) !== false ) { unset( $current_value[ $i ] ); }

		} else if( in_array( $filter, array( 'title' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			
		} else if( in_array( $filter, array( 'active' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )	{ $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) ){ $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 
				'id', 
				'title'
			);
			if( is_string( $current_value ) )	{ 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) )				{ $current_value = $default_value; }
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ){ $current_value = $default_value; }

		}
				
		$formatted_filters[ $filter ] = $current_value;
	}

	return $formatted_filters;
}




// FORM EDITOR

/**
 * Display 'managers' metabox content for forms
 * @since 1.5.0
 */
function bookacti_display_form_managers_meta_box( $form ) {
	
	// Get current form managers option list
	$managers_already_added = array();
	$manager_ids = $form->id !== 'new' ? bookacti_get_form_managers( $form->id ) : array();
	$current_managers_options_list = '';
	if( ! empty( $manager_ids ) ) {
		foreach( $manager_ids as $manager_id ) {
			$userdata = get_userdata( $manager_id );
			$display_name = $userdata->user_login;
			if( ! empty( $userdata->first_name ) && ! empty( $userdata->last_name ) ){
				$display_name = $userdata->first_name  . ' ' . $userdata->last_name . ' (' . $userdata->user_login . ')';
			}
			$display_name = apply_filters( 'bookacti_managers_name_display', $display_name, $userdata );
			$current_managers_options_list .= '<option value="' . $manager_id . '" selected >' . $display_name . '</option>';
			$managers_already_added[] = $manager_id;
		}
	}
	
	// Get available form managers option list
	$in_roles		= apply_filters( 'bookacti_managers_roles', array() );
	$not_in_roles	= apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ) );
	$user_query		= new WP_User_Query( array( 'role__in' => $in_roles, 'role__not_in' => $not_in_roles ) );
	$users			= $user_query->get_results();
	$available_managers_options_list = '';
	if ( ! empty( $users ) ) {
		foreach( $users as $user ) {
			if( $user->has_cap( 'bookacti_edit_forms' ) ) {
				$userdata = get_userdata( $user->ID );
				$display_name = $userdata->user_login;
				if( $userdata->first_name && $userdata->last_name ){
					$display_name = $userdata->first_name  . ' ' . $userdata->last_name . ' (' . $userdata->user_login . ')';
				}
				$display_name = apply_filters( 'bookacti_managers_name_display', $display_name, $userdata );
				$disabled = in_array( $user->ID, $managers_already_added, true ) ? 'disabled style="display:none;"' : '';
				
				$available_managers_options_list .= '<option value="' . esc_attr( $user->ID ) . '" ' . $disabled . ' >' . esc_html( $display_name ) . '</option>';
			}
		}
	}
	
	?>
	<div id='bookacti-form-managers-container' class='bookacti-items-container' data-type='users' >
		<label id='bookacti-form-managers-title' class='bookacti-fullwidth-label' for='bookacti-add-new-form-managers-select-box' >
		<?php 
			esc_html_e( 'Who can manage this form?', BOOKACTI_PLUGIN_NAME );
			$tip  = __( 'Choose who is allowed to access this form.', BOOKACTI_PLUGIN_NAME );
			/* translators: %s = capabilities name */
			$tip .= ' ' . sprintf( __( 'All administrators already have this privilege. If the selectbox is empty, it means that no users have capabilities such as %s.', BOOKACTI_PLUGIN_NAME ), '"bookacti_edit_forms"' );
			/* translators: %1$s = Points of sale add-on link. %2$s = User role editor plugin name. */
			$tip .= '<br/>' 
				 .  sprintf( __( 'Point of sale managers from %1$s add-on have these capabilities. If you want to grant a user these capabilities, use a plugin such as %2$s.', BOOKACTI_PLUGIN_NAME ), 
						'<a href="https://booking-activities.fr/en/downloads/points-of-sale/?utm_source=plugin&utm_medium=plugin&utm_campaign=points-of-sale&utm_content=infobulle-permission" target="_blank" >' . esc_html( __( 'Points of Sale', BOOKACTI_PLUGIN_NAME ) ) . '</a>',
						'<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>'
					);
			bookacti_help_tip( $tip );
		?>
		</label>
		<div id='bookacti-add-form-managers-container' class='bookacti-add-items-container' >
			<select id='bookacti-add-new-form-managers-select-box' class='bookacti-add-new-items-select-box' >
			<?php echo $available_managers_options_list; ?>
			</select>
			<button type='button' id='bookacti-add-form-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', BOOKACTI_PLUGIN_NAME ); ?></button>
		</div>
		<div id='bookacti-form-managers-list-container' class='bookacti-items-list-container' >
			<select name='form-managers[]' id='bookacti-form-managers-select-box' class='bookacti-items-select-box' multiple >
			<?php echo $current_managers_options_list; ?>
			</select>
			<button type='button' id='bookacti-remove-form-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', BOOKACTI_PLUGIN_NAME ); ?></button>
		</div>
	</div>
	<?php
}


/**
 * Display 'publish' metabox content for forms
 * @since 1.5.0
 * @param object $form
 */
function bookacti_display_form_publish_meta_box( $form ) {
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions'>
			<div id='delete-action'>
			<?php
				if ( current_user_can( 'bookacti_delete_forms' ) ) {
					if( ! $form->active ) {
						echo '<a href="' . esc_url( wp_nonce_url( get_admin_url() . 'admin.php?page=bookacti_forms', 'delete-form_' . $form->id ) . '&action=delete&active=0&form_id=' . $form->id ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Delete Permanently', 'forms', BOOKACTI_PLUGIN_NAME )
							. '</a>';
					} else {
						echo '<a href="' . esc_url( wp_nonce_url( get_admin_url() . 'admin.php?page=bookacti_forms', 'trash-form_' . $form->id ) . '&action=trash&form_id=' . $form->id ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Move to trash', 'forms', BOOKACTI_PLUGIN_NAME )
							. '</a>';
					}
				}
			?>
			</div>

			<div id='publishing-action'>
				<span class='spinner'></span>
				<input name='save' type='submit' class='button button-primary button-large' id='publish' value='<?php echo $form->id === 'new' ? esc_attr_x( 'Publish', 'forms', BOOKACTI_PLUGIN_NAME ) : esc_attr_x( 'Update', 'forms', BOOKACTI_PLUGIN_NAME ); ?>' />
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}



// PERMISSIONS

/**
 * Check if user is allowed to manage form
 * @since 1.5.0
 * @param int $template_id
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_can_manage_form( $form_id, $user_id = false ) {

	$user_can_manage_form = false;
	$bypass_form_managers_check = apply_filters( 'bookacti_bypass_form_managers_check', false );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin() || $bypass_form_managers_check ) { $user_can_manage_form = true; }
	else {
		$admins = bookacti_get_form_managers( $form_id );
		if( in_array( $user_id, $admins, true ) ) { $user_can_manage_form = true; }
	}

	return apply_filters( 'bookacti_user_can_manage_form', $user_can_manage_form, $form_id, $user_id );
}


/**
 * Get form managers
 * @since 1.5.0
 * @param int $activity_id
 * @return array
 */
function bookacti_get_form_managers( $form_id ) {
	return bookacti_get_managers( 'form', $form_id );
}


/**
 * Format form managers
 * @since 1.5.0
 * @param array $form_managers
 * @return array
 */
function bookacti_format_form_managers( $form_managers = array() ) {
	
	$form_managers = bookacti_ids_to_array( $form_managers );
	
	// If user is not super admin, add him automatically in the form managers list if he isn't already
	$bypass_form_managers_check = apply_filters( 'bookacti_bypass_form_managers_check', false );
	if( ! is_super_admin() && ! $bypass_form_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $form_managers, true ) ) {
			$form_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage forms
	foreach( $form_managers as  $i => $form_manager ) {
		if( empty( $form_manager )
		|| ! user_can( $form_manager, 'bookacti_edit_forms' ) ) {
			unset( $form_managers[ $i ] );
		}
	}
	
	return apply_filters( 'bookacti_form_managers', $form_managers );
}