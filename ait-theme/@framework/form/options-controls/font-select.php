<?php


class AitFontSelectOptionControl extends AitTranslatableOptionControl
{

	protected function init()
	{
		$this->isCloneable = false;
		$this->isLessVar = true;
	}

	protected function control()
	{

		$fonts = AitGoogleFonts::getAll();

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">

			<?php foreach(AitLangs::getLanguagesList() as $lang): ?>

				<?php
					$fontType = $this->getLocalisedValue('', $lang->locale); // e.g: theme => 'Open Sans'
					$selectedFontType = $fontType;
					$parts = explode('@', $fontType);
					$fontType = $parts[0];
				?>

				<?php if(!AitLangs::isFilteredOut($lang)): ?>

					<div class="ait-opt-wrapper chosen-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">

					<?php if(AitLangs::isEnabled()): ?> <div class="flag"><?php echo $lang->flag; ?></div> <?php endif;?>


						<?php if(!isset($this->config->choices[$fontType])): ?>

							<p><strong style='color:red'>Unknown value: <code><?php var_dump($fontType) ?></code> of key <code><?php echo $this->key ?></code></strong></p>

						<?php else: ?>
							<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale); ?>">
							<?php foreach((array) $this->config->choices as $type => $params): ?>
								<optgroup label="<?php echo($params['label']) ?>">
									<?php if ($type != 'google'):

									?><option <?php selected($fontType, $type) ?> value="<?php echo "{$type}@{$params['font-family']}" ?>"><?php echo esc_html($params['label']); ?></option><?php

									else:
										foreach($fonts as $font):
											?><option <?php selected($selectedFontType, "google@{$font->family}") ?> value="<?php echo "google@{$font->family}" ?>" ><?php echo esc_html($font->family) ?></option><?php
										endforeach; ?>
									<?php endif; ?>
								</optgroup>

							<?php endforeach; ?>

							</select>

						<?php endif; ?>

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
		list($typeOfFont, $fontFamily) = array_pad(explode('@', $typeOfFont), 2, null);


		/*
		* This prevents error if theme migrates to new font-select type or user didn't save any font yet.
		* If so, default value from theme config neon will be used
		* next time user save settings, font-family is already included in option value string
		*/
		if ($fontFamily == "") {
			$fontFamily = $this->config->choices[$typeOfFont]['font-family'];
		}

		$lessVar["{$lessVarBaseName}-type"] = $typeOfFont;

		if(is_string($typeOfFont) and isset($this->config->choices[$typeOfFont])){
			$lessVar["{$lessVarBaseName}-family"] = $fontFamily;
		}else{
			$lessVar["{$lessVarBaseName}-family"] = 'THEME FONT CAN NOT BE FOUND, Arial, sans-serif';
		}

		$this->lessVar = $lessVar;

	}

}
