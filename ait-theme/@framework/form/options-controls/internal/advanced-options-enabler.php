<?php


/**
 * Option type class for type: image
 */
class AitAdvancedOptionsEnablerOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->key = '@enabledAdvanced';
		$this->config = (object) array(
			'label' => __('Enable', 'ait-admin'),
			'default' => '0',
			'help' => __('Enable to override advanced options from Default Layout', 'ait-admin'),
		);
	}



	protected function control()
	{
		$val = $this->getValue();
		if(is_null($val)) $val = 0;
		?>
            <div class="ait-opt-label">
                <?php $this->labelWrapper('', 'inline') ?>
            </div>
			<div class="ait-opt ait-enable-advanced">
				<div class="ait-opt-wrapper">
				<select id="<?php echo $this->getIdAttr() ?>" name="<?php echo $this->getNameAttr() ?>" class="ait-toggle-advanced">
					<option value="1" <?php selected(1, $val) ?>><?php _e('Yes', 'ait-admin') ?></option>
					<option value="0" <?php selected(0, $val) ?>><?php _e('No', 'ait-admin') ?></option>
				</select>
				</div>
			</div>
			<div class="ait-opt-help">
				<div class="ait-opt-<?php echo $this->id ?>-add">
				</div>
				<?php
					if($this->helpPosition() != 'label') {
						$this->help();
					}
				?>
			</div>
		<?php
	}

}
