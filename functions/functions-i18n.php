<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GLOBAL

/**
 * Get all translatable texts
 * @since 1.14.0
 * @return array
 */
function bookacti_get_translatable_texts() {
	// Get data to translate in the default language
	$lang_switched = bookacti_switch_locale( bookacti_get_site_default_locale() );
	
	$texts      = array();
	$alloptions = wp_load_alloptions();
	
	// Get Notifications strings
	$notifications_raw = array();
	foreach( $alloptions as $option_key => $option_value ) {
		if( substr( $option_key, 0, 32 ) !== 'bookacti_notifications_settings_' ) { continue; }
		$notification_texts = array();
		$notification_id    = substr( $option_key, 32 );
		$notification_raw   = maybe_unserialize( $option_value );
		$notifications_raw[ $notification_id ] = $notification_raw;
		
		if( ! empty( $notification_raw[ 'email' ][ 'subject' ] ) ) { $texts[] = array( 'value' => $notification_raw[ 'email' ][ 'subject' ], 'string_name' => 'Notification - ' . $notification_id . ' - email subject' ); }
		if( ! empty( $notification_raw[ 'email' ][ 'message' ] ) ) { $texts[] = array( 'value' => $notification_raw[ 'email' ][ 'message' ], 'string_name' => 'Notification - ' . $notification_id . ' - email message' ); }
		
		$notification_texts = apply_filters( 'bookacti_translatable_texts_notification', $notification_texts, $notification_raw, $notification_id );
		if( $notification_texts ) { $texts = array_merge( $texts, $notification_texts ); }
	}
	
	// Get Messages strings
	$messages = isset( $alloptions[ 'bookacti_messages_settings' ] ) ? maybe_unserialize( $alloptions[ 'bookacti_messages_settings' ] ) : array();
	if( $messages ) {
		$messages_texts = array();
		foreach( $messages as $message_id => $message ) {
			if( $message ) { $messages_texts[] = array( 'value' => $message, 'string_name' => 'Message - ' . $message_id ); }
		}
		$messages_texts = apply_filters( 'bookacti_translatable_texts_messages', $messages_texts, $messages );
		if( $messages_texts ) { $texts = array_merge( $texts, $messages_texts ); }
	}
	
	// Get forms and form fields strings
	$forms_raw = bookacti_get_forms( bookacti_format_form_filters( array( 'active' => 1 ) ) );
	if( $forms_raw ) {
		$register_defaults   = bookacti_get_register_fields_default_data( 'edit' );
		$log_in_defaults     = bookacti_get_log_in_fields_default_data( 'edit' );
		$login_type_defaults = bookacti_get_login_type_field_default_options( array(), 'edit' );
		$login_subfields     = array_merge( $register_defaults, $log_in_defaults, $login_type_defaults );
		
		foreach( $forms_raw as $i => $form_raw ) {
			// Get forms strings
			$form_texts = array();
			$form_raw   = (array) $form_raw;
			$form_id    = $form_raw[ 'id' ];
			$form_meta  = bookacti_get_metadata( 'form', $form_id );
			$fields_raw = bookacti_get_form_fields_data( $form_id, true, false, true );
			if( is_array( $form_meta ) ) { $form_raw = array_merge( $form_raw, $form_meta ); }
			$form_raw[ 'fields_raw' ] = $fields_raw;
			$forms_raw[ $i ] = $form_raw;
			
			if( ! empty( $form_raw[ 'redirect_url' ] ) ) { $form_texts[] = array( 'value' => $form_raw[ 'redirect_url' ] ); }
			
			$form_texts = apply_filters( 'bookacti_translatable_texts_form', $form_texts, $form_raw, $fields_raw );
			if( $form_texts ) { $texts = array_merge( $texts, $form_texts ); }
			
			// Get fields strings
			foreach( $fields_raw as $field_id => $field_raw ) {
				$field_texts = array();
				
				if( ! empty( $field_raw[ 'title' ] )       && is_string( $field_raw[ 'title' ] ) )       { $field_texts[] = array( 'value' => $field_raw[ 'title' ] ); }
				if( ! empty( $field_raw[ 'label' ] )       && is_string( $field_raw[ 'label' ] ) )       { $field_texts[] = array( 'value' => $field_raw[ 'label' ] ); }
				if( ! empty( $field_raw[ 'placeholder' ] ) && is_string( $field_raw[ 'placeholder' ] ) ) { $field_texts[] = array( 'value' => $field_raw[ 'placeholder' ] ); }
				if( ! empty( $field_raw[ 'tip' ] )         && is_string( $field_raw[ 'tip' ] ) )         { $field_texts[] = array( 'value' => $field_raw[ 'tip' ] ); }
				
				if( $field_raw[ 'type' ] === 'login' ) {
					if( ! empty( $field_raw[ 'login_button_label' ] )    && is_string( $field_raw[ 'login_button_label' ] ) )    { $field_texts[] = array( 'value' => $field_raw[ 'login_button_label' ] ); }
					if( ! empty( $field_raw[ 'register_button_label' ] ) && is_string( $field_raw[ 'register_button_label' ] ) ) { $field_texts[] = array( 'value' => $field_raw[ 'register_button_label' ] ); }
					
					foreach( $login_subfields as $subfield_name => $subfield_default ) {
						if( ! empty( $field_raw[ 'label' ][ $subfield_name ] )       && is_string( $field_raw[ 'label' ][ $subfield_name ] ) )       { $field_texts[] = array( 'value' => $field_raw[ 'label' ][ $subfield_name ] ); }
						if( ! empty( $field_raw[ 'placeholder' ][ $subfield_name ] ) && is_string( $field_raw[ 'placeholder' ][ $subfield_name ] ) ) { $field_texts[] = array( 'value' => $field_raw[ 'placeholder' ][ $subfield_name ] ); }
						if( ! empty( $field_raw[ 'tip' ][ $subfield_name ] )         && is_string( $field_raw[ 'tip' ][ $subfield_name ] ) )         { $field_texts[] = array( 'value' => $field_raw[ 'tip' ][ $subfield_name ] ); }
					
					}
				}
				if( $field_raw[ 'type' ] === 'submit' ) {
					if( ! empty( $field_raw[ 'value' ] ) && is_string( $field_raw[ 'value' ] ) ) { $field_texts[] = array( 'value' => $field_raw[ 'value' ] ); }
				}
				if( $field_raw[ 'type' ] === 'free_text' ) {
					if( ! empty( $field_raw[ 'value' ] ) && is_string( $field_raw[ 'value' ] ) ) { $field_texts[] = array( 'value' => $field_raw[ 'value' ], 'string_name' => 'Form field #' . $field_raw[ 'field_id' ] . ' - value' ); }
				}
				
				$field_texts = apply_filters( 'bookacti_translatable_texts_form_field', $field_texts, $field_raw, $form_raw );
				if( $field_texts ) { $texts = array_merge( $texts, $field_texts ); }
			}
		}
	}
	
	// Get templates strings
	$templates = bookacti_get_templates_data( array(), true );
	foreach( $templates as $template_id => $template ) {
		$template_texts = array();
		
		if( ! empty( $template[ 'multilingual_title' ] ) ) { $template_texts[] = array( 'value' => $template[ 'multilingual_title' ] ); }
		
		$template_texts = apply_filters( 'bookacti_translatable_texts_template', $template_texts, $template );
		if( $template_texts ) { $texts = array_merge( $texts, $template_texts ); }
	}
	
	// Get activities strings
	$activities = $templates ? bookacti_get_activities_by_template( array_keys( $templates ) ) : array();
	foreach( $activities as $activity_id => $activity ) {
		$activity_texts = array();
		
		if( ! empty( $activity[ 'multilingual_title' ] ) ) { $activity_texts[] = array( 'value' => $activity[ 'multilingual_title' ] ); }
		
		$activity_texts = apply_filters( 'bookacti_translatable_texts_activity', $activity_texts, $activity );
		if( $activity_texts ) { $texts = array_merge( $texts, $activity_texts ); }
	}
	
	// Get events strings
	$events = bookacti_fetch_events( array( 'data_only' => 1, 'past_events' => 0 ) );
	if( ! empty( $events[ 'data' ] ) ) {
		foreach( $events[ 'data' ] as $event_id => $event ) {
			$event_texts = array();

			if( ! empty( $event[ 'multilingual_title' ] ) ) { $event_texts[] = array( 'value' => $event[ 'multilingual_title' ] ); }

			$event_texts = apply_filters( 'bookacti_translatable_texts_event', $event_texts, $event );
			if( $event_texts ) { $texts = array_merge( $texts, $event_texts ); }
		}
	}
	
	// Get groups categories strings
	$categories = bookacti_get_group_categories();
	foreach( $categories as $category_id => $category ) {
		$category_texts = array();
		
		if( ! empty( $category[ 'multilingual_title' ] ) ) { $category_texts[] = array( 'value' => $category[ 'multilingual_title' ] ); }
		
		$category_texts = apply_filters( 'bookacti_translatable_texts_group_category', $category_texts, $category );
		if( $category_texts ) { $texts = array_merge( $texts, $category_texts ); }
	}
	
	// Get groups of events strings
	$groups = bookacti_get_groups_of_events( array( 'data_only' => 1, 'past_events' => 0 ) );
	if( ! empty( $groups[ 'data' ] ) ) {
		foreach( $groups[ 'data' ] as $group_id => $group ) {
			$group_texts = array();

			if( ! empty( $group[ 'multilingual_title' ] ) ) { $group_texts[] = array( 'value' => $group[ 'multilingual_title' ] ); }

			$group_texts = apply_filters( 'bookacti_translatable_texts_group_of_events', $group_texts, $group );
			if( $group_texts ) { $texts = array_merge( $texts, $group_texts ); }
		}
	}
	
	$data = array(
		'alloptions'       => $alloptions,
		'notifications'    => $notifications_raw,
		'messages'         => $messages,
		'forms'            => $forms_raw,
		'templates'        => $templates,
		'activities'       => $activities,
		'group_categories' => $categories,
		'groups_of_events' => $groups,
		'events'           => $events
	);
	
	$texts = apply_filters( 'bookacti_translatable_texts', $texts, $data );
	
	if( $lang_switched ) { bookacti_restore_locale(); }
	
	return $texts;
}




