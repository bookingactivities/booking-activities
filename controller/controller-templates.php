<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// EVENTS
	// Fetch events in order to display them
	add_action( 'wp_ajax_bookactiFetchTemplateEvents', 'bookacti_controller_fetch_template_events' );
	function bookacti_controller_fetch_template_events() {
		
		$template_id = intval( $_POST['template_id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_fetch_template_events', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed && $template_id ) {
			
			$event_id	= intval( $_POST['event_id'] );
			$events		= bookacti_fetch_events_for_calendar_editor( $template_id, $event_id );
			wp_send_json( array( 'status' => 'success', 'events' => $events ) );
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Retrieve some event data
	add_action( 'wp_ajax_bookactiGetEventData', 'bookacti_controller_get_event_data' );
	function bookacti_controller_get_event_data() {
		
		$event_id = intval( $_POST['event_id'] );
		$template_id	= bookacti_get_event_template_id( $event_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_event_data', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {

			$data = bookacti_get_event_data( $event_id );

			if( is_array( $data ) && ! empty( $data ) ){ 
				
				// Check template permission
				if( bookacti_user_can_manage_template( $data[ 'template_id' ] ) ) {
					$data[ 'status' ] = 'success';
					wp_send_json( $data ); 
				}
				
			} else { 
				wp_send_json( array( 'status' => 'failed', 'error' => 'no_data', 'data' => $data ) ); 
			}
		}
		
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) ); 
	}
	
	
	// Add new event on calendar
	add_action( 'wp_ajax_bookactiInsertEvent', 'bookacti_controller_insert_event' );
	function bookacti_controller_insert_event() {

		$template_id = intval( $_POST['template_id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_event', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$activity_id		= intval( $_POST['activity_id'] );
			$event_title		= sanitize_text_field( stripslashes( $_POST['event_title'] ) );
			$event_start		= bookacti_sanitize_datetime( $_POST['event_start'] );
			$event_end			= bookacti_sanitize_datetime( $_POST['event_end'] );
			$event_availability	= intval( $_POST['event_availability'] );
		
			$lastid = bookacti_insert_event( $template_id, $activity_id, $event_title, $event_start, $event_end, $event_availability );

			if( $lastid ) {
				wp_send_json( array( 'status' => 'success', 'eventid' => $lastid ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_inserted' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Update start and end values of an event on calendar
	add_action( 'wp_ajax_bookactiResizeEvent', 'bookacti_controller_update_event' );
	add_action( 'wp_ajax_bookactiMoveEvent', 'bookacti_controller_update_event' );
	function bookacti_controller_update_event() {

		$event_id       = intval( $_POST['event_id'] );
		$template_id	= bookacti_get_event_template_id( $event_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_move_or_resize_event', 'nonce', false );
		$is_allowed = current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {
			
			$has_bookings = bookacti_get_number_of_bookings( $event_id );

			if( is_numeric( $has_bookings ) && $has_bookings > 0 ) {

				wp_send_json( array( 'status' => 'failed', 'error' => 'has_bookings' ) );

			} else {
				
				$event_start    = bookacti_sanitize_datetime( $_POST['event_start'] );
				$event_end      = bookacti_sanitize_datetime( $_POST['event_end'] );
				$delta_days     = intval( $_POST['delta_days'] );
				$is_duplicated  = intval( $_POST['is_duplicated'] );
				
				$updated = bookacti_update_event( $event_id, $event_start, $event_end, $delta_days, $is_duplicated );

				if( $is_duplicated ) {
					if( $updated ) { 
						wp_send_json( array( 'status' => 'success', 'event_id' => $updated ) ); 
					} else { 
						wp_send_json( array( 'status' => 'failed' ) ); 
					}
				} else {
					if( $updated ){ 
						wp_send_json( array( 'status' => 'success' ) ); 
					} else if ( $updated === 0 ) { 
						wp_send_json( array( 'status' => 'nochanges' ) ); 
					} else { 
						wp_send_json( array( 'status' => 'failed' ) ); 
					}
				}
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Update event data
	add_action( 'wp_ajax_bookactiUpdateEventData', 'bookacti_controller_update_event_data' );
	function bookacti_controller_update_event_data() {
		
		$event_id			= intval( $_POST['event-id'] );
		$template_id		= bookacti_get_event_template_id( $event_id );
		
		// Check nonce and capabilities
		$is_allowed			= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		$is_nonce_valid		= check_ajax_referer( 'bookacti_update_event_data', 'nonce_update_event_data', false );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$event_availability	= intval( $_POST['event-availability'] );
			$sanitized_freq		= sanitize_title_with_dashes( $_POST['event-repeat-freq'] );
			$event_repeat_freq	= in_array( $sanitized_freq, array( 'none', 'daily', 'weekly', 'monthly' ), true ) ? $sanitized_freq : 'none';
			$event_repeat_from	= bookacti_sanitize_date( $_POST['event-repeat-from'] );
			$event_repeat_to	= bookacti_sanitize_date( $_POST['event-repeat-to'] );
			$dates_excep_array	= bookacti_sanitize_exceptions( $_POST['event-repeat-excep'] );
			
			// Check if input data are complete and consistent 
			$event_validation	= bookacti_validate_event( $event_id, $event_availability, $event_repeat_freq, $event_repeat_from, $event_repeat_to, $dates_excep_array );

			if( $event_validation['status'] === 'valid' ) {
				
				$event_title		= sanitize_text_field( $_POST['event-title'] );
				$event_start		= bookacti_sanitize_datetime( $_POST['event-start'] );
				$event_end			= bookacti_sanitize_datetime( $_POST['event-end'] );
				$settings			= is_array( $_POST['eventOptions'] ) ? $_POST['eventOptions'] : array();
				$formatted_settings = bookacti_format_event_settings( $settings );
				
				// Update event data
				$updated = bookacti_set_event_data( $event_id, $event_title, $event_availability, $event_start, $event_end, $event_repeat_freq, $event_repeat_from, $event_repeat_to, $dates_excep_array, $formatted_settings );

				if(	  ( $updated['updated_event']		>= 0 && $updated['updated_event']		!== false && 
						$updated['updated_event_meta']  >= 0 && $updated['updated_event_meta']  !== false && 
						$updated['inserted_excep']		>= 0 && $updated['inserted_excep']		!== false && 
						$updated['deleted_excep']		>= 0 && $updated['deleted_excep']		!== false ) 
					&&
						! ( $updated['updated_event']		=== 0 && 
							( $updated['updated_event_meta']=== 0 && ! empty( $formatted_settings ) ) )
					){

					wp_send_json( array( 
						'status' => 'success', 
						'results' => array( $updated['updated_event'], 
											$updated['updated_event_meta'], 
											$updated['inserted_excep'], 
											$updated['deleted_excep'] ) 
						) ); 

				} else if( $updated['updated_event'] === 0 
						&& $updated['updated_event_meta'] === 0 
						&& $updated['inserted_excep'] === 0 
						&& $updated['deleted_excep'] === 0 ) { 

					wp_send_json( array( 'status' => 'nochanges' ) );

				} else if( $updated['updated_event'] === false 
						|| $updated['updated_event_meta'] === false 
						|| $updated['inserted_excep'] === false 
						|| $updated['deleted_excep'] === false ) { 

					wp_send_json( array( 
						'status' => 'failed', 
						'updated_event'		=> $updated['updated_event'], 
						'updated_event_meta'=> $updated['updated_event_meta'], 
						'inserted_excep'	=> $updated['inserted_excep'], 
						'deleted_excep'		=> $updated['deleted_excep'] ) ); 
				} else { 

					wp_send_json( array( 
						'status' => 'unknown_error', 
						'updated_event'		=> $updated['updated_event'], 
						'updated_event_meta'=> $updated['updated_event_meta'], 
						'inserted_excep'	=> $updated['inserted_excep'], 
						'deleted_excep'		=> $updated['deleted_excep'] ) ); 
				}
			} else {
				wp_send_json( $event_validation );
			}
			
		} else {
			wp_send_json( array( 'status' => 'not_allowed' ) );
		}
	}
	
	
	// Get all exceptions for a given template and / or event
	add_action( 'wp_ajax_bookactiGetExceptions', 'bookacti_controller_get_exceptions' );
	function bookacti_controller_get_exceptions() {
		
		$template_id	= intval( $_POST['template_id'] );
		$event_id		= intval( $_POST['event_id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_exceptions', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$exceptions = bookacti_get_exceptions( $template_id, $event_id );

			if( count( $exceptions ) > 0 ) {
				wp_send_json( array( 'status' => 'success', 'exceptions' => $exceptions ) );
			} else if( count( $exceptions ) === 0 ) {
				wp_send_json( array( 'status' => 'no_exception' ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Delete an event
	add_action( 'wp_ajax_bookactiDeleteEvent', 'bookacti_controller_delete_event' );
	function bookacti_controller_delete_event() {

		$event_id		= intval( $_POST['event_id'] );
		$template_id	= bookacti_get_event_template_id( $event_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_delete_event', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {

			$has_bookings = bookacti_get_number_of_bookings( $event_id );

			if( is_numeric( $has_bookings ) && $has_bookings > 0 ) {

				wp_send_json( array( 'status' => 'failed', 'error' => 'has_bookings' ) );

			} else {

				$deleted = bookacti_delete_event( $event_id );

				if( $deleted ) {
					wp_send_json( array( 'status' => 'success' ) );
				} else {
					wp_send_json( array( 'status' => 'failed', 'error' => 'not_deleted' ) );
				}
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Unbind occurences of an event
	add_action( 'wp_ajax_bookactiUnbindOccurences', 'bookacti_controller_unbind_occurrences' );
	function bookacti_controller_unbind_occurrences() {

		$event_id		= intval( $_POST['event_id'] );
		$template_id	= bookacti_get_event_template_id( $event_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_unbind_occurences', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {
			
			$sanitized_unbind	= sanitize_title_with_dashes( $_POST['unbind'] );
			$unbind				= in_array( $sanitized_unbind, array( 'selected', 'booked' ), true ) ? $sanitized_unbind : 'selected';
			
			if( $unbind === 'selected' ) {
				$event_start	= bookacti_sanitize_datetime( $_POST['event_start'] );
				$event_end		= bookacti_sanitize_datetime( $_POST['event_end'] );
				$events			= bookacti_unbind_selected_occurrence( $event_id, $event_start, $event_end );
			} else if( $unbind === 'booked' ) {
				$events = bookacti_unbind_booked_occurrences( $event_id );
			}

			wp_send_json( array( 'status' => 'success', 'events' => $events ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	
// GROUPS OF EVENTS
	
	/**
	 * Create a group of events with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_insert_group_of_events() {
		
		$template_id	= intval( $_POST[ 'template_id' ] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_group_of_events', 'nonce_insert_or_update_group_of_events', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( ! $is_nonce_valid || ! $is_allowed ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
		
		$category_id	= intval( $_POST[ 'group-of-events-category' ] );
		$category_title	= sanitize_text_field( stripslashes( $_POST[ 'group-of-events-category-title' ] ) );
		$group_title	= sanitize_text_field( stripslashes( $_POST[ 'group-of-events-title' ] ) );
		$events			= json_decode( stripslashes( $_POST['events'] ) );
		
		// Validate input data
		$is_group_of_events_valid = bookacti_validate_group_of_events_data( $group_title, $category_id, $category_title, $events );
		if( $is_group_of_events_valid[ 'status' ] !== 'valid' ) {
			wp_send_json( array( 'status' => 'not_valid', 'errors' => $is_group_of_events_valid[ 'errors' ] ) );
		}
		
		// Create category if it doesn't exists
		$is_category = bookacti_group_category_exists( $category_id, $template_id );
		
		if( ! $is_category ) {
			$category_settings	= bookacti_format_group_category_settings( $_POST['groupCategoryOptions'] );
			$category_id		= bookacti_insert_group_category( $category_title, $template_id, $category_settings );
		}
		
		if( empty( $category_id ) ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'invalid_category', 'category_id' => $category_id ) );
		}
		
		// Insert the new group of event
		$group_settings	= bookacti_format_group_of_events_settings( $_POST['groupOfEventsOptions'] );
		$group_id		= bookacti_create_group_of_events( $events, $category_id, $group_title, $group_settings );
		
		if( empty( $group_id ) ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'invalid_events', 'events' => $events, 'group_id' => $group_id ) );
		}
		
		wp_send_json( array('status' => 'success', 
							'group_id' => $group_id, 
							'group_title' => apply_filters( 'bookacti_translate_text', $group_title ), 
							'category_id' => $category_id, 
							'category_title' => apply_filters( 'bookacti_translate_text', $category_title ) ) );
	}
	add_action( 'wp_ajax_bookactiInsertGroupOfEvents', 'bookacti_controller_insert_group_of_events' );
	
	
		
	/**
	 * Get group of events data with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_get_group_of_events_data() {

		$group_id = intval( $_POST[ 'group_id' ] );
		$template_id = bookacti_get_group_of_events_template_id( $group_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_group_of_events_data', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {

			$group = bookacti_get_group_of_events( $group_id, ARRAY_A );
			
			if( is_array( $group ) && ! empty( $group ) ){
				$group[ 'status' ] = 'success';
				wp_send_json( $group );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiGetGroupOfEventsData', 'bookacti_controller_get_group_of_events_data' );
	
	
	/**
	 * Update group of events data with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_update_group_of_events() {
		
		$group_id		= intval( $_POST[ 'group_id' ] );
		$template_id	= bookacti_get_group_of_events_template_id( $group_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_group_of_events', 'nonce_insert_or_update_group_of_events', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( ! $is_nonce_valid || ! $is_allowed ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
		
		$category_id	= intval( $_POST[ 'group-of-events-category' ] );
		$category_title	= sanitize_text_field( stripslashes( $_POST[ 'group-of-events-category-title' ] ) );
		$group_title	= sanitize_text_field( stripslashes( $_POST[ 'group-of-events-title' ] ) );
		$events			= json_decode( stripslashes( $_POST['events'] ) );
		
		// Validate input data
		$is_group_of_events_valid = bookacti_validate_group_of_events_data( $group_title, $category_id, $category_title, $events );
		if( $is_group_of_events_valid[ 'status' ] !== 'valid' ) {
			wp_send_json( array( 'status' => 'not_valid', 'errors' => $is_group_of_events_valid[ 'errors' ] ) );
		}
		
		// Create category if it doesn't exists
		$is_category = bookacti_group_category_exists( $category_id, $template_id );
		
		if( ! $is_category ) {
			$category_settings	= bookacti_format_group_category_settings( $_POST['groupCategoryOptions'] );
			$category_id		= bookacti_insert_group_category( $category_title, $template_id, $category_settings );
		}
		
		if( empty( $category_id ) ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'invalid_category', 'category_id' => $category_id ) );
		}
		
		// Insert the new group of event
		$group_settings	= bookacti_format_group_of_events_settings( $_POST['groupOfEventsOptions'] );
		$updated		= bookacti_edit_group_of_events( $group_id, $category_id, $group_title, $events, $group_settings );
		
		if( $updated === false ) {
			wp_send_json( array( 'status' => 'failed', 'error' => 'invalid_events', 'events' => $events, 'group_id' => $group_id ) );
		} else if( $updated === 0 ) {
			wp_send_json( array( 'status' => 'nochanges' ) );
		} else if( is_string( $updated ) ) {
			wp_send_json( array( 'status' => 'failed', error => $updated ) );
		}
		
		wp_send_json( array('status' => 'success', 
							'group_title' => apply_filters( 'bookacti_translate_text', $group_title ), 
							'category_id' => $category_id, 
							'category_title' => apply_filters( 'bookacti_translate_text', $category_title ) ) );
	}
	add_action( 'wp_ajax_bookactiUpdateGroupOfEvents', 'bookacti_controller_update_group_of_events' );
	
	
	/**
	 * Delete a group of events with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_delete_group_of_events() {
		
		$group_id		= intval( $_POST['group_id'] );
		$template_id	= bookacti_get_group_of_events_template_id( $group_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_delete_group_of_events', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$deleted = bookacti_delete_group_of_events( $group_id );
			
			if( $deleted ) {
				wp_send_json( array( 'status' => 'success' ) );
			} else {
				wp_send_json( array( 'status' => 'failed' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiDeleteGroupOfEvents', 'bookacti_controller_delete_group_of_events' );
	

	
// GROUP CATEGORIES
	
	/**
	 * Get group category data with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_get_group_category_data() {

		$category_id = intval( $_POST[ 'category_id' ] );
		$template_id = bookacti_get_group_category_template_id( $category_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_group_category_data', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {

			$category	= bookacti_get_group_category( $category_id, ARRAY_A );
			
			if( is_array( $category ) && ! empty( $category ) ){
				$category[ 'status' ] = 'success';
				wp_send_json( $category );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiGetGroupCategoryData', 'bookacti_controller_get_group_category_data' );

	
	/**
	 * Update group category data with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_update_group_category() {

		$category_id = intval( $_POST[ 'category_id' ] );
		$template_id = bookacti_get_group_category_template_id( $category_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_group_category', 'nonce_insert_or_update_group_category', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {
			
			$category_title = sanitize_text_field( stripslashes( $_POST[ 'group-category-title' ] ) );
			$is_group_category_valid = bookacti_validate_group_category_data( $category_title );
			
			// Update template only if its data are consistent
			if( $is_group_category_valid[ 'status' ] === 'valid' ) {
				
				$category_settings = bookacti_format_group_category_settings( $_POST['groupCategoryOptions'] );
				
				$updated = bookacti_update_group_category( $category_id, $category_title, $category_settings );
				
				if( $updated ) {
					wp_send_json( array( 'status' => 'success', 'title' => apply_filters( 'bookacti_translate_text', $category_title ) ) );
				} else if ( $updated === 0 ) { 
					wp_send_json( array( 'status' => 'nochanges', 'title' => apply_filters( 'bookacti_translate_text', $category_title ) ) );
				} else if ( $updated === false ) { 
					wp_send_json( array( 'status' => 'failed' ) );
				}
			} else {
				wp_send_json( array( 'status' => 'not_valid', 'errors' => $is_group_category_valid[ 'errors' ] ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiUpdateGroupCategory', 'bookacti_controller_update_group_category' );
	
	
	/**
	 * Delete a group category with AJAX
	 * 
	 * @since 1.1.0
	 */
	function bookacti_controller_delete_group_category() {
		
		$category_id	= intval( $_POST['category_id'] );
		$template_id	= bookacti_get_group_category_template_id( $category_id );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_delete_group_category', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$deleted = bookacti_delete_group_category( $category_id );
			
			if( $deleted ) {
				wp_send_json( array( 'status' => 'success' ) );
			} else {
				wp_send_json( array( 'status' => 'failed' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiDeleteGroupCategory', 'bookacti_controller_delete_group_category' );
	
	
	
// TEMPLATES
	// Get template data
	add_action( 'wp_ajax_bookactiGetTemplateData', 'bookacti_controller_get_template_data' );
	function bookacti_controller_get_template_data() {

		$template_id = intval( $_POST[ 'template_id' ] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_template_data', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {

			$template	= bookacti_get_template( $template_id, ARRAY_A );

			if( is_array( $template ) && ! empty( $template ) ){
				$template[ 'status' ] = 'success';
				wp_send_json( $template );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'unknown' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	/**
	 * AJAX Controller - Create a new template
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 */
	function bookacti_controller_insert_template() {
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_template', 'nonce_insert_or_update_template', false );
		$is_allowed		= current_user_can( 'bookacti_create_templates' );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$template_title	= sanitize_text_field( stripslashes( $_POST['template-title'] ) );
			$template_start	= bookacti_sanitize_date( $_POST['template-opening'] );
			$template_end	= bookacti_sanitize_date( $_POST['template-closing'] );
			
			$is_template_valid = bookacti_validate_template_data( $template_title, $template_start, $template_end );

			// Create template only if its data are consistent
			if( $is_template_valid['status'] === 'valid' ) {
			
				$duplicated_template_id	= intval( $_POST['duplicated-template-id'] );
				$template_managers		= bookacti_format_template_managers( $_POST['template-managers'] );
				$template_settings		= bookacti_format_template_settings( $_POST['templateOptions'] );

				$lastid = bookacti_insert_template( $template_title, $template_start, $template_end, $template_managers, $template_settings, $duplicated_template_id );

				if( $lastid ) {
					wp_send_json( array( 'status' => 'success', 'template_id' => $lastid ) );
				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	add_action( 'wp_ajax_bookactiInsertTemplate', 'bookacti_controller_insert_template' );
	
	
	/**
	 * AJAX Controller - Update template
	 *
	 * @since	1.0.0
	 * @version	1.0.6
	 */
	function bookacti_controller_update_template() {
		
		$template_id	= intval( $_POST['template-id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_template', 'nonce_insert_or_update_template', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$template_title	= sanitize_text_field( stripslashes( $_POST['template-title'] ) );
			$template_start	= bookacti_sanitize_date( $_POST['template-opening'] );
			$template_end	= bookacti_sanitize_date( $_POST['template-closing'] );

			$is_template_valid = bookacti_validate_template_data( $template_title, $template_start, $template_end );

			// Update template only if its data are consistent
			if( $is_template_valid['status'] === 'valid' ) {

				$template_settings	= bookacti_format_template_settings( $_POST['templateOptions'] );
				$template_managers	= bookacti_format_template_managers( $_POST['template-managers'] );
				
				$updated = bookacti_update_template( $template_id, $template_title, $template_start, $template_end, $template_managers, $template_settings );

				if( $updated ) {
					wp_send_json( array( 'status' => 'success' ) );
				} else if ( $updated === 0 ) { 
					wp_send_json( array( 'status' => 'nochanges' ) );
				} else if ( $updated === false ) { 
					wp_send_json( array( 'status' => 'failed' ) );
				}
			} else {
				wp_send_json( array( 'status' => 'not_valid', 'errors' => $is_template_valid[ 'errors' ] ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'errors' => array( 'not_allowed' ) ) );
		}
	}
	add_action( 'wp_ajax_bookactiUpdateTemplate', 'bookacti_controller_update_template' );
	
	
	// Deactivate a template
	add_action( 'wp_ajax_bookactiDeactivateTemplate', 'bookacti_controller_deactivate_template' );
	function bookacti_controller_deactivate_template() {

		$template_id = intval( $_POST['template_id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_deactivate_template', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_delete_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {

			$deactivated = bookacti_deactivate_template( $template_id );

			if( $deactivated ) {
				wp_send_json( array( 'status' => 'success' ) );
			} else {
				wp_send_json( array( 'status' => 'failed' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}
	
	
	// Change default template
	add_action( 'wp_ajax_bookactiSwitchTemplate', 'bookacti_controller_switch_template' );
	function bookacti_controller_switch_template() {

		$template_id = intval( $_POST['template_id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_switch_template', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );

		if( $is_nonce_valid && $is_allowed ) {

			$updated		= bookacti_update_user_default_template( $template_id );
			$activities_list= bookacti_get_template_activities_list( $template_id );
			$groups_list	= bookacti_get_template_groups_of_events_list( $template_id );
			$groups_events	= bookacti_get_groups_events( $template_id );
			$settings		= bookacti_get_templates_settings( $template_id );
			$exceptions		= bookacti_get_exceptions( $template_id );
			$events			= bookacti_fetch_events_for_calendar_editor( $template_id );
			
			wp_send_json( array(
					'status'			=> 'success', 
					'activities_list'	=> $activities_list, 
					'groups_list'		=> $groups_list, 
					'groups_events'		=> $groups_events, 
					'settings'			=> $settings, 
					'exceptions'		=> $exceptions, 
					'events'			=> $events,
					'user_default_template_updated'	=> $updated 
				) 
			);

		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
	}


	
// ACTIVITIES
    // Create new activity
    add_action( 'wp_ajax_bookactiInsertActivity', 'bookacti_controller_insert_activity' );
    function bookacti_controller_insert_activity() {
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_activity', 'nonce_insert_or_update_activity', false );
		$is_allowed		= current_user_can( 'bookacti_create_activities' );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$template_id			= intval( $_POST['template-id'] );
			$activity_title			= sanitize_text_field( stripslashes( $_POST['activity-title'] ) );
			$activity_color			= sanitize_hex_color( $_POST['activity-color'] );
			$activity_availability	= intval( $_POST['activity-availability'] );
			$activity_duration		= bookacti_sanitize_duration( $_POST['activity-duration'] );
			$activity_resizable		= intval( $_POST['activity-resizable'] );
			
			// Format arrays and check templates permissions
			$activity_managers		= bookacti_format_activity_managers( $_POST['activity-managers'] );
			$activity_templates		= bookacti_format_activity_templates( $_POST['activity-templates'], array( $template_id ) );
			$activity_settings		= bookacti_format_activity_settings( $_POST['activityOptions'] );
		
			if( ! empty( $activity_templates ) && $template_id ) {
				$activity_id = bookacti_insert_activity( $activity_title, $activity_color, $activity_availability, $activity_duration, $activity_resizable, $activity_managers, $activity_templates, $activity_settings );
				
				if( $activity_id ) {
					$title = apply_filters( 'bookacti_translate_text', esc_html( stripslashes( $activity_title ) ) );
					wp_send_json( array( 'status' => 'success', 'title' => $title, 'multilingual_title' => $activity_title, 'activity_id' => $activity_id, 'templates' => $activity_templates ) );
				} else {
					wp_send_json( array( 'status' => 'failed' ) );
				}
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'no_templates' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
    
	
    // Retrieve activity data
    add_action( 'wp_ajax_bookactiGetActivityData', 'bookacti_controller_get_activity_data' );
    function bookacti_controller_get_activity_data() {
        
        $activity_id = intval( $_POST['activity_id'] );
        
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_activity_data', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity_id );
		
		if( $is_nonce_valid && $is_allowed ) {
		
			$activity = bookacti_get_activity( $activity_id );

			if( $activity ){ 
				wp_send_json( array( 'status' => 'success', 'activity' => $activity ) ); 
			} else { 
				wp_send_json( array( 'status' => 'failed' ) ); 
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
    
	
    // Update an activity
    add_action( 'wp_ajax_bookactiUpdateActivity', 'bookacti_controller_update_activity' );
    function bookacti_controller_update_activity() {
		
		$activity_id = intval( $_POST['activity-id'] );
		
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_insert_or_update_activity', 'nonce_insert_or_update_activity', false );
		$is_allowed		= current_user_can( 'bookacti_edit_activities' ) && bookacti_user_can_manage_activity( $activity_id );
		
		if( $is_nonce_valid && $is_allowed ) {
		
			$template_id			= intval( $_POST['template-id'] );
			$activity_title			= sanitize_text_field( stripslashes( $_POST['activity-title'] ) );
			$activity_old_title		= sanitize_text_field( stripslashes( $_POST['activity-old-title'] ) );
			$activity_color			= sanitize_hex_color( $_POST['activity-color'] );
			$activity_availability	= intval( $_POST['activity-availability'] );
			$activity_duration		= bookacti_sanitize_duration( $_POST['activity-duration'] );
			$activity_resizable		= intval( $_POST['activity-resizable'] );
			
			// Format arrays and check templates permissions
			$activity_managers	= bookacti_format_activity_managers( $_POST['activity-managers'] );
			$activity_templates	= bookacti_format_activity_templates( $_POST['activity-templates'], array( $template_id ) );
			$activity_settings	= bookacti_format_activity_settings( $_POST['activityOptions'] );
		
			$updated_activity	= bookacti_update_activity( $activity_id, $activity_title, $activity_color, $activity_availability, $activity_duration, $activity_resizable, $activity_managers, $activity_templates, $activity_settings );
			$updated_events		= bookacti_update_events_title( $activity_id, $activity_old_title, $activity_title );
			
			if( ! empty( $activity_templates ) && $updated_activity > 0 && $updated_events >= 0 ){
				$title = apply_filters( 'bookacti_translate_text', stripslashes( $activity_title ) );
				wp_send_json( array( 'status' => 'success', 'title' => $title, 'multilingual_title' => $activity_title ) );
			} else if ( empty( $activity_templates ) ) { 
				wp_send_json( array( 'status' => 'no_templates' ) ); 
			} else if ( $updated_activity === false && $updated_events >= 0 ){ 
				wp_send_json( array( 'status' => 'failed_update_activity' ) ); 
			} else if ( $updated_events === false && $updated_activity >= 0 ){ 
				wp_send_json( array( 'status' => 'failed_update_bound_events' ) );
			} else if ( $updated_activity === 0 && $updated_events === 0 ){ 
				wp_send_json( array( 'status' => 'no_changes' ) );
			}
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
    
	
	// Create an association between existing activities (on various templates) and current template
    add_action( 'wp_ajax_bookactiImportActivities', 'bookacti_controller_import_activities' );
    function bookacti_controller_import_activities() {
        
		$template_id = intval( $_POST[ 'template_id' ] );
        
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_import_activity', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_activities' ) && current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$activity_ids	= bookacti_ids_to_array( $_POST[ 'activity_ids' ] );
			$was_empty		= empty( $activity_ids );
			$inserted		= false;

			// Check activity permissions, and remove not allowed activity ids
			if( ! $was_empty ) {
				foreach( $activity_ids as $i => $activity_id ) {
					$can_manage_activity = bookacti_user_can_manage_activity( $activity_id );
					if( ! $can_manage_activity ) {
						unset( $activity_ids[ $i ] );
					}
				}
				if( ! empty( $activity_ids ) ) {
					$inserted = bookacti_bind_activities_to_template( $activity_ids, $template_id );
				}
			}
			
			if( $inserted ) {
				wp_send_json( array( 'status' => 'success', 'activity_ids' => $activity_ids ) );
			} else if( $was_empty ) {
				wp_send_json( array( 'status' => 'no_activity' ) );
			} else if( ! $was_empty && empty( $activity_ids ) ) {
				wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'unknown', 'activity_ids' => $activity_ids, 'template_id' => $template_id, 'inserted' => $inserted ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
	
	
    // Deactivate an activity
    add_action( 'wp_ajax_bookactiDeactivateActivity', 'bookacti_controller_deactivate_activity' );
    function bookacti_controller_deactivate_activity() {
        
        $activity_id = intval( $_POST['activity_id'] );
        $template_id = intval( $_POST['template_id'] );
        
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_deactivate_activity', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_delete_activities' ) && bookacti_user_can_manage_activity( $activity_id ) && current_user_can( 'bookacti_edit_templates' ) && bookacti_user_can_manage_template( $template_id );
		
		if( $is_nonce_valid && $is_allowed ) {
			
			$deleted	= bookacti_delete_templates_x_activities( array( $template_id ), array( $activity_id ) );
			$templates	= bookacti_get_templates_by_activity_ids( $activity_id );
			
			if( empty( $templates ) ) {
				$deactivated = bookacti_deactivate_activity( $activity_id );
			}
			
			if( $deleted ) {
				wp_send_json( array( 'status' => 'success' ) );
			} else {
				wp_send_json( array( 'status' => 'failed' ) );
			}
        } else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
	
	
	// Get activities by template
    add_action( 'wp_ajax_bookactiGetActivitiesByTemplate', 'bookacti_controller_get_activities_by_template' );
    function bookacti_controller_get_activities_by_template() {
        
        $selected_template_id	= intval( $_POST[ 'selected_template_id' ] );
        $current_template_id	= intval( $_POST[ 'current_template_id' ] );
        
		// Check nonce and capabilities
		$is_nonce_valid	= check_ajax_referer( 'bookacti_get_activities_by_template', 'nonce', false );
		$is_allowed		= current_user_can( 'bookacti_edit_activities' ) && current_user_can( 'bookacti_read_templates' ) && bookacti_user_can_manage_template( $selected_template_id );
		
		if( $is_nonce_valid && $is_allowed ) {

			if( $selected_template_id !== $current_template_id ) {

				$new_activities		= bookacti_get_activities_by_template_ids( $selected_template_id );
				$current_activities	= bookacti_get_activity_ids_by_template_ids( $current_template_id );
				
				// Check activity permissions, and remove not allowed activity ids
				foreach( $new_activities as $new_activity_id => $new_activity ) {
					if( ! in_array( $new_activity_id, $current_activities ) ) {
						$is_allowed = bookacti_user_can_manage_activity( $new_activity_id );
						if( ! $is_allowed || ! $new_activity->active ) {
							unset( $new_activities[ $new_activity_id ] );
						}
					} else {
						unset( $new_activities[ $new_activity_id ] );
					}
				}

				if( is_array( $new_activities ) && ! empty( $new_activities ) ) {
					wp_send_json( array( 'status' => 'success', 'activities' => $new_activities ) );
				} else if( is_array( $new_activities ) && empty( $new_activities )  ) {
					wp_send_json( array( 'status' => 'no_activity' ) );
				} else {
					wp_send_json( array( 'status' => 'failed', 'activities' => $new_activities ) );
				}
			} else {
				wp_send_json( array( 'status' => 'failed', 'error' => 'no_change' ) );
			}
			
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
		}
    }
