$j( document ).ready( function() {
	// Initialize select2
	bookacti_select2_init();
	
	// Localize moment JS
	moment.locale( bookacti_localized.fullcalendar_locale );
	
	// Add formatPHP function to moment JS
	bookacti_init_moment_format_from_php_date_format();
	
	// Init Bootstrap tooltip global functions
	var is_bootstrap = typeof $j.fn.modal !== 'undefined';
	if( is_bootstrap ) {
		/**
		 * Remove the Bootstrap tooltips on click anywhere else than the tooltip
		 * @since 1.9.0
		 * @param {Event} e
		 */
		$j( document ).on( 'click', function( e ) {
			// Do nothing if the click hit the tooltip
			if( $j( e.target ).closest( '.bookacti-bs-tooltip' ).length ) { return; }

			// Remove the tooltip
			$j( '.bookacti-tip' ).tooltip( 'hide' );

			// Clear the timeout
			if( typeof bookacti_bs_display_bookings_tooltip_monitor !== 'undefined' ) { 
				if( bookacti_bs_display_bookings_tooltip_monitor ) { clearTimeout( bookacti_bs_display_bookings_tooltip_monitor ); }
			}
		});


		/**
		 * Keep the Bootstrap tooltips displayed if the user mouseover it
		 * @since 1.9.0
		 */
	   $j( 'body' ).on( 'mouseover', '.bookacti-bs-tooltip', function() {
		   if( typeof bookacti_bs_remove_mouseover_tooltip_monitor !== 'undefined' ) { 
			   if( bookacti_bs_remove_mouseover_tooltip_monitor ) { clearTimeout( bookacti_bs_remove_mouseover_tooltip_monitor ); }
		   }
	   });


	   /**
		* Remove the Bootstrap tooltips - on mouseout
		* @since 1.9.0
		*/
	   $j( 'body' ).on( 'mouseout', '.bookacti-bs-tooltip', function() {
			var tooltip_mouseover_timeout = Math.min( Math.max( parseInt( bookacti_localized.bookings_tooltip_mouseover_timeout ), 0 ), 200 );
			var _this = this;
			bookacti_bs_remove_mouseover_tooltip_monitor = setTimeout( function() {
				$j( _this ).tooltip( 'hide' );
			}, tooltip_mouseover_timeout );
	   });
	} 
});


// Test via a getter in the options object to see if the passive property is accessed
var supportsPassive = false;
try {
	var opts = Object.defineProperty( {}, 'passive', { get: function() { supportsPassive = true; } } );
	window.addEventListener( 'testPassive', null, opts );
	window.removeEventListener( 'testPassive', null, opts );
} catch ( e ) {}


/**
 * Detect if the device used is touch-sensitive
 * @version 1.8.9
 */
window.addEventListener( 'touchstart', function bookacti_detect_touch_device() {
    bookacti.is_touch_device = true;
    // Remove event listener once fired, otherwise it'll kill scrolling performance
    window.removeEventListener( 'touchstart', bookacti_detect_touch_device );
}, supportsPassive ? { passive: true } : false );


/**
 * Init tooltip
 * @version 1.14.2
 */
function bookacti_init_tooltip() {
	if( typeof $j.fn.tipTip != 'function' ) { return; }
	$j( '.bookacti-tip' ).tipTip({
		'attribute': 'data-tip',
		'fadeIn': 200,
		'fadeOut': 200,
		'delay': 200,
		'maxWidth': '300px',
		'keepAlive': true
	});
}


/**
 * Get loading spinner and text
 * @since 1.15.0
 * @returns {HTMLElement}
 */
function bookacti_get_loading_html() {
	return '<div class="bookacti-loading-container"><div class="bookacti-loading-image"><div class="bookacti-spinner"></div></div><div class="bookacti-loading-text">' + bookacti_localized.loading + '</div></div>';
}


/**
 * Add loading spinner + text
 * @since 1.15.0
 * @param {HTMLElement} element
 * @param {String} where 'append' (Default), 'prepend', 'before', after'
 */
function bookacti_add_loading_html( element, where ) {
	where = where ? where : 'append';
	var loading_html = bookacti_get_loading_html();
	     if( where === 'before' )  { element.before( loading_html ); }
	else if( where === 'after' )   { element.after( loading_html ); }
	else if( where === 'prepend' ) { element.prepend( loading_html ); }
	else                           { element.append( loading_html ); }
}


/**
 * Remove loading spinner + text
 * @since 1.15.0
 * @param {HTMLElement} element
 */
function bookacti_remove_loading_html( element ) {
	element.find( '.bookacti-loading-container' ).addBack( '.bookacti-loading-container' ).remove();
}


/**
 * Scroll to element or to position
 * @version 1.7.19
 * @param {HTMLElement|Number} element
 * @param {Int} speed
 * @param {String} position Either "middle" or "top"
 */
