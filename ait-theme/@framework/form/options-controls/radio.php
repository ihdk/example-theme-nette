<?php


class AitRadioOptionControl extends AitOptionControl
{


	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$val = $this->getValue();
		$defaultChecked = isset($this->config->checked) ? $this->config->checked : '';
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">

			<?php if(!isset($this->config->default[$val])): ?>

				<p><strong style='color:red'>Unknown value: <code><?php var_dump($val) ?></code> of key <code><?php echo $this->key ?></code></strong></p>

				<?php foreach((array) $this->config->default as $input => $label): ?>

					<label for="<?php echo $this->getIdAttr($input) ?>">
						<input type="radio" id="<?php echo $this->getIdAttr($input); ?>" name="<?php echo $this->getNameAttr(); ?>" <?php checked($defaultChecked, $input); ?>  value="<?php echo esc_attr($input) ?>">
						<?php $_translate = '_e'; $_translate($label, 'ait-admin') ?>
					</label>

				<?php endforeach; ?>

			<?php else: ?>

			<?php foreach((array) $this->config->default as $input => $label): ?>

				<label for="<?php echo $this->getIdAttr($input) ?>">
					<input type="radio" id="<?php echo $this->getIdAttr($input); ?>" name="<?php echo $this->getNameAttr(); ?>" <?php checked($val, $input); ?>  value="<?php echo esc_attr($input) ?>">
					<?php $_translate = '_e'; $_translate($label, 'ait-admin') ?>
				</label>

			<?php endforeach; ?>
			<?php endif; ?>

			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		if (isset($optionControlDefinition['checked']) and is_string($optionControlDefinition['checked'])) {
            return $optionControlDefinition['checked'];
        } else {
            return array_shift(array_keys($optionControlDefinition['default']));
        }
	}

}
