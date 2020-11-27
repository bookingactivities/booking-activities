<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ORDERS

/**
 * Change booking quantity when admin changes order item quantity
 * 
 * @version 1.7.8
 * 
 * @param boolean $check
 * @param int $item_id
 * @param string $meta_key
 * @param string $meta_value
 * @param string $prev_value
 * @return boolean
 */
function bookacti_update_booking_qty_with_order_item_qty( $check, $item_id, $meta_key, $meta_value, $prev_value ) {

	if( $meta_key === '_qty' ) {

		$old_qty = wc_get_order_item_meta( $item_id, '_qty', true );

		// If the quantity hasn't changed, return
		if( $old_qty == $meta_value ) {
			return $check;
		}

		$booking_id			= wc_get_order_item_meta( $item_id, 'bookacti_booking_id', true );
		$booking_group_id	= wc_get_order_item_meta( $item_id, 'bookacti_booking_group_id', true );

		if( ! empty( $booking_id ) ) {
			$response = bookacti_controller_update_booking_quantity( $booking_id, $meta_value, 'admin' );
		} else if( ! empty( $booking_group_id ) ) {
			$response = bookacti_controller_update_booking_group_quantity( $booking_group_id, $meta_value, false, 'admin' );
		} else {
			return $check;
		}

		if( ! in_array( $response[ 'status' ], array( 'success', 'no_change' ), true ) ) {
			if( $response[ 'error' ] === 'qty_sup_to_avail' ) {
				$message =  /* translators: %1$s is a variable number of bookings. */
							sprintf( _n( 'You want to make %1$s booking', 'You want to make %1$s bookings', $meta_value, 'booking-activities' ), $meta_value )
							/* translators: %1$s is a variable number of bookings. */
					. ' ' . sprintf( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $response[ 'availability' ], 'booking-activities' ), $response[ 'availability' ] );

			} else if( $response[ 'error' ] === 'no_availability' ) {
				$message = __( 'This event is no longer available. Please choose another event.', 'booking-activities' );

			} else {
				$message = __( 'Error occurs while trying to update booking quantity.', 'booking-activities' );
			}

			// Stop the script execution
			wp_die( esc_html( $message ) );
		}
	}

	return $check;
}
add_filter( 'update_order_item_metadata', 'bookacti_update_booking_qty_with_order_item_qty', 20, 5 );


/**
 * Cancel bookings when admin changes the associated order item quantity to 0
 * 
 * @since 1.1.0
 * @version 1.5.0
 * @param int $order_id
 * @param array $items
 * @return void
 */
function bookacti_cancel_bookings_if_order_item_qty_is_null( $order_id, $items ) { 

	if( empty( $items[ 'order_item_id' ] ) || empty( $items[ 'meta_key' ] ) || empty( $items[ 'meta_value' ] ) ) { return; }

	foreach( $items['order_item_id'] as $item_id ) {

		// Get booking (group) id and booking type
		$booking_id		= 0;
		$booking_type	= '';

		if( empty( $items[ 'meta_key' ][ $item_id ] ) || empty( $items[ 'meta_value' ][ $item_id ] ) ) { continue; }

		foreach( $items[ 'meta_key' ][ $item_id ] as $meta_id => $meta_value ) {
			if( ( $meta_value === 'bookacti_booking_id' || $meta_value === 'bookacti_booking_group_id' ) 
			&&	! empty( $items[ 'meta_value' ][ $item_id ][ $meta_id ] ) ) {
				$booking_id		= intval( $items[ 'meta_value' ][ $item_id ][ $meta_id ] ) ;
				$booking_type	= $meta_value === 'bookacti_booking_group_id' ? 'group' : 'single';
				break;
			}
		}

		// If the product is not an activity, return
		if( ! $booking_id ) { continue; }

		// Get quantity
		$quantity = isset( $items[ 'order_item_qty' ][ $item_id ] ) ? wc_clean( wp_unslash( $items[ 'order_item_qty' ][ $item_id ] ) ) : null;

		// The item will be removed, so cancel the associated bookings
		if( '0' === $quantity ) {
			if( $booking_type === 'group' ) {
				bookacti_cancel_booking_group_and_its_bookings( $booking_id );
			} else {
				bookacti_cancel_booking( $booking_id );
			}
		}
	}
}
add_action( 'woocommerce_before_save_order_items', 'bookacti_cancel_bookings_if_order_item_qty_is_null', 10, 2 );


