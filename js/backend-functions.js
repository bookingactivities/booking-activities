$j( document ).ready( function() { 
	/**
	 * Update multilangual fields with Qtranslate X - on keyup
	 */
	$j( '.qtranxs-translatable' ).on( 'keyup', function() {
		bookacti_update_qtx_field( this );
	});
	
	
	/**
	 * Init tabs
	 */
	$j( '.bookacti-tabs' ).tabs();
	
	
	/**
	 * Toggle Advanced options on click - on click
	 */
	$j( '.bookacti-show-hide-advanced-options' ).on( 'click', function( e ){
		bookacti_show_hide_advanced_options( $j( this ) );
	});
	
	
	/**
	 * Switch selectbox to multiple - on change
	 */
	$j( 'body' ).on( 'change', '.bookacti-multiple-select-container .bookacti-multiple-select', function(){
		bookacti_switch_select_to_multiple( this );
	});
	
	
	/**
	 * Init tooltip
	 */
	bookacti_init_tooltip();
	
	
	/**
	 * Dismiss notices - on click
	 */
	$j( '#bookacti-dismiss-5stars-rating' ).on( 'click', function() { bookacti_dismiss_5stars_rating_notice(); } );
	
	
	/**
	 * Init select2
	 * @since 1.7.19
	 */
	bookacti_select2_init();
	
	
	/**
	 * Allow select2 to work in a jquery-ui dialog
	 * @since 1.7.19
	 */
	$j( '.bookacti-backend-dialog' ).dialog({
		autoOpen: false,
		open: function() {
			if( $j.ui && $j.ui.dialog && ! $j.ui.dialog.prototype._allowInteractionRemapped && $j( this ).closest( '.ui-dialog' ).length ) {
				if( $j.ui.dialog.prototype._allowInteraction ) {
					$j.ui.dialog.prototype._allowInteraction = function( e ) {
						if( $j( e.target ).closest( '.select2-drop' ).length ) { return true; }
						if( typeof ui_dialog_interaction === 'undefined' ) { return true; }
						return ui_dialog_interaction.apply( this, arguments );
					};
					$j.ui.dialog.prototype._allowInteractionRemapped = true;
				} else {
					$j.error( 'You must upgrade jQuery UI or else.' );
				}
			}
		},
		_allowInteraction: function( event ) {
			return ! ( ( ! $j( event.target ).is( '.select2-input' ) ) || this._super( event ) );
		}
	});
	
	
	/**
	 * Convert duration from days/hours/minutes to seconds - on change
	 * @since 1.8.0
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-duration-field', function() {
		var field_value = $j( this ).closest( '.bookacti-duration-field-container' ).siblings( '.bookacti-duration-value' );
		var days		= field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="day"]' ).val();
		var hours		= field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="hour"]' ).val();
		var minutes		= field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="minute"]' ).val();
		var value		= '';
		if( $j.isNumeric( days ) || $j.isNumeric( hours ) || $j.isNumeric( minutes ) ) {
			value = 0;
			if( $j.isNumeric( minutes ) )	{ value += parseInt( minutes ) * 60; }
			if( $j.isNumeric( hours ) )		{ value += parseInt( hours ) * 3600; }
			if( $j.isNumeric( days ) )		{ value += parseInt( days ) * 86400; }
		}
		field_value.val( value ).trigger( 'change' );
		
		// Display an hint below avilability period fields to help setting the appropriate value - on change
		$j( this ).closest( '.bookacti-duration-field-container' ).siblings( '.bookacti-duration-hint' ).remove();
		if( ! $j.isNumeric( value ) ) { value = 0; }
		var hint = moment.utc().add( value + bookacti_localized.utc_offset, 's' ).formatPHP( bookacti_localized.date_format_long );
		$j( this ).closest( '.bookacti-duration-field-container' ).parent().append( '<div class="bookacti-duration-hint">' + hint + '</div>' );
	});
});


/**
 * Toggle advanced options
 * @version 1.8.0
 * @param {dom_element} button
 */
function bookacti_show_hide_advanced_options( button ) {
	// Find toggle elements
	var toogled_id = button.attr( 'for' );
	var toggled_elements = button.closest( 'form' ).find( '.bookacti-hidden-field' );
	if( toogled_id && $j( '#' + toogled_id ).length ) { toggled_elements = $j( '#' + toogled_id ); }
	
	button.toggleClass( 'bookacti-show-advanced-options bookacti-hide-advanced-options' );
	if( button.hasClass( 'bookacti-show-advanced-options' ) ) { 
		button.html( button.data( 'show-title' ) );
		toggled_elements.hide( 200, function() {
			if( button.closest( 'fieldset' ).length ) { button.closest( 'fieldset' ).addClass( 'bookacti-fieldset-no-css' ); }
		});
	}
	else if( button.hasClass( 'bookacti-hide-advanced-options' ) ) { 
		button.html( button.data( 'hide-title' ) );
		if( button.closest( 'fieldset' ).length ) { button.closest( 'fieldset' ).removeClass( 'bookacti-fieldset-no-css' ); }
		toggled_elements.show( 200 );
	}
}


/**
 * Init Add / Remove items boxes
 */
function bookacti_init_add_and_remove_items() {
	// Add a item to the items list
	$j( '.bookacti-items-container' ).on( 'click', '.bookacti-add-items', function( e ){
		var wrap = $j( this ).parents( '.bookacti-items-container' );
		
		// Retrieve data
		var is_multiple			= wrap.find( '.bookacti-add-new-items-select-box' ).is( '[multiple]' );
		var selected_item_ids	= wrap.find( '.bookacti-add-new-items-select-box' ).val();
		
		// Build an array of item ids
		var items_ids = selected_item_ids;
		if( ! is_multiple ) { items_ids = [ selected_item_ids ]; }
		
		$j.each( items_ids, function( i, item_id ){
			wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).clone().appendTo( wrap.find( '.bookacti-items-select-box' ) );
			wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).hide().attr( 'disabled', true );
			wrap.find( '.bookacti-add-new-items-select-box' ).val( wrap.find( '.bookacti-add-new-items-select-box option:enabled:first' ).val() );
		});
	});
	
	// Remove an item from the items list
	$j( '.bookacti-items-container' ).on( 'click', '.bookacti-remove-items', function( e ){
		
		var wrap = $j( this ).parents( '.bookacti-items-container' );
		var type = wrap.data( 'type' );
		var cannot_delete = '';
				if( type === 'users' )		{ cannot_delete = bookacti_localized.current_user_id; } 
		else	if( type === 'templates' )	{ cannot_delete = bookacti.selected_template; } 
		
		// Retrieve data
		var is_multiple			= wrap.find( '.bookacti-items-select-box' ).is( '[multiple]' );
		var selected_item_ids	= wrap.find( '.bookacti-items-select-box' ).val();
		
		// Build an array of item ids
		var items_ids = selected_item_ids;
		if( ! is_multiple ) { items_ids = [ selected_item_ids ]; }
		
		$j.each( items_ids, function( i, item_id ){
			if( item_id != cannot_delete ) {
				wrap.find( '.bookacti-items-select-box option[value="' + item_id + '"]' ).remove();
				wrap.find( '.bookacti-add-new-items-select-box option[value="' + item_id + '"]' ).show().attr( 'disabled', false );
				wrap.find( '.bookacti-add-new-items-select-box' ).val( item_id );
			}
		});
	});
}


