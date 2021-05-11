<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// TEMPLATE

/**
 * Validate template basic data
 * @since	1.0.6
 * @version	1.8.7
 * @param	string	$template_title
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
		$return_array['errors'][] = 'error_closing_before_opening';
    }
    
    return apply_filters( 'bookacti_validate_template_data', $return_array, $template_title, $template_start, $template_end );
}


/**
 * Format template managers
 * @version 1.8.8
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
	$template_managers_caps = array( 'bookacti_edit_bookings', 'bookacti_edit_templates', 'bookacti_read_templates' );
	foreach( $template_managers as $i => $template_manager ) {
		if( $template_manager ) {
			$user_can = false;
			foreach( $template_managers_caps as $template_managers_cap ) {
				if( user_can( $template_manager, $template_managers_cap ) ) { $user_can = true; break; }
			}
			if( $user_can ) { continue; }
		}
		unset( $template_managers[ $i ] );
	}
	
	return apply_filters( 'bookacti_template_managers', $template_managers );
}


/**
 * Sanitize template settings
 * @since 1.9.3 (was bookacti_format_template_settings)
 * @param array $raw_settings
 * @return array
 */
function bookacti_sanitize_template_settings( $raw_settings ) {
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
	
	// If minTime >= maxTime, add one day to maxTime
	if( intval( str_replace( ':', '', $settings[ 'minTime' ] ) ) >= intval( str_replace( ':', '', $settings[ 'maxTime' ] ) ) ) { 
		$settings[ 'maxTime' ] = str_pad( 24 + ( intval( substr( $settings[ 'maxTime' ], 0, 2 ) ) % 24 ), 2, '0', STR_PAD_LEFT ) . substr( $settings[ 'maxTime' ], 2 );
	}
	
	// Make sure snapDuration is not null
	if( $settings[ 'snapDuration' ] === '00:00' ) { $settings[ 'snapDuration' ] = '00:01'; }
	
	return apply_filters( 'bookacti_template_settings_formatted', $settings, $raw_settings, $default_settings );
}




// ACTIVITY

/**
 * Format activity managers
 * @version 1.8.8
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
	$activity_managers_caps = array( 'bookacti_edit_bookings', 'bookacti_edit_templates', 'bookacti_read_templates' );
	foreach( $activity_managers as $i => $activity_manager ) {
		if( $activity_manager ) {
			$user_can = false;
			foreach( $activity_managers_caps as $activity_managers_cap ) {
				if( user_can( $activity_manager, $activity_managers_cap ) ) { $user_can = true; break; }
			}
			if( $user_can ) { continue; }
		}
		unset( $activity_managers[ $i ] );
	}
	
	return apply_filters( 'bookacti_activity_managers', $activity_managers );
}


/**
 * Format activity settings
 * @version 1.8.0
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
		'booking_changes_deadline'	=> '',
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
	
	// booking_changes_deadline
	if( is_numeric( $settings[ 'booking_changes_deadline' ] ) && $settings[ 'booking_changes_deadline' ] < 0 ) { $settings[ 'booking_changes_deadline' ] = ''; }
	
	return apply_filters( 'bookacti_activity_settings', $settings, $activity_settings, $default_settings );
}




// EVENT

/**
 * Get event default data
 * @since 1.8.0
 * @version 1.11.0
 */
function bookacti_get_event_default_data() {
	$dt = new DateTime();
	$start = $dt->format( 'Y-m-d H:i:s' );
	$dt->add( new DateInterval( 'PT1H' ) );
	$end = $dt->format( 'Y-m-d H:i:s' );
	return apply_filters( 'bookacti_event_default_data', array(
		'id' => 0,
		'template_id' => 0,
		'activity_id' => 0,
		'title' => esc_html__( 'Event', 'booking-activities' ),
		'start' => $start,
		'end' => $end,
		'availability' => 1,
		'repeat_freq' => 'none',
		'repeat_step' => 1,
		'repeat_on' => '',
		'repeat_from' => '',
		'repeat_to' => '',
		'active' => 1
	));
}

/**
 * Get event default meta
 * @since 1.8.0
 */
function bookacti_get_event_default_meta() {
	return apply_filters( 'bookacti_event_default_meta', array() );
}


/**
 * Get available event repeat periods
 * @since 1.8.0
 * @version 1.11.0
 */
