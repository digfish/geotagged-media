// Resolve name collision between jQuery UI and Twitter Bootstrap
//jQuery.widget.bridge('uitooltip', $.ui.tooltip);


jQuery(document).ready(function ($) {

    var points = null;

    var totalMediaCount = -1;

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

    if ($('#map').length) { // fetch the data of geotagged media only if there is a map on the view
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

    $('#map').on('init', mapLoad);

    function mapLoad() {
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

        map.on('click', function(evt) {
            popupMedia_jqui_dialog(evt,map,popup);
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
            var img_html = "<IMG src='/wp-content/uploads/" + properties.thumbnail + "' style='width: 200px; max-width: 288px'>";
            $(popupElement).uitooltip({
                content: "<H3>" + properties.name + "</H3>" + wp_media_link(img_html, properties.post_id) + "</P>"
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
            // console.log('name', properties.name);
            // console.log('coordiantes', coord);
            var img_html = "<IMG src='/wp-content/uploads/" + properties.thumbnail + "' style='width: 200px; max-width: 288px'>";
            $(popupElement).dialog({
                modal: false,
                resizable: false,
                height: 'auto',
                width: '300px',
                buttons: false
            }).html("<P class='gtm-popup-title'>" + properties.name + "</P>" + wp_media_link(img_html, properties.post_id) + "</P>");

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
            var img_html = "<IMG src='/wp-content/uploads/" + properties.thumbnail + "' style='width: 200px; max-width: 288px'>";
            $(popupElement).tooltip({
                placement: 'top',
                animation: false,
                html: true,
                template: "<P><STRONG>" + properties.name + "</STRONG>" + wp_media_link(img_html, properties.post_id) + "</P>"
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
            var img_html = "<IMG src='/wp-content/uploads/" + properties.thumbnail + "' style='width: 200px; max-width: 288px'>";
            $(popupElement).popover({
                placement: 'top',
                animation: false,
                html: true,
                content: "<P><STRONG>" + properties.name + "</STRONG>" + wp_media_link(img_html, properties.post_id) + "</P>"
            });
            $(popupElement).popover('show');
        });
    }
});
