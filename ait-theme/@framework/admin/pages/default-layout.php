<?php


class AitAdminDefaultLayoutPage extends AitAdminPagesOptionsPage
{
    public function beforeRender()
    {
        $this->pageUrl = AitUtils::adminPageUrl(array('page' => $this->pageSlug));
    }


    protected function renderHeaderTitle()
    {
        ?>
        <h3 class="ait-options-header-title"><?php _e('Default Layout <small>Default Layout Options Administration for all pages</small>', 'ait-admin') ?></h3>
        <?php
    }

    protected function renderHeaderTools()
    {
        ?>
        <div class="ait-custom-header-tools">
			<div class="ait-pagetools-toggle"><i class="fa fa-gear"></i></div>
        	<ul class="ait-pagetools">
                <li class="ait-reset-button ait-tooltip-container">
					<a href="#" class="ait-reset-options" title="<?php _e('Reset to Defaults', 'ait-admin') ?>"
                        <?php echo aitDataAttr(
                            'reset-options', array(
                                'confirm' => __('Are you sure you want to reset all settings to defaults?', 'ait-admin'),
                                'nonce' => AitUtils::nonce("reset-pages-options"),
                                'what' => 'pages-options',
                                'oid' => false
                            )
                        )
                        ?>
                    >
                        <i class="fa fa-undo"></i>
						<span class="ait-tool-title"><?php esc_html_e('Import options', 'ait-admin');?></span>
					</a>
					<div class="ait-tooltip"><?php _e('Reset to Defaults', 'ait-admin') ?></div>
					<div id="action-indicator-reset" class="action-indicator action-reset"></div>
				</li>

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



    protected function renderTitle()
    {
        _e('<strong>Default Layout</strong> Options', 'ait-admin');
    }



    protected function isIntroPage()
    {
        return false;
    }

}
