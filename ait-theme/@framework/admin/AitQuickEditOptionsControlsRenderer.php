<?php


class AitQuickEditOptionsControlsRenderer extends AitOptionsControlsRenderer
{
	public function render($fullConfigOptions = array(), $defaults = array(), $options = array())
	{
		?>
		<fieldset class="ait-meta<?php if(isset($options['hidden']) && $options['hidden']): ?> hidden<?php endif ?>">
			<div class="inline-edit-col">
			<?php
			parent::render($fullConfigOptions, $defaults, $options);
			?>
            </div>
		</fieldset>
		<?php
	}
}
