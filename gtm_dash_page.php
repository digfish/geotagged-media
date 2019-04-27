<H1>Geotagged media</H1>

<?php

$args = array(
	'post_type'   => 'attachment',
	'post_status' => 'any',
	'orderby'     => 'date',
	'order'       => 'DESC',
	'nopaging'    => true,
);

add_filter( 'posts_where', 'gtm_mime_type_image', 10, 2 );

function gtm_mime_type_image( $where, $wp_query ) {
	$where .= " AND post_mime_type LIKE 'image%' ";

	return $where;
}


add_action( 'the_post', 'gtm_add_metadata_field' );
function gtm_add_metadata_field( &$postObj ) {
	$postObj->metadata = wp_get_attachment_metadata( $postObj->ID );

	return $postObj;
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
	echo "<P>Found {$query->post_count} images, from which " . count( $geocoded_images ) . " are geocoded, whose locations are shown on the map.</P>";
//	d( $geocoded_images );
	$images_only_geodata = gtm_extract_geodata_from_post( $geocoded_images );
//	d( $images_only_geodata );
}

remove_filter( 'posts_where', 'gtm_mime_type_image' );
?>
<P style="font-weight: bold;">Click on every square to show a popup with a thumbnail for the photo. Clicking on the thumbnail will take you to the 'Edit Photo' view.</P>
<link rel="stylesheet" href="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/css/ol.css"
      type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
<style>
    .map {
        height: 400px;
        width: 100%;
    }
</style>
<!-- TODO replace this script elements with enqueueScripts()-->
<script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/build/ol.js"></script>
<script src="https://code.jquery.com/jquery-2.2.3.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<div id="map" class="map">
</div>
<div style="display:none">
    <!-- Popup -->
    <div id="popup" class="popup" title="Here is:"></div>
</div>
<script type="text/javascript">
	<?php
	echo "var points = " . json_encode( $images_only_geodata ) . " ;";
	?>
    $ = jQuery;
    //jQuery.noConflict();
    jQuery(document).ready(function ($) {
// $('.popover').popover();
    });
    // $(function () {
    //   $('[data-toggle="popover"]').popover();
    // });

    var stroke = new ol.style.Stroke({
        color: 'black',
        width: 2
    });
    var fill = new ol.style.Fill({
        color: 'red'
    });
    var square = new ol.style.Style({
        image: new ol.style.RegularShape({
            fill: fill,
            stroke: stroke,
            points: 4,
            radius: 10,
            angle: Math.PI / 4
        })
    });

    var features = new Array(points.length);
    var vectorSource = new ol.source.Vector({});

    for (var i = 0; i < points.length; i++) {
        var point = points[i];
        var coordinates = [parseFloat(point.longitude), parseFloat(point.latitude)];
        var projected = ol.proj.transform(coordinates, 'EPSG:4326', 'EPSG:3857');
        features[i] = new ol.Feature({
            geometry: new ol.geom.Point(projected),
            name: point.title,
            thumbnail: point.thumbnail,
            post_id: point.post_id
        });
        features[i].setStyle(square);
    }

//    console.log('features', features);

    for (var i = 0; i < features.length; i++) {
        vectorSource.addFeature(features[i]);
    }


    var map = new ol.Map({
        target: 'map',
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            new ol.layer.Vector({
                source: vectorSource
            })
        ],
        view: new ol.View({
            //center: ol.proj.fromLonLat([-7.59,37.13 ]),
            //center: 'auto',
            zoom: 10
        })
    });

    // Popup showing the position the user clicked
    var popup = new ol.Overlay({
        element: document.getElementById('popup')
    });
    map.addOverlay(popup);

    map.getView().fit(vectorSource.getExtent(), map.getSize());

    jQuery(document).ready(function ($) {
        // $('.popover').popover();
        map.on('click', function (evt) {
            var popupElement = popup.getElement();
            $(popupElement).popover('destroy');
            popup.setPosition(evt.coordinate);
            //console.log('map click', evt);
            map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
              //  console.log('foreachpixel feature', feature);
                var properties = feature.getProperties();
                var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                // console.log('properties', properties);
                // console.log('name', properties.name);
                // console.log('coordiantes', coord);
                var img_html = "<IMG src='/wp-content/uploads/" + properties.thumbnail + "'>";
                $(popupElement).popover({
                    placement: 'top',
                    animation: false,
                    html: true,
                    content: "<P><STRONG>" + properties.name + "</STRONG>" + wp_media_link(img_html, properties.post_id) + "</P>"
                });
                $(popupElement).popover('show');
            });
        });
    });

    function wp_media_link(link_text, image_post_id) {
        return "<A href='/wp-admin/upload.php?item=" + image_post_id + "&mode=grid' target='_blank'>" + link_text + "</A>";
    }

</script>
