<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// EVENTS

/**
 * Fetch events by templates and / or activities
 * @version 1.13.0
 * @param array $raw_args {
 *  @type array $templates Array of template IDs
 *  @type array $activities Array of activity IDs
 *  @type array $events Array of event IDs
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to compute past events
 *  @type boolean $bounding_only Whether to retrieve the first and the last events only
 *  @type boolean $data_only Whether to retrieve the events data only, not occurrences
 * }
 * @return array $events_array Array of events
 */
function bookacti_fetch_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'activities' => array(),
		'events' => array(),
		'interval' => array(),
		'skip_exceptions' => 1,
		'past_events' => 0,
		'bounding_only' => 0,
		'data_only' => 0
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
	$query  = 'SELECT DISTINCT E.id as event_id, E.template_id, E.activity_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.repeat_exceptions, E.availability, A.color '
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id '
			. ' WHERE E.active = 1 '
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

	// Get events from desired event IDs only
	if( $args[ 'events' ] ) {
		$query  .= ' AND E.id IN ( %d';
		for( $i=1,$len=count( $args[ 'events' ] ); $i < $len; ++$i ) {
			$query  .= ', %d';
		}
		$query  .= ' ) ';
		$variables = array_merge( $variables, $args[ 'events' ] );
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
 * Fetch booked events only
 * @since 1.2.2
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates Array of template IDs
 *  @type array $activities Array of activity IDs
 *  @type array events Array of event IDs
 *  @type array $status Array of groups of events IDs
 *  @type int|false $active 0 or 1. False to ignore.
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type int|string $users Array of user IDs
 *  @type boolean $past_events Whether to compute past events
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $bounding_only Whether to retrieve the first and the last events only
 *  @type boolean $data_only Whether to retrieve the events data only, not occurrences
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
		'bounding_only' => 0,
		'data_only' => 0
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
	$query  = 'SELECT DISTINCT B.event_id, E.template_id, E.activity_id, E.title, B.event_start as start, B.event_end as end, "none" as repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.repeat_exceptions, E.availability, A.color '
			. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON B.event_id = E.id '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id '
			. ' WHERE TRUE ';

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
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return object|false
 */
function bookacti_get_event_by_id( $event_id ) {
	global $wpdb;

	$query	= 'SELECT E.id as event_id, E.template_id, E.activity_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.repeat_exceptions, E.availability, E.active as event_active, A.color, A.active as activity_active, T.active as template_active ' 
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_ACTIVITIES . ' as A ON E.activity_id = A.id '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ON E.template_id = T.id '
			. ' WHERE E.id = %d';
	$query = $wpdb->prepare( $query, $event_id );
	$event = $wpdb->get_row( $query, OBJECT );
	
	if( ! is_object( $event ) ) { return false; }
	
	$event->repeat_exceptions = ! empty( $event->repeat_exceptions ) ? maybe_unserialize( $event->repeat_exceptions ) : array();
	if( ! is_array( $event->repeat_exceptions ) ) { $event->repeat_exceptions = array(); }
	
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




// GROUPS OF EVENTS

/**
 * Get groups of events data by template ids
 * @since 1.4.0 (was bookacti_get_groups_of_events_by_template and bookacti_get_groups_of_events_by_category)
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates
 *  @type array $group_categories
 *  @type array $event_groups
 *  @type array $nb_events array( 'min' => 2, 'max' => 0 ) Retrieve groups having between 'min' and 'max' events only
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type boolean $interval_started Whether to retrieve started groups in interval
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to get past groups of events
 *  @type boolean $data_only Whether to retrieve the groups data only, not the occurrences
 * }
 * @return array
 */
function bookacti_get_groups_of_events( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'group_categories' => array(),
		'event_groups' => array(),
		'nb_events' => array( 'min' => 2, 'max' => 0 ),
		'interval' => array(),
		'interval_started' => 0,
		'skip_exceptions' => 1,
		'past_events' => 0,
		'data_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp				= $current_datetime_object->format( 'U' );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	$variables					= array();

	$query	= 'SELECT DISTINCT G.id, G.category_id, G.title, G.repeat_freq, G.repeat_step, G.repeat_on, G.repeat_from, G.repeat_to, G.repeat_exceptions, GE.start, GE.end, GE.delta_days, C.template_id, M.started_groups_bookable '
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
					SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end, COUNT( id ) as nb_events, ABS( DATEDIFF( MAX( event_start ), MIN( event_start ) ) ) as delta_days
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
		$interval_start = $args[ 'interval_started' ] ? 'DATE_SUB( %s, INTERVAL GE.delta_days DAY )' : '%s';
		
		$query  .= ' 
		AND (
				( 	NULLIF( G.repeat_freq, "none" ) IS NULL 
					AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( ' . $interval_start . ', %s, @@global.time_zone ) ) 
						AND
							UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
						) 
				) 
				OR
				( 	G.repeat_freq IS NOT NULL
					AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_from, " 00:00:00" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( ' . $interval_start . ', %s, @@global.time_zone ) ) 
							AND 
								UNIX_TIMESTAMP( CONVERT_TZ( CONCAT( G.repeat_to, " 23:59:59" ), %s, @@global.time_zone ) ) < 
								UNIX_TIMESTAMP( CONVERT_TZ( ' . $interval_start . ', %s, @@global.time_zone ) ) 
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
	
	// Get groups having a certain number of events only
	if( ! empty( $args[ 'nb_events' ][ 'min' ] ) ) {
		$query .= ' AND GE.nb_events >= %d ';
		$variables[] = intval( $args[ 'nb_events' ][ 'min' ] );
	}
	if( ! empty( $args[ 'nb_events' ][ 'max' ] ) ) {
		$query .= ' AND GE.nb_events <= %d ';
		$variables[] = intval( $args[ 'nb_events' ][ 'max' ] );
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




// GROUP CATEGORIES

/**
 * Retrieve group categories data by id
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates
 *  @type array $group_categories
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