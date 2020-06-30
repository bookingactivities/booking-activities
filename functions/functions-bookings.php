<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS

/**
 * Check if a booking is whithin the authorized delay as of now
 * @since 1.1.0
 * @version 1.8.0
 * @param object|int $booking
 * @param string $context
 * @return boolean
 */
function bookacti_is_booking_in_delay( $booking, $context = '' ) {
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	if( ! is_object( $booking ) ) { return false; }

	$is_in_delay	= false;
	$delay_global	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'booking_changes_deadline' );
	$timezone		= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	
	// Get the more specific per activity / group category delay
	$delay_specific = false;
	if( $booking->group_id ) {
		$booking_group	= bookacti_get_booking_group_by_id( $booking->group_id );
		$event_group	= bookacti_get_group_of_events( $booking_group->event_group_id );
		$category_data	= bookacti_get_metadata( 'group_category', $event_group->category_id );
		if( isset( $category_data[ 'booking_changes_deadline' ] ) && is_numeric( $category_data[ 'booking_changes_deadline' ] ) ) {
			$delay_specific	= floatval( $category_data[ 'booking_changes_deadline' ] );
		}
	} else {
		$event			= bookacti_get_event_by_id( $booking->event_id );
		$activity_data	= bookacti_get_metadata( 'activity', $event->activity_id );
		if( isset( $activity_data[ 'booking_changes_deadline' ] ) && is_numeric( $activity_data[ 'booking_changes_deadline' ] ) ) {
			$delay_specific	= floatval( $activity_data[ 'booking_changes_deadline' ] );
		}
	}

	// Sanitize
	if( ! is_numeric( $delay_specific ) || $delay_specific < 0 ){ $delay_specific = false; } 
	if( ! is_numeric( $delay_global ) || $delay_global < 0 )	{ $delay_global = 0; } 

	// Choose the most specific defined value
	$delay = $delay_specific !== false ? $delay_specific : $delay_global;

	// Convert delay to a valid DateInterval constructor
	$date_interval_constructor = bookacti_format_duration( floatval( $delay ), 'iso8601' );

	$date_interval		= apply_filters( 'bookacti_booking_changes_deadline_date_interval', new DateInterval( $date_interval_constructor ), $booking, $delay, $context );
	$delay_datetime		= DateTime::createFromFormat( 'Y-m-d H:i:s', $booking->event_start, new DateTimeZone( $timezone ) );
	$delay_datetime->sub( $date_interval );
	$current_datetime	= new DateTime( 'now', new DateTimeZone( $timezone ) );

	if( $current_datetime < $delay_datetime ) { $is_in_delay = true; }

	return apply_filters( 'bookacti_is_booking_in_delay', $is_in_delay, $booking, $delay, $context );
}




// BOOKINGS PAGE

/**
 * Return the HTML code to display activities by templates in the bookings page
 * @version 1.7.4
 * @param array $template_ids
 * @param array $activity_ids
 * @return string
 */
function bookacti_get_activities_html_for_booking_page( $template_ids, $activity_ids = array() ) {
	$activities = bookacti_get_activities_by_template( $template_ids, false );
	$j = 0;
	$html = '';
	foreach ( $activities as $activity ) {	
		if( ( empty( $activity_ids )  && $j === 0 ) || in_array( $activity[ 'id' ], $activity_ids ) ) { $selected = 'selected'; } else { $selected = ''; }

		// Retrieve activity title
		$title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );

		// Display activity
		$html.=	"<div class='bookacti-bookings-filter-activity bookacti-bookings-filter' "
			.		"data-activity-id='" . esc_attr( $activity[ 'id' ] ) . "' "
			.		"style='background-color: " . esc_attr( $activity[ 'color' ] ) . "; border-color: " . esc_attr( $activity[ 'color' ] ) . "' " 
			.		esc_attr( $selected )
			.	" >"
			.		"<div class='bookacti-bookings-filter-content' >"
			.			"<div class='bookacti-bookings-filter-activity-title' >"
			.				"<strong>" . esc_html( $title ). "</strong>"
			.			"</div>"
			.		"</div>"
			.		"<div class='bookacti-bookings-filter-bg' ></div>"
			.	"</div>";

		$j++;
	}

	return apply_filters( 'bookacti_activities_html_by_templates', $html, $template_ids, $activity_ids );
}


/**
 * Get Default booking filters
 * @since 1.6.0
 * @version 1.8.0
 * @return array
 */
function bookacti_get_default_booking_filters() {
	return apply_filters( 'bookacti_default_booking_filters', array(
		'templates'					=> array(), 
		'activities'				=> array(), 
		'booking_id'				=> 0, 
		'booking_group_id'			=> 0,
		'group_category_id'			=> 0, 
		'event_group_id'			=> 0, 
		'event_id'					=> 0, 
		'event_start'				=> '', 
		'event_end'					=> '', 
		'status'					=> array(), 
		'payment_status'			=> array(), 
		'user_id'					=> 0,
		'form_id'					=> 0,
		'from'						=> '',
		'to'						=> '',
		'active'					=> false,
		'group_by'					=> '',
		'order_by'					=> array( 'creation_date', 'id', 'event_start' ), 
		'order'						=> 'desc',
		'offset'					=> 0,
		'per_page'					=> 0,
		'in__booking_id'			=> array(),
		'in__booking_group_id'		=> array(),
		'in__group_category_id'		=> array(),
		'in__event_group_id'		=> array(),
		'in__event_id'				=> array(),
		'in__user_id'				=> array(),
		'in__form_id'				=> array(),
		'not_in__booking_id'		=> array(),
		'not_in__booking_group_id'	=> array(),
		'not_in__group_category_id'	=> array(),
		'not_in__event_group_id'	=> array(),
		'not_in__event_id'			=> array(),
		'not_in__user_id'			=> array(),
		'not_in__form_id'			=> array(),
		'fetch_meta'				=> false
	));
}


/**
 * Format booking filters
 * @since 1.3.0
 * @version 1.8.0
 * @param array $filters 
 * @return array
 */
function bookacti_format_booking_filters( $filters = array() ) {
	$formatted_filters = array();
	$default_filters = bookacti_get_default_booking_filters();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) { $formatted_filters[ $filter ] = $default_value; continue; }

		$current_value = $filters[ $filter ];
		
		// Specific pre-format
		if( in_array( $filter, array( 'from' ), true ) ) {
			$date = bookacti_sanitize_date( $current_value );
			if( $date ) { $current_value = $date . ' 00:00:00'; }
		} else if( in_array( $filter, array( 'to' ), true ) ) {
			$date = bookacti_sanitize_date( $current_value );
			if( $date ) { $current_value = $date . ' 23:59:59'; }
		}
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'templates' ) ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( is_array( $current_value ) ) {
				// Check if current user is allowed to manage desired templates, or unset them
				if( ! empty( $current_value ) ) {
					foreach( $current_value as $i => $template_id ) {
					if( ! is_numeric( $template_id ) || ! bookacti_user_can_manage_template( $template_id ) ) {
							unset( $current_value[ $i ] );
						}
					}
				}
				// Re-check if the template list is empty because some template filters may have been removed
				// and get all allowed templates if it is empty
				if( empty( $current_value ) ) {
					$current_value = array_keys( bookacti_fetch_templates() );
				}
			}
			else { $current_value = $default_value; }
			$current_value = array_values( $current_value );

		} else if( in_array( $filter, array( 'activities', 'in__booking_id', 'in__booking_group_id', 'in__group_category_id', 'in__event_group_id', 'in__event_id', 'in__form_id', 'not_in__booking_id', 'not_in__booking_group_id', 'not_in__group_category_id', 'not_in__event_group_id', 'not_in__event_id', 'not_in__form_id' ), true ) ) {
			if( is_numeric( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = array_values( $current_value );

		} else if( in_array( $filter, array( 'in__user_id', 'not_in__user_id' ), true ) ) {
			if( is_numeric( $current_value ) || is_string( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			$current_value = array_values( $current_value );
			
		} else if( in_array( $filter, array( 'status', 'payment_status' ), true ) ) {
			if( is_string( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = array_values( $current_value );

		} else if( in_array( $filter, array( 'booking_id', 'booking_group_id', 'group_category_id', 'event_group_id', 'event_id', 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ){ $current_value = $default_value; }

		} else if( in_array( $filter, array( 'event_start', 'event_end', 'from', 'to' ), true ) ) {
			$current_value = bookacti_sanitize_datetime( $current_value );
			if( ! $current_value ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'active' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )	{ $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) ){ $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'fetch_meta' ), true ) ) {
			if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = true; }
			else { $current_value = false; }

		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 
				'id', 
				'user_id', 
				'event_id', 
				'event_title', 
				'event_start', 
				'event_end', 
				'state', 
				'payment_status', 
				'quantity', 
				'template_id', 
				'activity_id', 
				'creation_date' 
			);
			if( is_string( $current_value ) )	{ 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) || ! $current_value )	{ $current_value = $default_value; }
			$current_value = array_values( $current_value );
			if( count( $current_value ) === 1 ) {
				if( $current_value[ 0 ] === 'creation_date' )			{ $current_value = array( 'creation_date', 'id', 'event_start' ); }
				else if( $current_value[ 0 ] === 'id' )					{ $current_value = array( 'id', 'event_start' ); }
			}
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( $filter === 'group_by' ) {
			if( ! in_array( $current_value, array( 'none', 'booking_group' ), true ) ) { $current_value = $default_value; }

		} else if( $filter === 'user_id' ) {
			if( ! is_numeric( $filter ) && ! is_string( $filter ) ) { $current_value = $default_value; }
		}

		$formatted_filters[ $filter ] = $current_value;
	}

	return apply_filters( 'bookacti_formatted_booking_filters', $formatted_filters, $filters, $default_filters );
}


/**
 * Format booking filters manually input
 * @since 1.6.0
 * @version 1.8.0
 * @param array $filters
 * @return array
 */
function bookacti_format_string_booking_filters( $filters = array() ) {
	// Format arrays
	$formatted_arrays = array();
	$int_arrays = array( 'templates', 'activities', 'in__booking_id', 'in__booking_group_id', 'in__group_category_id', 'in__event_group_id', 'in__event_id','in__form_id', 'not_in__booking_id', 'not_in__booking_group_id', 'not_in__group_category_id', 'not_in__event_group_id', 'not_in__event_id', 'not_in__form_id' );
	$str_arrays = array( 'status', 'payment_status', 'order_by', 'columns' );
	$user_id_arrays = array( 'in__user_id', 'not_in__user_id' );

	foreach( array_merge( $int_arrays, $str_arrays, $user_id_arrays ) as $att_name ) {
		if( empty( $filters[ $att_name ] ) || is_array( $filters[ $att_name ] ) ) { continue; }

		$formatted_value = preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$filters[ $att_name ] );

		if( in_array( $att_name, $int_arrays, true ) ) { 
			$formatted_arrays[ $att_name ] = explode( ',', preg_replace( array(
				'/[^\d,]/',    // Matches anything that's not a comma or number.
			), '', $formatted_value ) );
			$formatted_arrays[ $att_name ] = array_map( 'intval', $formatted_arrays[ $att_name ] ); 
		}
		if( in_array( $att_name, $str_arrays, true ) ) { 
			$formatted_arrays[ $att_name ] = explode( ',', $formatted_value );
			$formatted_arrays[ $att_name ] = array_map( 'sanitize_title_with_dashes', $formatted_arrays[ $att_name ] ); 
		}
		if( in_array( $att_name, $user_id_arrays, true ) ) { 
			// No need to santize user ids because they can be either string or numeric
			$formatted_arrays[ $att_name ] = explode( ',', $formatted_value );
		}
	}

	// Format datetime
	$from = ''; $to = '';
	if( ! empty( $filters[ 'from' ] ) || ! empty( $filters[ 'to' ] ) !== '' ) { 
		$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		if( ! empty( $filters[ 'from' ] ) && (bool)strtotime( $filters[ 'from' ] ) ) {
			$from_datetime = new DateTime( $filters[ 'from' ], $timezone );
			$from = $from_datetime->format( 'Y-m-d H:i:s' );
		}
		if( ! empty( $filters[ 'to' ] ) && (bool)strtotime( $filters[ 'to' ] ) ) {
			$to_datetime = new DateTime( $filters[ 'to' ], $timezone );
			$to = ! bookacti_sanitize_datetime( $filters[ 'to' ] ) && $to_datetime->format( 'H:i:s' ) === '00:00:00' ? $to_datetime->format( 'Y-m-d' ) . ' 23:59:59' : $to_datetime->format( 'Y-m-d H:i:s' );
		}
	}

	return apply_filters( 'bookacti_format_string_booking_filters', array_merge( $filters, array( 'from' => $from, 'to' => $to ), $formatted_arrays ), $filters );
}


/**
 * Format bookings calendar settings
 * @since 1.8.0
 * @param array $raw_settings
 * @return array
 */
