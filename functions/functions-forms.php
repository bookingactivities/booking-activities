<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// FORM LIST

/**
 * Format form filters
 * @since 1.5.0
 * @param array $filters 
 * @return array
 */
function bookacti_format_form_filters( $filters = array() ) {

	$default_filters = apply_filters( 'bookacti_default_booking_filters', array(
		'id'			=> array(), 
		'title'			=> '', 
		'status'		=> array(), 
		'user_id'		=> 0, 
		'active'		=> false,
		'order_by'		=> array( 'id' ), 
		'order'			=> 'desc',
		'offset'		=> 0,
		'per_page'		=> 0
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
		
		} else if( in_array( $filter, array( 'id' ), true ) ) {
			if( is_numeric( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( $i = array_search( 'all', $current_value ) !== false ) { unset( $current_value[ $i ] ); }
		
		} else if( in_array( $filter, array( 'title' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'status' ), true ) ) {
			if( is_string( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else { 
				$i = array_search( 'all', $current_value );
				if( $i !== false ) { unset( $current_value[ $i ] ); }
			}
			
			
		} else if( in_array( $filter, array( 'active' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )	{ $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) ){ $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 
				'id', 
				'title'
			);
			if( is_string( $current_value ) )	{ 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) )				{ $current_value = $default_value; }
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'user_id', 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ){ $current_value = $default_value; }

		}
		
		$formatted_filters[ $filter ] = $current_value;
	}
	
	return $formatted_filters;
}




// FORM EDITOR - FORM

/**
 * Get form data and metadata
 * @since 1.5.0
 * @param int $form_id
 * @return array
 */
function bookacti_get_form_data( $form_id ) {
	
	$form = bookacti_get_form( $form_id );
	
	if( ! $form ) { return array(); }
	
	// Add form metadata
	$form_meta = bookacti_get_metadata( 'form', $form_id );
	if( is_array( $form_meta ) ) { 
		$form = array_merge( $form, $form_meta );
	}

	// Format data
	$form = bookacti_format_form_data( $form );
	
	return apply_filters( 'bookacti_form_data', $form, $form_id );
}


/**
 * Get default form data
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_form_data() {
	return apply_filters( 'bookacti_default_form_data', array( 
		'form_id' => 0,			// Form ID
		'title' => '',			// Form title displayed in form list and form editor
		'user_id' => -1,			// Author user ID
		'creation_date' => '',	// Datetime when the form was created
		'status' => '',			// Form status
		'active' => -1			// If the form is active
	));
}


/**
 * Get default form meta
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_form_meta() {
	return apply_filters( 'bookacti_default_form_meta', array(
		'id' => '',				// Form's id
		'class' => '',			// Form's classes
		'redirect_url' => '',	// URL to redirect to when the form is submitted
	));
}


/**
 * Format form data
 * @since 1.5.0
 * @param array|string $raw_form_data
 * @return array|false
 */
function bookacti_format_form_data( $raw_form_data = array() ) {
	
	// Check if name and type are set
	if( ! is_array( $raw_form_data ) ) { return false; }
	
	$default_data	= bookacti_get_default_form_data();
	$default_meta	= bookacti_get_default_form_meta();
	
	if( ! $default_data ) { return false; }
	
	$form_data	= array();
	$form_meta	= array();
	
	// Format meta values
	$keys_by_type = array( 
		'str_id'	=> array( 'id' ),
		'str'		=> array( 'class', 'redirect_url' )
	);
	$form_meta = bookacti_sanitize_values( $default_meta, $raw_form_data, $keys_by_type, $form_meta );
	
	// Exception: Keep field_order and format it
	if( isset( $raw_form_data[ 'field_order' ] ) ) { $form_meta[ 'field_order' ] = maybe_unserialize( $raw_form_data[ 'field_order' ] ); }
	
	// Format common values
	$keys_by_type = array( 
		'int'		=> array( 'form_id', 'user_id' ),
		'str_id'	=> array( 'status' ),
		'str'		=> array( 'title' ),
		'datetime'	=> array( 'creation_date' ),
		'bool'		=> array( 'active' )
	);
	$form_data = bookacti_sanitize_values( $default_data, $raw_form_data, $keys_by_type, $form_data );
	
	// Merge common data and metadata
	$form_data = array_merge( $form_data, $form_meta );
	
	return apply_filters( 'bookacti_formatted_form_data', $form_data, $raw_form_data );
}


/**
 * Sanitize form data
 * @since 1.5.0
 * @param array|string $raw_form_data
 * @return array|false
 */
function bookacti_sanitize_form_data( $raw_form_data ) {
	
	// Check if name and type are set
	if( ! is_array( $raw_form_data ) ) { return false; }
	
	$default_data	= bookacti_get_default_form_data();
	$default_meta	= bookacti_get_default_form_meta();
	
	if( ! $default_data ) { return false; }
	
	$form_data	= array();
	$form_meta	= array();
	
	// Sanitize meta values
	$keys_by_type = array( 
		'str_id'	=> array( 'id' ),
		'str'		=> array( 'class', 'redirect_url' )
	);
	$form_meta = bookacti_sanitize_values( $default_meta, $raw_form_data, $keys_by_type, $form_meta );
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'		=> array( 'form_id', 'user_id' ),
		'str_id'	=> array( 'status' ),
		'datetime'	=> array( 'creation_date' ),
		'bool'		=> array( 'active' )
	);
	$form_data = bookacti_sanitize_values( $default_data, $raw_form_data, $keys_by_type, $form_data );
	
	// Merge common data and metadata
	$form_data = array_merge( $form_data, $form_meta );
	
	return apply_filters( 'bookacti_sanitized_form_data', $form_data, $raw_form_data );
}


// FORM EDITOR - FORM FIELDS

/**
 * Get form fields array
 * @since 1.5.0
 * @param int $form_id
 * @return array
 */
function bookacti_get_form_fields_data( $form_id ) {
	
	$fields = bookacti_get_form_fields( $form_id );
	
	if( ! $fields ) { return array(); }
	
	// Add form field metadata and 
	// Format form fields
	foreach( $fields as $i => $field ) {
		// Add field-specific data
		$field_metadata = bookacti_get_metadata( 'form_field', $field[ 'field_id' ] );
		if( is_array( $field_metadata ) ) { 
			$field = array_merge( $field, $field_metadata );
		}
		
		// Format data
		$fields[ $i ] = bookacti_format_form_field_data( $field );
	}
	
	return apply_filters( 'bookacti_form_fields_data', $fields, $form_id );
}

/**
 * Get all field data as an array
 * @since 1.5.0
 * @param int $field_id
 * @return array
 */
function bookacti_get_form_field_data( $field_id ) {
	
	$field = bookacti_get_form_field( $field_id );
	
	if( ! $field ) { return array(); }
	
	// Add form field metadata and
	$field_metadata = bookacti_get_metadata( 'form_field', $field[ 'field_id' ] );
	if( is_array( $field_metadata ) ) { 
		$field = array_merge( $field, $field_metadata );
	}
		
	// Format data
	$field = bookacti_format_form_field_data( $field );
	
	return apply_filters( 'bookacti_form_field', $field );
}


/**
 * Get fields data
 * @see bookacti_format_form_field_data to properly format your array
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_form_fields_data( $field_name = '' ) {
	
	// Set the common default data
	$default_data = array( 
		'field_id' => 0,		// Field ID
		'name' => '',			// Text identifier of the field
		'type' => '',			// Field type [calendar, quantity, submit, login, free_text, or you custom types]
		'title' => '',			// Field title display in form editor
		'label' => '',			// Text displayed for the field in frontend
		'id' => '',				// Field's id
		'class' => '',			// Field's classes
		'options' => array(),	// Array of allowed values
		'value' => '',			// Default value among the allowed values
		'placeholder' => '',	// Text displayed in transparency when the field is empty
		'tip' => '',			// Help text displayed in a tooltip next to the field
		'required' => 0,		// Whether the customer is forced to fill this field when it is displayed
		'compulsory' => 0,		// Whether the field cannot be deleted
		'default' => 0,			// Whether the field is set by default (if compulsory, it is by default too)
		'unique' => 1			// Whether the user can add multiple occurence of this field in the form
	);
	
	// Add register fields default
	$register_fields	= bookacti_get_default_register_fields_data();
	$register_defaults	= array( 'label' => array(), 'placeholder' => array(), 'tip' => array() );
	foreach( $register_fields as $register_field_name => $register_field ) {
		$register_defaults[ 'label' ][ $register_field_name ]		= ! empty( $register_field[ 'label' ] )			? $register_field[ 'label' ] : '';
		$register_defaults[ 'placeholder' ][ $register_field_name ]	= ! empty( $register_field[ 'placeholder' ] )	? $register_field[ 'placeholder' ] : '';
		$register_defaults[ 'tip' ][ $register_field_name ]			= ! empty( $register_field[ 'tip' ] )			? $register_field[ 'tip' ] : '';
	}
	
	// Set field-speicific default data
	$fields_data = array(
		'calendar' => array( 
			'name'			=> 'calendar',
			'type'			=> 'calendar',
			'title'			=> __( 'Calendar', BOOKACTI_PLUGIN_NAME ),
			'compulsory'	=> 1,
			'default'		=> 1
		),
		'login' => array( 
			'name'			=> 'login',
			'type'			=> 'login',
			'title'			=> __( 'Login / Register', BOOKACTI_PLUGIN_NAME ),
			'default'		=> 1,
			'label'			=> array_merge( array( 
								'email'			=> __( 'Email', BOOKACTI_PLUGIN_NAME ), 
								'password'		=> __( 'Password', BOOKACTI_PLUGIN_NAME ), 
								'register'		=> __( 'Create a new account', BOOKACTI_PLUGIN_NAME )
							), $register_defaults[ 'label' ] ),
			'placeholder'	=> array_merge( array( 'email' => '', 'password' => '', 'register' => '' ), $register_defaults[ 'placeholder' ] ),
			'tip'			=> array_merge( array( 'email' => '', 'password' => '', 'register' => '' ), $register_defaults[ 'tip' ] )
		),
		'free_text' => array( 
			'name'			=> 'free_text',
			'type'			=> 'free_text',
			'title'			=> __( 'Free text', BOOKACTI_PLUGIN_NAME ),
			'unique' 		=> 0
		),
		'quantity' => array( 
			'name'		=> 'quantity',
			'type'		=> 'quantity',
			'title'		=> __( 'Quantity', BOOKACTI_PLUGIN_NAME ),
			'label'		=> __( 'Quantity', BOOKACTI_PLUGIN_NAME ),
			'required'	=> 1,
			'default'	=> 1
		),
		'submit' => array( 
			'name'		=> 'submit',
			'type'		=> 'submit',
			'title'		=> __( 'Submit button', BOOKACTI_PLUGIN_NAME ),
			'value'		=> __( 'Book', BOOKACTI_PLUGIN_NAME ),
			'default'	=> 1
		)
	);
	
	// Merge field-specific data to common data
	foreach( $fields_data as $i => $field_data ) {
		$fields_data[ $i ] = array_merge( $default_data, $field_data );
	}
	
	$fields_data = apply_filters( 'bookacti_default_form_fields_data', $fields_data );
		
	if( $field_name ) {
		return isset( $fields_data[ $field_name ] ) ? $fields_data[ $field_name ] : array();
	}
	
	return $fields_data;
}


/**
 * Get fields metadata
 * @see bookacti_format_form_field_data to properly format your array
 * @since 1.5.0
 * @param string $field_name
 * @return array
 */
function bookacti_get_default_form_fields_meta( $field_name = '' ) {
	
	// Calendar default meta
	$booking_system_meta = bookacti_get_booking_system_default_attributes();
	unset( $booking_system_meta[ 'template_data' ] );
	unset( $booking_system_meta[ 'auto_load' ] );
	unset( $booking_system_meta[ 'check_roles' ] );
	
	$template_meta = bookacti_format_template_settings( array() );
	unset( $template_meta[ 'snapDuration' ] );
	
	$calendar_meta = array_merge( $booking_system_meta, $template_meta, array(
		'start' => '',
		'end' => '',
		'past_events_bookable' => 0
	) );
	
	// Add register fields default meta to login field meta
	$register_fields	= bookacti_get_default_register_fields_data();
	$register_defaults	= array( 'displayed' => array(), 'required' => array() );
	foreach( $register_fields as $register_field_name => $register_field ) {
		$register_defaults[ 'displayed' ][ $register_field_name ]	= ! empty( $register_field[ 'displayed' ] )	? $register_field[ 'displayed' ] : 1;
		$register_defaults[ 'required' ][ $register_field_name ]	= ! empty( $register_field[ 'required' ] )	? $register_field[ 'required' ] : 0;
	}
	
	
	$fields_meta = apply_filters( 'bookacti_default_form_fields_meta', array(
		'calendar'	=> $calendar_meta,
		'login'		=> array(
			'generate_password'			=> 0,
			'send_new_account_email'	=> 1,
			'displayed_fields'			=> array_merge( array( 'email' => 1, 'password' => 1, 'register' => 1 ), $register_defaults[ 'displayed' ] ),
			'required_fields'			=> array_merge( array( 'email' => 1, 'password' => 1, 'register' => 0 ), $register_defaults[ 'required' ] )
		),
		'free_text'	=> array(),
		'quantity'	=> array(),
		'submit'	=> array()
	));
	
	if( $field_name ) {
		return isset( $fields_meta[ $field_name ] ) ? $fields_meta[ $field_name ] : array();
	}
	
	return $fields_meta;
}


/**
 * Format field data according to its type
 * @since 1.5.0
 * @param array|string $raw_field_data
 * @return array|false
 */
function bookacti_format_form_field_data( $raw_field_data ) {

	// Check if name and type are set
	if( ! is_array( $raw_field_data ) || empty( $raw_field_data[ 'name' ] ) || empty( $raw_field_data[ 'type' ] ) ) { return false; }
	
	$default_data	= bookacti_get_default_form_fields_data( $raw_field_data[ 'name' ] );
	$default_meta	= bookacti_get_default_form_fields_meta( $raw_field_data[ 'name' ] );

	if( ! $default_data ) { return false; }
	
	$field_data	= array();
	$field_meta	= array();
	
	// Format field-specific data and metadata
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		// Build template_data array
		$raw_field_data[ 'template_data' ] = array(
			'start'		=> isset( $raw_field_data[ 'start' ] ) ? $raw_field_data[ 'start' ] : $default_meta[ 'start' ],
			'end'		=> isset( $raw_field_data[ 'end' ] ) ? $raw_field_data[ 'end' ] : $default_meta[ 'end' ],
			'settings'	=> array_intersect_key( $raw_field_data, bookacti_format_template_settings( array() ) )
		);
		
		// Format booking system data
		$field_meta = bookacti_format_booking_system_attributes( $raw_field_data );
		
		// Keep some meta unformatted
		$field_meta[ 'raw' ] = array(
			'start'				=> isset( $raw_field_data[ 'start' ] ) ? $raw_field_data[ 'start' ] : $default_meta[ 'start' ],
			'end'				=> isset( $raw_field_data[ 'end' ] ) ? $raw_field_data[ 'end' ] : $default_meta[ 'end' ],
			'method'			=> isset( $raw_field_data[ 'method' ] ) ? $raw_field_data[ 'method' ] : $default_meta[ 'method' ],
			'group_categories'	=> isset( $raw_field_data[ 'group_categories' ] ) ? $raw_field_data[ 'group_categories' ] : $default_meta[ 'group_categories' ],
			'user_id'			=> isset( $raw_field_data[ 'user_id' ] ) ? $raw_field_data[ 'user_id' ] : $default_meta[ 'user_id' ],
			'id'				=> isset( $raw_field_data[ 'id' ] ) ? sanitize_title_with_dashes( $raw_field_data[ 'id' ] ) : $default_meta[ 'id' ],
			'class'				=> isset( $raw_field_data[ 'class' ] ) ? sanitize_title( $raw_field_data[ 'class' ] ) : $default_meta[ 'class' ]
		);
		
		// Format additional meta
		$keys_by_type = array( 'bool' => array( 'past_events_bookable' ) );
		$additional_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
		$field_meta = array_merge( $field_meta, $additional_meta );
		
		
	} else if( $raw_field_data[ 'name' ] === 'login' ) {
		// Format meta values
		$keys_by_type = array( 'bool' => array( 'generate_password', 'send_new_account_email' ) );
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type, $field_meta );
		
		// Treat 'required_fields' and 'displayed_fields' field meta as a common field data
		$default_data[ 'displayed_fields' ]	= $default_meta[ 'displayed_fields' ];
		$default_data[ 'required_fields' ]	= $default_meta[ 'required_fields' ];
		
		// Format common values (specific cases)
		// Format label, placeholder and tip
		$register_defaults = bookacti_get_default_register_fields_data();
		$fields = array( 'label', 'placeholder', 'tip', 'displayed_fields', 'required_fields' );
		foreach( $fields as $field ) {
			$raw_field_data[ $field ] = isset( $raw_field_data[ $field ] ) ? maybe_unserialize( $raw_field_data[ $field ] ) : false;
			if( is_array( $raw_field_data[ $field ] ) ) {
				// Format register
				$register_fields = array();
				foreach( $register_defaults as $register_field_name => $register_default ) {
					$register_fields[ $register_field_name ] = isset( $raw_field_data[ $field ][ $register_field_name ] ) ? $raw_field_data[ $field ][ $register_field_name ] : $register_default[ $field ];
				}

				// Merge register fields
				$field_data[ $field ] = array_merge( array( 
					'email'			=> isset( $raw_field_data[ $field ][ 'email' ] ) ? $raw_field_data[ $field ][ 'email' ] : $default_data[ $field ][ 'email' ], 
					'password'		=> isset( $raw_field_data[ $field ][ 'password' ] ) ? $raw_field_data[ $field ][ 'password' ] : $default_data[ $field ][ 'password' ],
					'register'		=> isset( $raw_field_data[ $field ][ 'register' ] ) ? $raw_field_data[ $field ][ 'register' ] : $default_data[ $field ][ 'register' ]
				), $register_fields );

			} else {
				$field_data[ $field ] = $default_data[ $field ];
			}
		}
		
	} else if( $raw_field_data[ 'name' ] === 'quantity' ) {
		
	} else if( $raw_field_data[ 'name' ] === 'submit' ) {
		
	} else if( $raw_field_data[ 'name' ] === 'free_text' ) {
		// Format common values (specific cases)
		// Format value
		if( isset( $raw_field_data[ 'value' ] ) ) {
			$field_data[ 'value' ] = wpautop( $raw_field_data[ 'value' ] );
		}
	}
	
	// Format common values
	$keys_by_type = array( 
		'int'		=> array( 'field_id' ),
		'str_id'	=> array( 'name', 'type', 'id' ),
		'str'		=> array( 'title', 'label', 'class', 'value', 'placeholder', 'tip' ),
		'array'		=> array( 'options' ),
		'bool'		=> array( 'compulsory', 'default', 'unique', 'required' )
	);
	$field_data = bookacti_sanitize_values( $default_data, $raw_field_data, $keys_by_type, $field_data );
	
	if( ! $field_data[ 'title' ] ) { $field_data[ 'title' ] = $default_data[ 'title' ]; }
	
	// Merge common data and metadata
	$field_data = array_merge( $field_data, $field_meta );
	
	return apply_filters( 'bookacti_formatted_field_data', $field_data, $raw_field_data );
}

