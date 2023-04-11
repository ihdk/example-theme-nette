<?php

class AitLicensing
{
	public static function renderDomainControl()
	{
		if (self::isTF()) return;
		?>
		<div class="ait-opt-label">
			<div class="ait-label-wrapper">
				<label class="ait-label" for="ait-licensing-domain"><?php esc_html_e('Domain') ?></label>
			</div>
		</div>
		<div class="ait-opt ait-opt-text">
			<div class="ait-opt-wrapper">
				<input type="text" id="ait-licensing-domain" value="<?php echo esc_attr(self::domain()) ?>" onclick="this.focus();this.select()" readonly style="cursor: copy;">
			</div>
			<div class="ait-help"><?php echo wp_kses_post(__('You will need this when you will be generating API Key', 'ait')) ?></div>
		</div>
		<?php
	}



	public static function renderKeyControl()
	{
		if (self::isTF()) {
			$label = __('Purchase Code', 'ait');
			$option = 'tf_purchase_code';
			$value = self::purchaseCode();
		} else {
			$label = __('API Key', 'ait');
			$option = 'api_key';
			$value = self::apiKey();
		}
		?>
		<div class="ait-opt-label">
			<div class="ait-label-wrapper">
				<label class="ait-label" for="ait-licensing-key"><?php echo esc_html($label) ?></label>
			</div>
		</div>
		<div class="ait-opt ait-opt-text">
			<div class="ait-opt-wrapper">
				<input type="text" id="ait-licensing-key" name="_ait_updater_options[<?php echo esc_attr($option) ?>]" value="<?php echo esc_attr($value) ?>">
			</div>
			<div class="ait-help">
			<?php if(!self::isTF()): ?>
				<?php echo wp_kses_post(sprintf(__('You can generate API Key for the domain in your %sAitThemes account%s.', 'ait'), '<a href="https://system.ait-themes.club/account/api" target="_blank">', '</a>' )) ?>
			<?php else: ?>
				<?php echo wp_kses_post(sprintf(__('You can find Purchase Code for this theme in your %sThemeForest account &rarr; Downloads%s under the Download button.', 'ait'), '<a href="https://themeforest.net/downloads" target="_blank">', '</a>' )) ?>
			<?php endif ?>
			</div>
		</div>
		<?php
	}



	protected static function domainUsed()
	{
		$o = get_transient('check_ait_subscription');
		if (!empty($o->body['domain'])) {
			return $o->body['domain'];
		}
	}


	
	protected static function purchaseCode()
	{
		$o = get_option('_ait_updater_options');
		return !empty($o['tf_purchase_code']) ? $o['tf_purchase_code'] : '';
	}



	protected static function apiKey()
	{
		$o = get_option('_ait_updater_options');
		return !empty($o['api_key']) ? $o['api_key'] : '';
	}



	public static function handleConfig($value='')
	{
		add_filter('ait-get-full-config', function ($config, $type) {
			if ($type !== 'theme') return $config;
			if (isset($config['licensing']) and !is_super_admin()) {
				unset($config['licensing']);
			}
			return $config;
		}, 10, 2);
	}



	public static function adminNotice()
	{
		add_action('admin_notices', function() {
			if (!is_super_admin()) {
				return;
			}
			if (self::isUnauthorized()) {
				$tf = self::isTF();
				$domain = self::domainUsed();
				if ($tf && $domain) {
					$title = __('Purchase Code is already used', 'ait');
					$message = sprintf(__('Purchase Code %s is already used on "%s". If you want to use it on this website, you can %sderegister it here%s.', 'ait'), self::purchaseCode(), $domain, '<a href="https://system.ait-themes.club/account/themeforest">', '</a>');
				} else if ($tf) {
					$title = __('Invalid Purchase Code', 'ait');
					$message = sprintf(__('Please go to %sTheme Admin &rarr; Theme Options &rarr; ThemeForest Purchase Code%s to configure it.', 'ait'), '<a href="' . admin_url('admin.php?page=ait-theme-options#ait-theme-options-licensing-panel') . '">', '</a>');
				} else {
					$title = __('Invalid API Key for this domain', 'ait');
					$message = sprintf(__('Please enter a valid API key for this domain. You can configure it in %sTheme Admin &rarr; Theme Options &rarr; API Key%s.', 'ait'), '<a href="' . admin_url('admin.php?page=ait-theme-options#ait-theme-options-licensing-panel') . '">', '</a>');
				}
			} else {
				if ($tf) {
					$title = __('We can\'t verify your Purchase Code right now.', 'ait');
					$message = sprintf(__('Please try to check your product activation later here: %sTheme Admin &rarr; Theme Options &rarr; ThemeForest Purchase Code%s', 'ait'), '<a href="' . admin_url('admin.php?page=ait-theme-options#ait-theme-options-licensing-panel') . '">', '</a>');
				} else {
					$title = __('We can\'t verify your API key right now.', 'ait');
					$message = sprintf(__('Please try to check your product activation later here: %sTheme Admin &rarr; Theme Options &rarr; API Key%s', 'ait'), '<a href="' . admin_url('admin.php?page=ait-theme-options#ait-theme-options-licensing-panel') . '">', '</a>');
				}
			}			
			printf(
				'<div class="notice notice-warning notice-large"><p><strong class="notice-title">%1$s</strong><br>%2$s</p></div>',
				esc_html($title),
				wp_kses_post($message)
			);
		});
	}



