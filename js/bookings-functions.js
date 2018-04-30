// BOOKINGS PAGE

// Init booking filter action
function bookacti_init_booking_filters_actions() {
	
	// Display or hide activities filter according to selected templates
	bookacti_update_template_related_filters();
	$j( '#bookacti-booking-filter-templates, #bookacti-booking-filter-status, #bookacti-booking-filter-customer' ).on( 'change bookacti_customers_selectbox_changed', function() {
		// Show / Hide activities filter
		bookacti_update_template_related_filters();
		
		// Reload events according to filters
		if( $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
			var booking_system = $j( '#bookacti-booking-system-bookings-page' );
			bookacti_reload_booking_system_according_to_filters( booking_system );
		}
	});
	
	
	// Display / Hide the calendar
	$j( '#bookacti-pick-event-filter' ).on( 'click', function() {
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		
		// Reload events according to filters if they have changed
		if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
			var booking_system_id	= booking_system.attr( 'id' );
			var selected_templates	= $j( '#bookacti-booking-filter-templates' ).val() ? $j( '#bookacti-booking-filter-templates' ).val() : [];
			var selected_status		= $j( '#bookacti-booking-filter-status' ).val() ? $j( '#bookacti-booking-filter-status' ).val() : [];
			var selected_user		= $j( '#bookacti-booking-filter-customer' ).val() ? $j( '#bookacti-booking-filter-customer' ).val() : 0;
		
			if( ! bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'calendars' ], selected_templates )
			||  ! bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'status' ], selected_status )
			||  bookacti.booking_system[ booking_system_id ][ 'user_id' ] !== selected_user ) {
				bookacti_reload_booking_system_according_to_filters( booking_system );
			}
			
			$j( '#bookacti-pick-event-filter' ).text( bookacti_localized.hide_calendar ).attr( 'title', bookacti_localized.hide_calendar );
		} else {
			$j( '#bookacti-pick-event-filter' ).text( bookacti_localized.pick_an_event ).attr( 'title', bookacti_localized.pick_an_event );
		}
		
		// Show / Hide calendar
		$j( '#bookacti-booking-system-filter-container' ).toggle( 200 );
	});
	
	// Unpick all events
	$j( '#bookacti-unpick-events-filter' ).on( 'click', function() {
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_unpick_all_events( booking_system );
		bookacti_clear_booking_system_displayed_info( booking_system );
		$j( '#bookacti-unpick-events-filter' ).hide( 200 );
	});
	
	// Display the "unpick events" button
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	booking_system.on( 'bookacti_event_click', function( e, event, group_id ) { 
		$j( '#bookacti-unpick-events-filter' ).show( 200 );
	});
	
	// Display / Hide activities on the calendar
	$j( '#bookacti-booking-filter-activities' ).on( 'change', function() {
		var booking_system	= $j( '#bookacti-booking-system-bookings-page' );
		var calendar		= booking_system.find( '.bookacti-calendar' );
		calendar.fullCalendar( 'rerenderEvents' );
	});
	
	
	// Retrict calendars date according to date filter
	$j( '#bookacti-booking-filter-dates-from, #bookacti-booking-filter-dates-to' ).on( 'change', function() {
		var booking_system	= $j( '#bookacti-booking-system-bookings-page' );
		var calendar		= booking_system.find( '.bookacti-calendar' );
		bookacti_refresh_calendar_according_to_date_filter( calendar );
	});
}


// TO DO: AJAXify the booking list
// Filter the booking list with current filters values
function bookacti_filter_booking_list() {
	// Update the URL without refreshing the page
	var serialized = $j( '#bookacti-booking-list-filters-form' ).serialize();
	var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=bookacti_bookings&' + serialized;
	window.history.pushState({path:newurl},'',newurl);
}


