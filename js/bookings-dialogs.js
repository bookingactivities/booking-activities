$j( document ).ready( function() {
	/**
	 * Init the Dialogs
	 */
	bookacti_init_bookings_dialogs();
	
	
	/**
	 * Init booking actions
	 */
	bookacti_init_booking_actions();
	
	
	if( $j( '.bookacti-bookings-bulk-action' ).length ) {	
		/**
		 * Init booking bulk actions
		 * @since 1.6.0
		 * @version 1.8.0
		 */
		bookacti_init_booking_bulk_actions();
	}
	
	
	/**
	 * Open export bookings dialog - on click
	 * @since 1.6.0
	 * @version 1.8.0
	 */
	$j( '.bookacti-export-bookings-button' ).on( 'click', function() {
		bookacti_dialog_export_bookings();
	});
	
	
	/**
	 * Open export link in a new tab to generate and download the exported file
	 * @since 1.6.0
	 * @version 1.8.0
	 */
	$j( '.bookacti_export_button input[type="button"]' ).on( 'click', function() {
		var url = $j( this ).closest( '.bookacti_export_url' ).find( '.bookacti_export_url_field input' ).val();
		if( url ) { window.open( url, '_blank' ); }
	});
	
	
	/**
	 * Change the export type according to the selected tab
	 * @since 1.8.0
	 * @param {Event} e
	 * @param {Object} ui
	 */
	$j( '#bookacti-export-bookings-dialog' ).on( 'tabsactivate', '.bookacti-tabs', function( e, ui ) {
		bookacti_change_export_type_according_to_active_tab();
	});
});


/**
 * Initialize bookings dialogs
 * @version 1.7.0
 */
