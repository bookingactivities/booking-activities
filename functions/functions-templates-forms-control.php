<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// TEMPLATE

/**
 * Get template default data
 * @since 1.12.0
 */
function bookacti_get_template_default_data() {
	return apply_filters( 'bookacti_template_default_data', array(
		'id' => 0,
		'title' => esc_html__( 'Calendar', 'booking-activities' ),
		'active' => 1
	));
}


/**
 * Get template default meta
 * @since 1.12.0
 */
function bookacti_get_template_default_meta() {
	return apply_filters( 'bookacti_template_default_meta', array(
		'minTime'		=> '00:00',
		'maxTime'		=> '00:00',
		'snapDuration'	=> '00:05'
	));
}


/**
 * Sanitize template data
 * @since 1.12.0 (was bookacti_sanitize_template_settings)
 * @param array $raw_data
 * @return array
 */
function bookacti_sanitize_template_data( $raw_data ) {
	$default_data = bookacti_get_template_default_data();
	$default_meta = bookacti_get_template_default_meta();
	
	// Sanitize common values
	$keys_by_type = array( 
		'absint'=> array( 'id', 'duplicated_template_id' ),
		'str'	=> array( 'title', 'minTime', 'maxTime', 'snapDuration' ),
		'array'	=> array( 'managers' ),
		'bool'	=> array( 'active' )
	);
	$data = bookacti_sanitize_values( array_merge( $default_data, $default_meta, array( 'managers' => array() ) ), $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'template_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'template_id' ] ); }
	if( ! empty( $raw_data[ 'duplicated_template_id' ] ) ) { $data[ 'duplicated_template_id' ] = intval( $raw_data[ 'duplicated_template_id' ] ); }
	
	// Sanitize managers
	$data[ 'managers' ] = bookacti_sanitize_template_managers( $data[ 'managers' ] );
	
	// Format 24-h times: minTime, maxTime, snapDuration
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $data[ 'minTime' ] ) )		{ $data[ 'minTime' ] = $default_meta[ 'minTime' ]; }
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $data[ 'maxTime' ] ) )		{ $data[ 'maxTime' ] = $default_meta[ 'maxTime' ]; }
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $data[ 'snapDuration' ] ) )	{ $data[ 'snapDuration' ] = $default_meta[ 'snapDuration' ]; }
	
	// If minTime >= maxTime, add one day to maxTime
	if( intval( str_replace( ':', '', $data[ 'minTime' ] ) ) >= intval( str_replace( ':', '', $data[ 'maxTime' ] ) ) ) { 
		$data[ 'maxTime' ] = str_pad( 24 + ( intval( substr( $data[ 'maxTime' ], 0, 2 ) ) % 24 ), 2, '0', STR_PAD_LEFT ) . substr( $data[ 'maxTime' ], 2 );
	}
	
	// Make sure snapDuration is not null
	if( $data[ 'snapDuration' ] === '00:00' ) { $data[ 'snapDuration' ] = '00:01'; }
	
	return apply_filters( 'bookacti_sanitized_template_data', $data, $raw_data );
}


/**
 * Validate template data
 * @since 1.0.6
 * @version 1.12.0
 * @param array $data
 * @return array
 */
function bookacti_validate_template_data( $data ) {
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
	return apply_filters( 'bookacti_validate_template_data', $return_array, $data ) ;
}


/**
 * Sanitize template managers
 * @since 1.12.0 (was bookacti_format_template_managers)
 * @version 1.12.3
 * @param array $template_managers
 * @return array
 */
