<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GLOBAL

/**
 * Check if plugin is active
 * 
 * @param string $plugin_path_and_name
 * @return boolean
 */
function bookacti_is_plugin_active( $plugin_path_and_name ) {
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return is_plugin_active( $plugin_path_and_name );
}


/**
 * Display an admin notice
 * @since 1.7.18
 * @param array $array composed of a type and a message
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_display_admin_notice( $array, $action = '' ) {
	if( empty( $array[ 'type' ] ) )		{ $array[ 'type' ] = 'error'; }
	if( empty( $array[ 'message' ] ) )	{ $array[ 'message' ] = esc_html__( 'An error occurred, please try again.', 'booking-activities' ); }
	$notice = apply_filters( 'bookacti_display_admin_notice_' . $action, $array );
	?>
		<div class='notice is-dismissible bookacti-form-notice notice-<?php echo $notice[ 'type' ]; ?>' ><p><?php echo $notice[ 'message' ]; ?></p></div>
	<?php
}


/**
 * Send a filtered array via json during an ajax process
 * @since 1.5.0
 * @version 1.5.3
 * @param array $array Array to encode as JSON, then print and die.
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json( $array, $action = '' ) {
	if( empty( $array[ 'status' ] ) ) { $array[ 'status' ] = 'failed'; }
	$response = apply_filters( 'bookacti_send_json_' . $action, $array );
	wp_send_json( $response );
}


/**
 * Send a filtered array via json to stop an ajax process running with an invalid nonce
 * @since 1.5.0
 * @version 1.8.10
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json_invalid_nonce( $action = '' ) {
	$return_array = array( 
		'status'	=> 'failed', 
		'error'		=> 'invalid_nonce',
		'action'	=> $action, 
		'message'	=> esc_html__( 'Invalid nonce.', 'booking-activities' ) . ' ' . esc_html__( 'Please reload the page and try again.', 'booking-activities' )
	);
	bookacti_send_json( $return_array, $action );
}


/**
 * Send a filtered array via json to stop a not allowed an ajax process
 * @since 1.5.0
 * @version 1.8.10
 * @param string $action Name of the filter to allow third-party modifications
 */
function bookacti_send_json_not_allowed( $action = '' ) {
	$return_array = array( 
		'status'	=> 'failed', 
		'error'		=> 'not_allowed', 
		'action'	=> $action, 
		'message'	=> esc_html__( 'You are not allowed to do that.', 'booking-activities' )
	);
	bookacti_send_json( $return_array, $action );
}


/**
 * Write logs to log files
 * @version 1.8.0
 * @param string $message
 * @param string $filename
 * @return int
 */
function bookacti_log( $message = '', $filename = 'debug' ) {
	if( is_array( $message ) || is_object( $message ) ) { $message = print_r( $message, true ); }
	if( is_bool( $message ) ) { $message = $message ? 'true' : 'false'; }

	$file = WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/log/' . $filename . '.log'; 

	$time = date( 'Y-m-d H:i:s' );
	$log = $time . ' - ' . $message . PHP_EOL;

	$handle	= fopen( $file, 'a' );

	$write = 0;
	if( $handle !== false ) {
		$write	= fwrite( $handle, $log );
		fclose( $handle );
	}

	return $write;
}


/**
 * Increase the max_execution_time and the memory_limit, and remove the maximum execution time limit
 * @since 1.7.0
 * @param int $time
 * @param string $memory
 */
function bookacti_increase_max_execution_time( $time = 600, $memory = '512M' ) {
	ini_set( 'max_execution_time', $time );
	ini_set( 'memory_limit', $memory );
	set_time_limit( 0 );
}


/**
 * Create a zip
 * @since 1.7.0
 * @param array $files
 * @param string $destination
 * @param boolean $overwrite
 * @param boolean $remove_files
 * @return boolean
 */
function bookacti_create_zip( $files = array(), $destination = '', $overwrite = true, $remove_files = false ) {
	// If the zip file already exists and overwrite is false, return false
	if( file_exists( $destination ) ) { 
		if( ! $overwrite ) { return false; }
		unlink( $destination );
	}

	$valid_files = array();
	// Validate files
	if( is_array( $files ) ) {
		foreach( $files as $file) {
			if( file_exists( $file ) ) { $valid_files[] = $file; }
		}
	}

	if( empty( $valid_files ) ) { return false; }

	// Create the archive
	$zip = new ZipArchive();
	$opened = $zip->open( $destination, ZIPARCHIVE::CREATE );
	if( $opened !== true ) { return false; }

	// Add the files
	foreach( $valid_files as $file ) { $zip->addFile( $file, basename( $file ) ); }
	$zip->close();

	// Check to make sure the zip file exists
	$zip_success = file_exists( $destination );

	// Remove the original files
	if( $zip_success && $remove_files ) {
		foreach( $valid_files as $file ) { unlink( $file ); }
	}

	return $zip_success;
}


/**
 * Extract a zip file to specific directory
 * @since 1.7.0
 * @param string $zip_file
 * @param string $destination Must be an existing folder
 * @return array|false
 */
function bookacti_extract_zip( $zip_file, $destination = '' ) {
	// Make sure that the extract directory exists
	if( ! is_dir( $destination ) ) {
		$uploads_dir = wp_upload_dir();
		$destination = str_replace( '\\', '/', $uploads_dir[ 'basedir' ] );
	}
	$extract_dir = trailingslashit( $destination ) . basename( $zip_file, '.zip' );

	// Make sure that the extract directory is empty
	$base_extract_dir = rtrim( $extract_dir, '/' );
	while( is_dir( $extract_dir ) && count( scandir( $extract_dir ) ) > 2 ) {
		$extract_dir = trailingslashit( $base_extract_dir . '-' . md5( microtime().rand() ) );
	}

	// Check if the zip file can be opened
	$zip = new ZipArchive;
	if( $zip->open( $zip_file ) !== true ) { return false; }

	// Extract the files
	$zip->extractTo( $extract_dir );
	$zip->close();

	$archives_handle = opendir( $extract_dir );
	if( ! $archives_handle ) { return false; }

	// Build an array of extracted file
	$files = array();
	while( false !== ( $filename = readdir( $archives_handle ) ) ) {
		if( $filename == '.' || $filename == '..' ) { continue; }
		$files[] = trailingslashit( $extract_dir ) . $filename;
	}

	return $files;
}


/**
 * Get a substring between two specific strings
 * @since 1.7.10
 * @param string $string
 * @param string $start
 * @param string $end
 * @return string
 */
function bookacti_get_string_between( $string, $start, $end ) {
	$string	= ' ' . $string;
	$ini	= strpos( $string, $start );

	if( $ini == 0 ) { return ''; }

	$ini += strlen( $start );
	$len = strpos( $string, $end, $ini ) - $ini;

	return substr( $string, $ini, $len );
}


/**
 * Recursively remove values in an associative array by keys
 * @since 1.7.10
 * @param array $array
 * @param array $recursive_keys
 * @return array
 */
function bookacti_recursive_unset( $array, $recursive_keys ) {
	foreach( $recursive_keys as $key => $value ) {
		if( is_array( $value ) && isset( $array[ $key ] ) && is_array( $array[ $key ] ) ) {
			$array[ $key ] = bookacti_recursive_unset( $array[ $key ], $value );
		}
		else if( is_string( $value ) && isset( $array[ $value ] ) ) {
			unset( $array[ $value ] );
		}
	}
	return $array;
}


/**
 * Encrypt a string
 * @since 1.7.15
 * @param string $string
 * @return string
 */
function bookacti_encrypt( $string ) {
	$secret_key = get_option( 'bookacti_secret_key' );
	$secret_iv = get_option( 'bookacti_secret_iv' );

	if( ! $secret_key ) { update_option( 'bookacti_secret_key', md5( microtime().rand() ) ); }
	if( ! $secret_iv )	{ update_option( 'bookacti_secret_iv', md5( microtime().rand() ) ); }

	$output = $string;
	$encrypt_method = 'AES-256-CBC';
	$key = hash( 'sha256', $secret_key );
	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

	if( function_exists( 'openssl_encrypt' ) && version_compare( phpversion(), '5.3.3', '>=' ) ) {
		$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
	}

	if( ! $output ) { return $string; }

	return $output;
}


/**
 * Dencrypt a string
 * @since 1.7.15
 * @param string $string
 * @return string
 */
function bookacti_decrypt( $string ) {
	$secret_key = get_option( 'bookacti_secret_key' );
	$secret_iv = get_option( 'bookacti_secret_iv' );

	if( ! $secret_key || ! $secret_iv ) { return $string; }

	$output = $string;
	$encrypt_method = 'AES-256-CBC';
	$key = hash( 'sha256', $secret_key );
	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

	if( function_exists( 'openssl_decrypt' ) && version_compare( phpversion(), '5.3.3', '>=' ) ) {
		$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
	}

	if( ! $output ) { return $string; }

	return $output;
}


/**
 * Generate CSV file
 * @since 1.8.0
 * @param array $items
 * @param array $headers
 * @return string
 */
function bookacti_generate_csv( $items, $headers = array() ) {
	if( ! $headers && ! $items ) { return ''; }
	
	// Get headers from first item if not given
	if( ! $headers ) { $items = array_values( $items ); $headers = array_keys( $items[ 0 ] ); }
	if( ! $headers ) { return ''; }
	
	ob_start();

	// Display headers
	$count = 0;
	foreach( $headers as $title ) {
		if( $count ) { echo ','; }
		++$count;
		echo str_replace( ',', '', strip_tags( $title ) );
	}

	// Display rows
	foreach( $items as $item ) {
		echo PHP_EOL;
		$count = 0;
		foreach( $headers as $column_name => $title ) {
			if( $count ) { echo ','; }
			++$count;
			if( ! isset( $item[ $column_name ] ) ) { continue; }
			echo str_replace( ',', '', strip_tags( $item[ $column_name ] ) );
		}
	}

	return ob_get_clean();
}


/**
 * Generate iCal file
 * @since 1.8.0
 * @version 1.8.8
 * @param array $vevents
 * @param array $vcalendar
 * @return string
 */
