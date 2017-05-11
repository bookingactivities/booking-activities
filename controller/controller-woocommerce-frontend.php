<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL
	// Add woocommerce related translations
	add_filter( 'bookacti_translation_array', 'bookacti_woocommerce_translation_array', 10, 1 );
	function bookacti_woocommerce_translation_array( $translation_array ) {
		
		$site_booking_method = bookacti_get_setting_value( 'bookacti_general_settings',	'booking_method' );
		
		$translation_array[ 'expired_min' ]						= esc_html__( 'expired', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'expired' ]							= esc_html__( 'Expired', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'in_cart' ]							= esc_html__( 'In cart', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'days' ]							= esc_html__( 'days', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'day' ]								= esc_html_x( 'day', 'singular of days',BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'error_remove_expired_cart_item' ]	= esc_html__(  'Error occurs while trying to remove expired cart item.', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'error_cart_expired' ]				= __( 'Your cart has expired.', BOOKACTI_PLUGIN_NAME );
		/* translators: %1$s is the coupon code. Ex: AAB12. */
		$translation_array[ 'coupon_code' ]						= __( 'Coupon', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'advice_coupon_code' ]				= __( 'The coupon code is %1$s. Use it on your next cart!', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'advice_coupon_created' ]			= __( 'A %1$s coupon has been created. You can use it once for any order at any time.', BOOKACTI_PLUGIN_NAME );
		$translation_array[ 'site_booking_method' ]				= $site_booking_method;
		
		return $translation_array;
	}


	// Change 'user_id' of bookings from customer id to user id when he logs in
	add_action( 'wp_login', 'bookacti_change_customer_id_to_user_id', 20, 2 );
	function bookacti_change_customer_id_to_user_id( $user_login, $user ) {
		
		global $woocommerce;
		$customer_id = $woocommerce->session->get_customer_id();
		
		// Make sure the customer was not logged in (it could be a user switching from two accounts)
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
	}
	
	
// LOOP PRODUCTS PAGE
	// Add 'activity' class to activity product in the products loop
	add_filter( 'post_class', 'bookacti_add_activity_post_class', 10, 3 );
	function bookacti_add_activity_post_class( $classes, $class, $post_id ) {
		
		$is_activity = bookacti_product_is_activity( $post_id );
		
		if( $is_activity ) { $classes[] = 'bookacti-activity'; }
		
		return $classes;
	}
	
	// Disable AJAX add to cart support for activities
	add_filter( 'woocommerce_product_supports', 'bookacti_disable_ajax_add_to_cart_support_for_activities', 100, 3 );
	function bookacti_disable_ajax_add_to_cart_support_for_activities( $enabled, $feature, $product ){
		if( $feature === 'ajax_add_to_cart' && $enabled ){
			
			if( bookacti_product_is_activity( $product ) ){
				$enabled = false;
			}
		}
		return $enabled;
	}
	
	// Change 'Add to cart' button URL to the single product page URL for activities
	add_filter( 'woocommerce_product_add_to_cart_url', 'bookacti_change_add_to_cart_url_for_activities', 100, 2 );
	function bookacti_change_add_to_cart_url_for_activities( $url, $product ){
		
		if( bookacti_product_is_activity( $product ) ){
			$url = get_permalink( $product->get_id() );
		}
		return $url;
	}
	
	// Change 'Add to cart' text for activities by 'Book'
	add_filter( 'woocommerce_product_add_to_cart_text', 'bookacti_change_add_to_cart_text_for_activities', 100, 2 );
	function bookacti_change_add_to_cart_text_for_activities( $text, $product ){
		
		if( bookacti_product_is_activity( $product ) ){
			$text = __( 'Book', BOOKACTI_PLUGIN_NAME );
		}
		return $text;
	}
	
	
