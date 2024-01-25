$j( document ).ready( function() { 
	/**
	 * Update multilangual fields with qTranslate-XT - on keyup
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
	$j( '.bookacti-show-hide-advanced-options' ).on( 'click', function(){
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
	 * @version 1.15.5
	 */
	$j( '.bookacti-backend-dialog' ).dialog({
		"open": function() {
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
		"_allowInteraction": function( e ) {
			return ! ( ( ! $j( e.target ).is( '.select2-input' ) ) || this._super( e ) );
		}
	});
	
	
	/**
	 * Convert duration from days/hours/minutes to seconds - on change
	 * @since 1.8.0
	 * @version 1.15.8
	 */
	$j( 'body' ).on( 'keyup mouseup change', '.bookacti-duration-field', function() {
		var field_value = $j( this ).closest( '.bookacti-duration-field-container' ).siblings( '.bookacti-duration-value' );
		var days        = field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="day"]' ).val();
		var hours       = field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="hour"]' ).val();
		var minutes     = field_value.siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="minute"]' ).val();
		var value       = '';
		if( $j.isNumeric( days ) || $j.isNumeric( hours ) || $j.isNumeric( minutes ) ) {
			value = 0;
			if( $j.isNumeric( minutes ) ) { value += parseInt( minutes ) * 60; }
			if( $j.isNumeric( hours ) )   { value += parseInt( hours ) * 3600; }
			if( $j.isNumeric( days ) )    { value += parseInt( days ) * 86400; }
		}
		field_value.val( value ).trigger( 'change' );
		
		// Display an hint below avilability period fields to help setting the appropriate value - on change
		$j( this ).closest( '.bookacti-duration-field-container' ).siblings( '.bookacti-duration-hint' ).remove();
		if( ! $j.isNumeric( value ) ) { value = 0; }
		var hint = moment.utc().add( value + bookacti_localized.utc_offset, 's' ).formatPHP( bookacti_localized.date_format_long );
		$j( this ).closest( '.bookacti-duration-field-container' ).parent().find( '.bookacti-duration-field-container' ).last().after( '<div class="bookacti-duration-hint">' + hint + '</div>' );
	});
	
	
	/**
	 * Add a Days off line
	 * @since 1.13.0
	 */
	$j( '.bookacti-date-intervals-container' ).on( 'click', '.bookacti-add-date-interval', function() {
		bookacti_add_days_off_row( $j( this ).closest( '.bookacti-date-intervals-container' ).find( '.bookacti-date-intervals-table-container' ) );
	});
	
	
	/**
	 * Delete a Days off line
	 * @since 1.13.0
	 */
	$j( '.bookacti-date-intervals-container' ).on( 'click', '.bookacti-delete-date-interval', function() {
		bookacti_delete_days_off_row( $j( this ).closest( '.bookacti-date-intervals-container' ).find( '.bookacti-date-intervals-table-container' ), $j( this ).closest( 'tr' ) );
	});
	
	
	/**
	 * Reset custom Days off
	 * @since 1.13.0
	 * @param {Event} e
	 * @param {String} scope
	 */
	$j( 'body' ).on( 'bookacti_empty_all_dialogs_forms', function( e, scope ) {
		bookacti_delete_days_off_rows( $j( scope + ' .bookacti-date-intervals-table-container' ) );
		$j( scope + ' input.bookacti-date-interval-from, ' + scope + ' input.bookacti-date-interval-to' ).attr( 'min', '' ).attr( 'max', '' );
	});
});


/**
 * Toggle advanced options
 * @version 1.8.0
 * @param {HTMLElement} button
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
 * Empty all dialog forms fields
 * @version 1.15.19
 * @param {string} scope
 */
