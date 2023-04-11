<?php


class AitTextElement extends AitElement
{

	public function getContentPreview($elementData = array())
	{
		$optionKey = 'text';
		$localisedContent = AitLangs::getCurrentLocaleText($this->option($optionKey));

		$content = AitUtils::trimHtmlContent($localisedContent, 400, '...');

		ob_start();
		?>

		<script type=<?php echo ($this->isUsed() ? "text/javascript" : "text/template")?>>
			(function(){
				var elementData = <?php echo json_encode($elementData); ?>;
				<?php echo file_get_contents(__DIR__ . '/admin/element-preview.js'); ?>
			})();
		</script>

		<?php
		$script = ob_get_clean();

		$preview = array(
			'content' => $content,
			'script' => $script
		);

		return $preview;
	}



	public function getText()
	{
		$content = $this->option('text'); // filtered by AitWpLatte::filterOptionsForCurrentLocale, returns text in given frontend locale

		$note = '';

		if(empty($content)){
			ob_start();
			?>
			<div class="alert alert-info">
				<strong><?php _ex('Text', 'name of element', 'ait') ?></strong>
				&nbsp;|&nbsp;
				<?php _e('Info: Enter some content to the textarea in the Text element, please.', 'ait'); ?>
			</div>
			<?php

			return ob_get_clean();
		}

		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);

		return $content;
	}
}
