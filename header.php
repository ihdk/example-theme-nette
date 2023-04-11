<!doctype html>
<!--[if IE 8]>
<html {languageAttributes}  class="lang-{$currentLang->locale} {$options->layout->custom->pageHtmlClass} ie ie8">
<![endif]-->
<!--[if !(IE 7) | !(IE 8)]><!-->
<html {languageAttributes} class="lang-{$currentLang->locale} {$options->layout->custom->pageHtmlClass}">
<!--<![endif]-->
<head>
	<meta charset="{$wp->charset}">
	<meta name="viewport" content="width=device-width, user-scalable=0">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="{$wp->pingbackUrl}">

	{if $options->theme->general->favicon != ""}
		<link href="{$options->theme->general->favicon}" rel="icon" type="image/x-icon" />
	{/if}

	{includePart parts/seo}

	{googleAnalytics $options->theme->google->analyticsTrackingId, $options->theme->google->anonymizeIp}

	{wpHead}

	{!$options->theme->header->customJsCode}
</head>

{var $searchFormClass = ""}
{if $elements->unsortable[search-form]->display}
	{var $searchFormClass = $elements->unsortable[search-form]->option('type') != "" ? "search-form-type-".$elements->unsortable[search-form]->option('type') : "search-form-type-1"}
{/if}

{* ****** HEADER VARIABLES START ********** *}
{var $headerLayoutType = ''}

{var $headerElementClass = array()}

{var $headerImageSrc = ''}
{var $headerImageHeight = ''}

{if $wp->isSingular('item')}
	{* SINGLE ITEM SETTINGS *}
	{var $headerLayoutType = $post->meta('item-data')->headerType}

{elseif $wp->isSingular('event-pro')}
	{* SINGLE ITEM SETTINGS *}
	{var $headerLayoutType = $post->meta('event-pro-data')->headerType}

{elseif $wp->isTax('items') or $wp->isTax('locations')}
	{* TAXONOMY SETTINGS *}
	{var $meta = (object) get_option("{$taxonomyTerm->taxonomy}_category_{$taxonomyTerm->id}")}
	{var $headerLayoutType = isset($meta->header_type) ? $meta->header_type : ''}

{elseif $wp->isTax('ait-events-pro')}
	{* TAXONOMY SETTINGS *}
	{var $meta = (object) get_option("{$taxonomyTerm->taxonomy}_category_{$taxonomyTerm->id}")}
	{var $headerLayoutType = isset($meta->header_type) ? $meta->header_type : ''}

{else}
	{* PAGE BUILDER SETTINGS *}
	{var $headerLayoutType = $options->layout->general->headerType}

{/if}

{if $headerLayoutType == 'map'}
	{var $headerLayoutType = $elements->unsortable[header-map]->display ? $headerLayoutType : 'none'}
{elseif $headerLayoutType == 'video'}
	{var $headerLayoutType = $elements->unsortable[header-video]->display ? $headerLayoutType : 'none'}
{elseif $headerLayoutType == 'revslider'}
	{var $headerLayoutType = $elements->unsortable[revolution-slider]->display ? $headerLayoutType : 'none'}
{elseif $headerLayoutType == ''}
	{var $headerLayoutType = 'none'}
{/if}

{* ****** HEADER VARIABLES END ************ *}

{if $options->layout->custom->layout == 'half' && $headerLayoutType != '' && $headerLayoutType != 'none' && $headerLayoutType != 'revslider'}
	{var $layoutType = 'half'}
	{elseif $options->layout->custom->layout == 'full'}
	{var $layoutType = 'full'}
{else}
	{var $layoutType = 'collapsed'}
{/if}

<body n:class='$wp->bodyHtmlClass(false), $layoutType, "header-type-{$headerLayoutType}", defined("AIT_REVIEWS_ENABLED") ? reviews-enabled, $searchFormClass, $options->layout->general->showBreadcrumbs ? breadcrumbs-enabled'>
	{if function_exists( 'wp_body_open' )}
		{wp_body_open()}
	{else}
		{doAction wp_body_open}
	{/if}
	
	{* usefull for inline scripts like facebook social plugins scripts, etc... *}
	{doAction ait-html-body-begin}
	
	
	{if $wp->isPage}
	<div id="page" class="page-container header-one">
	{else}
	<div id="page" class="hfeed page-container header-one">
	{/if}


		<header id="masthead" class="site-header">

			{if $wp->description}
			<div class="top-bar">
				<div class="grid-main">
					<p class="site-description">{!html_entity_decode($wp->description)}</p>

					<div class="top-bar-tools">
						{includePart parts/social-icons, class => 'has-dropdown-mobile'}
					</div>
				</div>
			</div>
			{/if}

			<div class="header-container grid-main">

				<div class="site-logo">
					{if $options->theme->header->logo}
					<a href="{$homeUrl}" title="{!$wp->name}" rel="home"><img src="{$options->theme->header->logo}" alt="logo"></a>
					{else}
					<div class="site-title"><a href="{$homeUrl}" title="{!$wp->name}" rel="home">{!$wp->name}</a></div>
					{/if}

				</div>

				<div class="menu-container">
					<nav class="main-nav menu-hidden" data-menucollapse={$options->theme->header->menucollapse}>

						<div class="main-nav-wrap">
							<h3 class="menu-toggle"><i class="icon-burger"></i> {__ 'Menu'}</h3>
							{menu main}
						</div>
					</nav>

					<div class="menu-tools">
						{includePart portal/parts/header-resources}
						{includePart portal/parts/user-panel}
						{includePart parts/languages-switcher}
						{includePart "parts/woocommerce-cart"}
					</div>
				</div>

			</div>


			</header><!-- #masthead -->

		<div class="sticky-menu menu-container" >
			<div class="grid-main">
				<div class="site-logo">
					{if $options->theme->header->logo}
					<a href="{$homeUrl}" title="{!$wp->name}" rel="home"><img src="{$options->theme->header->logo}" alt="logo"></a>
					{else}
					<div class="site-title"><a href="{$homeUrl}" title="{!$wp->name}" rel="home">{!$wp->name}</a></div>
					{/if}
				</div>
				<nav class="main-nav menu-hidden" data-menucollapse={$options->theme->header->menucollapse}>
					<!-- wp menu here -->
				</nav>
			</div>
		</div>
