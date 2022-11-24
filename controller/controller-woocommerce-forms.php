<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add a description how to embbed calendars to woocommerce products
 * @since 1.5.0
 * @version 1.12.0
 * @param string $step
 * @param int $template_id
 */
function bookacti_display_wc_calendar_integration_description( $step, $template_id ) {
	$description = '<ul><li>';
	$description .= '<strong>' . esc_html__( 'Without WooCommerce:', 'booking-activities' ) . '</strong> ' . $step;
	$description .= '<li>';
	$description .= '<strong>' . esc_html__( 'With WooCommerce:', 'booking-activities' ) . '</strong> ' . esc_html__( 'Bind the booking form to the desired product in the product data', 'booking-activities' );
	$description .= '</ul>';
	return $description;
}
add_filter( 'bookacti_calendar_integration_tuto', 'bookacti_display_wc_calendar_integration_description', 10, 2 );


/**
 * Add a description how to embbed forms to woocommerce products
 * @since 1.5.0
 * @version 1.14.0
 * @param array $form_raw
 */
function bookacti_display_wc_form_integration_description( $form_raw ) {
	$img_link = esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/wc-integration.png' );
?>
	<h4><?php _e( 'Integrate with a WooCommerce product', 'booking-activities' ) ?></h4>
	<div>
		<p><em><?php esc_html_e( 'Select this form in your product data, in the "Activity" tab.', 'booking-activities' ); ?></em></p>
		<p id='bookacti-form-wc-integration-tuto' >
			<a href='<?php echo $img_link; ?>' target='_blank' style='display:block;'>
				<img src='<?php echo $img_link; ?>'/>
			</a>
		</p>
		<p><em><?php esc_html_e( 'For variable products, you can set a different form for each variation.', 'booking-activities' ); ?></em></p>
	</div>
<?php
}
add_action( 'bookacti_after_form_integration_tuto', 'bookacti_display_wc_form_integration_description', 10, 1 );


/**
 * Add a WooCommerce-related description to the form editor
 * @since 1.5.0
 * @version 1.7.8
 * @param array $form
 */
function bookacti_form_editor_wc_description( $form ) {
	/* translators: %1$s is replaced with an image */
	echo '<p>' . sprintf( __( 'The fields with this icon %1$s will NOT appear on WooCommerce product pages.', 'booking-activities' ), '<span class="bookacti-wc-icon-not-supported"></span>' );
	bookacti_help_tip( __( 'These fields already exist in WooCommerce. E.g.: Quantity and Submit are already part of product pages. Login and register fields are already asked on checkout page.', 'booking-activities' ) );
	echo '</p>';
}
add_action( 'bookacti_form_editor_description_after', 'bookacti_form_editor_wc_description', 20, 1 );


/**
 * Display a WC notice in the form editor, Calendar field settings, "Actions" tab
 * @since 1.7.15
 * @version 1.11.3
 * @param array $params
 */
function bookacti_form_action_wc_notice( $params ) {
	$docs = $links[ 'docs' ] = '<a href="' . esc_url( 'https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-calendars-on-product-page/?utm_source=plugin&utm_medium=plugin&utm_content=form-action-notice' ) . '" title="' . esc_attr( __( 'View Booking Activities Documentation', 'booking-activities' ) ) . '" target="_blank" >' . esc_html__( 'Docs', 'booking-activities' ) . '</a>';
	?>
	<div class='bookacti-info bookacti-form-action-with-wc-notice' style='display:none;'>
		<span class='dashicons dashicons-info'></span>
		<span>
		<?php 
			/* translators: %1$s = "Form action" option label. %2$s = the shortcode (e.g.: [bookingactivities_form form="21"]) */
			echo sprintf( esc_html__( 'The "%1$s" option is taken into account only if the form is displayed with its shortcode (%2$s).', 'booking-activities' ), '<strong>' . esc_html__( 'Form action', 'booking-activities' ) . '</strong>', '<code>[bookingactivities_form form="' . $params[ 'form' ][ 'form_id' ] . '"]</code>' );
		?>
		</span>
	</div>
	<div class='bookacti-info bookacti-add-product-to-cart-form-action-notice' style='display:none;'>
		<span class='dashicons dashicons-info'></span>
		<span>
		<?php 
			$activity_label = '<strong>' . esc_html__( 'Activity', 'booking-activities' ) . '</strong>';
			/* translators: %1$s = "Activity" option label. %2$s = "Docs" (link to the documentation) */
			echo sprintf( esc_html__( 'The products must be configured as "%1$s" in the product data (%2$s).', 'booking-activities' ), $activity_label, $docs );
		?>
		</span>
	</div>
	<?php
}
add_action( 'bookacti_calendar_dialog_actions_tab_before_tables', 'bookacti_form_action_wc_notice', 10, 1 );


