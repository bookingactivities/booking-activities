<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// PERMISSIONS
	// CHECK IF USER IS ALLOWED TO MANAGE TEMPLATE
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
	 * @since 1.0.0
	 * @version 1.1.0
	 * 
	 * @param int $template_id
	 * @return boolean|string 
	 */
	function bookacti_get_template_activities_list( $template_id ) {
		
		if( empty( $template_id ) ) {
			return false;
		}
		
		$list = '';
		$activities = bookacti_fetch_activities();
		$template_activities = bookacti_get_activity_ids_by_template( $template_id, false );

		foreach ( $activities as $activity ) {
			if( in_array( $activity->id, $template_activities ) ) {
				$title = apply_filters( 'bookacti_translate_text', $activity->title );

				$list	.=	"<div class='activity-row'>"
						.       "<div class='activity-show-hide' >"
						.           "<img src='" . esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/show.png" ) . "' data-activity-id='" . esc_attr( $activity->id ) . "' data-activity-visible='1' />"
						.       "</div>"
						.       "<div class='activity-container'>"
						.           "<div "
						.				"class='fc-event ui-draggable ui-draggable-handle' "
						.				"data-title='"			. esc_attr( $activity->title ) . "' "
						.				"data-activity-id='"	. esc_attr( $activity->id )				. "' "
						.				"data-color='"			. esc_attr( $activity->color )			. "' "
						.				"data-availability='"	. esc_attr( $activity->availability	)	. "' "
						.				"data-start='12:00' "
						.				"data-duration='"		. esc_attr( $activity->duration )		. "' "
						.				"data-resizable='"		. esc_attr( $activity->is_resizable	)	. "' "
						.           ">"
						.               $title
						.           "</div>"
						.       "</div>";
				if( current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity->id ) ) {	
					$list .=	"<div class='activity-gear' >"
						.           "<img src='" . esc_url( plugins_url() . "/" . BOOKACTI_PLUGIN_NAME . "/img/gear.png" ) . "' data-activity-id='" . esc_attr( $activity->id ) . "' />"
						.       "</div>";
				}
				$list	.=	"</div>";
			}
		}

		return $list;
	}



// TEMPLATE SETTINGS
	/**
	 * Get templates settings
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 * @param	array|int $template_ids Array of template ids or single template id
	 * @return	array
	 */
	function bookacti_get_templates_settings( $template_ids ) {
		
		if( is_numeric( $template_ids ) ) {
			$template_ids = array( $template_ids );
		}
				
		$is_empty = empty( $template_ids );
				
		// Get templates range (start and end dates)
		$templates_range = array();
		$templates = bookacti_fetch_templates( true );
		foreach( $templates as $template ) {
			// If empty, take them all
			if( $is_empty ) {
				$template_ids[] = $template->id;
			}
			$templates_range[ $template->id ] = array( 'start' => $template->start_date, 'end' => $template->end_date );
		}
		
		
		$return_array = array();
		
		if( is_numeric( $template_ids ) || ( is_array( $template_ids ) && count( $template_ids ) === 1 ) ) {
			$template_id	= is_numeric( $template_ids ) ? $template_ids : $template_ids[0];
			$template_meta	= bookacti_get_metadata( 'template', $template_id );
			$return_array	= array_merge( $template_meta, $templates_range[ $template_id ] );
		} else {
			foreach( $template_ids as $template_id ) {
				$template_meta					= bookacti_get_metadata( 'template', $template_id );
				$return_array[ $template_id ]	= array_merge( $template_meta, $templates_range[ $template_id ] );
			}
		}

		return $return_array;
	}


	/**
	 * Get a unique template setting made from a combination of multiple template settings
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 * @param	array|int $template_ids Array of template ids or single template id
	 * @return	array
	 */
	function bookacti_get_mixed_template_settings( $template_ids ) {
		
		$templates_settings = bookacti_get_templates_settings( $template_ids );
		if( is_numeric( $template_ids ) || ( is_array( $template_ids ) && count( $template_ids ) === 1 ) ) {
			$template_id = is_numeric( $template_ids ) ? $template_ids : reset( $template_ids );
			$templates_settings = array( $template_id => $templates_settings );
		}
		$mixed_settings	= array();
		
		if( count( $templates_settings ) > 1 ) {
			foreach( $templates_settings as $settings ){
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
			}
			
			$templates_mixed_range	= bookacti_get_mixed_template_range( $template_ids );
			$mixed_settings			= array_merge( $mixed_settings, $templates_mixed_range );
		} else {
			$mixed_settings = $templates_settings[ $template_id ];
		}
		
		return apply_filters( 'bookacti_mixed_template_settings', $mixed_settings, $templates_settings, $template_ids );
	}



// USER DEFAULT TEMPLATE
	// GET USER DEFAULT TEMPLATE
	function bookacti_get_user_default_template( $user_id = false ) {

		$user_id = $user_id ? $user_id : get_current_user_id();

		$template_settings = get_option( 'bookacti_template_settings' );

		if( empty( $template_settings ) ){
			$template_settings = array();
		}
		if( empty( $template_settings['default_template_per_user'] ) ) {
			$template_settings['default_template_per_user'] = array();
		} 
		if( empty( $template_settings['default_template_per_user'][ $user_id ] ) ) {
			$template_settings['default_template_per_user'][ $user_id ] = 0;
		} 

		return intval( $template_settings['default_template_per_user'][ $user_id ] );
	}


	// UPDATE USER DEFAULT TEMPLATE
	function bookacti_update_user_default_template( $template_id, $user_id = false ) {

		$user_id = $user_id ? $user_id : get_current_user_id();

		$template_settings = get_option( 'bookacti_template_settings' );
		if( ! is_array( $template_settings['default_template_per_user'] ) ) {
			$template_settings['default_template_per_user'] = array();
		}

		// If no change, return 0
		if( isset( $template_settings['default_template_per_user'][ $user_id ] )
		&&  $template_settings['default_template_per_user'][ $user_id ] === $template_id ) {
			return 0;
		}

		$template_settings['default_template_per_user'][ $user_id ] = $template_id;

		$is_updated = update_option( 'bookacti_template_settings', $template_settings );

		return $is_updated;
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

	
	// UPDATE THE LIST OF ACTIVITIES ASSOCIATED TO A TEMPLATE ID
	function bookacti_bind_activities_to_template( $new_activities, $template_id ) {
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
					<?php esc_html_e( 'It seems you didn\'t activate your license yet. Please follow these instructions to activate your license:', BOOKACTI_PLUGIN_NAME ); ?>
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
				/* transmators: %s is the placeholder for Price and Promotion add-on link */
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
		
		if( empty( $template_id ) ) {
			return false;
		}
		
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