<?php


function gtm_dashboard_init()
{


    add_action('admin_enqueue_scripts', 'gtm_admin_scripts', 1000);
    $gtm_options = get_option('gtm_options');

    if (isset($gtm_options['add_dashboard_geotagged_media_option'])) {
        add_action('admin_menu', 'gtm_add_media_menu_item');
    }
    add_action('admin_menu', 'gtm_add_settings_item');
    if (isset($gtm_options['media_metadata_gps_details'])) {
        add_action('attachment_submitbox_misc_actions', 'gtm_submitbox_misc_actions', 15);
    }
    add_action('manage_media_custom_column', 'gtm_add_metadata_custom_column', 10, 2);
    //add_action( 'add_attachment', 'gtm_assign_names_on_media_upload', 20,2 );
    add_action('add_attachment', 'gtm_on_add_attachment');
    if (isset($gtm_options['media_show_edit_exif_form'])) { // implement here the geomap in the "Media Details" view
        add_filter('attachment_fields_to_edit', 'gtm_media_details', 10, 2); // this shows below the edit media details in list view
    }
    add_filter('wp_read_image_metadata', 'gtm_assign_names_on_media_upload', 20, 2);

    add_filter('manage_media_columns', 'gtm_add_metadata_column');
    add_action('admin_notices', 'gtm_admin_notices');
    if (isset($gtm_options['media_library_gtm_filters'])) {
        add_action('restrict_manage_posts', 'gtm_filter_media_has_geotag', 10, 2);
    }
    add_action('pre_get_posts', 'gtm_change_main_query');
    add_filter('the_posts', 'gtm_debug_query', 10, 2);
    add_action('save_post', function ($post_id) {
        $post = get_post($post_id);
	    //debug(__FUNCTION__ . 'post = ', $post);
    });
    add_filter('attachment_fields_to_save', 'gtm_after_media_updated', 10, 2);
//	add_action('save_post','gtm_after_media_updated');
//  add_action('the_post','gtm_filter_post',15);
}


function gtm_geocoord_dms_to_attach_fmt($coord_dms)
{
    $coord_tokens = preg_split("/\s+/", $coord_dms);
    $tlt = array();
    for ($i = 0; $i < 3; $i++) {
        $tok = $coord_tokens[$i];
        $nv = (float)substr($tok, 0, strlen($tok) - 2);
        $tlt[] = (string)"$nv";
    }

    $geo_fmt = array("{$tlt[0]}/1", "{$tlt[1]}/1", ($tlt[2] * 10000.0) . "/10000");
    $coord_ref = $coord_tokens[3];
    return array($geo_fmt, $coord_ref);
}

function gtm_after_media_updated($post, $new_attachment_data)
{
	/*   debug(__FUNCTION__ . "_REQUEST", $_REQUEST);
	   debug(__FUNCTION__ . ' post', $post);
	   debug(__FUNCTION__ . ' attachment', $new_attachment_data);
   */

    if (isset($post['ID'])) {
        $curr_attach_md = wp_get_attachment_metadata($post['ID']);
    } else {
        return $post;
    }

    $image_md = $curr_attach_md['image_meta'];
	// d($image_md);

    list($new_latitude, $new_latitude_ref) = gtm_geocoord_dms_to_attach_fmt($_REQUEST['latitude']);
    list($new_longitude, $new_longitude_ref) = gtm_geocoord_dms_to_attach_fmt($_REQUEST['longitude']);
	if (array_key_exists('camera',$_REQUEST)) {
		$new_camera = $_REQUEST['camera'];
		$image_md['camera'] = $new_camera;
	}
    $image_md['latitude'] = $new_latitude;
    $image_md['latitude_ref'] = $new_latitude_ref;
    $image_md['longitude'] = $new_longitude;
    $image_md['longitude_ref'] = $new_longitude_ref;

//    debug('new values', [$new_camera, $new_latitude, $new_latitude_ref, $new_longitude, $new_longitude_ref]);

    $curr_attach_md['image_meta'] = $image_md;
 //   debug('new camera', $curr_attach_md['image_meta']['camera']);

    $res = wp_update_attachment_metadata($post['ID'], $curr_attach_md);

    $curr_attach_md = wp_get_attachment_metadata($post['ID']);

    return $post;
//
}