// Change template-related filters
function bookacti_update_template_related_filters() {
	
	// Update activities filter
	var associations = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'templates_per_activities' ];
	var selected_templates = $j( '#bookacti-booking-filter-templates' ).val();
	
	// If no template are selected, show all activities
	if( ! selected_templates ) {
		$j( '#bookacti-booking-filter-activities option' ).show();
		return false;
	}
	
	$j( '#bookacti-booking-filter-activities option' ).each( function( i, option ) {
		var activity_id = parseInt( $j( option ).attr( 'value' ) );
		var hide_activity = true;
		$j.each( selected_templates, function( j, selected_template ){
			if( $j.inArray( selected_template, associations[ activity_id ][ 'template_ids' ] ) >= 0 ) {
				hide_activity = false;
				return false; // Break
			}
		});
		if( hide_activity ) {
			if( $j( option ).is( ':selected' ) ) { $j( option ).prop( 'selected', false ); }
			$j( option ).hide();
		} else {
			$j( option ).show();
		}
	});
}


// Refresh calendar accoding to dates
function bookacti_refresh_calendar_according_to_date_filter( calendar ) {
	if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) { return false; }

	calendar = $j( '#bookacti-booking-system-bookings-page' ).find( '.bookacti-calendar' );
	var from	= moment( $j( '#bookacti-booking-filter-dates-from' ).val() );
	var to		= moment( $j( '#bookacti-booking-filter-dates-to' ).val() );
	
	var interval_filter = {
		"start": from.isValid() ? from : moment( bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'template_data' ][ 'start' ] ),
		"end": to.isValid() ? to.add( 1, 'days' ) : moment( bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'template_data' ][ 'end' ] ).add( 1, 'days' )
	};
	
	calendar.fullCalendar( 'option', 'validRange', interval_filter );
}


// Reload bookings booking system according to filters
function bookacti_reload_booking_system_according_to_filters( booking_system ) {
	var booking_system_id	= booking_system.attr( 'id' );
	
	var selected_templates	= $j( '#bookacti-booking-filter-templates' ).val();
	var selected_status		= $j( '#bookacti-booking-filter-status' ).val();
	var selected_user		= $j( '#bookacti-booking-filter-customer' ).val();
	
	bookacti.booking_system[ booking_system_id ][ 'calendars' ] = selected_templates ? selected_templates : [];
	bookacti.booking_system[ booking_system_id ][ 'status' ]	= selected_status ? selected_status : [];
	bookacti.booking_system[ booking_system_id ][ 'user_id' ]	= selected_user ? selected_user : 0;
	bookacti.booking_system[ booking_system_id ][ 'template_data' ] = [];
	
	bookacti_reload_booking_system( booking_system );
}



// BOOKING LIST

// Init booking actions
function bookacti_init_booking_actions() {
	$j( '.bookacti-user-bookings-list, .bookacti-order-item-activity, #bookacti-bookings-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
		e.preventDefault();
		
		// Single Bookings
		if( $j( this ).hasClass( 'bookacti-booking-action' ) ) {

			var booking_id = $j( this ).data( 'booking-id' );
			if( $j( this ).hasClass( 'bookacti-cancel-booking' ) ){
				bookacti_dialog_cancel_booking( booking_id, 'single' );
			} else if( $j( this ).hasClass( 'bookacti-reschedule-booking' ) ){
				bookacti_dialog_reschedule_booking( booking_id );
			} else if( $j( this ).hasClass( 'bookacti-refund-booking' ) ){
				bookacti_dialog_refund_booking( booking_id, 'single' );
			} else if( $j( this ).hasClass( 'bookacti-change-booking-state' ) ){
				bookacti_dialog_change_booking_state( booking_id, 'single' );
			} else if( $j( this ).hasClass( 'bookacti-delete-booking' ) ){
				bookacti_dialog_delete_booking( booking_id, 'single' );
			}
		
		// Booking Groups
		} else {
			
			var booking_group_id = $j( this ).data( 'booking-group-id' );
			if( $j( this ).hasClass( 'bookacti-cancel-booking-group' ) ){
				bookacti_dialog_cancel_booking( booking_group_id, 'group' );
			} else if( $j( this ).hasClass( 'bookacti-refund-booking-group' ) ){
				bookacti_dialog_refund_booking( booking_group_id, 'group' );
			} else if( $j( this ).hasClass( 'bookacti-change-booking-group-state' ) ){
				bookacti_dialog_change_booking_state( booking_group_id, 'group' );
			} else if( $j( this ).hasClass( 'bookacti-show-booking-group-bookings' ) ){
				bookacti_display_grouped_bookings( $j( '#bookacti-booking-system-bookings-page' ), booking_group_id );
			} else if( $j( this ).hasClass( 'bookacti-delete-booking-group' ) ){
				bookacti_dialog_delete_booking( booking_group_id, 'group' );
			}
			
		}
		
		// Common action
		// If it is a link which do not have 'prevent-default' class, just follow the link
		if( $j( this ).attr( 'href' ) && $j( this ).attr( 'href' ) !== '' && ! $j( this ).hasClass( 'prevent-default' ) ) {
			if( $j( this ).hasClass( '_blank' ) ) {
				window.open( $j( this ).attr( 'href' ) );
			} else {
				location.href = $j( this ).attr( 'href' );
			}
		}
	});
}


