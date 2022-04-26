<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// FORM

/**
 * Create a new form
 * @since 1.5.0
 * @version 1.14.0
 * @param string $title
 * @param boolean $insert_default_fields
 * @return int|false
 */
function bookacti_create_form( $title = '', $insert_default_fields = false ) {
	// Insert form
	$form_id = bookacti_insert_form( $title );
	if( $form_id === false ) { return false; }
	
	// Insert default form fields
	if( $insert_default_fields ) {
		$inserted = bookacti_insert_default_form_fields( $form_id );
		if( $inserted ) {
			// Save initial field order
			$field_order = bookacti_sanitize_form_field_order( $form_id, array() );
			if( $field_order ) { bookacti_update_metadata( 'form', $form_id, array( 'field_order' => $field_order ) ); }
		}
	}
	
	// Insert default form managers
	$form_managers = bookacti_format_form_managers();
	bookacti_update_managers( 'form', $form_id, $form_managers );
	
	do_action( 'bookacti_form_inserted', $form_id );
	
	return $form_id;
}


/**
 * Get form data and metadata
 * @since 1.5.0
 * @version 1.14.0
 * @param int $form_id
 * @param boolean $raw
 * @return array
 */
function bookacti_get_form_data( $form_id, $raw = false ) {
	$form = bookacti_get_form( $form_id );
	if( ! $form ) { return array(); }
	
	// Add form metadata
	$form_meta = bookacti_get_metadata( 'form', $form_id );
	if( is_array( $form_meta ) ) { 
		$form = array_merge( $form, $form_meta );
	}

	// Format data
	if( ! $raw ) { $form = bookacti_format_form_data( $form ); }
	
	return apply_filters( 'bookacti_form_data', $form, $form_id, $raw );
}


/**
 * Get default form data
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_form_data() {
	return apply_filters( 'bookacti_default_form_data', array( 
		'form_id'       => 0,  // Form ID
		'title'         => '', // Form title displayed in form list and form editor
		'user_id'       => -1, // Author user ID
		'creation_date' => '', // Datetime when the form was created
		'status'        => '', // Form status
		'active'        => -1  // If the form is active
	));
}


/**
 * Get default form meta
 * @since 1.5.0
 * @return array
 */
function bookacti_get_default_form_meta() {
	return apply_filters( 'bookacti_default_form_meta', array(
		'id' => '',           // Form's id
		'class' => '',        // Form's classes
		'redirect_url' => '', // URL to redirect to when the form is submitted
	));
}


/**
 * Format form data
 * @since 1.5.0
 * @version 1.14.0
 * @param array|string $raw_form_data
 * @param string $context "view" or "edit"
 * @return array
 */
function bookacti_format_form_data( $raw_form_data = array(), $context = 'view' ) {
	if( ! is_array( $raw_form_data ) ) { return array(); }
	
	$default_data = bookacti_get_default_form_data();
	$default_meta = bookacti_get_default_form_meta();
	if( ! $default_data ) { return array(); }
	
	// Empty default strings in edit mode
	if( $context === 'edit' ) {
		$default_data[ 'title' ] = $default_meta[ 'redirect_url' ] = '';
	}
	
	// Format meta values
	$keys_by_type = array( 
		'str_id' => array( 'id' ),
		'str'    => array( 'class', 'redirect_url' )
	);
	$form_meta = bookacti_sanitize_values( $default_meta, $raw_form_data, $keys_by_type );
	
	// Exception: Keep field_order and format it
	$form_meta[ 'field_order' ] = isset( $raw_form_data[ 'field_order' ] ) ? maybe_unserialize( $raw_form_data[ 'field_order' ] ) : array();
	
	// Format common values
	$keys_by_type = array( 
		'int'      => array( 'form_id', 'user_id' ),
		'str_id'   => array( 'status' ),
		'str'      => array( 'title' ),
		'datetime' => array( 'creation_date' ),
		'bool'     => array( 'active' )
	);
	$form_data = bookacti_sanitize_values( $default_data, $raw_form_data, $keys_by_type );
	
	// Merge common data and metadata
	$form_data = array_merge( $form_data, $form_meta );
	
	// Translate texts
	if( $context !== 'edit' ) { 
		if( ! empty( $form_data[ 'title' ] ) )        { $form_data[ 'title' ]        = apply_filters( 'bookacti_translate_text', $form_data[ 'title' ] ); }
		if( ! empty( $form_data[ 'redirect_url' ] ) ) { $form_data[ 'redirect_url' ] = apply_filters( 'bookacti_translate_text', $form_data[ 'redirect_url' ] ); }
	}
	
	return apply_filters( 'bookacti_formatted_form_data', $form_data, $raw_form_data, $context );
}


/**
 * Sanitize form data
 * @since 1.5.0
 * @version 1.14.0
 * @param array|string $raw_form_data
 * @return array|false
 */
function bookacti_sanitize_form_data( $raw_form_data ) {
	if( ! is_array( $raw_form_data ) ) { return array(); }
	
	$default_data = bookacti_get_default_form_data();
	$default_meta = bookacti_get_default_form_meta();
	if( ! $default_data ) { return array(); }
	
	// Sanitize meta values
	$keys_by_type = array( 
		'str_id' => array( 'id' ),
		'str'    => array( 'class', 'redirect_url' )
	);
	$form_meta = bookacti_sanitize_values( $default_meta, $raw_form_data, $keys_by_type );
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'      => array( 'form_id', 'user_id' ),
		'str_id'   => array( 'status' ),
		'str'      => array( 'title' ),
		'datetime' => array( 'creation_date' ),
		'bool'     => array( 'active' )
	);
	$form_data = bookacti_sanitize_values( $default_data, $raw_form_data, $keys_by_type );
	
	// Merge common data and metadata
	$form_data = array_merge( $form_data, $form_meta );
	
	return apply_filters( 'bookacti_sanitized_form_data', $form_data, $raw_form_data );
}


/**
 * Display a booking form
 * @version 1.14.0
 * @param int $form_id
 * @param string $instance_id
 * @param string $context
 * @param boolean $echo
 * @return void|string
 */
function bookacti_display_form( $form_id, $instance_id = '', $context = 'display', $echo = true ) {
	if( ! $form_id ) { return ''; }
	
	$form = bookacti_get_form_data( $form_id );
	if( ! $form ) { return ''; }
	
	$form_action = 'bookactiSubmitBookingForm';
	
	// Set the form unique CSS selector
	$form_css_id = ! empty( $form[ 'id' ] ) ? esc_attr( $form[ 'id' ] ) : ( $instance_id ? esc_attr( $instance_id ) : esc_attr( ( $context === 'login_form' ? 'login-form-' : 'form-' ) . $form[ 'form_id' ] . '-' . rand() ) );
	if( ! $instance_id ) { $instance_id = $form_css_id; }
	
	$fields = bookacti_get_form_fields_data( $form_id );
	$ordered_form_fields = bookacti_sort_form_fields_array( $form_id, $fields );
	
	$displayed_form_fields = apply_filters( 'bookacti_displayed_form_fields', $ordered_form_fields, $form, $instance_id, $context );
	
	// Build array of field types
	$is_form = 0;
	$login_field = array();
	$calendar_field = array();
	foreach( $displayed_form_fields as $field ) { 
		if( empty( $field[ 'type' ] ) ) { continue; }
			 if( $field[ 'type' ] === 'calendar' ) { $calendar_field = $field; }
		else if( $field[ 'type' ] === 'login' )    { $login_field = $field; }
		else if( $field[ 'type' ] === 'submit' )   { $is_form = 1; }
	}
	
	// Show only the login field if the user is not logged in
	if( ! is_user_logged_in() && ( ! empty( $login_field[ 'login_first' ] ) || $context === 'login_form' ) ) {
		// Force some values
		$login_field[ 'login_first' ] = 1;
		$login_field[ 'login_button' ] = 1;
		$login_field[ 'displayed_fields' ][ 'no_account' ] = 0;
		$displayed_form_fields = array( $login_field );
		$form_action = 'bookactiSubmitLoginForm';
		$is_form = 1;
	}
	
	// Change the form action according to the form_action option of calendar field
	else if( ! empty( $calendar_field[ 'form_action' ] ) ) {
		if( $calendar_field[ 'form_action' ] === 'redirect_to_url' ) { $form_action = ''; }
	}
	
	// Set form action
	$form_redirect_url = $form[ 'redirect_url' ];
	if( $context === 'login_form' ) {
		$form_redirect_url = ! empty( $GLOBALS[ 'bookacti_login_redirect_url' ] ) ? $GLOBALS[ 'bookacti_login_redirect_url' ] : '';
	}
	
	// Set form attributes
	$form_attributes = apply_filters( 'bookacti_form_attributes', array(
		'action'       => $form_redirect_url,
		'id'           => empty( $form[ 'id' ] ) ? 'bookacti-' . $form_css_id : $form_css_id,
		'class'        => 'bookacti-booking-form-' . $form_id . ' ' . $form[ 'class' ],
		'autocomplete' => 'off'
	), $form, $instance_id, $context, $displayed_form_fields );
	
	// Add compulsory class
	$compulsory_class = $is_form ? 'bookacti-booking-form' : 'bookacti-form-fields';
	$form_attributes[ 'class' ] = $compulsory_class . ( ! empty( $form_attributes[ 'class' ] ) ? ' ' . $form_attributes[ 'class' ] : '' );
	
	// Convert $form_attributes array to inline attributes
	$form_attributes_str = '';
	foreach( $form_attributes as $form_attribute_key => $form_attribute_value ) {
		if( $form_attribute_value !== '' ) { $form_attributes_str .= $form_attribute_key . '="' . $form_attribute_value . '" '; }
	}
	
	ob_start();
	
	// Add form container only if there is a "submit" button
	if( $is_form ) { ?>
		<form <?php echo $form_attributes_str; ?>>
	<?php } else { ?>
		<div <?php echo $form_attributes_str; ?>>
	<?php } ?>
			<input type='hidden' name='form_id' value='<?php echo $form_id; ?>'/>
			<input type='hidden' name='action' value='<?php echo apply_filters( 'bookacti_form_action_field_value', $form_action, $form, $instance_id, $context, $displayed_form_fields ); ?>'/>
		<?php
			do_action( 'bookacti_form_before', $form, $instance_id, $context, $displayed_form_fields );

			foreach( $displayed_form_fields as $field ) {
				if( ! $field ) { continue; }
				bookacti_display_form_field( $field, $instance_id, $context, true );
			}

			do_action( 'bookacti_form_after', $form, $instance_id, $context, $displayed_form_fields );
		?>
			<div class='bookacti-notices' style='display:none;'></div>
	<?php
	if( ! $is_form ) { ?>
		</div>
	<?php } else { ?>
		</form>
	<?php }
	
	$html = apply_filters( 'bookacti_form_html', ob_get_clean(), $form, $instance_id, $context, $displayed_form_fields );
	if( ! $echo ) { return $html; }
	echo $html;
}




// FORM FIELDS

/**
 * Get form fields array
 * @since 1.5.4
 * @version 1.14.0
 * @param int $form_id
 * @param boolean $active_only   Whether to fetch only active fields. Default "true".
 * @param boolean $index_by_name Whether to index by name. Else, indexed by field id.
 * @param boolean $raw           Whether to format the data.
 * @return array
 */
