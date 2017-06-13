$j=jQuery.noConflict();

var supportsTouch			= 'ontouchstart' in window || navigator.msMaxTouchPoints;

var json_events             = [];
var json_activities			= [];
var json_groups				= [];
var template_id				= parseInt( $j( '#bookacti-template-picker' ).val() ) || 0;
var currentMousePos         = { x: -1, y: -1 };
var isDragging              = false;
var startOftemplateReached  = false;
var endOftemplateReached    = false;
var exceptions              = [];
var bookings                = [];
var hiddenActivities        = [];
var selectedCategory		= 'new';
var selectedEvents			= [];
var eventGroups				= [];
var pickedEvents			= [];
var lockedEvents            = [];
var blockEvents             = false;
var loadingNumber			= [];
loadingNumber['template']	= 0;

var templates_array			= [];
var activities_array		= [];
var groups_array			= [];

var calendar_settings		= [];
var calendarPeriod			= [];
var is_activity				= [];

var size = {
	'tinyHeight' : parseInt( bookacti_localized.event_tiny_height ),
	'smallHeight' : parseInt( bookacti_localized.event_small_height ),
	'narrowWidth' : parseInt( bookacti_localized.event_narrow_width ),
	'wideWidth' : parseInt( bookacti_localized.event_wide_width )
};

var valid_form				= [];