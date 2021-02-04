<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// EVENTS

/**
 * Fetch events to display on calendar editor
 * @since 1.1.0 (replace bookacti_fetch_events from 1.0.0)
 * @version 1.9.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates Array of template IDs
 *  @type array events Array of event IDs
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to compute past events
 *  @type boolean $bounding_events_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_fetch_events_for_calendar_editor( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'events' => array(),
		'interval' => array(),
		'skip_exceptions' => 0,
		'past_events' => 1,
		'bounding_events_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );

	global $wpdb;

	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );

	// Select events
	$query  = 'SELECT E.id as event_id, E.template_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.is_resizable, A.id as activity_id, T.start_date as template_start,  T.end_date as template_end ' 
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T'
			. ' WHERE E.activity_id = A.id '
			. ' AND E.template_id = T.id '
			. ' AND E.active = 1 ';

	$variables = array();

	// Filter by event ids
	if( $args[ 'events' ] ) {
		$query .= ' AND E.id IN ( %d';
		for( $i=1, $len=count( $args[ 'events' ] ); $i < $len; ++$i ) {
			$query .= ', %d ';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'events' ] );
	}

	// Filter by template ids
	if( $args[ 'templates' ] ) {
		$query .= ' AND E.template_id IN ( %d';
		for( $i=1, $len=count( $args[ 'templates' ] ); $i < $len; ++$i ) {
			$query .= ', %d ';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}

	// Do not fetch events out of the desired interval
	if( $args[ 'interval' ] ) {
		$query .= ' 
		AND (
				( 	NULLIF( E.repeat_freq, "none" ) IS NULL 
					AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						AND
							UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						) 
				) 
				OR
				( 	E.repeat_freq IS NOT NULL
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_to, %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							)
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, %s, @@global.time_zone ) ) > 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_to, %s, @@global.time_zone ) ) > 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							)
				) 
			)';
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'start' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'end' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'start' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'start' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'end' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'end' ];
		$variables[] = $user_timestamp_offset;
	}

	// Safely apply variables to the query
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	// Allow plugins to change the query
	$query = apply_filters( 'bookacti_get_events_for_editor_query', $query, $args );

	$events = $wpdb->get_results( $query, OBJECT );

	// Transform raw events from database to array of individual events
	$events_array = bookacti_get_events_array_from_db_events( $events, $args );

	return apply_filters( 'bookacti_get_events_for_editor', $events_array, $query, $args ) ;
}


/**
 * Insert an event
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param array $data Data sanitized with bookacti_sanitize_event_data
 * @return int
 */
function bookacti_insert_event( $data ) {
	global $wpdb;
	
	$wpdb->insert( 
		BOOKACTI_TABLE_EVENTS, 
		array( 
			'template_id'   => $data[ 'template_id' ],
			'activity_id'   => $data[ 'activity_id' ],
			'title'         => $data[ 'title' ],
			'start'         => $data[ 'start' ],
			'end'           => $data[ 'end' ],
			'availability'	=> $data[ 'availability' ]
		),
		array( '%d', '%d', '%s', '%s', '%s', '%d' )
	);
	
	$event_id = $wpdb->insert_id;
	
	$meta = array_intersect_key( $data, bookacti_get_event_default_meta() );
	if( $meta && $event_id ) { bookacti_update_metadata( 'event', $event_id, $meta ); }

	return $event_id;
}


/**
 * Change event dates and time or duplicate these new values to another event
 * @since 1.2.2 (was bookacti_update_event)
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $action
 * @param int $delta_days
 * @param boolean $is_duplicated
 * @return int|false|null
 */
function bookacti_move_or_resize_event( $event_id, $event_start, $event_end, $action, $delta_days = 0, $is_duplicated = false ) {
	global $wpdb;

	$event_query = 'SELECT * FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d ';
	$event_query_prep = $wpdb->prepare( $event_query, $event_id );
	$event = $wpdb->get_row( $event_query_prep, OBJECT );
	
	if( ! $event ) { return false; }
	
	$values         = array( 'start' => $event_start, 'end' => $event_end, 'repeat_from' => $event->repeat_from, 'repeat_to' => $event->repeat_to );
	$values_format  = array( '%s', '%s', '%s', '%s' );

	if( $action === 'move' && $event->repeat_freq !== 'none' && $delta_days !== 0 ) {
		// Delay by the same amount of time the repetion period
		$repeat_from_datetime   = DateTime::createFromFormat('Y-m-d', $event->repeat_from );
		$repeat_to_datetime     = DateTime::createFromFormat('Y-m-d', $event->repeat_to );
		$delta_days_interval	= DateInterval::createFromDateString( abs( $delta_days ) . ' days' );

		if( $delta_days > 0 ) {
			$repeat_from_datetime->add( $delta_days_interval );
			$repeat_to_datetime->add( $delta_days_interval );
		} else if( $delta_days < 0 ) {
			$repeat_from_datetime->sub( $delta_days_interval );
			$repeat_to_datetime->sub( $delta_days_interval );
		}

		// Format the new repeat from and repeat to value
		$values['repeat_from']	= $repeat_from_datetime->format( 'Y-m-d' );
		$values['repeat_to']	= $repeat_to_datetime->format( 'Y-m-d' );
	}

	if( $is_duplicated ) {
		$query			= ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to ) '
						. ' SELECT template_id, activity_id, title, %s, %s, availability, repeat_freq, NULLIF( %s, "" ), NULLIF( %s, "" ) '
						. ' FROM ' . BOOKACTI_TABLE_EVENTS
						. ' WHERE id = %d ';
		$query_prep		= $wpdb->prepare( $query, $values['start'], $values['end'], $values['repeat_from'], $values['repeat_to'], $event_id );
		$inserted		= $wpdb->query( $query_prep );
		$new_event_id	= $wpdb->insert_id;
		
		if( ! $new_event_id ) { return $new_event_id; }
		
		// Duplicate exceptions
		$exceptions = bookacti_duplicate_exceptions( $event_id, $new_event_id );

		// Duplicate event metadata
		$duplicated = bookacti_duplicate_metadata( 'event', $event_id, $new_event_id );

		return $new_event_id;

	} else {
		// Update the event
		$updated = $wpdb->update( 
			BOOKACTI_TABLE_EVENTS, 
			$values,
			array( 'id' => $event_id ),
			$values_format,
			array( '%d' )
		);

		return $updated;
	}        
}


/**
 * Update event data
 * @since 1.2.2 (was bookacti_set_event_data)
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param array $data Data sanitized with bookacti_sanitize_event_data
 * @return int|false
 */
function bookacti_update_event( $data ) {
	global $wpdb;
	
	// Get event
	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
	$query = $wpdb->prepare( $query, $data[ 'id' ] );
	$initial_event = $wpdb->get_row( $query );
	
	if( ! $initial_event ) { return false; }
	
	// Update the event
	$updated_event = $wpdb->update( 
		BOOKACTI_TABLE_EVENTS, 
		array( 
			'title'         => $data[ 'title' ],
			'availability'  => $data[ 'availability' ],
			'start'         => $data[ 'start' ],
			'end'           => $data[ 'end' ],
			'repeat_freq'   => $data[ 'repeat_freq' ],
			'repeat_from'   => $data[ 'repeat_from' ],
			'repeat_to'     => $data[ 'repeat_to' ]
		),
		array( 'id' => $data[ 'id' ] ),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);

	// If event repeat frequency has changed, we must remove this event from all groups
	if( $data[ 'repeat_freq' ] !== $initial_event->repeat_freq ) {
		bookacti_delete_event_from_groups( $data[ 'id' ] );
	}

	// If the repetition dates have changed, we must delete out of range grouped events
	else if( $data[ 'repeat_from' ] !== $initial_event->repeat_from || $data[ 'repeat_to' ] !== $initial_event->repeat_to ) {
		bookacti_delete_out_of_range_occurrences_from_groups( $data[ 'id' ] );
	}
	
	// Update meta
	$meta = array_intersect_key( $data, bookacti_get_event_default_meta() );
	if( $meta ) { 
		$updated_meta = bookacti_update_metadata( 'event', $data[ 'id' ], $meta );
		if( is_numeric( $updated_event ) && is_numeric( $updated_meta ) ) { $updated_event += $updated_meta; }
	}
	
	// Update exceptions
	$updated_excep = bookacti_update_exceptions( $data[ 'id' ], $data[ 'exceptions_dates' ] );
	if( is_numeric( $updated_event ) && is_numeric( $updated_excep ) ) { $updated_event += $updated_excep; }
	
	return $updated_event;
}


