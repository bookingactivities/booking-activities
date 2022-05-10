<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Define translation plugin
 * @since 1.14.0
 * @param string $translation_plugin
 * @return string
 */
function bookacti_define_translation_plugin( $translation_plugin = '' ) {
	if( ! $translation_plugin ) {
			if( bookacti_is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) { $translation_plugin = 'wpml'; }
	   else if( bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' ) 
	         || bookacti_is_plugin_active( 'qtranslate-xt/qtranslate.php' ) )             { $translation_plugin = 'qtranslate'; }
	}
	return $translation_plugin;
}
add_filter( 'bookacti_translation_plugin', 'bookacti_define_translation_plugin', 10, 1 );


/**
 * Translate a string into the desired language (default to current site language) with qTranslate-XT or WPML
 * @since 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function bookacti_translate_text_with_plugin( $text, $lang = '', $fallback = true, $args = array() ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( $plugin ) {
		// Keep only the lang code, not country indicator
		$lang_code = $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ? substr( $lang, 0, strpos( $lang, '_' ) ) : $lang;
		
		if( $plugin === 'qtranslate' ) {
			$text = bookacti_translate_text_with_qtranslate( $text, $lang_code, $fallback );
		} else if( $plugin === 'wpml' ) {
			$text = bookacti_translate_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'bookacti_translate_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_plugin', 10, 4 );


/**
 * Translate a string external to Booking Activities into the desired language (default to current site language) with qTranslate-XT or WPML
 * @since 1.14.0
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function bookacti_translate_external_text_with_plugin( $text, $lang = '', $fallback = true, $args = array() ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( $plugin ) {
		// Keep only the lang code, not country indicator
		$lang_code = $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ? substr( $lang, 0, strpos( $lang, '_' ) ) : $lang;
		
		if( $plugin === 'qtranslate' ) {
			$text = bookacti_translate_text_with_qtranslate( $text, $lang_code, $fallback );
		} else if( $plugin === 'wpml' ) {
			$text = bookacti_translate_external_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'bookacti_translate_external_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'bookacti_translate_text_external', 'bookacti_translate_external_text_with_plugin', 10, 4 );


/**
 * Get active locales with plugin
 * @since 1.14.0
 * @global array $q_config
 * @param array $locales
 * @param boolean $with_locale
 * @return array
 */
function bookacti_active_locales_with_plugin( $locales, $with_locale ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( $plugin === 'wpml' ) {
		$languages = apply_filters( 'wpml_active_languages', array() );
		if( $languages ) {
			foreach( $languages as $lang_code => $locale_data ) {
				if( $locale_data[ 'active' ] ) { $locales[] = $with_locale ? $locale_data[ 'default_locale' ] : $lang_code; }
			}
		}
	} 
	else if( $plugin === 'qtranslate' ) {
		$languages = function_exists( 'qtranxf_getSortedLanguages' ) ? qtranxf_getSortedLanguages() : array();
		if( $languages && $with_locale ) {
			global $q_config;
			foreach( $languages as $i => $lang_code ) {
				if( ! empty( $q_config[ 'locale' ][ $lang_code ] ) ) { $languages[ $i ] = $q_config[ 'locale' ][ $lang_code ]; }
			}
		}
	}
	
	return $locales;
}
add_filter( 'bookacti_active_locales', 'bookacti_active_locales_with_plugin', 10, 2 );


/**
 * Get current lang code with plugin
 * @since 1.14.0
 * @global array $q_config
 * @param string $lang_code
 * @param boolean $with_locale
 * @return string
 */
function bookacti_current_lang_code_with_plugin( $lang_code, $with_locale ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( $plugin === 'wpml' ) {
		$lang_code = apply_filters( 'wpml_current_language', '' );
		if( $lang_code && $with_locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( ! empty( $languages[ $lang_code ][ 'default_locale' ] ) ) { $lang_code = $languages[ $lang_code ][ 'default_locale' ]; }
		}
	}
	else if( $plugin === 'qtranslate' ) {
		$lang_code = function_exists( 'qtranxf_getLanguage' ) ? qtranxf_getLanguage() : '';
		if( $lang_code && $with_locale ) {
			global $q_config;
			if( isset( $q_config[ 'locale' ][ $lang_code ] ) ) { $lang_code = $q_config[ 'locale' ][ $lang_code ]; }
		}
	}
	
	return $lang_code;
}
add_filter( 'bookacti_current_lang_code', 'bookacti_current_lang_code_with_plugin', 10, 2 );


