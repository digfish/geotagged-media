
<H1>Geotagged media</H1>

<div id="source-maps-radios">
    <label><input type="radio" name="source_map" value="OSM" checked>OSM</label>
    <label><input type="radio" name="source_map" value="BingMaps">BingMaps</label>
    <label><input type="radio" name="source_map" value="ESRI-XYZ">ESRI-XYZ</label>
    <label><input type="radio" name="source_map" value="TileWMS">TileWMS</label>
    <label><input type="radio" name="source_map" value="ThunderForest">ThunderForest</label>


</div>
<P id='gtm-media-info'>Please wait while the map with the points for the geocoded media loads...</P>
<P class="gtm-highlight" style="display:none">Click on every square to show a popup with a thumbnail for the photo. Clicking on the thumbnail inside the popup will take you to the 'Edit Photo' view.</P>


<div id="map" class="gtm-map">
</div>
<div style="display:none" id="popup_wrapper">
    <!-- Popup in which the point details appears when clicking -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>


