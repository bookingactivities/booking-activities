<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL
/**
 * Define a global for internal use
 * @since 1.7.10
 */
if( ! isset( $GLOBALS[ 'global_bookacti_wc' ] ) ) { $GLOBALS[ 'global_bookacti_wc' ] = array(); }


/**
 * Add woocommerce related translations
 * @version 1.8.0
 * @param array $translation_array
 * @return array
 */
function bookacti_woocommerce_translation_array( $translation_array ) {
	$translation_array[ 'expired' ]							= esc_html__( 'expired', 'booking-activities' );
	$translation_array[ 'days' ]							= esc_html__( 'days', 'booking-activities' );
	$translation_array[ 'day' ]								= esc_html_x( 'day', 'singular of days','booking-activities' );
	$translation_array[ 'error_cart_expired' ]				= esc_html__( 'Your cart has expired.', 'booking-activities' );
	$translation_array[ 'add_product_to_cart_button_text' ]	= esc_html__( 'Add to cart', 'woocommerce' );
	$translation_array[ 'add_booking_to_cart_button_text' ]	= bookacti_get_message( 'booking_form_submit_button' );

	if( is_admin() ) {
		$translation_array[ 'empty_product_price' ] = esc_html__( 'You must set a price for your product, otherwise the booking form won’t appear on the product page.', 'booking-activities' );
	}

	return $translation_array;
}
add_filter( 'bookacti_translation_array', 'bookacti_woocommerce_translation_array', 10, 1 );


/**
 * Change 'user_id' of bookings from customer id to user id when he logs in
 * @version 1.9.0
 * @global WooCommerce $woocommerce
 * @param string $user_login
 * @param WP_User $user
 */
function bookacti_change_customer_id_to_user_id( $user_login, $user ) {
	global $woocommerce;

	// Replace bookings customer ID with user ID
	if( ! empty( $woocommerce->session ) && is_object( $woocommerce->session ) && method_exists( $woocommerce->session, 'get_customer_id' ) ) {
		$customer_id = $woocommerce->session->get_customer_id();
		
		// If the customer was already logged in, do nothing (user switching between two accounts)
		if( is_numeric( $customer_id ) && get_user_by( 'id', $customer_id ) ) { return; } 
		
		bookacti_update_bookings_user_id( $user->ID, $customer_id );
	}

	// Update the cart expiration date if the user is logged in
	$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	if( ! $is_per_product_expiration ) {
		$cart_expiration_date = bookacti_get_cart_expiration_date_per_user( $user->ID );
		update_user_meta( $user->ID, 'bookacti_expiration_cart', $cart_expiration_date );
	}
	
	// Check if user's cart is still valid or change it if necessary
	global $woocommerce;
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings();
	if( $cart_items_bookings ) {
		foreach( $cart_items_bookings as $cart_item_key => $cart_item_bookings ) {
			$item = $woocommerce->cart->get_cart_item( $cart_item_key );
			$old_quantity = $item[ 'quantity' ];
			$quantity = $old_quantity;
			
			// User check
			$response = bookacti_wc_validate_cart_item_bookings_new_user( $cart_item_bookings, $user->ID );
			
			// Quantity check
			if( $response[ 'status' ] === 'success' ) {
				// Check if the cart item bookings quantity can be "changed" to its own quantity
				// is the same as checking if an inactive cart item can be turned to active
				$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $quantity );

				// If the quantity > availability, change the $new_quantity to the available quantity
				if( $response[ 'status' ] === 'failed' && ! empty( $response[ 'messages' ][ 'qty_sup_to_avail' ] ) && ! empty( $response[ 'availability' ] ) ) {
					$quantity += intval( $response[ 'availability' ] );
					$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $quantity );
				}
			}

			// Display the error and remove cart item
			if( $response[ 'status' ] !== 'success' ) {
				$removed_message = esc_html__( 'The item has been automatically removed from your cart.', 'booking-activities' );
				foreach( $response[ 'messages' ] as $message ) {
					wc_add_notice( $message . ' ' . $removed_message, 'error' );
				}
				$woocommerce->cart->remove_cart_item( $cart_item_key );
			} 
			
			// If the validation passed with a different quantity, change the cart item quantity
			else if( $quantity !== $old_quantity ) {
				$woocommerce->cart->set_quantity( $cart_item_key, $quantity );
			}
		}
	}
}
add_action( 'wp_login', 'bookacti_change_customer_id_to_user_id', 20, 2 );




// LOOP PRODUCTS PAGE

/**
 * Add 'activity' class to activity product in the products loop
 * @param string $classes
 * @param string $class
 * @param int $post_id
 * @return string
 */
function bookacti_add_activity_post_class( $classes, $class, $post_id ) {
	$is_activity = bookacti_product_is_activity( $post_id );
	if( $is_activity ) { $classes[] = 'bookacti-activity'; }
	return $classes;
}
add_filter( 'post_class', 'bookacti_add_activity_post_class', 10, 3 );


/**
 * Disable AJAX add to cart support for activities
 * @param boolean $enabled
 * @param string $feature
 * @param WC_Product $product
 * @return boolean
 */
function bookacti_disable_ajax_add_to_cart_support_for_activities( $enabled, $feature, $product ){
	if( $feature === 'ajax_add_to_cart' && $enabled ){
		if( bookacti_product_is_activity( $product ) ){
			$enabled = false;
		}
	}
	return $enabled;
}
add_filter( 'woocommerce_product_supports', 'bookacti_disable_ajax_add_to_cart_support_for_activities', 100, 3 );


/**
 * Change 'Add to cart' button URL to the single product page URL for activities
 * @param string $url
 * @param WC_Product $product
 * @return string
 */
function bookacti_change_add_to_cart_url_for_activities( $url, $product ){
	if( bookacti_product_is_activity( $product ) ){
		$url = get_permalink( $product->get_id() );
	}
	return $url;
}
add_filter( 'woocommerce_product_add_to_cart_url', 'bookacti_change_add_to_cart_url_for_activities', 100, 2 );


/**
 * Change 'Add to cart' text for activities by user defined string
 * @version 1.2.0
 * @param string $text
 * @param WC_Product $product
 * @return string
 */