function gtm_change_main_query($wp_query)
{
    if ($wp_query->is_main_query()) {
//      d( $wp_query );
        if (isset($_REQUEST['geofilter'])) {
            if ($_REQUEST['geofilter'] == 'geotagged') {
                $wp_query->set('meta_query', array(
                    array(
                        'compare' => 'RLIKE',
                        'value' => 'latitude',
                    )
                ));
            } elseif ($_REQUEST['geofilter'] == 'non geotagged') {
                $wp_query->set('meta_query', array(
                    array(
                        'key' => '_wp_attachment_metadata',
                        'value' => 'latitude',
                        'compare' => 'NOT REGEXP',
                    )
                ));
            }
        }
    }
}


function gtm_has_file_exif($filename)
{

    $tag = @exif_read_data($filename);

    return !empty($tag['GPSLatitude']) && !empty($tag['GPSLongitude']);
}

function gtm_extract_exif($meta, $file)
{

    $exif = @exif_read_data($file);

    if (!empty($exif['GPSLatitude'])) {
        $meta['latitude'] = $exif['GPSLatitude'];
    }
    if (!empty($exif['GPSLatitudeRef'])) {
        $meta['latitude_ref'] = trim($exif['GPSLatitudeRef']);
    }
    if (!empty($exif['GPSLongitude'])) {
        $meta['longitude'] = $exif['GPSLongitude'];
    }
    if (!empty($exif['GPSLongitudeRef'])) {
        $meta['longitude_ref'] = trim($exif['GPSLongitudeRef']);
    }
    if (!empty($exif['ExposureBiasValue'])) {
        $meta['exposure_bias'] = trim($exif['ExposureBiasValue']);
    }
    if (!empty($exif['Flash'])) {
        $meta['flash'] = trim($exif['Flash']);
    }

    return $meta;
}

function gtm_assign_names_on_media_upload($meta, $image_file = '')
{

    require_once "gtm_geocode_lib.php";
    $gtm_options = get_option("gtm_options");

    $md = $meta;

    if (!empty($image_file)) {
        $md = gtm_extract_exif($meta, $image_file);
    }


    if (!isset($md['latitude'])) {
        return $meta;
    }

    // skip renaming using geocode if the setting is not set
    if (!isset($gtm_options['geocode_on_upload'])) {
        return $md;
    }

    if (!empty($md['latitude'] && !empty($md['longitude']))) {
        $lat_dec = gtm_geo_dms2dec($md['latitude'], $md['latitude_ref']);
        $long_dec = gtm_geo_dms2dec($md['longitude'], $md['longitude_ref']);
        $complete_location = gtm_revgeocode(array('lat' => $lat_dec, 'long' => $long_dec));
        $toks = preg_split("/,/", $complete_location);
        $street_name = join(' ', array_slice($toks, 0, 2));
        $md['title'] = $street_name;
        $md['caption'] = $street_name;
    }


    return $md;
}

function gtm_filter_out_posts_with_no_metadata($results, $wp_query)
{
    $geofilter = 'all';
    $filtered_out = array();
    if (isset($_REQUEST['geofilter'])) {
        $geofilter = $_REQUEST['geofilter'];
    }

    /*  if ($geofilter == 'all') {
            return $results;
        } else {


            foreach ($results as $post) {

                $md = wp_get_attachment_metadata($post->ID);
                $does_lat_md_field_exist = isset($md['image_meta']['latitude']) ;

                if ($geofilter == 'geotagged' && $does_lat_md_field_exist) {
                    $filtered_out[] = $post;
                } else if ($geofilter == 'non geotagged' && !$does_lat_md_field_exist) {
                    $filtered_out[] = $post;
                }
            }
            return $filtered_out;
        }*/
}

function gtm_filter_media_has_geotag($post_type, $which)
{
    if ($post_type != 'attachment') {
        return;
    }
    $criteria = array(
        'geotagged' => 'Geotagged',
        'non geotagged' => 'Non geotagged',
        'all' => 'All'
    );
    $links = array();
    foreach ($criteria as $term => $label) {
        $links[$term] = "<A href='?mode=list&geofilter=$term'>$label</A>";
    }
    $html = join("</LI>|<LI>", $links);
    echo "<ul class='gtm-library-filters'><li>$html</li></ul>";
}

