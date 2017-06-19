<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id='bookacti-booking-system-dialogs' >
	<!-- Choose a group of events -->
	<div id='bookacti-choose-group-of-events-dialog' class='bookacti-booking-system-dialog' >
		<?php
			echo apply_filters( 'bookacti_choose_group_of_events_title', __( 'This event is available in several bundle. Which one do you want to pick?', BOOKACTI_PLUGIN_NAME ) );
		?>
		<div id='bookacti-groups-of-events-list' >
		</div>
	</div>
</div>