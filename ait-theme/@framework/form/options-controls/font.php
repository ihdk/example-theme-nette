<?php


class AitFontOptionControl extends AitTranslatableOptionControl
{

	protected function init()
	{
		$this->isCloneable = false;
		$this->isLessVar = true;
	}



	protected function control()
	{

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">

			<?php foreach(AitLangs::getLanguagesList() as $lang): ?>

				<?php
					$fontType = $this->getLocalisedValue('', $lang->locale); // e.g: theme => 'Open Sans'
				?>

				<?php if(!AitLangs::isFilteredOut($lang)): ?>

					<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">
					<?php if(AitLangs::isEnabled()): ?> <div class="flag"><?php echo $lang->flag; ?></div> <?php endif;?>

						<?php if(!isset($this->config->choices[$fontType])): ?>

							<p><strong style='color:red'>Unknown value: <code><?php var_dump($fontType) ?></code> of key <code><?php echo $this->key ?></code></strong></p>

						<?php else: ?>

						<?php foreach((array) $this->config->choices as $type => $params): ?>


							<label for="<?php echo $this->getLocalisedIdAttr($type, $lang->locale) ?>">
								<input type="radio" id="<?php echo $this->getLocalisedIdAttr($type, $lang->locale); ?>" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale); ?>" <?php checked($fontType, $type); ?>  value="<?php echo esc_attr($type) ?>">
								<?php $_translate = '_e'; $_translate($params['label'], 'ait-admin') ?>
								&nbsp;
								<small>(<?php echo esc_html($params['font-family']) ?>)</small>
							</label>
							<br>

						<?php endforeach; endif; ?>

					</div>

				<?php else: ?>
					<input type="hidden" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" value="<?php echo $this->getLocalisedValue('', $lang->locale) ?>">
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



	public function updateLessVar()
	{
        parent::updateLessVar();

        $lessVarBaseName = key($this->lessVar); // key refers to (first and only) less var name
        $lessVar = array();

		$typeOfFont = AitLangs::getCurrentLocaleText($this->value, 'system');

		$lessVar["{$lessVarBaseName}-type"] = $typeOfFont;

		if(is_string($typeOfFont) and isset($this->config->choices[$typeOfFont])){
			$lessVar["{$lessVarBaseName}-family"] = $this->config->choices[$typeOfFont]['font-family'];
		}else{
			$lessVar["{$lessVarBaseName}-family"] = 'THEME FONT CAN NOT BE FOUND, Arial, sans-serif';
		}

		$this->lessVar = $lessVar;

	}

}
