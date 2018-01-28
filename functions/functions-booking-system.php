<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/***** BOOKING SYSTEM *****/
/**
 * Get a booking system based on given parameters
 * 
 * @version 1.3.0
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
				
				$templates_data		= bookacti_get_mixed_template_data( $atts[ 'calendars' ], $atts[ 'past_events' ] );
				$events_interval	= bookacti_get_new_interval_of_events( $templates_data );
				$user_ids			= array();
				
				if( $atts[ 'groups_only' ] ) {
					$events			= bookacti_fetch_grouped_events( $atts[ 'calendars' ], $atts[ 'activities' ], array(), $atts[ 'group_categories' ], $atts[ 'past_events' ], $events_interval );
				} else if( $atts[ 'bookings_only' ] ) {
					$events			= bookacti_fetch_booked_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'status' ], $atts[ 'user_id' ], $atts[ 'past_events' ], $events_interval );
					$user_ids		= $atts[ 'user_id' ];
				} else {
					$events			= bookacti_fetch_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'past_events' ], $events_interval );
				}
				
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
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'events' ]					= <?php echo json_encode( $events[ 'events' ] ? $events[ 'events' ] : array() ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'events_data' ]				= <?php echo json_encode( $events[ 'data' ] ? $events[ 'data' ] : array() ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'events_interval' ]			= <?php echo json_encode( $events_interval ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'exceptions' ]				= <?php echo json_encode( bookacti_get_exceptions( $atts[ 'calendars' ] ) ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'bookings' ]					= <?php echo json_encode( bookacti_get_number_of_bookings_by_events( $atts[ 'calendars' ], array(), $user_ids ) ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'activities_data' ]			= <?php echo json_encode( bookacti_get_activities_by_template( $atts[ 'calendars' ], true ) ); ?>;
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'groups_events' ]			= <?php echo json_encode( $groups_events ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'groups_data' ]				= <?php echo json_encode( $groups_data ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'group_categories_data' ]	= <?php echo json_encode( $categories_data ); ?>;	
				bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ][ 'template_data' ]			= <?php echo json_encode( $templates_data ); ?>;	
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
 * @version 1.3.0
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
        'bookings_only'			=> 0,
        'status'				=> array(),
        'user_id'				=> 0,
        'method'				=> 'calendar',
		'url'					=> '',
		'button'				=> bookacti_get_message( 'booking_form_submit_button' ),
		'auto_load'				=> 1,
		'past_events'			=> 0
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
	$available_template_ids = array_keys( bookacti_fetch_templates( array(), true ) );
	foreach( $atts[ 'calendars' ] as $i => $template_id ) {
		$is_existing = false;
		foreach( $available_template_ids as $available_template_id ) {
			if( $available_template_id == intval( $template_id ) ) {
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
	$booleans_to_check = array( 'bookings_only', 'groups_only', 'groups_single_events', 'auto_load', 'past_events' );
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
	
	// Sanitize user id
	$atts[ 'user_id' ] = esc_attr( $atts[ 'user_id' ] );
	if( $atts[ 'user_id' ] === 'current' ) { $atts[ 'user_id' ] = get_current_user_id(); }
	
	// Sanitize booking status
	if( is_string( $atts[ 'status' ] ) ) {
		$atts[ 'status' ] = array_map( 'sanitize_title_with_dashes', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'status' ] ) ) );
	}
	
	// Check if desired status are registered
	$available_booking_status = array_keys( bookacti_get_booking_state_labels() );
	
	foreach( $atts[ 'status' ] as $i => $status ) {
		if( ! in_array( $status, $available_booking_status, true ) ) {
			unset( $atts[ 'status' ][ $i ] );
		}
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
	
	
	return apply_filters( 'bookacti_formatted_booking_system_attributes', $atts, $shortcode );
}


/**
 * Validate booking form (verify the info of the selected event before booking it)
 * 
 * @version 1.3.0
 * @param int $group_id
 * @param int $event_id
 * @param string $event_start Start datetime of the event to check (format 2017-12-31T23:59:59)
 * @param string $event_end End datetime of the event to check (format 2017-12-31T23:59:59)
 * @param int $quantity Desired number of bookings
 * @return array
 */
