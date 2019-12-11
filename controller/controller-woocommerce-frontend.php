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
	 * @param type $translation_array
	 * @return type
	 */
	function bookacti_woocommerce_translation_array( $translation_array ) {
		
		$site_booking_method = bookacti_get_setting_value( 'bookacti_general_settings',	'booking_method' );
		
		$translation_array[ 'expired_min' ]						= esc_html__( 'expired', 'booking-activities' );
		$translation_array[ 'expired' ]							= esc_html__( 'Expired', 'booking-activities' );
		$translation_array[ 'in_cart' ]							= esc_html__( 'In cart', 'booking-activities' );
		$translation_array[ 'days' ]							= esc_html__( 'days', 'booking-activities' );
		$translation_array[ 'day' ]								= esc_html_x( 'day', 'singular of days','booking-activities' );
		$translation_array[ 'error_remove_expired_cart_item' ]	= esc_html__(  'Error occurs while trying to remove expired cart item.', 'booking-activities' );
		$translation_array[ 'error_cart_expired' ]				= esc_html__( 'Your cart has expired.', 'booking-activities' );
		$translation_array[ 'coupon_code' ]						= esc_html__( 'Coupon', 'booking-activities' );
		/* translators: %1$s is the coupon code. Ex: AAB12. */
		$translation_array[ 'advice_coupon_code' ]				= esc_html__( 'The coupon code is %1$s. Use it on your next cart!', 'booking-activities' );
		/* translators: %1$s is the amount of the coupon. Ex: $10. */
		$translation_array[ 'advice_coupon_created' ]			= esc_html__( 'A %1$s coupon has been created. You can use it once for any order at any time.', 'booking-activities' );
		$translation_array[ 'add_product_to_cart_button_text' ]	= esc_html__( 'Add to cart', 'woocommerce' );
		$translation_array[ 'add_booking_to_cart_button_text' ]	= bookacti_get_message( 'booking_form_submit_button' );
		$translation_array[ 'site_booking_method' ]				= $site_booking_method;
		
		return $translation_array;
	}
	add_filter( 'bookacti_translation_array', 'bookacti_woocommerce_translation_array', 10, 1 );

	
	/**
	 * Change 'user_id' of bookings from customer id to user id when he logs in
	 * 
	 * @since 1.0.0
	 * @version 1.4.0
	 * @global WooCommerce $woocommerce
	 * @param string $user_login
	 * @param WP_User $user
	 */
	function bookacti_change_customer_id_to_user_id( $user_login, $user ) {
		
		global $woocommerce;
		$customer_id = $woocommerce->session->get_customer_id();
		
		// Make sure the customer was not logged in (it could be a user switching between two accounts)
		if( ! bookacti_user_id_exists( $customer_id ) ) {
			// update customer id to user id
			bookacti_update_bookings_user_id( $user->ID, $customer_id );
		}
					
		// Update the cart expiration date if the user is logged in
		$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
		if( ! $is_per_product_expiration ) {
			$cart_expiration_date = bookacti_get_cart_expiration_date_per_user( $user->ID );
			update_user_meta( $user->ID, 'bookacti_expiration_cart', $cart_expiration_date );
		}
		
		// Check if user's cart is still valid or change it if necessary (according to min and max bookings restrictions)
		bookacti_update_cart_item_quantity_according_to_booking_restrictions( $user->ID );
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
	 * Add booking forms to single product page (front-end)
	 * @version 1.7.11
	 * @global WC_Product $product
	 */
	function bookacti_add_booking_system_in_single_product_page() {
		global $product;
		
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
		
		/** BACKWARD COMPATIBILITY < 1.5 **/
		if( ! $form_id ) {
			$booking_method			= get_post_meta( $product->get_id(), '_bookacti_booking_method', true );
			$template_id			= get_post_meta( $product->get_id(), '_bookacti_template', true );
			$activity_id			= get_post_meta( $product->get_id(), '_bookacti_activity', true );
			$group_categories		= get_post_meta( $product->get_id(), '_bookacti_group_categories', true );
			$groups_only			= get_post_meta( $product->get_id(), '_bookacti_groups_only', true );
			$groups_single_events	= get_post_meta( $product->get_id(), '_bookacti_groups_single_events', true );

			// Convert 'site' booking methods to actual booking method
			// And make sure the resulting booking method exists
			$available_booking_methods = bookacti_get_available_booking_methods();
			if( ! in_array( $booking_method, array_keys( $available_booking_methods ), true ) ) {
				if( $booking_method === 'site' ) {
					$site_booking_method = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
					if( in_array( $site_booking_method, array_keys( $available_booking_methods ), true ) ) {
						$booking_method = $site_booking_method;
					} else {
						$booking_method = 'calendar';
					}
				} else {
					$booking_method = 'calendar';
				}
			}

			$atts = array( 
				'calendars'				=> is_numeric( $template_id ) ? array( $template_id ) : $template_id,
				'activities'			=> is_numeric( $activity_id ) ? array( $activity_id ) : $activity_id,
				'group_categories'		=> is_numeric( $group_categories ) ? array( $group_categories ) : $group_categories,
				'groups_only'			=> $groups_only === 'yes' ? 1 : 0,
				'groups_single_events'	=> $groups_single_events === 'yes' ? 1 : 0,
				'method'				=> $booking_method,
				'auto_load'				=> $product->is_type( 'variable' ) ? 0 : 1,
				'id'					=> 'bookacti-booking-system-product-' . $product->get_id(),
				'class'					=> 'bookacti-frontend-booking-system bookacti-woocommerce-product-booking-system'
			);
			
			// Format booking system attributes
			$atts = bookacti_format_booking_system_attributes( $atts );
			
			bookacti_get_booking_system( $atts, true );
			return;
		} 
		/** END BACKWARD COMPATIBILITY < 1.5 **/
		
		
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
	 * Remove WC unsupported fields on product pages
	 * @since 1.5.0
	 * @version 1.5.2
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
	
	
	/**
	 * Add cart item data (all sent in one array)
	 * @version 1.7.10
	 * @global array $global_bookacti_wc
	 * @param array $cart_item_data
	 * @param int $product_id
	 * @param int $variation_id
	 * @return array
	 */
	function bookacti_add_item_data( $cart_item_data, $product_id, $variation_id ) {
		if( ! isset( $_POST[ 'bookacti_booking_id' ] ) && ! isset( $_POST[ 'bookacti_booking_group_id' ] ) ) { return $cart_item_data; }
			
		if( ! isset( $cart_item_data[ '_bookacti_options' ] ) ) { $cart_item_data[ '_bookacti_options' ] = array(); }

		// Single event
		if( isset( $_POST[ 'bookacti_booking_id' ] ) ) {
			$booking_id	= intval( $_POST[ 'bookacti_booking_id' ] );
			$event = bookacti_get_booking_event_data( $booking_id );
			$cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ]		= $booking_id;
			$cart_item_data[ '_bookacti_options' ][ 'bookacti_booked_events' ]	= json_encode( array( $event ) );

		// Group of events
		} else {
			$booking_group_id = intval( $_POST[ 'bookacti_booking_group_id' ] );
			$events = bookacti_get_booking_group_events_data( $booking_group_id );
			$cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ]= $booking_group_id;
			$cart_item_data[ '_bookacti_options' ][ 'bookacti_booked_events' ]	= json_encode( $events );
		}
		
		// Add the cart item key to be merged to the cart item data for two reasons: 
		// - identify the cart item to be merged later, 
		// - prevent the WC default merging which consist in increasing the existing cart item quantity
		global $global_bookacti_wc;
		if( ! empty( $global_bookacti_wc[ 'bookacti_merged_cart_item_key' ] ) ) {
			$cart_item_data[ '_bookacti_options' ][ 'bookacti_merged_cart_item_key' ] = sanitize_title_with_dashes( $global_bookacti_wc[ 'bookacti_merged_cart_item_key' ] );
		}
		
		return $cart_item_data;
	}
	add_filter( 'woocommerce_add_cart_item_data', 'bookacti_add_item_data', 10, 3 );

	
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
	 * Add the information as meta data so that it can be seen as part of the order 
	 * (to hide any meta data from the customer just start it with an underscore)
	 * To be used before WC 3.0 only, on woocommerce_add_order_item_meta hook
	 * 
	 * @version 1.5.4
	 * @param int $item_id
	 * @param array $values
	 */
	function bookacti_add_values_to_order_item_meta( $item_id, $values ) {
		if( ! array_key_exists( '_bookacti_options', $values ) ) { return; }
			
		// Single event data
		if( isset( $values['_bookacti_options']['bookacti_booking_id'] ) ) {

			$state = bookacti_get_booking_state( $values['_bookacti_options']['bookacti_booking_id'] );
			wc_add_order_item_meta( $item_id, 'bookacti_booking_id', intval( $values['_bookacti_options']['bookacti_booking_id'] ) );

		// Group of events data
		} else if( isset( $values['_bookacti_options']['bookacti_booking_group_id'] ) ) {

			$state	= bookacti_get_booking_group_state( $values['_bookacti_options']['bookacti_booking_group_id'] );
			wc_add_order_item_meta( $item_id, 'bookacti_booking_group_id', intval( $values['_bookacti_options']['bookacti_booking_group_id'] ) );
		}

		// Common data
		wc_add_order_item_meta( $item_id, 'bookacti_booked_events', $values['_bookacti_options']['bookacti_booked_events'] );
		wc_add_order_item_meta( $item_id, 'bookacti_state', sanitize_title_with_dashes( $state ) );
	}
	
	/**
	 * Add the information as meta data so that it can be seen as part of the order 
	 * (to hide any meta data from the customer just start it with an underscore)
	 * To be used since WC 3.0 instead of bookacti_add_values_to_order_item_meta, on woocommerce_checkout_create_order_line_item hook
	 * 
	 * @since 1.1.0
	 * @version 1.4.3
	 * 
	 * @param WC_Order_Item_Product $item
	 * @param string $cart_item_key
	 * @param array $values
	 * @param WC_Order $order
	 */
	function bookacti_save_order_item_metadata( $item, $cart_item_key, $values, $order ) {
		
		// Do not process non-booking metadata
		if( ! array_key_exists( '_bookacti_options', $values ) ) { return; }
		
		// Single event data
		if( isset( $values['_bookacti_options']['bookacti_booking_id'] ) ) {

			$state = bookacti_get_booking_state( $values['_bookacti_options']['bookacti_booking_id'] );
			$item->add_meta_data( 'bookacti_booking_id', intval( $values['_bookacti_options']['bookacti_booking_id'] ), true );
		
		// Group of events data
		} else if( isset( $values['_bookacti_options']['bookacti_booking_group_id'] ) ) {

			$state	= bookacti_get_booking_group_state( $values['_bookacti_options']['bookacti_booking_group_id'] );
			$item->add_meta_data( 'bookacti_booking_group_id', intval( $values['_bookacti_options']['bookacti_booking_group_id'] ), true );
		}

		// Common data
		$item->add_meta_data( 'bookacti_booked_events', $values['_bookacti_options']['bookacti_booked_events'], true );
		$item->add_meta_data( 'bookacti_state', sanitize_title_with_dashes( $state ), true );
	}
	

	/**
	 * Validate add to cart form and temporarily book the event
	 * @version 1.7.10
	 * @global WooCommerce $woocommerce
	 * @global array $global_bookacti_wc
	 * @param boolean $true
	 * @param int $product_id
	 * @param int $quantity
	 * @return boolean
	 */
	function bookacti_validate_add_to_cart_and_book_temporarily( $true, $product_id, $quantity ) {
		if( ! $true ) { return $true; }
		
		if( isset( $_POST[ 'variation_id' ] ) ) {
			$is_activity = bookacti_product_is_activity( intval( $_POST[ 'variation_id' ] ) );
		} else {
			$is_activity = bookacti_product_is_activity( $product_id );
		}

		if( ! $is_activity ) { return $true; }

		// Check if a group id or an event id + start + end are set
		if( ( empty( $_POST[ 'bookacti_event_id' ] ) && empty( $_POST[ 'bookacti_group_id' ] ) )
			|| ( isset( $_POST[ 'bookacti_group_id' ] ) && ! is_numeric( $_POST[ 'bookacti_group_id' ] ) && $_POST[ 'bookacti_group_id' ] !== 'single' )
			|| ( $_POST[ 'bookacti_group_id' ] === 'single'
				&& (empty( $_POST[ 'bookacti_event_id' ] )
				||	empty( $_POST[ 'bookacti_event_start' ] ) 
				||	empty( $_POST[ 'bookacti_event_end' ] ) ) ) ) {
			wc_add_notice(  __( 'You haven\'t picked any event. Please pick an event first.', 'booking-activities' ), 'error' ); 
			return false;
		}

		global $woocommerce;
		$user_id = $woocommerce->session->get_customer_id();

		if( is_user_logged_in() ) { $user_id = get_current_user_id(); }
		
		// Get product form ID
		$variation_id = isset( $_POST[ 'variation_id' ] ) ? intval( $_POST[ 'variation_id' ] ) : 0;
		if( $variation_id ) {
			$form_id = bookacti_get_product_form_id( $variation_id, true );
		} else {
			$form_id = bookacti_get_product_form_id( $product_id, false );
		}
		
		// Sanitize the variables
		$form_id		= is_numeric( $form_id ) ? intval( $form_id ) : 0;
		$group_id		= is_numeric( $_POST[ 'bookacti_group_id' ] ) ? intval( $_POST[ 'bookacti_group_id' ] ) : 'single';
		$event_id		= intval( $_POST[ 'bookacti_event_id' ] );
		$event_start	= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] );
		$event_end		= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] );
		
		// Check if the form fields are properly filled
		if( $form_id ) {
			$form_fields_validated = bookacti_validate_form_fields( $form_id );
			if( $form_fields_validated[ 'status' ] !== 'success' ) {
				$form_fields_validated[ 'message' ] = is_array( $form_fields_validated[ 'message' ] ) ? implode( '</li><li>', $form_fields_validated[ 'message' ] ) : $form_fields_validated[ 'message' ];
				wc_add_notice( $form_fields_validated[ 'message' ], 'error' );
				return false;
			}
		}
		
		// Check if data are correct before booking
		$response = bookacti_validate_booking_form( $group_id, $event_id, $event_start, $event_end, $quantity, $form_id );
		
		// Display error message
		if( $response[ 'status' ] !== 'success' ) {
			wc_add_notice( $response[ 'message' ], 'error' );
			return false;
		}
		
		global $global_bookacti_wc;
		
		// Book a single event temporarily
		if( $group_id === 'single' ) {
			
			// Book temporarily the event
			$response = bookacti_add_booking_to_cart( $product_id, $variation_id, $user_id, $event_id, $event_start, $event_end, $quantity, $form_id );

			// If the event is booked, add the booking ID to the corresponding hidden field
			if( $response[ 'status' ] === 'success' ) {
				$_POST[ 'bookacti_booking_id' ] = intval( $response[ 'id' ] );
				$global_bookacti_wc[ 'bookacti_merged_cart_item_key' ] = ! empty( $response[ 'merged_cart_item_key' ] ) ? $response[ 'merged_cart_item_key' ] : '';
				return true;
			}

		// Book a groups of events temporarily
		} else if( is_numeric( $group_id ) ) {

			// Book temporarily the group of event
			$response = bookacti_add_booking_group_to_cart( $product_id, $variation_id, $user_id, $group_id, $quantity, $form_id );

			// If the events are booked, add the booking group ID to the corresponding hidden field
			if( $response[ 'status' ] === 'success' ) {
				$_POST[ 'bookacti_booking_group_id' ] = intval( $response[ 'id' ] );
				$global_bookacti_wc[ 'bookacti_merged_cart_item_key' ] = ! empty( $response[ 'merged_cart_item_key' ] ) ? $response[ 'merged_cart_item_key' ] : '';
				return true;
			}
		}

		// Display error message
		if( isset( $response[ 'message' ] ) && $response[ 'message' ] ) { 
			wc_add_notice( $response[ 'message' ], 'error' ); 
		}

		// Return false at this point
		return false;
	}
	add_filter( 'woocommerce_add_to_cart_validation', 'bookacti_validate_add_to_cart_and_book_temporarily', 1000, 3 );
	
	
	/**
	 * If an activity is added to cart with the same booking data (same product, same variation, same booking) as an existing cart item
	 * Merge the old cart items to the new one
	 * @since 1.5.4
	 * @version 1.7.10
	 * @global WooCommerce $woocommerce
	 * @param array $cart_item_data
	 * @param string $cart_item_key
	 * @return array
	 */
	function bookacti_merge_cart_items_with_same_booking_data( $cart_item_data, $cart_item_key ) {
		if( empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ] ) && empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ] ) ) { return $cart_item_data; }
		if( empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_merged_cart_item_key' ] ) ) { return $cart_item_data; }
		
		global $woocommerce;
		
		$old_cart_item_key = $cart_item_data[ '_bookacti_options' ][ 'bookacti_merged_cart_item_key' ];
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

		// Restore the booking (it has been removed while removing the duplicated cart item)
		$restored = false;
		if( ! empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ] ) ) {
			$booking_id = $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ];
			$restored = bookacti_update_booking_state( $booking_id, 'in_cart' );
		} else if( ! empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ] ) ) {
			$booking_id = $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ];
			$restored = bookacti_update_booking_group_state( $booking_id, 'in_cart', 'auto', true );
		}

		if( $restored === false ) {
			do_action( 'bookacti_cart_item_not_merged', $cart_item_data, $old_cart_item );
			$removed = $woocommerce->cart->remove_cart_item( $cart_item_key );
			if( $removed ) {
				do_action( 'bookacti_cart_item_not_merged_removed', $cart_item_data, $old_cart_item );
			}
		} else {
			do_action( 'bookacti_cart_item_merged', $cart_item_data, $old_cart_item );
			// Remove the merged key
			unset( $cart_item_data[ '_bookacti_options' ][ 'bookacti_merged_cart_item_key' ] );
			$cart_item_data[ 'quantity' ] = $new_quantity;
			$cart_item_data = apply_filters( 'bookacti_merged_cart_item_data', $cart_item_data, $old_cart_item );
		}
		
		return $cart_item_data;
	}
	add_filter( 'woocommerce_add_cart_item', 'bookacti_merge_cart_items_with_same_booking_data', 15, 2 );
	
	
	/**
	 * Set the timeout for a product added to cart
	 * @version 1.5.2
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
			
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

		if( ! $is_expiration_active ) { return; }
		
		// If all cart item expire at once, set cart expiration date
		$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
		
		if( $is_per_product_expiration ) { return; }
		
		global $woocommerce;
		$reset_timeout_on_change	= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
		$timeout					= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );
		$cart_expiration_date		= bookacti_get_cart_timeout();
		$expiration_date			= date( 'c', strtotime( '+' . $timeout . ' minutes' ) );
		
		// Reset cart timeout and its items timeout
		if(	$reset_timeout_on_change 
		||	is_null ( $cart_expiration_date ) 
		||	strtotime( $cart_expiration_date ) <= time()
		||  $woocommerce->cart->get_cart_contents_count() === $quantity ) {

			// Reset global cart timeout
			bookacti_set_cart_timeout( $expiration_date );

			// If there are others items in cart, we need to change their expiration dates
			if( $reset_timeout_on_change ) {
				bookacti_reset_cart_expiration_dates( $expiration_date );
			}
		}
	}
	add_action( 'woocommerce_add_to_cart', 'bookacti_set_timeout_to_cart_item', 30, 6 ); 
	
	
	/**
	 * Load filters depending on WC version
	 * Used for WOOCOMMERCE 3.0.0 backward compatibility
	 * 
	 * @since 1.0.4
	 * @version 1.1.0
	 */
	function bookacti_load_filters_with_backward_compatibility() {
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			add_filter( 'woocommerce_get_stock_html', 'bookacti_dont_display_instock_in_variation', 10, 2 );
			add_filter( 'wc_add_to_cart_message_html', 'bookacti_add_to_cart_message_html', 10, 2 );
			add_action( 'woocommerce_checkout_create_order_line_item', 'bookacti_save_order_item_metadata', 10, 4 );
		} else {
			add_filter( 'woocommerce_stock_html', 'bookacti_deprecated_dont_display_instock_in_variation', 10, 3 );
			add_filter( 'wc_add_to_cart_message', 'bookacti_deprecated_add_to_cart_message_html', 10, 2 );
			add_action( 'woocommerce_add_order_item_meta', 'bookacti_add_values_to_order_item_meta', 10, 2 );
		}
	}
	add_action( 'woocommerce_loaded', 'bookacti_load_filters_with_backward_compatibility' );
	
	
	/**
	 * Notice the user that his activity has been reserved and will expire, along with the add to cart confirmation
	 * @since 1.0.4
	 * @version 1.5.2
	 * @param string $message
	 * @param array $products
	 * @return string
	 */
	function bookacti_add_to_cart_message_html( $message, $products ) {
		
		// If no activity has been added to cart, return the default message
		if( ! isset( $_POST[ 'bookacti_booking_id' ] ) 
		&&  ! isset( $_POST[ 'bookacti_booking_group_id' ] ) ) {
			return $message;
		}
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

		if( ! $is_expiration_active ) { return $message; }
		
		// Check if there is at least one activity 
		$total_added_qty	= 0;
		$is_activity		= false;		
		foreach ( $products as $product_id => $qty ) {
			// Totalize added qty 
			$total_added_qty += $qty;

			if( $is_activity ) { continue; }
			
			$is_activity = bookacti_product_is_activity( $product_id );
		}

		if( ! $is_activity ) { return $message; }

		$temporary_book_message = bookacti_get_message( 'temporary_booking_success' );

		// If no message, return WC message only
		if( ! $temporary_book_message ) { return $message; }

		// If the message has no countdown, return the basic messages
		if( strpos( $temporary_book_message, '{time}' ) === false ) {
			return $message . '<br/>' . $temporary_book_message;
		}
		
		// Single event
		if( isset( $_POST[ 'bookacti_booking_id' ] ) ) {
			$booking_id		= intval( $_POST[ 'bookacti_booking_id' ] );
			$booking_type	= 'single';
		// Group of events
		} else if( isset( $_POST[ 'bookacti_booking_group_id' ] ) ) {
			$booking_id		= intval( $_POST[ 'bookacti_booking_group_id' ] );
			$booking_type	= 'group';
		}
		
		$expiration_date	= bookacti_get_new_booking_expiration_date( $booking_id, $booking_type, $total_added_qty );
		$remaining_time		= bookacti_get_formatted_time_before_expiration( $expiration_date );
		
		$message .= '<br/>' . str_replace( '{time}', $remaining_time, $temporary_book_message );
		
		return $message;
	}
	
	
	/**
	 * Notice the user that an activity has been reserved and will expire, along with the add to cart confirmation
	 *
	 * Only use it for WOOCOMMERCE 3.0.0 backward compatibility 
	 * 
	 * @since 1.0.4
	 * 
	 * @param string $message
	 * @param int $product_id
	 * @return string
	 */
	function bookacti_deprecated_add_to_cart_message_html( $message, $product_id ) {
		$products = array( $product_id => 1 );
		return bookacti_add_to_cart_message_html( $message, $products );
	}
	
	
	/**
	 * Do not display "In stock" for activities in variable product pages
	 * 
	 * Only use it for WOOCOMMERCE 3.0.0 backward compatibility 
	 * 
	 * @since 1.0.4
	 * 
	 * @param string $availability_html
	 * @param string $availability
	 * @param WC_Product $variation
	 * @return string
	 */
	function bookacti_deprecated_dont_display_instock_in_variation( $availability_html, $availability, $variation ) {
		if( $variation->stock_status === 'instock' ) {
			$is_activity = get_post_meta( $variation->variation_id, 'bookacti_variable_is_activity', true ) === 'yes';
			if( $is_activity ) {
				$availability_html = '';
			}
		}
		return $availability_html;
	}
	
	
	/**
	 * Do not display "In stock" for activities in variable product pages
	 *
	 * @since 1.0.4
	 * 
	 * @param string $availability_html
	 * @param WC_Product $variation
	 * @return string
	 */
	function bookacti_dont_display_instock_in_variation( $availability_html, $variation ) {
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			if( $variation->get_stock_status() === 'instock' ) {
				$is_activity = get_post_meta( $variation->get_id(), 'bookacti_variable_is_activity', true ) === 'yes';
				if( $is_activity ) {
					$availability_html = '';
				}
			}
			return $availability_html;
		} else {
			return bookacti_deprecated_dont_display_instock_in_variation( $availability_html, null, $variation );
		}
	}
			

	