function bookacti_format_bookings_calendar_settings( $raw_settings = array() ) {
	if( empty( $raw_settings ) ) { $raw_settings = array(); }
	
	// Default settings
	$default_settings = apply_filters( 'bookacti_bookings_calendar_default_settings', array_merge( array( 
		'show' => 1, 
		'ajax' => 1,
		'tooltip_booking_list' => 1,
		'tooltip_booking_list_columns' => bookacti_get_event_booking_list_default_columns()
	), bookacti_get_booking_system_default_display_data() ) );
	
	$settings = array();
		
	// Check if all settings are filled
	foreach( $default_settings as $setting_key => $setting_default_value ){
		if( isset( $raw_settings[ $setting_key ] ) && is_string( $raw_settings[ $setting_key ] ) ){ $raw_settings[ $setting_key ] = stripslashes( $raw_settings[ $setting_key ] ); }
		$settings[ $setting_key ] = isset( $raw_settings[ $setting_key ] ) ? $raw_settings[ $setting_key ] : $setting_default_value;
	}
	
	// Format non display data
	$settings[ 'show' ] = in_array( $settings[ 'show' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	$settings[ 'ajax' ] = in_array( $settings[ 'ajax' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	$settings[ 'tooltip_booking_list' ] = in_array( $settings[ 'tooltip_booking_list' ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
	
	// Check if desired columns are registered
	$formatted_atts[ 'tooltip_booking_list_columns' ] = is_array( $settings[ 'tooltip_booking_list_columns' ] ) && $settings[ 'tooltip_booking_list_columns' ] ? array_intersect( $settings[ 'tooltip_booking_list_columns' ], array_keys( bookacti_get_user_booking_list_columns_labels() ) ) : $default_settings[ 'tooltip_booking_list_columns' ];
	
	// Format display data
	$display_data = bookacti_format_booking_system_display_data( $raw_settings );
	
	return apply_filters( 'bookacti_bookings_calendar_settings_formatted', array_merge( $settings, $display_data ), $raw_settings, $default_settings );
}




// PERMISSIONS

// SINGLE BOOKINGS

/**
 * Check if user is allowed to manage a booking
 * @version 1.8.0
 * @param int $booking_id
 * @param int|string $user_id
 * @return boolean
 */
function bookacti_user_can_manage_booking( $booking_id, $user_id = false ) {
	$user_can_manage_booking = false;
	
	$user = false;
	if( ! $user_id && function_exists( 'wp_get_current_user' ) ) { $user = wp_get_current_user(); }
	else if( $user_id && function_exists( 'get_user_by' ) ) { $user = get_user_by( 'id', $user_id ); }
	
	if( $user ) {
		$booking = bookacti_get_booking_by_id( $booking_id );
		if( $booking ) {
			if( ( user_can( $user, 'bookacti_edit_bookings' ) && bookacti_user_can_manage_template( $booking->template_id, $user->ID ) ) 
			 || ( $booking->user_id && $booking->user_id == $user->ID ) 
			) { 
				$user_can_manage_booking = true; 
			}
		} 
		
		else if( in_array( 'administrator', (array) $user->roles, true ) ) {
			$user_can_manage_booking = true;
		}
	}
	
	return apply_filters( 'bookacti_user_can_manage_booking', $user_can_manage_booking, $booking_id, $user_id );
}


/**
 * Check if a booking can be cancelled
 * @version 1.7.14
 * @param object|int $booking
 * @param boolean $bypass_group_check
 * @param string $context
 * @return boolean
 */
function bookacti_booking_can_be_cancelled( $booking, $bypass_group_check = false, $context = '' ) {
	$is_allowed	= true;

	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	if( ! $booking ) { $is_allowed = false; }
	else {
		if( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) {
			$is_cancel_allowed	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' );
			$is_grouped			= $bypass_group_check ? false : ! empty( $booking->group_id );
			$is_in_delay		= apply_filters( 'bookacti_bypass_delay', false, $booking ) ? true : bookacti_is_booking_in_delay( $booking, 'cancel' );

			// Final check and return the actions array without invalid entries
			if( ! $is_cancel_allowed || $is_grouped || ! $is_in_delay ) { $is_allowed = false; }
		}
		if( empty( $booking->active ) ) { $is_allowed = false; }
	}

	return apply_filters( 'bookacti_booking_can_be_cancelled', $is_allowed, $booking, $bypass_group_check, $context );
}


/**
 * Check if a booking is allowed to be rescheduled
 * @version 1.8.0
 * @param object|int $booking
 * @param string $context
 * @return boolean
 */
function bookacti_booking_can_be_rescheduled( $booking, $context = '' ) {
	$is_allowed	= true;

	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	if( ! $booking ) { $is_allowed = false; }
	else {
		if( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) {
			// First check if the booking is part of a group
			$is_grouped = ! empty( $booking->group_id );
			if( ! $is_grouped ) {
				// Init variable
				$is_reschedule_allowed	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' );
				$is_in_delay			= apply_filters( 'bookacti_bypass_delay', false, $booking ) ? true : bookacti_is_booking_in_delay( $booking, 'reschedule' );

				if( ! $is_reschedule_allowed || ! $booking->active || ! $is_in_delay ) { $is_allowed = false; }
			} else {
				$is_allowed = false;
			}
		}

		// If the booked event has been removed, we cannot know its activity, then, the booking cannot be rescheduled.
		if( ! bookacti_get_event_by_id( $booking->event_id ) ) { $is_allowed = false; }
	}

	return apply_filters( 'bookacti_booking_can_be_rescheduled', $is_allowed, $booking, $context );
}


/**
 * Check if a booking can be rescheduled to another event
 * @since 1.1.0
 * @version 1.7.14
 * @param object|int $booking
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @param string $context
 * @return boolean
 */
function bookacti_booking_can_be_rescheduled_to( $booking, $event_id, $event_start, $event_end, $context = '' ) {
	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	if( ! $booking ) { 
		$return_array[ 'status' ] = 'failed';
		$return_array[ 'error' ] = 'booking_not_found';
		$return_array[ 'message' ] = esc_html__( 'You are not allowed to reschedule this event.', 'booking-activities' );

	} else {
		$return_array = array( 'status' => 'success' );
		$is_allowed = bookacti_booking_can_be_rescheduled( $booking, $context );

		if( ! $is_allowed ) {
			$return_array[ 'status' ] = 'failed';
			$return_array[ 'error' ] = 'reschedule_not_allowed';
			$return_array[ 'message' ] = esc_html__( 'You are not allowed to reschedule this event.', 'booking-activities' );

		} else {
			$from_event	= bookacti_get_event_by_id( $booking->event_id );
			$to_event	= bookacti_get_event_by_id( $event_id );

			if( $from_event->activity_id !== $to_event->activity_id ) {
				$return_array[ 'status' ] = 'failed';
				$return_array[ 'error' ] = 'reschedule_to_different_activity';
				$return_array[ 'message' ] = esc_html__( 'The desired event haven\'t the same activity as the booked event.', 'booking-activities' );
			}
		}
	}

	return apply_filters( 'bookacti_booking_can_be_rescheduled_to', $return_array, $booking, $event_id, $event_start, $event_end, $context );
}


/**
 * Check if a booking can be refunded
 * @version 1.8.0
 * @param int $booking
 * @param string $refund_action
 * @param string $context
 * @return boolean
 */
function bookacti_booking_can_be_refunded( $booking, $refund_action = false, $context = '' ) {
	$true = true;

	// Get booking
	if( is_numeric( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }
	if( ! $booking ) { $true = false; }
	else {
		$refund_actions = bookacti_get_booking_refund_actions( $booking, 'single', $context );

		// Disallow refund in those cases:
		// -> If the booking is already marked as refunded, 
		if( $booking->state === 'refunded' 
		// -> If the booking is part of a group
		||  ! empty( $booking->group_id )
		// -> If there are no refund action available
		||  empty( $refund_actions )
		// -> If the refund action is set but doesn't exist in available refund actions list
		|| ( ! empty( $refund_action ) && ! array_key_exists( $refund_action, $refund_actions ) ) 
		// -> If the user is not an admin, the booking state has to be 'cancelled' in the first place
		|| ( $booking->state !== 'cancelled' && ( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) ) ) { 

			$true = false;
		}
	}

	return apply_filters( 'bookacti_booking_can_be_refunded', $true, $booking, $context );
}


/**
 * Check if a booking state can be changed to another
 * @version 1.7.14
 * @param object|int $booking
 * @param string $new_state
 * @param string $context
 * @return boolean
 */
function bookacti_booking_state_can_be_changed_to( $booking, $new_state, $context = 'admin' ) {
	$true = true;
	if( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) {
		switch ( $new_state ) {
			case 'delivered':
				$true = false;
				break;
			case 'cancelled':
				$true = bookacti_booking_can_be_cancelled( $booking, false, $context );
				break;
			case 'refund_requested':
			case 'refunded':
				$true = bookacti_booking_can_be_refunded( $booking, false, $context );
				break;
		}
	}
	return apply_filters( 'bookacti_booking_state_can_be_changed', $true, $booking, $new_state, $context );
}


// BOOKING GROUPS

/**
 * Check if user is allowed to manage a booking group
 * 
 * @since 1.1.0
 * 
 * @param int $booking_group_id
 * @param int|string $user_id
 * @return boolean
 */
function bookacti_user_can_manage_booking_group( $booking_group_id, $user_id = false ) {

	$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );

	$user_can_manage_booking_group = true;
	foreach( $booking_ids as $booking_id ) {
		$is_allowed = bookacti_user_can_manage_booking( $booking_id, $user_id );
		if( ! $is_allowed ) {
			$user_can_manage_booking_group = false;
			break; // If one of the booking of the group is not allowed, return false immediatly
		}
	}

	return apply_filters( 'bookacti_user_can_manage_booking_group', $user_can_manage_booking_group, $booking_id, $user_id );
}


/**
 * Check if a booking group can be cancelled
 * @since 1.1.0
 * @version 1.7.14
 * @param object $booking_group
 * @param string $context
 * @return boolean
 */
function bookacti_booking_group_can_be_cancelled( $booking_group, $context = '' ) {
	$true = true;

	// Get booking group
	if( is_numeric( $booking_group ) ) { $booking_group = bookacti_get_booking_group_by_id( $booking_group ); }
	if( ! $booking_group ) { $true = false; }
	else {
		if( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) {
			$filters = bookacti_format_booking_filters( array( 'booking_group_id' => $booking_group->id ) );
			$bookings = bookacti_get_bookings( $filters );
			foreach( $bookings as $booking ) {
				$is_allowed = bookacti_booking_can_be_cancelled( $booking, true, $context );
				if( ! $is_allowed ) {
					$true = false;
					break; // If one of the booking of the group is not allowed, return false immediatly
				}
			}
		}
		if( ! $booking_group->active ) { $true = false; }
	}

	return apply_filters( 'bookacti_booking_group_can_be_cancelled', $true, $booking_group, $context );
}


/**
 * Check if a booking group can be refunded
 * @since 1.1.0
 * @version 1.8.0
 * @param object|int $booking_group
 * @param string $refund_action
 * @param string $context
 * @return boolean
 */
function bookacti_booking_group_can_be_refunded( $booking_group, $refund_action = false, $context = '' ) {
	$true = true;

	// Get booking group
	if( is_numeric( $booking_group ) ) { $booking_group = bookacti_get_booking_group_by_id( $booking_group ); }
	if( ! $booking_group ) { $true = false; }
	else {
		$refund_actions	= bookacti_get_booking_refund_actions( $booking_group, 'group', $context );

		// Disallow refund in those cases:
		// -> If the booking group is already marked as refunded, 
		if( $booking_group->state === 'refunded' 
		// -> If there are no refund action available
		||  empty( $refund_actions )
		// -> If the refund action is set but doesn't exist in available refund actions list
		|| ( ! empty( $refund_action ) && ! array_key_exists( $refund_action, $refund_actions ) ) 
		// -> If the user is not an admin, the booking group state has to be 'cancelled' in the first place
		|| ( $booking_group->state !== 'cancelled' && ( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) ) ) { 

			$true = false;
		}
	}

	return apply_filters( 'bookacti_booking_group_can_be_refunded', $true, $booking_group, $context );
}


/**
 * Check if a booking group state can be changed to another
 * @since 1.1.0
 * @version 1.7.14
 * @param object $booking_group
 * @param string $new_state
 * @param string $context
 * @return boolean
 */
function bookacti_booking_group_state_can_be_changed_to( $booking_group, $new_state, $context = 'admin' ) {
	$true = true;
	$can_edit_bookings = current_user_can( 'bookacti_edit_bookings' );
	switch ( $new_state ) {
		case 'delivered':
			$true = $can_edit_bookings;
			break;
		case 'cancelled':
			$true = bookacti_booking_group_can_be_cancelled( $booking_group, $context );
			break;
		case 'refund_requested':
			if( ! $can_edit_bookings || $context === 'front' ) {
				$true = bookacti_booking_group_can_be_refunded( $booking_group, false, $context );
			}
			break;
		case 'refunded':
			$true = bookacti_booking_group_can_be_refunded( $booking_group, false, $context );
			break;
	}
	return apply_filters( 'bookacti_booking_group_state_can_be_changed', $true, $booking_group, $new_state, $context );
}




// BOOKING ACTIONS

// SINGLE BOOKING
/**
 * Get booking actions array
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.8.5
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_get_booking_actions( $admin_or_front = 'both' ) {
	$actions = apply_filters( 'bookacti_booking_actions', array(
		'change-state' => array( 
			'class'			=> 'bookacti-change-booking-state',
			'label'			=> esc_html__( 'Change booking state',  'booking-activities' ),
			'description'	=> esc_html__( 'Change the booking state to any available state.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' ),
		'change-quantity' => array( 
			'class'			=> 'bookacti-change-booking-quantity',
			'label'			=> esc_html__( 'Change booking quantity',  'booking-activities' ),
			'description'	=> esc_html__( 'Change the quantity to any number.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' ),
		'cancel' => array( 
			'class'			=> 'bookacti-cancel-booking',
			'label'			=> bookacti_get_message( 'cancel_booking_open_dialog_button' ),
			'description'	=> esc_html__( 'Cancel the booking.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'front' ),
		'reschedule' => array( 
			'class'			=> 'bookacti-reschedule-booking',
			'label'			=> bookacti_get_message( 'reschedule_dialog_button' ),
			'description'	=> esc_html__( 'Change the booking dates to any other available time slot for this event.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'both' ),
		'refund' => array( 
			'class'			=> 'bookacti-refund-booking',
			'label'			=> $admin_or_front === 'both' || $admin_or_front === 'admin' ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : bookacti_get_message( 'refund_dialog_button' ),
			'description'	=> esc_html__( 'Refund the booking with one of the available refund method.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'both' ),
		'delete' => array( 
			'class'			=> 'bookacti-delete-booking bookacti-delete-button',
			'label'			=> esc_html__( 'Delete', 'booking-activities' ),
			'description'	=> esc_html__( 'Delete permanently the booking.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' )
	), $admin_or_front );

	$possible_actions = array();
	foreach( $actions as $action_id => $action ){
		if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
			$possible_actions[ $action_id ] = $action;
		}
	}

	return $possible_actions;
}


/**
 * Get booking actions according to booking id
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.7.14
 * @param object|int $booking
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_get_booking_actions_by_booking( $booking, $admin_or_front = 'both' ) {
	// Get booking
	if( ! is_object( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }

	$actions = bookacti_get_booking_actions( $admin_or_front );
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( isset( $actions[ 'change-state' ] ) )	{ unset( $actions[ 'change-state' ] ); }
		if( isset( $actions[ 'change-quantity' ] ) ){ unset( $actions[ 'change-quantity' ] ); }
	}
	if( isset( $actions[ 'cancel' ] ) && ! bookacti_booking_can_be_cancelled( $booking, false, $admin_or_front ) ) {
		unset( $actions[ 'cancel' ] );
	}
	if( isset( $actions[ 'reschedule' ] ) && ! bookacti_booking_can_be_rescheduled( $booking, $admin_or_front ) ) {
		unset( $actions[ 'reschedule' ] );
	}
	if( isset( $actions[ 'refund' ] ) && ! bookacti_booking_can_be_refunded( $booking, false, $admin_or_front ) ) {
		unset( $actions[ 'refund' ] );
	}
	if( isset( $actions[ 'delete' ] ) && ! current_user_can( 'bookacti_delete_bookings' ) ) {
		unset( $actions[ 'delete' ] );
	}
	return apply_filters( 'bookacti_booking_actions_by_booking', $actions, $booking, $admin_or_front );
}


/**
 * Get booking actions html
 * @version 1.6.0
 * @param object|int $booking
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @param array $actions
 * @param boolean $return_array
 * @param boolean $with_container
 * @return string
 */
function bookacti_get_booking_actions_html( $booking, $admin_or_front = 'both', $actions = array(), $return_array = false, $with_container = false ) {
	// Get booking
	if( ! is_object( $booking ) ) { $booking = bookacti_get_booking_by_id( $booking ); }

	// Get booking actions
	if( ! $actions ) { $actions = bookacti_get_booking_actions_by_booking( $booking, $admin_or_front ); }
	
	$actions_html_array	= array();
	foreach( $actions as $action_id => $action ){
			$action_html	= '<a '
								. 'href="' . esc_url( $action[ 'link' ] ) . '" '
								. 'id="bookacti-booking-action-' . esc_attr( $action_id ) . '-' . esc_attr( $booking->id ) . '" '
								. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-action bookacti-tip" '
								. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
								. 'data-booking-id="' . esc_attr( $booking->id ) . '" >';

			if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
				$action_html .= esc_html( $action[ 'label' ] ); 
			}

			$action_html	.= '</a>';
			$actions_html_array[ $action_id ] = $action_html;
	}

	// Return the array of html actions
	if( $return_array ) {
		return apply_filters( 'bookacti_booking_actions_html_array', $actions_html_array, $booking, $admin_or_front );
	}

	$actions_html = implode( ' | ', $actions_html_array );

	// Add a container
	if( $with_container ) {
		$actions_html	= '<div class="bookacti-booking-actions" data-booking-id="' . esc_attr( $booking->id ) . '" >'
						.	$actions_html
						. '</div>';
	}

	return apply_filters( 'bookacti_booking_actions_html', $actions_html, $booking, $admin_or_front );
}


/**
 * Get booking price details html
 * @version 1.7.10
 * @param array $prices_array
 * @param object|int $booking
 * @return string
 */
function bookacti_get_booking_price_details_html( $prices_array, $booking ) {
	$prices_html = '<div class="bookacti-booking-price-details" data-booking-id="' . esc_attr( $booking->id ) . '" >';
	foreach( $prices_array as $price_id => $price ) {
		if( ! is_array( $price ) )			{ continue; }
		if( ! isset( $price[ 'value' ] ) )	{ continue; }
		if( ! isset( $price[ 'title' ] ) )			{ $price[ 'title' ] = $price_id; }
		if( ! isset( $price[ 'display_value' ] ) )	{ $price[ 'display_value' ] = $price[ 'value' ]; }

		$prices_html .= '<div class="bookacti-price-details bookacti-price-details-' . esc_attr( $price_id ) . '" data-price="' . $price[ 'value' ] . '"><span class="bookacti-booking-price-details-title">' . $price[ 'title' ] . ': </span><span class="bookacti-booking-price-details-value">' . $price[ 'display_value' ] . '</span></div>';
	}
	$prices_html .= '</div>';

	return apply_filters( 'bookacti_booking_price_details_html', $prices_html, $prices_array, $booking );
}




// BOOKING GROUPS

/**
 * Get booking group actions array
 * @since 1.6.0 (replace bookacti_get_booking_group_actions_array)
 * @version 1.8.5
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_get_booking_group_actions( $admin_or_front = 'both' ) {
	$actions = apply_filters( 'bookacti_booking_group_actions', array(
		'change-state' => array( 
			'class'			=> 'bookacti-change-booking-group-state',
			'label'			=> esc_html__( 'Change booking state',  'booking-activities' ),
			'description'	=> esc_html__( 'Change the booking group state to any available state.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' ),
		'change-quantity' => array( 
			'class'			=> 'bookacti-change-booking-group-quantity',
			'label'			=> esc_html__( 'Change booking quantity',  'booking-activities' ),
			'description'	=> esc_html__( 'Change the quantity to any number.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' ),
		'edit-single' => array( 
			'class'			=> 'bookacti-show-booking-group-bookings',
			'label'			=> esc_html__( 'Edit bookings',  'booking-activities' ),
			'description'	=> esc_html__( 'Edit each booking of the group separately.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' ),
		'cancel' => array( 
			'class'			=> 'bookacti-cancel-booking-group',
			'label'			=> bookacti_get_message( 'cancel_booking_open_dialog_button' ),
			'description'	=> esc_html__( 'Cancel the booking group.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'front' ),
		'refund' => array( 
			'class'			=> 'bookacti-refund-booking-group',
			'label'			=> $admin_or_front === 'both' || $admin_or_front === 'admin' ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : bookacti_get_message( 'refund_dialog_button' ),
			'description'	=> esc_html__( 'Refund the booking group with one of the available refund method.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'both' ),
		'delete' => array( 
			'class'			=> 'bookacti-delete-booking-group bookacti-delete-button',
			'label'			=> esc_html__( 'Delete', 'booking-activities' ),
			'description'	=> esc_html__( 'Delete permanently the booking group.', 'booking-activities' ),
			'link'			=> '',
			'admin_or_front'=> 'admin' )
	), $admin_or_front );

	$possible_actions = array();
	foreach( $actions as $action_id => $action ){
		if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
			$possible_actions[ $action_id ] = $action;
		}
	}

	return $possible_actions;
}


/**
 * Get booking actions according to booking id
 * @since 1.6.0 (replace bookacti_get_booking_actions_array)
 * @version 1.7.14
 * @param object|int $booking_group
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @return array
 */
function bookacti_get_booking_group_actions_by_booking_group( $booking_group, $admin_or_front = 'both' ) {
	// Get booking group
	if( ! is_object( $booking_group ) ) { $booking_group = bookacti_get_booking_group_by_id( $booking_group ); }

	$actions = bookacti_get_booking_group_actions( $admin_or_front );
	if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
		if( isset( $actions[ 'change-state' ] ) )	{ unset( $actions[ 'change-state' ] ); }
		if( isset( $actions[ 'change-quantity' ] ) ){ unset( $actions[ 'change-quantity' ] ); }
		if( isset( $actions[ 'edit-single' ] ) )	{ unset( $actions[ 'edit-single' ] ); }
	}
	if( isset( $actions[ 'cancel' ] ) && ! bookacti_booking_group_can_be_cancelled( $booking_group, $admin_or_front ) ) {
		unset( $actions[ 'cancel' ] );
	}
	if( isset( $actions[ 'refund' ] ) && ! bookacti_booking_group_can_be_refunded( $booking_group, false, $admin_or_front ) ) {
		unset( $actions[ 'refund' ] );
	}
	if( isset( $actions[ 'delete' ] ) && ! current_user_can( 'bookacti_delete_bookings' ) ) {
		unset( $actions[ 'delete' ] );
	}
	return apply_filters( 'bookacti_booking_group_actions_by_booking_group', $actions, $booking_group, $admin_or_front );
}


/**
 * Get booking group actions html
 * @version 1.6.0
 * @param object|int $booking_group
 * @param string $admin_or_front Can be "both", "admin", "front. Default "both".
 * @param array $actions
 * @param boolean $return_array
 * @param boolean $with_container
 * @return string
 */
function bookacti_get_booking_group_actions_html( $booking_group, $admin_or_front = 'both', $actions = array(), $return_array = false, $with_container = false ) {
	// Get booking group
	if( ! is_object( $booking_group ) ) { $booking_group = bookacti_get_booking_group_by_id( $booking_group ); }

	if( ! $actions ) {
		$actions = bookacti_get_booking_group_actions_by_booking_group( $booking_group, $admin_or_front );
	}

	$actions_html_array	= array();
	foreach( $actions as $action_id => $action ){
		$action_html	= '<a '
							. 'href="' . esc_url( $action[ 'link' ] ) . '" '
							. 'id="bookacti-booking-group-action-' . esc_attr( $action_id ) . '-' . intval( $booking_group->id ) . '" '
							. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-group-action bookacti-tip" '
							. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
							. 'data-booking-group-id="' . intval( $booking_group->id ) . '" >';

		if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
			$action_html .= esc_html( $action[ 'label' ] ); 
		}

		$action_html	.= '</a>';
		$actions_html_array[] = $action_html;
	}

	// Return the array of html actions
	if( $return_array ) {
		return apply_filters( 'bookacti_booking_group_actions_html_array', $actions_html_array, $booking_group, $admin_or_front );
	}

	$actions_html = implode( ' | ', $actions_html_array );

	// Add a container
	if( $with_container ) {
		$actions_html	= '<div class="bookacti-booking-group-actions" data-booking-group-id="' . esc_attr( $booking_group->id ) . '" >' 
						.	$actions_html
						. '</div>';
	}

	return apply_filters( 'bookacti_booking_group_actions_html', $actions_html, $booking_group, $admin_or_front );
}



// BOTH SINGLE AND GROUPS

/**
 * Booking data that can be exported
 * @since 1.6.0
 * @version 1.8.0
 * @return array
 */
function bookacti_get_bookings_export_columns() {
	return apply_filters( 'bookacti_bookings_export_columns_labels', array(
		'booking_id'			=> esc_html__( 'Booking ID', 'booking-activities' ),
		'booking_type'			=> esc_html__( 'Booking type (single or group)', 'booking-activities' ),
		'status'				=> esc_html__( 'Booking status', 'booking-activities' ),
		'payment_status'		=> esc_html__( 'Payment status', 'booking-activities' ),
		'quantity'				=> esc_html__( 'Quantity', 'booking-activities' ),
		'creation_date'			=> esc_html__( 'Creation date', 'booking-activities' ),
		'customer_id'			=> esc_html__( 'Customer ID', 'booking-activities' ),
		'customer_display_name'	=> esc_html__( 'Customer display name', 'booking-activities' ),
		'customer_first_name'	=> esc_html__( 'Customer first name', 'booking-activities' ),
		'customer_last_name'	=> esc_html__( 'Customer last name', 'booking-activities' ),
		'customer_email'		=> esc_html__( 'Customer email', 'booking-activities' ),
		'customer_phone'		=> esc_html__( 'Customer phone', 'booking-activities' ),
		'customer_roles'		=> esc_html__( 'Customer roles', 'booking-activities' ),
		'event_id'				=> esc_html__( 'Event ID', 'booking-activities' ),
		'event_title'			=> esc_html__( 'Event title', 'booking-activities' ),
		'start_date'			=> esc_html__( 'Start date', 'booking-activities' ),
		'end_date'				=> esc_html__( 'End date', 'booking-activities' ),
		'template_id'			=> esc_html__( 'Calendar ID', 'booking-activities' ),
		'template_title'		=> esc_html__( 'Calendar title', 'booking-activities' ),
		'activity_id'			=> esc_html__( 'Activity / Category ID', 'booking-activities' ),
		'activity_title'		=> esc_html__( 'Activity / Category title', 'booking-activities' ),
		'form_id'				=> esc_html__( 'Form ID', 'booking-activities' ),
		'order_id'				=> esc_html__( 'Order ID', 'booking-activities' )
	) );
}


/**
 * Get bookings export default settings
 * @since 1.8.0
 * @return array
 */
function bookacti_get_bookings_export_default_settings() {
	$defaults = array(
		// CSV
		'csv_columns' => array(
			'booking_id',
			'booking_type',
			'status',
			'payment_status',
			'quantity',
			'creation_date',
			'customer_display_name',
			'customer_email',
			'event_title',
			'start_date',
			'end_date'
		),
		'csv_raw' => 0,
		'csv_export_groups'	=> 'groups',
		
		// iCal
		'ical_columns' => array(
			'customer_display_name',
			'customer_email',
			'quantity'
		),
		'ical_raw' => 0,
		'ical_booking_list_header' => 1,
		'vevent_summary'	=> '[{event_booked_quantity}/{event_availability_total}] {event_title}',
		'vevent_description'=> '{booking_list}',
		
		// Global
		'per_page'			=> 200
	);
	return apply_filters( 'bookacti_bookings_export_default_settings', $defaults );
}


/**
 * Get bookings export settings per user
 * @since 1.8.0
 * @param int $user_id
 * @return array
 */
function bookacti_get_bookings_export_settings( $user_id = 0 ) {
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	
	$default_settings	= bookacti_get_bookings_export_default_settings();
	$user_settings		= $user_id ? get_user_meta( $user_id, 'bookacti_bookings_export_settings', true ) : array();
	
	$settings = array();
	foreach( $default_settings as $key => $default ) {
		$settings[ $key ] = isset( $user_settings[ $key ] ) ? $user_settings[ $key ] : $default;
	}
	
	return apply_filters( 'bookacti_bookings_export_settings', $settings, $user_id );
}


/**
 * Sanitize bookings export settings
 * @since 1.8.0
 * @param array $raw_settings
 * @return array
 */
function bookacti_sanitize_bookings_export_settings( $raw_settings ) {
	$default_settings = bookacti_get_bookings_export_default_settings();
	
	$columns_settings = array( 'csv_columns', 'ical_columns' );
	$keys_by_type = array( 
		'str'		=> array( 'vevent_summary' ),
		'str_id'	=> array( 'csv_export_groups' ),
		'str_html'	=> array( 'vevent_description' ),
		'int'		=> array( 'per_page' ),
		'bool'		=> array( 'csv_raw', 'ical_raw', 'ical_booking_list_header' ),
		'array'		=> array( 'csv_columns', 'ical_columns' )
	);
	$settings = bookacti_sanitize_values( $default_settings, $raw_settings, $keys_by_type );
	
	// Sanitize export groups value
	if( ! in_array( $settings[ 'csv_export_groups' ], array( 'groups', 'bookings' ), true ) )	{ $settings[ 'csv_export_groups' ] = $default_settings[ 'csv_export_groups' ]; }
	
	// Keep only allowed columns
	$allowed_columns = bookacti_get_bookings_export_columns();
	foreach( $columns_settings as $setting_name ) {
		foreach( $settings[ $setting_name ] as $i => $column_name ) {
			if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $settings[ $setting_name ][ $i ] ); }
		}
		$settings[ $setting_name ] = array_values( $settings[ $setting_name ] );
	}
	
	return apply_filters( 'bookacti_sanitized_bookings_export_settings', $settings, $raw_settings, $default_settings );
}


/**
 * Get bookings export event tags
 * @since 1.8.0
 * @return array
 */
function bookacti_get_bookings_export_event_tags() {
	return apply_filters( 'bookacti_bookings_export_event_tags', array(
		'{event_id}'				=> esc_html__( 'Event ID', 'booking-activities' ),
		'{event_title}'				=> esc_html__( 'The event title', 'booking-activities' ),
		'{event_start}'				=> esc_html__( 'Event start date and time (formatted)', 'booking-activities' ),
		'{event_end}'				=> esc_html__( 'Event end date and time (formatted)', 'booking-activities' ),
		'{event_start_raw}'			=> esc_html__( 'Event start date and time (ISO)', 'booking-activities' ),
		'{event_end_raw}'			=> esc_html__( 'Event end date and time (ISO)', 'booking-activities' ),
		'{event_booked_quantity}'	=> esc_html__( 'Number of active bookings', 'booking-activities' ),
		'{event_availability}'		=> esc_html__( 'Number of remaining places', 'booking-activities' ),
		'{event_availability_total}'=> esc_html__( 'Total number of places', 'booking-activities' ),
		'{activity_id}'				=> esc_html__( 'Activity ID', 'booking-activities' ),
		'{activity_title}'			=> esc_html__( 'Activity title', 'booking-activities' ),
		'{calendar_id}'				=> esc_html__( 'Calendar ID', 'booking-activities' ),
		'{calendar_title}'			=> esc_html__( 'Calendar title', 'booking-activities' ),
		'{booking_list}'			=> esc_html__( 'Event booking list (table)', 'booking-activities' ),
		'{booking_list_raw}'		=> esc_html__( 'Event booking list (csv)', 'booking-activities' )
	));
}


/**
 * Convert a list of bookings to CSV format
 * @since 1.6.0
 * @version 1.8.0
 * @param array $filters
 * @param array $args_raw
 * @return string
 */
function bookacti_convert_bookings_to_csv( $filters, $args_raw = array() ) {
	$default_settings = bookacti_get_bookings_export_default_settings();
	$args_default = array( 
		'columns'	=> $default_settings[ 'csv_columns' ],
		'raw'		=> $default_settings[ 'csv_raw' ],
		'locale'	=> '',
	);
	$args = wp_parse_args( $args_raw, $args_default );
	
	// Remove unknown columns 
	$headers = array();
	$allowed_columns = bookacti_get_bookings_export_columns();
	foreach( $args[ 'columns' ] as $i => $column_name ) {
		if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $args[ 'columns' ][ $i ] ); continue; }
		$headers[ $column_name ] = str_replace( ',', '', strip_tags( apply_filters( 'bookacti_translate_text', $allowed_columns[ $column_name ], $args[ 'locale' ] ) ) );
	}
	
	// Get booking items
	$export_args = array( 
		'filters' => $filters, 
		'columns' => $args[ 'columns' ], 
		'raw' => $args[ 'raw' ], 
		'type' => 'csv',
		'locale' => $args[ 'locale' ]
	);
	$items = bookacti_get_bookings_for_export( $export_args );
	
	return bookacti_generate_csv( $items, $headers );
}


