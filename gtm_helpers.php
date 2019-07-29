<?php
/*** HELPERS ***/

function gtm_debug_query($results, $wp_query)
{
    d(__FUNCTION__ . " query:", $wp_query->request);

    return $results;
}

function gtm_gmaps_link($lat_dec, $long_dec)
{
    return "<P class='misc-pub-section'><A href='" . gtm_gmaps_url($lat_dec, $long_dec) . "' target='_blank'>Show it on Google Maps (opens in new window)</A></P>";
}


function gtm_gmaps_url($lat_dec, $long_dec)
{
    return "//www.google.com/maps/search/?api=1&query=$lat_dec,$long_dec";
}

function gtm_format_md($label, $value)
{
    return "<P>$label:<STRONG>$value</STRONG></P>";
}

function gtm_mime_type_image($where, $wp_query)
{
    $where .= " AND post_mime_type LIKE 'image%' ";

    return $where;
}


function gtm_extract_geodata_from_post($posts)
{
    global $i;
    $i = 0;

    return array_map(function ($post) {
        $md = $post->metadata['image_meta'];
        $image_sizes = $post->metadata['sizes'];
        $image_location = preg_split('/\//', $post->metadata['file']);
        $image_location = join('/', array_slice($image_location, 0, 2));
        global $i;
        if ($i == 0) {
            $i = 1;
        }
        if (!empty($image_sizes['thumbnail'])) {
            $thumbnail_filename = $image_location . '/' . $image_sizes['thumbnail']['file'];
            $media_dir = wp_upload_dir();
            $media_dir = $media_dir['basedir'];
            if (!file_exists($media_dir . "/$thumbnail_filename")) {
                $thumbnail_filename = null;
            }
        }

        return array(
            'title' => $post->post_title,
            'latitude' => (($md['latitude_ref'] == 'S') ? "-" : "") . gtm_geo_dms2dec($md['latitude']),
            'longitude' => (($md['longitude_ref'] == 'W') ? "-" : "") . gtm_geo_dms2dec($md['longitude']),
            'thumbnail' => $thumbnail_filename,
            'post_id' => $post->ID
        );
    }, $posts);
}

function gtm_geotag_media($md, $coordinates)
{
    $lat_dec = doubleval($coordinates[1]);
    $long_dec = doubleval($coordinates[0]);
    $id = $md['image_data'];
}

/**
 * convert decimal coordinates into degree-minite-format
 * @param $coord_dec
 * @return array
 */
function gtm_coord_dec_to_dms($coord_dec)
{
    $coord_dec = abs($coord_dec);
    $deg = intval($coord_dec);
    $min = intval(($coord_dec - $deg) * 60);
    $sec = ($coord_dec - $deg - ($min / 60.0)) * (60.0 * 60.0);

    return array($deg, $min, $sec);
}

function gtm_html_format_dms($r_dms, $type)
{
    $deg = $r_dms[0];
    $min = $r_dms[1];
    $sec = round($r_dms[2], 4);
    $dir_char = "";

    return " {$deg}° {$min}' {$sec}\" " . gtm_orientation_char($deg, $type);
}

function gtm_orientation_char($coord, $type)
{
    if ($coord >= 0) {
        switch ($type) {
            case 'lat':
                return 'N';
                break;
            case 'long':
                return 'E';
                break;
        }
    } else {
        switch ($type) {
            case 'lat':
                return 'S';
                break;
            case 'long':
                return 'W';
                break;
        }
    }
}

/**
 * convert coordinates from dms into the format used by the exif data
 * @param $r_dms
 * @return array
 */
function gtm_exif_format_dms($r_dms)
{
    return array(
        $deg = $r_dms[0] . "/1",
        $min = $r_dms[1] . "/1",
        $sec = (round($r_dms[2], 4) * 10000) . "/10000"
    );
}


function gtm_field_for_form($label, $value, $post_id)
{
    return array(
        'value' => $value,
        'label' => ucfirst($label),
        'html' => "<INPUT type='text' class='text' id='attachments-$post_id-$label' readonly='readonly' name='[attachments][$post_id][$label]' value='$value'>"
    );
}


function gtm_geo_frac2dec($str)
{
    @list($n, $d) = explode('/', $str);
    if (!empty($d)) {
        return $n / $d;
    }

    return $str;
}

function gtm_geo_pretty_fracs2dec($fracs)
{
    return gtm_geo_frac2dec($fracs[0]) . '&deg; ' .
        gtm_geo_frac2dec($fracs[1]) . '&prime; ' .
        gtm_geo_frac2dec($fracs[2]) . '&Prime; ';
}

/**
 * @param $fracs
 * @param string $coord_ref the direction of the coordinate (south, west)
 * @return float|int
 */
