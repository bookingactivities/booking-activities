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
		'cancellation'	=> __( 'Cancellation', BOOKACTI_PLUGIN_NAME )
	) );

	//Display the tabs
	echo '<h2 class="nav-tab-wrapper bookacti-nav-tab-wrapper">';
	foreach ( $tabs as $tab_id => $tab_title ) {

		$active_tab_class = '';
		if( $tab_id === $active_tab ) { $active_tab_class = 'nav-tab-active'; }
		echo "<a href='" . esc_url( "?page=bookacti_settings&tab=" . $tab_id ) . "' class='nav-tab " . esc_attr( $active_tab_class ) . "'>" . esc_html( $tab_title ) . "</a>";
	}
	echo '</h2>';
	

	//Display the tabs content
	echo "<form method='post' action='options.php'>";

		if( $active_tab === 'general' ) {  

			settings_fields( 'bookacti_general_settings' );
			do_settings_sections( 'bookacti_general_settings' ); 

		} else if( $active_tab === 'cancellation' ) {

			settings_fields( 'bookacti_cancellation_settings' );
			do_settings_sections( 'bookacti_cancellation_settings' ); 

		}
		
		do_action( 'bookacti_settings_tab_content', $active_tab );

		submit_button(); 

	echo '</form>';
			
echo '</div>'; 