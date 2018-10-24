<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GLOBAL
	
	/**
	 * Check if plugin is active
	 * 
	 * @param string $plugin_path_and_name
	 * @return boolean
	 */
	function bookacti_is_plugin_active( $plugin_path_and_name ) {
		if( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		return is_plugin_active( $plugin_path_and_name );
	}
	
	
	/**
	 * Send a filtered array via json during an ajax process
	 * @since 1.5.0
	 * @version 1.5.3
	 * @param array $array Array to encode as JSON, then print and die.
	 * @param string $action Name of the filter to allow third-party modifications
	 */
	function bookacti_send_json( $array, $action = '' ) {
		if( empty( $array[ 'status' ] ) ) { $array[ 'status' ] = 'failed'; }
		$response = apply_filters( 'bookacti_send_json_' . $action, $array );
		wp_send_json( $response );
	}
	
	
	/**
	 * Send a filtered array via json to stop an ajax process running with an invalid nonce
	 * @since 1.5.0
	 * @version 1.5.3
	 * @param string $action Name of the filter to allow third-party modifications
	 */
	function bookacti_send_json_invalid_nonce( $action = '' ) {
		$return_array = array( 
			'status'	=> 'failed', 
			'error'		=> 'invalid_nonce', 
			'message'	=> esc_html__( 'Invalid nonce.', BOOKACTI_PLUGIN_NAME ) . ' ' . __( 'Please reload the page and try again.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_send_json( $return_array, $action );
	}
	
	
	/**
	 * Send a filtered array via json to stop a not allowed an ajax process
	 * @since 1.5.0
	 * @version 1.6.0
	 * @param string $action Name of the filter to allow third-party modifications
	 */
	function bookacti_send_json_not_allowed( $action = '' ) {
		$return_array = array( 
			'status'	=> 'failed', 
			'error'		=> 'not_allowed', 
			'message'	=> esc_html__( 'You are not allowed to do that.', BOOKACTI_PLUGIN_NAME )
		);
		bookacti_send_json( $return_array, $action );
	}

	
	/**
	 * Write logs to log files
	 * 
	 * @param string $message
	 * @param string $filename
	 * @return int
	 */
	function bookacti_log( $message, $filename = 'debug' ) {

		if( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		if( is_bool( $message ) ) {
			$message = $message ? 'true' : 'false';
		}

		$file = WP_PLUGIN_DIR . '/' . BOOKACTI_PLUGIN_NAME . '/log/' . $filename . '.log'; 

		$time = date( 'c', time() );
		$log = $time . ' - ' . $message . PHP_EOL;

		$handle	= fopen( $file, 'a' );

		$write = 0;
		if( $handle !== false ) {
			$write	= fwrite( $handle, $log );
			fclose( $handle );
		}

		return $write;
	}
	
	
	
	
// LOCALE

	/**
	 * Detect current language with Qtranslate X or WPML
	 * @version 1.6.0
	 * @param boolean $with_locale
	 * @return string 
	 */
	function bookacti_get_current_lang_code( $with_locale = false ) {
		
		$locale		= get_locale();
		$lang_code	= $with_locale ? $locale : substr( $locale, 0, strpos( $locale, '_' ) );
		
		if( bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' ) ) {
			if( function_exists( 'qtranxf_getLanguage' ) ) {
				$lang_code = qtranxf_getLanguage();
				if( $with_locale ) {
					global $q_config;
					foreach( $q_config[ 'locale' ] as $code => $locale ) {
						if( $code === $lang_code ) { $lang_code = $locale; break; }
					}
				}
			}
		} else if ( bookacti_is_plugin_active( 'wpml/wpml.php' ) ) {
			$lang_code = apply_filters( 'wpml_current_language', NULL );
			if( $with_locale ) {
				$languages = apply_filters( 'wpml_active_languages', NULL );
				foreach( $languages as $l ) {
					if( $l[ 'active' ] ) { $lang_code = $l[ 'default_locale' ]; break; }
				}
			}
		}
		
		return $lang_code;
	}
	
	
	/**
	 * Get current translation plugin identifier
	 * 
	 * @return string
	 */
	function bookacti_get_translation_plugin() {

		$translation_plugin = '';
		$is_qtranslate	= bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' );
		$is_wpml		= bookacti_is_plugin_active( 'wpml/wpml.php' );

		if ( $is_qtranslate ) {
			$translation_plugin = 'qtranslate';
		} else if ( $is_wpml ) {
			$translation_plugin = 'wpml';
		}

		return $translation_plugin;
	}


	// Translate text with QTranslate
	$is_qtranslate	= bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' );
	if( $is_qtranslate ) {
		/**
		 * Translate a string into the desired language (default to current site language)
		 * 
		 * @version 1.2.0
		 * @param string $text
		 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
		 * @return string
		 */
		function bookacti_translate_text_with_qtranslate( $text, $lang = null ) {
			if( $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ) { 
				$lang = substr( $lang, 0, strpos( $lang, '_' ) );
			}
			return apply_filters( 'translate_text', $text, $lang );
		}
		add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_qtranslate', 10, 2 );
	}
	

	/* 
	 * Get user locale, and default to site or current locale
	 * 
	 * @since 1.2.0
	 * @param int|WP_User $user_id
	 * @param string $default 'current' or 'site'
	 * @param boolean $country_code Whether to return also country code
	 * @return string
	 */
	function bookacti_get_user_locale( $user_id, $default = 'current', $country_code = true ) {

		if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		} elseif ( $user_id instanceof WP_User ) {
			$user = $user_id;
		} elseif ( $user_id && is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
		}

		if( ! $user ) { $locale = get_locale(); }
		else {
			if( $default === 'site' ) {
				// Get user locale
				$locale = strval( $user->locale );
				// If not set, get site default locale
				if( ! $locale ) {
					$alloptions	= wp_load_alloptions();
					$locale		= $alloptions[ 'WPLANG' ] ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
				}
			} else {
				// Get user locale, if not set get current locale
				$locale = $user->locale ? strval( $user->locale ) : get_locale();
			}
		}

		// Remove country code from locale string
		if( ! $country_code ) {
			$_pos = strpos( $locale, '_' );
			if( $_pos !== false ) {
				$locale = substr( $locale, 0, $_pos );
			}
		}

		return $locale;
	}


	/* 
	 * Get site locale, and default to site or current locale
	 * 
	 * @since 1.2.0
	 * @param int|WP_User $user_id
	 * @param string $default 'current' or 'site'
	 * @param boolean $country_code Whether to return also country code
	 * @return string
	 */
	function bookacti_get_site_locale( $default = 'site', $country_code = true ) {

		// Get raw site locale, or current locale by default
		if( $default === 'site' ) {
			$alloptions	= wp_load_alloptions();
			$locale		= $alloptions[ 'WPLANG' ] ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
		} else {
			$locale = get_locale();
		}
		
		// Remove country code from locale string
		if( ! $country_code ) {
			$_pos = strpos( $locale, '_' );
			if( $_pos !== false ) {
				$locale = substr( $locale, 0, $_pos );
			}
		}

		return $locale;
	}


	/**
	 * Switch Booking Activities locale
	 * 
	 * @since 1.2.0
	 */
	function bookacti_switch_locale( $locale ) {
		if ( function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( $locale );
			
			// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
			add_filter( 'plugin_locale', function() use ( &$locale ){
				return $locale;
			});
			
			bookacti_load_textdomain();
		}
	}
	
	
	/**
	 * Switch Booking Activities locale back to the original
	 * 
	 * @since 1.2.0
	 * @param string $locale
	 */
	function bookacti_restore_locale() {
		if ( function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();

			// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
			add_filter( 'plugin_locale', 'get_locale' );

			bookacti_load_textdomain();
		}
	}
	
	
	/**
	 * Get FullCalendar supported locale
	 * @since 1.5.2
	 * @return array
	 */
	function bookacti_get_fullcalendar_supported_locales() {
		return apply_filters( 'bookacti_fullcalendar_locales', array( 
			'af', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'ar', 'bg', 'bs', 'ca', 'cs', 
			'da', 'de-at', 'de-ch', 'de', 'el', 'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-nz', 'es-do', 'es-us', 'es', 'et', 'eu', 'fa', 'fi', 'fr-ca', 'fr-ch', 'fr', 
			'gl', 'he', 'hi', 'hr', 'hu', 'id', 'is', 'it', 
			'ja', 'ka', 'kk', 'ko', 'lb', 'lt', 'lv', 
			'mk', 'ms-my', 'ms', 'nb', 'nl-be', 'nl', 'nn', 
			'pl', 'pt-br', 'pt', 'ro', 'ru', 
			'sk', 'sl', 'sq', 'sr-cyrl', 'sr', 'sv', 'th', 'tr', 'uk', 
			'vi', 
			'zh-cn', 'zh-tw' 
		));
	}
	
	
	/**
	 * Convert a WP formatted locale to the closest available FullCalendar locale
	 * @since 1.5.2
	 * @param string $wp_locale
	 * @return string
	 */
	function bookacti_convert_wp_locale_to_fc_locale( $wp_locale = false ) {
		if( ! $wp_locale ) { $wp_locale = bookacti_get_site_locale(); }
		
		// Format the locale like FC locale formatting
		$fc_locale = $wp_locale;
		
		// Keep these formats "lang_COUNTRY" or "lang" only
		$pos = strpos( $wp_locale, '_', strpos( $wp_locale, '_' ) + 1 );
		if( $pos ) { $fc_locale = substr( $wp_locale, 0, $pos ); }
		
		// Replace _ by - and use lowercase only
		$fc_locale = strtolower( str_replace( '_', '-', $fc_locale ) ); 
		
		// Check if the locale exists
		$fc_locales = bookacti_get_fullcalendar_supported_locales();
		if( ! in_array( $fc_locale, $fc_locales, true ) ) {
			// Keep only the lang code
			$fc_locale = strstr( $wp_locale, '_', true );
			// Default to english if the locale doesn't exist at all
			if( ! in_array( $fc_locale, $fc_locales, true ) ) {
				$fc_locale = 'en';
			}
		}
		return apply_filters( 'bookacti_fullcalendar_locale', $fc_locale, $wp_locale );
	}




// FORMS
	/**
	 * Display fields
	 * @since 1.5.0
	 * @param array $args
	 */
	function bookacti_display_fields( $fields, $args = array() ) {
		
		if( empty( $fields ) || ! is_array( $fields ) )	{ return; }
		
		// Format parameters
		if( ! isset( $args[ 'hidden' ] ) || ! is_array( $args[ 'hidden' ] ) )	{ $args[ 'hidden' ] = array(); }
		if( ! isset( $args[ 'prefix' ] ) || ! is_string( $args[ 'prefix' ] ) )	{ $args[ 'prefix' ] = ''; }

		foreach( $fields as $field_name => $field ) {
			if( empty( $field[ 'type' ] ) ) { continue; }
			
			if( empty( $field[ 'name' ] ) ) { $field[ 'name' ] = $field_name; }
			$field[ 'name' ]	= ! empty( $args[ 'prefix' ] ) ? $args[ 'prefix' ] . '[' . $field_name . ']' : $field[ 'name' ];
			$field[ 'id' ]		= empty( $field[ 'id' ] ) ? 'bookacti-' . $field_name : $field[ 'id' ];
			$field[ 'hidden' ]	= in_array( $field_name, $args[ 'hidden' ], true ) ? 1 : 0;

			// If custom type, call another function to display this field
			if( $field[ 'type' ] === 'custom' ) {
				do_action( 'bookacti_display_custom_field', $field, $field_name );
				continue;
			}
			// Else, display standard field
		?>
			<div class='bookacti-field-container <?php if( ! empty( $field[ 'hidden' ] ) ) { echo 'bookacti-hidden-field'; } ?>' id='<?php echo $field[ 'id' ] . '-container'; ?>'>
			<?php 
			// Display field title
			if( ! empty( $field[ 'title' ] ) ) { 
			?>
				<label for='<?php echo $field[ 'id' ]; ?>' class='<?php if( $field[ 'type' ] === 'checkboxes' ) { echo 'bookacti-fullwidth-label'; } ?>' >
				<?php 
					echo $field[ 'title' ];
					if( $field[ 'type' ] === 'checkboxes' ) { bookacti_help_tip( $field[ 'tip' ] ); unset( $field[ 'tip' ] ); }
				?>
				</label>
			<?php } 
				// Display field
				bookacti_display_field( $field ); 
			?>
			</div>
		<?php
		}
	}
	
	
	/**
	 * Display various fields
	 * 
	 * @since 1.2.0
	 * @version 1.5.8
	 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'tip', 'required']
	 */
	function bookacti_display_field( $args ) {
		
		$args = bookacti_format_field_args( $args );
		
		if( ! $args ) { return; }
		
		// Display field according to type

		// TEXT & NUMBER
		if( in_array( $args[ 'type' ], array( 'text', 'hidden', 'number', 'date', 'time', 'email', 'tel', 'password', 'file' ), true ) ) {
		?>
			<input	type=		'<?php echo esc_attr( $args[ 'type' ] ); ?>' 
					name=		'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
					value=		'<?php echo esc_attr( $args[ 'value' ] ); ?>' 
					autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
					id=			'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
					class=		'bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
				<?php if( ! in_array( $args[ 'type' ], array( 'hidden', 'file' ) ) ) { ?>
					placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>' 
				<?php } 
				if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ), true ) ) { ?>
					min=		'<?php echo esc_attr( $args[ 'options' ][ 'min' ] ); ?>' 
					max=		'<?php echo esc_attr( $args[ 'options' ][ 'max' ] ); ?>'
					step=		'<?php echo esc_attr( $args[ 'options' ][ 'step' ] ); ?>'
				<?php }
				if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; }
				if( $args[ 'type' ] === 'file' && $args[ 'multiple' ] ) { echo ' multiple'; }
				if( $args[ 'required' ] ) { echo ' required'; } ?>
			/>
		<?php if( $args[ 'label' ] ) { ?>
			<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
				<?php echo $args[ 'label' ]; ?>
			</label>
		<?php
			}
		}

		// TEXTAREA
		else if( $args[ 'type' ] === 'textarea' ) {
		?>
			<textarea	
				name=		'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				id=			'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				class=		'bookacti-textarea <?php echo esc_attr( $args[ 'class' ] ); ?>' 
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>'
				<?php if( ! empty( $args[ 'attr' ] ) ) { echo $args[ 'attr' ]; } ?>
				<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
			><?php echo $args[ 'value' ]; ?></textarea>
		<?php if( $args[ 'label' ] ) { ?>
				<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
					<?php echo $args[ 'label' ]; ?>
				</label>
		<?php
			}
		}

		// SINGLE CHECKBOX (boolean)
		else if( $args[ 'type' ] === 'checkbox' ) {
			bookacti_onoffswitch( esc_attr( $args[ 'name' ] ), esc_attr( $args[ 'value' ] ), esc_attr( $args[ 'id' ] ) );
		}

		// MULTIPLE CHECKBOX
		else if( $args[ 'type' ] === 'checkboxes' ) {
			?>
			<input  name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
					id='<?php echo esc_attr( $args[ 'id' ] ) . '_none'; ?>'
					type='hidden' 
					value='none' />
			<?php
			foreach( $args[ 'options' ] as $option ) {
			?>
				<div class='bookacti_checkbox'>
					<input	name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
							id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
							class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
							type='checkbox' 
							value='<?php echo $option[ 'id' ]; ?>'
							<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
							<?php if( in_array( $option[ 'id' ], $args[ 'value' ], true ) ){ echo 'checked'; } ?>
					/>
				<?php if( ! empty( $option[ 'label' ] ) ) { ?>
					<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
						<?php echo apply_filters( 'bookacti_translate_text', $option[ 'label' ] ); ?>
					</label>
				<?php
					}
					// Display the tip
					if( ! empty( $option[ 'description' ] ) ) {
						$tip = apply_filters( 'bookacti_translate_text', $option[ 'description' ] );
						bookacti_help_tip( $tip );
					}
				?>
				</div>
			<?php
			}
		}

		// RADIO
		else if( $args[ 'type' ] === 'radio' ) {
			foreach( $args[ 'options' ] as $option ) {
			?>
				<div class='bookacti_radio'>
					<input	name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
							id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
							class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
							type='radio' 
							value='<?php echo esc_attr( $option[ 'id' ] ); ?>'
							<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo $args[ 'attr' ][ $option[ 'id' ] ]; } ?>
							<?php if( isset( $args[ 'value' ] ) ) { checked( $args[ 'value' ], $option[ 'id' ], true ); } ?>
							<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
					/>
				<?php if( $option[ 'label' ] ) { ?>
					<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
						<?php echo apply_filters( 'bookacti_translate_text', $option[ 'label' ] ); ?>
					</label>
				<?php
					}
					// Display the tip
					if( !empty( $option[ 'description' ] ) ) {
						$tip = apply_filters( 'bookacti_translate_text', $option[ 'description' ] );
						bookacti_help_tip( $tip );
					}
				?>
				</div>
			<?php
			}
		}

		// SELECT
		else if( $args[ 'type' ] === 'select' ) {
			?>
			<select	name=	'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
					id=		'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
					class=	'bookacti-select <?php echo esc_attr( $args[ 'class' ] ); ?>' 
					<?php if( ! empty( $args[ 'attr' ][ '<select>' ] ) ) { echo $args[ 'attr' ][ '<select>' ]; } ?>
					<?php if( $args[ 'multiple' ] && $args[ 'multiple' ] !== 'maybe' ) { echo 'multiple'; } ?>
					<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
			>
			<?php foreach( $args[ 'options' ] as $option_id => $option_value ) { ?>
				<option value='<?php echo esc_attr( $option_id ); ?>'
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option_id ); ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option_id ] ) ) { echo $args[ 'attr' ][ $option_id ]; } ?>
						<?php	if( $args[ 'multiple' ] ) { selected( true, in_array( $option_id, $args[ 'value' ], true ) ); }
								else { selected( $args[ 'value' ], $option_id ); }?>
				>
						<?php echo esc_html( $option_value ); ?>
				</option>
			<?php } ?>
			</select>
		<?php 
			if( $args[ 'multiple' ] === 'maybe' && count( $args[ 'options' ] ) > 1 ) { ?>
				<span class='bookacti-multiple-select-container' >
					<label for='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' ><span class='dashicons dashicons-plus' title='<?php esc_attr_e( 'Multiple selection', BOOKACTI_PLUGIN_NAME ); ?>'></span></label>
					<input type='checkbox' 
						   class='bookacti-multiple-select' 
						   id='bookacti-multiple-select-<?php echo esc_attr( $args[ 'id' ] ); ?>' 
						   data-select-id='<?php echo esc_attr( $args[ 'id' ] ); ?>'
						   style='display:none' />
				</span>
		<?php 
				// Add select multiple values instructions
				if( $args[ 'tip' ] ) {
					/* translators: %s is the "+" icon to click on. */
					$args[ 'tip' ] .= '<br/>' . sprintf( esc_html__( 'To select multiple values, click on %s and use CTRL+Click to pick or unpick a value.', BOOKACTI_PLUGIN_NAME ), '<span class="dashicons dashicons-plus"></span>' );
				}
			} 
			if( $args[ 'label' ] ) { ?>
			<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
				<?php echo apply_filters( 'bookacti_translate_text', $args[ 'label' ] ); ?>
			</label>
		<?php
			}
		}

		// TINYMCE editor
		else if( $args[ 'type' ] === 'editor' ) {
			wp_editor( $args[ 'value' ], $args[ 'id' ], $args[ 'options' ] );
		}

		// User ID
		else if( $args[ 'type' ] === 'user_id' ) {
			bookacti_display_user_selectbox( $args[ 'options' ] );
		}


		// Display the tip
		if( $args[ 'tip' ] ) {
			bookacti_help_tip( $args[ 'tip' ] );
		}
	}


	/**
	 * Format arguments to diplay a proper field
	 * 
	 * @since 1.2.0
	 * @version 1.5.4
	 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'multiple', 'tip', 'required']
	 * @return array|false
	 */
	function bookacti_format_field_args( $args ) {

		// If $args is not an array, return
		if( ! is_array( $args ) ) { return false; }

		// If fields type or name are not set, return
		if( ! isset( $args[ 'type' ] ) || ! isset( $args[ 'name' ] ) ) { return false; }

		// If field type is not supported, return
		if( ! in_array( $args[ 'type' ], array( 'text', 'hidden', 'email', 'tel', 'date', 'time', 'password', 'number', 'checkbox', 'checkboxes', 'select', 'radio', 'textarea', 'file', 'editor', 'user_id' ) ) ) { 
			return false; 
		}

		$default_args = array(
			'type'			=> '',
			'name'			=> '',
			'label'			=> '',
			'id'			=> '',
			'class'			=> '',
			'placeholder'	=> '',
			'options'		=> array(),
			'attr'			=> '',
			'value'			=> '',
			'multiple'		=> false,
			'tip'			=> '',
			'required'		=> 0,
			'autocomplete'	=> 0
		);

		// Replace empty value by default
		foreach( $default_args as $key => $default_value ) {
			$args[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $default_value;
		}
		
		// Sanitize id and name
		$args[ 'id' ] = sanitize_title_with_dashes( $args[ 'id' ] );
		
		// If no id, use name instead
		$args[ 'id' ] = $args[ 'id' ] ? $args[ 'id' ] : sanitize_title_with_dashes( $args[ 'name' ] ) . '-' . rand();
		
		// Sanitize required
		$args[ 'required' ] = isset( $args[ 'required' ] ) && $args[ 'required' ] ? 1 : 0;
		if( $args[ 'required' ] ) { $args[ 'class' ] .= ' bookacti-required-field'; }
		
		// Make sure fields with multiple options have 'options' set
		if( in_array( $args[ 'type' ], array( 'checkboxes', 'radio', 'select', 'user_id' ) ) ){
			if( ! $args[ 'options' ] ) { return false; }
			if( ! is_array( $args[ 'attr' ] ) ) { $args[ 'attr' ] = array(); }
		} else {
			if( ! is_string( $args[ 'attr' ] ) ) { $args[ 'attr' ] = ''; }
		}
		
		// If multiple, make sure name has brackets and value is an array
		if( in_array( $args[ 'multiple' ], array( 'true', true, '1', 1 ), true ) ) {
			if( strpos( '[]', $args[ 'name' ] ) === false ) {
				$args[ 'name' ]	.= '[]';
			}
		} else if( $args[ 'multiple' ] && $args[ 'type' ] === 'select' ) {
			$args[ 'multiple' ] = 'maybe';
		}
		
		// Make sure checkboxes have their value as an array
		if( $args[ 'type' ] === 'checkboxes' || ( $args[ 'multiple' ] && $args[ 'type' ] !== 'file' ) ){
			if( ! is_array( $args[ 'value' ] ) ) { 
				$args[ 'value' ] = array( $args[ 'value' ] );
			}
		}

		// Make sure 'number' has min and max
		else if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ) ) ) {
			$args[ 'options' ][ 'min' ] = isset( $args[ 'options' ][ 'min' ] ) ? $args[ 'options' ][ 'min' ] : '';
			$args[ 'options' ][ 'max' ] = isset( $args[ 'options' ][ 'max' ] ) ? $args[ 'options' ][ 'max' ] : '';
			$args[ 'options' ][ 'step' ] = isset( $args[ 'options' ][ 'step' ] ) ? $args[ 'options' ][ 'step' ] : '';
		}

		// Make sure that if 'editor' has options, options is an array
		else if( $args[ 'type' ] === 'editor' ) {
			if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
			$args[ 'options' ][ 'textarea_name' ]	= $args[ 'name' ];
			$args[ 'options' ][ 'editor_class' ]	= $args[ 'class' ];
		}

		return $args;
	}
	
	
	/**
	 * Sanitize text from HTML editor in form fields
	 * @since 1.5.2
	 * @param string $html
	 * @return string
	 */
	function bookacti_sanitize_form_field_free_text( $html ) {
		$html = wp_kses_post( stripslashes( $html ) );
		// Strip form tags
		$tags = array( 'form', 'input', 'textarea', 'select', 'option', 'output' );
		$html = preg_replace( '#<(' . implode( '|', $tags) . ')(?:[^>]+)?>.*?</\1>#s', '', $html );
		return $html;
	}
	

	/**
	 * Display help toolbox
	 * 
	 * @version 1.5.0
	 * @param string $tip
	 */
	function bookacti_help_tip( $tip, $echo = true ){
		$tip = "<span class='dashicons dashicons-editor-help bookacti-tip' data-tip='" . esc_attr( $tip ) . "'></span>";
		
		if( $echo ) { echo $tip; }
		
		return $tip;
	}

	
	/**
	 * Create ON / OFF switch
	 * @version 1.5.4
	 * @param string $name
	 * @param string $current_value
	 * @param string $id
	 * @param boolean $disabled
	 */
	function bookacti_onoffswitch( $name, $current_value, $id = NULL, $disabled = false ) {
		
		// Format current value
		$current_value = in_array( $current_value, array( true, 'true', 1, '1', 'on' ), true ) ? '1' : '0';
		
		$checked = checked( '1', $current_value, false );
		if( is_null ( $id ) || $id === '' || ! $id ) { $id = $name; }

		?>
		<div class="bookacti-onoffswitch <?php if( $disabled ) { echo 'bookacti-disabled'; } ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value='0' class='bookacti-onoffswitch-hidden-input' />
			<input type="checkbox" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   class="bookacti-onoffswitch-checkbox" 
				   id="<?php echo esc_attr( $id ); ?>" 
				   value='1' 
					<?php echo $checked; ?> 
					<?php if( $disabled ) { echo 'disabled'; } ?> 
			/>
			<label class="bookacti-onoffswitch-label" for="<?php echo esc_attr( $id ); ?>">
				<span class="bookacti-onoffswitch-inner"></span>
				<span class="bookacti-onoffswitch-switch"></span>
			</label>
		</div>
		<?php
		if( $disabled ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $current_value ) . '" />'; }
	}
	
	
	/**
	 * Create a user selectbox
	 * 
	 * @since 1.3.0
	 * @version 1.5.0
	 * @param array $args
	 * @return string|void
	 */
	function bookacti_display_user_selectbox( $args ) {
		
		$defaults = array(
			'show_option_all' => '', 'show_option_none' => '', 'option_none_value' => -1,
			'show_option_current' => '', 'option_current_value' => 'current',
			'option_label' => array( 'display_name' ), 'echo' => 1,
			'selected' => 0, 'name' => 'user_id', 'class' => '', 'id' => '',
			'include' => array(), 'exclude' => array(),
			'role' => array(), 'role__in' => array(), 'role__not_in' => array(),
			'orderby' => 'display_name', 'order' => 'ASC'
		);
		
		$args	= apply_filters( 'bookacti_user_selectbox_args', wp_parse_args( $args, $defaults ), $args );
		$users	= bookacti_get_users_data( $args );
		
		ob_start();
		?>
		
		<select <?php if( $args[ 'id' ] ) { echo 'id="' . $args[ 'id' ] . '"'; } ?> name='<?php echo $args[ 'name' ]; ?>' class='bookacti-user-selectbox <?php echo $args[ 'class' ]; ?>' >
			<option value='' ><?php echo esc_html__( 'Search for a customer', BOOKACTI_PLUGIN_NAME ); ?></option>
			<?php
				if( $args[ 'show_option_all' ] ) {
					$_selected = selected( 0, $args[ 'selected' ], false );
					?><option value='0' <?php echo $_selected ?> ><?php echo $args[ 'show_option_all' ]; ?></option><?php
				}

				if( $args[ 'show_option_none' ] ) {
					$_selected = selected( $args[ 'option_none_value' ], $args[ 'selected' ], false );
					?><option value='<?php echo esc_attr( $args[ 'option_none_value' ] ); ?>' <?php echo $_selected ?> ><?php echo $args[ 'show_option_none' ]; ?></option><?php
				}

				if( $args[ 'show_option_current' ] ) {
					$_selected = selected( $args[ 'option_current_value' ], $args[ 'selected' ], false );
					?><option value='<?php echo esc_attr( $args[ 'option_current_value' ] ); ?>' <?php echo $_selected ?> ><?php echo $args[ 'show_option_current' ]; ?></option><?php
				}
			
				do_action( 'bookacti_add_user_selectbox_options', $args );

				foreach( $users as $user ){
					$_selected = selected( $user->ID, $args[ 'selected' ], false );
					
					// Build the option label based on the array
					$label = '';
					foreach( $args[ 'option_label' ] as $show ) {
						if( preg_match( '/^[a-zA-Z0-9_]+$/' , $show ) && isset( $user->$show ) ) {
							$label .= $user->$show;
						} else {
							$label .= $show;
						}
					}
			?>
					<option value='<?php echo $user->ID; ?>' <?php echo $_selected ?> ><?php echo esc_html( $label ); ?></option>
			<?php
				}
			?>
		</select>

		<?php
		$output = ob_get_clean();
		
		// Return the output...
		if( ! $args[ 'echo' ] ) { return $output; }
		
		// ...or echo
		echo $output;
	}


	/**
	 * Display tabs and their content
	 * 
	 * @param array $tabs
	 * @param string $id
	 */
	function bookacti_display_tabs( $tabs, $id ) {

		if( ! isset( $tabs ) || ! is_array( $tabs ) || empty( $tabs ) || ! $id || ! is_string( $id ) ) {
			exit;
		}

		// Sort tabs in the desired order
		usort( $tabs, 'bookacti_sort_array_by_order' );

		echo "<div class='bookacti-tabs' >";

			//Display tabs
			echo '<ul>';
			$i = 1;
			foreach( $tabs as $tab ) {
				$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;
				echo  "<li class='bookacti-tab-" . esc_attr(  $tab_id ) . "' >"
					.	"<a href='#bookacti-tab-content-" . esc_attr(  $tab_id ) . "' >" . esc_html( $tab[ 'label' ] ) . "</a>"
					. "</li>";
				$i++;
			}
			echo '</ul>';


		//Display tab content
		$i = 1;
		foreach( $tabs as $tab ) {
			$tab_id	= isset( $tab[ 'id' ] ) ? sanitize_title_with_dashes( $tab[ 'id' ] ) : $i;

			echo "<div id='bookacti-tab-content-" . esc_attr( $tab_id ) . "' class='bookacti-tab-content' >";

			if( isset( $tab[ 'callback' ] ) && function_exists( $tab[ 'callback' ] ) ) {
				if( isset( $tab[ 'parameters' ] ) ) {
					call_user_func( $tab[ 'callback' ], $tab[ 'parameters' ] );
				} else {
					call_user_func( $tab[ 'callback' ] );
				}
			}

			echo "</div>";
			$i++;
		}

		echo '</div>';
	}
	
	
	
	
