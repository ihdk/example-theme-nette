<?php


/**
 * Abstract base class for creating elements
 */
class AitElement extends NObject
{
	const UNDEFINED_INDEX = 1000;

	/**
	 * Internal ID (name) of Element
	 * @var string
	 */
	protected $internalId;

	/**
	 * Element ID from config, same as folder name
	 * @var string
	 */
	protected $id;

	/**
	 * Element's title
	 * @var string
	 */
	protected $title;

	/**
	 * Element's icon
	 * @var string
	 */
	protected $icon;

	/**
	 * Element's color
	 * @var string
	 */
	protected $color;

	/**
	 * Element's instance number
	 * @var string
	 */
	protected $instanceNumber = 0;

	/**
	 * Element's config
	 * @var array
	 */
	protected $config;

	/**
	 * Configuration
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * Default options of element from config
	 * @var array
	 */
	protected $optionsDefaults = array();

	/**
	 * Options of element
	 * @var array
	 */
	protected $options = array();

	/** @var  AitOptionsControlsGroup */
	protected $optionsControlsGroup;

	/**
	 * Options Id (Object Id)
	 * @var string
	 */
	protected $oid = '';

	/**
	 * List of custom post types that element require
	 * @var array
	 */
	protected $cpts = array();

	/**
	 * Absolute path to element's template
	 * @var string
	 */
	protected $template;

	/**
	 * Template filename
	 * @var string
	 */
	protected $templateName;

	/**
	 * Absolute path to element's LESS file
	 * @var string
	 */
	protected $style;

	/**
	 * Style filename
	 * @var string
	 */
	protected $styleLessName;

	protected $assets = array(
		'js' => array(),
		'css' => array(),
		'admin-js' => array(),
		'admin-css' => array(),
	);

	/**
	 * Paths
	 * @var stdClass
	 */
	protected $paths;

	/**
	 * Generated CSS from LESS
	 * @var array
	 */
	protected $generatedCss = array();

	protected $lessCompiler;

	/**
	 * Array of element's options converted to stdClass for
	 * more simple usage
	 * @var stdClass
	 */
	protected $optionsObject;

	/**
	 * Flag if element is between sidebars
	 * @var boolean
	 */
	protected $isBetweenSidebars = false;

	/**
	 * Element is used
	 * @var bool
	 */
	protected $used = false;


	/**
	 * Constructor
	 * @param string $elementId
	 * @param array $fullConfig
	 * @param array $optionsDefaults
	 */
	public function __construct($elementId, $fullConfig, $optionsDefaults)
	{
		$this->id = $elementId;
		$this->config = $fullConfig;
		$this->optionsDefaults = $optionsDefaults;

		if(isset($fullConfig['@title']))
			$this->title = $fullConfig['@title'];
		else
			$this->title = $this->id;

		$this->icon = $fullConfig['@icon'];

		$this->color = $fullConfig['@color'];

		if (isset($fullConfig['@used'])) {
			$this->used = $fullConfig['@used'];
		}

		$this->internalId = "elm-" . $this->id;

		$this->init();
		$this->configure();
	}



	/**
	 * Initializer, something like 2nd constructor, but simpler
	 */
	public function init()
	{
		// needs to be overriden
	}



	/**
	 * Configures element
	 * @param  array $params Default element config from constructor
	 * @param  array $config Element's config
	 */
	protected function configure()
	{
		$c = (object) ($this->configuration = array_replace_recursive($this->configuration, $this->config['@configuration']));

		if((isset($c->cpt) and !empty($c->cpt))){
			foreach($c->cpt as $cpt){
				$this->cpts[$cpt] = false;
			}
		}else{
			$this->cpts = array();
		}

		if(!isset($c->template) or (isset($c->template) and $c->template == 'default'))
			$this->templateName = "{$this->id}.latte";

		if(isset($c->template) and $c->template != 'default')
			$this->templateName = $c->template . ".latte";

		if(!isset($c->style) or (isset($c->style) and $c->style == 'default'))
			$this->styleLessName = "style.less";

		if(isset($c->style) and $c->style != 'default')
			$this->styleLessName = $c->style . ".less";


		if(isset($c->assets))
			$this->assets = array_merge($this->assets, $c->assets);

		$base = $this->getBaseStyleUrl();

		if($base){
			$this->assets['css'][$this->getInternalId() . '-base'] = array(
				'file' => $base,
				'deps' => array_keys($this->assets['css']),
			);
		}
	}



