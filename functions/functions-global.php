<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// Detect current language with WPML or Qtranslate X
function bookacti_get_current_lang_code() {
    $lang_code = substr( get_locale(), 0, strpos( get_locale(), '_' ) );
	
	$is_qtranslate	= bookacti_is_plugin_active( 'qtranslate-x/qtranslate.php' );
	$is_wpml		= bookacti_is_plugin_active( 'wpml/wpml.php' );
	
    if ( $is_qtranslate ) {
        if( function_exists( 'qtranxf_getLanguage' ) ) {
            $lang_code = qtranxf_getLanguage();
        }
    } else if ( $is_wpml ) {
        if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
            $lang_code = ICL_LANGUAGE_CODE;
        }
    }
	return $lang_code;
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

	if( $default === 'site' ) {
		$alloptions	= wp_load_alloptions();
		$locale		= $alloptions[ 'WPLANG' ] ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
	} else {
		// Get user locale, if not set get current locale
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
 * Switch Booking Activities to specific locale
 *
 * @param string $locale
 * @since 1.2.0
 */
function bookacti_switch_locale( $locale ) {
	if( ! $locale ) { return; }
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( $locale );
		bookacti_load_textdomain( $locale );
	}
}


// Detect current language with WPML or Qtranslate X
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
	 * @param string $text
	 * @return string
	 */
	function bookacti_translate_text_with_qtranslate( $text, $lang = null ) {
		return apply_filters( 'translate_text', $text, $lang );
	}
	add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_qtranslate', 10, 2 );
}


// Write logs to files
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


/**
 * Display various fields
 * 
 * @since 1.2.0
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'value', 'tip']
 */
