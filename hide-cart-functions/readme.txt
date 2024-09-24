=== Hide Cart Functions ===

Contributors: Artiosmedia, steveneray, repon.wp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=E7LS2JGFPLTH2
Tags: hide price, hide quantity, hide option, hide add to cart, hide category
Requires at least: 4.6
Tested up to: 6.6.1
Version: 1.1.5
Stable tag: 1.1.5
Requires PHP: 7.4.33
License: GPLv3 or later license and included
URI: http://www.gnu.org/licenses/gpl-3.0.html

Hide the product's price, add to cart button, quantity, and options on any product and order. Inject an optional message.

== Description ==

Several plugins provide a means to edit the shopping page cart functions, to hide the price, "Add to Cart" button, quantity selector, and the product options dropdown, but not all in one plugin. Additionally, none of the available plugins or snippets allow a custom message to appear in any format including embedded graphics, nor do they allow any combination of hidden shopping cart elements on the same WooCommerce website.

At least not until now! <strong>Hide Cart Functions</strong> simply gives a WooCommerce website full control over each user side shop's function, while at the same time allowing a user to create several rules to apply to various categories or products within the same system. 

Take your shopping page edits one step further, a user can also enter IDs or classes to hide custom elements. This extra provision provides a means for users to customize third-party plugin functions that are additional to WooCommerce options or adjust those provided by themes with WooCommerce templates. This additional level of customization may come with unexpected results, therefore use at your own discretion followed by a good deal of testing. We cannot resolve any conflicts resulting in the use of this field.

As of <strong>version 1.0.4</strong>, a requested setting has been added to each rule, that allows you to apply the rule to Guest Users only or Logged-In users only, but doesn't allow both at once which would cause a conflict. Leave both unchecked to apply to all users.

As of <strong>version 1.0.5</strong>, search and select for products with a 3 letter minimum was added to ease the selection of products to the rule applies. This addition works in combination with the Product ID field, meaning you can use one or the other or both at once.

<strong>How to Find a Product ID:</strong> Open your WordPress dashboard and click on Pages > All Pages. Then, select the page that you need to find the ID for. Once the page has opened, you need to look at the URL in your web browser’s address bar. Here, you will find the page ID number displayed within the page URL, immediately behind ?post=.

<strong>Plugin Limitation:</strong> As a disclaimer to this plugin's capabilities, it is not possible to create a rule to customize the cart functions for any individual Variable Post ID found within a variable product post. The plugin can only modify the functions of a Product ID or Category ID due to the limits inherent in WooCommerce itself, not for lack of the plugin.

The plugin supports languages including English, Spanish, Portuguese, French, and Russian.

== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/hide-cart-functions' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Follow plugin setting panel in WordPress settings menu.

== Technical Details for Release 1.1.5 ==

Load time: 0.343 s; Memory usage: 3.54 MiB
PHP up to tested version: 8.3.11
MySQL up to tested version: 8.0.39
MariaDB up to tested version: 11.5.2
cURL up to tested version: 8.9.1, OpenSSL/3.3.1
PHP 7.4, 8.0, 8.1, 8.2, and 8.3 compliant.

== Using in Multisite Installation ==

1. Extract the zip file contents in the wp-content/mu-plugins/ directory of your WordPress installation. (This is not created by default. You must create it in the wp-content folder.) The 'mu' does not stand for multi-user like it did for WPMU, it stands for 'must-use' as any code placed in that folder will run without needing to be activated.
2. Follow plugin setting panel in WordPress settings menu.

== Frequently Asked Questions ==

= Is this plugin frequently updated to Wordpress compliance? =
Yes, attention is given on a staged installation with many other plugins via debug mode.

= Is the plugin as simple to use as it looks? =
Yes. No other plugin exists that allows the management of the shopping cart so simply.

= Has there ever any compatibility issues? =
There was a hide button issue with WooCommerce 8.2.0 which was fixed in under 24 hours.

= Is the code in the plugin proven stable? =

Please click the following link to check the current stability of this plugin:
<a href="https://plugintests.com/plugins/hide-cart-functions/latest" rel="nofollow ugc">https://plugintests.com/plugins/hide-cart-functions/latest</a>

== Screenshots ==

1. The Hide Cart Functions Rules Table
2. Top Part of Hide Cart Functions User Selected Settings
3. Bottom Part of Hide Cart Functions User Selected Settings
4. Example of One Setting Selection Active while Three are Deactivated

== Upgrade Notice ==

None to report as of the release version

== Changelog ==

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