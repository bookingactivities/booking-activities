$j( document ).ready( function() {
	
	// ORDER DETAILS
	
		/**
		 * Add data to booking actions
		 * @since 1.0.12
		 */
		$j( '.woocommerce-table' ).on( 'bookacti_booking_action_data', 'tr.order_item', function( e, data, booking_id, booking_type, action ) {
			data.context = 'wc_order_items';
		});




	// SINGLE PRODUCT
		
		// Handle variations
		if( $j( '.woocommerce form.cart.variations_form' ).length ) { 
			/**
			 * Do not init booking system automatically if is supposed to be loaded while switching WC variations
			 * @since 1.7.0
			 * @version 1.7.4
			 */
			$j( '.woocommerce' ).on( 'bookacti_init_booking_sytem', 'form.cart.variations_form .bookacti-booking-system', function( e, load, attributes ) {
				if( load.load === false ) { return; }
				if( typeof $j( this ).closest( '.bookacti-wc-form-fields' ) !== 'undefined' ) { 
					if( $j( this ).closest( '.bookacti-wc-form-fields' ).data( 'default-variation-id' ) ) { load.load = false; }
				}
			});
			
			/**
			 * Switch the booking form according to the selected product variation
			 * @version 1.7.0
			 */
			$j( '.woocommerce form.cart.variations_form' ).each( function() {
				var wc_form = $j( this );
				
				/** START BACKWARD COMPATIBILITY < 1.5 **/
				if( wc_form.find( '.bookacti-booking-system' ).length && ! wc_form.find( '.bookacti-wc-form-fields' ).length ) { 
					var booking_system		= wc_form.find( '.bookacti-booking-system' );
					var booking_system_id	= booking_system.attr( 'id' );

					// Deactivate the booking system if no variation are selected
					wc_form.on( 'reset_data', function() { 
						bookacti_deactivate_booking_system( booking_system );
					});

					// Save the parent data
					bookacti.parent_booking_system[ booking_system_id ] = bookacti.booking_system[ booking_system_id ];

					// Change the booking system if a variation is selected
					wc_form.find( '.single_variation_wrap' ).on( 'show_variation', function( e, variation ) { 
						// Switch booking system
						bookacti_switch_booking_system_according_to_variation( booking_system, variation );
						// Change Add to cart button label
						var new_button_text = variation[ 'bookacti_is_activity' ] ? bookacti_localized.add_booking_to_cart_button_text : bookacti_localized.add_product_to_cart_button_text;
						wc_form.find( '.single_add_to_cart_button' ).text( new_button_text );
					});
					
					return true; // continue
				}
				/** END BACKWARD COMPATIBILITY < 1.5 **/
				
				if( typeof bookacti.form_fields === 'undefined' ) { bookacti.form_fields = []; }
				
				// Empty the form
				wc_form.on( 'reset_data', function( e ) { 
					var form_container = wc_form.find( '.bookacti-wc-form-fields' );
					form_container.data( 'form-id', '' );
					form_container.attr( 'data-form-id', '' );
					form_container.data( 'variation-id', '' );
					form_container.attr( 'data-variation-id', '' );
					form_container.empty();
				});
				
				// Switch form
				wc_form.find( '.single_variation_wrap' ).on( 'show_variation', function( e, variation ) { 
					var form_container = wc_form.find( '.bookacti-wc-form-fields' );
					bookacti_switch_product_variation_form( form_container, variation );
					
					// Change Add to cart button label
					var new_button_text = variation[ 'bookacti_is_activity' ] ? bookacti_localized.add_booking_to_cart_button_text : bookacti_localized.add_product_to_cart_button_text;
					wc_form.find( '.single_add_to_cart_button' ).text( new_button_text );
				});
			});
		}
		
		
		/**
		 * Enable add-to-cart button
		 * @version 1.7.4
		 */
		$j( '.woocommerce form.cart' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', '.bookacti-booking-system', function( e ) {
			$j( this ).parents( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', false );
			$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', false );
		});

		
		/**
		 * Disable add-to-cart button
		 * @version 1.7.4
		 */
		$j( '.woocommerce form.cart' ).on( 'bookacti_error_displayed', '.bookacti-booking-system', function( e ) {
			$j( this ).parents( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', true );
			$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', true );
		});

		
		if( $j( '.woocommerce form.cart .single_add_to_cart_button' ).length ) {
			/**
			 * Add to cart dynamic check
			 * @version 1.7.4
			 */
			$j( '.woocommerce form.cart' ).on( 'submit', function( e ) { 
				var form = $j( this );

				var proceed_to_validation = false;

				if( form.hasClass( 'variations_form' ) ) {
					var variation_id = form.find( '.variation_id' ).val();
					if( variation_id !== '' && typeof variation_id !== 'undefined' ) {
						proceed_to_validation = bookacti.is_variation_activity[ variation_id ];
					}
				} else if( form.find( '.bookacti-booking-system-container' ).length ) {
					proceed_to_validation = true;
				}

				if( proceed_to_validation ) {
					if( form.find( '.bookacti-booking-system' ).length ) {
						// Submit form if all is OK
						var is_valid = bookacti_validate_picked_events( form.find( '.bookacti-booking-system' ), form.find( '.quantity input.qty' ).val() );
						if( is_valid ) {
							// Trigger action before sending form
							form.trigger( 'bookacti_before_submit_booking_form' );
							return true;
						} else {
							// Scroll to error message
							bookacti_scroll_to( form.find( '.bookacti-booking-system-container .bookacti-notices' ), 500, 'middle' );
							return false; // Prevent form submission
						}
					}
				}
			});
		}


		/**
		 * Change activity summary on qty change
		 * @version 1.7.4
		 */
		$j( '.woocommerce form.cart' ).on( 'keyup mouseup change', 'input.qty', function() {
			var booking_system = $j( this ).parents( 'form.cart' ).find( '.bookacti-booking-system' );
			if( booking_system.length ) {
				bookacti_fill_picked_events_list( booking_system );
			}
		});

		
		/**
		 * Set quantity on eventClick
		 * @version 1.7.4
		 */
		$j( '.woocommerce form.cart' ).on( 'bookacti_picked_events_list_data', '.bookacti-booking-system', function( e, event_summary_data, event ) {
			var booking_system = $j( this );
			var qty_field = booking_system.parents( 'form' ).find( '.quantity .qty' );
			if( qty_field.length ) {
				bookacti_set_min_and_max_quantity( booking_system, qty_field, event_summary_data );
			}
		});
		
		
		/**
		 * Init WC actions to perfoms when the user submit booking form
		 * @since 1.7.0
		 */
		$j( 'body' ).on( 'bookacti_submit_booking_form', 'form.bookacti-booking-form', function( e, form_action ){
			if( form_action !== 'redirect_to_product_page' && form_action !== 'add_product_to_cart' ) { return; }
			
			var form			= $j( this );
			var booking_system	= form.find( '.bookacti-form-field-type-calendar .bookacti-booking-system' );
			var group_id		= booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val();
			var event			= {
				'id': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val(),
				'start': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val(),
				'end': booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val()
			};
			
			// A single event is selected
			if( group_id === 'single' && event.id && event.start && event.end ) {
				if( form_action === 'redirect_to_product_page' ) {
					// Redirect to activity URL if a single event is selected
					bookacti_redirect_to_activity_product_page( booking_system, event );
				} else if( form_action === 'add_product_to_cart' ) {
					// Add the product bound to the activity to cart
					bookacti_add_activity_product_to_cart( booking_system, event );
				}
			}
			
			// A group of events is selected
			else if( $j.isNumeric( group_id ) ) {
				if( form_action === 'redirect_to_product_page' ) {
					// Redirect to group category URL if a group of events is selected
					bookacti_redirect_to_group_category_product_page( booking_system, group_id );
				} else if( form_action === 'add_product_to_cart' ) {
					// Add the product bound to the group category to cart
					bookacti_add_group_category_product_to_cart( booking_system, group_id );
				}
			}
		});
	
	
	
	// CART
		// Create a countdown on cart
		if( $j( '.bookacti-countdown' ).length ) {
			setInterval( bookacti_countdown, 1000 );
		}
	
}); // end of document ready


/**
 * Switch form according to variation
 * @version 1.7.4
 * @param {dom_element} form_container
 * @param {object} variation
 */
function bookacti_switch_product_variation_form( form_container, variation ) {
	
	// Remove current form
	form_container.empty();
	
	// Switch form if the variation is actually an activity and if it is active, in stock and visible
	if( ! variation[ 'bookacti_is_activity' ] 
	||  ! variation[ 'is_in_stock' ] 
	||  ! variation[ 'variation_is_active' ] 
	||  ! variation[ 'variation_is_visible' ] ) { return; }
	
	bookacti.is_variation_activity[ variation[ 'variation_id' ] ] = true;
	
	var form_id = parseInt( variation[ 'bookacti_form_id' ] );
	
	if( ! form_id ) { return; }
	
	// Check if the form has already been loaded earlier
	if( typeof bookacti.form_fields[ form_id ] !== 'undefined' ) { 
		bookacti_fill_product_variation_form( form_container, variation, bookacti.form_fields[ form_id ] );
		return;
	}
	
	// Display a loading feedback
	var loading_div = '<div class="bookacti-loading-alt">' 
					+	'<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+	'<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	form_container.append( loading_div );
	
	var data = {	
		'action': 'bookactiGetForm', 
		'form_id': form_id, 
		'instance_id': 'product-variation-' + variation[ 'variation_id' ], 
		'context': 'wc_switch_variation'
	};
	
	// Get selected event from URL parameters if the variation attributes match the URL attributes
	var is_same_var = true;
	$j.each( variation.attributes, function( attr_name, attr_value ) {
		if( attr_value !== bookacti_get_url_parameter( attr_name ) ) {
			is_same_var = false; 
			return false; // Break
		}
	});
	
	if( is_same_var ) {
		data.group_id = bookacti_get_url_parameter( 'bookacti_group_id' );
		if( data.group_id === null ) { data.group_id = bookacti_get_url_parameter( 'event_group_id' ); }
		data.event_id = bookacti_get_url_parameter( 'bookacti_event_id' );
		if( data.event_id === null ) { data.event_id = bookacti_get_url_parameter( 'event_id' ); }
		data.event_start = bookacti_get_url_parameter( 'bookacti_event_start' );
		if( data.event_start === null ) { data.event_start = bookacti_get_url_parameter( 'event_start' ); }
		data.event_end = bookacti_get_url_parameter( 'bookacti_event_end' );
		if( data.event_end === null ) { data.event_end = bookacti_get_url_parameter( 'event_end' ); }
	}
	
	// Load new form fields
	$j.ajax({
        url: bookacti_localized.ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function( response ){
			if( response.status === 'success' ) {
				bookacti.form_fields[ form_id ] = response.form_html;
				bookacti_fill_product_variation_form( form_container, variation, response.form_html );
				
			} else {
				var error_message = bookacti_localized.error_load_form;
				if( response.error === 'not_allowed' ) {
					error_message += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( error_message );
				console.log( response );
			}
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_load_form );
            console.log( e );
        },
        complete: function() { form_container.find( '.bookacti-loading-alt' ).remove(); }
    });	
}


// Replace a old variation form with a new one
function bookacti_fill_product_variation_form( form_container, variation, form_html ) {
	// Remove current form
	form_container.empty();
	
	// Insert new form fields
	form_container.append( form_html );

	// Change fields container metadata
	form_container.data( 'form-id', variation[ 'bookacti_form_id' ] );
	form_container.attr( 'data-form-id', variation[ 'bookacti_form_id' ] );
	form_container.data( 'variation-id', variation[ 'variation_id' ] );
	form_container.attr( 'data-variation-id', variation[ 'variation_id' ] );
	
	// Load the booking system
	var booking_system = form_container.find( '.bookacti-booking-system' );
	bookacti_booking_method_set_up( booking_system );
	
	// Initialize dialog
	bookacti_init_booking_system_dialogs();
	
	// Initialize tooltip
	bookacti_init_tooltip();
	
	// Remove initial loading feedback
	booking_system.find( '.bookacti-loading-alt' ).remove();

	form_container.trigger( 'bookacti_product_variation_form_switched', [ variation ] );
}


/**
 * Switch booking system according to variation 
 * Used for BACKWARD COMPATIBILITY < 1.5
 * @deprecated since 1.5.0
 * @param {dom_element} booking_system
 * @param {object} variation
 */
function bookacti_switch_booking_system_according_to_variation( booking_system, variation ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	booking_system.empty();
	bookacti_clear_booking_system_displayed_info( booking_system );
	
	// Switch booking system if the variation is actually an activity and if it is active, in stock and visible
	if( variation[ 'bookacti_is_activity' ] 
	&&  variation[ 'is_in_stock' ] 
	&&  variation[ 'variation_is_active' ] 
	&&  variation[ 'variation_is_visible' ] ) {
		
		bookacti.is_variation_activity[ variation[ 'variation_id' ] ] = true;
		
		// Make sure the booking system is visible and active
		bookacti_activate_booking_system( booking_system );

		var template_id				= $j.isArray( variation[ 'bookacti_template_id' ] ) ? variation[ 'bookacti_template_id' ] : [ variation[ 'bookacti_template_id' ] ];
		var activity_id				= $j.isArray( variation[ 'bookacti_activity_id' ] ) ? variation[ 'bookacti_activity_id' ] : [ variation[ 'bookacti_activity_id' ] ];
		var group_categories		= $j.isArray( variation[ 'bookacti_group_categories' ] ) ? variation[ 'bookacti_group_categories' ] : [ variation[ 'bookacti_group_categories' ] ];
		var groups_only				= $j.inArray( variation[ 'bookacti_groups_only' ], [ 1, '1', true, 'true', 'yes', 'ok' ] ) >= 0 ? true : false;
		var groups_single_events	= $j.inArray( variation[ 'bookacti_groups_single_events' ], [ 1, '1', true, 'true', 'yes', 'ok' ] ) >= 0 ? true : false;
		var booking_method			= $j.inArray( variation[ 'bookacti_booking_method' ], bookacti_localized.available_booking_methods.push( 'parent', 'site' ) ) >= 0 ? variation[ 'bookacti_booking_method' ] : 'calendar';

		if( template_id[0]	=== 'parent' )			{ template_id		= bookacti.parent_booking_system[ booking_system_id ].calendars; }
		if( activity_id[0]	=== 'parent' )			{ activity_id		= bookacti.parent_booking_system[ booking_system_id ].activities; }
		if( group_categories[0]	=== 'none' )		{ group_categories	= false; }
		else if( group_categories[0] === 'parent' )	{ group_categories	= bookacti.parent_booking_system[ booking_system_id ].group_categories; }
		if( booking_method	=== 'parent' )			{ booking_method	= bookacti.parent_booking_system[ booking_system_id ].method; }
		else if( booking_method	=== 'site' )		{ booking_method	= bookacti_localized.site_booking_method; }

		bookacti.booking_system[ booking_system_id ][ 'method' ]				= booking_method;
		bookacti.booking_system[ booking_system_id ][ 'calendars' ]				= template_id;
		bookacti.booking_system[ booking_system_id ][ 'activities' ]			= activity_id;
		bookacti.booking_system[ booking_system_id ][ 'group_categories' ]		= group_categories;
		bookacti.booking_system[ booking_system_id ][ 'groups_only' ]			= groups_only;
		bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ]	= groups_single_events;
		bookacti.booking_system[ booking_system_id ][ 'template_data' ]			= [];

		bookacti_reload_booking_system( booking_system );

	// Deactivate the booking system if the variation is not an activity
	} else {
		bookacti.is_variation_activity[ variation[ 'variation_id' ] ] = false;
		bookacti_deactivate_booking_system( booking_system );
	}
}


