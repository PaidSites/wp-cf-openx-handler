## CF OpenX

The CF OpenX plugin adds the ability to easily add OpenX content into a WordPress site.  The plugin provides the ability to add zone content via shortcode, template tag and Widget.  All of these functions are easy to use and easy to utilize in multiple areas of a site including inside of posts and pages.

### Usage

The CF OpenX admin page allows for the easy addition of OpenX zones.  All zones must be created in the OpenX Admin before they can be used inside of WordPress.  To do this, follow the instructions in the `OpenX Admin Information` section.

To setup the CF OpenX plugin, click on the CF OpenX link inside of the Settings section of the WordPress admin navigation.
	
- First setup the "Server URL"
	- In the "Server URL" field, enter the URL to the OpenX server delivery script
		- Ex: openx.org/openx/www/delivery
		- NOTE: There is no need to insert the "http://" or "https://" as it will automatically be removed
- Next add the Zone IDs to be used inside of WordPress
	- In the "Zone ID" field enter the Zone ID
		- The Zone ID can be found in the URL of the zone from the OpenX Admin.  If the Zone ID is still unable to be located, copy the URL of the zone in the OpenX Admin and paste it into this field.
		- The Zone ID can also be found in the "Invocation Code" from OpenX.  This code can be pasted into the field and the Zone ID will automatically be found.
- Next add the Zone Description for each zone
	- Simply add a brief description of the zone into the description field next to the Zone ID desired.  This can be any description desired, it is simply a placeholder on the WordPress side to notify users what the zone content will be
- Once the zones are all added, click the "Save OpenX Server Settings" button to save the settings.

### Widgets

CF OpenX Widgets are added very easily.  Simply Navigate to the `Widgets` screen under the `Appearance` section of the Admin navigation. Two different types of widgets have been added for easier handling of the display based on desired settings.  

The `CF OpenX Ad` widget loads the OpenX Zone in the standard OpenX fashion.  This widget loads the JavaScript for the ad on the page, and will display the ad on page load. See the `Postload or Standard Load` section under the `Preload vs. Postload Ads` section for more info on how this works.

The `CF OpenX Preload Ad` widget loads the OpenX Zone before the pages loads.  This widget will grab the OpenX Zone information before the page loads. See the `Preload` section under the `Preload vs. Postload Ads` section for more info on how this works.

If the WP Super Cache plugin has been added, and is in `Legacy Mode` for caching pages, an option will be added to the widget to block it from being cached.  This allows the OpenX Ad code to be loaded each time the page is loaded to allow for OpenX to serve the ad each time, which lets OpenX determine ads based on Cookies and other site data.

### Shortcode

The CF OpenX plugin also adds shortcodes for loading of OpenX Zone data in places where there is no easy way to load Zones.  The Shortcode allows for two different types of loading of the OpenX Zone data, `Preload` and `Postload`.  See the `Preload vs. Postload Ads` section for more info on the benefits of these two options.

To add Zone content via shortcode using the `Preload` functionality (replace [zoneID] with the actual ID of the desired zone):

	[cfox zone="[zoneID]" preload="true"]

To add Zone content via shortcode using the "Postload" functionality (replace [zoneID] with the actual ID of the desired zone):

	[cfox zone="[zoneID]"]

### Template Tag

The CF OpenX plugin also adds template tags for loading of OpenX zone data in desired places inside of themes.  The Template Tag allows for two different types of loading of the OpenX zone data, `Preload` and `Postload`. See the `Preload vs. Postload Ads` section for more info on the benefits of these two options.

To add zone content via template tag using the `Preload` functionality (replace [zoneID] with the actual ID of the desired zone):

	<?php cfox_template([zoneID], '[HTML before the Zone Content]', '[HTML after the Zone Content]', true); ?>

To add zone content via template tag using the `Postload` functionality (replace [zoneID] with the actual ID of the desired zone):

	<?php cfox_template([zoneID], '[HTML before the Zone Content]', '[HTML after the Zone Content]', false); ?>


### Preload vs. Postload Ads

The CF OpenX plugin allows for two different ways of loading ads, Preload and Postload.

#### Preload

The benefit of the Preload Ad is that it will have the Zone data before the page loads.  One of the drawbacks of this functionality is that it will slow down page loads for users with slower connections as it stops the page load to get the data from OpenX, and will only proceed with the page load after it gets the information.

#### Postload or Standard Load

The benefit of the standard loading of the data is that it will not stop the page from loading.  The page will load as normal, and the JavaScript will run after the page load is complete.  The drawback of this is that if a user has a slow connection the ad may not show up right away.







