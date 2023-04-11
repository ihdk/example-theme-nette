<?php


class AitDateOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$langCode = AitLangs::getCurrentLanguageCode();


		if($langCode === 'br'){
			$langCode = 'pt-BR';
		}elseif($langCode === 'cn'){
			$langCode = 'zh-CN';
		}elseif($langCode === 'tw'){
			$langCode = 'zh-TW';
		}

		$dataAttr = array(
			'dateFormat' => $this->hasCustomFormat() ? $this->getFormat() : AitUtils::phpDate2jsDate($this->getFormat()),
			'timeFormat' => AitUtils::phpTime2jsTime(get_option('time_format')),
			'pickerType' => isset($this->config->picker) ? $this->config->picker : "date",
			'langCode'   => $langCode,
		);

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<div class="ait-datepicker">
					<input type="text" autocomplete="off" id="<?php echo $this->getIdAttr(); ?>" <?php echo aitDataAttr('datepicker', $dataAttr) ?> value="<?php echo esc_attr($this->getValue()); ?>">
					<input type="hidden" id="<?php echo $this->getIdAttr(); ?>-standard-format" name="<?php echo $this->getNameAttr(); ?>" value="<?php echo esc_attr($this->getValue()); ?>">
					<a href="#" class="datepicker-reset" style="position: absolute; top: 5px; right: 47px" onclick="javascript: event.preventDefault(); jQuery(this).parent().find('input[type=text], input[type=hidden]').val(''); return false;"><i class="fa fa-times"></i></a>
				</div>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}


	protected function hasCustomFormat()
	{
		return false;
	}



	protected function getFormat()
	{
		if($this->hasCustomFormat())
			$format = $this->config->format;
		else
			$format = get_option('date_format');

		return $format;
	}



	/**
	 * Default value in config has JavaScript format from jQuery UI Datepicker
	 * @see http://api.jqueryui.com/datepicker/#utility-formatDate
	 * @return string
	 */
	public static function prepareDefaultValue($optionControlDefinition)
	{
		if (isset($optionControlDefinition['default'])) {
			if ($optionControlDefinition['default'] == 'none') {
				return '';
			}
			$dt = new DateTime($optionControlDefinition['default']);
		} else {
			$dt = new DateTime();
		}
		if (isset($optionControlDefinition['format']) and !empty($optionControlDefinition['format']))
			return $dt->format(AitUtils::jsDate2phpDate($optionControlDefinition['format']));
		else
			return $dt->format(get_option('date_format'));

	}

}
