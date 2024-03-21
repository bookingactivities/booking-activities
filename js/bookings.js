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
	 * Do not init bookings booking system automatically if it is hidden
	 * @since 1.15.0
	 * @param {Event} e
	 * @param {Object} load
	 * @param {Object} attributes
	 */
	$j( 'body' ).on( 'bookacti_init_booking_sytem', '.bookacti-booking-system#bookacti-booking-system-bookings-page', function( e, load, attributes ) {
		if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) { load.load = false; }
	});
	
	
	/**
	 * Display or hide activities filter according to selected templates - on change
	 * @version 1.15.9
	 */
	$j( '#bookacti-booking-filter-templates, #bookacti-booking-filter-status, #bookacti-booking-filter-customer' ).on( 'change', function() {
		// Show / Hide activities filter
		bookacti_update_template_related_filters();
		
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		
		// Reload events according to filters
		if( $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
			var booking_system = $j( '#bookacti-booking-system-bookings-page' );
			bookacti_reload_booking_system_according_to_filters( booking_system );
		}

		// Filter the booking list according to filters
		bookacti_filter_booking_list();
	});
	
	
	/**
	 * Display / Hide the calendar and reload it if the filters has been changed
	 * @version 1.16.0
	 */
	$j( '#bookacti-pick-event-filter' ).on( 'click', function() {
		var booking_system = $j( '#bookacti-booking-system-bookings-page' );
		
		// Reload events according to filters if they have changed
		if( ! $j( '#bookacti-booking-system-filter-container' ).is( ':visible' ) ) {
			var booking_system_id  = booking_system.attr( 'id' );
			var selected_templates = $j( '#bookacti-booking-filter-templates' ).val() ? $j( '#bookacti-booking-filter-templates' ).val() : [];
			var selected_status    = $j( '#bookacti-booking-filter-status' ).val() ? $j( '#bookacti-booking-filter-status' ).val() : [];
			var selected_user      = $j( '#bookacti-booking-filter-customer' ).val() ? [ $j( '#bookacti-booking-filter-customer' ).val() ] : [];
			
			if( ! bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'calendars' ], selected_templates )
			||  ! bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'status' ], selected_status )
			||  ! bookacti_compare_arrays( bookacti.booking_system[ booking_system_id ][ 'user_id' ], selected_user ) ) {
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
	 * Filter the booking list when an event is picked
	 * @version 1.15.7
	 * @param {Event} e
	 * @param {(FullCalendar.EventApi|Object)} event
	 * @param {Object} groups
	 * @param {Boolean} open_dialog
	 */
	$j( '#bookacti-booking-system-bookings-page' ).on( 'bookacti_event_click', function( e, event, groups, open_dialog ) { 
		// Display the "unpick events" button
		$j( '#bookacti-pick-event-filter-instruction' ).hide( 200 );
		$j( '#bookacti-unpick-events-filter' ).show( 200 );
		$j( '#bookacti-picked-events-actions-container' ).show( 200 );
		
		// Filter the booking list when an event is picked
		if( $j.isEmptyObject( groups ) || ! open_dialog ) {
			if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
		}
	});
	
	
	/**
	 * Filter the booking list when a group of events is picked
	 * @version 1.12.0
	 * @param {Event} e
	 * @param {Int} group_id
	 * @param {String} group_date
	 * @param {(FullCalendar.EventApi|Object)} event
	 */
	$j( '#bookacti-booking-system-bookings-page' ).on( 'bookacti_group_of_events_chosen', function( e, group_id, group_date, event ) {
		if( $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { bookacti_filter_booking_list(); }
	});
	
	
	/**
	 * Display / Hide activities on the bookings calendar
	 * @version 1.15.0
	 */
	$j( '#bookacti-booking-filter-activities' ).on( 'change', function() {
		bookacti_unpick_all_events_filter();
		bookacti_booking_method_rerender_events( $j( '#bookacti-booking-system-bookings-page' ) );
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
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {Object} return_object {
	 *  @type {Array} class_names
	 * }
	 * @param {Object} info {
	 *  @type {FullCalendar.EventApi} event
	 *  @type {String} timeText
	 *  @type {Boolean} isStart
	 *  @type {Boolean} isEnd
	 *  @type {Boolean} isMirror
	 *  @type {Boolean} isPast
	 *  @type {Boolean} isFuture
	 *  @type {Boolean} isToday
	 *  @type {HTMLElement} el
	 *  @type {FullCalendar.ViewApi} view The current View Object.
	 * }
	 */
	booking_system.on( 'bookacti_calendar_event_class_names', function( e, return_object, info ) {
		// Check if the event is hidden
		var event_id           = typeof info.event.groupId !== 'undefined' ? parseInt( info.event.groupId ) : parseInt( info.event.id );
		var activity_id        = bookacti.booking_system[ booking_system_id ][ 'events_data' ][ event_id ][ 'activity_id' ];
		var visible_activities = $j( '#bookacti-booking-filter-activities' ).val() ? $j( '#bookacti-booking-filter-activities' ).val() : [];
		if( $j.isNumeric( visible_activities ) ) { visible_activities = [ visible_activities ]; }
		
		// Hide events according to the Activities filter values
		if( visible_activities ) { 
			if( $j.isArray( visible_activities ) ) {
				if( visible_activities.length && $j.inArray( activity_id + '', visible_activities ) === -1 ) { 
					return_object.class_names.push( 'bookacti-event-hidden' );
				}
			}
		}
	});
	
	
	/**
	 * Add total availability to availability div in Bookings calendar events
	 * @version 1.15.0
	 * @param {Event} e
	 * @param {Object} return_object {
	 *  @type {Array} domNodes
	 * }
	 * @param {Object} info {
	 *  @type {FullCalendar.EventApi} event
	 *  @type {String} timeText
	 *  @type {Boolean} isStart
	 *  @type {Boolean} isEnd
	 *  @type {Boolean} isMirror
	 *  @type {Boolean} isPast
	 *  @type {Boolean} isFuture
	 *  @type {Boolean} isToday
	 *  @type {HTMLElement} el
	 *  @type {FullCalendar.ViewApi} view The current View Object.
	 * }
	 */
	booking_system.on( 'bookacti_calendar_event_content', function( e, return_object, info ) { 
		// Find the availability div
		var avail_div_i = -1;
		for( var i = 0; i < return_object.domNodes.length; i++ ) {
			if( return_object.domNodes[ i ].classList.contains( 'bookacti-availability-container' ) ) {
				avail_div_i = i;
				break;
			}
		}
		if( avail_div_i < 0 ) { return; }
		
		var availability  = parseInt( bookacti.booking_system[ booking_system_id ][ 'events_data' ][ info.event.groupId ][ 'availability' ] );
		var new_avail_div = $j( return_object.domNodes[ avail_div_i ] );
		new_avail_div.find( '.bookacti-available-places' ).append( ' / <span class="bookacti-total-places-number">' + availability + '</span>' );
		
		return_object.domNodes[ avail_div_i ] = new_avail_div[ 0 ];
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
	 * @version 1.15.9
	 * @param {Event} e
	 */
	$j( '#bookacti-booking-list-filters-form' ).on( 'submit', function( e ) {
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		e.preventDefault();
		bookacti_filter_booking_list();
	});


// BOOKING LIST
	
	/**
	 * Refresh booking groups frames - On page load
	 * @version 1.8.6
	 */
	bookacti_refresh_booking_group_frame();
	
	
	/**
	 * WP List Table pagination - go to a specific page
	 * @version 1.8.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'submit', '.bookacti-list-table-go-to-page-form', function( e ){
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		e.preventDefault();
		var paged = $j( this ).find( '.current-page' ).val();
		bookacti_filter_booking_list( paged );
	});
	
	
	/**
	 * WP List Table pagination - go to prev, next, first or last page
	 * @version 1.8.9
	 * @param {Event} e
	 */
	$j( '#bookacti-bookings-container' ).on( 'click', '.first-page, .prev-page, .next-page, .last-page', function( e ){
		if( ! $j( '#bookacti-submit-filter-button' ).data( 'ajax' ) ) { return; }
		e.preventDefault();
		var href = $j( this ).attr( 'href' );
		var paged_index = href.indexOf( 'paged=' );
		var paged = paged_index !== -1 ? href.substr( paged_index + 6 ) : 1;
		bookacti_filter_booking_list( paged );
	});
	
	
	/**
	 * Show the number of selected elements - on change
	 * @since 1.16.0
	 */
	$j( 'body' ).on( 'change', '.bookacti-list-table .check-column input', function() {
		$j( '#bookacti-bookings-container .bookacti-select-all-container' ).remove();
		$j( '#bookacti-all-selected' ).val( 0 );
		
		var nb_selected = '<span class="bookacti-nb-selected">' + bookacti_localized.nb_selected.replace( '{nb}', $j( '#bookacti-bookings-container tbody .check-column input:checked' ).length ) + '</span>';
		var select_all  = '<button class="bookacti-select-all button">' + bookacti_localized.select_all.replace( '{nb}', $j( '#bookacti-bookings-container .displaying-num' ).first().text() ) + '</button>';
		
		$j( '#bookacti-bookings-container .tablenav .bulkactions' ).append( '<span class="bookacti-select-all-container">' + nb_selected + select_all + '</span>' );
	});
	
	
	/**
	 * Select all items of a WP_List_Table according to filters, even those not displayed
	 * @since 1.16.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '#bookacti-booking-list .bookacti-select-all', function( e ) {
		e.preventDefault();
		$j( '#bookacti-bookings-container thead .check-column input[type="checkbox"]' ).prop( 'checked', false ).trigger( 'click.wp-toggle-checkboxes' );
		var nb_selected  = '<span class="bookacti-nb-selected">' + bookacti_localized.nb_selected.replace( '{nb}', $j( '#bookacti-bookings-container .displaying-num' ).first().text() ) + '</span>';
		var unselect_all = '<button class="bookacti-unselect-all button">' + bookacti_localized.unselect_all + '</button>';
		$j( '#bookacti-bookings-container .tablenav .bookacti-nb-selected' ).replaceWith( nb_selected );
		$j( '#bookacti-bookings-container .tablenav .bookacti-select-all' ).replaceWith( unselect_all );
		$j( '#bookacti-all-selected' ).val( 1 );
	});
	
	
	/**
	 * Unselect all items of a WP_List_Table according to filters
	 * @since 1.16.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '#bookacti-booking-list .bookacti-unselect-all', function( e ) {
		e.preventDefault();
		$j( '#bookacti-bookings-container thead .check-column input[type="checkbox"]' ).prop( 'checked', true ).trigger( 'click.wp-toggle-checkboxes' );
		$j( '#bookacti-bookings-container .tablenav .bookacti-select-all-container' ).remove();
		$j( '#bookacti-all-selected' ).val( 0 );
	});
});