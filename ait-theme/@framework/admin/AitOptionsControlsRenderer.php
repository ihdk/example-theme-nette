<?php


class AitOptionsControlsRenderer
{

	protected $configType;
	protected $adminPageSlug;

	protected $options;
	protected $defaults;

	/** @var AitOptionsControlsGroup[] */
	protected $optionsControlsGroups = array();

	protected $oid;

	protected $isRenderingDefaultLayout;
	protected $isRenderingPluginOptions;

	protected static $renderer;



	/**
	 * Constructor
	 */
	public function __construct($params)
	{
		$defaults = array(
			'configType'    => '',
			'adminPageSlug' => '',
			'oid'           => '',
			'fullConfig'    => array(),
			'defaults'      => array(),
			'options'       => array(),
		);

		$params = (object) array_merge($defaults, $params);

		$this->configType = $params->configType;
		$this->adminPageSlug = $params->adminPageSlug;
		$this->oid = $params->oid;

		$this->fullConfig = $params->fullConfig;
		$this->defaults = $params->defaults;
		$this->options = $params->options;

		$this->isRenderingDefaultLayout = empty($this->oid);
		$this->isRenderingPluginOptions = !empty($params->isPlugin);
	}



	public static function create($params, $class = '')
	{

		if(!$class) $class = __CLASS__;
		self::$renderer = new $class($params);

		return self::$renderer;
	}



	/**
	 * Renders input controls
	 * @param  array $fullConfig Options to rendered as form inputs
	 * @param  array $defaults   Default values extracted from config
	 * @param  array $options    Current values for options
	 */
	public function render()
	{
		/** @var AitOptionsControlsGroupFactory $groupFactory */
		$groupFactory = AitTheme::getFactory('options-controls-group');

		$groups = array();

		foreach($this->fullConfig as $groupId => $groupDefinition){
			$groups[] = $groupFactory->createOptionsControlsGroup($this->configType, $groupId, $groupDefinition, $this->options[$groupId], $this->defaults[$groupId]);
		}

		foreach($groups as $group){
			$panelId = sanitize_key(sprintf("ait-%s-%s-panel", $this->adminPageSlug, $group->getId()));
			?>

			<div id="<?php echo $panelId?>" class="ait-options-group ait-options-panel ait-<?php echo $this->adminPageSlug ?>-tabs-panel">
				<?php
				$this->renderOptionsControlsGroup($group);
				?>
			</div>
		<?php
		}
	}



