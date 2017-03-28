$j( document ).ready( function() {
	
    // Template form dynamic check
    $j( '#bookacti-template-data-dialog input[type="text"]' ).off().on( 'keyup', function() {  bookacti_validate_template_form(); });
    $j( '#bookacti-template-data-dialog input[type="date"]' ).off().on( 'change', function() { bookacti_validate_template_form(); });
    $j( '#bookacti-template-data-dialog select'				).off().on( 'change blur', function() { bookacti_validate_template_form(); });
    
    // Activity form dynamic check
    $j( '#bookacti-activity-data-dialog input[type="text"]'		).off().on( 'keyup', function() {  bookacti_validate_activity_form(); });
    $j( '#bookacti-activity-data-dialog input[type="number"]'	).off().on( 'change', function() { bookacti_validate_activity_form(); });
    $j( '#bookacti-activity-data-dialog input[type="color"]'	).off().on( 'change', function() { bookacti_validate_activity_form(); });
    
	// Event form dynamic check
	/** EXEPTIONS **/
		// Add exception
		$j( '#bookacti-event-add-exception-button' ).off().on( 'click', function() { 
			var isFormValid = bookacti_validate_add_exception_form();
			if( isFormValid ) {
				var exception_date = moment( $j( '#bookacti-event-exception-date-picker' ).val() ).format( 'YYYY-MM-DD' );
				$j( '#bookacti-event-exceptions-selectbox' ).append( "<option class='exception' value='" + exception_date + "' >" + exception_date + "</option>" );
			}
		});

		// Remove exception 
		// on pressing 'Delete' key
		$j( '#bookacti-event-exceptions-selectbox' ).off().on( 'keyup', function( key ) { 
			if( key.which === 46 ) {
				$j( this ).find( 'option:selected' ).remove();
			}
		});

		// on click on the delete button
		$j( '#bookacti-event-delete-exceptions-button' ).off().on( 'click', function() { 
			$j( '#bookacti-event-exceptions-selectbox option:selected' ).remove();
		});

	// Validate the title and availability fields
	$j( '#bookacti-event-title, #bookacti-event-availability' ).off().on( 'change', function() { 
		bookacti_validate_event_general_data(); 
	});

	// Enable or disable repetition and exception parts of the form
	$j( '#bookacti-event-repeat-freq, #bookacti-event-repeat-from, #bookacti-event-repeat-to' ).off().on( 'change', function() { 
		bookacti_validate_event_repetition_data( event.start, event.end );
	});
	
});



// TEMPLATES

