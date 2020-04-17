<?php
/**
 * Booking list page
 * @version 1.8.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Bookings', 'booking-activities' ); ?></h1>
	<?php do_action( 'bookacti_booking_list_page_header' ); ?>
	<hr class='wp-header-end'>

	<?php
	$templates = bookacti_fetch_templates();
	if( ! $templates ) {
		$editor_path = 'admin.php?page=bookacti_calendars';
		$editor_url = admin_url( $editor_path );
		?>
			<div id='bookacti-first-template-container' >
				<h2>
					<?php
					/* translators: %1$s and %2$s delimit the link to Calendar Editor page. */
					echo sprintf( esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %1$sCalendar Editor%2$s to create your first calendar.', 'booking-activities' ), '<a href="' . esc_url( $editor_url ) . '" >', '</a>' );
					?>
				</h2>
			</div>
		</div><!-- end of wp wrap -->
		<?php
		exit;
	}
	?>
	
	<div id='bookacti-bookings-container' >
		<div id='bookacti-bookings-filters-container' >
			<form id='bookacti-booking-list-filters-form' action=''>
				<input type='hidden' name='page' value='bookacti_bookings' />
				<?php
					// Display sorting data
					if( ! empty( $_GET[ 'orderby' ] ) || ! empty( $_GET[ 'order_by' ] ) ) {
						$order_by = ! empty( $_GET[ 'order_by' ] ) ? $_GET[ 'order_by' ] : $_GET[ 'orderby' ];
						if( ! is_array( $order_by ) ) {
							$order_by = array( $order_by );
						}
						$i=0;
						foreach( $order_by as $column_name ) {
							if( $i === 0 ) {
								echo '<input type="hidden" name="orderby" value="' . esc_attr( $column_name ) . '" />';
							}
							echo '<input type="hidden" name="order_by[' . $i . ']" value="' . esc_attr( $column_name ) . '" />';
							++$i;
						}
					}
					if( ! empty( $_GET[ 'order' ] ) ) {
						echo '<input type="hidden" name="order" value="' . esc_attr( $_GET[ 'order' ] ) . '" />';
					}

					// Display nonce field
					wp_nonce_field( 'bookacti_get_booking_list', 'nonce_get_booking_list', false );

					do_action( 'bookacti_before_booking_filters' );
				?>
				<div id='bookacti-templates-filter-container' class='bookacti-bookings-filter-container'>
					<div id='bookacti-templates-filter-title' class='bookacti-bookings-filter-title'>
						<?php esc_html_e( 'Calendars', 'booking-activities' ); ?>
					</div>
					<div id='bookacti-templates-filter-content'  class='bookacti-bookings-filter-content'>
					<?php
						// Format templates from URL
						$selected_templates	= isset( $_REQUEST[ 'templates' ] ) ? $_REQUEST[ 'templates' ] : array();
						$templates_select_options = array();
						foreach( $templates as $template_id => $template ) {
							$templates_select_options[ $template_id ] = esc_html( $template[ 'title' ] );
						}
						$args = array(
							'type'		=> 'select',
							'name'		=> 'templates',
							'id'		=> 'bookacti-booking-filter-templates',
							'options'	=> $templates_select_options,
							'value'		=> array_map( 'intval', $selected_templates ),
							'multiple'	=> true
						);
						bookacti_display_field( $args );
					?>
					</div>
				</div>
				<div id='bookacti-activities-filter-container' class='bookacti-bookings-filter-container'>
					<div class='bookacti-bookings-filter-title' >
						<?php esc_html_e( 'Activities', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-bookings-filter-content'>
					<?php
						// Format activities from URL
						$activities = bookacti_fetch_activities_with_templates_association( array_keys( $templates ) );
						$activities_select_options = array();
						foreach ( $activities as $activity_id => $activity ) {
							$activities_select_options[ $activity_id ] = esc_html( apply_filters( 'bookacti_translate_text', $activity[ 'title' ] ) );
						}
						$selected_activities = isset( $_REQUEST[ 'activities' ] ) ? $_REQUEST[ 'activities' ] : array();
						$args = array(
							'type'		=> 'select',
							'name'		=> 'activities',
							'id'		=> 'bookacti-booking-filter-activities',
							'options'	=> $activities_select_options,
							'value'		=> array_map( 'intval', $selected_activities ),
							'multiple'	=> true
						);
						bookacti_display_field( $args );
					?>
					</div>
				</div>
				<div id='bookacti-status-filter-container' class='bookacti-bookings-filter-container' >
					<div class='bookacti-bookings-filter-title' >
						<?php echo esc_html_x( 'Status', 'Booking status', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-bookings-filter-content'>
					<?php
						// Format status from URL
						$default_status = get_user_meta( get_current_user_id(), 'bookacti_status_filter', true );
						$default_status = is_array( $default_status ) ? $default_status : array( 'delivered', 'booked', 'pending', 'cancelled', 'refunded', 'refund_requested' );
						$statuses = bookacti_get_booking_state_labels();
						$status_select_options = array();
						foreach ( $statuses as $status_id => $status ) {
							$status_select_options[ $status_id ] = esc_html( $status[ 'label' ] );
						}
						$selected_status = isset( $_REQUEST[ 'status' ] ) ? $_REQUEST[ 'status' ] : $default_status;
						$selected_status = is_array( $selected_status ) ? $selected_status : array( $selected_status );
						$args = array(
							'type'		=> 'select',
							'name'		=> 'status',
							'id'		=> 'bookacti-booking-filter-status',
							'options'	=> $status_select_options,
							'value'		=> $selected_status,
							'multiple'	=> true
						);
						bookacti_display_field( $args );

						// Update user default status filter
						if( $selected_status != $default_status && empty( $_REQUEST[ 'keep_default_status' ] ) ) {
							update_user_meta( get_current_user_id(), 'bookacti_status_filter', $selected_status );
						}
					?>
					</div>
				</div>
				<div id='bookacti-dates-filter-container' class='bookacti-bookings-filter-container' >
					<div class='bookacti-bookings-filter-title' >
						<?php esc_html_e( 'Date', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-bookings-filter-content' >
						<?php
						$from = isset( $_REQUEST[ 'from' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'from' ] ) : '';
						$to = isset( $_REQUEST[ 'to' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'to' ] ) : '';
						if( ! $from ) { $from = isset( $_REQUEST[ 'from' ] ) ? substr( bookacti_sanitize_datetime( $_REQUEST[ 'from' ] ), 0, 10 ) : ''; }
						if( ! $to ) { $to = isset( $_REQUEST[ 'to' ] ) ? substr( bookacti_sanitize_datetime( $_REQUEST[ 'to' ] ), 0, 10 ) : ''; }
						
						// If Internet Explorer is used, do not change field type dynamically
						$user_agent = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';
						if( strpos( $user_agent, 'MSIE' ) || strpos( $user_agent, 'Trident/7' ) ) { ?>
							<div><label for='bookacti-booking-filter-dates-from'><?php echo esc_html__( 'From', 'booking-activities' ) . ' '; ?></label><input type='date' name='from' id='bookacti-booking-filter-dates-from' value='<?php echo $from; ?>' ></div>
							<div><label for='bookacti-booking-filter-dates-to'><?php echo esc_html__( 'To', 'booking-activities' ) . ' '; ?></label><input type='date' name='to'  id='bookacti-booking-filter-dates-to' value='<?php echo $to; ?>' ></div>
						<?php } else { ?>
							<div><input type='text' onfocus="(this.type='date')" onblur="(this.type='text')" name='from' id='bookacti-booking-filter-dates-from' placeholder='<?php esc_html_e( 'From', 'booking-activities' ); ?>' value='<?php echo $from; ?>' ></div>
							<div><input type='text' onfocus="(this.type='date')" onblur="(this.type='text')" name='to'  id='bookacti-booking-filter-dates-to' placeholder='<?php esc_html_e( 'To', 'booking-activities' ); ?>' value='<?php echo $to; ?>' ></div>
						<?php } ?>
					</div>
				</div>
				<div id='bookacti-customer-filter-container' class='bookacti-bookings-filter-container' >
					<div class='bookacti-bookings-filter-title' >
						<?php esc_html_e( 'Customer', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-bookings-filter-content' >
					<?php
						$selected_user = isset( $_REQUEST[ 'user_id' ] ) ? esc_attr( $_REQUEST[ 'user_id' ] ) : '';
						$args = apply_filters( 'bookacti_booking_list_user_selectbox_args', array(
							'name'				=> 'user_id',
							'id'				=> 'bookacti-booking-filter-customer',
							'show_option_all'	=> esc_html__( 'All', 'booking-activities' ),
							'option_label'		=> array( 'first_name', ' ', 'last_name', ' (', 'user_login', ' / ', 'user_email', ')' ),
							'selected'			=> $selected_user,
							'allow_clear'		=> 1,
							'allow_tags'		=> 1,
							'echo'				=> 1
						));
						bookacti_display_user_selectbox( $args );
					?>
					</div>
				</div>
				<?php
					do_action( 'bookacti_after_booking_filters' );
					$user_calendar_settings	= bookacti_format_bookings_calendar_settings( get_user_meta( get_current_user_id(), 'bookacti_bookings_calendar_settings', true ) );
				?>
				<div id='bookacti-actions-filter-container' class='bookacti-bookings-filter-container'>
					<div class='bookacti-bookings-filter-title' >
						<?php esc_html_e( 'Actions', 'booking-activities' ); ?>
					</div>
					<div class='bookacti-bookings-filter-content' >
						<input type='submit' class='button button-primary button-large' id='bookacti-submit-filter-button' value='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' data-ajax='<?php echo $user_calendar_settings[ 'ajax' ] ? 1 : 0; ?>'/>
						<input type='button' class='button button-primary button-large bookacti-export-bookings-button' value='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>'/>
					</div>
				</div>
				<div id='bookacti-event-filter-container'>
					<div class='bookacti-bookings-filter-title'>
						<span><?php esc_html_e( 'Booking calendar', 'booking-activities' ); ?></span>
						<span id='bookacti-bookings-calendar-settings' class='dashicons dashicons-admin-generic'></span>
					</div>
					<div class='bookacti-bookings-filter-content'>
						<?php
							// Get selected (group of) event(s) data (if any)
							// Accepts two different parameter name
							$event_group_id = 0; $event_id = 0; $event_start = ''; $event_end = ''; $picked_events = array();
							if( isset( $_REQUEST[ 'bookacti_group_id' ] )	&& $_REQUEST[ 'bookacti_group_id' ] !== 'single' )	{ $event_group_id = intval( $_REQUEST[ 'bookacti_group_id' ] ); }
							if( isset( $_REQUEST[ 'event_group_id' ] )		&& $_REQUEST[ 'event_group_id' ] !== 'single' )		{ $event_group_id = intval( $_REQUEST[ 'event_group_id' ] ); }
							if( isset( $_REQUEST[ 'bookacti_event_id' ] ) )		{ $event_id		= intval( $_REQUEST[ 'bookacti_event_id' ] ); }
							if( isset( $_REQUEST[ 'event_id' ] ) )				{ $event_id		= intval( $_REQUEST[ 'event_id' ] ); }
							if( isset( $_REQUEST[ 'bookacti_event_start' ] ) )	{ $event_start	= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_start' ] ); }
							if( isset( $_REQUEST[ 'event_start' ] ) )			{ $event_start	= bookacti_sanitize_datetime( $_REQUEST[ 'event_start' ] ); }
							if( isset( $_REQUEST[ 'bookacti_event_end' ] ) )	{ $event_end	= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_end' ] ); }
							if( isset( $_REQUEST[ 'event_end' ] ) )				{ $event_end	= bookacti_sanitize_datetime( $_REQUEST[ 'event_end' ] ); }

							// Check if there is an event picked by default
							$has_event_picked = ( ! $event_group_id && $event_id ) || $event_group_id;

							// Fill booking system default inputs
							$default_inputs = array(
								'group_id'		=> $event_group_id ? $event_group_id : ( $event_id ? 'single' : '' ),
								'event_id'		=> $event_id,
								'event_start'	=> $event_start,
								'event_end'		=> $event_end
							);

							$calendar_button_label_show = esc_html__( 'Show calendar', 'booking-activities' );
							$calendar_button_label_hide = esc_html__( 'Hide calendar', 'booking-activities' );
							$calendar_button_label = $has_event_picked || $user_calendar_settings[ 'show' ] ? $calendar_button_label_hide : $calendar_button_label_show;
						?>
						<a class='button' id='bookacti-pick-event-filter' title='<?php echo $calendar_button_label; ?>' data-label-hide='<?php echo $calendar_button_label_hide; ?>' data-label-show='<?php echo $calendar_button_label_show; ?>'>
							<?php echo $calendar_button_label; ?>
						</a>
						<span id='bookacti-pick-event-filter-instruction' <?php if( $has_event_picked || ! $user_calendar_settings[ 'show' ] ) { echo 'style="display:none;"'; } ?>>
							<?php esc_html_e( 'Pick an event to filter the booking list.', 'booking-activities' ); ?>
						</span>
						<a class='button' id='bookacti-unpick-events-filter' title='<?php esc_html_e( 'Unpick events', 'booking-activities' ); ?>' <?php if( ! $has_event_picked ) { echo 'style="display:none;"'; } ?>>
							<?php esc_html_e( 'Unpick events', 'booking-activities' ); ?>
						</a>
						<span id='bookacti-picked-events-actions-container' <?php if( ! $has_event_picked ) { echo 'style="display:none;"'; } ?>>
							<input type='submit' class='button button-primary button-large' value='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>'/>
							<input type='button' class='button button-primary button-large bookacti-export-bookings-button' value='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Export bookings', 'booking-activities' ); ?>'/>
						</span>
					</div>
				</div>
				<div id='bookacti-booking-system-filter-container' <?php if( ! $has_event_picked && ! $user_calendar_settings[ 'show' ] ) { echo 'style="display:none;"'; } ?>>
					<?php
						// Display data
						$display_data = array();
						$default_display_data = bookacti_get_booking_system_default_display_data();
						foreach( $default_display_data as $name => $default_value ) {
							$display_data[ $name ] = ! empty( $user_calendar_settings[ $name ] ) ? $user_calendar_settings[ $name ] : $default_value;
						}

						// Display the booking system
						$atts = apply_filters( 'bookacti_bookings_booking_system_attributes', array( 
							'bookings_only'			=> 1,
							'calendars'				=> $selected_templates,
							'status'				=> $selected_status,
							'payment_status'		=> isset( $_REQUEST[ 'payment_status' ] ) ? $_REQUEST[ 'payment_status' ] : array(),
							'user_id'				=> $selected_user,
							'group_categories'		=> array(),
							'groups_only'			=> 0,
							'groups_single_events'	=> 1,
							'method'				=> 'calendar',
							'id'					=> 'bookacti-booking-system-bookings-page',
							'start'					=> ! empty( $_REQUEST[ 'from' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'from' ] ) : '',
							'end'					=> ! empty( $_REQUEST[ 'to' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'to' ] ) : '',
							'trim'					=> 1,
							'past_events'			=> 1,
							'past_events_bookable'	=> 1,
							'check_roles'			=> 0,
							'auto_load'				=> 0, // Prevent to load on page load to save some performance
							'picked_events'			=> $default_inputs,
							'tooltip_booking_list'			=> $user_calendar_settings[ 'tooltip_booking_list' ],
							'tooltip_booking_list_columns'	=> $user_calendar_settings[ 'tooltip_booking_list_columns' ],
							'display_data'			=> $display_data
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
			<?php do_action( 'bookacti_before_booking_list' ); ?>
			<div id='bookacti-booking-list'>
			<?php
				$filters = array( 'fetch_meta' => true, 'merge_url_parameters' => true );
				$bookings_list_table = new Bookings_List_Table();
				$bookings_list_table->prepare_items( $filters );
				$bookings_list_table->display();
			?>
			</div>
			<?php do_action( 'bookacti_after_booking_list' ); ?>
		</div>
	</div>
</div><!-- end of wp wrap -->

<?php
// Include dialogs
include_once( 'view-backend-bookings-dialogs.php' );
include_once( 'view-bookings-dialogs.php' );
