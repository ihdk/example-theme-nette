{block content}

	{loop as $post}
		{* SETTINGS AND DATA *}

		{? wp_enqueue_style( 'full-calendar', aitPaths()->url->ait . '/assets/fullcalendar/fullcalendar.min.css') }
		{? wp_enqueue_script( 'moment', aitPaths()->url->ait . '/assets/fullcalendar/lib/moment.min.js') }
		{? wp_enqueue_script( 'full-calendar', aitPaths()->url->ait . '/assets/fullcalendar/fullcalendar.min.js', 'moment') }
		{if AitLangs::getCurrentLanguageCode() != 'en'}
			{? wp_enqueue_script( 'full-calendar-translation', aitPaths()->url->ait . '/assets/fullcalendar/lang/'.AitLangs::getFullCalendarLocale().'.js') }
		{/if}

		{var $meta = $post->meta('event-pro-data')}
		{var $settings = get_option('ait_events_pro_options', array())}
		{var $relatedItemMeta = $meta->item ? get_post_meta($meta->item, _ait-item_item-data, true) : ""}
		{var $relatedItem = get_post($meta->item) }

		{* SETTINGS AND DATA *}


		{* CONTENT SECTION *}

			<div class="column-grid column-grid-3">
				<div class="column column-span-1 column-narrow column-first">
				{* GALLERY SECTION *}
				{includePart portal/parts/single-event-thumbnail}
				{* GALLERY SECTION *}

				{* DATE SECTION *}
				{includePart portal/parts/single-event-date, eventId => $post->id}
				{* DATE SECTION *}

				{* ADDRESS SECTION *}
				{includePart portal/parts/single-event-address}
				{* ADDRESS SECTION *}

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

					{* FEE SECTION *}
					{includePart portal/parts/single-event-fee}
					{* FEE SECTION *}

					{includePart "portal/parts/event-taxonomy", itemID => $post->id, taxonomy => 'ait-events-pro', onlyParent => true, count => 5, wrapper => true, sectionTitle => true}

					{if !$wp->hasSidebar('left') && !$wp->hasSidebar('right')}
						{* ORGANIZER SECTION *}
						{includePart parts/event-recurring-dates, 'dates' => AitEventsPro::getEventRecurringDates($post->id)}
						{includePart portal/parts/single-event-organizer}
						{*  ORGANIZER SECTION *}

						{* RICH SNIPPETS *}
						{includePart portal/parts/single-event-richsnippets}
						{* RICH SNIPPETS *}
					{/if}

				</div>
			</div>

		{* CONTENT SECTION *}

		{if $wp->hasSidebar('left') || $wp->hasSidebar('right')}

			{* ORGANIZER SECTION *}
			{includePart parts/event-recurring-dates, 'dates' => AitEventsPro::getEventRecurringDates($post->id)}
			{includePart portal/parts/single-event-organizer}
			{* ORGANIZER SECTION *}

			{* RICH SNIPPETS *}
			{includePart portal/parts/single-event-richsnippets}
			{* RICH SNIPPETS *}

		{/if}

	{/loop}
