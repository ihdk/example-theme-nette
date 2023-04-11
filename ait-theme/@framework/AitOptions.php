<?php


/**
 * Class for Options
 */
class AitOptions
{

	protected $config;
	protected $options;
	protected $elementsPrototypes;

	protected $storage = array();

	protected static $frontpage;



	/**
	 * Constructor
	 * @param array $config
	 * @param array $elementsPrototypes
	 */
	public function __construct(AitConfig $config)
	{
		$this->config = $config;
		$this->options = $config->getDefaults();

		self::$frontpage = (object) array(
			'customFrontpage' => get_option('show_on_front') == 'page',
			'blog'            => get_option('page_for_posts'),
		);

		if(is_admin()){
			$this->registerValidation();
		}

		if(current_user_can('delete_posts')){
			add_action('delete_post', array(&$this, 'deleteLocalOptionsOnPageDelete'), 10);
		}

		add_action('pll_before_add_language', array($this, 'onBeforeAddLanguage'), 10, 1);

		// for WP 4.2
		add_action('split_shared_term', array($this, 'updateTermIdsOnSplitSharedTerm'), 10, 4);
	}



	public function updateTermIdsOnSplitSharedTerm($oldTermId, $newTermId, $termTaxonomyId, $taxonomy)
	{
		// it will update all old term IDs only in 'elements' options, elsewhere terms are not used

		$config = $this->config->getFullConfig();
		$register = $this->getLocalOptionsRegister();

		foreach($register as $type => $oids){
			foreach($oids as $oid){

				$localOptions = $this->getLocalOptions($oid);
				$elements = &$localOptions['elements'];

				foreach($elements as $elIndex => $element){
					$elId = key($element);
					foreach($element[$elId] as $optionKey => $optionVal){

						if($elConfig = $this->findElementInConfig($elId, $config['elements'])){
							foreach($elConfig['@options'] as $sectionIndex => $options){
								foreach($options as $k => $v){
									if(
										$k !== '@section' and
										($v['type'] === 'categories' or $v['type'] === 'categories-featured') and
										$optionKey === $k and
										!empty($optionVal) and // not '0' neither 0
										$v['taxonomy'] === AitUtils::stripPrefix($taxonomy) and
										$optionVal == $oldTermId
									){
										$elements[$elIndex][$elId][$optionKey] = $newTermId;
										update_option($this->getOptionKey('elements', $oid), $elements);
									}
								}
							}
						}

					}
				}

			}
		}

	}



	protected function findElementInConfig($elementId, $elements)
	{
		foreach($elements as $element){
			if($elementId === $element['@element']){
				return $element;
			}
		}
		return array();
	}



	public function setElementsPrototypes($prototypes)
	{
		$this->elementsPrototypes = $prototypes;
	}



	public function registerValidation()
	{
		$keys = $this->getOptionsKeys(AitConfig::getMainConfigTypes(), $this->getRequestedOid('get'));
		foreach($keys as $key){
			add_filter("sanitize_option_{$key}", array($this, 'validateOptions'), 10, 2);
		}
	}



	/**
	 * TODO
	 */
	public function validateOptions($input, $optKey)
	{
		return $input;
	}



	/**
	 * Factory for one option key
	 * @param  string $type Type, like: 'theme', 'elements', 'layout'
	 * @param  string $oid  OID, like '_page_123'
	 * @return string
	 */
	public function getOptionKey($type, $oid = '')
	{
		$theme = AIT_CURRENT_THEME;
		return "_ait_{$theme}_{$type}_opts{$oid}";
	}



	/**
	 * Factory for multiple options keys
	 * @param  array $types Types, arrays of types. @see getOptionKey()
	 * @param  string $oid   OID
	 * @return array
	 */
	public function getOptionsKeys($types, $oid = '')
	{
		$return = array();
		foreach($types as $type){
			$return[] = $this->getOptionKey($type, $oid);
		}

		return $return;
	}



