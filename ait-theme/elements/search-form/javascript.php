<script id="{$htmlId}-script">
//jQuery(window).load(function(){
jQuery(document).ready(function(){
	{if $options->theme->general->progressivePageLoading}
		if(!isResponsive(1024)){
			jQuery("#{!$htmlId}-main").waypoint(function(){
				jQuery("#{!$htmlId}-main").addClass('load-finished');
			}, { triggerOnce: true, offset: "95%" });
		} else {
			jQuery("#{!$htmlId}-main").addClass('load-finished');
		}
	{else}
		jQuery("#{!$htmlId}-main").addClass('load-finished');
	{/if}

	/*jQuery('#{!$htmlId}').find('.radius-search').keyup(function(){
		var min = jQuery(this).data('min');
		var max = jQuery(this).data('max');
		var current = jQuery(this).val().replace(/[^0-9\.]/g,'');

		if (current > max){
			jQuery(this).val(max);
		} else {
			jQuery(this).val(current);
		}
	});*/

	var select2Settings = {
		dropdownAutoWidth : true
	};

	jQuery('#{!$htmlId}').find('select').select2(select2Settings).on("select2-close", function() {
		// fired to the original element when the dropdown closes
		jQuery('.select2-drop').removeClass('select2-drop-active');

		// replace all &nbsp;
		var regPattern = "&nbsp;";
		jQuery('#{!$htmlId} .category-search .select2-chosen').html(jQuery('#{!$htmlId} .category-search .select2-chosen').html().replace(new RegExp(regPattern, "g"), ''));
		if(jQuery('#{!$htmlId} .location-search .select2-chosen').length > 0) {
			jQuery('#{!$htmlId} .location-search .select2-chosen').html(jQuery('#{!$htmlId} .location-search .select2-chosen').html().replace(new RegExp(regPattern, "g"), ''));
		}

		jQuery('.select2-drop').removeClass('select-position-first').removeClass('select-position-last');
	});

	jQuery('#{!$htmlId}').find('select').select2(select2Settings).on("select2-loaded", function() {
		// fired to the original element when the dropdown closes
		jQuery('#{!$htmlId}').find('.select2-container').removeAttr('style');
	});

	jQuery('#{!$htmlId}').find('select').select2(select2Settings).on("select2-open", function() {
		var selectPosition = jQuery('#{!$htmlId}').find('.select2-dropdown-open').parent().attr('data-position');
		jQuery('.select2-drop').addClass('select-position-'+selectPosition);
	});

	if(isMobile()){
		jQuery('#{!$htmlId} .category-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				jQuery('#{!$htmlId}').find('.category-clear').addClass('clear-visible');
			}
		});
		jQuery('#{!$htmlId} .location-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				jQuery('#{!$htmlId}').find('.location-clear').addClass('clear-visible');
			}
		});

		jQuery('#{!$htmlId} .category-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				// add class
				jQuery('#{!$htmlId} .category-search-wrap').addClass('option-selected');
			}
		});
		jQuery('#{!$htmlId} .location-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				jQuery('#{!$htmlId} .location-search-wrap').addClass('option-selected');
			}
		});
	} else {
		jQuery('#{!$htmlId} .category-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				// add class
				jQuery('#{!$htmlId} .category-search-wrap').addClass('option-selected');
			}
		});
		jQuery('#{!$htmlId} .location-search-wrap').find('select').select2(select2Settings).on("select2-selecting", function(val, choice) {
			if(val != ""){
				jQuery('#{!$htmlId} .location-search-wrap').addClass('option-selected');
			}
		});

		jQuery('#{!$htmlId}').find('.category-search-wrap').hover(function(){
			if(jQuery(this).find('select').select2("val") != ""){
				jQuery(this).find('.category-clear').addClass('clear-visible');
			}
		},function(){
			if(jQuery(this).find('select').select2("val") != ""){
				jQuery(this).find('.category-clear').removeClass('clear-visible');
			}
		});

		jQuery('#{!$htmlId}').find('.location-search-wrap').hover(function(){
			if(jQuery(this).find('select').select2("val") != ""){
				jQuery(this).find('.location-clear').addClass('clear-visible');
			}
		},function(){
			if(jQuery(this).find('select').select2("val") != ""){
				jQuery(this).find('.location-clear').removeClass('clear-visible');
			}
		});
	}

	jQuery('#{!$htmlId}').find('.select2-chosen').each(function(){
		jQuery(this).html(jQuery(this).html().replace(new RegExp("&nbsp;", "g"), ''));
	});

	/*jQuery('#{!$htmlId}').find('form').submit(function(){
		var $radiusInput = jQuery(this).find('.radius-search');
		$radiusInput.attr('value', $radiusInput.val().length == 0 ? $radiusInput.attr('placeholder') : $radiusInput.val());
	});*/

	if(isMobile()){
		jQuery('#{!$htmlId}').find('.radius').on('click', function(){
			jQuery(this).find('.radius-clear').addClass('clear-visible');
		});
	} else {
		jQuery('#{!$htmlId}').find('.radius').hover(function(){
			jQuery(this).find('.radius-clear').addClass('clear-visible');
		},function(){
			jQuery(this).find('.radius-clear').removeClass('clear-visible');
		});
	}

	jQuery('#{!$htmlId}').find('.category-clear').click(function(){
		jQuery('#{!$htmlId}').find('.category-search-wrap select').select2("val", "");
		jQuery(this).removeClass('clear-visible');
		// remove class selected
		jQuery('#{!$htmlId} .category-search-wrap').removeClass('option-selected');
	});
	jQuery('#{!$htmlId}').find('.location-clear').click(function(){
		jQuery('#{!$htmlId}').find('.location-search-wrap select').select2("val", "");
		jQuery(this).removeClass('clear-visible');
		// remove class selected
		jQuery('#{!$htmlId} .location-search-wrap').removeClass('option-selected');
	});
	/*jQuery('#{!$htmlId}').find('.radius-clear').click(function(){
		jQuery(this).removeClass('clear-visible');
	});*/


	/* RADIUS SCRIPT */

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
	var coordinatesSet = false;
	if(typeof lat != 'undefined' & typeof lon != 'undefined') {
		coordinatesSet = true;
	}

	var $headerMap = jQuery("#{!$elements->unsortable[header-map]->getHtmlId()}-container");

	var $radiusContainer = jQuery('#{!$htmlId} .radius');
	var $radiusToggle = $radiusContainer.find('.radius-toggle');
	var $radiusDisplay = $radiusContainer.find('.radius-display');
	var $radiusPopup = $radiusContainer.find('.radius-popup-container');

	$radiusToggle.click(function(e, invoker){
		if (typeof invoker != 'undefined') {
			if(invoker.indexOf('advanced-search') > -1) {
				coordinatesSet = true;
			}
			if(invoker.indexOf('reset-geodata') > -1) {
				coordinatesSet = false;
			}
		}

		jQuery(this).removeClass('radius-input-visible').addClass('radius-input-hidden');
		$radiusContainer.find('input').each(function(){
			jQuery(this).removeAttr('disabled');
		});
		$radiusPopup.find('input').each(function(){
			jQuery(this).removeAttr('disabled');
		});
		$radiusDisplay.removeClass('radius-input-hidden').addClass('radius-input-visible');
		$radiusContainer.addClass('option-selected');

		if(typeof invoker == 'undefined' || invoker.indexOf('radius-already-selected') == -1) {
			openRadiusPopup($radiusPopup);
		}

		if(!coordinatesSet) {
			setGeoData();
		}

		$radiusDisplay.find('.radius-value').html($radiusPopup.find('input').val());
		$radiusPopup.find('.radius-value').html($radiusPopup.find('input').val());
	});

	$radiusDisplay.click(function(){
		openRadiusPopup($radiusPopup);
		if(!coordinatesSet) {
			setGeoData();
		}
	});
	$radiusDisplay.find('.radius-clear').click(function(e){
		e.stopPropagation();
		$radiusDisplay.removeClass('radius-input-visible').addClass('radius-input-hidden');
		$radiusContainer.removeClass('option-selected');
		$radiusContainer.find('input').each(function(){
			jQuery(this).attr('disabled', true);
		});
		$radiusPopup.find('input').each(function(){
			jQuery(this).attr('disabled', true);
		});
		$radiusPopup.find('.radius-popup-close').trigger('click');
		$radiusToggle.removeClass('radius-input-hidden').addClass('radius-input-visible');
		$radiusContainer.removeClass('radius-set');
	});
	$radiusPopup.find('.radius-popup-close').click(function(e){
		e.stopPropagation();
		closeRadiusPopup($radiusPopup);
	});
	$radiusPopup.find('input').change(function(){
		$radiusContainer.find('[name="rad"]').val(jQuery(this).val());
		$radiusDisplay.find('.radius-value').html(jQuery(this).val());
		$radiusPopup.find('.radius-value').html(jQuery(this).val());
	});

	{if $selectedRad}
		{if !empty($selectedLat) and !empty($selectedLon)}
			coordinatesSet = true;
		{/if}
		$radiusToggle.trigger('click', [['radius-already-selected']]);
	{/if}

	/* RADIUS SCRIPT */

	/* ABSOLUTE RADIUS POPUP */
	jQuery('body').append(jQuery('<div id="#{!$htmlId}-clone" class="elm-search-form radius-clone"></div>'));
	jQuery('.elm-search-form.radius-clone').append($radiusPopup);
	var formRadiusInput = jQuery('.elm-search-form.radius-clone [name="rad"]').clone();
	$radiusContainer.append(formRadiusInput);
	formRadiusInput.attr('type', 'hidden');

	jQuery(window).resize(function() {
		updateRadiusCloneDimensions($radiusPopup);
	});

	{if $type == 2}
	/* KEYWORD INPUT HACK */
	var $keywordContaier = jQuery('#{!$htmlId} #searchinput-text');
	var $keywordWidthHack = jQuery('#{!$htmlId} .search-input-width-hack');

	if($keywordContaier.val() != ""){
		$keywordWidthHack.html($keywordContaier.val());
	} else {
		$keywordWidthHack.html($keywordWidthHack.attr('data-defaulttext'));
	}
	$keywordContaier.width($keywordWidthHack.width());

	$keywordContaier.on('keyup', function(){
		if(jQuery(this).val() != ""){
			$keywordWidthHack.html(jQuery(this).val());
		} else {
			$keywordWidthHack.html($keywordWidthHack.attr('data-defaulttext'));
		}

		if($keywordWidthHack.width() <= 150){
			if(jQuery(this).val() != ""){
				$keywordContaier.width($keywordWidthHack.outerWidth(true));
			} else {
				$keywordContaier.width($keywordWidthHack.width());
			}
		}
	});
	/* KEYWORD INPUT HACK */
	{/if}

	{if $type == 3}
	jQuery('#{!$htmlId} .category-search-wrap .category-icon').on('click', function(){
		jQuery(this).parent().find('select').select2('open');
	});
	jQuery('#{!$htmlId} .location-search-wrap .location-icon').on('click', function(){
		jQuery(this).parent().find('select').select2('open');
	});
	{/if}
});

