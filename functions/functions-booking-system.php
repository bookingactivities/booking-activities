<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/***** BOOKING SYSTEM *****/
/**
 * Get a booking system based on given parameters
 * 
 * @since 1.0.0
 * @version 1.1.0
 * 
 * @param array $atts [id, classes, calendars, activities, groups, method]
 * @param boolean $echo Wether to return or directly echo the booking system
 * @return string
 */
function bookacti_get_booking_system( $atts, $echo = false ) {
	
	// Format booking system attributes
	$atts = bookacti_format_booking_system_attributes( $atts );
	
	if( ! $echo ) {
		ob_start();
	}
	
	do_action( 'bookacti_before_booking_form', $atts );
?>
	<div class='bookacti-booking-system-container' id='<?php echo esc_attr( $atts[ 'id' ] . '-container' ); ?>' >
	
		<script>
			
			bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ] = <?php echo json_encode( $atts ); ?>;
		
			<?php 
			// Events related data
			$when_events_load = bookacti_get_setting_value( 'bookacti_general_settings', 'when_events_load' ); 
			if( $when_events_load === 'on_page_load' && $atts[ 'auto_load' ] ) { 
				$groups_events = array();
				if( $atts[ 'group_categories' ] !== false ) { 
					$groups_events		= bookacti_get_groups_events( $atts[ 'calendars' ], $atts[ 'group_categories' ] );
				} 
				
				if( empty( $atts[ 'group_categories' ] ) ) {
					$groups_data		= bookacti_get_groups_of_events_by_template( $atts[ 'calendars' ] );
					$categories_data	= bookacti_get_group_categories_by_template( $atts[ 'calendars' ] );
				} else {
					$groups_data		= bookacti_get_groups_of_events_by_category( $atts[ 'group_categories' ] );
					$categories_data	= bookacti_get_group_categories( $atts[ 'group_categories' ] );
				}
			?>
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'events' ]					= <?php echo json_encode( bookacti_fetch_events( $atts ) ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'activities_data' ]			= <?php echo json_encode( bookacti_get_activities_by_template( $atts[ 'calendars' ], true ) ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'groups_events' ]			= <?php echo json_encode( $groups_events ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'groups_data' ]				= <?php echo json_encode( $groups_data ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'group_categories_data' ]	= <?php echo json_encode( $categories_data ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'settings' ]					= <?php echo json_encode( bookacti_get_mixed_template_settings( $atts[ 'calendars' ] ) ); ?>;	
			<?php } ?>
		</script>
				
		<div class='bookacti-booking-system-inputs'>
			<input type='hidden' name='bookacti_group_id' value='' />
			<input type='hidden' name='bookacti_event_id' value='' />
			<input type='hidden' name='bookacti_event_start' value='' />
			<input type='hidden' name='bookacti_event_end' value='' />
			<?php do_action( 'bookacti_booking_system_inputs', $atts ); ?>
		</div>
		
		<?php do_action( 'bookacti_before_booking_system_title', $atts ); ?>
		
		<div class='bookacti-booking-system-global-title' >
			<?php echo apply_filters( 'bookacti_booking_system_title', '', $atts ); ?>
		</div>
		
		<?php do_action( 'bookacti_before_booking_system', $atts ); ?>
		
		<div id='<?php echo esc_attr( $atts[ 'id' ] ); ?>' class='bookacti-booking-system <?php echo esc_attr( $atts[ 'classes' ] ); ?>' >
			<?php echo bookacti_get_booking_method_html( $atts[ 'method' ], $atts ); ?>
		</div>
		
		<?php do_action( 'bookacti_after_booking_system', $atts ); ?>
		
		<div class='bookacti-picked-events' >
			<div class='bookacti-picked-events-list-title' ></div>
			<ul class='bookacti-picked-events-list' >
				<?php do_action( 'bookacti_picked_events_list', $atts ); ?>
			</ul>
		</div>
		
		<?php do_action( 'bookacti_after_picked_events_list', $atts ); ?>
		
		<div class='bookacti-notices' >
			<?php do_action( 'bookacti_booking_system_errors', $atts ); ?>
		</div>
		
		<?php do_action( 'bookacti_after_booking_system_errors', $atts ); ?>
	</div>
<?php

	do_action( 'bookacti_after_booking_form', $atts );
	
	// Include frontend dialogs if they are not already
	include_once( WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/view/view-booking-system-dialogs.php' );
	
	if( ! $echo ) {
		return ob_get_clean();
	}
}


/**
 * Get available booking methods
 * 
 * @return string
 */
function bookacti_get_available_booking_methods(){
	$available_booking_methods = array(
		'calendar'	=> __( 'Calendar', BOOKACTI_PLUGIN_NAME )
	);
	return apply_filters( 'bookacti_available_booking_methods', $available_booking_methods );
}


