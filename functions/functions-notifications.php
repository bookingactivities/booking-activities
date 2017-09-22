<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
	
/**
 * Array of configurable emails
 * 
 * @since 1.2.0
 * @return array
 */
function bookacti_get_emails_default_settings() {
	$emails = array( 
		'admin_new_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has made a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'New booking', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'A customer has made a new booking', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to administrator when a new booking is registered.', BOOKACTI_PLUGIN_NAME ) 
			),
		'admin_cancel_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has cancelled a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking cancelled', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'A customer has cancelled a booking', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to administrator when a customer cancel a booking.', BOOKACTI_PLUGIN_NAME ) 
			),
		'admin_reschedule_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has rescheduled a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking rescheduled', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'A customer has rescheduled a booking', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to administrator when a customer reschedule a booking.', BOOKACTI_PLUGIN_NAME )  
			),
		'admin_refund_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has refunded a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking refunded', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'A customer has refunded a booking', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to administrator when a customer sucessfully refund a booking.', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_pending_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turned to "Pending"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Booking pending', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'Your booking is now pending', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Pending". If you set the "Default booking state" option to "Pending", this email will be sent right after the booking is made.', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_booked_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turned to "Booked"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Booking complete', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'Your booking is now complete', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Booked". If you set the "Default booking state" option to "Booked", this email will be sent right after the booking is made.', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_cancelled_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turned to "Cancelled"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Booking cancelled', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'Your booking is now cancelled', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Cancelled".', BOOKACTI_PLUGIN_NAME )  
			),
		'customer_refunded_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turned to "Refunded"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Booking refunded', BOOKACTI_PLUGIN_NAME ),
				'message'		=> __( 'Your booking is now refunded', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Refunded".', BOOKACTI_PLUGIN_NAME ) 
			),
	);

	return apply_filters( 'bookacti_emails_default_settings', $emails );
}


/**
 * Get email default settings
 * 
 * @since 1.2.0
 * @param string $email_id
 * @return false|array
 */
function bookacti_get_email_default_settings( $email_id ) {

	if( ! $email_id ) { return false; }

	$emails = bookacti_get_emails_default_settings();

	if( ! isset( $emails[ $email_id ] ) ) { return false; }

	return $emails[ $email_id ];
}


/**
 * Get email settings
 * 
 * @since 1.2.0
 * @param string $email_id
 * @return false|array
 */
function bookacti_get_email_settings( $email_id, $raw = false ) {

	if( ! $email_id ) { return false; }

	$emails = bookacti_get_emails_default_settings();

	if( ! isset( $emails[ $email_id ] ) ) { return false; }
	
	$email_settings = array();
	
	// Get raw value from database
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		if( isset( $alloptions[ 'bookacti_notifications_settings_email_' . $email_id ] ) ) {
			$email_settings	= maybe_unserialize( $alloptions[ 'bookacti_notifications_settings_email_' . $email_id ] );
		}
	} 
	
	// Else, get email settings through a normal get_option
	else {
		$email_settings = get_option( 'bookacti_notifications_settings_email_' . $email_id );
	}
	

	// Make sure all values are set
	foreach( $emails[ $email_id ] as $key => $value ) {
		if( ! isset( $email_settings[ $key ] ) ) {
			$email_settings[ $key ] = $value;
		}
	}

	return $email_settings;
}


/**
 * Sanitize email settings
 * 
 * @since 1.2.0
 * @param array $args
 * @param string $email_id Optionnal email id. If set, default value will be picked from the corresponding email.
 * @return array
 */