/**
 * Set WC booking system attributes
 * @since 1.7.0
 * @param array $atts
 * @return array
 */
function bookacti_default_wc_booking_system_attributes( $atts ) {
	$atts[ 'product_by_activity' ]			= array();
	$atts[ 'product_by_group_category' ]	= array();
	$atts[ 'products_page_url' ]			= array();
	return $atts;
}
add_filter( 'bookacti_booking_system_default_attributes', 'bookacti_default_wc_booking_system_attributes', 10, 1 );


/**
 * Set WC calendar form field meta
 * @since 1.7.17
 * @param array $default_meta
 * @param string $field_name
 * @return array
 */
function bookacti_default_wc_calendar_form_field_meta( $default_meta, $field_name = '' ) {
	if( empty( $default_meta[ 'calendar' ] ) ) { $default_meta[ 'calendar' ] = array(); }
	$default_meta[ 'calendar' ][ 'product_by_activity' ]		= array();
	$default_meta[ 'calendar' ][ 'product_by_group_category' ]	= array();
	return $default_meta;
}
add_filter( 'bookacti_default_form_fields_meta', 'bookacti_default_wc_calendar_form_field_meta', 10, 2 );


/**
 * Display a price field on WC product page
 * @since 1.12.4
 * @global WC_Product $product
 * @param array $form
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_price_field_on_product_page( $form, $instance_id = '', $context = '' ) {
	if( $context !== 'wc_product_init' && $context !== 'wc_switch_variation' ) { return; }
	
	$price = '';
	
	global $product;
	if( $product ) {
		// Display price according to Tax options
		$tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
		if( $tax_display_shop === 'incl' ) {
			$price = wc_get_price_including_tax( $product );
		} else {
			$price = wc_get_price_excluding_tax( $product );
		}
	}
	?>
		<input type='hidden' data-name='price' value='<?php echo esc_attr( $price ); ?>'/>
	<?php
}
add_action( 'bookacti_form_before', 'bookacti_display_price_field_on_product_page', 10, 3 );


/**
 * Unlock the "Total Price" field for WooCommerce
 * @since 1.12.4
 * @param array $default_meta
 * @param string $field_name
 * @return array
 */
add_filter( 'bookacti_is_total_price_field_used', '__return_true' );


/**
 * Add an icon before WC unsupported form field in form editor
 * @since 1.5.0
 * @version 1.15.0
 * @param string $field_title
 * @param array $field_data
 * @return array
 */
function bookacti_form_editor_wc_field_title( $field_title, $field_data ) {
	if( ! bookacti_wc_is_form_field_supported( $field_data ) ) {
		$field_title = '<span class="bookacti-wc-icon-not-supported"></span>' . $field_title;
	}
	return $field_title;
}
add_filter( 'bookacti_form_editor_field_title', 'bookacti_form_editor_wc_field_title', 10, 2 );


/**
 * Format WC booking system attributes
 * @since 1.7.0
 * @version 1.7.17
 * @param array $formatted_atts
 * @param array $raw_atts
 * @return array
 */
