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
 * Change booking quantity when a partial refund in done, 
 * Change booking state when a total refund is done
 * 
 * @since 1.2.0 (was named bookacti_update_booking_when_order_item_is_refunded before)
 * 
 * @param int $refund_id
 * @param array $args
 */
function bookacti_update_order_bookings_on_refund( $refund_id, $args ) {

	$refunded_items	= $args[ 'line_items' ];

	// If a refund has been perform on one or several items
	if( $refunded_items ) {
		bookacti_update_order_bookings_on_items_refund( $refunded_items, $refund_id );

	// If the order state has changed to 'Refunded'
	} else {
		$order_id = intval( $args[ 'order_id' ] );
		bookacti_update_order_bookings_on_order_refund( $order_id, $refund_id );
	}
}
add_action( 'woocommerce_refund_created', 'bookacti_update_order_bookings_on_refund', 10, 2 );


/**
 * If refund is processed automatically set booking order item refund method to 'auto'
 * 
 * @since 1.0.0
 * 
 * @param array $refund
 * @param boolean $result
 */
function bookacti_set_order_item_refund_method_to_auto( $refund, $result ) {
	if( $result ) {
		wc_update_order_item_meta( $refund[ 'refunded_item_id' ], '_bookacti_refund_method', 'auto' );
	}
}
add_action( 'woocommerce_refund_processed', 'bookacti_set_order_item_refund_method_to_auto', 10, 2 );


