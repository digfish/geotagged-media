<?php

/**** AJAX ACTIONS ****/
add_action('wp_ajax_gtm_geocoded_media', 'ajax_get_geotagged_media');
add_action('wp_ajax_nopriv_gtm_geocoded_media', 'ajax_get_geotagged_media');

function ajax_get_geotagged_media()
{
    header('Content-type: application/json');
    echo json_encode(gtm_get_geotagged_photos());
    wp_die();
}

add_action('wp_ajax_dismiss_activation_notice', 'ajax_get_dismiss_activation_notice');

function ajax_get_dismiss_activation_notice()
{
    header('Content-type: application/json');
    $result = update_user_meta(get_current_user_id(), "gtm_first_configure", TRUE);
    echo json_encode($result);
    wp_die();
}

add_action('wp_ajax_gtm_get_options_values', 'ajax_get_options_values');

function ajax_get_options_values()
{
    header('Content-type: application/json');
    echo json_encode(get_option('gtm_options'));
    wp_die();
}

add_action('wp_ajax_gtm_download_composer', function () {
    $composer_downloaded = gtm_download_composer();

    if ($composer_downloaded === true) {
        echo "Composer downloaded with sucess!";
    } elseif ($composer_downloaded == null) {
        echo "Composer already downloaded!";
    } else {
        echo "Composer failed to download!";
    }
    echo "<P>Now that composer was downloaded, click the following button to get the dependencies: <BUTTON class='button' id='btn_composer_init_vendor'>Click here to download dependencies</BUTTON></P>";
    wp_die();
});

add_action('wp_ajax_gtm_install_deps', function () {
    require_once("gtm_install_deps.php");
    if (!gtm_does_vendor_dir_exists()) {
        $composer_output = gtm_install_deps();
        echo $composer_output;
        echo "Dependencies installed with success!";
    } else {
        echo "Dependencies already installed!";
    }
    wp_die();
});


add_action('wp_ajax_gtm_html_url', function () {
    debug(__FUNCTION__ . " request:", $_REQUEST);
    $url = urldecode($_REQUEST['url']);
    echo file_get_contents(home_url() . "/$url");
    wp_die();
});

function ajax_gtm_get_mapsources_keys()
{
    $gtm_options = get_option('gtm_options');
    header('Content-type: application/json');
    echo json_encode(array(
        'key_bingmaps' => $gtm_options['key_bingmaps'],
        'key_thunderforest' => $gtm_options['key_thunderforest'],
        'key_mapbox' => $gtm_options['key_mapbox']

    ));
    wp_die();
}

add_action('wp_ajax_gtm_get_mapsources_keys', 'ajax_gtm_get_mapsources_keys');
add_action('wp_ajax_nopriv_gtm_get_mapsources_keys', 'ajax_gtm_get_mapsources_keys');

add_action('wp_ajax_gtm_geomark', function () {
    $coordinates = $_REQUEST['coordinates'];
    $post_id = $_REQUEST['post_id'];
    $md = wp_get_attachment_metadata($post_id);
    $original_md = $md;
    $lat_r = gtm_coord_dec_to_dms($coordinates[1]);
    $long_r = gtm_coord_dec_to_dms($coordinates[0]);
    $md['image_meta']['latitude'] = gtm_exif_format_dms($lat_r);
    $md['image_meta']['longitude'] = gtm_exif_format_dms($long_r);
    $md['image_meta']['latitude_ref'] = gtm_orientation_char($coordinates[1], 'lat');
    $md['image_meta']['longitude_ref'] = gtm_orientation_char($coordinates[0], 'long');

    $success = wp_update_attachment_metadata(
        $post_id,
        $md
    );
    // FIXME remove when the geotag is being properly stored!
    header('Content-type: application/json');
    echo json_encode(array('success' => $success));
    wp_die();
});

add_action('wp_ajax_getcoord', function () {
    header('Content-type: application/json');
    $image_md = wp_get_attachment_metadata($_REQUEST['post_id']);
    if (empty($image_md['image_meta'])) {
        echo json_encode('');
    } else {
        $md = $image_md['image_meta'];
        $lat_dec = gtm_geo_dms2dec($md['latitude'], $md['latitude_ref']);
        $long_dec = gtm_geo_dms2dec($md['longitude'], $md['longitude_ref']);
        debug(__METHOD__, array($lat_dec, $long_dec));
        echo json_encode([$long_dec, $lat_dec]);
    }
    wp_die();
});
