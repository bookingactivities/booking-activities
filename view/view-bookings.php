<?php
/**
 * Booking list page
 * @version 1.16.13
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Bookings', 'booking-activities' ); ?></h1>
	<?php do_action( 'bookacti_booking_list_page_header' ); ?>
	<hr class='wp-header-end'/>

	<?php
	// Check if the user has available calendars
	$templates = bookacti_get_templates_data();
	if( ! $templates ) {
		$editor_path = 'admin.php?page=bookacti_calendars';
		$editor_url = admin_url( $editor_path );
		?>
			<div id='bookacti-first-template-container'>
				<h2>
					<?php echo sprintf( esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %s to create your first calendar', 'booking-activities' ), '<a href="' . esc_url( $editor_url ) . '" >' . esc_html__( 'Calendar Editor', 'booking-activities' ) . '</a>' ); ?>
				</h2>
			</div>
		</div><!-- end of wp wrap -->
		<?php
		exit;
	}
	
	// Format templates from URL
	$available_template_ids = bookacti_ids_to_array( array_keys( $templates ) );
	$desired_templates      = isset( $_REQUEST[ 'templates' ] ) ? bookacti_ids_to_array( $_REQUEST[ 'templates' ], false ) : array();

	$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
	$allowed_templates  = ! empty( $desired_templates ) ? array_values( array_intersect( $desired_templates, $available_template_ids ) ) : $available_template_ids;
	$selected_templates = ! empty( $allowed_templates ) ? $allowed_templates : array( 'none' );

	$templates_select_options = array();
	foreach( $templates as $template_id => $template ) {
		$templates_select_options[ $template_id ] = esc_html( $template[ 'title' ] );
	}
	
	// Format activities from URL
	$selected_activities = isset( $_REQUEST[ 'activities' ] ) ? bookacti_ids_to_array( $_REQUEST[ 'activities' ], false ) : array();
	
	$activities = bookacti_fetch_activities_with_templates_association( $available_template_ids );
	$activities_select_options = array();
	foreach( $activities as $activity_id => $activity ) {
		$activities_select_options[ $activity_id ] = ! empty( $activity[ 'title' ] ) ? esc_html( apply_filters( 'bookacti_translate_text', $activity[ 'title' ] ) ) : '';
	}
	?>
	
	<div id='bookacti-bookings-container'>
		<div id='bookacti-bookings-filters-container'>
			<form id='bookacti-booking-list-filters-form' action=''>
				<input type='hidden' name='page' value='bookacti_bookings'/>
				<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_get_booking_list' ); ?>'/>
				<input type='hidden' name='created_from' value='<?php echo ! empty( $_REQUEST[ 'created_from' ] ) ? esc_attr( $_REQUEST[ 'created_from' ] ) : ''; ?>'/>
				<input type='hidden' name='created_to' value='<?php echo ! empty( $_REQUEST[ 'created_to' ] ) ? esc_attr( $_REQUEST[ 'created_to' ] ) : ''; ?>'/>
				<?php
					// Display sorting data
					$order_by = ! empty( $_REQUEST[ 'order_by' ] ) ? $_REQUEST[ 'order_by' ] : ( ! empty( $_REQUEST[ 'orderby' ] ) ? $_REQUEST[ 'orderby' ] : array() );
					$order_by = array_values( bookacti_str_ids_to_array( $order_by ) );
					if( $order_by ) {
						$i=0;
						foreach( $order_by as $column_name ) {
							if( $i === 0 ) {
								echo '<input type="hidden" name="orderby" value="' . esc_attr( $column_name ) . '" />';
							}
							echo '<input type="hidden" name="order_by[' . $i . ']" value="' . esc_attr( $column_name ) . '" />';
							++$i;
						}
					}
					$order = ! empty( $_REQUEST[ 'order' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'order' ] ) : '';
					if( $order ) {
						echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
					}
					$group_by = ! empty( $_REQUEST[ 'group_by' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'group_by' ] ) : 'booking_group';
					if( $group_by ) {
						echo '<input type="hidden" name="group_by" value="' . esc_attr( $group_by ) . '" />';
					}
					
					do_action( 'bookacti_before_booking_filters' );
				?>
				<div id='bookacti-booking-id-filter-container' class='bookacti-filter-container'>
					<div id='bookacti-booking-id-filter-title' class='bookacti-filter-title'>
						<?php esc_html_e( 'Booking ID', 'booking-activities' ); ?>
					</div>
					<div id='bookacti-booking-id-filter-content' class='bookacti-bookings-filter-content'>
						<?php
							$booking_id       = ! empty( $_REQUEST[ 'booking_id' ] ) ? intval( $_REQUEST[ 'booking_id' ] ) : '';
							$booking_group_id = ! empty( $_REQUEST[ 'booking_group_id' ] ) ? intval( $_REQUEST[ 'booking_group_id' ] ) : '';
						?>
						<div id='bookacti-booking-id-filter-input-container'>
							<input type='number' name='booking_id' id='bookacti-booking-filter-booking-id' value='<?php echo $booking_id; ?>' placeholder='<?php esc_html_e( 'Booking ID', 'booking-activities' ); ?>' min='1'/>
						</div>
						<div id='bookacti-booking-group-id-filter-input-container'>
							<input type='number' name='booking_group_id' id='bookacti-booking-filter-booking-group-id' value='<?php echo $booking_group_id; ?>' placeholder='<?php esc_html_e( 'Booking group ID', 'booking-activities' ); ?>' min='1'/>
						</div>
					</div>
				</div>
				<div id='bookacti-templates-filter-container' class='bookacti-filter-container' style='<?php if( count( $templates_select_options ) < 2 ) { echo 'display:none;'; } ?>'>
					<div id='bookacti-templates-filter-title' class='bookacti-filter-title'>
						<?php esc_html_e( 'Calendars', 'booking-activities' ); ?>
					</div>
					<div id='bookacti-templates-filter-content' class='bookacti-filter-content'>
					<?php
						$args = array(
							'type'        => 'select',
							'name'        => 'templates',
							'id'          => 'bookacti-booking-filter-templates',
							'class'       => 'bookacti-select2-no-ajax', 
							'attr'        => array( '<select>' => ' data-allow-clear="0"' ),
							'placeholder' => esc_html__( 'All', 'booking-activities' ),
							'options'     => $templates_select_options,
							'value'       => count( $selected_templates ) === count( $templates ) ? array() : $selected_templates,
							'multiple'    => true
						);
						bookacti_display_field( $args );
					?>
					</div>
				</div>
				<div id='bookacti-activities-filter-container' class='bookacti-filter-container' style='<?php if( count( $activities_select_options ) < 2 ) { echo 'display:none;'; } ?>'>
					<div class='bookacti-filter-title' >
						<?php esc_html_e( 'Activities', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-filter-content'>
					<?php
						$args = array(
							'type'        => 'select',
							'name'        => 'activities',
							'id'          => 'bookacti-booking-filter-activities',
							'class'       => 'bookacti-select2-no-ajax', 
							'attr'        => array( '<select>' => ' data-allow-clear="0"' ),
							'placeholder' => esc_html__( 'All', 'booking-activities' ),
							'options'     => $activities_select_options,
							'value'       => $selected_activities,
							'multiple'    => true
						);
						bookacti_display_field( $args );
					?>
					</div>
				</div>
				<div id='bookacti-status-filter-container' class='bookacti-filter-container' >
					<div class='bookacti-filter-title' >
						<?php echo esc_html_x( 'Status', 'Booking status', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-filter-content'>
					<?php
						// Format status from URL
						$default_status = get_user_meta( get_current_user_id(), 'bookacti_status_filter', true );
						$default_status = is_array( $default_status ) ? bookacti_str_ids_to_array( $default_status ) : array( 'delivered', 'booked', 'pending', 'cancelled', 'refunded', 'refund_requested' );
						$statuses       = bookacti_get_booking_statuses();
						$status_options = array();
						foreach ( $statuses as $status => $label ) {
							$status_options[ $status ] = esc_html( $label );
						}
						$selected_status = isset( $_REQUEST[ 'status' ] ) ? bookacti_str_ids_to_array( $_REQUEST[ 'status' ] ) : array();
						if( ! $selected_status ) { $selected_status = $default_status; }
						$args = array(
							'type'        => 'select',
							'name'        => 'status',
							'id'          => 'bookacti-booking-filter-status',
							'class'       => 'bookacti-select2-no-ajax', 
							'attr'        => array( '<select>' => ' data-allow-clear="0"' ),
							'placeholder' => esc_html__( 'All', 'booking-activities' ),
							'options'     => $status_options,
							'value'       => $selected_status,
							'multiple'    => true
						);
						bookacti_display_field( $args );
						
						// Update user default status filter
						if( $selected_status != $default_status && empty( $_REQUEST[ 'keep_default_status' ] ) ) {
							update_user_meta( get_current_user_id(), 'bookacti_status_filter', $selected_status );
						}
					?>
					</div>
				</div>
				<div id='bookacti-dates-filter-container' class='bookacti-filter-container' >
					<div class='bookacti-filter-title' >
						<?php esc_html_e( 'Date', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-filter-content' >
						<?php
							$from = isset( $_REQUEST[ 'from' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'from' ] ) : '';
							$to   = isset( $_REQUEST[ 'to' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'to' ] ) : '';
							if( ! $from ) { $from = isset( $_REQUEST[ 'from' ] ) ? substr( bookacti_sanitize_datetime( $_REQUEST[ 'from' ] ), 0, 10 ) : ''; }
							if( ! $to )   { $to   = isset( $_REQUEST[ 'to' ] ) ? substr( bookacti_sanitize_datetime( $_REQUEST[ 'to' ] ), 0, 10 ) : ''; }
						?>
						<div><label for='bookacti-booking-filter-dates-from'><?php /* translators: Followed by a date. E.g.: From 04/02/2021. */ echo esc_html_x( 'From', 'date', 'booking-activities' ) . ' '; ?></label><input type='date' name='from' id='bookacti-booking-filter-dates-from' value='<?php echo $from; ?>' ></div>
						<div><label for='bookacti-booking-filter-dates-to'><?php /* translators: Followed by a date. E.g.: To 04/02/2021. */ echo esc_html_x( 'To', 'date', 'booking-activities' ) . ' '; ?></label><input type='date' name='to'  id='bookacti-booking-filter-dates-to' value='<?php echo $to; ?>' ></div>
					</div>
				</div>
				<div id='bookacti-customer-filter-container' class='bookacti-filter-container' >
					<div class='bookacti-filter-title' >
						<?php esc_html_e( 'Customer', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-filter-content' >
					<?php
						$selected_user = isset( $_REQUEST[ 'user_id' ] ) ? sanitize_text_field( $_REQUEST[ 'user_id' ] ) : array();
						$args = apply_filters( 'bookacti_booking_list_user_selectbox_args', array(
							'name'            => 'user_id',
							'id'              => 'bookacti-booking-filter-customer',
							'show_option_all' => esc_html__( 'All', 'booking-activities' ),
							'option_label'    => array( 'first_name', ' ', 'last_name', ' (', 'user_login', ' / ', 'user_email', ')' ),
							'selected'        => $selected_user,
							'no_account'      => 1,
							'allow_clear'     => 1,
							'allow_tags'      => 1,
							'echo'            => 1
						));
						bookacti_display_user_selectbox( $args );
					?>
					</div>
				</div>
				<?php
					do_action( 'bookacti_after_booking_filters' );
					$user_calendar_settings = bookacti_format_bookings_calendar_settings( get_user_meta( get_current_user_id(), 'bookacti_bookings_calendar_settings', true ) );
				?>
				<div id='bookacti-actions-filter-container' class='bookacti-filter-container'>
					<div class='bookacti-filter-title' >
						<?php esc_html_e( 'Actions', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-filter-content' >
						<input type='submit' class='button button-primary button-large' id='bookacti-submit-filter-button' value='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' data-ajax='<?php echo $user_calendar_settings[ 'ajax' ] ? 1 : 0; ?>'/>
						<input type='button' class='button button-primary button-large bookacti-export-bookings-button' value='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>'/>
					</div>
				</div>
				<div id='bookacti-event-filter-container'>
					<div class='bookacti-filter-title'>
						<span><?php esc_html_e( 'Booking calendar', 'booking-activities' ); ?></span>
						<span id='bookacti-bookings-calendar-settings' class='dashicons dashicons-admin-generic'></span>
					</div>
					<div class='bookacti-filter-content'>
						<?php
							// Get selected (group of) event(s) data (if any)
							$picked_events = ! empty( $_REQUEST[ 'selected_events' ] ) ? bookacti_format_picked_events( $_REQUEST[ 'selected_events' ] ) : array();
							
							$calendar_button_label_show = esc_html__( 'Show calendar', 'booking-activities' );
							$calendar_button_label_hide = esc_html__( 'Hide calendar', 'booking-activities' );
							$calendar_button_label = $picked_events || $user_calendar_settings[ 'show' ] ? $calendar_button_label_hide : $calendar_button_label_show;
						?>
						<a class='button' id='bookacti-pick-event-filter' title='<?php echo $calendar_button_label; ?>' data-label-hide='<?php echo $calendar_button_label_hide; ?>' data-label-show='<?php echo $calendar_button_label_show; ?>'>
							<?php echo $calendar_button_label; ?>
						</a>
						<span id='bookacti-pick-event-filter-instruction' <?php if( $picked_events || ! $user_calendar_settings[ 'show' ] ) { echo 'style="display:none;"'; } ?>>
							<?php esc_html_e( 'Pick an event to filter the booking list.', 'booking-activities' ); ?>
						</span>
						<a class='button' id='bookacti-unpick-events-filter' title='<?php esc_html_e( 'Unpick events', 'booking-activities' ); ?>' <?php if( ! $picked_events ) { echo 'style="display:none;"'; } ?>>
							<?php esc_html_e( 'Unpick events', 'booking-activities' ); ?>
						</a>
						<span id='bookacti-picked-events-actions-container' <?php if( ! $picked_events ) { echo 'style="display:none;"'; } ?>>
							<input type='submit' class='button button-primary button-large' value='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>'/>
							<input type='button' class='button button-primary button-large bookacti-export-bookings-button' value='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>'/>
						</span>
					</div>
				</div>
				<div id='bookacti-booking-system-filter-container' <?php if( ! $picked_events && ! $user_calendar_settings[ 'show' ] ) { echo 'style="display:none;"'; } ?>>
					<?php
						// Display data
						$display_data = array();
						$default_display_data = bookacti_get_booking_system_default_display_data();
						foreach( $default_display_data as $name => $default_value ) {
							$display_data[ $name ] = ! empty( $user_calendar_settings[ $name ] ) ? $user_calendar_settings[ $name ] : $default_value;
						}
						
						// Search by both user email and ID
						$selected_users = array();
						if( $selected_user ) {
							if( is_array( $selected_user ) ) { $selected_users = $selected_user; }
							else { $selected_users = array( $selected_user ); }
							foreach( $selected_users as $user_id ) {
								if( is_email( $user_id ) ) {
									$user = get_user_by( 'email', $user_id );
									if( $user ) {
										$selected_users[] = intval( $user->ID );
									}
								} else if( is_numeric( $user_id ) ) {
									$user = get_user_by( 'ID', $user_id );
									if( $user ) {
										$selected_users[] = sanitize_email( $user->user_email );
									}
								}
							}
							$selected_users = array_unique( array_filter( $selected_users ) );
						}
						
						// Display the booking system
						$atts = apply_filters( 'bookacti_bookings_booking_system_attributes', array( 
							'bookings_only'                => 1,
							'calendars'                    => $selected_templates,
							'status'                       => $selected_status,
							'user_id'                      => $selected_users,
							'group_categories'             => array(),
							'groups_only'                  => 0,
							'groups_single_events'         => 1,
							'groups_first_event_only'      => 0,
							'method'                       => 'calendar',
							'id'                           => 'bookacti-booking-system-bookings-page',
							'start'                        => $from,
							'end'                          => $to,
							'trim'                         => 0, // Doesn't play nicely when dynamically changing bookings
							'past_events'                  => 1,
							'past_events_bookable'         => 1,
							'check_roles'                  => 0,
							'auto_load'                    => 0, // Prevent to load on page load to save some performance
							'picked_events'                => $picked_events,
							'tooltip_booking_list'         => $user_calendar_settings[ 'tooltip_booking_list' ],
							'tooltip_booking_list_columns' => $user_calendar_settings[ 'tooltip_booking_list_columns' ],
							'display_data'                 => $display_data
						), $user_calendar_settings );

						// Format booking system attributes
						$atts = bookacti_format_booking_system_attributes( $atts );
						
						echo bookacti_get_booking_system( $atts );
					?>
					<script>
						bookacti.booking_system[ 'bookacti-booking-system-bookings-page' ][ 'templates_per_activities' ] = <?php echo json_encode( $activities ); ?>;
					</script>
				</div>
			</form>
		</div>

		<div id='bookacti-booking-list-container'>
			<form id='bookacti-booking-list-form' action=''>
				<input type='hidden' value='0' id='bookacti-all-selected'/>
				<?php do_action( 'bookacti_before_booking_list' ); ?>
				<div id='bookacti-booking-list'>
				<?php
					$filters = apply_filters( 'bookacti_bookings_list_filters', array( 
						'templates'            => $selected_templates, 
						'status'               => $selected_status,
						'in__user_id'          => $selected_users,
						'fetch_meta'           => true, 
						'merge_url_parameters' => true
					), $atts );
					$bookings_list_table = new Bookings_List_Table();
					$bookings_list_table->prepare_items( $filters );
					$bookings_list_table->display();
				?>
				</div>
				<?php do_action( 'bookacti_after_booking_list' ); ?>
			</form>
		</div>
		
		<div class='bookacti-sos'>
			<strong><?php /* translators: %s = [bookingactivities_list] */ echo sprintf( esc_html__( 'Your customers can see their booking list too thanks to the %s shortcode!', 'booking-activities' ), '<code>[bookingactivities_list]</code>' ); ?> (<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-customers-bookings-list-on-the-frontend/' target='_blank'><?php esc_html_e( 'documentation', 'booking-activities' ); ?></a>)</strong>
			<span class='dashicons dashicons-sos' data-label='<?php echo esc_html_x( 'Help', 'button label', 'booking-activities' ); ?>'></span>
			<span>
				<ul class='bookacti-help-list'>
					<li><?php esc_html_e( 'The customers will need to be logged in to see their booking list, so it is recommended to display a login form on the same page', 'booking-activities' ); ?> (<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-booking-activities/display-a-login-registration-form/' target='_blank'><?php esc_html_e( 'documentation', 'booking-activities' ); ?></a>)
					<li><?php esc_html_e( 'The customers will be able to cancel, reschedule or request a refund from their booking list.', 'booking-activities' ); ?> (<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_settings&tab=cancellation' ) ); ?>'><?php esc_html_e( 'Settings', 'booking-activities' ); ?></a>)
					<li><?php esc_html_e( 'You can display your customers\' calendar of bookings too: create a booking form, remove all fields, configure the "Calendar" field to display events booked by the current user only, and past events. Then display it thanks to the booking form shortcode.', 'booking-activities' ); ?>
					<?php do_action( 'bookacti_admin_booking_list_help_after' ); ?>
				</ul>
			</span>
		</div>
	</div>
</div><!-- end of wp wrap -->

<?php
// Include dialogs
include_once( 'view-backend-bookings-dialogs.php' );
include_once( 'view-bookings-dialogs.php' );
