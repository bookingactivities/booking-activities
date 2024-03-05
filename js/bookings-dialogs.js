$j( document ).ready( function() {
	/**
	 * Init booking actions
	 */
	bookacti_init_booking_actions();
	bookacti_init_booking_bulk_actions();
	
	
	/**
	 * Open export bookings dialog - on click
	 * @since 1.6.0
	 * @version 1.8.0
	 */
	$j( '.bookacti-export-bookings-button' ).on( 'click', function() {
		bookacti_dialog_export_bookings();
	});
	
	
	/**
	 * Do not empty the export bookings dialog when the dialog is closing
	 * @since 1.15.5
	 */
	$j( '#bookacti-export-bookings-dialog' ).dialog({ "beforeClose": function(){} });
	
	
	/**
	 * Do not empty the booking calendar dialog when the dialog is closing
	 * @since 1.15.6
	 */
	$j( '#bookacti-bookings-calendar-settings-dialog' ).dialog({ "beforeClose": function(){} });
	
	
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


// DIALOGS

/**
 * Bookings calendar settings
 * @since 1.8.0
 * @version 1.15.13
 */
function bookacti_dialog_update_bookings_calendar_settings() {	
	// Add the buttons
    $j( '#bookacti-bookings-calendar-settings-dialog' ).dialog( 'option', 'buttons',
		// OK button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() { 
				// Reset error notices
				$j( '#bookacti-bookings-calendar-settings-dialog .bookacti-notices' ).remove();
				
				var data = bookacti_serialize_object( $j( '#bookacti-bookings-calendar-settings-form' ) );
				
				$j( 'body' ).trigger( 'bookacti_bookings_calendar_settings_data', [ data ] );
				
				// Display a loader
				bookacti_add_loading_html( $j( '#bookacti-bookings-calendar-settings-dialog' ) );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function( response ) {
						if( response.status === 'success' ) {
							// Update booking system data
							var booking_system_id = 'bookacti-booking-system-bookings-page';
							bookacti.booking_system[ booking_system_id ][ 'display_data' ]                 = response.display_data;
							bookacti.booking_system[ booking_system_id ][ 'tooltip_booking_list' ]         = response.calendar_settings.tooltip_booking_list;
							bookacti.booking_system[ booking_system_id ][ 'tooltip_booking_list_columns' ] = response.calendar_settings.tooltip_booking_list_columns;
							
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
						bookacti_remove_loading_html( $j( '#bookacti-bookings-calendar-settings-dialog' ) );
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
 * Cancel bookings dialog
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_cancel_bookings( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'cancel' ] );
	
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
				
				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-cancel-booking-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiCancelBookings' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				data.form_data.append( 'user_auth_key', typeof bookacti.user_auth_key !== 'undefined' ? bookacti.user_auth_key : '' );

				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'cancel' ] );

				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_add_loading_html( $j( '#bookacti-cancel-booking-dialog' ) );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-cancel-booking-dialog' ).dialog( 'close' );
							
							// Replace the rows
							if( response.rows ) {
								var new_rows = $j( response.rows );
								rows.each( function() {
									var row_booking_id       = $j( this ).find( '.bookacti-single-booking' ).addBack( '.bookacti-single-booking' ).data( 'booking-id' );
									var row_booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
									var new_row_selector     = row_booking_id ? '.bookacti-single-booking[data-booking-id="' + row_booking_id + '"]' : ( row_booking_group_id ? '.bookacti-booking-group[data-booking-group-id="' + row_booking_group_id + '"]' : '' );
									var new_row              = new_row_selector ? new_rows.find( new_row_selector ).addBack( new_row_selector ).closest( 'tr' ) : $j();
									if( new_row.length ) {
										$j( this ).replaceWith( new_row );
									}
									if( row_booking_group_id ) {
										$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
									}
								});
								bookacti_refresh_list_table_hidden_columns();
							}
							rows.remove();
							
							$j( 'body' ).trigger( 'bookacti_bookings_cancelled', [ response, booking_selection ] );
							
							if( response.allow_refund ) {
								bookacti_dialog_refund_bookings( booking_selection );
							}
						
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-cancel-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-cancel-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-cancel-booking-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-cancel-booking-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
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
    $j( '#bookacti-cancel-booking-dialog' ).dialog( 'open' );
}


/**
 * Refund bookings
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_refund_bookings( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	// Empty current refund actions
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-options' ).empty();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-options-container' ).hide();
	$j( '#bookacti-refund-booking-dialog #bookacti-no-refund-option' ).hide();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount' ).empty();
	$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount-container' ).hide();
	$j( '#bookacti-refund-booking-dialog' ).dialog( 'option', 'buttons', [] );
	
	// Reset error notices
	$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).remove();

	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'refund' ] );
	
	var data_get_actions = { 'form_data': new FormData( $j( '#bookacti-refund-booking-form' ).get(0) ) };
	data_get_actions.form_data.append( 'action', 'bookactiGetBookingsRefundActionsHTML' );
	data_get_actions.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
	data_get_actions.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );

	$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data_get_actions, booking_selection, 'get_refund_actions' ] );
	
	// Display a loader
	bookacti_booking_row_enter_loading_state( rows );
	bookacti_add_loading_html( $j( '#bookacti-refund-booking-dialog' ) );
	
	// Get possible refund actions
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data_get_actions.form_data,
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		success: function( response ) {
			if( response.status === 'success' ) {
				if( ! response.actions_html ) {
					$j( '#bookacti-refund-booking-dialog #bookacti-no-refund-option' ).show();
					return;
				}
				
				// Display the refund options
				$j( '#bookacti-refund-booking-dialog #bookacti-refund-options' ).html( response.actions_html );
				$j( '#bookacti-refund-booking-dialog #bookacti-refund-options-container' ).show();

				// Display the refund amount
				if( response.amount ) {
					$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount' ).html( response.amount );
					$j( '#bookacti-refund-booking-dialog #bookacti-refund-amount-container' ).show();
				}

				// Check the first radio
				$j( '#bookacti-refund-booking-form #bookacti-refund-options input[type="radio"]:first' ).prop( 'checked', true );
				
				// Add the buttons
				$j( '#bookacti-refund-booking-dialog' ).dialog( 'option', 'buttons', [{
					text: bookacti_localized.dialog_button_refund,
					'class': 'bookacti-dialog-delete-button',
					click: function() {
						// Make sure the refund action is selected
						if( ! $j( '#bookacti-refund-booking-form input[name="refund_action"]' ).val() ) { return; }
						
						// Reset error notices
						$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).remove();

						// Columns to display
						var columns = [];
						rows.first().find( 'td' ).each( function() {
							var column_id = $j( this ).data( 'column-id' );
							if( column_id ) { columns.push( column_id ); }
						});
						
						var data_refund = { 'form_data': new FormData( $j( '#bookacti-refund-booking-form' ).get(0) ) };
						data_refund.form_data.append( 'action', 'bookactiRefundBookings' );
						data_refund.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
						data_refund.form_data.append( 'columns', JSON.stringify( columns ) );
						data_refund.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
						data_refund.form_data.append( 'user_auth_key', typeof bookacti.user_auth_key !== 'undefined' ? bookacti.user_auth_key : '' );

						$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data_refund, booking_selection, 'refund' ] );

						// Display a loader
						bookacti_booking_row_enter_loading_state( rows );
						bookacti_add_loading_html( $j( '#bookacti-refund-booking-dialog' ) );

						$j.ajax({
							url: bookacti_localized.ajaxurl,
							type: 'POST',
							data: data_refund.form_data,
							dataType: 'json',
							cache: false,
							contentType: false,
							processData: false,
							success: function( response ) {
								if( response.status === 'success' ) {
									// Close the modal dialog
									$j( '#bookacti-refund-booking-dialog' ).dialog( 'close' );

									// Replace the rows
									if( response.rows ) {
										var new_rows = $j( response.rows );
										rows.each( function() {
											var row_booking_id       = $j( this ).find( '.bookacti-single-booking' ).addBack( '.bookacti-single-booking' ).data( 'booking-id' );
											var row_booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
											var new_row_selector     = row_booking_id ? '.bookacti-single-booking[data-booking-id="' + row_booking_id + '"]' : ( row_booking_group_id ? '.bookacti-booking-group[data-booking-group-id="' + row_booking_group_id + '"]' : '' );
											var new_row              = new_row_selector ? new_rows.find( new_row_selector ).addBack( new_row_selector ).closest( 'tr' ) : $j();
											if( new_row.length ) {
												$j( this ).replaceWith( new_row );
											}
											if( row_booking_group_id ) {
												$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
											}
										});
										bookacti_refresh_list_table_hidden_columns();
										bookacti_init_tooltip();
									}
									rows.remove();
									
									// Notify user that his booking has been refunded
									if( response.message ) {
										bookacti_dialog_refund_confirmation( response.message );
									}
									
									// Refresh bookings calendar
									if( $j( '#bookacti-booking-system-bookings-page' ).length ) {
										bookacti_refresh_booking_numbers( $j( '#bookacti-booking-system-bookings-page' ) );
									}
								
									$j( 'body' ).trigger( 'bookacti_bookings_refunded', [ response, booking_selection ] );

									// If the rows have not been refresh, reload the page
									if( ! response.rows ) {
										window.location.reload();
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
								bookacti_remove_loading_html( $j( '#bookacti-refund-booking-dialog' ) );
								bookacti_booking_row_exit_loading_state( rows );
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
				}] );
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-refund-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			$j( '#bookacti-refund-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			$j( '#bookacti-refund-booking-dialog .bookacti-notices' ).show();
			bookacti_remove_loading_html( $j( '#bookacti-refund-booking-dialog' ) );
			bookacti_booking_row_exit_loading_state( rows );
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
 * Change Bookings Status
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_change_bookings_status( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	// Select the current status
	var booking_status = rows.length === 1 ? rows.find( '.bookacti-booking-status' ).data( 'booking-status' ) : '';
	var payment_status = rows.length === 1 ? rows.find( '.bookacti-payment-status' ).data( 'payment-status' ) : '';
	$j( 'select#bookacti-select-booking-status' ).val( booking_status );
	$j( 'select#bookacti-select-payment-status' ).val( payment_status );
	$j( '#bookacti-send-notifications-on-status-change' ).prop( 'checked', false );

	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'change_booking_status' ] );

	// Add the buttons
    $j( '#bookacti-change-booking-status-dialog' ).dialog( 'option', 'buttons',
		// Change booking status button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				// Check if the status have changed
				var new_booking_status = $j( 'select#bookacti-select-booking-status' ).val(); 
				var new_payment_status = $j( 'select#bookacti-select-payment-status' ).val();
				if( ( ! new_booking_status && ! new_payment_status ) 
				 || ( new_booking_status === booking_status && new_payment_status === payment_status ) ) { return; }

				// Reset error notices
				$j( '#bookacti-change-booking-status-dialog .bookacti-notices' ).remove();

				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-change-booking-status-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiChangeBookingsStatus' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				
				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'change_booking_status' ] );
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_add_loading_html( $j( '#bookacti-change-booking-status-dialog' ) );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-change-booking-status-dialog' ).dialog( 'close' );

							// Replace the rows
							if( response.rows ) {
								var new_rows = $j( response.rows );
								rows.each( function() {
									var row_booking_id       = $j( this ).find( '.bookacti-single-booking' ).addBack( '.bookacti-single-booking' ).data( 'booking-id' );
									var row_booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
									var new_row_selector     = row_booking_id ? '.bookacti-single-booking[data-booking-id="' + row_booking_id + '"]' : ( row_booking_group_id ? '.bookacti-booking-group[data-booking-group-id="' + row_booking_group_id + '"]' : '' );
									var new_row              = new_row_selector ? new_rows.find( new_row_selector ).addBack( new_row_selector ).closest( 'tr' ) : $j();
									if( new_row.length ) {
										$j( this ).replaceWith( new_row );
									}
									if( row_booking_group_id ) {
										$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
									}
								});
								bookacti_refresh_list_table_hidden_columns();
								bookacti_init_tooltip();
							}
							rows.remove();
							
							// Update booking status
							if( new_booking_status ) {
								$j( 'body' ).trigger( 'bookacti_bookings_status_changed', [ response, booking_selection, new_booking_status ] );
								
								// Refresh bookings calendar
								if( $j( '#bookacti-booking-system-bookings-page' ).length ) {
									bookacti_refresh_booking_numbers( $j( '#bookacti-booking-system-bookings-page' ) );
								}
							}

							// Update payment status
							if( new_payment_status ) {
								$j( 'body' ).trigger( 'bookacti_bookings_payment_status_changed', [ response, booking_selection, new_payment_status ] );
							}
							
							// If the rows have not been refresh, reload the page
							if( ! response.rows ) {
								window.location.reload();
							}

						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-change-booking-status-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-change-booking-status-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-change-booking-status-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-change-booking-status-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
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
    $j( '#bookacti-change-booking-status-dialog' ).dialog( 'open' );
}


/**
 * Change Bookings quantity
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_change_bookings_quantity( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	// Set the current quantity
	var current_quantity = rows.length === 1 ? parseInt( rows.find( '.column-quantity' ).text() ) : 1;
	$j( '#bookacti-new-quantity' ).val( current_quantity );
	
	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'change_quantity' ] );
	
	// Add the buttons
    $j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'option', 'buttons',
		// Change booking quantity button
		[{
			text: bookacti_localized.dialog_button_ok,
			click: function() {
				var new_quantity = parseInt( $j( '#bookacti-new-quantity' ).val() );
				if( ! new_quantity ) { return; }
				
				// Reset error notices
				$j( '#bookacti-change-booking-quantity-dialog .bookacti-notices' ).remove();
				
				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-change-booking-quantity-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiChangeBookingsQuantity' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				
				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'change_quantity' ] );
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_add_loading_html( $j( '#bookacti-change-booking-quantity-dialog' ) );
				
				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'close' );
							
							// Replace the rows
							if( response.rows ) {
								var new_rows = $j( response.rows );
								rows.each( function() {
									var row_booking_id       = $j( this ).find( '.bookacti-single-booking' ).addBack( '.bookacti-single-booking' ).data( 'booking-id' );
									var row_booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
									var new_row_selector     = row_booking_id ? '.bookacti-single-booking[data-booking-id="' + row_booking_id + '"]' : ( row_booking_group_id ? '.bookacti-booking-group[data-booking-group-id="' + row_booking_group_id + '"]' : '' );
									var new_row              = new_row_selector ? new_rows.find( new_row_selector ).addBack( new_row_selector ).closest( 'tr' ) : $j();
									if( new_row.length ) {
										$j( this ).replaceWith( new_row );
									}
									if( row_booking_group_id ) {
										$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
									}
								});
								bookacti_refresh_list_table_hidden_columns();
								bookacti_init_tooltip();
							}
							rows.remove();
							
							// Trigger a hook for booking quantity changes
							$j( 'body' ).trigger( 'bookacti_bookings_quantity_changed', [ response, booking_selection, new_quantity ] );
							
							// Refresh bookings calendar
							if( $j( '#bookacti-booking-system-bookings-page' ).length ) {
								bookacti_refresh_booking_numbers( $j( '#bookacti-booking-system-bookings-page' ) );
							}
							
							// If the rows have not been refresh, reload the page
							if( ! response.rows ) {
								window.location.reload();
							}

						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-change-booking-quantity-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-change-booking-quantity-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-change-booking-quantity-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-change-booking-quantity-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
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
    $j( '#bookacti-change-booking-quantity-dialog' ).dialog( 'open' );
}


/**
 * Reschedule bookings dialog
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_reschedule_bookings( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	var booking_system    = $j( '#bookacti-booking-system-reschedule.bookacti-booking-system' );
	var booking_system_id = booking_system.attr( 'id' );
	var booking_quantity  = 1;
	
	// Clear and reset dialog
	booking_system.empty();
	bookacti_clear_booking_system_displayed_info( booking_system );
	$j( '#bookacti-reschedule-booking-dialog > .bookacti-notices' ).remove();
	$j( '#bookacti-reschedule-booking-dialog .bookacti-booking-system-container .bookacti-notices' ).empty().hide();
	if( bookacti_localized.is_admin ) {
		$j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked', false );
	}
	
	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'reschedule' ] );

	var data_get_booking_system = { 'form_data': new FormData( $j( '#bookacti-reschedule-booking-form' ).get(0) ) };
	data_get_booking_system.form_data.append( 'action', 'bookactiGetRescheduleBookingSystemData' );
	data_get_booking_system.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
	data_get_booking_system.form_data.append( 'user_auth_key', typeof bookacti.user_auth_key !== 'undefined' ? bookacti.user_auth_key : '' );
	data_get_booking_system.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );

	$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data_get_booking_system, booking_selection, 'get_reschedule_booking_system_data' ] );
	
	// Display a loader
	bookacti_booking_row_enter_loading_state( rows );
	bookacti_add_loading_html( $j( '#bookacti-reschedule-booking-dialog' ), 'prepend' );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: data_get_booking_system.form_data,
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		success: function( response ) {
			bookacti_remove_loading_html( $j( '#bookacti-reschedule-booking-dialog' ) );
			
			if( response.status === 'success' ) {
				booking_quantity = response.quantity;
				booking_system.closest( 'form' ).find( 'input.bookacti-quantity' ).val( booking_quantity );
				
				bookacti.booking_system[ booking_system_id ] = response.booking_system_data;
				
				$j( 'body' ).trigger( 'bookacti_before_reschedule_booking_system_loads', [ response ] );
				
				// Load booking system with new data
				bookacti_reload_booking_system( booking_system );
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
				$j( '#bookacti-reschedule-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			bookacti_remove_loading_html( $j( '#bookacti-reschedule-booking-dialog' ) );
			$j( '#bookacti-reschedule-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
			console.log( 'AJAX ' + bookacti_localized.error );
			console.log( e );
		},
		complete: function() {
			$j( '#bookacti-reschedule-booking-dialog > .bookacti-notices' ).show();
			bookacti_booking_row_exit_loading_state( rows );
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
				
				// Reset error notices
				$j( '#bookacti-reschedule-booking-dialog > .bookacti-notices' ).remove();
				
				// Groups cannot be rescheduled
				var is_group = false;
				var picked_events = bookacti.booking_system[ booking_system_id ][ 'picked_events' ];
				$j.each( picked_events, function( i, picked_event ) {
					if( parseInt( picked_event[ 'group_id' ] ) > 0 ) {
						is_group = true;
						return false; // break
					}
				});
				if( is_group ) { return; }
				
				var send_notifications = 1;
				if( bookacti_localized.is_admin && $j( '#bookacti-send-notifications-on-reschedule' ).length ) {
					send_notifications = $j( '#bookacti-send-notifications-on-reschedule' ).prop( 'checked' ) ? 1 : 0; 
				}
				
				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-reschedule-booking-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiRescheduleBookings' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'picked_events', JSON.stringify( picked_events ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				data.form_data.append( 'send_notifications', send_notifications );
				data.form_data.append( 'user_auth_key', typeof bookacti.user_auth_key !== 'undefined' ? bookacti.user_auth_key : '' );
				
				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'reschedule' ] );

				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_start_loading_booking_system( booking_system );
				bookacti_add_loading_html( $j( '#bookacti-reschedule-booking-dialog' ) );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'close' );

							// Replace the rows
							if( response.rows ) {
								var new_rows = $j( response.rows );
								rows.each( function() {
									var row_booking_id       = $j( this ).find( '.bookacti-single-booking' ).addBack( '.bookacti-single-booking' ).data( 'booking-id' );
									var row_booking_group_id = $j( this ).find( '.bookacti-booking-group' ).addBack( '.bookacti-booking-group' ).data( 'booking-group-id' );
									var new_row_selector     = row_booking_id ? '.bookacti-single-booking[data-booking-id="' + row_booking_id + '"]' : ( row_booking_group_id ? '.bookacti-booking-group[data-booking-group-id="' + row_booking_group_id + '"]' : '' );
									var new_row              = new_row_selector ? new_rows.find( new_row_selector ).addBack( new_row_selector ).closest( 'tr' ) : $j();
									if( new_row.length ) {
										$j( this ).replaceWith( new_row );
									}
									if( row_booking_group_id ) {
										$j( '.bookacti-gouped-booking[data-booking-group-id="' + row_booking_group_id + '"]' ).remove();
									}
								});
								bookacti_refresh_list_table_hidden_columns();
								bookacti_init_tooltip();
							}
							rows.remove();
							
							// Refresh bookings calendar
							if( $j( '#bookacti-booking-system-bookings-page' ).length ) {
								bookacti_booking_method_refetch_events( $j( '#bookacti-booking-system-bookings-page' ) );
								bookacti_refresh_booking_numbers( $j( '#bookacti-booking-system-bookings-page' ) );
							}
						
							$j( 'body' ).trigger( 'bookacti_bookings_rescheduled', [ response, booking_selection ] );

						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-reschedule-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-reschedule-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-reschedule-booking-dialog > .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-reschedule-booking-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
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
	
	// Open the modal dialog
	$j( '#bookacti-reschedule-booking-dialog' ).dialog( 'open' );
}


/**
 * Send a notification for bookings or booking groups
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_send_bookings_notification( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'send_notification' ] );
	
	// Add the buttons
    $j( '#bookacti-send-booking-notification-dialog' ).dialog( 'option', 'buttons',
		[{
			text: bookacti_localized.dialog_button_send,
			click: function() {
				var notification_id = $j( '#bookacti-booking-notification-id' ).val();
				if( ! notification_id ) { return; }
				
				// Reset error notices
				$j( '#bookacti-send-booking-notification-dialog .bookacti-notices' ).remove();
				
				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-send-booking-notification-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiSendBookingsNotification' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				
				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'send_notification' ] );
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_add_loading_html( $j( '#bookacti-send-booking-notification-dialog' ) );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							if( response.message ) {
								$j( '#bookacti-send-booking-notification-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + '</li></ul></div>' ).show();
							} else {
								// Close the modal dialog
								$j( '#bookacti-send-booking-notification-dialog' ).dialog( 'close' );
							}
							
							// Trigger a hook when the notifications are sent
							$j( 'body' ).trigger( 'bookacti_bookings_notification_sent', [ response, booking_selection, notification_id ] );
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-send-booking-notification-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' ).show();
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-send-booking-notification-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' ).show();
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-send-booking-notification-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-send-booking-notification-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
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
    $j( '#bookacti-send-booking-notification-dialog' ).dialog( 'open' );
}


/**
 * Delete bookings (groups)
 * @since 1.16.0
 * @param {Object} booking_selection
 */
function bookacti_dialog_delete_bookings( booking_selection ) {
	// Get booking rows
	var rows = booking_selection.all ? $j( '.bookacti-single-booking, .bookacti-booking-group' ).closest( 'tr, .bookacti-booking-row' ) : $j();
	
	if( ! booking_selection.all ) {
		$j.each( booking_selection.booking_ids, function( i, booking_id ) {
			rows = rows.add( $j( '.bookacti-single-booking[data-booking-id="' + booking_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
		$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
			rows = rows.add( $j( '.bookacti-booking-group[data-booking-group-id="' + booking_group_id + '"]' ).closest( 'tr, .bookacti-booking-row' ) );
		});
	}
	
	$j( '#bookacti-delete-booking-group-warning' ).toggle( booking_selection.all || booking_selection.booking_group_ids.length );
	
	$j( 'body' ).trigger( 'bookacti_booking_action_dialog_opened', [ booking_selection, 'delete' ] );
	
	// Add the buttons
    $j( '#bookacti-delete-booking-dialog' ).dialog( 'option', 'buttons',
		// Delete button
		[{
			text: bookacti_localized.dialog_button_delete,
			'class': 'bookacti-dialog-delete-button',
			click: function() {
				// Reset error notices
				$j( '#bookacti-delete-booking-dialog .bookacti-notices' ).remove();
				
				// Columns to display
				var columns = [];
				rows.first().find( 'td' ).each( function() {
					var column_id = $j( this ).data( 'column-id' );
					if( column_id ) { columns.push( column_id ); }
				});
				
				var data = { 'form_data': new FormData( $j( '#bookacti-delete-booking-form' ).get(0) ) };
				data.form_data.append( 'action', 'bookactiDeleteBookings' );
				data.form_data.append( 'booking_selection', JSON.stringify( booking_selection ) );
				data.form_data.append( 'columns', JSON.stringify( columns ) );
				data.form_data.append( 'is_admin', bookacti_localized.is_admin ? 1 : 0 );
				
				$j( 'body' ).trigger( 'bookacti_booking_action_data', [ data, booking_selection, 'delete' ] );
				
				// Display a loader
				bookacti_booking_row_enter_loading_state( rows );
				bookacti_add_loading_html( $j( '#bookacti-delete-booking-dialog' ) );

				$j.ajax({
					url: bookacti_localized.ajaxurl,
					type: 'POST',
					data: data.form_data,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					success: function( response ) {
						if( response.status === 'success' ) {
							// Close the modal dialog
							$j( '#bookacti-delete-booking-dialog' ).dialog( 'close' );
							
							// Remove the rows
							rows.animate( {'opacity': 0}, function() { rows.children('td, th').animate({ 'padding': 0 }).wrapInner('<div></div>').children().slideUp(function() { rows.remove(); }); });
							
							// Remove the grouped booking rows
							$j.each( booking_selection.booking_group_ids, function( i, booking_group_id ) {
								$j( '.bookacti-gouped-booking[data-booking-group-id="' + booking_group_id + '"]' ).remove();
							});
							
							// Trigger a hook when the bookings are deleted
							$j( 'body' ).trigger( 'bookacti_bookings_deleted', [ response, booking_selection ] );
							
							// Refresh grouped bookings visual frame
							bookacti_refresh_booking_group_frame();
							
							// If all rows have been deleted, reload the page
							if( booking_selection.all ) {
								window.location.reload();
							}
							
						} else if( response.status === 'failed' ) {
							var error_message = typeof response.message !== 'undefined' ? response.message : bookacti_localized.error;
							$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
							console.log( error_message );
							console.log( response );
						}
					},
					error: function( e ) {
						$j( '#bookacti-delete-booking-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + 'AJAX ' + bookacti_localized.error + '</li></ul></div>' );
						console.log( 'AJAX ' + bookacti_localized.error );
						console.log( e );
					},
					complete: function() {
						$j( '#bookacti-delete-booking-dialog .bookacti-notices' ).show();
						bookacti_remove_loading_html( $j( '#bookacti-delete-booking-dialog' ) );
						bookacti_booking_row_exit_loading_state( rows );
					}
				});
			}
		},
		// Cancel button
		{
            text: bookacti_localized.dialog_button_cancel,
            'class': 'bookacti-dialog-left-button',
            click: function() {
				$j( this ).dialog( 'close' );
            }
        }]
    );
	
	// Open the modal dialog
    $j( '#bookacti-delete-booking-dialog' ).dialog( 'open' );
}


/**
 * Export bookings dialog
 * @since 1.6.0
 * @version 1.15.5
 */
function bookacti_dialog_export_bookings() {
	// Change the export type according to the selected tab
	$j( '#bookacti-export-bookings-url-container' ).data( 'export-type', '' );
	bookacti_change_export_type_according_to_active_tab();
	
	// Reset URL
	$j( '#bookacti_export_bookings_url_secret' ).val( '' );
	$j( '#bookacti-export-bookings-url-container' ).hide();
	
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
 * @version 1.15.13
 * @param {string} reset_key
 */
function bookacti_generate_export_bookings_url( reset_key ) {
	reset_key = reset_key || false;
	
	// Reset error notices
	$j( '#bookacti-export-bookings-dialog .bookacti-notices' ).remove();

	// Display a loader
	bookacti_add_loading_html( $j( '#bookacti-export-bookings-dialog' ) );
	
	// Get current filters and export settings
	var data = bookacti_serialize_object( $j( '#bookacti-export-bookings-form' ) );
	data.action = 'bookactiExportBookingsUrl';
	data.reset_key = reset_key ? 1 : 0;
	data.booking_filters = bookacti_serialize_object( $j( '#bookacti-booking-list-filters-form' ) );
	
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
			bookacti_remove_loading_html( $j( '#bookacti-export-bookings-dialog' ) );
		}
	});
}