//ADD TO CART (SINGLE PRODUCT PAGE)
	
	// Prevent auto-loading on variable product pages
	add_filter( 'bookacti_booking_system_auto_load', 'bookacti_prevent_auto_load_on_variable_product_page', 10, 6 );
	function bookacti_prevent_auto_load_on_variable_product_page( $auto_load, $templates, $activities, $booking_method, $id, $classes ) {
		global $product;
		if( ! empty( $product ) ) {
			if( $product->is_type( 'variable' ) ) {
				$auto_load = 0;
			}
		}
		
		return $auto_load;
	}
	
	
	//Add fields to single product page (front-end)
	add_action( 'woocommerce_before_add_to_cart_button', 'bookacti_add_booking_system_in_single_product_page', 10, 0 );
	function bookacti_add_booking_system_in_single_product_page() {

		global $product;
		$is_activity = bookacti_product_is_activity( $product );
		
		if( $is_activity ) {

			$booking_method	= get_post_meta( $product->get_id(), '_bookacti_booking_method', true );
			$template_id	= get_post_meta( $product->get_id(), '_bookacti_template', true );
			$activity_id	= get_post_meta( $product->get_id(), '_bookacti_activity', true );
			
			// Convert 'site' booking methods to actual booking method
			// And make sure the resulting booking method exists
			$available_booking_methods = bookacti_get_available_booking_methods();
			if( ! in_array( $booking_method, array_keys ( $available_booking_methods ) ) ) {
				if( $booking_method === 'site' ) {
					$site_booking_method = bookacti_get_setting_value( 'bookacti_general_settings', 'booking_method' );
					if( in_array( $site_booking_method, array_keys ( $available_booking_methods ) ) ) {
						$booking_method = $site_booking_method;
					} else {
						$booking_method = 'calendar';
					}
				} else {
					$booking_method = 'calendar';
				}
			}
			
			bookacti_display_booking_system( array( $template_id ), array( $activity_id ), $booking_method, $product->get_id(), 'bookacti-frontend-booking-system bookacti-woocommerce-product-booking-system' );
		}
	}
	

	//This captures additional posted information (all sent in one array)
	add_filter( 'woocommerce_add_cart_item_data', 'bookacti_add_item_data', 10, 3 );
	function bookacti_add_item_data( $cart_item_data, $product_id, $variation_id ) {
		
		if( isset( $_POST[ 'bookacti_booking_id' ] ) ) {

			$new_value = array();
			$new_value[ '_bookacti_options' ][ 'bookacti_event_id' ]	= intval( $_POST[ 'bookacti_event_id' ] );
			$new_value[ '_bookacti_options' ][ 'bookacti_booking_id' ]	= intval( $_POST[ 'bookacti_booking_id' ] );
			$new_value[ '_bookacti_options' ][ 'bookacti_event_start' ]	= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] );
			$new_value[ '_bookacti_options' ][ 'bookacti_event_end' ]	= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] );

			if( empty( $cart_item_data ) ) {
				return $new_value;
			} else {
				return array_merge( $cart_item_data, $new_value );
			}
		}
		
		return $cart_item_data;
	}


	//This captures the information from the previous function and attaches it to the item.
	add_filter( 'woocommerce_get_cart_item_from_session', 'bookacti_get_cart_items_from_session', 10, 3 );
	function bookacti_get_cart_items_from_session( $item, $values, $key ) {
		
		if ( array_key_exists( '_bookacti_options', $values ) ) {
			$item[ '_bookacti_options' ] = $values[ '_bookacti_options' ];
		}
		
		return $item;
	}
	
	
	//This add the information as meta data so that it can be seen as part of the order 
	//(to hide any meta data from the customer just start it with an underscore)
	add_action( 'woocommerce_add_order_item_meta', 'bookacti_add_values_to_order_item_meta', 10, 2 );
	function bookacti_add_values_to_order_item_meta( $item_id, $values ) {
		
		if ( array_key_exists( '_bookacti_options', $values ) ) {
			
			$state = bookacti_get_booking_state( $values['_bookacti_options']['bookacti_booking_id'] );
			
			wc_add_order_item_meta( $item_id, '_bookacti_event_id',		$values['_bookacti_options']['bookacti_event_id'] );
			wc_add_order_item_meta( $item_id, 'bookacti_booking_id',	$values['_bookacti_options']['bookacti_booking_id'] );
			wc_add_order_item_meta( $item_id, 'bookacti_event_start',	$values['_bookacti_options']['bookacti_event_start'] );
			wc_add_order_item_meta( $item_id, 'bookacti_event_end',		$values['_bookacti_options']['bookacti_event_end'] );
			wc_add_order_item_meta( $item_id, 'bookacti_state',			$state );
		}
	}
	

	// Validate add to cart form and temporarily book the event
	add_filter( 'woocommerce_add_to_cart_validation', 'bookacti_validate_add_to_cart_and_book_temporarily', 10, 3 );
	function bookacti_validate_add_to_cart_and_book_temporarily( $true, $product_id, $quantity ) {
		
		if( $true ) {
			if( isset( $_POST[ 'variation_id' ] ) ) {
				$is_activity = bookacti_product_is_activity( intval( $_POST[ 'variation_id' ] ) );
			} else {
				$is_activity = bookacti_product_is_activity( $product_id );
			}
			
			if( $is_activity ) {
				
				
				if( empty( $_POST[ 'bookacti_event_id' ] )
				||  empty( $_POST[ 'bookacti_event_start' ] ) 
				||  empty( $_POST[ 'bookacti_event_end' ] ) ) {
					return false;
				}
				
				global $woocommerce;
				$user_id = $woocommerce->session->get_customer_id();
				
				if( is_user_logged_in() ) { $user_id = get_current_user_id(); }
				
				//Gether the variables
				$event_id		= intval( $_POST[ 'bookacti_event_id' ] );
				$event_start	= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] );
				$event_end		= bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] );

				//Check if the form is ok and if so Book temporarily the event
				$response = bookacti_controller_book_temporarily( $user_id, $event_id, $event_start, $event_end, $quantity );
				
				if( $response[ 'status' ] === 'success' ) {

					global $woocommerce;

					//If the event is booked, add the booking ID to the corresponding hidden field
					$_POST[ 'bookacti_booking_id' ] = intval( $response[ 'id' ] );

					return true;

				} else {

					// Display error messgae
					wc_add_notice( $response[ 'message' ], 'error' );
				}

				return false;
			}
		}
		
		return $true;
	}

	
	//Set the timeout for a product added to cart
	add_action( 'woocommerce_add_to_cart', 'bookacti_set_timeout_to_cart_item', 30, 6 ); 
	function bookacti_set_timeout_to_cart_item( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		
		if( $variation_id !== 0 ) {
			$is_activity = bookacti_product_is_activity( $variation_id );
		} else {
			$is_activity = bookacti_product_is_activity( $product_id );
		}
		
		if( $is_activity ) {
			
			$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
			
			if( $is_expiration_active ) {
				
				//Retrieve user params about expiration
				$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
				$reset_timeout_on_change	= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
				$timeout					= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );

				//Compute expiration datetime
				$expiration_date	= date( 'c', strtotime( '+' . $timeout . ' minutes' ) );

				//If all cart item expire at once, set cart expiration date
				if( ! $is_per_product_expiration ) {
					
					global $woocommerce;
					$cart_expiration_date = bookacti_get_cart_timeout();
					
					// Reset cart timeout and its items timeout
					if(	$reset_timeout_on_change 
					||	is_null ( $cart_expiration_date ) 
					||	strtotime( $cart_expiration_date ) <= time()
					||  $woocommerce->cart->get_cart_contents_count() === $quantity ) {
						
						// Reset global cart timeout
						bookacti_set_cart_timeout( $expiration_date );
						
						//If there are others items in cart, we need to change their expiration dates
						if( $reset_timeout_on_change ) {
							$updated = bookacti_reset_cart_expiration_dates( $expiration_date );
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Load filters depending on WC version
	 *
	 * Used for WOOCOMMERCE 3.0.0 backward compatibility
	 * 
	 * @since 1.0.4
	 */
	add_action( 'woocommerce_loaded', 'bookacti_load_filters_with_backward_compatibility' );
	function bookacti_load_filters_with_backward_compatibility() {
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			add_filter( 'woocommerce_get_stock_html', 'bookacti_dont_display_instock_in_variation', 10, 2 );
			add_filter( 'wc_add_to_cart_message_html', 'bookacti_add_to_cart_message_html', 10, 2 );
		} else {
			add_filter( 'woocommerce_stock_html', 'bookacti_deprecated_dont_display_instock_in_variation', 10, 3 );
			add_filter( 'wc_add_to_cart_message', 'bookacti_deprecated_add_to_cart_message_html', 10, 2 );
		}
	}
	
	
	/**
	 * Notice the user that is activity has been reserved and will expire, along with the add to cart confirmation
	 *
	 * @since 1.0.4
	 */
	function bookacti_add_to_cart_message_html( $message, $products ) {
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

		if( $is_expiration_active ) {
			
			$total_added_qty	= 0;
			$is_activity		= false;
			// Check if there is at least one activity 
			foreach ( $products as $product_id => $qty ) {
				
				// Totalize added qty 
				$total_added_qty += $qty;
				
				if( ! $is_activity ) {
					$product		= wc_get_product( $product_id );
					
					// WOOCOMMERCE 3.0.0 BW compability
					if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
						if( method_exists( $product, 'get_type' ) ) {
							$product_type = $product->get_type();
						}
					} else {
						$product_type = $product->product_type;
					}

					// Check if product is activity
					if( $product_type === 'variable' ) {
						if( ! empty( $_POST[ 'variation_id' ] ) ) {
							
							// WOOCOMMERCE 3.0.0 BW compability
							if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
								if( method_exists( $product, 'get_visible_children' ) ) {
									$variation_ids = $product->get_visible_children();
								}
							} else {
								$variation_ids = $product->get_children( true );
							}
							
							foreach ( $variation_ids as $variation_id ) {
								if( $_POST[ 'variation_id' ] == $variation_id ) {
									$is_activity = true;
									break;
								}
							}
						}
					} else {
						if( bookacti_product_is_activity( $product_id ) ) {
							$is_activity = true;
						}
					}
				}
			}

			if( $is_activity ) {
				//Retrieve user params about expiration
				global $woocommerce;
				$is_per_product_expiration	= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_per_product' );
				$reset_timeout_on_change	= bookacti_get_setting_value( 'bookacti_cart_settings', 'reset_cart_timeout_on_change' );
				$timeout					= bookacti_get_setting_value( 'bookacti_cart_settings', 'cart_timeout' );

				//Compute expiration datetime
				$expiration_date	= date( 'c', strtotime( '+' . $timeout . ' minutes' ) );

				//If all cart item expire at once, set cart expiration date
				if( ! $is_per_product_expiration ) {

					$cart_expiration_date = bookacti_get_cart_timeout();

					if(	! $reset_timeout_on_change 
					&&  ! empty( $cart_expiration_date ) 
					&&  strtotime( $cart_expiration_date ) > time()
					&&  $woocommerce->cart->get_cart_contents_count() !== $total_added_qty ) {

						$expiration_date = $cart_expiration_date;
					}
				}

				//Change added to cart product expiration date
				//if it doesn't have one, 
				//if the old one is expired (that is to say the product is not in cart anymore), or 
				//if admin set to reset expiration on cart change
				
				$booking_id					= $_POST[ 'bookacti_booking_id' ];
				$is_expired					= bookacti_is_expired_booking( $booking_id );
				$current_expiration_date	= bookacti_get_booking_expiration_date( $booking_id );

				if( ! $reset_timeout_on_change && ! $is_expired && ! is_null( $current_expiration_date ) ) {
					$expiration_date = $current_expiration_date;
				}

				$time = bookacti_seconds_to_explode_time( round( abs( strtotime( $expiration_date ) - time() ) ) );
				$timeout_formated = ''; $days_formated = ''; $hours_formated = ''; $minutes_formated = '';

				if( intval( $time['days'] ) > 0 ) { 
					/* translators: %d is a variable number of days */
					$days_formated = sprintf( _n( '%d day', '%d days', $time['days'], BOOKACTI_PLUGIN_NAME ), $time['days'] );
					$timeout_formated .= $days_formated;
				}
				if( intval( $time['hours'] ) > 0 ) { 
					/* translators: %d is a variable number of hours */
					$hours_formated = sprintf( _n( '%d hour', '%d hours', $time['hours'], BOOKACTI_PLUGIN_NAME ), $time['hours'] );
					$timeout_formated .= ' ' . $hours_formated;
				}
				if( intval( $time['minutes'] ) > 0 ) { 
					/* translators: %d is a variable number of minutes */
					$minutes_formated = sprintf( _n( '%d minute', '%d minutes', $time['minutes'], BOOKACTI_PLUGIN_NAME ), $time['minutes'] );
					$timeout_formated .= ' ' . $minutes_formated;
				}

				/* translators: '%1$s' is a variable standing for an amount of days, hours and minutes. Ex: '%1$s' can be '1 day, 6 hours, 30 minutes'. */
				$temporary_book_message = sprintf( __( 'The schedule is temporarily booked for %1$s. Please proceed to checkout.', BOOKACTI_PLUGIN_NAME ), $timeout_formated );
				$temporary_book_message = apply_filters( 'bookacti_temporary_book_message', $temporary_book_message, $timeout_formated );
				$message .= '<br/>' . $temporary_book_message;
			}
		}
		
		return $message;
	}
	
	/**
	 * Notice the user that is activity has been reserved and will expire, along with the add to cart confirmation
	 *
	 * Only use it for WOOCOMMERCE 3.0.0 backward compatibility 
	 * 
	 * @since 1.0.4
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
			

	
//CART & CHECKOUT
	//Add the timeout to cart and checkout
	add_action( 'woocommerce_before_cart', 'bookacti_add_timeout_to_cart', 10, 0 );
	add_action( 'woocommerce_checkout_before_order_review', 'bookacti_add_timeout_to_cart', 10, 0 );
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

					$cart_keys = array_keys ( $cart_contents );
					
					foreach ( $cart_keys as $key ) {
						if( isset( $cart_contents[$key]['_bookacti_options'] ) && isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] ) ) {
							$booking_id = $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'];
							if( ! is_null( $booking_id ) ) {

								$is_in_cart_state = bookacti_get_booking_state( $booking_id ) === 'in_cart';

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

						/* translators: In context this sentence is followed by a countdown: Ex: 'Your cart expires in 3 days 12:35:26' or 'Your cart expires in 01:30:05'*/
						$timeout = "<div class='bookacti-cart-expiration-container woocommerce-info'>";
						$timeout .= esc_html__( 'Your cart expires in', BOOKACTI_PLUGIN_NAME );
						$timeout .= " <span class='bookacti-countdown bookacti-cart-expiration' data-expiration-date='" . esc_attr( $expiration_date ) . "' ></span>";
						$timeout .= '</div>';

						echo $timeout;

					}
				} else {
					bookacti_set_cart_timeout( null );
				}
			}
		}
	}


	//Add the timeout to each cart item
	add_filter( 'woocommerce_cart_item_remove_link', 'bookacti_add_timeout_to_cart_item', 10, 2 );
	function bookacti_add_timeout_to_cart_item( $sprintf, $cart_item_key ) { 
		
		$is_expiration_active = bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );
		
		if( $is_expiration_active ) {
		
			global $woocommerce;
			$item		= $woocommerce->cart->get_cart_item( $cart_item_key );
			
			if( isset( $item['_bookacti_options'] ) && isset( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
				$booking_id = $item['_bookacti_options']['bookacti_booking_id'];
				
				if( ! is_null( $booking_id ) ) {
					$timeout = bookacti_get_cart_item_timeout( $cart_item_key );
					$base = "<div class='bookacti-remove-cart-item-container'>" . $sprintf . "</div>";
					return $base . $timeout; 
				}
			}
		}
		
		return $sprintf;
	}

	
	//Delete cart items if they are expired (trigger on cart, on checkout, on mini-cart
	add_action( 'woocommerce_check_cart_items', 'bookacti_remove_expired_product_from_cart', 10, 0 );
	add_action( 'woocommerce_review_order_before_cart_contents', 'bookacti_remove_expired_product_from_cart', 10, 0 );
	add_action( 'woocommerce_before_mini_cart', 'bookacti_remove_expired_product_from_cart', 10, 0 );
	add_action( 'woocommerce_checkout_process', 'bookacti_remove_expired_product_from_cart', 10, 0 );
	function bookacti_remove_expired_product_from_cart() {
		
		if( is_checkout() || is_cart() ) {

			$is_expiration_active		= bookacti_get_setting_value( 'bookacti_cart_settings', 'is_cart_expiration_active' );

			if( $is_expiration_active ) {

				global $woocommerce;
				$cart_contents = $woocommerce->cart->get_cart();

				if( ! empty( $cart_contents ) ) {

					$cart_keys = array_keys ( $cart_contents );

					//Check if each cart item has expired, and if so, reduce its quantity to 0 (delete it)
					$nb_deleted_cart_item = 0;

					foreach ( $cart_keys as $key ) {
						if( isset( $cart_contents[$key]['_bookacti_options'] ) && isset( $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'] ) ) {
							$booking_id = $cart_contents[$key]['_bookacti_options']['bookacti_booking_id'];
							if( ! is_null( $booking_id ) ) {
								//Check if the cart item has expired, if so, remove it from cart
								$is_expired = bookacti_is_expired_booking( $booking_id );
								if( $is_expired ) {
									// Set quantity to zero to remove the product
									$is_deleted = $woocommerce->cart->set_quantity( $key , 0 , true );
									if( $is_deleted ) {
										$nb_deleted_cart_item++;
									}
								}
							}
						}
					}

					//Display feedback to tell user that (part of) his cart has expired
					if( $nb_deleted_cart_item > 0 ) {
						/* translators: %d is a variable number of products */
						$message = sprintf( _n(	'%d product has expired and has been automatically removed from cart.', 
												'%d products have expired and have been automatically removed from cart.', 
												$nb_deleted_cart_item, 
												BOOKACTI_PLUGIN_NAME ), $nb_deleted_cart_item );

						//display feedback
						wc_add_notice( $message, 'error' );
					}
				}
			}
		}
	}


	//If quantity changes in cart, temporarily book the extra qty if possible
	add_filter( 'woocommerce_stock_amount_cart_item', 'bookacti_update_quantity_in_cart', 20, 2 ); 
	function bookacti_update_quantity_in_cart( $wc_stock_amount, $cart_item_key ) { 
		
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		
		if( isset( $item['_bookacti_options'] ) && isset( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
			$booking_id		= $item['_bookacti_options']['bookacti_booking_id'];
			$is_in_cart		= bookacti_get_booking_state( $booking_id ) === 'in_cart';
			$old_quantity	= $item[ 'quantity' ];
			$new_quantity	= $wc_stock_amount;

			if( ! is_null( $booking_id ) && $new_quantity !== $old_quantity ) {

				if( $is_in_cart ) {

					$response = bookacti_controller_update_booking_quantity( $booking_id, $new_quantity );

					while( $response['status'] === 'qty_sup_to_avail' ) {
						$new_quantity = intval( $response[ 'availability' ] );
						$woocommerce->cart->set_quantity( $cart_item_key, $new_quantity, true );
						$response = bookacti_controller_update_booking_quantity( $booking_id, $new_quantity );
					}

					if( $response[ 'status' ] !== 'success' ) {
						$new_quantity = $old_quantity;
					}

				}  else {
					$new_quantity = $old_quantity;
					wc_add_notice( __( 'You can\'t update quantity since this product is temporarily booked on an order pending payment. Please, first cancel the order or remove this product from cart.', BOOKACTI_PLUGIN_NAME ), 'error' );
				}
			}
		}
		
		return $new_quantity;
	}


	//Remove the temporary booking when cart items are removed from cart
	add_action( 'woocommerce_remove_cart_item', 'bookacti_remove_bookings_of_removed_cart_item', 10, 2 ); 
	function bookacti_remove_bookings_of_removed_cart_item( $cart_item_key, $instance ) { 
		
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		
		if( isset( $item['_bookacti_options'] ) && isset( $item['_bookacti_options']['bookacti_booking_id'] ) ) {
			$booking_id = $item['_bookacti_options']['bookacti_booking_id'];

			$is_in_cart = bookacti_get_booking_state( $booking_id ) === 'in_cart';

			if( ! is_null( $booking_id ) && $is_in_cart ) {
				$response = bookacti_controller_update_booking_quantity( $booking_id, 0 );
			}
		}
	}


	//Restore the booking if user change his mind after deleting one
	add_action( 'woocommerce_cart_item_restored', 'bookacti_restore_bookings_of_removed_cart_item', 10, 2 ); 
	function bookacti_restore_bookings_of_removed_cart_item( $cart_item_key, $instance ) { 
		
		global $woocommerce;
		$item = $woocommerce->cart->get_cart_item( $cart_item_key );
		
		if( isset( $item['_bookacti_options'] ) && isset( $item['_bookacti_options']['bookacti_booking_id'] ) ) {

			$booking_id = $item['_bookacti_options']['bookacti_booking_id'];
			$is_removed	= bookacti_get_booking_state( $booking_id ) === 'removed';

			if( ! is_null( $booking_id ) && $is_removed ) {

				$item		= $woocommerce->cart->get_cart_item( $cart_item_key );
				$quantity	= $item[ 'quantity' ];
				$response	= bookacti_controller_update_booking_quantity( $booking_id, $quantity );
				
				while( $response['status'] === 'qty_sup_to_avail' ) {
					$max_quantity = intval( $response[ 'availability' ] );
					$woocommerce->cart->set_quantity( $cart_item_key, $max_quantity, true );
					$response = bookacti_controller_update_booking_quantity( $booking_id, $max_quantity );
				}
				
				if( in_array( $response['status'], array( 'success', 'no_change' ) ) ) {
					
					bookacti_update_booking_state( $booking_id, 'in_cart' );
					
				} else {

					$woocommerce->cart->set_quantity( $cart_item_key, 0, true );
					$response = bookacti_controller_update_booking_quantity( $booking_id, 0 );

				}
			}
		}
	}

	
	//Tell how to display the custom metadata in cart and checkout
	add_filter( 'woocommerce_get_item_data', 'bookacti_get_item_data', 10, 2 );
	function bookacti_get_item_data( $item_data, $cart_item_data ) {
		
		if( isset( $cart_item_data[ '_bookacti_options' ] ) ) {
			
			$start	= $cart_item_data[ '_bookacti_options' ][ 'bookacti_event_start' ];
			$end	= $cart_item_data[ '_bookacti_options' ][ 'bookacti_event_end' ];

			//Format start and end datetime of the event
			$start_value	= bookacti_format_datetime( $start );
			$end_value		= bookacti_format_datetime( $end );

			//Add Start and End of the Event as data to display with the cart item
			/* translators: 'Start' is followed by colon and the datetime of the activity booked. Ex: "Start: Wednesday 06 April 2016 08:00". */
			$item_data[] = array( 
				'key' => __( 'Start', BOOKACTI_PLUGIN_NAME ), 
				'value' => $start_value );
			/* translators: 'End' is followed by colon and the datetime of the activity booked. Ex: "End: Wednesday 06 April 2016 10:00". */
			$item_data[] = array( 
				'key' => __( 'End', BOOKACTI_PLUGIN_NAME ), 
				'value' => $end_value );
		}

		return $item_data;
	}

		
	//Particular case: Format label of custom metadata in cart and checkout
	add_filter( 'woocommerce_attribute_label', 'bookacti_define_label_of_item_data', 10, 2 );
	function bookacti_define_label_of_item_data( $label, $name ) {
		
		if( $label === '_bookacti_event_id' )		{ $label = __( 'Event ID', BOOKACTI_PLUGIN_NAME ); }
		if( $label === '_bookacti_booking_id' )		{ $label = __( 'Booking number', BOOKACTI_PLUGIN_NAME ); }
		if( $label === 'bookacti_booking_id' )		{ $label = __( 'Booking number', BOOKACTI_PLUGIN_NAME ); }
		if( $label === 'bookacti_event_start' )		{ $label = __( 'Start', BOOKACTI_PLUGIN_NAME ); }
		if( $label === 'bookacti_event_end' )		{ $label = __( 'End', BOOKACTI_PLUGIN_NAME ); }
		if( $label === 'bookacti_state' )			{ $label = __( 'Status', BOOKACTI_PLUGIN_NAME ); }
		if( $label === '_bookacti_refund_method' )	{ $label = __( 'Refund method', BOOKACTI_PLUGIN_NAME ); }
		if( $label === 'bookacti_refund_coupon' )	{ $label = __( 'Coupon code', BOOKACTI_PLUGIN_NAME ); }
		
		return $label;
	}
	
	
	//Particular case: Format value of custom metadata in order review
	add_filter( 'woocommerce_order_items_meta_get_formatted', 'bookacti_format_order_meta_data', 10, 2 );
	function bookacti_format_order_meta_data( $formatted_meta, $instance ) {
		
		foreach( $formatted_meta as $key => $meta ) {
			$value = $meta[ 'value' ];
			if( $meta[ 'key' ] === 'bookacti_event_start' || $meta[ 'key' ] === 'bookacti_event_end' ) {
				
				$formatted_meta[ $key ][ 'value' ] = bookacti_format_datetime( $value );
				
			} else if( $meta[ 'key' ] === 'bookacti_state' ) {
				
				$formatted_meta[ $key ][ 'value' ] = bookacti_format_booking_state( $value );
				
			}
		}
		
		return $formatted_meta;
	}
	
	
	/**
	 * Format order item mata values in order received page and in order pages in admin panel
	 * 
	 * Must be used since WC 3.0.0
	 * 
	 * @since 1.0.4
	 */
	add_filter( 'woocommerce_order_item_display_meta_value', 'bookacti_format_order_item_meta_values', 10, 1 );
	function bookacti_format_order_item_meta_values( $meta_value ) {
		
		if( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			// Format booking state
			$available_states = bookacti_get_booking_state_labels();
			if( array_key_exists( $meta_value, $available_states ) ) {
				return bookacti_format_booking_state( $meta_value );
			}

			// Format datetime
			if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $meta_value ) 
			||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $meta_value ) ) {
				//return bookacti_format_datetime( $meta_value );
			}
		}
		
		return $meta_value;
	}
	
	
	/**
	 * Format order item mata values in order received page
	 * 
	 * Must be used since WC 3.0.0
	 * 
	 * @since 1.0.4
	 * @version 1.0.5
	 */
	add_filter( 'woocommerce_display_item_meta', 'bookacti_format_order_item_meta', 10, 3 );
	function bookacti_format_order_item_meta( $html, $item, $args ) {
		
		$strings = array();
		$html    = '';
		
		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			// Format booking id
			if( $meta->key === 'bookacti_booking_id' ) {
				$meta->display_value = intval( $meta->value );
			}
			
			// Format booking state
			if( $meta->key === 'bookacti_state' ) {
				$meta->display_value = bookacti_format_booking_state( $meta->value );
			}
			
			// Format datetime
			if( $meta->key === 'bookacti_event_start' 
			||  $meta->key === 'bookacti_event_end' ) {
				$meta->display_value = bookacti_format_datetime( $meta->value );
			}
			
			$value = $args['autop'] ? wp_kses_post( wpautop( make_clickable( $meta->display_value ) ) ) : wp_kses_post( make_clickable( $meta->display_value ) );
			
			$strings[]	= '<strong	class="wc-item-meta-label wc-item-meta-' . $meta->key . '">' . wp_kses_post( $meta->display_key ) . ':</strong> '
						. '<span	class="wc-item-meta-value wc-item-meta-' . $meta->key . '">' . $value . '</span>';
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}
		
		return $html;
	}
	
	
	
