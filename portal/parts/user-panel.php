{if $options->theme->header->userPanel == enable}

{var $descLogin = $options->theme->header->descLogin}
{var $descRegister = $options->theme->header->descRegister}
{var $helperUsername = $options->theme->header->helperUsername}
{var $helperEmail = $options->theme->header->helperEmail}
{var $conditions = $options->theme->header->conditions}
{var $captchaEnable = $options->theme->header->headerPanelUseCaptcha}

<div class="user-panel">

	{if is_user_logged_in()}

		{var $currentUser = wp_get_current_user()}

		<div class="user-info">
			<div class="user-avatar">{!get_avatar($currentUser->ID, 45)}</div>
			<!--<div class="user-name">{*$currentUser->user_nicename*}</div>-->
		</div>

		<div class="user-actions">
			<a href="{admin_url('profile.php')}" title="{__ 'Account'}" class="button-account">{__ 'Account'}<span><svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span></a>
			<a href="{!wp_logout_url(get_permalink())}" title="{__ 'Logout'}" class="button-logout">{__ 'Logout'}<span><svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></span></a>

			{if user_can( $currentUser, 'ait_toolkit_items_edit_items' )}
			{var $adminItemsUrl = 'edit.php?post_type=ait-item&author=' . $currentUser->ID}
			{var $itemsCount = intval(count_user_posts($currentUser->ID, "ait-item"))}
			<a href="{admin_url($adminItemsUrl)}" title="{__ 'Items'}" class="user-items">{__ 'My Items'}<span>{$itemsCount}</span></a>
			{/if}

			{if defined('AIT_EVENTS_PRO_ENABLED') && user_can( $currentUser, 'ait_toolkit_eventspro_edit_events' )}
			{var $adminEventsUrl = 'edit.php?post_type=ait-event-pro&author=' . $currentUser->ID}
			{var $eventsCount = intval(count_user_posts($currentUser->ID, "ait-event-pro"))}
			<a href="{admin_url($adminEventsUrl)}" title="{__ 'Events'}" class="user-events">{__ 'My Events'}<span>{$eventsCount}</span></a>
			{/if}
		</div>

		{? wp_enqueue_script( 'modernizr', aitPaths()->url->admin.'/modernizr/modernizr.touch.js') ?}

		<script type="text/javascript">
			jQuery(document).ready(function(){

				var userPanel = jQuery(".user-panel");

				if(!(Modernizr.touchevents || Modernizr.pointerevents)) {
					userPanel.mouseenter(function(){
						userPanel.addClass('opened');
					})
					userPanel.mouseleave(function(){
						userPanel.removeClass('opened');
					});
				} else {
					userPanel.find(".user-info").click(function(e) {
						e.preventDefault();

						userPanel.toggleClass("opened");
					});
				}

				jQuery('.main-nav .menu-toggle').on('touchstart', function(){
					jQuery(".user-panel").removeClass("opened");
				});

			});

		</script>

	{else}

		{var $rand = rand()}

		<a href="#" class="toggle-button" aria-label="{__ 'Login'}" role="button"><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a>

		<div class="login-register widget_login">
			<div class="userlogin-container user-not-logged-in">
				<div class="userlogin-tabs">
					<div class="userlogin-tabs-menu">
						<a class="userlogin-option-active" href="#">{__ 'Login'}</a>
						{if (get_option( 'users_can_register' ))}
						<a href="#">{__ 'Register'}</a>
						{/if}
					</div>
					<div class="userlogin-tabs-contents">
						<div class="userlogin-tabs-content userlogin-option-active">
							{if $descLogin != ''}
							<p>{!$descLogin}</p>
							{/if}
							{? wp_login_form( array( 'redirect' => get_permalink(), 'form_id' => 'ait-login-form-panel', 'echo' => true, 'remember' => false, 'id_username' => 'user_login_panel', 'id_password' => 'user_pass_panel', 'id_submit' => 'wp-submit-panel') ) }
						</div>
						{if (get_option( 'users_can_register' ))}

						<div class="userlogin-tabs-content">
							{if $descRegister !=''}
							<p>{!$descRegister}</p>
							{/if}
							<form method="post" action="{home_url('/?ait-action=register&source=panel')}" class="wp-user-form user-register-form">
								<p class="input-container input-username">
									<label for="user_login_reg">{__ 'Username'}</label>
									<input type="text" name="user_login" id="user_login_reg" value="" size="20" tabindex="81" />
									{if $helperUsername}<span class="helper">{!$helperUsername}</span>{/if}
								</p>
								<p class="input-container input-email">
									<label for="user_email_reg">{__ 'Email'}</label>
									<input type="text" name="user_email" id="user_email_reg" value="" size="20" tabindex="82" />
									{if $helperEmail}<span class="helper">{!$helperEmail}</span>{/if}
								</p>

								{*var $rand = rand()*}
								{var $themeOptions = $options->theme}
								{var $themePackages = new ThemePackages()}
								{var $orderedPackages = $themePackages->getOrderedPackages()}

								<p class="input-container input-role">
									{if count($orderedPackages) == 1}
										{var $package = $themePackages->getPackageBySlug($orderedPackages[0])}
										{var $packageOptions = $package->getOptions()}
										{var $isFree = $packageOptions['price'] == 0 ? "true" : "false"}
										<span>{$package->getName()} ({$packageOptions['price']} {$themeOptions->payments->currency})</span>
										<input type="hidden" name="user_role" value="{$package->getSlug()}" data-isfree="{$isFree}"/>
									{else}
										<select id="user_role_panel" name="user_role" tabindex="83">
											{foreach $orderedPackages as $key => $value}
												{var $package = $themePackages->getPackageBySlug($value)}
												{var $packageOptions = $package->getOptions()}
												{var $isFree = $packageOptions['price'] == 0 ? "true" : "false"}
												<option value="{$package->getSlug()}" data-isfree="{$isFree}">{$package->getName()} {$packageOptions['price']} {$themeOptions->payments->currency}</option>
											{/foreach}
										</select>
									{/if}
								</p>

								{var $themeConfig = aitConfig()->getFullConfig('theme')}
								{var $paymentGates = $themeOptions->payments}
								{? unset($paymentGates->currency)}
								{var $paymentGatesConfig = $themeConfig['payments']['@options'][1]}
								{var $paymentGatesInstalled = array()}
								{var $paymentGatesEnabled = array()}

								{foreach $paymentGates as $name => $value}
									{if $paymentGatesConfig[$name]['controller'] == "none" or class_exists($paymentGatesConfig[$name]['controller'])}
										{var $paymentGatesInstalled[$name] = $value}
									{/if}
								{/foreach}

								{foreach $paymentGatesInstalled as $name => $value}
									{if ((bool)$value == true)}
										{var $paymentGatesEnabled[$name] = $value}
									{/if}
								{/foreach}

								{var $paymentGatesTexts = array(
									'bankTransfer'    => __('Bank Transfer', 'ait'),
									'paypal' => 'PayPal',
									'paypalRecurring' => __('PayPal Recurring', 'ait'),
									'stripe' => 'Stripe',
								)}

								{if count($paymentGatesEnabled) > 0}
								{var $firstPackage = $themePackages->getPackageBySlug($orderedPackages[0])}
								{var $firstPackageOptions = $firstPackage->getOptions()}
								{if ($firstPackageOptions['price'] == 0)}
									<p class="input-container input-payment" style="display: none;">
								{else}
									<p class="input-container input-payment">
								{/if}
									<select id="user_payment_panel" name="user_payment" tabindex="84">
									{foreach $paymentGatesEnabled as $name => $value}
										<option value="{$name}">{$paymentGatesTexts[$name]}</option>
									{/foreach}
									</select>
								</p>
								{/if}

								{if $conditions != ""}
								<p class="input-container input-required-conditions">
									<input type="checkbox" name="required_conditions" id="required_conditions" />
									<label for="required_conditions">{$conditions|noescape}</label>
								</p>
								{/if}


								{* CAPTCHA *}
								{if $captchaEnable}

								{* CAPTCHA VALIDATION *}
								{if class_exists("AitReallySimpleCaptcha")}
									{var $captcha = new AitReallySimpleCaptcha()}
									{var $captcha->tmp_dir = aitPaths()->dir->cache . '/captcha'}

									{var $cacheUrl = aitPaths()->url->cache . '/captcha'}
									{var $img = $captcha->generate_image('ait-login-widget-captcha-'.$rand, $captcha->generate_random_word())}
									{var $imgUrl = $cacheUrl."/".$img}

									<p class="input-container input-captcha">
										<img src="{$imgUrl}" alt="captcha-input"/>
										<input type="text" name="user_captcha" id="user_captcha" value="" size="20" tabindex="84" />
									</p>
								{/if}
								{* CAPTCHA VALIDATION *}

								{/if}
								{* CAPTCHA *}


								<div class="login-fields">
									{? do_action('register_form') ?}
									<input type="submit" name="user-submit" value="{__ 'Sign up!'}" class="user-submit" tabindex="85" />
									<input type="hidden" name="redirect_to" value="{home_url()}" />
									<input type="hidden" name="user-cookie" value="1" />

									{* CAPTCHA *}
									{if $captchaEnable}
									<input type="hidden" name="rand" value="{$rand}" />
									{/if}
									{* CAPTCHA *}
								</div>

								<div class="login-messages">
									<div class="login-message-error" style="display: none">{__ 'Please fill out all registration fields'}</div>

									{* CAPTCHA *}
									{if $captchaEnable}
									<div class="captcha-error" style="display: none">{__ 'Captcha failed to verify'}</div>
									<div class="ajax-error" style="display: none">{__ 'There was a server error during ajax request'}</div>
									{/if}
									{* CAPTCHA *}
								</div>
							</form>
						</div>
						{/if}

					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function(){

				// Toggle Button
				jQuery(".user-panel .toggle-button").click(function(e) {
					e.preventDefault();

					jQuery(".user-panel").toggleClass("opened");
				});

				jQuery('.main-nav .menu-toggle').on('touchstart', function(){
					jQuery(".user-panel").removeClass("opened");
				});

				// Widget
				jQuery(".user-panel .userlogin-tabs-contents input[type=text], .user-panel .userlogin-tabs-contents input[type=password]").each(function(){
					var $label = jQuery(this).parent().find("label");
					var placeholder = $label.html();
					jQuery(this).attr("placeholder", placeholder);
					$label.hide();
				});

				var $tabs = jQuery(".user-panel .userlogin-container .userlogin-tabs-menu a");
				var $contents = jQuery(".user-panel .userlogin-container .userlogin-tabs-contents");
				var activeClass = "userlogin-option-active";
				$tabs.each(function(){
					jQuery(this).click(function(e){
						e.preventDefault();
						$tabs.each(function(){
							jQuery(this).removeClass(activeClass);
						});
						$contents.find(".userlogin-tabs-content").each(function(){
							jQuery(this).removeClass(activeClass);
						});
						jQuery(this).addClass(activeClass);
						$contents.find(".userlogin-tabs-content:eq("+jQuery(this).index()+")").addClass(activeClass);
					});
				});

				jQuery(".user-panel form.user-register-form select[name=user_role]").change(function(){
					var $payments = jQuery(".user-panel form.user-register-form select[name=user_payment]");
					var $selected = jQuery(this).find("option:selected");
					var isFree = $selected.data("isfree");
					if(isFree){
						// disable payment gates input
						$payments.attr("disabled", "disabled");
						$payments.parent().hide();
					} else {
						// enable payment gates input
						$payments.removeAttr("disabled");
						$payments.parent().show();
					}
				});

				jQuery(".user-panel form.user-register-form").on("submit", function(e){
					{* CAPTCHA *}
					{if $captchaEnable}
					e.preventDefault();
					{/if}
					{* CAPTCHA *}

					var $inputs = jQuery(this).find("input[type=text]");
					var $selects = jQuery(this).find("select:not(:disabled)");
					var $checkboxes = jQuery(this).find("input[type=checkbox]");
					var valid = false;
					var all = parseInt($selects.length + $inputs.length + $checkboxes.length);
					var validation = 0;
					$selects.each(function(){
						if(jQuery(this).val() != "-1"){
							validation = validation + 1;
						}
					});
					$inputs.each(function(){
						if(jQuery(this).val() != ""){
							if(jQuery(this).attr("name") == "user_email"){
								validation = validation + 1;
							} else {
								validation = validation + 1;
							}
						}
					});
					$checkboxes.each(function(){
						if(jQuery(this).prop("checked")){
								validation = validation + 1;
						}
					});
					if(validation == all){
						valid = true;
					}
					if(!valid){
						jQuery(this).find(".login-message-error").fadeIn("slow"); jQuery(this).find(".login-message-error").on("hover", function(){ jQuery(this).fadeOut("fast"); });
						return false;

					{* CAPTCHA *}
					{if $captchaEnable}
					} else {
						var data = { "captcha-check": jQuery(this).find("#user_captcha").val(), "captcha-hash": {$rand} };
						ait.ajax.post("login-widget-check-captcha:check", data).done(function(rdata){
							if(rdata.data == true){
								jQuery(".user-panel form.user-register-form").off("submit");
								jQuery(".user-panel form.user-register-form").submit();
							} else {
								jQuery(".user-panel form.user-register-form").find(".captcha-error").fadeIn("slow"); jQuery(".user-panel form.user-register-form").find(".captcha-error").on("hover", function(){ jQuery(this).fadeOut("fast"); });
							}
						}).fail(function(rdata){
							jQuery(".user-panel form.user-register-form").find(".ajax-error").fadeIn("slow");
							jQuery(".user-panel form.user-register-form").find(".ajax-error").on("hover", function(){
								jQuery(this).fadeOut("fast");
							});
						});
					{/if}
					{* CAPTCHA *}

					}
				});

			});
		</script>

	{/if}

</div>
{/if}
