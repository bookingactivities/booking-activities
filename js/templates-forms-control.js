$j( document ).ready( function() {
	/**
	 * Template form dynamic check
	 */
    $j( '#bookacti-template-data-dialog :input' ).on( 'keyup mouseup change', function() { bookacti_validate_template_form(); });
    
	
	/**
	 * Activity form dynamic check
	 */
    $j( '#bookacti-activity-data-dialog :input' ).on( 'keyup mouseup change', function() { bookacti_validate_activity_form(); });
    

	/**
	 * Validate the repetition fields
	 * @since 1.8.0 (was in bookacti_validate_event_repetition_data)
	 * @version 1.12.0
	 */
	$j( 'select[name="repeat_freq"], input[name="repeat_from"], input[name="repeat_to"]' ).on( 'keyup mouseup change', function() { 
		var object_type = $j( this ).closest( '#bookacti-group-of-events-dialog' ).length ? 'group' : 'event';
		bookacti_validate_event_repetition_data( object_type );
	});
	
	
	/**
	 * Add a Days off line
	 * @since 1.13.0
	 */
	$j( '.bookacti-days-off-container' ).on( 'click', '.bookacti-add-days-off', function() {
		console.log( $j( this ).closest( '.bookacti-days-off-container' ).find( '.bookacti-days-off-table-container' ).length );
		bookacti_add_days_off_row( $j( this ).closest( '.bookacti-days-off-container' ).find( '.bookacti-days-off-table-container' ) );
	});
	
	
	/**
	 * Delete a Days off line
	 * @since 1.13.0
	 */
	$j( '.bookacti-days-off-container' ).on( 'click', '.bookacti-delete-days-off', function() {
		bookacti_delete_days_off_row( $j( this ).closest( '.bookacti-days-off-container' ).find( '.bookacti-days-off-table-container' ), $j( this ).closest( 'tr' ) );
	});
	
	
	/**
	 * Fill template's days off in calendar editor
	 * @since 1.13.0
	 */
	$j( '#bookacti-template-data-dialog' ).on( 'bookacti_default_template_settings', function() {
		bookacti_delete_days_off_rows( $j( '#bookacti-template-data-dialog .bookacti-days-off-table-container' ) );
		$j( '#bookacti-template-data-dialog input.bookacti-days-off-from, #bookacti-template-data-dialog input.bookacti-days-off-to' ).attr( 'min', '' ).attr( 'max', '' );
		
		// Fill Days off option since the bookacti_fill_fields_from_array function won't do it
		var template_data = bookacti.booking_system[ 'bookacti-template-calendar' ][ 'template_data' ];
		if( ! $j.isEmptyObject( template_data.settings ) ) {
			if( ! $j.isEmptyObject( template_data.days_off ) ) {
				bookacti_fill_days_off( $j( '#bookacti-template-data-dialog .bookacti-days-off-table-container' ), template_data.days_off );
			}
		}
	});
	
	
	/**
	 * Reset custom Days off
	 * @since 1.13.0
	 * @param {Event} e
	 * @param {String} scope
	 */
	$j( 'body' ).on( 'bookacti_empty_all_dialogs_forms', function( e, scope ) {
		bookacti_delete_days_off_rows( $j( scope + ' .bookacti-days-off-table-container' ) );
		$j( scope + ' input.bookacti-days-off-from, ' + scope + ' input.bookacti-days-off-to' ).attr( 'min', '' ).attr( 'max', '' );
	});
});



// TEMPLATES

