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
				'subject'		=> __( 'New booking!', BOOKACTI_PLUGIN_NAME ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is made */
				'message'		=> __( '<p>You have {booking_total_qty} new booking(s) from {user_firstname} {user_lastname} ({user_email})!</p>
										<p>{booking_list}</p>
										<p>Booking status: <strong>{booking_status}</strong>.</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the administrator when a new booking is registered.', BOOKACTI_PLUGIN_NAME ) 
			),
		'admin_cancelled_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has cancelled a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking cancelled', BOOKACTI_PLUGIN_NAME ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is cancelled */
				'message'		=> __( '<p>A customer has cancelled a booking.</p>
										<p>{booking_list}</p>
										<p>Contact him: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the administrator when a customer cancel a booking.', BOOKACTI_PLUGIN_NAME ) 
			),
		'admin_rescheduled_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Customer has rescheduled a booking', BOOKACTI_PLUGIN_NAME ),
				'to'			=> array( get_bloginfo( 'admin_email' ) ),
				'subject'		=> __( 'Booking rescheduled', BOOKACTI_PLUGIN_NAME ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is rescheduled */
				'message'		=> __( '<p>A customer has rescheduled a booking.</p>
										<p>Old booking: {booking_old_start} - {booking_old_end}</p>
										<p>New booking: {booking_list}</p>
										<p>Contact him: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the administrator when a customer reschedule a booking.', BOOKACTI_PLUGIN_NAME )  
			),
		
		'customer_pending_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Pending"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Your booking is pending', BOOKACTI_PLUGIN_NAME ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an customer receive when a booking is made, but is still Pending */
				'message'		=> __( '<p>Thank you for your booking request {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>Your reservation is <strong>pending</strong>.</p>
										<p>We will process your request and contact you as soon as possible.</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Pending". If you set the "Default booking state" option to "Pending", this email will be sent right after the booking is made.', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_booked_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Booked"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Your booking is complete! Thank you', BOOKACTI_PLUGIN_NAME ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an customer receive when a booking is made and Complete */
				'message'		=> __( '<p>Thank you for your booking {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>We confirm that your reservation is now <strong>complete</strong>.</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Booked". If you set the "Default booking state" option to "Booked", this email will be sent right after the booking is made.', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_cancelled_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Cancelled"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Your booking has been cancelled', BOOKACTI_PLUGIN_NAME ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an customer receive when a booking is cancelled */
				'message'		=> __( '<p>Hello {user_firstname},
										<p>Your booking has been <strong>cancelled</strong>.</p>
										<p>{booking_list}</p>
										<p>If you didn\'t cancelled this reservation or if you think this is an error, please contact us.</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Cancelled".', BOOKACTI_PLUGIN_NAME )  
			),
		'customer_refunded_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Refunded"', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Your booking has been refunded', BOOKACTI_PLUGIN_NAME ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an customer receive when he is reimbursed for a booking */
				'message'		=> __( '<p>Hello {user_firstname},
										<p>Your booking has been <strong>refunded</strong>.</p>
										<p>{booking_list}</p>
										<p>We are sorry for the inconvenience and hope to see you soon.</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings becomes "Refunded".', BOOKACTI_PLUGIN_NAME ) 
			),
		'customer_rescheduled_booking' => 
			array(
				'active'		=> 1,
				'title'			=> __( 'Booking is rescheduled', BOOKACTI_PLUGIN_NAME ),
				'subject'		=> __( 'Your booking has been rescheduled', BOOKACTI_PLUGIN_NAME ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
				/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an customer receive when a booking is rescheduled */
				'message'		=> __( '<p>Hello {user_firstname},
										<p>Your booking has been <strong>rescheduled</strong> from {booking_old_start} to:</p>
										<p>{booking_list}</p>
										<p>If you didn\'t rescheduled this reservation or if you think this is an error, please contact us.</p>', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'This email is sent to the customer when one of his bookings is rescheduled.', BOOKACTI_PLUGIN_NAME )  
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
function bookacti_get_email_settings( $email_id, $raw = true ) {

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

	return apply_filters( 'bookacti_email_settings', $email_settings, $email_id, $raw );
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

			$sanitized_textarea = wp_kses_post( stripslashes( $email[ $key ] ) );
			$email[ $key ] = $sanitized_textarea ? $sanitized_textarea : $default_value;
		} 
	}


	return apply_filters( 'bookacti_email_sanitized_settings', $email, $email_id );
}


/**
 * Get notifications tags
 * 
 * @since 1.2.0
 * @param string $notification_id Optional.
 */
function bookacti_get_notifications_tags( $notification_id = '' ) {
	
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
	
	if( $notification_id === 'admin_rescheduled_booking' || $notification_id === 'customer_rescheduled_booking' ) {
		$tags[ '{booking_old_start}' ]	= __( 'Booking start date and time before reschedule. Displayed in a user-friendly format.', BOOKACTI_PLUGIN_NAME );
		$tags[ '{booking_old_end}' ]	= __( 'Booking end date and time before reschedule. Displayed in a user-friendly format.', BOOKACTI_PLUGIN_NAME );
	}
	
	return apply_filters( 'bookacti_notifications_tags', $tags, $notification_id );
}


/**
 * Get notifications tags and values corresponding to given booking
 * 
 * @since 1.2.0
 * @param int $booking_id
 * @param string $booking_type 'group' or 'single'
 * @param string $notification_id Optional.
 * @return array
 */
function bookacti_get_notifications_tags_values( $booking_id, $booking_type, $notification_id ) {
	
	$booking_data = array();
	
	$booking = $booking_type === 'group' ? bookacti_get_booking_group_by_id( $booking_id ) : bookacti_get_booking_by_id( $booking_id );
	
	if( $booking_type === 'group' ) {
		$bookings			= bookacti_get_bookings_by_booking_group_id( $booking_id );
		$group_of_events	= bookacti_get_group_of_events( $booking->event_group_id );
		
		$booking_data[ '{booking_quantity}' ]	= bookacti_get_booking_group_quantity( $booking_id );
		$booking_data[ '{booking_total_qty}' ]	= 0;
		foreach( $bookings as $grouped_booking ) { $booking_data[ '{booking_total_qty}' ] += intval( $grouped_booking->quantity ); }
		$booking_data[ '{booking_title}' ]		= $group_of_events ? $group_of_events->title : '';
		$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&event_group_id=' . $group_of_events->id );
		
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
	
	$default_tags = array_keys( bookacti_get_notifications_tags( $notification_id ) );
	
	// Make sure the array contains all tags 
	$tags = array();
	foreach( $default_tags as $default_tag ) {
		$tags[ $default_tag ] = isset( $booking_data[ $default_tag ] ) ? $booking_data[ $default_tag ] : '';
	}
	
	return apply_filters( 'bookacti_notifications_tags_values', $tags, $booking_id, $booking_type, $notification_id );
}


/**
 * Send an email according to notifications settings
 * 
 * @since 1.2.0
 * @param string $notification_id Must exists in "bookacti_emails_default_settings"
 * @param int $booking_id
 * @param string $booking_type "single" or "group"
 * @param array $args Replace or add email settings and tags
 * @param boolean $async Whether to send the email asynchronously. 
 * @return boolean
 */
function bookacti_send_email( $notification_id, $booking_id, $booking_type, $args = array(), $async = true ) {
	
	// Send emails asynchronously
	$allow_async = apply_filters( 'bookacti_email_allow_async', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async_email' ) );
	if( $allow_async && $async ) {
		wp_schedule_single_event( time(), 'bookacti_send_async_email', array( $notification_id, $booking_id, $booking_type, $args, false ) );
		return;
	}
	
	// Get email settings
	$email = bookacti_get_email_settings( $notification_id );
	
	// Replace or add email settings
	if( $args && $args[ 'email' ] ) {
		$email = array_merge( $email, $args[ 'email' ] );
	}
	
	if( ! $email || ! $email[ 'active' ] ) { return false; }
	
	$to = $email[ 'to' ];
	
	// Change params according to recipients
	if( substr( $notification_id, 0, 8 ) === 'customer' ) {
		
		$user_id	= $booking_type === 'group' ? bookacti_get_booking_group_owner( $booking_id ) : bookacti_get_booking_owner( $booking_id );
		$user_data	= get_userdata( $user_id );
		$to			= $user_data->user_email;
		
		if( ! $to ) { return false; }
		
		// Temporarilly switch locale to user's
		$locale = bookacti_get_user_locale( $user_id );
		
	} else {
		$locale = bookacti_get_site_locale();
	}
	
	$locale = apply_filters( 'bookacti_email_locale', $locale, $notification_id, $booking_id, $booking_type );
	
	// Temporarilly switch locale to site or user default's
	bookacti_switch_locale( $locale );
	
	$subject	= $email[ 'subject' ];
	
	// Replace tags in message and replace linebreaks with html tags
	$tags		= bookacti_get_notifications_tags_values( $booking_id, $booking_type, $notification_id );
	
	// Replace or add tags values
	if( $args && $args[ 'tags' ] ) {
		$tags = array_merge( $tags, $args[ 'tags' ] );
	}
	
	$message	= wpautop( str_replace( array_keys( $tags ), array_values( $tags ), $email[ 'message' ] ) );
	
	$from_name	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_name' );
	$from_email	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' );
	$headers	= array( 'Content-Type: text/html; charset=UTF-8;', 'From:' . $from_name . ' <' . $from_email . '>' );
	
	$email_data = apply_filters( 'bookacti_email_data', array(
		'headers'	=> $headers,
		'to'		=> $to,
		'subject'	=> apply_filters( 'bookacti_translate_text', $subject, $locale ),
		'message'	=> apply_filters( 'bookacti_translate_text', $message, $locale )
	), $notification_id, $booking_id, $booking_type );
	
	$sent = wp_mail( $email_data[ 'to' ], $email_data[ 'subject' ], $email_data[ 'message' ], $email_data[ 'headers' ] );
	
	// Switch locale back to normal
	bookacti_restore_locale();
	
	do_action( 'bookacti_email_sent', $sent, $email_data, $notification_id, $booking_id, $booking_type );
}

// Hook the asynchronous call and send the email
add_action( 'bookacti_send_async_email', 'bookacti_send_email', 10, 5 );