// Show bookings of a group
function bookacti_display_grouped_bookings( booking_system, booking_group_id ) {
	
	booking_group_id = typeof booking_group_id !== 'undefined' && $j.isNumeric( booking_group_id ) ? booking_group_id : false;
	
	if( ! booking_group_id ) { return false; }
	
	var group_row = $j( '.bookacti-show-booking-group-bookings[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr' );
	
	group_row.find( '.bookacti-show-booking-group-bookings' ).toggleClass( 'active' );
	
	// If already displayed, act like a show / hide switch
	if( group_row.next().hasClass( 'bookacti-gouped-booking' ) ) { 
		if( group_row.next().is( ':visible' ) ) {
			group_row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
			if( group_row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
				group_row.after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
			}
		} else {
			group_row.next( '.bookacti-gouped-booking.hidden.dummy' ).remove();
			group_row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).removeClass( 'hidden' );
		}
		return false; 
	}
	
	var data = { 
		'action': 'bookactiGetBookingRows',
		'booking_group_id': booking_group_id,
		'nonce': bookacti_localized.nonce_get_booking_rows
	};
	
	bookacti_booking_row_enter_loading_state( group_row );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Update the booking list
				$j( '#bookacti-bookings-list-container #the-list tr.no-items' ).remove();
				group_row.after( response.rows );
				bookacti_refresh_list_table_hidden_columns();
				
				/**
				 * Trigger after the booking list has been filled.
				 */
				$j( '#bookacti-bookings-list' ).trigger( 'bookacti_grouped_bookings_displayed' );

			} else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_retrieve_bookings;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				var no_bookings_entry = '<tr class="no-items" ><td class="colspanchange" colspan="' + $j( '#bookacti-bookings-list table tr th' ).length + '" >' + message_error + '</td></tr>';
				group_row.after( no_bookings_entry );

			}
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_retrieve_bookings );
			console.log( e );
		},
		complete: function() {
			bookacti_booking_row_exit_loading_state( group_row );
		}
	});
}