function gtm_admin_notices()
{
    $first_run = get_user_meta(get_current_user_id(), 'gtm_first_configure');
    if (empty($first_run)) {
        echo "<DIV id='gtm_activation_notice' class='updated notice notice-success is-dismissible'>" . GTM_PLUGIN_NAME . " was installed! <br> Go to the <A href='" . admin_url('options-general.php?page=gtm-admin-options') . "'>settings page</A> to configure it. 	<!--button type='button' class='notice-dismiss'> <span class='screen-reader-text'>Dismiss this notice.</span></button--></DIV>";
    }
}


function gtm_add_settings_item()
{
    $options_page_title = 'Geotagged Media Settings';
    $menu_item_title = 'Geotagged Media';
    $capability = 'administrator';
    $menu_slug = 'gtm-admin-options';
    $callback = 'gtm_settings_admin_page';

    add_options_page($options_page_title, $menu_item_title, $capability, $menu_slug, $callback);
}

function gtm_settings_admin_page()
{

    $gtm_options = get_option('gtm_options');

    if (!empty($_POST)) {
	    //      d(__FUNCTION__, $_POST);
        if (!empty($_POST['gtm_options'])) {
            $gtm_options = $_POST['gtm_options'];
            update_option('gtm_options', $gtm_options);
        }
    }

    update_user_meta(get_current_user_id(), 'gtm_first_configure', true);
    $gtm_options['key_mapbox'] = 'pk.eyJ1IjoiZGlnZmlzaCIsImEiOiJjanlycmt4b2QwZDcxM2JxeXhkcmQxaThrIn0.XgtlZb4MK9_kbMhwPI0qCw';
    require_once "gtm_settings_page.php";
}

function gtm_verify_debug_functions_exist()
{
    if (!class_exists('PC', false) || !function_exists('d')
        || !function_exists('debug')) {
        require_once "gtm_dummy.php";
    };
}


function gtm_admin_scripts($hook_suffix)
{

//	if ( $hook_suffix == 'media_page_gtm' || $hook_suffix == "upload") {
    $ol_css = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.css';
    $ol_js = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.js';
    wp_register_style('ol_css', $ol_css, array(), '5.3.0');
    wp_register_script('ol_js', $ol_js, array(), '5.3.0');
    wp_enqueue_script('ol_js');
//	}

    $bootstrap_css = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap.css';
    $bootstrap_css_theme = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-theme.css';
    $bootstrap_js = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-3.3.6.min.js';

	$leaflet_js = plugin_dir_url(__FILE__) . "leaflet/leaflet-src.js";
    $leaflet_css = plugin_dir_url(__FILE__) ."leaflet/leaflet.css";

	$gtm_css = plugin_dir_url(__FILE__) . 'gtm.css';
    $gtm_js = plugin_dir_url(__FILE__) . 'gtm.js';
    $gtm_jquery_commons_js = plugin_dir_url(__FILE__) . 'gtm.jquery.commons.js';
    $mustache_js = plugin_dir_url(__FILE__) . 'mustache/mustache-3.0.1.js';

    $gtm_geomap_js = plugin_dir_url(__FILE__) . "gtm_geomap.js";
	$gtm_geomap_leaflet_js = plugin_dir_url(__FILE__) . "gtm_geomap_leaflet.js";

    wp_register_style('bootstrap_css', $bootstrap_css, '3.3.6');
    wp_register_style('bootstrap_css_theme', $bootstrap_css_theme, '3.3.6');
    wp_register_style('leaflet_css',$leaflet_css);
    wp_register_script('bootstrap_js', $bootstrap_js, array('jquery'), '3.3.6');
    wp_register_script('mustache_js', $mustache_js, array(), '3.0.1');
	wp_register_script('leaflet_js',$leaflet_js);
	wp_register_style('gtm_css', $gtm_css);
    wp_register_script('gtm_js', $gtm_js);
    wp_register_script("gtm_geomap_js", $gtm_geomap_js, array('ol_js'));
    wp_register_script("gtm_geomap_leaflet_js", $gtm_geomap_leaflet_js, array('leaflet_js'));

	wp_register_script("gtm_geomap_js", $gtm_geomap_js, array('ol_js'));
	wp_enqueue_style('bootstrap_css');
//  wp_enqueue_style( 'bootstrap_css_theme' );
    wp_enqueue_style('ol_css');
    wp_enqueue_style('leaflet_css');
    wp_enqueue_style('gtm_css');
    wp_enqueue_script('bootstrap_js');
    wp_enqueue_script('mustache_js');
    wp_enqueue_script('leaflet_js');
    wp_enqueue_script('gtm_js');
 //   wp_enqueue_script('gtm_geomap_js');
	wp_enqueue_script('gtm_geomap_leaflet_js');

    wp_add_inline_script('gtm_js', 'initMustacheTemplates()');
    if (empty($_REQUEST['action'])) {
        wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_footer_map_scripts.js'));
    } elseif ($_REQUEST['action'] == 'marknew') {
        wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_marknew_scripts.js'));
    }
    wp_add_inline_script('gtm_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm.jquery.commons.js'));
    wp_add_inline_script('gtm_js', 'initDismissableButtonAction()');

}

