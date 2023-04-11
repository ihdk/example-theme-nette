<?php


class AitPageTemplatesOptionControl extends AitOptionControl
{

	protected function control()
	{
		global $post;

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<?php
				if(isset($post) and $post->post_type == 'page' and count(get_page_templates()) != 0){
					$template = !empty($post->page_template) ? $post->page_template : false;
					?>
					<select name="specific-post[template]" id="<?php echo $this->getIdAttr() ?>" class="chosen">
						<option value='default'><?php _e('Default Template', 'ait-admin'); ?></option>
						<?php page_template_dropdown($template); ?>
					</select>
					<?php
				}else{
					_e('"Page templates" option is available only on local options for specific page.', 'ait-admin');
				}
				?>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}


}