	public function getLocalOptionsRegisterKey()
	{
		return '_ait_' . AIT_CURRENT_THEME . '_local_opts_register';
	}



	/**
	 * Shortcut method for accessing options in PHP like in WpLatte tempaltes
	 * @param  string $configType 'theme', 'layout', 'elements'
	 * @param  string $oid         Object/Options ID
	 * @return stdClass            Options array converted to nested stdClass
	 */
	public function get($configType, $oid = null)
	{
		$k = $configType . $oid;

		if(!isset($this->storage[$k])){
			$r = $this->getOptionsByType($configType, $oid);
			$this->storage[$k] = json_decode(json_encode($r));
			return $this->storage[$k];
		}else{
			return $this->storage[$k];
		}
	}



	/**
	 * Gets options by Type
	 * @param  string $configType
	 * @param  string $oid  OID
	 * @return mixe
	 */
	public function getOptionsByType($configType, $oid = null)
	{
		if($oid === null){
			$oid = $this->getOid();
		}

		$o = $this->getOptions($oid);

		if(isset($o[$configType])){
			return $o[$configType];
		}else{
			$k = implode(', ', array_keys($o));
			trigger_error("There is no config type '$configType' (" . __METHOD__ . " method). There are only: $k ");
			return array();
		}
	}



	public function getOptions($oid = null)
	{
		if($oid){ // local
			if(isset($this->storage["local$oid"])){
				return $this->storage["local$oid"];
			}

			$this->storage["local$oid"] = $this->getLocalOptions($oid);
			return $this->storage["local$oid"];

		}else{ // global
			if(isset($this->storage['global'])){
				return $this->storage['global'];
			}

			$this->storage['global'] = $this->getGlobalOptions();
			return $this->storage['global'];
		}
	}



	/**
	 * Gets global options
	 * @return array
	 */
	public function getGlobalOptions()
	{
		$theme = get_option($this->getOptionKey('theme'), array());

		if(!isset($this->options['theme'])){
			$this->options['theme'] = array();
		}

		if(!empty($theme)){
			$this->options['theme'] = $this->mergeConfigDefaultsAndOptions($this->options['theme'], $theme);
		}

		$layout = get_option($this->getOptionKey('layout'), array());

		if(!isset($this->options['layout'])){
			$this->options['layout'] = array();
		}

		if(!empty($layout)){
			$this->options['layout'] = $this->mergeConfigDefaultsAndOptions($this->options['layout'], $layout);
		}

		$elements = get_option($this->getOptionKey('elements'), null);

		if(!isset($this->options['elements'])){
			$this->options['elements'] = array();
		}

		$fullConfig = $this->config->getFullConfig('elements');

		// There is nothing saved, maybe options were deleted from DB manualy
		// or other edge case occured
		if($elements === null){
			$this->options['elements'] = $this->getDefaultsOnlyOfUsedElements($fullConfig);
		}else{
			$this->options['elements'] = $this->mergeElementsConfigDefaultsAndOptions($this->options['elements'], $elements, $fullConfig);
		}

		$pluginConfigs = array();
		$pluginConfigs = apply_filters('ait-config-types', $pluginConfigs);
		foreach($pluginConfigs as $key){
			if (in_array($key, array('theme', 'layout', 'elements'))) continue;
			$plugin = get_option($this->getOptionKey($key), array());
			if(!isset($this->options[$key])){
				$this->options[$key] = array();
			}

			if(!empty($plugin)){
				$this->options[$key] = $this->mergeConfigDefaultsAndOptions($this->options[$key], $plugin);
			}
		}

		return $this->options;
	}