/**
 * Change booking quantity and status when a refund is deleted
 * @version 1.8.3
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
		$booking_id			= wc_get_order_item_meta( $item_id, 'bookacti_booking_id', true );
		$booking_group_id	= wc_get_order_item_meta( $item_id, 'bookacti_booking_group_id', true );

		// Check if the order item is bound to a booking (group)
		if( empty( $booking_id ) && empty( $booking_group_id ) ) { continue; }

		$booking_type = empty( $booking_group_id ) ? 'single' : 'group';

		// Check if the deleted refund is bound to this booking (group)
		if( $booking_type === 'group' ) {
			$refunds = bookacti_get_metadata( 'booking_group', $booking_group_id, 'refunds', true );
		} else {
			$refunds = bookacti_get_metadata( 'booking', $booking_id, 'refunds', true );
		}
		if( ! $refunds ) { continue; }
		
		$refund_id_index = array_search( $refund_id, $refunds );
		if( $refund_id_index === false ) { continue; }

		// Compute new quantity 
		// (we still need to substract $refunded_qty because it is possible to have multiple refunds, 
		// so even if you delete one, you still need to substract the quantity of the others)
		$init_qty		= $item[ 'qty' ];
		$refunded_qty	= $order->get_qty_refunded_for_item( $item_id ) ? abs( $order->get_qty_refunded_for_item( $item_id ) ) : 0;
		$new_qty		= $init_qty - $refunded_qty;

		// Gether the booking (group) data
		$state		= 'cancelled';
		$active		= 0;
		$old_qty	= 0;
		if( $booking_type === 'group' ) {
			$booking_group = bookacti_get_booking_group_by_id( $booking_group_id );
			if( $booking_group ) {
				$state		= $booking_group->state;
				$active		= $booking_group->active;
				$old_qty	= bookacti_get_booking_group_quantity( $booking_group_id );
			}
		} else {
			$booking = bookacti_get_booking_by_id( $booking_id );
			if( $booking ) {
				$state		= $booking->state;
				$active		= $booking->active;
				$old_qty	= $booking->quantity;
			}
		}


		// If the booking (group) is still active, 
		// we need to check the booking (group) availability before updating
		if( $active && $old_qty !== $new_qty ) {

			// Try to update booking (group) quantity
			if( $booking_type === 'group' ) {
				$response = bookacti_controller_update_booking_group_quantity( $booking_group_id, $new_qty, false, 'admin' );
			} else {
				$response = bookacti_controller_update_booking_quantity( $booking_id, $new_qty, 'admin' );
			}

			// If there is not enough availability...
			if( $response[ 'status' ] !== 'success' ) {

				// Reduce item quantity to fit the booking (group)
				$item_args = array( 'qty' => $old_qty );
				$product = $item->get_product();
				if( $product ) {
					if( $product->backorders_require_notification() && $product->is_on_backorder( $old_qty ) ) {
						$item->add_meta_data( apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ), $item ), $old_qty - max( 0, $product->get_stock_quantity() ), true );
					}
					$old_price = wc_get_price_excluding_tax( $product, array( 'qty' => $old_qty ) );
					$item_args[ 'subtotal' ] = $old_price;
					$item_args[ 'total' ] = $old_price;
				}
				$item->set_props( $item_args );
				$item->save();
				
				// Prepare message
				if( isset( $response[ 'error' ] ) && $response[ 'error' ] === 'qty_sup_to_avail' ) {
					$message = /* translators: %1$s is a variable number of bookings. */
							sprintf( _n( 'You want to add %1$s booking to your cart', 'You want to add %1$s bookings to your cart', $new_qty, 'booking-activities' ), $new_qty )
					. ' ' . sprintf( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $response[ 'availability' ], 'booking-activities' ), $response[ 'availability' ] )
					. ' ' . __( 'Please choose another event or decrease the quantity.', 'booking-activities' );

				} else if( isset( $response[ 'error' ] ) && $response[ 'error' ] === 'no_availability' ) {
					$message = __( 'This event is no longer available. Please choose another event.', 'booking-activities' );

				} else {
					$message = __( 'Error occurs while trying to update booking quantity.', 'booking-activities' );
				}

				// Stop the script execution and feedback user
				wp_die( esc_html( $message ) );
			}


		// If the booking (group) is not active,
		// we can force the booking quantity to update to the new value
		} else if( ! $active && $new_qty > 0 ) {

			$updated1 = $updated2 = true;

			// Update booking (group) quantity
			if( $booking_type === 'group' ) {

				$updated1 = bookacti_force_update_booking_group_bookings_quantity( $booking_group_id, $new_qty );

				// If the booking group was 'refunded', 
				// now that the refunds has been deleted, we need to change its state to cancelled
				if( $state === 'refunded' ) {
					$updated2 = bookacti_update_booking_group_state( $booking_group_id, 'cancelled' );
					if( $updated2 ) {
						wc_delete_order_item_meta( $item_id, '_bookacti_refund_method' );
						do_action( 'bookacti_booking_group_state_changed', $booking_group_id, 'cancelled', array( 'is_admin' => true, 'send_notifications' => false ) );
					}
				}

				// Also update bookings of the group if some were 'refunded'
				// (it is possible that some bookings are 'refunded' but not the whole group)
				if( $updated1 ) {
					bookacti_update_booking_group_bookings_state( $booking_group_id, 'cancelled', 0, 'refunded' );
				}


			// For single bookings, first check if the quantity need to be updated
			} else if( $old_qty !== $new_qty ) {

				$updated1 = bookacti_force_update_booking_quantity( $booking_id, $new_qty );

				// If the booking was 'refunded', 
				// now that the refunds has been deleted, we need to change its state to cancelled
				if( $state === 'refunded' ) {
					$updated2 = bookacti_update_booking_state( $booking_id, 'cancelled' );
					if( $updated2 ) {
						wc_delete_order_item_meta( $item_id, '_bookacti_refund_method' );
						do_action( 'bookacti_booking_state_changed', $booking_id, 'cancelled', array( 'is_admin' => true, 'send_notifications' => false ) );
					}
				}
			}

			if( $updated1 === false || $updated2 === false ) {
				$message = __( 'Error occurs while trying to update booking quantity.', 'booking-activities' );
				wp_die( esc_html( $message ) );
			}
		}

		// Delete booking refund metadata
		unset( $refunds[ $refund_id_index ] );
		if( ! empty( $refunds ) ) {
			if( $booking_type === 'group' ) {
				bookacti_update_metadata( 'booking_group', $booking_group_id, array( 'refunds' => $refunds ) );
			} else {
				bookacti_update_metadata( 'booking', $booking_id, array( 'refunds' => $refunds ) );
			}
		} else {
			if( $booking_type === 'group' ) {
				bookacti_delete_metadata( 'booking_group', $booking_group_id, array( 'refunds' ) );
			} else {
				bookacti_delete_metadata( 'booking', $booking_id, array( 'refunds' ) );
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