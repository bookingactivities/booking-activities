$j( document ).ready( function() {

	//Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) { 
		
		$j( '.bookacti-booking-system' ).each( function() { 
			
			// Retrieve the info required to show the desired events
			var booking_system		= $j( this );
			var booking_system_id	= booking_system.data( 'booking-system-id' );
			var booking_method		= booking_system.data( 'booking-method' );
			var activities			= booking_system.data( 'activities' ).toString();
			var templates			= booking_system.data( 'templates' ).toString();
			var auto_load			= parseInt( booking_system.data( 'auto-load' ) );
			
			is_activity[booking_system_id]		= true;
			loadingNumber[booking_system_id]	= 0;
			
			if( templates_array[booking_system_id] === undefined )	{ templates_array[booking_system_id] = []; }
			if( activities_array[booking_system_id] === undefined )	{ activities_array[booking_system_id] = []; }
			
			if( templates.length )	{ templates_array[booking_system_id]	= templates.split(','); }
			if( activities.length )	{ activities_array[booking_system_id]	= activities.split(','); }
			
			// Load the booking system
			if( auto_load && booking_system_id !== 'reschedule' ) {
				if( booking_method === 'calendar' || ! $j.inArray( booking_method, bookacti_localized.available_booking_methods ) ) {
					bookacti_load_calendar( booking_system, true );
				} else {
					booking_system.trigger( 'bookacti_load_booking_system', [ booking_method, false ] );
				}
			}			
		});		
	}

}); // end of document ready


