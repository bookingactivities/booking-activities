<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'Bookings_List_Table' ) ) { 
	
	/**
	 * Bookings WP_List_Table
	 * 
	 * @version 1.3.0
	 */
	class Bookings_List_Table extends WP_List_Table {
		
		public $items;
		protected $screen;
		
		/**
		 * Set up the Booking list table
		 * 
		 * @version 1.3.0
		 */
		public function __construct(){
			parent::__construct( array(
				/*translator:  */
				'singular'	=> 'booking',	// Singular name of the listed records
				'plural'	=> 'bookings',	// Plural name of the listed records
				'ajax'		=> false,
				'screen'	=> null
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
		}
		
		
		/**
		 * Get booking list table columns
		 * 
		 * @version 1.3.0
		 * @return array
		 */
		public function get_columns(){

			// SET THE COLUMNS
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'id'			=> _x( 'id', 'An id is a unique identification number', BOOKACTI_PLUGIN_NAME ),
				'customer'		=> __( 'Customer', BOOKACTI_PLUGIN_NAME ),
				'state'			=> _x( 'Status', 'Booking status', BOOKACTI_PLUGIN_NAME ),
				'quantity'		=> _x( 'Qty', 'Short for "Quantity"', BOOKACTI_PLUGIN_NAME ),
				'event_title'	=> __( 'Title', BOOKACTI_PLUGIN_NAME ),
				'start_date'	=> __( 'Start', BOOKACTI_PLUGIN_NAME ),
				'end_date'		=> __( 'End', BOOKACTI_PLUGIN_NAME ),
				'template_title'=> __( 'Calendar', BOOKACTI_PLUGIN_NAME ),
				'activity_title'=> __( 'Activity', BOOKACTI_PLUGIN_NAME ),
				'creation_date'	=> __( 'Date', BOOKACTI_PLUGIN_NAME )
			);

			/**
			 * Columns of the booking list
			 * You must use 'bookacti_booking_list_columns_order' php filter order your custom columns.
			 * You must use 'bookacti_fill_booking_list_entry' jquery hook to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'bookacti_booking_list_columns', $columns );


			// SORT THE COLUMNS
			$columns_order = array(
				10 => 'cb',
				20 => 'id',
				30 => 'customer',
				40 => 'event_title',
				50 => 'start_date',
				60 => 'end_date',
				70 => 'state',
				80 => 'quantity',
				90 => 'template_title',
				100 => 'activity_title',
				110 => 'creation_date'
			);

			/**
			 * Columns order of the booking list
			 * Order the columns given by the filter 'bookacti_booking_list_columns'
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
		
		
		/**
		 * Get default hidden columns
		 * 
		 * @since 1.3.0
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = array(
					'end_date',
					'template_title',
					'activity_title',
					'creation_date'
				);
			}
			return $hidden;
		}
		
		
		/**
		 * Get default hidden columns
		 * 
		 * @since 1.3.0
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'id'				=> array( 'id', true ),
				'customer'			=> array( 'user_id', false ),
				'event_title'		=> array( 'event_id', false ),
				'start_date'		=> array( 'event_start', false ),
				'end_date'			=> array( 'event_end', false ),
				'state'				=> array( 'state', false ),
				'quantity'			=> array( 'quantity', false ),
				'template_title'	=> array( 'template_id', false ),
				'activity_title'	=> array( 'activity_id', false ),
				'creation_date'		=> array( 'creation_date', false )
			);
		}
		
		
		/**
		 * Get the screen property
		 * 
		 * @since 1.3.0
		 * @return WP_Screen
		 */
		private function get_wp_screen() {
		   if( empty( $this->screen ) ) {
			  $this->screen = get_current_screen();
		   }
		   return $this->screen;
		}
		
		
		/**
		 * Prepare the items to be displayed in the list
		 * 
		 * @version 1.3.0
		 */
		public function prepare_items() {
			
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$items = $this->get_booking_list_items();
			
			// Get the number of booking to display per page
			$user			= get_current_user_id();
			$screen			= $this->get_wp_screen();
			$screen_option	= $screen->get_option( 'per_page', 'option' );
			$per_page		= get_user_meta( $user, $screen_option, true );
			if( empty ( $per_page) || $per_page < 1 ) {
				$per_page = $screen->get_option( 'per_page', 'default' );
			}
			
			// Set pagination
			$this->set_pagination_args( array(
				'total_items' => count( $items ),
				'per_page'    => $per_page
			) );
			
			$items = array_slice( $items,( ( $this->get_pagenum() - 1 ) * $per_page ), $per_page );
			
			$this->items = $items;
		}

		
		/**
		 * Fill columns
		 * 
		 * @version 1.3.0
		 * @param array $item
		 * @param string $column_name
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		}

		
		/**
		 * Fill "Customer" column and add action buttons
		 * 
		 * @param array $item
		 * @return string
		 */
		public function column_customer( $item ) {
			
			if( $item[ 'booking_type' ] === 'group' ) {
				$actions = bookacti_get_booking_group_actions_html( $item[ 'id' ], 'admin', true );
			} else {
				$actions = bookacti_get_booking_actions_html( $item[ 'id' ], 'admin', true );
			}
			
			return sprintf( '%1$s %2$s', $item[ 'customer' ], $this->row_actions( $actions, false, $item[ 'booking_type' ] ) );
		}
		
		
		/**
		 * Get booking list items. Parameters can be passed in the URL.
		 * 
		 * @version 1.3.0
		 * @param int $event_group_id
		 * @param int $event_id
		 * @param string $event_start
		 * @param string $event_end
		 * @param int $booking_group_id
		 * @return array
		 */
		public function get_booking_list_items( $filters = array() ) {
			
			// Get filters from URL if no filter was directly passed
			if( ! $filters ) {
				$filters = array(
					'templates'			=> isset( $_REQUEST[ 'calendars' ] )		? explode( ',', $_REQUEST[ 'calendars' ] ) : array(), 
					'activities'		=> isset( $_REQUEST[ 'activities' ] )		? explode( ',', $_REQUEST[ 'activities' ] ) : array(), 
					'booking_group_id'	=> isset( $_REQUEST[ 'booking_group_id' ] )	? intval( $_REQUEST[ 'booking_group_id' ] ): 0, 
					'event_group_id'	=> isset( $_REQUEST[ 'event_group_id' ] )	? intval( $_REQUEST[ 'event_group_id' ] ): 0, 
					'event_id'			=> isset( $_REQUEST[ 'event_id' ] )			? intval( $_REQUEST[ 'event_id' ] ): 0, 
					'event_start'		=> isset( $_REQUEST[ 'event_start' ] )		? bookacti_sanitize_datetime( $_REQUEST[ 'event_start' ] )	: '', 
					'event_end'			=> isset( $_REQUEST[ 'event_end' ] )		? bookacti_sanitize_datetime( $_REQUEST[ 'event_end' ] )	: '',
					'status'			=> isset( $_REQUEST[ 'status' ] )			? explode( ',', $_REQUEST[ 'status' ] )	: array(),
					'order_by'			=> isset( $_REQUEST[ 'orderby' ] )			? explode( ',', $_REQUEST[ 'orderby' ] ) : 'id',
					'order'				=> isset( $_REQUEST[ 'order' ] )			? $_REQUEST[ 'order' ] : 'creation_date'
				);
			}
			
			// Format filters before making the request
			$formatted_filters = bookacti_format_booking_filters( $filters );
			
			// Request bookings corresponding to filters
			$bookings = bookacti_get_bookings( $formatted_filters );
						
			// Retrieve information about users and stock them into an array sorted by user id
			$user_ids = array();
			foreach( $bookings as $booking ) {
				if( ! in_array( $booking->user_id, $user_ids, true ) ){
					$user_ids[] = $booking->user_id;
				}
			}
			$users = bookacti_get_users_data( $user_ids );
			
			// Build booking list
			$booking_list_items = array();
			foreach( $bookings as $booking ) {
				
				// Booking group fields
//				if( $is_group_of_events ) {
//					$quantity = bookacti_get_booking_group_quantity( $booking->id );
//					
//				// Single booking fields
//				} else {
					$quantity = $booking->quantity;
//				}
				
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
					'event_title'	=> apply_filters( 'bookacti_translate_text', $booking->event_title ),
					'start_date'	=> bookacti_format_datetime( $booking->event_start ),
					'end_date'		=> bookacti_format_datetime( $booking->event_end ),
					'template_title'=> apply_filters( 'bookacti_translate_text', $booking->template_title ),
					'activity_title'=> apply_filters( 'bookacti_translate_text', $booking->activity_title ),
					'creation_date'	=> bookacti_format_datetime( $booking->creation_date ),
					'order_id'		=> $booking->order_id,
					'booking_type'	=> 'single',
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
		 * @access public
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
		 * @access public
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

				if ( in_array( $column_name, $hidden, true ) ) {
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
			
			return $out;
	   }
	}
}


/**
 * Set the primary columns
 * 
 * @param string $column
 * @param string $screen
 * @return string
 */
function bookacti_bookings_list_table_primary_column( $column, $screen ) {
	if( $screen === 'booking-activities_page_bookacti_bookings' ) { $column = 'customer'; }
	return $column;
}
add_filter( 'list_table_primary_column', 'bookacti_bookings_list_table_primary_column', 10, 2 );