<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// EVENTS
	/**
	 * Fetch events by templates and / or activities
	 *
	 * @since	1.0.0
	 * @version 1.0.7
	 * @param	array		$activities				Array of activity ids
	 * @param	array		$templates				Array of templates ids
	 * @param	DateTime	$user_datetime_object	User current DateTime
	 * @param	bool			$fetch_past_events		Whether to fetch events occuring before user datetime
	 * @return	array		$events_array			Array of events matching the parameters
	 */
    function bookacti_fetch_calendar_events( $activities = array(), $templates = array(), $user_datetime_object = null, $fetch_past_events = false, $context = 'frontend' ) {
        
        global $wpdb;
        
		if( is_null( $activities ) )			{ $activities = array(); }
        if( is_null( $templates ) )				{ $templates = array(); }
        if( is_null( $user_datetime_object ) )	{ $user_datetime_object = new DateTime(); $user_datetime_object->setTimezone( new DateTimeZone( 'UTC' ) ); }
		$user_timestamp	= $user_datetime_object->format( 'U' );
		
        // Prepare the query
        $query  = 'SELECT DISTINCT E.id as event_id, E.template_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.id as activity_id, IFNULL( B.bookings, 0 ) as bookings '
                . ' FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' as A, ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
				. ' LEFT JOIN (
						SELECT SUM(quantity) as bookings, event_id FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE active = 1 GROUP BY event_id
					) as B ON B.event_id = E.id'
                . ' WHERE E.activity_id = A.id '
                . ' AND E.template_id = T.id ';
		
		$array_user_timestamp = array();
		
		// Whether to fetch past events
		if( ! $fetch_past_events ) {
			
			$started_events_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
			if( $started_events_bookable ) {
				// Fetch events already started but not finished
				$query .= ' AND ( ( UNIX_TIMESTAMP( CONVERT_TZ( E.start, "+00:00", @@global.time_zone ) ) <= %d AND UNIX_TIMESTAMP( CONVERT_TZ( E.end, "+00:00", @@global.time_zone ) ) >= %d ) OR UNIX_TIMESTAMP( CONVERT_TZ( E.start, "+00:00", @@global.time_zone ) ) >= %d OR UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) >= %d ) ';
				$array_user_timestamp = array( $user_timestamp, $user_timestamp, $user_timestamp, $user_timestamp );
			} else {
				// Fetch only future events
				$query .= ' AND ( UNIX_TIMESTAMP( CONVERT_TZ( E.start, "+00:00", @@global.time_zone ) ) >= %d OR UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) >= %d ) ';
				$array_user_timestamp = array( $user_timestamp, $user_timestamp );
			}
		}
        
		// Do not fetch events out of their respective template limits...
		$query  .= ' AND (	';
		// ...unless we are on booking page, then, we need to keep booked events
		if( $context === 'booking_page' ) {
			$query  .= '	(
								bookings > 0
							)
							OR ';
		}
		$query  .= '		( 	NULLIF( E.repeat_freq, "none" ) IS NULL 
								AND (	UNIX_TIMESTAMP( CONVERT_TZ( E.start, "+00:00", @@global.time_zone ) ) >= 
										UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, "+00:00", @@global.time_zone ) ) 
									AND
										UNIX_TIMESTAMP( CONVERT_TZ( ( E.end + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) <= 
										UNIX_TIMESTAMP( CONVERT_TZ( T.end_date, "+00:00", @@global.time_zone ) ) 
									) 
							) 
							OR
							( 	E.repeat_freq IS NOT NULL
								AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, "+00:00", @@global.time_zone ) ) < 
											UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, "+00:00", @@global.time_zone ) ) 
										AND 
											UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) < 
											UNIX_TIMESTAMP( CONVERT_TZ( T.start_date, "+00:00", @@global.time_zone ) ) 
										)
								AND NOT (	UNIX_TIMESTAMP( CONVERT_TZ( E.repeat_from, "+00:00", @@global.time_zone ) ) > 
											UNIX_TIMESTAMP( CONVERT_TZ( T.end_date, "+00:00", @@global.time_zone ) ) 
										AND 
											UNIX_TIMESTAMP( CONVERT_TZ( ( E.repeat_to + INTERVAL -24 HOUR ), "+00:00", @@global.time_zone ) ) > 
											UNIX_TIMESTAMP( CONVERT_TZ( T.end_date, "+00:00", @@global.time_zone ) ) 
										)
						) )';
				
		//Query activities
		if( ! empty( $activities ) ) {
			
			$query  .= ' AND A.id IN ( %d';
            if( count( $activities ) >= 2 )  {
                for( $i = 0; $i < count( $activities ) - 1; $i++ ) {
                    $query  .= ', %d';
                }
            }
            $query  .= ' ) ';
			
        }
        
        //If there are templates id, only get activities from those templates 
        if( ! empty( $templates ) ) {
            $query  .= ' AND E.template_id IN ( %d';
            if( count( $templates ) >= 2 )  {
                for( $i = 0; $i < count( $templates ) - 1; $i++ ) {
                    $query  .= ', %d';
                }
            }
            $query  .= ' ) ';
        }
        
		$query  .= ' ORDER BY E.start ASC ';
		
		// Prepare the array of variable to prepare the query
        $array_of_variables = array_merge ( $array_user_timestamp, $activities, $templates );
        if( ! empty( $array_of_variables ) ) {
            $prep_query = $wpdb->prepare( $query, $array_of_variables );
        } else {
            $prep_query = $query;
        }
        
        $events = $wpdb->get_results( $prep_query, OBJECT );
		
        //Prepare the array of events to return
        $events_array = array();
        foreach ( $events as $event ) {
			// Common settings (for both events and repeat events)
			$event_array = array(
				'id'				=> $event->event_id,
				'template_id'		=> $event->template_id,
				'title'				=> apply_filters( 'bookacti_translate_text', $event->title ),
				'multilingual_title'=> $event->title,
				'allDay'			=> false,
				'color'				=> $event->color,
				'activity_id'		=> $event->activity_id,
				'availability'		=> $event->availability,
				'durationEditable'	=> false,
				'event_settings'	=> bookacti_get_metadata( 'event', $event->event_id ),
				'activity_settings'	=> bookacti_get_metadata( 'activity', $event->activity_id )
			);
			
			if( $event->repeat_freq === 'none' ) {
                
                $event_array['start']			= $event->start;
                $event_array['end']				= $event->end;
                $event_array['bookings']		= $event->bookings;
                
                array_push( $events_array, $event_array );
            } else {
				$repeated_events_array = bookacti_create_repeated_events( $event, $event_array, $user_datetime_object, $fetch_past_events, $context );
                $events_array = array_merge( $events_array, $repeated_events_array );
            }
        }
        
        return $events_array;
    }
	
	
	// Get event by id
	function bookacti_get_event_by_id( $event_id ) {
		global $wpdb;

		$query_event = 'SELECT E.id as event_id, E.template_id, E.title, E.start, E.end, E.repeat_freq, E.repeat_from, E.repeat_to, E.availability, A.color, A.is_resizable, A.id as activity_id ' 
						. ' FROM ' . BOOKACTI_TABLE_EVENTS . ' as E, ' . BOOKACTI_TABLE_ACTIVITIES . ' as A '
						. ' WHERE E.activity_id = A.id '
						. ' AND E.id = %d';
		$prep_query_event = $wpdb->prepare( $query_event, $event_id );
		$event = new stdClass();
		$event = $wpdb->get_row( $prep_query_event, OBJECT );
		
		$event->event_settings		= bookacti_get_metadata( 'event', $event_id );
		$event->activity_settings	= bookacti_get_metadata( 'activity', $event->activity_id );
		
		return $event;
	}
	
	
	// Check if the event exists
	function bookacti_is_existing_event( $event_id, $event_start = NULL, $event_end = NULL ) {
		global $wpdb;
		
		$event = bookacti_get_event_by_id( $event_id );
		
		$is_existing_event = false;
		if( ! is_null( $event ) ) {
			if( $event->repeat_freq !== 'none' ) {
				
				$is_existing_event = bookacti_is_existing_occurence( $event, $event_start, $event_end );
				
			} else {
				
				$query_exist_event = 'SELECT id FROM ' . BOOKACTI_TABLE_EVENTS
									. ' WHERE id = %d'
									. ' AND start = %s'
									. ' AND end = %s';
				$prep_exist_event = $wpdb->prepare( $query_exist_event, $event_id, $event_start, $event_end );
				$exist_event = $wpdb->get_var( $prep_exist_event );

				if( ! is_null( $exist_event ) ) {
					$is_existing_event = true;
				}
			}
		}
		
        return $is_existing_event;
	}
	
	
	// Check if the occurence exists
	function bookacti_is_existing_occurence( $event, $event_start = NULL, $event_end = NULL ) {
		if( is_numeric( $event ) ) {
			$event = bookacti_get_event_by_id( $event );
		}
		
		$repeated_events_array = bookacti_create_repeated_events( $event, array( 'id' => $event->event_id ), null, true );
		
		$is_existing_occurence = false;
		foreach( $repeated_events_array as $event_occurence ) 
		{
			if( is_null( $event_end ) || is_null( $event_start ) )
			{
				if( is_null( $event_start ) && is_null( $event_end ) ) {
					if( $event_occurence['id'] === $event->event_id ) {
						$is_existing_occurence = true;
					}
				} else if( is_null( $event_start ) ) {
					if( $event_occurence['id'] === $event->event_id 
					&&  strtotime( $event_occurence['end'] ) === strtotime( $event_end ) ) {
						$is_existing_occurence = true;
					}
				} else if( is_null( $event_end ) ) {
					if( $event_occurence['id'] === $event->event_id 
					&&  strtotime( $event_occurence['start'] ) === strtotime( $event_start ) ) {
						$is_existing_occurence = true;
					}
				}
			} else {
				if( $event_occurence['id'] === $event->event_id 
				&&  strtotime( $event_occurence['start'] )	=== strtotime( $event_start ) 
				&&  strtotime( $event_occurence['end'] )	=== strtotime( $event_end ) ) {
					$is_existing_occurence = true;
				}
			}
		}
		
		return $is_existing_occurence;
	}
	
	
	// Get the number of remaining places of an event (total places - booked places)
	function bookacti_get_event_availability( $event_id, $event_start, $event_end ) {
		global $wpdb;

        $query_total_avail  = 'SELECT availability FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
		$prep_total_avail	= $wpdb->prepare( $query_total_avail, $event_id );
        $total_avail		= $wpdb->get_var( $prep_total_avail );
        
		if( is_null( $total_avail ) ) { $availability = 0; }
		
        $bookings = bookacti_get_number_of_bookings( $event_id, $event_start, $event_end );
        
		$availability = $total_avail - $bookings;
		
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

		if( empty( $event_id ) || empty( $event_start ) || empty( $event_end ) ) {
			return false;
		}
		
		global $wpdb;
		
		// Get template range in order to be compared with the event dates
		$range_query	= 'SELECT T.start_date as start, T.end_date as end FROM ' . BOOKACTI_TABLE_TEMPLATES . ' as T, ' . BOOKACTI_TABLE_EVENTS . ' as E '
						. ' WHERE E.template_id = T.id '
						. ' AND E.id = %d ';
		$range_prepare	= $wpdb->prepare( $range_query, $event_id );
		$range			= $wpdb->get_row( $range_prepare, OBJECT );
		
		if( empty( $range ) ){
			return false;
		}
		
		// Make sure datetimes have this format 'Y-m-d H:i:s'
		$event_start	= str_replace( 'T', ' ', $event_start );
		$event_end		= str_replace( 'T', ' ', $event_end );
		
		$event_start_datetime		= DateTime::createFromFormat('Y-m-d H:i:s', $event_start );
		$event_end_datetime			= DateTime::createFromFormat('Y-m-d H:i:s', $event_end );
		$template_start_datetime	= DateTime::createFromFormat('Y-m-d H:i:s', $range->start . ' 00:00:00' );
		$template_end_datetime		= DateTime::createFromFormat('Y-m-d H:i:s', $range->end . ' 00:00:00' );
		$template_end_datetime->add( new DateInterval( 'P1D' ) );
		
		if( $event_start_datetime >= $template_start_datetime 
		&&  $event_end_datetime   <= $template_end_datetime ) {
			return true;
		}
		
		return false;
	}
	
	
