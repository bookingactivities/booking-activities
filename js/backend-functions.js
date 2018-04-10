$j( document ).ready( function() { 
	
	// Update multilangual fields with Qtranslate X
	$j( '.qtranxs-translatable' ).on( 'keyup', function() {
		bookacti_update_qtx_field( this );
	});
	
	// Tabs
	$j( '.bookacti-tabs' ).tabs();
	
	// Show/hide Advanced options
	$j( '.bookacti-show-hide-advanced-options' ).on( 'click', function( e ){
		bookacti_show_hide_advanced_options( $j( this ) );
	});
	
	// Switch selectbox to multiple
	$j( '.bookacti-multiple-select-container' ).on( 'change', '.bookacti-multiple-select', function(){
		bookacti_switch_select_to_multiple( this );
	});
	
	//Show or hide activities depending on the selected template
	// On load
	if( $j( '#_bookacti_template' ).length ) { 
		var template_ids	= $j( '#_bookacti_template' ).val();
		var options			= $j( '[data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options( template_ids, options ); 
	}
	// On change
	$j( '#_bookacti_template' ).on( 'change', function(){ 
		var template_ids	= $j( this ).val();
		var options			= $j( '[data-bookacti-show-if-templates]' );
		bookacti_show_hide_template_related_options( template_ids, options );
	});
	
	// Tooltip
	bookacti_init_tooltip();
	
	// Dismiss notices
	$j( '#bookacti-dismiss-5stars-rating' ).on( 'click', function(){ bookacti_dismiss_5stars_rating_notice(); });
	
});


function bookacti_show_hide_advanced_options( button ) {
	button.closest( 'form' ).find( '.bookacti-advanced-option' ).toggle();
	button.toggleClass( 'bookacti-show-advanced-options bookacti-hide-advanced-options' );
	if( button.hasClass( 'bookacti-show-advanced-options' ) )		{ button.html( button.data( 'show-title' ) ); }
	else if( button.hasClass( 'bookacti-hide-advanced-options' ) )	{ button.html( button.data( 'hide-title' ) ); }
}

// Init Add / Remove items boxes
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


// Init tooltip
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


// Empty all dialog forms
function bookacti_empty_all_dialog_forms( scope ) {
	scope = typeof scope === 'undefined' || ! scope ? '.bookacti-backend-dialog ' : scope + ' ';

    $j( scope + '.bookacti-form-error' ).remove();
	$j( scope + 'input[type="hidden"]:not([name^="nonce"]):not([name="_wp_http_referer"])' ).val( '' );
	$j( scope + 'input[type="text"]' ).val( '' );
    $j( scope + 'input[type="number"]' ).val( '' );
    $j( scope + 'input[type="date"]' ).val( '' );
    $j( scope + 'textarea' ).val( '' );
    $j( scope + 'input[type="color"]' ).val( '#3a87ad' );
    $j( scope + 'input[type="checkbox"]' ).prop( 'checked', false );
    $j( scope + 'input[type="radio"]' ).prop( 'checked', false );
    $j( scope + 'option' ).prop( 'selected', false );
    $j( scope + '.exception' ).remove();
    $j( scope + 'select.bookacti-add-new-items-select-box option' ).show().attr( 'disabled', false );
    $j( scope + 'select.bookacti-items-select-box option' ).remove();
	
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
}


// Fill custom settings fields in a form
function bookacti_fill_fields_from_array( fields, field_prefix, scope ) {
	field_prefix = field_prefix || '';
	scope = typeof scope === 'undefined' || ! scope ? '' : scope + ' ';
	
	$j.each( fields, function( key, value ) {
		if( fields[ key ] ) {
			var field_name = field_prefix ? field_prefix + '[' + key + ']' : key;
			
			// If the value is also a plain object, fill its fields recursively
			if( $j.isPlainObject( value ) ) {
				bookacti_fill_fields_from_array( value, field_name, scope );
				return true; // Jump to next field
			}
			
			// Checkbox
			if( $j( scope + 'input[type="checkbox"][name="' + field_name + '[]"]' ).length 
			||  $j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).length ) {
				
				if( $j.isArray( value ) ){
					$j( scope + 'input[type="checkbox"][name="' + field_name + '[]"]' ).prop( 'checked', false );
					$j.each( value, function( i, checkbox_value ){
						$j( scope + 'input[type="checkbox"][name="' + field_name + '[]"][value="' + checkbox_value + '"]' ).prop( 'checked', true );
					});
				} else if( value == 1 ) {
					$j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).prop( 'checked', true );
				} else {
					$j( scope + 'input[type="checkbox"][name="' + field_name + '"]' ).prop( 'checked', false );
				}
				
			// Radio
			} else if( $j( scope + 'input[name="' + field_name + '"]' ).is( ':radio' ) ) {
				$j( scope + 'input[name="' + field_name + '"][value="' + value + '"]' ).prop( 'checked', true );
				
			// Select
			} else if( $j( scope + 'select[name="' + field_name + '"]' ).length ) {
				$j( scope + 'select[name="' + field_name + '"] option[value="' + value + '"]' ).prop( 'selected', true );
				$j( scope + 'select[name="' + field_name + '"]' ).trigger( 'change' );
				// Update user-selectbox
				if( $j( scope + 'select[name="' + field_name + '"].bookacti-user-selectbox' ).length ) {
					var new_value = $j( scope + 'select[name="' + field_name + '"].bookacti-user-selectbox option:selected' ).html();
					$j( scope + 'select[name="' + field_name + '"].bookacti-user-selectbox' ).siblings( '.bookacti-combobox' ).find( '.bookacti-combobox-input' ).val( new_value );
				}
				
			// Select multiple
			} else if( $j( scope + 'select[name="' + field_name + '[]"]' ).length ) {
				$j.each( value, function( i, option ){
					$j( scope + 'select[name="' + field_name + '[]"] option[value="' + option + '"]' ).prop( 'selected', true );
				});
				$j( scope + 'select[name="' + field_name + '[]"]' ).trigger( 'change' );
				
			// Input and Textarea
			} else {
				// If the time value is 24:00, reset it to 00:00
				if( $j( scope + 'input[name="' + field_name + '"]' ).attr( 'type' ) === 'time' && value === '24:00' ) { value = '00:00'; }
				$j( scope + 'input[name="' + field_name + '"]' ).val( value );
				$j( scope + 'textarea[name="' + field_name + '"]' ).val( value );
				if( typeof tinyMCE !== 'undefined' ) {
					if( tinyMCE && $j( scope + 'textarea[name="' + field_name + '"]' ).hasClass( 'wp-editor-area' ) ) {
						var tmce_id = $j( scope + 'textarea[name="' + field_name + '"]' ).attr( 'id' );
						if( tinyMCE.get( tmce_id ) ) {
							tinyMCE.get( tmce_id ).setContent( value );
						}
					}
				}
			}
		}
	});
}


