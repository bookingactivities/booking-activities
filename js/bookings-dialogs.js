$j( document ).ready( function() {
	// Init the Dialogs
	bookacti_init_bookings_dialogs();
	
	// Init booking actions
	if( $j( '.bookacti-booking-action' ).length || $j( '.bookacti-booking-group-action' ).length ) {	
		bookacti_init_booking_actions();
	}
});


// Initialize bookings dialogs
function bookacti_init_bookings_dialogs() {
	// Common param
	$j( '.bookacti-bookings-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		440,
		"resize":		'auto',
		"show":			true,
		"hide":			true,
		"dialogClass":	'bookacti-dialog',
		"closeText":	'&#10006;',
		"close":		function() {}
	});
	
	// Make dialogs close when the user click outside
	$j( 'body' ).on( 'click', '.ui-widget-overlay', function (){
		$j( 'div:ui-dialog:visible' ).dialog( 'close' );
	});
	
	// Press ENTER to bring focus on OK button
	$j( '.bookacti-bookings-dialog' ).off( 'keydown' ).on( 'keydown', function( e ) {
		if( ! $j( 'textarea' ).is( ':focus' ) && e.keyCode == $j.ui.keyCode.ENTER ) {
			$j( this ).parent().find( '.ui-dialog-buttonpane button:first' ).focus(); 
			return false; 
		}
	});
}


// DIALOGS

