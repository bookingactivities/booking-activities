<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'Forms_List_Table' ) ) { 
	
	/**
	 * Forms WP_List_Table
	 * @since 1.5.0
	 * @version 1.15.5
	 */
	class Forms_List_Table extends WP_List_Table {
		
		public $items;
		public $filters;
		protected $screen;
		
		/**
		 * Set up the Form list table
		 * @access public
		 */
		public function __construct(){
			// This global variable is required to create screen
			if( ! isset( $GLOBALS[ 'hook_suffix' ] ) ) { $GLOBALS[ 'hook_suffix' ] = null; }
			
			parent::__construct( array(
				/*translator:  */
				'singular'	=> 'form',	// Singular name of the listed records
				'plural'	=> 'forms',	// Plural name of the listed records
				'ajax'		=> false,
				'screen'	=> null
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
		}
		
		
		/**
		 * Get form list table columns
		 * @version 1.15.5
		 * @access public
		 * @return array
		 */
		public function get_columns(){
			// Set the columns
			$columns = array(
//				'cb'		=> '<input type="checkbox" />',
				'id'		=> _x( 'id', 'An id is a unique identification number', 'booking-activities' ),
				'title'		=> __( 'Title', 'booking-activities' ),
				'author'	=> __( 'Author', 'booking-activities' ),
				'date'		=> __( 'Date', 'booking-activities' ),
				'status'	=> _x( 'Status', 'Form status', 'booking-activities' ),
				'shortcode'	=> __( 'Shortcode', 'booking-activities' ),
				'active'	=> __( 'Active', 'booking-activities' )
			);

			/**
			 * Columns of the form list
			 * You must use 'bookacti_form_list_columns_order' php filter to order your custom columns.
			 * You must use 'bookacti_form_list_default_hidden_columns' php filter to hide your custom columns by default.
			 * You must use 'bookacti_form_list_form_columns' php filter to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'bookacti_form_list_columns', $columns );


			// Sort the columns
			$columns_order = array(
//				10 => 'cb',
				20 => 'id',
				30 => 'title',
				40 => 'shortcode',
				50 => 'author',
				60 => 'date',
				70 => 'status',
				80 => 'active'
			);

			/**
			 * Columns order of the form list
			 * Order the columns given by the filter 'bookacti_form_list_columns'
			 * 
			 * @param array $columns
			 */
			$columns_order = apply_filters( 'bookacti_form_list_columns_order', $columns_order );

			ksort( $columns_order );

			$displayed_columns = array();
			foreach( $columns_order as $column_id ) {
				$displayed_columns[ $column_id ] = $columns[ $column_id ];
			}

			// Return the columns
			return $displayed_columns;
		}
		
		
		/**
		 * Get default hidden columns
		 * @access public
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = apply_filters( 'bookacti_form_list_default_hidden_columns', array(
					'status',
					'active'
				) );
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * @access public
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'id'	=> array( 'id', true ),
				'title'	=> array( 'title', false ),
				'author'=> array( 'user_id', false ),
				'date'	=> array( 'creation_date', false ),
				'status'=> array( 'status', false )
			);
		}
		
		
		/**
		 * Get the screen property
		 * @access public
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
		 * @access public
		 * @param array $filters
		 * @param boolean $no_pagination
		 */
		public function prepare_items( $filters = array(), $no_pagination = false ) {
			
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$this->filters = $this->format_filters( $filters );
			
			if( ! $no_pagination ) {
				// Get the number of forms to display per page
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
			
			$items = $this->get_form_list_items();
			
			$this->items = $items;
		}

		
		/**
		 * Fill columns
		 * @version 1.7.7
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
		 * Fill "Title" column and add action buttons
		 * @version 1.15.4
		 * @access public
		 * @param array $item
		 * @return string
		 */
		public function column_title( $item ) {
			$form_id	= $item[ 'id' ];
			$actions	= array();
			
			if( current_user_can( 'bookacti_edit_forms' ) ) {
				
				// Add the 'edit' and the 'duplicate' actions
				if( $item[ 'active_raw' ] ) {
					$actions[ 'edit' ]	= '<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id ) ) . '" >'
											. esc_html__( 'Edit' )
										. '</a>';
					$actions[ 'duplicate' ]	= '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&action=duplicate&form_id=' . $form_id ), 'duplicate-form_' . $form_id ) ) . '" >'
												. esc_html__( 'Duplicate', 'booking-activities' )
											. '</a>';
				}
				
				if( current_user_can( 'bookacti_delete_forms' ) ) {
					if( $item[ 'active_raw' ] ) {
						// Add the 'trash' action
						$actions[ 'trash' ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&action=trash&form_id=' . $form_id ), 'trash-form_' . $form_id ) ) . '" >'
												. esc_html_x( 'Trash', 'verb' )
											. '</a>';
					} else {
						// Add the 'restore' action
						$actions[ 'restore' ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&action=restore&form_id=' . $form_id ), 'restore-form_' . $form_id ) ) . '" >'
												. esc_html__( 'Restore' )
											. '</a>';
						// Add the 'delete' action
						$actions[ 'delete' ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&status=trash&action=delete&form_id=' . $form_id ), 'delete-form_' . $form_id ) ) . '" >'
												. esc_html__( 'Delete Permanently' )
											. '</a>';
					}
				}
			}
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			$primary_data_html = '';
			if( $primary_column_name === 'title' && ! empty( $item[ 'primary_data_html' ] ) ) {
				$primary_data_html = $item[ 'primary_data_html' ];
			}
			
			// Add a span and a class to each action
			$actions = apply_filters( 'bookacti_form_list_row_actions', $actions, $item );
			foreach( $actions as $action_id => $link ) {
				$actions[ $action_id ] = '<span class="' . $action_id . '">' . $link . '</span>';
			}
			
			return sprintf( '%1$s%2$s %3$s', $item[ 'title' ], $primary_data_html, $this->row_actions( $actions, false ) );
		}
		
		
		/**
		 * Get form list items. Parameters can be passed in the URL.
		 * @version 1.15.5
		 * @access public
		 * @return array
		 */
		public function get_form_list_items() {
			$forms = bookacti_get_forms( $this->filters );
			$can_edit_forms = current_user_can( 'bookacti_edit_forms' );
			
			$date_format = get_option( 'date_format' );
			$utc_timezone_obj = new DateTimeZone( 'UTC' );
			$timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
			try { $timezone_obj = new DateTimeZone( $timezone ); }
			catch ( Exception $ex ) { $timezone_obj = clone $utc_timezone_obj; }
			
			// Form list
			$form_list_items = array();
			foreach( $forms as $form ) {
				// If the user is not allowed to manage this form, do not display it at all
				if( ! bookacti_user_can_manage_form( $form->id ) ) { continue; }
				
				$id     = $form->id;
				$active = $form->active ? __( 'Yes', 'booking-activities' ) : __( 'No', 'booking-activities' );
				
				// Format title column
				$title = ! empty( $form->title ) ? esc_html( apply_filters( 'bookacti_translate_text', $form->title ) ) : sprintf( esc_html__( 'Form #%d', 'booking-activities' ), $id );
				if( $can_edit_forms ) {
					$title = '<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $id ) ) . '" >' . $title . '</a>';
				}
				
				// Build shortcode
				$shortcode = "<input type='text' onfocus='this.select();' readonly='readonly' value='" . esc_attr( '[bookingactivities_form form="' . $id . '"]' ) . "' class='large-text code'>";
				
				// Author name
				$user_object = get_user_by( 'id', $form->user_id );
				$author = $user_object ? $user_object->display_name : $form->user_id;
				
				// Creation date
				$creation_date_raw = ! empty( $form->creation_date ) ? bookacti_sanitize_datetime( $form->creation_date ) : '';
				$creation_date_dt = new DateTime( $creation_date_raw, $utc_timezone_obj );
				$creation_date_dt->setTimezone( $timezone_obj );
				$creation_date = $creation_date_raw ? bookacti_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
				$creation_date = $creation_date ? '<span title="' . esc_attr( $form->creation_date ) . '">' . $creation_date . '</span>' : '';
				
				// Add info on the primary column to make them directly visible in responsive view
				$primary_data = array( '<span class="bookacti-column-id" >(' . esc_html_x( 'id', 'An id is a unique identification number', 'booking-activities' ) . ': ' . $id . ')</span>' );
				$primary_data_html = '<div class="bookacti-primary-data-container">';
				foreach( $primary_data as $single_primary_data ) {
					$primary_data_html .= '<span class="bookacti-primary-data">' . $single_primary_data . '</span>';
				}
				$primary_data_html .= '</div>';
				
				$form_item = apply_filters( 'bookacti_form_list_form_columns', array( 
					'id'                => $id,
					'title'             => $title,
					'shortcode'         => $shortcode,
					'author'            => $author,
					'date'              => $creation_date,
					'status'            => $form->status,
					'active'            => $active,
					'active_raw'        => $form->active,
					'primary_data'      => $primary_data,
					'primary_data_html' => $primary_data_html
				), $form );
				
				$form_list_items[] = $form_item;
			}
			
			return $form_list_items;
		}
		
		
		/**
		 * Format filters passed as argument or retrieved via POST or GET
		 * @version 1.5.7
		 * @access public
		 * @param array $filters
		 * @return array
		 */
		public function format_filters( $filters = array() ) {
			
			// Get filters from URL if no filter was directly passed
			if( ! $filters ) {
				
				$active = false;
				if( isset( $_REQUEST[ 'active' ] ) ) { $active = ! $_REQUEST[ 'active' ] ? 0 : 1; }
				
				$filters = array(
					'id'		=> isset( $_REQUEST[ 'id' ] )		? $_REQUEST[ 'id' ] : array(), 
					'title'		=> isset( $_REQUEST[ 'title' ] )	? $_REQUEST[ 'title' ] : '', 
					'user_id'	=> isset( $_REQUEST[ 'user_id' ] )	? $_REQUEST[ 'user_id' ] : 0, 
					'status'	=> isset( $_REQUEST[ 'status' ] )	? $_REQUEST[ 'status' ] : '', 
					'active'	=> $active, 
					'order_by'	=> isset( $_REQUEST[ 'orderby' ] )	? $_REQUEST[ 'orderby' ] : array( 'id' ),
					'order'		=> isset( $_REQUEST[ 'order' ] )	? $_REQUEST[ 'order' ] : 'DESC'
				);
			}
			
			// Format filters before making the request
			$filters = bookacti_format_form_filters( $filters );
			
			return $filters;
		}
		
		
		/**
		 * Get the total amount of forms according to filters
		 * @access public
		 * @return int
		 */
		public function get_total_items_count() {
			return bookacti_get_number_of_form_rows( $this->filters );
		}
		
		
		/**
		 * Get the tbody element for the list table
		 * @access public
		 * @return string
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
		 * @access public
		 * @return string
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
		 * @access public
		 * @param array $item The current item
		 * @return string
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
		 * Display content for a single row of the table
		 * 
		 * @access public
		 * @param array $item The current item
		 */
		public function single_row( $item ) {
			echo '<tr>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		
		/**
		 * Get an associative array ( id => link ) with the list of views available on this table
		 * @version 1.15.4
		 * @return array
		 */
		protected function get_views() {
			$published_current	= 'current';
			$trash_current		= '';
			if( isset( $_REQUEST[ 'status' ] ) && $_REQUEST[ 'status' ] === 'trash' ) { 
				$published_current	= '';
				$trash_current		= 'current';
			}
			
			$filters			= bookacti_format_form_filters();
			$published_filter	= $filters; $published_filter[ 'status' ] = array( 'publish' );
			$trash_filter		= $filters; $trash_filter[ 'status' ] = array( 'trash' );
			
			$published_count	= bookacti_get_number_of_form_rows( $published_filter );
			$trash_count		= bookacti_get_number_of_form_rows( $trash_filter );
			
			return array(
				'published'	=> '<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_forms' ) ) . '" class="' . $published_current . '" >' . esc_html__( 'Published' ) . ' <span class="count">(' . $published_count . ')</span></a>',
				'trash'		=> '<a href="' . esc_url( admin_url( 'admin.php?page=bookacti_forms&status=trash' ) ) . '" class="' . $trash_current . '" >' . esc_html_x( 'Trash', 'noun' ) . ' <span class="count">(' . $trash_count . ')</span></a>'
			);
		}
		
		
		/**
		 * Generate row actions div
		 * @access protected
		 * @param array $actions
		 * @param bool $always_visible
		 * @return string
		 */
		protected function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if( ! $action_count ) { return ''; }

			$class_visible		= $always_visible ? 'visible' : '';
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
		 * Get default primary column name
		 * 
		 * @access public
		 * @return string
		 */
		public function get_default_primary_column_name() {
			return apply_filters( 'bookacti_form_list_primary_column', 'title', $this->screen );
		}
		
		
		/**
		 * Display pagination inside a form to allow to jump to a page
		 * @version 1.5.4
		 * @param string $which
		 */
		protected function pagination( $which ) {
			if( $which !== 'top' ) { parent::pagination( $which ); return; }
			?>
			<form action='<?php echo esc_url( add_query_arg( 'paged', '%d' ) ); ?>' class='bookacti-list-table-go-to-page-form' >
				<input type='hidden' name='page' value='bookacti_forms' />
				<?php parent::pagination( $which ); ?>
			</form>
			<?php 
		}
		
		
		/**
		 * Gets a list of CSS classes for the WP_List_Table table tag.
		 * @since 1.15.5
		 * @return string[] Array of CSS classes for the table tag.
		 */
		protected function get_table_classes() {
			$classes = parent::get_table_classes();
			$classes[] = 'bookacti-list-table';
			return $classes;
		}
	}
}