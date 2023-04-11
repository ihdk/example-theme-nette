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

<div id="{$containerID}-container" class="google-map-container {if $el->option('mapLoadType') == "request" }on-request{/if} {if isset($classes)}{!$classes}{/if}">
	{if $el->option('mapLoadType') == "request"}
		<div class="request-map">
			{if $themeOptions->google->requestDescriptionText}
				<div class="request-map-description">
					{!$themeOptions->google->requestDescriptionText}
				</div>
			{/if}
			<div class="request-map-button">
				<a href="#" class="ait-sc-button simple">
				      <span class="container">
				            <span class="text">
				                  <span class="title">{if $themeOptions->google->requestButtonText}{!$themeOptions->google->requestButtonText}{else}{__ 'Show the map'}{/if}</span>
				            </span>
				      </span>
				</a>
			</div>
		</div>
	{/if}
</div>

<script>
(function($, $window, $document, globals){
"use strict";


var MAP = MAP || {};

MAP = $.extend(MAP, {
	provider: 'google',
	map: null,
	markers: [],
	placedMarkers: [],
	bounds:  null,
	locations: [],
	currentInfoWindow: null,
	clusterer: null,
	lastMarkerID: 0,
	// multiInfoBox: '<div class"multiInfoBox"></div>',
	multimarker: [],
	containerID: '',
	panorama: null,
	ibTimeout: null,

	mapOptions: {
		center: { lat: 0, lng: 0},
		zoom: 3,
	    streetViewControl: true,
		draggable: true,
		scrollwheel: false,
        fullscreenControl: false
	},

	params: {
		name: '',
		enableAutoFit: false,
		enableClustering: false,
		enableGeolocation: false,
		customIB: true,
		externalInfoWindow: true,
		streetview: false,
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
		MAP.clusterer   = new MarkerClusterer();
		MAP.bounds      = new google.maps.LatLngBounds();
		MAP.containerID = containerID;
		MAP.setCustomOptions();



		var mapContainer = $("#" + containerID + "-container").get(0);
		MAP.mapContainer = mapContainer;

		//decide if standard map or streetview will be displayed

		if (MAP.params.streetview) {
			var pov = {
				heading: parseInt(MAP.params.swheading),
				pitch: parseInt(MAP.params.swpitch),
				zoom: parseInt(MAP.params.swzoom),
			};
			MAP.map = new google.maps.StreetViewPanorama(mapContainer, MAP.mapOptions);
			MAP.map.setPosition(new google.maps.LatLng(MAP.params.address.latitude, MAP.params.address.longitude));
			MAP.map.setPov(pov);
		}else{
			MAP.map = new google.maps.Map(mapContainer, MAP.mapOptions);
		}

		// create global variable (if doesn't exist)
		// make sure you are using unique name - there might be another map already stored
		// store only map with defined name parameter
		globals.gm_authFailure = MAP.gm_authFailure;
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

		/*if (MAP.params.streetview) {
			MAP.enableStreetview();
		}*/

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
			var location = new google.maps.LatLng(marker.lat, marker.lng);

			MAP.bounds.extend(location);
			MAP.locations.push(location);
			var newMarker = MAP.placeMarker(marker);
			MAP.placedMarkers.push(newMarker);


		}
	},



	placeMarker: function(marker){
		if (marker.icon) {
			var icon = {
				url: marker.icon,
			};
		} else {
			var icon = "";
		}
		// title is commented because it caused tooltip problems on mouse hover
		var marker = new google.maps.Marker({
			position:  new google.maps.LatLng(marker.lat, marker.lng),
			map: MAP.map,
			icon: icon,
			title: '',
			// title: marker.title,
			context: marker.context,
			type: marker.type,
			id: marker.id,
			data: marker.data,
			enableInfoWindow: marker.enableInfoWindow
		});

		//hotfix
		// if marker doesn't specify enableInfoWindow parameter automatically consider it as enabled
		if (typeof marker.enableInfoWindow === "undefined" || marker.enableInfoWindow === true) {
			MAP.customInfoWindow(marker);
		}
		marker.addListener('click', function() {
			//do not do panTo() function if streetview
			if( MAP.params.streetview ) return;

			if (!MAP.mapContainer) {
				MAP.map.panTo(marker.getPosition());
				return;
			}

			setTimeout(() => {
				var popupOffset = MAP.mapContainer.offsetHeight/2 - MAP.currentInfoWindow.content_.offsetHeight - marker.icon.size.height - 19; // marker gap
				var lat = marker.position.lat();

				if (popupOffset < 0) {
					var project = MAP.latLngToPoint(marker.position);
					project.y -= MAP.currentInfoWindow.content_.offsetHeight/2;
					lat = MAP.pointToLatLng(project).lat();
				}

				MAP.map.panTo({"lat": lat, "lng": marker.position.lng()});
			}, 0);
		});

		return marker;
	},



	latLngToPoint: function(latLng) {
		var topRight = MAP.map.getProjection().fromLatLngToPoint(MAP.map.getBounds().getNorthEast());
		var bottomLeft = MAP.map.getProjection().fromLatLngToPoint(MAP.map.getBounds().getSouthWest());
		var scale = Math.pow(2, MAP.map.getZoom());
		var worldPoint = MAP.map.getProjection().fromLatLngToPoint(latLng);
		return new google.maps.Point((worldPoint.x - bottomLeft.x) * scale, (worldPoint.y - topRight.y) * scale);
	},



	pointToLatLng: function(point) {
		var topRight = MAP.map.getProjection().fromLatLngToPoint(MAP.map.getBounds().getNorthEast());
		var bottomLeft = MAP.map.getProjection().fromLatLngToPoint(MAP.map.getBounds().getSouthWest());
		var scale = Math.pow(2, MAP.map.getZoom());
		var worldPoint = new google.maps.Point(point.x / scale + bottomLeft.x, point.y / scale + topRight.y);
		return MAP.map.getProjection().fromPointToLatLng(worldPoint);
	},



	customInfoWindow: function(marker){
		//if marker is Geolocation position pin, do not create infobox
		if(marker.type === undefined) return;

		var boxText = document.createElement("div");
		boxText.className = 'infobox-content';
		var content = marker.context;
		boxText.innerHTML = content;

		var myOptions = {
			content: boxText,
			disableAutoPan: false,
			closeBoxURL: ait.paths.img + "/infobox_close.png",
			pixelOffset: new google.maps.Size(-145, -200),
		};

		var ib = new InfoBox(myOptions);

		marker.addListener('click', function() {
			if (MAP.currentInfoWindow) {
				MAP.currentInfoWindow.close();
			}

			MAP.currentInfoWindow = ib;
			ib.open(MAP.map, marker);
		});

		google.maps.event.addListener(ib, 'domready', function() {
			var content = ib.getContent()
			jQuery(content).find('.review-stars-container .review-stars').raty({
				font: true,
				readOnly:true,
				halfShow:true,
				starHalf:'fa-star-half-o',
				starOff:'fa-star-o',
				starOn:'fa-star',
				score: function() {
					return jQuery(this).attr('data-score');
				},
			});
		})

		return ib;
	},



	autoFit: function(){
		//do not do autofit for streetview map
		if( MAP.params.streetview ) return;

		if (!MAP.bounds.isEmpty()) {
			MAP.map.fitBounds(MAP.bounds);
	    	MAP.map.panToBounds(MAP.bounds);
			var listener = google.maps.event.addListener(MAP.map, "idle", function() {
				if (MAP.map.getZoom() > MAP.mapOptions.zoom) {
					MAP.map.setZoom(MAP.mapOptions.zoom);
				}
				google.maps.event.removeListener(listener);
			});
		} else {
			MAP.map.setCenter(MAP.mapOptions.center);
		}
	},



	setGeolocation: function(){
		//do not do geolocation for streetview map
		if( MAP.params.streetview ) return;

		var lat,
		lon,
		tmp = [];
		window.location.search
		//.replace ( "?", "" )
		// this is better, there might be a question mark inside
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
			var pos = new google.maps.LatLng(lat, lon);

			MAP.placeMarker({
				lat: lat,
				lng: lon,
				icon: ait.paths.img +'/pins/geoloc_pin.png',
			});
			MAP.map.setCenter(pos);
			if(MAP.params.radius === false) {
				MAP.map.setZoom(MAP.mapOptions.zoom);
			} else {
				MAP.map.setZoom(Math.round(14-Math.log(MAP.params.radius)/Math.LN2));
			}
			var radiusOptions = {
				strokeColor: '#005BB7',
				strokeOpacity: 0.8,
				strokeWeight: 2,
				fillColor: '#008BB2',
				fillOpacity: 0.35,
				map: MAP.map,
				center: pos,
				radius: MAP.params.radius * 1000,
			};
			var radiusCircle = new google.maps.Circle(radiusOptions);
		} else if(navigator.geolocation) {
			// Try HTML5 geolocation
			navigator.geolocation.getCurrentPosition(function(position) {
				var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

				MAP.placeMarker({
					enableInfoWindow: false,
					lat: position.coords.latitude,
					lng: position.coords.longitude,
					icon: ait.paths.img +'/pins/geoloc_pin.png',
				});
				MAP.map.setCenter(pos);
				if(MAP.params.radius === false) {
					MAP.map.setZoom(MAP.mapOptions.zoom);
				} else {
					MAP.map.setZoom(Math.round(14-Math.log(MAP.params.radius)/Math.LN2));
				}
				var radiusOptions = {
					strokeColor: '#005BB7',
					strokeOpacity: 0.8,
					strokeWeight: 2,
					fillColor: '#008BB2',
					fillOpacity: 0.35,
					map: MAP.map,
					center: pos,
					radius: MAP.params.radius * 1000,
				};
				var radiusCircle = new google.maps.Circle(radiusOptions);
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
		MAP.map.setCenter(MAP.mapOptions.center);
		alert(content);
	},



	gm_authFailure: function(){
		var apiBanner = document.createElement('div');
		var a = document.createElement('a');
		var linkText = document.createTextNode("Read more");
		a.appendChild(linkText);
		a.title = "Read more";
		a.href = "https://www.ait-themes.club/knowledge-base/google-maps-api-error/";
		a.target = "_blank";

		apiBanner.className = "alert alert-info";
		var bannerText = document.createTextNode("Please check Google API key settings");
		apiBanner.appendChild(bannerText);
		apiBanner.appendChild(document.createElement('br'));
		apiBanner.appendChild(a);

		$(MAP.mapContainer).html(apiBanner);
	},



	initClusterer: function(){
		//do not use clusterer for streetview map
		if( MAP.params.streetview ) return;

		var mcOptions = {
			gridSize: 50,
			enableRetinaIcons: true,
			ignoreHidden: true,
			styles: [{
				url: ait.paths.img +'/pins/clusters/cluster1.png',
				text: '+',
				height: 50,
				width: 50,
				// anchor: [3, 0],
				textColor: '#666',
				textSize: 10
				// text: '<i class"fa fa-times"></i>'
				}, {
				url: ait.paths.img +'/pins/clusters/cluster2.png',
				height: 60,
				width: 60,
				// anchor: [6, 0],
				text: '+',
				textColor: '#666',
				textSize: 11
				// text: '<i class"fa fa-times"></i>',
				}, {
				url: ait.paths.img +'/pins/clusters/cluster3.png',
				text: '+',
				width: 66,
				height: 66,
				// anchor: [8, 0],
				textColor: '#666',
				textSize: 12
			}]
		};

		if (typeof MAP.params.clusterRadius !== "undefined") {
			mcOptions.gridSize = MAP.params.clusterRadius;
		}
		MAP.clusterer.clearMarkers();
		var mc = new MarkerClusterer(MAP.map, MAP.placedMarkers, mcOptions);
		mc.setCalculator(function(markers) {
			var count = markers.length;
			for (var i = markers.length - 1; i >= 0; i--) {
				if (markers[i].isMulti) {
					count = count + markers[i].count -1;
				}
				// markers[i]
			};
			var index = 0;
			var dv = count;
			while (dv !== 0) {
				dv = parseInt(dv / 10, 10);
				index++;
			}

			index = Math.min(index);
			return {
			text: count,
			index: index
			};
		});
		MAP.clusterer = mc;
	},



	placeMultimarker: function(position, type, context1, context2, id1, id2, title1, title2){
		var $multiInfoBox = jQuery('<div class="multiInfoBox"><div class="infobox-select"><select></select></div>');

		$multiInfoBox.append(context1);
		$multiInfoBox.append(context2);
		var option1 = jQuery('<option value='+id1+'>'+title1+'</option>');
		var option2 = jQuery('<option value='+id2+'>'+title2+'</option>');
		$multiInfoBox.find('select').append(option1);
		$multiInfoBox.find('select').append(option2);


		var context = $multiInfoBox.wrap('<p/>').parent().html();
		var icon = ait.paths.img + "/pins/multi_pin.png";
		var marker = new google.maps.Marker({
			position:  position,
			map: MAP.map,
			icon: icon,
			// title: marker.title,
			context: context,
			isMulti: true,
			type: type,
			count: 2,
		});

		google.maps.event.addListener(marker, 'click', function(event) {
			if (MAP.currentInfoWindow) {
				MAP.currentInfoWindow.close();
			}

			MAP.map.panTo(marker.getPosition());
			MAP.currentInfoWindow = MAP.customInfoWindow(marker);

		});



		return marker;
	},



	appendToMultimarker: function(index, context, id, title){

		var $multiInfoBox = jQuery.parseHTML(MAP.placedMarkers[index].context);
		$multiInfoBox = jQuery($multiInfoBox).append(context);
		var $select = $multiInfoBox.find('select');
		var option = jQuery('<option value="'+id+'">'+title+'</option>');
		$select.append(option);
		var result = $multiInfoBox.wrap('<p/>').parent().html();
		MAP.placedMarkers[index].context = result;
		MAP.placedMarkers[index].count ++;
	},


	setCustomOptions: function(){
		if (typeof MAP.params.typeId !== "undefined") {
			MAP.mapOptions.mapTypeId = google.maps.MapTypeId[MAP.params.typeId];
		}

		MAP.mapOptions.mapTypeControlOptions = {
	 		position: google.maps.ControlPosition.LEFT_BOTTOM,
	 		style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
	 	};

		MAP.mapOptions.streetViewControlOptions = {
	 		position: google.maps.ControlPosition.RIGHT_BOTTOM,
	 	};

	 	MAP.mapOptions.zoomControlOptions = {
	 		position: google.maps.ControlPosition.RIGHT_BOTTOM,
	 	};
	},



	/*enableStreetview: function(){

		MAP.panorama = MAP.map.getStreetView();
		MAP.panorama.setPosition(new google.maps.LatLng(MAP.params.address.latitude, MAP.params.address.longitude));

		var pov = {
			heading: parseInt(MAP.params.swheading),
			pitch: parseInt(MAP.params.swpitch),
			zoom: parseInt(MAP.params.swzoom),
		};
		MAP.panorama.setPov(pov);
		MAP.panorama.setVisible(true);
	},*/


	clear: function(){
		for (var i in MAP.placedMarkers) {
			var marker = MAP.placedMarkers[i];
			marker.setMap(null);
		}
		MAP.placedMarkers = [];
		MAP.locations = [];
		MAP.clusterer.clearMarkers();
	},

});



$window.load(function(){

	{if $el->option('mapLoadType') == "request" }
		jQuery('#{!$containerID}-container .request-map-button .ait-sc-button, .search-form-type-3 .header-search-wrap .close-search-form-request-map').on('click', function(e){
			e.preventDefault();
			MAP.initialize({$containerID}, {$markers}, {$options}, {$params} );
		});
	{else}
		google.maps.event.addDomListener(window, 'load', MAP.initialize({$containerID}, {$markers}, {$options}, {$params} ));
	{/if}

});


})(jQuery, jQuery(window), jQuery(document), this);
</script>