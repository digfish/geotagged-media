<?php

//debug(__FILE__  .'-'. __LINE__. ': ' . $shortcode_attrs['category']);
if (isset($category)) {

    echo "<script type='text/javascript'>var category = '$category' ;</script>";
}

function gtm_category_names_for_geotagged_photos()
{
    global $wpdb;

    $results = $wpdb->get_results("select wp_terms.name, wp_terms.slug from wp_term_taxonomy,wp_term_relationships, wp_posts, wp_terms where wp_term_relationships.object_id = wp_posts.ID and post_type='attachment' and wp_term_taxonomy .term_taxonomy_id = wp_term_relationships.term_taxonomy_id and wp_terms.term_id = wp_term_taxonomy.term_id group by wp_terms.name ");

    $categories = array();

    foreach ($results as $result) {
        $categories[$result->slug] = $result->name;
    }

    //d($categories);

    return $categories;
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


<div id="map" class="gtm-map">
</div>
<div style="display:none" id="popup_wrapper">
    <!-- Popup in which the point details appears when clicking -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>