// TEMPLATES
	/**
	 * Get the mixed range (start and end dates) of a group of template
	 *
	 * @since  1.0.6
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
		
		$range_query	= 'SELECT MIN( start_date ) as start, MAX( end_date ) as end FROM ' . BOOKACTI_TABLE_TEMPLATES;
		
		// If templates ids were given, search only in those templates
		if( ! empty( $template_ids ) ) {
			$range_query	.= ' WHERE id IN ( %d';

			if( count( $template_ids ) >= 2 )  {
				for( $i = 0; $i < count( $template_ids ) - 1; $i++ ) {
					$range_query  .= ', %d';
				}
			}

			$range_query	.= ')';
		}
		
		$range_prepare	= $wpdb->prepare( $range_query, $template_ids );
		$range			= $wpdb->get_row( $range_prepare, ARRAY_A );
		
		return $range;
	}
	
	
	
// PERMISSIONS
	// GET MANAGERS
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
			$managers_array[] = $manager->user_id;
		}
		
		return $managers_array;
	}
	
	
	// UPDATE MANAGERS
	function bookacti_update_managers( $object_type, $object_id, $managers_array ) {
		
		if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $managers_array ) || empty( $managers_array ) ) {
			return false;
		}
		
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
		
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

	
	// INSERT MANAGERS
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
			$insert_variables_array[] = $object_id;
			$insert_variables_array[] = $new_manager_id;
		}
		$insert_query_prep = $wpdb->prepare( $insert_managers_query, $insert_variables_array );
		$inserted = $wpdb->query( $insert_query_prep );
		
		return $inserted;
	}
	
	
	// DELETE MANAGERS
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
	// GET METADATA
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
	
	
	// UPDATE METADATA
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
			$update_metadata_query = 'UPDATE ' . BOOKACTI_TABLE_META . ' SET meta_value = ';
			$update_metadata_query_end .= ' WHERE object_type = %s AND object_id = %d AND meta_key = %s;';

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


	// INSERT META
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
	
	
	// DUPLICATE METADATA
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
	
	// DELETE METADATA
	function bookacti_delete_metadata( $object_type, $object_id, $metadata_key_array ) {
		
		global $wpdb;
		
		if ( ! $object_type || ! is_numeric( $object_id ) || ! is_array( $metadata_key_array ) || empty( $metadata_key_array ) ) {
			return false;
		}
		
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}
	
		$delete_metadata_query = 'DELETE FROM ' . BOOKACTI_TABLE_META . ' WHERE object_type = %s AND object_id = %d AND meta_key IN( ';
		$delete_variables_array = array( $object_type, $object_id );
		$j = 0;
		foreach( $metadata_key_array as $metadata_key ) {
			$delete_metadata_query .= '%s';
			
			if( ++$j === count( $metadata_key_array ) ) {
				$delete_metadata_query .= ' );';
			} else {
				$delete_metadata_query .= ', ';
			}
			$delete_variables_array[] = $metadata_key;
		}
		$delete_query_prep = $wpdb->prepare( $delete_metadata_query, $delete_variables_array );
		$deleted = $wpdb->query( $delete_query_prep );
		
		return $deleted;
	}