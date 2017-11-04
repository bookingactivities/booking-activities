<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

echo "<div class='wrap'>";
	
	echo "<h1>" . esc_html__( 'Settings', BOOKACTI_PLUGIN_NAME ) . "</h1>";

	settings_errors();

	$active_tab = 'general';
	if( isset( $_GET[ 'tab' ] ) ) { 
		$active_tab = sanitize_title_with_dashes( $_GET[ 'tab' ] ); 
	}

	//Define the tabs here: 'tab slug' => 'tab title'
	$tabs = apply_filters( 'bookacti_settings_tabs', array ( 
		/* translators: Used for a category of parameters */
		'general'		=> __( 'General', BOOKACTI_PLUGIN_NAME ),
		'cancellation'	=> __( 'Cancellation', BOOKACTI_PLUGIN_NAME ),
		'notifications'	=> __( 'Notifications', BOOKACTI_PLUGIN_NAME ),
		'messages'		=> __( 'Messages', BOOKACTI_PLUGIN_NAME )
	) );

	//Display the tabs
	echo '<h2 class="nav-tab-wrapper bookacti-nav-tab-wrapper">';
	foreach ( $tabs as $tab_id => $tab_title ) {

		$active_tab_class = '';
		if( $tab_id === $active_tab ) { $active_tab_class = 'nav-tab-active'; }
		echo "<a href='" . esc_url( "?page=bookacti_settings&tab=" . $tab_id ) . "' class='nav-tab " . esc_attr( $active_tab_class ) . "'>" . esc_html( $tab_title ) . "</a>";
	}
	echo '</h2>';
	
	$save_with_ajax	= isset( $_GET[ 'notification_id' ] ) ? 'bookacti_save_settings_with_ajax' : '';
	$action			= $save_with_ajax ? '' : 'options.php';
	
	echo "<form method='post' action='" . $action . "' id='bookacti-settings' class='" . $save_with_ajax . "' >";
	
		// Display the tabs content
		if( $active_tab === 'general' ) {  
			
			echo '<div id="bookacti-settings-lang-switcher" ></div>';
			
			settings_fields( 'bookacti_general_settings' );
			do_settings_sections( 'bookacti_general_settings' ); 

		} else if( $active_tab === 'cancellation' ) {

			settings_fields( 'bookacti_cancellation_settings' );
			do_settings_sections( 'bookacti_cancellation_settings' ); 

		} else if( $active_tab === 'notifications' ) {
			
			if( isset( $_GET[ 'notification_id' ] ) ) {
				
				$notification_id = sanitize_title_with_dashes( $_GET[ 'notification_id' ] );
				
				echo '<input type="hidden" name="option_page" value="' . esc_attr( 'bookacti_notifications_settings_' . $notification_id ) . '" />';
				echo '<input type="hidden" name="notification_id" value="' . $notification_id . '" />';
				echo '<input type="hidden" name="action" value="bookactiUpdateNotification" />';
				wp_nonce_field( 'bookacti_notifications_settings_' . $notification_id );
				
				do_action( 'bookacti_notification_settings_page', $notification_id );
				
			} else {
				
				settings_fields( 'bookacti_notifications_settings' );
				do_settings_sections( 'bookacti_notifications_settings' );
				
			}

		} else if( $active_tab === 'messages' ) {

			settings_fields( 'bookacti_messages_settings' );
			do_settings_sections( 'bookacti_messages_settings' ); 
			
			do_action( 'bookacti_messages_settings' );

		}
		
		do_action( 'bookacti_settings_tab_content', $active_tab );

		submit_button(); 

	echo '</form>';
			
echo '</div>'; 