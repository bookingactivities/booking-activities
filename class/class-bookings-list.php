<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'Bookings_List_Table' ) ) { 
	
	/**
	 * Bookings WP_List_Table
	 * @version 1.12.3
	 */
	class Bookings_List_Table extends WP_List_Table {
		
		public $items;
		public $filters;
		public $user_ids;
		public $booking_ids;
		public $group_ids;
		public $screen;
		public $url;
		
		/**
		 * Set up the Booking list table
		 * @version 1.8.0
		 * @access public
		 */
		public function __construct(){
			// This global variable is required to create screen
			if( ! isset( $GLOBALS[ 'hook_suffix' ] ) ) { $GLOBALS[ 'hook_suffix' ] = null; }
			
			parent::__construct( array(
				/*translator:  */
				'singular'	=> 'booking',	// Singular name of the listed records
				'plural'	=> 'bookings',	// Plural name of the listed records
				'ajax'		=> false,
				'screen'	=> 'booking-activities_page_bookacti_bookings'
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
			
			// Change the current URL when doing AJAX
			add_filter( 'set_url_scheme', array( $this, 'set_bookings_page_url_when_doing_ajax' ), 10, 3 );
		}
		
		
		/**
		 * Get booking list table columns
		 * @version 1.8.0
		 * @access public
		 * @return array
		 */
		public function get_columns() {
			/**
			 * Columns of the booking list
			 * You must use 'bookacti_booking_list_columns_order' php filter to order your custom columns.
			 * You must use 'bookacti_booking_list_default_hidden_columns' php filter to hide your custom columns by default.
			 * You must use 'bookacti_booking_list_booking_columns' php filter to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'bookacti_booking_list_columns', array(
				'cb'			=> '<input type="checkbox" />',
				'id'			=> esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ),
				'customer'		=> esc_html__( 'Customer', 'booking-activities' ),
				'email'			=> esc_html__( 'Email', 'booking-activities' ),
				'phone'			=> esc_html__( 'Phone', 'booking-activities' ),
				'roles'			=> esc_html__( 'Roles', 'booking-activities' ),
				'state'			=> esc_html_x( 'Status', 'Booking status', 'booking-activities' ),
				'payment_status'=> esc_html_x( 'Paid', 'Payment status column name', 'booking-activities' ),
				'quantity'		=> esc_html_x( 'Qty', 'Short for "Quantity"', 'booking-activities' ),
				'event_title'	=> esc_html__( 'Title', 'booking-activities' ),
				'start_date'	=> esc_html__( 'Start', 'booking-activities' ),
				'end_date'		=> esc_html__( 'End', 'booking-activities' ),
				'template_title'=> esc_html__( 'Calendar', 'booking-activities' ),
				'activity_title'=> esc_html__( 'Activity', 'booking-activities' ),
				'creation_date'	=> esc_html__( 'Date', 'booking-activities' ),
				'price_details'	=> esc_html__( 'Price details', 'booking-activities' ),
				'actions'		=> esc_html__( 'Actions', 'booking-activities' )
			));

			
			/**
			 * Columns order of the booking list
			 * Order the columns given by the filter 'bookacti_booking_list_columns'
			 * 
			 * @param array $columns
			 */
			$columns_order = apply_filters( 'bookacti_booking_list_columns_order', array(
				10 => 'cb',
				20 => 'id',
				30 => 'state',
				40 => 'payment_status',
				50 => 'customer',
				52 => 'email',
				54 => 'phone',
				56 => 'roles',
				60 => 'event_title',
				70 => 'start_date',
				80 => 'end_date',
				90 => 'quantity',
				100 => 'template_title',
				110 => 'activity_title',
				120 => 'creation_date',
				130 => 'price_details',
				1000 => 'actions'
			));

			ksort( $columns_order );

			$displayed_columns = array();
			foreach( $columns_order as $column_id ) {
				$displayed_columns[ $column_id ] = $columns[ $column_id ];
			}
			
			return $displayed_columns;
		}
		
		
		/**
		 * Get default hidden columns
		 * @since 1.3.0
		 * @version 1.8.0
		 * @access public
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = apply_filters( 'bookacti_booking_list_default_hidden_columns', array(
					'email',
					'phone',
					'roles',
					'end_date',
					'template_title',
					'activity_title',
					'price_details'
				));
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * @since 1.3.0
		 * @version 1.8.0
		 * @access public
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'id'				=> array( 'id', true ),
				'customer'			=> array( 'user_id', false ),
				'event_title'		=> array( 'event_title', false ),
				'start_date'		=> array( 'event_start', false ),
				'end_date'			=> array( 'event_end', false ),
				'state'				=> array( 'state', false ),
				'payment_status'	=> array( 'payment_status', false ),
				'quantity'			=> array( 'quantity', false ),
				'template_title'	=> array( 'template_id', false ),
				'activity_title'	=> array( 'activity_id', false ),
				'creation_date'		=> array( 'creation_date', false )
			);
		}
		
		
		/**
		 * Prepare the items to be displayed in the list
		 * @version 1.8.0
		 * @access public
		 * @param array $filters
		 * @param boolean $no_pagination
		 */
		public function prepare_items( $filters = array(), $no_pagination = false ) {
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$this->format_filters( $filters, ! empty( $filters[ 'merge_url_parameters' ] ) );
			
			if( ! $no_pagination ) {
				// Get the number of booking to display per page
				$per_page = $this->get_rows_number_per_page();
				
				// Set pagination
				$this->set_pagination_args( array(
					'total_items' => $this->get_total_items_count(),
					'per_page'    => $per_page
				) );

				$this->filters[ 'offset' ] = ( $this->get_pagenum() - 1 ) * $per_page;
				$this->filters[ 'per_page' ] = $per_page;
			}
			
			$this->items = $this->get_booking_list_items();
		}

		
		/**
		 * Fill columns
		 * @version 1.12.3
		 * @access public
		 * @param array $item
		 * @param string $column_name
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			$column_content = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			if( $column_name === $primary_column_name && ! empty( $item[ 'primary_data_html' ] ) ) {
				$column_content .= $item[ 'primary_data_html' ];
			}
			
			return $column_content;
		}
		
		
		/**
		 * Get booking list items. Parameters can be passed in the URL.
		 * @version 1.11.2
		 * @access public
		 * @return array
		 */
		public function get_booking_list_items() {
			// Request bookings corresponding to filters
			if( $this->filters[ 'event_id' ] && ! $this->filters[ 'event_group_id' ] ) { $this->filters[ 'group_by' ] = 'none'; }
			if( ! $this->filters[ 'booking_group_id' ] && $this->filters[ 'group_by' ] !== 'none' ) { $this->filters[ 'group_by' ] = 'booking_group'; }
			
			// Force to fetch meta
			$this->filters[ 'fetch_meta' ] = true;
			
			$bookings = bookacti_get_bookings( $this->filters );
			
			// Check if the booking list can contain groups
			$single_only = $this->filters[ 'group_by' ] === 'none';
			$may_have_groups = false; 
			if( ( ! $this->filters[ 'booking_group_id' ] || in_array( $this->filters[ 'group_by' ], array( 'booking_group', 'none' ), true ) ) && ! $this->filters[ 'booking_id' ] ) {
				$may_have_groups = true;
			}
			
			// Gether all IDs in arrays
			$this->user_ids = array();
			$this->booking_ids = array();
			$this->group_ids = array();
			foreach( $bookings as $booking ) {
				if( $booking->user_id && is_numeric( $booking->user_id ) && ! in_array( $booking->user_id, $this->user_ids, true ) ){ $this->user_ids[] = $booking->user_id; }
				if( $booking->id && ! in_array( $booking->id, $this->booking_ids, true ) ){ $this->booking_ids[] = $booking->id; }
				if( $booking->group_id && ! in_array( $booking->group_id, $this->group_ids, true ) ){ $this->group_ids[] = $booking->group_id; }
			}
			$unknown_user_id = esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
			
			// Retrieve the required groups data only
			$booking_groups		= array();
			$displayed_groups	= array();
			$bookings_per_group	= array();
			if( ( $may_have_groups || $single_only ) && $this->group_ids ) {
				// Get only the groups that will be displayed
				$group_filters = bookacti_format_booking_filters( array( 'in__booking_group_id' => $this->group_ids, 'fetch_meta' => true ) );
				$booking_groups = bookacti_get_booking_groups( $group_filters );
				$groups_bookings = bookacti_get_bookings( $group_filters );
				foreach( $groups_bookings as $booking ) {
					if( ! isset( $bookings_per_group[ $booking->group_id ] ) ) { $bookings_per_group[ $booking->group_id ] = array(); }
					$bookings_per_group[ $booking->group_id ][] = $booking;
				}
			}
			
			// Retrieve information about users and stock them into an array sorted by user id
			$users = bookacti_get_users_data( array( 'include' => $this->user_ids ) );
			$roles_names = bookacti_get_roles();
			$can_edit_users = current_user_can( 'edit_users' );
			
			// Get datetime format
			$datetime_format	= bookacti_get_message( 'date_format_long' );
			$quantity_separator	= bookacti_get_message( 'quantity_separator' );
			
			// Build booking list
			$booking_list_items = array();
			foreach( $bookings as $booking ) {
				$group = $booking->group_id && ! empty( $booking_groups[ $booking->group_id ] ) ? $booking_groups[ $booking->group_id ] : null;
				$grouped_bookings = $booking->group_id && ! empty( $bookings_per_group[ $booking->group_id ] ) && ! $single_only ? $bookings_per_group[ $booking->group_id ] : array( $booking );
		
				// Display one single row for a booking group, instead of each bookings of the group
				if( $booking->group_id && $may_have_groups && ! $single_only ) {
					// If the group row has already been displayed, or if it is not found, continue
					if( isset( $displayed_groups[ $booking->group_id ] ) 
					||  empty( $booking_groups[ $booking->group_id ] ) ) { continue; }
					
					$group_id_link	= '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $group->id . '&group_by=booking_group&event_group_id=' . $group->event_group_id ) . '">' . $group->id . '</a>';
					
					$raw_id			= $group->id;
					$raw_group_id	= $group->id;
					$tr_class		= 'bookacti-booking-group';
					$id				= $group_id_link . '<span class="bookacti-booking-group-indicator">' . esc_html_x( 'Group', 'noun', 'booking-activities' ) . '</span>';
					$user_id		= $group->user_id;
					$state			= bookacti_format_booking_state( $group->state, true );
					$paid			= bookacti_format_payment_status( $group->payment_status, true );
					$title			= $group->group_title;
					$start			= $group->start;
					$end			= $group->end;
					$quantity		= $group->quantity;
					$order_id		= $group->order_id;
					$actions		= bookacti_get_booking_group_actions_by_booking_group( $grouped_bookings, 'admin' );
					$refund_actions	= bookacti_get_booking_refund_actions( $grouped_bookings, 'group', 'admin' );
					$activity_title	= $group->category_title;
					$booking_type	= 'group';
					
					$displayed_groups[ $booking->group_id ] = $booking->id;
					
				// Single booking
				} else {
					$booking_id_link= '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_id=' . $booking->id . '&event_id=' . $booking->event_id . '&event_start=' . $booking->event_start . '&event_end=' . $booking->event_end ) . '">' . $booking->id. '</a>';
					$group_id_link	= '<a href="' . admin_url( 'admin.php?page=bookacti_bookings&booking_group_id=' . $booking->group_id . '&group_by=booking_group&event_group_id=' . ( $group ? $group->event_group_id : '' ) ) . '">' . $booking->group_id . '</a>';
					
					$raw_id			= $booking->id;
					$raw_group_id	= $booking->group_id ? $booking->group_id : 0;
					$tr_class		= $booking->group_id ? 'bookacti-single-booking bookacti-gouped-booking bookacti-booking-group-id-' . $booking->group_id : 'bookacti-single-booking';
					$id				= $booking->group_id ? $booking_id_link . '<span class="bookacti-booking-group-id" >' . $group_id_link . '</span>' : $booking_id_link;
					$user_id		= $booking->user_id;
					$state			= bookacti_format_booking_state( $booking->state, true );
					$paid			= bookacti_format_payment_status( $booking->payment_status, true );
					$title			= $booking->event_title;
					$start			= $booking->event_start;
					$end			= $booking->event_end;
					$quantity		= $booking->quantity;
					$order_id		= $booking->order_id;
					$actions		= bookacti_get_booking_actions_by_booking( $booking, 'admin' );
					$refund_actions	= bookacti_get_booking_refund_actions( array( $booking ), 'single', 'admin' );
					$activity_title	= $booking->activity_title;
					$booking_type	= 'single';
				}
				
				// Remove refund action if not possible
				if( ! empty( $actions[ 'refund' ] ) ) {
					if( $booking->state === 'refunded' || ( ! $may_have_groups && ! empty( $booking->group_id ) ) ) {
						unset( $actions[ 'refund' ] );
					}
				}
				
				// Format creation date
				$creation_date_raw = ! empty( $booking->creation_date ) ? bookacti_sanitize_datetime( $booking->creation_date ) : '';
				/* translators: Datetime format. Must be adapted to each country. Use wp date_i18n documentation to find the appropriated combinaison https://wordpress.org/support/article/formatting-date-and-time/ */
				$creation_date = $creation_date_raw ? bookacti_format_datetime( $creation_date_raw, __( 'F d, Y', BOOKACTI_PLUGIN_NAME ) ) : '';
				$creation_date = $creation_date ? '<span title="' . $booking->creation_date . '">' . $creation_date . '</span>' : '';
				
				// Format customer column
				// If the customer has an account
				if( ! empty( $users[ $user_id ] ) ) {
					$user = $users[ $user_id ];
					$display_name = ! empty( $user->first_name ) && ! empty( $user->last_name ) ? $user->first_name . ' ' . $user->last_name : $user->display_name;
					$customer	= $can_edit_users ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ) . '">' . esc_html( $display_name ) . ' </a>' : esc_html( $display_name );
					$email		= ! empty( $user->user_email ) ? $user->user_email : '';
					$phone		= ! empty( $user->phone ) ? $user->phone : '';
					$roles		= ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : '';
					
				// If the booking was made without account
				} else if( $user_id === $unknown_user_id || is_email( $user_id ) ) {
					$user		= null;
					$customer	= ! empty( $user_id ) ? $user_id : '';
					$booking_meta = $group && $this->filters[ 'group_by' ] !== 'booking_group' ? $group : $booking;
					if( ! empty( $booking_meta->user_first_name ) || ! empty( $booking_meta->user_last_name ) ) {
						$customer = ! empty( $booking_meta->user_first_name ) ? $booking_meta->user_first_name . ' ' : '';
						$customer .= ! empty( $booking_meta->user_last_name ) ? $booking_meta->user_last_name . ' ' : '';
						$customer .= $user_id !== $unknown_user_id ? '<br/>(' . $user_id . ')' : '';
					}
					$email		= ! empty( $booking_meta->user_email ) ? $booking_meta->user_email : '';
					$phone		= ! empty( $booking_meta->user_phone ) ? $booking_meta->user_phone : '';
					$roles		= '';
					
				// Any other cases
				} else {
					$user		= null;
					$customer	= esc_html( __( 'Unknown user', 'booking-activities' ) . ' (' . $user_id . ')' );
					$email		= '';
					$phone		= '';
					$roles		= '';
				}
				
				// Add info on the primary column to make them directly visible in responsive view
				$primary_data = array( 
					'<span class="bookacti-column-id" >(' . esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ) . ': ' . $id . ')</span>', 
					$state, 
					$paid, 
					'<span class="bookacti-column-quantity" >' . $quantity_separator . $quantity . '</span>',
				);
				$primary_data_html = '';
				if( $primary_data ) {
					$primary_data_html = '<div class="bookacti-booking-primary-data-container">';
					foreach( $primary_data as $single_primary_data ) {
						$primary_data_html .= '<span class="bookacti-booking-primary-data">' . $single_primary_data . '</span>';
					}
					$primary_data_html .= '</div>';
				}
				
				/**
				 * Third parties can add or change columns content, do your best to optimize your process
				 */
				$booking_item = apply_filters( 'bookacti_booking_list_booking_columns', array( 
					'tr_class'		=> $tr_class,
					'booking_type'	=> $booking_type,
					'id'			=> $id,
					'raw_id'		=> $raw_id,
					'raw_group_id'	=> $raw_group_id,
					'user_id'		=> $user_id,
					'customer'		=> $customer,
					'email'			=> $email,
					'phone'			=> $phone,
					'roles'			=> $roles,
					'state'			=> $state,
					'payment_status'=> $paid,
					'quantity'		=> $quantity,
					'price_details'	=> array(),
					'event_title'	=> apply_filters( 'bookacti_translate_text', $title ),
					'start_date'	=> bookacti_format_datetime( $start, $datetime_format ),
					'end_date'		=> bookacti_format_datetime( $end, $datetime_format ),
					'template_title'=> apply_filters( 'bookacti_translate_text', $booking->template_title ),
					'activity_title'=> apply_filters( 'bookacti_translate_text', $activity_title ),
					'creation_date'	=> $creation_date,
					'actions'		=> $actions,
					'refund_actions'=> $refund_actions,
					'order_id'		=> $order_id,
					'primary_data'	=> $primary_data,
					'primary_data_html'	=> $primary_data_html
				), $booking, $group, $grouped_bookings, $user, $this );
				
				$booking_list_items[ $booking->id ] = $booking_item;
			}
			
			/**
			 * Third parties can add or change rows and columns, do your best to optimize your process
			 * @since 1.6.0
			 */
			$booking_list_items = apply_filters( 'bookacti_booking_list_items', $booking_list_items, $bookings, $booking_groups, $displayed_groups, $users, $this );
			
			foreach( $booking_list_items as $booking_id => $booking_list_item ) {
				$booking = $booking_list_item[ 'booking_type' ] === 'group' ? $booking_groups[ $booking_list_item[ 'raw_id' ] ] : $bookings[ $booking_list_item[ 'raw_id' ] ];
				
				// Format prices
				$booking_list_items[ $booking_id ][ 'price_details' ] = bookacti_get_booking_price_details_html( $booking_list_item[ 'price_details' ], $booking );
				
				// Format refund actions
				if( empty( $booking_list_item[ 'refund_actions' ] ) && isset( $booking_list_item[ 'actions' ][ 'refund' ] ) ) { unset( $booking_list_item[ 'actions' ][ 'refund' ] ); }
				if( $booking_list_item[ 'booking_type' ] === 'group' ) {
					$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_group_actions_html( $bookings_per_group[ $booking_list_item[ 'raw_id' ] ], 'admin', $booking_list_item[ 'actions' ] ) : '';
				} else if( $booking_list_item[ 'booking_type' ] === 'single' ) {
					$booking_list_items[ $booking_id ][ 'actions' ] = ! empty( $booking_list_item[ 'actions' ] ) ? bookacti_get_booking_actions_html( $booking, 'admin', $booking_list_item[ 'actions' ] ) : '';
				}
			}
			
			return $booking_list_items;
		}
		
		
		/**
		 * Format filters passed as argument or retrieved via POST or GET
		 * @version 1.9.0
		 * @access public
		 * @param array $filters_raw
		 * @param boolean $merge_url_param Merge $filters_raw with URL parameters if not set
		 * @return array
		 */
		public function format_filters( $filters_raw = array(), $merge_url_param = false ) {
			$filters = $filters_raw;
			
			// Get filters from URL if no filter was directly passed
			if( ! $filters_raw || $merge_url_param ) {
				// Get the filters from URL
				$default_filters = bookacti_get_default_booking_filters();
				foreach( $default_filters as $filter_name => $default_value ) {
					if( isset( $_REQUEST[ $filter_name ] ) ) { $filters[ $filter_name ] = $_REQUEST[ $filter_name ]; }
				}
				
				// Specific cases
				$picked_events = ! empty( $_REQUEST[ 'selected_events' ] ) ? bookacti_format_picked_events( $_REQUEST[ 'selected_events' ] ) : array();
				
				if( ! empty( $picked_events[ 0 ][ 'group_id' ] ) )	{ $filters[ 'event_group_id' ] = $picked_events[ 0 ][ 'group_id' ]; }
				else {
					if( ! empty( $picked_events[ 0 ][ 'id' ] ) )	{ $filters[ 'event_id' ] = $picked_events[ 0 ][ 'id' ]; }
					if( ! empty( $picked_events[ 0 ][ 'start' ] ) )	{ $filters[ 'event_start' ] = $picked_events[ 0 ][ 'start' ]; }
					if( ! empty( $picked_events[ 0 ][ 'end' ] ) )	{ $filters[ 'event_end' ] = $picked_events[ 0 ][ 'end' ]; }
				}
				if( ! empty( $_REQUEST[ 'orderby' ] ) )				{ $filters[ 'order_by' ] = $_REQUEST[ 'orderby' ]; }
			}
			
			// Format filters before making the request
			$this->filters = bookacti_format_booking_filters( $filters );
			
			// Define the URL with parameters corresponding to passed filters
			$protocol = is_ssl() ? 'https' : 'http';
			$base_url = defined( 'DOING_AJAX' ) && DOING_AJAX ? admin_url( 'admin.php?page=bookacti_bookings' ) : $protocol . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
			$url_filters = array_intersect_key( $this->filters, $filters );
			$this->url = esc_url_raw( add_query_arg( $url_filters, $base_url ) );
		}
		
		
		/**
		 * Get the total amount of bookings according to filters
		 * @since 1.3.0
		 * @version 1.8.0
		 * @access public
		 * @return int
		 */
		public function get_total_items_count() {
			if( $this->filters[ 'event_id' ] && ! $this->filters[ 'event_group_id' ] ) { $this->filters[ 'group_by' ] = 'none'; }
			if( ! $this->filters[ 'booking_group_id' ] && $this->filters[ 'group_by' ] !== 'none' ) { $this->filters[ 'group_by' ] = 'booking_group'; }
			return bookacti_get_number_of_booking_rows( $this->filters );
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
				return '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">' . esc_html__( 'No items found.', 'booking-activities' ) . '</td></tr>';
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
		 * @version 1.8.6
		 * @access public
		 * @param array $item The current item
		 */
		public function get_single_row( $item ) {
			$class = $item[ 'tr_class' ] ? $item[ 'tr_class' ] : '';
			$tr_data = $item[ 'raw_id' ] ? ' data-booking-id="' . $item[ 'raw_id' ] . '"' : '';
			$tr_data .= $item[ 'raw_group_id' ] ? ' data-booking-group-id="' . $item[ 'raw_group_id' ] . '"' : '';
			
			$row  = '<tr class="' . $class . '" ' . $tr_data . '>';
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
			$tr_data = $item[ 'raw_id' ] ? ' data-booking-id="' . $item[ 'raw_id' ] . '"' : '';
			$tr_data .= $item[ 'raw_group_id' ] ? ' data-booking-group-id="' . $item[ 'raw_group_id' ] . '"' : '';
			
			echo '<tr class="' . $class . '" ' . $tr_data . '>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		
		/**
		 * Get default primary column name
		 * 
		 * @since 1.3.0
		 * @access public
		 * @return string
		 */
		public function get_default_primary_column_name() {
			return apply_filters( 'bookacti_booking_list_primary_column', 'customer', $this->screen );
		}
		
		
		/**
		 * Display pagination inside a form to allow to jump to a page
		 * @since 1.5.0
		 * @version 1.8.0
		 * @param string $which
		 */
		protected function pagination( $which ) {
			if( $which !== 'top' ) { parent::pagination( $which ); return; }
			?>
			<form action='<?php echo add_query_arg( 'paged', '%d', $this->url ); ?>' class='bookacti-list-table-go-to-page-form' >
				<input type='hidden' name='page' value='bookacti_bookings' />
				<?php parent::pagination( $which ); ?>
			</form>
			<?php 
		}
		
		
		/**
		 * Get the number of rows to display per page
		 * @since 1.6.0
		 * @version 1.8.0
		 * @return int
		 */
		public function get_rows_number_per_page() {
			$screen_option_name = $this->screen->get_option( 'per_page', 'option' );
			if( ! $screen_option_name ) { $screen_option_name = 'bookacti_bookings_per_page'; }
			
			$per_page = intval( get_user_meta( get_current_user_id(), $screen_option_name, true ) );
			if( ! $per_page || $per_page < 1 ) {
				$per_page_default = $this->screen->get_option( 'per_page', 'default' );
				$per_page = $per_page_default ? $per_page_default : 10;
			}
			return $per_page;
		}
		
		
		/**
		 * Generate the table navigation above or below the table
		 * @since 1.6.0
		 * @param string $which
		 */
		protected function display_tablenav( $which ) {
			?>
			<div class='tablenav <?php echo esc_attr( $which ); ?>'>
				<?php if ( $this->has_items() ) { ?>
				<div class='alignleft actions bulkactions'>
					<form method='post' class='bookacti-bookings-bulk-action'>
						<input type='hidden' name='page' value='bookacti_bookings' />
						<input type='hidden' name='nonce_bookings_bulk_action' value='<?php echo wp_create_nonce( 'bulk-' . $this->_args[ 'plural' ] ); ?>' />
						<?php $this->bulk_actions( $which ); ?>
					</form>
				</div>
				<?php }
				$this->extra_tablenav( $which );
				$this->pagination( $which );
			?>
				<br class='clear'/>
			</div>
			<?php
		}
		
		
		/**
		 * Displays the table
		 * @since 1.8.0
		 */
		function display() {
			$GLOBALS[ 'bookacti_displaying_booking_list' ] = 1;
			parent::display();
			$GLOBALS[ 'bookacti_displaying_booking_list' ] = 0;
		}
		
		
		/**
		 * Get an associative array ( option_name => option_title ) with the list
		 * of bulk actions available on this table.
		 * @since 1.6.0
		 * @version 1.8.0
		 * @return array
		 */
		protected function get_bulk_actions() {
			return apply_filters( 'bookacti_booking_list_bulk_actions', array() );
		}
		
		
		/**
		 * Process the selected bulk action
		 * @since 1.6.0
		 */
		public function process_bulk_action() {
			if( empty( $_REQUEST[ 'nonce_bookings_bulk_action' ] ) ) { return; }
			
			$action = 'bulk-' . $this->_args[ 'plural' ];
			check_admin_referer( $action );
			
			$action = $this->current_action();
			
			do_action( 'bookacti_booking_list_process_bulk_action', $action );
			
			return;
		}
		
		
		/**
		 * Replace current URL with the bookings page URL when doing AJAX
		 * @since 1.8.0
		 * @param string $url
		 * @param string|null $scheme
		 * @param string|null $orig_scheme
		 * @return string
		 */
		function set_bookings_page_url_when_doing_ajax( $url, $scheme = null, $orig_scheme = null ) {
			// When doing AJAX only
			if( ! defined( 'DOING_AJAX' ) ) { return $url; }
			if( ! DOING_AJAX ) { return $url; }
			
			// When displaying booking list only
			if( empty( $GLOBALS[ 'bookacti_displaying_booking_list' ] ) ) { return $url; }
			
			// If the URL is set
			if( empty( $this->url ) ) { return $url; }
			
			return $this->url;
		}
	}
}