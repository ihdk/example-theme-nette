<?php


class AitInfoOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$options = (array)$this->config->default;
		if(isset($this->config->dataFunction) && !empty($this->config->dataFunction)){
			if(is_callable($this->config->dataFunction)){
				$options = call_user_func($this->config->dataFunction);
			}
		}

		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper">
				<?php echo $options; ?>
			</div>
			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>

		<?php
	}

}