function bookacti_change_add_to_cart_text_for_activities( $text, $product ){
	if( bookacti_product_is_activity( $product ) ){
		$text = bookacti_get_message( 'booking_form_submit_button' );
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'bookacti_change_add_to_cart_text_for_activities', 100, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'bookacti_change_add_to_cart_text_for_activities', 100, 2 );




// SINGLE PRODUCT PAGE

/**
 * Move the add-to-cart form below the product summary
 * @since 1.7.16
 * @version 1.8.2
 */
function bookacti_move_add_to_cart_form_below_product_summary() {
	global $product;
	if( ! $product ) { return; }
	if( ! bookacti_product_is_activity( $product ) ) { return; }
	
	if( bookacti_get_setting_value( 'bookacti_products_settings', 'wc_product_pages_booking_form_location' ) !== 'form_below' ) { return; }
	
	$priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
	if( $priority === false ) { return; }
	if( has_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_add_to_cart' ) ) { return; }
	
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', $priority );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_add_to_cart', 5 );
}
add_action( 'woocommerce_before_single_product_summary', 'bookacti_move_add_to_cart_form_below_product_summary', 10 );


/**
 * Add booking forms to single product page (front-end)
 * @version 1.8.0
 * @global WC_Product $product
 */
function bookacti_add_booking_system_in_single_product_page() {
	global $product;
	if( ! $product ) { return; }

	$is_activity = bookacti_product_is_activity( $product );
	if( ! $is_activity ) { return; }

	// Check if the product or one of its available variation is bound to a booking form
	$form_id = bookacti_get_product_form_id( $product->get_id() );
	if( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();
		foreach( $variations as $variation ) {
			if( empty( $variation[ 'bookacti_is_activity' ] ) || empty( $variation[ 'bookacti_form_id' ] ) ) { continue; }
			$form_id = apply_filters( 'bookacti_product_booking_form_id', $variation[ 'bookacti_form_id' ], $variation[ 'variation_id' ], true );
			break;
		}
	}
	if( ! $form_id ) { return; }

	$form_instance_id		= '';
	$variation_id			= 0;
	$default_variation_id	= 0;

	// Show form on single product page or on variable product with a default value
	if( $product->is_type( 'simple' ) ) {
		$form_instance_id = 'product-' . $product->get_id();
	}
	else if( $product->is_type( 'variable' ) ) {
		$default_attributes = bookacti_get_product_default_attributes( $product );
		if( $default_attributes ) { 
			$variation_id = bookacti_get_product_variation_matching_attributes( $product, $default_attributes );
			$default_variation_id = $variation_id;
			if( $default_variation_id ) { 
				$form_id = get_post_meta( $default_variation_id, 'bookacti_variable_form', true );
				if( $form_id ) { 
					$form_instance_id = 'product-variation-' . $default_variation_id;
				}	
			}
		}

	} else if( $product->is_type( 'variation' ) ) {
		$variation_id = $product->get_id();
		$form_id = get_post_meta( $variation_id, 'bookacti_variable_form', true );
		if( $form_id ) { 
			$form_instance_id = 'product-variation-' . $variation_id;
		}
	}

	$form_atts = apply_filters( 'bookacti_product_form_attributes', array(
		'id' => $form_instance_id,
		'class' => '',
		'data-default-variation-id' => ! empty( $default_variation_id ) ? $default_variation_id : '',
		'data-variation-id' => ! empty( $variation_id ) ? $variation_id : '',
		'data-product-id' => $product->get_id(),
		'data-form-id' => $form_id
	), $product );

	// Add compulsory class
	$form_atts[ 'class' ] .= ' bookacti-wc-form-fields';

	// Convert $form_atts array to inline attributes
	$form_attributes_str = '';
	foreach( $form_atts as $form_attribute_key => $form_attribute_value ) {
		if( $form_attribute_value !== '' ) { $form_attributes_str .= $form_attribute_key . '="' . $form_attribute_value . '" '; }
	}

	// If no default variation are selected, or if it's not an activity, or if doesn't have a form bound
	// display an empty form fields container
	if( ! $form_atts[ 'id' ] ) { 
		?><div class='bookacti-wc-form-fields'></div><?php
		return;
	}

	?>
	<div <?php echo $form_attributes_str; ?>>
		<?php 
			$form_html = bookacti_display_form( $form_id, $form_atts[ 'id' ], 'wc_product_init', false ); 
			echo $form_html;
			if( empty( $form_atts[ 'data-default-variation-id' ] ) && ! empty( $form_atts[ 'data-variation-id' ] ) ) {
			?>
				<script>
					if( typeof bookacti.form_fields === 'undefined' ) { bookacti.form_fields = []; }
					bookacti.form_fields[ '<?php echo $form_id; ?>' ] = <?php echo json_encode( $form_html ); ?>;
				</script>
			<?php
			}
		?>			
	</div>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'bookacti_add_booking_system_in_single_product_page', 20, 0 );


/**
 * Remove booking form action for WC use
 * @since 1.8.0
 * @param string $form_action
 * @param array $form
 * @param string $instance_id
 * @param string $context
 * @param array $displayed_form_fields
 * @return string
 */
function bookacti_wc_form_action_field_value( $form_action, $form, $instance_id, $context, $displayed_form_fields ) {
	if( $context === 'wc_product_init' || $context === 'wc_switch_variation' ) { $form_action = ''; }
	else {
		$calendar_field = array();
		foreach( $displayed_form_fields as $field ) { if( ! empty( $field[ 'type' ] ) && $field[ 'type' ] === 'calendar' ) { $calendar_field = $field; } }
		if( ! empty( $calendar_field[ 'form_action' ] ) ) {
			if( $calendar_field[ 'form_action' ] === 'redirect_to_product_page' ) { $form_action = ''; }
			else if( $calendar_field[ 'form_action' ] === 'add_product_to_cart' ) { $form_action = 'bookactiAddBoundProductToCart'; }
		}
	}
	return $form_action;
}
add_filter( 'bookacti_form_action_field_value', 'bookacti_wc_form_action_field_value', 10, 5 );


/**
 * Remove WC unsupported fields on product pages
 * @since 1.5.0
 * @version 1.7.15
 * @param array $fields
 * @param array $form
 * @param string $instance_id
 * @param string $context
 * @return array
 */
function bookacti_remove_unsupported_fields_from_product_page( $fields, $form, $instance_id, $context ) {
	if( $context !== 'wc_product_init' && $context !== 'wc_switch_variation' ) { return $fields; }

	$unsupported_fields = bookacti_get_wc_unsupported_form_fields();
	foreach( $fields as $i => $field ) {
		if( $field[ 'type' ] === 'calendar' ) {
			$fields[ $i ][ 'form_action' ] = 'default';
		}
		if( in_array( $field[ 'type' ], $unsupported_fields, true ) ) {
			unset( $fields[ $i ] );
		}
	}
	return $fields;
}
add_filter( 'bookacti_displayed_form_fields', 'bookacti_remove_unsupported_fields_from_product_page', 10, 4 );


/**
 * Force auto-load calendar when variation are switched
 * @since 1.5.2
 * @param array $fields
 * @param array $form
 * @param string $instance_id
 * @param string $context
 * @return array
 */
function bookacti_force_auto_load_calendar_while_switching_variations( $fields, $form, $instance_id, $context ) {
	if( $context !== 'wc_switch_variation' ) { return $fields; }

	foreach( $fields as $i => $field ) {
		if( $field[ 'name' ] === 'calendar' ) {
			$fields[ $i ][ 'auto_load' ] = 1;
		}
	}
	return $fields;
}
add_filter( 'bookacti_displayed_form_fields', 'bookacti_force_auto_load_calendar_while_switching_variations', 20, 4 );


/**
 * Change 'calendar' field attributes when it is displayed on product pages
 * @since 1.7.0 (was bookacti_display_form_field_calendar_on_wc_product_page before)
 * @param array $atts
 * @param string $instance_id
 * @param string $context
 * @return $atts
 */
function bookacti_form_field_calendar_attributes_on_wc_product_page( $atts, $instance_id, $context ) {
	if( $context !== 'wc_product_init' && $context !== 'wc_switch_variation' ) { return $atts; }

	// Change class
	if( ! empty( $atts[ 'class' ] ) ) {
		$atts[ 'class' ] .= ' bookacti-woocommerce-product-booking-system';
	} else {
		$atts[ 'class' ] = 'bookacti-woocommerce-product-booking-system';
	}

	return $atts;
}
add_filter( 'bookacti_form_field_calendar_attributes', 'bookacti_form_field_calendar_attributes_on_wc_product_page', 10, 3 );


/**
 * Set the WC quantity input value with the URL parameter 'quantity' 
 * @since 1.7.0
 * @param array $args
 * @param WC_Product $product
 * @return array
 */
function bookacti_set_wc_quantity_via_url( $args, $product ) {
	if( empty( $_GET[ 'quantity' ] ) || ! is_numeric( $_GET[ 'quantity' ] ) ) { return $args; }
	if( ! bookacti_product_is_activity( $product ) ) { return $args; }

	$args[ 'input_value' ] = intval( $_GET[ 'quantity' ] );

	return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'bookacti_set_wc_quantity_via_url', 10, 2 );




// ADD A PRODUCT TO CART

/**
 * Validate add to cart form and temporarily book the event
 * @version 1.12.0
 * @global WooCommerce $woocommerce
 * @global array $global_bookacti_wc
 * @param boolean $true
 * @param int $product_id
 * @param int $quantity
 * @param int $variation_id
 * @return boolean
 */
function bookacti_validate_add_to_cart_and_book_temporarily( $true, $product_id, $quantity, $variation_id = 0 ) {
	if( ! $true ) { return $true; }
	
	$variation_id = $variation_id ? $variation_id : ( isset( $_POST[ 'variation_id' ] ) ? intval( $_POST[ 'variation_id' ] ) : 0 );
	
	$is_activity = $variation_id ? bookacti_product_is_activity( $variation_id ) : bookacti_product_is_activity( $product_id );
	if( ! $is_activity ) { return $true; }
	
	$picked_events = ! empty( $_POST[ 'selected_events' ] ) ? bookacti_format_picked_events( $_POST[ 'selected_events' ] ) : array();
	
	// Check if there are picked events
	if( ! $picked_events ) {
		wc_add_notice( esc_html__( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' ), 'error' ); 
		return false;
	}

	global $woocommerce;
	$user_id = is_user_logged_in() ? get_current_user_id() : $woocommerce->session->get_customer_id();

	// Get product form ID
	$form_id = $variation_id ? bookacti_get_product_form_id( $variation_id, true ) : bookacti_get_product_form_id( $product_id, false );
	
	// Sanitize the variables
	$form_fields_validated = bookacti_validate_form_fields( $form_id );
	if( $form_fields_validated[ 'status' ] !== 'success' ) {
		wc_add_notice( implode( '</li><li>', $form_fields_validated[ 'messages' ] ), 'error' );
		return false;
	}
		
	// Gether the product booking form variables
	$product_bookings_data = apply_filters( 'bookacti_wc_product_booking_form_values', array(
		'product_id'		=> $product_id,
		'variation_id'		=> $variation_id,
		'user_id'			=> $user_id,
		'picked_events'		=> $picked_events,
		'quantity'			=> $quantity,
		'form_id'			=> $form_id,
		'status'			=> 'in_cart',
		'payment_status'	=> 'owed',
		'expiration_date'	=> bookacti_wc_get_new_cart_item_expiration_date()
	), $product_id, $variation_id, $form_id );
	
	// Check if data are correct before booking
	$response = bookacti_validate_booking_form( $product_bookings_data[ 'picked_events' ], $product_bookings_data[ 'quantity' ], $product_bookings_data[ 'form_id' ] );
	
	// Display error message
	if( $response[ 'status' ] !== 'success' ) {
		$messages = ! empty( $response[ 'message' ] ) ? array( $response[ 'message' ] ) : array();
		foreach( $response[ 'messages' ] as $error => $error_messages ) {
			if( ! is_array( $error_messages ) ) { $error_messages = array( $error_messages ); }
			$messages = array_merge( $messages, $error_messages );
		}
		foreach( $messages as $message ) { wc_add_notice( $message, 'error' ); }
		return false;
	}

	// Let third party plugins change form values before booking
	$product_bookings_data = apply_filters( 'bookacti_wc_product_booking_form_values_before_add_to_cart', $product_bookings_data, $product_id, $variation_id, $form_id );
	
	// Let third party plugins do their stuff before booking
	do_action( 'bookacti_wc_product_booking_form_before_add_to_cart', $product_id, $variation_id, $form_id, $product_bookings_data );
	
	global $global_bookacti_wc;
	
	// Keep one entry per group
	$picked_events = bookacti_format_picked_events( $product_bookings_data[ 'picked_events' ], true );
	
	// Add a cart item for each picked events (in case of multiple bookings)
	$validated = false;
	$i = 0;
	$last_i = count( $picked_events ) - 1;
	foreach( $picked_events as $picked_event ) {
		// Reset global
		$global_bookacti_wc = array();
		
		// Book picked events one by one
		$product_bookings_data[ 'picked_events' ] = $picked_event[ 'events' ];
		
		// Book temporarily
		$response = bookacti_wc_add_bookings_to_cart( $product_bookings_data );

		// If the event is booked, add the booking ID to the corresponding hidden field
		if( $response[ 'status' ] === 'success' ) {
			$validated = true;
			$global_bookacti_wc[ 'bookings' ] = $response[ 'bookings' ];
			$global_bookacti_wc[ 'merged_cart_item_key' ] = ! empty( $response[ 'merged_cart_item_key' ] ) ? $response[ 'merged_cart_item_key' ] : '';

			do_action( 'bookacti_wc_add_to_cart_validated', $response, $product_id, $variation_id, $form_id, $product_bookings_data );
			
			// Add a cart item for each picked events except the last one
			// Because the last cart item will be added by another process right after this function
			if( $i !== $last_i ) {
				$woocommerce->cart->add_to_cart( $product_bookings_data[ 'product_id' ], $product_bookings_data[ 'quantity' ], $product_bookings_data[ 'variation_id' ] );
			}
		}
		
		// Display error message
		else if( ! empty( $response[ 'message' ] ) ) { wc_add_notice( $response[ 'message' ], 'error' ); }
		
		++$i;
	}
	
	return $validated;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bookacti_validate_add_to_cart_and_book_temporarily', 1000, 4 );


/**
 * Add cart item data (all sent in one array)
 * @since 1.9.0 (was bookacti_add_item_data)
 * @version 1.12.0
 * @global array $global_bookacti_wc
 * @param array $cart_item_data
 * @param int $product_id
 * @param int $variation_id
 * @return array
 */
function bookacti_wc_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
	global $global_bookacti_wc;
	if( empty( $global_bookacti_wc[ 'bookings' ] ) ) { return $cart_item_data; }

	if( ! isset( $cart_item_data[ '_bookacti_options' ] ) ) { $cart_item_data[ '_bookacti_options' ] = array(); }
	
	// Sanitize cart item bookings
	$cart_item_bookings = array();
	foreach( $global_bookacti_wc[ 'bookings' ] as $cart_item_booking ) {
		if( empty( $cart_item_booking[ 'id' ] ) || empty( $cart_item_booking[ 'type' ] ) ) { continue; }
		if( ! intval( $cart_item_booking[ 'id' ] ) || ! in_array( $cart_item_booking[ 'type' ], array( 'single', 'group' ), true ) ) { continue; }
		$cart_item_bookings[] = array(
			'id' => intval( $cart_item_booking[ 'id' ] ),
			'type' => $cart_item_booking[ 'type' ]
		);
	}
	
	$cart_item_data[ '_bookacti_options' ][ 'bookings' ] = json_encode( $cart_item_bookings );

	// Add the cart item key to be merged to the cart item data for two reasons: 
	// - identify the cart item to be merged later, 
	// - prevent the WC default merging which consist in increasing the existing cart item quantity
	if( ! empty( $global_bookacti_wc[ 'merged_cart_item_key' ] ) ) {
		$cart_item_data[ '_bookacti_options' ][ 'merged_cart_item_key' ] = sanitize_title_with_dashes( $global_bookacti_wc[ 'merged_cart_item_key' ] );
	}
	
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'bookacti_wc_add_cart_item_data', 10, 3 );


/**
 * Attaches cart item data to the item
 * @param array $item
 * @param array $values
 * @param string $key
 * @return array
 */
function bookacti_get_cart_items_from_session( $item, $values, $key ) {
	if ( array_key_exists( '_bookacti_options', $values ) ) {
		$item[ '_bookacti_options' ] = $values[ '_bookacti_options' ];
	}
	return $item;
}
add_filter( 'woocommerce_get_cart_item_from_session', 'bookacti_get_cart_items_from_session', 10, 3 );


/**
 * If an activity is added to cart with the same booking data (same product, same variation, same booking) as an existing cart item
 * Merge the old cart items to the new one
 * @since 1.5.4
 * @version 1.9.0
 * @global WooCommerce $woocommerce
 * @param array $cart_item_data
 * @param string $cart_item_key
 * @return array
 */
function bookacti_merge_cart_items_with_same_booking_data( $cart_item_data, $cart_item_key ) {
	if( empty( $cart_item_data[ '_bookacti_options' ][ 'bookings' ] ) 
	||  empty( $cart_item_data[ '_bookacti_options' ][ 'merged_cart_item_key' ] ) ) { return $cart_item_data; }
	
	global $woocommerce;
	
	$old_cart_item_key = $cart_item_data[ '_bookacti_options' ][ 'merged_cart_item_key' ];
	$old_cart_item = $woocommerce->cart->get_cart_item( $old_cart_item_key );

	$product_id		= $cart_item_data[ 'product_id' ];
	$variation_id	= $cart_item_data[ 'variation_id' ];
	$quantity		= $cart_item_data[ 'quantity' ];
	$new_quantity	= $quantity;

	// Add the quantity of the old cart item to the new one
	$new_quantity += $old_cart_item[ 'quantity' ];

	// Set the new cart item quantity if it has merged with other cart items
	if( $new_quantity === $quantity ) { return $cart_item_data; }

	$merge = apply_filters( 'bookacti_merge_cart_items', true, $cart_item_data, $old_cart_item );
	if( ! $merge ) { return $cart_item_data; }

	// Remove the old cart item
	$woocommerce->cart->remove_cart_item( $old_cart_item_key );

	// Restore the bookings 
	// they has been removed while removing the duplicated cart item with $woocommerce->cart->remove_cart_item( $old_cart_item_key );
	$cart_item_expiration_date = bookacti_wc_get_new_cart_item_expiration_date();
	bookacti_wc_update_cart_item_bookings_status( $cart_item_data, 'in_cart', $cart_item_expiration_date );
	
	// Remove the merged key
	unset( $cart_item_data[ '_bookacti_options' ][ 'merged_cart_item_key' ] );
	$cart_item_data[ 'quantity' ] = $new_quantity;
	$cart_item_data = apply_filters( 'bookacti_merged_cart_item_data', $cart_item_data, $old_cart_item );
	
	do_action( 'bookacti_cart_item_merged', $cart_item_data, $old_cart_item );
	
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item', 'bookacti_merge_cart_items_with_same_booking_data', 15, 2 );


/**
 * Set the timeout for a product added to cart
 * @version 1.9.0
 * @global array $global_bookacti_wc
 * @global WooCommerce $woocommerce
 * @param string $cart_item_key
 * @param int $product_id
 * @param int $quantity
 * @param int $variation_id
 * @param array $variation
 * @param array $cart_item_data
 */
function bookacti_set_timeout_to_cart_item( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	if( $variation_id !== 0 ) {
		$is_activity = bookacti_product_is_activity( $variation_id );
	} else {
		$is_activity = bookacti_product_is_activity( $product_id );
	}
	if( ! $is_activity ) { return; }
	
	// Save the cart item key to a global to use it to display the add to cart message (wc_add_to_cart_message_html)
	global $global_bookacti_wc;
	$global_bookacti_wc[ 'added_cart_item_key' ] = $cart_item_key;
	
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return; }

	// If all cart item expire at once, set cart expiration date
	$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	if( $is_per_product_expiration ) { return; }

	global $woocommerce;
	$reset_timeout_on_change	= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
	$timeout					= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
	$cart_expiration_date		= bookacti_wc_get_cart_expiration_date();
	$expiration_date			= date( 'Y-m-d H:i:s', strtotime( '+' . $timeout . ' minutes' ) );

	// Reset cart timeout and its items timeout
	if(	$reset_timeout_on_change 
	||	! $cart_expiration_date
	||	strtotime( $cart_expiration_date ) <= time()
	||  $woocommerce->cart->get_cart_contents_count() === $quantity ) {

		// Reset global cart timeout
		bookacti_wc_set_cart_expiration_date( $expiration_date );

		// If there are others items in cart, we need to change their expiration dates
		if( $reset_timeout_on_change ) {
			bookacti_wc_reset_cart_expiration_date( $expiration_date );
		}
	}
}
add_action( 'woocommerce_add_to_cart', 'bookacti_set_timeout_to_cart_item', 30, 6 ); 


/**
 * Notice the user that his activity has been reserved and will expire, along with the add to cart confirmation
 * @since 1.0.4
 * @version 1.9.0
 * @global array $global_bookacti_wc
 * @param string $message
 * @param array $products
 * @return string
 */
function bookacti_add_to_cart_message_html( $message, $products ) {
	global $global_bookacti_wc;
	
	// If no activity has been added to cart, return the default message
	if( empty( $global_bookacti_wc[ 'bookings' ] ) 
	||  empty( $global_bookacti_wc[ 'added_cart_item_key' ] ) ) { return $message; }

	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return $message; }

	// Check if there is at least one activity 
	$is_activity = false;
	foreach( $products as $product_id => $qty ) {
		$is_activity = bookacti_product_is_activity( $product_id );
		if( $is_activity ) { break; }
	}
	if( ! $is_activity ) { return $message; }

	$temporary_book_message = bookacti_get_message( 'temporary_booking_success' );

	// If no message, return WC message only
	if( ! $temporary_book_message ) { return $message; }

	// If the message has no countdown, return the basic messages
	if( strpos( $temporary_book_message, '{time}' ) === false ) {
		return $message . '<br/>' . $temporary_book_message;
	}
	
	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $global_bookacti_wc[ 'added_cart_item_key' ] );
	$expiration_date	= ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->expiration_date ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->expiration_date : bookacti_wc_get_new_cart_item_expiration_date();
	$remaining_time		= bookacti_get_formatted_time_before_expiration( $expiration_date );

	$message .= '<br/>' . str_replace( '{time}', $remaining_time, $temporary_book_message );
	
	// Reset global
	$global_bookacti_wc = array();
	
	return $message;
}
add_filter( 'wc_add_to_cart_message_html', 'bookacti_add_to_cart_message_html', 10, 2 );


/**
 * Do not display "In stock" for activities in variable product pages
 * @since 1.0.4
 * @version 1.8.0
 * @param string $availability_html
 * @param WC_Product $variation
 * @return string
 */
function bookacti_dont_display_instock_in_variation( $availability_html, $variation ) {
	if( $variation->get_stock_status() === 'instock' ) {
		$is_activity = get_post_meta( $variation->get_id(), 'bookacti_variable_is_activity', true ) === 'yes';
		if( $is_activity ) {
			$availability_html = '';
		}
	}
	return $availability_html;
}
add_filter( 'woocommerce_get_stock_html', 'bookacti_dont_display_instock_in_variation', 10, 2 );




// CART PAGE

/**
 * Add the timeout to cart and checkout
 * @version 1.9.0
 * @global WooCommerce $woocommerce
 */
function bookacti_add_timeout_to_cart() { 
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return; }

	$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
	if( $is_per_product_expiration ) { return; }
	
	global $woocommerce;
	
	// Check if cart contains at least one item with the 'in_cart' state
	$is_in_cart = false;
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings();
	if( $cart_items_bookings ) {
		foreach( $cart_items_bookings as $cart_item_bookings ) {
			if( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state ) ) {
				if( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state === 'in_cart' ) {
					$is_in_cart = true;
					break;
				}
			}
		}
	}

	if( $is_in_cart ) {
		$cart_expiration_date = bookacti_wc_get_cart_expiration_date();
		if( $cart_expiration_date ) {
			$cart_expiration_dt = new DateTime( $cart_expiration_date );
			$now_dt = new DateTime();
			if( $cart_expiration_dt > $now_dt ) {
				$container = '<div class="bookacti-cart-expiration-container woocommerce-info">' . bookacti_get_message( 'cart_countdown' ) . '</div>';
				$countdown_html = '<span class="bookacti-countdown bookacti-cart-expiration" data-expiration-date="' . esc_attr( $cart_expiration_date ) . '" ></span>';
				echo str_replace( '{countdown}', $countdown_html, $container );
			}
		}
	} else {
		bookacti_wc_set_cart_expiration_date( null );
	}
}
add_action( 'woocommerce_before_cart', 'bookacti_add_timeout_to_cart', 10 );
add_action( 'woocommerce_checkout_order_review', 'bookacti_add_timeout_to_cart', 5 );


