<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// EVENTS

/**
 * Fetch events by templates and / or activities
 * @version 1.12.0
 * @param array $raw_args {
 *  @param array $templates Array of template IDs
 *  @param array $activities Array of activity IDs
 *  @param array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @param boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @param boolean $past_events Whether to compute past events
 *  @param boolean $bounding_only Whether to retrieve the first and the last events only
 * }
 * @return array $events_array Array of events
 */
function bookacti_fetch_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'activities' => array(),
		'interval' => array(),
		'skip_exceptions' => 1,
		'past_events' => 0,
		'bounding_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );

	global $wpdb;

	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp				= $current_datetime_object->format( 'U' );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	$variables					= array();

	// Prepare the query
	$query  = 'SELECT DISTINCT E.id as event_id, E.template_id, E.activity_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.availability, A.color '
			. ' FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE E.activity_id = A.id '
			. ' AND E.template_id = T.id '
			. ' AND E.active = 1 '
			. ' AND T.active = 1 ';

	// Do not fetch events totally out of the desired interval
	if( $args[ 'interval' ] ) {
		$query  .= ' 
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
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( E.repeat_from, " 00:00:00" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( E.repeat_to, " 23:59:59" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							)
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( E.repeat_from, " 00:00:00" ), %s, @@global.time_zone ) ) > 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( E.repeat_to, " 23:59:59" ), %s, @@global.time_zone ) ) > 
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

	// Whether to fetch past events
	if( ! $args[ 'past_events' ] ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) >= %d 
						OR	UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) >= %d ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;

		$started_events_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
		if( $started_events_bookable ) {
			// Fetch events already started but not finished
			$query .= ' OR	(	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) <= %d 
							AND UNIX_TIMESTAMP( CONVERT_TZ( E.end, %s, @@global.time_zone ) ) >= %d 
							)';
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
		}
		$query .= ') ';
	}

	// Get events from desired templates only
	if( $args[ 'templates' ] ) {
		$query  .= ' AND E.template_id IN ( %d';
		for( $i=1,$len=count( $args[ 'templates' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}

	// Get events from desired activities only
	if( $args[ 'activities' ] ) {
		$query  .= ' AND A.id IN ( %d';
		for( $i=1,$len=count( $args[ 'activities' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'activities' ] );
	}

	$query  .= ' ORDER BY E.start ASC ';

	// Allow plugins to change the query
	$query = apply_filters( 'bookacti_get_events_query', $wpdb->prepare( $query, $variables ), $args );

	// Get events complying with parameters
	$events = $wpdb->get_results( $query, OBJECT );
	
	// Transform raw events from database to array of individual events
	$events_array = bookacti_get_events_array_from_db_events( $events, $args );

	return apply_filters( 'bookacti_get_events', $events_array, $query, $args );
}


/**
 * Fetch events by groups and / or group categories
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @param array $templates Array of template IDs
 *  @param array $activities Array of activity IDs
 *  @param array groups Array of groups of events IDs
 *  @param array group_categories Array of group categories IDs
 *  @param array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @param boolean $past_events Whether to compute past events
 *  @param boolean $bounding_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_fetch_grouped_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'activities' => array(),
		'groups' => array(),
		'group_categories' => array(),
		'interval' => array(),
		'past_events' => 0,
		'bounding_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );

	global $wpdb;

	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp				= $current_datetime_object->format( 'U' );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	$variables					= array();

	// Prepare the query
	$query  = 'SELECT DISTINCT GE.event_id, E.template_id, E.activity_id, E.title, GE.event_start as start, GE.event_end as end, "none" as repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.availability, A.color '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G, ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE GE.event_id = E.id '
			. ' AND E.activity_id = A.id '
			. ' AND E.template_id = T.id '
			. ' AND GE.group_id = G.id '
			. ' AND G.category_id = C.id '
			. ' AND E.active = 1 '
			. ' AND T.active = 1 '
			. ' AND G.active = 1 '
			. ' AND C.active = 1 ';

	// Get events from desired templates only
	if( $args[ 'templates' ] ) {
		$query  .= ' AND E.template_id IN ( %d';
		for( $i=1,$len=count( $args[ 'templates' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}

	// Get events from desired activities only
	if( $args[ 'activities' ] ) {
		$query  .= ' AND A.id IN ( %d';
		for( $i=1,$len=count( $args[ 'activities' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'activities' ] );
	}

	// Fetch events from desired groups only
	if( $args[ 'groups' ] ) {
		// Get the event only if it belongs to a group of the allowed categories
		$query .= ' AND GE.group_id IN ( %d';
		for( $i=1, $len=count( $args[ 'groups' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'groups' ] );
	}

	// Fetch events from desired categories only
	if( $args[ 'group_categories' ] ) {
		// Get the event only if it belongs to a group of the allowed categories
		$query .= ' AND G.category_id IN ( %d';
		for( $i=1, $len=count( $args[ 'group_categories' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'group_categories' ] );
	}

	// Do not fetch events out of the desired interval
	if( $args[ 'interval' ] ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
							UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'start' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'end' ];
		$variables[] = $user_timestamp_offset;
	}

	// Whether to fetch past events
	if( ! $args[ 'past_events' ] ) {
		$started_events_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );

		$query .= ' AND ( UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) >= %d ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;

		if( $started_events_bookable ) {
			// Fetch events already started but not finished
			$query .= ' OR	(	UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) <= %d 
							AND UNIX_TIMESTAMP( CONVERT_TZ( GE.event_end, %s, @@global.time_zone ) ) >= %d 
							) ';
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
		}
		$query .= ' ) ';
	}

	$query .= ' ORDER BY GE.event_start ASC ';

	// Apply variables to the query
	$query = apply_filters( 'bookacti_get_grouped_events_query', $wpdb->prepare( $query, $variables ), $args );

	// Get events complying with parameters
	$events = $wpdb->get_results( $query, OBJECT );

	// Transform raw events from database to array of individual events
	$events_array = bookacti_get_events_array_from_db_events( $events, $args );

	return apply_filters( 'bookacti_get_grouped_events', $events_array, $query, $args );
}


/**
 * Fetch booked events only
 * @since 1.2.2
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @param array $templates Array of template IDs
 *  @param array $activities Array of activity IDs
 *  @param array events Array of event IDs
 *  @param array $status Array of groups of events IDs
 *  @param int|false $active 0 or 1. False to ignore.
 *  @param array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @param int|string $users Array of user IDs
 *  @param boolean $past_events Whether to compute past events
 *  @param boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @param boolean $bounding_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_fetch_booked_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'activities' => array(),
		'events' => array(),
		'status' => array(),
		'active' => false,
		'users' => array(),
		'interval' => array(),
		'past_events' => 0,
		'skip_exceptions' => 0,
		'bounding_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );

	global $wpdb;

	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp				= $current_datetime_object->format( 'U' );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );

	$variables					= array();

	// Prepare the query
	$query  = 'SELECT DISTINCT B.event_id, E.template_id, E.activity_id, E.title, B.event_start as start, B.event_end as end, "none" as repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.availability, A.color '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' WHERE B.event_id = E.id '
			. ' AND E.activity_id = A.id '
			. ' AND E.template_id = T.id ';

	// Get events from desired templates only
	if( $args[ 'templates' ] ) {
		$query  .= ' AND E.template_id IN ( %d';
		for( $i=1,$len=count( $args[ 'templates' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}

	// Get events from desired activities only
	if( $args[ 'activities' ] ) {
		$query  .= ' AND A.id IN ( %d';
		for( $i=1,$len=count( $args[ 'activities' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'activities' ] );
	}

	// Get events (occurrences) from desired events only
	if( $args[ 'events' ] ) {
		$query  .= ' AND B.event_id IN ( %d';
		for( $i=1,$len=count( $args[ 'events' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'events' ] );
	}

	// Fetch events from desired booking status only
	if( $args[ 'status' ] ) {
		$query .= ' AND B.state IN ( %s';
		for( $i=1, $len=count( $args[ 'status' ] ); $i < $len; ++$i ) {
			$query .= ', %s';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'status' ] );
	}
	
	// Filter bookings by active
	if( $args[ 'active' ] !== false ) {
		$query .= ' AND B.active = %d ';
		$variables[] = $args[ 'active' ];
	}

	// Filter bookings by user
	if( $args[ 'users' ] ) {
		$query .= ' AND B.user_id IN ( %s';
		for( $i=1, $len=count( $args[ 'users' ] ); $i < $len; ++$i ) {
			$query .= ', %s';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'users' ] );
	}

	// Do not fetch events out of the desired interval
	if( $args[ 'interval' ] ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
							UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						)';

		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'start' ];
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $args[ 'interval' ][ 'end' ];
		$variables[] = $user_timestamp_offset;
	}

	// Whether to fetch past events
	if( ! $args[ 'past_events' ] ) {
		$query .= ' AND ( UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= %d ) ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;
	}

	$query  .= ' ORDER BY B.event_start ASC ';

	// Safely apply variables to the query
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	$query = apply_filters( 'bookacti_get_booked_events_query', $query, $args );

	// Get events complying with parameters
	$events = $wpdb->get_results( $query, OBJECT );

	// Transform raw events from database to array of individual events
	$events_array = bookacti_get_events_array_from_db_events( $events, $args );

	return apply_filters( 'bookacti_get_booked_events', $events_array, $query, $args );
}


/**
 * Get event by id
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return object
 */
function bookacti_get_event_by_id( $event_id ) {
	global $wpdb;

	$query	= 'SELECT E.id as event_id, E.template_id, E.activity_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.availability, E.active as event_active, A.color, A.active as activity_active, T.active as template_active ' 
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id '
			. ' WHERE E.id = %d';
	$query = $wpdb->prepare( $query, $event_id );
	$event = $wpdb->get_row( $query, OBJECT );

	return $event;
}


/**
 * Get the number of remaining places of an event (total places - booked places)
 * 
 * @version 1.4.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start Format "YYYY-MM-DD HH:mm:ss"
 * @param string $event_end Format "YYYY-MM-DD HH:mm:ss"
 * @return int|false
 */
function bookacti_get_event_availability( $event_id, $event_start, $event_end ) {
	global $wpdb;

	$query  = 'SELECT ( E.availability - IFNULL( B.quantity_booked, 0 ) ) as availability '
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ( '
				. ' SELECT event_id, SUM( quantity ) as quantity_booked FROM ' . BOOKACTI_TABLE_BOOKINGS 
				. ' WHERE active = 1 '
				. ' AND event_id = %d '
				. ' AND event_start = %s '
				. ' AND event_end = %s '
			. ' ) as B ON B.event_id = E.id '
			. ' WHERE E.id = %d'
			. ' LIMIT 1 ';
	$query	= $wpdb->prepare( $query, $event_id, $event_start, $event_end, $event_id );
	$availability = $wpdb->get_var( $query );

	return $availability;
}




// EXCEPTIONS

/**
 * Get event repetition exceptions by templates or by events
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @param array $templates
 *  @param array $events
 *  @param array $event_groups
 *  @param array $types "event" or "group_of_events"
 * }
 * @return array of dates
 */
function bookacti_get_exceptions( $raw_args = array() ) {
	global $wpdb;

	$default_args = array(
		'templates' => array(),
		'events' => array(),
		'event_groups' => array(),
		'types' => array()
	);
	$args = wp_parse_args( $raw_args, $default_args );

	$variables = array();

	// No parameters are given, retrieve all exceptions
	if( ! $args[ 'templates' ] && ! $args[ 'events' ] && ! $args[ 'event_groups' ] ) {
		$query = 'SELECT X.object_type, X.object_id, X.exception_value, IF( X.object_type = "event", X.object_id, CONCAT( "G", X.object_id ) ) as unique_id FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' as X WHERE TRUE ';

	// If (group of) events ids are given, retrieve exceptions for these (group of) events, regardless of template ids
	} else if( $args[ 'events' ] || $args[ 'event_groups' ] ) {
		$query = 'SELECT X.object_type, X.object_id, X.exception_value, IF( X.object_type = "event", X.object_id, CONCAT( "G", X.object_id ) ) as unique_id FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' as X WHERE ';
		
		if( $args[ 'events' ] ) {
			$query .= ' ( X.object_type = "event" AND X.object_id IN ( ';
			$i = 1;
			foreach( $args[ 'events' ] as $event_id ) {
				$query .= ' %d';
				if( $i < count( $args[ 'events' ] ) ) { $query .= ','; }
				++$i;
			}
			$query .= ' ) )';
			$variables = array_merge( $variables, $args[ 'events' ] );
		}
		
		if( $args[ 'event_groups' ] ) {
			if( $args[ 'events' ] ) { $query .= ' OR '; }
			$query .= ' ( X.object_type = "group_of_events" AND X.object_id IN ( ';
			$i = 1;
			foreach( $args[ 'event_groups' ] as $group_id ) {
				$query .= ' %d';
				if( $i < count( $args[ 'event_groups' ] ) ) { $query .= ','; }
				++$i;
			}
			$query .= ' ) )';
			$variables = array_merge( $variables, $args[ 'event_groups' ] );
		}

	// If template ids are given, retrieve event exceptions from these templates
	} else if( $args[ 'templates' ] ) {
		$templates_placeholders = '';
		$i = 1;
		foreach( $args[ 'templates' ] as $template_id ) {
			$templates_placeholders .= ' %d';
			if( $i < count( $args[ 'templates' ] ) ) { $templates_placeholders .= ','; }
			++$i;
		}
		
		$query = 'SELECT X.object_type, X.object_id, X.exception_value, IF( X.object_type = "event", X.object_id, CONCAT( "G", X.object_id ) ) as unique_id '
				. ' FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' as X ' 
				. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON X.object_id = E.id AND X.object_type = "event" '
				. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as EG ON X.object_id = EG.id AND X.object_type = "group_of_events" '
				. ' LEFT JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON EG.category_id = C.id '
				. ' WHERE ( X.object_type = "event" AND E.template_id IN ( ' . $templates_placeholders . ' ) )'
				. ' OR ( X.object_type = "group_of_events" AND C.template_id IN ( ' . $templates_placeholders . ' ) )';
		
		$variables = array_merge( $variables, $args[ 'templates' ], $args[ 'templates' ] );
	}
	
	// Retrieve only specifics types (events or group of events)
	if( $args[ 'types' ] ) {
		$query .= ' AND X.object_type IN ( ';
		$i = 1;
		foreach( $args[ 'types' ] as $type ) {
			$query .= ' %s';
			if( $i < count( $args[ 'types' ] ) ) { $query .= ','; }
			++$i;
		}
		$query .= ' )';
		$variables = array_merge( $variables, $args[ 'types' ] );
	}

	$query .= ' ORDER BY unique_id, X.exception_value ASC';

	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	$exceptions = $wpdb->get_results( $query, ARRAY_A );
	
	return $exceptions;
}




// GROUPS OF EVENTS

/**
 * Get group of events data
 * 
 * @since 1.1.0
 * @version 1.5.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @param OBJECT|ARRAY_A $return_type
 * @return object|array|boolean
 */
function bookacti_get_group_of_events( $group_id, $return_type = OBJECT ) {

	$return_type = $return_type === OBJECT ? OBJECT : ARRAY_A;

	global $wpdb;

	$query	= 'SELECT G.*, GE.start, GE.end FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G ';
	$query .= ' LEFT JOIN ( 
					SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end
					FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' 
					GROUP BY group_id
				) as GE ON GE.group_id = G.id ';
	$query .= ' WHERE G.id = %d ';
	$prep	= $wpdb->prepare( $query, $group_id );
	$group	= $wpdb->get_row( $prep, $return_type );

	if( empty( $group ) ) {
		return false;
	}

	// Get template settings and managers
	if( $return_type === ARRAY_A ) {
		// Translate title
		$group[ 'multilingual_title' ]	= $group[ 'title' ];
		$group[ 'title' ]				= apply_filters( 'bookacti_translate_text', $group[ 'title' ] );

		$group[ 'settings' ]			= bookacti_get_metadata( 'group_of_events', $group_id );

	} else {
		// Translate title
		$group->multilingual_title	= $group->title;
		$group->title				= apply_filters( 'bookacti_translate_text', $group->title );

		$group->settings			= bookacti_get_metadata( 'group_of_events', $group_id );
	}

	return $group;
}


/**
 * Get groups of events data by template ids
 * @since 1.4.0 (was bookacti_get_groups_of_events_by_template and bookacti_get_groups_of_events_by_category)
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @param array|int $templates
 *  @param array|int $group_categories
 *  @param array|int event_groups
 *  @param array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @param boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @param boolean $past_events Whether to get past groups of events
 *  @param boolean $bounding_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_get_groups_of_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'group_categories' => array(),
		'event_groups' => array(),
		'interval' => array(),
		'skip_exceptions' => 1,
		'past_events' => 0,
		'bounding_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp				= $current_datetime_object->format( 'U' );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	$variables					= array();

	$query	= 'SELECT DISTINCT G.id, G.category_id, G.title, G.repeat_freq, G.repeat_step, G.repeat_on, G.repeat_from, G.repeat_to, GE.start, GE.end, C.template_id '
			. ' FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G ' 
			. ' LEFT JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON C.id = G.category_id '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON T.id = C.template_id ';

	// Join the meta table to filter groups already started
	$query .= ' LEFT JOIN ( 
					SELECT object_id as group_category_id, IFNULL( NULLIF( meta_value, -1 ), %d ) as started_groups_bookable
					FROM ' . BOOKACTI_TABLE_META . ' 
					WHERE object_type = "group_category" 
					AND meta_key = "started_groups_bookable" 
				) as M ON M.group_category_id = G.category_id ';

	$started_groups_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_groups_bookable' );
	$variables[] = $started_groups_bookable;

	// Join the groups events table to filter groups already started
	$query .= ' LEFT JOIN ( 
					SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end
					FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' 
					GROUP BY group_id
				) as GE ON GE.group_id = G.id ';

	$query .= ' WHERE GE.start IS NOT NULL '
			. ' AND GE.end IS NOT NULL '
			. ' AND G.active = 1 '
			. ' AND C.active = 1 '
			. ' AND T.active = 1 ';
	
	// Do not fetch events totally out of the desired interval
	if( $args[ 'interval' ] ) {
		$query  .= ' 
		AND (
				( 	NULLIF( G.repeat_freq, "none" ) IS NULL 
					AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						AND
							UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						) 
				) 
				OR
				( 	G.repeat_freq IS NOT NULL
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_from, " 00:00:00" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_to, " 23:59:59" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							)
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_from, " 00:00:00" ), %s, @@global.time_zone ) ) > 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_to, " 23:59:59" ), %s, @@global.time_zone ) ) > 
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

	// Whether to fetch past or started groups of events
	if( ! $args[ 'past_events' ] ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) >= %d 
						OR	UNIX_TIMESTAMP( CONVERT_TZ( ( G.repeat_to + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) >= %d ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;

		// Fetch group of events already started but not finished
		$query .= ' OR	(	M.started_groups_bookable = 1
						AND UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) <= %d 
						AND UNIX_TIMESTAMP( CONVERT_TZ( GE.end, %s, @@global.time_zone ) ) >= %d 
						)';
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp;
			
		$query .= ') ';
	}
	
	// Get events from desired templates only
	if( $args[ 'templates' ] ) {
		$query .= ' AND C.template_id IN ( %d ';
		$array_count = count( $args[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}
	
	// Get events from categories only
	if( $args[ 'group_categories' ] ) {
		$query .= ' AND G.category_id IN ( %d ';
		$array_count = count( $args[ 'group_categories' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'group_categories' ] );
	}
	
	// Get desired groups of events only
	if( $args[ 'event_groups' ] ) {
		$query .= ' AND G.id IN ( %d ';
		$array_count = count( $args[ 'event_groups' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'event_groups' ] );
	}
	
	$order_by = apply_filters( 'bookacti_groups_of_events_list_order_by', array( 'category_id', 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'G.id'; }
			if( $order_by[ $i ] === 'title' ) { $order_by[ $i ] = 'G.title'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	$query = apply_filters( 'bookacti_get_groups_of_events_query', $wpdb->prepare( $query, $variables ), $args );
	
	$groups	= $wpdb->get_results( $query, OBJECT );
	
	// Transform raw groups from database to array of individual group
	$groups_array = bookacti_get_groups_of_events_array_from_db_groups_of_events( $groups, $args );
	
	return apply_filters( 'bookacti_get_groups_of_events', $groups_array, $query, $args );
}


/**
 * Get group of events availability (= the lowest availability among its events)
 * @since 1.1.0
 * @version 1.7.1
 * @global wpdb $wpdb
 * @param int|array $group_of_events_ids
 * @return false|int|array
 */
function bookacti_get_group_of_events_availability( $group_of_events_ids ) {
	// Sanitize the array of group of events ID
	if( ! is_array( $group_of_events_ids ) ) {
		$variables = array( intval( $group_of_events_ids ) );
	} else {
		$variables = array_filter( array_map( 'intval', $group_of_events_ids ) );
	}

	if( ! $variables ) { return false; }

	global $wpdb;

	$query = 'SELECT GE.group_id, MIN( E.availability - IFNULL( B.quantity_booked, 0 ) ) as availability '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
			. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ( '
				. ' SELECT event_id, event_start, event_end, SUM( quantity ) as quantity_booked FROM ' . BOOKACTI_TABLE_BOOKINGS 
				. ' WHERE active = 1 '
				. ' GROUP BY CONCAT( event_id, event_start, event_end ) '
			. ' ) as B ON ( B.event_id = GE.event_id AND B.event_start = GE.event_start AND B.event_end = GE.event_end ) '
			. ' WHERE GE.event_id = E.id '
			. ' AND GE.group_id IN ( %d';

	$array_count = count( $variables );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$query .= ', %d';
		}
	}

	$query .= ' )'
			. ' GROUP BY GE.group_id;';

	$query		= $wpdb->prepare( $query, $variables );
	$results	= $wpdb->get_results( $query );

	$group_availabilities = array();
	foreach( $results as $result ) {
		$group_availabilities[ $result->group_id ] = $result->availability;
	}

	// Return the single value if only one group was given
	if( ! is_array( $group_of_events_ids ) ) {
		return isset( $group_availabilities[ $group_of_events_ids ] ) ? $group_availabilities[ $group_of_events_ids ] : 0;
	}

	return $group_availabilities;
}




// GROUPS X EVENTS

/**
 * Get the grouped events per group
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args
 * @return array
 */
function bookacti_get_groups_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'group_categories' => array(),
		'event_groups' => array()
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;

	$query  = 'SELECT GE.group_id, GE.event_id as id, GE.event_start as start, GE.event_end as end, E.title, E.template_id, A.color, IFNULL( NULLIF( GE.activity_id, 0 ), E.activity_id ) as activity_id '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE ' 
			. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G ON GE.group_id = G.id ' 
			. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON GE.event_id = E.id ' 
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON IFNULL( NULLIF( GE.activity_id, 0 ), E.activity_id ) = A.id ' 
			. ' WHERE NULLIF( GE.group_id, 0 ) IS NOT NULL '
			. ' AND E.active = 1 ';

	$variables = array();

	// Filter by template ids
	if( $args[ 'templates' ] ) {
		$query .= ' AND E.template_id IN (';
		$i = 1;
		foreach( $args[ 'templates' ] as $template_id ){
			$query .= ' %d';
			$variables[] = $template_id;
			if( $i < count( $args[ 'templates' ] ) ) { $query .= ','; }
			$i++;
		}
		$query .= ' ) ';
	}

	// Filter by category ids
	if( $args[ 'group_categories' ] ) {
		$query .= ' AND G.category_id IN (';
		$i = 1;
		foreach( $args[ 'group_categories' ] as $category_id ){
			$query .= ' %d';
			$variables[] = $category_id;
			if( $i < count( $args[ 'group_categories' ] ) ) { $query .= ','; }
			$i++;
		}
		$query .= ' ) ';
	}

	// Filter by group ids
	if( $args[ 'event_groups' ] ) {
		$query .= ' AND GE.group_id IN (';
		$i = 1;
		foreach( $args[ 'event_groups' ] as $group_id ){
			$query .= ' %d';
			$variables[] = $group_id;
			if( $i < count( $args[ 'event_groups' ] ) ) { $query .= ','; }
			$i++;
		}
		$query .= ' ) ';
	}

	$query .= ' ORDER BY GE.group_id, GE.event_start ';

	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	$events = $wpdb->get_results( $query, ARRAY_A );

	// Order by groups
	$groups_events = array();
	foreach( $events as $event ) {
		$event[ 'title' ] = apply_filters( 'bookacti_translate_text', $event[ 'title' ] );
		$group_id = intval( $event[ 'group_id' ] );
		if( ! isset( $groups_events[ $group_id ] ) ) { $groups_events[ $group_id ] = array(); }
		$groups_events[ $group_id ][] = $event;
	}

	return $groups_events;
}


/**
 * Get groups of an event // TO BE DELETED IN 1.12
 *
 * @param int $id
 * @param string $start
 * @param string $end
 * @param boolean $active_only Whether to get the group of events even if the link between the desired event and this group is inactive
 */
function bookacti_get_event_groups( $id, $start, $end ) {

	global $wpdb;

	$query	= ' SELECT GE.group_id, G.category_id '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
			. ' WHERE GE.group_id = G.id '
			. ' AND GE.event_id = %d '
			. ' AND GE.event_start = %s '
			. ' AND GE.event_end = %s ';

	$prep = $wpdb->prepare( $query, $id, $start, $end );
	$groups = $wpdb->get_results( $prep, OBJECT );

	return $groups;
}




// GROUP CATEGORIES

/**
 * Retrieve group categories data by id
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @param array $templates
 *  @param array $group_categories
 * }
 * @return array|boolean
 */
function bookacti_get_group_categories( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'group_categories' => array()
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;

	$query	= ' SELECT C.id, C.template_id, C.title, C.active '
			. ' FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C '
			. ' WHERE C.active = 1 ';

	$variables = array();

	if( $args[ 'templates' ] ) {
		$query .= ' AND C.template_id IN ( %d ';
		$array_count = count( $args[ 'templates' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'templates' ] );
	}

	if( $args[ 'group_categories' ] ) {
		$query .= ' AND C.id IN ( %d ';
		$array_count = count( $args[ 'group_categories' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $args[ 'group_categories' ] );
	}

	$order_by = apply_filters( 'bookacti_group_categories_list_order_by', array( 'template_id', 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	$categories = $wpdb->get_results( $query, ARRAY_A );
	
	// Get group categories meta
	$retrieved_category_ids = array();
	foreach( $categories as $category ) { $retrieved_category_ids[] = $category[ 'id' ]; }
	$categories_meta = bookacti_get_metadata( 'group_category', $retrieved_category_ids );
	
	$categories_data = array();
	foreach( $categories as $category ) {
		$category[ 'multilingual_title' ]	= $category[ 'title' ];
		$category[ 'title' ]				= apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
		$category[ 'settings' ]				= ! empty( $categories_meta[ $category[ 'id' ] ] ) ? $categories_meta[ $category[ 'id' ] ] : array();
		$categories_data[ $category[ 'id' ] ] = $category;
	}

	return $categories_data;
}


/**
 * Retrieve group category ids by template ids
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $check_roles
 * @return array
 */
function bookacti_get_group_category_ids_by_template( $template_ids = array(), $check_roles = false ) {
	global $wpdb;
	
	$variables = array();

	$query	= 'SELECT DISTINCT C.id FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C '
			. 'LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON C.template_id = T.id ';

	// Join the meta table to filter by roles
	if( $check_roles && ! is_super_admin() ) {
		$query .= ' LEFT JOIN ( 
						SELECT meta_value as roles, object_id as group_category_id 
						FROM ' . BOOKACTI_TABLE_META . ' 
						WHERE object_type = "group_category" 
						AND meta_key = "allowed_roles" 
					) as M ON M.group_category_id = C.id ';
		$query .= ' LEFT JOIN ( 
						SELECT user_id as manager_id, object_id as group_category_id
						FROM ' . BOOKACTI_TABLE_PERMISSIONS . ' 
						WHERE object_type = "group_category"
					) as P ON P.group_category_id = C.id ';
	}

	$query .= ' WHERE C.active = 1 '
			. ' AND T.active = 1 ';

	// Filter by roles
	if( $check_roles && ! is_super_admin() ) {
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
		$query .= ' AND C.template_id IN ( %d ';
		$array_count = count( $template_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $template_ids );
	}

	$order_by = apply_filters( 'bookacti_group_categories_list_order_by', array( 'template_id', 'title', 'id' ) );
	if( $order_by && is_array( $order_by ) ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($order_by); $i<$len; ++$i ) {
			if( $order_by[ $i ] === 'id' ) { $order_by[ $i ] = 'C.id'; }
			if( $order_by[ $i ] === 'title' ) { $order_by[ $i ] = 'C.title'; }
			$query .= $order_by[ $i ] . ' ASC';
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}

	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }
	$categories = $wpdb->get_results( $query, OBJECT );

	$category_ids = array();
	foreach( $categories as $category ) { $category_ids[] = $category->id; }

	return $category_ids;
}




// PERMISSIONS

/**
 * Get managers
 * @version 1.9.0
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @return array
 */
function bookacti_get_managers( $object_type, $object_id ) {
	global $wpdb;

	if( ! $object_type || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) ) { return array(); }

	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}

	if( ! $object_id ) { return array(); }

	$query	= 'SELECT object_id, user_id FROM ' . BOOKACTI_TABLE_PERMISSIONS
			. ' WHERE object_type = %s';

	$variables = array( $object_type );

	if( is_numeric( $object_id ) ) {
		$query .= ' AND object_id = %d';
		$variables[] = $object_id;

	} else if( is_array( $object_id ) ) {
		$query .= ' AND object_id IN ( %d ';
		$array_count = count( $object_id );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $object_id );
	}

	$query		= $wpdb->prepare( $query, $variables );
	$managers	= $wpdb->get_results( $query, OBJECT );

	if( is_null( $managers ) ) { return array(); }

	$managers_array = array();
	foreach( $managers as $manager ) {
		if( is_array( $object_id ) ) {
			if( ! isset( $managers_array[ $manager->object_id ] ) ) { $managers_array[ $manager->object_id ] = array();	}
			$managers_array[ $manager->object_id ][] = intval( $manager->user_id );
		} else {
			$managers_array[] = intval( $manager->user_id );
		}
	}

	return $managers_array;
}


