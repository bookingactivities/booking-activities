$j( document ).ready( function() {
	/**
	 * Highlight the selected row in booking lists
	 * @since 1.7.4
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-list, .bookacti-user-booking-list-table' ).on( 'click', 'tbody tr', function() {
		$j( '.bookacti-booking-list-selected-row' ).removeClass( 'bookacti-booking-list-selected-row' );
		$j( this ).addClass( 'bookacti-booking-list-selected-row' );
	});
	
	
	/**
	 * Add data to booking actions
	 * @since 1.7.6
	 * @version 1.8.0
	 * @param {Event} e
	 * @param {Object} data
	 */
	$j( '#bookacti-booking-list, .bookacti-user-booking-list-table' ).on( 'bookacti_booking_action_data', 'tr.bookacti-single-booking, tr.bookacti-booking-group', function( e, data ) {
		if( data instanceof FormData ) { 
			data.append( 'locale', bookacti_localized.current_locale );
		} else {
			data.locale = bookacti_localized.current_locale;
		}
	});
	
	
	/**
	 * Toggle list table rows on small screens
	 * (must be used with AJAXed bookings list)
	 * @since 1.8.0
	 */
	$j( '#bookacti-booking-list' ).on( 'click', 'tbody .toggle-row', function() {
		$j( this ).closest( 'tr' ).toggleClass( 'is-expanded' );
	});
	
	
	/**
	 * Disable the default toggle for list table rows on small screens
	 * @since 1.8.0
	 */
	$j( '#bookacti-booking-list tbody' ).off( 'click', '.toggle-row' );
});


// BOOKINGS PAGE

/**
 * Filter the booking list with current filters values
 * @since 1.8.0
 * @param {Int} paged
 */
function bookacti_filter_booking_list( paged ) {
	paged = paged ? paged : 1;
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	
	var data = $j( '#bookacti-booking-list-filters-form' ).serializeObject();
	data.paged = paged;
	data.action = 'bookactiGetBookingList';
	
	// Select only available templates
	if( ! data.templates ) {
		data.templates = [];
		$j( '#bookacti-booking-filter-templates option' ).each( function() {
			data.templates.push( $j( this ).val() );
		});
	}
	
	booking_system.trigger( 'bookacti_filter_booking_list_data', [ data ] );
	
	// Loading feedback
	bookacti_start_loading_booking_system( booking_system );
	
	var column_nb = $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length ? $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length : 1;
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	$j( '#bookacti-booking-list #the-list' ).html( '<tr class="no-items" ><td class="colspanchange" colspan="' + column_nb + '" >' + loading_div + '</td></tr>' );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				// Update the booking list
				$j( '#bookacti-booking-list' ).html( response.booking_list );
				bookacti_refresh_list_table_hidden_columns();
				
				// Update the URL without refreshing the page
				window.history.pushState( { path: response.new_url }, '', response.new_url );
				
				/**
				 * Trigger after the booking list has been filtered
				 */
				$j( '#bookacti-booking-list' ).trigger( 'bookacti_booking_list_filtered', [ response, data ] );

			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				var no_bookings_entry = '<tr class="no-items" ><td class="colspanchange" colspan="' + column_nb + '" >' + error_message + '</td></tr>';
				$j( '#bookacti-booking-list #the-list' ).append( no_bookings_entry );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_stop_loading_booking_system( booking_system );
		}
	});
}


/**
 * Change template-related filters
 * @version 1.8.0
 */
function bookacti_update_template_related_filters() {
	// Update activities filter
	var associations = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'templates_per_activities' ];
	var selected_templates = $j( '#bookacti-booking-filter-templates' ).val();
	
	// If no template are selected, show all activities
	if( ! selected_templates ) {
		$j( '#bookacti-booking-filter-activities option' ).show();
		return false;
	}
	if( typeof associations === 'undefined' )  { return false; }
	
	$j( '#bookacti-booking-filter-activities option' ).each( function( i, option ) {
		var activity_id = parseInt( $j( option ).attr( 'value' ) );
		var hide_activity = true;
		$j.each( selected_templates, function( j, selected_template ){
			if( typeof associations[ activity_id ] === 'undefined' ) { return true; /* continue */ }
			if( $j.inArray( selected_template, associations[ activity_id ][ 'template_ids' ] ) === -1 ) { return true; /* continue */ }
			hide_activity = false;
			return false; // Break
		});
		if( hide_activity ) {
			if( $j( option ).is( ':selected' ) ) { $j( option ).prop( 'selected', false ); }
			$j( option ).hide();
		} else {
			$j( option ).show();
		}
	});
}


/**
 * Refresh booking list calendar accoding to dates
 * @version 1.8.5
 */
