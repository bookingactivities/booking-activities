<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Validate template basic data
 *
 * @since	1.0.6
 * @param	string		$template_title
 * @param	string	$template_start Format 'YYYY-MM-DD'
 * @param	string	$template_end	Format 'YYYY-MM-DD'
 * @return	array
 */
function bookacti_validate_template_data( $template_title, $template_start, $template_end ) {

    //Init var to check with worst case
    $is_template_start_before_end	= false;
	
    //Prepare var that will be used to check the conditions
    $start_date = strtotime( $template_start );
    $end_date   = strtotime( $template_end );
	
    //Make the tests to validate the var
    if( $start_date <= $end_date ) { $is_template_start_before_end = true; }
	
    $return_array = array();
    $return_array['status'] = 'valid';
    $return_array['errors'] = array();
    if( ! $is_template_start_before_end ) {
        $return_array['status'] = 'not_valid';
		$return_array['errors'][] = 'error_template_end_before_begin';
    }
    
    return apply_filters( 'bookacti_validate_template_data', $return_array, $template_title, $template_start, $template_end );
}


/**
 * Format template managers
 * 
 * @param array $template_managers
 * @return array
 */
function bookacti_format_template_managers( $template_managers = array() ) {
	
	$template_managers = bookacti_ids_to_array( $template_managers );
	
	// If user is not super admin, add him automatically in the template managers list if he isn't already
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	if( ! is_super_admin() && ! $bypass_template_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $template_managers, true ) ) {
			$template_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage templates
	foreach( $template_managers as  $i => $template_manager ) {
		if( empty( $template_manager )
		|| ( ! user_can( $template_manager, 'bookacti_read_templates' )
		&&	 ! user_can( $template_manager, 'bookacti_edit_templates' ) ) ) {
			unset( $template_managers[ $i ] );
		}
	}
	
	return apply_filters( 'bookacti_template_managers', $template_managers );
}


/**
 * Format template settings
 * @version 1.7.17
 * @param array $raw_settings
 * @return array
 */
function bookacti_format_template_settings( $raw_settings ) {
	if( empty( $raw_settings ) ) { $raw_settings = array(); }
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_template_default_settings', array(
		'minTime'					=> '00:00',
		'maxTime'					=> '00:00',
		'snapDuration'				=> '00:05'
	) );
	
	$settings = array();
		
	// Check if all templates settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $raw_settings[ $setting_key ] ) && is_string( $raw_settings[ $setting_key ] ) ){ $raw_settings[ $setting_key ] = stripslashes( $raw_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $raw_settings[ $setting_key ] ) ? $raw_settings[ $setting_key ] : $setting_default_value;
	}
	
	// Format 24-h times: minTime, maxTime, snapDuration
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $settings[ 'minTime' ] ) )		{ $settings[ 'minTime' ] = $default_settings[ 'minTime' ]; }
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $settings[ 'maxTime' ] ) )		{ $settings[ 'maxTime' ] = $default_settings[ 'maxTime' ]; }
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $settings[ 'snapDuration' ] ) )	{ $settings[ 'snapDuration' ] = $default_settings[ 'snapDuration' ]; }
	
	// Make sure minTime is before maxTime
	// If maxTime is 00:xx change it to 24:xx
	if( $settings[ 'maxTime' ] === '00:00' ) { $settings[ 'maxTime' ] = '24:00'; }
	// If minTime >= maxTime, permute values
	if( intval( substr( $settings[ 'minTime' ], 0, 2 ) ) >= substr( $settings[ 'maxTime' ], 0, 2 ) ) { 
		$temp_max = $settings[ 'maxTime' ];
		$settings[ 'maxTime' ] = $settings[ 'minTime' ]; 
		$settings[ 'minTime' ] = $temp_max;
	}
	
	// Make sure snapDuration is not null
	if( $settings[ 'snapDuration' ] === '00:00' ) { $settings[ 'snapDuration' ] = '00:01'; }
	
	return apply_filters( 'bookacti_template_settings_formatted', $settings, $raw_settings, $default_settings );
}


/**
 * Format activity managers
 * 
 * @param array $activity_managers
 * @return array
 */
function bookacti_format_activity_managers( $activity_managers = array() ) {
	
	$activity_managers = bookacti_ids_to_array( $activity_managers );
	
	// If user is not super admin, add him automatically in the activity managers list if he isn't already
	$bypass_activity_managers_check = apply_filters( 'bypass_activity_managers_check', false );
	if( ! is_super_admin() && ! $bypass_activity_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $activity_managers, true ) ) {
			$activity_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage activities
	foreach( $activity_managers as  $i => $activity_manager ) {
		if( empty( $activity_manager ) || ! user_can( $activity_manager, 'bookacti_edit_activities' ) ) {
			unset( $activity_managers[ $i ] );
		}
	}
	
	return apply_filters( 'bookacti_activity_managers', $activity_managers );
}


