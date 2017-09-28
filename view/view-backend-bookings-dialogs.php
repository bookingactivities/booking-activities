<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Define variables
$user_id = get_current_user_id(); 
?>

<!-- Bookings page - Filters params dialog -->
<div id='bookacti-bookings-filters-param-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' >
	<form id='bookacti-bookings-filters-param-form'>
		<?php
		// Create a nonce field
		wp_nonce_field( 'bookacti_update_booking_filters_settings', 'nonce_update_booking_filters_settings' );
		
		//Fill the array of tabs with their label, callback for content and display order
		$booking_filters_tabs = apply_filters( 'bookacti_booking_filters_dialog_tabs', array (
			array(	'label'			=> __( 'Events', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'events',
					'callback'		=> 'bookacti_fill_booking_filters_tab_events',
					'parameters'	=> array( 'user_id' => $user_id ),
					'order'			=> 10 ),
			array(	'label'			=> _x( 'Filters', 'The noun, plural', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'filters',
					'callback'		=> 'bookacti_fill_booking_filters_tab_filters',
					'parameters'	=> array( 'user_id' => $user_id ),
					'order'			=> 20 )
		) );

		// Display tabs
		bookacti_display_tabs( $booking_filters_tabs, 'booking-filters' );
		
		function bookacti_fill_booking_filters_tab_events( $params ) {
			$user_id = $params[ 'user_id' ];
			do_action( 'bookacti_booking_filters_tab_events_before', $params );
		?>
			<div>
				<label for='bookacti-bookings-show-past-events' ><?php _e( 'Show past events', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				
				$show_past_events_array	= bookacti_get_setting_value( 'bookacti_bookings_settings', 'show_past_events' );
				$show_past_events		= 1;
				if( is_array( $show_past_events_array ) && isset( $show_past_events_array[ $user_id ] ) && ! is_null( $show_past_events_array[ $user_id ] ) ) {
					$show_past_events	= $show_past_events_array[ $user_id ];
				}

				$name	= 'bookings-show-past-events';
				$id		= 'bookacti-bookings-show-past-events';
				bookacti_onoffswitch( $name, $show_past_events, $id );

				$tip = __( "Show past events and access their booking list. You still won't be able to book a past event.", BOOKACTI_PLUGIN_NAME );
				bookacti_help_tip( $tip );
				?>
			</div>
		<?php 
			do_action( 'bookacti_booking_filters_tab_events_after', $params );
		}
		
		function bookacti_fill_booking_filters_tab_filters( $params ) {
			$user_id = $params[ 'user_id' ];
			do_action( 'bookacti_booking_filters_tab_filters_before', $params );
		?>
			<div>
				<?php /* translators: Name of the option allowing user to filter events by calendars. */ ?>
				<label for='bookacti-bookings-allow-templates-filter' ><?php esc_html_e( 'Calendars filter', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php

				$templates_filter_array	= bookacti_get_setting_value( 'bookacti_bookings_settings', 'allow_templates_filter' );
				$templates_filter		= 1;
				if( is_array( $templates_filter_array ) && isset( $templates_filter_array[ $user_id ] ) && ! is_null( $templates_filter_array[ $user_id ] ) ) {
					$templates_filter	= $templates_filter_array[ $user_id ];
				}

				$name	= 'bookings-allow-templates-filter';
				$id		= 'bookacti-bookings-allow-templates-filter';
				bookacti_onoffswitch( $name, $templates_filter, $id );

				$tip = __( "Allow to filter events by calendars.", BOOKACTI_PLUGIN_NAME );
				bookacti_help_tip( $tip );
				?>
			</div>
			<div>
				<?php /* translators: Name of the option allowing user to filter events by activities. */ ?>
				<label for='bookacti-bookings-allow-activities-filter' ><?php esc_html_e( 'Activities filter', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php

				$activities_filter_array= bookacti_get_setting_value( 'bookacti_bookings_settings', 'allow_activities_filter' );
				$activities_filter		= 1;
				if( is_array( $activities_filter_array ) && isset( $activities_filter_array[ $user_id ] ) && ! is_null( $activities_filter_array[ $user_id ] ) ) {
					$activities_filter	= $activities_filter_array[ $user_id ];
				}

				$name	= 'bookings-allow-activities-filter';
				$id		= 'bookacti-bookings-allow-activities-filter';
				bookacti_onoffswitch( $name, $activities_filter, $id );

				$tip = __( "Allow to filter events by activities.", BOOKACTI_PLUGIN_NAME );
				bookacti_help_tip( $tip );
				?>
			</div>
		<?php
			do_action( 'bookacti_booking_filters_tab_filters_after', $params );
		}
		?>
	</form>
</div>


<!-- Bookings page - List params dialog -->
<div id='bookacti-bookings-list-param-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' >
	<form id='bookacti-bookings-list-param-form'>
		<?php
		// Create a nonce field
		wp_nonce_field( 'bookacti_update_booking_list_settings', 'nonce_update_booking_list_settings' );
		
		//Fill the array of tabs with their label, callback for content and display order
		$bookings_list_tabs = apply_filters( 'bookacti_bookings_list_dialog_tabs', array (
			array(	'label'			=> _x( 'Filter', 'The action to filter', BOOKACTI_PLUGIN_NAME ),
					'id'			=> 'filter',
					'callback'		=> 'bookacti_fill_bookings_list_tab_filter',
					'parameters'	=> array( 'user_id' => $user_id ),
					'order'			=> 10 )
		) );

		// Display tabs
		bookacti_display_tabs( $bookings_list_tabs, 'bookings-list' );
		
		function bookacti_fill_bookings_list_tab_filter( $params ) {
			$user_id = $params[ 'user_id' ];
			do_action( 'bookacti_booking_list_tab_filter_before', $params );
		?>
			<div>
				<label for='bookacti-bookings-show-inactive-bookings' ><?php esc_html_e( 'Show inactive bookings', BOOKACTI_PLUGIN_NAME ); ?></label>
				<?php
				
				$show_inactive_bookings_array	= bookacti_get_setting_value( 'bookacti_bookings_settings', 'show_inactive_bookings' );
				$show_inactive_bookings			= 0;
				if( is_array( $show_inactive_bookings_array ) && isset( $show_inactive_bookings_array[ $user_id ] ) && ! is_null( $show_inactive_bookings_array[ $user_id ] ) ) {
					$show_inactive_bookings	= $show_inactive_bookings_array[ $user_id ];
				}
				
				$name	= 'bookings-show-inactive-bookings';
				$id		= 'bookacti-bookings-show-inactive-bookings';
				bookacti_onoffswitch( $name, $show_inactive_bookings, $id );

				$tip = __( "Show inactive bookings in the booking list (expired, cancelled, removed and refunded bookings).", BOOKACTI_PLUGIN_NAME );
				bookacti_help_tip( $tip );
				?>
			</div>
		<?php
			do_action( 'bookacti_booking_list_tab_filter_after', $params );
		} ?>
	</form>
</div>

<div id='bookacti-change-booking-state-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' >
	<form id='bookacti-change-booking-state-form'>
		<?php
		// Display nonce field
		wp_nonce_field( 'bookacti_change_booking_state', 'nonce_change_booking_state' );
		?>
		
		<p class='bookacti-dialog-intro' ><?php esc_html_e( 'Pick the desired booking state:', BOOKACTI_PLUGIN_NAME ); ?></p>
		<div>
		<label for='bookacti-select-booking-state' ><?php esc_html_e( 'Booking state', BOOKACTI_PLUGIN_NAME ); ?></label>
			<select name='select-booking-state' id='bookacti-select-booking-state' >
				<?php
				$selectable_booking_states = apply_filters( 'bookacti_booking_states_you_can_manually_change', array(
					'booked'			=> __( 'Booked', BOOKACTI_PLUGIN_NAME ),
					'pending'			=> __( 'Pending', BOOKACTI_PLUGIN_NAME ),
					'cancelled'			=> __( 'Cancelled', BOOKACTI_PLUGIN_NAME ),
					'refund_requested'	=> __( 'Refund requested', BOOKACTI_PLUGIN_NAME ),
					'refunded'			=> __( 'Refunded', BOOKACTI_PLUGIN_NAME )
				) );

				foreach( $selectable_booking_states as $state_key => $state_label ) {
					echo '<option value="' . esc_attr( $state_key ) . '" >' . esc_html( $state_label ) . '</option>';
				}
				?>
			</select>
		</div>
		<div>
			<label for='bookacti-send-notifications-on-state-change' ><?php esc_html_e( 'Send notifications', BOOKACTI_PLUGIN_NAME ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'send-notifications-on-state-change',
					'id'	=> 'bookacti-send-notifications-on-state-change',
					'value'	=> 0,
					'tip'	=> __( 'Whether to notify the customer of the booking status change.', BOOKACTI_PLUGIN_NAME )
				);
				bookacti_display_field( $args );
			?>
		</div>
	</form>
</div>

<?php 

do_action( 'bookacti_backend_bookings_dialogs' );