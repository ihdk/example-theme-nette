<?php


// ===============================================
// AIT WordPress Theme Framework Helpers
// -----------------------------------------------

/**
 * Get IMG HTML tag with resized image
 * @param  string $url  URL to image
 * @param  array $args  See https://github.com/humanmade/WPThumb/wiki#arguments
 * @return string       URL to resized image
 */
function aitGetResizedImgTag($url, $args)
{
	$src = aitResizeImage($url, $args);

	$newArgs = wp_parse_args($args);

	$width = $height = $class = $alt = '';

	if(isset($newArgs['width']))
		$width = 'width="' . $newArgs['width'] . '"';

	if(isset($newArgs['height']))
		$height = 'height="' . $newArgs['height'] . '"';

	if(isset($newArgs['class']))
		$class = 'class="' . $newArgs['class'] . '"';

	if(isset($newArgs['alt']))
		$alt = $newArgs['alt'];

	$img = '<img src="%s" %s %s %s alt="%s">';

	return sprintf($img, $src, $class, $width, $height, $alt);
}



/**
 * Resizing image with WPThumb
 * @param  string $url  URL to image
 * @param  array $args  See https://github.com/humanmade/WPThumb/wiki#arguments
 * @return string       URL to resized image
 */
function aitResizeImage($url, $args)
{
	if(!class_exists('WP_Thumb', false))
		require_once aitPaths()->dir->libs . "/wpthumb/ait-wpthumb.php";
	return wpthumb($url, $args);
}



/**
 * Generates HTML5 data-* attribute
 * @param  string $name   Name of attribute, data-<name>
 * @param  mixed $params  Value of data attribute, if is scalar then its directly passed, otherwise json_encoded
 * @param  string $prefix Prefix for name, default 'ait-'
 * @return return         HTML data attribute string
 */
function aitDataAttr($name, $params, $prefix = 'ait-')
{
	$data = is_scalar($params) ? $params : json_encode($params);
	return " data-{$prefix}{$name}='{$data}'";
}



/**
 * Checks if viewed page is static page set as homepage
 * @return bool
 */
function aitIsStaticHomepage()
{
	static $__aitIsStaticHomepage;

	if(is_null($__aitIsStaticHomepage)){
		$__aitIsStaticHomepage = (get_option('show_on_front') == 'page' and get_option('page_on_front') and is_page(get_option('page_on_front')));
		return $__aitIsStaticHomepage;
	}else{
		return $__aitIsStaticHomepage;
	}
}



function aitManager($manager)
{
	return AitTheme::getManager($manager);
}



/**
 * Shortcut function for accessing settings
 * @return AitThemeSettings
 */
function aitConfig()
{
	return AitTheme::getConfig();
}



/**
 * Shortcut function for accessing configuration
 * @param  $type theme, layout, elements
 * @return AitOptions
 */
function aitOptions($type = null)
{
	if($type) return AitTheme::getOptions()->get($type);
	return AitTheme::getOptions();
}



/**
 * Checks if given plugin is active
 * This is simpler way than is_plugin_active()
 *
 * @param  string  $plugin Plugin name
 * @return boolean
 */
function aitIsPluginActive($plugin)
{
	switch($plugin){
		// ait plugins
		case 'shortcodes':
			return (defined('AIT_SHORTCODES_ENABLED') and AIT_SHORTCODES_ENABLED);
		case 'toolkit':
			return (defined('AIT_TOOLKIT_ENABLED') and AIT_TOOLKIT_ENABLED);
		case 'languages':
			return (defined('AIT_LANGUAGES_ENABLED') and AIT_LANGUAGES_ENABLED);
		case 'revslider':
			return (defined('REVSLIDER_TEXTDOMAIN') or class_exists('RevSliderGlobals'));
		case 'woocommerce':
			return defined('WOOCOMMERCE_VERSION');
		case 'jetpack':
			return defined('JETPACK__VERSION');
		default:
			return false;
	}
}



function aitIsGutenbergActive()
{
	global $wp_version;
	return ((defined('GUTENBERG_VERSION') or version_compare($wp_version, '5.0-rc', '>=')) and !function_exists('classic_editor_replace'));
}



function aitHasBlocks($content)
{
	return (function_exists('has_blocks') and has_blocks($content));
}



/**
 * Gets specific option from given path
 * @param  string $path Dot notation of nested array, e.g.: theme.general.layoutType
 * @param  string $oid  OID
 * @return mixed
 */
