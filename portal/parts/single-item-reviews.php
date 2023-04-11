{var $current_rating_count = AitItemReviews::getReviewCount($post->id)}
{var $current_rating_mean = get_post_meta($post->id, 'rating_mean', true)}

{var $ratingsDisplayedClass = AitItemReviews::hasReviewQuestions($post->id) ? 'ratings-shown' : 'ratings-hidden'}

{var $ratingFormShownClass = 'rating-form-shown'}
{if $options->theme->itemReviews->onlyRegistered}
	{if !is_user_logged_in()}
		{var $ratingFormShownClass = 'rating-form-hidden'}
	{/if}
{/if}

<div class="reviews-container {$ratingsDisplayedClass} {$ratingFormShownClass}" id="review">
	<h2>
		<?php _e('Reviews', 'ait') ?>
		<i class="icon-star"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></i>
	</h2>

	<div class="rating-stats">
		<div class="rating">
			<div class="rating-overall">
				{$current_rating_mean|number:1}
			</div>

			<div class="review-stars-container">
				<div class="review-stars" data-score="{$current_rating_mean}"></div>

				<div class="review-count">
					<i class="icon-users"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></i>
					<span class="number">{$current_rating_count}</span>
				</div>
			</div>
		</div>

		<a href="{$post->permalink}#review" class="ait-button submit-rating">
			<i class="icon-pencil">
				<svg viewBox="0 0 24 24" width="16" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
			</i>

			<?php _e('Submit your rating', 'ait-item-reviews') ?>
		</a>
	</div>

	<div class="reviews-form">
	{if $options->theme->itemReviews->onlyRegistered}
		{if is_user_logged_in()}
			<h2><?php _e('Leave a Review', 'ait') ?></h2>

			{includePart portal/parts/single-item-reviews-form}
		{else}
			<h2><?php _e('Leave a Review', 'ait') ?></h2>

			<div class="ait-sc-notification attention">
				<div class="notify-wrap">
					<h5>{!$options->theme->itemReviews->onlyRegisteredMessage}</h5>
				</div>
			</div>
		{/if}
	{else}
		<h2><?php _e('Leave a Review', 'ait') ?></h2>

		{includePart portal/parts/single-item-reviews-form}
	{/if}

	{if $options->theme->itemReviews->maxShownReviews == 'all'}
		{var $reviews_query = AitItemReviews::getCurrentItemReviews($post->id)}
	{else}
		{var $reviews_query = AitItemReviews::getCurrentItemReviews($post->id, array('posts_per_page' => intval($options->theme->itemReviews->maxShownReviews), 'nopaging' => false))}
	{/if}
	</div>

	{if count($reviews_query->posts) > 0}
	<div class="content">
		{customLoop from $reviews_query as $review}

		{var $rating_overall = get_post_meta($review->id, 'rating_mean', true)}
		{var $rating_data = (array)json_decode(get_post_meta($review->id, 'ratings', true))}

		{var $ratingsDisplayedClass = AitItemReviews::willRatingsDisplay($rating_data) ? 'ratings-shown' : 'ratings-hidden'}

		{var $dateFormat = get_option('date_format')}

		<div class="review-container {$ratingsDisplayedClass}">
			<div class="review-info">
				<span class="review-name">{$review->rawTitle}</span>
				<span class="review-time"><span>{$review->rawDate|dateI18n: $dateFormat}</span>&nbsp;<span>{$review->time()}</span></span>
				{if is_array($rating_data) && count($rating_data) > 0}
				<div class="review-stars">
					<span class="review-rating-overall" data-score="{$rating_overall}"></span>
					<div class="review-ratings">
						{foreach $rating_data as $index => $rating}
							{if $rating->question}
							<div class="review-rating">
								<span class="review-rating-question">
									{$rating->question}
								</span>
								<span class="review-rating-stars" data-score="{$rating->value}"></span>
							</div>
							{/if}
						{/foreach}
					</div>
				</div>
				{/if}
			</div>
			<div class="content">
				{!$review->content}
			</div>
			{* REVIEW JSON-LD *}
			<script type="application/ld+json">
			{
				"@context": "http://schema.org/",
				"@type": "Review",
				"itemReviewed": {
					"@type": "LocalBusiness",
					"name": "{!$post->title}",
					"image": "{!$post->imageUrl ? $post->imageUrl : $options->theme->item->noFeatured}"
				},
				"reviewRating": {
					"@type": "Rating",
					"ratingValue": "{!$rating_overall}"
				},
				"author": {
					"@type": "Person",
					"name": "{!$review->title}"
				},
				"reviewBody": "{!strip_tags($review->content)}"
			}
			</script>
			{* REVIEW JSON-LD *}
		</div>
		{/customLoop}

		{if $options->theme->itemReviews->maxShownReviews != "all"}
			{if $reviews_query->found_posts - count($reviews_query->posts) != 0}
			<div class="reviews-ajax-container" data-all="{$reviews_query->found_posts}" data-shown="{count($reviews_query->posts)}">
				<a role="button">
					<span class="text">
						<span class="reviews-ajax-icon"><i data-icon-show="fa-angle-down" data-icon-hide="fa-angle-up" class="fa fa-angle-down"></i></span>
						<span class="reviews-ajax-info">
							{!__ "There are %s next ratings"|printf: "<span class='ajax-info-count'>" . ($reviews_query->found_posts - count($reviews_query->posts)) . "</span>"}
						</span>
					</span>

					<span class="reviews-ajax-button" data-text-show="{__ "View Next Ratings"}" data-text-hide="{__ "Hide Ratings"}">{__ "View Next Ratings"}</span>
				</a>

				<script type="text/javascript">
				jQuery(document).ready(function(){
					var $container = jQuery('.reviews-ajax-container');
					var params = {
						'action': 'loadReviews',
						'data': {
							'post_id': {$post->id},
							'query': {
								'offset': {intval($options->theme->itemReviews->maxShownReviews)},
								'nopaging': false,
								'posts_per_page': {intval($reviews_query->found_posts)},
							},
						}
					};

					jQuery.ajax({
						type: "POST",
						url: ait.ajax.url,
						data: params,
						beforeSend: function(){

						},
						success: function(data){
							jQuery(data.html).insertBefore('.reviews-ajax-container');

							var selectors = [
								".review-container .review-rating-overall",
								".review-container .review-rating-stars"
							];
							jQuery(selectors.join(', ')).raty({
								font: true,
								readOnly:true,
								halfShow:true,
								starHalf:'fa-star-half-o',
								starOff:'fa-star-o',
								starOn:'fa-star',
								score: function() {
									return jQuery(this).attr('data-score');
								},
							});

							//jQuery('.reviews-ajax-container .reviews-ajax-button').addClass('ajax-button-disabled');

							// update maximum height
							var expandedHeight = parseInt(jQuery('.reviews-container').children('.content').attr('data-height-expanded'));
							jQuery('.reviews-container').find('.review-ajax-loaded').each(function(){
								jQuery(this).css({'display' : 'block', 'visibility' : 'hidden'});
								expandedHeight = expandedHeight + jQuery(this).outerHeight(true);
								jQuery(this).css({'display' : '', 'visibility' : ''});
							});
							jQuery('.reviews-container').children('.content').attr('data-height-expanded', expandedHeight);
						},
					});

					var $contentContainer = jQuery('.reviews-container').children('.content');
					$contentContainer.attr('data-height-collapsed', $contentContainer.height());
					$contentContainer.attr('data-height-expanded', $contentContainer.height());
					//$contentContainer.attr('data-height-single', $contentContainer.find('.review-container:last').outerHeight(true));
					$contentContainer.css({'height': $contentContainer.attr('data-height-collapsed')});
				});

				jQuery('.reviews-ajax-container a').on('click', function(e){
					e.preventDefault();
					var $container = jQuery('.reviews-container');
					var $contentContainer = $container.children('.content');
					var $iconContainer = $container.find('.reviews-ajax-icon');
					var $button = $container.find('.reviews-ajax-button');

					var timeout = $container.hasClass('reviews-ajax-shown') ? 250 : 750;

					$container.toggleClass('reviews-ajax-shown');
					if($container.hasClass('reviews-ajax-shown')){
						$button.html($button.attr('data-text-hide'));
						$iconContainer.find('i').toggleClass($iconContainer.find('i').attr('data-icon-show'));
						$iconContainer.find('i').toggleClass($iconContainer.find('i').attr('data-icon-hide'));

						//$contentContainer.css({'height': parseInt($contentContainer.attr('data-height-collapsed')) + parseInt( jQuery('.review-ajax-loaded').length * $contentContainer.attr('data-height-single') )  });
						$contentContainer.css({'height': parseInt($contentContainer.attr('data-height-expanded'))});

						$contentContainer.find('.reviews-ajax-info').hide();
					} else {
						$button.html($button.attr('data-text-show'));
						$iconContainer.find('i').toggleClass($iconContainer.find('i').attr('data-icon-hide'));
						$iconContainer.find('i').toggleClass($iconContainer.find('i').attr('data-icon-show'));

						$contentContainer.css({'height': $contentContainer.attr('data-height-collapsed')});

						$contentContainer.find('.reviews-ajax-info').show();
					}

					setTimeout(function(){
						$container.find('.review-ajax-loaded').each(function(){
							if($container.hasClass('reviews-ajax-shown')){
								// fade in
								jQuery(this).fadeIn("slow");
							} else {
								// fade out
								jQuery(this).fadeOut("fast");
							}
						});

						//$container.find('.review-ajax-loaded').toggleClass('review-hidden');
					}, timeout);

					//$container.find('.review-ajax-loaded').toggleClass('review-hidden');


					/*$container.find('.review-ajax-loaded').each(function(){

					});*/

					/*if($container.hasClass('reviews-ajax-shown')){
						// showing
						$container.find('.review-ajax-loaded').each(function(){
							jQuery(this)
						});
					} else {
						// collapsing
					}*/

				});
				</script>
			</div>
			{/if}

		{/if}


		<script type="text/javascript">
			jQuery(document).ready(function() {
				/* Review Tooltip Off-screen Check */

				jQuery('#review .review-container:nth-last-of-type(-n+3) .review-stars').mouseenter(function() {
					reviewOffscreen(jQuery(this));
				});

				function reviewOffscreen(rating) {
					var reviewContainer = rating.find('.review-ratings');
					if (!reviewContainer.hasClass('off-screen')) {
						var	bottomOffset = jQuery(document).height() - rating.offset().top - reviewContainer.outerHeight() - 30;
						if (bottomOffset < 0) reviewContainer.addClass('off-screen');
					}
				}
			});
		</script>

	</div>
	{/if}

	<script type="text/javascript">
		jQuery(document).ready(function() {
			var $reviewsForm = jQuery('.reviews-container .reviews-form');

			jQuery('.reviews-container .submit-rating').on('click', function(e){
				e.preventDefault();
				jQuery(this).addClass('is-active');
				$reviewsForm.show();
			});

			var thumbRatingLink = jQuery('.detail-thumbnail-wrap .rating-text-shown a');

			if (!thumbRatingLink) return;

			jQuery('.rating-text-shown a').on('click', function(e){
				$reviewsForm.show();
			});
		});
	</script>
</div>
