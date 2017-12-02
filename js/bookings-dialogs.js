$j( document ).ready( function() {
	// INITIALIZATION
	if( $j( '.bookacti-booking-action' ).length || $j( '.bookacti-booking-group-action' ).length ) {
		// Init the Dialogs
		bookacti_init_bookings_dialogs();
		
		// Init booking actions
		bookacti_init_booking_actions();
	}
});


// Initialize bookings dialogs
function bookacti_init_bookings_dialogs() {
	//Common param
	$j( '.bookacti-bookings-dialog' ).dialog({ 
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
	$j( '#bookacti-bookings-filters-param-dialog' ).dialog({ 
		title: bookacti_localized.booking_filters_parameters
	});
	$j( '#bookacti-bookings-list-param-dialog' ).dialog({ 
		title: bookacti_localized.booking_list_parameters
	});
	$j( '#bookacti-cancel-booking-dialog' ).dialog({ 
		title: bookacti_localized.booking_action_cancel
	});
	$j( '#bookacti-reschedule-booking-dialog' ).dialog({ 
		title: bookacti_localized.booking_action_reschedule
	});
	$j( '#bookacti-refund-booking-dialog' ).dialog({ 
		title: bookacti_localized.booking_action_refund
	});
	$j( '#bookacti-refund-booking-confirm-dialog' ).dialog({ 
		title: bookacti_localized.booking_confirm_refund
	});
	$j( '#bookacti-change-booking-state-dialog' ).dialog({ 
		title: bookacti_localized.booking_change_state
	});
}


// DIALOGS
// Dialog Bookings filters parameters
function bookacti_dialog_bookings_filters_param( booking_system ) {
	//Open the modal dialog
    $j( '#bookacti-bookings-filters-param-dialog' ).dialog( 'open' );
	
    //Add the 'OK' button
    $j( '#bookacti-bookings-filters-param-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				
				// Retrieve params values
				var show_past_events		= $j( '#bookacti-bookings-filters-param-dialog #bookacti-bookings-show-past-events' ).is( ':checked' ) ? 1 : 0;
				var allow_templates_filter	= $j( '#bookacti-bookings-filters-param-dialog #bookacti-bookings-allow-templates-filter' ).is( ':checked' ) ? 1 : 0;
				var allow_activities_filter	= $j( '#bookacti-bookings-filters-param-dialog #bookacti-bookings-allow-activities-filter' ).is( ':checked' ) ? 1 : 0;
				var nonce					= $j( '#bookacti-bookings-filters-param-dialog #nonce_update_booking_filters_settings' ).val();
				
				bookacti_start_loading_booking_system( booking_system );

				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiBookingFiltersSettings', 
							'show_past_events': show_past_events, 
							'allow_templates_filter': allow_templates_filter,
							'allow_activities_filter': allow_activities_filter,
							'nonce': nonce
					},
					dataType: 'json',
					success: function( response ){
						if( response.status === 'success' ) {
							
							// TO DO
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_update_settings;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_settings );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_loading_booking_system( booking_system );
					}
				});
				
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}


