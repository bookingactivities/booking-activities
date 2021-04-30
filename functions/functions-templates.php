<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CALENDAR EDITOR

/**
 * Get booking system data
 * @since 1.7.4
 * @version 1.9.2
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @param int $template_id
 * @return array
 */
function bookacti_get_editor_booking_system_data( $atts, $template_id ) {
	$booking_system_data = $atts;
	
	$templates_data		= bookacti_get_templates_data( $template_id, true );
	$availability_period= array( 'start' => $templates_data[ $template_id ][ 'start' ] . ' 00:00:00', 'end' => $templates_data[ $template_id ][ 'end' ] . ' 23:59:59' );
	$events_interval	= bookacti_get_new_interval_of_events( $availability_period, array(), false, true );
	$events_args		= array( 'templates' => array( $template_id ), 'interval' => $events_interval );
	$events				= $events_interval ? bookacti_fetch_events_for_calendar_editor( $events_args ) : array();
	
	$booking_system_data[ 'calendars' ]				= array( $template_id );
	$booking_system_data[ 'events' ]				= $events[ 'events' ] ? $events[ 'events' ] : array();
	$booking_system_data[ 'events_data' ]			= $events[ 'data' ] ? $events[ 'data' ] : array();
	$booking_system_data[ 'events_interval' ]		= array( 'start' => substr( $events_interval[ 'start' ], 0, 10 ), 'end' => substr( $events_interval[ 'end' ], 0, 10 ) );
	$booking_system_data[ 'bookings' ]				= bookacti_get_number_of_bookings_for_booking_system( $template_id );
	$booking_system_data[ 'exceptions' ]			= bookacti_get_exceptions_by_event( array( 'templates' => array( $template_id ) ) );
	$booking_system_data[ 'activities_data' ]		= bookacti_get_activities_by_template( $template_id, false, true );
	$booking_system_data[ 'groups_events' ]			= bookacti_get_groups_events( $template_id );
	$booking_system_data[ 'groups_data' ]			= bookacti_get_groups_of_events( array( 'templates' => array( $template_id ) ) );
	$booking_system_data[ 'group_categories_data' ]	= bookacti_get_group_categories( $template_id );
	$booking_system_data[ 'start' ]					= $availability_period[ 'start' ];
	$booking_system_data[ 'end' ]					= $availability_period[ 'end' ];
	$booking_system_data[ 'display_data' ]			= $templates_data[ $template_id ][ 'settings' ];
	$booking_system_data[ 'template_data' ]			= $templates_data[ $template_id ];

	return apply_filters( 'bookacti_editor_booking_system_data', $booking_system_data, $atts );
}




// PERMISSIONS

/**
 * Check if user is allowed to manage template
 * @version 1.8.0
 * @param int|array $template_ids
 * @param int|false $user_id False for current user
 * @return boolean
 */
function bookacti_user_can_manage_template( $template_ids, $user_id = false ) {
	$user_can_manage_template = false;
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false, $user_id );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin( $user_id ) || $bypass_template_managers_check ) { $user_can_manage_template = true; }
	else {
		$admins = bookacti_get_template_managers( $template_ids );
		if( $admins ) {
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_template = true; }
		}
	}

	return apply_filters( 'bookacti_user_can_manage_template', $user_can_manage_template, $template_ids, $user_id );
}


/**
 * Check if user is allowed to manage activity
 * @version 1.8.0
 * @param int|array $activity_ids
 * @param int|false $user_id False for current user
 * @param array|false $admins False to retrieve the activity managers
 * @return boolean
 */
function bookacti_user_can_manage_activity( $activity_ids, $user_id = false, $admins = false ) {
	$user_can_manage_activity = false;
	$bypass_activity_managers_check = apply_filters( 'bookacti_bypass_activity_managers_check', false, $user_id );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin( $user_id ) || $bypass_activity_managers_check ) { $user_can_manage_activity = true; }
	else {
		$admins = $admins === false ? bookacti_get_activity_managers( $activity_ids ) : $admins;
		if( $admins ) {
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_activity = true; }
		}
	}

	return apply_filters( 'bookacti_user_can_manage_activity', $user_can_manage_activity, $activity_ids, $user_id );
}


/**
 * Get template managers
 * @version 1.9.2
 * @param int|array $template_ids
 * @return array
 */
function bookacti_get_template_managers( $template_ids ) {
	$managers = bookacti_get_managers( 'template', $template_ids );
	
	$merged_managers = array();
	foreach( $managers as $user_ids ) {
		$merged_managers = array_merge( $merged_managers, bookacti_ids_to_array( $user_ids ) );
	}
	
	return array_unique( $merged_managers );
}


