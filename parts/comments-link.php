{if $post->hasCommentsOpen}
	<div class="comments-link">
		<a href="{$post->commentsUrl}" title="{__ 'Comments on %s'|printf: $post->title}">
			{if $post->commentsNumber > 1}
				<span class="comments-count" title="{_n '%s Comment', '%s Comments'|printf: $post->commentsNumber}">
					{if isset($iconLayout)}
					<i class="icon-chat"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></i>
					{/if}
					<span class="comments-number">{$post->commentsNumber}</span>{if !isset($iconLayout)} {__ 'Comments'}{/if}
				</span>
			{elseif $post->commentsNumber == 0}
				<span class="comments-count" title="{__ 'Leave a comment'}">
					{if isset($iconLayout)}
					<i class="icon-chat"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></i>
					{/if}
					<span class="comments-number">0</span>{if !isset($iconLayout)} {__ 'Comments'}{/if}
				</span>
			{else}
				<span class="comments-count" title="{__ '1 Comment'}">
					{if isset($iconLayout)}
					<i class="icon-chat"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></i>
					{/if}
					<span class="comments-number">1</span>{if !isset($iconLayout)} {__ 'Comment'}{/if}
				</span>
			{/if}
		</a>
	</div><!-- .comments-link -->
{/if}