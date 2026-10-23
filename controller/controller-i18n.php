<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Load or reload Booking Activities language files
 * @version 1.16.35
 * @param string $locale
 */
function bookacti_load_textdomain( $locale = '' ) {
	if( ! $locale ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : ( is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		$locale = apply_filters( 'plugin_locale', $locale, 'booking-activities' );
	}
	
	// Check if locale is installed
	$locale = bookacti_is_available_locale( $locale );
	if( ! $locale ) { return; }
	
	unload_textdomain( 'booking-activities', true );
	// Load .mo from wp-content/languages/booking-activities/
	load_textdomain( 'booking-activities', WP_LANG_DIR . '/booking-activities/booking-activities-' . $locale . '.mo' );
	// Load .mo from wp-content/languages/plugins/
	// Fallback on .mo from wp-content/plugins/booking-activities/languages
	load_plugin_textdomain( 'booking-activities', false, plugin_basename( BOOKACTI_PATH ) . '/languages/' ); 
}
add_action( 'init', 'bookacti_load_textdomain', 5 );


// Backward compatibility
if( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
	add_action( 'bookacti_locale_switched', 'bookacti_load_textdomain', 10, 1 );
	add_action( 'bookacti_locale_restored', 'bookacti_load_textdomain', 10, 1 );
	
	add_filter( 'bookacti_switch_locale_callback', function( $callback ) {
		$plugin = bookacti_get_translation_plugin();
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'bookacti_wpml_switch_locale'; }
		else if( $plugin === 'qtranslate' )                          { $callback = 'bookacti_qtranxf_switch_locale'; }
		return $callback;
	} );
	
	add_filter( 'bookacti_restore_locale_callback', function( $callback ) {
		$plugin = bookacti_get_translation_plugin();
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'bookacti_wpml_restore_locale'; }
		else if( $plugin === 'qtranslate' )                          { $callback = 'bookacti_qtranxf_restore_locale'; }
		return $callback;
	} );
}


/**
 * Define translation plugin
 * @since 1.14.0
 * @version 1.19.2
 * @param string $translation_plugin
 * @return string
 */
function bookacti_define_translation_plugin( $translation_plugin = '' ) {
	if( ! $translation_plugin ) {
			if( class_exists( 'SitePress' ) )      { $translation_plugin = 'wpml'; }
	   else if( class_exists( 'Polylang' ) )       { $translation_plugin = 'polylang'; } // try to use Polylang's WPML compat API
	   else if( class_exists( 'QTX_Translator' ) ) { $translation_plugin = 'qtranslate'; }
	}
	return $translation_plugin;
}
add_filter( 'bookacti_translation_plugin', 'bookacti_define_translation_plugin', 10, 1 );


/**
 * Translate a string into the desired language (default to current site language) with plugin
 * @since 1.14.0
 * @version 1.19.2
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
		} else if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
			$text = bookacti_translate_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'bookacti_translate_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_plugin', 10, 4 );


/**
 * Translate a string external to Booking Activities into the desired language (default to current site language) with plugin
 * @since 1.14.0
 * @version 1.19.2
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
		} else if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
			$text = bookacti_translate_external_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'bookacti_translate_external_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'bookacti_translate_text_external', 'bookacti_translate_external_text_with_plugin', 10, 4 );


/**
 * Get current lang code with plugin
 * @since 1.14.0
 * @version 1.19.2
 * @global string $bookacti_locale
 * @global array $q_config
 * @param string $lang_code
 * @param boolean $with_locale
 * @return string
 */
