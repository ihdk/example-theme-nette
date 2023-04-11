<?php


class AitUserSelectOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}



	protected function control()
	{
		$val = $this->getValue();
		$multiAttr = $this->multi ? "multiple" : '';
		$k = $this->multi ? ' ' : '';
		$roles = $this->config->roles ? $this->config->roles : '';

		$enableAll = true;

		if (isset($this->config->enableAll)) {
			$enableAll = $this->config->enableAll;
		}


		$users = array();
		if ( in_array('0', $roles) ) {
			//there is only one role '0' - means all roles
			$users_query = new WP_User_Query( array(
					'role' => '',
				) );
			$results = $users_query->get_results();
			if ($results) $users = array_merge($users, $results);
		}
		else {
			foreach ($roles as $key => $role) {
				$users_query = new WP_User_Query( array(
					'role' => $role,
				) );
				$results = $users_query->get_results();
				if ($results) $users = array_merge($users, $results);
			}
		}


		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="<?php echo $this->getNameAttr($k); ?>" id="<?php echo $this->getIdAttr(); ?>" <?php echo $multiAttr ?>>
				<?php
					if(is_array($val)) {
						if ($this->isMulti()) {
							$value = in_array('0', $val) ? '0' : false;
						} else {
							$value = '';
						}
					} else {
						$value = $val;
					}
				 ?>
				 	<?php if ($enableAll) : ?>
					<option value="0" <?php selected($value, '0') ?>><?php esc_html_e('All', 'ait-admin') ?></option>
				 	<?php endif ?>
				<?php
					foreach($users as $key => $user):
						if(is_array($val)) {
							if ($this->isMulti()) {
								$value = in_array($user->ID, $val) ? $user->ID : false;
							} else {
								$value = '';
							}
						} else {
							$value = $val;
						}
					?>
						<option value="<?php echo esc_attr($user->ID) ?>" <?php selected($value, $user->ID) ?>><?php echo $user->display_name ?></option>
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



	public function isMulti()
	{
		return isset($this->config->multiple) and $this->config->multiple === true;
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		if(isset($optionControlDefinition['selected']))
			return $optionControlDefinition['selected'];
		else
			return '';
	}

}

