<?php

//debug(__FILE__  .'-'. __LINE__. ': ' . $shortcode_attrs['category']);
if (isset($category)) {

    echo "<script type='text/javascript'>var category = '$category' ;</script>";
}

$categories = gtm_category_names_for_geotagged_photos();
?>
<H1>Geotagged media</H1>

<div id="source-maps-radios">
    <label><input type="radio" name="source_map" value="OSM" checked>OSM</label>
    <label><input type="radio" name="source_map" value="BingMaps">BingMaps</label>
    <label><input type="radio" name="source_map" value="ESRI-XYZ">ESRI-XYZ</label>
    <label><input type="radio" name="source_map" value="TileWMS">TileWMS</label>
    <label><input type="radio" name="source_map" value="ThunderForest">ThunderForest</label>
    <label><input type="radio" name="source_map" value="Google">Google</label>
    <label><input type="radio" name="source_map" value="MapBox">MapBox</label>
</div>
<div id="map-thumbnails">
    <label><input type="checkbox" name="show_all_in_popovers" value="true">Show all photos in popups</label>
</div>
<div id="categories-filter">
    <label>Filter by category</label>
    <select name="categories_filter">
        <option value="">All</option>
        <?php foreach ($categories as $name) : ?>
            <?php ?>
            <option value="<?php echo $name ?>" <?php echo(isset($category) && $category == $name ? " selected" : "") ?> ><?php echo $name ?></option>
        <?php endforeach; ?>
    </select>
</div>
<P id='gtm-media-info'>Please wait while the map with the points for the geocoded media loads...</P>
<P class="gtm-highlight" style="display:none">Click on every square to show a popup with a thumbnail for the photo. Clicking on the thumbnail inside the popup will take you to the 'Edit Photo' view.</P>
<label>Map search
    <input id="map_search" type="text" name="geoname_search" style="width: 400px"
           placeholder="Input place name, street, etc...">
</label>

<div id="map" class="gtm-map">
</div>
<div style="display:none" id="popup_wrapper">
    <!-- Popup in which the point details appears when clicking -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>


<script type="text/javascript">

    jQuery(document).ready(function ($) {

        $('#map_search').autocomplete({
            source: function (request, response) {
                console.log('map search autocomplete!', request, response);
                $.getJSON(
                    ajaxurl + "?action=geocode_search",
                    {geoname: $('#map_search').val()},
                    response
                );
                console.log(response);
            },
            select: function (evt, ui) {

                evt.preventDefault();
                var input = $(evt.target);
                console.log('select', evt, ui);
                console.log('selected', ui.item.name);
                $(input).attr('value', ui.item.name);
                $(input).attr('lat', ui.item.lat);
                $(input).attr('long', ui.item.long);
                console.log('input', input, $(input).attr('lat'));
                moveToNewCenter($('#map').data('map'), input.attr('lat'), input.attr('long'));
                return false;
            },
            response: function (evt, ui) {
                console.log('response', evt, ui);
                $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                    newLink = $('<a>' + item.name + '</a>');
                    newListItem = $('<li>').attr('lat', item.lat);
                    $(newListItem).attr('long', item.long);
                    //$(newListItem).data('item',item);
                    return $(newListItem)
                        .append(newLink)
                        .appendTo(ul);
                };
            }
        });
    })
    ;
</script>
