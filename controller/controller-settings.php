<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// INIT BA SETTINGS
add_action( 'admin_init', 'bookacti_init_settings' );
function bookacti_init_settings() { 

	/* General settings Section */
	add_settings_section( 
		'bookacti_settings_section_general',
		__( 'General settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_general_callback',
		'bookacti_general_settings'
	);
	
	add_settings_field(  
		'booking_method', 
		__( 'Booking method', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_booking_method_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);
	
	add_settings_field(  
		'when_events_load', 
		__( 'When to load the events?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_when_events_load_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general'
	);

	add_settings_field(  
		'started_events_bookable', 
		__( 'Are started events bookable?', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_started_events_bookable_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'default_booking_state', 
		__( 'Default booking state', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_booking_state_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'timezone', 
		__( 'Calendars timezone', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_timezone_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);

	add_settings_field(  
		'date_format', 
		__( 'Date format', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_date_format_callback', 
		'bookacti_general_settings', 
		'bookacti_settings_section_general' 
	);
	
	
	
	/* Template settings Section */
	add_settings_section( 
		'bookacti_settings_section_template',
		__( 'Calendars Settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_template_callback',
		'bookacti_template_settings'
	);

	add_settings_field(  
		'default_template_per_user', 
		__( 'Default calendar per user', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_default_template_callback', 
		'bookacti_template_settings', 
		'bookacti_settings_section_template' 
	);
	
	
	
	/* Bookings settings Section */
	add_settings_section( 
		'bookacti_settings_section_bookings',
		__( 'Bookings Settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_bookings_callback',
		'bookacti_bookings_settings'
	);

	add_settings_field(  
		'show_past_events', 
		__( 'Show past events', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_show_past_events_callback', 
		'bookacti_bookings_settings', 
		'bookacti_settings_section_bookings' 
	);

	add_settings_field(  
		'allow_templates_filter', 
		__( 'Allow calendars filter', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_templates_filter_callback', 
		'bookacti_bookings_settings', 
		'bookacti_settings_section_bookings' 
	);

	add_settings_field(  
		'allow_activities_filter', 
		__( 'Allow activities filter', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_activities_filter_callback', 
		'bookacti_bookings_settings', 
		'bookacti_settings_section_bookings' 
	);

	add_settings_field(  
		'show_inactive_bookings', 
		__( 'Show inactive bookings', BOOKACTI_PLUGIN_NAME ), 
		'bookacti_settings_field_show_inactive_bookings_callback', 
		'bookacti_bookings_settings', 
		'bookacti_settings_section_bookings' 
	);
	
	
	
	/* Cancellation settings Section */
	add_settings_section( 
		'bookacti_settings_section_cancellation',
		__( 'Cancellation settings', BOOKACTI_PLUGIN_NAME ),
		'bookacti_settings_section_cancellation_callback',
		'bookacti_cancellation_settings'
	);
	
	add_settings_field(  
		'allow_customers_to_cancel',                      
		__( 'Allow customers to cancel their bookings', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_activate_cancel_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'allow_customers_to_reschedule',                      
		__( 'Allow customers to reschedule their bookings', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_activate_reschedule_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
		'cancellation_min_delay_before_event',                      
		__( 'Min delay before event', BOOKACTI_PLUGIN_NAME ),               
		'bookacti_settings_field_cancellation_delay_callback',   
		'bookacti_cancellation_settings',                     
		'bookacti_settings_section_cancellation' 
	);
	
	add_settings_field(  
			'refund_actions_after_cancellation',                      
			__( 'Possible actions customers can take to be refund', BOOKACTI_PLUGIN_NAME ),               
			'bookacti_settings_field_cancellation_refund_actions_callback',   
			'bookacti_cancellation_settings',                     
			'bookacti_settings_section_cancellation' 
	);
	
	
	do_action( 'bookacti_add_settings' );
	
	
	register_setting('bookacti_general_settings', 'bookacti_general_settings' );
	register_setting('bookacti_cancellation_settings', 'bookacti_cancellation_settings' );
}


// Add actions to Booking Activities in plugins list
add_filter( 'plugin_action_links_' . BOOKACTI_PLUGIN_BASENAME, 'bookacti_action_links_in_plugins_table', 10, 1 );
function bookacti_action_links_in_plugins_table( $links ) {
   $links = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=bookacti_settings' ) . '" title="' . esc_attr( __( 'Manage Booking Activities Settings', BOOKACTI_PLUGIN_NAME ) ) . '">' . __( 'Settings', BOOKACTI_PLUGIN_NAME ) . '</a>' ) + $links;
   return $links;
}


// Add meta links in plugins list
add_filter( 'plugin_row_meta', 'bookacti_meta_links_in_plugins_table', 10, 2 );
function bookacti_meta_links_in_plugins_table( $links, $file ) {
   if ( $file == BOOKACTI_PLUGIN_BASENAME ) {
		$links[ 'docs' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_user_docs_url',	'https://booking-activities.fr/en/documentation/user-documentation/?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) ) . '" title="' . esc_attr( __( 'View Booking Activities Documentation', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Docs', BOOKACTI_PLUGIN_NAME ) . '</a>';
		$links[ 'report' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_report_url',		'https://github.com/bookingactivities/booking-activities/issues/' ) ) . '" title="' . esc_attr( __( 'Report a bug or request a feature', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Report & Request', BOOKACTI_PLUGIN_NAME ) . '</a>';
		$links[ 'contact' ]	= '<a href="' . esc_url( apply_filters( 'bookacti_contact_url',		'https://booking-activities.fr/en/#contact?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) ) . '" title="' . esc_attr( __( 'Contact us directly', BOOKACTI_PLUGIN_NAME ) ) . '" target="_blank" >' . esc_html__( 'Contact us', BOOKACTI_PLUGIN_NAME ) . '</a>';
	}
	return $links;
}


// INIT BA NOTICES

// FIRST20 DISCOUNT NOTICE
add_action( 'admin_notices', 'bookacti_first20_notice' );
function bookacti_first20_notice() {
	if( ! empty( get_option( 'bookacti-first20-notice-dismissed' ) ) || ! current_user_can( 'bookacti_manage_booking_activities' ) ) {
		return;
	}
	
	$switch_message = false;
	if( ! empty( $_GET ) && isset( $_GET[ 'page' ] ) ) {
		if( sanitize_title_with_dashes( $_GET[ 'page' ] ) === 'booking-activities' ) {
			$switch_message = true;
			if( empty( get_option( 'bookacti-first20-notice-viewed' ) ) ) {
				update_option( 'bookacti-first20-notice-viewed', 1 );
			}
			if( isset( $_GET[ 'dismiss_first20_notice' ] ) && wp_verify_nonce( $_GET[ 'nonce' ], 'bookacti_dismiss_first20_notice' ) && current_user_can( 'bookacti_manage_booking_activities' ) ) {
				update_option( 'bookacti-first20-notice-dismissed', 1 );
				return;
			}
		} else {
			if( ! empty( get_option( 'bookacti-first20-notice-viewed' ) ) ) {
				return;
			}
		}
	} else if( ! empty( get_option( 'bookacti-first20-notice-viewed' ) ) ) {
		return;
	}
	?>
		<div class='notice notice-success bookacti-first20-notice is-dismissible' >
			<p><?php esc_html_e( 'Welcome to Booking Activities! To thank you for using our booking plugin, we give you a 20% discount on every add-ons with the code "FIRST20" on your first purchase on booking-activities.fr.', BOOKACTI_PLUGIN_NAME ); ?></p>
			<p>
			<?php if( ! $switch_message ) { ?>
				<a class='button' href='<?php echo esc_url( admin_url( 'admin.php?page=booking-activities' ) ); ?>' ><?php esc_html_e( 'See add-ons and hide this message', BOOKACTI_PLUGIN_NAME ); ?></a>
			<?php } else { ?>
				<a class='button' href='<?php echo esc_url(  wp_nonce_url( admin_url( 'admin.php?page=booking-activities&dismiss_first20_notice=1' ), 'bookacti_dismiss_first20_notice', 'nonce' ) ); ?>' ><?php esc_html_e( 'I have already benefited from this offer, hide this message', BOOKACTI_PLUGIN_NAME ); ?></a>
			<?php } ?>
			</p>
		</div>
	<?php
}


// ASK TO RATE THE PLUGIN 5 STARS
add_action( 'admin_notices', 'bookacti_5stars_rating_notice' );
function bookacti_5stars_rating_notice() {
	if( empty( get_option( 'bookacti-5stars-rating-notice-dismissed' ) ) ) {
		if( current_user_can( 'bookacti_manage_booking_activities' ) ) {
			$install_date = get_option( 'bookacti-install-date' );
			if( ! empty( $install_date ) ) {
				$install_datetime	= DateTime::createFromFormat( 'Y-m-d H:i:s', $install_date );
				$current_datetime	= new DateTime();
				$nb_days			= floor( $install_datetime->diff( $current_datetime )->days );
				if( $nb_days >= 7 ) {
					?>
					<div class='notice notice-info bookacti-5stars-rating-notice is-dismissible' >
						<p>
							<?php 
							_e( '<strong>Booking Activities</strong> has been helping you for one week now.', BOOKACTI_PLUGIN_NAME );
							/* translators: %s: five stars */
							echo '<br/>' 
								. sprintf( esc_html__( 'Would you help it back leaving us a %s rating? We need you now to make it last!', BOOKACTI_PLUGIN_NAME ), 
								  '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
							?>
						</p>
						<p>
							<a class='button' href='<?php echo esc_url( 'https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post' ); ?>' target='_blank' ><?php esc_html_e( "Ok, I'll rate you five stars!", BOOKACTI_PLUGIN_NAME ); ?></a>
							<span class='button' id='bookacti-dismiss-5stars-rating' ><?php esc_html_e( "I already rated you, hide this message", BOOKACTI_PLUGIN_NAME ); ?></span>
						</p>
					</div>
					<?php
				}
			}
		}
	}
}


// REMOVE NOTICES
// Remove Rate-us-5-stars notice
add_action( 'wp_ajax_bookactiDismiss5StarsRatingNotice', 'bookacti_dismiss_5stars_rating_notice' );
function bookacti_dismiss_5stars_rating_notice() {
	
	// Check nonce, no need to check capabilities
	$is_nonce_valid = check_ajax_referer( 'bookacti_dismiss_5stars_rating_notice', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_manage_booking_activities' );

	if( $is_nonce_valid && $is_allowed ) {
	
		$updated = update_option( 'bookacti-5stars-rating-notice-dismissed', 1 );
		if( $updated ) {
			wp_send_json( array( 'status' => 'success' ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}


/**
 * Display a custom message in the footer
 * 
 * @param string $footer_text
 * @return string
 */
function bookacti_admin_footer_text( $footer_text ) {
	if ( ! current_user_can( 'bookacti_manage_booking_activities' ) || ! function_exists( 'bookacti_get_screen_ids' ) ) {
		return $footer_text;
	}
	
	$current_screen	= get_current_screen();
	$bookacti_pages	= bookacti_get_screen_ids();
	
	// Check to make sure we're on a BA admin page.
	if ( isset( $current_screen->id ) && in_array( $current_screen->id, $bookacti_pages ) ) {
		// Change the footer text
		if ( ! get_option( 'woocommerce_admin_footer_text_rated' ) ) {
			/* translators: %s: five stars */
			$footer_text = sprintf( __( 'If <strong>Booking Activities</strong> helps you, help it back leaving us a %s rating. We need you now to make it last!', BOOKACTI_PLUGIN_NAME ), '<a href="https://wordpress.org/support/plugin/booking-activities/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
		}
	}

	return $footer_text;
}
add_filter( 'admin_footer_text', 'bookacti_admin_footer_text', 10, 1 );