/**
 * Cancel bookings when admin removes the associated order item
 * 
 * @since 1.1.0
 * 
 * @param int $item_id
 * @return void
 */
function bookacti_cancel_bookings_when_order_item_is_deleted( $item_id ) {

	$booking_id			= wc_get_order_item_meta( $item_id, 'bookacti_booking_id', true );
	$booking_group_id	= wc_get_order_item_meta( $item_id, 'bookacti_booking_group_id', true );

	if( ! empty( $booking_id ) ) {
		bookacti_cancel_booking( $booking_id );
	} else if( ! empty( $booking_group_id ) ) {
		bookacti_cancel_booking_group_and_its_bookings( $booking_group_id );
	} else {
		return;
	}
}
add_action( 'woocommerce_before_delete_order_item', 'bookacti_cancel_bookings_when_order_item_is_deleted', 10, 1 );


/**
 * Change booking quantity and status when a refund is deleted
 * @version 1.8.10
 * @param int $refund_id
 * @param int $order_id
 */
function bookacti_update_booking_when_refund_is_deleted( $refund_id, $order_id ) {
	$order = wc_get_order( $order_id );
	if( empty( $order ) ) { return false; }

	// Before anything, clear cache to make sure refunds are up to date 
	// espacially in case of multiple consecutive refunds
	$cache_key = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $order_id;
	wp_cache_delete( $cache_key, 'orders' );

	$items = $order->get_items();
	foreach( $items as $item_id => $item ) {
		$items_bookings	= bookacti_wc_get_order_items_bookings( array( $item ) );
		if( empty( $items_bookings[ $item_id ] ) ) { continue; }
		
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			// Check if the deleted refund is bound to this booking (group)
			if( $item_booking[ 'type' ] === 'group' ) {
				$refunds = bookacti_get_metadata( 'booking_group', $item_booking[ 'id' ], 'refunds', true );
			} else if( $item_booking[ 'type' ] === 'single' ) {
				$refunds = bookacti_get_metadata( 'booking', $item_booking[ 'id' ], 'refunds', true );
			}
			if( ! $refunds ) { continue; }
			
			// Backward compatibility
			$refund_id_index = array_search( $refund_id, $refunds ); // The refunds array used to be an array of ids only
			if( $refund_id_index === false ) {
				if( isset( $refunds[ $refund_id ] ) ) { $refund_id_index = $refund_id; }
			}
			if( $refund_id_index === false ) { continue; }
		
			$new_refunds = $refunds;
			unset( $new_refunds[ $refund_id_index ] );
			
			// Get the new item quantity 
			// (we still need to substract $refunded_qty because it is possible to have multiple refunds, 
			// so even if you delete one, you still need to substract the quantity of the others)
			$item_qty = $item->get_quantity() - abs( $order->get_qty_refunded_for_item( $item_id ) );
			$refunded_qty = isset( $refunds[ $refund_id_index ][ 'quantity' ] ) ? intval( $refunds[ $refund_id_index ][ 'quantity' ] ) : 0;
			
			// Update bookings quantity, and maybe update bookings status if they were refunded
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$new_data = $booking->state === 'refunded' ? array( 'id' => $booking->id, 'quantity' => $refunded_qty ? $refunded_qty : $item_qty, 'status' => 'cancelled', 'active' => 0 ) : array( 'id' => $booking->id, 'quantity' => $refunded_qty ? $booking->quantity + $refunded_qty : $item_qty );
				$booking_data = bookacti_sanitize_booking_data( $new_data );
				$updated = bookacti_update_booking( $booking_data );
				
				// Trigger booking status change
				if( $updated && $item_booking[ 'type' ] === 'single' ) {
					if( $booking->state !== $booking_data[ 'status' ] ) {
						do_action( 'bookacti_booking_state_changed', $booking->id, $booking_data[ 'status' ], array( 'is_admin' => true, 'send_notifications' => false ) );
					}
				}
			}
			
			// Update refunds array bound to the booking
			if( $item_booking[ 'type' ] === 'single' ) { 
				if( $new_refunds ) { bookacti_update_metadata( 'booking', $item_booking[ 'id' ], array( 'refunds' => $new_refunds ) ); }
				else { bookacti_delete_metadata( 'booking', $item_booking[ 'id' ], array( 'refunds' ) ); }
			}
			
			// Update refunds array bound to the booking group
			if( $item_booking[ 'type' ] === 'group' ) { 
				if( $new_refunds ) { bookacti_update_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' => $new_refunds ) ); }
				else { bookacti_delete_metadata( 'booking_group', $item_booking[ 'id' ], array( 'refunds' ) ); }
				
				// Update the booking group status
				$status = isset( $item_booking[ 'bookings' ][ 0 ]->group_state ) ? $item_booking[ 'bookings' ][ 0 ]->group_state : $item_booking[ 'bookings' ][ 0 ]->state;
				if( $status === 'refunded' ) {
					$booking_group_data = bookacti_sanitize_booking_group_data( array( 'id' => $item_booking[ 'id' ], 'status' => 'cancelled', 'active' => 0 ) );
					$updated = bookacti_update_booking_group( $booking_group_data );

					// Trigger booking group status change
					if( $updated && $booking_group_data[ 'status' ] !== $status ) {
						do_action( 'bookacti_booking_group_state_changed', $item_booking[ 'id' ], $booking_group_data[ 'status' ], array( 'is_admin' => true, 'send_notifications' => false ) );
					}
				}
			}
		}
	}
}
add_action( 'woocommerce_refund_deleted', 'bookacti_update_booking_when_refund_is_deleted', 10, 2 );