function bookacti_format_wc_booking_system_attributes( $formatted_atts, $raw_atts ) {
	$product_by_activity = array();
	if( isset( $raw_atts[ 'product_by_activity' ] ) && is_array( $raw_atts[ 'product_by_activity' ] ) ) {
		foreach( $raw_atts[ 'product_by_activity' ] as $activity_id => $product_id ) {
			if( ! is_numeric( $activity_id ) || ! is_numeric( $product_id )
			||  empty( $activity_id ) || empty( $product_id )) { continue; }
			$product_by_activity[ intval( $activity_id ) ] = intval( $product_id );
		}
	}
	$formatted_atts[ 'product_by_activity' ] = $product_by_activity;
	
	$product_by_group_category = array();
	if( isset( $raw_atts[ 'product_by_group_category' ] ) && is_array( $raw_atts[ 'product_by_group_category' ] ) ) {
		foreach( $raw_atts[ 'product_by_group_category' ] as $group_category_id => $product_id ) {
			if( ! is_numeric( $group_category_id ) || ! is_numeric( $product_id ) 
			||  empty( $group_category_id ) || empty( $product_id ) ) { continue; }
			$product_by_group_category[ intval( $group_category_id ) ] = intval( $product_id );
		}
	}
	$formatted_atts[ 'product_by_group_category' ] = $product_by_group_category;
	
	if( $formatted_atts[ 'form_action' ] === 'redirect_to_product_page' ) {
		$products_ids = array_unique( array_merge( $product_by_activity, $product_by_group_category ) );
		if( $products_ids ) {
			foreach( $products_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if( ! $product ) { continue; }
				$formatted_atts[ 'products_page_url' ][ $product_id ] = $product->get_permalink();
			}
		}
	}
	
	return $formatted_atts;
}
add_filter( 'bookacti_formatted_booking_system_attributes', 'bookacti_format_wc_booking_system_attributes', 10, 2 );


/**
 * Sanitize WC booking system attributes 
 * @since 1.7.17
 * @version 1.14.0
 * @param array $field_data
 * @param array $raw_field_data
 * @param string $context
 * @return array
 */
function bookacti_format_wc_field_data( $field_data, $raw_field_data, $context ) {
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$default_meta = bookacti_default_wc_calendar_form_field_meta( array() );
		$field_data[ 'product_by_activity' ]		= isset( $raw_field_data[ 'product_by_activity' ] ) && is_array( $raw_field_data[ 'product_by_activity' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_activity' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_activity' ];
		$field_data[ 'product_by_group_category' ]	= isset( $raw_field_data[ 'product_by_group_category' ] ) && is_array( $raw_field_data[ 'product_by_group_category' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_group_category' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_group_category' ];
	}
	return $field_data;
}
add_filter( 'bookacti_formatted_field_data', 'bookacti_format_wc_field_data', 10, 3 );


/**
 * Sanitize WC booking system attributes 
 * @since 1.7.0
 * @version 1.8.0
 * @param array $field_data
 * @param array $raw_field_data
 * @return array
 */
function bookacti_sanitize_wc_field_data( $field_data, $raw_field_data ) {
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$default_meta = bookacti_default_wc_calendar_form_field_meta( array() );
		$field_data[ 'product_by_activity' ]		= isset( $raw_field_data[ 'product_by_activity' ] ) && is_array( $raw_field_data[ 'product_by_activity' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_activity' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_activity' ];
		$field_data[ 'product_by_group_category' ]	= isset( $raw_field_data[ 'product_by_group_category' ] ) && is_array( $raw_field_data[ 'product_by_group_category' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_group_category' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_group_category' ];
	}
	return $field_data;
}
add_filter( 'bookacti_sanitized_field_data', 'bookacti_sanitize_wc_field_data', 10, 2 );


/**
 * Add new possible actions when the user clicks an event
 * @since 1.7.0
 * @param array $options
 * @param array $params
 * @return array
 */
function bookacti_add_wc_form_action_options( $options ) {
	$options[ 'redirect_to_product_page' ] = esc_html__( 'Redirect to a product page', 'booking-activities' );
	$options[ 'add_product_to_cart' ] = esc_html__( 'Add a product to cart', 'booking-activities' );
	return $options;
}
add_filter( 'bookacti_form_action_options', 'bookacti_add_wc_form_action_options', 10, 1 );


/**
 * Add product price per activity / category to booking sytem data if the form action is related to products
 * @since 1.12.4
 * @param array $booking_system_data
 * @param array $atts
 * @return array
 */
function bookacti_wc_add_product_price_per_activity_to_booking_system_data( $booking_system_data, $atts ) {
	if( ! in_array( $atts[ 'form_action' ], array( 'add_product_to_cart', 'redirect_to_product_page' ), true ) ) { return $booking_system_data; }
	
	$booking_system_data[ 'product_price_by_activity' ] = array();
	$booking_system_data[ 'product_price_by_group_category' ] = array();

	// Display price according to Tax options
	$tax_display_shop = get_option( 'woocommerce_tax_display_shop' );

	foreach( $booking_system_data[ 'product_by_activity' ] as $activity_id => $product_id ) {
		$product = wc_get_product( $product_id );
		if( $product ) { $booking_system_data[ 'product_price_by_activity' ][ $activity_id ] = $tax_display_shop === 'incl' ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product ); }
	}
	foreach( $booking_system_data[ 'product_by_group_category' ] as $category_id => $product_id ) {
		$product = wc_get_product( $product_id );
		if( $product ) { $booking_system_data[ 'product_price_by_group_category' ][ $category_id ] = $tax_display_shop === 'incl' ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product ); }
	}
	
	return $booking_system_data;
}
add_filter( 'bookacti_booking_system_data', 'bookacti_wc_add_product_price_per_activity_to_booking_system_data', 20, 2 );


