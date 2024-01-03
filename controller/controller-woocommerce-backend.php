<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ORDERS

/**
 * Change booking quantity when admin changes order item quantity
 * @version 1.9.0
 * @param boolean $check
 * @param int $item_id
 * @param string $meta_key
 * @param string $meta_value
 * @param string $prev_value
 * @return boolean
 */
function bookacti_update_booking_qty_with_order_item_qty( $check, $item_id, $meta_key, $meta_value, $prev_value ) {
	if( $meta_key !== '_qty' ) { return $check; }
	
	// Get the item
	$item_id = intval( $item_id );
	$item = WC_Order_Factory::get_order_item( $item_id );
	if( ! $item ) { return $check; }
	
	// Get the quantity
	$old_qty = intval( $item->get_quantity() );
	$new_qty = intval( $meta_value );
	$delta_qty = $old_qty - $new_qty;
	if( $old_qty === $new_qty ) { return $check; }
	
	// THE CASE WHERE THE NEW QUANTITY IS 0 IS TREATED IN bookacti_cancel_bookings_if_order_item_qty_is_null BELOW
	if( $new_qty <= 0 ) { return $check; }
	
	// Get the associated bookings
	$items_bookings = bookacti_wc_get_order_items_bookings( array( $item ) );
	if( empty( $items_bookings[ $item_id ] ) ) { return $check; }
	
	// Update each booking quantity
	foreach( $items_bookings[ $item_id ] as $item_booking ) {
		// Cancel the single bookings
		foreach( $item_booking[ 'bookings' ] as $booking ) {
			if( $new_qty <= 0 ) { continue; } 
			$booking_data = array( 'id' => $booking->id, 'quantity' => $old_qty > 0 ? $booking->quantity - $delta_qty : $new_qty );
			$booking_data = bookacti_sanitize_booking_data( $booking_data );
			bookacti_update_booking( $booking_data );
		}
	}
	
	return $check;
}
add_filter( 'update_order_item_metadata', 'bookacti_update_booking_qty_with_order_item_qty', 20, 5 );


/**
 * Cancel bookings when admin changes the associated order item quantity to 0
 * @since 1.1.0
 * @version 1.9.0
 * @param int $order_id
 * @param array $items
 */
function bookacti_cancel_bookings_if_order_item_qty_is_null( $order_id, $items ) { 
	if( empty( $items[ 'order_item_id' ] ) ) { return; }

	foreach( $items[ 'order_item_id' ] as $item_id ) {
		// Get the item
		$item = WC_Order_Factory::get_order_item( $item_id );
		if( ! $item ) { continue; }
		
		// Get the quantity
		$quantity = isset( $items[ 'order_item_qty' ][ $item_id ] ) ? wc_check_invalid_utf8( wp_unslash( $items[ 'order_item_qty' ][ $item_id ] ) ) : null;
		if( $quantity !== '0' ) { continue; }
		
		// Get the associated bookings
		$items_bookings = bookacti_wc_get_order_items_bookings( array( $item ) );
		if( empty( $items_bookings[ $item_id ] ) ) { continue; }
		
		// The item will be removed, so cancel the associated bookings
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			// Cancel the single bookings
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$booking_data = array( 'id' => $booking->id, 'order_id' => -1 );
				if( $booking->active ) { $booking_data[ 'status' ] = 'cancelled'; $booking_data[ 'active' ] = 0; }
				$booking_data = bookacti_sanitize_booking_data( $booking_data );
				bookacti_update_booking( $booking_data );
			}
			
			// Cancel the booking groups
			if( $item_booking[ 'type' ] === 'group' ) {
				$booking_group_data = array( 'id' => $item_booking[ 'id' ], 'order_id' => -1 );
				if( ! empty( $item_booking[ 'bookings' ][ 0 ]->group_active ) ) { $booking_group_data[ 'status' ] = 'cancelled'; $booking_group_data[ 'active' ] = 0; }
				$booking_group_data = bookacti_sanitize_booking_group_data( $booking_group_data );
				bookacti_update_booking_group( $booking_group_data );
			}
		}
	}
}
add_action( 'woocommerce_before_save_order_items', 'bookacti_cancel_bookings_if_order_item_qty_is_null', 10, 2 );


/**
 * Cancel bookings when admin removes the associated order item
 * @since 1.1.0
 * @version 1.9.0
 * @param int $item_id
 */
