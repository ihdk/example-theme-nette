<?php


class AitFontAwesomeSelectOptionControl extends AitOptionControl
{
	protected function init()
	{
		$this->isCloneable = true;

	}

	protected function control()
	{
		$val = $this->getValue();
		$path = isset($this->config->category) ? "/awesome/icons-".$this->config->category.".json" : "/awesome/icons.json";
		$icons = json_decode(file_get_contents(aitPath("fonts", $path)))->icons;
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper fa-select">
				<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getNameAttr(); ?>" id="<?php echo $this->getIdAttr(); ?>">
					<option value=""><?php _e("None", "ait-admin") ?></option>
				<?php
					if (is_array($icons) && !empty($icons)) {
						usort($icons, function($a, $b) {
							return strcasecmp($a->name, $b->name);
						});
						foreach($icons as $icon):
							$iconName = "&#x".$icon->unicode." ".$icon->name;
							$iconClass = "fa-".$icon->id;
							?>
							<option value="<?php echo esc_attr($iconClass) ?>" <?php selected($val, $iconClass) ?>><?php echo($iconName) ?></option>
							<?php
						endforeach;
					}
				?>
				</select>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}

	public static function prepareDefaultValue($optionControlDefinition)
	{
		if(isset($optionControlDefinition['default']))
			return $optionControlDefinition['default'];
		else
			return '';
	}

}
