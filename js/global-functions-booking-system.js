//Fill a intelligible field to feedback the user about his choice
function bookacti_fill_picked_event_summary( booking_system, start, end, activity_id ) {
	
	var date_picked = booking_system.siblings( '.bookacti-date-picked' );
	var event_start	= moment( start );
	var event_end	= moment( end );
	
	//Make 'from' and 'to' intelligible values
	date_picked.find( '.bookacti-date-picked-to' ).removeClass( 'to_hour to_date' );
	var from_val = event_start.locale( bookacti_localized.current_lang_code ).format( 'LLLL' );
	var sep_val	= '';
	var to_val = '';
	if( event_start.format( 'YYYY-MM-DD' ) === event_end.format( 'YYYY-MM-DD' ) ) { 
		sep_val		= ' ' + bookacti_localized.to_hour + ' ';
		to_val		= event_end.locale( bookacti_localized.current_lang_code ).format( 'LT' );
		date_picked.find( '.bookacti-date-picked-to' ).addClass( 'to_hour' );
	} else {
		sep_val		= ' ' + bookacti_localized.to_date + ' ';
		to_val		= event_end.locale( bookacti_localized.current_lang_code ).format( 'LLLL' );
		date_picked.find( '.bookacti-date-picked-to' ).addClass( 'to_date' );
	}

	// Give third party plugins opportunity to change some values, especially quantity
	var event_summary_data = {
		'start':		start,
		'end':			end,
		'activity_id':	activity_id,
		'quantity':		1,
		'from':			from_val,
		'separator':	sep_val,
		'to':			to_val
	};
	booking_system.trigger( 'bookacti_fill_picked_event_summary', [ event_summary_data ] );
	
	// Activity val
	bookacti_fill_picked_activity_summary( booking_system, event_summary_data.activity_id, event_summary_data.quantity );
	
	//Fill a intelligible field to feedback the user about his choice
	date_picked.find(  '.bookacti-date-picked-from' ).html( event_summary_data.from );
	date_picked.find(  '.bookacti-date-picked-separator' ).html( event_summary_data.separator );
	date_picked.find(  '.bookacti-date-picked-to' ).html( event_summary_data.to );
	
	booking_system.siblings( '.bookacti-date-picked' ).show();
	
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
}


// Fill activity summary
function bookacti_fill_picked_activity_summary( booking_system, activity_id, qty ) {
	
	var booking_system_id	= booking_system.data( 'booking-system-id' );
	
	if( ! activity_id ) {
		if( pickedEvents[ booking_system_id ] !== undefined ) {
			if( pickedEvents[ booking_system_id ][ 0 ] !== undefined ) {
				if( pickedEvents[ booking_system_id ][ 0 ][ 'activity-id' ] !== undefined ) {
					activity_id = pickedEvents[ booking_system_id ][ 0 ][ 'activity_id' ];
				}
			}
		}
	}
	
	if( activity_id ) {
		
		qty	= qty || 0;
		qty	= qty ? parseInt( qty ) : 1;
		var activity_val= '';
		
		if( json_activities[ activity_id ] !== undefined ) {
			if( json_activities[ activity_id ][ 'settings' ] !== undefined ) {
				if( json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== undefined
				&&  json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== undefined 
				&&  json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== undefined ) {

					if( json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== ''
					&&  json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== '' ) { 
						activity_val += qty + ' ';
						if( qty > 1 ) {
							activity_val += json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ];
						} else {
							activity_val += json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ];
						}
					}
					if( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== '' 
					&&  parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) > 0 )
					{
						if( parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) > 1 ) {
							activity_val += ' ' + bookacti_localized.n_persons_per_booking.replace( '%1$s', json_activities[ activity_id ][ 'settings' ][ 'places_number' ] );
						} else {
							activity_val += ' ' + bookacti_localized.one_person_per_booking;
						}
					}
					
					if((json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== ''
					&&	json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== '' )
					|| (json_activities[ activity_id ][ 'settings' ][ 'places_number' ] !== ''
					&&	parseInt( json_activities[ activity_id ][ 'settings' ][ 'places_number' ] ) !== 0 ) ) {
						activity_val += '<br/>';
					}
				}
			}
		}
		booking_system.trigger( 'bookacti_picked_activity_summary_filled', [ activity_val, activity_id, qty ] );
		booking_system.siblings( '.bookacti-date-picked' ).find( '.bookacti-date-picked-activity' ).html( activity_val );
	}
}