// Create a countdown on cart items
function bookacti_countdown() {
	
	$j( '.bookacti-countdown' ).each( function() {
		
		if( ! $j( this ).hasClass( 'bookacti-expired' ) ) {

			var expiration_date = $j( this ).data( 'expiration-date' );

			if( expiration_date ) {
				expiration_date = moment.utc( expiration_date );
				var current_date = moment.utc();

				var current_time = expiration_date.diff( current_date, 'seconds' );
				
				// calculate (and subtract) whole days
				var days = Math.max( Math.floor(current_time / 86400), 0 );
				current_time -= days * 86400;

				// calculate (and subtract) whole hours
				var hours = Math.max( Math.floor(current_time / 3600) % 24, 0 );
				current_time -= hours * 3600;

				// calculate (and subtract) whole minutes
				var minutes = Math.max( Math.floor(current_time / 60) % 60, 0 );
				current_time -= minutes * 60;

				// what's left is seconds
				var seconds = Math.max( current_time % 60, 0 );

				//Format
				var countdown = '';
				if( days > 0 ) {
					var days_word = bookacti_localized.days;
					if( days === 1 ) { days_word = bookacti_localized.day; }
					countdown += days + ' ' + days_word  + ' ';
				}
				if( hours > 0 || days > 0 ) {
					countdown += hours + ':' ;
				}

				countdown += bookacti_pad( minutes, 2 ) + ':' + bookacti_pad( seconds, 2 );

				if( days === 0 && hours === 0 && minutes === 0 && seconds === 0 ) {
					
					countdown = bookacti_localized.expired_min;
					$j( this ).addClass( 'bookacti-expired' );
					
					var countdown = this;
					setTimeout( function(){ bookacti_refresh_cart_after_expiration( countdown ); }, 2000 );
				}

				$j( this ).html( countdown );
			}
			
		} else {

			$j( this ).html( bookacti_localized.expired_min );
		}
	});
}