function bookacti_init_bookings_dialogs() {
	// Common param
	$j( '.bookacti-bookings-dialog' ).dialog({ 
		"modal":		true,
		"autoOpen":		false,
		"minHeight":	300,
		"minWidth":		460,
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

/**
 * Bookings calendar settings
 * @since 1.8.0
 */
function bookacti_dialog_update_bookings_calendar_settings() {	
	// Reset error notices
	$j( '#bookacti-bookings-calendar-settings-dialog .bookacti-notices' ).remove();
	
	// Add the buttons
    $j( '#bookacti-bookings-calendar-settings-dialog' ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() { 
				// Reset error notices
				$j( '#bookacti-bookings-calendar-settings-dialog .bookacti-notices' ).remove();
				
				// Multiple select
				$j( '#bookacti-bookings-calendar-settings-dialog select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
				
				var data = $j( '#bookacti-bookings-calendar-settings-form' ).serializeObject();
				
				$j( 'body' ).trigger( 'bookacti_bookings_calendar_settings_data', [ data ] );
				
				// Display a loader
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-bookings-calendar-settings-dialog' ).append( loading_div );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Update booking system data
							var booking_system_id = 'bookacti-booking-system-bookings-page';
							bookacti.booking_system[ booking_system_id ][ 'display_data' ]			= response.display_data;
							bookacti.booking_system[ booking_system_id ][ 'tooltip_booking_list' ]		= response.calendar_settings.tooltip_booking_list;
							bookacti.booking_system[ booking_system_id ][ 'tooltip_booking_list_columns' ]	= response.calendar_settings.booking_list_columns;
							
							$j( 'body' ).trigger( 'bookacti_bookings_calendar_settings_updated', [ data, response ] );
							
							// Reload the calendar
							var booking_system = $j( '#bookacti-booking-system-bookings-page' );
							bookacti_reload_booking_system( booking_system, true );
							
							// Change the AJAX value
							$j( '#bookacti-submit-filter-button' ).attr( 'data-ajax', response.calendar_settings.ajax ).data( 'ajax', response.calendar_settings.ajax );
							
							// Close the modal dialog
							$j( '#bookacti-bookings-calendar-settings-dialog' ).dialog( 'close' );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : '';
							if( ! error_message ) {
								error_message += 'Error while trying to update calendar settings';
								var error_code = typeof response.error !== 'undefined' ? response.error : '';
								if( error_code ) {
									error_message += ' (' + error_code + ')';
								}
							}

							$j( '#bookacti-bookings-calendar-settings-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );

							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ){
						$j( '#bookacti-bookings-calendar-settings-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX error while trying to update calendar settings</li></ul></div>' );
						console.log( 'AJAX error while trying to update calendar settings' );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-bookings-calendar-settings-dialog .bookacti-notices' ).show();
						$j( '#bookacti-bookings-calendar-settings-dialog .bookacti-loading-alt' ).remove();
					}
				});
			}
		},
		// Cancel button 
		{
			text: bookacti_localized.dialog_button_cancel,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
    $j( '#bookacti-bookings-calendar-settings-dialog' ).dialog( 'open' );
}


/**
 * Cancel booking dialog
 * @version 1.12.2
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_cancel_booking( booking_id, booking_type ) {
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiCancelBookingGroup' : 'bookactiCancelBooking';
	
	// Reset error notices
	$j( '#bookacti-cancel-booking-dialog .bookacti-notices' ).remove();
	
	// Add the buttons
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'option', 'buttons',
		// Cancel booking button
		[{
			text: bookacti_localized.dialog_button_cancel_booking,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() { 
				// Reset error notices
				$j( '#bookacti-cancel-booking-dialog .bookacti-notices' ).remove();
				
				var row; var loading_container;
				if( booking_type === 'single' ) {
					row = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
					loading_container = $j( '.bookacti-cancel-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' );
				} else {
					row = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr' );
					loading_container = $j( '.bookacti-cancel-booking-group[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' );
				}
				
				// Columns to display
				var columns = [];
				row.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 
					'action': action, 
					'booking_id': booking_id,
					'context': bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list',
					'columns': columns,
					'nonce': $j( '#bookacti_nonce_cancel_booking' ).val()
				};
				row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, booking_type, 'cancel' ] );
				
				// Display a loader
				var loading_div = 
				'<div class="bookacti-loading-alt">' 
					+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
					+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
				+ '</div>';
				$j( '#bookacti-cancel-booking-dialog' ).append( loading_div );
				bookacti_booking_row_enter_loading_state( loading_container );
								
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Replace the row
							if( response.row ) {
								row.last().after( response.row );
								row.remove();
								bookacti_refresh_list_table_hidden_columns();
							}
							
							// Close the modal dialog
							$j( '#bookacti-cancel-booking-dialog' ).dialog( 'close' );
							
							if( response.allow_refund ) {
								bookacti_dialog_refund_booking( booking_id, booking_type );
							}
						
							$j( 'body' ).trigger( 'bookacti_booking_cancelled_by_user', [ booking_id, booking_type ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-cancel-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
						
					},
					error: function( e ) {
						$j( '#bookacti-cancel-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-cancel-booking-dialog .bookacti-notices' ).show();
						$j( '#bookacti-cancel-booking-dialog .bookacti-loading-alt' ).remove();
						bookacti_booking_row_exit_loading_state( loading_container );
					}
				});
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
		}]
	);
	
	// Open the modal dialog
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'open' );
}


/**
 * Refund a cancelled booking
 * @version 1.12.2
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_refund_booking( booking_id, booking_type ) {
	// Sanitize booking_type
	booking_type		= booking_type === 'group' ? 'group' : 'single';
	var action_html		= booking_type === 'group' ? 'bookactiGetBookingGroupRefundActionsHTML' : 'bookactiGetBookingRefundActionsHTML';
	var action_refund	= booking_type === 'group' ? 'bookactiRefundBookingGroup' : 'bookactiRefundBooking';
	
	// Reset error notices
	$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).remove();
	
	// Empty current refund actions
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-options' ).empty();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-options-container' ).hide();
	$j( '#bookacti-refund-booking-dialog #bookacti-no-refund-option' ).hide();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount' ).empty();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount-container' ).hide();
	
	var loading_div = '<div class="bookacti-loading-alt">' 
						+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
						+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	$j( '#bookacti-refund-booking-dialog' ).append( loading_div );
	
	// Get possible refund actions
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': action_html, 
				'booking_id': booking_id,
				'is_admin': bookacti_localized.is_admin ? 1 : 0,
				'nonce': $j( '#nonce_refund_booking' ).val()
			},
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				var buttons = [];
				
				// Add refund booking button if a refund method is available
				if( response.actions_html ) {
					// Display the refund options
					$j( '#bookacti-refund-booking-dialog #bookacti-refund-options' ).html( response.actions_html );
					$j( '#bookacti-refund-booking-dialog #bookacti-refund-options-container' ).show();
					
					// Display the refund amount
					if( response.amount ) {
						$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount' ).html( response.amount );
						$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount-container' ).show();
					}
					
					// Check the first radio
					$j( '#bookacti-refund-booking-form input[type="radio"]:first' ).prop( 'checked', true );
					
					buttons.push( {
						'text': bookacti_localized.dialog_button_refund,
						'class': 'bookacti-dialog-delete-button',

						click: function() {
							// Reset error notices
							$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).remove();
							
							var row; var loading_container;
							if( booking_type === 'single' ) {
								row = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
								loading_container = $j( '.bookacti-refund-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' );
							} else {
								row = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr' );
								loading_container = $j( '.bookacti-refund-booking-group[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' );
							}
							
							var is_bookings_page = row.first().closest( '#bookacti-booking-list' ).length ? 1 : 0;

							// Booking page specific data
							var reload_grouped_bookings = 0;
							if( is_bookings_page ) {
								reload_grouped_bookings = row.next().hasClass( 'bookacti-gouped-booking' ) ? 1 : 0;
							}
							
							// Display a loader
							loading_container.find( '.bookacti-booking-state' ).hide();
							loading_container.find( '.bookacti-booking-state' ).after( loading_div );
							$j( '#bookacti-refund-booking-dialog' ).append( loading_div );
							
							var refund_action	= $j( '#bookacti-refund-booking-form input[name="refund-action"]:checked' ).val();
							var refund_message	= $j( '#bookacti-refund-message textarea[name="refund-message"]' ).val();
							
							// Columns to display
							var columns = [];
							row.first().find( 'td' ).each( function() {
								var column_id = $j( this ).data( 'column-id' );
								if( column_id ) { columns.push( column_id ); }
							});
							
							var data = { 
								'action': action_refund, 
								'booking_id': booking_id,
								'refund_action': refund_action,
								'refund_message': refund_message,
								'reload_grouped_bookings': reload_grouped_bookings,
								'is_admin': bookacti_localized.is_admin ? 1 : 0,
								'context': bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list',
								'columns': columns,
								'nonce': $j( '#nonce_refund_booking' ).val()
							};
							row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, booking_type, 'refund' ] );
							
							$j.ajax({
								url: bookacti_localized.ajaxurl,
								type: 'POST',
								data: data,
								dataType: 'json',
								success: function( response ) {
									if( response.status === 'success' ) {
										// Replace old grouped bookings by new ones
										if( reload_grouped_bookings && typeof response.grouped_booking_rows !== 'undefined' ) {
											row.each( function() {
												var are_grouped_booking_hidden = $j( this ).next().hasClass( 'hidden' );
												$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).remove();
												$j( this ).after( response.grouped_booking_rows );
												if( are_grouped_booking_hidden ) {
													$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
													if( $j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
														$j( this ).after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
													}
												}
											});
										}
										
										// Replace the row
										if( response.row ) {
											row.last().after( response.row );
											row.remove();
											bookacti_refresh_list_table_hidden_columns();
										}
										
										$j( 'body' ).trigger( 'bookacti_booking_refunded', [ booking_id, booking_type, refund_action, refund_message, response ] );
										
										// Close the modal dialog
										$j( '#bookacti-refund-booking-dialog' ).dialog( 'close' );
										
										// Notify user that his booking has been refunded
										if( response.message ) {
											bookacti_dialog_refund_confirmation( response.message );
										}
										
									} else if( response.status === 'failed' ) {
										var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
										$j( '#bookacti-refund-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
										console.log( error_message );
										console.log( response );
									}
								},
								error: function( e ){
									console.log( 'AJAX ' + bookacti_localized.error );
									console.log( e );
								},
								complete: function() {
									$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).show();
									$j( '#bookacti-refund-booking-dialog .bookacti-loading-alt' ).remove();
									loading_container.find( '.bookacti-loading-alt' ).remove();
									loading_container.find( '.bookacti-booking-state' ).show();
								}
							});
						}
					});
				} else {
					$j( '#bookacti-refund-booking-dialog #bookacti-no-refund-option' ).show();
				}
				
				// Cancel button
				buttons.push( {
					text: bookacti_localized.dialog_button_cancel,
					click: function() {
						$j( this ).dialog( 'close' );
					}
				});
				
				// Add the buttons
				$j( '#bookacti-refund-booking-dialog' ).dialog( 'option', 'buttons', buttons );
				
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-refund-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			$j( '#bookacti-refund-booking-dialog .bookacti-loading-alt' ).remove();
			$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).show();
		}
	});
	
	// Open the modal dialog
	$j( '#bookacti-refund-booking-dialog' ).dialog( 'open' );
}


/**
 * Confirmation dialog after refund
 * @version 1.8.0
 * @param {string} message
 */
function bookacti_dialog_refund_confirmation( message ) {
	// Fill the dialog
	$j( '#bookacti-refund-booking-confirm-dialog' ).html( message );
	
	// Add the buttons
	$j( '#bookacti-refund-booking-confirm-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				$j( this ).dialog( 'close' );
			}
		}]
	);
	
	// Open the modal dialog
	$j( '#bookacti-refund-booking-confirm-dialog' ).dialog( 'open' );
}