/**
 * Get activity managers
 * @version 1.9.2
 * @param int|array $activity_ids
 * @return array
 */
function bookacti_get_activity_managers( $activity_ids ) {	
	$managers = bookacti_get_managers( 'activity', $activity_ids );
	
	$merged_managers = array();
	foreach( $managers as $user_ids ) {
		$merged_managers = array_merge( $merged_managers, bookacti_ids_to_array( $user_ids ) );
	}
	
	return array_unique( $merged_managers );
}




// TEMPLATE X ACTIVITIES
/**
 * Retrieve template activities list
 * @version 1.11.0
 * @param int $template_id
 * @return boolean|string 
 */
function bookacti_get_template_activities_list( $template_id ) {
	if( ! $template_id ) { return false; }

	$activities = bookacti_get_activities_by_template( array( $template_id ) );
	
	// Sort the activities by custom order
	$activities_order = bookacti_get_metadata( 'template', $template_id, 'activities_order', true );
	if( $activities_order ) {
		$ordered_activities = array();
		foreach( $activities_order as $activity_id ) {
			if( isset( $activities[ $activity_id ] ) ) { $ordered_activities[] = $activities[ $activity_id ]; unset( $activities[ $activity_id ] ); }
		}
		$activities = array_merge( $ordered_activities, $activities );
	}
	
	ob_start();
	foreach( $activities as $activity ) {
		$title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
		?>
		<div class='bookacti-activity' data-activity-id='<?php echo esc_attr( $activity[ 'id' ] ); ?>'>
			<div class='bookacti-activity-visibility dashicons dashicons-visibility' data-activity-visible='1'></div>
			<div class='bookacti-activity-container'>
				<div
					class='fc-event ui-draggable ui-draggable-handle'
					data-event='{"title": "<?php echo htmlentities( esc_attr( $title ), ENT_QUOTES ); ?>", "activity_id": "<?php echo esc_attr( $activity[ 'id' ] ); ?>", "color": "<?php echo esc_attr( $activity[ 'color' ] ); ?>", "stick":"true"}' 
					data-activity-id='<?php echo esc_attr( $activity[ 'id' ] ); ?>'
					data-duration='<?php echo esc_attr( $activity[ 'duration' ] ? $activity[ 'duration' ] : '000.01:00:00' ); ?>'
					title='<?php esc_attr_e( $title ); ?>'
					style='border-color:<?php echo esc_attr( $activity[ 'color' ] ); ?>; background-color:<?php echo esc_attr( $activity[ 'color' ] ); ?>'
					>
					<?php echo $title; ?>
				</div>
			</div>
		<?php
		if( current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity[ 'id' ] ) ) {
		?>
			<div class='bookacti-activity-settings dashicons dashicons-admin-generic'></div>
		<?php
		}
		?>
		</div>
		<?php
	}
	return ob_get_clean();
}




// TEMPLATE SETTINGS

/**
 * Get templates data
 * @since 1.7.3 (was bookacti_fetch_templates)
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $ignore_permissions
 * @param int $user_id
 * @return array
 */
function bookacti_get_templates_data( $template_ids = array(), $ignore_permissions = false, $user_id = 0 ) {
	$templates = bookacti_fetch_templates( $template_ids, $ignore_permissions, $user_id );

	$retrieved_template_ids = array_keys( $templates );

	$templates_meta		= bookacti_get_metadata( 'template', $retrieved_template_ids );
	$templates_managers	= bookacti_get_managers( 'template', $retrieved_template_ids );

	foreach( $templates as $template_id => $template ) {
		$templates[ $template_id ][ 'settings' ]	= isset( $templates_meta[ $template_id ] ) ? $templates_meta[ $template_id ] : array();
		$templates[ $template_id ][ 'admin' ]	= isset( $templates_managers[ $template_id ] ) ? $templates_managers[ $template_id ] : array();
	}

	return $templates;
}


/**
 * Get additional calendar fields default data
 * @since 1.5.0
 * @version 1.8.0
 * @param array $fields
 * @return array
 */
