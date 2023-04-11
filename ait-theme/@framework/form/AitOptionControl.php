<?php


/**
 * Abstract base class for options in config files
 */
class AitOptionControl extends NObject
{

	protected $key;

	protected $value;

	protected $lessVar;

	/**
	 * Name of control, e.g. textarea, on-off
	 * @var string
	 */
	public $id;

	/**
	 * Config values of option
	 * @var stdClass
	 */
	protected $config;


	protected $isCloneable = false;
	protected $isLessVar = false;

	protected $idAttr = '';
	protected $classAttr = '';
	protected $htmlIdAndClassAttrs = '';
	protected $nameAttr = '';

	protected $index = 0;

	/** @var AitOptionsControlsSection */
	protected $parentSection = null;

	/** @var AitOptionControl */
	protected $parentCloneOptionControl = null;


	protected $specialLabels = array();

	protected $textDomain = 'ait-admin';

	protected $capabilityEnable = false;
	protected $capabilityName = "";

	/**
	 * Flag for disabling group key, sometimes we do not need it
	 * @var boolean
	 */
	public static $useGroupKeyInNameAttr = true;

	public static $useOnlySubkeyInNameAttr = false;
	protected static $helpTexts = array();


	public function __construct(AitOptionsControlsSection $parentSection, $key = '', $definition = array(), $value = '')
	{
		$this->id = AitUtils::class2id(get_class($this), 'OptionControl');

		$this->parentSection = $parentSection;
		$this->key = $key;

		if(!isset($definition['label'])) $definition['label'] = '';
		if(!isset($definition['default'])) $definition['default'] = '';

		$this->config = (object) $definition;

		$this->specialLabels = array('font' => true, 'checkbox' => true, 'radio' => true, 'on-off' => true, 'background' => true, 'clone' => true);

		$this->init();

		/* overload some option control class default values with values from config definition */

		if (isset($definition['text-domain'])) {
			$this->textDomain = $definition['text-domain'];
		}

		if (isset($definition['cloneable'])) {
			$this->isCloneable = $definition['cloneable'];
		}


		/** sets value and updates less vars if needed */
		$this->setValue($value);

		if (isset($definition['capabilities']))	{
			$this->capabilityEnable = $definition['capabilities'];
		}

		if($this->capabilityEnable){
			$this->setCapabilityName();
		}

	}



	public static function resolveClass($optionControlDefinition)
	{
		if (isset($optionControlDefinition['class']) and !empty($optionControlDefinition['class'])){
			$class = $optionControlDefinition['class'];
		} else {
			if (isset($optionControlDefinition['callback']) and !isset($optionControlDefinition['type'])) {
				$class = 'AitOptionControl';
			} else {
				$class = AitUtils::id2class($optionControlDefinition['type'], 'OptionControl');
			}
		}

		return $class;
	}




	// ============================================================
	// HTML of control
	// ------------------------------------------------------------


	/**
	 * Get full html of control, with label and help
	 * @return string Rendered HTML of control
	 */
	public function getHtml()
	{

		$hidden = '';
		if(isset($this->config->displayOnlyIf) and is_callable($this->config->displayOnlyIf)){
			$result = call_user_func($this->config->displayOnlyIf);
			if(!$result){
				$hidden = ' style="display:none;" ';
			}
		}

		ob_start();
		if ($this->capabilityEnable){

			if (current_user_can( $this->getCapabilityName() )){

				?>
				<div class="ait-opt-container ait-opt-<?php echo $this->id ?>-main" <?php echo $hidden ?>>
					<div class="ait-opt-wrap">

					<?php
						if(isset($this->config->callback)){
							call_user_func($this->config->callback, $this);
						}else{
							$this->control();
						}
					?>

					</div>
				</div>
				<?php
			}/* else {
				_e('You dont have permission to edit this option', 'ait-admin');
			}*/

		} else {

		?>
		<div class="ait-opt-container ait-opt-<?php echo $this->id ?>-main" <?php echo $hidden ?>>
			<div class="ait-opt-wrap">

			<?php
				if(isset($this->config->callback)){
					call_user_func($this->config->callback, $this);
				}else{
					$this->control();
				}
			?>

			</div>
		</div>
		<?php

		}

		return ob_get_clean();
	}



	/**
	 * Init, something like 2nd constructor
	 * @return void
	 */
	protected function init()
	{

	}


	/**
	 * Renders label for control as <label> or <span>
	 * @return void Echoes HTML
	 */
	protected function label($subKey = '')
	{
		$labelText = $this->getLabelText();

		if($labelText){
			if(isset($this->specialLabels[$this->id])){
				?>
				<span class="ait-label"><?php echo $labelText ?></span>
				<?php
			}else{
				?>
				<label class="ait-label" for="<?php echo $this->getIdAttr($subKey) ?>"><?php echo $labelText; ?></label>
				<?php
			}
		}
	}


