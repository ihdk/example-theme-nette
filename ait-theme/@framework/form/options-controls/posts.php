<?php


class AitPostsOptionControl extends AitTranslatableOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}

	protected function control()
	{
		$val = $this->getValue();

		if(isset($this->config->cpt) and $this->config->cpt){
			$cptName = AitUtils::addPrefix($this->config->cpt, 'post');
		}else{
			$cptName = 'post';
		}

		$isMultilingual = (isset($this->config->multilingual) and $this->config->multilingual) ? $this->config->multilingual : false;

		if(post_type_exists($cptName)){
			$cpt = get_post_type_object($cptName);
			$args = array(
				'post_type'         => $cptName,
				'selected'          => $val,
				'show_option_none'  => __('&mdash; Select &mdash;', 'default'),
				'option_none_value' => '0',
				'posts_per_page'    => -1,
				'post_status'       => array('publish', 'draft'),
			);

			if(!$cpt->hierarchical){
				$args['hierarchical'] = false;
			}

			// only current user posts
			if(isset($this->config->showCurrentUserPosts)){
				if($this->config->showCurrentUserPosts = true){
					global $current_user;
					if(!in_array("administrator", $current_user->roles)){
						// if the user is not admin
						$excluded = array();
						$posts = new WP_Query(array(
							'post_type' => $cptName,
							'posts_per_page' => -1,
							'author__not_in' => array($current_user->ID)
						));
						foreach($posts->posts as $post){
							array_push($excluded, $post->ID);
						}
						$args['exclude'] = join(',', $excluded);
					}

				}
			}

			$argsFromConfig = (isset($this->config->args) and !empty($this->config->args)) ? $this->config->args : array();

			$args = array_merge(
				$args,
				$argsFromConfig,
				array( // can not be overrided from config
					'echo'  => false,
					'name'  => $this->getNameAttr(),
					'id'    => $this->getIdAttr(),
					'class' => $this->id . ' chosen',
				)
			);


			if( $isMultilingual && AitLangs::isEnabled() ){
				//new version of input with "multilingual" attribute used.
				if($this->config->label == '@native')
					$this->config->label = $cpt->labels->singular_name;
				?>
				<div class="ait-opt-label">
					<?php $this->labelWrapper() ?>
				</div>

				<div class="ait-opt ait-opt-<?php echo $this->id ?>">
					<?php
					foreach(AitLangs::getLanguagesList() as $lang){
						$args['lang'] = $lang->slug;
						$args['name'] = $this->getLocalisedNameAttr('', $lang->locale);
						$args['id']   = $this->getLocalisedIdAttr('', $lang->locale);
						$args['selected'] = $this->getLocalisedValue('', $lang->locale);

						if(!AitLangs::isFilteredOut($lang)){
														
							
							
							$dropdown = '';
							
							$emptySelect = function($output) use($cpt, $args) {
								return sprintf(
									empty($output) ? "<select data-placeholder='%s' name='%s' id='%s' class='%s'></select>" : $output,
									esc_attr(sprintf(__('No items. Add some items to "%s"', 'ait-admin'), $cpt->labels->menu_name)),
									$args['name'],
									$args['id'],
									$args['class']
								);
							};
							

							add_filter('wp_dropdown_pages', $emptySelect);
							add_filter('ait-dropdown-posts', $emptySelect);
							if($cpt->hierarchical){
								$dropdown = wp_dropdown_pages($args);
								$dropdown = str_replace('<select', "<select class='{$args['class']}'", $dropdown);
							}else{
								$dropdown = aitDropdownPosts($args);
							}
							
							remove_filter('wp_dropdown_pages', $emptySelect);
							remove_filter('ait-dropdown-posts', $emptySelect);

							?>						
							<div class="ait-opt-wrapper chosen-wrapper ait-langs-enabled <?php echo AitLangs::htmlClass($lang->locale) ?>">
								<?php 
									echo "<div class=\"flag\">{$lang->flag}</div>";
									echo $dropdown;
								?>
							</div>
							
							<?php

						}else{
							?>
							<input type="hidden" name="<?php echo $args['name'] ?>" value="<?php echo $args['selected'] ?>">
							<?php
						}
					

					}
				?></div><?php

				if($this->helpPosition() == 'inline'): ?>
					<div class="ait-opt-help">
						<?php $this->help() ?>
					</div>
				<?php 
				endif; 

			}else{
				//older not touched version of input, with no "multilingual" attribute used.

				$dropdown = '';

				$emptySelect = function($output) use($cpt, $args) {
					return sprintf(
						empty($output) ? "<select data-placeholder='%s' name='%s' id='%s' class='%s'></select>" : $output,
						esc_attr(sprintf(__('No items. Add some items to "%s"', 'ait-admin'), $cpt->labels->menu_name)),
						$args['name'],
						$args['id'],
						$args['class']
					);
				};

				add_filter('wp_dropdown_pages', $emptySelect);
				add_filter('ait-dropdown-posts', $emptySelect);
				if($cpt->hierarchical){
					$dropdown = wp_dropdown_pages($args);
					$dropdown = str_replace('<select', "<select class='{$args['class']}'", $dropdown);
				}else{
					$dropdown = aitDropdownPosts($args);
				}
				
				remove_filter('wp_dropdown_pages', $emptySelect);
				remove_filter('ait-dropdown-posts', $emptySelect);

				if($this->config->label == '@native')
					$this->config->label = $cpt->labels->singular_name;
				?>

				<div class="ait-opt-label">
					<?php $this->labelWrapper() ?>
				</div>

				<div class="ait-opt ait-opt-<?php echo $this->id ?>">
					<div class="ait-opt-wrapper chosen-wrapper">
						<?php echo $dropdown ?>
					</div>
				</div>
				<?php if($this->helpPosition() == 'inline'): ?>
					<div class="ait-opt-help">
						<?php $this->help() ?>
					</div>
				<?php endif; ?>
		
		<?php
			}	
		}else{
			echo "<strong style='color:red'>Custom post type <code>{$cptName}</code> doesn't exist.</strong>";
		}
	}



	public static function prepareDefaultValue($optionControlDefinition)
	{
		return $optionControlDefinition['default'] == '' ? 0 : $optionControlDefinition['default']; // 0 - all posts, -1 - none posts
	}

}