/**
 * Empty all dialog forms fields
 * @version 1.8.0
 * @param {string} scope
 */
function bookacti_empty_all_dialog_forms( scope ) {
	scope = typeof scope === 'undefined' || ! scope ? '.bookacti-backend-dialog ' : scope + ' ';

	$j( scope + '.bookacti-form-error' ).remove();
	$j( scope + 'input[type="hidden"]:not([name^="nonce"]):not([name="_wp_http_referer"]):not([name="qtranslate-edit-language"]):not(.bookacti-onoffswitch-hidden-input)' ).val( '' );
	$j( scope + 'input[type="text"]' ).val( '' );
	$j( scope + 'input[type="email"]' ).val( '' );
	$j( scope + 'input[type="password"]' ).val( '' );
	$j( scope + 'input[type="tel"]' ).val( '' );
	$j( scope + 'input[type="number"]' ).val( '' );
	$j( scope + 'input[type="date"]' ).val( '' );
	$j( scope + 'input[type="time"]' ).val( '' );
	$j( scope + 'textarea' ).val( '' );
	$j( scope + 'input[type="color"]' ).val( '#3a87ad' );
	$j( scope + 'input[type="checkbox"]' ).prop( 'checked', false );
	$j( scope + 'input[type="radio"]' ).prop( 'checked', false );
	$j( scope + 'option' ).prop( 'selected', false );
	$j( scope + '.exception' ).remove();
	$j( scope + 'select.bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );
	$j( scope + 'select.bookacti-items-select-box option' ).remove();
	$j( scope + '.bookacti-duration-hint' ).remove();
	
	if( $j( scope + 'input[type="file"]' ).length ) {
		$j( scope + 'input[type="file"]' ).each( function() {
			$j( this ).wrap('<form>').closest('form').get( 0 ).reset();
			$j( this ).unwrap();
		});
	}
	
	// Reset tinyMCE editor
	if( typeof tinyMCE !== 'undefined' ) { 
		if( tinyMCE ) { 
			$j( scope + 'textarea.wp-editor-area' ).each( function(){
				var tmce_id = $j( this ).attr( 'id' );
				if( tinyMCE.get( tmce_id ) ) {
					tinyMCE.get( tmce_id ).setContent( '' );
				}
			});
		}
	}

	// Reset switchable multiple select
	if( $j( scope + '.bookacti-multiple-select' ).length ) {
		$j( scope + '.bookacti-multiple-select' ).each( function(){
			bookacti_switch_select_to_multiple( this );
		});
	}

	$j( 'body' ).trigger( 'bookacti_empty_all_dialogs_forms', [ scope ] );
}


/**
 * Fill custom settings fields in a form
 * @version 1.8.0
 * @param {array} fields
 * @param {string} field_prefix
 * @param {qtring} scope
 */
function bookacti_fill_fields_from_array( fields, field_prefix, scope ) {
	field_prefix = field_prefix || '';
	scope = typeof scope === 'undefined' || ! scope ? '' : scope + ' ';
	
	$j.each( fields, function( key, value ) {
		var field_name = field_prefix ? field_prefix + '[' + key + ']' : key;
		
		// If the value is also a plain object, fill its fields recursively
		if( $j.isPlainObject( value ) ) {
			bookacti_fill_fields_from_array( value, field_name, scope );
			return true; // Jump to next field
		}
		
		// Switch select multiple to simple
		if( $j( scope + 'select[name="' + field_name + '[]"]' ).length && ( ! $j.isArray( value ) || ( $j.isArray( value ) && value.length <= 1 ) ) ) {
			var field_id = $j( scope + 'select[name="' + field_name + '[]"]' ).attr( 'id' );
			if( $j( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' ).length ) {
				$j( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' ).prop( 'checked', false );
				bookacti_switch_select_to_multiple( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' );
				$j( scope + 'select[name="' + field_name + '"] option' ).prop( 'selected', false );
			}
		}
		// Switch simple select to multiple
		if( $j( scope + 'select[name="' + field_name + '"]:not(.bookacti-items-select-box)' ).length && $j.isArray( value ) && value.length > 1 ) {
			var field_id = $j( scope + 'select[name="' + field_name + '"]' ).attr( 'id' );
			if( $j( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' ).length ) {
				$j( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' ).prop( 'checked', true );
				bookacti_switch_select_to_multiple( scope + 'input.bookacti-multiple-select[data-select-id="' + field_id + '"]' );
				$j( scope + 'select[name="' + field_name + '[]"] option' ).prop( 'selected', false );
			}
		}
		
		// Checkbox
		if( $j( scope + 'input[type="checkbox"][name="' + field_name + '[]"]' ).length 
		||  $j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).length ) {

			if( $j.isArray( value ) ){
				$j( scope + 'input[type="checkbox"][name="' + field_name + '[]"]' ).prop( 'checked', false );
				$j.each( value, function( i, checkbox_value ){
					$j( scope + 'input[type="checkbox"][name="' + field_name + '[]"][value="' + checkbox_value + '"]' ).prop( 'checked', true ).trigger( 'change' );
				});
			} else if( value == 1 ) {
				$j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).prop( 'checked', true ).trigger( 'change' );
			} else {
				$j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).prop( 'checked', false ).trigger( 'change' );
			}

		// Radio
		} else if( $j( scope + 'input[name="' + field_name + '"]' ).is( ':radio' ) ) {
			$j( scope + 'input[name="' + field_name + '"][value="' + value + '"]' ).prop( 'checked', true ).trigger( 'change' );

		// Select items
		} else if( $j( scope + 'select[name="' + field_name + '[]"].bookacti-items-select-box' ).length ) {
			if( ! $j.isArray( value ) ) { value = [ value ]; }
			var selectbox = $j( scope + 'select[name="' + field_name + '[]"].bookacti-items-select-box' );
			var add_selectbox = selectbox.closest( '.bookacti-items-container' ).find( '.bookacti-add-new-items-select-box' );
			
			// Reset selectboxes
			add_selectbox.find( 'option' ).show().attr( 'disabled', false );
			selectbox.find( 'option' ).remove();
			
			// Add items
			$j.each( value, function( i, val ) {
				add_selectbox.find( 'option[value="' + val + '"]' ).clone().appendTo( selectbox );
				add_selectbox.find( 'option[value="' + val + '"]' ).hide().attr( 'disabled', true );
				if( add_selectbox.val() == val || ! add_selectbox.val() ) {
					add_selectbox.val( add_selectbox.find( 'option:enabled:first' ).val() );
				}
			});
			
			// Select all
			if( selectbox.find( 'option' ).length ) {
				selectbox.find( 'option' ).prop( 'selected', true );
				selectbox.trigger( 'change' );
			}
		
		// Select
		} else if( $j( scope + 'select[name="' + field_name + '"]' ).length ) {
			$j( scope + 'select[name="' + field_name + '"] option[value="' + value + '"]' ).prop( 'selected', true );
			$j( scope + 'select[name="' + field_name + '"]' ).trigger( 'change' );

		// Select multiple
		} else if( $j( scope + 'select[name="' + field_name + '[]"]' ).length ) {
			$j.each( value, function( i, option ) {
				$j( scope + 'select[name="' + field_name + '[]"] option[value="' + option + '"]' ).prop( 'selected', true );
			});
			$j( scope + 'select[name="' + field_name + '[]"]' ).trigger( 'change' );

		// Input and Textarea
		} else {
			// Do not handle file inputs
			if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'file' ) { 
				return true; // Jump to next field
			}
			// If the time value is 24:00, reset it to 00:00
			if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'time' && value === '24:00' ) { value = '00:00'; }
			$j( scope + 'input[name="' + field_name + '"]' ).val( value ).trigger( 'change' );;
			$j( scope + 'textarea[name="' + field_name + '"]' ).val( value ).trigger( 'change' );;
			
			// Editor
			if( typeof tinyMCE !== 'undefined' ) {
				if( tinyMCE && $j( scope + 'textarea[name="' + field_name + '"]' ).hasClass( 'wp-editor-area' ) ) {
					var tmce_id = $j( scope + 'textarea[name="' + field_name + '"]' ).attr( 'id' );
					if( tinyMCE.get( tmce_id ) ) {
						tinyMCE.get( tmce_id ).setContent( value );
					}
				}
			}
			
			// Duration
			if( $j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).length ) {
				var days, hours, minutes = '';
				if( $j.isNumeric( value ) ) {
					var total = parseInt( value );
					if( total >= 0 ) {
						days = Math.floor( total / 86400 ); total = total % 86400;
						hours = Math.floor( total / 3600 ); total = total % 3600;
						minutes = Math.floor( total / 60 );
					}
				}
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="day"]' ).val( days ).trigger( 'change' );
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="hour"]' ).val( hours ).trigger( 'change' );
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-unit="minute"]' ).val( minutes ).trigger( 'change' );
			}
		}
	});
}