/**
 * Check template form
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_template_form() {
	// Get template params
	var title       = $j( '#bookacti-template-title' ).val();
	var duplicate_id= $j( '#bookacti-template-duplicated-template-id' ).val();
	var snap_freq	= $j( '#bookacti-snapduration' ).val();

	// Init boolean test variables
	var valid_form = {
		'isTitle'				: false,
		'isSnapFreqFormatted'	: false,
		'isDuplicateIdPositive'	: false,
		'send'					: false
	};

	// Make the tests and change the booleans
	if( title !== '' )																			{ valid_form.isTitle = true; }
	if( duplicate_id !== '' && $j.isNumeric( duplicate_id ) && parseInt( duplicate_id ) >= 0 )	{ valid_form.isDuplicateIdPositive = true; }
	if( /^([0-1][0-9]|2[0-3]):([0-5][0-9])$/.test( snap_freq ) )								{ valid_form.isSnapFreqFormatted = true; }

	if( valid_form.isTitle 
	&&  valid_form.isDuplicateIdPositive
	&&  valid_form.isSnapFreqFormatted ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-template-data-dialog .bookacti-form-error, #bookacti-template-data-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-template-data-dialog input, #bookacti-template-data-dialog select' ).removeClass( 'bookacti-input-error' );
	if( ! valid_form.send ) { 
		$j( '#bookacti-template-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + bookacti_localized.error_on_a_form_field + '</li></ul></div>' );
		$j( '#bookacti-template-data-dialog .bookacti-notices' ).show();
	}
	
	// Allow third-party to change the results
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_validate_template_form', [ valid_form ] );

	// Check the results and show feedbacks
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-template-title' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-template-title' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}
	if( ! valid_form.isDuplicateIdPositive ){ 
		$j( '#bookacti-template-duplicated-template-id' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-template-duplicated-template-id' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_invalid_value + "</div>" );
	}
	if( ! valid_form.isSnapFreqFormatted ){ 
		$j( '#bookacti-snapduration' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-snapduration' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_time_format + "</div>" );
	}

	return valid_form.send;
}



// ACTIVITIES

/**
 * Check activity form
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_activity_form() {
	// Get template params
	var title       = $j( '#bookacti-activity-title' ).val();
	var color       = $j( '#bookacti-activity-color' ).val();
	var avail		= $j( '#bookacti-activity-availability' ).val();
	var days        = $j( '#bookacti-activity-duration-days' ).val();
	var hours       = $j( '#bookacti-activity-duration-hours' ).val();
	var minutes     = $j( '#bookacti-activity-duration-minutes' ).val();

	// Init boolean test variables
	var valid_form = {
		'isTitle'			: false,
		'isColor'			: false,
		'isColorWhite'		: false,
		'isAvail'			: false,
		'isAvailPositive'	: false,
		'isDays'			: false,
		'isHours'			: false,
		'isMinutes'			: false,
		'isDaysInfTo365'	: false,
		'isHoursInfTo23'	: false,
		'isMinutesInfTo59'	: false,
		'isSupToZero'		: false,
		'send'				: false
	};

	// Make the tests and change the booleans
	if( title.length  )												{ valid_form.isTitle			= true; }
	if( color.length  )												{ valid_form.isColor			= true; }
	if( valid_form.isColor && color === '#ffffff' )					{ valid_form.isColorWhite		= true; }
	if( avail.length  )												{ valid_form.isAvail			= true; }
	if( valid_form.isAvail && parseInt( avail ) >= 0 )				{ valid_form.isAvailPositive	= true; }
	if( days.length  )												{ valid_form.isDays				= true; }
	if( hours.length  )												{ valid_form.isHours			= true; }
	if( minutes.length )											{ valid_form.isMinutes			= true; }
	if( valid_form.isDays      && days <= 365   && days >= 0 )		{ valid_form.isDaysInfTo365		= true; }
	if( valid_form.isHours     && hours <= 23   && hours >= 0 )		{ valid_form.isHoursInfTo23		= true; }
	if( valid_form.isMinutes   && minutes <= 59 && minutes >= 0 )	{ valid_form.isMinutesInfTo59	= true; }
	if( days > 0 || hours > 0 || minutes > 0 )						{ valid_form.isSupToZero		= true; }

	if( valid_form.isTitle 
	&&  valid_form.isColor 
	&&  valid_form.isAvailPositive 
	&&  valid_form.isDaysInfTo365 
	&&  valid_form.isHoursInfTo23 
	&&  valid_form.isMinutesInfTo59 
	&&  valid_form.isSupToZero ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-activity-data-dialog .bookacti-form-error, #bookacti-activity-data-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-activity-data-dialog *' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
	if( ! valid_form.send ) {
		$j( '#bookacti-activity-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + bookacti_localized.error_on_a_form_field + '</li></ul></div>' );
		$j( '#bookacti-activity-data-dialog .bookacti-notices' ).show();
	}
	
	// Allow third-party to change the results
	$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_validate_activity_form', [ valid_form ] );

	// Check the results and show feedbacks
	// ERRORS
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-activity-title' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-activity-title' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}
	if( ! valid_form.isColor ) { 
		$j( '#bookacti-activity-color' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-color' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
	}
	if( ! valid_form.isAvail || ! valid_form.isAvailPositive ) { 
		$j( '#bookacti-activity-availability' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-availability' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
	}
	if( valid_form.isDays && ! valid_form.isDaysInfTo365 ) { 
		$j( '#bookacti-activity-duration-days' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-duration-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_days_sup_to_365 + "</div>" );
	}
	if( valid_form.isHours && ! valid_form.isHoursInfTo23 ) { 
		$j( '#bookacti-activity-duration-hours' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-duration-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_hours_sup_to_23 + "</div>" );
	}
	if( valid_form.isMinutes && ! valid_form.isMinutesInfTo59 ) { 
		$j( '#bookacti-activity-duration-minutes' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-duration-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_minutes_sup_to_59 + "</div>" );
	}
	if( ! valid_form.isSupToZero ) { 
		$j( '#bookacti-activity-duration-days, #bookacti-activity-duration-hours, #bookacti-activity-duration-minutes' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-activity-duration-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_activity_duration_is_null + "</div>" );
	}

	// WARNINGS
	if( valid_form.isColorWhite ) { 
		$j( '#bookacti-activity-color' ).addClass( 'bookacti-input-warning' );
	}
	if( ! valid_form.isDays ){ 
		$j( '#bookacti-activity-duration-days' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-activity-duration-days' ).val( 0 );
	}
	if( ! valid_form.isHours ){ 
		$j( '#bookacti-activity-duration-hours' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-activity-duration-hours' ).val( 0 );
	}
	if( ! valid_form.isMinutes ){ 
		$j( '#bookacti-activity-duration-minutes' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-activity-duration-minutes' ).val( 0 );
	}

	return valid_form.send;
}


// EVENTS

/**
 * Check event form
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_event_form() {
	$j( '#bookacti-event-data-dialog .bookacti-notices' ).remove();
	
	var valid_form = {
		'isGeneralValid'	: bookacti_validate_event_general_data(),
		'isRepetitionValid'	: bookacti_validate_event_repetition_data( 'event' ),
		'send'				: false
	};
    
    if( valid_form.isRepetitionValid && valid_form.isGeneralValid ) { valid_form.send = true; }
    
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_form', [ valid_form ] );
	
    return valid_form.send;
}


/**
 * Check event fields - Global tab
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_event_general_data() {
	// Get template params
	var title           = $j( '#bookacti-event-data-dialog #bookacti-event-title' ).val();
	var availability    = parseInt( $j( '#bookacti-event-data-dialog #bookacti-event-availability' ).val() );

	// Init boolean test variables
	var valid_form = {
		'isTitle' : false,
		'isAvailPositive' : false,
		'send' : false
	};

	// Make the tests and change the booleans    
	if( title !== '' ) { valid_form.isTitle = true; }
	if( availability >= 0 ) { valid_form.isAvailPositive = true; }
	if( valid_form.isTitle && valid_form.isAvailPositive ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-event-title-container .bookacti-form-error, #bookacti-event-availability-container .bookacti-form-error, #bookacti-event-data-dialog .bookacti-form-field-error' ).remove();
	$j( '#bookacti-event-title, #bookacti-event-availability' ).removeClass( 'bookacti-input-error' );
	if( ! valid_form.send ) { 
		if( ! $j( '#bookacti-event-data-dialog .bookacti-error-list' ).length ) {
			$j( '#bookacti-event-data-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"></ul></div>' );
		}
		$j( '#bookacti-event-data-dialog .bookacti-error-list' ).append( '<li class="bookacti-form-field-error">' + bookacti_localized.error_on_a_form_field + '</li>' );
		$j( '#bookacti-event-data-dialog .bookacti-notices' ).show();
	}
	
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_general_data', [ valid_form ] );

	// Check the results and show feedbacks
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-event-title' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-event-title-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
	}
	if( ! valid_form.isAvailPositive ){ 
		$j( '#bookacti-event-availability' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-event-availability-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
	}

	return valid_form.send;
}


/**
 * Check event fields - Repetition tab
 * @version 1.13.0
 * @param {String} object_type 'event' or 'group'
 * @returns {boolean}
 */