/**
 * Format order item mata values in order pages in admin panel
 * Must be used since WC 3.0.0
 * @since 1.0.4
 * @version 1.8.0
 */
function bookacti_format_order_item_meta_values( $meta_value ) {
	// Format booking state
	$available_states = bookacti_get_booking_state_labels();
	if( array_key_exists( $meta_value, $available_states ) ) {
		return bookacti_format_booking_state( $meta_value );
	}

	// Format booked events
	else if( bookacti_is_json( $meta_value ) ) {
		$events = json_decode( $meta_value );
		if( is_array( $events ) && count( $events ) > 0 && is_object( $events[ 0 ] ) && isset( $events[ 0 ]->event_id ) ) {
			return bookacti_get_formatted_booking_events_list( $events );
		}
	}

	// Deprecated data
	// Format datetime
	else if( bookacti_sanitize_datetime( $meta_value ) ) {
		return bookacti_format_datetime( $meta_value );
	}

	return $meta_value;
}
add_filter( 'woocommerce_order_item_display_meta_value', 'bookacti_format_order_item_meta_values', 10, 1 );


/**
 * Display order item meta action buttons
 * @since 1.7.10
 * @version 1.8.0
 * @param int $item_id
 * @param WC_Order_Item $item
 * @param WC_Product $product
 */
function bookacti_display_order_item_meta_action_buttons( $item_id, $item, $product ) {
	$booking_id = 0;
	$booking_type = '';

	$meta_data = $item->get_formatted_meta_data();
	if( $meta_data ) {
		foreach( $meta_data as $meta_id => $meta ) {
			if( $meta->key === 'bookacti_booking_id' || $meta->key === 'bookacti_booking_group_id' ) {
				$booking_id = $meta->value;
				$booking_type = $meta->key === 'bookacti_booking_group_id' ? 'group' : 'single';
				break;
			}
		}
	}
?>
	<div class='bookacti-order-item-action-buttons' style='display: none;'>
		<?php if( $booking_id ) { ?>
		<div class='bookacti-order-item-go-to-booking-button'>
			<?php
				$link_to_booking = admin_url( 'admin.php?page=bookacti_bookings&status%5B0%5D=all&keep_default_status=1' );
				if( $booking_type === 'group' ) {
					$link_to_booking .= '&booking_group_id=' . $booking_id . '&group_by=booking_group';
				} else if( $booking_type === 'single' ) {
					$link_to_booking .= '&booking_id=' . $booking_id;
				}
			?>
			<a href='<?php echo esc_url( $link_to_booking ); ?>' target='_blank' class='edit_booking button'><?php esc_html_e( 'Edit the booking', 'booking-activities' ); ?></a>
		</div>
		<?php }

		do_action( 'bookacti_order_item_meta_booking_action_buttons', $item_id, $item, $product, $booking_id, $booking_type ); 
		?>
	</div>
<?php
}
add_action( 'woocommerce_after_order_itemmeta', 'bookacti_display_order_item_meta_action_buttons', 10, 3 );