function bookacti_current_lang_code_with_plugin( $lang_code, $with_locale ) {
	// Skip if language is temporarily switched
	global $bookacti_locale;
	if( $bookacti_locale ) { return $lang_code; }
	
	$plugin = bookacti_get_translation_plugin();
	
	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
		$wpml_lang = apply_filters( 'wpml_current_language', '' );
		$lang_code = strpos( $wpml_lang, '-' ) ? substr( $wpml_lang, 0, strpos( $wpml_lang, '-' ) ) : $wpml_lang;
		
		if( $wpml_lang && $with_locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( $languages && ! empty( $languages[ $wpml_lang ][ 'default_locale' ] ) ) {
				$lang_code = $languages[ $wpml_lang ][ 'default_locale' ];
			} else if( strpos( $wpml_lang, '-' ) ) {
				$country_code = strtoupper( substr( $wpml_lang, strpos( $wpml_lang, '-' ) + 1 ) );
				if( $country_code ) {
					$lang_code .= '_' . $country_code;
				}
			}
		}
	}
	else if( $plugin === 'qtranslate' ) {
		$lang_code = function_exists( 'qtranxf_getLanguage' ) ? qtranxf_getLanguage() : '';
		if( $lang_code && $with_locale ) {
			global $q_config;
			if( isset( $q_config[ 'locale' ][ $lang_code ] ) ) {
				$lang_code = $q_config[ 'locale' ][ $lang_code ];
			}
		}
	}
	
	return $lang_code;
}
add_filter( 'bookacti_current_lang_code', 'bookacti_current_lang_code_with_plugin', 10, 2 );


/**
 * Get site default locale with plugin
 * @since 1.14.0
 * @version 1.19.2
 * @global array $q_config
 * @param string $locale
 * @param boolean $with_locale
 * @return string
 */
function bookacti_site_default_locale_with_plugin( $locale, $with_locale ) {
	$plugin = bookacti_get_translation_plugin();
	
	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
		$wpml_lang = apply_filters( 'wpml_default_language', '' );
		$locale    = strpos( $wpml_lang, '-' ) ? substr( $wpml_lang, 0, strpos( $wpml_lang, '-' ) ) : $wpml_lang;
		
		if( $wpml_lang && $with_locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( $languages && ! empty( $languages[ $wpml_lang ][ 'default_locale' ] ) ) {
				$locale = $languages[ $wpml_lang ][ 'default_locale' ];
			} else if( strpos( $wpml_lang, '-' ) ) {
				$country_code = strtoupper( substr( $wpml_lang, strpos( $wpml_lang, '-' ) + 1 ) );
				if( $country_code ) {
					$locale .= '_' . $country_code;
				}
			}
		}
	}
	else if( $plugin === 'qtranslate' ) {
		global $q_config;
		if( $q_config && ! empty( $q_config[ 'default_language' ] ) ) { 
			$locale = $q_config[ 'default_language' ];
			if( $with_locale && $locale ) {
				if( ! empty( $q_config[ 'locale' ][ $locale ] ) ) {
					$locale = $q_config[ 'locale' ][ $locale ];
				}
			}
		}
	}
	
	return $locale;
}
add_filter( 'bookacti_site_default_locale', 'bookacti_site_default_locale_with_plugin', 10, 2 );


/**
 * Add "Translation" settings
 * @since 1.19.2 (was bookacti_add_wpml_settings_section)
 */
function bookacti_add_i18n_settings_section() {
	$plugin = bookacti_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	add_settings_section( 
		'bookacti_settings_section_i18n',
		esc_html__( 'Translation', 'booking-activities' ),
		'bookacti_settings_section_i18n_callback',
		'bookacti_general_settings',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'bookacti-settings-section-i18n'
		)
	);
	
	add_settings_field( 
		'i18n_register_translatable_texts',
		esc_html__( 'Search translatable texts', 'booking-activities' ),
		'bookacti_settings_i18n_register_translatable_texts_callback',
		'bookacti_general_settings',
		'bookacti_settings_section_i18n'
	);
}
add_action( 'bookacti_add_settings', 'bookacti_add_i18n_settings_section' );


/**
 * Display "Translation" settings section
 * @since 1.19.2 (was bookacti_settings_section_wpml_general_callback)
 */
function bookacti_settings_section_i18n_callback() {}


/**
 * Display "Register translatable texts" setting
 * @since 1.19.2 (was bookacti_settings_wpml_register_translatable_texts_callback)
 */
