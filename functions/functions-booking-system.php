<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/***** BOOKING SYSTEM *****/
// Display calendar (or other booking method)
function bookacti_display_booking_system( $templates, $activities, $booking_method = 'calendar', $id = null, $classes = '', $echo = true ) {
	
	if( is_null ( $id ) || empty( $id ) ) { 
		$id = rand();
	}
	
	if( $booking_method === 'site' ) {
		$booking_method = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
	}
	
	// Check if desired booking method is registered
	$available_booking_methods = bookacti_get_available_booking_methods();
	if( ! in_array( $booking_method, array_keys ( $available_booking_methods ) ) ) {
		$booking_method = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
		if( ! in_array( $booking_method, array_keys ( $available_booking_methods ) ) ) {
			$booking_method = 'calendar';
		}
	}
	
	if( ! $echo ) {
		ob_start();
	}
	
	do_action( 'bookacti_before_booking_form', $templates, $activities, $booking_method, $id, $classes );
	
?>
	<div class='bookacti-booking-system-container' id='bookacti-booking-system-container-<?php echo esc_attr( $id ); ?>' data-booking-system-form-id='<?php echo esc_attr( $id ); ?>' >
		<div class='bookacti-booking-system-inputs'>
			<input type='hidden' name='bookacti_event_id'		value='' />
			<input type='hidden' name='bookacti_event_start'	value='' />
			<input type='hidden' name='bookacti_event_end'		value='' />
			<?php do_action( 'bookacti_booking_system_inputs', $templates, $activities, $booking_method, $id, $classes ); ?>
		</div>
		
		<?php do_action( 'bookacti_before_booking_system_title', $templates, $activities, $booking_method, $id, $classes ); ?>
		
		<div class='bookacti-booking-system-global-title' >
			<?php echo apply_filters( 'bookacti_booking_system_title', '', $templates, $activities, $booking_method, $id, $classes ); ?>
		</div>
		
		<?php do_action( 'bookacti_before_booking_system', $templates, $activities, $booking_method, $id, $classes ); ?>
		
		<div class='bookacti-booking-system <?php echo esc_attr( $classes ); ?>' 
			 id='bookacti-booking-system-<?php echo esc_attr( $id ); ?>'
			 data-booking-method=		'<?php echo esc_attr( $booking_method ); ?>'
			 data-booking-system-id=	'<?php echo esc_attr( $id ); ?>'
			 data-templates=			'<?php echo esc_attr( implode( ',', $templates ) ); ?>'  
			 data-activities=			'<?php echo esc_attr( implode( ',', $activities ) ); ?>' 
			 data-init-booking-method=	'<?php echo esc_attr( $booking_method ); ?>'
			 data-init-templates=		'<?php echo esc_attr( implode( ',', $templates ) ); ?>'
			 data-init-activities=		'<?php echo esc_attr( implode( ',', $activities ) ); ?>' 
			 data-auto-load=			'<?php echo apply_filters( 'bookacti_booking_system_auto_load', esc_attr( 1 ), $templates, $activities, $booking_method, $id, $classes ); ?>'
			 <?php do_action( 'bookacti_booking_system_attributes', $templates, $activities, $booking_method, $id, $classes ); ?> >

		</div>
		
		<?php do_action( 'bookacti_after_booking_system', $templates, $activities, $booking_method, $id, $classes ); ?>
		
		<div class='bookacti-date-picked' >
			<div class='bookacti-date-picked-title' >
				<?php echo apply_filters( 'bookacti_date_picked_title', esc_html__( 'Selected schedule:', BOOKACTI_PLUGIN_NAME ), $templates, $activities, $booking_method, $id, $classes ); ?>
			</div>
			<div class='bookacti-date-picked-summary' >
				<?php do_action( 'bookacti_before_date_picked_summary', $templates, $activities, $booking_method, $id, $classes ); ?>
				<span class='bookacti-date-picked-activity' ></span>
				<span class='bookacti-date-picked-from' ></span>
				<span class='bookacti-date-picked-separator' ></span>
				<span class='bookacti-date-picked-to' ></span>
				<?php do_action( 'bookacti_after_date_picked_summary', $templates, $activities, $booking_method, $id, $classes ); ?>
			</div>
		</div>
		
		<?php do_action( 'bookacti_after_date_picked', $templates, $activities, $booking_method, $id, $classes ); ?>
		
		<div class='bookacti-notices' >
			<?php do_action( 'bookacti_booking_system_errors', $templates, $activities, $booking_method, $id, $classes ); ?>
		</div>
		
		<?php do_action( 'bookacti_after_booking_system_errors', $templates, $activities, $booking_method, $id, $classes ); ?>
	</div>
<?php

	do_action( 'bookacti_after_booking_form', $templates, $activities, $booking_method, $id, $classes );
	
	if( ! $echo ) {
		return ob_get_clean();
	}
}