function bookacti_sanitize_template_managers( $template_managers ) {
	$template_managers = bookacti_ids_to_array( $template_managers );
	
	// Add the current user automatically if not super admin
	$bypass_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	if( ! is_super_admin() && ! $bypass_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $template_managers, true ) ) {
			$template_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage templates
	$managers_caps = array( 'bookacti_manage_bookings', 'bookacti_edit_bookings', 'bookacti_edit_templates', 'bookacti_read_templates' );
	foreach( $template_managers as $i => $template_manager ) {
		if( $template_manager ) {
			$user_can = false;
			foreach( $managers_caps as $managers_cap ) {
				if( user_can( $template_manager, $managers_cap ) ) { $user_can = true; break; }
			}
			if( $user_can ) { continue; }
		}
		unset( $template_managers[ $i ] );
	}
	
	return apply_filters( 'bookacti_template_managers', $template_managers );
}




// ACTIVITY

/**
 * Get activity default data
 * @since 1.12.0
 */
function bookacti_get_activity_default_data() {
	return apply_filters( 'bookacti_activity_default_data', array(
		'id' => 0,
		'title' => esc_html__( 'Activity', 'booking-activities' ),
		'color' => '#3a87ad',
		'availability' => 1,
		'duration' => 3600,
		'active' => 1
	));
}


/**
 * Get activity default meta
 * @since 1.12.0
 */
function bookacti_get_activity_default_meta() {
	return apply_filters( 'bookacti_activity_default_meta', array(
		'unit_name_singular'		=> '',
		'unit_name_plural'			=> '',
		'show_unit_in_availability'	=> 0,
		'places_number'				=> 0,
		'min_bookings_per_user'		=> 0,
		'max_bookings_per_user'		=> 0,
		'max_users_per_event'		=> 0,
		'booking_changes_deadline'	=> '',
		'allowed_roles'				=> array()
	));
}


/**
 * Sanitize activity data
 * @since 1.12.0 (was bookacti_format_activity_settings)
 * @param array $raw_data
 * @return array
 */
