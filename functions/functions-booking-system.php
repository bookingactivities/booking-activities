<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/***** BOOKING SYSTEM *****/

/**
 * Get a booking system based on given parameters
 * @version 1.8.10
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @return string
 */
function bookacti_get_booking_system( $atts ) {
	// Get booking system data
	$booking_system_data = bookacti_get_booking_system_data( $atts );
	
	ob_start();
	
	do_action( 'bookacti_before_booking_system_container', $atts, $booking_system_data );
	
	// Encrypt user_id
	$public_user_id = ! empty( $atts[ 'user_id' ] ) ? $atts[ 'user_id' ] : 0;
	if( $public_user_id && ( ( is_numeric( $public_user_id ) && strlen( (string) $public_user_id ) < 16 ) || is_email( $public_user_id ) ) ) { $public_user_id = bookacti_encrypt( $public_user_id ); }
	
	// Let plugins define what data should be passed to JS
	$public_booking_system_data = apply_filters( 'bookacti_public_booking_system_data', array_merge( $booking_system_data, array( 'user_id' => $public_user_id ) ), $atts );
	?>

	<div class='bookacti-booking-system-container' id='<?php echo esc_attr( $booking_system_data[ 'id' ] . '-container' ); ?>' >
		<script>
			// Compatibility with Optimization plugins
			if( typeof bookacti === 'undefined' ) { var bookacti = { booking_system:[] }; }
			bookacti.booking_system[ '<?php echo $booking_system_data[ 'id' ]; ?>' ] = <?php echo json_encode( $public_booking_system_data ); ?>;
		</script>
				
		<div class='bookacti-booking-system-inputs'>
			<!-- Backward compatibility -->
			<input type='hidden' name='bookacti_group_id' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'group_id' ] : 'single'; ?>' />
			<input type='hidden' name='bookacti_event_id' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'id' ] : ''; ?>' />
			<input type='hidden' name='bookacti_event_start' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'start' ] : ''; ?>' />
			<input type='hidden' name='bookacti_event_end' value='<?php echo $booking_system_data[ 'picked_events' ] ? $booking_system_data[ 'picked_events' ][ 0 ][ 'end' ] : ''; ?>' />
			<?php 
				$i = 0;
				foreach( $booking_system_data[ 'picked_events' ] as $picked_event ) {
				?>
					<input type='hidden' name='selected_events[<?php echo $i; ?>][group_id]' value='<?php echo esc_attr( $picked_event[ 'group_id' ] ); ?>' />
					<input type='hidden' name='selected_events[<?php echo $i; ?>][id]' value='<?php echo esc_attr( $picked_event[ 'id' ] ); ?>' />
					<input type='hidden' name='selected_events[<?php echo $i; ?>][start]' value='<?php echo esc_attr( $picked_event[ 'start' ] ); ?>' />
					<input type='hidden' name='selected_events[<?php echo $i; ?>][end]' value='<?php echo esc_attr( $picked_event[ 'end' ] ); ?>' />
				<?php
					++$i;
				}
				do_action( 'bookacti_booking_system_inputs', $atts, $booking_system_data );
			?>
		</div>
		
		<?php do_action( 'bookacti_booking_system_container_before', $atts, $booking_system_data ); ?>
		
		<div id='<?php echo esc_attr( $booking_system_data[ 'id' ] ); ?>' class='bookacti-booking-system <?php echo esc_attr( $booking_system_data[ 'class' ] ); ?>' >
			<?php echo bookacti_get_booking_method_html( $booking_system_data[ 'method' ], $booking_system_data ); 
			if( $booking_system_data[ 'auto_load' ] ) { 
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
		
		<div class='bookacti-tooltips-container'>
			<?php do_action( 'bookacti_tooltips_container', $atts, $booking_system_data ); ?>
		</div>
		
		<?php do_action( 'bookacti_booking_system_container_after', $atts, $booking_system_data ); ?>
	</div>
	<div id='<?php echo $atts[ 'id' ] . '-dialogs'; ?>' class='bookacti-booking-system-dialogs' >
		<?php
			bookacti_display_booking_system_dialogs( $booking_system_data[ 'id' ] );
		?>
	</div>
	<?php
	do_action( 'bookacti_after_booking_system_container', $atts, $booking_system_data );
	
	return ob_get_clean();
}


/**
 * Get booking system data
 * @since 1.7.4
 * @version 1.8.10
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @return array
 */
function bookacti_get_booking_system_data( $atts ) {
	$timezone	= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$now_dt		= new DateTime( 'now', new DateTimeZone( $timezone ) );
	$now		= $now_dt->format( 'Y-m-d H:i:s' );
	
	$booking_system_data = array_merge( array(
		'events' => array(),
		'events_data' => array(),
		'events_interval' => array( 'start' => $now, 'end' => $now ),
		'bookings' => array(),
		'booking_lists' => array(),
		'activities_data' => array(),
		'groups_events' => array(),
		'groups_data' => array(),
		'group_categories_data' => array(),
		'no_events' => 0
	), $atts );
	
	// Check if the availability period starts before it ends
	if( strtotime( $atts[ 'start' ] ) >= strtotime( $atts[ 'end' ] ) ) {
		$booking_system_data[ 'no_events' ] = 1;
	}
	
	// Events related data
	if( $atts[ 'auto_load' ] && ! $booking_system_data[ 'no_events' ] ) {
		$availability_period= array( 'start' => $atts[ 'start' ], 'end' => $atts[ 'end' ] );
		$user_ids			= array();
		$groups_ids			= array();
		$groups_data		= array();
		$categories_data	= array();
		$groups_events		= array();
		$events				= array( 'events' => array(), 'data' => array() );
		$booking_lists		= array();

		if( ! in_array( 'none', $atts[ 'group_categories' ], true ) ) {
			$groups_data		= bookacti_get_groups_of_events( array( 'templates' => $atts[ 'calendars' ], 'group_categories' => $atts[ 'group_categories' ], 'availability_period' => $atts[ 'past_events_bookable' ] ? array() : $availability_period, 'started' => true, 'inactive' => false ) );
			$categories_data	= bookacti_get_group_categories( $atts[ 'calendars' ], $atts[ 'group_categories' ] );
			foreach( $groups_data as $group_id => $group_data ) { $groups_ids[] = $group_id; }
			$groups_events = ! $groups_ids ? array() : bookacti_get_groups_events( $atts[ 'calendars' ], $atts[ 'group_categories' ], $groups_ids );
		}
		
		// Trim leading and trailing empty days
		if( $atts[ 'trim' ] ) {
			// Get bounding events
			$bounding_events = array();
			if( $atts[ 'groups_only' ] ) {
				if( $groups_ids ) {
					$bounding_events = bookacti_fetch_grouped_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'groups' => $groups_ids, 'group_categories' => $atts[ 'group_categories' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $availability_period, 'bounding_events_only' => true ) );
				}
			} else if( $atts[ 'bookings_only' ] ) {
				$bounding_events = bookacti_fetch_booked_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'status' => $atts[ 'status' ], 'users' => $atts[ 'user_id' ] ? array( $atts[ 'user_id' ] ) : array(), 'past_events' => $atts[ 'past_events' ], 'interval' => $availability_period, 'bounding_events_only' => true ) );
			} else {
				$bounding_events = bookacti_fetch_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $availability_period, 'bounding_events_only' => true ) );	
			}
			
			// Compute bounding dates
			if( ! empty( $bounding_events[ 'events' ] ) ) {
				$bounding_events_keys = array_keys( $bounding_events[ 'events' ] );
				$last_key = end( $bounding_events_keys );
				$first_key = reset( $bounding_events_keys );
				$bounding_dates = array( 
					'start' => $bounding_events[ 'events' ][ $first_key ][ 'start' ], 
					'start_last' => $bounding_events[ 'events' ][ $last_key ][ 'start' ],
					'end' => $bounding_events[ 'events' ][ $last_key ][ 'end' ],
				);

				// Replace availability period with events bounding dates
				if( strtotime( $bounding_dates[ 'start' ] ) > strtotime( $booking_system_data[ 'start' ] ) )	{ $booking_system_data[ 'start' ] = $bounding_dates[ 'start' ]; }
				if( strtotime( $bounding_dates[ 'end' ] ) < strtotime( $booking_system_data[ 'end' ] ) )		{ $booking_system_data[ 'end' ] = $bounding_dates[ 'end' ]; }
				if( strtotime( $booking_system_data[ 'start' ] ) > strtotime( $booking_system_data[ 'end' ] ) )	{ $booking_system_data[ 'start' ] = $booking_system_data[ 'end' ]; }
				
				// Trim the availability period
				$availability_period = array( 'start' => $booking_system_data[ 'start' ], 'end' => $booking_system_data[ 'end' ] );
				
				// Display the last event entirely
				if( strtotime( $bounding_dates[ 'start_last' ] ) < strtotime( $booking_system_data[ 'end' ] )
				 && strtotime( $bounding_dates[ 'end' ] ) > strtotime( $booking_system_data[ 'end' ] ) ) { $booking_system_data[ 'end' ] = $bounding_dates[ 'end' ]; }
				 
			// If there are no bounding events, it means that there are no events at all
			} else {
				$booking_system_data[ 'no_events' ] = 1;
			}
		}

		if( ! $booking_system_data[ 'no_events' ] ) {
			// Compute the interval of events to retrieve
			$events_interval = bookacti_get_new_interval_of_events( $availability_period, array(), false, $atts[ 'past_events' ] );
			
			// Get events
			if( $atts[ 'groups_only' ] ) {
				if( $groups_ids ) {
					$events	= bookacti_fetch_grouped_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'groups' => $groups_ids, 'group_categories' => $atts[ 'group_categories' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
				}
			} else if( $atts[ 'bookings_only' ] ) {
				$events = bookacti_fetch_booked_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'status' => $atts[ 'status' ], 'users' => $atts[ 'user_id' ] ? array( $atts[ 'user_id' ] ) : array(), 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
				$user_ids = $atts[ 'user_id' ];
			} else {
				$events	= bookacti_fetch_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );	
			}
			
			// Get the booking list for each events
			if( $atts[ 'tooltip_booking_list' ] && $events[ 'events' ] && $events[ 'data' ] ) {
				$booking_filters = array(
					'from'			=> $events_interval[ 'start' ],
					'to'			=> $events_interval[ 'end' ],
					'in__event_id'	=> array_keys( $events[ 'data' ] ),
				);
				$booking_lists = bookacti_get_events_booking_lists( $booking_filters, $atts[ 'tooltip_booking_list_columns' ], $atts );
			}

			$booking_system_data[ 'events' ]				= $events[ 'events' ] ? $events[ 'events' ] : array();
			$booking_system_data[ 'events_data' ]			= $events[ 'data' ] ? $events[ 'data' ] : array();
			$booking_system_data[ 'events_interval' ]		= $events_interval;
			$booking_system_data[ 'bookings' ]				= bookacti_get_number_of_bookings_by_events( $atts[ 'calendars' ], array(), $user_ids );
			$booking_system_data[ 'booking_lists' ]			= $booking_lists;
			$booking_system_data[ 'activities_data' ]		= bookacti_get_activities_by_template( $atts[ 'calendars' ], true );
			$booking_system_data[ 'groups_events' ]			= $groups_events;
			$booking_system_data[ 'groups_data' ]			= $groups_data;
			$booking_system_data[ 'group_categories_data' ]	= $categories_data;
		}
	}
	
	if( $booking_system_data[ 'no_events' ] ) {
		$booking_system_data[ 'start' ] = $now;
		$booking_system_data[ 'end' ] = $now;
	}
	
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
 * @version 1.7.18
 * @return string
 */
function bookacti_get_available_booking_methods(){
	$available_booking_methods = array(
		'calendar'	=> esc_html__( 'Calendar', 'booking-activities' )
	);
	return apply_filters( 'bookacti_available_booking_methods', $available_booking_methods );
}


/**
 * Get booking method HTML
 * @since 1.1.0
 * @version 1.8.0
 * @param string $method
 * @param array $booking_system_data
 * @return string $html_elements
 */
