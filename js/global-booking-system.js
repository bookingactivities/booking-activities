$j( document ).ready( function() {
	
	//Check if booking systems exist before anything
	if( $j( '.bookacti-booking-system' ).length ) { 
		
		$j( '.bookacti-booking-system' ).each( function() { 
			
			// Retrieve the info required to show the desired events
			var booking_system	= $j( this );
			var attributes		= booking_system.data( 'attributes' );
			
			is_activity[attributes.id]		= true;
			loadingNumber[attributes.id]	= 0;
			pickedEvents[attributes.id]		= [];
			
			templates_array[attributes.id]	= attributes.templates;
			activities_array[attributes.id]	= attributes.activities;
			groups_array[attributes.id]		= attributes.groups;
			
			// Load the booking system
			if( attributes.auto_load && attributes.id !== 'bookacti-booking-system-reschedule' ) {
				bookacti_set_up_booking_method( booking_system, attributes.method, json_events[ attributes.id ] );
			}
		});		
	}

}); // end of document ready


