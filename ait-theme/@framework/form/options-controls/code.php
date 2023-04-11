<?php


class AitCodeOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper('', 'inline') ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<input type="text" id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" value="<?php echo esc_textarea($this->getValue()) ?>">
			</div>

			<?php
				if($this->helpPosition() != 'label') {
					$this->help();
				}
			?>
		</div>

		<?php
	}

}
