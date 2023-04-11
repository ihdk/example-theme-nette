<?php


class AitMapOptionControl extends AitOptionControl
{
	protected $mapProvider = 'google';

	protected function init()
	{
		$this->isLessVar = false;
		
		$this->mapProvider = $this->useOpenstreetmap() ? 'openstreetmap' : 'google';
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

				<div class="ait-opt-tools ait-opt-maps-tools" data-map-provider="<?php echo $this->mapProvider ?>">
					<div class="ait-opt-tools-row">
						<div class="ait-opt-tools-cell1">

							<div class="ait-opt-maps-item ait-opt-maps-address">
								<label for="<?php echo $this->getIdAttr('map-address'); ?>"><?php _e('Address' , 'ait-admin') ?></label><!--
							 --><div class="ait-control-wrapper">
							 		<?php
							 			$data = $this->getValue();
							 			if(is_string($data)){
							 				$val = $data;
							 			} else {
								 			if(!isset($data['address'])){
								 				$val = AitLangs::getCurrentLocaleText($data);
								 			} else {
								 				$val = $data['address'];
								 			}
							 			}
							 		?>
									<input type="text" id="<?php echo $this->getIdAttr('map-address') ?>" name="<?php echo $this->getNameAttr('address') ?>" value="<?php echo $val ?>">
									<input type="button" value="<?php _e('Find' , 'ait-admin') ?>">
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-latitude">
								<label for="<?php echo $this->getIdAttr('map-latitude'); ?>"><?php _e('Latitude' , 'ait-admin') ?></label><!--
							 --><div class="ait-control-wrapper">
									<?php $val = isset($data['latitude']) ? floatval($data['latitude']) : 1 ?>
									<input type="text" id="<?php echo $this->getIdAttr('map-latitude') ?>" name="<?php echo $this->getNameAttr('latitude') ?>" value="<?php echo $val ?>">
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-longitude">
								<label for="<?php echo $this->getIdAttr('map-longitude'); ?>"><?php _e('Longitude' , 'ait-admin') ?></label><!--
							 --><div class="ait-control-wrapper">
							 		<?php $val = isset($data['longitude']) ? floatval($data['longitude']) : 1 ?>
									<input type="text" id="<?php echo $this->getIdAttr('map-longitude') ?>" name="<?php echo $this->getNameAttr('longitude') ?>" value="<?php echo $val ?>">
								</div>
							</div>

							<?php $val = isset($data['streetview']) ? (int)$data['streetview'] : 0 ?>
							<div class="ait-opt-maps-item ait-opt-maps-streetview ait-opt-on-off">
								<label for="<?php echo $this->getIdAttr('map-streetview'); ?>"><?php _e('Streetview' , 'ait-admin') ?></label><!--
							 	--><div class="ait-control-wrapper">
									<div class="ait-opt-switch">
										<select id="<?php echo $this->getIdAttr('map-streetview'); ?>" name="<?php echo $this->getNameAttr('streetview'); ?>" class="ait-opt-<?php echo $this->key ?>">
											<option <?php selected($val, 1); ?>  value="1">On</option>
											<option <?php selected($val, 0); ?>  value="0">Off</option>
										</select>
									</div>
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-swheading">
								<div class="ait-control-wrapper">
									<?php $val = isset($data['swheading']) && is_numeric($data['swheading']) ? floatval($data['swheading']) : 0 ?>
									<input type="hidden" id="<?php echo $this->getIdAttr('map-swheading') ?>" name="<?php echo $this->getNameAttr('swheading') ?>" value="<?php echo $val ?>">
								</div>
							</div>
							<div class="ait-opt-maps-item ait-opt-maps-swpitch">
								<div class="ait-control-wrapper">
									<?php $val = isset($data['swpitch']) && is_numeric($data['swpitch']) ? floatval($data['swpitch']) : 0 ?>
									<input type="hidden" id="<?php echo $this->getIdAttr('map-swpitch') ?>" name="<?php echo $this->getNameAttr('swpitch') ?>" value="<?php echo $val ?>">
								</div>
							</div>
							<div class="ait-opt-maps-item ait-opt-maps-swzoom">
								<div class="ait-control-wrapper">
									<?php $val = isset($data['swzoom']) && is_numeric($data['swzoom']) ? floatval($data['swzoom']) : 0 ?>
									<input type="hidden" id="<?php echo $this->getIdAttr('map-swzoom') ?>" name="<?php echo $this->getNameAttr('swzoom') ?>" value="<?php echo $val ?>">
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-message" style="display: none"><?php _e("Couldn't find location. Try different address", 'ait-admin') ?></div>
							<div class="ait-opt-maps-item ait-opt-maps-message-api" style="display: none"><?php _e("API Key for google maps is missing or invalid, please follow instructions ", 'ait-admin') ?><a href="https://www.ait-themes.club/knowledge-base/google-maps-api-error/" target="_blank"><?php _e("here", 'ait-admin') ?></a></div>

						</div>

						<div class="ait-opt-tools-cell2">
							<div class="ait-opt-maps-item ait-opt-maps-wrap">
								<div class="ait-opt-maps-screen">
									<div class="ait-opt-maps-preview">&nbsp;</div>
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
			'address'    	=> '',
			'latitude'   	=> 1,
			'longitude' 	=> 1,
			'streetview'   	=> false,
			'swheading'		=> 90,
			'swpitch'		=> 5,
			'swzoom'		=> 1,
		);

		$d = array_merge($d, $optionControlDefinition['default']);

		return $d;
	}



	protected function useOpenstreetmap() {
		$theme = wp_get_theme();
		$currentTheme = $theme->parent() != false ? $theme->parent()->stylesheet : $theme->stylesheet;
		$isDirectory2 = $currentTheme == 'skeleton' ? $theme->stylesheet == 'directory2' : $currentTheme == 'directory2';

		$themeOptions = aitOptions()->get('theme');
		return ($isDirectory2 && !$themeOptions->google->mapsApiKey);
	}


}
