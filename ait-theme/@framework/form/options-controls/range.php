<?php


class AitRangeOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = true;
		$this->isCloneable = true;
	}



	protected function control()
	{
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">

				<input type="range" id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>"  min="<?php echo esc_attr($this->getMin()) ?>" max="<?php echo esc_attr($this->getMax()) ?>" step="<?php echo esc_attr($this->getStep()) ?>" data-initval="<?php echo esc_attr($this->getValue()) ?>" value="<?php echo esc_attr($this->getValue()) ?>">
				<?php if($this->getUnit() != ''): ?>
				<span class="ait-unit ait-number-unit"><?php echo esc_html($this->getUnit()) ?></span>
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



	public function getUnit(){
		return isset($this->config->unit) ? $this->config->unit : '';
	}



	public function getMax(){
		return isset($this->config->max) ? $this->config->max : 100;
	}



	public function getMin(){
		return isset($this->config->min) ? $this->config->min : 0;
	}



	public function getStep(){
		return isset($this->config->step) ? $this->config->step : 1;
	}

}