// Retrieve Calendar booking system HTML to include in the booking system
function bookacti_retrieve_calendar_elements( $calendar_id ) {
	
	$default_calendar_title	= esc_html__( 'Pick a schedule on the calendar:', BOOKACTI_PLUGIN_NAME );
	$calendar_title			= apply_filters( 'bookacti_calendar_title', $default_calendar_title, $calendar_id );
	
	$before_calendar_title	= apply_filters( 'bookacti_before_calendar_title', '', $calendar_id );
	$before_calendar		= apply_filters( 'bookacti_before_calendar', '', $calendar_id );
	$after_calendar			= apply_filters( 'bookacti_after_calendar', '', $calendar_id );
	
	return
	
	$before_calendar_title
			
	. "<div class='bookacti-calendar-title bookacti-booking-system-title' >"
	.	$calendar_title
	. "</div>"
	
	. $before_calendar
	
	. "<div class='bookacti-calendar' id='bookacti-calendar-" . esc_attr( $calendar_id ) . "' ></div>"
			
	. $after_calendar;
}


// Check booking system attributes and format them to be correct
function bookacti_format_booking_system_attributes( $atts ) {
	
	// format templates and activities into a comma separated int list
	$atts[ 'calendars' ]	= preg_replace( array(
		'/[^\d,]/',    // Matches anything that's not a comma or number.
		'/(?<=,),+/',  // Matches consecutive commas.
		'/^,+/',       // Matches leading commas.
		'/,+$/'        // Matches trailing commas.
	), '', 	$atts[ 'calendars' ]  );
	$atts[ 'activities' ]	= preg_replace( array(
		'/[^\d,]/',    // Matches anything that's not a comma or number.
		'/(?<=,),+/',  // Matches consecutive commas.
		'/^,+/',       // Matches leading commas.
		'/,+$/'        // Matches trailing commas.
	), '', 	$atts[ 'activities' ]  );
	
	// format templates and activities comma separated list into array
	// and remove duplicate values
	$atts[ 'calendars' ]	= array_unique( explode( ',', $atts[ 'calendars' ] ) );
	$atts[ 'activities' ]	= array_unique( explode( ',', $atts[ 'activities' ] ) );
	
	// Check if desired templates exist
	$available_templates = bookacti_fetch_templates( true );
	foreach( $atts[ 'calendars' ] as $i => $template_id ) {
		$is_existing = false;
		foreach( $available_templates as $available_template ) {
			if( $available_template->id == intval( $template_id ) ) {
				$is_existing = true;
			}
		}
		if( ! $is_existing ) {
			unset( $atts[ 'calendars' ][ $i ] );
		}
	}
	
	// Check if desired activities exist
	if( ! empty( $atts[ 'calendars' ] ) ) {
		$available_activities = bookacti_get_activities_by_template_ids( $atts[ 'calendars' ] );
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
				}
			}
			if( ! $is_existing ) {
				unset( $atts[ 'activities' ][ $i ] );
			}
		}
	}
	
	// Check if desired booking method is registered
	$available_booking_methods = bookacti_get_available_booking_methods();
	$atts[ 'method' ] = esc_attr( $atts[ 'method' ] );
	if( ! in_array( $atts[ 'method' ], array_keys ( $available_booking_methods ) ) ) {
		$atts[ 'method' ] = 'calendar';
	}
	
	
	// Give a random id if not supplied
	$atts[ 'id' ]		= empty( $atts[ 'id' ] )		? esc_attr( rand() ) : esc_attr( $atts[ 'id' ] );
	
	// Format classes
	$atts[ 'classes' ]	= empty( $atts[ 'classes' ] )	? esc_attr( $atts[ 'classes' ] ) : '';
	
	// Sanitize redirect URL
	if( isset( $atts[ 'url' ] ) ) {
		$atts[ 'url' ] = esc_url( $atts[ 'url' ] );
	}
	
	// Sanitize submit button label
	if( isset( $atts[ 'button' ] ) ) {
		$atts[ 'button' ] = esc_html( sanitize_text_field( $atts[ 'button' ] ) );
	}
	
	
	return apply_filters( 'bookacti_formatted_booking_system_attributes', $atts );
}


/**
 * Validate booking form (verify the info of the selected schedule before booking it)
 *
 * @since	1.0.0
 * @version	1.0.6
 * @param	int		$event_id		ID of the event to check
 * @param	string	$event_start	Start datetime of the event to check (format 2017-12-31T23:59:59)
 * @param	string	$event_end		End datetime of the event to check (format 2017-12-31T23:59:59)
 * @param	int		$quantity		Desired number of bookings
 * @return	array
 */