/**
 * Sanitize field data according to its type
 * @since 1.5.0
 * @param array|string $raw_field_data
 * @return array|false
 */
function bookacti_sanitize_form_field_data( $raw_field_data ) {
	
	// Check if name and type are set
	if( ! is_array( $raw_field_data ) || empty( $raw_field_data[ 'name' ] ) || empty( $raw_field_data[ 'type' ] ) ) { return false; }
	
	$default_data	= bookacti_get_default_form_fields_data( $raw_field_data[ 'name' ] );
	$default_meta	= bookacti_get_default_form_fields_meta( $raw_field_data[ 'name' ] );
	
	if( ! $default_data ) { return false; }
	
	$field_data	= array();
	$field_meta	= array();
	
	// Sanitize field-specific data and metadata
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		// Build template_data array
		$raw_field_data[ 'template_data' ] = array(
			'start'		=> isset( $raw_field_data[ 'start' ] ) ? $raw_field_data[ 'start' ] : $default_meta[ 'start' ],
			'end'		=> isset( $raw_field_data[ 'end' ] ) ? $raw_field_data[ 'end' ] : $default_meta[ 'end' ],
			'settings'	=> array_intersect_key( $raw_field_data, bookacti_format_template_settings( array() ) ) 
		);
		
		// Sanitize booking system data
		$field_meta = bookacti_format_booking_system_attributes( $raw_field_data );
		
		// Keep some meta unformatted
		if( isset( $raw_field_data[ 'method' ] ) && $raw_field_data[ 'method' ] === 'site' )					{ $field_meta[ 'method' ] = 'site'; }
		if( isset( $raw_field_data[ 'group_categories' ] ) && $raw_field_data[ 'group_categories' ] === 'none' ){ $field_meta[ 'group_categories' ] = 'none'; }
		if( isset( $raw_field_data[ 'user_id' ] ) && $raw_field_data[ 'user_id' ] === 'current' )				{ $field_meta[ 'user_id' ] = 'current'; }
		$field_meta[ 'id' ]		= isset( $raw_field_data[ 'id' ] ) && $raw_field_data[ 'id' ] !== '' ? sanitize_title_with_dashes( $raw_field_data[ 'id' ] ) : $default_meta[ 'id' ];
		$field_meta[ 'class' ]	= isset( $raw_field_data[ 'class' ] ) && $raw_field_data[ 'class' ] !== '' ? sanitize_title( $raw_field_data[ 'class' ] ) : $default_meta[ 'class' ];
		
		// Deconstruct template_data
		$field_meta[ 'start' ]	= isset( $raw_field_data[ 'start' ] ) && $raw_field_data[ 'start' ] !== '' ? $field_meta[ 'template_data' ][ 'start' ] : $default_meta[ 'start' ];
		$field_meta[ 'end' ]	= isset( $raw_field_data[ 'end' ] ) && $raw_field_data[ 'end' ] !== '' ? $field_meta[ 'template_data' ][ 'end' ] : $default_meta[ 'end' ];
		foreach( $field_meta[ 'template_data' ][ 'settings' ] as $key => $value ) {
			if( ! isset( $default_meta[ $key ] ) ) { continue; }
			$field_meta[ $key ] = isset( $raw_field_data[ $key ] ) && $raw_field_data[ $key ] !== '' ? $value : $default_meta[ $key ];
		}
		
		// bookacti_log( $raw_field_data );Sanitize additional meta
		$keys_by_type = array( 'bool' => array( 'past_events_bookable' ) );
		$additional_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
		$field_meta = array_merge( $field_meta, $additional_meta );
		
	} else if( $raw_field_data[ 'name' ] === 'login' ) {
		// Sanitize meta values
		$keys_by_type = array( 'bool' => array( 'generate_password', 'send_new_account_email' ) );
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type, $field_meta );
		
		// Treat 'required_fields' and 'displayed_fields' field meta as a common field data
		$default_data[ 'displayed_fields' ] = $default_meta[ 'displayed_fields' ];
		$default_data[ 'required_fields' ] = $default_meta[ 'required_fields' ];

		// Sanitize common values (specific cases)
		// Sanitize label, placeholder and tip
		$register_defaults = bookacti_get_default_register_fields_data();
		$fields = array( 'label', 'placeholder', 'tip', 'displayed_fields', 'required_fields' );
		foreach( $fields as $field ) {
			if( isset( $raw_field_data[ $field ] ) ) {
				$raw_field_data[ $field ] = maybe_unserialize( $raw_field_data[ $field ] );
				if( is_array( $raw_field_data[ $field ] ) ) {
					// Sanitize register fields
					$register_fields = array();
					foreach( $register_defaults as $register_field_name => $register_default ) {
						$register_fields[ $register_field_name ] = isset( $raw_field_data[ $field ][ $register_field_name ] ) ? stripslashes( $raw_field_data[ $field ][ $register_field_name ] ) : $register_default[ $field ];
					}
					
					// Merge register fields
					$field_data[ $field ] = array_merge( array( 
						'email'			=> isset( $raw_field_data[ $field ][ 'email' ] ) ? stripslashes( $raw_field_data[ $field ][ 'email' ] ) : $default_data[ $field ][ 'email' ], 
						'password'		=> isset( $raw_field_data[ $field ][ 'password' ] ) ? stripslashes( $raw_field_data[ $field ][ 'password' ] ) : $default_data[ $field ][ 'password' ],
						'register'		=> isset( $raw_field_data[ $field ][ 'register' ] ) ? stripslashes( $raw_field_data[ $field ][ 'register' ] ) : $default_data[ $field ][ 'register' ]
					), $register_fields );
				} else {
					$field_data[ $field ] = $default_data[ $field ];
				}
			}
			$field_data[ $field ] = maybe_serialize( $field_data[ $field ] );
		}
		
	} else if( $raw_field_data[ 'name' ] === 'quantity' ) {
		
	} else if( $raw_field_data[ 'name' ] === 'submit' ) {
		
	} else if( $raw_field_data[ 'name' ] === 'free_text' ) {
		// Sanitize common values (specific cases)
		// Sanitize value
		if( isset( $raw_field_data[ 'value' ] ) ) {
			$field_data[ 'value' ] = wp_kses_post( stripslashes( $raw_field_data[ 'value' ] ) );
			// Strip form tags
			$tags = array( 'form', 'input', 'textarea', 'select', 'option', 'output' );
			$field_data[ 'value' ] = preg_replace( '#<(' . implode( '|', $tags) . ')(?:[^>]+)?>.*?</\1>#s', '', $field_data[ 'value' ] );
		}
	}
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'		=> array( 'field_id' ),
		'str_id'	=> array( 'name', 'type', 'id' ),
		'str'		=> array( 'title', 'label', 'class', 'value', 'placeholder', 'tip' ),
		'array'		=> array( 'options' ),
		'bool'		=> array( 'compulsory', 'default', 'unique', 'required' )
	);
	$field_data = bookacti_sanitize_values( $default_data, $raw_field_data, $keys_by_type, $field_data );
	
	$field_data[ 'options' ] = maybe_serialize( $field_data[ 'options' ] );
	
	// Keep only allowed data and metadata
	$field_data = array_intersect_key( $field_data, $default_data );
	$field_meta = array_intersect_key( $field_meta, $default_meta );
	
	// Merge common data and metadata
	$field_data = array_merge( $field_data, $field_meta );
	
	return apply_filters( 'bookacti_sanitized_field_data', $field_data, $raw_field_data );
}


