<?php
//d( '$shortcode_attrs', $shortcode_attrs );

//debug(__FILE__  .'-'. __LINE__. ': ' . $shortcode_attrs['category']);
echo "<script type='text/javascript'>";
    if ( isset( $category ) ) {
	echo "var category = '$category';" ;
	}
if (!empty($tags)) {
    echo "var tags = " . json_encode($tags) . ";";
    }
echo "</script>";


function verify_shortcode_attr( $shortcode_attrs, $attr ) {
	return ( ! empty( $shortcode_attrs[ $attr ] ) && ( $shortcode_attrs[ $attr ] == 'true' || $shortcode_attrs[ $attr ] == 'yes' ) );
}

$categories = gtm_category_names_for_geotagged_photos();
?>     <!-- shortcodes are: with_source_maps_selector, with_thumbnails_checkbox,  with_categories_filter, with_tip_info (binary), sources, category -->


<?php if ( verify_shortcode_attr( $shortcode_attrs, 'with_source_maps_selector' ) ) : ?>

    <div id="source-maps-radios">
		<?php foreach ( $using_sources as $source ): ?>
            <label><input type="radio" name="source_map" value="<?php echo $source ?>" checked><?php echo $source ?>
            </label>
		<?php endforeach; ?>
    </div>
<?php endif ?>

<?php if ( verify_shortcode_attr( $shortcode_attrs, 'with_thumbnails_checkbox' ) ) : ?>
    <div id="map-thumbnails">
        <label><input type="checkbox" name="show_all_in_popovers" value="true">Show all photos in popups</label>
    </div>
<?php endif ?>

<?php if ( verify_shortcode_attr( $shortcode_attrs, 'with_categories_filter' ) ) : ?>

    <div id="categories-filter">
        <label>Filter by category</label>
        <select name="categories_filter">
                <option value="all">All</option>
			<?php  foreach ( $categories as $name ) : ?>
                <option value="<?php echo $name ?>" <?php echo( isset( $category ) && $category == $name ? " selected" : "" ) ?> ><?php echo $name ?></option>
			<?php endforeach; ?>
        </select>
    </div>
<?php endif ?>

<?php
if ( !empty($tags)) {
	echo "<input id='tags' type='hidden' name='tags' value='$tags'>";
}
?>

<P id='gtm-media-info'>Please wait while the map with the points for the geocoded media loads...</P>

<?php if ( verify_shortcode_attr( $shortcode_attrs, 'with_tip_info' ) ) : ?>
    <P class="gtm-highlight" style="display:none">Click on every square to show a popup with a thumbnail for the photo.
        Clicking on the thumbnail inside the popup will take you to the 'Edit Photo' view.</P>
<?php endif ?>

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

    jQuery(function () {
        jQuery('#map_search').autocomplete({
            source: function (request, response) {
                console.log('map search autocomplete!', request, response);
                jQuery.getJSON(
                    ajaxurl + "?action=geocode_search",
                    {geoname: jQuery('#map_search').val()},
                    response
                );
                console.log(response);
            },
            select: function (evt, ui) {

                evt.preventDefault();
                var input = jQuery(evt.target);
                console.log('select', evt, ui);
                console.log('selected', ui.item.name);
                jQuery(input).attr('value', ui.item.name);
                jQuery(input).attr('lat', ui.item.lat);
                jQuery(input).attr('long', ui.item.long);
                console.log('input', input, jQuery(input).attr('lat'));
                moveToNewCenter(jQuery('#map').data('map'), input.attr('lat'), input.attr('long'));
                return false;
            },
            response: function (evt, ui) {
                console.log('response', evt, ui);
                jQuery(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                    newLink = jQuery('<a>' + item.name + '</a>');
                    newListItem = jQuery('<li>').attr('lat', item.lat);
                    jQuery(newListItem).attr('long', item.long);
                    //jQuery(newListItem).data('item',item);
                    return jQuery(newListItem)
                        .append(newLink)
                        .appendTo(ul);
                };
            }
        });
    })
    ;
</script>

