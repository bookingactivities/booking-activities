<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CALENDAR EDITOR

/**
 * Get booking system data
 * @since 1.7.4
 * @version 1.8.0
 * @param array $atts (see bookacti_format_booking_system_attributes())
 * @param int $template_id
 * @return array
 */
function bookacti_get_editor_booking_system_data( $atts, $template_id ) {
	$booking_system_data = $atts;
	
	$templates_data		= bookacti_get_templates_data( $template_id, true );
	$availability_period= array( 'start' => $templates_data[ $template_id ][ 'start' ] . ' 00:00:00', 'end' => $templates_data[ $template_id ][ 'end' ] . ' 23:59:59' );
	$events_interval	= bookacti_get_new_interval_of_events( $availability_period, array(), false, true );
	$events_args		= array( 'templates' => array( $template_id ), 'interval' => $events_interval );
	$events				= $events_interval ? bookacti_fetch_events_for_calendar_editor( $events_args ) : array();
	
	$booking_system_data[ 'calendars' ]				= array( $template_id );
	$booking_system_data[ 'events' ]				= $events[ 'events' ] ? $events[ 'events' ] : array();
	$booking_system_data[ 'events_data' ]			= $events[ 'data' ] ? $events[ 'data' ] : array();
	$booking_system_data[ 'events_interval' ]		= array( 'start' => substr( $events_interval[ 'start' ], 0, 10 ), 'end' => substr( $events_interval[ 'end' ], 0, 10 ) );
	$booking_system_data[ 'bookings' ]				= bookacti_get_number_of_bookings_by_events( $template_id );
	$booking_system_data[ 'exceptions' ]			= bookacti_get_exceptions_by_event( array( 'templates' => array( $template_id ) ) );
	$booking_system_data[ 'activities_data' ]		= bookacti_get_activities_by_template( $template_id, false, true );
	$booking_system_data[ 'groups_events' ]			= bookacti_get_groups_events( $template_id );
	$booking_system_data[ 'groups_data' ]			= bookacti_get_groups_of_events( array( 'templates' => array( $template_id ) ) );
	$booking_system_data[ 'group_categories_data' ]	= bookacti_get_group_categories( $template_id );
	$booking_system_data[ 'start' ]					= $availability_period[ 'start' ];
	$booking_system_data[ 'end' ]					= $availability_period[ 'end' ];
	$booking_system_data[ 'display_data' ]			= $templates_data[ $template_id ][ 'settings' ];
	$booking_system_data[ 'template_data' ]			= $templates_data[ $template_id ];

	return apply_filters( 'bookacti_editor_booking_system_data', $booking_system_data, $atts );
}




// PERMISSIONS
/**
 * Check if user is allowed to manage template
 * @version 1.7.17
 * @param int $template_id
 * @param int|false $user_id False for current user
 * @return boolean
 */
function bookacti_user_can_manage_template( $template_id, $user_id = false ) {
	$user_can_manage_template = false;
	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false, $user_id );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin( $user_id ) || $bypass_template_managers_check ) { $user_can_manage_template = true; }
	else {
		$admins = bookacti_get_template_managers( $template_id );
		if( $admins ) {
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_template = true; }
		}
	}

	return apply_filters( 'bookacti_user_can_manage_template', $user_can_manage_template, $template_id, $user_id );
}


/**
 * Check if user is allowed to manage activity
 * @version 1.7.17
 * @param int $activity_id
 * @param int|false $user_id False for current user
 * @param array|false $admins False to retrieve the activity managers
 * @return boolean
 */
function bookacti_user_can_manage_activity( $activity_id, $user_id = false, $admins = false ) {
	$user_can_manage_activity = false;
	$bypass_activity_managers_check = apply_filters( 'bookacti_bypass_activity_managers_check', false, $user_id );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin( $user_id ) || $bypass_activity_managers_check ) { $user_can_manage_activity = true; }
	else {
		$admins = $admins === false ? bookacti_get_activity_managers( $activity_id ) : $admins;
		if( $admins ) {
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_activity = true; }
		}
	}

	return apply_filters( 'bookacti_user_can_manage_activity', $user_can_manage_activity, $activity_id, $user_id );
}


