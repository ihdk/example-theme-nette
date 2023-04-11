{getHeader}

{* ****** HEADER VARIABLES START ********** *}
{var $headerLayoutType = ''}

{var $headerElementClass = array()}

{var $headerImageSrc = ''}
{var $headerImageHeight = ''}

{if $wp->isSingular('item')}
	{* SINGLE ITEM SETTINGS *}
	{var $meta = $post->meta('item-data')}

	{var $headerLayoutType = $meta->headerType}

	{var $headerImageSrc = isset($meta->headerImage) && $meta->headerImage != '' ? $meta->headerImage : $options->theme->item->noHeader}
	{var $headerImageHeight = $meta->headerHeight}

{elseif $wp->isSingular('event-pro')}
	{* SINGLE ITEM SETTINGS *}
	{var $meta = $post->meta('event-pro-data')}

	{var $headerLayoutType = $meta->headerType}
	{var $eventsOptions = get_option('ait_events_pro_options')}
	{var $headerImageSrc = isset($meta->headerImage) && $meta->headerImage != '' ? $meta->headerImage : $eventsOptions['noHeader']}
	{var $headerImageHeight = $options->layout->general->headerHeight}

{elseif $wp->isTax('items') or $wp->isTax('locations')}
	{* TAXONOMY SETTINGS *}
	{var $meta = (object) get_option("{$taxonomyTerm->taxonomy}_category_{$taxonomyTerm->id}")}

	{var $headerLayoutType = isset($meta->header_type) ? $meta->header_type : ''}

	{if $wp->isTax(items)}
		{var $headerImageSrc =  isset($meta->header_image) && $meta->header_image != '' ? $meta->header_image : $options->theme->items->categoryDefaultImage}
	{else}
		{var $headerImageSrc = isset($meta->header_image) && $meta->header_image != '' ? $meta->header_image : $options->theme->items->locationDefaultImage}
	{/if}

	{var $headerImageHeight = isset($meta->header_height) ? $meta->header_height : ''}

{elseif $wp->isTax('ait-events-pro')}
	{* TAXONOMY SETTINGS *}
	{var $meta = (object) get_option("{$taxonomyTerm->taxonomy}_category_{$taxonomyTerm->id}")}
	{var $eventsOptions = get_option('ait_events_pro_options')}
	{var $headerLayoutType = isset($meta->header_type) ? $meta->header_type : ''}
	{var $headerImageSrc =  isset($meta->header_image) && $meta->header_image != '' ? $meta->header_image : $eventsOptions['categoryDefaultImage']}
	{var $headerImageHeight = $options->layout->general->headerHeight}

{else}
	{* PAGE BUILDER SETTINGS *}

	{var $headerLayoutType = $options->layout->general->headerType}

	{var $headerImageSrc = isset($options->layout->general->headerImage) && $options->layout->general->headerImage != '' ? $options->layout->general->headerImage : ''}
	{var $headerImageHeight = $options->layout->general->headerHeight}

{/if}

{if $headerLayoutType == 'map'}
	{var $headerLayoutType = $elements->unsortable[header-map]->display ? $headerLayoutType : ''}
{elseif $headerLayoutType == 'video'}
	{var $headerLayoutType = $elements->unsortable[header-video]->display ? $headerLayoutType : ''}
{elseif $headerLayoutType == 'revslider'}
	{var $headerLayoutType = $elements->unsortable[revolution-slider]->display ? $headerLayoutType : ''}
{/if}

{var $searchFormType = $elements->unsortable[search-form]->display ? $elements->unsortable[search-form]->options[type] : ''}

{if $options->layout->custom->layout == 'half' && $headerLayoutType != '' && $headerLayoutType != 'none' && $headerLayoutType != 'revslider'}
	{var $layoutType = 'half'}
{elseif $options->layout->custom->layout == 'full'}
	{var $layoutType = 'full'}
{else}
	{var $layoutType = 'collapsed'}
{/if}

{var $toggleButtonGroup = false}
{var $togglableSearch = true}

{if $wp->isTax(items) or $wp->isTax(locations) or $wp->isSingular(item) or $wp->isTax('ait-events-pro') or $wp->isSingular('ait-event-pro') or $wp->isSearch}
	{if $headerLayoutType == 'map'}
		{var $toggleButtonGroup = true}
	{/if}
{/if}

{if $layoutType != 'full' and $searchFormType != 3}
	{var $toggleButtonGroup = true}
{/if}

{if !$elements->unsortable[search-form]->display}
	{var $togglableSearch = false}
{/if}

