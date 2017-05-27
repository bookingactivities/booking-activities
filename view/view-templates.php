<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

echo "<div class='wrap'>";
echo "<h2>" . esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ) . "</h2>";

?>

<div id='bookacti-template-container'>
    <?php
	$templates = bookacti_fetch_templates();
	?>
    <div id='bookacti-template-sidebar' class='<?php if( empty( $templates ) ) { echo 'bookacti-no-template'; } ?>'>
        
        <div class='bookacti-templates-box' >
				<div class='bookacti-template-box-title' >
					<h4><?php echo esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ); ?></h4>
					<?php if( current_user_can( 'bookacti_create_templates' ) ) { ?>
					<div class='bookacti-insert-button' id='bookacti-insert-template' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
					<?php } ?>
				</div>
				<div id='bookacti-template-picker-container' >
					<select name='template-picker' id='bookacti-template-picker' >
					<?php
						if( ! empty( $templates ) ) {
							$default_template = bookacti_get_user_default_template();

							$default_template_found = false;
							foreach ( $templates as $template ) {

								$selected = selected( $default_template, $template->id, false );

								if( $selected !== '' ) { $default_template_found = true; }

								echo "<option value='"			. esc_attr( $template->id )
									. "' data-template-start='" . esc_attr( $template->start_date )
									. "' data-template-end='"   . esc_attr( $template->end_date )
									. "' " . $selected . " >"
									. esc_html( stripslashes( $template->title ) )
									. "</option>";
							}

							if ( ! $default_template_found ) { 
								reset( $templates );
								$current_template = current( $templates );
								$default_template = $current_template->id; 
								bookacti_update_user_default_template( $default_template );
							}
						}
					?>
					</select>
				</div>
				<?php if( current_user_can( 'bookacti_edit_templates' ) ) { ?>
					<div id='bookacti-update-template' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ); ?>' />
					</div>
				<?php } ?>
        </div>


        <div id='bookacti-template-activities-container' class='bookacti-templates-box' >
            <div class='bookacti-template-box-title' >
                <h4><?php echo esc_html__( 'Activities', BOOKACTI_PLUGIN_NAME ); ?></h4>
				<?php if( current_user_can( 'bookacti_create_activities' ) ) { ?>
                <div class='bookacti-insert-button' id='bookacti-insert-activity' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
				<?php } ?>
            </div>
            
            <?php
			// Display list of activities
			$activity_list = '';
			if( isset( $default_template ) && $default_template > 0 ) {
				$activity_list = bookacti_get_template_activities_list( $default_template ); 
			}
			?>
			<div id='bookacti-template-activity-list' >
				<?php
				if( $activity_list !== '' ) {
					echo $activity_list;
				} else if( ! current_user_can( 'bookacti_create_activities' ) ) {
					?>
					<div id='bookacti-template-no-activity' >
						<h2>
							<?php esc_html_e( 'There is no activity available, and you are not allowed to create one.', BOOKACTI_PLUGIN_NAME ); ?>
						</h2>
					</div>
					<?php
				}
				?>
			</div>
			<?php if( current_user_can( 'bookacti_create_activities' ) ) { ?>
				<div id='bookacti-template-first-activity-container' style='display:<?php echo $activity_list !== '' ? 'none' : 'block'; ?>;' >
					<h2>
						<?php _e( 'Create your first activity', BOOKACTI_PLUGIN_NAME ); ?>
					</h2>
					<div id='bookacti-template-add-first-activity-button' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' />
					</div>
				</div>
			<?php } ?>
        </div>
        
		
		<div class='bookacti-templates-box' >
				<div class='bookacti-template-box-title' >
					<h4><?php echo esc_html__( 'Event groups', BOOKACTI_PLUGIN_NAME ); ?></h4>
					<?php if( current_user_can( 'bookacti_edit_templates' ) ) { ?>
					<div class='bookacti-insert-button' id='bookacti-insert-group-set' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
					<?php } ?>
				</div>
				<div id='bookacti-group-sets' >
					<div class='bookacti-group-set-show-hide' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/show.png' ); ?>' />
					</div>
					<div class='bookacti-group-set-title' >
						Set #1
					</div>
					<?php if( current_user_can( 'bookacti_edit_templates' ) ) { ?>
						<div id='bookacti-update-group-set' >
							<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ); ?>' />
						</div>
					<?php } ?>
					<?php if( current_user_can( 'bookacti_edit_templates' ) ) { ?>
						<div id='bookacti-add-group' >
							<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' />
						</div>
					<?php } ?>
				</div>
        </div>
    </div>
	
	<div id='bookacti-template-content' >
		<?php
		if( ! empty( $templates ) ) {
		?>
			<div id='bookacti-template-calendar'></div>
		<?php
		} else if( current_user_can( 'bookacti_create_templates' ) ) {
			?>
			<div id='bookacti-first-template-container'>
				<h2>
					<?php esc_html_e( "Welcome to Booking Activities! Let's start by creating your first calendar", BOOKACTI_PLUGIN_NAME ); ?>
				</h2>
				<div id='bookacti-add-first-template-button' >
					<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' />
				</div>
			</div>
			<?php
		} else {
			?>
			<div id='bookacti-no-template-container'>
				<h2>
					<?php esc_html_e( 'There is no calendar available, and you are not allowed to create one.', BOOKACTI_PLUGIN_NAME ); ?>
				</h2>
			</div>
			<?php
		}
		?>
	</div>
</div>
<hr/>
<div id='bookacti-shortcode-generator-container' class='<?php if( empty( $templates ) ) { echo 'bookacti-no-template'; } ?>' >
	<?php 
		$template_id = $activity_ids = '';
		if( ! empty( $default_template ) ) {
			$template_id = $default_template;
			$activity_ids = implode( ',', bookacti_get_activities_by_template_ids( array( $template_id ), true ) );
		}
	?>
	<h3><?php esc_html_e( 'Shortcodes', BOOKACTI_PLUGIN_NAME ); ?></h3>
	<h4><?php esc_html_e( 'Copy and paste them in any post you want:', BOOKACTI_PLUGIN_NAME ); ?></h4>
	<p>
		<code>
			[bookingactivities_form	calendars='<span class='bookacti-shortcode-calendar-ids'><?php echo esc_html( $template_id ); ?></span>' 
									activities='<span class='bookacti-shortcode-activity-ids'><?php echo esc_html( $activity_ids ); ?></span>']
		</code>
		<?php 
			$tip = __( 'This shortcode will display a booking form with this calendar. Users will be able to book an event via this form.', BOOKACTI_PLUGIN_NAME );
			bookacti_help_tip( $tip ); 
		?>
	</p>
	<p>
		<code>
			[bookingactivities_calendar	calendars='<span class='bookacti-shortcode-calendar-ids'><?php echo esc_html( $template_id ); ?></span>' 
										activities='<span class='bookacti-shortcode-activity-ids'><?php echo esc_html( $activity_ids ); ?></span>']
		</code>
		<?php 
			$tip = __( 'This shortcode will display this calendar alone. Users will only be able to browse the calendar, they can\'t make booking with it.', BOOKACTI_PLUGIN_NAME );
			bookacti_help_tip( $tip ); 
		?>
	</p>
</div>

<?php 
//Include dialogs
include_once( 'view-templates-dialogs.php' );