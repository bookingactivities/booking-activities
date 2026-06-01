<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'BOOKACTI_Notifications_List_Table' ) ) { 
	
	/**
	 * Notifications WP_List_Table
	 * @since 1.18.0
	 * @version 1.18.2
	 */
	class BOOKACTI_Notifications_List_Table extends WP_List_Table {
		
		public $items;
		public $filters;
		public $screen;
		public $url;
		
		/**
		 * Set up the list table
		 * @since 1.18.0
		 * @access public
		 */
		public function __construct(){
			// This global variable is required to create screen
			if( ! isset( $GLOBALS[ 'hook_suffix' ] ) ) { $GLOBALS[ 'hook_suffix' ] = null; }
			
			parent::__construct( array(
				/*translator:  */
				'singular' => 'notification',  // Singular name of the listed records
				'plural'   => 'notifications', // Plural name of the listed records
				'ajax'     => false,
				'screen'   => 'booking-activities_page_bookacti_notifications'
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
			
			// Change the current URL when doing AJAX
			add_filter( 'set_url_scheme', array( $this, 'set_notifications_page_url_when_doing_ajax' ), 10, 3 );
		}
		
		
		/**
		 * Get list table columns
		 * @since 1.18.0
		 * @access public
		 * @return array
		 */
		public function get_columns() {
			/**
			 * Columns of the notification list
			 * You must use 'bookacti_notification_list_columns_order' php filter to order your custom columns.
			 * You must use 'bookacti_notification_list_default_hidden_columns' php filter to hide your custom columns by default.
			 * You must use 'bookacti_notification_list_item' php filter to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'bookacti_notification_list_columns', array(
//				'cb'              => '<input type="checkbox"/>',
				'id'              => esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ),
				'title'           => esc_html__( 'Notification', 'booking-activities' ),
				'notification_id' => esc_html__( 'Notification ID', 'booking-activities' ),
				'subject'         => esc_html_x( 'Subject', 'email subject', 'booking-activities' ),
				'target'          => esc_html__( 'Recipient', 'booking-activities' ),
				'update_date'     => esc_html__( 'Updated', 'booking-activities' ),
				'active'          => esc_html__( 'Active', 'booking-activities' )
			));

			
			/**
			 * Columns order of the booking list
			 * Order the columns given by the filter 'bookacti_notification_list_columns'
			 * 
			 * @param array $columns
			 */
			$columns_order = apply_filters( 'bookacti_notification_list_columns_order', array(
//				10 => 'cb',
				20 => 'id',
				30 => 'active',
				40 => 'title',
				50 => 'notification_id',
				60 => 'subject',
				70 => 'target',
				80 => 'update_date'
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
		 * @since 1.18.0
		 * @access public
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = apply_filters( 'bookacti_notification_list_default_hidden_columns', array( 'id', 'notification_id', 'update_date' ) );
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * @since 1.18.0
		 * @access public
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'id'          => array( 'id', false ),
				'title'       => array( 'trigger', false ),
				'target'      => array( 'target', false ),
				'update_date' => array( 'update_date', false ),
				'active'      => array( 'active', false )
			);
		}
		
		
		/**
		 * Prepare the items to be displayed in the list
		 * @since 1.18.0
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
			
			$this->items = $this->get_notification_list_items();
		}

		
		/**
		 * Fill columns
		 * @since 1.18.0
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
		 * Get default primary column name
		 * @since 1.18.0
		 * @access public
		 * @return string
		 */
		public function get_default_primary_column_name() {
			return apply_filters( 'bookacti_notification_list_primary_column', 'title', $this->screen );
		}
		
		
		/**
		 * Fill the checbox column
		 * @since 1.18.0
		 * @access public
		 * @param array $item
		 * @return string
		 */
		public function column_cb( $item ) {
			if( ! current_user_can( 'bookacti_edit_notifications' ) ) { return ''; }
			?>
				<input id='cb-select-<?php echo esc_attr( $item[ 'id' ] ); ?>' type='checkbox' name='notification_db_ids[]' value='<?php echo esc_attr( $item[ 'id' ] ); ?>'/>
				<label for='cb-select-<?php echo esc_attr( $item[ 'id' ] ); ?>'>
					<span class='screen-reader-text'>
						<?php printf( __( 'Select %s' ), esc_html__( 'Notification', 'booking-activities' ) ); ?>
					</span>
				</label>
			<?php
		}
		
		
		/**
		 * Fill "Title" column and add action buttons
		 * @since 1.18.0
		 * @access public
		 * @param array $item
		 * @return string
		 */
		public function column_title( $item ) {
			$notification_id = $item[ 'notification_id' ];
			$actions = array();
			
			if( current_user_can( 'bookacti_edit_notifications' ) ) {
				if( in_array( $item[ 'status' ], array( 'permanent', 'publish' ), true ) ) {
					$actions[ 'edit' ] = '<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_notifications&action=edit&notification_id=' . $notification_id ) ) . '" >' . esc_html__( 'Edit' ) . '</a>';
				}
			}
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			$primary_data_html = '';
			if( $primary_column_name === 'title' && ! empty( $item[ 'primary_data_html' ] ) ) {
				$primary_data_html = $item[ 'primary_data_html' ];
			}
			
			// Add a span and a class to each action
			$actions = apply_filters( 'bookacti_notification_list_row_actions', $actions, $item );
			foreach( $actions as $action_id => $link ) {
				$actions[ $action_id ] = '<span class="' . $action_id . '">' . $link . '</span>';
			}
			
			return sprintf( '%1$s%2$s %3$s', $item[ 'title' ], $primary_data_html, $this->row_actions( $actions, false ) );
		}
		
		
		/**
		 * Get notification list items. Parameters can be passed in the URL.
		 * @since 1.18.0
		 * @version 1.18.2
		 * @access public
		 * @return array
		 */
		public function get_notification_list_items() {
			// Get notifications
			$notifications_data = bookacti_get_notifications_data( false );
			$notifications_raw  = bookacti_get_notifications( $this->filters );
			$notifications      = $notifications_raw ? array_intersect_key( array_replace( $notifications_raw, $notifications_data ), $notifications_raw, $notifications_data ) : array();
			$channel_names      = bookacti_get_notification_channel_names();
			
			$can_edit_notifications = current_user_can( 'bookacti_edit_notifications' );
			$can_edit_users         = current_user_can( 'edit_users' );
			
			// Get author users
			$user_ids = array();
			foreach( $notifications as $notification ) {
				if( $notification[ 'user_id' ] && is_numeric( $notification[ 'user_id' ] ) && ! in_array( $notification[ 'user_id' ], $user_ids, true ) ) { $user_ids[] = $notification[ 'user_id' ]; }
			}
			$users = $user_ids ? bookacti_get_users_data( array( 'include' => $user_ids ) ) : array();
			
			// Get datetime format
			$utc_timezone_obj = new DateTimeZone( 'UTC' );
			$timezone         = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
			try { $timezone_obj = new DateTimeZone( $timezone ); }
			catch ( Exception $ex ) { $timezone_obj = clone $utc_timezone_obj; }
			
			// Build booking list
			$notification_items = array();
			foreach( $notifications as $notification_id => $notification ) {
				// Is active
				$is_active   = $notification[ 'active' ] && $notification[ 'email' ][ 'active' ];
				$active      = '<span class="dashicons dashicons-' . ( $is_active ? 'yes' : 'no' ) . '" title="' . ( $is_active ? esc_html__( 'Yes', 'booking-activities' ) : esc_html__( 'No', 'booking-activities' ) ) . '"></span>';
				
				// Format title column
				$title = ! empty( $notification[ 'title' ] ) ? $notification[ 'title' ] : $notification_id;
				if( $can_edit_notifications && in_array( $notification[ 'status' ], array( 'permanent', 'publish' ), true ) ) {
					$title = '<a class="row-title" href="' . esc_url( admin_url( 'admin.php?page=bookacti_notifications&action=edit&notification_id=' . $notification[ 'id' ] ) ) . '">' . $title . '</a>';
				} else {
					$title = '<span class="row-title">' . $title . '</span>';
				}
				
				// Find a subject
				$subject = '';
				foreach( $channel_names as $channel_name ) {
					if( ! empty( $notification[ $channel_name ][ 'subject' ] ) ) {
						$subject = $notification[ $channel_name ][ 'subject' ];
						break;
					}
				}
				
				// Author name
				$user_object = ! empty( $users[ $notification[ 'user_id' ] ] ) ? $users[ $notification[ 'user_id' ] ] : null;
				$author      = $user_object ? $user_object->display_name : esc_html( __( 'Unknown user', 'booking-activities' ) . ' (' . $notification[ 'user_id' ] . ')' );
				if( $can_edit_users && $user_object ) {
					$author = '<a href="' . get_edit_user_link( $user_object->ID ) . '">' . $author . '</a>';
				}
				
				// Dates
				$creation_date_dt = new DateTime( $notification[ 'creation_date' ], $utc_timezone_obj );
				$update_date_dt   = new DateTime( $notification[ 'update_date' ], $utc_timezone_obj );
				$creation_date_dt->setTimezone( $timezone_obj );
				$update_date_dt->setTimezone( $timezone_obj );
				$creation_date = $notification[ 'creation_date' ] ? bookacti_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ) ) : '';
				$creation_date = $creation_date ? '<span title="' . esc_attr( $notification[ 'creation_date' ] ) . '">' . $creation_date . '</span>' : '';
				$update_date = $notification[ 'update_date' ] ? bookacti_format_datetime( $update_date_dt->format( 'Y-m-d H:i:s' ) ) : '';
				$update_date = $update_date ? '<span title="' . esc_attr( $notification[ 'update_date' ] ) . '">' . $update_date . '</span>' : '';
				
				// Add info on the primary column to make them directly visible in responsive view
				$primary_data = array( 
					'notification_id' => '<span class="bookacti-column-notification_id" >(' . esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ) . ': ' . $notification[ 'id' ] . ' / #' . $notification[ 'db_id' ] . ')</span>', 
					'active'          => $active
				);
				
				$notification_item = apply_filters( 'bookacti_notification_list_item', array( 
					'id'              => $notification[ 'db_id' ],
					'notification_id' => $notification_id,
					'type'            => $notification[ 'target' ] . '_' . $notification[ 'trigger' ],
					'title'           => $title,
					'subject'         => $subject,
					'target'          => $notification[ 'target' ] === 'admin' ? esc_html__( 'Administrator', 'booking-activities' ) : esc_html__( 'Customer', 'booking-activities' ),
					'author'          => $author,
					'creation_date'   => $creation_date,
					'update_date'     => $update_date,
					'status'          => $notification[ 'status' ],
					'active'          => $active,
					'primary_data'    => $primary_data
				), $notification, $this );
				
				$notification_items[ $notification_id ] = $notification_item;
			}
			
			$notification_items = apply_filters( 'bookacti_notification_list_items', $notification_items, $notifications, $this );
			
			// Notification Pack add-on promo
			$is_plugin_active = bookacti_is_plugin_active( 'ba-notification-pack/ba-notification-pack.php' );
			if( ! $is_plugin_active ) {
				$addon_link = '<a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" target="_blank" >Notification Pack</a>';
				$notification_items[ 'customer_scheduled_booking' ] = array(
					'id'              => '',
					'notification_id' => '',
					'type'            => '',
					'title'           => '<div id="bookacti-banp-promo-notification-list-row-title">'
						. '<strong>' . esc_html__( '1 hour before / after a booked event', 'booking-activities' ) . '</strong>' 
						/* translators: %1$s is the placeholder for Notification Pack add-on link */
						. bookacti_help_tip( sprintf( esc_html__( 'You can send automated notifications with the %1$s add-on before or after booked events (you can set the desired delay). This add-on also allows you to send all notifications through SMS and Push.', 'booking-activities' ), $addon_link ), false )
						. '<br/><small>' . esc_html__( 'Set up automated notifications (booking reminders, request a feedback, marketing automation...)', 'booking-activities' ) . '</small>'
						. '<br/><a href="https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=settings-notification-list" class="button" target="_blank" >' . esc_html__( 'Learn more', 'booking-activities' ) . '</a>'
						. '</div>',
					'subject'         => '',
					'target'          => esc_html__( 'Customer', 'booking-activities' ) . ' / ' . esc_html__( 'Administrator', 'booking-activities' ),
					'author'          => '',
					'creation_date'   => '',
					'update_date'     => '',
					'status'          => '',
					'active'          => '<span class="dashicons dashicons-no" title="' . esc_html__( 'No', 'booking-activities' ) . '"></span>',
					'primary_data'    => array()
				);
			}
			
			// Build primary data HTML
			foreach( $notification_items as $i => $notification_item ) {
				if( ! isset( $notification_item[ 'primary_data_html' ] ) && $notification_item[ 'primary_data' ] ) {
					$primary_data_html = '<div class="bookacti-primary-data-container">';
					foreach( $notification_item[ 'primary_data' ] as $single_primary_data_key => $single_primary_data ) {
						$primary_data_html .= '<span class="bookacti-primary-data bookacti-primary-data-' . $single_primary_data_key . '">' . $single_primary_data . '</span>';
					}
					$primary_data_html .= '</div>';
					$notification_items[ $i ][ 'primary_data_html' ] = $primary_data_html;
				}
			}
			
			return $notification_items;
		}
		
		
		/**
		 * Format filters passed as argument or retrieved via POST or GET
		 * @since 1.18.0
		 * @access public
		 * @param array $filters_raw
		 * @param boolean $merge_url_param Merge $filters_raw with URL parameters if not set
		 * @return array
		 */
		public function format_filters( $filters_raw = array(), $merge_url_param = false ) {
			$filters         = $filters_raw;
			$default_filters = bookacti_get_default_notification_filters();
			
			// Get filters from URL if no filter was directly passed
			if( ! $filters_raw || $merge_url_param ) {
				// Get the filters from URL
				foreach( $default_filters as $filter_name => $default_value ) {
					if( isset( $_GET[ $filter_name ] ) ) { $filters[ $filter_name ] = $_GET[ $filter_name ]; }
				}
				
				// Specific cases
				if( ! empty( $_GET[ 'orderby' ] ) ) { $filters[ 'order_by' ] = $_GET[ 'orderby' ]; }
			}
			
			// Format filters before making the request
			$this->filters = bookacti_format_notification_filters( $filters );
			
			if( empty( $this->filters[ 'in__status' ] ) ) {
				$this->filters[ 'in__status' ] = $default_filters[ 'in__status' ];
			}
			
			if( empty( $this->filters[ 'in__notification_type' ] ) ) {
				$this->filters[ 'in__notification_type' ] = $default_filters[ 'in__notification_type' ];
			}
			
			// Define the URL with parameters corresponding to passed filters
			$protocol    = is_ssl() ? 'https' : 'http';
			$base_url    = defined( 'DOING_AJAX' ) && DOING_AJAX ? admin_url( 'admin.php?page=bookacti_notifications' ) : $protocol . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
			$url_filters = array_intersect_key( $this->filters, $filters );
			$this->url   = esc_url_raw( add_query_arg( urlencode_deep( $url_filters ), $base_url ) );
		}
		
		
		/**
		 * Get the total number of notifications according to filters
		 * @since 1.18.0
		 * @access public
		 * @return int
		 */
		public function get_total_items_count() {
			return bookacti_get_number_of_notification_rows( $this->filters );
		}
		
		
		/**
		 * Get the tbody element for the list table.
		 * @since 1.18.0
		 * @access public
		 */
		public function get_rows_or_placeholder() {
			ob_start();
			$this->display_rows_or_placeholder();
			return ob_get_clean();
		}
		
		
		/**
		 * Message to be displayed when there are no items
		 * @since 1.18.0
		 * @version 1.18.1
		 * @access public
		 */
		function no_items() {
			_e( 'No items found.' );
		}
		
		
		/**
		 * Returns content for a single row of the table
		 * @since 1.18.0
		 * @access public
		 * @param array $item The current item
		 */
		public function get_single_row( $item ) {
			ob_start();
			$this->single_row( $item );
			return ob_get_clean();
		}
		
		
		/**
		 * Display content for a single row of the table
		 * @since 1.18.0
		 * @access public
		 * @param array $item The current item
		 */
		public function single_row( $item ) {
			$tr_data = ! empty( $item[ 'id' ] ) ? ' data-notification-db-id="' . $item[ 'id' ] . '"' : '';
			
			echo '<tr ' . $tr_data . '>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}
		
		
		/**
		 * Get an associative array ( id => link ) with the list of views available on this table
		 * @since 1.18.0
		 * @access protected
		 * @return array
		 */
		protected function get_views() {
			$published_current = 'current';
			$trash_current     = '';
			if( isset( $_GET[ 'in__status' ] ) && $_GET[ 'in__status' ] == 'trash' ) { 
				$published_current = '';
				$trash_current     = 'current';
			}
			
			$this->format_filters();
			$published_filter = array_merge( $this->filters, array( 'in__status' => array( 'permanent', 'publish' ) ) );
			$trash_filter     = array_merge( $this->filters, array( 'in__status' => array( 'trash' ) ) );
			
			$published_count = bookacti_get_number_of_notification_rows( $published_filter );
			$trash_count     = bookacti_get_number_of_notification_rows( $trash_filter );
			
			return array(
				'published' => '<a href="' . esc_url( remove_query_arg( array( 'action', 'in__status' ) ) ) . '" class="' . $published_current . '" >' . esc_html__( 'Published' ) . ' <span class="count">(' . $published_count . ')</span></a>',
				'trash'     => '<a href="' . esc_url( add_query_arg( array( 'in__status' => 'trash' ), remove_query_arg( array( 'action' ) ) ) ) . '" class="' . $trash_current . '" >' . esc_html_x( 'Trash', 'noun' ) . ' <span class="count">(' . $trash_count . ')</span></a>'
			);
		}
		
		
		/**
		 * Generate row actions div
		 * @since 1.18.0
		 * @access protected
		 * @param array $actions
		 * @param bool $always_visible
		 * @return string
		 */
		protected function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if( ! $action_count ) { return ''; }

			$class_visible = $always_visible ? 'visible' : '';
			$out = '<div class="row-actions ' . esc_attr( $class_visible ) . '">';
			foreach ( $actions as $action => $link ) {
				++$i;
				$sep = $i == $action_count ? '' : ' | ';
				$out .= $link . $sep;
			}
			$out .= '</div>';

			return $out;
		}
		
		
		/**
		 * Get the number of rows to display per page
		 * @since 1.18.0
		 * @return int
		 */
		public function get_rows_number_per_page() {
			$screen_option  = $this->screen ? $this->screen->get_option( 'per_page', 'option' ) : '';
			$screen_default = $this->screen ? $this->screen->get_option( 'per_page', 'default' ) : 0;
			$option_name    = $screen_option ? $screen_option : 'bookacti_notifications_per_page';
			$option_default = $screen_default && intval( $screen_default ) > 0 ? intval( $screen_default ) : 20;
			$per_page       = $option_name ? $this->get_items_per_page( $option_name, $option_default ) : $option_default;
			
			return $per_page;
		}
		
		
		/**
		 * Displays the table
		 * @since 1.18.0
		 */
		function display() {
			$GLOBALS[ 'bookacti_displaying_notification_list' ] = 1;
			parent::display();
			$GLOBALS[ 'bookacti_displaying_notification_list' ] = 0;
		}
		
		
		/**
		 * Get an associative array ( option_name => option_title ) with the list of bulk actions available on this table.
		 * @since 1.18.0
		 * @return array
		 */
		protected function get_bulk_actions() {
			return apply_filters( 'bookacti_notification_list_bulk_actions', array() );
		}
		
		
		/**
		 * Replace current URL with the notifications page URL when doing AJAX
		 * @since 1.18.0
		 * @param string $url
		 * @param string|null $scheme
		 * @param string|null $orig_scheme
		 * @return string
		 */
		function set_notifications_page_url_when_doing_ajax( $url, $scheme = null, $orig_scheme = null ) {
			// When doing AJAX only
			if( ! defined( 'DOING_AJAX' ) ) { return $url; }
			if( ! DOING_AJAX ) { return $url; }
			
			// When displaying booking list only
			if( empty( $GLOBALS[ 'bookacti_displaying_notification_list' ] ) ) { return $url; }
			
			// If the URL is set
			if( empty( $this->url ) ) { return $url; }
			
			return $this->url;
		}
		
		
		/**
		 * Gets a list of CSS classes for the WP_List_Table table tag.
		 * @since 1.18.0
		 * @return string[] Array of CSS classes for the table tag.
		 */
		protected function get_table_classes() {
			$classes = parent::get_table_classes();
			$classes[] = 'bookacti-list-table';
			return $classes;
		}
	}
}