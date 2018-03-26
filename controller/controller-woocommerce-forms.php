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
	echo '<p>' . sprintf( __( 'The fields with this icon %1$s will NOT appear on WooCommerce product page.', BOOKACTI_PLUGIN_NAME ), '<span class="bookacti-wc-icon bookacti-wc-icon-not-supported"></span>' ) . '</p>';
}
add_action( 'bookacti_form_editor_description_after', 'bookacti_form_editor_wc_description', 10, 1 );