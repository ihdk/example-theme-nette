<span class="author vcard">
	<span class="auth-links">
		<a class="url fn n" href="{$post->author->postsUrl}" title="{__ 'View all posts by %s'|printf: $post->author}" rel="author">
			<i class="icon-user"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></i>
			{$post->author}
		</a>
	</span>
</span>