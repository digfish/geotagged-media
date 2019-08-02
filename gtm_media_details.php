<?php

d(array('form_fields' => $form_fields, 'post' => $post));

?>

<h1>Map</h1>
<p>Click and drag the point to new position. Click again to confirm it.</p>
<div id="map" class="gtm-map" style="width: 100%; height: 450px" >
</div>
<div id='popup-container' style="display:none">
    <!-- Popup -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>

<script type="text/javascript">

	var gtm_post_id = <?php echo $post->ID?>;

    //    var ajaxurl = "/wp-admin/admin-ajax.php";

    jQuery(document).ready(function ($) {
        var geoMap = new GtmGeomap('#map','leaflet');
        geoMap.init();
        geoMap.fetchData(ajaxurl + "?action=getcoord", {'post_id': <?php echo $post->ID ?>});
        console.log('gtm_media_details geoMap',geoMap);
    });

</script>
