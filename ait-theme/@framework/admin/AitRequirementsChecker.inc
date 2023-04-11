<?php


/**
 * Checks system requirements right after activating theme for the first time
 */
class AitRequirementsChecker
{
	private $requirements;
	private $previousTemplate;
	private $report;



	public function __construct($previousTemplate)
	{
		$this->addTests();

		$this->report = (object) null;
		$this->report->errors = false;
		$this->report->warnings = false;
		$this->previousTemplate = $previousTemplate;
	}



	private function addTests()
	{
		$url = get_template_directory_uri() . '/ait-theme/@framework/admin/assets/checker';

		global $wp_version;

		$this->requirements[] = array(
			'title'        => __('WordPress version', 'ait-admin'),
			'required'     => TRUE,
			'passed'       => version_compare("4.0", $wp_version, '<='),
			'message'      => $wp_version,
			'errorMessage' => $wp_version,
			'description'  => __('This AIT Theme requires WordPress 4.0 or newer.', 'ait-admin'),
		);


		$this->requirements[] = array(
			'title'        => __('PHP Version', 'ait-admin'),
			'required'     => TRUE,
			'passed'       => version_compare(PHP_VERSION, '5.3.1', '>='),
			'message'      => PHP_VERSION,
			'errorMessage' => PHP_VERSION,
			'description'  => __('PHP version must be at least 5.3.1. But we recommend you to switch to the most recent versions of PHP 7.1 and newer. Because versions 5.3 - 7.0 reached end of life and are no longer maintained, and you are risking serious security issues and performance issues.', 'ait-admin'),
		);

		$limitPassed = (intval(ini_get('memory_limit')) == -1 or intval(ini_get('memory_limit')) >= 96);
		$mb = 96;

		$this->requirements[] = array(
			'title'        => __('Memory limit', 'ait-admin'),
			'required'     => FALSE,
			'passed'       => $limitPassed,
			'message'      => ini_get('memory_limit'),
			'errorMessage' => ini_get('memory_limit'),
			'description'  => sprintf(__('Memory limit is too low. At least <b>%sMB</b> are needed for AIT Theme to work properly.', 'ait-admin'), $mb),
		);

		$this->requirements[] = array(
			// translators: "wp-content/uploads" is exact path to folder
			'title'        => __('Read/Write permissions for wp-content/uploads folder', 'ait-admin'),
			'required'     => TRUE,
			'passed'       => $this->canWriteToUploadDir(),
			'message'      => __('Yes', 'ait-admin'),
			'errorMessage' => __('False', 'ait-admin'),
			'description'  => __('PHP is not allowed to write to "wp-content/uploads" directory', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'       => __('Function flock()', 'ait-admin'),
			'required'    => FALSE,
			'passed'      => flock(fopen(__FILE__, 'r'), LOCK_SH),
			'description' => __('Function <code>flock()</code> is not supported on this filesystem. AIT Theme requires this to process atomic file operations.', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'       => __('Function gzcompress()', 'ait-admin'),
			'required'    => FALSE,
			'passed'      => function_exists('gzcompress'),
			'description' => __('Function <code>gzcompress()</code> is not enabled. AIT Theme require it for data backuping process.', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'       => __('Function gzuncompress()', 'ait-admin'),
			'required'    => FALSE,
			'passed'      => function_exists('gzuncompress'),
			'description' => __('Function <code>gzuncompress()</code> is not enabled. AIT Theme require it for data backuping process.', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'        => 'register_globals',
			'required'     => FALSE,
			'passed'       => !$this->iniFlag('register_globals'),
			'message'      => __('Disabled', 'ait-admin'),
			'errorMessage' => __('Enabled', 'ait-admin'),
			'description'  => __('Configuration directive <code>register_globals</code> is enabled. We recommend this to be disabled for security reasons.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "Reflection" is the name of the extension
			'title'       => __('Reflection extension', 'ait-admin'),
			'required'    => TRUE,
			'passed'      => class_exists('ReflectionFunction'),
			'description' => __('Reflection extension is required.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "SPL" is the name of the extension
			'title'       => __('SPL extension', 'ait-admin'),
			'required'    => TRUE,
			'passed'      => extension_loaded('SPL'),
			'description' => __('SPL extension is required.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "PCRE" is the name of the extension
			'title'        => __('PCRE extension', 'ait-admin'),
			'required'     => TRUE,
			'passed'       => extension_loaded('pcre') && @preg_match('/pcre/u', 'pcre'),
			'message'      => __('Enabled and works properly', 'ait-admin'),
			'errorMessage' => __('Disabled or without UTF-8 support', 'ait-admin'),
			'description'  => __('PCRE extension is required and must support UTF-8.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "ICONV" is the name of the extension
			'title'        => __('ICONV extension', 'ait-admin'),
			'required'     => TRUE,
			'passed'       => extension_loaded('iconv') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
			'message'      => __('Enabled and works properly', 'ait-admin'),
			'errorMessage' => __('Disabled or does not work properly', 'ait-admin'),
			'description'  => __('ICONV extension is required and must work properly.', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'       => 'PHP tokenizer',
			'required'    => TRUE,
			'passed'      => extension_loaded('tokenizer'),
			'description' => __('PHP tokenizer is required.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'       => __('Multibyte String extension', 'ait-admin'),
			'required'    => FALSE,
			'passed'      => extension_loaded('mbstring'),
			'description' => __('Multibyte String extension is absent. Some internationalization components may not work properly.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'        => __('Multibyte String function overloading', 'ait-admin'),
			'required'     => FALSE,
			'passed'       => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
			'message'      => __('Disabled', 'ait-admin'),
			'errorMessage' => __('Enabled', 'ait-admin'),
			'description'  => __('Multibyte String function overloading is enabled. AIT Theme requires this to be disabled. If it is enabled, some string function may not work properly.', 'ait-admin'),
		);

		$this->requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'       => __('GD/ImageMagick extension', 'ait-admin'),
			'required'    => TRUE,
			'passed'      => extension_loaded('gd') || extension_loaded('imagick'),
			'description' => __('GD and ImageMagick extensions are absent. WordPress requires any of them for image resizing.', 'ait-admin'),
		);

		$this->requirements[] = array(
			'title'        => __('Max input variables', 'ait-admin'),
			'required'     => FALSE,
			'passed'       =>(intval(ini_get('max_input_vars')) >= 3000),
			'message'      => ini_get('max_input_vars'),
			'errorMessage' => ini_get('max_input_vars'),
			'description'  => __('Due to multilingual support in the theme, it is recommended that max_input_vars PHP option in php.ini config file is at least <b>3000</b>.', 'ait-admin'),
		);
	}



	public function check()
	{
		foreach(array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function){
			if(!function_exists($function)){
				trigger_error("Critical error: php function '$function' is not present.");
				switch_theme($this->previousTemplate);
				exit;
			}
		}

		foreach($this->requirements as $id => $requirement){
			$this->requirements[$id] = $requirement = (object) $requirement;
			if(isset($requirement->passed) && !$requirement->passed){
				if($requirement->required){
					$this->report->errors = TRUE;
				}else{
					$this->report->warnings = TRUE;
				}
			}
		}

		return $this->report;
	}



	public function displayReport()
	{
		$this->displayCheckerTemplate();
		exit;
	}


	private function canWriteToUploadDir()
	{
		$uploadDir = wp_upload_dir();
		return is_writable($uploadDir['basedir']);
	}



	/**
	 * Gets the boolean value of a configuration option.
	 * @param  string  configuration option name
	 * @return bool
	 */
	private function iniFlag($var)
	{
		$status = strtolower(ini_get($var));
		return $status === 'on' || $status === 'true' || $status === 'yes' || (int) $status;
	}



	protected function displayCheckerTemplate()
	{
		header('Content-Type: text/html; charset=utf-8');
		header('Cache-Control: s-maxage=0, max-age=0, must-revalidate');
		header('Expires: Mon, 23 Jan 1978 10:00:00 GMT');
	?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex">

	<title>
	<?php
		// translators: "AIT Themes" is a name
		_e('AIT Themes Requirements Checker', 'ait-admin');
	 ?>
	</title>

	<style>
		html { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; font-size: 14px; line-height: 22px; }
		body { margin: 90px 0 0; background: #F1F1F1; color: #888; }
		#wrapper {max-width: 780px; margin: 0 auto 100px auto; }
		.details-content { padding: 20px; background: #ffffff; border-radius: 2px; box-shadow: 0 20px 50px 0 rgba(0,0,0,0.1); }
		.logo { width: 84px; height: 84px; }
		#logo { margin: 0 auto; width: 84px; height: 84px; background-image: url('data:image/png;base64,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'); }
		h1 { color: #888; text-align: center; font-size: 32px; line-height: 1; font-weight: 300; }
		h2 { font-size: 2em; font-weight: normal; color: #888; margin: .7em 0; }
		p { margin: 1.2em 0; font-size: 15px; }
		hr { border: 0; height: 1px; background: #EDEDED; margin: 0 -20px 20px -20px; }

		.button { display: inline-block; background: #3BA6BD; border: none; border-radius: 2px; color: #fff; font-size: 13px; font-weight: bold; padding: 5px 14px; text-decoration: none; -webkit-transition: 0.3s all; -moz-transition: 0.3s all; transition: 0.3s all; }
		.button:hover	{ background: #4fb8ce; box-shadow: 0 4px 10px rgba(79, 184, 206, 0.35); color: #ffffff; }
		.button:active,
		.button:focus	{ color: #ffffff; }

		.center	{ text-align: center; }
		.small	{ margin-left: 20px; font-size: 14px; text-transform: uppercase; font-weight: bold; }

		.result { margin: 0; padding: 10px 15px; border-radius: 2px; }
		.result h2 { font-size: 16px; line-height: 22px; margin: 0; color: #fff; }
		.passed h2 {color: #1A7E1E; }
		.failed h2 {color: white; }
		table {padding: 0; margin: 0; border-collapse: collapse; width: 100%; }
		table td, table th {text-align: left; padding: 10px; vertical-align: top; border-style: solid; border-width: 1px 0 0; border-color: inherit; background: white none no-repeat 12px 8px; background-color: inherit; }
		table th {font-size: 105%; font-weight: 600; padding-left: 50px; }
		table tr:first-child td, table tr:first-child th { border: none; }
		.passed, .info { background-color: none; border-color: #efefef; }
		.passed th, .info th { border-color: #efefef; }
		.passed th { background-position: 12px center; background-image:  url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAErSURBVHjazJTBTcNAEEVfEAW4hJy4EiqI6cDpwGkAkgocKgDRgF1CqABTAebKKSWYCszlr/RxdnGQcmCklUee2TezszM7G4aBc8oFZ5bLoOw/71I+cy2Ag9aRFFfPP4ERqYDSYEE64GYyQ5MMeAUWBthLz4Gt+S5kTwId1mlza/YH00ugVrBVClgZ7BboEycLMICX1C1nwEb69kTYGmhSwNxq1irA/C+wMfA6dJDV8t0uZxI21didgasELJe9jgG/rJF725gBu0RmS0EPMWBoj0KQAGh/OWap70cM2ClSBtzb/5UmYwzb2Gn2qRqu9d1Z9H48DbI9jvZEga051Cp4MWqt2i6h8exSs9woq1qAPOLTawyfTnkcQi+2ym5pDd4Dbxb0SGb//sX+HgAjsVEOniajOwAAAABJRU5ErkJggg=='); }
		.info th { background-position: 12px center; background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAEpSURBVHjazJTfTcMwEMZ/rVjAK2QFkHhEQmWEZoSsEI/QjNCOACMQIfGIREZoVvAI4eULOq62EVIf+KSTLfvu8/31ZlkWroktV8bNunl4+/B3t0AnsXgx8o33x/ufhA5HQzQCk/YNsJeMwFPRQ4NnY9ACqaLz6kl9DnspDlJMhQhaIAI74FAj7ORZNGcBWICz0x2Ak2xCjnCvHEVnmIyxRzSOXBA2wGwKYNOwCpnHJtleEIYM2VrludJ6Uylk7IUzSBXCUCpKUjP/FTsbwdZNQMjkqtdDwbeIKeSYa+xZpL3W2eRwMOHbUA86G0uTEoFPTUAr5SlTrEbT0vw2KbMUgoiPLq+NvDrrvLXelWZ5Au7MB9EVWinm2qz021hPfZFOtb7c/Psf+2sAboxNpVZoBT4AAAAASUVORK5CYII='); }
		.warning {background-color: #F9F8EE; border-color: #EFEFDB; }
		div.warning {background-color: #daaf65; }
		.warning th { background-position: 12px center; background-image:  url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAFHSURBVHjazJSxkcIwEEWfmWvAV4JThbQAiUYplOAWoARcApSAU42TcwnnUKlbcAWMSZabRUhAQHB/RmOPrf379Xe1xTzPfBILPoyv3I/QuSVQy9JogdZY36biitSRQ+eOiqgHBnmvgM3tu7F+/ZIwdO4sQT2wNdZPiYR/e2LSO8LQuR1wABpj/V4d/ReYgG9jfXZvqii1ZN2/Mt9Y3wANUIfOlQ+EoXMb8SgmG6OnRqOEPCisgNFYP0RBk6wxoXKSglUpwlJVkwxpCoPEJvuwzAS1T5KVuZsyAcsnCleZhCttxyJSUUo73ONSHLgUdZxQFbJ/IDTWj0K6C52rov6HuUAHSqvsgMFY3+cau1JNvE1UXO87i+J1llDdjB/x6wScbsRCVIsyJGn7znCogKMYnkIP7FMnKJ4NWOWTxkn85u3x9a8m9nUAJvmWXBJn5OQAAAAASUVORK5CYII='); }
		.failed {background-color: #FCF7F7; border-color: #F5E8E8; }
		div.failed { background-color: #da6b6b; }
		.failed th { background-position: 12px center; background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAE7SURBVHjazFS7ccMwDH3OZQGuoBWUEZQRrBGcyjU1glS7skeIRrBGMEcIV+AIcvOYewYpXwoXwR1PFAg8PHzI3bqueKW84cXynjfxeLRnLYADl8os61ea0+kR0MhZgBYAIfsB2HMtAD43GYp8i0MPID2xuVpQW0NPw4mGaSODHsAAoAMwPmN4ILOBe0dwGxTUO9pNObgy3LNGg+hGpqf/ymgSIgXDBkCUBlwY9UzQSHZfPAPPA30LQCdgOiKRxXes3WxsHgBtU1ylAZ76VJnJwkcBE4e5Nh49u96SrUrHLArAmdG8gHWSZiBoA+BmGrnUahjp6PkdKnXNoE6CBwW0Ncwjc91oUg6caNOaMSsAozC4cWRaM1ojgB/qe2W3dZcDgA95IGqdzbcp/OVxsEy9ObtoV63s/v2LfR8AoB1X/qMqR68AAAAASUVORK5CYII='); }
		.description td {border-top: none !important; padding: 0 10px 15px 50px; color: #888; font-size: 13px; }
		.passed.description {display: none; }
	</style>

	<script type="text/javascript">
		var displayResult = function(id, passed) {
			var tr = document.getElementById('res' + id);
			tr.className = passed ? 'passed' : 'warning';
			tr.getElementsByTagName('td').item(0).innerHTML = passed ? '<?php _e('Enabled', 'ait-admin') ?>' : '<?php _e('Disabled', 'ait-admin') ?>';
			if (tr = document.getElementById('desc' + id)) {
				if (passed) {
					tr.parentNode.removeChild(tr);
				} else {
					tr.className = 'warning description';
				}
			}
		}
	</script>
</head>

<body class="wp-core-ui">
<div id="wrapper">
	<div id="logo"></div>
	<h1><?php _e('AIT Themes Requirements Checker', 'ait-admin') ?></h1>
	<p class="center"><?php _e('AIT Themes Requirements Checker checks if your server and PHP configuration meets the requirements for running this AIT Theme. It checks version of PHP, if appropriate PHP extensions have been loaded, and if PHP directives are set correctly.', 'ait-admin') ?></p>

	<h2 class="small"><?php _e('Details', 'ait-admin') ?></h2>

	<div class="details-content">

		<?php if($this->report->errors): ?>
			<div class="failed result">
				<h2><?php _e('Sorry, your server configuration does not match all requirements for this AIT Theme. <em>Your previous theme was reactivated.</em>', 'ait-admin') ?></h2>
			</div>
			<p><?php printf(__('Please fix the issues listed below and try again. You can go back to your <a href="%s" class="button button-primary" target="_blank">WordPress themes administration &raquo;</a>', 'ait-admin'), admin_url('themes.php')); ?></a></p>
			<hr>

		<?php elseif($this->report->warnings): ?>
			<div class="warning result">
				<h2><?php _e('Your server configuration does not fully match all requirements for this AIT Theme.', 'ait-admin') ?></h2>
			</div>
			<p><?php printf(__('We highly recommend to <a href="%s" class="button button-primary">reactivate your previous theme</a> until all of the listed issues are resolved. <a href="%s" class="button button-secondary">Continue</a> only if you know what you are doing.</p>', 'ait-admin'), admin_url('themes.php?ait-theme-revert'), admin_url('themes.php?ait-theme-continue')); ?></p>
			<hr>
		<?php endif ?>

		<table>
		<?php foreach ($this->requirements as $index => $requirement):?>
		<?php $class = isset($requirement->passed) ? ($requirement->passed ? 'passed' : ($requirement->required ? 'failed' : 'warning')) : 'info' ?>
		<tr index="res<?php echo($index) ?>" class="<?php echo($class) ?>">
			<th><?php echo htmlSpecialChars($requirement->title) ?></th>

			<?php if (empty($requirement->passed) && isset($requirement->errorMessage)): ?>

				<td><?php echo htmlSpecialChars($requirement->errorMessage) ?></td>

			<?php elseif (isset($requirement->message)): ?>

				<td><?php echo htmlSpecialChars($requirement->message) ?></td>

			<?php elseif (isset($requirement->passed)): ?>

				<td><?php echo ($requirement->passed ? __('Enabled', 'ait-admin') : __('Disabled', 'ait-admin')) ?></td>

			<?php else: ?>

				<td><?php _e('Not tested', 'ait-admin') ?></td>

			<?php endif ?>
		</tr>

		<?php if (isset($requirement->description)): ?>
		<tr id="desc<?php echo($index) ?>" class="<?php echo ( $class) ?> description">
			<td colspan="2"><?php echo( htmlSpecialChars(strip_tags($requirement->description))) ?></td>
		</tr>
		<?php endif ?>

		<?php endforeach ?>
		</table>

	</div>

</div>
</body>
</html>
<?php
	}
}