// TEMPLATES

/**
 * Add shop managers to templates managers exceptions
 * 
 * @param array $exceptions
 * @return string
 */
function bookacti_add_shop_manager_to_template_managers_exceptions( $exceptions ) {
	$exceptions[] = 'shop_manager';
	return $exceptions;
}
add_filter( 'bookacti_managers_roles_exceptions', 'bookacti_add_shop_manager_to_template_managers_exceptions', 10, 1 );


/**
 * Bypass template manager check for shop managers
 * @version 1.7.19
 * @param boolean $allowed
 * @return boolean
 */
function bookacti_bypass_checks_for_shop_managers( $allowed ) {
	return bookacti_is_shop_manager() ? true : $allowed;
}
add_filter( 'bookacti_bypass_template_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 1 );
add_filter( 'bookacti_bypass_activity_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 1 );
add_filter( 'bookacti_bypass_form_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 1 );




// CUSTOM PRODUCTS OPTIONS

/**
 * Add 'Activity' custom product type option
 * @version 1.5.2
 * @param array $options_array
 * @return array
 */
function bookacti_add_product_type_option( $options_array ) { 
	$options_array[ 'bookacti_is_activity' ] = array(
			'id'            => '_bookacti_is_activity',
			'wrapper_class' => 'show_if_simple',
			/* translators: 'Activity' is the new type of product in WooCommerce */
			'label'         => __( 'Activity', 'booking-activities' ),
			/* translators: Description of the 'Activity' type of product in WooCommerce */
			'description'   => __( 'Activities are bookable according to the defined calendar, and expire in cart.', 'booking-activities' ),
			'default'       => 'no'
		);

	return $options_array; 
}
add_filter( 'product_type_options', 'bookacti_add_product_type_option', 100, 1 ); 


/**
 * Add 'Activity' custom product tab
 * @param array $tabs
 * @return array
 */
function bookacti_create_activity_tab( $tabs ) {
	$tabs[ 'activity' ] = array(
		'label'     => __( 'Activity', 'booking-activities' ),
		'target'    => 'bookacti_activity_options',
		'class'     => array( 'bookacti_show_if_activity', 'hide_if_grouped', 'hide_if_external' ),
		'priority'  => 20
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'bookacti_create_activity_tab', 10, 1 );


/**
 * Content of the activity tab
 * @version 1.8.0
 * @global int $thepostid
 */
function bookacti_activity_tab_content() {
	global $thepostid;
	?>
	<div id='bookacti_activity_options' class='panel woocommerce_options_panel'>
		<div class='options_group'>
			<?php
				$form_id		= '_bookacti_form'; 
				$forms			= bookacti_get_forms( bookacti_format_form_filters( array( 'active' => 1 ) ) );
				$current_form	= get_post_meta( $thepostid, $form_id, true );
				$can_edit_forms	= current_user_can( 'bookacti_edit_forms' );
			?>
			<p class='form-field <?php echo $form_id; ?>_field' >
				<label for='<?php echo $form_id; ?>'>
				<?php
					echo esc_html__( 'Booking form', 'booking-activities' );
				?>
				</label>
				<select id='<?php echo $form_id; ?>' 
						name='<?php echo $form_id; ?>' 
						class='select short'
						<?php if( $can_edit_forms ) { echo 'style="margin-right:10px;"'; } ?> >
				<?php
					$forms_nb = 0;
					foreach( $forms as $form ) {
						// If the user is not allowed to manage this form, do not display it at all
						if( ! bookacti_user_can_manage_form( $form->id ) ) { continue; }
						++$forms_nb;
						?>
						<option value='<?php echo esc_attr( $form->id ); ?>' <?php echo selected( $form->id, $current_form, true ); ?>>
							<?php echo esc_html( apply_filters( 'bookacti_translate_text', $form->title ) ); ?>
						</option>
						<?php
					}
				?>
				</select>
				<span class='bookacti-form-selectbox-link' data-form-selectbox-id='<?php echo $form_id; ?>'>
				<?php 
					if( $can_edit_forms ) {
						if( $forms_nb ) {
							?>
							<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $current_form ) ); ?>' target='_blank'>
								<?php esc_html_e( 'Edit this form', 'booking-activities' ); ?>
							</a>
							<?php
						} else {
							?>
							<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>' target='_blank'>
								<?php esc_html_e( 'Create a form', 'booking-activities' ); ?>
							</a>
							<?php
						}
					}
				?>
				</span>
			</p>
		</div>
	</div>
<?php
}
add_action( 'woocommerce_product_data_panels', 'bookacti_activity_tab_content' );


/**
 * Save custom activity product type and activity tab content
 * @version 1.8.0
 * @param int $post_id
 */
function bookacti_save_custom_product_type_and_tab_content( $post_id ) { 
	if( ! empty( $_POST['_bookacti_is_activity'] ) ) {
		update_post_meta( $post_id, '_bookacti_is_activity', sanitize_text_field( 'yes' ) );
	} else {
		update_post_meta( $post_id, '_bookacti_is_activity', sanitize_text_field( 'no' ) );
	}

	if( isset( $_POST['_bookacti_form'] ) ) {
		update_post_meta( $post_id, '_bookacti_form', intval( $_POST['_bookacti_form'] ) );
	}
}
add_action( 'woocommerce_process_product_meta', 'bookacti_save_custom_product_type_and_tab_content', 30, 1 ); 




// CUSTOM VARIATION FIELDS

/**
 * Add custom variation product type option
 * @version 1.7.14
 * @param int $loop
 * @param array $variation_data
 * @param WP_Post $variation
 */
function bookacti_add_variation_option( $loop, $variation_data, $variation ) { 
?>
	<label>
		<input type='hidden' name='bookacti_variable_is_activity[<?php echo $loop; ?>]' value='no' />
		<input 
			type='checkbox' 
			id='bookacti_variable_is_activity_<?php echo esc_attr( $loop ); ?>' 
			class='checkbox bookacti_variable_is_activity' 
			name='bookacti_variable_is_activity[<?php echo esc_attr( $loop ); ?>]' 
			value='yes'
			<?php checked( 'yes', esc_attr( get_post_meta( $variation->ID, 'bookacti_variable_is_activity', true ) ), true ); ?> 
		/> 
		<?php esc_html_e( 'Activity', 'booking-activities' ); ?> 
		<?php 
			/* translators: Help tip to explain why and when you should check the 'Activity' type of product in WooCommerce */
			echo wc_help_tip( esc_html__( 'Enable this option if the product is a bookable activity', 'booking-activities' ) ); 
		?>
	</label>
<?php
}
add_action( 'woocommerce_variation_options', 'bookacti_add_variation_option', 10, 3 ); 


/**
 * Add custom fields for activity variation product type
 * @version 1.8.0
 * @param int $loop
 * @param array $variation_data
 * @param WP_Post $variation
 */
function bookacti_add_variation_fields( $loop, $variation_data, $variation ) { 
	$form_id		= 'bookacti_variable_form'; 
	$forms			= bookacti_get_forms( bookacti_format_form_filters( array( 'active' => 1 ) ) );
	$current_form	= get_post_meta( $variation->ID, $form_id, true );
	$can_edit_forms	= current_user_can( 'bookacti_edit_forms' );

	// Check if variation is flagged as activity
	$is_variation_activity = get_post_meta( $variation->ID, 'bookacti_variable_is_activity', true );
	$variation_class = $is_variation_activity === 'yes' ? 'bookacti-show-fields' : 'bookacti-hide-fields';
	?>
	<div class='show_if_variation_activity <?php echo $variation_class; ?>'>
		<p class='form-row form-row-full bookacti-woo-title'>
			<strong><?php esc_html_e( 'Activity', 'booking-activities' ) ?></strong>
		</p>
		<p class='form-row form-row-full' >
			<label for='<?php echo $form_id . '_' . esc_attr( $loop ); ?>' ><?php esc_html_e( 'Booking form', 'booking-activities' ); ?></label>
			<select name='<?php echo $form_id; ?>[<?php echo esc_attr( $loop ); ?>]' 
					id='<?php echo $form_id . '_' . esc_attr( $loop ); ?>' 
					class='<?php echo $form_id; ?>' 
					data-loop='<?php echo esc_attr( $loop ); ?>'
					<?php if( $can_edit_forms ) { echo 'style="margin-right:10px;"'; } ?>>
				<?php
				$forms_nb = 0;
				foreach( $forms as $form ) {
					// If the user is not allowed to manage this form, do not display it at all
					if( ! bookacti_user_can_manage_form( $form->id ) ) { continue; }
					++$forms_nb;
					?>
					<option value='<?php echo esc_attr( $form->id ); ?>' <?php echo selected( $form->id, $current_form, true ); ?>>
						<?php echo esc_html( apply_filters( 'bookacti_translate_text', $form->title ) ); ?>
					</option>
					<?php
				}
			?>
			</select>
			<span class='bookacti-form-selectbox-link' data-form-selectbox-id='<?php echo $form_id . '_' . esc_attr( $loop ); ?>'>
			<?php 
				if( $can_edit_forms ) {
					if( $forms_nb ) {
						?>
						<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $current_form ) ); ?>' target='_blank'>
							<?php esc_html_e( 'Edit this form', 'booking-activities' ); ?>
						</a>
						<?php
					} else {
						?>
						<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>' target='_blank'>
							<?php esc_html_e( 'Create a form', 'booking-activities' ); ?>
						</a>
						<?php
					}
				}
			?>
			</span>
		</p>
	</div>
<?php
}
add_action( 'woocommerce_product_after_variable_attributes', 'bookacti_add_variation_fields', 10, 3 ); 


