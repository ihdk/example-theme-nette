<?php


/**
 * Registers and enqueues scripts and styles for frontend
 */
class AitAssetsManager
{

	protected $builtinFrontendAssets = array();

	protected $assetsList = array();

	protected $ajaxActions = array();

	protected $params = array();

	protected $inlineStyleCallbacks = array();

	protected $lastEnqueuedCssHandler;
	protected $lastEnqueuedLessHandler;



	public function __construct($builtinFrontendAssets, $assetsFromFunctionsPhpFile)
	{
		$this->builtinFrontendAssets = $builtinFrontendAssets;
		$this->assetsList[] = array('assets' => $assetsFromFunctionsPhpFile, 'params' => array());
	}



	public function addAssets($assets, $params = array())
	{
		$this->assetsList[] = array('assets' => $assets, 'params' => $params);
	}



	public function setAjaxActions($callbacks)
	{
		$this->ajaxActions = $callbacks;
	}



	public function enqueueFrontendAssets()
	{
		$builtinAssets = apply_filters('ait-theme-builtin-assets', $this->builtinFrontendAssets);
		$this->enqueueCss($builtinAssets['css']);
		$this->enqueueJs($builtinAssets['js']);

		if(is_singular() and comments_open() and get_option('thread_comments')){
			wp_enqueue_script('comment-reply');
		}

		$this->addGoogleFontsCss();

		$assetsList = apply_filters('ait-theme-assets', $this->assetsList);

		foreach($assetsList as $item){
			if(isset($item['assets']['css'])){
				$this->enqueueCss($item['assets']['css'], $item['params']);
			}

			if(isset($item['assets']['js'])){
				$this->enqueueJs($item['assets']['js'], $item['params']);
			}
		}

		$this->enqueueLessFiles();
		$this->addWpInlineStyles();

		if(file_exists(aitPaths()->dir->theme . '/custom.css')){
			wp_enqueue_style('ait-theme-custom-style', aitPaths()->url->theme . '/custom.css');
		}
	}



	public function enqueueLessFiles()
	{
		$results = $this->compileLessFiles();

		foreach($results as $handler => $result){
			$this->embedOrEnqueueCssGeneratedFromLess($handler, $result);
		}
	}



	public function compileLessFiles()
	{
		$lessCompiler = new AitLessCompiler(aitPaths()->dir->cache, aitPaths()->url->cache);

		$lessFiles = apply_filters('ait-less-files', $this->getCoreLessFiles());

		$results = array();

		foreach($lessFiles as $handler => $args){
			if($args['inputFile']){
				$results[$handler] = $lessCompiler->compileFile($args['inputFile'], $args['params']);
			}
		}

		return $results;
	}



	public function getCoreLessFiles()
	{
		return array(
			'ait-theme-main-base-style' => array(
				'inputFile' => aitPath('css', '/base.less'),
				'params'    => array(),
			),
			'ait-theme-main-style' => array(
				'inputFile' => aitPath('css', '/style.less'),
				'params'    => array(),
			),
			'ait-theme-layout-style' => array(
				'inputFile' => aitPath('css', '/layout.less'),
				'params'    => array('oid' => aitOptions()->getOid()),
			),
			'ait-preloading-effects' => array(
				'inputFile' => aitPath('css', '/preloading.less'),
				'params'    => array(),
			),
			'ait-typography-style' => array(
				'inputFile' => aitPath('css', '/typography.less'),
				'params'    => array('lang' => AitLangs::getCurrentLocale()),
			),
		);
	}



	public function getInlineCss()
	{
		return apply_filters('ait-inline-css', array(
			array(
				'appendTo' => 'ait-theme-main-style',
				'css' => array($this, 'getThemeMainInlineStylesContent'),
			),
		));
	}



	public function getCustomCss()
	{
		if(apply_filters('ait-enable-less-in-custom-css-field', false)){
			$css = function(){
				$lessCompiler = new AitLessCompiler(aitPaths()->dir->cache, aitPaths()->url->cache);
				return $lessCompiler->compileString(aitOptions()->get('theme')->customCss->css);
			};
		}else{
			$css = function(){
				return aitOptions()->get('theme')->customCss->css;
			};
		}

		return apply_filters('ait-custom-css', array(
			array(
				'appendTo' => '', // do not append to specific handler, it will be appended to the last enqueued less file
				'css' => $css,
			),
		));
	}



	protected function embedOrEnqueueCssGeneratedFromLess($handler, $output)
	{
		if($output['isEmpty']) return;

		if($output['error']){
			wp_add_inline_style($this->lastEnqueuedCssHandler, $output['embedCss']);
			if(AIT_DEV and !empty($output['errorMsg'])){
				error_log($output['errorMsg']);
			}
		}else{
			$this->lastEnqueuedLessHandler = $handler;
			wp_enqueue_style($handler, $output['url'], array(), $output['version']);
		}
	}