//CHECKOUT

	//Add the timeout to each cart item in the checkout review
	add_filter( 'woocommerce_cart_item_name', 'bookacti_add_timeout_to_cart_item_in_checkout_review', 10, 3 );
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
	
	
	//After user validate checkout
	add_action( 'woocommerce_checkout_order_processed', 'bookacti_delay_expiration_date_for_payment', 10, 2 );
	function bookacti_delay_expiration_date_for_payment( $order_id, $order_details ) {
		
		//Bind order and user id to the bookings and turn its state to 
		//'pending' for payment
		//'booked' if no payment are required
		
		if( WC()->cart->needs_payment() ) { $state = 'pending'; $alert_admin = false; }
		else { $state = 'booked'; $alert_admin = true; }
		
		//Change state of all bookings of the order to 'pending' if a payment is required, or directly 'booked' if not
		bookacti_turn_order_bookings_to( $order_id, $state, $alert_admin );
	}
	

	
// BOOKINGS LIST

	// Add a column called 'Price' to user bookings list
	add_filter( 'bookacti_user_bookings_list_columns_titles', 'bookacti_add_woocommerce_price_column_to_bookings_list', 10, 2 );
	function bookacti_add_woocommerce_price_column_to_bookings_list( $columns, $user_id ) {
		$columns[ 60 ] = array( 'id' => 'price', 'title' => __( 'Price', BOOKACTI_PLUGIN_NAME ) );
		return $columns;
	}

	// Add prices in the 'Price' column of user bookings list
	add_filter( 'bookacti_user_bookings_list_columns_value', 'bookacti_add_woocommerce_prices_in_bookings_list', 20, 3 );
	function bookacti_add_woocommerce_prices_in_bookings_list( $columns_value, $booking, $user_id ) {
		$item = bookacti_get_order_item_by_booking_id( $booking->id );
		if( ! empty( $item ) ) {
			$total_price = wc_price( (float) $item[ 'line_total' ] + (float) $item[ 'line_tax' ] );
			$columns_value[ 'price' ] = $total_price ? $total_price : '/';
		}

		return $columns_value;
	}