	/**
	 * Gets specific options
	 * @param  string $key Name of option
	 * @return mixed      Value of option
	 */
	public function option($key)
	{
		if(isset($this->options[$key])){
			return $this->options[$key];
		}

		return false;
	}



	/**
	 * Returns absolute path to common template
	 * @param  string $part Name of the template, /@common/<$part>.latte
	 * @return string
	 */
	public function common($part)
	{
		return aitPath('elements', "/@common/{$part}.latte");
	}



	protected function addClassesFromSelectInputTypes(&$classes)
	{
		foreach($this->config['@options']  as $i => $sections){
			unset($sections['@section']);
			foreach($sections as $k => $option){
				if(
					$option['type'] === 'select' and
					isset($option['add-element-class']) and
					$option['add-element-class'] === true and
					isset($this->options[$k]) and
					!is_array($this->options[$k]) // is not multiselect
				){
					$classes[] = 'elm-selected-' . $this->options[$k];
				}
			}
		}
	}



	// =================================================
	// Getters
	// -------------------------------------------------

	public function getConfig($key = '')
	{
		if(empty($key))
			return $this->config;

		if(isset($this->config[$key])){
			return $this->config[$key];
		}

		return false;
	}



	public function getConfigOption($key)
	{
		foreach($this->config['@options']  as $i => $sections){
			unset($sections['@section']);
			foreach($sections as $k => $option){
				if($k === $key){
					return $option;
				}
			}
		}
		return null;
	}



	public function getOptionObjectFromConfig($optionKey)
	{
		return $this->getConfigOption($optionKey);
	}



	public function getContentPreviewDefaultOptions()
	{
		$layout   = $this->option('layout');
		$rows     = $this->option($layout.'Rows');
		$columns  = $this->option($layout.'Columns');
		$carousel = $this->option($layout.'EnableCarousel');

		return array(
			'layout' => $layout,
			'columns' => (!empty($columns) ? $columns : 1),
			'carousel' => $carousel,
			'rows' => (!empty($rows) ? ($carousel ? $rows : 1) : 1),
			'content' => true,
			'script' => true
		);
	}



	public function getContentPreviewOptions()
	{
		// should be overriden
		return array();
	}



	public function getContentPreview($elementData = array())
	{
		$defaultContentPreviewOptions = $this->getContentPreviewDefaultOptions();
		$contentPreviewOptions = $this->getContentPreviewOptions();
		$options = array_merge($defaultContentPreviewOptions, $contentPreviewOptions);

		$elementData['options'] = $options;

		/* Check if element is item organizer or uses default placeholders */
		if ($options['layout'] === false || $options['columns'] === false) return null;

		ob_start();
		?>

		<div class="ait-element-placeholder-wrap layout-<?php echo $options['layout'] ?>" data-layout="<?php echo $options['layout'] ?>">
			<?php for ($r = 0; $r < $options['rows']; $r++): ?>
				<div class="ait-element-placeholder-row">
					<?php for ($c = 0; $c < $options['columns']; $c++): ?>
						<div class="ait-element-placeholder<?php $options['content'] ? '' : ' no-content' ?>">
							<div class="ait-element-placeholder-image"><i class="fa <?php echo $this->getIcon(); ?>"></i></div>
							<?php if ($options['content']): ?>
								<div class="ait-element-placeholder-content"></div>
							<?php endif; ?>
						</div>
					<?php endfor; ?>
				</div>
			<?php endfor; ?>
		</div>

		<?php
		$content = ob_get_clean();

		if ($options['script']):

			$elementName = aitPath('elements', "/{$this->id}/admin/element-preview.js") ? $this->id : '@common';

			ob_start();
			?>

			<script type=<?php echo ($this->isUsed() ? "text/javascript" : "text/template")?>>
				(function(){
					var elementData = <?php echo json_encode($elementData); ?>;
					<?php echo file_get_contents(aitPath('elements', "/{$elementName}/admin/element-preview.js")); ?>
				})();
			</script>

			<?php
			$script = ob_get_clean();
		else:
			$script = false;
		endif;

		$preview = array(
			'content' => $content,
			'script' => $script
		);

		return $preview;

	}