function bookacti_settings_i18n_register_translatable_texts_callback() {
	$plugin = bookacti_get_translation_plugin();
	$url = $string_translation_path = '';
	
	if( $plugin === 'wpml' ) {
		if( ! bookacti_is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
		?>
			<div>
			<?php 
				/* translators: %s = external link to "WPML String Translation" */ 
				echo sprintf( esc_html__( 'You need the %s add-on to translate user generated content.', 'booking-activities' ), '<strong><a href="https://wpml.org/documentation/getting-started-guide/string-translation/" target="_blank">WPML String Translation</a></strong>' );
			?>
			</div>
		<?php
			return;
		}
		
		if( defined( 'WPML_ST_FOLDER' ) ) {
			$is_wpml5 = defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '5.0', '>=' );
			$url      = $is_wpml5 ? admin_url( 'admin.php?page=' . ( defined( 'WPML_TM_FOLDER' ) ? WPML_TM_FOLDER : 'tm' ) . '/menu/main.php&tab=strings&context=Booking%20Activities&register_all=1' ) : admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=Booking%20Activities&register_all=1' );
		}
		
		/* translators: This is the path to translate strings from the backend with WPML. */
		$string_translation_path = esc_html__( 'WPML > Translations > Strings', 'booking-activities' );
	}
	
	else if( $plugin === 'polylang' ) {
		$url = admin_url( 'admin.php?page=mlang_strings&group=Booking%20Activities&register_all=1' );
		/* translators: This is the path to translate strings from the backend with Polylang. */
		$string_translation_path = esc_html__( 'Languages > Translations', 'booking-activities' );
	}
	
	if( ! $url ) { return; }
	
	// Display search button
	?>
		<a href='<?php echo esc_url( $url ); ?>' class='button secondary-button'>
			<?php esc_html_e( 'Find and translate', 'booking-activities' ); ?>
		</a>
	<?php
	/* translators: %s = a navigation path the user must follow (e.g. "Languages > Translations") */
	bookacti_help_tip( sprintf( esc_html__( 'Search all the translatable settings values that you have manually input. Then, you will be able to translate them in %s. This process can last several minutes and can be ressource intensive, depending on your amount of data.', 'booking-activities' ), $string_translation_path ) );
}


/**
 * Register all translatable texts - on WPML String Translation page
 * @since 1.19.2 (was bookacti_wpml_controller_register_all_translatable_texts)
 */
function bookacti_controller_register_all_translatable_texts() {
	$plugin = bookacti_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	// Make sure register_all parameter is set to "1"
	if( empty( $_GET[ 'register_all' ] ) ) { return; }
	
	// Make sure we are on the desired page
	if( $plugin === 'wpml' ) {
		// Make sure we are on WPML String Translation page, "Booking Activities" domain
		if( empty( $_GET[ 'page' ] ) || empty( $_GET[ 'context' ] ) || ! defined( 'WPML_ST_FOLDER' ) ) { return; }
		
		$is_wpml5 = defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '5.0', '>=' );
		$page     = $is_wpml5 ? ( defined( 'WPML_TM_FOLDER' ) ? constant( 'WPML_TM_FOLDER' ) : 'tm' ) . '/menu/main.php' : WPML_ST_FOLDER . '/menu/string-translation.php';
		
		if( $_GET[ 'page' ]    !== $page ) { return; }
		if( $_GET[ 'context' ] !== 'Booking Activities' ) { return; }
	}
	else if( $plugin === 'polylang' ) {
		// Make sure we are on Polylang String Translation page, "Booking Activities" group
		if( empty( $_GET[ 'page' ] ) || empty( $_GET[ 'group' ] ) ) { return; }
		if( $_GET[ 'page' ]  !== 'mlang_strings' ) { return; }
		if( $_GET[ 'group' ] !== 'Booking Activities' )  { return; }
	}
	
	$texts = bookacti_get_translatable_texts();
	
	if( $texts ) {
		$default_lang_code = bookacti_get_site_default_locale( false );
		foreach( $texts as $text ) {
			$string_value  = ! empty( $text[ 'value' ] ) ? $text[ 'value' ] : '';
			$string_domain = ! empty( $text[ 'domain' ] ) ? $text[ 'domain' ] : 'Booking Activities';
			$string_name   = ! empty( $text[ 'string_name' ] ) ? $text[ 'string_name' ] : $string_value;
			if( $string_value && is_string( $string_value ) ) {
				do_action( 'wpml_register_single_string', $string_domain, $string_name, $string_value, false, $default_lang_code );
			}
		}
	}
}
add_action( 'admin_init', 'bookacti_controller_register_all_translatable_texts' );


