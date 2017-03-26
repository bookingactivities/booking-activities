<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

echo "<div class='wrap'>";
echo "<h2>" . esc_html__( 'Bookings', BOOKACTI_PLUGIN_NAME ) . "</h2>";

if( empty( bookacti_fetch_templates() ) ) {
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
		<div id='bookacti-bookings-filters-title' class='bookacti-bookings-title' >
			<h3><?php echo esc_html_x( 'Filters', 'The plural noun', BOOKACTI_PLUGIN_NAME ); ?></h3>
		</div>
<!--		
		<div id='bookacti-bookings-filters-param-gear' class='bookacti-bookings-title-gear' >
			<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ); ?>' />
		</div>
-->
		<div id='bookacti-bookings-filters-global-actions' class='bookacti-booking-actions' >
		<?php
			do_action( 'bookacti_booking_filters_global_actions' );
		?>
		</div>
		<?php
			do_action( 'bookacti_before_booking_filters' );
		?>
		<div class='bookacti-templates-filter-container' class='bookacti-bookings-filter-container' >
			<div id='bookacti-templates-filter-title' class='bookacti-bookings-filter-title' >
				<h4><?php echo esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ); ?></h4>
			</div>
			<div id='bookacti-templates-filter-content' >
			<?php
				$templates = bookacti_fetch_templates();
				$default_template = bookacti_get_user_default_template();
				$i = 0;
				foreach ( $templates as $template ) {
					if( ! $default_template && $i === 0 ) { $default_template = $template->id; }
					
					echo  "<div class='bookacti-bookings-filter-template bookacti-bookings-filter' "
						.	"data-template-id='"    . esc_attr( $template->id ) . "' "
						.	selected( $template->id, $default_template, false )
						. " >"
						.	esc_html( stripslashes( $template->title ) )
						. "</div>";
					
					$i++;
				}
			?>
			</div>
		</div>
		<div id='bookacti-activities-filter-container' class='bookacti-bookings-filter-container' >
			<div id='bookacti-activities-filter-title' class='bookacti-bookings-filter-title' >
				<h4><?php echo esc_html__( 'Activities', BOOKACTI_PLUGIN_NAME ); ?></h4>
			</div>
			<div id='bookacti-activities-filter-content' class='bookacti-bookings-filter-content' >
			<?php
				echo bookacti_get_activities_html_for_booking_page( $default_template );
			?>
			</div>
		</div>
		<?php
			do_action( 'bookacti_after_booking_filters' );
		?>
	</div>
	
	<?php
	// Display booking system title
	add_filter( 'bookacti_booking_system_title', 'bookacti_bookings_booking_system_title', 10, 6 );
	function bookacti_bookings_booking_system_title( $title, $templates, $activities, $booking_method, $id, $classes ) {
		if( $id === 'bookings-page' ) {
			$title = '<h3>' . esc_html__( 'Pick an event to show its bookings', BOOKACTI_PLUGIN_NAME ) . '</h3>';
		}
		return $title;
	}
	
	// Display the booking system
    bookacti_display_booking_system( array( $default_template ), array(), 'calendar', 'bookings-page', 'admin-booking-system' );
	?>
	
	<div id='bookacti-bookings-list-container'>
		<div id='bookacti-bookings-list-title' class='bookacti-bookings-title' >
			<h3><?php echo esc_html__( 'Booking list', BOOKACTI_PLUGIN_NAME ); ?></h3>
		</div>
		<div id='bookacti-bookings-list-param-gear' class='bookacti-bookings-title-gear' >
			<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ); ?>' />
		</div>
		<div id='bookacti-bookings-list-global-actions' class='bookacti-booking-actions' >
		<?php
			echo bookacti_get_booking_global_actions_html( array(), 'admin' );
		?>
		</div>
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
