<div n:class="'address-container', $meta->displaySocialIcons && is_array($meta->socialIcons) && count($meta->socialIcons) > 0 ? social-icons-displayed">
	<h2>
		{__ 'Address'} & {__ 'Contact'}
		<i class="icon-pin"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></i>
	</h2>

	<div class="content">
		{if !$meta->map['address'] && $settings->addressHideEmptyFields}{else}
		<div class="address-row row-postal-address" itemscope itemtype="http://schema.org/PostalAddress">
			<div class="address-name">
				<i class="icon-pin"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></i>
				<h5>{__ 'Our Address'}</h5>
			</div>
			<div class="address-data" itemprop="streetAddress"><p>{if $meta->map['address']}{$meta->map['address']}{else}-{/if}</p></div>
		</div>
		{/if}

		{if !$settings->addressHideGpsField}
		{if ($meta->map['latitude'] === "1" && $meta->map['longitude'] === "1") != true}

		<div class="address-row row-gps" itemscope itemtype="http://schema.org/Place">
			<div class="address-name">
				<i class="icon-crosshair"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="22" y1="12" x2="18" y2="12"></line><line x1="6" y1="12" x2="2" y2="12"></line><line x1="12" y1="6" x2="12" y2="2"></line><line x1="12" y1="22" x2="12" y2="18"></line></svg></i>
				<h5>{__ 'GPS'}</h5>
			</div>
			<div class="address-data" itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
				<p>
					{if $meta->map['latitude'] && $meta->map['longitude']}
						{$meta->map['latitude']}, {$meta->map['longitude']}
						<meta itemprop="latitude" content="{$meta->map['latitude']}">
						<meta itemprop="longitude" content="{$meta->map['longitude']}">
					{else}-{/if}
				</p>
			</div>
		</div>
		{/if}
		{/if}

		{if !$meta->telephone && $settings->addressHideEmptyFields}{else}
		<div class="address-row row-telephone">
			<div class="address-name">
				<i class="icon-phone"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg></i>
				<h5>{__ 'Telephone'}</h5>
			</div>
			<div class="address-data">
				{if $meta->telephone}
				<p>
					<span itemprop="telephone"><a href="tel:{!= str_replace(' ', '', $meta->telephone)}" class="phone">{$meta->telephone}</a></span>
				</p>
				{else}
				<p>-</p>
				{/if}

				{if is_array($meta->telephoneAdditional) && count($meta->telephoneAdditional) > 0}
					{foreach $meta->telephoneAdditional as $data}
					<p>
						<span itemprop="telephone"><a href="tel:{!= str_replace(' ', '', $data['number'])}" class="phone">{$data['number']}</a></span>
					</p>
					{/foreach}
				{/if}
			</div>

		</div>
		{/if}

		{if $settings->addressHideEmptyFields}
			{if $meta->email != ""}
				{if $meta->showEmail}
					<div n:class="address-row, row-email, !$meta->showEmail ? hide-email">
						<div class="address-name">
							<i class="icon-mail"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></i>
							<h5>{__ 'Email'}</h5>
						</div>
						<div class="address-data"><p><a href="mailto:{$meta->email}" target="_top" itemprop="email">{$meta->email}</a></p></div>
					</div>
				{else}
					{* dont display anything *}
				{/if}
			{else}
				{* dont display anything *}
			{/if}
		{else}
			{if $meta->email != ""}
				{if $meta->showEmail}
					<div n:class="address-row, row-email, !$meta->showEmail ? hide-email">
						<div class="address-name">
							<i class="icon-mail"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></i>
							<h5>{__ 'Email'}</h5>
						</div>
						<div class="address-data"><p><a href="mailto:{$meta->email}" target="_top" itemprop="email">{$meta->email}</a></p></div>
					</div>
				{else}
					<div n:class="address-row, row-email, !$meta->showEmail ? hide-email">
						<div class="address-name">
							<i class="icon-mail"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></i>
							<h5>{__ 'Email'}</h5>
						</div>
						<div class="address-data"><p>-</p></div>
					</div>
				{/if}
			{else}
				<div n:class="address-row, row-email, !$meta->showEmail ? hide-email">
					<div class="address-name">
						<i class="icon-mail"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></i>
						<h5>{__ 'Email'}</h5>
					</div>
					<div class="address-data"><p>-</p></div>
				</div>
			{/if}
		{/if}

		{if !$meta->web && $settings->addressHideEmptyFields}{else}
		<div class="address-row row-web">
			<div class="address-name">
				<i class="icon-web"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg></i>
				<h5>{__ 'Web'}</h5>
			</div>
			<div class="address-data"><p>{if $meta->web}<a href="{$meta->web}" target="_blank" itemprop="url" {if $settings->addressWebNofollow}rel="nofollow"{/if}>{if $meta->webLinkLabel}{$meta->webLinkLabel}{else}{$meta->web}{/if}</a>{else}-{/if}</p></div>
		</div>
		{/if}

		{if $meta->displaySocialIcons}
		<div class="address-row row-social">
			<div class="address-name">
				<i class="icon-share"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg></i>
				<h5>{__ 'Soc. Networks'}</h5>
			</div>
			<div class="address-data">
				{includePart portal/parts/single-item-social-icons}
			</div>
		</div>
		{/if}
	</div>

	{if $meta->contactOwnerBtn && $meta->email || defined('AIT_GET_DIRECTIONS_ENABLED')}
	<div class="address-footer">
		{* CONTACT OWNER SECTION *}
		{includePart portal/parts/single-item-contact-owner}
		{* CONTACT OWNER SECTION *}

		{* GET DIRECTIONS SECTION *}
		{if defined('AIT_GET_DIRECTIONS_ENABLED')}
			{includePart portal/parts/get-directions-button}
		{/if}
		{* GET DIRECTIONS SECTION *}
	</div>
	{/if}
</div>