function bookacti_get_event_repeat_periods() {
	return apply_filters( 'bookacti_event_repeat_periods', array( 
		'none' => esc_html__( 'Do not repeat', 'booking-activities' ),
		'daily' => esc_html__( 'Day', 'booking-activities' ),
		'weekly' => esc_html__( 'Week', 'booking-activities' ),
		'monthly' => esc_html__( 'Month', 'booking-activities' )
	) );
}


/**
 * Sanitize event data
 * @since 1.8.0
 * @version 1.12.0
 */
function bookacti_sanitize_event_data( $raw_data ) {
	$default_data = bookacti_get_event_default_data();
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'		=> array( 'id', 'template_id', 'activity_id', 'availability', 'repeat_step' ),
		'str_html'	=> array( 'title' ),
		'datetime'	=> array( 'start', 'end' ),
		'str_id'	=> array( 'repeat_freq', 'repeat_on' ),
		'date'		=> array( 'repeat_from', 'repeat_to' ),
		'bool'		=> array( 'active' )
	);
	$data = bookacti_sanitize_values( $default_data, $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'event_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'event_id' ] ); }
	
	// Make sure ints are positive
	foreach( $keys_by_type[ 'int' ] as $int ) {
		if( $data[ $int ] < 0 ) { $data[ $int ] = $default_data[ $int ]; }
	}
	
	// Make sure start AND end are set
	if( ! $data[ 'start' ] || ! $data[ 'end' ] ) { 
		$data[ 'start' ] = $default_data[ 'start' ];
		$data[ 'end' ] = $default_data[ 'end' ];
	}
	
	// Make sure start is before end
	$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'start' ] );
	$end_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'end' ] );
	if( $start_dt > $end_dt ) {
		$data[ 'start' ] = $end_dt->format( 'Y-m-d H:i:s' );
		$data[ 'end' ] = $start_dt->format( 'Y-m-d H:i:s' );
	}
	else if( $start_dt === $end_dt ) {
		$end_dt->add( new DateInterval( 'PT1H' ) );
		$data[ 'end' ] = $end_dt->format( 'Y-m-d H:i:s' );
	}
	
	$data = bookacti_sanitize_repeat_data( $data, 'event' );
	
	return apply_filters( 'bookacti_sanitized_event_data', $data, $raw_data );
}


/**
 * Sanitize (group of) events repeat data
 * @since 1.12.0
 * @param array $object_data see bookacti_get_group_of_events_default_data or bookacti_get_event_default_data
 * @param string $object_type "event" or "group_of_events"
 * @return array
 */
