<?php
/**
 * Notification list page
 * @since 1.18.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Notifications', 'booking-activities' ); ?></h1>
	<?php do_action( 'bookacti_notification_list_page_header' ); ?>
	<hr class='wp-header-end'/>
	
	<div id='bookacti-notifications-filters-container'>
		<form id='bookacti-notifications-filters-form' action=''>
			<input type='hidden' name='page' value='bookacti_notifications'/>
			<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_get_notification_list' ); ?>'/>
			<?php
				// Status
				$status = ! empty( $_GET[ 'status' ] ) ? sanitize_title_with_dashes( $_GET[ 'status' ] ) : '';
				if( $status ) {
					echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '"/>';
				}
			
				// Display sorting data
				$order_by = ! empty( $_GET[ 'order_by' ] ) ? $_GET[ 'order_by' ] : ( ! empty( $_GET[ 'orderby' ] ) ? $_GET[ 'orderby' ] : array() );
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
				$order = ! empty( $_GET[ 'order' ] ) ? sanitize_title_with_dashes( $_GET[ 'order' ] ) : '';
				if( $order ) {
					echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
				}

				do_action( 'bookacti_before_notifications_filters' );
			?>
			<div id='bookacti-notification-title-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Title', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$args = array( 
						'name'  => 'title',
						'id'    => 'bookacti-notifications-filter-title',
						'type'  => 'text',
						'value' => ! empty( $_GET[ 'title' ] ) ? sanitize_text_field( $_GET[ 'title' ] ) : ''
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			
			<div id='bookacti-notification-target-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Recipient', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$args = array( 
						'name'    => 'target',
						'id'      => 'bookacti-notifications-filter-target',
						'type'    => 'select',
						'options' => array(
							''         => esc_html__( 'All', 'booking-activities' ),
							'admin'    => esc_html__( 'Administrator', 'booking-activities' ),
							'customer' => esc_html__( 'Customer', 'booking-activities' )
						),
						'value'   => ! empty( $_GET[ 'target' ] ) ? sanitize_title_with_dashes( $_GET[ 'target' ] ) : ''
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			
			<div id='bookacti-notification-trigger-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Trigger', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$notifications_default_values = bookacti_get_notifications_default_values();
					$trigger_options              = array( '' => esc_html__( 'All', 'booking-activities' ) );
					foreach( $notifications_default_values as $notification_default_values ) {
						$trigger = ! empty( $notification_default_values[ 'trigger' ] ) ? $notification_default_values[ 'trigger' ] : '';
						if( ! $trigger ) { continue; }
						
						$trigger_options[ $trigger ] = ! empty( $notification_default_values[ 'title' ] ) ? $notification_default_values[ 'title' ] : $trigger;
					}
					
					$args = array( 
						'name'     => 'in__trigger',
						'id'       => 'bookacti-notifications-filter-trigger',
						'type'     => 'select',
						'multiple' => 'maybe',
						'options'  => $trigger_options,
						'value'    => ! empty( $_GET[ 'in__trigger' ] ) ? array_values( bookacti_str_ids_to_array( $_GET[ 'in__trigger' ] ) ) : array()
					);
					bookacti_display_field( $args );
				?>
				</div>
			</div>
			
			<div id='bookacti-notification-author-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Author', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
				<?php
					$author = ! empty( $_GET[ 'user_id' ] ) ? intval( $_GET[ 'user_id' ] ) : '';
					$args = apply_filters( 'bookacti_notification_list_author_selectbox_args', array(
						'name'         => 'user_id',
						'id'           => 'bookacti-notifications-filter-author',
						'option_label' => array( 'display_name', ' (#', 'ID', ' - ', 'user_login', ') - ', 'roles' ),
						'selected'     => $author,
						'allow_clear'  => 1,
						'echo'         => 1
					));
					bookacti_display_user_selectbox( $args );
				?>
				</div>
			</div>
			
			<?php
				do_action( 'bookacti_after_notifications_filters' );
			?>
			
			<div id='bookacti-actions-filter-container' class='bookacti-filter-container'>
				<div class='bookacti-filter-title'>
					<?php esc_html_e( 'Actions', 'booking-activities' ); ?>
				</div>
				<div class='bookacti-filter-content'>
					<input type='submit' class='button button-primary button-large' id='bookacti-submit-filter-button' value='<?php esc_attr_e( 'Filter the list', 'booking-activities' ); ?>' title='<?php esc_attr_e( 'Filter the list', 'booking-activities' ); ?>'/>
				</div>
			</div>
		</form>
	</div>
	
	<?php
		// Prepare notification list items
		$notifications_list_table = new BOOKACTI_Notifications_List_Table();
		$notifications_list_table->prepare_items();

		$notification_ids = array();
		foreach( $notifications_list_table->items as $item ) {
			$notification_ids[] = $item[ 'id' ];
		}
	?>
	
	<div id='bookacti-notification-list-container'>
		<?php do_action( 'bookacti_before_notification_list' ); ?>
		<div id='bookacti-notification-list'>
		<?php
			// Display notification list
			$notifications_list_table->views();
			$notifications_list_table->display();
		?>
		</div>
		<?php do_action( 'bookacti_after_notification_list' ); ?>
	</div>
	
	<?php do_action( 'bookacti_notification_list_page_after' ); ?>
</div>
<?php