function bookacti_empty_all_dialog_forms( scope ) {
	scope = typeof scope === 'undefined' || ! scope ? '.bookacti-backend-dialog ' : scope + ' ';

	$j( scope + '.bookacti-form-error' ).remove();
	$j( scope + '.bookacti-notices' ).not( scope + '.bookacti-booking-system-container .bookacti-notices' ).remove();
	$j( scope + '.bookacti-booking-system-container .bookacti-notices' ).empty().hide();
	$j( scope + '.bookacti-input-warning' ).removeClass( 'bookacti-input-warning' );
	$j( scope + '.bookacti-input-error' ).removeClass( 'bookacti-input-error' );
	$j( scope + 'input[type="hidden"]:not([name="action"]):not([name^="nonce"]):not([name="_wp_http_referer"]):not([name="qtranslate-edit-language"]):not(.bookacti-onoffswitch-hidden-input)' ).val( '' );
	$j( scope + 'input[type="text"]:not([readonly])' ).val( '' );
	$j( scope + 'input[type="email"]' ).val( '' );
	$j( scope + 'input[type="password"]' ).val( '' );
	$j( scope + 'input[type="tel"]' ).val( '' );
	$j( scope + 'input[type="number"]' ).val( '' );
	$j( scope + 'input[type="date"]' ).val( '' );
	$j( scope + 'input[type="time"]' ).val( '' );
	$j( scope + 'textarea' ).val( '' );
	$j( scope + 'input[type="color"]' ).val( '' );
	$j( scope + 'input[type="checkbox"]' ).prop( 'checked', false );
	$j( scope + 'input[type="radio"]' ).prop( 'checked', false );
	$j( scope + 'select' ).val( null ).trigger( 'change' );
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
 * @version 1.15.8
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
		if( $j( scope + 'select[name="' + field_name + '"]' ).length && $j.isArray( value ) && value.length > 1 ) {
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

		// Select
		} else if( $j( scope + 'select[name="' + field_name + '"]' ).length ) {
			$j( scope + 'select[name="' + field_name + '"] option[value="' + value + '"]' ).prop( 'selected', true );
			$j( scope + 'select[name="' + field_name + '"]' ).trigger( 'change' );

		// Select multiple
		} else if( $j( scope + 'select[name="' + field_name + '[]"]' ).length ) {
			if( ! $j.isArray( value ) ) { value = [ value ]; }
			$j.each( value, function( i, option_value ) {
				var option = $j( scope + 'select[name="' + field_name + '[]"] option[value="' + option_value + '"]' );
				$j( scope + 'select[name="' + field_name + '[]"] option[value="' + option_value + '"]' ).prop( 'selected', true );
				if( $j( scope + 'select[name="' + field_name + '[]"]' ).data( 'sortable' ) ) {
					option.detach();
					$j( scope + 'select[name="' + field_name + '[]"]' ).append( option );
				}
			});
			$j( scope + 'select[name="' + field_name + '[]"]' ).trigger( 'change' );

		// Input and Textarea
		} else {
			// Do not handle file inputs
			if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'file' ) { 
				return true; // Jump to next field
			}
			
			// Default color
			if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'color' && ! value ) { 
				value = '#3a87ad';
			}
			
			// If the time value is greater than 24:00, reset it to 00:00
			if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'time' && parseInt( value.substr( 0, 2 ) ) >= 24 ) { value = bookacti_pad( parseInt( value.substr( 0, 2 ) ) % 24, 2 ) + value.substr( 2 ); }
			
			$j( scope + 'input[name="' + field_name + '"]' ).val( value ).trigger( 'change' );
			$j( scope + 'textarea[name="' + field_name + '"]' ).val( value ).trigger( 'change' );
			
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
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="day"]' ).val( days ).trigger( 'change' );
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="hour"]' ).val( hours ).trigger( 'change' );
				$j( scope + 'input[name="' + field_name + '"].bookacti-duration-value' ).siblings( '.bookacti-duration-field-container' ).find( '.bookacti-duration-field[data-duration-unit="minute"]' ).val( minutes ).trigger( 'change' );
			}
		}
	});
}