/**
 * Display a form field for the form editor
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param boolean $echo
 * @return string|void
 */
function bookacti_diplay_form_field_for_editor( $field, $form_id, $echo = true ) {
	$field_name = esc_attr( $field[ 'name' ] );
	$field[ 'id' ] = esc_attr( 'bookacti-form-editor-' . $field_name );
	if( ! $field[ 'unique' ] ) { $field[ 'id' ] .= '-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! $echo ) { ob_start(); };
	?>
	<div id='bookacti-form-editor-field-<?php echo esc_attr( $field[ 'field_id' ] ); ?>' class='bookacti-form-editor-field' data-field-id='<?php echo esc_attr( $field[ 'field_id' ] ); ?>' data-field-name='<?php echo esc_attr( $field[ 'name' ] ); ?>' >
		<div class='bookacti-form-editor-field-header' >
			<div class='bookacti-form-editor-field-title' >
				<h3><?php echo wp_kses_post( $field[ 'title' ] ); ?></h3>
			</div>
			<div class='bookacti-form-editor-field-actions' >
				<?php do_action( 'bookacti_form_editor_field_actions_before', $field, $form_id ); ?>
				<div class='bookacti-form-editor-field-action bookacti-edit-form-field dashicons dashicons-admin-generic' title='<?php esc_attr_e( 'Change field settings', BOOKACTI_PLUGIN_NAME ); ?>'></div>
			<?php if( ! $field[ 'compulsory' ] ) { ?>
				<div class='bookacti-form-editor-field-action bookacti-remove-form-field dashicons dashicons-trash' title='<?php esc_attr_e( 'Remove this field', BOOKACTI_PLUGIN_NAME ); ?>'></div>
			<?php }
				do_action( 'bookacti_form_editor_field_actions_after', $field, $form_id ); 
			?>
				<div class='bookacti-field-toggle dashicons dashicons-arrow-down' title='<?php esc_attr_e( 'Show / Hide', BOOKACTI_PLUGIN_NAME ); ?>'></div>
			</div>
		</div>
		<div class='bookacti-form-editor-field-body' style='display:none;' >
		<?php
			bookacti_diplay_form_field( $field, $form_id, 'bookacti-form-editor', 'edit' );
		?>
		</div>
	</div>
<?php
	if( ! $echo ) { return ob_get_clean(); }
}


/**
 * Display a form field
 * @since 1.5.0
 * @param array $field
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 * @param boolean $echo
 * @return string|void
 */
function bookacti_diplay_form_field( $field, $form_id = 0, $instance_id = '', $context = 'display', $echo = true ) {
	
	if( empty( $field[ 'name' ] ) ) { return ''; };
	
	if( ! $instance_id ) { $instance_id = rand(); }
	$field_id = ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( $field[ 'field_id' ] . '-' . $instance_id );
	
	ob_start();
?>
	<div class='bookacti-form-field-container <?php echo esc_attr( $field[ 'class' ] ); if( ! empty( $field[ 'name' ] ) ) { echo ' bookacti-form-field-name-' . esc_attr( $field[ 'name' ] ); } if( ! empty( $field[ 'field_id' ] ) ) { echo ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); } ?>' id='<?php echo $field_id; ?>' >
	<?php if( ! empty( $field[ 'label' ] ) && is_string( $field[ 'label' ] ) ) { ?>
		<div class='bookacti-form-field-label' >
			<label><?php echo apply_filters( 'bookacti_translate_text', $field[ 'label' ] ); ?></label>
		<?php if( ! empty( $field[ 'tip' ] ) && is_string( $field[ 'tip' ] )) { bookacti_help_tip( apply_filters( 'bookacti_translate_text', esc_html( $field[ 'tip' ] ) ) ); } ?>
		</div>
	<?php } ?>
		<div class='bookacti-form-field-content' >
		<?php 
			do_action( 'bookacti_diplay_form_field_' . $field[ 'name' ], $field, $form_id, $instance_id, $context ); 
		?>
		</div>
	</div>
<?php
	$html = apply_filters( 'bookacti_html_form_field_' . $field[ 'name' ], ob_get_clean(), $field, $form_id, $instance_id, $context );
	if( ! $echo ) { return $html; }
	echo $html;
}


/**
 * Sanitize a field order array
 * @since 1.5.0
 * @param int $form_id
 * @param array $field_order
 * @return array
 */
function bookacti_sanitize_form_field_order( $form_id, $field_order ) {
	
	$custom_fields = bookacti_get_form_fields( $form_id );
	
	if( ! $custom_fields ) { return array(); }
	
	// Get existing field ids for the desired form
	$existing_field_ids = array();
	foreach( $custom_fields as $custom_field ) {
		$existing_field_ids[] = $custom_field[ 'field_id' ];
	}
	
	// Keep only existing field ids
	$intersect = array_intersect( $field_order, $existing_field_ids );
	
	// Add existing missing field ids to field order
	$diff	= array_diff( $intersect, $existing_field_ids );
	$merge	= array_merge( $intersect, $diff );
	
	// Sanitize strings to integers
	$map	= array_map( 'intval', $merge );
	$filter = array_filter( $map );
	
	return array_values( $filter );
}


/**
 * Sort fields array by field order
 * @since 1.5.0
 * @param int $form_id
 * @param array $fields
 * @param boolean $remove_unordered_fields Whether to remove the fields that are not in the field order array 
 * @return array
 */
function bookacti_sort_form_fields_array( $form_id, $fields, $remove_unordered_fields = false ) {
	// Sort form fields by customer custom order
	$field_order = bookacti_get_metadata( 'form', $form_id, 'field_order', true );
	
	if( $field_order ) { 
		$ordered_fields		= array();
		$remaining_fields	= $fields;
		foreach( $field_order as $field_id ) {
			foreach( $fields as $i => $field ) {
				if( $field[ 'field_id' ] !== $field_id ) { continue; }

				$ordered_fields[] = $fields[ $i ];
				unset( $remaining_fields[ $i ] );

				break;
			}
		}

		// Add the remaining unordered fields (if any)
		if( ! $remove_unordered_fields && $remaining_fields ) {
			foreach( $remaining_fields as $remaining_field ) {
				$ordered_fields[] = $remaining_field;
			}
		}
	} else {
		$ordered_fields = $fields;
	}
	
	return apply_filters( 'bookacti_ordered_form_fields', $ordered_fields, $form_id, $fields, $remove_unordered_fields );
}




// LOGIN / REGISTER

/**
 * Get user meta fields 
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_register_fields_data() {
	return apply_filters( 'bookacti_register_fields', array(
		'first_name' => array( 
			'name'			=> 'first_name', 
			'type'			=> 'text', 
			'label'			=> __( 'First name', BOOKACTI_PLUGIN_NAME ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		),
		'last_name' => array( 
			'name'			=> 'last_name', 
			'type'			=> 'text', 
			'label'			=> __( 'Last name', BOOKACTI_PLUGIN_NAME ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		),
		'phone' => array( 
			'name'			=> 'phone', 
			'type'			=> 'text', 
			'label'			=> __( 'Phone number', BOOKACTI_PLUGIN_NAME ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		)
	));
}




// FORM EDITOR METABOXES

/**
 * Display 'managers' metabox content for forms
 * @since 1.5.0
 */
function bookacti_display_form_managers_meta_box( $form ) {
	
	// Get current form managers option list
	$managers_already_added = array();
	$manager_ids = bookacti_get_form_managers( $form[ 'form_id' ] );
	$current_managers_options_list = '';
	if( ! empty( $manager_ids ) ) {
		foreach( $manager_ids as $manager_id ) {
			$userdata = get_userdata( $manager_id );
			$display_name = $userdata->user_login;
			if( ! empty( $userdata->first_name ) && ! empty( $userdata->last_name ) ){
				$display_name = $userdata->first_name  . ' ' . $userdata->last_name . ' (' . $userdata->user_login . ')';
			}
			$display_name = apply_filters( 'bookacti_managers_name_display', $display_name, $userdata );
			$current_managers_options_list .= '<option value="' . $manager_id . '" selected >' . $display_name . '</option>';
			$managers_already_added[] = $manager_id;
		}
	}
	
	// Get available form managers option list
	$in_roles		= apply_filters( 'bookacti_managers_roles', array() );
	$not_in_roles	= apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ) );
	$user_query		= new WP_User_Query( array( 'role__in' => $in_roles, 'role__not_in' => $not_in_roles ) );
	$users			= $user_query->get_results();
	$available_managers_options_list = '';
	if ( ! empty( $users ) ) {
		foreach( $users as $user ) {
			if( $user->has_cap( 'bookacti_edit_forms' ) ) {
				$userdata = get_userdata( $user->ID );
				$display_name = $userdata->user_login;
				if( $userdata->first_name && $userdata->last_name ){
					$display_name = $userdata->first_name  . ' ' . $userdata->last_name . ' (' . $userdata->user_login . ')';
				}
				$display_name = apply_filters( 'bookacti_managers_name_display', $display_name, $userdata );
				$disabled = in_array( $user->ID, $managers_already_added, true ) ? 'disabled style="display:none;"' : '';
				
				$available_managers_options_list .= '<option value="' . esc_attr( $user->ID ) . '" ' . $disabled . ' >' . esc_html( $display_name ) . '</option>';
			}
		}
	}
	
	?>
	<div id='bookacti-form-managers-container' class='bookacti-items-container' data-type='users' >
		<label id='bookacti-form-managers-title' class='bookacti-fullwidth-label' for='bookacti-add-new-form-managers-select-box' >
		<?php 
			esc_html_e( 'Who can manage this form?', BOOKACTI_PLUGIN_NAME );
			$tip  = __( 'Choose who is allowed to access this form.', BOOKACTI_PLUGIN_NAME );
			/* translators: %s = capabilities name */
			$tip .= ' ' . sprintf( __( 'All administrators already have this privilege. If the selectbox is empty, it means that no users have capabilities such as %s.', BOOKACTI_PLUGIN_NAME ), '"bookacti_edit_forms"' );
			/* translators: %1$s = Points of sale add-on link. %2$s = User role editor plugin name. */
			$tip .= '<br/>' 
				 .  sprintf( __( 'Point of sale managers from %1$s add-on have these capabilities. If you want to grant a user these capabilities, use a plugin such as %2$s.', BOOKACTI_PLUGIN_NAME ), 
						'<a href="https://booking-activities.fr/en/downloads/points-of-sale/?utm_source=plugin&utm_medium=plugin&utm_campaign=points-of-sale&utm_content=infobulle-permission" target="_blank" >' . esc_html( __( 'Points of Sale', BOOKACTI_PLUGIN_NAME ) ) . '</a>',
						'<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank" >User Role Editor</a>'
					);
			bookacti_help_tip( $tip );
		?>
		</label>
		<div id='bookacti-add-form-managers-container' class='bookacti-add-items-container' >
			<select id='bookacti-add-new-form-managers-select-box' class='bookacti-add-new-items-select-box' >
			<?php echo $available_managers_options_list; ?>
			</select>
			<button type='button' id='bookacti-add-form-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', BOOKACTI_PLUGIN_NAME ); ?></button>
		</div>
		<div id='bookacti-form-managers-list-container' class='bookacti-items-list-container' >
			<select name='form-managers[]' id='bookacti-form-managers-select-box' class='bookacti-items-select-box' multiple >
			<?php echo $current_managers_options_list; ?>
			</select>
			<button type='button' id='bookacti-remove-form-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', BOOKACTI_PLUGIN_NAME ); ?></button>
		</div>
	</div>
	<?php
}


