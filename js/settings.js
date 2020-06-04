$j( document ).ready( function() {
	/**
	 * Intercept settings form submission
	 * @version 1.8.0
	 * @param {Event} e
	 */
	$j( 'form#bookacti-settings.bookacti_save_settings_with_ajax' ).on( 'submit', function( e ) {
		// Prevent submission
		e.preventDefault();
		
		// Save tineMCE editor content 
		if( typeof tinyMCE !== 'undefined' ) { 
			if( tinyMCE ) { tinyMCE.triggerSave(); }
		}
	
		var form		= $j( this );
		var form_data	= form.serializeObject(); // Need to use the homemade serializeObject to support multidimentionnal array
		
		$j.ajax({
			url: bookacti_localized.ajaxurl,
			type: 'POST',
			data: form_data,
			dataType: 'json',
			success: function( response ){
				if( response.status === 'success' ) {
					if( form.attr( 'action' ) ) {
						window.location.replace( form.attr( 'action' ) );
					} else {
						window.location.reload( true ); 
					}
				} else {
					console.log( bookacti_localized.error );
					console.log( response );
				}
			},
			error: function( e ){
				console.log( 'AJAX ' + bookacti_localized.error );
				console.log( e );
			},
			complete: function() {}
		});	
		
	});
	
	
	/**
	 * Analyse bookings and events data that can be archived
	 * @since 1.7.0
	 * @version 1.8.0
	 */
	$j( '.bookacti-archive-options' ).on( 'click', '#bookacti-archive-button-analyse', function() {
		var date = $j( '#bookacti-archive-date' ).val();
		if( ! date ) { return; }

		var r = confirm( $j( '#booakcti-archive-alert-analyse' ).text().replace( '{date}', date ).replace( /\\n/g, '\n' ));
		if( r != true ) { return; }
		
		// Reset feedbacks
		$j( '.bookacti-archive-feedbacks-step' ).empty();
		$j( '.bookacti-archive-feedbacks-step-container' ).hide();
		$j( '#bookacti-archive-feedbacks-step1-container' ).show();
		
		var nonce = $j( '#nonce_archive_data' ).val();
		var feedbacks_step1 = $j( '#bookacti-archive-feedbacks-step1' );
		bookacti_archive_analyse( date, nonce, feedbacks_step1, bookacti_display_archive_step2 );
	});
	
	
	/**
	 * Dump bookings and events data prior to date
	 * @since 1.7.0
	 * @version 1.8.0
	 */
	$j( '.bookacti-archive-options' ).on( 'click', '#bookacti-archive-button-dump', function() {
		var file_already_exists = $j( '#bookacti-archive-button-dump' ).data( 'file-already-exists' );
		var r = true;
		if( typeof file_already_exists !== 'undefined' ) { 
			if( file_already_exists ) {
				r = confirm( $j( '#booakcti-archive-alert-override' ).text().replace( /\\n/g, '\n' ));
			}
		}
		
		if( r != true ) { return; }
		
		var date = $j( this ).data( 'date' );
		if( ! date ) { return; }
		
		// Reset feedbacks
		$j( '.bookacti-archive-feedbacks-step:not(#bookacti-archive-feedbacks-step1)' ).empty();
		$j( '.bookacti-archive-feedbacks-step-container:not(#bookacti-archive-feedbacks-step1-container)' ).hide();
		$j( '#bookacti-archive-feedbacks-step2-container' ).show();
		
		var nonce = $j( '#nonce_archive_data' ).val();
		var feedbacks_step2 = $j( '#bookacti-archive-feedbacks-step2' );
		bookacti_archive_dump( date, nonce, feedbacks_step2, bookacti_display_archive_step3 );
	});
	
	
	/**
	 * Delete bookings and events data prior to date
	 * @since 1.7.0
	 */
	$j( '.bookacti-archive-options' ).on( 'click', '#bookacti-archive-button-delete', function() {
		var date = $j( this ).data( 'date' );
		if( ! date ) { return; }
		
		// Reset feedbacks
		$j( '#bookacti-archive-feedbacks-step3' ).empty();
		$j( '#bookacti-archive-feedbacks-step3-container' ).show();
		$j( '#bookacti-archive-delete-data-note' ).show();
		
		var nonce = $j( '#nonce_archive_data' ).val();
		var feedbacks_step3 = $j( '#bookacti-archive-feedbacks-step3' );
		bookacti_archive_delete( date, nonce, feedbacks_step3, bookacti_after_data_deletion );
	});
	
	
	/**
	 * Restore bookings and events data from an archive
	 * @since 1.7.0
	 * @version 1.8.0
	 * @param {Event} e
	 */
	$j( '#bookacti-database-archives-table-container' ).on( 'click', 'a.bookacti-archive-restore-data', function( e ) {
		e.preventDefault();
		
		var filename = $j( this ).data( 'filename' );
		if( ! filename ) { return; }
		
		var r = confirm( $j( '#booakcti-archive-alert-restore' ).text().replace( '{filename}', filename ).replace( /\\n/g, '\n' ));
		if( r != true ) { return; }
		
		// Reset feedbacks
		var feedbacks_div = $j( this ).closest( 'tr' ).find( '.bookacti-archive-feedback' );
		feedbacks_div.empty().show();
		
		var nonce = $j( '#nonce_archive_data' ).val();
		bookacti_archive_restore_data( filename, nonce, feedbacks_div );
	});
	
	
	/**
	 * Delete a backup file
	 * @since 1.7.0
	 * @version 1.8.0
	 * @param {Event} e
	 */
	$j( '#bookacti-database-archives-table-container' ).on( 'click', 'a.bookacti-archive-delete-file', function( e ) {
		e.preventDefault();
		
		var filename = $j( this ).data( 'filename' );
		if( ! filename ) { return; }
		
		var r = confirm( $j( '#booakcti-archive-alert-delete-file' ).text().replace( '{filename}', filename ).replace( /\\n/g, '\n' ));
		if( r != true ) { return; }
		
		// Reset feedbacks
		var feedbacks_div = $j( this ).closest( 'tr' ).find( '.bookacti-archive-feedback' );
		feedbacks_div.empty().show();
		
		var nonce = $j( '#nonce_archive_data' ).val();
		bookacti_archive_delete_file( filename, nonce, feedbacks_div );
	});
	
	
	/**
	 * Toggle data ids to archive
	 * @since 1.7.0
	 */
	$j( '.bookacti-archive-options' ).on( 'click', '.bookacti-show-archive-ids', function(){
		$j( this ).toggleClass( 'dashicons-visibility dashicons-hidden' );
		$j( this ).siblings( '.bookacti-archive-ids' ).toggle();
	});
});


