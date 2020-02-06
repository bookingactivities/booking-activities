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
		if( visible_activities.length && $j.inArray( activity_id, visible_activities ) === -1 ) {
			event.render = 0;
		}
		
		// Add the total availability
		if( typeof element !== 'undefined' ) {
			var availability = parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event.id ][ 'availability' ] );
			element.find( '.bookacti-availability-container .bookacti-available-places' ).append( ' / <span class="bookacti-total-places-number">' + availability + '</span>' );
		}
	});


// BOOKING LIST
	
	/**
	 * Apply some filters after the booking list calendar has set up
	 * @version 1.7.0
	 */
	booking_system.on( 'bookacti_after_calendar_set_up', function() { 
		// Apply date filter
		bookacti_refresh_calendar_according_to_date_filter();
	});

	// Load tooltip for booking actions retrieved via AJAX
	$j( '#bookacti-booking-list' ).on( 'bookacti_booking_list_filled bookacti_grouped_bookings_displayed', function(){
		bookacti_init_tooltip();
	});
	
	// Refresh the calendar when a booking has been reschedule
	$j( 'body' ).on( 'bookacti_booking_rescheduled', function(){
		bookacti_init_tooltip();
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_booking_method_refetch_events( booking_system );
		bookacti_refresh_booking_numbers( booking_system );
	});
	
	/**
	 * Refresh bookings number when a booking state or payment status has changed
	 * @version 1.7.10
	 */
	$j( 'body' ).on( 'bookacti_booking_state_changed bookacti_payment_status_changed', function( e, booking_id, booking_type, new_state, old_state, is_bookings_page, active_changed ){
		bookacti_init_tooltip();
		
		if( ! active_changed ) { return false; }
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_refresh_booking_numbers( booking_system );
	});
	
	
	/**
	 * Refresh bookings number when a booking is refunded or when its quantity changed
	 * @version 1.7.18
	 */
	$j( 'body' ).on( 'bookacti_booking_refunded bookacti_booking_quantity_changed', function(){
		bookacti_init_tooltip();
		
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		bookacti_refresh_booking_numbers( booking_system );
	});
});