// Clear booking system displayed info
function bookacti_clear_booking_system_displayed_info( booking_system ) {
	
	var booking_system_id	= booking_system.data( 'booking-system-id' );
	
	//Empty the selected events info
	booking_system.siblings( '.bookacti-booking-system-inputs' ).find( 'input' ).val('');
	booking_system.siblings( '.bookacti-date-picked' ).hide();
	booking_system.siblings( '.bookacti-date-picked' ).find( '.bookacti-date-picked-from' ).empty();
	booking_system.siblings( '.bookacti-date-picked' ).find( '.bookacti-date-picked-separator' ).empty();
	booking_system.siblings( '.bookacti-date-picked' ).find( '.bookacti-date-picked-to' ).empty();
	booking_system.find( '.fc-event' ).removeClass( 'bookacti-picked-event' );
	pickedEvents[ booking_system_id ] = [];
	
	// Clear errors
	booking_system.siblings( '.bookacti-notices' ).hide();
	booking_system.siblings( '.bookacti-notices' ).empty();
	booking_system.show();
	
	booking_system.trigger( 'bookacti_displayed_info_cleared' );
}


// Update booking system settings
function bookacti_update_settings_from_database( booking_system, template_ids ) {
	
	if( booking_system.attr( 'id' ) === 'bookacti-template-calendar' ) {
		bookacti_start_template_loading();
	} else {
		bookacti_start_loading_booking_system( booking_system );
	}
	
    // Retrieve template info and fill fields
    $j.ajax({
        url: bookacti_localized.ajaxurl, 
        data: { 'action': 'bookactiGetBookingSystemData', 
                'template_ids': template_ids,
                'is_admin': bookacti_localized.is_admin,
                'nonce': bookacti_localized.nonce_get_booking_system_data
			}, 
        type: 'POST',
        dataType: 'json',
        success: function( response ){
			
            // If success
            if( response.status === 'success' && response.settings ) {
				
				var booking_method	= booking_system.data( 'booking-method' );
				
				if( ( booking_method === 'calendar' 
					  || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) 
					&& booking_system.find( '.bookacti-calendar' ).length ) {
					bookacti_update_calendar_settings( booking_system.find( '.bookacti-calendar' ), response.settings );
				} else if( booking_system.attr( 'id' ) === 'bookacti-template-calendar' ) {
					bookacti_update_calendar_settings( booking_system, response.settings );
				} else {
					booking_system.trigger( 'bookacti_update_settings', [ booking_system.data( 'booking-method' ), response.settings ] );
				}
				
            // If error
            } else {
				var message_error = bookacti_localized.error_retrieve_template_data;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
            }
        },
        error: function( e ){
            console.log( 'AJAX ' + bookacti_localized.error_retrieve_template_data );        
            console.log( e );
        },
        complete: function() { 
			if( booking_system.attr( 'id' ) === 'bookacti-template-calendar' ) {
				bookacti_stop_template_loading();
			} else {
				bookacti_stop_loading_booking_system( booking_system );
			}
		}
    });
}


//Start a loading (or keep on loading if already loading)
function bookacti_start_loading_booking_system( booking_system ) {
	
	var booking_system_id		= booking_system.data( 'booking-system-id' );
	var booking_method			= booking_system.data( 'booking-method' );
	
	var loading_div =	'<div class="bookacti-loading-alt">' 
							+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
							+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
						+ '</div>';
	
	if( ! $j.isNumeric( loadingNumber[ booking_system_id ] ) ) {
		loadingNumber[ booking_system_id ] = 0;
	}
	
	if( booking_method === 'calendar' || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) {
		
		if( booking_system.find( '.bookacti-calendar' ).length ) {
			if( loadingNumber[ booking_system_id ] === 0 || ! booking_system.find( '.bookacti-loading-overlay' ).length ) {
				booking_system.find( '.bookacti-loading-alt' ).remove();
				bookacti_enter_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
			}
		} else if( ! booking_system.find( '.bookacti-loading-alt' ).length ) {
			booking_system.prepend( loading_div );
		}
		
	} else {
		booking_system.trigger( 'bookacti_start_loading', [ booking_method, loading_div ] );
	}
	
	if( loadingNumber[ booking_system_id ] === 0 ) {
		booking_system.trigger( 'bookacti_enter_loading_state' );
	}
	
	loadingNumber[ booking_system_id ]++;
}


