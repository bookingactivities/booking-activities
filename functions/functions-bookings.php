<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// BOOKINGS

	/**
	 * Check if a booking is whithin the athorized delay as of now
	 * 
	 * @since 1.1.0
	 * @version 1.4.0
	 * 
	 * @param object|int $booking
	 * @return boolean
	 */
	function bookacti_is_booking_in_delay( $booking ) {

		if( is_numeric( $booking ) ) {
			$booking = bookacti_get_booking_by_id( $booking );
		}

		if( ! is_object( $booking ) ) { return false; }

		$is_in_delay	= false;
		$delay_global	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'cancellation_min_delay_before_event' );
		$timezone		= bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' );
		
		// Get the more specific per activity / group category delay
		$delay_specific = false;
		if( $booking->group_id ) {
			$booking_group	= bookacti_get_booking_group_by_id( $booking->group_id );
			$event_group	= bookacti_get_group_of_events( $booking_group->event_group_id );
			$category_data	= bookacti_get_metadata( 'group_category', $event_group->category_id );
			$delay_specific	= isset( $category_data[ 'booking_changes_deadline' ] ) ? intval( $category_data[ 'booking_changes_deadline' ] ) : false;
		} else {
			$event			= bookacti_get_event_by_id( $booking->event_id );
			$activity_data	= bookacti_get_metadata( 'activity', $event->activity_id );
			$delay_specific	= isset( $activity_data[ 'booking_changes_deadline' ] ) ? intval( $activity_data[ 'booking_changes_deadline' ] ) : false;
		}
		
		// Sanitize
		if( ! is_numeric( $delay_specific ) || $delay_specific < 0 ){ $delay_specific = false; } 
		if( ! is_numeric( $delay_global ) || $delay_global < 0 )	{ $delay_global = 0; } 
		
		// Choose the most specific defined value
		$delay = $delay_specific !== false ? $delay_specific : $delay_global;
		
		$event_datetime		= DateTime::createFromFormat( 'Y-m-d H:i:s', $booking->event_start );
		$delay_datetime		= $event_datetime->sub( new DateInterval( 'P' . $delay . 'D' ) );
		$current_datetime	= new DateTime( 'now', new DateTimeZone( $timezone ) );

		if( $current_datetime < $delay_datetime ) { $is_in_delay = true; }

		return apply_filters( 'bookacti_is_booking_in_delay', $is_in_delay, $booking );
	}
	
	
	/**
	 * Compute the booking group state based on its bookings state
	 * 
	 * @since 1.1.0
	 * @version 1.2.0
	 * 
	 * @param int $booking_group_id
	 * @param boolean $update
	 * @return string|false
	 */
	function bookacti_compute_booking_group_state( $booking_group_id ) {
		
		$bookings = bookacti_get_bookings_by_booking_group_id( $booking_group_id );
		
		if( empty( $bookings ) ) {
			return false;
		}
		
		// Gether all booking states
		$states = array();
		foreach( $bookings as $booking ) {
			$states[] = $booking->state;
		}
		
		// Default to cancelled
		$new_state = 'cancelled';
		
		// Set the same state as its booking by priority
		if( in_array( 'in_cart', $states, true ) ) {
			$new_state = 'in_cart';
		} else if( in_array( 'pending', $states, true ) ) {
			$new_state = 'pending';
		} else if( in_array( 'booked', $states, true ) ) {
			$new_state = 'booked';
		}  else if( in_array( 'refund_requested', $states, true ) ) {
			$new_state = 'refund_requested';
		} else if( in_array( 'refunded', $states, true ) ) {
			$new_state = 'refunded';
		}
		
		return apply_filters( 'bookacti_compute_booking_group_state', $new_state, $booking_group_id );
	}



