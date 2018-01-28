<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// PERMISSIONS
	/**
	 * Check if user is allowed to manage template
	 * 
	 * @param int $template_id
	 * @param int $user_id
	 * @return boolean
	 */
	function bookacti_user_can_manage_template( $template_id, $user_id = false ) {

		$user_can_manage_template = false;
		$bypass_template_managers_check = apply_filters( 'bookacti_bypass_template_managers_check', false );
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		if( is_super_admin() || $bypass_template_managers_check ) { $user_can_manage_template = true; }
		else {
			$admins = bookacti_get_template_managers( $template_id );
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_template = true; }
		}

		return apply_filters( 'bookacti_user_can_manage_template', $user_can_manage_template, $template_id, $user_id );
	}


	// CHECK IF USER IS ALLOWED TO MANAGE ACTIVITY
	function bookacti_user_can_manage_activity( $activity_id, $user_id = false ) {

		$user_can_manage_activity = false;
		$bypass_activity_managers_check = apply_filters( 'bookacti_bypass_activity_managers_check', false );
		if( ! $user_id ) { $user_id = get_current_user_id(); }
		if( is_super_admin() || $bypass_activity_managers_check ) { $user_can_manage_activity = true; }
		else {
			$admins = bookacti_get_activity_managers( $activity_id );
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_activity = true; }
		}

		return apply_filters( 'bookacti_user_can_manage_activity', $user_can_manage_activity, $activity_id, $user_id );
	}
	
	
	// GET TEMPLATE MANAGERS
	function bookacti_get_template_managers( $template_id ) {
		return bookacti_get_managers( 'template', $template_id );
	}
	
	
	// GET ACTIVITY MANAGERS
	function bookacti_get_activity_managers( $activity_id ) {	
		return bookacti_get_managers( 'activity', $activity_id );
	}

	

// TEMPLATE X ACTIVITIES
	/**
	 * Retrieve template activities list
	 * 
	 * @version 1.3.0
	 * @param int $template_id
	 * @return boolean|string 
	 */
	function bookacti_get_template_activities_list( $template_id ) {
		
		if( ! $template_id ) {
			return false;
		}
		
		$activities = bookacti_fetch_activities();
		$template_activities = bookacti_get_activity_ids_by_template( $template_id, false );
		
		ob_start();
		foreach ( $activities as $activity ) {
			if( in_array( $activity->id, $template_activities ) ) {
				$title = apply_filters( 'bookacti_translate_text', $activity->title );
				?>
				<div class='activity-row'>
					<div class='activity-show-hide' >
						<img src='<?php echo esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/show.png" ); ?>' data-activity-id='<?php echo esc_attr( $activity->id ); ?>' data-activity-visible='1' />
					</div>
					<div class='activity-container'>
						<div
							class='fc-event ui-draggable ui-draggable-handle'
							data-event='{"title": "<?php echo esc_attr( $title ) ?>", "activity_id": "<?php echo esc_attr( $activity->id ) ?>", "color": "<?php echo esc_attr( $activity->color ) ?>"}' 
							data-activity-id='<?php echo esc_attr( $activity->id ) ?>'
							data-duration='<?php echo esc_attr( $activity->duration ) ?>'
							style='border-color:<?php echo esc_attr( $activity->color ) ?>; background-color:<?php echo esc_attr( $activity->color ) ?>'
							>
							<?php echo $title; ?>
						</div>
					</div>
				<?php
				if( current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity->id ) ) {
				?>
					<div class='activity-gear' >
						<img src='<?php echo esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/gear.png" ); ?>' data-activity-id='<?php echo esc_attr( $activity->id ); ?>' />
					</div>
				<?php
				}
				?>
				</div>
				<?php
			}
		}

		return ob_get_clean();
	}



