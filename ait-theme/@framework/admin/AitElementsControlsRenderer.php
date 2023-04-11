<?php


class AitElementsControlsRenderer extends AitOptionsControlsRenderer
{
	/** @var  AitElement[] */
	protected $usedUnsortableElements = array();

	/** @var  AitElement[] */
	protected $usedSortableElements = array();

    /** @var  string[] */
    protected $usedElementsIds = array();

    /** @var  AitElement[] */
    protected $availableFullWidthElements = array();

    /** @var  AitElement[] */
    protected $availableColumnableElements = array();

	/** @var AitElementsManager */
	protected $em;



	/**
	 * Constructor
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		$this->em = aitManager('elements');
		$this->prepareUsedElements();

		foreach(array_values($this->options) as $element){
			$this->usedElementsIds[key($element)] = true;
		}

		$this->prepareAvailableElements();
	}



	public function renderUsedUnsortableElements()
	{
		foreach($this->options as $i => $el){
			$elIds[key($el)] = $i;
		}

		$params = array();

		foreach($this->defaults as $i => $el){
			$elId = key($el);
			if(isset($elIds[$elId]) && isset($this->usedUnsortableElements[$elIds[$elId]])) {
				$el = $this->usedUnsortableElements[$elIds[$elId]];

				$params['htmlId'] = sanitize_key(sprintf("ait-%s-element-%s-%s", $this->isRenderingDefaultLayout ? 'global' : 'local', $el->id, $elIds[$elId]));
				$params['htmlClass'] = ' ait-used-element ';
				$params['clone'] = ($el->cloneable and !$this->isRenderingDefaultLayout);

				if(!$el->isDisplay() or !apply_filters("ait-allow-render-controls-{$this->configType}-{$elId}", true, $this->oid)){
					$params['htmlClass'] .= ' ait-element-off';
				}

				$this->renderUnsortableElement($el, (object) $params);
			}
		}
	}



	public function renderUsedSortableElementsHandlers()
	{
		foreach ($this->usedSortableElements as $element) {
			if ($this->em->isElementSidebarsBoundary($element->getId())) {
				$this->renderSidebarsBoundaryElement($element);
				continue;
			}

			$this->renderElementHandler($element, 'sortable');
		}
	}



	public function renderUsedSortableElementsContents()
	{
		foreach ($this->usedSortableElements as $element) {
			if ($this->em->isElementSidebarsBoundary($element->getId())) {
				continue;
			}

			$this->renderElementContent($element);
		}
	}



	public function renderAvailableElementsHandlers()
	{
		$this->options = $this->defaults; // override current values of options with defaults
		?>

		<div class="ait-simple-tabs-content">
			<div id="ait-available-elements-droppable-to-columns" class="ait-simple-tab-content active">
				<?php
				foreach($this->availableColumnableElements as $element){
					$this->renderElementHandler($element, 'available');
				}
				?>
			</div>

			<div id="ait-available-elements-not-droppable-to-columns" class="ait-simple-tab-content">
				<?php
				foreach($this->availableFullWidthElements as $element){
					$this->renderElementHandler($element, 'available');
				}
				?>
			</div>

		</div>

		<?php if(!aitIsPluginActive('toolkit')): ?>
		<div class="alert alert-warning">
			<?php /* translators: %s - the name of a plugin */ ?>
			<span class="text"><i class="fa fa-download big"></i> <?php printf(__('These elements are available in the %s Plugin', 'ait-admin'), 'AIT Elements Toolkit')?></span>
			<a href="https://www.ait-themes.club/wordpress-plugins/ait-elements-toolkit/?utm_source=wp-admin&utm_medium=wp-admin-banner&utm_campaign=Free-Theme" target="_blank" class="ait-button positive uppercase"><?php _e('Download Plugin', 'ait-admin') ?></a>
		</div>
		<?php endif; ?>

