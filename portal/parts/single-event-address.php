{var $address                = aitEventAddress($post, true)}
{var $eventAdress            = $address['address']}
{var $eventLocationLatitude  = $address['latitude']}
{var $eventLocationLongitude = $address['longitude']}
{var $addressHideGpsField    = $eventsProOptions['addressHideGpsField']}
{var $addressHideEmptyFields = $eventsProOptions['addressHideEmptyFields']}

<div class="address-container data-container">

	<div class="content">
		{if !$eventAdress && $addressHideEmptyFields}{else}
		<div class="address data">
			<div class="address-text data-content">
				<div class="address-data">
					<h4>
						{__ 'Event Venue'}

						<i class="icon-pin"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></i>
					</h4>
					<p>{if $eventAdress}{$eventAdress}{else}-{/if}</p>
				</div>
				{if !$addressHideGpsField}
				<div class="address-gps">
					<p>
						<strong>{__ "GPS:"} </strong>
						{if $eventLocationLatitude && $eventLocationLongitude}
							{$eventLocationLatitude}, {$eventLocationLongitude}
						{else}-{/if}
					</p>
				</div>
				{/if}

				{*if defined('AIT_GET_DIRECTIONS_ENABLED')*}
				<!--<div class="ait-get-directions-button"></div>-->
				{*/if*}
			</div>
		</div>
		{/if}
	</div>

</div>