/**
 * Analyse the data to archive prior to a date
 * @since 1.7.0
 * @version 1.8.4
 * @param {string} date
 * @param {string} nonce
 * @param {HTMLElement} feedback_div
 * @param {callback} callback
 */
function bookacti_archive_analyse( date, nonce, feedback_div, callback ) {
	// Remove previous feedback_div
	feedback_div.find( '.bookacti-notices' ).remove();
	feedback_div.find( '.bookacti-loading-alt' ).remove();

	// Display a loader
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	feedback_div.append( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiArchiveDataAnalyse', 
				'date': date,
				'nonce': nonce
			},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'success' ) {
				var list = '<ul>';
				$j.each( response.ids_per_type, function( type, ids ){
					list += '<li>' + type + ': ' + ids.length;
					if( ids.length ) {
						list += '<span class="bookacti-show-archive-ids dashicons dashicons-visibility"></span><div class="bookacti-archive-ids"><code>' + ids.join( ', ' ) + '</code></div>';
					}
					list += '</li>';
				});
				list += '</ul>';
				feedback_div.append( '<div class="bookacti-archive-results">' + response.message + list + '</div>' );
				
				if( $j.isFunction( callback ) ) {
					callback( date, response.file_already_exists );
				}
				
			} else if( response.status === 'failed' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + '</li></ul></div>' );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX error occurred while trying to analyse data to archive';
			feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
			console.log( error_message );
			console.log( e );
		},
		complete: function() {
			feedback_div.find( '.bookacti-notices' ).show();
			feedback_div.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Dump data prior to a date
 * @since 1.7.0
 * @version 1.8.4
 * @param {string} date
 * @param {string} nonce
 * @param {HTMLElement} feedback_div
 * @param {callback} callback
 */
function bookacti_archive_dump( date, nonce, feedback_div, callback ) {
	
	// Remove previous feedback_div
	feedback_div.find( '.bookacti-notices' ).remove();
	feedback_div.find( '.bookacti-loading-alt' ).remove();

	// Display a loader
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	feedback_div.append( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiArchiveDataDump', 
				'date': date,
				'nonce': nonce
			},
		dataType: 'json',
		success: function( response ){
			var list = '';
			if( typeof response.results !== 'undefined' ) {
				list = '<ul>';
				$j.each( response.results, function( filename, error_code ){
					list += '<li>' + filename + ': ' + error_code + '</li>';
				});
				list += '</ul>';
			}
			
			if( response.status === 'success' ) {
				$j( '#bookacti-database-archives-table' ).replaceWith( response.archive_list );
				
				feedback_div.append( '<div class="bookacti-archive-results">' + response.message + list + '</div>' );
				
				if( $j.isFunction( callback ) ) {
					callback( date );
				}
				
			} else if( response.status === 'failed' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + list + '</li></ul></div>' );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX error occurred while trying to archive data';
			feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
			console.log( error_message );
			console.log( e );
		},
		complete: function() {
			feedback_div.find( '.bookacti-notices' ).show();
			feedback_div.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Delete data prior to a date
 * @since 1.7.0
 * @version 1.8.4
 * @param {string} date
 * @param {string} nonce
 * @param {HTMLElement} feedback_div
 * @param {callback} callback
 */
function bookacti_archive_delete( date, nonce, feedback_div, callback ) {
	// Remove previous feedback_div
	feedback_div.find( '.bookacti-notices' ).remove();
	feedback_div.find( '.bookacti-loading-alt' ).remove();

	// Display a loader
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	feedback_div.append( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiArchiveDataDelete', 
				'date': date,
				'nonce': nonce
			},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'success' ) {
				var list = '<ul>';
				$j.each( response.nb_per_type, function( type, nb ){
					list += '<li>' + type + ': ' + nb + '</li>';
				});
				list += '</ul>';
				feedback_div.append( '<div class="bookacti-archive-results">' + response.message + list + '</div>' );
				
				if( $j.isFunction( callback ) ) {
					callback( date );
				}
				
			} else if( response.status === 'failed' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + '</li></ul></div>' );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX error occurred while trying to delete data';
			feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
			console.log( error_message );
			console.log( e );
		},
		complete: function() {
			feedback_div.find( '.bookacti-notices' ).show();
			feedback_div.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Restore data from backup file
 * @since 1.7.0
 * @version 1.8.4
 * @param {string} filename
 * @param {string} nonce
 * @param {HTMLElement} feedback_div
 */
function bookacti_archive_restore_data( filename, nonce, feedback_div ) {
	// Remove previous feedback_div
	feedback_div.find( '.bookacti-notices' ).remove();
	feedback_div.find( '.bookacti-loading-alt' ).remove();

	// Display a loader
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	feedback_div.append( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiArchiveRestoreData', 
				'filename': filename,
				'nonce': nonce
			},
		dataType: 'json',
		success: function( response ){
			var list = '';
			if( typeof response.results !== 'undefined' ) {
				list = '<ul>';
				$j.each( response.results, function( filename, error_code ){
					list += '<li>' + filename + ': ' + error_code + '</li>';
				});
				list += '</ul>';
			}
			
			if( response.status === 'success' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-success-list"><li>' + response.message + list + '</li></ul></div>' );
				
			} else if( response.status === 'failed' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + list + '</li></ul></div>' );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX error occurred while trying to restore backup data';
			feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
			console.log( error_message );
			console.log( e );
		},
		complete: function() {
			feedback_div.find( '.bookacti-notices' ).show();
			feedback_div.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Delete backup file
 * @since 1.7.0
 * @version 1.8.4
 * @param {string} filename
 * @param {string} nonce
 * @param {HTMLElement} feedback_div
 */
function bookacti_archive_delete_file( filename, nonce, feedback_div ) {
	// Remove previous feedback_div
	feedback_div.find( '.bookacti-notices' ).remove();
	feedback_div.find( '.bookacti-loading-alt' ).remove();

	// Display a loader
	var loading_div = 
	'<div class="bookacti-loading-alt">' 
		+ '<img class="bookacti-loader" src="' + bookacti_localized.plugin_path + '/img/ajax-loader.gif" title="' + bookacti_localized.loading + '" />'
		+ '<span class="bookacti-loading-alt-text" >' + bookacti_localized.loading + '</span>'
	+ '</div>';
	feedback_div.append( loading_div );
	
	$j.ajax({
		url: bookacti_localized.ajaxurl,
		type: 'POST',
		data: { 'action': 'bookactiArchiveDeleteFile', 
				'filename': filename,
				'nonce': nonce
			},
		dataType: 'json',
		success: function( response ){
			if( response.status === 'success' ) {
				$j( '#bookacti-database-archives-table' ).replaceWith( response.archive_list );
				
			} else if( response.status === 'failed' ) {
				feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + response.message + '</li></ul></div>' );
				console.log( response );
			}
		},
		error: function( e ){
			var error_message = 'AJAX error occurred while trying to delete the backup file';
			feedback_div.append( '<div class="bookacti-notices"><ul class="bookacti-error-list"><li>' + error_message + '</li></ul></div>' );
			console.log( error_message );
			console.log( e );
		},
		complete: function() {
			feedback_div.find( '.bookacti-notices' ).show();
			feedback_div.find( '.bookacti-loading-alt' ).remove();
		}
	});
}


/**
 * Display the archive data Step 2 after step 1
 * @since 1.7.0
 * @param {String} date
 * @param {Boolean} file_already_exists
 */
function bookacti_display_archive_step2( date, file_already_exists ) {
	$j( '#bookacti-archive-feedbacks-step2-container' ).show();
	$j( '#bookacti-archive-button-dump' ).attr( 'data-date', date ).data( 'date', date );
	$j( '#bookacti-archive-button-dump' ).attr( 'data-file-already-exists', file_already_exists ).data( 'file-already-exists', file_already_exists );
}


/**
 * Display the archive data Step 3 after step 2
 * @since 1.7.0
 * @param {string} date
 */
function bookacti_display_archive_step3( date ) {
	$j( '#bookacti-archive-feedbacks-step3-container' ).show();
	$j( '#bookacti-archive-button-delete' ).attr( 'data-date', date ).data( 'date', date );
}


/**
 * Hide warnings after successful data deletion
 * @since 1.7.0
 * @param {string} date
 */
function bookacti_after_data_deletion( date ) {
	$j( '#bookacti-archive-delete-data-note' ).hide();
}