function bookacti_cancel_bookings_when_order_item_is_deleted( $item_id ) {
	// Get the item
	$item_id = intval( $item_id );
	$item = WC_Order_Factory::get_order_item( $item_id );
	if( ! $item ) { return; }
	
	// Get the associated bookings
	$items_bookings = bookacti_wc_get_order_items_bookings( array( $item ) );
	if( empty( $items_bookings[ $item_id ] ) ) { return; }
	
	// The item will be removed, so cancel the associated bookings
	foreach( $items_bookings[ $item_id ] as $item_booking ) {
		// Cancel the single bookings
		foreach( $item_booking[ 'bookings' ] as $booking ) {
			$booking_data = array( 'id' => $booking->id, 'order_id' => -1 );
			if( $booking->active ) { $booking_data[ 'status' ] = 'cancelled'; $booking_data[ 'active' ] = 0; }
			$booking_data = bookacti_sanitize_booking_data( $booking_data );
			bookacti_update_booking( $booking_data );
		}

		// Cancel the booking groups
		if( $item_booking[ 'type' ] === 'group' ) {
			$booking_group_data = array( 'id' => $item_booking[ 'id' ], 'order_id' => -1 );
			if( ! empty( $item_booking[ 'bookings' ][ 0 ]->group_active ) ) { $booking_group_data[ 'status' ] = 'cancelled'; $booking_group_data[ 'active' ] = 0; }
			$booking_group_data = bookacti_sanitize_booking_group_data( $booking_group_data );
			bookacti_update_booking_group( $booking_group_data );
		}
	}
}
add_action( 'woocommerce_before_delete_order_item', 'bookacti_cancel_bookings_when_order_item_is_deleted', 10, 1 );


/**
 * Change booking quantity and status when a refund is deleted
 * @version 1.12.9
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
			$refund_id_index = array_search( $refund_id, $refunds, true ); // The refunds array used to be an array of ids only
			if( $refund_id_index === false ) {
				if( isset( $refunds[ $refund_id ] ) ) { $refund_id_index = $refund_id; }
			}
			if( $refund_id_index === false ) { continue; }
		
			$new_refunds = $refunds;
			unset( $new_refunds[ $refund_id_index ] );
			
			// Get the new item quantity 
			// (we still need to substract $refunded_qty because it is possible to have multiple refunds, 
			// so even if you delete one, you still need to substract the quantity of the others)
			$item_qty = $item->get_quantity() - abs( intval( $order->get_qty_refunded_for_item( $item_id ) ) );
			$refunded_qty = isset( $refunds[ $refund_id_index ][ 'quantity' ] ) ? intval( $refunds[ $refund_id_index ][ 'quantity' ] ) : 0;
			
			// Update bookings quantity, and maybe update bookings status if they were refunded
			foreach( $item_booking[ 'bookings' ] as $booking ) {
				$new_data = $booking->state === 'refunded' ? array( 'id' => $booking->id, 'quantity' => $refunded_qty ? $refunded_qty : $item_qty, 'status' => 'cancelled', 'active' => 0 ) : array( 'id' => $booking->id, 'quantity' => $refunded_qty ? $booking->quantity + $refunded_qty : $item_qty );
				$booking_data = bookacti_sanitize_booking_data( $new_data );
				$updated = bookacti_update_booking( $booking_data );
				
				// Trigger booking status change
				if( $updated && $item_booking[ 'type' ] === 'single' ) {
					if( $booking->state !== $booking_data[ 'status' ] && $booking_data[ 'status' ] ) {
						do_action( 'bookacti_booking_state_changed', $booking, $booking_data[ 'status' ], array( 'is_admin' => true, 'send_notifications' => false ) );
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
					if( $updated && $booking_group_data[ 'status' ] !== $status && $booking_group_data[ 'status' ] ) {
						do_action( 'bookacti_booking_group_state_changed', $item_booking[ 'id' ], $item_booking[ 'bookings' ], $booking_group_data[ 'status' ], array( 'is_admin' => true, 'send_notifications' => false ) );
					}
				}
			}
		}
	}
}
add_action( 'woocommerce_refund_deleted', 'bookacti_update_booking_when_refund_is_deleted', 10, 2 );




// TEMPLATES

/**
 * Add shop managers to managers exceptions
 * @since 1.15.5 (was bookacti_add_shop_manager_to_template_managers_exceptions)
 * @param array $exceptions
 * @return string
 */
function bookacti_wc_add_managers_roles_exceptions( $exceptions ) {
	$exceptions[] = 'shop_manager';
	return $exceptions;
}
add_filter( 'bookacti_managers_roles_exceptions', 'bookacti_wc_add_managers_roles_exceptions', 10, 1 );


/**
 * Bypass template manager check for shop managers
 * @version 1.12.7
 * @param boolean $true
 * @param int $user_id Default to current user
 * @return boolean
 */
