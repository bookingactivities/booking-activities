<?php
/**
 * Booking Activities settings page
 * @version 1.15.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Settings', 'booking-activities' ); ?></h1>
	
	<?php do_action( 'bookacti_settings_page_header' ); ?>
	
	<hr class='wp-header-end'>
	
	<?php 
	// Display errors
	settings_errors();
	
	$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_title_with_dashes( $_GET[ 'tab' ] ) : 'general';
	
	// Define the ordered tabs here: 'tab slug' => 'tab title'
	$tabs = apply_filters( 'bookacti_settings_tabs', array ( 
		/* translators: Used for a category of parameters */
		'general'		=> esc_html__( 'General', 'booking-activities' ),
		'cancellation'	=> esc_html__( 'Cancellation', 'booking-activities' ),
		'notifications'	=> esc_html__( 'Notifications', 'booking-activities' ),
		'messages'		=> esc_html__( 'Messages', 'booking-activities' ),
		'licenses'		=> esc_html__( 'Licenses', 'booking-activities' )
	) );
	
	// Display the tabs
	?>
	<h2 class='nav-tab-wrapper bookacti-nav-tab-wrapper'>
		<?php
		foreach ( $tabs as $tab_id => $tab_title ) {
			$active_tab_class = $tab_id === $active_tab ? 'nav-tab-active' : '';
			?>
			<a href='<?php echo esc_url( "?page=bookacti_settings&tab=" . $tab_id ); ?>' class='nav-tab <?php echo esc_attr( $active_tab_class ); ?>'><?php echo esc_html( $tab_title ); ?></a>
			<?php 
		}
		?>
	</h2>
	
	<?php
	$save_with_ajax	= isset( $_GET[ 'notification_id' ] ) ? 'bookacti_save_settings_with_ajax' : '';
	$action			= $save_with_ajax ? '' : 'options.php';
	?>
	<form method='post' action='<?php echo $action; ?>' id='bookacti-settings' class='bookacti-settings-tab-<?php echo $active_tab . ' ' . $save_with_ajax; ?>' >
		<?php
		// Display the tabs content
		// GENERAL
		if( $active_tab === 'general' ) {
			echo '<div id="bookacti-settings-lang-switcher" ></div>';
			
			settings_fields( 'bookacti_general_settings' );
			do_settings_sections( 'bookacti_general_settings' ); 
			
		// CANCELLATION
		} else if( $active_tab === 'cancellation' ) {
			settings_fields( 'bookacti_cancellation_settings' );
			do_settings_sections( 'bookacti_cancellation_settings' ); 

		// NOTIFICATIONS
		} else if( $active_tab === 'notifications' ) {
			if( isset( $_GET[ 'notification_id' ] ) ) {
				$notification_id = sanitize_title_with_dashes( $_GET[ 'notification_id' ] );
				
				echo '<input type="hidden" name="option_page" value="' . esc_attr( 'bookacti_notifications_settings_' . $notification_id ) . '" />';
				echo '<input type="hidden" name="notification_id" value="' . $notification_id . '" />';
				echo '<input type="hidden" name="action" value="bookactiUpdateNotification" />';
				echo '<input type="hidden" name="nonce" value="' . wp_create_nonce( 'bookacti_notifications_settings_' . $notification_id ) . '" />';
				
				do_action( 'bookacti_notification_settings_page', $notification_id );
				
			} else {
				settings_fields( 'bookacti_notifications_settings' );
				do_settings_sections( 'bookacti_notifications_settings' );
			}

		// MESSAGES
		} else if( $active_tab === 'messages' ) {
			settings_fields( 'bookacti_messages_settings' );
			do_settings_sections( 'bookacti_messages_settings' ); 
			
			do_action( 'bookacti_messages_settings' );
		
		// LICENSES
		} else if( $active_tab === 'licenses' ) {
			settings_fields( 'bookacti_licenses_settings' );
			do_settings_sections( 'bookacti_licenses_settings' ); 
			
			do_action( 'bookacti_licenses_settings' );
		}
		
		do_action( 'bookacti_settings_tab_content', $active_tab );
		
		// Display the submit button
		$display_submit = apply_filters( 'bookacti_settings_display_submit_button', true, $active_tab );
		if( $display_submit ) { submit_button(); }
		?>
	</form>	
</div>