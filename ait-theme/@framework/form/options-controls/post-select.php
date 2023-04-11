<?php
class AitPostSelectOptionControl extends AitSelectOptionControl
{

	protected $postType = 'post';



	public function __construct(AitOptionsControlsSection $parentSection, $key = '', $definition = array(), $value = '')
	{
		parent::__construct($parentSection, $key, $definition, $value);
		$this->postType = $this->config->postType;
		$this->prepareSelectValues();
	}



	public function prepareSelectValues()
	{
		$args = array('post_type' => $this->postType);
		$posts = get_posts($args);

		$this->config->default = array();
		foreach ($posts as $post) {
			$this->config->default[$post->ID] = $post->post_title;
		}
	}



	protected function control()
	{
		$args = array('post_type' => $this->postType);
		$posts = get_posts($args);

		$val = array();
		foreach ($posts as $post) {
			$val[$post->ID] = $post->post_title;
		}
		$this->setValue($val);

		//$val = $this->getValue();
		$multiAttr = $this->isMulti() ? "multiple" : '';
		$k = $this->isMulti() ? ' ' : '';
		?>


		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getNameAttr($k); ?>" id="<?php echo $this->getIdAttr(); ?>" <?php echo $multiAttr ?>>
					<?php
					foreach((array) $this->config->default as $input => $label):
						if(is_numeric($input) and is_numeric($label)) {
							$input = $label;
						}

						if(is_array($val)) {
							if ($this->isMulti()) {
								$value = in_array($input, $val) ? $input : false;
							} else {
								$value = '';
							}
						} else {
							$value = $val;
						}

						?>
						<option value="<?php echo esc_attr($input) ?>" <?php selected($value, $input) ?>><?php $eschtmle = 'esc_html_e'; $eschtmle($label, 'ait-admin') ?></option>
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