{* ****** HEADER VARIABLES END ************ *}

{* ****** HEADER RESULTS START ********** *}
{if $headerLayoutType == 'image' && $headerImageHeight != ""}
<style type="text/css" scoped="scoped">
	.header-layout.element-image-enabled .header-image { height: {$headerImageHeight}px; }
</style>
{/if}

<div class="header-layout element-{$headerLayoutType}-enabled">
	{if $toggleButtonGroup}
	<div class="ait-toggle-area-group-container toggle-group-map-search-container {if $headerLayoutType != 'map'}no-toggle-map{/if}{if !$togglableSearch} no-toggle-search{/if}">
		<div class="grid-main">
			<div class="ait-toggle-area-group toggle-group-map-search">
				{if $headerLayoutType == 'map'}
					<a href="#" class="ait-toggle-area-btn" data-toggle=".elm-header-map"><i class="fa fa-map-o"></i> {__ 'Map'}</a>
				{/if}
				{if $elements->unsortable[search-form]->display}
					<a id="toggle-search-top" href="#" class="ait-toggle-area-btn" data-toggle=".elm-search-form"><i class="fa fa-search"></i> {__ 'Search'}</a>
				{/if}
			</div>
		</div>
	</div>
	{/if}

	<div n:class="'header-element-wrap'">
		{if $headerLayoutType == 'revslider'}
			{if $elements->unsortable['revolution-slider']->display}
				{includeElement $elements->unsortable['revolution-slider']}
			{/if}
		{elseif $headerLayoutType == 'map'}
			{if $elements->unsortable['header-map']->display}
				{includeElement $elements->unsortable['header-map']}
			{/if}
		{elseif $headerLayoutType == 'video'}
			{if $elements->unsortable['header-video']->display}
				{includeElement $elements->unsortable['header-video']}
			{/if}
		{elseif $headerLayoutType == 'image'}
			{if $headerImageHeight != ""}
			<div class="header-image" style="background-image: url('{!$headerImageSrc}')"></div>
			{else}
			<img src="{!$headerImageSrc}" alt="header-image" />
			{/if}
		{else}
			{* none *}
		{/if}
	</div>
	<div class="header-search-wrap">
		{if $elements->unsortable['search-form']->display}
			{includeElement $elements->unsortable['search-form']}
		{/if}
	</div>
</div>
{* ****** HEADER RESULTS END ************ *}

<div id="main" class="elements">

	{if $elements->unsortable['page-title']->display}
	    {includeElement $elements->unsortable['page-title']}
	{/if}

	{var $currentCategory = get_queried_object()}

	{if $wp->isTax(items) or $wp->isTax(locations) or $wp->isTax(ait-events-pro)}
		{includePart portal/parts/taxonomy-category-list, taxonomy => $currentCategory->taxonomy, gridMain => true}
	{elseif $wp->isPostTypeArchive(ait-item)}
		{includePart portal/parts/taxonomy-category-list, taxonomy => 'ait-items', gridMain => true}
	{elseif $wp->isPostTypeArchive(ait-event-pro)}
		{includePart portal/parts/taxonomy-category-list, taxonomy => 'ait-events-pro', gridMain => true}
	{/if}

	<div class="main-sections">
	{foreach $elements->sortable as $element}

		{if $element->id == sidebars-boundary-start}

		<div class="elements-with-sidebar">
			<div class="grid-main">
			<div class="elements-sidebar-wrap">
				{if $wp->hasSidebar('left')}
					{getSidebar left}
				{/if}
				<div class="elements-area">

		{elseif $element->id == sidebars-boundary-end}

				</div><!-- .elements-area -->
				{if $wp->hasSidebar('right')}
					{getSidebar}
				{/if}
				</div><!-- .elements-sidebar-wrap -->
				</div><!-- .grid-main -->
			</div><!-- .elements-with-sidebar -->

		{else}
			{? global $post}
			{if $element->id == 'comments' && $post == null}
				<!-- COMMENTS DISABLED - IS NOT SINGLE PAGE -->
			{elseif $element->id == 'comments' && !comments_open($post->ID) && get_comments_number($post->ID) == 0}
				<!-- COMMENTS DISABLED -->
			{else}
				<section n:if="$element->display" id="{$element->htmlId}-main" class="{$element->htmlClasses}">

					<div class="elm-wrapper {$element->htmlClass}-wrapper">

						{includeElement $element}

					</div><!-- .elm-wrapper -->

				</section>
			{/if}
		{/if}
	{/foreach}
	</div><!-- .main-sections -->
</div><!-- #main .elements -->

{getFooter}