function bookacti_refresh_calendar_according_to_date_filter() {
	if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) { return false; }

	var booking_system		= $j( '#bookacti-booking-system-bookings-page' );
	var booking_system_id	= booking_system.attr( 'id' );
	var calendar			= booking_system.find( '.bookacti-calendar' );
	var from_date			= $j( '#bookacti-booking-filter-dates-from' ).val();
	var to_date				= $j( '#bookacti-booking-filter-dates-to' ).val();
	
	var interval_filter = {
		"start": moment.utc( from_date ? from_date + ' 00:00:00' : '1970-01-01 00:00:00' ).locale( 'en' ),
		"end": moment.utc( to_date ? to_date + ' 23:59:59' : '2037-12-31 23:59:59' ).locale( 'en' )
	};
	
	bookacti.booking_system[ booking_system_id ][ 'start' ] = interval_filter.start.format( 'YYYY-MM-DD HH:mm:ss' );
	bookacti.booking_system[ booking_system_id ][ 'end' ] = interval_filter.end.format( 'YYYY-MM-DD HH:mm:ss' );
	
	var valid_range = {
		"start": interval_filter.start.format( 'YYYY-MM-DD' ),
		"end": interval_filter.end.add( 1, 'days' ).format( 'YYYY-MM-DD' )
	};
	
	calendar.fullCalendar( 'option', 'validRange', valid_range );
}


/**
 * Unpick all events and reset the event filter
 * @since 1.8.0
 */
function bookacti_unpick_all_events_filter() {
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	bookacti_unpick_all_events( booking_system );
	bookacti_clear_booking_system_displayed_info( booking_system );
	$j( '#bookacti-unpick-events-filter' ).hide( 200 );
	$j( '#bookacti-picked-events-actions-container' ).hide( 200 );
	if( $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
		$j( '#bookacti-pick-event-filter-instruction' ).show( 200 );
	}
}


/**
 * Reload bookings booking system according to filters
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 */
function bookacti_reload_booking_system_according_to_filters( booking_system ) {
	var booking_system_id	= booking_system.attr( 'id' );
	
	var selected_templates	= $j( '#bookacti-booking-filter-templates' ).val();
	var selected_status		= $j( '#bookacti-booking-filter-status' ).val();
	var selected_user		= $j( '#bookacti-booking-filter-customer' ).val();
	var selected_from		= $j( '#bookacti-booking-filter-dates-from' ).val();
	var selected_end		= $j( '#bookacti-booking-filter-dates-end' ).val();
	
	// Select only available templates
	if( ! selected_templates ) {
		selected_templates = [];
		$j( '#bookacti-booking-filter-templates option' ).each( function() {
			selected_templates.push( $j( this ).val() );
		});
	}
	
	bookacti.booking_system[ booking_system_id ][ 'calendars' ]			= selected_templates;
	bookacti.booking_system[ booking_system_id ][ 'activities' ]		= [];
	bookacti.booking_system[ booking_system_id ][ 'group_categories' ]	= [];
	bookacti.booking_system[ booking_system_id ][ 'status' ]			= selected_status ? selected_status : [];
	bookacti.booking_system[ booking_system_id ][ 'user_id' ]			= selected_user ? selected_user : 0;
	bookacti.booking_system[ booking_system_id ][ 'start' ]				= selected_from ? selected_from + ' 00:00:00' : '';
	bookacti.booking_system[ booking_system_id ][ 'end' ]				= selected_end ? selected_end + ' 23:59:59' : '';
	
	// Unpick selected event
	bookacti_unpick_all_events_filter();
	
	bookacti_reload_booking_system( booking_system );
}



// BOOKING LIST

/**
 * Init booking actions
 * @version 1.8.0
 */
function bookacti_init_booking_actions() {
	$j( '.bookacti-user-booking-list-table, .woocommerce-table, #bookacti-booking-list' ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function ( e ) {
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
			} else if( $j( this ).hasClass( 'bookacti-change-booking-quantity' ) ){
				bookacti_dialog_change_booking_quantity( booking_id, 'single' );
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
			} else if( $j( this ).hasClass( 'bookacti-change-booking-group-quantity' ) ){
				bookacti_dialog_change_booking_quantity( booking_group_id, 'group' );
			} else if( $j( this ).hasClass( 'bookacti-show-booking-group-bookings' ) ){
				bookacti_display_grouped_bookings( booking_group_id );
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
	
	// Add / remove items in multiple selectbox
	if( $j( 'body.booking-activities_page_bookacti_bookings' ).length ) {
		bookacti_init_add_and_remove_items();
	}
}


/**
 * Init booking bulk actions
 * @since 1.6.0
 * @version 1.8.0
 */
function bookacti_init_booking_bulk_actions() {
	$j( 'body' ).on( 'submit', '.bookacti-bookings-bulk-action', function( e ) {
		if( $j( this ).find( '[name="action"]' ).val() == -1 || $j( this ).find( '[name="action2"]' ).val() == -1 ) {
			e.preventDefault();
		}
	});
}


/**
 * Change the export type according to the selected tab
 * @since 1.8.0
 */
function bookacti_change_export_type_according_to_active_tab() {
	var active_tab = $j( '#bookacti-export-bookings-dialog .bookacti-tabs li.ui-tabs-active' );
	var export_type = active_tab.length ? ( active_tab.hasClass( 'bookacti-tab-ical' ) ? 'ical' : 'csv' ) : 'csv';
	var export_link_type = $j( '#bookacti-export-bookings-url-container' ).data( 'export-type' );
	$j( '#bookacti-export-type-field' ).val( export_type );
	if( export_link_type === export_type ) {
		$j( '#bookacti-export-bookings-url-container' ).show();
		$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).show();
	} else {
		$j( '#bookacti-export-bookings-url-container' ).hide();
		$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).hide();
	}
}