// TEMPLATE SETTINGS

	/**
	 * Get a unique template setting made from a combination of multiple template settings
	 * 
	 * @since	1.2.2 (was bookacti_get_mixed_template_settings)
	 * @param	array|int $template_ids Array of template ids or single template id
	 * @param	boolean $past_events Whether to allow past events
	 * @return	array
	 */
	function bookacti_get_mixed_template_data( $template_ids, $past_events = false ) {
		
		$templates_data = bookacti_fetch_templates( $template_ids, true );
		
		$mixed_settings	= array();
		
		foreach( $templates_data as $template_data ){
			$settings = $template_data[ 'settings' ];
			if( isset( $settings[ 'minTime' ] ) ) {
				//Keep the lower value
				if(  ! isset( $mixed_settings[ 'minTime' ] ) 
					|| isset( $mixed_settings[ 'minTime' ] ) && strtotime( $settings[ 'minTime' ] ) < strtotime( $mixed_settings[ 'minTime' ] ) ) {

					$mixed_settings[ 'minTime' ] = $settings[ 'minTime' ];
				} 
			}
			if( isset( $settings[ 'maxTime' ] ) ) {
				//Keep the higher value
				if(  ! isset( $mixed_settings[ 'maxTime' ] ) 
					|| isset( $mixed_settings[ 'maxTime' ] ) && strtotime( $settings[ 'maxTime' ] ) > strtotime( $mixed_settings[ 'maxTime' ] ) ) {

					$mixed_settings[ 'maxTime' ] = $settings[ 'maxTime' ];
				} 
			}
			if( isset( $settings[ 'snapDuration' ] ) ) {
				//Keep the lower value
				if(  ! isset( $mixed_settings[ 'snapDuration' ] ) 
					|| isset( $mixed_settings[ 'snapDuration' ] ) && strtotime( $settings[ 'snapDuration' ] ) < strtotime( $mixed_settings[ 'snapDuration' ] ) ) {

					$mixed_settings[ 'snapDuration' ] = $settings[ 'snapDuration' ];
				} 
			}
		}
		
		$mixed_data = array();
		
		// Get mixed template range
		if( count( $templates_data ) > 1 ) {
			$mixed_data	= bookacti_get_mixed_template_range( $template_ids );
		} else {
			reset( $templates_data );
			$first_key = key( $templates_data );
			$mixed_data = array( 
				'start'	=> $templates_data[ $first_key ][ 'start' ],
				'end'	=> $templates_data[ $first_key ][ 'end' ]
			);
		}
		
		// Limit the template range to future events
		if( ! $past_events ) {
			$timezone			= new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) );
			$current_time		= new DateTime( 'now', $timezone );
			$template_start		= new DateTime( $mixed_data[ 'start' ], $timezone );
			if( $template_start < $current_time ) {
				$mixed_data[ 'start' ] = $current_time->format( 'Y-m-d' );
			}
		}
		
		// Add mixed settings
		$mixed_data[ 'settings' ] = $mixed_settings;
		
		return apply_filters( 'bookacti_mixed_template_settings', $mixed_data, $templates_data, $template_ids, $past_events );
	}



// TEMPLATES X ACTIVITIES ASSOCIATION
	// UPDATE THE LIST OF TEMPLATES ASSOCIATED TO AN ACTIVITY ID
	function bookacti_update_templates_list_by_activity_id( $new_templates, $activity_id ) {
		$old_templates = bookacti_get_templates_by_activity( $activity_id );
		
		// Unset templates already added
		foreach( $new_templates as $i => $new_template ) {
			foreach( $old_templates as $j => $old_template ) {
				if( $new_template === $old_template ) {
					unset( $new_templates[ $i ] );
					unset( $old_templates[ $j ] );
				}
			}
		}
		
		// Insert new templates
		$inserted = 0;
		if( count( $new_templates ) > 0 ) {
			$inserted = bookacti_insert_templates_x_activities( $new_templates, array( $activity_id ) );
		}
		
		// Delete old templates
		$deleted = 0;
		if( count( $old_templates ) > 0 ) {
			$deleted = bookacti_delete_templates_x_activities( $old_templates, array( $activity_id ) );
		}
		
		return $inserted + $deleted;
	}

	
	/**
	 * Update the list of activities associated to a template id
	 * 
	 * @version 1.2.2
	 * @param array $new_activities
	 * @param int $template_id
	 * @return int|false
	 */
	function bookacti_bind_activities_to_template( $new_activities, $template_id ) {
		
		if( is_numeric( $new_activities ) ) { $new_activities = array( $new_activities ); }
		
		$old_activities = bookacti_get_activity_ids_by_template( $template_id, false );
		
		// Unset templates already added
		foreach( $new_activities as $i => $new_activity ) {
			foreach( $old_activities as $j => $old_activity ) {
				if( $new_activity === $old_activity ) {
					unset( $new_activities[ $i ] );
				}
			}
		}
		
		// Insert new activity bounds
		$inserted = 0;
		if( count( $new_activities ) > 0 ) {
			$inserted = bookacti_insert_templates_x_activities( array( $template_id ), $new_activities );
		}
		
		return $inserted;
	}

	
