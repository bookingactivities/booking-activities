<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


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


// GET A UNIQUE TEMPLATE SETTING MADE FROM A COMBINASON OF MULTIPLE TEMPLATE SETTINGS
function bookacti_get_mixed_template_settings( $template_ids ) {
	
	$templates_settings = bookacti_get_templates_settings( $template_ids );
	
	if( count( $templates_settings ) > 1 ) {
		$return_array = reset( $templates_settings );
	} else {
		$return_array = $templates_settings;
	}
	
	return apply_filters( 'bookacti_mixed_template_settings', $return_array, $templates_settings, $template_ids );
}