/**
 * Deactivate an event
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_deactivate_event( $event_id ) {
	global $wpdb;

	// Deactivate the event
	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_EVENTS, 
		array( 
			'active' => 0
		),
		array( 'id' => $event_id ),
		array( '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Get event template id
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_get_event_template_id( $event_id ) {
	global $wpdb;

	$query			= 'SELECT template_id FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
	$query_prep		= $wpdb->prepare( $query, $event_id );
	$template_id	= $wpdb->get_var( $query_prep );

	return $template_id;
}


/**
 * Unbind selected occurrence of an event
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return int
 */
function bookacti_unbind_selected_occurrence( $event_id, $event_start, $event_end ) {
	global $wpdb;

	// Create another similar event instead
	$query_duplicate_event	= ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability ) '
							. ' SELECT template_id, activity_id, title, %s, %s, availability '
							. ' FROM ' . BOOKACTI_TABLE_EVENTS 
							. ' WHERE id = %d ';
	$prep_duplicate_event	= $wpdb->prepare( $query_duplicate_event, $event_start, $event_end, $event_id );
	$insert_event			= $wpdb->query( $prep_duplicate_event );
	$unbound_event_id		= $wpdb->insert_id;
	
	if( ! $unbound_event_id ) { return $unbound_event_id; }
	
	// Duplicate event metadata
	$duplicated = bookacti_duplicate_metadata( 'event', $event_id, $unbound_event_id );

	// If the event was part of a group, change its id to the new one
	bookacti_update_grouped_event_id( $event_id, $event_start, $event_end, $unbound_event_id );

	// Create an exception on the day of the occurrence
	$dates_excep_array = array ( substr( $event_start, 0, 10 ) );
	$insert_excep = bookacti_update_exceptions( $event_id, $dates_excep_array, false );

	// If the event was booked, move its bookings to the new single event
	$bookings_moved = $wpdb->update( 
		BOOKACTI_TABLE_BOOKINGS, 
		array( 
			'event_id' => $unbound_event_id
		),
		array( 
			'event_id' => $event_id,
			'event_start' => $event_start,
			'event_end' => $event_end,
		),
		array( '%d' ),
		array( '%d', '%s', '%s' )
	);

	return $unbound_event_id;
}


/**
 * Unbind booked occurrences of an event
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int
 */