// WPML

/**
 * Translate a Booking Activities string into the desired language (default to current site language) with WPML
 * @since 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. Not implemented (see bookacti_wpml_fallback_text filter). False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function bookacti_translate_text_with_wpml( $text, $lang = '', $fallback = true, $args = array() ) {
	if( ! $text ) { return $text; }
	
	// Get current language
	if( ! $lang ) { $lang = bookacti_get_current_lang_code(); }
	
	// Translate
	$string_name     = ! empty( $args[ 'string_name' ] ) ? $args[ 'string_name' ] : $text;
	$translated_text = apply_filters( 'wpml_translate_single_string', $text, 'Booking Activities', $string_name, $lang );
	
	// WPML returns the original text if the translation is not found, 
	// but we don't know if that string is actually not registered, or if the translation is actually the same as the original
	if( $text === $translated_text ) {
		// Register the string (it's ok if it's already registered)
		do_action( 'wpml_register_single_string', 'Booking Activities', $string_name, $text );
		
		$default_lang_code = bookacti_get_site_default_locale( false );
		if( $lang !== $default_lang_code && ! $fallback ) {
			// You may want to return an empty string here instead of the original text
			$translated_text = apply_filters( 'bookacti_wpml_fallback_text', $translated_text, $lang, $args );
		}
	}

	return $translated_text;
}


/**
 * Translate a non-Booking Activities string into the desired language with WPML (default to current site language)
 * @since 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function bookacti_translate_external_text_with_wpml( $text, $lang = '', $fallback = true, $args = array() ) {
	// Get current language
	if( ! $lang ) { $lang = bookacti_get_current_lang_code(); }
	
	$default_args    = array( 'domain' => '', 'string_name' => '', 'object_type' => '', 'object_id' => 0, 'field' => '' );
	$args            = wp_parse_args( $args, $default_args );
	$translated_text = $fallback ? $text : '';
	
	// Wordpress texts
	if( $args[ 'domain' ] === 'wordpress' ) {
		// WP options
		if( $args[ 'field' ] === 'blogname' ) { $translated_text = apply_filters( 'wpml_translate_single_string', $text, 'WP', 'Blog Title', $lang ); }
		
		// Posts
		if( intval( $args[ 'object_id' ] ) && in_array( $args[ 'object_type' ], array( 'page', 'post' ), true ) ) {
			$translated_object_id = apply_filters( 'wpml_object_id', intval( $args[ 'object_id' ] ), $args[ 'object_type' ], false, $lang );
			if( $translated_object_id ) {
				$translated_post = get_post( $translated_object_id );
				if( isset( $translated_post->{ $args[ 'field' ] } ) ) {
					$translated_text = $fallback && ! $translated_post->{ $args[ 'field' ] } ? $text : $translated_post->{ $args[ 'field' ] };
				}
			}
		}
	}
	
	return apply_filters( 'bookacti_translate_external_text_with_wpml', $translated_text, $text, $lang, $fallback, $args );
}


/**
 * Translate a WooCommerce string into the desired language with WPML (default to current site language)
 * @since 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function bookacti_translate_wc_text_with_wpml( $translated_text, $text, $lang, $fallback, $args ) {
	if( $args[ 'domain' ] !== 'woocommerce' ) { return $translated_text; }
	
	// Translate product and product_variation
	if( intval( $args[ 'object_id' ] ) && in_array( $args[ 'object_type' ], array( 'product', 'product_variation' ), true ) ) {
		$translated_object_id = apply_filters( 'wpml_object_id', intval( $args[ 'object_id' ] ), $args[ 'object_type' ], false, $lang );
		if( $translated_object_id ) {
			$translated_post = get_post( $translated_object_id );
			if( isset( $translated_post->{ $args[ 'field' ] } ) ) {
				$translated_text = $fallback && ! $translated_post->{ $args[ 'field' ] } ? $text : $translated_post->{ $args[ 'field' ] };
			}
		}
	}
	
	// No need to translate order_item, product_attribute_key, product_attribute_option
	
	return $translated_text;
}
add_filter( 'bookacti_translate_external_text_with_wpml', 'bookacti_translate_wc_text_with_wpml', 10, 5 );


/**
 * Display "WPML" settings section
 * @since 1.14.0
 */