function bookacti_get_form_fields_data( $form_id, $active_only = true, $index_by_name = false, $raw = false ) {
	// Retrieve inactive fields too, for a better cache efficiency
	if( $raw ) { wp_cache_delete( 'form_fields_' . $form_id, 'bookacti' ); }
	$fields = bookacti_get_form_fields( $form_id, $raw ? $active_only : false );
	if( ! $fields ) { return array(); }
	
	$fields_data_by_id = ! $raw ? wp_cache_get( 'form_fields_data_' . $form_id, 'bookacti' ) : array();
	
	if( ! $fields_data_by_id ) {
		// Get fields meta
		$fields_meta = bookacti_get_metadata( 'form_field', array_keys( $fields ) );

		// Add form field metadata and 
		// Format form fields
		$fields_data_by_id = array();
		foreach( $fields as $field_id => $field ) {
			// Add field-specific data
			$field_metadata = isset( $fields_meta[ $field_id ] ) ? $fields_meta[ $field_id ] : array();
			if( is_array( $field_metadata ) ) { 
				$field = array_merge( $field, $field_metadata );
			}

			// Format data
			$formatted_data = $raw ? $field : bookacti_format_form_field_data( $field );
			if( $formatted_data ) { $fields_data_by_id[ $field_id ] = $formatted_data; }
		}
		
		$fields_data_by_id = apply_filters( 'bookacti_form_fields_data', $fields_data_by_id, $form_id, $active_only, $index_by_name, $raw );
		
		if( ! $raw ) { wp_cache_set( 'form_fields_data_' . $form_id, $fields_data_by_id, 'bookacti' ); }
	}
	
	$fields_data = $index_by_name ? array() : $fields_data_by_id;
	foreach( $fields_data_by_id as $field_id => $field_data ) {
		// Remove inactive fields
		if( ! $index_by_name && $active_only && empty( $fields[ $field_id ][ 'active' ] ) ) { unset( $fields_data[ $field_id ] ); continue; }
		// Index by name
		if( $index_by_name && ( ! $active_only || ( $active_only && ! empty( $fields[ $field_id ][ 'active' ] ) ) ) ) { $fields_data[ $field_data[ 'name' ] ] = $field_data; }
	}
	
	return $fields_data;
}


/**
 * Get the desired field data as an array
 * @since 1.5.0
 * @version 1.8.0
 * @param int $field_id
 * @return array
 */
function bookacti_get_form_field_data( $field_id ) {
	$cache = wp_cache_get( 'form_field_data_' . $field_id, 'bookacti' );
	if( $cache ) { return $cache; }
	
	$field = bookacti_get_form_field( $field_id );
	if( ! $field ) { return array(); }
	
	// Add form field metadata
	$field_metadata = bookacti_get_metadata( 'form_field', $field[ 'field_id' ] );
	if( is_array( $field_metadata ) ) { 
		$field = array_merge( $field, $field_metadata );
	}
	
	// Format data
	$field_data = apply_filters( 'bookacti_form_field', bookacti_format_form_field_data( $field ) );
	
	wp_cache_set( 'form_field_data_' . $field_id, $field_data, 'bookacti' );
	
	return $field_data;
}


/**
 * Get the desired field data as an array. The field name must be unique, only the first will be retrieved.
 * @since 1.5.0
 * @version 1.8.0
 * @param int $form_id
 * @param string $field_name
 * @return array
 */
function bookacti_get_form_field_data_by_name( $form_id, $field_name ) {
	$cache = wp_cache_get( 'form_field_data_' . $field_name . '_' . $form_id, 'bookacti' );
	if( $cache ) { return $cache; }
	
	$field = bookacti_get_form_field_by_name( $form_id, $field_name );
	if( ! $field ) { return array(); }
	
	// Add form field metadata 
	$field_metadata = bookacti_get_metadata( 'form_field', $field[ 'field_id' ] );
	if( is_array( $field_metadata ) ) { 
		$field = array_merge( $field, $field_metadata );
	}
	
	// Format data
	$field_data = apply_filters( 'bookacti_form_field', bookacti_format_form_field_data( $field ) );
	
	wp_cache_set( 'form_field_data_' . $field_name . '_' . $form_id, $field_data, 'bookacti' );
	
	return $field_data;
}


/**
 * Get the default common for field data
 * @since 1.5.3
 * @version 1.14.0
 * @return array
 */
function bookacti_get_default_form_field_common_data() {
	return apply_filters( 'bookacti_default_common_form_field_data', array( 
		'field_id'    => 0,       // Field ID
		'form_id'     => 0,       // Form ID
		'name'        => '',      // Text identifier of the field
		'type'        => '',      // Field type [calendar, quantity, submit, login, free_text, or your custom types]
		'title'       => '',      // Field title displayed in form editor
		'label'       => '',      // Text displayed for the field on the frontend
		'id'          => '',      // Field CSS id
		'class'       => '',      // Field CSS classes
		'options'     => array(), // Array of allowed values
		'value'       => '',      // Default value among the allowed values
		'placeholder' => '',      // Text displayed in transparency when the field is empty
		'tip'         => '',      // Help text displayed in a tooltip next to the field
		'required'    => 0,       // Whether the customer is forced to fill this field when it is displayed
		'compulsory'  => 0,       // Whether the field can be deleted
		'default'     => 0,       // Whether the field is part of the form by default (if compulsory, it is by default too)
		'unique'      => 1        // Whether the user can add multiple occurrence of this field in the form
	));
}


/**
 * Get fields data
 * @see bookacti_format_form_field_data to properly format your array
 * @since 1.5.0
 * @version 1.12.4
 * @param string $field_name
 * @return array
 */
function bookacti_get_default_form_fields_data( $field_name = '' ) {
	// Set the common default data
	$default_data = bookacti_get_default_form_field_common_data();
	
	// Add register fields default
	$register_fields   = bookacti_get_register_fields_default_data();
	$register_defaults = array( 'label' => array(), 'placeholder' => array(), 'tip' => array() );
	foreach( $register_fields as $register_field_name => $register_field ) {
		$register_defaults[ 'label' ][ $register_field_name ]       = ! empty( $register_field[ 'label' ] )       ? $register_field[ 'label' ] : '';
		$register_defaults[ 'placeholder' ][ $register_field_name ] = ! empty( $register_field[ 'placeholder' ] ) ? $register_field[ 'placeholder' ] : '';
		$register_defaults[ 'tip' ][ $register_field_name ]         = ! empty( $register_field[ 'tip' ] )         ? $register_field[ 'tip' ] : '';
	}
	
	// Add login fields default
	$login_fields = bookacti_get_login_fields_default_data();
	$login_defaults	= array( 'label' => array(), 'placeholder' => array(), 'tip' => array() );
	foreach( $login_fields as $login_field_name => $login_field ) {
		$login_defaults[ 'label' ][ $login_field_name ]       = ! empty( $login_field[ 'label' ] )       ? $login_field[ 'label' ] : '';
		$login_defaults[ 'placeholder' ][ $login_field_name ] = ! empty( $login_field[ 'placeholder' ] ) ? $login_field[ 'placeholder' ] : '';
		$login_defaults[ 'tip' ][ $login_field_name ]         = ! empty( $login_field[ 'tip' ] )         ? $login_field[ 'tip' ] : '';
	}
	
	// Add login type fields default
	$login_types         = bookacti_get_login_type_field_default_options();
	$login_type_defaults = array( 'label' => array(), 'placeholder' => array(), 'tip' => array() );
	foreach( $login_types as $login_type_name => $login_type ) {
		$login_type_defaults[ 'label' ][ $login_type_name ]       = ! empty( $login_type[ 'label' ] )       ? $login_type[ 'label' ] : '';
		$login_type_defaults[ 'placeholder' ][ $login_type_name ] = ! empty( $login_type[ 'placeholder' ] ) ? $login_type[ 'placeholder' ] : '';
		$login_type_defaults[ 'tip' ][ $login_type_name ]         = ! empty( $login_type[ 'tip' ] )         ? $login_type[ 'tip' ] : '';
	}
	
	// Set field-speicific default data
	$fields_data = array(
		'calendar' => array( 
			'name'       => 'calendar',
			'type'       => 'calendar',
			'title'      => esc_html__( 'Calendar', 'booking-activities' ),
			'compulsory' => 1,
			'default'    => 1
		),
		'login' => array( 
			'name'        => 'login',
			'type'        => 'login',
			'title'       => esc_html__( 'User Data (Login / Registration)', 'booking-activities' ),
			'default'     => 1,
			'label'       => array_merge( $login_defaults[ 'label' ], $login_type_defaults[ 'label' ], $register_defaults[ 'label' ] ),
			'placeholder' => array_merge( $login_defaults[ 'placeholder' ], $login_type_defaults[ 'placeholder' ], $register_defaults[ 'placeholder' ] ),
			'tip'         => array_merge( $login_defaults[ 'tip' ], $login_type_defaults[ 'tip' ], $register_defaults[ 'tip' ] )
		),
		'free_text' => array( 
			'name'   => 'free_text',
			'type'   => 'free_text',
			'title'  => esc_html__( 'Free text', 'booking-activities' ),
			'unique' => 0
		),
		'quantity' => array( 
			'name'     => 'quantity',
			'type'     => 'quantity',
			'title'    => esc_html__( 'Quantity', 'booking-activities' ),
			'label'    => esc_html__( 'Quantity', 'booking-activities' ),
			'required' => 1,
			'default'  => 1
		),
		'terms' => array( 
			'name'     => 'terms',
			'type'     => 'checkbox',
			'title'    => esc_html__( 'Terms', 'booking-activities' ),
			'label'    => esc_html__( 'I have read and agree to the terms and conditions', 'booking-activities' ),
			'required' => 1
		),
		'total_price' => array( 
			'name'  => 'total_price',
			'type'  => 'total_price',
			'title' => esc_html__( 'Total price', 'booking-activities' ),
			'label' => esc_html__( 'Total price', 'booking-activities' )
		),
		'submit' => array( 
			'name'    => 'submit',
			'type'    => 'submit',
			'title'   => esc_html__( 'Submit button', 'booking-activities' ),
			'value'   => esc_html__( 'Book', 'booking-activities' ),
			'default' => 1
		)
	);
	
	$fields_data = apply_filters( 'bookacti_default_form_fields_data', $fields_data, $field_name );
	
	// Merge field-specific data to common data
	foreach( $fields_data as $i => $field_data ) {
		$fields_data[ $i ] = array_merge( $default_data, $field_data );
	}
		
	if( $field_name ) {
		return isset( $fields_data[ $field_name ] ) ? $fields_data[ $field_name ] : array();
	}
	
	return $fields_data;
}


/**
 * Get fields metadata
 * @see bookacti_format_form_field_data to properly format your array
 * @since 1.5.0
 * @version 1.13.0
 * @param string $field_name
 * @return array
 */