	public function getDefaultsOnlyOfUsedElements($fullConfig = null)
	{
		if(isset($this->storage["getDefaultsOnlyOfUsedElements"]))
			return $this->storage["getDefaultsOnlyOfUsedElements"];

		if(!$fullConfig) $fullConfig = $this->config->getFullConfig('elements');

		$defaultsOfUsedElements = $this->config->getDefaults('elements');

		foreach($defaultsOfUsedElements as $i => $el){
			$elId = key($el);
			if(isset($fullConfig[$i]) and $fullConfig[$i]['@element'] == $elId and !$fullConfig[$i]['@used']){
				unset($defaultsOfUsedElements[$i]);
			}
		}

		$this->storage["getDefaultsOnlyOfUsedElements"] = $defaultsOfUsedElements;
		return $this->storage["getDefaultsOnlyOfUsedElements"];
	}



	/**
	 * Gets local options - options for specific page
	 * @param  string $oid Object ID
	 * @return array
	 */
	public function getLocalOptions($oid = null)
	{
		if($oid === null){
			$oid = $this->getOid();
		}

		$localOptions = $globalOptions = $this->getOptions();

		$register = $this->getLocalOptionsRegister();

		if(!(in_array($oid, $register['pages']) or in_array($oid, $register['special']))){
			return $localOptions;
		}


		// layout options
		$layout = get_option($this->getOptionKey('layout', $oid), array());

		if(!empty($layout)){
			$localOptions['layout'] = array_replace_recursive($globalOptions['layout'], $layout);
		}


		// elements options
		$elements = get_option($this->getOptionKey('elements', $oid), null);

		if($elements === null){
			$localOptions['elements'] = $globalOptions['elements'];
		}else{
			if(!$elements) $elements = array();
			$localOptions['elements'] = $this->mergeGlobalAndLocalElements($globalOptions['elements'], $elements);
		}

		return $localOptions;
	}



	public function hasCustomLocalOptions($oid)
	{
		$register = $this->getLocalOptionsRegister();
		if (AitUtils::startsWith($oid, '_page_')) {
			return in_array($oid, $register['pages']);
		} else {
			return in_array($oid, $register['special']);
		}
	}



	/**
	 * Get list of all (oids) saved local options
	 * @param  array  $special Default value for 'special' oids
	 * @param  array  $pages   Default value for 'pages' oids
	 * @return array
	 */
	public function getLocalOptionsRegister($special = array(), $pages = array())
	{
		return get_option($this->getLocalOptionsRegisterKey(), array('special' => $special, 'pages' => $pages));
	}



	/**
	 * Merges global and local elements and its options
	 * @param  array $ge Global elements and its options
	 * @param  array $le Local elements and its options
	 * @return array     Merged elements and its options
	 */
	protected function mergeGlobalAndLocalElements($globalElements, $localElements)
	{
		$u = $m = $missingGlobalElements = array();

		// find missing elements which are in local settings
		// but not in global settings
		// 1st step
		foreach($globalElements as $i => $el){
			$m[key($el)] = 1; // simple list of global elements
		}
		// 2nd step
		foreach($localElements as $i => $el){
			$k = key($el);
			if(!isset($m[$k]))
				$missingGlobalElements[$k] = 1;
		}

		$localElements = $this->handleMissingUnsortableElements($localElements);

		// Merging global and local options in elements...
		// If there is missing element in global settings which
		// is in local settings, therefore element in local settings
		// can not inherit advanced options from missing element in global settings
		// thus have to take advanced options from config default values

		foreach($localElements as $i => $localEl){
			$localElId = key($localEl);

			foreach($globalElements as $j => $globalEl){
				$globalElId = key($globalEl);

				if($localElId == $globalElId){
					foreach($globalElements[$j][$globalElId] as $optKey => $optVal){
						if(!isset($localElements[$i][$localElId][$optKey])){
							$localElements[$i][$localElId][$optKey] = $optVal;
						}
					}
				}
			}
		}

		// if in global settings is missing some element
		// so take advanced options from that specific element's config defaults
		foreach($localElements as $i => $localEl){
			$localElId = key($localEl);
			if(isset($missingGlobalElements[$localElId]) and isset($this->elementsPrototypes[$localElId])){
				foreach($this->elementsPrototypes[$localElId]->optionsDefaults as $dOptKey => $dOptVal){
					if(!isset($localElements[$i][$localElId][$dOptKey])){
						$localElements[$i][$localElId][$dOptKey] = $dOptVal;
					}
				}
			}
		}

		return $localElements;
	}



