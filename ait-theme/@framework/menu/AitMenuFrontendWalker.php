<?php


class AitMenuFrontendWalker extends Walker
{

	/** @inheritdoc */
	var $tree_type = array( 'post_type', 'taxonomy', 'custom' );

	/** @inheritdoc */
	var $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );

	private $columns = 0;

	/** @var object */
	private $currentColumn = null;

	private $inRow = false;


	/**
	 * @see Walker::start_lvl()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		if (isset($this->currentColumn)) {
			$columnMinWidth = get_post_meta($this->currentColumn->ID, "_menu-item-column-min-width", true);

			$styleAttr = $minWidthCssClass = "";
			if ($columnMinWidth) {
				$styleAttr = " style=\"min-width: {$columnMinWidth}px\"";
				$minWidthCssClass = " has-min-width-set";
			}

			$output .= "\n$indent<ul class=\"sub-menu{$minWidthCssClass}\"{$styleAttr}>\n";
		} else {
			$output .= "\n$indent<ul class=\"sub-menu\">\n";
		}

	}

	/**
	 * @see Walker::end_lvl()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	function end_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";

		if($depth === 0)
		{
				$this->columns = 0;
			}
		}


	/**
	 * @see Walker::start_el()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	function start_el(&$output, $item, $depth = 0, $args = array(), $current_object_id = 0 ) {
		global $wp_query;

		$args = (object) $args;

		$item_output = $li_text_block_class = $column_class = "";

		if ($depth === 0 && $this->inRow) {
			$output .= '</ul></li>';
			$this->inRow = false;
		}

		$itemIcon = get_post_meta($item->ID, "_menu-item-icon", true);
		if ($itemIcon) {
			$itemIcon = '<img alt="icon" src="' . $itemIcon . '" />';
		} else {
			$itemIcon = "";
		}

		if($depth === 1 && $item->title == 'menu-item-ait-column')
		{

			$this->columns++;
			$this->currentColumn = $item;


			$columnLabel = get_post_meta($item->ID, "_menu-item-column-label", true);
			$columnUrl = get_post_meta($item->ID, "_menu-item-column-url", true);
			$columnUrl = $this->replaceLangParam($columnUrl);

			$columnLabel = $itemIcon . $columnLabel;

			if ($columnUrl) {
				$columnLabel = "<a href=\"$columnUrl\">{$columnLabel}</a>";
			}

			if(!empty($columnLabel))
			{
				$item_output .= "<div class=\"menu-item-column-label\">{$columnLabel}";
			}

			if (!empty($item->description)) {
				$item_output .= '<span class="menu-item-description">' . $item->description . '</span>';
			}

			if(!empty($columnLabel)) {
				$item_output .= "</div>";
			}

			$column_class = " menu-item-column";

			if($this->columns == 1)
			{
				$column_class  .= " menu-item-first-column";
			}
		}
		else
		{
			$this->currentColumn = null;

			$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
			$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
			$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
			$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $this->replaceLangParam($item->url) ) .'"' : '';

			$item_output .= $args->before;
			$item_output .= '<a'. $attributes .'>';
			$item_output .= $itemIcon;
			$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
			if (!empty($item->description)) {
				$item_output .= '<span class="menu-item-description">' . $item->description . '</span>';
			}
			$item_output .= '</a>';
			$item_output .= $args->after;
		}


		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';


		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		if ($depth == 0) {
			$submenuPosition = get_post_meta($item->ID, "_menu-item-submenu-position", true);
			if ($submenuPosition) {
				$classes[] = "sub-menu-{$submenuPosition}-position";
			}
		}

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
		$class_names = ' class="'.$li_text_block_class. esc_attr( $class_names ) . $column_class.'"';

		if($depth === 1 && $item->title == 'menu-item-ait-column')
		{
			$columnInNewRow = get_post_meta($item->ID, "_menu-item-column-in-new-row", true) || $this->columns == 1;
			if ($columnInNewRow) {
				$this->inRow = true;
				if ($this->columns > 1) {
					$output .='</ul></li><li class="menu-item-ait-row"><ul>';
				} else {
					$output .= '<li class="menu-item-ait-row"><ul class="menu-item-ait-columns-in-row">';
				}
			}
		}

		$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $class_names . '>';



		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * @see Walker::end_el()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Page data object. Not used.
	 * @param int $depth Depth of page. Not Used.
	 */
	function end_el(&$output, $item, $depth = 0, $args = array()) {
		$output .= "</li>\n";
		//if ($item == $this->currentColumn)
	}



	protected function replaceLangParam($url)
	{
		$langCode = AitLangs::getCurrentLang()->slug;
		return str_replace('%lang%', $langCode, $url);
	}

}
