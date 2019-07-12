// Resolve name collision between jQuery UI and Twitter Bootstrap
//jQuery.widget.bridge('uitooltip', $.ui.tooltip);


jQuery(document).ready(function ($) {

    var points = null;

    var totalMediaCount = -1;

    var keyBingMaps = '';
    var keyThunderForest = '';

    // in the frontend ajaxurl is not defined!
    if (typeof ajaxurl == 'undefined') {
      ajaxurl = '/wp-admin/admin-ajax.php';
    }


    var stroke = new ol.style.Stroke({
        color: 'black',
        width: 1
    });
    var fill = new ol.style.Fill({
        color: 'green'
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

    var dot = new ol.style.Style({
        image: new ol.style.Circle({
            fill: fill,
            stroke: stroke,
            radius: 3
        })
    });



        var mustache_tmpl = "";
        // load mustache js templates FIXME replace with a function that returns the url path
        $.get("/wp-content/plugins/geotagged-media/gtm.mst",{}).success(function(response) {
            console.log("Mustache templates file loaded!");
            mustache_tmpl = jQuery.parseHTML(response,document,true);
            $('head').append(mustache_tmpl);

        });


    if ($('#map').length) { // fetch the data of geotagged media only if there is a map on the view
        $.get(ajaxurl + "?action=gtm_get_mapsources_keys",{}).success(
            function(response) {
                console.log('mapsources keys',response);
                keyBingMaps = response['key_bingmaps'];
                keyThunderForest = response['key_thunderforest'];
            }
        );

        $.get(ajaxurl + "?action=gtm_geocoded_media",
            {}).success(
            function (response) {
                console.log('geocoded data', response[0]);
                //        console.log('Total media count', response[1]);
                totalMediaCount = parseInt(response[1]);
                points = response[0];
                $('#map').trigger('init');
            });
    }

    $('[name=source_map]').change(function(evt) {
        console.log("New source map value:",$(this).val());
        // clean the map
        $('#map').html('');
        var popupWrapperInitialHtml = mst_render('#mst_popup_wrapper_reset');
        $('#popup_wrapper').html(popupWrapperInitialHtml);
        console.log($('#popup_wrapper').html());
        // reinit the map
        $('#map').trigger('init');
        //$('#map').on('init', mapLoad);
    });

    $('#map').on('init', mapLoad);

    function mapLoad() {
        console.log('>mapLoad()');
        console.log([keyBingMaps,keyThunderForest]);

        if (points.length != 0) {
        $('#gtm-media-info').html('Found ' +  totalMediaCount + ' images, from which ' + points.length + ' are geocoded, whose locations are shown on the map.');
        $('.gtm-highlight').show();
      } else {
        $('#gtm-media-info').html('<STRONG>Found ' +  totalMediaCount + ' images, but none of them are geotagged ! Please upload geotagged pictures!</STRONG>');
      }

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
            features[i].setStyle(dot);
        }

 //       console.log('features', features);

        for (var i = 0; i < features.length; i++) {
            vectorSource.addFeature(features[i]);
        }

        var baseLayerTile = new ol.source.OSM();

        sourceMap = $('[name=source_map]:checked').val();

        console.log('mapInit() sourceMap',sourceMap);

        switch(sourceMap) {
            case "BingMaps": baseLayerTile = new ol.source.BingMaps({
                key: keyBingMaps,
                imagerySet: 'Road'
            }); break;
            case "ESRI-XYZ": baseLayerTile = new ol.source.XYZ({
                attributions: 'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
                    'rest/services/World_Topo_Map/MapServer">ArcGIS</a>',
                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/' +
                    'World_Topo_Map/MapServer/tile/{z}/{y}/{x}'
            }); break;
            case "OSM": baseLayerTile = new ol.source.OSM(); break;
            case "TileWMS": baseLayerTile = new ol.source.TileWMS({
                url: 'https://ahocevar.com/geoserver/wms',
                params: {'LAYERS': 'ne:ne', 'TILED': true},
                serverType: 'geoserver',
                crossOrigin: 'anonymous'}); break;
            case 'ThunderForest': baseLayerTile = new ol.source.XYZ({
                url: 'https://{a-c}.tile.thunderforest.com/outdoors/{z}/{x}/{y}.png' +
                    '?apikey=' + keyThunderForest
                });
                break;
            case 'Google': baseLayerTile = new ol.source.XYZ({
                attributions: [new ol.control.Attribution({ html: '<a href=""></a>' })],
                url: 'http://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}&s=Ga'
            });
                break;
        }

        //map.layers[0] = new ol.layer.Tile({source: new ol.source.BingMaps()})


        var map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: baseLayerTile
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

        $('#popup_wrapper').data('initial_state',$(this).html())
        map.addOverlay(popup);

        map.getView().fit(vectorSource.getExtent(), map.getSize());

        map.on('click', function(evt) {
            if (isAdmin()) {
                popupMedia_bootstrap(evt,map,popup);
            }
            else {
                popupMedia_jqui_tooltip(evt, map, popup);
            }
        });

        map.on('pointermove', function(e){
            var pixel = map.getEventPixel(e.originalEvent);
            var hit = map.hasFeatureAtPixel(pixel);
            map.getViewport().style.cursor = hit ? 'pointer' : '';
        });





    }

    // popup using jQuery UI tooltip
    function popupMedia_jqui_tooltip(evt,map,popup) {

        var popupElement = popup.getElement();
        if ($(popupElement).uitooltip().length) {
            $(popupElement).uitooltip('destroy');
        }
        popup.setPosition(evt.coordinate);
        //console.log('map click', evt);
        map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
            //  console.log('foreachpixel feature', feature);
            var properties = feature.getProperties();
            var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            console.log('properties', properties);
            // console.log('name', properties.name);
            // console.log('coordiantes', coord);
            var img_html = mst_render("#mst_popover_image", {'thumbnail_src':properties.thumbnail });
            $(popupElement).uitooltip({
                content: mst_render('#mst_popover_content',{name: properties.name , content: wp_media_link(img_html, properties.post_id), url_edit_image: wp_media_url(properties.post_id) } )
            });

            $(popupElement).uitooltip('open');
        });
    }


    // popup using jQuery UI dialog
    function popupMedia_jqui_dialog(evt,map,popup) {

        var popupElement = popup.getElement();
        if ($(popupElement).dialog().length) {
            $(popupElement).dialog('destroy');
        }
        popup.setPosition(evt.coordinate);
        //console.log('map click', evt);
        map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
            //  console.log('foreachpixel feature', feature);
            var properties = feature.getProperties();
            var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            console.log('properties', properties);

            var img_html = mst_render("#mst_popover_image", {'thumbnail_src':properties.thumbnail });
            $(popupElement).dialog({
                modal: false,
                resizable: false,
                height: 'auto',
                width: '300px'
            }).html(
                mst_render('#mst_popover_content',{name: properties.name , content: wp_media_link(img_html, properties.post_id), url_edit_image: wp_media_url(properties.post_id)  } ));

            $(popupElement).dialog('open');
        });
    }

    // popup using bootstrap4 tooltip
    function popupMedia_bootstrap4(evt,map,popup) {
        $('#popup').addClass('tooltip').attr('role','tooltip');
        var popupElement = popup.getElement();
        $(popupElement).tooltip('dispose');
        popup.setPosition(evt.coordinate);
        //console.log('map click', evt);
        map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
            //  console.log('foreachpixel feature', feature);
            var properties = feature.getProperties();
            var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            console.log('properties', properties);
            // console.log('name', properties.name);
            // console.log('coordiantes', coord);
            var img_html = mst_render("#mst_popover_image", {'thumbnail_src':properties.thumbnail });
            $(popupElement).tooltip({
                placement: 'top',
                animation: false,
                html: true,
                template: mst_render('#mst_popover_content',{name: properties.name , content: wp_media_link(img_html, properties.post_id), url_edit_image: wp_media_url(properties.post_id)  } )
            });
            $(popupElement).tooltip('show');
        });
    }

    // popup using popover from Bootstrap 3.x
    function popupMedia_bootstrap(evt,map,popup) {
        var popupElement = popup.getElement();
        $(popupElement).popover('destroy');
        popup.setPosition(evt.coordinate);
        //console.log('map click', evt);
        map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
            //  console.log('foreachpixel feature', feature);
            var properties = feature.getProperties();
            var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            console.log('properties', properties);
            // console.log('name', properties.name);
            // console.log('coordiantes', coord);
            var img_html = mst_render("#mst_popover_image", {'thumbnail_src':properties.thumbnail });
            $(popupElement).popover({
                placement: 'top',
                animation: false,
                html: true,
                content: mst_render('#mst_popover_content',{name: properties.name , content: wp_media_link(img_html, properties.post_id), url_edit_image: wp_media_url(properties.post_id)  } )
            });
            $(popupElement).popover('show');
        });
    }
});