/**
 * Add the timeout to each cart item
 * @ersion 1.9.0
 * @param string $remove_link
 * @param string $cart_item_key
 * @return string
 */
function bookacti_add_timeout_to_cart_item( $remove_link, $cart_item_key ) { 
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return $remove_link; }
	
	$countdown_html = bookacti_wc_get_cart_item_countdown_html( $cart_item_key );
	if( ! $countdown_html ) { return $remove_link; }
	
	return '<div class="bookacti-remove-cart-item-container">' . $remove_link . '</div>' . $countdown_html;
}
add_filter( 'woocommerce_cart_item_remove_link', 'bookacti_add_timeout_to_cart_item', 10, 2 );


/**
 * Delete cart items if they are expired
 * @version 1.9.0
 * @global WooCommerce $woocommerce
 */
function bookacti_remove_expired_product_from_cart() {
	if( is_admin() ) { return; } // Return if not frontend
	
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return; } // Return if expiration is not active
	
	global $woocommerce;
	if( empty( $woocommerce ) ) { return; } // Return if woocommerce is not instanciated
	if( empty( $woocommerce->cart ) ) { return; } // Return if cart is null

	// Check if each cart item has expired, and if so, remove it
	$nb_deleted_cart_item = 0;
	
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings();
	if( $cart_items_bookings ) {
		foreach( $cart_items_bookings as $cart_item_key => $cart_item_bookings ) {
			// If one booking is expired, inactive or has no expiration date, we consider that the whole cart item is expired
			$is_expired = false;
			foreach( $cart_item_bookings[ 0 ][ 'bookings' ] as $cart_item_booking ) {
				$is_expired = bookacti_is_expired_booking( $cart_item_booking );
				if( $is_expired ) { break; }
			}
			
			if( $is_expired ) {
				// Remove the cart item
				do_action( 'bookacti_cart_item_expired', $cart_item_key );
				$is_deleted = $woocommerce->cart->remove_cart_item( $cart_item_key );
				if( $is_deleted ) {
					do_action( 'bookacti_expired_cart_item_removed', $cart_item_key );
					$nb_deleted_cart_item++;
				}
			}
		}
	}
	
	// Display feedback to tell user that (part of) his cart has expired
	if( $nb_deleted_cart_item > 0 ) {
		/* translators: %d is a variable number of products */
		$message = sprintf( esc_html( _n(	'%d product has expired and has been automatically removed from cart.', 
											'%d products have expired and have been automatically removed from cart.', 
											$nb_deleted_cart_item, 'booking-activities' ) ), $nb_deleted_cart_item );
		if( ! wc_has_notice( $message, 'error' ) ) { wc_add_notice( $message, 'error' ); }
	}
}
add_action( 'wp_loaded', 'bookacti_remove_expired_product_from_cart', 100, 0 );
/**
 * If you don't want to check cart expiration on each page load, please call at least these 4 actions instead:
 * 
 * add_action( 'woocommerce_check_cart_items', 'bookacti_remove_expired_product_from_cart', 10, 0 );
 * add_action( 'woocommerce_review_order_before_cart_contents', 'bookacti_remove_expired_product_from_cart', 10, 0 );
 * add_action( 'woocommerce_before_mini_cart', 'bookacti_remove_expired_product_from_cart', 10, 0 );
 * add_action( 'woocommerce_checkout_process', 'bookacti_remove_expired_product_from_cart', 10, 0 );
 */


