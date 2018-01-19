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
		public $filters;
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
				'creation_date'	=> __( 'Date', BOOKACTI_PLUGIN_NAME ),
				'actions'		=> __( 'Actions', BOOKACTI_PLUGIN_NAME )
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
				30 => 'state',
				40 => 'customer',
				50 => 'event_title',
				60 => 'start_date',
				70 => 'end_date',
				80 => 'quantity',
				90 => 'template_title',
				100 => 'activity_title',
				110 => 'creation_date',
				120 => 'actions'
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
				$hidden = apply_filters( 'bookacti_booking_list_default_hidden_columns', array(
					'end_date',
					'template_title',
					'activity_title'
				));
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * 
		 * @since 1.3.0
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
		 * @param array $filters
		 * @param boolean $no_pagination
		 */
		public function prepare_items( $filters = array(), $no_pagination = false ) {
			
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$this->filters = $this->format_filters( $filters );
			
			if( ! $no_pagination ) {
				// Get the number of booking to display per page
				$screen			= $this->get_wp_screen();
				$screen_option	= $screen->get_option( 'per_page', 'option' );
				$per_page		= intval( get_user_meta( get_current_user_id(), $screen_option, true ) );
				if( empty ( $per_page ) || $per_page < 1 ) {
					$per_page = $screen->get_option( 'per_page', 'default' );
				}

				// Set pagination
				$this->set_pagination_args( array(
					'total_items' => $this->get_total_items_count(),
					'per_page'    => $per_page
				) );

				$this->filters[ 'offset' ]		= ( $this->get_pagenum() - 1 ) * $per_page;
				$this->filters[ 'per_page' ]	= $per_page;
			}
			
			$items = $this->get_booking_list_items();
			
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
		public function get_booking_list_items() {
			
			// Request bookings corresponding to filters
			$bookings = bookacti_get_bookings( $this->filters );
			
			// Retrieve booking groups data
			$booking_groups		= bookacti_get_booking_groups( $this->filters );
			$displayed_groups	= array();
			
			// Retrieve information about users and stock them into an array sorted by user id
			$user_ids = array();
			foreach( $bookings as $booking ) {
				if( ! in_array( $booking->user_id, $user_ids, true ) ){
					$user_ids[] = $booking->user_id;
				}
			}
			$users = bookacti_get_users_data( array( 'include' => $user_ids ) );
			
			// Build booking list
			$booking_list_items = array();
			foreach( $bookings as $booking ) {
				
				// Display one single row for a booking group, instead of each bookings of the group
				if( $booking->group_id && ! $this->filters[ 'booking_group_id' ] && ! $this->filters[ 'booking_id' ] ) {
					// If the group row has already been displayed, or if it is not found, continue
					if( in_array( $booking->group_id, $displayed_groups, true ) 
					||  ! isset( $booking_groups[ $booking->group_id ] ) ) { continue; }
					
					$group		= $booking_groups[ $booking->group_id ];
					
					$raw_id		= $group->id;
					$tr_class	= 'bookacti-booking-group';
					$id			= $group->id . '<span class="bookacti-booking-group-indicator">' . _x( 'Group', 'noun', BOOKACTI_PLUGIN_NAME ) . '</span>';
					$user_id	= $group->user_id;
					$state		= bookacti_format_booking_state( $group->state, true );
					$title		= $group->group_title;
					$start		= $group->start;
					$end		= $group->end;
					$quantity	= bookacti_get_booking_group_quantity( $group->id );
					$order_id	= $group->order_id;
					$actions	= bookacti_get_booking_group_actions_html( $group->id, 'admin' );
					$activity_title	= '';
					$booking_type	= 'group';
					
					$displayed_groups[] = $booking->group_id;
				
				// Single booking
				} else {
					
					$raw_id		= $booking->id;
					$tr_class	= $booking->group_id ? 'bookacti-single-booking bookacti-gouped-booking' : 'bookacti-single-booking';
					$id			= $booking->group_id ? $booking->id . '<span class="bookacti-booking-group-id" >' . $booking->group_id . '</span>' : $booking->id;
					$user_id	= $booking->user_id;
					$state		= bookacti_format_booking_state( $booking->state, true );
					$title		= $booking->event_title;
					$start		= $booking->event_start;
					$end		= $booking->event_end;
					$quantity	= $booking->quantity;
					$order_id	= $booking->order_id;
					$actions	= bookacti_get_booking_actions_html( $booking->id, 'admin' );
					$activity_title	= $booking->activity_title;
					$booking_type	= 'single';
				}
				
				// Format customer column
				if( is_numeric( $user_id ) ) {
					$user = $users[ $user_id ];
					$customer = '<a '
								. ' href="' . esc_url( get_admin_url() . 'user-edit.php?user_id=' . $user_id ) . '" '
								. ' target="_blank" '
								. ' >'
									. esc_html( $user->user_login . ' (' . $user->user_email . ')' )
							. ' </a>';
				} else {
					$user = new stdClass();
					$customer = esc_html( __( 'Unknown user', BOOKACTI_PLUGIN_NAME ) . ' (' . $user_id . ')' );
				}
				
				$booking_item = apply_filters( 'bookacti_booking_list_booking_columns', array( 
					'tr_class'		=> $tr_class,
					'id'			=> $id,
					'customer'		=> $customer,
					'state'			=> $state,
					'quantity'		=> $quantity,
					'event_title'	=> apply_filters( 'bookacti_translate_text', $title ),
					'start_date'	=> bookacti_format_datetime( $start ),
					'end_date'		=> bookacti_format_datetime( $end ),
					'template_title'=> apply_filters( 'bookacti_translate_text', $booking->template_title ),
					'activity_title'=> apply_filters( 'bookacti_translate_text', $activity_title ),
					/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://codex.wordpress.org/Formatting_Date_and_Time */
					'creation_date'	=> bookacti_format_datetime( $booking->creation_date, __( 'F d, Y', BOOKACTI_PLUGIN_NAME ) ),
					'actions'		=> $actions,
					'order_id'		=> $order_id,
					'booking_type'	=> $booking_type,
					'primary_data'	=> array( 
						'(' . _x( 'id', 'An id is a unique identification number', BOOKACTI_PLUGIN_NAME ) . ': ' . $id . ')', 
						$state, 
						'x' . $quantity 
					)
				), $booking, $user );
				
				// Add info on the primary column to make them directly visible in responsive view
				if( $booking_item[ 'primary_data' ] ) {
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
		 * Format filters passed as argument or retrieved via POST or GET
		 * @param array $filters
		 * @return array
		 */
		public function format_filters( $filters = array() ) {
			
			// Get filters from URL if no filter was directly passed
			if( ! $filters ) {
				
				// Accepts two different parameter names for booking system related paramters
				$event_group_id = 0; $event_id = 0; $event_start = ''; $event_end = '';
				if( isset( $_REQUEST[ 'bookacti_group_id' ] )	&& $_REQUEST[ 'bookacti_group_id' ] !== 'single' )	{ $event_group_id = intval( $_REQUEST[ 'bookacti_group_id' ] ); }
				if( isset( $_REQUEST[ 'event_group_id' ] )		&& $_REQUEST[ 'event_group_id' ] !== 'single' )		{ $event_group_id = intval( $_REQUEST[ 'event_group_id' ] ); }
				if( $event_group_id === 0 ) {
					if( isset( $_REQUEST[ 'bookacti_event_id' ] ) )		{ $event_id		= intval( $_REQUEST[ 'bookacti_event_id' ] ); }
					if( isset( $_REQUEST[ 'event_id' ] ) )				{ $event_id		= intval( $_REQUEST[ 'event_id' ] ); }
					if( isset( $_REQUEST[ 'bookacti_event_start' ] ) )	{ $event_start	= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_start' ] ); }
					if( isset( $_REQUEST[ 'event_start' ] ) )			{ $event_start	= bookacti_sanitize_datetime( $_REQUEST[ 'event_start' ] ); }
					if( isset( $_REQUEST[ 'bookacti_event_end' ] ) )	{ $event_end	= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_end' ] ); }
					if( isset( $_REQUEST[ 'event_end' ] ) )				{ $event_end	= bookacti_sanitize_datetime( $_REQUEST[ 'event_end' ] ); }
				}
				
				$filters = array(
					'templates'			=> isset( $_REQUEST[ 'templates' ] )		? $_REQUEST[ 'templates' ] : array(), 
					'activities'		=> isset( $_REQUEST[ 'activities' ] )		? $_REQUEST[ 'activities' ] : array(), 
					'booking_id'		=> isset( $_REQUEST[ 'booking_id' ] )		? intval( $_REQUEST[ 'booking_id' ] ): 0, 
					'booking_group_id'	=> isset( $_REQUEST[ 'booking_group_id' ] )	? intval( $_REQUEST[ 'booking_group_id' ] ): 0, 
					'event_group_id'	=> $event_group_id, 
					'event_id'			=> $event_id, 
					'event_start'		=> $event_start, 
					'event_end'			=> $event_end,
					'status'			=> isset( $_REQUEST[ 'status' ] )			? $_REQUEST[ 'status' ] : array(),
					'user_id'			=> isset( $_REQUEST[ 'user_id' ] )			? $_REQUEST[ 'user_id' ] : 0,
					'from'				=> isset( $_REQUEST[ 'from' ] )				? $_REQUEST[ 'from' ] : '',
					'to'				=> isset( $_REQUEST[ 'to' ] )				? $_REQUEST[ 'to' ] : '',
					'order_by'			=> isset( $_REQUEST[ 'orderby' ] )			? $_REQUEST[ 'orderby' ] : array( 'creation_date', 'id' ),
					'order'				=> isset( $_REQUEST[ 'order' ] )			? $_REQUEST[ 'order' ] : 'DESC'
				);
			}

			// Format filters before making the request
			$filters = bookacti_format_booking_filters( $filters );
			
			return $filters;
		}
		
		
		/**
		 * 
		 * @return int
		 */
		public function get_total_items_count() {
			$group_by_booking_groups = ! isset( $this->filters[ 'booking_group_id' ] );
			return bookacti_get_number_of_booking_rows( $this->filters, $group_by_booking_groups );
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
		 * @version 1.3.0
		 * @access public
		 * @param array $item The current item
		 */
		public function get_single_row( $item ) {
			$class = $item[ 'tr_class' ] ? $item[ 'tr_class' ] : '';
			$row  = '<tr class="' . $class . '">';
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
		 * Display content for a single row of the table
		 * 
		 * @version 1.3.0
		 * @access public
		 * @param array $item The current item
		 */
		public function single_row( $item ) {
			$class = $item[ 'tr_class' ] ? $item[ 'tr_class' ] : '';
			echo '<tr class="' . $class . '">';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		
		/**
		 * Get default primary column name
		 * 
		 * @since 1.3.0
		 * @return string
		 */
		public function get_default_primary_column_name() {
			return apply_filters( 'bookacti_booking_list_primary_column', 'customer', $this->screen );
		}
	}
}