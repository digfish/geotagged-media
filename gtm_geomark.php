<?php
//d($_REQUEST);
$post = get_post($_REQUEST['post_id']);
$md = wp_get_attachment_metadata($post->ID);
//d($post);
//d($md);
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