function bookacti_get_default_form_fields_meta( $field_name = '' ) {
	// Add register fields default meta to login field meta
	$register_fields	= bookacti_get_register_fields_default_data();
	$register_defaults	= array( 'displayed' => array(), 'required' => array() );
	foreach( $register_fields as $register_field_name => $register_field ) {
		$register_defaults[ 'displayed' ][ $register_field_name ]	= ! empty( $register_field[ 'displayed' ] )	? $register_field[ 'displayed' ] : 0;
		$register_defaults[ 'required' ][ $register_field_name ]	= ! empty( $register_field[ 'required' ] )	? $register_field[ 'required' ] : 0;
	}
	
	// Add login fields default
	$login_fields = bookacti_get_login_fields_default_data();
	$login_defaults	= array( 'label' => array(), 'placeholder' => array(), 'tip' => array() );
	foreach( $login_fields as $login_field_name => $login_field ) {
		$login_defaults[ 'displayed' ][ $login_field_name ]	= ! empty( $login_field[ 'displayed' ] )? $login_field[ 'displayed' ] : 0;
		$login_defaults[ 'required' ][ $login_field_name ]	= ! empty( $login_field[ 'required' ] )	? $login_field[ 'required' ] : 0;
	}
	
	// Add login type fields default
	$login_types			= bookacti_get_login_type_field_default_options();
	$login_type_defaults	= array( 'displayed' => array(), 'required' => array() );
	foreach( $login_types as $login_type_name => $login_type ) {
		$login_type_defaults[ 'displayed' ][ $login_type_name ]	= ! empty( $login_type[ 'displayed' ] )	? $login_type[ 'displayed' ] : 0;
		$login_type_defaults[ 'required' ][ $login_type_name ]	= ! empty( $login_type[ 'required' ] )	? $login_type[ 'required' ] : 0;
	}
	
	$fields_meta = apply_filters( 'bookacti_default_form_fields_meta', array(
		'calendar' => array(
			'id'                             => '',
			'class'                          => '',
			'method'                         => 'calendar',
			'hide_availability'              => 100,
			'calendars'                      => array(),
			'activities'                     => array(),
			'group_categories'               => array( 'none' ),
			'groups_only'                    => 0,
			'groups_single_events'           => 0,
			'multiple_bookings'              => 0,
			'bookings_only'                  => 0,
			'status'                         => array(),
			'user_id'                        => 0,
			'start'                          => '',
			'end'                            => '',
			'availability_period_start'      => 0,
			'availability_period_end'        => 0,
			'trim'                           => 1,
			'past_events'                    => 0,
			'past_events_bookable'           => 0,
			'days_off'                       => array(),
			'form_action'                    => 'default',
			'when_perform_form_action'       => 'on_submit',
			'redirect_url_by_activity'       => array(),
			'redirect_url_by_group_category' => array(),
			'minTime'                        => '00:00',
			'maxTime'                        => '00:00'
		),
		'login' => array(
			'automatic_login'        => 1,
			'login_button'           => 1,
			'login_first'            => 0,
			'login_button_label'     => esc_html__( 'Log in', 'booking-activities' ),
			'register_button_label'  => esc_html__( 'Register', 'booking-activities' ),
			'min_password_strength'  => 4,
			'generate_password'      => 0,
			'remember'               => 0,
			'send_new_account_email' => 1,
			'new_user_role'          => 'default',
			'displayed_fields'       => array_merge( $login_defaults[ 'displayed' ], $login_type_defaults[ 'displayed' ], $register_defaults[ 'displayed' ] ),
			'required_fields'        => array_merge( $login_defaults[ 'required' ], $login_type_defaults[ 'required' ], $register_defaults[ 'required' ] )
		),
		'free_text'   => array(),
		'quantity'    => array(),
		'terms'       => array(),
		'total_price' => array( 'price_breakdown' => 1 ),
		'submit'      => array()
	), $field_name );
	
	if( $field_name ) {
		return isset( $fields_meta[ $field_name ] ) ? $fields_meta[ $field_name ] : array();
	}
	
	return $fields_meta;
}


/**
 * Get available form actions
 * @since 1.7.17
 * @return array
 */
function bookacti_get_available_form_actions() {
	return apply_filters( 'bookacti_form_action_options', array( 
		'default' => '', 
		'redirect_to_url' => ''
	));
}


/**
 * Get available form submit triggers
 * @since 1.7.17
 * @return array
 */
function bookacti_get_available_form_action_triggers() {
	return apply_filters( 'bookacti_when_perform_form_action_options', array( 
		'on_submit' => '', 
		'on_event_click' => ''
	));
}


/**
 * Format field data according to its type
 * @since 1.5.0
 * @version 1.14.0
 * @param array|string $raw_field_data
 * @param $context "view" or "edit"
 * @return array
 */
