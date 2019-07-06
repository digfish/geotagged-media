<?php
/**
 * Plugin Name: Geotagged Media
 * Description: Shpw the location data for every photo in the Media Library.
 * Plugin URI: https://github.com/digfish/geotagged-media
 * Author: digfish
 * Author URI: https://github.com/digfish
 * Version: 0.2
 * License: GPL2
 * Text Domain: gtm
 * Domain Path: digfish/gtm
 */


define('GTM_PLUGIN_NAME', 'Geotagged media');
define('GTM_TEXT_DOMAIN', 'gtm');

add_action('plugins_loaded','gtm_plugin_init');

function gtm_plugin_init() {
}

register_activation_hook(__FILE__, 'gtm_hook_on_plugin_activation');


 function gtm_hook_on_plugin_activation() {
  require_once("gtm_install_deps.php");
    //chdir(__DIR__);
    //debug(getcwd());
    $plugin_dir = __DIR__;
 	if (file_exists("$plugin_dir/vendor") && is_dir("$plugin_dir/vendor")) {
    debug('Current working dir is ' .getcwd());
 		//debug('vendor found! not running composer!');
 		return;
 	}

 	gtm_install_deps();
}
//
//  add_action("deactivated_plugin", function ($arg1, $arg2) {
//  }, 10, 2);

add_action('plugins_loaded', 'gtm_plugin_instantiate', 5);




function gtm_plugin_instantiate()
{
	if (is_admin()) {
		gtm_dashboard_init();
	} else {
		gtm_frontend_init();
	}
	add_action('loop_start','gtm_verify_debug_functions_exist');
}

function gtm_frontend_init() {
	add_action('wp_enqueue_scripts','gtm_frontend_scripts');
	add_shortcode('gtm_map',function($attrs) {
		require_once "gtm_dash_page.php";
	});
    add_filter('body_class',function($body_classes)  {
        $body_classes[] = 'gtm-body';
        return $body_classes;
    });
}


function gtm_frontend_scripts($hook_suffix) {
	$ol_css        = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.css';
	$ol_js         = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.js';
	$bootstrap_css = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap.css';
	$bootstrap_css_theme = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-theme.css';
	$bootstrap_js  = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-3.3.6.min.js';
	$gtm_css       = plugin_dir_url(__FILE__) . 'gtm.css';
	$gtm_js        = plugin_dir_url(__FILE__) . 'gtm.js';
	wp_register_style('ol_css', $ol_css, array(), '5.3.0');
	wp_register_script('ol_js', $ol_js, array(), '5.3.0');
	wp_register_style('bootstrap_css', $bootstrap_css, '3.3.6');
	wp_register_style('bootstrap_css_theme',$bootstrap_css_theme,'3.3.6');

	wp_register_script('bootstrap_js', $bootstrap_js, array( 'jquery' ), '3.3.6');
	wp_register_style('gtm_css', $gtm_css);
	wp_register_script('gtm_js', $gtm_js);

	wp_enqueue_style('bootstrap_css');
//	wp_enqueue_style('bootstrap_css_theme');
	wp_enqueue_style('ol_css');
	wp_enqueue_style('gtm_css');
	wp_enqueue_script('bootstrap_js');
	wp_enqueue_script('gtm_js');
	wp_enqueue_script('ol_js');
	wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_footer_map_scripts.js'));
}

function gtm_dashboard_init()
{
    add_action('admin_menu', 'gtm_add_media_menu_item');
    add_action('attachment_submitbox_misc_actions', 'gtm_submitbox_misc_actions', 15);
    add_action('manage_media_custom_column', 'gtm_add_metadata_custom_column', 10, 2);
    add_action('admin_enqueue_scripts', 'gtm_admin_scripts', 1000);
    add_action('add_attachment','gtm_set_fields_on_media_upload');

    add_filter('attachment_fields_to_edit', 'gtm_attachment_field_to_edit', 10, 2);
    add_filter('wp_read_image_metadata', 'gtm_set_fields_on_media_upload',20,1);
    add_filter('manage_media_columns', 'gtm_add_metadata_column');
 }

function gtm_verify_debug_functions_exist() {
	if ( ! class_exists( 'PC', false ) || ! function_exists( 'd' )
	     || ! function_exists( 'debug' ) ) {
		require_once "gtm_dummy.php";
	};
}


