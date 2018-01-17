<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$current_user_can_create_template	= current_user_can( 'bookacti_create_templates' );
$current_user_can_edit_template		= current_user_can( 'bookacti_edit_templates' );
$current_user_can_create_activities	= current_user_can( 'bookacti_create_activities' );
$default_template = false;

echo "<div class='wrap'>";
echo "<h1>" . esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ) . "</h1>";

?>


<div id='bookacti-fatal-error' style='display:none;'>
	<p><strong><?php esc_html_e( 'A fatal error occurred. Please try to refresh the page. If the error persists, contact the support.', BOOKACTI_PLUGIN_NAME ); ?></strong></p>
	<p>
		<em><?php esc_html_e( 'Advanced users, you can stop loading and free the fields to try to solve your problem:', BOOKACTI_PLUGIN_NAME ); ?></em>
		<input type='button' id='bookacti-exit-loading' value='<?php esc_attr_e( 'Stop loading and free fields', BOOKACTI_PLUGIN_NAME ) ?>' />
	</p>
</div>


<div id='bookacti-template-container'>
    <?php
	$templates = bookacti_fetch_templates();
	?>
    <div id='bookacti-template-sidebar' class='<?php if( ! $templates ) { echo 'bookacti-no-template'; } ?>'>
        
        <div id='bookacti-template-templates-container' class='bookacti-templates-box' >
				<div class='bookacti-template-box-title' >
					<h4><?php echo esc_html__( 'Calendars', BOOKACTI_PLUGIN_NAME ); ?></h4>
					<?php if( $current_user_can_create_template ) { ?>
					<div class='bookacti-insert-button' id='bookacti-insert-template' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
					<?php } ?>
				</div>
				<div id='bookacti-template-picker-container' >
					<select name='template-picker' id='bookacti-template-picker' >
					<?php
						if( $templates ) {
							$default_template = get_user_meta( get_current_user_id(), 'bookacti_default_template', true );

							$default_template_found = false;
							foreach ( $templates as $template ) {

								$selected = selected( $default_template, $template[ 'id' ], false );

								if( ! empty( $selected ) ) { $default_template_found = true; }

								echo "<option value='"			. esc_attr( $template[ 'id' ] )
									. "' data-template-start='" . esc_attr( $template[ 'start' ] )
									. "' data-template-end='"   . esc_attr( $template[ 'end' ] )
									. "' " . $selected . " >"
									. esc_html( $template[ 'title' ] )
									. "</option>";
							}

							if ( ! $default_template_found ) { 
								reset( $templates );
								$current_template = current( $templates );
								$default_template = $current_template[ 'id' ];
								update_user_meta( get_current_user_id(), 'bookacti_default_template', $default_template );
							}
						}
					?>
					</select>
				</div>
				<?php if( $current_user_can_edit_template ) { ?>
					<div id='bookacti-update-template' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ); ?>' />
					</div>
				<?php } ?>
        </div>


        <div id='bookacti-template-activities-container' class='bookacti-templates-box' >
            <div class='bookacti-template-box-title' >
                <h4><?php echo esc_html__( 'Activities', BOOKACTI_PLUGIN_NAME ); ?></h4>
				<?php if( $current_user_can_create_activities ) { ?>
                <div class='bookacti-insert-button' id='bookacti-insert-activity' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
				<?php } ?>
            </div>
            
            <?php
			// Display list of activities
			$activity_list = '';
			if( $default_template ) {
				$activity_list = bookacti_get_template_activities_list( $default_template ); 
			}
			?>
			<div id='bookacti-template-activity-list' >
				<?php
				if( ! empty( $activity_list ) ) {
					echo $activity_list;
				} else if( ! $current_user_can_create_activities ) {
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
			<?php if( $current_user_can_create_activities ) { ?>
				<div id='bookacti-template-first-activity-container' style='display:<?php echo empty( $activity_list ) ? 'block' : 'none'; ?>;' >
					<h2>
						<?php _e( 'Create your first activity', BOOKACTI_PLUGIN_NAME ); ?>
					</h2>
					<div id='bookacti-template-add-first-activity-button' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' />
					</div>
				</div>
			<?php } ?>
        </div>
        
		
		<div id='bookacti-template-groups-of-events-container' class='bookacti-templates-box' >
			<div class='bookacti-template-box-title' >
				<h4><?php echo esc_html__( 'Groups of events', BOOKACTI_PLUGIN_NAME ); ?></h4>
				<?php if( $current_user_can_edit_template ) { ?>
                <div class='bookacti-insert-button' id='bookacti-insert-group-of-events' ><img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' /></div>
				<?php } ?>
			</div>
			
			<div id='bookacti-group-categories' >
				<?php 
					// Display the template's groups of events list
					$groups_list = bookacti_get_template_groups_of_events_list( $default_template );
					if( ! empty( $groups_list ) ) { echo $groups_list; }
				?>
			</div>
			
			<?php
			// If no goup categories exists, display a tuto to create a group of events
			if( $current_user_can_edit_template ) {
				?>
				<p id='bookacti-template-add-group-of-events-tuto-select-events' style='<?php if( ! empty( $groups_list ) ) { echo 'display:none;'; } ?>' >
					<?php _e( 'Select at least 2 events to create a group of events', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
				
				<div id='bookacti-template-add-first-group-of-events-container' >
					<h2>
						<?php _e( 'Create your first group of events', BOOKACTI_PLUGIN_NAME ); ?>
					</h2>
					<div id='bookacti-template-add-first-group-of-events-button' >
						<img src='<?php echo esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add.png' ); ?>' />
					</div>
				</div>
				<?php
			}
			?>
        </div>
    </div>
	
	<div id='bookacti-template-content' >
		<?php
		if( $templates ) {
		?>
			<div id='bookacti-template-calendar'></div>
		<?php
		} else if( $current_user_can_create_template ) {
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
<div id='bookacti-shortcode-generator-container' class='<?php if( ! $templates ) { echo 'bookacti-no-template'; } ?>' >
	<?php 
		$template_id = $activity_ids = $category_ids = '';
		$categories = array();
		if( ! empty( $default_template ) ) {
			$template_id	= $default_template;
			$activity_ids	= implode( ',', bookacti_get_activity_ids_by_template( array( $template_id ), false ) );
			$category_ids	= implode( ',', bookacti_get_group_category_ids_by_template( array( $template_id ) ) );
		}
	?>
	<h3><?php esc_html_e( 'Shortcodes', BOOKACTI_PLUGIN_NAME ); ?></h3>
	<h4><?php esc_html_e( 'Copy and paste them in any post you want:', BOOKACTI_PLUGIN_NAME ); ?></h4>
	<p>
		<span id='bookacti-shortcode-form-constructor' class='bookacti-shortcode-constructor' >
			[bookingactivities_form	calendars='<span class='bookacti-shortcode-calendar-ids'><?php echo esc_html( $template_id ); ?></span>' 
									activities='<span class='bookacti-shortcode-activity-ids'><?php echo esc_html( $activity_ids ); ?></span>' 
									group_categories='<span class='bookacti-shortcode-group-category-ids'><?php echo esc_html( $category_ids ); ?></span>']
		</span>
		<code id='bookacti-shortcode-form' class='bookacti-shortcode' >
			[bookingactivities_form]
		</code>
		<?php 
			$tip = __( 'This shortcode will display a booking form with this calendar. Users will be able to book an event via this form.', BOOKACTI_PLUGIN_NAME );
			bookacti_help_tip( $tip ); 
		?>
	</p>
	<p>
		<span id='bookacti-shortcode-calendar-constructor' class='bookacti-shortcode-constructor' >
			[bookingactivities_calendar	calendars='<span class='bookacti-shortcode-calendar-ids'><?php echo esc_html( $template_id ); ?></span>' 
										activities='<span class='bookacti-shortcode-activity-ids'><?php echo esc_html( $activity_ids ); ?></span>' 
										group_categories='<span class='bookacti-shortcode-group-category-ids'><?php echo esc_html( $category_ids ); ?></span>']
		</span>
		<code id='bookacti-shortcode-calendar' class='bookacti-shortcode' >
			[bookingactivities_calendar]
		</code>
		<?php 
			$tip = __( "This shortcode will display this calendar alone. Users will only be able to browse the calendar, they can't make booking with it.", BOOKACTI_PLUGIN_NAME );
			bookacti_help_tip( $tip ); 
		?>
	</p>
</div>

<?php 
//Include dialogs
include_once( 'view-templates-dialogs.php' );