// EVENTS
	function bookacti_promo_for_bapap_addon( $type = 'event' ) {
		
		$is_plugin_active = bookacti_is_plugin_active( 'ba-prices-and-promotions/ba-prices-and-promotions.php' );
		
		$license_status = get_option( 'bapap_license_status' );
		
		// If the plugin is activated but the license is not active yet
		if( $is_plugin_active && ( empty( $license_status ) || $license_status !== 'valid' ) ) {
			?>
			<div class='bookacti-addon-promo' >
				<p>
				<?php 
					/* translators: %s = add-on name */
					echo sprintf( __( 'Thank you for purchasing %s add-on!', BOOKACTI_PLUGIN_NAME ), 
								 '<strong>' . esc_html( __( 'Prices and Promotions', BOOKACTI_PLUGIN_NAME ) ) . '</strong>' ); 
				?>
				</p><p>
					<?php esc_html_e( "It seems you didn't activate your license yet. Please follow these instructions to activate your license:", BOOKACTI_PLUGIN_NAME ); ?>
				</p><p>
					<strong>
						<a href='https://booking-activities.fr/en/docs/user-documentation/get-started-with-prices-and-promotions-add-on/prerequisite-installation-license-activation-of-prices-and-promotions-add-on/?utm_source=plugin&utm_medium=plugin&utm_content=encart-promo-<?php echo $type; ?>' target='_blank' >
							<?php 
							/* translators: %s = add-on name */
								echo sprintf( __( 'How to activate %s license?', BOOKACTI_PLUGIN_NAME ), 
											  esc_html( __( 'Prices and Promotions', BOOKACTI_PLUGIN_NAME ) ) ); 
							?>
						</a>
					</strong>
				</p>
			</div>
			<?php
		}
		
		else if( empty( $license_status ) || $license_status !== 'valid' ) {
			?>
			<div class='bookacti-addon-promo' >
				<?php 
				$addon_link = '<a href="https://booking-activities.fr/en/downloads/prices-and-promotions/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-promotions&utm_content=encart-promo-' . $type . '" target="_blank" >';
				$addon_link .= esc_html( __( 'Prices and Promotions', BOOKACTI_PLUGIN_NAME ) );
				$addon_link .= '</a>';
				/* translators: %s is the placeholder for Price and Promotion add-on link */
				$message = '';
				$event_name = '';
				if( $type === 'group-of-events' ) {
					$message = esc_html( __( 'Set a price or a promotion on your groups of events with %s add-on !', BOOKACTI_PLUGIN_NAME ) );
					$event_name = __( 'My grouped event', BOOKACTI_PLUGIN_NAME );
				} else {
					$message = esc_html( __( 'Set a price or a promotion on your events with %s add-on !', BOOKACTI_PLUGIN_NAME ) );
					$event_name = __( 'My event', BOOKACTI_PLUGIN_NAME );
				}
				echo sprintf( $message, $addon_link ); 
				?>
				<div class='bookacti-promo-events-examples'>
					<a class="fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event" >
						<div class="fc-content">
							<div class="fc-time" data-start="7:00" data-full="7:00 AM - 8:30 AM">
								<span>7:00 - 8:30</span>
							</div>
							<div class="fc-title"><?php echo $event_name; ?></div>
						</div>
						<div class="fc-bg"></div>
						<div class="bookacti-availability-container">
							<span class="bookacti-available-places bookacti-not-booked ">
								<span class="bookacti-available-places-number">50</span>
								<span class="bookacti-available-places-unit-name"> </span>
								<span class="bookacti-available-places-avail-particle"> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', BOOKACTI_PLUGIN_NAME ); ?></span>
							</span>
						</div>
						<div class="bookacti-price-container">
							<span class="bookacti-price bookacti-promo" style="display: block; width: fit-content; white-space: nowrap; margin: 4px auto; padding: 5px; font-weight: bolder; font-size: 1.2em; border: 1px solid #fff; -webkit-border-radius: 3px;  border-radius: 3px;  background-color: rgba(0,0,0,0.3); color: #fff;">$30</span>
						</div>
					</a>
					<a class="fc-time-grid-event fc-v-event fc-event fc-start fc-end bookacti-event-has-price bookacti-narrow-event" >
						<div class="fc-content">
							<div class="fc-time" data-start="7:00" data-full="7:00 AM - 8:30 AM">
								<span>7:00 - 8:30</span>
							</div>
							<div class="fc-title"><?php echo $event_name; ?></div>
						</div>
						<div class="fc-bg"></div>
						<div class="bookacti-availability-container">
							<span class="bookacti-available-places bookacti-not-booked ">
								<span class="bookacti-available-places-number">50</span>
								<span class="bookacti-available-places-unit-name"> </span>
								<span class="bookacti-available-places-avail-particle"> <?php _ex( 'avail.', 'Short for availabilities [plural noun]', BOOKACTI_PLUGIN_NAME ); ?></span>
							</span>
						</div>
						<div class="bookacti-price-container">
							<span class="bookacti-price bookacti-promo" style="display: block; width: fit-content; white-space: nowrap; margin: 4px auto; padding: 5px; font-weight: bolder; font-size: 1.2em; border: 1px solid #fff; -webkit-border-radius: 3px;  border-radius: 3px;  background-color: rgba(0,0,0,0.3); color: #fff;">- 20%</span>
						</div>
					</a>
				</div>
				<div><a href='https://booking-activities.fr/en/downloads/prices-and-promotions/?utm_source=plugin&utm_medium=plugin&utm_medium=plugin&utm_campaign=prices-and-promotions&utm_content=encart-promo-<?php echo $type; ?>' class='button' target='_blank' ><?php esc_html_e( 'Learn more', BOOKACTI_PLUGIN_NAME ); ?></a></div>
			</div>
			<?php
		}
	}
	

