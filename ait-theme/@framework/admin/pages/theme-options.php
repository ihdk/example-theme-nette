<?php


class AitAdminThemeOptionsPage extends AitAdminPage
{


	public function render()
	{

		?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php esc_html_e('Theme Options', 'ait-admin') ?></h3>
			<div class="ait-options-header-tools">
				<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>
				<div class="ait-header-save">
					<button class="ait-save-<?php echo $this->pageSlug ?>" disabled autocomplete="off">
						<?php esc_html_e('Save Options', 'ait-admin') ?>
					</button>

					<div id="action-indicator-save" class="action-indicator action-save"></div>
				</div>
			</div>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php esc_html_e('Theme Options', 'ait-admin') ?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
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
						$this->formBegin(aitOptions()->getOptionKey('theme'));
					?>

					<div class="ait-options-controls-container">
						<div id="ait-<?php echo $this->pageSlug ?>-panels" class="ait-options-controls ait-options-panels">

							<?php
								AitOptionsControlsRenderer::create(array(
									'configType'    => 'theme',
									'adminPageSlug' => $this->pageSlug,
									'fullConfig'    => aitConfig()->getFullConfig('theme'),
									'defaults'      => aitConfig()->getDefaults('theme'),
									'options'       => aitOptions()->getOptionsByType('theme'),
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

		$t = aitConfig()->getFullConfig('theme');

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
