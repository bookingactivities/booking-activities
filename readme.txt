=== Booking Activities ===
Contributors: bookingactivities
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7EKU434L7NEVC
Tags: booking, reservations, calendar, planning, booking system, booking form, activities, activity, sport, events, rental, appointments, woocommerce
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Create your activity calendars with drag and drop and book scheduled events with one click. Enable online payments of reservations with WooCommerce.



== Description ==

Booking Activities is a complete booking system designed for any kind of activity providers (nautical, aerial, land, mountain, foot or wheels, outdoor or indoor, sportive or cultural like courses, museums, cinemas... And probably yours!). It features a **unique WYSIWYG planning editor**: simply **drag and drop your events** to build your calendars. Then your customers will just have to **pick their desired event** onto this calendar to book it.

Booking Activities has been **designed and developed in collaboration with activity providers**, and it will always be so. Then feel free to tell us if you miss a feature, if you find a bug or anything to make your booking system better!

**Report a bug / Request a feature:** [GitHub](https://github.com/bookingactivities/booking-activities/issues/)

**Contact Us:** [Contact form](http://booking-activities.fr/en/#contact) (French and English)

= Features you and your customers will love: =

* WYSIWYG drag'n drop calendar editor
* Manage repeated events and exceptions
* Shortcodes to display booking forms, customer's bookings list, and read-only calendars
* One-click booking: just pick your event on the calendar
* Allow payments with WooCommerce
	* Booking Systems automatically integrated to product pages
	* **Cart expiration system** included
	* Auto-validation when payment is completed
	* Allow automatic refunds by coupon
* Manage your bookings visually: 
	* Click on the desired event to show its bookings list
	* Booking actions: validate, cancel, reschedule, refund...
* Your customers can **cancel** or **reschedule** their bookings and **ask a refund** by themselves
	* Swith on / off these features, and set a minimum delay before event to allow them
* Multilingual support with QTranslateX
	* You can also [help us](http://booking-activities.fr/en/#contact) translating Booking Activities in your language

More information at [booking-activities.fr](http://booking-activities.fr/en).

= Extend these features with add-ons: =
*See the whole [add-ons list here](http://booking-activities.fr/en/add-ons/)*

**[Display Pack](http://booking-activities.fr/en/downloads/display-pack/)**
*Customize your calendars or set alternative display*

* Calendar advanced customize options
	* Set calendars start / end and its height
	* Hide days, set timeslot duration
	* New views available. Basic: No timeslot, events are stacked (same as month view)
	* And much more...
* Waterfall alternative booking method: Replace the calendar by tiny datepicker and timepicker


**[Prices and Promotions](http://booking-activities.fr/en/downloads/prices-and-promotions/)**

*Set prices and discounts on your events to draw your customers attention where you want to*

* Set a price or a discount on whole activity or single events
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


**[Points of Sale](http://booking-activities.fr/en/downloads/points-of-sale/)** *Require WooCommerce*

*You have multiple points of sale but you want to sell and manage your activities from one single website*

* Your POS managers can manage their own point of sale and products in total autonomy, in their own separated space
	* No risk of interfering with another POS product
* They can manage their own calendars, activities, events and bookings
* They can set different price, description, calendar, availabilities for each of their product



== Installation ==

= Install Booking Activities = 
1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress


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


= Display a booking form = 
*Make to have at least one calendar, one activity and one event at a future date* 

1. Go to Booking Activities / Calendar Editor page
2. Select the desired calendar and show / hide activities you want
3. Copy the shortcode at the bottom of the page (it looks like `[bookacti_form calendars='' activities='']`)
4. Past this shortcode in any post or page you like
5. Go on this post / page frontend, the booking system appears! 
*The user must be logged in to book an event.*


= Display the calendar only = 
*Make to have at least one calendar, one activity and one event at a future date* 

1. Go to Booking Activities / Calendar Editor page
2. Select the desired calendar and show / hide activities you want
3. Copy the shortcode at the bottom of the page (it looks like `[bookacti_cal calendars='' activities='']`)
4. Past this shortcode in any post or page you like
5. Go on this post / page frontend, the calendar appears!


= Display user's bookings list = 
*Make sure the user has bookings and he is logged in, otherwise it will not show anything* 

1. Past this shortcode in any post or page you like: `[bookacti_list]`
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

___

For any question about how to use Booking Activities, take a look at the [documentation](http://booking-activities.fr/en/documentation/user-documentation/).

If you don't find the answer you need, please [contact us](http://booking-activities.fr/en/#contact) (French and English).



== Frequently Asked Questions ==

= I am not an activity provider, can this plugin still meet my needs? =
Yes of course. It is basically a booking system based on event scheduling. You can find another purposes to it, make sure to [tell us](http://booking-activities.fr/en/#contact) if you do :).
To know if it actually meets your needs, simply try it, it's free. 
If you are on a hurry, you can just check the [demo website](http://demo.booking-activities.fr/en/).
Or read the full [features description](http://booking-activities.fr/en/documentation/features/) if you are looking for something in particular.


= Does this plugin accept payments for bookings? =
Yes, through WooCommerce. Booking Activities is perfectly integrated to WooCommerce:

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
Don't worry, the booking is well registered. Now, it is up to you to turn it to "Booked" right away or when your customer comes, or when he gives you the money...
Note that if you use WooCommerce and online payments, booking states turn automatically to "Booked" if the payment is complete, or "Cancelled" if not.


= Cart expires but events are still booked = 
A bot clean expired bookings hourly. So just wait up to 1 hour.
Usually, users are still on your website when their bookings expire, if so, they are immediatly removed.
Else, they will be cancelled later, with the others in that case.


= My events appears to be booked, but no bookings appear in the list =
Temporary bookings (such as In cart events) take active slots but may not appear in the bookings list.
Just click on the settings wheel above the bookings list, and check 'Display temporary bookings'.
Now you can see all kind of active bookings (booked, pending, in cart).


= Cancelled booking disapeared from the list, I need to see them! =
Just click on the settings wheel above the bookings list, and check 'Display inactive bookings'.
Now you can see inactive bookings (cancelled, expired, removed, refunded, refund requested).


___

For any question about how to use Booking Activities, take a look at the [documentation](http://booking-activities.fr/en/documentation/user-documentation/).

If you don't find the answer you need, please [contact us](http://booking-activities.fr/en/#contact) (French and English).



== Screenshots ==

1. WYSIWYG calendar editor. Simply drag and drop events. Click on them for additionnal settings such as availability, name or repetition.
2. One click booking: just pick the event you want and submit.
3. Manage your bookings: just click on the desired event and its bookings list appears. Then choose a booking action: change state (cancel, validate), reschedule, refund...
4. A booking form appears on desired WooCommerce product pages.
5. WooCommerce cart expiration system: when time is up, bookings are cancelled and cart emptied.
6. Customers also have their bookings list, and they can cancel, reschedule or ask a refund by their own (if you allow them).



== Changelog ==

= 1.0.0 - 2017/03/25 =
* Booking Activities at your service!


== Upgrade Notice ==