/**
 * Save custom variation product
 * @version 1.8.0
 * @param int $post_id
 */
function bookacti_save_variation_option( $post_id ) {
	$variable_post_id	= is_array( $_POST[ 'variable_post_id' ] ) ? $_POST[ 'variable_post_id' ] : array();
	$keys				= array_keys( $variable_post_id );

	// Save data for each variation
	foreach ( $keys as $key ) {
		$variation_id = intval( $variable_post_id[ $key ] );
		if( $variation_id ) {
			// Save 'is_activity' checkbox
			if ( isset( $_POST[ 'bookacti_variable_is_activity' ][ $key ] ) ) {
				$variable_is_activity = $_POST[ 'bookacti_variable_is_activity' ][ $key ] === 'yes' ? 'yes' : 'no';
				update_post_meta( $variation_id, 'bookacti_variable_is_activity', $variable_is_activity );
			}

			// Save form
			if ( isset( $_POST[ 'bookacti_variable_form' ][ $key ] ) ) {
				$variable_form = intval( $_POST[ 'bookacti_variable_form' ][ $key ] );
				update_post_meta( $variation_id, 'bookacti_variable_form', $variable_form );
			}
		}
	}
}
add_action( 'woocommerce_save_product_variation', 'bookacti_save_variation_option', 10, 1 );