	protected function renderOptionsControlsGroup(AitOptionsControlsGroup $group)
	{
		$basicControls = $advancedControls = $tabs = '';

		$j = 0;
		$c = 0;

		foreach($group->getSections() as $i => $section){

			$basic = $advanced = array();

			foreach($section->getOptionsControls() as $optionControl){

				if(!apply_filters('ait-allow-render-option-control', true, $optionControl, $this->oid)){
					continue;
				}

				if(!$optionControl->isBasic() or $section->areAllAdvanced()){
					$advanced[$i][$c] = $optionControl->getHtml();
				}else{
					$basic[$i][$c] = $optionControl->getHtml();
				}

				$c++;
			}

			$c = 0;

			if(empty($basic[$i]) and !empty($advanced[$i]) and !$section->areAllAdvanced()){
				if($section->isCapabilityEnabled()){
					if(current_user_can( $section->getCapabilityName() )){
						$basicControls .= $this->renderSectionBegin($section);
						$basicControls .= implode("\n", $advanced[$i]);
						$basicControls .= $this->renderSectionEnd($section);
					}
				} else {
					$basicControls .= $this->renderSectionBegin($section);
					$basicControls .= implode("\n", $advanced[$i]);
					$basicControls .= $this->renderSectionEnd($section);
				}

			}elseif(!empty($basic[$i]) and empty($advanced[$i])){
				if($section->isCapabilityEnabled()){
					if(current_user_can( $section->getCapabilityName() )){
						$basicControls .= $this->renderSectionBegin($section);
						$basicControls .= implode("\n", $basic[$i]);
						$basicControls .= $this->renderSectionEnd($section);
					}
				} else {
					$basicControls .= $this->renderSectionBegin($section);
					$basicControls .= implode("\n", $basic[$i]);
					$basicControls .= $this->renderSectionEnd($section);
				}

			}else{
				if(empty($basic[$i]) and empty($advanced[$i])) // there is no options in current section
					continue;



				if(!$section->areAllAdvanced()){
					$basicControls .= $this->renderSectionBegin($section);
					$basicControls .= implode("\n", $basic[$i]);
					$basicControls .= $this->renderSectionEnd($section);
				}

				if($j == 0){
					$tabs = $this->renderBasicAdvancedTabs($group);

					if((!$this->isRenderingDefaultLayout and $this->adminPageSlug == 'pages-options')){
						$enabler = new AitAdvancedOptionsEnablerOptionControl($section, $group->getId());
						$enabler->setValue($group->areAdvancedEnabled());
						$advancedControls .= $enabler->getHtml();
					}

					$j++;
				}

				$advancedControls .= $this->renderSectionBegin($section, false);
				$advancedControls .= implode("\n", $advanced[$i]);
				$advancedControls .= $this->renderSectionEnd($section, false);

			}
		}

		$output = '';

		if($basicControls == '' and $advancedControls == ''){
			$output .= $this->noControls(__('Here are no options.', 'ait-admin'));

			$section = new AitOptionsControlsSection($group);
			$no = new AitHiddenOptionControl($section, $group->getId());
			$output .= $no->getHtml();
		}else{
			if(apply_filters("ait-allow-render-controls-{$this->configType}-{$group->getId()}", true, $this->oid)){
				$output .= $this->renderUtilsBar($group, $tabs);
				$output .= $this->renderBasicControls($group, $basicControls);
				$output .= $this->renderAdvancedControls($group, $advancedControls);
			}else{
				// render controls, but hide them
				$output .=  '<div style="display:none">';
				$output .= $this->renderBasicControls($group, $basicControls);
				$output .= $this->renderAdvancedControls($group, $advancedControls);
				$output .= '</div>';

				$output .= $this->noControls(apply_filters('ait-dont-allow-render-controls-message', __('Controls are not allowed to render', 'ait-admin'), $this->configType, $group->getId(), $this->oid));
			}
		}

		echo $output;
	}



	protected function renderBasicControls(AitOptionsControlsGroup $group, $basic)
	{
		$count = $this->countBasicControlsIn($group);
		ob_start();
		?>
		<div id="<?php echo $this->getBAHtmlId($group, 'basic') ?>" class="ait-controls-tabs-panel ait-options-basic ait-options-basic-count-<?php echo $count; ?>">
			<?php echo $basic; ?>
		</div>
		<?php
		return ob_get_clean();
	}



	protected function renderAdvancedControls(AitOptionsControlsGroup $group, $advanced)
	{
		if(empty($advanced)) return '';
		ob_start();
		?>
		<div id="<?php echo $this->getBAHtmlId($group, 'advanced')  ?>" class="ait-controls-tabs-panel ait-options-advanced <?php if(!$group->areAdvancedEnabled() and !$this->isRenderingDefaultLayout): echo 'advanced-options-disabled'; endif; ?>">
			<?php echo $advanced ?>
		</div>
		<?php
		return ob_get_clean();
	}



	public function renderSectionBegin(AitOptionsControlsSection $section, $basic = true)
	{
		ob_start();

		$b = $basic ? "-basic" : '-advanced';
		$sId = $section->getId() ? " id='{$section->getId()}{$b}'" : '';

		$class = $section->getId() ? " section-{$section->getId()}" : '';
		$class .= $section->getTitle() ? ' ait-sec-title' : '';

		$hidden = $section->isHidden() ? ' style="display:none;" ' : '';
		$_translate = '_e';
		?>
		<div class="ait-options-section <?php echo $class ?>" <?php echo $sId, $hidden ?>>
		<?php if($section->getTitle()){ ?>
			<h2 class="ait-options-section-title"><?php  $_translate($section->getTitle(), 'ait-admin') ?></h2>
		<?php } if($section->getHelp()){ ?>
			<div class="ait-options-section-help"><?php $_translate($section->getHelp(), 'ait-admin') ?></div>
		<?php }

		return ob_get_clean();
	}



	public function renderSectionEnd($section, $basic = true)
	{
		return "\n</div>\n";
	}