/**
 * Switch a selectbox to multiple
 * @version 1.15.13
 * @param {HTMLElement} checkbox
 */
function bookacti_switch_select_to_multiple( checkbox ) {
	if( ! $j( checkbox ).length ) { return; }
	
	var select_id	= $j( checkbox ).data( 'select-id' );
	var select_name	= $j( 'select#' + select_id ).attr( 'name' );
	var is_checked	= $j( checkbox ).is( ':checked' );
	
	// Get the currently selected values
	var values = $j( 'select#' + select_id ).val();
	
	$j( 'select#' + select_id ).prop( 'multiple', is_checked );
	
	// Forbidden values if multiple selection is allowed
	$j( 'select#' + select_id + ' option[value="all"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="none"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="parent"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="site"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[data-not-multiple="1"]' ).prop( 'disabled', is_checked );
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
		var first_available_value = null;
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
	
	if( $j( 'select#' + select_id ).hasClass( 'select2-hidden-accessible' ) ) {
		if( ! $j.fn.select2 ) { return; }
		$j( 'select#' + select_id ).select2( 'destroy' );
		bookacti_select2_init();
	}
	
	$j( 'select#' + select_id ).trigger( 'bookacti_switched_multiple' );
}


/**
 * Fill item boxes
 * @since 1.8.3
 * @version 1.15.4
 * @param {HTMLElement} selectbox
 * @param {Array} item_ids
 * @param {string} item_type
 */
function bookacti_fill_items_selectbox( selectbox, item_ids, item_type ) {
	item_type = item_type || 'users';
	if( ! selectbox.length ) { return; }
	
	// Convert object to array
	if( typeof item_ids === 'object' ) { item_ids = Object.values( item_ids ); }
	
	// Add unknown options, Sort the options if sortable
	var unknown_item_ids = [];
	if( item_ids.length ) {
		$j.each( item_ids, function( i, item_id ) {
			var option = selectbox.find( 'option[value="' + item_id + '"]' );
			// Add the option
			if( ! option.length ) {
				selectbox.append( '<option value="' + item_id + '" class="bookacti-unknown-item">' + item_id + '</option>' );
				if( $j.isNumeric( item_id ) ) { unknown_item_ids.push( parseInt( item_id ) ); }
			// Move the option to the bottom
			} else if( selectbox.data( 'sortable' ) ) {
				option.detach();
				selectbox.append( option );
			}
		});
	}
	
	// Select / Unselect options and trigger change for select2
	selectbox.val( item_ids.length ? item_ids : null ).trigger( 'change' );
	
	if( ! unknown_item_ids.length ) { return; }
	
	bookacti_add_loading_html( selectbox.parent() );
	
	// Try to retrieve unknow items label
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { 
			action: 'bookactiSelect2Query_' + item_type,
			id__in: unknown_item_ids,
			name: selectbox.attr( 'name' ) ? selectbox.attr( 'name' ) : '',
			id: selectbox.attr( 'id' ) ? selectbox.attr( 'id' ) : '',
			nonce: bookacti_localized.nonce_query_select2_options
		},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'success' ) {
				if( response.options.length ) {
					$j.each( response.options, function( i, option ) {
						selectbox.find( '.bookacti-unknown-item[value="' + option.id + '"]' ).html( option.text ).removeClass( 'bookacti-unknown-item' );
					});
					// Refresh select2
					if( selectbox.hasClass( 'select2-hidden-accessible' ) ) { selectbox.select2( 'destroy' ); bookacti_select2_init(); }
				}
			
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_remove_loading_html( selectbox.parent() );
		}
	});
}


/**
 * Show or hide activities depending on the selected template
 * @version 1.8.6
 * @param {array} template_ids
 * @param {HTMLElement} options
 */