	public function getTitle(){
		return $this->title;
	}



	public function getIcon(){
		return $this->icon;
	}



	public function getColor(){
		return $this->color;
	}



	public function getId(){
		return $this->id;
	}



	public function getHtmlId(){
		return $this->internalId . '-' . $this->instanceNumber;
	}



	public function getHtmlClass(){
		return $this->internalId;
	}



	public function getHtmlClasses($asString = true)
	{
		$classes = array();

		$classes[] = 'elm-main';
		$classes[] = $this->internalId . '-main';

		if($this->hasOption('@bg')){
			$bg = $this->option('@bg');
			if(isset($bg['color']) and !empty($bg['color'])){
				$classes[] = 'elm-has-bg';
			}
		}

		if($this->hasOption('customClass')){
			$classes[] =  $this->option('customClass');
		}

		if($this->hasOption('contentSize')){
			$confOption = $this->getConfigOption('contentSize');
			if(isset($confOption['target']) && !empty($confOption['target'])){
				if($confOption['target'] == $this->option('layout')){
					$classes[] =  $this->option('contentSize');
				}
			}else{
				$classes[] =  $this->option('contentSize');
			}
		}

		$carouselEnabledOptions = $this->findOptionsContaining('EnableCarousel');
		foreach ($carouselEnabledOptions as $key => $value){
			if ($value == true) {
				$layout = substr($key, 0, strpos($key, 'EnableCarousel'));
				$layoutOptions = $this->findOptionsContaining("[$layout]");

				// if corresponding layout is selected, add 'carousel-enabled' css class
				if(count($layoutOptions) == 1 && reset($layoutOptions) == true){
					$classes[] = 'carousel-enabled';
				}
			}
		}

		$this->addClassesFromSelectInputTypes($classes);

		if(AIT_THEME_PACKAGE === 'basic'){
			$classes[] = 'load-finished';
		}

		$classes = array_unique($classes);

		return $asString ? implode(' ', $classes) : $classes;
	}



	public function getPaths()
	{
		if(isset($this->configuration['no-paths']) and $this->configuration['no-paths']){
			return '';
		}

		if(!$this->paths){
			$this->paths = new stdClass;
			$this->paths->url = (object) array(
				'root'      => aitUrl('elements', "/{$this->id}"),
				'css'       => aitUrl('elements', "/{$this->id}/design/css"),
				'js'        => aitUrl('elements', "/{$this->id}/design/js"),
				'img'       => aitUrl('elements', "/{$this->id}/design/img"),
			);
			$this->paths->dir = (object) array(
				'root'      => aitPath('elements', "/{$this->id}"),
				'css'       => aitPath('elements', "/{$this->id}/design/css"),
				'js'        => aitPath('elements', "/{$this->id}/design/js"),
				'img'       => aitPath('elements', "/{$this->id}/design/img"),
			);
		}

		return $this->paths;
	}



	public function getOption(){
		if($this->optionsObject === null){
			$this->optionsObject = json_decode(json_encode($this->options));
		}

		return $this->optionsObject;
	}



	public function getOptions(){
		return $this->options;
	}



	public function getOptionsDefaults(){
		return $this->optionsDefaults;
	}



	public function setOptionsControlsGroup(AitOptionsControlsGroup $optionsControlsGroup)
	{
		$this->optionsControlsGroup = $optionsControlsGroup;
	}



	public function getOptionsControlsGroup()
	{
		return $this->optionsControlsGroup;
	}


	public function getInternalId(){
		return $this->internalId;
	}