// FORMATING AND SANITIZING
	
	/**
	 * Check if a string is valid for UTF-8 use
	 * @since 1.5.7
	 * @param string $string
	 * @return boolean
	 */
	function bookacti_is_utf8( $string ) {
		if( function_exists( 'mb_check_encoding' ) ) {
			if( mb_check_encoding( $string, 'UTF-8' ) ) { 
				return true;
			}
		}
		else if( preg_match( '%^(?:
				[\x09\x0A\x0D\x20-\x7E]            # ASCII
			  | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			  | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
			  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			  | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
			  | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			  | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			  | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)*$%xs', $string ) ) {
			return true;
		}
		return false;
	}
	
	
	/**
	 * Use mb_substr if available, else use a regex
	 * @since 1.6.0
	 * @param string $string
	 * @param int $offset
	 * @param int|null $length
	 * @return string
	 */
	function bookacti_substr( $string, $offset = 0, $length = null ) {
		$substr = '';
		if( function_exists( 'mb_substr' ) ) {
			$substr = mb_substr( $string, $offset, $length );
		} else {
			$arr = preg_split( '//u', $string );
			$slice = array_slice( $arr, $offset + 1, $length );
			$substr = implode( '', $slice );
		}
		return $substr;
	}
	
	
	/**
	 * Sort array of arrays with a ['order'] index
	 * 
	 * @param array $a
	 * @param array $b
	 * @return array 
	 */
	function bookacti_sort_array_by_order( $a, $b ) {
		return $a['order'] - $b['order'];
	}


	/**
	 * Sanitize templates ids or activities ids to array
	 * 
	 * @param array|int $ids
	 * @return array 
	 */
	function bookacti_ids_to_array( $ids ) {
		if( is_array( $ids ) ){
			return $ids;
		} else if( ! empty( $ids ) ){
			if( is_numeric( $ids ) ) {
				return array( intval( $ids ) );
			}
		}
		return array();
	}


	/**
	 * Format datetime to be displayed in a human comprehensible way
	 * 
	 * @version 1.5.7
	 * @param string $datetime Date format "YYYY-MM-DD HH:mm:ss" is expected
	 * @param string $format 
	 * @return string
	 */
	function bookacti_format_datetime( $datetime, $format = '' ) {
		if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) 
		||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) ) {
			if( ! $format ) {
				$format = bookacti_get_message( 'date_format_long' );
			}
			$datetime = date_i18n( $format, strtotime( $datetime ) );
			$datetime = ! bookacti_is_utf8( $datetime ) ? utf8_encode( $datetime ) : $datetime;
		}
		return $datetime;
	}


	/**
	 * Check if a string is in a correct datetime format
	 * 
	 * @param string $datetime Date format "YYYY-MM-DD HH:mm:ss" is expected
	 * @return string|false
	 */
	function bookacti_sanitize_datetime( $datetime ) {
		if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) 
		||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) ) {
			return $datetime;
		}
		return false;
	}


	/**
	 * Check if a string is in a correct date format
	 * 
	 * @param string $date Date format YYYY-MM-DD is expected
	 * @return string|false 
	 */
	function bookacti_sanitize_date( $date ) {
		if( preg_match( '/\d{4}-[01]\d-[0-3]\d/', $date ) ) {
			return $date;
		}
		return false;
	}


	/**
	 * Check if a string is in a correct duration format
	 * 
	 * @param string $duration Duration format "DDD.HH:mm:ss" is expected
	 * @return string|false
	 */
	function bookacti_sanitize_duration( $duration ) {
		if( preg_match( '/\d{3}\.[0-2]\d:[0-5]\d:[0-5]\d/', $duration ) ) {
			return $duration;
		}
		return false;
	}
	
	
	/**
	 * Convert an amount of days to add to an interval format
	 * @since 1.5.8
	 * @param int|float $days_to_add
	 * @param int|float $hours_to_add
	 * @param int $minutes_to_add
	 * @return string
	 */
	function bookacti_convert_days_to_add_to_interval_format( $days_to_add = 0, $hours_to_add = 0, $minutes_to_add = 0 ) {
		$days			= intval( abs( $days_to_add ) );
		if( ! $hours_to_add )	{ $hours_to_add = abs( ( $days_to_add - $days ) * 24 ); }
		$hours			= intval( $hours_to_add );
		if( ! $minutes_to_add )	{ $minutes_to_add = abs( ( $hours_to_add - $hours ) * 60 ); }
		$minutes		= intval( $minutes_to_add );
		return 'P' . $days . 'DT' . $hours . 'H' . $minutes . 'M';
	}
	
	
	/**
	 * Sanitize array of dates
	 * 
	 * @since 1.2.0 (replace bookacti_sanitize_exceptions)
	 * @param array|string $exceptions Date array expected (format "YYYY-MM-DD")
	 * @return array
	 */
	function bookacti_sanitize_date_array( $exceptions ) {
		if( ! empty( $exceptions ) ) {
			if( is_array( $exceptions ) ) {
				// Remove entries that do not correspond to a date
				foreach( $exceptions as $i => $exception ) {
					if( ! preg_match( '/\d{4}-[01]\d-[0-3]\d/', $exception ) ) {
						unset( $exceptions[ $i ] );
					}
				}
				return $exceptions;
			} else if( preg_match( '/\d{4}-[01]\d-[0-3]\d/', $exceptions ) ) {
				return array( $exceptions );
			}
		}
		return array();
	}


	/**
	 * Check if a string is valid JSON
	 * 
	 * @since 1.1.0
	 * @param string $string
	 * @return boolean
	 */
	function bookacti_is_json( $string ) {

		if( ! is_string( $string ) ) {
			return false;
		}

		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}
	
	
	/**
	 * Sanitize the values of an array
	 * @since 1.5.0
	 * @param array $default_data
	 * @param array $raw_data
	 * @param array $keys_by_type
	 * @param array $sanitized_data
	 * @return array
	 */
	function bookacti_sanitize_values( $default_data, $raw_data, $keys_by_type, $sanitized_data = array() ) {
		// Sanitize the keys-by-type array
		$allowed_types = array( 'int', 'bool', 'str', 'str_id', 'array', 'datetime' );
		foreach( $allowed_types as $allowed_type ) {
			if( ! isset( $keys_by_type[ $allowed_type ] ) ) { $keys_by_type[ $allowed_type ] = array(); }
		}
		
		// Make an array of all keys that will be sanitized
		$keys_to_sanitize = array();
		foreach( $keys_by_type as $type => $keys ) {
			if( ! in_array( $type, $allowed_types, true ) ) { continue; }
			if( ! is_array( $keys ) ) { $keys_by_type[ $type ] = array( $keys ); }
			foreach( $keys as $key ) {
				$keys_to_sanitize[] = $key;
			}
		}
		
		// Format each value according to its type
		foreach( $default_data as $key => $default_value ) {
			// Do not process keys without types
			if( ! in_array( $key, $keys_to_sanitize, true ) ) { continue; }
			// Skip already sanitized values
			if( isset( $sanitized_data[ $key ] ) ) { continue; }
			// Set undefined values to default and continue
			if( ! isset( $raw_data[ $key ] ) ) { $sanitized_data[ $key ] = $default_value; continue; }

			// Sanitize integers
			if( in_array( $key, $keys_by_type[ 'int' ], true ) ) { 
				$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? intval( $raw_data[ $key ] ) : $default_value;
			}

			// Sanitize string identifiers
			else if( in_array( $key, $keys_by_type[ 'str_id' ], true ) ) { 
				$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_title_with_dashes( stripslashes( $raw_data[ $key ] ) ) : $default_value;
			}

			// Sanitize text
			else if( in_array( $key, $keys_by_type[ 'str' ], true ) ) { 
				$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_text_field( stripslashes( $raw_data[ $key ] ) ) : $default_value;
			}

			// Sanitize array
			else if( in_array( $key, $keys_by_type[ 'array' ], true ) ) { 
				$sanitized_data[ $key ] = is_array( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
			}

			// Sanitize boolean
			else if( in_array( $key, $keys_by_type[ 'bool' ], true ) ) { 
				$sanitized_data[ $key ] = in_array( $raw_data[ $key ], array( 1, '1', true, 'true' ), true ) ? 1 : 0;
			}

			// Sanitize datetime
			else if( in_array( $key, $keys_by_type[ 'datetime' ], true ) ) { 
				$sanitized_data[ $key ] = bookacti_sanitize_datetime( $raw_data[ $key ] );
				if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
			}
		}
		
		return apply_filters( 'bookacti_sanitized_data', $sanitized_data, $default_data, $raw_data, $keys_by_type );
	}
	
	
	/**
	 * Escape illegal caracters in ical properties
	 * @since 1.6.0
	 * @param string $value
	 * @param string $property_name
	 * @return string
	 */
	function bookacti_sanitize_ical_property( $value, $property_name = '' ) {
		$value = trim( $value );									// Remove whitespaces at the start and at the end of the string
		$value = strip_tags( $value );								// Remove PHP and HTML elements
		$value = html_entity_decode( $value );						// Decode html entities first because semicolons will be escaped
		$value = preg_replace( '/([\,;])/', '\\\$1', $value );		// Escape illegal caracters in ical properties
		$value = preg_replace( '/' . PHP_EOL . '+/', ' ', $value ); // Replace End of lines with a whitespace
		$value = preg_replace( '/\s{2,}/', ' ', $value );			// Replace multiple whitespaces with a single space
		$property_name_len = strlen( $property_name ) + 1;			// Add 1 character for the colon (:) after the property name
		$lines = array();
		while( strlen( $value ) > ( 75 - $property_name_len ) ) {
			$space = ( 75 - $property_name_len );
			$mbcc = $space;
			while( $mbcc ) {
				$line = bookacti_substr( $value, 0, $mbcc );
				$oct = strlen( $line );
				if ( $oct > $space ) {
					$mbcc -= $oct - $space;
				}
				else {
					$lines[] = $line;
					$property_name_len = 0; // The leading space doesn't count
					$value = bookacti_substr( $value, $mbcc );
					break;
				}
			}
		}
		if( ! empty( $value ) ) {
			$lines[] = $value;
		}
		return implode( PHP_EOL . ' ', $lines ); // Line break + leading space
	}


	
	
// USERS

	/**
	 * Programmatically logs a user in
	 * @since 1.5.0
	 * @param string $username
	 * @return bool True if the login was successful; false if it wasn't
	 */
	function bookacti_log_user_in( $username ) {
		
		if ( is_user_logged_in() ) { wp_logout(); }
		
		// hook in earlier than other callbacks to short-circuit them
		add_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );
		$user = wp_signon( array( 'user_login' => $username ) );
		remove_filter( 'authenticate', 'bookacti_allow_to_log_user_in_programmatically', 10, 3 );

		if( is_a( $user, 'WP_User' ) ) {
			wp_set_current_user( $user->ID, $user->user_login );
			if ( is_user_logged_in() ) { return true; }
		}
		return false;
	}
	
	
	/**
	 * An 'authenticate' filter callback that authenticates the user using only the username.
	 *
	 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
	 * and unhooked immediately after it fires.
	 * 
	 * @since 1.5.0
	 * @param WP_User $user
	 * @param string $username
	 * @param string $password
	 * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
	 */
	function bookacti_allow_to_log_user_in_programmatically( $user, $username, $password ) {
		return get_user_by( 'login', $username );
	}