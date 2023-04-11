<?php


class AitImageRadioOptionControl extends AitOptionControl
{


	protected function init()
	{
		$this->isCloneable = true;
	}

	protected function control()
	{
		$val = $this->getValue();
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">

		<?php if(!isset($this->config->default[$val])): ?>

				<p><strong style='color:red'>Unknown value: <code><?php var_dump($val) ?></code> of key <code><?php echo $this->key ?></code></strong></p>

		<?php else: ?>

			<?php foreach((array) $this->config->default as $input => $label): ?>
				<?php
					$checked = checked($val, $input, false);

					$image = array(
						'path' => aitPath('img', '/admin/' . $this->config->images[$input]),
						'url'  => aitUrl('img', '/admin/' . $this->config->images[$input]),
					);

					$image['class'] = $image['path'] ? 'image-present' : 'image-missing';
				?>

				<label for="<?php echo $this->getIdAttr($input) ?>" class="<?php if($checked): ?>selected-option <?php endif; echo($image['class'])?>">
					<input type="radio" id="<?php echo $this->getIdAttr($input); ?>" name="<?php echo $this->getNameAttr(); ?>" <?php echo $checked ?>  value="<?php echo esc_attr($input) ?>">
					<?php
						if($image['path']){
							$_translate = '__';
							echo '<img src="' . $image['url'] . '" alt="' . esc_attr($_translate($label, 'ait-admin')) . '">';
						}
					?>
					<span class="input-title"><?php $_translate = '_e'; $_translate($label, 'ait-admin') ?></span>
				</label>

			<?php endforeach; ?>
		<?php endif; ?>

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
        if (isset($optionControlDefinition['checked']) and is_string($optionControlDefinition['checked'])) {
            return $optionControlDefinition['checked'];
        } else {
            return array_shift(array_keys($optionControlDefinition['default']));
        }
    }

}