	public function getCacheKey(){
		return $this->getHtmlId() . $this->oid;
	}



	public function getJsObjectName(){
		return get_class($this) . $this->instanceNumber;
	}



	public function getJsObject()
	{
		$o = array(
			'defaults' => $this->optionsDefaults,
			'current'  => $this->options,
			'paths'    => $this->getPaths()->url,
		);

		$var = 'var ' . $this->getJsObjectName() . " = " . json_encode($o) . ';';
		return $var;
	}



	/**
	 * Gets absolute path to element's template.
	 * Template can be set via options from admin panel, then it uses special option key "@template" and is set via selectbox input type.
	 * Or template can be set under "configuration" key via "template" key in config file.
	 *
	 * Priority is this:
	 *
	 * 1. From admin panel:
	 *    It's finding template file in /<elementId>-<custom template name from admin>.latte
	 *
	 * 2. From config under "configuration" key and "template" key:
	 *    It's finding template file in /<custom template name from config>.latte
	 *
	 * 3. Default template
	 *    It's /<elementId>.latte
	 *
	 * @return string Absolute path to element's template file
	 */
	public function getTemplate()
	{
		if(is_null($this->template)){
			if(isset($this->options['@template']) and $this->options['@template'] != 'default'){
				$base = basename($this->templateName, '.latte');
				$template = $base . "-{$this->options['@template']}.latte";
				$f = aitPath('elements', "/{$this->id}/{$template}");
				if($f === false)
					$this->templateName = $template;
			}
			$this->template = aitPath('elements', "/{$this->id}/{$this->templateName}");
		}

		return $this->template;
	}



	public function getBaseStyleUrl()
	{
		if(isset($this->configuration['no-base-style']) and $this->configuration['no-base-style'])
			return '';
		return aitUrl('elements', "/{$this->id}/design/css/base-style.css");
	}



	public function getStyleLessFile()
	{
		if(is_null($this->style)){
			$this->style = aitPath('elements', "/{$this->id}/design/css/{$this->styleLessName}");
		}

		return $this->style;
	}



	public function getStyleLessFileContent()
	{
		$file = $this->getStyleLessFile();
		$content = $file ? @file_get_contents($file) : '';
		return $content;
	}



	public function getInlineStyle()
	{
		return $this->generateCss();
	}



	public function getStyleTag()
	{
		return ''; // Temporary, this method will be deleted from elements' templates
	}



	public function getAssets()
	{
		return $this->assets;
	}



	public function getFrontendAssets()
	{
		return array(
			'css' => $this->assets['css'],
			'js' => $this->assets['js'],
		);
	}



	public function getAdminAssets()
	{
		return array(
			'css' => $this->assets['admin-css'],
			'js' => $this->assets['admin-js'],
		);
	}



	public function getCpts()
	{
		return $this->cpts;
	}



	public function isCloneable()
	{
		return (!isset($this->configuration['cloneable']) or (isset($this->configuration['cloneable']) and $this->configuration['cloneable'] !== false));
	}



	public function isSortable()
	{
		return (!isset($this->configuration['sortable']) or (isset($this->configuration['sortable']) and $this->configuration['sortable'] !== false));
	}


	public function setUsed($used)
	{
		$this->used = $used;
	}


	public function isUsed()
	{
		return $this->used;
	}



	public function isDisplay()
	{
		return (($this->hasOption('@display') and $this->option('@display')) or !$this->hasOption('@display'));
	}



	/**
	 * Element is disabled when depends on some CPTs but they no exists (plugin AIT Toolkit is not active)
	 * @return boolean
	 */
	public function isDisabled()
	{
		return !$this->isEnabled();
	}



	public function isEnabled()
	{
		$return = true;

		if($this->config['@disabled'] === false){
			// sticked unsortable elements such as pagetitle and seo should be always enabled
			if(!$this->isSortable()) {
				$return = true;
			}elseif($this->hasAllCptsEnabled() && aitIsPluginActive('toolkit')){
				$return = true;
			}else{
				$return = false;
			}
		}else{
			$return = false;
		}

		return $return;
	}



