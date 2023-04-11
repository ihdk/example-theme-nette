{block content}

	{loop as $post}
		{* SETTINGS AND DATA *}

		{if getMapProvider() == 'openstreetmap'}
		{? wp_enqueue_style( 'ait-leaflet', aitPaths()->url->css . '/libs/leaflet/leaflet.css') }
		{? wp_enqueue_style( 'ait-leaflet-gesture-handling', aitPaths()->url->css . '/libs/leaflet/leaflet-gesture-handling.min.css') }
		{? wp_enqueue_script( 'ait-leaflet', aitPaths()->url->js . '/libs/leaflet/leaflet.js') }
		{? wp_enqueue_script( 'ait-leaflet-gesture-handling', aitPaths()->url->js . '/libs/leaflet/leaflet-gesture-handling.min.js') }
		{/if}

		{var $meta = $post->meta('item-data')}
		{var $settings = $options->theme->item}
		{* SETTINGS AND DATA *}

		{*RICH SNIPPET WRAP*}
		<div class="item-content-wrap" itemscope itemtype="http://schema.org/LocalBusiness">
			<meta itemprop="name" content="{$post->title}">
			<meta itemprop="image" content="{$post->imageUrl}">
			{if $meta->map['address']}
			<meta itemprop="address" content="{$meta->map['address']}">
			{/if}

		{*RICH SNIPPET WRAP*}

			{var $wouldGalleryDisplay = false}
			{if $post->hasImage}
				{var $wouldGalleryDisplay = true}
			{/if}
			{if $meta->displayGallery && !empty($meta->gallery)}
				{var $wouldGalleryDisplay = true}
			{/if}

			{if $wouldGalleryDisplay == false}
				{if defined('AIT_REVIEWS_ENABLED')}
					{includePart portal/parts/single-item-reviews-stars, showCount => true, class => "gallery-hidden"}
				{/if}
			{/if}

			{if $wp->hasSidebar('left') || $wp->hasSidebar('right')}
				{* CONTENT SECTION *}
					{if $wouldGalleryDisplay == false}
					<div class="column-grid column-grid-2">
						<div class="column column-span-2 column-first column-last">
							<div class="entry-content-wrap" itemprop="description">
								<div class="entry-content">
									{if $post->hasContent}
										{!$post->content}
									{else}
										{!$post->excerpt}
									{/if}
								</div>
							</div>
						</div>
					</div>
					{else}
					<div class="column-grid column-grid-3">
						<div class="column column-span-1 column-narrow column-first">
						{* GALLERY SECTION *}
						{includePart portal/parts/single-item-gallery}
						{* GALLERY SECTION *}
						</div>

						<div class="column column-span-2 column-narrow column-last">
							<div class="entry-content-wrap" itemprop="description">
								<div class="entry-content">
									{if $post->hasContent}
										{!$post->content}
									{else}
										{!$post->excerpt}
									{/if}
								</div>
							</div>
						</div>
					</div>
					{/if}
				{* CONTENT SECTION *}

				{var $gridClass = $meta->displayOpeningHours ? 'column-grid-3' : 'column-grid-2'}
				<div class="column-grid {$gridClass} item-details">
					{if $meta->displayOpeningHours}
					<div class="column column-span-1 column-narrow column-first">
						{* OPENING HOURS SECTION *}
						{includePart portal/parts/single-item-opening-hours}
						{* OPENING HOURS SECTION *}
					</div>
					{/if}

					<div class="column column-span-2 column-narrow column-last">
						{* ADDRESS SECTION *}
						{includePart portal/parts/single-item-address}
						{* ADDRESS SECTION *}
					</div>
				</div>

				{* CLAIM LISTING SECTION *}
				{if defined('AIT_CLAIM_LISTING_ENABLED')}
					{includePart portal/parts/claim-listing}
				{/if}
				{* CLAIM LISTING SECTION *}

				{* MAP SECTION *}
				{includePart portal/parts/single-item-map}
				{* MAP SECTION *}

				{* GET DIRECTIONS SECTION *}
				{if defined('AIT_GET_DIRECTIONS_ENABLED')}
					{includePart portal/parts/get-directions-container}
				{/if}
				{* GET DIRECTIONS SECTION *}

				{* FEATURES SECTION *}
				{includePart portal/parts/single-item-features}
				{* FEATURES SECTION *}

				{* ITEM EXTENSION *}
				{if defined('AIT_EXTENSION_ENABLED')}
					{includePart portal/parts/item-extension}
				{/if}
				{* ITEM EXTENSION *}

				{* REVIEWS SECTION *}
				{if defined('AIT_REVIEWS_ENABLED')}
				{includePart portal/parts/single-item-reviews}
				{/if}
				{* REVIEWS SECTION *}

				{* SPECIAL OFFERS SECTION *}
				{if (defined('AIT_SPECIAL_OFFERS_ENABLED'))}
					{includePart parts/single-item-special-offers}
				{/if}
				{* SPECIAL OFFERS SECTION *}

				{* UPCOMING EVENTS SECTION *}
				{if (defined('AIT_EVENTS_PRO_ENABLED')) && AitEventsPro::getEventsByItem($post->id)->found_posts}
					{includePart portal/parts/single-item-events, itemId => $post->id}
				{/if}
				{* UPCOMING EVENTS SECTION *}
			{else}
				{includePart portal/parts/single-item-columned}
			{/if}

		{*RICH SNIPPET WRAP*}
		</div>
		{*RICH SNIPPET WRAP*}

	{/loop}