/**
 * Switch a selectbox to multiple
 * @version 1.7.17
 * @param {dom_element} checkbox
 */
function bookacti_switch_select_to_multiple( checkbox ) {
	if( ! $j( checkbox ).length ) { return; }
	
	var select_id	= $j( checkbox ).data( 'select-id' );
	var select_name	= $j( 'select#' + select_id ).attr( 'name' );;
	var is_checked	= $j( checkbox ).is( ':checked' );
	
	// Get the currently selected values
	var values = $j( 'select#' + select_id ).val();
	
	$j( 'select#' + select_id ).prop( 'multiple', is_checked );
	
	// Forbidden values if multiple selection is allowed
	$j( 'select#' + select_id + ' option[value="all"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="none"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="parent"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="site"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option:disabled:selected' ).prop( 'selected', false );
	
	// Add the [] at the end of the select name
	if( is_checked && select_name.indexOf( '[]' ) < 0 ) { 
		$j( 'select#' + select_id ).attr( 'name', select_name + '[]' ); 
		$j( checkbox ).closest( '.bookacti-multiple-select-container' ).find( 'label span.dashicons' ).removeClass( 'dashicons-plus' ).addClass( 'dashicons-minus' );
	} else { 
		$j( 'select#' + select_id ).attr( 'name', select_name.replace( '[]', '' ) ); 
		$j( checkbox ).closest( '.bookacti-multiple-select-container' ).find( 'label span.dashicons' ).removeClass( 'dashicons-minus' ).addClass( 'dashicons-plus' );
	}
	
	// Select the first available value
	if( ! is_checked ) {
		var first_available_value = $j( 'select#' + select_id + ' option:not(:disabled):first' ).length ? $j( 'select#' + select_id + ' option:not(:disabled):first' ).val() : '';
		if( values ) {
			if( ! $j.isArray( values ) ) { values = [ values ]; }
			$j.each( values, function( i, value ) {
				var option = $j( 'select#' + select_id + ' option[value="' + value + '"]' );
				if( option.length ) {
					if( ! option.is( ':disabled' ) ) {
						first_available_value = option.attr( 'value' );
						return false;
					}
				}
			});
		}
		$j( 'select#' + select_id ).val( first_available_value ).trigger( 'change' );
	}
}


