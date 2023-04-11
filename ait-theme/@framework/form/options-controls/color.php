<?php


class AitColorOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = true;
		$this->isCloneable = true;
	}



	protected function control()
	{
		$format = isset($this->config->opacity) ? 'rgba' : 'hex';
		$hex = AitUtils::rgba2hex($this->getValue());
		$required = (isset($this->config->required) and $this->config->required) ? '1' : '0';
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?> <?php if($format != "hex"): ?>ait-opt-opacity<?php endif; ?>">
			<div class="ait-opt-wrapper">
				<div class="ait-colorpicker ait-control-wrapper">
					<span class="ait-colorpicker-preview"><i style="background-color: <?php echo esc_attr($this->getValue()) ?>"></i></span>
					<input type="text" class="ait-colorpicker-color" data-color-format="<?php echo $format ?>" id="<?php echo $this->getIdAttr() ?>" value="<?php echo $hex->hex ?>">
					<input type="hidden" class="ait-colorpicker-required" value="<?php echo $required ?>">
					<input type="hidden" class="ait-colorpicker-storage" name="<?php echo $this->getNameAttr() ?>" value="<?php echo esc_attr($this->getValue()) ?>">
					<?php if($format != "hex"): ?>
					<input type="text" class="ait-colorpicker-opacity" value="<?php echo $hex->opacity ?>"><span class="ait-unit"> %</span>
					<?php elseif($format == "hex"): ?>
					<span class="ait-unit ait-value">100</span> <span class="ait-unit"> %</span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if($required == '1'): ?>
			<div class="ait-opt-help">
				<div class="ait-opt-required">
					<span><?php _ex('Required', 'mark for form input', 'ait-admin') ?></span>
					<?php
						if($this->helpPosition() == 'inline') {
							$this->help();
						}
					?>
				</div>
			</div>
		<?php else: ?>
			<?php if($this->helpPosition() == 'inline'): ?>
				<div class="ait-opt-help">
					<?php $this->help() ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		$opacity =  isset($optionControlDefinition['opacity']) ? floatval($optionControlDefinition['opacity']) : 1;

		if($opacity > 1){
			$opacity /= 100;
		}

		if(!isset($optionControlDefinition['default'])) $optionControlDefinition['default'] = '';
		$default = $optionControlDefinition['default'];

		if(AitUtils::startsWith($default, '#') and $opacity != 1){
			$r = 0; $g = 0; $b = 0;
			extract(AitUtils::hex2rgb($default));
			$default = sprintf("rgba(%s, %s, %s, %s)", $r, $g, $b, $opacity);
		}

		return $default;
	}
}