function bookacti_scroll_to( element, speed, position ) {
	speed	= $j.isNumeric( speed ) ? parseInt( speed ) : 500;
	position= position !== 'middle' ? 'top' : 'middle';
	
	var elOffset = typeof element === 'number' ? element : ( element.length ? element.offset().top : $j( document ).scrollTop() );
	var offset = elOffset;
	
	if( position === 'middle' && typeof element !== 'number' && element.length ) {	
		var elHeight = element.height();
		var windowHeight = $j( window ).height();

		if( elHeight < windowHeight ) {
		  offset = elOffset - ( ( windowHeight / 2 ) - ( elHeight / 2 ) );
		}
	}
	
	$j( 'html, body' ).animate( {scrollTop: offset}, speed );
}


/**
 * Add 0 before a number until it has *max* digits
 * @param {String} str
 * @param {int} max
 * @returns {String}
 */
function bookacti_pad( str, max ) {
  str = str.toString();
  return str.length < max ? bookacti_pad( '0' + str, max ) : str;
}


/**
 * Compare two arrays and tell if they are the same
 * @version 1.8.0
 * @param {array} array1
 * @param {array} array2
 * @returns {Boolean}
 */
function bookacti_compare_arrays( array1, array2 ) {
	return $j( array1 ).not( array2 ).length === 0 && $j( array2 ).not( array1 ).length === 0;
}


/**
 * Serialize a form into a single object (works with multidimentionnal inputs of any depth)
 * @returns {object}
 */
$j.fn.serializeObject = function() {
	var data = {};

	function buildInputObject(arr, val) {
		if (arr.length < 1) {
			return val;  
		}
		var objkey = arr[0];
		if (objkey.slice(-1) == "]") {
			objkey = objkey.slice(0,-1);
		}  
		var result = {};
		if (arr.length == 1){
			result[objkey] = val;
		} else {
			arr.shift();
			var nestedVal = buildInputObject(arr,val);
			result[objkey] = nestedVal;
		}
		return result;
	}
	
	function gatherMultipleValues( that ) {
		var final_array = [];
		$j.each(that.serializeArray(), function( key, field ) {
			// Copy normal fields to final array without changes
			if( field.name.indexOf('[]') < 0 ){
				final_array.push( field );
				return true; // That's it, jump to next iteration
			}
			
			// Remove "[]" from the field name
			var field_name = field.name.split('[]')[0];

			// Add the field value in its array of values
			var has_value = false;
			$j.each( final_array, function( final_key, final_field ){
				if( final_field.name === field_name ) {
					has_value = true;
					final_array[ final_key ][ 'value' ].push( field.value );
				}
			});
			// If it doesn't exist yet, create the field's array of values
			if( ! has_value ) {
				final_array.push( { 'name': field_name, 'value': [ field.value ] } );
			}
		});
		return final_array;
	}
	
	// Manage fields allowing multiple values first (they contain "[]" in their name)
	var final_array = gatherMultipleValues( this );
	
	// Then, create the object
	$j.each(final_array, function() {
		var val = this.value;
		var c = this.name.split('[');
		var a = buildInputObject(c, val);
		$j.extend(true, data, a);
	});

	return data;
};


/**
 * Init selectbox with AJAX search
 * @since 1.7.19
 * @version 1.12.6
 */
function bookacti_select2_init() {
	if( ! $j.fn.select2 ) { return; }
		
	// Without AJAX search
	$j( '.bookacti-select2-no-ajax:not(.select2-hidden-accessible)' ).select2({
		language: bookacti_localized.fullcalendar_locale,
		minimumResultsForSearch: 1,
		width: 'element',
		dropdownAutoWidth: true,
		dropdownParent: $j( this ).closest( '.bookacti-backend-dialog' ).length ? $j( this ).closest( '.bookacti-backend-dialog' ) : $j( 'body' ),
		escapeMarkup: function( text ) { return text; }
	});
	
	// With AJAX search
	$j( '.bookacti-select2-ajax:not(.select2-hidden-accessible)' ).select2({
		language: bookacti_localized.fullcalendar_locale,
		minimumInputLength: 3,
		width: 'element',
		dropdownAutoWidth: true,
		dropdownParent: $j( this ).closest( '.bookacti-backend-dialog' ).length ? $j( this ).closest( '.bookacti-backend-dialog' ) : $j( 'body' ),
  		escapeMarkup: function( text ) { return text; },
		ajax: {
			url: bookacti_localized.ajaxurl,
			dataType: 'json',
			delay: 1000,
			data: function( params ) {
				var data_type = $j( this ).data( 'type' ) ? $j( this ).data( 'type' ).trim() : '';
				var data = {
					term: params.term,
					action: data_type ? 'bookactiSelect2Query_' + data_type : 'bookactiSelect2Query',
					name: $j( this ).attr( 'name' ) ? $j( this ).attr( 'name' ) : '',
					id: $j( this ).attr( 'id' ) ? $j( this ).attr( 'id' ) : '',
					allow_current: $j( this ).find( 'option[value="current"]' ).length ? 1 : 0,
					nonce: bookacti_localized.nonce_query_select2_options
				};
				
				$j( this ).trigger( 'bookacti_select2_query_data', [ data ] );
				
				return data;
			},
			processResults: function( data ) {
				var options = [];
				if( typeof data.options !== 'undefined' ) {
					options = data.options;
				}
				
				var results = { results: options };
				
				$j( this ).trigger( 'bookacti_select2_query_results', [ results, data ] );
				
				return results;
			},
			cache: true
		}
	});
}


