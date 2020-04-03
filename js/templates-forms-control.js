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
	 * Add exception - on click
	 */
	$j( '#bookacti-event-add-exception-button' ).on( 'click', function() { 
		var isFormValid = bookacti_validate_add_exception_form();
		if( isFormValid ) {
			var exception_date = moment( $j( '#bookacti-event-exception-date-picker' ).val() ).format( 'YYYY-MM-DD' );
			$j( '#bookacti-event-exceptions-selectbox' ).append( "<option class='exception' value='" + exception_date + "' >" + exception_date + "</option>" );
		}
	});


	/**
	 * Remove exception - on click
	 */
	$j( '#bookacti-event-delete-exceptions-button' ).on( 'click', function() { 
		$j( '#bookacti-event-exceptions-selectbox option:selected' ).remove();
	});
	

	/**
	 * Remove exception - on pressing 'Delete' key
	 */
	$j( '#bookacti-event-exceptions-selectbox' ).on( 'keyup', function( key ) { 
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
	 */
	$j( '#bookacti-event-repeat-freq, #bookacti-event-repeat-from, #bookacti-event-repeat-to' ).on( 'keyup mouseup change', function() { 
		bookacti_validate_event_repetition_data();
	});
});



// TEMPLATES

/**
 * Check template form
 * @version 1.8.0
 * @returns {Boolean}
 */
