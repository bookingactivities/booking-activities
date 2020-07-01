<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
	
/**
 * Array of configurable notifications
 * 
 * @since 1.2.1 (was bookacti_get_emails_default_settings in 1.2.0)
 * @version 1.7.0
 * @return array
 */
function bookacti_get_notifications_default_settings() {
	$notifications = array( 
		'admin_new_booking' => 
			array(
				'id'		=> 'admin_new_booking',
				'active'	=> 1,
				'title'		=> __( 'Customer has made a booking', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the administrator when a new booking is registered.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( get_bloginfo( 'admin_email' ) ),
					'subject'	=> __( 'New booking!', 'booking-activities' ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is made */
					'message'	=> __( '<p>You have {booking_total_qty} new booking(s) from {user_firstname} {user_lastname} ({user_email})!</p>
										<p>{booking_list}</p>
										<p>Booking status: <strong>{booking_status}</strong>.</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) )
			),
		'admin_cancelled_booking' => 
			array(
				'id'		=> 'admin_cancelled_booking',
				'active'	=> 1,
				'title'		=> __( 'Customer has cancelled a booking', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the administrator when a customer cancel a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( get_bloginfo( 'admin_email' ) ),
					'subject'	=> __( 'Booking cancelled', 'booking-activities' ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is cancelled */
					'message'	=> __( '<p>A customer has cancelled a booking.</p>
										<p>{booking_list}</p>
										<p>Customer info: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) )
			),
		'admin_rescheduled_booking' => 
			array(
				'id'		=> 'admin_rescheduled_booking',
				'active'	=> 1,
				'title'		=> __( 'Customer has rescheduled a booking', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the administrator when a customer reschedule a booking.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'to'		=> array( get_bloginfo( 'admin_email' ) ),
					'subject'		=> __( 'Booking rescheduled', 'booking-activities' ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email an administrator receive when a booking is rescheduled */
					'message'	=> __( '<p>A customer has rescheduled a booking.</p>
										<p>Old booking: {booking_old_start} - {booking_old_end}</p>
										<p>New booking: {booking_list}</p>
										<p>Customer info: {user_firstname} {user_lastname} ({user_email})</p>
										<p><a href="{booking_admin_url}">Click here</a> to edit this booking (ID: {booking_id}).</p>', 'booking-activities' ) )
			),
		
		'customer_pending_booking' => 
			array(
				'id'		=> 'customer_pending_booking',
				'active'	=> 1,
				'title'		=> __( 'Booking status turns to "Pending"', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the customer when one of his bookings becomes "Pending". If you set the "Default booking state" option to "Pending", this notification will be sent right after the booking is made.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> __( 'Your booking is pending', 'booking-activities' ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receive when a booking is made, but is still Pending */
					'message'	=> __( '<p>Thank you for your booking request {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>Your reservation is <strong>pending</strong>.</p>
										<p>We will process your request and contact you as soon as possible.</p>', 'booking-activities' ) )
			),
		'customer_booked_booking' => 
			array(
				'id'			=> 'customer_booked_booking',
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Booked"', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the customer when one of his bookings becomes "Booked". If you set the "Default booking state" option to "Booked", this notification will be sent right after the booking is made.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> __( 'Your booking is complete! Thank you', 'booking-activities' ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receive when a booking is made and Complete */
					'message'	=> __( '<p>Thank you for your booking {user_firstname}!</p>
										<p>{booking_list}</p>
										<p>We confirm that your reservation is now <strong>complete</strong>.</p>', 'booking-activities' ) )
			),
		'customer_cancelled_booking' => 
			array(
				'id'			=> 'customer_cancelled_booking',
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Cancelled"', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the customer when one of his bookings becomes "Cancelled".', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> __( 'Your booking has been cancelled', 'booking-activities' ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receive when a booking is cancelled */
					'message'	=> __( "<p>Hello {user_firstname},
										<p>Your booking has been <strong>cancelled</strong>.</p>
										<p>{booking_list}</p>
										<p>If you haven't cancelled this reservation or if you think this is an error, please contact us.</p>", 'booking-activities' ) )
			),
		'customer_refunded_booking' => 
			array(
				'id'			=> 'customer_refunded_booking',
				'active'		=> 1,
				'title'			=> __( 'Booking status turns to "Refunded"', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the customer when one of his bookings becomes "Refunded".', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> __( 'Your booking has been refunded', 'booking-activities' ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receive when he is reimbursed for a booking */
					'message'	=> __( '<p>Hello {user_firstname},
										<p>Your booking has been <strong>refunded</strong>.</p>
										<p>{booking_list}</p>
										<p>We are sorry for the inconvenience and hope to see you soon.</p>', 'booking-activities' ) )
			),
		'customer_rescheduled_booking' => 
			array(
				'id'			=> 'customer_rescheduled_booking',
				'active'		=> 1,
				'title'			=> __( 'Booking is rescheduled', 'booking-activities' ),
				'description'	=> __( 'This notification is sent to the customer when one of his bookings is rescheduled.', 'booking-activities' ),
				'email'			=> array(
					'active'	=> 1,
					'subject'	=> __( 'Your booking has been rescheduled', 'booking-activities' ) . ' - ' . apply_filters( 'bookacti_translate_text', get_bloginfo( 'name' ) ),
					/* translators: Keep tags as is (this is a tag: {tag}), they will be replaced in code. This is the default email a customer receive when a booking is rescheduled */
					'message'	=> __( "<p>Hello {user_firstname},
										<p>Your booking has been <strong>rescheduled</strong> from {booking_old_start} to:</p>
										<p>{booking_list}</p>
										<p>If you haven't rescheduled this reservation or if you think this is an error, please contact us.</p>", 'booking-activities' ) )
			),
	);

	return apply_filters( 'bookacti_notifications_default_settings', $notifications );
}


/**
 * Get notification default settings
 * 
 * @since 1.2.1 (was bookacti_get_email_default_settings in 1.2.0)
 * @param string $notification_id
 * @return false|array
 */
function bookacti_get_notification_default_settings( $notification_id ) {

	if( ! $notification_id ) { return false; }

	$notifications = bookacti_get_notifications_default_settings();

	if( ! isset( $notifications[ $notification_id ] ) ) { return false; }

	return $notifications[ $notification_id ];
}


/**
 * Get notification settings
 * @since 1.2.1 (was bookacti_get_email_settings in 1.2.0)
 * @version 1.8.5
 * @param string $notification_id
 * @param boolean $raw
 * @return false|array
 */
function bookacti_get_notification_settings( $notification_id, $raw = true ) {
	if( ! $notification_id ) { return false; }

	$notifications = bookacti_get_notifications_default_settings();
	if( ! isset( $notifications[ $notification_id ] ) ) { return false; }
	
	$notification_settings = array();
	
	// Get raw value from database
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		if( isset( $alloptions[ 'bookacti_notifications_settings_' . $notification_id ] ) ) {
			$notification_settings	= maybe_unserialize( $alloptions[ 'bookacti_notifications_settings_' . $notification_id ] );
		}
	} 
	
	// Else, get notification settings through a normal get_option
	else {
		$notification_settings = get_option( 'bookacti_notifications_settings_' . $notification_id );
	}
	
	// Make sure all values are set
	foreach( $notifications[ $notification_id ] as $key => $value ) {
		if( ! isset( $notification_settings[ $key ] ) ) {
			$notification_settings[ $key ] = $value;
		}
	}
	
	// Make sure all values are set for emails
	if( ! empty( $notifications[ $notification_id ][ 'email' ] ) ) {
		foreach( $notifications[ $notification_id ][ 'email' ] as $key => $value ) {
			if( ! isset( $notification_settings[ 'email' ][ $key ] ) ) {
				$notification_settings[ 'email' ][ $key ] = $value;
			}
		}
	}
	
	return apply_filters( 'bookacti_notification_settings', $notification_settings, $notification_id, $raw );
}


/**
 * Sanitize notification settings
 * 
 * @since 1.2.1 (was bookacti_sanitize_email_settings in 1.2.0)
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
			'title'		=> '',
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
		if( $key === 'id' || $key === 'title' ) { continue; }
		
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
					$notification[ 'email' ][ $email_key ] = $sanitized_field ? $sanitized_field : $default_value;

				} else if( $email_key === 'message' ) {

					$sanitized_textarea = wp_kses_post( stripslashes( $notification[ 'email' ][ $email_key ] ) );
					$notification[ 'email' ][ $email_key ] = $sanitized_textarea ? $sanitized_textarea : $default_value;
				}
			}
		}
	}
	
	return apply_filters( 'bookacti_notification_sanitized_settings', $notification, $notification_id );
}


/**
 * Get notifications tags
 * @since 1.2.0
 * @version 1.7.10
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
		'{user_firstname}'		=> esc_html__( 'The user first name', 'booking-activities' ),
		'{user_lastname}'		=> esc_html__( 'The user last name', 'booking-activities' ),
		'{user_email}'			=> esc_html__( 'The user email address', 'booking-activities' ),
		'{user_phone}'			=> esc_html__( 'The user phone number', 'booking-activities' ),
		'{user_id}'				=> esc_html__( 'The user ID. If the user has booked without account, this will display his email address.', 'booking-activities' ),
		'{user_locale}'			=> esc_html__( 'The user locale code. If the user has booked without account, the site locale will be used.', 'booking-activities' ),
		'{user_ical_url}'		=> esc_html__( 'URL to export the user list of bookings in ical format. You can append the "start" and "end" parameters with relative time format to this URL (e.g.: {user_ical_url}&start=today&end=next+year). If the user has booked without account, only the current booking is exported.', 'booking-activities' ),
		'{shortcode}{/shortcode}'	=> esc_html__( 'Use any shortcode between these tags.', 'booking-activities' )
	);
	
	if( substr( $notification_id, 0, 6 ) === 'admin_' ) {
		$tags[ '{booking_admin_url}' ]	= esc_html__( 'URL to the booking admin panel. Use this tag only on notifications sent to administrators.', 'booking-activities' );
	}
	
	if( $notification_id === 'admin_rescheduled_booking' || $notification_id === 'customer_rescheduled_booking' ) {
		$tags[ '{booking_old_start}' ]	= esc_html__( 'Booking start date and time before reschedule. Displayed in a user-friendly format.', 'booking-activities' );
		$tags[ '{booking_old_end}' ]	= esc_html__( 'Booking end date and time before reschedule. Displayed in a user-friendly format.', 'booking-activities' );
	}
	
	return apply_filters( 'bookacti_notifications_tags', $tags, $notification_id );
}


/**
 * Get notifications tags and values corresponding to given booking
 * @since 1.2.0
 * @version 1.8.5
 * @param int $booking_id
 * @param string $booking_type 'group' or 'single'
 * @param string $notification_id
 * @param string $locale Optional
 * @return array
 */
function bookacti_get_notifications_tags_values( $booking_id, $booking_type, $notification_id, $locale = 'site' ) {
	// Set default locale to site's locale
	if( $locale === 'site' ) { $locale = bookacti_get_site_locale(); }
	
	$booking_data = array();
	
	$id_filters = $booking_type === 'group' ? array( 'in__booking_group_id' => array( $booking_id ) ) : array( 'in__booking_id' => array( $booking_id ) );
	$filters = bookacti_format_booking_filters( array_merge( array( 'fetch_meta' => true ), $id_filters ) );
	$booking_array = $booking_type === 'group' ? bookacti_get_booking_groups( $filters ) : bookacti_get_bookings( $filters );
	$booking = ! empty( $booking_array[ $booking_id ] ) ? $booking_array[ $booking_id ] : null;
	
	if( $booking ) {
		$datetime_format = apply_filters( 'bookacti_translate_text', bookacti_get_message( 'date_format_long', true ), $locale );
		
		if( $booking_type === 'group' ) {
			$bookings			= bookacti_get_bookings_by_booking_group_id( $booking_id );
			$group_of_events	= bookacti_get_group_of_events( $booking->event_group_id );

			$booking_data[ '{booking_total_qty}' ]	= 0;
			foreach( $bookings as $grouped_booking ) { $booking_data[ '{booking_total_qty}' ] += intval( $grouped_booking->quantity ); }
			$booking_data[ '{booking_title}' ]		= $group_of_events ? $group_of_events->title : '';
			$booking_data[ '{booking_event_id}' ]	= $booking->event_group_id;
			$booking_data[ '{booking_start}' ]		= bookacti_format_datetime( $booking->start, $datetime_format );
			$booking_data[ '{booking_start_raw}' ]	= $booking->start;
			$booking_data[ '{booking_end}' ]		= bookacti_format_datetime( $booking->end, $datetime_format );
			$booking_data[ '{booking_end_raw}' ]	= $booking->end;
			$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&booking_group_id=' . $booking_id . '&event_group_id=' . $group_of_events->id . '&group_by=booking_group' );

		} else {
			$bookings	= array( $booking );
			$event		= bookacti_get_event_by_id( $booking->event_id );
			
			$booking_data[ '{booking_total_qty}' ]	= $booking->quantity;
			$booking_data[ '{booking_title}' ]		= $event ? $event->title : '';
			$booking_data[ '{booking_event_id}' ]	= $booking->event_id;
			$booking_data[ '{booking_start}' ]		= bookacti_format_datetime( $booking->event_start, $datetime_format );
			$booking_data[ '{booking_start_raw}' ]	= $booking->event_start;
			$booking_data[ '{booking_end}' ]		= bookacti_format_datetime( $booking->event_end, $datetime_format );
			$booking_data[ '{booking_end_raw}' ]	= $booking->event_end;
			$booking_data[ '{booking_admin_url}' ]	= esc_url( admin_url( 'admin.php?page=bookacti_bookings' ) . '&booking_id=' . $booking_id . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end );
		}

		$booking_data[ '{booking_id}' ]			= $booking_id;
		$booking_data[ '{booking_title}' ]		= $booking_data[ '{booking_title}' ] ? apply_filters( 'bookacti_translate_text', $booking_data[ '{booking_title}' ], $locale ) : '';
		$booking_data[ '{booking_status}' ]		= bookacti_format_booking_state( $booking->state );
		$booking_data[ '{booking_quantity}' ]	= $booking->quantity;
		$booking_data[ '{booking_list}' ]		= bookacti_get_formatted_booking_events_list( $bookings, 'show', $locale );
		$booking_data[ '{booking_list_raw}' ]	= bookacti_get_formatted_booking_events_list_raw( $bookings, 'show', $locale );
		
		$user_ical_key = '';
		$booking_data[ '{user_locale}' ] = $locale;
		if( $booking->user_id ) { 
			$booking_data[ '{user_id}' ] = $booking->user_id;
			$user = is_numeric( $booking->user_id ) ? get_user_by( 'id', $booking->user_id ) : null;
			if( $user ) { 
				$booking_data[ '{user_firstname}' ]	= ! empty( $user->first_name ) ? $user->first_name : '';
				$booking_data[ '{user_lastname}' ]	= ! empty( $user->last_name ) ? $user->last_name : ''; 
				$booking_data[ '{user_email}' ]		= ! empty( $user->user_email ) ? $user->user_email : '';
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
				$booking_data[ '{user_email}' ]		= ! empty( $booking->user_email ) ? $booking->user_email : '';
				$booking_data[ '{user_phone}' ]		= ! empty( $booking->user_phone ) ? $booking->user_phone : '';
			}
		}
		if( ! $user_ical_key ) {
			$booking_id_param_name = $booking_type === 'group' ? 'booking_group_id' : 'booking_id';
			$booking_data[ '{user_ical_url}' ] = esc_url( home_url( '?action=bookacti_export_booked_events&filename=my-bookings&' . $booking_id_param_name . '=' . $booking_id . '&lang=' . $locale ) );
		}
	}
	
	$default_tags = array_keys( bookacti_get_notifications_tags( $notification_id ) );
	
	// Make sure the array contains all tags 
	$tags = array();
	foreach( $default_tags as $default_tag ) {
		$tags[ $default_tag ] = isset( $booking_data[ $default_tag ] ) ? $booking_data[ $default_tag ] : '';
	}
	
	return apply_filters( 'bookacti_notifications_tags_values', $tags, $booking, $booking_type, $notification_id, $locale );
}


/**
 * Send a notification according to its settings
 * 
 * @since 1.2.1 (was bookacti_send_email in 1.2.0)
 * @version 1.7.0
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
		return;
	}
	
	// Make sure not to run the same cron task multiple times
	if( $allow_async && ! $async ) {
		// If this notification was already sent in the past few minutes, do not send it again
		$notification_unique_key = md5( json_encode( array( $notification_id, $booking_id, $booking_type, $args ) ) );
		$already_sent = get_transient( 'bookacti_notif_' . $notification_unique_key );
		if( $already_sent ) { return; }
		set_transient( 'bookacti_notif_' . $notification_unique_key, 1, 3*60 );
	}
	
	// Get notification settings
	$notification = bookacti_get_notification_settings( $notification_id );
	
	// Replace or add notification settings
	if( ! empty( $args ) && ! empty( $args[ 'notification' ] ) ) {
		$notification = array_merge( $notification, $args[ 'notification' ] );
	}
	
	if( ! $notification || ! $notification[ 'active' ] ) { return false; }
	
	// Change params according to recipients
	$locale = '';
	if( substr( $notification_id, 0, 8 ) === 'customer' ) {
		
		$user_id = $booking_type === 'group' ? bookacti_get_booking_group_owner( $booking_id ) : bookacti_get_booking_owner( $booking_id );
		$user = is_numeric( $user_id ) ? get_user_by( 'id', $user_id ) : null;
		
		// If the user has an account
		if( $user ) {
			$user_data = get_user_by( 'id', $user_id );
			if( $user_data ) {
				$user_email = $user->user_email;

				// Use the user locale to translate the email
				$locale = bookacti_get_user_locale( $user );
			}
		} else if( is_email( $user_id ) ) {
			$user_email = $user_id;
		} else {
			$object_type = $booking_type === 'group' ? 'booking_group' : 'booking';
			$user_email = bookacti_get_metadata( $object_type, $booking_id, 'user_email', true );
			if( ! is_email( $user_email ) ) { $user_email = ''; }
		}
		
		// Fill the recipients fields
		$notification[ 'email' ][ 'to' ] = array( $user_email );
	}
	
	if( ! $locale ) { $locale = bookacti_get_site_locale();	}
	
	$locale = apply_filters( 'bookacti_notification_locale', $locale, $notification_id, $booking_id, $booking_type, $args );
	
	// Temporarily switch locale to site or user default's
	bookacti_switch_locale( $locale );
	
	// Replace tags in message and replace linebreaks with html tags
	$tags = bookacti_get_notifications_tags_values( $booking_id, $booking_type, $notification_id, $locale );
	
	// Replace or add tags values
	if( ! empty( $args ) && ! empty( $args[ 'tags' ] ) ) {
		$tags = array_merge( $tags, $args[ 'tags' ] );
	}
	
	$notification	= apply_filters( 'bookacti_notification_data', $notification, $tags, $locale, $booking_id, $booking_type, $args );
	$tags			= apply_filters( 'bookacti_notification_tags', $tags, $notification, $locale, $booking_id, $booking_type, $args );
	$allow_sending	= apply_filters( 'bookacti_notification_sending_allowed', true, $notification, $tags, $locale, $booking_id, $booking_type, $args );
	
	if( ! $allow_sending ) { bookacti_restore_locale(); return array(); } 
	
	// Send email notification
	$sent = array( 'email' => 0 );
	$sent_email = bookacti_send_email_notification( $notification, $tags, $locale );
	
	if( $sent_email ) {
		$sent[ 'email' ] = count( $notification[ 'email' ][ 'to' ] );
	}
	
	$sent = apply_filters( 'bookacti_send_notifications', $sent, $notification_id, $notification, $tags, $booking_id, $booking_type, $args, $locale );
	
	// Switch locale back to normal
	bookacti_restore_locale();
	
	return $sent;
}

// Hook the asynchronous call and send the notification
add_action( 'bookacti_send_async_notification', 'bookacti_send_notification', 10, 5 );


/**
 * Send an email notification
 * @since 1.2.0
 * @version 1.7.17
 * @param array $notification
 * @param array $tags
 * @param string $locale
 * @return boolean
 */
function bookacti_send_email_notification( $notification, $tags = array(), $locale = 'site' ) {
	// Do not send email notification if it is deactivated or if there are no recipients
	if( ! $notification[ 'active' ] || ! $notification[ 'email' ][ 'active' ] || ! $notification[ 'email' ][ 'to' ] ) { return false; }
	
	// Set default locale to site's locale
	if( $locale === 'site' ) {
		$locale = bookacti_get_site_locale();
	}
	
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
		'headers'	=> $headers,
		'to'		=> $to,
		'subject'	=> $subject,
		'message'	=> $message
	), $notification, $tags, $locale );
	
	$sent = bookacti_send_email( $email_data[ 'to' ], $email_data[ 'subject' ], $email_data[ 'message' ], $email_data[ 'headers' ] );
	
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
 * @param array $to
 * @param string $subject
 * @param string $message
 * @param array $headers
 * @return bool
 */
function bookacti_send_email( $to, $subject, $message, $headers ) {
	
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
	
	$actual_recipients = apply_filters( 'bookacti_send_email_recipients', $recipients, $to, $subject, $message, $headers );
	
	if( ! $actual_recipients ) { return false; }
	
	$sent = wp_mail( $actual_recipients, $subject, $message, $headers );
	
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