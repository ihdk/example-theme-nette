<?php


class AitEasySliderElement extends AitElement
{
	public function getContentPreview($elementData = array())
	{
		ob_start(); ?>

		<div class="ait-element-placeholder-wrap layout-slider">
			<div class="ait-element-placeholder-image">
				<i class="fa <?php echo $this->getIcon(); ?>"></i>
			</div>
		</div>

		<?php

		return ob_get_clean();
	}
}
