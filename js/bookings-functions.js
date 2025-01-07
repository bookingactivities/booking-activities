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
	 * @version 1.16.0
	 * @param {Event} e
	 * @param {Object} data
	 */
	$j( 'body' ).on( 'bookacti_booking_action_data', function( e, data ) {
		if( data?.form_data instanceof FormData ) {
			data.form_data.append( 'locale', bookacti_localized.current_locale );
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
 * @version 1.16.0
 * @param {Int} paged
 */
function bookacti_filter_booking_list( paged ) {
	paged = paged ? paged : 1;
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	
	var data    = bookacti_get_booking_list_filters();
	data.action = 'bookactiGetBookingList';
	data.paged  = paged;
	
	var column_nb = $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length ? $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length : 1;
	
	bookacti.current_filter_request = $j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		beforeSend: function() {
			if( bookacti.current_filter_request != null ) {
				bookacti.current_filter_request.abort();
			}
			
			// Loading feedback
			bookacti_start_loading_booking_system( booking_system );
			$j( '#bookacti-booking-list #the-list' ).html( '<tr class="no-items" ><td class="colspanchange" colspan="' + column_nb + '" >' + bookacti_get_loading_html() + '</td></tr>' );
		},
		success: function( response ) {
			if( response.status === 'success' ) {
				// Update the booking list
				$j( '#bookacti-booking-list' ).html( response.booking_list );
				bookacti_refresh_list_table_hidden_columns();
				
				// Update the URL without refreshing the page
				window.history.pushState( { path: response.new_url }, '', response.new_url );
				
				// Refresh tooltips and grouped bookings visual frame
				bookacti_refresh_booking_group_frame();
				bookacti_init_tooltip();
				
				$j( '#bookacti-booking-list' ).trigger( 'bookacti_booking_list_filtered', [ response, data ] );

			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				var no_bookings_entry = '<tr class="no-items" ><td class="colspanchange" colspan="' + column_nb + '" >' + error_message + '</td></tr>';
				$j( '#bookacti-booking-list #the-list' ).append( no_bookings_entry );
			}
		},
		error: function( e ) {
			if( e.statusText == 'abort' ) { return; }
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_stop_loading_booking_system( booking_system );
			bookacti_remove_loading_html( $j( '#bookacti-booking-list #the-list' ) );
		}
	});
}


/**
 * Change template-related filters
 * @version 1.16.28
 */
function bookacti_update_template_related_filters() {
	// Update activities filter
	var associations = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'templates_per_activities' ];
	var selected_templates = $j( '#bookacti-booking-filter-templates' ).val();

	// If no template are selected, show all activities
	if( $j.isEmptyObject( selected_templates ) ) {
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
			$j( option ).attr( 'disabled', true ).hide();
		} else {
			$j( option ).attr( 'disabled', false ).show();
		}
	});
	
	// Refresh select2
	if( $j( '#bookacti-booking-filter-activities' ).hasClass( 'select2-hidden-accessible' ) ) { 
		bookacti_select2_destroy( $j( '#bookacti-booking-filter-activities' ) ); 
		bookacti_select2_init();
	}
}


/**
 * Refresh booking list calendar accoding to dates
 * @version 1.15.6
 */
function bookacti_refresh_calendar_according_to_date_filter() {
	if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) { return false; }

	var booking_system    = $j( '#bookacti-booking-system-bookings-page' );
	var booking_system_id = booking_system.attr( 'id' );
	var from_date = $j( '#bookacti-booking-filter-dates-from' ).val();
	var to_date   = $j( '#bookacti-booking-filter-dates-to' ).val();
	
	var interval_filter = {
		"start": from_date ? moment.utc( from_date + ' 00:00:00' ).locale( 'en' ) : '',
		"end": to_date ? moment.utc( to_date + ' 23:59:59' ).locale( 'en' ) : ''
	};
	
	bookacti.booking_system[ booking_system_id ][ 'start' ] = interval_filter.start ? interval_filter.start.format( 'YYYY-MM-DD HH:mm:ss' ) : '';
	bookacti.booking_system[ booking_system_id ][ 'end' ] = interval_filter.end ? interval_filter.end.format( 'YYYY-MM-DD HH:mm:ss' ) : '';
	
	var valid_range = {};
	if( interval_filter.start ) { valid_range.start = interval_filter.start.format( 'YYYY-MM-DD' ); }
	if( interval_filter.end )   { valid_range.end   = interval_filter.end.add( 1, 'days' ).format( 'YYYY-MM-DD' ); }
	
	bookacti.fc_calendar[ booking_system_id ].setOption( 'validRange', valid_range );
}


