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
	 * Validate exception - on select
	 * @since 1.9.0
	 * @version 1.12.0
	 */
	$j( '.bookacti-exception-date-picker' ).on( 'change', function() { 
		bookacti_validate_add_exception_form( $j( this ).closest( '.bookacti-template-dialog' ) );
	});
	
	
	/**
	 * Add exception - on click
	 * @version 1.12.0
	 */
	$j( '.bookacti-add-exception-button' ).on( 'click', function() { 
		var container = $j( this ).closest( '.bookacti-template-dialog' );
		var isFormValid = bookacti_validate_add_exception_form( container );
		if( isFormValid ) {
			var exception_date = moment.utc( container.find( '.bookacti-exception-date-picker' ).val() ).locale( 'en' ).format( 'YYYY-MM-DD' );
			container.find( 'select.bookacti-exceptions-selectbox' ).append( "<option class='exception' value='" + exception_date + "' >" + exception_date + "</option>" );
		}
	});


	/**
	 * Remove exception - on click
	 * @version 1.12.0
	 */
	$j( '.bookacti-delete-exception-button' ).on( 'click', function() { 
		$j( this ).closest( '.bookacti-template-dialog' ).find( 'select.bookacti-exceptions-selectbox option:selected' ).remove();
	});
	

	/**
	 * Remove exception - on pressing 'Delete' key
	 * @version 1.12.0
	 * @param {Event} key
	 */
	$j( 'select.bookacti-exceptions-selectbox' ).on( 'keyup', function( key ) { 
		if( key.which === 46 ) {
			$j( this ).find( 'option:selected' ).remove();
		}
	});


	/**
	 * Validate the title and availability fields
	 */
	$j( '#bookacti-event-title, #bookacti-event-availability' ).on( 'keyup mouseup change', function() { 
		bookacti_validate_event_general_data();
	});


	/**
	 * Validate the repetition fields
	 * @since 1.8.0 (was in bookacti_validate_event_repetition_data)
	 * @version 1.12.0
	 */
	$j( 'select[name="repeat_freq"], input[name="repeat_from"], input[name="repeat_to"]' ).on( 'keyup mouseup change', function() { 
		var object_type = $j( this ).closest( '#bookacti-group-of-events-dialog' ).length > 0 ? 'group' : 'event';
		bookacti_validate_event_repetition_data( object_type );
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
	$j( '#bookacti-template-data-dialog .bookacti-form-error' ).remove();
	$j( '#bookacti-template-data-dialog input, #bookacti-template-data-dialog select' ).removeClass( 'bookacti-input-error' );

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
 * @version 1.8.0
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
    if( title.length > 0  )											{ valid_form.isTitle           = true; }
    if( color.length > 0  )											{ valid_form.isColor           = true; }
    if( valid_form.isColor && color === '#ffffff' )					{ valid_form.isColorWhite      = true; }
    if( avail.length > 0  )											{ valid_form.isAvail           = true; }
    if( valid_form.isAvail && parseInt( avail ) >= 0 )				{ valid_form.isAvailPositive	= true; }
    if( days.length > 0  )											{ valid_form.isDays            = true; }
    if( hours.length > 0  )											{ valid_form.isHours           = true; }
    if( minutes.length > 0  )										{ valid_form.isMinutes         = true; }
    if( valid_form.isDays      && days <= 365   && days >= 0 )		{ valid_form.isDaysInfTo365    = true; }
    if( valid_form.isHours     && hours <= 23   && hours >= 0 )		{ valid_form.isHoursInfTo23    = true; }
    if( valid_form.isMinutes   && minutes <= 59 && minutes >= 0 )	{ valid_form.isMinutesInfTo59  = true; }
    if( days > 0 || hours > 0 || minutes > 0 )						{ valid_form.isSupToZero		= true; }
    
    if( valid_form.isTitle 
	&&  valid_form.isColor 
	&&  valid_form.isAvailPositive 
	&&  valid_form.isDaysInfTo365 
	&&  valid_form.isHoursInfTo23 
	&&  valid_form.isMinutesInfTo59 
	&&  valid_form.isSupToZero ) { valid_form.send = true; }
	
    // Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-activity-data-dialog .bookacti-form-error' ).remove();
    $j( '#bookacti-activity-data-dialog *' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
    
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
	var valid_form = {
		'isRepetitionValid'	: bookacti_validate_event_repetition_data( 'event' ),
		'isGeneralValid'	: bookacti_validate_event_general_data(),
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
	$j( '#bookacti-event-title-container .bookacti-form-error, #bookacti-event-availability-container .bookacti-form-error' ).remove();
	$j( '#bookacti-event-title, #bookacti-event-availability' ).removeClass( 'bookacti-input-error' );

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
 * @version 1.12.0
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
		'areExcep'					: false,
		'areExcepBetweenFromAndTo'	: true,
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
	if( $j( scope + ' .exception' ).length > 0 )										{ valid_form.areExcep = true; }
	
	if( ! valid_form.isRepeated || ( valid_form.isRepeated && valid_form.isRepeatFrom && valid_form.isRepeatTo && valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) ) {
		valid_form.send = true;
	}
	
	// Disable all
	if( ! valid_form.isRepeated ) {
		$j( scope + ' input[name="repeat_from"]' ).prop( 'disabled', true );
		$j( scope + ' input[name="repeat_to"]' ).prop( 'disabled', true );
	}
	$j( scope + ' input.bookacti-exception-date-picker' ).prop( 'disabled', true );
	$j( scope + ' input.bookacti-add-exception-button' ).prop( 'disabled', true );
	$j( scope + ' select.bookacti-exceptions-selectbox' ).prop( 'disabled', true );
	$j( scope + ' div[id$="-repeat-days-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-monthly_type-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-from-container"]' ).hide();
	$j( scope + ' div[id$="-repeat-to-container"]' ).hide();
	$j( scope + ' .bookacti-exceptions-container' ).hide();
	$j( '#bookacti-group-of-events-repetition-first-event-notice' ).hide();
	$j( '#bookacti-group-of-events-occurrences-navigation' ).hide();
	
	var exceptions_disabled = false;
	var exceptions_min = moment.utc( repeat_from ).add( 1, 'd' );
	var exceptions_max = moment.utc( repeat_to ).subtract( 1, 'd' );
	if( exceptions_min.isAfter( exceptions_max ) ) { exceptions_disabled = true; };
	
	if( ! exceptions_disabled ) {
		if( valid_form.isRepeatFrom )	{ $j( scope + ' input.bookacti-exception-date-picker' ).attr( 'min', exceptions_min.format( 'YYYY-MM-DD' ) ); }
		if( valid_form.isRepeatTo )		{ $j( scope + ' input.bookacti-exception-date-picker' ).attr( 'max', exceptions_max.format( 'YYYY-MM-DD' ) ); }
	}
	
	// When the repetition period change, detect out-of-the-repeat-period existing exceptions and alert user
	$j( scope + ' .exception' ).removeClass( 'bookacti-error-exception bookacti-out-of-period-exception' );
	$j( scope + ' .exception' ).each( function() {
		var exception_date = moment.utc( $j( this ).val() );
		if( valid_form.isFromBeforeTo && ( exception_date < repeat_from || exception_date > repeat_to ) ) {
			valid_form.areExcepBetweenFromAndTo = false;
			$j( this ).addClass( 'bookacti-error-exception bookacti-out-of-period-exception' );
		}
	});

	// Clean the feedbacks before displaying new feedbacks
	$j( scope + ' div[id$="-repeat-to-container"] .bookacti-form-error, ' + scope + ' .bookacti-add-exception-container .bookacti-form-error' ).remove();
	$j( scope + ' input[name="repeat_from"], ' + scope + ' input[name="repeat_to"], ' + scope + ' select.bookacti-exceptions-selectbox' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
    
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
		$j( '#bookacti-group-of-events-occurrences-navigation' ).show();
		
		if( repeat_freq === 'weekly' ) {
			$j( scope + ' div[id$="-repeat-days-container"]' ).show();
		} else if( repeat_freq === 'monthly' ) {
			$j( scope + ' div[id$="-repeat-monthly_type-container"]' ).show();
		}
		
		if( valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) {
			// Enable the exception fields
			$j( scope + ' input.bookacti-exception-date-picker' ).prop( 'disabled', exceptions_disabled );
			$j( scope + ' input.bookacti-add-exception-button' ).prop( 'disabled', false );
			$j( scope + ' select.bookacti-exceptions-selectbox' ).prop( 'disabled', false );
			$j( scope + ' .bookacti-exceptions-container' ).show();

		} else {
			$j( scope + ' input[name="repeat_from"], ' + scope + ' input[name="repeat_to"]' ).addClass( 'bookacti-input-error' );
			if( ! valid_form.isFromBeforeTo ) {
				$j( scope + ' div[id$="-repeat-to-container"]' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_repeat_end_before_begin + "</div>" );
			} else if( ! valid_form.isEventBetweenFromAndTo ) {
				$j( scope + ' div[id$="-repeat-to-container"]' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_event_not_btw_from_and_to + "</div>" );
			}
		}
	}

	if( valid_form.areExcep && ! valid_form.areExcepBetweenFromAndTo ) { 
		$j( scope + ' select.bookacti-exceptions-selectbox' ).addClass( 'bookacti-input-warning' );
		$j( scope + ' .bookacti-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
	}

	return valid_form.send;
}


/**
 * Check event date exceptions field
 * @version 1.12.0
 * @param {HTMLElement} container
 * @returns {boolean}
 */
function bookacti_validate_add_exception_form( container ) {
	var exception_date  = moment.utc( container.find( '.bookacti-exception-date-picker' ).val() ).locale( 'en' );
	var repeat_from     = moment.utc( container.find( 'input[name="repeat_from"]' ).val() ).locale( 'en' );
	var repeat_to       = moment.utc( container.find( 'input[name="repeat_to"]' ).val() ).locale( 'en' );

	// Init boolean test variables
	var valid_form = {
		'isNewExcep'				: false,
		'isNewExcepBetweenFromAndTo': false,
		'isNewExcepDifferent'		: true,
		'send'						: false
	};

	// Make the tests and change the booleans
	if( ! isNaN( exception_date ) && exception_date !== '' && exception_date !== null )	{ valid_form.isNewExcep = true; }
	if( valid_form.isNewExcep 
	&&  exception_date.isSameOrAfter( repeat_from, 'day' ) 
	&&  exception_date.isSameOrBefore( repeat_to, 'day' ) ) { valid_form.isNewExcepBetweenFromAndTo = true; }

	// Detect duplicated exception
	if( valid_form.isNewExcep ) {
		container.find( '.exception' ).each( function() {
			if( exception_date.format( 'YYYY-MM-DD' ) === moment.utc( $j( this ).val() ).locale( 'en' ).format( 'YYYY-MM-DD' ) ) { 
				valid_form.isNewExcepDifferent = false;
				$j( this ).effect( 'highlight', 'swing', { color: '#ffff99' }, 2000 );
			}
		});
	}

	if( valid_form.isNewExcep && valid_form.isNewExcepBetweenFromAndTo && valid_form.isNewExcepDifferent ) { valid_form.send = true; }
	
	// Allow third party to change results
	container.trigger( 'bookacti_validate_add_exception', [ valid_form ] );
	
	// Clean old feedbacks
	container.find( '.bookacti-add-exception-container .bookacti-form-error' ).remove();
	container.find( '.bookacti-add-exception-container input' ).removeClass( 'bookacti-input-error' );

	// Feedback errors
	if( ! valid_form.isNewExcepBetweenFromAndTo ) { 
		container.find( '.bookacti-exception-date-picker' ).addClass( 'bookacti-input-error' );
		container.find( '.bookacti-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
	}
	if( ! valid_form.isNewExcepDifferent ) { 
		container.find( '.bookacti-exception-date-picker' ).addClass( 'bookacti-input-error' );
		container.find( '.bookacti-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_duplicated + "</div>" );
	}

	return valid_form.send;
}




// GROUP OF EVENTS

/**
 * Check group of events form
 * @version 1.12.0
 * @returns {Boolean}
 */
function bookacti_validate_group_of_events_form() {
	var valid_form = {
		'isRepetitionValid'	: bookacti_validate_event_repetition_data( 'group' ),
		'isGeneralValid'	: bookacti_validate_group_of_events_general_data(),
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
	$j( '#bookacti-group-of-events-dialog .bookacti-form-error' ).remove();
	$j( '#bookacti-group-of-events-dialog input, #bookacti-group-of-events-dialog select' ).removeClass( 'bookacti-input-error' );

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
 * @returns {Boolean}
 */
function bookacti_validate_group_category_form() {
	// Get group params
	var title		= $j( '#bookacti-group-category-title-field' ).val();
	
	// Init boolean test variables
	var valid_form = {
		'isTitle'			: false,
		'send'				: false
	};


	// Make the tests and change the booleans
	if( typeof title		=== 'string' && title		!== '')		{ valid_form.isTitle = true; }

	if( valid_form.isTitle ) { valid_form.send = true; }

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-group-category-dialog .bookacti-form-error' ).remove();
	$j( '#bookacti-group-category-dialog input, #bookacti-template-data-dialog select' ).removeClass( 'bookacti-input-error' );


	// Allow third-party to change the results
	$j( '#bookacti-group-category-dialog' ).trigger( 'bookacti_validate_group_category_form', [ valid_form ] );


	// Check the results and show feedbacks
	if( ! valid_form.isTitle ){ 
		$j( '#bookacti-group-category-title-field' ).addClass( 'bookacti-input-error' ); 
		$j( '#bookacti-group-category-title-field' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
	}
	
	return valid_form.send;
}