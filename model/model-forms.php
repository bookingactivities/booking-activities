<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// FORMS

/**
 * Get forms according to filters
 * @since 1.5.0
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
	$forms = $wpdb->get_results( $query, OBJECT );
	
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
 * Create a form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @param string $title
 * @param string $status
 * @param int $active
 * @return int|false
 */
function bookacti_insert_form( $title = '', $status = 'publish', $active = 1 ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone				= new DateTimeZone( 'UTC' );
	$current_datetime_object	= new DateTime( 'now', $utc_timezone );
	
	$created = $wpdb->insert( 
		BOOKACTI_TABLE_FORMS, 
		array( 
			'title' => $title,
			'user_id' => get_current_user_id(),
			'creation_date' => $current_datetime_object->format( 'Y-m-d H:i:s' ),
			'status' => $status,
			'active' => intval( $active )
		),
		array( '%s', '%d', '%s', '%s', '%d' )
	);
	if( ! $created ) { return $created; }
	
	$form_id = $wpdb->insert_id;
	
	// Set a default title if empty
	if( ! $title ) { 
		/* translators: %d is the form id */
		$title = sprintf( __( 'Form #%d', BOOKACTI_PLUGIN_NAME ), $form_id ); 
		bookacti_update_form( $form_id, $title );
	}
	
	return $form_id;
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
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int
 */
function bookacti_insert_default_form_fields( $form_id ) {
	global $wpdb;
	
	$default_fields		= bookacti_get_default_form_fields_data();
	$fields_to_insert	= array();
	foreach( $default_fields as $default_field ) {
		if( ! empty( $default_field[ 'compulsory' ] ) || ! empty( $default_field[ 'default' ] ) ) { $fields_to_insert[] = $default_field; }
	}
	
	if( ! $fields_to_insert ) { return 0; }
	
	$query = 'INSERT INTO ' . BOOKACTI_TABLE_FORM_FIELDS . ' ( form_id, name, type, label, options, value, placeholder, tip, active ) VALUES ';
	
	$variables = array();
	
	for( $i=0,$len=count($fields_to_insert); $i < $len; ++$i ) {
		$query .= ' ( %d, %s, %s, %s, %s, %s, %s, %s, 1 )';
		$variables = array_merge( $variables, array( 
			$form_id, 
			$fields_to_insert[ $i ][ 'name' ], 
			$fields_to_insert[ $i ][ 'type' ], 
			$fields_to_insert[ $i ][ 'label' ], 
			maybe_serialize( $fields_to_insert[ $i ][ 'options' ] ), 
			$fields_to_insert[ $i ][ 'value' ], 
			$fields_to_insert[ $i ][ 'placeholder' ], 
			$fields_to_insert[ $i ][ 'tip' ] )
		);
		if( $i < $len-1 ) { $query .= ','; }
		else { $query .= ';'; }
	}
	
	// Safely apply variables to the query
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}

	// Insert form fields
	$inserted = $wpdb->query( $query );
	
	return $inserted;
}

/**
 * Get the fields of the desired form
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return array|false
 */
function bookacti_get_form_fields( $form_id ) {
	global $wpdb;
	
	$query	= 'SELECT id as form_field_id, name, type, label, options, value, placeholder, tip, active FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE FF.form_id = %d';
	
	$variables = array( $form_id );
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$fields = $wpdb->get_results( $query, ARRAY_A );
	
	foreach( $fields as $i => $field ) {
		$fields[ $i ][ 'options' ] = maybe_unserialize( $field[ 'options' ] );
	}
	
	return $fields;
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
	
	return $deleted;
}


/**
 * Delete all form fields
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return int|false
 */
function bookacti_delete_form_fields( $form_id ) {
	global $wpdb;
	
	// Remove form fields metadata
	$query	= 'DELETE M.* '
			. ' FROM ' . BOOKACTI_TABLE_META . ' as M, ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE M.object_id = FF.id '
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