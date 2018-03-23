<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<!-- Frontend and backend - Cancel booking -->
<div id='bookacti-cancel-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' >
<?php
	echo wp_kses_post( wpautop( bookacti_get_message( 'cancel_dialog_content' ) ) );
?>
</div>

<!-- Frontend and backend - Refund a cancel booking -->
<div id='bookacti-refund-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' ></div>
<div id='bookacti-refund-booking-confirm-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' ></div>

<!-- Frontend and backend - Reschedule booking -->
<div id='bookacti-reschedule-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' >
	<div>
<?php 
	$reschedule_booking_method = apply_filters( 'bookacti_reschedule_booking_method', 'calendar' );
	$atts = array( 
		'method'		=> $reschedule_booking_method,
		'id'			=> 'booking-system-reschedule',
		'class'			=> is_admin() ? 'admin-booking-system' : '',
		'past_events'	=> is_admin() ? 1 : 0,
		'context'		=> is_admin() ? 'booking_page' : 'frontend',
		'auto_load'		=> 0
	);
	bookacti_get_booking_system( $atts, true );
?>
	</div>
	<div>
<?php
	if( is_admin() ) {
?>
		<label for='bookacti-send-notifications-on-reschedule' ><?php esc_html_e( 'Send notifications', BOOKACTI_PLUGIN_NAME ); ?></label>
<?php 
		$args = array(
			'type'	=> 'checkbox',
			'name'	=> 'send-notifications-on-reschedule',
			'id'	=> 'bookacti-send-notifications-on-reschedule',
			'value'	=> 0,
			'tip'	=> __( 'Whether to notify the customer of the booking reschedule.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_display_field( $args );
	}
?>
	</div>
</div>

<?php do_action( 'bookacti_bookings_dialogs' );