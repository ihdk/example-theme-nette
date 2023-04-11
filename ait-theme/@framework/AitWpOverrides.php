<?php


/**
 * WordPress overrides of some default behaviours and functionality for purposes of Ait framework and themes
 */
class AitWpOverrides extends NObject
{

	protected static $galleryInstance = 0;

	public static function init()
	{
		add_filter('post_gallery', array(__CLASS__, 'postGallery'), 10, 2);
		add_filter('tiny_mce_before_init', array(__CLASS__, 'tinymceUnhideKitchensink'));

		add_filter('posts_join', array(__CLASS__, 'joinPostsMetadataToEnableSearchingInElements'));
		add_filter('posts_search', array(__CLASS__, 'enableSearchingInElements'), 10, 2);
		add_filter('posts_request', array(__CLASS__, 'showOnlyDistinctPostSearchResults'));

		add_filter('comment_form_fields', array(__CLASS__, 'fixCommentFieldsInWp44'), 99);
	}



	public static function postGallery($emptyString, $attr)
	{
		$post = get_post();

		self::$galleryInstance++;

		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) )
				$attr['orderby'] = 'post__in';
			$attr['include'] = $attr['ids'];
		}

		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		extract(shortcode_atts(array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => 'dl',
			'icontag'    => 'dt',
			'captiontag' => 'dd',
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => ''
		), $attr, 'gallery'));

		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( !empty($include) ) {
			$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		}

		if ( empty($attachments) )
			return '';

		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment )
				$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
			return $output;
		}

		$itemtag = tag_escape($itemtag);
		$captiontag = tag_escape($captiontag);
		$icontag = tag_escape($icontag);
		$valid_tags = wp_kses_allowed_html( 'post' );
		if ( ! isset( $valid_tags[ $itemtag ] ) )
			$itemtag = 'dl';
		if ( ! isset( $valid_tags[ $captiontag ] ) )
			$captiontag = 'dd';
		if ( ! isset( $valid_tags[ $icontag ] ) )
			$icontag = 'dt';

		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		$float = is_rtl() ? 'right' : 'left';

		$selector = "gallery-" . self::$galleryInstance;

		$gallery_style = $gallery_div = '';
		if ( apply_filters( 'use_default_gallery_style', true ) )
			$gallery_style = "
			<div>
			<style type='text/css'>
				#{$selector} {
					margin: auto;
				}
				#{$selector} .gallery-item {
					float: {$float};
					margin-top: 10px;
					text-align: center;
					width: {$itemwidth}%;
				}
				#{$selector} img {
					border: 2px solid #cfcfcf;
				}
				#{$selector} .gallery-caption {
					margin-left: 0;
				}
				/* see gallery_shortcode() in wp-includes/media.php */
			</style></div>";
		$size_class = sanitize_html_class( $size );
		$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
		$gallery_div .= '<div class="gallery-inner-wrapper">';
		$output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

		$i = 0;
		foreach ( $attachments as $id => $attachment ) {
			if ( ! empty( $attr['link'] ) && 'file' === $attr['link'] )
				$image_output = wp_get_attachment_link( $id, $size, false, false );
			elseif ( ! empty( $attr['link'] ) && 'none' === $attr['link'] )
				$image_output = wp_get_attachment_image( $id, $size, false );
			else
				$image_output = wp_get_attachment_link( $id, $size, true, false );

			$image_meta  = wp_get_attachment_metadata( $id );

			$orientation = '';
			if ( isset( $image_meta['height'], $image_meta['width'] ) )
				$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';

			$output .= "<{$itemtag} class='gallery-item'>";
			$output .= "
				<{$icontag} class='gallery-icon {$orientation}'>
					$image_output
				</{$icontag}>";
			if ( $captiontag && trim($attachment->post_excerpt) ) {
				$output .= "
					<{$captiontag} class='wp-caption-text gallery-caption'>
					" . wptexturize($attachment->post_excerpt) . "
					</{$captiontag}>";
			}else{
				$output .= "<{$captiontag}></{$captiontag}>";
			}
			$output .= "</{$itemtag}>";
			if ( $columns > 0 && ++$i % $columns == 0 )
				$output .= '<br style="clear: both" />';
		}

		$output .= "
				<br style='clear: both;' />
			</div></div>\n";

		return $output;
	}



	public static function tinymceUnhideKitchensink($args)
	{
		$args['wordpress_adv_hidden'] = false;
		return $args;
	}



	/**
	 * Adopted from Search Everything plugin
	 */
	public static function enableSearchingInElements( $where, $wp_query )
	{
		if ( !$wp_query->is_search() || basename( $_SERVER["SCRIPT_NAME"] ) == "admin-ajax.php" || ( isset($_REQUEST['a']) && $_REQUEST['a'] == true )) {
			// if it is not search or it is ajax search in admin (we don't have access to wp_options table as posts_join filter is not called)
			// fix: if it is not directory items search
			return $where;
		}

		$searchQuery = self::buildDefaultSearchSql($wp_query);
		$searchQuery .= self::buildPostElementsOptionsSearchSql($wp_query);

		if ($searchQuery != '') {
			$where = preg_replace( '#\(\(\(.*?\)\)\)#', '(('.$searchQuery.'))', $where );
		}

		return $where;
	}



	/**
	 * Adopted from Search Everything plugin
	 */
	public static function joinPostsMetadataToEnableSearchingInElements($join)
	{
		global $wpdb;
		// fix: if it is not directory items search
		if(is_search() && !isset($_REQUEST['a']) ){
			$theme = esc_sql(AIT_CURRENT_THEME);
			$join .= " LEFT JOIN {$wpdb->options} ON {$wpdb->options}.option_name LIKE CONCAT('_ait_{$theme}_elements_opts_page_', {$wpdb->posts}.ID)";
		}
		return $join;
	}



	/**
	 * Adopted from Search Everything plugin
	 */
	public static function showOnlyDistinctPostSearchResults($query)
	{
		if(is_search() and strstr($query, 'DISTINCT') === false){
			$query = str_replace('SELECT', 'SELECT DISTINCT', $query);
		}
		return $query;
	}



	public static function fixCommentFieldsInWp44($fields)
	{
		if(isset($fields['comment'])){
			$commentField = $fields['comment'];
			unset($fields['comment']);
			$fields['comment'] = $commentField;
		}
		return $fields;
	}



	/**
	 * Search for terms in default locations like title and content
	 *
	 * Replacing the old search terms seems to be the best way to
	 * avoid issue with multiple terms
	 *
	 * Adopted from Search Everything plugin
	 */
	private static function buildDefaultSearchSql($wp_query)
	{
		global $wpdb;

		$not_exact = empty($wp_query->query_vars['exact']);
		$search_sql_query = '';
		$seperator = '';
		$terms = self::getSearchTerms($wp_query);

		// if it's not a sentance add other terms
		$search_sql_query .= '(';
		foreach ( $terms as $term ) {
			$search_sql_query .= $seperator;

			$esc_term = esc_sql($term);
			if ($not_exact) {
				$esc_term = "%$esc_term%";
			}

			$like_title = "($wpdb->posts.post_title LIKE '$esc_term')";
			$like_post = "($wpdb->posts.post_content LIKE '$esc_term')";

			$search_sql_query .= "($like_title OR $like_post)";

			$seperator = ' AND ';
		}

		$search_sql_query .= ')';
		return $search_sql_query;
	}



	private static function buildPostElementsOptionsSearchSql($wp_query)
	{
		$s = $wp_query->query_vars['s'];
		$search_terms = self::getSearchTerms($wp_query);
		$n = ( isset( $wp_query->query_vars['exact'] ) && $wp_query->query_vars['exact'] ) ? '' : '%';
		$search = '';

		if ( !empty( $search_terms ) ) {
			// Building search query
			$searchand = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$search .= "{$searchand}(option_value LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$search = "($search) OR (option_value LIKE '{$n}{$sentence_term}{$n}')";
			}

			if ( !empty( $search ) )
				$search = " OR ({$search}) ";

		}

		return $search;
	}



	/**
	 * Get the list of search keywords from the 's' parameters.
	 */
	private static function getSearchTerms($wp_query)
	{

		$s = isset( $wp_query->query_vars['s'] ) ? $wp_query->query_vars['s'] : '';
		$sentence = isset( $wp_query->query_vars['sentence'] ) ? $wp_query->query_vars['sentence'] : false;
		$search_terms = array();

		if ( !empty( $s ) ) {
			// added slashes screw with quote grouping when done early, so done later
			$s = stripslashes( $s );
			if ( $sentence ) {
				$search_terms = array( $s );
			} else {
				preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches );
				$search_terms = array_map( function($a){ return trim($a, "'\"\n\r "); }, $matches[0] );
			}
		}
		return $search_terms;
	}


}
