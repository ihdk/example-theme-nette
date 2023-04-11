<?php


class AitAdminPagesOptionsPage extends AitAdminPage
{
	protected $oid = '';
	protected $importFrom = NULL;
	protected $post = NULL;
	protected $pageUrl;

	const LAST_EDITED_OID = '_ait_page_builder_last_edited_oid';



	public function beforeRender()
	{
		$o = aitOptions();

		$postOid = $o->getRequestedOid('post');
		$getOid = $o->getRequestedOid('get');

		if($postOid){

			if($o->pageForLocalOptionsIsAvailable($postOid)){
				if(!$o->hasCustomLocalOptions($postOid)){
					$o->addLocalOptions($postOid);
				}
				AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $postOid));
			}else{
				delete_option(self::LAST_EDITED_OID);
				AitUtils::adminRedirect(array('page' => $this->pageSlug));
			}

		}elseif($getOid){

			if ($o->pageForLocalOptionsIsAvailable($getOid)){
				if(!$o->hasCustomLocalOptions($getOid) and isset($_GET['oidnonce']) and AitUtils::checkNonce($_GET['oidnonce'], 'oidnonce')){
					$o->addLocalOptions($getOid);
					AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $getOid));
				}elseif($o->hasCustomLocalOptions($getOid)){
					$this->oid = $getOid;
				}else{
					$first = $o->getFirstFoundLocalOptionsId();
					if($first){
						AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $first));
					}else{
						delete_option(self::LAST_EDITED_OID);
						AitUtils::adminRedirect(array('page' => $this->pageSlug));
					}
				}
			}else{
				delete_option(self::LAST_EDITED_OID);
				AitUtils::adminRedirect(array('page' => $this->pageSlug));
			}

		}elseif($oid = get_option(self::LAST_EDITED_OID)){

			if($o->pageForLocalOptionsIsAvailable($oid)){
				AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $oid));
			}else{
				if($first = $o->getFirstFoundLocalOptionsId()){
					AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $first));
				}else{
					delete_option(self::LAST_EDITED_OID);
					AitUtils::adminRedirect(array('page' => $this->pageSlug));
				}
			}
		}

		if($this->oid){
			update_option(self::LAST_EDITED_OID, $this->oid);
		}elseif($first = $o->getFirstFoundLocalOptionsId()){
			AitUtils::adminRedirect(array('page' => $this->pageSlug, 'oid' => $first));
		}

		if (isset($_GET['importFrom'])) {
			$this->importFrom = $_GET['importFrom'];
		}

		$this->pageUrl = AitUtils::adminPageUrl(array('page' => $this->pageSlug));

		$this->setupPost();

		add_action('admin_bar_menu', array(&$this, 'addViewAndEditLinksToAdminBar'), 110);
		add_thickbox();
	}



	public function render()
	{
		if($this->isIntroPage()) return $this->renderIntroPage();

		$this->isRenderingDefaultLayout = (empty($this->oid) and $this->pageSlug === 'default-layout');

		?>
		<div class="ait-options-mainmenu full-pagebuilder">
			<div class="ait-options-mainmenu-content">
				<?php
					if($this->isRenderingDefaultLayout){
						?>
				        <h3 class="ait-options-header-title has-subtitle"><?php _e('Default Layout <small>Layout for all pages</small>', 'ait-admin') ?></h3>
						<?php
					}else{
						$this->renderPagesDropdown('page-options-selection', __('Edit different page&hellip;', 'ait-admin'), $this->oid);
					}
					$this->renderHeaderTools();
				?>
			</div>
		</div><!-- /.ait-options-mainmenu -->




		<div class="ait-options-page" data-unsaved-changes-message="<?php esc_html_e("Changes have been made.", 'ait-admin'); ?>">
			<div class="hidden" id="hidden-wp-editor-wrapper">
				<?php wp_editor('','hidden-wp-editor'); // not used wp-editor to properly load all needed scripts/css for tinyMce editors on page ?>
			</div>
			<div class="ait-options-page-content">

				<?php
					/** @var AitElementsControlsRenderer $elementsControlsRenderer */
					$elementsControlsRenderer = AitElementsControlsRenderer::create(array(
						'configType' => 'elements',
						'adminPageSlug' => 'pages-options',
						'oid'           => $this->oid,
						'fullConfig'    => aitConfig()->getFullConfig('elements'),
						'defaults'      => aitConfig()->getDefaults('elements'),
						'options'       => aitOptions()->getOptionsByType('elements', isset($this->importFrom) ? $this->importFrom : $this->oid),
					), 'AitElementsControlsRenderer');
				?>


				<div class="ait-available-elements-container">
					<div id="stick-to-top">

						<div id="ait-available-elements-contents" class="hidden">
							<form action="" method="post" class="ait-available-elements-contents-form">
								<?php
								$elementsControlsRenderer->renderAvailableElementsContents();
								?>
							</form>
						</div>

						<div class="ait-available-elements-tabs ait-simple-tabs">
							<h3 class="ait-simple-tab active" data-tab-id="ait-available-elements-droppable-to-columns"><?php _e('Columnable Elements', 'ait-admin') ?></h3>
							<h3 class="ait-simple-tab" data-tab-id="ait-available-elements-not-droppable-to-columns"><?php _e('Fullwidth Elements', 'ait-admin') ?></h3>
						</div>

						<div id="ait-available-elements">
							<?php
								$elementsControlsRenderer->renderAvailableElementsHandlers();
							?>

							<a href="#" class="toggle-collapse" style="display: none;"></a>
						</div>
					</div>
				</div><!-- /.ait-available-elements-container -->

				<div class="ait-options-content">
					<!-- Elements -->

					<div id="ait-used-elements-contents" class="hidden">
						<form action="" method="post" class="ait-used-sortable-elements-contents-form">
						<?php $elementsControlsRenderer->renderUsedSortableElementsContents(); ?>
						</form>
					</div>

					<div id="ait-used-elements" class="ait-elements">

						<!-- Layout -->

						<div id="ait-layout-options" class="ait-element ait-used-element ait-layout-options" data-ait-element-content-id="ait-layout-options-content">

							<div class="ait-element-handler">
								<div class="ait-element-actions">
									<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
								</div>
								<div class="ait-element-icon" style="background-color: #dadada;"><i class="fa fa-file-text"></i></div>
								<div class="ait-element-title">
									<h4><?php esc_html_e('Layout Options', 'ait-admin'); ?></h4>
								</div>
							</div>

							<div id="ait-layout-options-content" class="ait-element-content ait-layout-options-controls-container no-tabs" data-ait-element-id="ait-layout-options">
								<div class="ait-element-wrap">
									<h3><?php esc_html_e('Layout Options', 'ait-admin'); ?></h3>
									<div class="ait-element-controls ait-layout-options-controls">

										<?php $this->formBegin(aitOptions()->getOptionsKeys(array('layout', 'elements'), $this->oid)); ?>

											<?php
												AitOptionsControlsRenderer::create(array(
													'configType'    => 'layout',
													'adminPageSlug' => 'pages-options',
													'oid'           => $this->oid,
													'fullConfig'    => aitConfig()->getFullConfig('layout'),
													'defaults'      => aitConfig()->getDefaults('layout'),
													'options'       => aitOptions()->getOptionsByType('layout', isset($this->importFrom) ? $this->importFrom : $this->oid),
												))->render();
											?>

										<?php $this->formEnd() ?>

									</div>

									<div class="ait-element-actions">
										<button class="ait-button ait-element-close" type="button">OK</button>
									</div>
								</div>
							</div>
						</div>

						<h2 class="ait-elements-group toggle-unsortables open"><?php esc_html_e('Sticked unsortable elements', 'ait-admin') ?></h2>
						<p class="ait-elements-placeholder-note"><?php esc_html_e('You can only enable or disable these elements', 'ait-admin') ?></p>
						<div id="ait-used-elements-unsortable" class="ait-used-elements ait-unsortable-elements open">
						<?php
							AitElementsControlsRenderer::create(array(
								'configType' => 'elements',
								'adminPageSlug' => 'pages-options',
								'oid'           => $this->oid,
								'fullConfig'    => aitConfig()->getFullConfig('elements'),
								'defaults'      => aitConfig()->getDefaults('elements'),
								'options'       => aitOptions()->getOptionsByType('elements', isset($this->importFrom) ? $this->importFrom : $this->oid),
							), 'AitElementsControlsRenderer')->renderUsedUnsortableElements();
						?>
						</div>

						<h2 class="ait-elements-group"><?php esc_html_e('Sortable elements', 'ait-admin') ?></h2>
						<p class="ait-elements-placeholder-note"><?php esc_html_e('Drag&Drop here elements from the right hand side', 'ait-admin') ?></p>
						<div id="ait-used-elements-sortable-wrapper">
							<form action="" method="post" class="ait-used-sortable-elements-form">
							<div id="ait-elements-with-sidebars-background"></div>

								<div id="ait-used-elements-sortable" class="ait-used-elements">
									<form action="" method="post" class="ait-used-sortable-elements-handlers-form">
										<?php
										$elementsControlsRenderer->renderUsedSortableElementsHandlers();
										?>
									</form>

								</div>
							</form>
						</div>

					</div>


				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->
		</div><!-- /.ait-options-page -->
		<?php
	}



	protected function renderIntroPage()
	{
		?>
		<div class="ait-intro-container">
			<div class="ait-intro-content">
				<h2><?php esc_html_e('One step closer to your Page Builder', 'ait-admin') ?></h2>

				<div class="ait-intro-panel">
					<div class="ait-options-mainmenu">
						<div class="ait-options-mainmenu-content">
							<?php $this->renderPagesDropdown('page-options-selection', __('Select page to edit', 'ait-admin'), $this->oid); ?>
						</div>
					</div>

					<div class="ait-intro-separator"><span><?php esc_html_e('or', 'ait-admin') ?></span></div>

					<div class="ait-intro-create">
						<a href="<?php echo admin_url('post-new.php?post_type=page') ?>" title="<?php esc_html_e('Create New Page', 'ait-admin') ?>">
							<div class="ait-intro-create-icon">
								<i class="fa fa-plus-circle"></i>
								<span class="ait-tool-title"><?php esc_html_e('Create new page', 'ait-admin') ?></span>
							</div>
						</a>
					</div>
				</div>

			</div>
		</div>
		<?php
	}



	protected function renderHeaderTools()
	{
		?>
		<div class="ait-custom-header-tools">

			<div class="ait-pagetools-toggle"><i class="fa fa-gear"></i></div>
			<ul class="ait-pagetools">
				<?php if(!$this->isIntroPage()): ?>
					<li class="ait-page-import ait-tooltip-container">
						<a href="#TB_inline?width=content&amp;height=content&amp;inlineId=ait-page-options-import-selection-popup" class="thickbox">
							<i class="fa fa-download"></i>
							<span class="ait-tool-title"><?php esc_html_e('Import options', 'ait-admin');?></span>
						</a>
						<div class="ait-tooltip"><?php esc_html_e('Import options', 'ait-admin');?></div>
						<div id="ait-page-options-import-selection-popup" style="display:none;">
							<div class="ait-controls-tabs-panel">
								<div class="ait-options-section ait-sec-title">
									<h2 class="ait-options-section-title"><?php esc_html_e('Import options', 'ait-admin');?></h2>
									<div id="ait-page-options-import-selection-content-wrapper" class="ait-opt-container">
										<div class="ait-opt-wrap">
											<div class="ait-opt-label">
												<div class="ait-label-wrapper">
													<span class="ait-label"><?php esc_html_e('Select page', 'ait-admin') ?></span>
													<div class="ait-help"><?php esc_html_e('Imports options from this page', 'ait-admin') ?></div>
												</div>
											</div>
											<div class="ait-opt">
												<?php $this->renderPagesDropdown('page-options-import-selection', esc_html__('Select a page from which to import options', 'ait-admin'), '', true); ?>
											</div>
										<button data-url="<?php echo esc_url($this->pageUrl) ?>&amp;oid=<?php echo $this->oid ?>" id="ait-import-page-options-button" class="button-primary">Import</button>
									</div>
								</div>
							</div>
						</div>
					</li>

					<?php if(aitOptions()->isNormalPageOptions($this->oid)): ?>
						<li class="ait-page-edit ait-tooltip-container">
							<a target="_blank" href="<?php global $post; echo get_edit_post_link($post->ID)?>">
								<i class="fa fa-edit"></i>
								<span class="ait-tool-title"><?php esc_html_e('Edit page', 'ait-admin') ?></span>
							</a>
							<div class="ait-tooltip"><?php esc_html_e('Edit page', 'ait-admin') ?></div>
						</li>
					<?php endif; ?>

					<li id="action-delete-local-options" class="ait-page-delete ait-tooltip-container">
						<?php
						$nonce = AitUtils::nonce('delete-local-options');
						printf('<a href="%s" data-ait-delete-local-options=\'%s\'><i class="fa fa-trash"></i><span class="ait-tool-title">%s</span></a><div class="ait-tooltip">%s</div>',
							esc_url(add_query_arg('oid', $this->oid)),
							json_encode(array('oid' => $this->oid,  'nonce' => $nonce)),
							__('Delete options', 'ait-admin'),
							__('Delete options', 'ait-admin')
						);
						?>
					</li>
				<?php endif; ?>


				<li class="ait-page-new ait-tooltip-container">
					<a href="<?php echo admin_url('post-new.php?post_type=page') ?>" title="<?php esc_html_e('Add New Page', 'ait-admin') ?>">
						<i class="fa fa-plus"></i>
						<span class="ait-tool-title"><?php esc_html_e('New page', 'ait-admin') ?></span>
					</a>
					<div class="ait-tooltip"><?php esc_html_e('New page', 'ait-admin') ?></div>
				</li>

				<?php if(!$this->isIntroPage() && aitOptions()->isNormalPageOptions($this->oid)): ?>
					<li class="ait-page-view ait-tooltip-container">
						<a target="_blank" href="<?php global $post; echo get_permalink($post->ID) ?>">
							<i class="fa fa-eye"></i>
							<span class="ait-tool-title"><?php esc_html_e('View page', 'ait-admin') ?></span>
						</a>
						<div class="ait-tooltip"><?php esc_html_e('View page', 'ait-admin') ?></div>
					</li>
				<?php endif; ?>
			</ul>

			<div class="ait-header-save">
				<button class="ait-save-pages-options" disabled autocomplete="off">
					<?php esc_html_e('Save Options', 'ait-admin') ?>
				</button>
				<div id="action-indicator-save" class="action-indicator action-save"></div>
			</div>

		</div>
	<?php
	}



	protected function getPostTitle()
	{
			global $post;
			if(isset($post))
				return $post->post_title;
		return '';
	}


	protected function getTitle()
	{
		$title = '';

		if(aitOptions()->isNormalPageOptions($this->oid)){
			$title = esc_html($this->getPostTitle());
		}else{
			$specialPages = aitOptions()->getSpecialCustomPages();
			$esc_html__ = 'esc_html__';
			if(isset($specialPages[$this->oid])){

				$title =  $esc_html__($specialPages[$this->oid]['label'], 'ait-admin');
				if(isset($specialPages[$this->oid]['sub-label']) and !empty($specialPages[$this->oid]['sub-label'])){
					$title .= " <small>(" . $esc_html__($specialPages[$this->oid]['sub-label'], 'ait-admin') . ")</small>";
				}

			}
		}

		return $title;
	}



	protected function renderPagesDropdown($name, $placeholderText = '', $selectedOid = null, $onlyListPagesWithCustomOptions = false)
	{
		$pagesDropdownId = 'ait-' . $name;
		?>
		<form action="<?php echo esc_url($this->pageUrl) ?>" method="post" class="ait-page-options-selection-form">
				<?php
				$localOptsRegister = aitOptions()->getLocalOptionsRegister();

				$pagesWithCustomLocalOptions = array_map(
					function($oid) {
						return (int) str_replace('_page_', '', $oid);
					},
					$localOptsRegister['pages']
				);
				$specialPagesWithCustomLocalOptions = $localOptsRegister['special'];

				$blogPageIndex = '';
				if (get_option('show_on_front') == 'page'){
					$blogPageIndex = get_option('page_for_posts');
					if ($blogPageIndex) {
						if(($key = array_search($blogPageIndex, $pagesWithCustomLocalOptions)) !== false) {
							unset($pagesWithCustomLocalOptions[$key]);
						}
					} else {
						if(($key = array_search('_blog', $specialPagesWithCustomLocalOptions)) !== false) {
							unset($specialPagesWithCustomLocalOptions[$key]);
						}
					}
				}

				$customTitleClass = '';
				if(aitOptions()->isNormalPageOptions($this->oid)){
					$customTitleClass = 'ait-custom-title';
				}

				?><div id="<?php echo $pagesDropdownId ?>" class="<?php  echo $pagesDropdownId . " " . $customTitleClass; ?>">
						<div id="<?php echo $pagesDropdownId ?>-select-placeholder">
							<div class="chosen-container chosen-container-single">
								<a class="chosen-single">
									<span>
										<?php esc_html_e('Loading...', 'ait-admin'); ?>
									</span>
								</a>
							</div>
						</div>
				<?php


				$pages = get_posts(array(
					'numberposts' => -1,
					'post_type'   => 'page',
				));


				// remove blog page, add '(home)' to home page name and mark parent pages as disabled instead of excluding them if they contain sub-pages that do not have local options set yet
				$disabledPagesIds = array();
				$homePageIndex = get_option('page_on_front');
				foreach ($pages as $i => $page) {
					if ($blogPageIndex && $page->ID == $blogPageIndex) {
						unset($pages[$i]);
					}
					if (isset($homePageIndex) && $page->ID == (int) $homePageIndex) {
						$page->post_title .= ' (' . esc_html__('home', 'ait-admin') . ')';
					}
					if ($page->post_status == 'trash') {
						if (count(get_pages("child_of={$page->ID}")) > 0) {
							$disabledPagesIds[] = $page->ID;
						} else {
							unset($pages[$i]);
						}
					}
				}

				$args = (object) array(
					'depth'                    => 0,
					'selected'                 => $selectedOid,
					'name'                     => 'oid',
					'id'                       => 'oid',
					'oid_prefix'               => '_page_',
					'pages_with_local_options' => $pagesWithCustomLocalOptions,
					'disabled_pages_ids'       => $disabledPagesIds,
					'only_list_pages_with_local_options' => $onlyListPagesWithCustomOptions
				);


				$specialPages = aitOptions()->getSpecialCustomPages();

				if ($onlyListPagesWithCustomOptions) {
					$specialPagesWithCustomOptions = array();
					foreach ($specialPagesWithCustomLocalOptions as $pageId) {
						if (isset($specialPages[$pageId])) {
							$specialPagesWithCustomOptions[$pageId] = $specialPages[$pageId];
						}
					}
					$specialPages = $specialPagesWithCustomOptions;
				}

				$walker = new AitPagePostDropdownWalker;

				$output = "<select class='hidden' name='{$args->name}' id='{$args->id}' data-placeholder='{$placeholderText}'>\n";
				$output .= "<option value=''></option>\n";

				$label = esc_html__('Special pages', 'ait-admin');
				$output .= "<optgroup label=\"{$label}\" data-page-type=\"special\">";
				foreach($specialPages as $id => $page){
					$label = $page['label'];
					if(isset($page['sub-label']) and !empty($page['sub-label'])){
						$label .= " ({$page['sub-label']})";
					}
					$selectedAttr = ($id == $args->selected || ($id == '_blog' && "_page_" . $blogPageIndex == $args->selected)) ? ' selected' : '';
					$output .= "<option class=\"special-page" . (in_array($id, $specialPagesWithCustomLocalOptions) ? " has-local-options" : "") . "\" value=\"" . esc_attr($id) . "\"" . $selectedAttr . ">{$label}</option>\n";
				}
				$output .= "</optgroup>";

				if (!empty($pages)){
					$label = esc_html__('Normal pages', 'ait-admin');

					$output .= "<optgroup label=\"{$label}\" data-page-type=\"standard\">";
					$output .= $walker->walk($pages, $args->depth, (array) $args);
					$output .= "</optgroup>";

				}

				$output .= "</select>\n";

				echo $output;
				?>
				</div>
		</form>
		<?php
	}



	protected function isIntroPage()
	{
		return empty($this->oid);
	}



	protected function setupPost()
	{
		if(AitUtils::startsWith($this->oid, '_page_') or $this->oid == '_blog'){

			$_page = false;

			if($this->oid == '_blog'){
				$blog = get_option('page_for_posts');
				if($blog){
					$_page = get_post($blog);
				}
			}else{
				if (AitUtils::contains($this->oid, '_page_')) {
					$id = substr($this->oid, strlen('_page_'));
					$_page = get_post($id);
				}
			}

			if($_page){
				global $post;
				$post = $_page;
				setup_postdata($post);
			}

		}
	}



	public function addViewAndEditLinksToAdminBar($wp_admin_bar)
	{
		global $post;

		if(!$post) return;

		if($post->post_type == 'page'){

			$wp_admin_bar->add_node(array(
				'id' => 'view-page',
				'title'  => esc_html__('View Page', 'ait-admin'),
				'href' => get_permalink($post->ID)
			));

			$wp_admin_bar->add_node(array(
				'id' => 'edit-page',
				'title'  => esc_html__('Edit Page', 'ait-admin'),
				'href' => get_edit_post_link($post->ID)
			));
		}
	}

}
