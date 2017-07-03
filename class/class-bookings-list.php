<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'Bookings_List_Table' ) ) { 

	class Bookings_List_Table extends WP_List_Table {
		
		public $items;
		
		public function __construct(){
			global $status, $page;
			parent::__construct( array(
				/*translator:  */
				'singular'	=> 'booking',	//singular name of the listed records
				'plural'	=> 'bookings',	//plural name of the listed records
				'ajax'		=> true,
				'screen'	=> 'bookacti-bookings'
			) );

			add_action( 'admin_head', array( &$this, 'admin_header' ) );            
		}

		
		public function get_columns(){

			// SET THE COLUMNS
			$columns = array(
				'cb'		=> '<input type="checkbox" />',
				'id'		=> _x( 'id', 'An id is a unique identification number', BOOKACTI_PLUGIN_NAME ),
				'customer'	=> __( 'Customer', BOOKACTI_PLUGIN_NAME ),
				'state'		=> _x( 'State', 'State of a booking', BOOKACTI_PLUGIN_NAME ),
				'quantity'	=> _x( 'Qty', 'Short for "Quantity"', BOOKACTI_PLUGIN_NAME )
			);

			/**
			 * Columns of the booking list.
			 * 
			 * You must use 'bookacti_booking_list_columns_order' php filter order your custom columns.
			 * You must use 'bookacti_fill_booking_list_entry' jquery hook to fill your custom columns.
			 * 
			 * @since 1.0.0
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'bookacti_booking_list_columns', $columns );


			// SORT THE COLUMNS
			$columns_order = array(
				10 => 'cb',
				15 => 'id',
				20 => 'customer',
				30 => 'state',
				40 => 'quantity'
			);

			/**
			 * Columns order of the booking list.
			 * 
			 * Order the columns given by the filter 'bookacti_booking_list_columns'
			 * 
			 * @since 1.0.0
			 * 
			 * @param array $columns
			 */
			$columns_order = apply_filters( 'bookacti_booking_list_columns_order', $columns_order );

			ksort( $columns_order );

			$displayed_columns = array();
			foreach( $columns_order as $column_id ) {
				$displayed_columns[ $column_id ] = $columns[ $column_id ];
			}

			// RETURN THE COLUMNS
			return $displayed_columns;
		}

		
		public function prepare_items() {
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			
			$this->items = $this->get_booking_list_items();
		}

		
		public function column_default( $item, $column_name ) {
			return $item[ $column_name ];
		}

		
		public function column_customer( $item ) {
			
			if( $item[ 'booking_type' ] === 'group' ) {
				$actions = bookacti_get_booking_group_actions_html( $item[ 'id' ], 'admin', true );
			} else {
				$actions = bookacti_get_booking_actions_html( $item[ 'id' ], 'admin', true );
			}
			
			return sprintf( '%1$s %2$s', $item[ 'customer' ], $this->row_actions( $actions, false, $item[ 'booking_type' ] ) );
		}
		
		
		public function get_booking_list_items( $event_group_id = 0, $event_id = 0, $event_start = 0, $event_end = 0, $booking_group_id = 0 ) {
			
			$booking_group_id	= isset( $_REQUEST[ 'booking_group_id' ] )	? intval( $_REQUEST[ 'booking_group_id' ] ): $booking_group_id;			
			$event_group_id		= isset( $_REQUEST[ 'event_group_id' ] )	? intval( $_REQUEST[ 'event_group_id' ] ): $event_group_id;			
			$event_id			= isset( $_REQUEST[ 'event_id' ] )			? intval( $_REQUEST[ 'event_id' ] ): $event_id;			
			$event_start		= isset( $_REQUEST[ 'event_start' ] )		? bookacti_sanitize_datetime( $_REQUEST[ 'event_start' ] )	: $event_start;			
			$event_end			= isset( $_REQUEST[ 'event_end' ] )			? bookacti_sanitize_datetime( $_REQUEST[ 'event_end' ] )	: $event_end;
			
			$is_booking_group	= ! empty( $booking_group_id );
			$is_group_of_events = ! empty( $event_group_id );
			$is_event			= ! empty( $event_id ) && ! empty( $event_start ) && ! empty( $event_end );
			
			
			if( ! $is_booking_group && ! $is_group_of_events && ! $is_event ){
				return array();
			}
			
			$booking_type = $is_group_of_events ? 'group' : 'single';
			
			// Retrieve booking groups in priority
			if( $is_booking_group ) {
				$bookings = bookacti_get_bookings_by_booking_group_id( $booking_group_id );
				
			} else if( $is_group_of_events ) {
				$bookings = bookacti_get_booking_groups_data_for_bookings_list( $event_group_id );
			
			// Retrieve single bookings
			} else if( $is_event ) {
				$bookings = bookacti_get_bookings_data_for_bookings_list( $event_id, $event_start, $event_end );
			}
			
			// Retrieve information about users and stock them into an array sorted by user id
			$user_ids = array();
			foreach( $bookings as $booking ) {
				if( ! in_array( $booking->user_id, $user_ids ) ){
					$user_ids[] = $booking->user_id;
				}
			}
			$users = bookacti_get_users_data( $user_ids );
			
			// Build booking list
			$booking_list_items = array();
			foreach( $bookings as $booking ) {
				
				// Booking group fields
				if( $is_group_of_events ) {
					$quantity = bookacti_get_booking_group_quantity( $booking->id );
					
				// Single booking fields
				} else if( $is_event || $is_booking_group ) {
					$quantity = $booking->quantity;
				}
				
				// Common fields
				if( is_numeric( $booking->user_id ) ) {
					$user = $users[ $booking->user_id ];
					$customer = '<a '
								. ' href="' . esc_url( get_admin_url() . 'user-edit.php?user_id=' . $booking->user_id ) . '" '
								. ' target="_blank" '
								. ' >'
									. esc_html( $user->user_login . ' (' . $user->user_email . ')' )
							. ' </a>';
				} else {
					$customer = esc_html( __( 'Unknown user', BOOKACTI_PLUGIN_NAME ) . ' (' . $booking->user_id . ')' );
				}

				$state = bookacti_format_booking_state( $booking->state );
				
				$booking_item = apply_filters( 'bookacti_booking_list_booking_columns', array( 
					'customer'		=> $customer,
					'state'			=> $state,
					'quantity'		=> $quantity,
					'id'			=> $booking->id,
					'order_id'		=> $booking->order_id,
					'booking_type'	=> $booking_type,
					'primary_data'	=> array( 
						'(' . _x( 'id', 'An id is a unique identification number', BOOKACTI_PLUGIN_NAME ) . ': ' . $booking->id . ')', 
						$state, 
						'x' . $quantity 
					)
				), $booking, $user );

				// Add info on the primary column to make them directly visible in responsive view
				if( ! empty( $booking_item[ 'primary_data' ] ) ) {
					$primary_column_name = $this->get_primary_column();
					$primary_data = '<div class="bookacti-booking-primary-data-container">';
					foreach( $booking_item[ 'primary_data' ] as $single_primary_data ) {
						$primary_data .= '<span class="bookacti-booking-primary-data">' . $single_primary_data . '</span>';
					}
					$primary_data .= '</div>';
					$booking_item[ $primary_column_name ] .= $primary_data;
				}

				$booking_list_items[] = $booking_item;
			}
			
			return $booking_list_items;
		}
		
		
		/**
		 * Get the tbody element for the list table.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function get_rows_or_placeholder() {
			if ( $this->has_items() ) {
				return $this->get_rows();
			} else {
				return '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">' . esc_html__( 'No items found.', BOOKACTI_PLUGIN_NAME ) . '</td></tr>';
			}
		}
		
		
		/**
		 * Generate the table rows
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function get_rows() {
			$rows = '';
			foreach ( $this->items as $item ) {
				$rows .= $this->get_single_row( $item );
			}
			return $rows;
		}
		
		
		/**
		 * Returns content for a single row of the table
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param object $item The current item
		 */
		public function get_single_row( $item ) {
			$row  = '<tr>';
			$row .= $this->get_single_row_columns( $item );
			$row .= '</tr>';
			
			return $row;
		}
		
		/**
		 * Returns the columns for a single row of the table
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param object $item The current item
		 */
		public function get_single_row_columns( $item ) {
			
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
			
			$returned_columns = '';
			foreach ( $columns as $column_name => $column_display_name ) {
				$classes = "$column_name column-$column_name";
				if ( $primary === $column_name ) {
					$classes .= ' has-row-actions column-primary';
				}

				if ( in_array( $column_name, $hidden ) ) {
					$classes .= ' hidden';
				}

				// Comments column uses HTML in the display name with screen reader text.
				// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
				$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

				$attributes = "class='$classes' $data";
				
				if ( 'cb' === $column_name ) {
					$returned_columns .= '<th scope="row" class="check-column">';
					$returned_columns .=  $this->column_cb( $item );
					$returned_columns .=  '</th>';
				} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
					$returned_columns .=  call_user_func(
											array( $this, '_column_' . $column_name ),
											$item,
											$classes,
											$data,
											$primary
										);
				} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
					$returned_columns .=  "<td $attributes>";
					$returned_columns .=  call_user_func( array( $this, 'column_' . $column_name ), $item );
					$returned_columns .=  $this->handle_row_actions( $item, $column_name, $primary );
					$returned_columns .=  "</td>";
				} else {
					$returned_columns .=  "<td $attributes>";
					$returned_columns .=  $this->column_default( $item, $column_name );
					$returned_columns .=  $this->handle_row_actions( $item, $column_name, $primary );
					$returned_columns .=  "</td>";
				}
			}
			
			return $returned_columns;
		}
		
		
		/**
		* Generate row actions div
		*
		* @version 1.1.0
		* @access protected
		*
		* @param array $actions The list of actions
		* @param bool $always_visible Whether the actions should be always visible
		* @param bool $booking_type 'group' or 'single'
		* @return string
		*/
		protected function row_actions( $actions, $always_visible = false, $booking_type = 'single' ) {
			$action_count = count( $actions );
			$i = 0;

			if ( !$action_count )
				return '';
			
			$class_visible		= $always_visible ? 'visible' : '';
			$class_booking_type = $booking_type === 'group' ? 'bookacti-booking-group-actions' : 'bookacti-booking-actions';
			$out = '<div class="row-actions ' . esc_attr( $class_booking_type ) . ' ' . esc_attr( $class_visible ) . '">';
			foreach ( $actions as $action => $link ) {
				++$i;
				$sep = $i == $action_count ? '' : ' | ';
				$out .= $link . $sep;
			}
			$out .= '</div>';
			
			/* translators: Don't translate */
			//$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details' ) . '</span></button>';

			return $out;
	   }
	}
}

add_filter( 'list_table_primary_column', 'bookacti_bookings_list_table_primary_column', 10, 2 );
function bookacti_bookings_list_table_primary_column( $column, $screen ) {

	if ( $screen === 'bookacti-bookings' ) {
		$column = 'customer';
	}

	return $column;
}