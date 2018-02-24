<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ORDERS
	
	/**
	 * Change booking quantity when admin changes order item quantity
	 * 
	 * @version 1.3.0
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
					$message =  /* translators: %1$s is a variable number of bookings. This sentence is followed by two others : 'but only %1$s is available on this time slot.' and 'Please choose another event or decrease the quantity.' */
								sprintf( _n( 'You want to make %1$s booking', 'You want to make %1$s bookings', $meta_value, BOOKACTI_PLUGIN_NAME ), $meta_value )
								/* translators: %1$s is a variable number of bookings. This sentence is preceded by : 'You want to make %1$s booking' */
						. ' ' . sprintf( _n( 'but only %1$s is available on this time slot.', 'but only %1$s are available on this time slot. ', $response[ 'availability' ], BOOKACTI_PLUGIN_NAME ), $response[ 'availability' ] );
							
				} else if( $response[ 'error' ] === 'no_availability' ) {
					$message = __( 'This event is no longer available. Please choose another event.', BOOKACTI_PLUGIN_NAME );

				} else {
					$message = __( 'Error occurs while trying to update booking quantity.', BOOKACTI_PLUGIN_NAME );
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
	 * 
	 * @param int $order_id
	 * @param array $items
	 * @return void
	 */
	function bookacti_cancel_bookings_if_order_item_qty_is_null( $order_id, $items ) {
		if( isset( $items['order_item_id'] ) ) {
			
			foreach( $items['order_item_id'] as $item_id ) {
				
				// Get booking (group) id and booking type
				$booking_id		= 0;
				$booking_type	= '';
				foreach( $items[ 'meta_key' ][ $item_id ] as $meta_id => $meta_value ) {
					if( $meta_value === 'bookacti_booking_id' || $meta_value === 'bookacti_booking_group_id' ) {
						$booking_id		= intval( $items[ 'meta_value' ][ $item_id ][ $meta_id ] ) ;
						$booking_type	= $meta_value === 'bookacti_booking_group_id' ? 'group' : 'single';
						break;
					}
				}
				
				// If the product is not an activity, return
				if( empty( $booking_id ) ) {
					return;
				}
				
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
	 * 
	 * @since 1.0.0
	 * @version 1.2.0
	 * 
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
			if( empty( $booking_id ) && empty( $booking_group_id ) ) {
				continue;
			}
			
			$booking_type = empty( $booking_group_id ) ? 'single' : 'group';
				
			// Check if the deleted refund is bound to this booking (group)
			if( $booking_type === 'group' ) {
				$refunds = bookacti_get_metadata( 'booking_group', $booking_group_id, 'refunds', true );
			} else {
				$refunds = bookacti_get_metadata( 'booking', $booking_id, 'refunds', true );
			}
			
			$refund_id_index = array_search( $refund_id, $refunds );
			
			if( empty( $refunds ) || $refund_id_index === false ) {
				continue;
			}
			
			// Compute new quantity 
			// (we still need to substract $refunded_qty because it is possible to have multiple refunds, 
			// so even if you delete one, you still need to substract the quantity of the others)
			$init_qty		= $item[ 'qty' ];
			$refunded_qty	= $order->get_qty_refunded_for_item( $item_id ) ? abs( $order->get_qty_refunded_for_item( $item_id ) ) : 0;
			$new_qty		= $init_qty - $refunded_qty;
			
			// Gether the booking (group) data
			if( $booking_type === 'group' ) {
				
				$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
				$state			= $booking_group->state;
				$active			= $booking_group->active;
				$old_qty		= bookacti_get_booking_group_quantity( $booking_group_id );
				
			} else {
				
				$booking	= bookacti_get_booking_by_id( $booking_id );
				$state		= $booking->state;
				$active		= $booking->active;
				$old_qty	= $booking->quantity;
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
					$product = $order->get_product_from_item( $item );
					$order->update_product( $item_id, $product, array( 'qty' => $old_qty ) );

					// Prepare message
					if( $response['error'] === 'qty_sup_to_avail' ) {
						$message =
						sprintf( __( 'You want to add %1$s bookings to your cart but only %2$s are available on this time slot. '
								. 'Please choose another event or decrease the quantity. '
								, BOOKACTI_PLUGIN_NAME ), 
								$new_qty, $response[ 'availability' ] );

					} else if( $response['error'] === 'no_availability' ) {
						$message = __( 'This event is no longer available. Please choose another event.', BOOKACTI_PLUGIN_NAME );

					} else {
						$message = __( 'Error occurs while trying to update booking quantity.', BOOKACTI_PLUGIN_NAME );
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
					$message = __( 'Error occurs while trying to update booking quantity.', BOOKACTI_PLUGIN_NAME );
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
	 * 
	 * Must be used since WC 3.0.0
	 * 
	 * @since 1.0.4
	 */
	function bookacti_format_order_item_meta_values( $meta_value ) {
		
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
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
			else if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $meta_value ) 
			||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $meta_value ) ) {
				return bookacti_format_datetime( $meta_value );
			}
		}
		
		return $meta_value;
	}
	add_filter( 'woocommerce_order_item_display_meta_value', 'bookacti_format_order_item_meta_values', 10, 1 );
	
	
	
	
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
	 * 
	 * @param boolean $allowed
	 * @return boolean
	 */
	function bookacti_bypass_checks_for_shop_managers( $allowed ) {
		return bookacti_is_shop_manager() ? true : $allowed;
	}
	add_filter( 'bookacti_bypass_template_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 1 );
	add_filter( 'bookacti_bypass_activity_managers_check', 'bookacti_bypass_checks_for_shop_managers', 10, 1 );
	
	
	
	
// CUSTOM PRODUCTS OPTIONS

	/**
	 * Add 'Activity' custom product type option
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * 
	 * @param type $options_array
	 * @return type
	 */
	function bookacti_add_product_type_option( $options_array ) { 

		$options_array[ 'bookacti_is_activity' ] = array(
				'id'            => '_bookacti_is_activity',
				'wrapper_class' => 'show_if_simple show_if_variable',
				/* translators: 'Activity' is the new type of product in WooCommerce */
				'label'         => __( 'Activity', BOOKACTI_PLUGIN_NAME ),
				/* translators: Description of the 'Activity' type of product in WooCommerce */
				'description'   => __( 'Activities are bookable according to the defined calendar, and expire in cart.', BOOKACTI_PLUGIN_NAME ),
				'default'       => 'no'
			);
		
		return $options_array; 
	}
	add_filter( 'product_type_options', 'bookacti_add_product_type_option', 1, 1 ); 
	
	
	/**
	 * Add 'Activity' custom product tab
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * 
	 * @param array $tabs
	 * @return array
	 */
	function bookacti_create_activity_tab( $tabs ) {
		$tabs[ 'activity' ] = array(
			'label'     => __( 'Activity', BOOKACTI_PLUGIN_NAME ),
			'target'    => 'bookacti_activity_options',
			'class'     => array( 'bookacti_show_if_activity', 'hide_if_grouped', 'hide_if_external' ),
			'priority'  => 20
		);

		return $tabs;
	}
	add_filter( 'woocommerce_product_data_tabs', 'bookacti_create_activity_tab' );
	
	
	/**
	 * Content of the activity tab
	 * 
	 * @version 1.4.0
	 * 
	 * @global type $thepostid
	 */
	function bookacti_activity_tab_content() {

		global $thepostid;
		?>
		<div id='bookacti_activity_options' class='panel woocommerce_options_panel'>
			
			<div class='options_group'>
			<?php
				$booking_methods_array = array_merge( array( 'site' => __( 'Site setting', BOOKACTI_PLUGIN_NAME ) ), bookacti_get_available_booking_methods() );
		
				woocommerce_wp_select( 
					array( 
						'id'      => '_bookacti_booking_method', 
						'label'   => __( 'Booking method', BOOKACTI_PLUGIN_NAME ), 
						'options' => $booking_methods_array
					)
				);
			?>
			</div>
			<div class='options_group'>
				<?php
					$template_field_id	= '_bookacti_template'; 
					$templates = bookacti_fetch_templates();
					$current_templates	= get_post_meta( $thepostid, $template_field_id, true );
					$current_templates	= is_numeric( $current_templates ) ? array( $current_templates ) : $current_templates;
				?>
				<p class="form-field <?php echo $template_field_id; ?>_field" >
					<label for="<?php echo $template_field_id; ?>">
					<?php
						echo wp_kses_post( __( 'Calendar', BOOKACTI_PLUGIN_NAME ) );
					?>
					</label>
					<select id="<?php echo $template_field_id; ?>" 
							name="<?php echo $template_field_id; if( count( $current_templates ) > 1 ) { echo '[]'; } ?>" 
							class="select short" 
							<?php 
							if( count( $templates ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( count( $current_templates ) > 1 ) { echo 'multiple'; } 
							?> >
					<?php 
						$templates_options = '';
						foreach( $templates as $template ) {
							$template_title = apply_filters( 'bookacti_translate_text', $template[ 'title' ] );
							$templates_options .= '<option '
													.  'value="' . esc_attr( $template[ 'id' ] ) . '" '
													. selected( in_array( $template[ 'id' ], $current_templates ), true, true ) . ' >'
														. esc_html( $template_title )
												.  '</option>';
						}
						echo $templates_options;
					?>
					</select>
					<?php if( count( $templates ) > 1 ) { ?>
						<span class='bookacti-multiple-select-container' >
							<input type='checkbox' 
								   class='bookacti-multiple-select' 
								   id='bookacti-multiple-select-<?php echo $template_field_id; ?>' 
								   data-select-id='<?php echo $template_field_id; ?>' 
									<?php checked( count( $current_templates ) > 1, true, true ); ?>/>
							<label for='bookacti-multiple-select-<?php echo $template_field_id; ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
						</span>
					<?php } ?>
				</p>
			</div>
			
			
			<div class='options_group'>
				<?php 
					$activity_field_id	= '_bookacti_activity'; 
					$activities			= bookacti_fetch_activities_with_templates_association();
					$current_activities	= get_post_meta( $thepostid, $activity_field_id, true );
					$current_activities	= is_numeric( $current_activities ) ? array( $current_activities ) : $current_activities;
				?>
				<p class="form-field <?php echo $activity_field_id; ?>_field" >
					<label for="<?php echo $activity_field_id; ?>">
					<?php
						echo wp_kses_post( __( 'Activity', BOOKACTI_PLUGIN_NAME ) );
					?>
					</label>
					<select id="<?php echo $activity_field_id; ?>" 
							name="<?php echo $activity_field_id; if( count( $current_activities ) > 1 ) { echo '[]'; } ?>" 
							class="select short" 
							<?php 
							if( count( $activities ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( count( $current_activities ) > 1 ) { echo 'multiple'; } 
							?> >
					<?php 
						$activities_options = '';
						foreach( $activities as $activity ) {
							$activity_title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
							$activities_options .= '<option '
													.  'value="' . esc_attr( $activity[ 'id' ] ) . '" '
													.  'data-bookacti-show-if-templates="' . esc_attr( implode( ',', $activity[ 'template_ids' ] ) ) . '" '
													. selected( in_array( $activity[ 'id' ], $current_activities ), true, true ) . ' >'
														. esc_html( $activity_title )
												.  '</option>';
						}
						echo $activities_options;
					?>
					</select>
					<?php if( count( $activities ) > 1 ) { ?>
						<span class='bookacti-multiple-select-container' >
							<input type='checkbox' 
								   class='bookacti-multiple-select' 
								   id='bookacti-multiple-select-<?php echo $activity_field_id; ?>' 
								   data-select-id='<?php echo $activity_field_id; ?>' 
									<?php checked( count( $current_activities ) > 1, true, true ); ?>/>
							<label for='bookacti-multiple-select-<?php echo $activity_field_id; ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
						</span>
					<?php } ?>
				</p>
			</div>
			
			
			<div class='options_group'>
				<?php 
					$groups_field_id	= '_bookacti_group_categories'; 
					$categories			= bookacti_get_group_categories();
					$current_categories	= get_post_meta( $thepostid, $groups_field_id, true );
					$current_categories	= is_numeric( $current_categories ) ? array( $current_categories ) : $current_categories;
				?>
				<p class="form-field <?php echo $groups_field_id; ?>_field" >
					<label for="<?php echo $groups_field_id; ?>">
					<?php
						echo wp_kses_post( __( 'Group category', BOOKACTI_PLUGIN_NAME ) );
					?>
					</label>
					<select id="<?php echo $groups_field_id; ?>" 
							name="<?php echo $groups_field_id; if( count( $current_categories ) > 1 ) { echo '[]'; } ?>" 
							class="select short" 
							<?php 
							if( count( $categories ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( is_array( $current_categories ) && count( $current_categories ) > 1 ) { echo 'multiple'; } 
							?> >
						<option value='none' ><?php _ex( 'None', 'About group category', BOOKACTI_PLUGIN_NAME ); ?></option>
					<?php 
						$groups_options	= '';
						foreach( $categories as $category ) {
							$category_title = apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
							$selected		= is_array( $current_categories ) ? selected( in_array( $category[ 'id' ], $current_categories ), true, false ) : '';
							$groups_options .= '<option '
												.  'value="' . esc_attr( $category[ 'id' ] ) . '" '
												.  'data-bookacti-show-if-templates="' . $category[ 'template_id' ] . '" '
												. $selected . ' >'
													. esc_html( $category_title )
											.  '</option>';
						}
						echo $groups_options;
					?>
					</select>
					<?php if( count( $categories ) > 1 ) { 
						$checked = is_array( $current_categories ) ? checked( count( $current_categories ) > 1, true, false ) : '';
					?>
						<span class='bookacti-multiple-select-container' >
							<input type='checkbox' 
								   class='bookacti-multiple-select' 
								   id='bookacti-multiple-select-<?php echo $groups_field_id; ?>' 
								   data-select-id='<?php echo $groups_field_id; ?>' 
									<?php echo $checked; ?> />
							<label for='bookacti-multiple-select-<?php echo $groups_field_id; ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
						</span>
					<?php } ?>
				</p>
				<p class='form-field bookacti-groups-options' >
					<?php 
					// Groups only checkbox
					$groups_only_field_id = '_bookacti_groups_only';
					?>
					<span>
						<span>
							<input 
							type='checkbox' 
							id='<?php echo $groups_only_field_id; ?>' 
							class='checkbox' 
							name='<?php echo $groups_only_field_id; ?>' 
							value='yes'
							<?php checked( 'yes', esc_attr( get_post_meta( $thepostid, $groups_only_field_id, true ) ), true ); ?> 
							/>
						</span>
						<label for='<?php echo $groups_only_field_id; ?>' >
						<?php
							echo wp_kses_post( __( 'Display only the groups', BOOKACTI_PLUGIN_NAME ) );
						?>
						</label>
						<?php
							$tip = __( 'Display only groups of events if checked. Else, also display the other single events (if any).', BOOKACTI_PLUGIN_NAME );
							echo wc_help_tip( $tip );
						?>
					</span>

					<?php 
					// Groups events alone checkbox
					$groups_events_alone_field_id = '_bookacti_groups_single_events';
					?>
					<span>
						<span>
							<input 
								type='checkbox' 
								id='<?php echo $groups_events_alone_field_id; ?>' 
								class='checkbox' 
								name='<?php echo $groups_events_alone_field_id; ?>' 
								value='yes'
								<?php checked( 'yes', esc_attr( get_post_meta( $thepostid, $groups_events_alone_field_id, true ) ), true ); ?> 
							/>
						</span>
						<label for='<?php echo $groups_events_alone_field_id; ?>' >
						<?php
							echo wp_kses_post( __( "Allow to book grouped events also as single events", BOOKACTI_PLUGIN_NAME ) );
						?>
						</label>
						<?php
							$tip = __( 'When a customer pick an event, let him choose between the group or the single event.', BOOKACTI_PLUGIN_NAME );
							echo wc_help_tip( $tip );
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
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @param int $post_id
	 */
	function bookacti_save_custom_product_type_and_tab_content( $post_id ) { 
		
		if( ! empty( $_POST['_bookacti_is_activity'] ) ) {
			update_post_meta( $post_id, '_bookacti_is_activity', sanitize_text_field( 'yes' ) );
			
			//Force the product to be flagged as virtual if it is an activity
			if( empty( $_POST['_virtual'] ) ) {
				update_post_meta( $post_id, '_virtual', wc_clean( 'yes' ) );
			}
		} else {
			update_post_meta( $post_id, '_bookacti_is_activity', sanitize_text_field( 'no' ) );
		}
		
		if ( isset( $_POST['_bookacti_booking_method'] ) ) {
			update_post_meta( $post_id, '_bookacti_booking_method', sanitize_text_field( $_POST['_bookacti_booking_method'] ) );
		}
		if ( isset( $_POST['_bookacti_template'] ) ) {
			update_post_meta( $post_id, '_bookacti_template', bookacti_ids_to_array( $_POST['_bookacti_template'] ) );
		}
		if ( isset( $_POST['_bookacti_activity'] ) ) {
			update_post_meta( $post_id, '_bookacti_activity', bookacti_ids_to_array( $_POST['_bookacti_activity'] ) );
		}
		if ( isset( $_POST['_bookacti_group_categories'] ) ) {
			
			$group_categories = 'none';
			
			if( is_array( $_POST['_bookacti_group_categories'] ) && in_array( 'none', $_POST['_bookacti_group_categories'] ) ) {
				$_POST['_bookacti_group_categories'] = 'none';
				$group_categories = 'none';
			}
			
			if( is_numeric( $_POST['_bookacti_group_categories'] ) || is_array( $_POST['_bookacti_group_categories'] ) ) {
				$group_categories = bookacti_ids_to_array( $_POST['_bookacti_group_categories'] );
			}
			
			update_post_meta( $post_id, '_bookacti_group_categories', $group_categories );
		}
		if( ! empty( $_POST['_bookacti_groups_only'] ) ) {
			update_post_meta( $post_id, '_bookacti_groups_only', sanitize_text_field( 'yes' ) );
		} else {
			update_post_meta( $post_id, '_bookacti_groups_only', sanitize_text_field( 'no' ) );
		}
		if( ! empty( $_POST['_bookacti_groups_single_events'] ) ) {
			update_post_meta( $post_id, '_bookacti_groups_single_events', sanitize_text_field( 'yes' ) );
		} else {
			update_post_meta( $post_id, '_bookacti_groups_single_events', sanitize_text_field( 'no' ) );
		}
	}
	add_action( 'woocommerce_process_product_meta', 'bookacti_save_custom_product_type_and_tab_content', 30, 1 ); 




//CUSTOM VARIATION FIELDS

	/**
	 * Add custom variation product type option
	 * 
	 * @since 1.0.0
	 * 
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
				id='bookacti_variable_is_activity[<?php echo esc_attr( $loop ); ?>]' 
				class='checkbox bookacti_variable_is_activity' 
				name='bookacti_variable_is_activity[<?php echo esc_attr( $loop ); ?>]' 
				value='yes'
				<?php checked( 'yes', esc_attr( get_post_meta( $variation->ID, 'bookacti_variable_is_activity', true ) ), true ); ?> 
			/> 
			<?php esc_html_e( 'Activity', BOOKACTI_PLUGIN_NAME ); ?> 
			<?php 
				/* translators: Help tip to explain why and when you should check the 'Activity' type of product in WooCommerce */
				echo wc_help_tip( esc_html__( 'Enable this option if the product is a bookable activity', BOOKACTI_PLUGIN_NAME ) ); 
			?>
		</label>
	<?php
	}
	add_action( 'woocommerce_variation_options', 'bookacti_add_variation_option', 10, 3 ); 

	
	/**
	 * Add custom fields for activity variation product type
	 * 
	 * @since 1.0.0
	 * @version 1.4.0
	 * 
	 * @param int $loop
	 * @param array $variation_data
	 * @param WP_Post $variation
	 */
	function bookacti_add_variation_fields( $loop, $variation_data, $variation ) { 

		//Retrieve templates and assiociated data
		$templates = bookacti_fetch_templates();
		$activities = bookacti_fetch_activities_with_templates_association();
		$categories	= bookacti_get_group_categories();

		//Check if variation is flagged as activity
		$is_variation_activity = get_post_meta( $variation->ID, 'bookacti_variable_is_activity', true );
		$is_active = 'bookacti-hide-fields';
		if( $is_variation_activity === 'yes' ) { $is_active = 'bookacti-show-fields'; }

		$current_templates				= get_post_meta( $variation->ID, 'bookacti_variable_template', true );
		$current_templates				= is_numeric( $current_templates ) ? array( $current_templates ) : $current_templates;
		
		$current_activities				= get_post_meta( $variation->ID, 'bookacti_variable_activity', true );
		$current_activities				= is_numeric( $current_activities ) ? array( $current_activities ) : $current_activities;
		
		$current_group_categories		= get_post_meta( $variation->ID, 'bookacti_variable_group_categories', true );
		$current_group_categories		= is_numeric( $current_group_categories ) ? array( $current_group_categories ) : $current_group_categories;
		
		$parent_template_id				= get_post_meta( $variation->post_parent, '_bookacti_template', true );
		$parent_template_id				= is_numeric( $parent_template_id ) ? array( $parent_template_id ) : $parent_template_id;
		
		$parent_activity_id				= get_post_meta( $variation->post_parent, '_bookacti_activity', true );
		$parent_activity_id				= is_numeric( $parent_activity_id ) ? array( $parent_activity_id ) : $parent_activity_id;
		
		$parent_group_categories		= get_post_meta( $variation->post_parent, '_bookacti_group_categories', true );
		$parent_group_categories		= is_numeric( $parent_group_categories ) ? array( $parent_group_categories ) : $parent_group_categories;
		
		$current_groups_only			= get_post_meta( $variation->ID, 'bookacti_variable_groups_only', true );
		$current_groups_single_events	= get_post_meta( $variation->ID, 'bookacti_variable_groups_single_events', true );
		$current_booking_method			= get_post_meta( $variation->ID, 'bookacti_variable_booking_method', true );
		
		$is_default_template			= empty( $templates )	|| $current_templates === 'parent'			|| is_null( $current_templates );
		$is_default_activity			= empty( $activities )	|| $current_activities === 'parent'			|| is_null( $current_activities );
		$is_default_group_categories	= empty( $categories )	|| $current_group_categories === 'parent'	|| is_null( $current_group_categories );
		$is_default_booking_method		= empty( $templates )	|| $current_booking_method === 'parent'		|| is_null( $current_booking_method );
	?>
		
		<div class='show_if_variation_activity <?php echo $is_active; ?>'>
			
			<p class='form-row form-row-full bookacti-woo-title'>
				<strong><?php _e( 'Activity', BOOKACTI_PLUGIN_NAME ) ?></strong>
			</p>
			
			
			<p class='form-row form-row-full'>
				<label for='bookacti_variable_booking_method_<?php echo esc_attr( $loop ); ?>' ><?php esc_html_e( 'Booking method', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select name='bookacti_variable_booking_method[<?php echo esc_attr( $loop ); ?>]' id='bookacti_variable_booking_method_<?php echo esc_attr( $loop ); ?>' class='bookacti_variable_booking_method' data-loop='<?php echo esc_attr( $loop ); ?>' >
					<option value='parent' <?php selected( true, $is_default_booking_method, true ); ?> >
						<?php esc_html_e( 'Parent setting', BOOKACTI_PLUGIN_NAME ); ?>
					</option>
					<option value='site' <?php selected( 'site', esc_attr( $current_booking_method ), true ); ?> >
						<?php 
							/* translators: This is an option in a select box that means 'Use the setting of the whole website' */
							esc_html_e( 'Site setting', BOOKACTI_PLUGIN_NAME ); 
						?>
					</option>
					<?php
					$available_booking_methods = bookacti_get_available_booking_methods();
					foreach( $available_booking_methods as $booking_method_id => $booking_method_label ) {
						echo '<option value="' . $booking_method_id . '" ' . selected( $booking_method_id, $current_booking_method, true ) . ' >'
								. esc_html( $booking_method_label )
							. '</option>';
					}
					?>
				</select>
			</p>
			
			
			<p class='form-row form-row-full'>
				<label for='bookacti_variable_template_<?php echo esc_attr( $loop ); ?>' ><?php esc_html_e( 'Calendar', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select name='bookacti_variable_template[<?php echo esc_attr( $loop ); ?>]<?php if( count( $current_templates ) > 1 ) { echo '[]'; } ?>' 
						id='bookacti_variable_template_<?php echo esc_attr( $loop ); ?>' 
						class='bookacti_variable_template' 
						data-loop='<?php echo esc_attr( $loop ); ?>' 
						data-parent='<?php echo is_array( $parent_template_id ) ? esc_attr( implode( ',', $parent_template_id ) ) : esc_attr( $parent_template_id ); ?>' 
						<?php 
							if( count( $templates ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( is_array( $current_templates ) && count( $current_templates ) > 1 ) { echo 'multiple'; } 
						?> >
					<?php 
					if( bookacti_user_can_manage_template( $parent_template_id ) ) { ?>
						<option value='parent' <?php selected( true, $is_default_template, true ); ?> >
							<?php 
								/* translators: This is an option in a select box that means 'Use the setting of the parent' */
								esc_html_e( 'Parent setting', BOOKACTI_PLUGIN_NAME ); 
							?>
						</option>
					<?php
					} else if( $is_default_template ) {
						// Return first template id of $templates
						$current_templates = array( current( array_keys( $templates ) ) );
					}
					
					foreach( $templates as $template ) {
						$selected = is_array( $current_templates ) ? selected( in_array( $template[ 'id' ], $current_templates ), true, false ) : '';
					
						echo  '<option '
								. ' value="' . esc_attr( $template[ 'id' ] ) . '" '
								. $selected
							. '>'
								. esc_html( $template[ 'title' ] )
							. '</option>';
					}
					?>
				</select>
				<?php if( count( $templates ) > 1 ) { 
					$checked = is_array( $current_templates ) ? checked( count( $current_templates ) > 1, true, false ) : '';
				?>
					<span class='bookacti-multiple-select-container' >
						<input type='checkbox' 
							   class='bookacti-multiple-select' 
							   id='bookacti-multiple-select-bookacti_variable_template_<?php echo esc_attr( $loop ); ?>' 
							   data-select-id='bookacti_variable_template_<?php echo esc_attr( $loop ); ?>' 
								<?php echo $checked; ?> />
						<label for='bookacti-multiple-select-bookacti_variable_template_<?php echo esc_attr( $loop ); ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
					</span>
				<?php } ?>
			</p>
			
			
			<p class='form-row form-row-full'>
				<label for='bookacti_variable_activity_<?php echo esc_attr( $loop ); ?>' ><?php esc_html_e( 'Activity', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select name='bookacti_variable_activity[<?php echo esc_attr( $loop ); ?>]<?php if( count( $current_activities ) > 1 ) { echo '[]'; } ?>' 
						id='bookacti_variable_activity_<?php echo esc_attr( $loop ); ?>' 
						class='bookacti_variable_activity' 
						data-loop='<?php echo esc_attr( $loop ); ?>' 
						data-parent='<?php echo is_array( $parent_activity_id ) ? esc_attr( implode( ',', $parent_activity_id ) ) : esc_attr( $parent_activity_id ); ?>' 
						<?php 
							if( count( $activities ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( is_array( $current_activities ) && count( $current_activities ) > 1 ) { echo 'multiple'; } 
						?> >
					<option value='parent' <?php selected( true, $is_default_activity, true ); ?> >
						<?php 
							/* translators: This is an option in a select box that means 'Use the setting of the parent' */
							esc_html_e( 'Parent setting', BOOKACTI_PLUGIN_NAME ); 
						?>
					</option>
					<?php
					$options = '';
					foreach( $activities as $activity ) {
						$activity_title = apply_filters( 'bookacti_translate_text', $activity[ 'title' ] );
						$selected		= is_array( $current_activities ) ? selected( in_array( $activity[ 'id' ], $current_activities ), true, false ) : '';
						$options .= '<option '
									. 'value="' . esc_attr( $activity[ 'id' ] ) . '" '
									. 'data-bookacti-show-if-templates="' . esc_attr( implode( ',', $activity[ 'template_ids' ] ) ) . '" '
									. $selected . ' >'
										. esc_html( $activity_title )
								.  '</option>';
					}
					echo $options;
					?>
				</select>
				<?php if( count( $activities ) > 1 ) { 
					$checked = is_array( $current_activities ) ? checked( count( $current_activities ) > 1, true, false ) : '';
				?>
					<span class='bookacti-multiple-select-container' >
						<input type='checkbox' 
							   class='bookacti-multiple-select' 
							   id='bookacti-multiple-select-bookacti_variable_activity_<?php echo esc_attr( $loop ); ?>' 
							   data-select-id='bookacti_variable_activity_<?php echo esc_attr( $loop ); ?>' 
								<?php echo $checked; ?> />
						<label for='bookacti-multiple-select-bookacti_variable_activity_<?php echo esc_attr( $loop ); ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
					</span>
				<?php } ?>
			</p>
			
			
			<p class='form-row form-row-full'>
				<label for='bookacti_variable_group_categories_<?php echo esc_attr( $loop ); ?>' ><?php esc_html_e( 'Group category', BOOKACTI_PLUGIN_NAME ); ?></label>
				<select name='bookacti_variable_group_categories[<?php echo esc_attr( $loop ); ?>]<?php if( count( $current_group_categories ) > 1 ) { echo '[]'; } ?>' 
						id='bookacti_variable_group_categories_<?php echo esc_attr( $loop ); ?>' 
						class='bookacti_variable_group_categories' 
						data-loop='<?php echo esc_attr( $loop ); ?>' 
						data-parent='<?php if( is_array( $parent_group_categories ) ) { echo esc_attr( implode( ',', $parent_group_categories ) ); } else if( $parent_group_categories ) { echo esc_attr( $parent_group_categories ); } else { echo 'none'; } ?>'
						<?php 
							if( count( $categories ) > 1 ) { echo 'style="margin-right:10px;"'; } 
							if( is_array( $current_group_categories ) && count( $current_group_categories ) > 1 ) { echo 'multiple'; } 
						?> >
					<option value='parent' <?php selected( true, $is_default_group_categories, true ); ?> >
						<?php 
							/* translators: This is an option in a select box that means 'Use the setting of the parent' */
							esc_html_e( 'Parent setting', BOOKACTI_PLUGIN_NAME ); 
						?>
					</option>
					<option value='none' <?php selected( true, $current_group_categories === 'none', true ) ?> ><?php _ex( 'None', 'About group category', BOOKACTI_PLUGIN_NAME ); ?></option>
					<?php
					$groups_options	= '';
					foreach( $categories as $category ) {
						$category_title = apply_filters( 'bookacti_translate_text', $category[ 'title' ] );
						$selected		= is_array( $current_group_categories ) ? selected( in_array( $category[ 'id' ], $current_group_categories ), true, false ) : '';
						$groups_options .= '<option '
											. 'value="' . esc_attr( $category[ 'id' ] ) . '" '
											. 'data-bookacti-show-if-templates="' . $category[ 'template_id' ] . '" '
											. $selected . ' >'
												. esc_html( $category_title )
										.  '</option>';
					}
					echo $groups_options;
					?>
				</select>
				<?php if( count( $categories ) > 1 ) { 
					$checked = is_array( $current_group_categories ) ? checked( count( $current_group_categories ) > 1, true, false ) : '';
				?>
					<span class='bookacti-multiple-select-container' >
						<input type='checkbox' 
							   class='bookacti-multiple-select' 
							   id='bookacti-multiple-select-bookacti_variable_group_categories_<?php echo esc_attr( $loop ); ?>' 
							   data-select-id='bookacti_variable_group_categories_<?php echo esc_attr( $loop ); ?>' 
								<?php echo $checked; ?> />
						<label for='bookacti-multiple-select-bookacti_variable_group_categories_<?php echo esc_attr( $loop ); ?>' ><?php _e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?></label>
					</span>
				<?php } ?>
			</p>
			
			
			<p class='form-row form-row-full bookacti-groups-options' >
				<?php 
				// Groups only checkbox
				?>
				<span>
					<span>
						<input 
							type='checkbox' 
							id='<?php echo 'bookacti_variable_groups_only_' . esc_attr( $loop ); ?>' 
							class='checkbox' 
							name='<?php echo 'bookacti_variable_groups_only[' . esc_attr( $loop ) . ']'; ?>' 
							value='yes'
							<?php checked( 'yes', $current_groups_only, true ); ?> 
						/>
					</span>
					<label for='<?php echo 'bookacti_variable_groups_only_' . esc_attr( $loop ); ?>' >
					<?php
						echo wp_kses_post( __( 'Display only the groups', BOOKACTI_PLUGIN_NAME ) );
					?>
					</label>
					<?php
						$tip = __( 'Display only groups of events if checked. Else, also display the other single events (if any).', BOOKACTI_PLUGIN_NAME );
						echo wc_help_tip( $tip );
					?>
				</span>

				<?php 
				// Groups events alone checkbox
				?>
				<span>
					<span>
						<input 
							type='checkbox' 
							id='<?php echo 'bookacti_variable_groups_single_events_' . esc_attr( $loop ); ?>' 
							class='checkbox' 
							name='<?php echo 'bookacti_variable_groups_single_events[' . esc_attr( $loop ) . ']'; ?>' 
							value='yes'
							<?php checked( 'yes', $current_groups_single_events, true ); ?> 
						/>
					</span>
					<label for='<?php echo 'bookacti_variable_groups_single_events_' . esc_attr( $loop ); ?>' >
					<?php
						echo wp_kses_post( __( "Allow to book grouped events also as single events", BOOKACTI_PLUGIN_NAME ) );
					?>
					</label>
					<?php
						$tip = __( 'When a customer pick an event, let him choose between the group or the single event.', BOOKACTI_PLUGIN_NAME );
						echo wc_help_tip( $tip );
					?>
				</span>
			</p>
		</div>
	<?php
	}
	add_action( 'woocommerce_product_after_variable_attributes', 'bookacti_add_variation_fields', 10, 3 ); 


	/**
	 * Save custom variation product
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @param int $post_id
	 */
	function bookacti_save_variation_option( $post_id ) {

		$variable_post_id	= is_array( $_POST[ 'variable_post_id' ] ) ? $_POST[ 'variable_post_id' ] : array();
		$keys				= array_keys( $variable_post_id );
		
		//Save data for each variation
		foreach ( $keys as $key ) {
			$variation_id = intval( $variable_post_id[ $key ] );
			if( $variation_id ) {
				// Save 'is_activity' checkbox
				if ( isset( $_POST[ 'bookacti_variable_is_activity' ][ $key ] ) ) {
					$variable_is_activity = $_POST[ 'bookacti_variable_is_activity' ][ $key ] === 'yes' ? 'yes' : 'no';
					update_post_meta( $variation_id, 'bookacti_variable_is_activity', $variable_is_activity );

					//Force the variation to be flagged as virtual if it is an activity
					if( $variable_is_activity === 'yes' ) {
						update_post_meta( $variation_id, '_virtual', 'yes' );
					}
				}
				
				// Save booking method
				if ( isset( $_POST[ 'bookacti_variable_booking_method' ][ $key ] ) ) {
					// Build array of available booking methods
					$avail_booking_methods = array_keys( bookacti_get_available_booking_methods() );
					$avail_booking_methods[] = 'parent';
					$avail_booking_methods[] = 'site';

					// Check selected booking methods against available ones
					$sanitized_booking_method	= sanitize_title_with_dashes( $_POST[ 'bookacti_variable_booking_method' ][ $key ] );
					$variable_booking_method	= in_array( $sanitized_booking_method, $avail_booking_methods, true ) ? $sanitized_booking_method : 'parent';
					update_post_meta( $variation_id, 'bookacti_variable_booking_method', $variable_booking_method );
				}
				
				// Save template
				if ( isset( $_POST[ 'bookacti_variable_template' ][ $key ] ) ) {
					
					if( is_array( $_POST['bookacti_variable_template'][ $key ] ) 
					&&  in_array( 'parent', $_POST['bookacti_variable_template'][ $key ] ) ) { 
						$_POST[ 'bookacti_variable_template' ][ $key ] = 'parent';
					}
					
					$variable_template = is_numeric( $_POST[ 'bookacti_variable_template' ][ $key ] ) || is_array( $_POST[ 'bookacti_variable_template' ][ $key ] ) ? bookacti_ids_to_array( $_POST[ 'bookacti_variable_template' ][ $key ] ) : 'parent';
					update_post_meta( $variation_id, 'bookacti_variable_template', $variable_template );
				}

				// Save activity
				if ( isset( $_POST[ 'bookacti_variable_activity' ][ $key ] ) ) {
					
					if( is_array( $_POST['bookacti_variable_activity'][ $key ] ) 
					&&  in_array( 'parent', $_POST['bookacti_variable_activity'][ $key ] ) ) { 
						$_POST[ 'bookacti_variable_activity' ][ $key ] = 'parent';
					}
					
					$variable_activity = is_numeric( $_POST[ 'bookacti_variable_activity' ][ $key ] ) || is_array( $_POST[ 'bookacti_variable_activity' ][ $key ] ) ? bookacti_ids_to_array( $_POST[ 'bookacti_variable_activity' ][ $key ] ) : 'parent';
					update_post_meta( $variation_id, 'bookacti_variable_activity', $variable_activity );
				}
				
				// Save group category
				if ( isset( $_POST[ 'bookacti_variable_group_categories' ][ $key ] ) ) {
					$variable_group_categories = 'parent';
					
					if( is_array( $_POST['bookacti_variable_group_categories'][ $key ] ) ) { 
						if( in_array( 'none', $_POST['bookacti_variable_group_categories'][ $key ] ) ) {
							$_POST['bookacti_variable_group_categories'][ $key ] = 'none';
							$variable_group_categories = 'none';
						} else if( in_array( 'parent', $_POST['bookacti_variable_group_categories'][ $key ] ) ) {
							$_POST['bookacti_variable_group_categories'][ $key ] = 'parent';
							$variable_group_categories = 'parent';
						}
					}
					
					if( is_numeric( $_POST[ 'bookacti_variable_group_categories' ][ $key ] ) 
					||  is_array( $_POST[ 'bookacti_variable_group_categories' ][ $key ] ) ) {
						$variable_group_categories = bookacti_ids_to_array( $_POST[ 'bookacti_variable_group_categories' ][ $key ] );
					} else if( $_POST[ 'bookacti_variable_group_categories' ][ $key ] === 'none' ) {
						$variable_group_categories = 'none';
					}
					
					$updated = update_post_meta( $variation_id, 'bookacti_variable_group_categories', $variable_group_categories );
				}
				
				// Save 'groups_only' checkbox
				$variable_groups_only = isset( $_POST[ 'bookacti_variable_groups_only' ][ $key ] ) && $_POST[ 'bookacti_variable_groups_only' ][ $key ] === 'yes' ? 'yes' : 'no';
				update_post_meta( $variation_id, 'bookacti_variable_groups_only', $variable_groups_only );
				
				// Save 'groups_single_events' checkbox
				$variable_groups_single_events = isset( $_POST[ 'bookacti_variable_groups_single_events' ][ $key ] ) && $_POST[ 'bookacti_variable_groups_single_events' ][ $key ] === 'yes' ? 'yes' : 'no';
				update_post_meta( $variation_id, 'bookacti_variable_groups_single_events',$variable_groups_single_events );
				
			}
		}
	}
	add_action( 'woocommerce_save_product_variation', 'bookacti_save_variation_option', 10, 1 );
	
	
	/**
	 * Load custom variation settings in order to use it in frontend
	 * 
	 * @since 1.1.0 (called load_variation_settings_fields before)
	 * 
	 * @param array $variations
	 * @return array
	 */
	function bookacti_load_variation_settings_fields( $variations ) {

		$variations['bookacti_is_activity']				= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_is_activity', true ) === 'yes';
		$variations['bookacti_booking_method']			= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_booking_method', true );
		$variations['bookacti_template_id']				= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_template', true );
		$variations['bookacti_activity_id']				= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_activity', true );
		$variations['bookacti_group_categories']		= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_group_categories', true );
		$variations['bookacti_groups_only']				= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_groups_only', true );
		$variations['bookacti_groups_single_events']	= get_post_meta( $variations[ 'variation_id' ], 'bookacti_variable_groups_single_events', true );
		
		return $variations;
	}
	add_filter( 'woocommerce_available_variation', 'bookacti_load_variation_settings_fields' );

	
	

// ROLES AND CAPABILITIES

	/**
	 * Set Booking Activities roles and capabilities related to WooCommerce
	 * 
	 * @since 1.0.0
	 */
	function bookacti_set_role_and_cap_for_woocommerce() {
		$shop_manager = get_role( 'shop_manager' );
		$shop_manager->add_cap( 'bookacti_manage_booking_activities' );
		$shop_manager->add_cap( 'bookacti_manage_bookings' );
		$shop_manager->add_cap( 'bookacti_manage_templates' );
		$shop_manager->add_cap( 'bookacti_manage_booking_activities_settings' );
		$shop_manager->add_cap( 'bookacti_read_templates' );
		$shop_manager->add_cap( 'bookacti_create_templates' );
		$shop_manager->add_cap( 'bookacti_edit_templates' );
		$shop_manager->add_cap( 'bookacti_delete_templates' );
		$shop_manager->add_cap( 'bookacti_create_activities' );
		$shop_manager->add_cap( 'bookacti_edit_activities' );
		$shop_manager->add_cap( 'bookacti_delete_activities' );
		$shop_manager->add_cap( 'bookacti_create_bookings' );
		$shop_manager->add_cap( 'bookacti_edit_bookings' );
	}
	add_action( 'bookacti_set_capabilities', 'bookacti_set_role_and_cap_for_woocommerce' );
	add_action( 'woocommerce_installed', 'bookacti_set_role_and_cap_for_woocommerce' );
	
	
	/**
	 * Unset Booking Activities roles and capabilities related to WooCommerce (to be used on wp_roles_init)
	 * 
	 * @since 1.0.0
	 */
	function bookacti_unset_role_and_cap_for_woocommerce_on_woocommerce_uninstall() {
		if( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN === 'woocommerce/woocommerce.php' ) {
			bookacti_unset_role_and_cap_for_woocommerce();
		}
	}
	add_action( 'wp_roles_init', 'bookacti_unset_role_and_cap_for_woocommerce_on_woocommerce_uninstall' );
	
	
	/**
	 * Unset Booking Activities roles and capabilities related to WooCommerce
	 * 
	 * @since 1.0.0
	 */
	function bookacti_unset_role_and_cap_for_woocommerce() {
		$shop_manager = get_role( 'shop_manager' );
		$shop_manager->remove_cap( 'bookacti_manage_booking_activities' );
		$shop_manager->remove_cap( 'bookacti_manage_bookings' );
		$shop_manager->remove_cap( 'bookacti_manage_templates' );
		$shop_manager->remove_cap( 'bookacti_manage_booking_activities_settings' );
		$shop_manager->remove_cap( 'bookacti_read_templates' );
		$shop_manager->remove_cap( 'bookacti_create_templates' );
		$shop_manager->remove_cap( 'bookacti_edit_templates' );
		$shop_manager->remove_cap( 'bookacti_delete_templates' );
		$shop_manager->remove_cap( 'bookacti_create_activities' );
		$shop_manager->remove_cap( 'bookacti_edit_activities' );
		$shop_manager->remove_cap( 'bookacti_delete_activities' );
		$shop_manager->remove_cap( 'bookacti_create_bookings' );
		$shop_manager->remove_cap( 'bookacti_edit_bookings' );
	}
	add_action( 'bookacti_unset_capabilities', 'bookacti_unset_role_and_cap_for_woocommerce' );