/**
 * Add columns to the activity redirect URL table
 * @since 1.7.0
 * @version 1.7.19
 * @param array $url_array
 * @param array $params
 * @return array
 */
function bookacti_add_wc_columns_to_activity_redirect_url_table( $url_array, $params = array() ) {
	$url_array[ 'head' ][ 'product' ] = esc_html__( 'Bound product', 'booking-activities' );
	
	// Get the product selectbox
	$args = array(
		'field_name'		=> 'product_by_activity[0]',
		'allow_tags'		=> 0,
		'allow_clear'		=> 1,
		'echo'				=> 0
	);
	$default_product_selectbox	= bookacti_display_product_selectbox( $args );
	
	$redirect_url_activity_ids	= ! empty( $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] ) && is_array( $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] ) ? array_keys( $params[ 'calendar_data' ][ 'redirect_url_by_activity' ] ) : array();
	$product_activity_ids		= ! empty( $params[ 'calendar_data' ][ 'product_by_activity' ] ) && is_array( $params[ 'calendar_data' ][ 'product_by_activity' ] ) ? array_keys( $params[ 'calendar_data' ][ 'product_by_activity' ] ) : array();
	$missing_activities			= array_diff( $product_activity_ids, $redirect_url_activity_ids );
	$product_by_activity		= ! empty( $params[ 'calendar_data' ][ 'product_by_activity' ] ) && is_array( $params[ 'calendar_data' ][ 'product_by_activity' ] ) ? $params[ 'calendar_data' ][ 'product_by_activity' ] : array();
	
	// Add missing rows, those having a product bound but no redirect URL
	foreach( $product_by_activity as $activity_id => $product_id ) {
		if( ! in_array( $activity_id, $missing_activities, true ) ) { continue; }
		$url_array[ 'body' ][] = array( 
			'activity' => intval( $activity_id ),
			'redirect_url' => '<input type="text" name="redirect_url_by_activity[ ' . intval( $activity_id ) . ' ]" value="" />'
		);
	}
	
	// Add the product column content
	foreach( $url_array[ 'body' ] as $i => $row ) {
		$activity_id	= ! empty( $row[ 'activity' ] ) ? intval( $row[ 'activity' ] ) : 0;
		$selected		= ! empty( $product_by_activity[ $activity_id ] ) ? intval( $product_by_activity[ $activity_id ] ) : 0;
		$url_array[ 'body' ][ $i ][ 'product' ] = $activity_id ? bookacti_display_product_selectbox( array_merge( $args, array( 'field_name' => 'product_by_activity[' . $activity_id . ']', 'selected' => $selected ) ) ) : $default_product_selectbox;
	}
	
	return $url_array;
}
add_filter( 'bookacti_activity_redirect_url_table', 'bookacti_add_wc_columns_to_activity_redirect_url_table', 10, 2 );


/**
 * Add columns to the group category redirect URL table
 * @since 1.7.19 (was bookacti_add_wc_columns_to_group_activity_redirect_url_table)
 * @version 1.8.3
 * @param array $url_array
 * @param array $params
 * @return array
 */
