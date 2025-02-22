<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// FORMS

/**
 * Get forms according to filters
 * @since 1.5.0
 * @version 1.16.2
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_form_filters() before
 * @return array
 */
function bookacti_get_forms( $filters = array() ) {
	global $wpdb;
	
	$query	= ' SELECT DISTINCT F.* ' 
			. ' FROM ' . BOOKACTI_TABLE_FORMS . ' as F '
			. ' WHERE true ';
	
	$variables = array();
	
	if( $filters[ 'id' ] ) {
		$query .= ' AND F.id IN ( %d ';
		$array_count = count( $filters[ 'id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'id' ] );
	}
	
	if( $filters[ 'title' ] ) {
		$query .= ' AND F.title LIKE %s ';
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
	}
	
	if( $filters[ 'status' ] ) {
		$query .= ' AND F.status IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND F.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND F.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	$results = $wpdb->get_results( $query, OBJECT );
	
	$forms = array();
	foreach( $results as $result ) {
		$forms[ $result->id ] = $result;
	}
	
	return $forms;
}


/**
 * Get the total amount of booking rows according to filters
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param array $filters Use bookacti_format_form_filters() before
 * @return int
 */
function bookacti_get_number_of_form_rows( $filters = array() ) {
	global $wpdb;
	
	$query	= ' SELECT COUNT( DISTINCT F.id ) ' 
			. ' FROM ' . BOOKACTI_TABLE_FORMS . ' as F '
			. ' WHERE true ';
	
	$variables = array();
	
	if( $filters[ 'id' ] ) {
		$query .= ' AND F.id IN ( %d ';
		$array_count = count( $filters[ 'id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'id' ] );
	}
	
	if( $filters[ 'title' ] !== false ) {
		$query .= ' AND F.title LIKE %s ';
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
	}
	
	if( $filters[ 'status' ] ) {
		$query .= ' AND F.status IN ( %s ';
		$array_count = count( $filters[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'status' ] );
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND F.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND F.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$count = $wpdb->get_var( $query );

	return $count ? $count : 0;
}


/**
 * Get form by id
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return array
 */
function bookacti_get_form( $form_id ) {
	global $wpdb;

	$query	= ' SELECT F.id as form_id, title, user_id, creation_date, status, active FROM ' . BOOKACTI_TABLE_FORMS . ' as F WHERE F.id = %d ';
	$query	= $wpdb->prepare( $query, $form_id );
	$form	= $wpdb->get_row( $query, ARRAY_A );

	return $form;
}


/**
 * Create a form
 * @since 1.5.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param string $title
 * @return int|false
 */
function bookacti_insert_form( $title = '' ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone            = new DateTimeZone( 'UTC' );
	$current_datetime_object = new DateTime( 'now', $utc_timezone );
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_FORMS
	       . ' ( title, user_id, creation_date, status, active ) '
	       . ' VALUES ( %s, %d, %s, "publish", 1 )';
	
	$variables = array(
		$title,
		get_current_user_id(),
		$current_datetime_object->format( 'Y-m-d H:i:s' )
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$created = $wpdb->query( $query );
	
	return $created ? $wpdb->insert_id : $created;
}


/**
 * Update a form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param string $title
 * @param int $user_id
 * @param string $title
 * @param string $creation_date
 * @param string $status
 * @param int $active
 * @return int|false
 */
function bookacti_update_form( $form_id, $title = '', $user_id = -1, $creation_date = '', $status = '', $active = -1 ) {
	global $wpdb;
	
	$query	= 'UPDATE ' . BOOKACTI_TABLE_FORMS
			. ' SET '
			. ' title = IFNULL( NULLIF( %s, "" ), title ), '
			. ' user_id = IFNULL( NULLIF( %d, -1 ), user_id ), '
			. ' creation_date = IFNULL( NULLIF( %s, "" ), creation_date ), '
			. ' status = IFNULL( NULLIF( %s, "" ), status ), '
			. ' active = IFNULL( NULLIF( %d, -1 ), active ) '
			. ' WHERE id = %d ';
	
	$variables = array( $title, $user_id, $creation_date, $status, $active, $form_id );
	
	// Safely apply variables to the query
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	// Update form
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Update forms' author id
 * @since 1.16.21
 * @global wpdb $wpdb
 * @param int $old_user_id
 * @param int $new_user_id
 * @return int|false
 */
function bookacti_update_forms_user_id( $old_user_id, $new_user_id ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_FORMS
	       . ' SET user_id = %d '
	       . ' WHERE user_id = %d ';
	
	$query   = $wpdb->prepare( $query, $new_user_id, $old_user_id );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Activate a form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int|false
 */
function bookacti_activate_form( $form_id ) {
	global $wpdb;
	
	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_FORMS, 
		array( 
			'status' => 'publish',
			'active' => 1
		),
		array( 'id' => $form_id ),
		array( '%s', '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Deactivate a form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int|false
 */
function bookacti_deactivate_form( $form_id ) {
	global $wpdb;
	
	$deactivated = $wpdb->update( 
		BOOKACTI_TABLE_FORMS, 
		array( 
			'status' => 'trash',
			'active' => 0
		),
		array( 'id' => $form_id ),
		array( '%s', '%d' ),
		array( '%d' )
	);

	return $deactivated;
}


/**
 * Delete a form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int|false
 */
function bookacti_delete_form( $form_id ) {
	global $wpdb;
	
	// Delete form
	$deleted = $wpdb->delete( 
		BOOKACTI_TABLE_FORMS, 
		array( 'id' => $form_id ), 
		array( '%d' ) 
	);
	
	// Delete form managers
	$wpdb->delete( 
		BOOKACTI_TABLE_PERMISSIONS, 
		array( 
			'object_type' => 'form',
			'object_id' => $form_id
		), 
		array( '%s', '%d' ) 
	);
	
	// Delete form metadata
	$wpdb->delete( 
		BOOKACTI_TABLE_META, 
		array( 
			'object_type' => 'form',
			'object_id' => $form_id
		), 
		array( '%s', '%d' ) 
	);
	
	// Delete form fields
	bookacti_delete_form_fields( $form_id );
	
	return $deleted;
}




// FORM FIELDS

/**
 * Insert default form fields
 * @since 1.5.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int
 */
function bookacti_insert_default_form_fields( $form_id ) {
	global $wpdb;
	
	$default_fields = bookacti_get_default_form_fields_data( '', 'edit' );

	$fields_to_insert = array();
	foreach( $default_fields as $i => $default_field ) {
		if( empty( $default_field[ 'compulsory' ] ) && empty( $default_field[ 'default' ] ) ) { continue; }
		if( empty( $default_field[ 'name' ] )       || empty( $default_field[ 'type' ] ) )    { continue; }
		$fields_to_insert[] = array( 'name' => $default_field[ 'name' ], 'type' => $default_field[ 'type' ] );
	}
	
	if( ! $fields_to_insert ) { return 0; }
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_FORM_FIELDS . ' ( form_id, name, type, active ) VALUES ';
	
	$variables = array();
	foreach( $fields_to_insert as $field_to_insert ) {
		$query    .= ' ( %d, %s, %s, 1 ),';
		$variables = array_merge( $variables, array( $form_id, $field_to_insert[ 'name' ], $field_to_insert[ 'type' ] ) );
	}
	$query = rtrim( $query, ',' ); // remove trailing comma
	
	// Safely apply variables to the query
	if( $variables ) { $query = $wpdb->prepare( $query, $variables ); }

	// Insert form fields
	$inserted = $wpdb->query( $query );
	
	return $inserted;
}


/**
 * Insert a form field without any data
 * @since 1.5.0
 * @version 1.15.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param array $field_data
 * @return int|false
 */
function bookacti_insert_form_field( $form_id, $field_data ) {
	global $wpdb;
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_FORM_FIELDS 
	       . ' ( form_id, name, type, required, active ) '
	       . ' VALUES ( %d, %s, %s, %d, 1 )';
	
	$variables = array(
		$form_id,
		$field_data[ 'name' ],
		$field_data[ 'type' ],
		! empty( $field_data[ 'required' ] ) ? 1 : 0
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$created = $wpdb->query( $query );
	
	return $created ? $wpdb->insert_id : $created;
}


/**
 * Get the desired field by id
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return array
 */
function bookacti_get_form_field( $field_id ) {
	global $wpdb;
	
	$query	= 'SELECT id as field_id, form_id, name, type, title, label, options, value, placeholder, tip, required, active FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE FF.id = %d';
	
	$variables = array( $field_id );
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$field = $wpdb->get_row( $query, ARRAY_A );
	
	if( ! $field ) { return array(); }
	
	foreach( $field as $field_key => $field_value ) {
		$field[ $field_key ] = maybe_unserialize( $field_value );
	}
	
	return $field;
}


/**
 * Get a field by name in the desired form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param string $field_name
 * @return array
 */
function bookacti_get_form_field_by_name( $form_id, $field_name ) {
	global $wpdb;
	
	$query	= 'SELECT id as field_id, form_id, name, type, title, label, options, value, placeholder, tip, required, active FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE FF.form_id = %d '
			. ' AND FF.name = %s '
			. ' LIMIT 1 ';
	
	$variables = array( $form_id, $field_name );
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$field = $wpdb->get_row( $query, ARRAY_A );
	
	if( ! $field ) { return array(); }
	
	foreach( $field as $field_key => $field_value ) {
		$field[ $field_key ] = maybe_unserialize( $field_value );
	}
	
	return $field;
}


/**
 * Get fields by name per form
 * @since 1.16.2
 * @global wpdb $wpdb
 * @param int $form_ids
 * @param string $field_name
 * @return array
 */
function bookacti_get_forms_field_by_name( $form_ids, $field_name ) {
	global $wpdb;
	
	$query = 'SELECT id as field_id, form_id, name, type, title, label, options, value, placeholder, tip, required, active FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
	       . ' WHERE FF.form_id IN ( %d ';
	
	$array_count = count( $form_ids );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$query .= ', %d ';
		}
	}
	
	$query .= ') AND FF.name = %s ';
	
	$variables = array_merge( $form_ids, array( $field_name ) );
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$results = $wpdb->get_results( $query, ARRAY_A );
	if( ! $results ) { return array(); }
	
	$forms_fields = array();
	foreach( $results as $result ) {
		$form_id = intval( $result[ 'form_id' ] );
		if( isset( $forms_fields[ $form_id ] ) ) { continue; }
		$field = array();
		foreach( $result as $field_key => $field_value ) {
			$field[ $field_key ] = maybe_unserialize( $field_value );
		}
		$forms_fields[ $form_id ] = $field;
	}
	
	return $forms_fields;
}


/**
 * Get the fields of the desired form
 * @since 1.5.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param boolean $active_only Whether to fetch only active fields. Default "true".
 * @return array|false
 */
function bookacti_get_form_fields( $form_id, $active_only = true ) {
	$fields = wp_cache_get( 'form_fields_' . $form_id, 'bookacti' );	
	if( $fields === false ) { 
		global $wpdb;

		$query = 'SELECT id as field_id, form_id, name, type, title, label, options, value, placeholder, tip, required, active '
			   . ' FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			   . ' WHERE FF.form_id = %d '
			   . ' ORDER BY id ASC ';
		$query  = $wpdb->prepare( $query, $form_id );
		$fields = $wpdb->get_results( $query, ARRAY_A );
		
		wp_cache_set( 'form_fields_' . $form_id, $fields, 'bookacti' );
	}
	
	$fields_by_id = array();
	if( $fields ) {
		foreach( $fields as $i => $field ) {
			if( $active_only && ! $field[ 'active' ] ) { continue; }
			foreach( $field as $field_key => $field_value ) {
				$fields_by_id[ $field[ 'field_id' ] ][ $field_key ] = maybe_unserialize( $field_value );
			}
		}
	}
	
	return $fields_by_id;
}


/**
 * Update a form field
 * @since 1.5.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param array $field_data
 * @return int|false
 */
function bookacti_update_form_field( $field_data ) {
	global $wpdb;
	
	$query = 'UPDATE ' . BOOKACTI_TABLE_FORM_FIELDS 
	       . ' SET '
	       . ' title = NULLIF( IFNULL( NULLIF( %s, "" ), title ), "null" ), '
	       . ' label = NULLIF( IFNULL( NULLIF( %s, "" ), label ), "null" ), '
	       . ' placeholder = NULLIF( IFNULL( NULLIF( %s, "" ), placeholder ), "null" ), '
	       . ' tip = NULLIF( IFNULL( NULLIF( %s, "" ), tip ), "null" ), '
	       . ' options = NULLIF( IFNULL( NULLIF( %s, "" ), options ), "null" ), '
	       . ' value = NULLIF( IFNULL( NULLIF( %s, "" ), value ), "null" ), '
	       . ' required = IFNULL( NULLIF( %d, -1 ), required ) '
	       . ' WHERE id = %d ';
	
	$variables = array(
		$field_data[ 'title' ],
		$field_data[ 'label' ],
		$field_data[ 'placeholder' ],
		$field_data[ 'tip' ],
		$field_data[ 'options' ],
		$field_data[ 'value' ],
		$field_data[ 'required' ],
		$field_data[ 'field_id' ]
	);
	
	$query = $wpdb->prepare( $query, $variables );
	$updated = $wpdb->query( $query );
	
	return $updated;
}


/**
 * Delete a single form fields
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $field_id
 * @return int|false
 */
function bookacti_delete_form_field( $field_id ) {
	global $wpdb;
	
	// Delete field
	$deleted = $wpdb->delete( 
		BOOKACTI_TABLE_FORM_FIELDS, 
		array( 'id' => $field_id ), 
		array( '%d' ) 
	);
	
	// Delete field metadata
	$wpdb->delete( 
		BOOKACTI_TABLE_META, 
		array( 
			'object_type' => 'form_field',
			'object_id' => $field_id 
		), 
		array( '%s', '%d' ) 
	);
	
	return $deleted;
}


/**
 * Delete all form fields
 * @since 1.5.0
 * @version 1.14.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int|false
 */
function bookacti_delete_form_fields( $form_id ) {
	global $wpdb;
	
	// Remove form fields metadata
	$query	= 'DELETE M.* '
			. ' FROM ' . BOOKACTI_TABLE_META . ' as M, ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE M.object_type = "form_field" '
			. ' AND M.object_id = FF.id '
			. ' AND FF.form_id = %d ';
	
	$query = $wpdb->prepare( $query, $form_id );
	$wpdb->query( $query );
	
	// Delete form fields
	$deleted = $wpdb->delete( 
		BOOKACTI_TABLE_FORM_FIELDS, 
		array( 'form_id' => $form_id ), 
		array( '%d' ) 
	);
	
	return $deleted;
}