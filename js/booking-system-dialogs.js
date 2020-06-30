$j( document ).ready( function() {
	/**
	 * Close dialogs when the user clicks outside
	 */
	$j( 'body' ).on( 'click', '.ui-widget-overlay', function (){
		$j( 'div:ui-dialog:visible' ).dialog( 'close' );
	});
	
	
	/**
	 * Press ENTER to bring focus on dialog OK button
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'keydown', '.bookacti-booking-system-dialog', function( e ) {
		if( ! $j( 'textarea' ).is( ':focus' ) && e.keyCode == $j.ui.keyCode.ENTER ) {
			$j( this ).parent().find( '.ui-dialog-buttonpane button:first' ).focus(); 
			return false; 
		}
	});
	
	
	/**
	 * Toggle group events list
	 * @param {Event} e
	 * @param {Int} group_id
	 * @param {object} event
	 */
	$j( 'body' ).on( 'bookacti_group_of_events_preview', '.bookacti-booking-system', function( e, group_id, event ) {
		var booking_system		= $j( this );
		var booking_system_id	= $j( this ).attr( 'id' );
		var groups_list			= $j( '#' + booking_system_id + '-choose-group-of-events-dialog .bookacti-groups-of-events-list' );
		
		// Hide other events list
		groups_list.find( '.bookacti-group-of-events-option[data-group-id!="' + group_id + '"]' ).data( 'show-events', 0 ).attr( 'data-show-events', 0 );
		groups_list.find( '.bookacti-group-of-events-list[data-group-id!="' + group_id + '"]' ).hide( 200 );
		
		// Show group events list
		groups_list.find( '.bookacti-group-of-events-option[data-group-id="' + group_id + '"]' ).data( 'show-events', 1 ).attr( 'data-show-events', 1 );
		groups_list.find( '.bookacti-group-of-events-list[data-group-id="' + group_id + '"]' ).show( 200 );
		
		// Pick events and fill form inputs
		bookacti_unpick_all_events( booking_system );
		bookacti_pick_events_of_group( booking_system, group_id, event );
	});
});


// INITIALIZATION

/**
 * Initialize bookings dialogs
 */
function bookacti_init_booking_system_dialogs() {
	// Common param
	$j( '.bookacti-booking-system-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		400,
		"resize":		'auto',
		"show":			true,
		"hide":			true,
		"dialogClass":	'bookacti-dialog',
		"closeText":	'&#10006;',
		"close":		function() {}
	});
}


/**
 * Choose a group of events dialog
 * @version 1.8.5
 * @param {HTMLElement} booking_system
 * @param {array} group_ids
 * @param {object} event
 */
