<?php


class AitSidebarOptionControl extends AitOptionControl
{

	protected function control()
	{
		$val = $this->getValue();
		$sidebars = aitManager('sidebars')->getSidebars();

		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" name="<?php echo $this->getNameAttr('sidebar'); ?>" id="<?php echo $this->getIdAttr('sidebar'); ?>" class="chosen">
					<option value="none" <?php selected($val['sidebar'], 'none') ?>><?php echo esc_html(_x('None', 'sidebar', 'ait-admin')) ?></option>
				<?php
					foreach($sidebars as $sidebarId => $params):
						?>
						<option value="<?php echo esc_attr($sidebarId) ?>" <?php selected($val['sidebar'], $sidebarId) ?>><?php echo esc_html(AitLangs::getDefaultLocaleText($params['name'], 'unknown sidebar')) ?></option>
						<?php
					endforeach;
				?>
				</select>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php

	}

}
