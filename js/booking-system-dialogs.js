// INITIALIZATION
// Initialize bookings dialogs
function bookacti_init_booking_system_dialogs() {
	//Common param
	$j( '.bookacti-booking-system-dialog' ).dialog({ 
		modal:      true,
		autoOpen:   false,
		minHeight:  300,
		minWidth:   440,
		resize:		'auto',
		show:       true,
		hide:       true,
		closeText:  '&#10006;',
		close: function() {}
	});

	//Individual param
	$j( '#bookacti-choose-group-of-events-dialog' ).dialog({ 
		title: bookacti_localized.dialog_choose_group_of_events_title
	});
}


// Choose a group of events dialog
function bookacti_dialog_choose_group_of_events( booking_system, groups ) {
	
	var booking_system_id = booking_system.attr( 'id' );
	
	// Fill the dialog with the different choices
	$j( '#bookacti-groups-of-events-list' ).empty();
	$j.each( groups, function( i, group_id ) {
		if( typeof json_groups[ booking_system_id ][ parseInt( group_id ) ] !== 'undefined' ) {
			
			var div = $j( '<div />', {});
			var radio = $j( '<input />', {
				id: 'bookacti-group-of-events-' + group_id,
				type: 'radio',
				name: 'group_of_events',
				value: group_id
			});
			var label = $j( '<label />', {
				text: json_groups[ booking_system_id ][ group_id ][0][ 'group_title' ],
				for: 'bookacti-group-of-events-' + group_id
			});
			
			div.append( radio );
			div.append( label );
			
			$j( '#bookacti-groups-of-events-list' ).append( div );
		}
	});
	
	
	// Open the modal dialog
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog( 'open' );
	
	// Make sure pickedEvents is emptied on close
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog({
		close: function() {
				var selected_group = $j('#bookacti-groups-of-events-list input[type="radio"]:checked').val();
				// Empty the picked events if no group was choosen
				if( typeof selected_group === 'undefined' ) {
					pickedEvents[ booking_system_id ] = [];
					$j( '.bookacti-picked-event' ).removeClass( 'bookacti-picked-event' );
				}
			}
	});
	
    // Add the 'OK' button
    $j( '#bookacti-choose-group-of-events-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            click: function() {
				
				var selected_group = $j( '#bookacti-groups-of-events-list input[type="radio"]:checked' ).val();
				if( typeof selected_group === 'undefined' ) {
					pickedEvents[ booking_system_id ] = [];
					$j( '.bookacti-picked-event' ).removeClass( 'bookacti-picked-event' );
				} else {
					bookacti_pick_events_of_group( booking_system, selected_group );
				}
				
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}