// Dialog Booking list parameters
function bookacti_dialog_booking_list_param( booking_system ) {
	// Open the modal dialog
    $j( '#bookacti-bookings-list-param-dialog' ).dialog( 'open' );
	
    // Add the 'OK' button
    $j( '#bookacti-bookings-list-param-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            // On click on the OK Button, new values are send to a script that update the database
            click: function() {
				
				// Retrieve params values
				var show_inactive_bookings	= $j( '#bookacti-bookings-list-param-dialog #bookacti-bookings-show-inactive-bookings' ).is( ':checked' ) ? 1 : 0;
				var show_temporary_bookings	= $j( '#bookacti-bookings-list-param-dialog #bookacti-bookings-show-temporary-bookings' ).is( ':checked' ) ? 1 : 0;
				var nonce					= $j( '#bookacti-bookings-list-param-dialog #nonce_update_booking_list_settings' ).val();
				
				bookacti_start_loading_booking_system( booking_system );

				$j.ajax({
					url: ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiBookingListSettings', 
							'show_inactive_bookings': show_inactive_bookings, 
							'show_temporary_bookings': show_temporary_bookings,
							'nonce': nonce
						},
					dataType: 'json',
					success: function( response ){
						if( response.status === 'success' ) {
							// Reload the booking list
							if( $j( '.bookacti-picked-event' ).length ) {
								$j( '.bookacti-picked-event:first' ).trigger( 'click' );
							}
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_update_settings;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_settings );
						console.log( e );
					},
					complete: function() { 
						bookacti_stop_loading_booking_system( booking_system );
					}
				});
				
				// Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}


// Cancel booking
function bookacti_dialog_cancel_booking( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiCancelBookingGroup' : 'bookactiCancelBooking';
	
	//Open the modal dialog
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'open' );
	
	//Add the buttons
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'option', 'buttons',
		// Cancel button    
		[{
            text: bookacti_localized.dialog_button_cancel,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        },
		// Cancel booking button
		{
			text: bookacti_localized.dialog_button_cancel_booking,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() { 
				
				if( booking_type === 'single' ) {
					var row = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
					var actions_container = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
					var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
				} else {
					var row = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
					var actions_container = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
				}
				
				var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( row );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: { 'action': action, 
							'booking_id': booking_id,
							'is_bookings_page': is_bookings_page,
							'nonce': bookacti_localized.nonce_cancel_booking
						},
					dataType: 'json',
					success: function( response ){
						
						if( response.status === 'success' ) {
							
							// Notify user that he cancelled his booking
							actions_container.html( response.new_actions_html );
							row.find( '.bookacti-booking-state' ).removeClass( 'bookacti-booking-state-good bookacti-booking-state-warning bookacti-booking-state-bad' ).addClass( 'bookacti-booking-state-bad' ).html( bookacti_localized.cancelled );
							
							if( response.allow_refund ) {
								bookacti_dialog_refund_booking( booking_id, booking_type );
							}
						
							$j( 'body' ).trigger( 'bookacti_booking_cancelled', [ booking_id, is_bookings_page ] );
						
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_cancel_booking;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( message_error );
							console.log( response );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_cancel_booking );
						console.log( e );
					},
					complete: function() {
						bookacti_booking_row_exit_loading_state( row );
					}
				});
				
				//Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}]
    );
}


// Refund a cancel booking
function bookacti_dialog_refund_booking( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type		= booking_type === 'group' ? 'group' : 'single';
	var action_html		= booking_type === 'group' ? 'bookactiGetBookingGroupRefundActionsHTML' : 'bookactiGetBookingRefundActionsHTML';
	var action_refund	= booking_type === 'group' ? 'bookactiRefundBookingGroup' : 'bookactiRefundBooking';
	
	// Cancel button
	var buttons = [{
		text: bookacti_localized.dialog_button_cancel,

		//On click on the OK Button, new values are send to a script that update the database
		click: function() {
			//Close the modal dialog
			$j( this ).dialog( 'close' );
		}
	}];
	
	// Get possible refund actions
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': action_html, 
				'booking_id': booking_id,
				'nonce': bookacti_localized.nonce_get_refund_actions_html
			},
		dataType: 'json',
		success: function( response ){
			
			if( response.status === 'success' ) {
				// FIll the dialog
				$j( '#bookacti-refund-booking-dialog' ).html( response.actions_html );
				
				// Add refund booking button if a refund method is available
				if( ! $j.isEmptyObject( response.actions_array ) ) {
					
					// Check the first radio
					$j( '#bookacti-refund-options input[type="radio"]:first' ).attr( 'checked', true );
					
					// Add a textarea to let the customer explain his choice
					var message_container = $j( '<div />', {
						'id': 'bookacti-refund-message'
					} );
					var message_title = $j( '<strong />', {
						'text': bookacti_localized.ask_for_reasons
					} );
					var message_input = $j( '<textarea />', {
						'name': 'refund-message'
					} );
					message_container.append( message_title );
					message_container.append( message_input );
					$j( '#bookacti-refund-options' ).after( message_container );
					
					buttons.push(
					{
						text: bookacti_localized.dialog_button_refund,

						click: function() { 
							
							if( booking_type === 'single' ) {
								var row = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
								var actions_container = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
							} else {
								var row = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
								var actions_container = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
							}
							
							// Display a loader
							var loading_div = 
							'<div class="bookacti-loading-alt">' 
								+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
								+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
							+ '</div>';
							row.find( '.bookacti-booking-state' ).hide();
							row.find( '.bookacti-booking-state' ).after( loading_div );
							
							var refund_action	= $j( '#bookacti-refund-options input[name="refund-action"]:checked' ).val();
							var refund_message	= $j( '#bookacti-refund-message textarea[name="refund-message"]' ).val();
							var nonce			= $j( '#bookacti-refund-options #nonce_refund_booking' ).val();
							
							$j.ajax({
								url: bookacti_localized.ajaxurl,
								type: 'POST',
								data: { 'action': action_refund, 
										'booking_id': booking_id,
										'refund_action': refund_action,
										'refund_message': refund_message,
										'is_admin': bookacti_localized.is_admin,
										'nonce': nonce
									},
								dataType: 'json',
								success: function( response ){
									
									if( response.status === 'success' ) {
										
										var refund_data = { 'message': '', 'new_status': bookacti_localized.refunded };
										if( refund_action === 'email' ) {
											refund_data.message += bookacti_localized.advice_refund_request_email_sent;
											refund_data.new_status = bookacti_localized.refund_requested;
										}
										
										$j( 'body' ).trigger( 'bookacti_booking_refunded', [ booking_id, booking_type, refund_action, refund_message, refund_data, response ] );
										
										// Change the booking data and possible actions on the booking line
										actions_container.html( response.new_actions_html );
										
										// Change booking state in the booking row
										var state_container = row.find( '.bookacti-booking-state' ).parent();
										row.find( '.bookacti-booking-state' ).remove();
										state_container.append( response.formatted_state );
										
										// Notify user that his booking has been refunded
										bookacti_dialog_refund_confirmation( refund_data.message );
											
									} else if( response.status === 'failed' ) {
										
										var message_error = bookacti_localized.error_refund_booking;
										if( response.error === 'not_allowed' ) {
											message_error += '\n' + bookacti_localized.error_not_allowed;
										} else if( response.error ) {
											message_error += '\n' + response.error;
										} else if( response.message ) {
											message_error += '\n' + response.message;
										}
										console.log( message_error );
										console.log( response );
									}

								},
								error: function( e ){
									console.log( 'AJAX ' + bookacti_localized.error_refund_booking );
									console.log( e );
								},
								complete: function() {
									row.find( '.bookacti-loading-alt' ).remove();
									row.find( '.bookacti-booking-state' ).show();
								}
							});

							//Close the modal dialog
							$j( this ).dialog( 'close' );
						}
					} );
				}
				
				//Add the buttons
				$j( '#bookacti-refund-booking-dialog' ).dialog( 'option', 'buttons', buttons );
				
				//Open the modal dialog
				$j( '#bookacti-refund-booking-dialog' ).dialog( 'open' );
				
			} else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_get_refund_booking_actions;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( message_error );
				console.log( response );
			}

		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_refund_booking );
			console.log( e );
		},
		complete: function() {
		}
	});
	
}


