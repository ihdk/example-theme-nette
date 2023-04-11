<?php


class AitBackgroundOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = true;
	}



	protected function control()
	{
		$d = (object) $this->config->default; // no getDefaultValue()
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
			<?php if(isset($d->color)): ?>
				<div class="ait-opt-bgcolorpicker">
					<div class="ait-opt-bg-item ait-opt-bg-color">
						<?php
							$format = isset($d->opacity) ? 'rgba' : 'hex';
							$hex = AitUtils::rgba2hex($this->getValue('color'));
							$required = (isset($this->config->required) and $this->config->required) ? '1' : '0';
						?>
						<label for="<?php echo $this->getIdAttr('background-color'); ?>"><?php _e('Background Color', 'ait-admin') ?></label>
						<div class="ait-colorpicker ait-control-wrapper">
							<span class="ait-colorpicker-preview"><i style="background-color: <?php echo esc_attr($this->getValue('color')); ?>"></i></span>
							<input type="text" class="ait-colorpicker-color" data-color-format="<?php echo $format ?>" id="<?php echo $this->getIdAttr('background-color'); ?>" value="<?php echo $hex->hex ?>">
							<input type="hidden" class="ait-colorpicker-storage" name="<?php echo $this->getNameAttr('color'); ?>" value="<?php echo esc_attr($this->getValue('color')); ?>">
							<input type="hidden" class="ait-colorpicker-required" value="<?php echo $required ?>">
							<?php if($format != "hex"): ?>
							<input type="text" class="ait-colorpicker-opacity" value="<?php echo $hex->opacity ?>"><span class="ait-unit"> %</span>
							<?php elseif($format == "hex"): ?>
							<span class="ait-unit ait-value">100</span> <span class="ait-unit"> %</span>
							<?php endif; ?>
						</div>
					</div>
					<?php if($required == '1'): ?>
						<div class="ait-opt-required"><span><?php _ex('Required', 'mark for form input', 'ait-admin') ?></span></div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if(isset($d->image)): ?>
				<div class="ait-opt-bg-item ait-opt-bg-image">
					<label for="<?php echo $this->getIdAttr('background-image'); ?>"><?php _e('Background Image', 'ait-admin') ?></label>
					<div class="ait-imagepicker ait-control-wrapper">
						<input type="text" class="ait-image-value-fake"  id="<?php echo $this->getIdAttr('background-image') ?>" value="<?php echo esc_attr($this->getValue('image')) ?>">
						<input type="hidden" class="ait-image-value" name="<?php echo $this->getNameAttr('image'); ?>" value="<?php echo esc_attr($this->getValue('image')) ?>">
                        <?php if ($this->getParentSection()->getParentGroup()->getConfigName() != 'shortcodes'): ?>
						<input type="button" class="ait-image-select" <?php echo aitDataAttr('select-image', array('title' => sprintf(__('Select Background Image for: %s', 'ait-admin'), $this->config->label), 'buttonTitle' => __('Insert Image', 'ait-admin'))) ?> value="<?php _e('Select Image', 'ait-admin') ?>" value="<?php _e('Select Image', 'ait-admin') ?>" id="<?php echo $this->getIdAttr('background-image-button')?>">
                        <?php endif; ?>
					</div>
				</div>
				<?php endif; ?>


				<div class="ait-opt-tools ait-opt-bg-tools">
				<div class="ait-opt-tools-row">
				<?php if(isset($d->repeat) or isset($d->position) or isset($d->scroll)): ?> <div class="ait-opt-tools-cell1"><?php endif; ?>
				<?php if(isset($d->repeat)): ?>
				<div class="ait-opt-bg-item ait-opt-bg-repeat">
					<label for="<?php echo $this->getIdAttr('background-repeat'); ?>"><?php _e('Repeat' , 'ait-admin') ?></label><!--
					--><div class="ait-control-wrapper">
						<select id="<?php echo $this->getIdAttr('background-repeat'); ?>" name="<?php echo $this->getNameAttr('repeat'); ?>">
							<?php
							$repeats = array('repeat' => __('repeat', 'ait-admin'), 'no-repeat' => __('no-repeat', 'ait-admin'), 'repeat-x' => __('repeat-x', 'ait-admin'), 'repeat-y' => __('repeat-y', 'ait-admin'));
							foreach($repeats as $r => $label):
							?>
							<option value="<?php echo $r ?>" <?php selected($this->getValue('repeat'), $r) ?>><?php echo $label ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php if(isset($d->position)): ?>
				<div class="ait-opt-bg-item ait-opt-bg-position">
					<label for="<?php echo $this->getIdAttr('background-position'); ?>"><?php _e('Position', 'ait-admin') ?></label><!--
					--><div class="ait-control-wrapper">
						<select id="<?php echo $this->getIdAttr('background-position'); ?>" name="<?php echo $this->getNameAttr('position'); ?>">
							<?php
							$positions = array('top left' => __('top left', 'ait-admin'), 'top center' => __('top center', 'ait-admin'), 'top right' => __('top right', 'ait-admin'), 'center left' => __('center left', 'ait-admin'), 'center center' => __('center center', 'ait-admin'), 'center right' => __('center right', 'ait-admin'), 'bottom left' => __('bottom left', 'ait-admin'), 'bottom center' => __('bottom center', 'ait-admin'), 'bottom right' => __('bottom right', 'ait-admin'));
							foreach($positions as $pos => $label):
							?>
							<option value="<?php echo $pos ?>" <?php selected($this->getValue('position'), $pos) ?>><?php echo $label ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php if(isset($d->scroll)): ?>
				<div class="ait-opt-bg-item ait-opt-bg-scroll">
					<label for="<?php echo $this->getIdAttr('background-scroll'); ?>"><?php _e('Scroll', 'ait-admin') ?></label><!--
					--><div class="ait-control-wrapper">
						<select id="<?php echo $this->getIdAttr('background-scroll'); ?>" name="<?php echo $this->getNameAttr('scroll'); ?>">
							<?php
							$scrolls = array('fixed' => __('fixed', 'ait-admin'), 'scroll' => __('scroll', 'ait-admin'));
							foreach($scrolls as $scroll => $label):
							?>
							<option value="<?php echo $scroll ?>" <?php selected($this->getValue('scroll'), $scroll) ?>><?php echo $label ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endif; ?>
				<?php if(isset($d->repeat) or isset($d->position) or isset($d->scroll)): ?> </div><?php endif; ?>

				<div class="ait-opt-tools-cell2">
					<div class="ait-opt-bg-item ait-opt-bg-wrap">
						<div class="ait-opt-bg-screen">
							<div class="ait-opt-bg-preview">&nbsp;</div>
						</div>
					</div>
				</div>

				</div>
				</div><!-- end of ait-opt-tools -->

			</div>

			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>
		<?php
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		$d = array(
			'color'    => '',
			'opacity'  => 1,
			'image'    => '',
			'repeat'   => '',
			'position' => '',
			'scroll'   => ''
		);

		$d = array_merge($d, $optionControlDefinition['default']);

		$opacity =  isset($d['opacity']) ? floatval($d['opacity']) : 1;
		if($opacity > 1)
			$opacity /= 100;

		if(AitUtils::startsWith($d['color'], '#') and $opacity != 1){
            $r = 0; $g = 0; $b = 0;
			extract(AitUtils::hex2rgb($d['color']));
			$rgba = sprintf("rgba(%s, %s, %s, %s)", $r, $g, $b, $opacity);
			$d['color'] = $rgba;
		}

		if(!empty($d['image']) and !AitUtils::isExtUrl($d['image'])){
			if(AitUtils::contains($d['image'], 'admin/assets/img')){ // built in config and images for admin
				$d['image'] = aitPaths()->url->fw . $d['image'];
			}else{
				$fullUrl = aitUrl('theme', $d['image']);
				if($fullUrl !== false){
					$d['image'] = $fullUrl;
				}
			}
		}

		return $d;
	}



    public function updateLessVar()
    {
        parent::updateLessVar();

        $lessVarBaseName = key($this->lessVar); // key refers to (first and only) less var name

        $lessVar = array();

        if(isset($this->config->default['color']))
            $lessVar["{$lessVarBaseName}-color"] = $this->value['color'];

        if(isset($this->config->default['image'])){
            $lessVar["{$lessVarBaseName}-image"] = !empty($this->value['image']) ? "url('" . $this->value['image'] . "')" : 'none';
        }
        if(isset($this->config->default['repeat']))
            $lessVar["{$lessVarBaseName}-repeat"] = $this->value['repeat'];

        if(isset($this->config->default['position']))
            $lessVar["{$lessVarBaseName}-position"] = $this->value['position'];

        if(isset($this->config->default['scroll']))
            $lessVar["{$lessVarBaseName}-scroll"] = $this->value['scroll'];

        $this->lessVar = $lessVar;
    }



}