function bookacti_format_form_field_data( $raw_field_data, $context = 'view' ) {
	// Check if name and type are set
	if( ! is_array( $raw_field_data ) || empty( $raw_field_data[ 'name' ] ) || empty( $raw_field_data[ 'type' ] ) ) { return array(); }
	
	$default_data = bookacti_get_default_form_fields_data( $raw_field_data[ 'name' ] );
	$default_meta = bookacti_get_default_form_fields_meta( $raw_field_data[ 'name' ] );
	if( ! $default_data ) { return array(); }
	
	$field_data	= array();
	$field_meta	= array();
	
	// Format field-specific data and metadata
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$booleans_to_check = array( 'groups_only', 'groups_single_events', 'multiple_bookings', 'bookings_only', 'trim', 'past_events', 'past_events_bookable' );
		foreach( $booleans_to_check as $key ) {
			if( ! isset( $raw_field_data[ $key ] ) ) { continue; }
			$field_meta[ $key ] = in_array( $raw_field_data[ $key ], array( 1, '1', true, 'true', 'yes', 'ok' ), true ) ? 1 : 0;
		}
		
		$field_meta[ 'id' ]    = isset( $raw_field_data[ 'id' ] ) ? sanitize_title_with_dashes( $raw_field_data[ 'id' ] ) : $default_meta[ 'id' ];
		$field_meta[ 'class' ] = isset( $raw_field_data[ 'class' ] ) ? sanitize_text_field( $raw_field_data[ 'class' ] ) : $default_meta[ 'class' ];
		
		$field_meta[ 'method' ]            = isset( $raw_field_data[ 'method' ] ) && in_array( $raw_field_data[ 'method' ], array_keys( bookacti_get_available_booking_methods() ), true ) ? $raw_field_data[ 'method' ] : $default_meta[ 'method' ];
		$field_meta[ 'hide_availability' ] = isset( $raw_field_data[ 'hide_availability' ] ) && is_numeric( $raw_field_data[ 'hide_availability' ] ) ? max( min( intval( $raw_field_data[ 'hide_availability' ] ), 100 ), 0 ) : $default_meta[ 'hide_availability' ];
	
		$field_meta[ 'calendars' ] = isset( $raw_field_data[ 'calendars' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'calendars' ] ) : $default_meta[ 'calendars' ];
		
		$had_activities             = ! empty( $raw_field_data[ 'activities' ] );
		$activities                 = isset( $raw_field_data[ 'activities' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'activities' ] ) : $default_meta[ 'activities' ];
		$field_meta[ 'activities' ] = $activities && is_array( $activities ) ? $activities : ( $had_activities ? array( 'none' ) : array() );
		
		$had_group_categories             = ! empty( $raw_field_data[ 'group_categories' ] );
		$group_categories                 = isset( $raw_field_data[ 'group_categories' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'group_categories' ] ) : $default_meta[ 'group_categories' ];
		$field_meta[ 'group_categories' ] = $group_categories && is_array( $group_categories ) ? $group_categories : ( $had_group_categories ? array( 'none' ) : array() );
		
		$status                  = isset( $raw_field_data[ 'status' ] ) ? ( is_string( $raw_field_data[ 'status' ] ) ? array( $raw_field_data[ 'status' ] ) : $raw_field_data[ 'status' ] ) : $default_meta[ 'status' ];
		$field_meta[ 'status' ]  = is_array( $status ) ? array_intersect( $status, array_keys( bookacti_get_booking_state_labels() ) ) : $default_meta[ 'status' ];
		$field_meta[ 'user_id' ] = isset( $raw_field_data[ 'user_id' ] ) && is_numeric( $raw_field_data[ 'user_id' ] ) ? intval( $raw_field_data[ 'user_id' ] ) : ( isset( $raw_field_data[ 'user_id' ] ) && in_array( $raw_field_data[ 'user_id' ], array( 0, '0', 'current' ), true ) ? $raw_field_data[ 'user_id' ] : $default_meta[ 'user_id' ] );
		
		$field_meta[ 'start' ]    = isset( $raw_field_data[ 'start' ] ) && bookacti_sanitize_date( $raw_field_data[ 'start' ] ) ? bookacti_sanitize_date( $raw_field_data[ 'start' ] ) : $default_meta[ 'start' ];
		$field_meta[ 'end' ]      = isset( $raw_field_data[ 'end' ] ) && bookacti_sanitize_date( $raw_field_data[ 'end' ] ) ? bookacti_sanitize_date( $raw_field_data[ 'end' ] ) : $default_meta[ 'end' ];
		$field_meta[ 'days_off' ] = isset( $raw_field_data[ 'days_off' ] ) && is_array( $raw_field_data[ 'days_off' ] ) ? bookacti_sanitize_days_off( $raw_field_data[ 'days_off' ] ) : $default_meta[ 'days_off' ];
		$field_meta[ 'availability_period_start' ] = isset( $raw_field_data[ 'availability_period_start' ] ) && is_numeric( $raw_field_data[ 'availability_period_start' ] ) ? intval( $raw_field_data[ 'availability_period_start' ] ) : $default_meta[ 'availability_period_start' ];
		$field_meta[ 'availability_period_end' ]   = isset( $raw_field_data[ 'availability_period_end' ] ) && is_numeric( $raw_field_data[ 'availability_period_end' ] ) ? intval( $raw_field_data[ 'availability_period_end' ] ) : $default_meta[ 'availability_period_end' ];
		
		$field_meta[ 'form_action' ]                    = isset( $raw_field_data[ 'form_action' ] ) && in_array( $raw_field_data[ 'form_action' ], array_keys( bookacti_get_available_form_actions() ), true ) ? $raw_field_data[ 'form_action' ] : $default_meta[ 'form_action' ];
		$field_meta[ 'when_perform_form_action' ]       = isset( $raw_field_data[ 'when_perform_form_action' ] ) && in_array( $raw_field_data[ 'when_perform_form_action' ], array_keys( bookacti_get_available_form_action_triggers() ), true ) ? $raw_field_data[ 'when_perform_form_action' ] : $default_meta[ 'when_perform_form_action' ];
		$field_meta[ 'redirect_url_by_activity' ]       = isset( $raw_field_data[ 'redirect_url_by_activity' ] ) && is_array( $raw_field_data[ 'redirect_url_by_activity' ] ) ? array_map( 'esc_url', $raw_field_data[ 'redirect_url_by_activity' ] ) : $default_meta[ 'redirect_url_by_activity' ];
		$field_meta[ 'redirect_url_by_group_category' ] = isset( $raw_field_data[ 'redirect_url_by_group_category' ] ) && is_array( $raw_field_data[ 'redirect_url_by_group_category' ] ) ? array_map( 'esc_url', $raw_field_data[ 'redirect_url_by_group_category' ] ) : $default_meta[ 'redirect_url_by_group_category' ];
		
		// The Calendar field display data are the same as the booking system's, we can safely use bookacti_format_booking_system_display_data
		$display_data = bookacti_format_booking_system_display_data( $raw_field_data );
		foreach( $default_meta as $default_meta_key => $default_meta_value ) {
			if( isset( $display_data[ $default_meta_key ] ) ) {
				$field_meta[ $default_meta_key ] = $display_data[ $default_meta_key ];
			}
		}
		
	} else if( $raw_field_data[ 'name' ] === 'login' ) {
		// Empty default strings in edit mode
		if( $context === 'edit' ) {
			$default_meta[ 'login_button_label' ] = $default_meta[ 'register_button_label' ] = '';
		}
		
		// Format meta values
		$keys_by_type = array( 
			'bool'   => array( 'automatic_login', 'generate_password', 'send_new_account_email', 'login_first', 'login_button', 'remember' ),
			'int'    => array( 'min_password_strength' ),
			'str_id' => array( 'new_user_role' ),
			'str'    => array( 'login_button_label', 'register_button_label' )
		);
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
		
		// Translate texts
		$field_meta[ 'login_button_label' ]    = ! empty( $raw_field_data[ 'login_button_label' ] ) ? ( $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ 'login_button_label' ] ) : $raw_field_data[ 'login_button_label' ] ) : $default_meta[ 'login_button_label' ];
		$field_meta[ 'register_button_label' ] = ! empty( $raw_field_data[ 'register_button_label' ] ) ? ( $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ 'register_button_label' ] ) : $raw_field_data[ 'register_button_label' ] ) : $default_meta[ 'register_button_label' ];
		
		// Treat 'required_fields' and 'displayed_fields' field meta as a common field data
		$default_data[ 'displayed_fields' ] = $default_meta[ 'displayed_fields' ]; unset( $default_meta[ 'displayed_fields' ] );
		$default_data[ 'required_fields' ]  = $default_meta[ 'required_fields' ]; unset( $default_meta[ 'required_fields' ] );
		
		// Format common values (specific cases)
		// Format label, placeholder and tip
		$register_defaults   = bookacti_get_register_fields_default_data();
		$login_defaults      = bookacti_get_login_fields_default_data();
		$login_type_defaults = bookacti_get_login_type_field_default_options();
		
		$fields = array( 'label', 'placeholder', 'tip', 'displayed_fields', 'required_fields' );
		foreach( $fields as $field ) {
			$raw_field_data[ $field ] = isset( $raw_field_data[ $field ] ) ? maybe_unserialize( $raw_field_data[ $field ] ) : false;
			$is_translatable = in_array( $field, array( 'label', 'placeholder', 'tip' ), true );
			if( is_array( $raw_field_data[ $field ] ) ) {
				// Format register
				$register_fields = array();
				foreach( $register_defaults as $register_field_name => $register_default ) {
					$register_fields[ $register_field_name ] = $is_translatable && $context === 'edit' ? '' : $default_data[ $field ][ $register_field_name ];
					if( ! empty( $raw_field_data[ $field ][ $register_field_name ] ) ) {
						$register_fields[ $register_field_name ] = $is_translatable && $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ $field ][ $register_field_name ] ) : $raw_field_data[ $field ][ $register_field_name ];
					}
				}
				
				// Format login fields
				$login_fields = array();
				foreach( $login_defaults as $login_field_name => $login_field_default ) {
					$login_fields[ $login_field_name ] = $is_translatable && $context === 'edit' ? '' : $default_data[ $field ][ $login_field_name ];
					if( ! empty( $raw_field_data[ $field ][ $login_field_name ] ) ) {
						$login_fields[ $login_field_name ] = $is_translatable && $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ $field ][ $login_field_name ] ) : $raw_field_data[ $field ][ $login_field_name ];
					}
				}
				
				// Format login type
				$login_types = array();
				foreach( $login_type_defaults as $login_type_name => $login_type_default ) {
					$login_types[ $login_type_name ] = $is_translatable && $context === 'edit' ? '' : $default_data[ $field ][ $login_type_name ];
					if( ! empty( $raw_field_data[ $field ][ $login_type_name ] ) ) {
						$login_types[ $login_type_name ] = $is_translatable && $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ $field ][ $login_type_name ] ) : $raw_field_data[ $field ][ $login_type_name ];
					}
				}
				
				// Merge register fields
				$field_data[ $field ] = array_merge( $login_fields, $login_types, $register_fields );

			} else {
				$field_data[ $field ] = $is_translatable && $context === 'edit' ? array() : $default_data[ $field ];
			}
		}
		
	} else if( $raw_field_data[ 'name' ] === 'quantity' ) {
	} else if( $raw_field_data[ 'name' ] === 'terms' ) {
		// Format common values (specific cases)
		$field_data[ 'label' ] = $raw_field_data[ 'label' ];
		
	} else if( $raw_field_data[ 'name' ] === 'submit' ) {
		// Empty default strings in edit mode
		if( $context === 'edit' ) { $default_data[ 'value' ] = ''; }
		// Format Submit button label
		$keys_by_type = array( 'str' => array( 'value' ) );
		$field_data = bookacti_sanitize_values( $default_data, $raw_field_data, $keys_by_type );
		// Translate texts
		if( $context !== 'edit' ) {
			if( ! empty( $raw_field_data[ 'value' ] ) ) { $field_data[ 'value' ] = apply_filters( 'bookacti_translate_text', $raw_field_data[ 'value' ] ); }
		}
		
	} else if( $raw_field_data[ 'name' ] === 'free_text' ) {
		// Format common values (specific cases)
		if( isset( $raw_field_data[ 'value' ] ) ) {
			// Translate texts
			if( $context !== 'edit' ) {
				$field_data[ 'value' ] = $raw_field_data[ 'value' ] ? wpautop( apply_filters( 'bookacti_translate_text', $raw_field_data[ 'value' ], '', true, array( 'string_name' => ! empty( $raw_field_data[ 'field_id' ] ) ? 'Form field #' . $raw_field_data[ 'field_id' ] . ' - value' : '' ) ) ) : $raw_field_data[ 'value' ];
			} else {
				$field_data[ 'value' ] = $raw_field_data[ 'value' ];
			}
		}
	} else if( $raw_field_data[ 'name' ] === 'total_price' ) {
		// Format meta values
		$keys_by_type = array( 'bool' => array( 'price_breakdown' ) );
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
	}
	
	// Empty default translatable strings in edit mode
	$translatable_keys = array( 'title', 'label', 'placeholder', 'tip' );
	if( $context === 'edit' ) {
		foreach( $translatable_keys as $key ) {
			if( ! empty( $default_data[ $key ] ) ) { $default_data[ $key ] = is_array( $default_data[ $key ] ) ? array() : ''; }
		}
	}
	
	// Format common values
	$keys_by_type = array( 
		'int'    => array( 'field_id', 'form_id' ),
		'str_id' => array( 'name', 'type', 'id' ),
		'str'    => array( 'title', 'label', 'class', 'value', 'placeholder', 'tip' ),
		'array'  => array( 'options' ),
		'bool'   => array( 'compulsory', 'default', 'unique', 'required' )
	);
	$formatted_field_data = bookacti_sanitize_values( $default_data, $raw_field_data, $keys_by_type, $field_data );
	
	// Translate texts
	foreach( $translatable_keys as $key ) {
		if( is_string( $formatted_field_data[ $key ] ) ) { 
			$formatted_field_data[ $key ] = ! empty( $raw_field_data[ $key ] ) ? ( $context !== 'edit' ? apply_filters( 'bookacti_translate_text', $raw_field_data[ $key ] ) : $raw_field_data[ $key ] ) : ( isset( $field_data[ $key ] ) ? $field_data[ $key ] : $default_data[ $key ] );
		}
	}
	
	// Keep only meta declared in default meta
	foreach( $default_meta as $name => $default ) {
		if( ! isset( $field_meta[ $name ] ) ) { $field_meta[ $name ] = $default; }
	}
	$formatted_field_meta = array_intersect_key( $field_meta, $default_meta );
		
	return apply_filters( 'bookacti_formatted_field_data', array_merge( $formatted_field_data, $formatted_field_meta ), $raw_field_data, $context );
}


/**
 * Sanitize field data according to its type
 * @since 1.5.0
 * @version 1.14.0
 * @param array|string $raw_field_data
 * @return array
 */
