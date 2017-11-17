$j=jQuery.noConflict();

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

	// Bookings
	'hidden_activities': [],

	// Woocommerce
	'parent_booking_system': [],
	'is_variation_activity': []
};