function bookacti_generate_ical( $vevents, $vcalendar = array() ) {
	if( ! $vevents ) { return ''; }
	
	// Set default vcalendar properties
	$site_url		= home_url();
	$site_url_array	= parse_url( $site_url );
	$site_host		= $site_url_array[ 'host' ];
	
	$timezone		= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
	$timezone_obj	= new DateTimeZone( $timezone );
	$current_time	= new DateTime( 'now', $timezone_obj );
	$current_time->setTimezone( new DateTimeZone( 'UTC' ) );
	$now_formatted	= $current_time->format( 'Ymd\THis\Z' );
	
	$site_name		= get_bloginfo( 'name' );
	$calname		= $site_name;
	/* translators: %1$s is a link to Booking Activities website. %2$s is the site URL. */
	$caldesc		= sprintf( esc_html__( 'This calendar was generated by %1$s from %2$s.' ), 'Booking Activities (https://booking-activities.fr)', $site_name . ' (' . $site_url . ')' );
	
	if( ! empty( $vcalendar[ 'X-WR-CALNAME' ] ) ) { $vcalendar[ 'X-WR-CALNAME' ] .= ' (' . $calname . ')'; }
	if( ! empty( $vcalendar[ 'X-WR-CALDESC' ] ) ) { $vcalendar[ 'X-WR-CALDESC' ] .= ' ' . $caldesc; }
	
	$vcalendar_default = apply_filters( 'bookacti_ical_vcalendar_default', array(
		'PRODID'		=> '-//Booking Activities//Booking Activities Calendar//EN',
		'VERSION'		=> '2.0',
		'CALSCALE'		=> 'GREGORIAN',
		'METHOD'		=> 'PUBLISH',
		'X-WR-CALNAME'	=> $calname,
		'X-WR-TIMEZONE'	=> $timezone,
		'X-WR-CALDESC'	=> $caldesc
	));
	
	foreach( $vcalendar_default as $property => $value ) {
		$vcalendar[ $property ] = isset( $vcalendar[ $property ] ) ? bookacti_sanitize_ical_property( $vcalendar[ $property ], $property ) : $value;
	}
	
	// Compulsory vevent properties
	$vevent_default = array( 'UID' => 0, 'DTSTAMP' => $now_formatted, 'DTSTART' => $now_formatted );
	
	ob_start();
	
	?>
	BEGIN:VCALENDAR
	<?php
		foreach( $vcalendar as $property => $value ) {
			if( $value === '' ) { continue; }
			echo $property . ':' . $value . PHP_EOL;
		}
		do_action( 'bookacti_ical_vcalendar_before', $vevents, $vcalendar );
		
		foreach( $vevents as $vevent ) {
			// Add compulsory properties
			if( empty( $vevent[ 'UID' ] ) ) { ++$vevent_default[ 'UID' ]; }
			$vevent = array_merge( $vevent_default, $vevent );
			$vevent[ 'UID' ] = bookacti_sanitize_ical_property( $vevent[ 'UID' ] . '@' . $site_host, 'UID' );
		?>
			BEGIN:VEVENT
			<?php
				foreach( $vevent as $property => $value ) {
					if( $value === '' ) { continue; }
					echo $property . ':' . $value . PHP_EOL;
				}
				do_action( 'bookacti_ical_vevent_after', $vevent, $vevents, $vcalendar );
			?>
			END:VEVENT
		<?php
		}

		do_action( 'bookacti_ical_vcalendar_after', $vevents, $vcalendar );
	
	?>
	END:VCALENDAR
	<?php
	
	// Remove tabs at the beginning and at the end of each new lines
	return preg_replace( '/^\t+|\t+$/m', '', ob_get_clean() );
}




// JS variables

/**
 * Get the variables used with javascript
 * @since 1.8.0
 * @version 1.8.10
 * @return array
 */
function bookacti_get_js_variables() {
	$timezone = new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
	$current_datetime = new DateTime( 'now', $timezone );
	$current_datetime_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$can_edit_bookings = current_user_can( 'bookacti_edit_bookings' );
	$messages = bookacti_get_messages();
	
	/**
	 * /!\ 
	 * Don't transtate strings here, read this documentation to learn how to translate booking activities into your language:
	 * https://booking-activities.fr/en/faq/translate-booking-activities-into-my-language/
	 */
	
	$bookacti_localized = array(
		// ERRORS
		'error'								=> esc_html__( 'An error occurred.', 'booking-activities' ),
		'error_select_event'				=> esc_html__( 'You haven\'t selected any event. Please select an event.', 'booking-activities' ),
		'error_corrupted_event'				=> esc_html__( 'There is an inconsistency in the selected events data, please select an event and try again.', 'booking-activities' ),
		/* translators: %1$s is the quantity the user want. %2$s is the available quantity. */
		'error_less_avail_than_quantity'	=> esc_html__( 'You want to make %1$s bookings but only %2$s are available for the selected events. Please choose another event or decrease the quantity.', 'booking-activities' ),
		'error_quantity_inf_to_0'			=> esc_html__( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', 'booking-activities' ),
		'error_not_allowed'					=> esc_html__( 'You are not allowed to do that.', 'booking-activities' ),
		'error_user_not_logged_in'			=> esc_html__( 'You are not logged in. Please create an account and log in first.', 'booking-activities' ),
		'error_password_not_strong_enough'	=> esc_html__( 'Your password is not strong enough.', 'booking-activities' ),

		// OTHERS
		'loading'							=> esc_html__( 'Loading', 'booking-activities' ),
		'one_person_per_booking'			=> esc_html__( 'for one person', 'booking-activities' ),
		/* translators: %1$s is the number of people who can enjoy the activity with one booking */
		'n_people_per_booking'				=> esc_html__( 'for %1$s people', 'booking-activities' ),
		/* translators: This particle is used right after the quantity of bookings. Put the singular here. E.g.: 1 booking . */
		'booking'							=> esc_html__( 'booking', 'booking-activities' ),
		/* translators: This particle is used right after the quantity of bookings. Put the plural here. E.g.: 2 bookings . . */
		'bookings'							=> esc_html__( 'bookings', 'booking-activities' ),

		// VARIABLES
		'ajaxurl'							=> admin_url( 'admin-ajax.php' ),
		'nonce_query_select2_options'		=> wp_create_nonce( 'bookacti_query_select2_options' ),

		'fullcalendar_locale'				=> bookacti_convert_wp_locale_to_fc_locale( bookacti_get_current_lang_code( true ) ),
		'current_lang_code'					=> bookacti_get_current_lang_code(),
		'current_locale'					=> bookacti_get_current_lang_code( true ),

		'available_booking_methods'			=> array_keys( bookacti_get_available_booking_methods() ),

		'event_tiny_height'					=> apply_filters( 'bookacti_event_tiny_height', 30 ),
		'event_small_height'				=> apply_filters( 'bookacti_event_small_height', 75 ),
		'event_narrow_width'				=> apply_filters( 'bookacti_event_narrow_width', 70 ),
		'event_wide_width'					=> apply_filters( 'bookacti_event_wide_width', 250 ),

		'started_events_bookable'			=> bookacti_get_setting_value( 'bookacti_general_settings',	'started_events_bookable' ) ? true : false,
		'started_groups_bookable'			=> bookacti_get_setting_value( 'bookacti_general_settings',	'started_groups_bookable' ) ? true : false,
		'event_load_interval'				=> bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ),
		'default_view_threshold'			=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_calendar_view_threshold' ),
		'bookings_tooltip_mouseover_timeout'=> 250,

		'date_format'						=> apply_filters( 'bookacti_translate_text', $messages[ 'date_format_short' ][ 'value' ] ),
		'date_format_long'					=> apply_filters( 'bookacti_translate_text', $messages[ 'date_format_long' ][ 'value' ] ),
		'time_format'						=> apply_filters( 'bookacti_translate_text', $messages[ 'time_format' ][ 'value' ] ),
		'dates_separator'					=> apply_filters( 'bookacti_translate_text', $messages[ 'dates_separator' ][ 'value' ] ),
		'date_time_separator'				=> apply_filters( 'bookacti_translate_text', $messages[ 'date_time_separator' ][ 'value' ] ),

		'single_event'						=> apply_filters( 'bookacti_translate_text', $messages[ 'choose_group_dialog_single_event' ][ 'value' ] ),
		'selected_event'					=> apply_filters( 'bookacti_translate_text', $messages[ 'selected_event' ][ 'value' ] ),
		'selected_events'					=> apply_filters( 'bookacti_translate_text', $messages[ 'selected_events' ][ 'value' ] ),
		'avail'								=> apply_filters( 'bookacti_translate_text', $messages[ 'avail' ][ 'value' ] ),
		'avails'							=> apply_filters( 'bookacti_translate_text', $messages[ 'avails' ][ 'value' ] ),

		'dialog_button_ok'                  => esc_html__( 'OK', 'booking-activities' ),
		'dialog_button_cancel'				=> apply_filters( 'bookacti_translate_text', $messages[ 'cancel_dialog_button' ][ 'value' ] ),
		'dialog_button_cancel_booking'		=> apply_filters( 'bookacti_translate_text', $messages[ 'cancel_booking_dialog_button' ][ 'value' ] ),
		'dialog_button_reschedule'			=> apply_filters( 'bookacti_translate_text', $messages[ 'reschedule_dialog_button' ][ 'value' ] ),
		'dialog_button_refund'				=> $can_edit_bookings ? esc_html_x( 'Refund', 'Button label to trigger the refund action', 'booking-activities' ) : apply_filters( 'bookacti_translate_text', $messages[ 'refund_dialog_button' ][ 'value' ] ),

		'plugin_path'						=> plugins_url() . '/' . BOOKACTI_PLUGIN_NAME,
		'is_admin'							=> is_admin(),
		'current_user_id'					=> get_current_user_id(),
		'current_time'						=> $current_datetime->format( 'Y-m-d H:i:s' ),

		'calendar_localization'				=> bookacti_get_setting_value( 'bookacti_messages_settings', 'calendar_localization' ),
		'wp_time_format'					=> get_option( 'time_format' ),
		'wp_start_of_week'					=> get_option( 'start_of_week' ),
	);
	
	// Strings for backend only
	if( is_admin() ) { 
		$bookacti_localized_backend = array(
			'nonce_dismiss_5stars_rating_notice'=> wp_create_nonce( 'bookacti_dismiss_5stars_rating_notice' ),
			'admin_url'							=> admin_url(),
			'is_qtranslate'						=> bookacti_get_translation_plugin() === 'qtranslate',
			'utc_offset'						=> intval( $timezone->getOffset( $current_datetime_utc ) ),
			'create_new'						=> esc_html__( 'Create new', 'booking-activities' ),
			'edit_id'							=> esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ),
			'dialog_button_generate_link'		=> esc_html__( 'Generate export link', 'booking-activities' ),
			'dialog_button_reset'				=> esc_html__( 'Reset', 'booking-activities' ),
			'dialog_button_delete'				=> esc_html__( 'Delete', 'booking-activities' ),
			'error_time_format'					=> esc_html__( 'The time format should be HH:mm where "HH" represents hours and "mm" minutes.', 'booking-activities' ),
												/* translators: %1$s = "At the latest". %2$s = "At the earliest". */
			'error_availability_period'			=> sprintf( esc_html__( 'The "%1$s" delay must be higher than the "%2$s" delay.', 'booking-activities' ), 
					esc_html__( 'At the earliest', 'booking-activities' ), 
					esc_html__( 'At the latest', 'booking-activities' ) ),
												/* translators: %1$s = "Opening". %2$s = "Closing". */
			'error_closing_before_opening'		=> sprintf( esc_html__( 'The "%1$s" date must be prior to the "%2$s" date.', 'booking-activities' ), 
					esc_html__( 'Opening', 'booking-activities' ), 
					esc_html__( 'Closing', 'booking-activities' ) ),
			'nonce_get_booking_rows'			=> wp_create_nonce( 'bookacti_get_booking_rows' )
		);

		// Strings for calendar editor only
		if( bookacti_is_booking_activities_screen( 'booking-activities_page_bookacti_calendars' ) ) {
			$calendar_editor_strings = array(
				'dialog_button_create_activity'		=> esc_html__( 'Create Activity', 'booking-activities' ),
				'dialog_button_import_activity'		=> esc_html__( 'Import Activity', 'booking-activities' ),
				/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event in order to edit it independently. 'Unbind selected' is a button that isolate the event the user clicked on. */
				'dialog_button_unbind_selected'		=> esc_html__( 'Unbind Selected', 'booking-activities' ),
				/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event  in order to edit it independently. 'Unbind booked' is a button that split the repeating event in two : one repeating event holding all the booked events (restricted edition), and the other holding the events without bookings (fully editable). */
				'dialog_button_unbind_all_booked'	=> esc_html__( 'Unbind Booked', 'booking-activities' ),
				/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event  in order to edit it independently. 'Unbind' is a button that open a dialog where the user can choose wether to unbind the selected event, all events or booked events. */
				'dialog_button_unbind'				=> esc_html__( 'Unbind', 'booking-activities' ),

				'error_fill_field'                  => esc_html__( 'Please fill this field.', 'booking-activities' ),
				'error_invalid_value'               => esc_html__( 'Please select a valid value.', 'booking-activities' ),
				'error_day_end_before_begin'		=> esc_html__( 'Day end time must be after day start time.', 'booking-activities' ),
				'error_repeat_period_not_set'		=> esc_html__( 'The repetition period is not set.', 'booking-activities' ),
				'error_repeat_end_before_begin'     => esc_html__( 'The repetition period cannot end before it started.', 'booking-activities' ),
				'error_repeat_start_before_template'=> esc_html__( 'The repetition period should not start before the beginning date of the calendar.', 'booking-activities' ),
				'error_repeat_end_after_template'   => esc_html__( 'The repetition period should not end after the end date of the calendar.', 'booking-activities' ),
				'error_days_sup_to_365'             => esc_html__( 'The number of days should be between 0 and 365.', 'booking-activities' ),
				'error_hours_sup_to_23'             => esc_html__( 'The number of hours should be between 0 and 23.', 'booking-activities' ),
				'error_minutes_sup_to_59'           => esc_html__( 'The number of minutes should be between 0 and 59.', 'booking-activities' ),
				'error_activity_duration_is_null'	=> esc_html__( 'The activity duration should not be null.', 'booking-activities' ),
				'error_less_avail_than_bookings'    => esc_html__( "You can't set less available bookings than it has already on one of the occurrence of this event.", 'booking-activities' ),
				'error_booked_events_out_of_period' => esc_html__( 'The repetition period must include all booked occurrences.', 'booking-activities' ),
				'error_event_not_btw_from_and_to'   => esc_html__( 'The selected event should be included in the period in which it will be repeated.', 'booking-activities' ),
				'error_freq_not_allowed'            => esc_html__( 'Error: The repetition frequency is not a valid value.', 'booking-activities' ),
				'error_excep_not_btw_from_and_to'   => esc_html__( 'Exception dates should be included in the repetition period.', 'booking-activities' ),
				'error_excep_duplicated'            => esc_html__( 'Exceptions should all have a different date.', 'booking-activities' ),
				'error_set_excep_on_booked_occur'   => esc_html__( 'Warning: this occurrence is booked.', 'booking-activities' ),
				'error_no_templates_for_activity'	=> esc_html__( 'The activity must be bound to at least one calendar.', 'booking-activities' ),
				'error_select_at_least_two_events'	=> esc_html__( 'You must select at least two events.', 'booking-activities' ),
				'error_edit_locked_event'           => esc_html__( 'This event is booked, you cannot move it nor change its duration.', 'booking-activities' ),
				'error_no_template_selected'        => esc_html__( 'You must select a calendar first.', 'booking-activities' ),
			);
			$bookacti_localized_backend = array_merge( $bookacti_localized_backend, $calendar_editor_strings );
		}
		$bookacti_localized = array_merge( $bookacti_localized, $bookacti_localized_backend );
	}

	return apply_filters( 'bookacti_translation_array', $bookacti_localized, $messages ); 
}