function bookacti_get_calendar_fields_default_data( $fields = array() ) {
	if( ! is_array( $fields ) ) { $fields = array(); }
	$defaults = array();

	// Day Begin
	if( ! $fields || in_array( 'minTime', $fields, true ) ) {
		$defaults[ 'minTime' ] = array(
			'type'			=> 'time',
			'name'			=> 'minTime',
			'value'			=> '08:00',
			/* translators: Refers to the first hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/minTime/ */
			'title'			=> esc_html__( 'Day begin', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when you want the days to begin on the calendar. E.g.: "06:00" Days will begin at 06:00am.', 'booking-activities' )
		);
	}

	// Day end
	if( ! $fields || in_array( 'maxTime', $fields, true ) ) {
		$defaults[ 'maxTime' ] = array(
			'type'			=> 'time',
			'name'			=> 'maxTime',
			'value'			=> '20:00',
			/* translators: Refers to the last hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/maxTime/ */
			'title'			=> esc_html__( 'Day end', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when you want the days to end on the calendar. E.g.: "18:00" Days will end at 06:00pm.', 'booking-activities' )
		);
	}

	// Snap Duration
	if( ! $fields || in_array( 'snapDuration', $fields, true ) ) {
		$defaults[ 'snapDuration' ] = array(
			'type'			=> 'text',
			'name'			=> 'snapDuration',
			'class'			=> 'bookacti-time-field',
			'placeholder'	=> '23:59',
			'value'			=> '00:05',
			/* translators: Refers to the time interval at which a dragged event will snap to the agenda view time grid. E.g.: 00:20', you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...). More information: http://fullcalendar.io/docs/agenda/snapDuration/ */
			'title'			=> esc_html__( 'Snap frequency', 'booking-activities' ),
			'tip'			=> esc_html__( 'The time interval at which a dragged event will snap to the agenda view time grid. E.g.: "00:20", you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...).', 'booking-activities' )
		);
	}

	return apply_filters( 'bookacti_calendar_fields_default_data', $defaults, $fields );
}


/**
 * Get a unique template setting made from a combination of multiple template settings
 * @since	1.2.2 (was bookacti_get_mixed_template_settings)
 * @version 1.9.3
 * @param	array|int $template_ids Array of template ids or single template id
 * @param	boolean $past_events Whether to allow past events
 * @return	array
 */
function bookacti_get_mixed_template_data( $template_ids, $past_events = false ) {
	$templates_data = bookacti_get_templates_data( $template_ids, true );
	$mixed_data = array();
	$mixed_settings	= array();

	foreach( $templates_data as $template_data ){
		$settings = $template_data[ 'settings' ];
		if( isset( $template_data[ 'start' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_data[ 'start' ] ) 
				|| isset( $mixed_data[ 'start' ] ) && strtotime( $template_data[ 'start' ] ) < strtotime( $mixed_data[ 'start' ] ) ) {

				$mixed_data[ 'start' ] = $template_data[ 'start' ];
			} 
		}
		if( isset( $template_data[ 'end' ] ) ) {
			// Keep the higher value
			if(  ! isset( $mixed_data[ 'end' ] ) 
				|| isset( $mixed_data[ 'end' ] ) && strtotime( $template_data[ 'end' ] ) < strtotime( $mixed_data[ 'end' ] ) ) {

				$mixed_data[ 'end' ] = $template_data[ 'end' ];
			} 
		}
		if( isset( $settings[ 'minTime' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_settings[ 'minTime' ] ) 
				|| isset( $mixed_settings[ 'minTime' ] ) && intval( str_replace( ':', '', $settings[ 'minTime' ] ) ) < intval( str_replace( ':', '', $mixed_settings[ 'minTime' ] ) ) ) {

				$mixed_settings[ 'minTime' ] = $settings[ 'minTime' ];
			} 
		}
		if( isset( $settings[ 'maxTime' ] ) ) {
			// Keep the higher value
			if(  ! isset( $mixed_settings[ 'maxTime' ] ) 
				|| isset( $mixed_settings[ 'maxTime' ] ) && intval( str_replace( ':', '', $settings[ 'maxTime' ] ) ) > intval( str_replace( ':', '', $mixed_settings[ 'maxTime' ] ) ) ) {

				$mixed_settings[ 'maxTime' ] = $settings[ 'maxTime' ];
			} 
		}
		if( isset( $settings[ 'snapDuration' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_settings[ 'snapDuration' ] ) 
				|| isset( $mixed_settings[ 'snapDuration' ] ) && strtotime( $settings[ 'snapDuration' ] ) < strtotime( $mixed_settings[ 'snapDuration' ] ) ) {

				$mixed_settings[ 'snapDuration' ] = $settings[ 'snapDuration' ];
			} 
		}
	}

	// Limit the template range to future events
	if( ! $past_events ) {
		$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_time		= new DateTime( 'now', $timezone );
		$template_start		= new DateTime( $mixed_data[ 'start' ], $timezone );
		if( $template_start < $current_time ) {
			$mixed_data[ 'start' ] = $current_time->format( 'Y-m-d' );
		}
	}

	// Add mixed settings
	$mixed_data[ 'settings' ] = $mixed_settings;

	return apply_filters( 'bookacti_mixed_template_settings', $mixed_data, $templates_data, $template_ids, $past_events );
}




// TEMPLATES X ACTIVITIES ASSOCIATION

// UPDATE THE LIST OF TEMPLATES ASSOCIATED TO AN ACTIVITY ID
function bookacti_update_templates_list_by_activity_id( $new_templates, $activity_id ) {
	$old_templates = bookacti_get_templates_by_activity( $activity_id );

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


/**
 * Update the list of activities associated to a template id
 * 
 * @version 1.2.2
 * @param array $new_activities
 * @param int $template_id
 * @return int|false
 */
function bookacti_bind_activities_to_template( $new_activities, $template_id ) {

	if( is_numeric( $new_activities ) ) { $new_activities = array( $new_activities ); }

	$old_activities = bookacti_get_activity_ids_by_template( $template_id, false );

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




// EVENTS

/**
 * Unbind selected occurrence of an event
 * @version 1.12.0
 * @param object $event
 * @param string $event_start Y-m-d H:i:s
 * @param string $event_end Y-m-d H:i:s
 * @return int
 */
function bookacti_unbind_selected_occurrence( $event, $event_start, $event_end ) {
	$event_id = $event->event_id;
	
	// Duplicate the event occurrence
	$duplicated_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'start' => $event_start, 'end' => $event_end, 'repeat_freq' => 'none' ) ) );
	$duplicated_event_id = bookacti_insert_event( $duplicated_event_data );
	if( ! $duplicated_event_id ) { return 0; }
	
	// Duplicate event metadata
	$duplicated = bookacti_duplicate_metadata( 'event', $event_id, $duplicated_event_id );

	// If the event was part of a group, change its id to the new one
	bookacti_update_grouped_event_id( $event_id, $duplicated_event_id, $event_start, $event_end );

	// If the event was booked, move its bookings to the new single event
	bookacti_update_bookings_event_id( $event_id, $duplicated_event_id, $event_start, $event_end );
	
	// Get original event exceptions and add the unbound event date to them
	$events_exceptions = bookacti_get_exceptions_by_event( array( 'events' => array( $event_id ) ) );
	$original_event_exceptions = isset( $events_exceptions[ $event_id ] ) ? $events_exceptions[ $event_id ] : array();
	$unbound_event_date = substr( $event_start, 0, 10 );
	
	// Sanitize and update the original event dates and exceptions
	$original_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'exceptions_dates' => array_unique( array_merge( $original_event_exceptions, array( $unbound_event_date ) ) ) ) ) );
	bookacti_update_event( $original_event_data );
	bookacti_update_exceptions( $event_id, $original_event_data[ 'exceptions_dates' ] );
	
	return $duplicated_event_id;
}


