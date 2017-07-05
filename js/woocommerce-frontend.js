$j( document ).ready( function() {
	
	// BOOKING LIST

		// Update booking row after frontend rechedule
		$j( 'body' ).on( 'bookacti_booking_rescheduled', function( e, booking_id, event_start, event_end, response ){
			var row	= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
			// Update start and end dates, and updates available actions
			if( $j( '.wc-item-meta-bookacti_event_start.wc-item-meta-value' ).length ) {
				row.find( '.wc-item-meta-bookacti_event_start.wc-item-meta-value' ).html( response.event_start_formatted );
				row.find( '.wc-item-meta-bookacti_event_end.wc-item-meta-value' ).html( response.event_end_formatted );
				row.find( '.bookacti-booking-actions' ).html( response.actions_html );
			}
			// WOOCOMMERCE 3.0.0 backward compatibility
			if( $j( 'dd.variation-bookacti_event_start p' ).length ) {
				row.find( 'dd.variation-bookacti_event_start p' ).html( response.event_start_formatted );
				row.find( 'dd.variation-bookacti_event_end p' ).html( response.event_end_formatted );
				row.find( '.bookacti-booking-actions' ).html( response.actions_html );
			}
		});
	
	
	
	
	// SINGLE PRODUCT
	
		// Handle variations
		if( $j( 'body.woocommerce form.cart .bookacti-booking-system' ).length ) { 
			$j( '.bookacti-booking-system' ).each( function() {	
				var booking_system		= $j( this );
				var booking_system_id	= booking_system.attr( 'id' );

				var is_variable = booking_system.parents( '.variations_form' ).length;
				if( booking_system.hasClass( 'bookacti-woocommerce-product-booking-system' ) && is_variable ) {
					// Deactivate the booking system if no variation are selected
					booking_system.parents( '.variations_form' ).on( 'reset_data', function() { 
						bookacti_deactivate_booking_system( booking_system );
					});
					
					// Save the parent data
					parent_calendar_data[ booking_system_id ] = calendars_data[ booking_system_id ];

					// Change the booking system if a variation is selected
					booking_system.parents( '.variations_form' ).find( '.single_variation_wrap' ).on( 'show_variation', function( event, variation ) { 
						bookacti_switch_booking_system_according_to_variation( booking_system, variation );
					});
				}
			});
		}


		// Enable add-to-cart button
		$j( 'body.woocommerce form.cart' ).on( 'bookacti_view_refreshed bookacti_displayed_info_cleared', '.bookacti-booking-system', function( e ) {
			$j( this ).parents( 'form' ).find( 'input[name="quantity"]' ).attr( 'disabled', false );
			$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', false );
		});


		// Disable add-to-cart button
		$j( 'body.woocommerce form.cart' ).on( 'bookacti_error_displayed', '.bookacti-booking-system', function( e ) {
			$j( this ).parents( 'form' ).find( 'input[name="quantity"], input[name^="bookacti_quantity"]' ).attr( 'disabled', true );
			$j( this ).parents( 'form' ).find( 'button[type="submit"]' ).attr( 'disabled', true );
		});


		// Add to cart dynamic check
		if( $j( 'body.woocommerce form.cart .single_add_to_cart_button' ).length ) {		
			$j( 'body.woocommerce form.cart' ).on( 'submit', function( e ) { 
				var form = $j( this );

				var proceed_to_validation = false;

				if( form.hasClass( 'variations_form' ) ) {
					var variation_id = form.find( '.variation_id' ).val();
					if( variation_id !== '' && typeof variation_id !== 'undefined' ) {
						proceed_to_validation = is_activity[ variation_id ];
					}
				} else if( form.find( '.bookacti-booking-system-container' ).length ) {
					proceed_to_validation = true;
				}

				if( proceed_to_validation ) {
					if( form.find( '.bookacti-booking-system-container' ).length ) {
						// Submit form if all is OK
						var is_valid = bookacti_validate_picked_events( form.find( '.bookacti-booking-system' ), form.find( '.quantity input.qty' ).val() );
						if( is_valid ) {
							// Trigger action before sending form
							form.find( '.bookacti-booking-system-container' ).trigger( 'bookacti_submit_booking_form' );
							return true;
						} else {
							return false; // Prevent submission
						}
					}
				}
			});
		}


		// Change activity summary on qty change
		$j( 'body.woocommerce form.cart' ).on( 'keyup mouseup', 'input.qty', function() {
			var booking_system = $j( this ).parents( 'form.cart' ).find( '.bookacti-booking-system' );
			if( booking_system.length ) {
				bookacti_fill_picked_events_list( booking_system );
			}
		});


		// Set quantity on eventClick
		$j( 'body.woocommerce form.cart' ).on( 'bookacti_picked_events_list_data', '.bookacti-booking-system', function( e, event_summary_data ) {
			if( $j( this ).parents( 'form' ).find( '.quantity .qty' ).length ) {

				var booking_system_id	= $j( this ).attr( 'id' );
				var quantity			= parseInt( $j( this ).parents( 'form' ).find( '.quantity .qty' ).val() );
				var available_places	= 0; 

				//Limit the max quantity
				if( pickedEvents[ booking_system_id ].length > 1 ) {
					available_places = bookacti_get_group_availability( pickedEvents[ booking_system_id ] );
				} else {
					available_places = bookacti_get_event_availability( pickedEvents[ booking_system_id ][ 0 ] );
				}

				$j( this ).parents( 'form' ).find( '.quantity .qty' ).attr( 'max', available_places );
				if( quantity > available_places ) {
					$j( this ).parents( 'form' ).find( '.quantity .qty' ).val( available_places );
					quantity = available_places;
				}

				event_summary_data.quantity = quantity;
			}
		});
	
	
	
	
	// CART
	
		// Create a countdown on cart
		if( $j( '.bookacti-countdown' ).length ) {
			setInterval( bookacti_countdown, 1000 );
		}
	
}); // end of document ready