function gtm_on_add_attachment($r_args)
{

}


function gtm_add_media_menu_item()
{

    $item_title = 'Geotagged media';
    add_media_page($item_title, $item_title, 'administrator', GTM_TEXT_DOMAIN, 'gtm_dash_callback', true);
    //add_menu_page( WPCM_PLUGIN_NAME, WPCM_PLUGIN_NAME, 'administrator', WPCM_TEXT_DOMAIN, 'wpcm_dash_page', 'dashicons-layout', 2 )
}

function gtm_dash_callback()
{

    $action = @$_REQUEST['action'];
    if (empty($action)) {
        $action = 'render_geotags';
    }
    switch ($action) {
        case 'render_geotags':
            require_once "gtm_tagged_media_map_page.php";
            break;
        case 'marknew':
            require_once "gtm_geomark.php";
            break;
        case 'read_exif':
            $media_id = @$_REQUEST['media_id'];
            gtm_repair_image_meta($media_id);
            $image_metadata = wp_get_attachment_metadata($media_id);
//            debug('read_exif image_metadata',$image_metadata);
            $imgfilepath = gtm_media_image_file($media_id);
	        //           debug('read_exif imagefilepath',[$imgfilepath, file_exists( $imgfilepath)]);;

            $image_metadata['image_meta'] = gtm_extract_exif($image_metadata['image_meta'],$imgfilepath);
	        //         debug('read_exif image_metadata',$image_metadata);
            wp_update_attachment_metadata( $media_id, $image_metadata );
//            debug('read exif image metadata after update',wp_get_attachment_metadata( $media_id));
            header("Location: post.php?post=$media_id&action=edit");

            break;

        case 'store_exif':
            $media_id = @$_REQUEST['media_id'];
            require_once "gtm_store_exif.php";
            gtm_store_exif($media_id);
            break;
        case 'delete_exif':
            $media_id = @$_REQUEST['media_id'];
            require_once "gtm_delete_exif.php";
            gtm_delete_attachment_metadata($media_id);
            echo "<p>Click here to <A href='post.php?post=$media_id&action=edit'>return to the Edit media panel</A></p>";

            break;
        case 'media_new_title':
            $media_id = @$_REQUEST['media_id'];
            $new_title = @$_REQUEST['new_title'];
            $media_metadata = wp_get_attachment_metadata($media_id);
            $image_meta = $media_metadata['image_meta'];
            $image_meta['title'] = $new_title;
            $image_meta['caption'] = $new_title;
            $media_metadata['image_meta'] = $image_meta;
            $upd_medatata_status = wp_update_attachment_metadata($media_id, $media_metadata);
            if ($upd_medatata_status === true) {
                echo "<P>The new title was given to the media successfully!</P>";
            } else {
                echo "<P class='error'>It was not possible to assign the new title to the media!</P>";
            }
            $upd_post_status = wp_update_post(array(
                'ID' => $media_id,
                'post_title' => $new_title,
                'post_excerpt' => $new_title
            ));

            if (is_numeric($upd_post_status) && $upd_post_status == $media_id) {
                echo "<P>The attachment post was updated with success!</P>";
            } else {
                echo "<P class='error'>It was not possible to update the postdata of media!</P>";
            }

            $updated_media_postdata = get_post($media_id);


            $updated_media_metadata = wp_get_attachment_metadata($media_id);
            echo "<A href='post.php?post=$media_id&action=edit'>Click here to go to thw updated media</A>";
            break;
        default:
            echo "<STRONG>No defined action for $action!</STRONG>";
    }
}

