<?php


class AitCloneOptionControl extends AitOptionControl
{
	protected $isCloneable = false;

	/** @var AitOptionsControlsSection[] */
	protected $clonedOptionsControlsSections = array();

	/** @var AitOptionsControlsSection */
	protected $cloneableOptionsControlsSectionTemplate;



	protected function init()
	{
		$this->isLessVar = true;
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		if(isset($optionControlDefinition['items']) and !empty($optionControlDefinition['items'])){
			$items = $optionControlDefinition['items'];
		}else{
			trigger_error("Clones can not be created because in the config there are no items specified under key 'items'");
			return array();
		}

		$clonesDefaultValues = array();

		foreach($optionControlDefinition['default'] as $defaultCloneDefinition){
			$clonesDefaultValue = array();
			foreach($defaultCloneDefinition as $key => $value){
				if(isset($items[$key])){
					$itemDefinition = $items[$key];

					if(isset($itemDefinition['type']) or (!isset($itemDefinition['type']) and isset($itemDefinition['callback']))){
						$optionControlClass = AitOptionControl::resolveClass($itemDefinition);
						$itemDefinition['default'] = $value;
						$defaultValue = call_user_func(array($optionControlClass, 'prepareDefaultValue'), $itemDefinition);
						$clonesDefaultValue[$key] = $defaultValue;
					}else{
						trigger_error(sprintf("Clone item %s has no 'type' key defined.", print_r($itemDefinition, true)));
					}
				}
			}
			$clonesDefaultValues[] = $clonesDefaultValue;
		}

		return $clonesDefaultValues;
	}



	public function setCloneableOptionsControlsSectionTemplate(AitOptionsControlsSection $template) {
		$this->cloneableOptionsControlsSectionTemplate = $template;
	}



	public function addClonedOptionsControlsSection(AitOptionsControlsSection $clonedOptionsControlsSection)
	{
		$this->clonedOptionsControlsSections[] = $clonedOptionsControlsSection;
	}



	protected function control()
	{
		?>

		<?php
			/* Empty Label Wrapper Check */
			$labelText = $this->getLabelText();

			ob_start();
			$this->help();
			$help = ob_get_clean();

			$emptyLabelWrapper = (empty($labelText) and empty($help)) ?: false;
		?>

		<?php if (!$emptyLabelWrapper): ?>

			<div class="ait-opt-label">
				<?php $this->labelWrapper() ?>
			</div>

		<?php endif; ?>

		<?php
		$containerId = $this->getIdAttr();
		?>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div id="<?php echo $containerId ?>" class="ait-clone-controls"
				 data-confirm-message="<?php _e('Are you sure?', 'ait-admin'); ?>"
				 data-min-forms="<?php echo $this->getMin(); ?>"
				 data-max-forms="<?php echo $this->getMax(); ?>"
				 data-allow-remove-all="<?php echo $this->getRemoveAll(); ?>"
				>

				<div id="<?php echo $containerId ?>_noforms_template" class="ait-clone-noforms">
					<?php _e('No Items Defined', 'ait-admin') ?>
					<input type="hidden" name="<?php echo $this->getNameAttr() ?>" value="">
				</div>

				<?php
				$clonedOptionsControlsSections = $this->getClonedOptionsControlsSections();

				foreach ($clonedOptionsControlsSections as $i => $clonedOptionsControlsSection) {

					$id = "{$containerId}-{$i}-pregenerated";
					$class = "ait-clone-item ait-pregenerated-clone-item";

					$firstTextInputValue = $this->getClonedOptionsControlsSectionLabel($clonedOptionsControlsSection);
					$sort = (isset($this->config->sort) and $this->config->sort === false) ? '' : 'clone-sort';
					?>
					<div id="<?php echo $id ?>" class="<?php echo $class ?>">
						<div class="form-input-handler <?php echo $sort ?>">
							<div class="form-input-title"><?php echo $firstTextInputValue ? esc_html(
									$firstTextInputValue
								) : _x('Input', 'default title for cloned form item', 'ait-admin') ?> </div>
							<a id="<?php echo $containerId ?>_remove_current" href="#"
							   class="ait-clone-remove-current">&times;</a></div>
						<div class="form-input-content" style="display: none">
							<?php
							foreach ($clonedOptionsControlsSection->getOptionsControls() as $optionControl) {
								echo $optionControl->getHtml();
							}
							?>
						</div>
					</div>
				<?php
				}

				echo $this->getCloneableOptionsControlsSectionTemplateHtml();
				?>


			</div>

			<div id="<?php echo $containerId ?>_controls" class="ait-clone-tools">
				<div id="<?php echo $containerId ?>_add" class="ait-clone-add ait-clone-control-link">
					<a href="#"><?php _e('+ Add New Item', 'ait-admin') ?></a>
				</div>
				<div id="<?php echo $containerId ?>_toggle_all" class="ait-clone-toggle-all ait-clone-control-link">
					<a href="#"><?php _e('Open/Collapse All Items', 'ait-admin') ?></a>
				</div>
				<div id="<?php echo $containerId ?>_remove_last" class="ait-clone-remove-last ait-clone-control-link">
					<a href="#"><?php _e('Remove', 'ait-admin') ?></a>
				</div>
				<div id="<?php echo $containerId ?>_remove_all" class="ait-clone-remove-all ait-clone-control-link">
					<a href="#"><?php _e('Remove All Items', 'ait-admin') ?></a>
				</div>
			</div>

		</div>
	<?php
	}



