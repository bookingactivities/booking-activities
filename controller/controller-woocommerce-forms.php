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
	<h3><?php _e( 'Integrate in a WooCommerce product page', BOOKACTI_PLUGIN_NAME ) ?></h3>
	<div>
		<p><em><?php esc_html_e( 'Select this form in your product data, in the "Activity" tab.', BOOKACTI_PLUGIN_NAME ); ?></em></p>
		<p id='bookacti-form-wc-integration-tuto' >
			<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/wc-integration.png' ); ?>' />
		</p>
	</div>
<?php
}
add_action( 'bookacti_after_form_integration_tuto', 'bookacti_display_wc_form_integration_description', 10, 1 );