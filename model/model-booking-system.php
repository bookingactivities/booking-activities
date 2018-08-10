<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// EVENTS
	/**
	 * Fetch events by templates and / or activities
	 *
	 * @version 1.5.3
	 * 
	 * @param array $templates
	 * @param array $activities
	 * @param boolean $past_events
	 * @param array $interval array('start' => string: start date, 'end' => string: end date)
	 * @return array $events_array Array of events
	 */
    function bookacti_fetch_events( $templates = array(), $activities = array(), $past_events = false, $interval = array() ) {
		
		global $wpdb;
		
		// Set current datetime
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_datetime_object	= new DateTime( 'now', $timezone );
		$user_timestamp				= $current_datetime_object->format( 'U' );
		$user_timestamp_offset		= $current_datetime_object->format( 'P' );
		$variables					= array();
		
		// Prepare the query
		$query  = 'SELECT DISTINCT E.id as event_id, E.template_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.id as activity_id, 0 as is_resizable '
				. ' FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' WHERE E.activity_id = A.id '
				. ' AND E.template_id = T.id '
				. ' AND E.active = 1 '
				. ' AND A.active = 1 '
				. ' AND T.active = 1 ';

		// Do not fetch events out of their respective template limits
		$query  .= ' AND (	
							( 	NULLIF( E.repeat_freq, "none" ) IS NULL 
								AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) >= 
										UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, %s, @@global.time_zone ) ) 
									AND
										UNIX_TIMESTAMP( CONVERT_TZ( E.end, %s, @@global.time_zone ) ) <= 
										UNIX_TIMESTAMP( CONVERT_TZ( ( T.end_date + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
									) 
							) 
							OR
							( 	E.repeat_freq IS NOT NULL
								AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, %s, @@global.time_zone ) ) < 
											UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, %s, @@global.time_zone ) ) 
										AND 
											UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_to, %s, @@global.time_zone ) ) < 
											UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, %s, @@global.time_zone ) ) 
										)
								AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, %s, @@global.time_zone ) ) > 
											UNIX_TIMESTAMP( CONVERT_TZ( T.end_date, %s, @@global.time_zone ) ) 
										AND 
											UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_to, %s, @@global.time_zone ) ) > 
											UNIX_TIMESTAMP( CONVERT_TZ( T.end_date, %s, @@global.time_zone ) ) 
										)
							) 
						)';

		for( $i = 0; $i < 12; $i++ ) {
			$variables[] = $user_timestamp_offset;
		}
		
		// Do not fetch events out of the desired interval
		if( $interval ) {
			$query  .= ' 
			AND (
					( 	NULLIF( E.repeat_freq, "none" ) IS NULL 
						AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) >= 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
							AND
								UNIX_TIMESTAMP( CONVERT_TZ( E.end, %s, @@global.time_zone ) ) <= 
								UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
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
			$variables[] = $interval[ 'start' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'end' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'start' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'start' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'end' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'end' ];
			$variables[] = $user_timestamp_offset;
		}

		// Whether to fetch past events
		if( ! $past_events ) {

			$started_events_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
			
			$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, %s, @@global.time_zone ) ) >= %d 
							OR	UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) >= %d ';
			
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
			
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
		if( $templates ) {
			$query  .= ' AND E.template_id IN ( %d';
			for( $i=1,$len=count($templates); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $templates );
		}
		
		// Get events from desired activities only
		if( $activities ) {
			$query  .= ' AND A.id IN ( %d';
			for( $i=1,$len=count($activities); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $activities );
		}
		
		$query  .= ' ORDER BY E.start ASC ';
		
		
		// Apply variables to the query
		$query = apply_filters( 'bookacti_get_events_query', $wpdb->prepare( $query, $variables ), $templates, $activities, $past_events, $interval );
		
		// Get events complying with parameters
		$events = $wpdb->get_results( $query, OBJECT );
		
		// Transform raw events from database to array of individual events
		$events_array = bookacti_get_events_array_from_db_events( $events, $past_events, $interval );
		
		return apply_filters( 'bookacti_get_events', $events_array, $templates, $activities, $past_events, $interval, $query );
    }
	
	
	/**
	 * Fetch events by groups and / or categories of groups
	 * @version 1.5.3
	 * @global wpdb $wpdb
	 * @param array $templates
	 * @param array $activities
	 * @param array $groups
	 * @param array $categories
	 * @param boolean $past_events
	 * @param array $interval array('start' => string: start date, 'end' => string: end date)
	 * @return array
	 */
	function bookacti_fetch_grouped_events( $templates = array(), $activities = array(), $groups = array(), $categories = array(), $past_events = false, $interval = array() ) {
		
		global $wpdb;
		
		// Set current datetime
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_datetime_object	= new DateTime( 'now', $timezone );
		$user_timestamp				= $current_datetime_object->format( 'U' );
		$user_timestamp_offset		= $current_datetime_object->format( 'P' );
		$variables					= array();
		
		// Prepare the query
		$query  = 'SELECT DISTINCT GE.event_id, E.template_id, E.title, GE.event_start as start, GE.event_end as end, "none" as repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.id as activity_id, 0 as is_resizable '
				. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G, ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' WHERE GE.event_id = E.id '
				. ' AND E.activity_id = A.id '
				. ' AND E.template_id = T.id '
				. ' AND GE.group_id = G.id '
				. ' AND G.category_id = C.id '
				. ' AND GE.active = 1 '
				. ' AND E.active = 1 '
				. ' AND A.active = 1 '
				. ' AND T.active = 1 '
				. ' AND G.active = 1 '
				. ' AND C.active = 1 ';
		
		// Get events from desired templates only
		if( $templates ) {
			$query  .= ' AND E.template_id IN ( %d';
			for( $i=1,$len=count($templates); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $templates );
		}
		
		// Get events from desired activities only
		if( $activities ) {
			$query  .= ' AND A.id IN ( %d';
			for( $i=1,$len=count($activities); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $activities );
		}
		
		// Fetch events from desired groups only
		if( $groups ) {
			// Get the event only if it belongs to a group of the allowed categories
			$query .= ' AND GE.group_id IN ( %d';
			for( $i=1, $len=count($groups); $i < $len; ++$i ) {
				$query .= ', %d';
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $groups );
		}
		
		// Fetch events from desired categories only
		if( $categories ) {
			// Get the event only if it belongs to a group of the allowed categories
			$query .= ' AND G.category_id IN ( %d';
			for( $i=1, $len=count($categories); $i < $len; ++$i ) {
				$query .= ', %d';
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $categories );
		}
		
		// Do not fetch events out of their respective template limits
		$query  .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) >= 
							UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, %s, @@global.time_zone ) ) 
						AND
							UNIX_TIMESTAMP( CONVERT_TZ( GE.event_end, %s, @@global.time_zone ) ) <= 
							UNIX_TIMESTAMP( CONVERT_TZ( ( T.end_date + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
						) ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		
		// Do not fetch events out of the desired interval
		if( $interval ) {
			$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( GE.event_start, %s, @@global.time_zone ) ) >= 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
								AND 
								UNIX_TIMESTAMP( CONVERT_TZ( GE.event_end, %s, @@global.time_zone ) ) <= 
								UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
							)';
			
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'start' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'end' ];
			$variables[] = $user_timestamp_offset;
		}

		// Whether to fetch past events
		if( ! $past_events ) {

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
		
		$query  .= ' ORDER BY GE.event_start ASC ';
		
		// Apply variables to the query
		$query = apply_filters( 'bookacti_get_grouped_events_query', $wpdb->prepare( $query, $variables ), $templates, $activities, $groups, $categories, $past_events, $interval );
		
		// Get events complying with parameters
		$events = $wpdb->get_results( $query, OBJECT );

		// Transform raw events from database to array of individual events
		$events_array = bookacti_get_events_array_from_db_events( $events, $past_events, $interval );
		
		return apply_filters( 'bookacti_get_grouped_events', $events_array, $templates, $activities, $groups, $categories, $past_events, $interval, $query );
	}
	
	
	/**
	 * Fetch booked events only
	 * @since 1.2.2
	 * @version 1.5.3
	 * @global wpdb $wpdb
	 * @param array $templates
	 * @param array $activities
	 * @param array $booking_status
	 * @param int $user_id
	 * @param boolean $past_events
	 * @param array $interval array('start' => string: start date, 'end' => string: end date)
	 * @return array
	 */
	function bookacti_fetch_booked_events( $templates = array(), $activities = array(), $booking_status = array(), $user_id = 0, $past_events = false, $interval = array() ) {
		
		global $wpdb;
		
		// Set current datetime
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_datetime_object	= new DateTime( 'now', $timezone );
		$user_timestamp				= $current_datetime_object->format( 'U' );
		$user_timestamp_offset		= $current_datetime_object->format( 'P' );
		
		$variables					= array();
		
		// Prepare the query
		$query  = 'SELECT DISTINCT B.event_id, E.template_id, E.title, B.event_start as start, B.event_end as end, "none" as repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.id as activity_id, 0 as is_resizable '
				. ' FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' WHERE B.event_id = E.id '
				. ' AND E.activity_id = A.id '
				. ' AND E.template_id = T.id ';
		
		// Get events from desired templates only
		if( $templates ) {
			$query  .= ' AND E.template_id IN ( %d';
			for( $i=1,$len=count($templates); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $templates );
		}
		
		// Get events from desired activities only
		if( $activities ) {
			$query  .= ' AND A.id IN ( %d';
			for( $i=1,$len=count($activities); $i < $len; ++$i ) {
				$query  .= ', %d';
			}
			$query  .= ' ) ';
			$variables = array_merge( $variables, $activities );
		}
		
		// Fetch events from desired booking status only
		if( $booking_status ) {
			// Get the event only if it belongs to a group of the allowed categories
			$query .= ' AND B.state IN ( %s';
			for( $i=1, $len=count($booking_status); $i < $len; ++$i ) {
				$query .= ', %s';
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $booking_status );
		}
		
		// Filter bookings by user
		if( $user_id ) {
			$query .= ' AND B.user_id = %s ';
			$variables[] = $user_id ;
		}
		
		// Do not fetch events out of the desired interval
		if( $interval ) {
			$query .= ' AND (	UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= 
								UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) 
								AND 
								UNIX_TIMESTAMP( CONVERT_TZ( B.event_end, %s, @@global.time_zone ) ) <= 
								UNIX_TIMESTAMP( CONVERT_TZ( ( %s + INTERVAL 24 HOUR ), %s, @@global.time_zone ) ) 
							)';
			
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'start' ];
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp_offset;
			$variables[] = $interval[ 'end' ];
			$variables[] = $user_timestamp_offset;
		}

		// Whether to fetch past events
		if( ! $past_events ) {
			$query .= ' AND ( UNIX_TIMESTAMP( CONVERT_TZ( B.event_start, %s, @@global.time_zone ) ) >= %d ) ';
			
			$variables[] = $user_timestamp_offset;
			$variables[] = $user_timestamp;
		}
		
		$query  .= ' ORDER BY B.event_start ASC ';
		
		// Safely apply variables to the query
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
		$query = apply_filters( 'bookacti_get_booked_events_query', $query, $templates, $activities, $booking_status, $user_id, $past_events, $interval );
		
		// Get events complying with parameters
		$events = $wpdb->get_results( $query, OBJECT );

		// Transform raw events from database to array of individual events
		$events_array = bookacti_get_events_array_from_db_events( $events, $past_events, $interval );

		return apply_filters( 'bookacti_get_booked_events', $events_array, $templates, $activities, $booking_status, $user_id, $past_events, $interval, $query );
	}
	
	
	/**
	 * Get event by id
	 * 
	 * @version 1.2.2 
	 * @global wpdb $wpdb
	 * @param int $event_id
	 * @return object
	 */
	function bookacti_get_event_by_id( $event_id ) {
		global $wpdb;

		$query_event = 'SELECT E.id as event_id, E.template_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.is_resizable, A.id as activity_id ' 
						. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
						. ' WHERE E.activity_id = A.id '
						. ' AND E.id = %d';
		$prep_query_event = $wpdb->prepare( $query_event, $event_id );
		$event = $wpdb->get_row( $prep_query_event, OBJECT );
		
		return $event;
	}
	
	
	/**
	 * Check if a single event exists. For reapeating events, please use bookacti_is_existing_event.
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param int $event_id
	 * @param string $event_start
	 * @param string $event_end
	 * @return boolean
	 */
	function bookacti_is_existing_single_event( $event_id, $event_start = NULL, $event_end = NULL ) {
		global $wpdb;
		
		$query	= 'SELECT id FROM ' . BOOKACTI_TABLE_EVENTS
				. ' WHERE id = %d';
		
		$parameters = array( $event_id );
		
		if( ! empty( $event_start ) ) {
			$query	.= ' AND start = %s';
			$parameters[] = $event_start;
		}
		if( ! empty( $event_end ) ) {
			$query	.= ' AND end = %s';
			$parameters[] = $event_end;
		}
		
		$prep			= $wpdb->prepare( $query, $parameters );
		$event_exists	= $wpdb->get_var( $prep );
		
		$is_event = false;
		if( ! empty( $event_exists ) ) {
			$is_event = true;
		}
		
		return $is_event;
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
	
	
	// GET EVENT AND ACTIVITY SETTINGS
	function bookacti_get_settings_by_event( $event_id ) {
		
		$event = bookacti_get_event_by_id( $event_id );
		
		$settings[ 'event' ]	= bookacti_get_metadata( 'event', $event_id );
		$settings[ 'activity' ]	= bookacti_get_metadata( 'activity', $event->activity_id );
		
		return $settings;
	}
	
	
	/**
	 * Determine if an event or one of its occurrence is included in calendar range
	 *
	 * @since  1.0.6
	 * @version 1.4.0
	 * @param  int		$event_id		ID of the event to check
	 * @param  string	$event_start	Start datetime of the event to check (format 2017-12-31T23:59:59)
	 * @param  string	$event_end		End datetime of the event to check (format 2017-12-31T23:59:59)
	 * @return bool
	 */
	function bookacti_is_event_in_its_template_range( $event_id, $event_start, $event_end ) {
		// Sanitize params
		$event_id		= intval( $event_id );
		$event_start	= bookacti_sanitize_datetime( $event_start );
		$event_end		= bookacti_sanitize_datetime( $event_end );

		if( ! $event_id || ! $event_start || ! $event_end ) { return false; }
		
		global $wpdb;
		
		// Get template range in order to be compared with the event dates
		$query		= 'SELECT T.id, T.start_date as start, T.end_date as end FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
					. ' WHERE E.template_id = T.id '
					. ' AND E.id = %d ';
		$query		= $wpdb->prepare( $query, $event_id );
		$template	= $wpdb->get_row( $query, OBJECT );
		
		if( ! $template ) { return false; }
		
		$timezone		= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_time	= new DateTime( 'now', $timezone );
		
		$event_start_datetime		= new DateTime( $event_start, $timezone );
		$event_end_datetime			= new DateTime( $event_end, $timezone );
		
		$availability_period_start	= $template->start;
		$availability_period_end	= $template->end;
		
		// Restrict template interval if an availability period is set
		if( ! apply_filters( 'bookacti_bypass_availability_period_check', false ) ) {
			
			// Take default availability period if not set
			$template_settings = bookacti_get_metadata( 'template', $template->id );
			if( ! isset( $template_settings[ 'availability_period_start' ] ) || $template_settings[ 'availability_period_start' ] === '' || intval( $template_settings[ 'availability_period_start' ] ) < 0 )	{ $template_settings[ 'availability_period_start' ] = intval( bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_start' ) ); }
			if( ! isset( $template_settings[ 'availability_period_end' ] ) || $template_settings[ 'availability_period_end' ] === '' || intval( $template_settings[ 'availability_period_end' ] ) < 0 )			{ $template_settings[ 'availability_period_end' ] = intval( bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_end' ) ); }
			
			if( $template_settings[ 'availability_period_start' ] > 0 ) {
				$availability_start_time = clone $current_time;
				$availability_start_time->add( new DateInterval( 'P' . $template_settings[ 'availability_period_start' ] . 'D' ) );
				$availability_start_date = $availability_start_time->format( 'Y-m-d' );
				if( strtotime( $availability_start_date ) > strtotime( $template->start ) ) {
					$availability_period_start = $availability_start_date;
				}
			}
			if( $template_settings[ 'availability_period_end' ] > 0 ) {
				$availability_end_time = clone $current_time;
				$availability_end_time->add( new DateInterval( 'P' . $template_settings[ 'availability_period_end' ] . 'D' ) );
				$availability_end_date = $availability_end_time->format( 'Y-m-d' );
				if( strtotime( $availability_end_date ) < strtotime( $template->end ) ) {
					$availability_period_end = $availability_end_date;
				}
			}
		}
		
		$template_start_datetime	= new DateTime( $availability_period_start . ' 00:00:00', $timezone );
		$template_end_datetime		= new DateTime( $availability_period_end . ' 23:59:59', $timezone );
		
		if( $event_start_datetime >= $template_start_datetime 
		&&  $event_end_datetime   <= $template_end_datetime ) {
			return true;
		}
		
		return false;
	}


	
	
// EXCEPTIONS
	/**
	 * Get event repetition exceptions by templates or by events
	 * 
	 * @version 1.3.0
	 * @global wpdb $wpdb
	 * @param array $template_ids
	 * @param array $event_ids
	 * @return array
	 */
    function bookacti_get_exceptions( $template_ids = array(), $event_ids = array() ) {
		global $wpdb;
		
		// Convert numeric to array
		if( ! is_array( $template_ids ) ){
			$template_id = intval( $template_ids );
			$template_ids = array();
			if( $template_id ) { $template_ids[] = $template_id; }
		}
		if( ! is_array( $event_ids ) ){
			$event_id = intval( $event_ids );
			$event_ids = array();
			if( $event_id ) { $event_ids[] = $event_id; }
		}
		
		// No no template id and event id are given, retrieve all exceptions
		$variables = array();
		if( ! $template_ids && ! $event_ids ) {
			$excep_query = 'SELECT event_id, exception_type, exception_value FROM ' . BOOKACTI_TABLE_EXCEPTIONS . ' ORDER BY exception_value ASC ';
		
		// If event ids are given, retrieve exceptions for these events, regardless of template ids
		} else if ( $event_ids ) {
			$excep_query = 'SELECT event_id, exception_type, exception_value FROM ' . BOOKACTI_TABLE_EXCEPTIONS 
						. ' WHERE event_id IN ( ';
			
			$i = 1;
			foreach( $event_ids as $event_id ){
				$excep_query .= ' %d';
				if( $i < count( $event_ids ) ) { $excep_query .= ','; }
				++$i;
			}
			
			$excep_query .= ' ) ORDER BY exception_value ASC ';
			
			$variables = $event_ids;
			
		// If template ids are given, retrieve event exceptions from these templates
		} else if ( $template_ids ) {
			$excep_query = 'SELECT X.event_id, X.exception_type, X.exception_value '
						. ' FROM '  . BOOKACTI_TABLE_EXCEPTIONS . ' as X, '
									. BOOKACTI_TABLE_EVENTS . ' as E '
						. ' WHERE X.event_id = E.id '
						. ' AND E.template_id IN ( ';
			
			$i = 1;
			foreach( $template_ids as $template_id ){
				$excep_query .= ' %d';
				if( $i < count( $template_ids ) ) { $excep_query .= ','; }
				++$i;
			}
			
			$excep_query .= ' ) ORDER BY exception_value ASC ';
			
			$variables = $template_ids;
		}
		
		if( $variables ) {
			$excep_query = $wpdb->prepare( $excep_query, $variables );
		}
		
		$exceptions = $wpdb->get_results( $excep_query, ARRAY_A );
		
		// Order exceptions by event id
		$exceptions_array = array();
		if( $exceptions ) {
			foreach( $exceptions as $exception ) {
				$event_id = $exception[ 'event_id' ];
				unset( $exception[ 'event_id' ] );
				if( ! isset( $exceptions_array[ $event_id ] ) ) {
					$exceptions_array[ $event_id ] = array();
				}
				$exceptions_array[ $event_id ][] = $exception;
			}
		}
		
		return $exceptions_array;
    }


	/**
	 * Check if a date is a repeat exception of a given event
	 * 
	 * @global wpdb $wpdb
	 * @param int $event_id
	 * @param string $date Format "YYYY-MM-DD".
	 * @return int
	 */
    function bookacti_is_repeat_exception( $event_id, $date ) {
        global $wpdb;
        
        // Check if the date exists in exceptions database for this event
        $is_excep_query = 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_EXCEPTIONS
						. ' WHERE exception_value = %s'
						. ' AND event_id = %d';
        $is_excep_prep = $wpdb->prepare( $is_excep_query, $date, $event_id );
        $is_excep = $wpdb->get_var( $is_excep_prep );

        return $is_excep;
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
						WHERE active = 1 
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
	 * 
	 * @since 1.4.0 (was bookacti_get_groups_of_events_by_template and bookacti_get_groups_of_events_by_category
	 * @version 1.5.0
	 * @global wpdb $wpdb
	 * @param array|int $template_ids
	 * @param array|int $category_ids
	 * @param boolean $fetch_past_groups
	 * @param boolean $fetch_inactive_groups
	 * @param array $templates_data
	 * @return array
	 */
	function bookacti_get_groups_of_events( $template_ids = array(), $category_ids = array(), $fetch_past_groups = false, $fetch_inactive_groups = false, $templates_data = array() ) {
		
		// If empty, take them all
		if( ! $template_ids ) { $template_ids = array(); }
		if( ! $category_ids ) { $category_ids = array(); }
		
		// Convert numeric to array
		if( is_numeric( $template_ids ) )	{ $template_ids = array( intval( $template_ids ) ); }
		if( ! is_array( $template_ids ) )	{ $template_ids = array(); }
		if( is_numeric( $category_ids ) )	{ $category_ids = array( intval( $category_ids ) ); }
		if( ! is_array( $category_ids ) )	{ $category_ids = array(); }
		if( ! $templates_data )				{ $templates_data = bookacti_get_mixed_template_data( $template_ids, $fetch_past_groups ); }
		
		$started_groups_bookable	= bookacti_get_setting_value( 'bookacti_general_settings', 'started_groups_bookable' );
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_datetime_object	= new DateTime( 'now', $timezone );
		$user_timestamp				= $current_datetime_object->format( 'U' );
		$user_timestamp_offset		= $current_datetime_object->format( 'P' );
		
		global $wpdb;
		
		$variables = array();
		
		$query	= 'SELECT G.*, GE.start, GE.end '
				. ' FROM ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G ' 
				. ' JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C '
				. ' JOIN ' . BOOKACTI_TABLE_TEMPLATES . ' as T ';
		
		// Join the meta table to filter groups already started
		$query .= ' LEFT JOIN ( 
						SELECT object_id as group_category_id, IFNULL( NULLIF( meta_value, -1 ), %d ) as started_groups_bookable
						FROM ' . BOOKACTI_TABLE_META . ' 
						WHERE object_type = "group_category" 
						AND meta_key = "started_groups_bookable" 
					) as M ON M.group_category_id = C.id ';
		
		$variables[] = $started_groups_bookable;
		
		// Join the groups events table to filter groups already started
		$query .= ' LEFT JOIN ( 
						SELECT group_id, MIN( event_start ) as start, MAX( event_end ) as end
						FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' 
						WHERE active = 1 
						GROUP BY group_id
					) as GE ON GE.group_id = G.id ';
		
		$query .= ' WHERE C.id = G.category_id '
				. ' AND C.template_id = T.id '
				. ' AND GE.start IS NOT NULL '
				. ' AND GE.end IS NOT NULL ';
		
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
		
		if( $category_ids ) {
			$query .= ' AND G.category_id IN ( %d ';
			$array_count = count( $category_ids );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d ';
				}
			}
			$query .= ') ';
			$variables = array_merge( $variables, $category_ids );
		}
		
		// Make sure groups are in their template range
		$query .= ' AND UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) >= UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, %s, @@global.time_zone ) ) ';
		$query .= ' AND UNIX_TIMESTAMP( CONVERT_TZ( GE.end, %s, @@global.time_zone ) ) <= UNIX_TIMESTAMP( CONVERT_TZ( T.end_date + INTERVAL 24 HOUR, %s, @@global.time_zone ) ) ';
		
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		$variables[] = $user_timestamp_offset;
		
		// Do not get a group of events if its events are not fully included in the availability period
		// Also check if started groups of events are to be retrieved or not
		$availability_period_start	= $templates_data[ 'start' ];
		$availability_period_end	= $templates_data[ 'end' ];

		if( ! apply_filters( 'bookacti_bypass_availability_period_check', false ) ) {
			if( ! empty( $templates_data[ 'settings' ][ 'availability_period_start' ] ) 
				&& $templates_data[ 'settings' ][ 'availability_period_start' ] > 0 ) {
				$availability_start_time = clone $current_datetime_object;
				$availability_start_time->add( new DateInterval( 'P' . $templates_data[ 'settings' ][ 'availability_period_start' ] . 'D' ) );
				$availability_start_date = $availability_start_time->format( 'Y-m-d' );
				if( strtotime( $availability_start_date ) > strtotime( $templates_data[ 'start' ] ) ) {
					$availability_period_start = $availability_start_date;
				}
			}
			if( ! empty( $templates_data[ 'settings' ][ 'availability_period_end' ] ) 
				&& $templates_data[ 'settings' ][ 'availability_period_end' ] > 0 ) {
				$availability_end_time = clone $current_datetime_object;
				$availability_end_time->add( new DateInterval( 'P' . $templates_data[ 'settings' ][ 'availability_period_end' ] . 'D' ) );
				$availability_end_date = $availability_end_time->format( 'Y-m-d' );
				if( strtotime( $availability_end_date ) < strtotime( $templates_data[ 'end' ] ) ) {
					$availability_period_end = $availability_end_date;
				}
			}
		}
		
		$past_query = ' UNIX_TIMESTAMP( CONVERT_TZ( GE.start, %s, @@global.time_zone ) ) >= UNIX_TIMESTAMP( CONVERT_TZ( %s, %s, @@global.time_zone ) ) ';
		if( ! $fetch_past_groups ) {
			$past_query = ' ( M.started_groups_bookable = 1 ' . ' OR  ' . $past_query . ' ) ';
		}
		
		$query .= ' AND ' . $past_query 
				. ' AND UNIX_TIMESTAMP( CONVERT_TZ( GE.end, %s, @@global.time_zone ) ) <= UNIX_TIMESTAMP( CONVERT_TZ( %s + INTERVAL 24 HOUR, %s, @@global.time_zone ) ) ';

		$variables[] = $user_timestamp_offset;
		$variables[] = $availability_period_start;
		$variables[] = $user_timestamp_offset;
		
		$variables[] = $user_timestamp_offset;
		$variables[] = $availability_period_end;
		$variables[] = $user_timestamp_offset;
		
		if( ! $fetch_inactive_groups ) {
			$query .= ' AND G.active = 1 ';
		}
		
		$query .= ' ORDER BY G.category_id ';
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
		
        $groups	= $wpdb->get_results( $query, ARRAY_A );
		
		$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
		$groups_data = array();
		foreach( $groups as $group ) {
			
			$group_id = $group[ 'id' ];
			
			// Translate title
			$group[ 'multilingual_title' ]	= $group[ 'title' ];
			$group[ 'title' ]				= apply_filters( 'bookacti_translate_text', $group[ 'title' ] );
			
			// Add metadata
			$group[ 'settings' ] = bookacti_get_metadata( 'group_of_events', $group_id );
			
			// Add info about booking per users
			$quantity_per_user = bookacti_get_number_of_bookings_per_user_by_group_of_events( $group_id );
			$group[ 'distinct_users' ]			= count( $quantity_per_user );
			$group[ 'current_user_bookings' ]	= $current_user_id && isset( $quantity_per_user[ $current_user_id ] ) ? $quantity_per_user[ $current_user_id ] : 0;
			$group[ 'availability' ]			= bookacti_get_group_of_events_availability( $group_id );
			
			$groups_data[ $group_id ] = $group;
		}
		
        return $groups_data;
	}

	/**
	 * Get group of events availability (= the lowest availability among its events)
	 * 
	 * @global wpdb $wpdb
	 * @since 1.1.0
	 * @version 1.4.0
	 * @param int $event_group_id
	 * @return false|int
	 */
	function bookacti_get_group_of_events_availability( $event_group_id ) {
		
		global $wpdb;
		
		$query = 'SELECT MIN( E.availability - IFNULL( B.quantity_booked, 0 ) ) as availability '
				. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE '
				. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' LEFT JOIN ( '
					. ' SELECT event_id, event_start, event_end, SUM( quantity ) as quantity_booked FROM ' . BOOKACTI_TABLE_BOOKINGS 
					. ' WHERE active = 1 '
					. ' GROUP BY CONCAT( event_id, event_start, event_end ) '
				. ' ) as B ON B.event_id = GE.event_id AND B.event_start = GE.event_start AND B.event_end = GE.event_end '
				. ' WHERE GE.group_id = %d '
				. ' AND GE.event_id = E.id ';
		
		$query = $wpdb->prepare( $query, $event_group_id );
		$availability = $wpdb->get_var( $query );
		
		return $availability;
	}
	
	


// GROUPS X EVENTS
	
	/**
	 * Get the groups events belonging to a template, a category or / and a group, ordered by group
	 * 
	 * @since 1.1.0
	 * @version 1.3.0
	 * 
	 * @global wpdb $wpdb
	 * @param array $template_ids
	 * @param array $category_ids
	 * @param array $group_ids
	 * @param boolean $fetch_inactive_events
	 * @return array
	 */
	function bookacti_get_groups_events( $template_ids = array(), $category_ids = array(), $group_ids = array(), $fetch_inactive_events = false ) {
		global $wpdb;
        
		// Convert numeric to array
		if( ! is_array( $template_ids ) ){
			$template_id = intval( $template_ids );
			$template_ids = array();
			if( $template_id ) {
				$template_ids[] = $template_id;
			}
		}
		if( ! is_array( $category_ids ) ){
			$category_id = intval( $category_ids );
			$category_ids = array();
			if( $category_id ) {
				$category_ids[] = $category_id;
			}
		}
		if( ! is_array( $group_ids ) ){
			$group_id = intval( $group_ids );
			$group_ids = array();
			if( $group_id ) {
				$group_ids[] = $group_id;
			}
		}
		
        $query  = 'SELECT GE.group_id, GE.event_id as id, GE.event_start as start, GE.event_end as end, GE.active, E.activity_id, E.title, G.category_id, C.template_id, E.availability, IFNULL( B.bookings, 0 ) as bookings, IFNULL( BG.bookings, 0 ) as group_bookings '
				. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE ' 
				. ' JOIN ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G ON G.id = GE.group_id ' 
				. ' JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON GE.event_id = E.id ' 
				. ' JOIN ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ON G.category_id = C.id ' 
				. ' LEFT JOIN (
						SELECT SUM( quantity ) as bookings, event_id, event_start, event_end 
						FROM ' . BOOKACTI_TABLE_BOOKINGS . ' 
						WHERE active = 1
						GROUP BY CONCAT( event_id, event_start, event_end ) 
					) as B ON B.event_id = GE.event_id AND B.event_start = GE.event_start AND B.event_end = GE.event_end '
				. ' LEFT JOIN (
						SELECT SUM( B.quantity ) as bookings, G.event_group_id, B.event_id, B.event_start, B.event_end 
						FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B, ' . BOOKACTI_TABLE_BOOKING_GROUPS . ' as G
						WHERE B.active = 1
						AND B.group_id = G.id
						GROUP BY CONCAT( G.event_group_id, B.event_id, B.event_start, B.event_end ) 
					) as BG ON BG.event_group_id = GE.group_id AND BG.event_id = GE.event_id AND BG.event_start = GE.event_start AND BG.event_end = GE.event_end '
				. ' WHERE GE.group_id IS NOT NULL ';
		
		$variables = array();
		
		// Filter by template ids
		if( ! empty( $template_ids ) ) {
			$query .= ' AND C.template_id IN (';
			$i = 1;
			foreach( $template_ids as $template_id ){
				$query .= ' %d';
				$variables[] = $template_id;
				if( $i < count( $template_ids ) ) { $query .= ','; }
				$i++;
			}
			$query .= ' ) ';
		}
		
		// Filter by category ids
		if( ! empty( $category_ids ) ) {
			$query .= ' AND C.id IN (';
			$i = 1;
			foreach( $category_ids as $category_id ){
				$query .= ' %d';
				$variables[] = $category_id;
				if( $i < count( $category_ids ) ) { $query .= ','; }
				$i++;
			}
			$query .= ' ) ';
		}
		
		// Filter by group ids
		if( ! empty( $group_ids ) ) {
			$query .= ' AND G.id IN (';
			$i = 1;
			foreach( $group_ids as $group_id ){
				$query .= ' %d';
				$variables[] = $group_id;
				if( $i < count( $group_ids ) ) { $query .= ','; }
				$i++;
			}
			$query .= ' ) ';
		}
		
		// Filter inactive events
		if( ! $fetch_inactive_events ) {
			$query .= ' AND GE.active = 1 ';
			$query .= ' AND E.active = 1 ';
		}
		
		$query .= ' ORDER BY GE.group_id, GE.event_start ';
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
		
        $events = $wpdb->get_results( $query, ARRAY_A );
		
		// Order by groups
		$groups_events = array();
		foreach( $events as $event ) {
			
			$group_id = $event[ 'group_id' ];
			
			if( ! isset( $groups_events[ $group_id ] ) ) {
				$groups_events[ $group_id ]	= array();
			}
			
			// Translate title
			$event[ 'title' ] = apply_filters( 'bookacti_translate_text', $event[ 'title' ] );
			
			$groups_events[ $group_id ][] = $event;
		}
		
		return $groups_events;
	}
	
	
	/**
	 * Determine if a group of events is totally included in calendar range
	 *
	 * @since 1.1.0
	 * @version 1.4.0
	 * @param int $group_id
	 * @return bool
	 */
	function bookacti_is_group_of_events_in_its_template_range( $group_id ) {
		
		// Sanitize params
		$group_id = intval( $group_id );

		if( ! $group_id ) { return false; }
		
		global $wpdb;
		
		// Get template range in order to be compared with the event dates
		$range_query	= 'SELECT T.id, T.start_date as start, T.end_date as end '
						. ' FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G, ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C '
						. ' WHERE C.template_id = T.id '
						. ' AND G.category_id = C.id '
						. ' AND G.id = %d ';
		$range_prepare	= $wpdb->prepare( $range_query, $group_id );
		$template		= $wpdb->get_row( $range_prepare, OBJECT );
		
		if( ! $template ){ return false; }
		
		$availability_period_start	= $template->start;
		$availability_period_end	= $template->end;
		
		$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		
		// Restrict template interval if an availability period is set
		if( ! apply_filters( 'bookacti_bypass_availability_period_check', false ) ) {
		
			$current_time = new DateTime( 'now', $timezone );
			
			// Take default availability period if not set
			$template_settings = bookacti_get_metadata( 'template', $template->id );
			if( ! isset( $template_settings[ 'availability_period_start' ] ) || $template_settings[ 'availability_period_start' ] === '' || intval( $template_settings[ 'availability_period_start' ] ) < 0 )	{ $template_settings[ 'availability_period_start' ] = intval( bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_start' ) ); }
			if( ! isset( $template_settings[ 'availability_period_end' ] ) || $template_settings[ 'availability_period_end' ] === '' || intval( $template_settings[ 'availability_period_end' ] ) < 0 )			{ $template_settings[ 'availability_period_end' ] = intval( bookacti_get_setting_value( 'bookacti_general_settings', 'availability_period_end' ) ); }
			
			if( $template_settings[ 'availability_period_start' ] > 0 ) {
				$availability_start_time = clone $current_time;
				$availability_start_time->add( new DateInterval( 'P' . $template_settings[ 'availability_period_start' ] . 'D' ) );
				$availability_start_date = $availability_start_time->format( 'Y-m-d' );
				if( strtotime( $availability_start_date ) > strtotime( $template->start ) ) {
					$availability_period_start = $availability_start_date;
				}
			}
			if( $template_settings[ 'availability_period_end' ] > 0 ) {
				$availability_end_time = clone $current_time;
				$availability_end_time->add( new DateInterval( 'P' . $template_settings[ 'availability_period_end' ] . 'D' ) );
				$availability_end_date = $availability_end_time->format( 'Y-m-d' );
				if( strtotime( $availability_end_date ) < strtotime( $template->end ) ) {
					$availability_period_end = $availability_end_date;
				}
			}
		}
		
		// Get the first and the last event of the group and keep respectively their start and end datetime
		$events_range_query		= 'SELECT MIN( event_start ) as start, MAX( event_end ) as end '
								. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS
								. ' WHERE group_id = %d ';
		$events_range_prepare	= $wpdb->prepare( $events_range_query, $group_id );
		$events_range			= $wpdb->get_row( $events_range_prepare, OBJECT );
		
		$event_start_datetime		= new DateTime( $events_range->start, $timezone );
		$event_end_datetime			= new DateTime( $events_range->end, $timezone );
		$template_start_datetime	= new DateTime( $availability_period_start . ' 00:00:00', $timezone );
		$template_end_datetime		= new DateTime( $availability_period_end . ' 00:00:00', $timezone );
		$template_end_datetime->add( new DateInterval( 'P1D' ) );
		
		if( $event_start_datetime >= $template_start_datetime 
		&&  $event_end_datetime   <= $template_end_datetime ) {
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Get groups of an event
	 * 
	 * @param int $id
	 * @param string $start
	 * @param string $end
	 * @param boolean $active_only Whether to get the group of events even if the link between the desired event and this group is inactive
	 */
	function bookacti_get_event_groups( $id, $start, $end, $active_only = true ) {
		
		global $wpdb;
		
		$query	= ' SELECT GE.group_id, G.category_id '
				. ' FROM ' . BOOKACTI_TABLE_GROUPS_EVENTS . ' as GE, ' . BOOKACTI_TABLE_EVENT_GROUPS . ' as G '
				. ' WHERE GE.group_id = G.id '
				. ' AND GE.event_id = %d '
				. ' AND GE.event_start = %s '
				. ' AND GE.event_end = %s ';
		
		if( $active_only ) {
			$query	.= ' AND GE.active = 1 ';
		}
		
		$prep = $wpdb->prepare( $query, $id, $start, $end );
		$groups = $wpdb->get_results( $prep, OBJECT );
				
		return $groups;
	}
	
	
	
	
// GROUP CATEGORIES
	
	/**
	 * Retrieve group categories data by id
	 * 
	 * @since 1.1.0
	 * @version 1.5.7
	 * 
	 * @global wpdb $wpdb
	 * @param array|int $template_ids
	 * @param array|int $category_ids
	 * @param boolean $fetch_inactive
	 * @return array|boolean
	 */
	function bookacti_get_group_categories( $template_ids = array(), $category_ids = array(), $fetch_inactive = false ) {
		
		// If empty, take them all
		if( ! $template_ids ) { $template_ids = array(); }
		if( ! $category_ids ) { $category_ids = array(); }
		
		// Convert numeric to array
		if( is_numeric( $template_ids ) ) { $template_ids = array( intval( $template_ids ) ); }
		if( ! is_array( $template_ids ) ) { $template_ids = array(); }
		if( is_numeric( $category_ids ) ) { $category_ids = array( intval( $category_ids ) ); }
		if( ! is_array( $category_ids ) ) { $category_ids = array(); }
		
		global $wpdb;
		
        $query	= 'SELECT * FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C '
				. ' WHERE TRUE ';
		
		$variables = array();
		
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
		
		if( $category_ids ) {
			$query .= ' AND C.id IN ( %d ';
			$array_count = count( $category_ids );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d ';
				}
			}
			$query .= ') ';
			$variables = array_merge( $variables, $category_ids );
		}
		
		if( ! $fetch_inactive ) {
			$query .= ' AND active = 1 ';
		}
		
		$query .= ' ORDER BY C.template_id ';
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
        
        $categories = $wpdb->get_results( $query, ARRAY_A );
		
		$categories_data = array();
		foreach( $categories as $category ) {
			
			$category_id = $category[ 'id' ];
			
			// Translate title
			$category[ 'multilingual_title' ]	= $category[ 'title' ];
			$category[ 'title' ]				= apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
			
			// Add metadata
			$category[ 'settings' ] = bookacti_get_metadata( 'group_category', $category_id );
			
			$categories_data[ $category_id ] = $category;
		}
		
        return $categories_data;
	}
	
	
	/**
	 * Retrieve group category ids by template ids
	 * 
	 * @since 1.1.0
	 * @version 1.4.0
	 * 
	 * @global wpdb $wpdb
	 * @param array|int $template_ids
	 * @param boolean $fetch_inactive
	 * @param boolean $allowed_roles_only
	 * @return array|boolean
	 */
	function bookacti_get_group_category_ids_by_template( $template_ids = array(), $fetch_inactive = false, $allowed_roles_only = false ) {
		
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
		
		$variables = array();
		
		global $wpdb;
		
        $query	= 'SELECT id FROM ' . BOOKACTI_TABLE_GROUP_CATEGORIES . ' as C ';
		
		// Join the meta table to filter by roles
		if( $allowed_roles_only ) {
			$query .= ' LEFT JOIN ( 
							SELECT meta_value as roles, object_id as group_category_id 
							FROM ' . BOOKACTI_TABLE_META . ' 
							WHERE object_type = "group_category" 
							AND meta_key = "allowed_roles" 
						) as M ON M.group_category_id = C.id ';
		}
		
		$query .= ' WHERE TRUE ';
		
		// Filter by roles
		if( $allowed_roles_only ) {
			$current_user	= wp_get_current_user();
			$roles			= $current_user->roles;
			
			$query .= ' AND ( ( M.roles = "a:0:{}" OR M.roles IS NULL OR M.roles = "" ) ';
			if( $roles ) {
				foreach( $roles as $i => $role ) {
					$query .= ' OR M.roles LIKE %s ';
					// Prefix and suffix each element of the array
					$roles[ $i ] = '%' . $wpdb->esc_like( $role ) . '%';
				}
				$variables = array_merge( $variables, $roles );
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
		
		if( ! $fetch_inactive ) {
			$query .= ' AND C.active = 1 ';
		}
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables );
		}
        $categories = $wpdb->get_results( $query, OBJECT );
		
		$category_ids = array();
		foreach( $categories as $category ) {
			$category_ids[] = $category->id;
		}
		
        return $category_ids;
	}
	
	
	
	
// TEMPLATES
	/**
	 * Get the mixed range (start and end dates) of a group of template
	 *
	 * @since  1.0.6
	 * @version  1.3.0
	 * @param  array $template_ids Array of template ids
	 * @return array (start, end)
	 */
	function bookacti_get_mixed_template_range( $template_ids = array() ) {
		
		if( is_numeric( $template_ids ) ) {
			$template_ids = array( $template_ids );
		}
		
		if( ! is_array( $template_ids ) ) {
			return false;
		}
		
		global $wpdb;
		
		$range_query = 'SELECT MIN( start_date ) as start, MAX( end_date ) as end '
					 . ' FROM ' . BOOKACTI_TABLE_TEMPLATES
					 . ' WHERE active = 1 ';
		
		$variables = array();
		
		// If templates ids were given, search only in those templates
		if( $template_ids ) {
			$range_query .= ' AND id IN ( %d';
			for( $i=1, $len=count($template_ids); $i < $len; ++$i ) {
				$range_query  .= ', %d';
			}
			$range_query .= ')';
			
			$variables = $template_ids;
		}
		
		if( $template_ids ) {
			$range_query = $wpdb->prepare( $range_query, $template_ids );
		}
		
		$range = $wpdb->get_row( $range_query, ARRAY_A );
		
		return $range;
	}
	
	
	
// PERMISSIONS
	/**
	 * Get managers
	 * 
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $object_id
	 * @return array
	 */
	function bookacti_get_managers( $object_type, $object_id ) {
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $object_id ) ) {
			return false;
		}
		
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
		
		$query_get_managers = 'SELECT user_id FROM ' . BOOKACTI_TABLE_PERMISSIONS
							. ' WHERE object_type = %s'
							. ' AND object_id = %d';
		
		$query_prep	= $wpdb->prepare( $query_get_managers, $object_type, $object_id );
		$managers = $wpdb->get_results( $query_prep, OBJECT );
		
		$managers_array = array();
		foreach( $managers as $manager ) {
			$managers_array[] = intval( $manager->user_id );
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
	
	
	
	
// METADATA
	/**
	 * Get metadata
	 * 
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $object_id
	 * @param string $meta_key
	 * @param boolean $single
	 * @return mixed
	 */
	function bookacti_get_metadata( $object_type, $object_id, $meta_key = '', $single = false ) {
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
		
		$query_get_meta = 'SELECT meta_key, meta_value FROM ' . BOOKACTI_TABLE_META
						. ' WHERE object_type = %s'
						. ' AND object_id = %d';
		
		$variables_array = array( $object_type, $object_id );
		
		if( $meta_key !== '' ) {
			$query_get_meta .= ' AND meta_key = %s';
			$variables_array[] = $meta_key;
		}
		
		$query_prep = $wpdb->prepare( $query_get_meta, $variables_array );
		
		if( $single ) {
			$metadata = $wpdb->get_row( $query_prep, OBJECT );
			return isset( $metadata->meta_value ) ? maybe_unserialize( $metadata->meta_value ) : false;
		}
		
		$metadata = $wpdb->get_results( $query_prep, OBJECT );
		
		if( is_null( $metadata ) ) { 
			return false; 
		}
		
		$metadata_array = array();
		foreach( $metadata as $metadata_pair ) {
			$metadata_array[ $metadata_pair->meta_key ] = maybe_unserialize( $metadata_pair->meta_value );
		}
		
		return $metadata_array;
	}
	
	
	/**
	 * Update metadata
	 * 
	 * @version 1.3.2
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $object_id
	 * @param array $metadata_array
	 * @return int|false
	 */
	function bookacti_update_metadata( $object_type, $object_id, $metadata_array ) {
		
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $metadata_array ) ) {
			return false;
		}
		
		if ( is_array( $metadata_array ) && empty( $metadata_array ) ) {
			return 0;
		}
		
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
		
		$current_metadata = bookacti_get_metadata( $object_type, $object_id );
		
		// INSERT NEW METADATA
		$inserted =  0;
		$new_metadata = array_diff_key( $metadata_array, $current_metadata );
		if( ! empty( $new_metadata ) ) {
			$inserted = bookacti_insert_metadata( $object_type, $object_id, $new_metadata );
		}
		
		// UPDATE EXISTING METADATA
		$updated = 0;
		$existing_metadata = array_intersect_key( $metadata_array, $current_metadata );
		if( ! empty( $existing_metadata ) ) {
			$update_metadata_query		= 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = ';
			$update_metadata_query_end	= ' WHERE object_type = %s AND object_id = %d AND meta_key = %s;';

			foreach( $existing_metadata as $meta_key => $meta_value ) {

				$update_metadata_query_n = $update_metadata_query;

				if( is_int( $meta_value ) )			{ $update_metadata_query_n .= '%d'; }
				else if( is_float( $meta_value ) )	{ $update_metadata_query_n .= '%f'; }
				else								{ $update_metadata_query_n .= '%s'; }

				$update_metadata_query_n .= $update_metadata_query_end;

				$update_variables_array = array( maybe_serialize( $meta_value ), $object_type, $object_id, $meta_key );

				$update_query_prep = $wpdb->prepare( $update_metadata_query_n, $update_variables_array );
				$updated_n = $wpdb->query( $update_query_prep );

				if( is_int( $updated_n ) && is_int( $updated ) ) {
					$updated += $updated_n;
				} else if( $updated_n === false ) {
					$updated = false;
				}
			}
		}
		
		if( is_int( $inserted ) && is_int( $updated ) ) {
			return $inserted + $updated;
		}
		
		return false;
	}


	/**
	 * Insert metadata
	 * 
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $object_id
	 * @param array $metadata_array
	 * @return int|boolean
	 */
	function bookacti_insert_metadata( $object_type, $object_id, $metadata_array ) {
		
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $metadata_array ) || empty( $metadata_array ) ) {
			return false;
		}
		
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
		
		$insert_metadata_query = 'INSERT INTO ' . BOOKACTI_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) VALUES ';
		$insert_variables_array = array();
		$i = 0;
		foreach( $metadata_array as $meta_key => $meta_value ) {
			$insert_metadata_query .= '( %s, %d, %s, ';
			
			if( is_int( $meta_value ) )			{ $insert_metadata_query .= '%d'; }
			else if( is_float( $meta_value ) )	{ $insert_metadata_query .= '%f'; }
			else								{ $insert_metadata_query .= '%s'; }
			
			if( ++$i === count( $metadata_array ) ) {
				$insert_metadata_query .= ' );';
			} else {
				$insert_metadata_query .= ' ), ';
			}
			$insert_variables_array[] = $object_type;
			$insert_variables_array[] = $object_id;
			$insert_variables_array[] = $meta_key;
			$insert_variables_array[] = maybe_serialize( $meta_value );
		}
		
		$insert_query_prep = $wpdb->prepare( $insert_metadata_query, $insert_variables_array );
		$inserted = $wpdb->query( $insert_query_prep );
		
		return $inserted;
	}
	
	
	/**
	 * Duplicate metadata
	 * 
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $source_id
	 * @param int $recipient_id
	 * @return int|boolean
	 */
	function bookacti_duplicate_metadata( $object_type, $source_id, $recipient_id ) {
	
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $source_id ) || ! is_numeric( $recipient_id ) ) {
			return false;
		}
		
		$source_id		= absint( $source_id );
		$recipient_id	= absint( $recipient_id );
		if ( ! $source_id || ! $recipient_id ) {
			return false;
		}
		
		$query		= 'INSERT INTO ' . BOOKACTI_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) '
					. ' SELECT object_type, %d, meta_key, meta_value '
					. ' FROM ' . BOOKACTI_TABLE_META
					. ' WHERE object_type = %s ' 
					. ' AND object_id = %d';
		$query_prep	= $wpdb->prepare( $query, $recipient_id, $object_type, $source_id );
		$inserted	= $wpdb->query( $query_prep );
		
		return $inserted;
	}
	
	
	/**
	 * Delete metadata
	 * @version 1.5.0
	 * @global wpdb $wpdb
	 * @param string $object_type
	 * @param int $object_id
	 * @param array $metadata_key_array Array of metadata keys to delete. Leave it empty to delete all metadata of the desired object.
	 * @return int|boolean
	 */
	function bookacti_delete_metadata( $object_type, $object_id, $metadata_key_array = array() ) {
		global $wpdb;
		
		if( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $metadata_key_array ) ) { return false; }
		
		$object_id = absint( $object_id );
		if( ! $object_id ) { return false; }
	
		$query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = %s AND object_id = %d ';
		
		$variables = array( $object_type, $object_id );
		
		if( $metadata_key_array ) {
			$query .= ' AND meta_key IN( %s';
			for( $i=1,$len=count($metadata_key_array); $i < $len; ++$i ) {
				$query .= ', %s';
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, array_values( $metadata_key_array ) );
		}
		$query = $wpdb->prepare( $query, $variables );
		$deleted = $wpdb->query( $query );
		
		return $deleted;
	}