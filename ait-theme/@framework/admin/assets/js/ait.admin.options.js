

ait.admin.options = ait.admin.options || {};


(function($, $window, $document, undefined){

	"use strict";

	var $context;
	var isMediaFrame = false;
	var $pageSelectContainer = $("#ait-page-options-selection");
	var $pageSelect = $pageSelectContainer.find('select');
	var $pageImportSelectContainer = $("#ait-page-options-import-selection");
	var $pageImportSelect = $pageImportSelectContainer.find('select');
	var $stickToTopHeader = $('#stick-to-top');
	var currentPageSelectIndex = $pageSelect.val();

	if($('body').find('#ait-shortcodes-options').length){
		isMediaFrame = true;
		$context = $('#ait-shortcodes-options');
	}else{
		$context = $('#wpbody .wrap');
		if($context.length < 1){
			$context = $('#wpbody .block-editor');
		}
	}



	/**
	 * ait.admin.options.Data
	 *
	 * Handle data manipulation from the "Theme Options" page
	 */
	var optionsData = ait.admin.options.Data = {



		save: function(e)
		{
			ait.admin.publish('ait.options.save', ['working']);
			try{
				$.each(tinyMCE.editors, function(i, ed){
					if(!ed.isHidden())
						ed.save();
				});
			}catch(exc){ }

			if(e.target.className === "ait-save-plugin-options"){
				var action = 'savePluginOptions';
			}else{
				var action = 'saveThemeOptions';
			}

			ait.admin.ajax.post(action, $('#ait-options-form').serialize(), function(response){
				if(response.success)
					ait.admin.publish('ait.options.save', ['done']);
				else
					ait.admin.publish('ait.options.save', ['error', response.data]);
			});
		},



		deleteLocal: function(e)
		{
			e.preventDefault();
			if(confirm(ait.admin.l10n.confirm.removeCustomOptions)){
				var $this = $(this);
				var o = $this.data('ait-delete-local-options');
				var href = $this.attr('href');

				ait.admin.ajax.post('deleteLocalOptions', {nonce: o.nonce, oid: o.oid}, function(response){
					window.location.href = response.data.url;
				});
			}
		},



		reset: function(data)
		{
			ait.admin.publish('ait.options.reset', ['working', data]);

			if(data.what == 'all'){
				ait.admin.ajax.post('resetAllOptions', {nonce: data.nonce}, function(response){
					ait.admin.publish('ait.options.reset', ['done', data, response]);
				});

			}else if(data.what == 'pages-options'){
				ait.admin.ajax.post('resetGlobalPagesOptions', {nonce: data.nonce}, function(response){
					ait.admin.publish('ait.options.reset', ['done', data, response]);
				});

			}else if(data.what == 'theme-options'){
				ait.admin.ajax.post('resetThemeOptions', {nonce: data.nonce}, function(response){
					ait.admin.publish('ait.options.reset', ['done', data, response]);
				});

			}
		},



		resetGroup: function(data)
		{
			ait.admin.publish('ait.options.reset.group', ['working', data]);

			if('group' in data && data.what == 'group'){

				var d = jQuery.extend(true, {}, data);
				delete d.confirm;
				delete d.$indicator;
				delete d.what;

				ait.admin.ajax.post('resetOptionsGroup', d, function(response){
					ait.admin.publish('ait.options.reset.group', ['done', data, response]);
				});
			}
		},



		importGlobals: function(data)
		{
			ait.admin.publish('ait.options.importGlobals', ['working', data]);

			if('group' in data && data.what == 'group'){
				var d = jQuery.extend(true, {}, data);
				delete d.confirm;
				delete d.$indicator;
				delete d.what;

				ait.admin.ajax.post('importGlobalOptions', d, function(response){
					ait.admin.publish('ait.options.importGlobals', ['done', data, response]);
				});
			}
		}

	};



	/**
	 * ait.admin.options.Ui
	 *
	 * Binds events and improves basic inputs of the options types
	 */
	var ui = ait.admin.options.Ui = {


		$stickToTopHeader: $stickToTopHeader,
		$availableElements: $("#ait-available-elements"),
		stickToTopSidebarTopMargin: ($stickToTopHeader.length) ? parseInt($stickToTopHeader.css('margin-top')) : 0,
		$scrollToTopButton: $(".ait-scroll-to-top"),
		wpAdminBarHeight: ($("#wpadminbar").length) ? parseInt($('#wpadminbar').height()) : 28,
		pageBottomOffset: 0,



		init: function()
		{
			/* PLUGINS ADMIN HOTFIX BEFORE USER UPDATES */
			$('.ait-admin-page:not(.ait-pages-options-page):not(.ait-default-layout-page):not(.ait-options-layout)')
				.addClass('ait-options-layout')
				.prepend('<div class="notice notice-warning"><p>Please update your all AIT plugins to be fully compatible with redesigned AIT admin pages and the Page Builder.</p><p><a class="button button-primary" href="plugins.php">Go to Plugins page</a></p></div>');
			/* PLUGINS ADMIN HOTFIX BEFORE USER UPDATES */

			if ($pageSelect.length) {
				ui.initPageSelect();
				ui.initPageImportSelect();
			}

			ui.bindGlobalEvents();
			ui.bindEvents($context.not('#ait-available-elements-contents'));
			// ui.preventPageScrollingWhenScrollingAvailableElements();
			ui.initAitSimpleTabs();
			ui.enablePageToolsToggle();
			ui.enableSaveButton();

			ui.enhancedInputs(
				$context
					.find('.ait-options-content, .ait-options-mainmenu, .media-frame-content, #metaboxes, #advanced-sortables, #normal-sortables')
					.not('#ait-page-options-selection')
			);

			ui.updateUploadFileInput();

			ui.switchableSections($context);

			ui.pageBottomOffset = ui.calculatePageBottomOffset();

			ui.initGotoPageBuilderButton();

			ui.scrollToTop(ui.$scrollToTopButton);

			if (!isMediaFrame) {
				ui.stickHeader();
				ui.stickElements();

				var id = '#ait-' + ait.admin.currentPage;
				new ait.admin.Tabs($(id + '-tabs'), $(id + '-panels'), 'ait-admin-' + ait.admin.currentPage + '-page');
			}
		},



		items: {
			$saveIndicator: $('#action-indicator-save'),
			$resetIndicator: $('#action-indicator-reset')
		},



		bindGlobalEvents: function()
		{
			ait.admin.subscribe('ait.options.save', ui.onSave);
			ait.admin.subscribe('ait.options.reset', ui.onReset);
			ait.admin.subscribe('ait.options.reset.group', ui.onResetGroup);
			ait.admin.subscribe('ait.options.importGlobals', ui.onImportGlobals);

			$('#action-delete-local-options a').on('click', {href: $(this).attr('href')}, optionsData.deleteLocal);
		},



		initGotoPageBuilderButton: function()
		{
			var $button = $('#ait-goto-page-builder-button');
			var $title = $('#post input#title');

			var $note = $('<span>', {
				'class': 'ait-empty-title-note',
				'text': ait.admin.utils.getDataAttr($button, 'empty-title-note')
			}).css({
				'display': 'none',
				'color': '#d54e21',
				'font-weight': 'bold',
				'line-height': '26px',
				'padding-left': '15px',
			});

			$button.after($note);

			$button.on('click', function(e){
				e.preventDefault();

				if($title.val() == ''){
					$note.fadeIn();
					$title.focus();
				}else{
					$note.fadeOut();
					$button.after($('<input>', {'type': 'hidden', 'name': 'ait-redirect-to-page-builder', 'value': 'true'}));
					$('#post input#publish').trigger('click.edit-post');
				}
			});
		},



		bindEvents: function($currentContext)
		{
			if (!isMediaFrame) {
				$('.ait-save-theme-options', $currentContext).on('click', ui.save);
				$('.ait-save-plugin-options', $currentContext).on('click', ui.save);
				$('.ait-reset-options', $currentContext).on('click', ui.reset);
				$('.ait-reset-group-options', $currentContext).on('click', ui.resetGroup);
				$('.ait-import-global-options', $currentContext).on('click', ui.importGlobals);
			}

			$currentContext.on('click', '.ait-image-select', ui.selectImage);
			$currentContext.on('change', '.ait-image-value-fake', ui.changeImageInput).trigger('change');

			ui.initDynamicForms($currentContext);

			$currentContext.on('click', '.insert-media', function() {
			   wpActiveEditor = $(this).data('editor');
			});
		},



		initDynamicForms: function($currentContext)
		{
			var $dynamicForms = $('.ait-clone-controls:not(#ait-available-elements-contents .ait-clone-controls)', $currentContext);

			if ($dynamicForms.length) {
				$dynamicForms.each(function() {
					var $pregeneratedItems = $(this).find('.ait-pregenerated-clone-item');
					var pregeneratedItemsIds = $.map($pregeneratedItems, function(form) {
						return form.id;
					});

					var $this = $(this);
					var confirmMessage = $this.data('confirm-message');

					var initializing = true;

				   $this.sheepIt({
						indexFormat:'%index%',
						continuousIndex: false,
						separator: '',
						allowRemoveLast: false,
						allowRemoveCurrent: true,
						allowRemoveAll: $this.data('allow-remove-all'),
						allowAdd: true,

						afterAdd: function(source, newForm)
						{
							if (!initializing) {
								ui.enhancedInputs(newForm);
								ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
							}
						},
						afterRemoveCurrent: ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground,

						removeLastConfirmation: true,
						removeCurrentConfirmation: true,
						removeAllConfirmation: true,
						removeLastConfirmationMsg: confirmMessage,
						removeCurrentConfirmationMsg: confirmMessage,
						removeAllConfirmationMsg: confirmMessage,

						maxFormsCount: $this.data('max-forms'),
						minFormsCount: $this.data('min-forms'),
						iniFormsCount: 0,
						pregeneratedForms: pregeneratedItemsIds
					});


					var sorting = false;


					$this.sortable({
						handle: '.form-input-handler.clone-sort',
						axis: 'y',
						tolerance: 'pointer',
						distance: 15,
						scrollSensitivity: 50,
						start: function(e, jUi) {
							sorting = true;

							$(jUi.helper).css('height', $(jUi.helper).find('.form-input-handler').css('height'));
							$('.form-input-content').hide();
							$this.sortable('refreshPositions');
							$this.sortable('refresh');
							$window.scrollTop($(jUi.helper).offset().top - $window.height() / 2);
							ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
						},
						stop: function(e, jUi) {
							var $item =  $(jUi.item);
							$item.attr('style', '');

							// replace indexes to reflect new cloned items order
							var $items = $this.find('.ait-clone-item');
							$.each($items, function(i,item) {
								var $inputs = $(item).find('*[name]');
								$.each($inputs, function() {
									$(this).attr('name', $(this).attr('name').replace(/\[\d+\]/, '[' + i + ']'));
								});
							});

							$item.find('.form-input-content').slideDown(function() {
								ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
							});
						}
					});

					$this.on('click', '.form-input-handler', function() {
						if (!sorting) {
							$(this).parent().find('.form-input-content').slideToggle();
							ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
						}
						sorting = false;
					});

					$this.closest('.ait-opt-clone').find('.ait-clone-tools').on('click', '.ait-clone-toggle-all', function(event) {
						event.preventDefault();
						var $inputs = $this.find('.form-input-content');
						if ($inputs.is(':visible')) {
							// at least one input is open, close all of them
							$this.find('.form-input-content').slideUp();
						} else {
							$this.find('.form-input-content').slideDown();
						}
						ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
					});


					$this.on('change', '.ait-clone-item .ait-opt-text-main:first-child input', function(event) {
						var $formInput = $(event.target);
						var $clonedItem = $formInput.closest('.ait-clone-item');
						$clonedItem.find('.form-input-handler').find('.form-input-title').html($formInput.val());
					});

					initializing = false;

					ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();

					$(this).find('.ait-clone-remove-all').click(ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground);
				});
			}
		},



		preventPageScrollingWhenScrollingAvailableElements: function()
		{
			ui.$availableElements.on("mousewheel DOMMouseScroll", function(e) {
				var scrollingDown = false;
				if (e.type == 'mousewheel') {
					scrollingDown = e.originalEvent.wheelDelta < 0;
				} else {
					scrollingDown =  e.originalEvent.detail > 0;
				}

				if (scrollingDown) {
					var availableElementsBottomScrolledPosition = ui.$availableElements.prop('scrollHeight') - ui.$availableElements.prop('clientHeight');
					var scrolledToAvailableElementsBottom = ui.$availableElements.scrollTop() == availableElementsBottomScrolledPosition;
					if (scrolledToAvailableElementsBottom) {
						e.preventDefault();
					}
				} else if (ui.$availableElements.scrollTop() == 0) {
					e.preventDefault();
				}
			});
		},



		initPageSelect: function()
		{
			if(!$pageSelect.length) return;

			$pageSelect.chosen();
			$('#ait-page-options-selection-select-placeholder').remove();

			var $chosenSearch = $pageSelectContainer.find('.chosen-search');
			var $chosenDrop = $pageSelectContainer.find('.chosen-drop');
			$chosenSearch.css({'position': 'relative', 'width': '100%'});
			$chosenSearch.find('input').css({'width': '100%'});
			$chosenSearch.find('input').attr('placeholder', $pageSelect.data('placeholder'));
			//$pageSelectContainer.append($chosenSearch);
			$chosenDrop.css({'margin-top': '28px'});

			$chosenSearch.find('input').click(function(){
				$(this).trigger({type: 'keyup', which: 8, keyCode: 8});
			});

			ui.removeSpacesFromPageSelect($pageSelectContainer);
			ui.appendPageTypeToPageSelect($pageSelectContainer);

			$pageSelect.on('change', function(evt, params)
			{
				var $this = $(this);
				ui.removeSpacesFromPageSelect($pageSelectContainer);
				ui.appendPageTypeToPageSelect($pageSelectContainer);
				var $selectedOption = $this.find('option:selected');

				if (!$selectedOption.hasClass('has-local-options')) {
					if (confirm(ait.admin.l10n.confirm.addCustomOptions.supplant({pageTitle: $selectedOption.text()}))) {
						$this.closest('form').submit();
					} else {
						$this.val(currentPageSelectIndex).trigger("chosen:updated");
						ui.removeSpacesFromPageSelect($pageSelectContainer);
						ui.appendPageTypeToPageSelect($pageSelectContainer);
					}
				} else {
					$this.closest('form').submit();
				}
			});
		},



		initAitSimpleTabs: function()
		{
			var $tabs = $('.ait-simple-tabs .ait-simple-tab'),
				$content = $('.ait-simple-tabs-content .ait-simple-tab-content');

			$tabs.on('click', function() {
				var $tabContent = $('#' + $(this).data('tab-id'));

				$(this).siblings().removeClass('active');
				$tabContent.siblings().removeClass('active');

				$(this).addClass('active');
				$tabContent.addClass('active');
			});
		},



		enablePageToolsToggle: function()
		{
			var $pageToolsToggle = $('.ait-custom-header-tools .ait-pagetools-toggle');
			var $pageTools = $('.ait-custom-header-tools .ait-pagetools');

			$pageToolsToggle.on('click', function() {
				$(this).toggleClass('active');
				$pageTools.toggleClass('active');
			});
		},



		enableSaveButton: function()
		{
			var $button = $context.find('button[class^="ait-save"]:disabled');
			if ($button) $button.removeAttr('disabled');
		},



		initPageImportSelect: function()
		{
			$pageImportSelect.chosen();
			$('#ait-page-options-import-selection-select-placeholder').remove();

			ui.removeSpacesFromPageSelect($pageImportSelectContainer);

			$pageSelect.on('change', function(evt, params)
			{
				ui.removeSpacesFromPageSelect($pageImportSelectContainer);
			});

			$('#ait-import-page-options-button').click(function() {
				window.location = $(this).data('url') + '&importFrom=' + $pageImportSelect.val();
			})
		},



		save: function(e)
		{
			e.preventDefault();
			optionsData.save(e);
		},



		onSave: function(status, message)
		{
			ui.showSaveIndicator(status, message);
		},



		showSaveIndicator: function(status, message)
		{
			ui.items.$saveIndicator.hide();
			ui.items.$saveIndicator.removeClass('action-working action-done action-error');

			if (status == 'working') {
				ui.items.$saveIndicator.html(ait.admin.l10n.save.working);
				ui.items.$saveIndicator.addClass('action-working').show();
			} else if (status == 'done') {
				ui.items.$saveIndicator.html(ait.admin.l10n.save.done);
				ui.items.$saveIndicator.addClass('action-done').fadeIn().delay(2000).fadeOut(100, function()
				{
					ui.items.$saveIndicator.removeClass('action-working action-done action-error');
				});
			} else if (status == 'error') {
				if (typeof message === "undefined" || (typeof data === "object")) {
					message = '';
				}
				ui.items.$saveIndicator.html(ait.admin.l10n.save.error + ' ' + message);
				ui.items.$saveIndicator.addClass('action-error').fadeIn();
			}
		},



		reset: function(e)
		{
			e.preventDefault();
			var data = ait.admin.utils.getDataAttr($(this), 'reset-options');

			if (!confirm(data.confirm))
				return false;

			optionsData.reset(data);
		},



		onReset: function(status, data, response)
		{
			ui.showResetIndicator(status, data, response);
		},



		resetGroup: function(e)
		{
			e.preventDefault();
			var data = ait.admin.utils.getDataAttr($(this), 'reset-options');

			if (!confirm(data.confirm))
				return false;

			data.$indicator = $(this).find('span.action-indicator.action-reset-group');
			optionsData.resetGroup(data);
		},



		onResetGroup: function(status, data, response)
		{
			ui.showResetGroupIndicator(status, data, response);
		},



		showResetIndicator: function(status, data, response)
		{
			ui.items.$resetIndicator.hide();
			ui.items.$resetIndicator.removeClass('action-working action-done action-error');

			if (typeof response !== "undefined" && !response.success) {
				ui.items.$resetIndicator.html(response.data);
				ui.items.$resetIndicator.addClass('action-error').fadeIn();
				return false;
			}

			if (status == 'working') {
				ui.items.$resetIndicator.html(ait.admin.l10n.reset.working);
				ui.items.$resetIndicator.addClass('action-working').show();
			} else if (status == 'done') {
				ui.items.$resetIndicator.html(ait.admin.l10n.reset.done);
				ui.items.$resetIndicator.addClass('action-done').fadeIn();
				window.location.reload();
			}
		},



		showResetGroupIndicator: function(status, data, response)
		{
			var $i = data.$indicator; // shortcut
			$i.hide();
			$i.removeClass('action-working action-done action-error');

			if (typeof response !== "undefined" && !response.success) {
				$i.addClass('action-error').fadeIn();
				return false;
			}

			if (status == 'working') {
				$i.addClass('action-working').show();
			} else if (status == 'done') {
				$i.addClass('action-done').hide(100, function()
				{
					$i.removeClass('action-working action-done action-error');
				});
				window.location.reload();
			}
		},



		importGlobals: function(e)
		{
			e.preventDefault();
			var data = ait.admin.utils.getDataAttr($(this), 'import-global-options');

			if (!confirm(data.confirm))
				return false;

			data.$indicator = $(this).find('span.action-indicator.action-import-global-options');
			optionsData.importGlobals(data);
		},



		onImportGlobals: function(status, data, response)
		{
			ui.showImportGlobalsIndicator(status, data, response);
		},



		showImportGlobalsIndicator: function(status, data, response)
		{
			var $i = data.$indicator; // shortcut
			$i.hide();
			$i.removeClass('action-working action-done action-error');

			if (typeof response !== "undefined" && !response.success) {
				$i.addClass('action-error').fadeIn();
				return false;
			}

			if (status == 'working') {
				$i.addClass('action-working').show();
			} else if (status == 'done') {
				$i.addClass('action-done').hide(100, function()
				{
					$i.removeClass('action-working action-done action-error');
				});
				window.location.reload();
			}
		},



		enhancedInputs: function($currentContext)
		{
			if(jQuery.fn.chosen !== undefined){
				$('select.chosen', $currentContext).chosen({
					search_contains: true
				});
			}

			if(jQuery.fn.colorpicker !== undefined){
				$('.ait-opt-color .ait-colorpicker', $currentContext).colorpicker();
			}

			if(jQuery.fn.rangeinput !== undefined){
				$('.ait-opt-range input[type=range]', $currentContext).rangeinput();
			}
			$window.on('load', function() {
				if(ait.admin.options.elements){
					ait.admin.options.elements.Ui.updateRangeInputs($currentContext); // prevent NaN in range inputs
				}
			});

			if(jQuery.fn.numberinput !== undefined){
				$('.ait-opt-number input[type=number]', $currentContext).numberinput();
			}
			ui.imageradio($currentContext);
			ui.onoff($currentContext);
			ui.datepicker($currentContext);
			ui.background($currentContext);
			ui.map($currentContext);
			ui.multimarkerMap($currentContext);
			ui.hidden($currentContext);
		},



		switchableSections: function($context)
		{
			var sectionSwitchers = [];

			$context.find('.ait-options-section').each(function() {
				var $section = $(this);
				if ($section.is('[id]')) {
					var sectionId = $section.attr('id').split('-');
					if (sectionId.length >= 2 && sectionId[0] != undefined && sectionId[1] != undefined) {
						var $sectionSwitcher = $section.parent().find('[name*="[' + sectionId[0] + ']"]');
						if ($sectionSwitcher.length) {

							// register onchange events of section switcher
							if ($.inArray($sectionSwitcher.attr('id'), sectionSwitchers) === -1) {
								sectionSwitchers.push($sectionSwitcher.attr('id'));
								$sectionSwitcher.change(function() {
									var $switchableSections = $sectionSwitcher.closest('.ait-controls-tabs-panel').find('[class*="ait-options-section"][id*="' + sectionId[0] + '"]');
									$switchableSections.hide();
									var $selectedSection = $sectionSwitcher.closest('.ait-controls-tabs-panel').find('[class*="ait-options-section"][id*="' + $(this).val() + '"]');
									$selectedSection.fadeIn();

									// if switched section contains map preview trigger resize to re-render map
									// if map preview is provided by openstreetmap we must completely re-init map in container
									if (jQuery($selectedSection).find('.ait-opt-maps-preview').length > 0) {
										var $mapsOptionContainer = $('.ait-opt-maps-tools', $selectedSection);

										if ($mapsOptionContainer.data('map-provider') == 'openstreetmap') {
											jQuery($selectedSection).find('.ait-opt-maps-preview').trigger('mapinit');
										} else {
											jQuery($selectedSection).find('.ait-opt-maps-preview').gmap3({trigger:'resize'});
										}

									}

									ait.admin.options.elements.Ui.updateElementsWithSidebarsBackground();
								});
							}

							// show only currently selected section
							var selectedSection;
							if ($sectionSwitcher.length > 1) {
								selectedSection = $sectionSwitcher.filter(':checked').val();
							} else {
								selectedSection = $sectionSwitcher.val();
							}

							if (selectedSection == sectionId[1]) {
								$section.show();
							} else {
								$section.hide();
							}
						}
					}
				}
			})
		},



		imageradio: function($currentContext)
		{
			$('.ait-opt-image-radio, .ait-opt-image-radio-full', $currentContext).each(function(i)
			{
				var $imgRadio = $(this);

				$imgRadio.find('input').click(function()
				{
					var $this = $(this);
					if ($this.is(':checked')) {
						$imgRadio.find('label').removeClass('selected-option');
						$this.parent().addClass('selected-option');
					}
				});
			});
		},



		onoff: function($currentContext)
		{
			if(jQuery.fn.switchify === undefined) return;

			$('.ait-opt-on-off .ait-opt-switch select', $currentContext).switchify().each(function(i)
			{
				var $this = $(this);
				if ($this.hasClass('ait-opt-@display')) {
					$this.data('switch').on('switch:slide', function(e, type)
					{
						var $el = $(this).closest('.ait-element');
						if (type == 'off' && !$el.hasClass('ait-element-off')) {
							$el.addClass('ait-element-off');
						} else {
							$el.removeClass('ait-element-off');
						}
					});
				}
			});
		},



		hidden: function($currentContext)
		{
			function getUniqueId() {
				function s4() {
					return Math.floor((1 + Math.random()) * 0x10000)
						.toString(16)
						.substring(1);
				}
				return s4() + s4();
			}
			$("input[type=hidden][data-uuid=1]", $currentContext).each(function(i)
			{
				var $this = jQuery(this);
				if ($this.val() === "") {
					var uuid = getUniqueId();
					$this.val(uuid);
				}
			});
		},



		datepicker: function($currentContext)
		{
			var $datepickers = $('.ait-opt-date .ait-datepicker input[type=text]', $currentContext);
			$datepickers.each(function()
			{
				var $this  = $(this);
				var dateFormat = ait.admin.utils.getDataAttr($this, 'datepicker').dateFormat;
				var timeFormat = ait.admin.utils.getDataAttr($this, 'datepicker').timeFormat;
				var pickerType   = ait.admin.utils.getDataAttr($this, 'datepicker').pickerType;
				var langCode   = ait.admin.utils.getDataAttr($this, 'datepicker').langCode;

				var options;

				if (pickerType == "datetime") {
					options = {
						showOn: "both",
						changeMonth: true,
						changeYear: true,
						dateFormat: dateFormat,
						timeFormat: timeFormat,
						altFormat: 'yy-mm-dd',
						altTimeFormat: 'HH:mm:00',
						altFieldTimeOnly: false,
						altField: '#' + $this.attr('id') + "-standard-format",
						firstDay: ait.admin.l10n.datetimes.startOfWeek,
						yearRange: '1900:3000',
						beforeShow: function(input, object)
						{
							var c = $('#ui-datepicker-div').attr('class');
							$('#ui-datepicker-div').attr('class', 'ait-datepicker-calendar ' + c);
						},
						buttonImage: "data:image/gif;base64,R0lGODlhEAAPAPQAAIyq7zlx3lqK5zFpznOe7/729fvh3/3y8e1lXt1jXO5tZe9zbLxeWfB6c6lbV/GDffKIgvKNh/OYkvSblvSinfWrp3dTUfawq/e1sf3r6v/8/P/9/f///////wAAAAAAACH5BAEAAB0ALAAAAAAQAA8AAAWK4GWJpDWN6KU8nNK+bsIxs3FdVUVRUhQ9wMUCgbhkjshbbkkpKnWSqC84rHA4kmsWu9lICgWHlQO5lsldSMEgrkAaknccQBAE4mKtfkPQaAIZFw4TZmZdAhoHAxkYg25wchABAQMDeIRYHF5gEkcSBo2YEGlgEEcQoI4SDRWrrayrFxCDDrW2t7ghADs=",
						showButtonPanel: false,
					};

					if(langCode != 'en'){
						if($.datepicker.regional[langCode]){
							$.extend(options, $.datepicker.regional[langCode]);
						}
						if($.timepicker.regional[langCode]){
							$.extend(options, $.timepicker.regional[langCode]);
						}
					}
					$this.datetimepicker(options);
					if ($this.val() != '') {
						var currentDate = new Date($this.val().replace(new RegExp('-', 'g'), '/'));
						$this.datetimepicker('setDate', currentDate);

					}
				}
				else if(pickerType == "date") {
					options = {
						dateFormat: dateFormat,
						changeMonth: true,
						changeYear: true,
						showOn: "both",
						altFormat: 'yy-mm-dd',
						altField: '#' + $this.attr('id') + "-standard-format",
						firstDay: ait.admin.l10n.datetimes.startOfWeek,
						yearRange: '1900:3000',
						beforeShow: function(input, object)
						{
							var c = $('#ui-datepicker-div').attr('class');
							$('#ui-datepicker-div').attr('class', 'ait-datepicker-calendar ' + c);
						},
						buttonImage: "data:image/gif;base64,R0lGODlhEAAPAPQAAIyq7zlx3lqK5zFpznOe7/729fvh3/3y8e1lXt1jXO5tZe9zbLxeWfB6c6lbV/GDffKIgvKNh/OYkvSblvSinfWrp3dTUfawq/e1sf3r6v/8/P/9/f///////wAAAAAAACH5BAEAAB0ALAAAAAAQAA8AAAWK4GWJpDWN6KU8nNK+bsIxs3FdVUVRUhQ9wMUCgbhkjshbbkkpKnWSqC84rHA4kmsWu9lICgWHlQO5lsldSMEgrkAaknccQBAE4mKtfkPQaAIZFw4TZmZdAhoHAxkYg25wchABAQMDeIRYHF5gEkcSBo2YEGlgEEcQoI4SDRWrrayrFxCDDrW2t7ghADs=",
					};
					if(langCode != 'en' && $.datepicker.regional[langCode]){
						$.extend(options, $.datepicker.regional[langCode]);
					}
					$this.datepicker(options);
					if ($this.val() != '') {
						var currentDate = new Date($this.val());
						currentDate.setMinutes(currentDate.getMinutes() + currentDate.getTimezoneOffset());
						$this.datepicker('setDate', currentDate);
					}
				}
			});
		},



		background: function($currentContext)
		{
			var $backgrounds = $('.ait-opt-background', $currentContext);

			if(jQuery.fn.colorpicker !== undefined){
				$backgrounds.find('.ait-colorpicker').colorpicker().on('changeColor', function(e)
				{
					if ($(e.target).val() !== '')
						$(this).closest('.ait-opt-background').find('.ait-opt-bg-preview')[0].style.backgroundColor = e.color.toRGBAstring();
					else
						$(this).closest('.ait-opt-background').find('.ait-opt-bg-preview')[0].style.backgroundColor = 'transparent';
				});
			}

			$backgrounds.each(function(i, el)
			{
				var css = {
					'background-size': '',
					'background-image': '',
					'background-color': '',
					'background-position': '',
					'background-repeat': '',
					'background-attachment': ''
				};

				var $root = $(el);
				var $preview = $root.find('.ait-opt-bg-preview');
				var $imageFake = $root.find('.ait-image-value-fake');
				var $image = $root.find('.ait-image-value');
				var $color = $root.find('.ait-colorpicker-storage');
				var $repeat = $root.find('.ait-opt-bg-repeat select');
				var $position = $root.find('.ait-opt-bg-position select');
				var $scroll = $root.find('.ait-opt-bg-scroll select');

				if ($image.length && $image.val() !== '') {
					css['background-image'] = 'url("' + $image.val() + '")';
				}

				$imageFake.change(function(e)
				{
					css['background-image'] = 'url("' + $imageFake.val() + '")';
					$preview.css(css);
				});

				if ($color.length) css['background-color'] = $color.val() !== '' ? $color.val() : 'transparent';

				if ($repeat.length) {
					css['background-repeat'] = $repeat.val();

					$repeat.change(function(e)
					{
						css['background-repeat'] = $repeat.val();
						$preview.css(css);
					});
				}

				if ($position.length) {
					css['background-position'] = $position.val();
					$position.change(function(e)
					{
						css['background-position'] = $position.val();
						$preview.css(css);
					});
				}

				if ($scroll.length) {
					css['background-attachment'] = $scroll.val();
					// $scroll.change(function(e){
					// 	css['background-attachment'] = $scroll.val();
					// 	$preview.css(css);
					// });
				}

				$preview.css(css);
			});
		},

		map: function($currentContext)
		{
			var $container = $('.ait-opt-maps-tools', $currentContext);

			if ($container.data('map-provider') == 'openstreetmap') {
				this.leafletMap($container);
			} else {
				this.googleMap($container);
			}
		},

		googleMap: function($container)
		{
			$container.on('mapinit', null, function(){
				var $thisContainer = $(this);
				var $map = $thisContainer.find('.ait-opt-maps-preview');

				var $addressField = $thisContainer.find('.ait-opt-maps-address input[type="text"]');
				var $addressSearchBtn = $thisContainer.find('.ait-opt-maps-address input[type="button"]');
				var $latitudeField = $thisContainer.find('.ait-opt-maps-latitude input[type="text"]');
				var $longitudeField = $thisContainer.find('.ait-opt-maps-longitude input[type="text"]');
				var $streetviewControl = $thisContainer.find('.ait-opt-maps-streetview select');
				var $swHeadingControl = $thisContainer.find('.ait-opt-maps-swheading input[type="hidden"]');
				var $swPitchControl = $thisContainer.find('.ait-opt-maps-swpitch input[type="hidden"]');
				var $swZoomControl = $thisContainer.find('.ait-opt-maps-swzoom input[type="hidden"]');

				var $messageContainer = $thisContainer.find('.ait-opt-maps-message');

				var mapdata = {
					address: $addressField.val(),
					latitude: $latitudeField.val() ? parseFloat($latitudeField.val()) : 1,
					longitude: $longitudeField.val() ? parseFloat($longitudeField.val()) : 1,
					streetview: parseInt($streetviewControl.val()) == 1 ? true : false,
					swheading: $swHeadingControl.val() ? parseFloat($swHeadingControl.val()) : 1,
					swpitch: $swPitchControl.val() ? parseFloat($swPitchControl.val()) : 1,
					swzoom: $swZoomControl.val() ? parseFloat($swZoomControl.val()) : 1,
				}

				// init map
				var position = new google.maps.LatLng(mapdata.latitude, mapdata.longitude);
				var width = $map.parent().width();
				$map.width(width).height(width/2);
				$map.gmap3({
					map: {
						events: {
							click:function(mapLocal, event){
								$map.gmap3({
									get: {
										name: "marker",
										callback: function(marker){
											marker.setPosition(event.latLng);
											$latitudeField.val(event.latLng.lat());
											$longitudeField.val(event.latLng.lng());
										}
									}
								});
							}
						},
						options: {
							center: position,
							zoom: 3,
							mapTypeId: google.maps.MapTypeId.ROADMAP,
							streetViewControl: false,
						}
					},
					marker: {
						values:[
							{latLng:[mapdata.latitude,mapdata.longitude]}
						],
						options: {
							draggable: true
						},
						events: {
							dragend: function(marker){
								var pos = marker.getPosition();
								$latitudeField.val(pos.lat());
								$longitudeField.val(pos.lng());
							}
						}
					},
					streetviewpanorama:{
						options:{
							container: $map,
							opts:{
								position: position,
								pov: {
									heading: mapdata.swheading,
									pitch: mapdata.swpitch,
									zoom: mapdata.swzoom
								}
						  	}
						},
						events: {
							position_changed: function (obj) {
								if(parseInt($streetviewControl.attr('data-switch-state')) == 1){
									$latitudeField.val(obj.position.lat());
									$longitudeField.val(obj.position.lng());
								}
							},
							pov_changed: function (obj) {
								if(parseInt($streetviewControl.attr('data-switch-state')) == 1){
									$swHeadingControl.val(obj.pov.heading);
									$swPitchControl.val(obj.pov.pitch);
									$swZoomControl.val(obj.pov.zoom);
								}
							}
						}
					}
				});

				gm_authFailure = function(){
					var $apiMessageContainer = $thisContainer.find('.ait-opt-maps-message-api');
					$apiMessageContainer.show();
				};

				var mapObject = $map.gmap3({
					get: {
						name: "map"
					}
				});

				var marker = $map.gmap3({
					get: {
						name: "marker"
					}
				});

				var streetviewObject = $map.gmap3({
					get: {
						name: "streetviewpanorama"
					}
				});

				$streetviewControl.switchify().each(function(i)
				{
					var $this = $(this);

					var $selectSelected = $this.find('option:selected').val();
					$this.attr('data-switch-state', $selectSelected);	// 0 => off / 1 => on

					$this.data('switch').on('switch:slide', function(e, type)
					{
						if (type == 'on') {
							streetviewObject.setPosition(new google.maps.LatLng(parseFloat($latitudeField.val()), parseFloat($longitudeField.val())));
							streetviewObject.setVisible(true);
							$this.attr('data-switch-state', 1);
						} else {
							var position = new google.maps.LatLng(parseFloat($latitudeField.val()), parseFloat($longitudeField.val()));
							marker.setPosition(position);
							mapObject.setCenter(position);
							streetviewObject.setVisible(false);
							$this.attr('data-switch-state', 0);
						}
					});
				});

				// click action for find button
				$addressSearchBtn.click(function(e){
					e.preventDefault();
					$messageContainer.hide('slow');
					var addr = $addressField.val();
					if ( !addr || !addr.length ) return;

					$map.gmap3({
						getlatlng:{
							address:  addr,
							callback: function(results){
								if(typeof results !== "undefined" && results.length > 0){
									marker.setPosition(results[0].geometry.location);
									$map.gmap3({
										map: {
											options: {
												zoom: 17,
												center: results[0].geometry.location
											}
										}
									})
									$latitudeField.val(results[0].geometry.location.lat());
									$longitudeField.val(results[0].geometry.location.lng());
									streetviewObject.setPosition(new google.maps.LatLng(parseFloat($latitudeField.val()), parseFloat($longitudeField.val())));
								} else {
									$messageContainer.show();
								}
							}
						}
					});
				});

				$latitudeField.on('keyup', function(e){
					$(this).css({'border-color': ''});
					$(this).parent().find('i').remove();
					if($(this).val().match(/^-?\d*(\.\d+)?$/) && $(this).val() >= -90 && $(this).val() <= 90){
						//$(this).css({'border-color': '#9DB1B9'});
						$(this).parent().append('<i class="fa fa-check-circle" style="color: #88B44E" title="Valid"></i>');
					} else {
						$(this).css({'border-color': '#BE6565'});
						$(this).parent().append('<i class="fa fa-times-circle" style="color: #BE6565" title="Invalid"></i>');
					}
				}).trigger('keyup');

				$longitudeField.on('keyup', function(e){
					$(this).css({'border-color': ''});
					$(this).parent().find('i').remove();
					if($(this).val().match(/^-?\d*(\.\d+)?$/) && $(this).val() >= -180 && $(this).val() <= 180){
						//$(this).css({'border-color': '#9DB1B9'});
						$(this).parent().append('<i class="fa fa-check-circle" style="color: #88B44E" title="Valid"></i>');
					} else {
						$(this).css({'border-color': '#BE6565'});
						$(this).parent().append('<i class="fa fa-times-circle" style="color: #BE6565" title="Invalid"></i>');
					}
				}).trigger('keyup');

				// initial display of the container
				if(mapdata.streetview){
					if(typeof streetviewObject.setVisible == "function"){
						streetviewObject.setVisible(true);
					}
				} else {
					if(typeof streetviewObject.setVisible == "function"){
						streetviewObject.setVisible(false);
					}
				}
			});

			// if($container.find('.ait-opt-maps-preview').parent().width() != 0){
				$container.trigger('mapinit');
			// }
		},

		leafletMap: function($container)
		{

			$container.on('mapinit', null, function(){
				var $thisContainer = $(this);
				var $map = $thisContainer.find('.ait-opt-maps-preview');
				var $addressField = $thisContainer.find('.ait-opt-maps-address input[type="text"]');
				var $addressSearchBtn = $thisContainer.find('.ait-opt-maps-address input[type="button"]');
				var $latitudeField = $thisContainer.find('.ait-opt-maps-latitude input[type="text"]');
				var $longitudeField = $thisContainer.find('.ait-opt-maps-longitude input[type="text"]');
				var $streetviewControl = $thisContainer.find('.ait-opt-maps-streetview select');
				
				var $messageContainer = $thisContainer.find('.ait-opt-maps-message');

				var mapdata = {
					address: $addressField.val(),
					latitude: $latitudeField.val() ? parseFloat($latitudeField.val()) : 1,
					longitude: $longitudeField.val() ? parseFloat($longitudeField.val()) : 1,
				}

				var position = L.latLng(mapdata.latitude, mapdata.longitude);
				var width = $map.parent().width();
				$map.width(width).height(width/2);

				// fix: map cannot be initialized multiple times in same dom container
				$map.empty();
				if (!$map[0]) return;
				var mapContainer = L.DomUtil.create('div', 'ait-opt-maps-preview-container', $map[0]);
				jQuery(mapContainer).css('width', 'inherit').css('height', 'inherit');

				var map = L.map(mapContainer);

				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?', {
					attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
				}).addTo(map);

				map.on('load', function(e) {
					setTimeout(function() {
						map.invalidateSize();
					}, 300);
				});

				map.setView(position, 17);

				var marker = L.marker(position, {
					draggable: true,
					autoPan: true
				}).addTo(map);

				map.on('click', function(e) {
					map.panTo(e.latlng);
					marker.setLatLng(e.latlng);
					$latitudeField.val(e.latlng.lat);
					$longitudeField.val(e.latlng.lng);
				});

				marker.on('move', function(e) {
					$latitudeField.val(e.latlng.lat);
					$longitudeField.val(e.latlng.lng);
				});
				
				marker.on('moveend', function(e) {
					map.panTo(e.target.getLatLng());
				});

				$addressSearchBtn.click(function(e) {
					e.preventDefault();
					$messageContainer.slideUp();

					var address = $addressField.val();
					if ( !address || !address.length ) return;

					$.getJSON('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + address, function(data) {
						if (data.length == 0) {
							$messageContainer.slideDown();
							return;
						}
						var item = data[0];

						var foundPosition = L.latLng(item.lat, item.lon);
						map.panTo(foundPosition);
						marker.setLatLng(foundPosition);
						$latitudeField.val(item.lat);
						$longitudeField.val(item.lon);
					});
				});
			});

			$container.trigger('mapinit');
			
		},

		multimarkerMap: function($currentContext)
		{
			var $container   = $('.ait-opt-maps-tools', $currentContext);
			var mapContainer = $container.find('.ait-opt-multimaps-preview').get(0);

			var addressField;
			var relatedName;
			var findAddressBtn;
			var metaboxID;
			var relatedElement;
			var relatedCloneID;
			var addLink;
			var infoWindow;
			var geocoder;
			var flightPaths = [];
			var mapData = {};

			function initialize()
			{
				addressField   = $container.find('.ait-opt-maps-address input[type="text"]');
				findAddressBtn = $container.find('#find-address');
				relatedName    = $container.find('.ait-opt-maps-related input[type="hidden"]').val();
				metaboxID      = $('div[data-ait-metabox]', $currentContext).data('ait-metabox');
				relatedCloneID = "#ait-opt-metabox-" + metaboxID + "-"+ relatedName;
				relatedElement = $(relatedCloneID).parent();
				addLink        = relatedElement.find('.ait-clone-add');
				geocoder       = new google.maps.Geocoder();

				mapData = {
					counter: 0,
					flightPlanCoordinates: {},
					markers: {},
					bounds: new google.maps.LatLngBounds(),
				};

				var mapOptions = {
					zoom: 3,
					center: new google.maps.LatLng(0, 0),
					mapTypeId: google.maps.MapTypeId.ROADMAP
				};

				var map = new google.maps.Map(mapContainer, mapOptions);

				hideControls();
				initMarkers(map);
				autoFit(map);

				google.maps.event.addListener(map, 'click', function(event) {
					var marker = placeMarker(event.latLng, map, "", "");
					cloneMarker(marker);
				});

				findAddressBtn.get(0).addEventListener("click", function() {
					findAddress(map);
				});
			}

			function placeMarker(location, map, title)
			{
				var marker = new google.maps.Marker({
					position: location,
					map: map,
					draggable: true,
					id: mapData.counter,
					title: title
				});
				mapData.markers[marker.id] = marker;
				mapData.flightPlanCoordinates[marker.id] = location;
				buildPath(map);
				mapData.counter++;

				mapData.bounds.extend(location);

				// listeners
				google.maps.event.addListener(marker, 'dragend', function(event) {
					mapData.flightPlanCoordinates[marker.id] = event.latLng;
					updatePath(map);
					updateClonedMarker(marker);
				});

				google.maps.event.addListener(marker, 'click', function(event) {
					openInfoWindow(map, marker);
				});

				return marker;
			}

			function hideControls()
			{
				relatedElement.parent().parent().addClass('hidden-tools');
				relatedElement.parent().parent().addClass('hidden-tools');
			}

			function openInfoWindow(map, marker)
			{
				var content = $('#info-window-data').clone().show().get(0);
				$(content).find('h3').text('#'+(marker.id+1) + " " + marker.title);
				if (infoWindow) {
					infoWindow.close();
				}
				infoWindow = new google.maps.InfoWindow({content: content});
				infoWindow.open(map, marker);

				google.maps.event.addListener(infoWindow, 'domready', function(){
					$(infoWindow.getContent()).find('#info-window-remove').click(function(event){
						if(relatedElement.find(relatedCloneID+"_template"+marker.id+" .ait-clone-remove-current").length) {
							relatedElement.find(relatedCloneID+"_template"+marker.id+" .ait-clone-remove-current").trigger("click");
						}else{
							relatedElement.find(relatedCloneID+"-"+marker.id+"-pregenerated"+marker.id+" .ait-clone-remove-current").trigger("click");
						}
						removeMarker(map, marker);
					});
				});
			}

			function removeMarker(map, marker)
			{
				mapData.markers[marker.id].setMap(null);
				delete mapData.flightPlanCoordinates[marker.id];
				updatePath(map);
			}

			function buildPath(map)
			{
				var coordinates = $.map(mapData.flightPlanCoordinates, function(el) { return el; })
				var flightPath = new google.maps.Polyline({
					path: coordinates,
					geodesic: true,
					strokeColor: '#FF0000',
					strokeOpacity: 0.9,
					strokeWeight: 1
				});
				flightPath.setMap(map);
				flightPaths.push(flightPath);
			}

			function updatePath(map)
			{
				removePaths();
				buildPath(map);
			}

			function updateClonedMarker(marker)
			{
				if(relatedElement.find(relatedCloneID+"_template"+marker.id+" .ait-clone-remove-current").length) {
					var clonedMarker = relatedElement.find(relatedCloneID+"_template"+marker.id);
				}else{
					var clonedMarker = relatedElement.find(relatedCloneID+"-"+marker.id+"-pregenerated"+marker.id);
				}
				var s = metaboxID + '[' + relatedName + ']';
				$(clonedMarker).find('input[name="' + s + '['+marker.id+'][lat]"]').val(marker.position.lat());
				$(clonedMarker).find('input[name="' + s + '['+marker.id+'][lng]"]').val(marker.position.lng());
			}

			function initMarkers(map)
			{
				var cloneItems = relatedElement.find('.ait-clone-item');
				var s = metaboxID + '[' + relatedName + ']';
				cloneItems.each(function(index, value){
					$(value).find('.form-input-title').text('#'+(index+1) + " " + $(value).find('.form-input-title').text());
					var lat = $(value).find('input[name="' + s + '['+index+'][lat]"]').val();
					var lng = $(value).find('input[name="' + s + '['+index+'][lng]"]').val();
					var title = $(value).find('input[name="' + s + '['+index+'][title]"]').val();
					var context = $(value).find('input[name="' + s + '['+index+'][desc]"]').val();
					placeMarker(new google.maps.LatLng(lat, lng), map, title, context)
				});
			}

			function cloneMarker(marker)
			{
				addLink.trigger("click");
				var clonedMarker = relatedElement.find(relatedCloneID+"_template"+marker.id);
				clonedMarker.find('.form-input-title').text('#'+(marker.id+1) + " " + clonedMarker.find('.form-input-title').text());
				var s = metaboxID + '[' + relatedName + ']';
				$(clonedMarker).find('input[name="' + s + '['+marker.id+'][lat]"]').val(marker.position.lat());
				$(clonedMarker).find('input[name="' + s + '['+marker.id+'][lng]"]').val(marker.position.lng());
			}

			function removePaths()
			{
				flightPaths.forEach(function(element){
					element.setMap(null);
				});
			}

			function findAddress(map) {
				var address = addressField.val();
				geocoder.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						map.setCenter(results[0].geometry.location);
						map.setZoom(18);
					}
				});
			}

			function autoFit(map)
			{
				map.fitBounds(mapData.bounds);
			    map.panToBounds(mapData.bounds);
			}


			if(typeof mapContainer !== 'undefined'){
				google.maps.event.addDomListener(window, 'load', initialize);
			 };
		},



		changeImageInput: function(e)
		{
			var $this = $(this);
			var $realInput = $this.siblings('.ait-image-value');
			var val = $this.val();
			$realInput.val(val);
		},



		selectImage: function(e)
		{
			e.preventDefault();
			var $this = $(this);
			var id = $this.attr('id');
			var $input = $this.siblings('.ait-image-value');
			var data = ait.admin.utils.getDataAttr($this, 'select-image');

			ui.mediaFrame(
				id,
				data.title,
				data.buttonTitle,
				$input
			).open();
		},



		updateUploadFileInput: function()
		{
			$('.ait-opt-file-upload input[type="file"]').on('change', function() {
				$(this).siblings('.ait-opt-file-input').html($(this).val());
			});
		},



		_frame: {},

		mediaFrame: function(id, title, buttonTitle, $input)
		{
			if (id in ui._frame) {
				return ui._frame[id];
			}

			var $inputFake = $input.siblings('.ait-image-value-fake');

			var frame = ui._frame[id] = wp.media({
				title: title,
				library: { type: 'image' },
				button: { text: buttonTitle }
			});

			frame.on('select', function()
			{
				var img = frame.state().get('selection').first();
				$input.val(img.get('url'));

				$inputFake.val(img.get('url'));
				$inputFake.trigger('change');
			});

			return frame;
		},



		stickHeader: function()
		{
			var $stickyHeader = $('.ait-sticky-header');

			if($stickyHeader.length) {
				var scrollOfset = $('.ait-options-page-content').offset().top + ui.wpAdminBarHeight;
				$stickyHeader.css('width', $('.ait-options-page-content').width());


				$window.on('resize scroll', function() {
					if ($window.scrollTop() > scrollOfset) {
						$('body').addClass('sticky');
						$stickyHeader.css('width', $('.ait-options-page-content').width());
						if(ui.isResponsive(480)) {
							$('.ait-options-page').css('margin-top', $('.ait-header-save').outerHeight() + 10);
						}
					} else {
						$('body').removeClass('sticky');
						$('.ait-options-page').css('margin-top', 0);
					}
				});
			}
		},



		scrollToTop: function($elm)
		{
			$elm.on('click', function(e){
				e.preventDefault();
				$(this).blur();
				$('html, body').animate({ scrollTop: 0 }, "slow");
			})
		},



		stickElements: function()
		{
			if(ui.$stickToTopHeader.length && ui.$availableElements.length) {

				var stickToTopHeaderOffset = ui.$stickToTopHeader.offset().top - ui.wpAdminBarHeight;

				$window.on('resize scroll', function() {
					var stickToTopHeaderHeight = ui.$stickToTopHeader.outerHeight(true);
					var scrollHeight = $(document).height() - $window.height() - ui.wpAdminBarHeight;

					if ( $window.scrollTop() > stickToTopHeaderOffset && stickToTopHeaderHeight < scrollHeight) {
						$('body').addClass('sticky');
						if (!ui.$availableElements.hasClass('collapsed')) {
							$('.ait-options-content').css('margin-top', stickToTopHeaderHeight);
						}
						ui.$stickToTopHeader.css('top', ui.wpAdminBarHeight);
						ui.$stickToTopHeader.css('width', $('.ait-available-elements-container').width());
					} else {
						$('body').removeClass('sticky');
						ui.$stickToTopHeader.css('top', 0);
						ui.$stickToTopHeader.css('width', 'auto');
						$('.ait-options-content').css('margin-top', 0);
						ui.$availableElements.height('auto').removeClass('collapsed');
						ui.$stickToTopHeader.find('.toggle-collapse').hide();
					}

					// Collapse sticky elements
					clearTimeout($.data(this, 'scrollTimer'));
					$.data(this, 'scrollTimer', setTimeout(function() {
						ui.collapseElements();
					}, 250));
				});


				// Toggle sticky elements

				ui.$stickToTopHeader.hover(function(){
					ui.$availableElements.addClass('hover');
					ui.collapseElements(false);
				}, function(){
					ui.$availableElements.removeClass('hover');
					ui.collapseElements(true);
				});

				ui.$stickToTopHeader.find('.toggle-collapse').on('click', function(e) {
					e.preventDefault();
					ui.collapseElements('toggle');
				});

				ui.$stickToTopHeader.find('.ait-simple-tab').on('click', function() {
					ui.collapseElements('checkToggle');
				});
			}
		},



		collapseElements: function(enabled)
		{
			var elementsWrap = ui.$availableElements;
			var elementsWrapHeight = elementsWrap.outerHeight();
			var elementHeight = $('.active .ait-available-element').outerHeight();
			var safeOffset = 20;
			var expandedHeight = elementsWrap.get(0).scrollHeight;
			var collapsedHeight = elementHeight + safeOffset;
			var collapsedClass = 'collapsed';
			var toggle = $('.toggle-collapse');

			var action = {

				collapse: function() {
					if (elementsWrapHeight <= (collapsedHeight + 10) || elementsWrap.hasClass(collapsedClass)) return;
					elementsWrap.addClass(collapsedClass).stop().animate({height: collapsedHeight}, 200, function(){
						$(this).removeClass('hover').blur();
						if (ui.isTouch) {
							$(this).addClass('has-toggle');
						}
					});
				},

				expand: function() {
					if (!elementsWrap.hasClass(collapsedClass)) return;
					elementsWrap.stop().animate({height: expandedHeight}, 200, function(){
						$(this).height('auto').removeClass(collapsedClass);
						if (!ui.isTouch) {
							$(this).removeClass('has-toggle');
						}
					});
				},

				toggle: function() {
					if (elementsWrap.hasClass(collapsedClass)) {
						action.expand();
					} else {
						action.collapse();
					}
				},

				checkToggle: function() {
					action.expand();
					if (ui.isTouch) {
						if (elementsWrapHeight <= (collapsedHeight + 10)) {
							elementsWrap.removeClass('has-toggle');
						} else {
							elementsWrap.addClass('has-toggle');
						}
					}
				}

			}

			if ($('body').hasClass('sticky') && !ui.isResponsive(1190) && $window.height() < 900) {

				// Execute Collapse / Expand
				if (typeof enabled !== 'undefined') {
					switch (enabled) {
						case true:
							action.collapse();
							break;

						case 'toggle':
							action.toggle();
							break;

						case 'checkToggle':
							action.checkToggle();
							break;

						default:
							action.expand();
					}
				} else {
					action.collapse();
				}

			} else {
				action.expand();
				elementsWrap.removeClass('has-toggle');
			}
		},



		setStickToTopSidebarDimensions: function(stickToTopSidebarOffset, availableElementsTopPosition)
		{
			ui.$availableElements.height(parseInt($window.height() - availableElementsTopPosition - ui.pageBottomOffset));

			if ( $window.scrollTop() > stickToTopSidebarOffset + ui.wpAdminBarHeight) {
				ui.$stickToTopHeader.css('margin-top', parseInt($window.scrollTop() - stickToTopSidebarOffset));
			} else {
				ui.$stickToTopHeader.css('margin-top', parseInt(ui.stickToTopSidebarTopMargin));
			}
		},



		calculatePageBottomOffset: function()
		{
			var bodyBottomPadding = 65;
			var wpBody = $("#wpbody-content");
			if (wpBody.length) {
				bodyBottomPadding = parseInt(wpBody.css('padding-bottom'));
			}
			return bodyBottomPadding;
		},



		removeSpacesFromPageSelect: function($container)
		{
			var $currentPageSelect = $container.find(".chosen-single");
			var html = $currentPageSelect.html();
			if(html){
				$currentPageSelect.html(html.replace(/&nbsp;/g, ''));
			}
		},



		appendPageTypeToPageSelect: function($container)
		{
			var text = $container.find('.chosen-container a span');
			var pageType = $container.find('option[value="' + $container.find('select').val() + '"]').closest('optgroup').data('page-type');
			var label = '';
			if(pageType){
				if(pageType == 'special') label = ait.admin.l10n.labels.settingsForSpecialPageType
				else if(pageType == 'standard') label = ait.admin.l10n.labels.settingsForStandardPageType
				text.html(text.html() + '<i class="fa fa-caret-down"></i>' + '<small>' + label + '</small>');
			}
		},



		isResponsive: function(width)
		{
			var w=window,
				d=document,
				e=d.documentElement,
				g=d.getElementsByTagName('body')[0],
				x=w.innerWidth||e.clientWidth||g.clientWidth;
			return x <= parseInt(width);
		},



		isTouch: (('ontouchstart' in window) ||
			(navigator.maxTouchPoints > 0) ||
			(navigator.msMaxTouchPoints > 0))
	};



	// ===============================================
	// Init the UI and the Tabs
	// -----------------------------------------------

	$document.ready(function(){
		ait.admin.options.Ui.init();
	});

})(jQuery, jQuery(window), jQuery(document));
