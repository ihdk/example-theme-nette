/* elementData is required global object which contains data of the current element:
 *
 * elementId     - id of element
 * contentId     - id of element content
 * optId         - basic example of element options id, '__opt__' should be replaced with desired option key from config
 *                 e.g. ait-opt-elements-content-__opt__-__1__ > ait-opt-elements-content-content-__1__
 * currentLocale - current locale code e.g. 'en_US'
 */

(function($){

	"use strict";

	var element = {

		div: $('#' + elementData.elementId),
		content: $('#' + elementData.contentId),
		optId: elementData.optId,
		preview: $('#' + elementData.elementId + ' .ait-element-preview-content'),


		init: function() {
			element.bindEvents();
		},


		bindEvents: function() {
			element.div.on('close', element.updateContent);
		},


		updateContent: function() {
			element.preview.html($.truncate(element.getOpt('text').val(), {
				length: 400
			}));
		},


		getOpt: function(opt, suffix) {
			return ait.admin.options.elements.Data.getOpt(element.optId, elementData.currentLocale, opt, suffix);
		}
	}

	$(function(){
		element.init();
	});

})(jQuery);