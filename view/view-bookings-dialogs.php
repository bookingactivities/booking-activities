<?php 
/**
 * Frontend and Backend booking dialogs
 * @version 1.19.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$messages = bookacti_get_messages();
?>

<!-- Frontend and backend - Cancel booking -->
<div id='bookacti-cancel-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php echo esc_attr( $messages[ 'cancel_dialog_title' ][ 'value' ] ); ?>' >
	<form id='bookacti-cancel-booking-form'>
		<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'bookacti_cancel_booking' ) ); ?>'/>
		<?php
			echo wp_kses_post( wpautop( bookacti_get_message( 'cancel_dialog_content' ) ) );
		
			do_action( 'bookacti_cancel_booking_dialog_after' );
		?>
	</form>
</div>

<!-- Frontend and backend - Refund a cancel booking -->
<div id='bookacti-refund-booking-dialog' 
	 class='bookacti-backend-dialog bookacti-bookings-dialog' 
	 style='display:none;' 
	 title='<?php echo esc_attr( current_user_can( 'bookacti_edit_bookings' ) ? _x( 'Refund a booking', 'Dialog title', 'booking-activities' ) : $messages[ 'refund_dialog_title' ][ 'value' ] ); ?>'>
	<form id='bookacti-refund-booking-form'>
		<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'bookacti_refund_booking' ) ); ?>'/>
		<div id='bookacti-no-refund-option' style='display:none;'>
			<?php esc_html_e( 'Sorry, no available refund option were found. Please contact the administrator.', 'booking-activities' ); ?>
		</div>
		<div id='bookacti-refund-options-container' style='display:none;'>
			<div id='bookacti-refund-amount-container'>
				<span><?php esc_html_e( 'Refund amount:', 'booking-activities' ); ?></span>
				<strong id='bookacti-refund-amount'></strong>
			</div>
			<div id='bookacti-refund-options-title'>
				<?php esc_html_e( 'Pick a refund option:', 'booking-activities' ); ?>
			</div>
			<div id='bookacti-refund-options'></div>
			<div id='bookacti-refund-message'>
				<?php echo wp_kses_post( bookacti_get_message( 'refund_request_dialog_feedback_label' ) ); ?>
				<textarea name='refund_message'></textarea>
			</div>
		</div>
	</form>
</div>

<div id='bookacti-refund-booking-confirm-dialog' 
	 class='bookacti-backend-dialog bookacti-bookings-dialog' 
	 style='display:none;' 
	 title='<?php echo esc_html__( 'Refund confirmation', 'booking-activities' ); ?>'>
</div>

<!-- Frontend and backend - Reschedule booking -->
<div id='bookacti-reschedule-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' title='<?php echo esc_attr( $messages[ 'reschedule_dialog_title' ][ 'value' ] ); ?>'>
	<form id='bookacti-reschedule-booking-form' class='bookacti-booking-form'>
		<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'bookacti_reschedule_booking' ) ); ?>'/>
		<div>
			<?php
				$atts = bookacti_format_booking_system_attributes( array( 'id' => 'bookacti-booking-system-reschedule', 'auto_load' => 0 ) );
				echo bookacti_get_booking_system( $atts ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			?>
			<input type='hidden' class='bookacti-quantity' value='1'/>
		</div>
		<div>
			<?php if( is_admin() ) { ?>
				<label for='bookacti-send-notifications-on-reschedule' ><?php esc_html_e( 'Send notifications', 'booking-activities' ); ?></label>
			<?php 
				$args = array(
					'type'	=> 'checkbox',
					'name'	=> 'send-notifications-on-reschedule',
					'id'	=> 'bookacti-send-notifications-on-reschedule',
					'value'	=> 0,
					'tip'	=> sprintf( 
						/* Translators: %s is a link to the "Notifications settings" */
						esc_html__( 'Send the booking rescheduling notifications configured in %s.', 'booking-activities' ), 
						'<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_notifications' ) ) . '">' . esc_html__( 'Notifications settings', 'booking-activities') . '</a>'
					)
				);
				bookacti_display_field( $args );
			}
			?>
		</div>
		<?php do_action( 'bookacti_reschedule_booking_dialog_after' ); ?>
	</form>
</div>

<?php do_action( 'bookacti_bookings_dialogs' );