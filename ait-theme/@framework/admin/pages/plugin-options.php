<?php


class AitAdminPluginOptionsPage extends AitAdminPage
{
	protected $pluginCodename = '';
	protected $params = array();



	public function __construct($params)
	{
		parent::__construct($params['slug']);
		$this->params = $params;
		$this->pluginCodename = $params['pluginCodename'];
	}



	public function render()
	{

		?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php echo esc_html($this->params['menu-title']) ?></h3>
			<div class="ait-options-header-tools">
				<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>
				<div class="ait-header-save">
					<button class="ait-save-plugin-options" disabled autocomplete="off">
						<?php esc_html_e('Save Options', 'ait-admin') ?>
					</button>

					<div id="action-indicator-save" class="action-indicator action-save"></div>
				</div>
			</div>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php echo esc_html($this->params['menu-title']) ?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
			</div>
		</div>

		<div class="ait-options-page">

			<div class="ait-options-page-content">
				<div class="ait-options-sidebar">
					<div class="ait-options-sidebar-content">
						<ul id="ait-<?php echo $this->pageSlug ?>-tabs" class="ait-options-tabs">
							<?php
								$this->renderTabs();
							?>
						</ul>
					</div>
				</div>

				<div class="ait-options-content">

					<?php
						$this->formBegin(aitOptions()->getOptionKey($this->pluginCodename));
					?>
					<input type="hidden" name="pluginCodename" value="<?php echo $this->pluginCodename ?>">

					<div class="ait-options-controls-container">
						<div id="ait-<?php echo $this->pageSlug ?>-panels" class="ait-options-controls ait-options-panels">

							<?php
								AitOptionsControlsRenderer::create(array(
									'configType'    => $this->pluginCodename,
									'adminPageSlug' => $this->pageSlug,
									'fullConfig'    => aitConfig()->getFullConfig($this->pluginCodename),
									'defaults'      => aitConfig()->getDefaults($this->pluginCodename),
									'options'       => aitOptions()->getOptionsByType($this->pluginCodename),
									'isPlugin'      => true,
								))->render();
							?>

						</div>
					</div>

					<?php $this->formEnd() ?>

				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->

		</div><!-- /.ait-options-page -->
		<?php
	}



	protected function renderTabs()
	{
		$tabs = '';

		$t = aitConfig()->getFullConfig($this->pluginCodename);

		foreach($t as $groupKey => $groupData){

			$panelId = sanitize_key(sprintf("ait-%s-%s-panel", $this->pageSlug, $groupKey));
			$title = (!empty($groupData['@title'])) ? $groupData['@title'] : $groupKey;
			$_translate = '__'; // alternative hack for fn call: __($title, 'ait-admin')
			$title = $_translate($title, 'ait-admin');

			$tabs .= "<li id='{$panelId}-tab'><a href='#{$panelId}'>$title</a></li>";
		}

		echo $tabs;
	}

}
