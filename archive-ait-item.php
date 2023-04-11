{block content}
{? global $wp_query}
{var $query = $wp_query}

{var $noFeatured = $options->theme->item->noFeatured}

<div n:class="items-container, !$wp->willPaginate($query) ? 'pagination-disabled'">
	<div class="content">

		{if $query->have_posts()}

		{includePart portal/parts/search-filters, taxonomy => "ait-items", current => $query->post_count, max => $query->found_posts}

		{if defined("AIT_ADVANCED_FILTERS_ENABLED")}
			{includePart portal/parts/advanced-filters, query => $query}
		{/if}

		<div class="ajax-container">
			<div class="content">

				{customLoop from $query as $post}

					{includePart "portal/parts/item-container"}

				{/customLoop}

				{includePart parts/pagination, location => pagination-below, max => $query->max_num_pages}
			</div>
		</div>

		{else}
			{includePart parts/none, message => empty-site}
		{/if}
	</div>
</div>