/**
 * Convert bookings to iCal format
 * @since 1.8.0
 * @version 1.8.4
 * @param array $filters
 * @param array $args_raw
 * @return string
 */
function bookacti_convert_bookings_to_ical( $filters = array(), $args_raw = array() ) {
	// Sanitized args
	$default_settings = bookacti_get_bookings_export_default_settings();
	$args_default = array( 
		'vevent_summary'		=> $default_settings[ 'vevent_summary' ],
		'vevent_description'	=> $default_settings[ 'vevent_description' ],
		'tooltip_booking_list_columns'	=> $default_settings[ 'ical_columns' ],
		'booking_list_header'	=> $default_settings[ 'ical_booking_list_header' ],
		'raw'					=> $default_settings[ 'ical_raw' ],
		'sequence'				=> 0,
		'locale'				=> ''
	);
	$args = wp_parse_args( $args_raw, $args_default );
	
	// Get items and events
	$filters[ 'group_by' ] = 'none';
	$export_args = array( 
		'filters' => $filters, 
		'columns' => $args[ 'tooltip_booking_list_columns' ], 
		'raw' => $args[ 'raw' ], 
		'type' => 'ical',
		'locale' => $args[ 'locale' ]
	);
	$booking_items = bookacti_get_bookings_for_export( $export_args );
	$events_tags = bookacti_get_bookings_export_events_tags_values( $booking_items, $args );
	
	$vcalendar = apply_filters( 'bookacti_bookings_ical_vcalendar', array(
		'X-WR-CALNAME' => esc_html__( 'My bookings', 'booking-activities' ),
		'X-WR-CALDESC' => esc_html__( 'My bookings', 'booking-activities' ) . '.'
	), $booking_items, $events_tags, $args, $filters );
	
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj = new DateTimeZone( $timezone );
	$occurrence_counter = array();
	$vevents = array();
	
	foreach( $booking_items as $item ) {
		$index = $item[ 'start_date_raw' ] . '_' . $item[ 'event_id' ];
		if( isset( $vevents[ $index ] ) ) { continue; }
		
		// Increase the occurrence counter
		if( ! isset( $occurrence_counter[ $item[ 'event_id' ] ] ) ) { $occurrence_counter[ $item[ 'event_id' ] ] = 0; }
		++$occurrence_counter[ $item[ 'event_id' ] ];

		$uid			= $item[ 'event_id' ] . '-' . $occurrence_counter[ $item[ 'event_id' ] ];
		$event_start	= new DateTime( $item[ 'start_date_raw' ], $timezone_obj );
		$event_end		= new DateTime( $item[ 'end_date_raw' ], $timezone_obj );
		
		$events_tags[ $index ][ '{booking_list}' ] = $events_tags[ $index ][ '{booking_list}' ] ? '<table>' . $events_tags[ $index ][ '{booking_list}' ] . '</table>' : '';
		
		$summary	= $args[ 'vevent_summary' ] ? str_replace( array_keys( $events_tags[ $index ] ), array_values( $events_tags[ $index ] ), $args[ 'vevent_summary' ] ) : '';
		$description= $args[ 'vevent_description' ] ? str_replace( array_keys( $events_tags[ $index ] ), array_values( $events_tags[ $index ] ), $args[ 'vevent_description' ] ) : '';
		
		$vevents[ $index ] = apply_filters( 'bookacti_bookings_ical_vevent', array(
			'UID'			=> $uid,
			'DTSTART'		=> $event_start->format( 'Ymd\THis' ),
			'DTEND'			=> $event_end->format( 'Ymd\THis' ),
			'SUMMARY'		=> bookacti_sanitize_ical_property( $summary, 'SUMMARY' ),
			'DESCRIPTION'	=> bookacti_sanitize_ical_property( $description, 'DESCRIPTION' ),
			'SEQUENCE'		=> $args[ 'sequence' ]
		), $item, $events_tags[ $index ], $vcalendar, $args, $filters );
	}
	
	$vevents = apply_filters( 'bookacti_bookings_ical_vevents', $vevents, $booking_items, $events_tags, $vcalendar, $args, $filters );
	
	return bookacti_generate_ical( $vevents, $vcalendar );
}