// Start booking row loading
function bookacti_booking_row_enter_loading_state( row ) {
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
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


// Refresh Shown / Hidden column
function bookacti_refresh_list_table_hidden_columns() {
	// Show / Hide columns according to page options
	$j( '.hide-column-tog' ).each( function(){ 
		var column = $j( this ).val();
		if( $j( this ).prop( 'checked' ) ) { 
			$j( '.column-' + column ).removeClass( 'hidden' ); 
		} else { 
			$j( '.column-' + column ).addClass( 'hidden' ); 
		}
	});
}




// BOOK AN EVENT

// Check if sent data correspond to displayed data
function bookacti_validate_picked_events( booking_system, quantity ) {
	
	//Get event params
	booking_system	= booking_system || $j( '.bookacti-booking-system:first' );
	quantity		= quantity || 0;
	var booking_system_id	= booking_system.attr( 'id' );
	var group_id	= booking_system.parent().find( 'input[name="bookacti_group_id"]' ).val();
	var event_id	= booking_system.parent().find( 'input[name="bookacti_event_id"]' ).val();
	var event_start	= booking_system.parent().find( 'input[name="bookacti_event_start"]' ).val();
	var event_end	= booking_system.parent().find( 'input[name="bookacti_event_end"]' ).val();
	var total_avail	= 0;
	
	//Init boolean test variables
	var valid_form = {
		is_group			: false,
		is_event			: false,
		is_event_in_selected: false,
		are_picked_events_same_as_group: false,
		is_corrupted		: false,
		is_qty_sup_to_avail	: false,
		is_qty_sup_to_0		: false,
		send				: false
	};
	
	//Make the tests and change the booleans
	if( event_id !== '' && event_start !== '' && event_end !== '' ) { valid_form.is_event = true; }
	
	// Group is validated if it is 'single' and there is an event, 
	// or if it is a number and the corresponding group events exist
	if( group_id !== '' && (	
			( $j.isNumeric( group_id ) && typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] !== 'undefined' ) 
		||	(  group_id === 'single' && valid_form.is_event ) ) ) { valid_form.is_group = true; }
	
	if( parseInt( quantity ) > 0 ) { valid_form.is_qty_sup_to_0 = true; }
	
	if( valid_form.is_group && typeof bookacti.booking_system[ booking_system_id ][ 'picked_events' ] !== 'undefined' ) {
		// Validate single event
		if( group_id === 'single' && bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length === 1 ) {
				if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['id']	=== event_id 
				&&  bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['start']	=== event_start 
				&&  bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['end']	=== event_end ) {

					valid_form.is_event_in_selected = true;

					total_avail = bookacti_get_event_availability( booking_system, bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0] );
				}
			
		// Validate group of events
		} else {
			
			// If both arrays do not have the same number of elements, then the selection is corrupted
			if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ].length === bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ].length ) {
				
				// Make sure picked_events are all in the related group of events
				var are_in_group = true;
				$j.each( bookacti.booking_system[ booking_system_id ][ 'picked_events' ], function( i, picked_event ) {
					var is_in_group = false;
					$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, event_of_group ) {
						if( picked_event['id']		== event_of_group['id'] 
						&&  picked_event['start']	=== event_of_group['start'] 
						&&  picked_event['end']		=== event_of_group['end'] ) {
							is_in_group = true;
							return false; // The event as been found! Break the loop and check the next one
						}
					});
					if( ! is_in_group ) { 
						are_in_group = false;
						return false; // An event hasn't been found, no need to check the others, break the loop
					}
				});
				valid_form.are_picked_events_same_as_group = are_in_group;
				
				total_avail = bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'availability' ];
			}
		}
	}
	
	if( ( parseInt( quantity ) > parseInt( total_avail ) ) && total_avail !== 0 ) {
		valid_form.is_qty_sup_to_avail = true;
	}
	
	// The submitted value is considered as corrupted if 
	// - A single event is submitted but hidden fields data are not consistent with pickedEvent array
	// - A group is submitted but picked_events array is not consistent with groups_events.group_id array
	if( ( group_id === 'single'		&& valid_form.is_event && ! valid_form.is_event_in_selected )
	||  ( $j.isNumeric( group_id )	&& ( ! valid_form.is_group || ! valid_form.are_picked_events_same_as_group ) ) ) { valid_form.is_corrupted = true; }
	
	if( valid_form.is_event 
	&&  valid_form.is_group 
	&&  valid_form.is_qty_sup_to_0 
	&&  ! valid_form.is_qty_sup_to_avail 
	&&  ! valid_form.is_corrupted )	{ valid_form.send = true; }
	
	
	// Clear feedbacks
	booking_system.siblings( '.bookacti-notices' ).empty();
	
	// Allow third-party to change the results
	booking_system.trigger( 'bookacti_validate_picked_events', [ valid_form ] );
	
	//Check the results and build error list
	if( ! valid_form.send ) {
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
	}
	
	return valid_form.send;
}