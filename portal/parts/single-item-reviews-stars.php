{var $rating_count = AitItemReviews::getRatingCount($post->id)}
{var $rating_mean = floatval(get_post_meta($post->id, 'rating_mean', true))}

{var $showCount = isset($showCount) ? $showCount : false}

{var $class = isset($class) ? $class : '' }
<div class="review-stars-container {$class}">
	{if $rating_count > 0}
		<div class="content rating-star-shown">
			<span class="review-stars" data-score="{$rating_mean}"></span>
			{if $showCount}<span class="review-count">({$rating_count})</span>{/if}
			<a href="{$post->permalink}#review"><?php _e('Submit your rating', 'ait-item-reviews') ?></a>
		</div>
		<script type="application/ld+json">
		{
		  "@context": "https://schema.org/",
		  "@type": "AggregateRating",
		  "itemReviewed": {
		    "@type": "LocalBusiness",
			"name": "{!$post->title}",
			"image": "{!$post->imageUrl ? $post->imageUrl : $options->theme->item->noFeatured}"
		  },
		  "ratingValue": "{!$rating_mean}",
		  "bestRating": "5",
		  "ratingCount": "{!$rating_count}"
		}
		</script>
	{else}
		<div class="content rating-text-shown">
			<a href="{$post->permalink}#review"><?php _e('Submit your rating', 'ait-item-reviews') ?></a>
		</div>
	{/if}
</div>