/**
 * Get the events tags values
 * @since 1.8.0
 * @param array $booking_items
 * @param array $args
 * @return array
 */
function bookacti_get_bookings_export_events_tags_values( $booking_items, $args ) {
	$has_booking_list = ! empty( $args[ 'tooltip_booking_list_columns' ] ) && strpos( $args[ 'vevent_summary' ] . $args[ 'vevent_description' ], '{booking_list' ) !== false;
	
	// Remove unknown columns in the booking list
	if( $has_booking_list ) {
		$booking_list_headers = array();
		$allowed_columns = bookacti_get_bookings_export_columns();
		foreach( $args[ 'tooltip_booking_list_columns' ] as $i => $column_name ) {
			if( ! isset( $allowed_columns[ $column_name ] ) ) { unset( $args[ 'tooltip_booking_list_columns' ][ $i ] ); continue; }
			$booking_list_headers[ $column_name ] = apply_filters( 'bookacti_translate_text', $allowed_columns[ $column_name ], $args[ 'locale' ] );
		}
		if( empty( $args[ 'tooltip_booking_list_columns' ] ) ) { $has_booking_list = false; }
	}
	
	$date_format = bookacti_get_message( 'date_format_long' );
	
	// Order the items by event occurrence and merge data into an event array
	$qty_ack = array();
	$events_tags = array();
	foreach( $booking_items as $item ) {
		$index = $item[ 'start_date_raw' ] . '_' . $item[ 'event_id' ];
		
		// Get the initial tags values
		if( ! isset( $events_tags[ $index ] ) ) {
			$events_tags[ $index ] = array(
				'{event_id}'				=> $item[ 'event_id' ],
				'{event_title}'				=> $item[ 'event_title' ],
				'{event_start}'				=> bookacti_format_datetime( $item[ 'start_date_raw' ], $date_format ),
				'{event_end}'				=> bookacti_format_datetime( $item[ 'end_date_raw' ], $date_format ),
				'{event_start_raw}'			=> $item[ 'start_date_raw' ],
				'{event_end_raw}'			=> $item[ 'end_date_raw' ],
				'{event_booked_quantity}'	=> 0,
				'{event_availability}'		=> intval( $item[ 'availability' ] ),
				'{event_availability_total}'=> intval( $item[ 'availability' ] ),
				'{activity_id}'				=> $item[ 'activity_id' ],
				'{activity_title}'			=> $item[ 'activity_title' ],
				'{calendar_id}'				=> $item[ 'template_id' ],
				'{calendar_title}'			=> $item[ 'template_title' ],
				'{booking_list}'			=> $has_booking_list && ! empty( $args[ 'booking_list_header' ] ) ? '<tr><th>' . implode( '</th><th>', $booking_list_headers ) . '</th></tr>' : '',
				'{booking_list_raw}'		=> $has_booking_list && ! empty( $args[ 'booking_list_header' ] ) ? implode( ', ', str_replace( ',', '', array_map( 'strip_tags', $booking_list_headers ) ) ) : ''
			);
		}
		
		// Increment booking quantity
		if( $item[ 'booking_active' ] && empty( $qty_ack[ $item[ 'booking_id' ] ] ) ) {
			$events_tags[ $index ][ '{event_booked_quantity}' ] += intval( $item[ 'quantity' ] );
			$events_tags[ $index ][ '{event_availability}' ] -= intval( $item[ 'quantity' ] );
			$qty_ack[ $item[ 'booking_id' ] ] = true; // Make sure each booking is counted only once
		}
		
		// Build the booking list tags (keep the columns order)
		if( $has_booking_list ) {
			$booking_list_values = array_replace( $booking_list_headers, array_intersect_key( $item, $booking_list_headers ) );
			if( $booking_list_values ) {
				$events_tags[ $index ][ '{booking_list}' ] .= '<tr><td>' . implode( '</td><td>', $booking_list_values ) . '</td></tr>';
				$events_tags[ $index ][ '{booking_list_raw}' ] .= '\n' . implode( ', ', str_replace( ',', '', array_map( 'strip_tags', $booking_list_values ) ) );
			}
		}
	}
	
	return apply_filters( 'bookacti_bookings_export_events_tags_values', $events_tags, $booking_items, $args );
}


