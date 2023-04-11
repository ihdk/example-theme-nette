{* VARIABLES *}
{var $concreteTaxonomy = isset($taxonomy) && $taxonomy != "" ? $taxonomy : ''}
{var $maxCategories = $options->theme->items->maxDisplayedCategories}
{* VARIABLES *}


	{if !$wp->isSingular}

		{if $wp->isSearch}
			{var $isAdvanced = false}

			{if isset($_REQUEST['a']) && $_REQUEST['a'] != ""}
				{var $isAdvanced = true}
			{/if}

			{if $isAdvanced}
				{var $noFeatured = $options->theme->item->noFeatured}

				{var $categories = get_the_terms($post->id, 'ait-items')}

				{* FEATURED CATEGORIES *}
				{var $categories = aitOrderTermsByHierarchy($categories)}
				{var $categories = aitFilterTerms($categories, $options->theme->items->maxDisplayedCategories)}
				{* FEATURED CATEGORIES *}

				{var $meta = $post->meta('item-data')}

				{var $dbFeatured = get_post_meta($post->id, '_ait-item_item-featured', true)}
				{var $isFeatured = $dbFeatured != "" ? filter_var($dbFeatured, FILTER_VALIDATE_BOOLEAN) : false}

				<div n:class='item-container, $isFeatured ? item-featured, defined("AIT_REVIEWS_ENABLED") ? reviews-enabled'>
						<div class="content">

							<div class="item-image">
								<a class="main-link" href="{$post->permalink}">
									{if $post->image}
										<img src="{imageUrl $post->imageUrl, width => 200, height => 240, crop => 1}" alt="{!$post->title}">
										{else}
										<img src="{imageUrl $noFeatured, width => 200, height => 240, crop => 1}" alt="{!$post->title}">
									{/if}
								</a>
								{if defined('AIT_REVIEWS_ENABLED')}
									{includePart "portal/parts/carousel-reviews-stars", item => $post, showCount => false}
								{/if}
							</div>
							<div class="item-data">
								<div class="item-header">
									<div class="item-title-wrap">
										<div class="item-title">
											<a href="{$post->permalink}">
												<h3>{!$post->title}</h3>
											</a>
										</div>
										<span class="subtitle">{AitLangs::getCurrentLocaleText($meta->subtitle)}</span>
									</div>

									{var $target = $meta->socialIconsOpenInNewWindow ? 'target="_blank"' : ""}
									{if $meta->displaySocialIcons}

											<div class="social-icons-container">
												<div class="content">
													{if is_array($meta->socialIcons) && count($meta->socialIcons) > 0}
														<ul><!--
														{foreach $meta->socialIcons as $icon}
														--><li>
																<a href="{!$icon['link']}" {!$target}>
																	<i class="fa {$icon['icon']}"></i>
																</a>
															</li><!--
														{/foreach}
														--></ul>
													{/if}
												</div>
											</div>

									{/if}

									{if is_array($categories) && count($categories) > 0}
										<div class="item-categories">
											{foreach $categories as $category}
												{var $catLink = get_term_link($category)}
												<a href="{$catLink}"><span class="item-category">{!$category->name}</span></a>
											{/foreach}
										</div>
									{/if}
								</div>
								<div class="item-body">
									<div class="entry-content">
										<p class="txtrows-4">{if $post->hasExcerpt}{!$post->excerpt|striptags|trim|truncate: 140}{else}{!$post->content|striptags|trim|truncate: 250}{/if}</p>
									</div>
								</div>
								<div class="item-footer">
									{if $meta->map['address']}
									<div class="item-address">
										<span class="label">{__ 'Address:'}</span>
										<span class="value">{$meta->map['address']}</span>
									</div>
									{/if}

									{if $meta->web}
									<div class="item-web">
										<span class="label">{__ 'Web:'}</span>
										<span class="value"><a href="{!$meta->web}" target="_blank">{if $meta->webLinkLabel}{$meta->webLinkLabel}{else}{$meta->web}{/if}</a></span>
									</div>
									{/if}

									{if !is_array($meta->features)}
										{var $meta->features = array()}
									{/if}

									{if defined('AIT_ADVANCED_FILTERS_ENABLED')}
										{var $item_meta_filters = $post->meta('filters-options')}
										{if is_array($item_meta_filters->filters) && count($item_meta_filters->filters) > 0}
											{var $custom_features = array()}
											{foreach $item_meta_filters->filters as $filter_id}
												{var $filter_data = get_term($filter_id, 'ait-items_filters', "OBJECT")}
												{if $filter_data}
													{var $filter_meta = get_option( "ait-items_filters_category_".$filter_data->term_id )}
													{var $filter_icon = isset($filter_meta['icon']) ? $filter_meta['icon'] : ""}
													{? array_push($meta->features, array(
														"icon" => $filter_icon,
														"text" => $filter_data->name,
														"desc" => $filter_data->description
													))}
												{/if}
											{/foreach}
										{/if}
									{/if}


									{if is_array($meta->features) && count($meta->features) > 0}
									<div class="item-features">
										<div class="label">{__ 'Features:'}</div>
										<div class="value">
											<ul class="item-filters">
											{foreach $meta->features as $filter}
												{var $imageClass = $filter['icon'] != '' ? 'has-image' : ''}
												{var $textClass = $filter['text'] != '' ? 'has-text' : ''}

												<li class="item-filter {$imageClass} {$textClass}">
													{if $filter['icon'] != ''}
													<i class="fa {$filter['icon']}"></i>
													{/if}
													<span class="filter-hover">
														{!$filter['text']}
													</span>

												</li>
											{/foreach}
											</ul>
										</div>
									</div>
									{/if}


								</div>
							</div>
						</div>


				</div>

			{else}
				{*** SEARCH RESULTS ONLY ***}

				<article {!$post->htmlId} {!$post->htmlClass('hentry')}>
					<div class="entry-header nothumbnail">
						<div class="entry-thumbnail">
							<a href="{$post->permalink}" class="thumb-link">
								{includePart parts/entry-date-format, dateIcon => $post->rawDate, dateLinks => 'no', dateShort => 'yes'}
							</a>

							<div class="entry-meta">
								{capture $editLinkLabel}<span class="edit-link">{!__ 'Edit'}</span>{/capture}
								{!$post->editLink($editLinkLabel)}
							</div><!-- /.entry-meta -->
						</div>
					</div><!-- /.entry-header -->

					<div class="entry-body">
						<header class="entry-title">
							<div class="entry-title-wrap">
								<h2><a href="{$post->permalink}">{!$post->title}</a></h2>
							</div><!-- /.entry-title-wrap -->
						</header><!-- /.entry-title -->


						<div class="entry-content loop">
							{if $post->hasContent}
								{!$post->content|trim|truncate: 160|striptags}
							{else}
								{!$post->excerpt}
							{/if}
						</div><!-- .entry-content -->

						<footer class="entry-footer">

							<a href="{$post->permalink}" class="more">{!__ 'Read More'}</a>

							<div class="entry-data">
								{if $post->type == post}
									{includePart parts/entry-author}

									{includePart parts/comments-link, 'iconLayout' => true}
								{/if}
							</div>

						</footer><!-- .entry-footer -->
					</div>
				</article>
			{/if}

		{else}

			{*** STANDARD LOOP ***}

			<article {!$post->htmlId} {!$post->htmlClass('hentry')}>
				<div class="entry-header {if !$post->hasImage}nothumbnail{/if}">
					<div class="entry-thumbnail">
						{if $post->hasImage}
							{var $img_id = get_post_thumbnail_id($post->id)}
							{var $img_alt = get_post_meta($img_id , '_wp_attachment_image_alt', true)}
							{var $alt_text = $img_alt != "" ? $img_alt : $post->title}
							<div class="entry-thumbnail-wrap entry-content">
								<a href="{$post->permalink}" class="thumb-link">
									<span class="entry-thumbnail-icon">
										<img src="{imageUrl $post->imageUrl, width => 1000, height => 600, crop => 1}" width="1000" height="600" alt="{!$alt_text}">
									</span>
								</a>
							</div>
						{/if}

						<a href="{$post->permalink}" class="thumb-link">
							{includePart parts/entry-date-format, dateIcon => $post->rawDate, dateLinks => 'no', dateShort => 'yes'}
						</a>

						<div class="entry-meta">
							{capture $editLinkLabel}<span class="edit-link">{!__ 'Edit'}</span>{/capture}
	      					{!$post->editLink($editLinkLabel)}
						</div><!-- /.entry-meta -->
					</div>
				</div><!-- /.entry-header -->

				<div class="entry-body">
					<header class="entry-title">
						<div class="entry-title-wrap">
							<h2><a href="{$post->permalink}">{!$post->title}</a></h2>
						</div><!-- /.entry-title-wrap -->
					</header><!-- /.entry-title -->

					{if $post->isInAnyCategory}
						{includePart parts/entry-categories}
					{/if}

					<div class="entry-content loop">
						{if $post->hasContent}
							{!$post->content|trim|truncate: 160|striptags}
						{else}
							{!$post->excerpt}
						{/if}

					</div><!-- .entry-content -->

					<footer class="entry-footer">

						<a href="{$post->permalink}" class="more">{!__ 'Read More'}</a>

						<div class="entry-data">

							{if $post->type == post}
								{includePart parts/entry-author}
							{/if}

							{includePart parts/comments-link, 'iconLayout' => true}

						</div>

					</footer><!-- .entry-footer -->
				</div>
			</article>
		{/if}

	{else}

		{*** POST DETAIL ***}

		<article {!$post->htmlId} class="content-block hentry">

			<div class="entry-title hidden-tag">
				<h2>{!$post->title}</h2>
			</div>

			<div class="entry-thumbnail">
					{if $post->hasImage}
						<div class="entry-thumbnail-wrap">
						 <a href="{$post->imageUrl}" class="thumb-link">
						  <span class="entry-thumbnail-icon">
							<img src="{imageUrl $post->imageUrl, width => 1000, height => 500, crop => 1}" width="1000" height="500" alt="{!$post->title}">
						  </span>
						 </a>
						</div>
					{/if}
				</div>

			<div class="entry-content">
				{!$post->content}
				{!$post->linkPages}
			</div><!-- .entry-content -->

			<footer class="entry-footer single">

				{if $post->categoryList}
					<div class="categories-wrap">
						<i class="icon-folder"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></i>

						{includePart parts/entry-categories, taxonomy => 'category'}
					</div>
				{/if}

				{if $post->tagList}
					<div class="tags-wrap">
						<i class="icon-tag"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></i>

						<span class="tags">
							<span class="tags-links">{!$post->tagList}</span>
						</span>
					</div>
				{/if}


			</footer><!-- .entry-footer -->

			{includePart parts/author-bio}


		</article>

	{/if}