function gtm_format_metadata_entry($label, $value, $dashicon = '', $with_link = false, $title = '')
{
    if (is_array($value)) {
        $value = print_r($value, true);
    }
    echo "<div style='display: inline-block'class='misc-pub-section misc-pub-$label'>";
    if (!empty($dashicon)) {
        echo "<span class='dashicons dashicons-$dashicon gtm-media-details-label'></span>";
    }
    echo ucfirst($label)
        . ": <strong class='gtm-strong'>" . (($with_link) ? "<A href='https://google.com/search?q=$value' target='_blank' title='$title'>" : "") . $value . (($with_link) ? "</A>" : "")
        . " </strong>";
    echo "<INPUT type='hidden' class='gtm_editable' name='$label' value='$value'>";
    echo "<span><button class='btn_geotag_edit' id='btn_edit_$label'>Edit</button></span>";
//	echo "<span><input type='button' class='btn_geotag_edit' id='btn_edit_$label' value='Edit'></input></span>";
    echo "</div>";
}

function gtm_submitbox_misc_actions($post)
{
    require_once "gtm_geocode_lib.php";

    $atchmnt_post_data = get_post($post->ID);
	//  d($atchmnt_post_data);
    $image = wp_get_attachment_metadata($post->ID);
	//d($image);
    if (!empty($image['image_meta'])) {
        $md = $image['image_meta'];

        if (!empty($md['camera'])) {
            gtm_format_metadata_entry('camera', $md['camera'], 'camera', true, "Click to search for {$md['camera']} in Google");
        }

        if (isset($md['latitude']) && isset($md['longitude'])) {
            gtm_format_metadata_entry('latitude', gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'], 'admin-site');
            gtm_format_metadata_entry('longitude', gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'], 'admin-site');
            $lat_dec = gtm_geo_dms2dec($md['latitude'], $md['latitude_ref']);
            $long_dec = gtm_geo_dms2dec($md['longitude'], $md['longitude_ref']);
            $revgeocode_compl = null;
            try {
	            $revgeocode_compl = gtm_revgeocode( array( 'lat' => $lat_dec, 'long' => $long_dec ) );
            } catch (Exception $ex) {
	            //               debug('error',$ex);
                return null;
                
            }
            $toks = preg_split("/,/", $revgeocode_compl);
            $street_name = trim($toks[0]);

            echo gtm_gmaps_link($lat_dec, $long_dec);
            //d( $image );

            echo "<li class='misc-pub-section'> <A href='upload.php?page=gtm&action=media_new_title&media_id={$post->ID}&new_title=$street_name'>Change the title of this picture to '$revgeocode_compl'?</A></li>";

            $media_upload_dir = wp_get_upload_dir();
            $absfilepath = $media_upload_dir['basedir'] . "/" . $image['file'];

            //           d($absfilepath);

            $gtm_has_file_exif_out = gtm_has_file_exif($absfilepath);

            //           d($gtm_has_file_exif_out);
            if ($gtm_has_file_exif_out == false) {
                echo "<li class='misc-pub-section'><strong>The image file as no EXIF tags</strong></li>";
            } else {
            	echo "<li class='misc-pub-section'><strong>The file has EXIF geotag.</strong>&nbsp;<A href='upload.php?page=gtm&action=delete_exif&media_id={$post->ID}'>Delete the EXIF tag from the Wordpress DB</A></li>";
            }
        } else {
            echo "<P>There is no geometadata data for this image!</P>";
        }
    }
	echo "<li class='misc-pub-section'><A href='upload.php?page=gtm&action=store_exif&media_id={$post->ID}'>Store  geometadata as EXIF tags in image file</A></li>";
    $url_geomark = "/notmpl/gtm_geomark?post_id={$post->ID}";
//    echo "<li><A href=\"javascript:gtmOverlayModalUrl('$url_geomark')\" target='_blank'>Geotag to a new location</A></li>";
	echo "<li class='misc-pub-section'><A href='#geomark_footer_map' >Geotag to a new location</A></li>";

    //if (gtm_has_file_exif(gtm_media_image_file($post->ID))) {
    echo "<li class='misc-pub-section'><A href='upload.php?page=gtm&action=read_exif&media_id={$post->ID}'>Extract metadata from the EXIF tags of image file to Wordpress DB</A></li>";
   // }



}

function gtm_add_metadata_column($columns)
{
    $gtm_options = get_option('gtm_options');
    unset($columns['comments']);
    if (isset($gtm_options['add_metadata_column'])) {
        $columns['metadata'] = "Metadata";
    }

//  d( $columns );

    return $columns;
}

// FIXME function is not working properly in the media gallery, returns true or false if the media has geotag

function gtm_is_metadata_empty($image_metadata)
{

    $md = @$image_metadata['image_meta'];
    if (empty($md)) {
        return true;
    }

    foreach ($md as $k => $item) {
        if (is_string($item)) {
            if ("0" == $item || strlen($item) == 0) {
                continue;
            } else {
                return false;
            }
        } elseif (is_numeric($item)) {
            if ($item == 0) {
                continue;
            } else {
                return false;
            }
        }
    }

    return true;
}

function gtm_option_names()
{
    return array(
        'geocode_on_upload',
        'add_metadata_column',
        'media_metadata_gps_details',
        'media_show_edit_exif_form',
        'add_dashboard_geotagged_media_option'
    );
}

function gtm_add_metadata_custom_column($column_name, $id)
{

    $post = get_post($id);


    if ($column_name != 'metadata') {
        return;
    }


    if ($post->post_type == 'attachment') {
        $buf = '';
        $all_md = wp_get_attachment_metadata($id);
        if (gtm_is_metadata_empty($all_md)) {
            // FIXME after sucessfull manual geomark, the new geomarked photo is not showing as it in the media gallery
            $url_geomark = "/notmpl/gtm_geomark?post_id=$id";
            echo "<P><A href=\"javascript:gtmOverlayModalUrl('$url_geomark')\" target='_blank'>Click here to geotag this photo</A></P>";
        } else {
            $md = $all_md['image_meta'];
            if (!empty($md['camera'])) {
                $buf .= gtm_format_md('Camera', $md['camera']);
            }
            if (!empty($md['latitude']) && !empty($md['longitude'])) {
                $latitude_dms = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
                $longitude_dms = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
                $buf .= gtm_format_md('Latitude', $latitude_dms);
                $buf .= gtm_format_md('Longitude', $longitude_dms);
                $lat_dec = gtm_geo_dms2dec($md['latitude'], $md['latitude_ref']);
                $long_dec = gtm_geo_dms2dec($md['longitude'], $md['longitude_ref']);

                $buf .= gtm_gmaps_link($lat_dec, $long_dec);
            }
        }


        echo $buf;
    }
}

function gtm_media_details($form_fields, $post)
{

    include_once "gtm_media_details.php";
    return $form_fields;
}

function gtm_attachment_field_to_edit($form_fields, $post)
{
//    d(__FUNCTION__);
    $ff = $form_fields;
    $image = wp_get_attachment_metadata($post->ID);
    if (!empty($image['image_meta'])) {
        $md = $image['image_meta'];

        if (!empty($md['camera'])) {
            $ff['camera'] = gtm_field_for_form('camera', $md['camera'], $post->ID);
        }
        if (!empty($md['latitude']) && !empty($md['longitude'])) {
            $latitude_dms = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
            $longitude_dms = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
            $ff['latitude'] = gtm_field_for_form('latitude', $latitude_dms, $post->ID);
            $ff['longitude'] = gtm_field_for_form('longitude', $longitude_dms, $post->ID);
        }
    }

    return $ff;
}
