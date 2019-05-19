<?php

$host_vroot = __DIR__ . '/../../../../vhosts/historiasdagomeira';

$docroot = realpath($host_vroot);


$wp_load_location = $docroot . '/wp-load.php';


if (file_exists($wp_load_location)) {
    require_once $wp_load_location;
} else {
    echo "Could n't load $wp_load_location !";
}

include_once dirname(__DIR__) . '/gtm_plugin_main.php';

$post_id = 3751;

function essay_gtm_is_metadata_empty($post_id)
{
    $post = get_post($post_id);
    $md = wp_get_attachment_metadata($post_id);
    $resp = gtm_is_metadata_empty($md);
    return $resp;
}


function essay_gtm_coord_dec_to_dms($coord)
{
    return gtm_coord_dec_to_dms($coord);
}


function essay_gtm_all_fmts($coord_dec,$type) {
    $coord_dms_r = essay_gtm_coord_dec_to_dms($coord_dec);
    $coord_dms_str = gtm_html_format_dms($coord_dms_r,$type);
    $coord_dms_exif = gtm_exif_format_dms($coord_dms_r);
    var_dump($coord_dms_r);
    var_dump($coord_dms_str);
    var_dump($coord_dms_exif);

}

//$resp = essay_gtm_is_metadata_empty($post_id);
$coord = [-2.329102, 40.736852];
$lat_dec = $coord[1];
essay_gtm_all_fmts($lat_dec,'lat');
$long_dec = $coord[0];
essay_gtm_all_fmts($long_dec,'long');

// expected coordinates in dms
// DMS Latitude
//40° 44' 12.6672'' N
//DMS Longitude
//2° 19' 44.7672'' W