/**
 * Get booking method HTML elemnts
 * 
 * @since 1.1.0
 * 
 * @param string $method
 * @param array $booking_system_attributes
 * @return string $html_elements
 */
function bookacti_get_booking_method_html( $method, $booking_system_attributes ) {
	
	$available_booking_methods = bookacti_get_available_booking_methods();
	if( $method === 'calendar' || ! in_array( $method, array_keys( $available_booking_methods ), true ) ) {
		$html_elements = bookacti_retrieve_calendar_elements( $booking_system_attributes );
	} else {
		$html_elements = apply_filters( 'bookacti_get_booking_method_html', '', $method, $booking_system_attributes );
	}
	
	return $html_elements;
}


/**
 * Retrieve Calendar booking system HTML to include in the booking system
 * 
 * @since 1.0.0
 * @version 1.2.0
 * 
 * @param array $booking_system_atts
 * @return string
 */
function bookacti_retrieve_calendar_elements( $booking_system_atts ) {
	
	$default_calendar_title	= esc_html( bookacti_get_message( 'calendar_title' ) );
	$calendar_title			= apply_filters( 'bookacti_calendar_title', $default_calendar_title, $booking_system_atts );
	
	$before_calendar_title	= apply_filters( 'bookacti_before_calendar_title', '', $booking_system_atts );
	$before_calendar		= apply_filters( 'bookacti_before_calendar', '', $booking_system_atts );
	$after_calendar			= apply_filters( 'bookacti_after_calendar', '', $booking_system_atts );
	
	return
	
	$before_calendar_title
			
	. "<div class='bookacti-calendar-title bookacti-booking-system-title' >"
	.	$calendar_title
	. "</div>"
	
	. $before_calendar
	
	. "<div class='bookacti-calendar' ></div>"
			
	. $after_calendar;
}


/**
 * Check booking system attributes and format them to be correct
 * 
 * @version 1.2.0
 * 
 * @param array $atts [id, classes, calendars, activities, groups, method, url, button]
 * @param string $shortcode
 * @return type
 */
