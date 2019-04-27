<?php
/**
 * Plugin Name: Geotagged Media
 * Description: Shpw the location data for every photo in the Media Library.
 * Plugin URI: http://digfish.org/gtm
 * Author: digfish
 * Author URI: http://digfish.org
 * Version: 0.01
 * License: GPL2
 * Text Domain: gtm
 * Domain Path: digfish/gtm
 */

define('GTM_PLUGIN_NAME', 'Geotagged media');
define('GTM_TEXT_DOMAIN','gtm');

 // add_action("activated_plugin", function ($arg1, $arg2) {
 // });
 //
 //  add_action("deactivated_plugin", function ($arg1, $arg2) {
 //  }, 10, 2);

add_action('plugins_loaded', 'gtm_plugin_instantiate', 5);

function gtm_plugin_instantiate()
{
	dashboard_init();
}


function dashboard_init() {
	add_action('admin_menu','gtm_add_media_menu_item');
	add_action('attachment_submitbox_misc_actions', 'gtm_submitbox_misc_actions', 15);
	add_filter('manage_media_columns','gtm_add_metadata_column');
	add_action('manage_media_custom_column','gtm_add_metadata_custom_column',10,2);
	add_filter('attachment_fields_to_edit', 'gtm_attachment_field_to_edit', 10, 2);

}

function gtm_add_media_menu_item() {
	$item_title = 'Geotagged media';
	add_media_page($item_title,$item_title,'administrator',GTM_TEXT_DOMAIN,'gtm_dash_page_callback',TRUE);
		//add_menu_page( WPCM_PLUGIN_NAME, WPCM_PLUGIN_NAME, 'administrator', WPCM_TEXT_DOMAIN, 'wpcm_dash_page', 'dashicons-layout', 2 )
}

function gtm_dash_page_callback() {
	require_once "gtm_dash_page.php";

}


function gtm_format_metadata_entry($label, $value, $dashicon='', $with_link=false)
{
    if (is_array($value)) {
        $value = print_r($value, true);
    }
    echo "<div style='display: inline-block'class='misc-pub-section misc-pub-$label'>";
    if (!empty($dashicon)) {
        echo "<span class='dashicons dashicons-$dashicon' style='display: inline'></span>";
    }
    echo ucfirst($label)
  . ": <strong>". (($with_link)?"<A href='https://google.com/search?q=$value' target='_blank'>":"") . $value .(($with_link)?"</A>":"")
  ." </strong>";
    // if (!empty($dashicon)) {
    //   echo "</span>";
    // }

    echo "</div>";
}

function gtm_submitbox_misc_actions($post)
{
    $image = wp_get_attachment_metadata($post->ID);
    if (!empty($image['image_meta'])) {
        $md = $image['image_meta'];

        if (!empty($md['camera'])) {
            gtm_format_metadata_entry('camera', $md['camera'], 'camera', true);
        }

        if (isset($md['latitude']) && isset($md['longitude'])) {
            gtm_format_metadata_entry('latitude', gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'], 'admin-site') ;
            gtm_format_metadata_entry('longitude', gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'], 'admin-site');
            $lat_dec = (($md['latitude_ref'] == 'S')? "-":"") . gtm_geo_dms2dec($md['latitude']);
            $long_dec = (($md['longitude_ref'] == 'W')? "-":"") . gtm_geo_dms2dec($md['longitude']);
            echo gtm_gmaps_link($lat_dec,$long_dec);
        }
    }
    //d($image);
}

function gtm_gmaps_link($lat_dec,$long_dec) {
  return "<A href='//www.google.com/maps/search/?api=1&query=$lat_dec,$long_dec' target='_blank'>Show on Google Maps</A>";
}


function gtm_add_metadata_column( $columns ) {
  $columns['metadata'] = "Metadata";
  return $columns;
}

function gtm_add_metadata_custom_column($column_name,$id) {
  $post = get_post($id);
  //PC::debug([$column_name,$id],__FUNCTION__);
//  PC::debug($post,__FUNCTION__);

  if ($post->post_type == 'attachment') {
    $buf = '';
    $all_md = wp_get_attachment_metadata($id);
    if (empty($all_md['image_meta'])) {
      //d($all_md);
    } else {
    $md = $all_md['image_meta'];
    if (!empty($md['camera'])) {
      $buf .= gtm_format_md('Camera',$md['camera']);
    }
    if (!empty($md['latitude']) && !empty($md['longitude'])) {
      $latitude_dms = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
      $longitude_dms = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
      $buf .= gtm_format_md('Latitude',$latitude_dms);
      $buf .= gtm_format_md('Longitude',$longitude_dms);
      $lat_dec = (($md['latitude_ref'] == 'S')? "-":"") . gtm_geo_dms2dec($md['latitude']);
      $long_dec = (($md['longitude_ref'] == 'W')? "-":"") . gtm_geo_dms2dec($md['longitude']);
      $buf  .= gtm_gmaps_link($lat_dec,$long_dec);
    }
  }

    //d($column_name,$id);
    //d($md);
    echo $buf;
    //echo $md['file'];
  } //else echo d($post);
}

function gtm_format_md($label,$value) {
  return "<P>$label:<STRONG>$value</STRONG></P>";
}


function gtm_attachment_field_to_edit($form_fields, $post)
{
    $ff = $form_fields;
    $image = wp_get_attachment_metadata($post->ID);
    if (!empty($image['image_meta'])) {
      //PC::debug($image,'image_meta');
        $md = $image['image_meta'];

        if (!empty($md['camera'])) {
//          PC::debug($md['camera']);
            $ff['camera'] =  gtm_field_for_form('camera',$md['camera'],$post->ID);
        }
        if (!empty($md['latitude']) && !empty($md['longitude'])) {
          $latitude_dms = gtm_geo_pretty_fracs2dec($md['latitude']) . $md['latitude_ref'];
          $longitude_dms = gtm_geo_pretty_fracs2dec($md['longitude']) . $md['longitude_ref'];
          $ff['latitude'] = gtm_field_for_form('latitude',$latitude_dms,$post->ID);
          $ff['longitude'] = gtm_field_for_form('longitude',$longitude_dms,$post->ID);
        }


    }
    return $ff;
}

function gtm_field_for_form($label,$value,$post_id) {
  return array(
  	'value'=>$value,
    'label' => ucfirst($label),
    'html' => "<INPUT type='text' class='text' id='attachments-$post_id-$label' readonly='readonly' name='[attachments][$post_id][$label]' value='$value'>"
  );
}

// taken from https://kristarella.blog/2008/12/geo-exif-data-in-wordpress/
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

function gtm_geo_dms2dec($fracs)
{
    list($deg, $frac) = preg_split("/\//", $fracs[0]);
    $deg = intval($deg);
    list($min, $frac) = preg_split("/\//", $fracs[1]);
    $min = intval($min);
    list($sec, $frac) = preg_split("/\//", $fracs[2]);
    //d($sec, $frac);
    $sec = floatval($sec) / floatval($frac);
    //d($deg, $min, $sec);
    return $deg + $min * (1.0/60.0) + $sec * (1.0/(60.0*60.0));
}

function gtm_get_geotagged_photos() {
  global $wpdb;

}
