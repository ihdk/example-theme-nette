<div class="item-infocard">
    {* OPENING HOURS SECTION *}
    {includePart portal/parts/single-item-opening-hours, collapsed => true}
    {* OPENING HOURS SECTION *}

    {* MAP SECTION *}
    {includePart portal/parts/single-item-map}
    {* MAP SECTION *}

    {* GET DIRECTIONS SECTION *}
    {if defined('AIT_GET_DIRECTIONS_ENABLED')}
        {includePart portal/parts/get-directions-container}
    {/if}
    {* GET DIRECTIONS SECTION *}

    {* ADDRESS SECTION *}
    {includePart portal/parts/single-item-address}
    {* ADDRESS SECTION *}
</div>