function setGeoData() {
	if(navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			//var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			jQuery("#latitude-search").attr('value', position.coords.latitude);
			jQuery("#longitude-search").attr('value', position.coords.longitude);
		});
	}
}

function openRadiusPopup(popup) {
	jQuery('body').addClass('radius-open');

	setTimeout(() => {
		updateRadiusCloneDimensions(popup);
		popup.parent().addClass('active');
		popup.removeClass('radius-input-hidden').addClass('radius-input-visible');
	}, 100);

}

function updateRadiusCloneDimensions(popup) {
	$radiusContainer = jQuery('#{!$htmlId} .radius');
	$radiusClone = popup.parent();
	var radiusContainerPosition = $radiusContainer.offset();
	var radiusContainerWidth = $radiusContainer.outerWidth() + 'px';
	var radiusContainerHeight = $radiusContainer.outerHeight() + 'px';
	$radiusClone.css(radiusContainerPosition);
	$radiusClone.css('width', radiusContainerWidth);
	$radiusClone.css('height', radiusContainerHeight);

	var offscreen = popupOffscreen(popup);
	if (popupOffscreen(popup) != 0) {
		$radiusClone.css('left', $radiusClone.offset().left + offscreen);
		popup.addClass('off-screen');
	} else {
		popup.removeClass('off-screen');
	}
}

function closeRadiusPopup(popup) {
	popup.removeClass('radius-input-visible').addClass('radius-input-hidden');
	popup.parent().removeClass('active').removeAttr('style');
	jQuery('body').removeClass('radius-open');
}

function popupOffscreen(popup) {
	var	offsetRight = jQuery(window).width() - (popup.offset().left + popup.outerWidth());
	var offsetLeft = popup.offset().left;
	return offsetLeft < 20 ? Math.abs(offsetLeft < 0 ? offsetLeft - 20 : 20 - offsetLeft)
		: offsetRight < 20 ? offsetRight - 20 : 0;
}

</script>
