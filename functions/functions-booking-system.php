<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/***** BOOKING SYSTEM *****/

/**
 * Get a booking system based on given parameters
 * @version 1.7.15
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @param boolean $echo Wether to return or directly echo the booking system
 * @return string
 */
function bookacti_get_booking_system( $atts, $echo = false ) {
	// Get booking system data
	$booking_system_data = bookacti_get_booking_system_data( $atts );
	
	if( ! $echo ) { ob_start(); }
	do_action( 'bookacti_before_booking_system_container', $atts, $booking_system_data );
	
	// Encrypt user_id
	$public_user_id = ! empty( $atts[ 'user_id' ] ) ? $atts[ 'user_id' ] : 0;
	if( $public_user_id && ( ( is_numeric( $public_user_id ) && strlen( (string) $public_user_id ) < 16 ) || is_email( $public_user_id ) ) ) { $public_user_id = bookacti_encrypt( $public_user_id ); }
	
	// Let plugins define what data should be passed to JS
	$public_booking_system_data = apply_filters( 'bookacti_public_booking_system_data', array_merge( $booking_system_data, array( 'user_id' => $public_user_id ) ), $atts );
	?>

	<div class='bookacti-booking-system-container' id='<?php echo esc_attr( $atts[ 'id' ] . '-container' ); ?>' >
		<script>
			// Compatibility with Optimization plugins
			if( typeof bookacti === 'undefined' ) { var bookacti = { booking_system:[] }; }
			bookacti.booking_system[ '<?php echo $atts[ 'id' ]; ?>' ] = <?php echo json_encode( $public_booking_system_data ); ?>;
		</script>
				
		<div class='bookacti-booking-system-inputs'>
			<input type='hidden' name='bookacti_group_id' value='<?php echo $atts[ 'picked_events' ][ 'group_id' ]; ?>' />
			<input type='hidden' name='bookacti_event_id' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'id' ] : ''; ?>' />
			<input type='hidden' name='bookacti_event_start' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'start' ] : ''; ?>' />
			<input type='hidden' name='bookacti_event_end' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'end' ] : ''; ?>' />
			<?php do_action( 'bookacti_booking_system_inputs', $atts, $booking_system_data ); ?>
		</div>
		
		<?php do_action( 'bookacti_booking_system_container_before', $atts, $booking_system_data ); ?>
		
		<div id='<?php echo esc_attr( $atts[ 'id' ] ); ?>' class='bookacti-booking-system <?php echo esc_attr( $atts[ 'class' ] ); ?>' >
			<?php echo bookacti_get_booking_method_html( $atts[ 'method' ], $atts ); 
			if( $atts[ 'auto_load' ] ) { 
			?>
			<div class='bookacti-loading-alt'> 
				<img class='bookacti-loader' src='<?php echo plugins_url() . '/' . BOOKACTI_PLUGIN_NAME; ?>/img/ajax-loader.gif' title='<?php esc_html_e( 'Loading', 'booking-activities' ); ?>' />
				<span class='bookacti-loading-alt-text' ><?php esc_html_e( 'Loading', 'booking-activities' ); ?></span>
			</div>
			<?php } ?>
		</div>
		
		<?php do_action( 'bookacti_after_booking_system', $atts, $booking_system_data ); ?>
		
		<div class='bookacti-picked-events' style='display:none;' >
			<div class='bookacti-picked-events-list-title' ></div>
			<ul class='bookacti-picked-events-list bookacti-custom-scrollbar' >
				<?php do_action( 'bookacti_picked_events_list', $atts, $booking_system_data ); ?>
			</ul>
		</div>
		
		<div class='bookacti-notices' style='display:none;' >
			<?php do_action( 'bookacti_booking_system_errors', $atts, $booking_system_data ); ?>
		</div>
		
		<?php do_action( 'bookacti_booking_system_container_after', $atts, $booking_system_data ); ?>
	</div>
	<div id='<?php echo $atts[ 'id' ] . '-dialogs'; ?>' class='bookacti-booking-system-dialogs' >
		<?php
			bookacti_display_booking_system_dialogs( $atts[ 'id' ] );
		?>
	</div>
	<?php
	do_action( 'bookacti_after_booking_system_container', $atts, $booking_system_data );
	
	if( ! $echo ) { return ob_get_clean(); }
}


/**
 * Get booking system data
 * @since 1.7.4
 * @version 1.7.10
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @return array
 */
function bookacti_get_booking_system_data( $atts ) {
	$booking_system_data = $atts;

	// Events related data
	if( $atts[ 'auto_load' ] ) { 
		if( empty( $atts[ 'template_data' ] ) ) {
			$atts[ 'template_data' ] = bookacti_get_mixed_template_data( $atts[ 'calendars' ], $atts[ 'past_events' ] );
		}

		$events_interval	= bookacti_get_new_interval_of_events( $atts[ 'template_data' ], array(), false, $atts[ 'past_events' ] );

		$user_ids			= array();
		$groups_ids			= array();
		$groups_data		= array();
		$categories_data	= array();
		$groups_events		= array();
		$events				= array( 'events' => array(), 'data' => array() );

		if( $atts[ 'group_categories' ] !== false ) {
			$groups_data		= bookacti_get_groups_of_events( $atts[ 'calendars' ], $atts[ 'group_categories' ], $atts[ 'past_events_bookable' ], true, false, $atts[ 'template_data' ] );
			$categories_data	= bookacti_get_group_categories( $atts[ 'calendars' ], $atts[ 'group_categories' ] );

			foreach( $groups_data as $group_id => $group_data ) { $groups_ids[] = $group_id; }

			$groups_events = ! $groups_ids ? array() : bookacti_get_groups_events( $atts[ 'calendars' ], $atts[ 'group_categories' ], $groups_ids );
		} 

		if( $atts[ 'groups_only' ] ) {
			if( $groups_ids ) { 
				$events	= bookacti_fetch_grouped_events( $atts[ 'calendars' ], $atts[ 'activities' ], $groups_ids, $atts[ 'group_categories' ], $atts[ 'past_events' ], $events_interval );
			}
		} else if( $atts[ 'bookings_only' ] ) {
			$events		= bookacti_fetch_booked_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'status' ], $atts[ 'user_id' ], $atts[ 'past_events' ], $events_interval );
			$user_ids	= $atts[ 'user_id' ];
		} else {
			$events		= bookacti_fetch_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'past_events' ], $events_interval );
		}

		$booking_system_data[ 'events' ]				= $events[ 'events' ] ? $events[ 'events' ] : array();
		$booking_system_data[ 'events_data' ]			= $events[ 'data' ] ? $events[ 'data' ] : array();
		$booking_system_data[ 'events_interval' ]		= $events_interval;
		$booking_system_data[ 'bookings' ]				= bookacti_get_number_of_bookings_by_events( $atts[ 'calendars' ], array(), $user_ids );
		$booking_system_data[ 'activities_data' ]		= bookacti_get_activities_by_template( $atts[ 'calendars' ], true );
		$booking_system_data[ 'groups_events' ]			= $groups_events;
		$booking_system_data[ 'groups_data' ]			= $groups_data;
		$booking_system_data[ 'group_categories_data' ]	= $categories_data;
		$booking_system_data[ 'template_data' ]			= $atts[ 'template_data' ];
	}

	// Events picked by default
	$picked_events = array();
	if( $atts[ 'picked_events' ][ 'group_id' ] === 'single' && $atts[ 'picked_events' ][ 'event_id' ] ) {
		$event_id = $atts[ 'picked_events' ][ 'event_id' ];
		$event = ! empty( $events[ 'data' ][ $event_id ] ) ? $events[ 'data' ][ $event_id ] : (array) bookacti_get_event_by_id( $event_id );
		
		if( $event 
		&& ( ! $atts[ 'auto_load' ] 
		|| ( ( in_array( intval( $event[ 'template_id' ] ), $atts[ 'calendars' ], true ) || ( empty( $atts[ 'calendars' ] ) && is_super_admin() ) )
			&&  ! empty( $booking_system_data[ 'activities_data' ][ $event[ 'activity_id' ] ] ) ) ) ) { 
			$picked_events[] = array(
				'id'	=> $event_id,
				'start'	=> $atts[ 'picked_events' ][ 'event_start' ],
				'end'	=> $atts[ 'picked_events' ][ 'event_end' ],
				'title'	=> apply_filters( 'bookacti_translate_text', $event[ 'title' ] )
			);
		}
	} else if( is_numeric( $atts[ 'picked_events' ][ 'group_id' ] ) ) {
		if( isset( $groups_events[ $atts[ 'picked_events' ][ 'group_id' ] ] ) ) {
			$picked_events = $groups_events[ $atts[ 'picked_events' ][ 'group_id' ] ];
		} else {
			$group_events = bookacti_get_group_events( $atts[ 'picked_events' ][ 'group_id' ] );
			$category_id  = ! empty( $group_events[ 0 ][ 'category_id' ] ) ? intval( $group_events[ 0 ][ 'category_id' ] ) : 0;
			if( $category_id 
			&& ( ! $atts[ 'auto_load' ] 
			|| ! empty( $booking_system_data[ 'group_categories_data' ][ $category_id ] ) ) ) {
				foreach( $group_events as $grouped_event ) {
					$picked_events[] = array(
						'id'	=> $grouped_event[ 'id' ],
						'start'	=> $grouped_event[ 'start' ],
						'end'	=> $grouped_event[ 'end' ],
						'title'	=> $grouped_event[ 'title' ]
					);
				}
			}
		}
	}
	
	$booking_system_data[ 'picked_events' ] = $picked_events;
	
	return apply_filters( 'bookacti_booking_system_data', $booking_system_data, $atts );
}


/**
 * Display booking system dialogs
 * @since 1.5.0
 * @param string $booking_system_id
 */
function bookacti_display_booking_system_dialogs( $booking_system_id ) {
?>
	<!-- Choose a group of events -->
	<div id='<?php echo $booking_system_id . '-choose-group-of-events-dialog' ?>' 
		 class='bookacti-choose-group-of-events-dialog bookacti-booking-system-dialog' 
		 title='<?php echo bookacti_get_message( 'choose_group_dialog_title' ); ?>' 
		 style='display:none;' >
		<?php echo bookacti_get_message( 'choose_group_dialog_content' ); ?>
		<div id='<?php echo $booking_system_id . '-groups-of-events-list' ?>' class='bookacti-groups-of-events-list' ></div>
	</div>

	<?php do_action( 'bookacti_display_booking_system_dialogs', $booking_system_id );
}


/**
 * Get available booking methods
 * 
 * @return string
 */