/**
 * Unbind booked occurrences of an event
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param object $event
 * @return int
 */
function bookacti_unbind_booked_occurrences( $event ) {
	$event_id = $event->event_id;
	
	// Get the booked occurrences (the original event will keep the booked occurrences only)
	$booked_events = bookacti_fetch_booked_events( array( 'events' => array( $event_id ), 'active' => 1, 'past_events' => 1 ) );
	if( empty( $booked_events[ 'events' ] ) ) { return 0; }
	
	// Duplicate the original event
	$duplicated_event_id = bookacti_duplicate_event( $event_id );
	if( ! $duplicated_event_id ) { return 0; }
	
	// Duplicate event metadata
	bookacti_duplicate_metadata( 'event', $event_id, $duplicated_event_id );
	
	// Replace all occurrences' event id in groups (we will turn it back to the original id for booked events)
	bookacti_update_grouped_event_id( $event_id, $duplicated_event_id );
	
	$max_repeat_from = $event->repeat_from;
	$min_repeat_to = $event->repeat_to;
	$events_exceptions = bookacti_get_exceptions_by_event( array( 'events' => array( $event_id ) ) );
	$original_event_exceptions = isset( $events_exceptions[ $event_id ] ) ? $events_exceptions[ $event_id ] : array();
	$booked_dates = array();
	$not_booked_dates = array();
	
	// For each booked event...
	foreach( $booked_events[ 'events' ] as $event_to_unbind ) {
		// Give back its original event id to booked occurrences
		bookacti_update_grouped_event_id( $duplicated_event_id, $event_id, $event_to_unbind[ 'start' ], $event_to_unbind[ 'end' ] );

		// Get the smallest repeat period possible
		if( ! $booked_dates ) { $max_repeat_from = substr( $event_to_unbind[ 'start' ], 0, 10 ); }
		$min_repeat_to = substr( $event_to_unbind[ 'start' ], 0, 10 );

		// Store the booked dates for exceptions
		$booked_date = substr( $event_to_unbind[ 'start' ], 0, 10 );
		if( ! in_array( $booked_date, $booked_dates, true ) ) { $booked_dates[] = $booked_date; }
	}
	
	// Add an exception on days that are not booked on the original event
	if( $max_repeat_from !== $min_repeat_to ) {
		$dummy_event = clone $event;
		$dummy_event->repeat_from = $max_repeat_from;
		$dummy_event->repeat_to = $min_repeat_to;
		$occurrences = bookacti_get_occurrences_of_repeated_event( $dummy_event, array( 'exceptions_dates' => $original_event_exceptions, 'past_events' => 1 ) );
		foreach( $occurrences as $occurrence ) {
			$occurrence_date = substr( $occurrence[ 'start' ], 0, 10 );
			if( ! in_array( $occurrence_date, $booked_dates, true ) ) { $not_booked_dates[] = $occurrence_date; }
		}
	}
	
	// Sanitize and update the duplicated event dates and exceptions
	$duplicated_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'id' => $duplicated_event_id, 'exceptions_dates' => array_unique( array_merge( $original_event_exceptions, $booked_dates ) ) ) ) );
	bookacti_update_event( $duplicated_event_data );
	bookacti_update_exceptions( $duplicated_event_id, $duplicated_event_data[ 'exceptions_dates' ] );
	
	// Sanitize and update the original event dates and exceptions
	$original_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'repeat_from' => $max_repeat_from, 'repeat_to' => $min_repeat_to, 'exceptions_dates' => array_unique( array_merge( $original_event_exceptions, $not_booked_dates ) ) ) ) );
	bookacti_update_event( $original_event_data );
	bookacti_update_exceptions( $event_id, $original_event_data[ 'exceptions_dates' ] );
	
	return $duplicated_event_id;
}


