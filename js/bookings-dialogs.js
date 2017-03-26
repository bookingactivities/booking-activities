// INITIALIZATION
// Initialize bookings dialogs
function bookacti_init_bookings_dialogs() {
    //Common param
    $j( '.bookacti-bookings-dialogs' ).dialog({ 
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
function bookacti_dialog_bookings_filters_param( booking_system )
{
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
							console.log( response );
							alert( message_error );
						}
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_settings );
						console.log( e.responseText );
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
function bookacti_dialog_booking_list_param( booking_system )
{
	//Open the modal dialog
    $j( '#bookacti-bookings-list-param-dialog' ).dialog( 'open' );
	
    //Add the 'OK' button
    $j( '#bookacti-bookings-list-param-dialog' ).dialog( 'option', 'buttons',
        [{
            text: bookacti_localized.dialog_button_ok,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				
				// Retrieve params values
				var show_inactive_bookings	= $j( '#bookacti-bookings-list-param-dialog #bookacti-bookings-show-inactive-bookings' ).is( ':checked' ) ? 1 : 0;
				var show_temporary_bookings	= $j( '#bookacti-bookings-list-param-dialog #bookacti-bookings-show-temporary-bookings' ).is( ':checked' ) ? 1 : 0;
				var nonce					= $j( '#bookacti-bookings-list-param-dialog #bookacti_update_booking_list_settings' ).val();
				
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
							if( $j( '.bookacti-selected-event' ).length ) {
								$j( '.bookacti-selected-event' ).trigger( 'click' );
							}
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_update_settings;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							console.log( response );
							alert( message_error );
						}
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_update_settings );
						console.log( e.responseText );
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


// Cancel booking
function bookacti_dialog_cancel_booking( booking_id ) {
	
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
			class: 'bookacti-dialog-delete-button',
			
			click: function() { 
				
				var row = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
				var actions_container = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
				var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( row );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: { 'action': 'bookactiCancelBooking', 
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
								bookacti_dialog_refund_booking( booking_id );
							}
						
							$j( 'body' ).trigger( 'bookacti_booking_cancelled', [ booking_id, is_bookings_page ] );
						
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_cancel_booking;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							alert( message_error );
						}
						
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error_cancel_booking );
						console.log( e.responseText );
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
function bookacti_dialog_refund_booking( booking_id ) {
	
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
		data: { 'action': 'bookactiGetRefundActionsHTML', 
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
						id: 'bookacti-refund-message'
					} );
					var message_title = $j( '<strong />', {
						text: bookacti_localized.ask_for_reasons
					} );
					var message_input = $j( '<textarea />', {
						name: 'refund-message'
					} );
					message_container.append( message_title );
					message_container.append( message_input );
					$j( '#bookacti-refund-options' ).after( message_container );
					
					buttons.push(
					{
						text: bookacti_localized.dialog_button_refund,

						click: function() { 
							
							var row = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
							var actions_container = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
							
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
								data: { 'action': 'bookactiRefundBooking', 
										'booking_id': booking_id,
										'refund_action': refund_action,
										'refund_message': refund_message,
										'nonce': nonce
									},
								dataType: 'json',
								success: function( response ){
									
									if( response.status === 'success' ) {
										
										var message = '';
										var new_status = bookacti_localized.refunded;
										if( refund_action === 'auto' ) {
											message += bookacti_localized.advice_booking_refunded;
										} else if( refund_action === 'coupon' ) {
											message += bookacti_localized.advice_booking_refunded;
											message += '<br/>' + bookacti_localized.advice_coupon_created.replace( '%1$s', '<strong>' + response.coupon_amount + '</strong>' );
											message += '<br/>' + bookacti_localized.advice_coupon_code.replace( '%1$s', '<strong>' + response.coupon_code + '</strong>' );
										} else if( refund_action === 'email' ) {
											message += bookacti_localized.advice_refund_request_email_sent;
											new_status = bookacti_localized.refund_requested;
										}
										
										
										// Change the booking data and possible actions on the booking line
										actions_container.html( response.new_actions_html );
										row.find( '.bookacti-booking-state' ).removeClass( 'bookacti-booking-state-good bookacti-booking-state-warning bookacti-booking-state-bad' ).addClass( 'bookacti-booking-state-bad' ).html( new_status );
																				
										$j( 'body' ).trigger( 'bookacti_booking_refunded', [ booking_id, refund_action, message, response ] );
										
										// Notify user that his booking has been refunded
										bookacti_dialog_refund_confirmation( message );
											
									} else if( response.status === 'failed' ) {
										
										var message_error = bookacti_localized.error_refund_booking;
										if( response.error === 'not_allowed' ) {
											message_error += '\n' + bookacti_localized.error_not_allowed;
										} else if( response.error ) {
											message_error += '\n' + response.error;
										} else if( response.message ) {
											message_error += '\n' + response.message;
										}
										alert( message_error );
									}

								},
								error: function( e ){
									console.log( 'AJAX ' + bookacti_localized.error_refund_booking );
									console.log( e.responseText );
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
				alert( message_error );
			}

		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_refund_booking );
			console.log( e.responseText );
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
function bookacti_dialog_change_booking_state( booking_id ) {
	
	//Open the modal dialog
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'open' );
	
	// Disable current state
	$j( '#bookacti-select-booking-state option' ).attr( 'disabled', false );
	var row		= $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
	var state	= row.find( '.bookacti-booking-state' ).data( 'booking-state' );
	if( state ) {
		$j( '#bookacti-select-booking-state option[value="' + state + '"]' ).attr( 'disabled', true );
	}
	
	//Add the buttons
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
				
				var actions_container	= $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
				var is_bookings_page	= row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
				var new_state			= $j( 'select#bookacti-select-booking-state' ).val(); 
				var nonce				= $j( '#bookacti-change-booking-state-dialog #nonce_change_booking_state' ).val(); 
				
				if( new_state && new_state !== state ) {

					// Display a loader
					bookacti_booking_row_enter_loading_state( row );

					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: { 'action': 'bookactiChangeBookingState', 
								'booking_id': booking_id,
								'new_state': new_state,
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
									var booking_method	= $j( '#bookacti-booking-system-bookings-page' ).data( 'booking-method' );
									if( booking_method === 'calendar' || ! ( booking_method in bookacti_localized.available_booking_methods ) ) {
										$j( '#bookacti-booking-system-bookings-page .bookacti-calendar' ).fullCalendar( 'removeEvents' );
										bookacti_fetch_calendar_events( $j( '#bookacti-booking-system-bookings-page .bookacti-calendar' ) );
									} else {
										$j( '#bookacti-booking-system-bookings-page' ).trigger( 'bookacti_refetch_events', [ booking_method, false ] );
									}
									
								}
								
								$j( 'body' ).trigger( 'bookacti_booking_state_changed', [ booking_id, new_state, is_bookings_page ] );

							} else if( response.status === 'failed' ) {
								var message_error = bookacti_localized.error_change_booking_state;
								if( response.error === 'not_allowed' ) {
									message_error += '\n' + bookacti_localized.error_not_allowed;
								}
								if( typeof response.message !== 'undefined' ) {
									message_error += '\n' + response.message;
								}
								console.log( response );
								alert( message_error );
							}

						},
						error: function( e ){
							console.log( 'AJAX ' + bookacti_localized.error_change_booking_state );
							console.log( e.responseText );
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
	
	var row				= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
	var booking_system	= $j( '#bookacti-booking-system-reschedule.bookacti-booking-system' );
	var booking_method	= booking_system.data( 'booking-method' );
	var booking_quantity= 0;
	
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
				var template_id		= response.template_id;
				var activity_id		= response.activity_id;
				booking_quantity	= response.quantity;
				
				// Empty global data
				templates_array[ 'reschedule' ]		= [];
				activities_array[ 'reschedule' ]	= [];
				
				// Fill global data
				booking_system.data( 'templates', template_id );
				booking_system.attr( 'data-templates', template_id );
				templates_array[ 'reschedule' ]	= template_id.toString().split(',');
				
				booking_system.data( 'activities', activity_id );
				booking_system.attr( 'data-activities', activity_id );
				activities_array[ 'reschedule' ] = activity_id.toString().split(',');
				
				booking_system.trigger( 'bookacti_before_booking_system_loads', [ response.event_settings ] );
				
				// Load booking system with new data
				if ( booking_method === 'calendar' || ! ( booking_method in bookacti_localized.available_booking_methods ) ) {
					bookacti_load_calendar( booking_system, true );
				} else {
					booking_system.trigger( 'bookacti_load_booking_system', [ booking_method, false ] );
				}
				
				//Open the modal dialog
				$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'open' );

			} else if( response.status === 'failed' ) {
				var message_error = bookacti_localized.error_retrieve_booking_system;
				if( response.error === 'not_allowed' ) {
					message_error += '\n' + bookacti_localized.error_not_allowed;
				}
				console.log( response );
				alert( message_error );
			}

		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error_retrieve_booking_system );
			console.log( e.responseText );
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
			class: 'bookacti-dialog-delete-button',
			
			click: function() { 
				
				var event_id	= booking_system.parent().find( 'input[name="bookacti_event_id"]' ).val();
				var event_start	= booking_system.parent().find( 'input[name="bookacti_event_start"]' ).val();
				var event_end	= booking_system.parent().find( 'input[name="bookacti_event_end"]' ).val();
				
				var validated = bookacti_validate_selected_booking_event( booking_system, booking_quantity );
				
				if( validated ) {
					
					var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length;
					
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
								'nonce': bookacti_localized.nonce_reschedule_booking
							},
						dataType: 'json',
						success: function( response ){

							if( response.status === 'success' ) {
								
								//Close the modal dialog
								$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'close' );

								if( is_bookings_page ) {
									row.remove();
									$j( '#bookacti-calendar-bookings-page' ).fullCalendar( 'removeEvents' );
									bookacti_fetch_calendar_events( $j( '#bookacti-calendar-bookings-page' ) );
								}
								
								$j( 'body' ).trigger( 'bookacti_booking_rescheduled', [ booking_id, event_start, event_end, response ] );

							} else {
								if( response.error == null ) {
									alert( bookacti_localized.error_reschedule_booking );
									console.log( response );
								}

								booking_system.siblings( '.bookacti-notices' ).html( "<ul class='bookacti-error-list'><li>" + response.message + "</li></ul>").show();
							}

						},
						error: function( e ){
							console.log( 'AJAX ' + bookacti_localized.error_reschedule_booking );
							console.log( e.responseText );
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