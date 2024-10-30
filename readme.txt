=== Moosend Website Connector ===
Contributors: moosend
Tags: ecommerce, cart abandonment, email marketing, multi-channel marketing, marketing, product recommendations, recommendations, customer analytics, product analytics, conversion optimization, improve sales
Requires at least: 4.1
Tested up to: 6.6
Stable tag: 1.0.191
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Improve your conversion rates with cart abandonment and product recommendations emails with a click of a button. Track website behaviour of all visitors, ghost users and identified / logged in users and perform segmentations or setup automated workflows to re-engage with your user base.

[vimeo https://vimeo.com/212905817]

== Description ==

## Getting started
1. [Register with Moosend](https://www.moosend.com)
2. Install the plugin in your store

## Identify Visitors
Automatically relate your users to their actions and record traits. The plugin is able to identify a user - and to map down all of their actions - as soon as they register or log in to your website.

The Moosend Website Tracking for Wordpress plugin automatically tracks actions even if the visitor is logged out or has never logged in and builds the user profile accordingly.

## Track Website Events
By installing the Moosend Website Tracking for Wordpress plugin you are automatically tracking:

1. **Page Views**: Understand which pages are popular and how they relate to your marketing & sales funnels.
2. **Add to cart events**: Never miss an abandoned visitor again. Track add to cart events and use the Moosend marketing automation feature to create targeted workflows based upon your visitorsï¿½ behaviour.
3. **Successful Purchases**: Track every conversion and realized purchases. See which visitors have entered your post-purchase Thank You page, and assign tags to them so that you can send them personalized Thank You campaigns.

You also have the option using a tiny bit of javascript to track custom events. The plugin allows you to track any custom event you may need to create. Now you will be able to see how successful your latest promo has been, and how many conversions your landing page has created.

## Add Customers Automatically to Moosend
Every time one of your visitors purchases from your WooCommerce store, we automatically sync their name, email address, and details in a new list in your Moosend account. Therefore, you don't have to worry about lengthy imports and following a specific sync routine.

## Link Actions to Campaigns

Page Visits, Add to Cart events, and Successful Purchases are automatically linked to your email campaigns. This way you are able to better understand which campaigns generate engagement and you will get a full picture of your campaign's ROI.

## Cart Abandonment
Moosend's Website Tracking for Wordpress plugin automatically tracks your visitors' actions on your website and gives you the ability to:
1. Segment visitors that have abandoned their carts so you can re-target them to capture additional revenue.
2. Create automated campaigns using Moosend's powerful automations to automatically send re-targeting emails to everyone that has abandoned their cart.
3. Analyse your Purchase Funnel to identify ways to optimize your conversions and reduce your cart abandonment.

## Product Recommendations
All of your visitor's actions are automatically analysed to allow you to send personalized product recommendations based on their specific behavior. Sending your most popular products, or those with better revenue, as part of your campaigns and automations is a breeze. While these automations are very powerful, they are incredibly easy to set up!

== Installation ==


1. In order to install the plugin manually copy the contents of the downloaded directory to wordpress_installation_directory/wp-content/plugins/mootracker/
2. After you have installed the plugin navigate to Settings - MooTracker Settings and from that screen insert the site id associated with this shop.

== Changelog ==

= 1.0.190 =
* Update dependencies

= 1.0.188 =
* Fix exit intent event bug. Add compatibility for WP 6.0

= 1.0.187 =
* Fire order completed event only after the full purchase is made, such as PayPal case etc..

= 1.0.186 =
* Update GuzzleHttp
* Added compatibility with WP 5.8

= 1.0.179 =
* Added compatibility with WP 5.6

= 1.0.178 =
* Now the product ID is sent instead of variation ID in variable products

= 1.0.177 =
* Fixed a bug when ItemCategory was not being sent from a Variable Product

= 1.0.175 #
* Fixed some issues in Add to Cart and Complete Order

= 1.0.173 #
* Add Subscription Forms feature
* Remove external JS file. Now adds script snippet inline in HTML head

= 1.0.169 #
* Product category is tracked when Order is completed.

= 1.0.163 #
* Full name is tracked when Order is completed. Tested up to 5.2.3

= 0.9.160 #
Prevent multiple calls when "Enable AJAX add to cart buttons on archives" is disabled

= 0.9.153 #
* Added itemCategory in addToOrder event

= 0.9.152 =
* Updated Moosend Website Tracking Package
* Added Product Attributes to Page View

= 0.3.2 =
* Updated Moosend Website Tracking Package

= 0.3.1 =
* Plugin is compatible up to 4.8