/**
 * Unbind future occurrences of an event
 * @since 1.10.0
 * @version 1.12.0
 * @param object $event
 * @param string $unbind_from Y-m-d
 * @return int
 */
function bookacti_unbind_future_occurrences( $event, $unbind_from ) {
	// Get the original events exceptions
	$events_exceptions = bookacti_get_exceptions_by_event( array( 'events' => array( $event->event_id ) ) );
	$original_event_exceptions = isset( $events_exceptions[ $event->event_id ] ) ? $events_exceptions[ $event->event_id ] : array();
	
	// Duplicate the original event and make its repetition begins on the desired date
	$duplicated_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'repeat_from' => $unbind_from, 'exceptions_dates' => $original_event_exceptions ) ) );
	$duplicated_event_id = bookacti_insert_event( $duplicated_event_data );
	if( ! $duplicated_event_id ) { return 0; }
	
	// Duplicate event metadata
	bookacti_duplicate_metadata( 'event', $event->event_id, $duplicated_event_id );
	
	// Duplicate exceptions
	bookacti_update_exceptions( $duplicated_event_id, $duplicated_event_data[ 'exceptions_dates' ] );
	
	// Replace the event_id of future grouped occurrences
	bookacti_update_grouped_event_id( $event->event_id, $duplicated_event_id, '', '', $unbind_from . ' 00:00:00' );
	
	// Change the event_id of bookings made on future events
	bookacti_update_bookings_event_id( $event->event_id, $duplicated_event_id, '', '', $unbind_from . ' 00:00:00' );
	
	// Stop the original event repetition where the new event repetition begins
	$repeat_to_dt = DateTime::createFromFormat( 'Y-m-d', $unbind_from );
	$repeat_to_dt->sub( new DateInterval( 'P1D' ) );
	
	// Update the original event dates
	$original_event_data = bookacti_sanitize_event_data( array_merge( (array) $event, array( 'repeat_to' => $repeat_to_dt->format( 'Y-m-d' ), 'exceptions_dates' => $original_event_exceptions ) ) );
	bookacti_update_event( $original_event_data );
	
	// Remove the original event's exceptions that are no longer in the repetition period
	bookacti_update_exceptions( $event->event_id, $original_event_data[ 'exceptions_dates' ] );
	
	return $duplicated_event_id;
}


/**
 * Unbind each occurrence of an event
 * @since 1.10.0
 * @version 1.12.0
 * @param object $event
 * @return array
 */
