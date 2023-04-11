<?php


class AitAdminBackupPage extends AitAdminPage
{

	public function beforeRender()
	{
		add_action('admin_enqueue_scripts', function() {
			wp_enqueue_script("ait-jquery-filedownload");
		});
	}



	protected function getGroups()
	{
		return array(
			'export' => array(
				'title' => __('Export', 'ait-admin'),
				'callback'  => array($this, 'exportControl'),
			),
			'import' => array(
				'title' => __('Import', 'ait-admin'),
				'callback'  => array($this, 'importControl'),
			),
			'import-demo-content' => array(
				'title' => __('Import Demo Content', 'ait-admin'),
				'callback'  => array($this, 'importDemoContentControl'),
			),
		);
	}



	public function render()
	{
		?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php _e('Import / Export', 'ait-admin') ?></h3>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php _e('Import / Export', 'ait-admin') ?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
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
					<div class="ait-options-controls-container">
						<div id="ait-<?php echo $this->pageSlug ?>-panels" class="ait-options-controls ait-options-panels">

							<?php foreach($this->getGroups() as $groupKey => $groupValues): ?>
								<div id="<?php echo $this->getPanelId($groupKey); ?>" class="ait-options-group ait-options-panel ait-<?php echo $this->pageSlug ?>-tabs-panel">
									<div class="ait-controls-tabs-panel ait-options-basic">
										<div class="ait-options-section">
											<div class="ait-opt-container">
												<div class="ait-opt-wrap full-width">

													<?php call_user_func($groupValues['callback'], $groupKey) ?>

												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>

						</div>
					</div>

				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->
		</div><!-- /.ait-options-page -->
	<?php
	}



	protected function getPanelId($groupKey)
	{
		return sanitize_key(sprintf("ait-%s-%s-panel", $this->pageSlug, $groupKey));
	}



	protected function renderTabs()
	{
		$tabs = '';

		$t = $this->getGroups();

		foreach($t as $k => $v){
			$title = $v['title'];
			$panelId = $this->getPanelId($k);

			$tabs .= "<li id='{$panelId}-tab'><a href='#{$panelId}'>{$title}</a></li>";
		}

		echo $tabs;
	}



	public function exportControl($groupKey)
	{
		?>

		<form id="ait-<?php echo $this->pageSlug ?>-<?php echo $groupKey ?>-form" action="" method="post">
			<div class="ait-opt-container ait-opt-radio-main">
				<div class="ait-opt ait-opt-radio">
					<div class="ait-opt-wrapper">

						<?php if(apply_filters('ait-enable-old-backup-ui', false) or apply_filters('ait-enable-old-export-ui', false)): ?>

							<?php if(AitUtils::isAitServer()): ?>
								<label><input type="radio" name="what-to-export" value="demo-content"> Demo Content</label>
							<?php endif; ?>
							<div>
								<label><input type="radio" name="what-to-export" value="all" checked="checked"> <?php _ex('All', 'export', 'ait-admin') ?>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the WordPress Content, theme settings and WordPress settings', 'export', 'ait-admin') ?>
									</div>
								</div>
								</label>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="theme-options"> <?php _ex('All theme settings', 'export', 'ait-admin') ?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the theme settings (Theme Options, Default Layout and Page Builder)', 'export', 'ait-admin') ?>
									</div>
								</div>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="wp-options"> <?php _ex('WordPress settings', 'export', 'ait-admin') ?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports some WordPress settings (menu settings, sidebars, widgets)', 'export', 'ait-admin') ?>
									</div>
								</div>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="content"> <?php _ex('WordPress Content', 'export', 'ait-admin') ?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the WordPress content. All your posts, pages, comments, custom fields, taxonomies, navigation menus and custom post types.', 'export', 'ait-admin') ?>
									</div>
								</div>
							</div>
						<?php else: ?>
							<?php if(AitUtils::isAitServer()): ?><?php /* For internal AIT usage */ ?>
								<label><input type="radio" name="what-to-export" value="demo-content">Demo Content</label>
								<label><input type="radio" name="what-to-export" value="all" checked="checked">All - Full Content</label>
							<?php else: ?>
								<input type="hidden" name="what-to-export" value="all">
								<div class="alert alert-info">
									<?php _ex('Exports all the WordPress content, theme settings and some WordPress settings', 'export', 'ait-admin') ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</form>
		<div class="ait-backup-action export">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Export', 'export action button label', 'ait-admin') ?></a>
			<span class="action-indicator"></span>
		</div>
	<?php
	}