	protected function handleMissingUnsortableElements($localElements)
	{
		$prototypes = aitManager('elements')->getPrototypes();
		$defaultUnsortablesFromConfig = $foundLocalUnsortables = array();

		foreach($prototypes as $proto){
			if(!$proto->isSortable()){
				$defaultUnsortablesFromConfig[$proto->getId()] = $proto->getOptionsDefaults();
			}
		}

		$lastLocalUnsortableId = 0;

		foreach($localElements as $id => $localEl){
			$localElId = key($localEl);
			if(isset($defaultUnsortablesFromConfig[$localElId])){
				$foundLocalUnsortables[$localElId] = $localEl[$localElId];
				$lastLocalUnsortableId = $id;
			}
		}

		$missingUnsortables = array_diff(array_keys($defaultUnsortablesFromConfig), array_keys($foundLocalUnsortables));
		$missingUnsortablesToInsert = array();

		foreach($missingUnsortables as $missingUnsortableElementId){
			$missingUnsortablesToInsert['e' . uniqid()][$missingUnsortableElementId] = $defaultUnsortablesFromConfig[$missingUnsortableElementId];
		}

		NArrays::insertAfter($localElements, $lastLocalUnsortableId, $missingUnsortablesToInsert);

		return $localElements;
	}



	/**
	 * Adds new local options. It picks only basic options from saved global options
	 * @param string $oid OID
	 */
	public function addLocalOptions($oid)
	{
		$layout = $elements = $local = array();

		$local['layout'] = $local['elements'] = array();

		// get current global options
		$layout   = get_option($this->getOptionKey('layout'), array());
		$elements = get_option($this->getOptionKey('elements'), array());

		$fullConfigLayout = $this->config->getFullConfig('layout');

		// from saved global layout options pick just options which are basic
		foreach($fullConfigLayout as $groupKey => $groupData){
			foreach($groupData['@options'] as $sectionIndex => $section){
				unset($section['@section']);
				foreach($section as $optionKey => $optionDefinition){
					if(isset($layout[$groupKey][$optionKey]) && isset($optionDefinition['basic']) && $optionDefinition['basic']){
						$local['layout'][$groupKey][$optionKey] = $layout[$groupKey][$optionKey];
					}
				}
			}
		}

		if(empty($local['layout'])){
			$local['layout'] = $layout;
		}

		$fullConfigElements = $this->config->getFullConfig('elements');

		$basicOpts = array();

		foreach($fullConfigElements as $i => $el){
			foreach($el['@options'] as $section){
				unset($section['@section']);
				foreach($section as $optionKey => $optionDefinition){
					if(isset($optionDefinition['basic']) && $optionDefinition['basic']){
						$basicOpts[$el['@element']][$optionKey] = true;
					}
				}
			}
		}

		$index = null;

		// from saved global elements options pick just options which are basic
		// omg
		foreach($elements as $i => $els){
			foreach($els as $el => $options){

				if($el == 'content') $index = $i;

				if (isset($basicOpts[$el])) {
					$basicElementOptions = $basicOpts[$el];

					foreach($options as $key => $val){
						if (isset($basicElementOptions[$key])) {
							$local['elements'][$i][$el][$key] = $val;
						}
					}
				}


				// if there is no basic options, all are advanced,
				// so make all advanced as basic
				if(!isset($local['elements'][$i][$el])){
					$local['elements'][$i][$el] = $elements[$i][$el];
				}
			}
		}

		$configTypes = array('layout', 'elements');

		foreach($this->getOptionsKeys($configTypes, $oid) as $key){
			delete_option($key);
		}

		$global = $this->getOptions();

		foreach($configTypes as $type){
			if(empty($local[$type])){
				$local[$type] = $global[$type];
			}
			add_option($this->getOptionKey($type, $oid), $local[$type], '', 'no');
		}

		$register = $this->getLocalOptionsRegister();

		if(AitUtils::startsWith($oid, '_page_')){
			$register['pages'][] = $oid;
		}else{
			$register['special'][] = $oid;
		}

		update_option($this->getLocalOptionsRegisterKey(), $register);
	}