function bookacti_unbind_all_occurrences( $event ) {
	$events_exceptions = bookacti_get_exceptions_by_event( array( 'events' => array( $event->event_id ) ) );
	$original_event_exceptions = isset( $events_exceptions[ $event->event_id ] ) ? $events_exceptions[ $event->event_id ] : array();
	$occurrences = bookacti_get_occurrences_of_repeated_event( $event, array( 'exceptions_dates' => $original_event_exceptions, 'past_events' => 1 ) );
	if( ! $occurrences ) { return array(); }
	
	$event_array = (array) $event;
	$occurrences_ids = array();
	foreach( $occurrences as $occurrence ) {
		// Get the occurrence data
		$occurrence_data = bookacti_sanitize_event_data( array( 
			'template_id'   => $event_array[ 'template_id' ],
			'activity_id'   => $event_array[ 'activity_id' ],
			'title'         => $event_array[ 'title' ],
			'start'         => $occurrence[ 'start' ],
			'end'           => $occurrence[ 'end' ],
			'availability'	=> $event_array[ 'availability' ],
			'repeat_freq'	=> 'none'
		));
		
		// Create one event per occurrence
		$occurrence_id = bookacti_insert_event( $occurrence_data );
		if( ! $occurrence_id ) { continue; }
		
		// Duplicate event metadata
		bookacti_duplicate_metadata( 'event', $event->event_id, $occurrence_id );
		
		// Replace the event_id of grouped occurrences
		bookacti_update_grouped_event_id( $event->event_id, $occurrence_id, $occurrence[ 'start' ], $occurrence[ 'end' ] );
		
		// Change the event_id of bookings
		bookacti_update_bookings_event_id( $event->event_id, $occurrence_id, $occurrence[ 'start' ], $occurrence[ 'end' ] );
		
		$occurrences_ids[] = $occurrence_id;
	}
	
	// Deactivate the original event
	bookacti_deactivate_event( $event->event_id );
	
	return $occurrences_ids;
}


/**
 * Update event exceptions
 * @since 1.8.0
 * @version 1.12.0
 * @param int $object_id
 * @param array $new_exceptions
 * @param string $object_type 'event' or 'group_of_events'
 * @param array $delete_old Whether to delete the existing exceptions first
 * @return int|false
 */
function bookacti_update_exceptions( $object_id, $new_exceptions, $object_type = 'event', $delete_old = true ) {
	// Check if the exceptions already exist
	$args = $object_type === 'group_of_events' ? array( 'group_of_events' => array( $object_id ) ) : array( 'events' => array( $object_id ) );
	$old_exceptions = bookacti_get_exceptions( $args );
	$exceptions_dates = array();
	if( $old_exceptions ) {	
		foreach( $old_exceptions as $old_exception ) { 
			if( ! $old_exception[ 'exception_value' ] ) { continue; }
			$exceptions_dates[] = $old_exception[ 'exception_value' ];
		} 
	}
	$dates_to_insert = array_values( array_diff( $new_exceptions, $exceptions_dates ) );
	$dates_to_delete = array_values( array_diff( $exceptions_dates, $new_exceptions ) );
	
	if( ! $dates_to_insert && ! $dates_to_delete ) { return 0; }
	
	$updated_nb = 0;

	// Insert new exceptions
	$inserted = $dates_to_insert ? bookacti_insert_exceptions( $object_id, $dates_to_insert ) : 0;
	if( $inserted && is_numeric( $inserted ) && $object_type === 'event' ) {
		// Delete the events on exceptions from groups of events
		bookacti_delete_events_on_dates_from_group( $object_id, $dates_to_insert );
		$updated_nb += $inserted;
	}

	// Delete old exceptions
	$deleted = 0;
	if( $delete_old && $dates_to_delete ) {
		$deleted = bookacti_remove_exceptions( $object_id, $dates_to_delete, $object_type );
		if( $deleted && is_numeric( $deleted ) ) { $updated_nb += $deleted; }
	}

	if( $inserted === false || $deleted === false ) { return false; }

	return $updated_nb;
}


/**
 * Display a promo area of Prices and Credits add-on
 * @version 1.8.0
 * @param string $type
 */
