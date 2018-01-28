<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id='bookacti-booking-system-dialogs' >
	<!-- Choose a group of events -->
	<div id='bookacti-choose-group-of-events-dialog' class='bookacti-booking-system-dialog' style='display:none;' >
		<?php
			echo bookacti_get_message( 'choose_group_dialog_content' );
		?>
		<div id='bookacti-groups-of-events-list' >
		</div>
	</div>
</div>