function bookacti_sanitize_form_field_data( $raw_field_data ) {
	// Check if name and type are set
	if( ! is_array( $raw_field_data ) || empty( $raw_field_data[ 'name' ] ) || empty( $raw_field_data[ 'type' ] ) ) { return array(); }
	
	$default_data = bookacti_get_default_form_fields_data( $raw_field_data[ 'name' ] );
	$default_meta = bookacti_get_default_form_fields_meta( $raw_field_data[ 'name' ] );
	
	if( ! $default_data ) { return array(); }
	
	$field_data	= array();
	$field_meta	= array();
	
	// Sanitize field-specific data and metadata
	if( $raw_field_data[ 'name' ] === 'calendar' ) {
		$booleans_to_check = array( 'groups_only', 'groups_single_events', 'multiple_bookings', 'bookings_only', 'trim', 'past_events', 'past_events_bookable' );
		foreach( $booleans_to_check as $key ) {
			if( ! isset( $raw_field_data[ $key ] ) ) { $field_meta[ $key ] = $default_meta[ $key ]; continue; }
			$field_meta[ $key ] = in_array( $raw_field_data[ $key ], array( 1, '1', true, 'true', 'yes', 'ok' ), true ) ? 1 : 0;
		}
		
		$field_meta[ 'id' ]    = isset( $raw_field_data[ 'id' ] ) && $raw_field_data[ 'id' ] !== '' ? sanitize_title_with_dashes( $raw_field_data[ 'id' ] ) : $default_meta[ 'id' ];
		$field_meta[ 'class' ] = isset( $raw_field_data[ 'class' ] ) && $raw_field_data[ 'class' ] !== '' ? sanitize_text_field( $raw_field_data[ 'class' ] ) : $default_meta[ 'class' ];
		
		$field_meta[ 'method' ]            = isset( $raw_field_data[ 'method' ] ) && in_array( $raw_field_data[ 'method' ], array_keys( bookacti_get_available_booking_methods() ), true ) ? $raw_field_data[ 'method' ] : $default_meta[ 'method' ];
		$field_meta[ 'hide_availability' ] = isset( $raw_field_data[ 'hide_availability' ] ) && is_numeric( $raw_field_data[ 'hide_availability' ] ) ? max( min( intval( $raw_field_data[ 'hide_availability' ] ), 100 ), 0 ) : $default_meta[ 'hide_availability' ];
		
		$field_meta[ 'calendars' ] = isset( $raw_field_data[ 'calendars' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'calendars' ] ) : $default_meta[ 'calendars' ];
		
		$had_activities             = ! empty( $raw_field_data[ 'activities' ] );
		if( isset( $raw_field_data[ 'activities' ] ) && ( $raw_field_data[ 'activities' ] === 'all' || ( is_array( $raw_field_data[ 'activities' ] ) && in_array( 'all', $raw_field_data[ 'activities' ], true ) ) ) ) { $had_activities = false; }
		$activities                 = isset( $raw_field_data[ 'activities' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'activities' ] ) : $default_meta[ 'activities' ];
		$field_meta[ 'activities' ] = $activities && is_array( $activities ) ? $activities : ( $had_activities ? array( 'none' ) : array() );
		
		$had_group_categories             = ! empty( $raw_field_data[ 'group_categories' ] );
		if( isset( $raw_field_data[ 'group_categories' ] ) && ( $raw_field_data[ 'group_categories' ] === 'all' || ( is_array( $raw_field_data[ 'group_categories' ] ) && in_array( 'all', $raw_field_data[ 'group_categories' ], true ) ) ) ) { $had_group_categories = false; }
		$group_categories                 = isset( $raw_field_data[ 'group_categories' ] ) ? bookacti_ids_to_array( $raw_field_data[ 'group_categories' ] ) : $default_meta[ 'group_categories' ];
		$field_meta[ 'group_categories' ] = $group_categories && is_array( $group_categories ) ? $group_categories : ( $had_group_categories ? array( 'none' ) : array() );
		
		$status                  = isset( $raw_field_data[ 'status' ] ) ? ( is_string( $raw_field_data[ 'status' ] ) ? array( $raw_field_data[ 'status' ] ) : $raw_field_data[ 'status' ] ) : $default_meta[ 'status' ];
		$field_meta[ 'status' ]  = is_array( $status ) ? array_intersect( $status, array_keys( bookacti_get_booking_state_labels() ) ) : $default_meta[ 'status' ];
		$field_meta[ 'user_id' ] = isset( $raw_field_data[ 'user_id' ] ) && is_numeric( $raw_field_data[ 'user_id' ] ) ? intval( $raw_field_data[ 'user_id' ] ) : ( isset( $raw_field_data[ 'user_id' ] ) && in_array( $raw_field_data[ 'user_id' ], array( 0, '0', 'current' ), true ) ? $raw_field_data[ 'user_id' ] : $default_meta[ 'user_id' ] );
		
		$field_meta[ 'start' ]    = isset( $raw_field_data[ 'start' ] ) && bookacti_sanitize_date( $raw_field_data[ 'start' ] ) ? bookacti_sanitize_date( $raw_field_data[ 'start' ] ) : $default_meta[ 'start' ];
		$field_meta[ 'end' ]      = isset( $raw_field_data[ 'end' ] ) && bookacti_sanitize_date( $raw_field_data[ 'end' ] ) ? bookacti_sanitize_date( $raw_field_data[ 'end' ] ) : $default_meta[ 'end' ];
		$field_meta[ 'days_off' ] = isset( $raw_field_data[ 'days_off' ] ) && is_array( $raw_field_data[ 'days_off' ] ) ? bookacti_sanitize_days_off( $raw_field_data[ 'days_off' ] ) : $default_meta[ 'days_off' ];
		$field_meta[ 'availability_period_start' ] = isset( $raw_field_data[ 'availability_period_start' ] ) && is_numeric( $raw_field_data[ 'availability_period_start' ] ) ? intval( $raw_field_data[ 'availability_period_start' ] ) : $default_meta[ 'availability_period_start' ];
		$field_meta[ 'availability_period_end' ]   = isset( $raw_field_data[ 'availability_period_end' ] ) && is_numeric( $raw_field_data[ 'availability_period_end' ] ) ? intval( $raw_field_data[ 'availability_period_end' ] ) : $default_meta[ 'availability_period_end' ];
		
		// Switch start and end if start > end
		if( $field_meta[ 'start' ] && $field_meta[ 'end' ] ) {
			$start_dt = new DateTime( $field_meta[ 'start' ] );
			$end_dt = new DateTime( $field_meta[ 'end' ] );
			if( $start_dt > $end_dt ) {
				$start = $field_meta[ 'start' ];
				$field_meta[ 'start' ] = $field_meta[ 'end' ];
				$field_meta[ 'end' ]   = $start;
			}
		}
		
		// Switch availability_period_start and availability_period_end if availability_period_start > availability_period_end
		if( $field_meta[ 'availability_period_start' ] && $field_meta[ 'availability_period_end' ] 
		&&  $field_meta[ 'availability_period_start' ] > $field_meta[ 'availability_period_end' ] ) {
			$availability_period_start = $field_meta[ 'availability_period_start' ];
			$field_meta[ 'availability_period_start' ] = $field_meta[ 'availability_period_end' ];
			$field_meta[ 'availability_period_end' ]   = $availability_period_start;
		}
		
		$field_meta[ 'form_action' ]                    = isset( $raw_field_data[ 'form_action' ] ) && in_array( $raw_field_data[ 'form_action' ], array_keys( bookacti_get_available_form_actions() ), true ) ? $raw_field_data[ 'form_action' ] : $default_meta[ 'form_action' ];
		$field_meta[ 'when_perform_form_action' ]       = isset( $raw_field_data[ 'when_perform_form_action' ] ) && in_array( $raw_field_data[ 'when_perform_form_action' ], array_keys( bookacti_get_available_form_action_triggers() ), true ) ? $raw_field_data[ 'when_perform_form_action' ] : $default_meta[ 'when_perform_form_action' ];
		$field_meta[ 'redirect_url_by_activity' ]       = isset( $raw_field_data[ 'redirect_url_by_activity' ] ) && is_array( $raw_field_data[ 'redirect_url_by_activity' ] ) ? array_map( 'stripslashes', array_map( 'esc_url_raw', $raw_field_data[ 'redirect_url_by_activity' ] ) ) : $default_meta[ 'redirect_url_by_activity' ];
		$field_meta[ 'redirect_url_by_group_category' ] = isset( $raw_field_data[ 'redirect_url_by_group_category' ] ) && is_array( $raw_field_data[ 'redirect_url_by_group_category' ] ) ? array_map( 'stripslashes', array_map( 'esc_url_raw', $raw_field_data[ 'redirect_url_by_group_category' ] ) ) : $default_meta[ 'redirect_url_by_group_category' ];
		
		// The Calendar field display data are the same as the booking system's, we can safely use bookacti_sanitize_booking_system_display_data
		$display_data = bookacti_sanitize_booking_system_display_data( $raw_field_data );
		foreach( $default_meta as $default_meta_key => $default_meta_value ) {
			if( isset( $display_data[ $default_meta_key ] ) ) {
				$field_meta[ $default_meta_key ] = $display_data[ $default_meta_key ];
			}
		}
		
	} else if( $raw_field_data[ 'name' ] === 'login' ) {
		// Sanitize meta values
		$keys_by_type = array( 
			'bool'   => array( 'automatic_login', 'generate_password', 'send_new_account_email', 'login_first', 'login_button', 'remember' ),
			'int'    => array( 'min_password_strength' ),
			'str_id' => array( 'new_user_role' ),
			'str'    => array( 'login_button_label', 'register_button_label' )
		);
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
		
		// Treat 'required_fields' and 'displayed_fields' field meta as a common field data
		$default_data[ 'displayed_fields' ] = $default_meta[ 'displayed_fields' ]; unset( $default_meta[ 'displayed_fields' ] );
		$default_data[ 'required_fields' ]  = $default_meta[ 'required_fields' ]; unset( $default_meta[ 'required_fields' ] );

		// Sanitize common values (specific cases)
		// Sanitize label, placeholder and tip
		$register_defaults   = bookacti_get_register_fields_default_data();
		$login_defaults      = bookacti_get_login_fields_default_data();
		$login_type_defaults = bookacti_get_login_type_field_default_options();
		$fields = array( 'label', 'placeholder', 'tip', 'displayed_fields', 'required_fields' );
		foreach( $fields as $field ) {
			$raw_field_data[ $field ] = isset( $raw_field_data[ $field ] ) ? maybe_unserialize( $raw_field_data[ $field ] ) : false;
			$is_translatable = in_array( $field, array( 'label', 'placeholder', 'tip' ), true );
			$raw_field_data[ $field ] = maybe_unserialize( $raw_field_data[ $field ] );
			
			if( is_array( $raw_field_data[ $field ] ) ) {
				// Sanitize register fields
				$register_fields = array();
				foreach( $register_defaults as $register_field_name => $register_default ) {
					$register_fields[ $register_field_name ] = isset( $raw_field_data[ $field ][ $register_field_name ] ) ? stripslashes( $raw_field_data[ $field ][ $register_field_name ] ) : ( $is_translatable ? '' : $default_data[ $field ][ $register_field_name ] );
				}
				
				// Sanitize login fields
				$login_fields = array();
				foreach( $login_defaults as $login_field_name => $login_field_default ) {
					$login_fields[ $login_field_name ] = isset( $raw_field_data[ $field ][ $login_field_name ] ) ? stripslashes( $raw_field_data[ $field ][ $login_field_name ] ) : ( $is_translatable ? '' : $default_data[ $field ][ $login_field_name ] );
				}
				
				// Sanitize login type
				$login_types = array();
				foreach( $login_type_defaults as $login_type_name => $login_type_default ) {
					$login_types[ $login_type_name ] = isset( $raw_field_data[ $field ][ $login_type_name ] ) ? stripslashes( $raw_field_data[ $field ][ $login_type_name ] ) : ( $is_translatable ? '' : $default_data[ $field ][ $login_type_name ] );
				}
				
				// Merge register fields
				$field_data[ $field ] = array_merge( $login_fields, $login_types, $register_fields );
			} else {
				$field_data[ $field ] = array();
			}
		}
		
	} else if( $raw_field_data[ 'name' ] === 'quantity' ) {
	} else if( $raw_field_data[ 'name' ] === 'terms' ) {
		if( isset( $raw_field_data[ 'label' ] ) ) { $field_data[ 'label' ] = bookacti_sanitize_form_field_free_text( $raw_field_data[ 'label' ] ); }
		
	} else if( $raw_field_data[ 'name' ] === 'submit' ) {
		$field_data[ 'value' ] = isset( $raw_field_data[ 'value' ] ) ? sanitize_text_field( $raw_field_data[ 'value' ] ) : '';
	
	} else if( $raw_field_data[ 'name' ] === 'free_text' ) {
		$field_data[ 'value' ] = isset( $raw_field_data[ 'value' ] ) ? bookacti_sanitize_form_field_free_text( $raw_field_data[ 'value' ] ) : '';
		
	} else if( $raw_field_data[ 'name' ] === 'total_price' ) {
		// Sanitize meta values
		$keys_by_type = array( 'bool' => array( 'price_breakdown' ) );
		$field_meta = bookacti_sanitize_values( $default_meta, $raw_field_data, $keys_by_type );
	}
	
	// Empty default translatable strings
	$translatable_keys = array( 'title', 'label', 'placeholder', 'tip' );
	foreach( $translatable_keys as $key ) {
		if( ! empty( $default_data[ $key ] ) ) { $default_data[ $key ] = is_array( $default_data[ $key ] ) ? array() : ''; }
	}
	
	// Sanitize common values
	$keys_by_type = array( 
		'int'    => array( 'field_id', 'form_id' ),
		'str_id' => array( 'name', 'type', 'id' ),
		'str'    => array( 'title', 'label', 'class', 'value', 'placeholder', 'tip' ),
		'array'  => array( 'options' ),
		'bool'   => array( 'compulsory', 'default', 'unique', 'required' )
	);
	$sanitized_raw_field_data = bookacti_sanitize_values( $default_data, $raw_field_data, $keys_by_type, $field_data );
	
	// Keep only allowed data and metadata
	$sanitized_field_data = array_intersect_key( $sanitized_raw_field_data, $default_data );
	$sanitized_field_meta = array_intersect_key( $field_meta, $default_meta );
	
	$sanitized_data = apply_filters( 'bookacti_sanitized_field_data', array_merge( $sanitized_field_data, $sanitized_field_meta ), $raw_field_data );
	
	// We must serialize arrays in addition to sanitizing them
	foreach( $sanitized_data as $key => $value ) {
		if( is_array( $value ) ) { $sanitized_data[ $key ] = maybe_serialize( $value ); }
	}
	
	return $sanitized_data;
}