/**
 * Get template managers
 * @param int $activity_id
 * @return array
 */
function bookacti_get_template_managers( $template_id ) {
	return bookacti_get_managers( 'template', $template_id );
}


/**
 * Get activity managers
 * @param int $activity_id
 * @return array
 */
function bookacti_get_activity_managers( $activity_id ) {	
	return bookacti_get_managers( 'activity', $activity_id );
}




// TEMPLATE X ACTIVITIES
/**
 * Retrieve template activities list
 * @version 1.7.17
 * @param int $template_id
 * @return boolean|string 
 */
function bookacti_get_template_activities_list( $template_id ) {
	if( ! $template_id ) { return false; }

	$activities = bookacti_get_activities_by_template( array( $template_id ) );

	ob_start();
	foreach ( $activities as $activity ) {
		$title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
		?>
		<div class='activity-row'>
			<div class='activity-show-hide dashicons dashicons-visibility' data-activity-id='<?php echo esc_attr( $activity[ 'id' ] ); ?>' data-activity-visible='1' ></div>
			<div class='activity-container'>
				<div
					class='fc-event ui-draggable ui-draggable-handle'
					data-event='{"title": "<?php echo htmlentities( esc_attr( $title ), ENT_QUOTES ); ?>", "activity_id": "<?php echo esc_attr( $activity[ 'id' ] ); ?>", "color": "<?php echo esc_attr( $activity[ 'color' ] ); ?>", "stick":"true"}' 
					data-activity-id='<?php echo esc_attr( $activity[ 'id' ] ); ?>'
					data-duration='<?php echo esc_attr( $activity[ 'duration' ] ? $activity[ 'duration' ] : '000.01:00:00' ); ?>'
					title='<?php esc_attr_e( $title ); ?>'
					style='border-color:<?php echo esc_attr( $activity[ 'color' ] ); ?>; background-color:<?php echo esc_attr( $activity[ 'color' ] ); ?>'
					>
					<?php echo $title; ?>
				</div>
			</div>
		<?php
		if( current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity[ 'id' ] ) ) {
		?>
			<div class='activity-gear dashicons dashicons-admin-generic' data-activity-id='<?php echo esc_attr( $activity[ 'id' ] ); ?>' ></div>
		<?php
		}
		?>
		</div>
		<?php
	}
	return ob_get_clean();
}




// TEMPLATE SETTINGS

/**
 * Get templates data
 * @since 1.7.3 (was bookacti_fetch_templates)
 * @global wpdb $wpdb
 * @param array $template_ids
 * @param boolean $ignore_permissions
 * @param int $user_id
 * @return array
 */
function bookacti_get_templates_data( $template_ids = array(), $ignore_permissions = false, $user_id = 0 ) {
	$templates = bookacti_fetch_templates( $template_ids, $ignore_permissions, $user_id );

	$retrieved_template_ids = array_keys( $templates );

	$templates_meta		= bookacti_get_metadata( 'template', $retrieved_template_ids );
	$templates_managers	= bookacti_get_managers( 'template', $retrieved_template_ids );

	foreach( $templates as $template_id => $template ) {
		$templates[ $template_id ][ 'settings' ]	= isset( $templates_meta[ $template_id ] ) ? $templates_meta[ $template_id ] : array();
		$templates[ $template_id ][ 'admin' ]	= isset( $templates_managers[ $template_id ] ) ? $templates_managers[ $template_id ] : array();
	}

	return $templates;
}


/**
 * Get additional calendar fields default data
 * @since 1.5.0
 * @version 1.8.0
 * @param array $fields
 * @return array
 */
