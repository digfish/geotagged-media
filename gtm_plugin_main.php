<?php
/**
 * Plugin Name: Geotagged Media
 * Description: Shpw the location data for every photo in the Media Library.
 * Plugin URI: https://github.com/digfish/geotagged-media
 * Author: digfish
 * Author URI: https://github.com/digfish
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
	gtm_dashboard_init();
}


function gtm_dashboard_init() {
	add_action('admin_menu','gtm_add_media_menu_item');
	add_action('attachment_submitbox_misc_actions', 'gtm_submitbox_misc_actions', 15);
	add_filter('manage_media_columns','gtm_add_metadata_column');
	add_action('manage_media_custom_column','gtm_add_metadata_custom_column',10,2);
	add_filter('attachment_fields_to_edit', 'gtm_attachment_field_to_edit', 10, 2);
	add_action( 'admin_enqueue_scripts', 'gtm_admin_scripts',1000 );
}



function gtm_admin_scripts($hook_suffix) {
	PC::debug($hook_suffix,__FUNCTION__);
	if ($hook_suffix == 'media_page_gtm') {
		$ol_css        = plugin_dir_url( __FILE__ ) . 'ol/ol-5.3.0.css';
		$ol_js         = plugin_dir_url( __FILE__ ) . 'ol/ol-5.3.0.js';
		$bootstrap_css = plugin_dir_url( __FILE__ ) . 'bootstrap/bootstrap-3.3.6.min.css';
		$bootstrap_js  = plugin_dir_url( __FILE__ ) . 'bootstrap/bootstrap-3.3.6.min.js';
		$gtm_css = plugin_dir_url(__FILE__) . 'gtm.css';
		wp_register_style( 'ol_css', $ol_css, array(), '5.3.0' );
		wp_register_script( 'ol_js', $ol_js,array() , '5.3.0' );
		wp_register_style( 'bootstrap_css', $bootstrap_css,'3.3.6' );
		wp_register_script( 'bootstrap_js', $bootstrap_js ,array('jquery'),'3.3.6');
		wp_register_style('gtm_css',$gtm_css);
		wp_enqueue_style( 'bootstrap_css' );
		wp_enqueue_style( 'ol_css' );
		wp_enqueue_style('gtm_css');
		wp_enqueue_script( 'bootstrap_js' );
		wp_enqueue_script( 'ol_js');
		wp_add_inline_script('ol_js',file_get_contents( plugin_dir_path(__FILE__) . 'gtm_footer_map_scripts.js' ));
	}
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
	$args = array(
		'post_type'   => 'attachment',
		'post_status' => 'any',
		'orderby'     => 'date',
		'order'       => 'DESC',
		'nopaging'    => true,
	);
	add_filter( 'posts_where', 'gtm_mime_type_image', 10, 2 );
	add_action( 'the_post', 'gtm_add_metadata_field' );
	$images_only_geodata = null;
	$query               = new WP_Query( $args );
//d( $query->post_count );
	$geocoded_images = array();
//d( $query->request );
	if ( $query->have_posts() ) {
		$posts = $query->get_posts();
		foreach ( $posts as $post ) {
			$md = wp_get_attachment_metadata( $post->ID );
			if ( $md && ! empty( $md['image_meta']['latitude'] ) ) {
				$post->metadata    = $md;
				$geocoded_images[] = $post;
			}
		}
		echo "";
//	d( $geocoded_images );
		$images_only_geodata = gtm_extract_geodata_from_post( $geocoded_images );
//	d( $images_only_geodata );
	}

	remove_filter( 'posts_where', 'gtm_mime_type_image' );
	return array($images_only_geodata,$query->post_count);
}

function gtm_add_metadata_field( &$postObj ) {
	$postObj->metadata = wp_get_attachment_metadata( $postObj->ID );

	return $postObj;
}

function gtm_mime_type_image( $where, $wp_query ) {
	$where .= " AND post_mime_type LIKE 'image%' ";

	return $where;
}


function gtm_extract_geodata_from_post( $posts ) {
	return array_map( function ( $post ) {
		$md             = $post->metadata['image_meta'];
		$image_sizes    = $post->metadata['sizes'];
		$image_location = preg_split( '/\//', $post->metadata['file'] );
		$image_location = join( '/', array_slice( $image_location, 0, 2 ) );
		if ( ! empty( $image_sizes['thumbnail'] ) ) {
			$thumbnail_filename = $image_location . '/' . $image_sizes['thumbnail']['file'];
		}

		return array(
			'title'     => $post->post_title,
			'latitude'  => ( ( $md['latitude_ref'] == 'S' ) ? "-" : "" ) . gtm_geo_dms2dec( $md['latitude'] ),
			'longitude' => ( ( $md['longitude_ref'] == 'W' ) ? "-" : "" ) . gtm_geo_dms2dec( $md['longitude'] ),
			'thumbnail' => $thumbnail_filename,
			'post_id'   => $post->ID
		);
	}, $posts );
}

add_action('wp_ajax_gtm_geocoded_media', function() {
	header( 'Content-type: application/json' );
	echo json_encode(gtm_get_geotagged_photos());
	wp_die();
});