// Confirmation dialog after refund
function bookacti_dialog_refund_confirmation( message ) {
	
	// Fill the dialog
	 $j( '#bookacti-refund-booking-confirm-dialog' ).html( message );
	
	//Open the modal dialog
    $j( '#bookacti-refund-booking-confirm-dialog' ).dialog( 'open' );
	
	//Add the buttons
    $j( '#bookacti-refund-booking-confirm-dialog' ).dialog( 'option', 'buttons',
		// OK button    
		[{
            text: bookacti_localized.dialog_button_ok,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
}


// Change Booking State
function bookacti_dialog_change_booking_state( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiChangeBookingGroupState' : 'bookactiChangeBookingState';
	
	//Open the modal dialog
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'open' );
	
	// Disable current state
	$j( '#bookacti-select-booking-state option' ).attr( 'disabled', false );
	var row		= $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
	
	if( booking_type === 'single' ) {
		var row = $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
		var actions_container = $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
	} else {
		var row = $j( '.bookacti-change-booking-group-state[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
		var actions_container = $j( '.bookacti-change-booking-group-state[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
	}
	
	var state	= row.find( '.bookacti-booking-state' ).data( 'booking-state' );
	if( state ) {
		$j( '#bookacti-select-booking-state option[value="' + state + '"]' ).attr( 'disabled', true );
	}
	
	// Add the buttons
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'option', 'buttons',
		// Cancel button    
		[{
            text: bookacti_localized.dialog_button_cancel,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        },
		// Change booking state button
		{
			text: bookacti_localized.dialog_button_ok,
			
			click: function() { 
				
				var is_bookings_page	= row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
				var new_state			= $j( 'select#bookacti-select-booking-state' ).val(); 
				var send_notifications	= $j( '#bookacti-send-notifications-on-state-change' ).prop( 'checked' ) ? 1 : 0; 
				var nonce				= $j( '#bookacti-change-booking-state-dialog #nonce_change_booking_state' ).val(); 
				
				if( new_state && new_state !== state ) {

					// Display a loader
					bookacti_booking_row_enter_loading_state( row );

					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: { 'action': action, 
								'booking_id': booking_id,
								'new_state': new_state,
								'send_notifications': send_notifications,
								'is_bookings_page': is_bookings_page,
								'nonce': nonce
							},
						dataType: 'json',
						success: function( response ){

							if( response.status === 'success' ) {

								// Notify user that the booking state has changed
								actions_container.html( response.new_actions_html );
								row.find( '.bookacti-booking-state' ).parent().html( response.formatted_state );
								
								// Reload calendar if is bookings page and if active changed
								if( is_bookings_page && response.active_changed ) {
									var booking_method = bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'method' ];
									bookacti_booking_method_refetch_events( $j( '#bookacti-booking-system-bookings-page' ), booking_method );
								}
								
								$j( 'body' ).trigger( 'bookacti_booking_state_changed', [ booking_id, booking_type, new_state, is_bookings_page ] );

							} else if( response.status === 'failed' ) {
								var message_error = bookacti_localized.error_change_booking_state;
								if( response.error === 'not_allowed' ) {
									message_error += '\n' + bookacti_localized.error_not_allowed;
								}
								if( typeof response.message !== 'undefined' ) {
									message_error += '\n' + response.message;
								}
								console.log( message_error );
								console.log( response );
							}

						},
						error: function( e ){
							console.log( 'AJAX ' + bookacti_localized.error_change_booking_state );
							console.log( e );
						},
						complete: function() {
							bookacti_booking_row_exit_loading_state( row );
						}
					});

					//Close the modal dialog
					$j( this ).dialog( 'close' );
				}
			}
		}]
    );
}