function bookacti_get_calendar_fields_default_data( $fields = array() ) {
	if( ! is_array( $fields ) ) { $fields = array(); }
	$defaults = array();

	// Day Begin
	if( ! $fields || in_array( 'minTime', $fields, true ) ) {
		$defaults[ 'minTime' ] = array(
			'type'			=> 'time',
			'name'			=> 'minTime',
			'value'			=> '08:00',
			/* translators: Refers to the first hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/minTime/ */
			'title'			=> esc_html__( 'Day begin', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when you want the days to begin on the calendar. E.g.: "06:00" Days will begin at 06:00am.', 'booking-activities' )
		);
	}

	// Day end
	if( ! $fields || in_array( 'maxTime', $fields, true ) ) {
		$defaults[ 'maxTime' ] = array(
			'type'			=> 'time',
			'name'			=> 'maxTime',
			'value'			=> '20:00',
			/* translators: Refers to the last hour displayed on calendar. More information: http://fullcalendar.io/docs/agenda/maxTime/ */
			'title'			=> esc_html__( 'Day end', 'booking-activities' ),
			'tip'			=> esc_html__( 'Set when you want the days to end on the calendar. E.g.: "18:00" Days will end at 06:00pm.', 'booking-activities' )
		);
	}

	// Snap Duration
	if( ! $fields || in_array( 'snapDuration', $fields, true ) ) {
		$defaults[ 'snapDuration' ] = array(
			'type'			=> 'text',
			'name'			=> 'snapDuration',
			'class'			=> 'bookacti-time-field',
			'placeholder'	=> '23:59',
			'value'			=> '00:05',
			/* translators: Refers to the time interval at which a dragged event will snap to the agenda view time grid. E.g.: 00:20', you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...). More information: http://fullcalendar.io/docs/agenda/snapDuration/ */
			'title'			=> esc_html__( 'Snap frequency', 'booking-activities' ),
			'tip'			=> esc_html__( 'The time interval at which a dragged event will snap to the agenda view time grid. E.g.: "00:20", you will be able to drop an event every 20 minutes (at 6:00am, 6:20am, 6:40am...).', 'booking-activities' )
		);
	}

	return apply_filters( 'bookacti_calendar_fields_default_data', $defaults, $fields );
}


/**
 * Get a unique template setting made from a combination of multiple template settings
 * @since	1.2.2 (was bookacti_get_mixed_template_settings)
 * @version 1.7.17
 * @param	array|int $template_ids Array of template ids or single template id
 * @param	boolean $past_events Whether to allow past events
 * @return	array
 */
function bookacti_get_mixed_template_data( $template_ids, $past_events = false ) {
	$templates_data = bookacti_get_templates_data( $template_ids, true );
	$mixed_data = array();
	$mixed_settings	= array();

	foreach( $templates_data as $template_data ){
		$settings = $template_data[ 'settings' ];
		if( isset( $template_data[ 'start' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_data[ 'start' ] ) 
				|| isset( $mixed_data[ 'start' ] ) && strtotime( $template_data[ 'start' ] ) < strtotime( $mixed_data[ 'start' ] ) ) {

				$mixed_data[ 'start' ] = $template_data[ 'start' ];
			} 
		}
		if( isset( $template_data[ 'end' ] ) ) {
			// Keep the higher value
			if(  ! isset( $mixed_data[ 'end' ] ) 
				|| isset( $mixed_data[ 'end' ] ) && strtotime( $template_data[ 'end' ] ) < strtotime( $mixed_data[ 'end' ] ) ) {

				$mixed_data[ 'end' ] = $template_data[ 'end' ];
			} 
		}
		if( isset( $settings[ 'minTime' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_settings[ 'minTime' ] ) 
				|| isset( $mixed_settings[ 'minTime' ] ) && strtotime( $settings[ 'minTime' ] ) < strtotime( $mixed_settings[ 'minTime' ] ) ) {

				$mixed_settings[ 'minTime' ] = $settings[ 'minTime' ];
			} 
		}
		if( isset( $settings[ 'maxTime' ] ) ) {
			// Keep the higher value
			if(  ! isset( $mixed_settings[ 'maxTime' ] ) 
				|| isset( $mixed_settings[ 'maxTime' ] ) && strtotime( $settings[ 'maxTime' ] ) > strtotime( $mixed_settings[ 'maxTime' ] ) ) {

				$mixed_settings[ 'maxTime' ] = $settings[ 'maxTime' ];
			} 
		}
		if( isset( $settings[ 'snapDuration' ] ) ) {
			// Keep the lower value
			if(  ! isset( $mixed_settings[ 'snapDuration' ] ) 
				|| isset( $mixed_settings[ 'snapDuration' ] ) && strtotime( $settings[ 'snapDuration' ] ) < strtotime( $mixed_settings[ 'snapDuration' ] ) ) {

				$mixed_settings[ 'snapDuration' ] = $settings[ 'snapDuration' ];
			} 
		}
	}

	// Limit the template range to future events
	if( ! $past_events ) {
		$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
		$current_time		= new DateTime( 'now', $timezone );
		$template_start		= new DateTime( $mixed_data[ 'start' ], $timezone );
		if( $template_start < $current_time ) {
			$mixed_data[ 'start' ] = $current_time->format( 'Y-m-d' );
		}
	}

	// Add mixed settings
	$mixed_data[ 'settings' ] = $mixed_settings;

	return apply_filters( 'bookacti_mixed_template_settings', $mixed_data, $templates_data, $template_ids, $past_events );
}




// TEMPLATES X ACTIVITIES ASSOCIATION

// UPDATE THE LIST OF TEMPLATES ASSOCIATED TO AN ACTIVITY ID
function bookacti_update_templates_list_by_activity_id( $new_templates, $activity_id ) {
	$old_templates = bookacti_get_templates_by_activity( $activity_id );

	// Unset templates already added
	foreach( $new_templates as $i => $new_template ) {
		foreach( $old_templates as $j => $old_template ) {
			if( $new_template === $old_template ) {
				unset( $new_templates[ $i ] );
				unset( $old_templates[ $j ] );
			}
		}
	}

	// Insert new templates
	$inserted = 0;
	if( count( $new_templates ) > 0 ) {
		$inserted = bookacti_insert_templates_x_activities( $new_templates, array( $activity_id ) );
	}

	// Delete old templates
	$deleted = 0;
	if( count( $old_templates ) > 0 ) {
		$deleted = bookacti_delete_templates_x_activities( $old_templates, array( $activity_id ) );
	}

	return $inserted + $deleted;
}


/**
 * Update the list of activities associated to a template id
 * 
 * @version 1.2.2
 * @param array $new_activities
 * @param int $template_id
 * @return int|false
 */
function bookacti_bind_activities_to_template( $new_activities, $template_id ) {

	if( is_numeric( $new_activities ) ) { $new_activities = array( $new_activities ); }

	$old_activities = bookacti_get_activity_ids_by_template( $template_id, false );

	// Unset templates already added
	foreach( $new_activities as $i => $new_activity ) {
		foreach( $old_activities as $j => $old_activity ) {
			if( $new_activity === $old_activity ) {
				unset( $new_activities[ $i ] );
			}
		}
	}

	// Insert new activity bounds
	$inserted = 0;
	if( count( $new_activities ) > 0 ) {
		$inserted = bookacti_insert_templates_x_activities( array( $template_id ), $new_activities );
	}

	return $inserted;
}




// EVENTS

/**
 * Update event exceptions
 * @since 1.8.0
 * @param int $event_id
 * @param array $new_exceptions
 * @param array $delete_old Whether to delete the existing exceptions first
 * @return int|false
 */
function bookacti_update_exceptions( $event_id, $new_exceptions, $delete_old = true ) {
	// Check if the exceptions already exist
	$dates_to_insert = $new_exceptions;
	$dates_to_delete = array();
	if( $new_exceptions ) {
		$old_exceptions = bookacti_get_exceptions( array( 'events' => array( $event_id ), 'types' => array( 'date' ) ) );
		if( $old_exceptions ) {
			$exceptions_dates = array();
			foreach( $old_exceptions as $old_exception ) { $exceptions_dates[] = $old_exception[ 'exception_value' ]; }
			$dates_to_insert = array_values( array_diff( $new_exceptions, $exceptions_dates ) );
			$dates_to_delete = array_values( array_diff( $exceptions_dates, $new_exceptions ) );
		}
	}
	if( ! $dates_to_insert && ! $dates_to_delete ) { return 0; }
	
	$updated_nb = 0;

	// Insert new exceptions
	$inserted = $dates_to_insert ? bookacti_insert_exceptions( $event_id, $dates_to_insert ) : 0;
	if( $inserted && is_numeric( $inserted ) ) {
		// Delete the events on exceptions from groups of events
		bookacti_delete_events_on_dates_from_group( $event_id, $dates_to_insert );
		$updated_nb += $inserted;
	}

	// Delete old exceptions
	$deleted = 0;
	if( $delete_old && $dates_to_delete ) {
		$deleted = bookacti_remove_exceptions( $event_id, $dates_to_delete );
		if( $deleted && is_numeric( $deleted ) ) { $updated_nb += $deleted; }
	}

	if( $inserted === false || $deleted === false ) { return false; }

	return $updated_nb;
}


/**
 * Display a promo area of Prices and Credits add-on
 * @version 1.7.10
 * @param string $type
 */
function bookacti_promo_for_bapap_addon( $type = 'event' ) {
	$is_plugin_active = bookacti_is_plugin_active( 'ba-prices-and-credits/ba-prices-and-credits.php' );
	$license_status = get_option( 'bapap_license_status' );

	// If the plugin is activated but the license is not active yet
	if( $is_plugin_active && ( empty( $license_status ) || $license_status !== 'valid' ) ) {
		?>
		<div class='bookacti-addon-promo' >
			<p>
			<?php 
				/* translators: %s = add-on name */
				echo sprintf( __( 'Thank you for purchasing %s add-on!', 'booking-activities' ), 
							 '<strong>Prices and Credits</strong>' ); 
			?>
			</p><p>
				<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", 'booking-activities' ); ?>
			</p><p>
				<strong>
					<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-prices-and-credits-add-on/prerequisite-installation-license-activation-of-prices-and-credits-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-<?php echo $type; ?>' target='_blank' >
						<?php 
						/* translators: %s = add-on name */
							echo sprintf( __( 'How to activate %s license?', 'booking-activities' ), 'Prices and Credits' ); 
						?>
					</a>
				</strong>
			</p>
		</div>
		<?php
	}

	else if( empty( $license_status ) || $license_status !== 'valid' ) {
		?>
		<div class='bookacti-addon-promo' >
			<?php 
			$addon_link = '<a href="https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=encart-promo-' . $type . '" target="_blank" >Prices and Credits</a>';
			$message = '';
			$event_name = '';
			if( $type === 'group-of-events' ) {
				/* translators: %s is the placeholder for Prices and Credits add-on link */
				$message = esc_html( __( 'Set a price or a promotion in cash or in credits on your groups of events with %s add-on !', 'booking-activities' ) );
				$event_name = __( 'My grouped event', 'booking-activities' );
			} else {
				/* translators: %s is the placeholder for Prices and Credits add-on link */
				$message = esc_html( __( 'Set a price or a promotion in cash or in credits on your events with %s add-on !', 'booking-activities' ) );
				$event_name = __( 'My event', 'booking-activities' );
			}
			echo sprintf( $message, $addon_link );
			$price_div_style = 'display: block; width: fit-content; white-space: nowrap; margin: 4px auto; padding: 5px; font-weight: bolder; font-size: 1.2em; border: 1px solid #fff; -webkit-border-radius: 3px;  border-radius: 3px;  background-color: rgba(0,0,0,0.3); color: #fff;';
			?>
			<div class='bookacti-promo-events-examples'>
				<a class="fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event" >
					<div class="fc-content">
						<div class="fc-time" data-start="7:00" data-full="7:00 AM - 8:30 AM">
							<span>7:00 - 8:30</span>
						</div>
						<div class="fc-title"><?php echo $event_name; ?></div>
					</div>
					<div class="fc-bg"></div>
					<div class="bookacti-availability-container">
						<span class="bookacti-available-places bookacti-not-booked ">
							<span class="bookacti-available-places-number">50</span>
							<span class="bookacti-available-places-unit-name"> </span>
							<span class="bookacti-available-places-avail-particle"> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ); ?></span>
						</span>
					</div>
					<div class="bookacti-price-container" style="<?php echo $price_div_style; ?>">
						<span class="bookacti-price bookacti-promo">$30</span>
					</div>
				</a>
				<a class="fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event" >
					<div class="fc-content">
						<div class="fc-time" data-start="7:00" data-full="7:00 AM - 8:30 AM">
							<span>7:00 - 8:30</span>
						</div>
						<div class="fc-title"><?php echo $event_name; ?></div>
					</div>
					<div class="fc-bg"></div>
					<div class="bookacti-availability-container">
						<span class="bookacti-available-places bookacti-not-booked ">
							<span class="bookacti-available-places-number">50</span>
							<span class="bookacti-available-places-unit-name"> </span>
							<span class="bookacti-available-places-avail-particle"> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ); ?></span>
						</span>
					</div>
					<div class="bookacti-price-container" style="<?php echo $price_div_style; ?>">
						<span class="bookacti-price bookacti-promo">- 20%</span>
					</div>
				</a>
				<a class="fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event" >
					<div class="fc-content">
						<div class="fc-time" data-start="7:00" data-full="7:00 AM - 8:30 AM">
							<span>7:00 - 8:30</span>
						</div>
						<div class="fc-title"><?php echo $event_name; ?></div>
					</div>
					<div class="fc-bg"></div>
					<div class="bookacti-availability-container">
						<span class="bookacti-available-places bookacti-not-booked ">
							<span class="bookacti-available-places-number">50</span>
							<span class="bookacti-available-places-unit-name"> </span>
							<span class="bookacti-available-places-avail-particle"> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', 'booking-activities' ); ?></span>
						</span>
					</div>
					<div class="bookacti-price-container" style="<?php echo $price_div_style; ?>">
						<span class="bookacti-price bookacti-promo">
							<?php 
							$amount = 12;
							/* translators: %d is an integer (an amount of credits) */
							echo sprintf( _n( '%d credit', '%d credits', $amount ), $amount ); 
							?>
						</span>
					</div>
				</a>
			</div>
			<div><a href='https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=encart-promo-<?php echo $type; ?>' class='button' target='_blank' ><?php esc_html_e( 'Learn more', 'booking-activities' ); ?></a></div>
		</div>
		<?php
	}
}




