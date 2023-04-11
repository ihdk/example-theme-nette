{if $options->theme->social->enableSocialIcons}
<div class="social-icons {isset($class) ? $class : ''}">
	<a href="#" class="social-icons-toggle ait-toggle-hover"><i class="icon-share"><svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg></i></a>

	<ul><!--
		{foreach array_filter((array) $options->theme->social->socIcons) as $icon}
			--><li>
				<a href="{$icon->url}" {if $options->theme->social->socIconsNewWindow}target="_blank"{/if} class="icon-{$iterator->getCounter()}" onmouseover="this.style.backgroundColor='{!$icon->iconColor}'" onmouseout="this.style.backgroundColor=''">
					{if $icon->icon}<i class="fa {$icon->icon}"></i>{/if}
					<span class="s-title">{$icon->title}</span>
				</a>
			</li><!--
		{/foreach}
	--></ul>
</div>
{/if}