	<?php
	}



	public function renderAvailableElementsContents()
	{
		$availableElements = array_merge($this->availableFullWidthElements, $this->availableColumnableElements);

		foreach ($availableElements as $element) {
			$this->renderElementContent($element);
		}
	}



	protected function renderElementHandler(AitElement $element, $type)
	{
		$htmlElementId = sanitize_key(sprintf("ait-%s-element-%s-__%s__", $this->isRenderingDefaultLayout ? 'global' : 'local', $element->getId(), $element->getOptionsControlsGroup()->getIndex()));
		$htmlElementContentId = $htmlElementId . '-content';
		$htmlDataClone = $element->isCloneable() && !$this->isRenderingDefaultLayout;
		$htmlClass = $element->isUsed() ? 'ait-used-element' : 'ait-available-element';
		$htmlOptExample = sanitize_key(sprintf("ait-opt-elements-%s-__opt__-__%s__", $element->getId(), $element->getOptionsControlsGroup()->getIndex()));

		if(!$element->isDisplay() or !apply_filters("ait-allow-render-controls-{$this->configType}-{$element->getId()}", true, $this->oid)) $htmlClass .= ' ait-element-off';
		if($element->isColumnable()) $htmlClass .= ' ait-element-columnable';
		if($element->option('@columns-element-index')) $htmlClass .= ' hidden in-column';
		if($element->isDisabled()) $htmlClass .= ' ait-element-disabled';
		if($element->getOptionsControlsGroup()->getIndex() == AitElement::UNDEFINED_INDEX && ((isset($this->usedElementsIds[$element->getId()]) and !$element->isCloneable() and !$this->isRenderingDefaultLayout) or (isset($this->usedElementsIds[$element->getId()]) and $this->isRenderingDefaultLayout))) {
			$htmlClass .= ' hidden';
		}

		if(AIT_THEME_PACKAGE == 'standard' and $element->isDisabled()) $htmlClass .= ' hidden';

		if($element->getId() === 'comments' and aitOptions()->isQueryForSpecialPage(array('_404', '_search', '_archive', '_wc_product', '_wc_shop'))){
			return;
		}
		?>

		<div id="<?php echo $htmlElementId ?>" class="ait-element <?php echo $htmlClass ?> <?php echo $element->getId() == 'columns' ? 'ait-element-columns no-popup' : ''; ?>"
			<?php
			echo aitDataAttr('element', array('type' => $element->getId(), 'clone' => $htmlDataClone, 'global' => $this->isRenderingDefaultLayout));
			echo aitDataAttr('element-id', $htmlElementId);
			echo aitDataAttr('element-content-id', $htmlElementContentId);
			echo aitDataAttr('columns-element-index', $element->option('@columns-element-index'));
			echo aitDataAttr('columns-element-column-index', $element->option('@columns-element-column-index'));
			?>
			>
			<div class="ait-element-handler">
				<div class="ait-element-actions">
					<!-- <a class="ait-element-help" href="#">?</a> -->
					<?php if($element->getId() != 'columns'): ?>
						<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
					<?php else: ?>
						<a class="ait-element-toggle" href="#"><i class="fa fa-caret-up"></i></a>
					<?php endif; ?>

					<?php if($element->getId() != 'content' and $element->getId() != 'comments' and $element->isSortable()): ?>
						<a class="ait-element-remove" href="#"><i class="fa fa-close"></i></a>
					<?php endif; ?>
				</div>
				<?php
				if ($element->hasOption('@element-user-description') && $element->option('@element-user-description') != '') {
					$elementUserDescription = $element->option('@element-user-description');
					$elementUserDescriptionCssClass = ' element-has-user-description';
				} else {
					$elementUserDescription = '';
					$elementUserDescriptionCssClass = '';
				}

				$styleAttr = 'style="background-color: ' . $element->getColor() . '"';
				if($type == 'available'){
					$styleAttr = 'style="color: ' . $element->getColor() . '"';
				}
				?>

				<div class="ait-element-icon ait-touch-handle" <?php echo $styleAttr ?> data-color="<?php echo $element->getColor(); ?>">
					<?php if($type == 'available'): ?>
						<div class="ait-element-background" style="background-color: <?php echo $element->getColor(); ?>;"></div>
					<?php endif; ?>
					<i class="fa <?php echo $element->getIcon(); ?>"></i>
				</div>
				<div class="ait-element-title">
                    <h4><?php $eschtmle = 'esc_html_e'; $eschtmle($element->getTitle(), 'ait-admin'); ?></h4>
					<span class="ait-element-user-description<?php echo $elementUserDescriptionCssClass; ?>" title="<?php _e('Edit element description', 'ait-admin'); ?>"><?php echo $elementUserDescription ?></span>
				</div>
			</div>

			<?php
			$p = AitLangs::checkIfPostAndGetLang();

			$elementData = array(
				'elementId'     => $htmlElementId,
				'contentId'     => $htmlElementContentId,
				'optId'         => $htmlOptExample,
				'currentLocale' => $p ? $p->locale : AitLangs::getDefaultLocale(),
			);
			?>

			<?php if($contentPreview = $element->getContentPreview($elementData)): ?>
				<div class="ait-element-preview">
					<div class="ait-element-preview-content"><?php
						echo (is_array($contentPreview) ? $contentPreview['content'] : $contentPreview);
					?></div>
					<?php echo (!empty($contentPreview['script']) ? $contentPreview['script'] : ''); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php
	}



	protected function renderElementContent(AitElement $element)
	{
		if($element->getId() === 'comments' and aitOptions()->isQueryForSpecialPage(array('_404', '_search', '_archive', '_wc_product', '_wc_shop'))){
			return;
		}
		$htmlElementId = sanitize_key(sprintf("ait-%s-element-%s-__%s__", $this->isRenderingDefaultLayout ? 'global' : 'local', $element->getId(), $element->getOptionsControlsGroup()->getIndex()));
		$htmlId = $htmlElementId . '-content';
		if ($element->getOptionsControlsGroup()->getIndex() == AitElement::UNDEFINED_INDEX) $htmlId .= '-prototype';
		?>
		<div id="<?php echo $htmlId; ?>" class="ait-element-content" <?php echo aitDataAttr('element-id', $htmlElementId); ?>>
			<div class="ait-element-wrap">
				<?php if($element->getId() != 'columns'): ?>

					<?php
						if ($element->hasOption('@element-user-description') && $element->option('@element-user-description') != '') {
							$elementUserDescription = $element->option('@element-user-description');
							$elementUserDescriptionCssClass = ' element-has-user-description';
						} else {
							$elementUserDescription = '';
							$elementUserDescriptionCssClass = '';
						}
					?>

					<h3><?php echo $element->title; ?><span class="ait-element-user-description<?php echo $elementUserDescriptionCssClass; ?>" title="<?php _e('Edit element description', 'ait-admin'); ?>"><?php echo $elementUserDescription ?></span></h3>
				<?php endif; ?>
				<div class="ait-element-controls">
					<?php
					$this->renderOptionsControlsGroup($element->getOptionsControlsGroup())
					?>
				</div>
				<?php if($element->getId() != 'columns'): ?>
					<div class="ait-element-actions">
						<button class="ait-button ait-element-close" type="button">OK</button>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php
	}



	protected function renderUnsortableElement(AitElement $el, $params)
	{
		?>
		<div
			id="<?php echo $params->htmlId ?>"
			class="ait-element <?php echo $params->htmlClass ?>"
			<?php
			echo aitDataAttr('element', array('type' => $el->id, 'clone' => $params->clone, 'global' => $this->isRenderingDefaultLayout));
			echo aitDataAttr('element-id', $params->htmlId);
			echo aitDataAttr('element-content-id', $params->htmlId . '-content');
			echo aitDataAttr('columns-element-index', $el->option('@columns-element-index'));
			echo aitDataAttr('columns-element-column-index', $el->option('@columns-element-column-index'));
			?>
		>

			<div class="ait-element-handler">
				<div class="ait-element-actions">
					<!-- <a class="ait-element-help" href="#">?</a> -->
					<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
					<?php if($el->id != 'content' and $el->id != 'comments' and $el->sortable): ?>
						<a class="ait-element-remove" href="#"><i class="fa fa-close"></i></a>
					<?php endif; ?>
				</div>
				<div class="ait-element-icon" style="background-color: <?php echo $el->getColor(); ?>;" data-color="<?php echo $el->getColor(); ?>"><i class="fa <?php echo $el->getIcon(); ?>"></i></div>
				<div class="ait-element-title">
					<h4><?php $eschtmle = 'esc_html_e'; $eschtmle($el->getTitle(), 'ait-admin'); ?></h4>
				</div>
			</div>

			<div id="<?php echo $params->htmlId;?>-content" class="ait-element-content" <?php echo aitDataAttr('element-id', $params->htmlId); ?>>

				<div class="ait-element-wrap">
					<h3><?php echo $el->title; ?></h3>
					<div class="ait-element-controls">
						<?php
						$this->renderOptionsControlsGroup($el->getOptionsControlsGroup());
						?>
					</div>
					<div class="ait-element-actions">
						<button class="ait-button ait-element-close" type="button">OK</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}



	private function renderSidebarsBoundaryElement(AitElement $el)
	{
		$index = $el->getOptionsControlsGroup()->getIndex();

		$htmlElementId = sanitize_key(sprintf("ait-%s-element-%s-__%d__", $this->isRenderingDefaultLayout ? 'global' : 'local', $el->id, $index));
		$htmlElementContentId = $htmlElementId . '-content';
		?>
		<div id="<?php echo $htmlElementId ?>" class="ait-element ait-used-element ait-sidebars-boundary no-popup ait-<?php echo $el->id ?>"
			<?php
			echo aitDataAttr('element', array('type' => $el->id, 'clone' => false, 'global' => true));
			echo aitDataAttr('element-id', $htmlElementId);
			echo aitDataAttr('element-content-id', $htmlElementContentId);
			?>
			>
			<div class="ait-element-handler">
				<div class="ait-element-title">
					<h4 class="ait-touch-handle">
						<?php printf(__('Sidebars <strong>%s</strong> here', 'ait-admin'), ($el->id == 'sidebars-boundary-start') ? __('start', 'ait-admin') : __('end', 'ait-admin')) ?>
					</h4>
				</div>
			</div>
			<div id="<?php echo $htmlElementId; ?>-content" class="ait-element-content" <?php echo aitDataAttr('element-id', $htmlElementContentId); ?>>
				<?php

				$sections = $el->getOptionsControlsGroup()->getSections();

				/** @var AitOptionsControlsSection $section */
				$section = reset($sections);
				$option = $section->getOptionControl($el->getId());

				echo $option->getHtml();
				?>
			</div>
		</div>
	<?php
	}



	private function prepareUsedElements()
	{
		$usedElements = $this->em->createElementsFromOptions($this->options, $this->oid);

		foreach ($usedElements as $index => $element) {
			if (!isset($this->em->prototypes[$element->getId()]) || $element->isDisabled()) {
				continue;
			}

			$element->setUsed(true);

			if ($element->isSortable()) {
				$this->usedSortableElements[(string)$index] = $element;
			} else {
				$this->usedUnsortableElements[(string)$index] = $element;
			}
		}
	}



	private function prepareAvailableElements()
	{
		$availableElements = $this->em->createElementsFromOptions($this->defaults, $this->oid);
		$oldLC = setlocale(LC_COLLATE, "0"); // get current lc_collate

		if(PHP_OS === 'WINNT'){
			setlocale(LC_COLLATE, null); // set system's lc_collate for Windows
		}else{
			$l = get_locale();
			setlocale(LC_COLLATE, "$l.UTF8", "$l.UTF-8"); // set lc_collate for unix systems
		}

		usort($availableElements, function($a, $b){
			return strcoll($a->getTitle(), $b->getTitle());
		});

		setlocale(LC_COLLATE, $oldLC); // restore old lc_collate

		foreach ($availableElements as $index => $el) {
			$el->getOptionsControlsGroup()->setIndex(AitElement::UNDEFINED_INDEX);

			if ($this->em->isElementSidebarsBoundary($el->getId())) {
				continue;
			}

			if ($el->isColumnable()){
				$this->availableColumnableElements[$index] = $el;
			} else {
				$this->availableFullWidthElements[$index] = $el;
			}
		}
	}


}
