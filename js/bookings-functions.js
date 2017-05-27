//Launch dialogs
function bookacti_bind_bookings_dialogs( booking_system ) {
    $j( '#bookacti-bookings-filters-param-gear' ).on( 'click', 'img', function() {
        bookacti_dialog_bookings_filters_param( booking_system ); 
    });
    $j( '#bookacti-bookings-list-param-gear' ).on( 'click', 'img', function() {
        bookacti_dialog_booking_list_param( booking_system ); 
    });
}


// Init booking actions
function bookacti_init_booking_actions() {
	$j( '.bookacti-booking-actions, #bookacti-bookings-list' ).on( 'click', '.bookacti-booking-action', function ( e ) {
		
		e.preventDefault();
		
		var booking_id = $j( this ).data( 'booking-id' );
		if( $j( this ).hasClass( 'bookacti-cancel-booking' ) ){
			bookacti_dialog_cancel_booking( booking_id );
		} else if( $j( this ).hasClass( 'bookacti-reschedule-booking' ) ){
			bookacti_dialog_reschedule_booking( booking_id );
		} else if( $j( this ).hasClass( 'bookacti-refund-booking' ) ){
			bookacti_dialog_refund_booking( booking_id );
		} else if( $j( this ).hasClass( 'bookacti-change-booking-state' ) ){
			bookacti_dialog_change_booking_state( booking_id );
		} else if( $j( this ).attr( 'href' ) && $j( this ).attr( 'href' ) !== '' && ! $j( this ).hasClass( 'prevent-default' ) ) {
			if( $j( this ).hasClass( '_blank' ) ) {
				window.open( $j( this ).attr( 'href' ) );
			} else {
				location.href = $j( this ).attr( 'href' );
			}
		}
		
	});
}


// Init booking filters
function bookacti_init_booking_filters( booking_system ) {
    $j( '.bookacti-bookings-filter-activity, .bookacti-bookings-filter-template' ).off().on( 'click', function( e ){
		bookacti_select_bookings_filter( e, booking_system, $j( this ) );
	});
	
	// Apply filters on load
	bookacti_filter_bookings_by_activities( booking_system );
}


// Select bookings filters
function bookacti_select_bookings_filter( e, booking_system, selected_filter ) {
	
	e = e || false;
	
	// Select the filters (press CTRL to select multiple for non touch devices)
	if( e && ! supportsTouch ) {
		if( ! e.ctrlKey ) {
			selected_filter.parent().find( '.bookacti-bookings-filter' ).attr( 'selected', false );
		}
	}
	
	if( selected_filter.is( '[selected]' ) ) {
		selected_filter.attr( 'selected', false );
	} else {
		selected_filter.attr( 'selected', true );
	}
	
	bookacti_filter_bookings( booking_system, true );
}

// Update bookings according to filters
function bookacti_filter_bookings( booking_system, refresh_calendar_settings ) {
	
	refresh_calendar_settings = refresh_calendar_settings || false;
	
	bookacti_filter_bookings_by_templates( booking_system, refresh_calendar_settings );
	bookacti_filter_bookings_by_activities( booking_system );
}


// Filter bookings by template
function bookacti_filter_bookings_by_templates( booking_system, refresh_calendar_settings ) {
	
	refresh_calendar_settings = refresh_calendar_settings || false;
	
	var booking_method		= booking_system.data( 'booking-method' );
	var booking_system_id	= booking_system.data( 'booking-system-id' );
	
	// Retrieve the selected templates
	var selected_templates	= [];
	$j( '.bookacti-bookings-filter-template[selected]' ).each( function(){
		selected_templates.push( $j( this ).data( 'template-id' ).toString() );
	});	
	
	// If templates has changed, reload events
	var are_same = bookacti_compare_arrays( templates_array[ booking_system_id ], selected_templates );
	if( ! are_same ) {
		
		bookacti_clear_booking_system_displayed_info( booking_system );

		// Update template info in both booking system html element and jquery templates_array
		booking_system.data( 'templates', selected_templates.join(',') );
		templates_array[ booking_system_id ] = selected_templates;
		
		
		// Retrieve selected activities
		var selected_activities = [];
		$j( '.bookacti-bookings-filter-activity[selected]' ).each( function(){
			var activity_id	= $j( this ).data( 'activity-id' );
			selected_activities.push( activity_id );
		});
		
		// Retrieve associated activities and change the default template for current user
		bookacti_start_loading_booking_system( booking_system );
		$j.ajax({
			url: ajaxurl,
			data: { 'action': 'bookactiSelectTemplateFilter', 
					'template_ids': selected_templates,
					'activity_ids': selected_activities,
					'nonce': bookacti_localized.nonce_selected_template_filter
				},
			type: 'POST',
			dataType: 'json',
			success: function( response ){
				if( response.status === 'success' ) {
					
					$j( '#bookacti-activities-filter-content' ).html( response.activities_html );
					bookacti_filter_bookings_by_activities( booking_system );

				} else if( response.status === 'failed' ) {
					var message_error = bookacti_localized.error_switch_template;
					if( response.error === 'not_allowed' ) {
						message_error += '\n' + bookacti_localized.error_not_allowed;
					}
					alert( message_error );
					console.log( response );
				}
			},
			error: function( e ) {
				console.log( 'AJAX ' + bookacti_localized.error_switch_template );
				console.log( e );
			},
			complete: function() { 
				bookacti_stop_loading_booking_system( booking_system );
			}
		});
		
		
		var fetch_past_events = 1;
		var context = 'booking_page';
		
		if( booking_method === 'calendar' || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) {
			
			var calendar = $j( '#bookacti-calendar-' + booking_system_id );
			calendar.fullCalendar( 'removeEvents' );
			
			if( refresh_calendar_settings ) {
				bookacti_update_settings_from_database( booking_system, selected_templates, true );
			}
			
			bookacti_fetch_calendar_events( calendar, fetch_past_events, 'booking_page' );
			
		} else {
			booking_system.trigger( 'bookacti_fetch_events', [ booking_method, fetch_past_events, context ] );
		}
		
	}
}