function bookacti_bypass_checks_for_shop_managers( $true, $user_id = 0 ) {
	return bookacti_is_shop_manager( $user_id ) ? true : $true;
}
add_filter( 'bookacti_bypass_template_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 2 );
add_filter( 'bookacti_bypass_activity_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 2 );
add_filter( 'bookacti_bypass_form_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 2 );




// CUSTOM PRODUCTS OPTIONS

/**
 * Add 'Activity' custom product type option
 * @version 1.14.0
 * @param array $options_array
 * @return array
 */
function bookacti_add_product_type_option( $options_array ) { 
	$options_array[ 'bookacti_is_activity' ] = array(
		'id'            => '_bookacti_is_activity',
		'wrapper_class' => 'hide_if_grouped hide_if_external hide_if_variable',
		'label'         => esc_html__( 'Activity', 'booking-activities' ),
		/* translators: Description of the 'Activity' type of product in WooCommerce */
		'description'   => esc_html__( 'Activities are bookable according to the defined calendar, and expire in cart.', 'booking-activities' ),
		'default'       => 'no'
	);
	return $options_array; 
}
add_filter( 'product_type_options', 'bookacti_add_product_type_option', 100, 1 ); 


/**
 * Add 'Activity' custom product tab
 * @version 1.12.0
 * @param array $tabs
 * @return array
 */
function bookacti_create_activity_tab( $tabs ) {
	$tabs[ 'activity' ] = array(
		'label'     => esc_html__( 'Activity', 'booking-activities' ),
		'target'    => 'bookacti_activity_options',
		'class'     => array( 'bookacti_show_if_activity', 'hide_if_grouped', 'hide_if_external' ),
		'priority'  => 20
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'bookacti_create_activity_tab', 10, 1 );


/**
 * Content of the activity tab
 * @version 1.15.17
 * @global WP_Post $post
 */
function bookacti_activity_tab_content() {
	global $post;
	$product = wc_get_product( $post );
	?>
	<div id='bookacti_activity_options' class='panel woocommerce_options_panel'>
		<div class='options_group'>
			<?php
				$form_id_key    = '_bookacti_form'; 
				$forms          = bookacti_get_forms( bookacti_format_form_filters( array( 'active' => 1 ) ) );
				$current_form   = $product->get_meta( $form_id_key );
				$can_edit_forms = current_user_can( 'bookacti_edit_forms' );
			?>
			<p class='form-field <?php echo $form_id_key; ?>_field' >
				<label for='<?php echo $form_id_key; ?>'>
				<?php
					echo esc_html__( 'Booking form', 'booking-activities' );
				?>
				</label>
				<select id='<?php echo $form_id_key; ?>' 
						name='<?php echo $form_id_key; ?>' 
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
							<?php echo ! empty( $form->title ) ? esc_html( apply_filters( 'bookacti_translate_text', $form->title ) ) : sprintf( esc_html__( 'Form #%d', 'booking-activities' ), $form->id ); ?>
						</option>
						<?php
					}
				?>
				</select>
				<span class='bookacti-form-selectbox-link' data-form-selectbox-id='<?php echo $form_id_key; ?>'>
				<?php 
					if( $can_edit_forms ) {
						if( $forms_nb ) {
							?>
							<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $current_form ) ); ?>'>
								<?php esc_html_e( 'Edit this form', 'booking-activities' ); ?>
							</a>
							<?php
						} else {
							?>
							<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>'>
								<?php esc_html_e( 'Create a form', 'booking-activities' ); ?>
							</a>
							<?php
						}
					}
				?>
				</span>
			</p>
		</div>
		<?php do_action( 'bookacti_activity_tab_content_after' ); ?>
	</div>
<?php
}
add_action( 'woocommerce_product_data_panels', 'bookacti_activity_tab_content' );


/**
 * Save custom activity product type and activity tab content
 * @version 1.15.17
 * @param WC_Product $product
 */
function bookacti_save_custom_product_type_and_tab_content( $product ) { 
	$is_activity = ! empty( $_POST['_bookacti_is_activity'] ) ? 'yes' : 'no';
	$product->update_meta_data( '_bookacti_is_activity', sanitize_text_field( $is_activity ) );

	if( isset( $_POST['_bookacti_form'] ) ) {
		$product->update_meta_data( '_bookacti_form', intval( $_POST['_bookacti_form'] ) );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'bookacti_save_custom_product_type_and_tab_content', 10, 1 ); 



// CUSTOM VARIATION FIELDS

/**
 * Add custom variation product type option
 * @version 1.15.17
 * @param int $loop
 * @param array $variation_data
 * @param WP_Post $post
 */
function bookacti_add_variation_option( $loop, $variation_data, $post ) {
	$variation = wc_get_product( $post );
	/* translators: Help tip to explain why and when you should check the 'Activity' type of product in WooCommerce */
	$tip = esc_html__( 'Enable this option if the product is a bookable activity', 'booking-activities' );
?>
	<label class='tips' data-tip='<?php echo $tip; ?>'>
		<?php esc_html_e( 'Activity', 'booking-activities' ); ?> 
		<input type='hidden' name='bookacti_variable_is_activity[<?php echo $loop; ?>]' value='no' />
		<input 
			type='checkbox' 
			id='bookacti_variable_is_activity_<?php echo esc_attr( $loop ); ?>' 
			class='checkbox bookacti_variable_is_activity' 
			name='bookacti_variable_is_activity[<?php echo esc_attr( $loop ); ?>]' 
			value='yes'
			<?php checked( 'yes', esc_attr( $variation->get_meta( 'bookacti_variable_is_activity' ) ), true ); ?> 
		/>
	</label>
<?php
}
add_action( 'woocommerce_variation_options', 'bookacti_add_variation_option', 10, 3 ); 


/**
 * Add custom fields for activity variation product type
 * @version 1.15.17
 * @param int $loop
 * @param array $variation_data
 * @param WP_Post $post
 */
function bookacti_add_variation_fields( $loop, $variation_data, $post ) {
	$variation      = wc_get_product( $post );
	$form_id_key    = 'bookacti_variable_form'; 
	$forms          = bookacti_get_forms( bookacti_format_form_filters( array( 'active' => 1 ) ) );
	$current_form   = $variation->get_meta( $form_id_key );
	$can_edit_forms = current_user_can( 'bookacti_edit_forms' );

	// Check if variation is flagged as activity
	$is_variation_activity = $variation->get_meta( 'bookacti_variable_is_activity' );
	$variation_class = $is_variation_activity === 'yes' ? 'bookacti-show-fields' : 'bookacti-hide-fields';
	?>
	<div class='show_if_variation_activity <?php echo $variation_class; ?>'>
		<p class='form-row form-row-full bookacti-woo-title'>
			<strong><?php esc_html_e( 'Activity', 'booking-activities' ) ?></strong>
		</p>
		<p class='form-row form-row-full' >
			<label for='<?php echo $form_id_key . '_' . esc_attr( $loop ); ?>' ><?php esc_html_e( 'Booking form', 'booking-activities' ); ?></label>
			<select name='<?php echo $form_id_key; ?>[<?php echo esc_attr( $loop ); ?>]' 
					id='<?php echo $form_id_key . '_' . esc_attr( $loop ); ?>' 
					class='<?php echo $form_id_key; ?>' 
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
						<?php echo ! empty( $form->title ) ? esc_html( apply_filters( 'bookacti_translate_text', $form->title ) ) : sprintf( esc_html__( 'Form #%d', 'booking-activities' ), $form->id ); ?>
					</option>
					<?php
				}
			?>
			</select>
			<span class='bookacti-form-selectbox-link' data-form-selectbox-id='<?php echo $form_id_key . '_' . esc_attr( $loop ); ?>'>
			<?php 
				if( $can_edit_forms ) {
					if( $forms_nb ) {
						?>
						<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $current_form ) ); ?>'>
							<?php esc_html_e( 'Edit this form', 'booking-activities' ); ?>
						</a>
						<?php
					} else {
						?>
						<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>'>
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
 * @version 1.15.17
 * @param WC_Product_Variation $variation
 * @param int $i
 */
function bookacti_save_variation_option( $variation, $i ) {
	// Save 'is_activity' checkbox
	if( isset( $_POST[ 'bookacti_variable_is_activity' ][ $i ] ) ) {
		$variable_is_activity = $_POST[ 'bookacti_variable_is_activity' ][ $i ] === 'yes' ? 'yes' : 'no';
		$variation->update_meta_data( 'bookacti_variable_is_activity', $variable_is_activity );
	}
	// Save form
	if( isset( $_POST[ 'bookacti_variable_form' ][ $i ] ) ) {
		$variable_form = intval( $_POST[ 'bookacti_variable_form' ][ $i ] );
		$variation->update_meta_data( 'bookacti_variable_form', $variable_form );
	}
}
add_action( 'woocommerce_admin_process_variation_object', 'bookacti_save_variation_option', 10, 2 );


/**
 * Load custom variation settings in order to use it in frontend
 * @since 1.1.0 (was load_variation_settings_fields before)
 * @version 1.15.17
 * @param array $data
 * @param WC_Product_Variable $product
 * @param WC_Product_Variation $variation
 * @return array
 */
function bookacti_load_variation_settings_fields( $data, $product, $variation ) {
	$data[ 'bookacti_is_activity' ] = $variation->get_meta( 'bookacti_variable_is_activity' ) === 'yes';
	$data[ 'bookacti_form_id' ]     = $variation->get_meta( 'bookacti_variable_form' );
	return $data;
}
add_filter( 'woocommerce_available_variation', 'bookacti_load_variation_settings_fields', 10, 3 );




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
 * @since 1.9.0
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