function bookacti_sanitize_repeat_data( $object_data, $object_type = 'event' ) {
	$default_data = $object_type === 'group_of_events' ? bookacti_get_group_of_events_default_data() : bookacti_get_event_default_data();
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'		=> array( 'repeat_step' ),
		'str_id'	=> array( 'repeat_freq', 'repeat_on' ),
		'date'		=> array( 'repeat_from', 'repeat_to' )
	);
	$data = array_merge( $object_data, bookacti_sanitize_values( $default_data, $object_data, $keys_by_type ) );
	$data[ 'exceptions_dates' ] = ! empty( $object_data[ 'exceptions_dates' ] ) ? bookacti_sanitize_date_array( $object_data[ 'exceptions_dates' ] ) : array();
	
	// Make sure repeat_step is positive
	if( $data[ 'repeat_step' ] < 0 ) { $data[ 'repeat_step' ] = $default_data[ 'repeat_step' ]; }
	
	// Make sure repeat period exists
	if( ! in_array( $data[ 'repeat_freq' ], array_keys( bookacti_get_event_repeat_periods() ), true ) ) {
		$data[ 'repeat_freq' ] = $default_data[ 'repeat_freq' ];
	}
	
	// Make sure repeat period is consistent
	if( $data[ 'repeat_freq' ] !== 'none' && ( ! $data[ 'repeat_from' ] || ! $data[ 'repeat_to' ] || ! $data[ 'repeat_step' ] ) ) {
		$data[ 'repeat_freq' ] = 'none';
	}
	
	// Sanitize repeat_on according to the repeat_freq
	if( $data[ 'repeat_freq' ] === 'weekly' && $data[ 'repeat_on' ] !== '' ) {
		$repeat_on_array = explode( '_', $data[ 'repeat_on' ] );
		if( array_diff( $repeat_on_array, array( 0, 1, 2, 3, 4, 5, 6 ) ) ) { $data[ 'repeat_on' ] = 'null'; }
	}
	if( $data[ 'repeat_freq' ] === 'monthly' && $data[ 'repeat_on' ] ) {
		if( ! in_array( $data[ 'repeat_on' ], array( 'nth_day_of_month', 'last_day_of_month', 'nth_day_of_week', 'last_day_of_week' ), true ) ) { $data[ 'repeat_on' ] = 'null'; }
	}
	if( $data[ 'repeat_freq' ] === 'daily' ) {
		$data[ 'repeat_on' ] = 'null';
	}
	
	// Check the consistency between the event date and the repeat period
	$data = bookacti_sanitize_event_date_and_repeat_period( $data, $object_type );
	
	// If the event is repeated
	if( $data[ 'repeat_freq' ] !== 'none' ) {
		// Restrict the repeat period to the actual first and last occurrences
		$bounding_events = $object_type === 'event' ? bookacti_get_occurrences_of_repeated_event( (object) $data, array( 'exceptions_dates' => $data[ 'exceptions_dates' ], 'past_events' => true, 'bounding_events_only' => true ) ) : array();
		if( $bounding_events ) {
			$bounding_events_keys = array_keys( $bounding_events );
			$last_key = end( $bounding_events_keys );
			$first_key = reset( $bounding_events_keys );
			$bounding_dates = array( 
				'start' => substr( $bounding_events[ $first_key ][ 'start' ], 0, 10 ), 
				'end' => substr( $bounding_events[ $last_key ][ 'start' ], 0, 10 )
			);
			
			// Replace repeat period with events bounding dates
			if( strtotime( $bounding_dates[ 'start' ] ) > strtotime( $data[ 'repeat_from' ] ) )	{ $data[ 'repeat_from' ] = $bounding_dates[ 'start' ]; }
			if( strtotime( $bounding_dates[ 'end' ] ) < strtotime( $data[ 'repeat_to' ] ) )		{ $data[ 'repeat_to' ] = $bounding_dates[ 'end' ]; }
			
			// Make the event starts on the first occurrence
			if( $object_type === 'event' ) {
				$repeat_from_dt = DateTime::createFromFormat( 'Y-m-d', $data[ 'repeat_from' ] );
				$start_date_dt = DateTime::createFromFormat( 'Y-m-d', substr( $data[ 'start' ], 0, 10 ) );
				$offset_interval = $start_date_dt->diff( $repeat_from_dt );
				$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'start' ] );
				$end_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'end' ] );
				$start_dt->add( $offset_interval );
				$end_dt->add( $offset_interval );
				$data[ 'start' ] = $start_dt->format( 'Y-m-d H:i:s' );
				$data[ 'end' ] = $end_dt->format( 'Y-m-d H:i:s' );
			}
			
			// The repeat period may have changed, so, check the consistency again between the event date and the new repeat period
			$data = bookacti_sanitize_event_date_and_repeat_period( $data, $object_type );
		}
		
		// If the repeat period is only only day, do not repeat the event
		if( $data[ 'repeat_from' ] === $data[ 'repeat_to' ] ) { $data[ 'repeat_freq' ] = 'none'; }
		
		// Remove exceptions out of the repeat period and if they are not on an occurrence
		else if( $data[ 'exceptions_dates' ] ) {
			$repeat_from_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'repeat_from' ] . ' 00:00:00' );
			$repeat_to_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'repeat_to' ] . ' 23:59:59' );
			
			$occurrences = $object_type === 'event' ? bookacti_get_occurrences_of_repeated_event( (object) $data, array( 'past_events' => true ) ) : array();
			foreach( $data[ 'exceptions_dates' ] as $i => $excep_date ) {
				// Remove exceptions out of the repeat period
				$excep_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $excep_date . ' 00:00:00' );
				if( $excep_dt < $repeat_from_dt || $excep_dt > $repeat_to_dt ) {
					unset( $data[ 'exceptions_dates' ][ $i ] );
					continue;
				}
				
				// Remove exception if it is not on an occurrence
				$is_on_occurrence = false;
				foreach( $occurrences as $occurrence ) {
					$occurrence_date = substr( $occurrence[ 'start' ], 0, 10 );
					if( $excep_date === $occurrence_date ) { $is_on_occurrence = true; break; }
				}
				if( ! $is_on_occurrence ) {
					unset( $data[ 'exceptions_dates' ][ $i ] );
				}
			}
		}
	}
	
	// If the event is not repeated, remove all repeat data
	if( $data[ 'repeat_freq' ] === 'none' ) {
		$data[ 'repeat_step' ] = -1;
		$data[ 'repeat_on' ] = 'null';
		$data[ 'repeat_from' ] = 'null';
		$data[ 'repeat_to' ] = 'null';
		$data[ 'exceptions_dates' ] = array();
	}
	
	return $data;
}