	protected function getLabelText()
	{
		$labelText = '';
		if(isset($this->config->label) and !empty($this->config->label)){

			$l = $this->config->label;
			$labelText = '';

			$esc_html__ = 'esc_html__';
			$esc_html_x = 'esc_html_x';

			if(is_string($l)){
				$labelText = $esc_html__($l, $this->textDomain);
			}elseif($l instanceof NNeonEntity){
				if($l->value == '_x' and !empty($l->attributes)){
					$text = $l->attributes[0];
					$context = $l->attributes[1];
					$labelText = $esc_html_x($text, $context, $this->textDomain);
				}
			}
		}

		return $labelText;
	}



	/**
	 * Renders wrappers HTML for label
	 * @return void Echoes HTML
	 */
	public function labelWrapper($subKey = '', $helpPosition = null)
	{

		$position = $this->helpPosition();
		$helpPosition = (isset($helpPosition) and empty($position)) ? $helpPosition : $this->helpPosition();

		ob_start();
		$this->help();
		$help = ob_get_clean();

		?>
		<div class="ait-label-wrapper">
			<?php $this->lessVarHelp() ?>
			<?php $this->label($subKey); ?>
		</div>

		<?php if((empty($helpPosition) or $helpPosition == 'label') and !empty($help)): ?>

			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>

		<?php endif; ?>

		<?php
	}



	protected function lessVarHelp()
	{
		if(!defined('AIT_SERVER')) return;

		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$groupId = $this->parentSection->getParentGroup()->getId();

		if($this->isLessVar() and $groupId != 'adminBranding' and AitConfig::isMainConfigType($configName) and $this->id != 'clone'): ?>
		<span class="ait-label-icon">
			<span class="help">
				<?php
					$vars = array_keys($this->getLessVar() ? $this->getLessVar() : array());
					foreach($vars as $var){
						echo "<code>@$var</code><br>";
					}
				?>
			</span>
		</span>
		<?php endif;
	}



	/**
	 * Renders help text for control
	 * @return void Echoes HTML
	 */
	protected function help()
	{
		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$groupId = $this->parentSection->getParentGroup()->getId();

		if(!self::$helpTexts){
			$helpTextsFile = aitPath('config', '/help-texts.php');
			if($helpTextsFile) self::$helpTexts = require $helpTextsFile;
		}

		if(
			isset($this->config->help) and
			!empty($this->config->help)
		):
			?>
			<div class="ait-help">
			<?php $_translate = '_e'; $_translate($this->config->help, $this->textDomain); ?>
			</div>
			<?php
		elseif(
			isset(self::$helpTexts[$configName][$groupId][$this->key]) and
			is_string(self::$helpTexts[$configName][$groupId][$this->key])
		):
			?>
			<div class="ait-help">
			<?php echo self::$helpTexts[$configName][$groupId][$this->key]; /* no escaping, help texts can have html, e.g. links */ ?>
			</div>
			<?php
		elseif(
			isset(self::$helpTexts[$configName][$groupId][$this->key]) and
			$this->isCloned() and
			isset(self::$helpTexts[$configName][$groupId][$this->parentCloneOptionControl->getKey()]['items'][$this->key])
		):
			?>
			<div class="ait-help">
			<?php echo self::$helpTexts[$configName][$groupId][$this->parentCloneOptionControl->getKey()]['items'][$this->key]; /* no escaping, help texts can have html, e.g. links */ ?>
			</div>
			<?php
		endif;
	}



	/**
	 * Returns position of help text
	 * @return string
	 */
	protected function helpPosition()
	{
		return isset($this->config->helpPosition) ? $this->config->helpPosition : false;
	}



	/**
	 * Default HTML for control, mostly this will be overriden by code for option type
	 * @return void Echoes HTML
	 */
	protected function control()
	{
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt">
			<div class="ait-opt-wrapper">
				<input type="text" id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>" value="<?php echo esc_attr($this->getValue()); ?>">
			</div>

			<?php if($this->helpPosition() == 'inline'): ?>
				<div class="ait-opt-help">
					<?php $this->help() ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}



	// ============================================================
	// Content of Control's HTML Attributes
	// ------------------------------------------------------------



	public function getIdAttr($subKey = '')
	{
		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$groupId = $this->parentSection->getParentGroup()->getId();

		if($this->isCloned()){
			$e = "{$configName}-{$groupId}-{$this->parentCloneOptionControl->getKey()}-{%index%}-{$this->key}";
		}else{
			$e = "{$configName}-{$groupId}-{$this->key}";
		}

		if($this->parentSection->getParentGroup()->getIndex() !== null){
			$e .= "-__{$this->parentSection->getParentGroup()->getIndex()}__";
		}

		$id = sprintf("ait-opt-%s", $e);
		$id = empty($subKey) ? $id : "{$id}-{$subKey}";

		if($this->isCloned()){
			$id = str_replace('-index-', '-%index%-', sanitize_key(str_replace('@', 'internal-', $id)));
		}else{
			$id = sanitize_key(str_replace('@', 'internal-', $id));
		}

		return $id;
	}


