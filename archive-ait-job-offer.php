{block content}
	{? global $wp_query}
	{var $query = $wp_query}

	{if $query->have_posts()}
		{var $dateFormat 		= 'j M Y'}
		<div class="elm-item-organizer-container column-1 layout-list carousel-disabled" data-last="2" data-first="1" data-cols="1">
		{customLoop from $query as $item}
			{var $meta = $item->meta('offer-data')}
			{if time() <= strtotime($meta->validTo)}
				<div n:class='item, "item{$iterator->counter}", $item->hasImage ? image-present'	data-id="{$iterator->counter}">
					<a href="{$item->permalink}">
						{if $item->hasImage}
							<div class="item-thumbnail">
									<img src="{imageUrl $item->imageUrl, width => 100, height => 100, crop => 1}" alt="{!$item->title}">
							</div>
						{/if}
						<div class="item-title">
							<h3>{!$item->title}</h3>
							{if $meta->validTo != ''}
								<div class="item-duration">
									<span class="item-dur-title"><strong>{__ 'Validity:'}</strong></span>
									<time class="item-from" datetime="{$meta->validFrom|dateI18n:'c'}">{$meta->validFrom|dateI18n: $dateFormat}</time>
									<span class="item-sep">-</span>
									<time class="item-to" datetime="{$meta->validTo|dateI18n:'c'}">{$meta->validTo|dateI18n: $dateFormat}</time>
								</div>
							{/if}
						</div>
					</a>
					<div class="item-text">
						<div class="item-excerpt">{!$item->excerpt(50)}</div>
					</div>
					<div class="item-info">
						<div class="job-contact">
							<span class="job-contact-title"><strong>{__ 'Contact:'}</strong></span>
							{if $meta->contactName}<span class="job-contact-name">{$meta->contactName}</span>{/if}<!--
							-->{if $meta->contactMail}<span class="job-contact-mail"><a href="mailto:{$meta->contactMail}">{$meta->contactMail}</a></span>{/if}<!--
							-->{if $meta->contactPhone}<span class="job-contact-phone">{$meta->contactPhone}</span>{/if}
						</div>
					</div>
				</div>
			{/if}
		{/customLoop}
		</div>
	{else}
		{includePart parts/none, message => no-posts}
	{/if}
