=== Omoikane Simple Sales Reports for Welcart ===
Contributors: omoikaneworks
Tags: welcart, sales report, report, order, japanese
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create simple sales reports based on Welcart order data.

== Description ==

Omoikane Simple Sales Reports for Welcart is a WordPress plugin for creating simple sales reports based on Welcart order data.

It is designed for simple monthly reports and client submissions, rather than analytical dashboards.

Features:

* Generate sales reports
* Aggregate data for the current month, previous month, or a custom date range
* Display order details
* Display tax rate breakdowns
* Exclude canceled orders from sales totals
* Use a standard template designed for A4 landscape printing
* Select a standard report template
* Save the previously used standard template

= Future Extensions =

This plugin supports basic sales report creation and printing.

Future extensions may include template editing, PDF output, and more detailed report settings for business use.

== Installation ==

1. Install this plugin from the "Plugins" section in the WordPress admin.
2. Activate the plugin.
3. Make sure Welcart is enabled.
4. Open "Sales Report" from the "Simple Reports" menu in the WordPress admin.

== Frequently Asked Questions ==

= Is Welcart required? =

Yes. Welcart is required to use this plugin.

= Can I export PDF files directly? =

Currently, the plugin is designed to use the browser's print function for printing or saving as a PDF.

= Are canceled orders included in the sales total? =

Canceled orders are shown in the order details, but they are excluded from the sales total.

= Can templates be edited? =

This plugin supports selecting from standard templates.
Template editing is being considered for a future extension.

== Screenshots ==

1. View sales reports, tax rate breakdowns, and order details in the WordPress admin.
2. Select the current month, previous month, or a custom date range, and choose a report template.
3. Print or save as a PDF using the browser's print function.

== Changelog ==

= 1.0.2 =
* Improved the release package to include production dependencies only.
* Removed unnecessary development and Composer metadata files from the distributed ZIP.

= 1.0.1 =
* Renamed the plugin to Omoikane Simple Sales Reports for Welcart.
* Updated the plugin slug, text domain, namespace, and internal prefixes.
* Updated Mustache to 3.2.0.
* Updated source strings and Japanese translation files.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Updated the release package to include production dependencies only.

= 1.0.1 =
Renamed the plugin and updated dependencies for WordPress.org review.

= 1.0.0 =
Initial release.
