<?php


class AitSidebarsManager
{

	protected $sidebars = array();
	protected $widgetAreas = array();
	protected $dynamicSidebars = array();
	protected $cacheWidgetOutput = 0;



	public function __construct($areasFromThemeConfiguration = array(), $areasFromOptions = array(), $cacheWidgetOutput = 0)
	{
		$this->cacheWidgetOutput = $cacheWidgetOutput;

		$areas = $areasFromThemeConfiguration;

		// from options
		if(isset($areasFromOptions['@widgetAreasAndSidebars']) and !empty($areasFromOptions['@widgetAreasAndSidebars'])){
			$areas = $areasFromOptions['@widgetAreasAndSidebars'];
		}

		$this->prepare($areas);
	}



	public function getDynamicSidebars()
	{
		return $this->dynamicSidebars;
	}



	public function getSidebars()
	{
		return $this->sidebars;
	}



	public function getWidgetAreas()
	{
		return $this->widgetAreas;
	}



	public function registerSidebars()
	{
		add_action('widgets_init', array($this, 'initSidebars'), 2);
	}



	public function registerWidgets()
	{
		remove_filter('widget_title', 'wptexturize');
		remove_filter('widget_title', 'convert_chars');
		remove_filter('widget_title', 'esc_html');

		if($this->cacheWidgetOutput != 0)
			add_filter('widget_display_callback', array($this, 'cacheWidgetOutput'), 10, 3);

		add_filter('widget_title', array($this, 'widgetTitle'), 3, 1999);

		add_action('widgets_init', array($this, 'initWidgets'), 4);
	}



	/**
	 * Prepares Widget Areas and Sidebars for registering via widgets_init action
	 */
	protected function prepare($areas)
	{
		$defaults = array(
			// these default values are hacks for wrapping title to one div
			// and content to the other div
			'description'   => '',
			'before_widget' => '<div id="%1$s" class="widget-container %2$s"><div class="widget">',
			'after_widget'  => "</div></div></div>",
			'before_title'  => '<div class="widget-title">',
			'after_title'   => '</div><div class="widget-content">',
		);

		$defaults = apply_filters('ait-widget-areas-default-parmas', $defaults);

		$i = 0;

		foreach($areas as $group => $area){
			foreach($area as $i => $params){
				$realId = '__' . trim("{$group}-{$i}", '@');
				$p = array_diff_assoc($params, $defaults);
				if($group == '@sidebar'){
					$this->sidebars[$realId] = $p;
				}else{
					$this->widgetAreas[$group][$realId] = $p;
				}

				$s = array_merge(array('id' => $realId), $defaults, $params);

				$name = AitLangs::getCurrentLocaleText($params['name'], '[not translated sidebar name]');
				if(empty($name)){
					$s['name'] = '[not translated sidebar name]';
				}else{
					$s['name'] = $name;
				}

				$this->dynamicSidebars[] = $s;
			}
		}
	}



	/**
	 * Registers widget areas and sidebars
	 */
	public function initSidebars()
	{
		foreach($this->dynamicSidebars as $sidebar){
			register_sidebar($sidebar);
		}
	}



	/**
	 * Loads defined widgets in config file
	 */
	public function initWidgets()
	{
		$config = AitConfig::loadRawConfig(aitPath('config', '/widgets.neon'), '/widgets.neon');

		foreach($config as $widget){
			$widgetClass = AitUtils::id2class($widget, 'Widget');
			if(class_exists($widgetClass))
				register_widget($widgetClass);
			else
				trigger_error("Widget class {$widgetClass} doesn't exist.", E_USER_WARNING); // less aggressive as fatal error if widget class does no exist
		}
	}



	/**
	 * Fixes widget title, it insert permanent container div around title,
	 * if title is empty only empty container div is returned
	 * @param  string $title
	 * @param  array  $instance
	 * @param  string $idBase
	 * @return string
	 */
	public function widgetTitle($title, $instance = array(), $idBase = '')
	{
		if(isset($instance['ait-dropdown-wc-cart-widget']) and $instance['ait-dropdown-wc-cart-widget']) return '';

		$hasTitle = (trim(str_replace('&nbsp;', '', $title)) !== '');

		if($hasTitle){
			// default filters was removed, so apply these function manualy to title
			$title = esc_html(convert_chars(wptexturize($title)));
			if($idBase === 'rss'){
				return $title;
			}else{
				return "<h3>{$title}</h3>";
			}
		}
		// if title is empty return whitespace thus condition for checking
		// emptyness of the title in default WP widget will always pass
		// and will outputs $before_title . ' ' . $after_title
		return '<!-- no widget title -->';
	}



	/**
	 * Callback for widget_display_callback
	 * Thanks to plugin "Widget Output Cache" by Kaspars Dambis
	 */
	public function cacheWidgetOutput($instance, $widgetObject, $args)
	{
		$timerStart = microtime(true);
		$key = 'widget-' . md5(serialize(array($instance, $args)));

		$cachedWidget = get_transient($key);

		$ttl = $this->cacheWidgetOutput;

		if(empty($cachedWidget)){

			ob_start();
			$widgetObject->widget($args, $instance);
			$cachedWidget = ob_get_clean();

			set_transient($key, $cachedWidget, $ttl);
		}

		printf(
			"%s <!-- From widget cache in %s seconds -->",
			$cachedWidget,
			number_format(microtime(true) - $timerStart, 5)
		);

		// We already echoed the widget, so return false
		return false;
	}
}