function bookacti_validate_template_form() {
    // Get template params
    var title       = $j( '#bookacti-template-title' ).val();
    var start       = moment( $j( '#bookacti-template-opening' ).val() );
    var end         = moment( $j( '#bookacti-template-closing' ).val() );
    var duplicate_id= $j( '#bookacti-template-duplicated-template-id' ).val();
    var day_start	= moment( '1970-01-01T' + $j( '#bookacti-mintime' ).val() + ':00' );
	var day_end		= $j( '#bookacti-maxtime' ).val().substr( 0, 2 ) === '00' ? moment( '1970-01-02T' + $j( '#bookacti-maxtime' ).val() + ':00' ) : moment( '1970-01-01T' + $j( '#bookacti-maxtime' ).val() + ':00' );
	var snap_freq	= $j( '#bookacti-snapduration' ).val();
	
    // Init boolean test variables
	var valid_form = {
		'isTitle'				: false,
		'isStart'				: false,
		'isEnd'					: false,
		'isStartBeforeEnd'		: false,
		'isDayStartBeforeEnd'	: false,
		'isSnapFreqFormatted'	: false,
		'isDuplicateIdPositive'	: false,
		'send'					: false
	};
    

    // Make the tests and change the booleans
    if( title !== '' )																			{ valid_form.isTitle = true; }
    if( ! isNaN(start)  && start !== '' && start !== null)										{ valid_form.isStart = true; }
    if( ! isNaN(end)    && end   !== '' && end   !== null)										{ valid_form.isEnd = true; }
    if( valid_form.isStart && valid_form.isEnd && ( start < end ) )								{ valid_form.isStartBeforeEnd = true; }
	if( duplicate_id !== '' && $j.isNumeric( duplicate_id ) && parseInt( duplicate_id ) >= 0 )	{ valid_form.isDuplicateIdPositive = true; }
	if( day_start.isBefore( day_end ) )															{ valid_form.isDayStartBeforeEnd = true; }
	if( /^([0-1][0-9]|2[0-3]):([0-5][0-9])$/.test( snap_freq ) )								{ valid_form.isSnapFreqFormatted = true; }
	
	if( valid_form.isTitle 
	&&  valid_form.isDuplicateIdPositive 
	&&  ( ! valid_form.isStart || ! valid_form.isEnd || valid_form.isStartBeforeEnd )
	&&  valid_form.isDayStartBeforeEnd
	&&  valid_form.isSnapFreqFormatted )	{ valid_form.send = true; }
    
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
    if( ! valid_form.isStartBeforeEnd && valid_form.isStart && valid_form.isEnd ) { 
        $j( '#bookacti-template-closing' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-template-closing' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_template_end_before_begin + "</div>" );
    }
    if( ! valid_form.isDuplicateIdPositive ){ 
        $j( '#bookacti-template-duplicated-template-id' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-template-duplicated-template-id' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_invalid_value + "</div>" );
    }
	if( ! valid_form.isDayStartBeforeEnd ){ 
		$j( '#bookacti-mintime' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-maxtime' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-maxtime' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_day_end_before_begin + "</div>" );
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
 * @version 1.8.0
 * @param {object} event
 * @returns {Boolean}
 */
function bookacti_validate_event_form() {
	var valid_form = {
		'isRepetitionValid'	: bookacti_validate_event_repetition_data(),
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
 * @version 1.8.0
 * @returns {Boolean}
 */
function bookacti_validate_event_general_data() {
    // Get template params
    var title           = $j( '#bookacti-event-data-dialog #bookacti-event-title' ).val();
    var availability    = parseInt( $j( '#bookacti-event-data-dialog #bookacti-event-availability' ).val() );
    var min_availability= parseInt( $j( '#bookacti-event-data-dialog #bookacti-event-availability' ).attr( 'min' ) ) || 0;
    
    // Init boolean test variables
	var valid_form = {
		'isTitle'				: false,
		'isAvailPositive'		: false,
		'isAvailSupToBookings'	: false,
		'send'					: false
	};
    
    // Make the tests and change the booleans    
    if( title !== '' )                      { valid_form.isTitle = true; }
    if( availability >= 0 )                 { valid_form.isAvailPositive = true; }
    if( availability >= min_availability )  { valid_form.isAvailSupToBookings = true; }
    
    if( valid_form.isTitle 
	&& valid_form.isAvailPositive 
	&& valid_form.isAvailSupToBookings ) { valid_form.send = true; }
	
    // Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-tab-content-general .bookacti-form-error' ).remove();
    $j( '#bookacti-tab-content-general *' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
	
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_general_data', [ valid_form ] );
	
    // Check the results and show feedbacks
    // ERROR
    if( ! valid_form.isTitle ){ 
        $j( '#bookacti-event-title' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-event-title' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isAvailPositive ){ 
        $j( '#bookacti-event-availability' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-event-availability' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isAvailSupToBookings ){ 
        $j( '#bookacti-event-availability' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-event-availability' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_less_avail_than_bookings + " (" + min_availability + ")</div>" );
    }
    
    return valid_form.send;
}


/**
 * Check event fields - Repetition tab
 * @version 1.8.0
 * @returns {boolean}
 */
function bookacti_validate_event_repetition_data() {
	if( typeof bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ] === 'undefined' ) { return; }
	
	var event		= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'picked_events' ][ 0 ];
	var event_start = moment( event.start ).format( 'YYYY-MM-DD HH:mm:ss' );
	var event_end	= moment( event.end ).format( 'YYYY-MM-DD HH:mm:ss' );

	// Get params
	var min_bookings    = parseInt( $j( '#bookacti-event-data-dialog #bookacti-event-availability' ).attr( 'min' ) );
	var repeat_freq     = $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq').val() || bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] + '';
	var current_freq	= bookacti.booking_system[ 'bookacti-template-calendar' ][ 'events_data' ][ event.id ][ 'repeat_freq' ] + '';
	var repeat_from     = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).val() );
	var repeat_from_max = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).attr('max') ).format( 'YYYY-MM-DD' );
	var repeat_to       = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).val() );
	var repeat_to_min   = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).attr('min') ).format( 'YYYY-MM-DD' );
	var template_start  = moment( $j( '#bookacti-template-picker :selected' ).data( 'template-start' ) );
	var template_end    = moment( $j( '#bookacti-template-picker :selected' ).data( 'template-end' ) );

	// Init boolean test variables
	var valid_form = {
		'isFreqAllowed'					: false,
		'isRepeated'					: false,
		'isRepeatFrom'					: false,
		'isRepeatTo'					: false,
		'isFromBeforeTo'				: false,
		'isRepeatFromAfterTemplateStart': false,
		'isRepeatToBeforeTemplateEnd'	: false,
		'isRepeatFromBeforeFirstBooked'	: false,
		'isRepeatToAfterLastBooked'		: false,
		'isEventBetweenFromAndTo'		: false,
		'areExcep'						: false,
		'areExcepBetweenFromAndTo'		: true,
		'send'							: false
	};

	// Make the tests and change the booleans
	if( repeat_freq !== 'none' )                                                    { valid_form.isRepeated = true; }
	if( ! isNaN( repeat_from )  && repeat_from  !== ''  && repeat_from !== null )   { valid_form.isRepeatFrom = true; }
	if( ! isNaN( repeat_to )    && repeat_to    !== ''  && repeat_to !== null )     { valid_form.isRepeatTo = true; }
	if( valid_form.isRepeatFrom && valid_form.isRepeatTo && ( repeat_from < repeat_to ) )					{ valid_form.isFromBeforeTo = true; }
	if( valid_form.isRepeated ) {
		if(( event_start.substr( 0, 10 ) >= repeat_from.format( 'YYYY-MM-DD' ) ) 
		&& ( event_end.substr( 0, 10 )	<= repeat_to.format( 'YYYY-MM-DD' ) ) ){ 
		valid_form.isEventBetweenFromAndTo = true; 
		}
	}

	if( valid_form.isRepeatFrom )	{ if( ( repeat_from.format( 'YYYY-MM-DD' ) >= template_start.format( 'YYYY-MM-DD' ) ) )	{ valid_form.isRepeatFromAfterTemplateStart    = true; } }
	if( valid_form.isRepeatTo )		{ if( ( repeat_to.format( 'YYYY-MM-DD' )   <= template_end.format( 'YYYY-MM-DD' ) ) )	{ valid_form.isRepeatToBeforeTemplateEnd       = true; } }
	if( valid_form.isRepeatFrom )	{ if( ( repeat_from.format( 'YYYY-MM-DD' ) <= repeat_from_max ) )						{ valid_form.isRepeatFromBeforeFirstBooked     = true; } }
	if( valid_form.isRepeatTo )		{ if( ( repeat_to.format( 'YYYY-MM-DD' )   >= repeat_to_min ) )							{ valid_form.isRepeatToAfterLastBooked         = true; } }
	if( $j( '#bookacti-event-data-dialog .exception' ).length > 0 ) { valid_form.areExcep = true; }

	if( ! valid_form.isRepeated ) {
		valid_form.send = true;
	} else {
		if(	valid_form.isRepeatFrom && valid_form.isRepeatTo && valid_form.isFromBeforeTo
		&&	valid_form.isRepeatFromBeforeFirstBooked && valid_form.isRepeatToAfterLastBooked 
		&&	valid_form.isEventBetweenFromAndTo ) {
			valid_form.send = true;
		}
	}

	// Disable all
	$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option' ).prop( 'disabled', true );
	if( ! valid_form.isRepeated ) {
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).prop( 'disabled', true );
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).prop( 'disabled', true );
	}
	$j( '#bookacti-event-data-dialog #bookacti-event-exception-date-picker' ).prop( 'disabled', true );
	$j( '#bookacti-event-data-dialog #bookacti-event-add-exception-button' ).prop( 'disabled', true );
	$j( '#bookacti-event-data-dialog #bookacti-event-exceptions-selectbox' ).prop( 'disabled', true );
	$j( '#bookacti-event-data-dialog #bookacti-event-repeat-from-container' ).hide();
	$j( '#bookacti-event-data-dialog #bookacti-event-repeat-to-container' ).hide();
	$j( '#bookacti-event-data-dialog #bookacti-event-exceptions-container' ).hide();
	
	var exceptions_disabled = false;
	var exceptions_min = moment( repeat_from ).add( 1, 'd' ).format( 'YYYY-MM-DD' );
	var exceptions_max = moment( repeat_to ).subtract( 1, 'd' ).format( 'YYYY-MM-DD' );
	if( moment( exceptions_min ).isAfter( exceptions_max ) ) { exceptions_disabled = true; };
	
	if( ! exceptions_disabled ) {
		if( valid_form.isRepeatFrom )	{ $j( '#bookacti-event-exception-date-picker' ).attr( 'min', exceptions_min ); }
		if( valid_form.isRepeatTo )		{ $j( '#bookacti-event-exception-date-picker' ).attr( 'max', exceptions_max ); }
	}
	
	// When the repetition period change, detect out-of-the-repeat-period existing exceptions and alert user
	$j( '#bookacti-event-data-dialog .exception' ).removeClass( 'bookacti-error-exception out-of-period-exception' );
	$j( '#bookacti-event-data-dialog .exception' ).each( function() {
		var exception_date = moment( $j( this ).val() );
		if( valid_form.isFromBeforeTo && ( exception_date < repeat_from || exception_date > repeat_to ) ) {
			valid_form.areExcepBetweenFromAndTo = false;
			$j( this ).addClass( 'bookacti-error-exception out-of-period-exception' );
		}
	});

	// Allow to change the freqency only if there are no bookings. Else you can only increase the frequency
	if( min_bookings === 0 || current_freq === 'none' ) { 
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option' ).prop( 'disabled', false );
		if( repeat_freq === 'none' || repeat_freq === 'daily' || repeat_freq === 'weekly' || repeat_freq === 'monthly' ) {
			valid_form.isFreqAllowed = true;
		}
	} else {
		if( current_freq === 'monthly' ) { 
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="monthly"]' ).prop( 'disabled', false );
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="weekly"]' ).prop( 'disabled', false );
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
			if( repeat_freq === 'daily' || repeat_freq === 'weekly' || repeat_freq === 'monthly' ) {
				valid_form.isFreqAllowed = true;
			}
		} else if ( current_freq === 'weekly' ) {
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="weekly"]' ).prop( 'disabled', false );
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
			if( repeat_freq === 'daily' || repeat_freq === 'weekly' ) {
				valid_form.isFreqAllowed = true;
			}
		} else if ( current_freq === 'daily' ) {
			$j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
			if( repeat_freq === 'daily' ) {
				valid_form.isFreqAllowed = true;
			}
		}
	}

	// Clean the feedbacks before displaying new feedbacks
	$j( '#bookacti-tab-content-repetition .bookacti-form-error' ).remove();
	$j( '#bookacti-tab-content-repetition *' ).removeClass( 'bookacti-input-error bookacti-input-warning' );

	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_repetition_data', [ valid_form ] );

	// Display feedbacks if the form is not correct
	if( valid_form.isRepeated ) { 
		//Enable the repeat period fields
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).prop( 'disabled', false );
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).prop( 'disabled', false );
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-from-container' ).show();
		$j( '#bookacti-event-data-dialog #bookacti-event-repeat-to-container' ).show();

		if( valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) {
			// Enable the exception fields
			$j( '#bookacti-event-data-dialog #bookacti-event-exception-date-picker' ).prop( 'disabled', exceptions_disabled );
			$j( '#bookacti-event-data-dialog #bookacti-event-add-exception-button' ).prop( 'disabled', false );
			$j( '#bookacti-event-data-dialog #bookacti-event-exceptions-selectbox' ).prop( 'disabled', false );
			$j( '#bookacti-event-data-dialog #bookacti-event-exceptions-container' ).show();

		} else {
			$j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).addClass( 'bookacti-input-error' );
			if( ! valid_form.isFromBeforeTo ) {
				$j( '#bookacti-event-repeat-to' ).after( "<div class='bookacti-form-error'>" + bookacti_localized.error_repeat_end_before_begin + "</div>" );
			} else if( ! valid_form.isEventBetweenFromAndTo ) {
				$j( '#bookacti-event-repeat-to' ).after( "<div class='bookacti-form-error'>" + bookacti_localized.error_event_not_btw_from_and_to + "</div>" );
			}
		}
	}

	if( valid_form.areExcep && ! valid_form.areExcepBetweenFromAndTo ) { 
		$j( '#bookacti-event-exceptions-selectbox' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-event-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
	}
	if( ! valid_form.isFreqAllowed ) { 
		$j( '#bookacti-event-repeat-freq' ).addClass( 'bookacti-input-error' );
		$j( '#bookacti-event-repeat-freq' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_freq_not_allowed + "</div>" );
	}
	if( valid_form.isRepeated && valid_form.isRepeatFrom && ! valid_form.isRepeatFromAfterTemplateStart ){ 
		$j( '#bookacti-event-repeat-from' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-event-repeat-from' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_repeat_start_before_template + "</div>" );
	}
	if( valid_form.isRepeated && valid_form.isRepeatTo && ! valid_form.isRepeatToBeforeTemplateEnd ){ 
		$j( '#bookacti-event-repeat-to' ).addClass( 'bookacti-input-warning' );
		$j( '#bookacti-event-repeat-to' ).parent().append( "<div class='bookacti-form-error'>" + bookacti_localized.error_repeat_end_after_template + "</div>" );
	}
	if( valid_form.isRepeated && valid_form.isRepeatFrom && ! valid_form.isRepeatFromBeforeFirstBooked ) { $j( '#bookacti-event-repeat-from' ).addClass( 'bookacti-input-error' ); }
	if( valid_form.isRepeated && valid_form.isRepeatTo && ! valid_form.isRepeatToAfterLastBooked )       { $j( '#bookacti-event-repeat-to' ).addClass( 'bookacti-input-error' );}
	if( ! valid_form.isRepeatFromBeforeFirstBooked || ! valid_form.isRepeatToAfterLastBooked ){ 
		$j( '#bookacti-event-repeat-to' ).parent().append( 
			"<div class='bookacti-form-error'>" + bookacti_localized.error_booked_events_out_of_period 
			+ " (" + repeat_from_max + " - " + repeat_to_min + ")</div>" );
	}

	return valid_form.send;
}


/**
 * Check event date exceptions field
 * @returns {boolean}
 */
function bookacti_validate_add_exception_form() {
    //Get params
    var event_id        = $j( '#bookacti-event-data-dialog' ).data( 'event-id' );
    var exception_date  = moment( $j( '#bookacti-event-data-dialog #bookacti-event-exception-date-picker' ).val() );
    var repeat_from     = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).val() );
    var repeat_to       = moment( $j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).val() );
    
    //Init boolean test variables
    var isNewExcep                  = false;
    var isNewExcepBetweenFromAndTo  = false;
    var isNewExcepDifferent         = true;
    var isNewExcepBooked            = false;
    var sendForm                    = false;
    
    //Make the tests and change the booleans
    if( ! isNaN( exception_date ) && exception_date  !== '' && exception_date !== null )    { isNewExcep = true; }
    if( isNewExcep && (exception_date >= repeat_from) && (exception_date <= repeat_to ))    { isNewExcepBetweenFromAndTo = true; }
    
    //Detect duplicated exception
    $j( '#bookacti-event-data-dialog .exception' ).each( function() {
        if( exception_date.format( 'YYYY-MM-DD' ) === moment( $j( this ).val() ).format( 'YYYY-MM-DD' ) ) { 
            isNewExcepDifferent = false;
            $j( this ).effect( 'highlight', 'swing', { color: '#ffff99' }, 2000 );
        }
    });
    
    //Prevent from adding an exception on a day when the occurrence is booked
	var exception_element = $j( '.fc-event[data-event-id="' + event_id + '"][data-event-start^="' + exception_date.format( 'YYYY-MM-DD' ) + '"]' );
	if( exception_element.length ) {
		if( parseInt( exception_element.find( '.bookacti-bookings' ).text() ) > 0 ) {
			isNewExcepBooked = true;
		}
	}
    
    
    if( isNewExcepBetweenFromAndTo && isNewExcepDifferent ) { sendForm = true; }
    
    //Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-event-add-exception-container .bookacti-form-error, #bookacti-event-add-exception-container .bookacti-form-warning' ).remove();
    $j( '#bookacti-event-add-exception-container input' ).removeClass( 'bookacti-input-error bookacti-input-warning' );
    
    //Check the results and show feedbacks
    //ERROR
    if( ! isNewExcepBetweenFromAndTo ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
    }
    if( ! isNewExcepDifferent ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'bookacti-input-error' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='bookacti-form-error'>" + bookacti_localized.error_excep_duplicated + "</div>" );
    }
    if( isNewExcepBooked ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'bookacti-input-warning' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='bookacti-form-warning'>" + bookacti_localized.error_set_excep_on_booked_occur + "</div>" );
    }
    
    return sendForm;
}



// GROUP OF EVENTS
/**
 * Check group of events form fields
 * @returns {Boolean}
 */
function bookacti_validate_group_of_events_form() {
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
	$j( '#bookacti-group-of-events-dialog' ).trigger( 'bookacti_validate_group_of_events_form', [ valid_form ] );


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