function bookacti_validate_booking_form( $group_id, $event_id, $event_start, $event_end, $quantity ) {
	
	if( $group_id === 'single' ) {
		$event			= bookacti_get_event_by_id( $event_id );
		$title			= apply_filters( 'bookacti_translate_text', $event->title );
		
		$exists			= bookacti_is_existing_event( $event, $event_start, $event_end );
		$availability	= bookacti_get_event_availability( $event_id, $event_start, $event_end );
		$is_in_range	= bookacti_is_event_in_its_template_range( $event_id, $event_start, $event_end );
	
	} else if( is_numeric( $group_id ) ) {
		$group			= bookacti_get_group_of_events( $group_id );
		$title			= apply_filters( 'bookacti_translate_text', $group->title );
		
		$exists			= bookacti_is_existing_group_of_events( $group );
		$availability	= bookacti_get_group_of_events_availability( $group_id );
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
		} else if( $availability === 0 ) {
			$validated['error'] = 'no_availability';
			$validated['availability'] = $availability;
			/* translators: %1$s is the event title. */
			$validated['message'] = sprintf( __( 'The event "%1$s" is no longer available on this time slot. Please choose another event.', BOOKACTI_PLUGIN_NAME ), $title );
		} else if( $is_qty_sup_to_avail ) {
			$validated['error'] = 'qty_sup_to_avail';
			$validated['availability'] = $availability;
			$validated['message'] = /* translators: %1$s is a variable number of bookings, %2$s is the event title. This sentence is followed by two others : 'but only %1$s is available on this time slot.' and 'Please choose another event or decrease the quantity.' */
									sprintf( _n( 'You want to make %1$s booking of "%2$s" event', 'You want to make %1$s bookings of "%2$s" event', $quantity, BOOKACTI_PLUGIN_NAME ), $quantity, $title )
									/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s" event' and followed by 'Please choose another event or decrease the quantity.' */
							. ' ' . sprintf( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $availability, BOOKACTI_PLUGIN_NAME ), $availability )
									/* translators: This sentence is preceded by two others : 'You want to make %1$s booking of "%2$s" event' and 'but only %1$s is available on this time slot.' */
							. ' ' . __( 'Please choose another event or decrease the quantity.', BOOKACTI_PLUGIN_NAME );
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
 * @version 1.3.0
 * 
 * @param object $event
 * @param string $event_start
 * @param string $event_end
 * @return boolean
 */
function bookacti_is_existing_event( $event, $event_start = NULL, $event_end = NULL ) {
	
	if( is_numeric( $event ) ) {
		$event = bookacti_get_event_by_id( $event );
	}

	$is_existing_event = false;
	if( $event ) {
		if( $event->repeat_freq && $event->repeat_freq !== 'none' ) {
			$is_existing_event = bookacti_is_existing_occurence( $event, $event_start, $event_end );
		} else {
			$is_existing_event = bookacti_is_existing_single_event( $event->id, $event_start, $event_end );
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
	
	// Check if the occurence is on an exception date
	if( bookacti_is_repeat_exception( $event->event_id, substr( $event_start, 0, 10 ) ) ) { return false; }
	
	return true;
}


/**
 * Check if the group of event exists
 * 
 * @since 1.1.0
 * @version 1.3.0
 * 
 * @param object|int $group
 * @return boolean
 */
function bookacti_is_existing_group_of_events( $group ) {
	
	if( is_numeric( $group ) ) {
		$group = bookacti_get_group_of_events( $group );
	}
	
	// Try to retrieve the group and check the result
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
 * Get array of events from raw events from database
 * 
 * @since 1.2.2
 * @param array $events Array of objects events from database
 * @param boolean $past_events
 * @param array $interval array('start'=> start date, 'end'=> end date)
 * @return array
 */
function bookacti_get_events_array_from_db_events( $events, $past_events, $interval ) {
	$events_array = array( 'data' => array(), 'events' => array() );
	foreach ( $events as $event ) {

		$event_fc_data = array(
			'id'				=> $event->event_id,
			'title'				=> apply_filters( 'bookacti_translate_text', $event->title ),
			'start'				=> $event->start,
			'end'				=> $event->end,
			'color'				=> $event->color,
			'durationEditable'	=> $event->is_resizable === '1' ? true : false
		);

		$event_bookacti_data = array(
			'multilingual_title'=> $event->title,
			'template_id'		=> $event->template_id,
			'activity_id'		=> $event->activity_id,
			'availability'		=> $event->availability,
			'repeat_freq'		=> $event->repeat_freq,
			'repeat_from'		=> $event->repeat_from,
			'repeat_to'			=> $event->repeat_to,
			'settings'			=> bookacti_get_metadata( 'event', $event->event_id )
		);

		// Build events data array
		$events_array[ 'data' ][ $event->event_id ] = array_merge( $event_fc_data, $event_bookacti_data );

		// Build events array
		if( $event->repeat_freq === 'none' ) {
			$events_array[ 'events' ][] = $event_fc_data;
		} else {
			$events_array[ 'events' ] = array_merge( $events_array[ 'events' ], bookacti_get_occurences_of_repeated_event( $event, $past_events, $interval ) );
		}
	}

	return $events_array;
}


/**
 * Get a new interval of events to load. Computed from the compulsory interval, or now's date and template interval.
 * 
 * @since 1.2.2
 * @param array $template_interval array( 'start'=>Calendar start, 'end'=> Calendar end) 
 * @param array $min_interval array( 'start'=> Calendar start, 'end'=> Calendar end)
 * @param int $interval_duration Number of days of the interval
 * @param bool $past_events
 * @return array
 */
function bookacti_get_new_interval_of_events( $template_interval, $min_interval = array(), $interval_duration = false, $past_events = false ) {
	
	if( ! isset( $template_interval[ 'start' ] ) || ! isset( $template_interval[ 'end' ] ) ) { return array(); }
	
	$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_time		= new DateTime( 'now', $timezone );
	$current_date		= $current_time->format( 'Y-m-d' );
	
	$calendar_start		= new DateTime( $template_interval[ 'start' ] . ' 00:00:00', $timezone );
	$calendar_end		= new DateTime( $template_interval[ 'end' ] . ' 23:59:59', $timezone );
	
	if( ! $past_events && $calendar_end < $current_time ) { return array(); }
	
	if( ! $min_interval ) {
		if( $calendar_start > $current_time ) {
			$min_interval = array( 'start' => $template_interval[ 'start' ], 'end' => $template_interval[ 'start' ] );
		} else if( $calendar_end < $current_time ) {
			$min_interval = array( 'start' => $template_interval[ 'end' ], 'end' => $template_interval[ 'end' ] );
		} else {
			$min_interval = array( 'start' => $current_date, 'end' => $current_date );
		}
	}
	
	$interval_duration	= $interval_duration ? intval( $interval_duration ) : intval( bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ) );
	
	$interval_start		= new DateTime( $min_interval[ 'start' ] . ' 00:00:00', $timezone );
	$interval_end		= new DateTime( $min_interval[ 'end' ] . ' 23:59:59', $timezone );
	$min_interval_duration = intval( abs( $interval_end->diff( $interval_start )->format( '%a' ) ) );
	
	if( $min_interval_duration > $interval_duration ) { $interval_duration = $min_interval_duration; }
	
	$half_interval		= abs( round( intval( $interval_duration - $min_interval_duration ) / 2 ) );
	$interval_end_days_to_add = $half_interval;
	
	// Compute Interval start
	if( $past_events ) {
		$interval_start->sub( new DateInterval( 'P' . $half_interval . 'D' ) );
		if( $calendar_start > $interval_start ) {
			$interval_end_days_to_add += abs( $interval_start->diff( $calendar_start )->format( '%a' ) );
			$interval_start = clone $calendar_start;
		}
	} else {
		$interval_end_days_to_add += $half_interval;
	}

	// Compute interval end
	$interval_end->add( new DateInterval( 'P' . $interval_end_days_to_add . 'D' ) );
	if( $calendar_end < $interval_end ) {
		$interval_end = $calendar_end;
	}

	$interval = array( 
		'start' => $interval_start->format( 'Y-m-d' ), 
		'end' => $interval_end->format( 'Y-m-d' ) 
	);
	
	return $interval;
}


/**
 * Sanitize events interval
 * 
 * @since 1.2.2
 * @param array $interval
 * @return array
 */
function bookacti_sanitize_events_interval( $interval ) {
	
	if( ! $interval || ! is_array( $interval ) ) { return array(); }
	
	$sanitized_interval = array(
		'start'	=> isset( $interval[ 'start' ] ) ? bookacti_sanitize_date( $interval[ 'start' ] ) : false,
		'end'	=> isset( $interval[ 'end' ] ) ? bookacti_sanitize_date( $interval[ 'end' ] ) : false
	);
	
	return $sanitized_interval;
}


/**
 * get occurences of repeated events
 * 
 * @since 1.2.2 (replace bookacti_create_repeated_events)
 * @param object $event Event data 
 * @param boolean $past_events Whether to compute past events
 * @param array $interval array('start' => string: start date, 'end' => string: end date)
 * @return array
 */
function bookacti_get_occurences_of_repeated_event( $event, $past_events = false, $interval = array() ) {

	// Get site settings
	$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$get_started_events	= bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );

	// Init variables to compute occurences
	$event_start		= new DateTime( $event->start, $timezone );
	$event_end			= new DateTime( $event->end, $timezone );
	$event_duration		= $event_start->diff( $event_end );

	$event_start_time	= substr( $event->start, 11 );
	$event_monthday		= $event_start->format( 'd' );

	$repeat_from		= new DateTime( $event->repeat_from . ' 00:00:00', $timezone );
	$repeat_to			= new DateTime( $event->repeat_to . ' 23:59:59', $timezone );
	$repeat_interval	= array();

	$current_time		= new DateTime( 'now', $timezone );

	// Check if the repetition period is in the interval to be rendered
	if( $interval ) {
		// If the repetition period is totally outside the desired interval, skip the event
		// Else, restrict the repetition period
		if( $interval[ 'start' ] ) {
			$interval_start = new DateTime( $interval[ 'start' ] . ' 00:00:00', $timezone );
			if( $interval_start > $repeat_from && $interval_start > $repeat_to ) { return array(); }
			if( $interval_start > $repeat_from ) { $repeat_from = clone $interval_start; }
		}
		if( $interval[ 'end' ] ) {
			$interval_end	= new DateTime( $interval[ 'end' ] . ' 23:59:59', $timezone );
			if( $interval_end < $repeat_from && $interval_end < $repeat_to ) { return array(); }
			if( $interval_end < $repeat_to ) { $repeat_to = clone $interval_end; }
		}
	}
	
	// Make sure repeated events don't start in the past if not explicitly allowed
	if( ! $past_events && $current_time > $repeat_from ) {
		$current_date = $current_time->format( 'Y-m-d' );

		$repeat_from = new DateTime( $current_date . ' 00:00:00', $timezone );

		// If started event are NOT allowed
		$first_potential_event = new DateTime( $current_date . ' ' . $event_start_time, $timezone );
		$first_potential_event->add( $event_duration );
		
		if( ! $get_started_events || $first_potential_event <= $current_time ) {
			$repeat_from->add( new DateInterval( 'P1D' ) );
		}
	}

	switch( $event->repeat_freq ) {
		case 'daily':
			$repeat_interval = new DateInterval( 'P1D' );
			break;
		case 'weekly':
			$repeat_interval = new DateInterval( 'P7D' );
			// We need to make sure the repetition start from the week day of the event
			$event_weekday = $event_start->format( 'N' );
			if( $repeat_from->format( 'N' ) !== $event_weekday ) { $repeat_from->modify( 'next ' . $event_start->format( 'l' ) ); }
			break;
		case 'monthly':
			// We need to make sure the repetition starts on the event month day
			if( $repeat_from->format( 'd' ) !== $event_monthday ) {
				if( $repeat_from->format( 'd' ) < $event_monthday ) {
					$repeat_from->modify( 'last day of previous month' )->modify( '+' . $event_monthday . ' day' );
				} else { 
					$repeat_from->modify( 'last day of this month' )->modify( '+' . $event_monthday . ' day' ); 
				}

				// If the event_monthday is 31 (or 29, 30 or 31 in January), it could have jumped the next month
				// Make sure it doesn't happen
				if( $repeat_from->format( 'd' ) !== $event_monthday ) {
					$repeat_from->modify( '-1 month' );
					if( $event_monthday > $repeat_from->format( 't' ) ) { $repeat_from->modify( 'last day of this month' ); }
				}
			}

			// The repeat_interval will be computed directly in the loop
			break;
		default:
			$repeat_interval = new DateInterval( 'P1D' ); // Default to daily to avoid unexpected behavior such as infinite loop
	}

	// Properties common to each events of the 
	$shared_properties = array(
		'id'				=> $event->event_id,
		'title'				=> apply_filters( 'bookacti_translate_text', $event->title ),
		'color'				=> $event->color,
		'durationEditable'	=> $event->is_resizable === '1' ? true : false
	);

	// Compute occurences
	$events		= array();
	$loop		= clone $repeat_from;
	$end_loop	= $repeat_to->format( 'U' );

	while( $loop->format( 'U' ) < $end_loop ) {

		$occurence_start = new DateTime( $loop->format( 'Y-m-d' ) . ' ' . $event_start_time, $timezone );
		$occurence_end = clone $occurence_start;
		$occurence_end->add( $event_duration );

		// Check if the event is in the interval to be rendered
		if( $interval ) {
			if( $interval[ 'start' ] && $interval_start > $occurence_start ){ $loop->add( $repeat_interval ); continue; }
			if( $interval[ 'end' ] && $interval_end < $occurence_start )	{ $loop->add( $repeat_interval ); continue; }
		}

		// Compute start and end dates
		$event_occurence = array(
			'start'	=> $occurence_start->format( 'Y-m-d H:i:s' ),
			'end'	=> $occurence_end->format( 'Y-m-d H:i:s' )
		);

		// Add this occurrence to events array
		$events[] = array_merge( $event_occurence, $shared_properties );

		// Alter repeat_interval to make sure it matches the last day of next month
		if( $event->repeat_freq === 'monthly' ) {
			$next_month = clone $loop;
			$next_month->modify( 'last day of this month' )->modify( '+' . $event_monthday . ' day' );
			if( $next_month->format( 'd' ) !== $event_monthday ) {
				$next_month->modify( '-1 month' );
				if( $event_monthday > $next_month->format( 't' ) ) { $next_month->modify( 'last day of this month' ); }
			}
			$days_to_next_month	= abs( $next_month->diff( $loop )->format( '%a' ) );		
			$repeat_interval = new DateInterval( 'P' . $days_to_next_month . 'D' );
		}

		// Increase loop
		$loop->add( $repeat_interval );
	}

	return $events;
}



/**
 * Build a user-friendly events list
 * 
 * @since 1.1.0
 * @version 1.3.0
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
	
	$datetime_format		= bookacti_get_message( 'date_format_long' );
	$date_time_spearator	= bookacti_get_message( 'date_time_separator' );
	$dates_spearator		= bookacti_get_message( 'dates_separator' );
	$quantity_separator		= bookacti_get_message( 'quantity_separator' );
	
	$events_list = '';
	foreach( $formatted_events as $event ) {
		
		// Format the event duration
		$event_duration = '';
		if( $event[ 'start' ] && $event[ 'end' ] ) {
			
			$event_start = bookacti_format_datetime( $event[ 'start' ], $datetime_format );
			
			// Format differently if the event start and end on the same day
			$start_and_end_same_day	= substr( $event[ 'start' ], 0, 10 ) === substr( $event[ 'end' ], 0, 10 );
			if( $start_and_end_same_day ) {
				/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
				$event_end = date_i18n( __( 'h:i a', BOOKACTI_PLUGIN_NAME ), strtotime( $event[ 'end' ] ) );
				$event_end = ! mb_check_encoding( $event_end, 'UTF-8' ) ? utf8_encode( $event_end ) : $event_end;
			} else {
				$event_end = bookacti_format_datetime( $event[ 'end' ], $datetime_format );
			}
			
			$class		= $start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
			$separator	= $start_and_end_same_day ? $date_time_spearator : $dates_spearator;
			
			// Place an arrow between start and end
			$event_duration = '<span class="bookacti-booking-event-start" >' . $event_start . '</span>'
							. '<span class="bookacti-booking-event-date-separator ' . $class . '" >' . $separator . '</span>'
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
				$events_list .= '<span class="bookacti-booking-event-quantity-separator" >' . $quantity_separator . '</span>';
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
	 * @since 1.1.0
	 * @version 1.3.0
	 * @param int $event_group_id
	 * @param array $include_states
	 * @return int
	 */
	function bookacti_get_group_of_events_availability( $event_group_id, $include_states = array() ) {

		$events = bookacti_get_group_events( $event_group_id, true );

		$max = 999999999; // Any big int
		foreach( $events as $event ) {
			$availability = bookacti_get_event_availability( $event[ 'id' ], $event[ 'start' ], $event[ 'end' ], $include_states );
			if( $availability < $max ) {
				$max = $availability;
			}
		}

		return $max;
	}
	
	
	/**
	 * Book all events of a group
	 * 
	 * @version 1.3.0
	 * @param int $user_id
	 * @param int $event_group_id
	 * @param int $quantity
	 * @param string $state
	 * @param string $payment_status
	 * @param string $expiration_date
	 * @return int|boolean
	 */
	function bookacti_book_group_of_events( $user_id, $event_group_id, $quantity, $state = 'booked', $payment_status = 'none', $expiration_date = NULL ) {
				
		// Insert the booking group
		$booking_group_id = bookacti_insert_booking_group( $user_id, $event_group_id, $state, $payment_status );
		
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
			bookacti_insert_booking( $user_id, $event[ 'id' ], $event[ 'start' ], $event[ 'end' ], $quantity, $state, $payment_status, $expiration_date, $booking_group_id );
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