/**
 * Unpick all events and reset the event filter
 * @since 1.8.0
 * @version 1.9.0
 */
function bookacti_unpick_all_events_filter() {
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	bookacti_clear_booking_system_displayed_info( booking_system );
	$j( '#bookacti-unpick-events-filter' ).hide( 200 );
	$j( '#bookacti-picked-events-actions-container' ).hide( 200 );
	if( $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
		$j( '#bookacti-pick-event-filter-instruction' ).show( 200 );
	}
}


/**
 * Reload bookings booking system according to filters
 * @version 1.16.0
 * @param {HTMLElement} booking_system
 */
function bookacti_reload_booking_system_according_to_filters( booking_system ) {
	var booking_system_id = booking_system.attr( 'id' );
	
	var selected_templates = $j( '#bookacti-booking-filter-templates' ).val();
	var selected_status    = $j( '#bookacti-booking-filter-status' ).val();
	var selected_user      = $j( '#bookacti-booking-filter-customer' ).val();
	var selected_from      = $j( '#bookacti-booking-filter-dates-from' ).val();
	var selected_end       = $j( '#bookacti-booking-filter-dates-end' ).val();
	
	// Select only available templates
	if( ! selected_templates || $j.isEmptyObject( selected_templates ) ) {
		selected_templates = [];
		$j( '#bookacti-booking-filter-templates option' ).each( function() {
			selected_templates.push( $j( this ).val() );
		});
	}
	
	bookacti.booking_system[ booking_system_id ][ 'calendars' ]        = selected_templates;
	bookacti.booking_system[ booking_system_id ][ 'activities' ]       = [];
	bookacti.booking_system[ booking_system_id ][ 'group_categories' ] = [];
	bookacti.booking_system[ booking_system_id ][ 'status' ]           = selected_status ? selected_status : [];
	bookacti.booking_system[ booking_system_id ][ 'user_id' ]          = selected_user ? [ selected_user ] : [];
	bookacti.booking_system[ booking_system_id ][ 'start' ]            = selected_from ? selected_from + ' 00:00:00' : '';
	bookacti.booking_system[ booking_system_id ][ 'end' ]              = selected_end ? selected_end + ' 23:59:59' : '';
	
	// Unpick selected event
	bookacti_unpick_all_events_filter();
	
	bookacti_reload_booking_system( booking_system );
}



// BOOKING LIST

/**
 * Init booking actions
 * @version 1.16.1
 * @param {String} scope
 */