function bookacti_get_booking_method_html( $method, $booking_system_data = array() ) {
	// Return a uniform message when no events are to be displayed
	if( ! empty( $booking_system_data[ 'no_events' ] ) ) {
		return '<div class="bookacti-no-events">' . bookacti_get_message( 'no_events' ) . '</div>';
	}
	
	$available_booking_methods = bookacti_get_available_booking_methods();
	if( $method === 'calendar' || ! in_array( $method, array_keys( $available_booking_methods ), true ) ) {
		$html = bookacti_get_calendar_html( $booking_system_data );
	} else {
		$html = apply_filters( 'bookacti_get_booking_method_html', '', $method, $booking_system_data );
	}
	return $html;
}


/**
 * Retrieve Calendar booking system HTML to include in the booking system
 * @since 1.8.0 (was bookacti_retrieve_calendar_elements)
 * @param array $booking_system_data
 * @return string
 */
function bookacti_get_calendar_html( $booking_system_data = array() ) {
	ob_start();
	?>
		<div class='bookacti-calendar-title bookacti-booking-system-title'>
			<?php echo bookacti_get_message( 'calendar_title' ); ?>
		</div>
		<div class='bookacti-calendar'></div>
	<?php
	return apply_filters( 'bookacti_calendar_html', ob_get_clean(), $booking_system_data );
}


/**
 * Get default booking system attributes
 * @since 1.5.0
 * @version 1.8.10
 * @return array
 */
function bookacti_get_booking_system_default_attributes() {
	$timezone			= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$current_datetime	= new DateTime( 'now', new DateTimeZone( $timezone ) );
	
	$cached_atts = wp_cache_get( 'booking_system_default_attributes', 'bookacti' );
	if( $cached_atts ) { return $cached_atts; }
	
	$default_atts = apply_filters( 'bookacti_booking_system_default_attributes', array(
		'id'							=> '',
		'class'							=> '',
		'calendars'						=> array(),
		'activities'					=> array(),
		'group_categories'				=> array( 'none' ),
		'groups_only'					=> 0,
		'groups_single_events'			=> 0,
		'bookings_only'					=> 0,
		'tooltip_booking_list'			=> 0,
		'tooltip_booking_list_columns'	=> array(),
		'status'						=> array(),
		'user_id'						=> 0,
		'method'						=> 'calendar',
		'auto_load'						=> bookacti_get_setting_value( 'bookacti_general_settings', 'when_events_load' ) === 'on_page_load' ? 1 : 0,
		'start'							=> $current_datetime->format( 'Y-m-d H:i:s' ),
		'end'							=> '2037-12-31 23:59:59',
		'trim'							=> 1,
		'past_events'					=> 0,
		'past_events_bookable'			=> 0,
		'check_roles'					=> 1,
		'picked_events'					=> array(),
		'form_id'						=> 0,
		'form_action'					=> 'default',
		'when_perform_form_action'		=> 'on_submit',
		'redirect_url_by_activity'		=> array(),
		'redirect_url_by_group_category'=> array(),
		'display_data'					=> bookacti_get_booking_system_default_display_data()
	));
	
	wp_cache_set( 'booking_system_default_attributes', $default_atts, 'bookacti' );
	
	return $default_atts;
}


/**
 * Check booking system attributes and format them to be correct
 * @version 1.8.10
 * @param array $raw_atts 
 * @return array
 */