function bookacti_format_booking_system_attributes( $atts = array(), $shortcode = '' ) {
	
	// Set default value
	$defaults = apply_filters( 'bookacti_booking_system_default_attributes', array(
        'id'					=> '',
        'classes'				=> '',
        'calendars'				=> array(),
        'activities'			=> array(),
        'group_categories'		=> false,
        'groups_only'			=> 0,
        'groups_single_events'	=> 0,
        'method'				=> 'calendar',
		'url'					=> '',
		'button'				=> bookacti_get_message( 'booking_form_submit_button' ),
		'auto_load'				=> 1,
		'past_events'			=> 0,
		'context'				=> 'frontend'
    ) );
	
	// Replace empty mandatory values by default
	$atts = shortcode_atts( $defaults, $atts, $shortcode );
	
	// Format comma separated lists into arrays of integers
	if( is_string( $atts[ 'calendars' ] ) || is_numeric( $atts[ 'calendars' ] ) ) {
		$atts[ 'calendars' ] = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'calendars' ] ) ) );
	}
	
	if( in_array( $atts[ 'activities' ], array( true, 'all', 'true', 'yes', 'ok' ), true ) ) {
		$atts[ 'activities' ] = array();
		
	} else if( is_string( $atts[ 'activities' ] ) || is_numeric( $atts[ 'activities' ] ) ) {
		$atts[ 'activities' ] = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'activities' ] ) ) );
	}
	
	if( in_array( $atts[ 'group_categories' ], array( true, 'all', 'true', 'yes', 'ok' ), true ) ) {
		$atts[ 'group_categories' ] = array();
	
	} else if( in_array( $atts[ 'group_categories' ], array( false, 'none', 'false', 'no' ), true )
	|| ( empty( $atts[ 'group_categories' ] ) && ! is_array( $atts[ 'group_categories' ] ) ) ) { 
		$atts[ 'group_categories' ] = false;
	
		
	} else if( is_string( $atts[ 'group_categories' ] ) || is_numeric( $atts[ 'group_categories' ] ) ) {
		$atts[ 'group_categories' ] = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'group_categories' ] ) ) );
	}
	
	// Remove duplicated values
	$atts[ 'calendars' ]	= array_unique( $atts[ 'calendars' ] );
	$atts[ 'activities' ]	= array_unique( $atts[ 'activities' ] );
	
	// Check if desired templates exist
	$available_templates = bookacti_fetch_templates( true );
	foreach( $atts[ 'calendars' ] as $i => $template_id ) {
		$is_existing = false;
		foreach( $available_templates as $available_template ) {
			if( $available_template->id == intval( $template_id ) ) {
				$is_existing = true;
				break;
			}
		}
		if( ! $is_existing ) {
			unset( $atts[ 'calendars' ][ $i ] );
		}
	}
	
	// Check if desired activities exist
	if( ! empty( $atts[ 'calendars' ] ) ) {
		$available_activities = bookacti_get_activity_ids_by_template( $atts[ 'calendars' ], false );
		foreach( $atts[ 'activities' ] as $i => $activity_id ) {
			if( ! in_array( intval( $activity_id ), $available_activities ) ) {
				unset( $atts[ 'activities' ][ $i ] );
			}
		}
	} else {
		$available_activities = bookacti_fetch_activities();
		foreach( $atts[ 'activities' ] as $i => $activity_id ) {
			$is_existing = false;
			foreach( $available_activities as $available_activity ) {
				if( $available_activity->id == intval( $activity_id ) ) {
					$is_existing = true;
					break;
				}
			}
			if( ! $is_existing ) {
				unset( $atts[ 'activities' ][ $i ] );
			}
		}
	}
	
	// Check if desired groups exist
	if( is_array( $atts[ 'group_categories' ] ) ) { 
		// Remove duplicated values
		$atts[ 'group_categories' ] = array_unique( $atts[ 'group_categories' ] ); 
		
		$available_category_ids = bookacti_get_group_category_ids_by_template( $atts[ 'calendars' ] );
		foreach( $atts[ 'group_categories' ] as $i => $category_id ) {
			foreach( $available_category_ids as $available_category_id ) {
				if( $available_category_id == intval( $category_id ) ) {
					$is_existing = true;
					break;
				}
			}
			if( ! $is_existing ) {
				unset( $atts[ 'group_categories' ][ $i ] );
			}
		}
	}
	
	// Sanitize booleans
	$booleans_to_check = array( 'groups_only', 'groups_single_events', 'auto_load', 'past_events' );
	foreach( $booleans_to_check as $key ) {
		$atts[ $key ] = in_array( $atts[ $key ], array( 1, '1', true, 'true', 'yes', 'ok' ), true ) ? 1 : 0;
	}
	
	// If booking method is set to 'site', get the site default
	$atts[ 'method' ] = esc_attr( $atts[ 'method' ] );
	if( $atts[ 'method' ] === 'site' ) {
		$atts[ 'method' ] = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
	}
	
	// Check if desired booking method is registered
	$available_booking_methods = bookacti_get_available_booking_methods();
	if( ! in_array( $atts[ 'method' ], array_keys( $available_booking_methods ), true ) ) {
		$atts[ 'method' ] = 'calendar';
	}
	
	// Give a random id if not supplied
	if( empty( $atts[ 'id' ] ) ) { 
		$atts[ 'id' ] = rand(); 
	}
	if( substr( strval( $atts[ 'id' ] ), 0, 9 ) !== 'bookacti-' ) {
		$atts[ 'id' ]	= 'bookacti-' . $atts[ 'id' ];
	}
	$atts[ 'id' ]	= esc_attr( $atts[ 'id' ] );
	
	// Format classes
	$atts[ 'classes' ]	= ! empty( $atts[ 'classes' ] )	? esc_attr( $atts[ 'classes' ] ) : '';
	
	// Sanitize redirect URL
	$atts[ 'url' ] = esc_url( $atts[ 'url' ] );
	
	// Sanitize submit button label
	$atts[ 'button' ] = esc_html( sanitize_text_field( $atts[ 'button' ] ) );
	
	// Make sure context is valid
	$atts[ 'context' ] = in_array( $atts[ 'context' ], array( 'frontend', 'editor', 'booking_page' ), true ) ? $atts[ 'context' ] : 'frontend';
	
	
	return apply_filters( 'bookacti_formatted_booking_system_attributes', $atts, $shortcode );
}


/**
 * Sanitize arguments used to fetch events on the booking system
 * 
 * @param array $args
 * @return array
 */
function bookacti_sanitize_arguments_to_fetch_events( $args ) {
	// Sanitize arguments
	$default_args = array(
		'calendars'			=> array(),
		'activities'		=> array(),
		'group_categories'	=> array(),
		'groups_only'		=> false,
		'past_events'		=> false,
		'context'			=> 'frontend'
	);

	$sanitized_args = array();
	foreach( $default_args as $default_key => $default_value ) {
		
		if( ! isset( $args[ $default_key ] ) ) {
			$sanitized_args[ $default_key ] = $default_value;
			continue;
		}
		
		switch ( $default_key ) {
			case 'calendars':
			case 'activities':
			case 'group_categories':
				$sanitized_args[ $default_key ] = ! empty( $args[ $default_key ] ) && is_array( $args[ $default_key ] ) ? $args[ $default_key ] : $default_value;
				break;
			case 'groups_only':
			case 'past_events':
				if( in_array( $args[ $default_key ], array( 0, '0', false, 'false' ), true ) ) {
					$sanitized_args[ $default_key ] = false;
				}
				else if( in_array( $args[ $default_key ], array( 1, '1', true, 'true' ), true ) ) {
					$sanitized_args[ $default_key ] = true;
				}
				else {
					$sanitized_args[ $default_key ] = $default_value;
				}
				break;
			case 'context':
				$sanitized_args[ $default_key ] = in_array( $args[ $default_key ], array( 'frontend', 'booking_page', 'editor' ), true ) ? $args[ $default_key ] : $default_value;
				break;
			default:
				$sanitized_args[ $default_key ] = $default_value;
		}
	}

	return $sanitized_args;
}


