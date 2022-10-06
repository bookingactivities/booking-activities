<?php
/**
 * Form list page
 * @since 1.5.0
 * @version 1.15.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php echo esc_html__( 'Booking Forms', 'booking-activities' ); ?></h1>
	<?php 
	$templates = bookacti_get_templates_data();
	$can_create_form = current_user_can( 'bookacti_create_forms' );
	if( $can_create_form && $templates ) { 
		?>
		<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>' class='page-title-action' >
			<?php echo esc_html_x( 'Add New', 'post' ); ?>
		</a>
		<?php
	}
	
	do_action( 'bookacti_form_list_page_header' );
	?>
	<hr class='wp-header-end'/>
	
	<?php
	// Check if the user has available calendars
	if( ! $templates ) {
		$editor_path = 'admin.php?page=bookacti_calendars';
		$editor_url = admin_url( $editor_path );
		?>
			<div id='bookacti-first-template-container'>
				<h2>
					<?php
					/* translators: %s is a link to "Calendar Editor" page. */
					echo sprintf( esc_html__( 'Welcome! It seems you don\'t have any calendar yet. Go to %s to create your first calendar', 'booking-activities' ), '<a href="' . esc_url( $editor_url ) . '" >' . esc_html__( 'Calendar Editor', 'booking-activities' ) . '</a>' );
					?>
				</h2>
			</div>
		</div><!-- end of wp wrap -->
		<?php
		exit;
	}
	?>
	
	<div id='bookacti-form-list-container'>
		<?php
			do_action( 'bookacti_before_form_list' );
		?>
		<div id='bookacti-form-list'>
		<?php
			$bookings_list_table = new Forms_List_Table();
			
			$filters = array(
				'id'		=> isset( $_REQUEST[ 'id' ] )		? $_REQUEST[ 'id' ] : array(), 
				'title'		=> isset( $_REQUEST[ 'title' ] )	? $_REQUEST[ 'title' ] : '', 
				'user_id'	=> isset( $_REQUEST[ 'user_id' ] )	? $_REQUEST[ 'user_id' ] : 0, 
				'status'	=> isset( $_REQUEST[ 'status' ] )	? $_REQUEST[ 'status' ] : array( 'publish' ), 
				'active'	=> isset( $_REQUEST[ 'active' ] )	? $_REQUEST[ 'active' ] : false, 
				'order_by'	=> isset( $_REQUEST[ 'orderby' ] )	? $_REQUEST[ 'orderby' ] : array( 'id' ),
				'order'		=> isset( $_REQUEST[ 'order' ] )	? $_REQUEST[ 'order' ] : 'DESC'
			);
			$bookings_list_table->prepare_items( $filters );
			$bookings_list_table->views();
			$bookings_list_table->display();
		?>
		</div>
		<?php
			do_action( 'bookacti_after_form_list' );
		?>
	</div>
</div>