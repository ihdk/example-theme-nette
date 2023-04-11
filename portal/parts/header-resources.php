{if $options->theme->header->displayHeaderResources}
{var $args = array(
	'lang'           => AitLangs::getCurrentLanguageCode(),
	'post_type'      => 'ait-item',
	'post_status'	 => 'publish',
	'posts_per_page' => -1,
	'fields'		 => 'ids',
)}

{var $resources = get_posts($args)}
{var $url = $options->theme->header->headerResourcesButtonLink }
{var $link = is_user_logged_in() ? admin_url('post-new.php?post_type=ait-item') : get_permalink( function_exists('pll_get_post') ? pll_get_post( $url ) : $url )}

<div class="header-resources">
	<a href="{!$link}" class="resources-wrap">
		<span class="resources-data">
			<span class="resources-count" title="{__ 'Resources'}">{count($resources)}</span>
		</span>

		<span href="{!$link}" class="resources-button ait-sc-button">{__ 'Add'}</span>
	</a>
</div>
{/if}
