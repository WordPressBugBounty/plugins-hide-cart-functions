=== Hide Cart Functions ===

Contributors: Artiosmedia, steveneray, arafatrahmanbd
Donate link: https://www.zeffy.com/en-US/donation-form/your-donation-makes-a-difference-6
Tags: hide price, hide quantity, hide option, hide add to cart, hide category
Requires at least: 5.8
Tested up to: 6.9.1
Stable tag: 1.2.16
Requires PHP: 8.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Hide the product's price, add-to-cart button, quantity, and options on any product and order. Inject an optional message.

== Description ==

Several plugins offer the ability to edit the shopping cart functions on the page, including hiding the price, "Add to Cart" button, quantity selector, and product options dropdown, but not often in one plugin. Additionally, none of the available plugins or snippets allow a custom message to appear in any format, including embedded graphics, nor do they allow any combination of hidden shopping cart elements on the same WooCommerce website.

At least not until now! <strong>Hide Cart Functions</strong> gives a WooCommerce website complete control over each user's side shop's functionality while allowing users to create multiple rules to apply to various categories or products within the same system. 

Take your shopping page edits one step further; a user can also enter IDs or classes to hide custom elements. This additional provision enables users to customize third-party plugin functions beyond WooCommerce options or adjust those provided by themes with WooCommerce templates. This extra level of customization may yield unexpected results; therefore, use it at your own discretion and thoroughly test it. We cannot resolve any conflicts resulting from the use of this field.

= How to Find a Product ID =

Open your WordPress dashboard and click on Pages > All Pages. Then, select the page for which you need to find the ID. Once the page has opened, you need to look at the URL in your web browser’s address bar. Here, you will find the page ID number displayed in the page URL, immediately after ?post=.

= Plugin Limitation =

As a disclaimer to this plugin's capabilities, it is not possible to create a rule to customize the cart functions for any individual Variable Post ID found within a variable product post. The plugin can only modify the tasks of a Product ID or Category ID due to WooCommerce's inherent limits, not because of the plugin.

= As of version 1.0.4 =

A requested setting has been added to each rule that allows you to apply it to Guest Users only or Logged-In users only, but not both at once, which would cause a conflict. Leave both unchecked to apply to all users.

= As of version 1.0.5 =

Search and select for products with a 3-letter minimum length was added to ease selecting products for which the rule applies. This addition works in conjunction with the Product ID field, allowing you to use one, both, or neither at the same time.

= As of version 1.2.16 =

New Store-Wide Settings section added with two security options to combat carding attacks. Carding is when bots use your WooCommerce checkout to test stolen credit card numbers by posting directly to checkout endpoints, bypassing any hidden buttons. "Cripple Bots" requires a valid cart session before checkout, blocking direct POST attacks while allowing real customers to shop normally. "Disable Purchases" completely blocks all code-activated purchases as a temporary nuclear option during active attacks. Both settings apply globally and are independent of individual product rules.

Also added "Show Login Button" option which displays a login button in place of the hidden Add to Cart button for guest users. Works with "Guests Only" to encourage visitors to log in to see prices and make purchases. Includes customizable button text and configurable return URL (Current Product Page, Shop Page, Home Page, or My Account Page) so customers return to where they were browsing after logging in.

= Translations =

All text strings use WordPress translation functions. Includes complete translations for English, Spanish, French, German, Portuguese, Dutch, Polish, Finnish, and Russian. Any edits to the PO files or additional languages are welcome.

= Donations =

If this free effort assists you, please consider making a small donation from the main plugin page, found on the lower right. All funds assist orphans in destitution. 

== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/hide-cart-functions' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Follow the plugin setting panel in the WordPress settings menu.

== Technical Details for Release 1.2.16 ==

Load time: 0.409 s; Memory usage: 59.62 MiB
PHP up to tested version: 8.4.17
MySQL up to tested version: 8.4.8
MariaDB up to tested version: 12.1.2
cURL up to tested version: 8.18.0, OpenSSL/3.6.1
PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4 compliant. Not tested on 8.5 yet.

== Using in Multisite Installation ==

1. Extract the zip file contents in the wp-content/mu-plugins/ directory of your WordPress installation. (This is not created by default. You must create it in the wp-content folder.) The 'mu' does not stand for multi-user as it did for WPMU, it stands for 'must-use' as any code placed in that folder will run without needing to be activated.
2. Follow the plugin setting panel in the WordPress settings menu.

== Frequently Asked Questions ==

= Is this plugin frequently updated to comply with WordPress? =
Yes, attention is given to a staged installation with many other plugins via debug mode.