// ADD-ONS

/**
 * Get the active Booking Activities add-ons
 * @since 1.7.14
 * @version 1.8.10
 * @param string $prefix
 * @param array $exclude
 */
function bookacti_get_active_add_ons( $prefix = '', $exclude = array( 'balau' ) ) {
	$add_ons_data = bookacti_get_add_ons_data( $prefix, $exclude );

	$active_add_ons = array();
	foreach( $add_ons_data as $add_on_prefix => $add_on_data ) {
		$add_on_path = $add_on_data[ 'plugin_name' ] . '/' . $add_on_data[ 'plugin_name' ] . '.php';
		if( bookacti_is_plugin_active( $add_on_path ) ) {
			$active_add_ons[ $add_on_prefix ] = $add_on_data;
		}
	}

	return $active_add_ons;
}


/**
 * Get add-on data by prefix
 * @since 1.7.14
 * @version 1.8.10
 * @param string $prefix
 * @param array $exclude
 * @return array
 */
function bookacti_get_add_ons_data( $prefix = '', $exclude = array( 'balau' ) ) {
	$addons_data = array( 
		'badp'	=> array( 
			'title'			=> 'Display Pack', 
			'slug'			=> 'display-pack', 
			'plugin_name'	=> 'ba-display-pack', 
			'end_of_life'	=> '', 
			'download_id'	=> 482,
			'min_version'	=> '1.4.11'
		),
		'banp'	=> array( 
			'title'			=> 'Notification Pack', 
			'slug'			=> 'notification-pack', 
			'plugin_name'	=> 'ba-notification-pack', 
			'end_of_life'	=> '', 
			'download_id'	=> 1393,
			'min_version'	=> '1.2.4'
		),
		'bapap' => array( 
			'title'			=> 'Prices and Credits', 
			'slug'			=> 'prices-and-credits', 
			'plugin_name'	=> 'ba-prices-and-credits', 
			'end_of_life'	=> '', 
			'download_id'	=> 438,
			'min_version'	=> '1.4.16'
		),
		'baaf' => array( 
			'title'			=> 'Advanced Forms', 
			'slug'			=> 'advanced-forms', 
			'plugin_name'	=> 'ba-advanced-forms', 
			'end_of_life'	=> '', 
			'download_id'	=> 2705,
			'min_version'	=> '1.2.13'
		),
		'baofc'	=> array( 
			'title'			=> 'Order for Customers', 
			'slug'			=> 'order-for-customers', 
			'plugin_name'	=> 'ba-order-for-customers', 
			'end_of_life'	=> '', 
			'download_id'	=> 436,
			'min_version'	=> '1.2.14'
		),
		'balau' => array( 
			'title'			=> 'Licenses & Updates', 
			'slug'			=> 'licenses-and-updates', 
			'plugin_name'	=> 'ba-licenses-and-updates', 
			'end_of_life'	=> '',
			'download_id'	=> 880,
			'min_version'	=> '1.1.11'
		),
		'bapos' => array( 
			'title'			=> 'Points of Sale', 
			'slug'			=> 'points-of-sale', 
			'plugin_name'	=> 'ba-points-of-sale', 
			'end_of_life'	=> '2021-04-30 23:59:59', // This add-on has been discontinued
			'download_id'	=> 416,
			'min_version'	=> '1.0.15'
		)
	);
	
	// Exclude undesired add-ons
	if( $exclude ) { $addons_data = array_intersect_key( $addons_data, array_flip( $exclude ) ); }
	
	if( ! $prefix ) { return $addons_data; }

	return isset( $addons_data[ $prefix ] ) ? $addons_data[ $prefix ] : array();
}




// LOCALE

/**
 * Detect current language with Qtranslate-XT or WPML
 * @version 1.8.5
 * @param boolean $with_locale
 * @return string 
 */
function bookacti_get_current_lang_code( $with_locale = false ) {
	$locale		= get_locale();
	$lang_code	= $with_locale ? $locale : substr( $locale, 0, strpos( $locale, '_' ) );

	if( bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' ) || bookacti_is_plugin_active( 'qtranslate-xt/qtranslate.php' ) ) {
		if( function_exists( 'qtranxf_getLanguage' ) ) {
			$lang_code = qtranxf_getLanguage();
			if( $with_locale ) {
				global $q_config;
				foreach( $q_config[ 'locale' ] as $code => $locale ) {
					if( $code === $lang_code ) { $lang_code = $locale; break; }
				}
			}
		}
	} else if ( bookacti_is_plugin_active( 'wpml/wpml.php' ) ) {
		$lang_code = apply_filters( 'wpml_current_language', NULL );
		if( $with_locale ) {
			$languages = apply_filters( 'wpml_active_languages', NULL );
			foreach( $languages as $l ) {
				if( $l[ 'active' ] ) { $lang_code = $l[ 'default_locale' ]; break; }
			}
		}
	}
	
	if( ! $lang_code ) {
		$lang_code = $with_locale ? 'en_US' : 'en';
	}
	
	return apply_filters( 'bookacti_current_lang_code', $lang_code, $with_locale );
}


/**
 * Get current translation plugin identifier
 * @version 1.8.5
 * @return string
 */
function bookacti_get_translation_plugin() {
	$translation_plugin = '';
	
	if( bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' ) || bookacti_is_plugin_active( 'qtranslate-xt/qtranslate.php' ) ) {
		$translation_plugin = 'qtranslate';
	} else if ( bookacti_is_plugin_active( 'wpml/wpml.php' ) ) {
		$translation_plugin = 'wpml';
	}
	
	return apply_filters( 'bookacti_translation_plugin', $translation_plugin );
}


/**
 * Apply bookacti_translate_text filters to a text
 * @since 1.7.0
 * @param string $text
 * @param string $lang
 * @return string
 */
function bookacti_translate_text( $text, $lang = '' ) {
	return apply_filters( 'bookacti_translate_text', $text, $lang );
}


// Translate text with qTranslate-XT
if( bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' ) || bookacti_is_plugin_active( 'qtranslate-xt/qtranslate.php' ) ) {
	/**
	 * Translate a string into the desired language (default to current site language)
	 * 
	 * @version 1.2.0
	 * @param string $text
	 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
	 * @return string
	 */
	function bookacti_translate_text_with_qtranslate( $text, $lang = null ) {
		if( $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ) { 
			$lang = substr( $lang, 0, strpos( $lang, '_' ) );
		}
		return apply_filters( 'translate_text', $text, $lang );
	}
	add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_qtranslate', 10, 2 );
}


/* 
 * Get user locale, and default to site or current locale
 * @since 1.2.0
 * @version 1.8.5
 * @param int|WP_User $user_id
 * @param string $default 'current' or 'site'
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function bookacti_get_user_locale( $user_id, $default = 'current', $country_code = true ) {
	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	} elseif ( $user_id instanceof WP_User ) {
		$user = $user_id;
	} elseif ( $user_id && is_numeric( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
	}

	if( ! $user ) { $locale = get_locale(); }
	else {
		if( $default === 'site' ) {
			// Get user locale
			$locale = strval( $user->locale );
			// If not set, get site default locale
			if( ! $locale ) {
				$alloptions	= wp_load_alloptions();
				$locale		= $alloptions[ 'WPLANG' ] ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
			}
		} else {
			// Get user locale, if not set get current locale
			$locale = $user->locale ? strval( $user->locale ) : get_locale();
		}
	}

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'bookacti_user_locale', $locale, $user_id, $default, $country_code );
}


/* 
 * Get site locale, and default to site or current locale
 * @since 1.2.0
 * @version 1.8.10
 * @param string $default 'current' or 'site'
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function bookacti_get_site_locale( $default = 'site', $country_code = true ) {
	// Get raw site locale, or current locale by default
	$locale = get_locale();

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'bookacti_site_locale', $locale, $default, $country_code );
}


/**
 * Switch Booking Activities locale
 * @since 1.2.0
 * @version 1.8.0
 * @param string $locale
 * @return boolean
 */
