<?php


/**
 * Create HTML dropdown list of Categories.
 */
class AitCategoryDropdownWalker extends Walker_CategoryDropdown
{

	function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0)
	{
		$pad = str_repeat('&nbsp;', $depth * 3);

		// $args['selected'] must be slug or array of slugs when 'use_slug' is true
		$useSlug = (isset($args['use_slug']) and $args['use_slug']) ? true : false;

		$value = $useSlug ? $category->slug : $category->term_id;

		$cat_name = apply_filters('list_cats', $category->name, $category);
		$output .= "\t<option class=\"level-$depth\" value=\"" . $value . "\"";

		if(
			(isset($args['@multi_selected']) and is_array($args['@multi_selected']) and in_array($value, $args['@multi_selected']))
			or
			((is_string($args['selected']) or is_numeric($args['selected'])) and $args['selected'] == $value)
		)
		{
			$output .= ' selected="selected"';
		}

		$output .= '>';
		$output .= $pad.$cat_name;

		if($args['show_count'])
			$output .= '&nbsp;&nbsp;('. $category->count .')';

		$output .= "</option>\n";
	}
}