// Switch a selectbox to multiple
function bookacti_switch_select_to_multiple( checkbox ) {
	
	var select_id	= $j( checkbox ).data( 'select-id' );
	var select_name	= $j( 'select#' + select_id ).attr( 'name' );;
	var is_checked	= $j( checkbox ).is( ':checked' );
	
	$j( 'select#' + select_id ).prop( 'multiple', is_checked );
	
	// Forbidden values if multiple selection is allowed
	$j( 'select#' + select_id + ' option[value="all"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="none"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="parent"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="site"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option:disabled:selected' ).prop( 'selected', false );
	$j( 'select#' + select_id + ' option:not(:visible):selected' ).prop( 'selected', false );
	
	// Add the [] at the end of the select name
	if( is_checked && select_name.indexOf( '[]' ) < 0 ) { 
		$j( 'select#' + select_id ).attr( 'name', select_name + '[]' ); 
		$j( checkbox ).parents( '.bookacti-multiple-select-container' ).find( 'label span.dashicons' ).removeClass( 'dashicons-plus' ).addClass( 'dashicons-minus' );
	} else { 
		$j( 'select#' + select_id ).attr( 'name', select_name.replace( '[]', '' ) ); 
		$j( checkbox ).parents( '.bookacti-multiple-select-container' ).find( 'label span.dashicons' ).removeClass( 'dashicons-minus' ).addClass( 'dashicons-plus' );
	}
}

// Show or hide activities depending on the selected template
function bookacti_show_hide_template_related_options( template_ids, options ) {
	
	// Init variables
	var change_selected = [];
	
	if( $j.isNumeric( template_ids ) ) { template_ids = [ template_ids ]; }
	
	// Show all
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
		}
	});

	// Change selected activity automatically if it gets hidden
	$j.each( change_selected, function( i, old_selected_option ) {
		old_selected_option.removeAttr( 'selected' );
		old_selected_option.siblings( 'option:not(.bookacti-hide-fields):not(:disabled):first' ).prop( 'selected', true );
	});
}


// Update multilangual fields with qtranslate X
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

//Refresh multilingual field to make a correct display 
//( '[:en]Hello[:fr]Bonjour[:]' become 'Hello' and 'Bonjour' each in its own switchable field (with the LSB) )
function bookacti_refresh_qtx_field( field ){
	if( typeof qTranslateConfig !== 'undefined' ) {
		var qtx = qTranslateConfig.js.get_qtx();
		$j( field ).removeClass('qtranxs-translatable');
		qtx.refreshContentHook( field );
		$j( field ).addClass('qtranxs-translatable');
	}
}


// Dismiss 5Stars rating notice
function bookacti_dismiss_5stars_rating_notice() {
	$j( '.bookacti-5stars-rating-notice' ).remove();
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiDismiss5StarsRatingNotice',
				'nonce': bookacti_localized.nonce_dismiss_5stars_rating_notice
			},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_update_settings;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( message_error );
				console.log( response );
			}
		},
		error: function( e ){
			console.log( e );
		},
		complete: function() { 
		}
	});
}