// BOOKINGS PAGE

	/**
	 * Return the HTML code to display activities by templates in the bookings page
	 * 
	 * @param array $template_ids
	 * @param array $activity_ids
	 * @return string
	 */
	function bookacti_get_activities_html_for_booking_page( $template_ids, $activity_ids = array() ) {

		$activities = bookacti_get_activities_by_template( $template_ids, false );
		$j = 0;
		$html = '';
		foreach ( $activities as $activity ) {	
			if( ( empty( $activity_ids )  && $j === 0 ) || in_array( $activity->id, $activity_ids ) ) { $selected = 'selected'; } else { $selected = ''; }

			// Retrieve activity title
			$title = apply_filters( 'bookacti_translate_text', $activity->title );

			// Display activity
			$html.=	"<div class='bookacti-bookings-filter-activity bookacti-bookings-filter' "
				.		"data-activity-id='" . esc_attr( $activity->id ) . "' "
				.		"style='background-color: " . esc_attr( $activity->color ) . "; border-color: " . esc_attr( $activity->color ) . "' " 
				.		esc_attr( $selected )
				.	" >"
				.		"<div class='bookacti-bookings-filter-content' >"
				.			"<div class='bookacti-bookings-filter-activity-title' >"
				.				"<strong>" . esc_html( $title ). "</strong>"
				.			"</div>"
				.		"</div>"
				.		"<div class='bookacti-bookings-filter-bg' ></div>"
				.	"</div>";

			$j++;
		}

		return apply_filters( 'bookacti_activities_html_by_templates', $html, $template_ids, $activity_ids );
	}
	
	
	/**
	 * Format booking filters
	 * 
	 * @since 1.3.0
	 * @version 1.5.4
	 * @param array $filters 
	 * @return array
	 */
	function bookacti_format_booking_filters( $filters ) {

		$default_filters = apply_filters( 'bookacti_default_booking_filters', array(
			'templates'					=> array(), 
			'activities'				=> array(), 
			'booking_id'				=> 0, 
			'booking_group_id'			=> 0,
			'event_group_id'			=> 0, 
			'event_id'					=> 0, 
			'event_start'				=> '', 
			'event_end'					=> '', 
			'status'					=> array(), 
			'user_id'					=> 0,
			'form_id'					=> 0,
			'from'						=> '',
			'to'						=> '',
			'active'					=> false,
			'group_by'					=> '',
			'order_by'					=> array( 'creation_date', 'id', 'event_start' ), 
			'order'						=> 'desc',
			'offset'					=> 0,
			'per_page'					=> 0,
			'in__booking_id'			=> array(),
			'in__booking_group_id'		=> array(),
			'not_in__booking_id'		=> array(),
			'not_in__booking_group_id'	=> array(),
			'not_in__user_id'			=> array()
		));
		
		$formatted_filters = array();
		foreach( $default_filters as $filter => $default_value ) {
			// If a filter isn't set, use the default value
			if( ! isset( $filters[ $filter ] ) ) {
				$formatted_filters[ $filter ] = $default_value;
				continue;
			}
			
			$current_value = $filters[ $filter ];
			
			// Else, check if its value is correct, or use default
			if( in_array( $filter, array( 'templates' ) ) ) {
				if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
				if( is_array( $current_value ) ) {
					// Check if current user is allowed to manage desired templates, or unset them
					if( ! empty( $current_value ) ) {
						foreach( $current_value as $i => $template_id ) {
						if( ! is_numeric( $template_id ) || ! bookacti_user_can_manage_template( $template_id ) ) {
								unset( $current_value[ $i ] );
							}
						}
					}
					// Re-check if the template list is empty because some template filters may have been removed
					// and get all allowed templates if it is empty
					if( empty( $current_value ) ) {
						$current_value = array_keys( bookacti_fetch_templates() );
					}
				}
				else { $current_value = $default_value; }
				
			} else if( in_array( $filter, array( 'activities', 'in__booking_id', 'in__booking_group_id', 'not_in__booking_id', 'not_in__booking_group_id', 'not_in__user_id' ), true ) ) {
				if( is_numeric( $current_value ) )	{ $current_value = array( $current_value ); }
				if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
				else if( $i = array_search( 'all', $current_value ) !== false ) { unset( $current_value[ $i ] ); }
				
			} else if( in_array( $filter, array( 'status' ), true ) ) {
				if( is_string( $current_value ) )	{ $current_value = array( $current_value ); }
				if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
				else if( $i = array_search( 'all', $current_value ) !== false ) { unset( $current_value[ $i ] ); }
				
			} else if( in_array( $filter, array( 'booking_id', 'booking_group_id', 'event_group_id', 'event_id', 'user_id', 'offset', 'per_page' ), true ) ) {
				if( ! is_numeric( $current_value ) ){ $current_value = $default_value; }
			
			} else if( in_array( $filter, array( 'event_start', 'event_end' ), true ) ) {
				if( ! bookacti_sanitize_datetime( $current_value ) ) { $current_value = $default_value; }
			
			} else if( in_array( $filter, array( 'from', 'to' ), true ) ) {
				if( ! bookacti_sanitize_date( $current_value ) ) { $current_value = $default_value; }
			
			} else if( in_array( $filter, array( 'active' ), true ) ) {
					 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )	{ $current_value = 1; }
				else if( in_array( $current_value, array( 0, '0' ), true ) ){ $current_value = 0; }
				if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }
				
			} else if( $filter === 'order_by' ) {
				$sortable_columns = array( 
					'id', 
					'user_id', 
					'event_id', 
					'event_start', 
					'event_end', 
					'state', 
					'quantity', 
					'template_id', 
					'activity_id', 
					'creation_date' 
				);
				if( is_string( $current_value ) )	{ 
					if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
					else { $current_value = array( $current_value ); }
				}
				if( ! is_array( $current_value ) )				{ $current_value = $default_value; }
				if( $current_value[ 0 ] === 'creation_date' )	{ $current_value = array( 'creation_date', 'id', 'event_start' ); }
				else if( $current_value[ 0 ] === 'id' )			{ $current_value = array( 'id', 'event_start' ); }
				
			} else if( $filter === 'order' ) {
				if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }
			
			} else if( $filter === 'group_by' ) {
				if( ! in_array( $current_value, array( 'booking_group' ), true ) ) { $current_value = $default_value; }
			}
			
			$formatted_filters[ $filter ] = $current_value;
		}
		
		return apply_filters( 'bookacti_formatted_booking_filters', $formatted_filters, $filters, $default_filters );
	}



