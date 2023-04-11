<?php


class AitCheckboxOptionControl extends AitOptionControl
{



	protected function control()
	{
		$values = $this->getValue();
		if(!isset($values['twoormore'])){
			?>
			<div class="ait-opt-label">
				<?php $this->labelWrapper() ?>
			</div>

			<div class="ait-opt ait-opt-<?php echo $this->id ?>">
				<div class="ait-opt-wrapper">

				<?php foreach((array) $this->config->default as $input => $label){
					$value = isset($values[$input]) ? $values[$input] : false; ?>

					<label for="<?php echo $this->getIdAttr($input) ?>">
						<input type="checkbox" id="<?php echo $this->getIdAttr($input) ?>" name="<?php echo $this->getNameAttr($input); ?>" <?php checked($value, $input); ?>  value="<?php echo $input?>">
						 <?php $_translate = '_e'; $_translate($label, 'ait-admin') ?>
					</label>

				<?php } ?>

				</div>
			</div>

			<?php if($this->helpPosition() == 'inline'): ?>
				<div class="ait-opt-help">
					<?php $this->help() ?>
				</div>
			<?php endif; ?>

			<?php
		}else{
			?> <p><strong style='color:red'><?php echo $values['twoormore'] ?> </strong></p> <?php
		}
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		if(is_array($optionControlDefinition['default']) and count($optionControlDefinition['default']) > 1){
			if (isset($optionControlDefinition['checked']) and is_array($optionControlDefinition['checked'])) {
                return @array_combine($optionControlDefinition['checked'], $optionControlDefinition['checked']);
            } else {
                return array();
            }
		}else{
			return array('twoormore' => 'checkbox input type can be used only with two or more options otherwise use on-off input type');
		}
	}

}