/**
 * Change Booking State
 * @version 1.12.3
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_change_booking_state( booking_id, booking_type ) {
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	var action	= booking_type === 'group' ? 'bookactiChangeBookingGroupState' : 'bookactiChangeBookingState';
	
	// Get current row and actions container
	var row;
	if( booking_type === 'single' ) {
		row = $j( '.bookacti-change-booking-state[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
	} else {
		row = $j( '.bookacti-change-booking-group-state[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr' );
	}
	
	var is_bookings_page = row.first().closest( '#bookacti-booking-list' ).length ? 1 : 0;
	var reload_grouped_bookings = is_bookings_page && row.next().hasClass( 'bookacti-gouped-booking' ) ? 1 : 0;
	
	// Select the current state
	var booking_state	= row.first().find( '.bookacti-booking-state' ).data( 'booking-state' );
	var payment_status	= row.first().find( '.bookacti-payment-status' ).data( 'payment-status' );
	if( booking_state )	{ $j( '#bookacti-select-booking-state option[value="' + booking_state + '"]' ).prop( 'selected', true ); }
	if( payment_status ){ $j( '#bookacti-select-payment-status option[value="' + payment_status + '"]' ).prop( 'selected', true ); }
	$j( '#bookacti-send-notifications-on-state-change' ).prop( 'checked', false );
	
	// Reset error notices
	$j( '#bookacti-change-booking-state-dialog .bookacti-notices' ).remove();
	
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
					
					// Reset error notices
					$j( '#bookacti-change-booking-state-dialog .bookacti-notices' ).remove();
					
					// Columns to display
					var columns = [];
					row.first().find( 'td' ).each( function() {
						var column_id = $j( this ).data( 'column-id' );
						if( column_id ) { columns.push( column_id ); }
					});
					
					var data = { 
						'action': action, 
						'booking_id': booking_id,
						'new_booking_state': booking_state !== new_booking_state ? new_booking_state : 0,
						'new_payment_status': new_payment_status,
						'send_notifications': send_notifications,
						'is_bookings_page': is_bookings_page,
						'is_admin': bookacti_localized.is_admin ? 1 : 0,
						'context': bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list',
						'columns': columns,
						'reload_grouped_bookings': reload_grouped_bookings,
						'nonce': nonce
					};
					row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, booking_type, 'change_state' ] );
					
					// Display a loader
					bookacti_booking_row_enter_loading_state( row );
					
					// Display a loader
					var loading_div = '<div class="bookacti-loading-alt">' 
										+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
										+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
									+ '</div>';
					$j( '#bookacti-change-booking-state-dialog' ).append( loading_div );
					
					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: data,
						dataType: 'json',
						success: function( response ) {
							if( response.status === 'success' ) {
								// Close the modal dialog
								$j( '#bookacti-change-booking-state-dialog' ).dialog( 'close' );
								
								// Replace old grouped bookings by new ones
								if( reload_grouped_bookings && typeof response.grouped_booking_rows !== 'undefined' ) {
									row.each( function() {
										var are_grouped_booking_hidden = $j( this ).next().hasClass( 'hidden' );
										$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).remove();
										$j( this ).after( response.grouped_booking_rows );
										if( are_grouped_booking_hidden ) {
											$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
											if( $j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
												$j( this ).after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
											}
										}
									});
								}
								
								// Replace the row
								if( response.row ) {
									row.last().after( response.row );
									row.remove();
									bookacti_refresh_list_table_hidden_columns();
								}
								
								// Update booking state
								if( booking_state !== new_booking_state ) {
									$j( 'body' ).trigger( 'bookacti_booking_state_changed', [ booking_id, booking_type, new_booking_state, booking_state, is_bookings_page, response.active_changed ] );
								}
								
								// Update payment status
								if( payment_status !== new_payment_status ) {
									$j( 'body' ).trigger( 'bookacti_payment_status_changed', [ booking_id, booking_type, new_payment_status, payment_status, is_bookings_page, response.active_changed ] );
								}
								
							} else if( response.status === 'failed' ) {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								$j( '#bookacti-change-booking-state-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
								console.log( error_message );
								console.log( response );
							}

						},
						error: function( e ){
							$j( '#bookacti-change-booking-state-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
							console.log( 'AJAX ' + bookacti_localized.error );
							console.log( e );
						},
						complete: function() {
							$j( '#bookacti-change-booking-state-dialog .bookacti-notices' ).show();
							$j( '#bookacti-change-booking-state-dialog .bookacti-loading-alt' ).remove();
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
				// Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
	
	// Open the modal dialog
    $j( '#bookacti-change-booking-state-dialog' ).dialog( 'open' );
}


/**
 * Change Booking quantity
 * @since 1.7.10
 * @version 1.12.3
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_change_booking_quantity( booking_id, booking_type ) {
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	
	// Get current row and actions container
	var row;
	if( booking_type === 'single' ) {
		row = $j( '.bookacti-change-booking-quantity[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
	} else {
		row = $j( '.bookacti-change-booking-group-quantity[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr' );
	}
	
	var is_bookings_page = row.first().closest( '#bookacti-booking-list' ).length ? 1 : 0;
	var reload_grouped_bookings = is_bookings_page && row.next().hasClass( 'bookacti-gouped-booking' ) ? 1 : 0;
	
	// Select the current quantity
	var current_quantity = parseInt( row.first().find( '.column-quantity' ).text() );
	$j( '#bookacti-new-quantity' ).val( current_quantity );
	
	// Reset error notices
	$j( '#bookacti-change-booking-quantity-dialog .bookacti-notices' ).remove();
	
	row.first().trigger( 'bookacti_booking_action_dialog_opened', [ booking_id, booking_type, 'change_quantity' ] );
	
	// Add the buttons
    $j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'option', 'buttons',
		[
		// Change booking quantity button
		{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				var new_quantity= parseInt( $j( '#bookacti-new-quantity' ).val() );
				
				if( new_quantity ) {
					// Reset error notices
					$j( '#bookacti-change-booking-quantity-dialog .bookacti-notices' ).remove();
					
					// Columns to display
					var columns = [];
					row.first().find( 'td' ).each( function() {
						var column_id = $j( this ).data( 'column-id' );
						if( column_id ) { columns.push( column_id ); }
					});
					
					var data = $j( '#bookacti-change-booking-quantity-form' ).serializeObject();
					data.action = booking_type === 'group' ? 'bookactiChangeBookingGroupQuantity' : 'bookactiChangeBookingQuantity';
					data.booking_id = booking_id;
					data.is_bookings_page = is_bookings_page;
					data.is_admin = bookacti_localized.is_admin ? 1 : 0;
					data.context = bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list';
					data.columns = columns;
					data.reload_grouped_bookings = reload_grouped_bookings;
					
					row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, booking_type, 'change_quantity' ] );
					
					// Display a loader
					bookacti_booking_row_enter_loading_state( row );
					
					// Display a loader
					var loading_div = '<div class="bookacti-loading-alt">' 
										+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
										+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
									+ '</div>';
					$j( '#bookacti-change-booking-quantity-dialog' ).append( loading_div );
	
					$j.ajax({
						url: bookacti_localized.ajaxurl,
						type: 'POST',
						data: data,
						dataType: 'json',
						success: function( response ) {
							if( response.status === 'success' ) {
								// Close the modal dialog
								$j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'close' );
								
								// Replace old grouped bookings by new ones
								if( reload_grouped_bookings && typeof response.grouped_booking_rows !== 'undefined' ) {
									row.each( function() {
										var are_grouped_booking_hidden = $j( this ).next().hasClass( 'hidden' );
										$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).remove();
										$j( this ).after( response.grouped_booking_rows );
										if( are_grouped_booking_hidden ) {
											$j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).addClass( 'hidden' );
											if( $j( this ).nextUntil( 'tr:not(.bookacti-gouped-booking)' ).length % 2 ) {
												$j( this ).after( '<tr class="bookacti-gouped-booking hidden dummy"></tr>' ); // Add a dummy tr to keep the alternate background
											}
										}
									});
								}
								
								// Replace the row
								if( response.row ) {
									row.last().after( response.row );
									row.remove();
									bookacti_refresh_list_table_hidden_columns();
								}
								
								// Trigger a hook for booking quantity changes
								$j( 'body' ).trigger( 'bookacti_booking_quantity_changed', [ booking_id, booking_type, new_quantity, current_quantity, is_bookings_page, response.active_changed ] );
								
							} else if( response.status === 'failed' ) {
								var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
								$j( '#bookacti-change-booking-quantity-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
								console.log( error_message );
								console.log( response );
							}

						},
						error: function( e ){
							$j( '#bookacti-change-booking-quantity-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
							console.log( 'AJAX ' + bookacti_localized.error );
							console.log( e );
						},
						complete: function() {
							$j( '#bookacti-change-booking-quantity-dialog .bookacti-notices' ).show();
							$j( '#bookacti-change-booking-quantity-dialog .bookacti-loading-alt' ).remove();
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
				// Close the modal dialog
				$j( this ).dialog( 'close' );
            }
        }]
    );
	
	// Open the modal dialog
    $j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'open' );
}



/**
 * Reschedule booking dialog
 * @version 1.12.3
 * @param {int} booking_id
 */
