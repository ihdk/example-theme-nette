<?php


class AitRevsliderAliasOptionControl extends AitTranslatableOptionControl
{

	protected function control()
	{
		if(!aitIsPluginActive('revslider')):
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>
		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
		<?php
			_e('Revolution Slider plugin is not active', 'ait-admin');
		?>
		</div>
		<?php

		else:

		$value = $this->getValue();

		$slider = new RevSlider();
		$sliders = $slider->getArrSliders();

		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<?php if( aitOptions()->isQueryForSpecialPage() && AitLangs::isEnabled() ) : ?>
			
			<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<?php
				foreach(AitLangs::getLanguagesList() as $lang){
					$args['lang'] = $lang->slug;
					$args['name'] = $this->getLocalisedNameAttr('', $lang->locale);
					$args['id']   = $this->getLocalisedIdAttr('', $lang->locale);
					$args['selected'] = $this->getLocalisedValue('', $lang->locale);
					
					if(!AitLangs::isFilteredOut($lang)){
						?>

							<div class="ait-opt-wrapper chosen-wrapper ait-langs-enabled <?php echo AitLangs::htmlClass($lang->locale) ?>">
								<div class="flag"><?php echo $lang->flag; ?></div>
								<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $args['name']; ?>" id="<?php echo $args['id']; ?>">
								<?php
									if(!empty($sliders)){
										foreach($sliders as $slider){
											?>
					 						<option value="<?php echo esc_attr($slider->getAlias()) ?>" <?php selected($args['selected'], $slider->getAlias()) ?>><?php echo esc_html($slider->getTitle()) ?></option>
											<?php
										}
									}else{
										?><option value=""><?php _e('None', 'ait-admin') ?></option><?php
									}
								?>
								</select>
							</div>
						
						<?php
					}else{
						?>
						<input type="hidden" name="<?php echo $args['name']; ?>" value="<?php echo $args['selected']; ?>">
						<?php
					}
				}
			?>
			</div>


		<?php else: ?>

			<div class="ait-opt ait-opt-<?php echo $this->id ?>">
				<div class="ait-opt-wrapper chosen-wrapper">
					<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getNameAttr(); ?>" id="<?php echo $this->getIdAttr(); ?>">
					<?php
						if(!empty($sliders)){
							foreach($sliders as $slider){
								?>
		 						<option value="<?php echo esc_attr($slider->getAlias()) ?>" <?php selected($value, $slider->getAlias()) ?>><?php echo esc_html($slider->getTitle()) ?></option>
								<?php
							}
						}else{
							?><option value=""><?php _e('None', 'ait-admin') ?></option><?php
						}
					?>
					</select>
				</div>
			</div>

		<?php endif; ?>

		<div class="ait-opt-help">
			<?php $this->help() ?>
		</div>
		<?php
		endif;
	}

}
