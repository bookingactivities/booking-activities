$j( document ).ready( function() {
	/**
	 * Init WC actions to perfoms when the user submit booking form
	 * @since 1.7.0
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_submit_booking_form', '.bookacti-booking-form', function() {
		var booking_system = $j( this ).find( '.bookacti-booking-system' );
		bookacti_wc_perform_form_action( booking_system );
	});
	
	
	/**
	 * Init WC actions to perfoms when the user picks an event
	 * @since 1.7.0
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_events_picked_after', '.bookacti-booking-system', function( e, group_id, event ){
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		// Perform form action, but not on form editor
		if( ! booking_system.closest( '#bookacti-form-editor-page-form' ).length ) {
			if( group_id === 'single' && attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
				var group_ids = bookacti_get_event_group_ids( booking_system, event );
				var open_dialog = false;
				if( $j.isArray( group_ids )
					&&	(	( group_ids.length > 1 )
						||  ( group_ids.length === 1 && attributes[ 'groups_single_events' ] ) ) ) {
					open_dialog = true;
				}
				if( ! open_dialog ) {
					bookacti_wc_perform_form_action( booking_system );
				}
			}
		}
		
		booking_system.trigger( 'bookacti_events_picked_after_wc', [ group_id, event ] );
	});
	
	
	/**
	 * Init WC actions to perfoms when the user picks a group of events
	 * @since 1.7.0
	 * @version 1.7.19
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_chosen_after', '.bookacti-booking-system', function( e, group_id, event ) {
		// Retrieve the info required to show the desired events
		var booking_system		= $j( this );
		var booking_system_id	= booking_system.attr( 'id' );
		var attributes			= bookacti.booking_system[ booking_system_id ];
		
		// Perform form action, but not on form editor
		if( ! booking_system.closest( '#bookacti-form-editor-page-form' ).length ) {
			if( attributes[ 'when_perform_form_action' ] === 'on_event_click' ) {
				bookacti_wc_perform_form_action( booking_system );
			}
		}
		
		booking_system.trigger( 'bookacti_group_of_events_chosen_after_wc', [ group_id, event ] );
	});
});


/**
 * Perform WC form action
 * @since 1.7.19
 * @version 1.8.0
 * @param {string} form_action
 */
function bookacti_wc_perform_form_action( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	if( typeof bookacti.booking_system[ booking_system_id ] === 'undefined' ) { return; }
	
	var attributes = bookacti.booking_system[ booking_system_id ];
	if( typeof attributes[ 'form_action' ] !== 'undefined' ) { return; }
	
	var form_action = attributes[ 'form_action' ];
	if( $j.inArray( form_action, [ 'default', 'redirect_to_product_page', 'add_product_to_cart' ] ) === -1 ) { return; }
	
	var group_id= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val();
	var event = {
		'id': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val(),
		'start': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val(),
		'end': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val()
	};
	
	// Send the add to cart form
	if( form_action === 'default' && booking_system.closest( 'form.cart' ).length ) {
		if( booking_system.closest( 'form.cart' ).find( '.single_add_to_cart_button' ).length ) {
			booking_system.closest( 'form.cart' ).find( '.single_add_to_cart_button' ).trigger( 'click' );
		}
		return;
	}
	
	// A single event is selected
	if( group_id === 'single' && event.id && event.start && event.end ) {
		// Redirect to activity URL if a single event is selected
		 if( form_action === 'redirect_to_product_page' ) {
			bookacti_redirect_to_activity_product_page( booking_system, event );
		} 
		// Add the product bound to the activity to cart
		else if( form_action === 'add_product_to_cart' ) {
			bookacti_add_activity_product_to_cart( booking_system, event );
		}
	}

	// A group of events is selected
	else if( $j.isNumeric( group_id ) ) {
		// Redirect to group category URL if a group of events is selected
		if( form_action === 'redirect_to_product_page' ) {
			bookacti_redirect_to_group_category_product_page( booking_system, group_id );
		} 
		// Add the product bound to the group category to cart
		else if( form_action === 'add_product_to_cart' ) {
			bookacti_add_group_category_product_to_cart( booking_system, group_id );
		}
	}
}




// REDIRECT

/**
 * Redirect to activity product page
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {object} event
 */
function bookacti_redirect_to_activity_product_page( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event.id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event.id ][ 'activity_id' ];
	if( typeof attributes[ 'product_by_activity' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_activity' ][ activity_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_activity' ][ activity_id ];
	if( typeof attributes[ 'products_page_url' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'products_page_url' ][ product_id ] === 'undefined' ) { return; }

	var redirect_url = attributes[ 'products_page_url' ][ product_id ];
	
	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}


/**
 * Redirect to group category product page
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {int} group_id
 */
function bookacti_redirect_to_group_category_product_page( booking_system, group_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'product_by_group_category' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_group_category' ][ category_id ];
	if( typeof attributes[ 'products_page_url' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'products_page_url' ][ product_id ] === 'undefined' ) { return; }
	
	var redirect_url = attributes[ 'products_page_url' ][ product_id ];
	
	bookacti_redirect_booking_system_to_url( booking_system, redirect_url );
}




// ADD TO CART

/**
 * Add the product bound to the activity to cart from a booking form
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {object} event
 */
function bookacti_add_activity_product_to_cart( booking_system, event ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'events_data' ][ event.id ] === 'undefined' ) { return; }
	
	var activity_id = attributes[ 'events_data' ][ event.id ][ 'activity_id' ];
	if( typeof attributes[ 'product_by_activity' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_activity' ][ activity_id ] === 'undefined' ) { return; }

	var product_id = attributes[ 'product_by_activity' ][ activity_id ];
	
	bookacti_add_product_to_cart_via_booking_system( booking_system, product_id );
}


/**
 * Add the product bound to the group category to cart from a booking form
 * @since 1.7.0
 * @param {dom_element} booking_system
 * @param {int} group_id
 */
function bookacti_add_group_category_product_to_cart( booking_system, group_id ) {
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	
	if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return; }
	
	var category_id = attributes[ 'groups_data' ][ group_id ][ 'category_id' ];
	if( typeof attributes[ 'product_by_group_category' ] === 'undefined' ) { return; }
	if( typeof attributes[ 'product_by_group_category' ][ category_id ] === 'undefined' ) { return; }
	
	var product_id = attributes[ 'product_by_group_category' ][ category_id ];
	
	bookacti_add_product_to_cart_via_booking_system( booking_system, product_id );
}


/**
 * Add a product to cart from a booking form
 * @since 1.7.0
 * @version 1.8.0
 * @param {dom_element} booking_system
 * @param {int} product_id
 */
function bookacti_add_product_to_cart_via_booking_system( booking_system, product_id ) {
	// Use the error div of the booking system by default, or if possible, the error div of the form
	var error_div = booking_system.siblings( '.bookacti-notices' );
	if( booking_system.closest( 'form' ).length ) {
		if( booking_system.closest( 'form' ).find( '> .bookacti-notices' ).length ) {
			error_div = booking_system.closest( 'form' ).find( '> .bookacti-notices' );
		}
	}
	
	// Remove the previous feedbacks
	error_div.empty();
	
	// Add form parameters to the URL
	var data = [];
	if( ! booking_system.closest( 'form' ).length ) {
		booking_system.closest( '.bookacti-form-fields' ).wrap( '<form class="bookacti-temporary-form"></form>' );
		data = new FormData( booking_system.closest( 'form' ).get(0) );
		booking_system.closest( '.bookacti-form-fields' ).unwrap( 'form.bookacti-temporary-form' );
	} else {
		data = new FormData( booking_system.closest( 'form' ).get(0) );
	}
	
	// Trigger action before sending form
	booking_system.trigger( 'bookacti_before_add_product_to_cart', [ data ] );
	
	// Set the form action
	if( data instanceof FormData ) {
		data.set( 'action', 'bookactiAddBoundProductToCart' );
	} else {
		return;
	}
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		cache: false,
        contentType: false,
        processData: false,
		success: function( response ) {
			var redirect_url = typeof response.redirect_url !== 'undefined' ? response.redirect_url : '';
			if( response.status === 'success' ) {
				// Reload booking numbers if page is not reloaded
				if( redirect_url.indexOf( '://' ) < 0 ) {
					bookacti_refresh_booking_numbers( booking_system );
				}
				
				booking_system.trigger( 'bookacti_product_added_to_cart', [ response, data, product_id ] );
				
				// Redirect to the desired page or to cart
				if( redirect_url ) {
					bookacti_start_loading_booking_system( booking_system );
					window.location.replace( redirect_url );
					bookacti_stop_loading_booking_system( booking_system );
				}
				
			} else {
				console.log( response );
			}
			
			// Display feedback message
			if( response.messages ) {
				var feedback_class = response.status === 'success' ? 'bookacti-success-list woocommerce' : 'bookacti-error-list';
				var message = '<ul class="' + feedback_class + '"><li>' + response.messages + '</li></ul>';
				// Fill error message
				error_div.empty().append( message ).show();
				// Scroll to error message
				if( ! redirect_url ) {
					bookacti_scroll_to( error_div, 500, 'middle' );
				}
			}
        },
        error: function( e ){
			// Fill error message
			var message = '<ul class="bookacti-error-list"><li>AJAX error occured while trying to add the product to cart</li></ul>';
			error_div.empty().append( message ).show();
			// Scroll to error message
			bookacti_scroll_to( error_div, 500, 'middle' );
            console.log( 'AJAX error occured while trying to add the product to cart' );
            console.log( e );
        },
        complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
    });
}