function GtmGeomap() {


    $ = jQuery;

    this.map = null;

    this.points = null;
    this.totalMediaCount = -1;
    this.keyBingMaps = '';
    this.keyThunderForest = '';


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
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                })]

        });
        //      console.log('getElement:',this.map.getTargetElement());

        var mapElem = this.map.getTargetElement();
        $(mapElem).data('map', this);
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

        //this.map.render();
        // var center = this.map.getView().getCenter();
        // var resolution = this.map.getView().getResolution();
        // this.map.getView().setCenter([center[0] + 10*resolution, center[1] + 10*resolution]);

        //this.map.getView().centerOn(this.projected,this.map.getSize(),[$mapElem.css('width')/2,$mapElem.prop('height')/2]);


        $mapElem.trigger('afterShow', this);

    };

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



