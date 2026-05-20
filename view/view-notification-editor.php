<?php
/**
 * Notification editor page
 * @since 1.18.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action             = ! empty( $_GET[ 'action' ] ) && in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ? sanitize_title_with_dashes( $_GET[ 'action' ] ) : '';
$is_new             = $action === 'new';
$notification_id    = ! empty( $_GET[ 'notification_id' ] ) ? sanitize_title_with_dashes( $_GET[ 'notification_id' ] ) : '';
$notification_db_id = ! empty( $_GET[ 'notification_db_id' ] ) ? intval( $_GET[ 'notification_db_id' ] ) : 0;

if( ! $action ) { return; }
if( ! $is_new && ! $notification_db_id && ! $notification_id ) { return; }

// Get notification raw data
$notification_raw = $notification_db_id || $notification_id ? bookacti_get_notification_data( $notification_db_id ? $notification_db_id : $notification_id, true ) : array();
if( ! $is_new && ! $notification_raw ) { return; }

if( $is_new ) {
	if( ! $notification_raw && $notification_id ) {
		$notification_raw = bookacti_get_notification_default_values( $notification_id );
	}
	
	$notification_raw[ 'db_id' ]  = 0;
	$notification_raw[ 'status' ] = '';
}

// Get edit data in the default language
$lang_switched     = bookacti_switch_locale( bookacti_get_site_default_locale() );
$notification_edit = bookacti_format_notification_data( $notification_raw, 'edit' );
if( $lang_switched ) { bookacti_restore_locale(); }

// Exit if not allowed to edit current notification
$notification_id         = ! empty( $notification_edit[ 'id' ] ) ? $notification_edit[ 'id' ] : '';
$notification_db_id      = ! empty( $notification_edit[ 'db_id' ] ) ? $notification_edit[ 'db_id' ] : 0;
$can_edit_notification   = $is_new ? current_user_can( 'bookacti_create_notifications' ) : current_user_can( 'bookacti_edit_notifications' );
$can_manage_notification = $is_new ? $can_edit_notification : bookacti_user_can_manage_notification( $notification_db_id );
if( ! $can_edit_notification || ! $can_manage_notification ) { esc_html_e( 'You are not allowed to do that.', 'booking-activities' ); return; }

?>
<div class='wrap'>
	<h1><?php echo $is_new ? esc_html__( 'Add new notification', 'booking-activities' ) : /* translators: %1$s is the database ID, %2$s is the notification user-friendly identifier. */ sprintf( esc_html__( 'Edit notification #%1$s - %2$s', 'booking-activities' ), $notification_db_id, '<code>' . $notification_id . '</code>' ); ?></h1>
	<hr class='wp-header-end'/>
	<div id='bookacti-notification-editor-page-container'>
		<?php
			do_action( 'bookacti_notification_editor_page_before', $notification_edit );
			$redirect_url = ! $is_new && $notification_id ? 'admin.php?page=bookacti_notifications&action=edit&notification_id=' . $notification_id : 'admin.php?page=bookacti_notifications';
		?>
		<form name='post' action='<?php echo esc_attr( $redirect_url ); ?>' method='post' id='bookacti-notification-editor-page-form' novalidate>
			<?php
			/* Used to save closed meta boxes and their order */
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			?>
			<input type='hidden' name='page' value='bookacti_notifications'/>
			<input type='hidden' name='action' value='<?php echo esc_attr( $is_new ? 'create' : 'edit' ); ?>'/>
			<input type='hidden' name='nonce' value='<?php echo wp_create_nonce( 'bookacti_update_notification' ); ?>'/>
			<input type='hidden' name='notification_db_id' value='<?php echo esc_attr( $notification_db_id ); ?>' id='bookacti-notification-db-id'/>
			
			<div id='bookacti-notification-editor-page-lang-switcher' class='bookacti-lang-switcher'></div>
			
			<div id='poststuff'>
				<div id='post-body' class='metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>'>
					<div id='post-body-content'>
						<div id='titlediv'>
							<div id='titlewrap'>
								<?php $title_placeholder = esc_html__( 'Enter notification title here', 'booking-activities' ); ?>
								<label class='screen-reader-text' id='title-prompt-text' for='title'><?php echo $title_placeholder; ?></label>
								<input type='text' name='title' size='30' value='<?php esc_attr_e( $notification_edit[ 'title' ] ); ?>' id='title' class='bookacti-translatable' spellcheck='true' autocomplete='off' placeholder='<?php echo esc_attr( $title_placeholder ); ?>' required/>
							</div>
						</div>
						<div id='postdivrich' class='postarea'></div>
					</div>
					<div id='postbox-container-1' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'side', $notification_edit );
					?>
					</div>
					<div id='postbox-container-2' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'normal', $notification_edit );
						do_meta_boxes( null, 'advanced', $notification_edit );
					?>
					</div>
				</div>
				<br class='clear'/>
			</div>
		</form>
		<?php
			do_action( 'bookacti_notification_editor_page_after', $notification_edit );
		?>
	</div>
</div>
<?php