/**
 * Update managers
 * 
 * @version 1.2.2
 * @param string $object_type
 * @param int $object_id
 * @param array $managers_array
 * @return int
 */
function bookacti_update_managers( $object_type, $object_id, $managers_array ) {

	if( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $managers_array ) ) { return false;	}

	$object_id = absint( $object_id );
	if( ! $object_id ) { return false; }

	$current_managers = bookacti_get_managers( $object_type, $object_id );

	// INSERT NEW USERS
	$inserted = 0;
	$new_managers = array_diff( $managers_array, $current_managers );
	if( ! empty( $new_managers ) ) {
		$inserted = bookacti_insert_managers( $object_type, $object_id, $new_managers );
	}

	// DELETE USERS WHO ARE NO LONGER IN THE LIST
	$deleted = 0;
	$old_managers = array_diff( $current_managers, $managers_array );
	if( ! empty( $old_managers ) ) {
		$deleted = bookacti_delete_managers( $object_type, $object_id, $old_managers );
	}

	return $inserted + $deleted;
}


/**
 * Insert managers
 * 
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $object_id
 * @param array $managers_array
 * @return int|boolean
 */
function bookacti_insert_managers( $object_type, $object_id, $managers_array ) {

	global $wpdb;

	if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $managers_array ) || empty( $managers_array ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	$insert_managers_query = 'INSERT INTO ' . BOOKACTI_TABLE_PERMISSIONS . ' ( object_type, object_id, user_id ) VALUES ';
	$insert_variables_array = array();
	$i = 0;
	foreach( $managers_array as $new_manager_id ) {
		$insert_managers_query .= '( %s, %d, %d )';
		if( ++$i === count( $managers_array ) ) {
			$insert_managers_query .= ';';
		} else {
			$insert_managers_query .= ', ';
		}
		$insert_variables_array[] = $object_type;
		$insert_variables_array[] = intval( $object_id );
		$insert_variables_array[] = intval( $new_manager_id );
	}
	$insert_query_prep = $wpdb->prepare( $insert_managers_query, $insert_variables_array );
	$inserted = $wpdb->query( $insert_query_prep );

	return $inserted;
}


/**
 * Delete managers
 * 
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $object_id
 * @param array $managers_array
 * @return int|boolean
 */
function bookacti_delete_managers( $object_type, $object_id, $managers_array ) {

	global $wpdb;

	if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $managers_array ) || empty( $managers_array ) ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	$delete_managers_query = 'DELETE FROM ' . BOOKACTI_TABLE_PERMISSIONS . ' WHERE object_type = %s AND object_id = %d AND user_id IN( ';
	$delete_variables_array = array( $object_type, $object_id );
	$j = 0;
	foreach( $managers_array as $old_manager_id ) {
		$delete_managers_query .= '%d';
		if( ++$j === count( $managers_array ) ) {
			$delete_managers_query .= ' );';
		} else {
			$delete_managers_query .= ', ';
		}
		$delete_variables_array[] = $old_manager_id;
	}
	$delete_query_prep = $wpdb->prepare( $delete_managers_query, $delete_variables_array );
	$deleted = $wpdb->query( $delete_query_prep );

	return $deleted;
}