function bookacti_display_field( $args ) {

	$args = bookacti_format_field_args( $args );

	if( ! $args ) { return; }

	// Display field according to type
	
	// TEXT & NUMBER
	if( in_array( $args[ 'type' ], array( 'text', 'number' ) ) ) {
	?>
		<input	type=		'<?php echo esc_attr( $args[ 'type' ] ); ?>' 
				name=		'<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				id=			'<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class=		'bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>' 
				value=		'<?php echo esc_attr( $args[ 'value' ] ); ?>' 
			<?php if( $args[ 'type' ] === 'number' ) { ?>
				min=		'<?php echo esc_attr( $args[ 'options' ][ 'min' ] ); ?>' 
				max=		'<?php echo esc_attr( $args[ 'options' ][ 'max' ] ); ?>'
			<?php } ?>
		/>
		<label	for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo $args[ 'label' ]; ?>
		</label>
	<?php
	}
	
	// SINGLE CHECKBOX (boolean)
	else if( $args[ 'type' ] === 'checkbox' ) {
		bookacti_onoffswitch( esc_attr( $args[ 'name' ] ), esc_attr( $args[ 'value' ] ), esc_attr( $args[ 'id' ] ) );
	}
	
	// MULTIPLE CHECKBOX
	else if( $args[ 'type' ] === 'checkboxes' ) {
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='bookacti_checkbox'>
				<input	name='<?php echo esc_attr( $args[ 'name' ] ) . '[' . esc_attr( $option[ 'id' ] ) . ']'; ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='bookacti-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='checkbox' 
						value='1'
						<?php if( isset( $args[ 'value' ][ $option[ 'id' ] ] ) ) { checked( $args[ 'value' ][ $option[ 'id' ] ], 1, true ); } ?>
				/>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo apply_filters( 'bookacti_translate_text', esc_html( $option[ 'label' ] ) ); ?>
				</label>
			<?php
				//Display the tip
				if( $option[ 'description' ] ) {
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
		>
		<?php foreach( $args[ 'options' ] as $option_id => $option_value ) { ?>
			<option value='<?php echo esc_attr( $option_id ); ?>'
					id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option_id ); ?>'
					<?php selected( $args[ 'value' ], $option_id ); ?>
			>
					<?php echo esc_html( $option_value ); ?>
			</option>
		<?php } ?>
		</select>
		<?php
	}
	
	// TINYMCE editor
	else if( $args[ 'type' ] === 'editor' ) {
		wp_editor( $args[ 'value' ], $args[ 'id' ], $args[ 'options' ] );
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
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'value', 'tip']
 * @return array|false
 */
function bookacti_format_field_args( $args ) {

	// If $args is not an array, return
	if( ! is_array( $args ) ) { return false; }

	// If fields type or name are not set, return
	if( ! isset( $args[ 'type' ] ) || ! isset( $args[ 'name' ] ) ) { return false; }

	// If field type is not supported, return
	if( ! in_array( $args[ 'type' ], array( 'text', 'number', 'checkbox', 'checkboxes', 'select', 'radio', 'editor' ) ) ) { 
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
		'value'			=> '',
		'tip'			=> ''
	);

	// Replace empty value by default
	foreach( $default_args as $key => $default_value ) {
		$args[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $default_value;
	}

	// If no id, use name instead
	$args[ 'id' ] = $args[ 'id' ] ? $args[ 'id' ] : sanitize_title_with_dashes( $args[ 'name' ] );

	// Make sure fields with multiple options have 'options' set
	if( in_array( $args[ 'type' ], array( 'checkboxes', 'radio', 'select' ) ) ){
		if( ! $args[ 'options' ] ) { return false; }
	}

	// Make sure checkboxes have their value as an array
	if( $args[ 'type' ] === 'checkboxes' ){
		if( ! is_array( $args[ 'value' ] ) ) { return false; }
	}

	// Make sure 'number' has min and max
	else if( $args[ 'type' ] === 'number' ) {
		$args[ 'options' ][ 'min' ] = isset( $args[ 'options' ][ 'min' ] ) ? $args[ 'options' ][ 'min' ] : '';
		$args[ 'options' ][ 'max' ] = isset( $args[ 'options' ][ 'max' ] ) ? $args[ 'options' ][ 'max' ] : '';
	}

	// Make sure that if 'editor' has options, options is an array
	else if( $args[ 'type' ] === 'editor' ) {
		if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
		$args[ 'options' ][ 'textarea_name' ]	= $args[ 'name' ];
		$args[ 'options' ][ 'editor_class' ]	= $args[ 'class' ];
	}

	return $args;
}


// Create help tip
function bookacti_help_tip( $tip ){

	$tip = esc_attr( $tip );
	
	echo "<span class='bookacti-tip' data-tip='" . esc_attr( $tip ) . "'>"
			. "<img src='" . esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/help.png' ) . "' />"
		. "</span>";
}

//Create ON / OFF switch
function bookacti_onoffswitch( $name, $current_value, $id = NULL, $disabled = false ) {
	
	$checked = checked( '1', esc_attr( $current_value ), false );
	if( is_null ( $id ) || $id === '' || ! $id ) { $id = $name; }
	
	?>
	<div class="bookacti-onoffswitch <?php if( $disabled ) { echo 'bookacti-disabled'; } ?>">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value='0' />
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


// Check if plugin is active
function bookacti_is_plugin_active( $plugin_path_and_name ) {
	
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	return is_plugin_active( $plugin_path_and_name );
}


// Display tabs and their content
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

// Sort array of arrays with a ['order'] index
function bookacti_sort_array_by_order( $a, $b ) {
	return $a['order'] - $b['order'];
}


// Get available booking methods
function bookacti_get_available_booking_methods(){
	$available_booking_methods = array(
			'calendar'	=> __( 'Calendar', BOOKACTI_PLUGIN_NAME )
		);
		
	return apply_filters( 'bookacti_available_booking_methods', $available_booking_methods );
}


// Format datetime to be displayed in a human comprehensible way
function bookacti_format_datetime( $datetime ) {
	if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) 
	||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) ) {
		/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
		$datetime = date_i18n( __( 'l, F d, Y h:i a', BOOKACTI_PLUGIN_NAME ), strtotime( $datetime ) );
		$datetime = ! mb_check_encoding( $datetime, 'UTF-8' ) ? utf8_encode( $datetime ) : $datetime;
	}
	return $datetime;
}


// Check if a string is in a correct datetime format
function bookacti_sanitize_datetime( $datetime ) {
	if( preg_match( '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) 
	||  preg_match( '/\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/', $datetime ) ) {
		return $datetime;
	}
	return false;
}


// Check if a string is in a correct date format
function bookacti_sanitize_date( $date ) {
	if( preg_match( '/\d{4}-[01]\d-[0-3]\d/', $date ) ) {
		return $date;
	}
	return false;
}


// Check if a string is in a correct duration format
function bookacti_sanitize_duration( $duration ) {
	if( preg_match( '/\d{3}\.[0-2]\d:[0-5]\d:[0-5]\d/', $duration ) ) {
		return $duration;
	}
	return false;
}


// Sanitize templates ids or activities ids to array
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


// Sanitize exceptions array
function bookacti_sanitize_exceptions( $exceptions ) {
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
 * 
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