// Check template form
function bookacti_validate_template_form() {
    // Get template params
    var title       = $j( '#bookacti-template-title' ).val();
    var start       = moment( $j( '#bookacti-template-opening' ).val() );
    var end         = moment( $j( '#bookacti-template-closing' ).val() );
    var min_start   = $j( '#bookacti-template-opening' ).attr( 'max' ) ? moment( $j( '#bookacti-template-opening' ).attr( 'max' ) ).format( 'YYYY-MM-DD' ) : false;
    var min_end     = $j( '#bookacti-template-closing' ).attr( 'min' ) ? moment( $j( '#bookacti-template-closing' ).attr( 'min' ) ).format( 'YYYY-MM-DD' ) : false;
    var duplicate_id= $j( '#bookacti-template-duplicated-template-id' ).val();
    var day_start	= moment( '1970-01-01T' + $j( '#bookacti-template-data-minTime' ).val() + ':00' );
	var day_end		= $j( '#bookacti-template-data-maxTime' ).val().substr( 0, 2 ) === '00' ? moment( '1970-01-02T' + $j( '#bookacti-template-data-maxTime' ).val() + ':00' ) : moment( '1970-01-01T' + $j( '#bookacti-template-data-maxTime' ).val() + ':00' );
	
    // Init boolean test variables
	var valid_form = {
		'isNew'					: false,
		'isTitle'				: false,
		'isStart'				: false,
		'isEnd'					: false,
		'isStartBeforeEnd'		: false,
		'isDayStartBeforeEnd'	: false,
		'isBookedEventOut'		: false,
		'isDuplicateIdPositive'	: false,
		'send'					: false
	};
    

    // Make the tests and change the booleans
    if( $j( '#bookacti-template-data-form-action' ).val() === 'bookactiInsertTemplate' )		{ valid_form.isNew = true; }
    if( title !== '' )																			{ valid_form.isTitle = true; }
    if( ! isNaN(start)  && start !== '' && start !== null)										{ valid_form.isStart = true; }
    if( ! isNaN(end)    && end   !== '' && end   !== null)										{ valid_form.isEnd = true; }
    if( valid_form.isStart && valid_form.isEnd && ( start < end ) )								{ valid_form.isStartBeforeEnd = true; }
	if( valid_form.isStart && valid_form.isEnd && min_start && min_end ) {
		if( start.format( 'YYYY-MM-DD' ) > min_start || end.format( 'YYYY-MM-DD' ) < min_end )	{ valid_form.isBookedEventOut = true; }
	}
	if( duplicate_id !== '' && $j.isNumeric( duplicate_id ) && parseInt( duplicate_id ) >= 0 )	{ valid_form.isDuplicateIdPositive = true; }
	if( day_start.isBefore( day_end ) )															{ valid_form.isDayStartBeforeEnd = true; }
	
	if( valid_form.isTitle 
	&&  valid_form.isDuplicateIdPositive 
	&&  valid_form.isStartBeforeEnd 
	&&  valid_form.isDayStartBeforeEnd 
	&& !valid_form.isBookedEventOut )	{ valid_form.send = true; }
    
    // Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-template-data-dialog .form-error' ).remove();
    $j( '#bookacti-template-data-dialog input, #bookacti-template-data-dialog select' ).removeClass( 'input-error' );

	
	// Allow third-party to change the results
	$j( '#bookacti-template-data-dialog' ).trigger( 'bookacti_validate_template_form', [ valid_form ] );
	
	
    // Check the results and show feedbacks
    if( ! valid_form.isTitle ){ 
        $j( '#bookacti-template-title' ).addClass( 'input-error' ); 
        $j( '#bookacti-template-title' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
    }
    if( ! valid_form.isStartBeforeEnd && valid_form.isStart && valid_form.isEnd ) { 
        $j( '#bookacti-template-closing' ).addClass( 'input-error' );
        $j( '#bookacti-template-closing' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_template_end_before_begin + "</div>" );
    }
    if( ! valid_form.isStart ){ 
        $j( '#bookacti-template-opening' ).addClass( 'input-error' );
        $j( '#bookacti-template-opening' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isEnd ){ 
        $j( '#bookacti-template-closing' ).addClass( 'input-error' );
        $j( '#bookacti-template-closing' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isNew && valid_form.isBookedEventOut ) { 
		if( ( start.format( 'YYYY-MM-DD' ) > min_start ) ){ 
			$j( '#bookacti-template-opening' ).addClass( 'input-error' );
		}
		if( ( end.format( 'YYYY-MM-DD' ) < min_end ) ){ 
			$j( '#bookacti-template-closing' ).addClass( 'input-error' );
		}
        $j( '#bookacti-template-closing' ).parent().append( 
            "<div class='form-error'>" + bookacti_localized.error_bookings_out_of_template
            + " (" + min_start + " - " + min_end + ")</div>" );
    }
    if( ! valid_form.isDuplicateIdPositive ){ 
        $j( '#bookacti-template-duplicated-template-id' ).addClass( 'input-error' );
        $j( '#bookacti-template-duplicated-template-id' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_invalid_value + "</div>" );
    }
	if( ! valid_form.isDayStartBeforeEnd ){ 
		$j( '#bookacti-template-data-minTime' ).addClass( 'input-error' );
		$j( '#bookacti-template-data-maxTime' ).addClass( 'input-error' );
		$j( '#bookacti-template-data-maxTime' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_day_end_before_begin + "</div>" );
	}
	
    return valid_form.send;
}



// ACTIVITIES

// Check activity form
function bookacti_validate_activity_form() {
	
    // Get template params
    var title       = $j( '#bookacti-activity-title' ).val();
    var color       = $j( '#bookacti-activity-color' ).val();
    var avail		= $j( '#bookacti-activity-availability' ).val();
    var days        = $j( '#bookacti-activity-duration-days' ).val();
    var hours       = $j( '#bookacti-activity-duration-hours' ).val();
    var minutes     = $j( '#bookacti-activity-duration-minutes' ).val();
    var templates   = $j( '#bookacti-activity-templates-select-box option' ).length;
    
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
		'areTemplates'		: false,
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
    if( templates > 0 )												{ valid_form.areTemplates		= true; }
    
    if( valid_form.isTitle 
	&&  valid_form.isColor 
	&&  valid_form.isAvailPositive 
	&&  valid_form.isDaysInfTo365 
	&&  valid_form.isHoursInfTo23 
	&&  valid_form.isMinutesInfTo59 
	&&  valid_form.isSupToZero 
	&&  valid_form.areTemplates ) { valid_form.send = true; }
	
    // Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-activity-data-dialog .form-error' ).remove();
    $j( '#bookacti-activity-data-dialog *' ).removeClass( 'input-error input-warning' );
    
	// Allow third-party to change the results
	$j( '#bookacti-activity-data-dialog' ).trigger( 'bookacti_validate_activity_form', [ valid_form ] );
	
    // Check the results and show feedbacks
    // ERRORS
    if( ! valid_form.isTitle ){ 
        $j( '#bookacti-activity-title' ).addClass( 'input-error' ); 
        $j( '#bookacti-activity-title' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" ); 
    }
    if( ! valid_form.isColor ) { 
        $j( '#bookacti-activity-color' ).addClass( 'input-error' );
        $j( '#bookacti-activity-color' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isAvail ) { 
        $j( '#bookacti-activity-availability' ).addClass( 'input-error' );
        $j( '#bookacti-activity-availability' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( valid_form.isAvail && ! valid_form.isAvailPositive ) { 
        $j( '#bookacti-activity-availability' ).addClass( 'input-error' );
        $j( '#bookacti-activity-availability' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_availability_inf_to_0 + "</div>" );
    }
    if( valid_form.isDays && ! valid_form.isDaysInfTo365 ) { 
        $j( '#bookacti-activity-duration-days' ).addClass( 'input-error' );
        $j( '#bookacti-activity-duration-days' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_days_sup_to_365 + "</div>" );
    }
    if( valid_form.isHours && ! valid_form.isHoursInfTo23 ) { 
        $j( '#bookacti-activity-duration-hours' ).addClass( 'input-error' );
        $j( '#bookacti-activity-duration-hours' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_hours_sup_to_23 + "</div>" );
    }
    if( valid_form.isMinutes && ! valid_form.isMinutesInfTo59 ) { 
        $j( '#bookacti-activity-duration-minutes' ).addClass( 'input-error' );
        $j( '#bookacti-activity-duration-minutes' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_minutes_sup_to_59 + "</div>" );
    }
    if( ! valid_form.isSupToZero ) { 
        $j( '#bookacti-activity-duration-days, #bookacti-activity-duration-hours, #bookacti-activity-duration-minutes' ).addClass( 'input-error' );
        $j( '#bookacti-activity-duration-days' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_activity_duration_is_null + "</div>" );
    }
    if( ! valid_form.areTemplates ) { 
        $j( '#bookacti-activity-templates-select-box' ).addClass( 'input-error' );
        $j( '#bookacti-activity-templates-select-box' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_no_templates_for_activity + "</div>" );
    }
    
    // WARNINGS
    if( valid_form.isColorWhite ) { 
        $j( '#bookacti-activity-color' ).addClass( 'input-warning' );
    }
    if( ! valid_form.isDays ){ 
        $j( '#bookacti-activity-duration-days' ).addClass( 'input-warning' );
		$j( '#bookacti-activity-duration-days' ).val( 0 );
    }
    if( ! valid_form.isHours ){ 
        $j( '#bookacti-activity-duration-hours' ).addClass( 'input-warning' );
		$j( '#bookacti-activity-duration-hours' ).val( 0 );
    }
    if( ! valid_form.isMinutes ){ 
        $j( '#bookacti-activity-duration-minutes' ).addClass( 'input-warning' );
        $j( '#bookacti-activity-duration-minutes' ).val( 0 );
    }
	
    return valid_form.send;
}


// EVENTS

// Check update events form
function bookacti_validate_event_form( event_start, event_end ) {
    
	var valid_form = {
		'isRepetitionValid'	: bookacti_validate_event_repetition_data( event_start, event_end ),
		'isGeneralValid'	: bookacti_validate_event_general_data(),
		'send'				: false
	};
    
    if( valid_form.isRepetitionValid 
	&&  valid_form.isGeneralValid ) { valid_form.send = true; }
    
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_form', [ valid_form ] );
	
    return valid_form.send;
}


// Check params from update events form
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
    $j( '#event-general-tab-content .form-error' ).remove();
    $j( '#event-general-tab-content *' ).removeClass( 'input-error input-warning' );
	
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_general_data', [ valid_form ] );
	
    // Check the results and show feedbacks
    // ERROR
    if( ! valid_form.isTitle ){ 
        $j( '#bookacti-event-title' ).addClass( 'input-error' );
        $j( '#bookacti-event-title' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_fill_field + "</div>" );
    }
    if( ! valid_form.isAvailPositive ){ 
        $j( '#bookacti-event-availability' ).addClass( 'input-error' );
        $j( '#bookacti-event-availability' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_availability_inf_to_0 + "</div>" );
    }
    if( ! valid_form.isAvailSupToBookings ){ 
        $j( '#bookacti-event-availability' ).addClass( 'input-error' );
        $j( '#bookacti-event-availability' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_less_avail_than_bookings + " (" + min_availability + ")</div>" );
    }
    
    return valid_form.send;
}


function bookacti_validate_event_repetition_data( event_start, event_end ) {
	
    event_start = event_start	|| selectedEvents[ 'template' ][ 0 ][ 'event-start' ];
    event_end	= event_end		|| selectedEvents[ 'template' ][ 0 ][ 'event-end' ];
	
	// Get params
    var min_bookings    = parseInt( $j( '#bookacti-event-data-dialog #bookacti-event-availability' ).attr( 'min' ) );
    var repeat_freq     = $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq').val();
    var repeat_init_freq= $j( '#bookacti-event-repeat-freq' ).data( 'initial-freq' );
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
		if(( event_start.format( 'YYYY-MM-DD' ) >= repeat_from.format( 'YYYY-MM-DD' ) ) 
		&& ( event_end.format( 'YYYY-MM-DD' )	<= repeat_to.format( 'YYYY-MM-DD' ) ) ){ 
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
    $j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).prop( 'disabled', true );
    $j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).prop( 'disabled', true );
    $j( '#bookacti-event-data-dialog #bookacti-event-exception-date-picker' ).prop( 'disabled', true );
    $j( '#bookacti-event-data-dialog #bookacti-event-add-exception-button' ).prop( 'disabled', true );
    $j( '#bookacti-event-data-dialog #bookacti-event-exceptions-selectbox' ).prop( 'disabled', true );
    $j( '#bookacti-event-data-dialog #bookacti-event-repeat-period-container' ).hide();
    $j( '#bookacti-event-data-dialog #bookacti-event-exceptions-container' ).hide();
    if( valid_form.isRepeatFrom )	{ $j( '#bookacti-event-exception-date-picker' ).attr( 'min', repeat_from.format( 'YYYY-MM-DD' ) ); }
    if( valid_form.isRepeatTo )		{ $j( '#bookacti-event-exception-date-picker' ).attr( 'max', repeat_to.format( 'YYYY-MM-DD' ) ); }
    
    // When the repetition period change, detect out-of-the-repeat-period existing exceptions and alert user
    $j( '#bookacti-event-data-dialog .exception' ).removeClass( 'error-exception out-of-period-exception' );
    $j( '#bookacti-event-data-dialog .exception' ).each( function() {
        var exception_date = moment( $j( this ).val() );
        if( valid_form.isFromBeforeTo && ( exception_date < repeat_from || exception_date > repeat_to ) ) {
            valid_form.areExcepBetweenFromAndTo = false;
            $j( this ).addClass( 'error-exception out-of-period-exception' );
        }
    });
    
    // Allow to change the freqency only if there are no bookings. Else you can only increase the frequency
    if( min_bookings === 0 || repeat_init_freq === 'none' ) { 
        $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option' ).prop( 'disabled', false );
        if( repeat_freq === 'none' || repeat_freq === 'daily' || repeat_freq === 'weekly' || repeat_freq === 'monthly' ) {
            valid_form.isFreqAllowed = true;
        }
    } else {
        if( repeat_init_freq === 'monthly' ) { 
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="monthly"]' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="weekly"]' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
            if( repeat_freq === 'daily' || repeat_freq === 'weekly' || repeat_freq === 'monthly' ) {
                valid_form.isFreqAllowed = true;
            }
        } else if ( repeat_init_freq === 'weekly' ) {
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="weekly"]' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
            if( repeat_freq === 'daily' || repeat_freq === 'weekly' ) {
                valid_form.isFreqAllowed = true;
            }
        } else if ( repeat_init_freq === 'daily' ) {
            $j( '#bookacti-event-data-dialog #bookacti-event-repeat-freq option[value="daily"]' ).prop( 'disabled', false );
            if( repeat_freq === 'daily' ) {
                valid_form.isFreqAllowed = true;
            }
        }
    }
    
    // Clean the feedbacks before displaying new feedbacks
    $j( '#event-repetition-tab-content .form-error' ).remove();
    $j( '#event-repetition-tab-content *' ).removeClass( 'input-error input-warning' );
	
	// Allow third party to change results
	$j( '#bookacti-event-data-dialog' ).trigger( 'bookacti_validate_event_repetition_data', [ valid_form ] );
	
	// Display feedbacks if the form is not correct
    if( valid_form.isRepeated ) { 
        //Enable the repeat period fields
        $j( '#bookacti-event-data-dialog #bookacti-event-repeat-from' ).prop( 'disabled', false );
        $j( '#bookacti-event-data-dialog #bookacti-event-repeat-to' ).prop( 'disabled', false );
        $j( '#bookacti-event-data-dialog #bookacti-event-repeat-period-container' ).show();
        
        if( valid_form.isFromBeforeTo && valid_form.isEventBetweenFromAndTo ) {
            // Enable the exception fields
            $j( '#bookacti-event-data-dialog #bookacti-event-exception-date-picker' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-add-exception-button' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-exceptions-selectbox' ).prop( 'disabled', false );
            $j( '#bookacti-event-data-dialog #bookacti-event-exceptions-container' ).show();
           
        } else {
            $j( '#bookacti-event-repeat-from, #bookacti-event-repeat-to' ).addClass( 'input-error' );
            if( ! valid_form.isFromBeforeTo ) {
                $j( '#bookacti-event-repeat-period-container' ).append( "<div class='form-error'>" + bookacti_localized.error_repeat_end_before_begin + "</div>" );
            } else if( ! valid_form.isEventBetweenFromAndTo ) {
                $j( '#bookacti-event-repeat-period-container' ).append( "<div class='form-error'>" + bookacti_localized.error_event_not_btw_from_and_to + "</div>" );
            }
        }
    }
    
    if( valid_form.areExcep && ! valid_form.areExcepBetweenFromAndTo ) { 
        $j( '#bookacti-event-exceptions-selectbox' ).addClass( 'input-warning' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
    }
    if( ! valid_form.isFreqAllowed ) { 
        $j( '#bookacti-event-repeat-freq' ).addClass( 'input-error' );
        $j( '#bookacti-event-repeat-freq' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_freq_not_allowed + "</div>" );
    }
    if( valid_form.isRepeated && valid_form.isRepeatFrom && ! valid_form.isRepeatFromAfterTemplateStart ){ 
        $j( '#bookacti-event-repeat-from' ).addClass( 'input-warning' );
        $j( '#bookacti-event-repeat-from' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_repeat_start_before_template + "</div>" );
    }
    if( valid_form.isRepeated && valid_form.isRepeatTo && ! valid_form.isRepeatToBeforeTemplateEnd ){ 
        $j( '#bookacti-event-repeat-to' ).addClass( 'input-warning' );
        $j( '#bookacti-event-repeat-to' ).parent().append( "<div class='form-error'>" + bookacti_localized.error_repeat_end_after_template + "</div>" );
    }
    if( valid_form.isRepeated && valid_form.isRepeatFrom && ! valid_form.isRepeatFromBeforeFirstBooked ) { $j( '#bookacti-event-repeat-from' ).addClass( 'input-error' ); }
    if( valid_form.isRepeated && valid_form.isRepeatTo && ! valid_form.isRepeatToAfterLastBooked )       { $j( '#bookacti-event-repeat-to' ).addClass( 'input-error' );}
    if( ! valid_form.isRepeatFromBeforeFirstBooked || ! valid_form.isRepeatToAfterLastBooked ){ 
		$j( '#bookacti-event-repeat-to' ).parent().append( 
            "<div class='form-error'>" + bookacti_localized.error_booked_events_out_of_period 
            + " (" + repeat_from_max + " - " + repeat_to_min + ")</div>" );
    }
    
    return valid_form.send;
}


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
    if( bookings[ template_id ] !== undefined ) {
		if( bookings[ template_id ][ event_id ] !== undefined ) {
			if( bookings[ template_id ][ event_id ].length > 0 ) {
				$j.each( bookings[ template_id ][ event_id ], function( i, booking ) {
					if( moment( booking.event_start ).format( 'YYYY-MM-DD' ) === exception_date.format( 'YYYY-MM-DD' ) ) {
						isNewExcepBooked = true;
					}
				});
			}
		}
    }
    
    
    if( isNewExcepBetweenFromAndTo && isNewExcepDifferent && ! isNewExcepBooked ) { sendForm = true; }
    
    //Clean the feedbacks before displaying new feedbacks
    $j( '#bookacti-event-add-exception-container .form-error' ).remove();
    $j( '#bookacti-event-add-exception-container input' ).removeClass( 'input-error' );
    
    //Check the results and show feedbacks
    //ERROR
    if( ! isNewExcepBetweenFromAndTo ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'input-error' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='form-error'>" + bookacti_localized.error_excep_not_btw_from_and_to + "</div>" );
    }
    if( ! isNewExcepDifferent ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'input-error' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='form-error'>" + bookacti_localized.error_excep_duplicated + "</div>" );
    }
    if( isNewExcepBooked ) { 
        $j( '#bookacti-event-exception-date-picker' ).addClass( 'input-error' );
        $j( '#bookacti-event-add-exception-container' ).append( "<div class='form-error'>" + bookacti_localized.error_set_excep_on_booked_occur + "</div>" );
    }
    
    return sendForm;
}