/**
 * Init a new moment function: moment.formatPHP 
 * Convert a php date format into a moment.js format
 * http://www.php.net/manual/en/function.date.php
 * http://momentjs.com/docs/#/displaying/format/
 * @version 1.9.0
 */
function bookacti_init_moment_format_from_php_date_format() {
	(function( m ) {
		var formatMap = {
			d: 'DD',
			D: 'ddd',
			j: 'D',
			l: 'dddd',
			N: 'E',
			S: function() {
				return '[' + this.format( 'Do' ).replace( /\d*/g, '' ) + ']';
			},
			w: 'd',
			z: function() {
				return this.format( 'DDD' ) - 1;
			},
			W: 'W',
			F: 'MMMM',
			m: 'MM',
			M: 'MMM',
			n: 'M',
			t: function() {
				return this.daysInMonth();
			},
			L: function() {
				return this.isLeapYear() ? 1 : 0;
			},
			o: 'GGGG',
			Y: 'YYYY',
			y: 'YY',
			a: 'a',
			A: 'A',
			B: function() {
				var thisUTC = this.clone().utc();
				var swatch = ( ( thisUTC.hours() + 1 ) % 24 ) + ( thisUTC.minutes() / 60 ) + ( thisUTC.seconds() / 3600 );
				return Math.floor( swatch * 1000 / 24 );
			},
			g: 'h',
			G: 'H',
			h: 'hh',
			H: 'HH',
			i: 'mm',
			s: 'ss',
			u: '[u]', // moment doesn't support microseconds
			e: '[e]', // moment doesn't support timezone litteral format
			I: function() {
				return this.isDST() ? 1 : 0;
			},
			O: 'ZZ',
			P: 'Z',
			T: '[T]', // moment doesn't support timezone litteral format
			Z: function() {
				return parseInt( this.format( 'ZZ' ), 10 ) * 36;
			},
			c: 'YYYY-MM-DD[T]HH:mm:ssZ',
			r: 'ddd, DD MMM YYYY HH:mm:ss ZZ',
			U: 'X'
		};
		
		moment.fn.formatPHP = function( format ) {
			var that = this;
			escape = false;

			return this.format( format.replace( /./g, function( phpStr ) {
				if( escape ) { escape = false; return '[' + phpStr + ']'; } // Display escaped chars
				if( phpStr === '\\' ) { escape = true; return ''; } // The next char will be escaped
				if( typeof formatMap[ phpStr ] === 'undefined' ) { return '[' + phpStr + ']'; } // Display non-used char as is
				return typeof formatMap[ phpStr ] === 'function' ? formatMap[ phpStr ].call(that) : formatMap[ phpStr ];
			}));
		};
	}( moment ));
}


/**
 * Convert a PHP datetime format to moment JS format
 * @since 1.7.16
 * @version 1.15.1
 * @param {string} php_format
 * @returns {string}
 */
function bookacti_convert_php_datetime_format_to_moment_js( php_format ) {
	if( typeof php_format !== 'string' ) { return ''; }
	if( ! php_format.length ) { return ''; }
	
	var format_map = {
		"d": 'DD',
		"D": 'ddd',
		"j": 'D',
		"l": 'dddd',
		"N": 'E',
		"w": 'd',
		"W": 'W',
		"F": 'MMMM',
		"m": 'MM',
		"M": 'MMM',
		"n": 'M',
		"o": 'GGGG',
		"Y": 'YYYY',
		"y": 'YY',
		"a": 'a',
		"A": 'A',
		"g": 'h',
		"G": 'H',
		"h": 'hh',
		"H": 'HH',
		"i": 'mm',
		"s": 'ss',
		"u": 'X',
		"e": 'z',
		"O": 'ZZ',
		"P": 'Z',
		"T": 'z',
		"c": 'YYYY-MM-DD[T]HH:mm:ssZ',
		"r": 'ddd, DD MMM YYYY HH:mm:ss ZZ',
		"U": 'X'
	};
	
	var has_backslash = false;
	var moment_js_format = '';
	for( var i = 0; i < php_format.length; i++ ) {
		var char = php_format[ i ];
		if( char === '\\' && ! has_backslash ) { has_backslash = true; continue; }
		moment_js_format += has_backslash || typeof format_map[ char ] === 'undefined' ? '[' + char + ']' : format_map[ char ];
		has_backslash = false;
	}
	
	return moment_js_format;
}


