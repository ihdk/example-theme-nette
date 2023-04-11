<?php


class AitPaymentOptionControl extends AitOptionControl
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
			<?php $this->labelWrapper() ?>
		</div>

		<?php if($this->config->controller == "none" || class_exists($this->config->controller)){ ?>
		<div class="ait-opt ait-opt-<?php echo $this->id ?> ait-opt-on-off">
			<div class="ait-opt-wrapper">
				<div class="ait-opt-switch">
					<select id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>" class="ait-opt-<?php echo $this->key ?>">
						<option <?php selected($val, 1); ?>  value="1">On</option>
						<option <?php selected($val, 0); ?>  value="0">Off</option>
					</select>
				</div>
			</div>
		</div>
		<?php } else {
			_e("Not Installed", 'ait-admin');
		}
		?>

		<?php if($this->helpPosition() == 'inline'): ?>
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