function bookacti_switch_locale( $locale ) {
	if( ! function_exists( 'switch_to_locale' ) ) { return false; }
	
	// Convert lang code to locale
	if( strpos( $locale, '_' ) === false ) {
		$len = strlen( $locale );
		$has_locale = false;
		$available_locales = get_available_languages();
		foreach( $available_locales as $available_locale ) {
			if( substr( $available_locale, 0, $len ) === $locale ) { $locale = $available_locale; $has_locale = true; break; }
		}
		if( ! $has_locale ) { return false; }
	}

	$switched = switch_to_locale( $locale );

	if( $switched ) {
		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', function() use ( &$locale ) { return $locale; } );
		// Load textdomain on bookacti_locale_switched
		do_action( 'bookacti_locale_switched', $locale );
	}
	
	return $switched;
}


/**
 * Switch Booking Activities locale back to the original
 * @since 1.2.0
 */
function bookacti_restore_locale() {
	if( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		bookacti_load_textdomain();
	}
}


/**
 * Get FullCalendar supported locale
 * @since 1.5.2
 * @version 1.8.5
 * @return array
 */
function bookacti_get_fullcalendar_supported_locales() {
	return apply_filters( 'bookacti_fullcalendar_locales', array( 
		'af', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'ar', 'be', 'bg', 'bs', 'ca', 'cs', 
		'da', 'de-at', 'de-ch', 'de', 'el', 'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-nz', 'es-do', 'es-us', 'es', 'et', 'eu', 'fa', 'fi', 'fr-ca', 'fr-ch', 'fr', 
		'gl', 'he', 'hi', 'hr', 'hu', 'id', 'is', 'it', 
		'ja', 'ka', 'kk', 'ko', 'lb', 'lt', 'lv', 
		'mk', 'ms-my', 'ms', 'nb', 'nl-be', 'nl', 'nn', 
		'pl', 'pt-br', 'pt', 'ro', 'ru', 
		'sk', 'sl', 'sq', 'sr-cyrl', 'sr', 'sv', 'th', 'tr', 'uk', 
		'vi', 
		'zh-cn', 'zh-hk', 'zh-tw' 
	));
}


/**
 * Convert a WP formatted locale to the closest available FullCalendar locale
 * @since 1.5.2
 * @param string $wp_locale
 * @return string
 */
function bookacti_convert_wp_locale_to_fc_locale( $wp_locale = false ) {
	if( ! $wp_locale ) { $wp_locale = bookacti_get_site_locale(); }

	// Format the locale like FC locale formatting
	$fc_locale = $wp_locale;

	// Keep these formats "lang_COUNTRY" or "lang" only
	$pos = strpos( $wp_locale, '_', strpos( $wp_locale, '_' ) + 1 );
	if( $pos ) { $fc_locale = substr( $wp_locale, 0, $pos ); }

	// Replace _ by - and use lowercase only
	$fc_locale = strtolower( str_replace( '_', '-', $fc_locale ) ); 

	// Check if the locale exists
	$fc_locales = bookacti_get_fullcalendar_supported_locales();
	if( ! in_array( $fc_locale, $fc_locales, true ) ) {
		// Keep only the lang code
		$fc_locale = strstr( $wp_locale, '_', true );
		// Default to english if the locale doesn't exist at all
		if( ! in_array( $fc_locale, $fc_locales, true ) ) {
			$fc_locale = 'en';
		}
	}
	return apply_filters( 'bookacti_fullcalendar_locale', $fc_locale, $wp_locale );
}




// FORMS

/**
 * Display fields
 * @since 1.5.0
 * @version 1.8.0
 * @param array $args
 */
function bookacti_display_fields( $fields, $args = array() ) {
	if( empty( $fields ) || ! is_array( $fields ) )	{ return; }

	// Format parameters
	if( ! isset( $args[ 'hidden' ] ) || ! is_array( $args[ 'hidden' ] ) )	{ $args[ 'hidden' ] = array(); }
	if( ! isset( $args[ 'prefix' ] ) || ! is_string( $args[ 'prefix' ] ) )	{ $args[ 'prefix' ] = ''; }

	foreach( $fields as $field_name => $field ) {
		if( empty( $field[ 'type' ] ) ) { continue; }

		if( is_numeric( $field_name ) && ! empty( $field[ 'name' ] ) ) { $field_name = $field[ 'name' ]; }
		if( empty( $field[ 'name' ] ) ) { $field[ 'name' ] = $field_name; }
		$field[ 'name' ]	= ! empty( $args[ 'prefix' ] ) ? $args[ 'prefix' ] . '[' . $field_name . ']' : $field[ 'name' ];
		$field[ 'id' ]		= empty( $field[ 'id' ] ) ? 'bookacti-' . $field_name : $field[ 'id' ];
		$field[ 'hidden' ]	= in_array( $field_name, $args[ 'hidden' ], true ) ? 1 : 0;
		
		$wrap_class = '';
		if( ! empty( $field[ 'hidden' ] ) )			{ $wrap_class .= ' bookacti-hidden-field'; } 
		if( $field[ 'type' ] === 'select_items' )	{ $wrap_class .= ' bookacti-items-container'; } 
		
		// If custom type, call another function to display this field
		if( $field[ 'type' ] === 'custom' ) {
			do_action( 'bookacti_display_custom_field', $field, $field_name );
			continue;
		}
		
		// Else, display standard field
		?>
		<div class='bookacti-field-container <?php echo $wrap_class; ?>' id='<?php echo $field[ 'id' ] . '-container'; ?>'>
		<?php 
			// Display field title
			if( ! empty( $field[ 'title' ] ) ) { 
				$fullwidth = ! empty( $field[ 'fullwidth' ] ) || in_array( $field[ 'type' ], array( 'checkboxes', 'select_items', 'editor' ), true );
			?>
				<label for='<?php echo esc_attr( sanitize_title_with_dashes( $field[ 'id' ] ) ); ?>' class='<?php if( $fullwidth ) { echo 'bookacti-fullwidth-label'; } ?>'>
					<?php echo $field[ 'title' ]; if( $fullwidth ) { bookacti_help_tip( $field[ 'tip' ] ); unset( $field[ 'tip' ] ); } ?>
				</label>
			<?php
			}
			
			// Display field
			bookacti_display_field( $field ); 
		?>
		</div>
	<?php
	}
}


/**
 * Display various fields
 * @since 1.2.0
 * @version 1.8.7
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'tip', 'required']
 */
