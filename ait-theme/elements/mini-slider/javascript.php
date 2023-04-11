<script id="{$htmlId}-script">
jQuery(window).on('load', function() {
	{!$el->jsObject}

	{if $options->theme->general->progressivePageLoading}
		{!$el->jsObjectName}.current.progressive = true;

		if(!isResponsive(1024)){
			jQuery("#{!$htmlId}-main").waypoint(function(){
				jQuery("#{!$htmlId}-main").addClass('load-finished');
			}, { triggerOnce: true, offset: "95%" });

			if(jQuery.fn.miniSlider !== undefined){
				jQuery("#{!$htmlId}").find(".loading").show();
				jQuery("#{!$htmlId}").miniSlider({!$el->jsObjectName});
			}
		} else {
			{!$el->jsObjectName}.current.progressive = false;
			jQuery("#{!$htmlId}-main").addClass('load-finished');
			
			if(jQuery.fn.miniSlider !== undefined){
				jQuery("#{!$htmlId}").find(".loading").show();
				jQuery("#{!$htmlId}").miniSlider({!$el->jsObjectName});
			}
		}

	{else}

		{!$el->jsObjectName}.current.progressive = false;
		jQuery("#{!$htmlId}-main").addClass('load-finished');

		if(jQuery.fn.miniSlider !== undefined){
			jQuery("#{!$htmlId}").find(".loading").show();
			jQuery("#{!$htmlId}").miniSlider({!$el->jsObjectName});
		}
	{/if}
});
</script>
