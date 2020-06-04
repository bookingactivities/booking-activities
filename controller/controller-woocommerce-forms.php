<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add a description how to embbed calendars to woocommerce products
 * @since 1.5.0
 * @param string $step
 * @param int $template_id
 */
function bookacti_display_wc_calendar_integration_description( $step, $template_id ) {
	$description = '<ul><li>';
	$description .= '<strong>' . esc_html__( 'Without WooCommerce:', 'booking-activities' ) . '</strong> ' . $step;
	$description .= '</li><li>';
	$description .= '<strong>' . esc_html__( 'With WooCommerce:', 'booking-activities' ) . '</strong> ' . esc_html__( 'Bind the booking form to the desired product in the product data', 'booking-activities' );
	$description .= '</li></ul>';
	return $description;
}
add_filter( 'bookacti_calendar_integration_tuto', 'bookacti_display_wc_calendar_integration_description', 10, 2 );


/**
 * Add a description how to embbed forms to woocommerce products
 * @since 1.5.0
 * @param array $form
 */
function bookacti_display_wc_form_integration_description( $form ) {
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
 * @param array $params
 */
function bookacti_form_action_wc_notice( $params ) {
	?>
	<div class='bookacti-form-action-wc-notice'>
		<?php esc_html_e( 'The form action is not taken into account on WooCommerce product pages.', 'booking-activities' ); ?>
	</div>
	<?php
}
add_action( 'bookacti_calendar_dialog_actions_tab_after', 'bookacti_form_action_wc_notice', 10, 1 );


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
 * Add an icon before WC unsupported form field in form editor
 * @since 1.5.0
 * @param array $field_data
 * @param array $raw_field_data
 * @return array
 */
function bookacti_form_editor_wc_field_title( $field_data, $raw_field_data ) {
	if( in_array( $field_data[ 'name' ], bookacti_get_wc_unsupported_form_fields(), true ) ) {
		$field_data[ 'title' ] = '<span class="bookacti-wc-icon-not-supported"></span>' . $field_data[ 'title' ];
	}
	return $field_data;
}
add_filter( 'bookacti_formatted_field_data', 'bookacti_form_editor_wc_field_title', 10, 2 );
add_filter( 'bookacti_sanitized_field_data', 'bookacti_form_editor_wc_field_title', 10, 2 );


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
 * @version 1.8.0
 * @param array $field_data
 * @param array $raw_field_data
 * @return array
 */
function bookacti_format_wc_field_data( $field_data, $raw_field_data ) {
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$default_meta = bookacti_default_wc_calendar_form_field_meta( array() );
		$field_data[ 'product_by_activity' ]		= isset( $raw_field_data[ 'product_by_activity' ] ) && is_array( $raw_field_data[ 'product_by_activity' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_activity' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_activity' ];
		$field_data[ 'product_by_group_category' ]	= isset( $raw_field_data[ 'product_by_group_category' ] ) && is_array( $raw_field_data[ 'product_by_group_category' ] ) ? array_filter( array_map( 'intval', $raw_field_data[ 'product_by_group_category' ] ) ) : $default_meta[ 'calendar' ][ 'product_by_group_category' ];
	}
	return $field_data;
}
add_filter( 'bookacti_formatted_field_data', 'bookacti_format_wc_field_data', 10, 2 );


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
 * @version 1.8.0
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
		'id' => isset( $_REQUEST[ 'id' ] ) ? sanitize_title_with_dashes( stripslashes( $_REQUEST[ 'id' ] ) ) : ''
	);
	$args = apply_filters( 'bookacti_ajax_select2_products_args', $defaults );
	
	$products_titles = bookacti_get_products_titles( $term );
	$options = array();
	
	// Add products options
	foreach( $products_titles as $product_id => $product ) {
		$product_title = esc_html( apply_filters( 'bookacti_translate_text', $product[ 'title' ] ) );
		if( $product[ 'type' ] === 'variable' && ! empty( $product[ 'variations' ] ) ) {
			$children_options = array();
			foreach( $product[ 'variations' ] as $variation_id => $variation ) {
				$variation_title = esc_html( apply_filters( 'bookacti_translate_text', $variation[ 'title' ] ) );
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
 * Add the product bound to the selected event to cart
 * @since 1.7.0
 * @version 1.8.4
 */
function bookacti_controller_add_bound_product_to_cart() {
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_booking_form', 'nonce_booking_form', false ) ) {
		bookacti_send_json_invalid_nonce( 'add_bound_product_to_cart' );
	}
	
	$form_id		= intval( $_POST[ 'form_id' ] );
	$group_id		= $_POST[ 'bookacti_group_id' ] === 'single' ? 'single' : intval( $_POST[ 'bookacti_group_id' ] );
	$event_id		= intval( $_POST[ 'bookacti_event_id' ] );
	$event_start	= ! empty( $_POST[ 'bookacti_event_start' ] ) ? bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] ) : '';
	$event_end		= ! empty( $_POST[ 'bookacti_event_end' ] ) ? bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] ) : '';
	$product_id		= 0;
	
	$unknown_event_response = array( 'status' => 'failed', 'error' => 'unknown_event', 'messages' => esc_html__( 'The selected event couldn\'t be found.', 'booking-activities' ) );
	
	// Get the form; field data
	$field = bookacti_get_form_field_data_by_name( $form_id, 'calendar' );
	if( ! $field ) { 
		$no_field_response = array( 'status' => 'failed', 'error' => 'unknown_field', 'messages' => esc_html__( 'The calendar field data couldn\'t be retrieved.', 'booking-activities' ) );
		bookacti_send_json( $no_field_response, 'add_bound_product_to_cart' );
	}
	
	// Check if the form action is "add_product_to_cart"
	if( $field[ 'form_action' ] !== 'add_product_to_cart' ) { 
		$incorrect_form_action_response = array( 'status' => 'failed', 'error' => 'incorrect_form_action', 'messages' => esc_html__( 'You cannot add a product to cart with this form.', 'booking-activities' ) );
		bookacti_send_json( $incorrect_form_action_response, 'add_bound_product_to_cart' );
	}
	
	// A single event was selected
	if( $group_id === 'single' && $event_id && $event_start && $event_end ) {
		// Check if the event is available on this form
		$is_avail_on_form = bookacti_is_event_available_on_form( $form_id, $event_id, $event_start, $event_end );
		if( $is_avail_on_form[ 'status' ] !== 'success' ) { 
			$is_avail_on_form[ 'messages' ] = ! empty( $is_avail_on_form[ 'message' ] ) ? $is_avail_on_form[ 'message' ] : '';
			bookacti_send_json( $is_avail_on_form, 'add_bound_product_to_cart' );
		}
		
		// Get the event
		$event = bookacti_get_event_by_id( $event_id );
		if( ! $event ) { bookacti_send_json( $unknown_event_response, 'add_bound_product_to_cart' ); }
		
		// Find the product bound to the activity
		$product_id = ! empty( $field[ 'product_by_activity' ][ $event->activity_id ] ) ? intval( $field[ 'product_by_activity' ][ $event->activity_id ] ) : 0;
		
	}
	
	// A group of event was selected
	else if ( is_numeric( $group_id ) ) {
		// Check if the group of events is available on this form
		$is_avail_on_form = bookacti_is_group_of_events_available_on_form( $form_id, $group_id );
		if( $is_avail_on_form[ 'status' ] !== 'success' ) { 
			$is_avail_on_form[ 'messages' ] = ! empty( $is_avail_on_form[ 'message' ] ) ? $is_avail_on_form[ 'message' ] : '';
			bookacti_send_json( $is_avail_on_form, 'add_bound_product_to_cart' );
		}
		
		// Get the event
		$group = bookacti_get_group_of_events( $group_id );
		if( ! $group ) { bookacti_send_json( $unknown_event_response, 'add_bound_product_to_cart' ); }
		
		// Find the product bound to the group category
		$product_id = ! empty( $field[ 'product_by_group_category' ][ $group->category_id ] ) ? intval( $field[ 'product_by_group_category' ][ $group->category_id ] ) : 0;
		
	}
	
	// Cannot recognize the selected event
	else {
		bookacti_send_json( $unknown_event_response, 'add_bound_product_to_cart' );
	}
	
	// Check if the event is bound to a product
	if( ! $product_id ) {
		$no_product_bound_response = array( 'status' => 'failed', 'error' => 'no_product_bound', 'messages' => esc_html__( 'No product is bound to this event.', 'booking-activities' ) );
		bookacti_send_json( $no_product_bound_response, 'add_bound_product_to_cart' );
	}

	// Check if the product still exists
	$product = wc_get_product( $product_id );
	if( ! $product ) {
		$product_unavailable_response = array( 'status' => 'failed', 'error' => 'product_not_found', 'messages' => esc_html__( 'The desired product cannot be found.', 'booking-activities' ) );
		bookacti_send_json( $product_unavailable_response, 'add_bound_product_to_cart' );
	}
	
	// If the product is a variation, add the corresponding attributes to $_REQUEST
	if( $product->get_type() === 'variation' ) {
		$variation_data = wc_get_product_variation_attributes( $product_id );
		$_POST		= array_merge( $_POST, $variation_data );
		$_REQUEST	= array_merge( $_REQUEST, $variation_data );
		$_POST[ 'variation_id' ]	= $product_id;
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
	wc_set_notices( $wc_notices );
	
	// Get redirect URL
	$cart_url = get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ? esc_url( wc_get_page_permalink( 'cart' ) ) : '';
	$form_url = bookacti_get_metadata( 'form', $form_id, 'redirect_url', true );
	$redirect_url = $form_url ? esc_url( apply_filters( 'bookacti_translate_text', $form_url ) ) : $cart_url;
	
	$messages_array = array();
	if( ! empty( $wc_notices[ 'error' ] ) ) {
		if( version_compare( WC_VERSION, '3.9.0', '>=' ) ) {
			foreach( $wc_notices[ 'error' ] as $wc_notice ) { $messages_array[] = $wc_notice[ 'notice' ]; }
		} else {
			$messages_array = $wc_notices[ 'error' ];
		}
		$response = array( 'status' => 'failed', 'messages' => implode( '</li><li>', $messages_array ) );
	} else if( ! empty( $wc_notices[ 'success' ] ) ) {
		if( version_compare( WC_VERSION, '3.9.0', '>=' ) ) {
			foreach( $wc_notices[ 'success' ] as $wc_notice ) { $messages_array[] = $wc_notice[ 'notice' ]; }
		} else {
			$messages_array = $wc_notices[ 'success' ];
		}
		$response = array( 'status' => 'success', 'messages' => implode( '</li><li>', $messages_array ), 'redirect_url' => $redirect_url );
	} else {
		$response = array( 'status' => 'failed', 'error' => 'unknown_error', 'messages' => esc_html__( 'An error occurred while trying to add the product to cart.', 'booking-activities' ) );
	}
	
	// If the user is not redirected, clear the notices to display them only once in the booking form
	if( ! $redirect_url ) { wc_clear_notices(); }
	
	// Return the results
	bookacti_send_json( $response, 'add_bound_product_to_cart' );
}
add_action( 'wp_ajax_bookactiAddBoundProductToCart', 'bookacti_controller_add_bound_product_to_cart' );
add_action( 'wp_ajax_nopriv_bookactiAddBoundProductToCart', 'bookacti_controller_add_bound_product_to_cart' );


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