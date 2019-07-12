=== Geotagged Media ===
Contributors: digitalfisherman
Donate link: https://digfish.org/
Tags: maps osm openlayers geotag exif dashboard
Requires at least: 5.0
Tested up to: 5.1.1
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Geotagged Media shows the location of your photos in a map on the Dashboard.

== Description ==

This sinple plugin allows to show the location of your photos on map, if they were geotagged (i.e., they contain an EXIF tag with the geocoordinates).
It uses the [OpenLayers JS Library](https://openlayers.org/) to render the points in a [OpenStreetMap](https://www.openstreetmap.org/). Clicking on the points will show a popup with a thumbnail. Clicking in it will open the 'Edit Image' for that particular photo
Besides the map, it adds a 'metadata' column to the Media Listing table with the coordinates and the camera which took the photo. In the 'Edit Media' and 'Attachment Details' views adds fields with the coordinates and camera metadata values.
It allows that your media files (photos) are automatically named using reverse geocoding during the upload to Wordpress.
Three source Maps: OpenStreetMap, Bing,ESRI,TileWMS,ThunderForest.


== Installation ==


1. Upload the plugin files to the `/wp-content/plugins/geotagged-media` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. A new entry in the sidebard menu called 'Geotagged media' will apear. By now, this plugin is not configurable.

== Frequently Asked Questions ==

= My photos from my camera are surely geotagged, why they don't appear as such in Wordpress ? =

By default and for privacy reasons, Wordpress strips away EXIF tags containing the location metadata, but this plugin already circumvents it so it captures the tags and stores it as a media object metadata. No need to use a Wordpress plugin (eg [Exifography](https://pt.wordpress.org/plugins/thesography/) ) for that.

= Why Google Maps is not included in the Map Tiles Source =
Because OpenLayers does not support it builtin in version 5. In version 3, there is a [third-party implementation](https://github.com/mapgears/ol3-google-maps) that allows to use it. Furthermore, Google APIs Terms of use stays explicitely that direct access to Google APIs through libraries not developed or endorsed by Google is a violation of its terms of service.




== Screenshots ==

1. Edit Media gtm_edit_media.jpg
2. Edit Photo gtm_edit_photo.jpg
3. Media Library list gtm_media_library_list.jpg
4. Map with the points where the media are located to.jpg
5. Clicking in one of the scores will bring a popup

== Changelog ==

= 0.2.5 =
* Besides OSM, more four map sources are available: BingMaps, ESRI-XYZ, TileWMS, ThunderForest

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
* Add an overlay icon to media library in grid mode
* Filter on Media Library by if have metadata fields for camera or geolocation
* Clicking on the popup in the geomap it will not bring the attachment details form in the media library in grid mode
* composer must be installed not by default, but ONLY if the user wants to use geocoding
* Use [Leaflet Map Visualization Library](https://leafletjs.com/) on mobile devices instead of OpenLayers
* Plugin view in backoffice: allow to access settings on a link close to the plugin entry
* Geomark manually must be done in a modal overlay instead of a new page

