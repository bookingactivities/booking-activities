<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Don't localize strings here, please use .po file with poedit and submit the .mo generated file to the plugin author

$current_datetime_object = new DateTime( 'now', new DateTimeZone( bookacti_get_setting_value( 'bookacti_general_settings', 'timezone' ) ) );

// Fill the translation array to use it in js 
$bookacti_translation_array = apply_filters( 'bookacti_translation_array', array(

	// DIALOGS
	'dialog_update_event_title'				=> esc_html__( 'Update event parameters', BOOKACTI_PLUGIN_NAME ),
	'dialog_create_template_title'			=> esc_html__( 'Create new calendar', BOOKACTI_PLUGIN_NAME ),
	'dialog_update_template_title'			=> esc_html__( 'Update calendar parameters', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_template_title'			=> esc_html__( 'Delete calendar', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_event_title'			    => esc_html__( 'Delete event', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_booked_event_title'		=> esc_html__( 'Delete booked event', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_activity_title'			=> esc_html__( 'Delete activity', BOOKACTI_PLUGIN_NAME ),
	'dialog_choice_activity_title'		    => esc_html__( 'Create a new activity or use an existing activity ?', BOOKACTI_PLUGIN_NAME ),
	'dialog_import_activity_title'			=> esc_html__( 'Import existing activity', BOOKACTI_PLUGIN_NAME ),
	'dialog_create_activity_title'			=> esc_html__( 'Create new activity', BOOKACTI_PLUGIN_NAME ),
	'dialog_update_activity_title'		    => esc_html__( 'Update activity parameters', BOOKACTI_PLUGIN_NAME ),
	'dialog_choose_group_of_events_title'	=> esc_html__( 'This event is available in several bundles', BOOKACTI_PLUGIN_NAME ),
	'dialog_create_group_of_events_title'	=> esc_html__( 'Create a group of events', BOOKACTI_PLUGIN_NAME ),
	'dialog_update_group_of_events_title'	=> esc_html__( 'Update a group of events', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_group_of_events_title'	=> esc_html__( 'Delete a group of events', BOOKACTI_PLUGIN_NAME ),
	'dialog_create_group_category_title'	=> esc_html__( 'Create a group category', BOOKACTI_PLUGIN_NAME ),
	'dialog_update_group_category_title'	=> esc_html__( 'Update a group category', BOOKACTI_PLUGIN_NAME ),
	'dialog_delete_group_category_title'	=> esc_html__( 'Delete a group category', BOOKACTI_PLUGIN_NAME ),
	'dialog_locked_event'					=> esc_html__( 'Locked event', BOOKACTI_PLUGIN_NAME ),
	'booking_filters_parameters'			=> esc_html__( 'Booking filters parameters', BOOKACTI_PLUGIN_NAME ),
	'booking_list_parameters'				=> esc_html__( 'Booking list parameters', BOOKACTI_PLUGIN_NAME ),
	'booking_action_cancel'					=> esc_html_x( 'Cancel booking', 'Dialog title', BOOKACTI_PLUGIN_NAME ),
	'booking_action_reschedule'				=> esc_html__( 'Reschedule booking', BOOKACTI_PLUGIN_NAME ),
	'booking_action_refund'					=> esc_html_x( 'Request a refund', 'Dialog title', BOOKACTI_PLUGIN_NAME ),
	'booking_confirm_refund'				=> esc_html__( 'Refund confirmation', BOOKACTI_PLUGIN_NAME ),
	'booking_change_state'					=> esc_html__( 'Change booking state', BOOKACTI_PLUGIN_NAME ),


	// BUTTONS
	'dialog_button_ok'                  => esc_html__( 'OK', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_yes'                 => esc_html__( 'Yes', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_no'                  => esc_html__( 'No', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_delete'              => esc_html__( 'Delete', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_cancel'              => esc_html__( 'Cancel', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_create_activity'		=> esc_html__( 'Create Activity', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_import_activity'		=> esc_html__( 'Import Activity', BOOKACTI_PLUGIN_NAME ),
	/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event in order to edit it independently. 'Unbind selected' is a button that isolate the event the user clicked on. */
	'dialog_button_unbind_selected'     => esc_html__( 'Unbind Selected', BOOKACTI_PLUGIN_NAME ),
	/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event  in order to edit it independently. 'Unbind booked' is a button that split the repeating event in two : one repeating event holding all the booked events (restricted edition), and the other holding the events without bookings (fully editable). */
	'dialog_button_unbind_all_booked'   => esc_html__( 'Unbind Booked', BOOKACTI_PLUGIN_NAME ),
	/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event  in order to edit it independently. 'Unbind all' is a button that split the repeating event into multiple individual single events. */
	'dialog_button_unbind_all'          => esc_html__( 'Unbind All', BOOKACTI_PLUGIN_NAME ),
	/* translators: 'unbind' is the process to isolate one (or several) event from a repeating event  in order to edit it independently. 'Unbind' is a button that open a dialog where the user can choose wether to unbind the selected event, all events or booked events. */
	'dialog_button_unbind'				=> esc_html__( 'Unbind', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_cancel_booking'		=> esc_html_x( 'Cancel booking', 'Button label to trigger the cancel action', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_reschedule'			=> esc_html_x( 'Reschedule', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ),
	'dialog_button_refund'				=> esc_html_x( 'Request a refund', 'Button label to trigger the refund action', BOOKACTI_PLUGIN_NAME ),
	'calendar_button_list_year'			=> esc_html__( 'list year', BOOKACTI_PLUGIN_NAME ),
	'calendar_button_list_month'		=> esc_html__( 'list month', BOOKACTI_PLUGIN_NAME ),
	'calendar_button_list_week'			=> esc_html__( 'list week', BOOKACTI_PLUGIN_NAME ),
	'calendar_button_list_day'			=> esc_html__( 'list day', BOOKACTI_PLUGIN_NAME ),
	'booking_form_new_booking_button'	=> bookacti_get_message( 'booking_form_new_booking_button' ),

	// ERRORS
	'error_retrieve_event_data'			=> esc_html__( 'Error occurs when trying to retrieve event parameters.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_booking_numbers'	=> esc_html__( 'Error occurs when trying to retrieve booking numbers.', BOOKACTI_PLUGIN_NAME ),
	'error_update_event_param'          => esc_html__( 'Error occurs when trying to save event parameters.', BOOKACTI_PLUGIN_NAME ),
	'error_add_exception'               => esc_html__( 'Error occurs when trying to add repetition exceptions.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_exception'            => esc_html__( 'Error occurs when trying to delete repetition exceptions.', BOOKACTI_PLUGIN_NAME ),
	'error_display_event'               => esc_html__( 'Error occurs when trying to display events.', BOOKACTI_PLUGIN_NAME ),
	'error_insert_event'		        => esc_html__( 'Error occurs when trying to add an activity on the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_resize_event'                => esc_html__( 'Error occurs when trying to resize the event on the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_move_event'                  => esc_html__( 'Error occurs when trying to move the event on the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_render_event'                => esc_html__( 'Error occurs when trying to render the event on the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_exceptions'         => esc_html__( 'Error occurs when trying to retrieve the event exceptions.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_bookings'           => esc_html__( 'Error occurs when trying to retrieve the bookings.', BOOKACTI_PLUGIN_NAME ),
	'error_event_out_of_template'       => esc_html__( 'Error: The event has been placed out of the calendar period.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_event'                => esc_html__( 'Error occurs when trying to delete the event.', BOOKACTI_PLUGIN_NAME ),
	'error_no_template_selected'        => esc_html__( 'You must select a calendar first.', BOOKACTI_PLUGIN_NAME ),
	'error_create_template'             => esc_html__( 'Error occurs when trying to create the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_update_template'             => esc_html__( 'Error occurs when trying to update the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_template'             => esc_html__( 'Error occurs when trying to delete the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_switch_template'             => esc_html__( 'Error occurs when trying to change the default calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_template_data'		=> esc_html__( 'Error occurs when trying to retrieve calendar settings.', BOOKACTI_PLUGIN_NAME ),
	'error_create_activity'             => esc_html__( 'Error occurs when trying to create the activity.', BOOKACTI_PLUGIN_NAME ),
	'error_update_activity'             => esc_html__( 'Error occurs when trying to update the activity.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_activity'             => esc_html__( 'Error occurs when trying to delete the activity.', BOOKACTI_PLUGIN_NAME ),
	'error_import_activity'             => esc_html__( 'Error occurs when trying to import the activity.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_activity_data'		=> esc_html__( 'Error occurs when trying to retrieve activity settings.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_activity_bound'		=> esc_html__( 'Error occurs when trying to retrieve activities bound to calendars.', BOOKACTI_PLUGIN_NAME ),
	'error_no_avail_activity_bound'		=> esc_html__( 'No available activities found for this calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_update_bound_events'         => esc_html__( 'Error occurs when trying to update events bound to the updated activity.', BOOKACTI_PLUGIN_NAME ),
	'error_edit_locked_event'           => esc_html__( 'This event is booked, you cannot move it, change its duration, nor delete it.', BOOKACTI_PLUGIN_NAME ),
	'error_unbind_occurences'           => esc_html__( 'Error occurs when trying to unbind occurrences of the event.', BOOKACTI_PLUGIN_NAME ),
	/* translators: In the context, it is one of the message following 'There are bookings on at least one of the occurence of this event. You can't: ' */
	'error_move_locked_event'           => esc_html__( 'Move this occurence because it will affect the complete event.', BOOKACTI_PLUGIN_NAME ),
	/* translators: In the context, it is one of the message following 'There are bookings on at least one of the occurence of this event. You can't: ' */
	'error_resize_locked_event'         => esc_html__( 'Resize this occurence because it will affect the complete event.', BOOKACTI_PLUGIN_NAME ),
	/* translators: In the context, it is one of the message following 'There are bookings on at least one of the occurence of this event. You can't: ' */
	'error_delete_locked_event'         => esc_html__( 'Delete this occurence because it will affect the complete event.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_group_category_data'	=> esc_html__( 'Error occurs when trying to retrieve the group category settings.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_group_of_events_data'	=> esc_html__( 'Error occurs when trying to retrieve the group of events settings.', BOOKACTI_PLUGIN_NAME ),
	'error_create_group_of_events'		=> esc_html__( 'Error occurs when trying to create the group of events.', BOOKACTI_PLUGIN_NAME ),
	'error_update_group_of_events'		=> esc_html__( 'Error occurs when trying to update the group of events.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_group_of_events'		=> esc_html__( 'Error occurs when trying to delete the group of events.', BOOKACTI_PLUGIN_NAME ),
	'error_create_group_category'		=> esc_html__( 'Error occurs when trying to create the group category.', BOOKACTI_PLUGIN_NAME ),
	'error_update_group_category'		=> esc_html__( 'Error occurs when trying to update the group category.', BOOKACTI_PLUGIN_NAME ),
	'error_delete_group_category'		=> esc_html__( 'Error occurs when trying to delete the group category.', BOOKACTI_PLUGIN_NAME ),
	
	'error_display_product_events'		=> esc_html__( 'Error occurs when trying to display product events. Please try later.', BOOKACTI_PLUGIN_NAME ),
	'error_book_temporary'				=> esc_html__( 'Error occurs when trying to temporarily book your event. Please try later.', BOOKACTI_PLUGIN_NAME ),
	'error_book'						=> esc_html__( 'Error occurs when trying to book your event. Please try again.', BOOKACTI_PLUGIN_NAME ),
	/* translators: It is the message displayed to users if no events bookable were found. */
	'error_no_events_bookable'			=> esc_html__( 'Sorry, no events are available.', BOOKACTI_PLUGIN_NAME ),
	/* translators: It is the message displayed to users if no events were found according to search criterias (filters). */
	'error_no_results'					=> esc_html__( 'No results.', BOOKACTI_PLUGIN_NAME ),
	/* translators: It is the message displayed to users if no bookings were found for a given event. */
	'error_no_bookings'					=> esc_html__( 'No bookings.', BOOKACTI_PLUGIN_NAME ),
	'error_retrieve_booking_system'		=> esc_html__( 'Error occurs while trying to retrieve booking system.', BOOKACTI_PLUGIN_NAME ),
	'error_switch_booking_method'		=> esc_html__( 'Error occurs while trying to switch booking method.', BOOKACTI_PLUGIN_NAME ),
	'error_reload_booking_system'		=> esc_html__( 'Error occurs while trying to reload booking system.', BOOKACTI_PLUGIN_NAME ),
	'error_update_settings'				=> esc_html__( 'Error occurs while trying to update settings.', BOOKACTI_PLUGIN_NAME ),
	'error_not_allowed'					=> esc_html__( 'You are not allowed to do this.', BOOKACTI_PLUGIN_NAME ),
	'error_cancel_booking'				=> esc_html__( 'Error occurs while trying to cancel booking.', BOOKACTI_PLUGIN_NAME ),
	'error_reschedule_booking'			=> esc_html__( 'Error occurs while trying to reschedule booking.', BOOKACTI_PLUGIN_NAME ),
	'error_change_booking_state'		=> esc_html__( 'Error occurs while trying to change booking state.', BOOKACTI_PLUGIN_NAME ),
	'error_get_refund_booking_actions'	=> esc_html__( 'Error occurs while trying to request available refund actions.  Please contact the administrator.', BOOKACTI_PLUGIN_NAME ),
	'error_refund_booking'				=> esc_html__( 'Error occurs while trying to request a refund. Please contact the administrator.', BOOKACTI_PLUGIN_NAME ),
	'error_user_not_logged_in'			=> esc_html__( 'You are not logged in. Please create an account and log in first.', BOOKACTI_PLUGIN_NAME ),


	// FORMS CHECK
	'error_fill_field'                  => esc_html__( 'Please fill this field.', BOOKACTI_PLUGIN_NAME ),
	'error_invalid_value'               => esc_html__( 'Please select a valid value.', BOOKACTI_PLUGIN_NAME ),
	'error_template_end_before_begin'   => esc_html__( 'The calendar period can not end before it started.', BOOKACTI_PLUGIN_NAME ),
	'error_day_end_before_begin'		=> esc_html__( 'Day end time must be after day start time.', BOOKACTI_PLUGIN_NAME ),
	'error_repeat_period_not_set'		=> esc_html__( 'The repetition period is not set.', BOOKACTI_PLUGIN_NAME ),
	'error_repeat_end_before_begin'     => esc_html__( 'The repetition period can not end before it started.', BOOKACTI_PLUGIN_NAME ),
	'error_repeat_start_before_template'=> esc_html__( 'The repetition period should not start before the beginning date of the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_repeat_end_after_template'   => esc_html__( 'The repetition period should not end after the end date of the calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_days_sup_to_365'             => esc_html__( 'The number of days should be between 0 and 365.', BOOKACTI_PLUGIN_NAME ),
	'error_hours_sup_to_23'             => esc_html__( 'The number of hours should be between 0 and 23.', BOOKACTI_PLUGIN_NAME ),
	'error_minutes_sup_to_59'           => esc_html__( 'The number of minutes should be between 0 and 59.', BOOKACTI_PLUGIN_NAME ),
	'error_activity_duration_is_null'	=> esc_html__( 'The activity duration should not be null.', BOOKACTI_PLUGIN_NAME ),
	'error_availability_inf_to_0'       => esc_html__( 'The number of available bookings should be higher than or equal to 0.', BOOKACTI_PLUGIN_NAME ),
	'error_less_avail_than_bookings'    => esc_html__( "You can't set less available bookings than it has already on one of the occurrence of this event.", BOOKACTI_PLUGIN_NAME ),
	'error_booked_events_out_of_period' => esc_html__( 'The repetition period must include all booked occurences.', BOOKACTI_PLUGIN_NAME ),
	'error_event_not_btw_from_and_to'   => esc_html__( 'The selected event should be included in the period in which it will be repeated.', BOOKACTI_PLUGIN_NAME ),
	'error_freq_not_allowed'            => esc_html__( 'Error: The repetition frequency is not a valid value.', BOOKACTI_PLUGIN_NAME ),
	'error_excep_not_btw_from_and_to'   => esc_html__( 'Exception dates should be included in the repetition period.', BOOKACTI_PLUGIN_NAME ),
	'error_excep_duplicated'            => esc_html__( 'Exceptions should all have a different date.', BOOKACTI_PLUGIN_NAME ),
	'error_set_excep_on_booked_occur'   => esc_html__( 'Warning: this occurence is booked.', BOOKACTI_PLUGIN_NAME ),
	'error_select_schedule'				=> esc_html__( "You haven't selected any event. Please select an event.", BOOKACTI_PLUGIN_NAME ),
	'error_corrupted_schedule'			=> esc_html__( 'The event you selected is corrupted, please reselect an event and try again.', BOOKACTI_PLUGIN_NAME ),
	/* translators: %1$s is the quantity the user want. %2$s is the available quantity. */
	'error_less_avail_than_quantity'	=> esc_html__( 'You want to make %1$s bookings but only %2$s are available on this time slot. Please choose another event or decrease the quantity.', BOOKACTI_PLUGIN_NAME ),
	'error_quantity_inf_to_0'			=> esc_html__( 'The amount of desired bookings is less than or equal to 0. Please increase the quantity.', BOOKACTI_PLUGIN_NAME ),
	'error_no_templates_for_activity'	=> esc_html__( 'The activity must be bound to at least one calendar.', BOOKACTI_PLUGIN_NAME ),
	'error_no_activity_selected'		=> esc_html__( 'Select at least one activity.', BOOKACTI_PLUGIN_NAME ),
	'error_select_at_least_two_events'	=> esc_html__( 'You must select at least two events.', BOOKACTI_PLUGIN_NAME ),
	'error_missing_title'				=> esc_html__( 'You must type a title.', BOOKACTI_PLUGIN_NAME ),

	// ADVICE
	'advice_switch_to_maintenance'      => esc_html__( 'Please consider switching your website to maintenance mode when working on a published calendar.', BOOKACTI_PLUGIN_NAME ),
	'advice_booking_refunded'			=> esc_html__( 'Your booking has been successfully refunded.', BOOKACTI_PLUGIN_NAME ),
	'advice_refund_request_email_sent'	=> esc_html__( 'Your refund request has been sent. We will contact you soon.', BOOKACTI_PLUGIN_NAME ),


	// PARTICLES
	/* translators: In the context, 'Wednesday, March 2, 2016 9:30 AM to Thursday, March 3, 2016 1:30 PM' */
	'to_date'							=> esc_html_x( 'to', 'between two dates', BOOKACTI_PLUGIN_NAME ),
	/* translators: In the context, 'Wednesday, March 2, 2016 9:30 AM to 1:30 PM' */
	'to_hour'							=> esc_html_x( 'to', 'between two hours', BOOKACTI_PLUGIN_NAME ),
	'removed'							=> esc_html__( 'Removed', BOOKACTI_PLUGIN_NAME ),
	'cancelled'							=> esc_html__( 'Cancelled', BOOKACTI_PLUGIN_NAME ),
	'booked'							=> esc_html__( 'Booked', BOOKACTI_PLUGIN_NAME ),
	'pending_payment'					=> esc_html__( 'Pending payment', BOOKACTI_PLUGIN_NAME ),
	'loading'							=> esc_html__( 'Loading', BOOKACTI_PLUGIN_NAME ),
	'cancel'							=> esc_html_x( 'Cancel', 'action to cancel a booking', BOOKACTI_PLUGIN_NAME ),
	'refund'							=> esc_html_x( 'Refund', 'action to refund a booking', BOOKACTI_PLUGIN_NAME ),
	'refunded'							=> esc_html__( 'Refunded', BOOKACTI_PLUGIN_NAME ),
	'refund_requested'					=> esc_html__( 'Refund requested', BOOKACTI_PLUGIN_NAME ),
	'coupon_code'						=> esc_html__( 'Coupon code', BOOKACTI_PLUGIN_NAME ),
	/* translators: This particle is used right after the quantity of available bookings. Put the singular here. Ex: 1 avail. . */
	'avail'								=> esc_html_x( 'avail.', 'Short for availability [singular noun]', BOOKACTI_PLUGIN_NAME ),
	/* translators: This particle is used right after the quantity of available bookings. Put the plural here. Ex: 2 avail. . */
	'avails'							=> esc_html_x( 'avail.', 'Short for availabilities [plural noun]', BOOKACTI_PLUGIN_NAME ),
	/* translators: This particle is used right after the quantity of bookings. Put the singular here. Ex: 1 booking . */
	'booking'							=> esc_html__( 'booking', BOOKACTI_PLUGIN_NAME ),
	/* translators: This particle is used right after the quantity of bookings. Put the plural here. Ex: 2 bookings . . */
	'bookings'							=> esc_html__( 'bookings', BOOKACTI_PLUGIN_NAME ),


	// OTHERS
	'ask_for_reasons'					=> esc_html__( 'Tell us why? (Details, reasons, comments...)', BOOKACTI_PLUGIN_NAME ),
	'one_person_per_booking'			=> esc_html__( 'for one person', BOOKACTI_PLUGIN_NAME ),
	/* translators: %1$s is the number of persons who can enjoy the activity with one booking */
	'n_persons_per_booking'				=> esc_html__( 'for %1$s persons', BOOKACTI_PLUGIN_NAME ),
	'product_price'						=> esc_html__( 'Product price', BOOKACTI_PLUGIN_NAME ),
	'create_first_calendar'				=> esc_html__( 'Create your first calendar', BOOKACTI_PLUGIN_NAME ),
	'create_first_activity'				=> esc_html__( 'Create your first activity', BOOKACTI_PLUGIN_NAME ),
	/* translators: When the user is asked whether to pick the single event or the whole group it is part of */
	'single_event'						=> esc_html__( 'Single event', BOOKACTI_PLUGIN_NAME ),
	'selected_event'					=> esc_html__( 'Selected event', BOOKACTI_PLUGIN_NAME ),
	'selected_events'					=> esc_html__( 'Selected events', BOOKACTI_PLUGIN_NAME ),


	// VARIABLES
	'ajaxurl'							=> admin_url( 'admin-ajax.php' ),
	
	'is_qtranslate'						=> bookacti_get_translation_plugin() === 'qtranslate',
	'current_lang_code'					=> bookacti_get_current_lang_code(),
	
	'available_booking_methods'			=> array_keys( bookacti_get_available_booking_methods() ),

	'event_tiny_height'					=> apply_filters( 'bookacti_event_tiny_height', 30 ),
	'event_small_height'				=> apply_filters( 'bookacti_event_small_height', 75 ),
	'event_narrow_width'				=> apply_filters( 'bookacti_event_narrow_width', 70 ),
	'event_wide_width'					=> apply_filters( 'bookacti_event_wide_width', 250 ),

	'started_events_bookable'			=> bookacti_get_setting_value( 'bookacti_general_settings',	'started_events_bookable' ) ? true : false,
	'when_events_load'					=> bookacti_get_setting_value( 'bookacti_general_settings',	'when_events_load' ),
	'event_load_interval'				=> bookacti_get_setting_value( 'bookacti_general_settings', 'event_load_interval' ),
	'date_format'						=> bookacti_get_setting_value( 'bookacti_general_settings',	'date_format' ),
	'show_past_events_on_bookings_page'	=> bookacti_get_setting_value_by_user( 'bookacti_bookings_settings', 'show_past_events' ),

	'plugin_path'						=> plugins_url() . '/' . BOOKACTI_PLUGIN_NAME,
	'site_url'							=> get_site_url(),
	'admin_url'							=> get_admin_url(),
	'current_user_id'					=> get_current_user_id(),
	'is_admin'							=> is_admin(),
	'current_time'						=> $current_datetime_object->format( 'Y-m-d H:i:s' ),

	// NONCES
	'nonce_get_booking_system_data'		=> wp_create_nonce( 'bookacti_get_booking_system_data' ),
	'nonce_switch_booking_method'		=> wp_create_nonce( 'bookacti_switch_booking_method' ),
	'nonce_reload_booking_system'		=> wp_create_nonce( 'bookacti_reload_booking_system' ),

	'nonce_selected_template_filter'	=> wp_create_nonce( 'bookacti_selected_template_filter' ),
	'nonce_fetch_events'				=> wp_create_nonce( 'bookacti_fetch_events' ),
	'nonce_get_bookings'				=> wp_create_nonce( 'bookacti_get_bookings' ),
	'nonce_get_booking_rows'			=> wp_create_nonce( 'bookacti_get_booking_rows' ),
	'nonce_get_refund_actions_html'		=> wp_create_nonce( 'bookacti_get_refund_actions_html' ),
	'nonce_get_booking_data'			=> wp_create_nonce( 'bookacti_get_booking_data' ),

	'nonce_cancel_booking'				=> wp_create_nonce( 'bookacti_cancel_booking' ),
	'nonce_reschedule_booking'			=> wp_create_nonce( 'bookacti_reschedule_booking' ),

	'nonce_fetch_template_events'		=> wp_create_nonce( 'bookacti_fetch_template_events' ),
	'nonce_get_exceptions'				=> wp_create_nonce( 'bookacti_get_exceptions' ),
	'nonce_get_booking_numbers'			=> wp_create_nonce( 'bookacti_get_booking_numbers' ),

	'nonce_insert_event'				=> wp_create_nonce( 'bookacti_insert_event' ),
	'nonce_move_or_resize_event'		=> wp_create_nonce( 'bookacti_move_or_resize_event' ),
	'nonce_delete_event'				=> wp_create_nonce( 'bookacti_delete_event' ),
	'nonce_delete_event_forced'			=> wp_create_nonce( 'bookacti_delete_event_forced' ),
	'nonce_unbind_occurences'			=> wp_create_nonce( 'bookacti_unbind_occurences' ),
	
	'nonce_delete_group_of_events'		=> wp_create_nonce( 'bookacti_delete_group_of_events' ),
	'nonce_delete_group_category'		=> wp_create_nonce( 'bookacti_delete_group_category' ),
	
	'nonce_switch_template'				=> wp_create_nonce( 'bookacti_switch_template' ),
	'nonce_deactivate_template'			=> wp_create_nonce( 'bookacti_deactivate_template' ),

	'nonce_get_activities_by_template'	=> wp_create_nonce( 'bookacti_get_activities_by_template' ),
	'nonce_import_activity'				=> wp_create_nonce( 'bookacti_import_activity' ),
	'nonce_deactivate_activity'			=> wp_create_nonce( 'bookacti_deactivate_activity' ),

	'nonce_dismiss_5stars_rating_notice'=> wp_create_nonce( 'bookacti_dismiss_5stars_rating_notice' ),
) );