// GROUP OF EVENTS
	/**
	 * Check if a group category exists
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $category_id
	 * @param int $template_id
	 * @return boolean
	 */
	function bookacti_group_category_exists( $category_id, $template_id = null ) {
		if( empty( $category_id ) || ! is_numeric( $category_id ) ) {
			return false;
		}
		
		$available_category_ids = bookacti_get_group_category_ids_by_template( $template_id );
		foreach( $available_category_ids as $available_category_id ) {
			if( intval( $category_id ) === intval( $available_category_id ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	
	/**
	 * Insert a new group of events
	 * 
	 * @since 1.1.0
	 * 
	 * @param array $events
	 * @param int $category_id
	 * @param string $group_title
	 * @param array $group_meta
	 * @return boolean|int
	 */
	function bookacti_create_group_of_events( $events, $category_id, $group_title = '', $group_meta = array() ) {
		if( ! is_array( $events ) || empty( $events ) || empty( $category_id ) ) {
			return false;
		}
		
		// First insert the group
		$group_id = bookacti_insert_group_of_events( $category_id, $group_title, $group_meta );
		
		if( empty( $group_id ) ) {
			return false;
		}
		
		// Then, insert the events in the group
		$inserted = bookacti_insert_events_into_group( $events, $group_id );
		
		if( empty( $inserted ) && $inserted !== 0 ) {
			return false;
		}
		
		return $group_id;
	}
	
	
	/**
	 * Edit a group of events
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $group_id
	 * @param int $category_id
	 * @param string $group_title
	 * @param array $events
	 * @param array $group_meta
	 * @return boolean|int
	 */
	function bookacti_edit_group_of_events( $group_id, $category_id, $group_title = '', $events = array(), $group_meta = array() ) {
		if( empty( $group_id ) || ! is_array( $events ) || empty( $events ) || empty( $category_id ) ) {
			return false;
		}
		
		$updated1 = $updated2 = $updated3 = 0;
		
		// First update the group
		$updated1 = bookacti_update_group_of_events( $group_id, $category_id, $group_title );
		
		if( $updated1 === false ) {
			return 'error_update_group_of_events_data';
		}
		
		// Then update group of events metadata
		if( ! empty( $group_meta ) ) {
			$updated2 = bookacti_update_metadata( 'group_of_events', $group_id, $group_meta );
		}
		
		if( $updated2 === false ) {
			return 'error_update_group_metadata';
		}
		
		// Fially, update events of the group
        $updated3 = bookacti_update_events_of_group( $events, $group_id );
		
		if( $updated3 === false ) {
			return 'error_update_events_of_group';
		}
		
		// Return the number of row affected
		$updated = intval( $updated1 ) + intval( $updated2 ) + intval( $updated3 );
		
		return $updated;
	}
	
	
	/**
	 * Update events of a group
	 * 
	 * @since 1.1.0
	 * 
	 * @global wpdb $wpdb
	 * @param array $new_events
	 * @param int $group_id
	 * @return int|boolean
	 */
	function bookacti_update_events_of_group( $new_events, $group_id ) {
		
		$group_id = intval( $group_id );
		if( ! is_array( $new_events ) || empty( $new_events ) || empty( $group_id ) ) {
			return false;
		}
		
		// Get events currently in the group
		$current_events = bookacti_get_group_events( $group_id );
		
		// Determine what events are to be added or removed
		$to_insert = $new_events;
		$to_delete = $current_events;
		foreach( $new_events as $i => $new_event ) {
			foreach( $current_events as $j => $current_event ) {
				$current_event = (object) $current_event;
				if( $current_event->id		== $new_event->id 
				&&  $current_event->start	== $new_event->start 
				&&  $current_event->end		== $new_event->end ) {
					// If the event already exists, remove it from both arrays
					unset( $to_insert[ $i ] );
					unset( $to_delete[ $j ] );
					break;
				}
			}
		}
		
		// Now $new_events contains only events to add
		// and $current_events contains events to remove
		$deleted = $inserted = 0;
		
		// Delete old events
		if( ! empty( $to_delete ) ) {
			$deleted = bookacti_delete_events_from_group( $to_delete, $group_id );
		}
		
		// Insert new events
		if( ! empty( $to_insert ) ) {
			$inserted = bookacti_insert_events_into_group( $to_insert, $group_id );
		}
		
		if( $deleted === false && $inserted = false ) {
			return false;
		}
		
		$updated = intval( $deleted ) + intval( $inserted );
		
		return $updated;
	}
	
	
	/**
	 * Retrieve template groups of events list
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $template_id
	 * @return string|boolean
	 */
	function bookacti_get_template_groups_of_events_list( $template_id ) {
		
		if( ! $template_id ) { return false; }
		
		$current_user_can_edit_template	= current_user_can( 'bookacti_edit_templates' );
		
		$list =	"";
		
		// Retrieve groups by categories
		$categories	= bookacti_get_group_categories_by_template( $template_id );
		$groups		= bookacti_get_groups_of_events_by_template( $template_id );
		foreach( $categories as $category ) {
			
			$category_title			= $category[ 'title' ];
			$category_short_title	= strlen( $category_title ) > 16 ? substr( $category_title, 0, 16 ) . '&#8230;' : $category_title;
			
			$list	.= "<div class='bookacti-group-category' data-group-category-id='" . $category[ 'id' ] . "' data-show-groups='0' data-visible='1' >
							<div class='bookacti-group-category-show-hide' >
								<img src='" . esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/show.png' ) . "' />
							</div>
							<?php  ?>
							<div class='bookacti-group-category-title' title='" . $category_title . "' >
								<span>
									" . $category_short_title . "
								</span>
							</div>";
			
			if( $current_user_can_edit_template ) {
				$list	.= "<div class='bookacti-update-group-category' >
								<img src='" . esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ) . "' />
							</div>";
			}
			
			$list	.= 	   "<div class='bookacti-groups-of-events-list' >";
			
			foreach( $groups as $group_id => $group ) {
				if( $group[ 'category_id' ] === $category[ 'id' ] ) {
					$group_title		= $group[ 'title' ];
					$group_short_title	= strlen( $group_title ) > 16 ? substr( $group_title, 0, 16 ) . '&#8230;' : $group_title;
					
					$list	.=	   "<div class='bookacti-group-of-events' data-group-id='" . $group_id . "' >
										<div class='bookacti-group-of-events-title' title='" . $group_title . "' >
											" . $group_short_title . " 
										</div>";
					if( $current_user_can_edit_template ) {
						$list	.=	   "<div class='bookacti-update-group-of-events' >
											<img src='" . esc_url( plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/gear.png' ) . "' />
										</div>";
					}
					$list	.=	   "</div>";
				}
			}
			
			$list	.=	   "</div>
						</div>";
		}
		
		return $list;
	}	