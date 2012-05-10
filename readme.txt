=== ActiveCampaign Subscription Form ===
Contributors: activecampaign
Tags: activecampaign, subscribe, email, newsletter, signup, marketing, plugin, widget, sidebar
Requires at least: 2
Tested up to: 3.2.1
Stable tag: trunk

The ActiveCampaign email marketing plugin connects WordPress with your email marketing software and allows you to choose a subscription form to embed (as a widget) anywhere on your site.

== Description ==

The ActiveCampaign plugin connects WordPress with your [email marketing software](http://www.activecampaign.com/) and allows you to choose a subscription form to embed (as a widget) anywhere on your site.  After enabling go to Appearance > Widgets to activate this plugin.

For more information & to download a free trial visit the [ActiveCampaign](http://www.activecampaign.com/) web site.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the entire "activecampaign_subscribe" Zip file to the Plugins section of WordPress.
2. Activate the plugin through the Plugins section in WordPress.
3. Visit Appearance > Widgets and drag the "ActiveCampaign Subscription Form" widget to a sidebar.
4. Fill in your ActiveCampaign connection details, then hit Save.
5. Choose a subscription form to display, then hit Save again.

== Frequently Asked Questions ==

= How do I create ActiveCampaign subscription forms to use in WordPress? =

You need to be using [ActiveCampaign email marketing software](http://www.activecampaign.com/) to use this widget. Create new subscriptions forms in the software by going to the "Integration" section, then they will be available through this widget.

= I get a "Connection failed" message. What does this mean? =

Please make sure that your login information is correct, and that you have at least one Integration form already created in the ActiveCampaign system.

= What does "Fetch form with each page load" mean? =

If you check this box, WordPress will re-request the details of your form each time the page is loaded. For performance reasons, it's best to leave this box unchecked.

== Screenshots ==

1. Entering your ActiveCampaign API information
2. Selecting the subscription form to display
3. Confirmation that your settings have been saved
4. Viewing the subscription form on the public side when using the plugin

== Changelog ==

= 1.0 =
* Initial release.

= 1.1 =
* Verified this works with latest versions of WordPress and ActiveCampaign.
* Updated installation instructions.

= 2.0 =
* Re-configured to work with ActiveCampaign version 5.4.
* Improved some areas.

= 2.1 =
* Changed internal API requests to use only API URL and Key instead of Username and Password.
* Provided option to remove style blocks from embedded form code, and converting <input type="button" into <input type="submit".

== Upgrade Notice ==

= 1.1 =
* Installation instructions updated if you are having trouble installing it.

= 2.0 =
* Version 2.0 will NOT work with ActiveCampaign versions < 5.4.

= 2.1 =
* This version requires the use of API URL and Key instead of Username and Password.