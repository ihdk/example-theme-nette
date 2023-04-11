{block content}
{? global $wp_query}

<div n:class="items-container, ajax-container, !$wp->willPaginate($wp_query) ? 'pagination-disabled'">
	<div class="content">

		{if $wp_query->have_posts()}

			{* LAYOUT VARIABLES *}
			{var $layout = 'list'}
			{var $enableCarousel = false}
			{var $numOfColumns = 1}
			{var $addInfo = true}
			{var $imgWidth = 300}
			{var $imgHeight = 500}
			{var $textRows = 3}

			{includePart parts/special-offers-filter, current => $wp_query->post_count, max => $wp_query->found_posts, element => '#primary .items-container.ajax-container .content'}

			<div class="items-container elm-item-organizer{if $layout == 'box'} organizer-alt{/if}">
				<div class="elm-item-organizer-container ajax-content carousel-disabled layout-{$layout} column-{$numOfColumns}">

					{customLoop from $wp_query as $post}

						{* DATA VARIABLES *}
						{var $item = $post}
						{var $meta = get_post_meta($item->id, '_ait-special-offer_special-offer-data', true)}

						{* SPECIAL OFFERS DATA *}
						{var $offerImg = "default_offer_img.jpg"}
						{if $post->image}
							{var $offerImage = $post->imageUrl}
						{elseif aitUrl('img', "/$offerImg")}
							{var $offerImage = aitUrl('img', "/$offerImg")}
						{elseif file_exists(AitSpecialOffers::getPaths('design')."/img/$offerImg")}
							{var $offerImage = AitSpecialOffers::getPaths('design')."/img/$offerImg"}
						{/if}

						{var $dateFrom = !empty($meta['dateFrom']) ? date_i18n(get_option('date_format'), strtotime($meta['dateFrom'])) : ''}
						{var $dateTo   = !empty($meta['dateTo']) ? date_i18n(get_option('date_format'), strtotime($meta['dateTo'])) : ''}

						{var $offerUrl = !empty($meta['item']) ? get_permalink($meta['item']).'#single-item-special-offers' : $post->permalink}

						<div n:class='item, "item{$iterator->counter}", $enableCarousel ? carousel-item, $iterator->isFirst($numOfColumns) ? item-first, $iterator->isLast($numOfColumns) ? item-last, image-present, '	data-id="{$iterator->counter}">

							<div class="item-content-wrap{if !$addInfo} no-info{/if}">

								<div class="item-thumbnail">
									<a href="{!$offerUrl}">
										<div class="item-thumbnail-wrap" style="background-image: url('{imageUrl $offerImage, width => $imgWidth, height => $imgHeight, crop => 1}')"></div>
									</a>
								</div>

								<div class="item-content">

									<div class="item-title">
										<a href="{!$offerUrl}"><h3>{!$item->title}</h3></a>
									</div>

									<div class="item-text">
										<div class="item-excerpt txtrows-{$textRows}"><p>{!$item->excerpt(200)|striptags}</p></div>
									</div>

									{if $meta['item']}
									<div class="item-location">
										<p>
											<i class="icon-pin"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></i>

											<a href="{!$offerUrl}">{!get_the_title($meta['item'])}</a>
										</p>
									</div>
									{/if}

								</div>

							</div>

							{if $addInfo}
							<div class="item-info-wrap">
								{if $dateFrom}
								<div class="item-date label">
									<span>{__ 'From:'}</span> {$dateFrom}
								</div>
								{/if}

								{if $dateTo}
								<div class="item-date label">
									<span>{__ 'To:'}</span> {$dateTo}
								</div>
								{/if}

								<div class="item-price">
									{var $price = !empty($meta['price']) ? $meta['price'] : 0}
									<span>{currency $price, $meta['currency']}</span>
								</div>
							</div>
							{/if}

						</div>

					{/customLoop}

				</div>
			</div>

			{includePart parts/pagination, location => pagination-below, max => $wp_query->max_num_pages}

		{else}
			{includePart parts/none, message => empty-site}
		{/if}
	</div>
</div>
