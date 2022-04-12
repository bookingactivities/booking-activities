<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// EVENTS

/**
 * Fetch events to display on calendar editor
 * @since 1.1.0 (replace bookacti_fetch_events)
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $raw_args {
 *  @type array $templates Array of template IDs
 *  @type array events Array of event IDs
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to compute past events
 *  @type boolean $bounding_only Whether to retrieve the first and the last events only
 *  @type boolean $data_only Whether to retrieve the events data only, not occurrences
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
		'bounding_only' => 0,
		'data_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );

	global $wpdb;

	// Set current datetime
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );

	// Select events
	$query  = 'SELECT E.id as event_id, E.template_id, E.activity_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_step, E.repeat_on, E.repeat_from, E.repeat_to, E.repeat_exceptions, E.availability, A.color ' 
			. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
			. ' WHERE E.activity_id = A.id '
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
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $data Data sanitized with bookacti_sanitize_event_data
 * @return int
 */
function bookacti_insert_event( $data ) {
	global $wpdb;
	
	$query = ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, availability, start, end, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions, active ) '
			. ' VALUES ( %d, %d, %s, %d, %s, %s, %s, NULLIF( NULLIF( %d, -1 ), 0 ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), 1 )';
	
	$variables = array( 
		$data[ 'template_id' ], 
		$data[ 'activity_id' ], 
		$data[ 'title' ], 
		$data[ 'availability' ], 
		$data[ 'start' ], 
		$data[ 'end' ], 
		$data[ 'repeat_freq' ], 
		$data[ 'repeat_step' ], 
		$data[ 'repeat_on' ], 
		$data[ 'repeat_from' ], 
		$data[ 'repeat_to' ],
		maybe_serialize( $data[ 'repeat_exceptions' ] )
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	$event_id = $wpdb->insert_id;
	
	return $event_id;
}


/**
 * Duplicate an event
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_duplicate_event( $event_id ) {
	global $wpdb;

	$query	= ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, availability, start, end, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions, active ) '
			. ' SELECT template_id, activity_id, title, availability, start, end, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions, active '
			. ' FROM ' . BOOKACTI_TABLE_EVENTS 
			. ' WHERE id = %d ';
	$query	= $wpdb->prepare( $query, $event_id );
	$inserted = $wpdb->query( $query );
	$new_event_id = $wpdb->insert_id;

	return $new_event_id;      
}


/** 
 * Duplicate template events
 * @since 1.13.0
 * @global wpdb $wpdb
 * @param int $from_template_id
 * @param int $to_template_id
 * @return int
 */
function bookacti_duplicate_template_events( $from_template_id, $to_template_id ) {
	global $wpdb;

	// Duplicate events and their metadata
	$query_events = ' SELECT id FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE template_id = %d ';
	$query_events = $wpdb->prepare( $query_events, $from_template_id );
	$events	= $wpdb->get_results( $query_events, OBJECT );
	
	$duplicated = 0;
	foreach( $events as $event ) {
		$old_event_id = $event->id;

		// Duplicate the event and get its id
		$query	= ' INSERT INTO ' . BOOKACTI_TABLE_EVENTS . ' ( template_id, activity_id, title, start, end, availability, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions ) '
				. ' SELECT %d, activity_id, title, start, end, availability, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d AND active = 1';
		$query = $wpdb->prepare( $query, $to_template_id, $event->id );
		$wpdb->query( $query );

		$new_event_id = $wpdb->insert_id;

		bookacti_duplicate_metadata( 'event', $old_event_id, $new_event_id );
		
		++$duplicated;
	}
	
	return $duplicated;
}


/**
 * Update event data
 * @since 1.2.2 (was bookacti_set_event_data)
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $data Data sanitized with bookacti_sanitize_event_data
 * @return int|false
 */