// Cancel booking
function bookacti_dialog_cancel_booking( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiCancelBookingGroup' : 'bookactiCancelBooking';
	
	//Open the modal dialog
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'open' );
	
	//Add the buttons
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'option', 'buttons',
		[
		// Cancel booking button
		{
			text: bookacti_localized.dialog_button_cancel_booking,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() { 
				
				var row, actions_container;
				if( booking_type === 'single' ) {
					row = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
					actions_container = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
				} else {
					row = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
					actions_container = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
				}
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( row );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: { 'action': action, 
							'booking_id': booking_id,
							'nonce': bookacti_localized.nonce_cancel_booking
						},
					dataType: 'json',
					success: function( response ){
						
						if( response.status === 'success' ) {
							
							// Change possible actions on the booking line
							actions_container.html( response.new_actions_html );
							
							// Change booking state in the booking row
							var state_container = row.find( '.bookacti-booking-state' ).parent();
							row.find( '.bookacti-booking-state' ).remove();
							state_container.append( response.formatted_state );
							
							if( response.allow_refund ) {
								bookacti_dialog_refund_booking( booking_id, booking_type );
							}
						
							$j( 'body' ).trigger( 'bookacti_booking_cancelled_by_user', [ booking_id, booking_type ] );
						
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
		},
		// Cancel button 
		{
			text: bookacti_localized.dialog_button_cancel,
			// On click on the OK Button, new values are send to a script that update the database
			click: function() {
				// Close the modal dialog
				$j( this ).dialog( 'close' );
			}
		}
		]
	);
}


/**
 * Refund a cancelled booking
 * @version 1.5.8
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_refund_booking( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type		= booking_type === 'group' ? 'group' : 'single';
	var action_html		= booking_type === 'group' ? 'bookactiGetBookingGroupRefundActionsHTML' : 'bookactiGetBookingRefundActionsHTML';
	var action_refund	= booking_type === 'group' ? 'bookactiRefundBookingGroup' : 'bookactiRefundBooking';
	
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
				var buttons = [];
				
				// FIll the dialog
				$j( '#bookacti-refund-booking-dialog' ).html( response.actions_html );
				
				// Add refund booking button if a refund method is available
				if( ! $j.isEmptyObject( response.actions_array ) ) {
					
					// Check the first radio
					$j( '#bookacti-refund-options input[type="radio"]:first' ).prop( 'checked', true );
					
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
						'class': 'bookacti-dialog-delete-button',

						click: function() { 
							
							// Reset error notices
							$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).remove();
							
							var row, actions_container;
							if( booking_type === 'single' ) {
								row = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
								actions_container = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
							} else {
								row = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
								actions_container = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
							}
							
							var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;

							// Booking page specific data
							var reload_grouped_bookings = 0;
							if( is_bookings_page ) {
								actions_container = row.find( 'td.actions.column-actions' );
								reload_grouped_bookings = row.next().hasClass( 'bookacti-gouped-booking' ) ? 1 : 0;
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
										'reload_grouped_bookings': reload_grouped_bookings,
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
										
										// Change the booking data and possible actions on the booking line
										actions_container.html( response.new_actions_html );
										
										// Change booking state in the booking row
										var state_container = row.find( '.bookacti-booking-state' ).parent();
										row.find( '.bookacti-booking-state' ).remove();
										state_container.append( response.formatted_state );
										
										// Replace old grouped bookings by new ones
										if( reload_grouped_bookings && typeof response.grouped_booking_rows !== 'undefined' ) {
											var are_grouped_booking_hidden = row.next().hasClass( 'hidden' );
											row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).remove();
											row.after( response.grouped_booking_rows );
											if( are_grouped_booking_hidden ) {
												row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
												if( row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
													row.after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
												}
											}
										}
										
										$j( 'body' ).trigger( 'bookacti_booking_refunded', [ booking_id, booking_type, refund_action, refund_message, refund_data, response ] );
										
										// Notify user that his booking has been refunded
										bookacti_dialog_refund_confirmation( refund_data.message );
										
									} else if( response.status === 'failed' ) {
										
										var message_error = '';
										if( response.message ) {
											message_error += response.message;
										} else {
											message_error += bookacti_localized.error_refund_booking;
											if( response.error ) {
												message_error += ' (' + response.error + ')';
											}
										}
										
										$j( '#bookacti-refund-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + message_error + '</li></ul></div>' );
										
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
				
				// Cancel button
				buttons.push(  
					{
						text: bookacti_localized.dialog_button_cancel,

						// On click on the OK Button, new values are send to a script that update the database
						click: function() {
							// Close the modal dialog
							$j( this ).dialog( 'close' );
						}
					}
				);
				
				// Add the buttons
				$j( '#bookacti-refund-booking-dialog' ).dialog( 'option', 'buttons', buttons );
				
				// Open the modal dialog
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


/**
 * Change Booking State
 * @version 1.5.6
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_change_booking_state( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiChangeBookingGroupState' : 'bookactiChangeBookingState';
	
	// Get current row and actions container
	var row, actions_container;
	if( booking_type === 'single' ) {
		row = $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
		actions_container = $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).parents( '.bookacti-booking-actions' );
	} else {
		row = $j( '.bookacti-change-booking-group-state[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
		actions_container = $j( '.bookacti-change-booking-group-state[data-booking-group-id="' + booking_id + '"]' ).parents( '.bookacti-booking-group-actions' );
	}
	
	var is_bookings_page = row.parents( '#bookacti-bookings-list' ).length ? 1 : 0;
	
	// Booking page specific data
	var reload_grouped_bookings = 0;
	if( is_bookings_page ) {
		actions_container = row.find( 'td.actions.column-actions' );
		reload_grouped_bookings = row.next().hasClass( 'bookacti-gouped-booking' ) ? 1 : 0;
	}
	
	// Select the current state
	var booking_state	= row.find( '.bookacti-booking-state' ).data( 'booking-state' );
	var payment_status	= row.find( '.bookacti-payment-status' ).data( 'payment-status' );
	if( booking_state )	{ $j( '#bookacti-select-booking-state option[value="' + booking_state + '"]' ).prop( 'selected', true ); }
	if( payment_status ){ $j( '#bookacti-select-payment-status option[value="' + payment_status + '"]' ).prop( 'selected', true ); }
	$j( '#bookacti-send-notifications-on-state-change' ).prop( 'checked', false );
	
	// Add the buttons
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'option', 'buttons',
		[
		// Change booking state button
		{
			text: bookacti_localized.dialog_button_ok,
			
			click: function() { 
				
				var new_booking_state	= $j( 'select#bookacti-select-booking-state' ).val(); 
				var new_payment_status	= $j( 'select#bookacti-select-payment-status' ).val(); 
				var send_notifications	= $j( '#bookacti-send-notifications-on-state-change' ).prop( 'checked' ) ? 1 : 0; 
				var nonce				= $j( '#bookacti-change-booking-state-dialog #nonce_change_booking_state' ).val(); 
				
				if( ( new_booking_state || new_payment_status ) 
				&&  ( new_booking_state !== booking_state || new_payment_status !== payment_status ) ) {
					
					// Display a loader
					bookacti_booking_row_enter_loading_state( row );
					
					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: { 'action': action, 
								'booking_id': booking_id,
								'new_booking_state': booking_state !== new_booking_state ? new_booking_state : 0,
								'new_payment_status': new_payment_status,
								'send_notifications': send_notifications,
								'is_bookings_page': is_bookings_page,
								'reload_grouped_bookings': reload_grouped_bookings,
								'nonce': nonce
							},
						dataType: 'json',
						success: function( response ){
							
							if( response.status === 'success' ) {

								// Replace old grouped bookings by new ones
								if( reload_grouped_bookings && typeof response.grouped_booking_rows !== 'undefined' ) {
									var are_grouped_booking_hidden = row.next().hasClass( 'hidden' );
									row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).remove();
									row.after( response.grouped_booking_rows );
									if( are_grouped_booking_hidden ) {
										row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
										if( row.nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
											row.after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
										}
									}
								}
								
								// Replace the row
								row.replaceWith( response.row );
								bookacti_refresh_list_table_hidden_columns();
								
								// Update booking state
								if( booking_state !== new_booking_state ) {
									$j( 'body' ).trigger( 'bookacti_booking_state_changed', [ booking_id, booking_type, new_booking_state, booking_state, is_bookings_page, response.active_changed ] );
								}
								
								// Update payment status
								if( payment_status !== new_payment_status ) {
									$j( 'body' ).trigger( 'bookacti_payment_status_changed', [ booking_id, booking_type, new_payment_status, payment_status ] );
								}
								
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
		},
		// Cancel button
		{
            text: bookacti_localized.dialog_button_cancel,
            
            //On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
	
	// Open the modal dialog
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'open' );
}


// Reschedule booking
function bookacti_dialog_reschedule_booking( booking_id ) {
	
	var row					= $j( '.bookacti-booking-action[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
	var booking_system		= $j( '#bookacti-booking-system-reschedule.bookacti-booking-system' );
	var booking_quantity	= 0;
	
	var is_bookings_page	= bookacti_localized.is_admin ? 1 : 0;
	if( is_bookings_page ) {
		$j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked', false ); 
	}
	
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
				var template_id			= response.booking_data.template_id;
				var activity_id			= response.booking_data.activity_id;
				booking_quantity		= response.booking_data.quantity;
				
				// Replace global data
				bookacti.booking_system[ booking_system_id ][ 'calendars' ]		= template_id ? [ template_id ] : [];
				bookacti.booking_system[ booking_system_id ][ 'activities' ]	= activity_id ? [ activity_id ] : [];
				bookacti.booking_system[ booking_system_id ][ 'template_data' ]	= [];
				
				booking_system.trigger( 'bookacti_before_reschedule_booking_system_loads', [ response.booking_data ] );
				
				// Load booking system with new data
				bookacti_reload_booking_system( booking_system );
				
				// Open the modal dialog
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
	
	
	// Add the buttons
    $j( '#bookacti-reschedule-booking-dialog' ).dialog( 'option', 'buttons',
		[	
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
								
								// Close the modal dialog
								$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'close' );
								
								// If the action has been triggered from admin booking list or frontend shortcode
								if( row.parents( '#bookacti-bookings-list, .bookacti-user-bookings-list' ).length > 0 ) {
									row.after( response.row );
									row.remove();
									bookacti_refresh_list_table_hidden_columns();
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
		},
		// Cancel button
		{
            text: bookacti_localized.dialog_button_cancel,
            
            // On click on the OK Button, new values are send to a script that update the database
            click: function() {
				//Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }
		]
    );
}


/**
 * Delete a booking or a booking group
 * @since 1.5.0
 * @param {type} booking_id
 * @param {type} booking_type
 * @returns {undefined}
 */
function bookacti_dialog_delete_booking( booking_id, booking_type ) {
	
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	
	// Get current row and show / hide specific fields
	var row, action;
	if( booking_type === 'single' ) {
		action = 'bookactiDeleteBooking';
		row = $j( '.bookacti-delete-booking[data-booking-id="' + booking_id + '"]' ).parents( 'tr' );
		$j( '.bookacti-delete-booking-group-description' ).hide();
	} else {
		action = 'bookactiDeleteBookingGroup';
		row = $j( '.bookacti-delete-booking-group[data-booking-group-id="' + booking_id + '"]' ).parents( 'tr' );
		$j( '.bookacti-delete-booking-group-description' ).show();
	}
	
	// Fill hidden fields
	$j( '#bookacti-delete-booking-dialog input[name="action"]' ).val( action );
	$j( '#bookacti-delete-booking-dialog input[name="booking_id"]' ).val( booking_id );
	$j( '#bookacti-delete-booking-dialog input[name="booking_type"]' ).val( booking_type );
	
	// Reset error notices
	$j( '#bookacti-delete-booking-dialog .bookacti-notices' ).remove();
	
	// Add the buttons
    $j( '#bookacti-delete-booking-dialog' ).dialog( 'option', 'buttons',
		[
		// Delete button
		{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',
			click: function() { 
				
				// Reset error notices
				$j( '#bookacti-delete-booking-dialog .bookacti-notices' ).remove();
				
				// Get form values
				var data = $j( '#bookacti-delete-booking-dialog form' ).serialize();
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( row );
				
				// Display a loader
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-delete-booking-dialog' ).append( loading_div );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ){

						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-delete-booking-dialog' ).dialog( 'close' );
							
							// Remove grouped bookings row
							if( booking_type === 'group' && $j( '.bookacti-booking-group-id-' + booking_id ).length ) {
								var group_rows = $j( '.bookacti-booking-group-id-' + booking_id );
								group_rows.removeClass( 'bookacti-gouped-booking' ).animate( {'opacity': 0}, function() { group_rows.children('td, th').animate({ 'padding': 0 }).wrapInner('<div />').children().slideUp( function() { group_rows.remove(); } ); });
							}
							
							// Remove the booking row
							row.animate( {'opacity': 0}, function() { row.children('td, th').animate({ 'padding': 0 }).wrapInner('<div />').children().slideUp(function() { row.remove(); }); });
							
						} else if( response.status === 'failed' ) {
							var message_error = bookacti_localized.error_delete_booking;
							if( response.error === 'not_allowed' ) {
								message_error += '\n' + bookacti_localized.error_not_allowed;
							}
							if( typeof response.message !== 'undefined' ) {
								message_error += '\n' + response.message;
							}
							$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + message_error + '</li></ul></div>' );
							console.log( message_error );
							console.log( response );
						}

					},
					error: function( e ){
						$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error_delete_booking + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error_delete_booking );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-delete-booking-dialog' ).find( '.bookacti-notices' ).show();
						$j( '#bookacti-delete-booking-dialog' ).find( '.bookacti-loading-alt' ).remove();
						bookacti_booking_row_exit_loading_state( row );
					}
				});
			}
		},
		// Cancel button
		{
            text: bookacti_localized.dialog_button_cancel,
            'class': 'bookacti-dialog-left-button',
            // On click on the OK Button, new values are send to a script that update the database
            click: function() {
				// Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }
		]
    );
	
	// Open the modal dialog
    $j( '#bookacti-delete-booking-dialog' ).dialog( 'open' );
}