/**
 * Get an array of bookings data formatted to be exported
 * @since 1.6.0
 * @version 1.8.0
 * @param array $args_raw
 * @return array
 */
function bookacti_get_bookings_for_export( $args_raw = array() ) {
	// Format args
	$default_args = array(
		'filters' => array(),
		'columns' => array(),
		'raw' => true,
		'type' => 'csv',
		'locale' => ''
	);
	$args = wp_parse_args( $args_raw, $default_args );
	
	// Check if we will need user data
	$has_user_data = false;
	foreach( $args[ 'columns' ] as $column_name ) {
		if( $column_name !== 'customer_id' && substr( $column_name, 0, 9 ) === 'customer_' ) { 
			$has_user_data = true; break; 
		}
	}
	$get_user_data = apply_filters( 'bookacti_bookings_export_get_users_data', $has_user_data, $args );
	if( $get_user_data ) { $args[ 'filters' ][ 'fetch_meta' ] = true; }

	$bookings = bookacti_get_bookings( $args[ 'filters' ] );

	// Check if the booking list can contain groups
	$may_have_groups = false; 
	$single_only = $args[ 'filters' ][ 'group_by' ] === 'none';
	if( ( ! $args[ 'filters' ][ 'booking_group_id' ] || $args[ 'filters' ][ 'group_by' ] === 'booking_group' ) && ! $args[ 'filters' ][ 'booking_id' ] ) {
		$may_have_groups = true;
	}

	// Gether all IDs in arrays
	$user_ids = array();
	$group_ids = array();
	if( ( $may_have_groups || $single_only ) || $get_user_data ) {
		foreach( $bookings as $booking ) {
			if( $booking->user_id && is_numeric( $booking->user_id ) && ! in_array( $booking->user_id, $user_ids, true ) ) { $user_ids[] = $booking->user_id; }
			if( $booking->group_id && ! in_array( $booking->group_id, $group_ids, true ) )	{ $group_ids[] = $booking->group_id; }
		}
	}

	// Retrieve the required groups data only
	$booking_groups		= array();
	$displayed_groups	= array();
	if( ( $may_have_groups || $single_only ) && $group_ids ) {
		// Get only the groups that will be displayed
		$group_filters = bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids ) );

		// If the bookings are grouped by booking groups, 
		// booking group meta will already be attached to the booking representing its group 
		$group_filters[ 'fetch_meta' ] = $args[ 'filters' ][ 'group_by' ] !== 'booking_group';

		$booking_groups = bookacti_get_booking_groups( $group_filters );
	}

	// Retrieve information about users and stock them into an array sorted by user id
	$users = array();
	$roles_names = array();
	if( $get_user_data ) {
		$users = bookacti_get_users_data( array( 'include' => $user_ids ) );
		$roles_names = bookacti_get_roles();
	}
	$unknown_user_id = esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
	
	$date_format		= $args[ 'raw' ] ? 'Y-m-d' : get_option( 'date_format' );
	$datetime_format	= $args[ 'raw' ] ? 'Y-m-d H:i:s' : apply_filters( 'bookacti_translate_text', bookacti_get_message( 'date_format_long', true ), $args[ 'locale' ] );
	$booking_status		= $args[ 'raw' ] ? array() : bookacti_get_booking_state_labels();
	$payment_status		= $args[ 'raw' ] ? array() : bookacti_get_payment_status_labels();
	
	// Build booking list
	$booking_items = array();
	foreach( $bookings as $booking ) {
		$group = $booking->group_id && ! empty( $booking_groups[ $booking->group_id ] ) ? $booking_groups[ $booking->group_id ] : null;

		// Display one single row for a booking group, instead of each bookings of the group
		if( $booking->group_id && $may_have_groups && ! $single_only ) {
			// If the group row has already been displayed, or if it is not found, continue
			if( isset( $displayed_groups[ $booking->group_id ] ) )	{ continue; }
			if( empty( $booking_groups[ $booking->group_id ] ) )	{ continue; }

			$booking_type	= 'group';
			$id				= $group->id;
			$user_id		= $group->user_id;
			$status			= $group->state;
			$paid			= $group->payment_status;
			$event_id		= $group->event_group_id;
			$title			= $group->group_title;
			$start			= $group->start;
			$end			= $group->end;
			$quantity		= $group->quantity;
			$availability	= $booking->availability;
			$form_id		= $group->form_id;
			$order_id		= $group->order_id;
			$activity_id	= $group->category_id;
			$activity_title	= $group->category_title;
			$active			= $group->active;

			$displayed_groups[ $booking->group_id ] = $booking->id;

		// Single booking
		} else {
			$booking_type	= 'single';
			$id				= $booking->id;
			$user_id		= $booking->user_id;
			$status			= $booking->state;
			$paid			= $booking->payment_status;
			$title			= $booking->event_title;
			$event_id		= $booking->event_id;
			$start			= $booking->event_start;
			$end			= $booking->event_end;
			$quantity		= $booking->quantity;
			$availability	= $booking->availability;
			$form_id		= $booking->form_id;
			$order_id		= $booking->order_id;
			$activity_id	= $booking->activity_id;
			$activity_title	= $booking->activity_title;
			$active			= $booking->active;
		}

		$booking_data = array( 
			'booking_id'			=> $id,
			'booking_type'			=> $booking_type,
			'booking_active'		=> $active,
			'status'				=> $args[ 'raw' ] ? $status : ( ! empty( $booking_status[ $status ][ 'label' ] ) ? $booking_status[ $status ][ 'label' ] : $status ),
			'status_raw'			=> $status,
			'payment_status'		=> $args[ 'raw' ] ? $paid : ( ! empty( $payment_status[ $paid ][ 'label' ] ) ? $payment_status[ $paid ][ 'label' ] : $paid ),
			'payment_status_raw'	=> $paid,
			'quantity'				=> $quantity,
			'availability'			=> $availability,
			'creation_date'			=> $args[ 'raw' ] ? $booking->creation_date : bookacti_format_datetime( $booking->creation_date, $date_format ),
			'creation_date_raw'		=> $booking->creation_date,
			'event_id'				=> $event_id,
			'event_title'			=> apply_filters( 'bookacti_translate_text', $title, $args[ 'locale' ] ),
			'start_date'			=> $args[ 'raw' ] ? $start : bookacti_format_datetime( $start, $datetime_format ),
			'end_date'				=> $args[ 'raw' ] ? $end : bookacti_format_datetime( $end, $datetime_format ),
			'start_date_raw'		=> $start,
			'end_date_raw'			=> $end,
			'template_id'			=> $booking->template_id,
			'template_title'		=> apply_filters( 'bookacti_translate_text', $booking->template_title, $args[ 'locale' ] ),
			'activity_id'			=> $activity_id,
			'activity_title'		=> apply_filters( 'bookacti_translate_text', $activity_title, $args[ 'locale' ] ),
			'form_id'				=> $form_id,
			'order_id'				=> $order_id,
			'customer_id'			=> $user_id,
			'customer_display_name'	=> '',
			'customer_first_name'	=> '',
			'customer_last_name'	=> '',
			'customer_email'		=> '',
			'customer_phone'		=> '',
			'customer_roles'		=> ''
		);

		// Format customer column
		$user = null;
		if( $get_user_data ) {
			// If the customer has an account
			if( ! empty( $users[ $user_id ] ) ) {
				$user = $users[ $user_id ];
				$booking_data = array_merge( $booking_data, array(
					'customer_display_name'	=> $user->display_name,
					'customer_first_name'	=> ! empty( $user->first_name ) ? $user->first_name : '',
					'customer_last_name'	=> ! empty( $user->last_name ) ? $user->last_name : '',
					'customer_email'		=> ! empty( $user->user_email ) ? $user->user_email : '',
					'customer_phone'		=> ! empty( $user->phone ) ? $user->phone : '',
					'customer_roles'		=> ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : ''
				));

			// If the booking was made without account
			} else if( $user_id === $unknown_user_id || is_email( $user_id ) ) {
				$booking_meta = $group && $args[ 'filters' ][ 'group_by' ] !== 'booking_group' ? $group : $booking;
				$booking_data = array_merge( $booking_data, array(
					'customer_first_name'	=> ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name : '',
					'customer_last_name'	=> ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name : '',
					'customer_email'		=> ! empty( $booking_meta->user_email ) ? $booking_meta->user_email : '',
					'customer_phone'		=> ! empty( $booking_meta->user_phone ) ? $booking_meta->user_phone : ''
				));
				$booking_data[ 'customer_display_name' ] .= ! empty( $booking_data[ 'customer_first_name' ] ) ? $booking_data[ 'customer_first_name' ] : '';
				$booking_data[ 'customer_display_name' ] .= empty( $booking_data[ 'customer_first_name' ] ) && ! empty( $booking_data[ 'customer_last_name' ] ) ? ' ' : '';
				$booking_data[ 'customer_display_name' ] .= ! empty( $booking_data[ 'customer_last_name' ] ) ? $booking_data[ 'customer_last_name' ] : '';
			}
		}

		/**
		 * Third parties can add or change columns content, but do your best to optimize your process
		 * @since 1.6.0
		 */
		$booking_item = apply_filters( 'bookacti_booking_export_columns_content', $booking_data, $booking, $group, $user, $args );

		$booking_items[ $booking->id ] = $booking_item;
	}

	/**
	 * Third parties can add or change rows and columns, but do your best to optimize your process
	 * @since 1.6.0
	 */
	return apply_filters( 'bookacti_booking_items_to_export', $booking_items, $bookings, $booking_groups, $displayed_groups, $users, $args );
}




// REFUND BOOKING

/**
 * Get available actions user can take to be refunded 
 * @return array
 */
function bookacti_get_refund_actions(){
	$possible_actions_array = array(
		'email' => array( 
			'id'			=> 'email',
			'label'			=> __( 'Email', 'booking-activities' ),
			'description'	=> __( 'Send a refund request by email to the administrator.', 'booking-activities' ) )
	);

	return apply_filters( 'bookacti_refund_actions', $possible_actions_array );
}


/**
 * Get refund actions for a specific booking or booking group
 * @since 1.8.0 (was bookacti_get_refund_actions_by_booking_type)
 * @param int|object $booking
 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
 * @param string $context
 * @return array
 */
function bookacti_get_booking_refund_actions( $booking, $booking_type = 'single', $context = '' ) {
	$possible_actions = bookacti_get_refund_actions();

	// If current user is a customer
	if( ! current_user_can( 'bookacti_edit_bookings' ) || $context === 'front' ) {
		// Keep only allowed action
		$allowed_actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
		if( ! is_array( $allowed_actions ) ) {
			if( ! empty( $allowed_actions ) ) {
				$allowed_actions = array( $allowed_actions );
			} else {
				$allowed_actions = array();
			}
		}
		// Keep all possible actions that are allowed
		$possible_actions = array_intersect_key( $possible_actions, array_flip( $allowed_actions ) );

	// If current user is an admin
	} else {
		// Email action is useless, remove it
		if( isset( $possible_actions[ 'email' ] ) ) { unset( $possible_actions[ 'email' ] ); }
	}

	if( $booking_type === 'single' ) {
		$possible_actions = apply_filters( 'bookacti_refund_actions_by_booking', $possible_actions, $booking, $context );
	} else if( $booking_type === 'group' ) {
		$possible_actions = apply_filters( 'bookacti_refund_actions_by_booking_group', $possible_actions, $booking, $context );
	}

	return $possible_actions;
}


/**
 * Get dialog refund text for a specific booking
 * @since 1.8.0 (was bookacti_get_refund_dialog_html_by_booking_type)
 * @param int $booking_id
 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
 * @param array $actions
 * @param string $context
 * @return string
 */