function bookacti_unbind_booked_occurrences( $event_id ) {
	global $wpdb;

	// Duplicate the original event and its exceptions
	$duplicated_event_id = bookacti_duplicate_event( $event_id );
	
	if( ! $duplicated_event_id ) { return false; }
	
	// Get occurrences to unbind
	$booked_events_query = 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE event_id = %d AND active = 1 ORDER BY event_start ASC ';
	$booked_events_prep = $wpdb->prepare( $booked_events_query, $event_id );
	$booked_events = $wpdb->get_results( $booked_events_prep, OBJECT );

	// Replace all occurrences' event id in groups (we will turn it back to the original id for booked events)
	bookacti_update_grouped_event_id( $event_id, false, false, $duplicated_event_id );

	// For each occurrence to unbind...
	$first_booking  = '';
	$last_booking   = '';
	$duplicated_event_exceptions = array();
	foreach( $booked_events as $event_to_unbind ) {
		// Give back its original event id to booked occurrences
		bookacti_update_grouped_event_id( $duplicated_event_id, $event_to_unbind->event_start, $event_to_unbind->event_end, $event_id );

		// Get the smallest repeat period possible
		if( $first_booking === '' ) { $first_booking = substr( $event_to_unbind->event_start, 0, 10 ); }
		$last_booking = substr( $event_to_unbind->event_start, 0, 10 );

		// Create an exception on the day of the occurrence
		$duplicated_event_exceptions[] = substr( $event_to_unbind->event_start, 0, 10 );
	}
	bookacti_update_exceptions( $duplicated_event_id, $duplicated_event_exceptions, false );

	// Set the smallest repeat period possible to the original event
	$wpdb->update( 
		BOOKACTI_TABLE_EVENTS, 
		array( 
			'repeat_from' => $first_booking,
			'repeat_to' => $last_booking,
		),
		array( 'id' => $event_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	// Add an exception on days that are not booked on the original event
	$date_timestamp = strtotime( $first_booking );
	$date = date( 'Y-m-d', $date_timestamp );
	$original_event_exceptions = array ();
	while( $date != $last_booking ) {
		$set_exception = true;
		foreach( $booked_events as $booked_event ) {
			$date_booked = substr( $booked_event->event_start, 0, 10 );
			if( $date_booked == $date ) { $set_exception = false; }
		}

		if( $set_exception ) { $original_event_exceptions[] = $date; }

		$date_timestamp = strtotime( '+1 day', $date_timestamp );
		$date = date( 'Y-m-d', $date_timestamp );
	}
	bookacti_update_exceptions( $event_id, $original_event_exceptions, false );

	return $duplicated_event_id;
}


/**
 * Duplicate an event and its exceptions and return its ID
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_duplicate_event( $event_id ) {
	global $wpdb;

	//Duplicate the original event
	$duplicate_event_query  = 'INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to ) '
							. ' SELECT template_id, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to ' 
							. ' FROM ' . BOOKACTI_TABLE_EVENTS 
							. ' WHERE id = %d';
	$duplicate_event_prep   = $wpdb->prepare( $duplicate_event_query, $event_id );
	$duplicated_event       = $wpdb->query( $duplicate_event_prep, OBJECT);
	$duplicated_event_id    = $wpdb->insert_id;

	if( ! $duplicated_event || ! $duplicated_event_id ) { return false; }
	
	// Duplicate event metadata
	bookacti_duplicate_metadata( 'event', $event_id, $duplicated_event_id );

	// Duplicate the exceptions and link them to the new created event
	// Get exceptions
	$exceptions_query  = 'SELECT * FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' WHERE event_id = %d';
	$exceptions_prep    = $wpdb->prepare( $exceptions_query, $event_id );
	$exceptions         = $wpdb->get_results( $exceptions_prep, OBJECT);

	$excep_inserted = true;
	foreach ( $exceptions as $exception ) {
		$inserted = $wpdb->insert( 
			BOOKACTI_TABLE_EXCEPTIONS, 
			array( 
				'event_id' => $duplicated_event_id,
				'exception_type'    => $exception->exception_type,
				'exception_value'   => $exception->exception_value
			),
			array( '%d', '%s', '%s' )
		);
		if( ! $inserted ) { $excep_inserted = false; }
	}

	return $duplicated_event_id;
}


/**
 * Get min availability
 * 
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int
 */
function bookacti_get_min_availability( $event_id ) {
	global $wpdb;

	// Get all different booked occurrences of the event
	$booked_occurrences_query   = 'SELECT SUM( quantity ) '
								. ' FROM ' . BOOKACTI_TABLE_BOOKINGS 
								. ' WHERE active = 1 '
								. '	AND event_id = %d '
								. '	GROUP BY CONCAT( event_id, event_start, event_end ) '
								. '	ORDER BY quantity '
								. '	DESC LIMIT 1 ';
	$booked_occurrences_prep    = $wpdb->prepare( $booked_occurrences_query, $event_id );
	$booked_occurrences         = $wpdb->get_var( $booked_occurrences_prep );

	if( ! $booked_occurrences ) { $booked_occurrences = 0; }

	return $booked_occurrences;
}


/**
 * Get the period of time between the first and the last booking of an event / a template
 * 
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $template_id
 * @param int $event_id
 * @return false|array
 */
function bookacti_get_min_period( $template_id = NULL, $event_id = NULL ) {
	global $wpdb;

	// Get min period for event
	if( $event_id ) {
		$period_query   = 'SELECT event_start FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE active = 1 AND event_id = %d ORDER BY event_start ';
		$min_from_query = $period_query . ' ASC LIMIT 1';
		$min_to_query   = $period_query . ' DESC LIMIT 1';
		$var            = $event_id;

	// Get min period for template
	} else if( $template_id ) {
		$period_query   =  'SELECT B.event_start FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_EVENTS . ' as E '
						. ' WHERE B.active = 1 '
						. ' AND B.event_id = E.id '
						. ' AND E.template_id = %d ' 
						. ' ORDER BY B.event_start ';
		$min_from_query = $period_query . ' ASC LIMIT 1';
		$min_to_query   = $period_query . ' DESC LIMIT 1';
		$var            = $template_id;
	}
	$min_from_query_prep= $wpdb->prepare( $min_from_query, $var );
	$min_to_query_prep  = $wpdb->prepare( $min_to_query, $var );
	$first_booking      = $wpdb->get_row( $min_from_query_prep, OBJECT );
	$last_booking       = $wpdb->get_row( $min_to_query_prep, OBJECT );

	$period = false;
	if( $first_booking && $last_booking ) {
		$period = array(
			'from' => substr( $first_booking->event_start, 0, 10 ), 
			'to' => substr( $last_booking->event_start, 0, 10 )
		);
	}

	return $period;
}




// EXCEPTIONS

/**
 * Insert event repeat exceptions
 * @since 1.7.0 (was bookacti_insert_exeptions before)
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param array $dates
 * @return boolean
 */
function bookacti_insert_exceptions( $event_id, $dates ) {
	global $wpdb;

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_EXCEPTIONS . ' ( event_id, exception_type, exception_value ) VALUES ';
	$variables = array();

	$i = 1;
	$len = count( $dates );
	foreach( $dates as $date ) {
		$query .= '( %d, "date", %s )';
		if( $i < $len ) { $query .= ', '; }
		$variables[] = $event_id;
		$variables[] = $date;
		++$i;
	}

	$query = $wpdb->prepare( $query, $variables );
	$inserted = $wpdb->query( $query );

	return $inserted;
}


/**
 * Duplicate event repeat exceptions for another event
 * @global wpdb $wpdb
 * @param int $old_event_id
 * @param int $new_event_id
 * @return int|false
 */
function bookacti_duplicate_exceptions( $old_event_id, $new_event_id ) {
	global $wpdb;

	// Duplicate the exceptions and bind them to the newly created event
	$query		= ' INSERT INTO ' . BOOKACTI_TABLE_EXCEPTIONS . ' ( event_id, exception_type, exception_value ) '
				. ' SELECT %d, exception_type, exception_value ' 
				. ' FROM ' . BOOKACTI_TABLE_EXCEPTIONS
				. ' WHERE event_id = %d ';
	$query_prep	= $wpdb->prepare( $query, $new_event_id, $old_event_id );
	$inserted	= $wpdb->query( $query_prep );

	return $inserted;
}


/**
 * Remove event repeat exceptions
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param array $dates
 * @return int|false
 */
function bookacti_remove_exceptions( $event_id, $dates ) {
	global $wpdb;

	$query = 'DELETE FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' WHERE event_id = %d AND exception_type = "date" AND ( ';
	$variables = array( $event_id );

	$i = 1;
	$len = count( $dates );
	foreach( $dates as $date ) {
		$query .= 'exception_value = %s';
		if( $i < $len ) { $query .= ' OR '; }
		$variables[] = $date;
		++$i;
	}

	$query .= ' )';

	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}




// GROUP OF EVENTS

/**
 * Insert a group of events
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $category_id
 * @param string $group_title
 * @param array $group_meta
 * @return int
 */
function bookacti_insert_group_of_events( $category_id, $group_title = '', $group_meta = array() ) {
	if( empty( $category_id ) ) {
		return false;
	}

	if( empty( $group_title ) ) {
		$group_title = '';
	}

	global $wpdb;

	// Insert the new group of events
	$wpdb->insert( 
		BOOKACTI_TABLE_EVENT_GROUPS, 
		array( 
			'category_id'	=> $category_id,
			'title'			=> $group_title,
			'active'		=> 1
		),
		array( '%d', '%s', '%d' )
	);

	$group_id = $wpdb->insert_id;

	if( ! empty( $group_meta ) && ! empty( $group_id ) ) {
		bookacti_insert_metadata( 'group_of_events', $group_id, $group_meta );
	}

	return $group_id;
}


/**
 * Update a group of events
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $group_id
 * @param int $category_id
 * @param string $group_title
 * @return int|boolean
 */
function bookacti_update_group_of_events( $group_id, $category_id, $group_title = '' ) {
	if( empty( $group_id ) || empty( $category_id ) ) {
		return false;
	}

	if( empty( $group_title ) ) {
		$group_title = '';
	}

	global $wpdb;

	// Update the group of events
	$updated = $wpdb->update( 
		BOOKACTI_TABLE_EVENT_GROUPS, 
		array( 
			'category_id' => $category_id,
			'title' => $group_title
		),
		array( 'id' => $group_id ),
		array( '%d', '%s' ),
		array( '%d' )
	);

	return $updated;
}


/**
 * Delete a group of events
 * 
 * @global wpdb $wpdb
 * @param type $group_id
 * @return boolean
 */
function bookacti_delete_group_of_events( $group_id ) {
	if( empty( $group_id ) || ! is_numeric( $group_id ) ) {
		return false;
	}

	global $wpdb;

	// Delete events of the group
	$query_events	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS 
					. ' WHERE group_id = %d ';
	$prep_events	= $wpdb->prepare( $query_events, $group_id );
	$deleted1		= $wpdb->query( $prep_events );

	// Delete the group itself
	$query_group	= 'DELETE FROM ' . BOOKACTI_TABLE_EVENT_GROUPS 
					. ' WHERE id = %d ';
	$prep_group		= $wpdb->prepare( $query_group, $group_id );
	$deleted2		= $wpdb->query( $prep_group );

	if( $deleted1 === false && $deleted2 === false ) {
		return false;
	}

	$deleted = intval( $deleted1 ) + intval( $deleted2 );

	return $deleted;
}


/**
 * Deactivate a group of events
 * 
 * @global wpdb $wpdb
 * @param type $group_id
 * @return boolean
 */
function bookacti_deactivate_group_of_events( $group_id ) {
	if( empty( $group_id ) || ! is_numeric( $group_id ) ) {
		return false;
	}

	global $wpdb;

	// Deactivate events of the group
	$updated1 = $wpdb->update( 
		BOOKACTI_TABLE_GROUPS_EVENTS, 
		array( 
			'active' => 0
		),
		array( 'group_id' => $group_id ),
		array( '%d' ),
		array( '%d' )
	);

	// Deactivate the group itself
	$updated2 = $wpdb->update( 
		BOOKACTI_TABLE_EVENT_GROUPS, 
		array( 
			'active' => 0
		),
		array( 'id' => $group_id ),
		array( '%d' ),
		array( '%d' )
	);

	if( $updated1 === false && $updated2 === false ) {
		return false;
	}

	$deactivated = intval( $updated1 ) + intval( $updated2 );

	return $deactivated;
}


/**
 * Get an array of all group of events ids bound to designated category
 * 
 * @since 1.1.0
 * @version 1.3.0
 * 
 * @global wpdb $wpdb
 * @param array $category_ids
 * @param boolean $fetch_inactive
 * @return array
 */
function bookacti_get_groups_of_events_ids_by_category( $category_ids = array(), $fetch_inactive = false ) {

	global $wpdb;

	// Convert numeric to array
	if( ! is_array( $category_ids ) ){
		$category_id = intval( $category_ids );
		$category_ids = array();
		if( $category_id ) {
			$category_ids[] = $category_id;
		}
	}

	$query	= 'SELECT G.id FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
			. ' WHERE G.category_id IN (';

	$i = 1;
	foreach( $category_ids as $category_id ){
		$query .= ' %d';
		if( $i < count( $category_ids ) ) { $query .= ','; }
		$i++;
	}

	$query .= ' ) ';

	if( ! $fetch_inactive ) {
		$query .= ' AND G.active = 1 ';
	}

	if( $category_ids ) {
		$query = $wpdb->prepare( $query, $category_ids );
	}

	$groups	= $wpdb->get_results( $query, OBJECT );

	$groups_ids = array();
	foreach( $groups as $group ) {
		$groups_ids[] = $group->id;
	}

	return $groups_ids;
}


/**
 * Get the template id of a group of events
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $group_id
 * @return int|boolean
 */
function bookacti_get_group_of_events_template_id( $group_id ) {
	if( empty( $group_id ) || ! is_numeric( $group_id ) ) {
		return false;
	}

	global $wpdb;

	$query			= 'SELECT C.template_id '
					. ' FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES  . ' as C, ' . BOOKACTI_TABLE_EVENT_GROUPS  . ' as G '
					. ' WHERE C.id = G.category_id '
					. ' AND G.id = %d ';
	$query_prep		= $wpdb->prepare( $query, $group_id );
	$template_id	= $wpdb->get_var( $query_prep );

	return $template_id;
}




// GROUPS X EVENTS

/**
 * Insert events into a group
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param array $events
 * @param int $group_id
 * @return int|boolean|null
 */
function bookacti_insert_events_into_group( $events, $group_id ) {

	$group_id = intval( $group_id );
	if( ! is_array( $events ) || empty( $events ) || empty( $group_id ) ) {
		return false;
	}

	global $wpdb;

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' ( group_id, event_id, event_start, event_end, active ) ' . ' VALUES ';

	$i = 0;
	$variables_array = array();
	foreach( $events as $event ) {
		if( $i > 0 ) { $query .= ','; } 
		$query .= ' ( %d, %d, %s, %s, %d ) ';
		$variables_array[] = $group_id;
		$variables_array[] = intval( $event->id );
		$variables_array[] = $event->start;
		$variables_array[] = $event->end;
		$variables_array[] = 1;
		$i++;
	}

	$prep		= $wpdb->prepare( $query, $variables_array );
	$inserted	= $wpdb->query( $prep );

	return $inserted;
}


/**
 * Delete events from a group
 * @since 1.1.0
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param array $events
 * @param int $group_id
 * @return int|boolean|null
 */
function bookacti_delete_events_from_group( $events, $group_id = 0 ) {
	if( ! $events || ! is_array( $events ) ) { return false; }

	global $wpdb;

	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE ( ';
	$variables = array();

	$i = 0;
	foreach( $events as $event ) {
		$event = (object) $event;
		$query .= ' ( event_id = %d AND event_start = %s AND event_end = %s ) ';
		$variables[] = ! empty( $event->event_id ) ? $event->event_id : ( ! empty( $event->id ) ? $event->id : 0 );
		$variables[] = $event->start;
		$variables[] = $event->end;
		$i++;
		if( $i < count( $events ) ) { $query .= ' OR '; } 
	}

	$query .= ' ) ';

	if( $group_id ) {
		$query .= 'AND group_id = %d';
		$variables[] = $group_id;
	}

	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Delete events starting on a specific date from a group 
 * @since 1.8.0
 * @global wpdb $wpdb
 * @param array $events
 * @param int|string $group_id
 * @return int|boolean
 */
function bookacti_delete_events_on_dates_from_group( $dates, $group_id = 0 ) {
	if( ! $dates || ! is_array( $dates ) ) { return false; }

	global $wpdb;

	$query = 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE ( ';
	$variables = array();

	$i = 0;
	foreach( $dates as $date ) {
		$event = (object) $event;
		$query .= 'DATE( event_start ) = %s';
		$variables[] = $date;
		$i++;
		if( $i < count( $dates ) ) { $query .= ' OR '; } 
	}

	$query .= ' ) ';

	// Update only a specific group
	if( $group_id ) {
		$query .= 'AND group_id = %d';
		$variables[] = $group_id;
	}

	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Delete an event from all groups of events
 * @since 1.1.0
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return int|boolean|null
 */
function bookacti_delete_event_from_groups( $event_id, $event_start = false, $event_end = false ) {
	if( ! $event_id ) { return false; }

	global $wpdb;

	$query = 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE event_id = %d ';

	$variables = array( $event_id );

	if( $event_start ) {
		$query .= strlen( $event_start ) === 10 ? ' AND DATE( event_start ) = %s ' : ' AND event_start = %s ';
		$variables[] = $event_start;
	}

	if( $event_end ) {
		$query .= strlen( $event_end ) === 10 ? ' AND DATE( event_end ) = %s ' : ' AND event_end = %s ';
		$variables[] = $event_end;
	}

	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Delete events from specific activiy and template from all groups of events
 * 
 * @since 1.1.4
 * @version 1.3.0
 * 
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param int $template_id
 * @return int|boolean|null
 */
function bookacti_deactivate_activity_events_from_groups( $activity_id = 0, $template_id = 0 ){

	$activity_id = intval( $activity_id );
	$template_id = intval( $template_id );
	if( ! $activity_id && ! $template_id ) {
		return false;
	}

	global $wpdb;

	// Update grouped events to inactive
	$query	= ' UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON GE.event_id = E.id '
			. ' SET GE.active = 0 '
			. ' WHERE TRUE ';

	$variables = array();

	if( $activity_id ) {
		$query .= ' AND E.activity_id = %d ';
		$variables[] = $activity_id;
	}
	if( $template_id ) {
		$query .= ' AND E.template_id = %d ';
		$variables[] = $template_id;
	}

	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	$deactivated = $wpdb->query( $query );

	return $deactivated;
}



/**
 * Delete event occurrences that are beyond repeat dates from all groups
 * @since 1.8.4 (was bookacti_delete_out_of_range_occurences_from_groups)
 * @version 1.8.5
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return int|boolean|null
 */
function bookacti_delete_out_of_range_occurrences_from_groups( $event_id ) {
	global $wpdb;

	$event_id = intval( $event_id );
	if( ! $event_id ) { return false; }

	// Get event
	$query1	= 'SELECT * ' 
			. ' FROM ' . BOOKACTI_TABLE_EVENTS
			. ' WHERE id = %d ';
	$prep1	= $wpdb->prepare( $query1, $event_id );
	$event	= $wpdb->get_row( $prep1 );
	
	if( ! $event ) { return false; }
	
	// Delete occurrences that are before repeat from date, or after repeat to date
	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' WHERE event_id = %d '
			. ' AND ('
				. ' UNIX_TIMESTAMP( CONVERT_TZ( event_start, "+00:00", @@global.time_zone ) ) < '
				. ' UNIX_TIMESTAMP( CONVERT_TZ( %s, "+00:00", @@global.time_zone ) ) '
				. ' OR '
				. ' UNIX_TIMESTAMP( CONVERT_TZ( ( event_end + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) > '
				. ' UNIX_TIMESTAMP( CONVERT_TZ( %s, "+00:00", @@global.time_zone ) ) '
			. ' ) ';

	$prep		= $wpdb->prepare( $query, $event_id, $event->repeat_from, $event->repeat_to );
	$deleted	= $wpdb->query( $prep );

	return $deleted;
}


/**
 * Update dates of an event bound to a group
 * 
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param 'move'|'resize' $action
 * @param int|null $delta_days
 * @return int|false|null|void
 */
function bookacti_update_grouped_event_dates( $event_id, $event_start, $event_end, $action, $delta_days = 0 ) {

	if( ! is_numeric( $delta_days ) ) {
		return;
	}

	global $wpdb;

	$start_time = substr( $event_start, 10, 8 );
	$end_time	= substr( $event_end, 10, 8 );

	// The event is resize, only the end datetime changes
	if( $action === 'resize' ) {

		// Update only time, keep date
		$query		= 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS 
					. ' SET event_end = CONCAT( DATE( DATE_ADD( event_end, INTERVAL %d DAY ) ), %s ) '
					. ' WHERE event_id = %d ';
		$prep		= $wpdb->prepare( $query, intval( $delta_days ), $end_time, $event_id );
		$updated	= $wpdb->query( $prep );
	}

	// The event is moved, event start and end may have changed
	else if( $action === 'move' ) {

		$query		= 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS 
					. ' SET event_start = CONCAT( DATE( DATE_ADD( event_start, INTERVAL %d DAY ) ), %s ),'
					. ' event_end = CONCAT( DATE( DATE_ADD( event_end, INTERVAL %d DAY ) ), %s ) '
					. ' WHERE event_id = %d ';
		$prep		= $wpdb->prepare( $query, intval( $delta_days ), $start_time, intval( $delta_days ), $end_time, $event_id );
		$updated	= $wpdb->query( $prep );
	}

	return $updated;   
}


/**
 * Update id of an event bound to a group
 * 
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param int $new_id
 * @return int|false|null|void
 */
function bookacti_update_grouped_event_id( $event_id, $event_start, $event_end, $new_id ) {

	if( ! is_numeric( $event_id ) || ! is_numeric( $new_id ) ) {
		return;
	}

	$event_id	= intval( $event_id );
	$new_id		= intval( $new_id );

	global $wpdb;

	$query	= 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' SET event_id = %d '
			. ' WHERE event_id = %d ';

	$variables = array( $new_id, $event_id );

	if( $event_start ) {
		$query .= ' AND event_start = %s ';
		$variables[] = $event_start;
	}

	if( $event_end ) {
		$query .= ' AND event_end = %s ';
		$variables[] = $event_end;
	}

	$prep		= $wpdb->prepare( $query, $variables );
	$updated	= $wpdb->query( $prep );

	return $updated;   
}




// GROUP CATEGORIES

/**
 * Insert a group category
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param string $category_title
 * @param int $template_id
 * @param array $category_meta
 * @return type
 */
function bookacti_insert_group_category( $category_title, $template_id, $category_meta = array() ) {
	global $wpdb;

	// Insert the new category
	$wpdb->insert( 
		BOOKACTI_TABLE_GROUP_CATEGORIES, 
		array( 
			'template_id'	=> $template_id,
			'title'			=> $category_title,
			'active'		=> 1
		),
		array( '%d', '%s', '%d' )
	);

	$category_id = $wpdb->insert_id;

	if( ! empty( $category_meta ) && ! empty( $category_id ) ) {
		bookacti_insert_metadata( 'group_category', $category_id, $category_meta );
	}

	return $category_id;
}


/**
 * Update a group category
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $category_id
 * @param string $category_title
 * @param array $category_meta
 * @return int|boolean
 */
function bookacti_update_group_category( $category_id, $category_title, $category_meta ) {
	global $wpdb;

	$updated = 0;

	$updated1 = $wpdb->update( 
		BOOKACTI_TABLE_GROUP_CATEGORIES, 
		array( 
			'title' => $category_title
		),
		array( 'id' => $category_id ),
		array( '%s' ),
		array( '%d' )
	);

	// Insert Meta
	$updated2 = 0;
	if( ! empty( $category_meta ) ) {
		$updated2 = bookacti_update_metadata( 'group_category', $category_id, $category_meta );
	}

	if( is_int( $updated1 ) && is_int( $updated2 ) ) {
		$updated = $updated1 + $updated2;
	}

	if( $updated1 === false || $updated2 === false ) {
		$updated = false;
	}

	return $updated;
}


/**
 * Delete a group category and its groups of events
 * 
 * @global wpdb $wpdb
 * @param type $category_id
 * @return boolean
 */
function bookacti_delete_group_category_and_its_groups_of_events( $category_id ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	global $wpdb;

	// Delete the category's groups events
	$query_events	= 'DELETE GE.* '
					. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
					. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
					. ' ON GE.group_id = G.id '
					. ' WHERE G.category_id = %d ';
	$prep_events	= $wpdb->prepare( $query_events, $category_id );
	$deleted1		= $wpdb->query( $prep_events );

	// Delete the category's groups
	$query_group	= 'DELETE FROM ' . BOOKACTI_TABLE_EVENT_GROUPS 
					. ' WHERE category_id = %d ';
	$prep_group		= $wpdb->prepare( $query_group, $category_id );
	$deleted2		= $wpdb->query( $prep_group );

	// Delete the category itself
	$query_category	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES 
					. ' WHERE id = %d ';
	$prep_category	= $wpdb->prepare( $query_category, $category_id );
	$deleted3		= $wpdb->query( $prep_category );

	if( $deleted1 === false && $deleted2 === false && $deleted3 === false ) {
		return false;
	}

	$deleted = intval( $deleted1 ) + intval( $deleted2 ) + intval( $deleted3 );

	return $deleted;
}


/**
 * Delete a group category 
 * 
 * @global wpdb $wpdb
 * @param type $category_id
 * @return boolean
 */
function bookacti_delete_group_category( $category_id ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	global $wpdb;

	// Delete the category
	$query_category	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES 
					. ' WHERE id = %d ';
	$prep_category	= $wpdb->prepare( $query_category, $category_id );
	$deleted		= $wpdb->query( $prep_category );

	return $deleted;
}


/**
 * Deactivate a group category 
 * 
 * @global wpdb $wpdb
 * @param type $category_id
 * @return boolean
 */
function bookacti_deactivate_group_category( $category_id ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	global $wpdb;

	// Deactivate the group category
	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_GROUP_CATEGORIES, 
		array( 
			'active' => 0
		),
		array( 'id' => $category_id ),
		array( '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Get group category data
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $category_id
 * @param OBJECT|ARRAY_A $return_type
 * @return array|object
 */
function bookacti_get_group_category( $category_id, $return_type = OBJECT ) {

	$return_type = $return_type === OBJECT ? OBJECT : ARRAY_A;

	global $wpdb;

	$query		= 'SELECT * FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' WHERE id = %d ';
	$prep		= $wpdb->prepare( $query, $category_id );
	$category	= $wpdb->get_row( $prep, $return_type );

	// Get template settings and managers
	if( $return_type === ARRAY_A ) {
		// Translate title
		$category[ 'multilingual_title' ]	= $category[ 'title' ];
		$category[ 'title' ]				= apply_filters( 'bookacti_translate_text', $category[ 'title' ] );

		$category[ 'settings' ]				= bookacti_get_metadata( 'group_category', $category_id );

	} else {
		// Translate title
		$category->multilingual_title	= $category->title;
		$category->title				= apply_filters( 'bookacti_translate_text', $category->title );

		$category->settings				= bookacti_get_metadata( 'group_category', $category_id );
	}

	return $category;
}


/**
 * Get the template id of a group category
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param int $category_id
 * @return int|boolean
 */
function bookacti_get_group_category_template_id( $category_id ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	global $wpdb;

	$query			= 'SELECT template_id FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' WHERE id = %d ';
	$query_prep		= $wpdb->prepare( $query, $category_id );
	$template_id	= $wpdb->get_var( $query_prep );

	return $template_id;
}




// TEMPLATES

/**
 * Get templates
 * @version 1.8.6
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $ignore_permissions
 * @param int $user_id
 * @return array
 */
function bookacti_fetch_templates( $template_ids = array(), $ignore_permissions = false, $user_id = 0 ) {
	if( is_numeric( $template_ids ) ) { $template_ids = array( $template_ids ); }

	// Check if we need to check permissions
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( ! $ignore_permissions ) {
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( $bypass_template_managers_check || is_super_admin( $user_id ) ) {
			$ignore_permissions = true;
		}
	}

	global $wpdb;
	$variables = array();

	if( $ignore_permissions ) {
		$query = 'SELECT T.id, T.title, T.start_date as start, T.end_date as end, T.active '
			. ' FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T '
			. ' WHERE T.active = 1 ';
	} else {
		$query = 'SELECT T.id, T.title, T.start_date as start, T.end_date as end, T.active '
			. ' FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_PERMISSIONS . ' as P '
			. ' WHERE T.active = 1 '
			. ' AND T.id = P.object_id '
			. ' AND P.object_type = "template" '
			. ' AND P.user_id = %d ';
		$variables[] = $user_id;
	}

	if( $template_ids ) {
		$query  .= ' AND T.id IN ( %d';
		for( $i=1,$len=count($template_ids); $i<$len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $template_ids );
	}

	$order_by = apply_filters( 'bookacti_templates_list_order_by', array( 'T.title', 'T.id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	$templates = $wpdb->get_results( $query, ARRAY_A );

	$templates_by_id = array();
	foreach( $templates as $template ) {
		$templates_by_id[ $template[ 'id' ] ] = $template;
	}

	return $templates_by_id;
}


/**
 * Get template data, metadata and managers
 * @version 1.9.2
 * @global wpdb $wpdb
 * @param int $template_id
 * @param OBJECT|ARRAY_A $return_type
 * @return object|array
 */
function bookacti_get_template( $template_id, $return_type = OBJECT ) {

	$return_type = $return_type === OBJECT ? OBJECT : ARRAY_A;

	global $wpdb;

	$query		= 'SELECT * FROM ' . BOOKACTI_TABLE_TEMPLATES . ' WHERE id = %d ';
	$prep		= $wpdb->prepare( $query, $template_id );
	$template	= $wpdb->get_row( $prep, $return_type );

	// Get template settings and managers
	if( $return_type === ARRAY_A ) {
		$template[ 'admin' ]	= bookacti_get_template_managers( $template_id );
		$template[ 'settings' ] = bookacti_get_metadata( 'template', $template_id );
	} else {
		$template->admin		= bookacti_get_template_managers( $template_id );
		$template->settings		= bookacti_get_metadata( 'template', $template_id );
	}

	return $template;
}


/**
 * Create a new template
 * @global wpdb $wpdb
 * @param string $template_title
 * @param string $template_start
 * @param string $template_end
 * @param array $template_managers
 * @param array $template_meta
 * @param int $duplicated_template_id
 * @return int
 */
function bookacti_insert_template( $template_title, $template_start, $template_end, $template_managers, $template_meta, $duplicated_template_id = 0 ) { 
   global $wpdb;

	//Add the new template and set it by default
	$wpdb->insert( 
		BOOKACTI_TABLE_TEMPLATES, 
		array( 
			'title'			=> $template_title,
			'start_date'	=> $template_start,
			'end_date'		=> $template_end
		),
		array( '%s', '%s', '%s' )
	);

	$new_template_id = $wpdb->insert_id;

	// Insert Managers
	bookacti_insert_managers( 'template', $new_template_id, $template_managers );

	// Insert Meta
	bookacti_insert_metadata( 'template', $new_template_id, $template_meta );

	// Duplicate events and activities connection if the template is duplicated
	if( $duplicated_template_id > 0 ) {
		bookacti_duplicate_template( $duplicated_template_id, $new_template_id );
	}

	return $new_template_id;
}


/**
 * Duplicate a template
 * @global wpdb $wpdb
 * @param int $duplicated_template_id
 * @param int $new_template_id
 */
function bookacti_duplicate_template( $duplicated_template_id, $new_template_id ) {

	global $wpdb;

	if( $duplicated_template_id && $new_template_id ) {

		// Duplicate events without exceptions and their metadata
		$query_event_wo_excep	= ' SELECT id FROM ' . BOOKACTI_TABLE_EVENTS
								. ' WHERE id NOT IN ( SELECT event_id FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' ) ' 
								. ' AND template_id = %d ';
		$prep_event_wo_excep	= $wpdb->prepare( $query_event_wo_excep, $duplicated_template_id );
		$events_wo_exceptions	= $wpdb->get_results( $prep_event_wo_excep, OBJECT );

		foreach( $events_wo_exceptions as $event ) {

			$old_event_id = $event->id;

			// Duplicate the event and get its id 
			$query_duplicate_event_wo_excep = ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to ) '
											. ' SELECT %d, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
			$prep_duplicate_event_wo_excep	= $wpdb->prepare( $query_duplicate_event_wo_excep, $new_template_id, $event->id );
			$wpdb->query( $prep_duplicate_event_wo_excep );

			$new_event_id	= $wpdb->insert_id;

			bookacti_duplicate_metadata( 'event', $old_event_id, $new_event_id);
		}

		// Duplicate events with exceptions, their exceptions and their metadata
		$query_event_w_excep= ' SELECT id FROM ' . BOOKACTI_TABLE_EVENTS
							. ' WHERE id IN ( SELECT event_id FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' ) ' 
							. ' AND template_id = %d ';
		$prep_event_w_excep	= $wpdb->prepare( $query_event_w_excep, $duplicated_template_id );
		$events_with_exceptions	= $wpdb->get_results( $prep_event_w_excep, OBJECT );

		foreach( $events_with_exceptions as $event ) {

			$old_event_id = $event->id;

			// Duplicate the event and get its id 
			$query_duplicate_event_w_excep	= ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to ) '
											. ' SELECT %d, activity_id, title, start, end, availability, repeat_freq, repeat_from, repeat_to FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
			$prep_duplicate_event_w_excep	= $wpdb->prepare( $query_duplicate_event_w_excep, $new_template_id, $event->id );
			$wpdb->query( $prep_duplicate_event_w_excep );

			$new_event_id	= $wpdb->insert_id;

			bookacti_duplicate_exceptions( $old_event_id, $new_event_id );
			bookacti_duplicate_metadata( 'event', $old_event_id, $new_event_id);
		}


		// Duplicate activities connection
		$query_template_x_activity	= ' INSERT INTO ' . BOOKACTI_TABLE_TEMP_ACTI . ' ( template_id, activity_id ) '
									. ' SELECT %d, activity_id FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' WHERE template_id = %d ';
		$prep_template_x_activity	= $wpdb->prepare( $query_template_x_activity, $new_template_id, $duplicated_template_id );
		$wpdb->query( $prep_template_x_activity );
	}
}


/**
 * Deactivate a template
 * @global wpdb $wpdb
 * @param int $template_id
 * @return int|false
 */
function bookacti_deactivate_template( $template_id ) {
	global $wpdb;

	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_TEMPLATES, 
		array( 
			'active' => 0
		),
		array( 'id' => $template_id ),
		array( '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Update template
 * 
 * @global wpdb $wpdb
 * @param int $template_id
 * @param string $template_title
 * @param string $template_start
 * @param string $template_end
 * @return int|false
 */
function bookacti_update_template( $template_id, $template_title, $template_start, $template_end ) { 
	global $wpdb;

	$updated = $wpdb->update( 
		BOOKACTI_TABLE_TEMPLATES, 
		array( 
			'title'         => $template_title,
			'start_date'    => $template_start,
			'end_date'      => $template_end,
		),
		array( 'id' => $template_id ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);

	return $updated;
}




// ACTIVITIES

/**
 * Get activity data
 * @version 1.9.2
 * @global wpdb $wpdb
 * @param int $activity_id
 * @return object
 */
function bookacti_get_activity( $activity_id ) {
	global $wpdb;

	$query		= 'SELECT * FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' WHERE id = %d';
	$prep		= $wpdb->prepare( $query, $activity_id );
	$activity	= $wpdb->get_row( $prep, OBJECT );

	// Get activity settings and managers
	$activity->admin	= bookacti_get_activity_managers( $activity_id );
	$activity->settings	= bookacti_get_metadata( 'activity', $activity_id );

	$activity->multilingual_title = $activity->title;
	$activity->title	= apply_filters( 'bookacti_translate_text', $activity->title );

	$unit_name_singular	= isset( $activity->settings[ 'unit_name_singular' ] )	? $activity->settings[ 'unit_name_singular' ]	: '';
	$unit_name_plural	= isset( $activity->settings[ 'unit_name_plural' ] )	? $activity->settings[ 'unit_name_plural' ]		: '';
	
	$activity->settings[ 'multilingual_unit_name_singular' ] = $unit_name_singular;
	$activity->settings[ 'multilingual_unit_name_plural' ]	= $unit_name_plural;
	$activity->settings[ 'unit_name_singular' ] = apply_filters( 'bookacti_translate_text', $unit_name_singular );
	$activity->settings[ 'unit_name_plural' ]	= apply_filters( 'bookacti_translate_text', $unit_name_plural );

	return $activity;
}


/**
 * Insert an activity
 * 
 * @version 1.2.2
 * @global wpdb $wpdb
 * @param string $activity_title
 * @param string $activity_color
 * @param int $activity_availability
 * @param string $activity_duration
 * @param int $activity_resizable
 * @return int|false
 */
function bookacti_insert_activity( $activity_title, $activity_color, $activity_availability, $activity_duration, $activity_resizable ) {
	global $wpdb;

	$wpdb->insert( 
		BOOKACTI_TABLE_ACTIVITIES, 
		array( 
			'title'         => $activity_title,
			'color'         => $activity_color,
			'availability'	=> $activity_availability,
			'duration'      => $activity_duration,
			'is_resizable'  => $activity_resizable,
			'active'        => 1
		),
		array( '%s', '%s', '%d', '%s', '%d', '%d' )
	);

	$activity_id = $wpdb->insert_id;

	return $activity_id;
}


/**
 * Update an activity
 * 
 * @version 1.2.2
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param string $activity_title
 * @param string $activity_color
 * @param int $activity_availability
 * @param string $activity_duration
 * @param int $activity_resizable
 * @return int|false
 */
function bookacti_update_activity( $activity_id, $activity_title, $activity_color, $activity_availability, $activity_duration, $activity_resizable ) {
	global $wpdb;

	$updated = $wpdb->update( 
		BOOKACTI_TABLE_ACTIVITIES, 
		array( 
			'title'         => $activity_title,
			'color'         => $activity_color,
			'availability'  => $activity_availability,
			'duration'      => $activity_duration,
			'is_resizable'  => $activity_resizable
		),
		array( 'id' => $activity_id ),
		array( '%s', '%s', '%d', '%s', '%d' ),
		array( '%d' )
	);

	return $updated;
}


/**
 * Update events title to match the activity title
 * @version 1.8.0
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param string $new_title
 * @return int|false
 */
function bookacti_update_events_title( $activity_id, $new_title ) {
	global $wpdb;

	$query	= ' UPDATE ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id '
			. ' SET E.title = %s '
			. ' WHERE E.activity_id = %d '
			. ' AND A.title = E.title ';
	
	$query	= $wpdb->prepare( $query, $new_title, $activity_id );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Deactivate an activity
 * @global wpdb $wpdb
 * @param int $activity_id
 * @return int|false
 */
function bookacti_deactivate_activity( $activity_id ) {
	global $wpdb;

	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_ACTIVITIES, 
		array( 
			'active' => 0
		),
		array( 'id' => $activity_id ),
		array( '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Deactivate all events of a specific activity from a specific template
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param int $template_id
 * @return int|false
 */
function bookacti_deactivate_activity_events( $activity_id, $template_id ) {
	global $wpdb;

	//Remove the event
	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_EVENTS, 
		array( 
			'active' => 0
		),
		array( 
			'activity_id' => $activity_id,
			'template_id' => $template_id
		),
		array( '%d' ),
		array( '%d', '%d' )
	);

	return $deactivated;
}




// TEMPLATES X ACTIVITIES ASSOCIATION

/**
 * Insert a template x activity association
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $activity_ids
 * @return int|false
 */
function bookacti_insert_templates_x_activities( $template_ids, $activity_ids ) {

	if( ! is_array( $template_ids ) || empty( $template_ids )
	||  ! is_array( $activity_ids ) || empty( $activity_ids ) ) {
		return false;
	}

	global $wpdb;

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_TEMP_ACTI . ' ( template_id, activity_id ) ' . ' VALUES ';

	$i = 0;
	$variables_array = array();
	foreach( $activity_ids as $activity_id ) {
		foreach( $template_ids as $template_id ) {
			if( $i > 0 ) { $query .= ','; } 
			$query .= ' ( %d, %d ) ';
			$variables_array[] = $template_id;
			$variables_array[] = $activity_id;
			$i++;
		}
	}

	$prep		= $wpdb->prepare( $query, $variables_array );
	$inserted	= $wpdb->query( $prep );

	return $inserted;
}


/**
 * Delete a template x activity association
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $activity_ids
 * @return int|false
 */
function bookacti_delete_templates_x_activities( $template_ids, $activity_ids ) {

	if( ! is_array( $template_ids ) || empty( $template_ids )
	||  ! is_array( $activity_ids ) || empty( $activity_ids ) ) {
		return false;
	}

	global $wpdb;

	// Prepare query
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' WHERE template_id IN (';
	for( $i = 0; $i < count( $template_ids ); $i++ ) {
		$query .= ' %d';
		if( $i < ( count( $template_ids ) - 1 ) ) {
			$query .= ',';
		}
	}
	$query .= ' ) AND activity_id IN (';
	for( $i = 0; $i < count( $activity_ids ); $i++ ) {
		$query .= ' %d';
		if( $i < ( count( $activity_ids ) - 1 ) ) {
			$query .= ',';
		}
	}
	$query .= ' ) ';

	$variables_array = array_merge( $template_ids, $activity_ids );

	$prep		= $wpdb->prepare( $query, $variables_array );
	$deleted	= $wpdb->query( $prep );

	return $deleted;
}


/**
 * Get activities by template
 * @version 1.9.2
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $based_on_events Whether to retrieve activities bound to templates or activities bound to events of templates
 * @param boolean $get_managers Whether to retrieve the managers
 * @return array
 */
function bookacti_get_activities_by_template( $template_ids = array(), $based_on_events = false, $retrieve_managers = false ) {
	global $wpdb;

	// If empty, take them all
	if( ! $template_ids ) {
		$template_ids = array_keys( bookacti_fetch_templates( array(), true ) );
	}

	// Convert numeric to array
	if( ! is_array( $template_ids ) ){
		$template_id = intval( $template_ids );
		$template_ids = array();
		if( $template_id ) {
			$template_ids[] = $template_id;
		}
	}

	if( $based_on_events ) {
		$query	= 'SELECT DISTINCT A.* FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
				. ' WHERE A.id = E.activity_id AND E.template_id IN (';
	} else {
		$query	= 'SELECT DISTINCT A.* FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' as TA, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
				. ' WHERE A.id = TA.activity_id AND TA.template_id IN (';
	}

	$i = 1;
	foreach( $template_ids as $template_id ){
		$query .= ' %d';
		if( $i < count( $template_ids ) ) { $query .= ','; }
		$i++;
	}

	$query .= ' )';

	$order_by = apply_filters( 'bookacti_activities_list_order_by', array( 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'A.id'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $template_ids ) {
		$query = $wpdb->prepare( $query, $template_ids );
	}

	$activities = $wpdb->get_results( $query, ARRAY_A );

	$activity_ids = array();
	foreach( $activities as $activity ) {
		$activity_ids[] = $activity[ 'id' ];
	}

	$activities_meta		= bookacti_get_metadata( 'activity', $activity_ids );
	$activities_managers	= $retrieve_managers ? bookacti_get_managers( 'activity', $activity_ids ) : array();

	$activities_array = array();
	foreach( $activities as $activity ) {
		$activity[ 'settings' ] = isset( $activities_meta[ $activity[ 'id' ] ] ) ? $activities_meta[ $activity[ 'id' ] ] : array();
		$activity[ 'multilingual_title' ] = $activity[ 'title' ];
		$activity[ 'title' ]	= apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );

		$unit_name_singular	= isset( $activity[ 'settings' ][ 'unit_name_singular' ] )	? $activity[ 'settings' ][ 'unit_name_singular' ] : '';
		$unit_name_plural	= isset( $activity[ 'settings' ][ 'unit_name_plural' ] )	? $activity[ 'settings' ][ 'unit_name_plural' ] : '';

		$activity[ 'settings' ][ 'multilingual_unit_name_singular' ] = $unit_name_singular;
		$activity[ 'settings' ][ 'multilingual_unit_name_plural' ]	= $unit_name_plural;
		$activity[ 'settings' ][ 'unit_name_singular' ] = apply_filters( 'bookacti_translate_text', $unit_name_singular );
		$activity[ 'settings' ][ 'unit_name_plural' ]	= apply_filters( 'bookacti_translate_text', $unit_name_plural );

		if( $retrieve_managers ) { 
			$activity[ 'admin' ] = isset( $activities_managers[ $activity[ 'id' ] ] ) ? $activities_managers[ $activity[ 'id' ] ] : array();
		}

		$activities_array[ $activity[ 'id' ] ] = $activity;
	}

	return $activities_array;
}


/**
 * Get an array of all activity ids bound to designated templates
 * @since 1.1.0
 * @version 1.7.16
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $based_on_events Whether to retrieve activity ids bound to templates or activity ids bound to events of templates
 * @param boolean $allowed_roles_only Whether to retrieve only allowed activity based on current user role
 * @return array
 */
function bookacti_get_activity_ids_by_template( $template_ids = array(), $based_on_events = false, $allowed_roles_only = false ) {
	global $wpdb;

	// Convert numeric to array
	if( ! is_array( $template_ids ) ){
		$template_id = intval( $template_ids );
		$template_ids = array();
		if( $template_id ) {
			$template_ids[] = $template_id;
		}
	}

	$variables = array();

	if( $based_on_events ) { 
		$query	= 'SELECT DISTINCT E.activity_id as unique_activity_id FROM ' . BOOKACTI_TABLE_EVENTS . ' as E ';
	} else {
		$query	= 'SELECT DISTINCT A.id as unique_activity_id FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' as TA, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ';
	}

	// Join the meta table to filter by roles
	if( $allowed_roles_only && ! is_super_admin() ) {
		$query .= ' LEFT JOIN ( 
						SELECT meta_value as roles, object_id as activity_id 
						FROM ' . BOOKACTI_TABLE_META . ' 
						WHERE object_type = "activity" 
						AND meta_key = "allowed_roles" 
					) as M ';
		$query .= $based_on_events ? ' ON M.activity_id = E.activity_id ' : ' ON M.activity_id = A.id ';
		$query .= ' LEFT JOIN ( 
						SELECT user_id as manager_id, object_id as activity_id
						FROM ' . BOOKACTI_TABLE_PERMISSIONS . ' 
						WHERE object_type = "activity"
					) as P ';
		$query .= $based_on_events ? ' ON P.activity_id = E.activity_id ' : ' ON P.activity_id = A.id ';
	}

	$query .= $based_on_events ? ' WHERE TRUE ' : ' WHERE A.id = TA.activity_id ';

	// Filter by roles
	if( $allowed_roles_only && ! is_super_admin() ) {
		$current_user = wp_get_current_user();
		$roles = $current_user && ! empty( $current_user->roles ) ? $current_user->roles : array();

		// If the "all" role is selected, allow everybody
		$roles[] = 'all';

		$query .= ' AND ( ( M.roles = "a:0:{}" OR M.roles IS NULL OR M.roles = "" ) ';
		if( $roles ) {
			foreach( $roles as $i => $role ) {
				$query .= ' OR M.roles LIKE %s ';
				// Prefix and suffix each element of the array
				$roles[ $i ] = '%' . $wpdb->esc_like( $role ) . '%';
			}
			$variables = array_merge( $variables, $roles );
		}
		if( $current_user && isset( $current_user->ID ) ) {
			$query .= ' OR P.manager_id = %d ';
			$variables[] = $current_user->ID;
		}
		$query .= ' ) ';
	}

	// Filter by templates
	if( $template_ids ) {
		$query .= $based_on_events ? ' AND E.template_id IN ( %d ' : ' AND TA.template_id IN ( %d ';
		$array_count = count( $template_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $template_ids );
	}

	$order_by = apply_filters( 'bookacti_activities_list_order_by', array( 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'unique_activity_id'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	$activities = $wpdb->get_results( $query, OBJECT );

	$activities_ids = array();
	foreach( $activities as $activity ) {
		$activities_ids[] = intval( $activity->unique_activity_id );
	}

	return $activities_ids;
}


/**
 * Get templates by activity
 * 
 * @version 1.7.0
 * @global wpdb $wpdb
 * @param array $activity_ids
 * @param boolean $id_only
 * @return array
 */
function bookacti_get_templates_by_activity( $activity_ids, $id_only = true ) {
	global $wpdb;

	if( ! is_array( $activity_ids ) ){
		$activity_ids = array( $activity_ids );
	}

	$query	= 'SELECT DISTINCT T.* FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' as TA, ' . BOOKACTI_TABLE_TEMPLATES . ' as T '
			. ' WHERE T.id = TA.template_id '
			. 'AND TA.activity_id IN (';

	$i = 1;
	foreach( $activity_ids as $activity_id ){
		$query .= ' %d';
		if( $i < count( $activity_ids ) ) { $query .= ','; }
		$i++;
	}

	$query .= ' )';

	$order_by = apply_filters( 'bookacti_templates_list_order_by', array( 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'T.id'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	$prep		= $wpdb->prepare( $query, $activity_id );
	$templates	= $wpdb->get_results( $prep, OBJECT );

	$templates_array = array();
	foreach( $templates as $template ) {
		if( $id_only ){
			$templates_array[] = $template->id;
		} else {
			$templates_array[] = $template;
		}
	}

	return $templates_array;
}


/**
 * Fetch activities with the list of associated templated
 * 
 * @version 1.7.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @return array [ activity_id ][id, title, color, duration, availability, is_resizable, active, template_ids] where template_ids = [id, id, id, ...]
 */
function bookacti_fetch_activities_with_templates_association( $template_ids = array() ) {
	global $wpdb;

	// Convert numeric to array
	if( ! is_array( $template_ids ) ){
		$template_id = intval( $template_ids );
		$template_ids = array();
		if( $template_id ) {
			$template_ids[] = $template_id;
		}
	}

	$query  = 'SELECT A.*, TA.template_id FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMP_ACTI . ' as TA ' 
			. ' WHERE active=1 '
			. ' AND A.id = TA.activity_id';

	// Filter by template
	if( $template_ids ) {
		$query .= ' AND TA.template_id IN ( %d';
		for( $i=1, $len=count( $template_ids ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ') ';
	}

	$order_by = apply_filters( 'bookacti_activities_list_order_by', array( 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'A.id'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $template_ids ) {
		$query = $wpdb->prepare( $query, $template_ids );
	}

	$activities = $wpdb->get_results( $query, ARRAY_A );

	$activities_array = array();
	foreach( $activities as $activity ) {
		if( ! isset( $activities_array[ $activity[ 'id' ] ] ) ) {
			$activities_array[ $activity[ 'id' ] ] = $activity;
		}
		$activities_array[ $activity[ 'id' ] ][ 'template_ids' ][] = $activity[ 'template_id' ];
		unset( $activities_array[ $activity[ 'id' ] ][ 'template_id' ] );
	}

	return $activities_array;
}