= Is the plugin as simple to use as it looks? =
Yes. No other plugin exists that allows the management of the shopping cart so simply.

= Have there ever been any compatibility issues? =
There was a hide button issue in WooCommerce 8.2.0, which was fixed within 24 hours.

= Is the code in the plugin proven stable? =

Please click the following link to check the current stability of this plugin:
<a href="https://plugintests.com/plugins/hide-cart-functions/latest" rel="nofollow ugc">https://plugintests.com/plugins/hide-cart-functions/latest</a>

== Screenshots ==

1. The Hide Cart Functions Rules Table With Bot Options
2. Top Part of Hide Cart Functions User Selected Settings
3. Bottom Part of Hide Cart Functions User Selected Settings
4. Example of One Setting Selection Active while Four are Deactivated

== Upgrade Notice ==

There is none to report as of the release version.

== Changelog ==

1.2.16 02/03/26
- Added: Store-Wide Settings section with Cripple Bots and Disable Purchases options
- Added: Session-based checkout protection to block direct POST attacks
- Added: Show Login Button option to display login button instead of Add to Cart for guests
- Added: Login Button Text field for custom button text
- Added: Login Return URL setting to redirect users after login (Product, Shop, Home, or Account page)
- Fixed: Data deletion now only occurs on uninstall, not deactivation
- Fixed: Dashboard table header and search box alignment
- Changed: Edit/Delete row actions now always visible in rules table
- Changed: Default rule title to "Function Rule"
- Assure compliance with WordPress 6.9.1

1.2.15 01/19/26
- Added: Automatic cache clearing on settings save
- Added: Toast notification replaces standard admin notice
- Added: Support for LiteSpeed, WP Rocket, W3TC, and other caching plugins
- Fixed: Mobile button styling at 782px breakpoint
- Fixed: Search Products field width on mobile
- Fixed: Existing Rule display for new settings

1.2.14 01/12/26
- Fixed: Database option naming conflict with other plugins
- Fixed: TI WishList integration returning incorrect values
- Fixed: Date/time functions for timezone consistency
- Fixed: Function naming convention compliance
- Added: Security nonce verification to AJAX handlers
- Added: Direct file access protection to PHP files
- Removed: Dead code and duplicate logic
- Improved: UI changes with bug fixes
- Improved: Input sanitization and security hardening
- Updated: All translation files
- Assure compliance with WooCommerce 10.4.3

1.2.13 12/30/25
- Fixed: Removed invalid Loco_gettext class check that caused error with Loco Translate
- Added: Updated POT, added Dutch, Polish, and Finnish translation files
- Fixed: SQL injection vulnerabilities with proper $wpdb->prepare()
- Fixed: Added capability checks and nonce verification for admin actions
- Fixed: Review nag notification logic now properly triggers and resets
- Fixed: HTML sanitization using wp_kses_post() for custom messages
- Improved: Admin UI consistent field styling across all field types
- Improved: Tooltips repositioned to the right side of fields
- Improved: Panel styling with 8px border radius, reduced row spacing
- Added: Support button with email notification to the developer
- Added: Donation section in settings panel
- Changed: Page heading renamed to "Hide Functions Settings"

1.2.12 12/13/25
- Fixed: Custom Message field now correctly saves HTML formatting, links, and colors

1.2.11 12/12/25
- Security: Fixed SQL injection vulnerability in product search functionality
- Security: Added capability check for AJAX product search endpoint
- Fixed: Incorrect text domain 'simple-tags' changed to 'hide-cart-functions'
- Fixed: Input sanitization now correctly applied to array data
- Fixed: Translation function logic for WPML compatibility
- Fixed: JavaScript syntax error in window.open() call
- Fixed: JavaScript global variable leaks in product selection
- Removed: Debug code and console.log statements from production files
- Fixed: Review notice logic now correctly waits 30 days after install
- Added: German translation files

1.2.10 12/11/25
- Removed debug code left in production
- Assure compliance with WordPress 6.9.0
- Assure compliance with WooCommerce 10.4.0

1.2.9 05/30/25
- Bug Fix: The nag bar for Hide Cart Functions was not resetting.
- Assure compliance with WordPress 6.8.1
- Assure compliance with WooCommerce 9.8.5

1.2.8 02/13/25
- Bug Fix: Override Price Tag text overrides the prices for all products

1.2.7 02/12/25
- Bug Fix: Hiding all selections from BOTH the guest and the logged-in user
- Assure compliance with WordPress 6.7.2

1.2.6 01/21/25
- Bug Fix: Hiding the price from BOTH the guest and the logged-in user
- Assure compliance with WooCommerce 9.6.1