function aitGetOption($path, $oid = '')
{
	static $__aitOption;

	if(is_null($__aitOption) or !isset($__aitOption[$path . $oid])){
		 $r = AitUtils::arrayDotGet(AitTheme::getOptions()->getOptions($oid), $path);
		 $__aitOption[$path . $oid] = is_scalar($r) ? $r : json_decode(json_encode($r));
		return $__aitOption[$path . $oid];
	}else{
		return $__aitOption[$path . $oid];
	}
}



function aitDropdownPosts($args = '')
{
	$defaults = array(
		'selected'              => 0,
		'echo'                  => true,
		'name'                  => 'post_id',
		'id'                    => '',
		'class'                 => '',
		'show_option_none'      => '',
		'show_option_no_change' => '',
		'option_none_value'     => '',
		'oid_prefix'            => '',
	);

	$r = wp_parse_args($args, $defaults);

	$postsArgs = array_diff_key($r, $defaults); // strip keys for dropdown formatting

	$posts = get_posts($postsArgs);

	$r = (object) $r;

	$output = '';

	$optionTag = "\t<option value='%s' %s>%s</option>";

	if(!empty($posts)){
		$output = sprintf("<select name='%s' id='%s' class='%s'>\n", esc_attr($r->name), esc_attr($r->id), esc_attr($r->class));

		if($r->show_option_no_change)
			$output .= sprintf($optionTag, -1, selected($r->selected, -1, false), $r->show_option_no_change);

		if($r->show_option_none)
			$output .= sprintf($optionTag,  esc_attr($r->option_none_value), selected($r->selected, $r->option_none_value, false), $r->show_option_none);

		foreach($posts as $post){
			$output .= sprintf($optionTag, $r->oid_prefix . $post->ID, selected($r->selected, $r->oid_prefix . $post->ID, false), esc_html($post->post_title));
		}

		$output .= "</select>\n";
	}

	$output = apply_filters('ait-dropdown-posts', $output);

	if($r->echo)
		echo $output;

	return $output;
}



/**
 * Enable or disable dev mode
 * @return void
 */
function aitEnableDisableDevMode()
{
	if(!defined('AIT_SERVER')){
		if(!defined('AIT_DEV')){
			define('AIT_DEV', false);
		}

		if(!defined('AIT_DISABLE_CACHE')){
			define('AIT_DISABLE_CACHE', false);
		}

		return;
	}

	$defaults = array('administrator' => array('devMode' => false, 'devIp' => ''));
	$devOpts = get_option('_ait_' . AIT_CURRENT_THEME . '_theme_opts', $defaults);
	if(empty($devOpts)) $devOpts = $defaults;

	$ip = (!empty($devOpts['administrator']['devIp']) and $devOpts['administrator']['devIp'] == $_SERVER['REMOTE_ADDR']);

	if($devOpts['administrator']['devMode'] or $ip){
		if(!defined('AIT_DEV')){
			define('AIT_DEV', true);
		}

		if(!defined('AIT_DISABLE_CACHE')){
			define('AIT_DISABLE_CACHE', true);
		}

	}else{
		if(!defined('AIT_DEV')){
			define('AIT_DEV', false);
		}

		if(!defined('AIT_DISABLE_CACHE')){
			define('AIT_DISABLE_CACHE', false);
		}
	}
}



function aitRenderDeleteCachesThemeOptionControl()
{
	?>

		<div class="ait-opt-label">
			<div class="ait-label-wrapper">
				<span class="ait-label"><?php _e('Delete caches', 'ait-admin') ?></span>
			</div>
		</div>

		<div class="ait-opt">
			<div class="ait-opt-wrapper" style="background:none; box-shadow:none;">
				<div class="ait-button-group">
					<button type="button" id="ait-delete-cache-theme-btn" class="ait-opt-btn">
						<?php _e('Empty theme cache', 'ait-admin') ?>
						<span class="status"></span>
					</button>
					<button type="button" id="ait-delete-cache-image-btn" class="ait-opt-btn">
						<?php _e('Empty image (WPThumb) cache', 'ait-admin') ?>
						<span class="status"></span>
					</button>
				</div>
			</div>
		</div>
		<script>
			jQuery(function($){
				$('#ait-delete-cache-theme-btn').on('click', function(){
					$this = $(this);
					$this.addClass('working');
					$.post(ajaxurl, {'action': 'emptyThemeCacheDir', '_ajax_nonce': '<?php echo AitUtils::nonce("delete-cache-theme") ?>'}, function(response){
						$this.removeClass('working');
						$this.addClass('done');
						setTimeout(function() {
							$this.removeClass('done');
						}, 1000);
					});
					return false;
				});

				$('#ait-delete-cache-image-btn').on('click', function(){
					$this = $(this);
					$this.addClass('working');
					$.post(ajaxurl, {'action': 'emptyWPThumbCacheDir', '_ajax_nonce': '<?php echo AitUtils::nonce("delete-cache-image") ?>'}, function(response){
						$this.removeClass('working');
						$this.addClass('done');
						setTimeout(function() {
							$this.removeClass('done');
						}, 1000);
					});
					return false;
				});
			});
		</script>
	<?php
}



