<?php


class AitCategoriesOptionControl extends AitOptionControl
{

	protected function control()
	{
		$val = $this->getValue();

		$multi = (isset($this->config->multiple) and $this->config->multiple) ? "multiple" : '';

		if(isset($this->config->taxonomy)){
			$taxonomy = AitUtils::addPrefix($this->config->taxonomy, 'taxonomy');
		}else{
			$taxonomy = 'category';
		}

		if($multi and is_array($val) and empty($val)){
			$val = -1; // none
		}

		add_filter('ait-langs-enabled', '__return_true');

		if(!($lang = AitLangs::checkIfPostAndGetLang())){
			$lang = null;
		}

		remove_filter('ait-langs-enabled', '__return_true');

		$multiSelected = array();
		$singleSelected = '0';

		if($multi and is_array($val)){
			$multiSelected = $val;
		}else{
			$singleSelected = $val;
		}

		if(taxonomy_exists($taxonomy)){
			$defaultArgs = array(
				'name'             => $this->getNameAttr(),
				'id'               => $this->getIdAttr(),
				'class'            => $this->id . ' chosen',
				'taxonomy'         => $taxonomy,
				'selected'         => $singleSelected,
				'@multi_selected'  => $multiSelected, // our custom arg
				'orderby'		   => "NAME",
				'hierarchical'	   => 1,
				'show_option_all'  => $multi ? false : esc_html__('All', 'ait-admin'),
				'show_option_none' => false,
				'hide_if_empty'    => true,
				'hide_empty'       => true,
				'show_count'       => true,
				'lang'             => $lang ? $lang->slug : null,
				'echo'             => false,
				'walker'           => new AitCategoryDropdownWalker,
			);

			if(!isset($this->config->args)){
				$args = $defaultArgs;
			}else{
				$args = $this->config->args;
				if(!is_array($args)){
					$args = array();
				}
				$args = array_merge($defaultArgs, $args);
				$args['echo'] = false;
			}

			$html = wp_dropdown_categories($args);

			$tax = get_taxonomy($taxonomy);

			if($this->config->label == '@native')
				$this->config->label = $tax->labels->singular_name;

			$cpt = get_post_type_object($tax->object_type[0]);

			if($multi){
				$html = str_replace("class=", $multi . " class=", $html);
				$html = str_replace("' id=", "[]' id=", $html);
			}

			$html = str_replace('class=', 'data-placeholder="' . esc_html__('- select -', 'ait-admin') . '" class=', $html);

			$at = admin_url("edit-tags.php?taxonomy={$tax->name}&amp;post_type={$cpt->name}");
			$t = "<a href='{$at}' target='_blank'>{$tax->label}</a>";
			$ap = admin_url("edit.php?post_type={$cpt->name}");
			$p = "<a href='{$ap}' target='_blank'>{$cpt->labels->name}</a>";
			$none = sprintf(__("<strong>All available %s will be displayed.</strong><br><em>(Because there are no categories in %s or all categories are empty)</em>", 'ait-admin'), $p, $t);
		?>

		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">

			<?php if(!empty($html)): ?>
			<div class="ait-opt-wrapper chosen-wrapper">
				<?php echo $html; ?>
			</div>
			<?php else: ?>
				<div class="ait-sys-message ait-sys-message-notice">
				<?php echo $none; ?>
                <input id="<?php echo $this->getIdAttr() ?>" type="hidden" disabled="disabled" /> <?php // important to satisfy label reference ?>
				</div>
			<?php endif; ?>


		</div>

		<div class="ait-opt-help">
			<div class="ait-opt-<?php echo $this->id ?>-add">
				<?php if(isset($this->config->addnew)){
					if($this->config->addnew != false){?>
						<a href="<?php echo $at ?>" target="_blank">+ <?php echo $tax->labels->add_new_item ?></a>
					<?php }
				} else {
					if(current_user_can('manage_options')){ ?>
				<a href="<?php echo $at ?>" target="_blank">+ <?php echo $tax->labels->add_new_item ?></a>
				<?php } } ?>
			</div>
			<?php
				if($this->helpPosition() == 'inline') {
					$this->help();
				}
			?>
		</div>

		<?php
		}else{
			echo "<strong style='color:red'>Taxonomy <code>{$taxonomy}</code> doesn't exist.</strong>";
		}
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		return $optionControlDefinition['default'] == '' ? '0' : $optionControlDefinition['default']; // 0 - all posts, -1 - none posts
	}

}