	/**
	 * Deletes local options
	 * @param  string $oid OID
	 * @return void
	 */
	public function deleteLocalOptions($oid)
	{
		$this->deleteLocalOptionFromRegister($oid);

		foreach($this->getOptionsKeys(array('layout', 'elements'), $oid) as $key){
			delete_option($key);
		}
	}



	/**
	 * Deletes local option from local options register
	 * @param  string $oid OID
	 * @return void
	 */
	public function deleteLocalOptionFromRegister($oid)
	{
		$register = $this->getLocalOptionsRegister();

		if(AitUtils::startsWith($oid, '_page_')){
			$i = array_search($oid, $register['pages']);
			if($i !== false)
				unset($register['pages'][$i]);
		}else{
			$i = array_search($oid, $register['special']);
			if($i !== false)
				unset($register['special'][$i]);
		}

		update_option($this->getLocalOptionsRegisterKey(), $register);
	}



	/**
	 * Deletes local option from local options register
	 * @param  string $oid OID
	 * @return void
	 */
	public function updateLocalOptionsRegister($oid)
	{
		$register = $this->getLocalOptionsRegister();

		if(AitUtils::startsWith($oid, '_page_')){
			$register['pages'][] = $oid;
			$register['pages'] = array_unique($register['pages']);
		}else{
			$register['special'][] = $oid;
			$register['special'] = array_unique($register['special']);
		}

		update_option($this->getLocalOptionsRegisterKey(), $register);
	}



	public function pageForLocalOptionsIsAvailable($oid)
	{
		if (AitUtils::startsWith($oid, '_page_')) {
			$pid = (int) substr($oid, 6);
			$post = get_post($pid);
			return isset($post) && $post->post_status != 'trash';
		} else {
			$specialPages = $this->getSpecialCustomPages();
			return isset($specialPages[$oid]);
		}
	}



	public function getFirstFoundLocalOptionsId()
	{
		$localOptionsRegister = $this->getLocalOptionsRegister();
		$specialCustomPages = $this->getSpecialCustomPages();

		if (isset($localOptionsRegister['special'])) {
			foreach($localOptionsRegister['special'] as $specialPage) {
				if (isset($specialCustomPages[$specialPage])) return $specialPage;
			}
		}

		if (isset($localOptionsRegister['pages'])) {
			foreach($localOptionsRegister['pages'] as $page) {
				if ($this->pageForLocalOptionsIsAvailable($page)) return $page;
			}
		}

		return NULL;
	}



	public function resetAllOptions()
	{
		// do not reset administrator settings
		$old = get_option($this->getOptionKey('theme'), array());
		$defaults = $this->config->defaults;

		if(isset($old['administrator'])){
			$defaults['theme']['administrator'] = $old['administrator'];
		}

		foreach(AitConfig::getMainConfigTypes() as $type){
			$key = $this->getOptionKey($type);
			update_option($key, $defaults[$type]);
		}
	}



	public function resetThemeOptions()
	{
		// do not reset administrator settings
		$old = get_option($this->getOptionKey('theme'), array());
		$defaults = $this->config->getDefaults('theme');

		if(isset($old['administrator'])){
			$defaults['administrator'] = $old['administrator'];
		}

		update_option($this->getOptionKey('theme'), $defaults);
	}



	public function resetDefaultLayoutOptions()
	{
		$defaults = $this->config->extractDefaultsFromConfig($this->config->getRawConfig(), true);

		update_option($this->getOptionKey('layout'), $defaults['layout']);
		update_option($this->getOptionKey('elements'), $defaults['elements']);
	}