/**
 * If quantity changes in cart, temporarily book the extra quantity if possible
 * @version 1.12.0
 * @param int $new_quantity
 * @param string $cart_item_key
 */
function bookacti_update_quantity_in_cart( $new_quantity, $cart_item_key ) { 
	global $woocommerce;
	$item = $woocommerce->cart->get_cart_item( $cart_item_key );
	$old_quantity = $item[ 'quantity' ];
	$restore_qty = false;

	if( empty( $item[ '_bookacti_options' ] ) || $new_quantity === $old_quantity ) { return $new_quantity; }

	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	if( ! $cart_item_bookings ) { return $new_quantity; }
	
	// Check if the bookings have the "in_cart" status
	$is_in_cart = false;
	if( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state ) ) {
		if( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state === 'in_cart' ) {
			$is_in_cart = true;
		}
	}
	
	if( $is_in_cart ) {
		$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $new_quantity );
		
		// Feedback the errors
		if( $response[ 'status' ] !== 'success' ) {
			foreach( $response[ 'messages' ] as $message ) {
				wc_add_notice( $message, 'error' );
			}
		}
		
		// If the quantity > availability, change the $new_quantity to the available quantity
		if( $response[ 'status' ] === 'failed' && ! empty( $response[ 'messages' ][ 'qty_sup_to_avail' ] ) && ! empty( $response[ 'availability' ] ) ) {
			$new_quantity = $response[ 'availability' ] + $old_quantity;
			$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $new_quantity );
		}
		
		// If the event is unavailable, restore to old quantity
		if( $response[ 'status' ] !== 'success' ) { $restore_qty = true; }
		
		// Update the cart item bookings quantity
		if( $response[ 'status' ] === 'success' && $new_quantity !== $old_quantity ) {
			$updated = true;
			if( $new_quantity ) { $updated = bookacti_wc_update_cart_item_bookings_quantity( $cart_item_key, $new_quantity ); }
			
			// If the quantity is 0, remove the cart item, but only change the booking status to "removed" if the cart item was 'in_cart'
			else {
				// Get the cart item bookings status and order status
				$cart_item_bookings_status = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state : '' );
				$cart_item_bookings_order_id = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id : '' );
				$cart_item_bookings_order_status = '';
				if( $cart_item_bookings_order_id ) { 
					$cart_item_bookings_order = wc_get_order( $cart_item_bookings_order_id );
					if( $cart_item_bookings_order ) { $cart_item_bookings_order_status = $cart_item_bookings_order->get_status( 'edit' ); }
				}
				if( $cart_item_bookings_status === 'in_cart' || $cart_item_bookings_order_status === 'failed' ) { 
					$updated = bookacti_wc_update_cart_item_bookings_status( $cart_item_key, $cart_item_bookings_order_status === 'failed' ? 'cancelled' : 'removed' );
				}
			}
			
			if( ! $updated ) {
				$restore_qty = true;
				wc_add_notice( esc_html__( 'An error occurred while trying to change the quantity of the bookings attached to the item.', 'booking-activities' ), 'error' );
			}
		}
	}

	// If the product is not "in_cart", it means that the order is already in process (maybe waiting for payment)
	else {
		$restore_qty = true;
		wc_add_notice( esc_html__( 'You can\'t update quantity since this product is temporarily booked on an order pending payment. Please, first cancel the order or remove this product from cart.', 'booking-activities' ), 'error' );
	}
	
	return $restore_qty ? $old_quantity : $new_quantity;
}
add_filter( 'woocommerce_stock_amount_cart_item', 'bookacti_update_quantity_in_cart', 40, 2 ); 


/**
 * Remove in_cart bookings when cart items are removed from cart
 * @version 1.9.0
 * @global WooCommerce $woocommerce
 * @param string $cart_item_key
 * @param WC_Cart $cart
 */
function bookacti_remove_bookings_of_removed_cart_item( $cart_item_key, $cart ) { 
	bookacti_remove_cart_item_bookings( $cart_item_key );
}
add_action( 'woocommerce_remove_cart_item', 'bookacti_remove_bookings_of_removed_cart_item', 10, 2 ); 


/**
 * Remove corrupted cart items bookings when they are removed from cart
 * @since 1.5.8
 * @version 1.9.0
 * @param string $cart_item_key
 * @param array $item
 */
function bookacti_remove_bookings_of_corrupted_cart_items( $cart_item_key, $item ) {
	bookacti_remove_cart_item_bookings( $cart_item_key );
}
add_action( 'woocommerce_remove_cart_item_from_session', 'bookacti_remove_bookings_of_corrupted_cart_items', 10, 2 );


/**
 * Remove cart item bookings
 * @since 1.5.8
 * @version 1.12.0
 * @global WooCommerce $woocommerce
 * @param string $cart_item_key
 */
function bookacti_remove_cart_item_bookings( $cart_item_key ) {
	global $woocommerce;
	$item = $woocommerce->cart->get_cart_item( $cart_item_key );
	if( ! $item ) { return; }
	if( empty( $item[ '_bookacti_options' ] ) ) { return; }
	
	// Get the cart item bookings status and order status
	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	if( ! $cart_item_bookings ) { return; }
	$cart_item_bookings_status = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state : '' );
	$cart_item_bookings_order_id = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id : '' );
	$cart_item_bookings_order_status = '';
	if( $cart_item_bookings_order_id ) { 
		$cart_item_bookings_order = wc_get_order( $cart_item_bookings_order_id );
		if( $cart_item_bookings_order ) { $cart_item_bookings_order_status = $cart_item_bookings_order->get_status( 'edit' ); }
	}
	
	if( $cart_item_bookings_status === 'in_cart' || $cart_item_bookings_order_status === 'failed' ) {
		bookacti_wc_update_cart_item_bookings_status( $cart_item_key, $cart_item_bookings_order_status === 'failed' ? 'cancelled' : 'removed' );
	}
}


/**
 * Restore the booking if user change his mind after deleting one
 * @version 1.12.0
 * @global WooCommerce $woocommerce
 * @param string $cart_item_key
 */