	private function setCapabilityName(){
		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$groupId = $this->parentSection->getParentGroup()->getId();

		$cloneKey = "";
		if($this->isCloned()) {
			$cloneKey = $this->parentCloneOptionControl->getKey();
		}

		$this->capabilityName = $groupId.$cloneKey.'_'.$this->key;
	}

	public function getCapabilityName(){
		return $this->capabilityName;
	}


	public function getNameAttr($subKey = '')
	{
		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$groupId = $this->parentSection->getParentGroup()->getId();

		if($this->isCloned()) {
			$cloneKey = $this->parentCloneOptionControl->getKey();
		}

		if(AitConfig::isMainConfigType($configName)){

			if($this->isCloned()) {
				$e = "[$groupId][$cloneKey][%index%][$this->key]";
			} else {
				$e = "[$groupId][$this->key]";
			}

			if($this->parentSection->getParentGroup()->getIndex() !== null){
				$e = "[__{$this->parentSection->getParentGroup()->getIndex()}__]{$e}";
			}

			$name = aitOptions()->getOptionKey($configName, aitOptions()->getRequestedOid('get'));
			$name .= $subKey ? "{$e}[{$subKey}]" : $e;

		// shortcodes, metaboxes...
		}else{
			if(self::$useGroupKeyInNameAttr){
				if($this->isCloned()){
					$e = "{$groupId}[$cloneKey][%index%][$this->key]";
				}else{
					$e = "{$groupId}[{$this->key}]";
				}
			}else{
				if($this->isCloned()){
					$e = "{$cloneKey}[%index%][$this->key]";
				}else{
					$e = $this->key;
				}
			}

			if(self::$useOnlySubkeyInNameAttr and !empty($subKey)){
				$name = $subKey;
			}else{
				$name = empty($subKey) ? $e : "{$e}[{$subKey}]";
			}
		}

		return $name;
	}



	// ============================================================
	// Control HTML Attributes
	// ------------------------------------------------------------


	public function getValue($subKey = '')
	{
		$value = $this->value;

		if(!empty($subKey) and is_array($value) and isset($value[$subKey])){
			$value = $value[$subKey];
		}

		return $value;
	}



	public function getKey()
	{
		return $this->key;
	}



	public function isCloneable()
	{
		return $this->isCloneable;
	}



	public function isLessVar()
	{
		$this->isLessVar = isset($this->config->less) ? $this->config->less : $this->isLessVar;
		return $this->isLessVar;
	}



	public function isBasic()
	{
		return (isset($this->config->basic) and $this->config->basic);
	}



	public function setParentSection(AitOptionsControlsSection $optionsControlsSection)
	{
		$this->parentSection = $optionsControlsSection;
	}



	public function getParentSection()
	{
		return $this->parentSection;
	}



	public function setTextDomain($textDomain)
	{
		$this->textDomain = $textDomain;
	}



	public function setConfigDefaultValue($value)
	{
		$this->config->default = $value;
	}



	public static function prepareDefaultValue($controlDefinition)
	{
		if (isset($controlDefinition['default'])) {
			return $controlDefinition['default'];
		} else {
			return '';
		}
	}



	public function setValue($value)
	{
		$this->value = $value;
		if ($this->isLessVar()) {
			$this->updateLessVar();
		}
	}



	public function isJsVar()
	{
		return isset($this->config->jsVar) and $this->config->jsVar;
	}



	public function updateLessVar()
	{
			$prefixedKey = $this->key;

			$configName = $this->parentSection->getParentGroup()->getConfigName();
			$groupId = $this->parentSection->getParentGroup()->getId();

			if ($configName != 'elements') {
				$prefixedKey = "{$groupId}-{$prefixedKey}";

				if ($configName != '') {
					$prefixedKey = "{$configName}-{$prefixedKey}";
				}
			}
			$prefixedKey = sanitize_html_class($prefixedKey);

			if ($this->isCloned()) {
				$prefixedKey .= sanitize_html_class("-{$this->parentCloneOptionControl->getKey()}");
				$prefixedKey .= "-%index%";
			}

			$var = (object)array('name' => '', 'value' => '');

			$var->name = $prefixedKey;

			$this->lessVar = array($var->name => $this->value);

	}



	public function getLessVar()
	{
		return $this->lessVar;
	}



	public function setParentCloneOptionControl($parentCloneOptionControl)
	{
		$this->parentCloneOptionControl = $parentCloneOptionControl;
		$this->setValue($this->getValue()); // refresh less var
	}



	public function getParentCloneOptionControl()
	{
		return $this->parentCloneOptionControl;
	}



	public function isCloned()
	{
		return isset($this->parentCloneOptionControl);
	}

}
