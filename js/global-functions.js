$j( document ).ready( function() {
	bookacti_init_user_selectbox();
	bookacti_init_moment_format_from_php_date_format();
});


// Detect if the device used is touch-sensitive
window.addEventListener( 'touchstart', function bookacti_detect_touch_device() {
    bookacti.is_touch_device = true;
    // Remove event listener once fired, otherwise it'll kill scrolling
    // performance
    window.removeEventListener( 'touchstart', bookacti_detect_touch_device );
}, false );


/**
 * Init tooltip
 */
function bookacti_init_tooltip() {
	$j( '.bookacti-tip' ).tooltip({
		"items":	'[data-tip]',
		"content": function () {
			return $j( this ).data( 'tip' );
		},
		"show":	{ effect: 'fadeIn', duration: 200 },
		"hide":	{ effect: 'fadeOut', duration: 200 },
		"close": function(event, ui) {
			ui.tooltip.hover( function() {
				$j( this ).stop( true ).fadeTo( 200, 1 ); 
			},
			function() {
				$j( this ).fadeOut( '200', function() {
					$j( this ).remove();
				});
			});
		}
	});
	$j( '.bookacti-tip' ).tooltip( 'close' );
}


/**
 * Scroll to element or to position
 * @version 1.5.4
 * @param {DOM_Element} or {number} element
 * @param {int} speed
 * @param {string} position Either "middle" or "top"
 */
function bookacti_scroll_to( element, speed, position ) {
	speed	= $j.isNumeric( speed ) ? parseInt( speed ) : 500;
	position= position !== 'middle' ? 'top' : 'middle';
	
	var elOffset = typeof element === 'number' ? element : element.offset().top;
	var offset = elOffset;
	
	if( position === 'middle' && typeof element !== 'number' ) {	
		var elHeight = element.height();
		var windowHeight = $j( window ).height();

		if( elHeight < windowHeight ) {
		  offset = elOffset - ( ( windowHeight / 2 ) - ( elHeight / 2 ) );
		}
	}
	
	$j( 'html, body' ).animate( {scrollTop: offset}, speed );
}


// Add 0 before a number until it has *max* digits
function bookacti_pad( str, max ) {
  str = str.toString();
  return str.length < max ? bookacti_pad( "0" + str, max ) : str;
}


// Compare two arrays and tell if they are the same
function bookacti_compare_arrays( array1, array2 ) {
	
	var are_same = $j( array1 ).not( array2 ).length === 0 && $j( array2 ).not( array1 ).length === 0;
	
	return are_same;
}


// Serialize a form into a single object (works with multidimentionnal inputs of any depth)
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
 * Init Find-As-You-Type user selectbox
 * @version 1.7.0
 */