/**
 * Format activity settings
 * @version 1.7.17
 * @param array $activity_settings
 * @return array
 */
function bookacti_format_activity_settings( $activity_settings ) {
	if( empty( $activity_settings ) ) { $activity_settings = array(); }
	
	// Default settings
	$init_default_settings = array(
		'unit_name_singular'		=> '',
		'unit_name_plural'			=> '',
		'show_unit_in_availability'	=> 0,
		'places_number'				=> 0,
		'min_bookings_per_user'		=> 0,
		'max_bookings_per_user'		=> 0,
		'max_users_per_event'		=> 0,
		'booking_changes_deadline'	=> -1,
		'allowed_roles'				=> array()
	);
	$default_settings = apply_filters( 'bookacti_activity_default_settings', $init_default_settings );
	
	$settings = array();
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $activity_settings[ $setting_key ] ) && is_string( $activity_settings[ $setting_key ] ) ){ $activity_settings[ $setting_key ] = stripslashes( $activity_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $activity_settings[ $setting_key ] ) ? $activity_settings[ $setting_key ] : $setting_default_value;
	}
	
	// Sanitize by type
	$keys_by_type = array( 
		'str'	=> array( 'unit_name_singular', 'unit_name_plural' ),
		'bool'	=> array( 'show_unit_in_availability' ),
		'int'	=> array( 'places_number', 'min_bookings_per_user', 'max_bookings_per_user', 'max_users_per_event', 'booking_changes_deadline' ),
		'array'	=> array( 'allowed_roles' )
	);
	$settings = bookacti_sanitize_values( $default_settings, $settings, $keys_by_type, array_diff_key( $settings, $init_default_settings ) );
	
	return apply_filters( 'bookacti_activity_settings', $settings, $activity_settings, $default_settings );
}


/**
 * Format event settings
 * @version 1.7.17
 * @param array $event_settings
 * @return array
 */
function bookacti_format_event_settings( $event_settings ) {
	if( empty( $event_settings ) ) { $event_settings = array(); }
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_event_default_settings', array() );
	
	$settings = array();
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $event_settings[ $setting_key ] ) && is_string( $event_settings[ $setting_key ] ) ){ $event_settings[ $setting_key ] = stripslashes( $event_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $event_settings[ $setting_key ] ) ? $event_settings[ $setting_key ] : $setting_default_value;
	}
	
	return apply_filters( 'bookacti_event_settings', $settings, $event_settings, $default_settings );
}


/**
 * Validate event fields
 * @version 1.5.8
 * @param int $event_id
 * @param int $event_availability
 * @param string $repeat_freq
 * @param string $repeat_from
 * @param string $repeat_to
 * @return array
 */
function bookacti_validate_event( $event_id, $event_availability, $repeat_freq, $repeat_from, $repeat_to ) {
	// Get info required
	$min_avail          = bookacti_get_min_availability( $event_id );
	$min_period         = bookacti_get_min_period( NULL, $event_id );
	$repeat_from_time   = strtotime( $repeat_from );
	$repeat_to_time     = strtotime( $repeat_to );
	$max_from           = $min_period ? strtotime( $min_period[ 'from' ] ) : '';
	$min_to             = $min_period ? strtotime( $min_period[ 'to' ] ) : '';
	
	// Init var to check with worst case
	$isAvailSupToBookings           = $min_avail ? false : true;
	$isRepeatFromBeforeFirstBooked  = false;
	$isRepeatToAfterLastBooked      = false;

	// Make the tests
	if( $min_avail ) {
		if( intval( $event_availability ) >= intval( $min_avail ) ) {
			$isAvailSupToBookings = true;
		}
	}
	if( $min_period ) {
		if( $repeat_from_time <= $max_from ){ $isRepeatFromBeforeFirstBooked = true; }
		if( $repeat_to_time   >= $min_to )  { $isRepeatToAfterLastBooked = true; }
	}

	$return_array = array();
	$return_array['status'] = 'valid';
	$return_array['errors'] = array();
	if( ! $isAvailSupToBookings ){
		$return_array['status'] = 'not_valid';
		$return_array['min_availability'] = $min_avail;
		array_push ( $return_array['errors'], 'error_less_avail_than_bookings' );
	}
	if( $repeat_freq !== 'none' && $min_period && ( ! $isRepeatFromBeforeFirstBooked || ! $isRepeatToAfterLastBooked ) ){
		$return_array['status'] = 'not_valid';
		$return_array['from'] = date( 'Y-m-d', $max_from );
		$return_array['to'] = date( 'Y-m-d', $min_to );
		array_push ( $return_array['errors'], 'error_booked_events_out_of_period' );
	}
	if( ( $repeat_freq !== 'none' ) && ( ! $repeat_from || ! $repeat_to ) ){
		$return_array['status'] = 'not_valid';
		array_push ( $return_array['errors'], 'error_repeat_period_not_set' );
	}

	return $return_array;
}