function bookacti_dialog_reschedule_booking( booking_id ) {
	var row					= $j( '.bookacti-reschedule-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
	var loading_container	= $j( '.bookacti-reschedule-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' );
	var booking_system		= $j( '#bookacti-booking-system-reschedule.bookacti-booking-system' );
	var booking_system_id	= booking_system.attr( 'id' );
	var booking_quantity	= 0;
	
	if( bookacti_localized.is_admin ) { $j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked', false ); }
	
	// Clear old booking system info
	booking_system.empty();
	bookacti_clear_booking_system_displayed_info( booking_system );
	
	// Display a loader
	bookacti_booking_row_enter_loading_state( loading_container );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiGetRescheduleBookingSystemData', 
				'booking_id': booking_id,
				'is_admin': bookacti_localized.is_admin ? 1 : 0
			},
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				var booking_system_id	= booking_system.attr( 'id' );
				booking_quantity		= response.booking_system_data.rescheduled_booking_data.quantity;
				
				booking_system.closest( 'form' ).find( 'input.bookacti-quantity' ).val( booking_quantity );
				
				bookacti.booking_system[ booking_system_id ] = response.booking_system_data;
				
				booking_system.trigger( 'bookacti_before_reschedule_booking_system_loads', [ response ] );
				
				// Load booking system with new data
				bookacti_reload_booking_system( booking_system );
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				booking_system.siblings( '.bookacti-notices' ).html( "<ul class='bookacti-error-list'><li>" + error_message + "</li></ul>").show();
				console.log( error_message );
				console.log( response );
			}
			
			// Open the modal dialog
			$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'open' );
		},
		error: function( e ){
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			bookacti_booking_row_exit_loading_state( loading_container );
		}
	});
	
	
	// Add the buttons
    $j( '#bookacti-reschedule-booking-dialog' ).dialog( 'option', 'buttons',
		// Reschedule booking button
		[{
			text: bookacti_localized.dialog_button_reschedule,
			'class': 'bookacti-dialog-delete-button',
			
			click: function() { 
				var validated = bookacti_validate_picked_events( booking_system, booking_quantity );
				if( ! validated ) { return; }
				
				// Groups cannot be rescheduled
				var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
				if( parseInt( picked_events[ 0 ][ 'group_id' ] ) > 0 ) { return; }
				
				var send_notifications	= 1;
				if( bookacti_localized.is_admin && $j( '#bookacti-send-notifications-on-reschedule' ).length ) {
					send_notifications = $j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked' ) ? 1 : 0; 
				}

				// Columns to display
				var columns = [];
				row.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});

				var data = { 
					'action': 'bookactiRescheduleBooking', 
					'booking_id': booking_id,
					'picked_events': picked_events,
					'columns': columns,
					'context': bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list',
					'is_admin': bookacti_localized.is_admin ? 1 : 0,
					'send_notifications': send_notifications,
					'nonce': $j( '#bookacti-reschedule-booking-dialog #nonce_reschedule_booking' ).val()
				};
				row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, 'single', 'reschedule' ] );

				// Display a loader
				bookacti_booking_row_enter_loading_state( loading_container );
				bookacti_start_loading_booking_system( booking_system );
				
				// Display a loader
				var loading_div = '<div class="bookacti-loading-alt">' 
									+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
									+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
								+ '</div>';
				$j( '#bookacti-reschedule-booking-dialog' ).append( loading_div );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ){
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'close' );

							// Replace the row
							if( response.row ) {
								row.last().after( response.row );
								row.remove();
								bookacti_refresh_list_table_hidden_columns();
							}

							$j( 'body' ).trigger( 'bookacti_booking_rescheduled', [ response ] );

						} else {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							booking_system.siblings( '.bookacti-notices' ).html( "<ul class='bookacti-error-list'><li>" + error_message + "</li></ul>").show();
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ){
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-reschedule-booking-dialog .bookacti-loading-alt' ).remove();
						bookacti_booking_row_exit_loading_state( loading_container );
						bookacti_stop_loading_booking_system( booking_system );
					}
				});
			}
		},
		// Cancel button
		{
            text: bookacti_localized.dialog_button_cancel,
            click: function() {
				$j( this ).dialog( 'close' );
            }
        }]
    );
}