function bookacti_get_booking_refund_options_html( $booking_id, $booking_type = 'single', $actions = array(), $context = '' ) {
	if( ! $actions ) { $actions = bookacti_get_booking_refund_actions( $booking_id, $booking_type, $context ); }
	if( ! $actions ) { return ''; }
	
	ob_start();
	
	foreach( $actions as $action ) {
		$action_id = esc_attr( $action[ 'id' ] );
		?>
		<div class='bookacti-refund-option' >
			<div class='bookacti-refund-option-radio'>
				<input type='radio' name='refund-action' value='<?php echo $action_id; ?>' id='bookacti-refund-action-<?php echo $action_id; ?>' class='bookacti-refund-action'/>
			</div>
			<div class='bookacti-refund-option-text'>
				<label class='bookacti-refund-option-label' for='bookacti-refund-action-<?php echo $action_id; ?>'><?php echo esc_html( $action[ 'label' ] ); ?></label>
				<span class='bookacti-refund-option-description'><?php echo esc_html( $action[ 'description' ] ); ?></span>
			</div>
		</div>
		<?php
	}
	
	return apply_filters( 'bookacti_booking_refund_options_html', ob_get_clean(), $booking_id, $booking_type, $actions, $context );
}


/**
 * Get the amount to be refunded for a booking
 * @since 1.8.0
 * @param int $booking_id
 * @param string $booking_type
 * @return string
 */
function bookacti_get_booking_refund_amount( $booking_id, $booking_type = 'single' ) {
	return apply_filters( 'bookacti_booking_refund_amount', '', $booking_id, $booking_type );
}


/**
 * Send a refund request by email for a specific booking
 * @version 1.7.0
 * @param int $booking_id
 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
 * @param string $user_message
 * @return boolean
 */
