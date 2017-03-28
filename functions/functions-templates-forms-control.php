<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

//Make sure the template period include all booked events
function bookacti_validate_template( $template_id, $template_start, $template_end ) {
    //Get min period possible
    $dates = bookacti_get_min_period( $template_id );

    //Init var to check with worst case
	$is_admin						= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
    $is_min                         = true;
    $is_max                         = true;
    $is_template_start_inf_to_min   = false;
    $is_start_before_end			= false;
    $is_template_end_sup_to_max     = false;
    $is_period_ok		            = false;

    //Prepare var that will be used to check the conditions
    $start_date = strtotime( $template_start );
    $end_date   = strtotime( $template_end );
    $min_date   = strtotime( $dates['from'] );
    $max_date   = strtotime( $dates['to'] );
	
	
    //Make the tests to validate the var
    if( $dates['from'] === null )   { $is_min = false; }
    if( $dates['to']   === null )   { $is_max = false; }

    if( $is_min && ( $start_date <= $min_date ) )   { $is_template_start_inf_to_min = true; }
    if( $is_max && ( $end_date   >= $max_date ) )   { $is_template_end_sup_to_max   = true; }
    if( $start_date <= $end_date )					{ $is_start_before_end   = true; }
	
	if( $is_start_before_end ) {
		if( ! $is_min && $is_template_end_sup_to_max )                      { $is_period_ok = true; }
		if( ! $is_max && $is_template_start_inf_to_min )                    { $is_period_ok = true; }
		if( ! $is_max && ! $is_min )                                        { $is_period_ok = true; }
		if( $is_template_start_inf_to_min && $is_template_end_sup_to_max )  { $is_period_ok = true; }
	}
	
    $return_array = array();
    $return_array['status'] = 'valid';
    $return_array['errors'] = array();
    if( ! $is_period_ok ) {
        $return_array['status'] = 'not_valid';
		if( $is_start_before_end ) {
			$return_array['errors'][] = 'error_bookings_out_of_template';
		} else {
			$return_array['errors'][] = 'error_template_end_before_begin';
		}
    }
	if( ! $is_admin ) {
		$return_array['status'] = 'not_valid';
        $return_array['errors'][] = 'error_not_allowed';
	}
    
    return $return_array;
}


// Format template managers
function bookacti_format_template_managers( $template_managers = array() ) {
	
	$template_managers = bookacti_ids_to_array( $template_managers );
	
	// If user is not super admin, add him automatically in the template managers list if he isn't already
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	if( ! is_super_admin() && ! $bypass_template_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $template_managers ) ) {
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


// Format template settings
function bookacti_format_template_settings( $template_settings ) {
	
	if( empty( $template_settings ) ) {
		$template_settings = array();
	}
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_template_default_settings', array(
		'minTime'				=> '08:00',
		'maxTime'				=> '20:00',
	) );
	
	$settings = array();
		
	// Check if all templates settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( is_string( $template_settings[ $setting_key ] ) ){ $template_settings[ $setting_key ] = stripslashes( $template_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = ! empty( $template_settings[ $setting_key ] ) ? $template_settings[ $setting_key ] : $setting_default_value;
	}

	// Make sure minTime is before maxTime
	// If maxTime is 00:xx change it to 24:xx
	if( $settings[ 'maxTime' ] === '00:00' ) { $settings[ 'maxTime' ] = '24:00'; }
	// If minTime >= maxTime, permute values
	if( intval( substr( $settings[ 'minTime' ], 0, 2 ) ) >= substr( $settings[ 'maxTime' ], 0, 2 ) ) { 
		$temp_max = $settings[ 'maxTime' ];
		$settings[ 'maxTime' ] = $settings[ 'minTime' ]; 
		$settings[ 'minTime' ] = $temp_max;
	}
	
	return apply_filters( 'bookacti_template_settings', $settings );
}


// Format activity templates
function bookacti_format_activity_templates( $activity_templates = array(), $mandatory_templates = array() ) {
	
	$activity_templates		= bookacti_ids_to_array( $activity_templates );
	$mandatory_templates	= bookacti_ids_to_array( $activity_templates );
	
	// Add mandatory templates if they are not already
	if( ! empty( $mandatory_templates ) ) {
		foreach( $mandatory_templates as $mandatory_template ) {
			if( ! empty( $mandatory_template ) && ! in_array( $mandatory_template, $activity_templates ) ) {
				$activity_templates[] = $mandatory_template;
			}
		}
	}
	
	// Check permission to alter template
	foreach( $activity_templates as $i => $activity_template ) {
		if( ! current_user_can( 'bookacti_edit_templates' ) || ! bookacti_user_can_manage_template( $activity_template ) ) {
			unset( $activity_templates[ $i ] );
		}
	}
	
	return apply_filters( 'bookacti_activity_templates', $activity_templates );
}


