<?php


class AitMenuBackendWalker extends Walker_Nav_Menu_Edit
{

	/** @inheritdoc */
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $_wp_nav_menu_max_depth;

		/* AIT BLOCK */
		if ($item->title == 'menu-item-ait-column') {
			$depth = 1;
		}
		/* AIT BLOCK END */

		$_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

		ob_start();
		$item_id = esc_attr( $item->ID );
		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		$original_title = '';
		if ( 'taxonomy' == $item->type ) {
			$original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
			if ( is_wp_error( $original_title ) )
				$original_title = false;
		} elseif ( 'post_type' == $item->type ) {
			$original_object = get_post( $item->object_id );
			$original_title = get_the_title( $original_object->ID );
		}

		$classes = array(
			'menu-item menu-item-depth-' . $depth,
			'menu-item-' . esc_attr( $item->object ),
			'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
		);

		if ($item->title == 'menu-item-ait-column') {
			$isColumn = true;
			$classes[] = 'menu-item-column';
		} else {
			$isColumn = false;
		}

		$title = $item->title;

		if ( ! empty( $item->_invalid ) ) {
			$classes[] = 'menu-item-invalid';
			/* translators: %s: title of menu item which is invalid */
			$title = sprintf( __( '%s (Invalid)', 'ait-admin' ), $item->title );
		} elseif ( isset( $item->post_status ) && 'draft' == $item->post_status ) {
			$classes[] = 'pending';
			/* translators: %s: title of menu item in draft status */
			$title = sprintf( __('%s (Pending)', 'ait-admin'), $item->title );
		}

		$title = ( ! isset( $item->label ) || '' == $item->label ) ? $title : $item->label;

		if ($isColumn) {
			$menuItemColumnLabelValue = trim(get_post_meta($item->ID, '_menu-item-column-label', true));
			$title = $menuItemColumnLabelValue ? $menuItemColumnLabelValue : '&nbsp;';
		}

		$submenu_text = '';
		if ( 0 == $depth )
			$submenu_text = 'style="display: none;"';