	public function resetOptionsGroup($configType, $groupKey, $oid)
	{
		$old = get_option($this->getOptionKey($configType, $oid), array());
		$defaults = $this->config->extractDefaultsFromConfig($this->config->getRawConfig(), true);

		if($configType == 'theme' or $configType == 'layout'){

			if($groupKey and isset($defaults[$configType][$groupKey])){
				$old[$groupKey] = $defaults[$configType][$groupKey];
			}elseif(!$groupKey and isset($defaults[$configType])){
				$old = $defaults[$configType];
			}
			update_option($this->getOptionKey($configType, $oid), $old);

		}elseif($configType == 'elements'){

			$idx = array();
			foreach($old as $i => $el){
				$idx[key($el)] = $i;
			}

			foreach($defaults[$configType] as $i => $el){
				foreach($el as $key => $el){
					if($groupKey == $key and !empty($idx) and isset($old[$idx[$key]][$key])){
						$old[$idx[$key]][$key] = array_intersect_key($el, $old[$idx[$key]][$key]);
						update_option($this->getOptionKey($configType, $oid), $old);
					}
				}
			}
		}
	}



	public function importGlobalOptions($configType, $groupKey, $oid)
	{
		$globalOld = get_option($this->getOptionKey($configType), array());
		$localOld = get_option($this->getOptionKey($configType, $oid), array());

		if($configType == 'layout'){

			// nothing for now...

		}elseif($configType == 'elements'){

			$idx = array();
			foreach($globalOld as $i => $el){
				$idx[key($el)] = $i;
			}

			foreach($localOld as $i => $el){
				foreach($el as $key => $el){
					if($groupKey == $key and isset($globalOld[$idx[$key]])){
						$localOld[$i][$key] = array_intersect_key($globalOld[$idx[$key]][$key], $el);
						update_option($this->getOptionKey($configType, $oid), $localOld);
					}
				}
			}
		}
	}



	// =======================================
	// Helpers
	// ---------------------------------------

	protected static $specialPages;

	/**
	 * Special types of pages for which we can aplly local options
	 * @return array
	 */
	public function getSpecialCustomPages()
	{
		$pages = array();

		if(!empty(self::$specialPages)){
			return self::$specialPages;
		}else{

			$blogPage = false;
			if(self::$frontpage->customFrontpage and self::$frontpage->blog){
				$blogPage = get_page(self::$frontpage->blog)->post_title;
			}

			$pages = array(
				'_blog' => array(
					'label' => $blogPage ? sprintf(__('%s (blog)', 'ait-admin'), $blogPage) : esc_html__('Homepage (blog)', 'ait-admin'),
					'with-id' => false,
					'if'      => 'is_home',
				),
				'_404' => array(
					'label'   => esc_html__('404 page', 'ait-admin'),
					'with-id' => false,
					'if'      => 'is_404',
				),
				'_search' => array(
					'label'   => esc_html__('Search page', 'ait-admin'),
					'with-id' => false,
					'if'      => 'is_search',
				),
				'_archive' => array(
					'label'     => esc_html__('Archive pages', 'ait-admin'),
					'sub-label' => esc_html__('Category, Taxonomy, Tag, Author, Date', 'ait-admin'),
					'with-id'   => false,
					'if'        => 'is_archive',
				),
				'_attachment' => array(
					'label'   => esc_html__('Attachment pages', 'ait-admin'),
					'with-id' => false,
					'if'      => 'is_attachment',
				),
				'_post' => array(
					'label'   => esc_html__('Single post', 'ait-admin'),
					'with-id' => false,
					'if'      => array('is_singular', array('post')),
				),
			);

			if(aitIsPluginActive('toolkit')){
				$cpts = aitManager('cpts')->getAll();

				foreach($cpts as $cpt){
					if($cpt instanceof AitPublicCpt){
						$pages["_{$cpt->getId()}"] = array(
							'label'   => $cpt->getLabels()->singular_name,
							'with-id' => false,
							'if'      => array('is_singular', array($cpt->getInternalId())),
						);
					}
				}
			}

			if(AitWoocommerce::enabled()){

				$pages['_wc_product'] = array(
					'label'   => esc_html__('Single - WooCommerce Product', 'ait-admin'),
					'with-id' => false,
					'if'      => array('AitWoocommerce::currentPageIs', array('product')),
				);

				$shop = AitWoocommerce::getPage('shop');
				if($shop){
					$pages['_wc_shop'] = array(
						'label'   => sprintf(__('%s - WooCommerce Shop Page', 'ait-admin'), $shop->post_title),
						'with-id' => false,
						'if'      => array('AitWoocommerce::currentPageIs', array('woocommerce')),
					);
				}
			}


			self::$specialPages = apply_filters('ait-special-custom-pages', $pages);

			return self::$specialPages;
		}
	}