// PERMISSIONS
	
	// SINGLE BOOKINGS

		/**
		 * Check if user is allowed to manage a booking
		 * 
		 * @param int $booking_id
		 * @param int $user_id
		 * @return boolean
		 */
		function bookacti_user_can_manage_booking( $booking_id, $user_id = false ) {

			$user_can_manage_booking = false;
			if( ! $user_id ) { $user_id = get_current_user_id(); }

			if( user_can( $user_id, 'bookacti_edit_bookings' ) 
			||  bookacti_get_booking_owner( $booking_id ) == $user_id ) { $user_can_manage_booking = true; }

			return apply_filters( 'bookacti_user_can_manage_booking', $user_can_manage_booking, $booking_id, $user_id );
		}


		/**
		 * Check if a booking can be cancelled
		 * @version 1.5.4
		 * @param int $booking_id
		 * @return boolean
		 */
		function bookacti_booking_can_be_cancelled( $booking_id, $bypass_group_check = false ) {
			$is_allowed	= true;
			
			if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
				// Init variable
				$booking = bookacti_get_booking_by_id( $booking_id );
				
				if( ! $booking ) { return apply_filters( 'bookacti_booking_can_be_cancelled', false, $booking_id ); }
				
				$is_cancel_allowed	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_cancel' );
				$is_grouped			= $bypass_group_check ? false : ! empty( $booking->group_id );
				$is_in_delay		= apply_filters( 'bookacti_bypass_delay', false, $booking_id ) ? true : bookacti_is_booking_in_delay( $booking );
				
				// Final check and return the actions array without invalid entries
				if( ! $is_cancel_allowed || ! $booking->active || ! $is_in_delay || $is_grouped ) { $is_allowed = false; }
			}
			
			return apply_filters( 'bookacti_booking_can_be_cancelled', $is_allowed, $booking_id );
		}


		/**
		 * Check if a booking is allowed to be rescheduled
		 * @version 1.5.4
		 * @param int $booking_id
		 * @return boolean
		 */
		function bookacti_booking_can_be_rescheduled( $booking_id ) {
			$is_allowed	= true;
			
			if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
				// First check if the booking is part of a group
				$booking = bookacti_get_booking_by_id( $booking_id );
				
				if( ! $booking ) { return apply_filters( 'bookacti_booking_can_be_rescheduled', false, $booking_id ); }
				
				$is_allowed	= empty( $booking->group_id );
				if( $is_allowed ) {
					// Init variable
					$is_reschedule_allowed	= bookacti_get_setting_value( 'bookacti_cancellation_settings', 'allow_customers_to_reschedule' );
					$is_in_delay			= apply_filters( 'bookacti_bypass_delay', false, $booking_id ) ? true : bookacti_is_booking_in_delay( $booking );

					if( ! $is_reschedule_allowed || ! $booking->active || ! $is_in_delay ) { $is_allowed = false; }
				}
			}
			
			return apply_filters( 'bookacti_booking_can_be_rescheduled', $is_allowed, $booking_id );
		}
		
		
		/**
		 * Check if a booking can be rescheduled to another event
		 * @since 1.1.0
		 * @version 1.5.8
		 * @param int $booking_id
		 * @param int $event_id
		 * @param string $event_start
		 * @param string $event_end
		 * @return boolean
		 */
		function bookacti_booking_can_be_rescheduled_to( $booking_id, $event_id, $event_start, $event_end ) {
			
			$return_array = array( 'status' => 'success' );
			$is_allowed = bookacti_booking_can_be_rescheduled( $booking_id );
			if( ! $is_allowed ) {
				$return_array[ 'status' ] = 'failed';
				$return_array[ 'error' ] = 'reschedule_not_allowed';
				$return_array[ 'message' ] = esc_html__( 'You are not allowed to reschedule this event.', BOOKACTI_PLUGIN_NAME );
			}
						
			return apply_filters( 'bookacti_booking_can_be_rescheduled_to', $return_array, $booking_id, $event_id, $event_start, $event_end );
		}


		/**
		 * Check if a booking can be refunded
		 * @version 1.5.4
		 * @param int $booking_id
		 * @param string $refund_action
		 * @return boolean
		 */
		function bookacti_booking_can_be_refunded( $booking_id, $refund_action = false ) {
			
			$booking = bookacti_get_booking_by_id( $booking_id );
			
			if( ! $booking ) { return apply_filters( 'bookacti_booking_can_be_refunded', false, $booking_id ); }
			
			$refund_actions	= bookacti_get_refund_actions_by_booking_id( $booking_id );
			$true			= true;
			
			// Disallow refund in those cases:
			// -> If the booking is already marked as refunded, 
			if( $booking->state === 'refunded' 
			// -> If the booking is part of a group
			||  ! empty( $booking->group_id )
			// -> If there are no refund action available
			||  empty( $refund_actions )
			// -> If the refund action is set but doesn't exist in available refund actions list
			|| ( ! empty( $refund_action ) && ! array_key_exists( $refund_action, $refund_actions ) ) 
			// -> If the user is not an admin, the booking state has to be 'cancelled' in the first place
			|| ( ! current_user_can( 'bookacti_edit_bookings' ) && $booking->state !== 'cancelled' ) )	{ 

				$true = false; 

			}
			
			return apply_filters( 'bookacti_booking_can_be_refunded', $true, $booking_id );
		}


		/**
		 * Check if a booking state can be changed to another
		 * @version 1.5.6
		 * @param int $booking_id
		 * @param string $new_state
		 * @return boolean
		 */
		function bookacti_booking_state_can_be_changed_to( $booking_id, $new_state ) {
			
			$true		= true;
			$is_admin	= current_user_can( 'bookacti_edit_bookings' );
			
			if( $is_admin ) {
				$state = bookacti_get_booking_state( $booking_id );
				if ( $state === $new_state ) {
					$true = false; 
				}
			}
			
			if( ! $is_admin && $true ) {
				switch ( $new_state ) {
					case 'cancelled':
						$true = bookacti_booking_can_be_cancelled( $booking_id );
						break;
					case 'refund_requested':
					case 'refunded':
						$true = bookacti_booking_can_be_refunded( $booking_id );
						break;
				}
			}

			return apply_filters( 'bookacti_booking_state_can_be_changed', $true, $booking_id, $new_state );
		}
	
		
	// BOOKING GROUPS

		/**
		 * Check if user is allowed to manage a booking group
		 * 
		 * @since 1.1.0
		 * 
		 * @param int $booking_group_id
		 * @param int $user_id
		 * @return boolean
		 */
		function bookacti_user_can_manage_booking_group( $booking_group_id, $user_id = false ) {
			
			$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );
			
			$user_can_manage_booking_group = true;
			foreach( $booking_ids as $booking_id ) {
				$is_allowed = bookacti_user_can_manage_booking( $booking_id, $user_id );
				if( ! $is_allowed ) {
					$user_can_manage_booking_group = false;
					break; // If one of the booking of the group is not allowed, return false immediatly
				}
			}
			
			return apply_filters( 'bookacti_user_can_manage_booking_group', $user_can_manage_booking_group, $booking_id, $user_id );
		}


		/**
		 * Check if a booking group can be cancelled
		 * 
		 * @since 1.1.0
		 * 
		 * @param int $booking_group_id
		 * @return boolean
		 */
		function bookacti_booking_group_can_be_cancelled( $booking_group_id ) {
			
			$booking_ids = bookacti_get_booking_group_bookings_ids( $booking_group_id );
			
			$booking_group_can_be_cancelled = true;
			foreach( $booking_ids as $booking_id ) {
				$is_allowed = bookacti_booking_can_be_cancelled( $booking_id, true );
				if( ! $is_allowed ) {
					$booking_group_can_be_cancelled = false;
					break; // If one of the booking of the group is not allowed, return false immediatly
				}
			}
			
			return apply_filters( 'bookacti_booking_group_can_be_cancelled', $booking_group_can_be_cancelled, $booking_group_id );
		}


		/**
		 * Check if a booking group can be refunded
		 * 
		 * @since 1.1.0
		 * 
		 * @param int $booking_group_id
		 * @param string $refund_action
		 * @return boolean
		 */
		function bookacti_booking_group_can_be_refunded( $booking_group_id, $refund_action = false ) {

			$true			= true;
			$state			= bookacti_get_booking_group_state( $booking_group_id );
			$refund_actions	= bookacti_get_refund_actions_by_booking_group_id( $booking_group_id );
			
			// Disallow refund in those cases:
			// -> If the booking group is already marked as refunded, 
			if( $state === 'refunded' 
			// -> If there are no refund action available
			||  empty( $refund_actions )
			// -> If the refund action is set but doesn't exist in available refund actions list
			|| ( ! empty( $refund_action ) && ! array_key_exists( $refund_action, $refund_actions ) ) 
			// -> If the user is not an admin, the booking group state has to be 'cancelled' in the first place
			|| ( ! current_user_can( 'bookacti_edit_bookings' ) && $state !== 'cancelled' ) )	{ 

				$true = false; 

			}

			return apply_filters( 'bookacti_booking_group_can_be_refunded', $true, $booking_group_id );
		}


		/**
		 * Check if a booking group state can be changed to another
		 * 
		 * @since 1.1.0
		 * 
		 * @param int $booking_group_id
		 * @param string $new_state
		 * @return boolean
		 */
		function bookacti_booking_group_state_can_be_changed_to( $booking_group_id, $new_state ) {

			$true = true;
			switch ( $new_state ) {
				case 'cancelled':
					$true = bookacti_booking_group_can_be_cancelled( $booking_group_id );
					break;
				case 'refund_requested':
					if( current_user_can( 'bookacti_edit_bookings' ) ) {
						$state = bookacti_get_booking_group_state( $booking_group_id );
						if ( $state === 'refund_requested' ) {
							$true = false; 
						}
					} else {
						$true = bookacti_booking_group_can_be_refunded( $booking_group_id );
					}
					break;
				case 'refunded':
					$true = bookacti_booking_group_can_be_refunded( $booking_group_id );
					break;
			}

			return apply_filters( 'bookacti_booking_group_state_can_be_changed', $true, $booking_group_id, $new_state );
		}



	