function bookacti_get_available_booking_methods(){
	$available_booking_methods = array(
		'calendar'	=> __( 'Calendar', 'booking-activities' )
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
 * @since 1.0.0
 * @version 1.7.8
 * @param array $booking_system_atts
 * @return string
 */
function bookacti_retrieve_calendar_elements( $booking_system_atts ) {
	$default_calendar_title	= esc_html( bookacti_get_message( 'calendar_title' ) );
	$calendar_title			= apply_filters( 'bookacti_calendar_title', $default_calendar_title, $booking_system_atts );
	
	ob_start();
	
	do_action( 'bookacti_before_calendar_title', $booking_system_atts );
	?>
	
	<div class='bookacti-calendar-title bookacti-booking-system-title'>
		<?php echo $calendar_title; ?>
	</div>
	
	<?php do_action( 'bookacti_before_calendar', $booking_system_atts ); ?>
	
	<div class='bookacti-calendar'></div>
			
	<?php 
	do_action( 'bookacti_after_calendar', $booking_system_atts );
	
	return ob_get_clean();
}


/**
 * Get default booking system attributes
 * @since 1.5.0
 * @version 1.7.17
 * @return array
 */
function bookacti_get_booking_system_default_attributes() {
	return apply_filters( 'bookacti_booking_system_default_attributes', array(
		'id'					=> '',
		'class'					=> '',
		'template_data'			=> array(),
		'calendars'				=> array(),
		'activities'			=> 'all',
		'group_categories'		=> false,
		'groups_only'			=> 0,
		'groups_single_events'	=> 0,
		'bookings_only'			=> 0,
		'status'				=> array(),
		'user_id'				=> 0,
		'method'				=> 'calendar',
		'auto_load'				=> bookacti_get_setting_value( 'bookacti_general_settings', 'when_events_load' ) === 'on_page_load' ? 1 : 0,
		'past_events'			=> 0,
		'past_events_bookable'	=> 0,
		'check_roles'			=> 1,
		'picked_events'			=> array( 'group_id' => '', 'event_id' => '', 'event_start' => '', 'event_end' => '' ),
		'form_id'						=> 0,
		'form_action'					=> 'default',
		'when_perform_form_action'		=> 'on_submit',
		'redirect_url_by_activity'		=> array(),
		'redirect_url_by_group_category'=> array()
	) );
}


/**
 * Check booking system attributes and format them to be correct
 * @version 1.7.17
 * @param array $atts 
 * @return array
 */
function bookacti_format_booking_system_attributes( $atts = array() ) {
	// Set default value
	$defaults = bookacti_get_booking_system_default_attributes();
	
	// Replace empty mandatory values by default
	$atts = shortcode_atts( $defaults, $atts );
	
	// Sanitize booleans
	$booleans_to_check = array( 'bookings_only', 'groups_only', 'groups_single_events', 'auto_load', 'past_events', 'past_events_bookable', 'check_roles' );
	foreach( $booleans_to_check as $key ) {
		$atts[ $key ] = in_array( $atts[ $key ], array( 1, '1', true, 'true', 'yes', 'ok' ), true ) ? 1 : 0;
	}
	
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
	if( ! is_array( $atts[ 'group_categories' ] ) ) { $atts[ 'group_categories' ] = false; }
	else { $atts[ 'group_categories' ]	= array_values( array_unique( $atts[ 'group_categories' ] ) ); }
	
	// Remove duplicated values
	$atts[ 'calendars' ]	= array_values( array_unique( $atts[ 'calendars' ] ) );
	$atts[ 'activities' ]	= array_values( array_unique( $atts[ 'activities' ] ) );
	
	// Check if the desired templates are active and allowed
	$available_template_ids = array_keys( bookacti_fetch_templates( array(), true ) );
	$had_templates = ! empty( $atts[ 'calendars' ] );
	// Remove unauthorized templates
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	$allowed_templates = ! $bypass_template_managers_check && ! is_super_admin() ? array_values( array_intersect( $atts[ 'calendars' ], $available_template_ids ) ) : $atts[ 'calendars' ];
	$atts[ 'calendars' ] = ! empty( $allowed_templates ) ? $allowed_templates : ( ! $had_templates && $available_template_ids ? $available_template_ids : array( 'none' ) );
	
	// Check if desired activities are active and allowed according to current user role
	$available_activity_ids = bookacti_get_activity_ids_by_template( $atts[ 'calendars' ], false, $atts[ 'check_roles' ] );
	$had_activities = ! empty( $atts[ 'activities' ] );
	// Remove unauthorized activities
	$allowed_activities = array_values( array_intersect( $atts[ 'activities' ], array_map( 'intval', $available_activity_ids ) ) );
	$atts[ 'activities' ] = ! empty( $allowed_activities ) ? $allowed_activities : ( ! $had_activities && $available_activity_ids ? $available_activity_ids : array( 'none' ) );
	
	// Check if desired group categories exist and are allowed according to current user role
	$available_category_ids = bookacti_get_group_category_ids_by_template( $atts[ 'calendars' ], false, $atts[ 'check_roles' ] );
	$had_group_categories = ! empty( $atts[ 'group_categories' ] );
	if( is_array( $atts[ 'group_categories' ] ) ) { 
		// Remove unauthorized group categories
		$allowed_group_categories = array_values( array_intersect( $atts[ 'group_categories' ], array_map( 'intval', $available_category_ids ) ) );
		$atts[ 'group_categories' ] = ! empty( $allowed_group_categories ) ? $allowed_group_categories : ( ! $had_group_categories && $available_category_ids ? $available_category_ids : false );
	}
	
	// Format template data
	if( ! empty( $atts[ 'template_data' ] ) && is_array( $atts[ 'template_data' ] ) ) {
		$templates_data = bookacti_get_mixed_template_data( $atts[ 'calendars' ], $atts[ 'past_events' ] );
		$atts[ 'template_data' ][ 'start' ]		= ! empty( $atts[ 'template_data' ][ 'start' ] ) && bookacti_sanitize_date( $atts[ 'template_data' ][ 'start' ] ) ? bookacti_sanitize_date( $atts[ 'template_data' ][ 'start' ] ) : bookacti_sanitize_date( $templates_data[ 'start' ] );
		$atts[ 'template_data' ][ 'end' ]		= ! empty( $atts[ 'template_data' ][ 'end' ] ) && bookacti_sanitize_date( $atts[ 'template_data' ][ 'end' ] ) ? bookacti_sanitize_date( $atts[ 'template_data' ][ 'end' ] ) : bookacti_sanitize_date( $templates_data[ 'end' ] );
		$atts[ 'template_data' ][ 'settings' ]	= ! empty( $atts[ 'template_data' ][ 'settings' ] ) ? bookacti_format_template_settings( $atts[ 'template_data' ][ 'settings' ] ) : $templates_data[ 'settings' ];
	}
	
	// Check if desired booking method is registered
	$atts[ 'method' ] = esc_attr( $atts[ 'method' ] );
	$available_booking_methods = array_keys( bookacti_get_available_booking_methods() );
	if( ! in_array( $atts[ 'method' ], $available_booking_methods, true ) ) {
		$atts[ 'method' ] = in_array( $defaults[ 'method' ], $available_booking_methods, true ) ? $defaults[ 'method' ] : 'calendar';
	}
	
	// Sanitize user id
	$atts[ 'user_id' ] = is_numeric( $atts[ 'user_id' ] ) ? intval( $atts[ 'user_id' ] ) : esc_attr( $atts[ 'user_id' ] );
	if( $atts[ 'user_id' ] === 'current' ) { $atts[ 'user_id' ] = get_current_user_id(); }
	if( $atts[ 'user_id' ] && ! is_email( $atts[ 'user_id' ] ) && ( ! is_numeric( $atts[ 'user_id' ] ) || ( is_numeric( $atts[ 'user_id' ] ) && strlen( (string) $atts[ 'user_id' ] ) ) >= 16 ) ) { $atts[ 'user_id' ] = bookacti_decrypt( $atts[ 'user_id' ] ); }
	
	// Sanitize booking status
	if( is_string( $atts[ 'status' ] ) ) {
		$atts[ 'status' ] = array_map( 'sanitize_title_with_dashes', explode( ',', preg_replace( array(
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
		$atts[ 'id' ] = 'bookacti-booking-system-' . $atts[ 'id' ];
	}
	$atts[ 'id' ] = esc_attr( $atts[ 'id' ] );
	
	// Format classes
	$atts[ 'class' ] = ! empty( $atts[ 'class' ] )	? esc_attr( $atts[ 'class' ] ) : '';
	
	// Format picked events
	$atts[ 'picked_events' ]					= shortcode_atts( $defaults[ 'picked_events' ], $atts[ 'picked_events' ] );
	$atts[ 'picked_events' ][ 'event_id' ]		= $atts[ 'picked_events' ][ 'event_id' ] && is_numeric( $atts[ 'picked_events' ][ 'event_id' ] ) ? intval( $atts[ 'picked_events' ][ 'event_id' ] ) : '';
	$atts[ 'picked_events' ][ 'event_start' ]	= bookacti_sanitize_datetime( $atts[ 'picked_events' ][ 'event_start' ] ) ? bookacti_sanitize_datetime( $atts[ 'picked_events' ][ 'event_start' ] ) : $defaults[ 'picked_events' ][ 'event_start' ];
	$atts[ 'picked_events' ][ 'event_end' ]		= bookacti_sanitize_datetime( $atts[ 'picked_events' ][ 'event_end' ] ) ? bookacti_sanitize_datetime( $atts[ 'picked_events' ][ 'event_end' ] ) : $defaults[ 'picked_events' ][ 'event_end' ];
	$atts[ 'picked_events' ][ 'group_id' ]		= $atts[ 'picked_events' ][ 'group_id' ] && is_numeric( $atts[ 'picked_events' ][ 'group_id' ] ) ? intval( $atts[ 'picked_events' ][ 'group_id' ] ) : ( is_numeric( $atts[ 'picked_events' ][ 'event_id' ] ) ? 'single' : '' );
	
	// Sanitize form id
	$atts[ 'form_id' ] = is_numeric( $atts[ 'form_id' ] ) ? intval( $atts[ 'form_id' ] ) : 0;	
	
	// Format actions
	$possible_form_actions	= array_keys( apply_filters( 'bookacti_form_action_options', array( 'default' => '', 'redirect_to_url' => '' ) ) );
	$possible_form_triggers = array_keys( apply_filters( 'bookacti_when_perform_form_action_options', array( 'on_submit' => '', 'on_event_click' => '' ) ) );
	$atts[ 'form_action' ]				= in_array( $atts[ 'form_action' ], $possible_form_actions, true ) ? $atts[ 'form_action' ] : $defaults[ 'form_action' ];
	$atts[ 'when_perform_form_action' ]	= in_array( $atts[ 'when_perform_form_action' ], $possible_form_triggers, true ) ? $atts[ 'when_perform_form_action' ] : $defaults[ 'when_perform_form_action' ];
	
	$redirect_url_by_group_activity = $defaults[ 'redirect_url_by_activity' ];
	if( isset( $atts[ 'redirect_url_by_activity' ] ) && is_array( $atts[ 'redirect_url_by_activity' ] ) ) {
		foreach( $atts[ 'redirect_url_by_activity' ] as $activity_id => $redirect_url ) {
			if( ! is_numeric( $activity_id ) || empty( $redirect_url ) ) { continue; }
			$redirect_url_by_group_activity[ intval( $activity_id ) ] = esc_url_raw( $redirect_url );
		}
	}
	$atts[ 'redirect_url_by_activity' ] = $redirect_url_by_group_activity;

	$redirect_url_by_group_category = $defaults[ 'redirect_url_by_group_category' ];
	if( isset( $atts[ 'redirect_url_by_group_category' ] ) && is_array( $atts[ 'redirect_url_by_group_category' ] ) ) {
		foreach( $atts[ 'redirect_url_by_group_category' ] as $group_category_id => $redirect_url ) {
			if( ! is_numeric( $group_category_id ) || empty( $redirect_url ) ) { continue; }
			$redirect_url_by_group_category[ intval( $group_category_id ) ] = esc_url_raw( $redirect_url );
		}
	}
	$atts[ 'redirect_url_by_group_category' ] = $redirect_url_by_group_category;
	
	return apply_filters( 'bookacti_formatted_booking_system_attributes', $atts );
}


/**
 * Format booking system attributes passed via the URL
 * @since 1.6.0
 * @version 1.7.10
 * @param array $atts
 * @return array
 */
function bookacti_format_booking_system_url_attributes( $atts = array() ) {
	$default_atts = bookacti_get_booking_system_default_attributes();
	if( ! $atts ) { $atts = $default_atts; }
	
	$url_raw_atts = $_REQUEST;
	
	// Bind past_events and past_events_bookable together
	if( isset( $url_raw_atts[ 'past_events' ] ) ) {
		// If 'past_events' is set on 'auto', keep the initial value
		if( $url_raw_atts[ 'past_events' ] === 'auto' && isset( $atts[ 'past_events' ] ) ) {
			$url_raw_atts[ 'past_events' ] = $atts[ 'past_events' ];
		}
		
		// Make 'past_events_bookable' = 'past_events'
		$was_past_events					= ! empty( $atts[ 'past_events' ] );
		$atts[ 'past_events' ]				= ! empty( $url_raw_atts[ 'past_events' ] ) ? 1 : 0;
		$atts[ 'past_events_bookable' ]		= $atts[ 'past_events' ];
		$url_raw_atts[ 'past_events_bookable' ]	= $atts[ 'past_events' ];
		
		// If the 'past_events' value changed, reset the template dates to compute them again later with bookacti_format_booking_system_attributes
		if( $atts[ 'past_events' ] && ! $was_past_events ) {
			$url_raw_atts[ 'template_data' ][ 'start' ] = '';
			$url_raw_atts[ 'template_data' ][ 'end' ] = '';
		}
	}
	
	// Format the URL attributes
	if( ! empty( $url_raw_atts[ 'start' ] ) || ! empty( $url_raw_atts[ 'end' ] ) ) { 
		$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		if( ! empty( $url_raw_atts[ 'start' ] ) && (bool)strtotime( $url_raw_atts[ 'start' ] ) ) {
			$from_datetime = new DateTime( $url_raw_atts[ 'start' ], $timezone );
			$url_raw_atts[ 'template_data' ][ 'start' ] = $from_datetime->format( 'Y-m-d' );
		}
		if( ! empty( $url_raw_atts[ 'end' ] ) && (bool)strtotime( $url_raw_atts[ 'end' ] ) ) {
			$to_datetime = new DateTime( $url_raw_atts[ 'end' ], $timezone );
			$url_raw_atts[ 'template_data' ][ 'end' ] = $to_datetime->format( 'Y-m-d' );
		}
	}
	
	$default_template_settings = bookacti_format_template_settings( array() );
	foreach( $default_template_settings as $att_name => $att_value ) {
		if( ! isset( $url_raw_atts[ $att_name ] ) || ( ! $url_raw_atts[ $att_name ] && ! in_array( $url_raw_atts[ $att_name ], array( 0, '0' ), true ) ) ) { continue; }
		$url_raw_atts[ 'template_data' ][ 'settings' ][ $att_name ] = $url_raw_atts[ $att_name ];
	}
	
	$url_atts = bookacti_format_booking_system_attributes( $url_raw_atts );
	
	// Replace booking system attributes with attributes passed through the URL
	foreach( $default_atts as $att_name => $att_value ) {
		if( ! isset( $url_raw_atts[ $att_name ] ) || ( ! $url_raw_atts[ $att_name ] && ! in_array( $url_raw_atts[ $att_name ], array( 0, '0' ), true ) ) ) { continue; }
		if( $att_name === 'past_events' && $url_raw_atts[ 'past_events' ] === 'auto' ) { continue; }
		$atts[ $att_name ] = $url_atts[ $att_name ];
	}
	foreach( $url_atts[ 'template_data' ] as $att_name => $att_value ) {
		if( $att_name === 'settings' ) { continue; }
		$atts[ 'template_data' ][ $att_name ] = $att_value;
	}
	foreach( $default_template_settings as $att_name => $att_value ) {
		if( ! isset( $url_raw_atts[ $att_name ] ) || ( ! $url_raw_atts[ $att_name ] && ! in_array( $url_raw_atts[ $att_name ], array( 0, '0' ), true ) ) ) { continue; }
		$atts[ 'template_data' ][ 'settings' ][ $att_name ] = $url_atts[ 'template_data' ][ 'settings' ][ $att_name ];
	}
	
	return apply_filters( 'bookacti_format_booking_system_url_attributes', $atts );
}


/**
 * Get booking system fields default data
 * @since 1.5.0
 * @version 1.7.17
 * @param array $fields
 * @return array
 */
function bookacti_get_booking_system_fields_default_data( $fields = array() ) {
	if( ! is_array( $fields ) ) { $fields = array(); }
	$defaults = array();
	
	// Calendars
	if( ! $fields || in_array( 'calendars', $fields, true ) ) {
		// Format template options array
		$templates = bookacti_fetch_templates();
		$templates_options = array();
		foreach( $templates as $template ) {
			$templates_options[ $template[ 'id' ] ] = apply_filters( 'bookacti_translate_text', $template[ 'title' ] );
		}
		
		$defaults[ 'calendars' ] = array( 
			'name'		=> 'calendars',
			'type'		=> 'select',
			'id'		=> '_bookacti_template',
			'multiple'	=> 'maybe',
			'options'	=> $templates_options,
			'value'		=> '', 
			'title'		=> esc_html__( 'Calendar', 'booking-activities' ),
			'tip'		=> esc_html__( 'Retrieve events from the selected calendars only.', 'booking-activities' )
		);
	}
	
	// Activities
	if( ! $fields || in_array( 'activities', $fields, true ) ) {
		// Format activity options array
		$activities = bookacti_fetch_activities_with_templates_association();
		$activities_options			= array( 'all' => esc_html__( 'All', 'booking-activities' ) );
		$activities_options_attr	= array();
		foreach( $activities as $activity ) {
			$activities_options[ $activity[ 'id' ] ]		=  apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
			$activities_options_attr[ $activity[ 'id' ] ]	=  'data-bookacti-show-if-templates="' .esc_attr( implode( ',', $activity[ 'template_ids' ] ) ) . '"';
		}
		
		$defaults[ 'activities' ] = array( 
			'name'			=> 'activities',
			'type'			=> 'select',
			'multiple'		=> 'maybe',
			'options'		=> $activities_options,
			'attr'			=> $activities_options_attr,
			'value'			=> '', 
			'title'			=> esc_html__( 'Activity', 'booking-activities' ),
			'tip'			=> esc_html__( 'Retrieve events from the selected activities only.', 'booking-activities' )
		);
	}
	
	// Group categories
	if( ! $fields || in_array( 'group_categories', $fields, true ) ) {
		// Format group category options array
		$categories = bookacti_get_group_categories();
		$category_options		= array( 'none' => _x( 'None', 'About group category', 'booking-activities' ), 'all' => esc_html__( 'All', 'booking-activities' ) );
		$category_options_attr	= array();
		foreach( $categories as $category ) {
			$category_options[ $category[ 'id' ] ]		=  apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
			$category_options_attr[ $category[ 'id' ] ]	=  'data-bookacti-show-if-templates="' . esc_attr( implode( ',', (array) $category[ 'template_id' ] ) ) . '"';
		}
		
		$defaults[ 'group_categories' ] = array( 
			'name'			=> 'group_categories',
			'type'			=> 'select',
			'multiple'		=> 'maybe',
			'options'		=> $category_options,
			'attr'			=> $category_options_attr,
			'value'			=> '', 
			'title'			=> esc_html__( 'Group category', 'booking-activities' ),
			'tip'			=> esc_html__( 'Retrieve groups of events from the selected group categories only.', 'booking-activities' )
		);
	}
	
	// Groups only
	if( ! $fields || in_array( 'groups_only', $fields, true ) ) {
		$defaults[ 'groups_only' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'groups_only',
			'id'			=> '_bookacti_groups_only',
			'value'			=> 0,
			'title'			=> esc_html__( 'Groups only', 'booking-activities' ),
			'tip'			=> esc_html__( 'Display only groups of events if checked. Else, also display the other single events (if any).', 'booking-activities' )
		);
	}
	
	// Groups single events
	if( ! $fields || in_array( 'groups_single_events', $fields, true ) ) {
		$defaults[ 'groups_single_events' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'groups_single_events',
			'value'			=> 0,
			'title'			=> esc_html__( 'Book grouped events alone', 'booking-activities' ),
			'tip'			=> esc_html__( 'When a customer picks an event belonging to a group, let him choose between the group or the event alone.', 'booking-activities' )
		);
	}
	
	// Bookings only
	if( ! $fields || in_array( 'bookings_only', $fields, true ) ) {
		$defaults[ 'bookings_only' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'bookings_only',
			'value'			=> 0,
			'title'			=> esc_html__( 'Booked only', 'booking-activities' ),
			'tip'			=> esc_html__( 'Display only events that has been booked.', 'booking-activities' )
		);
	}
	
	// Bookings status
	if( ! $fields || in_array( 'status', $fields, true ) ) {
		// Format status array
		$statuses = bookacti_get_booking_state_labels();
		$status_options = array( 'none' => _x( 'None', 'About booking status', 'booking-activities' ) );
		foreach ( $statuses as $status_id => $status ) { 
			$status_options[ $status_id ] = esc_html( $status[ 'label' ] );
		}
		$defaults[ 'status' ] = array(
			'name'			=> 'status',
			'type'			=> 'select',
			'multiple'		=> 'maybe',
			'options'		=> $status_options,
			'value'			=> '', 
			'title'			=> esc_html__( 'Bookings status', 'booking-activities' ),
			'tip'			=> esc_html__( 'Retrieve booked events with the selected booking status only.', 'booking-activities' ) . ' ' . esc_html__( '"Booked only" option must be activated.', 'booking-activities' )
		);
	}
	
	// User ID
	if( ! $fields || in_array( 'user_id', $fields, true ) ) {
		$defaults[ 'user_id' ] = array(
			'type'			=> 'user_id',
			'name'			=> 'user_id',
			'options'		=> array(
				'name'					=> 'user_id',
				'id'					=> 'user_id',
				'show_option_all'		=> __( 'None', 'booking-activities' ),
				'show_option_current'	=> __( 'Current user', 'booking-activities' ),
				'option_label'			=> array( 'user_login', ' (', 'user_email', ')' ),
				'selected'				=> 0,
				'echo'					=> true
			),
			'title'			=> esc_html__( 'Customer', 'booking-activities' ),
			'tip'			=> esc_html__( 'Retrieve events booked by the selected user only.', 'booking-activities' ) . ' ' . esc_html__( '"Booked only" option must be activated.', 'booking-activities' )
		);
	}
	
	// ID
	if( ! $fields || in_array( 'id', $fields, true ) ) {
		$defaults[ 'id' ] = array(
			'type'			=> 'text',
			'name'			=> 'id',
			'title'			=> esc_html__( 'ID', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set the booking system CSS id. Leave this empty if you display more than one occurence of this form on the same page.', 'booking-activities' )
		);
	}
	
	// Class
	if( ! $fields || in_array( 'class', $fields, true ) ) {
		$defaults[ 'class' ] = array(
			'type'			=> 'text',
			'name'			=> 'class',
			'title'			=> esc_html__( 'Class', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set the booking system CSS classes. Leave an empty space between each class.', 'booking-activities' )
		);
	}
	
	// Availability Period Start
	if( ! $fields || in_array( 'availability_period_start', $fields, true ) ) {
		$defaults[ 'availability_period_start' ] = array(
			'type'			=> 'number',
			'name'			=> 'availability_period_start',
			'options'		=> array( 'min' => 0, 'step' => 1 ),
			/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
			'title'			=> esc_html__( 'Events will be bookable in', 'booking-activities' ),
			/* translators: Arrives after a field indicating a number of days before the event. E.g.: "Events will be bookable in 2 days from today". */
			'label'			=> esc_html__( 'days from today', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set the beginning of the availability period. E.g.: "2", your customers may book events starting in 2 days at the earliest. They are no longer allowed to book events starting earlier (like today or tomorrow).', 'booking-activities' )
		);
	}
	
	// Availability Period End
	if( ! $fields || in_array( 'availability_period_end', $fields, true ) ) {
		$defaults[ 'availability_period_end' ] = array(
			'type'			=> 'number',
			'name'			=> 'availability_period_end',
			'options'		=> array( 'min' => 0, 'step' => 1 ),
			/* translators: Followed by a field indicating a number of days before the event. E.g.: "Events are bookable for up to 30 days from today". */
			'title'			=>  esc_html__( 'Events are bookable for up to', 'booking-activities' ),
			'label'			=> esc_html__( 'days from today', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set the end of the availability period. E.g.: "30", your customers may book events starting within 30 days at the latest. They are not allowed yet to book events starting later.', 'booking-activities' )
		);
	}
	
	// Opening
	if( ! $fields || in_array( 'start', $fields, true ) ) {
		$defaults[ 'start' ] = array(
			'type'			=> 'date',
			'name'			=> 'start',
			'title'			=> esc_html__( 'Opening', 'booking-activities' ),
			'tip'			=> esc_html__( 'The calendar will start at this date.', 'booking-activities' )
		);
	}
	
	// Closing
	if( ! $fields || in_array( 'end', $fields, true ) ) {
		$defaults[ 'end' ] = array(
			'type'			=> 'date',
			'name'			=> 'end',
			'title'			=> esc_html__( 'Closing', 'booking-activities' ),
			'tip'			=> esc_html__( 'The calendar will end at this date.', 'booking-activities' )
		);
	}
	
	// Past events
	if( ! $fields || in_array( 'past_events', $fields, true ) ) {
		$defaults[ 'past_events' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'past_events',
			'value'			=> 0,
			'title'			=> esc_html__( 'Display past events', 'booking-activities' ),
			'tip'			=> esc_html__( 'Display events out of the availability period. If they cannot be booked, they will be grayed out.', 'booking-activities' )
		);
	}
	
	// Past events bookable
	if( ! $fields || in_array( 'past_events_bookable', $fields, true ) ) {
		$defaults[ 'past_events_bookable' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'past_events_bookable',
			'value'			=> 0,
			'title'			=> esc_html__( 'Make past events bookable', 'booking-activities' ),
			'tip'			=> esc_html__( 'Allow customers to select events out of the availability period and book them.', 'booking-activities' )
		);
	}
	
	return apply_filters( 'bookacti_booking_system_fields_default_data', $defaults, $fields );
}


/**
 * Check the selected event / group of events data before booking
 * @version 1.7.14
 * @param int $group_id
 * @param int $event_id
 * @param string $event_start Start datetime of the event to check (format 2017-12-31T23:59:59)
 * @param string $event_end End datetime of the event to check (format 2017-12-31T23:59:59)
 * @param int $quantity Desired number of bookings
 * @param int $form_id Set your form id to validate the event against its form parameters. Default is 0: ignore form validation.
 * @return array
 */
function bookacti_validate_booking_form( $group_id, $event_id, $event_start, $event_end, $quantity, $form_id = 0 ) {
	
	$validated = array( 'status' => 'failed' );
	
	// Check if the event / group exists before everything
	$exists = false;
	if( $group_id === 'single' ) {
		$event	= bookacti_get_event_by_id( $event_id );
		$exists	= bookacti_is_existing_event( $event, $event_start, $event_end );
	} else if( is_numeric( $group_id ) ) {
		$group	= bookacti_get_group_of_events( $group_id );
		$exists	= bookacti_is_existing_group_of_events( $group );
	}
	if( ! $exists ) {
		$validated['error'] = 'do_not_exist';
		$validated['message'] = $group_id === 'single' ? __( "The event doesn't exist, please pick an event and try again.", 'booking-activities' ) : __( "The group of events doesn't exist, please pick an event and try again.", 'booking-activities' );
		return apply_filters( 'bookacti_validate_booking_form', $validated, $group_id, $event_id, $event_start, $event_end, $quantity, $form_id );
	}
	
	
	// Form checks
	if( $form_id ) { 
		// Check if the event can be booked on the given form
		if( $group_id === 'single' ) {
			$form_validated = bookacti_is_event_available_on_form( $form_id, $event_id, $event_start, $event_end );
		} else if( is_numeric( $group_id ) ) {
			$form_validated = bookacti_is_group_of_events_available_on_form( $form_id, $group_id );
		}
		
		// If the event doesn't match the form parameters, stop the validation here and return the error
		if( $form_validated[ 'status' ] !== 'success' ) {
			return apply_filters( 'bookacti_validate_booking_form', $form_validated, $group_id, $event_id, $event_start, $event_end, $quantity, $form_id );
		}
	}
	
	// Availability checks
	if( ! empty( $_POST[ 'login_type' ] ) && $_POST[ 'login_type' ] === 'no_account' 
	&&  ! empty( $_POST[ 'email' ] ) && is_email( $_POST[ 'email' ] ) ) { 
		$user_id = $_POST[ 'email' ]; 
	}
	$user_id = apply_filters( 'bookacti_current_user_id', ! empty( $user_id ) ? $user_id : get_current_user_id() );
	$quantity_already_booked = 0;
	$number_of_users = 0;
	$allowed_roles = array();
	
	// Validate single booking
	if( $group_id === 'single' ) {
		$title			= apply_filters( 'bookacti_translate_text', $event->title );
		$activity_data	= bookacti_get_metadata( 'activity', $event->activity_id );
		
		$availability	= bookacti_get_event_availability( $event_id, $event_start, $event_end );
		$min_quantity	= isset( $activity_data[ 'min_bookings_per_user' ] ) ? intval( $activity_data[ 'min_bookings_per_user' ] ) : 0;
		$max_quantity	= isset( $activity_data[ 'max_bookings_per_user' ] ) ? intval( $activity_data[ 'max_bookings_per_user' ] ) : 0;
		$max_users		= isset( $activity_data[ 'max_users_per_event' ] ) ? intval( $activity_data[ 'max_users_per_event' ] ) : 0;
		
		// Check if the user has already booked this event
		if( ( $min_quantity || $max_quantity || $max_users ) && $user_id ) {
			$filters = bookacti_format_booking_filters( array(
				'event_id'		=> $event_id,
				'event_start'	=> $event_start,
				'event_end'		=> $event_end,
				'user_id'		=> $user_id,
				'active'		=> 1
			) );
			$quantity_already_booked = bookacti_get_number_of_bookings( $filters );
		}
		
		// Check if the event has already been booked by other users
		if( $max_users ) {
			$bookings_made_by_other_users = bookacti_get_number_of_bookings_per_user_by_event( $event_id, $event_start, $event_end );
			$number_of_users = count( $bookings_made_by_other_users );
		}
		
		// Check allowed roles
		if( isset( $activity_data[ 'allowed_roles' ] ) && $activity_data[ 'allowed_roles' ] ) {
			$allowed_roles = $activity_data[ 'allowed_roles' ];
		}
	
	// Validate group booking
	} else if( is_numeric( $group_id ) ) {
		$title			= apply_filters( 'bookacti_translate_text', $group->title );
		$category_data	= bookacti_get_metadata( 'group_category', $group->category_id );
		
		$availability	= bookacti_get_group_of_events_availability( $group_id );
		$min_quantity	= isset( $category_data[ 'min_bookings_per_user' ] ) ? intval( $category_data[ 'min_bookings_per_user' ] ) : 0;
		$max_quantity	= isset( $category_data[ 'max_bookings_per_user' ] ) ? intval( $category_data[ 'max_bookings_per_user' ] ) : 0;
		$max_users		= isset( $category_data[ 'max_users_per_event' ] ) ? intval( $category_data[ 'max_users_per_event' ] ) : 0;
		
		// Check if the user has already booked this group of events
		if( ( $min_quantity || $max_quantity || $max_users ) && $user_id ) {
			$filters = bookacti_format_booking_filters( array(
				'event_group_id'		=> $group_id,
				'user_id'				=> $user_id,
				'active'				=> 1,
				'group_by'				=> 'booking_group'
			) );
			$quantity_already_booked = bookacti_get_number_of_bookings( $filters );
		}
		
		// Check if the event has already been booked by other users
		if( $max_users ) {
			$bookings_made_by_other_users = bookacti_get_number_of_bookings_per_user_by_group_of_events( $group_id );
			$number_of_users = count( $bookings_made_by_other_users );
		}
		
		// Check allowed roles
		if( isset( $category_data[ 'allowed_roles' ] ) && $category_data[ 'allowed_roles' ] ) {
			$allowed_roles = $category_data[ 'allowed_roles' ];
		}
	}
	
	// Init boolean test variables
	$is_event				= false;
	$is_qty_inf_to_avail	= false;
	$is_qty_sup_to_0		= false;
	$is_qty_sup_to_min		= false;
	$is_qty_inf_to_max		= false;
	$is_users_inf_to_max	= false;
	$has_allowed_roles		= false;
	$can_book				= false;
	
	// Sanitize
	$quantity		= intval( $quantity );
	$availability	= intval( $availability );
	
	// Make the tests and change the booleans
	if( $group_id !== '' && $event_id !== '' && $event_start !== '' && $event_end !== '' )	{ $is_event = true; }
	if( $quantity > 0 )																		{ $is_qty_sup_to_0 = true; }
	if( $quantity <= $availability )														{ $is_qty_inf_to_avail = true; }
	if( $min_quantity === 0 || ( $quantity + $quantity_already_booked ) >= $min_quantity )	{ $is_qty_sup_to_min = true; }
	if( $max_quantity === 0 || $quantity <= ( $max_quantity - $quantity_already_booked ) )	{ $is_qty_inf_to_max = true; }
	if( $max_users === 0 || $quantity_already_booked || $number_of_users < $max_users )		{ $is_users_inf_to_max = true; }
	if( ! $allowed_roles 
		|| in_array( 'all', $allowed_roles, true ) 
		|| apply_filters( 'bookacti_bypass_roles_check', false ) )							{ $has_allowed_roles = true; }
	else { 
		$is_allowed		= false;
		$current_user	= wp_get_current_user();
		
		if( $current_user && ! empty( $current_user->roles ) ) {
			$is_allowed = array_intersect( $current_user->roles, $allowed_roles );
		}
		
		if( $is_allowed ) { $has_allowed_roles = true; }
	}
	
	if( $is_event && $exists && $is_qty_sup_to_0 && $is_qty_sup_to_min && $is_qty_inf_to_max && $is_users_inf_to_max && $is_qty_inf_to_avail && $has_allowed_roles ) { $can_book = true; }

	if( $can_book ) {
		$validated['status'] = 'success';
	} else {
		$validated['status'] = 'failed';
		if( ! $is_event ) {
			$validated['error'] = 'no_event_selected';
			$validated['message'] = esc_html__( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' );
		} else if( ! $is_qty_sup_to_0 ) {
			$validated['error'] = 'qty_inf_to_0';
			$validated['message'] = esc_html__( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', 'booking-activities' );
		} else if( ! $is_qty_sup_to_min ) {
			$validated['error'] = 'qty_inf_to_min';
			/* translators: %1$s is a variable number of bookings, %2$s is the event title. */
			$validated['message'] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title );
			if( $quantity_already_booked ) {
				/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
				$validated['message'] .= ' ' . sprintf( esc_html( _n( 'and you have already booked %1$s place, but the minimum number of reservations required per user is %2$s.', 'and you have already booked %1$s places, but the minimum number of reservations required per user is %2$s.', $quantity_already_booked, 'booking-activities' ) ), $quantity_already_booked, $min_quantity );
			} else {
				/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
				$validated['message'] .= ' ' . sprintf( esc_html__( 'but the minimum number of reservations required per user is %1$s.', 'booking-activities' ), $min_quantity );
			}	
			/* translators: %1$s is a variable quantity. */
			$validated['message'] .= $min_quantity - $quantity_already_booked > 0 ? ' ' . sprintf( esc_html__( 'Please choose another event or increase the quantity to %1$s.', 'booking-activities' ), $min_quantity - $quantity_already_booked ) : ' ' . esc_html__( 'Please choose another event', 'booking-activities' );
		} else if( ! $is_qty_inf_to_max ) {
			$validated['error'] = 'qty_sup_to_max';
			$validated['message'] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title );
			if( $quantity_already_booked ) {
				/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
				$validated['message'] .= ' ' . sprintf( esc_html( _n( 'but you have already booked %1$s place and the maximum number of reservations allowed per user is %2$s.', 'but you have already booked %1$s places and the maximum number of reservations allowed per user is %2$s.', $quantity_already_booked, 'booking-activities' ) ), $quantity_already_booked, $max_quantity );
			} else {
				/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
				$validated['message'] .= ' ' . sprintf( esc_html__( 'but the maximum number of reservations allowed per user is %1$s.', 'booking-activities' ), $max_quantity );
			}
			/* translators: %1$s is a variable quantity. */
			$validated['message'] .= $max_quantity - $quantity_already_booked > 0  ? ' ' . sprintf( esc_html__( 'Please choose another event or decrease the quantity to %1$s.', 'booking-activities' ), $max_quantity - $quantity_already_booked ) : ' ' . esc_html__( 'Please choose another event', 'booking-activities' );
		} else if( ! $is_users_inf_to_max ) {
			$validated['error'] = 'users_sup_to_max';
			$validated['message'] = esc_html__( 'This event has reached the maximum number of users allowed. Bookings from other users are no longer accepted. Please choose another event.', 'booking-activities' );
		} else if( $availability === 0 ) {
			$validated['error'] = 'no_availability';
			$validated['availability'] = $availability;
			/* translators: %1$s is the event title. */
			$validated['message'] = sprintf( esc_html__( 'The event "%1$s" is no longer available on this time slot. Please choose another event.', 'booking-activities' ), $title );
		} else if( ! $is_qty_inf_to_avail ) {
			$validated['error'] = 'qty_sup_to_avail';
			$validated['availability'] = $availability;
			$validated['message'] = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title )
							. ' ' . sprintf( esc_html( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $availability, 'booking-activities' ) ), $availability )
							. ' ' . esc_html__( 'Please choose another event or decrease the quantity.', 'booking-activities' );
		} else if( ! $has_allowed_roles ) {
			$validated['error'] = 'role_not_allowed';
			if( is_user_logged_in() ) {
				$validated['message'] = esc_html__( 'This event is not available in your user category. Please choose another event.', 'booking-activities' );
			} else {
				$validated['message'] = esc_html__( 'This event is restricted to certain categories of users. Please log in first.', 'booking-activities' );
			}
				
		} else {
			$validated['error'] = 'failed';
			$validated['message'] = esc_html__( 'An error occurred, please try again.', 'booking-activities' );
		}
	}
	
	return apply_filters( 'bookacti_validate_booking_form', $validated, $group_id, $event_id, $event_start, $event_end, $quantity, $form_id );
}


/**
 * Check if an event or an occurence exists
 * 
 * @version 1.3.1
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
			$is_existing_event = bookacti_is_existing_single_event( $event->event_id, $event_start, $event_end );
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


/**
 * Check if an event can be book with the given form
 * @since 1.5.0
 * @version 1.7.17
 * @param int $form_id
 * @param int $event_id
 * @param string $event_start
 * @param string $event_end
 * @return array
 */
function bookacti_is_event_available_on_form( $form_id, $event_id, $event_start, $event_end ) {
	$validated		= array( 'status' => 'failed' );
	$calendar_data	= bookacti_get_form_field_data_by_name( $form_id, 'calendar' );

	// Check if the form exists and if it has a calendar field (compulsory)
	$form_exists = ! empty( $calendar_data );
	if( ! $form_exists ) {
		$validated[ 'error' ] = 'invalid_form';
		$validated[ 'message' ] = esc_html__( 'Failed to retrieve the requested form data.', 'booking-activities' );
		return $validated;
	}
	
	
	// Check if the event is displayed on the form
	$belongs_to_form = true;
	$event = bookacti_get_event_by_id( $event_id );

	// If the form calendar doesn't have the event template or the event activity
	if( ( $calendar_data[ 'calendars' ] && ! in_array( $event->template_id, $calendar_data[ 'calendars' ] ) )
	||  ( $calendar_data[ 'activities' ] && ! in_array( $event->activity_id, $calendar_data[ 'activities' ] ) ) ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'The selected event is not supposed to be available on this form.', 'booking-activities' );
	}

	// If the form calendar have groups, with no possibility to book a single event
	if( $belongs_to_form && $calendar_data[ 'group_categories' ] !== false && ! $calendar_data[ 'groups_single_events' ] ) {
		if( $calendar_data[ 'groups_only' ] ) {
			$belongs_to_form = false;
			$validated[ 'message' ] = esc_html__( 'You cannot book single events with this form, you must select a group of events.', 'booking-activities' );
		} else {
			// Check if the event belong to a group
			$event_groups = bookacti_get_event_groups( $event->event_id, $event_start, $event_end );
			$event_categories = array();
			foreach( $event_groups as $event_group ) {
				if( ! in_array( $event_group->category_id, $event_categories ) ) {
					$event_categories[] = $event_group->category_id;
				}
			}

			// If the categories array is empty, it means "take all categories"
			if( is_array( $calendar_data[ 'group_categories' ] ) && empty( $calendar_data[ 'group_categories' ] ) ) {
				$calendar_data[ 'group_categories' ] = array_keys( bookacti_get_group_categories( $event->template_id ) );
			}

			// Check if the event belong to a group available on the calendar
			if( array_intersect( $event_categories, $calendar_data[ 'group_categories' ] ) ) {
				$belongs_to_form = false;
				$validated[ 'message' ] = esc_html__( 'The selected event is part of a group and cannot be booked alone.', 'booking-activities' );
			}
		}
	}
	
	// Check if the groups of events is in its template range
	if( $belongs_to_form ) {
		$in_template_range = false;
		$template_range = bookacti_get_mixed_template_range( $event->template_id );
		if( $template_range ) {
			$event_start_dt		= DateTime::createFromFormat( 'Y-m-d H:i:s', $event_start );
			$event_end_dt		= DateTime::createFromFormat( 'Y-m-d H:i:s', $event_end );
			$template_start_dt	= DateTime::createFromFormat( 'Y-m-d H:i:s', $template_range[ 'start' ] . ' 00:00:00' );
			$template_end_dt	= DateTime::createFromFormat( 'Y-m-d H:i:s', $template_range[ 'end' ] . ' 23:59:59' );
			if( $event_start_dt >= $template_start_dt && $event_end_dt <= $template_end_dt ) {
				$in_template_range = true;
			}
		}
		if( ! $in_template_range ) {
			$belongs_to_form = false;
			$validated[ 'message' ] = esc_html__( 'The event is out of its calendar range, please pick another event and try again.', 'booking-activities' );
		}
	}
	
	if( ! $belongs_to_form ) {
		$validated[ 'error' ] = 'event_not_in_form';
		return $validated;
	}
	
	$past_events_bookable = isset( $calendar_data[ 'past_events_bookable' ] ) ? $calendar_data[ 'past_events_bookable' ] : 0;
	
	if( ! $past_events_bookable ) {
		// Check if the event is past
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$started_events_bookable	= bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
		$event_start_obj			= new DateTime( $event_start, $timezone );
		$event_end_obj				= new DateTime( $event_end, $timezone );
		$current_time				= new DateTime( 'now', $timezone );
		if( ( $event_start_obj < $current_time )
		&& ! ( $started_events_bookable && $event_end_obj > $current_time ) ) {
			$validated[ 'error' ] = 'past_event';
			$validated[ 'message' ] = esc_html__( 'You cannot book a past event.', 'booking-activities' );
			return $validated;
		}
	
		// Check if the event is in the availability period
		$availability_period = bookacti_get_availability_period( $calendar_data[ 'template_data' ], $past_events_bookable );
		$calendar_start	= new DateTime( $availability_period[ 'start' ] . ' 00:00:00', $timezone );
		$calendar_end	= new DateTime( $availability_period[ 'end' ] . ' 23:59:59', $timezone );
			
		if( $event_start_obj < $calendar_start ) {
			$validated[ 'error' ] = 'event_starts_before_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_start->format( 'Y-m-d H:i:s' ), esc_html__( 'F d, Y', 'booking-activities' ) );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book an event that starts before %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
		if( $event_end_obj > $calendar_end ) {
			$validated[ 'error' ] = 'event_ends_after_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_end->format( 'Y-m-d H:i:s' ), esc_html__( 'F d, Y', 'booking-activities' ) );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book an event that takes place after %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
	}
	
	// So far, so good
	$validated[ 'status' ] = 'success';
	return $validated;
}


/**
 * Check if a group of events can be book with the given form
 * @since 1.5.0
 * @version 1.7.17
 * @param int $form_id
 * @param int $group_id
 * @return array
 */
function bookacti_is_group_of_events_available_on_form( $form_id, $group_id ) {
	$validated		= array( 'status' => 'failed' );
	$calendar_data	= bookacti_get_form_field_data_by_name( $form_id, 'calendar' );;
	
	// Check if the form exists and if it has a calendar field (compulsory)
	$form_exists = ! empty( $calendar_data );
	if( ! $form_exists ) {
		$validated[ 'error' ] = 'invalid_form';
		$validated[ 'message' ] = esc_html__( 'Failed to retrieve the requested form data.', 'booking-activities' );
		return $validated;
	}
	
	
	// Check if the group of events is displayed on the form
	$belongs_to_form	= true;
	$group				= bookacti_get_group_of_events( $group_id );
	$category			= bookacti_get_group_category( $group->category_id, ARRAY_A );
	
	// If the form calendar doesn't have the group of events' template
	if( $calendar_data[ 'calendars' ] && ! in_array( $category[ 'template_id' ], $calendar_data[ 'calendars' ] ) ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'The selected events are not supposed to be available on this form.', 'booking-activities' );
	}
	
	// If the form calendar doesn't have groups
	if( $belongs_to_form && $calendar_data[ 'group_categories' ] === false ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'You cannot book groups of events with this form, you must select a single event.', 'booking-activities' );
	}

	// If the form calendar have groups
	if( $belongs_to_form && is_array( $calendar_data[ 'group_categories' ] ) ) {
		// If the categories array is empty, it means "take all categories"
		if( empty( $calendar_data[ 'group_categories' ] ) ) {
			$calendar_data[ 'group_categories' ] = array_keys( bookacti_get_group_categories( $category[ 'template_id' ] ) );
		}

		// Check if the group of event category is available on this form
		if( ! in_array( $group->category_id, $calendar_data[ 'group_categories' ] ) ) {
			$belongs_to_form = false;
			$validated[ 'message' ] = esc_html__( 'The selected goup of events is not supposed to be available on this form.', 'booking-activities' );
		}
	}
	
	// Check if the groups of events is in its template range
	if( $belongs_to_form ) {
		$in_template_range = false;
		$template_range = bookacti_get_mixed_template_range( $category[ 'template_id' ] );
		if( $template_range ) {
			$event_start_dt		= DateTime::createFromFormat( 'Y-m-d H:i:s', $group->start );
			$event_end_dt		= DateTime::createFromFormat( 'Y-m-d H:i:s', $group->end );
			$template_start_dt	= DateTime::createFromFormat( 'Y-m-d H:i:s', $template_range[ 'start' ] . ' 00:00:00' );
			$template_end_dt	= DateTime::createFromFormat( 'Y-m-d H:i:s', $template_range[ 'end' ] . ' 23:59:59' );
			if( $event_start_dt >= $template_start_dt && $event_end_dt <= $template_end_dt ) {
				$in_template_range = true;
			}
		}
		if( ! $in_template_range ) {
			$belongs_to_form = false;
			$validated[ 'message' ] = esc_html__( 'The group of events is out of its calendar range, please pick another event and try again.', 'booking-activities' );
		}
	}
	
	if( ! $belongs_to_form ) {
		$validated[ 'error' ] = 'event_not_in_form';
		return $validated;
	}
	
	$past_events_bookable = isset( $calendar_data[ 'past_events_bookable' ] ) ? $calendar_data[ 'past_events_bookable' ] : 0;
	
	if( ! $past_events_bookable ) {
		// Check if the event is past
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$started_groups_bookable	= isset( $category[ 'settings' ][ 'started_groups_bookable' ] ) && in_array( $category[ 'settings' ][ 'started_groups_bookable' ], array( 0, 1, '0', '1', true, false ), true ) ? intval( $category[ 'settings' ][ 'started_groups_bookable' ] ) : bookacti_get_setting_value( 'bookacti_general_settings', 'started_groups_bookable' );
		$group_start				= new DateTime( $group->start, $timezone );
		$group_end					= new DateTime( $group->end, $timezone );
		$current_time				= new DateTime( 'now', $timezone );
		if( ( $group_start < $current_time )
		&& ! ( $started_groups_bookable && $group_end > $current_time ) ) {
			$validated[ 'error' ] = 'past_group_of_events';
			$validated[ 'message' ] = esc_html__( 'You cannot book a group of events if any of its events is past.', 'booking-activities' );
			return $validated;
		}
	
		// Check if the group of events is in the availability period
		$availability_period = bookacti_get_availability_period( $calendar_data[ 'template_data' ], $past_events_bookable );
		$calendar_start	= new DateTime( $availability_period[ 'start' ] . ' 00:00:00', $timezone );
		$calendar_end	= new DateTime( $availability_period[ 'end' ] . ' 23:59:59', $timezone );
		
		if( $group_start < $calendar_start ) {
			$validated[ 'error' ] = 'group_of_events_starts_before_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_start->format( 'Y-m-d H:i:s' ), esc_html__( 'F d, Y', 'booking-activities' ) );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book a group if any of its events starts before %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
		if( $group_end > $calendar_end ) {
			$validated[ 'error' ] = 'group_of_events_ends_after_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_end->format( 'Y-m-d H:i:s' ), esc_html__( 'F d, Y', 'booking-activities' ) );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book a group if any of its events takes place after %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
	}	
	
	// So far, so good
	$validated[ 'status' ] = 'success';
	return $validated;
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




/***** EVENTS *****/

/**
 * Get array of events from raw events from database
 * @since 1.2.2
 * @version 1.7.1
 * @param array $events Array of objects events from database
 * @param boolean $past_events
 * @param array $interval array('start'=> start date, 'end'=> end date)
 * @param boolean $skip_exceptions Whether to retrieve occurence on exceptions
 * @return array
 */
function bookacti_get_events_array_from_db_events( $events, $past_events, $interval, $skip_exceptions = true ) {
	$events_array = array( 'data' => array(), 'events' => array() );
	
	// Get event ids
	$event_ids = array();
	foreach ( $events as $event ) { $event_ids[] = $event->event_id; }
	
	// Get event exceptions
	$exceptions_dates = array();
	if( $skip_exceptions ) {
		$exceptions_dates = bookacti_get_exceptions_dates( array(), $event_ids );
	}
	
	// Get event meta
	$events_meta = bookacti_get_metadata( 'event', $event_ids );
	
	foreach( $events as $event ) {
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
			'settings'			=> isset( $events_meta[ $event->event_id ] ) ? $events_meta[ $event->event_id ] : array()
		);
		
		// Build events data array
		$events_array[ 'data' ][ $event->event_id ] = array_merge( $event_fc_data, $event_bookacti_data );

		// Build events array
		if( $event->repeat_freq === 'none' ) {
			$events_array[ 'events' ][] = $event_fc_data;
		} else {
			$event_exceptions = $skip_exceptions && ! empty( $exceptions_dates[ $event->event_id ] ) ? $exceptions_dates[ $event->event_id ] : array();
			$events_array[ 'events' ] = array_merge( $events_array[ 'events' ], bookacti_get_occurences_of_repeated_event( $event, $past_events, $interval, $event_exceptions ) );
		}
	}

	return $events_array;
}


/**
 * Get a new interval of events to load. Computed from the compulsory interval, or now's date and template interval.
 * 
 * @since 1.2.2
 * @version 1.7.16
 * @param array $template_interval array( 'start'=>Calendar start, 'end'=> Calendar end, 'settings'=> array( 'availability_period_start'=> Relative start from today, 'availability_period_end'=> Relative end from today) ) 
 * @param array $min_interval array( 'start'=> Calendar start, 'end'=> Calendar end)
 * @param int $interval_duration Number of days of the interval
 * @param bool $past_events
 * @return array
 */
function bookacti_get_new_interval_of_events( $template_interval, $min_interval = array(), $interval_duration = false, $past_events = false ) {
	if( ! isset( $template_interval[ 'start' ] ) || ! isset( $template_interval[ 'end' ] ) ) { return array(); }
	
	// Take default availability period if not set
	if( ! isset( $template_interval[ 'settings' ][ 'availability_period_start' ] ) ){ $template_interval[ 'settings' ][ 'availability_period_start' ]	= 0; }
	if( ! isset( $template_interval[ 'settings' ][ 'availability_period_end' ] ) )	{ $template_interval[ 'settings' ][ 'availability_period_end' ]		= 0; }
	
	$timezone		= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_time	= new DateTime( 'now', $timezone );
	$current_date	= $current_time->format( 'Y-m-d' );
	
	// Restrict template interval if an availability period is set
	$availability_period = bookacti_get_availability_period( $template_interval, $past_events );

	$calendar_start	= new DateTime( $availability_period[ 'start' ] . ' 00:00:00', $timezone );
	$calendar_end	= new DateTime( $availability_period[ 'end' ] . ' 23:59:59', $timezone );
	
	if( ! $past_events && $calendar_end < $current_time ) { return array(); }
	
	if( ! $min_interval ) {
		if( $calendar_start > $current_time ) {
			$min_interval = array( 'start' => $availability_period[ 'start' ], 'end' => $availability_period[ 'start' ] );
		} else if( $calendar_end < $current_time ) {
			$min_interval = array( 'start' => $availability_period[ 'end' ], 'end' => $availability_period[ 'end' ] );
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
 * Get availability period according to relative and absolute dates
 * @since 1.7.16
 * @param array $template_data
 * @param boolean $bypass_relative_period
 * @return array
 */
function bookacti_get_availability_period( $template_data, $bypass_relative_period = false ) {
	
	$calendar_start_date	= $template_data[ 'start' ];
	$calendar_end_date		= $template_data[ 'end' ];
	
	if( ! $bypass_relative_period ) { 
		// Take default availability period if not set
		$availability_period_start	= isset( $template_data[ 'settings' ][ 'availability_period_start' ] ) ? intval( $template_data[ 'settings' ][ 'availability_period_start' ] ) : 0;
		$availability_period_end	= isset( $template_data[ 'settings' ][ 'availability_period_end' ] ) ? intval( $template_data[ 'settings' ][ 'availability_period_end' ] ) : 0; 
		$timezone					= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_time				= new DateTime( 'now', $timezone );

		// Restrict template interval if an availability period is set
		if( $availability_period_start > 0 ) {
			$availability_start_time = clone $current_time;
			$availability_start_time->add( new DateInterval( 'P' . $availability_period_start . 'D' ) );
			$availability_start_date = $availability_start_time->format( 'Y-m-d' );
			if( strtotime( $availability_start_date ) > strtotime( $template_data[ 'start' ] ) ) {
				$calendar_start_date = $availability_start_date;
			}
		}
		if( $availability_period_end > 0 ) {
			$availability_end_time = clone $current_time;
			$availability_end_time->add( new DateInterval( 'P' . $availability_period_end . 'D' ) );
			$availability_end_date = $availability_end_time->format( 'Y-m-d' );
			if( strtotime( $availability_end_date ) < strtotime( $template_data[ 'end' ] ) ) {
				$calendar_end_date = $availability_end_date;
			}
		}
	}
	
	$availability_period = array( 'start' => $calendar_start_date, 'end' => $calendar_end_date );

	return apply_filters( 'bookacti_availability_period', $availability_period, $template_data, $bypass_relative_period );
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
 * Get occurences of repeated events
 * @since 1.2.2 (replace bookacti_create_repeated_events)
 * @version 1.7.10
 * @param object $event Event data 
 * @param boolean $past_events Whether to compute past events
 * @param array $interval array('start' => string: start date, 'end' => string: end date)
 * @param array $exceptions_dates Array of dates
 * @return array
 */
function bookacti_get_occurences_of_repeated_event( $event, $past_events = false, $interval = array(), $exceptions_dates = array() ) {

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
	$repeat_interval	= new DateInterval( 'P1D' ); // Default to daily to avoid unexpected behavior such as infinite loop

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

		$first_potential_event_start		= new DateTime( $current_date . ' ' . $event_start_time, $timezone );
		$first_potential_event_end			= clone $first_potential_event_start;
		$first_potential_event_end->add( $event_duration );
		
		$first_potential_event_is_past		= $first_potential_event_end <= $current_time;
		$first_potential_event_has_started	= $first_potential_event_start <= $current_time;
		
		// Set the repetition "from" date to tommorow if:
		// - The first postential event is today but is already past
		// - The first potential event is today but has already started and started event are not allowed
		if(  $first_potential_event_is_past
		|| ( $first_potential_event_has_started && ! $get_started_events ) ) {
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
			break;
	}
	
	$repeat_interval = apply_filters( 'bookacti_event_repeat_interval', $repeat_interval, $event, $past_events, $interval, $repeat_from, $repeat_to );
	
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
		
		// Check if the occurrence is on an exception
		if( in_array( $occurence_start->format( 'Y-m-d' ), $exceptions_dates, true ) ) { $loop->add( $repeat_interval ); continue; }
		
		// Check if the occurrence is in the interval to be rendered
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
 * Get exceptions dates by event
 * @since 1.7.0
 * @param array $template_ids
 * @param array $event_ids
 * @return array
 */
function bookacti_get_exceptions_dates( $template_ids = array(), $event_ids = array() ) {
	$exceptions_per_event = bookacti_get_exceptions( $template_ids, $event_ids );
	
	if( ! $exceptions_per_event ) { return array(); }
	
	$exceptions_dates = array();
	foreach( $exceptions_per_event as $event_id => $exceptions ) {
		$exceptions_dates[ $event_id ] = array();
		foreach( $exceptions as $exception ) {
			if( $exception[ 'exception_type' ] === 'date' ) {
				$exceptions_dates[ $event_id ][] = $exception[ 'exception_value' ];
			}
		}
	}
	
	return $exceptions_dates;
}


/**
 * Build a user-friendly events list
 * @since 1.1.0
 * @version 1.7.0
 * @param array $booking_events
 * @param int|string $quantity
 * @param string $locale Optional. Default to site locale.
 * @return string
 */
function bookacti_get_formatted_booking_events_list( $booking_events, $quantity = 'hide', $locale = 'site' ) {
	if( ! $booking_events ) { return false; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
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
			'raw_event' => $booking_event,
			'title'		=> isset( $booking_event->title )	? $booking_event->title : ( isset( $booking_event->event_title ) ? $booking_event->event_title : '' ),
			'start'		=> isset( $booking_event->start )	? bookacti_sanitize_datetime( $booking_event->start )	: ( isset( $booking_event->event_start ) ? bookacti_sanitize_datetime( $booking_event->event_start ) : '' ),
			'end'		=> isset( $booking_event->end )		? bookacti_sanitize_datetime( $booking_event->end )		: ( isset( $booking_event->event_end ) ? bookacti_sanitize_datetime( $booking_event->event_end ) : '' ),
			'quantity'	=> $booking_quantity
		);
	}
	
	$messages			= bookacti_get_messages( true );
	$datetime_format	= isset( $messages[ 'date_format_long' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'date_format_long' ][ 'value' ], $locale ) : '';
	$time_format		= isset( $messages[ 'time_format' ][ 'value' ] )		? apply_filters( 'bookacti_translate_text', $messages[ 'time_format' ][ 'value' ], $locale ) : '';
	$date_time_separator= isset( $messages[ 'date_time_separator' ][ 'value' ] )? apply_filters( 'bookacti_translate_text', $messages[ 'date_time_separator' ][ 'value' ], $locale ) : '';
	$dates_separator	= isset( $messages[ 'dates_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'dates_separator' ][ 'value' ], $locale ) : '';
	$quantity_separator = isset( $messages[ 'quantity_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'quantity_separator' ][ 'value' ], $locale ) : '';
	
	$events_list = '';
	foreach( $formatted_events as $event ) {
		// Format the event duration
		$event[ 'duration' ] = '';
		if( $event[ 'start' ] && $event[ 'end' ] ) {
			
			$event_start = bookacti_format_datetime( $event[ 'start' ], $datetime_format );
			
			// Format differently if the event start and end on the same day
			$start_and_end_same_day	= substr( $event[ 'start' ], 0, 10 ) === substr( $event[ 'end' ], 0, 10 );
			if( $start_and_end_same_day ) {
				$event_end = bookacti_format_datetime( $event[ 'end' ], $time_format );
			} else {
				$event_end = bookacti_format_datetime( $event[ 'end' ], $datetime_format );
			}
			
			$class		= $start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
			$separator	= $start_and_end_same_day ? $date_time_separator : $dates_separator;
			
			// Place an arrow between start and end
			$event[ 'duration' ] = '<span class="bookacti-booking-event-start" >' . $event_start . '</span>'
								. '<span class="bookacti-booking-event-date-separator ' . $class . '" >' . $separator . '</span>'
								. '<span class="bookacti-booking-event-end ' . $class . '" >' . $event_end . '</span>';
		}
		
		$event = apply_filters( 'bookacti_formatted_booking_events_list_event_data', $event, $locale );
		
		// Add an element to event list if there is at least a title or a duration
		if( $event[ 'title' ] || $event[ 'duration' ] ) {
			$list_element = '<li>';
			
			if( $event[ 'title' ] ) {
				$list_element .= '<span class="bookacti-booking-event-title" >' . apply_filters( 'bookacti_translate_text', $event[ 'title' ], $locale ) . '</span>';
				if( $event[ 'duration' ] ) {
					$list_element .= '<span class="bookacti-booking-event-title-separator" >' . ' - ' . '</span>';
				}
			}
			if( $event[ 'duration' ] ) {
				$list_element .= $event[ 'duration' ];
			}
			
			if( $event[ 'quantity' ] && $quantity !== 'hide' ) {
				$list_element .= '<span class="bookacti-booking-event-quantity-separator" >' . $quantity_separator . '</span>';
				$list_element .= '<span class="bookacti-booking-event-quantity" >' . $event[ 'quantity' ] . '</span>';
			}
			
			$list_element .= '</li>';
			$events_list .= apply_filters( 'bookacti_formatted_booking_events_list_element', $list_element, $event, $locale );
		}
	}
	
	// Wrap the list only if it is not empty
	if( ! empty( $events_list ) ) {
		$events_list = '<ul class="bookacti-booking-events-list bookacti-custom-scrollbar" style="clear:both;" >' . $events_list . '</ul>';
	}
	
	return apply_filters( 'bookacti_formatted_booking_events_list', $events_list, $booking_events, $quantity, $locale );
}


/**
 * Build a user-friendly comma separated events list
 * @since 1.7.0
 * @param array $booking_events
 * @param int|string $quantity
 * @param string $locale Optional. Default to site locale.
 * @return string
 */
function bookacti_get_formatted_booking_events_list_raw( $booking_events, $quantity = 'hide', $locale = 'site' ) {
	if( ! $booking_events ) { return false; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
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
			'raw_event' => $booking_event,
			'title'		=> isset( $booking_event->title )	? $booking_event->title : ( isset( $booking_event->event_title ) ? $booking_event->event_title : '' ),
			'start'		=> isset( $booking_event->start )	? bookacti_sanitize_datetime( $booking_event->start )	: ( isset( $booking_event->event_start ) ? bookacti_sanitize_datetime( $booking_event->event_start ) : '' ),
			'end'		=> isset( $booking_event->end )		? bookacti_sanitize_datetime( $booking_event->end )		: ( isset( $booking_event->event_end ) ? bookacti_sanitize_datetime( $booking_event->event_end ) : '' ),
			'quantity'	=> $booking_quantity
		);
	}
	
	$messages			= bookacti_get_messages( true );
	$datetime_format	= isset( $messages[ 'date_format_short' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'date_format_short' ][ 'value' ], $locale ) : '';
	$time_format		= isset( $messages[ 'time_format' ][ 'value' ] )		? apply_filters( 'bookacti_translate_text', $messages[ 'time_format' ][ 'value' ], $locale ) : '';
	$date_time_separator= isset( $messages[ 'date_time_separator' ][ 'value' ] )? apply_filters( 'bookacti_translate_text', $messages[ 'date_time_separator' ][ 'value' ], $locale ) : '';
	$dates_separator	= isset( $messages[ 'dates_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'dates_separator' ][ 'value' ], $locale ) : '';
	$quantity_separator = isset( $messages[ 'quantity_separator' ][ 'value' ] )	? apply_filters( 'bookacti_translate_text', $messages[ 'quantity_separator' ][ 'value' ], $locale ) : '';
	
	$events_list = '';
	$i = 0;
	foreach( $formatted_events as $event ) {
		// Format the event duration
		$event[ 'duration' ] = '';
		if( $event[ 'start' ] && $event[ 'end' ] ) {
			
			$event_start = bookacti_format_datetime( $event[ 'start' ], $datetime_format );
			
			// Format differently if the event start and end on the same day
			$start_and_end_same_day	= substr( $event[ 'start' ], 0, 10 ) === substr( $event[ 'end' ], 0, 10 );
			if( $start_and_end_same_day ) {
				$event_end = bookacti_format_datetime( $event[ 'end' ], $time_format );
			} else {
				$event_end = bookacti_format_datetime( $event[ 'end' ], $datetime_format );
			}
			
			$separator	= $start_and_end_same_day ? $date_time_separator : $dates_separator;
			
			// Place an arrow between start and end
			$event[ 'duration' ] = $event_start . $separator . $event_end;
		}
		
		$event = apply_filters( 'bookacti_formatted_booking_events_list_raw_event_data', $event, $locale );
		
		// Add an element to event list if there is at least a title or a duration
		if( $event[ 'title' ] || $event[ 'duration' ] ) {
			$list_element = '';
			if( $i !== 0 ) { $list_element .= ', '; }
			if( $event[ 'title' ] ) {
				$list_element .= apply_filters( 'bookacti_translate_text', $event[ 'title' ], $locale );
				if( $event[ 'duration' ] ) {
					$list_element .= ' ';
				}
			}
			if( $event[ 'duration' ] ) {
				$list_element .= $event[ 'duration' ];
			}
			
			if( $event[ 'quantity' ] && $quantity !== 'hide' ) {
				$list_element .= $quantity_separator . $event[ 'quantity' ];
			}
			
			$events_list .= apply_filters( 'bookacti_formatted_booking_events_list_raw_element', $list_element, $event, $locale );
		}
		++$i;
	}
	
	return apply_filters( 'bookacti_formatted_booking_events_list_raw', $events_list, $booking_events, $quantity, $locale );
}


/**
 * Convert an array of events into ical format
 * @since 1.6.0
 * @version 1.7.0
 * @param array $events
 * @param string $name
 * @param string $description
 * @param int $sequence
 * @return string
 */
function bookacti_convert_events_to_ical( $events, $name = '', $description = '', $sequence = 0 ) {
	if( empty( $events ) || empty( $events[ 'events' ] ) || empty( $events[ 'data' ] ) ) {
		return '';
	}
	
	$occurence_counter = array();
	$site_url		= home_url();
	$site_url_array	= parse_url( $site_url );
	$site_host		= $site_url_array[ 'host' ];
	$site_name		= get_bloginfo( 'name' );
	$timezone		= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj	= new DateTimeZone( $timezone );
	$calname		= $site_name;
	/* translators: %1$s is a link to Booking Activities website. %2$s is the site URL. */
	$caldesc		= sprintf( esc_html__( 'This calendar was generated by %1$s from %2$s.' ), 'Booking Activities (https://booking-activities.fr)', $site_name . ' (' . $site_url . ')' );
	
	if( $name )			{ $calname = $name . ' (' . $calname . ')'; }
	if( $description )	{ $caldesc = $description . ' ' . $caldesc; }
	
	ob_start();
	
	$vcalendar_properties = apply_filters( 'bookacti_ical_vcalendar_properties', array(
		'PRODID'		=> '-//Booking Activities//Booking Activities Calendar//EN',
		'VERSION'		=> '2.0',
		'CALSCALE'		=> 'GREGORIAN',
		'METHOD'		=> 'PUBLISH',
		'X-WR-CALNAME'	=> bookacti_sanitize_ical_property( $calname, 'X-WR-CALNAME' ),
		'X-WR-TIMEZONE'	=> $timezone,
		'X-WR-CALDESC'	=> bookacti_sanitize_ical_property( $caldesc, 'X-WR-CALDESC' )
	), $events, $name, $description, $sequence );
	
	// Display the calendar header
	?>
	BEGIN:VCALENDAR
	<?php
		foreach( $vcalendar_properties as $property => $value ) {
			if( $value === '' ) { continue; }
			echo $property . ':' . $value . PHP_EOL;
		}
		do_action( 'bookacti_ical_vcalendar_before', $events, $name, $description, $sequence );
		
		foreach( $events[ 'events' ] as $event ) {
			// Increase the occurence counter
			if( ! isset( $occurence_counter[ $event[ 'id' ] ] ) ) {
				$occurence_counter[ $event[ 'id' ] ] = 0;
			}
			$occurence_counter[ $event[ 'id' ] ] += 1;
			
			$uid			= $event[ 'id' ] . '-' . $occurence_counter[ $event[ 'id' ] ] . '@' . $site_host;
			$event_start	= new DateTime( $event[ 'start' ], $timezone_obj );
			$event_end		= new DateTime( $event[ 'end' ], $timezone_obj );
			$current_time	= new DateTime( 'now', $timezone_obj );
			$now_formatted	= $current_time->format( 'Ymd\THis' );

			$vevent_properties = apply_filters( 'bookacti_ical_vevent_properties', array(
				'UID'			=> $uid,
				'DTSTART'		=> $event_start->format( 'Ymd\THis' ),
				'DTEND'			=> $event_end->format( 'Ymd\THis' ),
				'DTSTAMP'		=> $now_formatted,
				'CREATED'		=> '',
				'LAST-MODIFIED' => '',
				'SUMMARY'		=> bookacti_sanitize_ical_property( $event[ 'title' ], 'SUMMARY' ),
				'DESCRIPTION'	=> '',
				'LOCATION'		=> '',
				'SEQUENCE'		=> $sequence,
				'STATUS'		=> 'CONFIRMED',
				'TRANSP'		=> 'OPAQUE'
			), $event, $events, $name, $description, $sequence );
		?>
			BEGIN:VEVENT
			<?php
				foreach( $vevent_properties as $property => $value ) {
					if( $value === '' ) { continue; }
					echo $property . ':' . $value . PHP_EOL;
				}
				do_action( 'bookacti_ical_vevent_after', $event, $events, $name, $description, $sequence );
			?>
			END:VEVENT
		<?php
		}

		do_action( 'bookacti_ical_vcalendar_after', $events, $name, $description, $sequence );
	
	// Display the calendar footer
	?>
	END:VCALENDAR
	<?php
	
	// Remove tabs at the beginning and at the end of each new lines
	return preg_replace( '/^\t+|\t+$/m', '', ob_get_clean() );
}


/**
 * Generate a ICAL file of events according to booking system attributes
 * @since 1.6.0
 * @version 1.7.13
 * @param array $atts
 * @param string $calname
 * @param string $caldesc
 * @param int $sequence
 */
function bookacti_export_events_page( $atts, $calname = '', $caldesc = '', $sequence = 0 ) {
	// Retrieve all events, bypass the interval
	$events_interval = bookacti_get_new_interval_of_events( $atts[ 'template_data' ], array(), 999999999, $atts[ 'past_events' ] );
	
	// Get the events
	$groups_ids	= array();
	$events		= array( 'events' => array(), 'data' => array() );
	if( $atts[ 'groups_only' ] ) {
		if( $atts[ 'group_categories' ] !== false ) {
			$groups_data = bookacti_get_groups_of_events( $atts[ 'calendars' ], $atts[ 'group_categories' ], $atts[ 'past_events_bookable' ], true, false, $atts[ 'template_data' ] );
			$groups_ids[] = array_keys( $groups_data );
		}
		if( $groups_ids ) { 
			$events	= bookacti_fetch_grouped_events( $atts[ 'calendars' ], $atts[ 'activities' ], $groups_ids, $atts[ 'group_categories' ], $atts[ 'past_events' ], $events_interval );
		}
	} else if( $atts[ 'bookings_only' ] ) {
		$events	= bookacti_fetch_booked_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'status' ], $atts[ 'user_id' ], $atts[ 'past_events' ], $events_interval );
	} else {
		$events	= bookacti_fetch_events( $atts[ 'calendars' ], $atts[ 'activities' ], $atts[ 'past_events' ], $events_interval );
	}
	
	// Check the filename
	$filename = ! empty( $_REQUEST[ 'filename' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'filename' ] ) : ( ! empty( $atts[ 'filename' ] ) ? sanitize_title_with_dashes( $atts[ 'filename' ] ) : '' );
	if( ! $filename ) { 
		$action = ! empty( $_REQUEST[ 'action' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'action' ] ) : '';
		switch( $action ) {
			case 'bookacti_export_user_booked_events':
			case 'bookacti_export_booked_events':
				$filename = 'my-bookings';
				break;
			case 'bookacti_export_form_events':
				$filename = ! empty( $_REQUEST[ 'form_id' ] ) ? 'booking-activities-events-form-' . $_REQUEST[ 'form_id' ] : 'booking-activities-events-form-unknown';
				break;
			default:
				$filename = 'my-events';
				break;
		}
	}
	if( substr( $filename, -4 ) !== '.ics' ) { $filename .= '.ics'; }
	
	header( 'Content-type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Cache-Control: no-cache, must-revalidate' ); // HTTP/1.1
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Expired date to force third-party calendars to refresh soon
	
	echo bookacti_convert_events_to_ical( $events, $calname, $caldesc, $sequence );
	
	exit;
}




// GROUPS OF EVENTS

/**
 * Book all events of a group
 * 
 * @version 1.5.4
 * @param int|string $user_id
 * @param int $event_group_id
 * @param int $quantity
 * @param string $state
 * @param string $payment_status
 * @param string $expiration_date
 * @param int $form_id
 * @return int|boolean
 */
function bookacti_book_group_of_events( $user_id, $event_group_id, $quantity, $state = 'booked', $payment_status = 'none', $expiration_date = NULL, $form_id = NULL ) {

	// Insert the booking group
	$booking_group_id = bookacti_insert_booking_group( $user_id, $event_group_id, $state, $payment_status, $form_id );

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
		bookacti_insert_booking( $user_id, $event[ 'id' ], $event[ 'start' ], $event[ 'end' ], $quantity, $state, $payment_status, $expiration_date, $booking_group_id, $form_id );
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