function gtm_geo_dms2dec($fracs, $coord_ref = '')
{
    list($deg, $frac) = preg_split("/\//", $fracs[0]);
    $deg = intval($deg);
    list($min, $frac) = preg_split("/\//", $fracs[1]);
    $min = intval($min);
    list($sec, $frac) = preg_split("/\//", $fracs[2]);
    $sec = floatval($sec) / floatval($frac);

    $final = $deg + $min * (1.0 / 60.0) + $sec * (1.0 / (60.0 * 60.0));

    if ($coord_ref == 'S' || $coord_ref == 'W') {
        $final = -1.0 * $final;
    }

    return $final;
}

/**
 * generates html SCRIPT tags for a js library, along with all its dependencies
 * @param $scripts_r an array with the registered name of the library (as used by wp_register_script)
 * @return string SCRIPT tags for all the dependencies of the library
 */
function gtm_output_scripts_html($scripts_r)
{

    $script_locations = gtm_registered_scripts_locations($scripts_r);
    $buf = '';
    foreach ($script_locations as $loc) {
        $buf .= "<script src='" . site_url() . $loc . "'></script>\n";
    }

    return $buf;
}

function gtm_registered_scripts_locations($scripts_r)
{
    global $wp_scripts;
    $registered_scripts = $wp_scripts->registered;
    $all_deps = array();
    foreach ($scripts_r as $script) {
        if (isset($registered_scripts[$script])) {
            $this_script = $registered_scripts[$script];
            $all_deps[] = $this_script->handle;
            $all_deps = array_merge($all_deps, $this_script->deps);
        }
    }
    $scripts_locs = array();
    foreach ($all_deps as $scr_key) {
        $script = $registered_scripts[$scr_key];
        if ($script->src) {
            $scripts_locs[] = $script->src;
        }
    }

    return array_unique($scripts_locs);
}

function dump_all_styles()
{
    global $wp_styles;
    $registered_styles = $wp_styles->registered;
    $style_names = array_keys($registered_styles);
    sort($style_names);
    dump($style_names);
}

function gtm_output_styles_html($styles_r)
{

    $style_locations = gtm_registered_styles_locations($styles_r);
    $buf = '';
    foreach ($style_locations as $loc) {
        $buf .= "<link rel=\"stylesheet\" href='" . site_url() . "$loc. '  type='text/css'/>\n";
    }

    return $buf;
}

function gtm_registered_styles_locations($styles_r)
{
    global $wp_styles;
    $registered_styles = $wp_styles->registered;
    $all_deps = array();
    foreach ($styles_r as $style) {
        if (isset($registered_styles[$style])) {
            $this_style = $registered_styles[$style];
            $all_deps[] = $this_style->handle;
            $all_deps = array_merge($all_deps, $this_style->deps);
        }
    }


    $styles_locs = array();
    foreach ($all_deps as $scr_key) {
        $style = $registered_styles[$scr_key];
        $styles_locs[] = $style->src;
    }

    return array_unique($styles_locs);
}

function gtm_composer_phar_exists()
{
    return file_exists(__DIR__ . '/' . 'composer.phar');
}

function gtm_does_vendor_dir_exists()
{
    $plugin_dir = plugin_dir_path(__FILE__);

    return (file_exists("$plugin_dir/vendor") && is_dir("$plugin_dir/vendor"));
}

function gtm_download_composer()
{

    $composer_download_url = 'https://getcomposer.org/composer.phar';

    if (gtm_composer_phar_exists()) {
        return null;
    }

    echo "<P>Downloading $composer_download_url ... </P>";
    $ch = curl_init($composer_download_url);
    $plugin_path = plugin_dir_path(__FILE__);
    $fp = fopen("$plugin_path/composer.phar", 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    echo "<P>Composer Download complete! </P>";

    return true;
}

function gtm_get_geotagged_photos()
{

    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'any',
        'orderby' => 'date',
        'order' => 'DESC',
        'nopaging' => true,
    );


    if (isset($_REQUEST['taxonomy'])) {
        $cat_id = get_cat_ID($_REQUEST['taxonomy']);

        //      debug('cat_id', $cat_id);
        $args['cat'] = $cat_id;
    }
    //   debug("query args", $args);
    $query = new WP_Query($args);
//    debug("after instantiation", $query);

    add_filter('posts_where', 'gtm_mime_type_image', 10, 2);
    add_action('the_post', 'gtm_add_metadata_field');
    $images_only_geodata = null;

    //   debug("after filters", $query);

    $geocoded_images = array();
    if ($query->have_posts()) {
        $posts = $query->get_posts();
        debug(__FUNCTION__ . " query:", $query->request);
        foreach ($posts as $post) {
            $md = wp_get_attachment_metadata($post->ID);
            if ($md && !empty($md['image_meta']['latitude'])) {
                $post->metadata = $md;
                $geocoded_images[] = $post;
            }
        }
        echo "";
        $images_only_geodata = gtm_extract_geodata_from_post($geocoded_images);
    }


    remove_filter('posts_where', 'gtm_mime_type_image');

    return array($images_only_geodata, $query->post_count);
}

function gtm_add_metadata_field(&$postObj)
{
    $postObj->metadata = wp_get_attachment_metadata($postObj->ID);

    return $postObj;
}