/**
 * Make sure the event date and its repeat period are consistent
 * @since 1.11.0
 * @version 1.12.0
 */
function bookacti_sanitize_event_date_and_repeat_period( $data, $object_type = 'event' ) {
	if( $data[ 'repeat_freq' ] !== 'none' && $data[ 'repeat_from' ] && $data[ 'repeat_to' ] ) {
		// Make sure repeat from is before repeat to
		$repeat_from_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'repeat_from' ] . ' 00:00:00' );
		$repeat_to_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'repeat_to' ] . ' 23:59:59' );
		if( $repeat_from_dt > $repeat_to_dt ) { 
			$data[ 'repeat_from' ] = $data[ 'repeat_to' ]; 
			$repeat_from_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'repeat_from' ] . ' 00:00:00' );
		}

		// Make sure the event starts in the repeat period (else, set the event date to the repeat_from date)
		if( $object_type === 'event' ) {
			$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'start' ] );
			if( $start_dt < $repeat_from_dt || $start_dt > $repeat_to_dt ) { 
				$end_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'end' ] );
				$repeat_from_date_dt = DateTime::createFromFormat( 'Y-m-d', $data[ 'repeat_from' ] );
				$start_date_dt = DateTime::createFromFormat( 'Y-m-d', substr( $data[ 'start' ], 0, 10 ) );
				$offset_interval = $start_date_dt->diff( $repeat_from_date_dt );
				$start_dt->add( $offset_interval );
				$end_dt->add( $offset_interval );
				$data[ 'start' ] = $start_dt->format( 'Y-m-d H:i:s' );
				$data[ 'end' ] = $end_dt->format( 'Y-m-d H:i:s' );
			}
		}
	}

	// Check if the monthly repetition type is valid
	if( $data[ 'repeat_freq' ] === 'monthly' && $data[ 'repeat_on' ] && isset( $data[ 'start' ] ) ) {
		$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $data[ 'start' ] );
		$nth_in_month = intval( $start_dt->format( 'j' ) );
		$days_in_month = intval( $start_dt->format( 't' ) );
		if( $data[ 'repeat_on' ] === 'last_day_of_month' && $nth_in_month !== $days_in_month )		{ $data[ 'repeat_on' ] = 'nth_day_of_month'; }
		if( $data[ 'repeat_on' ] === 'last_day_of_week' && $nth_in_month < ( $days_in_month - 6 ) )	{ $data[ 'repeat_on' ] = 'nth_day_of_week'; }
	}
	
	return $data;
}


/**
 * Validate event data
 * @since 1.8.0 (was bookacti_validate_event)
 * @version 1.12.0
 * @param array $data
 * @return array
 */
function bookacti_validate_event_data( $data ) {
	$return_array = array(
		'status' => 'success',
		'errors' => array(),
		'messages' => array()
	);
	return apply_filters( 'bookacti_validate_event', $return_array, $data ) ;
}




// GROUPS OF EVENTS

/**
 * Get group of events default data
 * @since 1.12.0
 */
function bookacti_get_group_of_events_default_data() {
	return apply_filters( 'bookacti_group_of_events_default_data', array(
		'id' => 0,
		'category_id' => 0,
		'title' => esc_html__( 'Group of events', 'booking-activities' ),
		'repeat_freq' => 'none',
		'repeat_step' => 1,
		'repeat_on' => '',
		'repeat_from' => '',
		'repeat_to' => '',
		'active' => 1
	));
}


/**
 * Get group of events default meta
 * @since 1.12.0
 */
function bookacti_get_group_of_events_default_meta() {
	return apply_filters( 'bookacti_group_of_events_default_meta', array() );
}


/**
 * Get available group of events repeat periods
 * @since 1.12.0
 */
function bookacti_get_group_of_events_repeat_periods() {
	return apply_filters( 'bookacti_group_of_events_repeat_periods', array( 
		'none' => esc_html__( 'Do not repeat', 'booking-activities' ),
		'daily' => esc_html__( 'Day', 'booking-activities' ),
		'weekly' => esc_html__( 'Week', 'booking-activities' ),
		'monthly' => esc_html__( 'Month', 'booking-activities' )
	) );
}