function aitExtractVideoIdFromVideoUrl($videoUrl)
{
	$videoId = '';
	if(preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $videoUrl, $match)) {
		if(isset($match[1])){
			$videoId = $match[1];
		}
	}elseif(preg_match("/https?:\/\/(?:www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/", $videoUrl, $match)){
		if(isset($match[3])){
			$videoId = $match[3];
		}
	}

	return $videoId;
}



function aitIsOurServer()
{
	return defined("AIT_SERVER");
}



if(!function_exists('d')){
	function d()
	{
		foreach(func_get_args() as $arg){
			echo "<xmp style='outline:1px solid red;background:ivory;position:relative;z-index:9999;clear:both;'>";
			var_dump($arg);
			echo "</xmp>";
		}
	}
}



if(!function_exists('dd')){
	function dd()
	{
		foreach(func_get_args() as $arg){
			echo "<xmp style='outline:1px solid red;background:ivory;position:relative;z-index:9999;clear:both;'>";
			var_dump($arg);
			echo "</xmp>";
		}
		die();
	}
}



if(!function_exists('p')){
	function p()
	{
		foreach(func_get_args() as $arg){
			echo "<xmp style='outline:1px solid red;background:ivory;position:relative;z-index:9999;clear:both;'>";
			print_r($arg);
			echo "</xmp>";
		}
	}
}



if(!function_exists('pd')){
	function pd()
	{
		foreach(func_get_args() as $arg){
			echo "<xmp style='outline:1px solid red;background:ivory;position:relative;z-index:9999;clear:both;'>";
			print_r($arg);
			echo "</xmp>";
		}
		die();
	}
}



// ===============================================
// PHP compatibility
// -----------------------------------------------

 if(!function_exists('array_replace_recursive')){
	/**
	 * Replaces elements from passed arrays into the first array recursively
	 * for PHP 5 <= 5.3.0
	 * @return array|null
	 */
	function array_replace_recursive()
	{
		$arrays = func_get_args();
		$original = array_shift($arrays);

		foreach($arrays as $array){
			foreach($array as $key => $value){
				if(is_array($value) and isset($original[$key])){
					$original[$key] = array_replace_recursive($original[$key], $array[$key]);
				}else{
					$original[$key] = $value;
				}
			}
		}
		return $original;
	}
}

// ===============================================
// ThemeCheck Compatibility
// ===============================================
add_action( 'themecheck_checks_loaded', 'ait_disable_checks' );
function ait_disable_checks(){
	global $themechecks;

	$checks_to_disable = array(
		'IncludeCheck',
		'I18NCheck',
		'AdminMenu',
		'Bad_Checks',
		'MalwareCheck',
		'Theme_Support',
		'CustomCheck',
		'EditorStyleCheck',
		'IframeCheck',
	);

	foreach ( $themechecks as $keyindex => $check ) {
		if ( $check instanceof themecheck ) {
			$check_class = get_class( $check );
			if ( in_array( $check_class, $checks_to_disable ) ) {
				unset( $themechecks[$keyindex] );
			}
		}
	}
}
// ===============================================
// Get provider of maps
// ===============================================
function getMapProvider(){
	$theme = sanitize_key( get_stylesheet() );
	$themeOptionKey = '_ait_'.$theme.'_theme_opts';	// better way to do this
	wp_cache_delete('alloptions', 'options'); // will force to load new options from DB in next get_option call
	$themeOptions = get_option($themeOptionKey, array());
	if(AIT_THEME_CODENAME == 'directory2'){
		if (empty($themeOptions['google']['mapsApiKey'])) {
			return "openstreetmap";
		}else{
			return $themeOptions['maps']['provider'];
		}
	}else{
		return "google";
	}
}