function bookacti_restore_bookings_of_removed_cart_item( $cart_item_key ) { 
	global $woocommerce;
	$item = $woocommerce->cart->get_cart_item( $cart_item_key );
	if( empty( $item[ '_bookacti_options' ] ) ) { return; }

	$quantity = $item[ 'quantity' ];
	$init_quantity = $quantity;
	$status_updated = false;
	
	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item_key );
	if( ! $cart_item_bookings ) { return; }
	
	// Check if the cart item bookings quantity can be "changed" to its own quantity
	// is the same as checking if a cart item can be restored
	$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $quantity );
	
	// Feedback the errors
	if( $response[ 'status' ] !== 'success' ) {
		foreach( $response[ 'messages' ] as $message ) {
			wc_add_notice( $message, 'error' );
		}
	} 
	
	// If the quantity > availability, change the $quantity to the available quantity
	if( $response[ 'status' ] === 'failed' && ! empty( $response[ 'messages' ][ 'qty_sup_to_avail' ] ) &&  ! empty( $response[ 'availability' ] ) ) {
		$quantity = intval( $response[ 'availability' ] );
		$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $quantity );
	}
	
	$restore_bookings = $response[ 'status' ] === 'success';

	// Update the cart item bookings quantity
	if( $restore_bookings && $quantity !== $init_quantity ) { 
		$updated = true;
		if( $quantity ) { $updated = bookacti_wc_update_cart_item_bookings_quantity( $cart_item_key, $quantity ); }

		// If the quantity is 0, remove the cart item, but only change the booking status to "removed" if the cart item was 'in_cart'
		else {
			// Get the cart item bookings status and order status
			$cart_item_bookings_status = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_state : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state : '' );
			$cart_item_bookings_order_id = $cart_item_bookings[ 0 ][ 'type' ] === 'group' && ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->group_order_id : ( ! empty( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id ) ? $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->order_id : '' );
			$cart_item_bookings_order_status = '';
			if( $cart_item_bookings_order_id ) { 
				$cart_item_bookings_order = wc_get_order( $cart_item_bookings_order_id );
				if( $cart_item_bookings_order ) { $cart_item_bookings_order_status = $cart_item_bookings_order->get_status( 'edit' ); }
			}
			if( $cart_item_bookings_status === 'in_cart' || $cart_item_bookings_order_status === 'failed' ) { 
				$updated = bookacti_wc_update_cart_item_bookings_status( $cart_item_key, $cart_item_bookings_order_status === 'failed' ? 'cancelled' : 'removed' );
			}
		}
			
		if( ! $updated ) { 
			$restore_bookings = false;
			wc_add_notice( esc_html__( 'An error occurred while trying to change the quantity of the bookings attached to the item.', 'booking-activities' ), 'error' );
		}
	}
	
	// Update the cart item bookings status
	if( $restore_bookings ) {
		$cart_item_expiration_date = bookacti_wc_get_new_cart_item_expiration_date();
		$status_updated = bookacti_wc_update_cart_item_bookings_status( $cart_item_key, 'in_cart', $cart_item_expiration_date );
		$restore_bookings = $status_updated;
	}
	
	$restore_bookings = apply_filters( 'bookacti_restore_bookings_of_restored_cart_item', $restore_bookings, $cart_item_key, $quantity );
	
	if( ! $restore_bookings ) {
		do_action( 'bookacti_cart_item_not_restored', $item, $quantity );
		$removed = $woocommerce->cart->remove_cart_item( $cart_item_key );
		if( $removed ) {
			do_action( 'bookacti_cart_item_not_restored_removed', $item, $quantity );
		}
	} else {
		if( $quantity !== $init_quantity ) { $woocommerce->cart->set_quantity( $cart_item_key, $quantity, true ); }
		$item[ 'quantity' ] = $quantity;
		do_action( 'bookacti_cart_item_restored', $item, $quantity );
	}
}
add_action( 'woocommerce_cart_item_restored', 'bookacti_restore_bookings_of_removed_cart_item', 10, 1 );


/**
 * Display the custom metadata in cart and checkout
 * @since 1.9.0 (was bookacti_get_item_data)
 * @param array $item_data
 * @param array $cart_item
 * @return array
 */
function bookacti_wc_cart_item_meta_formatted( $item_data, $cart_item ) {
	if( empty( $cart_item[ '_bookacti_options' ][ 'bookings' ] ) ) { return $item_data; }

	$cart_item_bookings = bookacti_wc_get_cart_item_bookings( $cart_item[ 'key' ], array( 'fetch_meta' => true ) );
	if( ! $cart_item_bookings ) { return $item_data; }
	
	$cart_item_bookings_attributes = bookacti_wc_get_item_bookings_attributes( $cart_item_bookings );
	if( ! $cart_item_bookings_attributes ) { return $item_data; }
	
	$hidden_cart_item_bookings_attributes = bookacti_wc_get_hidden_cart_item_bookings_attributes();
	
	foreach( $cart_item_bookings_attributes as $cart_item_bookings_attribute ) {
		foreach( $cart_item_bookings_attribute as $cart_item_booking_attribute_name => $cart_item_booking_attribute ) {
			if( in_array( $cart_item_booking_attribute_name, $hidden_cart_item_bookings_attributes, true ) ) { continue; }
			$item_data[] = array(
				'key' => $cart_item_booking_attribute[ 'label' ] ? $cart_item_booking_attribute[ 'label' ] : $cart_item_booking_attribute_name,
				'value' => $cart_item_booking_attribute[ 'value' ]
			);
		}
	}
	
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'bookacti_wc_cart_item_meta_formatted', 10, 2 );


/**
 * Add class to activity cart item to identify them
 * @version 1.9.0
 * @param string $classes
 * @param array $cart_item
 * @param string $cart_item_key
 * @return string
 */
function bookacti_add_class_to_activity_cart_item( $classes, $cart_item, $cart_item_key ) {
	if( ! empty( $cart_item[ '_bookacti_options' ][ 'bookings' ] ) ) {
		$classes .= ' bookacti-cart-item-activity';
	}
	return $classes;
}
add_filter( 'woocommerce_cart_item_class', 'bookacti_add_class_to_activity_cart_item', 10, 3 );


/**
 * Format label of custom metadata in cart and checkout
 * @version 1.9.0
 * @param string $label
 * @param string $name
 * @return string
 */
function bookacti_define_label_of_item_data( $label, $name ) {
	if( $label === 'bookings' || $label === 'bookacti_bookings' ) { $label = esc_html__( 'Bookings', 'booking-activities' ); }
	
	// Backward compatibility
	if( $label === '_bookacti_booking_id' 
	||  $label === 'bookacti_booking_id' )			{ $label = esc_html__( 'Booking', 'booking-activities' ); }
	if( $label === '_bookacti_booking_group_id' 
	||  $label === 'bookacti_booking_group_id' )	{ $label = esc_html__( 'Booking group', 'booking-activities' ); }
	if( $label === '_bookacti_refund_method' )		{ $label = esc_html__( 'Refund method', 'booking-activities' ); }
	if( $label === 'bookacti_refund_coupon' )		{ $label = esc_html__( 'Coupon code', 'booking-activities' ); }
	
	return $label;
}
add_filter( 'woocommerce_attribute_label', 'bookacti_define_label_of_item_data', 10, 2 );


/**
 * Take into account the in_cart bookings to compute events availability
 * @since 1.10.1 (was bookacti_wc_number_of_bookings_per_user_by_events_query)
 * @global wpdb $wpdb
 * @param string $query
 * @param array $events
 * @return string
 */
function bookacti_wc_number_of_bookings_per_event_per_user_query_include_current_user_in_cart_bookings( $query, $events ) {
	global $wpdb;
	$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
	$search		= 'LEFT JOIN ' . BOOKACTI_TABLE_BOOKINGS . ' as B ON B.event_id = E.id AND B.active = 1';
	$replace	= 'LEFT JOIN ' . BOOKACTI_TABLE_BOOKINGS . ' as B ON B.event_id = E.id AND ( B.active = 1 OR ( B.state = "in_cart" AND B.user_id = %s ) )';
	$replace	= $wpdb->prepare( $replace, $current_user_id );
	$query		= str_replace( $search, $replace, $query );
	return $query;
}
add_filter( 'bookacti_number_of_bookings_per_event_per_user_query', 'bookacti_wc_number_of_bookings_per_event_per_user_query_include_current_user_in_cart_bookings', 10, 2 );


/**
 * Consider the booking as active if it is in current user's cart while trying to change quantity
 * @since 1.9.0
 * @param int $is_active
 * @param object $booking
 * @param int $new_quantity
 * @return int
 */
function bookacti_wc_booking_quantity_check_is_active( $is_active, $booking, $new_quantity ) {
	if( $is_active ) { return $is_active; }
	$current_user_id = apply_filters( 'bookacti_current_user_id', get_current_user_id() );
	if( $booking->user_id == $current_user_id && $booking->state === 'in_cart' ) {
		$is_active = 1;
	}
	return $is_active;
}
add_filter( 'bookacti_booking_quantity_check_is_active', 'bookacti_wc_booking_quantity_check_is_active', 10, 3 );




// CHECKOUT

/**
 * Add the information as meta data so that it can be seen as part of the order 
 * (to hide any meta data from the customer just start it with an underscore)
 * To be used since WC 3.0 on woocommerce_checkout_create_order_line_item hook
 * @since 1.1.0
 * @version 1.9.0
 * @param WC_Order_Item_Product $item
 * @param string $cart_item_key
 * @param array $values
 * @param WC_Order $order
 */
