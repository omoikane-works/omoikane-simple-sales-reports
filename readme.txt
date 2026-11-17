=== Omoikane Simple Sales Reports for Welcart ===
Contributors: omoikaneworks
Tags: welcart, sales report, report, order, japanese
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.1
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
* Use standard templates designed for A4 landscape printing
* Select a standard or custom template and remember the selection
* Duplicate templates and edit or delete custom templates
* Register, replace, or delete a logo and a company stamp in Settings
* Set whether to show each image and adjust its position and width in millimeters per template
* Open the template tag reference from the template editor

== Installation ==

1. Install this plugin from the "Plugins" section in the WordPress admin.
2. Activate the plugin.
3. Make sure Welcart is enabled.
4. Open "Sales Reports" from the "Simple Reports" menu in the WordPress admin.

== Frequently Asked Questions ==

= Is Welcart required? =

Yes. Welcart is required to use this plugin.

= Can I export PDF files directly? =

Currently, the plugin is designed to use the browser's print function for printing or saving as a PDF.

= Are canceled orders included in the sales total? =

Canceled orders are shown in the order details, but they are excluded from the sales total.

= Can templates be edited? =

Yes. In the Templates tab, choose "Duplicate and edit" to make an editable copy of a standard template. Open the template tag reference next to the template content field for the available tags.

Use the v1.1.0 tag format for new templates. To show or hide a section based on whether a point count is zero, use the point tag ending in .amount.

= How do I add a logo or company stamp to a report? =

Register the images in the Settings tab, then select the standard sales report template v1.1.0 when generating the report. It places registered images in the header of the summary page. To adjust placement, duplicate the standard template and edit the copy. Custom templates need the corresponding image tags in the template body. Image registration requires PHP's GD extension and supports static PNG, JPEG, and WebP files up to 1 MiB, 4,096 pixels per side, and 4,194,304 pixels in total.

== Screenshots ==

1. View sales reports, tax rate breakdowns, and order details in the WordPress admin.
2. Select the current month, previous month, or a custom date range, and choose a report template.
3. Print or save as a PDF using the browser's print function.

== Changelog ==

= 1.1.1 =
* Updated the tested WordPress version to 7.1.

= 1.1.0 =
* Added template management to view and duplicate standard templates and edit or delete custom templates.
* Added logo and company stamp registration, replacement, and deletion in Settings.
* Added per-template controls for image visibility, position, and width.
* Added standard sales report template v1.1.0 with logo and company stamp placement on the summary page.
* Added missing standard template revisions on upgrade while preserving stored templates and the saved template selection.
* Added the template tag reference and reorganized the amount, tax breakdown, and customer tags used by the bundled templates. Legacy tags continue to render in saved templates.
* Added sales support fee tags that round up per order and sum the fees for orders included in sales totals. The legacy fee tags continue to round up once for the report.
* Hid admin notices when printing with the bundled templates and included compiled admin assets in the distribution ZIP.

= 1.0.3 =
* Updated the release package to include composer.json.

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

= 1.1.0 =
Adds template management, logo and company stamp support, and a new standard template. Saved templates retain their content and selection. Use the tag reference for new templates.

= 1.0.3 =
Updated the release package to include composer.json.

= 1.0.2 =
Updated the release package to include production dependencies only.

= 1.0.1 =
Renamed the plugin and updated dependencies for WordPress.org review.

= 1.0.0 =
Initial release.
