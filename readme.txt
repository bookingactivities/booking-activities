=== Booking Activities ===
Contributors: bookingactivities
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7EKU434L7NEVC
Tags: booking activities, booking form, manage reservations, activity planning, events calendar, booking sport, booking system, booking, reservations, appointments, woocommerce
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Create your activity calendars by drag and drop and book scheduled events with one click. Enable reservation online payments with WooCommerce.



== Description ==

Booking Activities is a complete booking system designed for any kind of activity providers *(See the [demo website](http://demo.booking-activities.fr/en/))*. 
Drag and drop your events to build your activity planning. Copy / Paste a shortcode to display booking forms. Pick an event onto this calendar and book it. Calendars are automatically integrated to WooCommerce product pages.


= Features you and your customers will love: =

* WYSIWYG drag and drop events calendar editor
* Shortcodes to display booking forms, customer's bookings list, or simple calendars
* One-click booking: just pick the desired event on the calendar and click on "Book"!
* Allow payments with WooCommerce
	* Reservation Systems automatically integrated to product pages
	* **Cart expiration system** included
	* Auto-validation when payment is completed
	* Allow automatic refunds by coupon
* Manage reservations visually: 
	* Click on the desired event to show its bookings list
	* Booking actions: validate, cancel, reschedule, refund...
* Your customers can **cancel** or **reschedule** their bookings and **ask a refund** by themselves
	* Swith on / off these features, and set a minimum delay before event to allow them
* Multilingual support with QTranslateX
	* You can also help us [translating Booking Activities](https://translate.wordpress.org/projects/wp-plugins/booking-activities) in your language

More information at [booking-activities.fr](http://booking-activities.fr/en).


= Extend these features with add-ons: =

**[Display Pack](http://booking-activities.fr/en/downloads/display-pack/)**
*Customize your activity plannings or set alternative display*

* Calendar advanced customize options
	* Set calendars start / end and its height
	* Hide days, set timeslot duration
	* New views available. Basic: No timeslot, events are stacked (same as month view)
	* And much more...
* Waterfall alternative booking method: Replace the calendar by tiny datepicker and timepicker


**[Prices and Promotions](http://booking-activities.fr/en/downloads/prices-and-promotions/)**

*Set prices and discounts on your events to draw your customers attention where you want to*

* Set a price or a discount on whole activity or a single event
* Perfectly integrated to WooCommerce:
	* WC price is overriden
	* Discounts are based on WC product price if no activity price is set


**[Order for Customers](http://booking-activities.fr/en/downloads/order-for-customers/)**

*Your operators and salespersons can place order in the name of your customers*

* Create a user and book an event for him from your admin account
* Perfectly integrated to WooCommerce:
	* Select the customer you want to book for on Checkout
	* New 'Pay Later' gateway allow your customers to pay on your website later:
		* Your operators place order for your customer
		* He receives an email with a pay link
		* He is redirected to his order's checkout on you website where he can pay


*See the whole [add-ons list here](http://booking-activities.fr/en/add-ons/)*


= We are listening =

Booking Activities has been **designed and developed in collaboration with activity providers** *(nautical, aerial, land, mountain, foot or wheels, outdoor or indoor, sportive or cultural like courses, museums, cinemas... And probably yours!)*, and it will always be so. Then feel free to tell us if you miss a feature, if you find a bug or anything to make your booking system better!

**Report a bug / Request a feature:** [GitHub](https://github.com/bookingactivities/booking-activities/issues/)

**Contact Us:** [Contact form](http://booking-activities.fr/en/#contact) (French and English)

___

For any question about how to use Booking Activities, take a look at the [documentation](http://booking-activities.fr/en/documentation/user-documentation/).

If you don't find the answer you need, please [contact us](http://booking-activities.fr/en/#contact) (French and English).



== Frequently Asked Questions ==

= I am not an activity provider, can this plugin still meet my needs? =
Yes of course. It is basically a reservation system based on event scheduling. You can find another purposes to it, make sure to [tell us](http://booking-activities.fr/en/#contact) if you do :).
To know if it actually meets your needs, simply try it, it's free. 
If you are on a hurry, you can just check the [demo website](http://demo.booking-activities.fr/en/).
Or read the full [features description](http://booking-activities.fr/en/documentation/features/) if you are looking for something in particular.

= Create your first calendar = 
*Make sure Booking Activities is activated* 

1. Go to Booking Activities / Calendar Editor page
2. Click on the big '+' button
3. Set a title, an opening date and a closing date
4. Click on the dialog OK button, the calendar is loading!


= Create your first activity = 
*Make sure to have at least one calendar* 

1. Go to Booking Activities / Calendar Editor page
2. Click on the '+' button next to 'Activities' area
3. Set a title, an availability amount, a color and a duration
4. Click on the dialog OK button, the activity is added to the list!


= Create your first event = 
*Make to have at least one calendar and one activity* 

1. Go to Booking Activities / Calendar Editor page
2. Drag an activity from the list and drop it on the calendar
3. Drag and drop the event to move it, click on it to edit its properties


= Display a reservation form = 
*Make to have at least one calendar, one activity and one event at a future date* 

1. Go to Booking Activities / Calendar Editor page
2. Select the desired calendar and show / hide activities you want
3. Copy the shortcode at the bottom of the page (it looks like `[bookingactivities_form calendars='' activities='']`)
4. Past this shortcode in any post or page you like
5. Go on this post / page frontend, the booking system appears! 
*The user must be logged in to book an event.*


= Display the calendar only = 
*Make to have at least one calendar, one activity and one event at a future date* 

1. Go to Booking Activities / Calendar Editor page
2. Select the desired calendar and show / hide activities you want
3. Copy the shortcode at the bottom of the page (it looks like `[bookingactivities_calendar calendars='' activities='']`)
4. Past this shortcode in any post or page you like
5. Go on this post / page frontend, the calendar appears!


= Display user's bookings list = 
*Make sure the user has bookings and he is logged in, otherwise it will not show anything* 

1. Past this shortcode in any post or page you like: `[bookingactivities_list]`
2. Go on this post / page frontend, the bookings list appears!
*Depending on BA settings and bookings dates, actions like cancel or reschedule may appear. Try them!* 


= Use it with WooCommerce = 
*Make to have at least one calendar, one activity and one event at a future date for each activity* 
*You also need to activate WooCommerce and create one product* 

1. Go to Products / *Your Product*
2. In 'Product data' area, check 'Activity', a new 'Activity' tab appears
3. In 'Activity' tab, set a calendar and an activity to be bound to the product
4. Go on this product page on the frontend, a booking form appears!
*For Variable Products, you need to check the 'Activity' checkbox and set a calendar and an activity for each variation.* 


= Does this plugin accept reservation online payments? =
Yes, you can accept payments for bookings through WooCommerce. Booking Activities is perfectly integrated to WooCommerce:

* Booking forms will appears automatically on product pages 
* A cart expiration system is implemented to make sure that in cart bookings won't stay in cart forever, taking the place of someone else
* Bookings are automatically validated when the payment is received, or cancelled if not
* If you change order quantity or state, so do the bookings, and vice versa


= Should I accept payments for bookings? =
Quite a reccuring question from activities providers. Here is a pros and cons analysis:

**PROS:**

* Customers are engaged, if they book, they come.
* Saves time: customers have already paid.
* Customers can come without money, they can offer the activity to a relative
* Automatic cashing, billing, accounting and stats

**CONS:**

* May discourage customers, depending on the type of activity you provide and your target
* Takes more time for customers, and more diffult process, higher cart abandonment rate
* You will have to do much more development, administrative and legal procedures, and you will have more expenses (bank commission, maintenance ...)

We still recommend to accept online payments since it's a great way to automate your business management and make it grow.


= Events are not "Booked" after booking form submission, they are "Pending", why? =
Don't worry, the reservation is well registered. Now, it is up to you to turn it to "Booked" right away or when your customer comes, or when he gives you the money...
Note that if you use WooCommerce and online payments, booking states turn automatically to "Booked" if the payment is complete, or "Cancelled" if not.


= Cart expires but events are still booked = 
A bot clean expired bookings hourly. So just wait up to 1 hour.
Usually, users are still on your website when their bookings expire, if so, they are immediatly removed.
Else, they will be cancelled later, with the others in that case.


= My events appears to be booked, but no bookings appear in the list =
Temporary bookings (such as In cart events) take active slots but may not appear in the bookings list.
Just click on the settings wheel above the bookings list, and check 'Display temporary bookings'.
Now you can see all kind of active bookings (booked, pending, in cart).


= Cancelled bookings disapeared from the list, I need to see them! =
Just click on the settings wheel above the bookings list, and check 'Display inactive bookings'.
Now you can see inactive bookings (cancelled, expired, removed, refunded, refund requested).


___

For any question about how to use Booking Activities, take a look at the [documentation](http://booking-activities.fr/en/documentation/user-documentation/).

If you don't find the answer you need, please [contact us](http://booking-activities.fr/en/#contact) (French and English).



== Screenshots ==

1. WYSIWYG events calendar editor. Simply drag and drop events. Click on events for additionnal settings (availability, name or repetition).
2. One click booking: just pick the event you want and submit.
3. Manage your bookings: just click on the desired event and its bookings list appears. Then choose a booking action: change state (cancel, validate), reschedule, refund...
4. A reservation form appears on desired WooCommerce product pages.
5. WooCommerce cart expiration system: when time is up, bookings are cancelled and cart emptied.
6. Customers also have their bookings list, and they can cancel, reschedule or ask a refund by their own (if you allow them).



== Changelog ==

= 1.1.0 =
* Optimization - Calendars are now loaded faster and on page load (possibility to load after page load in BA settings)
* Feature - Calendars events are now related to your business timezone, and no longer to users' timezone. Customers around the world cannot see / book a past event because of time offset. Set this parameter in BA settings page.
* Delete - Removed trashes from editor, to delete calendars, activities and events please use their respective settings dialogs
* Delete - Deleted events 'occurrence id' since it is not a relevant identifier. All events can be identified by id + start + end datetimes.
* Fix - Fixed booking method checks in JS files (misuse of inArray)
* Fix - Fixed permission error after closing a dialog in template editor
* Fix - Apostrophe characters in template / activity / events names are now correctly displayed
* Fix - Copy a shortcode in calendar editor now copy only plain text, no more undesired html
* Delete - bookacti_display_booking_system() function replaced by bookacti_get_booking_system() which MUST be used to display a booking system
* Hooks changes:
  * JS hooks
    * Add - bookacti_booking_method_set_up
    * Add - bookacti_booking_method_fill_with_events
    * Add - bookacti_after_calendar_set_up
    * Add - bookacti_select_event
    * Add - bookacti_unselect_event
    * Add - bookacti_refresh_selected_events
    * Add - bookacti_validate_group_of_events_form
    * Add - bookacti_validate_group_category_form
    * Deleted - bookacti_validate_selected_event replaced by bookacti_validate_picked_event
    * Deleted - bookacti_activate_booking_system replaced by bookacti_rerender_events
  * PHP actions
    * Add - bookacti_group_of_events_tab_general_before
    * Add - bookacti_group_of_events_tab_general_after
    * Add - bookacti_group_category_tab_general_before
    * Add - bookacti_group_category_tab_general_after
	* Tweak - Merged all booking system parameters into one array in bookacti_before_booking_form, bookacti_booking_system_inputs, bookacti_before_booking_system_title, bookacti_before_booking_system, bookacti_booking_system_attributes, bookacti_after_booking_system, bookacti_before_date_picked_summary, bookacti_after_date_picked_summary, bookacti_after_date_picked, bookacti_booking_system_errors, bookacti_after_booking_system_errors, bookacti_after_booking_form
  * PHP filters
    * Add - bookacti_validate_group_activity_data
    * Add - bookacti_group_category_default_settings
    * Add - bookacti_group_category_settings
    * Add - bookacti_validate_group_of_events_data
    * Add - bookacti_group_of_events_default_settings
    * Add - bookacti_group_of_events_settings
    * Add - bookacti_get_booking_method_html
	* Tweak - Merged all booking system parameters into one array in bookacti_booking_system_title, bookacti_booking_system_auto_load, bookacti_date_picked_title
	* Tweak - Added $shortcode parameter to bookacti_formatted_booking_system_attributes
    * Delete - bookacti_shortcode_{$shortcode}_default_parameters replaced by core shortcode_atts_{$shortcode} (not exactly the same use, be careful)
    * Delete - bookacti_shortcode_{$shortcode}_return replaced by bookacti_shortcode_{$shortcode}_output
    * Delete - bookacti_shortcode_{$shortcode}_prevent_execution. Please use remove_shortcode($tag) function instead.
    * Delete - bookacti_shortcode_atts_{$shortcode}, bookacti_booking_system_auto_load and bookacti_booking_system_attributes. Please use bookacti_formatted_booking_system_attributes instead.
    
= 1.0.8 - 2017/05/31 =
* Fix - Fixed events not fetched if your database prefix was not exactly "wp_"
* Fix - "Create or import activity" dialog is closed before opening a new one. This prevent undesirable display and behavior.
* Fix - Flush rewrite rules on activate to avoid error 500
* Fix - Cron error in log/error.log appeared even if bookings were correctly deactivated hourly
* Fix - Check booking id before sync booking state in woocommerce meta to avoid errors

= 1.0.7 - 2017/05/27 =
* Fix - Fixed non-repeting events not fetched

= 1.0.6 - 2017/05/24 =
* Lib - Updated FullCalendar to 3.4 and Moment.js to 2.18.1
* Tweak - Calendars range is not restricted by booked events anymore
* Fix - Events out of their calendar range are not displayed and are impossible to book
* Fix - Fixed update booking list parameters always resulting in a permission error
* Fix - Added defined() check before constant definitions to avoid collisions
* Add - Added bookacti_updated action hook and stored plugin version in database
* Add - Added bookacti_validate_template_data action hook on template insert / update
* Tweak - Moved some functions from model-template.php to functions-template.php
* Delete - Deleted bookacti_validate_template function, replaced by bookacti_validate_template_data
* Delete - Deleted bookacti_deactivate_expired_bookings_hourly function, replaced by bookacti_controller_deactivate_expired_bookings

= 1.0.5 - 2017/05/11 =
* Fix - Fixed error messages not disappearing in event dialog on calendars editor
* Fix - Correct formating of booking id in WooCommerce emails

= 1.0.4 - 2017/04/20 =
* Fix - WooCommerce 3.0 supported and backward compatibility to WooCommerce 2.6
* Fix - Fixed issue causing separator between to dates or two hours not to show
* Fix - Fixed issue causing unique event in calendar or events closed to calendar limits not to show
* Fix - Fixed 'parent' booking method for variations
* Fix - Fixed refund via coupon AJAX call feedback
* Fix - Fixed dates not displayed when they had a special character such as "é"
* Fix - Fixed permission to create and read coupons when a user try to generate a refund coupon
* Fix - Fixed blank page when you 'Order Again' an order containing bookings. This fonctionnality is not supported yet, but now it leads to a proper error message.
* Fix - Hid reschedule timepicker when no date has been selected in datepicker (w/ Display Pack add-on)
* Fix - Replaced 'eventRender' JS action triggered on FullCalendar eventRender in calendar editor by 'bookacti_event_render'
* Fix - Fixed activity list not filtered by calendar on load in admin product page
* Tweak - Merged WC and BA confirmation notices when you add a product to cart to display only one
* Tweak - Hid in-cart, expired and removed bookings from users' bookings list (can be filtered with 'bookacti_bookings_list_hidden_states' filter)
* Add - Added 'bookacti_validate_selected_event' JS action
* Add - Added 'bookacti_temporary_book_message' filter to allow you to change the confirmation text when an activity has been added to cart through WooCommerce
* Add - Added 'bookacti_refund_coupon_code_template' filter to change the template of WC generated coupon code (with refund by coupon method)
* Add - Added 'bookacti_get_booking_product_id' function to retreive product id by booking id, if the reservation was made with WC

= 1.0.3 - 2017/03/29 =
* Feature - Added possibility to change calendar day start / end hours
* Fix - Corrected the [bookingactivities_calendar] shortcode name in calendar editor
* Fix - Fixed possible error while uninstalling plugin
* Localization - Updated fr_FR

= 1.0.2 - 2017/03/27 =
* Fix - Fixed error 500 on plugin activation (undefined function wp_get_current_user())

= 1.0.1 - 2017/03/25 =
* Fix - Updated shortcodes in readme.txt

= 1.0.0 - 2017/03/25 =
* Feature - Drag and drop planning editor
* Feature - Shortcodes to display reservation form, the calendar alone, or customers' list of reservations
* Feature - One click reservation system
* Feature - Woocommerce support for reservation online payments
* Feature - Woocommerce cart expiration system
* Feature - Backend reservation manager and frontend user's bookings list so that both can manage reservation



== Upgrade Notice ==

= 1.0.4 =
* If you are using Booking Activities with WooCommerce, make sure you are using 2.6.0 or later, and try to update to 3.0.x as soon as possible since BW compatibility functions are now deprecated