// Format activity managers
function bookacti_format_activity_managers( $activity_managers = array() ) {
	
	$activity_managers = bookacti_ids_to_array( $activity_managers );
	
	// If user is not super admin, add him automatically in the activity managers list if he isn't already
	$bypass_activity_managers_check = apply_filters( 'bypass_activity_managers_check', false );
	if( ! is_super_admin() && ! $bypass_activity_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $activity_managers ) ) {
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


// Format activity settings
function bookacti_format_activity_settings( $activity_settings ) {
	
	if( empty( $activity_settings ) ) {
		$activity_settings = array();
	}
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_activity_default_settings', array(
		'unit_name_singular'		=> '',
		'unit_name_plural'			=> '',
		'show_unit_in_availability'	=> 0,
		'places_number'				=> 0
	) );
	
	$settings = array();
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( is_string( $activity_settings[ $setting_key ] ) ){ $activity_settings[ $setting_key ] = stripslashes( $activity_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = ( $activity_settings[ $setting_key ] !== null ) ? $activity_settings[ $setting_key ] : $setting_default_value;
	}
	
	return apply_filters( 'bookacti_activity_settings', $settings );
}


// Format event settings
function bookacti_format_event_settings( $event_settings ) {
	
	if( empty( $event_settings ) ) {
		$event_settings = array();
	}
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_event_default_settings', array() );
	
	$settings = array();
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( is_string( $event_settings[ $setting_key ] ) ){ $event_settings[ $setting_key ] = stripslashes( $event_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = ( $event_settings[ $setting_key ] !== null ) ? stripslashes( $event_settings[ $setting_key ] ) : $setting_default_value;
	}
	
	return apply_filters( 'bookacti_event_settings', $settings );
}


//Make sure the availability is higher than the bookings already made
function bookacti_validate_event( $event_id, $event_availability, $repeat_freq, $repeat_from, $repeat_to, $exceptions ) {
    //Get info required
    $min_avail          = bookacti_get_min_availability( $event_id );
    $min_period         = bookacti_get_min_period( NULL, $event_id );
    $bookings           = bookacti_get_bookings( NULL, $event_id );
    $bookings_array     = $bookings[$event_id];
    $repeat_from_time   = strtotime( $repeat_from );
    $repeat_to_time     = strtotime( $repeat_to );
    $max_from           = strtotime( $min_period['from'] );
    $min_to             = strtotime( $min_period['to'] );
    
    //Init var to check with worst case
    $isAvailSupToBookings           = false;
    $isRepeatFromBeforeFirstBooked  = false;
    $isRepeatToAfterLastBooked      = false;
    $bookedExcep                    = array();
    	
    //Make the tests
    if( $min_avail !== null ) {
        if( intval( $event_availability ) >= intval( $min_avail ) ) {
            $isAvailSupToBookings = true;
        }
    }
    if( $min_period !== null ) {
        if( $min_period['is_bookings'] > 0 ) {
            if( $repeat_from_time <= $max_from ){ $isRepeatFromBeforeFirstBooked = true; }
            if( $repeat_to_time   >= $min_to )  { $isRepeatToAfterLastBooked = true; }
        }
    }
    if( count ( $bookings ) > 0 && count( $exceptions ) > 0 ) {
        foreach( $bookings_array as $booking ) {
            foreach( $exceptions as $exception ){
                $booked_time    = strtotime( substr( $booking->event_start, 0, 10 ) );
                $exception_time = strtotime( $exception );
                if( $booked_time === $exception_time ){
                    $date = date('Y-m-d', $booked_time );
                    array_push( $bookedExcep, $date );
                }
            }
        }
    }
    
    $return_array = array();
    $return_array['status'] = 'valid';
    $return_array['errors'] = array();
    if( ! $isAvailSupToBookings ){
        $return_array['status'] = 'not_valid';
        $return_array['min_availability'] = $min_avail;
        array_push ( $return_array['errors'], 'error_less_avail_than_bookings' );
    }
    if( ( $repeat_freq !== 'none' ) && ( $min_period['is_bookings'] > 0 ) && ( ! $isRepeatFromBeforeFirstBooked || ! $isRepeatToAfterLastBooked ) ){
        $return_array['status'] = 'not_valid';
        $return_array['from'] = date( 'Y-m-d', $max_from );
        $return_array['to'] = date( 'Y-m-d', $min_to );
        array_push ( $return_array['errors'], 'error_booked_events_out_of_period' );
    }
    if( ( $repeat_freq !== 'none' ) && ( count( $bookedExcep ) > 0 ) ){
        $return_array['status'] = 'not_valid';
        $return_array['booked_exceptions'] = $bookedExcep;
        array_push ( $return_array['errors'], 'error_set_excep_on_booked_occur' );
    }
    if( ( $repeat_freq !== 'none' ) && ( ! $repeat_from || ! $repeat_to ) ){
        $return_array['status'] = 'not_valid';
        array_push ( $return_array['errors'], 'error_repeat_period_not_set' );
    }
    
    return $return_array;
}