/**
 * Sanitize group of events data
 * @since 1.12.0
 * @param array $raw_data
 * @return array
 */
function bookacti_sanitize_group_of_events_data( $raw_data ) {
	$default_data = bookacti_get_group_of_events_default_data();
	$default_data[ 'category_title' ] = '';
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'		=> array( 'id', 'category_id', 'repeat_step' ),
		'str_html'	=> array( 'title' ),
		'str'		=> array( 'category_title' ),
		'str_id'	=> array( 'repeat_freq', 'repeat_on' ),
		'date'		=> array( 'repeat_from', 'repeat_to' ),
		'bool'		=> array( 'active' )
	);
	$data = bookacti_sanitize_values( $default_data, $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'group_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'group_id' ] ); }
	
	// Make sure ints are positive
	foreach( $keys_by_type[ 'int' ] as $int ) {
		if( $data[ $int ] < 0 ) { $data[ $int ] = $default_data[ $int ]; }
	}
	
	// Sanitize array of events
	$raw_events = isset( $raw_data[ 'events' ] ) ? bookacti_maybe_decode_json( stripslashes( $raw_data[ 'events' ] ), true ) : array();
	$event_default_data = array( 'id' => 0, 'activity_id' => 0, 'start' => '', 'end' => '' );
	$event_keys_by_type = array( 
		'int'		=> array( 'id', 'activity_id' ),
		'datetime'	=> array( 'start', 'end' ),
	);
	
	$data[ 'events' ] = array();
	foreach( $raw_events as $raw_event ) {
		$event = bookacti_sanitize_values( $event_default_data, $raw_event, $event_keys_by_type );
		if( $event[ 'id' ] && $event[ 'start' ] && $event[ 'end' ] ) { $data[ 'events' ][] = $event; }
	}
	usort( $data[ 'events' ], 'bookacti_sort_array_by_start' );
	
	// Group start
	$data[ 'start' ] = isset( $data[ 'events' ][ 0 ][ 'start' ] ) ? $data[ 'events' ][ 0 ][ 'start' ] : '';
	
	$data = bookacti_sanitize_repeat_data( $data, 'group_of_events' );
	
	return apply_filters( 'bookacti_sanitized_group_of_events_data', $data, $raw_data );
}


/**
 * Validate group of events data input
 * @since 1.1.0
 * @version 1.12.0
 * @param array $group_of_events_data sanitized with bookacti_sanitize_group_of_events_data
 * @return array
 */
function bookacti_validate_group_of_events_data( $group_of_events_data ) {
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
	
	$missing_fields = array();
	if( ! $group_of_events_data[ 'title' ] ) { $missing_fields[] = esc_html__( 'Group title', 'booking-activities' ); }
	if( ! $group_of_events_data[ 'category_id' ] && ! $group_of_events_data[ 'category_title' ] ) { $missing_fields[] = esc_html__( 'Category title', 'booking-activities' ); }
	
	if( $missing_fields ) {
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'error_missing_fields';
		/* translators: %s is a comma-sperated list of field labels */
		$return_array[ 'messages' ][ 'error_missing_fields' ] = sprintf( esc_html__( 'You must fill these fields: %s', 'booking-activities' ), implode( ', ', $missing_fields ) );
	}
	if( count( $group_of_events_data[ 'events' ] ) < 2 ) {
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'error_select_at_least_two_events';
		$return_array[ 'messages' ][ 'error_select_at_least_two_events' ] = esc_html__( 'You must select at least two events.', 'booking-activities' );
	}
	
	return apply_filters( 'bookacti_validate_group_of_events_data', $return_array, $group_of_events_data );
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
 * @version 1.8.0
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
		'booking_changes_deadline'	=> '',
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
	
	// booking_changes_deadline
	if( is_numeric( $settings[ 'booking_changes_deadline' ] ) && $settings[ 'booking_changes_deadline' ] < 0 ) { $settings[ 'booking_changes_deadline' ] = ''; }
		
	// started_groups_bookable
	if( $settings[ 'started_groups_bookable' ] > 1 || $settings[ 'started_groups_bookable' ] < -1 ) { $settings[ 'started_groups_bookable' ] = $default_settings[ 'started_groups_bookable' ]; }
	
	return apply_filters( 'bookacti_group_category_settings', $settings, $category_settings, $default_settings );
}