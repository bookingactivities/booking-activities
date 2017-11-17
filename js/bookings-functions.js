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
	$j( '.bookacti-booking-actions, .bookacti-booking-group-actions, #bookacti-bookings-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
		
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
				bookacti_fill_booking_list( $j( '#bookacti-booking-system-bookings-page' ), null, null, booking_group_id );
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


// Init booking filters
function bookacti_init_booking_filters( booking_system ) {
    $j( '.bookacti-bookings-filter-activity, .bookacti-bookings-filter-template' ).off( 'click' ).on( 'click', function( e ){
		bookacti_select_bookings_filter( e, booking_system, $j( this ) );
		
		if( $j( this ).hasClass( 'bookacti-bookings-filter-template' ) ) {
			bookacti_filter_bookings_by_templates( booking_system );
		} else if( $j( this ).hasClass( 'bookacti-bookings-filter-activity' ) ) {
			bookacti_filter_bookings_by_activities( booking_system );
		}
	});
	
	// Apply activity filters on first load
	bookacti_filter_bookings_by_activities( booking_system );
}


// Select bookings filters
function bookacti_select_bookings_filter( e, booking_system, selected_filter ) {
	
	e = e || false;
	
	// Select the filters (press CTRL to select multiple for non touch devices)
	if( e && ! bookacti.is_touch_device ) {
		if( ! e.ctrlKey ) {
			selected_filter.parent().find( '.bookacti-bookings-filter' ).attr( 'selected', false );
		}
	}
	
	if( selected_filter.is( '[selected]' ) ) {
		selected_filter.attr( 'selected', false );
	} else {
		selected_filter.attr( 'selected', true );
	}
}


// Filter bookings by template
function bookacti_filter_bookings_by_templates( booking_system ) {
		
	var booking_system_id	= booking_system.attr( 'id' );
	var attributes			= bookacti.booking_system[ booking_system_id ];
	var booking_method		= attributes.method;
		
	// Retrieve the selected templates
	var selected_templates	= [];
	$j( '.bookacti-bookings-filter-template[selected]' ).each( function(){
		selected_templates.push( $j( this ).data( 'template-id' ).toString() );
	});	
	
	// If templates has changed, reload events
	var are_same = bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'calendars' ], selected_templates );
	if( ! are_same ) {
		
		bookacti_clear_booking_system_displayed_info( booking_system );
		
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
					'nonce': bookacti_localized.nonce_selected_template_filter
				},
			type: 'POST',
			dataType: 'json',
			success: function( response ){
				
				if( response.status === 'success' ) {
					
					// Replace activities filters HTML
					$j( '#bookacti-activities-filter-content' ).html( response.activities_html );
					bookacti_filter_bookings_by_activities( booking_system );
					
					// Update calendar data
					bookacti.booking_system[ booking_system_id ][ 'calendars' ]				= response.calendar_ids;
					bookacti.booking_system[ booking_system_id ][ 'activities' ]			= response.activity_ids;
					bookacti.booking_system[ booking_system_id ][ 'group_categories' ]		= response.group_categories;
					bookacti.booking_system[ booking_system_id ][ 'settings' ]				= response.settings;
					
					// Update calendar content data
					bookacti.booking_system[ booking_system_id ][ 'events' ]				= response[ 'events' ];
					bookacti.booking_system[ booking_system_id ][ 'activities_data' ]		= response.activities_data;
					bookacti.booking_system[ booking_system_id ][ 'groups_events' ]			= response.groups_events;
					bookacti.booking_system[ booking_system_id ][ 'groups_data' ]			= response.groups_data;
					bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ]	= response.group_categories_data;
					
					// Update booking system settings
					bookacti_booking_method_update_settings( booking_system, booking_method );
					
					// Fill the calendar with events
					bookacti_booking_method_fill_with_events( booking_system, booking_method );
				
					// Filter by activities
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
	}
}


// Filter bookings by activity
function bookacti_filter_bookings_by_activities( booking_system ) {
	bookacti.hidden_activities = [];
	$j( '.bookacti-bookings-filter-activity' ).each( function(){
		var activity_id = $j( this ).data( 'activity-id' );
		bookacti.hidden_activities.push( activity_id );
	});
	
	if( ! $j( '.bookacti-bookings-filter-activity[selected]' ).length ) {
		$j( '.bookacti-bookings-filter-activity:first' ).attr( 'selected', true );
	}
	
	$j( '.bookacti-bookings-filter-activity[selected]' ).each( function(){
		var activity_id	= $j( this ).data( 'activity-id' );
		var idx			= $j.inArray( activity_id, bookacti.hidden_activities );
		bookacti.hidden_activities.splice( idx, 1 );
	});
	
	var booking_method = bookacti.booking_system[ booking_system.attr( 'id' ) ][ 'method' ];
	bookacti_booking_method_rerender_events( booking_system, booking_method );
}


// Fill the booking list of an event
function bookacti_fill_booking_list( booking_system, event, event_group_id, booking_group_id ) {
	
	event				= event || false;
	event_group_id		= event_group_id || false;
	booking_group_id	= booking_group_id || false;
	
	var data = { 
		'action': 'bookactiGetBookingRows',
		'nonce': bookacti_localized.nonce_get_booking_rows
	};
	
	if( typeof booking_group_id !== 'undefined' && $j.isNumeric( booking_group_id ) ) {
		data.booking_group_id = booking_group_id;
	
	} else if( typeof event_group_id !== 'undefined' && $j.isNumeric( event_group_id ) ) {
		data.event_group_id	= event_group_id;
	
	} else {
		data.event_id		= event.id;
		data.event_start	= event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
		data.event_end		= event.end instanceof moment ? event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;
	}
	
	bookacti_start_loading_booking_system( booking_system );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Update the booking list
				$j( '#bookacti-bookings-list-container #the-list' ).html( response.rows );
				
				
				/**
				 * Trigger after the booking list has been filled.
				 */
				$j( '#bookacti-bookings-list' ).trigger( 'bookacti_booking_list_filled' );

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
}

// Deactivate booking list action buttons
function bookacti_bookings_exit_loading_state( booking_system ) {
	bookacti_init_booking_filters( booking_system );
	bookacti_bind_bookings_dialogs( booking_system );
	
	$j( '.bookacti-bookings-title-gear' ).attr( 'disabled', false );
	$j( '.bookacti-bookings-filter-content' ).attr( 'disabled', false );
	$j( '#bookacti-bookings-list .bookacti-booking-action' ).attr( 'disabled', false );
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
				if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['id']		=== event_id 
				&&  bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['start']	=== event_start 
				&&  bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['end']		=== event_end ) {

					valid_form.is_event_in_selected = true;

					total_avail = bookacti_get_event_availability( bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0] );
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
				
				total_avail = bookacti_get_group_availability( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] );
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