function bookacti_dialog_choose_group_of_events( booking_system, group_ids, event ) {
	var booking_system_id		= booking_system.attr( 'id' );
	var dialog					= $j( '#' + booking_system_id + '-choose-group-of-events-dialog' );
	var groups_of_events_list	= $j( '#' + booking_system_id + '-groups-of-events-list' );
	
	var bookings_only		= bookacti.booking_system[ booking_system_id ][ 'bookings_only' ];
	var past_events			= bookacti.booking_system[ booking_system_id ][ 'past_events' ];
	var past_events_bookable= bookacti.booking_system[ booking_system_id ][ 'past_events_bookable' ];
	var current_time		= moment.utc( bookacti_localized.current_time );
	
	groups_of_events_list.data( 'booking-system-id', booking_system_id );
	
	groups_of_events_list.empty();
	
	// Fill the dialog with the different choices
	
	// Add single event option if allowed
	if( bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) {
		var group_id = 'single';
		
		// Show availability or bookings
		var avail_html = '';
		if( bookings_only ) {
			var bookings = bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, group_ids );
			var booking_html = bookings === 1 ? bookacti_localized.booking : bookacti_localized.bookings;
			avail_html = bookings + ' ' + booking_html;
		} else {
			var availability	= bookacti_get_event_availability( booking_system, event );
			var avail			= availability === 1 ? bookacti_localized.avail : bookacti_localized.avails;
			avail_html = availability + ' ' + avail;
		}
		
		// Check event availability
		var is_available = true;
		
		// Check if the event is past
		if( past_events ) {
			var event_start	= moment.utc( event.start ).clone();
			var event_end	= moment.utc( event.end ).clone();
			if( ! past_events_bookable && event_start.isBefore( current_time ) 
			&& ! ( bookacti_localized.started_events_bookable && event_end.isAfter( current_time ) ) ) {
				is_available = false;
			}
		}
		
		if( is_available && typeof bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ] !== 'undefined' ) {
			// Check the min quantity required
			is_available = false;
			var min_qty_ok = false;
			var activity_id		= parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ] );
			var activity_data	= bookacti.booking_system[ booking_system_id ][ 'activities_data' ][ activity_id ][ 'settings' ];
			var min_quantity	= typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 0 );
			if( min_quantity <= availability ) { min_qty_ok = true; }
			
			// Check the max quantity allowed AND
			// Check the max number of different users allowed
			var max_qty_ok = max_users_ok = true;
			var max_quantity	= typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : 0 );
			var max_users		= typeof activity_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( activity_data[ 'max_users_per_event' ] ? parseInt( activity_data[ 'max_users_per_event' ] ) : 0 );

			if( max_quantity || max_users ) {
				var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ] !== 'undefined' ) {
					if( typeof bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ] !== 'undefined' ) {
						var occurrence = bookacti.booking_system[ booking_system_id ][ 'bookings' ][ event.id ][ event_start_formatted ];
						var qty_booked = parseInt( occurrence[ 'current_user_bookings' ] );
						if( max_users && qty_booked === 0 && parseInt( occurrence[ 'distinct_users' ] ) >= max_users ) {
							max_users_ok = false;
						}
						if( max_quantity && qty_booked >= max_quantity ) {
							max_qty_ok = false;
						}
					}
				}
			}
			
			if( min_qty_ok && max_qty_ok && max_users_ok ) { is_available = true; }
		}
		
		var container = $j( '<div></div>', {});
		var option_container = $j( '<div></div>', {
			'class': 'bookacti-group-of-events-option',
			'data-group-id': group_id,
			'data-show-events': 0
		});
		var radio = $j( '<input />', {
			'id': 'bookacti-group-of-events-' + group_id,
			'type': 'radio',
			'name': 'group_of_events',
			'value': group_id,
			'disabled': ! bookings_only && ! is_available
		});
		
		var single_label = {
			'html': bookacti_localized.single_event + ' <span class="bookacti-group-availability" >(' + avail_html + ')</span>',
			'for': 'bookacti-group-of-events-' + group_id
		};
		
		// Allow third party to edit single label
		booking_system.trigger( 'bookacti_choose_group_dialog_group_label', [ single_label, 'single', event ] );
		var label = $j( '<label></label>', single_label );
		
		var event_list = $j( '<ul></ul>', {
			'class': 'bookacti-group-of-events-list bookacti-custom-scrollbar',
			'data-group-id': group_id
		});
		
		var event_duration = bookacti_format_event_duration( event.start, event.end );
		
		var event_data = {
			'title': event.title,
			'duration': event_duration,
			'quantity': 1
		};

		booking_system.trigger( 'bookacti_group_of_events_list_data', [ event_data, event ] );
		
		var list_element_data = {
			'html': '<span class="bookacti-booking-event-duration" >'  + event_data.duration + '</span>' 
				+ '<span class="bookacti-booking-event-title-separator" > - </span>' 
				+ '<span class="bookacti-booking-event-title" >'  + event_data.title + '</span>' 
		};
		
		booking_system.trigger( 'bookacti_group_of_events_list_element_data', [ list_element_data, event ] );
		
		var list_element = $j( '<li></li>', list_element_data );
		
		option_container.append( radio );
		option_container.append( label );
		container.append( option_container );
		
		event_list.append( list_element );
		container.append( event_list );
		
		groups_of_events_list.append( container );
	}
	
	// Add each available group of events as a radio option
	$j.each( group_ids, function( i, group_id ) {
		group_id = parseInt( group_id );
		if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] !== 'undefined'
		&&  typeof bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ] !== 'undefined') {
			
			var group					= bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ];
			var availability			= group.availability;
			var category_id				= parseInt( group[ 'category_id' ] );
			var category_data			= bookacti.booking_system[ booking_system_id ][ 'group_categories_data' ][ category_id ][ 'settings' ];
			var started_groups_bookable	= bookacti_localized.started_groups_bookable;
			if( typeof category_data[ 'started_groups_bookable' ] !== 'undefined' ) {
				if( $j.inArray( category_data[ 'started_groups_bookable' ], [ 0, 1, '0', '1', true, false ] ) >= 0 ) {
					started_groups_bookable	= parseInt( category_data[ 'started_groups_bookable' ] );
				}
			}
			
			// Check group of events availability
			var is_available = true;
			
			// Check if the group is past
			var group_start	= moment.utc( group.start ).clone();
			var group_end	= moment.utc( group.end ).clone();
			if( ! past_events_bookable && group_start.isBefore( current_time ) 
			&& ! ( started_groups_bookable && group_end.isAfter( current_time ) ) ) {
				is_available = false; // Skip this group
			}
			
			if( is_available ) {
				// Check the min quantity
				is_available		= false;
				var min_qty_ok		= false;
				var min_quantity	= typeof category_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'min_bookings_per_user' ] ? parseInt( category_data[ 'min_bookings_per_user' ] ) : 0 );
				if( min_quantity <= availability && availability > 0 ) { min_qty_ok = true; }
				
				var max_users		= typeof category_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( category_data[ 'max_users_per_event' ] ? parseInt( category_data[ 'max_users_per_event' ] ) : 0 );
				var max_quantity	= typeof category_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( category_data[ 'max_bookings_per_user' ] ? parseInt( category_data[ 'max_bookings_per_user' ] ) : 0 );
				
				var max_qty_ok = max_users_ok = true;
				if( max_quantity || max_users ) {
					var qty_booked = parseInt( group[ 'current_user_bookings' ] );
					if( max_users && qty_booked === 0 && parseInt( group[ 'distinct_users' ] ) >= max_users ) {
						max_users_ok = false;
					}
					if( max_quantity && qty_booked >= max_quantity ) {
						max_qty_ok = false;
					}
				}
				
				if( min_qty_ok && max_qty_ok && max_users_ok ) { is_available = true; }
			}
			
			var container = $j( '<div></div>', {} );
			var option_container = $j( '<div></div>', {
				'class': 'bookacti-group-of-events-option',
				'data-group-id': group_id,
				'data-show-events': 0
			});
			var radio = $j( '<input />', {
				'id': 'bookacti-group-of-events-' + group_id,
				'type': 'radio',
				'name': 'group_of_events',
				'disabled': ! bookings_only && ! is_available,
				'value': group_id
			});
			
			// Show availability or bookings
			var avail_html = '';
			if( bookings_only ) {
				var bookings = 0;
				$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, grouped_event ) {
					var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
					var event_end_formatted = moment.utc( event.end ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
					
					if( event.id == grouped_event.id
					&&  event_start_formatted === grouped_event.start 
					&&  event_end_formatted === grouped_event.end ) {
						bookings = grouped_event.group_bookings;
					}
				});
				var booking_html = bookings === 1 ? bookacti_localized.booking : bookacti_localized.bookings;
				avail_html = bookings + ' ' + booking_html;
			} else {
				var avail = availability === 1 ? bookacti_localized.avail : bookacti_localized.avails;
				avail_html = availability + ' ' + avail;
			}
			
			var group_label = {
				'html': bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'title' ]  + ' <span class="bookacti-group-availability" >(' + avail_html + ')</span>',
				'for': 'bookacti-group-of-events-' + group_id
			};
			
			// Allow third party to edit group labels
			booking_system.trigger( 'bookacti_choose_group_dialog_group_label', [ group_label, group_id, event ] );
			var label = $j( '<label></label>', group_label );
			
			// Build the group events list
			var event_list = $j( '<ul></ul>', {
				'class': 'bookacti-group-of-events-list bookacti-custom-scrollbar',
				'data-group-id': group_id
			});
			
			// Add events of the group to the list
			$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, grouped_event ) {
				var start_and_end_same_day = grouped_event.start.substr( 0, 10 ) === grouped_event.end.substr( 0, 10 );
				var grouped_event_start = moment.utc( grouped_event.start ).locale( bookacti_localized.current_lang_code );
				var grouped_event_end = moment.utc( grouped_event.end ).locale( bookacti_localized.current_lang_code );
				
				var grouped_event_duration = grouped_event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + grouped_event_end.formatPHP( bookacti_localized.date_format );
				if( start_and_end_same_day ) {
					grouped_event_duration = grouped_event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + grouped_event_end.formatPHP( bookacti_localized.time_format );
				}
				
				var list_element = $j( '<li></li>', {
					'html':	'<span class="bookacti-booking-event-duration" >'  + grouped_event_duration + '</span>' 
							+ '<span class="bookacti-booking-event-title-separator" > - </span>'  
							+ '<span class="bookacti-booking-event-title" >'  + grouped_event.title + '</span>'
				});
				
				event_list.append( list_element );
			});
			
			option_container.append( radio );
			option_container.append( label );
			container.append( option_container );
			container.append( event_list );
						
			groups_of_events_list.append( container );
		}
	});
	
	// Trigger a preview of the selection on change
	groups_of_events_list.find( 'input[name="group_of_events"]' ).on( 'change', function() { 
		var group_id = $j( this ).val();
		booking_system.trigger( 'bookacti_group_of_events_preview', [ group_id, event ] ); 
	});
	
	// Pick the first group by default and yell it
	groups_of_events_list.find( 'input[name="group_of_events"]:not([disabled]):first' ).prop( 'checked', true ).trigger( 'change' );
	
	
	// Open the modal dialog
    dialog.dialog( 'open' );
	
	// Make sure picked_events is emptied on close if no option has been selected
    dialog.dialog({
		close: function() {
			var selected_group = groups_of_events_list.find( 'input[type="radio"]:checked').val();
			// Empty the picked events if no group was choosen
			if( typeof selected_group === 'undefined' ) {
				bookacti_unpick_all_events( booking_system );
			}
		}
	});
	
    // Add the 'OK' button
    dialog.dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            click: function() {
				
				var group_id = groups_of_events_list.find( 'input[type="radio"]:checked' ).val();
				
				if( typeof group_id !== 'undefined' ) {
					// Pick events and fill form inputs
					bookacti_unpick_all_events( booking_system );
					bookacti_pick_events_of_group( booking_system, group_id, event );

					booking_system.trigger( 'bookacti_group_of_events_chosen', [ group_id, event ] );
				}
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}