$j=jQuery.noConflict();

if( typeof bookacti === 'undefined' ) {
	var bookacti = {
		// Global
		'booking_system': [],
		'event_sizes': {
				'tiny_height' : parseInt( bookacti_localized.event_tiny_height ),
				'small_height' : parseInt( bookacti_localized.event_small_height ),
				'narrow_width' : parseInt( bookacti_localized.event_narrow_width ),
				'wide_width' : parseInt( bookacti_localized.event_wide_width )
			},
		'is_touch_device': false,

		// Woocommerce
		'parent_booking_system': [], /** BACKWARD COMPATIBILITY < 1.5 **/
		'is_variation_activity': []
	};

// Compatibility with Optimization plugins
} else {
	if( typeof bookacti.booking_system === 'undefined' ) { bookacti.booking_system = []; }
	if( typeof bookacti.event_sizes === 'undefined' ) { 
		bookacti.event_sizes = {
			'tiny_height' : parseInt( bookacti_localized.event_tiny_height ),
			'small_height' : parseInt( bookacti_localized.event_small_height ),
			'narrow_width' : parseInt( bookacti_localized.event_narrow_width ),
			'wide_width' : parseInt( bookacti_localized.event_wide_width )
		};
	}
	if( typeof bookacti.is_touch_device === 'undefined' )		{ bookacti.is_touch_device = false; }
	if( typeof bookacti.parent_booking_system === 'undefined' )	{ bookacti.parent_booking_system = []; } /** BACKWARD COMPATIBILITY < 1.5 **/
	if( typeof bookacti.is_variation_activity === 'undefined' )	{ bookacti.is_variation_activity = []; }
}