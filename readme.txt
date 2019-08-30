=== Geotagged Media ===
Contributors: digitalfisherman
Donate link: https://digfish.org/
Tags: maps osm openlayers geotag exif dashboard
Requires at least: 5.0
Tested up to: 5.2.2
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Geotagged Media shows the location of your photos in a map on the Dashboard.

== Description ==

This plugin allows to show the location of your photos on map, if they were geotagged (i.e., they contain an EXIF tag with the geocoordinates).
Through a shortcode you can insert zoomable maps in your posts grouping your pictures in categories and tags (it enables taxonomies for media on activation).
It uses the [OpenLayers JS Library](https://openlayers.org/) to render the points in a [OpenStreetMap](https://www.openstreetmap.org/). Clicking on the points will show a popup with a thumbnail. Clicking in it will open the 'Edit Image' for that particular photo
Besides the maps, it adds a 'metadata' column to the Media Listing table with the coordinates and the camera which took the photo. In the 'Edit Media' and 'Attachment Details' views adds fields with the coordinates and camera metadata values.
It allows that your media files (photos) are automatically named using reverse geocoding during the upload to Wordpress.

= Features =
  * Seven source Maps:
   - OpenStreetMap
   - BingMaps
   - ESRI
   - TileWMS
   - ThunderForest
   - MapBox
   - Google[^1]

= DISCLAIMER =
[^1]: *Use of GoogleMaps without the use of their specific API is a violation of its terms of servie. If you want to use Google, use the HTML Widgets or a plugin like that support it. If you use Google with this plugin, you must register a Google Cloud project and enable the use of its API. The servie is paid. If you don't use this, be warned that you may be blocked by Google. You have been warned.*


== Installation ==


1. Upload the plugin files to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. A new entry in the sidebard menu called 'Geotagged media' will apear. By now, this plugin is not configurable.

== Frequently Asked Questions ==

= What is a geotag ? =
The geotag is a small piece of information that stores the geographical coordinates (latitude, longitude) or camera model, which are stored into the image file, normally by the device that has GPS capabilities which created the image (your mobile phone or tablet). It can be accessed and read by any software and be edited with it. This plugin is able to access it and modify it, and delete it. [More Information here](https://en.wikipedia.org/wiki/Exif)

= What is the shortcode ? =
The shortcode is [gtm_map] and its arguments are:
* `category` - can accept the name in which you sort your media into, case you want two maps for each media category, specify one here, if not, a dropdown with all and the further category names will be drawn, allowing to se the media geotag locations individually
* `sources` - if you don't use this argument, it has the same effect if you have written `source=all` , it will show all the source map available in radio buttons, in alternative, you can specify one or more sources separated by commas like in this way: `OSM,BingMaps,MapBox`
The four arguments below accept a `yes` (or `true`) or `no` (`false`) value. if you DON'T specify it, it has the same has if you have written the respective argument with the value of `yes`
* `with_source_maps_selector` - draw the radio buttons allowing to select the desire the map source, one at a time -
* `with_thumbnail_checkbox` - show the checkbox to show the thumbnails close to the geotag mark
* `with_categories_filter`- draw a dropdown allowing to select one category at a time
* `with_tip_info` - show a tip explaining how to deal with the map


= My photos from my camera are surely geotagged, why they don't appear as such in Wordpress ? =

The plugin is ready to read those EXIF tags when you upload new photos. Automatically assigns titles acording to the location found in the tags.
By default and for privacy reasons, Wordpress strips away EXIF tags containing the location metadata, but this plugin already circumvents it so it captures the tags and stores it as a media object metadata.

= Why Google Maps is not included in the Map Tiles Source ? =
Because OpenLayers does not support it builtin in version 5. In version 3, there is a [third-party implementation](https://github.com/mapgears/ol3-google-maps) that allows to use it. Furthermore, Google APIs Terms of use stays explicitely that direct access to Google APIs through libraries not developed or endorsed by Google is a violation of their terms of service.




== Screenshots ==

1. New columns on media library list screenshot-1.jpg
2. New metadata fields on Media details view screenshot-2.jpg
3. Interactive map on dashobard screenshot-3.jpg
4. Interactive map on dashboard showing popup screenshot-4.jpg
5. Interactive Map with thumbnails on frontend screenshot-5.jpg
6. Interactive map on dashboard with thumbnails screenshot-6.jpg

== Changelog ==

= 0.3.2 =
* Search box in the Tagged media page in the dashboard

= 0.3.1 =
* Implemented search box for frontend maps

= 0.3.0 =
* Implemented categories and tags for Media
* Implemented filter by category or tag
* Shortcode now allows to filter geo tagged media on a map to category or tag
* Edit the media metadata in  "edit media details" view
* Media library list mode, filter images with or without geotag
* Store the geotags directly in the image file as EXIF tags
* Using leafletJS for the map in the Edit Media Details view


= 0.2.5 =
* Besides OSM, more four map sources are available: BingMaps, ESRI, TileWMS, ThunderForest

= 0.2.0 =
* Hability to rename photos directly when uploading them using reverse geocoding
* Feature that show OS Map with the geotagged photos using shortcode `[gtm_map]`
* Removed dependency from the plugin Exifography

= 0.1.2 =
* Some minor corrections
* Updated README

= 0.1 =
* First released version in wordpress.org

= 0.0.1 =
* First version


== Upgrade Notice ==

= 0.1 =
First version. Not applicable.

== TODO ==

* Map also in the frontend attachment page for the image, besides the backoffice (not important!)
* Two or more maps in the same HTML stream does not work properly (need to replace HTML id's with classes)
* The geomarking feature on the media library in list view only allow to geomark one item (the page must be refreshed to enable again the modal view for geomarking)
* The providers maps keys should stay on server and not be send to the client
* Add an overlay icon to each photo in media library in grid mode
* Filter on Media Library by if have metadata fields for camera or geolocation (partially done, not using camera)
* Use [Leaflet Map Visualization Library](https://leafletjs.com/) on mobile devices instead of OpenLayers (being used in Edit Media Details page)
