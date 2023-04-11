<?php


class AitOnOffOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}


	protected function control()
	{
		$val = (int) $this->getValue();
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper('', 'inline') ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<div class="ait-opt-switch">
					<select id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>" class="ait-opt-<?php echo $this->key ?>">
						<option <?php selected($val, 1); ?>  value="1">On</option>
						<option <?php selected($val, 0); ?>  value="0">Off</option>
					</select>
				</div>
			</div>
		</div>

		<?php if($this->helpPosition() != 'label'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		return empty($optionControlDefinition['default']) ? false : $optionControlDefinition['default'];
	}

}