function bookacti_init_booking_actions( scope ) {
	if( ! scope ) { scope = '#bookacti-booking-list, .bookacti-user-booking-list-table'; }
	
	$j( scope ).on( 'click', '.bookacti-booking-action, .bookacti-booking-group-action', function( e ) {
		e.preventDefault();
		var action = $j( this ).data( 'action' );
		if( ! action ) { return; }
		
		bookacti.user_auth_key = $j( this ).data( 'user-auth-key' ) ? $j( this ).data( 'user-auth-key' ) : '';
		
		var booking_selection = {
			'booking_ids': [],
			'booking_group_ids': [],
			'all': 0,
			'filters': {}
		};
		
		// Single Bookings
		if( $j( this ).hasClass( 'bookacti-booking-action' ) ) {
			var booking_id = $j( this ).data( 'booking-id' );
			if( booking_id ) {
				booking_selection.booking_ids.push( parseInt( booking_id ) );
			}
		// Booking Groups
		} else {
			var booking_group_id = $j( this ).data( 'booking-group-id' );
			if( booking_group_id ) {
				booking_selection.booking_group_ids.push( parseInt( booking_group_id ) );
			}
		}
		
		// If the action is a link that do not have 'prevent-default' class, just follow the link
		if( $j( this ).attr( 'href' ) && $j( this ).attr( 'href' ) !== '' && ! $j( this ).hasClass( 'prevent-default' ) ) {
			if( $j( this ).hasClass( '_blank' ) ) {
				window.open( $j( this ).attr( 'href' ) );
			} else {
				location.href = $j( this ).attr( 'href' );
			}
			return;
		}
		
		// Do not trigger action if selection is empty
		if( ! booking_selection.booking_ids.length && ! booking_selection.booking_group_ids.length ) {
			return;
		}
		
		bookacti_trigger_booking_action( action, booking_selection );
	});
}


/**
 * Init booking bulk actions
 * @since 1.6.0
 * @version 1.16.1
 */
function bookacti_init_booking_bulk_actions() {
	$j( '#bookacti-bookings-container' ).on( 'click', '.bulkactions input[type="submit"]', function( e ) {
		e.preventDefault();
		var action = $j( this ).siblings( 'select[name^="action"]' ).val();
		if( ! action ) { return; }
		
		var booking_selection = {
			'booking_ids': [],
			'booking_group_ids': [],
			'all': parseInt( $j( '#bookacti-all-selected' ).val() ) ? 1 : 0,
			'filters': parseInt( $j( '#bookacti-all-selected' ).val() ) ? bookacti_get_booking_list_filters() : {}
		};
		
		$j( '#bookacti-bookings-container tbody .check-column input[name="booking_ids[]"]:checked' ).each( function() {
			var booking_id = $j( this ).val();
			if( booking_id ) {
				booking_selection.booking_ids.push( parseInt( booking_id ) );
			}
		});

		$j( '#bookacti-bookings-container tbody .check-column input[name="booking_group_ids[]"]:checked' ).each( function() {
			var booking_group_id = $j( this ).val();
			if( booking_group_id ) {
				booking_selection.booking_group_ids.push( parseInt( booking_group_id ) );
			}
		});
		
		// Do not trigger action if selection is empty
		if( ! booking_selection.booking_ids.length && ! booking_selection.booking_group_ids.length ) {
			return;
		}
		
		bookacti_trigger_booking_action( action, booking_selection );
	});
}


/**
 * Trigger a booking action for a booking selection
 * @since 1.16.0
 * @param {string} action
 * @param {object} booking_selection
 */
function bookacti_trigger_booking_action( action, booking_selection ) {
	if( ! booking_selection.booking_ids.length 
	&&  ! booking_selection.booking_group_ids.length
	&&  ! ( booking_selection.all && ! $j.isEmptyObject( booking_selection.filters ) ) ) { return; }

	if( action === 'reschedule' ) {
		bookacti_dialog_reschedule_bookings( booking_selection );
	} else if( action === 'refund' ) {
		bookacti_dialog_refund_bookings( booking_selection );
	} else if( action === 'edit_status' ) {
		bookacti_dialog_change_bookings_status( booking_selection );
	} else if( action === 'edit_quantity' ) {
		bookacti_dialog_change_bookings_quantity( booking_selection );
	} else if( action === 'send_notification' ) {
		bookacti_dialog_send_bookings_notification( booking_selection );
	} else if( action === 'delete' ) {
		bookacti_dialog_delete_bookings( booking_selection );
	} else if( action === 'display_grouped_bookings' ) {
		bookacti_display_grouped_bookings( booking_selection );
	} else if( action === 'cancel' ) {
		bookacti_dialog_cancel_bookings( booking_selection );
	} else {
		$j( 'body' ).trigger( 'bookacti_trigger_booking_action', [ action, booking_selection ] );
	}
}


