

ait.admin.options.elements = ait.admin.options.elements || {};

(function($, $window, $document, undefined)
{

	"use strict";

	var $context = $('.ait-options-page');
    var $usedElementsContentsArea = $('#ait-used-elements-contents');
    var $unsortableArea = $('#ait-used-elements-unsortable');
	var $sortableArea = $('#ait-used-elements-sortable');
	var $availableElements = $('#ait-available-elements').find('.ait-available-element:not(.ait-element-disabled)');
	var $elementsWithSidebarsContainer = $('#ait-elements-with-sidebars-background');
	var $elementsWithSidebarsStartBoundary = $sortableArea.find('.ait-sidebars-boundary-start');
	var $elementsWithSidebarsEndBoundary = $sortableArea.find('.ait-sidebars-boundary-end');
	var $elementsWithDetachedOptions = [];

    var changesMade = false;

	/**
	 * ait.admin.options.elements.Data
	 *
	 * Handle data manipulation for Elements and other on "Pages Options" page
	 */
	var data = ait.admin.options.elements.Data = {



        changesMade: function()
        {
            changesMade = true;
        },



		save: function()
		{
			ait.admin.publish('ait.options.save', ['working']);

            data.saveEditors();

            var serializedData;
            serializedData = data.serializePageOptions();
            serializedData += '&' + data.serializeUsedUnsortableElements();
            serializedData += '&' + data.serializeUsedSortableElements();
            serializedData = data.postprocessFormData(serializedData);

            ait.admin.ajax.post("savePagesOptions", serializedData, function(response)
            {
                if (response.success)
                    ait.admin.publish('ait.options.save', ['done']);
                else
                    ait.admin.publish('ait.options.save', ['error']);
            });

            changesMade = false;
		},



        saveEditors: function()
        {
            try {
                $.each(tinyMCE.editors, function(i, ed)
                {
                    if (!ed.isHidden())
                        ed.save();
                });
            } catch (ex) {
            }
        },



        serializePageOptions: function()
        {
            return $('#ait-options-form').serialize();
        },




        serializeUsedUnsortableElements: function()
        {
            var serializedUsedUnsortableElements = '';

            $unsortableArea.find('> .ait-element').each(function(i, e)
            {
                var $element = $(this);

                if (i > 0) {
                    serializedUsedUnsortableElements += '&';
                }

                serializedUsedUnsortableElements += data.serializeElementContent($element);
            });

            return serializedUsedUnsortableElements;
        },




        serializeUsedSortableElements: function()
        {
            var serializedUsedSortableElements = '';

            $sortableArea.find('> .ait-element').each(function(i, e)
            {
                var $element = $(this);

                if (i > 0) {
                    serializedUsedSortableElements += '&';
                }

                serializedUsedSortableElements += data.serializeElementContent($element);

                if (ui.isColumnsElement($element)) {
                    ui.getElementContent($element).find('.ait-element').each(function(j, el)
                    {
                        serializedUsedSortableElements += '&';
                        serializedUsedSortableElements += data.serializeElementContent($(this));
                    });
                }
            });

            return serializedUsedSortableElements;
        },



        serializeElementContent: function($element)
        {
            var $elementContent = ui.getElementContent($element);

            var $formInputs = $elementContent.find('input, textarea, select');

            if (ui.isColumnsElement($element)) {
                // do not serialize form inputs of element opened in columns editor
                $formInputs = $formInputs.not('.ait-columns-editor input, .ait-columns-editor textarea, .ait-columns-editor select ')
            }

            return $formInputs.serialize();
        },



        postprocessFormData: function(formData)
        {
            return formData.replace(/__[a-zA-Z0-9]+__/g, function(x)
            {
                return x.replace(/_/g, '');
            });
        },




		removeElement: function()
		{
            data.changesMade();
		},



		updateSortableElementPositionInfo: function($element)
		{
            var $elementContent = ui.getElementContent($element);

            if (ui.isElementInColumn($element)) {
                var $columnsElement = ui.getColumnsElementContainingElement($element);
                var $column = $element.parents('.ait-column');
            }

            var $columnsElementIndexInput = $elementContent.find('[name*="@columns-element-index"]');
            if ($columnsElement !== undefined) {
                $columnsElementIndexInput.attr('value', ui.getElementIndex($columnsElement));
            } else {
                $columnsElementIndexInput.attr('value', ''); // not in columns element
            }

            var $columnsElementColumnIndexInput = $elementContent.find('[name*="@columns-element-column-index"]');
            if ($columnsElement !== undefined) {
                $columnsElementColumnIndexInput.attr('value', $column.index());
            } else {
                $columnsElementColumnIndexInput.attr('value', ''); // not in column
            }
		},



        createElementContent: function($element)
        {
            var $elementContent = ui.getElementContentPrototype($element).clone();
            $elementContent.attr('id', $elementContent.attr('id').replace('-prototype', ''));
            $usedElementsContentsArea.append($elementContent);
        },



        updateElementIndexes: function($element)
        {
            var index = data.getNextElementIndex();


            var $elementContent = ui.getElementContent($element);

            var eid = ait.admin.utils.getDataAttr($element, 'element-id').replace(/__\d+__/, "__" + index + "__");

            $element.attr('id', eid);
            $element.data('ait-element-id', eid);
            $element.data('ait-element-content-id', eid + '-content');

			var $previewScript = $element.find('script');
			if ($previewScript.length > 0) {
				var previewScriptString = $previewScript.html().replace(/__\d+__/g, "__" + index + "__");
				$previewScript.html(previewScriptString);
			}

            $elementContent.attr('id', eid + '-content');
            $elementContent.data('ait-element-id', eid);

            var $attributesSelector = '[name*="__"], [id*="__"], [idtemplate*="__"], [nametemplate*="__"],  [class*="__"], [for*="__"], [href*="__"], [data-editor*="__"]';
            $elementContent.find($attributesSelector)
                .add($.each($element.find('iframe'), function() { return $(this).contents().find($attributesSelector); } ))
                .each(function(j, e)
                {
                    var $e = $(e);
                    var name = $e.attr('name');
                    var id = (!$e.hasClass('ait-element')) ? $e.attr('id') : undefined;
                    var forAttr = $e.attr('for');
                    var href = $e.attr('href');
                    var idtemplate = $e.attr('idtemplate');
                    var nametemplate = $e.attr('nametemplate');
                    var dataEditor = $e.attr('data-editor');
                    var columnsElementIndex = ($e.attr('name') && $e.attr('name').indexOf("@columns-element-index") > 0) ? $e.attr('value') : undefined;
                    var columnsElementColumnIndex = ($e.attr('name') && $e.attr('name').indexOf("@columns-element-column-index") > 0) ? $e.attr('value') : undefined;

                    if (name !== undefined)
                        $e.attr('name', name.replace(/__\d+__/, "__" + index + "__"));

                    if (id !== undefined) {
                        $e.attr('id', id.replace(/__\d+__/, "__" + index + "__"));
                    }

                    if (forAttr !== undefined)
                        $e.attr('for', forAttr.replace(/__\d+__/, "__" + index + "__"));

                    if (href !== undefined)
                        $e.attr('href', href.replace(/__\d+__/, "__" + index + "__"));

                    if (idtemplate !== undefined)
                        $e.attr('idtemplate', idtemplate.replace(/__\d+__/, "__" + index + "__"));

                    if (nametemplate !== undefined)
                        $e.attr('nametemplate', nametemplate.replace(/__\d+__/, "__" + index + "__"));

                    if (dataEditor !== undefined)
                        $e.attr('data-editor', dataEditor.replace(/__\d+__/, "__" + index + "__"));

                    if (columnsElementIndex !== undefined) {
                        // $e refers to hidden input of element appended in column
                        var $columnsElement = $($e.parents('.ait-element').get(1)); // get columns element if exists

                        if ($columnsElement.length) {
                            $e.attr('value', ui.getElementIndex($columnsElement));
                        } else {
                            $e.attr('value', ''); // not in column
                        }
                    }

                    if (columnsElementColumnIndex !== undefined) {
                        // $e refers to hidden input of element appended in column
                        var $columnElement = $($e.closest('.ait-column')); // get column if exists
                        if ($columnElement.length) {
                            $e.attr('value', $columnElement.index());
                        } else {
                            $e.attr('value', ''); // not in column
                        }
                    }
                });

        },



		updateColumnsElementLayout: function($columnsElement, layout)
		{
            var $elementContent = ui.getElementContent($columnsElement);

			if (layout.gridCssClass) {
                $elementContent.find('input[id*="grid-css-class"]').attr('value', layout.gridCssClass);
			}

			if (layout.columnsCssClasses.length) {
                $elementContent.find('input[id*="columns-css-classes"]').attr('value', layout.columnsCssClasses);
			}

            $.each($columnsElement.find('.ait-row-content .ait-element'), function() {
                data.updateSortableElementPositionInfo($(this));
            });
            ui.updateElementsWithSidebarsBackground();
            data.changesMade();
		},



        getNextElementIndex: function()
        {
			return '_e' + Math.random().toString(16).slice(2);
        },



		getOpt: function(optId, locale, opt, suffix) {
			opt = '#' + optId.replace('__opt__', opt);
			opt = (typeof suffix === 'undefined') ? opt : opt + '-' + suffix;
			return ($(opt).length > 0 ? $(opt) : $(opt + '-' + locale));
		}

	};



	/**
	 * ait.admin.options.elements.Ui
	 *
	 * Binds events and manipulate with UI of the elements
	 */
	var ui = ait.admin.options.elements.Ui = {



		$draggedElement: null,
		draggableSize: { width: 0, height: 0 },
		transitionEnd: 'webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',



		init: function()
		{
			ui.bindEvents();
			ui.basicAdvancedTabs();
			ui.initColumnsElements();
			ui.sortables();
			ui.droppables();
			ui.draggables();
			ui.clickables();
			ui.checkAvailableElementsDraggability();
			ui.toggleUnsortables();
			//ui.resetCursorPositionOfDraggables();
			ui.initSidebarsBoundaryElements();
		},



		bindEvents: function()
		{
			$('.ait-save-pages-options').on('click', ui.save);
			$context.on('click', '#ait-used-elements .ait-element-handler, #ait-used-elements .ait-element-edit, #ait-used-elements .ait-element-close, #ait-used-elements .ait-element-preview', ui.onToggleElement);
			$context.on('click', '#ait-used-elements .ait-element-remove', ui.onRemoveElement);
			$context.on('click', '#ait-used-elements span.ait-element-user-description', ui.onElementUserDescriptionClick);
			$context.on('blur', '#ait-used-elements input.ait-element-user-description', ui.onElementUserDescriptionBlur);
			$context.on('keydown', '#ait-used-elements input.ait-element-user-description', ui.onElementUserDescriptionKeydown);
			$context.on('change', 'select[name*="sidebar"]', ui.initSidebarsBoundaryElements);
			$window.on('resize', ui.checkAvailableElementsDraggability);
			//$window.on('resize', ui.resetCursorPositionOfDraggables);
            $window.on('beforeunload', function() {
                if (changesMade) {
                    return $('.ait-options-page').data('unsaved-changes-message');
                }
            });
		},



		sortables: function()
		{
			$sortableArea.sortable({
				connectWith: '.ait-column-content',
				placeholder: 'ait-used-elements-droppable-placeholder',
				handle: '.ait-element-handler',
                appendTo: "#ait-available-elements",
				// forcePlaceholderSize: true,
				distance: 15,
				scrollSensitivity: 50,
				/*cursorAt: {
					left: Math.floor(ui.draggableSize.width / 2),
					top: Math.floor(ui.draggableSize.height / 2)
				},*/
				start: ui.onSortingStart,
				stop: ui.onSortingStop
			});

			if ($.support.touch) {
				$sortableArea.sortable({
					handle: '.ait-touch-handle'
				});
			}

			if (ui.isResponsive(640)) {
				$sortableArea.sortable({
					containment: '#wpbody'
				});
			}

		},



		droppables: function()
		{
			$sortableArea.droppable({
				accept: '.ait-element',
				activate: function(e, jUi)
				{
					if (!$(this).children().length)
						$(this).addClass('ait-droppable-placeholder-active');
				},
				deactivate: function(e, jUi)
				{
					$(this).removeClass('ait-droppable-placeholder-active');
				},
				drop: function(e, jUi)
				{
                    var $droppedElement = $(jUi.draggable);

					$droppedElement.removeClass('in-column');
					$droppedElement.css({'width': 'auto', 'height': 'auto'});
                    var d = ait.admin.utils.getDataAttr($droppedElement, 'element');
                    if (!d.clone) {
                        var $original = $("#" + ait.admin.utils.getDataAttr($droppedElement, 'elementId'));
                        if (!$original.hasClass('ait-used-element')) {
                            $original.addClass('hidden');
                        }

                    }
				}
			});
		},



		draggables: function()
		{


			$availableElements.draggable({
                connectToSortable: $sortableArea,
				handle: '.ait-element-handler',
				helper: 'clone',
                appendTo: '#ait-available-elements',
				revert: "invalid",
				/*cursorAt: {
					left: Math.floor(ui.draggableSize.width / 2),
					top: Math.floor(ui.draggableSize.height / 2)
				},*/
				start: function(e, jUi)
				{
					//$(jUi.helper).css('width', Math.floor($('#ait-available-elements .ait-element').width() * 0.48));
					$availableElements.find('.mceEditor').remove();
					$availableElements.find('.ait-opt-editor textarea').show();
					ui.$draggedElement = $(this);
					ui.$draggedElement.find('.mceEditor').remove();
				}
			});

		},



		clickables: function()
		{

			$availableElements.on('click', function() {

				/* Before drop */

				$availableElements.find('.mceEditor').remove();
				$availableElements.find('.ait-opt-editor textarea').show();
				ui.$draggedElement = $(this).clone();
				ui.$draggedElement.find('.mceEditor').remove();

				var $droppedElement = ui.$draggedElement;
				$droppedElement.addClass('ait-used-element');
				$droppedElement.css('opacity', 0);

				/* Drop */

				$droppedElement.appendTo($sortableArea);

				/* After drop */

				 $('html, body').animate({
					scrollTop: $droppedElement.offset().top
				}, 700);

				var animDelay = ($(window).scrollTop() + $(window).height() > $(document).height() - 200) ? 0 : 500;

				setTimeout(function() {
					$droppedElement.animate({
						opacity: 1
					}, 200);
				}, animDelay);

				ui.initElement($droppedElement);
				$droppedElement.find('.ait-element-user-description').attr('title', ait.admin.l10n.elementUserDescriptionPlaceholder);
				data.updateSortableElementPositionInfo($droppedElement);
				data.changesMade();

				//ui.openElement($droppedElement);

			});

		},



		toggleUnsortables: function()
		{

			var $button = $('.toggle-unsortables');
			var	$unsortables = $('#ait-used-elements-unsortable');
			var $storage = (typeof(Storage) !== "undefined") ? true : false;

			var toggle = function() {
				$button.toggleClass('open');
				$unsortables.toggleClass('open');
				$unsortables.slideToggle('fast');

				if ($storage) {
					localStorage.aitToggleUnsortables = $button.hasClass('open') ? 'open' : 'close';
				}
			}

			if ($storage && localStorage.getItem('aitToggleUnsortables') !== null) {

				if ((localStorage.aitToggleUnsortables == 'open' && !$button.hasClass('open')) ||
					(localStorage.aitToggleUnsortables == 'close' && $button.hasClass('open'))) {
					toggle();
				}
			}

			$button.on('click', toggle);

		},



		basicAdvancedTabs: function()
		{

			// tabs

			$context.on('click', '.ait-controls-tabs a', function(e)
			{
				e.preventDefault();
				var $this = $(this);
				var $li = $this.parent();
                if ($li.hasClass('ait-ba-tab-active'))
                    return false;

				var $panels = $this.closest('.ait-element-controls').children('.ait-controls-tabs-panel');

				var target = $this.attr('href');

				$li.siblings().removeClass('ait-ba-tab-active');
				$li.addClass('ait-ba-tab-active').blur();


				$panels.hide();

				$(target).fadeIn('fast');

				/* Update or init options controls (inputs) */
				ui.updateRangeInputs($(target));

                //ui.setWindowScroll();
				ui.updateElementsWithSidebarsBackground();
			});



			// enabling / disabling advanced options

			$context.find('.ait-element-content:not(.no-tabs)').each(function()
			{
				var $this = $(this);
			    var $tabs = $this.find('ul.ait-controls-tabs li');
			    var $panels = $this.find('.ait-controls-tabs-panel');
				$panels.hide();
				$panels.eq(0).show();
				$tabs.eq(0).addClass('ait-ba-tab-active');

                ui.updateElementAdvancedTab($this.find('.ait-toggle-advanced'));

			});

			$context.on('change', '.ait-toggle-advanced', function(e){
                ui.updateElementAdvancedTab($(this));
			});
		},



        updateElementAdvancedTab: function($advToggle)
        {
            var $adv = $advToggle.parents('.ait-options-advanced');
            if ($advToggle.val() == 1) {
                $adv.removeClass('advanced-options-disabled');
                var $inputs = $adv.find('[readonly], [disabled]').not('.ait-toggle-advanced');
                $inputs.removeAttr('readonly');
                $inputs.removeAttr('disabled');
                $adv.find('.disabled').removeClass('disabled');
            }

            else if ($advToggle.val() == 0) {
                $adv.addClass('advanced-options-disabled');
                var $inputs = $adv.find('input, select, textarea, button').not('.ait-toggle-advanced');
                $inputs.attr({
                    'disabled': 'disabled',
                    'readonly': 'readonly'
                });
            }

			$adv.find('select').trigger('chosen:updated.chosen');
        },


		initElement: function($element)
		{
            data.createElementContent($element);
            data.updateElementIndexes($element);

			var $previewScript = $element.find('script');
			if ($previewScript.length > 0) {
				var $script   = document.createElement("script");
				$script.type  = "text/javascript";
				$script.text  = $previewScript.html();
				$previewScript.replaceWith($script);
			}

            var $elementContent = ui.getElementContent($element);
			ait.admin.options.Ui.bindEvents($elementContent);
			ait.admin.options.Ui.enhancedInputs($elementContent);
			ait.admin.options.Ui.switchableSections($elementContent);

			if (ui.isColumnsElement($element)) {
				ui.initColumnsElement($element);
			}

			$element.addClass('ait-used-element');
		},


		/**
		 * Init extra functionality of Columns elements
		 */
		initColumnsElements: function()
		{
			$.each($('[class*="ait-used-element"]'), function()
			{
				if (ui.isColumnsElement($(this))) {
					ui.initColumnsElement($(this));
					ui.openElement($(this));
				}
			});
		},



		/**
		 * Init extra functionality of Columns element
		 *
		 * @param $columnsElement
		 */
		initColumnsElement: function($columnsElement)
		{
			ui.initColumnsElementRow($columnsElement);
			ui.initColumnsElementColumns($columnsElement);
			ui.initColumnsElementTopPanel($columnsElement);
			ui.initColumnsElementEditor($columnsElement);
		},



		initColumnsElementRow: function($columnsElement)
		{
			var $row = ui.getElementContent($columnsElement).find('.ait-row-content');

			$row.sortable({
				axis: 'x',
				handle: '.ait-column-handle',
				forceHelperSize: true,
				forcePlaceholderSize: true,
                start: function()
                {
                    ui.toggleColumnsEditor($columnsElement);
                },
				stop: function()
				{
					data.updateColumnsElementLayout($columnsElement, {
						columnsCssClasses: $row.children().map(function()
						{
							return $(this).data('ait-column-css-class');
						}).get()
					});
				}
			});

			$row.find('.ait-column-handle').disableSelection();
		},



		initColumnsElementColumns: function($columnsElement)
		{
			var $columnsElementElements = $('.in-column').filter(function() {
				return $(this).data('ait-columns-element-index') == ui.getElementIndex($columnsElement);
			});

			$.each(ui.getElementContent($columnsElement).find('.ait-column'), function(i) {
				ui.initColumn($(this), $columnsElementElements.filter(function() {
					return $(this).data('ait-columns-element-column-index') == i;
				}));
			});
		},



		initColumnsElementTopPanel: function($columnsElement)
		{
			$(ui.getElementContent($columnsElement).find('.change-columns')).click(function(e)
			{
				e.preventDefault();

				var gridCssClass = $(this).data('ait-grid-css-class').trim();

				var columnsCssClasses = $($(this).data('ait-columns-css-classes').split(",")).map(function() {
					return this.trim();
				}).get();

                var columnsNames = $($(this).data('ait-columns-names').split(",")).map(function() {
                    return this.trim();
                }).get();

				var $row = $columnsElement.find('.ait-row-content');
				var columns = $row.children();

				$.each(columnsCssClasses, function(i, columnCssClass)
				{
					if (columns.length > i) {
						$(columns[i]).removeClass();
					} else {
						// append new column
						var $column = $('<div>');
						$row.append($column);
						$column.html('<div class="ait-column-handle"><h4></h4></div><div class="ait-column-content"></div>');
					}

					$column = $($row.children().get(i));
					$column.addClass('ait-column');
					$column.addClass(columnCssClass);
					$column.find('.ait-column-handle h4').html(columnsNames[i]);
					$column.data('ait-column-css-class', columnCssClass);

					ui.initColumn($column);
				});

                ui.triggerAllEditors(false);


				// remove extra columns and move elements from removed columns to last column
				var index = $row.children().size();
                var lastRow = columnsCssClasses.length - 1;
				while (index-- > columnsCssClasses.length) {
					var columnElements = $(columns[index]).find('.ait-column-content').children();
					$(columns[lastRow]).find('.ait-column-content').append(columnElements);
					$(columns[index]).remove();
				}

				$row = $(ui.getElementContent($columnsElement).find('.ait-row-content'));
				$row.removeClass().addClass('ait-row-content').addClass(gridCssClass);
				$row.data('ait-grid-css-class', gridCssClass);

				data.updateColumnsElementLayout($columnsElement, {
					gridCssClass: gridCssClass, columnsCssClasses: columnsCssClasses
				});

                ui.triggerAllEditors(true);
			});
		},



		initColumnsElementEditor: function($columnsElement)
		{
			$('.ait-columns-editor-remove').click(function(e) {
				e.preventDefault();
				ui.toggleColumnsEditor($columnsElement);
			})
		},



		initColumn: function($column, $columnElements)
		{


			var $columnContent = $column.find('.ait-column-content');

			$columnContent.sortable({
				connectWith: '.ait-column-content, #ait-used-elements-sortable',
				placeholder: 'ait-used-elements-droppable-placeholder',
				// containment: "document",
				forcePlaceholderSize: true,
				distance: 15,
				scrollSensitivity: 50,
				receive: function(e, jUi) {
					if(!jUi.item.hasClass('ait-element-columnable'))
						jUi.sender.sortable('cancel');
				},
				start: ui.onSortingStart,
				stop: ui.onSortingStop
			});

			if ($columnElements !== undefined) {
				$.each($columnElements, function()
				{
					$(this).removeClass('hidden');
					$(this).find('.ait-element-user-description').removeAttr('title');
					$columnContent.append($(this));
				});
			}

		},



		updateRangeInputs: function($content)
		{
			var $rangeInputs = $content.find('.ait-opt-range input');
			$.each($rangeInputs, function(i, input) {
				var initVal = $(input).data('initval');
				var rangeinputData = $(input).data('rangeinput');
				var slideVal;
				if(rangeinputData){
					slideVal = rangeinputData.getValue()
					if (isNaN(slideVal) && typeof initVal !== 'undefined') {
						$(input).data('rangeinput').setValue(initVal);
					}
				}
				if (isNaN($(input).val()) && typeof initVal !== 'undefined') {
					$(input).val(initVal);
				}
			});
		},



		onRemoveElement: function(e)
		{
			e.preventDefault();
			e.stopPropagation();
			var $this = $(this);

			if (!confirm(ait.admin.l10n.confirm.removeElement))
				return false;

			var $elementToRemove = $this.closest('.ait-element');

			$elementToRemove.slideToggle('fast', function()
			{
                ui.removeElement($elementToRemove);
                data.removeElement(); // call only if element is completely removed
                ui.setWindowScroll();
                ui.updateElementsWithSidebarsBackground();
			});

		},



        onElementUserDescriptionClick: function(e)
        {
            var $this = $(this);

            e.preventDefault();
            e.stopPropagation();

            var value = '';
            if ($this.html() != ait.admin.l10n.elementUserDescriptionPlaceholder) {
                value = $this.html();
            }

            var $input = $('<input />', {'type': 'text', 'class': $this.attr('class'), 'title': ait.admin.l10n.elementUserDescriptionPlaceholder,  'value': value});
            $input.click(function(e) { e.stopPropagation(); });
            ait.admin.options.Ui.enhancedInputs($input);
            $(this).parent().append($input);
            $(this).remove();
            $input.focus();
        },




        onElementUserDescriptionBlur: function(e)
        {
            var $this = $(this);

            var value = $this.val().trim();

            var $element;

            if ($this.parents('.ait-columns-editor').length) {
                $element = $this.closest('.ait-columns-editor').data('element');
            } else {
                $element = $this.closest('.ait-element');
            }

            var $elementContent = ui.getElementContent($element);
            $elementContent.find("input[name*='[@element-user-description]']").val(value);

			var cssClasses = 'ait-element-user-description';
			if (value != '') {
				cssClasses += ' element-has-user-description';
			}

            var $span = $('<span />', { 'class': cssClasses, 'title': ait.admin.l10n.elementUserDescriptionPlaceholder}).text(value);
            $this.parent().append($span);

			if ($this.parents('.ait-element-content').length) {
				var $anotherSpan = $this.parents('.ait-element').find('.ait-element-user-description');
				$anotherSpan.attr('css', cssClasses);
				$anotherSpan.text(value);
			} else {
				var $anotherSpan = $('#' + $this.parents('.ait-element').data('ait-element-content-id')).find('.ait-element-user-description');
				$anotherSpan.attr('css', cssClasses);
				$anotherSpan.text(value);
			}

            $this.remove();
        },



        onElementUserDescriptionKeydown: function(e)
        {
            if (e.keyCode == 13) {
                e.stopPropagation();
                $(this).blur();
            }
        },



        removeElement: function($element)
        {
            if (ui.isColumnsElement($element)) {
                var $columnsElementContent = ui.getElementContent($element);
                var $elementsInColumnsElement = $columnsElementContent.find('.ait-element');
                $.each($elementsInColumnsElement, function() {
                    ui.removeElement($(this));
                });
            } else if (ui.isElementInColumn($element)) {
                ui.toggleColumnsEditor(ui.getColumnsElementContainingElement($element));
            }


            var elData = ait.admin.utils.getDataAttr($element, 'element');

            if (!elData.clone) {
                $availableElements.filter(':not(.clone)').each(function(i, el)
                {
                    var $e = $(el);
                    var type = ait.admin.utils.getDataAttr($e, 'element').type;
                    if (elData.type == type) {
                        $e.removeClass('hidden');
                    }
                });
            }

            var $elementContent = ui.getElementContent($element);
            $elementContent.remove();
            $element.remove();
        },



		closeAllElements: function(closeColumnsElements)
		{
			var $open = $('.ait-element.open');

			if ($open.length) {

                $.each($open, function() {
                    var $element = $(this);
                    if (ui.isColumnsElement($element)) {
                        ui.toggleColumnsEditor($element);
                    }
                });

                if (!closeColumnsElements) {
                    $open = $open.filter(function() {
                        return !ui.isColumnsElement($(this));
                    });
                }

                ui.triggerAllEditors(false);
                $open.removeClass('open');


				$usedElementsContentsArea.append($open.find('> .ait-element-content').hide());

                ui.updateElementsWithSidebarsBackground();

                if (ui.$draggedElement.hasClass('ait-used-element')) {
                    var elementPosition = parseInt(ui.$draggedElement.offset().top) - $window.height() / 2;
                    //ui.setWindowScroll(elementPosition);
                } else {
                    //ui.setWindowScroll();
                }

                $sortableArea.sortable('refreshPositions');
                $sortableArea.sortable('refresh');
            }
		},



		onToggleElement: function(e)
		{
            e.preventDefault();
            e.stopPropagation();

			var $element = $(this).closest('.ait-element');

            if ($element.hasClass('open')) {
                ui.closeElement($element);
            } else {
                ui.openElement($element);
            }

			$('.ait-element-user-description').blur();
		},



        openElement: function($element)
        {

            ui.triggerAllEditors(false, ui.getElementContent($element));

			if (ui.isElementInColumn($element)) {
				$element.parents('.ait-column-content').sortable("option", "disabled", true);
			}

			var $elementContent = ui.getElementContent($element);
			var updatePopupDimensions = function() {
				if (!$element.hasClass('no-popup') && !ui.isResponsive(782)) {
					$elementContent.css({'width': '', 'height': ''});
					$elementContent.height(2 * Math.round($elementContent.height() / 2));
					$elementContent.width(2 * Math.round($elementContent.width() / 2));
				}
			}

			$element.append($elementContent);
			ui.triggerAllEditors(true, ui.getElementContent($element));

			if ($element.hasClass('no-popup')) {
				$elementContent.slideToggle('fast', function(){
					$element.trigger('open');
				});
			} else {
				$elementContent.show().outerWidth();
				$element.addClass('open').one(ui.transitionEnd, function(e) {
					$(this).off(ui.transitionEnd);
					$element.trigger('open');
				});
			}

			$element.on('open', function() {
				if (!$element.hasClass('open')) {
					$element.addClass('open');
				}

				//ui.setWindowScroll();
				updatePopupDimensions();

				ui.updateElementsWithSidebarsBackground();
				ui.preventPageScrollingWhenScrollingElementPopup();

				/* Update or init options controls (inputs) */

				ui.updateRangeInputs($elementContent);

				var $mapInput = $elementContent.find('.ait-opt-maps-preview');
				$mapInput.width($mapInput.parent().width()).height(($mapInput.parent().width()/2));
				$elementContent.find('.ait-opt-maps-tools').trigger('mapinit');
				$elementContent.find('.ait-opt-maps-address input[type="button"]').trigger("click");
			});

			$window.on('resize', updatePopupDimensions);

        },



        closeElement: function($element)
        {
			var $elementContent = ui.getElementContent($element);

			var $mceFullscreen = $elementContent.find('.mce-i-fullscreen').parent();
			if ($mceFullscreen.length > 0 && $mceFullscreen.parent().hasClass('mce-active')) {
				$mceFullscreen.trigger('click');
			}
            ui.triggerAllEditors(false, $elementContent);

			if (ui.isElementInColumn($element)) {
				$element.parents('.ait-column-content').sortable("option", "disabled", false);
			}

			if ($element.hasClass('no-popup')) {
				$elementContent.slideToggle('fast', function() {
					$element.addClass('close');
					$element.trigger('close');
				});
			} else {
				$element.addClass('close').one(ui.transitionEnd, function() {
					$(this).off(ui.transitionEnd);
					$element.trigger('close');
				});
			}

			$element.on('close', function() {
				$elementContent.hide();
				$elementContent.css({'width': '', 'height': ''});
				$usedElementsContentsArea.append($elementContent);
				ui.updateElementsWithSidebarsBackground();
				$element.removeClass('open close');
			});
        },



		toggleColumnsEditor: function($columnsElement, $elementToOpenInEditor)
		{
            var $columnsEditor = $columnsElement.find('.ait-columns-editor');
			var $elementContent;

			if ($columnsEditor.hasClass('open')) {
                $elementContent = $columnsEditor.find('.ait-columns-editor-element-options').find('.ait-element-content');
                ui.triggerAllEditors(false, $elementContent);
                $elementContent = $elementContent.detach();
				$columnsEditor.hide();
				$elementContent.hide();
                var $el = $($columnsEditor.data('element'));
                $elementsWithDetachedOptions.splice($.inArray($el.attr('id'), $elementsWithDetachedOptions), 1); // delete element
                $usedElementsContentsArea.append($elementContent);
                $el.removeClass('open');
				$columnsEditor.removeData('element');
                ui.triggerAllEditors(true, $elementContent);
                $columnsEditor.removeClass('open');
			}

			if ($elementToOpenInEditor) {
                $elementsWithDetachedOptions.push($elementToOpenInEditor.attr('id'));
                $elementContent = ui.getElementContent($elementToOpenInEditor);
                ui.triggerAllEditors(false, $elementContent);
                $columnsEditor.find('.ait-columns-editor-element-title > h4').html($elementToOpenInEditor.find('.ait-element-title > h4').html());
                var elementUserDescription = $elementContent.find("input[name*='[@element-user-description]']").val();
                var $elementUserDescription = $columnsEditor.find('.ait-columns-editor-element-title > .ait-element-user-description');
				$elementUserDescription.text(elementUserDescription);
                if (elementUserDescription != '') {
					$elementUserDescription.addClass('element-has-user-description');
				} else {
					$elementUserDescription.removeClass('element-has-user-description');
                }
                $columnsEditor.find('.ait-columns-editor-element-options').append($elementContent);
                $elementContent.show();
                $columnsEditor.show();
                $columnsEditor.data('element', $elementToOpenInEditor);
                ui.triggerAllEditors(true, $elementContent);
                $columnsEditor.addClass('open');

                // init map if columnable element is opened
                var $map = $columnsElement.find('.ait-opt-maps-tools');
                $map.trigger('mapinit');
            }

			ui.updateElementsWithSidebarsBackground();
            //ui.setWindowScroll();
        },



		triggerAllEditors: function(creatingEditor, $context)
		{
            var $textareas = [];
            if ($context !== undefined) {
                $textareas = $context.find('.ait-opt-editor textarea');//.not('[name*="1000"]');
            } else {
                $textareas = $('.ait-opt-editor textarea');//.not('[name*="1000"]');
            }

			$textareas.each(function(index, textarea)
			{
				var editor;
				try{
					editor = tinyMCE.EditorManager.get(textarea.id);
				}catch(e){
					editor = false;
				}

				if (creatingEditor && !editor) {
                    try {
						ait.admin.ajax.post("tinyMceEditor", {'content': $(textarea).val(), 'id': textarea.id, 'textarea_name': $(textarea).attr('name')},  function(response) {
							var $wrapper = $(textarea).parent();
							$(textarea).replaceWith(response);
							var editor = tinyMCEPreInit.mceInit[textarea.id];
							if(editor && tinyMCE){
								editor.init_instance_callback = function() {
									quicktags( tinyMCEPreInit.qtInit[textarea.id] );
									QTags._buttonsInit();
									var $container = $(response);
									$wrapper.show();
									// hotfix: visual/html tabs of text element didn't work in columns
									// we have to find editor buttons within whole document because $container object is added by ajax and has no click event binded
									if ($container.hasClass('html-active')) {
										$('#'+$container.get(0).id).find('.switch-html').trigger('click');
									} else {
										$('#'+$container.get(0).id).find('.switch-tmce').trigger('click');
									}
									ui.updateElementsWithSidebarsBackground();
								};
								tinyMCE.init(editor);
							}else{
								quicktags( tinyMCEPreInit.qtInit[textarea.id] );
								QTags._buttonsInit();
								var $container = $(response);
								$wrapper.show();
								// hotfix: visual/html tabs of text element didn't work in columns
								// we have to find editor buttons within whole document because $container object is added by ajax and has no click event binded
								console.log('edge case of editor initialization');
								if ($container.hasClass('html-active')) {
									$('#'+$container.get(0).id).find('.switch-html').trigger('click');
								} else {
									$('#'+$container.get(0).id).find('.switch-tmce').trigger('click');
								}
								ui.updateElementsWithSidebarsBackground();
							}
						});
                    } catch(e) {
                        if (typeof console == "object") {
                            console.log(e);
                        }
                    }


				} else if (editor) {
					try {
                        if (textarea.id in QTags.instances) {
                            delete QTags.instances[textarea.id];
                        }

						var htmlModeValue = null;
						if ($(textarea).closest('.wp-editor-wrap').hasClass('html-active')) {
							htmlModeValue = $(textarea).val();
							editor.remove();
							// do not overwrite text entered in html mode with text from visual mode
							$(textarea).val(htmlModeValue);
						} else {
							editor.remove();
						}

						$(textarea).closest('.ait-opt-wrapper').append(textarea).find('.wp-editor-wrap, script').remove();
						$(textarea).closest('.ait-opt-wrapper').hide();
                    } catch (e) {
                        if (typeof console == "object") {
                            console.log(e);
                        }
                    }

				}
			});
		},



		initSidebarsBoundaryElements: function()
		{
			var atLeastOneSidebarSet = false;
			$.each($('select[name*="sidebar"]'), function()
			{
				if ($(this).val() != 'none') {
					atLeastOneSidebarSet = true;
					return false;
				}
				return true;
			});

			if (atLeastOneSidebarSet) {
				$elementsWithSidebarsStartBoundary.slideDown('fast');
				$elementsWithSidebarsEndBoundary.slideDown('fast');
				$elementsWithSidebarsContainer.show();
				ui.updateElementsWithSidebarsBackground();
			} else {
				$elementsWithSidebarsStartBoundary.slideUp('fast');
				$elementsWithSidebarsEndBoundary.slideUp('fast');
				$elementsWithSidebarsContainer.hide();
			}

		},



        save: function(e)
        {
            e.preventDefault();
            data.save();
        },



		checkAvailableElementsDraggability: function()
		{
			var $draggablesInstance = $availableElements.draggable('instance');

			if (ui.isResponsive(1190)) {
				if (typeof $draggablesInstance !== 'undefined') {
					$availableElements.draggable('destroy');
				}
			} else {
				if (typeof $draggablesInstance === 'undefined') {
					ui.draggables();
				}
			}
		},



		onSortingStart: function(e, jUi)
		{
            ui.$draggedElement = jUi.item;

			var $draggedElementHelper = jUi.helper;

			if (ui.isElementInColumn(ui.$draggedElement)) {
				$draggedElementHelper.appendTo($draggedElementHelper.parents('.ait-opt-columns-main'));
			} else {
				if (!ui.isColumnableElement(ui.$draggedElement)) {
					$('.ait-element-columns').addClass('ait-drop-disabled');
				}
			}

		    // $draggedElementHelper.css('width', Math.floor($('#ait-used-elements').width()) * 0.65);
			if (!ui.isResponsive(640)) {
				$draggedElementHelper.css('width', $draggedElementHelper.find('.ait-element-handler').width() * 0.55);
			} else {
				$draggedElementHelper.css('width', $draggedElementHelper.find('.ait-element-handler').width() - 66);
			}
			// $draggedElementHelper.css('min-width', 320);
			// $sortableArea.find('.ait-used-elements-droppable-placeholder').outerHeight($draggedElementHelper.find('.ait-element-handler').height());
			$sortableArea.find('.ait-used-elements-droppable-placeholder').css('background', $draggedElementHelper.find('.ait-element-icon').data('color'));

			$('#ait-used-elements-sortable').addClass('ait-dragging-start');
            // ui.closeAllElements(!$(jUi.helper).hasClass('ait-element-columnable'));
		},



		onSortingStop: function(e, jUi)
		{
            var $droppedElement = $(jUi.item);

			$('#ait-used-elements-sortable').removeClass('ait-dragging-start');
			$droppedElement.addClass('ait-dragging-stop');

            if ($('.ait-sidebars-boundary-start').index() > $('.ait-sidebars-boundary-end').index()) {
                $(this).sortable('cancel');
                return;
            }

            var open = false;

            if (!$droppedElement.hasClass('ait-used-element')) {
                ui.initElement($droppedElement);
                open = true;
            }

            if (ui.isElementInColumn($droppedElement)) {
                $droppedElement.addClass('in-column');
                $droppedElement.removeClass('open');
				$droppedElement.find('.ait-element-user-description').removeAttr('title');
            } else {
                $droppedElement.removeClass('in-column');
				$droppedElement.find('.ait-element-user-description').attr('title', ait.admin.l10n.elementUserDescriptionPlaceholder);

				$('.ait-element-columns').removeClass('ait-drop-disabled');
            }

            data.updateSortableElementPositionInfo($droppedElement);

            ui.updateElementsWithSidebarsBackground();
            data.changesMade();

            if (open) {
                ui.openElement($droppedElement);
            }

			setTimeout(function() {
				$droppedElement.removeClass('ait-dragging-stop')
			}, 300);

		},



		updateElementsWithSidebarsBackground: function()
		{
            if ($elementsWithSidebarsContainer.length && $elementsWithSidebarsStartBoundary.length && $elementsWithSidebarsEndBoundary.length) {
                $elementsWithSidebarsContainer.css('top', $elementsWithSidebarsStartBoundary.position().top + $elementsWithSidebarsStartBoundary.height() / 2);
                $elementsWithSidebarsContainer.css('height', $elementsWithSidebarsEndBoundary.position().top - $elementsWithSidebarsStartBoundary.position().top);
            }
        },



	    resetCursorPositionOfDraggables: function()
		{
			var cursorX = Math.floor($('#ait-available-elements .ait-element').width() / 2);
			var cursorXSortable = Math.floor($('#ait-used-elements').width() * 0.48 / 2);

			// root sortable cursor position reset
			/*$sortableArea.sortable('option', 'cursorAt', {
				left: cursorXSortable
			});*/

			// every column sortable cursor position reset
			/*$.each($('.ait-options-content .ait-column-content'), function()
			{
				$(this).sortable('option', 'cursorAt', {
					left: cursorXSortable
				})
			});*/

			// available elements cursor position reset
			/*$availableElements.draggable('option', 'cursorAt', {
				left: cursorX
			});*/

		},



        setWindowScroll: function(y)
        {
            var currentY = $window.scrollTop();

            var maxY = $sortableArea.offset().top + $sortableArea.height() - $window.height() + ait.admin.options.Ui.pageBottomOffset;

            if (y == undefined) {
                y = currentY;
            }

            if (y > maxY) {
                y = maxY;
            }

            $window.scrollTop(y);
        },



		preventPageScrollingWhenScrollingElementPopup: function()
		{

			var $elementPopupContent = $(".ait-element:not(.no-popup).open .ait-element-content .ait-element-controls")

			$elementPopupContent.on("mousewheel DOMMouseScroll", function(e) {
				var scrollingDown = false;
				if (e.type == 'mousewheel') {
					scrollingDown = e.originalEvent.wheelDelta < 0;
				} else {
					scrollingDown =  e.originalEvent.detail > 0;
				}

				if (scrollingDown) {
					var elementPopupBottomScrolledPosition = $elementPopupContent.prop('scrollHeight') - $elementPopupContent.prop('clientHeight');
					var scrolledToElementsPopupBottom = $elementPopupContent.scrollTop() == elementPopupBottomScrolledPosition;
					if (scrolledToElementsPopupBottom) {
						e.preventDefault();
					}
				} else if ($elementPopupContent.scrollTop() == 0) {
					e.preventDefault();
				}
			});
		},



		isColumnableElement: function($element)
		{
			return $element.hasClass('ait-element-columnable')
		},



		isColumnsElement: function($element)
		{
			return $element.data('aitElementId') && $element.data('aitElementId').indexOf('columns') != -1
		},



        isElementInColumn: function($element)
        {
            return ($element.parents('.ait-element-content').get(0) !== undefined);
        },



        getColumnsElementContainingElement: function($element)
        {
            var $columnsElementContent = $($element.parents('.ait-element-content').get(0));
            var columnsElementId = $columnsElementContent.data('ait-element-id');
            return $('#' + columnsElementId);
        },


        getElementContentPrototype: function($element)
        {
            return $('#' + $element.data('ait-element-content-id') + '-prototype');
        },


        getElementContent: function($element)
        {
            return $('#' + $element.data('ait-element-content-id'));
        },


        getElementIndex: function($element)
        {
        	var index;
        	var elId = $element.attr('id') ? $element.attr('id') : $element.data('ait-element-id');
        	var rawIndex = elId.match(/__[_a-zA-Z0-9]+__/)[0];
        	var startsWith = function(haystack, needle) { return haystack.indexOf(needle, 0) == 0; }

        	index = rawIndex.match(/[a-zA-Z0-9]+/)[0];

        	if(startsWith(rawIndex, '___e')){
            	index = '_' + index;
        	}

            return index;
        },



		contentPreviewOptions: function(previewContent, optId, currentLocale) {
			var layout = data.getOpt(optId, currentLocale, 'layout').val(),
				originalLayout = typeof layout,
				layout = (originalLayout === 'undefined') ? previewContent.find('.ait-element-placeholder-wrap').data('layout') : layout,
				columns = parseInt(data.getOpt(optId, currentLocale, (layout != 'undefined' ? layout : '') + 'columns').val()),
				columns = isNaN(columns) ? parseInt(data.getOpt(optId, currentLocale, 'columns').val()) : columns,
				carousel = parseInt(data.getOpt(optId, currentLocale, (layout != 'undefined' ? layout : '') + 'enablecarousel').val()),
				rows = parseInt(data.getOpt(optId, currentLocale, (layout != 'undefined' ? layout : '') + 'rows').val()),
				rows = carousel == 1 ? rows : (originalLayout === 'undefined' ? 1 : parseInt(previewContent.find('.ait-element-placeholder-row').length));

			/* Fallback if not typical item organizer */
			var coulumsInRows = parseInt(previewContent.find('.ait-element-placeholder-row:first .ait-element-placeholder').length),
				columnsCount  = isNaN(coulumsInRows) ? parseInt(previewContent.find('.ait-element-placeholder').length) : coulumsInRows;

			var columns = isNaN(columns) ? (layout == 'box' ? columnsCount : 1) : columns,
				rows = (isNaN(carousel) && rows == 1 && layout != 'box') ? parseInt(data.getOpt(optId, currentLocale, 'count').val()) : rows,
				rows = rows > 4 ? 4 : rows;
			/* Fallback if not typical item organizer */

			/* Default layout settings */
			switch (layout) {
				case 'icon':
					var columns = 4;
					break;

				case 'horizontal':
					var columns = 3;
					var rows = 1;
					break;

				case 'vertical':
					var columns = 1;
					var rows = 3;
					break;
			}
			/* Default layout settings */

			return {
				layout: layout,
				columns: columns,
				carousel: carousel,
				rows: rows
			}
		},


		updateContentPreview: function(previewContent, optId, currentLocale, options) {
			if (typeof options === 'undefined') {
				options = ui.contentPreviewOptions(previewContent, optId, currentLocale);
			}

			/* Get placeholder */
			var $placeholders = previewContent.find('.ait-element-placeholder-wrap');
			$placeholders
				.removeClass()
				.addClass('ait-element-placeholder-wrap layout-' + options.layout)
				.attr('data-layout', options.layout);
			var placeholderHtml = $placeholders.find('.ait-element-placeholder:first')[0].outerHTML;

			/* Create preview cols */
			$placeholders.html('');
			var placeholder = '';
			for (var i = 0; i < options.columns; i++) {
				placeholder = placeholder + placeholderHtml;
			}
			$placeholders.html('<div class="ait-element-placeholder-row">' + placeholder + '</div>');

			/* Create preview rows */
			if (options.rows > 1) {
				var placeholderRow = $placeholders.find('.ait-element-placeholder-row:first')[0].outerHTML;
				$placeholders.html('');
				var placeholder = '';
				for (var i = 0; i < options.rows; i++) {
					placeholder = placeholder + placeholderRow;
				}
				$placeholders.html(placeholder);
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
		}

	};



	// ===============================================
	// Init
	// -----------------------------------------------

	$(function()
	{
		ait.admin.options.elements.Ui.init();
	});



})(jQuery, jQuery(window), jQuery(document));
