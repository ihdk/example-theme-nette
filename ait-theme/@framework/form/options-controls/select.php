<?php


class AitSelectOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$val = $this->getValue();
		$multiAttr = $this->multi ? "multiple" : '';
		$k = $this->multi ? ' ' : '';
		?>


		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getNameAttr($k); ?>" id="<?php echo $this->getIdAttr(); ?>" <?php echo $multiAttr ?>>
				<?php
					foreach((array) $this->config->default as $input => $label):
						if(is_numeric($input) and is_numeric($label)) {
							$input = $label;
						}

						if(is_array($val)) {
							if ($this->isMulti()) {
								$value = in_array($input, $val) ? $input : false;
							} else {
								$value = '';
							}
						} else {
							$value = $val;
						}

						?>
						<option value="<?php echo esc_attr($input) ?>" <?php selected($value, $input) ?>><?php $eschtmle = 'esc_html_e'; $eschtmle($label, 'ait-admin') ?></option>
						<?php
					endforeach;
				?>
				</select>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}



	public function isMulti()
	{
		return isset($this->config->multiple) and $this->config->multiple === true;
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		if (isset($optionControlDefinition['multiple']) and $optionControlDefinition['multiple'] === true){
			if (is_array($optionControlDefinition['default']) and count($optionControlDefinition['default']) > 1){
				return (isset($optionControlDefinition['selected']) and is_array($optionControlDefinition['selected'])) ? $optionControlDefinition['selected'] : array();
			} else{
				return array('twoormore' => '"select" type with multiple attribute can be used only with two or more options otherwise use it as basic "select"');
			}
		}

		if(isset($optionControlDefinition['selected']))
			return $optionControlDefinition['selected'];
		else
			return '';
	}

}