function gtm_admin_scripts($hook_suffix)
{
    //debug($hook_suffix, __FUNCTION__);
    if ($hook_suffix == 'media_page_gtm') {
        $ol_css        = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.css';
        $ol_js         = plugin_dir_url(__FILE__) . 'ol/ol-5.3.0.js';
        $bootstrap_css = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-3.3.6.min.css';
        $bootstrap_js  = plugin_dir_url(__FILE__) . 'bootstrap/bootstrap-3.3.6.min.js';
        $gtm_css       = plugin_dir_url(__FILE__) . 'gtm.css';
        $gtm_js       = plugin_dir_url(__FILE__) . 'gtm.js';
        wp_register_style('ol_css', $ol_css, array(), '5.3.0');
        wp_register_script('ol_js', $ol_js, array(), '5.3.0');
        wp_register_style('bootstrap_css', $bootstrap_css, '3.3.6');
        wp_register_script('bootstrap_js', $bootstrap_js, array( 'jquery' ), '3.3.6');
        wp_register_style('gtm_css', $gtm_css);
        wp_register_script('gtm_js', $gtm_js);
        wp_enqueue_style('bootstrap_css');
        wp_enqueue_style('ol_css');
        wp_enqueue_style('gtm_css');
        wp_enqueue_script('bootstrap_js');
        wp_enqueue_script('gtm_js');
        wp_enqueue_script('ol_js');
        if (empty($_REQUEST['action'])) {
            wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_footer_map_scripts.js'));
        } elseif ($_REQUEST['action'] == 'marknew') {
            wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_marknew_scripts.js'));
        }
    }
}

function gtm_set_fields_on_media_upload( $meta) {
	require_once "gtm_geocode_lib.php";
//	print "\n-> " . __FUNCTION__ ."\n";
//	print_r($meta);

	//$athcmt_post = get_post($atchmt_id);
	$md = $meta;

	if (!empty($md['latitude'] && !empty($md['longitude']))) {

		$lat_dec       =  gtm_geo_dms2dec($md['latitude'],$md['latitude_ref']);
		$long_dec      =  gtm_geo_dms2dec($md['longitude'],$md['longitude_ref']);
/*		$lat_dec  = ( ( $md['latitude_ref'] == 'S' ) ? "-" : "" ) . gtm_geo_dms2dec( $md['latitude'] );
		$long_dec = ( ( $md['longitude_ref'] == 'W' ) ? "-" : "" ) . gtm_geo_dms2dec( $md['longitude'] );*/
	//	print_r([$lat_dec,$long_dec]);
		$complete_location = gtm_revgeocode(array('lat' => $lat_dec, 'long' => $long_dec));
	//	print_r($complete_location);
		$toks = preg_split("/,/",$complete_location);
		$street_name  = join(' ',array_slice($toks,0,2));
		$meta['title'] = $street_name;
		$meta['caption'] = $street_name;
	}
/*	$atchmt_upd_post = array(
		'ID' => $atchmt_id,
		'post_excerpt' => $street_name,
		'post_title' => $street_name
	);*/
	//print "\nmeta out:";
	//print_r($meta);
	return $meta;
}


function gtm_add_media_menu_item()
{

    $item_title = 'Geotagged media';
    add_media_page($item_title, $item_title, 'administrator', GTM_TEXT_DOMAIN, 'gtm_dash_page_callback', true);
    //add_menu_page( WPCM_PLUGIN_NAME, WPCM_PLUGIN_NAME, 'administrator', WPCM_TEXT_DOMAIN, 'wpcm_dash_page', 'dashicons-layout', 2 )
}

