<?php
/**
 * Plugin Name: Geotagged Media
 * Description: Shpw the location data for every photo in the Media Library.
 * Plugin URI: https://github.com/digfish/geotagged-media
 * Author: digfish
 * Author URI: https://github.com/digfish
 * Version: 0.3.2
 * License: GPL2
 * Text Domain: gtm
 * Domain Path: digfish/gtm
 */


define( 'GTM_PLUGIN_NAME', 'Geotagged media' );
define( 'GTM_TEXT_DOMAIN', 'gtm' );


add_action( 'plugins_loaded', 'gtm_plugin_instantiate', 5 );

//
//  add_action("deactivated_plugin", function ($arg1, $arg2) {
//  }, 10, 2);

register_activation_hook( __FILE__, 'gtm_hook_on_plugin_activation' );

require_once WP_PLUGIN_DIR . '/geotagged-media/gtm_dash.php';
require_once WP_PLUGIN_DIR . '/geotagged-media/gtm_helpers.php';
require_once WP_PLUGIN_DIR . '/geotagged-media/gtm_frontend.php';
require_once WP_PLUGIN_DIR . '/geotagged-media/gtm_ajax.php';

function gtm_hook_on_plugin_activation() {


	$plugin_dir = __DIR__;

	$option_names       = gtm_option_names();
	$option_values      = get_option( 'gtm_options' );
	$new_options_values = array();
	foreach ( $option_names as $name ) {
		if ( ! isset( $option_values[ $name ] ) ) {
			// if not already created, creat the option values to true with the exception
			// of geocode_on_upload
			if ( $name != 'geocode_on_upload' ) {
				$new_options_values[ $name ] = 'true';
			}
		}
	}

	if ( count( $new_options_values ) > 0 ) {
		update_option( 'gtm_options', $new_options_values );
	}

	$gtm_options = get_option( 'gtm_options' );

	if ( file_exists( "$plugin_dir/vendor" ) && is_dir( "$plugin_dir/vendor" ) ) {
		return;
	}
}


function gtm_plugin_instantiate() {

    add_action('init', 'gtm_init');
}


function gtm_init()
{
    add_rewrite_rule("notmpl/([^/]*)/?", 'index.php?notmpl=$matches[1]', 'top');

    add_filter('query_vars', 'gtm_query_vars');
    add_filter('template_include', 'gtm_no_tmpl', 5);

    register_taxonomy_for_object_type('category', 'attachment');
    register_taxonomy_for_object_type('post_tag', 'attachment');

    if (wp_doing_ajax()) {
    } elseif (is_admin()) {
//		debug( 'Loading backoffice...' );
		gtm_dashboard_init();
	} else {
//		debug( 'Loading frontoffice...' );
		gtm_frontend_init();
	}

	$gtm_options = get_option( 'gtm_options' );
}

function gtm_query_vars($qv)
{
    $qv[] = 'notmpl';
    remove_filter('query_vars', 'gtm_query_vars');

    return $qv;
}

function gtm_no_tmpl($template)
{
    global $wp_query;
    global $wp_rewrite;
    $wpqv = $wp_query->query_vars;
    if (isset($_REQUEST['notmpl'])) {
        $wpqv['notmpl'] = $_REQUEST['notmpl'];
    }

    if (isset($wp_query->query_vars['notmpl'])) {
        $pagename = $wp_query->query_vars['notmpl'];
        switch ($pagename) {
            case 'gtm_geomark':
                ob_start();
                require_once(__DIR__ . "/$pagename.php");
                http_response_code(200);
                echo ob_get_clean();
                break;
            default:
                return $template;
        }
    } else {
        return $template;
	}
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gtm_add_action_links');

function gtm_add_action_links($links)
{
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=gtm-admin-options') . '">Settings</a>',
        '<a href="' . admin_url('upload.php?page=gtm') . '">Geottaged media map</a>',
    );
    return array_merge($links, $mylinks);
}