function bookacti_sanitize_email_settings( $args, $email_id = '' ) {
	if( ! $args ) { return false; }

	$defaults = bookacti_get_email_default_settings( $email_id );
	if( ! $defaults ) {
		$defaults = array(
			'active'	=> 0,
			'title'		=> '',
			'to'		=> array(),
			'subject'	=> '',
			'message'	=> ''
		);
	}

	$email = array();
	foreach( $defaults as $key => $default_value ) {

		$email[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $default_value;

		if( $key === 'active' ) {

			$email[ $key ] = intval( $args[ $key ] ) ? 1 : 0;

		} else if( $key === 'to' ) {

			if( ! is_array( $email[ $key ] ) ) {
				$email[ $key ] = strval( $email[ $key ] );
				$email[ $key ] = strpos( $email[ $key ], ',' ) !== false ? explode( ',', $email[ $key ] ) : array( $email[ $key ] );
			}
			foreach( $email[ $key ] as $to_key => $to_email_address ) {
				$sanitized_email = sanitize_email( $to_email_address );
				if( $sanitized_email ) {
					$email[ $key ][ $to_key ] = $sanitized_email;
				} else {
					unset( $email[ $key ][ $to_key ] );
				}
			}

		} else if( $key === 'title' || $key === 'subject' ) {

			$sanitized_field = sanitize_text_field( stripslashes( $args[ $key ] ) );
			$email[ $key ] = $sanitized_field ? $sanitized_field : $default_value;

		} else if( $key === 'message' ) {

			$sanitized_textarea = wp_kses( stripslashes( $email[ $key ] ) );
			$email[ $key ] = $sanitized_textarea ? $sanitized_textarea : $default_value;
		} 
	}


	return $email;
}

/**
 * Get notifications tags
 */
function bookacti_get_notifications_tags() {
	
	$tags = array( 
		'{booking_id}'			=> __( 'Booking unique ID (integer). Bookings and booking groups have different set of IDs.', BOOKACTI_PLUGIN_NAME ),
		'{booking_title}'		=> __( 'The event / group of events title.', BOOKACTI_PLUGIN_NAME ),
		'{booking_quantity}'	=> __( 'Booking quantity. If bookings of a same group happen to have different quantities, the higher is displayed.', BOOKACTI_PLUGIN_NAME ),
		'{booking_total_qty}'	=> __( 'For booking groups, this is the bookings sum. For single bookings, this is the same as {booking_quantity}.', BOOKACTI_PLUGIN_NAME ),
		'{booking_status}'		=> __( 'Current booking status.', BOOKACTI_PLUGIN_NAME ),
		'{booking_start}'		=> __( 'Booking start date and time displayed in a user-friendly format. Not available for booking groups.', BOOKACTI_PLUGIN_NAME ),
		'{booking_end}'			=> __( 'Booking end date and time displayed in a user-friendly format. Not available for booking groups.', BOOKACTI_PLUGIN_NAME ),
		'{booking_list}'		=> __( 'Booking summary displayed as a booking list. You should use this tag once in every notification to know what booking (group) it is about.', BOOKACTI_PLUGIN_NAME ),
		'{booking_admin_url}'	=> __( 'URL to the booking admin panel. Use this tag only on notifications sent to administrators.', BOOKACTI_PLUGIN_NAME ),
		'{user_firstname}'		=> __( 'The user first name', BOOKACTI_PLUGIN_NAME ),
		'{user_lastname}'		=> __( 'The user last name', BOOKACTI_PLUGIN_NAME ),
		'{user_email}'			=> __( 'The user email address', BOOKACTI_PLUGIN_NAME )
	);
	
	return apply_filters( 'bookacti_notifications_tags', $tags );
}


/**
 * Get notifications tags and values corresponding to given booking
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param string $booking_type 'group' or 'single'
 * @return array
 */
function bookacti_get_notifications_tags_values( $booking_id, $booking_type ) {
	
	$booking_data = array();
	
	$booking = $booking_type === 'group' ? bookacti_get_booking_group_by_id( $booking_id ) : bookacti_get_booking_by_id( $booking_id );
	
	if( $booking_type === 'group' ) {
		$bookings			= bookacti_get_bookings_by_booking_group_id( $booking_id );
		$group_of_events	= bookacti_get_group_of_events( $booking->event_group_id );
		
		$booking_data[ '{booking_quantity}' ]	= bookacti_get_booking_group_quantity( $booking_id );
		$booking_data[ '{booking_total_qty}' ]	= 0;
		foreach( $bookings as $booking ) { $booking_data[ '{booking_total_qty}' ] += intval( $booking->quantity ); }
		$booking_data[ '{booking_title}' ]		= $group_of_events ? $group_of_events->title : '';
		$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&booking_group_id=' . $booking_id );
		
	} else {
		$bookings	= array( $booking );
		$event		= bookacti_get_event_by_id( $booking->event_id );
		
		$booking_data[ '{booking_quantity}' ]	= $booking->quantity;
		$booking_data[ '{booking_total_qty}' ]	= $booking_data[ '{booking_quantity}' ];
		$booking_data[ '{booking_title}' ]		= $event ? $event->title : '';
		$booking_data[ '{booking_start}' ]		= $booking->event_start;
		$booking_data[ '{booking_end}' ]		= $booking->event_end;
		$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end );
	}

	$booking_data[ '{booking_id}' ]				= $booking_id;
	$booking_data[ '{booking_title}' ]			= $booking_data[ 'booking_title' ] ? apply_filters( 'bookacti_translate_text', $booking_data[ 'title' ] ) : '';
	$booking_data[ '{booking_status}' ]			= bookacti_format_booking_state( $booking->state );
	$booking_data[ '{booking_list}' ]			= bookacti_get_formatted_booking_events_list( $bookings, 'show' );
	
	if( $booking->user_id ) { 
		$user = get_user_by( 'id', $booking->user_id );
		if( $user ) { 
			$booking_data[ '{user_firstname}' ]	= $user->first_name;
			$booking_data[ '{user_lastname}' ]	= $user->last_name;
			$booking_data[ '{user_email}' ]		= $user->user_email;
		}
	}
	
	$default_tags = array_keys( bookacti_get_notifications_tags() );
	
	// Make sure the array contains all tags 
	$tags = array();
	foreach( $default_tags as $default_tag ) {
		$tags[ $default_tag ] = isset( $booking_data[ $default_tag ] ) ? $booking_data[ $default_tag ] : '';
	}
	
	return apply_filters( 'bookacti_notifications_tags_values', $tags, $booking_id, $booking_type );
}

