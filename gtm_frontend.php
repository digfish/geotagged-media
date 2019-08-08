<?php


$GLOBALS['available_sources'] = array(
	'OSM',
	'BingMaps',
	'ESRI-XYZ',
	'TileWMS',
	'ThunderForest',
	'Google',
	'MapBox'
);

function gtm_frontend_init() {

	add_action( 'wp_enqueue_scripts', 'gtm_frontend_scripts' );
	add_shortcode( 'gtm_map', function ( $shortcode_attrs ) {

		global $available_sources, $wp_query;

		$category = array_key_exists( 'category', $shortcode_attrs ) ? $shortcode_attrs['category'] : 'all';
		d( $category );

		$tags = '';
		if ( array_key_exists( 'tags', $shortcode_attrs ) ) {
			$tags = $shortcode_attrs['tags'];
			d($tags);
			// replace commnas with plus signs
			//$tags = str_replace(',','+',$tags);
			//$wp_query->query_vars['tags'] = $tags;
		}

		$using_sources = $available_sources;
		if ( array_key_exists( 'sources', $shortcode_attrs ) && $shortcode_attrs['sources'] != 'all' ) {

			$declared_sources = preg_split( '/,/', $shortcode_attrs['sources'] );
			$using_sources    = array_intersect( $declared_sources, $available_sources );
		};
		d( $using_sources );


		require_once "gtm_frontend_map.php";
	} );


	add_filter( 'body_class', function ( $body_classes ) {
		$body_classes[] = 'gtm-body';

		return $body_classes;
	} );


}

/**
 * NOTE: Bootstrap is not loaded in the frontend, since any theme already using it will break stuff
 *
 * @param $hook_suffix
 */
function gtm_frontend_scripts( $hook_suffix ) {
	$ol_css              = plugin_dir_url( __FILE__ ) . 'ol/ol-5.3.0.css';
	$ol_js               = plugin_dir_url( __FILE__ ) . 'ol/ol-5.3.0.js';
	$bootstrap_css       = plugin_dir_url( __FILE__ ) . 'bootstrap/bootstrap.css';
	$bootstrap_css_theme = plugin_dir_url( __FILE__ ) . 'bootstrap/bootstrap-theme.css';
	$bootstrap_js        = plugin_dir_url( __FILE__ ) . 'bootstrap/bootstrap-3.3.6.min.js';
	$mustache_js         = plugin_dir_url( __FILE__ ) . 'mustache/mustache-3.0.1.js';
	$gtm_css             = plugin_dir_url( __FILE__ ) . 'gtm.css';
	$gtm_js              = plugin_dir_url( __FILE__ ) . 'gtm.js';
	$jqueryui_base_css   = 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';

	wp_register_style( 'ol_css', $ol_css, array(), '5.3.0' );
	wp_register_script( 'ol_js', $ol_js, array(), '5.3.0' );
//  wp_register_style( 'bootstrap_css', $bootstrap_css, array(), '3.3.6' );
//  wp_register_style( 'bootstrap_css_theme', $bootstrap_css_theme, array(), '3.3.6' );
	wp_register_style( 'jqueryui_base_css', $jqueryui_base_css, array(), '1.12.1' );

//  wp_register_script( 'bootstrap_js', $bootstrap_js, array( 'jquery' ), '3.3.6' );
	wp_register_script( 'mustache_js', $mustache_js, array(), '3.0.1' );
	wp_register_style( 'gtm_css', $gtm_css );
	wp_register_script( 'gtm_js', $gtm_js );

	wp_enqueue_style( 'jqueryui_base_css' );
	//wp_enqueue_style('wp-jquery-ui-dialog');
//  wp_enqueue_style( 'bootstrap_css' );
//  wp_enqueue_style('bootstrap_css_theme');
	wp_enqueue_style( 'ol_css' );
	wp_enqueue_style( 'gtm_css' );

	wp_enqueue_script( 'jquery-core' );
	wp_enqueue_script( 'jquery-ui-core', false, array(), false, false );
	wp_enqueue_script( 'jquery-ui-widget', '', array( 'jquery-ui-core' ), false, false );
	wp_enqueue_script( 'jquery-ui-tooltip', '', array( 'jquery-ui-widget' ), false, false );
	wp_enqueue_script( 'jquery-ui-dialog', '', array( 'jquery-ui-widget' ), false, false );

	wp_add_inline_script( 'jquery-ui-tooltip', 'jQuery.widget.bridge(\'uitooltip\', jQuery.ui.tooltip);' );

//  wp_enqueue_script( 'bootstrap_js','',array('jquery-ui-tooltip-bridge'));
	wp_enqueue_script( 'mustache_js', '', array( 'jquery-core' ) );
	wp_enqueue_script( 'gtm_js', '', array( 'ol_js' ) );
	wp_enqueue_script( 'ol_js', '', array( 'jquery-ui-tooltip-bridge' ) );

	global $post;
//	d('post on top',$post);
	// load mustache templates if there is a post with a shortcode in it
	if ( has_shortcode( $post->post_content, 'gtm_map' ) ) {
		wp_add_inline_script( 'gtm_js', 'initMustacheTemplates()' );
	}
	/*    if (isset($shortcode_attrs['category'])) {
			$category = $shortcode_attrs['category'];
			 wp_add_inline_script ("gtm_js","var category = \'$category\' ;");
		}*/
	wp_register_script( "gtm_footer_map", plugin_dir_url( __FILE__ ) . '/gtm_footer_map_scripts.js' );
	wp_enqueue_script( 'gtm_footer_map', '', array( 'gtm_js', 'ol_js' ) );

	// wp_add_inline_script('ol_js', file_get_contents(plugin_dir_path(__FILE__) . 'gtm_footer_map_scripts.js'));
}

