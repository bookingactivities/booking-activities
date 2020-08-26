$j( document ).ready( function() {
	var booking_system = $j( '#bookacti-booking-system-bookings-page' );
	var booking_system_id = booking_system.attr( 'id' );
	if( ! booking_system.length ) { return false; }
	
// FILTERS

	/**
	 * Display or hide activities filter according to selected templates - on page load
	 */
	bookacti_update_template_related_filters();
	
	
	/**
	 * Display or hide activities filter according to selected templates - on change
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-filter-templates, #bookacti-booking-filter-status, #bookacti-booking-filter-customer' ).on( 'change', function() {
		// Show / Hide activities filter
		bookacti_update_template_related_filters();
		
		// Reload events according to filters
		if( $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
			var booking_system = $j( '#bookacti-booking-system-bookings-page' );
			bookacti_reload_booking_system_according_to_filters( booking_system );
		}
		
		// Filter the booking list according to filters
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 * Display / Hide the calendar and reload it if the filters has been changed
	 * @version 1.8.0
	 */
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
			
			var button_label = $j( '#bookacti-pick-event-filter' ).data( 'label-hide' );
			$j( '#bookacti-pick-event-filter' ).text( button_label ).attr( 'title', button_label );
			$j( '#bookacti-pick-event-filter-instruction' ).show( 200 );
		} else {
			var button_label = $j( '#bookacti-pick-event-filter' ).data( 'label-show' );
			$j( '#bookacti-pick-event-filter' ).text( button_label ).attr( 'title', button_label );
			$j( '#bookacti-pick-event-filter-instruction' ).hide( 200 );
		}
		
		// Show / Hide calendar
		$j( '#bookacti-booking-system-filter-container' ).toggle( 200 );
	});
	
	
	/**
	 * Unpick all events on bookings calendar
	 * @version 1.8.0
	 */
	$j( '#bookacti-unpick-events-filter' ).on( 'click', function() {
		bookacti_unpick_all_events_filter();
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 * Display the "unpick events" button
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-system-bookings-page' ).on( 'bookacti_event_click', function( e, event, group_id, open_dialog ) { 
		$j( '#bookacti-pick-event-filter-instruction' ).hide( 200 );
		$j( '#bookacti-unpick-events-filter' ).show( 200 );
		$j( '#bookacti-picked-events-actions-container' ).show( 200 );
	});
	
	
	/**
	 * Filter the booking list when an event is picked
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-system-bookings-page' ).on( 'bookacti_event_click', function( e, event, group_id, open_dialog ) {
		if( group_id === 'single' || ! open_dialog ) {
			if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
		}
	});
	
	
	/**
	 * Filter the booking list when a group of events is picked
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-system-bookings-page' ).on( 'bookacti_group_of_events_chosen', function( e, group_id, event ) {
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 * Display / Hide activities on the bookings calendar
	 * @version 1.8.0
	 */
	$j( '#bookacti-booking-filter-activities' ).on( 'change', function() {
		bookacti_unpick_all_events_filter();
		var booking_system	= $j( '#bookacti-booking-system-bookings-page' );
		var calendar		= booking_system.find( '.bookacti-calendar' );
		calendar.fullCalendar( 'rerenderEvents' );
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 *  Retrict calendars date according to date filter
	 *  @version 1.8.0
	 */
	$j( '#bookacti-booking-filter-dates-from, #bookacti-booking-filter-dates-to' ).on( 'change', function() {
		bookacti_unpick_all_events_filter();
		bookacti_refresh_calendar_according_to_date_filter();
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 * Hide filtered events
	 */
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
	
	
	/**
	 * Open the bookings page calendar settings dialog
	 * @since 1.8.0
	 */
	$j( '#bookacti-bookings-calendar-settings' ).on( 'click', function() {
		bookacti_dialog_update_bookings_calendar_settings();
	});
	
	
	/**
	 * Bookings page calendar settings: Toggle tooltip options - on change
	 * @since 1.8.0
	 */
	$j( '#bookacti-bookings-calendar-settings-dialog' ).on( 'change', '#bookacti-tooltip_booking_list', function() { 
		if( $j( this ).is( ':checked' ) ) { 
			$j( '#bookacti-event-booking-list-columns-container' ).show();
		} else {
			$j( '#bookacti-event-booking-list-columns-container' ).hide();
		}
	});
	
	
	/**
	 * Filter the booking list according to filters
	 * @since 1.8.0
	 */
	$j( '#bookacti-booking-list-filters-form' ).on( 'submit', function( e ) {
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { 
			e.preventDefault();
			bookacti_filter_booking_list();
		}
	});


// BOOKING LIST
	
	/**
	 * Refresh booking groups frames - On page load
	 * @version 1.8.6
	 */
	bookacti_refresh_booking_group_frame();
	
	/**
	 * Load tooltip for booking actions retrieved via AJAX and Refresh booking groups frames
	 * @version 1.8.6
	 */
	$j( '#bookacti-booking-list' ).on( 'bookacti_booking_list_filtered bookacti_grouped_bookings_displayed', function(){
		bookacti_init_tooltip();
		bookacti_refresh_booking_group_frame();
	});
	
	
	/**
	 * Refresh booking groups frames - On delete booking
	 * @since 1.8.6
	 */
	$j( 'body' ).on( 'bookacti_booking_deleted', function() {
		bookacti_refresh_booking_group_frame();
	});
	
	
	/**
	 * Refresh the calendar when a booking has been reschedule
	 */
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
	
	
	/**
	 * WP List Table pagination - go to a specific page
	 * @version 1.8.0
	 */
	$j( 'body' ).on( 'submit', '.bookacti-list-table-go-to-page-form', function( e ){
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		e.preventDefault();
		var paged = $j( this ).find( '.current-page' ).val();
		bookacti_filter_booking_list( paged );
	});
	
	
	/**
	 * WP List Table pagination - go to prev, next, first or last page
	 * @version 1.8.0
	 */
	$j( 'body' ).on( 'click', '.first-page, .prev-page, .next-page, .last-page', function( e ){
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		e.preventDefault();
		var href = $j( this ).attr( 'href' );
		var paged_index = href.indexOf( 'paged=' );
		var paged = paged_index !== -1 ? href.substr( paged_index + 6 ) : 1;
		bookacti_filter_booking_list( paged );
	});
});