	public function getFrontpage()
	{
		return self::$frontpage;
	}




	public function isNormalPageOptions($oid)
	{
		return AitUtils::startsWith($oid, "_page_");
	}



	/**
	 * Gets part of the option key for queried object
	 * a.k.a. OID
	 * @return string Part of the key
	 */
	public function getOid()
	{
		$key = '';

		if(!did_action('template_redirect')){
			$key = $this->getRequestedOid('post');
			if(!$key){
				$key = $this->getRequestedOid('get');
			}

			return $key;
		}

		$pages = $this->getSpecialCustomPages();

		// rearange array for condition cascade
		// woocommerce must be before is_archive()
		if(isset($pages['_wc_shop'])){
			$shop = $pages['_wc_shop'];
			$product = $pages['_wc_product'];
			unset($pages['_wc_shop']);
			unset($pages['_wc_product']);
			$splitIndex = array_search('_404', array_keys($pages)) + 1;
			$pages = array_merge(
				array_slice($pages, 0, $splitIndex),
				array('_wc_product' => $product),
				array('_wc_shop' => $shop),
				array_slice($pages, $splitIndex)
			);
		}

		// normal pages are with id
		$pages['_page'] = array(
			'with-id' => true,
			'if'      => 'is_page',
		);

		foreach($pages as $oid => $values){

			$ifResult = false;

			// example: 'if' => function() { return 1 != 0; },
			if(is_callable($values['if'])){
				$ifResult = call_user_func($values['if']);
			// example: 'if' => 'is_home'
			}elseif(is_string($values['if']) and is_callable($values['if'])){
				$ifResult = call_user_func($values['if']);
			// example: 'if' => array('is_singular', array('post'))
			}elseif(is_array($values['if']) and is_string($values['if'][0]) and is_callable($values['if'][0]) and is_array($values['if'][1])){
				$ifResult = call_user_func_array($values['if'][0], $values['if'][1]);
			}

			if($ifResult){
				$key = $oid;

				$id = get_queried_object_id();

				if($id and $values['with-id']){
					$key = "{$key}_{$id}";
				}

				break;
			}
		}

		return $key;
	}



	public function isQueryForSpecialPage($oidsToCheck = array())
	{
		$pages = $this->getSpecialCustomPages();
		$oid = $this->getOid();
		if(empty($oidsToCheck) and isset($pages[$oid])){
			return true;
		}else{
			return in_array($oid, $oidsToCheck);
		}
	}



	public function getRequestedOid($requestMethod = 'post')
	{
		$oid = '';

		if($requestMethod == 'post'){
			$oid = isset($_POST['oid']) ? $_POST['oid'] : '';
		}elseif($requestMethod == 'get'){
			$oid = isset($_GET['oid']) ? $_GET['oid'] : '';
		}

		return sanitize_key($oid);
	}



	public function getRequestedPluginCodename($requestMethod = 'post')
	{
		$codename = '';

		if($requestMethod == 'post'){
			$codename = isset($_POST['pluginCodename']) ? $_POST['pluginCodename'] : '';
		}elseif($requestMethod == 'get'){
			$codename = isset($_GET['pluginCodename']) ? $_GET['pluginCodename'] : '';
		}

		return sanitize_key($codename);
	}



