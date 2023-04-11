<?php


class AitPostTitleOptionControl extends AitOptionControl
{



	protected function control()
	{
		global $post;

		if(!isset($post)) return;

		$val = $post->post_title;

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-text">
			<div class="ait-opt-wrapper">
				<input type="text" id="<?php echo $this->getIdAttr() ?>" name="specific-post[title]" value="<?php echo esc_attr($val) ?>">
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
