// INITIALIZATION

/**
 * Choose a group of events dialog
 * @version 1.16.39
 * @param {HTMLElement} booking_system
 * @param {Object} groups
 * @param {(FullCalendar.EventApi|Object)} event
 */
function bookacti_dialog_choose_group_of_events( booking_system, groups, event ) {
	var booking_system_id     = booking_system.attr( 'id' );
	var dialog                = $j( '#' + booking_system_id + '-choose-group-of-events-dialog' );
	var groups_of_events_list = $j( '#' + booking_system_id + '-groups-of-events-list' );

	var attributes           = bookacti.booking_system[ booking_system_id ];
	var multiple_bookings    = attributes[ 'multiple_bookings' ];
	var bookings_only        = attributes[ 'bookings_only' ];
	var past_events          = attributes[ 'past_events' ];
	var past_events_bookable = attributes[ 'past_events_bookable' ];
	var current_time         = moment.utc( bookacti_localized.current_time );

	groups_of_events_list.data( 'booking-system-id', booking_system_id );

	groups_of_events_list.empty();

	// Fill the dialog with the different choices
	
	// Add single event option if allowed
	if( attributes[ 'groups_single_events' ] ) {
		// Check event availability
		var availability = bookacti_get_event_availability( booking_system, event );
		var is_available = availability > 0;
		if( typeof event.is_available !== 'undefined' )  { if( ! event.is_available ) { is_available = false; } }
		if( typeof event.extendedProps !== 'undefined' ) { if( typeof event.extendedProps.is_available !== 'undefined' ) { if( ! event.extendedProps.is_available ) { is_available = false; } } }
		
		// Check if the event is past
		if( past_events ) {
			var event_start = moment.utc( event.start ).clone();
			var event_end   = moment.utc( event.end ).clone();
			if( ! past_events_bookable && event_start.isBefore( current_time ) 
			&& ! ( bookacti_localized.started_events_bookable && event_end.isAfter( current_time ) ) ) {
				is_available = false;
			}
		}
		
		var event_id = typeof event.groupId !== 'undefined' ? parseInt( event.groupId ) : parseInt( event.id );
		if( is_available && typeof attributes[ 'events_data' ][ event_id ] !== 'undefined' ) {
			// Check the min quantity required
			is_available      = false;
			var min_qty_ok    = false;
			var activity_id   = parseInt( attributes[ 'events_data' ][ event_id ][ 'activity_id' ] );
			var activity_data = attributes[ 'activities_data' ][ activity_id ][ 'settings' ];
			var min_quantity  = typeof activity_data[ 'min_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'min_bookings_per_user' ] ? parseInt( activity_data[ 'min_bookings_per_user' ] ) : 0 );
			if( min_quantity <= availability ) { min_qty_ok = true; }

			// Check the max quantity allowed AND
			// Check the max number of different users allowed
			var max_qty_ok = max_users_ok = true;
			var max_quantity = typeof activity_data[ 'max_bookings_per_user' ] === 'undefined' ? 0 : ( activity_data[ 'max_bookings_per_user' ] ? parseInt( activity_data[ 'max_bookings_per_user' ] ) : 0 );
			var max_users    = typeof activity_data[ 'max_users_per_event' ] === 'undefined' ? 0 : ( activity_data[ 'max_users_per_event' ] ? parseInt( activity_data[ 'max_users_per_event' ] ) : 0 );

			if( max_quantity || max_users ) {
				var event_start_formatted = moment.utc( event.start ).clone().locale( 'en' ).format( 'YYYY-MM-DD HH:mm:ss' );
				if( typeof attributes[ 'bookings' ][ event_id ] !== 'undefined' ) {
					if( typeof attributes[ 'bookings' ][ event_id ][ event_start_formatted ] !== 'undefined' ) {
						var occurrence = attributes[ 'bookings' ][ event_id ][ event_start_formatted ];
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
			'id': 'bookacti-group-of-events-option-single',
			'class': 'bookacti-group-of-events-option',
			'data-group-id': 0,
			'data-group-date': '',
			'data-show-events': 0
		});
		var radio = $j( '<input />', {
			'id': 'bookacti-group-of-events-single',
			'type': 'radio',
			'name': 'group_of_events',
			'value': 'single',
			'disabled': ! bookings_only && ! is_available
		});
		
		// Show availability or bookings
		var bookings_number  = bookacti_get_bookings_number_for_a_single_grouped_event( booking_system, event, groups );
		var available_places = ! is_available && availability > 0 && bookacti_localized.not_bookable !== '{current}' ? 0 : availability;
		
		// Maybe hide availability
		var hide_availability_class = '';
		var total_availability = bookacti.booking_system[ booking_system_id ][ 'events_data' ]?.[ event_id ]?.[ 'availability' ];
		if( ! total_availability ) { total_availability = 0; }
		var percent_remaining = total_availability ? parseInt( ( availability / total_availability ) * 100 ) : 0;
		var percent_threshold = parseInt( bookacti.booking_system[ booking_system_id ][ 'hide_availability' ] );
		var fixed_threshold   = parseInt( bookacti_localized.hide_availability_fixed );
		var hide_percent      = percent_threshold < 100 && percent_remaining > percent_threshold;
		var hide_fixed        = fixed_threshold > 0 && availability > fixed_threshold;

		if( ! bookings_only ) {
			if( ( fixed_threshold <= 0 && hide_percent ) || ( percent_threshold >= 100 && hide_fixed ) || ( hide_percent && hide_fixed ) ) {
				available_places        = '';
				hide_availability_class = 'bookacti-hide-availability';
			}
		}
		
		// Add CSS class according to the number of bookings and the availability
		var availability_classes = '';
		availability_classes += bookings_number > 0 ? ' bookacti-booked' : ' bookacti-not-booked';
		availability_classes += availability <= 0 ? ' bookacti-full' : '';
		availability_classes += ! is_available && availability > 0 ? ' bookacti-not-bookable' : '';

		var avail_div  = $j( '<div></div>', { 'class': 'bookacti-group-availability-container ' + hide_availability_class } );
		var places_div = $j( '<div></div>', { 'class': 'bookacti-available-places ' + availability_classes } );
		
		if( bookings_only ) {
			var bookings_particle = bookings_number === 1 ? bookacti_localized.booking : bookacti_localized.bookings;
			
			var nb_span       = $j( '<span></span>', { 'class': 'bookacti-active-bookings-number', 'html': bookings_number } );
			var particle_span = $j( '<span></span>', { 'class': 'bookacti-available-places-avail-particle', 'html': bookings_particle } );

			places_div.append( nb_span );
			places_div.append( particle_span );
			avail_div.append( places_div );

			booking_system.trigger( 'bookacti_choose_group_dialog_group_number_of_bookings_div', [ avail_div, 0, '', event, bookings_number, availability, is_available ] );
			
		} else {
			var avail_particle = availability === 1 ? bookacti_localized.avail : bookacti_localized.avails;
			if( ! is_available && availability > 0 && bookacti_localized.not_bookable && bookacti_localized.not_bookable !== '{current}' ) {
				available_places = '';
				avail_particle   = bookacti_localized.not_bookable;
			}
			
			var nb_span       = $j( '<span></span>', { 'class': 'bookacti-available-places-number', 'html': available_places } );
			var particle_span = $j( '<span></span>', { 'class': 'bookacti-available-places-avail-particle', 'html': avail_particle } );

			places_div.append( nb_span );
			places_div.append( particle_span );
			avail_div.append( places_div );

			booking_system.trigger( 'bookacti_choose_group_dialog_group_availability_div', [ avail_div, 0, '', event, availability, is_available ] );
		}
		
		var single_label = {
			'html': bookacti_localized.single_event + avail_div[0].outerHTML,
			'for': 'bookacti-group-of-events-single'
		};

		// Allow third party to edit single label
		booking_system.trigger( 'bookacti_choose_group_dialog_group_label', [ single_label, 0, '', event, is_available ] );
		var label = $j( '<label></label>', single_label );

		var event_list = $j( '<ul></ul>', {
			'id': 'bookacti-group-of-events-list-single',
			'class': 'bookacti-group-of-events-list bookacti-custom-scrollbar',
			'data-group-id': 0,
			'data-group-date': ''
		});

		var event_duration = bookacti_format_event_duration( event.start, event.end );

		var event_data = {
			'title':    typeof event.title !== 'undefined' ? event.title : '',
			'duration': event_duration,
			'quantity': 1
		};

		booking_system.trigger( 'bookacti_group_of_events_list_data', [ event_data, event, is_available ] );

		var list_element_data = {
			'html': '<span class="bookacti-booking-event-duration" >'  + event_data.duration + '</span>' 
			+ '<span class="bookacti-booking-event-title-separator" > - </span>' 
			+ '<span class="bookacti-booking-event-title" >'  + event_data.title + '</span>' 
		};

		booking_system.trigger( 'bookacti_group_of_events_list_element_data', [ list_element_data, event, is_available ] );

		var list_element = $j( '<li></li>', list_element_data );

		option_container.append( radio );
		option_container.append( label );
		container.append( option_container );

		event_list.append( list_element );
		container.append( event_list );

		groups_of_events_list.append( container );
	}

	// Add each available group of events as a radio option
	$j.each( groups, function( group_id, groups_per_date ) {
		if( typeof attributes[ 'groups_data' ][ group_id ] === 'undefined' ) { return true; } // Skip
		
		$j.each( groups_per_date, function( group_date, group_events ) {
			// Get group bookings data
			var is_group_available, group_availability, group_bookings, current_user_bookings, distinct_users, total_availability;
			is_group_available = group_availability = group_bookings = current_user_bookings = distinct_users = total_availability = 0;
			if( typeof attributes[ 'groups_bookings' ][ group_id ] !== 'undefined' ) {
				if( typeof attributes[ 'groups_bookings' ][ group_id ][ group_date ] !== 'undefined' ) {
					is_group_available    = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'is_available' ];
					group_availability    = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'availability' ];
					group_bookings        = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'quantity' ];
					current_user_bookings = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'current_user_bookings' ];
					distinct_users        = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'distinct_users' ];
					total_availability    = attributes[ 'groups_bookings' ][ group_id ][ group_date ]?.[ 'total_availability' ];
				}
			}
			
			var is_available = bookacti_is_group_of_events_available( booking_system, group_id, group_date );

			var container = $j( '<div></div>', {} );
			var option_container = $j( '<div></div>', {
				'id': 'bookacti-group-of-events-option-' + group_id + '_' + group_date,
				'class': 'bookacti-group-of-events-option' + ( ! bookings_only && ! is_available ? ' bookacti-group-of-events-unavailable' : '' ),
				'data-group-id': group_id,
				'data-group-date': group_date,
				'data-show-events': 0
			});
			var radio = $j( '<input />', {
				'id': 'bookacti-group-of-events-' + group_id + '_' + group_date,
				'type': 'radio',
				'name': 'group_of_events',
				'disabled': ! bookings_only && ! is_available,
				'value': group_id + '_' + group_date
			});

			// Show availability or bookings
			var available_places = ! is_available && group_availability > 0 && bookacti_localized.not_bookable !== '{current}' ? 0 : group_availability;
			var bookings_number  = group_bookings;
			
			// Maybe hide availability
			var hide_availability_class = '';
			var percent_remaining = total_availability ? parseInt( ( group_availability / total_availability ) * 100 ) : 0;
			var percent_threshold = parseInt( bookacti.booking_system[ booking_system_id ][ 'hide_availability' ] );
			var fixed_threshold   = parseInt( bookacti_localized.hide_availability_fixed );
			var hide_percent      = percent_threshold < 100 && percent_remaining > percent_threshold;
			var hide_fixed        = fixed_threshold > 0 && group_availability > fixed_threshold;

			if( ! bookings_only ) {
				if( ( fixed_threshold <= 0 && hide_percent ) || ( percent_threshold >= 100 && hide_fixed ) || ( hide_percent && hide_fixed ) ) {
					available_places        = '';
					hide_availability_class = 'bookacti-hide-availability';
				}
			}
			
			// Add CSS class according to the number of bookings and the availability
			var availability_classes = '';
			availability_classes += group_bookings > 0 ? ' bookacti-booked' : ' bookacti-not-booked';
			availability_classes += group_availability <= 0 ? ' bookacti-full' : '';
			availability_classes += ! is_available && group_availability > 0 ? ' bookacti-not-bookable' : '';
			
			var avail_div  = $j( '<div></div>', { 'class': 'bookacti-group-availability-container ' + hide_availability_class } );
			var places_div = $j( '<div></div>', { 'class': 'bookacti-available-places ' + availability_classes } );
			
			if( bookings_only ) {
				var bookings_particle = group_bookings === 1 ? bookacti_localized.booking : bookacti_localized.bookings;

				var nb_span       = $j( '<span></span>', { 'class': 'bookacti-active-bookings-number', 'html': bookings_number } );
				var particle_span = $j( '<span></span>', { 'class': 'bookacti-available-places-avail-particle', 'html': bookings_particle } );

				places_div.append( nb_span );
				places_div.append( particle_span );
				avail_div.append( places_div );

				booking_system.trigger( 'bookacti_choose_group_dialog_group_number_of_bookings_div', [ avail_div, group_id, group_date, event, bookings_number, group_availability, is_available ] );

			} else {
				var avail_particle = group_availability === 1 ? bookacti_localized.avail : bookacti_localized.avails;
				if( ! is_available && group_availability > 0 && bookacti_localized.not_bookable && bookacti_localized.not_bookable !== '{current}' ) {
					available_places = '';
					avail_particle   = bookacti_localized.not_bookable;
				}

				var nb_span       = $j( '<span></span>', { 'class': 'bookacti-available-places-number', 'html': available_places } );
				var particle_span = $j( '<span></span>', { 'class': 'bookacti-available-places-avail-particle', 'html': avail_particle } );

				places_div.append( nb_span );
				places_div.append( particle_span );
				avail_div.append( places_div );

				booking_system.trigger( 'bookacti_choose_group_dialog_group_availability_div', [ avail_div, group_id, group_date, event, group_availability, is_available ] );
			}
			
			var group_label = {
				'html': attributes[ 'groups_data' ][ group_id ][ 'title' ] + avail_div[0].outerHTML,
				'for': 'bookacti-group-of-events-' + group_id + '_' + group_date
			};

			// Allow third party to edit group labels
			booking_system.trigger( 'bookacti_choose_group_dialog_group_label', [ group_label, group_id, group_date, event, is_available ] );
			var label = $j( '<label></label>', group_label );

			// Build the group events list
			var event_list = $j( '<ul></ul>', {
				'id': 'bookacti-group-of-events-list-' + group_id + '_' + group_date,
				'class': 'bookacti-group-of-events-list bookacti-custom-scrollbar',
				'data-group-id': group_id,
				'data-group-date': group_date
			});

			// Add events of the group to the list
			$j.each( group_events, function( i, group_event ) {
				var start_and_end_same_day = group_event.start.substr( 0, 10 ) === group_event.end.substr( 0, 10 );
				var group_event_start      = moment.utc( group_event.start ).locale( bookacti_localized.current_lang_code );
				var group_event_end        = moment.utc( group_event.end ).locale( bookacti_localized.current_lang_code );

				var group_event_duration = group_event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.dates_separator + group_event_end.formatPHP( bookacti_localized.date_format );
				if( start_and_end_same_day ) {
					group_event_duration = group_event_start.formatPHP( bookacti_localized.date_format ) + bookacti_localized.date_time_separator + group_event_end.formatPHP( bookacti_localized.time_format );
				}

				var list_element = $j( '<li></li>', {
					'html': '<span class="bookacti-booking-event-duration" >'  + group_event_duration + '</span>' 
					+ '<span class="bookacti-booking-event-title-separator" > - </span>'  
					+ '<span class="bookacti-booking-event-title" >'  + group_event.title + '</span>'
				});

				event_list.append( list_element );
			});
			
			option_container.append( radio );
			option_container.append( label );
			container.append( option_container );
			container.append( event_list );
			
			groups_of_events_list.append( container );
		});
	});

	// Trigger a preview of the selection on change
	groups_of_events_list.find( 'input[name="group_of_events"]' ).on( 'change', function() {
		var group_val   = $j( this ).val();
		var group_id    = group_val !== 'single' ? parseInt( group_val.substr( 0, group_val.indexOf( '_' ) ) ) : 0;
		var group_date  = group_val !== 'single' ? group_val.substr( group_val.indexOf( '_' ) + 1 ) : '';
		var groups_list = $j( '#' + booking_system_id + '-choose-group-of-events-dialog .bookacti-groups-of-events-list' );

		// Hide other events list
		groups_list.find( '.bookacti-group-of-events-option:not(#bookacti-group-of-events-option-' + group_val + ')' ).data( 'show-events', 0 ).attr( 'data-show-events', 0 );
		groups_list.find( '.bookacti-group-of-events-list:not(#bookacti-group-of-events-list-' + group_val + ')' ).hide( 200 );

		// Show group events list
		groups_list.find( '#bookacti-group-of-events-option-' + group_val ).data( 'show-events', 1 ).attr( 'data-show-events', 1 );
		groups_list.find( '#bookacti-group-of-events-list-' + group_val ).show( 200 );

		// Don't preview the group of events if it is not available
		var trigger = { 'click': true };
		if( $j( this ).is( ':disabled' ) ) { trigger.click = false; }

		// Allow plugins to prevent the group of event preview
		booking_system.trigger( 'bookacti_trigger_group_of_events_preview', [ trigger, group_id, group_date, event ] );

		if( trigger.click ) {
			// If the event is picked, just unpick it (or its group)
			if( multiple_bookings ) { bookacti_unpick_events( booking_system, event ); }
			// Pick events and fill form inputs
			else { bookacti_unpick_all_events( booking_system ); }
			bookacti_pick_events( booking_system, event, group_id, group_date );

			booking_system.trigger( 'bookacti_group_of_events_preview', [ group_id, group_date, event ] ); 
		}
	});

	// Pick the first group by default and trigger the change
	groups_of_events_list.find( 'input[name="group_of_events"]:not([disabled]):first' ).prop( 'checked', true ).trigger( 'change' );
	
	// TEMP FIX: https://github.com/fullcalendar/fullcalendar/issues/5631
	booking_system.find( '.fc-toolbar button:focus' ).blur();
	
	// Make sure picked_events is emptied on close if no option has been selected
	dialog.dialog({
		"beforeClose": function(){},
		"close": function() {
			var selected_group = groups_of_events_list.find( 'input[type="radio"]:checked' ).val();
			// Empty the picked events if no group was choosen
			if( typeof selected_group === 'undefined' && ! multiple_bookings ) {
				bookacti_unpick_all_events( booking_system );
			}
		}
	});

	// Add the 'OK' button
	dialog.dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				var group_val = groups_of_events_list.find( 'input[type="radio"]:checked' ).val();

				if( typeof group_val !== 'undefined' ) {
					// Don't select the group of events if it is not available
					var trigger = { 'click': true };
					if( groups_of_events_list.find( 'input[type="radio"]:checked' ).is( ':disabled' ) ) { trigger.click = false; }
					
					var group_id   = group_val !== 'single' ? parseInt( group_val.substr( 0, group_val.indexOf( '_' ) ) ) : 0;
					var group_date = group_val !== 'single' ? group_val.substr( group_val.indexOf( '_' ) + 1 ) : '';
					
					// Allow plugins to prevent the group of events selection
					booking_system.trigger( 'bookacti_trigger_group_of_events_click', [ trigger, group_id, group_date, event ] );

					if( trigger.click ) {
						// If the event is picked, just unpick it (or its group)
						if( multiple_bookings ) { bookacti_unpick_events( booking_system, event ); }
						// Pick events and fill form inputs
						else { bookacti_unpick_all_events( booking_system ); }
						bookacti_pick_events( booking_system, event, group_id, group_date );

						booking_system.trigger( 'bookacti_group_of_events_chosen', [ group_id, group_date, event ] );
					}
				}

				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
	);

	// Open the modal dialog
	dialog.dialog( 'open' );
}