	protected function addWpInlineStyles()
	{
		foreach($this->getInlineCss() as $inline){
			$css = call_user_func($inline['css']);
			if($inline['appendTo']){
				wp_add_inline_style($inline['appendTo'], $css);
			}else{
				wp_add_inline_style($this->lastEnqueuedLessHandler, $css);
			}
		}

		foreach($this->getCustomCss() as $inline){

			$output = call_user_func($inline['css']);
			$css = is_array($output) ? $output['css'] : $output;

			if((is_array($output) and $output['isEmpty']) or empty($css)) continue;

			if($inline['appendTo']){
				wp_add_inline_style($inline['appendTo'], $css);
			}else{
				wp_add_inline_style($this->lastEnqueuedLessHandler, $css);
			}
		}
	}




	public function enqueueAdminAssets()
	{
		foreach($this->assetsList as $item){

			if(isset($item['assets']['admin-css'])){
				$this->enqueueCss($item['assets']['admin-css'], $item['params']);
			}

			if(isset($item['assets']['admin-js'])){
				$this->enqueueJs($item['assets']['admin-js'], $item['params']);
			}
		}
	}



	/**
	 * Enqueues CSS files
	 */
	public function enqueueCss($styles, $params = array())
	{
		if(empty($styles) or !is_array($styles)) return; // do nothing

		$lastHandler = '';
		foreach($styles as $handler => $css){
			$lastHandler = $handler;

			if($css === true){ // wp builtin css

				wp_enqueue_style($handler);

			}elseif(is_array($css)){

				if($css['file'] === false) continue; // file not found

				if(AitUtils::isExtUrl($css['file']) or AitUtils::isAbsUrl($css['file'])){
					$url = $css['file'];
				}else{
					if(isset($params['paths']->url->css))
						$url = $params['paths']->url->css . $css['file'];
					else
						$url = aitUrl('css', $css['file']);
				}

				wp_register_style(
					$handler,
					$url,
					isset($css['deps']) ? $css['deps'] : array(),
					isset($css['ver']) ? $css['ver'] : false,
					isset($css['media']) ? $css['media'] : 'all'
				);

				if(isset($css['enqueue-only-if'])){
					$ifResult = false;
					// example: 'enqueue-only-if' => function() { return 1 != 0; },
					if(is_callable($css['enqueue-only-if'])){
						$ifResult = call_user_func($css['enqueue-only-if']);
					// example: 'enqueue-only-if' => 'is_home'
					}if(is_string($css['enqueue-only-if']) and is_callable($css['enqueue-only-if'])){
						$ifResult = call_user_func($css['enqueue-only-if']);
					// example: 'enqueue-only-if' => array('is_singular', array('post'))
					}elseif(is_array($css['enqueue-only-if']) and is_string($css['enqueue-only-if'][0]) and is_callable($css['enqueue-only-if'][0]) and is_array($css['enqueue-only-if'][1])){
						$ifResult = call_user_func_array($css['enqueue-only-if'][0], $css['enqueue-only-if'][1]);
					}

					if($ifResult){
						wp_enqueue_style($handler);
					}
				}elseif(!isset($css['enqueue']) or (isset($css['enqueue']) and $css['enqueue'])){
					wp_enqueue_style($handler);
				}
			}
		}

		$this->lastEnqueuedCssHandler = $lastHandler;
	}