/**
 * Unselect all bookings in booking list
 * @since 1.16.1
 */
function bookacti_unselect_all_bookings() {
	$j( '#bookacti-bookings-container thead .check-column input[type="checkbox"]' ).prop( 'checked', true ).trigger( 'click.wp-toggle-checkboxes' );
	$j( '#bookacti-bookings-container .tablenav .bookacti-select-all-container' ).remove();
	$j( '#bookacti-all-selected' ).val( 0 );
}


/**
 * Get booking list filters as a serialized object
 * @since 1.16.0
 * @returns {Object}
 */
function bookacti_get_booking_list_filters() {
	var filters = $j( '#bookacti-booking-list-filters-form' ).length ? bookacti_serialize_object( $j( '#bookacti-booking-list-filters-form' ) ) : {};
	
	// Select only available templates
	if( ! $j.isEmptyObject( filters ) ) {
		if( ! filters.templates ) {
			filters.templates = [];
			$j( '#bookacti-booking-filter-templates option' ).each( function() {
				filters.templates.push( $j( this ).val() );
			});
		}
	}
	
	$j( '#bookacti-booking-list-filters-form' ).trigger( 'bookacti_filter_booking_list_data', [ filters ] );
	
	return filters;
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
 * Show groups bookings
 * @version 1.16.0
 * @param {object} booking_selection
 */
function bookacti_display_grouped_bookings( booking_selection ) {
	// Get the selected booking rows only (ignore "all")
	var rows = $j();
	$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
		rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
	});
	
	// If already displayed, act like a show / hide switch
	rows.each( function() {
		var booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
		if( ! booking_group_id ) { return true; }
		
		if( $j( this ).next().hasClass( 'bookacti-gouped-booking' ) ) { 
			if( $j( this ).next().is( ':visible' ) ) {
				$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
				if( $j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
					$j( this ).after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
				}
				$j( this ).find( '.bookacti-show-booking-group-bookings' ).removeClass( 'active' );
			} else {
				$j( this ).next( '.bookacti-gouped-booking.hidden.dummy' ).remove();
				$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).removeClass( 'hidden' );
				$j( this ).find( '.bookacti-show-booking-group-bookings' ).addClass( 'active' );
			}
			// Remove the booking group from the selection
			booking_selection.booking_group_ids = jQuery.grep( booking_selection.booking_group_ids, function( value ) { return value != booking_group_id; } );
			rows = rows.not( $j( this ) );
		}
	});
	if( ! rows.length || ! booking_selection.booking_group_ids.length ) { return; }
	
	// Columns to display
	var columns = [];
	rows.first().find( 'td' ).each( function() {
		var column_id = $j( this ).data( 'column-id' );
		if( column_id ) { columns.push( column_id ); }
	});
	
	// Retrieve the grouped bookings of the displayed rows only
	booking_selection.all     = false;
	booking_selection.filters = {};

	var data = { 'form_data': new FormData() };
	data.form_data.append( 'action', 'bookactiGetGroupedBookingsRows' );
	data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
	data.form_data.append( 'columns', JSON.stringify( columns ) );
	data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
	data.form_data.append( 'user_auth_key', typeof bookacti.user_auth_key !== 'undefined' ? bookacti.user_auth_key : '' );
	data.form_data.append( 'nonce', bookacti_localized.nonce_get_booking_rows );

	$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'display_grouped_bookings' ] );
	
	// Display a loader
	bookacti_booking_row_enter_loading_state( rows );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data.form_data,
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		success: function( response ) {
			if( response.status === 'success' ) {
				// Update the booking list
				$j( '#bookacti-booking-list-container #the-list tr.no-items' ).remove();
				
				// Replace the rows
				if( response.rows ) {
					var new_rows = $j( response.rows );
					rows.each( function() {
						var row_booking_group_id      = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
						var grouped_bookings_selector = row_booking_group_id ? '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' : '';
						var grouped_bookings_rows     = grouped_bookings_selector ? new_rows.find( grouped_bookings_selector ).addBack( grouped_bookings_selector ).closest( 'tr' ) : $j();
						if( row_booking_group_id ) {
							$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
						}
						if( grouped_bookings_rows.length ) {
							$j( this ).after( grouped_bookings_rows );
						}
					});
					bookacti_refresh_list_table_hidden_columns();
					bookacti_init_tooltip();
				}
				
				// Refresh grouped bookings visual frame
				bookacti_refresh_booking_group_frame();
				
				$j( 'body' ).trigger( 'bookacti_grouped_bookings_displayed', [ response ] );

			} else if( response.status === 'failed' ) {
				var column_nb = $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length ? $j( '#bookacti-booking-list thead .manage-column:not(.hidden)' ).length : columns.length;
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				var no_bookings_entry = '<tr class="no-items"><td class="colspanchange" colspan="' + column_nb + '">' + error_message + '</td></tr>';
				rows.after( no_bookings_entry );
			}
		},
		error: function( e ) {
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_booking_row_exit_loading_state( rows );
		}
	});
}