/**
 * Update the metadata of a specific field
 * @since 1.5.0
 * @param array $meta
 * @param int|string $field_id_or_name If you give the field name, it must be a unique field and you must give the form id
 * @param int $form_id Required only if you give the field name instead of the field id
 * @return int|false
 */
function bookacti_update_form_field_meta( $meta, $field_id_or_name, $form_id = 0 ) {
	// If we haven't the form ID nor the field ID
	if( ! is_numeric( $field_id_or_name ) && ! $form_id ) { return false; }
	// If we have the form id but not the unique field name
	if( $form_id && ! $field_id_or_name ) { return false; }
	
	// Get field
	if( is_numeric( $field_id_or_name ) ) {
		$field	= bookacti_get_form_field( $field_id_or_name );
	} else {
		$field	= bookacti_get_form_field_by_name( $form_id, $field_id_or_name );
	}
	
	if( ! $field ) { return false; }
	
	// Sanitize calendar data
	$field_required_data	= array( 'type' => $field[ 'type' ], 'name' => $field[ 'name' ] );
	$field_raw_data			= array_merge( $field_required_data, $meta );
	$field_sanitized_data	= bookacti_sanitize_form_field_data( $field_raw_data );
	$field_sanitized_meta	= array_intersect_key( $field_sanitized_data, bookacti_get_default_form_fields_meta( $field[ 'name' ] ) );
	
	// Update calendar metadata
	if( ! $field_sanitized_meta ) { return false; }
	
	$updated = bookacti_update_metadata( 'form_field', $field[ 'field_id' ], $field_sanitized_meta );
	
	return $updated;
}


/**
 * Sanitize the values entered by the user in the form fields
 * @since 1.5.0
 * @version 1.12.4
 * @param array $values
 * @param string $field_type
 * @return array
 */
function bookacti_sanitize_form_field_values( $values, $field_type = '' ) {
	if( ! $field_type && empty( $values[ 'type' ] ) ) { return $values; }
	if( ! $field_type && ! empty( $values[ 'type' ] ) ) { $field_type = $values[ 'type' ]; }
	
	$sanitized_values = array();
	
	// Login fields
	if( $field_type === 'login' ) {
		$sanitized_values[ 'email' ]	= ! empty( $values[ 'email' ] ) ? sanitize_email( stripslashes( $values[ 'email' ] ) ) : '';
		$sanitized_values[ 'password' ]	= ! empty( $values[ 'password' ] ) ? trim( stripslashes( $values[ 'password' ] ) ) : '';
		$sanitized_values[ 'password_strength' ] = ! empty( $values[ 'password_strength' ] ) ? intval( $values[ 'password_strength' ] ) : 1;
		$sanitized_values[ 'remember' ] = ! empty( $values[ 'remember' ] ) ? 1 : 0;
		
		$login_types = bookacti_get_login_type_field_default_options();
		$sanitized_values[ 'login_type' ] = ! empty( $values[ 'login_type' ] ) && in_array( $values[ 'login_type' ], array_keys( $login_types ), true ) ? $values[ 'login_type' ] : '';
		
		$default_register_data = bookacti_get_register_fields_default_data();
		$default_register_values = array();
		foreach( $default_register_data as $field_name => $register_field ) {
			$default_register_values[ $field_name ] = $register_field[ 'value' ];
		}
		$register_keys_by_type = array( 'str' => array( 'first_name', 'last_name', 'phone' ) );
		$sanitized_register_values = bookacti_sanitize_values( $default_register_values, $values, $register_keys_by_type );
		
		$sanitized_values = array_merge( $sanitized_values, $sanitized_register_values );
	}
	
	return apply_filters( 'bookacti_sanitize_form_field_values', $sanitized_values, $values, $field_type );
}


/**
 * Display a form field
 * @since 1.5.0
 * @version 1.14.0
 * @param array $field
 * @param string $instance_id
 * @param string $context
 * @param boolean $echo
 * @return string|void
 */
function bookacti_display_form_field( $field, $instance_id = '', $context = 'display', $echo = true ) {
	if( empty( $field[ 'name' ] ) ) { return ''; }
	
	if( ! $instance_id ) { $instance_id = rand(); }
	$field_id	= ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	$field_class= 'bookacti-form-field-container';
	$field_css_data = '';
	if( ! empty( $field[ 'name' ] ) )		{ $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); $field_css_data .= ' data-field-name="' . esc_attr( $field[ 'name' ] ) . '"'; } 
	if( ! empty( $field[ 'type' ] ) )		{ $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); $field_css_data .= ' data-field-type="' . esc_attr( $field[ 'type' ] ) . '"'; } 
	if( ! empty( $field[ 'field_id' ] ) )	{ $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); $field_css_data .= ' data-field-id="' . esc_attr( $field[ 'field_id' ] ) . '"'; }
	if( ! empty( $field[ 'class' ] ) )		{ $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	ob_start();
?>
	<div class='<?php echo $field_class; ?>' id='<?php echo $field_id; ?>' <?php echo trim( $field_css_data ); ?>>
	<?php if( ! empty( $field[ 'label' ] ) && is_string( $field[ 'label' ] ) ) { ?>
		<div class='bookacti-form-field-label' >
			<label>
			<?php
				echo $field[ 'label' ];
				if( $field[ 'required' ] ) {
					echo '<span class="bookacti-required-field-indicator" title="' . esc_attr__( 'Required field', 'booking-activities' ) . '"></span>';
				}
			?>
			</label>
		<?php if( ! empty( $field[ 'tip' ] ) && is_string( $field[ 'tip' ] )) { bookacti_help_tip( esc_html( $field[ 'tip' ] ) ); } ?>
		</div>
	<?php } ?>
		<div class='bookacti-form-field-content' >
		<?php 
			do_action( 'bookacti_display_form_field_' . $field[ 'type' ], $field, $instance_id, $context ); 
		?>
		</div>
	</div>
<?php
	$html = apply_filters( 'bookacti_html_form_field_' . $field[ 'type' ], ob_get_clean(), $field, $instance_id, $context );
	if( ! $echo ) { return $html; }
	echo $html;
}


/**
 * Display a form field for the form editor
 * @since 1.5.0
 * @version 1.14.0
 * @param array $field
 * @param boolean $echo
 * @return string|void
 */