// Filter bookings by activity
function bookacti_filter_bookings_by_activities( booking_system ) {
	
	var booking_method = booking_system.data( 'booking-method' );
	
	hiddenActivities = [];
	$j( '.bookacti-bookings-filter-activity' ).each( function(){
		var activity_id = $j( this ).data( 'activity-id' );
		hiddenActivities.push( activity_id );
	});
	
	if( ! $j( '.bookacti-bookings-filter-activity[selected]' ).length ) {
		$j( '.bookacti-bookings-filter-activity:first' ).attr( 'selected', true );
	}
	
	$j( '.bookacti-bookings-filter-activity[selected]' ).each( function(){
		var activity_id	= $j( this ).data( 'activity-id' );
		var idx			= $j.inArray( activity_id, hiddenActivities );
		hiddenActivities.splice( idx, 1 );
	});
	
	if( booking_method === 'calendar' || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) {
		booking_system.find( '.bookacti-calendar' ).fullCalendar( 'rerenderEvents' );
	} else {
		booking_system.trigger( 'bookacti_rerender_events', [ booking_method ] );
	}
}


// Fill the booking list of an event
function bookacti_fill_booking_list( booking_system, event_id, event_start, event_end ) {
	
	bookacti_start_loading_booking_system( booking_system );

	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiGetBookingRows',
				'event_id': event_id,
				'event_start': event_start,
				'event_end': event_end,
				'nonce': bookacti_localized.nonce_get_booking_rows
			},
		dataType: 'json',
		success: function( response ){

			if( response.status === 'success' ) {
				
				// Update the booking list
				$j( '#bookacti-bookings-list-container #the-list' ).html( response.rows );
				
				// Update the global actions
				$j( '#bookacti-bookings-list-container #bookacti-bookings-list-global-actions' ).html( response.global_actions_html );
				
				
				/**
				 * Trigger after the booking list has been filled.
				 * 
				 * @since 1.0.0
				 * 
				 */
				$j( '#bookacti-bookings-list' ).trigger( 'bookacti_bookings_list_filled' );

			} else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_retrieve_bookings;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				var no_bookings_entry = '<tr class="no-items" ><td class="colspanchange" colspan="' + $j( '#bookacti-bookings-list table tr th' ).length + '" >' + message_error + '</td></tr>';
				$j( '#bookacti-bookings-list-container #the-list' ).html( no_bookings_entry );

			}
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_retrieve_bookings );
			console.log( e );
		},
		complete: function() { 
			bookacti_stop_loading_booking_system( booking_system );
		}
	});
}	


// Deactivate booking filters & booking list action buttons & dialogs
function bookacti_bookings_enter_loading_state( booking_system ) {
	$j( '.bookacti-bookings-title-gear' ).off( 'click' );
	$j( '.bookacti-bookings-filter' ).off( 'click' );
	
	$j( '.bookacti-bookings-title-gear' ).attr( 'disabled', true );
	$j( '.bookacti-bookings-filter-content' ).attr( 'disabled', true );
	$j( '#bookacti-bookings-list .bookacti-booking-action' ).attr( 'disabled', true );
	$j( '#bookacti-bookings-list-global-actions .bookacti-booking-action' ).attr( 'disabled', true );
}

