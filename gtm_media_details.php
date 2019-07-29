<?php

d(array('form_fields' => $form_fields, 'post' => $post));

?>

<div id="map" class="gtm-map" style="">
</div>


<script type="text/javascript">

    //    var ajaxurl = "/wp-admin/admin-ajax.php";

    jQuery(document).ready(function ($) {
        var geoMap = new GtmGeomap('#map');
        geoMap.init();
        geoMap.fetchData(ajaxurl + "?action=getcoord", {'post_id': <?php echo $post->ID ?>});
        console.log(geoMap);
    });

</script>