// Switch booking system according to variation
function bookacti_switch_booking_system_according_to_variation( booking_system, variation ) {
	
	var booking_system_id		= booking_system.attr( 'id' );
	
	booking_system.empty();
	bookacti_clear_booking_system_displayed_info( booking_system );
	
	//Switch booking system if the variation is actually an activity and if it is active, in stock and visible
	if( variation[ 'bookacti_is_activity' ] 
	&&  variation[ 'is_in_stock' ] 
	&&  variation[ 'variation_is_active' ] 
	&&  variation[ 'variation_is_visible' ] ) {
		
		is_activity[ variation[ 'variation_id' ] ] = true;
		bookacti_activate_booking_system( booking_system, variation[ 'variation_id' ] );

		var template_id				= $j.isArray( variation[ 'bookacti_template_id' ] ) ? variation[ 'bookacti_template_id' ] : [ variation[ 'bookacti_template_id' ] ];
		var activity_id				= $j.isArray( variation[ 'bookacti_activity_id' ] ) ? variation[ 'bookacti_activity_id' ] : [ variation[ 'bookacti_activity_id' ] ];
		var group_categories		= $j.isArray( variation[ 'bookacti_group_categories' ] ) ? variation[ 'bookacti_group_categories' ] : [ variation[ 'bookacti_group_categories' ] ];
		var groups_only				= $j.inArray( variation[ 'bookacti_groups_only' ], [ 1, '1', true, 'true', 'yes', 'ok' ] ) >= 0 ? true : false;
		var groups_single_events	= $j.inArray( variation[ 'bookacti_groups_single_events' ], [ 1, '1', true, 'true', 'yes', 'ok' ] ) >= 0 ? true : false;
		var booking_method			= $j.inArray( variation[ 'bookacti_booking_method' ], bookacti_localized.available_booking_methods.push( 'parent', 'site' ) ) ? variation[ 'bookacti_booking_method' ] : 'calendar';
		
		if( template_id[0]	=== 'parent' )			{ template_id		= parent_calendar_data[ booking_system_id ].calendars; }
		if( activity_id[0]	=== 'parent' )			{ activity_id		= parent_calendar_data[ booking_system_id ].activities; }
		if( group_categories[0]	=== 'none' )		{ group_categories	= false; }
		else if( group_categories[0] === 'parent' )	{ group_categories	= parent_calendar_data[ booking_system_id ].group_categories; }
		if( booking_method	=== 'parent' )			{ booking_method	= parent_calendar_data[ booking_system_id ].method; }
		else if( booking_method	=== 'site' )		{ booking_method	= bookacti_localized.site_booking_method; }
		
		calendars_data[ booking_system_id ][ 'method' ]					= booking_method;
		calendars_data[ booking_system_id ][ 'calendars' ]				= template_id;
		calendars_data[ booking_system_id ][ 'activities' ]				= activity_id;
		calendars_data[ booking_system_id ][ 'group_categories' ]		= group_categories;
		calendars_data[ booking_system_id ][ 'groups_only' ]			= groups_only;
		calendars_data[ booking_system_id ][ 'groups_single_events' ]	= groups_single_events;
		
		bookacti_reload_booking_system( booking_system );
		
	// Deactivate the booking system if the variation is not an activity
	} else {
		is_activity[ variation[ 'variation_id' ] ] = false;
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


// Deactivate booking system
function bookacti_deactivate_booking_system( booking_system ) {
	booking_system.parent().hide();
	booking_system.siblings( '.bookacti-booking-system-inputs input' ).prop( 'disabled', true );
}


// Activate booking system
function bookacti_activate_booking_system( booking_system ) {
	
	var booking_method = calendars_data[ booking_system.attr( 'id' ) ][ 'method' ];
	
	booking_system.parent().show();
	booking_system.siblings( '.bookacti-booking-system-inputs input' ).prop( 'disabled', false );
	
	bookacti_booking_method_rerender_events( booking_system, booking_method );
}