1.2.5 01/21/25
- Bug Fix: All prices in selected categories disappear

1.2.4 12/22/24
- Bug Fix: Attempt to read property “ID” on null
- Bug Fix: price is not working on my home page
- Assure compliance with WooCommerce 9.5.2

1.2.3 12/22/24
- Fixed the custom message is not showing the HTML code

1.2.2 12/21/24
- Fixed "Add to Cart" button visibility in TI WooCommerce Wishlist plugin

1.2.1 12/17/24
- Bug Fix: Override Price Tag Issue
- Bug Fix: Override Price Tag Translation Issue
- Bug Fix: Custom Message Translation Issue
- Bug Fix: "Hide Price" Not Working on Shop Page with Avada Theme
- Assure compliance with WordPress 6.7.1
- Assure compliance with WooCommerce 9.5.1

1.2.0 11/20/24
- More adjustments for 1.1.7 issues

1.1.9 11/19/24
- Fix further 1.1.7 issues and adjustments

1.1.8 11/12/24
- Fix conflict with TI WooCommerce Wishlist
- Fix the hide price for those who are not registered
- Assure compliance with WordPress 6.7.0
- Assure compliance with WooCommerce 9.4.1

1.1.7 11/05/24
- Fix compatibility with Polylang plugin 3.6.4

1.1.6 10/31/24
- Fix overriding prices for selected category
- Fix language assignments in message panel
- Assure compliance with WordPress 6.6.2
- Assure compliance with WooCommerce 9.3.3

1.1.5 09/01/24
- Minor edits to language files
- Assure compliance with WordPress 6.6.1
- Assure compliance with WooCommerce 9.2.3

1.1.4 03/24/24
- Make Custom Message field compatible with WPML
- Assure compliance with WordPress 6.5
- Assure compliance with WooCommerce 8.7.0

1.1.3 10/12/23
- Fixed hide buy button conflict
- Assure compliance with WooCommerce 8.2.0

1.1.2 10/10/23
- Fixed cart button for Divi Theme
- Add Portuguese translation
- Update English, French, Russian, Spanish languages

1.1.1 09/28/23
- Update errors in language files
- Assure compliance with WordPress 6.3.1
- Assure compliance with WooCommerce 8.1.1

1.0.9 08/11/23
- Added compatibility with WooCommerce HPOS

1.0.8 08/09/23
- Fixed JavaScript error and another issue
- Assure compliance with WordPress 6.3.0
- Assure compliance with WooCommerce 8.0.0

1.0.7 08/07/23
- Fixed fatal error on line 162 in hwcf-admin file
- Assure compliance with WordPress 6.2.2
- Assure compliance with WooCommerce 7.9.0

1.0.6 02/08/23
- Remove conflicting install script
- Assure compliance with WooCommerce 7.3.0

1.0.5 12/24/22
- Add Product Selection Search Field
- Fix several settings page formatting errors
- Fix multiple user rule selection conflict
- Update English, French, Russian, Spanish languages
- Assure compliance with WordPress 6.1.1
- Assure compliance with WooCommerce 7.2.2

1.0.4 11/12/22
- Add choice of logged-in user and guest user option
- Fix missing custom message to work properly with rules
- Fix for Hide Custom Element fields thanks to @rruyter
- Update language files and add Russian translation
- Assure compliance with WordPress 6.1
- Assure compliance with WooCommerce 7.1.0

1.0.3 06/16/22
- Remove dash appearing in place of hidden price

1.0.2 05/23/22
- Fixed Hide Custom Element fields conflict
- Assure compliance with WordPress 6.0.2
- Assure compliance with WooCommerce 6.5.1

1.0.1 05/03/22
- Fixed feedback bar timeout function
- Assure compliance with WordPress 5.9.3
- Assure compliance with WooCommerce 6.4.1

1.0.0 03/24/22
- Initial release

== Privacy & Data ==

This plugin operates entirely on your server with no external services, APIs, or data transmission. All rule settings are stored locally in your WordPress database (wp_options table). No visitor data is collected, tracked, or shared beyond standard WordPress and WooCommerce functionality.

The Cripple Bots security feature uses WooCommerce's built-in session system to validate that customers added items to their cart before checkout. No additional cookies are created. Blocked bot attempts are logged to WooCommerce's standard log system (wc-logs) for security monitoring, including the IP address of the blocked request.

The Show Login Button feature redirects guests to the standard WordPress/WooCommerce login page with a return URL parameter - no tracking is involved.

Complete deletion of all plugin settings is available by enabling "Delete Data on Uninstall" in Store-Wide Settings before uninstalling.