	public function importControl($groupKey)
	{
		?>
		<div class="alert alert-warning">
		<?php if(apply_filters('ait-enable-old-backup-ui', false) or apply_filters('ait-enable-old-import-ui', false)): ?>
			<?php _e('<strong>Warning!</strong> This import will delete all entries in database for selected option', 'ait-admin') ?>
		<?php else: ?>
			<?php _e('<strong>Warning!</strong> This import will delete all content, all theme settings and some WordPress settings in database.', 'ait-admin') ?>
		<?php endif; ?>
		</div>
		<form id="ait-<?php echo $this->pageSlug ?>-<?php echo $groupKey ?>-form" method="post">
		<?php if(apply_filters('ait-enable-old-backup-ui', false) or apply_filters('ait-enable-old-import-ui', false)): ?>
			<p><label><input type="radio" name="what-to-import" value="all" checked="checked"> <?php _ex('All', 'import', 'ait-admin') ?></label></p>
			<p><label><input type="radio" name="what-to-import" value="theme-options"> <?php _ex('All theme settings', 'import', 'ait-admin') ?></label></p>
			<p><label><input type="radio" name="what-to-import" value="wp-options"> <?php _ex('WordPress settings', 'import', 'ait-admin') ?></label></p>
			<p><label><input type="radio" name="what-to-import" value="content"> <?php _ex('WordPress Content', 'import', 'ait-admin') ?></label></p>
		<?php else: ?>
			<div><input type="hidden" name="what-to-import" value="all"></div>
		<?php endif; ?>

			<div class="ait-opt-container ait-opt-file-upload-main">
				<div class="ait-opt ait-opt-file-upload">
					<div class="ait-opt-wrapper">
						<label class="ait-opt-file-wrapper">
							<span class="ait-opt-file-input"><?php _ex('Choose your file', 'import', 'ait-admin') ?></span>
							<input type="file" name="import-file" accept=".ait-backup">
							<span class="ait-opt-btn"><?php _ex('Browse', 'browse file from disk button label', 'ait-admin') ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="ait-opt-container ait-opt-checkbox-main">
				<div class="ait-opt ait-opt-checkbox">
					<div class="ait-opt-wrapper">
						<label><input type="checkbox" name="import-attachments" value="1" checked="checked"> <?php _ex('Import Attachments?', 'import', 'ait-admin') ?></label>
					</div>
				</div>
			</div>
		</form>
		<div class="ait-backup-action import">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Import', 'import action button label', 'ait-admin') ?></a>
			<span class="action-indicator"></span>
			<div class="action-report"></div>
			<?php self::jsTemplates(); ?>
		</div>
	<?php
	}



	public function importDemoContentControl($groupKey)
	{
		?>
		<div class="alert alert-warning">
			<?php _e('<strong>Warning!</strong> Importing of demo content will delete all your current content in database and will replace all your images with demo images in the Media Library.', 'ait-admin') ?>
		</div>
		<form id="ait-<?php echo $this->pageSlug ?>-<?php echo $groupKey ?>-form" method="post">
			<?php if(AitUtils::isAitServer()): ?>
				<div class="ait-opt-container ait-opt-file-upload-main">
					<div class="ait-opt ait-opt-file-upload">
						<div class="ait-opt-wrapper">
							<label class="ait-opt-file-wrapper">
								<span class="ait-opt-file-input"><?php _ex('Choose your file', 'import', 'ait-admin') ?></span>
								<input type="file" name="import-file" accept=".ait-backup">
								<span class="ait-opt-btn"><?php _ex('Browse', 'browse file from disk button label', 'ait-admin') ?></span>
							</label>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</form>
		<div class="ait-backup-action import-demo-content">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Import Demo Content', 'import action button label', 'ait-admin') ?></a>
			<span class="action-indicator"></span>
			<div class="action-report"></div>
			<?php self::jsTemplates(); ?>
		</div>
	<?php
	}



	protected static function jsTemplates()
	{
		?>
		<script type="text/html" class="action-report-tpl">
			<# if(typeof failed !== 'undefined') { #>
				<div class='action-report-error alert alert-danger'>
					{{ failed }}
				</div>
			<# } #>

			<# if(typeof imports !== 'undefined') { #>
				<# _.each(imports, function(msgs, status) { #>
					<# if(_.keys(msgs).length){ #>
						<#
							var c = 'warning';
							if(status == 'ok') c = 'success';
							if(status == 'error') c = 'danger';
						#>
						<div class='action-report-{{{ status }}} alert alert-{{{ c }}}'>
							<ul>
							<# _.each(msgs, function(msg) { #>
								<li>{{ msg }}</li>
							<# }); #>
							</ul>
						</div>
					<# } #>
				<# }); #>
			<# } #>

			<# if(typeof attachments !== 'undefined') { #>
				<# _.each(attachments, function(atts, type) { #>
					<# if(atts.length){ #>
						<#
							var c = 'success';
							if(type == 'failed') c = 'danger';
						#>
						<div class='action-report-attachments-{{{ type }}} alert alert-{{{ c }}}'>
							<ul>
							<# _.each(atts, function(attachment) { #>
								<li>{{ attachment }}</li>
							<# }); #>
							</ul>
						</div>
					<# } #>
				<# }); #>
			<# } #>
		</script>
	<?php
	}


}
