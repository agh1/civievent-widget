=== CiviEvent Widget ===
Contributors: agh1
Tags: civicrm, events
Requires at least: 3.3
Tested up to: 4.3
Stable tag: 2.0
License: AGPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.html

Display widgets for CiviCRM events: the next public event or a whole list.

== Description ==

You can use the CiviEvent widget to add two types of widgets for upcoming public events from CiviCRM.  There's no limit to the number of widgets you can add of either type.

= CiviEvent List Widget =

This widget is a basic, flexible listing of upcoming events that are marked as public.  You have options to customize the appearance and number of events.  There is the option to add the event's city, state, and/or country to the listing if "Show location" is enabled on the event.

= Single CiviEvent Widget =

This widget displays a single public event from CiviCRM.  By default, it will display the first event from the current day or the future, or you can set an offset to skip one or more and display the second or third upcoming event.  You may display the location if "Show location" is enabled on the event.

= Shortcodes =

Both widgets are available to be inserted into the body of a post using a shortcode.  Use the `[civievent_widget]` shortcode for the events listing and the `[civievent_single_widget]` shortcode for the single next (or offset) event.  The available parameters for the shortcodes are as follows:

* **`title="Your Title"`** The widget title (default: "Upcoming Events" for the list widget, or the event's title for the single widget).
* **`summary=1`** Display the summary.  Omit the parameter or set it to 0 to hide the summary. (List widget only.)
* **`limit=5`** Display the specified number of events (default: 5).  (List widget only.)
* **`alllink=1`** Display "view all" with a link to the page with a full list of public events.  Omit the parameter or set it to 0 to hide the link.  (List widget only.)
* **`wtheme="mytheme"`** The widget theme (a class added to the widget div).  Set a new one and handle it in your theme's CSS.  (Default for list widget: "stripe", with "divider" as an alternative.  Default for single widget: "standard".)
* **`divider=" | "`** The location field delimiter (default: comma followed by a space).
* **`city=1`** Display the event's city.  Omit the parameter or set it to 0 to hide the city.
* **`state="abbreviate"`** Display the event's state/province.  Default is "none", which will display nothing.  Display options are "abbreviate" for the state/province abbreviation or "full" for the full name.
* **`country=1`** Display the event's country.  Omit the parameter or set it to 0 to hide the country.
* **`offset=2`** Skip the given number of events (default: 0).  (Single widget only.)

= Further Notes =

This plugin requires CiviCRM 4.3 or higher.

Read more at http://aghstrategies.com/civievent-widget

== Installation ==

1. Upload `civievent-widget` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the 'Widgets' page in WordPress to add and configure one or more widgets.
1. Insert shortcodes into posts or pages as appropriate.

== Changelog ==

= 2.0 =
* Both widgets are now available as shortcodes

= 1.3 =
* Cleanup for PHP strict notices
* Compatibility for WordPress 4.3

= 1.2 =
* New single event widget for displaying the next upcoming event
* Offset the single event widget to display the second or third upcoming event

= 1.1 =
* Suppress repetitive names for city, state, and/or country (e.g. Singapore, Singapore)
* Wrapped all strings in translate functions
* Option to set city, state, country divider

= 1.0 =
* Option to display City, State, and/or Country

= 0.3 =
* Solving some numbering confusion in the WP plugin directory

= 0.2 =
* New option for displaying event summary
* Theme adjustments
* Better handling if CiviCRM itself isn't enabled and installed

= 0.1 =
* Initial version
