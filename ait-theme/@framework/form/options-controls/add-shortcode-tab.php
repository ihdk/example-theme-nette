<?php


/**
 * New option type just for tabs shortcode
 *
 * options:
 *    tab:
 *        label: Add tab
 *        type: add-shortcode-tab
 */
class AitAddShortcodeTabOptionControl extends AitOptionControl
{

	protected function init()
	{
		// do not wrap label to <label> tag, but to <span>
		$this->specialLabels['add-shortcode-tab'] = true;
	}



	protected function control()
	{
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<?php
		$id = "ait-opt-{$this->id}-cloneable";
		?>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>" id="<?php echo $id ?>">
			<div class="ait-opt-tabs-clone" id="<?php echo $id ?>_template">
				<label for="<?php echo $this->getIdAttr('#index#-title') ?>"><?php _e('Title for the tab', 'ait-admin'); ?></label>
				<a id="<?php echo $id ?>_remove_current" href="#">&times;</a>
				<br>
				<p class="ait-opt-wrapper">
					<input type="text" class="full-width" id="<?php echo $this->getIdAttr('#index#-title') ?>" name="tab[#index#][title]" value="<?php echo esc_attr($this->getValue('title')) ?>">
				</p>
				<label for="<?php echo $this->getIdAttr('#index#-content') ?>"><?php _e('Content of the tab', 'ait-admin') ?></label><br>
				<p class="ait-opt-wrapper">
					<textarea id="<?php echo $this->getIdAttr('#index#-content') ?>" name="tab[#index#][content]"><?php echo esc_textarea($this->value['content']) ?></textarea>
				</p>
			</div>

			 <div id="<?php echo $id ?>_noforms_template"><?php _e('No tabs', 'ait-admin') ?></div>

			<p id="<?php echo $id ?>_controls">
				<input type="button" class="button button-secondary" id="<?php echo $id ?>_add" value="<?php _e('Add another tab', 'ait-admin') ?>">
			</p>
		</div>

		<?php

		$this->shortcodeBuilderJs();
	}



	protected function shortcodeBuilderJs()
	{
		?>
		<script>
		(function($){ $(function(){

			if(ait.admin.shortcodes){

				$('#<?php echo "ait-opt-{$this->id}-cloneable" ?>').sheepIt({
					separator: '',
					allowRemoveCurrent: true,
					allowAdd: true,
					minFormsCount: 1,
					iniFormsCount: 1
				});


				var builder = ait.admin.shortcodes.Builder;

				// Custom build method
				builder.onBuild['tabs'] =  function(tag, rawFormData, defaultAttrs, type){

					var content = '<br>\n';
					var tabClones = rawFormData.tab;

					delete defaultAttrs.tab;
					delete rawFormData.tab;

					var attrs = _.defaults(rawFormData, defaultAttrs);

					// Remove default attributes from the shortcode.
					_.each(defaultAttrs, function(value, key){
						if(value == attrs[key])
							delete attrs[key];
					});

					$.each(tabClones, function(i, val){
						content += wp.shortcode.string({
							tag: 'tab',
							attrs: {title: val.title},
							type: 'closed',
							content: val.content
						}) + '<br>\n';
					});

					// returs [tabs animation="1"] shortcode with content
					// of [tab] shorgcodes generated above
					return wp.shortcode.string({
						tag: tag,
						attrs: attrs,
						type: 'closed',
						content: content
					});
				};
			}

		});}(jQuery));
		</script>
		<?php
	}
}