/**
 * Validate booking form (verify the info of the selected event before booking it)
 * 
 * @version 1.1.0
 * @param int $group_id
 * @param int $event_id
 * @param string $event_start Start datetime of the event to check (format 2017-12-31T23:59:59)
 * @param string $event_end End datetime of the event to check (format 2017-12-31T23:59:59)
 * @param int $quantity Desired number of bookings
 * @return array
 */
function bookacti_validate_booking_form( $group_id, $event_id, $event_start, $event_end, $quantity ) {
	
	if( $group_id === 'single' ) {
		$availability	= bookacti_get_event_availability( $event_id, $event_start, $event_end );
		$exists			= bookacti_is_existing_event( $event_id, $event_start, $event_end );
		$is_in_range	= bookacti_is_event_in_its_template_range( $event_id, $event_start, $event_end );
	
	} else if( is_numeric( $group_id ) ) {
		
		$availability	= bookacti_get_group_of_events_availability( $group_id );
		$exists			= bookacti_is_existing_group_of_events( $group_id );
		$is_in_range	= bookacti_is_group_of_events_in_its_template_range( $group_id );
	}
	
	// Init boolean test variables
	$is_event				= false;
	$is_qty_sup_to_avail	= false;
	$is_qty_sup_to_0		= false;
	$can_book				= false;

	// Make the tests and change the booleans
	if( $group_id !== '' && $event_id !== '' && $event_start !== '' && $event_end !== '' )	{ $is_event = true; }
	if( intval( $quantity ) > 0 )															{ $is_qty_sup_to_0 = true; }
	if( intval( $availability ) - intval( $quantity ) < 0 )									{ $is_qty_sup_to_avail = true; }
	
	if( $is_event && $exists && $is_in_range && $is_qty_sup_to_0 && ! $is_qty_sup_to_avail ) { $can_book = true; }

	if( $can_book ) {
		$validated['status'] = 'success';
	} else {
		$validated['status'] = 'failed';
		if( ! $exists ) {
			$validated['error'] = 'do_not_exist';
			$validated['message'] = $group_id === 'single' ? __( "The event doesn't exist, please pick an event and try again.", BOOKACTI_PLUGIN_NAME ) : __( "The group of events doesn't exist, please pick an event and try again.", BOOKACTI_PLUGIN_NAME );
		} else if( ! $is_in_range ) {
			$validated['error'] = 'out_of_range';
			$validated['message'] = $group_id === 'single' ? __( 'The event is out of calendar range, please pick an event and try again.', BOOKACTI_PLUGIN_NAME ) :  __( 'The group of events is out of calendar range, please pick an event and try again.', BOOKACTI_PLUGIN_NAME );
		} else if( ! $is_event ) {
			$validated['error'] = 'no_event_selected';
			$validated['message'] = __( "You haven't picked any event. Please pick an event first.", BOOKACTI_PLUGIN_NAME );
		} else if( ! $is_qty_sup_to_0 ) {
			$validated['error'] = 'qty_inf_to_0';
			$validated['message'] = __( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', BOOKACTI_PLUGIN_NAME );
		} else if( $is_qty_sup_to_avail ) {
			$validated['error'] = 'qty_sup_to_avail';
			$validated['availability'] = $availability;
			$validated['message'] = sprintf( __( 'You want to make %1$s bookings but only %2$s are available. Please pick another event or lower the quantity.', BOOKACTI_PLUGIN_NAME ), $quantity, $availability );
		} else {
			$validated['error'] = 'failed';
			$validated['message'] = __( 'An error occurred, please try again.', BOOKACTI_PLUGIN_NAME );
		}
	}

	return apply_filters( 'bookacti_validate_booking_form', $validated, $group_id, $event_id, $event_start, $event_end, $quantity );
}


/**
 * Check if an event or an occurence exists
 * 
 * @version 1.2.2
 * 
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return boolean
 */
function bookacti_is_existing_event( $event_id, $event_start = NULL, $event_end = NULL ) {

	$event = bookacti_get_event_by_id( $event_id );

	$is_existing_event = false;
	if( $event ) {
		if( $event->repeat_freq && $event->repeat_freq !== 'none' ) {
			$is_existing_event = bookacti_is_existing_occurence( $event, $event_start, $event_end );
		} else {
			$is_existing_event = bookacti_is_existing_single_event( $event_id, $event_start, $event_end );
		}
	}

	return $is_existing_event;
}


/**
 * Check if the occurence exists
 * 
 * @version 1.2.2
 * 
 * @param object|int $event
 * @param string $event_start
 * @param string $event_end
 * @return boolean
 */
function bookacti_is_existing_occurence( $event, $event_start, $event_end = NULL ) {
	// Get the event
	if( is_numeric( $event ) ) {
		$event = bookacti_get_event_by_id( $event );
	}

	// Check if the event is well repeated
	if( ! $event 
	||  ! $event_start
	||  ! in_array( $event->repeat_freq, array( 'daily', 'weekly', 'monthly' ), true )
	||  ! $event->repeat_from || $event->repeat_from === '0000-00-00' 
	||  ! $event->repeat_to || $event->repeat_to === '0000-00-00' ) { return false; }

	// Check if the times match
	if( $event_start ) { if( substr( $event_start, -8 ) !== substr( $event->start, -8 ) ) { return false; } }
	if( $event_end ) { if( substr( $event_end, -8 ) !== substr( $event->end, -8 ) ) { return false; } }
	
	// Check if the days match
	$repeat_from	= DateTime::createFromFormat( 'Y-m-d', substr( $event->repeat_from, 0, 10 ) );
	$repeat_to		= DateTime::createFromFormat( 'Y-m-d', substr( $event->repeat_to, 0, 10 ) );
	$event_datetime	= DateTime::createFromFormat( 'Y-m-d', substr( $event->start, 0, 10 ) );
	$occurence		= DateTime::createFromFormat( 'Y-m-d', substr( $event_start, 0, 10 ) );
	$repeat_from_timestamp	= intval( $repeat_from->format( 'U' ) );
	$repeat_to_timestamp	= intval( $repeat_to->format( 'U' ) );
	$occurence_timestamp	= intval( $occurence->format( 'U' ) );
	
	// Check if occurence is between repeat_from and repeat_to
	if( $occurence_timestamp < $repeat_from_timestamp || $occurence_timestamp > $repeat_to_timestamp ) { return false; }
	
	// Check if the weekdays match
	if( $event->repeat_freq === 'weekly' ) {
		if( $occurence->format( 'w' ) !== $event_datetime->format( 'w' ) ) { return false; }
	}
	
	// Check if the monthdays match
	if( $event->repeat_freq === 'monthly' ) {
		$is_last_day_of_month = $event_datetime->format( 't' ) === $event_datetime->format( 'd' );
		if( ! $is_last_day_of_month && $occurence->format( 'd' ) !== $event_datetime->format( 'd' ) ) { return false; }
		else if ( $is_last_day_of_month && $occurence->format( 't' ) !== $occurence->format( 'd' ) ) { return false; }
	}
	
	return true;
}


/**
 * Check if the group of event exists
 * 
 * @since 1.1.0
 * 
 * @param int $group_id
 * @return boolean
 */
function bookacti_is_existing_group_of_events( $group_id ) {

	// Try to retrieve the group and check the result
	$group = bookacti_get_group_of_events( $group_id );
	return ! empty( $group );
}


// Convert minutes to days, hours and minutes
function bookacti_seconds_to_explode_time( $seconds ) {
	
    $dtF = new DateTime( "@0" );
    $dtT = new DateTime( "@$seconds" );
    
	$time = array();
	$time['days']		= $dtF->diff($dtT)->format('%a');
	$time['hours']		= $dtF->diff($dtT)->format('%h');
	$time['minutes']	= $dtF->diff($dtT)->format('%i');
	$time['seconds']	= $dtF->diff($dtT)->format('%s');
	
	return $time;
}


// Get html enclosing booking dates
function bookacti_get_booking_dates_html( $booking ) {
	$formatted_dates = bookacti_format_booking_dates( $booking->event_start, $booking->event_end );
	$html = "
	<span class='bookacti-booking-start' >" . esc_html( $formatted_dates[ 'start' ] ) . "</span>
	<span class='bookacti-booking-date-separator' >" . esc_html( $formatted_dates[ 'separator' ] ) . "</span>
	<span class='bookacti-booking-end " . esc_attr( $formatted_dates[ 'to_hour_or_date' ] ) . "' >" . esc_html( $formatted_dates[ 'end' ] ) . "</span>";
	
	return $html;
}




/***** EVENTS *****/

/**
 * Create repeated events
 * 
 * @version 1.1.0
 * @param object $event Event data
 * @param array $shared_data	 Event data shared by every occurences of the event
 * @param array $args Additional data
 * @return array
 */
function bookacti_create_repeated_events( $event, $shared_data = array(), $args ) {
	
	// Sanitize arguments
	$args = bookacti_sanitize_arguments_to_fetch_events( $args );
	
	// Set current datetime
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$current_datetime_object = new DateTime( 'now', new DateTimeZone( $timezone ) );
	
    if( empty( $shared_data ) ) { 
		$shared_data = array(
			'id'				=> $event->event_id,
			'template_id'		=> $event->template_id,
			'title'				=> apply_filters( 'bookacti_translate_text', $event->title ),
			'multilingual_title'=> $event->title,
			'allDay'			=> false,
			'color'				=> $event->color,
			'activity_id'		=> $event->activity_id,
			'availability'		=> $event->availability
		);
		if( isset( $event->is_resizable ) && isset( $event->event_settings ) && isset( $event->activity_settings ) ) {
			$shared_data['durationEditable']	= $event->is_resizable;
			$shared_data['event_settings']		= maybe_unserialize( $event->event_settings );
			$shared_data['activity_settings']	= maybe_unserialize( $event->activity_settings );
		}
	}
	
	$started_events_bookable = bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
	
	//Determine the number of day to add according to the repetition frequence
    $to_add = bookacti_units_to_add_to_repeat_event( $event );
	
    //Get event duration
    $event_start	= DateTime::createFromFormat('Y-m-d H:i:s', $event->start );
    $event_end		= DateTime::createFromFormat('Y-m-d H:i:s', $event->end );
    $event_duration = $event_start->diff( $event_end );

    //The first event created will begin at the 'repeat from' date and at the 'event.start' hour
    $start_hours    = substr( $event->start, 11 );
    $start_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $event->repeat_from . ' ' . $start_hours );
    //It will last the same duration as the event
    $end_datetime   = clone $start_datetime;
    $end_datetime   = $end_datetime->add( $event_duration );

    //Compute the timestamp of the begin and the end of the very first event of the repetition period
    $start_timestamp= $start_datetime->format('U');
    $end_timestamp  = $end_datetime->format('U');
	
    //Compute the number of days during the repetition period
    $repeat_from	= DateTime::createFromFormat('Y-m-d', $event->repeat_from );
    $repeat_to		= DateTime::createFromFormat('Y-m-d', $event->repeat_to );
    $interval       = $repeat_from->diff( $repeat_to )->days;

    //Create the event every X days ($days_to_add) from the begining of the repetition period
    $repeated_events_array = array();
    
        if( $to_add['unit'] === 'days' )    { $iteration = $interval / $to_add['number']; }
    elseif( $to_add['unit'] === 'months' )  { $iteration = $interval / ( $to_add['number'] * 30.5 ); }
		
	$event_start		= new DateTime( '@' . $start_timestamp );
    $event_end			= new DateTime( '@' . $end_timestamp );
	$interval_to_add	= DateInterval::createFromDateString( $to_add['number'] . ' ' . $to_add['unit'] );
	
    for( $i=0; $i <= $iteration; $i++ ) {
		
        $is_exception	= bookacti_is_repeat_exception( $event->event_id, date( 'Y-m-d', $event_start->format( 'U' ) ) );
        $has_started	= $event_start->getTimestamp() < ( $current_datetime_object->getTimestamp() + $current_datetime_object->getOffset() );
        $has_ended		= $event_end->getTimestamp() < ( $current_datetime_object->getTimestamp() + $current_datetime_object->getOffset() );
		$is_in_range	= bookacti_is_event_in_its_template_range( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') );
		$is_booked		= bookacti_get_number_of_bookings( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') ) > 0;
		$category_ids	= bookacti_get_event_group_category_ids( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') );
		$is_in_category	= empty( $args[ 'group_categories' ] ) ? true : array_intersect( $category_ids, $args[ 'group_categories' ] );
		
        if( ( ( ! $args[ 'groups_only' ]																		// If single events are displayed, do not care about categories
			||    $args[ 'groups_only' ] && $is_in_category && ! empty( $category_ids ) )						// Else, filter events by category
			) && (
				$args[ 'context' ] === 'editor'																	// Show all events on templates 
			||  $args[ 'context' ] === 'booking_page' && $is_exception == 0 && ( $is_in_range || $is_booked )	// If we are on booking page, show booked events even if they are out of range
			||  $args[ 'past_events' ] && $is_exception == 0 && $is_in_range									// If we also fetch past events, show all events but those wich are on an exception
			||  ( $args[ 'context' ] !== 'editor' && $is_exception == 0 && $is_in_range							// Don't show exception on frontend (on editor, it is done on event render)
				&& ( ! $has_started																				// Don't show started events on frontend
					|| ( $started_events_bookable && $has_started && ! $has_ended ) )							// Show in progress events on frontend if user decides so
				) 
			)
		  ) {
			
			$event_array = array(
				'start'			=> $event_start->format('Y-m-d H:i:s'),
				'end'			=> $event_end->format('Y-m-d H:i:s'),
				'bookings'		=> bookacti_get_number_of_bookings( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') )
			);
			$event_array = array_merge( $shared_data, $event_array );
			
            $repeated_events_array[] = $event_array;
        }
        
        $event_start->add( $interval_to_add );
        $event_end->add( $interval_to_add );
    }

    return $repeated_events_array;
}


// Determine the number of day or month to add according to the repetition frequence
function bookacti_units_to_add_to_repeat_event( $event ) {
    $to_add = array( 'number' => 0, 'unit' => 'days' );
    
    if( $event->repeat_freq === 'daily' )   { $to_add['number'] = 1; $to_add['unit'] = 'days'; }
    
    if( $event->repeat_freq === 'weekly' )  { 
        $weekday_event  = date( 'N', strtotime( $event->start ) );
        $weekday_from   = date( 'N', strtotime( $event->repeat_from ) );
        $new_repeat_from_datetime   = DateTime::createFromFormat('Y-m-d', $event->repeat_from );
        $new_repeat_from_timestamp  = $new_repeat_from_datetime->format( 'U' );

        //Add one day to 'repeat-from' date until the weekday is the same as the event and set it as the new repat-from date
        $i=0;
        while ( $weekday_event !== $weekday_from ) {
            $weekday_from = date('N', strtotime('+' . $i . 'days', $new_repeat_from_timestamp ) );
            $event->repeat_from = date('Y-m-d', strtotime('+' . $i . 'days', $new_repeat_from_timestamp ) );
            $i++;
        }

        $to_add['number']   = 7;
        $to_add['unit']     = 'days';  
    }
    
    if( $event->repeat_freq === 'monthly' ) {
        $event_start_datetime       = DateTime::createFromFormat( 'Y-m-d H:i:s', $event->start );
        $new_repeat_from_datetime   = DateTime::createFromFormat( 'Y-m-d', $event->repeat_from );
        $event_start_timestamp      = $event_start_datetime->format( 'U' );
        $new_repeat_from_timestamp  = $new_repeat_from_datetime->format( 'U' );
		
        //Substract one month to the start date of the event until the 'repeat-from' date is reach
		$interval_to_substract = DateInterval::createFromDateString( '1 month' );
        do {
            $event->repeat_from = $event_start_datetime->format( 'Y-m-d' );
            //Set the first occurence of the event as the new repeat-from date
			$event_start_datetime->sub( $interval_to_substract );
			$event_start_timestamp = $event_start_datetime->format( 'U' );
        }
        while ( $event_start_timestamp > $new_repeat_from_timestamp );

        $to_add['number']   = 1;
        $to_add['unit']     = 'months';  
    }
    
    return $to_add;
}


/**
 * Build a user-friendly events list
 * 
 * @since 1.1.0
 * @version 1.2.1
 * 
 * @param array $booking_events
 * @param int|string $quantity
 * @param string $locale Optional. Default to site locale.
 * @return string
 */
function bookacti_get_formatted_booking_events_list( $booking_events, $quantity = 'hide', $locale = 'site' ) {
	
	if( ! $booking_events ) { return false; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) {
		$locale = bookacti_get_site_locale();
	}
	
	// Format $events
	$formatted_events = array();
	foreach( $booking_events as $booking_event ) {
		
		$booking_quantity = '';
		if( isset( $booking_event->quantity ) ) {
			$booking_quantity = $booking_event->quantity;
		} else if( $quantity && is_numeric( $quantity ) ) {
			$booking_quantity = intval( $quantity );
		}
		
		$formatted_events[] = array( 
			'title'		=> isset( $booking_event->title )	? $booking_event->title : '',
			'start'		=> isset( $booking_event->start )	? bookacti_sanitize_datetime( $booking_event->start )	: isset( $booking_event->event_start )	? bookacti_sanitize_datetime( $booking_event->event_start )	: '',
			'end'		=> isset( $booking_event->end )		? bookacti_sanitize_datetime( $booking_event->end )		: isset( $booking_event->event_end )	? bookacti_sanitize_datetime( $booking_event->event_end )	: '',
			'quantity'	=> $booking_quantity
		);
	}
	
	$events_list = '';
	foreach( $formatted_events as $event ) {
		
		// Format the event duration
		$event_duration = '';
		if( $event[ 'start' ] && $event[ 'end' ] ) {
			
			$event_start = bookacti_format_datetime( $event[ 'start' ] );
			
			// Format differently if the event start and end on the same day
			$start_and_end_same_day	= substr( $event[ 'start' ], 0, 10 ) === substr( $event[ 'end' ], 0, 10 );
			if( $start_and_end_same_day ) {
				/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
				$event_end = date_i18n( __( 'h:i a', BOOKACTI_PLUGIN_NAME ), strtotime( $event[ 'end' ] ) );
				$event_end = ! mb_check_encoding( $event_end, 'UTF-8' ) ? utf8_encode( $event_end ) : $event_end;
			} else {
				$event_end = bookacti_format_datetime( $event[ 'end' ] );
			}
			
			$class = $start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
			// Place an arrow between start and end
			$event_duration = '<span class="bookacti-booking-event-start" >' . $event_start . '</span>'
							. '<span class="bookacti-booking-event-date-separator ' . $class . '" >' . ' &rarr; ' . '</span>'
							. '<span class="bookacti-booking-event-end ' . $class . '" >' . $event_end . '</span>';
		}
		
		// Add an element to event list if there is at least a title or a duration
		if( $event[ 'title' ] || $event_duration ) {
			$events_list .= '<li>';
			
			if( $event[ 'title' ] ) {
				$events_list .= '<span class="bookacti-booking-event-title" >' . apply_filters( 'bookacti_translate_text', $event[ 'title' ], $locale ) . '</span>';
				if( $event_duration ) {
					$events_list .= '<span class="bookacti-booking-event-title-separator" >' . ' - ' . '</span>';
				}
			}
			if( $event_duration ) {
				$events_list .= $event_duration;
			}
			
			if( $event[ 'quantity' ] && $quantity !== 'hide' ) {
				$events_list .= '<span class="bookacti-booking-event-quantity-separator" >' . ' x' . '</span>';
				$events_list .= '<span class="bookacti-booking-event-quantity" >' . $event[ 'quantity' ] . '</span>';
			}
			
			$events_list .= '</li>';
		}
		
	}
	
	// Wrap the list only if it is not empty
	if( ! empty( $events_list ) ) {
		$events_list = '<ul class="bookacti-booking-events-list" >' . $events_list . '</ul>';
	}
	
	return $events_list;
}



// GROUPS OF EVENTS

	/**
	 * Get group of events availability (= the lowest availability among its events)
	 * 
	 * @param int $event_group_id
	 * @return int
	 */
	function bookacti_get_group_of_events_availability( $event_group_id ) {

		$events = bookacti_get_group_events( $event_group_id, true );

		$max = 999999999; // Any big int
		foreach( $events as $event ) {
			$availability = bookacti_get_event_availability( $event[ 'id' ], $event[ 'start' ], $event[ 'end' ] );
			if( $availability < $max ) {
				$max = $availability;
			}
		}

		return $max;
	}
	
	
	/**
	 * Book all events of a group
	 * 
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param int $quantity
	 * @param string $state
	 * @param string $expiration_date
	 * @return int|boolean
	 */
	function bookacti_book_group_of_events( $user_id, $event_group_id, $quantity, $state = 'booked', $expiration_date = NULL ) {
				
		// Insert the booking group
		$booking_group_id = bookacti_insert_booking_group( $user_id, $event_group_id, $state );
		
		if( empty( $booking_group_id ) ) {
			return false;
		}
		
		// Make sure quantity isn't over group availability
		$max_quantity	= bookacti_get_group_of_events_availability( $event_group_id );
		if( $quantity > $max_quantity ) {
			$quantity = $max_quantity;
		}
		
		// Insert bookings
		$events = bookacti_get_group_events( $event_group_id );
		foreach( $events as $event ) {
			bookacti_insert_booking( $user_id, $event[ 'id' ], $event[ 'start' ], $event[ 'end' ], $quantity, $state, $expiration_date, $booking_group_id );
		}
		
		return $booking_group_id;
	}
	
	
	/**
	 * Get categories of groups where an event is included
	 * 
	 * @param int $id
	 * @param string $start
	 * @param string $end
	 * @param boolean $active_only Whether to get the group of events even if the link between the desired event and this group is inactive
	 */
	function bookacti_get_event_group_category_ids( $id, $start, $end, $active_only = true ) {
		
		$groups = bookacti_get_event_groups( $id, $start, $end, $active_only );
		
		$categories = array();
		if( ! empty( $groups ) ) {
			foreach( $groups as $group ) {
				$categories[] = $group->category_id;
			}
		}
		
		return $categories;
	}
	
	
	/**
	 * Get events of a group
	 * 
	 * @global wpdb $wpdb
	 * @param int $group_id
	 * @param boolean $fetch_inactive_events
	 * @return array|false
	 */
	function bookacti_get_group_events( $group_id, $fetch_inactive_events = false ) {
		
		if( empty( $group_id ) ) {
			return false;
		}
		
		if( is_array( $group_id ) ) {
			$group_id = $group_id[ 0 ];
		}
		
		if( ! is_numeric( $group_id ) ) {
			return false;
		}
		
		$group_id = intval( $group_id );
		
		$groups_events = bookacti_get_groups_events( array(), array(), $group_id, $fetch_inactive_events );
		
		return $groups_events[ $group_id ];
	}