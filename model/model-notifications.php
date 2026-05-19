<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get notifications data from database
 * @version 1.18.0
 * @param array $filters
 * @return array
 */
function bookacti_get_notifications( $filters = array() ) {
	global $wpdb;
	
	// Check if we need to check permissions
	$ignore_permissions = $filters[ 'manager_id' ] === false;
	if( ! $ignore_permissions ) {
		$bypass_notification_managers_check = apply_filters( 'bookacti_bypass_notification_managers_check', false, $filters[ 'manager_id' ] );
		if( $bypass_notification_managers_check || is_super_admin( $filters[ 'manager_id' ] ) ) {
			$ignore_permissions = true;
		}
	}
	
	// Get notifications
	$query = ' SELECT DISTINCT N.id as db_id, N.object_type, N.target, N.trigger, N.title, N.user_id, N.creation_date, N.update_date, N.status, N.active ' 
	       . ' FROM ' . BOOKACTI_TABLE_NOTIFICATIONS . ' as N ';
	
	if( $ignore_permissions ) {
		$query .= ' WHERE TRUE ';
	} else {
		$query .= ' LEFT JOIN ' . BOOKACTI_TABLE_PERMISSIONS . ' as P ON N.id = P.object_id AND P.object_type = "notification" '
		        . ' WHERE P.user_id = %d ';
		$variables[] = $filters[ 'manager_id' ];
	}
	
	$variables = array();
	
	if( $filters[ 'in__id' ] ) {
		$query .= ' AND N.id IN ( %d ';
		$array_count = count( $filters[ 'in__id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__id' ] );
	}
	
	if( $filters[ 'object_type' ] ) {
		$query .= ' AND N.object_type = %s ';
		$variables[] = $filters[ 'object_type' ];
	}
	
	if( $filters[ 'target' ] ) {
		$query .= ' AND N.target = %s ';
		$variables[] = $filters[ 'target' ];
	}
	
	if( $filters[ 'in__trigger' ] ) {
		$query .= ' AND N.trigger IN ( %s ';
		$array_count = count( $filters[ 'in__trigger' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__trigger' ] );
	}
	
	if( $filters[ 'in__notification_type' ] ) {
		$query .= ' AND CONCAT( N.target, "_", N.trigger ) IN ( %s ';
		$array_count = count( $filters[ 'in__notification_type' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__notification_type' ] );
	}
	
	if( $filters[ 'title' ] ) {
		$query .= ' AND ( N.title LIKE %s OR N.trigger LIKE %s ) ';
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
	}
	
	if( $filters[ 'in__status' ] ) {
		$query .= ' AND N.status IN ( %s ';
		$array_count = count( $filters[ 'in__status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__status' ] );
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND N.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND N.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ] === 'trigger' ? 'N.trigger' : $filters[ 'order_by' ][ $i ]; // "trigger" is a reserved keyword
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	$results = $wpdb->get_results( $query, ARRAY_A );
	
	// Get notifications IDs
	$notification_db_ids = array();
	foreach( $results as $result ) {
		$notification_db_ids[] = $result[ 'db_id' ];
	}
	
	// Get notification channels
	$notification_channels = $notification_db_ids ? bookacti_get_notification_channels( $notification_db_ids ) : array();
	
	// Build notitification array
	$notifications = array();
	foreach( $results as $result ) {
		$notification_id = sanitize_title_with_dashes( $result[ 'target' ] . '_' . $result[ 'trigger' ] );
		if( $result[ 'status' ] !== 'permanent' ) {
			$notification_id = sanitize_title_with_dashes( $notification_id . '_custom_' . $result[ 'db_id' ] );
		}
		
		$notifications[ $notification_id ]  = array( 'id' => $notification_id );
		$notifications[ $notification_id ] += $result;
		
		if( isset( $notification_channels[ $result[ 'db_id' ] ] ) ) {
			$notifications[ $notification_id ] += $notification_channels[ $result[ 'db_id' ] ];
		}
	}
	
	return $notifications;
}


/**
 * Get notification channels data from database
 * @since 1.18.0
 * @param array $notification_db_ids
 * @return array
 */
function bookacti_get_notification_channels( $notification_db_ids = array() ) {
	global $wpdb;
	
	// Get notification channels
	$query = ' SELECT DISTINCT NC.id as channel_db_id, NC.notification_id as notification_db_id, NC.channel, NC.to, NC.subject, NC.message, NC.attachments, NC.active ' 
	       . ' FROM ' . BOOKACTI_TABLE_NOTIFICATION_CHANNELS . ' as NC '
	       . ' WHERE TRUE ';
	
	$variables = array();
	
	if( $notification_db_ids ) {
		$query .= ' AND NC.notification_id IN ( %d ';
		$array_count = count( $notification_db_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $notification_db_ids );
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	$results = $wpdb->get_results( $query, ARRAY_A );
	
	$notification_channels = array();
	foreach( $results as $result ) {
		$notification_db_id      = $result[ 'notification_db_id' ];
		$channel                 = $result[ 'channel' ];
		$result[ 'to' ]          = maybe_unserialize( $result[ 'to' ] );
		$result[ 'attachments' ] = maybe_unserialize( $result[ 'attachments' ] );
		unset( $result[ 'notification_db_id' ] );
		
		if( ! isset( $notification_channels[ $notification_db_id ] ) ) {
			$notification_channels[ $notification_db_id ] = array();
		}
		
		$notification_channels[ $notification_db_id ][ $channel ] = $result;
	}
	
	return $notification_channels;
}


/**
 * Get the total number of notification rows according to filters
 * @since 1.18.0
 * @param array $filters
 * @return array
 */
function bookacti_get_number_of_notification_rows( $filters = array() ) {
	global $wpdb;
	
	// Check if we need to check permissions
	$ignore_permissions = $filters[ 'manager_id' ] === false;
	if( ! $ignore_permissions ) {
		$bypass_notification_managers_check = apply_filters( 'bookacti_bypass_notification_managers_check', false, $filters[ 'manager_id' ] );
		if( $bypass_notification_managers_check || is_super_admin( $filters[ 'manager_id' ] ) ) {
			$ignore_permissions = true;
		}
	}
	
	// Get notifications
	$query = ' SELECT COUNT( DISTINCT N.id ) FROM ' . BOOKACTI_TABLE_NOTIFICATIONS . ' as N ';
	
	if( ! $ignore_permissions ) {
		$query .= ', ' . BOOKACTI_TABLE_PERMISSIONS . ' as P ';
	}
	
	$query .= ' WHERE TRUE ';
	
	if( ! $ignore_permissions ) {
		$query .= ' AND N.id = P.object_id '
		        . ' AND P.object_type = "notification" '
		        . ' AND P.user_id = %d ';
		$variables[] = $filters[ 'manager_id' ];
	}
	
	$variables = array();
	
	if( $filters[ 'in__id' ] ) {
		$query .= ' AND N.id IN ( %d ';
		$array_count = count( $filters[ 'in__id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__id' ] );
	}
	
	if( $filters[ 'object_type' ] ) {
		$query .= ' AND N.object_type = %s ';
		$variables[] = $filters[ 'object_type' ];
	}
	
	if( $filters[ 'target' ] ) {
		$query .= ' AND N.target = %s ';
		$variables[] = $filters[ 'target' ];
	}
	
	if( $filters[ 'in__trigger' ] ) {
		$query .= ' AND N.trigger IN ( %d ';
		$array_count = count( $filters[ 'in__trigger' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__trigger' ] );
	}
	
	if( $filters[ 'in__notification_type' ] ) {
		$query .= ' AND CONCAT( N.target, "_", N.trigger ) IN ( %s ';
		$array_count = count( $filters[ 'in__notification_type' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__notification_type' ] );
	}
	
	if( $filters[ 'title' ] ) {
		$query .= ' AND ( N.title LIKE %s OR N.trigger LIKE %s ) ';
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
	}
	
	if( $filters[ 'in__status' ] ) {
		$query .= ' AND N.status IN ( %s ';
		$array_count = count( $filters[ 'in__status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__status' ] );
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND N.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND N.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$count = $wpdb->get_var( $query );

	return $count ? $count : 0;
}


/**
 * Insert a new notification to the database
 * @since 1.18.0
 * @param array $notification_data
 * @return int|false
 */
function bookacti_insert_notification( $notification_data ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone            = new DateTimeZone( 'UTC' );
	$current_datetime_object = new DateTime( 'now', $utc_timezone );
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_NOTIFICATIONS
	       . ' ( `object_type`, `target`, `trigger`, `title`, `user_id`, `creation_date`, `update_date`, `status`, `active` ) '
	       . ' VALUES ( %s, %s, %s, %s, %d, %s, %s, %s, %d )';
	
	$variables = array(
		$notification_data[ 'object_type' ],
		$notification_data[ 'target' ],
		$notification_data[ 'trigger' ],
		$notification_data[ 'title' ],
		get_current_user_id(),
		$current_datetime_object->format( 'Y-m-d H:i:s' ),
		$current_datetime_object->format( 'Y-m-d H:i:s' ),
		$notification_data[ 'status' ],
		$notification_data[ 'active' ]
	);
	
	$query   = $wpdb->prepare( $query, $variables );
	$created = $wpdb->query( $query );
	
	return $created ? intval( $wpdb->insert_id ) : $created;
}


/**
 * Insert a new notification channel to the database
 * @since 1.18.0
 * @param int $notification_db_id
 * @param array $channel_data
 * @return int|false
 */
function bookacti_insert_notification_channel( $notification_db_id, $channel_data ) {
	if( ! $notification_db_id || empty( $channel_data[ 'channel' ] ) ) { return false; }
	
	global $wpdb;
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_NOTIFICATION_CHANNELS
	       . ' ( `notification_id`, `channel`, `to`, `subject`, `message`, `attachments`, `active` ) '
	       . ' VALUES ( %d, %s, %s, %s, %s, %s, %d )';
	
	$variables = array(
		$notification_db_id,
		$channel_data[ 'channel' ],
		$channel_data[ 'to' ],
		$channel_data[ 'subject' ],
		$channel_data[ 'message' ],
		$channel_data[ 'attachments' ],
		$channel_data[ 'active' ]
	);
	
	$query   = $wpdb->prepare( $query, $variables );
	$created = $wpdb->query( $query );
	
	return $created ? intval( $wpdb->insert_id ) : $created;
}


/**
 * Update a notification
 * @since 1.18.0
 * @global wpdb $wpdb
 * @param array $notification_data
 * @return int|false
 */
function bookacti_update_notification( $notification_data ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone            = new DateTimeZone( 'UTC' );
	$current_datetime_object = new DateTime( 'now', $utc_timezone );
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_NOTIFICATIONS
	       . ' SET '
	       . ' `object_type` = IFNULL( NULLIF( %s, "" ), `object_type` ), '
	       . ' `target` = IFNULL( NULLIF( %s, "" ), `target` ), '
	       . ' `trigger` = IFNULL( NULLIF( %s, "" ), `trigger` ), '
	       . ' `title` = IFNULL( NULLIF( %s, "" ), `title` ), '
	       . ' `user_id` = IFNULL( NULLIF( %d, -1 ), `user_id` ), '
	       . ' `update_date` = IFNULL( NULLIF( %s, "" ), `update_date` ), '
	       . ' `status` = IFNULL( NULLIF( %s, "" ), `status` ), '
	       . ' `active` = IFNULL( NULLIF( %d, -1 ), `active` ) '
	       . ' WHERE `id` = %d ';
	
	$variables = array(
		$notification_data[ 'object_type' ],
		$notification_data[ 'target' ],
		$notification_data[ 'trigger' ],
		$notification_data[ 'title' ],
		$notification_data[ 'user_id' ],
		$current_datetime_object->format( 'Y-m-d H:i:s' ),
		$notification_data[ 'status' ],
		$notification_data[ 'active' ],
		$notification_data[ 'db_id' ]
	);
	
	$query   = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Update a notification channel
 * @since 1.18.0
 * @global wpdb $wpdb
 * @param array $channel_data
 * @return int|false
 */
function bookacti_update_notification_channel( $channel_data ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_NOTIFICATION_CHANNELS
	       . ' SET '
	       . ' `channel` = IFNULL( NULLIF( %s, "" ), `channel` ), '
	       . ' `to` = IFNULL( NULLIF( %s, "" ), `to` ), '
	       . ' `subject` = IFNULL( NULLIF( %s, "" ), `subject` ), '
	       . ' `message` = IFNULL( NULLIF( %s, "" ), `message` ), '
	       . ' `attachments` = IFNULL( NULLIF( %s, "" ), `attachments` ), '
	       . ' `active` = IFNULL( NULLIF( %d, -1 ), `active` ) '
	       . ' WHERE `id` = %d ';
	
	$variables = array(
		$channel_data[ 'channel' ],
		$channel_data[ 'to' ],
		$channel_data[ 'subject' ],
		$channel_data[ 'message' ],
		$channel_data[ 'attachments' ],
		$channel_data[ 'active' ],
		$channel_data[ 'channel_db_id' ]
	);
	
	$query   = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
}




// LEGACY

/**
 * Get legacy notifications data from wp_options table
 * @since 1.18.0 (was bookacti_get_notifications)
 * @param boolean $raw
 * @return array
 */
function bookacti_get_legacy_notifications() {
	global $wpdb;
	
	$query = 'SELECT option_name, option_value '
	       . ' FROM ' . $wpdb->options
	       . ' WHERE option_name LIKE %s ';
	
	$query   = $wpdb->prepare( $query, $wpdb->esc_like( 'bookacti_notifications_settings_' ) . '%' );
	$results = $wpdb->get_results( $query );
	
	$notifications = array();
	if( $results ) {
		foreach( $results as $result ) {
			if( ! ( ! empty( $result->option_name ) && substr( $result->option_name, 0, strlen( 'bookacti_notifications_settings_' ) ) === 'bookacti_notifications_settings_' ) ) { continue; }
			
			$notification_id = substr( $result->option_name, strlen( 'bookacti_notifications_settings_' ) );
			$notification    = ! empty( $result->option_value ) ? maybe_unserialize( $result->option_value ) : false;
			
			if( ! ( $notification && is_array( $notification ) ) ) { continue; }
			
			$notifications[ $notification_id ] = array_merge( $notification, array( 'id' => $notification_id ) );
		}
	}
	
	return $notifications;
}


/**
 * Migrate legacy notifications data from wp_options table
 * @since 1.18.0
 * @return array
 */
function bookacti_migrate_legacy_notifications() {
	// Do it only once
	$migrated = get_option( 'bookacti_1_18_notifications_migrated' );
	if( $migrated ) { return; }
	
	// First, create missing notifications
	bookacti_create_missing_permanent_notifications();
	
	// Get legacy notitifications and current notifications data
	$legacy_notifications         = bookacti_get_legacy_notifications();
	$notifications                = bookacti_get_notifications_data( false, true );
	$notifications_default_values = bookacti_get_notifications_default_values();
	$channel_names                = array( 'email', 'sms', 'push' );
	$booking_statuses             = array_keys( bookacti_get_booking_statuses() );
	$permanent_notification_types = array( 
		'admin_new_booking', 'admin_rescheduled_booking', 'admin_cancelled_booking', 'admin_refund_requested_booking', 'admin_refunded_booking',
		'customer_rescheduled_booking', 'customer_pending_booking', 'customer_booked_booking', 'customer_delivered_booking', 'customer_cancelled_booking', 'customer_refund_requested_booking', 'customer_refunded_booking',
		'admin_waiting_list_booking', 'admin_waiting_list_rejected_booking', 'admin_waiting_list_accepted_booking', 'admin_waiting_list_available_event',
		'customer_waiting_list_booking', 'customer_waiting_list_rejected_booking', 'customer_waiting_list_accepted_booking', 'customer_waiting_list_available_event',
		'customer_scheduled_booking'
	);
	$add_ons_default_notification_meta = array(
		'active_with_wc' => 0,
		'delay'          => 0,
		'once_per_group' => 0,
		'filters'        => array(
			'type'             => '',
			'templates'        => array(),
			'activities'       => array(),
			'group_categories' => array()
		)
	);
	$add_ons_default_channels_meta = array(
		'email' => array(
			'email_custom_fields'         => array(),
			'send_to_activity_recipients' => 0
		),
		'push'  => array(
			'send_to_activity_recipients' => 0
		),
		'sms'   => array(
			'sms_custom_fields'           => array(),
			'send_to_activity_recipients' => 0
		)
	);
	
	foreach( $legacy_notifications as $legacy_notification_id => $legacy_notification ) {
		$target  = substr( $legacy_notification_id, 0, 6 ) === 'admin_' ? 'admin' : 'customer';
		$trigger = substr( $legacy_notification_id, strlen( $target . '_' ) );
		
		// Find custom notification trigger
		$_custom_strpos = strpos( $trigger, '_custom_' );
		if( $_custom_strpos !== false ) {
			$trigger = substr( $trigger, 0, $_custom_strpos );
		}
		
		// Convert old trigger names
		if( in_array( $trigger, array_merge( $booking_statuses, array( 'scheduled', 'rescheduled' ) ), true ) ) {
			$trigger .= '_booking';
		}
		if( $trigger === 'scheduled_default' ) {
			$trigger = 'scheduled_booking';
		}
		
		// Get notification type and default values
		$notification_type = $target . '_' . $trigger;
		$default_values    = isset( $notifications_default_values[ $notification_type ] ) ? $notifications_default_values[ $notification_type ] : array();
		
		// Check if the notification is permanent
		$is_custom = ! in_array( $notification_type, $permanent_notification_types, true );
		
		// Unset empty fields that should use default value
		$default_empty_keys = array( 'title', 'description' );
		foreach( $default_empty_keys as $default_empty_key ) {
			if( empty( $legacy_notification[ $default_empty_key ] ) && ! empty( $default_values[ $default_empty_key ] ) ) { 
				unset( $legacy_notification[ $default_empty_key ] );
			}
		}
		
		// Merge with default values
		$legacy_notification = array_merge(
			array( 'object_type' => 'booking', 'target' => $target, 'trigger' => $trigger ),
			$default_values,
			$legacy_notification
		);
		
		// Rename 'title' channel parameter to 'subject', and convert 'to' to array
		foreach( $channel_names as $channel_name ) {
			if( isset( $legacy_notification[ $channel_name ][ 'title' ] ) ) {
				$legacy_notification[ $channel_name ][ 'subject' ] = $legacy_notification[ $channel_name ][ 'title' ];
			}
			if( isset( $legacy_notification[ $channel_name ][ 'to' ] ) && ! is_array( $legacy_notification[ $channel_name ][ 'to' ] ) ) {
				$legacy_notification[ $channel_name ][ 'to' ] = explode( ',', $legacy_notification[ $channel_name ][ 'to' ] );
			}
		}
		
		// Remove "None" value from *_custom_fields meta
		if( isset( $legacy_notification[ 'email' ][ 'email_custom_fields' ] ) ) { $legacy_notification[ 'email' ][ 'email_custom_fields' ] = array_diff( $legacy_notification[ 'email' ][ 'email_custom_fields' ], array( 'none' ) ); }
		if( isset( $legacy_notification[ 'sms' ][ 'sms_custom_fields' ] ) )     { $legacy_notification[ 'sms' ][ 'sms_custom_fields' ]     = array_diff( $legacy_notification[ 'sms' ][ 'sms_custom_fields' ], array( 'none' ) ); }
		
		// Sanitize legacy notification data
		$sanitized_data = bookacti_sanitize_notification_data( $legacy_notification );
		if( ! $sanitized_data ) { continue; }
		
		// Add add-ons meta to sanitized data
		$sanitized_data = array_merge( 
			$add_ons_default_notification_meta, 
			array_intersect_key( $legacy_notification, $add_ons_default_notification_meta ),
			$sanitized_data
		);
		
		// Add add-ons channels meta to sanitized data
		foreach( $channel_names as $channel_name ) {
			if( isset( $add_ons_default_channels_meta[ $channel_name ][ 'send_to_activity_recipients' ] ) ) {
				$add_ons_default_channels_meta[ $channel_name ][ 'send_to_activity_recipients' ] = $target === 'admin' ? 1 : 0;
			}
			
			$sanitized_data[ $channel_name ] = array_merge(
				array(
					'channel_db_id' => 0,
					'channel'       => $channel_name,
					'to'            => array(),
					'subject'       => '',
					'message'       => '',
					'attachments'   => array(),
					'active'        => 0
				),
				isset( $add_ons_default_channels_meta[ $channel_name ] ) ? $add_ons_default_channels_meta[ $channel_name ] : array(), 
				isset( $legacy_notification[ $channel_name ] ) ? $legacy_notification[ $channel_name ] : array(), 
				isset( $sanitized_data[ $channel_name ] ) ? $sanitized_data[ $channel_name ] : array()
			);
		}
		
		// Serialize arrays
		if( is_array( $sanitized_data[ 'filters' ] ) )               { $sanitized_data[ 'filters' ]               = maybe_serialize( $sanitized_data[ 'filters' ] ); }
		if( is_array( $sanitized_data[ 'sms' ][ 'to' ] ) )           { $sanitized_data[ 'sms' ][ 'to' ]           = maybe_serialize( $sanitized_data[ 'sms' ][ 'to' ] ); }
		if( is_array( $sanitized_data[ 'sms' ][ 'attachments' ] ) )  { $sanitized_data[ 'sms' ][ 'attachments' ]  = maybe_serialize( $sanitized_data[ 'sms' ][ 'attachments' ] ); }
		if( is_array( $sanitized_data[ 'push' ][ 'to' ] ) )          { $sanitized_data[ 'push' ][ 'to' ]          = maybe_serialize( $sanitized_data[ 'push' ][ 'to' ] ); }
		if( is_array( $sanitized_data[ 'push' ][ 'attachments' ] ) ) { $sanitized_data[ 'push' ][ 'attachments' ] = maybe_serialize( $sanitized_data[ 'push' ][ 'attachments' ] ); }
		
		// Update existing notification
		$existing_notification = ! $is_custom && isset( $notifications[ $notification_type ] ) ? $notifications[ $notification_type ] : array();
		if( $existing_notification ) {
			$sanitized_data[ 'db_id' ] = $existing_notification[ 'db_id' ];
			bookacti_update_notification( $sanitized_data );
		}
		// Create custom notifications
		else {
			$sanitized_data[ 'id' ] = '';
			$notification_db_id = bookacti_create_notification( $sanitized_data );
			if( $notification_db_id ) {
				$existing_notification = bookacti_get_notification_data( $notification_db_id, true );
			}
		}
		
		$notification_db_id = ! empty( $existing_notification[ 'db_id' ] ) ? $existing_notification[ 'db_id' ] : 0;
		if( ! $notification_db_id ) { continue; }
		
		// Update notifications missing channels and meta
		$channels_data = array_intersect_key( $sanitized_data, array_flip( $channel_names ) );
		foreach( $channels_data as $channel => $channel_data ) {
			$channel_db_id = ! empty( $existing_notification[ $channel ][ 'channel_db_id' ] ) ? $existing_notification[ $channel ][ 'channel_db_id' ] : 0;
			
			// Update existing channels
			if( $channel_db_id ) {
				$channel_data[ 'channel_db_id' ] = $channel_db_id;
				$channel_db_id = bookacti_update_notification_channel( $channel_data );
			}
			// Create non existing channels
			else {
				$channel_db_id = bookacti_create_notification_channel( $notification_db_id, $channel_data );
			}
			if( ! $channel_db_id ) { continue; }
			
			// Update channel new meta only (if they are not equal to default value)
			$add_ons_default_channel_meta = isset( $add_ons_default_channels_meta[ $channel ] ) ? $add_ons_default_channels_meta[ $channel ] : array();
			$channel_meta = array_intersect_key( $channel_data, $add_ons_default_channel_meta );
			
			// Do not save meta equal to default
			$channel_meta_to_delete = array();
			foreach( $channel_meta as $key => $value ) {
				if( maybe_unserialize( $value ) == $add_ons_default_channel_meta[ $key ] ) {
					unset( $channel_meta[ $key ] );
					$channel_meta_to_delete[] = $key;
				}
			}
			
			if( $channel_meta ) {
				bookacti_update_metadata( 'notification_channel', $channel_db_id, $channel_meta );
			}
			
			if( $channel_meta_to_delete ) {
				bookacti_delete_metadata( 'notification_channel', $channel_db_id, $channel_meta_to_delete );
			}
		}
		
		// Update notification new meta only
		$notification_meta = array_intersect_key( $sanitized_data, $add_ons_default_notification_meta );
		
		// Do not save meta equal to default
		$notification_meta_to_delete = array();
		foreach( $notification_meta as $key => $value ) {
			if( maybe_unserialize( $value ) == $add_ons_default_notification_meta[ $key ] ) {
				unset( $notification_meta[ $key ] );
				$notification_meta_to_delete[] = $key;
			}
		}
		
		if( $notification_meta ) {
			bookacti_update_metadata( 'notification', $notification_db_id, $notification_meta );
		}
		
		if( $notification_meta_to_delete ) {
			bookacti_delete_metadata( 'notification', $notification_db_id, $notification_meta_to_delete );
		}
	}
	
	// Delete cache
	wp_cache_delete( 'notifications_data', 'bookacti' );
	
	update_option( 'bookacti_1_18_notifications_migrated', 1 );
}