/**
 * Show or hide activities depending on the selected template
 * @version 1.7.0
 * @param {array} template_ids
 * @param {dom_element} options
 */
function bookacti_show_hide_template_related_options( template_ids, options ) {
	
	// Init variables
	var change_selected = [];
	
	if( $j.isNumeric( template_ids ) ) { template_ids = [ template_ids ]; }
	
	// Show all
	options.prop( 'disabled', false );
	options.removeClass( 'bookacti-hide-fields' );
	
	// Hide not allowed
	options.each( function() {
		
		var option = $j( this );
		
		// Retrieve allowed templates array
		var allowed_templates = option.data( 'bookacti-show-if-templates' ).toString();
		if( allowed_templates.indexOf( ',' ) >= 0 ) {
			allowed_templates = allowed_templates.split( ',' );
		} else {
			allowed_templates = [ allowed_templates ];
		}
		
		// Hide not allowed data and flag if one of them was selected
		var hide = true;
		$j.each( template_ids, function( i, template_id ) {
			if( $j.inArray( template_id.toString(), allowed_templates ) >= 0 ) {
				hide = false;
			}
		});
		
		if( hide ) {
			if( option.is( ':selected' ) ) { 
				change_selected.push( option ); 
			}
			option.addClass( 'bookacti-hide-fields' );
			option.prop( 'disabled', true );
		}
	});

	// Change selected activity automatically if it gets hidden
	$j.each( change_selected, function( i, old_selected_option ) {
		old_selected_option.removeAttr( 'selected' );
		old_selected_option.siblings( 'option:not(.bookacti-hide-fields):not(:disabled):first' ).prop( 'selected', true );
	});
}


