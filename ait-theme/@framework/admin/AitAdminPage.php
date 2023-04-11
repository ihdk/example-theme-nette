<?php


abstract class AitAdminPage
{
	protected $pageSlug;
	protected $storage;



	public function __construct($pageSlug)
	{
		$this->pageSlug = $pageSlug;
	}



	public function beforeRender()
	{
	}



	public function render()
	{
	}



	public function renderPage()
	{
		$optionsLayoutClass = ($this->pageSlug == 'pages-options' or $this->pageSlug == 'default-layout') ? '' : ' ait-options-layout';

		?>
		<div class="wrap">
			<div id="ait-<?php echo $this->pageSlug ?>-page" class="ait-admin-page ait-<?php echo $this->pageSlug ?>-page<?php echo $optionsLayoutClass ?>">
				<div class="ait-admin-page-wrap">
					<?php /* Hack for WP notifications, all will be placed right after this h2 */ ?>
					<h2 style="display: none;"></h2>
					<?php $this->render(); ?>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Renders beginning of form
	 */
	protected function formBegin($optionsKeys)
	{
		global $post;

		$keys = implode(',', (array) $optionsKeys);

		$nonce = AitUtils::nonce($keys, true);

		?>
		<form action="#" method="post" id="ait-options-form" class="ait-options-form">
			<input type='hidden' name='options-keys' value="<?php echo $keys ?>">
			<input type="hidden" name="_ajax_nonce" value="<?php echo $nonce ?>">

			<?php if(isset($this->oid) and !empty($this->oid)): ?><input type="hidden" name="oid" value="<?php echo $this->oid ?>"><?php endif; ?>

			<?php if(isset($post)): ?>
			<input type="hidden" name="specific-post[id]" value="<?php echo $post->ID ?>">
		<?php endif; ?>
		<?php
	}



	/**
	 * Renders ending of form
	 */
	protected function formEnd()
	{
		?></form><?php
	}





}