function bookacti_sanitize_activity_data( $raw_data ) {
	$default_data = bookacti_get_activity_default_data();
	$default_meta = bookacti_get_activity_default_meta();
	
	// Sanitize by type
	$keys_by_type = array( 
		'absint'	=> array( 'id', 'availability', 'duration', 'places_number', 'min_bookings_per_user', 'max_bookings_per_user', 'max_users_per_event', 'booking_changes_deadline' ),
		'str'		=> array( 'title', 'unit_name_singular', 'unit_name_plural' ),
		'color'		=> array( 'color' ),
		'bool'		=> array( 'show_unit_in_availability' ),
		'array'		=> array( 'allowed_roles', 'managers' )
	);
	$data = bookacti_sanitize_values( array_merge( $default_data, $default_meta, array( 'managers' => array() ) ), $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'activity_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'activity_id' ] ); }
	if( ! empty( $raw_data[ 'duplicated_template_id' ] ) ) { $data[ 'duplicated_template_id' ] = intval( $raw_data[ 'duplicated_template_id' ] ); }
	
	// Convert duration from seconds to timespan
	$data[ 'duration' ] = $data[ 'duration' ] ? bookacti_format_duration( $data[ 'duration' ], 'timespan' ) : bookacti_format_duration( $default_data[ 'duration' ], 'timespan' );
	
	// If booking_changes_deadline is empty, it fallbacks to the global value. So it is not the same as booking_changes_deadline = 0.
	if( ! is_numeric( $raw_data[ 'booking_changes_deadline' ] ) 
	||  ( is_numeric( $raw_data[ 'booking_changes_deadline' ] ) && intval( $raw_data[ 'booking_changes_deadline' ] ) < 0 ) ) { $data[ 'booking_changes_deadline' ] = ''; }
	
	// Sanitize managers
	$data[ 'managers' ] = bookacti_sanitize_template_managers( $data[ 'managers' ] );
	
	return apply_filters( 'bookacti_sanitized_activity_data', $data, $raw_data );
}


/**
 * Validate activity data
 * @since 1.12.0
 * @param array $data
 * @return array
 */
function bookacti_validate_activity_data( $data ) {
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
	return apply_filters( 'bookacti_validate_activity_data', $return_array, $data ) ;
}


/**
 * Sanitize activity managers
 * @since 1.12.0 (was bookacti_format_activity_managers)
 * @param array $activity_managers
 * @return array
 */
function bookacti_sanitize_activity_managers( $activity_managers = array() ) {
	$activity_managers = bookacti_ids_to_array( $activity_managers );
	
	// Add the current user automatically if not super admin
	$bypass_managers_check = apply_filters( 'bypass_activity_managers_check', false );
	if( ! is_super_admin() && ! $bypass_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $activity_managers, true ) ) {
			$activity_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage activities
	$managers_caps = array( 'bookacti_edit_bookings', 'bookacti_edit_activities', 'bookacti_edit_templates', 'bookacti_read_templates' );
	foreach( $activity_managers as $i => $activity_manager ) {
		if( $activity_manager ) {
			$user_can = false;
			foreach( $managers_caps as $managers_cap ) {
				if( user_can( $activity_manager, $managers_cap ) ) { $user_can = true; break; }
			}
			if( $user_can ) { continue; }
		}
		unset( $activity_managers[ $i ] );
	}
	
	return apply_filters( 'bookacti_activity_managers', $activity_managers );
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
	$default_meta = bookacti_get_event_default_meta();
	
	// Sanitize common values
	$keys_by_type = array( 
		'absint'	=> array( 'id', 'template_id', 'activity_id', 'availability', 'repeat_step' ),
		'str_html'	=> array( 'title' ),
		'datetime'	=> array( 'start', 'end' ),
		'str_id'	=> array( 'repeat_freq', 'repeat_on' ),
		'date'		=> array( 'repeat_from', 'repeat_to' ),
		'bool'		=> array( 'active' )
	);
	$data = bookacti_sanitize_values( array_merge( $default_data, $default_meta ), $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'event_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'event_id' ] ); }
	$data[ 'exceptions_dates' ] = ! empty( $raw_data[ 'exceptions_dates' ] ) ? $raw_data[ 'exceptions_dates' ] : array();
	
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
 * @param string $object_type "event" or "group"
 * @return array
 */
function bookacti_sanitize_repeat_data( $object_data, $object_type = 'event' ) {
	$default_data = $object_type === 'group' ? bookacti_get_group_of_events_default_data() : bookacti_get_event_default_data();
	
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
	$repeat_periods = array_keys( bookacti_get_event_repeat_periods() );
	if( ! in_array( $data[ 'repeat_freq' ], $repeat_periods, true ) ) {
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
		if( $object_type === 'group' ) {
			// Get the occurrences
			$group_i = $data[ 'id' ] ? $data[ 'id' ] : ( rand() * -1 );
			$groups_occurrences = bookacti_get_occurrences_of_repeated_groups_of_events( array( $group_i => $data ), array( 'past_events' => true ) );
			$group_occurrences = ! empty( $groups_occurrences[ $group_i ] ) ? $groups_occurrences[ $group_i ] : array();

			// Get the first events of each group occurrence
			$group_occurrences_events = array();
			$group_occurrences_bounding_events = array();
			foreach( $group_occurrences as $group_date => $group_events ) {
				if( empty( $group_events[ 0 ] ) ) { continue; }
				$group_occurrences_events[ $group_date ] = $group_events[ 0 ];
				if( ! in_array( $group_date, $data[ 'exceptions_dates' ], true ) ) { $group_occurrences_bounding_events[ $group_date ] = $group_events[ 0 ]; }
			}
			ksort( $group_occurrences_events );
			ksort( $group_occurrences_bounding_events );
		}
		
		// Restrict the repeat period to the actual first and last occurrences
		$bounding_events = $object_type === 'event' ? bookacti_get_occurrences_of_repeated_event( (object) $data, array( 'exceptions_dates' => $data[ 'exceptions_dates' ], 'past_events' => true, 'bounding_only' => true ) ) : $group_occurrences_bounding_events;
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
			
			$occurrences = $object_type === 'event' ? bookacti_get_occurrences_of_repeated_event( (object) $data, array( 'past_events' => true ) ) : $group_occurrences_events;
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
 * @param array $data see bookacti_get_group_of_events_default_data or bookacti_get_event_default_data
 * @param string $object_type "event" or "group"
 * @return array
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
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
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
 * Sanitize group of events data
 * @since 1.12.0
 * @param array $raw_data
 * @return array
 */
function bookacti_sanitize_group_of_events_data( $raw_data ) {
	$default_data = bookacti_get_group_of_events_default_data();
	$default_meta = bookacti_get_group_of_events_default_meta();
	
	// Sanitize common values
	$keys_by_type = array( 
		'absint'	=> array( 'id', 'category_id', 'repeat_step' ),
		'str_html'	=> array( 'title' ),
		'str'		=> array( 'category_title' ),
		'str_id'	=> array( 'repeat_freq', 'repeat_on' ),
		'date'		=> array( 'repeat_from', 'repeat_to' ),
		'bool'		=> array( 'active' )
	);
	$data = bookacti_sanitize_values( array_merge( $default_data, $default_meta, array( 'category_title' => '' ) ), $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'group_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'group_id' ] ); }
	$data[ 'template_id' ] = ! empty( $raw_data[ 'template_id' ] ) ? abs( intval( $raw_data[ 'template_id' ] ) ) : 0;
	$data[ 'exceptions_dates' ] = ! empty( $raw_data[ 'exceptions_dates' ] ) ? $raw_data[ 'exceptions_dates' ] : array();
	
	// Sanitize array of events
	$raw_events = isset( $raw_data[ 'events' ] ) ? ( is_array( $raw_data[ 'events' ] ) ? $raw_data[ 'events' ] : ( is_string( $raw_data[ 'events' ] ) ? bookacti_maybe_decode_json( stripslashes( $raw_data[ 'events' ] ), true ) : array() ) ) : array();
	$event_default_data = array( 'id' => 0, 'activity_id' => 0, 'start' => '', 'end' => '' );
	$event_keys_by_type = array( 
		'absint'	=> array( 'id', 'activity_id', 'template_id' ),
		'datetime'	=> array( 'start', 'end' ),
	);
	
	$data[ 'events' ] = array();
	foreach( $raw_events as $raw_event ) {
		$event = bookacti_sanitize_values( $event_default_data, $raw_event, $event_keys_by_type );
		if( $event[ 'id' ] && $event[ 'start' ] && $event[ 'end' ] ) { 
			if( ! isset( $event[ 'template_id' ] ) && isset( $data[ 'template_id' ] ) ) { $event[ 'template_id' ] = $data[ 'template_id' ]; }
			$data[ 'events' ][] = $event;
		}
	}
	usort( $data[ 'events' ], 'bookacti_sort_array_by_start' );
	
	// Group start (used for sanitizing repeat data)
	$data[ 'start' ] = isset( $data[ 'events' ][ 0 ][ 'start' ] ) ? $data[ 'events' ][ 0 ][ 'start' ] : '';
	
	$data = bookacti_sanitize_repeat_data( $data, 'group' );
	
	return apply_filters( 'bookacti_sanitized_group_of_events_data', $data, $raw_data );
}


/**
 * Validate group of events data
 * @since 1.1.0
 * @version 1.12.0
 * @param array $data
 * @return array
 */
function bookacti_validate_group_of_events_data( $data ) {
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
	
	$missing_fields = array();
	if( ! $data[ 'title' ] ) { $missing_fields[] = esc_html__( 'Group title', 'booking-activities' ); }
	if( ! $data[ 'category_id' ] && ! $data[ 'category_title' ] ) { $missing_fields[] = esc_html__( 'Category title', 'booking-activities' ); }
	
	// The saved events must be part of the repeat interval
	if( $data[ 'repeat_freq' ] !== 'none' ) {
		$repeat_from_dt = new DateTime( $data[ 'repeat_from' ] . ' 00:00:00' );
		$first_event_dt = new DateTime( $data[ 'events' ][ 0 ][ 'start' ] );
		if( $first_event_dt < $repeat_from_dt ) {
			$return_array[ 'status' ] = 'failed';
			$return_array[ 'errors' ][] = 'event_not_btw_from_and_to';
			$return_array[ 'messages' ][ 'event_not_btw_from_and_to' ] = esc_html__( 'The selected event should be included in the period in which it will be repeated.', 'booking-activities' );
		}
	}
	
	if( $missing_fields ) {
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'missing_fields';
		/* translators: %s is a comma-sperated list of field labels */
		$return_array[ 'messages' ][ 'missing_fields' ] = sprintf( esc_html__( 'You must fill these fields: %s', 'booking-activities' ), implode( ', ', $missing_fields ) );
	}
	if( count( $data[ 'events' ] ) < 2 ) {
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'select_at_least_two_events';
		$return_array[ 'messages' ][ 'select_at_least_two_events' ] = esc_html__( 'You must select at least two events.', 'booking-activities' );
	}
	
	return apply_filters( 'bookacti_validate_group_of_events_data', $return_array, $data );
}




// GROUP CATEGORIES

/**
 * Get group category default data
 * @since 1.12.0
 */
function bookacti_get_group_category_default_data() {
	return apply_filters( 'bookacti_group_category_default_data', array(
		'id' => 0,
		'template_id' => 0,
		'title' => esc_html__( 'Group category', 'booking-activities' ),
		'active' => 1
	));
}


/**
 * Get group category default meta
 * @since 1.12.0
 */
function bookacti_get_group_category_default_meta() {
	return apply_filters( 'bookacti_group_category_default_meta', array(
		'min_bookings_per_user'		=> 0,
		'max_bookings_per_user'		=> 0,
		'max_users_per_event'		=> 0,
		'booking_changes_deadline'	=> '',
		'started_groups_bookable'	=> -1,
		'allowed_roles'				=> array()
	));
}


/**
 * Sanitize group category data
 * @since 1.1.0
 * @version 1.12.0
 * @param array $raw_data
 * @return array
 */
function bookacti_sanitize_group_category_data( $raw_data ) {
	$default_data = bookacti_get_group_category_default_data();
	$default_meta = bookacti_get_group_category_default_meta();
	
	// Sanitize by type
	$keys_by_type = array( 
		'absint'	=> array( 'id', 'template_id', 'min_bookings_per_user', 'max_bookings_per_user', 'max_users_per_event', 'booking_changes_deadline' ),
		'str'		=> array( 'title' ),
		'bool'		=> array( 'started_groups_bookable' ),
		'array'		=> array( 'allowed_roles' )
	);
	$data = bookacti_sanitize_values( array_merge( $default_data, $default_meta ), $raw_data, $keys_by_type );
	
	if( ! $data[ 'id' ] && ! empty( $raw_data[ 'category_id' ] ) ) { $data[ 'id' ] = intval( $raw_data[ 'category_id' ] ); }
	
	return apply_filters( 'bookacti_sanitized_group_category_data', $data, $raw_data );
}


/**
 * Validate group category data
 * @since 1.1.0
 * @version 1.12.0
 * @param array $data
 * @return array
 */
function bookacti_validate_group_category_data( $data ) {
	$return_array = array( 'status' => 'success', 'errors' => array(), 'messages' => array() );
	
	if( ! $data[ 'title' ] ) {
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'missing_fields';
		$return_array[ 'messages' ][ 'missing_fields' ] = sprintf( esc_html__( 'You must fill these fields: %s', 'booking-activities' ), esc_html__( 'New category title', 'booking-activities' ) );
	}
	if( ! $data[ 'template_id' ] ) { 
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'errors' ][] = 'invalid_template_id';
		$return_array[ 'messages' ][ 'invalid_template_id' ] = esc_html__( 'Invalid calendar ID.', 'booking-activities' );
	}
	
	return apply_filters( 'bookacti_validate_group_category_data', $return_array, $data );
}