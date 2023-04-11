<?php


class AitUrlOptionControl extends AitOptionControl
{

	protected function init()
	{
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
				<input type="url" id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" value="<?php echo esc_attr($this->getValue()) ?>">
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}

}
