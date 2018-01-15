<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

echo "<div class='wrap'>";
echo "<h1>" . esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME ) . "</h1>";

$templates = bookacti_fetch_templates();

if( ! $templates ) {
	$editor_path = 'admin.php?page=bookacti_calendars';
	$editor_url = admin_url( $editor_path );
	?>
	<div id='bookacti-first-template-container' >
		<h2>
			<?php
			/* translators: %1$s and %2$s delimit the link to Calendar Editor page. */
			echo sprintf(	
					esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %1$sCalendar Editor%2$s to create your first calendar.', BOOKACTI_PLUGIN_NAME ),
					'<a href="' . esc_url( $editor_url ) . '" >', 
					'</a>' );
			?>
		</h2>
	</div>
	<?php
	exit;
}
?>

<div id='bookacti-bookings-container' >

	<div id='bookacti-bookings-filters-container' >
		<form id='bookacti-booking-list-filters-form' action=''>
			<input type='hidden' name='page' value='bookacti_bookings' />
			<?php
				// Display nonce field
				wp_nonce_field( 'bookacti_filter_booking_list', 'nonce_filter_booking_list', false );
		
				do_action( 'bookacti_before_booking_filters' );
			?>
			<div id='bookacti-templates-filter-container' class='bookacti-bookings-filter-container' >
				<div id='bookacti-templates-filter-title' class='bookacti-bookings-filter-title' >
					<?php echo esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div id='bookacti-templates-filter-content'  class='bookacti-bookings-filter-content' >
					<input type='hidden' name='templates[]' value='all' />
				<?php
					// Format templates from URL
					if( isset( $_REQUEST[ 'templates' ] ) ) {
						if( count( $_REQUEST[ 'templates' ] ) <= 1 ) {
							$_REQUEST[ 'templates' ] = array();
						} else {
							unset( $_REQUEST[ 'templates' ][0] ); // unset 'all' value
						}
					}
				
					$default_template	= bookacti_get_user_default_template();
					$selected_templates	= isset( $_REQUEST[ 'templates' ] ) ? $_REQUEST[ 'templates' ] : array( $default_template );
					$templates_select_options = array();
					foreach ( $templates as $template_id => $template ) {
						if( ! $default_template ) { $default_template = $template_id; }
						$templates_select_options[ $template_id ] = esc_html( $template[ 'title' ] );
					}
					$args = array(
						'type'		=> 'select',
						'name'		=> 'templates',
						'id'		=> 'bookacti-booking-filter-templates',
						'options'	=> $templates_select_options,
						'value'		=> $selected_templates,
						'multiple'	=> true
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			<div id='bookacti-activities-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html__( 'Activities', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
					<input type='hidden' name='activities[]' value='all' />
				<?php
					// Format templates from URL
					if( isset( $_REQUEST[ 'activities' ] ) ) {
						if( count( $_REQUEST[ 'activities' ] ) <= 1 ) {
							$_REQUEST[ 'activities' ] = array();
						} else {
							unset( $_REQUEST[ 'activities' ][0] ); // unset 'all' value
						}
					}
					
					$activities = bookacti_get_activities_by_template( $selected_templates, false );
					$activities_select_options = array();
					foreach ( $activities as $activity_id => $activity ) {
						$activities_select_options[ $activity_id ] = esc_html( $activity->title );
					}
					$args = array(
						'type'		=> 'select',
						'name'		=> 'activities',
						'id'		=> 'bookacti-booking-filter-activities',
						'options'	=> $activities_select_options,
						'value'		=> isset( $_REQUEST[ 'activities' ] ) ? $_REQUEST[ 'activities' ] : array(),
						'multiple'	=> true
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			<div id='bookacti-status-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html_x( 'Status', 'Booking status', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
					<input type='hidden' name='status[]' value='' />
				<?php
					// Format templates from URL
					if( isset( $_REQUEST[ 'status' ] ) ) {
						if( count( $_REQUEST[ 'status' ] ) <= 1 ) {
							$_REQUEST[ 'status' ] = array();
						} else {
							unset( $_REQUEST[ 'status' ][0] ); // unset 'all' value
						}
					}
				
					$statuses = bookacti_get_booking_state_labels();
					$status_select_options = array();
					foreach ( $statuses as $status_id => $status ) {
						$status_select_options[ $status_id ] = esc_html( $status[ 'label' ] );
					}
					$args = array(
						'type'		=> 'select',
						'name'		=> 'status',
						'id'		=> 'bookacti-booking-filter-status',
						'options'	=> $status_select_options,
						'value'		=> isset( $_REQUEST[ 'status' ] ) ? $_REQUEST[ 'status' ] : array(),
						'multiple'	=> true
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			<div id='bookacti-dates-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html__( 'Date', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
					<div><input type='text' onfocus="(this.type='date')" onblur="(this.type='text')" name='from' id='bookacti-booking-filter-dates-from' placeholder='<?php _e( 'From', BOOKACTI_PLUGIN_NAME ); ?>' value='<?php echo isset( $_REQUEST[ 'from' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'from' ] ) : ''; ?>' ></div>
					<div><input type='text' onfocus="(this.type='date')" onblur="(this.type='text')" name='to'  id='bookacti-booking-filter-dates-to' placeholder='<?php _e( 'To', BOOKACTI_PLUGIN_NAME ); ?>' value='<?php echo isset( $_REQUEST[ 'to' ] ) ? bookacti_sanitize_date( $_REQUEST[ 'to' ] ) : ''; ?>' ></div>
				</div>
			</div>
			<div id='bookacti-customer-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html__( 'Customer', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
				<?php
					$args = apply_filters( 'bookacti_booking_list_user_selectbox_args', array(
						'name'				=> 'user_id',
						'id'				=> 'bookacti-booking-filter-customer',
						'show_option_all'	=> __( 'All', BOOKACTI_PLUGIN_NAME ),
						'option_label'		=> array( 'user_login', ' (', 'user_email', ')' ),
						'selected'			=> isset( $_REQUEST[ 'user_id' ] ) ? intval( $_REQUEST[ 'user_id' ] ) : 0,
						'echo'				=> true
					));
					bookacti_display_user_selectbox( $args );
				?>
				</div>
			</div>
			<div id='bookacti-event-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html__( 'Event', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
					<a class='button' id='bookacti-pick-event-filter' >
						<?php _e( 'Pick an event', BOOKACTI_PLUGIN_NAME ); ?>
					</a>
				</div>
			</div>
			<?php
				do_action( 'bookacti_after_booking_filters' );
			?>
			<div id='bookacti-event-filter-container' class='bookacti-bookings-filter-container' >
				<div class='bookacti-bookings-filter-title' >
					<?php echo esc_html_x( 'Filter', 'verb', BOOKACTI_PLUGIN_NAME ); ?>
				</div>
				<div class='bookacti-bookings-filter-content' >
					<input type='submit' class='button button-primary button-large' value='<?php _e( 'Apply filters', BOOKACTI_PLUGIN_NAME ); ?>' />
				</div>
			</div>
		</form>
	</div>
	
	<?php
/*
	// Display booking system title
	add_filter( 'bookacti_booking_system_title', 'bookacti_bookings_booking_system_title', 10, 2 );
	function bookacti_bookings_booking_system_title( $title, $atts ) {
		if( $atts[ 'id' ] === 'bookacti-booking-system-bookings-page' ) {
			$title = '<h2>' . esc_html__( 'Pick an event to show its bookings', BOOKACTI_PLUGIN_NAME ) . '</h2>';
		}
		return $title;
	}
	
	// Display the booking system
	$atts = array( 
		'calendars'				=> array( $default_template ),
		'group_categories'		=> array(),
		'groups_only'			=> 0,
		'groups_single_events'	=> 1,
		'method'				=> 'calendar',
		'id'					=> 'booking-system-bookings-page',
		'classes'				=> 'admin-booking-system',
		'past_events'			=> 1
	);
    bookacti_get_booking_system( $atts, true );
*/
	?>
	
	<div id='bookacti-bookings-list-container' >
		<?php
			do_action( 'bookacti_before_booking_list' );
		?>
		<div id='bookacti-bookings-list' >
		<?php
			$bookings_list_table = new Bookings_List_Table();
			$bookings_list_table->prepare_items();
			$bookings_list_table->display();
		?>
		</div>
		<?php
			do_action( 'bookacti_after_booking_list' );
		?>
	</div>
	
</div>

<?php 

echo "</div>"; // end of wp wrap

//Include dialogs
include_once( 'view-backend-bookings-dialogs.php' );
include_once( 'view-bookings-dialogs.php' );