function bookacti_save_order_item_metadata( $item, $cart_item_key, $values, $order ) {
	// Do not process non-booking metadata
	if( ! array_key_exists( '_bookacti_options', $values ) ) { return; }
	$item->add_meta_data( 'bookacti_bookings', $values[ '_bookacti_options' ][ 'bookings' ], true );
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bookacti_save_order_item_metadata', 10, 4 );


/**
 * Add the timeout to each cart item in the checkout review
 * @version 1.9.0
 * @param string $cart_item_name
 * @param array $values
 * @param string $cart_item_key
 * @return string
 */
function bookacti_add_timeout_to_cart_item_in_checkout_review( $cart_item_name, $values, $cart_item_key ) { 
	$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
	if( ! $is_expiration_active ) { return $cart_item_name; }

	if( ! empty( $values[ '_bookacti_options' ] ) && is_checkout() ) {
		$countdown_html = bookacti_wc_get_cart_item_countdown_html( $cart_item_key );
		$cart_item_name = $countdown_html . $cart_item_name;
	}

	return $cart_item_name;
}
add_filter( 'woocommerce_cart_item_name', 'bookacti_add_timeout_to_cart_item_in_checkout_review', 10, 3 );


/**
 * Check bookings availability before validating checkout in case that "in_cart" state is not active
 * @since 1.3.0
 * @version 1.10.1
 * @global WooCommerce $woocommerce
 * @param array $posted_data An array of posted data.
 * @param WP_Error $errors
 */
function bookacti_availability_check_before_checkout( $posted_data, $errors = null ) {
	// Do not make this check if "in_cart" bookings are active, because they already hold their own booking quantity 
	if( in_array( 'in_cart', bookacti_get_active_booking_states(), true ) ) { return; }
	
	$cart_items_bookings = bookacti_wc_get_cart_items_bookings();
	if( ! $cart_items_bookings ) { return; }
	
	global $woocommerce;
	$valid_status = array_merge( array( 'in_cart' ), bookacti_get_active_booking_states() );
	$nb_deleted_cart_item = 0;
	
	foreach( $cart_items_bookings as $cart_item_key => $cart_item_bookings ) {
		// Check booking status, they must be in_cart or any active status, else, remove cart item
		if( ! in_array( $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->state, $valid_status, true ) ) { 
			$woocommerce->cart->remove_cart_item( $cart_item_key );
			++$nb_deleted_cart_item;
			continue;
		}
		
		// Check if the cart item bookings quantity can be "changed" to its own quantity
		// is the same as checking if an inactive cart item can be turned to active
		$quantity = $cart_item_bookings[ 0 ][ 'bookings' ][ 0 ]->quantity;
		$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $cart_item_bookings, $quantity );
		
		// Display the error and stop checkout processing
		if( $response[ 'status' ] !== 'success' ) {
			foreach( $response[ 'messages' ] as $error => $error_message ) {
				$errors->add( $error, $error_message );
			}
		}
	}
	
	// Prevent checkout if a booking has been removed from cart
	if( $nb_deleted_cart_item ) {
		$expired_message = sprintf( esc_html( _n(	'%d product has expired and has been automatically removed from cart.', 
													'%d products have expired and have been automatically removed from cart.', 
													$nb_deleted_cart_item, 'booking-activities' ) ), $nb_deleted_cart_item );
		$errors->add( 'expired_booking', $expired_message );
	}
}
add_action( 'woocommerce_after_checkout_validation', 'bookacti_availability_check_before_checkout', 10, 2 );


/**
 * Create one order item per booking (group)
 * @since 1.9.0
 * @param WC_Order $order
 * @param array $data Data posted during checkout
 */
function bookacti_wc_checkout_create_one_order_item_per_booking( $order, $data ) {
	$items = $order->get_items();
	if( ! $items ) { return; }
	
	$items_bookings = bookacti_wc_get_order_items_bookings( $items );
	if( ! $items_bookings ) { return; }
	
	$price_decimals = wc_get_price_decimals();
	
	foreach( $items as $item_id => $item ) {
		if( empty( $items_bookings[ $item_id ] ) ) { continue; }
		
		$nb_bookings = count( $items_bookings[ $item_id ] );
		if( $nb_bookings === 1 ) { continue; }
		
		// Get the default prices
		$default_subtotal		= round( $item->get_subtotal() / $nb_bookings, $price_decimals );
		$default_total			= round( $item->get_total() / $nb_bookings, $price_decimals );
		$default_subtotal_tax	= wc_round_tax_total( $item->get_subtotal_tax() / $nb_bookings, $price_decimals );
		$default_total_tax		= wc_round_tax_total( $item->get_total_tax() / $nb_bookings, $price_decimals );
		$item_taxes				= maybe_unserialize( $item->get_taxes() );
		
		$default_taxes = array();
		foreach( $item_taxes as $i => $taxes ) {
			$default_taxes[ $i ] = array();
			foreach( $taxes as $tax_id => $amount ) {
				$default_taxes[ $i ][ $tax_id ] = wc_round_tax_total( floatval( $amount ) / $nb_bookings, $price_decimals );
			}
		}
		
		// Add the rests in the first item totals
		$default_taxes_total_rest	= ! empty( $item_taxes[ 'total' ] ) && ! empty( $default_taxes[ 'total' ] ) ? wc_round_tax_total( array_sum( $item_taxes[ 'total' ] ) - ( array_sum( $default_taxes[ 'total' ] ) * $nb_bookings ), $price_decimals ) : 0;
		$default_taxes_subtotal_rest= ! empty( $item_taxes[ 'subtotal' ] ) && ! empty( $default_taxes[ 'subtotal' ] ) ? wc_round_tax_total( array_sum( $item_taxes[ 'subtotal' ] ) - ( array_sum( $default_taxes[ 'subtotal' ] ) * $nb_bookings ), $price_decimals ) : 0;
		$first_item_taxes = $default_taxes;
		foreach( $first_item_taxes as $i => $taxes ) {
			foreach( $taxes as $tax_id => $amount ) {
				if( $i === 'total' )	{ $first_item_taxes[ $i ][ $tax_id ] += $default_taxes_total_rest; break; }
				if( $i === 'subtotal' ) { $first_item_taxes[ $i ][ $tax_id ] += $default_taxes_subtotal_rest; break; }
			}
		}
		
		$default_subtotal_rest		= $item->get_subtotal() - ( $default_subtotal * $nb_bookings );
		$default_total_rest			= $item->get_total() - ( $default_total * $nb_bookings );
		$default_subtotal_tax_rest	= $item->get_subtotal_tax() - ( $default_subtotal_tax * $nb_bookings );
		$default_total_tax_rest		= $item->get_total_tax() - ( $default_total_tax * $nb_bookings );
		
		$item_clone = clone $item;
		$i=0;
		foreach( $items_bookings[ $item_id ] as $item_booking ) {
			$item_bookings_ids = json_encode( array( array( 'id' => intval( $item_booking[ 'id' ] ), 'type' => $item_booking[ 'type' ] ) ) );
			
			// For the first booking, use the current item
			$the_item = $i === 0 ? $item : clone $item_clone;
			
			// Update the item totals and bookings meta
			$the_item->update_meta_data( 'bookacti_bookings', $item_bookings_ids );
			$the_item->set_subtotal( $default_subtotal + $default_subtotal_rest );
			$the_item->set_total( $default_total + $default_total_rest );
			$the_item->set_taxes( maybe_serialize( $i === 0 ? $first_item_taxes : $default_taxes ) );
			$the_item->set_subtotal_tax( $default_subtotal_tax + $default_subtotal_tax_rest );
			$the_item->set_total_tax( $default_total_tax + $default_total_tax_rest );
			
			// Add item to order and save
			if( $i === 0 ) { $i++; $default_subtotal_rest = 0; $default_total_rest = 0; $default_subtotal_tax_rest = 0; $default_total_tax_rest = 0; }
			else { $order->add_item( $the_item ); }
		}
	}
}
add_action( 'woocommerce_checkout_create_order', 'bookacti_wc_checkout_create_one_order_item_per_booking', 100, 2 );


/**
 * Check availability before paying for a failed order
 * @since 1.7.13
 * @version 1.12.0
 * @param WC_Order $order
 */
function bookacti_availability_check_before_pay_action( $order ) {
	$error_occurred = false;
	$order_id = $order->get_id();
	$order_items_bookings = bookacti_wc_get_order_items_bookings( $order );
	
	// If the booking is already attached to another non "failed" order, prevent to purchase it again
	foreach( $order_items_bookings as $item_id => $order_item_bookings ) {
		foreach( $order_item_bookings as $order_item_booking ) {
			$booking_order_id = $order_item_booking[ 'type' ] === 'group' && ! empty( $order_item_booking[ 'bookings' ][ 0 ]->group_order_id ) ? $order_item_booking[ 'bookings' ][ 0 ]->group_order_id : ( ! empty( $order_item_booking[ 'bookings' ][ 0 ]->order_id ) ? $order_item_booking[ 'bookings' ][ 0 ]->order_id : 0 );
			if( $booking_order_id && intval( $booking_order_id ) !== intval( $order_id ) ) {
				$booking_order = wc_get_order( $booking_order_id );
				if( $booking_order ) {
					if( $booking_order->get_status() !== 'failed' ) {
						$error_occurred = true;
						$title = $order_item_booking[ 'type' ] === 'group' && ! empty( $order_item_booking[ 'bookings' ][ 0 ]->group_title ) ? $order_item_booking[ 'bookings' ][ 0 ]->group_title : ( ! empty( $order_item_booking[ 'bookings' ][ 0 ]->event_title ) ? $order_item_booking[ 'bookings' ][ 0 ]->event_title : '' );
						$last_booking = end( $order_item_booking[ 'bookings' ] );
						$first_booking = reset( $order_item_booking[ 'bookings' ] );
						$dates = bookacti_get_formatted_event_dates( $first_booking->event_start, $last_booking->event_end, false );
						/* translators: %1$s = the booking ID. %2$s = the event title and dates. %3$s = the order ID. */
						wc_add_notice( sprintf( esc_html__( 'You have already purchased the booking #%1$s "%2$s" in the order #%3$s.', 'booking-activities' ), $order_item_booking[ 'id' ], $title ? $title . ' (' . $dates . ')' : $dates, $booking_order_id ), 'error' );
					}
				}
			}
		}
	}
	
	// Validate events availability
	foreach( $order_items_bookings as $order_item_key => $order_item_bookings ) {
		// Check if the order item bookings quantity can be "changed" to its own quantity
		// is the same as checking if an inactive order item can be turned to active
		$quantity = $order_item_bookings[ 0 ][ 'bookings' ][ 0 ]->quantity;
		$response = bookacti_wc_validate_cart_item_bookings_new_quantity( $order_item_bookings, $quantity );
		
		// Display the error and stop checkout processing
		if( $response[ 'status' ] !== 'success' ) {
			$error_occurred = true;
			foreach( $response[ 'messages' ] as $error => $error_message ) {
				wc_add_notice( $error_message, 'error' );
			}
		}
	}
	
	// If the events are no longer available, prevent submission and feedback user
	if( $error_occurred ) {
		wc_add_notice( esc_html__( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ), 'error' );
		$checkout_url = $order->get_checkout_payment_url();
		wp_redirect( $checkout_url );
		exit;
	}
}
add_action( 'woocommerce_before_pay_action', 'bookacti_availability_check_before_pay_action', 10, 1 );


/**
 * Change order bookings states after the customer validates checkout
 * @since 1.2.2
 * @version 1.12.0
 * @param int $order_id
 * @param array $posted_data
 * @param WC_Order $order
 */
