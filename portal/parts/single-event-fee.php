{if isset($meta->fee)}

{var $count = ( is_array($meta->fee) && count($meta->fee) > 1 ) ? 'multiple-fees' : ''}

<div class="fee-container data-container">
	<div class="content">
		<div class="fee data">
			<h6>
				{__ 'Fees & Tickets'}

				<i class="icon-cart"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></i>
			</h6>
			<div class="fee-text data-content {!$count}">
				{if !$meta->fee}
					<div class="fee-data">
						<div class="fee-info">
							<div class="fee-label">
								<span>{__ 'Free'}</span>
							</div>
							<div class="fee-price free">
								<div class="ait-button disabled">
									{currency 0, $meta->currency}
								</div>
							</div>
						</div>
					</div>
				{else}
					{foreach $meta->fee as $feeData}
					<div class="fee-data">
						<div class="fee-info">
							{if $feeData['name']}
							<div class="fee-label">
								<span>{$feeData['name']}</span>
								{if $feeData['desc']}
								<div class="fee-desc">{$feeData['desc']}</div>
								{/if}
							</div>
							{/if}
							<div class="fee-price">
								{if isset($feeData['url']) and $feeData['url'] != ''}
									<a href="{!$feeData['url']}" target="_blank" title="{__ 'Buy Ticket'}">
								{/if}
									<div class="ait-button {!(isset($feeData['url']) and $feeData['url'] != '') ?: 'disabled'}">
										{if empty($feeData['price'])}
											<span>{__ 'Free'}</span>
										{else}
											{currency $feeData['price'], $meta->currency}
										{/if}
									</div>
								{if isset($feeData['url']) and $feeData['url'] != ''}
									</a>
								{/if}
							</div>
						</div>
					</div>
					{/foreach}
				{/if}
			</div>
		</div>
	</div>
</div>

{/if}