function bookacti_show_hide_template_related_options( template_ids, options ) {
	// Init variables
	var change_selected = [];
	
	if( $j.isNumeric( template_ids ) ) { template_ids = [ template_ids ]; }
	if( ! $j.isArray( template_ids ) ) { template_ids = []; }
	
	// Show all
	options.prop( 'disabled', false );
	options.removeClass( 'bookacti-hide-fields' );
	
	if( $j.isEmptyObject( template_ids ) ) { return; }
	
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
	
	// Refresh select2
	if( options.parent( 'select' ).hasClass( 'select2-hidden-accessible' ) ) { options.parent( 'select' ).select2( 'destroy' ); bookacti_select2_init(); }
}


/**
 * Update multilangual fields with qTranslate-XT
 * @param {HTMLElement} field
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
 * @param {HTMLElement} field
 */
function bookacti_refresh_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		$j( field ).removeClass('qtranxs-translatable');
		var h = qtx.refreshContentHook( field );
		$j( field ).addClass('qtranxs-translatable');
		
		// Refresh tinyMCE (from "qtranslate-xt\admin\js\common.js" updateTinyMCE line 588)
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


/**
 * Fill default Days off fields
 * @since 1.13.0
 * @param {HTMLElement} container
 * @param {Array} entries
 */
function bookacti_fill_days_off( container, entries ) {
	if( typeof entries === 'undefined' ) { return; }
	if( ! $j.isArray( entries ) && ! $j.isPlainObject( entries ) ) { return; }
	if( entries.length <= 0 ) { return; }
	
	// Reset Days off table
	bookacti_delete_days_off_rows( container );
	
	var tbody = container.find( 'tbody' );
	
	var i = 0;
	$j.each( entries, function( j, entry ) {
		if( i > 0 ) { bookacti_add_days_off_row( container ); }
		tbody.find( 'tr:last .bookacti-date-interval-from' ).val( entry.from );
		tbody.find( 'tr:last .bookacti-date-interval-to' ).val( entry.to );
		++i;
	});
}


/**
 * Add a Days off row
 * @since 1.13.0
 * @param {HTMLElement} container
 */
function bookacti_add_days_off_row( container ) {
	var tbody = container.find( 'tbody' );
	var name_i = container.data( 'name' ) + '[' + tbody.find( 'tr' ).length + ']';
	tbody.find( 'tr:first' ).clone().appendTo( tbody );
	tbody.find( 'tr:last .bookacti-date-interval-from' ).attr( 'name', name_i + '[from]' ).val( '' );
	tbody.find( 'tr:last .bookacti-date-interval-to' ).attr( 'name', name_i + '[to]' ).val( '' );
}


/**
 * Delete a Days off row
 * @since 1.13.0
 * @param {HTMLElement} container
 * @param {HTMLElement} row
 */
function bookacti_delete_days_off_row( container, row ) {
	row = row || null;
	var tbody = container.find( 'tbody' );
	// If there is only one row, empty the fields
	if( tbody.find( 'tr' ).length <= 1 ) {
		tbody.find( 'tr:first .bookacti-date-interval-from, tr:first .bookacti-date-interval-to' ).val( '' );
		
	// Else, delete the whole row and reset indexes
	} else if( row != null ) {
		row.remove();
		var i = 0;
		var name = container.data( 'name' );
		tbody.find( 'tr' ).each( function() {
			var name_i = name + '[' + i + ']';
			$j( this ).find( '.bookacti-date-interval-from' ).attr( 'name', name_i + '[from]' );
			$j( this ).find( '.bookacti-date-interval-to' ).attr( 'name', name_i + '[to]' );
			++i;
		});
	}
}


/**
 * Delete all Days off rows
 * @since 1.13.0
 * @param {HTMLElement} container
 */
function bookacti_delete_days_off_rows( container ) {
	var tbody = container.find( 'tbody' );
	tbody.find( 'tr:not(:first)' ).remove();
	bookacti_delete_days_off_row( container, tbody.find( 'tr:first' ) );
}