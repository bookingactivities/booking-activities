$j( document ).ready( function() {
	
	//Show or hide the activity tab on product page in the backend
	bookacti_show_hide_activity_tab();
	$j( '.type_box select, .type_box input' ).on( 'change', function(){ 
		bookacti_show_hide_activity_tab();
	});
	
	//Show or hide activities depending on the selected template
	// On load
	bookacti_show_hide_template_related_options( '#_bookacti_template' );
	// On change
	$j( '#_bookacti_template, bookacti_variable_template' ).on( 'change', function(){ 
		bookacti_show_hide_template_related_options( this );
	});
	
	// Switch selectbox to multiple
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti-multiple-select', function(){
		bookacti_switch_select_to_multiple( this );
	});
	
	//Show or hide activity fields on variation page in the backend
	// On load
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_show_hide_activity_variation_fields();
		$j( '#woocommerce-product-data .bookacti_variable_template' ).each( function() {
			bookacti_show_hide_template_related_options( this, true );
		});
	});
	// On change
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_is_activity', function(){ 
		bookacti_show_hide_activity_variation_fields( this );
	});
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_template', function(){ 
		bookacti_show_hide_template_related_options( this, true );
	});
	
	//Force virtual on activities variations
	$j( '#woocommerce-product-data' ).on( 'change', '.variable_is_virtual', function(){ 
		if( $j( this ).parents( '.options' ).find( '.bookacti_variable_is_activity' ).is( ':checked' ) ) {
			if( ! $j( this ).is( ':checked' ) ) {
				$j( this ).prop( 'checked', true ).trigger( 'change' );
			}
		}
	});
});

//Show or hide the activity tab on product page in the backend
function bookacti_show_hide_activity_tab() {
	
	$j( '.bookacti_show_if_activity' ).hide();
	
	if( $j( '#_bookacti_is_activity' ).is( ':checked' ) ) {
		
		if( ( $j( '#product-type' ).val() === 'simple' || $j( '#product-type' ).val() === 'variable' ) ) {
			$j( '.bookacti_show_if_activity' ).show();
			
			if( ! $j( '#_virtual' ).is( ':checked' ) ) {
				$j( '#_virtual' ).prop( 'checked', true ).trigger( 'change' );
			}
		}
		$j( '.bookacti_hide_if_activity' ).hide();
		
	} else {
		
		$j( '.bookacti_hide_if_activity' ).show();
		
		if( $j( '#_virtual' ).is( ':checked' ) ) {
			$j( '.bookacti_hide_if_activity.hide_if_virtual' ).hide();
		}
	}
}


// Switch a selectbox to multiple
function bookacti_switch_select_to_multiple( checkbox ) {
	
	var select_id	= $j( checkbox ).data( 'select-id' );
	var select_name	= $j( 'select#' + select_id ).attr( 'name' );;
	var is_checked	= $j( checkbox ).is( ':checked' );
	
	$j( 'select#' + select_id ).prop( 'multiple', is_checked );
	
	// Forbidden values if multiple selection is allowed
	$j( 'select#' + select_id + ' option[value="none"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="parent"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option[value="site"]' ).prop( 'disabled', is_checked );
	$j( 'select#' + select_id + ' option:disabled:selected' ).prop( 'selected', false );
	$j( 'select#' + select_id + ' option:not(:visible):selected' ).prop( 'selected', false );
	
	// Add the [] at the end of the select name
	if( is_checked && select_name.indexOf( '[]' ) < 0 ) { 
		$j( 'select#' + select_id ).attr( 'name', select_name + '[]' ); 
	} else { 
		$j( 'select#' + select_id ).attr( 'name', select_name.replace( '[]', '' ) ); 
	}
}


// Show or hide activities depending on the selected template
function bookacti_show_hide_template_related_options( template, is_variation ) {
	
	is_variation = is_variation ? 1 : 0;
	
	// Init variables
	var template_ids	= $j( template ).val();
	var options			= $j( '[data-bookacti-show-if-templates]' );
	var change_selected = [];
	if( is_variation ) {
		if( template_ids === 'parent' ) { template_ids = $j( '#_bookacti_template' ).val() || $j( template ).data( 'parent' ); }
		options = $j( '[name$="[' + $j( template ).data( 'loop' ) + ']"] [data-bookacti-show-if-templates], [name$="[' + $j( template ).data( 'loop' ) + '][]"] [data-bookacti-show-if-templates]' );
	}
	
	if( $j.isNumeric( template_ids ) ) {
		template_ids = [ template_ids ];
	}
	
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
		old_selected_option.siblings( 'option:not(.bookacti-hide-fields):not(:disabled):first' ).attr( 'selected', 'selected' );
	});
}


//Show or hide activity fields on variation page in the backend
function bookacti_show_hide_activity_variation_fields( checkbox ) {
	
	checkbox = checkbox || undefined;
	
	if( checkbox === undefined ) {
		
		$j( '.show_if_variation_activity' ).hide();
		
		$j( '.bookacti_variable_is_activity' ).each( function() {
			if( $j( this ).is( ':checked' ) ) {
				$j( this ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
			} 
		});
		
	} else {
		
		if( $j( checkbox ).is( ':checked' ) ) {
			$j( checkbox ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).show();
			
			if( ! $j( checkbox ).parents( '.options' ).find( '.variable_is_virtual' ).is( 'checked' ) ) {
				$j( checkbox ).parents( '.options' ).find( '.variable_is_virtual' ).prop( 'checked', true ).trigger( 'change' );
			}
			
		} else {
			$j( checkbox ).parents( '.woocommerce_variation' ).find( '.show_if_variation_activity' ).hide();
		}
	}
}