{var $collapsed = isset($collapsed) ? $collapsed : false }

{if $meta->displayOpeningHours}
<div n:class="elm-opening-hours-main, $collapsed ? 'collapsed' : ''">
	{if $collapsed}
		<a class="opening-hours-toggle" role="button" aria-label="{__ 'Opening Hours'}">
			<h2>
				<i class="icon-clock inline"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></i>
				{__ 'Today'}

				<span class="hours"></span>
				<i class="icon-arrow"><svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></i>
			</h2>
		</a>
	{else}
		<h2>
			{__ 'Opening Hours'}
			<i class="icon-clock"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></i>
		</h2>
	{/if}

	<div class="opening-hours" {if $collapsed}style="display: none"{/if}>
		<div class="day-container">
			<div class="day-wrapper" data-day="1">
				<div class="day-title"><h5>{__ 'Monday'}</h5></div>
				{var $monday = AitLangs::getCurrentLocaleText($meta->openingHoursMonday)}
				<div class="day-data">
					<p>
						{if $monday}{$monday}
						<meta itemprop="openingHours" content="Mo {$monday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper" data-day="2">
				<div class="day-title"><h5>{__ 'Tuesday'}</h5></div>
				{var $tuesday = AitLangs::getCurrentLocaleText($meta->openingHoursTuesday)}
				<div class="day-data">
					<p>
						{if $tuesday}{$tuesday}
						<meta itemprop="openingHours" content="Tu {$tuesday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper" data-day="3">
				<div class="day-title"><h5>{__ 'Wednesday'}</h5></div>
				{var $wednesday = AitLangs::getCurrentLocaleText($meta->openingHoursWednesday)}
				<div class="day-data">
					<p>
						{if $wednesday}{$wednesday}
						<meta itemprop="openingHours" content="We {$wednesday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper" data-day="4">
				<div class="day-title"><h5>{__ 'Thursday'}</h5></div>
				{var $thursday = AitLangs::getCurrentLocaleText($meta->openingHoursThursday)}
				<div class="day-data">
					<p>
						{if $thursday}{$thursday}
						<meta itemprop="openingHours" content="Th {$thursday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper" data-day="5">
				<div class="day-title"><h5>{__ 'Friday'}</h5></div>
				{var $friday = AitLangs::getCurrentLocaleText($meta->openingHoursFriday)}
				<div class="day-data">
					<p>
						{if $friday}{$friday}
						<meta itemprop="openingHours" content="Fr {$friday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper day-sat" data-day="6">
				<div class="day-title"><h5>{__ 'Saturday'}</h5></div>
				{var $saturday = AitLangs::getCurrentLocaleText($meta->openingHoursSaturday)}
				<div class="day-data">
					<p>
						{if $saturday}{$saturday}
						<meta itemprop="openingHours" content="Sa {$saturday}">
						{else}-{/if}
					</p>
				</div>
			</div>
			<div class="day-wrapper day-sun" data-day="0">
				<div class="day-title"><h5>{__ 'Sunday'}</h5></div>
				{var $sunday = AitLangs::getCurrentLocaleText($meta->openingHoursSunday)}
				<div class="day-data">
					<p>
						{if $sunday}{$sunday}
						<meta itemprop="openingHours" content="Su {$sunday}">
						{else}-{/if}
					</p>
				</div>
			</div>
		</div>
		{var $note = AitLangs::getCurrentLocaleText($meta->openingHoursNote)}
		{if $note != ""}
		<div class="hours-note">
			<p>{$note}</p>
		</div>
		{/if}
	</div>

	{if $collapsed}
	<script type="text/javascript">
		jQuery(document).ready(function(){
			var $container = jQuery('.elm-opening-hours-main.collapsed');
			var $toggle = $container.find('.opening-hours-toggle');
			var $openingHours = $container.find('.opening-hours');
			var $hours = $toggle.find('.hours');
			var $currentDay = $container.find('[data-day='+ ((new Date()).getDay()) +']');
			var currentHours = $currentDay.find('.day-data > p').text();

			$currentDay.addClass('current');
			$hours.text(currentHours);

			$toggle.on('click', function(e) {
				e.preventDefault();
				$container.toggleClass('is-active');
				$openingHours.slideToggle(300);
			});
		});
	</script>
	{/if}
</div>
{/if}