/**
 * Load custom variation settings in order to use it in frontend
 * 
 * @since 1.1.0 (was load_variation_settings_fields before)
 * @version 1.8.0
 * @param array $variations
 * @return array
 */
function bookacti_load_variation_settings_fields( $variations ) {
	$variations[ 'bookacti_is_activity' ]	= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_is_activity', true ) === 'yes';
	$variations[ 'bookacti_form_id' ]		= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_form', true );
	return $variations;
}
add_filter( 'woocommerce_available_variation', 'bookacti_load_variation_settings_fields' );




// ROLES AND CAPABILITIES

/**
 * Set Booking Activities roles and capabilities related to WooCommerce
 * @version 1.8.0
 */
function bookacti_set_role_and_cap_for_woocommerce() {
	$shop_manager = get_role( 'shop_manager' );
	if( $shop_manager ) { 
		$shop_manager->add_cap( 'bookacti_manage_booking_activities' );
		$shop_manager->add_cap( 'bookacti_manage_bookings' );
		$shop_manager->add_cap( 'bookacti_manage_templates' );
		$shop_manager->add_cap( 'bookacti_manage_forms' );
		$shop_manager->add_cap( 'bookacti_manage_booking_activities_settings' );
		$shop_manager->add_cap( 'bookacti_read_templates' );
		$shop_manager->add_cap( 'bookacti_create_templates' );
		$shop_manager->add_cap( 'bookacti_edit_templates' );
		$shop_manager->add_cap( 'bookacti_delete_templates' );
		$shop_manager->add_cap( 'bookacti_create_activities' );
		$shop_manager->add_cap( 'bookacti_edit_activities' );
		$shop_manager->add_cap( 'bookacti_delete_activities' );
		$shop_manager->add_cap( 'bookacti_edit_bookings' );
		$shop_manager->add_cap( 'bookacti_delete_bookings' );
		$shop_manager->add_cap( 'bookacti_create_forms' );
		$shop_manager->add_cap( 'bookacti_edit_forms' );
		$shop_manager->add_cap( 'bookacti_delete_forms' );
	}
}
add_action( 'bookacti_set_capabilities', 'bookacti_set_role_and_cap_for_woocommerce' );
add_action( 'woocommerce_installed', 'bookacti_set_role_and_cap_for_woocommerce' );


