$j( document ).ready( function() {
	
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
		
		// Add the total availability
		if( typeof element !== 'undefined' ) {
			var availability = parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ] );
			element.find( '.bookacti-availability-container .bookacti-available-places' ).append( ' / <span class="bookacti-total-places-number">' + availability + '</span>' );
		}
	});


// BOOKING LIST
	
	// Apply some filters after the calendar has set up
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
	$j( '#bookacti-bookings-list' ).on( 'bookacti_booking_list_filled bookacti_grouped_bookings_displayed', function(){
		bookacti_init_tooltip();
	});
	
	// Refresh the calendar when a booking has been reschedule
	$j( 'body' ).on( 'bookacti_booking_rescheduled', function(){
		bookacti_init_tooltip();
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_booking_method_refetch_events( booking_system );
		bookacti_refresh_booking_numbers( booking_system );
	});
	
	// Refresh bookings number when a booking state has changed from active to inactive and vice versa
	$j( 'body' ).on( 'bookacti_booking_state_changed', function( e, booking_id, booking_type, new_state, old_state, is_bookings_page, active_changed ){
		bookacti_init_tooltip();
		
		if( ! active_changed ) { return false; }
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_refresh_booking_numbers( booking_system );
	});
	
	// Refresh bookings number when a booking is refunded
	$j( 'body' ).on( 'bookacti_booking_refunded', function(){
		bookacti_init_tooltip();
		
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_refresh_booking_numbers( booking_system );
	});
});