/**
 * Start booking row loading
 * @version 1.16.0
 * @param {HTMLElement} row
 */
function bookacti_booking_row_enter_loading_state( row ) {
	row.find( '.bookacti-booking-status' ).hide();
	bookacti_add_loading_html( row.find( '.bookacti-booking-status' ), 'after' );
	row.find( '.bookacti-booking-action' ).attr( 'disabled', true );
}


/**
 * Stop booking row loading
 * @version 1.16.0
 * @param {HTMLElement} row
 */
function bookacti_booking_row_exit_loading_state( row ) {
	bookacti_remove_loading_html( row );
	row.find( '.bookacti-booking-status' ).show();
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


/**
 * Add a frame around groups in booking list
 * @since 1.8.6
 */
function bookacti_refresh_booking_group_frame() {
	$j( '.bookacti-gouped-booking' ).removeClass( 'bookacti-gouped-booking-first bookacti-gouped-booking-last' );
	
	var group_id = 0;
	$j( '.bookacti-gouped-booking' ).each( function() {
		if( group_id === $j( this ).data( 'booking-group-id' ) ) { return true; } // skip
		group_id = $j( this ).data( 'booking-group-id' );
		$j( '.bookacti-gouped-booking[data-booking-group-id="' + group_id + '"]:first' ).addClass( 'bookacti-gouped-booking-first' );
		$j( '.bookacti-gouped-booking[data-booking-group-id="' + group_id + '"]:last' ).addClass( 'bookacti-gouped-booking-last' );
	});
}




// BOOK AN EVENT

/**
 * Check if sent data correspond to displayed data
 * @version 1.15.13
 * @param {HTMLElement} booking_system
 * @param {int} quantity
 * @returns {boolean}
 */
function bookacti_validate_picked_events( booking_system, quantity ) {
	booking_system	= booking_system || $j( '.bookacti-booking-system:first' );
	quantity		= quantity || 0;
	
	var booking_system_id = booking_system.attr( 'id' );
	var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
	
	var valid_form = {
		events_selected: true,
		consistent_inputs: true,
		is_qty_sup_to_0: true,
		is_qty_inf_to_avail: true,
		send: true
	};
	
	var form = booking_system.closest( 'form' );
	var form_values = { 'selected_events': {} };
	
	if( form.length ) {
		form_values = bookacti_serialize_object( form );
	} else {
		var inputs = booking_system.siblings( '.bookacti-booking-system-inputs' );
		if( inputs.length ) { 
			inputs.wrap( '<form class="bookacti-temporary-form"></form>' );
			form_values = bookacti_serialize_object( inputs.closest( 'form.bookacti-temporary-form' ) );
			inputs.unwrap( 'form.bookacti-temporary-form' );
		}
	}
	
	if( typeof form_values[ 'selected_events' ] === 'undefined' )	{ valid_form.events_selected = false; }
	else if( $j.isEmptyObject( form_values[ 'selected_events' ] ) )	{ valid_form.events_selected = false; }
	if( ! picked_events.length )									{ valid_form.events_selected = false; }
	
	// Check if the picked events inputs match the picked events object
	if( valid_form.events_selected ) {
		var i = 0;
		$j.each( picked_events, function( j, picked_event ) {
			// Break the loop if a problem has been detected
			if( ! valid_form.consistent_inputs ) { return false; }

			// Groups of events
			if( parseInt( picked_event.group_id ) > 0 ) {
				if( form_values[ 'selected_events' ][ i ][ 'group_id' ] != picked_event.group_id
				||  form_values[ 'selected_events' ][ i ][ 'group_date' ] !== picked_event.group_date ) {
					valid_form.consistent_inputs = false;
				}

			// Single events
			} else {
				if( form_values[ 'selected_events' ][ i ][ 'id' ] != picked_event.id 
				||  form_values[ 'selected_events' ][ i ][ 'start' ] !== picked_event.start 
				||  form_values[ 'selected_events' ][ i ][ 'end' ] !== picked_event.end ) {
					valid_form.consistent_inputs = false;
				}
			}
			++i;
		});
		
		// Count the number of selected_events inputs
		var count_form_values = 0; var k;
		for( k in form_values[ 'selected_events' ] ) { if( form_values[ 'selected_events' ].hasOwnProperty( k ) ) { ++count_form_values; } }
		var count_picked_events = 0; var l;
		for( l in picked_events ) { if( picked_events.hasOwnProperty( l ) ) { ++count_picked_events; } }
		
		// The number of selected_events inputs must match the number of picked events objects
		if( count_form_values !== count_picked_events ) { valid_form.consistent_inputs = false; }
	}

	// Check quantity
	if( parseInt( quantity ) <= 0 ) { valid_form.is_qty_sup_to_0 = false; }
	else {
		var qty_data = bookacti_get_min_and_max_quantity( booking_system );
		if( ( parseInt( quantity ) > parseInt( qty_data.avail ) ) ) {
			valid_form.is_qty_inf_to_avail = false;
		}
	}
	
	if( ! valid_form.events_selected
	||  ! valid_form.consistent_inputs
	||  ! valid_form.is_qty_sup_to_0 
	||  ! valid_form.is_qty_inf_to_avail )	{ valid_form.send = false; }
	
	// Display feedbacks
	booking_system.siblings( '.bookacti-notices' ).empty();
	
	// Allow third-party to change the results
	booking_system.trigger( 'bookacti_validate_picked_events', [ valid_form ] );
	
	// Check the results and build error list
	if( ! valid_form.send ) {
		var error_list = '';
		if( ! valid_form.events_selected ) { 
			error_list += '<li>' + bookacti_localized.error_select_event + '</li>' ; 
		}
		if( ! valid_form.is_qty_inf_to_avail ){ 
			error_list += '<li>' + bookacti_localized.error_less_avail_than_quantity.replace( '%1$s', quantity ).replace( '%2$s', qty_data.avail ) + '</li>'; 
		}
		if( ! valid_form.is_qty_sup_to_0 ){ 
			error_list += '<li>' + bookacti_localized.error_quantity_inf_to_0 + '</li>'; 
		}
		if( ! valid_form.consistent_inputs ){ 
			error_list += '<li>' + bookacti_localized.error_corrupted_event + '</li>'; 
		}

		// Display error list
		if( error_list !== '' ) {
			booking_system.siblings( '.bookacti-notices' ).append( "<ul class='bookacti-error-list'>" + error_list + "</ul>" ).show();
		}
	}
	
	return valid_form.send;
}