/**
 * Convert a PHP datetime format to FullCalendar Date-Formatting Object, see https://fullcalendar.io/docs/date-formatting/
 * @since 1.15.0
 * @version 1.15.1
 * @param {string} php_format
 * @returns {object}
 */
function bookacti_convert_php_datetime_format_to_fc_date_formatting_object( php_format ) {
	if( typeof php_format !== 'string' ) { return {}; }
	if( ! php_format.length ) { return {}; }
	
	var format_map = {
		"d": { 'day': '2-digit' },
		"D": { 'weekday': 'short' },
		"j": { 'day': 'numeric' },
		"l": { 'weekday': 'long' },
		"N": {},
		"w": {},
		"W": { 'week': 'numeric' },
		"F": { 'month': 'long' },
		"m": { 'month': '2-digit' },
		"M": { 'month': 'short' },
		"n": { 'month': 'numeric' },
		"o": { 'year': 'numeric' },
		"Y": { 'year': 'numeric' },
		"y": { 'year': '2-digit' },
		"a": { 'meridiem': 'lowercase' },
		"A": {},
		"g": { 'hour': 'numeric', 'hour12': true },
		"G": { 'hour': 'numeric', 'hour12': false },
		"h": { 'hour': '2-digit', 'hour12': true },
		"H": { 'hour': '2-digit', 'hour12': false },
		"i": { 'minute': '2-digit' },
		"s": { 'second': '2-digit' },
		"u": { 'timeZoneName': 'short' },
		"e": { 'timeZoneName': 'short' },
		"O": {},
		"P": {},
		"T": { 'timeZoneName': 'short' },
		"c": { 'year': 'numeric', 'month': '2-digit', 'day': '2-digit', 'hour': '2-digit', 'hour12': false, 'minute': '2-digit', 'second': '2-digit' },
		"r": { 'year': 'numeric', 'month': 'short', 'day': '2-digit', 'weekday': 'short', 'hour': '2-digit', 'hour12': false, 'minute': '2-digit', 'second': '2-digit' },
		"U": {}
	};
	
	var date_formatting_obj = { 'meridiem': false };
	
	var has_backslash = false;
	for( var i = 0; i < php_format.length; i++ ) {
		var char = php_format[ i ];
		if( char === '\\' && ! has_backslash ) { has_backslash = true; continue; }
		if( ! has_backslash && typeof format_map[ char ] !== 'undefined' ) {
			$j.extend( true, date_formatting_obj, format_map[ char ] );
		}
		has_backslash = false;
	}
	
	return date_formatting_obj;
}


/**
 * Get URL parameter value
 * @since 1.7.4
 * @version 1.15.0
 * @param {string} desired_param
 * @returns {String}
 */
function bookacti_get_url_parameter( desired_param ) {
	var url = window.location.search.substring( 1 );
	var url_variables = url.split( '&' );
	
	for( var i = 0; i < url_variables.length; i++ ) {
		var param_name = url_variables[ i ].split( '=' );
		if( param_name[ 0 ] == desired_param ) {
			return decodeURIComponent( param_name[ 1 ].replace( /\+/g, '%20' ) );
		}
	}
	return '';
}


/**
 * Format price with the correct format (currency, separators, decimals)
 * @since 1.12.4
 * @param {Float} price
 * @returns {string}
 */
function bookacti_format_price( price ) {
	var formatted_price = '';
	if( ! $j.isNumeric( price ) ) { return formatted_price; }
	
	price = parseFloat( price );
	var negative = price < 0;
	if( negative ) { price = price * -1; }
	
	// Keep n decimals
	formatted_price = price.toFixed( parseInt( bookacti_localized.price_decimal_number ) );
	
	// Do not display decimals if = 0
	if( parseFloat( formatted_price ).toString().indexOf( '.' ) === -1 ) { formatted_price = parseFloat( formatted_price ); }
	
	// Split int and decimal parts
	var num_parts = formatted_price.toString().split( '.' );
	
	// Add thousand separators to the int part
    num_parts[ 0 ] = num_parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, bookacti_localized.price_thousand_separator );
	
	// Join int and decimal parts again with decimal separators
    formatted_price = num_parts.join( bookacti_localized.price_decimal_separator );
	
	// Add the price in its container (with currency)
	formatted_price = bookacti_localized.price_format.replace( '{price}', formatted_price );
	
	if( negative ) { formatted_price = '-' + formatted_price; }
	
	return formatted_price;
}