/**
 * Display 'publish' metabox content for forms
 * @since 1.5.0
 * @param object $form
 */
function bookacti_display_form_publish_meta_box( $form ) {
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions'>
			<div id='delete-action'>
			<?php
				if ( current_user_can( 'bookacti_delete_forms' ) ) {
					if( ! $form[ 'active' ] ) {
						echo '<a href="' . esc_url( wp_nonce_url( get_admin_url() . 'admin.php?page=bookacti_forms', 'delete-form_' . $form[ 'form_id' ] ) . '&action=delete&active=0&form_id=' . $form[ 'form_id' ] ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Delete Permanently', 'forms', BOOKACTI_PLUGIN_NAME )
							. '</a>';
					} else {
						echo '<a href="' . esc_url( wp_nonce_url( get_admin_url() . 'admin.php?page=bookacti_forms', 'trash-form_' . $form[ 'form_id' ] ) . '&action=trash&form_id=' . $form[ 'form_id' ] ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Move to trash', 'forms', BOOKACTI_PLUGIN_NAME )
							. '</a>';
					}
				}
			?>
			</div>

			<div id='publishing-action'>
				<span class='spinner'></span>
				<input id='bookacti-save-form-button' name='save' type='submit' class='button button-primary button-large' id='publish' value='<?php echo ! $form[ 'active' ] ? esc_attr_x( 'Publish', 'forms', BOOKACTI_PLUGIN_NAME ) : esc_attr_x( 'Update', 'forms', BOOKACTI_PLUGIN_NAME ); ?>' />
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}


/**
 * Display 'integration tuto' metabox content for forms
 * @since 1.5.0
 * @param object $form
 */
function bookacti_display_form_integration_tuto_meta_box( $form ) {
	$shortcode = '[bookingactivities_form form="' . $form[ 'form_id' ] . '"]';
?>
	<h4><?php _e( 'Integrate in a post, page, or text widget', BOOKACTI_PLUGIN_NAME ) ?></h4>
	<div>
		<p><em><label for='bookacti-form-shortcode'><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', BOOKACTI_PLUGIN_NAME ); ?></label></em></p>
		<p class='shortcode wp-ui-highlight'>
			<input type='text' id='bookacti-form-shortcode' onfocus='this.select();' readonly='readonly' class='large-text code' value='<?php echo esc_attr( $shortcode ); ?>' />
		</p>
	</div>
<?php
	do_action( 'bookacti_after_form_integration_tuto', $form );
}




// PERMISSIONS

/**
 * Check if user is allowed to manage form
 * @since 1.5.0
 * @param int $template_id
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_can_manage_form( $form_id, $user_id = false ) {

	$user_can_manage_form = false;
	$bypass_form_managers_check = apply_filters( 'bookacti_bypass_form_managers_check', false );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin() || $bypass_form_managers_check ) { $user_can_manage_form = true; }
	else {
		$admins = bookacti_get_form_managers( $form_id );
		if( in_array( $user_id, $admins, true ) ) { $user_can_manage_form = true; }
	}

	return apply_filters( 'bookacti_user_can_manage_form', $user_can_manage_form, $form_id, $user_id );
}


/**
 * Get form managers
 * @since 1.5.0
 * @param int $activity_id
 * @return array
 */
function bookacti_get_form_managers( $form_id ) {
	return bookacti_get_managers( 'form', $form_id );
}


/**
 * Format form managers
 * @since 1.5.0
 * @param array $form_managers
 * @return array
 */
function bookacti_format_form_managers( $form_managers = array() ) {
	
	$form_managers = bookacti_ids_to_array( $form_managers );
	
	// If user is not super admin, add him automatically in the form managers list if he isn't already
	$bypass_form_managers_check = apply_filters( 'bookacti_bypass_form_managers_check', false );
	if( ! is_super_admin() && ! $bypass_form_managers_check ) {
		$user_id = get_current_user_id();
		if( ! in_array( $user_id, $form_managers, true ) ) {
			$form_managers[] = $user_id;
		}
	}
	
	// Make sure all users have permission to manage forms
	foreach( $form_managers as  $i => $form_manager ) {
		if( empty( $form_manager )
		|| ! user_can( $form_manager, 'bookacti_edit_forms' ) ) {
			unset( $form_managers[ $i ] );
		}
	}
	
	return apply_filters( 'bookacti_form_managers', $form_managers );
}