$j( document ).ready(function() {
	
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	
//DIALOGS
    //Init the Dialogs
    bookacti_init_bookings_dialogs();
    
    //Launch dialogs when...
    bookacti_bind_bookings_dialogs( booking_system );


// FILTERS
	// Init filters
	bookacti_init_booking_filters( booking_system );
	// Init booking actions
	bookacti_init_booking_actions();
	
	// Hide filtered events
	booking_system.on( 'bookacti_event_render', function( e, event, element ) { 
		
		element = element || undefined;
		
		//Check if the event is hidden
		if( hiddenActivities !== undefined && event.activity_id !== undefined ) {
			$j.each( hiddenActivities, function ( i, activity_id_to_hide ) {
				if( parseInt( event.activity_id ) === activity_id_to_hide ) {
					if( typeof element !== 'undefined' ) { element.addClass( 'event-exception' ); }
					event.render = 0;
				}
			});
		}
		
		if( typeof element !== 'undefined' ) {
			// Make all event available
			if( element.hasClass( 'event-unavailable' ) ) {
				element.removeClass( 'event-unavailable' );
			}
		
			// Replace the availability div with something more comfortable to see at a glance if there is a reservation
			element.find( '.bookacti-availability-container' ).remove();
			
			var active_bookings	= parseInt( event.bookings );
			var is_bookings		= active_bookings > 0 ? 1 : 0;
			var availability	= parseInt( event.availability );
			var available_places= bookacti_get_event_availability( event );
			
			//Detect if the event is available or full, and if it is booked or not
			var class_no_availability	= parseInt( event.availability ) === 0 ? 'bookacti-no-availability' : '';
			var class_booked			= is_bookings ? 'bookacti-booked' : 'bookacti-not-booked';
			var class_full				= available_places <= 0 ? 'bookacti-full' : '';

			//Build a div with availability
			var div = '<div class="bookacti-availability-container" >' 
						+ '<span class="bookacti-available-places ' + class_no_availability + ' ' + class_booked + ' ' + class_full + '" >'
							+ '<span class="bookacti-active-bookings-number">' + active_bookings + '</span> / ' 
							+ '<span class="bookacti-total-places-number">' + availability + '</span>' 
						+ '</span>'
					+ '</div>';
			
			element.append( div );
		}
	});

	
// BOOKING LIST
	// Show the booking list
	booking_system.on( 'bookacti_event_click', function( e, event ) { 
		
		var event_id	= event.id;
		var event_start	= event.start.format( 'YYYY-MM-DD[T]HH:mm:ss' );
		var event_end	= event.end.format( 'YYYY-MM-DD[T]HH:mm:ss' );
		
		bookacti_fill_booking_list( booking_system, event_id, event_start, event_end );
	});
	
	// Highlight a row in the booking list on click to help users if he has to scroll
	$j( '#bookacti-bookings-list tbody' ).on( 'click', 'tr', function() {
		$j( '#bookacti-bookings-list tbody tr' ).css( 'background-color', 'rgba( 255, 255, 255, 0 )' );
		$j( this ).css( 'background-color', 'rgba( 57, 134, 172, 0.15 )' );
	});
	
	
// LOADING
	// Lock filters and booking list while loading
	booking_system.on( 'bookacti_enter_loading_state', function() {
		bookacti_bookings_enter_loading_state( booking_system );
	});
	
	// Unlock filters and booking list after loading
	booking_system.on( 'bookacti_exit_loading_state', function() { 
		bookacti_bookings_exit_loading_state( booking_system );
	});
	
});