		?>
	<li id="menu-item-<?php echo $item_id; ?>" class="<?php echo implode(' ', $classes ); ?>">
		<dl class="menu-item-bar">
			<dt class="<?php if ($isColumn): ?>menu-item-handle menu-item-column-handle<?php else: ?>menu-item-handle<?php endif; ?>">
				<span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <?php if (!$isColumn): ?><span class="is-submenu" <?php echo $submenu_text; ?>><?php _e( 'sub item', 'ait-admin' ); ?></span><?php endif; ?></span>
					<span class="item-controls">
						<span class="item-type"><?php echo $isColumn ? __('Column', 'ait-admin') : esc_html( $item->type_label ); ?></span>
						<a class="item-edit" id="edit-<?php echo $item_id; ?>" title="<?php esc_attr_e('Edit Menu Item', 'default'); ?>" href="<?php
						echo ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? esc_url(admin_url( 'nav-menus.php' )) : esc_url(add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id )) ) );
						?>"><?php _e( 'Edit Menu Item', 'ait-admin' ); ?></a>
					</span>
			</dt>
		</dl>

		<div class="menu-item-settings wp-clearfix" id="menu-item-settings-<?php echo $item_id; ?>">
			<?php if( 'custom' == $item->type) : ?>
				<p class="field-url description description-wide<?php if ($isColumn) echo " hidden" ?>">
					<label for="edit-menu-item-url-<?php echo $item_id; ?>">
						<?php _e( 'URL', 'ait-admin' ); ?><br />
						<input type="text" id="edit-menu-item-url-<?php echo $item_id; ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
					</label>
				</p>
			<?php endif; ?>
			<?php
			if ($isColumn):
				?>
				<p class="field-column-label description description-wide">
					<label for="edit-menu-item-column-label-<?php echo $item_id; ?>">
						<?php _e( 'Label (Optional)', 'ait-admin' ); ?><br />
						<input type="text" id="edit-menu-item-column-label-<?php echo $item_id; ?>" class="widefat edit-menu-item-column-label" name="menu-item-column-label[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $menuItemColumnLabelValue ); ?>" />
					</label>
				</p>
			<?php endif; ?>
			<p class="description description-thin<?php if ($isColumn) echo " hidden" ?>">
				<label for="edit-menu-item-title-<?php echo $item_id; ?>">
					<?php _e( 'Navigation Label', 'ait-admin' ); ?><br />
					<input type="text" id="edit-menu-item-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
				</label>
			</p>
			<p class="description description-thin<?php if ($isColumn) echo " hidden" ?>">
				<label for="edit-menu-item-attr-title-<?php echo $item_id; ?>">
					<?php _e( 'Title Attribute', 'ait-admin' ); ?><br />
					<input type="text" id="edit-menu-item-attr-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
				</label>
			</p>
			<p class="field-link-target description<?php if ($isColumn) echo " hidden" ?>">
				<label for="edit-menu-item-target-<?php echo $item_id; ?>">
					<input type="checkbox" id="edit-menu-item-target-<?php echo $item_id; ?>" value="_blank" name="menu-item-target[<?php echo $item_id; ?>]"<?php checked( $item->target, '_blank' ); ?> />
					<?php _e( 'Open link in a new window/tab', 'ait-admin' ); ?>
				</label>
			</p>
			<p class="field-css-classes description description-thin">
				<label for="edit-menu-item-classes-<?php echo $item_id; ?>">
					<?php _e( 'CSS Classes (optional)', 'ait-admin' ); ?><br />
					<input type="text" id="edit-menu-item-classes-<?php echo $item_id; ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo $item_id; ?>]" value="<?php echo esc_attr( implode(' ', $item->classes ) ); ?>" />
				</label>
			</p>
			<p class="field-xfn description description-thin<?php if ($isColumn) echo " hidden" ?>">
				<label for="edit-menu-item-xfn-<?php echo $item_id; ?>">
					<?php _e( 'Link Relationship (XFN)', 'ait-admin' ); ?><br />
					<input type="text" id="edit-menu-item-xfn-<?php echo $item_id; ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
				</label>
			</p>
			<p class="field-description description description-wide<?php if ($isColumn) echo " hidden" ?>">
				<label for="edit-menu-item-description-<?php echo $item_id; ?>">
					<?php $isColumn ? _e('Description', 'ait-admin') : _e( 'Description', 'ait-admin' ); ?><br />
					<textarea id="edit-menu-item-description-<?php echo $item_id; ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id; ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
					<span class="description"><?php _e('The description will be displayed in the menu if the current theme supports it.', 'ait-admin'); ?></span>
				</label>
			</p>

			<p class="field-icon description description-wide">
				<label for="edit-menu-item-icon-<?php echo $item_id; ?>">
					<?php _e( 'Icon (Optional)', 'ait-admin' ); ?><br />
					<?php $menuItemIconValue = get_post_meta($item->ID, '_menu-item-icon', true); ?>
					<input type="text" id="edit-menu-item-icon-<?php echo $item_id; ?>" class="edit-menu-item-image edit-menu-item-icon ait-image-value" name="menu-item-icon[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $menuItemIconValue ); ?>" />
					<input type="button" class="edit-menu-item-image-media-button ait-image-select button button-secondary right" <?php echo aitDataAttr('select-image', array('title' => __('Select Image', 'ait-admin'), 'buttonTitle' => __('Insert Image', 'ait-admin'))); ?> value="<?php _e('Select Image', 'ait-admin') ?>" id="edit-menu-item-icon-<?php echo $item_id; ?>-media-button">
				</label>
			</p>

			<p class="field-submenu-position description description-wide<?php if ($depth > 0) echo ' hidden' ?>">
				<label for="edit-menu-item-submenu-<?php echo $item_id; ?>">
					<?php _e( 'Submenu Position', 'ait-admin' ); ?><br />
					<?php $menuItemSubmenuPositionValue = get_post_meta($item->ID, '_menu-item-submenu-position', true); ?>
					<select id="edit-menu-item-icon-<?php echo $item_id; ?>" class="edit-menu-item-submenu-position" name="menu-item-submenu-position[<?php echo $item_id; ?>]">
						<option value=""<?php if (empty($menuItemSubmenuPositionValue)) echo ' selected="selected"'; ?>><?php _e('Theme Default', 'ait-admin'); ?></option>
						<option value="left"<?php if ($menuItemSubmenuPositionValue == 'left') echo ' selected="selected"'; ?>><?php _e('Left', 'ait-admin'); ?></option>
						<option value="right"<?php if ($menuItemSubmenuPositionValue == 'right') echo ' selected="selected"'; ?>><?php _e('Right', 'ait-admin'); ?></option>
						<option value="center"<?php if ($menuItemSubmenuPositionValue == 'center') echo ' selected="selected"'; ?>><?php _e('Center', 'ait-admin'); ?></option>
						<option class="only-if-has-columns" value="content-left"<?php if ($menuItemSubmenuPositionValue == 'content-left') echo ' selected="selected"'; ?>><?php _e('Content Left', 'ait-admin'); ?></option>
						<option class="only-if-has-columns" value="content-right"<?php if ($menuItemSubmenuPositionValue == 'content-right') echo ' selected="selected"'; ?>><?php _e('Content Right', 'ait-admin'); ?></option>
						<option class="only-if-has-columns" value="content-full-width"<?php if ($menuItemSubmenuPositionValue == 'content-full-width') echo ' selected="selected"'; ?>><?php _e('Full Content Width', 'ait-admin'); ?></option>
					</select>
				</label>
			</p>

			<?php if ($isColumn): ?>
				<p class="field-column-background-image description description-wide">
					<label for="edit-menu-item-column-url-<?php echo $item_id; ?>">
						<?php _e( 'Url (Optional)', 'ait-admin' ); ?><br />
						<?php $menuItemColumnUrlValue = get_post_meta($item->ID, '_menu-item-column-url', true); ?>
						<input type="text" id="edit-menu-item-column-url-<?php echo $item_id; ?>" class="widefat code edit-menu-item-column-url" name="menu-item-column-url[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $menuItemColumnUrlValue ); ?>" />
					</label>
				</p>
				<p class="field-column-min-width description description-thin">
					<label for="edit-menu-item-column-min-width-<?php echo $item_id; ?>">
						<?php _e( 'Minimum Width in px (Optional)', 'ait-admin' ); ?><br />
						<?php $menuItemColumnMinWidthValue = get_post_meta($item->ID, '_menu-item-column-min-width', true); ?>
						<input type="number" id="edit-menu-item-column-min-width-<?php echo $item_id; ?>" class="widefat code edit-menu-item-column-min-width" name="menu-item-column-min-width[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $menuItemColumnMinWidthValue); ?>" />
					</label>
				</p>
				<p class="field-column-in-new-row description description-thin">
					<label for="edit-menu-item-column-in-new-row-<?php echo $item_id; ?>">
						<?php $menuItemColumnInNewRowChecked = get_post_meta($item->ID, '_menu-item-column-in-new-row', true); ?>
						<input type="checkbox" id="edit-menu-item-column-in-new-row-<?php echo $item_id; ?>" class="widefat code edit-menu-item-column-in-new-row" name="menu-item-column-in-new-row[<?php echo $item_id; ?>]" value="true"<?php if ($menuItemColumnInNewRowChecked) echo " checked"; ?> />
						<?php _e( 'In New Row', 'ait-admin' ); ?><br />
					</label>
				</p>
			<?php endif; ?>

			<div class="menu-item-actions description-wide submitbox">
				<?php if( 'custom' != $item->type && $original_title !== false ) : ?>
					<p class="link-to-original">
						<?php printf( __('Original: %s', 'ait-admin'), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?>
					</p>
				<?php endif; ?>

				<?php /** AIT BLOCK */ ?>

				<div class="item-add-column-action <?php if ($depth > 0) echo 'hidden' ?>">
				<a class="item-add-column add-column" id="add-column-to-<?php echo $item_id; ?>" href="#<?php echo $item_id; ?>-column" data-menu-item="<?php echo "menu-item-" . $item_id ?>" data-menu-column-item-prototype="<?php echo "menu-item-" . $item_id . "-column"; ?>"><?php _e( 'Add Column', 'ait-admin' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span>
				</div>

				<?php /** END AIT BLOCk */ ?>

				<a class="item-delete submitdelete deletion" id="delete-<?php echo $item_id; ?>" href="<?php
				echo esc_url(wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'delete-menu-item',
							'menu-item' => $item_id,
						),
						admin_url( 'nav-menus.php' )
					),
					'delete-menu_item_' . $item_id
				)); ?>"><?php _e( 'Remove', 'ait-admin' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo $item_id; ?>" href="<?php echo esc_url( add_query_arg( array( 'edit-menu-item' => $item_id, 'cancel' => time() ), admin_url( 'nav-menus.php' ) ) );
				?>#menu-item-settings-<?php echo $item_id; ?>"><?php _e('Cancel', 'ait-admin'); ?></a>
			</div>

			<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" value="<?php echo $item_id; ?>" />
			<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
			<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
			<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
			<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
			<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->type ); ?>" />
		</div><!-- .menu-item-settings-->
		<ul class="menu-item-transport"></ul>

		<?php /** AIT BLOCK */ ?>

		<div class="hidden" style="display: none" id="menu-item-<?php echo $item_id ?>-column">
			<input type="hidden" disabled="disabled" class="menu-item-data-db-id" name="menu-item[0][menu-item-data-db-id]" value="0" />
			<input type="hidden" disabled="disabled" class="menu-item-object" name="menu-item[0][menu-item-object]" value="" />
			<input type="hidden" disabled="disabled" class="menu-item-parent-id" name="menu-item[0][menu-item-parent-id]" value="<?php echo $item->ID; ?>" />
			<input type="hidden" disabled="disabled" class="menu-item-type" name="menu-item[0][menu-item-type]" value="custom" />
			<input type="hidden" disabled="disabled" class="menu-item-title" name="menu-item[0][menu-item-title]" value="menu-item-ait-column" />
			<input type="hidden" disabled="disabled" class="menu-item-url" name="menu-item[0][menu-item-url]" value="#" />
			<input type="hidden" disabled="disabled" class="menu-item-classes" name="menu-item[0][menu-item-classes]" value="" />
		</div>

		<?php /** AIT BLOCK END */ ?>

		<?php

		$output .= ob_get_clean();
	}


}