/**
 * Unset Booking Activities roles and capabilities related to WooCommerce (to be used on wp_roles_init)
 */
function bookacti_unset_role_and_cap_for_woocommerce_on_woocommerce_uninstall() {
	if( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN === 'woocommerce/woocommerce.php' ) {
		bookacti_unset_role_and_cap_for_woocommerce();
	}
}
add_action( 'wp_roles_init', 'bookacti_unset_role_and_cap_for_woocommerce_on_woocommerce_uninstall' );


/**
 * Unset Booking Activities roles and capabilities related to WooCommerce
 * @version 1.8.0
 */
function bookacti_unset_role_and_cap_for_woocommerce() {
	$shop_manager = get_role( 'shop_manager' );
	if( $shop_manager ) {
		$shop_manager->remove_cap( 'bookacti_manage_booking_activities' );
		$shop_manager->remove_cap( 'bookacti_manage_bookings' );
		$shop_manager->remove_cap( 'bookacti_manage_templates' );
		$shop_manager->remove_cap( 'bookacti_manage_forms' );
		$shop_manager->remove_cap( 'bookacti_manage_booking_activities_settings' );
		$shop_manager->remove_cap( 'bookacti_read_templates' );
		$shop_manager->remove_cap( 'bookacti_create_templates' );
		$shop_manager->remove_cap( 'bookacti_edit_templates' );
		$shop_manager->remove_cap( 'bookacti_delete_templates' );
		$shop_manager->remove_cap( 'bookacti_create_activities' );
		$shop_manager->remove_cap( 'bookacti_edit_activities' );
		$shop_manager->remove_cap( 'bookacti_delete_activities' );
		$shop_manager->remove_cap( 'bookacti_edit_bookings' );
		$shop_manager->remove_cap( 'bookacti_delete_bookings' );
		$shop_manager->remove_cap( 'bookacti_create_forms' );
		$shop_manager->remove_cap( 'bookacti_edit_forms' );
		$shop_manager->remove_cap( 'bookacti_delete_forms' );
	}
}
add_action( 'bookacti_unset_capabilities', 'bookacti_unset_role_and_cap_for_woocommerce' );


/**
 * Allow Users having the bookacti_manage_booking_activities capability to access the backend
 * @since 1.8.10
 * @param bool $prevent_access
 * @return bool
 */
function bookacti_wc_prevent_admin_access( $prevent_access ) {
	if( $prevent_access ) { 
		if ( current_user_can( 'bookacti_manage_booking_activities' ) ) { $prevent_access = false; }
	}
	return $prevent_access;
}
add_filter( 'woocommerce_prevent_admin_access', 'bookacti_wc_prevent_admin_access', 10, 1 );