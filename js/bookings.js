$j( document ).ready(function() {
	
	var booking_system		= $j( '#bookacti-booking-system-bookings-page' );
	var booking_system_id	= booking_system.attr( 'id' );
	
	if( ! booking_system.length ) { return false; }
	
// FILTERS
	// Init filter actions
	bookacti_init_booking_filters_actions();

	// Hide filtered events
	booking_system.on( 'bookacti_event_render', function( e, event, element, view ) { 

		element = element || undefined;

		// Check if the event is hidden
		var activity_id			= bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'activity_id' ];
		var visible_activities	= $j( '#bookacti-booking-filter-activities' ).val() ? $j( '#bookacti-booking-filter-activities' ).val() : [];
		if( visible_activities.length && $j.inArray( activity_id, visible_activities ) < 0 ) {
			event.render = 0;
		}

		if( typeof element !== 'undefined' ) {
			// Make all event available
			if( element.hasClass( 'bookacti-event-unavailable' ) ) {
				element.removeClass( 'bookacti-event-unavailable' );
			}

			// Replace the availability div with something more comfortable to see at a glance if there is a reservation
			element.find( '.bookacti-availability-container' ).remove();

			var active_bookings	= parseInt( event.bookings );
			var is_bookings		= active_bookings > 0 ? 1 : 0;
			var availability	= parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ] );
			var available_places= bookacti_get_event_availability( booking_system, event );

			// Detect if the event is available or full, and if it is booked or not
			var class_no_availability	= availability === 0 ? 'bookacti-no-availability' : '';
			var class_booked			= is_bookings ? 'bookacti-booked' : 'bookacti-not-booked';
			var class_full				= available_places <= 0 ? 'bookacti-full' : '';

			// Build a div with availability
			var avail_div	= '<div class="bookacti-availability-container" >' 
								+ '<span class="bookacti-available-places ' + class_no_availability + ' ' + class_booked + ' ' + class_full + '" >'
									+ '<span class="bookacti-active-bookings-number">' + active_bookings + '</span> / ' 
									+ '<span class="bookacti-total-places-number">' + availability + '</span>' 
								+ '</span>'
							+ '</div>';

			element.append( avail_div );
		}
	});


// DIALOGS
	// Init the Dialogs
	bookacti_init_bookings_dialogs();

	// Init booking actions
	bookacti_init_booking_actions();


// BOOKING LIST
	// Show the booking list
//	booking_system.on( 'bookacti_event_click', function( e, event, group_id ) { 
//		if( group_id === 'single' ) {
//			bookacti_fill_booking_list( booking_system, event );
//		}
//	});
//
//	// Show the booking group list
//	booking_system.on( 'bookacti_group_of_events_chosen', function( e, group_id, event ) { 
//		if( group_id === 'single' || $j.isNumeric( group_id ) ) {
//			bookacti_fill_booking_list( booking_system, event,group_id );
//		}
//	});
	
	// Apply some filters after the caladar has set up
	booking_system.on( 'bookacti_after_calendar_set_up', function() { 
		var calendar = booking_system.find( '.bookacti-calendar' );
		
		// Fill default inputs, if defualt values are availables
		var default_inputs = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'default_inputs' ];
		if( ! $j.isEmptyObject( default_inputs ) ) {
			if( default_inputs.group_id == 0 ) { default_inputs.group_id = 'single'; }
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_group_id"]' ).val( default_inputs.group_id );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_id"]' ).val( default_inputs.id );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_start"]' ).val( default_inputs.start );
			booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input[name="bookacti_event_end"]' ).val( default_inputs.end );
			delete bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'default_inputs' ];
		}
		
		// Go to the first picked events
		var picked_events = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'picked_events' ];
		if( ! $j.isEmptyObject( bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'picked_events' ] ) ) {
			calendar.fullCalendar( 'gotoDate', moment( picked_events[ 0 ][ 'start' ] ) );
		}
		
		// Apply date filter
		bookacti_refresh_calendar_according_to_date_filter( calendar );
	});

	// Load tooltip for booking actions retrieved via AJAX
	$j( '#bookacti-bookings-list' ).on( 'bookacti_booking_list_filled', function(){
		bookacti_init_tooltip();
	});
});

