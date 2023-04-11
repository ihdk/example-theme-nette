<?php


/**
 * Customized page dropdown walker.
 */
class AitPagePostDropdownWalker extends Walker_PageDropdown
{

	function start_el(&$output, $post, $depth = 0, $args = array(), $id = 0)
	{
		$pad = str_repeat('&nbsp;', $depth * 3);

		$oidPrefix = (isset($args['oid_prefix']) and $args['oid_prefix']) ? $args['oid_prefix'] : '';

		if($oidPrefix){
			$valueAttribute = $oidPrefix . $post->ID;
		}else{
			$valueAttribute = $post->ID;
		}

		$cssClasses = "normal-page level-$depth";

		if (in_array($post->ID, $args['pages_with_local_options'])) {
			$cssClasses .= " has-local-options";
		} else if ($args['only_list_pages_with_local_options']) {
			return;
		}

		$selectedAttribute = ($valueAttribute == $args['selected'] ? "selected" : "");
		$disabledAttribute = in_array($post->ID, $args['disabled_pages_ids']) ? ' disabled' : '';

		$output .= sprintf("\t<option class='%s' value='%s'%s%s>", $cssClasses, $valueAttribute, $selectedAttribute, $disabledAttribute);
		$output .= $pad . ' ' . esc_html($post->post_title);
		$output .= "</option>\n";
	}
}