// GROUP OF EVENTS

/**
 * Check if a group category exists
 * 
 * @since 1.1.0
 * 
 * @param int $category_id
 * @param int $template_id
 * @return boolean
 */
function bookacti_group_category_exists( $category_id, $template_id = null ) {
	if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
		return false;
	}

	$available_category_ids = bookacti_get_group_category_ids_by_template( $template_id );
	foreach( $available_category_ids as $available_category_id ) {
		if( intval( $category_id ) === intval( $available_category_id ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Insert a new group of events
 * 
 * @since 1.1.0
 * 
 * @param array $events
 * @param int $category_id
 * @param string $group_title
 * @param array $group_meta
 * @return boolean|int
 */
function bookacti_create_group_of_events( $events, $category_id, $group_title = '', $group_meta = array() ) {
	if( ! is_array( $events ) || empty( $events ) || empty( $category_id ) ) {
		return false;
	}

	// First insert the group
	$group_id = bookacti_insert_group_of_events( $category_id, $group_title, $group_meta );

	if( empty( $group_id ) ) {
		return false;
	}

	// Then, insert the events in the group
	$inserted = bookacti_insert_events_into_group( $events, $group_id );

	if( empty( $inserted ) && $inserted !== 0 ) {
		return false;
	}

	return $group_id;
}


/**
 * Edit a group of events
 * 
 * @since 1.1.0
 * 
 * @param int $group_id
 * @param int $category_id
 * @param string $group_title
 * @param array $events
 * @param array $group_meta
 * @return boolean|int
 */
function bookacti_edit_group_of_events( $group_id, $category_id, $group_title = '', $events = array(), $group_meta = array() ) {
	if( empty( $group_id ) || ! is_array( $events ) || empty( $events ) || empty( $category_id ) ) {
		return false;
	}

	$updated1 = $updated2 = $updated3 = 0;

	// First update the group
	$updated1 = bookacti_update_group_of_events( $group_id, $category_id, $group_title );

	if( $updated1 === false ) {
		return 'error_update_group_of_events_data';
	}

	// Then update group of events metadata
	if( ! empty( $group_meta ) ) {
		$updated2 = bookacti_update_metadata( 'group_of_events', $group_id, $group_meta );
	}

	if( $updated2 === false ) {
		return 'error_update_group_metadata';
	}

	// Fially, update events of the group
	$updated3 = bookacti_update_events_of_group( $events, $group_id );

	if( $updated3 === false ) {
		return 'error_update_events_of_group';
	}

	// Return the number of row affected
	$updated = intval( $updated1 ) + intval( $updated2 ) + intval( $updated3 );

	return $updated;
}


/**
 * Update events of a group
 * 
 * @since 1.1.0
 * 
 * @global wpdb $wpdb
 * @param array $new_events
 * @param int $group_id
 * @return int|boolean
 */
function bookacti_update_events_of_group( $new_events, $group_id ) {

	$group_id = intval( $group_id );
	if( ! is_array( $new_events ) || empty( $new_events ) || empty( $group_id ) ) {
		return false;
	}

	// Get events currently in the group
	$current_events = bookacti_get_group_events( $group_id );

	// Determine what events are to be added or removed
	$to_insert = $new_events;
	$to_delete = $current_events;
	foreach( $new_events as $i => $new_event ) {
		foreach( $current_events as $j => $current_event ) {
			$current_event = (object) $current_event;
			if( $current_event->id		== $new_event->id 
			&&  $current_event->start	== $new_event->start 
			&&  $current_event->end		== $new_event->end ) {
				// If the event already exists, remove it from both arrays
				unset( $to_insert[ $i ] );
				unset( $to_delete[ $j ] );
				break;
			}
		}
	}

	// Now $new_events contains only events to add
	// and $current_events contains events to remove
	$deleted = $inserted = 0;

	// Delete old events
	if( ! empty( $to_delete ) ) {
		$deleted = bookacti_delete_events_from_group( $to_delete, $group_id );
	}

	// Insert new events
	if( ! empty( $to_insert ) ) {
		$inserted = bookacti_insert_events_into_group( $to_insert, $group_id );
	}

	if( $deleted === false && $inserted = false ) {
		return false;
	}

	$updated = intval( $deleted ) + intval( $inserted );

	return $updated;
}


/**
 * Retrieve template groups of events list
 * @since 1.1.0
 * @version 1.8.0
 * @param int $template_id
 * @return string|boolean
 */
function bookacti_get_template_groups_of_events_list( $template_id ) {

	if( ! $template_id ) { return false; }

	$current_user_can_edit_template	= current_user_can( 'bookacti_edit_templates' );

	$list =	"";

	// Retrieve groups by categories
	$categories	= bookacti_get_group_categories( $template_id );
	$groups		= bookacti_get_groups_of_events( array( 'templates' => array( $template_id ) ) );
	foreach( $categories as $category ) {

		$category_title			= $category[ 'title' ];
		$category_short_title	= strlen( $category_title ) > 16 ? substr( $category_title, 0, 16 ) . '&#8230;' : $category_title;

		$list	.= "<div class='bookacti-group-category' data-group-category-id='" . $category[ 'id' ] . "' data-show-groups='0' data-visible='1' >
						<div class='bookacti-group-category-title' title='" . $category_title . "' >
							<span>
								" . $category_short_title . "
							</span>
						</div>";

		if( $current_user_can_edit_template ) {
			$list	.= "<div class='bookacti-update-group-category dashicons dashicons-admin-generic' ></div>";
		}

		$list	.= 	   "<div class='bookacti-groups-of-events-editor-list bookacti-custom-scrollbar' >";

		foreach( $groups as $group_id => $group ) {
			if( $group[ 'category_id' ] === $category[ 'id' ] ) {
				$group_title = strip_tags( $group[ 'title' ] );

				$list	.=	   "<div class='bookacti-group-of-events' data-group-id='" . $group_id . "' >
									<div class='bookacti-group-of-events-title' title='" . $group_title . "' >
										" . $group_title . " 
									</div>";
				if( $current_user_can_edit_template ) {
					$list	.=	   "<div class='bookacti-update-group-of-events dashicons dashicons-admin-generic' ></div>";
				}
				$list	.=	   "</div>";
			}
		}

		$list	.=	   "</div>
					</div>";
	}

	return $list;
}