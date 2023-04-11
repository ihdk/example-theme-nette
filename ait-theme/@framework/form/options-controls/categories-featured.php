<?php


class AitCategoriesFeaturedOptionControl extends AitOptionControl
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

		if(!($lang = AitLangs::checkIfPostAndGetLang())){
			$lang = AitLangs::getDefaultLang();
		}

		if(taxonomy_exists($taxonomy)){
			/*$html = wp_dropdown_categories(array(
				'name'             => $this->getNameAttr(),
				'id'               => $this->getIdAttr(),
				'class'            => $this->id . ' chosen',
				'taxonomy'         => $taxonomy,
				'selected'         => $val,
				'show_option_all'  => __('All'),
				'show_option_none' => false,
				'hide_if_empty'    => true,
				'show_count'       => true,
				'lang'             => $lang->slug,
				'echo'             => false,
				'walker'           => new AitCategoryDropdownWalker,
			));*/

			$output = array();
			$categories = get_terms($taxonomy, array('hide_empty' => true));
			$allFeatured = 0;
			foreach($categories as $category){
				$result = array(
					'category' => $category,
					'counts' => 0,
				);

				$posts_query = new WP_Query(array(
					'post_type' => 'ait-item',
					'tax_query' => array(
						array(
							'taxonomy' => $taxonomy,
							'field' => 'slug',
							'terms' => $category->slug,
						),
					),
				));

				foreach($posts_query->posts as $post){
					$meta = (object)array_shift(get_post_meta($post->ID, '_ait-item_item-data'));
					if($meta->featured == '1'){
						$allFeatured = $allFeatured + 1;
						$result['counts'] = $result['counts'] + 1;
					}
				}
				array_push($output, $result);
			}

			$html = '<select name="'.$this->getNameAttr().'" id="'.$this->getIdAttr().'" class="'.$this->id.' chosen">';
				$html .= '<option value="0">'.__('All', 'ait-admin').'&nbsp;&nbsp;('.$allFeatured.')</option>';
			foreach ($output as $out) {
				$selected = '';
				if($out['category']->term_id == $val){
					$selected = "selected";
				}
				$html .= '<option value="'.$out['category']->term_id.'" '.$selected.'>'.$out['category']->name.'&nbsp;&nbsp;('.$out['counts'].')</option>';
			}
			$html .= '</select>';


			$tax = get_taxonomy($taxonomy);

			if($this->config->label == '@native')
				$this->config->label = $tax->labels->singular_name;

			$cpt = get_post_type_object($tax->object_type[0]);

			if($multi){
				$html = str_replace("class=", $multi . " class=", $html);
				$html = str_replace("' id=", "[]' id=", $html);
			}

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
			<?php $lang = AitLangs::checkIfPostAndGetLang(); ?>
			<div class="ait-opt-wrapper chosen-wrapper <?php echo AitLangs::htmlClass($lang ? $lang->locale : '') ?>">
				<?php if(AitLangs::isEnabled()){
					if($lang){ ?>
						<div class="flag"> <?php
						echo $lang->flag;
						?> </div> <?php
					}else{
						?>
						<div class="flag"> <?php
						echo AitLangs::getDefaultLang()->flag;
						?> </div> <?php
					}
				} ?>
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
				<a href="<?php echo $at ?>" target="_blank">+ <?php echo $tax->labels->add_new_item ?></a>
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
		return $optionControlDefinition['default'] == '' ? 0 : $optionControlDefinition['default']; // 0 - all posts, -1 - none posts
	}

}
