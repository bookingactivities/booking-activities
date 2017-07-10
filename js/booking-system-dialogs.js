// INITIALIZATION
// Initialize bookings dialogs
function bookacti_init_booking_system_dialogs() {
	//Common param
	$j( '.bookacti-booking-system-dialog' ).dialog({ 
		modal:      true,
		autoOpen:   false,
		minHeight:  300,
		minWidth:   400,
		resize:		'auto',
		show:       true,
		hide:       true,
		closeText:  '&#10006;',
		close: function() {}
	});

	//Individual param
	$j( '#bookacti-choose-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_choose_group_of_events_title
	});
	
	// Show / Hide group events list
	$j( '#bookacti-groups-of-events-list' ).on( 'bookacti_group_of_events_preview', 'input[name="group_of_events"]', function( e, event ) {
		var group_id			= $j( this ).val();
		var booking_system_id	= $j( '#bookacti-groups-of-events-list' ).data( 'booking-system-id' );
		var booking_system		= $j( '#' + booking_system_id );
		
		// Hide other events list
		$j( '.bookacti-group-of-events-option[data-group-id!="' + group_id + '"]' ).data( 'show-events', 0 ).attr( 'data-show-events', 0 );
		$j( '.bookacti-group-of-events-list[data-group-id!="' + group_id + '"]' ).hide( 200 );
		
		// Show group events list
		$j( '.bookacti-group-of-events-option[data-group-id="' + group_id + '"]' ).data( 'show-events', 1 ).attr( 'data-show-events', 1 );
		$j( '.bookacti-group-of-events-list[data-group-id="' + group_id + '"]' ).show( 200 );
		
		// Pick events and fill group id input
		bookacti_unpick_all_events( booking_system );
		
		bookacti_pick_events_of_group( booking_system, group_id, event );
		booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( group_id );
	});
}


