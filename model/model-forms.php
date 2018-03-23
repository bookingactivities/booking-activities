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
 * @return int|false
 */
function bookacti_create_form( $title = '' ) {
	global $wpdb;
	
	if( ! $title ) { $title = ''; }
	
	$created = $wpdb->insert( 
		BOOKACTI_TABLE_FORMS, 
		array( 
			'title' => $title
		),
		array( '%s' )
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
 * @return int|false
 */
function bookacti_update_form( $form_id, $title ) {
	global $wpdb;
	
	$updated = $wpdb->update( 
		BOOKACTI_TABLE_FORMS, 
		array( 
			'title' => $title
		),
		array( 'id' => $form_id ),
		array( '%s' ),
		array( '%d' )
	);

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
			'active' => 1
		),
		array( 'id' => $form_id ),
		array( '%d' ),
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
			'active' => 0
		),
		array( 'id' => $form_id ),
		array( '%d' ),
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
	
	// Delete form managers too
	$wpdb->delete( 
		BOOKACTI_TABLE_PERMISSIONS, 
		array( 
			'object_type' => 'form',
			'object_id' => $form_id
		), 
		array( '%s', '%d' ) 
	);
	
	return $deleted;
}




// FORM FIELDS

/**
 * Get the fields of the desired form
 * Use 
 * @since 1.5.0
 * @global wpdb $wpdb
 * @param int $form_id
 * @return array|false
 */
function bookacti_get_form_fields( $form_id ) {
	global $wpdb;
	
	$query	= 'SELECT id as form_field_id, title, active FROM ' . BOOKACTI_TABLE_FORM_FIELDS . ' as FF '
			. ' WHERE FF.form_id = %d';
	
	$variables = array( $form_id );
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables );
	}
	
	$fields = $wpdb->get_results( $query, ARRAY_A );
	
	return $fields;
}