function bookacti_promo_for_bapap_addon( $type = 'event' ) {
	$is_plugin_active = bookacti_is_plugin_active( 'ba-prices-and-credits/ba-prices-and-credits.php' );
	$license_status = get_option( 'bapap_license_status' );

	// If the plugin is activated but the license is not active yet
	if( $is_plugin_active && ( empty( $license_status ) || $license_status !== 'valid' ) ) {
		?>
		<div class='bookacti-addon-promo' >
			<p>
			<?php 
				/* translators: %s = add-on name */
				echo sprintf( esc_html__( 'Thank you for purchasing %s add-on!', 'booking-activities' ), '<strong>Prices and Credits</strong>' ); 
			?>
			</p><p>
				<?php esc_html_e( 'It seems you didn\'t activate your license yet. Please follow these instructions to activate your license:', 'booking-activities' ); ?>
			</p><p>
				<strong>
					<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-prices-and-credits-add-on/prerequisite-installation-license-activation-of-prices-and-credits-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-<?php echo esc_attr( $type ); ?>' target='_blank' >
						<?php 
						/* translators: %s = add-on name */
							echo sprintf( esc_html__( 'How to activate %s license?', 'booking-activities' ), 'Prices and Credits' ); 
						?>
					</a>
				</strong>
			</p>
		</div>
		<?php
	}

	else if( empty( $license_status ) || $license_status !== 'valid' ) {
		?>
		<div class='bookacti-addon-promo' >
			<?php 
			$addon_link = '<a href="https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=encart-promo-' . $type . '" target="_blank" >Prices and Credits</a>';
			$message = '';
			$event_name = '';
			if( $type === 'group-of-events' ) {
				/* translators: %s is the placeholder for Prices and Credits add-on link */
				$message = esc_html__( 'Set a price or a promotion in cash or in credits on your groups of events with %s add-on !', 'booking-activities' );
				$event_name = esc_html__( 'My grouped event', 'booking-activities' );
			} else {
				/* translators: %s is the placeholder for Prices and Credits add-on link */
				$message = esc_html__( 'Set a price or a promotion in cash or in credits on your events with %s add-on !', 'booking-activities' );
				$event_name = esc_html__( 'My event', 'booking-activities' );
			}
			echo sprintf( $message, $addon_link );
			$price_div_style = 'display: block; width: fit-content; white-space: nowrap; margin: 4px auto; padding: 5px; font-weight: bolder; font-size: 1.2em; border: 1px solid #fff; -webkit-border-radius: 3px;  border-radius: 3px;  background-color: rgba(0,0,0,0.3); color: #fff;';
			?>
			<div class='bookacti-promo-events-examples'>
				<a class='fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event' >
					<div class='fc-content'>
						<div class='fc-time' data-start='7:00' data-full='7:00 AM - 8:30 AM'>
							<span>7:00 - 8:30</span>
						</div>
						<div class='fc-title'><?php echo $event_name; ?></div>
					</div>
					<div class='fc-bg'></div>
					<div class='bookacti-availability-container'>
						<span class='bookacti-available-places bookacti-not-booked '>
							<span class='bookacti-available-places-number'>50</span>
							<span class='bookacti-available-places-unit-name'> </span>
							<span class='bookacti-available-places-avail-particle'> <?php esc_html( _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ) ); ?></span>
						</span>
					</div>
					<div class='bookacti-price-container' style='<?php echo esc_attr( $price_div_style ); ?>'>
						<span class='bookacti-price bookacti-promo'>$30</span>
					</div>
				</a>
				<a class='fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event' >
					<div class='fc-content'>
						<div class='fc-time' data-start='7:00' data-full='7:00 AM - 8:30 AM'>
							<span>7:00 - 8:30</span>
						</div>
						<div class='fc-title'><?php echo $event_name; ?></div>
					</div>
					<div class='fc-bg'></div>
					<div class='bookacti-availability-container'>
						<span class='bookacti-available-places bookacti-not-booked '>
							<span class='bookacti-available-places-number'>50</span>
							<span class='bookacti-available-places-unit-name'> </span>
							<span class='bookacti-available-places-avail-particle'> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ); ?></span>
						</span>
					</div>
					<div class='bookacti-price-container' style='<?php echo esc_attr( $price_div_style ); ?>'>
						<span class='bookacti-price bookacti-promo'>- 20%</span>
					</div>
				</a>
				<a class='fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event' >
					<div class='fc-content'>
						<div class='fc-time' data-start='7:00' data-full='7:00 AM - 8:30 AM'>
							<span>7:00 - 8:30</span>
						</div>
						<div class='fc-title'><?php echo $event_name; ?></div>
					</div>
					<div class='fc-bg'></div>
					<div class='bookacti-availability-container'>
						<span class='bookacti-available-places bookacti-not-booked '>
							<span class='bookacti-available-places-number'>50</span>
							<span class='bookacti-available-places-unit-name'> </span>
							<span class='bookacti-available-places-avail-particle'> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ); ?></span>
						</span>
					</div>
					<div class='bookacti-price-container' style='<?php echo esc_attr( $price_div_style ); ?>'>
						<span class='bookacti-price bookacti-promo'>
							<?php 
							$amount = 12;
							/* translators: %d is an integer (an amount of credits) */
							echo sprintf( _n( '%d credit', '%d credits', $amount, 'booking-activities' ), $amount ); 
							?>
						</span>
					</div>
				</a>
			</div>
			<div><a href='https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=encart-promo-<?php echo $type; ?>' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
		</div>
		<?php
	}
}




// GROUP OF EVENTS

/**
 * Check if a group category exists
 * 
 * @since 1.1.0
 * 
 * @param int $category_id
 * @param int $template_id
 * @return boolean
 */