function bookacti_init_user_selectbox() {
	// Jquery UI Autocomplete
	( function( $j ) {
		$j.widget( 'custom.bookacti_combobox', {
			_create: function() {
				this.wrapper = $j( '<span>' )
					.addClass( 'bookacti-combobox' )
					.insertAfter( this.element );

				this.element.hide();
				this._createAutocomplete();
				this._createShowAllButton();
			},

			_createAutocomplete: function() {
				var selected = this.element.children( ':selected' ),
				value = selected.val() ? selected.text() : '';

				this.input = $j( '<input>' )
					.appendTo( this.wrapper )
					.val( value )
					.attr( 'placeholder', bookacti_localized.placeholder_select_customer )
					.addClass( 'bookacti-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left' )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: $j.proxy( this, '_source' )
					})
					.tooltip({
						tooltipClass: 'ui-state-highlight'
					});

				this._on( this.input, {
					autocompleteselect: function( event, ui ) {
						ui.item.option.selected = true;
						this._trigger( 'select', event, {
							item: ui.item.option
						});
						
						// Trigger action and pass selected option
						$j( this.element ).trigger( 'bookacti_customers_selectbox_changed', [ ui.item.option ] );

					},

					autocompletesearch: function( event, ui ) {
						// Add self (add the searched string as an option)
						var variable_option = this.element.children( 'option.bookacti-variable-value' );
						if( variable_option.length ) {
							variable_option.val( this.input.val() );
							variable_option.html( this.input.val() );
							variable_option.selected = true;
						}
					},
					
					autocompletechange: function( event, ui ) {
						this._removeIfInvalid( event, ui );
					}
				});
				
				this.input.autocomplete( 'widget' ).addClass( 'bookacti-combobox-list bookacti-custom-scrollbar' );
			},

			_createShowAllButton: function() {
				var input = this.input,
				wasOpen = false;

				$j( '<a>' )
					.attr( 'tabIndex', -1 )
					.attr( 'title', bookacti_localized.show_all_customers )
					.tooltip()
					.appendTo( this.wrapper )
					.button({
						icons: {
							primary: 'ui-icon-triangle-1-s'
						},
						text: false
					})
					.removeClass( 'ui-corner-all' )
					.addClass( 'bookacti-combobox-toggle ui-corner-right' )
					.mousedown( function() {
						wasOpen = input.autocomplete( 'widget' ).is( ':visible' );
					})
					.click( function() {
						input.focus();

						// Close if already visible
						if ( wasOpen ) {
							return;
						}

						// Pass empty string as value to search for, displaying all results
						input.autocomplete( 'search', '' );
					});
			},

			_source: function( request, response ) {
				var matcher = new RegExp( $j.ui.autocomplete.escapeRegex(request.term), 'i' );
				response( this.element.children( 'option' ).map( function() {
					var text = $j( this ).text();
					if( this.value && ( !request.term || matcher.test(text) ) )
						return {
							label: text,
							value: text,
							option: this
						};
					}));
			},
			
			_removeIfInvalid: function( event, ui ) {
				// Selected an item, nothing to do
				if( ui.item ) { return; }
								
				// Search for a match (case-insensitive)
				var value = this.input.val(),
					valueLowerCase = value.toLowerCase(),
					valid = false;
				this.element.children( 'option' ).each( function() {
					if( $j( this ).text().toLowerCase() === valueLowerCase ) {
						this.selected = valid = true;
						return false;
					}
				});
				
				// Found a match, nothing to do
				if( valid ) { return; }
				
				// Remove invalid value
				this.input
					.val( '' )
					.attr( 'title', '"' + value + '" ' + bookacti_localized.error_no_results )
					.tooltip( 'open' );
				this.element.val( '' );
				this._delay( function() {
					this.input.tooltip( 'close' ).attr( 'title', '' );
				}, 2500 );
				this.input.autocomplete( 'instance' ).term = '';
			},

			_destroy: function() {
				this.wrapper.remove();
				this.element.show();
			}
		});
	})( jQuery );

	$j( function() {
		$j( '.bookacti-user-selectbox' ).bookacti_combobox();
	});
}


/**
 * Init a new moment function: Format format moment with PHP date format
 */
function bookacti_init_moment_format_from_php_date_format() {
	(function (m) {
		/*
		 * PHP => moment.js
		 * Will take a php date format and convert it into a JS format for moment
		 * http://www.php.net/manual/en/function.date.php
		 * http://momentjs.com/docs/#/displaying/format/
		 */
		var formatMap = {
				d: 'DD',
				D: 'ddd',
				j: 'D',
				l: 'dddd',
				N: 'E',
				S: function () {
					return '[' + this.format('Do').replace(/\d*/g, '') + ']';
				},
				w: 'd',
				z: function () {
					return this.format('DDD') - 1;
				},
				W: 'W',
				F: 'MMMM',
				m: 'MM',
				M: 'MMM',
				n: 'M',
				t: function () {
					return this.daysInMonth();
				},
				L: function () {
					return this.isLeapYear() ? 1 : 0;
				},
				o: 'GGGG',
				Y: 'YYYY',
				y: 'YY',
				a: 'a',
				A: 'A',
				B: function () {
					var thisUTC = this.clone().utc(),
					// Shamelessly stolen from http://javascript.about.com/library/blswatch.htm
						swatch = ((thisUTC.hours() + 1) % 24) + (thisUTC.minutes() / 60) + (thisUTC.seconds() / 3600);
					return Math.floor(swatch * 1000 / 24);
				},
				g: 'h',
				G: 'H',
				h: 'hh',
				H: 'HH',
				i: 'mm',
				s: 'ss',
				u: '[u]', // not sure if moment has this
				e: '[e]', // moment does not have this
				I: function () {
					return this.isDST() ? 1 : 0;
				},
				O: 'ZZ',
				P: 'Z',
				T: '[T]', // deprecated in moment
				Z: function () {
					return parseInt(this.format('ZZ'), 10) * 36;
				},
				c: 'YYYY-MM-DD[T]HH:mm:ssZ',
				r: 'ddd, DD MMM YYYY HH:mm:ss ZZ',
				U: 'X'
			},
			formatEx = /[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]/g;

		moment.fn.formatPHP = function (format) {
			var that = this;

			return this.format(format.replace(formatEx, function (phpStr) {
				return typeof formatMap[phpStr] === 'function' ? formatMap[phpStr].call(that) : formatMap[phpStr];
			}));
		};
	}(moment));
}