function bookacti_display_field( $args ) {
	$args = bookacti_format_field_args( $args );
	if( ! $args ) { return; }

	// Display field according to type

	// TEXT & NUMBER
	if( in_array( $args[ 'type' ], array( 'text', 'hidden', 'number', 'date', 'time', 'email', 'tel', 'password', 'file', 'color' ), true ) ) {
	?>
		<input	type='<?php echo esc_attr( $args[ 'type' ] ); ?>' 
				name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				value='<?php echo esc_attr( $args[ 'value' ] ); ?>' 
				autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			<?php if( ! in_array( $args[ 'type' ], array( 'hidden', 'file' ) ) ) { ?>
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>' 
			<?php } 
			if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ), true ) ) { ?>
				min='<?php echo esc_attr( $args[ 'options' ][ 'min' ] ); ?>' 
				max='<?php echo esc_attr( $args[ 'options' ][ 'max' ] ); ?>'
				step='<?php echo esc_attr( $args[ 'options' ][ 'step' ] ); ?>'
			<?php }
			if( $args[ 'type' ] === 'number' && is_int( $args[ 'options' ][ 'step' ] ) ) { ?>
				onkeypress='return event.charCode >= 48 && event.charCode <= 57'
			<?php }
			if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; }
			if( $args[ 'type' ] === 'file' && $args[ 'multiple' ] ) { echo ' multiple'; }
			if( $args[ 'required' ] ) { echo ' required'; } ?>
		/>
	<?php if( $args[ 'label' ] ) { ?>
		<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo $args[ 'label' ]; ?>
		</label>
	<?php
		}
	}

	// DURATION
	if( $args[ 'type' ] === 'duration' ) {
		// Convert value from seconds
		$duration = is_numeric( $args[ 'value' ] ) ? bookacti_format_duration( $args[ 'value' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$step = is_numeric( $args[ 'options' ][ 'step' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'step' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$min = is_numeric( $args[ 'options' ][ 'min' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'min' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		$max = is_numeric( $args[ 'options' ][ 'max' ] ) ? bookacti_format_duration( $args[ 'options' ][ 'max' ], 'array' ) : array( 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => '' );
		?>
		<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='<?php echo esc_attr( $args[ 'value' ] ); ?>' id='<?php echo esc_attr( $args[ 'id' ] ); ?>' class='bookacti-input bookacti-duration-value <?php echo esc_attr( $args[ 'class' ] ); ?>'/>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'days' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-days'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo ! empty( $min[ 'days' ] ) ? max( 0, $min[ 'days' ] ) : 0; ?>' 
					max='<?php echo ! empty( $max[ 'days' ] ) ? min( 99999, $max[ 'days' ] ) : 99999; ?>' 
					step='<?php echo ! empty( $step[ 'days' ] ) ? max( 1, $step[ 'days' ] ) : 1; ?>'
					placeholder='365' data-unit='day' onkeypress='return event.charCode >= 48 && event.charCode <= 57'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-days'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'day', 'days', 2, 'booking-activities' ) ); ?></label>
		</div>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'hours' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-hours'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo empty( $min[ 'days' ] ) && ! empty( $min[ 'hours' ] ) ? max( 0, $min[ 'hours' ] ) : 0; ?>' 
					max='<?php echo empty( $max[ 'days' ] ) && ! empty( $max[ 'hours' ] ) ? min( 23, $max[ 'hours' ] ) : 23; ?>' 
					step='<?php echo empty( $step[ 'days' ] ) && ! empty( $step[ 'hours' ] ) ? max( 1, $step[ 'hours' ] ) : 1; ?>'
					placeholder='23' data-unit='hour' onkeypress='return event.charCode >= 48 && event.charCode <= 57'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-hours'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'hour', 'hours', 2, 'booking-activities' ) ); ?></label>
		</div>
		<div class='bookacti-duration-field-container'>
			<input type='number' value='<?php echo esc_attr( $duration[ 'minutes' ] ); ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '-minutes'; ?>' class='bookacti-input bookacti-duration-field'
					min='<?php echo empty( $min[ 'days' ] ) && empty( $min[ 'hours' ] ) && ! empty( $min[ 'minutes' ] ) ? max( 0, $min[ 'minutes' ] ) : 0; ?>' 
					max='<?php echo empty( $max[ 'days' ] ) && empty( $max[ 'hours' ] ) && ! empty( $max[ 'minutes' ] ) ? min( 59, $max[ 'minutes' ] ) : 59; ?>' 
					step='<?php echo empty( $step[ 'days' ] ) && empty( $step[ 'hours' ] ) && ! empty( $step[ 'minutes' ] ) ? max( 1, $step[ 'minutes' ] ) : 1; ?>'
					placeholder='59' data-unit='minute' onkeypress='return event.charCode >= 48 && event.charCode <= 57'/>
			<label for='<?php echo esc_attr( $args[ 'id' ] ) . '-minutes'; ?>' class='bookacti-duration-field-label'><?php echo esc_html( _n( 'minute', 'minutes', 2, 'booking-activities' ) ); ?></label>
		</div>
		<?php if( $args[ 'label' ] ) { ?>
		<span><?php echo $args[ 'label' ]; ?></span>
		<?php
		}
	}

	// TEXTAREA
	else if( $args[ 'type' ] === 'textarea' ) {
	?>
		<textarea	
			name=		'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
			id=			'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
			autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
			class=		'bookacti-textarea <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>'
			<?php if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; } ?>
			<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
		><?php echo $args[ 'value' ]; ?></textarea>
	<?php if( $args[ 'label' ] ) { ?>
			<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
				<?php echo $args[ 'label' ]; ?>
			</label>
	<?php
		}
	}

	// SINGLE CHECKBOX (boolean)
	else if( $args[ 'type' ] === 'checkbox' ) {
		bookacti_onoffswitch( esc_attr( $args[ 'name' ] ), esc_attr( $args[ 'value' ] ), esc_attr( $args[ 'id' ] ) );
	}

	// MULTIPLE CHECKBOX
	else if( $args[ 'type' ] === 'checkboxes' ) {
		?>
		<input  name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
				id='<?php echo esc_attr( $args[ 'id' ] ) . '_none'; ?>'
				type='hidden' 
				value='none' />
		<?php
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='bookacti_checkbox <?php if( $i === $count ) { echo 'bookacti_checkbox_last'; } ?>'>
				<input	name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='checkbox' 
						value='<?php echo $option[ 'id' ]; ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
						<?php if( in_array( $option[ 'id' ], $args[ 'value' ], true ) ){ echo 'checked'; } ?>
				/>
			<?php if( ! empty( $option[ 'label' ] ) ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo apply_filters( 'bookacti_translate_text', $option[ 'label' ] ); ?>
				</label>
			<?php
				}
				// Display the tip
				if( ! empty( $option[ 'description' ] ) ) {
					$tip = apply_filters( 'bookacti_translate_text', $option[ 'description' ] );
					bookacti_help_tip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// RADIO
	else if( $args[ 'type' ] === 'radio' ) {
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='bookacti_radio <?php if( $i === $count ) { echo 'bookacti_radio_last'; } ?>'>
				<input	name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='radio' 
						value='<?php echo esc_attr( $option[ 'id' ] ); ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
						<?php if( isset( $args[ 'value' ] ) ) { checked( $args[ 'value' ], $option[ 'id' ], true ); } ?>
						<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
				/>
			<?php if( $option[ 'label' ] ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo apply_filters( 'bookacti_translate_text', $option[ 'label' ] ); ?>
				</label>
			<?php
				}
				// Display the tip
				if( !empty( $option[ 'description' ] ) ) {
					$tip = apply_filters( 'bookacti_translate_text', $option[ 'description' ] );
					bookacti_help_tip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// SELECT
	else if( $args[ 'type' ] === 'select' ) {
		$is_multiple = $args[ 'multiple' ] && ( $args[ 'multiple' ] !== 'maybe' || ( $args[ 'multiple' ] === 'maybe' && count( $args[ 'value' ] ) > 1 ) );
		if( $is_multiple && strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
		if( ! $is_multiple && is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = reset( $args[ 'value' ] ); }
		?>
		<select	name=	'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				id=		'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class=	'bookacti-select <?php echo esc_attr( $args[ 'class' ] ); ?>' 
				<?php if( ! empty( $args[ 'attr' ][ '<select>' ] ) ) { echo $args[ 'attr' ][ '<select>' ]; } ?>
				<?php if( $is_multiple ) { echo 'multiple'; } ?>
				<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
		>
		<?php foreach( $args[ 'options' ] as $option_id => $option_value ) { ?>
			<option value='<?php echo esc_attr( $option_id ); ?>'
					id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option_id ); ?>' 
					<?php if( $args[ 'multiple' ] ) { ?> 
					title='<?php echo esc_html( $option_value ); ?>' 
					<?php } ?>
					<?php if( ! empty( $args[ 'attr' ][ $option_id ] ) ) { echo $args[ 'attr' ][ $option_id ]; } ?>
					<?php	if( $is_multiple ) { selected( true, in_array( $option_id, $args[ 'value' ], true ) ); }
							else { selected( $args[ 'value' ], $option_id ); }?>
			>
					<?php echo esc_html( $option_value ); ?>
			</option>
		<?php } ?>
		</select>
	<?php 
		if( $args[ 'multiple' ] === 'maybe' && count( $args[ 'options' ] ) > 1 ) { ?>
			<span class='bookacti-multiple-select-container' >
				<label for='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' ><span class='dashicons dashicons-<?php echo $is_multiple ? 'minus' : 'plus';?>' title='<?php esc_attr_e( 'Multiple selection', 'booking-activities' ); ?>'></span></label>
				<input type='checkbox' 
					   class='bookacti-multiple-select' 
					   id='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' 
					   data-select-id='<?php echo esc_attr( $args[ 'id' ] ); ?>'
					   style='display:none' <?php checked( $is_multiple ) ?>/>
			</span>
	<?php 
			// Add select multiple values instructions
			if( $args[ 'tip' ] ) {
				/* translators: %s is the "+" icon to click on. */
				$args[ 'tip' ] .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s and use CTRL+Click to pick or unpick a value.', 'booking-activities' ), '<span class="dashicons dashicons-plus"></span>' );
			}
		} 
		if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo apply_filters( 'bookacti_translate_text', $args[ 'label' ] ); ?>
		</label>
	<?php
		}
	}
	
	// SELECT ITEMS
	else if( $args[ 'type' ] === 'select_items' ) { ?>
		<div class='bookacti-add-items-container'>
			<select id='<?php echo $args[ 'id' ]; ?>-add-selectbox' class='bookacti-add-new-items-select-box' >
			<?php 
			$selected_values = array_flip( $args[ 'value' ] );
			foreach( $args[ 'options' ] as $value => $label ) { 
				$disabled = isset( $selected_values[ $value ] ) ? 'disabled style="display:none;"' : '';
			?>
				<option value='<?php echo esc_attr( $value ); ?>' <?php echo $disabled; ?>><?php echo esc_html( $label ); ?></option>
			<?php } ?>
			</select>
			<button type='button' id='<?php echo $args[ 'id' ]; ?>-add-button' class='bookacti-add-items' ><?php esc_html_e( 'Add', 'booking-activities' ); ?></button>
		</div>
		<div class='bookacti-items-list-container' >
			<select name='<?php echo $args[ 'name' ]; ?>' id='<?php echo $args[ 'id' ]; ?>-selectbox' class='bookacti-items-select-box' multiple>
				<?php 
				foreach( $args[ 'value' ] as $value ) {
					$label = ! empty( $args[ 'options' ][ $value ] ) ? $args[ 'options' ][ $value ] : $value;
				?>
					<option value='<?php echo $value ?>' title='<?php echo htmlentities( esc_attr( $label ), ENT_QUOTES ); ?>'><?php echo esc_html( $label ); ?></option>
				<?php } ?>
			</select>
			<button type='button' id='<?php echo $args[ 'id' ]; ?>-remove-button' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', 'booking-activities' ); ?></button>
		</div>
	<?php
	}

	// TINYMCE editor
	else if( $args[ 'type' ] === 'editor' ) {
		wp_editor( $args[ 'value' ], $args[ 'id' ], $args[ 'options' ] );
	}

	// User ID
	else if( $args[ 'type' ] === 'user_id' ) {
		bookacti_display_user_selectbox( $args[ 'options' ] );
	}

	// Display the tip
	if( $args[ 'tip' ] ) {
		bookacti_help_tip( $args[ 'tip' ] );
	}
}


/**
 * Format arguments to diplay a proper field
 * @since 1.2.0
 * @version 1.8.7
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'multiple', 'tip', 'required']
 * @return array|false
 */
function bookacti_format_field_args( $args ) {
	// If $args is not an array, return
	if( ! is_array( $args ) ) { return false; }

	// If fields type or name are not set, return
	if( ! isset( $args[ 'type' ] ) || ! isset( $args[ 'name' ] ) ) { return false; }

	// If field type is not supported, return
	if( ! in_array( $args[ 'type' ], array( 'text', 'hidden', 'email', 'tel', 'date', 'time', 'password', 'number', 'duration', 'checkbox', 'checkboxes', 'select', 'select_items', 'radio', 'textarea', 'file', 'color', 'editor', 'user_id' ) ) ) { 
		return false; 
	}

	$default_args = array(
		'type'			=> '',
		'name'			=> '',
		'label'			=> '',
		'id'			=> '',
		'class'			=> '',
		'placeholder'	=> '',
		'options'		=> array(),
		'attr'			=> '',
		'value'			=> '',
		'multiple'		=> false,
		'tip'			=> '',
		'required'		=> 0,
		'autocomplete'	=> 0
	);

	// Replace empty value by default
	foreach( $default_args as $key => $default_value ) {
		$args[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $default_value;
	}

	// Sanitize id and name
	$args[ 'id' ] = sanitize_title_with_dashes( $args[ 'id' ] );

	// If no id, use name instead
	$args[ 'id' ] = $args[ 'id' ] ? $args[ 'id' ] : sanitize_title_with_dashes( $args[ 'name' ] ) . '-' . rand();

	// Sanitize required
	$args[ 'required' ] = isset( $args[ 'required' ] ) && $args[ 'required' ] ? 1 : 0;
	if( $args[ 'required' ] ) { $args[ 'class' ] .= ' bookacti-required-field'; }

	// Make sure fields with multiple options have 'options' set
	if( in_array( $args[ 'type' ], array( 'checkboxes', 'radio', 'select', 'user_id' ) ) ){
		if( ! $args[ 'options' ] ) { return false; }
		if( ! is_array( $args[ 'attr' ] ) ) { $args[ 'attr' ] = array(); }
	} else {
		if( ! is_string( $args[ 'attr' ] ) ) { $args[ 'attr' ] = ''; }
	}

	// If multiple, make sure name has brackets and value is an array
	if( $args[ 'type' ] === 'select_items' ) { $args[ 'multiple' ] = 1; }
	if( in_array( $args[ 'multiple' ], array( 'true', true, '1', 1 ), true ) ) {
		if( strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
	} else if( $args[ 'multiple' ] && $args[ 'type' ] === 'select' ) {
		$args[ 'multiple' ] = 'maybe';
	}

	// Make sure checkboxes have their value as an array
	if( $args[ 'type' ] === 'checkboxes' || ( $args[ 'multiple' ] && $args[ 'type' ] !== 'file' ) ){
		if( ! is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = array( $args[ 'value' ] ); }
	}

	// Make sure 'number' has min and max
	else if( in_array( $args[ 'type' ], array( 'number', 'date', 'time', 'duration' ) ) ) {
		$args[ 'options' ][ 'min' ] = isset( $args[ 'options' ][ 'min' ] ) ? $args[ 'options' ][ 'min' ] : '';
		$args[ 'options' ][ 'max' ] = isset( $args[ 'options' ][ 'max' ] ) ? $args[ 'options' ][ 'max' ] : '';
		$args[ 'options' ][ 'step' ] = isset( $args[ 'options' ][ 'step' ] ) ? $args[ 'options' ][ 'step' ] : '';
	}

	// Make sure that if 'editor' has options, options is an array
	else if( $args[ 'type' ] === 'editor' ) {
		if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
		$args[ 'options' ][ 'textarea_name' ]	= $args[ 'name' ];
		$args[ 'options' ][ 'editor_class' ]	= $args[ 'class' ];
		$args[ 'options' ][ 'editor_height' ]	= ! empty( $args[ 'height' ] ) ? intval( $args[ 'class' ] ) : 120;
	}

	return $args;
}


/**
 * Display a toggled fieldset with tags list and description
 * @since 1.8.0
 * @param array $args_raw
 */
function bookacti_display_tags_fieldset( $args_raw = array() ) {
	$defaults = array(
		'title' => esc_html__( 'Available tags', 'booking-activities' ),
		'tip' => '',
		'tags' => array(),
		'id' => 'bookacti-tags-' . rand()
	);
	$args = wp_parse_args( $args_raw, $defaults );
?>
	<fieldset id='<?php echo $args[ 'id' ]; ?>-container' class='bookacti-tags-fieldset bookacti-fieldset-no-css'>
		<legend class='bookacti-fullwidth-label'>
			<?php 
				echo $args[ 'title' ];
				if( $args[ 'tip' ] ) { bookacti_help_tip( $args[ 'tip' ] ); }
			?>
			<span class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' for='<?php echo $args[ 'id' ]; ?>' data-show-title='<?php esc_html_e( 'show', 'booking-activities' ); ?>' data-hide-title='<?php esc_html_e( 'hide', 'booking-activities' ); ?>'><?php esc_html_e( 'show', 'booking-activities' ); ?></span>
		</legend>
		<div id='<?php echo $args[ 'id' ]; ?>' class='bookacti-fieldset-toggled' style='display:none;'>
			<?php
				if( $args[ 'tags' ] ) {
					$i = 1;
					$nb = count( $args[ 'tags' ] );
					foreach( $args[ 'tags' ] as $tag => $label ) {
						?>
							<code title='<?php echo esc_attr( $label ); ?>'><?php echo $tag; ?></code>
						<?php
						bookacti_help_tip( $label );
						if( $i < $nb ) { echo '<br/>'; }
						++$i;
					}
				}
			?>
		</div>
	</fieldset>
<?php
}


/**
 * Sanitize text from HTML editor in form fields
 * @since 1.5.2
 * @param string $html
 * @return string
 */
function bookacti_sanitize_form_field_free_text( $html ) {
	$html = wp_kses_post( stripslashes( $html ) );
	// Strip form tags
	$tags = array( 'form', 'input', 'textarea', 'select', 'option', 'output' );
	$html = preg_replace( '#<(' . implode( '|', $tags) . ')(?:[^>]+)?>.*?</\1>#s', '', $html );
	return $html;
}


/**
 * Display help toolbox
 * @version 1.7.12
 * @param string $tip
 */
function bookacti_help_tip( $tip, $echo = true ){
	$tip = "<span class='bookacti-tip-icon bookacti-tip' data-tip='" . esc_attr( $tip ) . "'></span>";
	if( $echo ) { echo $tip; }
	return $tip;
}


/**
 * Create ON / OFF switch
 * @version 1.5.4
 * @param string $name
 * @param string $current_value
 * @param string $id
 * @param boolean $disabled
 */
function bookacti_onoffswitch( $name, $current_value, $id = NULL, $disabled = false ) {

	// Format current value
	$current_value = in_array( $current_value, array( true, 'true', 1, '1', 'on' ), true ) ? '1' : '0';

	$checked = checked( '1', $current_value, false );
	if( is_null ( $id ) || $id === '' || ! $id ) { $id = $name; }

	?>
	<div class="bookacti-onoffswitch <?php if( $disabled ) { echo 'bookacti-disabled'; } ?>">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value='0' class='bookacti-onoffswitch-hidden-input' />
		<input type="checkbox" 
			   name="<?php echo esc_attr( $name ); ?>" 
			   class="bookacti-onoffswitch-checkbox" 
			   id="<?php echo esc_attr( $id ); ?>" 
			   value='1' 
				<?php echo $checked; ?> 
				<?php if( $disabled ) { echo 'disabled'; } ?> 
		/>
		<label class="bookacti-onoffswitch-label" for="<?php echo esc_attr( $id ); ?>">
			<span class="bookacti-onoffswitch-inner"></span>
			<span class="bookacti-onoffswitch-switch"></span>
		</label>
	</div>
	<?php
	if( $disabled ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $current_value ) . '" />'; }
}


/**
 * Create a user selectbox
 * @since 1.3.0
 * @version 1.8.7
 * @param array $raw_args
 * @return string|void
 */
function bookacti_display_user_selectbox( $raw_args ) {
	$defaults = array(
		'allow_tags' => 0, 'allow_clear' => 1, 'allow_current' => 0, 
		'option_label' => array( 'display_name' ), 'ajax' => 1, 'select2' => 1, 'echo' => 1,
		'selected' => 0, 'name' => 'user_id', 'class' => '', 'id' => '',
		'include' => array(), 'exclude' => array(),
		'role' => array(), 'role__in' => array(), 'role__not_in' => array(),
		'meta' => true, 'meta_single' => true,
		'orderby' => 'display_name', 'order' => 'ASC'
	);

	$args = apply_filters( 'bookacti_user_selectbox_args', wp_parse_args( $raw_args, $defaults ), $raw_args );
	
	$is_allowed = current_user_can( 'list_users' ) || current_user_can( 'edit_users' );
	$users = ! $args[ 'ajax' ] && $is_allowed ? bookacti_get_users_data( $args ) : array();
	$args[ 'class' ] = $args[ 'ajax' ] ? 'bookacti-select2-ajax ' . trim( $args[ 'class' ] ) : ( $args[ 'select2' ] ? 'bookacti-select2-no-ajax ' . trim( $args[ 'class' ] ) : trim( $args[ 'class' ] ) );
	
	if( $args[ 'ajax' ] && $args[ 'selected' ] && is_numeric( $args[ 'selected' ] ) && $is_allowed ) {
		$user = get_user_by( 'id', $args[ 'selected' ] );
		if( $user ) { $users[] = $user; }
	}

	ob_start();
	?>
	<input type='hidden' name='<?php echo $args[ 'name' ]; ?>' value='' />
	<select <?php if( $args[ 'id' ] ) { echo 'id="' . $args[ 'id' ] . '"'; } ?> 
		name='<?php echo $args[ 'name' ]; ?>' 
		class='bookacti-user-selectbox <?php echo $args[ 'class' ]; ?>'
		data-tags='<?php echo ! empty( $args[ 'allow_tags' ] ) ? 1 : 0; ?>'
		data-allow-clear='<?php echo ! empty( $args[ 'allow_clear' ] ) ? 1 : 0; ?>'
		data-placeholder='<?php esc_html_e( 'Search for a customer', 'booking-activities' ); ?>'
		data-type='users' >
		<option><!-- Used for the placeholder --></option>
		<?php
			if( $args[ 'allow_current' ] ) {
				$_selected = selected( 'current', $args[ 'selected' ], false );
				?><option value='current' <?php echo $_selected ?> ><?php esc_html_e( 'Current user', 'booking-activities' ); ?></option><?php
			}

			do_action( 'bookacti_add_user_selectbox_options', $args, $users );

			$is_selected = false;
			if( $users ) {
				foreach( $users as $user ) {
					$_selected = selected( $user->ID, $args[ 'selected' ], false );
					if( $_selected ) { $is_selected = true; }

					// Build the option label based on the array
					$label = '';
					foreach( $args[ 'option_label' ] as $show ) {
						// If the key contain "||" display the first not empty value
						if( strpos( $show, '||' ) !== false ) {
							$keys = explode( '||', $show );
							$show = $keys[ 0 ];
							foreach( $keys as $key ) {
								if( ! empty( $user->{ $key } ) ) { $show = $key; break; }
							}
						}

						// Display the value if the key exists, else display the key as is, as a separator
						if( isset( $user->{ $show } ) ) {
							$label .= $user->{ $show };
						} else {
							$label .= $show;
						}
					}
				?>
					<option value='<?php echo $user->ID; ?>' <?php echo $_selected ?> ><?php echo esc_html( $label ); ?></option>
				<?php
				}
			}

			if( $args[ 'allow_tags' ] && $args[ 'selected' ] !== '' && ! $is_selected ) {
				?><option value='<?php echo esc_attr( $args[ 'selected' ] ); ?>' selected="selected"><?php echo esc_html( $args[ 'selected' ] ); ?></option><?php
			}
		?>
	</select>
	<?php
	$output = ob_get_clean();

	if( ! $args[ 'echo' ] ) { return $output; }
	echo $output;
}


/**
 * Display tabs and their content
 * @version 1.8.0
 * @param array $tabs
 * @param string $id
 */
function bookacti_display_tabs( $tabs, $id ) {
	if( ! isset( $tabs ) || ! is_array( $tabs ) || empty( $tabs ) || ! $id || ! is_string( $id ) ) { return; }

	// Sort tabs in the desired order
	usort( $tabs, 'bookacti_sort_array_by_order' );
	?>
	
	<div class='bookacti-tabs'>
		<ul>
		<?php
			// Display tabs
			foreach( $tabs as $i => $tab ) {
				$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;
				?>
				<li class='bookacti-tab-<?php echo esc_attr(  $tab_id ); ?>'>
					<a href='#bookacti-tab-content-<?php echo esc_attr(  $tab_id ); ?>' ><?php echo esc_html( $tab[ 'label' ] ); ?></a>
				</li>
				<?php
			}
		?>
		</ul>
		<?php
			// Display tabs content
			foreach( $tabs as $i => $tab ) {
				$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;
				?>
				<div id='bookacti-tab-content-<?php echo esc_attr( $tab_id ); ?>' class='bookacti-tab-content bookacti-custom-scrollbar'>
				<?php
					if( isset( $tab[ 'callback' ] ) && is_callable( $tab[ 'callback' ] ) ) {
						if( isset( $tab[ 'parameters' ] ) ) {
							call_user_func( $tab[ 'callback' ], $tab[ 'parameters' ] );
						} else {
							call_user_func( $tab[ 'callback' ] );
						}
					}
				?>
				</div>
				<?php
			}
		?>
	</div>
	<?php
}


/**
 * Display a table from a properly formatted array
 * @since 1.7.0
 * @param array $array
 * @return string
 */
function bookacti_display_table_from_array( $array ) {
	if( empty( $array[ 'head' ] ) || empty( $array[ 'body' ] ) ) { return ''; }
	if( ! is_array( $array[ 'head' ] ) || ! is_array( $array[ 'body' ] ) ) { return ''; }

	$column_ids = array_keys( $array[ 'head' ] );
	?>
	<table class='bookacti-options-table'>
		<thead>
			<tr>
			<?php
			foreach( $array[ 'head' ] as $column_id => $column_label ) {
			?>
				<th class='bookacti-column-<?php echo $column_id; ?>'><?php echo is_string( $column_label ) || is_numeric( $column_label ) ? $column_label : $column_id; ?></th>
			<?php
			}
			?>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach( $array[ 'body' ] as $row ) {
		?>
			<tr>
				<?php
				foreach( $column_ids as $column_id ) {
					$column_content = isset( $row[ $column_id ] ) && ( is_string( $row[ $column_id ] ) || is_numeric( $row[ $column_id ] ) ) ? $row[ $column_id ] : '';
				?>
					<td class='bookacti-column-<?php echo $column_id; ?>'><?php echo $column_content; ?></td>
				<?php
				}
			?>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
	<?php
}




// FORMATING AND SANITIZING

/**
 * Check if a string is valid for UTF-8 use
 * @since 1.5.7
 * @param string $string
 * @return boolean
 */
function bookacti_is_utf8( $string ) {
	if( function_exists( 'mb_check_encoding' ) ) {
		if( mb_check_encoding( $string, 'UTF-8' ) ) { 
			return true;
		}
	}
	else if( preg_match( '%^(?:
			[\x09\x0A\x0D\x20-\x7E]            # ASCII
		  | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		  | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		  | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		  | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		  | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		  | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $string ) ) {
		return true;
	}
	return false;
}


/**
 * Use mb_substr if available, else use a regex
 * @since 1.6.0
 * @param string $string
 * @param int $offset
 * @param int|null $length
 * @return string
 */
function bookacti_substr( $string, $offset = 0, $length = null ) {
	$substr = '';
	if( function_exists( 'mb_substr' ) ) {
		$substr = mb_substr( $string, $offset, $length );
	} else {
		$arr = preg_split( '//u', $string );
		$slice = array_slice( $arr, $offset + 1, $length );
		$substr = implode( '', $slice );
	}
	return $substr;
}


/**
 * Sort array of arrays with a ['order'] index
 * 
 * @param array $a
 * @param array $b
 * @return array 
 */
function bookacti_sort_array_by_order( $a, $b ) {
	return $a['order'] - $b['order'];
}


/**
 * Sanitize int ids to array
 * @version 1.7.17
 * @param array|int $ids
 * @return array 
 */
function bookacti_ids_to_array( $ids ) {
	if( is_array( $ids ) ){
		return array_filter( array_unique( array_map( 'intval', $ids ) ) );
	} else if( ! empty( $ids ) ){
		if( is_numeric( $ids ) ) {
			return array( intval( $ids ) );
		}
	}
	return array();
}

/**
 * Sanitize str ids to array
 * @since 1.8.3
 * @param array|string $ids
 * @return array 
 */
function bookacti_str_ids_to_array( $ids ) {
	if( is_array( $ids ) ){
		return array_filter( array_unique( array_map( 'sanitize_title_with_dashes', $ids ) ) );
	} else if( ! empty( $ids ) ){
		if( is_string( $ids ) ) {
			return array( sanitize_title_with_dashes( $ids ) );
		}
	}
	return array();
}


/**
 * Convert an array to string recursively
 * @since 1.6.0
 * @version 1.8.0
 * @param array $array
 * @param int|boolean $display_keys If int, keys will be displayed if >= $level
 * @param int $type "csv" or "ical"
 * @param int $level Used for recursivity for multidimensional arrays. "1" is the first level.
 * @return string
 */
function bookacti_format_array_for_export( $array, $display_keys = false, $type = 'csv', $level = 1 ) {
	if( ! is_array( $array ) ) { return $array; }
	if( empty( $array ) ) { return ''; }
	
	$this_display_keys = $display_keys === true || ( is_numeric( $display_keys ) && $display_keys >= $level );
	$strip = $type === 'csv' ? array( ',', ';', '"' ) : array( ',' );
	
	$i = 0;
	$string = '';
	foreach( $array as $key => $value ) {
		$value = bookacti_maybe_decode_json( maybe_unserialize( $value ) );
		
		if( $this_display_keys || is_array( $value ) ) {								// If keys are not displayed, display values on the same line
			if( $i>0 || $level>1 )	{ $string .= $type === 'csv' ? PHP_EOL : '\n'; }	// Else, one line per value
			if( $level > 1 )		{ $string .= str_repeat( '    ', ($level-1) ); }	// And indent it according to its level in the array (for multidimentional array)
		} else {
			if( $i > 0 )			{ $string .= $type === 'csv' ? '; ' : ', '; }		// Separate each value with the appropriate delimiter
		}
		if( $this_display_keys )	{ $string .= $key . ': '; }							// Display key before value
		
		if( is_array( $value ) )	{ $string .= bookacti_format_array_for_export( $value, $display_keys, $type, $level+1 ); } // Repeat. (for multidimentional array)
		else						{ $string .= str_replace( $strip, '', $value ); }	// Remove forbidden characters
		
		++$i;
	}
	
	// Add double quotes for CSV multiline values
	if( $level === 1 && $type === 'csv' && strpos( $string, PHP_EOL ) !== false ) { $string = '"' . $string . '"'; }
	
	return apply_filters( 'bookacti_format_array_for_export', $string, $array, $display_keys, $type, $level );
}


/**
 * Format datetime to be displayed in a human comprehensible way
 * @version 1.8.6
 * @param string $datetime Date format "Y-m-d H:i:s" is expected
 * @param string $format 
 * @return string
 */
function bookacti_format_datetime( $datetime, $format = '' ) {
	$datetime = bookacti_sanitize_datetime( $datetime );
	if( $datetime ) {
		if( ! $format ) { $format = bookacti_get_message( 'date_format_long' ); }

		// Force timezone to UTC to avoid offsets because datetimes should be displayed regarless of timezones
		$dt = new DateTime( $datetime, new DateTimeZone( 'UTC' ) );
		$timestamp = $dt->getTimestamp();
		if( $timestamp === false ) { return $datetime; }
		
		// Do not use date_i18n() function to force the UTC timezone
		$datetime = apply_filters( 'date_i18n', wp_date( $format, $timestamp, new DateTimeZone( 'UTC' ) ), $format, $timestamp, false );

		// Encode to UTF8 to avoid any bad display of special chars
		if( ! bookacti_is_utf8( $datetime ) ) { $datetime = utf8_encode( $datetime ); }
	}
	return $datetime;
}


/**
 * Check if a string is in a correct datetime format
 * @version 1.8.10
 * @param string $datetime Date format "Y-m-d H:i:s" is expected
 * @return string|false
 */
function bookacti_sanitize_datetime( $datetime ) {
	if( preg_match( '/^\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d$/', $datetime ) 
	||  preg_match( '/^\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d$/', $datetime ) ) {
		$datetime_object = new DateTime( $datetime );

		// Do not allow to set a date after 2037 because of the year 2038 problem
		$datetime_2038 = new DateTime( '2038-01-01' );
		if( $datetime_object > $datetime_2038 ) {
			return '2037-12-31 ' . $datetime_object->format( 'H:i:s' );
		}

		return $datetime;
	}
	return '';
}


if( ! function_exists( 'wp_date' ) ) {
	/**
	 * Backward Compatibility - Retrieves the date, in localized format
	 * @since 1.8.7
	 * @param string $format
	 * @param int $timestamp
	 * @param DateTimeZone $timezone
	 * @return string
	 */
	function wp_date( $format, $timestamp = false, $timezone = false ) {
		return date_i18n( $format, $timestamp, false );
	}
}


/**
 * Check if a string is in a correct date format
 * @version 1.8.0
 * @param string $date Date format Y-m-d is expected
 * @return string 
 */
function bookacti_sanitize_date( $date ) {
	if( preg_match( '/^\d{4}-[01]\d-[0-3]\d$/', $date ) ) {
		$datetime_object = new DateTime( $date );

		// Do not allow to set a date after 2037 because of the year 2038 problem
		$datetime_2038 = new DateTime( '2038-01-01' );
		if( $datetime_object > $datetime_2038 ) {
			return '2037-12-31';
		}

		return $datetime_object->format( 'Y-m-d' );
	}
	return '';
}


/**
 * Convert duration from seconds
 * @since 1.8.0
 * @version 1.8.4
 * @param int $seconds
 * @param string $format Either "iso8601", "timespan" or "array"
 * @return string P%dDT%dH%dM%dS
 */
function bookacti_format_duration( $seconds, $format = 'iso8601' ) {
	$seconds = intval( $seconds );
	$array = array();
    
	$array[ 'days' ] = floor( $seconds / 86400 );
    $seconds = $seconds % 86400;

    $array[ 'hours' ] = floor( $seconds / 3600 );
    $seconds = $seconds % 3600;

    $array[ 'minutes' ] = floor( $seconds / 60 );
    $array[ 'seconds' ] = $seconds % 60;
	
	if( $format === 'array' ) { return $array; }
	
	if( $format === 'timespan' ) { 
		// The timespan format is limited to a INT64 number of seconds
		if( $array[ 'days' ] > 10675198 ) { $array[ 'days' ] = 10675198; }
		
		return sprintf( '%s.%s:%s:%s', str_pad( $array[ 'days' ], 3, '0', STR_PAD_LEFT ), str_pad( $array[ 'hours' ], 2, '0', STR_PAD_LEFT ), str_pad( $array[ 'minutes' ], 2, '0', STR_PAD_LEFT ), str_pad( $array[ 'seconds' ], 2, '0', STR_PAD_LEFT ) );
	}
	
	// The iso8601 format is limited to 12-digit numbers
	if( $array[ 'days' ] > 999999999999 ) { $array[ 'days' ] = 999999999999; }
	
    return sprintf( 'P%dDT%dH%dM%dS', $array[ 'days' ], $array[ 'hours' ], $array[ 'minutes' ], $array[ 'seconds' ] );
}


/**
 * Format a delay in seconds to a user friendly remaining time
 * @since 1.8.6
 * @param int $seconds
 * @param int $precision 1 for days, 2 for hours, 3 for minutes, 4 for seconds
 * @return string
 */
function bookacti_format_delay( $seconds, $precision = 3 ) {
	$time = bookacti_format_duration( $seconds, 'array' );
	$formatted_delay = '';
	
	if( intval( $time[ 'days' ] ) > 0 ) { 
		/* translators: %d is a variable number of days */
		$days_formated = sprintf( _n( '%d day', '%d days', $time[ 'days' ], 'booking-activities' ), $time[ 'days' ] );
		$formatted_delay .= $days_formated;
	}
	if( intval( $time[ 'hours' ] ) > 0 && $precision >= 2 ) { 
		/* translators: %d is a variable number of hours */
		$hours_formated = sprintf( _n( '%d hour', '%d hours', $time[ 'hours' ], 'booking-activities' ), $time[ 'hours' ] );
		$formatted_delay .= ' ' . $hours_formated;
	}
	if( intval( $time[ 'minutes' ] ) > 0 && $precision >= 3 ) { 
		/* translators: %d is a variable number of minutes */
		$minutes_formated = sprintf( _n( '%d minute', '%d minutes', $time[ 'minutes' ], 'booking-activities' ), $time[ 'minutes' ] );
		$formatted_delay .= ' ' . $minutes_formated;
	}
	if( intval( $time[ 'seconds' ] ) > 0 && $precision >= 4 ) { 
		/* translators: %d is a variable number of minutes */
		$seconds_formated = sprintf( _n( '%d second', '%d seconds', $time[ 'seconds' ], 'booking-activities' ), $time[ 'seconds' ] );
		$formatted_delay .= ' ' . $seconds_formated;
	}

	return apply_filters( 'bookacti_formatted_delay', $formatted_delay, $seconds, $precision );
}


/**
 * Sanitize array of dates
 * @since 1.2.0 (replace bookacti_sanitize_exceptions)
 * @version 1.7.13
 * @param array|string $exceptions Date array expected (format "Y-m-d")
 * @return array
 */
function bookacti_sanitize_date_array( $exceptions ) {
	if( ! empty( $exceptions ) ) {
		if( is_array( $exceptions ) ) {
			// Remove entries that do not correspond to a date
			foreach( $exceptions as $i => $exception ) {
				$exceptions[ $i ] = bookacti_sanitize_date( $exception );
				if( ! $exceptions[ $i ] ) { unset( $exceptions[ $i ] ); }
			}
			return $exceptions;
		} else {
			$exceptions = bookacti_sanitize_date( $exceptions );
			if( $exceptions ) {
				return array( $exceptions );
			}
		}
	}
	return array();
}


/**
 * Check if a string is valid JSON
 * 
 * @since 1.1.0
 * @param string $string
 * @return boolean
 */
function bookacti_is_json( $string ) {
	if( ! is_string( $string ) ) { return false; }
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE );
}


/**
 * Decode JSON if it is valid else return self
 * @since 1.6.0
 * @param string $string
 * @param boolean $assoc
 * @return array|$string
 */
function bookacti_maybe_decode_json( $string, $assoc = false ) {
	if( ! is_string( $string ) ) { return $string; }
	$decoded = json_decode( $string, $assoc );
	if( json_last_error() == JSON_ERROR_NONE ) { return $decoded; }
	return $string;
}


/**
 * Sanitize the values of an array
 * @since 1.5.0
 * @version 1.8.0
 * @param array $default_data
 * @param array $raw_data
 * @param array $keys_by_type
 * @param array $sanitized_data
 * @return array
 */
function bookacti_sanitize_values( $default_data, $raw_data, $keys_by_type, $sanitized_data = array() ) {
	// Sanitize the keys-by-type array
	$allowed_types = array( 'int', 'float', 'numeric', 'bool', 'str', 'str_id', 'str_html', 'array', 'datetime', 'date' );
	foreach( $allowed_types as $allowed_type ) {
		if( ! isset( $keys_by_type[ $allowed_type ] ) ) { $keys_by_type[ $allowed_type ] = array(); }
	}

	// Make an array of all keys that will be sanitized
	$keys_to_sanitize = array();
	foreach( $keys_by_type as $type => $keys ) {
		if( ! in_array( $type, $allowed_types, true ) ) { continue; }
		if( ! is_array( $keys ) ) { $keys_by_type[ $type ] = array( $keys ); }
		foreach( $keys as $key ) {
			$keys_to_sanitize[] = $key;
		}
	}

	// Format each value according to its type
	foreach( $default_data as $key => $default_value ) {
		// Do not process keys without types
		if( ! in_array( $key, $keys_to_sanitize, true ) ) { continue; }
		// Skip already sanitized values
		if( isset( $sanitized_data[ $key ] ) ) { continue; }
		// Set undefined values to default and continue
		if( ! isset( $raw_data[ $key ] ) ) { $sanitized_data[ $key ] = $default_value; continue; }

		// Sanitize integers
		if( in_array( $key, $keys_by_type[ 'int' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? intval( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize floats
		if( in_array( $key, $keys_by_type[ 'float' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? foatval( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize numeric
		if( in_array( $key, $keys_by_type[ 'numeric' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize string identifiers
		else if( in_array( $key, $keys_by_type[ 'str_id' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_title_with_dashes( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text
		else if( in_array( $key, $keys_by_type[ 'str' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_text_field( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text with html
		else if( in_array( $key, $keys_by_type[ 'str_html' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? wp_kses_post( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize array
		else if( in_array( $key, $keys_by_type[ 'array' ], true ) ) { 
			$sanitized_data[ $key ] = is_array( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize boolean
		else if( in_array( $key, $keys_by_type[ 'bool' ], true ) ) { 
			$sanitized_data[ $key ] = in_array( $raw_data[ $key ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
		}

		// Sanitize datetime
		else if( in_array( $key, $keys_by_type[ 'datetime' ], true ) ) { 
			$sanitized_data[ $key ] = bookacti_sanitize_datetime( $raw_data[ $key ] );
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}

		// Sanitize date
		else if( in_array( $key, $keys_by_type[ 'date' ], true ) ) { 
			$sanitized_data[ $key ] = bookacti_sanitize_date( $raw_data[ $key ] );
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}
	}

	return apply_filters( 'bookacti_sanitized_data', $sanitized_data, $default_data, $raw_data, $keys_by_type );
}


/**
 * Escape illegal caracters in ical properties
 * @since 1.6.0
 * @version 1.8.8
 * @param string $value
 * @param string $property_name
 * @return string
 */
function bookacti_sanitize_ical_property( $value, $property_name = '' ) {
	$is_desc = $property_name === 'DESCRIPTION';
	$eol = $is_desc ? '\n' : ' ';
	$value = trim( $value );									// Remove whitespaces at the start and at the end of the string
	$value = ! $is_desc ? strip_tags( $value ) : $value;		// Remove PHP and HTML elements
	$value = html_entity_decode( $value );						// Decode html entities first because semicolons will be escaped
	$value = preg_replace( '/([\,;])/', '\\\$1', $value );		// Escape illegal caracters in ical properties
	$value = preg_replace( '/' . PHP_EOL . '+/', $eol, $value );// Replace End of lines with a whitespace or a \n in DESCRIPTION
	$value = preg_replace( '/\s{2,}/', ' ', $value );			// Replace multiple whitespaces with a single space
	$property_name_len = strlen( $property_name ) + 1;			// Add 1 character for the colon (:) after the property name
	$lines = array();
	while( strlen( $value ) > ( 75 - $property_name_len ) ) {
		$space = ( 75 - $property_name_len );
		$mbcc = $space;
		while( $mbcc ) {
			$line = bookacti_substr( $value, 0, $mbcc );
			$oct = strlen( $line );
			if ( $oct > $space ) {
				$mbcc -= $oct - $space;
			}
			else {
				$lines[] = $line;
				$property_name_len = 1; // The leading space doesn't count, but we still take it into account it for better compatibility
				$value = bookacti_substr( $value, $mbcc );
				break;
			}
		}
	}
	if( ! empty( $value ) ) {
		$lines[] = $value;
	}
	return implode( PHP_EOL . ' ', $lines ); // Line break + leading space
}




// USERS

/**
 * Get users metadata
 * @version 1.8.0
 * @param array $args
 * @return array
 */
function bookacti_get_users_data( $args = array() ) {
	$defaults = array(
		'blog_id' => $GLOBALS[ 'blog_id' ],
		'include' => array(), 'exclude' => array(),
		'role' => '', 'role__in' => array(), 'role__not_in' => array(), 'who' => '',
		'meta_key' => '', 'meta_value' => '', 'meta_compare' => '', 'meta_query' => array(),
		'date_query' => array(),
		'orderby' => 'login', 'order' => 'ASC', 'offset' => '',
		'number' => '', 'paged' => '', 'count_total' => false,
		'search' => '', 'search_columns' => array(), 'fields' => 'all', 
		'meta' => true, 'meta_single' => true
	 ); 

	$args = apply_filters( 'bookacti_users_data_args', wp_parse_args( $args, $defaults ), $args );

	$users = get_users( $args );
	if( ! $users ) { return $users; }
	
	// Index the array by user ID
	$sorted_users = array();
	foreach( $users as $user ) {
		$sorted_users[ $user->ID ] = $user;
	}
	
	// Add user meta
	if( $args[ 'meta' ] ) {
		// Make sure that all the desired users meta are in cache with a single db query
		update_meta_cache( 'user', array_keys( $sorted_users ) );
		
		// Add cached meta to user object
		foreach( $sorted_users as $user_id => $user ) {
			$meta = array();
			$meta_raw = wp_cache_get( $user_id, 'user_meta' );
			foreach( $meta_raw as $key => $values ) {
				$meta[ $key ] = $args[ 'meta_single' ] ? maybe_unserialize( $values[ 0 ] ) : array_map( 'maybe_unserialize', $values );
			}
			$sorted_users[ $user_id ]->meta = $meta; 
		}
	}
		
	return $sorted_users;
}


/**
 * Get all available user roles
 * @since 1.8.0
 * @version 1.8.3
 * @return array
 */
function bookacti_get_roles() {
	global $wp_roles;
	if( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
    $roles = array_map( 'translate_user_role', $wp_roles->get_names() );
	return $roles;
}


/**
 * Get all user roles per capability
 * @since 1.8.3
 * @param array $capabilities
 * @return array
 */
function bookacti_get_roles_by_capabilities( $capabilities = array() ) {
	global $wp_roles;
	if( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
	
	$matching_roles = array();
	
	$roles = $wp_roles->roles;
	if( $roles ) {
		foreach( $roles as $role_id => $role ) {
			$role_cap = array_keys( $role[ 'capabilities' ] );
			if( array_intersect( $role_cap, $capabilities ) ) {
				$matching_roles[] = $role_id;
			}
		}
	}
	
	return $matching_roles;
}


/**
 * Programmatically logs a user in
 * @since 1.5.0
 * @param string $username
 * @return bool True if the login was successful; false if it wasn't
 */
function bookacti_log_user_in( $username ) {

	if ( is_user_logged_in() ) { wp_logout(); }

	// hook in earlier than other callbacks to short-circuit them
	add_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );
	$user = wp_signon( array( 'user_login' => $username ) );
	remove_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );

	if( is_a( $user, 'WP_User' ) ) {
		wp_set_current_user( $user->ID, $user->user_login );
		if ( is_user_logged_in() ) { return true; }
	}
	return false;
}


/**
 * An 'authenticate' filter callback that authenticates the user using only the username.
 *
 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
 * and unhooked immediately after it fires.
 * 
 * @since 1.5.0
 * @param WP_User $user
 * @param string $username
 * @param string $password
 * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
 */
function bookacti_allow_to_log_user_in_programmatically( $user, $username, $password ) {
	return get_user_by( 'login', $username );
}




// FILES AND FOLDERS

/**
 * Delete a directory and all its files
 * @since 1.7.0
 * @param string $dir_path Initial directory Path
 * @param boolean $delete_init_dir TRUE = delete content and self. FALSE = delete content only.
 * @return boolean TRUE if everything is deleted, FALSE if a single file or directory hasn't been deleted
 */
function bookacti_delete_files( $dir_path, $delete_init_dir = false ) {
	if( ! is_dir( $dir_path ) ) { return false; }
	
	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir_path, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST,
		RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
	);

	$files = array();
	foreach ( $iter as $path => $dir ) {
		$files[] = $path;
	}
	if( $delete_init_dir ) { $files[] = $dir_path; }
	
	$all_deleted = true;
	foreach( $files as $file ) {
		if( is_dir( $file ) ){
			$deleted = rmdir( $file );
		} elseif( is_file( $file ) ) {
			$deleted  = unlink( $file );
		}
		if( ! $deleted ) { $all_deleted = false; }
	}
	
	return $all_deleted;
}