function bookacti_settings_section_wpml_general_callback() {}


/**
 * Display "Register translatable texts" setting
 * @since 1.14.0
 */
function bookacti_settings_wpml_register_translatable_texts_callback() {
	if( ! bookacti_is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
	?>
		<div>
		<?php 
			/* translators: %s = external link to "WPML String Translation" */ 
			echo sprintf( esc_html__( 'You need the %s add-on to translate user generated content.', 'booking-activities' ), '<strong><a href="https://wpml.org/documentation/getting-started-guide/string-translation/" target="_blank">WPML String Translation</a></strong>' );
		?>
		</div>
	<?php
	} else {
	?>
		<a href='<?php echo admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=Booking%20Activities&register_all=1' ); ?>' class='button secondary-button'>
			<?php esc_html_e( 'Find and translate', 'booking-activities' ); ?>
		</a>
	<?php
		bookacti_help_tip( esc_html__( 'Search all the translatable settings values that you have manually input. Then, you will be able to translate them in WPML > String Translation. This process can last several minutes and can be ressource intensive, depending on your amount of data.', 'booking-activities' ) );
	}
}




// qTranslate-XT

/**
 * Translate a string into the desired language (default to current site language) with qTranslate-XT
 * @version 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @return string
 */
function bookacti_translate_text_with_qtranslate( $text, $lang = '', $fallback = true ) {
	if( ! $text ) { return $text; }
	
	$qtranslate_show_empty = defined( 'TRANSLATE_SHOW_EMPTY' ) ? TRANSLATE_SHOW_EMPTY : 4;
	$flags = $fallback ? 0 : $qtranslate_show_empty;
	
	return apply_filters( 'translate_text', $text, $lang, $flags );
}