function bookacti_send_email_refund_request( $booking_id, $booking_type, $user_message = false ) {

	$to = apply_filters( 'bookacti_refund_request_email_to', array( get_option( 'admin_email' ) ), $booking_id, $booking_type );

	/* translators: %1$s is the booking id */
	$subject	= $booking_type === 'group' ? esc_html__( 'Refund request for booking group %1$s', 'booking-activities' ) : esc_html__( 'Refund request for booking %1$s', 'booking-activities' );
	$subject	= apply_filters( 'bookacti_refund_request_email_subject', sprintf( $subject, $booking_id ), $booking_id, $booking_type );

	$data = array();

	// Single booking
	if( $booking_type === 'single' ) {

		$booking = bookacti_get_booking_by_id( $booking_id );
		if( $booking ) {
			$data['booking']					= array();
			$data['booking']['calendar_id']		= $booking->template_id;
			$data['booking']['activity_name']	= apply_filters( 'bookacti_translate_text', $booking->title ) . ' (' . _x( 'id', 'An id is a unique identification number' ) . ': ' . $booking->activity_id . ')';
			$data['booking']['event_start']		= bookacti_format_datetime( $booking->event_start );
			$data['booking']['event_end']		= bookacti_format_datetime( $booking->event_end );
			$data['booking']['quantity']		= $booking->quantity;
			$data['booking']['status']			= $booking->state;
		}

	// Booking Group
	} else if( $booking_type === 'group' ) {

		$booking_group	= bookacti_get_booking_group_by_id( $booking_id );
		$bookings		= bookacti_get_bookings_by_booking_group_id( $booking_id );
		if( $booking_group || $bookings ) {
			$data['booking_group'] = array();
		}
		if( $bookings ) {
			$data['booking_group']['calendar_id']	= $bookings[0]->template_id;
			$data['booking_group']['events']		= bookacti_get_formatted_booking_events_list( $bookings, 'show' );
		}
		if( $booking_group ) {
			$data['booking_group']['status'] = $booking_group->state;
		}
	}

	$user_id = ! empty( $booking_group ) ? $booking_group->user_id : ( ! empty( $booking ) ? $booking->user_id : '' );
	if( $user_id ) {
		$data['user'] = array();
		$user = is_numeric( $user_id ) ? get_user_by( 'id', $user_id ) : null;

		if( $user ) {
			$data['user']['name']	= isset( $user->first_name ) && isset( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->user_login;
			$data['user']['name']	= '<a href="' . esc_url( get_edit_user_link() ) . '">' . esc_html( $data['user']['name'] ) . '</a>';
			$data['user']['email']	= '<a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a>';
		} else {
			$object_type	= $booking_type === 'group' ? 'booking_group' : 'booking';
			$booking_meta	= bookacti_get_metadata( $object_type, $booking_id );
			$user_email		= ! empty( $booking_meta[ 'user_email' ] ) ? $booking_meta[ 'user_email' ] : '';

			$data['user']['name'] = '';
			$data['user']['name'] .= ! empty( $booking_meta[ 'user_first_name' ] ) ? $booking_meta[ 'user_first_name' ] : '';
			$data['user']['name'] .= empty( $booking_meta[ 'user_first_name' ] ) && ! empty( $booking_meta[ 'user_last_name' ] ) ? ' ' : '';
			$data['user']['name'] .= ! empty( $booking_meta[ 'user_last_name' ] ) ? $booking_meta[ 'user_last_name' ] : '';
			$data['user']['email']	= is_email( $user_email ) ? '<a href="mailto:' . esc_attr( $user_email ) . '">' . esc_html( $user_email ) . '</a>' : $user_email;
		}
	}


	$data = apply_filters( 'bookacti_refund_request_email_data', $data, $booking_id, $booking_type );

	/* translators: %1$s is a user name and %2$s is the booking ID. */
	$message = '<h3>' . sprintf( esc_html__( '%1$s wants to be refunded for booking %2$s', 'booking-activities' ), $data['user']['name'], $booking_id ) . '</h3>';
	foreach( $data as $category_name => $category_data ) {
		$message .= '<h4>' . esc_html( ucfirst ( str_replace( '_', ' ', $category_name ) ) ) . '</h4>';
		$message .= '<table style="border: none;" >';
		foreach( $category_data as $name => $value ) {
			$message .= '<tr><td style="border: none; width: 135px; padding-right: 15px;">' . esc_html( ucfirst ( str_replace( '_', ' ', $name ) ) ) . '</td><td>' . $value . '</td>';
		}
		$message .= '</table>';
	}

	/* translators: Message left by the user */
	if( $user_message ) {
		$message	.= '<h4>' . esc_html__( 'User message', 'booking-activities' ). '</h4>';
		$message	.= '<em>' . esc_html( $user_message ) . '</em><br/>';
	}

	$message	= apply_filters( 'bookacti_refund_request_email_message', $message, $booking_id, $booking_type, $data, $user_message );
	$headers	= apply_filters( 'bookacti_refund_request_email_headers', array( 'Content-Type: text/html; charset=UTF-8' ) );

	$sent = bookacti_send_email( $to, $subject, $message, $headers );

	return $sent;
}




// FORMATTING

/**
 * Retrieve booking states labels and display data
 * @version 1.6.0
 * @return array
 */
function bookacti_get_booking_state_labels() {
	return apply_filters( 'bookacti_booking_states_labels_array', array(
		'delivered'			=> array( 'display_state' => 'good',	'label' => esc_html__( 'Delivered', 'booking-activities' ) ),
		'booked'			=> array( 'display_state' => 'good',	'label' => esc_html__( 'Booked', 'booking-activities' ) ),
		'pending'			=> array( 'display_state' => 'warning',	'label' => esc_html__( 'Pending', 'booking-activities' ) ),
		'cancelled'			=> array( 'display_state' => 'bad',		'label' => esc_html__( 'Cancelled', 'booking-activities' ) ),
		'refunded'			=> array( 'display_state' => 'bad',		'label' => esc_html__( 'Refunded', 'booking-activities' ) ),
		'refund_requested'	=> array( 'display_state' => 'bad',		'label' => esc_html__( 'Refund requested', 'booking-activities' ) )
	) );
}


/**
 * Retrieve payment status labels and display data
 * @since 1.3.0
 * @version 1.6.0
 * @return array
 */
function bookacti_get_payment_status_labels() {
	return apply_filters( 'bookacti_payment_status_labels_array', array(
		'none'	=> array( 'display_state' => 'disabled','label' => esc_html__( 'No payment required', 'booking-activities' ) ),
		'owed'	=> array( 'display_state' => 'warning',	'label' => esc_html__( 'Owed', 'booking-activities' ) ),
		'paid'	=> array( 'display_state' => 'good',	'label' => esc_html__( 'Paid', 'booking-activities' ) )
	) );
}


/**
 * Get the formatted and translated booking state
 * @version 1.8.0
 * @param string $state
 * @param boolean $icon_only
 * @return string
 */
function bookacti_format_booking_state( $state, $icon_only = false ) {
	$booking_states_labels = bookacti_get_booking_state_labels();

	$formatted_value = '';
	if( isset( $booking_states_labels[ $state ] ) ) {
		if( $icon_only ) {
			$formatted_value = '<span class="bookacti-booking-state bookacti-booking-state-' . esc_attr( $booking_states_labels[ $state ][ 'display_state' ] ) . ' bookacti-tip" data-booking-state="' . esc_attr( $state ) . '" data-tip="'. esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '" ></span>';
		} else {
			$formatted_value = '<span class="bookacti-booking-state bookacti-booking-state-' . esc_attr( $booking_states_labels[ $state ][ 'display_state' ] ) . '" data-booking-state="' . esc_attr( $state ) . '" title="' . esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '" ><label>' . esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '</label></span>';
		}
	} else if( $state ) {
		$formatted_value = '<span class="bookacti-booking-state" data-booking-state="' . esc_attr( $state ) . '" title="' . esc_html( $state ) . '"><label>' . esc_html( $state ) . '</label></span>';
	}

	return apply_filters( 'bookacti_booking_states_display', $formatted_value, $state, $icon_only );
}


/**
 * Get the formatted and translated payment status
 * @since 1.8.0
 * @param string $status
 * @param boolean $icon_only
 * @return string
 */
function bookacti_format_payment_status( $status, $icon_only = false ) {
	$payment_status_labels = bookacti_get_payment_status_labels();

	$formatted_value = '';
	if( isset( $payment_status_labels[ $status ] ) ) {
		if( $icon_only ) {
			$formatted_value = '<span class="bookacti-payment-status bookacti-payment-status-' . esc_attr( $payment_status_labels[ $status ][ 'display_state' ] ) . ' bookacti-tip" data-payment-status="' . esc_attr( $status ) . '" data-tip="'. esc_html( $payment_status_labels[ $status ][ 'label' ] ) . '" ></span>';
		} else {
			$formatted_value = '<span class="bookacti-payment-status bookacti-payment-status-' . esc_attr( $payment_status_labels[ $status ][ 'display_state' ] ) . '" data-payment-status="' . esc_attr( $status ) . '" title="' . esc_html( $payment_status_labels[ $status ][ 'label' ] ) . '"><label>' . esc_html( $payment_status_labels[ $status ][ 'label' ] ) . '</label></span>';
		}
	} else if( $status ) {
		$formatted_value = '<span class="bookacti-payment-status" data-payment-status="' . esc_attr( $status ) . '" title="' . esc_html( $status ) . '"><label>' . esc_html( $status ) . '</label></span>';
	}

	return apply_filters( 'bookacti_payment_status_display', $formatted_value, $status, $icon_only );
}


/**
 * Give an array of all ACTIVE booking state, every other booking states will be considered as INACTIVE
 * @version 1.6.0
 * @return array
 */
function bookacti_get_active_booking_states() {
	return apply_filters( 'bookacti_active_booking_states', array( 'delivered', 'booked', 'pending' ) );
}	




// BOOKING LIST

/**
 * Booking list column labels
 * @since 1.7.4
 * @version 1.8.0
 * @return array
 */
function bookacti_get_user_booking_list_columns_labels() {
	return apply_filters( 'bookacti_user_booking_list_columns_labels', array(
		'booking_id'			=> _x( 'id', 'An id is a unique identification number', 'booking-activities' ),
		'booking_type'			=> esc_html_x( 'Type', 'Booking type (single or group)', 'booking-activities' ),
		'status'				=> esc_html_x( 'Status', 'Booking status', 'booking-activities' ),
		'payment_status'		=> esc_html_x( 'Paid', 'Payment status column name', 'booking-activities' ),
		'quantity'				=> esc_html_x( 'Qty', 'Short for "Quantity"', 'booking-activities' ),
		'creation_date'			=> esc_html__( 'Date', 'booking-activities' ),
		'customer_id'			=> esc_html__( 'Customer ID', 'booking-activities' ),
		'customer_display_name'	=> esc_html__( 'Customer', 'booking-activities' ),
		'customer_first_name'	=> esc_html__( 'First name', 'booking-activities' ),
		'customer_last_name'	=> esc_html__( 'Last name', 'booking-activities' ),
		'customer_email'		=> esc_html__( 'Email', 'booking-activities' ),
		'customer_phone'		=> esc_html__( 'Phone', 'booking-activities' ),
		'customer_roles'		=> esc_html__( 'Roles', 'booking-activities' ),
		'events'				=> esc_html__( 'Events', 'booking-activities' ),
		'event_id'				=> esc_html__( 'Event ID', 'booking-activities' ),
		'event_title'			=> esc_html__( 'Title', 'booking-activities' ),
		'start_date'			=> esc_html__( 'Start', 'booking-activities' ),
		'end_date'				=> esc_html__( 'End', 'booking-activities' ),
		'template_id'			=> esc_html__( 'Calendar ID', 'booking-activities' ),
		'template_title'		=> esc_html__( 'Calendar', 'booking-activities' ),
		'activity_id'			=> esc_html__( 'Activity ID', 'booking-activities' ),
		'activity_title'		=> esc_html__( 'Activity', 'booking-activities' ),
		'form_id'				=> esc_html__( 'Form ID', 'booking-activities' ),
		'order_id'				=> esc_html__( 'Order ID', 'booking-activities' ),
		'actions'				=> esc_html__( 'Actions', 'booking-activities' )
	) );
}


/**
 * Default user booking list columns
 * @since 1.7.4
 * @return array
 */
function bookacti_get_user_booking_list_default_columns() {
	$columns = apply_filters( 'bookacti_user_booking_list_default_columns', array(
		10	=> 'booking_id',
		20	=> 'events',
		30	=> 'quantity',
		40	=> 'status',
		100 => 'actions'
	) );

	// Order columns
	ksort( $columns );

	return $columns;
}


/**
 * Default event booking list columns
 * @since 1.8.0
 * @return array
 */
function bookacti_get_event_booking_list_default_columns() {
	$columns = apply_filters( 'bookacti_event_booking_list_default_columns', array(
		10	=> 'booking_id',
		20	=> 'status',
		30	=> 'payment_status',
		40	=> 'quantity',
		50	=> 'customer_display_name'
	) );
	
	// Order columns
	ksort( $columns );

	return $columns;
}


/**
 * Private booking list columns
 * @since 1.8.0
 * @return array
 */
function bookacti_get_user_booking_list_private_columns() {
	return apply_filters( 'bookacti_user_booking_list_private_columns', array(
		'customer_id',
		'customer_display_name',
		'customer_first_name',
		'customer_last_name',
		'customer_email',
		'customer_phone',
		'customer_roles'
	));
}


/**
 * Get booking list items
 * @since 1.7.4
 * @version 1.8.0
 * @param array $filters
 * @param array $columns
 * @return string
 */
function bookacti_get_user_booking_list_items( $filters, $columns = array() ) {
	// Get default columns
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	// Remove private columns if not allowed
	$current_user_id = get_current_user_id();
	$display_private_columns = bookacti_get_setting_value( 'bookacti_general_settings', 'display_private_columns' ) || ! empty( $filters[ 'display_private_columns' ] );
	$current_user_can_manage_bookings = current_user_can( 'bookacti_manage_bookings' ) || current_user_can( 'bookacti_edit_bookings' );
	if( ! $display_private_columns ) {
		$current_user_can_see_users_data = current_user_can( 'list_users' ) || current_user_can( 'edit_users' );
		$is_current_user_list = $current_user_id && ( ( intval( $filters[ 'user_id' ] ) && intval( $filters[ 'user_id' ] ) === $current_user_id ) || ( count( $filters[ 'in__user_id' ] ) === 1 && intval( $filters[ 'in__user_id' ][ 0 ] ) && intval( $filters[ 'in__user_id' ][ 0 ] ) === $current_user_id ) );
		$display_private_columns = $current_user_can_manage_bookings || $current_user_can_see_users_data || $is_current_user_list;
	}
	$private_columns = bookacti_get_user_booking_list_private_columns();
	$is_customer_email_pivate = in_array( 'customer_email', $private_columns, true );
	$is_customer_id_pivate = in_array( 'customer_id', $private_columns, true );
	
	// Check if we will need user data
	$has_user_data = false;
	foreach( $columns as $column_name ) {
		if( $column_name !== 'customer_id' && substr( $column_name, 0, 9 ) === 'customer_' ) { 
			$has_user_data = true; break; 
		}
	}
	$get_user_data = apply_filters( 'bookacti_user_booking_list_get_users_data', $has_user_data, $filters, $columns );
	if( $get_user_data ) { $filters[ 'fetch_meta' ] = true; }
	
	// Get bookings
	$bookings = bookacti_get_bookings( $filters );
	
	// Check if the booking list can contain groups
	$single_only = $filters[ 'group_by' ] === 'none';
	$may_have_groups = false; 
	if( ( ! $filters[ 'booking_group_id' ] || in_array( $filters[ 'group_by' ], array( 'booking_group', 'none' ), true ) ) && ! $filters[ 'booking_id' ] ) {
		$may_have_groups = true;
	}
	
	// Gether all IDs in arrays
	$user_ids = array();
	$group_ids = array();
	foreach( $bookings as $booking ) {
		if( $booking->user_id && is_numeric( $booking->user_id ) && ! in_array( $booking->user_id, $user_ids, true ) ){ $user_ids[] = $booking->user_id; }
		if( $booking->group_id && ! in_array( $booking->group_id, $group_ids, true ) ){ $group_ids[] = $booking->group_id; }
	}
	
	// Retrieve the required groups data only
	$booking_groups		= array();
	$displayed_groups	= array();
	$bookings_per_group	= array();
	if( ( $may_have_groups || $single_only ) && $group_ids ) {
		$group_filters		= bookacti_format_booking_filters( array( 'in__booking_group_id' => $group_ids, 'fetch_meta' => $filters[ 'fetch_meta' ] ) );
		$booking_groups		= bookacti_get_booking_groups( $group_filters );
		$groups_bookings	= bookacti_get_bookings( $group_filters );
		foreach( $groups_bookings as $booking ) {
			if( ! isset( $bookings_per_group[ $booking->group_id ] ) ) { $bookings_per_group[ $booking->group_id ] = array(); }
			$bookings_per_group[ $booking->group_id ][] = $booking;
		}
	}

	// Retrieve information about users and stock them into an array sorted by user id
	$users = array();
	$roles_names = array();
	if( $get_user_data ) {
		$users = bookacti_get_users_data( array( 'include' => $user_ids ) );
		$roles_names = bookacti_get_roles();
	}
	$unknown_user_id = esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
	
	// Get datetime format
	$datetime_format	= bookacti_get_message( 'date_format_long' );
	$quantity_separator	= bookacti_get_message( 'quantity_separator' );
	
	// Build an array of bookings rows
	$booking_list_items = array();
	foreach( $bookings as $booking ) {
		$group				= $booking->group_id && ! empty( $booking_groups[ $booking->group_id ] ) ? $booking_groups[ $booking->group_id ] : null;
		$grouped_bookings	= $booking->group_id && ! empty( $bookings_per_group[ $booking->group_id ] ) && ! $single_only ? $bookings_per_group[ $booking->group_id ] : array( $booking );
		
		// Display one single row for a booking group, instead of each bookings of the group
		if( $booking->group_id && $may_have_groups && ! $single_only ) {
			// If the group row has already been displayed, or if it is not found, continue
			if( isset( $displayed_groups[ $booking->group_id ] ) 
			||  empty( $booking_groups[ $booking->group_id ] ) ) { continue; }
			
			$group_id_link	= $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $group->id . '&group_by=booking_group&event_group_id=' . $group->event_group_id ) . '">' . $group->id . '</a>' : $group->id;
			
			$raw_id			= $group->id;
			$tr_class		= 'bookacti-booking-group';
			$id				= $group_id_link . '<span class="bookacti-booking-group-indicator">' . esc_html_x( 'Group', 'noun', 'booking-activities' ) . '</span>';
			$user_id		= $group->user_id;
			$status			= $group->state;
			$paid			= $group->payment_status;
			$event_id		= $group->event_group_id;
			$title			= $group->group_title;
			$start			= $group->start;
			$end			= $group->end;
			$quantity		= $group->quantity;
			$form_id		= $group->form_id;
			$order_id		= $group->order_id;
			$actions		= in_array( 'actions', $columns, true ) ? bookacti_get_booking_group_actions_by_booking_group( $group, 'front' ) : array();
			$refund_actions	= in_array( 'actions', $columns, true ) ? bookacti_get_booking_refund_actions( $group, 'group', 'front' ) : array();
			$activity_id	= $group->category_id;
			$activity_title	= $group->category_title;
			$booking_type	= 'group';
			
			$displayed_groups[ $booking->group_id ] = $booking->id;

		// Single booking
		} else {
			$booking_id_link= $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_id=' . $booking->id . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end ) . '">' . $booking->id. '</a>' : $booking->id;
			$group_id_link	= $current_user_can_manage_bookings ? '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $booking->group_id . '&group_by=booking_group&event_group_id=' . ( $group ? $group->event_group_id : '' ) ) . '">' . $booking->group_id . '</a>' : $booking->group_id;
			
			$raw_id			= $booking->id;
			$tr_class		= $booking->group_id ? 'bookacti-single-booking bookacti-gouped-booking bookacti-booking-group-id-' . $booking->group_id : 'bookacti-single-booking';
			$id				= $booking->group_id ? $booking_id_link . '<span class="bookacti-booking-group-id" >' . $group_id_link . '</span>' : $booking_id_link;
			$user_id		= $booking->user_id;
			$status			= $booking->state;
			$paid			= $booking->payment_status;
			$event_id		= $booking->event_id;
			$title			= $booking->event_title;
			$start			= $booking->event_start;
			$end			= $booking->event_end;
			$quantity		= $booking->quantity;
			$form_id		= $booking->form_id;
			$order_id		= $booking->order_id;
			$actions		= in_array( 'actions', $columns, true ) ? bookacti_get_booking_actions_by_booking( $booking, 'front' ) : array();
			$refund_actions	= in_array( 'actions', $columns, true ) ? bookacti_get_booking_refund_actions( $booking, 'single', 'front' ) : array();
			$activity_id	= $booking->activity_id;
			$activity_title	= $booking->activity_title;
			$booking_type	= 'single';
		}
		
		// Format customer column
		// If the customer has an account
		if( ! empty( $users[ $user_id ] ) ) {
			$user = $users[ $user_id ];
			$customer	= ! empty( $user->first_name ) && ! empty( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->display_name;
			$first_name	= ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name	= ! empty( $user->last_name ) ? $user->last_name : '';
			$email		= ! empty( $user->user_email ) ? $user->user_email : '';
			$phone		= ! empty( $user->phone ) ? $user->phone : '';
			$roles		= ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : '';

		// If the booking was made without account
		} else if( $user_id === $unknown_user_id || is_email( $user_id ) ) {
			$user		= null;
			$customer	= ! empty( $user_id ) ? $user_id : '';
			$booking_meta = $group && $filters[ 'group_by' ] !== 'booking_group' ? $group : $booking;
			if( ! empty( $booking_meta->user_first_name ) || ! empty( $booking_meta->user_last_name ) ) {
				$customer = ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name . ' ' : '';
				$customer .= ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name . ' ' : '';
				$customer .= $user_id !== $unknown_user_id ? '<br/>(' . $user_id . ')' : '';
			}
			$first_name	= ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name : '';
			$last_name	= ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name : '';
			$email		= ! empty( $booking_meta->user_email ) ? $booking_meta->user_email : '';
			$phone		= ! empty( $booking_meta->user_phone ) ? $booking_meta->user_phone : '';
			$roles		= '';

		// Any other cases
		} else {
			$user		= null;
			$customer	= esc_html( __( 'Unknown user', 'booking-activities' ) . ' (' . $user_id . ')' );
			$first_name	= '';
			$last_name	= '';
			$email		= '';
			$phone		= '';
			$roles		= '';
		}
		
		/**
		 * Third parties can add or change columns content, do your best to optimize your process
		 */
		$booking_item = apply_filters( 'bookacti_user_booking_list_item', array( 
			'tr_class'				=> $tr_class,
			'booking_id_raw'		=> $raw_id,
			'booking_id'			=> $id,
			'booking_type'			=> $booking_type,
			'status'				=> bookacti_format_booking_state( $status ),
			'payment_status'		=> bookacti_format_payment_status( $paid ),
			'quantity'				=> $quantity,
			/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://wordpress.org/support/article/formatting-date-and-time/ */
			'creation_date'			=> bookacti_format_datetime( $booking->creation_date, esc_html__( 'F d, Y', 'booking-activities' ) ),
			'customer_id'			=> $user_id,
			'customer_display_name'	=> $customer,
			'customer_first_name'	=> $first_name,
			'customer_last_name'	=> $last_name,
			'customer_email'		=> $email,
			'customer_phone'		=> $phone,
			'customer_roles'		=> $roles,
			'events'				=> in_array( 'events', $columns, true ) ? bookacti_get_formatted_booking_events_list( $grouped_bookings ) : '',
			'event_id'				=> $event_id,
			'event_title'			=> apply_filters( 'bookacti_translate_text', $title ),
			'start_date'			=> bookacti_format_datetime( $start, $datetime_format ),
			'end_date'				=> bookacti_format_datetime( $end, $datetime_format ),
			'start_date_raw'		=> $start,
			'end_date_raw'			=> $end,
			'template_id'			=> $booking->template_id,
			'template_title'		=> apply_filters( 'bookacti_translate_text', $booking->template_title ),
			'activity_id'			=> $activity_id,
			'activity_title'		=> apply_filters( 'bookacti_translate_text', $activity_title ),
			'form_id'				=> $form_id,
			'order_id'				=> $order_id,
			'actions'				=> $actions,
			'refund_actions'		=> $refund_actions
		), $booking, $group, $grouped_bookings, $user, $filters, $columns );
		
		$booking_list_items[ $booking->id ] = $booking_item;
	}
	
	/**
	 * Third parties can add or change rows and columns, do your best to optimize your process
	 */
	$booking_list_items = apply_filters( 'bookacti_user_booking_list_items', $booking_list_items, $bookings, $booking_groups, $bookings_per_group, $displayed_groups, $users, $filters, $columns );
	
	$get_empty_items = apply_filters( 'bookacti_user_booking_list_empty_items', false, $booking_list_items, $bookings, $booking_groups, $bookings_per_group, $displayed_groups, $users, $filters, $columns );
	foreach( $booking_list_items as $booking_id => $booking_list_item ) {
		// Turn the action array to HTML
		if( empty( $booking_list_item[ 'refund_actions' ] ) && isset( $booking_list_item[ 'actions' ][ 'refund' ] ) ) { unset( $booking_list_item[ 'actions' ][ 'refund' ] ); }
		if( $booking_list_item[ 'booking_type' ] === 'group' ) {
			$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_group_actions_html( $booking_groups[ $booking_list_item[ 'booking_id_raw' ] ], 'front', $booking_list_item[ 'actions' ] ) : '';
		} else if( $booking_list_item[ 'booking_type' ] === 'single' ) {
			$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_actions_html( $bookings[ $booking_list_item[ 'booking_id_raw' ] ], 'front', $booking_list_item[ 'actions' ] ) : '';
		}
		
		// Remove the booking item if all the desired columns are empty
		$current_user_row = is_numeric( $booking_list_item[ 'customer_id' ] ) && $current_user_id === intval( $booking_list_item[ 'customer_id' ] );
		$empty_row = true;
		foreach( $columns as $column ) {
			$not_empty = ! empty( $booking_list_item[ $column ] ) || ( isset( $booking_list_item[ $column ] ) && in_array( $booking_list_item[ $column ], array( '0', 0 ), true ) );
			// Replace private column value
			// and make sure 'customer_id' and 'customer_display_name' columns don't display the customer email if not allowed
			$private_column = false;
			if( ! $display_private_columns && ! $current_user_row && $not_empty 
				&& ( in_array( $column, $private_columns, true ) 
					|| ( $is_customer_email_pivate && in_array( $column, array( 'customer_id', 'customer_display_name' ), true ) && is_email( $booking_list_item[ $column ] ) )
					|| ( $is_customer_id_pivate && $column === 'customer_display_name' && is_numeric( $booking_list_item[ 'customer_id' ] ) ) && strpos( $booking_list_item[ $column ], $booking_list_item[ 'customer_id' ] ) !== false
					) 
			) {
				$private_column = true;
				$booking_list_items[ $booking_id ][ $column ] = '<span class="bookacti-private-value">' . esc_html__( 'Private data', 'booking-activities' ) . '</span>';
			}
			if( $not_empty && ! $private_column ) {
				$empty_row = false;
				if( $display_private_columns ) { break; }
			}
		}
		if( $empty_row ) { 
			if( ! $get_empty_items ) {
				unset( $booking_list_items[ $booking_id ] );
			} else {
				if( empty( $booking_list_items[ $booking_id ][ 'tr_class' ] ) ) { $booking_list_items[ $booking_id ][ 'tr_class' ] = ''; }
				$booking_list_items[ $booking_id ][ 'tr_class' ] .= ' bookacti-empty-row';
			}
		}
	}
	
	return $booking_list_items;
}


/**
 * Display a booking list
 * @since 1.7.6
 * @version 1.8.3
 * @param array $filters
 * @param array $columns
 * @param int $per_page
 * @return string
 */
function bookacti_get_user_booking_list( $filters, $columns = array(), $per_page = 10 ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	// Set a counter of bookings list displayed on the same page
	if( empty( $GLOBALS[ 'bookacti_booking_list_count' ] ) ) { $GLOBALS[ 'bookacti_booking_list_count' ] = 0; }
	global $bookacti_booking_list_count;
	++$bookacti_booking_list_count;
	
	// Total number of bookings to display
	$bookings_nb = bookacti_get_number_of_booking_rows( $filters );	
	
	// Pagination
	$page_nb				= ! empty( $_GET[ 'bookacti_booking_list_paged_' . $bookacti_booking_list_count ] ) ? intval( $_GET[ 'bookacti_booking_list_paged_' . $bookacti_booking_list_count ] ) : 1;
	$page_max				= ceil( $bookings_nb / intval( $per_page ) );
	$filters[ 'per_page' ]	= intval( $per_page );
	$filters[ 'offset' ]	= ( $page_nb - 1 ) * $filters[ 'per_page' ];
	
	$booking_list_items = bookacti_get_user_booking_list_items( $filters, $columns );
	
	ob_start();
	?>
	<div id='bookacti-user-booking-list-<?php echo $bookacti_booking_list_count; ?>' class='bookacti-user-booking-list' data-user-id='<?php echo $filters[ 'user_id' ] ? $filters[ 'user_id' ] : ''; ?>' >
		<?php
			echo bookacti_get_user_booking_list_table_html( $booking_list_items, $columns );
		
		if( $page_max > 1 ) { ?>
		<div class='bookacti-user-booking-list-pagination'>
		<?php
			if( $page_nb > 1 ) {
			?>
				<span class='bookacti-user-booking-list-previous-page'>
					<a href='<?php echo esc_url( add_query_arg( 'bookacti_booking_list_paged_' . $bookacti_booking_list_count, ( $page_nb - 1 ) ) ); ?>' class='button'>
						<?php esc_html_e( 'Previous', 'booking-activities' ); ?>
					</a>
				</span>
			<?php
			}
			?>
			<span class='bookacti-user-booking-list-current-page'>
				<span class='bookacti-user-booking-list-page-counter'><strong><?php echo $page_nb; ?></strong><span> / </span><em><?php echo $page_max; ?></em></span>
				<span class='bookacti-user-booking-list-total-bookings'><?php /* translators: %s is the number of bookings */ echo esc_html( sprintf( _n( '%s booking', '%s bookings', $bookings_nb, 'booking-activities' ), $bookings_nb ) ); ?></span>
			</span>
			<?php
			if( $page_nb < $page_max ) {
			?>
				<span class='bookacti-user-booking-list-next-page'>
					<a href='<?php echo esc_url( add_query_arg( 'bookacti_booking_list_paged_' . $bookacti_booking_list_count, ( $page_nb + 1 ) ) ); ?>' class='button'>
						<?php esc_html_e( 'Next', 'booking-activities' ); ?>
					</a>
				</span>
			<?php
			}
		?>
		</div>
		<?php } ?>
	</div>
	<?php
	
	// Include bookings dialogs if they are not already
	if( in_array( 'actions', $columns, true ) ) {
		include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-bookings-dialogs.php' );
	}
	
	return apply_filters( 'bookacti_user_booking_list_html', ob_get_clean(), $booking_list_items, $columns, $filters, $per_page );
}


/**
 * Get the booking list table HTML
 * @since 1.8.0
 * @param array $booking_items
 * @param array $columns
 * @return array
 */
function bookacti_get_user_booking_list_table_html( $booking_items, $columns = array() ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	ob_start();
	?>
	<table class='bookacti-user-booking-list-table'>
		<thead>
			<tr>
			<?php
				$columns_labels = bookacti_get_user_booking_list_columns_labels();
				foreach( $columns as $column_id ) {
				?>
					<th class='bookacti-column-<?php echo sanitize_title_with_dashes( $column_id ); ?>' >
						<div class='bookacti-column-title-<?php echo $column_id; ?>' >
							<?php echo ! empty( $columns_labels[ $column_id ] ) ? esc_html( $columns_labels[ $column_id ] ) : $column_id; ?>
						</div>
					</th>
				<?php
				} 
			?>
			</tr>
		</thead>
		<tbody>
		<?php
			echo bookacti_get_user_booking_list_rows( $booking_items, $columns );
		?>
		</tbody>
	</table>
	<?php
	return apply_filters( 'bookacti_user_booking_list_table_html', ob_get_clean(), $booking_items, $columns );
}


/**
 * Display booking list rows
 * @since 1.7.6
 * @version 1.8.0
 * @param array $booking_list_items
 * @param array $columns
 * @return string
 */
function bookacti_get_user_booking_list_rows( $booking_list_items, $columns = array() ) {
	if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
	
	$columns_labels = bookacti_get_user_booking_list_columns_labels();
	
	ob_start();
	
	// If there are no booking rows
	if( empty( $booking_list_items ) ) {
	?>
		<tr>
			<td colspan='<?php echo esc_attr( count( $columns ) ); ?>'>
				<?php esc_html_e( 'No bookings found.', 'booking-activities' ); ?>
			</td>
		</tr>
	<?php
	} 
	
	// Display rows
	else {
		foreach( $booking_list_items as $list_item ) {
		?>
			<tr class='<?php echo ! empty( $list_item[ 'tr_class' ] ) ? $list_item[ 'tr_class' ] : ''; ?>'>
			<?php
				foreach( $columns as $column_id ) {
					$value			= isset( $list_item[ $column_id ] ) && ( is_string( $list_item[ $column_id ] ) || is_numeric( $list_item[ $column_id ] ) ) ? $list_item[ $column_id ] : '';
					$class_empty	= empty( $value ) ? 'bookacti-empty-column' : '';
					$class_group	= $list_item[ 'booking_type' ] === 'group' ? 'bookacti-booking-group-' . $column_id : '';
					$column_label	= ! empty( $columns_labels[ $column_id ] ) ? $columns_labels[ $column_id ] : $column_id;
				?>
					<td data-column-id='<?php echo esc_attr( $column_id ); ?>' data-column-label='<?php echo esc_attr( $column_label ); ?>' class='bookacti-column-<?php echo $column_id . ' ' . $class_empty; ?>' >
						<div class='bookacti-booking-<?php echo $column_id . ' ' . $class_group; ?>' >
							<?php echo $value; ?>
						</div>
					</td>
				<?php
				}
			?>
			</tr>
		<?php
		}
	}
	
	return apply_filters( 'bookacti_user_booking_list_rows_html', ob_get_clean(), $booking_list_items, $columns );
}


/**
 * Get some booking list rows according to filters
 * @since 1.7.4
 * @version 1.7.6
 * @param string $context
 * @param array $filters
 * @param array $columns
 * @return string
 */
function bookacti_get_booking_list_rows_according_to_context( $context = 'user_booking_list', $filters = array(), $columns = array() ) {
	// Switch language
	if( ! empty( $_REQUEST[ 'locale' ] ) ) { bookacti_switch_locale( $_REQUEST[ 'locale' ] ); }
	
	$rows = '';
	if( $context === 'admin_booking_list' ) {
		$Bookings_List_Table = new Bookings_List_Table();
		$Bookings_List_Table->prepare_items( $filters, true );
		$rows = $Bookings_List_Table->get_rows_or_placeholder();
	} else if( $context === 'user_booking_list' ) {
		if( ! $columns ) { $columns = bookacti_get_user_booking_list_default_columns(); }
		$filters	= bookacti_format_booking_filters( $filters );
		$list_items = bookacti_get_user_booking_list_items( $filters, $columns );
		$rows		= bookacti_get_user_booking_list_rows( $list_items, $columns );
	}
	
	$rows = apply_filters( 'booking_list_rows_according_to_context', $rows, $context, $filters, $columns );
	
	// Restore language
	if( ! empty( $_REQUEST[ 'locale' ] ) ) { bookacti_restore_locale();	}
	
	return $rows;
}


/**
 * Get booking lists by event
 * @since 1.8.0
 * @param array $filters_raw
 * @param array $columns
 * @param array $atts Booking system attributes
 * @return array
 */
function bookacti_get_events_booking_lists( $filters_raw, $columns = array(), $atts = array() ) {
	// Sanitize bookings filters
	$default_filters = array( 
		'status'		=> array( 'pending', 'booked', 'delivered' ),
		'order_by'		=> array( 'event_id', 'event_start' ), 
		'order'			=> 'desc',
		'group_by'		=> 'none',
		'fetch_meta'	=> true
	);
	$sanitized_filters = bookacti_format_booking_filters( wp_parse_args( $filters_raw, $default_filters ) );
	if( ! empty( $filters_raw[ 'display_private_columns' ] ) ) { $sanitized_filters[ 'display_private_columns' ] = 1; }
	
	// Allow plugins to change filters
	$filters = apply_filters( 'bookacti_events_booking_lists_filters', $sanitized_filters, $filters_raw, $columns, $atts );
	
	// Set default columns
	if( ! $columns ) { $columns = bookacti_get_event_booking_list_default_columns(); }
	
	// Get the bookings
	$booking_items = bookacti_get_user_booking_list_items( $filters, $columns );

	// Get the booking lists
	$booking_lists = array();
	if( $booking_items ) {
		// Order the bookings per event
		$ordered_items = array();
		foreach( $booking_items as $booking_item ) {
			$index = $booking_item[ 'event_id' ] . '_' . $booking_item[ 'start_date_raw' ];
			if( ! isset( $ordered_items[ $index ] ) ) { $ordered_items[ $index ] = array(); }
			$ordered_items[ $index ][] = $booking_item;
		}
		// Build the booking list for each event
		foreach( $booking_items as $booking_item ) {
			$index = $booking_item[ 'event_id' ] . '_' . $booking_item[ 'start_date_raw' ];
			if( empty( $ordered_items[ $index ] ) ) { continue; }
			if( ! isset( $booking_lists[ $booking_item[ 'event_id' ] ] ) ) { $booking_lists[ $booking_item[ 'event_id' ] ] = array(); }
			
			$event_booking_list = apply_filters( 'bookacti_event_booking_list_table_html', bookacti_get_user_booking_list_table_html( $ordered_items[ $index ], $columns ), $booking_item[ 'event_id' ], $booking_item[ 'start_date_raw' ], $filters, $filters_raw, $columns, $atts );
			
			$booking_lists[ $booking_item[ 'event_id' ] ][ $booking_item[ 'start_date_raw' ] ] = $event_booking_list;
		}
	}
	
	return apply_filters( 'bookacti_events_booking_lists', $booking_lists, $filters, $filters_raw, $columns, $atts );
}