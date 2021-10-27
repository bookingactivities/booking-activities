<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
	
/**
 * Array of configurable notifications
 * @since 1.2.1 (was bookacti_get_emails_default_settings in 1.2.0)
 * @version 1.12.4
 * @return array
 */
function bookacti_get_notifications_default_settings() {
	$admin_email = get_bloginfo( 'admin_email' );
	$blog_name = apply_filters( 'bookacti_translate_text', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	
	$notifications = array( 
		'admin_new_booking' => 
			array(
				'id'		=> 'admin_new_booking',
				'active'	=> 1,
				'title'		=> esc_html__( 'Customer has made a booking', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the administrator when a new booking is registered.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( $admin_email ),
					'subject'	=> esc_html__( 'New booking!', 'booking-activities' ),
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receives when a booking is made */
										__( '<p>You have {booking_total_qty} new booking(s) from {user_firstname} {user_lastname} ({user_email})!</p>
										<p>{booking_list}</p>
										<p>Booking status: <strong>{booking_status}</strong>.</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) ) ) 
				)
			),
		'admin_cancelled_booking' => 
			array(
				'id'		=> 'admin_cancelled_booking',
				'active'	=> 1,
				'title'		=> esc_html__( 'Customer has cancelled a booking', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the administrator when a customer cancels a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( $admin_email ),
					'subject'	=> esc_html__( 'Booking cancelled', 'booking-activities' ),
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receives when a booking is cancelled */
										__( '<p>A customer has cancelled a booking.</p>
										<p>{booking_list}</p>
										<p>Customer info: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) ) ) 
				)
			),
		'admin_rescheduled_booking' => 
			array(
				'id'		=> 'admin_rescheduled_booking',
				'active'	=> 1,
				'title'		=> esc_html__( 'Customer has rescheduled a booking', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the administrator when a customer reschedules a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( $admin_email ),
					'subject'		=> esc_html__( 'Booking rescheduled', 'booking-activities' ),
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receives when a booking is rescheduled */
										__( '<p>A customer has rescheduled a booking.</p>
										<p>Old booking: {booking_old_start} - {booking_old_end}</p>
										<p>New booking: {booking_list}</p>
										<p>Customer info: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) ) ) 
				)
			),
		'admin_refund_requested_booking' => 
			array(
				'id'			=> 'admin_refund_requested_booking',
				'active'		=> 1,
				'title'			=> esc_html__( 'Customer has requested a refund for a booking', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the administrator when a customer submits a refund request for a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( $admin_email ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email subject an administrator receives when a customer submits a refund request for a booking */
					'subject'	=> esc_html__( 'Refund request for booking #{booking_id}', 'booking-activities' ),
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receives when a customer submits a refund request for a booking */
										__( '<h3>{user_firstname} {user_lastname} wants to be refunded for <a href="{booking_admin_url}" target="_blank">booking #{booking_id}</a></h3>', 'booking-activities' )
									.	PHP_EOL
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receives when a customer submits a refund request or process a refund for a booking */
									.	__( '<h4>Booking</h4>
										ID: {booking_id}
										Name: {booking_title}
										Start: {booking_start}
										End: {booking_end}
										Quantity: {booking_quantity}
										Status: {booking_status}
										List:
										{booking_list}
										<h4>User</h4>
										Name: {user_firstname} {user_lastname}
										Email: {user_email}
										Phone: {user_phone}
										<h4>User message</h4>
										{refund_message}
										', 'booking-activities' ) ) ) 
				)
			),
		'admin_refunded_booking' => 
			array(
				'id'			=> 'admin_refunded_booking',
				'active'		=> 1,
				'title'			=> esc_html__( 'Customer has been refunded', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the administrator when a customer is successfully reimbursed for a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( get_bloginfo( 'admin_email' ) ),
					'subject'	=> esc_html__( 'Booking refunded', 'booking-activities' ),
					'message'	=> trim( preg_replace( '/\t+/', '',
										__( '<h3>{user_firstname} {user_lastname} has been refunded for <a href="{booking_admin_url}" target="_blank">booking #{booking_id}</a></h3>', 'booking-activities' )
									.	PHP_EOL
									.	__( '<h4>Booking</h4>
										ID: {booking_id}
										Name: {booking_title}
										Start: {booking_start}
										End: {booking_end}
										Quantity: {booking_quantity}
										Status: {booking_status}
										List:
										{booking_list}
										<h4>User</h4>
										Name: {user_firstname} {user_lastname}
										Email: {user_email}
										Phone: {user_phone}
										<h4>User message</h4>
										{refund_message}
										', 'booking-activities' ) ) ) 
				)
			),
		
		
		'customer_pending_booking' => 
			array(
				'id'		=> 'customer_pending_booking',
				'active'	=> 1,
				'title'		=>	/* translators: %s = a booking status. E.g.: "Pending", "Booked"... */
								sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Pending', 'booking-activities' ) ),
				'description'	=>	/* translators: %s = a booking status. E.g.: "Pending", "Booked"... */
									sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Pending', 'booking-activities' ) ) 
									/* translators: %s = a booking status. E.g.: "Pending", "Booked"... */
								. ' ' . sprintf( esc_html__( 'If you set the "Default booking status" option to "%s", this notification will be sent right after the booking is made.', 'booking-activities' ), esc_html__( 'Pending', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Your booking is pending', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when a booking is made, but is still Pending */
										__( '<p>Thank you for your booking request {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>Your reservation is <strong>pending</strong>.</p>
										<p>We will process your request and contact you as soon as possible.</p>', 'booking-activities' ) ) )
				)
			),
		'customer_booked_booking' => 
			array(
				'id'			=> 'customer_booked_booking',
				'active'		=> 1,
				'title'			=> sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Booked', 'booking-activities' ) ),
				'description'	=> sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Booked', 'booking-activities' ) ) 
								. ' ' . sprintf( esc_html__( 'If you set the "Default booking status" option to "%s", this notification will be sent right after the booking is made.', 'booking-activities' ), esc_html__( 'Booked', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Your booking is complete! Thank you', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when a booking is made and Complete */
										__( '<p>Thank you for your booking {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>We confirm that your reservation is now <strong>complete</strong>.</p>', 'booking-activities' ) ) )
				)
			),
		'customer_delivered_booking' => 
			array(
				'id'			=> 'customer_delivered_booking',
				'active'		=> 1,
				'title'			=> sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Delivered', 'booking-activities' ) ),
				'description'	=> sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Delivered', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Thank you!', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when a booking status becomes "Delivered" */
										__( '<p>Thank you {user_firstname}!</p>
										<p>Your booking is now <strong>complete</strong>.</p>
										<p>{booking_list}</p>
										<p>We hope to see you soon!</p>', 'booking-activities' ) ) )
				)
			),
		'customer_cancelled_booking' => 
			array(
				'id'			=> 'customer_cancelled_booking',
				'active'		=> 1,
				'title'			=> sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Cancelled', 'booking-activities' ) ),
				'description'	=> sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Cancelled', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Your booking has been cancelled', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when a booking is cancelled */
										__( "<p>Hello {user_firstname},</p>
										<p>Your booking has been <strong>cancelled</strong>.</p>
										<p>{booking_list}</p>
										<p>If you haven't cancelled this reservation or if you think this is an error, please contact us.</p>", 'booking-activities' ) ) )
				)
			),
		'customer_refund_requested_booking' => 
			array(
				'id'			=> 'customer_refund_requested_booking',
				'active'		=> 1,
				'title'			=> sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Refund requested', 'booking-activities' ) ),
				'description'	=> sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Refund requested', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'A refund has been requested for your booking', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when he is reimbursed for a booking */
										__( '<p>Hello {user_firstname},</p>
										<p>We have received your <strong>refund request</strong> for your booking.</p>
										<p>{booking_list}</p>
										<blockquote><strong>Your message:</strong> <q>{refund_message}</q></blockquote>
										<p>We will get back to you as soon as possible.</p>
										<p>If you haven\'t requested a refund for this reservation or if you think this is an error, please contact us.</p>', 'booking-activities' ) ) )
				)
			),
		'customer_refunded_booking' => 
			array(
				'id'			=> 'customer_refunded_booking',
				'active'		=> 1,
				'title'			=> sprintf( esc_html__( 'Booking status turns to "%s"', 'booking-activities' ), esc_html__( 'Refunded', 'booking-activities' ) ),
				'description'	=> sprintf( esc_html__( 'This notification is sent to the customer when one of his bookings becomes "%s".', 'booking-activities' ), esc_html__( 'Refunded', 'booking-activities' ) ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Your booking has been refunded', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when he is reimbursed for a booking */
										__( '<p>Hello {user_firstname},</p>
										<p>Your booking has been <strong>refunded</strong>.</p>
										<p>{booking_list}</p>
										<p>We are sorry for the inconvenience and hope to see you soon.</p>', 'booking-activities' ) ) )
				)
			),
		'customer_rescheduled_booking' => 
			array(
				'id'			=> 'customer_rescheduled_booking',
				'active'		=> 1,
				'title'			=> esc_html__( 'Booking is rescheduled', 'booking-activities' ),
				'description'	=> esc_html__( 'This notification is sent to the customer when one of his bookings is rescheduled.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> esc_html__( 'Your booking has been rescheduled', 'booking-activities' ) . ' - ' . $blog_name,
					'message'	=> trim( preg_replace( '/\t+/', '', 
										/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receives when a booking is rescheduled */
										__( "<p>Hello {user_firstname},</p>
										<p>Your booking has been <strong>rescheduled</strong> from {booking_old_start} to:</p>
										<p>{booking_list}</p>
										<p>If you haven't rescheduled this reservation or if you think this is an error, please contact us.</p>", 'booking-activities' ) ) )
				)
			),
	);

	return apply_filters( 'bookacti_notifications_default_settings', $notifications );
}


/**
 * Get notification default settings
 * @since 1.2.1 (was bookacti_get_email_default_settings in 1.2.0)
 * @version 1.8.6
 * @param string $notification_id
 * @return array
 */
function bookacti_get_notification_default_settings( $notification_id ) {
	$notifications = bookacti_get_notifications_default_settings();
	$default_settings = isset( $notifications[ $notification_id ] ) ? $notifications[ $notification_id ] : array();
	return apply_filters( 'bookacti_notification_default_settings', $default_settings, $notification_id );
}


/**
 * Get notification settings
 * @since 1.2.1 (was bookacti_get_email_settings in 1.2.0)
 * @version 1.8.6
 * @param string $notification_id
 * @param boolean $raw
 * @return array
 */
function bookacti_get_notification_settings( $notification_id, $raw = true ) {
	if( ! $notification_id ) { return array(); }
	
	$notification_settings = array();
	$default_settings = bookacti_get_notification_default_settings( $notification_id );
	
	// Get raw value from database
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		if( isset( $alloptions[ 'bookacti_notifications_settings_' . $notification_id ] ) ) {
			$notification_settings = maybe_unserialize( $alloptions[ 'bookacti_notifications_settings_' . $notification_id ] );
		}
	} 
	
	// Else, get notification settings through a normal get_option
	else {
		$notification_settings = get_option( 'bookacti_notifications_settings_' . $notification_id, array() );
		if( ! is_array( $notification_settings ) ) { $notification_settings = array(); }
	}
	
	// Make sure all values are set
	if( ! empty( $default_settings ) ) {
		foreach( $default_settings as $key => $value ) {
			if( ! isset( $notification_settings[ $key ] ) ) {
				$notification_settings[ $key ] = $value;
			}
		}
	}
	
	// Make sure all values are set for emails
	if( ! empty( $default_settings[ 'email' ] ) ) {
		foreach( $default_settings[ 'email' ] as $key => $value ) {
			if( ! isset( $notification_settings[ 'email' ][ $key ] ) ) {
				$notification_settings[ 'email' ][ $key ] = $value;
			}
		}
	}
	
	return apply_filters( 'bookacti_notification_settings', $notification_settings, $notification_id, $raw );
}


/**
 * Sanitize notification settings
 * @since 1.2.1 (was bookacti_sanitize_email_settings in 1.2.0)
 * @version 1.8.6
 * @param array $args
 * @param string $notification_id Optionnal notification id. If set, default value will be picked from the corresponding notification.
 * @return array
 */
function bookacti_sanitize_notification_settings( $args, $notification_id = '' ) {
	if( ! $args ) { return false; }

	$defaults = bookacti_get_notification_default_settings( $notification_id );
	if( ! $defaults ) {
		$defaults = array(
			'id'		=> $notification_id,
			'active'	=> 0,
			'email'		=> array(
				'active'	=> 1,
				'to'		=> array(),
				'subject'	=> '',
				'message'	=> '' )
		);
	}
	
	$notification = array();
	foreach( $defaults as $key => $default_value ) {
		// Do not save constant data
		if( in_array( $key, array( 'id', 'title', 'description' ), true ) ) { continue; }
		
		$notification[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $default_value;

		if( $key === 'active' ) {
			$notification[ $key ] = intval( $notification[ $key ] ) ? 1 : 0;

		} else if( $key === 'email' ) {
			foreach( $default_value as $email_key => $email_value ) {
				$notification[ 'email' ][ $email_key ] = isset( $args[ 'email' ][ $email_key ] ) ? $args[ 'email' ][ $email_key ] : $email_value;
				
				if( $email_key === 'active' ) {
					$notification[ 'email' ][ $email_key ] = intval( $notification[ 'email' ][ $email_key ] ) ? 1 : 0;
					
				} else if( $email_key === 'to' ) {
					if( ! is_array( $notification[ 'email' ][ $email_key ] ) ) {
						$notification[ 'email' ][ $email_key ] = strval( $notification[ 'email' ][ $email_key ] );
						$notification[ 'email' ][ $email_key ] = strpos( $notification[ 'email' ][ $email_key ], ',' ) !== false ? explode( ',', $notification[ 'email' ][ $email_key ] ) : array( $notification[ 'email' ][ $email_key ] );
					}
					
					foreach( $notification[ 'email' ][ $email_key ] as $to_key => $to_email_address ) {
						$sanitized_email = sanitize_email( $to_email_address );
						if( $sanitized_email ) {
							$notification[ 'email' ][ $email_key ][ $to_key ] = $sanitized_email;
						} else {
							unset( $notification[ 'email' ][ $email_key ][ $to_key ] );
						}
					}

				} else if( $email_key === 'title' || $email_key === 'subject' ) {
					$sanitized_field = sanitize_text_field( stripslashes( $notification[ 'email' ][ $email_key ] ) );
					$notification[ 'email' ][ $email_key ] = $sanitized_field ? $sanitized_field : $email_value;

				} else if( $email_key === 'message' ) {
					$sanitized_textarea = wp_kses_post( stripslashes( $notification[ 'email' ][ $email_key ] ) );
					$notification[ 'email' ][ $email_key ] = $sanitized_textarea ? $sanitized_textarea : $email_value;
				}
			}
		}
	}
	
	return apply_filters( 'bookacti_notification_sanitized_settings', $notification, $notification_id, $args );
}


/**
 * Get notifications tags
 * @since 1.2.0
 * @version 1.12.0
 * @param string $notification_id Optional.
 * @return array
 */
function bookacti_get_notifications_tags( $notification_id = '' ) {
	$tags = array( 
		'{booking_id}'			=> esc_html__( 'Booking unique ID (integer). Bookings and booking groups have different set of IDs.', 'booking-activities' ),
		'{booking_title}'		=> esc_html__( 'The event / group of events title.', 'booking-activities' ),
		'{booking_quantity}'	=> esc_html__( 'Booking quantity. If bookings of a same group happen to have different quantities, the higher is displayed.', 'booking-activities' ),
		'{booking_total_qty}'	=> esc_html__( 'For booking groups, this is the bookings sum. For single bookings, this is the same as {booking_quantity}.', 'booking-activities' ),
		'{booking_status}'		=> esc_html__( 'Current booking status.', 'booking-activities' ),
		'{booking_event_id}'	=> esc_html__( 'Booking event ID. For booking groups, the group of events ID is used.', 'booking-activities' ),
		'{booking_start}'		=> esc_html__( 'Booking start date and time displayed in a user-friendly format. For booking groups, the first event start date and time is used.', 'booking-activities' ),
		'{booking_start_raw}'	=> esc_html__( 'Booking start date and time displayed in the ISO format. For booking groups, the first event start date and time is used.', 'booking-activities' ),
		'{booking_end}'			=> esc_html__( 'Booking end date and time displayed in a user-friendly format. For booking groups, the last event end date and time is used.', 'booking-activities' ),
		'{booking_end_raw}'		=> esc_html__( 'Booking end date and time displayed in the ISO format. For booking groups, the last event end date and time is used.', 'booking-activities' ),
		'{booking_list}'		=> esc_html__( 'Booking summary displayed as a booking list. You should use this tag once in every notification to know what booking (group) it is about.', 'booking-activities' ),
		'{booking_list_raw}'	=> esc_html__( 'Booking summary displayed as a comma separated booking list, without HTML formatting.', 'booking-activities' ),
		'{activity_id}'			=> esc_html__( 'Activity or Group Category ID', 'booking-activities' ),
		'{activity_title}'		=> esc_html__( 'Activity or Group Category title', 'booking-activities' ),
		'{calendar_id}'			=> esc_html__( 'Calendar ID', 'booking-activities' ),
		'{user_firstname}'		=> esc_html__( 'The user first name', 'booking-activities' ),
		'{user_lastname}'		=> esc_html__( 'The user last name', 'booking-activities' ),
		'{user_email}'			=> esc_html__( 'The user email address', 'booking-activities' ),
		'{user_phone}'			=> esc_html__( 'The user phone number', 'booking-activities' ),
		'{user_id}'				=> esc_html__( 'The user ID. If the user has booked without account, this will display his email address.', 'booking-activities' ),
		'{user_locale}'			=> esc_html__( 'The user locale code. If the user has booked without account, the site locale will be used.', 'booking-activities' ),
		'{user_ical_url}'		=> esc_html__( 'URL to export the user list of bookings in ical format. You can append the "start" and "end" parameters with relative time format to this URL (e.g.: {user_ical_url}&start=today&end=next+year). If the user has booked without account, only the current booking is exported.', 'booking-activities' ),
		'{booking_ical_url}'	=> esc_html__( 'URL to export the current booking in ical format.', 'booking-activities' ),
		'{shortcode}{/shortcode}'	=> esc_html__( 'Use any shortcode between these tags.', 'booking-activities' )
	);
	
	if( substr( $notification_id, 0, 6 ) === 'admin_' ) {
		$booking_list_shortcode_docs = '<a href="https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-customers-bookings-list-on-the-frontend/" target="_blank">' . esc_html__( '[bookingactivities_list] shortcode parameters', 'booking-activities' ) . '</a>';
		$tags[ '{booking_admin_url}' ]	= esc_html__( 'URL to the booking admin panel.', 'booking-activities' ) . ' ' . esc_html__( 'View only the current booking.', 'booking-activities' );
		$tags[ '{event_admin_url}' ]	= esc_html__( 'URL to the booking admin panel.', 'booking-activities' ) . ' ' . esc_html__( 'View all bookings for the current event.', 'booking-activities' );
		$tags[ '{event_booking_list}{/event_booking_list}' ] = esc_html__( 'Event booking list (table)', 'booking-activities' ) . '. ' 
															/* translators: %s = "[bookingactivities_list] shortcode parameters" (link to the documentation) */
															.  sprintf( esc_html__( 'Use %s between the tags. E.g.:', 'booking-activities' ), $booking_list_shortcode_docs ) . ' <code>{event_booking_list}status="delivered, booked, pending" columns="booking_id, quantity, customer_display_name, customer_email"{/event_booking_list}</code>';
	}
	
	if( strpos( $notification_id, '_rescheduled' ) !== false ) {
		$tags[ '{booking_old_start_raw}' ]	= esc_html__( 'Booking start date and time before reschedule. Displayed in the ISO format.', 'booking-activities' );
		$tags[ '{booking_old_end_raw}' ]	= esc_html__( 'Booking end date and time before reschedule. Displayed in the ISO format.', 'booking-activities' );
		$tags[ '{booking_old_start}' ]		= esc_html__( 'Booking start date and time before reschedule. Displayed in a user-friendly format.', 'booking-activities' );
		$tags[ '{booking_old_end}' ]		= esc_html__( 'Booking end date and time before reschedule. Displayed in a user-friendly format.', 'booking-activities' );
	}
	
	if( strpos( $notification_id, '_refunded' ) !== false || strpos( $notification_id, '_refund_requested' ) !== false ) {
		$tags[ '{refund_message}' ] = esc_html__( 'Message written by the customer during the refund (request).', 'booking-activities' );
	}
	
	return apply_filters( 'bookacti_notifications_tags', $tags, $notification_id );
}


/**
 * Get notifications tags and values corresponding to given booking
 * @since 1.2.0
 * @version 1.12.3
 * @param object $booking
 * @param string $booking_type 'group' or 'single'
 * @param array $notification
 * @param string $locale Optional
 * @return array
 */
function bookacti_get_notifications_tags_values( $booking, $booking_type, $notification, $locale = 'site' ) {
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	// Event booking list default attributes
	$event_booking_list_atts = array(
		'user_id'	=> 'all',
		'status'	=> 'delivered,booked,pending',
		'columns'	=> 'booking_id,status,quantity,customer_display_name,customer_email',
		'per_page'	=> 999999,
		'group_by'	=> 'booking_group'
	);
	
	$booking_data = array();
	
	if( $booking ) {
		$datetime_format = apply_filters( 'bookacti_translate_text', bookacti_get_message( 'date_format_long', true ), $locale );
		
		if( $booking_type === 'group' ) {
			$bookings = bookacti_get_booking_group_bookings_by_id( $booking->id );
			
			$booking_data[ '{booking_total_qty}' ]	= 0;
			foreach( $bookings as $grouped_booking ) { $booking_data[ '{booking_total_qty}' ] += intval( $grouped_booking->quantity ); }
			$booking_data[ '{booking_title}' ]		= $booking->group_title;
			$booking_data[ '{booking_event_id}' ]	= $booking->event_group_id;
			$booking_data[ '{booking_start}' ]		= bookacti_format_datetime( $booking->start, $datetime_format );
			$booking_data[ '{booking_start_raw}' ]	= $booking->start;
			$booking_data[ '{booking_end}' ]		= bookacti_format_datetime( $booking->end, $datetime_format );
			$booking_data[ '{booking_end_raw}' ]	= $booking->end;
			$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&booking_group_id=' . $booking->id . '&group_by=booking_group' );
			$booking_data[ '{event_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&event_group_id=' . $booking->event_group_id . '&group_by=booking_group' );
			$booking_data[ '{activity_id}' ]		= $booking->category_id;
			$booking_data[ '{activity_title}' ]		= apply_filters( 'bookacti_translate_text', $booking->category_title, $locale );
			
			$event_booking_list_atts[ 'event_group_id' ] = $booking->event_group_id;
			
		} else {
			$bookings = array( $booking );
			
			$booking_data[ '{booking_total_qty}' ]	= $booking->quantity;
			$booking_data[ '{booking_title}' ]		= $booking->event_title;
			$booking_data[ '{booking_event_id}' ]	= $booking->event_id;
			$booking_data[ '{booking_start}' ]		= bookacti_format_datetime( $booking->event_start, $datetime_format );
			$booking_data[ '{booking_start_raw}' ]	= $booking->event_start;
			$booking_data[ '{booking_end}' ]		= bookacti_format_datetime( $booking->event_end, $datetime_format );
			$booking_data[ '{booking_end_raw}' ]	= $booking->event_end;
			$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&booking_id=' . $booking->id );
			$booking_data[ '{event_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end );
			$booking_data[ '{activity_id}' ]		= $booking->activity_id;
			$booking_data[ '{activity_title}' ]		= apply_filters( 'bookacti_translate_text', $booking->activity_title, $locale );
		
			$event_booking_list_atts[ 'event_id' ] = $booking->event_id;
			$event_booking_list_atts[ 'event_start' ] = $booking->event_start;
			$event_booking_list_atts[ 'event_end' ] = $booking->event_end;
		}

		$booking_data[ '{booking_id}' ]			= $booking->id;
		$booking_data[ '{booking_title}' ]		= $booking_data[ '{booking_title}' ] ? apply_filters( 'bookacti_translate_text', $booking_data[ '{booking_title}' ], $locale ) : '';
		$booking_data[ '{booking_status}' ]		= bookacti_format_booking_state( $booking->state );
		$booking_data[ '{booking_quantity}' ]	= $booking->quantity;
		$booking_data[ '{booking_list}' ]		= bookacti_get_formatted_booking_events_list( $bookings, 'show', $locale );
		$booking_data[ '{booking_list_raw}' ]	= bookacti_get_formatted_booking_events_list_raw( $bookings, 'show', $locale );
		$booking_data[ '{calendar_id}' ]		= $booking->template_id;
		$booking_data[ '{refund_message}' ]		= ! empty( $booking->refund_message ) ? $booking->refund_message : '';
		
		$user_ical_key = '';
		$booking_data[ '{user_locale}' ] = $locale;
		if( $booking->user_id ) { 
			$booking_data[ '{user_id}' ] = $booking->user_id;
			$user = is_numeric( $booking->user_id ) ? get_user_by( 'id', $booking->user_id ) : null;
			if( $user ) { 
				$booking_data[ '{user_firstname}' ]	= ! empty( $user->first_name ) ? $user->first_name : '';
				$booking_data[ '{user_lastname}' ]	= ! empty( $user->last_name ) ? $user->last_name : ''; 
				$booking_data[ '{user_email}' ]		= ! empty( $user->user_email ) ? $user->user_email : ( is_email( $booking->user_id ) ? $booking->user_id : '' );
				$booking_data[ '{user_phone}' ]		= ! empty( $user->phone ) ? $user->phone : '';
				
				$user_meta = get_user_meta( $booking->user_id );
				if( ! empty( $user_meta[ 'bookacti_secret_key' ][ 0 ] ) ) {
					$user_ical_key = $user_meta[ 'bookacti_secret_key' ][ 0 ];
				} else {
					$user_ical_key = md5( microtime().rand() );
					update_user_meta( $booking->user_id, 'bookacti_secret_key', $user_ical_key );
				}
				$booking_data[ '{user_ical_url}' ] = esc_url( home_url( '?action=bookacti_export_user_booked_events&filename=my-bookings&key=' . $user_ical_key . '&lang=' . $locale ) );
			} else {
				$booking_data[ '{user_firstname}' ]	= ! empty( $booking->user_first_name ) ? $booking->user_first_name : '';
				$booking_data[ '{user_lastname}' ]	= ! empty( $booking->user_last_name ) ? $booking->user_last_name : '';
				$booking_data[ '{user_email}' ]		= ! empty( $booking->user_email ) ? $booking->user_email : ( is_email( $booking->user_id ) ? $booking->user_id : '' );
				$booking_data[ '{user_phone}' ]		= ! empty( $booking->user_phone ) ? $booking->user_phone : '';
			}
		}
		
		$booking_ical_key = $user_ical_key ? $user_ical_key : ( ! empty( $booking->secret_key ) ? $booking->secret_key : '' );
		if( ! $booking_ical_key ) { 
			$booking_ical_key = md5( microtime().rand() );
			$object_type = $booking_type === 'group' ? 'booking_group' : 'booking';
			bookacti_update_metadata( $object_type, $booking->id, array( 'secret_key' => $booking_ical_key ) );
		}
		$booking_id_param_name = $booking_type === 'group' ? 'booking_group_id' : 'booking_id';
		$booking_data[ '{booking_ical_url}' ] = esc_url( home_url( '?action=bookacti_export_booked_events&filename=my-bookings&key=' . $booking_ical_key . '&' . $booking_id_param_name . '=' . $booking->id . '&lang=' . $locale ) );
		if( empty( $booking_data[ '{user_ical_url}' ] ) ) { $booking_data[ '{user_ical_url}' ] = $booking_data[ '{booking_ical_url}' ]; }
	}
	
	$tags = array();
	
	// Transform event booking list tag into [bookingactivities_list] shortcode tags
	$default_args_str = '';
	foreach( $event_booking_list_atts as $key => $value ) { $default_args_str .= ' ' . $key . '="' . $value . '"'; }
	$tags[ '{event_booking_list}' ]		= '{shortcode}[bookingactivities_list' . $default_args_str. ' ';
	$tags[ '{/event_booking_list}' ]	= ']{/shortcode}';
	$GLOBALS[ 'bookacti_notification_private_columns' ] = substr( $notification[ 'id' ], 0, 6 ) === 'admin_' ? 1 : 0;
	
	// Make sure the array contains all tags 
	$default_tags = array_keys( bookacti_get_notifications_tags( $notification[ 'id' ] ) );
	foreach( $default_tags as $default_tag ) {
		$tags[ $default_tag ] = isset( $booking_data[ $default_tag ] ) ? $booking_data[ $default_tag ] : '';
	}
	
	return apply_filters( 'bookacti_notifications_tags_values', $tags, $booking, $booking_type, $notification, $locale );
}


/**
 * Send a notification according to its settings
 * @since 1.2.1 (was bookacti_send_email in 1.2.0)
 * @version 1.12.0
 * @param string $notification_id Must exists in "bookacti_notifications_default_settings"
 * @param int $booking_id
 * @param string $booking_type "single" or "group"
 * @param array $args Replace or add notification settings and tags
 * @param boolean $async Whether to send the notification asynchronously. 
 * @return array
 */
function bookacti_send_notification( $notification_id, $booking_id, $booking_type, $args = array(), $async = 1 ) {
	$async = ! empty( $async ) ? 1 : 0;
	
	// Send notifications asynchronously
	$allow_async = apply_filters( 'bookacti_allow_async_notifications', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ) );
	if( $allow_async && $async ) {
		// Delay with few seconds to try to avoid scheduling problems
		wp_schedule_single_event( time() + 3, 'bookacti_send_async_notification', array( $notification_id, $booking_id, $booking_type, $args, 0 ) );
		return array();
	}
	
	// Make sure not to run the same cron task multiple times
	if( $allow_async && ! $async ) {
		// If this notification was already sent in the past few minutes, do not send it again
		$notification_unique_key = md5( json_encode( array( $notification_id, $booking_id, $booking_type, $args ) ) );
		$already_sent = get_transient( 'bookacti_notif_' . $notification_unique_key );
		if( $already_sent ) { return array(); }
		set_transient( 'bookacti_notif_' . $notification_unique_key, 1, 3*60 );
	}
	
	// Get notification settings
	$notification = bookacti_get_notification_settings( $notification_id );
	
	// Replace or add notification settings
	if( ! empty( $args ) && ! empty( $args[ 'notification' ] ) ) {
		$notification = array_merge( $notification, $args[ 'notification' ] );
	}
	
	if( ! $notification || empty( $notification[ 'active' ] ) ) { return array(); }
	
	// Get the booking (group)
	$booking = $booking_type === 'group' ? bookacti_get_booking_group_by_id( $booking_id, true ) : bookacti_get_booking_by_id( $booking_id, true );
	if( ! $booking ) { return array(); }
	
	// Change params according to recipients
	$locale = '';
	if( substr( $notification_id, 0, 8 ) === 'customer' ) {
		$user_id = $booking->user_id;
		$user = is_numeric( $user_id ) ? get_user_by( 'id', $user_id ) : null;
		
		// If the user has an account
		if( $user ) {
			$user_email = $user->user_email;
			// Use the user locale to translate the email
			$locale = bookacti_get_user_locale( $user, 'site' );
		} else if( is_email( $user_id ) ) {
			$user_email = $user_id;
		} else {
			$object_type = $booking_type === 'group' ? 'booking_group' : 'booking';
			$user_email = ! empty( $booking->user_email ) ? $booking->user_email : '';
			if( ! is_email( $user_email ) ) { $user_email = ''; }
		}
		
		// Fill the recipients fields
		$notification[ 'email' ][ 'to' ] = array( $user_email );
	} 
	
	if( ! $locale ) { $locale = bookacti_get_site_locale();	}
	
	$locale = apply_filters( 'bookacti_notification_locale', $locale, $notification_id, $booking, $booking_type, $args );
	
	// Temporarily switch locale to site or user default's
	bookacti_switch_locale( $locale );
	
	// Replace tags in message and replace linebreaks with html tags
	$tags = bookacti_get_notifications_tags_values( $booking, $booking_type, $notification, $locale );
	
	// Replace or add tags values
	if( ! empty( $args ) && ! empty( $args[ 'tags' ] ) ) {
		$tags = array_merge( $tags, $args[ 'tags' ] );
	}
	
	$notification	= apply_filters( 'bookacti_notification_data', $notification, $tags, $locale, $booking, $booking_type, $args );
	$tags			= apply_filters( 'bookacti_notification_tags', $tags, $notification, $locale, $booking, $booking_type, $args );
	$allow_sending	= apply_filters( 'bookacti_notification_sending_allowed', true, $notification, $tags, $locale, $booking, $booking_type, $args );
	
	if( ! $allow_sending ) { bookacti_restore_locale(); return array(); } 
	
	// Send email notification
	$sent = array( 'email' => 0 );
	$sent_email = bookacti_send_email_notification( $notification, $tags, $locale );
	
	if( $sent_email ) {
		$sent[ 'email' ] = count( $notification[ 'email' ][ 'to' ] );
	}
	
	$sent = apply_filters( 'bookacti_send_notifications', $sent, $notification_id, $notification, $tags, $booking, $booking_type, $args, $locale );
	
	// Switch locale back to normal
	bookacti_restore_locale();
	
	return $sent;
}

// Hook the asynchronous call and send the notification
add_action( 'bookacti_send_async_notification', 'bookacti_send_notification', 10, 5 );


/**
 * Send an email notification
 * @since 1.2.0
 * @version 1.9.0
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @return boolean
 */
function bookacti_send_email_notification( $notification, $tags = array(), $locale = 'site' ) {
	// Do not send email notification if it is deactivated or if there are no recipients
	if( empty( $notification[ 'active' ] ) || empty( $notification[ 'email' ][ 'active' ] ) || empty( $notification[ 'email' ][ 'to' ] ) ) { return false; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	$to			= $notification[ 'email' ][ 'to' ];
	$subject	= str_replace( array_keys( $tags ), array_values( $tags ), apply_filters( 'bookacti_translate_text', $notification[ 'email' ][ 'subject' ], $locale ) );
	$message	= wpautop( str_replace( array_keys( $tags ), array_values( $tags ), apply_filters( 'bookacti_translate_text', $notification[ 'email' ][ 'message' ], $locale ) ) );
	
	// Do shortcodes
	while( $shortcode = bookacti_get_string_between( $message, '{shortcode}', '{/shortcode}' ) ) {
		$shortcode_done = do_shortcode( $shortcode );
		if( $shortcode_done === $shortcode ) { $shortcode_done = ''; }
		$message = str_replace( '{shortcode}' . $shortcode . '{/shortcode}', $shortcode_done, $message );
	}
	
	$from_name	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_name' );
	$from_email	= bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_from_email' );
	$headers	= array( 'Content-Type: text/html; charset=UTF-8;', 'From:' . $from_name . ' <' . $from_email . '>' );
	
	$email_data = apply_filters( 'bookacti_email_notification_data', array(
		'to'			=> $to,
		'subject'		=> $subject,
		'message'		=> $message,
		'headers'		=> $headers,
		'attachments'	=> array()
	), $notification, $tags, $locale );
	
	$sent = bookacti_send_email( $email_data[ 'to' ], $email_data[ 'subject' ], $email_data[ 'message' ], $email_data[ 'headers' ], $email_data[ 'attachments' ] );
	
	do_action( 'bookacti_email_notification_sent', $sent, $email_data, $notification, $tags, $locale );
	
	return $sent;
}


// Allow this function to be replaced
if( ! function_exists( 'bookacti_send_new_user_notification' ) ) {

/**
 * Email login credentials to a newly-registered user in an asynchronous way
 * @since 1.5.0
 * @version 1.7.0
 * @global string  $wp_version
 * @param  int     $user_id   User ID.
 * @param  string  $notify    Optional. Type of notification that should happen. Accepts 'admin' or an empty
 *                            string (admin only), 'user', or 'both' (admin and user). Default 'both'.
 * @param  boolean $async     Whether to send the notification asynchronously. 
 */
function bookacti_send_new_user_notification( $user_id, $notify = 'both', $async = 1 ) {
	$async = ! empty( $async ) ? 1 : 0;
	
	// Send notifications asynchronously
	$allow_async = apply_filters( 'bookacti_allow_async_notifications', bookacti_get_setting_value( 'bookacti_notifications_settings', 'notifications_async' ) );
	if( $allow_async && $async ) {
		wp_schedule_single_event( time() + 3, 'bookacti_send_async_new_user_notification', array( $user_id, $notify, 0 ) );
		return;
	}	
	
	// Send new user email in a backward compatible way
	global $wp_version;
	if( $notify === 'user' && version_compare( $wp_version, '4.6.0', '<' ) ) { $notify = 'both'; }
	
	if( version_compare( $wp_version, '4.3.1', '>=' ) ) {
		wp_new_user_notification( $user_id, null, $notify );
	} else if( version_compare( $wp_version, '4.3.0', '==' ) ) {
		wp_new_user_notification( $user_id, $notify );
	} else {
		$user = get_user_by( 'id', $user_id );
		wp_new_user_notification( $user_id, $user->user_pass );
	}
}

// Hook the asynchronous call and send the new user notification
add_action( 'bookacti_send_async_new_user_notification', 'bookacti_send_new_user_notification', 10, 3 );

}


/**
 * Send an email.
 * Make sure not to send more emails than allowed in a specific timeframe
 * @since 1.7.0
 * @version 1.9.0
 * @param array $to
 * @param string $subject
 * @param string $message
 * @param array $headers
 * @param array $attachments
 * @return bool
 */
function bookacti_send_email( $to, $subject, $message, $headers, $attachments = array() ) {
	$recipients				= is_array( $to ) ? $to : explode( ',', $to );
	$latest_emails_sent		= get_option( 'bookacti_latest_emails_sent' );
	if( ! $latest_emails_sent ) { $latest_emails_sent = array(); }
	
	$current_datetime		= new DateTime( 'now' );
	$time_formatted			= $current_datetime->format( 'Y-m-d H:i:s' );
	
	$user_threshold_minute	= apply_filters( 'bookacti_limit_email_per_minute_per_user', 20 );
	$user_threshold_hour	= apply_filters( 'bookacti_limit_email_per_hour_per_user', 200 );
	$user_threshold_day		= apply_filters( 'bookacti_limit_email_per_day_per_user', 2000 );
	$user_exceptions		= apply_filters( 'bookacti_limit_email_per_user_exceptions', array() );
	
	$one_mn_ago_datetime	= clone $current_datetime;
	$one_hour_ago_datetime	= clone $current_datetime;
	$one_day_ago_datetime	= clone $current_datetime;
	$one_mn_ago_datetime->sub( new DateInterval( 'PT1M' ) );
	$one_hour_ago_datetime->sub( new DateInterval( 'PT1H' ) );
	$one_day_ago_datetime->sub( new DateInterval( 'P1D' ) );
	
	$emails_count_minute_per_user	= array();
	$emails_count_hour_per_user		= array();
	$emails_count_day_per_user		= array();
	
	// Check per recipient thresholds
	if( $latest_emails_sent ) {
		foreach( $recipients as $i => $recipient ) {
			if( in_array( $recipient, $user_exceptions, true ) ) { continue; }
			if( empty( $latest_emails_sent[ $recipient ] ) || ! is_array( $latest_emails_sent[ $recipient ] ) ) { continue; }
			$emails_count_minute_per_user[ $recipient ] = 0;
			$emails_count_hour_per_user[ $recipient ] = 0;
			$emails_count_day_per_user[ $recipient ] = 0;
			foreach( $latest_emails_sent[ $recipient ] as $j => $email_sent ) {
				$email_datetime = new DateTime( $email_sent );
				if( $one_day_ago_datetime < $email_datetime ) {
					$emails_count_day_per_user[ $recipient ] += 1;
				} else {
					// Remove useless values (before day-1) to clean the database
					unset( $latest_emails_sent[ $j ] );
					continue;
				}
				if( $one_mn_ago_datetime < $email_datetime ) {
					$emails_count_minute_per_user[ $recipient ] += 1;
				}
				if( $one_hour_ago_datetime < $email_datetime ) {
					$emails_count_hour_per_user[ $recipient ] += 1;
				}
			}
			
			if( $emails_count_minute_per_user[ $recipient ] >= $user_threshold_minute
			||  $emails_count_hour_per_user[ $recipient ] >= $user_threshold_hour 
			||  $emails_count_day_per_user[ $recipient ] >= $user_threshold_day ) {
				unset( $recipients[ $i ] );
			}
		}
	}
	
	$actual_recipients = apply_filters( 'bookacti_send_email_recipients', $recipients, $to, $subject, $message, $headers, $attachments );
	
	if( ! $actual_recipients ) { return false; }
	
	$sent = wp_mail( $actual_recipients, $subject, $message, $headers, $attachments );
	
	if( $sent ) {
		foreach( $actual_recipients as $i => $recipient ) {
			if( in_array( $recipient, $user_exceptions, true ) ) { continue; }
			if( ! isset( $latest_emails_sent[ $recipient ] ) || ! is_array( $latest_emails_sent[ $recipient ] ) ) { $latest_emails_sent[ $recipient ] = array(); }
			$latest_emails_sent[ $recipient ][] = $time_formatted;
		}
		update_option( 'bookacti_latest_emails_sent', $latest_emails_sent );
	}
	
	return $sent;
}


/**
 * Send a notification when an event dates change to the customers who booked it, once per event per user, for future bookings only
 * @since 1.10.0
 * @param object $old_event
 * @param array $old_bookings
 * @param int $delta_seconds
 */
function bookacti_send_event_rescheduled_notifications( $old_event, $old_bookings = array(), $delta_seconds = 0 ) {
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$now_dt = new DateTime( 'now', new DateTimeZone( $timezone ) );
	$from = $old_event->repeat_freq && $old_event->repeat_freq !== 'none' ? $now_dt->format( 'Y-m-d H:i:s' ) : '';
	
	// If $old_bookings is set, retrieve only the given bookings, if they were not already cancelled
	// else retrieve all cancelled bookings of the given event
	$bookings_filters = array( 'event_id' => $old_event->event_id, 'from' => $from, 'active' => 1 );
	if( $old_bookings ) {
		$bookings_filters = array( 'in__booking_id' => array_keys( $old_bookings ), 'from' => $from, 'active' => 1 );
	}
	
	// Get bookings
	$bookings_filters = bookacti_format_booking_filters( $bookings_filters );
	$bookings = bookacti_get_bookings( $bookings_filters );
	if( ! $bookings ) { return; }
	
	// If there are a lot of scheduled notifications to send, this operation can take a while
	// So we need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 'send_event_rescheduled_notifications' );
	
	foreach( $bookings as $booking_id => $booking ) {
		$old_booking = isset( $old_bookings[ $booking_id ] ) ? $old_bookings[ $booking_id ] : $booking;
		
		// Compute old booking start and end
		if( ! isset( $old_bookings[ $booking_id ] ) ) {
			$old_booking_start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $old_booking->event_start, new DateTimeZone( $timezone ) );
			$old_booking_end_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $old_booking->event_end, new DateTimeZone( $timezone ) );
			$delta_seconds_di = new DateInterval( 'PT' . abs( $delta_seconds ). 'S' );
			$delta_seconds_di->invert = $delta_seconds > 0 ? 1 : 0;
			$old_booking_start_dt->add( $delta_seconds_di );
			$old_booking_end_dt->add( $delta_seconds_di );
			$old_booking->event_start = $old_booking_start_dt->format( 'Y-m-d' ) . ' ' . substr( $old_event->start, 11, 8 );
			$old_booking->event_end = $old_booking_end_dt->format( 'Y-m-d' ) . ' ' . substr( $old_event->end, 11, 8 );
		}
		
		$send = apply_filters( 'bookacti_send_event_rescheduled_notification', true, $booking, $old_event, $delta_seconds, $old_booking );
		if( ! $send ) { continue; }
		
		$notification_args = array( 'tags' => array(
			'{booking_old_start_raw}' => $old_booking->event_start,
			'{booking_old_end_raw}' => $old_booking->event_end
		));

		bookacti_send_notification( 'customer_rescheduled_booking', $booking->id, 'single', $notification_args );

		do_action( 'bookacti_event_rescheduled_notification_sent', $booking, $old_event, $delta_seconds, $old_booking );
	}
}


/**
 * Send a notification when an event is deleted to the customers who booked it
 * @since 1.10.0
 * @param object $event
 * @param array $old_bookings
 */
function bookacti_send_event_cancelled_notifications( $event, $old_bookings = array() ) {
	// If the event is repeated, send notifications only for future events
	$timezone = bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$now_dt = new DateTime( 'now', new DateTimeZone( $timezone ) );
	$from = $event->repeat_freq && $event->repeat_freq !== 'none' ? $now_dt->format( 'Y-m-d H:i:s' ) : '';

	// If $old_bookings is set, retrieve only the given bookings, if they were not already cancelled
	// else retrieve all cancelled bookings of the given event
	$booking_ids = array();
	$bookings_filters = array( 'event_id' => $event->event_id, 'from' => $from, 'status' => array( 'cancelled' ) );
	if( $old_bookings ) {
		foreach( $old_bookings as $booking_id => $old_booking ) {
			if( $old_booking->active ) { $booking_ids[] = $booking_id; }
		}
		if( ! $booking_ids ) { return; }
		$bookings_filters = array( 'in__booking_id' => $booking_ids, 'status' => array( 'cancelled' ) );
	}
	
	// Get bookings
	$bookings_filters = bookacti_format_booking_filters( $bookings_filters );
	$bookings = bookacti_get_bookings( $bookings_filters );
	if( ! $bookings ) { return; }
	
	// If there are a lot of scheduled notifications to send, this operation can take a while
	// So we need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 'send_event_cancelled_notifications' );

	foreach( $bookings as $booking ) {
		$old_booking = ! empty( $old_bookings[ $booking->id ] ) ? $old_bookings[ $booking->id ] : $booking;

		$send = apply_filters( 'bookacti_send_event_cancelled_notification', true, $booking, $event, $old_booking );
		if( ! $send ) { continue; }
		
		bookacti_send_notification( 'customer_cancelled_booking', $booking->id, 'single' );

		do_action( 'bookacti_event_cancelled_notification_sent', $event, $booking, $old_booking );
	}
}


/**
 * Send a notification when an event is deleted to the customers who booked it
 * @since 1.10.0
 * @param array $group_of_events
 * @param array $old_booking_groups
 */
function bookacti_send_group_of_events_cancelled_notifications( $group_of_events, $old_booking_groups = array() ) {
	// If $old_booking_groups is set, retrieve only the given booking groups, if they were not already cancelled
	// else retrieve all cancelled booking groups of the given event group
	$booking_group_ids = array();
	$booking_groups_filters = array( 'event_group_id' => $group_of_events[ 'id' ], 'status' => array( 'cancelled' ) );
	if( $old_booking_groups ) {
		foreach( $old_booking_groups as $booking_group_id => $old_booking_group ) {
			if( $old_booking_group->active ) { $booking_group_ids[] = $booking_group_id; }
		}
		if( ! $booking_group_ids ) { return; }
		$booking_groups_filters = array( 'in__booking_group_id' => $booking_group_ids, 'status' => array( 'cancelled' ) );
	}
	
	// Get booking groups
	$booking_groups_filters = bookacti_format_booking_filters( $booking_groups_filters );
	$booking_groups = bookacti_get_booking_groups( $booking_groups_filters );
	if( ! $booking_groups ) { return; }
	
	// If there are a lot of scheduled notifications to send, this operation can take a while
	// So we need to increase the max_execution_time and the memory_limit
	bookacti_increase_max_execution_time( 'send_group_of_events_cancelled_notifications' );
	
	foreach( $booking_groups as $booking_group ) {
		$old_booking_group = ! empty( $old_booking_groups[ $booking_group->id ] ) ? $old_booking_groups[ $booking_group->id ] : $booking_group;
		
		$send = apply_filters( 'bookacti_send_group_of_events_cancelled_notification', true, $booking_group, $group_of_events, $old_booking_group );
		if( ! $send ) { continue; }
		
		bookacti_send_notification( 'customer_cancelled_booking', $booking_group->id, 'group' );

		do_action( 'bookacti_group_of_events_cancelled_notification_sent', $group_of_events, $booking_group, $old_booking_group );
	}
}