	public static function frontendNotice()
	{
		add_filter('template_include', function($template) {
			if (self::isTF()) {
				if ($domain = self::domainUsed()) {
					wp_die(sprintf(__('This Purchase Code is already used on "%s". Please check WordPress admin for more details.', 'ait'), $domain), __('Purchase Code is already used', 'ait'));
				} else {
					wp_die(__('Please enter a valid ThemeForest Purchase Code. You can configure it in Theme Options.', 'ait'), __('Invalid Purchase Code', 'ait'));
				}
			} else {
				wp_die(__('Please enter a valid API key for this domain. You can configure it in Theme Options.', 'ait'), __('Invalid API Key for this domain', 'ait'));
			}
			return $template;
		});
	}



	protected static function check()
	{
		if (self::isLocalhost()) {
			return 200;
		}
		$current = get_transient('check_ait_subscription');
		if (!is_object($current)) {
			$current = new stdClass;
		}
		$option = new stdClass;
		$option->lastChecked = time();
		$interval = 24 * HOUR_IN_SECONDS;
		if (isset($current->lastChecked) && $interval > (time() - $current->lastChecked)) {
			if (!empty($current->responseCode)) return $current->responseCode;
			if (empty(self::apiKey()) or empty(self::purchaseCode())) {
				return 401;
			}
		}
		$current->lastChecked = time();
		set_transient('check_ait_subscription', $current);
		$response = wp_remote_post('https://system.ait-themes.club/api/5.0/subscriptions/check', array(
			'timeout' => 3,
			'body' => array(
				'domain'      => self::domain(),
				'key'         => self::isTF() ? self::purchaseCode() : self::apiKey(),
				'package'     => AIT_THEME_PACKAGE,
				'theme'       => AIT_THEME_CODENAME
			)
		));
		if (is_wp_error($response)) {
			error_log($response->get_error_message());
			return 500;
		}
		$option->body = json_decode(wp_remote_retrieve_body($response), true);
		$option->responseCode = wp_remote_retrieve_response_code($response);
		set_transient('check_ait_subscription', $option);
		return $option->responseCode;
	}



	protected static function isLocalhost()
	{
		$domain = self::domain();
		foreach (array(
			'localhost',
			'127.0.0.1',
		) as $host) {
			if (strpos($domain, $host) !== false) {
				return true;
			}
		}
		if (!empty($_SERVER['SERVER_PORT']) and !in_array($_SERVER['SERVER_PORT'], array(80, 443))) {
			return true;
		}
		return false;
	}



	public static function isOk()
	{
		return self::check() == 200;
	}



	public static function isUnauthorized()
	{
		return self::check() == 401;
	}



	public static function isForbidden()
	{
		return self::check() == 403;
	}



	protected static function isTF()
	{
		return (AIT_THEME_PACKAGE === 'themeforest');
	}



	protected static function domain()
	{
		$domain = preg_replace('|https?://|', '', get_option('siteurl'));
		$slash = strpos($domain, '/');
		if ($slash) {
			$domain = substr($domain, 0, $slash);
		}
		return $domain;
	}



	public static function interceptSaveThemeOptions(){
		add_action('ait-save-options', function ($data) {
			if (!is_super_admin()) return;
			if (isset($data['_ait_updater_options']['api_key'])) {
				$options = get_option('_ait_updater_options', array());
				$options['api_key'] = trim($data['_ait_updater_options']['api_key']);
				update_option('_ait_updater_options', $options);
			} elseif (isset($data['_ait_updater_options']['tf_purchase_code'])) {
				$options = get_option('_ait_updater_options', array());
				$options['tf_purchase_code'] = trim($data['_ait_updater_options']['tf_purchase_code']);
				update_option('_ait_updater_options', $options);
			}
			delete_transient('check_ait_subscription');
		});
	}
}