function bookacti_update_event( $data ) {
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET '
				. ' template_id = IFNULL( NULLIF( %d, 0 ), template_id ), '	
				. ' activity_id = IFNULL( NULLIF( %d, 0 ), activity_id ), '	
				. ' title = IFNULL( NULLIF( %s, "" ), title ), '
				. ' availability = IFNULL( NULLIF( %d, -1 ), availability ), '
				. ' start = IFNULL( NULLIF( %s, "" ), start ), '
				. ' end = IFNULL( NULLIF( %s, "" ), end ), '
				. ' repeat_freq = IFNULL( NULLIF( %s, "" ), repeat_freq ), '
				. ' repeat_step = NULLIF( IFNULL( NULLIF( %d, 0 ), repeat_step ), -1 ),'
				. ' repeat_on = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_on ), "null" ),'
				. ' repeat_from = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_from ), "null" ), '
				. ' repeat_to = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_to ), "null" ), '
				. ' repeat_exceptions = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_exceptions ), "null" ) '
			. ' WHERE id = %d ';
	
	$variables = array( 
		$data[ 'template_id' ],
		$data[ 'activity_id' ],
		$data[ 'title' ],
		! is_null( $data[ 'availability' ] ) ? $data[ 'availability' ] : -1,
		$data[ 'start' ],
		$data[ 'end' ],
		$data[ 'repeat_freq' ],
		! is_null( $data[ 'repeat_step' ] ) ? $data[ 'repeat_step' ] : -1,
		! is_null( $data[ 'repeat_on' ] ) ? $data[ 'repeat_on' ] : 'null',
		! is_null( $data[ 'repeat_from' ] ) ? $data[ 'repeat_from' ] : 'null',
		! is_null( $data[ 'repeat_to' ] ) ? $data[ 'repeat_to' ] : 'null',
		! is_null( $data[ 'repeat_exceptions' ] ) ? maybe_serialize( $data[ 'repeat_exceptions' ] ) : 'null',
		$data[ 'id' ]
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

	return $updated;
}


/**
 * Deactivate an event
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int|false
 */
function bookacti_deactivate_event( $event_id ) {
	global $wpdb;
	$query = ' UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET active = 0 WHERE id = %d ';
	$query = $wpdb->prepare( $query, $event_id );
	$deactivated = $wpdb->query( $query );
	return $deactivated;
}




// GROUP OF EVENTS

/**
 * Insert a group of events
 * @since 1.1.0
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $data sanitized with bookacti_sanitize_group_of_events_data
 * @return int
 */
function bookacti_insert_group_of_events( $data ) {
	global $wpdb;
	
	$query = ' INSERT INTO ' . BOOKACTI_TABLE_EVENT_GROUPS . ' ( category_id, title, repeat_freq, repeat_step, repeat_on, repeat_from, repeat_to, repeat_exceptions, active ) '
			. ' VALUES ( %d, %s, %s, NULLIF( NULLIF( %d, -1 ), 0 ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), NULLIF( NULLIF( %s, "null" ), "" ), 1 )';
	
	$variables = array( 
		$data[ 'category_id' ], 
		$data[ 'title' ], 
		$data[ 'repeat_freq' ], 
		$data[ 'repeat_step' ], 
		$data[ 'repeat_on' ], 
		$data[ 'repeat_from' ], 
		$data[ 'repeat_to' ],
		maybe_serialize( $data[ 'repeat_exceptions' ] )
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );
	
	$event_id = $wpdb->insert_id;
	
	return $event_id;
}


/**
 * Update a group of events
 * @since 1.1.0
 * @version 1.13.0
 * @global wpdb $wpdb
 * @param array $data sanitized with bookacti_sanitize_group_of_events_data
 * @return int|boolean
 */
function bookacti_update_group_of_events( $data ) {
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' SET '
				. ' category_id = IFNULL( NULLIF( %d, 0 ), category_id ), '	
				. ' title = IFNULL( NULLIF( %s, "" ), title ), '
				. ' repeat_freq = IFNULL( NULLIF( %s, "" ), repeat_freq ), '
				. ' repeat_step = NULLIF( IFNULL( NULLIF( %d, 0 ), repeat_step ), -1 ),'
				. ' repeat_on = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_on ), "null" ),'
				. ' repeat_from = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_from ), "null" ), '
				. ' repeat_to = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_to ), "null" ), '
				. ' repeat_exceptions = NULLIF( IFNULL( NULLIF( %s, "" ), repeat_exceptions ), "null" ) '
			. ' WHERE id = %d ';
	
	$variables = array( 
		$data[ 'category_id' ],
		$data[ 'title' ],
		$data[ 'repeat_freq' ], 
		! is_null( $data[ 'repeat_step' ] ) ? $data[ 'repeat_step' ] : -1, 
		! is_null( $data[ 'repeat_on' ] ) ? $data[ 'repeat_on' ] : 'null', 
		! is_null( $data[ 'repeat_from' ] ) ? $data[ 'repeat_from' ] : 'null', 
		! is_null( $data[ 'repeat_to' ] ) ? $data[ 'repeat_to' ] : 'null', 
		! is_null( $data[ 'repeat_exceptions' ] ) ? maybe_serialize( $data[ 'repeat_exceptions' ] ) : 'null', 
		$data[ 'id' ]
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

	return $updated;
}


/**
 * Delete a group of events
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @return boolean
 */
function bookacti_delete_group_of_events( $group_id ) {
	global $wpdb;

	// Delete events of the group
	$query_events	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE group_id = %d ';
	$query_events	= $wpdb->prepare( $query_events, $group_id );
	$deleted1		= $wpdb->query( $query_events );

	// Delete the group itself
	$query_group	= 'DELETE FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' WHERE id = %d ';
	$query_group	= $wpdb->prepare( $query_group, $group_id );
	$deleted2		= $wpdb->query( $query_group );

	return $deleted1 === false && $deleted2 === false ? false : intval( $deleted1 ) + intval( $deleted2 );
}


/**
 * Deactivate a group of events
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @return int|false
 */
function bookacti_deactivate_group_of_events( $group_id ) {
	global $wpdb;
	
	$query_group	= 'UPDATE ' . BOOKACTI_TABLE_EVENT_GROUPS . ' SET active = 0 WHERE id = %d ';
	$query_group	= $wpdb->prepare( $query_group, $group_id );
	$deactivated	= $wpdb->query( $query_group );
	
	return $deactivated;
}


/**
 * Get an array of all group of events ids bound to designated category
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $category_ids
 * @param boolean $fetch_inactive
 * @return array
 */
function bookacti_get_groups_of_events_ids_by_category( $category_ids, $fetch_inactive = false ) {
	global $wpdb;

	$query	= 'SELECT G.id FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
			. ' WHERE G.category_id IN ( ';

	$i = 1;
	foreach( $category_ids as $category_id ){
		$query .= $i < count( $category_ids ) ? '%d, ' : '%d';
		$i++;
	}
	$query .= ' ) ';

	if( ! $fetch_inactive ) { $query .= ' AND G.active = 1 '; }

	$query = $wpdb->prepare( $query, $category_ids );
	$groups	= $wpdb->get_results( $query, OBJECT );

	$groups_ids = array();
	foreach( $groups as $group ) { $groups_ids[] = $group->id; }

	return $groups_ids;
}


/**
 * Get the template id of a group of events
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @return int
 */
function bookacti_get_group_of_events_template_id( $group_id ) {
	global $wpdb;
	$query	= 'SELECT C.template_id '
			. ' FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES  . ' as C, ' . BOOKACTI_TABLE_EVENT_GROUPS  . ' as G '
			. ' WHERE C.id = G.category_id '
			. ' AND G.id = %d ';
	$query = $wpdb->prepare( $query, $group_id );
	$template_id = $wpdb->get_var( $query );
	return intval( $template_id );
}




// GROUPS X EVENTS

/**
 * Insert events into a group
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @param array $events
 * @return int|false
 */
function bookacti_insert_events_into_group( $group_id, $events ) {
	global $wpdb;

	$query = 'INSERT INTO ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' ( group_id, activity_id, event_id, event_start, event_end ) VALUES ';

	$i = 0;
	$variables = array();
	foreach( $events as $event ) {
		if( $i > 0 ) { $query .= ','; } 
		$query .= ' ( %d, %d, %d, %s, %s ) ';
		$variables[] = $group_id;
		$variables[] = ! empty( $event[ 'activity_id' ] ) ? $event[ 'activity_id' ] : 0;
		$variables[] = ! empty( $event[ 'event_id' ] ) ? $event[ 'event_id' ] : ( isset( $event[ 'id' ] ) ? $event[ 'id' ] : 0 );
		$variables[] = $event[ 'start' ];
		$variables[] = $event[ 'end' ];
		$i++;
	}

	$query = $wpdb->prepare( $query, $variables );
	$inserted = $wpdb->query( $query );

	return $inserted;
}


/**
 * Delete events from a group
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @param array $events
 * @return int|false
 */
function bookacti_delete_events_from_group( $group_id, $events = array() ) {
	global $wpdb;

	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' WHERE group_id = %d';
	$variables = array( $group_id );
	
	if( $events ) {
		$query .= ' AND ( ';
		$i = 0;
		foreach( $events as $event ) {
			$query .= ' ( event_id = %d AND event_start = %s AND event_end = %s ) ';
			$variables[] = ! empty( $event[ 'event_id' ] ) ? $event[ 'event_id' ] : ( isset( $event[ 'id' ] ) ? $event[ 'id' ] : 0 );
			$variables[] = $event[ 'start' ];
			$variables[] = $event[ 'end' ];
			$i++;
			if( $i < count( $events ) ) { $query .= ' OR '; } 
		}
		$query .= ' ) ';
	}

	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Delete events starting on a specific date from a group 
 * @since 1.8.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $group_id
 * @param array $events
 * @return int|boolean
 */
function bookacti_delete_events_on_dates_from_group( $group_id, $dates ) {
	global $wpdb;

	$query	= 'DELETE FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' WHERE group_id = %d '
			. ' AND DATE( event_start ) IN ( ';
	
	$i = 1;
	foreach( $dates as $date ) {
		$query .= $i < count( $dates ) ? '%s, ' : '%s';
		$i++;
	}
	$query .= ' )';
	
	$variables = array_merge( array( $group_id ), $dates );
	$query = $wpdb->prepare( $query, $variables );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Delete an event from all groups of events
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return int|false
 */
function bookacti_delete_event_from_groups( $event_id, $event_start = false, $event_end = false ) {
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
 * @since 1.12.0
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param int $template_id
 * @return int|false
 */
function bookacti_delete_activity_events_from_groups( $activity_id, $template_id = 0 ){
	global $wpdb;

	$query	= 'DELETE GE '
			. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
			. ' LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON GE.event_id = E.id '
			. ' WHERE GE.activity_id = %d ';

	$variables = array( $activity_id );

	if( $template_id ) {
		$query .= ' AND E.template_id = %d ';
		$variables[] = $template_id;
	}

	$query = $wpdb->prepare( $query, $variables );
	$deactivated = $wpdb->query( $query );

	return $deactivated;
}


/**
 * Update dates of an event bound to a group with a relative amount of days
 * @since 1.10.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param int $delta_seconds_start
 * @param int $delta_seconds_end
 * @return int|false
 */
function bookacti_shift_grouped_event_dates( $event_id, $delta_seconds_start = 0, $delta_seconds_end = 0 ) {
	global $wpdb;

	$query	= 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' SET  event_start = DATE_ADD( event_start, INTERVAL %d SECOND ), '
				.  ' event_end = DATE_ADD( event_end, INTERVAL %d SECOND ) '
			. ' WHERE event_id = %d ';
	$query	= $wpdb->prepare( $query, $delta_seconds_start, $delta_seconds_end, $event_id );
	$updated= $wpdb->query( $query );
	
	return $updated;   
}


/**
 * Update id of an event bound to a group
 * @version 1.10.0
 * @global wpdb $wpdb
 * @param int $old_event_id
 * @param int $new_event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $from Y-m-d H:i:s
 * @param string $to Y-m-d H:i:s
 * @return int|false
 */
function bookacti_update_grouped_event_id( $old_event_id, $new_event_id, $event_start = '', $event_end = '', $from = '', $to = '' ) {
	global $wpdb;
	
	$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime_object	= new DateTime( 'now', $timezone );
	$user_timestamp_offset		= $current_datetime_object->format( 'P' );
	
	$query	= 'UPDATE ' . BOOKACTI_TABLE_GROUPS_EVENTS 
			. ' SET event_id = %d '
			. ' WHERE event_id = %d ';

	$variables = array( $new_event_id, $old_event_id );

	if( $event_start ) {
		$query .= ' AND event_start = %s ';
		$variables[] = $event_start;
	}

	if( $event_end ) {
		$query .= ' AND event_end = %s ';
		$variables[] = $event_end;
	}
	
	if( $from ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( event_start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';
		$variables[] = $user_timestamp_offset;
		$variables[] = $from;
		$variables[] = $user_timestamp_offset;
	}
	
	if( $to ) {
		$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( event_start, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) )';
		$variables[] = $user_timestamp_offset;
		$variables[] = $to;
		$variables[] = $user_timestamp_offset;
	}

	$query		= $wpdb->prepare( $query, $variables );
	$updated	= $wpdb->query( $query );

	return $updated;   
}




// GROUP CATEGORIES

/**
 * Insert a group category
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param string $category_title
 * @param int $template_id
 * @return int
 */
function bookacti_insert_group_category( $category_title, $template_id ) {
	global $wpdb;
	
	$query = ' INSERT INTO ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' ( template_id, title, active ) '
			. ' VALUES ( %d, %s, 1 )';
	
	$query = $wpdb->prepare( $query, $template_id, $category_title );
	$wpdb->query( $query );
	
	return $wpdb->insert_id;
}


/**
 * Update a group category
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function bookacti_update_group_category( $data ) {
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' SET '
				. ' title = IFNULL( NULLIF( %s, "" ), title ) '
			. ' WHERE id = %d ';
	
	$variables = array( $data[ 'title' ], $data[ 'id' ] );
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

	return $updated;
}


/**
 * Delete a group category
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param type $category_id
 * @return int|false
 */
function bookacti_delete_group_category( $category_id ) {
	global $wpdb;
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' WHERE id = %d ';
	$query = $wpdb->prepare( $query, $category_id );
	$deleted = $wpdb->query( $query );
	return $deleted;
}


/**
 * Deactivate a group category 
 * @since 1.1.0
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $category_id
 * @return int|false
 */
function bookacti_deactivate_group_category( $category_id ) {
	global $wpdb;
	$query = ' UPDATE ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' SET active = 0 WHERE id = %d ';
	$query = $wpdb->prepare( $query, $category_id );
	$deactivated = $wpdb->query( $query );
	return $deactivated;
}


/**
 * Get group category data
 * @since 1.1.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $category_id
 * @return array
 */
function bookacti_get_group_category( $category_id ) {
	global $wpdb;

	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' WHERE id = %d ';
	$query = $wpdb->prepare( $query, $category_id );
	$category = $wpdb->get_row( $query, ARRAY_A );
	
	if( ! $category ) { $category = array(); }
	
	// Get template settings and managers
	$category[ 'multilingual_title' ] = $category[ 'title' ];
	$category[ 'title' ]              = ! empty( $category[ 'title' ] ) ? apply_filters( 'bookacti_translate_text', $category[ 'title' ] ) : '';
	$category[ 'settings' ]           = bookacti_get_metadata( 'group_category', $category_id );

	return $category;
}


/**
 * Get the template id of a group category
 * @since 1.1.0
 * @ersion 1.12.0
 * @global wpdb $wpdb
 * @param int $category_id
 * @return int
 */
function bookacti_get_group_category_template_id( $category_id ) {
	global $wpdb;
	$query = 'SELECT template_id FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' WHERE id = %d ';
	$query = $wpdb->prepare( $query, $category_id );
	$template_id = $wpdb->get_var( $query );
	return intval( $template_id );
}




// TEMPLATES

/**
 * Get templates
 * @version 1.14.0
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
		$query = 'SELECT T.id, T.title, T.active '
			. ' FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T '
			. ' WHERE T.active = 1 ';
	} else {
		$query = 'SELECT T.id, T.title, T.active '
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
		$template[ 'multilingual_title' ] = $template[ 'title' ];
		$template[ 'title' ]              = $template[ 'title' ] ? apply_filters( 'bookacti_translate_text', $template[ 'title' ] ) : '';
		
		$templates_by_id[ $template[ 'id' ] ] = $template;
	}

	return $templates_by_id;
}


/**
 * Create a new template
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $data Data sanitized with bookacti_sanitize_template_data
 * @return int
 */
function bookacti_insert_template( $data ) { 
	global $wpdb;

	$query = ' INSERT INTO ' . BOOKACTI_TABLE_TEMPLATES . ' ( title, active ) '
			. ' VALUES ( %s, 1 )';

	$variables = array( $data[ 'title' ] );

	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );

	$template_id = $wpdb->insert_id;

	return $template_id;
}


/**
 * Deactivate a template
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $template_id
 * @return int|false
 */
function bookacti_deactivate_template( $template_id ) {
	global $wpdb;
	$query = ' UPDATE ' . BOOKACTI_TABLE_TEMPLATES . ' SET active = 0 WHERE id = %d ';
	$query = $wpdb->prepare( $query, $template_id );
	$deactivated = $wpdb->query( $query );
	return $deactivated;
}


/**
 * Update template
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function bookacti_update_template( $data ) { 
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_TEMPLATES . ' SET '
				. ' title = IFNULL( NULLIF( %s, "" ), title ) '
			. ' WHERE id = %d ';
	
	$variables = array( $data[ 'title' ], $data[ 'id' ] );
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

	return $updated;
}




// ACTIVITIES

/**
 * Insert an activity
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function bookacti_insert_activity( $data ) {
	global $wpdb;

	$query = ' INSERT INTO ' . BOOKACTI_TABLE_ACTIVITIES . ' ( title, color, availability, duration, active ) '
			. ' VALUES ( %s, %s, %d, %s, 1 )';

	$variables = array( $data[ 'title' ], $data[ 'color' ], $data[ 'availability' ], $data[ 'duration' ] );

	$query = $wpdb->prepare( $query, $variables );
	$wpdb->query( $query );

	$activity_id = $wpdb->insert_id;

	return $activity_id;
}


/**
 * Update an activity
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function bookacti_update_activity( $data ) {
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_ACTIVITIES . ' SET '
				. ' title = IFNULL( NULLIF( %s, "" ), title ), '
				. ' color = IFNULL( NULLIF( %s, "" ), color ), '
				. ' availability = IFNULL( NULLIF( %d, -1 ), availability ), '
				. ' duration = IFNULL( NULLIF( %s, "000.00:00:00" ), duration ) '
			. ' WHERE id = %d ';
	
	$variables = array( $data[ 'title' ], $data[ 'color' ], $data[ 'availability' ], $data[ 'duration' ], $data[ 'id' ] );
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );

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
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $activity_id
 * @return int|false
 */
function bookacti_deactivate_activity( $activity_id ) {
	global $wpdb;
	$query = ' UPDATE ' . BOOKACTI_TABLE_ACTIVITIES . ' SET active = 0 WHERE id = %d ';
	$query = $wpdb->prepare( $query, $activity_id );
	$deactivated = $wpdb->query( $query );
	return $deactivated;
}


/**
 * Deactivate all events of a specific activity from a specific template
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param int $activity_id
 * @param int $template_id
 * @return int|false
 */
function bookacti_deactivate_activity_events( $activity_id, $template_id = 0 ) {
	global $wpdb;
	
	$query = ' UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET active = 0 WHERE activity_id = %d ';
	$variables = array( $activity_id );
	
	if( $template_id ) { 
		$query .= ' AND template_id = %d ';
		$variables[] = $template_id;
	}
	
	$query = $wpdb->prepare( $query, $variables );
	$deactivated = $wpdb->query( $query );
	
	return $deactivated;
}




// TEMPLATES X ACTIVITIES ASSOCIATION

/**
 * Insert a template x activity association
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $activity_ids
 * @return int|false
 */
function bookacti_insert_templates_x_activities( $template_ids, $activity_ids ) {
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

	$query = $wpdb->prepare( $query, $variables_array );
	$inserted = $wpdb->query( $query );

	return $inserted;
}


/** 
 * Duplicate a template x activity association
 * @since 1.13.0
 * @global wpdb $wpdb
 * @param int $from_template_id
 * @param int $to_template_id
 * @return int|false
 */
function bookacti_duplicate_template_activities( $from_template_id, $to_template_id ) {
	global $wpdb;

	$query	= ' INSERT INTO ' . BOOKACTI_TABLE_TEMP_ACTI . ' ( template_id, activity_id ) '
			. ' SELECT %d, activity_id FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' WHERE template_id = %d ';
	$query	= $wpdb->prepare( $query, $to_template_id, $from_template_id );
	$duplicated = $wpdb->query( $query );
	
	return $duplicated;
}


/**
 * Delete a template x activity association
 * @version 1.12.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param array $activity_ids
 * @return int|false
 */
function bookacti_delete_templates_x_activities( $template_ids, $activity_ids ) {
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

	$query = $wpdb->prepare( $query, $variables_array );
	$deleted = $wpdb->query( $query );

	return $deleted;
}


/**
 * Get activities by template
 * @version 1.14.0
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
		$query = 'SELECT DISTINCT A.* FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
			   . ' WHERE A.id = E.activity_id AND E.template_id IN (';
	} else {
		$query = 'SELECT DISTINCT A.* FROM ' . BOOKACTI_TABLE_TEMP_ACTI . ' as TA, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
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

	$activities_meta     = bookacti_get_metadata( 'activity', $activity_ids );
	$activities_managers = $retrieve_managers ? bookacti_get_managers( 'activity', $activity_ids ) : array();

	$activities_array = array();
	foreach( $activities as $activity ) {
		$activity[ 'multilingual_title' ] = $activity[ 'title' ];
		$activity[ 'title' ]              = $activity[ 'title' ] ? apply_filters( 'bookacti_translate_text', $activity[ 'title' ] ) : '';
		
		$unit_name_singular = ! empty( $activity[ 'settings' ][ 'unit_name_singular' ] ) ? $activity[ 'settings' ][ 'unit_name_singular' ] : '';
		$unit_name_plural   = ! empty( $activity[ 'settings' ][ 'unit_name_plural' ] )   ? $activity[ 'settings' ][ 'unit_name_plural' ] : '';

		$activity[ 'settings' ] = isset( $activities_meta[ $activity[ 'id' ] ] ) ? $activities_meta[ $activity[ 'id' ] ] : array();
		$activity[ 'settings' ][ 'multilingual_unit_name_singular' ] = $unit_name_singular;
		$activity[ 'settings' ][ 'multilingual_unit_name_plural' ]   = $unit_name_plural;
		$activity[ 'settings' ][ 'unit_name_singular' ] = $unit_name_singular ? apply_filters( 'bookacti_translate_text', $unit_name_singular ) : '';
		$activity[ 'settings' ][ 'unit_name_plural' ]   = $unit_name_plural   ? apply_filters( 'bookacti_translate_text', $unit_name_plural ) : '';

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
 * @version 1.7.0
 * @global wpdb $wpdb
 * @param array $template_ids
 * @return array [ activity_id ][id, title, color, duration, availability, active, template_ids] where template_ids = [id, id, id, ...]
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