/**
 * Update multilangual fields with qtranslate X
 * @param {dom_element} field
 */
function bookacti_update_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		var active_lang = qtx.getActiveLanguage();
		var input_name	= $j( field ).attr( 'name' );

		if( active_lang !== undefined ) {
			$j( 'input[name="qtranslate-fields[' + input_name + '][' + active_lang + ']"]' ).val( $j( field ).val() );
		}
	}
}


/**
 * Refresh multilingual field to make a correct display 
 * E.g.: '[:en]Hello[:fr]Bonjour[:]' become 'Hello' and 'Bonjour' each in its own switchable field (with the LSB)
 * @param {dom_element} field
 */
function bookacti_refresh_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		$j( field ).removeClass('qtranxs-translatable');
		var h = qtx.refreshContentHook( field );
		$j( field ).addClass('qtranxs-translatable');
		
		// Refresh tinyMCE (from "qtranslate-x\admin\js\common.js" updateTinyMCE line 588)
		if( typeof tinyMCE !== 'undefined' ) {
			if( tinyMCE && $j( '#' + field.id ).hasClass( 'wp-editor-area' ) ) {
				if( tinyMCE.get( field.id ) ) {
					h.mce = tinyMCE.get( field.id );
					h.mce.setContent( h.contentField.value, { format: 'html' } );
				}
			}
		}
	}
}


/**
 * Dismiss 5Stars rating notice
 * @version 1.8.0
 */
function bookacti_dismiss_5stars_rating_notice() {
	$j( '.bookacti-5stars-rating-notice' ).remove();
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { 
			'action': 'bookactiDismiss5StarsRatingNotice',
			'nonce': bookacti_localized.nonce_dismiss_5stars_rating_notice
		},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {}
	});
}