// Deactivate booking list action buttons
function bookacti_bookings_exit_loading_state( booking_system ) {
	bookacti_init_booking_filters( booking_system );
	bookacti_bind_bookings_dialogs( booking_system );
	
	$j( '.bookacti-bookings-title-gear' ).attr( 'disabled', false );
	$j( '.bookacti-bookings-filter-content' ).attr( 'disabled', false );
	$j( '#bookacti-bookings-list .bookacti-booking-action' ).attr( 'disabled', false );
	$j( '#bookacti-bookings-list-global-actions .bookacti-booking-action' ).attr( 'disabled', false );
}


// Start booking row loading
function bookacti_booking_row_enter_loading_state( row ) {
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	row.find( '.bookacti-booking-state' ).hide();
	row.find( '.bookacti-booking-state' ).after( loading_div );
	row.find( '.bookacti-booking-action' ).attr( 'disabled', true );
}


// Stop booking row loading
function bookacti_booking_row_exit_loading_state( row ) {
	row.find( '.bookacti-loading-alt' ).remove();
	row.find( '.bookacti-booking-state' ).show();
	row.find( '.bookacti-booking-action' ).attr( 'disabled', false );
}


// Check if sent data correspond to displayed data
function bookacti_validate_selected_booking_event( booking_system, quantity ) {
	
	//Get event params
	booking_system	= booking_system || $j( '.bookacti-booking-system' );
	quantity		= quantity || 0;
	var booking_system_id = booking_system.data( 'booking-system-id' );
	var event_id    = booking_system.parent().find( 'input[name="bookacti_event_id"]' ).val();
	var event_start = booking_system.parent().find( 'input[name="bookacti_event_start"]' ).val();
	var event_end   = booking_system.parent().find( 'input[name="bookacti_event_end"]' ).val();
	var total_avail = 0;
	
	//Init boolean test variables
	var valid_form = {
		is_event			: false,
		is_event_in_selected: false,
		is_corrupted		: false,
		is_qty_sup_to_avail	: false,
		is_qty_sup_to_0		: false,
		send				: false
	};
	
	//Make the tests and change the booleans
	if( event_id !== '' && event_start !== '' && event_end !== '' ) { valid_form.is_event = true; }
	if( parseInt( quantity ) > 0 ) { valid_form.is_qty_sup_to_0 = true; }
	
	if( pickedEvents[ booking_system_id ] !== undefined ) {
		$j.each( pickedEvents[ booking_system_id ], function( i, picked_event ) {
			if( picked_event['event_id']		=== event_id 
			&&  picked_event['event_start']	=== event_start 
			&&  picked_event['event_end']		=== event_end ) {
				
				valid_form.is_event_in_selected = true;

				total_avail = picked_event['event_availability'];
				
				if( ( parseInt( quantity ) > parseInt( total_avail ) ) && total_avail !== 0 ) {
					valid_form.is_qty_sup_to_avail = true;
				}
			}
		});
	}
	if( valid_form.is_event 
	&&  ! valid_form.is_event_in_selected )	{ valid_form.is_corrupted = true; }
	if( valid_form.is_event 
	&&  valid_form.is_qty_sup_to_0 
	&&  ! valid_form.is_qty_sup_to_avail 
	&&  ! valid_form.is_corrupted )			{ valid_form.send = true; }
	
	
	// Clear feedbacks
	booking_system.siblings( '.bookacti-notices' ).empty();
	
	
	// Allow third-party to change the results
	booking_system.trigger( 'bookacti_validate_picked_event', [ valid_form ] );
	
	
	//Check the results and build error list
	var error_list = '';
	if( ( ! valid_form.is_event || ! valid_form.is_event_in_selected ) && ! valid_form.is_corrupted ){ 
		error_list += '<li>' + bookacti_localized.error_select_schedule + '</li>' ; 
	}
	if( valid_form.is_qty_sup_to_avail ){ 
		error_list += '<li>' + bookacti_localized.error_less_avail_than_quantity.replace( '%1$s', quantity ).replace( '%2$s', total_avail ) + '</li>'; 
	}
	if( ! valid_form.is_qty_sup_to_0 ){ 
		error_list += '<li>' + bookacti_localized.error_quantity_inf_to_0 + '</li>'; 
	}
	if( valid_form.is_corrupted ){ 
		error_list += '<li>' + bookacti_localized.error_corrupted_schedule + '</li>'; 
	}
	
	// Display error list
	if( error_list !== '' ) {
		booking_system.siblings( '.bookacti-notices' ).append( "<ul class='bookacti-error-list'>" + error_list + "</ul>" ).show();
	}
	
	
	return valid_form.send;
}