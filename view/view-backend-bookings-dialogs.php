<?php 
/**
 * Backend booking dialogs
 * @version 1.15.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id='bookacti-bookings-calendar-settings-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php esc_html_e( 'Bookings page calendar settings', 'booking-activities' ); ?>'>
	<form id='bookacti-bookings-calendar-settings-form'>
		<input type='hidden' name='action' value='bookactiUpdateBookingsCalendarSettings'/>
		<?php
			wp_nonce_field( 'bookacti_update_bookings_calendar_settings', 'nonce_update_bookings_calendar_settings', false );
		?>
		<div class='bookacti-backend-settings-only-notice bookacti-warning'>
			<span class='dashicons dashicons-warning'></span>
			<span>
			<?php
				/* translators: %s is a link to the "booking form editor". */
				echo sprintf( esc_html__( 'These settings are used for the bookings page calendar only.', 'booking-activities' ) . ' ' .  esc_html__( 'For your frontend calendars, use the "Calendar" field settings in the desired %s.', 'booking-activities' ), '<a href="' . admin_url( 'admin.php?page=bookacti_forms' ) . '">' . esc_html__( 'booking form editor', 'booking-activities' ) . '</a>' );
			?>
			</span>
		</div>
		<?php
			$user_calendar_settings	= bookacti_format_bookings_calendar_settings( get_user_meta( get_current_user_id(), 'bookacti_bookings_calendar_settings', true ) );
			
			// Fill the array of tabs with their label, callback for content and display order
			$calendar_tabs = apply_filters( 'bookacti_bookings_calendar_dialog_tabs', array (
				array( 
					'label'      => esc_html__( 'Display', 'booking-activities' ),
					'id'         => 'display',
					'callback'   => 'bookacti_fill_bookings_calendar_dialog_display_tab',
					'parameters' => array( 'calendar_data' => $user_calendar_settings ),
					'order'      => 10
				),
				array(
					'label'      => esc_html__( 'Calendar', 'booking-activities' ),
					'id'         => 'calendar',
					'callback'   => 'bookacti_fill_bookings_calendar_dialog_calendar_tab',
					'parameters' => array( 'calendar_data' => $user_calendar_settings ),
					'order'      => 20
				)
			) );

			// Display tabs
			bookacti_display_tabs( $calendar_tabs, 'calendar' );

			/**
			 * Display the content of the "Display" tab of the "Bookings Calendar" dialog
			 * @since 1.8.0
			 * @since 1.15.4
			 * @param array $params
			 */
			function bookacti_fill_bookings_calendar_dialog_display_tab( $params ) {
				do_action( 'bookacti_bookings_calendar_dialog_display_tab_before', $params );
			?>
				<fieldset>
					<legend><?php esc_html_e( 'Display', 'booking-activities' ); ?></legend>
					<?php
						$display_fields = apply_filters( 'bookacti_bookings_calendar_display_fields', array( 
							'show' => array( 
								'name'  => 'show',
								'type'  => 'checkbox',
								'title' => esc_html__( 'Display the calendar by default', 'booking-activities' ),
								'value' => $params[ 'calendar_data' ][ 'show' ], 
								'tip'   => esc_html__( 'Display the calendar by default on the bookings page.', 'booking-activities' )
							),
							'ajax' => array( 
								'name'  => 'ajax',
								'type'  => 'checkbox',
								'title' => esc_html__( 'AJAX filtering', 'booking-activities' ),
								'value' => $params[ 'calendar_data' ][ 'ajax' ], 
								'tip'   => esc_html__( 'Automatically filter the booking list when you change a filter or select an event.', 'booking-activities' )
							),
						), $params[ 'calendar_data' ] );
						bookacti_display_fields( $display_fields );
					?>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Tooltip', 'booking-activities' ); ?></legend>
					<?php
						$undesired_columns = array( 'events', 'event_id', 'event_title', 'start_date', 'end_date', 'actions' );
						$event_booking_list_columns = array_diff_key( bookacti_get_user_booking_list_columns_labels(), array_flip( $undesired_columns ) );

						// Push the selected columns at the end of the options in the selected order
						$event_booking_list_columns_ordered = $event_booking_list_columns;
						foreach( $params[ 'calendar_data' ][ 'tooltip_booking_list_columns' ] as $col_name ) {
							if( isset( $event_booking_list_columns_ordered[ $col_name ] ) ) {
								$col_title = $event_booking_list_columns_ordered[ $col_name ];
								unset( $event_booking_list_columns_ordered[ $col_name ] );
								$event_booking_list_columns_ordered[ $col_name ] = $col_title;
							}
						}
						
						$tooltip_fields = apply_filters( 'bookacti_bookings_calendar_tooltip_fields', array( 
							'tooltip_booking_list' => array( 
								'name'  => 'tooltip_booking_list',
								'type'  => 'checkbox',
								'title' => esc_html__( 'Preview booking list', 'booking-activities' ),
								'value' => $params[ 'calendar_data' ][ 'tooltip_booking_list' ], 
								'tip'   => esc_html__( 'Display the event booking list when you mouse over an event.', 'booking-activities' )
							),
							'tooltip_booking_list_columns' => array( 
								'name'        => 'tooltip_booking_list_columns',
								'type'        => 'select',
								'id'          => 'bookacti-event-booking-list-columns',
								'class'       => 'bookacti-select2-no-ajax', 
								'multiple'    => 1,
								'attr'        => array( '<select>' => ' data-sortable="1"' ),
								'title'       => esc_html__( 'Preview booking list columns', 'booking-activities' ),
								'options'     => $event_booking_list_columns_ordered,
								'value'       => $params[ 'calendar_data' ][ 'tooltip_booking_list_columns' ],
								'tip'         => esc_html__( 'Add the columns in the order they will be displayed.', 'booking-activities' )
							)
						), $params[ 'calendar_data' ] );
						bookacti_display_fields( $tooltip_fields );
					?>
				</fieldset>
			<?php
				do_action( 'bookacti_bookings_calendar_dialog_display_tab_after', $params );
			}
			
			
			/**
			 * Display the content of the "Calendar" tab of the "Bookings Calendar" dialog
			 * @since 1.8.0
			 * @version 1.15.0
			 * @param array $params
			 */
			function bookacti_fill_bookings_calendar_dialog_calendar_tab( $params ) {
				do_action( 'bookacti_bookings_calendar_dialog_calendar_tab_before', $params );
			?>
				<fieldset>
					<legend><?php esc_html_e( 'Working time', 'booking-activities' ); ?></legend>
					<?php 
						$timeGrid_fields = bookacti_get_fullcalendar_fields_default_data( array( 'slotMinTime', 'slotMaxTime' ) );
						$timeGrid_fields[ 'slotMinTime' ][ 'value' ] = str_pad( intval( substr( $params[ 'calendar_data' ][ 'slotMinTime' ], 0, 2 ) ) % 24, 2, '0', STR_PAD_LEFT ) . substr( $params[ 'calendar_data' ][ 'slotMinTime' ], 2 );
						$timeGrid_fields[ 'slotMaxTime' ][ 'value' ] = str_pad( intval( substr( $params[ 'calendar_data' ][ 'slotMaxTime' ], 0, 2 ) ) % 24, 2, '0', STR_PAD_LEFT ) . substr( $params[ 'calendar_data' ][ 'slotMaxTime' ], 2 );
						bookacti_display_fields( $timeGrid_fields );
					?>
				</fieldset>
			<?php
				do_action( 'bookacti_bookings_calendar_dialog_calendar_tab_after', $params );
			}
			?>
		
		<div class='bookacti-hidden-field'>
			<?php bookacti_display_badp_promo(); ?>
		</div>
		<div class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' 
			 data-show-title='<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>'
			 data-hide-title='<?php esc_html_e( 'Hide advanced options', 'booking-activities' ); ?>'>
			<?php esc_html_e( 'Show advanced options', 'booking-activities' ); ?>
	   </div>
	</form>