/**
 * Enqueue admin CSS for translatable inputs
 * @since 1.19.2 (was bookacti_wpml_print_admin_css)
 * @global SitePress $sitepress
 * @global PLL_Base $polylang
 */
function bookacti_enqueue_i18n_admin_css() {
	$plugin = bookacti_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	$lang_code = bookacti_get_site_default_locale( false );
	$flag_url  = '';
	
	// Get default site language flag
	if( $plugin === 'wpml' ) {
		global $sitepress;
		if( empty( $sitepress ) || ! defined( 'ICL_PLUGIN_URL' ) || ! defined( 'WPML_PLUGIN_PATH' ) ) { return; }
		
		$flag = $sitepress->get_flag( $lang_code );
		if( $flag ) {
			if( $flag->from_template ) {
				$wp_upload_dir = wp_upload_dir();
				$flag_url      = file_exists( $wp_upload_dir[ 'basedir' ] . '/flags/' . $flag->flag ) ? $wp_upload_dir[ 'baseurl' ] . '/flags/' . $flag->flag : '';
			} else {
				$flag_url = file_exists( WPML_PLUGIN_PATH . '/res/flags/' . $flag->flag ) ? ICL_PLUGIN_URL . '/res/flags/' . $flag->flag : '';
			}
		}
		if( ! $flag_url && file_exists( WPML_PLUGIN_PATH . '/res/flags/en.png' ) ) { $flag_url = ICL_PLUGIN_URL . '/res/flags/en.png'; }
	}
	
	else if( $plugin === 'polylang' ) {
		global $polylang;
		if( empty( $polylang ) ) { return; }
		
		$pll_language = $polylang->model->get_language( $lang_code );
		$flag_url     = $pll_language ? $pll_language->get_display_flag_url() : '';
	}
	
	wp_add_inline_style(
		'bookacti-css-backend',
		'.bookacti-translatable {'
			. ( $flag_url ? 'background: url("' . esc_url( $flag_url ) . '") no-repeat top ' . ( is_rtl() ? 'left' : 'right' ) .', white; background-size: clamp( 18px, 18px, 24px ) auto;' : '' )
			. 'border-' . ( is_rtl() ? 'right' : 'left' ) . ': 3px solid #418fb6 !important;'
		. '}'
	);
}
add_action( 'admin_enqueue_scripts', 'bookacti_enqueue_i18n_admin_css', 110 );




// WPML

/**
 * Remove translated products from select2 product selectbox
 * @since 1.14.0
 * @global woocommerce_wpml $woocommerce_wpml
 * @param array $options
 * @param array $args
 * @return array
 */
function bookacti_wpml_select2_remove_translated_products( $options, $args ) {
	global $woocommerce_wpml;
	if( ! $woocommerce_wpml ) { return $options; }
	
	foreach( $options as $i => $option ) {
		$product_id = isset( $option[ 'children' ][ 0 ][ 'id' ] ) ? intval( $option[ 'children' ][ 0 ][ 'id' ] ) : ( isset( $option[ 'id' ] ) ? intval( $option[ 'id' ] ) : 0 );
		if( ! $product_id || ( $product_id && ! $woocommerce_wpml->products->is_original_product( $product_id ) ) ) {
			unset( $options[ $i ] );
		}
	}
	
	return array_values( $options );
}
add_filter( 'bookacti_ajax_select2_products_options', 'bookacti_wpml_select2_remove_translated_products', 100, 2 );




// QTRANSLATE-XT

/**
 * TEMP FIX - Do not translate cron option, as it may prevent cron jobs from being deleted
 * @since 1.14.1
 */
function bookacti_qtranxf_do_not_translate_options() {
	remove_filter( 'option_cron', 'qtranxf_translate_option', 5 );
}
add_action( 'plugins_loaded', 'bookacti_qtranxf_do_not_translate_options', 10 );