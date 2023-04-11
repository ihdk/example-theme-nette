<?php


class AitPostContentOptionControl extends AitOptionControl
{



	protected function control()
	{
		global $post;

		if(!isset($post)) return;

		$postContent = $post->post_content;

		if(!isset($this->config->settings)){
			$this->config->settings = array();
		}

		$defaults = array(
			'media_buttons'     => true,
			'textarea_rows'     => 10,
			'remove_linebreaks' => false,
            'wpautop'           => false,
            'quicktags'         => true,
            'teeny'             => false
		);


		$settings = array_merge($defaults, $this->config->settings);

		$settings['textarea_name'] = 'specific-post[content]';

		// disable shortcodes generator button if we do not want it
		if(isset($this->config->shortcodes) and $this->config->shortcodes === false and has_filter('mce_buttons', array('AitShortcodesGenerator', 'addMceButtons'))){
			remove_filter('mce_buttons', array('AitShortcodesGenerator', 'addMceButtons'));
		}
		?>

		<?php if($this->config->label): ?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>
		<?php endif; ?>

			<?php if(aitIsGutenbergActive() or aitHasBlocks($postContent)): ?>
				<div class="ait-opt">
					<a href="<?php echo get_edit_post_link($post->ID) ?>" target="_blank" class="button button-primary"><?php _e('Edit page content &raquo;', 'ait'); ?></a>
				</div>
			<?php else: ?>
				<div class="ait-opt ait-opt-editor">
					<?php
					$lang = AitLangs::getPostLang($post->ID);
					$inPageBuilder = AitUtils::contains($this->getIdAttr(), 'elements');
					?>

					<?php if ($inPageBuilder): // we load tinyMCE dynamically in elements in page builder ?>
						<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>" style="<?php if($inPageBuilder) echo 'display: none;'?>">
							<?php
							if(AitLangs::isEnabled()){ ?>
								<div class="flag">
									<?php echo $lang->flag; ?>
								</div> <?php
							}
							?>
								<textarea id="<?php echo $this->getIdAttr() ?>" name="<?php echo $settings['textarea_name'] ?>" class="wp-editor-area" data-locale="<?php echo $lang->locale ?>"><?php echo esc_textarea($postContent); ?></textarea>
						</div>
					<?php else: ?>
						<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">
							<?php

							if(AitLangs::isEnabled()){ ?>
								<div class="flag">
									<?php echo $lang->flag; ?>
								</div> <?php
							}

							wp_editor($postContent, $this->getIdAttr(), $settings);
							?>
						</div>
					<?php endif ?>
				</div>

				<?php if($this->helpPosition() == 'inline'): ?>
					<div class="ait-opt-help">
						<?php $this->help() ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		<?php
	}

}
