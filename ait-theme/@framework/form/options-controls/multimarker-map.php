<?php


class AitMultimarkerMapOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = false;

		// wp_enqueue_script( 'multimarker-map', aitPaths()->url->admin . '/assets/js/multimarker-map.js' );
	}



	protected function control()
	{
		$d = (object) $this->config->default; // no getDefaultValue()
		$related = '';
		if (isset($this->config->related)) {
			$related = $this->config->related;
		}
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">

				<div class="ait-opt-tools ait-opt-maps-tools">
					<div class="ait-opt-tools-row">
						<div class="ait-opt-tools-cell1">

							<div class="ait-opt-maps-item ait-opt-maps-address">
								<label for="<?php echo $this->getIdAttr('map-address'); ?>"><?php _e('Address' , 'ait-admin') ?></label><!--
							 --><div class="ait-control-wrapper">
									<input type="text" id="<?php echo $this->getIdAttr('map-address') ?>" name="<?php echo $this->getNameAttr('address') ?>" value="<?php echo $this->getValue('address') ?>">
									<input type="button" value="<?php _e('Find' , 'ait-admin') ?>" id="find-address">
									<!--<input type="button" id="reset-markers" value="<?php _e('Clear map' , 'ait-admin') ?>">-->
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-related">
								<div class="ait-control-wrapper">
									<input type="hidden" id="<?php echo $this->getIdAttr('related') ?>" name="<?php echo $this->getNameAttr('related') ?>" value="<?php echo $related ?>">
								</div>
							</div>

							<div class="ait-opt-maps-item ait-opt-maps-markers">
								<div class="ait-control-wrapper">
									<?php $val = is_array($this->getValue('markers')) ? 0 : $this->getValue('markers');?>
									<input type="hidden" id="<?php echo $this->getIdAttr('map-markers') ?>" name="<?php echo $this->getNameAttr('markers') ?>" value="<?php echo htmlspecialchars(($val)) ?>">
									<div id="info-window-data" style="display: none;">
										<h3></h3>
										<input id="info-window-remove" type="button" value="<?php _e('Remove', 'ait-admin') ?>">
									</div>
								</div>
							</div>

						</div>




						<div class="ait-opt-tools-cell2">
							<div class="ait-opt-maps-item ait-opt-maps-wrap">
								<!-- <div class="ait-opt-maps-screen"> -->
									<div class="ait-opt-multimaps-preview" style="height: inherit;"></div>
								<!-- </div> -->
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
			'swheading'		=> 90,
			'swpitch'		=> 5,
			'swzoom'		=> 1,
			'related'		=> "",
		);

		$d = array_merge($d, $optionControlDefinition['default']);

		return $d;
	}


}