	public function isColumnable()
	{
		return (!isset($this->configuration['columnable']) or (isset($this->configuration['columnable']) and $this->configuration['columnable'] == TRUE));
	}



	public function isBetweenSidebars()
	{
		return $this->isBetweenSidebars;
	}



	/**
	 * Checks if this element has given option
	 * @param  string $key Name of option
	 * @return bool
	 */
	public function hasOption($key)
	{
		return isset($this->options[$key]);
	}



	public function findOptionsContaining($string)
	{
		$options = array();
		foreach ($this->options as $key => $value) {
			if (AitUtils::contains($key, $string)) {
				$options[$key] = $value;
			}
		}

		return $options;
	}



	/**
	 * Checks if this element has given option
	 * @param  string $key Name of option
	 * @return bool
	 */
	public function hasAllCptsEnabled()
	{
		$hasAll = true;
		if($this->hasCpts()){
			foreach($this->cpts as $cpt => $enabled){
				if($enabled == false){
					$hasAll = false;
					break;
				}
			}
		}

		return $hasAll;
	}



	/**
	 * Checks if this element has given option
	 * @param  string $key Name of option
	 * @return bool
	 */
	public function hasCpts()
	{
		return !empty($this->cpts);
	}



	// =================================================
	// Setters
	// -------------------------------------------------



	public function setInstanceNumber($number){
		$this->instanceNumber = $number;
	}



	public function setOptions($options){
		$this->options = $options;
	}



	public function setOid($oid){
		$this->oid = $oid;
	}



	public function setCpt($cptId){
		$this->cpts[$cptId] = true;
	}



	public function setPath($type, $kind, $path){
		$this->getPaths();
		if($this->paths){
			$this->paths->{$type}->{$kind} = $path;
		}
	}



	public function setBetweenSidebars($value)
	{
		$this->isBetweenSidebars = $value;
	}



	// =================================================
	// Styles generating helpers
	// -------------------------------------------------


	/**
	 * Generates custom styles for each element
	 * @return string generated CSS
	 */
	protected function generateCss()
	{
		if(!empty($this->generatedCss)){
			return $this->generatedCss;
		}else{
			$content = $this->getStyleLessFileContent();

			$css = array('files' => array(), 'css' => '');

			if($content){
				$css['css'] = $this->compileLess($content, $this->getStyleLessFile());
				$css['files'] = array_keys($this->lessCompiler->allParsedFiles());
			}

			$this->generatedCss = $css;

			return $css;
		}
	}



	public function createLessCompiler()
	{
		return AitLessCompiler::create(
			array_map('dirname', aitGetPaths('elements', "/{$this->id}/design/css/{$this->styleLessName}", 'path', true)),
			array_map('dirname', aitGetPaths('elements', "/{$this->id}/design/css/{$this->styleLessName}", 'url', true))
		);
	}



	/**
	 * Compiles LESS file
	 * @param  string $content Content of LESS file
	 * @param  string $file    Path to LESS file
	 * @return string          Compiled LESS
	 */
	protected function compileLess($content, $file)
	{

		$this->lessCompiler = $this->createLessCompiler();

		// prepare vars
		$vars = array();
		$vars['el'] = $this->htmlId;
		foreach($this->getOptionsControlsGroup()->getSections() as $section){
			foreach($section->getOptionsControls() as $optionObject){
				if($optionObject->isLessVar()){
					$lessVar = $optionObject->getLessVar();
					if($lessVar) {
						$vars += $optionObject->getLessVar();
					}
				}
			}
		}

		$this->lessCompiler->resetVariables();

		$vars = apply_filters("ait-element-{$this->id}-less-variables", $vars, $this);

		$this->lessCompiler->setVariables($vars);

		$this->lessCompiler->allParsedFiles = array();

		$this->lessCompiler->addParsedFile($file);

		try{
			$css = $this->lessCompiler->compile($content);
		}catch(Exception $e){
			$css = sprintf("\n/* Error during parsing LESS file '%s'.\nMessage:\n %s */\n", $file, $e->getMessage());
		}

		return $css;
	}
}
