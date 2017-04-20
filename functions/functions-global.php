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
	add_filter( 'bookacti_translate_text', 'bookacti_translate_text_with_qtranslate' );
	function bookacti_translate_text_with_qtranslate( $text ) {
		return apply_filters( 'translate_text', $text );
	}
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


//Create help tip
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
		/* translators: Datetime format. Must be adapted to each country. Use strftime documentation to find the appropriated combinaison http://php.net/manual/en/function.strftime.php */
		$datetime = utf8_encode( strftime( __( '%A, %B %d, %Y %I:%M %p', BOOKACTI_PLUGIN_NAME ), strtotime( $datetime ) ) );
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