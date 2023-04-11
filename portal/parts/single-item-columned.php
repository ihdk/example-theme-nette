<div class="custom-sidebar column-grid column-grid-3">
    <div class="column column-span-1 column-narrow column-first">

        {* GALLERY SECTION *}
        {includePart portal/parts/single-item-gallery}
        {* GALLERY SECTION *}

        {* INFOCARD SECTION *}
        {includePart portal/parts/single-item-infocard}
        {* INFOCARD SECTION *}

        {* CLAIM LISTING SECTION *}
        {if defined('AIT_CLAIM_LISTING_ENABLED')}
            {includePart portal/parts/claim-listing}
        {/if}
        {* CLAIM LISTING SECTION *}

    </div>

    <div class="column column-span-2 column-narrow column-last">

        {* CONTENT SECTION *}
        <div class="entry-content-wrap" itemprop="description">
            <div class="entry-content">
                {if $post->hasContent}
                    {!$post->content}
                {else}
                    {!$post->excerpt}
                {/if}
            </div>
        </div>
        {* CONTENT SECTION *}

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
    </div>
</div>
