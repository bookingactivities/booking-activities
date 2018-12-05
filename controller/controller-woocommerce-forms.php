<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Add a description how to embbed calendars to woocommerce products
 * @since 1.5.0
 * @param string $step2
 * @param int $template_id
 */
function bookacti_display_wc_calendar_integration_description( $step, $template_id ) {
	$description = '<ul><li>';
	$description .= '<strong>' . esc_html__( 'Without WooCommerce:', BOOKACTI_PLUGIN_NAME ) . '</strong> ' . $step;
	$description .= '</li><li>';
	$description .= '<strong>' . esc_html__( 'With WooCommerce:', BOOKACTI_PLUGIN_NAME ) . '</strong> ' . esc_html__( 'Bind the booking form to the desired product in the product data', BOOKACTI_PLUGIN_NAME );
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
	<h4><?php _e( 'Integrate with a WooCommerce product', BOOKACTI_PLUGIN_NAME ) ?></h4>
	<div>
		<p><em><?php esc_html_e( 'Select this form in your product data, in the "Activity" tab.', BOOKACTI_PLUGIN_NAME ); ?></em></p>
		<p id='bookacti-form-wc-integration-tuto' >
			<a href='<?php echo $img_link; ?>' target='_blank' style='display:block;'>
				<img src='<?php echo $img_link; ?>'/>
			</a>
		</p>
		<p><em><?php esc_html_e( 'For variable products, you can set a different form for each variation.', BOOKACTI_PLUGIN_NAME ); ?></em></p>
	</div>
<?php
}
add_action( 'bookacti_after_form_integration_tuto', 'bookacti_display_wc_form_integration_description', 10, 1 );


/**
 * Add a WooCommerce-related description to the form editor
 * @since 1.5.0
 * @param array $form
 */
function bookacti_form_editor_wc_description( $form ) {
	echo '<p>' . sprintf( __( 'The fields with this icon %1$s will NOT appear on WooCommerce product pages.', BOOKACTI_PLUGIN_NAME ), '<span class="bookacti-wc-icon-not-supported"></span>' );
	bookacti_help_tip( __( 'These fields already exist in WooCommerce. E.g.: Quantity and Submit are already part of product pages. Login and register fields are already asked on checkout page.', BOOKACTI_PLUGIN_NAME ) );
	echo '</p>';
}
add_action( 'bookacti_form_editor_description_after', 'bookacti_form_editor_wc_description', 20, 1 );


/**
 * Set WC field meta default value
 * @since 1.7.0
 * @param array $field_meta
 * @param string $field_name
 * @return array
 */
function bookacti_default_wc_field_meta( $field_meta, $field_name ) {
	$field_meta[ 'calendar' ][ 'product_by_activity' ]		= array();
	$field_meta[ 'calendar' ][ 'product_by_group_category' ]= array();
	return $field_meta;
}
add_filter( 'bookacti_default_form_fields_meta', 'bookacti_default_wc_field_meta', 10, 2 );


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
 * Format WC field data 
 * @since 1.7.0
 * @param array $field_data
 * @param array $raw_field_data
 * @return array
 */
function bookacti_format_wc_field_data( $field_data, $raw_field_data ) {
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$field_data[ 'product_by_activity' ]		= isset( $raw_field_data[ 'product_by_activity' ] ) ? $raw_field_data[ 'product_by_activity' ] : array();
		$field_data[ 'product_by_group_category' ]	= isset( $raw_field_data[ 'product_by_group_category' ] ) ? $raw_field_data[ 'product_by_group_category' ] : array();
	}
	return $field_data;
}
add_filter( 'bookacti_formatted_field_data', 'bookacti_format_wc_field_data', 10, 2 );


/**
 * Sanitize WC field data 
 * @since 1.7.0
 * @param array $field_data
 * @param array $raw_field_data
 * @return array
 */
