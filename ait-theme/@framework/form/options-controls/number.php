<?php


class AitNumberOptionControl extends AitOptionControl
{


	protected $units = array(
		'%',
		'em', 'ex', 'ch', 'rem',
		'vw', 'vh', 'vmin', 'vmax',
		'cm', 'mm', 'in', 'pt', 'pc', 'px',
		'deg', 'grad', 'rad', 'turn',
		's', 'ms',
		'hz', 'khz',
		'dpi', 'dpcm', 'dppx',
	);



	protected function init()
	{
		$this->isLessVar = true;
		$this->isCloneable = true;
	}



	protected function control()
	{
		$value = $this->getValue();
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper('', 'inline') ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<input <?php echo (isset($this->config->step) ? 'step=' . $this->config->step : ""); ?> type="<?php echo (empty($value) or is_numeric($value)) ? 'number' : 'text' ?>" id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>" value="<?php echo esc_attr($this->getValue()) ?>">
				<?php if($this->unit != ''): ?>
				<span class="ait-unit ait-number-unit"><?php echo esc_html($this->unit) ?></span>
				<?php endif; ?>
			</div>
		</div>

		<?php if($this->helpPosition() != 'label'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}



	public function getUnit()
	{
		return isset($this->config->unit) ? $this->config->unit : '';
	}


    public function updateLessVar()
    {
        parent::updateLessVar();

		$lessVarBaseName = key($this->lessVar); // key refers to (first and only) less var name

		$lessVar = $this->lessVar;
		$lessVarValue = $lessVar[$lessVarBaseName];

        if ($lessVarValue !== 0) {
            $unit = in_array(strtolower($this->getUnit()), $this->units) ? $this->getUnit() : '';
            $lessVar[$lessVarBaseName] = $lessVarValue . $unit; // add unit to the value
        }

        $this->lessVar = $lessVar;
    }

}
