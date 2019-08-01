var GtmGeomap = function (selector,library) {

    $ = jQuery;

    if (typeof library == 'undefined') {
        this.library = 'leaflet';
    }  else {
        this.library = library;
    }


    this.map = null;

    this.points = null;
    this.totalMediaCount = -1;
    this.keyBingMaps = '';
    this.keyThunderForest = '';
    this.keys = [];
    this.tileImagesSources = ['bingmaps','thunderforest','mapbox'];

    this.selector = selector;

    this.popup = null;

    this.coordinates = null;


    // properties for geometric figures
    this.stroke  = null;


    this.fill  = null;

    this.square = null;

    this.dot = null;


    this.ajaxurl = (typeof ajaxurl == 'undefined') ? '/wp-admin/admin-ajax.php' : ajaxurl;


    this.init = function () {

        console.log('>this init');
        //this.map = null;
        //      console.log('getElement:',this.map.getTargetElement());
        mapElem = $(this.selector).get();
        $(mapElem).data('map', this);

        $(this.map).on('click', function (evt, mapObj) {
            console.log('map object click!', evt);
            console.log('mapObj', this);
            geoMap = $(mapElem).data('map');
            console.log('geoMap', geoMap);
            geoMap.onClick(evt.originalEvent);
        });

        //map = this;
        return this;
    };

    this.fetchData = function (url, params) {

        $.get(url, params).success(function (response) {
            var point = [response[1],response[0]];
            console.log('>fecthedData', response);
            geoMap = $('#map').data('map');
           // $('#map').trigger('dataFetched', [response]);
            onDataFetched(response).then(function(map) {
                geoMap.processData(map);
            });
            geoMap.coordinates = point;
            console.log('fetchedData geoMap', geoMap);
            $('#map').data('map',geoMap);
        });


    };

    function grabMapSourceKeys() {
        geoMap = $('#map').data('map');
        var deferred = jQuery.Deferred();
        if ($('#map').length) { // fetch the data of geotagged media only if there is a map on the view
            $.get(ajaxurl + "?action=gtm_get_mapsources_keys", {}).success(
                function (response) {
                    console.log('mapsources keys', response);
                    Object.keys(response).forEach(function(key) {
                        var keyVal = response[key];
                        geoMap.keys[key] = keyVal;
                        console.log('Grabbed ' + key + " !");
                    });
                    $('#map').data('map',geoMap);
                    deferred.resolve('map source keys successfully grabbed');
                }
            );
        }
        return deferred.promise();
    }


    function onDataFetched ( data) {
        deferred = $.Deferred();
        geoMap = $('#map').data('map');
        console.log('!dataFetched! geoMap',geoMap);

        console.log('triggered dataFetched with data', data);
        mapContainerId = 'map';
        console.log('mapContainerId =',mapContainerId);
        geoMap.mapContainerId = mapContainerId;
        grabMapSourceKeys().then(function(statusMsg)
        {
            // set leaflet with latitude, longitude and zoom level
            geoMap = $('#map').data('map');
            console.log('geoMap inside=>',geoMap );
            point = [data[1],data[0]];
            geoMap.map = L.map(geoMap.mapContainerId).setView( point, 13);
            console.log("accessing keys inside closure",geoMap.keys);
            L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}',
                {
                    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
                    maxZoom: 18,
                    id: 'mapbox.satellite',
                    accessToken: geoMap.keys.key_mapbox
                }).addTo(geoMap.map);
            geoMap.coordinates = point;
            deferred.resolve(geoMap.map);

        });
        return deferred.promise();
    }

    $('#map').on('dataProcessed', function (evt,point) {
        console.log('triggered dataProcessed');
        geoMap = $(this).data('map');
        grabCoordinates(geoMap,point);
        geoMap.show();
    });


    this.processData = function (data) {
        console.log('>mapProcessData()', data);

        $('#map').trigger('dataProcessed',[point]);
    };

    this.show = function () {

        console.log('>this show');
        $(this.selector).trigger('afterShow', this);

    };


    this.onClick = function (originalEvt) {
        console.log('map click original', originalEvt);
        //var geoMap = $(this).data('map');
        console.log('geoMap', this);
        //$(geoMap.map).trigger('click',geoMap);
        var popupEl = geoMap.popup.getElement();
        console.log('popupEl', popupEl);
        console.log('evt.coordinate', originalEvt.coordinate);
//        this.popup.setPosition(originalEvt.coordinate);
        $(popupEl).popover('destroy');
//        var coord = ol.proj.transform(originalEvt.coordinate, 'EPSG:3857', 'EPSG:4326');
        console.log('coord after transform', coord);
        $(popupEl).popover({
            placement: 'auto',
            animation: true,
            html: true,
            content:  mst_render(mst_popup_content,{lat: coord[1],long: coord[0]} )
        });
        onShowPopup(popupEl);
        console.log('popupEl after popover',popupEl);
  //      layers = this.map.getLayers().getArray();
        console.log('layers',layers);
  //      var features = layers[1].getSource().getFeatures();
        console.log('existing features',features);
    }


    $(document).on('click','#geomark',function (evt) {

        evt.preventDefault();
        console.log("geomark button clicked!",this,evt);
        $(popupEl).trigger('geomark', params);

    });


    function grabCoordinates(geoMap,point) {
        console.log('grabCoordinates',point);

        geoMap.coordinates = point;
        $('map').data('map',geoMap);
    }

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


    $('#map').on('afterShow', function (evt) {
        var geoMap = $('#map').data('map');
        console.log('!afterShow! coordinates:',geoMap.coordinates);
        console.log('!afterShow!  geomap',geoMap);
        L.marker(geoMap.coordinates).addTo(geoMap.map);

    })
}



