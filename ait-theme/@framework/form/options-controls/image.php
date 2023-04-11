<?php


class AitImageOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = true;
		$this->isCloneable = true;
	}



	protected function control()
	{
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<input type="text" class="ait-image-value-fake" id="<?php echo $this->getIdAttr(); ?>" value="<?php echo esc_attr($this->getValue()); ?>">
				<input type="hidden" class="ait-image-value" name="<?php echo $this->getNameAttr(); ?>" value="<?php echo esc_attr($this->getValue()); ?>">
                <?php if ($this->getParentSection()->getParentGroup()->getConfigName() != 'shortcodes'): ?>
				<input type="button" class="ait-image-select" <?php echo aitDataAttr('select-image', array('title' => sprintf(__('Select Image for: %s', 'ait-admin'), $this->config->label), 'buttonTitle' => __('Insert Image', 'ait-admin'))) ?> value="<?php _e('Select Image', 'ait-admin') ?>" id="<?php echo $this->getIdAttr('button') ?>">
			    <?php endif; ?>
            </div>

			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>

		<?php
	}




	public static function prepareDefaultValue($optionControlDefinition)
	{
		if(empty($optionControlDefinition['default'])) return '';

		$default = $optionControlDefinition['default'];

		if(!AitUtils::startsWith($default, '/') and !AitUtils::startsWith($default, 'http')){
			$default = "/{$default}";
		}

		if(!AitUtils::isExtUrl($default)){
			if(AitUtils::contains($default, 'admin/assets/img')){ // built in config and images for admin
				$default = aitPaths()->url->fw . $default;
			}else{
				$fullUrl = aitUrl('theme', $default);
				if($fullUrl !== false){
					$default = $fullUrl;
				}
			}
		}

		return $default;
	}


    public function updateLessVar()
    {
        parent::updateLessVar();

        $lessVarBaseName = key($this->lessVar); // key refers to (first and only) less var name

		$lessVar = $this->lessVar;

        $lessVarValue = $lessVar[$lessVarBaseName];

        if (empty($lessVarValue)) {
            $lessVar[$lessVarBaseName] = '~""';
        } else {
            $lessVar[$lessVarBaseName] = "url('" . $lessVarValue . "')";
        }

        $this->lessVar = $lessVar;
    }


}
