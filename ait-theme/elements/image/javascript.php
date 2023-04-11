<script id="{$htmlId}-script">
//jQuery(window).on('load', function() {
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

	jQuery("#{!$htmlId}").find('a[href*=".jpg"],a[href*=".jpeg"],a[href*=".png"],a[href*=".gif"]').each(function(){
		if(typeof jQuery.prettyPhoto != 'undefined'){
			jQuery(this).prettyPhoto({
				slideshow		: false,
				allow_resize	: true,
				theme 			: 'light_square',
				social_tools 	: false,
				deeplinking 	: false
			});
		} else {
			// use colorbox
			jQuery(this).colorbox({
				maxWidth: "95%",
				maxHeight: "95%",
				onOpen: true,
				onClosed: true,
			});
		}
	});
});
</script>