	public function renderBasicAdvancedTabs(AitOptionsControlsGroup $optionsControlsGroup)
	{
		ob_start();
		?>
		<ul class="ait-controls-tabs">
			<li id="<?php echo $this->getBAHtmlId($optionsControlsGroup, 'basic') ?>-tab"><a href="#<?php echo $this->getBAHtmlId($optionsControlsGroup, 'basic') ?>"><?php _e('Basic', 'ait-admin') ?></a></li>
			<li id="<?php echo $this->getBAHtmlId($optionsControlsGroup, 'advanced') ?>-tab"><a href="#<?php echo $this->getBAHtmlId($optionsControlsGroup, 'advanced') ?>"><?php _e('Advanced', 'ait-admin') ?></a></li>
		</ul>
		<?php
		return ob_get_clean();
	}



	// ============================================================
	// Helper methods for renderer
	// ------------------------------------------------------------


	protected function countBasicControlsIn($group)
	{
		$basicControlsInThisGroup = 0;

		foreach($group->getSections() as $i => $section){
			$_basic = array();

			foreach($section->getOptionsControls() as $optionControl){
				$isAdvanced = (!$optionControl->isBasic() or $section->areAllAdvanced());
				if(!$isAdvanced){
					$basicControlsInThisGroup++;
				}
			}
		}

		return $basicControlsInThisGroup - 3; // -3 is for hidden @element* controlls
	}



	protected function getBAHtmlId(AitOptionsControlsGroup $optionsControlsGroup, $type)
	{
		return sanitize_key("ait-options-{$type}-{$optionsControlsGroup->getId()}") . (!is_null($optionsControlsGroup->getIndex()) ? "-__{$optionsControlsGroup->getIndex()}__" : '');
	}



	public function renderUtilsBar(AitOptionsControlsGroup $optionsControlsGroup, $tabs = '')
	{
		$import = '';
		$reset = '';

		$tpl = '<li><a href="#" class="%s" %s>%s%s</a></li>';

		// Import
		if(!$this->isRenderingDefaultLayout and $this->configType == 'elements'):
			$import = sprintf($tpl,
				'ait-import-global-options',
				aitDataAttr('import-global-options', array(
					'confirm' => __('Are you sure you want to import options from Global Options to this element?', 'ait-admin'),
					'configType' => $this->configType,
					'nonce' => AitUtils::nonce("import-{$this->configType}-{$optionsControlsGroup->getId()}-options"),
					'what' => 'group',
					'group' => $optionsControlsGroup->getId(),
					'oid' => $this->oid,
				)),
				'<span class="action-indicator action-import-global-options"></span>',
				__('Import', 'default')
			);
		endif;

		// Reset
		if(($optionsControlsGroup->getReset() and AitConfig::isMainConfigType($this->configType) and !$this->isRenderingPluginOptions) or $this->configType == 'elements'):
			$confirm =
				$this->configType == 'elements' ?
					__('Are you sure you want to reset options from this element to default values?', 'ait-admin') :
					__('Are you sure you want to reset options from this group to default values?', 'ait-admin');


			$group = $this->configType == 'layout' ? '' : $optionsControlsGroup->getId();

			$reset = sprintf($tpl,
				"ait-reset-group-options",
				aitDataAttr('reset-options', array(
					'confirm' => $confirm,
					'configType' => $this->configType,
					'nonce' => AitUtils::nonce("reset-{$this->configType}-{$group}-options"),
					'what' => 'group',
					'group' => $group,
					'oid' => $this->oid,
				)),
				'<span class="action-indicator action-reset-group"></span>',
				__('Reset', 'ait-admin')
			);
		endif;

		$r = '';
		if($tabs or $import or $reset):
			ob_start();
			?>
			<div class="ait-controls-utils-bar <?php if($tabs == ''): ?>no-tabs<?php endif; ?>">
				<?php echo $tabs ?>

				<ul class="ait-element-utils">
					<?php echo $import ?>
					<?php echo $reset ?>
				</ul>

			</div>
			<?php
			$r = ob_get_clean();
		endif;
		return $r;
	}



	protected function noControls($message = '')
	{
		if(!$message){
			$message = __('No options', 'ait-admin');
		}
		$o =  '<div class="ait-no-controls">';
		$o .= "<em>{$message}</em>";
		$o .= '</div>';
		return $o;
	}

}