function bookacti_format_booking_system_attributes( $raw_atts = array() ) {
	// Set default value
	$defaults = bookacti_get_booking_system_default_attributes();
	
	// Make sure that all attributes are set (use default if not)
	$atts = array();
	foreach( $defaults as $name => $default ) {
		$atts[ $name ] = isset( $raw_atts[ $name ] ) ? $raw_atts[ $name ] : $default;
	}
	
	$formatted_atts = array();
	
	// Sanitize booleans
	$booleans_to_check = array( 'bookings_only', 'tooltip_booking_list', 'groups_only', 'groups_single_events', 'auto_load', 'trim', 'past_events', 'past_events_bookable', 'check_roles' );
	foreach( $booleans_to_check as $key ) {
		$formatted_atts[ $key ] = in_array( $atts[ $key ], array( 1, '1', true, 'true', 'yes', 'ok' ), true ) ? 1 : 0;
	}
	
	// Calendars
	$calendars = $atts[ 'calendars' ];
	if( is_numeric( $atts[ 'calendars' ] ) ) {
		$calendars = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'calendars' ] ) ) );
	}
	
	// Activities
	$activities = $atts[ 'activities' ];
	if( in_array( $atts[ 'activities' ], array( true, 'all', 'true', 'yes', 'ok' ), true ) ) {
		$activities = array();
		
	} else if( is_numeric( $atts[ 'activities' ] ) ) {
		$activities = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'activities' ] ) ) );
	}
	
	// Group categories
	$group_categories = $atts[ 'group_categories' ];
	if( in_array( $atts[ 'group_categories' ], array( true, 'all', 'true', 'yes', 'ok' ), true ) ) {
		$group_categories = array();
		
	} else if( is_numeric( $atts[ 'group_categories' ] ) ) {
		$group_categories = array_map( 'intval', explode( ',', preg_replace( array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$atts[ 'group_categories' ] ) ) );
	}
	if( ! is_array( $atts[ 'group_categories' ] ) || in_array( $atts[ 'group_categories' ], array( false, 'none', 'false', 'no' ), true ) ) { 
		$group_categories = array( 'none' );
	}
	
	// Remove duplicated values
	$calendars	= is_array( $calendars ) ? array_values( array_unique( $calendars ) ) : $defaults[ 'calendars' ];
	$activities	= is_array( $activities ) ? array_values( array_unique( $activities ) ) : $defaults[ 'activities' ];
	
	// Check if the desired templates are active and allowed
	$available_template_ids = array_keys( bookacti_fetch_templates( array(), true ) );
	// Remove unauthorized templates
	$had_templates = ! empty( $calendars );
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	$allowed_templates = ! $bypass_template_managers_check && ! is_super_admin() ? array_values( array_intersect( $calendars, $available_template_ids ) ) : $calendars;
	$formatted_atts[ 'calendars' ] = ! empty( $allowed_templates ) ? $allowed_templates : ( ! $had_templates && $available_template_ids ? $available_template_ids : array( 'none' ) );
	
	// Check if desired activities are active and allowed according to current user role
	$available_activity_ids = bookacti_get_activity_ids_by_template( $formatted_atts[ 'calendars' ], false, $formatted_atts[ 'check_roles' ] );
	// Remove unauthorized activities
	$had_activities = ! empty( $activities );
	$allowed_activities = array_values( array_intersect( $activities, array_map( 'intval', $available_activity_ids ) ) );
	$formatted_atts[ 'activities' ] = ! empty( $allowed_activities ) ? $allowed_activities : ( ! $had_activities && $available_activity_ids ? $available_activity_ids : array( 'none' ) );
	
	// Check if desired group categories exist and are allowed according to current user role
	$available_category_ids = bookacti_get_group_category_ids_by_template( $formatted_atts[ 'calendars' ], false, $formatted_atts[ 'check_roles' ] );
	if( ! in_array( 'none', $group_categories, true ) ) { 
		$group_categories = array_values( array_unique( $group_categories ) );
		// Remove unauthorized group categories
		$had_group_categories = ! empty( $group_categories );
		$allowed_group_categories = array_values( array_intersect( $group_categories, array_map( 'intval', $available_category_ids ) ) );
		$group_categories = ! empty( $allowed_group_categories ) ? $allowed_group_categories : ( ! $had_group_categories && $available_category_ids ? $available_category_ids : array( 'none' ) );
	}
	$formatted_atts[ 'group_categories' ] = $group_categories;
	
	// Format Start and End
	$sanitized_start_date	= bookacti_sanitize_date( $atts[ 'start' ] );
	$sanitized_end_date		= bookacti_sanitize_date( $atts[ 'end' ] );
	if( $sanitized_start_date ) { $atts[ 'start' ] = $sanitized_start_date . ' 00:00:00'; }
	if( $sanitized_end_date )	{ $atts[ 'end' ] = $sanitized_end_date . ' 23:59:59'; }
	
	$sanitized_start	= $atts[ 'past_events' ] && empty( $raw_atts[ 'start' ] ) ? '1970-02-01 00:00:00' : bookacti_sanitize_datetime( $atts[ 'start' ] );
	$sanitized_end		= bookacti_sanitize_datetime( $atts[ 'end' ] );
	$formatted_atts[ 'start' ]	= $sanitized_start ? $sanitized_start : $defaults[ 'start' ];
	$formatted_atts[ 'end' ]	= $sanitized_end ? $sanitized_end : $defaults[ 'end' ];
	
	// Format display data
	$formatted_atts[ 'display_data' ] = is_array( $atts[ 'display_data' ] ) ? bookacti_format_booking_system_display_data( $atts[ 'display_data' ] ) : $defaults[ 'display_data' ];
	
	// Check if desired booking method is registered
	$available_booking_methods = array_keys( bookacti_get_available_booking_methods() );
	$method = esc_attr( $atts[ 'method' ] );
	$formatted_atts[ 'method' ] = in_array( $method, $available_booking_methods, true ) ? $method : ( in_array( $defaults[ 'method' ], $available_booking_methods, true ) ? $defaults[ 'method' ] : 'calendar' );
	
	// Sanitize user id
	$user_id = is_numeric( $atts[ 'user_id' ] ) ? intval( $atts[ 'user_id' ] ) : esc_attr( $atts[ 'user_id' ] );
	if( $user_id === 'current' ) { $user_id = get_current_user_id(); }
	if( $user_id && ! is_email( $user_id ) && ( ! is_numeric( $user_id ) || ( is_numeric( $user_id ) && strlen( (string) $user_id ) ) >= 16 ) ) { $user_id = bookacti_decrypt( $user_id ); }
	$formatted_atts[ 'user_id' ] = $user_id;
	
	
	// Sanitize booking status
	$status = $atts[ 'status' ];
	if( is_string( $status ) ) {
		$status = array_map( 'sanitize_title_with_dashes', explode( ',', preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', 	$status ) ) );
	}
	
	// Check if desired status are registered
	$formatted_atts[ 'status' ] = is_array( $status ) && $status ? array_intersect( $status, array_keys( bookacti_get_booking_state_labels() ) ) : array();
	
	// Check if desired columns are registered
	$formatted_atts[ 'tooltip_booking_list_columns' ] = is_array( $atts[ 'tooltip_booking_list_columns' ] ) && $atts[ 'tooltip_booking_list_columns' ] ? array_intersect( $atts[ 'tooltip_booking_list_columns' ], array_keys( bookacti_get_user_booking_list_columns_labels() ) ) : array();
	
	// Give a random id if not supplied
	// Prefix the ID with bookacti-booking-system-
	$formatted_atts[ 'id' ] = empty( $atts[ 'id' ] ) || ! is_string( $atts[ 'id' ] ) ? rand() : ( substr( strval( $atts[ 'id' ] ), 0, 9 ) !== 'bookacti-' ? 'bookacti-booking-system-' . esc_attr( $atts[ 'id' ] ) : esc_attr( $atts[ 'id' ] ) );
	
	// Format classes
	$formatted_atts[ 'class' ] = is_string( $atts[ 'class' ] ) ? esc_attr( $atts[ 'class' ] ) : $defaults[ 'class' ];
	
	// Format picked events
	$formatted_atts[ 'picked_events' ] = bookacti_format_picked_events( $atts[ 'picked_events' ] );
	
	// Sanitize form id
	$formatted_atts[ 'form_id' ] = is_numeric( $atts[ 'form_id' ] ) ? intval( $atts[ 'form_id' ] ) : 0;	
	
	// Format actions
	$possible_form_actions	= array_keys( bookacti_get_available_form_actions() );
	$possible_form_triggers = array_keys( bookacti_get_available_form_action_triggers() );
	$formatted_atts[ 'form_action' ]				= in_array( $atts[ 'form_action' ], $possible_form_actions, true ) ? $atts[ 'form_action' ] : $defaults[ 'form_action' ];
	$formatted_atts[ 'when_perform_form_action' ]	= in_array( $atts[ 'when_perform_form_action' ], $possible_form_triggers, true ) ? $atts[ 'when_perform_form_action' ] : $defaults[ 'when_perform_form_action' ];
	
	$redirect_url_by_group_activity = array();
	if( is_array( $atts[ 'redirect_url_by_activity' ] ) ) {
		foreach( $atts[ 'redirect_url_by_activity' ] as $activity_id => $redirect_url ) {
			if( ! is_numeric( $activity_id ) || empty( $redirect_url ) ) { continue; }
			$redirect_url_by_group_activity[ intval( $activity_id ) ] = esc_url_raw( $redirect_url );
		}
	}
	$formatted_atts[ 'redirect_url_by_activity' ] = $redirect_url_by_group_activity ? $redirect_url_by_group_activity : $defaults[ 'redirect_url_by_activity' ];

	$redirect_url_by_group_category = array();
	if( is_array( $atts[ 'redirect_url_by_group_category' ] ) ) {
		foreach( $atts[ 'redirect_url_by_group_category' ] as $group_category_id => $redirect_url ) {
			if( ! is_numeric( $group_category_id ) || empty( $redirect_url ) ) { continue; }
			$redirect_url_by_group_category[ intval( $group_category_id ) ] = esc_url_raw( $redirect_url );
		}
	}
	$formatted_atts[ 'redirect_url_by_group_category' ] = $redirect_url_by_group_category ? $redirect_url_by_group_category : $defaults[ 'redirect_url_by_group_category' ];
	
	return apply_filters( 'bookacti_formatted_booking_system_attributes', $formatted_atts, $raw_atts );
}


/**
 * Format picked events array
 * @since 1.8.10
 * @param array $picked_events_raw
 * @param boolean $one_entry_per_group
 * @return array
 */
function bookacti_format_picked_events( $picked_events_raw = array(), $one_entry_per_group = false ) {
	$picked_events = array();
	
	if( is_array( $picked_events_raw ) ) {
		$i = 0;
		$picked_group_ids = array();
		foreach( $picked_events_raw as $picked_event_raw ) {
			$picked_event = bookacti_format_picked_event( $picked_event_raw );
			$group_id = $picked_event[ 'group_id' ];
			
			// For groups of events
			if( $group_id && $one_entry_per_group ) {
				// If the group of events is already in the array, add the picked event to the corresponding group and skip
				$array_i = array_search( $group_id, $picked_group_ids );
				if( $array_i !== false ) { 
					if( $picked_event ) { $picked_events[ $array_i ][ 'events' ][] = $picked_event; }
					continue;
				}
				$picked_group_ids[ $i ] = $group_id;
				
				// Add the group to the array and start listing its picked events
				$picked_events[ $i ] = array(
					'group_id' => $group_id,
					'events' => $picked_event ? array( $picked_event ) : array()
				);
			} 
			
			// For single event, add the event to the array
			else if( $picked_event ) {
				$picked_events[ $i ] = $picked_event;
			} 
			
			// If the picked event could not be identified as an event or a group, skip it
			else { continue; }
			
			// Increment 1 per group or event
			++$i;
		}
	}
	
	return apply_filters( 'bookacti_picked_events_formatted', $picked_events, $picked_events_raw );
}


/**
 * Format picked event array
 * @since 1.8.10
 * @param array $picked_event_raw
 * @return array
 */
function bookacti_format_picked_event( $picked_event_raw = array() ) {
	$picked_event = array();
	
	// Make sure all values are filled
	if( is_array( $picked_event_raw )
	&& ( isset( $picked_event_raw[ 'group_id' ] )
		|| (   isset( $picked_event_raw[ 'id' ] )
			&& isset( $picked_event_raw[ 'start' ] )
			&& isset( $picked_event_raw[ 'end' ] ) ) ) ) {
		// Sanitize the values
		$picked_event = array(
			'group_id'	=> isset( $picked_event_raw[ 'group_id' ] ) ? intval( $picked_event_raw[ 'group_id' ] ) : 0,
			'id'		=> isset( $picked_event_raw[ 'id' ] ) ? intval( $picked_event_raw[ 'id' ] ) : 0,
			'start'		=> isset( $picked_event_raw[ 'start' ] ) ? bookacti_sanitize_datetime( $picked_event_raw[ 'start' ] ) : '',
			'end'		=> isset( $picked_event_raw[ 'end' ] ) ? bookacti_sanitize_datetime( $picked_event_raw[ 'end' ] ) : '',
		);
		// If the event is not a group and one of its value is empty, return an empty array
		foreach( $picked_event as $key => $value ) {
			if( ! $value && $key !== 'group_id' && ! $picked_event[ 'group_id' ] ) { $picked_event = array(); break; }
		}
	}
	
	return apply_filters( 'bookacti_picked_event_formatted', $picked_event, $picked_event_raw );
}


/**
 * Get the difference between two picked events arrays
 * @since 1.8.10
 * @param array $picked_events1
 * @param array $picked_events2
 * @param array $one_entry_per_group
 * @return array
 */
function bookacti_diff_picked_events( $picked_events1, $picked_events2, $one_entry_per_group = false ) {
	$diff = array();
	
	foreach( $picked_events1 as $picked_event1 ) {
		$is_in_picked_events2 = false;
		foreach( $picked_events2 as $j => $picked_event2 ) {
			if( bookacti_is_same_picked_event( $picked_event1, $picked_event2 ) ) {
				$is_in_picked_events2 = true;
				unset( $picked_events2[ $j ] );
				break;
			}
		}
		if( ! $is_in_picked_events2 ) { $diff[] = $picked_event1; }
	}
	
	return array_merge( $diff, $picked_events2 );
}


/**
 * Check if two picked events are the same
 * @since 1.8.10
 * @param array $picked_event1
 * @param array $picked_event2
 * @return boolean
 */
function bookacti_is_same_picked_event( $picked_event1, $picked_event2 ) {
	if( ! empty( $picked_event1[ 'events' ] ) && ! empty( $picked_event2[ 'events' ] ) ) {
		$nb_events_total = count( $picked_event1[ 'events' ] );
		if( count( $picked_event2[ 'events' ] ) !== $nb_events_total ) { return false; }
		$nb_same_events = 0;
		foreach( $picked_event1[ 'events' ] as $i => $event1 ) {
			foreach( $picked_event2[ 'events' ] as $event2 ) {
				if( $event1[ 'group_id' ] === $event2[ 'group_id' ]
				&&  $event1[ 'id' ] === $event2[ 'id' ]
				&&  $event1[ 'start' ] === $event2[ 'start' ]
				&&  $event1[ 'end' ] === $event2[ 'end' ] ) {
					++$nb_same_events;
					break;
				}
			}
		}
		if( $nb_same_events !== $nb_events_total ) { return false; }
		
	} else if( empty( $picked_event1[ 'events' ] ) && empty( $picked_event2[ 'events' ] ) ) {
		if( $picked_event1[ 'group_id' ] !== $picked_event2[ 'group_id' ]
		||  $picked_event1[ 'id' ] !== $picked_event2[ 'id' ]
		||  $picked_event1[ 'start' ] !== $picked_event2[ 'start' ]
		||  $picked_event1[ 'end' ] !== $picked_event2[ 'end' ] ) {
			return false;
		}
	} else {
		return false;
	}
	
	return true;
}


/**
 * Get booking system attributes from calendar field data
 * @since 1.7.17
 * @version 1.8.10
 * @param array|int $calendar_field
 * @return array
 */
function bookacti_get_calendar_field_booking_system_attributes( $calendar_field ) {
	if( is_numeric( $calendar_field ) ) { $calendar_field = bookacti_get_form_field_data( $calendar_field ); }
	if( ! is_array( $calendar_field ) ) { $calendar_field = array(); }
	
	// Check if an event / group of events is picked by default
	$picked_events = ! empty( $_REQUEST[ 'selected_events' ] ) ? $_REQUEST[ 'selected_events' ] : array();
	
	// Compute availability period 
	$availability_period = bookacti_get_calendar_field_availability_period( $calendar_field );
	
	// Isolate display data
	$display_data = array_intersect_key( $calendar_field, bookacti_get_booking_system_default_display_data() );
	
	// Transform the Calendar field settings to Booking system attributes
	$booking_system_atts_raw= array_merge( $calendar_field, $availability_period, array( 'picked_events' => $picked_events, 'display_data' => $display_data ) );
	$booking_system_atts	= bookacti_format_booking_system_attributes( $booking_system_atts_raw );
	
	return apply_filters( 'bookacti_calendar_field_booking_system_attributes', $booking_system_atts, $calendar_field );
}


/**
 * Get booking system default display data
 * @since 1.7.17
 * @return array
 */
function bookacti_get_booking_system_default_display_data() {
	return apply_filters( 'bookacti_booking_system_default_display_data', array(
		'minTime'	=> '00:00',
		'maxTime'	=> '00:00'
	));
}


/**
 * Format booking system display data
 * @since 1.7.17
 * @version 1.8.6
 * @param array $raw_display_data
 * @return array
 */
function bookacti_format_booking_system_display_data( $raw_display_data ) {
	// Get the default values
	$default_data = bookacti_get_booking_system_default_display_data();
	$display_data = array();
	
	// Make sure that all data are set
	foreach( $default_data as $key => $default_value ){
		if( isset( $raw_display_data[ $key ] ) && is_string( $raw_display_data[ $key ] ) ) { $display_data[ $key ] = stripslashes( $raw_display_data[ $key ] ); }
		else if( ! isset( $raw_display_data[ $key ] ) ) { $display_data[ $key ] = $default_value; }
	}
	
	// Format 24-h times: minTime and maxTime
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $display_data[ 'minTime' ] ) ){ $display_data[ 'minTime' ] = $default_data[ 'minTime' ]; }
	if( ! preg_match( '/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $display_data[ 'maxTime' ] ) ){ $display_data[ 'maxTime' ] = $default_data[ 'maxTime' ]; }
	
	// Make sure minTime is before maxTime
	// If minTime = maxTime, set the default maxTime
	if( $display_data[ 'minTime' ] === $display_data[ 'maxTime' ] ) { $display_data[ 'maxTime' ] = $default_data[ 'maxTime' ]; }
	// If maxTime is 00:xx change it to 24:xx
	if( $display_data[ 'maxTime' ] === '00:00' ) { $display_data[ 'maxTime' ] = '24:00'; }
	// If minTime >= maxTime, permute values
	if( intval( substr( $display_data[ 'minTime' ], 0, 2 ) ) >= substr( $display_data[ 'maxTime' ], 0, 2 ) ) { 
		$temp_max = $display_data[ 'maxTime' ];
		$display_data[ 'maxTime' ] = $display_data[ 'minTime' ]; 
		$display_data[ 'minTime' ] = $temp_max;
	}
	
	return apply_filters( 'bookacti_formatted_booking_system_display_data', $display_data, $raw_display_data );
}


/**
 * Sanitize booking system display data
 * @since 1.7.17
 * @param array $raw_display_data
 * @return array
 */
function bookacti_sanitize_booking_system_display_data( $raw_display_data ) {
	// Sanitizing these values happens to be the same process as formatting for now, but it may not always be true
	$display_data = bookacti_format_booking_system_display_data( $raw_display_data );
	
	return apply_filters( 'bookacti_sanitized_booking_system_display_data', $display_data, $raw_display_data );
}


/**
 * Format booking system attributes passed via the URL
 * @since 1.6.0
 * @version 1.8.0
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
		
		// If the 'past_events' value changed, reset the template dates
		if( $atts[ 'past_events' ] && ! $was_past_events ) {
			$url_raw_atts[ 'start' ] = '';
			$url_raw_atts[ 'end' ] = '';
		}
	}
	
	// Format the URL attributes
	$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	if( ! empty( $url_raw_atts[ 'start' ] ) && (bool)strtotime( $url_raw_atts[ 'start' ] ) ) {
		$from_datetime = new DateTime( $url_raw_atts[ 'start' ], $timezone );
		$url_raw_atts[ 'start' ] = $from_datetime->format( 'Y-m-d H:i:s' );
	}
	if( ! empty( $url_raw_atts[ 'end' ] ) && (bool)strtotime( $url_raw_atts[ 'end' ] ) ) {
		$to_datetime = new DateTime( $url_raw_atts[ 'end' ], $timezone );
		$url_raw_atts[ 'end' ] = ! bookacti_sanitize_datetime( $url_raw_atts[ 'end' ] ) && $to_datetime->format( 'H:i:s' ) === '00:00:00' ? $to_datetime->format( 'Y-m-d' ) . ' 23:59:59' : $to_datetime->format( 'Y-m-d H:i:s' );
	}
	
	// Isolate display data
	$display_data = array_intersect_key( $url_raw_atts, bookacti_get_booking_system_default_display_data() );
	
	$url_atts = bookacti_format_booking_system_attributes( array_merge( $url_raw_atts, array( 'display_data' => $display_data ) ) );
	
	// Replace booking system attributes with attributes passed through the URL
	foreach( $default_atts as $att_name => $att_value ) {
		if( ! isset( $url_raw_atts[ $att_name ] ) || ( ! $url_raw_atts[ $att_name ] && ! in_array( $url_raw_atts[ $att_name ], array( 0, '0' ), true ) ) ) { continue; }
		if( $att_name === 'past_events' && $url_raw_atts[ 'past_events' ] === 'auto' ) { continue; }
		$atts[ $att_name ] = $url_atts[ $att_name ];
	}
	
	return apply_filters( 'bookacti_format_booking_system_url_attributes', $atts );
}


/**
 * Get booking system fields default data
 * @since 1.5.0
 * @version 1.8.10
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
		$category_options		= array( 'all' => esc_html__( 'All', 'booking-activities' ), 'none' => esc_html_x( 'None', 'About group category', 'booking-activities' ) );
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
			'tip'			=> esc_html__( 'When a customer picks an event belonging to a group, let the customer choose between the group or the event alone.', 'booking-activities' )
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
		$status_options = array( 'none' => esc_html__( 'All', 'booking-activities' ) );
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
				'option_label'			=> array( 'user_login', ' (', 'user_email', ')' ),
				'selected'				=> 0,
				'allow_current'			=> 1,
				'echo'					=> 1
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
			'tip'			=> esc_html__( 'Set the booking system CSS id. Leave this empty if you display more than one occurrence of this form on the same page.', 'booking-activities' )
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
	
	// Availability Period End
	if( ! $fields || in_array( 'availability_period_end', $fields, true ) ) {
		$defaults[ 'availability_period_end' ] = array(
			'type'			=> 'duration',
			'name'			=> 'availability_period_end',
			'options'		=> array( 'min' => 0, 'step' => 1 ),
			/* translators: Followed by a field indicating a number of days from today. E.g.: "At the earliest 14 days before the event". */
			'title'			=> esc_html__( 'At the earliest', 'booking-activities' ),
			/* translators: Comes after a field indicating a number of days from today. E.g.: "At the earliest 14 days before the event". */
			'label'			=> esc_html__( 'before the event', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when an event can be booked at the earliest. E.g.: "14 days", you can book an event starting in 13 days and few hours, but you cannot book an event starting in 3 weeks.', 'booking-activities' )
		);
	}
	
	// Availability Period Start
	if( ! $fields || in_array( 'availability_period_start', $fields, true ) ) {
		$defaults[ 'availability_period_start' ] = array(
			'type'			=> 'duration',
			'name'			=> 'availability_period_start',
			'options'		=> array( 'min' => 0, 'step' => 1 ),
			/* translators: Followed by a field indicating a number of days from today. E.g.: "At the latest 1 hour before the event". */
			'title'			=> esc_html__( 'At the latest', 'booking-activities' ),
			'label'			=> esc_html__( 'before the event', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when an event can be booked at the latest. E.g.: "1 hour 30 minutes", you can book an event starting in 2 hours, but you cannot book an event starting in 45 minutes.', 'booking-activities' )
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
	
	// Trim empty days
	if( ! $fields || in_array( 'trim', $fields, true ) ) {
		$defaults[ 'trim' ] = array(
			'type'			=> 'checkbox',
			'name'			=> 'trim',
			'title'			=> esc_html__( 'Trim empty days', 'booking-activities' ),
			'tip'			=> esc_html__( 'Make the calendar start at the first displayed event and end at the last one.', 'booking-activities' )
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
 * @version 1.8.10
 * @param array $picked_events formatted with bookacti_format_picked_events
 * @param int $quantity Desired number of bookings
 * @param int $form_id Set your form id to validate the event against its form parameters. Default is 0: ignore form validation.
 * @return array
 */
function bookacti_validate_booking_form( $picked_events, $quantity, $form_id = 0 ) {
	$date_format = bookacti_get_message( 'date_format_short' );
	$validated = array( 
		'status' => 'failed', 
		'error' => 'invalid_event',
		'messages' => array(), 
		'events_summary' => array()
	);
	
	// Get calendar data
	$allow_multiple_bookings = apply_filters( 'bookacti_allow_multiple_bookings', false, $form_id, $picked_events, $quantity );
	
	// Keep one entry per group
	$picked_events = bookacti_format_picked_events( $picked_events, true );
	
	// If no events are picked
	if( ! $picked_events ) {
		$validated[ 'error' ] = 'no_event_selected';
		$validated[ 'messages' ][ 'no_event_selected' ] = array( esc_html__( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' ) );
	} 
	
	// If no events are picked
	else if( count( $picked_events ) > 1 && ! $allow_multiple_bookings ) {
		$validated[ 'error' ] = 'multiple_events_selected';
		$validated[ 'messages' ][ 'multiple_events_selected' ] = array( esc_html__( 'You cannot book multiple events or group of events at the same time. Please book them one at a time.', 'booking-activities' ) );
	} 
	
	// If the quantity is not > 0
	else if( $quantity <= 0 ) {
		$validated[ 'error' ] = 'qty_inf_to_0';
		$validated[ 'messages' ][ 'qty_inf_to_0' ] = array( esc_html__( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', 'booking-activities' ) );
	}
	
	// Check each picked event
	else {
		foreach( $picked_events as $picked_event ) {
			$grouped_events_keys = isset( $picked_event[ 'events' ] ) ? array_keys( $picked_event[ 'events' ] ) : array();
			$last_key = end( $grouped_events_keys );
			
			$group_id = $picked_event[ 'group_id' ];
			$event_id = isset( $picked_event[ 'id' ] ) ? $picked_event[ 'id' ] : ( isset( $picked_event[ 'events' ][ 0 ][ 'id' ] ) ? $picked_event[ 'events' ][ 0 ][ 'id' ] : 0 );
			$event_start = isset( $picked_event[ 'start' ] ) ? $picked_event[ 'start' ] : ( isset( $picked_event[ 'events' ][ 0 ][ 'start' ] ) ? $picked_event[ 'events' ][ 0 ][ 'start' ] : 0 );
			$event_end = isset( $picked_event[ 'end' ] ) ? $picked_event[ 'end' ] : ( isset( $picked_event[ 'events' ][ $last_key ][ 'end' ] ) ? $picked_event[ 'events' ][ $last_key ][ 'end' ] : 0 );
			
			$title = '';
			$dates = bookacti_get_formatted_event_dates( $event_start, $event_end, false );

			// Check if the event / group exists before everything
			$exists = false;
			if( $group_id <= 0 ) {
				$event = bookacti_get_event_by_id( $event_id );
				if( $event ) {
					$exists = bookacti_is_existing_event( $event, $event_start, $event_end );
					$title = apply_filters( 'bookacti_translate_text', $event->title );
				}
			} else {
				$group = bookacti_get_group_of_events( $group_id );
				if( $group ) {
					$exists = bookacti_is_existing_group_of_events( $group );
					$title = apply_filters( 'bookacti_translate_text', $group->title );
				}
			}
			if( ! $exists ) {
				if( $group_id <= 0 ) {
					/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" doesn't exist. */
					$validated[ 'messages' ][ 'do_not_exist' ] = sprintf( esc_html__( 'The event "%s" doesn\'t exist, please pick an event and try again.', 'booking-activities' ), $title ? $title . ' (' . $dates . ')' : $dates );
				} else {
					/* translators: %s = The group of events title and dates. E.g.: The group of events "Basketball (Sep, 22nd - 3:00 PM to Sep, 29nd - 6:00 PM)" doesn't exist. */
					$validated[ 'messages' ][ 'do_not_exist' ] = sprintf( esc_html__( 'The group of events "%s" doesn\'t exist, please pick an event and try again.', 'booking-activities' ), $title ? $title . ' (' . $dates . ')' : $dates );
				}
				continue;
			}


			// Form checks
			if( $form_id ) { 
				// Check if the event can be booked on the given form
				if( $group_id <= 0 ) {
					$form_validated = bookacti_is_event_available_on_form( $form_id, $event_id, $event_start, $event_end );
				} else if( is_numeric( $group_id ) ) {
					$form_validated = bookacti_is_group_of_events_available_on_form( $form_id, $group_id );
				}

				// If the event doesn't match the form parameters, stop the validation here and return the error
				if( $form_validated[ 'status' ] !== 'success' ) {
					$validated[ 'messages' ][ $form_validated[ 'error' ] ] = $form_validated[ 'message' ];
					continue;
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
			if( $group_id <= 0 ) {
				$activity_data	= bookacti_get_metadata( 'activity', $event->activity_id );

				$min_quantity	= isset( $activity_data[ 'min_bookings_per_user' ] ) ? intval( $activity_data[ 'min_bookings_per_user' ] ) : 0;
				$max_quantity	= isset( $activity_data[ 'max_bookings_per_user' ] ) ? intval( $activity_data[ 'max_bookings_per_user' ] ) : 0;
				$max_users		= isset( $activity_data[ 'max_users_per_event' ] ) ? intval( $activity_data[ 'max_users_per_event' ] ) : 0;

				// Check if the user has already booked this event
				$bookings_nb_per_user = bookacti_get_number_of_bookings_per_user_by_events( array( $picked_event ) );
				$number_of_users = count( $bookings_nb_per_user );
				if( ! empty( $bookings_nb_per_user[ $user_id ] ) ) { 
					$quantity_already_booked = intval( $bookings_nb_per_user[ $user_id ] );
				}
				
				// Get the remaining availability
				$availability = bookacti_get_min_availability_by_events( array( $picked_event ) );
				foreach( $bookings_nb_per_user as $user_id => $qty_booked ) { $availability -= $qty_booked; }
				
				// Check allowed roles
				if( isset( $activity_data[ 'allowed_roles' ] ) && $activity_data[ 'allowed_roles' ] ) {
					$allowed_roles = $activity_data[ 'allowed_roles' ];
				}

			// Validate group booking
			} else {
				$category_data	= bookacti_get_metadata( 'group_category', $group->category_id );

				$min_quantity	= isset( $category_data[ 'min_bookings_per_user' ] ) ? intval( $category_data[ 'min_bookings_per_user' ] ) : 0;
				$max_quantity	= isset( $category_data[ 'max_bookings_per_user' ] ) ? intval( $category_data[ 'max_bookings_per_user' ] ) : 0;
				$max_users		= isset( $category_data[ 'max_users_per_event' ] ) ? intval( $category_data[ 'max_users_per_event' ] ) : 0;
				
				// Check if the user has already booked this group of events
				$bookings_nb_per_user = bookacti_get_number_of_bookings_per_user_by_events( $picked_event[ 'events' ] );
				$number_of_users = count( $bookings_nb_per_user );
				if( ! empty( $bookings_nb_per_user[ $user_id ] ) ) { 
					$quantity_already_booked = intval( $bookings_nb_per_user[ $user_id ] );
				}
				
				// Get the remaining availability
				$availability = bookacti_get_min_availability_by_events( $picked_event[ 'events' ] );
				foreach( $bookings_nb_per_user as $user_id => $qty_booked ) { $availability -= $qty_booked; }
				
				// Check allowed roles
				if( isset( $category_data[ 'allowed_roles' ] ) && $category_data[ 'allowed_roles' ] ) {
					$allowed_roles = $category_data[ 'allowed_roles' ];
				}
			}

			// Init boolean test variables
			$is_qty_inf_to_avail	= false;
			$is_qty_sup_to_min		= false;
			$is_qty_inf_to_max		= false;
			$is_users_inf_to_max	= false;
			$has_allowed_roles		= false;

			// Sanitize
			$quantity		= intval( $quantity );
			$availability	= intval( $availability );

			// Make the tests and change the booleans
			if( $quantity <= $availability )														{ $is_qty_inf_to_avail = true; }
			if( $min_quantity === 0 || ( $quantity + $quantity_already_booked ) >= $min_quantity )	{ $is_qty_sup_to_min = true; }
			if( $max_quantity === 0 || $quantity <= ( $max_quantity - $quantity_already_booked ) )	{ $is_qty_inf_to_max = true; }
			if( $max_users === 0 || $quantity_already_booked || $number_of_users < $max_users )		{ $is_users_inf_to_max = true; }
			
			// Check roles
			if( ! $allowed_roles 
				|| in_array( 'all', $allowed_roles, true ) 
				|| apply_filters( 'bookacti_bypass_roles_check', false ) )							{ $has_allowed_roles = true; }
			else { 
				$is_allowed = false;
				$current_user = wp_get_current_user();
				if( $current_user && ! empty( $current_user->roles ) ) {
					$is_allowed = array_intersect( $current_user->roles, $allowed_roles );
				}
				if( $is_allowed ) { $has_allowed_roles = true; }
			}
			
			// Set the error code and message
			$error = '';
			$message = '';
			
			if( ! $is_qty_sup_to_min ) {
				$error = 'qty_inf_to_min';
				/* translators: %1$s is a variable number of bookings, %2$s is the event title. */
				$message = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title . ' (' . $dates . ')' );
				if( $quantity_already_booked ) {
					/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
					$message .= ' ' . sprintf( esc_html( _n( 'and you have already booked %1$s place, but the minimum number of reservations required per user is %2$s.', 'and you have already booked %1$s places, but the minimum number of reservations required per user is %2$s.', $quantity_already_booked, 'booking-activities' ) ), $quantity_already_booked, $min_quantity );
				} else {
					/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or increase the quantity.' */
					$message .= ' ' . sprintf( esc_html__( 'but the minimum number of reservations required per user is %1$s.', 'booking-activities' ), $min_quantity );
				}	
				/* translators: %1$s is a variable quantity. */
				$message .= $min_quantity - $quantity_already_booked > 0 ? ' ' . sprintf( esc_html__( 'Please choose another event or increase the quantity to %1$s.', 'booking-activities' ), $min_quantity - $quantity_already_booked ) : ' ' . esc_html__( 'Please choose another event', 'booking-activities' );
			} else if( ! $is_qty_inf_to_max ) {
				$error = 'qty_sup_to_max';
				$message = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title . ' (' . $dates . ')' );
				if( $quantity_already_booked ) {
					/* translators: %1$s and %2$s are variable numbers of bookings, always >= 1. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
					$message .= ' ' . sprintf( esc_html( _n( 'but you have already booked %1$s place and the maximum number of reservations allowed per user is %2$s.', 'but you have already booked %1$s places and the maximum number of reservations allowed per user is %2$s.', $quantity_already_booked, 'booking-activities' ) ), $quantity_already_booked, $max_quantity );
				} else {
					/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking of "%2$s"' and followed by 'Please choose another event or decrease the quantity.' */
					$message .= ' ' . sprintf( esc_html__( 'but the maximum number of reservations allowed per user is %1$s.', 'booking-activities' ), $max_quantity );
				}
				/* translators: %1$s is a variable quantity. */
				$message .= $max_quantity - $quantity_already_booked > 0  ? ' ' . sprintf( esc_html__( 'Please choose another event or decrease the quantity to %1$s.', 'booking-activities' ), $max_quantity - $quantity_already_booked ) : ' ' . esc_html__( 'Please choose another event', 'booking-activities' );
			} else if( ! $is_users_inf_to_max ) {
				$error = 'users_sup_to_max';
				/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" has reached the maximum number of users allowed. */
				$message = sprintf( esc_html__( 'The event "%s" has reached the maximum number of users allowed. Bookings from other users are no longer accepted. Please choose another event.', 'booking-activities' ), $title . ' (' . $dates . ')' );
			} else if( $availability === 0 ) {
				$error = 'no_availability';
				/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" is no longer available. */
				$message = sprintf( esc_html__( 'The event "%s" is no longer available. Please choose another event.', 'booking-activities' ), $title . ' (' . $dates . ')' );
			} else if( ! $is_qty_inf_to_avail ) {
				$error = 'qty_sup_to_avail';
				$message = sprintf( esc_html( _n( 'You want to make %1$s booking of "%2$s"', 'You want to make %1$s bookings of "%2$s"', $quantity, 'booking-activities' ) ), $quantity, $title . ' (' . $dates . ')' )
						. ' ' . sprintf( esc_html( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $availability, 'booking-activities' ) ), $availability )
						. ' ' . esc_html__( 'Please choose another event or decrease the quantity.', 'booking-activities' );
			} else if( ! $has_allowed_roles ) {
				$error = 'role_not_allowed';
				if( is_user_logged_in() ) {
					/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" is not available in your user category. */
					$message = sprintf( esc_html__( 'The event "%s" is not available in your user category. Please choose another event.', 'booking-activities' ), $title . ' (' . $dates . ')' );
				} else {
					/* translators: %s = The event title and dates. E.g.: The event "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" is restricted to certain categories of users. */
					$message = sprintf( esc_html__( 'The event "%s" is restricted to certain categories of users. Please log in first.', 'booking-activities' ), $title . ' (' . $dates . ')' );
				}
			}
			
			if( $error ) {
				if( ! isset( $validated[ 'messages' ][ $error ] ) ) { $validated[ 'messages' ][ $error ] = array(); }
				$validated[ 'messages' ][ $error ][] = $message;
			}
		}
	}
	
	// If no errors were found, return success
	if( ! $validated[ 'messages' ] ) { 
		$validated[ 'status' ] = 'success';
		$validated[ 'error' ] = '';
	}
	
	return apply_filters( 'bookacti_validate_booking_form', $validated, $picked_events, $quantity, $form_id );
}


/**
 * Check if an event or an occurrence exists
 * @version 1.8.4
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
			$is_existing_event = bookacti_is_existing_occurrence( $event, $event_start, $event_end );
		} else {
			$is_existing_event = bookacti_is_existing_single_event( $event->event_id, $event_start, $event_end );
		}
	}

	return $is_existing_event;
}


/**
 * Check if the occurrence exists
 * @since 1.8.4 (was bookacti_is_existing_occurence)
 * @param object|int $event
 * @param string $event_start
 * @param string $event_end
 * @return boolean
 */
function bookacti_is_existing_occurrence( $event, $event_start, $event_end = NULL ) {
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
	$occurrence		= DateTime::createFromFormat( 'Y-m-d', substr( $event_start, 0, 10 ) );
	$repeat_from_timestamp	= intval( $repeat_from->format( 'U' ) );
	$repeat_to_timestamp	= intval( $repeat_to->format( 'U' ) );
	$occurrence_timestamp	= intval( $occurrence->format( 'U' ) );
	
	// Check if occurrence is between repeat_from and repeat_to
	if( $occurrence_timestamp < $repeat_from_timestamp || $occurrence_timestamp > $repeat_to_timestamp ) { return false; }
	
	// Check if the weekdays match
	if( $event->repeat_freq === 'weekly' ) {
		if( $occurrence->format( 'w' ) !== $event_datetime->format( 'w' ) ) { return false; }
	}
	
	// Check if the monthdays match
	if( $event->repeat_freq === 'monthly' ) {
		$is_last_day_of_month = $event_datetime->format( 't' ) === $event_datetime->format( 'd' );
		if( ! $is_last_day_of_month && $occurrence->format( 'd' ) !== $event_datetime->format( 'd' ) ) { return false; }
		else if ( $is_last_day_of_month && $occurrence->format( 't' ) !== $occurrence->format( 'd' ) ) { return false; }
	}
	
	// Check if the occurrence is on an exception date
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
 * @version 1.8.10
 * @param int $form_id
 * @param int|object $event_id
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
	$event = is_object( $event_id ) ? $event_id : bookacti_get_event_by_id( $event_id );

	// If the form calendar doesn't have the event template or the event activity
	if( ( $calendar_data[ 'calendars' ] && ! in_array( $event->template_id, $calendar_data[ 'calendars' ] ) )
	||  ( $calendar_data[ 'activities' ] && ! in_array( $event->activity_id, $calendar_data[ 'activities' ] ) ) ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'The selected event is not supposed to be available on this form.', 'booking-activities' );
	}

	// If the form calendar have groups, with no possibility to book a single event
	if( $belongs_to_form && ! in_array( 'none', $calendar_data[ 'group_categories' ], true ) && ! $calendar_data[ 'groups_single_events' ] ) {
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
			if( empty( $calendar_data[ 'group_categories' ] ) ) {
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
		$date_format				= bookacti_get_message( 'date_format_long' );
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
		$availability_period = bookacti_get_calendar_field_availability_period( $calendar_data );
		$calendar_start	= new DateTime( $availability_period[ 'start' ], $timezone );
		$calendar_end	= new DateTime( $availability_period[ 'end' ], $timezone );
			
		if( $event_start_obj < $calendar_start ) {
			$validated[ 'error' ] = 'event_starts_before_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_start->format( 'Y-m-d H:i:s' ), $date_format );
			/* translators: %s is a formatted date and hour (e.g.: "January 20, 2018 10:53 am") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book an event starting before %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
		if( $event_end_obj > $calendar_end ) {
			$validated[ 'error' ] = 'event_ends_after_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_end->format( 'Y-m-d H:i:s' ), $date_format );
			/* translators: %s is a formatted date "F d, Y" (e.g.: "January 20, 2018 10:53 am") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book an event taking place after %s.', 'booking-activities' ), $datetime_formatted );
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
 * @version 1.8.10
 * @param int $form_id
 * @param int|object $group_id
 * @return array
 */
function bookacti_is_group_of_events_available_on_form( $form_id, $group_id ) {
	$validated		= array( 'status' => 'failed' );
	$calendar_data	= bookacti_get_form_field_data_by_name( $form_id, 'calendar' );
	
	// Check if the form exists and if it has a calendar field (compulsory)
	$form_exists = ! empty( $calendar_data );
	if( ! $form_exists ) {
		$validated[ 'error' ] = 'invalid_form';
		$validated[ 'message' ] = esc_html__( 'Failed to retrieve the requested form data.', 'booking-activities' );
		return $validated;
	}
	
	
	// Check if the group of events is displayed on the form
	$belongs_to_form	= true;
	$group				= is_object( $group_id ) ? $group_id : bookacti_get_group_of_events( $group_id );
	$category			= bookacti_get_group_category( $group->category_id, ARRAY_A );
	
	// If the form calendar doesn't have the group of events' template
	if( $calendar_data[ 'calendars' ] && ! in_array( $category[ 'template_id' ], $calendar_data[ 'calendars' ] ) ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'The selected events are not supposed to be available on this form.', 'booking-activities' );
	}
	
	// If the form calendar doesn't have groups
	if( $belongs_to_form && in_array( 'none', $calendar_data[ 'group_categories' ], true ) ) {
		$belongs_to_form = false;
		$validated[ 'message' ] = esc_html__( 'You cannot book groups of events with this form, you must select a single event.', 'booking-activities' );
	}

	// If the form calendar have groups
	if( $belongs_to_form && ! in_array( 'none', $calendar_data[ 'group_categories' ], true ) ) {
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
		$date_format				= bookacti_get_message( 'date_format_long' );
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
		$availability_period = bookacti_get_calendar_field_availability_period( $calendar_data );
		$calendar_start	= new DateTime( $availability_period[ 'start' ], $timezone );
		$calendar_end	= new DateTime( $availability_period[ 'end' ], $timezone );
		
		if( $group_start < $calendar_start ) {
			$validated[ 'error' ] = 'group_of_events_starts_before_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_start->format( 'Y-m-d H:i:s' ), $date_format );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018 10:53 am") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book a group if any of its events starts before %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
		if( $group_end > $calendar_end ) {
			$validated[ 'error' ] = 'group_of_events_ends_after_availability_period';
			$datetime_formatted = bookacti_format_datetime( $calendar_end->format( 'Y-m-d H:i:s' ), $date_format );
			/* translators: %s is a formatted date (e.g.: "January 20, 2018 10:53 am") */
			$validated[ 'message' ] = sprintf( esc_html__( 'You cannot book a group if any of its events takes place after %s.', 'booking-activities' ), $datetime_formatted );
			return $validated;
		}
	}	
	
	// So far, so good
	$validated[ 'status' ] = 'success';
	return $validated;
}




/***** EVENTS *****/

/**
 * Get array of events from raw events from database
 * @since 1.2.2
 * @version 1.8.4
 * @param array $events Array of objects events from database
 * @param array $raw_args {
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to compute past events
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type boolean $bounding_events_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_get_events_array_from_db_events( $events, $raw_args = array() ) {
	$events_array = array( 'data' => array(), 'events' => array() );
	if( ! $events ) { return $events_array; }
	
	$default_args = array(
		'interval' => array(),
		'skip_exceptions' => 1,
		'past_events' => 0,
		'bounding_events_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	// Get event ids
	$event_ids = array();
	foreach( $events as $event ) { $event_ids[] = $event->event_id; }
	
	// Get event exceptions
	$args[ 'exceptions' ] = array();
	if( $args[ 'skip_exceptions' ] ) {
		$exceptions = bookacti_get_exceptions_by_event( array( 'events' => $event_ids, 'types'	=> array( 'date' ), 'only_values' => true ) );
	}
	
	// Keep only the events having the min start, and the max end
	if( $args[ 'bounding_events_only' ] ) {
		$events = bookacti_get_bounding_events_from_db_events( $events, array_merge( $args, array( 'exceptions' => $exceptions ) ) );
	}
	if( ! $events ) { return $events_array; }
	
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
			$args[ 'exceptions_dates' ]	= $args[ 'skip_exceptions' ] && ! empty( $exceptions[ $event->event_id ] ) ? $exceptions[ $event->event_id ] : array();
			$new_occurrences				= bookacti_get_occurrences_of_repeated_event( $event, $args );
			$events_array[ 'events' ]	= array_merge( $events_array[ 'events' ], $new_occurrences );
		}
	}
	
	return $events_array;
}


/**
 * Keep only the events having the min start, and the max end
 * @since 1.8.0
 * @version 1.8.4
 * @param array $events
 * @param array $raw_args {
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type array $exceptions array( event_id => array( 'Y-m-d', ... ) )
 *  @type boolean $skip_exceptions Whether to retrieve occurrence on exceptions
 *  @type boolean $past_events Whether to include past events
 *  @type boolean $bounding_events_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_get_bounding_events_from_db_events( $events, $raw_args = array() ) {
	if( ! $events ) { return array(); }
	
	$default_args = array(
		'interval' => array(),
		'exceptions' => array(),
		'skip_exceptions' => 1,
		'past_events' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	$args[ 'bounding_events_only' ] = 1;
	
	$bounding_dates = array();
	$bounding_events = array();
	$min_event = false;
	$max_event = false;
	
	foreach( $events as $event ) {
		$single_events = array();
		
		// For repeated events, generate the first and the last occurrence of the interval
		if( ! empty( $event->repeat_freq ) && $event->repeat_freq !== 'none' && ! empty( $event->repeat_from ) && ! empty( $event->repeat_to ) ) { 
			$from_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $event->repeat_from . ' 00:00:00' );
			$to_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $event->repeat_to . ' 23:59:59' );
			
			// Skip if it is fully included in bounding dates
			if( ! empty( $bounding_dates[ 'start' ] ) && $from_datetime >= $bounding_dates[ 'start' ]
			 && ! empty( $bounding_dates[ 'end' ] ) && $to_datetime <= $bounding_dates[ 'end' ] ) { continue; }
			
			// Get bounding occurrences
			$args[ 'exceptions_dates' ] = $args[ 'skip_exceptions' ] && ! empty( $args[ 'exceptions' ][ $event->event_id ] ) ? $args[ 'exceptions' ][ $event->event_id ] : array();
			$occurrences = bookacti_get_occurrences_of_repeated_event( $event, $args );
			
			// Add occurrences as single events
			foreach( $occurrences as $occurrence ) {
				$occurrence_object = clone $event;
				$occurrence_object->start = $occurrence[ 'start' ];
				$occurrence_object->end = $occurrence[ 'end' ];
				$occurrence_object->repeat_freq = 'none';
				$occurrence_object->repeat_from = '';
				$occurrence_object->repeat_to = '';
				$single_events[] = $occurrence_object;
			}
		} 
		// For single events, add it as is
		else { $single_events[] = clone $event; }
		
		foreach( $single_events as $single_event ) {
			$start_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $single_event->start );
			$end_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $single_event->end );
			if( empty( $bounding_dates[ 'start' ] ) || ( ! empty( $bounding_dates[ 'start' ] ) && $start_datetime < $bounding_dates[ 'start' ] ) )	{ $bounding_dates[ 'start' ] = $start_datetime; $min_event = clone $single_event; }
			if( empty( $bounding_dates[ 'end' ] ) || ( ! empty( $bounding_dates[ 'end' ] ) && $end_datetime > $bounding_dates[ 'end' ] ) )			{ $bounding_dates[ 'end' ] = $end_datetime; $max_event = clone $single_event; }
		}
	}
	
	$is_same_event = $min_event && $max_event && $min_event->event_id === $max_event->event_id && $min_event->start === $max_event->start;
	if( $min_event ) { $bounding_events[] = $min_event; }
	if( $max_event && ! $is_same_event ) { $bounding_events[] = $max_event; }
	
	return $bounding_events;
}


/**
 * Get occurrences of repeated events
 * @since 1.8.4 (was bookacti_get_occurences_of_repeated_event)
 * @version 1.8.10
 * @param object $event Event data 
 * @param array $raw_args {
 *  @type array $interval array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' )
 *  @type array $exceptions_dates array( 'Y-m-d', ... )
 *  @type boolean $past_events Whether to compute past events
 *  @type boolean $bounding_events_only Whether to retrieve the first and the last events only
 * }
 * @return array
 */
function bookacti_get_occurrences_of_repeated_event( $event, $raw_args = array() ) {
	if( ! $event ) { return array(); }
	
	$default_args = array(
		'interval' => array(),
		'exceptions_dates' => array(),
		'past_events' => 0,
		'bounding_events_only' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	// Get the repeat from and to datetimes, and the repeat interval
	$repeat = bookacti_get_event_repeat_data( $event, $args );
	if( ! $repeat ) { return array(); }
	if( $repeat[ 'from' ] > $repeat[ 'to' ] ) { return array(); }
	
	// Common properties
	$shared_properties = array(
		'id'				=> ! empty( $event->event_id ) ? $event->event_id : ( ! empty( $event->id ) ? $event->id : 0 ),
		'title'				=> ! empty( $event->title ) ? apply_filters( 'bookacti_translate_text', $event->title ) : '',
		'color'				=> ! empty( $event->color ) ? $event->color : '',
		'durationEditable'	=> ! empty( $event->is_resizable ) ? true : false
	);
	
	// Init variables to compute occurrences
	$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$interval_start		= ! empty( $args[ 'interval' ][ 'start' ] ) ? new DateTime( $args[ 'interval' ][ 'start' ], $timezone ) : '';
	$interval_end		= ! empty( $args[ 'interval' ][ 'end' ] ) ? new DateTime( $args[ 'interval' ][ 'end' ], $timezone ) : '';
	$event_start		= new DateTime( $event->start, $timezone );
	$event_end			= new DateTime( $event->end, $timezone );
	$event_duration		= $event_start->diff( $event_end );
	$event_start_time	= substr( $event->start, 11 );
	
	// Compute occurrences
	$events		= array();
	$start_loop	= clone $repeat[ 'from' ];
	$start_loop->setTime( 00, 00, 00 );
	$end_loop	= clone $repeat[ 'to' ];
	$end_loop->setTime( 23, 59, 59 );
	$loop		= clone $start_loop;
	$iterate	= $loop < $end_loop;
	$operation	= 'add';
	
	while( $iterate ) {
		// Compute start and end dates
		$current_loop = clone $loop;
		$occurrence_start = new DateTime( $current_loop->format( 'Y-m-d' ) . ' ' . $event_start_time, $timezone );
		$occurrence_end = clone $occurrence_start;
		$occurrence_end->add( $event_duration );
		
		// Allow repeat interval to change between each occurrence
		$repeat_interval = is_callable( $repeat[ 'interval' ] ) ? call_user_func_array( $repeat[ 'interval' ], array( $event, $args, $current_loop, $operation ) ) : $repeat[ 'interval' ];
		
		// Increase loop for next iteration
		if( $operation === 'add' ) { $loop->add( $repeat_interval ); $iterate = $loop < $end_loop; } 
		else { $loop->sub( $repeat_interval ); $iterate = $loop > $start_loop; }
		
		// Check if the occurrence is on an exception
		if( in_array( $occurrence_start->format( 'Y-m-d' ), $args[ 'exceptions_dates' ], true ) ) { continue; }
		
		// Check if the occurrence is in the interval to be rendered
		if( $args[ 'interval' ] ) {
			if( $interval_start && $interval_start > $occurrence_start ) { continue; }
			if( $interval_end && $interval_end < $occurrence_start ) { continue; }
		}
		
		// Format start and end dates
		$event_occurrence = apply_filters( 'bookacti_event_occurrence', array(
			'start'	=> $occurrence_start->format( 'Y-m-d H:i:s' ),
			'end'	=> $occurrence_end->format( 'Y-m-d H:i:s' )
		), $event, $args, $current_loop );
		
		
		// Add this occurrence to events array
		if( $event_occurrence ) { 
			$events[] = array_merge( $shared_properties, $event_occurrence );
			// For bounding events, now that we have the first event, start the loop backwards, get the last event, and exit the loop
			if( $args[ 'bounding_events_only' ] ) {
				if( $operation === 'add' ) { $operation = 'sub'; $loop = clone $end_loop; $iterate = $loop > $start_loop; }
				else { break; }
			}
		}
	}
	
	// Make sure bounding events are not the same
	if( $args[ 'bounding_events_only' ] && ! empty( $events[ 0 ] ) && ! empty( $events[ 1 ] ) ) {
		if( $events[ 0 ][ 'start' ] === $events[ 1 ][ 'start' ] ) {
			unset( $events[ 1 ] );
		}
	}
	
	return $events;
}


/**
 * Get the event repeat from and to DateTime, and the repeat interval DateInterval (or callable)
 * @since 1.8.0
 * @version 1.8.4
 * @param object $event
 * @param array $args See bookacti_get_occurrences_of_repeated_event documentation
 * @return array {
 *  @type DateTime $from
 *  @type DateTime $to
 *  @type DateInterval|callable $interval
 * }
 */
function bookacti_get_event_repeat_data( $event, $args ) {
	// Init variables to compute repeat from, to and interval
	$get_started_events	= bookacti_get_setting_value( 'bookacti_general_settings', 'started_events_bookable' );
	$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_time		= new DateTime( 'now', $timezone );
	$current_date		= $current_time->format( 'Y-m-d' );
	$event_start		= new DateTime( $event->start, $timezone );
	$event_end			= new DateTime( $event->end, $timezone );
	$event_duration		= $event_start->diff( $event_end );
	$event_start_time	= substr( $event->start, 11 );
	$event_monthday		= $event_start->format( 'd' );
	$repeat_from		= new DateTime( $event->repeat_from . ' 00:00:00', $timezone );
	$repeat_to			= new DateTime( $event->repeat_to . ' 23:59:59', $timezone );
	
	// Check if the repetition period is in the interval to be rendered
	if( $args[ 'interval' ] ) {
		// If the repetition period is totally outside the desired interval, skip the event
		// Else, restrict the repetition period
		if( $args[ 'interval' ][ 'start' ] ) {
			$interval_start = new DateTime( $args[ 'interval' ][ 'start' ], $timezone );
			if( $interval_start > $repeat_from && $interval_start > $repeat_to ) { return array(); }
			if( $interval_start > $repeat_from ) { $repeat_from = clone $interval_start; }
		}
		if( $args[ 'interval' ][ 'end' ] ) {
			$interval_end	= new DateTime( $args[ 'interval' ][ 'end' ], $timezone );
			if( $interval_end < $repeat_from && $interval_end < $repeat_to ) { return array(); }
			if( $interval_end < $repeat_to ) { $repeat_to = clone $interval_end; }
		}
	}
	
	// Make sure repeated events don't start in the past if not explicitly allowed
	if( ! $args[ 'past_events' ] && $current_time > $repeat_from ) {
		$repeat_from = new DateTime( $current_date . ' 00:00:00', $timezone );

		$first_potential_event_start= new DateTime( $current_date . ' ' . $event_start_time, $timezone );
		$first_potential_event_end	= clone $first_potential_event_start;
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
	
	// Compute the repeat interval according to the repeat frequency
	switch( $event->repeat_freq ) {
		case 'daily':
			$repeat_interval = new DateInterval( 'P1D' );
			break;
		case 'weekly':
			$repeat_interval = new DateInterval( 'P7D' );
			// We need to make sure the repetition start from the week day of the event
			$event_weekday = $event_start->format( 'N' );
			if( $repeat_from->format( 'N' ) !== $event_weekday ) { $repeat_from->modify( 'next ' . $event_start->format( 'l' ) ); }
			if( $repeat_to->format( 'N' ) !== $event_weekday ) { $repeat_to->modify( 'previous ' . $event_start->format( 'l' ) ); }
			break;
		case 'monthly':
			// We need to make sure the repetition starts and ends on the event month day
			if( $repeat_from->format( 'd' ) !== $event_monthday ) {
				// If the event_monthday is 31 (or 29, 30 or 31 for February)
				if( $event_monthday > $repeat_from->format( 't' ) ) { 
					$repeat_from->modify( 'last day of this month' );
				} else if( $repeat_from->format( 'd' ) < $event_monthday ) {
					$repeat_from->modify( 'first day of this month' )->modify( '+' . ( $event_monthday - 1 ) . ' day' );
				} else { 
					$repeat_from->modify( 'first day of next month' );
					if( $event_monthday > $repeat_from->format( 't' ) ) { $repeat_from->modify( 'last day of this month' ); }
					else { $repeat_from->modify( '+' . ( $event_monthday - 1 ) . ' day' ); }
				}
			}
			if( $repeat_to->format( 'd' ) !== $event_monthday ) {
				// If the event_monthday is 31 (or 29, 30 or 31 for February)
				if( $event_monthday > $repeat_to->format( 't' ) ) {
					// Keep the date if it is already the last day of the month, else change it to the last day of previous month
					if( $repeat_to->format( 'd' ) !== $repeat_to->format( 't' ) ) { $repeat_to->modify( 'last day of previous month' ); }
				} else if( $repeat_to->format( 'd' ) < $event_monthday ) {
					$repeat_to->modify( 'first day of previous month' );
					if( $event_monthday > $repeat_to->format( 't' ) ) { $repeat_to->modify( 'last day of this month' ); }
					else { $repeat_to->modify( '+' . ( $event_monthday - 1 ) . ' day' ); }
				} else { 
					$repeat_to->modify( 'first day of this month' )->modify( '+' . ( $event_monthday - 1 ) . ' day' );
				}
			}
			
			// Callback in the loop
			$repeat_interval = 'bookacti_get_interval_to_next_occurrence';
			break;
		default:
			break;
	}
	
	// Repeat dates must be full days
	$repeat_from->setTime( 00, 00, 00 );
	$repeat_to->setTime( 23, 59, 59 );
	
	return apply_filters( 'bookacti_event_repeat_data', array( 'from' => $repeat_from, 'to' => $repeat_to, 'interval' => $repeat_interval ), $event, $args );
}


/**
 * Compute the interval to the next occurrence
 * @since 1.8.4 (was bookacti_get_interval_to_next_occurence)
 * @param object $event
 * @param array $args See bookacti_get_occurrences_of_repeated_event documentation
 * @param DateTime $current_loop
 * @param string $operation Either "add" or "sub". Make sure it works in both directions.
 * @return DateInterval
 */
function bookacti_get_interval_to_next_occurrence( $event, $args, $current_loop, $operation ) {
	$repeat_interval = new DateInterval( 'P1D' ); // Default to daily to avoid unexpected behavior such as infinite loop
	if( $event->repeat_freq === 'monthly' ) {
		$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$event_start		= new DateTime( $event->start, $timezone );
		$event_monthday		= $event_start->format( 'd' );
		
		$next_month = clone $current_loop;
		if( $operation === 'add' ) { $next_month->modify( 'first day of next month' ); } 
		else { $next_month->modify( 'first day of previous month' ); }
		if( $event_monthday > $next_month->format( 't' ) ) { $next_month->modify( 'last day of this month' ); }
		else { $next_month->modify( '+' . ( $event_monthday - 1 ) . ' day' ); }
		$days_to_next_month	= abs( $next_month->diff( $current_loop )->format( '%a' ) );
		$repeat_interval = new DateInterval( 'P' . $days_to_next_month . 'D' );
	}
	return $repeat_interval;
}


/**
 * Get a new interval of events to load. Computed from the compulsory interval, or now's date and template interval.
 * @since 1.2.2
 * @version 1.8.0
 * @param array $availability_period array( 'start'=> 'Y-m-d H:i:s', 'end'=> 'Y-m-d H:i:s' ) 
 * @param array $min_interval array( 'start'=> 'Y-m-d', 'end'=> 'Y-m-d' )
 * @param int $interval_duration Number of days of the interval
 * @param bool $past_events
 * @return array array( 'start'=> 'Y-m-d H:i:s', 'end'=> 'Y-m-d H:i:s' )
 */
function bookacti_get_new_interval_of_events( $availability_period, $min_interval = array(), $interval_duration = 0, $past_events = false ) {
	if( ! isset( $availability_period[ 'start' ] ) || ! isset( $availability_period[ 'end' ] ) ) { return array(); }
	
	$timezone		= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_time	= new DateTime( 'now', $timezone );
	$current_date	= $current_time->format( 'Y-m-d H:i:s' );
	
	$calendar_start	= new DateTime( $availability_period[ 'start' ], $timezone );
	$calendar_end	= new DateTime( $availability_period[ 'end' ], $timezone );
	
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
	
	$interval_start	= new DateTime( substr( $min_interval[ 'start' ], 0, 10 ) . ' 00:00:00', $timezone );
	$interval_end	= new DateTime( substr( $min_interval[ 'end' ], 0, 10 ) . ' 23:59:59', $timezone );
	$min_interval_duration = intval( abs( $interval_end->diff( $interval_start )->format( '%a' ) ) );
	
	if( $min_interval_duration > $interval_duration ) { $interval_duration = $min_interval_duration; }
	
	$half_interval = abs( round( intval( $interval_duration - $min_interval_duration ) / 2 ) );
	$interval_end_days_to_add = $half_interval;
	
	// Compute Interval start
	if( $past_events ) {
		$interval_start->sub( new DateInterval( 'P' . $half_interval . 'D' ) );
		if( $calendar_start > $interval_start ) {
			$interval_end_days_to_add += abs( $interval_start->diff( $calendar_start )->format( '%a' ) );
		}
	} else {
		$interval_end_days_to_add += $half_interval;
	}
	if( $calendar_start > $interval_start ) { $interval_start = clone $calendar_start; }
	
	// Compute interval end
	$interval_end->add( new DateInterval( 'P' . $interval_end_days_to_add . 'D' ) );
	if( $calendar_end < $interval_end ) { $interval_end = clone $calendar_end; }

	$interval = array( 
		'start' => $interval_start->format( 'Y-m-d H:i:s' ), 
		'end' => $interval_end->format( 'Y-m-d H:i:s' ) 
	);

	return $interval;
}


/**
 * Get availability period from calendar field data
 * @since 1.7.17
 * @version 1.8.6
 * @param array|int $calendar_field
 * @return array
 */
function bookacti_get_calendar_field_availability_period( $calendar_field ) {
	if( is_numeric( $calendar_field ) ) { $calendar_field = bookacti_get_form_field_data( $calendar_field ); }
	if( ! is_array( $calendar_field ) ) { $calendar_field = array(); }
	
	// Convert absolute period from date to datetime
	$abs_start_date	= ! empty( $calendar_field[ 'start' ] ) ? bookacti_sanitize_date( $calendar_field[ 'start' ] ) : '';
	$abs_end_date	= ! empty( $calendar_field[ 'end' ] ) ? bookacti_sanitize_date( $calendar_field[ 'end' ] ) : '';
	if( $abs_start_date )	{ $calendar_field[ 'start' ] = $abs_start_date . ' 00:00:00'; }
	if( $abs_end_date )		{ $calendar_field[ 'end' ] = $abs_end_date . ' 23:59:59'; }
	
	// Compute availability period 
	$absolute_period = array(
		'start'	=> ! empty( $calendar_field[ 'start' ] ) ? bookacti_sanitize_datetime( $calendar_field[ 'start' ] ) : ( ! empty( $calendar_field[ 'past_events' ] ) ? '1970-02-01 00:00:00' : '' ),
		'end'	=> ! empty( $calendar_field[ 'end' ] ) ? bookacti_sanitize_datetime( $calendar_field[ 'end' ] ) : ''
	);
	$relative_period = array(
		'start'	=> ! empty( $calendar_field[ 'availability_period_start' ] ) && is_numeric( $calendar_field[ 'availability_period_start' ] ) ? intval( $calendar_field[ 'availability_period_start' ] ) : 0,
		'end'	=> ! empty( $calendar_field[ 'availability_period_end' ] ) && is_numeric( $calendar_field[ 'availability_period_end' ] ) ? intval( $calendar_field[ 'availability_period_end' ] ) : 0
	);
	
	return bookacti_get_availability_period( $absolute_period, $relative_period );
}


/**
 * Get availability period according to relative and absolute dates
 * @since 1.5.9
 * @version 1.8.4
 * @param array $absolute_period
 * @param array $relative_period
 * @param boolean $bypass_relative_period
 * @return array
 */
function bookacti_get_availability_period( $absolute_period = array(), $relative_period = array() ) {
	$timezone		= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_time	= new DateTime( 'now', $timezone );
	
	$max_dt					= new DateTime( '2037-12-31 23:59:59', new DateTimeZone( 'UTC' ) );
	$calendar_start_date	= ! empty( $absolute_period[ 'start' ] ) ? $absolute_period[ 'start' ] : $current_time->format( 'Y-m-d H:i:s' );
	$calendar_end_date		= ! empty( $absolute_period[ 'end' ] ) ? $absolute_period[ 'end' ] : '2037-12-31 23:59:59';
	
	if( $relative_period ) { 
		// Take default relative period if not set
		$relative_period_start	= isset( $relative_period[ 'start' ] ) ? intval( $relative_period[ 'start' ] ) : 0;
		$relative_period_end	= isset( $relative_period[ 'end' ] ) ? intval( $relative_period[ 'end' ] ) : 0; 
		
		// Restrict template interval if a relative period is set
		if( $relative_period_start > 0 ) {
			$relative_period_start_iso8601 = bookacti_format_duration( $relative_period_start, 'iso8601' );
			$relative_start_dt = clone $current_time;
			$relative_start_dt->add( new DateInterval( $relative_period_start_iso8601 ) );
			$calendar_start_dt = new DateTime( $calendar_start_date, $timezone );
			if( $relative_start_dt > $calendar_start_dt ) {
				$calendar_start_date = $relative_start_dt < $max_dt ? $relative_start_dt->format( 'Y-m-d H:i:s' ) : '2037-12-31 23:59:59';
			}
		}
		if( $relative_period_end > 0 ) {
			$relative_period_end_iso8601 = bookacti_format_duration( $relative_period_end, 'iso8601' );
			$relative_end_dt = clone $current_time;
			$relative_end_dt->add( new DateInterval( $relative_period_end_iso8601 ) );
			$calendar_end_dt = new DateTime( $calendar_end_date, $timezone );
			if( $relative_end_dt < $calendar_end_dt ) {
				$calendar_end_date = $relative_end_dt < $max_dt ? $relative_end_dt->format( 'Y-m-d H:i:s' ) : '2037-12-31 23:59:59';
			}
		}
	}
	
	$availability_period = array( 'start' => $calendar_start_date, 'end' => $calendar_end_date );
	
	return apply_filters( 'bookacti_availability_period', $availability_period, $absolute_period, $relative_period );
}


/**
 * Sanitize events interval
 * @since 1.2.2
 * @version 1.8.0
 * @param array $interval_raw
 * @return array
 */
function bookacti_sanitize_events_interval( $interval_raw ) {
	if( ! $interval_raw || ! is_array( $interval_raw ) ) { return array(); }
	$interval = array( 'start' => '', 'end' => '' );
	if( ! empty( $interval_raw[ 'start' ] ) ) {
		$date_start = bookacti_sanitize_date( $interval_raw[ 'start' ] );
		$date_end = bookacti_sanitize_date( $interval_raw[ 'end' ] );
		if( $date_start ) { $interval_raw[ 'start' ] = $date_start . ' 00:00:00'; }
		if( $date_end ) { $interval_raw[ 'end' ] = $date_end . ' 23:59:59'; }
		$interval[ 'start' ] = bookacti_sanitize_datetime( $interval_raw[ 'start' ] );
		$interval[ 'end' ] = bookacti_sanitize_datetime( $interval_raw[ 'end' ] );
	}
	return $interval;
}


/**
 * Get exceptions dates by event
 * @since 1.7.0
 * @version 1.8.0
 * @param array $raw_args {
 *  @type array $templates
 *  @type array $events
 *  @type array $types
 *  @type boolean $only_values
 * }
 * @return array
 */
function bookacti_get_exceptions_by_event( $raw_args = array() ) {
	$default_args = array(
		'templates' => array(),
		'events' => array(),
		'types'	=> array( 'date' ),
		'only_values' => 0
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	$exceptions = bookacti_get_exceptions( $raw_args );
	
	if( ! $exceptions ) { return array(); }
	
	// Order exceptions by event id
	$exceptions_by_event = array();
	if( $exceptions ) {
		foreach( $exceptions as $exception ) {
			if( ! $exception[ 'exception_value' ] ) { continue; }
			$event_id = $exception[ 'event_id' ];
			unset( $exception[ 'event_id' ] );
			if( ! isset( $exceptions_by_event[ $event_id ] ) ) { $exceptions_by_event[ $event_id ] = array(); }
			$exceptions_by_event[ $event_id ][] = $args[ 'only_values' ] ? $exception[ 'exception_value' ] : $exception;
		}
	}
	
	return $exceptions_by_event;
}


/**
 * Build a user-friendly events list
 * @since 1.1.0
 * @version 1.8.10
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
			$event[ 'duration' ] = bookacti_get_formatted_event_dates( $event[ 'start' ], $event[ 'end' ], true, $locale );
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
 * Get the formatted event start to event end dates
 * @since 1.8.10
 * @param string $start Format: Y-m-d h:i:s
 * @param string $end Format: Y-m-d h:i:s
 * @param boolean $html
 * @param string $locale
 * @return string
 */
function bookacti_get_formatted_event_dates( $start, $end, $html = true, $locale = 'site' ) {
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	$messages = bookacti_get_messages( true );
	$datetime_format = isset( $messages[ 'date_format_long' ][ 'value' ] ) ? apply_filters( 'bookacti_translate_text', $messages[ 'date_format_long' ][ 'value' ], $locale ) : '';
	$event_start = bookacti_format_datetime( $start, $datetime_format );
	
	// Format differently if the event start and end on the same day
	$start_and_end_same_day	= substr( $start, 0, 10 ) === substr( $end, 0, 10 );
	if( $start_and_end_same_day ) {
		$time_format = isset( $messages[ 'time_format' ][ 'value' ] ) ? apply_filters( 'bookacti_translate_text', $messages[ 'time_format' ][ 'value' ], $locale ) : '';
		$event_end = bookacti_format_datetime( $end, $time_format );
		$separator = isset( $messages[ 'date_time_separator' ][ 'value' ] ) ? apply_filters( 'bookacti_translate_text', $messages[ 'date_time_separator' ][ 'value' ], $locale ) : '';
	} else {
		$event_end = bookacti_format_datetime( $end, $datetime_format );
		$separator = isset( $messages[ 'dates_separator' ][ 'value' ] ) ? apply_filters( 'bookacti_translate_text', $messages[ 'dates_separator' ][ 'value' ], $locale ) : '';
	}
	
	// Format without HTML
	$dates = $event_start . $separator . $event_end;
	
	// Format with HTML
	if( $html ) {
		$class	= $start_and_end_same_day ? 'bookacti-booking-event-end-same-day' : '';
		$dates	= '<span class="bookacti-booking-event-start" >' . $event_start . '</span>'
				. '<span class="bookacti-booking-event-date-separator ' . $class . '" >' . $separator . '</span>'
				. '<span class="bookacti-booking-event-end ' . $class . '" >' . $event_end . '</span>';
	}
	
	return $dates;	
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
 * @version 1.8.4
 * @param array $events
 * @param string $name
 * @param string $description
 * @param int $sequence
 * @return string
 */
function bookacti_convert_events_to_ical( $events, $name = '', $description = '', $sequence = 0 ) {
	if( empty( $events[ 'events' ] ) ) { return ''; }
	
	$vcalendar = apply_filters( 'bookacti_events_ical_vcalendar_properties', array(
		'X-WR-CALNAME' => $name,
		'X-WR-CALDESC' => $description
	), $events, $sequence );
	
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj = new DateTimeZone( $timezone );
	$occurrence_counter = array();
	$vevents = array();
	
	foreach( $events[ 'events' ] as $event ) {
		// Increase the occurrence counter
		if( ! isset( $occurrence_counter[ $event[ 'id' ] ] ) ) { $occurrence_counter[ $event[ 'id' ] ] = 0; }
		++$occurrence_counter[ $event[ 'id' ] ];

		$uid			= $event[ 'id' ] . '-' . $occurrence_counter[ $event[ 'id' ] ];
		$event_start	= new DateTime( $event[ 'start' ], $timezone_obj );
		$event_end		= new DateTime( $event[ 'end' ], $timezone_obj );
		$current_time	= new DateTime( 'now', $timezone_obj );
		$now_formatted	= $current_time->format( 'Ymd\THis' );

		$vevents[] = apply_filters( 'bookacti_events_ical_vevent_properties', array(
			'UID'		=> $uid,
			'DTSTART'	=> $event_start->format( 'Ymd\THis' ),
			'DTEND'		=> $event_end->format( 'Ymd\THis' ),
			'SUMMARY'	=> bookacti_sanitize_ical_property( $event[ 'title' ], 'SUMMARY' ),
			'SEQUENCE'	=> $sequence
		), $event, $events, $vcalendar );
	}
	
	return bookacti_generate_ical( $vevents, $vcalendar );
}


/**
 * Generate a ICAL file of events according to booking system attributes
 * @since 1.6.0
 * @version 1.8.0
 * @param array $atts Booking system attributes
 * @param string $calname
 * @param string $caldesc
 * @param int $sequence
 */
function bookacti_export_events_page( $atts, $calname = '', $caldesc = '', $sequence = 0 ) {
	// Retrieve all events, bypass the interval and the relative availability period
	$availability_period = bookacti_get_calendar_field_availability_period( $atts );
	$events_interval = bookacti_get_new_interval_of_events( $availability_period, array(), 999999999, $atts[ 'past_events' ] );
	
	// Get the events
	$groups_ids	= array();
	$events		= array( 'events' => array(), 'data' => array() );
	if( $atts[ 'groups_only' ] ) {
		if( ! in_array( 'none', $atts[ 'group_categories' ], true ) ) {
			$groups_data = bookacti_get_groups_of_events( array( 'templates' => $atts[ 'calendars' ], 'group_categories' => $atts[ 'group_categories' ], 'availability_period' => $atts[ 'past_events_bookable' ] ? array() : $availability_period, 'started' => true, 'inactive' => false ) );
			$groups_ids[] = array_keys( $groups_data );
		}
		if( $groups_ids ) {
			$events	= bookacti_fetch_grouped_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'groups' => $groups_ids, 'group_categories' => $atts[ 'group_categories' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
		}
	} else if( $atts[ 'bookings_only' ] ) {
		$events = bookacti_fetch_booked_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'status' => $atts[ 'status' ], 'users' => $atts[ 'user_id' ] ? array( $atts[ 'user_id' ] ) : array(), 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );
	} else {
		$events	= bookacti_fetch_events( array( 'templates' => $atts[ 'calendars' ], 'activities' => $atts[ 'activities' ], 'past_events' => $atts[ 'past_events' ], 'interval' => $events_interval ) );	
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
 * @version 1.8.10
 * @param array $booking_group_data Sanitized with bookacti_sanitize_booking_group_data
 * @return int|boolean
 */
function bookacti_book_group_of_events( $booking_group_data ) {
	// Insert the booking group
	$booking_group_id = bookacti_insert_booking_group( $booking_group_data );
	$events = array();
	
	if( $booking_group_id ) {
		// If the group of events exists, get the events to be booked from the database
		$events = $booking_group_data[ 'event_group_id' ] ? bookacti_get_group_events( $booking_group_data[ 'event_group_id' ] ) : $booking_group_data[ 'grouped_events' ];
		
		if( $events ) {
			// Insert bookings
			foreach( $events as $i => $event ) {
				$booking_data = bookacti_sanitize_booking_data( array( 
					'group_id'			=> $booking_group_id,
					'user_id'			=> $booking_group_data[ 'user_id' ],
					'form_id'			=> $booking_group_data[ 'form_id' ],
					'order_id'			=> $booking_group_data[ 'order_id' ],
					'event_id'			=> $event[ 'id' ],
					'event_start'		=> $event[ 'start' ],
					'event_end'			=> $event[ 'end' ],
					'quantity'			=> $booking_group_data[ 'quantity' ],
					'status'			=> $booking_group_data[ 'status' ],
					'payment_status'	=> $booking_group_data[ 'payment_status' ],
					'expiration_date'	=> $booking_group_data[ 'expiration_date' ],
					'active'			=> $booking_group_data[ 'active' ]
				) );
				$booking_id = bookacti_insert_booking( $booking_data );
				$events[ $i ][ 'booking_id' ] = $booking_id;
			}
		}
	}

	return apply_filters( 'bookacti_group_of_events_booked', $booking_group_id, $booking_group_data, $events );
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
 * @version 1.8.10
 * @global wpdb $wpdb
 * @param int $group_id
 * @param boolean $fetch_inactive_events
 * @return array|false
 */
function bookacti_get_group_events( $group_id, $fetch_inactive_events = false ) {
	if( ! $group_id ) { return array(); }
	if( is_array( $group_id ) ) { $group_id = $group_id[ 0 ]; }
	if( ! is_numeric( $group_id ) ) { return array(); }

	$group_id = intval( $group_id );
	$groups_events = bookacti_get_groups_events( array(), array(), $group_id, $fetch_inactive_events );

	return isset( $groups_events[ $group_id ] ) ? $groups_events[ $group_id ] : array();
}