// GROUPS OF EVENTS

/**
 * Validate group of events data input
 * 
 * @since 1.1.0
 * 
 * @param string $group_title
 * @param int $category_id
 * @param string $category_title
 * @param array $events
 * @return array
 */
function bookacti_validate_group_of_events_data( $group_title, $category_id, $category_title, $events ) {
	
	//Init var to check with worst case
	$is_title			= false;
	$is_category		= false;
	$is_category_title	= false;
	$is_events			= false;
	
	//Make the tests to validate the var
	if( is_string( $group_title ) && $group_title !== '' )		{ $is_title = true; }
	if( is_numeric( $category_id ) )							{ $is_category = true; }
	if( is_string( $category_title ) && $category_title !== '' ){ $is_category_title = true; }
	if( is_array( $events ) && count( $events ) >= 2 )			{ $is_events = true; }
	
	$return_array = array();
	$return_array['status'] = 'valid';
	$return_array['errors'] = array();
	if( ! $is_title ) {
		$return_array['status'] = 'not_valid';
		$return_array['errors'][] = 'error_missing_title';
	}
	if( ! $is_category && ! $is_category_title ) {
		$return_array['status'] = 'not_valid';
		$return_array['errors'][] = 'error_missing_title';
	}
	if( ! $is_events ) {
		$return_array['status'] = 'not_valid';
		$return_array['errors'][] = 'error_select_at_least_two_events';
	}
	
	return apply_filters( 'bookacti_validate_group_of_events_data', $return_array, $group_title, $category_id, $category_title, $events );
}


/**
 * Format group of events data or apply default value
 * 
 * @since 1.1.0
 * @version 1.7.17
 * @param array $group_settings
 * @return array
 */
function bookacti_format_group_of_events_settings( $group_settings ) {
	if( empty( $group_settings ) ) { $group_settings = array(); }
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_group_of_events_default_settings', array() );
	
	$settings = array();
		
	// Check if all templates settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $group_settings[ $setting_key ] ) && is_string( $group_settings[ $setting_key ] ) ){ $group_settings[ $setting_key ] = stripslashes( $group_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $group_settings[ $setting_key ] ) ? $group_settings[ $setting_key ] : $setting_default_value;
	}
	
	return apply_filters( 'bookacti_group_of_events_settings', $settings, $group_settings, $default_settings );
}




// GROUP CATEGORIES

/**
 * Validate group activity data input
 * 
 * @since 1.1.0
 * 
 * @param string $title
 * @return array
 */
function bookacti_validate_group_category_data( $title ) {
	
	//Init var to check with worst case
	$is_title	= false;
	
	//Make the tests to validate the var
	if( ! empty( $title ) )	{ $is_title = true; }

	$return_array = array();
	$return_array['status'] = 'valid';
	$return_array['errors'] = array();
	if( ! $is_title ) {
		$return_array['status'] = 'not_valid';
		$return_array['errors'][] = 'error_missing_title';
	}
	
	return apply_filters( 'bookacti_validate_group_activity_data', $return_array, $title );
}


/**
 * Format group category data or apply default value
 * @since 1.1.0
 * @version 1.7.17
 * @param array $category_settings
 * @return array
 */
function bookacti_format_group_category_settings( $category_settings ) {
	if( empty( $category_settings ) ) { $category_settings = array(); }
	
	// Default settings
	$init_default_settings = array( 
		'min_bookings_per_user'		=> 0,
		'max_bookings_per_user'		=> 0,
		'max_users_per_event'		=> 0,
		'booking_changes_deadline'	=> -1,
		'started_groups_bookable'	=> -1,
		'allowed_roles'				=> array()
	);
	$default_settings = apply_filters( 'bookacti_group_category_default_settings', $init_default_settings );
	
	$settings = array();
		
	// Check if all templates settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $category_settings[ $setting_key ] ) && is_string( $category_settings[ $setting_key ] ) ){ $category_settings[ $setting_key ] = stripslashes( $category_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $category_settings[ $setting_key ] ) ? $category_settings[ $setting_key ] : $setting_default_value;
	}
	
	// Sanitize by type
	$keys_by_type = array( 
		'int'	=> array( 'min_bookings_per_user', 'max_bookings_per_user', 'max_users_per_event', 'booking_changes_deadline', 'started_groups_bookable' ),
		'array'	=> array( 'allowed_roles' )
	);
	$settings = bookacti_sanitize_values( $default_settings, $settings, $keys_by_type, array_diff_key( $settings, $init_default_settings ) );
	
	// started_groups_bookable
	if( $settings[ 'started_groups_bookable' ] > 1 || $settings[ 'started_groups_bookable' ] < -1 ) { $settings[ 'started_groups_bookable' ] = $default_settings[ 'started_groups_bookable' ]; }
	
	return apply_filters( 'bookacti_group_category_settings', $settings, $category_settings, $default_settings );
}