	public function mergeConfigDefaultsAndOptions($defaultValues, $currentValues)
	{
		if(is_array($defaultValues) and is_array($currentValues)){
			foreach($currentValues as $key => $value){
				if((isset($defaultValues[$key]) and is_numeric($key)) or (!isset($defaultValues[$key]) and is_numeric($key))){
					$defaultValues = $currentValues; // leaf
				}elseif(isset($defaultValues[$key])){
					$defaultValues[$key] = $this->mergeConfigDefaultsAndOptions($defaultValues[$key], $value);
				}
			}
		}else{
			$defaultValues = $currentValues; // leaf
		}

		return $defaultValues;
	}



	public function mergeElementsConfigDefaultsAndOptions($defaultValues, $currentValues, $fullConfig)
	{
		if(is_array($defaultValues) and is_array($currentValues)){
			foreach($defaultValues as $key => $value){

				foreach ($currentValues as $currentKey => $currentValue) {
					if (key($currentValue) == key($value)) {
						$currentValues[$currentKey] = $this->mergeConfigDefaultsAndOptions($defaultValues[$key], $currentValues[$currentKey]);
						continue 2;
					}
				}

				if(isset($fullConfig[$key]) and $fullConfig[$key]['@used']){
					// append forced used element to the end of elements
					$currentValues[] = $value;
				}
			}
		}else{
			$currentValues = $defaultValues;
		}

		return $currentValues;
	}



	public function deleteLocalOptionsOnPageDelete($postId)
	{
		$oid = '_page_' . $postId;
		if ($this->hasCustomLocalOptions($oid)) {
			$this->deleteLocalOptions($oid);
		}
	}



	/**
	 * @hook pll_before_add_language
	 */
	public function onBeforeAddLanguage($args)
	{
		$locale = $args['locale'];
		$defaultLocale = AitLangs::getDefaultLocale();

		$options = $this->getOptions();
		foreach ($options as $configType => &$configOptions) {
			$key = $this->getOptionKey($configType);
			$this->addOptionsTranslationsForLocale($configOptions, $defaultLocale, $locale);
			update_option($key, $configOptions);
		}

		$register = $this->getLocalOptionsRegister();

		foreach($register['special'] as $oid){
			$options = $this->getOptions($oid);
			unset($options['theme']);
			foreach ($options as $configType => &$configOptions) {
				$key = $this->getOptionKey($configType, $oid);
				$this->addOptionsTranslationsForLocale($configOptions, $defaultLocale, $locale);
				update_option($key, $configOptions);
			}
		}

		foreach($register['pages'] as $oid){
			$postId = (int) substr($oid, 6);
			$defaultLocale = AitLangs::getPostLang($postId)->locale;

			if($this->hasCustomLocalOptions($oid)){
				$options = $this->getOptions($oid);
				unset($options['theme']);
				foreach ($options as $configType => &$configOptions) {
					$key = $this->getOptionKey($configType, $oid);
					$this->addOptionsTranslationsForLocale($configOptions, $defaultLocale, $locale);
					update_option($key, $configOptions);
				}
			}
		}
	}



	private function addOptionsTranslationsForLocale(&$options, $defaultLocale, $locale)
	{
		foreach ($options as $key => &$subOptions) {
			if (is_array($subOptions)) {
				foreach ($subOptions as $subKey => &$subOption) {
					if (is_array($subOption)) {
						$this->addOptionsTranslationsForLocale($subOptions, $defaultLocale, $locale);
					} else if ($defaultLocale == $subKey) {
						$defaultLocaleOptionTranslation = $subOption;
						$optionTranslations = &$subOptions;
						$optionTranslations[$locale] = $defaultLocaleOptionTranslation;
						break;
					}
				}
			}
		}
	}


}