	public function getMin()
	{
		return isset($this->config->min) ? $this->config->min : 0;
	}



	public function getMax()
	{
		return (isset($this->config->max) and $this->config->max != 'infinity') ? $this->config->max : 0;
	}



	public function getRemoveAll()
	{
		return (isset($this->config->removeAll) and $this->config->removeAll === false) ? "false" : "true";
	}



	public function setClonedOptionsControlsSections($clonedOptionsControlsSections)
	{
		$this->clonedOptionsControlsSections = $clonedOptionsControlsSections;
	}



	public function getClonedOptionsControlsSections()
	{
		return $this->clonedOptionsControlsSections;
	}



	protected function getClonedOptionsControlsSectionLabel(AitOptionsControlsSection $clonedOptionsControlsSection)
	{
		$label = '';

		$lang = AitLangs::checkIfPostAndGetLang();
		$locale = $lang ? $lang->locale : AitLangs::getDefaultLocale();

		foreach($clonedOptionsControlsSection->getOptionsControls() as $optionControl){

			if($optionControl instanceof AitTextOptionControl && $optionControl->getLocalisedValue('', $locale)){
				$label = $optionControl->getLocalisedValue('', $locale);
				break;
			}else{
				if($optionControl instanceof AitStringOptionControl && $optionControl->getValue()){
					$label = $optionControl->getValue();
					break;
				}
			}
		}

		return $label;
	}



	protected function getCloneableOptionsControlsSectionTemplateHtml()
	{
		$sort = (isset($this->config->sort) and $this->config->sort === false) ? '' : 'clone-sort';
		$containerId = $this->getIdAttr();
		$templateHtml = "<div id='{$containerId}_template' class='ait-clone-item'>";
		$templateHtml .= '<div class="form-input-handler ' . $sort . '"><div class="form-input-title">' . _x(
				'Input',
				'default title for cloned form item',
				'ait-admin'
			) . '</div> <a id="' . $containerId . '_remove_current" href="#" class="ait-clone-remove-current">&times;</a></div>
						<div class="form-input-content">';

		foreach ($this->cloneableOptionsControlsSectionTemplate->getOptionsControls() as $optionControl) {
			$templateHtml .= $optionControl->getHtml();
		}

		$templateHtml .= "</div></div>";

		return $templateHtml;
	}



	public function updateLessVar()
	{
		parent::updateLessVar();

		$lessVarBaseName = key($this->lessVar);
		$lessVar = array();

		$clonedOptionsControlsSections = $this->getClonedOptionsControlsSections();

		foreach ($clonedOptionsControlsSections as $i => $clonedOptionsControlsSection) {
			foreach ($clonedOptionsControlsSection->getOptionsControls() as $optionControl) {
				if ($optionControl->isLessVar()) {
					$var = $optionControl->getLessVar();
					$cloneKey = $optionControl->getKey();
					$lessVar["{$lessVarBaseName}-{$cloneKey}-{$i}"] = reset($var);
				}
			}
		}

		$this->lessVar = $lessVar;
	}


}
