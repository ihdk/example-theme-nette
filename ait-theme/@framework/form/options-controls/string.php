<?php


class AitStringOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$value = $this->getValue();
		if(is_array($value)){ // when converting from text control type to this string control type
			$value = reset($value);
		}
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<input type="text" id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" value="<?php echo esc_attr($value); ?>">
			</div>

			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>

		<?php
	}

}