function bookacti_display_form_field_for_editor( $field, $echo = true ) {
	$field_name = esc_attr( $field[ 'name' ] );
	$field_class= 'bookacti-form-editor-field';
	if( ! empty( $field[ 'name' ] ) )     { $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); } 
	if( ! empty( $field[ 'type' ] ) )     { $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); } 
	if( ! empty( $field[ 'field_id' ] ) ) { $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! empty( $field[ 'class' ] ) )    { $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	$field[ 'id' ] = esc_attr( 'bookacti-form-editor-' . $field_name );
	if( ! $field[ 'unique' ] ) { $field[ 'id' ] .= '-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! $echo ) { ob_start(); }
	?>
	<div id='bookacti-form-editor-field-<?php echo esc_attr( $field[ 'field_id' ] ); ?>' class='<?php echo $field_class; ?>' data-field-id='<?php echo esc_attr( $field[ 'field_id' ] ); ?>' data-field-name='<?php echo esc_attr( $field[ 'name' ] ); ?>' >
		<div class='bookacti-form-editor-field-header' >
			<div class='bookacti-form-editor-field-title' >
				<h3><?php echo wp_kses_post( $field[ 'title' ] ); ?></h3>
			</div>
			<div class='bookacti-form-editor-field-actions' >
			<?php
				do_action( 'bookacti_form_editor_field_actions_before', $field );
			?>
				<div id='bookacti-edit-form-field-<?php echo esc_attr( $field[ 'field_id' ] ); ?>' class='bookacti-form-editor-field-action bookacti-edit-form-field <?php echo $field_name === 'calendar' ? 'button button-secondary' : 'dashicons dashicons-admin-generic'; ?>' title='<?php esc_attr_e( 'Change field settings', 'booking-activities' ); ?>'><?php if( $field_name === 'calendar' ) { esc_attr_e( 'Calendar settings', 'booking-activities' ); } ?></div>
			<?php if( ! $field[ 'compulsory' ] ) { ?>
				<div id='bookacti-remove-form-field-<?php echo esc_attr( $field[ 'field_id' ] ); ?>' class='bookacti-form-editor-field-action bookacti-remove-form-field dashicons dashicons-trash' title='<?php esc_attr_e( 'Remove this field', 'booking-activities' ); ?>'></div>
			<?php }
				do_action( 'bookacti_form_editor_field_actions_after', $field ); 
			?>
				<div id='bookacti-toggle-form-field-<?php echo esc_attr( $field[ 'field_id' ] ); ?>' class='bookacti-field-toggle dashicons <?php echo $field_name === 'calendar' ? 'dashicons-arrow-up' :'dashicons-arrow-down'; ?>' title='<?php esc_attr_e( 'Show / Hide', 'booking-activities' ); ?>'></div>
			</div>
		</div>
		<div class='bookacti-form-editor-field-body' style='<?php echo $field_name === 'calendar' ? '' : 'display:none;'; ?>' >
		<?php
			bookacti_display_form_field( $field, 'form-editor', 'edit' );
		?>
		</div>
	</div>
<?php
	if( ! $echo ) { return ob_get_clean(); }
}


/**
 * Validate register fields
 * @since 1.5.0
 * @version 1.14.0
 * @param array $login_values
 * @param array $login_data
 * @return array
 */
function bookacti_validate_registration( $login_values, $login_data ) {
	$return_array = array( 'messages' => array() );

	// Check email
	if( ! is_email( $login_values[ 'email' ] ) || strlen( $login_values[ 'email' ] ) > 64 ) { 
		$return_array[ 'messages' ][ 'invalid_email' ] = esc_html__( 'Invalid email address.', 'booking-activities' );
	}
	
	// Check if password exists
	if( ! $login_values[ 'password' ] && ! $login_data[ 'generate_password' ] ) {
		$return_array[ 'messages' ][ 'invalid_password' ] = esc_html__( 'Invalid password.', 'booking-activities' );
	}
	
	// Check password strength
	if( $login_values[ 'password_strength' ] < $login_data[ 'min_password_strength' ] && ! $login_data[ 'generate_password' ] ) {
		$return_array[ 'messages' ][ 'invalid_password_strength' ] = esc_html__( 'Your password is not strong enough.', 'booking-activities' );
	}
	
	// Check that required register fields are filled
	foreach( $login_data[ 'required_fields' ] as $field_name => $is_required ) {
		if( $is_required && empty( $login_values[ $field_name ] ) ) {
			if( $field_name === 'password' && ! empty( $login_values[ 'login_type' ] ) && $login_values[ 'login_type' ] === 'new_account' && $login_data[ 'generate_password' ] ) { continue; }
			$field_label = ! empty( $login_data[ 'label' ][ $field_name ] ) ? $login_data[ 'label' ][ $field_name ] : $field_name;
			/* translators: %s is the field name. */
			$return_array[ 'messages' ][ 'missing_' . $field_name ] = sprintf( esc_html__( 'The field "%s" is required.', 'booking-activities' ), $field_label );
		}
	}
	
	return apply_filters( 'bookacti_validate_registration_form', $return_array, $login_values );
}


/**
 * Register a new user through a booking form
 * @since 1.5.0
 * @version 1.8.9
 * @param array $login_values
 * @param array $login_data
 * @return WP_User|array
 */
function bookacti_register_a_new_user( $login_values, $login_data ) {
	
	$return_array = array( 'status' => 'failed' );
	
	$resgister_response = bookacti_validate_registration( $login_values, $login_data );

	if( count( $resgister_response[ 'messages' ] ) ) {
		$return_array[ 'error' ] = 'invalid_register_field';
		$return_array[ 'message' ] = implode( '</li><li>', $resgister_response[ 'messages' ] );
		return $return_array;
	}

	// Generate username
	$username = ! empty( $login_values[ 'first_name' ] ) ? $login_values[ 'first_name' ] . ' ' : '';
	$username .= ! empty( $login_values[ 'last_name' ] ) ? $login_values[ 'last_name' ] : '';
	if( ! trim( $username ) ) {
		$username = substr( $login_values[ 'email' ], 0, strpos( $login_values[ 'email' ], '@' ) );
	}
	$username = sanitize_title_with_dashes( $username );
	$username_base = strlen( $username ) > 60 ? substr( $username, 0, 50 ) : $username;
	$i = 2;
	while( username_exists( $username ) ) {
		$username = $username_base . '-' . $i;
		++$i;
	}

	// Let third party modify initial user data
	$new_user_data = apply_filters( 'bookacti_register_user_data', array(
		'user_login'	=> $username,
		'user_pass'		=> ! empty( $login_data[ 'generate_password' ] ) ? wp_generate_password( 24 ) : $login_values[ 'password' ],
		'user_email'	=> $login_values[ 'email' ],
		'first_name'	=> ! empty( $login_values[ 'first_name' ] ) ? $login_values[ 'first_name' ] : '',
		'last_name'		=> ! empty( $login_values[ 'last_name' ] ) ? $login_values[ 'last_name' ] : '',
		'role'			=> $login_data[ 'new_user_role' ] === 'default' ? get_option( 'default_role' ) : $login_data[ 'new_user_role' ]
	), $login_values );

	// Create the user
	$user_id = wp_insert_user( $new_user_data );
	if( is_wp_error( $user_id ) ) { 
		$return_array[ 'error' ]	= $user_id->get_error_code();
		$return_array[ 'message' ]	= $user_id->get_error_message();
		return $return_array;
	}
	$user = get_user_by( 'id', $user_id );

	// Insert user metadata
	$user_meta = array();
	if( ! empty( $login_values[ 'phone' ] ) ) { $user_meta[ 'phone' ] = $login_values[ 'phone' ]; }
	$user_meta = apply_filters( 'bookacti_register_user_metadata', $user_meta, $user, $login_values );
	foreach( $user_meta as $meta_key => $meta_value ) {
		update_user_meta( $user_id, $meta_key, $meta_value );
	}

	// Send the welcome email
	$user_registered_notify = apply_filters( 'bookacti_new_registered_user_notify', empty( $login_data[ 'send_new_account_email' ] ) ? 'admin' : 'both', $user, $login_values );
	bookacti_send_new_user_notification( $user_id, $user_registered_notify, 1 );
	
	return apply_filters( 'bookacti_new_registered_user', $user, $login_values, $login_data );
}


/**
 * Validate login fields
 * @since 1.5.0
 * @version 1.5.1
 * @param array $login_values
 * @param boolean $require_authentication Whether to authenticate the user
 * @return WP_User|array
 */
function bookacti_validate_login( $login_values, $require_authentication = true ) {
		
	$return_array = array( 'status' => 'failed' );

	// Check if email is correct
	$user = get_user_by( 'email', $login_values[ 'email' ] );
	if( ! $user ) { 
		$return_array[ 'error' ]	= 'user_not_found';
		$return_array[ 'message' ]	= esc_html__( 'This email address doesn\'t match any account.', 'booking-activities' );
		return $return_array;
	}

	// Check if password is correct
	if( $require_authentication ) {
		$user = wp_authenticate( $user->user_email, $login_values[ 'password' ] );
		if( ! is_a( $user, 'WP_User' ) ) { 
			$return_array[ 'error' ]	= 'wrong_password';
			$return_array[ 'message' ]	= esc_html__( 'The password is incorrect. Try again.', 'booking-activities' );
			return $return_array;
		}
	}
	
	return apply_filters( 'bookacti_validate_login_form', $user, $login_values );
}


/**
 * Validate form fields according to values received with $_POST
 * @since 1.7.0
 * @version 1.9.0
 * @param int $form_id
 * @param array $fields_data
 * @return array
 */
function bookacti_validate_form_fields( $form_id, $fields_data = array() ) {
	// Get form data
	if( $form_id && ! $fields_data ) { 
		$fields_data = bookacti_get_form_fields_data( $form_id );
	}
	
	$validated = array( 
		'status' => 'success',
		'messages' => array()
	);
	
	// Make sure that form data exist
	if( ! $fields_data ) { 
		$validated[ 'status' ]	= 'failed';
		$validated[ 'messages' ][ 'invalid_form_id' ] = esc_html__( 'Invalid form ID.', 'booking-activities' );
		
	} else {
		// Validate terms
		$has_terms = false;
		foreach( $fields_data as $field_data ) {
			if( $field_data[ 'name' ] === 'terms' ) { 
				$has_terms = true;
				break;
			}
		}
		if( $has_terms && empty( $_POST[ 'terms' ] ) ) {
			$validated[ 'status' ]	= 'failed';
			$validated[ 'messages' ][ 'terms_not_agreed' ] = esc_html__( 'You must agree to the terms and conditions.', 'booking-activities' );
		}
	}
	
	return apply_filters( 'bookacti_validate_form_fields', $validated, $form_id, $fields_data );
}



/* FIELD ORDER */

/**
 * Sanitize a field order array
 * @since 1.5.0
 * @version 1.5.2
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
	$diff	= array_diff( $existing_field_ids, $intersect );
	$merge	= array_merge( $intersect, $diff );
	
	// Sanitize strings to integers
	$map	= array_map( 'intval', $merge );
	$filter = array_filter( $map );
	
	return array_values( $filter );
}


/**
 * Get form fields order
 * @since 1.12.0
 * @version 1.12.3
 * @param int $form_id
 * @return array
 */
function bookacti_get_form_fields_order( $form_id ) {
	$form_id = intval( $form_id );
	$fields_order = wp_cache_get( 'form_fields_order_' . $form_id, 'bookacti' );
	if( $form_id && ! $fields_order ) {
		$fields_order = bookacti_ids_to_array( bookacti_get_metadata( 'form', $form_id, 'field_order', true ) );
		wp_cache_set( 'form_fields_order_' . $form_id, $fields_order, 'bookacti' );
	}
	return $fields_order ? $fields_order : array();
}


/**
 * Sort fields array by field order
 * @since 1.5.0
 * @version 1.12.0
 * @param int $form_id
 * @param array $fields
 * @param boolean $remove_unordered_fields Whether to remove the fields that are not in the field order array 
 * @param boolean $keep_keys Whether to keep the keys
 * @return array
 */
function bookacti_sort_form_fields_array( $form_id, $fields, $remove_unordered_fields = false, $keep_keys = false ) {
	// Sort form fields by customer custom order
	$field_order = bookacti_get_form_fields_order( $form_id );
	
	if( $field_order ) { 
		$ordered_fields = array();
		$remaining_fields = $fields;
		foreach( $field_order as $field_id ) {
			foreach( $fields as $i => $field ) {
				if( $field[ 'field_id' ] !== $field_id ) { continue; }
				if( $keep_keys ) { $ordered_fields[ $i ] = $fields[ $i ]; }
				else { $ordered_fields[] = $fields[ $i ]; }
				unset( $remaining_fields[ $i ] );
				break;
			}
		}

		// Add the remaining unordered fields (if any)
		if( ! $remove_unordered_fields && $remaining_fields ) {
			foreach( $remaining_fields as $i => $remaining_field ) {
				if( $keep_keys ) { $ordered_fields[ $i ] = $remaining_field; }
				else { $ordered_fields[] = $remaining_field; }
			}
		}
	} else {
		$ordered_fields = $keep_keys ? $fields : array_values( $fields );
	}
	
	return apply_filters( 'bookacti_ordered_form_fields', $ordered_fields, $form_id, $fields, $remove_unordered_fields, $keep_keys );
}




// LOGIN / REGISTRATION

/**
 * Get login fields default data
 * @since 1.6.0
 * @version 1.12.4
 * @return array
 */
function bookacti_get_login_fields_default_data() {
	$defaults = array(
		'email' => array( 
			'name'			=> 'email', 
			'type'			=> 'text', 
			'label'			=> esc_html__( 'Email', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 1, 
			'displayed'		=> 1
		),
		'password' => array( 
			'name'			=> 'password', 
			'type'			=> 'password', 
			'label'			=> esc_html__( 'Password', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 1, 
			'displayed'		=> 1
		),
		'forgotten_password' => array( 
			'name'			=> 'forgotten_password', 
			'type'			=> 'link', 
			'label'			=> esc_html__( 'Forgot your password?', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		),
		'remember' => array( 
			'name'			=> 'remember', 
			'type'			=> 'checkbox', 
			'label'			=> esc_html__( 'Remember me', 'booking-activities' ), 
			'placeholder'	=> 0, 
			'tip'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 0
		)
	);
	
	return apply_filters( 'bookacti_login_fields_default_data', $defaults );
}


/**
 * Get user meta fields default data
 * @since 1.6.0
 * @version 1.8.0
 * @param array $keys
 * @return array
 */
function bookacti_get_login_type_field_default_options( $keys = array() ) {
	$login_types = apply_filters( 'bookacti_login_type_field_default_options', array(
		'my_account' => array( 
			'value'			=> 'my_account', 
			'title'			=> esc_html__( 'Log in', 'booking-activities' ), 
			'label'			=> esc_html__( 'Log in', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'displayed'		=> 1
		),
		'new_account' => array( 
			'value'			=> 'new_account', 
			'title'			=> esc_html__( 'Create an account', 'booking-activities' ), 
			'label'			=> esc_html__( 'Create an account', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'displayed'		=> 1
		),
		'no_account' => array( 
			'value'			=> 'no_account', 
			'title'			=> esc_html__( 'Book without account', 'booking-activities' ), 
			'label'			=> esc_html__( 'Book without account', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '',  
			'displayed'		=> 1
		)
	), $keys );
	
	// Filter by tab
	if( $keys || ! is_array( $keys ) ) {
		foreach( $login_types as $login_type_name => $login_type ) {
			if( ! in_array( $login_type_name, $keys, true ) ) {
				unset( $login_types[ $login_type_name ] );
			}
		}
	}
	
	return $login_types;
}


/**
 * Get user meta fields default data
 * @since 1.5.0
 * @version 1.14.0
 * @return array
 */
function bookacti_get_register_fields_default_data() {
	$defaults = array(
		'first_name' => array( 
			'name'			=> 'first_name', 
			'type'			=> 'text', 
			'label'			=> esc_html__( 'First name', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		),
		'last_name' => array( 
			'name'			=> 'last_name', 
			'type'			=> 'text', 
			'label'			=> esc_html__( 'Last name', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		),
		'phone' => array( 
			'name'			=> 'phone', 
			'type'			=> 'tel', 
			'label'			=> esc_html__( 'Phone number', 'booking-activities' ), 
			'placeholder'	=> '', 
			'tip'			=> '', 
			'value'			=> '', 
			'required'		=> 0, 
			'displayed'		=> 1
		)
	);
	
	return apply_filters( 'bookacti_register_fields_default_data', $defaults );
}




// FORM LIST

/**
 * Format form filters
 * @since 1.5.0
 * @version 1.12.3
 * @param array $filters 
 * @return array
 */
function bookacti_format_form_filters( $filters = array() ) {

	$default_filters = apply_filters( 'bookacti_default_form_filters', array(
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
		if( in_array( $filter, array( 'id' ), true ) ) {
			if( is_numeric( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
		
		} else if( in_array( $filter, array( 'title' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'status' ), true ) ) {
			if( is_string( $current_value ) )	{ $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) )	{ $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			
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




// FORM EDITOR METABOXES

/**
 * Display 'managers' metabox content for forms
 * @since 1.5.0
 * @version 1.14.0
 * @param array $form_raw
 */
function bookacti_display_form_managers_meta_box( $form_raw ) {
	// Get current form managers option list
	$managers_already_added = array();
	$manager_ids = bookacti_get_form_managers( $form_raw[ 'form_id' ] );
	
	// Get available form managers option list
	$form_managers_cap = array( 'bookacti_edit_forms' );
	$form_managers_args = array(
		'option_label' => array( 'display_name', ' (', 'user_login', ')' ), 
		'id' => 'bapap-add-new-form-managers-select-box', 
		'name' => '', 
		'class' => 'bookacti-add-new-items-select-box bookacti-managers-selectbox',
		'role__in' => apply_filters( 'bookacti_managers_roles', array_merge( bookacti_get_roles_by_capabilities( $form_managers_cap ), $form_managers_cap ), 'form' ),
		'role__not_in' => apply_filters( 'bookacti_managers_roles_exceptions', array( 'administrator' ), 'form' ),
		'meta' => false,
		'ajax' => 0
	);
	
	?>
	<div id='bookacti-form-managers-container' class='bookacti-items-container' data-type='users' >
		<label id='bookacti-form-managers-title' class='bookacti-fullwidth-label' for='bookacti-add-new-form-managers-select-box' >
		<?php 
			esc_html_e( 'Who can manage this form?', 'booking-activities' );
			$tip  = esc_html__( 'Choose who is allowed to access this form.', 'booking-activities' );
			/* translators: %s = comma separated list of user roles */
			$tip .= '<br/>' . sprintf( esc_html__( 'These roles already have this privilege: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', array_intersect_key( bookacti_get_roles(), array_flip( $form_managers_args[ 'role__not_in' ] ) ) ) . '</code>' );
			/* translators: %s = capabilities name */
			$tip .= '<br/>' . sprintf( esc_html__( 'If the selectbox is empty, it means that no other users have these capabilities: %s.', 'booking-activities' ), '<code>' . implode( '</code>, <code>', $form_managers_cap ) . '</code>' );
			/* translators: %1$s = User Role Editor plugin link. */
			$tip .= '<br/>' . sprintf( esc_html__( 'If you want to grant a user these capabilities, use a plugin such as %1$s.', 'booking-activities' ), '<a href="https://wordpress.org/plugins/user-role-editor/" target="_blank">User Role Editor</a>' );
			bookacti_help_tip( $tip );
		?>
		</label>
		<div id='bookacti-add-form-managers-container' class='bookacti-add-items-container' >
			<?php bookacti_display_user_selectbox( $form_managers_args ); ?>
			<button type='button' id='bookacti-add-form-managers' class='bookacti-add-items' ><?php esc_html_e( 'Add manager', 'booking-activities' ); ?></button>
		</div>
		<div id='bookacti-form-managers-list-container' class='bookacti-items-list-container' >
			<select name='form-managers[]' id='bookacti-form-managers-select-box' class='bookacti-items-select-box' multiple >
			<?php 
				foreach( $manager_ids as $manager_id ) {
					?><option value='<?php echo $manager_id; ?>'><?php echo $manager_id; ?></option><?php
				}
			?>
			</select>
			<button type='button' id='bookacti-remove-form-managers' class='bookacti-remove-items' ><?php esc_html_e( 'Remove selected', 'booking-activities' ); ?></button>
		</div>
	</div>
	<?php
}


/**
 * Display 'publish' metabox content for forms
 * @since 1.5.0
 * @version 1.14.0
 * @param array $form_raw
 */
function bookacti_display_form_publish_meta_box( $form_raw ) {
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions' data-popup='<?php echo ! $form_raw[ 'active' ] && $form_raw[ 'status' ] !== 'trash' ? 1 : 0; ?>' >
			<div id='delete-action'>
			<?php
				if ( current_user_can( 'bookacti_delete_forms' ) ) {
					if( ! $form_raw[ 'active' ] ) {
						echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&action=delete&form_id=' . $form_raw[ 'form_id' ] ), 'delete-form_' . $form_raw[ 'form_id' ] ) ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Delete Permanently', 'forms', 'booking-activities' )
							. '</a>';
					} else {
						echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=bookacti_forms&status=trash&action=trash&form_id=' . $form_raw[ 'form_id' ] ), 'trash-form_' . $form_raw[ 'form_id' ] ) ) . '" class="submitdelete deletion" >'
								. esc_html_x( 'Move to trash', 'forms', 'booking-activities' )
							. '</a>';
					}
				}
			?>
			</div>

			<div id='publishing-action'>
				<span class='spinner'></span>
				<input id='bookacti-save-form-button' 
					   name='save' 
					   type='submit' 
					   class='button button-primary button-large' 
					   id='publish' 
					   value='<?php echo $form_raw[ 'active' ] ? esc_attr_x( 'Update', 'forms', 'booking-activities' ) : ( $form_raw[ 'status' ] === 'auto-draft' ? esc_attr_x( 'Publish', 'forms', 'booking-activities' ) : esc_attr_x( 'Restore', 'forms', 'booking-activities' ) ); ?>' 
				/>
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}


/**
 * Display 'integration tuto' metabox content for forms
 * @since 1.5.0
 * @version 1.14.0
 * @param array $form_raw
 */
function bookacti_display_form_integration_tuto_meta_box( $form_raw ) {
	$shortcode = '[bookingactivities_form form="' . $form_raw[ 'form_id' ] . '"]';
?>
	<h4><?php _e( 'Integrate in a post, page, or text widget', 'booking-activities' ) ?></h4>
	<div>
		<p><em><label for='bookacti-form-shortcode'><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'booking-activities' ); ?></label></em></p>
		<p class='shortcode wp-ui-highlight'>
			<input type='text' id='bookacti-form-shortcode' onfocus='this.select();' readonly='readonly' class='large-text code' value='<?php echo esc_attr( $shortcode ); ?>' />
		</p>
	</div>
<?php
	do_action( 'bookacti_after_form_integration_tuto', $form_raw );
}


/**
 * Display the calendar field help content
 * @since 1.8.0
 * @return string
 */
function bookacti_display_calendar_field_help() {
	ob_start();
	esc_html_e( 'Click on the problem you are experiencing to try to fix it:', 'booking-activities' );
	?>
	<ul class='bookacti-help-list'>
		<li><a href='https://booking-activities.fr/en/faq/events-do-not-appear-on-the-calendar/' target='_blank'><?php esc_html_e( 'The events do not appear on the calendar', 'booking-activities' ); ?></a><br/>
		<li><a href='https://booking-activities.fr/en/faq/calendar-not-showing/' target='_blank'><?php esc_html_e( 'The calendar doesn\'t show up', 'booking-activities' ); ?></a><br/>
		<li><a href='https://booking-activities.fr/en/faq/booking-activities-doesnt-work-as-it-should/' target='_blank'><?php esc_html_e( 'Booking Activities doesn\'t work as it should', 'booking-activities' ); ?></a>
		<?php do_action( 'bookacti_calendar_field_help_after' ); ?>
	</ul>
	<?php esc_html_e( 'This documentation can help you too:', 'booking-activities' ); ?>
	<ul class='bookacti-help-list'>
		<li><a href='https://booking-activities.fr/en/documentation/faq/' target='_blank'><?php esc_html_e( 'See the FAQ', 'booking-activities' ); ?></a>
		<li><a href='https://booking-activities.fr/en/documentation/user-documentation/' target='_blank'><?php esc_html_e( 'See the documentation', 'booking-activities' ); ?></a>
		<?php do_action( 'bookacti_global_help_after' ); ?>
	</ul>
	<?php
	return ob_get_clean();
}




// PERMISSIONS

/**
 * Check if user is allowed to manage form
 * @since 1.5.0
 * @version 1.7.17
 * @param int $form_id
 * @param int $user_id
 * @return boolean
 */
function bookacti_user_can_manage_form( $form_id, $user_id = false ) {
	$user_can_manage_form = false;
	$bypass_form_managers_check = apply_filters( 'bookacti_bypass_form_managers_check', false, $user_id );
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	if( is_super_admin( $user_id ) || $bypass_form_managers_check ) { $user_can_manage_form = true; }
	else {
		$admins = bookacti_get_form_managers( $form_id );
		if( $admins ) {
			if( in_array( $user_id, $admins, true ) ) { $user_can_manage_form = true; }
		}
	}

	return apply_filters( 'bookacti_user_can_manage_form', $user_can_manage_form, $form_id, $user_id );
}


/**
 * Get form managers
 * @since 1.5.0
 * @version 1.9.2
 * @param int|array $form_ids
 * @return array
 */
function bookacti_get_form_managers( $form_ids ) {
	$managers = bookacti_get_managers( 'form', $form_ids );
	
	$merged_managers = array();
	foreach( $managers as $user_ids ) {
		$merged_managers = array_merge( $merged_managers, bookacti_ids_to_array( $user_ids ) );
	}
	
	return array_unique( $merged_managers );
}


/**
 * Format form managers
 * @since 1.5.0
 * @version 1.8.8
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
	$form_managers_caps = array( 'bookacti_edit_forms' );
	foreach( $form_managers as $i => $form_manager ) {
		if( $form_manager ) {
			$user_can = false;
			foreach( $form_managers_caps as $form_managers_cap ) {
				if( user_can( $form_manager, $form_managers_cap ) ) { $user_can = true; break; }
			}
			if( $user_can ) { continue; }
		}
		unset( $form_managers[ $i ] );
	}
	
	return apply_filters( 'bookacti_form_managers', $form_managers );
}