/**
 * Get site default locale with WPML
 * @since 1.14.0
 * @global array $q_config
 * @param string $locale
 * @param boolean $with_locale
 * @return string
 */
function bookacti_site_default_locale_with_plugin( $locale, $with_locale ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( $plugin === 'wpml' ) {
		$locale = apply_filters( 'wpml_default_language', '' );
		if( $with_locale && $locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( $languages && ! empty( $languages[ $locale ][ 'default_locale' ] ) ) { $locale = $languages[ $locale ][ 'default_locale' ]; }
		}
	}
	else if( $plugin === 'qtranslate' ) {
		global $q_config;
		if( $q_config && ! empty( $q_config[ 'default_language' ] ) ) { 
			$locale = $q_config[ 'default_language' ];
			if( $with_locale && $locale ) {
				if( ! empty( $q_config[ 'locale' ][ $locale ] ) ) { $locale = $q_config[ 'locale' ][ $locale ]; }
			}
		}
	}
	
	return $locale;
}
add_filter( 'bookacti_site_default_locale', 'bookacti_site_default_locale_with_plugin', 10, 2 );




// WPML

/**
 * Add WPML settings section
 * @since 1.14.0
 */
function bookacti_add_wpml_settings_section() {
	if( bookacti_get_translation_plugin() !== 'wpml' ) { return; }
	
	add_settings_section( 
		'bookacti_settings_section_wpml_general',
		'WPML',
		'bookacti_settings_section_wpml_general_callback',
		'bookacti_general_settings'
	);
	
	add_settings_field( 
		'wpml_register_translatable_texts',
		esc_html__( 'Search translatable texts', 'booking-activities' ),
		'bookacti_settings_wpml_register_translatable_texts_callback',
		'bookacti_general_settings',
		'bookacti_settings_section_wpml_general'
	);
}
add_action( 'bookacti_add_settings', 'bookacti_add_wpml_settings_section' );


/**
 * Register all translatable texts - on WPML String Translation page
 * @since 1.14.0
 */
function bookacti_wpml_controller_register_all_translatable_texts() {
	// Make sure we are on WPML String Translation page, "Booking Activities" domain, and register_all parameter is set to "1"
	if( empty( $_GET[ 'page' ] ) || empty( $_GET[ 'context' ] ) || empty( $_GET[ 'register_all' ] ) ) { return; }
	if( $_GET[ 'page' ]    !== WPML_ST_FOLDER . '/menu/string-translation.php' ) { return; }
	if( $_GET[ 'context' ] !== 'Booking Activities' ) { return; }
	
	$texts = bookacti_get_translatable_texts();
	
	if( $texts ) {
		foreach( $texts as $text ) {
			$string_value  = ! empty( $text[ 'value' ] ) ? $text[ 'value' ] : '';
			$string_domain = ! empty( $text[ 'domain' ] ) ? $text[ 'domain' ] : 'Booking Activities';
			$string_name   = ! empty( $text[ 'string_name' ] ) ? $text[ 'string_name' ] : $string_value;
			$fallback      = isset( $text[ 'fallback' ] ) ? boolval( $text[ 'fallback' ] ) : 1;
			if( $string_value && is_string( $string_value ) ) {
				do_action( 'wpml_register_single_string', $string_domain, $string_name, $string_value, ! $fallback );
			}
		}
	}
}
add_action( 'admin_init', 'bookacti_wpml_controller_register_all_translatable_texts' );


/**
 * Remove translated products from select2 product selectbox
 * @since 1.14.0
 * @global woocommerce_wpml $woocommerce_wpml
 * @param type $options
 * @param type $args
 * @return type
 */
function bookacti_wpml_select2_remove_translated_products( $options, $args ) {
	global $woocommerce_wpml;
	if( ! $woocommerce_wpml ) { return $options; }
	
	foreach( $options as $i => $option ) {
		$product_id = isset( $option[ 'children' ][ 0 ][ 'id' ] ) ? intval($option[ 'children' ][ 0 ][ 'id' ] ) : ( isset( $option[ 'id' ] ) ? intval( $option[ 'id' ] ) : 0 );
		if( ! $product_id || ( $product_id && ! $woocommerce_wpml->products->is_original_product( $product_id ) ) ) {
			unset( $options[ $i ] );
		}
	}
	
	return array_values( $options );
}
add_filter( 'bookacti_ajax_select2_products_options', 'bookacti_wpml_select2_remove_translated_products', 100, 2 );