function gtm_dash_page_callback() {

    $action = @$_REQUEST['action'];
    if (empty($action)) {
        $action = 'render_geotags';
    }
    switch ($action) {
	    case 'render_geotags':
            require_once "gtm_dash_page.php";
            break;
        case 'marknew':
            require_once "gtm_geomark.php";
            break;
		    case 'media_new_title':
				$media_id = @$_REQUEST['media_id'];
				$new_title = @$_REQUEST['new_title'];
				$media_metadata = wp_get_attachment_metadata($media_id);
				$image_meta = $media_metadata['image_meta'];
				$image_meta['title'] = $new_title;
				$image_meta['caption'] = $new_title;
				$media_metadata['image_meta'] = $image_meta;
				$upd_medatata_status = wp_update_attachment_metadata($media_id,$media_metadata);
			    if ($upd_medatata_status === TRUE) {
				    echo "<P>The new title was given to the media successfully!</P>";
			    } else {
				    echo "<P class='error'>It was not possible to assign the new title to the media!</P>";
			    }
				$upd_post_status = wp_update_post(array(
					'ID' => $media_id,
					'post_title' => $new_title,
					'post_excerpt' => $new_title
				));

			    //d('update post status',$upd_post_status);
			    if (is_numeric($upd_post_status) && $upd_post_status == $media_id) {
			    	echo "<P>The attachment post was updated with success!</P>";
			    } else {
				    echo "<P class='error'>It was not possible to update the postdata of media!</P>";
			    }

			    $updated_media_postdata = get_post($media_id);
				//d('updated postdata:',$updated_media_postdata);


				$updated_media_metadata = wp_get_attachment_metadata($media_id);
				//d('updated metadata:',$updated_media_metadata);
				echo "<A href='post.php?post=$media_id&action=edit'>Click here to go to thw updated media</A>";
		    	break;
        default:
            echo "<STRONG>No defined action for $action!</STRONG>";
    }
}


function gtm_format_metadata_entry($label, $value, $dashicon = '', $with_link = false)
{
    if (is_array($value)) {
        $value = print_r($value, true);
    }
    echo "<div style='display: inline-block'class='misc-pub-section misc-pub-$label'>";
    if (! empty($dashicon)) {
        echo "<span class='dashicons dashicons-$dashicon' style='display: inline'></span>";
    }
    echo ucfirst($label)
         . ": <strong>" . ( ( $with_link ) ? "<A href='https://google.com/search?q=$value' target='_blank'>" : "" ) . $value . ( ( $with_link ) ? "</A>" : "" )
         . " </strong>";
    // if (!empty($dashicon)) {
    //   echo "</span>";
    // }

    echo "</div>";
}

