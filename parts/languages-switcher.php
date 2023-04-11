{*
	$lang->id
	$lang->slug
	$lang->name
	$lang->url
	$lang->flagUrl
	$lang->flag
	$lang->isCurrent
	$lang->hasTranslation
	$lang->htmlClass
*}

{if $languages && count($languages) > 1}
	<div class="language-switcher">
		<div class="language-icons">
			<a href="#" role="button" class="language-icons__icon language-icons__icon_main {$lang->htmlClass}">
				<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
			</a>

			<ul class="language-icons__list">
			{foreach $languages as $lang}
				<li>
					<a hreflang="{$lang->slug}" href="{$lang->url}" class="language-icons__icon {$lang->htmlClass} {$lang->isCurrent ? 'current'}">
						{!$lang->flag}
						{$lang->name}
					</a>
				</li>
			{/foreach}
			</ul>
		</div>
	</div>
{/if}
