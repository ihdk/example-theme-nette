{var $disabled = 'yes'}

{if $meta->contactOwnerBtn && $meta->email}
	{var $disabled = ''}
{/if}

<div n:class="contact-owner-container, $disabled ? contact-owner-disabled">

	{if !$disabled}
	<a href="#contact-owner-popup-form" id="contact-owner-popup-button" class="contact-owner-popup-button">{$settings->contactOwnerButtonTitle|trimWords:10}</a>
	<div class="contact-owner-popup-form-container" style="display: none">

		<form id="contact-owner-popup-form" class="contact-owner-popup-form" onSubmit="javascript:contactOwnerSubmit(event);">
			<h3>{$settings->contactOwnerButtonTitle}</h3>
			<input type="hidden" name="response-email-address" value="{$meta->email}">
			<input type="hidden" name="response-email-content" value="{$settings->contactOwnerMailForm}">
			{if $settings->contactOwnerMailFromName}
			<input type="hidden" name="response-email-sender-name" value="{$settings->contactOwnerMailFromName}">
			{/if}

			{if $settings->contactOwnerMailFromEmail}
			<input type="hidden" name="response-email-sender-address" value="{$settings->contactOwnerMailFromEmail}">
			{else}
			<input type="hidden" name="response-email-sender-address" value="{get_option('admin_email')}">
			{/if}

			<div class="input-container">
				<input type="text" class="input name" name="user-name" value="" placeholder="{$settings->contactOwnerInputNameLabel}" id="user-name">
				{if isset($settings->contactOwnerInputNameHelper) && $settings->contactOwnerInputNameHelper != ""}
					<span class="input-helper">{!$settings->contactOwnerInputNameHelper}</span>
				{/if}
			</div>

			<div class="input-container">
				<input type="text" class="input email" name="user-email" value="" placeholder="{$settings->contactOwnerInputEmailLabel}" id="user-email">
				{if isset($settings->contactOwnerInputEmailHelper) && $settings->contactOwnerInputEmailHelper != ""}
					<span class="input-helper">{!$settings->contactOwnerInputEmailHelper}</span>
				{/if}
			</div>

			<div class="input-container">
				<input type="text" class="input subject" name="response-email-subject" value="" placeholder="{$settings->contactOwnerInputSubjectLabel}" id="user-subject">
				{if isset($settings->contactOwnerInputSubjectHelper) && $settings->contactOwnerInputSubjectHelper != ""}
					<span class="input-helper">{!$settings->contactOwnerInputSubjectHelper}</span>
				{/if}
			</div>

			<div class="input-container">
				<textarea class="user-message" name="user-message" cols="30" rows="4" placeholder="{$settings->contactOwnerInputMessageLabel}" id="user-message"></textarea>
				{if isset($settings->contactOwnerInputMessageHelper) && $settings->contactOwnerInputMessageHelper != ""}
					<span class="input-helper">{!$settings->contactOwnerInputMessageHelper}</span>
				{/if}
			</div>

			{*CAPTCHA*}
			{if $settings->contactOwnerCaptcha && class_exists("AitReallySimpleCaptcha") }
				{var $captcha = new AitReallySimpleCaptcha() }
				{var $captcha->tmp_dir = aitPaths()->dir->cache . '/captcha' }
				{var $cacheUrl = aitPaths()->url->cache . '/captcha' }
				{var $rand = rand() }
				{var $img = $captcha->generate_image('ait-contact-owner-captcha-'.$rand, $captcha->generate_random_word()) }
				{var $imgUrl = $cacheUrl . "/" . $img }
				<div class="input-container captcha-check">
					<img src="{$imgUrl}" alt="captcha-input"/>
					<input type="text" class="input user-captcha" name="user-captcha" value="" placeholder="{$settings->contactOwnerInputCaptchaLabel}">
					<input type="hidden" class="rand-captcha" name="rand" value="{$rand}" />
				</div>
			
			{/if}

			<div class="input-container btn">
				<button class="contact-owner-send" type="submit">{$settings->contactOwnerSendButtonLabel}</button>
				<i class="fa fa-refresh fa-spin" style="margin-left: 10px; display: none;"></i>
			</div>

			<div class="messages">
				<div class="message message-success" style="display: none">{$settings->contactOwnerMessageSuccess}</div>
				<div class="message message-error-user" style="display: none">{$settings->contactOwnerMessageErrorUser}</div>
				<div class="message message-error-server" style="display: none">{$settings->contactOwnerMessageErrorServer}</div>
			</div>
		</form>

	</div>
	<script type="text/javascript" n:syntax="off">
	jQuery(document).ready(function(){
		jQuery("#contact-owner-popup-button").colorbox({ inline:true, href:"#contact-owner-popup-form", maxWidth:"95%" });
	});
	function contactOwnerSubmit(e){
		e.preventDefault();

		var $form = jQuery("#"+e.target.id);
		var $inputs = $form.find('input, textarea');
		var $submitButton = $form.find("button.contact-owner-send");
		var $loader = $form.find("i.fa-refresh");
		$loader.fadeIn('slow');
		var $messages = $form.find('.messages');
		var mailCheck = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		var mailParsed = $form.find('.email').val();
		// validate form data
			var passedInputs = 0;
			// check for empty inputs -- all inputs must be filled
			$inputs.each(function(){
				var inputValue = jQuery(this).val();
				if(inputValue !== ""){
					passedInputs = passedInputs + 1;
				}
			});
			// check for email field -- must be a valid email form

			//check if captcha is turned on
			if($form.find('.input-container.captcha-check').length){
				var $captchaContainer = $form.find('.input-container.captcha-check');
				var data = {"captcha-check": $captchaContainer.find(".user-captcha").val(), "captcha-hash": $captchaContainer.find(".rand-captcha").val()};
				ait.ajax.post("contact-owner-captcha:check", data).done(function(rdata){
					if(rdata.data == true){
						//captcha is OK
						if(passedInputs == $inputs.length && mailCheck.test(mailParsed) ){
							// ajax post -- if data are filled
							var data = {};
							$inputs.each(function(){
								data[jQuery(this).attr('name')] = jQuery(this).val();
							});
							//disable send button
							$submitButton.attr('disabled', true);
							//send email
							ait.ajax.post('contact-owner:send', data).done(function(data){
								if(data.success == true){
									$messages.find('.message-success').fadeIn('fast').delay(3000).fadeOut("fast", function(){
										//regenerate captcha
										regenerateCaptchaContactOwner($captchaContainer);
										jQuery.colorbox.close();
										$form.find('input[type=text], textarea').each(function(){
											jQuery(this).attr('value', "");
										});
										$submitButton.removeAttr('disabled');
									});
									$loader.fadeOut('slow');
								} else {
									$messages.find('.message-error-server').fadeIn('fast').delay(3000).fadeOut("fast");
									$submitButton.removeAttr('disabled');
									//regenerate captcha
									regenerateCaptchaContactOwner($captchaContainer);
									$loader.fadeOut('slow');
								}
								
							}).fail(function(){
								$messages.find('.message-error-server').fadeIn('fast').delay(3000).fadeOut("fast");
								$submitButton.removeAttr('disabled');
								//regenerate captcha
								regenerateCaptchaContactOwner($captchaContainer);
								$loader.fadeOut('slow');
							});
							// display result based on response data
						} else {
							// display bad message result
							$messages.find('.message-error-user').fadeIn('fast').delay(3000).fadeOut("fast");
							//regenerate captcha
							regenerateCaptchaContactOwner($captchaContainer);
							$loader.fadeOut('slow');
						}

					} else {
						//captcha check failed
						// display bad message result
						$messages.find('.message-error-user').fadeIn('fast').delay(3000).fadeOut("fast");
						//regenerate captcha
						regenerateCaptchaContactOwner($captchaContainer);
						$loader.fadeOut('slow');

					}
				}).fail(function(rdata){
					//captcha ajax failed
					$messages.find('.message-error-server').fadeIn('fast').delay(3000).fadeOut("fast");
					$submitButton.removeAttr('disabled');
					$loader.fadeOut('slow');
				});
			
			}else{
			
				//no captcha used, send mail

				if(passedInputs == $inputs.length && mailCheck.test(mailParsed) ){
					// ajax post -- if data are filled
					var data = {};
					$inputs.each(function(){
						data[jQuery(this).attr('name')] = jQuery(this).val();
					});
					//disable send button
					$submitButton.attr('disabled', true);
					ait.ajax.post('contact-owner:send', data).done(function(data){
						if(data.success == true){
							$messages.find('.message-success').fadeIn('fast').delay(3000).fadeOut("fast", function(){
								jQuery.colorbox.close();
								$form.find('input[type=text], textarea').each(function(){
									jQuery(this).attr('value', "");
								});
								$submitButton.removeAttr('disabled');
							});
						} else {
							$messages.find('.message-error-server').fadeIn('fast').delay(3000).fadeOut("fast");
							$submitButton.removeAttr('disabled');
						}
						$loader.fadeOut('slow');
					}).fail(function(){
						$messages.find('.message-error-server').fadeIn('fast').delay(3000).fadeOut("fast");
						$submitButton.removeAttr('disabled');
						$loader.fadeOut('slow');
					});
					// display result based on response data
				} else {
					// display bad message result
					$messages.find('.message-error-user').fadeIn('fast').delay(3000).fadeOut("fast");
					$loader.fadeOut('slow');
				}
			}

	}
	
	function regenerateCaptchaContactOwner( $captchaContainer ) {
		/* new captcha */
		if($captchaContainer.find('img').length > 0){
			var $captchaImage = $captchaContainer.find('img');
			$captchaImage.fadeTo("slow", 0);
			// ajax load new captcha
			ait.ajax.get('contact-owner-captcha:getCaptcha', null).done(function(xhr){
					$captchaContainer.find('input.rand-captcha').val(xhr.data.rand);
					var $imageUrl = xhr.data.url;
					$captchaImage.attr('src', $imageUrl);
					$captchaImage.fadeTo("slow", 1);
			}).fail(function(){
				console.error("get captcha failed");
			});
		}
		/* new captcha */
	}


	</script>
	{else}
	<a href="#contact-owner-popup-form" id="contact-owner-popup-button" class="contact-owner-popup-button">{$settings->contactOwnerButtonDisabledTitle|trimWords:10}</a>
	{/if}
</div>
