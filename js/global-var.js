$j=jQuery.noConflict();

// Global
var json_events			= [];
var json_activities		= [];
var json_groups			= [];
var pickedEvents		= [];
var loadingNumber		= [];
var calendars_data		= [];
var event_sizes = {
	'tinyHeight' : parseInt( bookacti_localized.event_tiny_height ),
	'smallHeight' : parseInt( bookacti_localized.event_small_height ),
	'narrowWidth' : parseInt( bookacti_localized.event_narrow_width ),
	'wideWidth' : parseInt( bookacti_localized.event_wide_width )
};

// Template
var template_id			= 0;
var isDragging			= false;
var exceptions			= [];
var selectedCategory	= 'new';
var selectedEvents		= [];
var blockEvents			= false;

// Bookings
var supportsTouch		= 'ontouchstart' in window || navigator.msMaxTouchPoints;
var hiddenActivities	= [];

// Woocommerce
var parent_calendar_data= [];
var is_activity			= [];