</div>

<div id='bookacti-change-booking-state-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php esc_html_e( 'Change booking state', 'booking-activities' ); ?>'>
	<form id='bookacti-change-booking-state-form'>
		<?php
			wp_nonce_field( 'bookacti_change_booking_state', 'nonce_change_booking_state', false );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Booking state', 'booking-activities' ); ?></legend>
			<div>
				<label for='bookacti-select-booking-state' ><?php esc_html_e( 'Booking state', 'booking-activities' ); ?></label>
				<select name='select-booking-state' id='bookacti-select-booking-state' >
					<?php
					$booking_state_labels   = bookacti_get_booking_state_labels();
					$allowed_booking_states = apply_filters( 'bookacti_booking_states_you_can_manually_change', array( 'delivered', 'booked', 'pending', 'cancelled', 'refund_requested', 'refunded' ) );
					foreach( $allowed_booking_states as $state_key ) {
						$state_label = ! empty( $booking_state_labels[ $state_key ][ 'label' ] ) ? $booking_state_labels[ $state_key ][ 'label' ] : $state_key;
						echo '<option value="' . esc_attr( $state_key ) . '" >' . $state_label . '</option>';
					}
					?>
				</select>
			</div>
			<div>
				<label for='bookacti-send-notifications-on-state-change' ><?php esc_html_e( 'Send notifications', 'booking-activities' ); ?></label>
				<?php 
					$args = array(
						'type'  => 'checkbox',
						'name'  => 'send-notifications-on-state-change',
						'id'    => 'bookacti-send-notifications-on-state-change',
						'value' => 0,
						/* Translators: %s is a link to the "Notifications settings" */
						'tip'   => sprintf( esc_html__( 'Send the booking status change notifications configured in %s.', 'booking-activities' ), '<a href="' . admin_url( 'admin.php?page=bookacti_settings&tab=notifications' ) . '">' . esc_html__( 'Notifications settings', 'booking-activities' ) . '</a>' )
					);
					bookacti_display_field( $args );
				?>
			</div>
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'Payment status', 'booking-activities' ); ?></legend>
			<div>
				<label for='bookacti-select-payment-status' ><?php esc_html_e( 'Payment status', 'booking-activities' ); ?></label>
				<select name='select-payment-status' id='bookacti-select-payment-status' >
					<?php
					$payment_status = bookacti_get_payment_status_labels();
					foreach( $payment_status as $payment_status_id => $payment_status_data ) {
						echo '<option value="' . esc_attr( $payment_status_id ) . '" >' . esc_html( $payment_status_data[ 'label' ] ) . '</option>';
					}
					?>
				</select>
			</div>
		</fieldset>
	</form>
