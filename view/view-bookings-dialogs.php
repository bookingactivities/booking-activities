<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<!-- Frontend and backend - Cancel booking -->
<div id='bookacti-cancel-booking-dialog' class='bookacti-frontend-dialogs bookacti-backend-dialogs bookacti-bookings-dialogs' >
<?php
	echo '<p>'
			. esc_html__( 'Do you really want to cancel this booking?', BOOKACTI_PLUGIN_NAME )
		. '</p><p>'
			. esc_html__( 'If you have already paid, you will be able to request a refund.', BOOKACTI_PLUGIN_NAME )
		. '</p>';
?>
</div>

<!-- Frontend and backend - Refund a cancel booking -->
<div id='bookacti-refund-booking-dialog' class='bookacti-frontend-dialogs bookacti-backend-dialogs bookacti-bookings-dialogs' ></div>
<div id='bookacti-refund-booking-confirm-dialog' class='bookacti-frontend-dialogs bookacti-backend-dialogs bookacti-bookings-dialogs' ></div>

<!-- Frontend and backend - Reschedule booking -->
<div id='bookacti-reschedule-booking-dialog' class='bookacti-frontend-dialogs bookacti-backend-dialogs bookacti-bookings-dialogs' >
<?php 
	$reschedule_booking_method = apply_filters( 'bookacti_reschedule_booking_method', 'calendar' );
	$atts = array( 
				'calendars'	=> array(),
				'activities'=> array(),
				'method'	=> $reschedule_booking_method,
				'id'		=> 'booking-system-reschedule'
			);
	bookacti_get_booking_system( $atts, true );
?>
</div>

<?php do_action( 'bookacti_bookings_dialogs' );