// Refresh cart after expiration
function bookacti_refresh_cart_after_expiration( countdown ) {
	var is_checkout	= $j( countdown ).parents( '.checkout' ).length;
	var woodiv		= $j( countdown ).parents( '.woocommerce' );
	var url			= woodiv.find( 'form' ).attr( 'action' );
	
	if( $j( countdown ).parents( '.bookacti-cart-expiration-container' ).length ) {
		$j( countdown ).parents( '.bookacti-cart-expiration-container' ).html( bookacti_localized.error_cart_expired );
	}
	
	if( ! is_checkout ) {

		if( $j( countdown ).hasClass( 'bookacti-cart-expiration' ) ) {
			woodiv.find( '.bookacti-cart-item-expires-with-cart' ).parents( '.cart_item' ).empty();
		} else {
			$j( countdown ).parents( '.cart_item' ).empty();
		}

		woodiv.find( '.cart-subtotal .amount'	).empty();
		woodiv.find( '.shipping .amount'		).empty();
		woodiv.find( '.order-total .amount'		).empty();
		woodiv.find( '.includes_tax .amount'	).empty();
	}

	$j.ajax({
		url: url,
		type: 'POST',
		data: { },
		dataType: 'html',
		success: function( response ){
			
			if( ! is_checkout ) {

				//If cart doesn't contains items anymore, update the whole woocommerce div 
				if( $j( response ).find( '.woocommerce form' ).length <= 0 ) {

					woodiv.html( $j( response ).find( '.woocommerce' ).html() );

				} else {

					//Copy error messages
					if( $j( response ).find( '.woocommerce-error' ).length ) {
						if( woodiv.find( '.woocommerce-error' ).length ){
							$j( response ).find( '.woocommerce-error li' ).clone().appendTo( woodiv.find( '.woocommerce-error' ) );
						} else {
							$j( response ).find( '.woocommerce-error' ).clone().prependTo( woodiv );
						}
					}

					//Replace totals amounts
					woodiv.find( '.cart-subtotal .amount'	).html( $j( response ).find( '.cart-subtotal .amount'	).html() );
					woodiv.find( '.shipping .amount'		).html( $j( response ).find( '.shipping .amount'		).html() );
					woodiv.find( '.order-total .amount'		).html( $j( response ).find( '.order-total .amount'		).html() );
					woodiv.find( '.includes_tax .amount'	).html( $j( response ).find( '.includes_tax .amount'	).html() );
				}

			} else {
				// Tell woocommerce to update checkout
				$j( 'body' ).trigger( 'update_checkout' );
			}
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_remove_expired_cart_item );
			console.log( e );
		},
		complete: function() { 

		}
	});
}


// Check if a booking system is active
function bookacti_booking_system_is_active( booking_system ) {
	return ! booking_system.siblings( '.bookacti-booking-system-inputs input' ).is( ':disabled' );
}


// Deactivate booking system
function bookacti_deactivate_booking_system( booking_system ) {
	booking_system.parent().hide();
	booking_system.siblings( '.bookacti-booking-system-inputs input' ).prop( 'disabled', true );
}


// Activate booking system
function bookacti_activate_booking_system( booking_system ) {
	
	booking_system.parent().show();
	
	if( bookacti_booking_system_is_active( booking_system ) ) { return; }
	
	booking_system.siblings( '.bookacti-booking-system-inputs input' ).prop( 'disabled', false );
	
	var booking_method = bookacti.booking_system[ booking_system.attr( 'id' ) ][ 'method' ];
	bookacti_booking_method_rerender_events( booking_system, booking_method );
}





