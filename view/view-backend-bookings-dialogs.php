<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

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