function gtm_submitbox_misc_actions($post)
{
	require_once "gtm_geocode_lib.php";
	$atchmnt_post_data = get_post($post->ID);
	d('attachment_post_data',$atchmnt_post_data);
    $image = wp_get_attachment_metadata($post->ID);
    if (! empty($image['image_meta'])) {
        $md = $image['image_meta'];

        if (! empty($md['camera'])) {
            gtm_format_metadata_entry('camera', $md['camera'], 'camera', true);
        }

        if (isset($md['latitude']) && isset($md['longitude'])) {
            gtm_format_metadata_entry('latitude', gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'], 'admin-site');
            gtm_format_metadata_entry('longitude', gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'], 'admin-site');
	        $lat_dec       =  gtm_geo_dms2dec($md['latitude'],$md['latitude_ref']);
	        $long_dec      =  gtm_geo_dms2dec($md['longitude'],$md['longitude_ref']);
/*            $lat_dec  = ( ( $md['latitude_ref'] == 'S' ) ? "-" : "" ) . gtm_geo_dms2dec($md['latitude']);
            $long_dec = ( ( $md['longitude_ref'] == 'W' ) ? "-" : "" ) . gtm_geo_dms2dec($md['longitude']);*/
            $revgeocode_compl = gtm_revgeocode(array('lat' => $lat_dec,'long'=>$long_dec));
            d('complete',$revgeocode_compl);
            $toks = preg_split("/,/",$revgeocode_compl);
	        $street_name  = trim($toks[0]);
	        d('street_name',$street_name);
            echo gtm_gmaps_link($lat_dec, $long_dec);
            d($image);
            echo "<A href='upload.php?page=gtm&action=media_new_title&media_id={$post->ID}&new_title=$street_name'>Do you want to change the title of this picture to '$revgeocode_compl' ?</A>";
        } else {
	        $url_geomark = "/wp-admin/upload.php?page=gtm&action=marknew&post_id={$post->ID}";
	        echo "<P><A href='$url_geomark' target='_blank'>Click here to geotag this photo</A></P>";

        }
    }
}




function gtm_gmaps_link($lat_dec, $long_dec)
{
    return "<P><A href='//www.google.com/maps/search/?api=1&query=$lat_dec,$long_dec' target='_blank'>Show on Google Maps</A></P>";
}


function gtm_add_metadata_column($columns)
{
    $columns['metadata'] = "Metadata";

    return $columns;
}

function gtm_is_metadata_empty($r)
{

    $md = @$r['image_meta'];
    if (empty($md)) {
        return true;
    }

    foreach ($md as $k => $item) {
        if (is_string($item)) {
            if (0 == $item || strlen($item) == 0) {
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

function gtm_add_metadata_custom_column($column_name, $id)
{
    $post = get_post($id);
//    PC::debug([$column_name,$id],__FUNCTION__);
    if ($column_name != 'metadata') {
 //           remove_filter('manage_media_custom_column',__FUNCTION__);
        return ;}
//  PC::debug($post,__FUNCTION__);

    if ($post->post_type == 'attachment') {
        $buf    = '';
        $all_md = wp_get_attachment_metadata($id);
        if (gtm_is_metadata_empty($all_md)) {
            $url_geomark = "/wp-admin/upload.php?page=gtm&action=marknew&post_id=$id";
            echo "<P><A href='$url_geomark' target='_blank'>Click here to geotag this photo</A></P>";
            d($all_md);
        } else {
            $md = $all_md['image_meta'];
            if (! empty($md['camera'])) {
                $buf .= gtm_format_md('Camera', $md['camera']);
            }
            if (! empty($md['latitude']) && ! empty($md['longitude'])) {
                $latitude_dms  = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
                $longitude_dms = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
                $buf           .= gtm_format_md('Latitude', $latitude_dms);
                $buf           .= gtm_format_md('Longitude', $longitude_dms);
                $lat_dec       =  gtm_geo_dms2dec($md['latitude'],$md['latitude_ref']);
                $long_dec      =  gtm_geo_dms2dec($md['longitude'],$md['longitude_ref']);

//                $lat_dec       = ( ( $md['latitude_ref'] == 'S' ) ? "-" : "" ) . gtm_geo_dms2dec($md['latitude']);
//                $long_dec      = ( ( $md['longitude_ref'] == 'W' ) ? "-" : "" ) . gtm_geo_dms2dec($md['longitude']);
                $buf           .= gtm_gmaps_link($lat_dec, $long_dec);
            }
        }

        //d($column_name,$id);
        //d($md);
        echo $buf;

        //echo $md['file'];
    } //else echo d($post);
}

function gtm_format_md($label, $value)
{
    return "<P>$label:<STRONG>$value</STRONG></P>";
}


function gtm_attachment_field_to_edit($form_fields, $post)
{
    $ff    = $form_fields;
    $image = wp_get_attachment_metadata($post->ID);
    if (! empty($image['image_meta'])) {
        //PC::debug($image,'image_meta');
        $md = $image['image_meta'];

        if (! empty($md['camera'])) {
//          PC::debug($md['camera']);
            $ff['camera'] = gtm_field_for_form('camera', $md['camera'], $post->ID);
        }
        if (! empty($md['latitude']) && ! empty($md['longitude'])) {
            $latitude_dms    = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
            $longitude_dms   = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
            $ff['latitude']  = gtm_field_for_form('latitude', $latitude_dms, $post->ID);
            $ff['longitude'] = gtm_field_for_form('longitude', $longitude_dms, $post->ID);
        }
    }

    return $ff;
}

function gtm_field_for_form($label, $value, $post_id)
{
    return array(
        'value' => $value,
        'label' => ucfirst($label),
        'html'  => "<INPUT type='text' class='text' id='attachments-$post_id-$label' readonly='readonly' name='[attachments][$post_id][$label]' value='$value'>"
    );
}


function gtm_geo_frac2dec($str)
{
    @list( $n, $d ) = explode('/', $str);
    if (! empty($d)) {
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

function gtm_geo_dms2dec($fracs,$coord_ref='')
{
    list( $deg, $frac ) = preg_split("/\//", $fracs[0]);
    $deg = intval($deg);
    list( $min, $frac ) = preg_split("/\//", $fracs[1]);
    $min = intval($min);
    list( $sec, $frac ) = preg_split("/\//", $fracs[2]);
    //d($sec, $frac);
    $sec = floatval($sec) / floatval($frac);

	$final = $deg + $min * ( 1.0 / 60.0 ) + $sec * ( 1.0 / ( 60.0 * 60.0 ) );

	if ($coord_ref == 'S' || $coord_ref == 'W') {
		$final = -1.0 * $final;
	}
    //d($deg, $min, $sec);
    return  $final;
}

function gtm_get_geotagged_photos()
{
    global $wpdb;
    $args = array(
        'post_type'   => 'attachment',
        'post_status' => 'any',
        'orderby'     => 'date',
        'order'       => 'DESC',
        'nopaging'    => true,
    );
    add_filter('posts_where', 'gtm_mime_type_image', 10, 2);
    add_action('the_post', 'gtm_add_metadata_field');
    $images_only_geodata = null;
    $query               = new WP_Query($args);
//d( $query->post_count );
    $geocoded_images = array();
//d( $query->request );
    if ($query->have_posts()) {
        $posts = $query->get_posts();
        foreach ($posts as $post) {
            $md = wp_get_attachment_metadata($post->ID);
            if ($md && ! empty($md['image_meta']['latitude'])) {
                $post->metadata    = $md;
                $geocoded_images[] = $post;
            }
        }
        echo "";
//  d( $geocoded_images );
        $images_only_geodata = gtm_extract_geodata_from_post($geocoded_images);
//  d( $images_only_geodata );
    }

    remove_filter('posts_where', 'gtm_mime_type_image');

    return array( $images_only_geodata, $query->post_count );
}

function gtm_add_metadata_field(&$postObj)
{
    $postObj->metadata = wp_get_attachment_metadata($postObj->ID);

    return $postObj;
}

function gtm_mime_type_image($where, $wp_query)
{
    $where .= " AND post_mime_type LIKE 'image%' ";

    return $where;
}


function gtm_extract_geodata_from_post($posts)
{
    return array_map(function ($post) {
        $md             = $post->metadata['image_meta'];
        $image_sizes    = $post->metadata['sizes'];
        $image_location = preg_split('/\//', $post->metadata['file']);
        $image_location = join('/', array_slice($image_location, 0, 2));
        if (! empty($image_sizes['thumbnail'])) {
            $thumbnail_filename = $image_location . '/' . $image_sizes['thumbnail']['file'];
            $media_dir = wp_upload_dir();
            $media_dir = $media_dir['basedir'];
            if (!file_exists($media_dir . "/$thumbnail_filename")) {
            	$thumbnail_filename = NULL;
            }
        }

        return array(
            'title'     => $post->post_title,
            'latitude'  => ( ( $md['latitude_ref'] == 'S' ) ? "-" : "" ) . gtm_geo_dms2dec($md['latitude']),
            'longitude' => ( ( $md['longitude_ref'] == 'W' ) ? "-" : "" ) . gtm_geo_dms2dec($md['longitude']),
            'thumbnail' => $thumbnail_filename,
            'post_id'   => $post->ID
        );
    }, $posts);
}

function gtm_geotag_media($md, $coordinates)
{
    $lat_dec = doubleval($coordinates[1]);
    $long_dec = doubleval($coordinates[0]);
    $id = $md['image_data'];
}

function gtm_coord_dec_to_dms($coord_dec)
{
    $coord_dec = abs($coord_dec);
    $deg = intval($coord_dec);
    $min = intval(($coord_dec - $deg) * 60);
    $sec = ($coord_dec - $deg - ($min / 60.0 )) * ( 60.0 * 60.0);
    return array($deg,$min,$sec);
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

function gtm_exif_format_dms($r_dms)
{
    return array(
        $deg = $r_dms[0] . "/1",
        $min = $r_dms[1] . "/1",
        $sec = (round($r_dms[2], 4) * 10000 ) . "/10000"
    );
}

/**** AJAX ACTIONS ****/


add_action('wp_ajax_gtm_geocoded_media', 'ajax_get_gecoded_media');
add_action('wp_ajax_nopriv_gtm_geocoded_media','ajax_get_gecoded_media');

 function ajax_get_gecoded_media () {
    header('Content-type: application/json');
    echo json_encode(gtm_get_geotagged_photos());
    wp_die();
}

add_action('wp_ajax_gtm_geomark', function () {
    $coordinates = $_REQUEST['coordinates'];
    $post_id = $_REQUEST['post_id'];
    $md = wp_get_attachment_metadata($post_id);
    $original_md = $md;
    //$md = gtm_geotag_media($md,$coordinates);
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
    //wp_update_attachment_metadata($post_id, $original_md);
    header('Content-type: application/json');
    echo json_encode(array('success' => $success));
    wp_die();
});
