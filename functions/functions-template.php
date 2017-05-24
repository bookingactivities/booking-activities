<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// PERMISSIONS
	// CHECK IF USER IS ALLOWED TO MANAGE TEMPLATE
	function bookacti_user_can_manage_template( $template_id, $user_id = false ) {

		$user_can_manage_template = false;
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		if( is_super_admin() || $bypass_template_managers_check ) { $user_can_manage_template = true; }
		else {
			$admins = bookacti_get_template_managers( $template_id );
			if( in_array( $user_id, $admins ) ) { $user_can_manage_template = true; }
		}

		return apply_filters( 'bookacti_user_can_manage_template', $user_can_manage_template, $template_id, $user_id );
	}


	// CHECK IF USER IS ALLOWED TO MANAGE ACTIVITY
	function bookacti_user_can_manage_activity( $activity_id, $user_id = false ) {

		$user_can_manage_activity = false;
		$bypass_activity_managers_check = apply_filters( 'bypass_activity_managers_check', false );
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		if( is_super_admin() || $bypass_activity_managers_check ) { $user_can_manage_activity = true; }
		else {
			$admins = bookacti_get_activity_managers( $activity_id );
			if( in_array( $user_id, $admins ) ) { $user_can_manage_activity = true; }
		}

		return apply_filters( 'bookacti_user_can_manage_activity', $user_can_manage_activity, $activity_id, $user_id );
	}
	
	
	// GET TEMPLATE MANAGERS
	function bookacti_get_template_managers( $template_id ) {
		return bookacti_get_managers( 'template', $template_id );
	}
	
	
	// GET ACTIVITY MANAGERS
	function bookacti_get_activity_managers( $activity_id ) {	
		return bookacti_get_managers( 'activity', $activity_id );
	}

	

// TEMPLATE X ACTIVITIES
	// RETRIEVE TEMPLATE ACTIVITIES LIST
	function bookacti_get_template_activities_list( $template_id ) {

		$list = '';
		$activities = bookacti_fetch_activities();
		$template_activities = bookacti_get_activities_by_template_ids( $template_id );

		foreach ( $activities as $activity ) {
			if( in_array( $activity->id, $template_activities ) ) {
				$title = apply_filters( 'bookacti_translate_text', stripslashes( $activity->title ) );

				$list	.=	"<div class='activity-row'>"
						.       "<div class='activity-show-hide' >"
						.           "<img src='" . esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/show.png" ) . "' data-activity-id='" . esc_attr( $activity->id ) . "' data-activity-visible='1' />"
						.       "</div>"
						.       "<div class='activity-container'>"
						.           "<div "
						.				"class='fc-event ui-draggable ui-draggable-handle' "
						.				"data-title='"			. esc_attr( stripslashes( $activity->title ) ) . "' "
						.				"data-activity-id='"	. esc_attr( $activity->id )				. "' "
						.				"data-color='"			. esc_attr( $activity->color )			. "' "
						.				"data-availability='"	. esc_attr( $activity->availability	)	. "' "
						.				"data-start='12:00' "
						.				"data-duration='"		. esc_attr( $activity->duration )		. "' "
						.				"data-resizable='"		. esc_attr( $activity->is_resizable	)	. "' "
						.           ">"
						.               $title
						.           "</div>"
						.       "</div>";
				if( current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity->id ) ) {	
					$list .=	"<div class='activity-gear' >"
						.           "<img src='" . esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/gear.png" ) . "' data-activity-id='" . esc_attr( $activity->id ) . "' />"
						.       "</div>";
				}
				$list	.=	"</div>";
			}
		}

		return $list;
	}