function bookacti_sanitize_wc_field_data( $field_data, $raw_field_data ) {
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$product_by_activity = array();
		if( isset( $raw_field_data[ 'product_by_activity' ] ) && is_array( $raw_field_data[ 'product_by_activity' ] ) ) {
			foreach( $raw_field_data[ 'product_by_activity' ] as $activity_id => $product_id ) {
				if( ! is_numeric( $activity_id ) || ! is_numeric( $product_id )
				||  empty( $activity_id ) || empty( $product_id )) { continue; }
				$product_by_activity[ intval( $activity_id ) ] = intval( $product_id );
			}
		}
		$field_data[ 'product_by_activity' ] = maybe_serialize( $product_by_activity );
		
		$product_by_group_category = array();
		if( isset( $raw_field_data[ 'product_by_group_category' ] ) && is_array( $raw_field_data[ 'product_by_group_category' ] ) ) {
			foreach( $raw_field_data[ 'product_by_group_category' ] as $group_category_id => $product_id ) {
				if( ! is_numeric( $group_category_id ) || ! is_numeric( $product_id ) 
				||  empty( $group_category_id ) || empty( $product_id ) ) { continue; }
				$product_by_group_category[ intval( $group_category_id ) ] = intval( $product_id );
			}
		}
		$field_data[ 'product_by_group_category' ] = maybe_serialize( $product_by_group_category );
	}
	return $field_data;
}
add_filter( 'bookacti_sanitized_field_data', 'bookacti_sanitize_wc_field_data', 10, 2 );


/**
 * Add new possible actions when the user clicks an event
 * @since 1.7.0
 * @param array $args
 * @param array $params
 * @return array
 */
function bookacti_add_wc_actions_to_on_event_click_field_options( $args, $params ) {
	$args[ 'options' ][ 'redirect_to_product_page' ] = esc_html__( 'Redirect to product page', BOOKACTI_PLUGIN_NAME );
	return $args;
}
add_filter( 'bookacti_event_click_actions_field', 'bookacti_add_wc_actions_to_on_event_click_field_options', 10, 2 );


/**
 * Add columns to the activity redirect URL table
 * @since 1.7.0
 * @param array $url_array
 * @param array $params
 * @return array
 */
function bookacti_add_wc_columns_to_activity_redirect_url_table( $url_array, $params = array() ) {
	$url_array[ 'head' ][ 'product' ] = esc_html__( 'Bound product', BOOKACTI_PLUGIN_NAME );
	
	// Get the product selectbox
	$args = array(
		'field_name'		=> 'product_by_activity[0]',
		'selected'			=> '',
		'show_option_none'	=> esc_html_x( 'None', 'About product', BOOKACTI_PLUGIN_NAME ),
		'option_none_value'	=> '',
		'echo'				=> 0
	);
	$products = wc_get_products( $args );
	$default_product_selectbox	= bookacti_display_product_selectbox( $args, $products );
	
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
		$url_array[ 'body' ][ $i ][ 'product' ] = $activity_id ? bookacti_display_product_selectbox( array_merge( $args, array( 'field_name' => 'product_by_activity[' . $activity_id . ']', 'selected' => $selected ) ), $products ) : $default_product_selectbox;
	}
	
	return $url_array;
}
add_filter( 'bookacti_activity_redirect_url_table', 'bookacti_add_wc_columns_to_activity_redirect_url_table', 10, 2 );


/**
 * Add columns to the group category redirect URL table
 * @since 1.7.0
 * @param array $url_array
 * @param array $params
 * @return array
 */
function bookacti_add_wc_columns_to_group_activity_redirect_url_table( $url_array, $params = array() ) {
	$url_array[ 'head' ][ 'product' ] = esc_html__( 'Bound product', BOOKACTI_PLUGIN_NAME );
	
	$args = array(
		'field_name'		=> 'product_by_group_category[0]',
		'selected'			=> '',
		'show_option_none'	=> esc_html_x( 'None', 'About product', BOOKACTI_PLUGIN_NAME ),
		'option_none_value'	=> '',
		'echo'				=> 0
	);
	$products = wc_get_products( $args );
	$default_product_selectbox	= bookacti_display_product_selectbox( $args, $products );
	
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
		$url_array[ 'body' ][ $i ][ 'product' ] = ! empty( $product_by_group_category[ $group_category_id ] ) ? bookacti_display_product_selectbox( array_merge( $args, array( 'field_name' => 'product_by_group_category[' . $group_category_id . ']', 'selected' => $selected ) ), $products ) : $default_product_selectbox;
	}
	
	return $url_array;
}
add_filter( 'bookacti_group_category_redirect_url_table', 'bookacti_add_wc_columns_to_group_activity_redirect_url_table', 10, 2 );