// Choose a group of events dialog
function bookacti_dialog_choose_group_of_events( booking_system, group_ids, event ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	var context = booking_system_id === 'bookacti-booking-system-bookings-page' ? 'booking_page' : 'frontend';
	
	$j( '#bookacti-groups-of-events-list' ).data( 'booking-system-id', booking_system_id );
	
	$j( '#bookacti-groups-of-events-list' ).empty();
	
	// Fill the dialog with the different choices
	
	// Add single event option if allowed
	if( bookacti.booking_system[ booking_system_id ][ 'groups_single_events' ] ) {
		var group_id = 'single';
		
		var container = $j( '<div />', {});
		var option_container = $j( '<div />', {
			class: 'bookacti-group-of-events-option',
			'data-group-id': group_id,
			'data-show-events': 0
		});
		var radio = $j( '<input />', {
			id: 'bookacti-group-of-events-' + group_id,
			type: 'radio',
			name: 'group_of_events',
			value: group_id
		});
		
		// Show availability or bookings
		var avail_html = '';
		if( context === 'booking_page' ) {
			var bookings = bookacti_get_bookings_number_for_a_single_grouped_event( event, group_ids, bookacti.booking_system[ booking_system_id ][ 'groups_events' ] );
			var booking_html = bookings > 1 ? bookacti_localized.bookings : bookacti_localized.booking;
			avail_html = bookings + ' ' + booking_html;
		} else {
			var availability	= bookacti_get_event_availability( event );
			var avail			= availability > 1 ? bookacti_localized.avails : bookacti_localized.avail;
			avail_html = availability + ' ' + avail;
		}
		
		var label = $j( '<label />', {
			html: bookacti_localized.single_event + ' <span class="bookacti-group-availability" >(' + avail_html + ')</span>',
			for: 'bookacti-group-of-events-' + group_id
		});

		var event_list = $j( '<ul />', {
			class: 'bookacti-group-of-events-list',
			'data-group-id': group_id
		});
		
		var start			= event.start instanceof moment ? event.start.format( 'YYYY-MM-DD HH:mm:ss' ) : event.start;
		var end				= event.end instanceof moment ?  event.end.format( 'YYYY-MM-DD HH:mm:ss' ) : event.end;
		var event_duration	=  bookacti_format_event_duration( start, end );

		var list_element = $j( '<li />', {
			html: '<span class="bookacti-booking-event-title" >'  + event.title + '</span>' 
				+ '<span class="bookacti-booking-event-title-separator" > - </span>' 
				+ event_duration
		});
		
		option_container.append( radio );
		option_container.append( label );
		container.append( option_container );
		
		event_list.append( list_element );
		container.append( event_list );
		
		$j( '#bookacti-groups-of-events-list' ).append( container );
	}
	
	// Add each available group of events as a radio option
	$j.each( group_ids, function( i, group_id ) {
		group_id = parseInt( group_id );
		if( typeof bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] !== 'undefined' ) {
			
			var availability	= bookacti_get_group_availability( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ] );
			
			var container = $j( '<div />', {});
			var option_container = $j( '<div />', {
				class: 'bookacti-group-of-events-option',
				'data-group-id': group_id,
				'data-show-events': 0
			});
			var radio = $j( '<input />', {
				id: 'bookacti-group-of-events-' + group_id,
				type: 'radio',
				name: 'group_of_events',
				'disabled': context !== 'booking_page' && availability <= 0,
				value: group_id
			});
			
			// Show availability or bookings
			var avail_html = '';
			if( context === 'booking_page' ) {
				var bookings = 0;
				$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, grouped_event ){
					if( event.id === grouped_event.id
					&&  event.start.format( 'YYYY-MM-DD HH:mm:ss' ) === grouped_event.start 
					&&  event.end.format( 'YYYY-MM-DD HH:mm:ss' ) === grouped_event.end ) {
						bookings = grouped_event.group_bookings;
					}
				});
				var booking_html = bookings > 1 ? bookacti_localized.bookings : bookacti_localized.booking;;
				avail_html = bookings + ' ' + booking_html;
			} else {
				var avail = availability > 1 ? bookacti_localized.avails : bookacti_localized.avail;
				avail_html = availability + ' ' + avail;
			}
			
			var group_label = {
				'html': bookacti.booking_system[ booking_system_id ][ 'groups_data' ][ group_id ][ 'title' ]  + ' <span class="bookacti-group-availability" >(' + avail_html + ')</span>',
				'for': 'bookacti-group-of-events-' + group_id
			};
			
			// Allow third party to edit group labels
			booking_system.trigger( 'bookacti_choose_group_dialog_group_label', [ group_label, group_id, event ] );
			var label = $j( '<label />', group_label );
			
			// Build the group events list
			var event_list = $j( '<ul />', {
				class: 'bookacti-group-of-events-list',
				'data-group-id': group_id
			});
			
			// Add events of the group to the list
			$j.each( bookacti.booking_system[ booking_system_id ][ 'groups_events' ][ group_id ], function( i, event ) {
				
				var start_and_end_same_day = event.start.substr( 0, 10 ) === event.end.substr( 0, 10 );
				
				var event_start = moment( event.start ).locale( bookacti_localized.current_lang_code );
				var event_end = moment( event.end ).locale( bookacti_localized.current_lang_code );
				
				var event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( bookacti_localized.date_format );
				if( start_and_end_same_day ) {
					
					event_duration = event_start.format( bookacti_localized.date_format ) + ' &rarr; ' + event_end.format( 'LT' );
				}
				
				var list_element = $j( '<li />', {
					html: event.title + ' - ' + event_duration
				});
				
				event_list.append( list_element );
			});
			
			option_container.append( radio );
			option_container.append( label );
			container.append( option_container );
			container.append( event_list );
						
			$j( '#bookacti-groups-of-events-list' ).append( container );
		}
	});
	
	// Trigger a preview of the selection on change
	$j( '#bookacti-groups-of-events-list input[name="group_of_events"]' ).on( 'change', function() { 
		$j( this ).trigger( 'bookacti_group_of_events_preview', [ event ] ); 
	});
	
	// Pick the first group by default and yell it
	$j( '#bookacti-groups-of-events-list input[name="group_of_events"]:not([disabled]):first' ).prop( 'checked', true ).trigger( 'change' );
	
	
	// Open the modal dialog
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog( 'open' );
	
	// Make sure picked_events is emptied on close if no option has been selected
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog({
		close: function() {
			var selected_group = $j('#bookacti-groups-of-events-list input[type="radio"]:checked').val();
			// Empty the picked events if no group was choosen
			if( typeof selected_group === 'undefined' ) {
				bookacti_unpick_all_events( booking_system );
			}
		}
	});
	
    // Add the 'OK' button
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            click: function() {
				
				var group_id = $j( '#bookacti-groups-of-events-list input[type="radio"]:checked' ).val();
				
				// Empty picked events
				bookacti_unpick_all_events( booking_system );

				// Pick events of the group
				bookacti_pick_events_of_group( booking_system, group_id, event );

				// Fill group id input
				booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( group_id );
				
				booking_system.trigger( 'bookacti_group_of_events_choosed', [ group_id, event ] );
				
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}