<?php
$js_root_dir = get_site_url() . "/wp-includes/js/";
echo gtm_output_styles_html(array('wp-jquery-ui-dialog'));
echo gtm_output_scripts_html(array('jquery-ui-dialog'));

?>
<link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) ?>/ol/ol-5.3.0.css" type="text/css"/>


<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) ?>ol/ol-5.3.0.js"></script>
<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) ?>gtm_marknew_scripts.js"></script>

<?php

$post = get_post($_REQUEST['post_id']);
$md = wp_get_attachment_metadata($post->ID);

?>
<script type="text/javascript">
    var post_id = <?=  $_REQUEST['post_id'] ?>;
</script>

<div id="map" class="gtm-map">
</div>
<div style="display:none">
    <!-- Popup -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>