function bookacti_add_wc_columns_to_group_category_redirect_url_table( $url_array, $params = array() ) {
	$url_array[ 'head' ][ 'product' ] = esc_html__( 'Bound product', 'booking-activities' );
	
	$args = array(
		'field_name'		=> 'product_by_group_category[0]',
		'allow_tags'		=> 0,
		'allow_clear'		=> 1,
		'echo'				=> 0
	);
	$default_product_selectbox	= bookacti_display_product_selectbox( $args );
	
	$redirect_url_group_category_ids= ! empty( $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] ) && is_array( $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] ) ? array_keys( $params[ 'calendar_data' ][ 'redirect_url_by_group_category' ] ) : array();
	$product_group_category_ids		= ! empty( $params[ 'calendar_data' ][ 'product_by_group_category' ] ) && is_array( $params[ 'calendar_data' ][ 'product_by_group_category' ] ) ? array_keys( $params[ 'calendar_data' ][ 'product_by_group_category' ] ) : array();
	$missing_group_categories		= array_diff( $product_group_category_ids, $redirect_url_group_category_ids );
	$product_by_group_category		= ! empty( $params[ 'calendar_data' ][ 'product_by_group_category' ] ) && is_array( $params[ 'calendar_data' ][ 'product_by_group_category' ] ) ? $params[ 'calendar_data' ][ 'product_by_group_category' ] : array();
	
	// Add missing rows, those having a product bound but no redirect URL
	foreach( $product_by_group_category as $group_category_id => $product_id ) {
		if( ! in_array( $group_category_id, $missing_group_categories, true ) ) { continue; }
		$url_array[ 'body' ][] = array( 
			'group_category' => intval( $group_category_id ),
			'redirect_url' => '<input type="text" name="redirect_url_by_group_category[ ' . intval( $group_category_id ) . ' ]" value="" />'
		);
	}
	
	// Add the product column content
	foreach( $url_array[ 'body' ] as $i => $row ) {
		$group_category_id	= ! empty( $row[ 'group_category' ] ) ? $row[ 'group_category' ] : 0;
		$selected			= ! empty( $product_by_group_category[ $group_category_id ] ) ? intval( $product_by_group_category[ $group_category_id ] ) : 0;
		$url_array[ 'body' ][ $i ][ 'product' ] = $group_category_id ? bookacti_display_product_selectbox( array_merge( $args, array( 'field_name' => 'product_by_group_category[' . $group_category_id . ']', 'selected' => $selected ) ) ) : $default_product_selectbox;
	}
	
	return $url_array;
}
add_filter( 'bookacti_group_category_redirect_url_table', 'bookacti_add_wc_columns_to_group_category_redirect_url_table', 10, 2 );


/**
 * Search products for AJAX selectbox
 * @since 1.7.19
 * @version 1.14.0
 */
