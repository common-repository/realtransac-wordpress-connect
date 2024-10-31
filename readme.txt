=== Real Estate Agency Website for WordPress ===
Contributors: realtransac
Donate link: http://realtransac.com/
Tags: real estate, agency, broker, CRM, real estate web site, real estate website, immobilier, agence, transaction, site immobilier, inmobiliaria, agencia, transacción, bienes raíces,  imobiliário, agência, transação, site imobiliário, fastigheter, agentur, transaktion, fastigheter plats, beni immobiliari, agenzia, transazione, sito immobiliare, immobiliare, immobiliari, Immobilien, Agentur, Transaktions, Immobilien, realestate, realtransac
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 2.2

Transform your wordpress into a real estate agency website solution linked to the MLS and the free CRM Realtransac.com.

== Description ==

Transform your wordpress into a last generation's dynamic real estate website. You need an API key to use this plugin. Get yours by registering as **Agency / Broker / Listing Agent** on <a href=\"http://realtransac.com/agency/index/request\" target=\"_blank\" title=\"Realtransac\">Realtransac</a>.
This plugin can also synchronize your property listings with the free CRM powered by <a href=\"http://realtransac.com\" target=\"_blank\">Realtransac.com</a>. Your real estate website can also be synchronized with the international MLS and the international portal realtransac.com !

**Features of Real Estate Agency plugin**

1.  Auto-translation of your listing in several languages
2.  Show Random or Best Properties or Last Properties in home page (static or slideshow).
3.  Real estate search engine Widget in home page and result page
4.  Result list of properties and map localization
5.  Page details of Property with request form
6.  Slide show of photos of Property and video embedded
7.  Quick links -> Sales / Rental Properties in menu or home page
8.  Customer testimonials widget
9.  Select Display format based on your theme -> Vertical and Horizontal Layout
10. Map Widget ( Displays properties in Google Maps )
11. Support for real estate Widget Shortcodes
12. Choose your own Listing / Search Result Page

*Note: This Plugin Requires <a href=\"http://wordpress.org/extend/plugins/qtranslate\" target=\"_blank\">Qtranslate</a> to be Installed inorder for Translation to Work. *

== Installation ==
Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

**Installation:**

1. Use wordpress' built-in installer, or upload and unzip Realtransac-Wordpress-Connect.zip to your /wp-content/plugins/ directory.
2. Go to www.realtransac.com and go the tab Professionals | Agency website creation then subscribe to our FREE CRM linked to your WP website.
3. Once your access is approved, in the CRM go to menu Configuration | Agency website and follow the steps.
By default you can publish only 3 properties to your WP website, if you need to publish all your properties then you will have to pay monthly fees.
4. In your WP admin console, go to Plugins and activate Realtransac Wordpress Connect Widget.
5. In your WP admin console, go to Settings | Realtransac settings and Enter your Api Key which you obtained while registering in realtransac.com.
6. Your API key is found in Agency Settings -> API Key in your CRM Account. (The Detailed steps on obtaining it is available in the Screenshots tab).
7. By default the WSDL Url is not necessary to change.
8. In your WP admin console, go to Appearance | Widgets and drag widget to the desired sidebar and click save.


= Search Result Page =
Use the shortcode [rt_search] on any wordpress page where you want your search results to appear. 
Also note to give the same page id in the Search Plugin Widget Settings.

= Shortcode for Widgets =

* **Realtransac property search**

[RT_Widget widget_name="Realsearch" instance="title=Normal&pageid=41&displayform=1"]
Parameters: title->Widget Title, pageid, displayform ->( Vertical - 1, Horizontal - 2)

* **Realtransac property advance search**

[RT_Widget widget_name="AdvancedRealsearch" instance="title=Advance&pageid=41&displayform=1"]
Parameters : title->Widget Title, pageid, displayform ->( Vertical - 1, Horizontal - 2)

* **Featured properties**

[RT_Widget widget_name="TopsalesList" instance="title=Featured&type=Sales&show=Best&limit=5&displayform=2"]
Parameters: title->Widget Title, type->(Sales-Sales, Rental-Rental), show-> (Best-Best, Random-Random), limit, displayform -> (Vertical - 1, Horizontal - 2)

* **Property List**

[RT_Widget widget_name="PropertyList" instance="title=PropertyList&limit=6&displayoption=Best"]

Parameters :title->Widget Title, limit, displayoption ->( Best - Best, Random - Random, Latest - Latest)

* **Google Map**

[RT_Widget widget_name="MapRealsearch" instance="title=Map"]
Parameters : title->Widget Title

== Frequently Asked Questions ==
1. See the live demo of plugin at http://realtransacpartners.com/
2. Plugin's Supported Languages are English, French and Spanish
3. Note: This Plugin Requires <a href=\"http://wordpress.org/extend/plugins/qtranslate\" target=\"_blank\">Qtranslate</a> to be Installed inorder for Translation to Work. 

== Screenshots ==

1. Connect in your Realtransac CRM www.realtransac.com/login, Go in Configuration/Agency website to set up your website.
2. Pick your options and order your website, Once you get the email confirmation go in Agency/ Edit Agency and copy/paste your Realtransac API KEY in your wordpress plugin configuration for activation.
3. Example for Realtransac API Form settings.
4. Example for Shortcodes to widget
5. Example for property Sales widget options display view, type.
6. Example for search results from realtransc.com.

== Support ==
Online chat Support is available for this Widget. Please visit http://support.realtransac.com/

== Changelog ==

= 2.2 =
* Property Listing now loads in AJAX (Faster Loading)
* No Restriction on No of Properties being displayed from Realtransac CRM
* Inbuilt Translation Files (Customize and add your own translation )
* Performance Tweaks 
* Several Bug Fixes
* Introducing New Color Schemes - BETA (Change color scheme of widget based on your theme color)

= 2.1.2 =
* New Short Codes for Property types like Sale, Rental, Short-Term Lease and Room for Rent
* Special Short Codes to Filter Properties based on Age of Property 
* API now supports more detailed property information
* Two New Widgets Added (Contact us Widget and What's my property Worth)
* Several Bug Fixes

= 2.1.1 =
* Plugin now uses Native Wordpress Jquery 
* Resolved Several Design Issues
* Removed Country Option from Search Widget
* Enhanced the Plugin Settings

= 2.1 =
* Added New Widget called Banner List
* Added Core Design for Widgets (Works with Non RT Themes)
* Several Bug Fixes
* Renamed Widgets for Easy Identification

= 2.0 =
* Added Support For New Languages French and Spanish
* Added a New Widget Mortage Calculator
* Recieve Translated Property listing from Realtransac
* Advanced Enquiry Forms for your Property Listing

= 1.0 =
* Initial Version / First Version Release

== Upgrade Notice ==

*Fixed to Work Without QTranslate Plugin in Default Language.