/**
 * Show bookings of a group
 * @version 1.8.0
 * @param {int} booking_group_id
 * @returns {false|null}
 */
function bookacti_display_grouped_bookings( booking_group_id ) {
	booking_group_id = typeof booking_group_id !== 'undefined' && $j.isNumeric( booking_group_id ) ? booking_group_id : false;
	if( ! booking_group_id ) { return false; }
	
	var group_row = $j( '.bookacti-show-booking-group-bookings[data-booking-group-id="' + booking_group_id + '"]:focus' ).closest( 'tr' );
	
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
	
	// Columns to display
	var columns = [];
	group_row.find( 'td' ).each( function() {
		var column_id = $j( this ).data( 'column-id' );
		if( column_id ) { columns.push( column_id ); }
	});
	
	var data = { 
		'action': 'bookactiGetGroupedBookingsRows',
		'booking_group_id': booking_group_id,
		'is_admin': bookacti_localized.is_admin ? 1 : 0,
		'context': bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list',
		'columns': columns,
		'nonce': bookacti_localized.nonce_get_booking_rows
	};
	group_row.trigger( 'bookacti_booking_action_data', [ data, booking_group_id, 'group', 'display_grouped_bookings' ] );
	
	bookacti_booking_row_enter_loading_state( group_row );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				// Update the booking list
				$j( '#bookacti-booking-list-container #the-list tr.no-items' ).remove();
				group_row.after( response.rows );
				bookacti_refresh_list_table_hidden_columns();
				
				/**
				 * Trigger after the booking list has been filled.
				 */
				$j( '#bookacti-booking-list' ).trigger( 'bookacti_grouped_bookings_displayed' );

			} else if( response.status === 'failed' ) {
				var column_nb = $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length ? $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length : 1;
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				var no_bookings_entry = '<tr class="no-items" ><td class="colspanchange" colspan="' + column_nb + '" >' + error_message + '</td></tr>';
				group_row.after( no_bookings_entry );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_booking_row_exit_loading_state( group_row );
		}
	});
}


/**
 * Start booking row loading
 * @param {HTMLElement} row
 */
function bookacti_booking_row_enter_loading_state( row ) {
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
	+ '</div>';
	row.find( '.bookacti-booking-state' ).hide();
	row.find( '.bookacti-booking-state' ).after( loading_div );
	row.find( '.bookacti-booking-action' ).attr( 'disabled', true );
}


/**
 * Stop booking row loading
 * @param {HTMLElement} row
 */
function bookacti_booking_row_exit_loading_state( row ) {
	row.find( '.bookacti-loading-alt' ).remove();
	row.find( '.bookacti-booking-state' ).show();
	row.find( '.bookacti-booking-action' ).attr( 'disabled', false );
}


/**
 * Refresh Shown / Hidden column
 */
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

/**
 * Check if sent data correspond to displayed data
 * @version 1.8.0
 * @param {HTMLElement} booking_system
 * @param {int} quantity
 * @returns {boolean}
 */
function bookacti_validate_picked_events( booking_system, quantity ) {
	// Get event params
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
	
	// Make the tests and change the booleans
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
				if( bookacti.booking_system[ booking_system_id ][ 'picked_events' ][0]['id']	==  event_id 
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
			error_list += '<li>' + bookacti_localized.error_select_event + '</li>' ; 
		}
		if( valid_form.is_qty_sup_to_avail ){ 
			error_list += '<li>' + bookacti_localized.error_less_avail_than_quantity.replace( '%1$s', quantity ).replace( '%2$s', total_avail ) + '</li>'; 
		}
		if( ! valid_form.is_qty_sup_to_0 ){ 
			error_list += '<li>' + bookacti_localized.error_quantity_inf_to_0 + '</li>'; 
		}
		if( valid_form.is_corrupted ){ 
			error_list += '<li>' + bookacti_localized.error_corrupted_event + '</li>'; 
		}

		// Display error list
		if( error_list !== '' ) {
			booking_system.siblings( '.bookacti-notices' ).append( "<ul class='bookacti-error-list'>" + error_list + "</ul>" ).show();
		}
	}
	
	return valid_form.send;
}