//Stop a loading (but keep on loading if there are other loadings )
function bookacti_stop_loading_booking_system( booking_system, force_exit ) {
	
	force_exit = force_exit || false;
	
	var booking_system_id	= booking_system.data( 'booking-system-id' );
	var booking_method		= booking_system.data( 'booking-method' );
	
	loadingNumber[ booking_system_id ]--;
	loadingNumber[ booking_system_id ] = Math.max( loadingNumber[ booking_system_id ], 0 );
	
	if( force_exit ) { loadingNumber[ booking_system_id ] = 0; }
	
	// Action to do after everything has loaded
	if( loadingNumber[ booking_system_id ] === 0 ) {
		
		if( booking_method === 'calendar' || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) {		
			bookacti_exit_calendar_loading_state( booking_system.find( '.bookacti-calendar' ) );
		} else {
			booking_system.trigger( 'bookacti_stop_loading', [ booking_method ] );
		}
		
		booking_system.find( '.bookacti-loading-alt' ).remove();
		booking_system.trigger( 'bookacti_exit_loading_state' );
		
	}
}


//Get event available places
function bookacti_get_event_availability( event ) {
	return parseInt( event.availability ) - parseInt( event.bookings );
}


//Get a div with event available places
function bookacti_get_event_availability_div( available_places, is_bookings, activity_id ) {
	
	var unit_name = '';
	if( activity_id ) {
		if( json_activities[ activity_id ] !== undefined ) {
			if( json_activities[ activity_id ][ 'settings' ] !== undefined ) {
				if( json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ] !== undefined
				&&  json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ] !== undefined 
				&&  json_activities[ activity_id ][ 'settings' ][ 'show_unit_in_availability' ] !== undefined ) {
					if( parseInt( json_activities[ activity_id ][ 'settings' ][ 'show_unit_in_availability' ] ) ) {
						if( available_places > 1 ) {
							unit_name = json_activities[ activity_id ][ 'settings' ][ 'unit_name_plural' ];
						} else {
							unit_name = json_activities[ activity_id ][ 'settings' ][ 'unit_name_singular' ];
						}
					}
				}
			}
		}
	}
	
	var avail = available_places > 1 ? bookacti_localized.avails : bookacti_localized.avail;
	
	//Detect if the event is available or full, and if it is booked or not
	var class_booked = is_bookings ? 'bookacti-booked' : 'bookacti-not-booked';
	var class_full = available_places <= 0 ? 'bookacti-full' : '';
	
	//Build a div with availability
	var div = '<div class="bookacti-availability-container" >' 
				+ '<span class="bookacti-available-places ' + class_booked + ' ' + class_full + '" >'
					+ '<span class="bookacti-available-places-number">' + available_places + '</span>' 
					+ '<span class="bookacti-available-places-unit-name"> ' + unit_name + '</span>' 
					+ '<span class="bookacti-available-places-avail-particle"> ' + avail + '</span>'
				+ '</span>'
			+ '</div>';
	
	return div;
}


//Display an error message if no availability was found
function bookacti_add_error_message( booking_system, message ) {
	
	message = message || bookacti_localized.error_no_events_bookable;
	
	booking_system.hide();
	booking_system.siblings( '.bookacti-notices' ).empty().append( "<ul class='bookacti-error-list'><li>" + message + "</li></ul>" ).show();
	
	booking_system.trigger( 'bookacti_error_displayed', [ message ] );
}


// Sort an array of events by dates
function bookacti_sort_events_array_by_dates( array, desc ) {
	
	desc = desc || false;
	
	array.sort( function( a, b ) {
		
		//Sort by start date ASC
		var sort = new Date( a.start ) - new Date( b.start );
		
		//If start date is the same, then sort by end date ASC
		if( sort === 0 ) {
			sort = new Date( a.end ) - new Date( b.end );
		}
		
		if( desc === true ) { sort = ! sort; }
		
		return sort;
	});
	
	return array;
}