
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


        //map = this;
        return this;
    };

    this.fetchData = function (url, params) {

        $.get(url, params).success(function (response) {
            console.log('>fecthedData', response);
            geoMap = $('#map').data('map');
           // $('#map').trigger('dataFetched', [response]);
            onDataFetched(response).then(function(map) {
                geoMap.processData(map);
            });
            console.log('fetchedData geoMap', geoMap);
            $('#map').data('map',geoMap);
        });


    };


    this.onClick = function (originalEvt) {
        console.log('!this on.Click()! originalEvt = ', originalEvt);
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

    $(this.selector).on('dataProcessed', function (evt, point) {
        console.log('triggered dataProcessed');
        geoMap = $(this).data('map');
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




    $(document).on('click','#geomark',function (evt) {
        evt.preventDefault();
        newCoordinates = $('#map').data('lastPos');
        console.log("geomark button clicked!",this,evt);
        $(evt.target).trigger('geomark', [newCoordinates]);
    });

    $(document).on('geomark','#geomark',function (evt,newCoordinates) {
        $.get(ajaxurl + "?action=gtm_geomark",
        {
            post_id: gtm_post_id,
            coordinates: $('#map').data('lastPos')
        }).success(
        function (response) {
            console.log('geomark response', response);
            if (response.success) {
                $('button#geomark').replaceWith('<STRONG>The photo was tagged with success!</STRONG>');
            }
        })
    });




    $(this.selector).on('afterShow', function (evt) {
        var geoMap = $('#map').data('map');
        var lastPos = null;
        var popUp = null;
        console.log('!afterShow! coordinates:',geoMap.coordinates);
        console.log('!afterShow!  geomap',geoMap);

        // point the marker
        marker = L.marker(geoMap.coordinates, {draggable: true}).addTo(geoMap.map);

        console.log('click geoMap ', geoMap);

        // add click on marker event handler
        geoMap.map.on('click', function (e) {
            console.log(e.type,e);
            popUp = L.popup()
                .setLatLng(e.latlng)
                .setContent("You clicked the map at " + e.latlng.toString())
                .openOn(geoMap.map);
        });

//        marker.draggable = true;

        // click on every point handler
        if (geoMap.coordinates != undefined) {
            popUp = marker.bindPopup(mst_render('#mst_popup_content', {
                lat: geoMap.coordinates[0],
                long: geoMap.coordinates[1]
            })).on('dragend',function(evt) {
                console.log('Marker '+ evt.type + ' at  ' + evt.distance  + ' to ' + lastPos.toString() + ' from source !',evt);
                console.log('lastPos obj = ',lastPos);
                popUp = marker.bindPopup(mst_render('#mst_popup_confirm_new_loc', {lat: lastPos.lat, long: lastPos.lng}));
                $('#map').data('lastPos',[lastPos.lng,lastPos.lat]);
            }).on('move', function(evt) {
                console.log('Marker '+ evt.type + ' to ' +  evt.latlng.toString(),evt);
                lastPos = evt.latlng;                
            });
            // show popup
            L.popup().openOn(geoMap.map);
        }


    });


};



