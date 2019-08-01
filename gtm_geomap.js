var GtmGeomap = function (selector,library) {

    $ = jQuery;

    if (typeof library == 'undefined') {
        this.library = 'openlayers';
    }  else {
        this.library = library;
    }

    this.map = null;

    this.points = null;
    this.totalMediaCount = -1;
    this.keyBingMaps = '';
    this.keyThunderForest = '';
    this.selector = selector;

    this.popup = new ol.Overlay({
        element: document.getElementById('popup')
    });


    // properties for geometric figures
    this.stroke = new ol.style.Stroke({
        color: 'black',
        width: 1
    });


    this.fill = new ol.style.Fill({
        color: 'green'
    });

    this.square = new ol.style.Style({
        image: new ol.style.RegularShape({
            fill: this.fill,
            stroke: this.stroke,
            points: 4,
            radius: 10,
            angle: Math.PI / 4
        })
    });

    this.dot = new ol.style.Style({
        image: new ol.style.Circle({
            fill: this.fill,
            stroke: this.stroke,
            radius: 3
        })
    });


    this.ajaxurl = (typeof ajaxurl == 'undefined') ? '/wp-admin/admin-ajax.php' : ajaxurl;


    this.init = function () {


        console.log('>this init');
        this.map = new ol.Map({
            target: this.selector.replace('#', ''),
            layers: [
                new ol.layer.Tile({
                    preload: Infinity,
                    source: new ol.source.OSM()
                })]

        });
        //      console.log('getElement:',this.map.getTargetElement());

        this.map.addOverlay(this.popup);

        var mapElem = this.map.getTargetElement();
        console.log('targetElement', mapElem);
        $(mapElem).data('map', this);

        $(this.map).on('click', function (evt, mapObj) {
            console.log('map object click!', evt);
            console.log('mapObj', this);
            geoMap = $(mapElem).data('map');
            console.log('geoMap', geoMap);
            geoMap.onClick(evt.originalEvent);
        });


        return this;
    };

    this.fetchData = function (url, params) {

        $.get(url, params).success(function (response) {
            console.log('>fecthedData', response);
            geoMap = $('#map').data('map');
            $('#map').trigger('dataFetched', [response])
        });


    };

    $('#map').on('dataFetched', function (evt, data) {
        console.log('triggered dataFetched with data', data);
        geoMap = $(this).data('map');
        geoMap.processData(data);
    }).on('dataProcessed', function (evt) {
        console.log('triggered dataProcessed');
        geoMap = $(this).data('map');
        geoMap.show();
    });


    this.processData = function (data) {

        console.log('>mapProcessData()', data);
        this.coordinates = data;
        this.vectorSource = new ol.source.Vector({});
        //this.coordinates = [ -7.5, 37.50];
        this.projected = ol.proj.transform(this.coordinates, 'EPSG:4326', 'EPSG:3857');

        feature = new ol.Feature({
            geometry: new ol.geom.Point(this.projected),
            name: 'Here is'
        });
        feature.setStyle(this.dot);

        this.vectorSource.addFeature(feature);

        $('#map').trigger('dataProcessed');

    };

    this.show = function () {
        console.log('>this show');
        this.map.addLayer(new ol.layer.Vector({
            source: this.vectorSource
        }));

        this.map.setView(new ol.View({
            zoom: 5,
            center: this.coordinates
        }));

        var $mapElem = $(this.map.getTargetElement());


        $mapElem.trigger('afterShow', this);

    };


    this.onClick = function (originalEvt) {
        console.log('map click original', originalEvt);
        //var geoMap = $(this).data('map');
        console.log('geoMap', this);
        //$(geoMap.map).trigger('click',geoMap);
        var popupEl = geoMap.popup.getElement();
        console.log('popupEl', popupEl);
        console.log('evt.coordinate', originalEvt.coordinate);
        this.popup.setPosition(originalEvt.coordinate);
        $(popupEl).popover('destroy');
        var coord = ol.proj.transform(originalEvt.coordinate, 'EPSG:3857', 'EPSG:4326');
        console.log('coord after transform', coord);
        $(popupEl).popover({
            placement: 'auto',
            animation: true,
            html: true,
            content:  mst_render(mst_popup_content,{lat: coord[1],long: coord[0]} )
        });
        onShowPopup(popupEl);
        console.log('popupEl after popover',popupEl);
        layers = this.map.getLayers().getArray();
        console.log('layers',layers);
        var features = layers[1].getSource().getFeatures();
        console.log('existing features',features);
        features.forEach(function(feat) {
            layers[1].getSource().removeFeature(feat);
        });

        var projected = ol.proj.transform(originalEvt.coordinate, 'EPSG:3857', 'EPSG:4326');
        console.log('coordinates on click point:', projected);



        newFeature = new ol.Feature({
            geometry: new ol.geom.Point(projected),
        });

        newFeature.setStyle(this.dot);

        newVectorSource = new ol.source.Vector({});
        newVectorSource.addFeature(newFeature);
        this.map.removeLayer(layers[1]);
        this.map.addLayer(new ol.layer.Vector({
            source: newVectorSource
        }));
        console.log("nr layers",this.map.getLayers().getArray().length);

    }


    $(document).on('click','#geomark',function (evt) {

        evt.preventDefault();
        console.log("geomark button clicked!",this,evt);
        $(popupEl).trigger('geomark', params);

    });

    function onShowPopup(popupEl) {
        $(popupEl).popover('show');
        $(popupEl).on('shown.bs.popover', function (evt) {
            console.log('shown.bs.popover!');
            // set handler when click on popover's button
        });

    }


    $(this.popup).on('geomark',function(evt,params) {
        console.log('map geomark!', evt, params);

    });


    $('#map').on('afterShow', function (evt, geoMap) {
        //var geoMap = $(this).data('map');
        mapObj = geoMap.map;
        console.log('>afterShow', mapObj);
        var layers = mapObj.getLayers().getArray();
        var source = layers[1].getSource();
        var feauture = source.getFeatures()[0];
        var polygon = feature.getGeometry();
        console.log('features', feature);
        mapObj.getView().fit(polygon, {padding: [170, 50, 30, 150], minResolution: 100});

        //mapObj.getView().centerOn(geoMap.projected,geoMap.map.getSize(),feature.getGeometry());
    })
}



