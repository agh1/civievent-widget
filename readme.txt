=== CiviEvent Widget ===
Contributors: agh1
Tags: civicrm, events
Requires at least: 3.3
Tested up to: 4.3
Stable tag: 1.3
License: AGPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.html

Display widgets for CiviCRM events: the next public event or a whole list.

== Description ==

You can use the CiviEvent widget to add two types of widgets for upcoming public events from CiviCRM.  There's no limit to the number of widgets you can add of either type.

= CiviEvent List Widget =

This widget is a basic, flexible listing of upcoming events that are marked as public.  You have options to customize the appearance and number of events.  There is the option to add the event's city, state, and/or country to the listing if "Show location" is enabled on the event.

= Single CiviEvent Widget =

This widget displays a single public event from CiviCRM.  By default, it will display the first event from the current day or the future, or you can set an offset to skip one or more and display the second or third upcoming event.  You may display the location if "Show location" is enabled on the event.

= Further Notes =

This plugin requires CiviCRM 4.3 or higher.

Read more at http://aghstrategies.com/civievent-widget

== Installation ==

1. Upload `civievent-widget` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the 'Widgets' page in WordPress to add and configure one or more widgets.

== Changelog ==

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
