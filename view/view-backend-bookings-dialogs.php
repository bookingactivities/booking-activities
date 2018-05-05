<?php 
/**
 * Backend booking dialogs
 * @version 1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id='bookacti-change-booking-state-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php echo esc_html__( 'Change booking state', BOOKACTI_PLUGIN_NAME ); ?>'>
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
		<div>
			<label for='bookacti-select-payment-status' ><?php esc_html_e( 'Payment status', BOOKACTI_PLUGIN_NAME ); ?></label>
			<select name='select-payment-status' id='bookacti-select-payment-status' >
				<?php
				$payment_status = bookacti_get_payment_status_labels();
				foreach( $payment_status as $payment_status_id => $payment_status_data ) {
					echo '<option value="' . esc_attr( $payment_status_id ) . '" >' . esc_html( $payment_status_data[ 'label' ] ) . '</option>';
				}
				?>
			</select>
		</div>
	</form>
</div>


<div id='bookacti-delete-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php echo esc_html__( 'Delete a booking', BOOKACTI_PLUGIN_NAME ); ?>'>
	<form id='bookacti-delete-booking-dialog-content'>
		<input type='hidden' name='action' value='bookactiDeleteBooking'/>
		<input type='hidden' name='booking_id' value='0'/>
		<input type='hidden' name='booking_type' value=''/>
		<?php wp_nonce_field( 'bookacti_delete_booking', 'nonce_delete_booking' ); ?>
		<p class='bookacti-dialog-intro bookacti-delete-single-booking-description' >
			<?php esc_html_e( 'Are you sure to delete this booking permanently?', BOOKACTI_PLUGIN_NAME ); ?>
		</p>
		<p class='bookacti-irreversible-action'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', BOOKACTI_PLUGIN_NAME ); ?></span>
		</p>
		<p class='bookacti-dialog-intro bookacti-delete-booking-group-description' style='display:none;'>
			<?php esc_html_e( 'All the bookings included in this booking group will also be delete.', BOOKACTI_PLUGIN_NAME ); ?>
		</p>
		<?php do_action( 'bookacti_delete_booking_form_after' ); ?>
	</form>
</div>



<?php 

do_action( 'bookacti_backend_bookings_dialogs' );