==== PayPal Pro CC Payment Gateway WooCommerce Addon ====
Contributors: nazrulhassanmca
Tags: woocommerce, paypal, woocommerce addon paypalpro, paypalpro cc for woocommerce,paypal pro for wordpress,paypal credit cards for woocommerce,PayPalPro REST api,wordpress paypalpro woocommerce,paypalpro woocommerce,woocommerce paypalpro wordpress
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=nazrulhassan@ymail.com&item_name=Donation+PaypalProCC+Woocommerce+Addon
Requires at least: 4.0 & WooCommerce 2.2+
Author: nazrulhassanmca
Tested up to: 4.3 & Woocommerce 2.4.6
Stable tag: 1.0.2
License: GPLv2

== Description ==
This plugin acts as an addon for woocommerce to add a payment method for WooCommerce for accepting credit card payments by merchants directly on your checkout page.**PayPal Pro** is only available to holders of a **PayPal Pro merchant account**.This Plugins uses **REST API** to communicate to paypal to handle payments But It does not ship REST API PHP SDK bundled with plugin this plugin directly makes a CURL call to create Access tokens & Charge the cards.

= Features =
1. Very Simple Clean Code plugin to add a PayPalPro method to woocommerce
2. No technical skills needed.
3. Prerequisite visualized on screenshots.
4. Adds Charde Id and Charge time to Order Note.
5. Can be customized easily.
7. Can work with test/sandbox mode.
8. It does not needs SSL.
11. Single checkbox to put it in live/test mode.
12. This plugin **does not store Credit Card Details**.
13. This plugin is designed to work **Only On REST API**
14. Single checkbox to put it in Authorize or Authorize & Capture.
15. This plugin Support to accept card types.
16. The Transaction details array returned from gateway are added to post meta of wordpress 

== Screenshots ==

1. Screenshot 1 - Api Key Location 
2. Screenshot 2 - Admin Settings of Addon
3. Screenshot 3 - Checkout Page Form
4. Screenshot 4 - Service Avalaible Countries
== Installation ==

1. Upload 'paypalpro-woocommerce-addon' folder to the '/wp-content/plugins/' directory
2. Activate 'PayPal Pro Credit Cards WooCommerce Addon' from wp plugin lists in admin area
3. Plugin will appear in settings of woocommerce
4. You can set the addon settings from wocommmerce->settings->Checkout->PayPal Pro Cards Settings 
5. You can check for Testing Card No 4446283280247004,Exp 11 / 2018 , CVV 874

== Frequently Asked Questions ==
1. You need to have woocoommerce plugin installed to make this plugin work
2. You need to follow The Screeenshot 1 to obtain API keys from PayPal
3. This plugin works on test & live api keys. 
4. This plugin readily works on local.
5. This plugin does nor requires SSL.
6. This plugin does not store Card Details anywhere.
7. This plugin requires CURL OpenSSL installed 
8. This Plugin will only work for **PayPal Pro merchant account** In supported Countries
9. For country support please check 
10. This plugin currently supports USD but On line no 249 of the main pugin file you can change the currency code in which you like to accept the payments.
11. This plugin does not support Pre Order or Subscriptions 
12. This plugin does support Refunds in woocommmerce interface
13. Paypal Servers sometimes throw Internal Service Error during testing Multiple times with test credit card No. In that case try changing Card No. This may be due to different location of Buyer(This Point Is for TEST MODE Only)

See Below for REST API Supported Countries

	1. https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/#direct-credit-card-payments



== Changelog ==

2015.06.04 - Version 1.0.1

	1. Added Support for Refunds(In case of auth & captured) Void(for Authorized Transaction) Within WooCommerce Interface.
	2. Added support to accept card types
	3. Added support for authorize or authorize & capture
	4. Added performance improvement and bugfixes
	5. Added Bugfix to store access token to database due to its long validity of 8 Hrs
	6. Fixed Warnings from Debug mode set to true on wordpress.
	
2015.05.06 - Version 1.0.0

	1. First Release

== Upgrade Notice == 
This is first version no known notices yet