function bookacti_controller_search_select2_products() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'bookacti_query_select2_options', 'nonce', false );
	if( ! $is_nonce_valid ) { bookacti_send_json_not_allowed( 'search_select2_products' ); }
	
	// Check query
	$term = isset( $_REQUEST[ 'term' ] ) ? sanitize_text_field( stripslashes( $_REQUEST[ 'term' ] ) ) : '';
	if( ! $term ) { bookacti_send_json( array( 'status' => 'failed', 'error' => 'empty_query' ), 'search_select2_products' ); }
	
	$defaults = array(
		'name' => isset( $_REQUEST[ 'name' ] ) ? sanitize_title_with_dashes( stripslashes( $_REQUEST[ 'name' ] ) ) : '',
		'id'   => isset( $_REQUEST[ 'id' ] ) ? sanitize_title_with_dashes( stripslashes( $_REQUEST[ 'id' ] ) ) : ''
	);
	$args = apply_filters( 'bookacti_ajax_select2_products_args', $defaults );
	
	$products_titles = bookacti_get_products_titles( $term );
	$options = array();
	
	// Add products options
	foreach( $products_titles as $product_id => $product ) {
		$product_title = $product[ 'title' ] !== '' ? esc_html( apply_filters( 'bookacti_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : $product[ 'title' ];
		if( $product[ 'type' ] === 'variable' && ! empty( $product[ 'variations' ] ) ) {
			$children_options = array();
			foreach( $product[ 'variations' ] as $variation_id => $variation ) {
				$variation_title = $variation[ 'title' ] !== '' ? esc_html( apply_filters( 'bookacti_translate_text_external', $variation[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_variation', 'object_id' => $variation_id, 'field' => 'post_excerpt', 'product_id' => $product_id ) ) ) : $variation[ 'title' ];
				$formatted_variation_title = trim( preg_replace( '/,[\s\S]+?:/', ',', ',' . $variation_title ), ', ' );
				$children_options[] = array( 'id' => $variation_id, 'text' => $formatted_variation_title );
			}
			$options[] = array( 'children' => $children_options, 'text' => $product_title );
		} else {
			$options[] = array( 'id' => $product_id, 'text' => $product_title );
		}
	}
	
	// Allow plugins to add their values
	$select2_options = apply_filters( 'bookacti_ajax_select2_products_options', $options, $args );
	
	if( ! $select2_options ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'no_results' ), 'search_select2_products' );
	}
	
	bookacti_send_json( array( 'status' => 'success', 'options' => $select2_options ), 'search_select2_products' );
}
add_action( 'wp_ajax_bookactiSelect2Query_products', 'bookacti_controller_search_select2_products' );


/**
 * Add products bound to the selected events to cart
 * @since 1.7.0
 * @version 1.14.0
 */
function bookacti_controller_add_bound_product_to_cart() {
	$form_id       = intval( $_POST[ 'form_id' ] );
	$quantity      = empty( $_REQUEST[ 'quantity' ] ) ? 1 : wc_stock_amount( wp_unslash( $_REQUEST[ 'quantity' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$picked_events = ! empty( $_POST[ 'selected_events' ] ) ? bookacti_format_picked_events( $_POST[ 'selected_events' ] ) : array();
	$return_array  = array( 'status' => 'failed', 'error' => '', 'messages' => array(), 'products' => array() );
	
	// Get the form; field data
	$field = bookacti_get_form_field_data_by_name( $form_id, 'calendar' );
	if( ! $field ) {
		$return_array[ 'error' ] = 'unknown_field'; 
		$return_array[ 'messages' ] = esc_html__( 'The calendar field data couldn\'t be retrieved.', 'booking-activities' );
		bookacti_send_json( $return_array, 'add_bound_product_to_cart' );
	}
	
	// Check if the form action is "add_product_to_cart"
	if( $field[ 'form_action' ] !== 'add_product_to_cart' ) {
		$return_array[ 'error' ] = 'incorrect_form_action'; 
		$return_array[ 'messages' ] = esc_html__( 'You cannot add a product to cart with this form.', 'booking-activities' );
		bookacti_send_json( $return_array, 'add_bound_product_to_cart' );
	}
	
	// Check if the booking form is valid
	$response = bookacti_validate_booking_form( $picked_events, $quantity, $form_id );
	if( $response[ 'status' ] !== 'success' ) {
		$messages = ! empty( $response[ 'message' ] ) ? array( $response[ 'message' ] ) : array();
		$return_array[ 'error' ] = $response[ 'error' ];
		foreach( $response[ 'messages' ] as $error => $error_messages ) {
			if( ! is_array( $error_messages ) ) { $error_messages = array( $error_messages ); }
			$messages = array_merge( $messages, $error_messages );
		}
		$return_array[ 'messages' ]	= implode( '</li><li>', array_merge( $return_array[ 'messages' ], $messages ) );
		bookacti_send_json( $return_array, 'add_bound_product_to_cart' );
	}
	
	$wc_notices_all = array();
	$init_post = $_POST;
	$init_request = $_REQUEST;
	unset( $_POST[ 'selected_events' ] );
	
	// Keep one entry per group
	$picked_events = $response[ 'picked_events' ];
	
	// Check if the products can be added to cart
	foreach( $picked_events as $i => $picked_event ) {
		$last_picked_event = end( $picked_event[ 'events' ] );
		$first_picked_event = reset( $picked_event[ 'events' ] );

		$group_id = $picked_event[ 'group_id' ];
		$event_id = isset( $first_picked_event[ 'id' ] ) ? $first_picked_event[ 'id' ] : 0;
		$event_start = isset( $first_picked_event[ 'start' ] ) ? $first_picked_event[ 'start' ] : '';
		$event_end   = isset( $last_picked_event[ 'end' ] ) ? $last_picked_event[ 'end' ] : '';

		$group = $group_id && ! empty( $picked_event[ 'args' ][ 'event' ] ) ? $picked_event[ 'args' ][ 'event' ] : array();
		$event = ! $group_id && ! empty( $picked_event[ 'args' ][ 'event' ] ) ? $picked_event[ 'args' ][ 'event' ] : array();
		
		$title = $group ? $group[ 'title' ] : ( $event ? $event[ 'title' ] : '' );
		$dates = bookacti_get_formatted_event_dates( $event_start, $event_end, false );
		
		if( $group_id ) {
			// Find the product bound to the group category
			$product_id = ! empty( $field[ 'product_by_group_category' ][ $group[ 'category_id' ] ] ) ? intval( $field[ 'product_by_group_category' ][ $group[ 'category_id' ] ] ) : 0;
		} else {
			// Find the product bound to the activity
			$product_id = ! empty( $field[ 'product_by_activity' ][ $event[ 'activity_id' ] ] ) ? intval( $field[ 'product_by_activity' ][ $event[ 'activity_id' ] ] ) : 0;
		}
		
		// Check if the event is bound to a product
		if( ! $product_id ) {
			$return_array[ 'error' ] = 'no_product_bound'; 
			/* translators: %s = The event title and dates. E.g.: No product is bound to "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)". */
			$return_array[ 'messages' ][] = sprintf( esc_html__( 'No product is bound to "%s".', 'booking-activities' ), $title ? $title . ' (' . $dates . ')' : $dates );
			continue;
		}
		
		// Check if the product still exists
		$product = wc_get_product( $product_id );
		if( ! $product ) {
			$return_array[ 'error' ] = 'product_not_found'; 
			/* translators: %d = The product ID. %s = The event title and dates. E.g.: The product (#56) bound to "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" cannot be found. */
			$return_array[ 'messages' ][] = sprintf( esc_html__( 'The product (#%1$s) bound to "%2$s" cannot be found.', 'booking-activities' ), $product_id, $title ? $title . ' (' . $dates . ')' : $dates );
			continue;
		}
		
		// Reset posted data
		$_POST = $init_post;
		$_REQUEST = $init_request;
		$_POST[ 'selected_events' ]    = $picked_event[ 'events' ];
		$_REQUEST[ 'selected_events' ] = $picked_event[ 'events' ];
		
		// If the product is a variation, add the corresponding attributes to $_REQUEST
		if( $product->get_type() === 'variation' ) {
			$variation_data = wc_get_product_variation_attributes( $product_id );
			$_POST    = array_merge( $_POST, $variation_data );
			$_REQUEST = array_merge( $_REQUEST, $variation_data );
			$_POST[ 'variation_id' ]    = $product_id;
			$_REQUEST[ 'variation_id' ] = $product_id;
		}

		// Make sure there is no remaining notices
		wc_clear_notices();

		// Add a dummy error notice to prevent the form handler to redirect to cart
		wc_add_notice( 'block_redirect', 'error' );

		// Add the product to cart
		$_REQUEST[ 'add-to-cart' ] = $product_id;
		WC_Form_Handler::add_to_cart_action();

		// Get the results
		$wc_notices = wc_get_notices();

		// Remove the dummy error notice
		unset( $wc_notices[ 'error' ][ 0 ] );
		
		// Store the new notices in the array containing all the notices
		if( ! empty( $wc_notices[ 'error' ] ) ) { $wc_notices[ 'error' ] = array_values( $wc_notices[ 'error' ] ); }
		$wc_notices_all = array_merge_recursive( $wc_notices_all, $wc_notices );
		
		$messages_array = array();
		if( ! empty( $wc_notices[ 'error' ] ) ) {
			$return_array[ 'error' ] = 'wc_add_to_cart_handler_error';
			if( version_compare( WC_VERSION, '3.9.0', '>=' ) ) {
				foreach( $wc_notices[ 'error' ] as $wc_notice ) { $return_array[ 'messages' ][] = $wc_notice[ 'notice' ]; }
			} else {
				$return_array[ 'messages' ] = array_merge( $return_array[ 'messages' ], $wc_notices[ 'error' ] );
			}
		} else if( ! empty( $wc_notices[ 'success' ] ) ) {
			$return_array[ 'products' ][] = array( 'id' => $product_id, 'picked_event' => $picked_event );
			if( version_compare( WC_VERSION, '3.9.0', '>=' ) ) {
				foreach( $wc_notices[ 'success' ] as $wc_notice ) { $return_array[ 'messages' ][] = $wc_notice[ 'notice' ]; }
			} else {
				$return_array[ 'messages' ] = array_merge( $return_array[ 'messages' ], $wc_notices[ 'success' ] );
			}
		} else {
			$return_array[ 'error' ] = 'unknown_error'; 
			/* translators: %d = The product ID. %s = The event title and dates. E.g.: An error occurred while trying to add the product (#56) bound to "Basketball (Sep, 22nd - 3:00 PM to 6:00 PM)" to cart. */
			$return_array[ 'messages' ][] = sprintf( esc_html__( 'An error occurred while trying to add the product (#%1$s) bound to "%2$s" to cart.', 'booking-activities' ), $product_id, $title ? $title . ' (' . $dates . ')' : $dates );
		}
	}
	
	// Reset posted data
	$_POST = $init_post;
	$_REQUEST = $init_request;
	
	// Reset notices
	wc_clear_notices();
	wc_set_notices( $wc_notices_all );
	
	$return_array[ 'messages' ]	= implode( '</li><li>', $return_array[ 'messages' ] );
	
	// Feedback errors
	if( $return_array[ 'error' ] ) {
		bookacti_send_json( $return_array, 'add_bound_product_to_cart' );
	}
	
	// Get redirect URL
	$cart_url = get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ? esc_url( wc_get_page_permalink( 'cart' ) ) : '';
	$form_url = bookacti_get_metadata( 'form', $form_id, 'redirect_url', true );
	$redirect_url = $form_url ? esc_url( apply_filters( 'bookacti_translate_text', $form_url ) ) : $cart_url;
	
	// If the user is not redirected, clear the notices to display them only once in the booking form
	if( ! $redirect_url ) { wc_clear_notices(); }
	
	// Return the results
	$return_array[ 'status' ] = 'success';
	$return_array[ 'redirect_url' ]	= $redirect_url;
	bookacti_send_json( $return_array, 'add_bound_product_to_cart' );
}
add_action( 'wp_ajax_bookactiAddBoundProductToCart', 'bookacti_controller_add_bound_product_to_cart', 20 );
add_action( 'wp_ajax_nopriv_bookactiAddBoundProductToCart', 'bookacti_controller_add_bound_product_to_cart', 20 );


/**
 * Change the booking form bound to a product if the product is added to cart via a booking form
 * @since 1.7.0
 * @param int $form_id
 * @param int $product_id
 * @param boolean $is_variation
 * @return int
 */
function bookacti_change_product_form_id_if_added_to_cart_via_booking_form( $form_id, $product_id, $is_variation ) {
	if( ! empty( $_POST[ 'action' ] ) && $_POST[ 'action' ] === 'bookactiAddBoundProductToCart' && ! empty( $_POST[ 'form_id' ] ) ) {
		$form_id = intval( $_POST[ 'form_id' ] );
	}
	return $form_id;
}
add_filter( 'bookacti_product_booking_form_id', 'bookacti_change_product_form_id_if_added_to_cart_via_booking_form', 10, 3 );


/**
 * Display WC user meta corresponding to the desired meta if it is empty
 * @since 1.7.18
 * @version 1.7.19
 * @param array $args
 * @param array $raw_args
 * @return array
 */
function bookacti_display_wc_user_meta_in_user_selectbox( $args, $raw_args = array() ) {
	if( empty( $args[ 'option_label' ] ) ) { return $args; }
	
	$wc_additional_option_labels = array( 
		'user_email'=> 'billing_email', 
		'first_name'=> 'billing_first_name||shipping_first_name', 
		'last_name'	=> 'billing_last_name||shipping_last_name', 
		'phone'		=> 'billing_phone'
	);
	$wc_additional_option_labels_keys = array_keys( $wc_additional_option_labels );
	foreach( $args[ 'option_label' ] as $i => $show ) {
		if( in_array( $show, $wc_additional_option_labels_keys, true ) ) {
			$args[ 'option_label' ][ $i ] .= '||' . $wc_additional_option_labels[ $show ];
		}
	}
	return $args;
}
add_filter( 'bookacti_user_selectbox_args', 'bookacti_display_wc_user_meta_in_user_selectbox', 10, 2 );
add_filter( 'bookacti_ajax_select2_users_args', 'bookacti_display_wc_user_meta_in_user_selectbox', 10, 1 );


/**
 * Delete products_titles cache if a product or a variation is added / updated / removed
 * @since 1.14.2
 * @param int $product_id
 */
function bookacti_clear_products_titles_cache( $product_id ) {
	wp_cache_delete( 'products_titles', 'bookacti_wc' );
}
add_action( 'woocommerce_delete_product_transients', 'bookacti_clear_products_titles_cache', 10, 1 );


/**
 * Callback to send WC Reset Password notification
 * @since 1.15.5
 * @param string|array $callback
 * @return string|array
 */
function bookacti_wc_reset_password_notification_callback( $callback ) {
	return 'bookacti_wc_send_reset_password_notification';
}
add_filter( 'bookacti_reset_password_notification_callback', 'bookacti_wc_reset_password_notification_callback', 10, 1 );