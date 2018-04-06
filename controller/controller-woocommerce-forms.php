<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Add a description how to embbed forms to woocommerce products
 * @since 1.5.0
 * @param object $form
 */
function bookacti_display_wc_form_integration_description( $form ) {
?>
	<h4><?php _e( 'Integrate with a WooCommerce product', BOOKACTI_PLUGIN_NAME ) ?></h4>
	<div>
		<p><em><?php esc_html_e( 'Select this form in your product data, in the "Activity" tab.', BOOKACTI_PLUGIN_NAME ); ?></em></p>
		<p id='bookacti-form-wc-integration-tuto' >
			<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/wc-integration.png' ); ?>' />
		</p>
	</div>
<?php
}
add_action( 'bookacti_after_form_integration_tuto', 'bookacti_display_wc_form_integration_description', 10, 1 );


/**
 * Add a WooCommerce-related description to the form editor
 * @since 1.5.0
 * @param object $form
 */
function bookacti_form_editor_wc_description( $form ) {
	echo '<p>' . sprintf( __( 'The fields with this icon %1$s will NOT appear on WooCommerce product pages.', BOOKACTI_PLUGIN_NAME ), '<span class="bookacti-wc-icon-not-supported"></span>' );
	bookacti_help_tip( __( 'These fields already exist in WooCommerce. E.g.: Quantity and Submit are already part of product pages. Login, Phone and Address are already asked on checkout page...', BOOKACTI_PLUGIN_NAME ) );
	echo '</p>';
}
add_action( 'bookacti_form_editor_description_after', 'bookacti_form_editor_wc_description', 10, 1 );


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