<?php


class AitMultilineCodeOptionControl extends AitOptionControl
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
				<textarea id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" rows="5"><?php echo esc_textarea($this->getValue()) ?></textarea>
			</div>
		</div>
		<?php
	}
}
