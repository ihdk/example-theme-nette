<?php


class AitTextOptionControl extends AitTranslatableOptionControl
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

			<?php foreach(AitLangs::getLanguagesList() as $lang): ?>

				<?php if(!AitLangs::isFilteredOut($lang)): ?>

			<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">
				<?php if(AitLangs::isEnabled()): ?>
					<div class="flag"><?php echo $lang->flag ?></div>
				<?php endif; ?>
				<input type="text" id="<?php echo $this->getLocalisedIdAttr('', $lang->locale) ?>" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" value="<?php echo esc_attr($this->getLocalisedValue('', $lang->locale)) ?>">
			</div>

				<?php else: ?>
					<input type="hidden" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" value="<?php echo esc_attr($this->getLocalisedValue('', $lang->locale)) ?>">
				<?php endif; ?>


			<?php endforeach; ?>

			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>
		<?php
	}


}