/**
 * Delete a booking or a booking group
 * @since 1.5.0
 * @version 1.12.2
 * @param {int} booking_id
 * @param {string} booking_type
 */
function bookacti_dialog_delete_booking( booking_id, booking_type ) {
	// Sanitize booking_type
	booking_type= booking_type === 'group' ? 'group' : 'single';
	
	// Get current row and show / hide specific fields
	var row, action;
	if( booking_type === 'single' ) {
		action = 'bookactiDeleteBooking';
		row = $j( '.bookacti-delete-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr' );
		$j( '.bookacti-delete-booking-group-description' ).hide();
	} else {
		action = 'bookactiDeleteBookingGroup';
		row = $j( '.bookacti-delete-booking-group[data-booking-group-id="' + booking_id + '"]' ).closest( 'tr' );
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
				var data = $j( '#bookacti-delete-booking-dialog form' ).serializeObject();
				data.is_admin = bookacti_localized.is_admin ? 1 : 0;
				data.context = bookacti_localized.is_admin ? 'admin_booking_list' : 'user_booking_list';
				row.first().trigger( 'bookacti_booking_action_data', [ data, booking_id, booking_type, 'delete' ] );
				
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
								group_rows.removeClass( 'bookacti-gouped-booking' ).animate( {'opacity': 0}, function() { group_rows.children('td, th').animate({ 'padding': 0 }).wrapInner('<div></div>').children().slideUp( function() { group_rows.remove(); } ); });
							}
							
							// Remove the booking row
							row.animate( {'opacity': 0}, function() { row.children('td, th').animate({ 'padding': 0 }).wrapInner('<div></div>').children().slideUp(function() { row.remove(); }); });
							
							$j( 'body' ).trigger( 'bookacti_booking_deleted', [ booking_id, booking_type, response ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}

					},
					error: function( e ){
						$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-delete-booking-dialog .bookacti-notices' ).show();
						$j( '#bookacti-delete-booking-dialog .bookacti-loading-alt' ).remove();
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


/**
 * Export bookings dialog
 * @since 1.6.0
 * @version 1.8.0
 */
function bookacti_dialog_export_bookings() {
	// Change the export type according to the selected tab
	$j( '#bookacti-export-bookings-url-container' ).data( 'export-type', '' );
	bookacti_change_export_type_according_to_active_tab();
	
	// Reset URL
	$j( '#bookacti_export_bookings_url_secret' ).val( '' );
	$j( '#bookacti-export-bookings-url-container' ).hide();
	
	// Reset error notices
	$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).remove();
	
	// Add the buttons
	$j( '#bookacti-export-bookings-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			'text': bookacti_localized.dialog_button_generate_link,			
			'click': function() { 
				bookacti_generate_export_bookings_url( false );
			}
		},
		// Reset the address
		{
			'text': bookacti_localized.dialog_button_reset,
			'class': 'bookacti-dialog-delete-button bookacti-dialog-left-button',
			'click': function() { 
				bookacti_generate_export_bookings_url( true );
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#bookacti-export-bookings-dialog' ).dialog( 'open' );
}


/**
 * Generate the URL to export bookings
 * @since 1.6.0
 * @version 1.8.0
 * @param {string} reset_key
 */
function bookacti_generate_export_bookings_url( reset_key ) {
	reset_key = reset_key || false;
	
	// Reset error notices
	$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).remove();

	// Display a loader
	var loading_div = '<div class="bookacti-loading-alt">' 
						+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
						+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
					+ '</div>';
	$j( '#bookacti-export-bookings-dialog' ).append( loading_div );
	
	// Multiple select
	$j( '#bookacti-export-bookings-dialog select[multiple].bookacti-items-select-box option' ).prop( 'selected', true );
	
	// Get current filters and export settings
	var data = $j( '#bookacti-export-bookings-form' ).serializeObject();
	data.action = 'bookactiExportBookingsUrl';
	data.reset_key = reset_key ? 1 : 0;
	data.booking_filters = $j( '#bookacti-booking-list-filters-form' ).serializeObject();
	
	$j( '#bookacti-export-bookings-form' ).trigger( 'bookacti_export_bookings_url_data', [ data, reset_key ] );
	
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				$j( '#bookacti_export_bookings_url_secret' ).val( response.url );
				$j( '#bookacti-export-bookings-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' ).show();
				
				$j( '#bookacti-export-bookings-url-container' ).data( 'export-type', data.export_type );
				$j( '#bookacti-export-bookings-url-container' ).show();
				
				$j( '#bookacti-form-editor' ).trigger( 'bookacti_export_bookings_url', [ response ] );

			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-export-bookings-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			$j( '#bookacti-export-bookings-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).show();
			$j( '#bookacti-export-bookings-dialog .bookacti-loading-alt' ).remove();
		}
	});
}