</div>


<div id='bookacti-change-booking-quantity-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php esc_html_e( 'Change booking quantity', 'booking-activities' ); ?>'>
	<form id='bookacti-change-booking-quantity-form'>
		<?php
			wp_nonce_field( 'bookacti_change_booking_quantity', 'nonce_change_booking_quantity', false );
		?>
		<p class='bookacti-dialog-intro' ><?php esc_html_e( 'Input the desired booking quantity:', 'booking-activities' ); ?></p>
		<?php
			$booking_qty_fields = apply_filters( 'bookacti_change_booking_quantity_dialog_fields', array(
				'quantity' => array(
					'type'  => 'number',
					'name'  => 'new_quantity',
					'title' => esc_html__( 'Quantity', 'booking-activities' ),
					'id'    => 'bookacti-new-quantity',
					'value' => 1,
					'tip'   => esc_html__( 'New total quantity. In case of booking groups, the quantity of all the bookings of the group will be updated.', 'booking-activities' )
				)
			));
			bookacti_display_fields( $booking_qty_fields );
		?>
		<p class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'The new quantity will be enforced. No checks and no further actions will be performed.', 'booking-activities' ); ?></span>
		</p>
	</form>
</div>


<div id='bookacti-delete-booking-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php esc_html_e( 'Delete a booking', 'booking-activities' ); ?>'>
	<form id='bookacti-delete-booking-dialog-content'>
		<input type='hidden' name='action' value='bookactiDeleteBooking'/>
		<input type='hidden' name='booking_id' value='0'/>
		<input type='hidden' name='booking_type' value=''/>
		<?php wp_nonce_field( 'bookacti_delete_booking', 'nonce_delete_booking' ); ?>
		<p class='bookacti-dialog-intro bookacti-delete-single-booking-description' >
			<?php esc_html_e( 'Are you sure to delete this booking permanently?', 'booking-activities' ); ?>
		</p>
		<p class='bookacti-error'>
			<span class='dashicons dashicons-warning'></span>
			<span><?php esc_html_e( 'This action cannot be undone.', 'booking-activities' ); ?></span>
		</p>
		<p class='bookacti-dialog-intro bookacti-delete-booking-group-description' style='display:none;'>
			<?php esc_html_e( 'All the bookings included in this booking group will also be delete.', 'booking-activities' ); ?>
		</p>
		<?php do_action( 'bookacti_delete_booking_form_after' ); ?>
	</form>