function bookacti_validate_event_repetition_data( object_type ) {
	if( object_type !== 'group' && object_type !== 'event' ) { return; }
	if( object_type === 'group' && typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ][ 0 ] === 'undefined' ) { return; }
	else if( object_type === 'event' && typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ] === 'undefined' ) { return; }
	
	var event		= object_type === 'group' ? bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ][ 0 ] : bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ];
	var event_start	= moment.utc( event.start ).clone().locale( 'en' );
	
	var scope = object_type === 'group' ? '#bookacti-group-of-events-dialog' : '#bookacti-event-data-dialog';
	
	var repeat_freq	= $j( scope + ' select[name="repeat_freq"]' ).val() ? $j( scope + ' select[name="repeat_freq"]' ).val() : 'none';
	var repeat_from	= moment.utc( $j( scope + ' input[name="repeat_from"]' ).val() ).locale( 'en' );
	var repeat_to	= moment.utc( $j( scope + ' input[name="repeat_to"]' ).val() ).locale( 'en' );

	// Init boolean test variables
	var valid_form = {
		'isRepeated'				: false,
		'isRepeatFrom'				: false,
		'isRepeatTo'				: false,
		'isFromBeforeTo'			: false,
		'isEventBetweenFromAndTo'	: false,
		'send'						: false
	};

	// Make the tests and change the booleans
	if( repeat_freq !== 'none' )														{ valid_form.isRepeated = true; }
	if( ! isNaN( repeat_from ) && repeat_from !== '' && repeat_from !== null )			{ valid_form.isRepeatFrom = true; }
	if( ! isNaN( repeat_to ) && repeat_to !== '' && repeat_to !== null )				{ valid_form.isRepeatTo = true; }
	if( valid_form.isRepeatFrom && valid_form.isRepeatTo && repeat_from < repeat_to )	{ valid_form.isFromBeforeTo = true; }
	if( valid_form.isRepeated 
	&& repeat_from.isSameOrBefore( event_start, 'day' )
	&& repeat_to.isSameOrAfter( event_start, 'day' ) )									{ valid_form.isEventBetweenFromAndTo = true; }
	
	if( ! valid_form.isRepeated || ( valid_form.isRepeated && valid_form.isRepeatFrom && valid_form.isRepeatTo && valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) ) {
		valid_form.send = true;
	}
	
	// Disable all
	if( ! valid_form.isRepeated ) {
		$j( scope + ' input[name="repeat_from"]' ).prop( 'disabled', true );
		$j( scope + ' input[name="repeat_to"]' ).prop( 'disabled', true );
	}
	$j( scope + ' div[id$="-repeat-days-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-monthly_type-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-from-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-to-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-exceptions-container"]' ).hide();
	$j( scope + ' input.bookacti-days-off-from, ' + scope + ' input.bookacti-days-off-to' ).attr( 'min', '' ).attr( 'max', '' );
	$j( '#bookacti-group-of-events-repetition-first-event-notice' ).hide();
	
	var exceptions_disabled = false;
	var exceptions_min = moment.utc( repeat_from ).add( 1, 'd' );
	var exceptions_max = moment.utc( repeat_to ).subtract( 1, 'd' );
	if( exceptions_min.isAfter( exceptions_max ) ) { exceptions_disabled = true; };
	
	if( ! exceptions_disabled ) {
		if( valid_form.isRepeatFrom )	{ $j( scope + ' input.bookacti-days-off-from, ' + scope + ' input.bookacti-days-off-to' ).attr( 'min', exceptions_min.format( 'YYYY-MM-DD' ) ); }
		if( valid_form.isRepeatTo )		{ $j( scope + ' input.bookacti-days-off-from, ' + scope + ' input.bookacti-days-off-to' ).attr( 'max', exceptions_max.format( 'YYYY-MM-DD' ) ); }
	}

	// Clean the feedbacks before displaying new feedbacks
	$j( scope + ' .bookacti-input-error, ' + scope + ' .bookacti-input-warning' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
	$j( scope + ' .bookacti-repeat-error' ).remove();
	
	if( ! valid_form.send && ! $j( scope + ' .bookacti-error-list' ).length ) { 
		$j( scope ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"></ul></div>' );
	}
	
	// Allow third party to change results
	$j( scope ).trigger( 'bookacti_validate_event_repetition_data', [ valid_form ] );

	// Display feedbacks if the form is not correct
	if( valid_form.isRepeated ) { 
		// Enable the repeat period fields
		$j( scope + ' input[name="repeat_from"]' ).prop( 'disabled', false );
		$j( scope + ' input[name="repeat_to"]' ).prop( 'disabled', false );
		$j( scope + ' div[id$="-repeat-from-container"]' ).show();
		$j( scope + ' div[id$="-repeat-to-container"]' ).show();
		$j( '#bookacti-group-of-events-repetition-first-event-notice' ).show();
		
		if( repeat_freq === 'weekly' ) {
			$j( scope + ' div[id$="-repeat-days-container"]' ).show();
		} else if( repeat_freq === 'monthly' ) {
			$j( scope + ' div[id$="-repeat-monthly_type-container"]' ).show();
		}
		
		if( valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) {
			// Enable the exception fields
			$j( scope + ' div[id$="-repeat-exceptions-container"]' ).show();

		} else {
			$j( scope + ' input[name="repeat_from"], ' + scope + ' input[name="repeat_to"]' ).addClass( 'bookacti-input-error' );
			if( ! valid_form.isFromBeforeTo ) {
				$j( scope + ' .bookacti-error-list' ).append( '<li class="bookacti-repeat-error">' + bookacti_localized.error_repeat_end_before_begin + '</li>' );
			} else if( ! valid_form.isEventBetweenFromAndTo ) {
				$j( scope + ' .bookacti-error-list' ).append( '<li class="bookacti-repeat-error">' + bookacti_localized.error_event_not_btw_from_and_to + '</li>' );
			}
		}
	}

	$j( scope + ' .bookacti-notices' ).toggle( $j( scope + ' .bookacti-notices li' ).length > 0 );
	
	return valid_form.send;
}




// GROUP OF EVENTS

/**
 * Check group of events form
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_group_of_events_form() {
	$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).remove();
	
	var valid_form = {
		'isGeneralValid'	: bookacti_validate_group_of_events_general_data(),
		'isRepetitionValid'	: bookacti_validate_event_repetition_data( 'group' ),
		'send'				: false
	};
    
    if( valid_form.isRepetitionValid && valid_form.isGeneralValid ) { valid_form.send = true; }
    
	// Allow third party to change results
	$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_validate_group_of_events_form', [ valid_form ] );
	
    return valid_form.send;
}


/**
 * Check group of events form fields
 * @since 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_group_of_events_general_data() {
	// Get group params
	var title		= $j( '#bookacti-group-of-events-title-field' ).val();
	var category	= $j( '#bookacti-group-of-events-category-selectbox' ).val();
	var cat_title	= $j( '#bookacti-group-of-events-category-title-field' ).val();
	
	// Init boolean test variables
	var valid_form = {
		'isTitle'			: false,
		'isCategory'		: false,
		'isCategoryTitle'	: false,
		'isSelectedEvents'	: false,
		'send'				: false
	};

	// Make the tests and change the booleans
	if( typeof title		=== 'string' && title		!== '' )	{ valid_form.isTitle = true; }
	if( typeof category		=== 'string' && category	!== 'new' )	{ valid_form.isCategory = true; }
	if( typeof cat_title	=== 'string' && cat_title	!== '' )	{ valid_form.isCategoryTitle = true; }
	if( bookacti.booking_system[ 'bookacti-template-calendar' ][ 'selected_events' ].length >= 2 )					{ valid_form.isSelectedEvents = true; }

	if( valid_form.isTitle 
	&&  ( valid_form.isCategory || valid_form.isCategoryTitle ) 
	&&  valid_form.isSelectedEvents ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-group-of-events-dialog .bookacti-form-error, #bookacti-group-of-events-dialog .bookacti-form-field-error' ).remove();
	$j( '#bookacti-group-of-events-dialog input, #bookacti-group-of-events-dialog select' ).removeClass( 'bookacti-input-error' );
	
	if( ! valid_form.send ) { 
		if( ! $j( '#bookacti-group-of-events-dialog .bookacti-error-list' ).length ) {
			$j( '#bookacti-group-of-events-dialog' ).append( '<div class="bookacti-notices"><ul class="bookacti-error-list"></ul></div>' );
		}
		$j( '#bookacti-group-of-events-dialog .bookacti-error-list' ).append( '<li class="bookacti-form-field-error">' + bookacti_localized.error_on_a_form_field + '</li>' );
		$j( '#bookacti-group-of-events-dialog .bookacti-notices' ).show();
	}
	
	// Allow third-party to change the results
	$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_validate_group_of_events_general_data', [ valid_form ] );

	// Check the results and show feedbacks
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-group-of-events-title-field' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-group-of-events-title-field' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}
	if( ! valid_form.isCategory && ! valid_form.isCategoryTitle ){ 
		$j( '#bookacti-group-of-events-category-title-field' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-group-of-events-category-title-field' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}
	if( ! valid_form.isSelectedEvents ){ 
		$j( '#bookacti-group-of-events-summary' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-group-of-events-summary' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_select_at_least_two_events + "</div>" ); 
	}
	
	return valid_form.send;
}


/**
 * Check group category form fields
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_group_category_form() {
	// Get group params
	var title = $j( '#bookacti-group-category-title-field' ).val();

	// Init boolean test variables
	var valid_form = {
		'isTitle': false,
		'send': false
	};

	// Make the tests and change the booleans
	if( typeof title === 'string' && title !== '' ) { valid_form.isTitle = true; }

	if( valid_form.isTitle ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-group-category-dialog .bookacti-form-error, #bookacti-group-category-dialog .bookacti-notices' ).remove();
	$j( '#bookacti-group-category-dialog input, #bookacti-template-data-dialog select' ).removeClass( 'bookacti-input-error' );
	if( ! valid_form.send ) { 
		$j( '#bookacti-group-category-dialog' ).append( '<div class="bookacti-notices"><ul><li>' + bookacti_localized.error_on_a_form_field + '</li></ul></div>' );
		$j( '#bookacti-group-category-dialog .bookacti-notices' ).show();
	}
	
	// Allow third-party to change the results
	$j( '#bookacti-group-category-dialog' ).trigger( 'bookacti_validate_group_category_form', [ valid_form ] );

	// Check the results and show feedbacks
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-group-category-title-field' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-group-category-title-field' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}

	return valid_form.send;
}


/**
 * Fill default Days off fields
 * @since 1.13.0
 * @param {HTMLElement} container
 * @param {Array} entries
 */
function bookacti_fill_days_off( container, entries ) {
	if( typeof entries === 'undefined' ) { return; }
	if( ! $j.isArray( entries ) && ! $j.isPlainObject( entries ) ) { return; }
	if( entries.length <= 0 ) { return; }
	
	// Reset Days off table
	bookacti_delete_days_off_rows( container );
	
	var tbody = container.find( 'tbody' );
	
	var i = 0;
	$j.each( entries, function( j, entry ) {
		if( i > 0 ) { bookacti_add_days_off_row( container ); }
		tbody.find( 'tr:last .bookacti-days-off-from' ).val( entry.from );
		tbody.find( 'tr:last .bookacti-days-off-to' ).val( entry.to );
		++i;
	});
}


/**
 * Add a Days off row
 * @since 1.13.0
 * @param {HTMLElement} container
 */
function bookacti_add_days_off_row( container ) {
	var tbody = container.find( 'tbody' );
	var name_i = container.data( 'name' ) + '[' + tbody.find( 'tr' ).length + ']';
	tbody.find( 'tr:first' ).clone().appendTo( tbody );
	tbody.find( 'tr:last .bookacti-days-off-from' ).attr( 'name', name_i + '[from]' ).val( '' );
	tbody.find( 'tr:last .bookacti-days-off-to' ).attr( 'name', name_i + '[to]' ).val( '' );
}


/**
 * Delete a Days off row
 * @since 1.13.0
 * @param {HTMLElement} container
 * @param {HTMLElement} row
 */
function bookacti_delete_days_off_row( container, row ) {
	row = row || null;
	var tbody = container.find( 'tbody' );
	// If there is only one row, empty the fields
	if( tbody.find( 'tr' ).length <= 1 ) {
		tbody.find( 'tr:first .bookacti-days-off-from, tr:first .bookacti-days-off-to' ).val( '' );
		
	// Else, delete the whole row and reset indexes
	} else if( row != null ) {
		row.remove();
		var i = 0;
		var name = container.data( 'name' );
		tbody.find( 'tr' ).each( function() {
			var name_i = name + '[' + i + ']';
			$j( this ).find( '.bookacti-days-off-from' ).attr( 'name', name_i + '[from]' );
			$j( this ).find( '.bookacti-days-off-to' ).attr( 'name', name_i + '[to]' );
			++i;
		});
	}
}


/**
 * Delete all Days off rows
 * @since 1.13.0
 * @param {HTMLElement} container
 */
function bookacti_delete_days_off_rows( container ) {
	var tbody = container.find( 'tbody' );
	tbody.find( 'tr:not(:first)' ).remove();
	bookacti_delete_days_off_row( container, tbody.find( 'tr:first' ) );
}