function bookacti_change_booking_state_after_checkout( $order_id, $posted_data, $order = null ) {
	if( ! $order ) { $order = wc_get_order( $order_id ); }
	if( ! $order ) { return; }
	
	// Get bookings before change
	$order_items_bookings = bookacti_wc_get_order_items_bookings( $order );
	
	// If the booking is already attached to another non "failed" order, prevent to purchase it again
	$error_messages = array();
	foreach( $order_items_bookings as $item_id => $order_item_bookings ) {
		foreach( $order_item_bookings as $order_item_booking ) {
			$booking_order_id = $order_item_booking[ 'type' ] === 'group' && ! empty( $order_item_booking[ 'bookings' ][ 0 ]->group_order_id ) ? $order_item_booking[ 'bookings' ][ 0 ]->group_order_id : ( ! empty( $order_item_booking[ 'bookings' ][ 0 ]->order_id ) ? $order_item_booking[ 'bookings' ][ 0 ]->order_id : 0 );
			if( $booking_order_id && intval( $booking_order_id ) !== intval( $order_id ) ) {
				$booking_order = wc_get_order( $booking_order_id );
				if( $booking_order ) {
					if( $booking_order->get_status() !== 'failed' ) {
						$title = $order_item_booking[ 'type' ] === 'group' && ! empty( $order_item_booking[ 'bookings' ][ 0 ]->group_title ) ? $order_item_booking[ 'bookings' ][ 0 ]->group_title : ( ! empty( $order_item_booking[ 'bookings' ][ 0 ]->event_title ) ? $order_item_booking[ 'bookings' ][ 0 ]->event_title : '' );
						$last_booking = end( $order_item_booking[ 'bookings' ] );
						$first_booking = reset( $order_item_booking[ 'bookings' ] );
						$dates = bookacti_get_formatted_event_dates( $first_booking->event_start, $last_booking->event_end, false );
						$error_messages[] = new Exception( sprintf( esc_html__( 'You have already purchased the booking #%1$s "%2$s" in the order #%3$s.', 'booking-activities' ), $order_item_booking[ 'id' ], $title ? $title . ' (' . $dates . ')' : $dates, $booking_order_id ) );
						wc_delete_order_item( $item_id );
					}
				}
			}
		}
	}
	
	if( $error_messages ) { throw new Exception( implode( '<br/>', $error_messages ) ); }
	if( $order->get_status() === 'failed' ) { return; }
	
	$needs_payment = WC()->cart->needs_payment();
	$customer_id= $order->get_user_id( 'edit' );
	$user_email	= $order->get_billing_email( 'edit' );
	$user_id	= is_numeric( $customer_id ) && $customer_id ? intval( $customer_id ) : ( $user_email ? $user_email : apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
	
	$new_data = array(
		'user_id' => $user_id,
		'order_id' => $order_id,
		'status' => $needs_payment ? 'pending' : 'booked',
		'payment_status' => $needs_payment ? 'owed' : 'paid',
		'active' => 'auto'
	);
	
	// Update the booking
	$updated = bookacti_wc_update_order_items_bookings( $order, $new_data );
	
	// If the user has no account, bind the user data to the bookings
	if( ! $new_data[ 'user_id' ] || ! is_numeric( $new_data[ 'user_id' ] ) ) {
		bookacti_wc_save_no_account_user_data_as_booking_meta( $order );
	}
	
	// Send new status notifications even if the booking status has not changed
	// The new status notifications is automatically sent if the booking status has changed (on the bookacti_wc_order_item_booking_updated hook)
	foreach( $order_items_bookings as $item_id => $order_item_bookings ) {
		foreach( $order_item_bookings as $order_item_booking ) {
			$old_status = $order_item_booking[ 'type' ] === 'group' && ! empty( $order_item_booking[ 'bookings' ][ 0 ]->group_state ) ? $order_item_booking[ 'bookings' ][ 0 ]->group_state : ( ! empty( $order_item_booking[ 'bookings' ][ 0 ]->state ) ? $order_item_booking[ 'bookings' ][ 0 ]->state : '' );
			if( $old_status !== $new_data[ 'status' ] ) { continue; }
			if( $order_item_booking[ 'type' ] === 'single' && in_array( $order_item_booking[ 'id' ], $updated[ 'booking_ids' ], true ) ) { continue; }
			if( $order_item_booking[ 'type' ] === 'group' && in_array( $order_item_booking[ 'id' ], $updated[ 'booking_group_ids' ], true ) ) { continue; }
			bookacti_wc_send_order_item_booking_status_notification( $order_item_booking, $new_data[ 'status' ], $order, true );
		}
	}
}
add_action( 'woocommerce_checkout_order_processed', 'bookacti_change_booking_state_after_checkout', 10, 3 );




// ORDER

/**
 * Format the order item meta values to display
 * @since 1.0.4 (was bookacti_format_order_item_meta)
 * @version 1.9.0
 * @param string $html
 * @param WC_Order_Item $item
 * @param array $args
 * @return string
 */
function bookacti_wc_order_item_meta_formatted( $formatted_meta, $item ) {
	foreach( $formatted_meta as $meta_id => $meta ) {
		// Backward compatibility
		if( $meta->key === '_bookacti_refund_method' ) { $meta->display_value = bookacti_get_refund_label( $meta->value ); continue; }
		if( $meta->key === 'bookacti_refund_coupon' ) { continue; }
		
		if( substr( $meta->key, 0, 9 ) !== 'bookacti_' ) { continue; }
		
		// Format bookings data to be displayed
		// Add 'bookacti_booking_id', 'bookacti_booking_group_id' for Backward compatibility with orders made before BA 1.9.0
		if( in_array( $meta->key, array( 'bookacti_bookings', 'bookacti_booking_id', 'bookacti_booking_group_id' ), true ) ) {
			$item_id = $item->get_id();
			$order_items_bookings = bookacti_wc_get_order_items_bookings( array( $item ), array( 'fetch_meta' => true ) );
			$order_item_bookings = ! empty( $order_items_bookings[ $item_id ] ) ? $order_items_bookings[ $item_id ] : bookacti_wc_format_order_item_bookings_ids( $item );
			$meta->display_key = esc_html( _n( 'Booking', 'Bookings', count( $order_item_bookings ), 'booking-activities' ) );
			$meta->display_value = bookacti_wc_get_item_bookings_attributes_html( $order_item_bookings );
		}
		// Remove the other bookacti_ attributes (Backward compatibility)
		else { unset( $formatted_meta[ $meta_id ] ); }
	}
	return $formatted_meta;
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'bookacti_wc_order_item_meta_formatted', 10, 2 );


/**
 * Allow additional inline CSS properties
 * @since 1.9.0
 * @param array $array
 * @return array
 */
function bookacti_wc_add_safe_style_css( $array ) {
	$array = array_unique( array_merge( $array, array( 'display', 'margin-inline', 'margin-inline-start', 'margin-inline-end' ) ) );
	return $array;
}
add_filter( 'safe_style_css', 'bookacti_wc_add_safe_style_css', 1000, 1 );


/**
 * Add class to activity order item to identify them on order received page
 * @since 1.1.0
 * @version 1.9.0
 * @param string $classes
 * @param WC_Order_Item $item
 * @param WC_Order $order
 * @return string
 */
function bookacti_add_class_to_activity_order_item( $classes, $item, $order ) {
	$item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $item );
	if( $item_bookings_ids ) { $classes .= ' bookacti-order-item-activity'; }
	return $classes;
}
add_filter( 'woocommerce_order_item_class', 'bookacti_add_class_to_activity_order_item', 10, 3 );




// BOOKING LIST

/**
 * Add a column called 'Price' to user booking list
 * @since 1.7.4 (was bookacti_add_woocommerce_price_column_to_bookings_list)
 * @version 1.7.10
 * @param array $columns
 * @return array
 */
function bookacti_add_woocommerce_price_column_to_user_booking_list( $columns ) {
	if( ! isset( $columns[ 'price' ] ) ) { 
		$columns[ 'price' ] = esc_html__( 'Price', 'booking-activities' );
	}
	return $columns;
}
add_filter( 'bookacti_user_booking_list_columns_labels', 'bookacti_add_woocommerce_price_column_to_user_booking_list', 10, 1 );


/**
 * Reorder the 'Price' column in user booking list
 * @since 1.7.4
 * @param array $columns
 * @return array
 */
function bookacti_reorder_woocommerce_price_column_in_user_booking_list( $columns ) {
	if( in_array( 'price', $columns, true ) ) { return $columns; }
	$columns[ 45 ] = 'price';
	return $columns;
}
add_filter( 'bookacti_user_booking_list_default_columns', 'bookacti_reorder_woocommerce_price_column_in_user_booking_list', 10, 1 );


/**
 * Add WC data to the user booking list
 * @since 1.7.12 (was bookacti_fill_wc_price_column_in_booking_list)
 * @version 1.11.3
 * @param array $booking_list_items
 * @param array $bookings
 * @param array $booking_groups
 * @param array $bookings_per_group
 * @param array $displayed_groups
 * @param array $users
 * @param array $filters
 * @param array $columns
 * @return array
 */
