=== ActiveCampaign Subscription Form ===
Contributors: activecampaign
Tags: activecampaign, subscribe, email, newsletter, signup, marketing, plugin, widget, sidebar
Requires at least: 2
Tested up to: 3.6
Stable tag: trunk

This plugin connects WordPress with your ActiveCampaign software and allows you to embed a subscription form on your site.

== Description ==

(OFFICIAL) This plugin connects WordPress with your ActiveCampaign software and allows you to embed a subscription form on your site with various options for how the form is displayed and submitted.

After enabling go to Settings > ActiveCampaign, or Appearance > Widgets to activate this plugin. The Settings section is for use with the shortcode (<code>[activecampaign]</code>), and the Widget is primarily for embedding in a sidebar.

For more information and to download a free trial visit the [ActiveCampaign Email Marketing](http://www.activecampaign.com/) web site.

== Installation ==

This section describes how to install the plugin and get it working. Please see [our additional help documentation](http://www.activecampaign.com/help/integrating-subscription-forms-with-wordpress/) for more detailed information.

1. Upload the entire "activecampaign" Zip file to the Plugins section of WordPress, or "Add New" plugin and search for "activecampaign."
2. Visit the Settings > ActiveCampaign section, or activate the plugin through the Plugins section in WordPress.
3. If using the widget, visit Appearance > Widgets and drag the "ActiveCampaign" widget to a sidebar.
4. Fill in your ActiveCampaign connection details, then hit Save.
5. Choose a subscription form to display, any optional sub-settings, then hit Save again.
6. Use `[activecampaign]` shortcode to display your form anywhere that shortcode syntax is supported.

== Frequently Asked Questions ==

= How do I create ActiveCampaign subscription forms to use in WordPress? =

You need to be using [ActiveCampaign email marketing software](http://www.activecampaign.com/) to use this widget. Create new subscription forms in the software by going to the "Integration" section, then they will be available through this widget.

= How does this plugin differ from copying and pasting the subscription form onto my site manually? =

It simply makes it much easier to do without requiring you to know which theme (or core WordPress) files to modify.

= What happens after someone submits the subscription form on my WordPress site? =

The same thing that would happen if they submitted it from another site: it redirects back to the ActiveCampaign confirmation message, or a custom URL if you have that set up for the subscription form in ActiveCampaign (modify your forms under the "Integration" section).

= Can my form require an opt-in email confirmation be sent? =

Yes, you would just make sure that your form settings (in ActiveCampaign) have the Opt-in confirmation setting checked.

= I get a "Connection failed" message. What does this mean? =

Please make sure that your login information is correct, and that you have at least one Integration form already created in the ActiveCampaign system.

== Screenshots ==

1. Entering your ActiveCampaign API connection information
2. Selecting the subscription form to display, and additional settings
3. Confirmation that your settings have been saved
4. Viewing the subscription form on the public side when using the plugin
5. Using the [activecampaign] shortcode in a blog post
6. Viewing the output of the [activecampaign] shortcode

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
* Provided option to remove style blocks from embedded form code, and converting `input type="button"` into `input type="submit"`.

= 3.0 =
* Re-wrote widget backend to use most recent WordPress Widget structure.
* Streamlined code and API usage.
* Ability to reset or refresh your forms.
* Better form width detection.

= 3.5 =
* You can now use a shortcode to display your subscription form.

= 4.0 =
* Added many additional settings to control how your form is displayed and submitted.

= 4.5 =
* Added ActiveCampaign to the Settings menu so you can use the shortcode independent of the widget.

== Upgrade Notice ==

= 1.1 =
* Installation instructions updated if you are having trouble installing it.

= 2.0 =
* Version 2.0 will NOT work with ActiveCampaign versions < 5.4.

= 2.1 =
* This version requires the use of API URL and Key instead of Username and Password.

= 4.0 =
* If you use the Ajax option, you will need jQuery enabled for your WordPress site.