// BOOKING ACTIONS
	
	// SINGLE BOOKING

		/**
		 * Get booking actions array
		 * @version 1.5.0
		 * @param int $booking_id
		 * @return array
		 */
		function bookacti_get_booking_actions_array( $booking_id ){

			$is_cancel_allowed		= bookacti_booking_can_be_cancelled( $booking_id );
			$is_reschedule_allowed	= bookacti_booking_can_be_rescheduled( $booking_id );
			
			$possible_actions_array = array();

			if( current_user_can( 'bookacti_edit_bookings' ) ) {
				$possible_actions_array[ 'change-state' ] = array( 
					'class'			=> 'bookacti-change-booking-state',
					'label'			=> __( 'Change booking state',  BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Change the booking state to any available state.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'admin' );
			}

			if( $is_cancel_allowed ) {
				$possible_actions_array[ 'cancel' ] = array( 
					'class'			=> 'bookacti-cancel-booking',
					'label'			=> __( 'Cancel', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Cancel the booking.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'front' );
			}

			if( $is_reschedule_allowed ) {
				$possible_actions_array[ 'reschedule' ] = array( 
					'class'			=> 'bookacti-reschedule-booking',
					'label'			=> __( 'Reschedule', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Change the booking dates to any other available time slot for this event.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'both' );
			}

			if( bookacti_booking_can_be_refunded( $booking_id ) ) {
				$possible_actions_array[ 'refund' ] = array( 
					'class'			=> 'bookacti-refund-booking',
					'label'			=> current_user_can( 'bookacti_edit_bookings' ) ? _x( 'Refund', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ) : __( 'Request a refund', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Refund the booking with one of the available refund method.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'both' );
			}
			
			if( current_user_can( 'bookacti_delete_bookings' ) ) {
				$possible_actions_array[ 'delete' ] = array( 
					'class'			=> 'bookacti-delete-booking',
					'label'			=> __( 'Delete',  BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Delete permanently the booking.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'admin' );
			}
			
			return apply_filters( 'bookacti_booking_actions', $possible_actions_array, $booking_id );
		}


		/**
		 * Get booking actions html
		 * 
		 * @version 1.5.0
		 * @param int $booking_id
		 * @param string $admin_or_front
		 * @param boolean $return_array
		 * @param boolean $with_container
		 * @return string
		 */
		function bookacti_get_booking_actions_html( $booking_id, $admin_or_front = 'both', $return_array = false, $with_container = false ) {

			$actions = bookacti_get_booking_actions_array( $booking_id );
			$actions_html_array	= array();
			
			foreach( $actions as $action_id => $action ){
				if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
					$action_html	= '<a '
										. 'href="' . esc_url( $action[ 'link' ] ) . '" '
										. 'id="bookacti-booking-action-' . esc_attr( $action_id ) . '-' . esc_attr( $booking_id ) . '" '
										. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-action bookacti-tip" '
										. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
										. 'data-booking-id="' . esc_attr( $booking_id ) . '" >';
					
					if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
						$action_html .= esc_html( $action[ 'label' ] ); 
					}
					
					$action_html	.= '</a>';
					$actions_html_array[] = $action_html;
				}
			}
			
			// Return the array of html actions
			if( $return_array ) {
				return apply_filters( 'bookacti_booking_actions_html_array', $actions_html_array, $booking_id, $admin_or_front );
			}
			
			$actions_html = implode( ' | ', $actions_html_array );
			
			// Allow third party to add actions
			$actions_html	= apply_filters( 'bookacti_before_booking_actions', '', $booking_id, $admin_or_front )
							. $actions_html
							. apply_filters( 'bookacti_after_booking_actions', '', $booking_id, $admin_or_front );
			
			// Add a container
			if( $with_container ) {
				$actions_html	= '<div class="bookacti-booking-actions" data-booking-id="' . esc_attr( $booking_id ) . '" >'
								.	$actions_html
								. '</div>';
			}

			return apply_filters( 'bookacti_booking_actions_html', $actions_html, $booking_id, $admin_or_front );
		}
	
	
	
	// BOOKING GROUPS

		/**
		 * Get booking group actions array
		 * 
		 * @version 1.5.0
		 * 
		 * @param int $booking_group_id
		 * @return array
		 */
		function bookacti_get_booking_group_actions_array( $booking_group_id ){
			
			$possible_actions_array = array();

			if( current_user_can( 'bookacti_edit_bookings' ) ) {
				$possible_actions_array[ 'change-state' ] = array( 
					'class'			=> 'bookacti-change-booking-group-state',
					'label'			=> __( 'Change booking state',  BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Change the booking group state to any available state.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'admin' );
				
				$possible_actions_array[ 'edit-single' ] = array( 
					'class'			=> 'bookacti-show-booking-group-bookings',
					'label'			=> __( 'Edit bookings',  BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Edit each booking of the group separately.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'admin' );
			}

			if( bookacti_booking_group_can_be_cancelled( $booking_group_id ) ) {
				$possible_actions_array[ 'cancel' ] = array( 
					'class'			=> 'bookacti-cancel-booking-group',
					'label'			=> __( 'Cancel', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Cancel the booking group.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'front' );
			}

			if( bookacti_booking_group_can_be_refunded( $booking_group_id ) ) {
				$possible_actions_array[ 'refund' ] = array( 
					'class'			=> 'bookacti-refund-booking-group',
					'label'			=> current_user_can( 'bookacti_edit_bookings' ) ? _x( 'Refund', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ) : __( 'Request a refund', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Refund the booking group with one of the available refund method.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'both' );
			}
			
			if( current_user_can( 'bookacti_delete_bookings' ) ) {
				$possible_actions_array[ 'delete' ] = array( 
					'class'			=> 'bookacti-delete-booking-group',
					'label'			=> __( 'Delete', BOOKACTI_PLUGIN_NAME ),
					'description'	=> __( 'Delete permanently the booking group.', BOOKACTI_PLUGIN_NAME ),
					'link'			=> '',
					'admin_or_front'=> 'admin' );
			}
			
			return apply_filters( 'bookacti_booking_group_actions', $possible_actions_array, $booking_group_id );
		}


		/**
		 * Get booking group actions html
		 *  
		 * @version 1.5.0
		 * 
		 * @param int $booking_group_id
		 * @param string $admin_or_front
		 * @param boolean $return_array
		 * @param boolean $with_container
		 * @return string
		 */
			function bookacti_get_booking_group_actions_html( $booking_group_id, $admin_or_front = 'both', $return_array = false, $with_container = false ) {
			
			$actions = bookacti_get_booking_group_actions_array( $booking_group_id );
			$actions_html_array	= array();
			
			foreach( $actions as $action_id => $action ){
				if( $admin_or_front === 'both' || $action[ 'admin_or_front' ] === 'both' || $admin_or_front === $action[ 'admin_or_front' ] ) {
					$action_html	= '<a '
										. 'href="' . esc_url( $action[ 'link' ] ) . '" '
										. 'id="bookacti-booking-group-action-' . esc_attr( $action_id ) . '-' . intval( $booking_group_id ) . '" '
										. 'class="button ' . esc_attr( $action[ 'class' ] ) . ' bookacti-booking-group-action bookacti-tip" '
										. 'data-tip="' . esc_attr( $action[ 'description' ] ) . '" '
										. 'data-booking-group-id="' . intval( $booking_group_id ) . '" >';
					
					if( $admin_or_front === 'front' || $action[ 'admin_or_front' ] === 'front' ) { 
						$action_html .= esc_html( $action[ 'label' ] ); 
					}
					
					$action_html	.= '</a>';
					$actions_html_array[] = $action_html;
				}
			}
			
			// Return the array of html actions
			if( $return_array ) {
				return apply_filters( 'bookacti_booking_group_actions_html_array', $actions_html_array, $booking_group_id, $admin_or_front );
			}
			
			$actions_html = implode( ' | ', $actions_html_array );
			
			// Allow third party to add actions
			$actions_html	= apply_filters( 'bookacti_before_booking_group_actions', '', $booking_group_id, $admin_or_front ) 
							. $actions_html
							. apply_filters( 'bookacti_after_booking_group_actions', '', $booking_group_id, $admin_or_front );
			
			// Add a container
			if( $with_container ) {
				$actions_html	= '<div class="bookacti-booking-group-actions" data-booking-group-id="' . esc_attr( $booking_group_id ) . '" >' 
								.	$actions_html
								. '</div>';
			}
			
			return apply_filters( 'bookacti_booking_group_actions_html', $actions_html, $booking_group_id, $admin_or_front );
		}




// REFUND BOOKING

	/**
	 * Get available actions user can take to be refunded 
	 * 
	 * @return array
	 */
	function bookacti_get_refund_actions(){
		$possible_actions_array = array(
			'email' => array( 
				'id'			=> 'email',
				'label'			=> __( 'Email', BOOKACTI_PLUGIN_NAME ),
				'description'	=> __( 'Send a refund request by email to the administrator.', BOOKACTI_PLUGIN_NAME ) )
		);

		return apply_filters( 'bookacti_refund_actions', $possible_actions_array );
	}
	
	
	/**
	 * Get refund actions for a specific booking
	 * 
	 * @version 1.1.0
	 * 
	 * @param int $booking_id
	 * @return array
	 */
	function bookacti_get_refund_actions_by_booking_id( $booking_id ) {
		return bookacti_get_refund_actions_by_booking_type( $booking_id, 'single' );
	}
	
	
	/**
	 * Get refund actions for a specific booking group
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $booking_group_id
	 * @return array
	 */
	function bookacti_get_refund_actions_by_booking_group_id( $booking_group_id ) {
		return bookacti_get_refund_actions_by_booking_type( $booking_group_id, 'group' );
	}

	/**
	 * Get refund actions for a specific booking or booking group
	 * 
	 * @since 1.1.0
	 * @version 1.3.0
	 * 
	 * @param int $booking_or_booking_group_id
	 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
	 * @return array
	 */
	function bookacti_get_refund_actions_by_booking_type( $booking_or_booking_group_id, $booking_type = 'single' ) {
		
		$possible_actions = bookacti_get_refund_actions();
		
		// If current user is a customer
		if( ! current_user_can( 'bookacti_edit_bookings' ) ) {
			// Keep only allowed action
			$allowed_actions = bookacti_get_setting_value( 'bookacti_cancellation_settings', 'refund_actions_after_cancellation' );
			if( ! is_array( $allowed_actions ) ) {
				if( ! empty( $allowed_actions ) ) {
					$allowed_actions = array( $allowed_actions );
				} else {
					$allowed_actions = array();
				}
			}
			// Keep all possible actions that are allowed
			$possible_actions = array_intersect_key( $possible_actions, array_flip( $allowed_actions ) );
		
		// If current user is an admin
		} else {
			// Email action is useless, remove it
			if( isset( $possible_actions[ 'email' ] ) ) { unset( $possible_actions[ 'email' ] ); }
		}
		
		if( $booking_type === 'single' ) {
			$possible_actions = apply_filters( 'bookacti_refund_actions_by_booking', $possible_actions, $booking_or_booking_group_id );
		} else if( $booking_type === 'group' ) {
			$possible_actions = apply_filters( 'bookacti_refund_actions_by_booking_group', $possible_actions, $booking_or_booking_group_id );
		}
		
		return $possible_actions;
	}
	
	
	/**
	 * Get dialog refund text for a specific booking
	 * 
	 * @version 1.1.0
	 * 
	 * @param int $booking_id
	 * @return string
	 */
	function bookacti_get_refund_dialog_html_by_booking_id( $booking_id ) {
		return bookacti_get_refund_dialog_html_by_booking_type( $booking_id, 'single' );
	}
	
	
	/**
	 * Get dialog refund text for a specific booking
	 * 
	 * @version 1.1.0
	 * 
	 * @param int $booking_group_id
	 * @return string
	 */
	function bookacti_get_refund_dialog_html_by_booking_group_id( $booking_group_id ) {
		return bookacti_get_refund_dialog_html_by_booking_type( $booking_group_id, 'group' );
	}
	
	
	/**
	 * Get dialog refund text for a specific booking
	 * 
	 * @since 1.1.0
	 * 
	 * @param int $booking_or_booking_group_id
	 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
	 * @return string
	 */
	function bookacti_get_refund_dialog_html_by_booking_type( $booking_or_booking_group_id, $booking_type = 'single' ) {

		$possible_actions = bookacti_get_refund_actions_by_booking_type( $booking_or_booking_group_id, $booking_type );

		$actions_list = '';
		foreach( $possible_actions as $possible_action ){
			$actions_list .= '<div class="bookacti-refund-option" >'
								. '<span class="bookacti-refund-option-radio" >'
									. '<input '
										. ' type="radio" '
										. ' name="refund-action" '
										. ' value="' . esc_attr( $possible_action['id'] ) . '" '
										. ' id="bookacti-refund-action-' . esc_attr( $possible_action['id'] ) . '" '
										. ' class="bookacti-refund-action" '
									. '/>'
								. '</span>'
								. '<label for="bookacti-refund-action-' . esc_attr( $possible_action['id'] ) . '" class="bookacti-refund-option-label-and-description" >'
									. '<strong class="bookacti-refund-option-label" >' . esc_html( $possible_action['label'] ). ':</strong> '
									. '<span class="bookacti-refund-option-description" >' . esc_html( $possible_action['description'] ) . '</span>'
								. '</label>'
							. '</div>';
		}

		// Define title and add actions list
		$html_to_return		= '';
		if( empty( $possible_actions ) ) {
			$html_to_return .= '<div id="bookacti-no-refund-option" >';
			$html_to_return .= esc_html__( 'Sorry, no available refund option were found. Please contact the administrator.', BOOKACTI_PLUGIN_NAME );
			$html_to_return .= '</div>';
		} else {

			$html_to_return .= apply_filters( 'bookacti_before_refund_actions', '', $booking_or_booking_group_id, $booking_type );

			$html_to_return .= '<div id="bookacti-refund-option-title" >';
			if( count( $possible_actions ) === 1 ) {
				$html_to_return .= esc_html__( 'There is only one available refund option:', BOOKACTI_PLUGIN_NAME );
			} else {
				$html_to_return .= esc_html__( 'Pick a refund option:', BOOKACTI_PLUGIN_NAME );
			}
			$html_to_return .= '</div>';

			$html_to_return .= '</div><form id="bookacti-refund-options" >';
			$html_to_return .= wp_nonce_field( 'bookacti_refund_booking', 'nonce_refund_booking', true, false );
			$html_to_return .= $actions_list;
			$html_to_return .= '</form>';
		}

		return $html_to_return;
	}


	/**
	 * Send a refund request by email for a specific booking
	 * @version 1.5.4
	 * @param int $booking_id
	 * @param string $booking_type Defined if the given id is a booking id or a booking group id. Accepted values are 'single' and 'group'.
	 * @param string $user_message
	 * @return boolean
	 */
	function bookacti_send_email_refund_request( $booking_id, $booking_type, $user_message = false ) {

		$user = get_userdata( get_current_user_id() );
		
		$to = apply_filters( 'bookacti_refund_request_email_to', array( get_option( 'admin_email' ) ), $booking_id, $booking_type );
		
		/* translators: %1$s is the booking id */
		$subject	= $booking_type === 'group' ? __( 'Refund request for booking group %1$s', BOOKACTI_PLUGIN_NAME ) : __( 'Refund request for booking %1$s', BOOKACTI_PLUGIN_NAME );
		$subject	= apply_filters( 'bookacti_refund_request_email_subject', sprintf( $subject, $booking_id ), $booking_id, $booking_type );

		$data = array();

		$data['user']			= array();
		$data['user']['name']	= isset( $user->first_name ) && isset( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->user_login;
		$data['user']['name']	= '<a href="' . esc_url( get_edit_user_link() ) . '">' . esc_html( $data['user']['name'] ) . '</a>';
		$data['user']['email']	= '<a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a>';
		
		// Single booking
		if( $booking_type === 'single' ) {
			
			$booking = bookacti_get_booking_by_id( $booking_id );
			
			if( $booking ) {
				$data['booking']					= array();
				$data['booking']['calendar_id']		= $booking->template_id;
				$data['booking']['activity_name']	= apply_filters( 'bookacti_translate_text', $booking->title ) . ' (' . _x( 'id', 'An id is a unique identification number' ) . ': ' . $booking->activity_id . ')';
				$data['booking']['event_start']		= bookacti_format_datetime( $booking->event_start );
				$data['booking']['event_end']		= bookacti_format_datetime( $booking->event_end );
				$data['booking']['quantity']		= $booking->quantity;
				$data['booking']['status']			= $booking->state;
			}
			
		// Booking Group
		} else if( $booking_type === 'group' ) {
			
			$booking_group	= bookacti_get_booking_group_by_id( $booking_id );
			$bookings		= bookacti_get_bookings_by_booking_group_id( $booking_id );
			if( $booking_group || $bookings ) {
				$data['booking_group'] = array();
			}
			if( $bookings ) {
				$data['booking_group']['calendar_id']	= $bookings[0]->template_id;
				$data['booking_group']['events']		= bookacti_get_formatted_booking_events_list( $bookings, 'show' );
			}
			if( $booking_group ) {
				$data['booking_group']['status'] = $booking_group->state;
			}
		}
		

		$data = apply_filters( 'bookacti_refund_request_email_data', $data, $booking_id, $booking_type );

		/* translators: %1$s is a user name and %2$s is the booking ID. */
		$message = '<h3>' . sprintf( esc_html__( '%1$s wants to be refunded for booking %2$s', BOOKACTI_PLUGIN_NAME ), $data['user']['name'], $booking_id ) . '</h3>';
		foreach( $data as $category_name => $category_data ) {
			$message .= '<h4>' . esc_html( ucfirst ( str_replace( '_', ' ', $category_name ) ) ) . '</h4>';
			$message .= '<table style="border: none;" >';
			foreach( $category_data as $name => $value ) {
				$message .= '<tr><td style="border: none; width: 135px; padding-right: 15px;">' . esc_html( ucfirst ( str_replace( '_', ' ', $name ) ) ) . '</td><td>' . $value . '</td>';
			}
			$message .= '</table>';
		}

		/* translators: Message left by the user */
		if( $user_message ) {
			$message	.= '<h4>' . esc_html__( 'User message', BOOKACTI_PLUGIN_NAME ). '</h4>';
			$message	.= '<em>' . esc_html( $user_message ) . '</em><br/>';
		}

		$message	= apply_filters( 'bookacti_refund_request_email_message', $message, $booking_id, $booking_type, $data, $user_message );
		$headers	= apply_filters( 'bookacti_refund_request_email_headers', array( 'Content-Type: text/html; charset=UTF-8' ) );

		$sent = wp_mail( $to, $subject, $message, $headers );

		return $sent;
	}




// FORMATTING
	
	/**
	 * Retrieve booking states labels and display data
	 * 
	 * @return array
	 */
	function bookacti_get_booking_state_labels() {
		$booking_states_labels = apply_filters( 'bookacti_booking_states_labels_array', array(
			'booked'			=> array( 'display_state' => 'good',	'label' => __( 'Booked', BOOKACTI_PLUGIN_NAME ) ),
			'pending'			=> array( 'display_state' => 'warning',	'label' => __( 'Pending', BOOKACTI_PLUGIN_NAME ) ),
			'cancelled'			=> array( 'display_state' => 'bad',		'label' => __( 'Cancelled', BOOKACTI_PLUGIN_NAME ) ),
			'refunded'			=> array( 'display_state' => 'bad',		'label' => __( 'Refunded', BOOKACTI_PLUGIN_NAME ) ),
			'refund_requested'	=> array( 'display_state' => 'bad',		'label' => __( 'Refund requested', BOOKACTI_PLUGIN_NAME ) )
		) );

		return $booking_states_labels;
	}
	
	/**
	 * Retrieve payment status labels and display data
	 * 
	 * @since 1.3.0
	 * @return array
	 */
	function bookacti_get_payment_status_labels() {
		$payment_status_labels = apply_filters( 'bookacti_payment_status_labels_array', array(
			'none'	=> array( 'display_state' => 'disabled','label' => __( 'No payment required', BOOKACTI_PLUGIN_NAME ) ),
			'owed'	=> array( 'display_state' => 'warning',	'label' => __( 'Owed', BOOKACTI_PLUGIN_NAME ) ),
			'paid'	=> array( 'display_state' => 'good',	'label' => __( 'Paid', BOOKACTI_PLUGIN_NAME ) )
		) );

		return $payment_status_labels;
	}
	
	
	/**
	 * Give a the formatted and translated booking state
	 * 
	 * @version 1.3.0
	 * @param string $state
	 * @param boolean $icon_only
	 * @return string
	 */
	function bookacti_format_booking_state( $state, $icon_only = false ) {
		$booking_states_labels = bookacti_get_booking_state_labels();
		
		$formatted_value = '';
		if( isset( $booking_states_labels[ $state ] ) ) {
			if( $icon_only ) {
				$formatted_value = '<span class="bookacti-booking-state bookacti-booking-state-' . esc_attr( $booking_states_labels[ $state ][ 'display_state' ] ) . ' bookacti-tip" data-booking-state="' . esc_attr( $state ) . '" data-tip="'. esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '" ></span>';
			} else {
				$formatted_value = '<span class="bookacti-booking-state bookacti-booking-state-' . esc_attr( $booking_states_labels[ $state ][ 'display_state' ] ) . '" data-booking-state="' . esc_attr( $state ) . '" >' . esc_html( $booking_states_labels[ $state ][ 'label' ] ) . '</span>';
			}
		} else if( $state ) {
			$formatted_value = '<span class="bookacti-booking-state" data-booking-state="' . esc_attr( $state ) . '" >' . esc_html__( $state, BOOKACTI_PLUGIN_NAME ) . '</span>';
		}

		return apply_filters( 'bookacti_booking_states_display', $formatted_value, $state, $icon_only );
	}
	
	
	/**
	 * Give a the formatted and translated payment status
	 * 
	 * @since 1.3.0
	 * @param string $status
	 * @param boolean $icon_only
	 * @return string
	 */
	function bookacti_format_payment_status( $status, $icon_only = false ) {
		$payment_status_labels = bookacti_get_payment_status_labels();
		
		$formatted_value = '';
		if( isset( $payment_status_labels[ $status ] ) ) {
			if( $icon_only ) {
				$formatted_value = '<span class="bookacti-payment-status bookacti-payment-status-' . esc_attr( $payment_status_labels[ $status ][ 'display_state' ] ) . ' bookacti-tip" data-payment-status="' . esc_attr( $status ) . '" data-tip="'. esc_html( $payment_status_labels[ $status ][ 'label' ] ) . '" ></span>';
			} else {
				$formatted_value = '<span class="bookacti-payment-status bookacti-payment-status-' . esc_attr( $payment_status_labels[ $status ][ 'display_state' ] ) . '" data-payment-status="' . esc_attr( $status ) . '" >' . esc_html( $payment_status_labels[ $status ][ 'label' ] ) . '</span>';
			}
		} else if( $status ) {
			$formatted_value = '<span class="bookacti-payment-status" data-payment-status="' . esc_attr( $status ) . '" >' . esc_html__( $status, BOOKACTI_PLUGIN_NAME ) . '</span>';
		}

		return apply_filters( 'bookacti_payment_status_display', $formatted_value, $status, $icon_only );
	}

	
	/**
	 * Give an array of all ACTIVE booking state, every other booking states will be considered as INACTIVE
	 * 
	 * @return array
	 */
	function bookacti_get_active_booking_states() {
		return apply_filters( 'bookacti_active_booking_states', array( 'booked', 'pending' ) );
	}	




// SHORTCODE BOOKING LIST

/**
 * Get booking list columns 
 * 
 * @since 1.3.0
 * @param int $user_id
 * @return array
 */
function bookacti_get_booking_list_columns( $user_id = 0 ) {
	
	// Set up booking list columns
	$columns = apply_filters( 'bookacti_user_bookings_list_columns_titles', array(
		10	=> array( 'id' => 'id',			'title' => esc_html_x( 'id', 'An id is a unique identification number' ), BOOKACTI_PLUGIN_NAME ),
		20	=> array( 'id' => 'activity',	'title' => esc_html__( 'Activity', BOOKACTI_PLUGIN_NAME ) ),
		40	=> array( 'id' => 'quantity',	'title' => esc_html__( 'Quantity', BOOKACTI_PLUGIN_NAME ) ),
		50	=> array( 'id' => 'state',		'title' => esc_html_x( 'State', 'State of a booking', BOOKACTI_PLUGIN_NAME ) ),
		100 => array( 'id' => 'actions',	'title' => esc_html__( 'Actions', BOOKACTI_PLUGIN_NAME ) )
	), $user_id );
	
	// Order columns
	ksort( $columns );
	
	return $columns;
}
	
/**
 * Get booking list rows
 * 
 * @since 1.3.0
 * @param array $bookings
 * @param array $columns
 * @param int $user_id
 * @return string
 */
function bookacti_get_booking_list_rows( $bookings, $columns = array(), $user_id = 0 ) {
	
	if( ! $columns ) { $columns = bookacti_get_booking_list_columns( $user_id ); }
	
	$list_items				= array();
	$groups_already_added	= array();
	$hidden_states			= apply_filters( 'bookacti_bookings_list_hidden_states', array( 'in_cart', 'expired', 'removed' ) );
	
	// Build an array of bookings rows
	foreach( $bookings as $booking ) {

		// Single Bookings
		if( empty( $booking->group_id ) ) {

			if( ! in_array( $booking->state, $hidden_states, true ) ) {

				$list_items[] = apply_filters( 'bookacti_user_bookings_list_columns_value', array(
					'id'		=> $booking->id,
					'activity'	=> bookacti_get_formatted_booking_events_list( array( $booking ) ),
					'quantity'	=> $booking->quantity,
					'state'		=> bookacti_format_booking_state( $booking->state ),
					'actions'	=> bookacti_get_booking_actions_html( $booking->id, 'front' ),
					'type'		=> 'single'
				), $booking, $user_id );
			}

		// Booking groups
		} else if( ! in_array( $booking->group_id, $groups_already_added, true ) ) {

			$state = bookacti_get_booking_group_state( $booking->group_id ); 

			if( ! in_array( $state, $hidden_states, true ) ) {

				$quantity		= bookacti_get_booking_group_quantity( $booking->group_id ); 
				$group_bookings = bookacti_get_bookings_by_booking_group_id( $booking->group_id ); 

				$list_items[] = apply_filters( 'bookacti_user_bookings_list_columns_value', array(
					'id'		=> $booking->group_id,
					'activity'	=> bookacti_get_formatted_booking_events_list( $group_bookings ),
					'quantity'	=> $quantity,
					'state'		=> bookacti_format_booking_state( $state ),
					'actions'	=> bookacti_get_booking_group_actions_html( $booking->group_id, 'front' ),
					'type'		=> 'group'
				), $booking, $user_id );

				// Flag the group as 'already added' to make it appears only once in the list
				$groups_already_added[] = $booking->group_id;
			}
		}

	}

	
	// Build the HTML booking rows
	$rows = '';
	foreach( $list_items as $list_item ) {
		$rows .= "<tr>";
		foreach( $columns as $column ) {

			// Format output values
			switch ( $column[ 'id' ] ) {
				case 'id':
					$value = isset( $list_item[ 'id' ] ) ? intval( $list_item[ 'id' ] ) : '';
					break;
				case 'activity':
					$value = isset( $list_item[ 'activity' ] ) ? $list_item[ 'activity' ] : '';
					break;
				case 'quantity':
					$value = isset( $list_item[ 'quantity' ] ) ? intval( $list_item[ 'quantity' ] ) : '';
					break;
				case 'state':
				case 'actions':
				default:
					$value = isset( $list_item[ $column[ 'id' ] ] ) ? $list_item[ $column[ 'id' ] ] : '';
			}
			
			$column_id	 = sanitize_title_with_dashes( $column[ 'id' ] );
			$class_empty = empty( $value ) ? 'bookacti-empty-column' : '';
			$class_group = $list_item[ 'type' ] === 'group' ? 'bookacti-booking-group-' . $column_id : '';
			
			$rows .=  "<td data-title='" . esc_attr( $column[ 'title' ] ) 
					. "' class='bookacti-column-" . $column_id . ' ' . $class_empty . "' >"
					.	"<div class='bookacti-booking-" . $column_id . " " . $class_group . "' >"  
					.		$value 
					.	"</div>"
					. "</td>";
		} 
		$rows .= "</tr>";
	}
	
	// If there are no booking rows
	if( empty( $list_items ) ) {
		$rows	.= '<tr>'
				.	'<td colspan="' . esc_attr( count( $columns ) ) . '">' . esc_html__( "You don't have any bookings.", BOOKACTI_PLUGIN_NAME ) . '</td>'
				. '</tr>';
	}
	
	return $rows;
}