=== Injection Guard ===
Contributors: fahadmahmood, invoicepress, alianwaar91
Tags: security, anti-hacking, sql injection, firewall, wordpress security, injection guard
Requires at least: 3.0
Tested up to: 6.9
Stable tag: 1.2.9
Requires PHP: 7.0
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
This plugin blocks all unauthorized and irrelevant requests through query strings and provides extended session tracking and capability audit.

== Description ==
**Author:** [Fahad Mahmood](https://www.androidbubbles.com/contact)  
**Project URI:** <https://www.androidbubbles.com/extends/wordpress/plugins/injection-guard>  
**License:** GPL v3

Injection Guard is a WordPress plugin designed to block malicious query string attacks and suspicious URL parameters. It logs all incoming attempts, blocks harmful parameters, and adds extra security intelligence to your WordPress admin—like user session tracking and capability audit.

The plugin uses the `ig_` prefix for database keys and functions, follows WordPress coding standards, and supports multiple languages. It's compatible with pretty permalinks and helps in securing your site from automated bots and manual attacks.

== Installation ==

To install Injection Guard, you will need:
- WordPress 3.0 or later
- FTP/SFTP or hosting control panel access

=== Method A (Admin Panel) ===
1. Login to WordPress Admin > Plugins > Add New > Upload Plugin
2. Upload the ZIP file and activate the plugin
3. Go to Settings > IG Settings and click "Save Settings"

=== Method B (Manual Upload) ===
1. Download and unzip the plugin package
2. Upload the folder to `/wp-content/plugins/injection-guard/`
3. Activate the plugin from the WordPress Dashboard
4. Visit Settings > IG Settings to configure

== Frequently Asked Questions ==

= Does this plugin help with SEO? =
Yes. By preventing invalid URLs from being indexed, it supports clean crawl behavior.

= How does it work? =
It monitors incoming query strings and blocks those that don't match expected patterns. You can log, block, or analyze attempts.

= What if I'm still getting suspicious traffic? =
Update to the latest version, use pretty permalinks, and tighten your query parameter policy.

= Can I get help with setup or troubleshooting? =
Yes. Use the plugin support tab or contact the author via the provided link.

== Features ==

- Logs all unique query strings attempting to penetrate your website
- Blocks malicious or unknown query parameters
- Tracks login, logout, session start and duration per user
- Capability audit report for all WordPress users
- Multi-language support (FR, DE, ES)
- Bootstrap-based admin UI and dashboard

== Screenshots ==
1. Settings panel with log and blocking tools
2. Capability audit screen with suspicious flagging

== Changelog ==
= 1.2.9 =
* Security Fix: Added depth-limited recursive sanitization for all POST/array inputs to prevent possible injection or malformed data attacks. [Thanks to Wordfence / 28/02/2026].
* Security Fix: Enforced stricter nonce and super-admin checks on bulk whitelist/blacklist updates. [Thanks to Wordfence / 28/02/2026].
* Security Fix: Standardized all AJAX JSON responses to use wp_send_json_success() / wp_send_json_error() for safer output. [Thanks to Wordfence / 28/02/2026].
= 1.2.8 =
* Security Fix: Escaped `$_SERVER['REQUEST_URI']` output to prevent Reflected Cross-Site Scripting (XSS) vulnerability in the admin logs tab. [Thanks to WPScan / 22/07/2025].
= 1.2.7 =
* Enhanced security for capability audit with proper nonce validation and admin-only access.
= 1.2.6 =
* Added user session tracking, logout history, and capability audit with proper prefixing (`ig_`) and textdomain usage. [Thanks to Hila / 10/04/2025]
= 1.2.4 =
* Updated version for WordPress. [05/06/2024]
= 1.2.3 =
* Updated version for pioneer. [Thanks to alianwaar91][11/05/2023]
= 1.2.2 =
* Updated version for vulnerable to Broken Access Control. [Thanks to Darius Sveikauskas | Patchstack Alliance overlord][10/05/2023]
= 1.2.1 =
* Updated version for WordPress. [07/09/2022]
= 1.2.0 =
* Bootstrap, FontAwesome and timestamp based log added. [Thanks to Team Ibulb Work]
= 1.1.9 =
* Updating jQuery functions.
= 1.1.8 =
* Updating FAQs.
= 1.1.7 =
* Languages added. [Thanks to Abu Usman]
= 1.1.6 =
* Dashboard refined with customers results.
= 1.1.5 =
* Dashboard introduced for registered users activity regarding orders and logins from different locations.
= 1.1.4 =
* Sanitized input and fixed direct file access issues.
= 1.1.3 =
* Updating a few Illegal string offset conditions. [Thanks to PapGeo]
= 1.1.2 =
* Updating a few Illegal string offset conditions.
= 1.1.0 =
* Releasing with WP Mechanic free help feature.

== Upgrade Notice ==
= 1.2.9 =
Security Fix: Added depth-limited input sanitization and stricter nonce & super-admin checks for bulk whitelist/blacklist updates.
= 1.2.8 =
Security Fix: Escaped `$_SERVER['REQUEST_URI']` output to prevent Reflected Cross-Site Scripting (XSS) vulnerability in the admin logs tab.
= 1.2.7 =
Enhanced security for capability audit with proper nonce validation and admin-only access.
= 1.2.6 =
Added user session tracking, logout history, and capability audit with proper prefixing (`ig_`) and textdomain usage. 
= 1.2.4 =
Updated version for WordPress.
= 1.2.3 =
Updated version for pioneer.
= 1.2.2 =
Updated version for vulnerable to Broken Access Control.
= 1.2.1 =
Updated version for WordPress.
= 1.2.0 =
Bootstrap, FontAwesome and timestamp based log added.
= 1.1.9 =
Updating jQuery functions.
= 1.1.8 =
Updating FAQs.
= 1.1.7 =
Languages added.
= 1.1.6 =
Dashboard refined with customers results.
= 1.1.5 =
Dashboard introduced for registered users activity regarding orders and logins from different locations.
= 1.1.4 =
Sanitized input and fixed direct file access issues.
= 1.1.3 =
Updating a few Illegal string offset conditions.
= 1.1.2 =
Updating a few Illegal string offset conditions.
= 1.1.0 =
Releasing with WP Mechanic free help feature.

== License ==
This plugin is free software licensed under the GNU GPL v2 or later.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.