# Geotagged Media 
**Contributors:** digitalfisherman  
**Donate link:** https://digfish.org/  
**Tags:** maps osm openlayers geotag exif dashboard  
**Requires at least:** 5.0  
**Tested up to:** 5.1.1  
**Stable tag:** trunk  
**Requires PHP:** 5.2.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Geotagged Media shows the location of your photos in a map on the Dashboard.


## Description 

This sinple plugin allows to show the location of your photos on map, if they were geotagged (i.e., they contain an EXIF tag with the geocoordinates).
It uses the [OpenLayers JS Library](https://openlayers.org/) to render the points in a [OpenStreetMap](https://www.openstreetmap.org/). Clicking on the points will show a popup with a thumbnail. Clicking in it will open the 'Edit Image' for that particular photo
Besides the map, it adds a 'metadata' column to the Media Listing table with the coordinates and the camera which took the photo. In the 'Edit Media' and 'Attachment Details' views adds fields with the coordinates and camera metadata values.
It allows that your media files (photos) are automatically named using reverse geocoding during the upload to Wordpress.

### Features 
* Six source Maps: OpenStreetMap, Bing,ESRI,TileWMS,ThunderForest,MapBox. Google[^1].


### DISCLAIMER 
[1]: *Use of GoogleMaps without the use of their specific API is a violation of its terms of servie. If you want to use Google, use the HTML Widgets or a plugin like that support it. If you use Google with this plugin, you must register a Google Cloud project and enable the use of its API. The servie is paid. If you don't use this, be warned that you will be blocked by Google. You have been warned.*



## Installation 


1. Upload the plugin files to the `/wp-content/plugins/geotagged-media` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. A new entry in the sidebard menu called 'Geotagged media' will apear. By now, this plugin is not configurable.


## Frequently Asked Questions 


### My photos from my camera are surely geotagged, why they don't appear as such in Wordpress ? 

By default and for privacy reasons, Wordpress strips away EXIF tags containing the location metadata, but this plugin already circumvents it so it captures the tags and stores it as a media object metadata. No need to use a Wordpress plugin (eg [Exifography](https://pt.wordpress.org/plugins/thesography/) ) for that.


### Why Google Maps is not included in the Map Tiles Source 
Because OpenLayers does not support it builtin in version 5. In version 3, there is a [third-party implementation](https://github.com/mapgears/ol3-google-maps) that allows to use it. Furthermore, Google APIs Terms of use stays explicitely that direct access to Google APIs through libraries not developed or endorsed by Google is a violation of its terms of service.





## Screenshots 

### 1. Edit Media gtm_edit_media.jpg
[missing image]

### 2. Edit Photo gtm_edit_photo.jpg
[missing image]

### 3. Media Library list gtm_media_library_list.jpg
[missing image]

### 4. Map with the points where the media are located to.jpg
[missing image]

### 5. Clicking in one of the scores will bring a popup
[missing image]



## Changelog 


### 0.3.0 
* Implemented categories and tags for Media
* Implemented filter by category or tag
* Shortcode now allows to filter geo tagged media on a map to category or tag
* Edit the media metadata in  "edit media details" view
* Media library list mode, filter images with or without geotag
* Store the geotags directly in the image file as EXIF tags



### 0.2.5 
* Besides OSM, more four map sources are available: BingMaps, ESRI, TileWMS, ThunderForest


### 0.2.0 
* Hability to rename photos directly when uploading them using reverse geocoding
* Feature that show OS Map with the geotagged photos using shortcode `[gtm_map]`
* Removed dependency from the plugin Exifography


### 0.1.2 
* Some minor corrections
* Updated README


### 0.1 
* First released version in wordpress.org


### 0.0.1 
* First version



## Upgrade Notice 


### 0.1 
First version. Not applicable.


## TODO 

* Two or more maps in the same HTML stream does not work properly (need to replace HTML id's with classes)
* The providers maps keys should stay on server and not be send to the client
* Geomark manually must be done in a modal overlay instead of a new page (done for media list view)

* Add an overlay icon to each photo in media library in grid mode
* Filter on Media Library by if have metadata fields for camera or geolocation (partially done, not using camera)
* Use [Leaflet Map Visualization Library](https://leafletjs.com/) on mobile devices instead of OpenLayers
* Add MapBOx to the list of source providers
