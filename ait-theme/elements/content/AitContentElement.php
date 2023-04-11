<?php


class AitContentElement extends AitElement
{
	// override AitElement class to enable element by default
	public function isEnabled()
	{
		if($this->config['@disabled'] === false){
			return true;
		}else{
			return false;
		}
	}



	public function getContentPreview($elementData = array())
	{
		global $post;

		if(!isset($post)) return;

		$postContent = $post->post_content;

		if(aitIsGutenbergActive() or aitHasBlocks($postContent)) return;

		$content = AitUtils::trimHtmlContent($postContent, 400, '...');

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



	public function isDisplay()
	{
		global $post;

		$return = parent::isDisplay();

		if(is_singular(array('post', 'page'))){
			if(post_password_required()){
				$return = true;
			// do not display element when content is empty, because of unwanted paddings/margins
			}elseif(isset($post) and empty($post->post_content)){
				$return = false;
			}
		}

		return $return;
	}
}