// CART & CHECKOUT

	/**
	 * Add the timeout to cart and checkout
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @global WooCommerce $woocommerce
	 */
	function bookacti_add_timeout_to_cart() { 
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

		if( $is_expiration_active ) {
		
			$is_per_product_expiration = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );

			if( ! $is_per_product_expiration ) {
				
				//Check if cart contains at least one item with the 'in_cart' state
				$cart_contains_expirables_items = false;
				
				global $woocommerce;
				$cart_contents = $woocommerce->cart->get_cart();
				if( ! empty( $cart_contents ) ) {

					$cart_keys = array_keys( $cart_contents );
					
					foreach ( $cart_keys as $key ) {
						// Single event
						if( isset( $cart_contents[$key]['_bookacti_options'] ) && isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] ) ) {
							$booking_id = $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'];
							if( ! is_null( $booking_id ) ) {

								$is_in_cart_state = bookacti_get_booking_state( $booking_id ) === 'in_cart';

								if( $is_in_cart_state ) {
									$cart_contains_expirables_items = true;
									break;
								}
							}
						
						// Group of events
						} else if( isset( $cart_contents[$key]['_bookacti_options'] ) && isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_group_id'] ) ) {
							$booking_group_id = $cart_contents[$key]['_bookacti_options']['bookacti_booking_group_id'];
							if( ! is_null( $booking_group_id ) ) {

								$is_in_cart_state = bookacti_get_booking_group_state( $booking_group_id ) === 'in_cart';

								if( $is_in_cart_state ) {
									$cart_contains_expirables_items = true;
									break;
								}
							}
						}
					}
				}
				
				if( $cart_contains_expirables_items ) {
					
					$expiration_date = bookacti_get_cart_timeout();
					
					if( strtotime( $expiration_date ) > time() ) {

						$timeout = '<div class="bookacti-cart-expiration-container woocommerce-info">' . bookacti_get_message( 'cart_countdown' ) . '</div>';
						$timeout = str_replace( '{countdown}', '<span class="bookacti-countdown bookacti-cart-expiration" data-expiration-date="' . esc_attr( $expiration_date ) . '" ></span>', $timeout );
						
						echo $timeout;
					}
				} else {
					bookacti_set_cart_timeout( null );
				}
			}
		}
	}
	add_action( 'woocommerce_before_cart', 'bookacti_add_timeout_to_cart', 10, 0 );
	add_action( 'woocommerce_checkout_before_order_review', 'bookacti_add_timeout_to_cart', 10, 0 );

	
	/**
	 * Add the timeout to each cart item
	 * 
	 * @since 1.0.0
	 * @ersion 1.1.0
	 * 
	 * @global WooCommerce $woocommerce
	 * @param string $sprintf
	 * @param string $cart_item_key
	 * @return string
	 */
	function bookacti_add_timeout_to_cart_item( $sprintf, $cart_item_key ) { 
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
		
		if( $is_expiration_active ) {
		
			global $woocommerce;
			$item = $woocommerce->cart->get_cart_item( $cart_item_key );
			
			if( isset( $item['_bookacti_options'] ) 
			&&  (  ( isset( $item['_bookacti_options']['bookacti_booking_id'] )			&& ! empty( $item['_bookacti_options']['bookacti_booking_id'] ) )
				|| ( isset( $item['_bookacti_options']['bookacti_booking_group_id'] )	&& ! empty( $item['_bookacti_options']['bookacti_booking_group_id'] ) )
				) 
			  ) {
					
					$timeout = bookacti_get_cart_item_timeout( $cart_item_key );
					
					$base = "<div class='bookacti-remove-cart-item-container'>" . $sprintf . "</div>";
					return $base . $timeout; 
			}
		}
		
		return $sprintf;
	}
	add_filter( 'woocommerce_cart_item_remove_link', 'bookacti_add_timeout_to_cart_item', 10, 2 );
	
	
	/**
	 * Delete cart items if they are expired (trigger on cart, on checkout, on mini-cart)
	 * @version 1.7.10
	 * @global WooCommerce $woocommerce
	 */
	function bookacti_remove_expired_product_from_cart() {
		// Return if not frontend
		if( is_admin() ) { return; }
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
		
		// Return if expiration is not active
		if( ! $is_expiration_active ) { return; }

		global $woocommerce;
		
		// Return if woocommerce is not instanciated
		if( empty( $woocommerce ) ) { return; }
		
		// Return if cart is null
		if( empty( $woocommerce->cart ) ) { return; }
		
		$cart_contents = $woocommerce->cart->get_cart();
		
		// Return if cart is already empty
		if( empty( $cart_contents ) ) { return; }

		// Check if each cart item has expired, and if so, reduce its quantity to 0 (delete it)
		$nb_deleted_cart_item = 0;
		$cart_keys = array_keys( $cart_contents );
		foreach( $cart_contents as $cart_item_key => $cart_item ) {
			if( isset( $cart_item['_bookacti_options'] ) ) {
				// Single event
				if( isset( $cart_item['_bookacti_options']['bookacti_booking_id'] ) ) {
					$booking_id = $cart_item['_bookacti_options']['bookacti_booking_id'];
					if( ! empty( $booking_id ) ) {
						// Check if the booking related to the cart item has expired
						$is_expired = bookacti_is_expired_booking( $booking_id );
					}

				// Group of events
				} else if( isset( $cart_item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
					$booking_group_id = $cart_item['_bookacti_options']['bookacti_booking_group_id'];
					if( ! empty( $booking_group_id ) ) {
						// Check if the bookings related to the cart item have expired
						$is_expired = bookacti_is_expired_booking_group( $booking_group_id );
					}
				}

				if( $is_expired ) {
					// Remove the cart item
					do_action( 'bookacti_cart_item_expired', $cart_item );
					$is_deleted = $woocommerce->cart->remove_cart_item( $cart_item_key );
					if( $is_deleted ) {
						do_action( 'bookacti_expired_cart_item_removed', $cart_item );
						$nb_deleted_cart_item++;
					}
				}
			}
		}

		// Display feedback to tell user that (part of) his cart has expired
		if( $nb_deleted_cart_item > 0 ) {
			/* translators: %d is a variable number of products */
			$message = sprintf( _n(	'%d product has expired and has been automatically removed from cart.', 
									'%d products have expired and have been automatically removed from cart.', 
									$nb_deleted_cart_item, 
									'booking-activities' ), $nb_deleted_cart_item );

			// Display feedback
			if( ! wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
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
	 * @version 1.7.11
	 * @param int $new_quantity
	 * @param string $cart_item_key
	 */
	function bookacti_update_quantity_in_cart( $new_quantity, $cart_item_key ) { 
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		$old_quantity = $item[ 'quantity' ];
		
		if( ! isset( $item['_bookacti_options'] ) || $new_quantity === $old_quantity ) { return $new_quantity; }
		
		$is_in_cart = false;
		$restore_qty = false;
		
		// Single event
		if( ! empty( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
			$booking_id		= $item['_bookacti_options']['bookacti_booking_id'];
			$booking_type	= 'single';
			$is_in_cart		= bookacti_get_booking_state( $booking_id ) === 'in_cart';
			
		// Group of events
		} else if( ! empty( $item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
			$booking_id		= $item['_bookacti_options']['bookacti_booking_group_id'];
			$booking_type	= 'group';
			$is_in_cart		= bookacti_get_booking_group_state( $booking_id ) === 'in_cart';
		}
		
		if( $is_in_cart ) {
			$response = $booking_type === 'group' ? bookacti_controller_update_booking_group_quantity( $booking_id, $new_quantity ) : bookacti_controller_update_booking_quantity( $booking_id, $new_quantity );
			while( $response[ 'status' ] === 'failed' && $response[ 'error' ] === 'qty_sup_to_avail' ) {
				// Availability cannot be <= 0, else the error code would be "no_availability"
				$new_quantity = intval( $response[ 'availability' ] );
				$response = $booking_type === 'group' ? bookacti_controller_update_booking_group_quantity( $booking_id, $new_quantity ) : bookacti_controller_update_booking_quantity( $booking_id, $new_quantity );
			}
			if( $response[ 'status' ] !== 'success' ) { $restore_qty = true; }
		}
		
		// If the product is not "in_cart", it means that the order in already in process (maybe waiting for payment)
		else {
			$restore_qty = true;
			wc_add_notice( esc_html__( 'You can\'t update quantity since this product is temporarily booked on an order pending payment. Please, first cancel the order or remove this product from cart.', 'booking-activities' ), 'error' );
		}
		
		return $restore_qty ? $old_quantity : $new_quantity;
	}
	add_filter( 'woocommerce_stock_amount_cart_item', 'bookacti_update_quantity_in_cart', 20, 2 ); 
	
	
	/**
	 * Remove in_cart bookings when cart items are removed from cart
	 * @version 1.5.8
	 * @global WooCommerce $woocommerce
	 * @param string $cart_item_key
	 * @param WC_Cart $cart
	 */
	function bookacti_remove_bookings_of_removed_cart_item( $cart_item_key, $cart ) { 
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		bookacti_remove_cart_item_bookings( $item );
	}
	add_action( 'woocommerce_remove_cart_item', 'bookacti_remove_bookings_of_removed_cart_item', 10, 2 ); 
	
	
	/**
	 * Remove corrupted cart items bookings when they are removed from cart
	 * @since 1.5.8
	 * @global WooCommerce $woocommerce
	 * @param string $cart_item_key
	 * @param array $item
	 */
	function bookacti_remove_bookings_of_corrupted_cart_items( $cart_item_key, $item ) {
		bookacti_remove_cart_item_bookings( $item );
	}
	add_action( 'woocommerce_remove_cart_item_from_session', 'bookacti_remove_bookings_of_corrupted_cart_items', 10, 2 );

	
	/**
	 * Remove cart item bookings
	 * @since 1.5.8
	 * @param array $item
	 */
	function bookacti_remove_cart_item_bookings( $item ) {
		if( ! isset( $item['_bookacti_options'] ) ) { return; }
		
		// Single event
		if( ! empty( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
			$booking_id = $item['_bookacti_options']['bookacti_booking_id'];
			$is_in_cart = bookacti_get_booking_state( $booking_id ) === 'in_cart';
			if( $is_in_cart ) {
				bookacti_update_booking_quantity( $booking_id, 0 );
			}

		// Group of events
		} else if( ! empty( $item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
			$booking_group_id = $item['_bookacti_options']['bookacti_booking_group_id'];
			$is_in_cart = bookacti_get_booking_group_state( $booking_group_id ) === 'in_cart';
			if( $is_in_cart ) {
				bookacti_update_booking_group_quantity( $booking_group_id, 0 );
			}
		}
	}
	
	 
	/**
	 * Restore the booking if user change his mind after deleting one
	 * @version 1.7.10
	 * @global WooCommerce $woocommerce
	 * @param string $cart_item_key
	 */
	function bookacti_restore_bookings_of_removed_cart_item( $cart_item_key ) { 
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		
		if( ! isset( $item[ '_bookacti_options' ] ) ) { return false; }
		
		$quantity = $item[ 'quantity' ];
		$is_removed = false;
		$init_quantity = $quantity;
		
		// Single event
		if( ! empty( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
			$booking_id		= $item['_bookacti_options']['bookacti_booking_id'];
			$booking_type	= 'single';
			$is_removed		= bookacti_get_booking_state( $booking_id ) === 'removed';

		// Group of events
		} else if( ! empty( $item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
			$booking_id		= $item['_bookacti_options']['bookacti_booking_group_id'];
			$booking_type	= 'group';
			$is_removed		= bookacti_get_booking_group_state( $booking_id ) === 'removed';
		}
		
		if( $is_removed ) {
			$response = $booking_type === 'group' ? bookacti_controller_update_booking_group_quantity( $booking_id, $quantity ) : bookacti_controller_update_booking_quantity( $booking_id, $quantity );

			while( $response[ 'status' ] === 'failed' && $response[ 'error' ] === 'qty_sup_to_avail' ) {
				// Availability cannot be <= 0, else the error code would be "no_availability"
				$quantity = intval( $response[ 'availability' ] );
				$response = $booking_type === 'group' ? bookacti_controller_update_booking_group_quantity( $booking_id, $quantity ) : bookacti_controller_update_booking_quantity( $booking_id, $quantity );
			}
			
			$is_restored = apply_filters( 'bookacti_restore_bookings_of_restored_cart_item', in_array( $response[ 'status' ], array( 'success', 'no_change' ), true ), $cart_item_key, $quantity );
			
			if( ! $is_restored ) {
				do_action( 'bookacti_cart_item_not_restored', $item, $quantity );
				$removed = $woocommerce->cart->remove_cart_item( $cart_item_key );
				if( $removed ) {
					do_action( 'bookacti_cart_item_not_restored_removed', $item, $quantity );
				}
			} else {
				if( $quantity !== $init_quantity ) { $woocommerce->cart->set_quantity( $cart_item_key, $quantity, true ); }
				do_action( 'bookacti_cart_item_restored', $item, $quantity );
			}
		}
	}
	add_action( 'woocommerce_cart_item_restored', 'bookacti_restore_bookings_of_removed_cart_item', 10, 1 );

	
	/**
	 * Tell how to display the custom metadata in cart and checkout
	 * @version 1.1.0
	 * @param array $item_data
	 * @param array $cart_item_data
	 * @return array
	 */
	function bookacti_get_item_data( $item_data, $cart_item_data ) {
		
		if( isset( $cart_item_data[ '_bookacti_options' ] ) ) {
			
			// Single event
			if( ( isset( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ] ) && ! empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_id' ] ) )
			||  ( isset( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ] ) && ! empty( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booking_group_id' ] ) ) ) {
				$events	= json_decode( $cart_item_data[ '_bookacti_options' ][ 'bookacti_booked_events' ] );
				$events_list = bookacti_get_formatted_booking_events_list( $events );
				
				$item_data[] = array( 
					'key' => _n( 'Booked event', 'Booked events', count( $events ), 'booking-activities' ), 
					'value' => $events_list );
			}
		}

		return $item_data;
	}
	add_filter( 'woocommerce_get_item_data', 'bookacti_get_item_data', 10, 2 );

	
	/**
	 * Format label of custom metadata in cart and checkout
	 * @version 1.1.0
	 * @param string $label
	 * @param string $name
	 * @return string
	 */
	function bookacti_define_label_of_item_data( $label, $name ) {
		
		if( $label === '_bookacti_booking_id' 
		||  $label === 'bookacti_booking_id' )			{ $label = __( 'Booking number', 'booking-activities' ); }
		if( $label === '_bookacti_booking_group_id' 
		||  $label === 'bookacti_booking_group_id' )	{ $label = __( 'Booking group number', 'booking-activities' ); }
		if( $label === 'bookacti_booked_events' )		{ $label = __( 'Booked events', 'booking-activities' ); }
		if( $label === 'bookacti_state' )				{ $label = __( 'Status', 'booking-activities' ); }
		if( $label === '_bookacti_refund_method' )		{ $label = __( 'Refund method', 'booking-activities' ); }
		if( $label === 'bookacti_refund_coupon' )		{ $label = __( 'Coupon code', 'booking-activities' ); }
		
		// Deprecated data
		if( $label === '_bookacti_event_id' )			{ $label = __( 'Event ID', 'booking-activities' ); }
		if( $label === 'bookacti_event_start' )			{ $label = __( 'Start', 'booking-activities' ); }
		if( $label === 'bookacti_event_end' )			{ $label = __( 'End', 'booking-activities' ); }
		
		return $label;
	}
	add_filter( 'woocommerce_attribute_label', 'bookacti_define_label_of_item_data', 10, 2 );
	
	
	/**
	 * Format value of custom metadata in order review
	 * @version 1.5.4
	 * @param array $formatted_meta
	 * @param WC_Order_Item_Meta $order_item_meta
	 * @return array
	 */
	function bookacti_format_order_meta_data( $formatted_meta, $order_item_meta ) {
		foreach( $formatted_meta as $key => $meta ) {
			if( substr( $meta[ 'key' ], 0, 9 ) !== 'bookacti_' ) { continue; }
			
			$value = $meta[ 'value' ];
			
			// Booking (group) state
			if( $meta[ 'key' ] === 'bookacti_state' ) {
				$formatted_meta[ $key ][ 'value' ] = bookacti_format_booking_state( $value );
			} 
			
			// Booked events
			else if( $meta[ 'key' ] === 'bookacti_booked_events' ) {
				$events	= json_decode( $value );
				$formatted_meta[ $key ][ 'value' ] = bookacti_get_formatted_booking_events_list( $events );
			} 
			
			// Deprecated data
			// Event start and end
			else if( $meta[ 'key' ] === 'bookacti_event_start' || $meta[ 'key' ] === 'bookacti_event_end' ) {
				$formatted_meta[ $key ][ 'value' ] = bookacti_format_datetime( $value );
			}
		}
		return $formatted_meta;
	}
	add_filter( 'woocommerce_order_items_meta_get_formatted', 'bookacti_format_order_meta_data', 10, 2 );
	
		
	/**
	 * Format order item mata values in order received page
	 * Must be used since WC 3.0.0
	 * @since 1.0.4
	 * @version 1.5.4
	 * @param string $html
	 * @param WC_Item $item
	 * @param array $args
	 * @return string
	 */
	function bookacti_format_order_item_meta( $formatted_meta, $item ) {
		foreach( $formatted_meta as $meta_id => $meta ) {
			if( substr( $meta->key, 0, 9 ) !== 'bookacti_' ) { continue; }
			
			// Format booking id
			if( $meta->key === 'bookacti_booking_id' || $meta->key === 'bookacti_booking_group_id' ) {
				$meta->display_value = intval( $meta->value );
			}
			
			// Format booking state
			else if( $meta->key === 'bookacti_state' ) {
				$meta->display_value = bookacti_format_booking_state( $meta->value );
			}
			
			// Format event list
			else if( $meta->key === 'bookacti_booked_events' ) {
				$events	= json_decode( $meta->value );
				$meta->display_key = _n( 'Booked event', 'Booked events', count( $events ), 'booking-activities' );
				$meta->display_value = bookacti_get_formatted_booking_events_list( $events );
			}
			
			// Deprecated data
			// Format datetime
			else if( $meta->key === 'bookacti_event_start' 
			||  $meta->key === 'bookacti_event_end' ) {
				$meta->display_value = bookacti_format_datetime( $meta->value );
			}
		}
		return $formatted_meta;
	}
	add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'bookacti_format_order_item_meta', 10, 2 );
	
	
	/**
	 * Add class to activity cart item to identify them
	 * 
	 * @param string $classes
	 * @param array $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	function bookacti_add_class_to_activity_cart_item( $classes, $cart_item, $cart_item_key ) {
		
		if( isset( $cart_item['_bookacti_options'] ) ) {
			// Single booking
			if ( isset( $cart_item['_bookacti_options']['bookacti_booking_id'] ) && ! empty( $cart_item['_bookacti_options']['bookacti_booking_id'] ) ) {
				
				$classes .= ' bookacti-cart-item-activity bookacti-single-booking';
				
			// Group of bookings
			} else if( isset( $cart_item['_bookacti_options']['bookacti_booking_group_id'] ) && ! empty( $cart_item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
				
				$classes .= ' bookacti-cart-item-activity bookacti-booking-group';
			}
		}
		
		return $classes;
	}
	add_filter( 'woocommerce_cart_item_class', 'bookacti_add_class_to_activity_cart_item', 10, 3 );
	
	
	/**
	 * Add class to activity order item to identify them on order received page
	 * @since 1.1.0
	 * @version 1.5.8
	 * @param string $classes
	 * @param WC_Order_Item $item
	 * @param WC_Order $order
	 * @return string
	 */
	function bookacti_add_class_to_activity_order_item( $classes, $item, $order ) {
		
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$booking_id = wc_get_order_item_meta( $item->get_id(), 'bookacti_booking_id', true );
			if( $booking_id ) {
				$classes .= ' bookacti-order-item-activity bookacti-single-booking';
			} else if( wc_get_order_item_meta( $item->get_id(), 'bookacti_booking_group_id', true ) ) {
				$classes .= ' bookacti-order-item-activity bookacti-booking-group';
			}
		} else {
			foreach ( $item[ 'item_meta' ] as $meta_key => $meta_array ) {
				// Single booking
				if( $meta_key === 'bookacti_booking_id' ) {
					$classes .= ' bookacti-order-item-activity bookacti-single-booking';

				// Group of bookings
				} else if( $meta_key === 'bookacti_booking_group_id' ) {
					$classes .= ' bookacti-order-item-activity bookacti-booking-group';
				}
			}
		}
		
		return $classes;
	}
	add_filter( 'woocommerce_order_item_class', 'bookacti_add_class_to_activity_order_item', 10, 3 );
	
	
	
// CHECKOUT
	
	/**
	 * Add the timeout to each cart item in the checkout review
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $product_name
	 * @param array $values
	 * @param string $cart_item_key
	 * @return string
	 */
	function bookacti_add_timeout_to_cart_item_in_checkout_review( $product_name, $values, $cart_item_key ) { 
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
		
		if( $is_expiration_active ) {
		
			if ( array_key_exists( '_bookacti_options', $values ) && is_checkout() ) {
				$timeout = bookacti_get_cart_item_timeout( $cart_item_key );
				return $timeout . $product_name;
			}
		}
		
		return $product_name;
	}
	add_filter( 'woocommerce_cart_item_name', 'bookacti_add_timeout_to_cart_item_in_checkout_review', 10, 3 );
	
	
	/**
	 * Check bookings availability before validating checkout 
	 * in case that "in_cart" state is not active
	 * 
	 * @since  1.3.0
	 * @version 1.7.0
	 * @param  array $posted_data An array of posted data.
	 * @param  WP_Error $errors
	 */
	function bookacti_availability_check_before_checkout( $posted_data, $errors = null ) {
		// Do not make this check if "in_cart" bookings are active, because they already hold their own booking quantity 
		if( in_array( 'in_cart', bookacti_get_active_booking_states(), true ) ) { return; }
		
		// Check availability
		global $woocommerce;
		$cart_contents = $woocommerce->cart->get_cart();
		
		foreach( $cart_contents as $cart_item ) {
			// Initialize on success for non-activity products
			$validated = array( 'status' => 'success' );
			
			// Single event
			if( isset( $cart_item['_bookacti_options'] ) && isset( $cart_item['_bookacti_options']['bookacti_booking_id'] ) ) {
				$booking_id = $cart_item['_bookacti_options']['bookacti_booking_id'];
				if( ! is_null( $booking_id ) ) {
					$event		= json_decode( $cart_item['_bookacti_options']['bookacti_booked_events'] );
					$booking	= bookacti_get_booking_by_id( $booking_id );
					$validated	= bookacti_validate_booking_form( 'single', $event[0]->event_id, $event[0]->event_start, $event[0]->event_end, $cart_item['quantity'], $booking->form_id );
				}

			// Group of events
			} else if( isset( $cart_item['_bookacti_options'] ) && isset( $cart_item['_bookacti_options']['bookacti_booking_group_id'] ) ) {
				$booking_group_id = $cart_item['_bookacti_options']['bookacti_booking_group_id'];
				if( ! is_null( $booking_group_id ) ) {
					$event			= json_decode( $cart_item['_bookacti_options']['bookacti_booked_events'] );
					$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
					$validated		= bookacti_validate_booking_form( $booking_group->event_group_id, $event[0]->event_id, $event[0]->event_start, $event[0]->event_end, $cart_item['quantity'], $booking_group->form_id );
				}
			}
			
			// Display the error and stop checkout processing
			if( $validated[ 'status' ] !== 'success' && isset( $validated[ 'message' ] ) ) {
				if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					if( ! isset( $validated[ 'error' ] ) ) { $validated[ 'error' ] = 'unknown_error'; }
					$errors->add( $validated[ 'error' ], $validated[ 'message' ] );

				// WOOCOMMERCE 3.0.0 backward compatibility
				} else {
					wc_add_notice( $validated[ 'message' ], 'error' );
				}
			}
		}
	}
	add_action( 'woocommerce_after_checkout_validation', 'bookacti_availability_check_before_checkout', 10, 2 );
	
	
	/**
	 * Check availability before paying for a failed order
	 * @since 1.7.13
	 * @param WC_Order $order
	 */
	function bookacti_availability_check_before_pay_action( $order ) {
		$active_states = bookacti_get_active_booking_states();
		$error_nb = 0;
		
		$order_items = $order->get_items();
		foreach( $order_items as $order_item ) {
			// Initialize on success for non-activity products
			$validated = array( 'status' => 'success' );
			
			// Single event
			if( isset( $order_item[ 'bookacti_booking_id' ] ) ) {
				$booking_id = $order_item[ 'bookacti_booking_id' ];
				if( ! is_null( $booking_id ) ) {
					$event		= json_decode( $order_item[ 'bookacti_booked_events' ] );
					$booking	= bookacti_get_booking_by_id( $booking_id );
					if( ! in_array( $booking->state, $active_states, true ) ) {
						$validated	= bookacti_validate_booking_form( 'single', $event[0]->event_id, $event[0]->event_start, $event[0]->event_end, $order_item['quantity'], $booking->form_id );
					}
				}

			// Group of events
			} else if( isset( $order_item[ 'bookacti_booking_group_id' ] ) ) {
				$booking_group_id = $order_item[ 'bookacti_booking_group_id' ];
				if( ! is_null( $booking_group_id ) ) {
					$event			= json_decode( $order_item[ 'bookacti_booked_events' ] );
					$booking_group	= bookacti_get_booking_group_by_id( $booking_group_id );
					if( ! in_array( $booking_group->state, $active_states, true ) ) {
						$validated	= bookacti_validate_booking_form( $booking_group->event_group_id, $event[0]->event_id, $event[0]->event_start, $event[0]->event_end, $order_item['quantity'], $booking_group->form_id );
					}
				}
			}
			
			// Display the error and stop checkout processing
			if( $validated[ 'status' ] !== 'success' ) {
				if( isset( $validated[ 'message' ] ) ) {
					wc_add_notice( $validated[ 'message' ], 'error' );
				}
				++$error_nb;
			}
		}
		
		// If the events are no longer available, prevent submission and feedback user
		if( $error_nb ) {
			wc_add_notice( __( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ), 'error' );
			$checkout_url = $order->get_checkout_payment_url();
			wp_redirect( $checkout_url );
			exit;
		}
	}
	add_action( 'woocommerce_before_pay_action', 'bookacti_availability_check_before_pay_action', 10, 1 );
	
	
	/**
	 * Change order bookings states after the customer validates checkout
	 * @since 1.2.2 (was bookacti_delay_expiration_date_for_payment before)
	 * @version 1.6.0
	 * @param int $order_id
	 * @param array $order_details
	 * @param WC_Order $order
	 */
	function bookacti_change_booking_state_after_checkout( $order_id, $order_details, $order = null ) {
		if( ! $order ) { $order = wc_get_order( $order_id ); }

		// Bind order and user id to the bookings and turn its state to 
		// 'pending' for payment
		// <user defined state> if no payment are required
		if( WC()->cart->needs_payment() ) { 
			$state = 'pending'; 
			$payment_status = 'owed';
			$alert_admin = false; 
		} else { 
			$state = bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' ); 
			$payment_status = bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' ); 
			$alert_admin = true;
		}
		
		bookacti_turn_order_bookings_to( $order, $state, $payment_status, $alert_admin, array( 'is_new_order' => true ) );
		
		// If the user has no account, bind the user data to the bookings
		$user_id = $order->get_user_id( 'edit' );
		if( $user_id && is_int( $user_id ) ) { return; }
		
		bookacti_save_order_data_as_booking_meta( $order );
	}
	add_action( 'woocommerce_checkout_order_processed', 'bookacti_change_booking_state_after_checkout', 10, 3 );
	
	
	
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
		$order_items_data = bookacti_get_order_items_data_by_bookings( $booking_ids, $booking_group_ids );
		if( ! $order_items_data ) { return $booking_list_items; }
		
		// Get WC orders by order item id
		$orders = array();
		$orders_array = wc_get_orders( array( 'post__in' => $order_ids ) );
		foreach( $orders_array as $order ) {
			$order_id = version_compare( WC_VERSION, '3.0.0', '>=' ) ? $order->get_id() : $order->id;
			$orders[ $order_id ] = $order;
		}
		
		// Add order item data to the booking list
		foreach( $order_items_data as $order_item_data ) {
			$booking_meta = array();
			// Booking group
			if( ! empty( $order_item_data->bookacti_booking_group_id ) ) {
				$booking_group_id = $order_item_data->bookacti_booking_group_id;
				if( ! isset( $displayed_groups[ $booking_group_id ] ) ) { continue; }
				$booking_id = $displayed_groups[ $booking_group_id ];
				if( ! empty( $booking_groups[ $booking_group_id ] ) ) { $booking_meta = (array) $booking_groups[ $booking_group_id ]; }
			}
			
			// Single booking
			else if( ! empty( $order_item_data->bookacti_booking_id ) ) {
				$booking_id = $order_item_data->bookacti_booking_id;
				if( ! empty( $bookings[ $booking_id ] ) ) { $booking_meta = (array) $bookings[ $booking_id ]; }
			}
			
			if( ! isset( $booking_list_items[ $booking_id ] ) ) { continue; }
			
			// Fill product column
			$booking_list_items[ $booking_id ][ 'product_id' ]		= ! empty( $order_item_data->_product_id ) ? intval( $order_item_data->_product_id ) : '';
			$booking_list_items[ $booking_id ][ 'product_title' ]	= ! empty( $order_item_data->order_item_name ) ? apply_filters( 'bookacti_translate_text', $order_item_data->order_item_name ) : '';
			
			// Fill price column
			$booking_list_items[ $booking_id ][ 'price' ] = apply_filters( 'bookacti_user_booking_list_order_item_price', wc_price( $order_item_data->_line_total + $order_item_data->_line_tax ), $order_item_data, $booking_list_items[ $booking_id ], $booking_meta, $filters );
			
			// Specify refund method in status column
			if( $bookings[ $booking_id ]->state === 'refunded' && ! empty( $order_item_data->_bookacti_refund_method ) && in_array( 'status', $columns, true ) ) {
				if( $order_item_data->_bookacti_refund_method === 'coupon' ) {
					$coupon_code = ! empty( $order_item_data->bookacti_refund_coupon ) ? $order_item_data->bookacti_refund_coupon : '';
					/* translators: %s is the coupon code used for the refund */
					$coupon_label = sprintf( esc_html__( 'Refunded with coupon %s', 'booking-activities' ), $coupon_code );
					$booking_list_items[ $booking_id ][ 'status' ] = '<span class="bookacti-booking-state bookacti-booking-state-bad bookacti-booking-state-refunded bookacti-converted-to-coupon bookacti-tip" data-booking-state="refunded" data-tip="' . $coupon_label . '" ></span><span class="bookacti-refund-coupon-code bookacti-custom-scrollbar">' . $coupon_code . '</span>';
				}
			}
		}
		
		return apply_filters( 'bookacti_user_booking_list_items_with_wc_data', $booking_list_items, $bookings, $booking_groups, $displayed_groups, $users, $filters, $columns, $orders, $order_items_data );
	}
	add_filter( 'bookacti_user_booking_list_items', 'bookacti_add_wc_data_to_user_booking_list_items', 10, 8 );