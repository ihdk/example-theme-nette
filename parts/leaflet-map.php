{if isset($mapID)}
	{var $containerID = $mapID}
{elseif isset($htmlId)}
	{var $containerID = $htmlId}
{else}
	{var $containerID = 'default-map'}
{/if}

{var $params = isset($params) ? $params : array()}
{var $options = isset($options) ? $options : array()}
{var $markers = isset($markers) ? $markers : array()}
{var $themeOptions = isset($themeOptions) ? $themeOptions : array()}
{var $tilesUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?' }

<div id="{$containerID}-container" class="leaflet-map-container {if isset($classes)}{!$classes}{/if}">

</div>

<script>
(function($, $window, $document, globals){
"use strict";


var MAP = MAP || {};

MAP = $.extend(MAP, {
	provider: 'openstreetmap',
	map: null,
	markers: [],
	markersLayer: null,
	placedMarkers: [],
	bounds:  null,
	locations: [],
	currentInfoWindow: null,
	clusterer: null,
	multimarker: [],
	containerID: '',
	panorama: null,
	ibTimeout: null,

	mapOptions: {
		center: { lat: 0, lng: 0},
		zoom: 3,
		draggable: true,
		scrollwheel: false,
	},

	params: {
		name: '',
		enableAutoFit: false,
		enableClustering: false,
		enableGeolocation: false,
		customIB: true,
		radius: 100,
		i18n: [],
	},



	initialize: function(containerID, mapMarkers, options, params){
		MAP.markers     = $.extend( MAP.markers, mapMarkers );
		MAP.mapOptions  = $.extend( MAP.mapOptions, options );
		//correct starting latitude and longitude options from 0,0 to values from Header Map Element to use as starting position the position defined inside element
		if( typeof params.address !== "undefined" ){
			MAP.mapOptions.center.lat = parseFloat(params.address.latitude);
			MAP.mapOptions.center.lng = parseFloat(params.address.longitude);
		}

		MAP.params      = $.extend( MAP.params, params );
		MAP.clusterer   = L.markerClusterGroup( { chunkedLoading: true, maxClusterRadius: MAP.params.clusterRadius} );
		MAP.markersLayer= L.featureGroup();
		MAP.bounds      = L.latLngBounds();
		MAP.containerID = containerID;

		var mapContainer = $("#" + containerID + "-container").get(0);
		MAP.mapContainer = mapContainer;

		MAP.map = L.map(mapContainer, {
			center: [MAP.mapOptions.center.lat, MAP.mapOptions.center.lng],
			zoom: MAP.mapOptions.zoom,
			gestureHandling: true,
			gestureHandlingOptions: {
        		duration: 3000
    		}
		});

		L.tileLayer({$tilesUrl}, {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
		}).addTo(MAP.map);

		MAP.map.addLayer(MAP.markersLayer);
		MAP.map.addLayer(MAP.clusterer);

		// create global variable (if doesn't exist)
		// make sure you are using unique name - there might be another map already stored
		// store only map with defined name parameter
		if (typeof globals.globalMaps === "undefined") {
			globals.globalMaps = {};
		}


		MAP.initMarkers(MAP.markers);

		if ( MAP.params.enableClustering) {
			MAP.initClusterer();
		};

		if ( MAP.params.enableGeolocation ) {
			MAP.setGeolocation();
		} else if( MAP.params.enableAutoFit ) {
			MAP.autoFit();
		}



		if (MAP.params.name !== "") {
			globals.globalMaps[MAP.params.name] = MAP;
		}
	},



	initMarkers: function(markers){
		for (var i in markers) {
			var marker = markers[i];
			if ( typeof type !== 'undefined' && marker.type !== type) {
				continue;
			}
			var location = L.latLng(marker.lat, marker.lng);
			MAP.bounds.extend(location);
			var newMarker = MAP.placeMarker(marker);
			MAP.placedMarkers.push(newMarker);
		}
	},



	placeMarker: function(point){
		var markerOptions = {
			context: point.context,
			type: point.type,
			id: point.id,
			data: point.data,
			enableInfoWindow: point.enableInfoWindow
		};

		if (point.icon) {
			var html = `<img src="${ point.icon }">`;
			markerOptions.icon = L.divIcon({
				html: html,
				className: 'ait-leaflet-marker-icon'

			});
		}

		var marker = L.marker(L.latLng(point.lat, point.lng), markerOptions);

		MAP.markersLayer.addLayer(marker);

		//hotfix
		// if marker doesn't specify enableInfoWindow parameter automatically consider it as enabled
		if (typeof point.enableInfoWindow === "undefined" || point.enableInfoWindow === true) {
			MAP.customInfoWindow(marker, point);
		}
		marker.on('click', function(e) {
			if (!marker._popup) {
				MAP.map.panTo(L.latLng(point.lat, point.lng));
				return;
			}

			var popupOffset = MAP.map._container.offsetHeight/2 - marker._popup._container.offsetHeight - marker._icon.querySelector('img').offsetHeight - 12; //  - marker wrapper
			var lat = point.lat;

			if (popupOffset < 0) {
				var project = MAP.map.project(marker._popup._latlng);
				project.y -= marker._popup._container.offsetHeight/2;
				lat = MAP.map.unproject(project).lat;
			}

			MAP.map.panTo(L.latLng(lat, point.lng));
		});

		return marker;
	},


	customInfoWindow: function(marker, point){
		//if marker is Geolocation position pin, do not create infobox
		if(point.type === undefined) return;

		var popupHtml = `<div class="infoBox"><div class="infobox-content">${ point.context }</div></div>`

		marker.bindPopup(popupHtml, {
			offset: [0, -50]
		});

		// TODO: do we really need to return popup? see multimarker custom infowindow in eventguide theme
		return null;
	},



	autoFit: function(){
		if (MAP.bounds.isValid()) {
			MAP.map.fitBounds(MAP.bounds, {
				maxZoom: MAP.mapOptions.zoom
			});
		} else {
			MAP.map.setView([MAP.mapOptions.center.lat, MAP.mapOptions.center.lng]);
		}
	},



	setGeolocation: function(){
		var lat,
		lon,
		tmp = [];
		window.location.search
		.substr(1)
		.split("&")
		.forEach(function (item) {
			tmp = item.split("=");
			if (tmp[0] === 'lat'){
				lat = decodeURIComponent(tmp[1]);
			}
			if (tmp[0] === 'lon'){
				lon = decodeURIComponent(tmp[1]);
			}
		});

		if(typeof lat != 'undefined' & typeof lon != 'undefined' && lat != '' && lon != '') {
			var pos = L.latLng(lat, lon);

			MAP.placeMarker({
				lat: lat,
				lng: lon,
				icon: ait.paths.img +'/pins/geoloc_pin.png',
			});
			MAP.map.setView(pos);
			if(MAP.params.radius === false) {
				MAP.map.setZoom(MAP.mapOptions.zoom);
			} else {
				MAP.map.setZoom(Math.round(14-Math.log(MAP.params.radius)/Math.LN2));
				var radiusOptions = {
					color: '#005BB7',
					opacity: 0.8,
					weight: 2,
					fillColor: '#008BB2',
					fillOpacity: 0.35,
					radius: MAP.params.radius * 1000,
				};
				L.circle(pos, radiusOptions).addTo(MAP.map);
			}
		} else if(navigator.geolocation) {
			// Try HTML5 geolocation
			navigator.geolocation.getCurrentPosition(function(position) {
				var pos = L.latLng(position.coords.latitude, position.coords.longitude);

				MAP.placeMarker({
					enableInfoWindow: false,
					lat: position.coords.latitude,
					lng: position.coords.longitude,
					icon: ait.paths.img +'/pins/geoloc_pin.png',
				});
				MAP.map.setView(pos);
				if(MAP.params.radius === false) {
					MAP.map.setZoom(MAP.mapOptions.zoom);
				} else {
					MAP.map.setZoom(Math.round(14-Math.log(MAP.params.radius)/Math.LN2));
					var radiusOptions = {
						color: '#005BB7',
						opacity: 0.8,
						weight: 2,
						fillColor: '#008BB2',
						fillOpacity: 0.35,
						radius: MAP.params.radius * 1000,
					};
					L.circle(pos, radiusOptions).addTo(MAP.map);
				}
			}, function() {
				MAP.handleNoGeolocation(true);
			});
		} else {
			// Browser doesn't support Geolocation
			MAP.handleNoGeolocation(false);
		}
	},



	handleNoGeolocation: function(errorFlag){
		var content = 'Geolocation failed';
		if (errorFlag) {
			if (typeof MAP.params.i18n.error_geolocation_failed !== 'undefined') {
				content = MAP.params.i18n.error_geolocation_failed;
			}
		} else {
			if (typeof MAP.params.i18n.error_geolocation_unsupported !== 'undefined') {
				content = MAP.params.i18n.error_geolocation_unsupported;
			}
		}

		MAP.map.setZoom(MAP.mapOptions.zoom);
		MAP.map.setView([MAP.mapOptions.center.lat, MAP.mapOptions.center.lng]);
		alert(content);
	},



	initClusterer: function(){
		// remove markers from map layer and place them into cluster layer
		MAP.map.removeLayer(MAP.markersLayer);

		MAP.clusterer.clearLayers();
		MAP.clusterer.addLayers(MAP.placedMarkers);
	},



	clear: function(){
		for (var i in MAP.placedMarkers) {
			var marker = MAP.placedMarkers[i];
			marker.setMap(null);
		}
		MAP.placedMarkers = [];
		MAP.clusterer.clearMarkers();
	},

});



$window.load(function(){
	MAP.initialize({$containerID}, {$markers}, {$options}, {$params} );
	//google.maps.event.addDomListener(window, 'load', MAP.initialize({$containerID}, {$markers}, {$options}, {$params} ));

});


})(jQuery, jQuery(window), jQuery(document), this);
</script>