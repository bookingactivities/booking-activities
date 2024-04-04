<?php
/**
 * Form list page
 * @since 1.5.0
 * @version 1.16.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php echo esc_html__( 'Booking Forms', 'booking-activities' ); ?></h1>
	<?php 
	$templates = bookacti_get_templates_data();
	if( current_user_can( 'bookacti_create_forms' ) && $templates ) { 
		?>
		<a href='<?php echo esc_url( admin_url( 'admin.php?page=bookacti_forms&action=new' ) ); ?>' class='page-title-action'>
			<?php esc_html_e( 'Add New Form', 'booking-activities' ); ?>
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
		
	<div id='bookacti-forms-filters-container' >
		<form id='bookacti-forms-filters-form' action=''>
			<input type='hidden' name='page' value='bookacti_forms'/>
			<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_get_form_list' ); ?>'/>
			<?php
				// Status
				$status = ! empty( $_REQUEST[ 'status' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'status' ] ) : '';
				if( $status ) {
					echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '"/>';
				}
			
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

				do_action( 'bookacti_before_forms_filters' );
			?>
			<div id='bookacti-form-id-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Form ID', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$args = array( 
						'name'  => 'id',
						'id'    => 'bookacti-forms-filter-id',
						'type'  => 'number',
						'value' => ! empty( $_REQUEST[ 'id' ] ) ? intval( $_REQUEST[ 'id' ] ) : ''
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			<div id='bookacti-form-title-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Title', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$args = array( 
						'name'  => 'title',
						'id'    => 'bookacti-forms-filter-title',
						'type'  => 'text',
						'value' => ! empty( $_REQUEST[ 'title' ] ) ? sanitize_text_field( $_REQUEST[ 'title' ] ) : ''
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			<div id='bookacti-form-author-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Author', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$author = ! empty( $_REQUEST[ 'user_id' ] ) ? intval( $_REQUEST[ 'user_id' ] ) : '';
					$args = apply_filters( 'bookacti_form_list_author_selectbox_args', array(
						'name'            => 'user_id',
						'id'              => 'bookacti-forms-filter-author',
						'option_label'    => array( 'display_name', ' (#', 'ID', ' - ', 'user_login', ') - ', 'roles' ),
						'selected'        => $author,
						'allow_clear'     => 1,
						'echo'            => 1
					));
					bookacti_display_user_selectbox( $args );
				?>
				</div>
			</div>
			<?php
				do_action( 'bookacti_after_forms_filters' );
			?>
			<div id='bookacti-actions-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Actions', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
					<input type='submit' class='button button-primary button-large' id='bookacti-submit-filter-button' value='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_html_e( 'Filter the list', 'booking-activities' ); ?>'/>
				</div>
			</div>
		</form>
	</div>
	
	<div id='bookacti-form-list-container'>
		<?php
			do_action( 'bookacti_before_form_list' );
		?>
		<div id='bookacti-form-list'>
		<?php
			$bookings_list_table = new Forms_List_Table();
			$bookings_list_table->prepare_items();
			$bookings_list_table->views();
			$bookings_list_table->display();
		?>
		</div>
		<?php
			do_action( 'bookacti_after_form_list' );
		?>
	</div>
</div>