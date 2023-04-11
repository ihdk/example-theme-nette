<script id="{$htmlId}-container-script">

(function($, $window, $document, globals){
"use strict";

$window.load(function(){
	{var $searchFormEnabled = $elements->unsortable['search-form']->options['@display'] }
	{var $searchFormType = $elements->unsortable['search-form']->options['type'] }
	{var $searchFormCloseButton = false}
	{if $searchFormEnabled == "1" && $searchFormType == "3"}
		{var $searchFormCloseButton = true}
	{/if}
	{if $searchFormCloseButton}
		if(jQuery(".search-form-type-3 .header-search-wrap .close-search-form-request-map").length){
			//enabled Button Search form type, remove form and show to map
			jQuery(".search-form-type-3 .header-search-wrap .close-search-form-request-map").on('click', function(){
				jQuery(".search-form-type-3 .header-search-wrap .elm-search-form-main").fadeOut();
			});
		}
	{/if}

	{if $el->option('mapLoadType') == "request" && $options->theme->maps->provider == 'google' }
		jQuery("#{!$htmlId} .google-map-container .request-map-button .ait-sc-button, .search-form-type-3 .header-search-wrap .close-search-form-request-map").on('click', function(){
			showHeaderMap();
		});
	{else}
		showHeaderMap();
	{/if}

	var isProgressbar = false;

	function showHeaderMap(){

		addHeaderMapControls();

		if (Modernizr.touchevents || Modernizr.pointerevents) {
			// disable the panorama on mobile
			if(globals.globalMaps.headerMap.panorama != null){
				// superhack waiting for content
				var headerMapPanoEvent = setInterval(function(){
					// we need second div because the first is the google map itself
					// if(jQuery("#{!$htmlId} .google-map-container").children('div').length > 1){ // old condition
					// this is better condition to check for button on streetview
					if(jQuery("#{!$htmlId} .draggable-toggle-button").length > 1){
						jQuery("#{!$htmlId} .google-map-container div:last-child").find('.draggable-toggle-button').parent().parent().find('div:first').css({'pointer-events': 'none'});
						clearInterval(headerMapPanoEvent);
					}
				}, 100);
			}
		}

		if (globals.globalMaps.headerMap.provider == 'google') {
			globals.globalMaps.headerMap.map.addListener('tilesloaded', function(){
				jQuery("#{!$htmlId} .google-map-container .gm-style").children('div').css({'touch-action': ''});
			});
		}

		var postType = 'ait-item';

		var requestData				  = {};
		requestData['action']       	  = 'get-items:getHeaderMapMarkers';
		requestData['type']         	  = 'headerMap';
		requestData['pageType']     	  = {$pageType};
		requestData['postType']     	  = postType;
		requestData['globalQueryVars']   = {$globalQueryVars};
		requestData['query-data']   	  = {$__ait_query_data};
		requestData['lang']         	  = {AitLangs::getCurrentLanguageCode()};
		requestData['is_post_preview'] = {is_preview()};
		{if isset($searchQuery)}
			requestData['search-params'] = {$searchQuery};
		{/if}

		{if $options->theme->items->sortingEnableMapPagination}
			requestData['ignorePagination'] = false;
		{else}
			requestData['ignorePagination']  = true;
		{/if}

		{if $el->option('infoboxEnableTelephoneNumbers')}
			requestData['enableTel']  = true;
		{/if}
		requestData['query-data'].ajax = {
			limit: 100,
			offset: 0
		};

		isProgressbar = false;

		// first initial load
		getHeaderMapMarkers(requestData);
	}


	function getHeaderMapMarkers(request_data) {

		ait.ajax.post('get-items:getHeaderMapMarkers', request_data).done(function(data){
			if(data.success == true){
				addMapPins(data.data.raw_data.markers);
				var willContinue = false;
				if ((data.data.raw_data.post_count + request_data['query-data'].ajax.offset) < data.data.raw_data.found_posts) {
					willContinue = true;
				} else {
					willContinue = false;
				}

				// will continue
				// response from first request - we can add progressbar
				if (willContinue && request_data['query-data'].ajax.offset == 0) {
					addProgressBar(data.data.raw_data.found_posts);
					isProgressbar = true;
				}

				var newOffset = request_data['query-data'].ajax.offset + data.data.raw_data.post_count;

				if (isProgressbar) {
					updateProgressBar(newOffset);
				}

				if(willContinue){
					request_data['query-data'].ajax.offset = newOffset;
					getHeaderMapMarkers(request_data);
				} else {
					fitMap();
				}
			} else {
				console.log("not success");
			}
		}).fail(function(){
			console.log("fail");
		});
	}

	function addMapPins(markers){
		var mapObject = globals.globalMaps.headerMap;
		mapObject.markers = markers
		mapObject.initMarkers(mapObject.markers);
		var mapObject = globals.globalMaps.headerMap;
		if ( mapObject.params.enableGeolocation ) {
			// mapObject.setGeolocation();
		} else if( mapObject.params.enableAutoFit ) {
			mapObject.autoFit();
		}
		if ( mapObject.params.enableClustering) {
			mapObject.initClusterer();
		};
	}

	function fitMap(){

		var mapObject = globals.globalMaps.headerMap;
		if ( mapObject.params.enableGeolocation ) {
			// mapObject.setGeolocation();
		} else if( mapObject.params.enableAutoFit ) {
			mapObject.autoFit();
		}
		if ( mapObject.params.enableClustering) {
			mapObject.initClusterer();
		};

	}

	function addProgressBar(max) {
		var progressBar = document.createElement('div');
		progressBar.className = 'ait-loader';
		progressBar.dataset.max = max;
		progressBar.dataset.current = 0;

		var loaderBar = document.createElement('div');
		loaderBar.className = 'loader-bar';

		progressBar.appendChild(loaderBar);


		var mapContainer = document.getElementById(globals.globalMaps.headerMap.containerID);
		mapContainer.appendChild(progressBar);
	}

	function updateProgressBar(count) {
		var mapContainer = document.getElementById(globals.globalMaps.headerMap.containerID);

		var loader = mapContainer.getElementsByClassName("ait-loader")[0];
		var max = loader.dataset.max;
		var progress = 100 / max * count;

		loader.dataset.current = count;
		// loader.getElementsByClassName('loader-bar')[0].style.width = (progress + '%');
		$(loader).find('.loader-bar').width(progress + '%');

		if (progress == 100) {
			$(loader).addClass('loader-hidden');
		}
	}

	function addHeaderMapControls() {
		if (globals.globalMaps.headerMap.provider && globals.globalMaps.headerMap.provider != 'google') {
			return;
		}
		var map = globals.globalMaps.headerMap.map;
		var panorama = globals.globalMaps.headerMap.panorama;
		if (Modernizr.touchevents || Modernizr.pointerevents) {
			var disableControlDiv = document.createElement('div');
			var disableControl = new DisableHeaderControl(disableControlDiv, map);
			map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(disableControlDiv);

			if(panorama != null){
				var disableStreetViewDiv = document.createElement('div');
				var disableStreetViewControl = new DisableHeaderStreetViewControl(disableStreetViewDiv);
				panorama.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(disableStreetViewDiv);
			}
		}
	}



	function isAdvancedSearch() {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === "a") {
				return true;
			}
		}
		return false;
	}

	/**
	 * The DisableControl adds a control to the map.
	 * This constructor takes the control DIV as an argument.
	 * @constructor
	 */
	function DisableHeaderControl(controlDiv, map) {
		var containerID = jQuery("#{!$htmlId} .google-map-container").attr('id');
		var disableButton = document.createElement('div');
		disableButton.className = "draggable-toggle-button";
		jQuery(disableButton).html('<i class="fa fa-lock"></i>');

		controlDiv.appendChild(disableButton);

		jQuery(this).removeClass('active').html('<i class="fa fa-lock"></i>');
		map.setOptions({ draggable : false });

		google.maps.event.addDomListener(disableButton, 'click', function(e) {
			if(jQuery(this).hasClass('active')){
				jQuery(this).removeClass('active').html('<i class="fa fa-lock"></i>');
				map.setOptions({ draggable : false });
			} else {
				jQuery(this).addClass('active').html('<i class="fa fa-unlock"></i>');
				map.setOptions({ draggable : true });
			}
		});
	}

	function DisableHeaderStreetViewControl(controlDiv){
		var containerID = jQuery("#{!$htmlId} .google-map-container").attr('id');
		var disableButton = document.createElement('div');
		disableButton.className = "draggable-toggle-button";
		jQuery(disableButton).html('<i class="fa fa-lock"></i>');

		controlDiv.appendChild(disableButton);

		jQuery(this).removeClass('active').html('<i class="fa fa-lock"></i>');

		google.maps.event.addDomListener(disableButton, 'click', function(e) {
			if(jQuery(this).hasClass('active')){
				jQuery(this).removeClass('active').html('<i class="fa fa-lock"></i>');
				if(globals.globalMaps.headerMap.panorama != null){
					// pano hack
					jQuery(this).parent().parent().find('div:first').css({'pointer-events': 'none'});
				}
			} else {
				jQuery(this).addClass('active').html('<i class="fa fa-unlock"></i>');
				if(globals.globalMaps.headerMap.panorama != null){
					// pano hack
					jQuery(this).parent().parent().find('div:first').css({'pointer-events': ''});
				}
			}
		});
	}

});

})(jQuery, jQuery(window), jQuery(document), this);

</script>