=== Injection Guard ===
Contributors: fahadmahmood, invoicepress
Tags: security, anti-hacking, sql injection, firewall, wordpress security
Requires at least: 5.3
Tested up to: 7.1
Stable tag: 1.4.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks malicious and unknown query-string requests, detects injection attempts, and audits user capabilities and sessions.

== Description ==
**Author:** [Fahad Mahmood](https://www.androidbubbles.com/contact)  
**Project URI:** <https://www.androidbubbles.com/extends/wordpress/plugins/injection-guard>  
**License:** GPL v2 or later

Injection Guard is a WordPress plugin designed to block malicious query string attacks and suspicious URL parameters. It logs all incoming attempts, blocks harmful parameters, and adds extra security intelligence to your WordPress admin—like user session tracking and capability audit.

The plugin uses the `ig_` prefix for database keys and functions, follows WordPress coding standards, and supports multiple languages. It's compatible with pretty permalinks and helps in securing your site from automated bots and manual attacks.

**How it works:** Injection Guard learns which query-string parameters your visitors use (only the names, never the values). You mark each one as good or bad from the Log tab; bad parameters are blocked on that URL from then on. New in 1.4: an optional attack-signature scan, a global blacklist, safe-parameter patterns, a blocked-requests list with CSV export, and a dashboard that shows what is happening at a glance.

= Pro version =
Automatic IP ban for repeat offenders, email alerts, a new-administrator watch (also catches accounts created straight in the database), an extended signature set with scanner user-agent blocking, an IP allowlist and a capability-audit CSV export.

== Installation ==

To install Injection Guard, you will need:
- WordPress 5.3 or later (tested up to 7.1)
- PHP 7.2 or later
- FTP/SFTP or hosting control panel access

=== Method A (Admin Panel) ===
1. Login to WordPress Admin > Plugins > Add New > Upload Plugin
2. Upload the ZIP file and activate the plugin
3. Go to Settings > Injection Guard, review the Settings tab and click "Save Settings"

=== Method B (Manual Upload) ===
1. Download and unzip the plugin package
2. Upload the folder to `/wp-content/plugins/injection-guard/`
3. Activate the plugin from the WordPress Dashboard
4. Visit Settings > Injection Guard to configure

== Frequently Asked Questions ==

= Does this plugin help with SEO? =
Yes. By preventing invalid URLs from being indexed, it supports clean crawl behavior.

= How does it work? =
It monitors incoming query strings and blocks those that don't match expected patterns. You can log, block, or analyze attempts.

= Will it block my own login or WooCommerce checkout? =
Only parameters you blacklist (or that match an attack signature in "block" mode) are blocked. Logged-in administrators are exempt by default, and you can list parameters that must never be blocked under Settings > Safe parameters (for example `utm_*`).

= Does it store IP addresses? =
Only in the blocked-requests list, anonymised by default, and for user login history. See the Privacy section of the settings. Everything stays in your own database and is removed when you delete the plugin.

= My site is behind Cloudflare or a proxy. =
Choose the matching option under Settings > "Where does the visitor IP come from?". Leave it on the default if your site is reached directly.

= What if I'm still getting suspicious traffic? =
Update to the latest version, use pretty permalinks, and tighten your query parameter policy.

= Can I get help with setup or troubleshooting? =
Yes. Use the plugin support tab or contact the author via the provided link.

== Features ==

- Logs all unique query strings attempting to penetrate your website
- Blocks malicious or unknown query parameters (per URL or on every URL)
- Safe-parameter list with prefix patterns such as `utm_*`
- Optional attack-signature scan (SQL injection, script injection, path traversal, PHP execution) in "record" or "block" mode
- Blocked-requests list with anonymised IPs, plus CSV export of the log and the blocked list
- Search and filter the log, clear it, or let it forget old entries automatically
- Dashboard with 24-hour counters and the latest blocked requests
- Tracks login, logout, session start and duration per user
- Capability audit report for all WordPress users, with search and a flagged-only filter
- Multi-language support (FR, DE, ES)
- Bootstrap-based admin UI and dashboard

== Screenshots ==
1. Dashboard with 24-hour counters and the latest blocked requests
2. Log tab: search, filter and whitelist / blacklist parameters
3. Blocked tab: what was stopped, when and why
4. Settings tab: signature scan, safe parameters, global blacklist, privacy options
5. Capability audit screen with suspicious flagging

== Changelog ==
= 1.4.0 =
* Tested up to WordPress 7.1.
* New: attack-signature scan of query strings (off / record / block).
* New: Blocked tab and blocked-requests list (anonymised IPs, rate-limited writes) with CSV export; log CSV export.
* New: Settings tab: safe parameters (prefix patterns), global blacklist, 404 or 403 response, log limits and retention, proxy / CDN IP header choice, administrator exemption.
* New: dashboard counters, log search and filters, clear-log button, capability audit search with flagged-only filter and last-login column.
* New (Pro): automatic IP ban, email alerts, new-administrator watch, extended signatures and scanner user agents, IP allowlist, capability audit CSV.
* Fix: the request log was overwritten by every visit of a non-administrator, so logging only worked while an administrator was browsing. Logging now works for all visitors.
* Fix: bulk whitelist / blacklist saved the wrong URL for paths containing a slash; single actions and bulk actions now share the same normalisation.
* Fix: the capability audit table never filled because its rows were parsed outside a table; the endpoint now returns JSON. A stray global `table {}` style was removed from the admin.
* Fix: block check and log compared different spellings of a parameter (for example `Foo` and `foo`); both are now normalised, as are encoded and dot-segment paths, so such variations no longer slip past a blacklist.
* Fix: block page no longer renders blank on block themes or themes without a 404 template.
* Security: bounded the log (parameters per request, per URL, name length) so a flood of random query strings cannot bloat the database; the log is no longer autoloaded and is written only when something changes.
* Security: visitor IP now uses REMOTE_ADDR unless a trusted proxy header is selected (forwarded headers can be forged); the IP history per user is capped.
* Security: removed the dashboard `?hash` debug export that wrote customer email hashes and password hashes to a file in the plugin folder; any leftover `ab-hash.dat` is deleted on update.
* Security: AJAX handlers answer with JSON errors, validate every input, and settings are saved through a nonce-protected admin-post handler; CSV exports neutralise spreadsheet formulas; the debug helper `pree()` is restricted to administrators and escapes its output.
* Fix: WooCommerce customer table no longer needs WooCommerce to be installed, works with HPOS orders, and escapes all output; external geolocation lookups are cached, time-limited and validated.
* Improvement: no plugin file parsing on public page views; script versions use the plugin version instead of the current time so browsers can cache them.
* Improvement: text domain is now loaded, remaining strings are translatable, an `uninstall.php` removes the plugin's data, and privacy-policy text is provided.
= 1.3.0 =
* Security: Fixed Stored XSS vulnerability by sanitizing query parameter keys, escaping admin output, improving query handling, and adding a log size limit. [Thanks to Wordfence / 15/03/2026 / Aramsri (Boeing777)].
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
= 1.4.0 =
Fixes request logging for visitors, hardens input handling and the log size, removes an unsafe debug export, and adds a signature scan, blocked-requests list and new settings. Tested up to WordPress 7.1.
= 1.3.0 =
Security: Fixed Stored XSS vulnerability by sanitizing query parameter keys, escaping admin output, improving query handling, and adding a log size limit.
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