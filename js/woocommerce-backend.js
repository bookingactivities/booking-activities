$j( document ).ready( function() {
	
	//Show or hide the activity tab on product page in the backend
	bookacti_show_hide_activity_tab();
	$j( '.type_box select, .type_box input' ).on( 'change', function(){ 
		bookacti_show_hide_activity_tab();
	});
	
	//Show or hide activities depending on the selected template
	bookacti_show_hide_activities_options();
	$j( '#_bookacti_template, bookacti_variable_template' ).on( 'change', function(){ 
		bookacti_show_hide_activities_options();
	});
	
	//Show or hide activity fields on variation page in the backend
	$j( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		bookacti_show_hide_activity_variation_fields();
		$j( '#woocommerce-product-data .bookacti_variable_template' ).each( function() {
			bookacti_show_hide_variation_activities_options( this );
		});
	});
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_is_activity', function(){ 
		bookacti_show_hide_activity_variation_fields( this );
	});
	$j( '#woocommerce-product-data' ).on( 'change', '.bookacti_variable_template', function(){ 
		bookacti_show_hide_variation_activities_options( this );
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

//Show or hide activities depending on the selected template
function bookacti_show_hide_activities_options() {
	//Init variables
	var template_id = $j( '#_bookacti_template' ).val();
	var change_selected = false;
	
	//Show all activities
	$j( '#_bookacti_activity option' ).removeClass( 'bookacti-hide-fields' );
	
	//Hide not allowed activities
	$j( '#_bookacti_activity option' ).each( function() {
		var allowed_templates = $j( this ).data( 'bookacti-show-if-templates' ).toString();
		if( allowed_templates.indexOf( ',' ) >= 0 ) {
			allowed_templates = allowed_templates.split( ',' );
		} else {
			allowed_templates = [ allowed_templates ];
		}
		if( $j.inArray( template_id.toString(), allowed_templates ) === -1 ) {
			if( $j( this ).is( ':selected' ) ) { change_selected = true; }
			$j( this ).addClass( 'bookacti-hide-fields' );
		}
	});
	
	//Change selected activity automatically if it gets hidden
	if( change_selected === true ) {
		$j( '#_bookacti_activity option' ).removeAttr( 'selected' );
		$j( '#_bookacti_activity option:not(.bookacti-hide-fields):first' ).attr( 'selected', 'selected' );
	}
}

//Show or hide activities depending on the selected template on variation
function bookacti_show_hide_variation_activities_options( template ) {
	
	//Init variables
	var template_id		= $j( template ).val();
	if( template_id === 'parent' ) { template_id = $j( '#_bookacti_template' ).val() || $j( template ).data( 'parent' ); }
	var loop			= $j( template ).data( 'loop' );
	var activity_field	= $j( '#bookacti_variable_activity_' + loop );
	var change_selected = false;

	//Show all activities
	activity_field.find( 'option' ).removeClass( 'bookacti-hide-fields' );

	//Hide not allowed activities
	activity_field.find( 'option' ).each( function() {
		if( $j( this ).data( 'bookacti-show-if-templates' ) != null ) {
			var allowed_templates	= $j( this ).data( 'bookacti-show-if-templates' ).toString();
		} else if( $j( this ).val() === 'parent' ) {
			var parent_activity_id	= $j( '#_bookacti_activity' ).val() || activity_field.data( 'parent' );
			var allowed_templates	= activity_field.find( 'option[value="' + parent_activity_id + '"]' ).data( 'bookacti-show-if-templates' ).toString();
		}

		if( allowed_templates.indexOf( ',' ) >= 0 ) {
			allowed_templates = allowed_templates.split( ',' );
		} else {
			allowed_templates = [ allowed_templates ];
		}
		
		if( $j.inArray( template_id.toString(), allowed_templates ) === -1 ) {
			if( $j( this ).is( ':selected' ) ) { change_selected = true; }
			$j( this ).addClass( 'bookacti-hide-fields' );
		}
	});

	//Change selected activity automatically if it gets hidden
	if( change_selected === true ) {
		activity_field.find( 'option' ).removeAttr( 'selected' );
		activity_field.find( 'option:not(.bookacti-hide-fields):first' ).attr( 'selected', 'selected' );
	}
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