// js code for geomark
// FIXME zoom level must be closer

jQuery(document).ready(function ($) {

    console.log('gtm_marknew_scripts ready!');

    var points = null;

    var totalMediaCount = -1;


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


    console.log('mapLoad init');

    var features = new Array(0);
    var vectorSource = new ol.source.Vector({});


    var map = new ol.Map({
        target: 'gtm-marker-map',
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            new ol.layer.Vector({
                source: vectorSource
            })
        ],
        view: new ol.View({
            center: ol.proj.fromLonLat([-7.59, 37.13]),
            zoom: 7
        })
    });

    // Popup showing the position the user clicked
    var popup = new ol.Overlay({
        element: document.getElementById('popup')
    });

    map.addOverlay(popup);

    //map.getView().fit(vectorSource.getExtent(), map.getSize());


    map.on('click', function (evt) {
        var popupElement = popup.getElement();
        $(popupElement).popover('destroy');
        popup.setPosition(evt.coordinate);
        console.log('map click', evt);
        var coord = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
        console.log('coordiantes', coord);
        $(popupElement).on('shown.bs.popover', function (evt) {
            console.log('shown.bs.popover!');
            // set handler when click on popover's button
            $('button#geomark').on('click', function (evt) {
                $(map).trigger('geomark', {coord: coord, post_id: post_id});
            });

        });

        $(popupElement).popover({
            placement: 'top',
            animation: false,
            html: true,
            content: "<STRONG>lat:</STRONG>" + coord[1] + "<BR/>"
                + "<STRONG>long</STRONG>: " + coord[0]
                + " Do to you want to set this place as the location tag for the image !?</STRONG><BUTTON id='geomark'>yes</BUTTON>"
        });
        $(popupElement).popover('show');
    });


    $(map).on('geomark', function (evt, params) {
        console.log('map geomark!', evt, params);
        $.get(ajaxurl + "?action=gtm_geomark",
            {
                post_id: params.post_id,
                coordinates: params.coord
            }).success(
            function (response) {
                console.log('geomark response', response);
                if (response.success) {
                    $('button#geomark').replaceWith('<STRONG>The photo was tagged with success!</STRONG>');
                }
            });
    });
});


