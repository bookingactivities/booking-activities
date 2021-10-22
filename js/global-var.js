$j=jQuery.noConflict();

if( typeof bookacti === 'undefined' ) {
	var bookacti = {
		'booking_system': [],
		'is_touch_device': false,
		'total_price_fields_data': [],
		'is_variation_activity': [] // Woocommerce
	};

// Compatibility with Optimization plugins
} else {
	if( typeof bookacti.booking_system === 'undefined' )			{ bookacti.booking_system = []; }
	if( typeof bookacti.is_touch_device === 'undefined' )			{ bookacti.is_touch_device = false; }
	if( typeof bookacti.total_price_fields_data === 'undefined' )	{ bookacti.total_price_fields_data = []; }
	if( typeof bookacti.is_variation_activity === 'undefined' )		{ bookacti.is_variation_activity = []; }
}