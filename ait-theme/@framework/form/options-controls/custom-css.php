<?php


class AitCustomCssOptionControl extends AitOptionControl
{


	protected function control()
	{
		?>
		<!--
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>
		-->

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<textarea id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" rows="20" cols="80"><?php echo esc_textarea($this->getValue()) ?></textarea>
			</div>

			<?php $this->help() ?>
		</div>
		<?php
	}

}