function bookacti_add_wc_data_to_user_booking_list_items( $booking_list_items, $bookings, $booking_groups, $bookings_per_group, $displayed_groups, $users, $filters, $columns ) {
	if( ! $booking_list_items ) { return $booking_list_items; }

	$order_ids = array();
	$booking_ids = array();
	$booking_group_ids = array();
	foreach( $booking_list_items as $booking_id => $booking_list_item ) {
		// Get booking which are part of an order
		if( $booking_list_item[ 'order_id' ] ) {
			if( ! in_array( $booking_list_item[ 'order_id' ], $order_ids, true ) ) { $order_ids[] = $booking_list_item[ 'order_id' ]; }

			if( $booking_list_item[ 'booking_type' ] === 'group' ) { $booking_group_ids[] = $booking_list_item[ 'booking_id_raw' ]; }
			else { $booking_ids[] = $booking_list_item[ 'booking_id_raw' ]; }
		}

		if( empty( $users[ $booking_list_item[ 'customer_id' ] ] ) ) { continue; }
		$user = $users[ $booking_list_item[ 'customer_id' ] ];
		if( $user ) {
			if( ! empty( $user->billing_first_name ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'customer_first_name' ] ) ) )	{ $booking_list_items[ $booking_id ][ 'customer_first_name' ] = $user->billing_first_name; }
			if( ! empty( $user->billing_last_name ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'customer_last_name' ] ) ) )	{ $booking_list_items[ $booking_id ][ 'customer_last_name' ] = $user->billing_last_name; }
			if( ! empty( $user->billing_email ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'customer_email' ] ) ) )			{ $booking_list_items[ $booking_id ][ 'customer_email' ] = $user->billing_email; }
			if( ! empty( $user->billing_phone ) && ( $booking_list_item[ 'order_id' ] || empty( $booking_list_item[ 'customer_phone' ] ) ) )			{ $booking_list_items[ $booking_id ][ 'customer_phone' ] = $user->billing_phone; }
		}
	}

	// Get order item data
	$order_items = bookacti_wc_get_order_items_by_bookings( $booking_ids, $booking_group_ids );
	if( ! $order_items ) { return $booking_list_items; }

	// Get WC orders by order item id
	$orders = array();
	$orders_array = $order_ids ? wc_get_orders( array( 'post__in' => $order_ids, 'limit' => -1 ) ) : array();
	foreach( $orders_array as $order ) {
		$order_id = $order->get_id();
		$orders[ $order_id ] = $order;
	}

	// Get WC refund actions
	$wc_refund_actions = array_keys( bookacti_wc_get_refund_actions() );

	// Add order item data to the booking list
	foreach( $order_items as $order_item_id => $order_item ) {
		$order_item_bookings_ids = bookacti_wc_format_order_item_bookings_ids( $order_item );
		if( ! $order_item_bookings_ids ) { continue; }
		
		foreach( $order_item_bookings_ids as $order_item_booking_id ) {
			$booking_id = 0;
			$booking_object = new stdClass();
			
			// Booking group
			if( $order_item_booking_id[ 'type' ] === 'group' ) {
				$booking_group_id = $order_item_booking_id[ 'id' ];
				if( ! isset( $displayed_groups[ $booking_group_id ] ) ) { continue; }
				$booking_id = $displayed_groups[ $booking_group_id ];
				if( ! empty( $booking_groups[ $booking_group_id ] ) ) { $booking_object = $booking_groups[ $booking_group_id ]; }
			}

			// Single booking
			else if( $order_item_booking_id[ 'type' ] === 'single' ) {
				$booking_id = $order_item_booking_id[ 'id' ];
				if( ! empty( $bookings[ $booking_id ] ) ) { $booking_object = $bookings[ $booking_id ]; }
			}

			if( ! isset( $booking_list_items[ $booking_id ] ) ) { continue; }

			// Fill product column
			$product_id = intval( $order_item->get_product_id() );
			$product_title = $order_item->get_name();
			$booking_list_items[ $booking_id ][ 'product_id' ]		= $product_id ? $product_id : '';
			$booking_list_items[ $booking_id ][ 'product_title' ]	= $product_title ? apply_filters( 'bookacti_translate_text', $product_title ) : '';

			// Fill price column
			$order_item_total = $order_item->get_total() + $order_item->get_total_tax();
			$booking_list_items[ $booking_id ][ 'price' ] = apply_filters( 'bookacti_user_booking_list_order_item_price', wc_price( $order_item_total ), $order_item, $booking_list_items[ $booking_id ], $booking_object, $order_item_booking_id[ 'type' ], $filters );
			
			
			// Try to find a coupon code
			if( ! empty( $filters[ 'fetch_meta' ] ) ) {
				$meta = $order_item_booking_id[ 'type' ] === 'group' && isset( $booking_groups[ $order_item_booking_id[ 'id' ] ] ) ? $booking_groups[ $order_item_booking_id[ 'id' ] ] : $bookings[ $booking_id ];
				$refunds = ! empty( $meta->refunds ) ? maybe_unserialize( $meta->refunds ) : array();
			} else {
				$object_type = $order_item_booking_id[ 'type' ] === 'group' ? 'booking_group' : 'booking';
				$refunds = bookacti_get_metadata( $object_type, $order_item_booking_id[ 'id' ], 'refunds', true );
			}
			
			$coupon_code = '';
			$refunds = is_array( $refunds ) ? bookacti_format_booking_refunds( $refunds, $order_item_booking_id[ 'id' ], $order_item_booking_id[ 'type' ] ) : array();
			foreach( $refunds as $refund ) {
				if( isset( $refund[ 'coupon' ] ) ) { $coupon_code = $refund[ 'coupon' ]; break; }
			}
			
			// Backward compatibility
			if( ! $coupon_code && ! empty( $order_item[ 'bookacti_refund_coupon' ] ) ) { $coupon_code = $order_item[ 'bookacti_refund_coupon' ]; }
			
			// Specify refund method in status column
			if( $bookings[ $booking_id ]->state === 'refunded' && $coupon_code && in_array( 'status', $columns, true ) ) {
				// Check if the coupon code is valid
				$coupon_valid = $coupon_code ? bookacti_wc_is_coupon_code_valid( $coupon_code ) : true;
				$coupon_class = is_wp_error( $coupon_valid ) ? 'bookacti-refund-coupon-not-valid bookacti-refund-coupon-error-' . esc_attr( $coupon_valid->get_error_code() ) : 'bookacti-refund-coupon-valid';
				$coupon_error_label = is_wp_error( $coupon_valid ) ? $coupon_valid->get_error_message() : '';
				
				$coupon_label = sprintf( esc_html__( 'Refunded with coupon %s', 'booking-activities' ), '<span class="bookacti-refund-coupon-code ' . esc_attr( $coupon_class ) . '">' . strtoupper( $coupon_code ) . '</span>' );
				$coupon_tip = $coupon_error_label ? $coupon_label . '<br/>' . $coupon_error_label : $coupon_label;
				$booking_list_items[ $booking_id ][ 'status' ] = '<span class="bookacti-booking-state bookacti-booking-state-bad bookacti-booking-state-refunded bookacti-converted-to-coupon bookacti-tip" data-booking-state="refunded" data-tip="' . esc_attr( $coupon_tip ) . '" >' . $coupon_label . '</span>';
			}

			// Filter refund actions
			$order_id = $order_item->get_order_id();
			if( ! empty( $booking_list_items[ $booking_id ][ 'actions' ][ 'refund' ] ) && ! empty( $orders[ $order_id ] ) ) {
				$order		= $orders[ $order_id ];
				$is_paid	= $order->get_date_paid( 'edit' );

				if( $order->get_status() !== 'pending' && $is_paid && $order_item_total > 0 ) {
					$booking_list_items[ $booking_id ][ 'refund_actions' ] = array_intersect_key( $booking_list_items[ $booking_id ][ 'refund_actions' ], array_flip( $wc_refund_actions ) );
				}
			}
		}
	}

	return apply_filters( 'bookacti_user_booking_list_items_with_wc_data', $booking_list_items, $bookings, $booking_groups, $displayed_groups, $users, $filters, $columns, $orders, $order_items );
}
add_filter( 'bookacti_user_booking_list_items', 'bookacti_add_wc_data_to_user_booking_list_items', 10, 8 );




// MY ACCOUNT

/**
 * Register the "Bookings" endpoint to use it on My Account
 * @since 1.7.16
 */
function bookacti_add_bookings_endpoint() {
	add_rewrite_endpoint( 'bookings', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'bookacti_add_bookings_endpoint', 10 );
add_action( 'bookacti_activate', 'bookacti_add_bookings_endpoint', 10 );


/**
 * Set the Bookings page title in WC account
 * @since 1.8.9
 * @version 1.9.0
 * @global WP_Query $wp_query
 * @param string $title
 * @param int $post_id
 * @return string
 */
function bookacti_wc_account_bookings_page_title( $title, $post_id = null ) {
	global $wp_query;
	$is_endpoint = isset( $wp_query->query_vars[ 'bookings' ] );
	if( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
		$title = esc_html__( 'Bookings', 'booking-activities' );
		remove_filter( 'the_title', 'bookacti_wc_account_menu_items_page_title', 10 );
	}
	return $title;
}
add_filter( 'the_title', 'bookacti_wc_account_bookings_page_title', 10, 2 );


/**
 * Add the "Bookings" tab to the My Account menu
 * @since 1.7.16
 * @param array $tabs
 * @return array
 */
function bookacti_add_bookings_tab_to_my_account_menu( $tabs ) {
	$page_id = intval( bookacti_get_setting_value( 'bookacti_account_settings', 'wc_my_account_bookings_page_id' ) );
	if( $page_id < 0 ) { return $tabs; }

	$inserted = false;
	$new_tabs = array();
	foreach( $tabs as $tab_key => $tab_title ) {
		// Insert the "Bookings" tab before the "Logout" tab
		if( $tab_key === 'customer-logout' && ! $inserted ) {
			$new_tabs[ 'bookings' ] = esc_html__( 'Bookings', 'booking-activities' );
			$inserted = true;
		}

		$new_tabs[ $tab_key ] = $tab_title;

		// Insert the "Bookings" tab after the "Orders" tab
		if( $tab_key === 'orders' && ! $inserted ) {
			$new_tabs[ 'bookings' ] = esc_html__( 'Bookings', 'booking-activities' );
			$inserted = true;
		}
	}

	// Insert the "Bookings" tab at the end if it hasn't been yet
	if( ! $inserted ) {
		$new_tabs[ 'bookings' ] = esc_html__( 'Bookings', 'booking-activities' );
	}

	return $new_tabs;
}
add_filter( 'woocommerce_account_menu_items', 'bookacti_add_bookings_tab_to_my_account_menu', 50 );


/**
 * Display the content of the "Bookings" tab in My Account
 * @since 1.7.16
 * @version 1.8.0
 */
function bookacti_display_my_account_bookings_tab_content() {
	$page_id = intval( bookacti_get_setting_value( 'bookacti_account_settings', 'wc_my_account_bookings_page_id' ) );
	if( $page_id === 0 ) {
		echo do_shortcode( '[bookingactivities_list]' );
	} else if( $page_id > 0 ) {
		$page = get_page( $page_id );
		if( $page && isset( $page->post_content ) ) {
			echo apply_filters( 'the_content', apply_filters( 'bookacti_translate_text', $page->post_content ) );
		}
	}
}
add_action( 'woocommerce_account_bookings_endpoint', 'bookacti_display_my_account_bookings_tab_content', 10 );