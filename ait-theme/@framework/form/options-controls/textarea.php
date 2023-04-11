<?php


class AitTextareaOptionControl extends AitTranslatableOptionControl
{


	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$rows = $cols = '';

		if($this->getRows())
			$rows = " rows='{$this->getRows()}' ";

		if($this->getCols())
			$cols = " cols='{$this->getCols()}' ";
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">

			<?php foreach(AitLangs::getLanguagesList() as $lang): ?>

				<?php if(!AitLangs::isFilteredOut($lang)): ?>

			<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">
				<?php if(AitLangs::isEnabled()): ?><div class="flag"> <?php echo $lang->flag ?></div><?php endif; ?><textarea id="<?php echo $this->getLocalisedIdAttr('', $lang->locale) ?>" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" <?php echo $rows, $cols; ?>><?php echo esc_textarea($this->getLocalisedValue('', $lang->locale)) ?></textarea>
			</div>

				<?php else: ?>
					<input type="hidden" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" value="<?php echo esc_attr($this->getLocalisedValue('', $lang->locale)); ?>">
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



	public function getRows()
	{
		return isset($this->config->rows) ? $this->config->rows : '';
	}


	public function getCols()
	{
		return isset($this->config->cols) ? $this->config->cols : '';
	}
}
