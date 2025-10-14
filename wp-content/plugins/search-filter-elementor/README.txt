=== Search & Filter - Elementor Extension ===
Contributors: CodeAmp
Donate link:
Tags: elementor, posts, woocommerce, products, portfolio, archive
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 1.4.1

Adds Search & Filter integration for Elementor - filter your Loop Grid, Posts, Portfolio, Products and Archives widgets.

== Description ==

Adds Search & Filter integration for Elementor - filter your Loop Grid, Posts, Portfolio, Products and Archives widgets

= Features: =

* Simply choose "Search & Filter" as a data source in your Elementor widgets and get setup instantly.
* Ajax is preconfigured - all you need to do is enable Ajax in your search forms and you're good to go.
* Extra: Adds a "no results found" message to widgets that wouldn't usually support it (posts, products, portfolio)


== Installation ==

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `search-filter-elementor.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard


= Using FTP =

1. Download `search-filter-elementor.zip`
2. Extract the `search-filter-elementor` directory to your computer
3. Upload the `search-filter-elementor` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.4.1 =
* Improvement - add hardening to upgrade process.
* Fix - issue with undefined array key `product_query_post_type`

= 1.4.0 =
* Improvement - rework integrations with widgets for better compatibility and stability with Search & Filter Version 3.
* New - added "no results" message to the loop carousel.
* New - support Elementor's "load on click" and "infinite scroll" in Loop Grids.
* New - support the Loop Grid "Individual Pagination" setting.
* New - support "load more" pagination for portfolio widgets.
* Fix - queries were sometimes not displayed in the Elementor editor.
* Fix - JS errors when using the portfolio widget.
* Fix - issues with live search when used on archives utilising the "current query" option.
* Fix - re-added "no results" message to the products widget.
* Fix - issues with WP 6.7 and load_text_domain.

= 1.3.4 =
* Notice - ensure you are using the Search & Filter Elementor Field widgets for optimum compatibility (shortcodes are less stable).
* Bump minumum Elementor version to 3.20.0.
* Fix - issues with JavaScript initialisation with Search & Filter 3.
* Fix - removed legacy "nothing found message" option from loop grids - Elementor has its own under 'additional options'.

= 1.3.3 =
* New - hook into the new integrations screen options and allow downloading from the dashboard.
* Improvement - Reworked JS integration for more reliable behaviour.
* Change - frontend JS filename changed from `search-filter-elementor.js` to `frontend.js`
* Fix - Support Search & Filter fields in Elementor popups.
* Fix - Undefined PHP errors when accessing query attributes.

= 1.3.2 =
* Update - change hooks to support Search & Filter 3.0.4.

= 1.3.1 =
* Fix - Frontend JS errors.

= 1.3.0 =
* New - add support for Search & Filter Pro v3

= 1.2.5 =
* Fix - CSS issues when navigating from a page with no results.
* Fix - an issue with the results class not being added to archive elementor widgets.
* Fix - new JS errors after performing an ajax search.
* Tested upto Elementor version 3.22

= 1.2.4 =
* Tested upto Elementor version 3.19

= 1.2.3 = 
* Fix - issues with pagination & infinite scroll on the loop grid.
* Fix - PHP warnings with the loop grid.

= 1.2.2 = 
* New - add support for product loop grid.

= 1.2.1 =
* Fix - an issue with our search forms not working in popups since Elementor 3.9.0.
* Fix - issues with deprecrated functions in Elementor.

= 1.2.0 = 
* Fix - Issue with deprecated _register_controls
* Improvement - add message to widget that a search form needs to be selected.
* New - add support for the Loop Grid widget.

= 1.0.10 = 
* Fix - Issue with the "no results" option not working in the Elementor posts widget.

= 1.0.9 =
* Fix - add logo to search form widget
* Fix - a JS issue when elementorFrontend isn't loaded yet
* Fix - remove no results message for Anywhere Elementor widgets (they provide their own)
* New - add support for Anywhere Elementor Posts advanced

= 1.0.8 =
* Fix - an undefined index error being thrown
* Fix - a JS error relating the window not being declared properly when accessing `elementorFrontend`

= 1.0.7 =
* Improvement - register as a S&F extension to prevent conflicts
* Fix - be more specific with the ajax pagination selector to prevent conflicts between instances
* New - hooks to render custom content at the start/end of rendering (add content inside the ajax container)

= 1.0.6 =
* Fix - an issue throwing a fatal error when using Elementor Free version only
* New - add support for Anywhere Elementor Post Blocks

= 1.0.5 =
* Fix - an issue with range sliders being duplicated in Elementor popups
* Fix - remove some unnecessary error_log calls

= 1.0.4 =
* Fix - a JS warning when editing a popup with our search form in
* Fix - the "No Results" message was not working for the Product widget

= 1.0.3 =
* New - Support search forms in popups
* Fix - don't assume Elementor is being used to build archives, check first before configuring settings
* Fix - notice text when minimum PHP version is not met
* Improvement - to workaround Polylang issues, we now show search forms for all languages in Elementor Widgets when Polylang is enabled

= 1.0.2 =
* Fix - a PHP warning when no pagination was set
* Fix - only re-init front end JS of elements we've updated - no more `elementorFrontend.init()`

= 1.0.1 =
* Remove some unused defines
* Added in a missing `wp_reset_postdata()`

= 1.0.0 =
* Initial release


== Upgrade Notice ==

= 1.0.0 =
Initial release