function bookacti_validate_booking_form( $event_id, $event_start, $event_end, $quantity ) {
	
	$availability		= bookacti_get_event_availability( $event_id, $event_start, $event_end );
	$event_exists		= bookacti_is_existing_event( $event_id, $event_start, $event_end );
	$is_in_range		= bookacti_is_event_in_its_template_range( $event_id, $event_start, $event_end );
	
	//Init boolean test variables
	$is_event				= false;
	$is_corrupted			= false;
	$is_qty_sup_to_avail	= false;
	$is_qty_sup_to_0		= false;
	$can_book				= false;

	//Make the tests and change the booleans
	if( $event_id !== '' && $event_start !== '' && $event_end !== '' )				{ $is_event = true; }
	if( intval( $quantity ) > 0 )													{ $is_qty_sup_to_0 = true; }
	if( $is_qty_sup_to_0 && ( intval( $availability ) - intval( $quantity ) < 0 ) ) { $is_qty_sup_to_avail = true; }
	if( ( $is_event && ! $event_exists ) || ! $is_in_range )						{ $is_corrupted = true; }

	if( $is_event && $is_qty_sup_to_0 && ! $is_qty_sup_to_avail && ! $is_corrupted ) { $can_book = true; }

	if( $can_book ) {
		$validated['status'] = 'success';
	} else {
		if( $is_corrupted ) {
			$validated['status'] = 'corrupted';
			$validated['message'] = __( 'The schedule you selected is corrupted, please reselect a schedule and try again.', BOOKACTI_PLUGIN_NAME );
		} else if( ! $is_event ) {
			$validated['status'] = 'no_event_selected';
			$validated['message'] = __( 'You haven\'t selected any schedule. Please select a schedule first.', BOOKACTI_PLUGIN_NAME );
		} else if( ! $is_qty_sup_to_0 ) {
			$validated['status'] = 'qty_inf_to_0';
			$validated['message'] = __( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', BOOKACTI_PLUGIN_NAME );
		} else if( $is_qty_sup_to_avail ) {
			$validated['status'] = 'qty_sup_to_avail';
			$validated['availability'] = $availability;
			$validated['message'] = sprintf( __( 'You want to make %1$s bookings but only %2$s are available on this schedule. Please choose another schedule.', BOOKACTI_PLUGIN_NAME ), $quantity, $availability );
		} else {
			$validated['status'] = 'failed';
			$validated['message'] = __( 'An error occurred, please try again.', BOOKACTI_PLUGIN_NAME );
		}
	}

	return apply_filters( 'bookacti_validate_booking_form', $validated, $event_id, $event_start, $event_end, $quantity );
}


//Convert minutes to days, hours and minutes
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
 * @since	1.0.0
 * @version	1.0.6
 * @param	object		$event					Event data
 * @param	array		$shared_data				Event data shared by every occurences of the event
 * @param	DateTime	$user_datetime_object	End datetime of the event to check (format 2017-12-31T23:59:59)
 * @param	bool			$fetch_past_events		Whether to create occurences before user datetime
 * @param	string		$context				(frontend, editor, booking_page) Determine which occurence will be generated according to the context.
 * @return	array
 */
function bookacti_create_repeated_events( $event, $shared_data = array(), $user_datetime_object = null, $fetch_past_events = false, $context = 'frontend' ) {
	if( is_null( $user_datetime_object ) ) { $user_datetime_object = new DateTime(); }
    if( empty( $shared_data ) ) { 
		$shared_data = array(
			'id'				=> $event->event_id,
			'template_id'		=> $event->template_id,
			'title'				=> apply_filters( 'bookacti_translate_text', stripslashes( $event->title ) ),
			'multilingual_title'=> stripslashes( $event->title ),
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
		
        //FILTER EXCEPTIONS HERE ONLY FOR READ ONLY PLANNINGS, FOR TEMPLATES DO IT ON EVENT RENDER
        $is_exception	= bookacti_is_repeat_exception( $event->event_id, date( 'Y-m-d', $event_start->format( 'U' ) ) );
        $has_started	= $event_start < $user_datetime_object;
        $has_ended		= $event_end < $user_datetime_object;
		$is_in_range	= bookacti_is_event_in_its_template_range( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') );
		$is_booked		= bookacti_get_number_of_bookings( $event->event_id, $event_start->format('Y-m-d H:i:s'), $event_end->format('Y-m-d H:i:s') ) > 0;
		
        if( $context === 'editor' /* Show all events on templates */ 
			|| $context === 'booking_page' && $is_exception == 0 && ( $is_in_range || $is_booked ) /* If we also fetch past events, show all events but those wich are on an exception */ 
			|| $fetch_past_events && $is_exception == 0 && $is_in_range /* If we also fetch past events, show all events but those wich are on an exception */ 
			|| ( $context !== 'editor' && $is_exception == 0 && $is_in_range /* Don't show exception on frontend */
				&& ( ! $has_started /* Don't show started events on frontend */
					|| ( $started_events_bookable && $has_started && ! $has_ended ) ) /* Show in progress events on frontend if user decides so */
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


//Determine the number of day or month to add according to the repetition frequence
function bookacti_units_to_add_to_repeat_event( $event ) {
    $to_add = ['number' => 0, 'unit' => 'days'];
    
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