function bookacti_group_category_exists( $category_id, $template_id = null ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	$available_category_ids = bookacti_get_group_category_ids_by_template( $template_id );
	foreach( $available_category_ids as $available_category_id ) {
		if( intval( $category_id ) === intval( $available_category_id ) ) {
			return true;
		}
	}

	return false;
}



/**
 * Update events of a group
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @param array $new_events [[id: int, start: "Y-m-d H:i:s", end: "Y-m-d H:i:s"], ...]
 * @return int|boolean
 */
function bookacti_update_events_of_group( $group_id, $new_events ) {
	// Get events currently in the group
	$current_events = bookacti_get_group_events( $group_id );

	// Determine what events are to be added or removed
	$to_insert = $new_events;
	$to_delete = $current_events;
	foreach( $new_events as $i => $new_event ) {
		foreach( $current_events as $j => $current_event ) {
			// If the event already exists, remove it from both arrays
			if( intval( $current_event[ 'id' ] ) === intval( $new_event[ 'id' ] )
			&&  $current_event[ 'start' ] === $new_event[ 'start' ]
			&&  $current_event[ 'end' ] === $new_event[ 'end' ] ) {
				unset( $to_insert[ $i ] );
				unset( $to_delete[ $j ] );
				break;
			}
		}
	}

	// Delete old events
	$deleted = $to_delete ? bookacti_delete_events_from_group( $group_id, $to_delete ) : 0;

	// Insert new events
	$inserted = $to_insert ? bookacti_insert_events_into_group( $group_id, $to_insert ) : 0;

	return $deleted === false && $inserted = false ? false : intval( $deleted ) + intval( $inserted );
}


/**
 * Retrieve template groups of events list
 * @since 1.1.0
 * @version 1.11.0
 * @param int $template_id
 * @return string|boolean
 */
function bookacti_get_template_groups_of_events_list( $template_id ) {
	if( ! $template_id ) { return false; }

	$current_user_can_edit_template	= current_user_can( 'bookacti_edit_templates' );
	
	// Retrieve groups by categories
	$categories	= bookacti_get_group_categories( $template_id );
	$groups		= bookacti_get_groups_of_events( array( 'templates' => array( $template_id ) ) );
	
	// Sort the group categories by custom order
	$categories_order = bookacti_get_metadata( 'template', $template_id, 'group_categories_order', true );
	if( $categories_order ) {
		$ordered_categories = array();
		foreach( $categories_order as $category_id ) {
			if( isset( $categories[ $category_id ] ) ) { $ordered_categories[] = $categories[ $category_id ]; unset( $categories[ $category_id ] ); }
		}
		$categories = array_merge( $ordered_categories, $categories );
	}
	
	ob_start();
	
	foreach( $categories as $category ) {
		$category_short_title = strlen( $category[ 'title' ] ) > 16 ? substr( $category[ 'title' ], 0, 16 ) . '&#8230;' : $category[ 'title' ];
	?>
		<div class='bookacti-group-category' data-group-category-id='<?php echo $category[ 'id' ]; ?>' data-show-groups='0' data-visible='1'>
			<div class='bookacti-group-category-title' title='<?php echo $category[ 'title' ]; ?>' >
				<span><?php echo $category_short_title; ?></span>
			</div>
	<?php
		if( $current_user_can_edit_template ) {
			?><div class='bookacti-update-group-category dashicons dashicons-admin-generic' ></div><?php
		}
	?>
			<div class='bookacti-groups-of-events-editor-list bookacti-custom-scrollbar' >
			<?php
				// Sort the groups of events by custom order
				$ordered_groups = $groups;
				$groups_order = bookacti_get_metadata( 'group_category', $category[ 'id' ], 'groups_of_events_order', true );
				if( $groups_order ) {
					$sorted_groups = array();
					foreach( $groups_order as $group_id ) {
						if( isset( $ordered_groups[ $group_id ] ) ) { $sorted_groups[] = $ordered_groups[ $group_id ]; unset( $ordered_groups[ $group_id ] ); }
					}
					$ordered_groups = array_merge( $sorted_groups, $ordered_groups );
				}
			
				foreach( $ordered_groups as $group ) {
					if( $group[ 'category_id' ] === $category[ 'id' ] ) {
						$group_title = strip_tags( $group[ 'title' ] );
					?>
						<div class='bookacti-group-of-events' data-group-id='<?php echo $group[ 'id' ]; ?>' >
							<div class='bookacti-group-of-events-title' title='<?php echo $group_title; ?>' ><?php echo $group_title; ?></div>
					<?php
						if( $current_user_can_edit_template ) {
							?><div class='bookacti-update-group-of-events dashicons dashicons-admin-generic' ></div><?php
						}
					?>
						</div>
					<?php
					}
				}
			?>
			</div>
		</div>
	<?php
	}

	return ob_get_clean();
}