// Reschedule booking
function bookacti_dialog_reschedule_booking( booking_id ) {
	
	var row					= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
	var booking_system		= $j( '#bookacti-booking-system-reschedule.bookacti-booking-system' );
	var booking_quantity	= 0;
	
	// Display a loader
	bookacti_booking_row_enter_loading_state( row );

	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiGetBookingData', 
				'booking_id': booking_id,
				'nonce': bookacti_localized.nonce_get_booking_data
			},
		dataType: 'json',
		success: function( response ){
			
			if( response.status === 'success' ) {
				
				// Clear displayed info
				bookacti_clear_booking_system_displayed_info( booking_system );
				
				// init var
				var booking_system_id	= booking_system.attr( 'id' );
				var template_id			= response.template_id;
				var activity_id			= response.activity_id;
				booking_quantity		= response.quantity;
				
				// Replace global data
				bookacti.booking_system[ booking_system_id ][ 'calendars' ]		= template_id ? [ template_id ] : [];
				bookacti.booking_system[ booking_system_id ][ 'activities' ]	= activity_id ? [ activity_id ] : [];
				
				booking_system.trigger( 'bookacti_before_booking_system_loads', [ response.event_settings ] );
				
				// Load booking system with new data
				bookacti_reload_booking_system( booking_system );
				
				//Open the modal dialog
				$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'open' );
				
				
			} else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_retrieve_booking_system;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( message_error );
				console.log( response );
			}

		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_retrieve_booking_system );
			console.log( e );
		},
		complete: function() {
			bookacti_booking_row_exit_loading_state( row );
		}
	});
	
	
	//Add the buttons
    $j( '#bookacti-reschedule-booking-dialog' ).dialog( 'option', 'buttons',
		// Cancel button    
		[{
            text: bookacti_localized.dialog_button_cancel,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        },
		// Reschedule booking button
		{
			text: bookacti_localized.dialog_button_reschedule,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() { 
				
				var event_id	= booking_system.parent().find( 'input[name="bookacti_event_id"]' ).val();
				var event_start	= booking_system.parent().find( 'input[name="bookacti_event_start"]' ).val();
				var event_end	= booking_system.parent().find( 'input[name="bookacti_event_end"]' ).val();
				
				var validated = bookacti_validate_picked_events( booking_system, booking_quantity );
				
				if( validated ) {
					
					var is_bookings_page	= bookacti_localized.is_admin ? 1 : 0;
					var send_notifications	= 1;
					if( is_bookings_page && $j( '#bookacti-send-notifications-on-reschedule' ).length ) {
						send_notifications	= $j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked' ) ? 1 : 0; 
					}
					
					// Display a loader
					bookacti_booking_row_enter_loading_state( row );

					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: { 'action': 'bookactiRescheduleBooking', 
								'booking_id': booking_id,
								'event_id': event_id,
								'event_start': event_start,
								'event_end': event_end,
								'is_bookings_page': is_bookings_page,
								'send_notifications': send_notifications,
								'nonce': bookacti_localized.nonce_reschedule_booking
							},
						dataType: 'json',
						success: function( response ){

							if( response.status === 'success' ) {
								
								//Close the modal dialog
								$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'close' );

								if( is_bookings_page ) {
									row.remove();
									$j( '#bookacti-booking-system-bookings-page .bookacti-calendar' ).fullCalendar( 'removeEvents' );
									bookacti_fetch_events( $j( '#bookacti-booking-system-bookings-page' ) );
								}
								
								$j( 'body' ).trigger( 'bookacti_booking_rescheduled', [ booking_id, event_start, event_end, response ] );

							} else {
								if( response.error == null ) {
									console.log( bookacti_localized.error_reschedule_booking );
									console.log( response );
								}

								booking_system.siblings( '.bookacti-notices' ).html( "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>").show();
							}

						},
						error: function( e ){
							console.log( 'AJAX ' + bookacti_localized.error_reschedule_booking );
							console.log( e );
						},
						complete: function() {
							bookacti_booking_row_exit_loading_state( row );
						}
					});
				}
			}
		}]
    );
}