// TEMPLATE SETTINGS
	/**
	 * Get templates settings
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 * @param	array|int $template_ids Array of template ids or single template id
	 * @return	array
	 */
	function bookacti_get_templates_settings( $template_ids ) {
		
		if( is_numeric( $template_ids ) ) {
			$template_ids = array( $template_ids );
		}
				
		$is_empty = empty( $template_ids );
				
		// Get templates range (start and end dates)
		$templates_range = array();
		$templates = bookacti_fetch_templates( true );
		foreach( $templates as $template ) {
			// If empty, take them all
			if( $is_empty ) {
				$template_ids[] = $template->id;
			}
			$templates_range[ $template->id ] = array( 'start' => $template->start_date, 'end' => $template->end_date );
		}
		
		
		$return_array = array();
		
		if( is_numeric( $template_ids ) || ( is_array( $template_ids ) && count( $template_ids ) === 1 ) ) {
			$template_id	= is_numeric( $template_ids ) ? $template_ids : $template_ids[0];
			$template_meta	= bookacti_get_metadata( 'template', $template_id );
			$return_array	= array_merge( $template_meta, $templates_range[ $template_id ] );
		} else {
			foreach( $template_ids as $template_id ) {
				$template_meta					= bookacti_get_metadata( 'template', $template_id );
				$return_array[ $template_id ]	= array_merge( $template_meta, $templates_range[ $template_id ] );
			}
		}

		return $return_array;
	}


	/**
	 * Get a unique template setting made from a combination of multiple template settings
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 * @param	array|int $template_ids Array of template ids or single template id
	 * @return	array
	 */
	function bookacti_get_mixed_template_settings( $template_ids ) {
		
		$templates_settings = bookacti_get_templates_settings( $template_ids );
		$mixed_settings		= array();
		
		if( count( $templates_settings ) > 1 ) {
			foreach( $templates_settings as $settings ){
				if( isset( $settings[ 'minTime' ] ) ) {
					//Keep the lower value
					if(  ! isset( $mixed_settings[ 'minTime' ] ) 
						|| isset( $mixed_settings[ 'minTime' ] ) && strtotime( $settings[ 'minTime' ] ) < strtotime( $mixed_settings[ 'minTime' ] ) ) {

						$mixed_settings[ 'minTime' ] = $settings[ 'minTime' ];
					} 
				}
				if( isset( $settings[ 'maxTime' ] ) ) {
					//Keep the higher value
					if(  ! isset( $mixed_settings[ 'maxTime' ] ) 
						|| isset( $mixed_settings[ 'maxTime' ] ) && strtotime( $settings[ 'maxTime' ] ) > strtotime( $mixed_settings[ 'maxTime' ] ) ) {

						$mixed_settings[ 'maxTime' ] = $settings[ 'maxTime' ];
					} 
				}
			}
			
			$templates_mixed_range	= bookacti_get_mixed_template_range( $template_ids );
			$mixed_settings			= array_merge( $mixed_settings, $templates_mixed_range );
		} else {
			$mixed_settings = $templates_settings;
		}

		return apply_filters( 'bookacti_mixed_template_settings', $mixed_settings, $templates_settings, $template_ids );
	}



// USER DEFAULT TEMPLATE
	// GET USER DEFAULT TEMPLATE
	function bookacti_get_user_default_template( $user_id = false ) {

		$user_id = $user_id ? $user_id : get_current_user_id();

		$template_settings = get_option( 'bookacti_template_settings' );

		if( empty( $template_settings ) ){
			$template_settings = array();
		}
		if( empty( $template_settings['default_template_per_user'] ) ) {
			$template_settings['default_template_per_user'] = array();
		} 
		if( empty( $template_settings['default_template_per_user'][ $user_id ] ) ) {
			$template_settings['default_template_per_user'][ $user_id ] = 0;
		} 

		return intval( $template_settings['default_template_per_user'][ $user_id ] );
	}


	// UPDATE USER DEFAULT TEMPLATE
	function bookacti_update_user_default_template( $template_id, $user_id = false ) {

		$user_id = $user_id ? $user_id : get_current_user_id();

		$template_settings = get_option( 'bookacti_template_settings' );
		if( ! is_array( $template_settings['default_template_per_user'] ) ) {
			$template_settings['default_template_per_user'] = array();
		}

		// If no change, return 0
		if( isset( $template_settings['default_template_per_user'][ $user_id ] )
		&&  $template_settings['default_template_per_user'][ $user_id ] === $template_id ) {
			return 0;
		}

		$template_settings['default_template_per_user'][ $user_id ] = $template_id;

		$is_updated = update_option( 'bookacti_template_settings', $template_settings );

		return $is_updated;
	}



// TEMPLATES X ACTIVITIES ASSOCIATION
	// UPDATE THE LIST OF TEMPLATES ASSOCIATED TO AN ACTIVITY ID
	function bookacti_update_templates_list_by_activity_id( $new_templates, $activity_id ) {
		$old_templates = bookacti_get_templates_by_activity_ids( $activity_id );
		
		// Unset templates already added
		foreach( $new_templates as $i => $new_template ) {
			foreach( $old_templates as $j => $old_template ) {
				if( $new_template === $old_template ) {
					unset( $new_templates[ $i ] );
					unset( $old_templates[ $j ] );
				}
			}
		}
		
		// Insert new templates
		$inserted = 0;
		if( count( $new_templates ) > 0 ) {
			$inserted = bookacti_insert_templates_x_activities( $new_templates, array( $activity_id ) );
		}
		
		// Delete old templates
		$deleted = 0;
		if( count( $old_templates ) > 0 ) {
			$deleted = bookacti_delete_templates_x_activities( $old_templates, array( $activity_id ) );
		}
		
		return $inserted + $deleted;
	}

	
	// UPDATE THE LIST OF ACTIVITIES ASSOCIATED TO A TEMPLATE ID
	function bookacti_bind_activities_to_template( $new_activities, $template_id ) {
		$old_activities = bookacti_get_activities_by_template_ids( $template_id, true );
		
		// Unset templates already added
		foreach( $new_activities as $i => $new_activity ) {
			foreach( $old_activities as $j => $old_activity ) {
				if( $new_activity === $old_activity ) {
					unset( $new_activities[ $i ] );
				}
			}
		}
		
		// Insert new activity bounds
		$inserted = 0;
		if( count( $new_activities ) > 0 ) {
			$inserted = bookacti_insert_templates_x_activities( array( $template_id ), $new_activities );
		}
		
		return $inserted;
	}