</div>


<div id='bookacti-export-bookings-dialog' class='bookacti-backend-dialog bookacti-bookings-dialog' style='display:none;' title='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>'>
	<form id='bookacti-export-bookings-form'>
		<?php wp_nonce_field( 'bookacti_export_bookings_url', 'nonce_export_bookings_url', false ); ?>
		<input type='hidden' name='export_type' value='csv' id='bookacti-export-type-field'/>
		<div class='bookacti-info'>
			<span class='dashicons dashicons-info'></span>
			<span><?php esc_html_e( 'This will export all the bookings of the current list (filters applied).', 'booking-activities' ); ?></span>
		</div>
		<?php
		// Display tabs
		$user_settings  = bookacti_get_bookings_export_settings();
		$export_columns = bookacti_get_bookings_export_columns();
		$export_tabs    = apply_filters( 'bookacti_export_bookings_dialog_tabs', array(
			array( 
				'label'      => esc_html__( 'CSV', 'booking-activities' ),
				'id'         => 'csv',
				'callback'   => 'bookacti_fill_export_bookings_csv_tab',
				'parameters' => array( 'user_settings' => $user_settings, 'export_columns' => $export_columns ),
				'order'      => 10
			),
			array(
				'label'      => esc_html__( 'iCal', 'booking-activities' ),
				'id'         => 'ical',
				'callback'   => 'bookacti_fill_export_bookings_ical_tab',
				'parameters' => array( 'user_settings' => $user_settings, 'export_columns' => $export_columns ),
				'order'      => 20
			)
		), $user_settings );
		bookacti_display_tabs( $export_tabs, 'export_bookings' );
		
		
		/**
		 * Display the content of the "csv" tab of the "Export bookings" dialog
		 * @param array $args
		 * @since 1.8.0
		 * @version 1.15.4
		 */
		function bookacti_fill_export_bookings_csv_tab( $args ) {
			do_action( 'bookacti_fill_export_bookings_csv_tab_before', $args );
			
			$excel_import_csv   = '<a href="https://support.office.com/en-us/article/import-or-export-text-txt-or-csv-files-5250ac4c-663c-47ce-937b-339e391393ba#ID0EAAFAAA" target="_blank">' . esc_html_x( 'import', 'verb', 'booking-activities' ) . '</a>';
			$excel_sync_csv     = '<a href="https://support.office.com/en-us/article/import-data-from-external-data-sources-power-query-be4330b3-5356-486c-a168-b68e9e616f5a#ID0EAAHAAA" target="_blank">' . esc_html_x( 'sync', 'verb', 'booking-activities' ) . '</a>';
			$gsheets_import_csv = '<a href="https://support.google.com/docs/answer/40608" target="_blank">' . esc_html_x( 'import', 'verb', 'booking-activities' ) . '</a>';
			$gsheets_sync_csv   = '<a href="https://support.google.com/docs/answer/3093335" target="_blank">' . esc_html_x( 'sync', 'verb', 'booking-activities' ) . '</a>';
			?>
			
			<div class='bookacti-info'>
				<span class='dashicons dashicons-info'></span>
				<span><?php echo '<strong>' . esc_html__( 'Types of use:', 'booking-activities' ) . '</strong> MS Excel (' . implode( ', ', array( $excel_import_csv, $excel_sync_csv ) ) . '), Google Sheets (' . implode( ', ', array( $gsheets_import_csv, $gsheets_sync_csv ) ) . ')...'; ?></span>
			</div>
			
			<?php
			// Push the selected columns at the end of the options in the selected order
			$export_columns_ordered = $args[ 'export_columns' ];
			foreach( $args[ 'user_settings' ][ 'csv_columns' ] as $col_name ) {
				if( isset( $export_columns_ordered[ $col_name ] ) ) {
					$col_title = $export_columns_ordered[ $col_name ];
					unset( $export_columns_ordered[ $col_name ] );
					$export_columns_ordered[ $col_name ] = $col_title;
				}
			}
			
			$csv_fields = apply_filters( 'bookacti_export_bookings_csv_fields', array(
				'csv_columns' => array(
					'type'        => 'select',
					'name'        => 'csv_columns',
					'id'          => 'bookacti-csv-columns-to-export',
					'class'       => 'bookacti-select2-no-ajax', 
					'multiple'    => 1,
					'attr'        => array( '<select>' => ' data-sortable="1"' ),
					'title'       => esc_html__( 'Columns to export (ordered)', 'booking-activities' ),
					'placeholder' => esc_html__( 'Search...', 'booking-activities' ),
					'options'     => $export_columns_ordered,
					'value'       => $args[ 'user_settings' ][ 'csv_columns' ],
					'tip'         => esc_html__( 'Add the columns you want to export in the order they will be displayed.', 'booking-activities' )
				),
				'csv_raw' => array(
					'type'  => 'checkbox',
					'name'  => 'csv_raw',
					'title' => esc_html__( 'Raw data', 'booking-activities' ),
					'id'    => 'bookacti-csv-raw',
					'value' => $args[ 'user_settings' ][ 'csv_raw' ],
					'tip'   => esc_html__( 'Display raw data (easy to manipulate), as opposed to formatted data (user-friendly). E.g.: A date will be displayed "1992-12-26 02:00:00" instead of "December 26th, 2020 2:00 AM".', 'booking-activities' )
				),
				'csv_export_groups' => array(
					'type'    => 'select',
					'name'    => 'csv_export_groups',
					'title'   => esc_html__( 'How to display the groups?', 'booking-activities' ),
					'id'      => 'bookacti-select-export-groups',
					'options' => array(
						'groups'   => esc_html__( 'One single row per group', 'booking-activities' ),
						'bookings' => esc_html__( 'One row for each booking of the group', 'booking-activities' )
					),
					'value'   => $args[ 'user_settings' ][ 'csv_export_groups' ],
					'tip'     => esc_html__( 'Choose how to display the grouped bookings. Do you want to display all the bookings of the group, or only the group as a single row?', 'booking-activities' )
				)
			), $args );
			bookacti_display_fields( $csv_fields );
			
			do_action( 'bookacti_fill_export_bookings_csv_tab_after', $args );
		}
		
		
		/**
		 * Display the content of the "iCal" tab of the "Export bookings" dialog
		 * @since 1.8.0
		 * @version 1.15.4
		 * @param array $args
		 */
		function bookacti_fill_export_bookings_ical_tab( $args ) {
			do_action( 'bookacti_fill_export_bookings_ical_tab_before', $args );
			
			$gcal_import_ical = '<a href="https://support.google.com/calendar/answer/37118" target="_blank">' . esc_html_x( 'import', 'verb', 'booking-activities' ) . '</a>';
			$gcal_sync_ical   = '<a href="https://support.google.com/calendar/answer/37100" target="_blank">' . esc_html_x( 'sync', 'verb', 'booking-activities' ) . '</a>';
			$outlook_com_ical = '<a href="https://support.office.com/en-us/article/import-or-subscribe-to-a-calendar-in-outlook-com-cff1429c-5af6-41ec-a5b4-74f2c278e98c" target="_blank">' . esc_html_x( 'import', 'verb', 'booking-activities' ) . ' / ' . esc_html_x( 'sync', 'verb', 'booking-activities' ) . '</a>';
			$outlook_ms_ical  = '<a href="https://support.office.com/en-us/article/video-import-calendars-8e8364e1-400e-4c0f-a573-fe76b5a2d379" target="_blank">' . esc_html_x( 'import', 'verb', 'booking-activities' ) . ' / ' . esc_html_x( 'sync', 'verb', 'booking-activities' ) . '</a>';
			?>
		
			<div class='bookacti-info'>
				<span class='dashicons dashicons-info'></span>
				<span><?php echo '<strong>' . esc_html__( 'Types of use:', 'booking-activities' ) . '</strong> Google Calendar (' . implode( ', ', array( $gcal_import_ical, $gcal_sync_ical ) ) . '), Outlook.com (' . $outlook_com_ical . '), MS Outlook (' . $outlook_ms_ical . ')...'; ?></span>
			</div>
			
			<?php
			$ical_fields = apply_filters( 'bookacti_export_bookings_ical_fields', array(
				'vevent_summary' => array(
					'type'      => 'text',
					'name'      => 'vevent_summary',
					'title'     => esc_html__( 'Event title', 'booking-activities' ),
					'fullwidth' => 1,
					'id'        => 'bookacti-vevent-title',
					'value'     => $args[ 'user_settings' ][ 'vevent_summary' ],
					'tip'       => esc_html__( 'The title of the exported events, use the tags to display event data.', 'booking-activities' )
				),
				'vevent_description' => array(
					'type'      => 'editor',
					'name'      => 'vevent_description',
					'title'     => esc_html__( 'Event description', 'booking-activities' ),
					'fullwidth' => 1,
					'id'        => 'bookacti-vevent-description',
					'value'     => $args[ 'user_settings' ][ 'vevent_description' ],
					'tip'       => esc_html__( 'The description of the exported events, use the tags to display event data.', 'booking-activities' )
				)
			), $args );
			bookacti_display_fields( $ical_fields );
			?>
			<div class='bookacti-warning'>
				<span class='dashicons dashicons-warning'></span>
				<span><?php esc_html_e( 'HTML may not be supported by your calendar app.', 'booking-activities' ); ?></span>
			</div>
			<?php
				$tags_args = array( 
					'title' => esc_html__( 'Available tags', 'booking-activities' ),
					'tip'   => esc_html__( 'Use these tags in the event title and description to display event specific data.', 'booking-activities' ),
					'tags'  => bookacti_get_bookings_export_event_tags(),
					'id'    => 'bookacti-tags-ical-bookings-export'
				);
				bookacti_display_tags_fieldset( $tags_args );
			?>
			<fieldset id='booakcti-ical-booking-list-fields-container' class='bookacti-fieldset-no-css'>
				<legend class='bookacti-fullwidth-label'>
					<?php 
						esc_html_e( 'Booking list tags settings', 'booking-activities' ); 
						bookacti_help_tip( esc_html__( 'Configure the booking list displayed on the exported events.', 'booking-activities' ) );
					?>
					<span class='bookacti-show-hide-advanced-options bookacti-show-advanced-options' for='booakcti-ical-booking-list-fields' data-show-title='<?php esc_html_e( 'show', 'booking-activities' ); ?>' data-hide-title='<?php esc_html_e( 'hide', 'booking-activities' ); ?>'><?php esc_html_e( 'show', 'booking-activities' ); ?></span>
				</legend>
				<div id='booakcti-ical-booking-list-fields' class='bookacti-fieldset-toggled' style='display:none;'>
					<div class='bookacti-info' style='margin-bottom:10px;'>
						<span class='dashicons dashicons-info'></span>
						<span><?php esc_html_e( 'These settings are used for the {booking_list} and {booking_list_raw} tags only.', 'booking-activities' ); ?></span>
					</div>
				<?php
					// Push the selected columns at the end of the options in the selected order
					$export_columns_ordered = $args[ 'export_columns' ];
					foreach( $args[ 'user_settings' ][ 'ical_columns' ] as $col_name ) {
						if( isset( $export_columns_ordered[ $col_name ] ) ) {
							$col_title = $export_columns_ordered[ $col_name ];
							unset( $export_columns_ordered[ $col_name ] );
							$export_columns_ordered[ $col_name ] = $col_title;
						}
					}
				
					$ical_booking_list_fields = apply_filters( 'bookacti_export_bookings_ical_booking_list_fields', array(
						'ical_columns' => array(
							'type'        => 'select',
							'name'        => 'ical_columns',
							'id'          => 'bookacti-ical-booking-list-columns',
							'class'       => 'bookacti-select2-no-ajax', 
							'multiple'    => 1,
							'attr'        => array( '<select>' => ' data-sortable="1"' ),
							'placeholder' => esc_html__( 'Search...', 'booking-activities' ),
							'title'       => esc_html__( 'Columns (ordered)', 'booking-activities' ),
							'options'     => $export_columns_ordered,
							'value'       => $args[ 'user_settings' ][ 'ical_columns' ],
							'tip'         => esc_html__( 'Add the columns in the order you want them to appear when using the {booking_list} or {booking_list_raw} tags.', 'booking-activities' )
						),
						'ical_raw' => array(
							'type'  => 'checkbox',
							'name'  => 'ical_raw',
							'title' => esc_html__( 'Raw data', 'booking-activities' ),
							'id'    => 'bookacti-ical-raw',
							'value' => $args[ 'user_settings' ][ 'ical_raw' ],
							'tip'   => esc_html__( 'Display raw data (easy to manipulate), as opposed to formatted data (user-friendly). E.g.: A date will be displayed "1992-12-26 02:00:00" instead of "December 26th, 2020 2:00 AM".', 'booking-activities' )
						),
						'ical_booking_list_header' => array(
							'type'  => 'checkbox',
							'name'  => 'ical_booking_list_header',
							'title' => esc_html__( 'Show columns\' title', 'booking-activities' ),
							'id'    => 'bookacti-ical-booking-list-header',
							'value' => $args[ 'user_settings' ][ 'ical_booking_list_header' ],
							'tip'   => esc_html__( 'Display the columns\' title in the first row of the booking list.', 'booking-activities' )
						)
					), $args );
					bookacti_display_fields( $ical_booking_list_fields );
				?>
				</div>
			</fieldset>
			<?php
			do_action( 'bookacti_fill_export_bookings_ical_tab_after', $args );
		}
		
		// Display global export fields
		$export_fields = apply_filters( 'bookacti_export_bookings_dialog_fields', array(
			'per_page' => array(
				'type'  => 'number',
				'name'  => 'per_page',
				'title' => esc_html__( 'Limit', 'booking-activities' ),
				'id'    => 'bookacti-select-export-limit',
				'value'	=> $user_settings[ 'per_page' ],
				'tip'   => esc_html__( 'Maximum number of bookings to export. You may need to increase your PHP max execution time if this number is too high.', 'booking-activities' )
			)
		), $user_settings );
		bookacti_display_fields( $export_fields );
		?>
		<div id='bookacti-export-bookings-url-container' style='display:none;'>
			<p><strong><?php esc_html_e( 'Secret address', 'booking-activities' ); ?></strong></p>
			<div class='bookacti_export_url'>
				<div class='bookacti_export_url_field'><input type='text' id='bookacti_export_bookings_url_secret' value='' readonly onfocus='this.select();'/></div>
				<div class='bookacti_export_button'><input type='button' value='<?php echo esc_html_x( 'Export', 'action', 'booking-activities' ); ?>' class='button button-primary button-large'/></div>
			</div>
			<p>
				<small><?php esc_html_e( 'Visit this address to get a file export of your bookings (according to filters and settings above), or use it as a dynamic URL feed to synchronize with other apps.', 'booking-activities' ); ?></small>
			</p>
			<p class='bookacti-warning'>
				<span class='dashicons dashicons-warning'></span>
				<span><small>
					<?php esc_html_e( 'This link provides real-time data. However, some apps may synchronize only every 24h, or more.', 'booking-activities' ); ?>
					<strong> <?php esc_html_e( 'That\'s why your changes won\'t be applied in real time on your synched apps.', 'booking-activities' ); ?></strong>
				</small></span>
			</p>
			<p class='bookacti-warning'>
				<span class='dashicons dashicons-warning'></span>
				<span><small>
				<?php 
					echo esc_html__( 'Only share this address with those you trust to see all your bookings details.', 'booking-activities' ) . ' ' 
					   . esc_html__( 'You can reset your secret key with the "Reset" button below. This will nullify the previously generated export links.', 'booking-activities' );
				?>
				</small></span>
			</p>
		</div>
		<?php do_action( 'bookacti_export_bookings_after', $user_settings ); ?>
	</form>
</div>

<?php 
do_action( 'bookacti_backend_bookings_dialogs' );