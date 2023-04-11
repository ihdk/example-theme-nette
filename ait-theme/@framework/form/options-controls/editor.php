<?php


class AitEditorOptionControl extends AitTranslatableOptionControl
{
	private $tinyMceSettings;

	private $ajaxEditorOptions = array();



	/**
	 * Render only tinyMce instance (dynamically adds new editor to page)
	 */
	public function ajaxHtml($id, $name)
	{
		do_action('before_ajax_editor');
		$settings = $this->tinyMceSettings;
		$settings['textarea_name'] = $name;

		wp_editor($this->getValue(), $id /* TODO: use $this->id in refactored branch!! */, $settings);
		?>

		<script type="text/javascript">
			<?php if(isset($this->ajaxEditorOptions['tinyMce'])):
				$serializedTinyMceOptions = $this->serializeEditorOptionsToJs($id, $this->ajaxEditorOptions['tinyMce']);
			?>
				tinyMCEPreInit.mceInit = jQuery.extend( tinyMCEPreInit.mceInit, <?php echo $serializedTinyMceOptions ?>);
			<?php endif ?>

			<?php if(isset($this->ajaxEditorOptions['quickTags'])):
				$serializedQuickTagsOptions = $this->serializeEditorOptionsToJs($id, $this->ajaxEditorOptions['quickTags']);
			?>
				tinyMCEPreInit.qtInit = jQuery.extend( tinyMCEPreInit.qtInit, <?php echo $serializedQuickTagsOptions ?>);
			<?php endif ?>
		</script>

		<?php
		exit;
	}



	protected function init()
	{
		add_filter('tiny_mce_before_init', array($this, 'saveTinyMceOptions'), 10, 2);
		add_filter('quicktags_settings', array($this, 'saveQuickTagsOptions'), 10, 2);

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

		$this->tinyMceSettings = array_merge($defaults, $this->config->settings);
	}



	public function saveTinyMceOptions($tinyMceOptions, $editor_id)
	{
		$this->ajaxEditorOptions['tinyMce'] = $tinyMceOptions;
		return $tinyMceOptions;
	}



	public function saveQuickTagsOptions($quickTagsOptions, $editor_id)
	{
		$this->ajaxEditorOptions['quickTags'] = $quickTagsOptions;
		return $quickTagsOptions;
	}



	protected function control()
	{
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

		<?php $inPageBuilder = AitUtils::contains($this->getIdAttr(), 'elements'); ?>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">

			<?php foreach(AitLangs::getLanguagesList() as $lang): ?>

				<?php if(!AitLangs::isFilteredOut($lang)): ?>

					<?php if($inPageBuilder): ?>

						<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>" style="<?php if($inPageBuilder) echo 'display: none;'?>">
							<?php
							if(AitLangs::isEnabled()){ ?>
								<div class="flag">
									<?php echo $lang->flag; ?>
								</div> <?php
							}
							?>
							<textarea id="<?php echo $this->getLocalisedIdAttr('', $lang->locale) ?>" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" class="wp-editor-area" data-locale="<?php echo $lang->locale ?>"><?php echo esc_textarea($this->getLocalisedValue('', $lang->locale)) ?></textarea>
						</div>

					<?php else: ?>

						<div class="ait-opt-wrapper <?php echo AitLangs::htmlClass($lang->locale) ?>">
							<?php
							if(AitLangs::isEnabled()){ ?>
								<div class="flag">
									<?php echo $lang->flag; ?>
								</div> <?php
							}

							// TODO: move to constructor in refactored branch (name is set during object creation!)
							$this->tinyMceSettings['textarea_name'] = $this->getLocalisedNameAttr('', $lang->locale); // can not be overrided

							wp_editor($this->getLocalisedValue('', $lang->locale), $this->getLocalisedIdAttr('', $lang->locale), $this->tinyMceSettings);
							?>
						</div>
					<?php endif ?>
				<?php else: ?>
					<input type="hidden" name="<?php echo $this->getLocalisedNameAttr('', $lang->locale) ?>" value="<?php echo esc_attr($this->getLocalisedValue('', $lang->locale)) ?>">
				<?php endif; ?>

			<?php endforeach; ?>

			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>
	<?php
	}



	private function serializeEditorOptionsToJs($editorId, $editorOptions)
	{
		if (!empty($editorOptions)) {
			$serialized = '';

			foreach ( $editorOptions as $k => $v ) {
				if ( is_bool($v) ) {
					$val = $v ? 'true' : 'false';
					$serialized .= $k . ':' . $val . ',';
					continue;
				} elseif ( !empty($v) && is_string($v) && ( ('{' == $v[0] && '}' == $v[strlen($v) - 1]) || ('[' == $v[0] && ']' == $v[strlen($v) - 1]) || preg_match('/^\(?function ?\(/', $v) ) ) {
					$serialized .= $k . ':' . $v . ',';
					continue;
				}
				$serialized .= $k . ':"' . $v . '",';
			}

			$serialized = '{' . trim( $serialized, ' ,' ) . '}';
			$serialized = "'$editorId':{$serialized},";
			$serialized = '{' . trim($serialized, ',') . '}';
		} else {
			$serialized = '{}';
		}
		return $serialized;
	}

}