	/**
	 * Enqueue JS files
	 */
	public function enqueueJs($scripts, $params = array())
	{
		if(empty($scripts) or !is_array($scripts)) return; // do nothing

		foreach($scripts as $handler => $js){

			if(is_bool($js) and $js === true){ // wp builtin js

				wp_enqueue_script($handler);

			}elseif(is_array($js)){

				if($js['file'] === false) continue; // file not found

				$filename = $js['file'];

				if(isset($js['lang'])){
					$filename = str_replace('{lang}', AitLangs::getCurrentLanguageCode(), $filename);
					$filename = str_replace('{gmaps-lang}', AitLangs::getGmapsLang(), $filename);
				}

				if(isset($js['api-key'])){
					$t = aitOptions()->getOptionsByType('theme');
					$gmapsApiKey = empty($t['google']['mapsApiKey']) ? "" : $t['google']['mapsApiKey'];
					$filename = str_replace('{gmaps-api-key}', $gmapsApiKey, $filename);
				}

				if(AitUtils::isExtUrl($filename) or AitUtils::isAbsUrl($filename)){
					$url = $filename;
				}else{
					if(isset($params['paths']->url->js))
						$url = $params['paths']->url->js . $filename;
					else
						$url = aitUrl('js', $filename);
				}

				wp_register_script(
					$handler,
					$url,
					isset($js['deps']) ? $js['deps'] : array(),
					isset($js['ver']) ? $js['ver'] : false,
					isset($js['in-footer']) ? $js['in-footer'] : true // our default is in footer
				);


				if(isset($js['enqueue-only-if'])){
					$ifResult = false;
					// example: 'enqueue-only-if' => function() { return 1 != 0; },
					if(is_callable($js['enqueue-only-if'])){
						$ifResult = call_user_func($js['enqueue-only-if']);
					// example: 'enqueue-only-if' => 'is_home'
					}if(is_string($js['enqueue-only-if']) and is_callable($js['enqueue-only-if'])){
						$ifResult = call_user_func($js['enqueue-only-if']);
					// example: 'enqueue-only-if' => array('is_singular', array('post'))
					}elseif(is_array($js['enqueue-only-if']) and is_string($js['enqueue-only-if'][0]) and is_callable($js['enqueue-only-if'][0]) and is_array($js['enqueue-only-if'][1])){
						$ifResult = call_user_func_array($js['enqueue-only-if'][0], $js['enqueue-only-if'][1]);
					}

					if($ifResult){
						wp_enqueue_script($handler);
					}
				}elseif(!isset($js['enqueue']) or (isset($js['enqueue']) and $js['enqueue'] == true)){
					wp_enqueue_script($handler);
				}

				if(isset($js['localize'])){
					if(isset($js['localize']['object-var'])){
						$var = $js['localize']['object-var'];
						unset($js['localize']['object-var']);
					}else{
						$var = AitUtils::dash2class($handler);
					}

					wp_localize_script($handler, $var, $js['localize']);
				}
			}
		}
	}



	public function initGlobalFrontendJsVariables()
	{
		$settings = array(
			'home' => array(
				'url' => home_url(),
			),
			'ajax' => array(
				'url'     => admin_url('admin-ajax.php'),
				'actions' => array(),
			),
			'paths' => array(
				'theme' => aitPaths()->url->theme,
				'css'   => aitPaths()->url->css,
				'js'    => aitPaths()->url->js,
				'img'   => aitPaths()->url->img,
			),
			'l10n' => array(
				'datetimes' => array(
					'dateFormat'  => AitUtils::phpDate2jsDate(get_option('date_format')),
					// 'timeFormat'  => get_option('time_format'),
					'startOfWeek' => get_option('start_of_week'),
				),
			),
		);

		$settings['ajax']['actions'] = $this->ajaxActions;

		?>
		<script type="text/javascript">
			var AitSettings = <?php echo json_encode( $settings ); ?>
		</script>
		<?php
	}



	public function addInlineStyleCallback($callback)
	{
		$this->inlineStyleCallbacks[] = $callback;
	}



	public function getThemeMainInlineStylesContent()
	{
		$css = '';
		$files = array();

		$oid = aitOptions()->getOid();
		$cacheKey = 'inline-styles-' . $oid;

		if($css = AitCache::load($cacheKey)){
			return $css;
		}else{

			foreach($this->inlineStyleCallbacks as $cb){
				$r = call_user_func($cb);
				$css .= $r['css'];
				$files = array_merge($files, $r['files']);
			}

			$files = array_unique($files);

			$tag = $oid == '' ? 'global' : $oid;
			AitCache::save($cacheKey, $css, array('files' => $files, 'tags' => array($tag)));
		}

		return $css;
	}



	protected function addGoogleFontsCss()
	{
		$asset = array();

		$themeOptions = aitOptions()->getOptionsByType('theme');

		if(!isset($themeOptions['typography'])) return;

		foreach($themeOptions['typography'] as $optionKey => $optionValue){
			$value = AitLangs::getCurrentLocaleText($optionValue, '');

			if(!$value) continue;

			list($fontType, $fontFamily) = array_pad(explode('@', $value), 2, null);

			if(!$fontFamily || $fontType != 'google') continue;

			$font = AitGoogleFonts::getByFontFamily($fontFamily);

			if($font !== false){
				$urlArgs = array(
					'family' => str_replace("'", "", $fontFamily) . ':' . implode(',', $font->variants),
					'subset' => implode(',', $font->subsets)
				);

				$fontUrl = add_query_arg($urlArgs, "//fonts.googleapis.com/css");
				$handler = "google-font-".$optionKey; //different handler for asset because we may inlude multiple goolge fonts for multiple sections

				$asset['css'][$handler]['file'] = esc_url_raw($fontUrl);
				$asset['css'][$handler]['ver'] = null;
				$this->assetsList[] = array('assets' => $asset, 'params' => array());
			}
		}
	}

}
