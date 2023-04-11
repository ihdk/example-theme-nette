<?php


/**
 * The Post Entity
 */
class WpLattePostEntity extends WpLatteBaseEntity
{

	/**
	 * The public post ID
	 * @var int
	 */
	protected $id;

	/**
	 * Post type
	 * @var string
	 */
	protected $type;

	/**
	 * The internal post ID
	 * @var int
	 * @internal
	 */
	protected $postId;

	/**
	 * Post parent ID
	 * @var int
	 */
	protected $parentId;

	/**
	 * Menu order attribute
	 * @var integer
	 */
	protected $menuOrder = 0;

	/**
	 * Is Custom Post Type?
	 * @internal
	 * @var boolean
	 */
	protected $isCpt = false;

	/**
	 * Post author
	 * @var WpLattePostAuthor
	 */
	protected $author;

	/**
	 * Post slug
	 * @var string
	 */
	protected $slug;

	/**
	 * @var internal
	 */
	protected $rawExcerpt;

	protected $rawDate;

	protected $rawTitle;

	/** @var boolean flag */
	protected static $isInAnyCategory = false;



	/**
	 * New Post entity
	 * @param stdClass $post Post object
	 * @param array
	 */
	public function __construct($post)
	{
		$this->id         = (int) $post->ID;
		$this->postId     = $this->id;
		$this->type       = $post->post_type;
		$this->slug       = $post->post_name;
		$this->parentId   = isset($post->post_parent) ? (int) $post->post_parent : 0;
		$this->menuOrder  = isset($post->menu_order) ? (int) $post->menu_order : 0;
		$this->isCpt      = WpLatteUtils::isCpt($post->post_type);
		$this->rawExcerpt = $post->post_excerpt;
		$this->rawDate    = $post->post_date;
		$this->rawTitle  = $post->post_title;
		$this->author = WpLatte::createEntity('PostAuthor', $post->post_author);
	}



	public function parent()
	{
		if($parent = WpLatteObjectCache::load("parent-{$this->postId}-{$this->parentId}")){
			return $parent;
		}

		$parent = new self(get_post($this->parentId));

		WpLatteObjectCache::save("parent-{$this->postId}-{$this->parentId}", $parent);
		return $parent;
	}



	/**
	 * Display the post title
	 * Alias for the_title()
	 *
	 * @return null|string    Null on no title. String if $echo parameter is false.
	 */
	public function title()
	{
		return get_the_title($this->postId);
	}



	/**
	 * Whether post has content
	 * @return boolean
	 */
	public function hasTitle()
	{
		$t = $this->title();
		return !empty($t);
	}



	/**
	 * Display the post content
	 * Alias for the_content()
	 *
	 * @param string $moreLinkText  Optional. Content for when there is more text.
	 * @param bool   $stripteaser   Optional. Strip teaser content before the more text. Default is false.
	 */
	public function content($moreLinkText = null, $stripteaser = false)
	{
		$funcParams = func_get_args();
		if($content = WpLatteObjectCache::load("content-{$this->postId}", $funcParams))
			return $content;

		$object = get_queried_object();
		$blogPage = 0;
		if(get_option('show_on_front') == 'page'){
		 	$blogPage = get_option('page_for_posts');
		}

		// special case - when static page is set as blog page and we want to display content
		if(is_home() and $object and $this->postId == $blogPage and (isset($object->post_status) or $object instanceof WP_Post)){
			$content = $object->post_content;
		}else{
			$content = get_the_content($moreLinkText, $stripteaser);
		}

		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);

		$funcParams = func_get_args();
		WpLatteObjectCache::save("content-{$this->postId}", $content, $funcParams);
		return $content;
	}



	/**
	 * Whether post has content
	 * @return boolean
	 */
	public function hasContent()
	{
		$c = $this->content();
		return !empty($c);
	}



	/**
	 * Display the Post Global Unique Identifier (guid)
	 * Alias for the_guid()
	 *
	 * @param  int     $id Optional. Post ID.
	 * @return string
	 */
	public function guid()
	{
		return get_the_guid($this->postId);
	}



	/**
	 * Display the post excerpt.
	 * This is tuned verion of the_excerpt() function. It can accept word counts via parameter.
	 * All excerpt functionality is preserved and $wordsCount parameter entered here
	 * is passed to excerpt_length hook too
	 * @param int $wordsCount Number of words from excerpt
	 * @return string Processed excerpt via the_excerpt hook
	 */
	public function excerpt($wordsCount = null)
	{
		if($wordsCount){

			if($this->hasPassword()){
				return __('There is no excerpt because this is a protected post.', 'wplatte');
			}

			$excerpt = $this->rawExcerpt;

			// if there is no manualy written excerpt use the content
			if($excerpt == ''){
				$excerpt = get_the_content('');
				$excerpt = strip_shortcodes($excerpt);

				remove_filter('the_content', 'do_shortcode');
				$excerpt = apply_filters('the_content', $excerpt);
				$excerpt = str_replace(']]>', ']]&gt;', $excerpt);
				if(!has_filter('the_content', 'do_shortcode')){
					add_filter('the_content', 'do_shortcode', 11); // AFTER wpautop()
				}
			}

			$excerptLength = apply_filters('excerpt_length', $wordsCount); // in wp_trim_excerpt() there is hardcoded 55
			$excerptMore = apply_filters('excerpt_more', ' ' . '[&hellip;]');
			$excerpt = wp_trim_words($excerpt, $excerptLength, $excerptMore);

			remove_filter('get_the_excerpt', 'wp_trim_excerpt');
			$excerpt = apply_filters('wp_trim_excerpt', $excerpt, apply_filters('get_the_excerpt', $this->rawExcerpt));

			return apply_filters('the_excerpt', $excerpt);
		}

		return apply_filters('the_excerpt', get_the_excerpt());
	}



	/**
	 * Whether post has excerpt.
	 * Alias for has_excerpt()
	 * @return boolean
	 */
	public function hasExcerpt()
	{
		return (!empty($this->rawExcerpt));
	}



	/**
	 * Display the permalink for the current post.
	 * Alias for the_permalink()
	 *
	 * @return string
	 */
	public function permalink()
	{
		return apply_filters('the_permalink', get_permalink($this->postId));
	}



	public function shortUrl()
	{
		return wp_get_shortlink($this->postId);
	}



	public function taxonomies()
	{
		$objectTaxonomies = get_object_taxonomies($this->type);
		$publicObjectTaxonomies = array();
		foreach($objectTaxonomies as $objectTaxonomy) {
			$objectTaxonomy = get_taxonomy($objectTaxonomy);
			if ($objectTaxonomy && $objectTaxonomy->public) {
				$publicObjectTaxonomies[] = $objectTaxonomy->name;
			}
		}
		return $publicObjectTaxonomies;
	}



	/**
	 * Retrieve category list in either HTML <ul> list or custom HTML format.
	 * Alias for get_the_category_list() for Post and get_the_term_list() for CPTs
	 *
	 * @param  string $separator Optional, default is empty string. Separator for between the categories.
	 * @param  string $parents   Optional. How to display the parents.
	 * @param  string $taxonomy  If CPT has more then one taxonomy you can specify which one you want to display
	 * @return string
	 */
	public function categoryList($separator = '', $parents = '', $taxonomy = '')
	{
		if(!$this->isCpt){
			return get_the_category_list($separator, $parents, $this->postId);
		}else{
			$objectTaxonomies = get_object_taxonomies($this->type);

			$publicObjectTaxonomies = array();

			foreach($objectTaxonomies as $objectTaxonomy) {
				if($objectTaxonomy === 'language') continue;
				$objectTaxonomy = get_taxonomy($objectTaxonomy);
				if ($objectTaxonomy && $objectTaxonomy->public) {
					$publicObjectTaxonomies[] = $objectTaxonomy->name;
				}
			}

			if(count($publicObjectTaxonomies) == 1 or $taxonomy){
				$tax = $publicObjectTaxonomies[0];
				if($taxonomy) $tax = $taxonomy;
				return get_the_term_list($this->postId, $tax, '', $separator);
			}else{
				$list = array();
				foreach($publicObjectTaxonomies as $tax){
					$list[] = get_the_term_list($this->postId, $tax, '', $separator);
				}
				if($separator == '') $separator = ' ';
				return implode($separator, $list);
			}
		}
	}



	/**
	 * Gets categories or terms for custom taxonomy.
	 * get_categories() function on steroids.
	 *
	 * @param  string $taxonomy    Taxonomy name, if it "post" CPT it is default "category", otherwide user defined taxonomy for CPT
	 * @return array               Array of categories entities in form: ID => Entity
	 */
	public function categories($taxonomy = 'category')
	{
		$funcParams = func_get_args();
		if($return = WpLatteObjectCache::load("post-categories-{ $this->postId}", $funcParams))
			return $return;

		$registeredTaxonomies = get_object_taxonomies($this->type);

		$registeredTaxonomies = array_diff($registeredTaxonomies, array('language', 'post_translations')); // quick fix for polylang taxonomies

		// if there is only one registred taxonomy for given cpt, we can ommit parameter $taxonomy
		// and use that one taxonomy by default, otherwide we must choose which taxonomy we want
		if($this->isCpt and $taxonomy == 'category' and count($registeredTaxonomies) == 1){
			$taxonomy = array_pop($registeredTaxonomies);
		}elseif($this->isCpt and $this->type !== 'product' and $taxonomy == 'category' and count($registeredTaxonomies) > 1){
			if(!self::$isInAnyCategory){ // is called from isInAnyCategory() without any params, so do not trigger error then
				trigger_error('You must specify concrete taxonomy name in the ' . __METHOD__ . '($taxonomy)');
			}
		}

		$terms = get_the_terms($this->postId, WpLatteUtils::addPrefix('taxonomy', $taxonomy));

		if(!$terms or is_wp_error($terms))
			$terms = array();

		if(!$this->isCpt and $taxonomy == 'category'){
			$terms = apply_filters('get_the_categories', array_values($terms));
		}

		// get_the_terms can return false if there are no
		// terms under specified taxonomy
		$terms = array_filter((array) $terms);

		$return = array();

		foreach($terms as $i => $term){
			if(WpLatteUtils::isCustomTax($term->taxonomy)){
				$c = WpLatte::createEntity('TaxonomyTerm', $term);
			}else{
				$c = WpLatte::createEntity('Category', $term);
			}

			$return[$c->id] = $c;
		}

		unset($terms); // not sure if useful for something

		$funcParams = func_get_args();
		WpLatteObjectCache::save("post-categories-{ $this->postId}", $return, $funcParams);

		return $return;
	}



	public function recursiveCategoriesSlugs($id, $type, $taxonomy, $separator, $prefix = "", $suffix = "")
	{
		$result = "";
		if($type == "post"){
			$terms = get_the_terms($id, $taxonomy);
			$term = reset($terms);
		} else {
			$term = get_term_by("id", $id, $taxonomy);
		}
		$result .= $prefix.$term->slug.$suffix.$separator;
		if($term->parent != 0){
			$result .= $this->recursiveCategoriesSlugs($term->parent, "cat", $taxonomy, $separator, $prefix, $suffix);
		}
		return $result;
	}



	public function catSlugs($taxonomy, $separator, $prefix = "", $suffix = "")
	{
		$result = "";
		$cats = $this->categories($taxonomy);

		/* loop for parent categories */
		foreach($cats as $key => $cat){
			if($cat->parentId == 0){
				$result .= $prefix.$cat->slug.$suffix.$separator;
				unset($cats[$key]);
			}
		}

		/* loop for child categories including recursion towards the childs parent */
		foreach ($cats as $cat) {
			$result .= $this->recursiveCategoriesSlugs($cat->id, "cat", $taxonomy, $separator, $prefix, $suffix);
		}

		/* Remove Duplicates */
		$slugs = explode(" ", $result);
		$result = implode(" ", array_unique($slugs));
		/* Remove Duplicates */

		return $result;
	}



	/**
	 * Gets categories slugs as array or string, useful for HTML classes and so...
	 * @param  string  $prefix   Prefix for slug
	 * @param  string  $suffix   Suffix for slug
	 * @param  boolean $asString Return slugs as array or string delimited by space?
	 * @param  string  $taxonomy Name of taxonomy, default: category
	 * @return string|array
	 */
	public function categoriesSlugs($prefix = '', $suffix = '', $asString = true, $taxonomy = 'category')
	{
		$funcParams = func_get_args();
		if($return = WpLatteObjectCache::load("categories-slugs-{$this->postId}", $funcParams))
			return $return;

		$cats = $this->categories($taxonomy);
		$slugs = array();

		$prefix = $prefix !== '' ? "{$prefix}-" : $prefix;
		$suffix = $suffix !== '' ? "-{$suffix}" : $suffix;

		foreach($cats as $i => $cat){
			$slugs[$i] = $prefix . $cat->slug . $suffix;
		}

		$return = $asString ? implode(' ', $slugs) : $slugs;

		$funcParams = func_get_args();
		WpLatteObjectCache::save("categories-slugs-{$this->postId}", $return, $funcParams);
		return $return;
	}



	/**
	 * Checks if post has some categories, if in
	 * @return boolean
	 */
	public function isInAnyCategory()
	{
		self::$isInAnyCategory = true;
		$cats = $this->categories();
		self::$isInAnyCategory = false;
		return !empty($cats);
	}



	/**
 	 * Check if the current post has any of given category.
	 * Alias for has_category()
	 *
 	 * @param string|int|array $category  Optional. The category name/term_id/slug or array of them to check for.
	 * @return boolean                    True if the current post has any of the given categories (or any category, if no category specified).
	 */
	public function hasCategory($category = '')
	{
		return has_term($category, 'category', $this->postId);
	}



	public function hasTaxonomy($taxonomy)
	{
		return in_array(WpLatteUtils::addPrefix('taxonomy', $taxonomy), get_object_taxonomies($this->type));
	}



	/**
	 * Retrieve the tags for a post formatted as a string.
	 * Alias for get_the_tag_list()
	 *
	 * @param string $sep Optional. Between tags.
	 * @return string
	 */
	public function tagList($separator = ', ')
	{
		return get_the_tag_list('', $separator, '', $this->postId);
	}



	public function hasTag($tag = '')
	{
		return has_term($tag, 'post_tag', $this->postId);
	}



	public function status()
	{
		return get_post_status($this->postId);
	}



	public function hasFormat($format)
	{
		$has = has_post_format($format, $this->postId);

		if($has === false and $format == 'standard'){
			return true;
		}

		return $has;
	}



	public function formatTitle($format = '')
	{
		if($format)
			return get_post_format_string($format);
		else
			return get_post_format_string($this->formatName());
	}



	/**
	 * get_post_format() alias
	 * @return string Post format name/slug
	 */
	public function formatName()
	{
		return get_post_format($this->postId);
	}



	/**
	 * Return post gallery
	 * @return string HTML of image gallery
	 */
	public function gallery()
	{
		return get_post_gallery($this->postId, true);
	}



	public function hasImage()
	{
		return (bool) get_post_thumbnail_id($this->postId == 0 ? null : $this->postId);
	}



	/**
	 * Gets the Featured Image source URL
	 * @return string
	 */
	public function imageUrl($size = 'full')
	{
		if($return = WpLatteObjectCache::load("image-url-{$size}-{$this->postId}"))
			return $return;

		$id = get_post_thumbnail_id($this->postId == 0 ? null : $this->postId);
		if(in_the_loop()){
			update_post_thumbnail_cache();
		}
        $args = wp_get_attachment_image_src($id, $size);

		if($args !== false)
			$return = $args[0];
		else
			$return = '';

		WpLatteObjectCache::save("image-url-{$size}-{$this->postId}", $return);
		return $return;
	}



	/**
	 * Gets the Featured Image alt
	 * @return string
	 */
	public function imageAlt()
	{
		$image_id = get_post_thumbnail_id($this->postId == 0 ? null : $this->postId);
		$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true);

		return $image_alt != "" ? $image_alt : $this->title();
	}



	/**
	 * Gets the Featured Image (formerly called Post Thumbnail) as set in post's or page's edit screen
	 * and returns an HTML image element representing a Featured Image,
	 * if there is any, otherwise an empty string.
	 *
	 * @param  string|array $size (Optional) Either a string keyword (thumbnail, medium, large or full) or a 2-item array representing width and height in pixels,
	 *                            e.g. array(32,32). Default: 'post-thumbnail'
	 * @return String             HTML <img> tag
	 */
	public function image($size = 'post-thumbnail')
	{
		return get_the_post_thumbnail($this->postId == 0 ? null : $this->postId, $size);
	}



	public function date($format = '', $translate = false)
	{
		if($translate === 'translate'){ // for better readability
			$translate = true;
		}
		// we need cop&paste from get_the_date()
		// becacuse of passing parent id to get_post
		$post = get_post($this->postId);
		$the_date = '';

		if ( '' == $format ){
			$the_date .= mysql2date(get_option('date_format'), $post->post_date, $translate);
		}else{
			$the_date .= mysql2date($format, $post->post_date, $translate);
		}

		return apply_filters('get_the_date', $the_date, $format);
	}



	public function dateI18n($format = '')
	{
		return $this->date($format, 'translate');
	}



	public function time($d = '')
	{
		return apply_filters('the_time', get_the_time($d, $this->postId), $d);
	}



	public function dayArchiveUrl()
	{
		return get_day_link(get_the_time('Y', $this->postId), get_the_time('m', $this->postId), get_the_time('d', $this->postId));
	}



	public function monthArchiveUrl()
	{
		return get_month_link(get_the_time('Y', $this->postId), get_the_time('m', $this->postId));
	}



	public function yearArchiveUrl()
	{
		return get_year_link(get_the_time('Y', $this->postId));
	}



	public function htmlClass($class = '', $withAttr = true)
	{
		$class = implode(' ', get_post_class($class, $this->postId));

		if($withAttr){
			return ' class="' . $class . '" ';
		}else{
			return $class;
		}
	}



	public function htmlId($withAttr = true)
	{
		$id = $this->type . '-' . $this->id;

		if($withAttr){
			return ' id="' . $id . '" ';
		}else{
			return $id;
		}
	}



	public function hasPassword()
	{
		return post_password_required($this->postId);
	}



	public function isSticky()
	{
		return is_sticky($this->postId);
	}



	/**
	 * The formatted output of a list of pages.
	 *
	 * Displays page links for paginated posts (i.e. includes the <!--nextpage-->.
	 * Quicktag one or more times). This tag must be within The Loop.
	 */
	public function linkPages($args = array())
	{
		if(is_array($args)){
			$args['echo'] = false;
			if(!isset($args['before']))
				$args['before'] = '<div class="page-links">' . __('Pages:', 'wplatte');
			if(!isset($args['after']))
				$args['after'] = '</div>';
		}

		return wp_link_pages($args);
	}



	public function editLink($linkText)
	{
		ob_start();
		edit_post_link($linkText, '', '', $this->postId);
		return ob_get_clean();
	}



	public function hasComments()
	{
		return have_comments();
	}



	public function commentsNumber()
	{
		return (int) get_comments_number($this->postId);
	}



	public function commentsUrl()
	{
		if($this->commentsNumber() == 0)
			return get_permalink() . '#respond';
		else
			return get_comments_link();
	}



	public function willCommentsPaginate()
	{
		return (get_comment_pages_count() > 1 and get_option('page_comments'));
	}



	public function hasCommentsOpen()
	{
		return comments_open($this->postId);
	}



	public function hasCommentsClosed()
	{
		return (!comments_open($this->postId) and get_comments_number($this->postId) != '0' and post_type_supports(get_post_type($this->postId), 'comments' ));
	}



	public function attachment()
	{
		if($return = WpLatteObjectCache::load("attachment-{$this->postId}-{$this->parentId}"))
			return $return;

		$return = WpLatte::createEntity('Attachment', $this->postId, $this->parentId, $this->mimeType());

		WpLatteObjectCache::save("attachment-{$this->postId}-{$this->parentId}", $return);
		return $return;
	}



	public function mimeType()
	{
		return get_post_mime_type($this->postId);
	}



	/**
	 * Gets post meta
	 * @param  String  $metaboxId ID of your registered metabox
	 * @return mixed
	 */
	public function meta($metaboxId, $key = null)
	{
		$metaboxKey = "_{$this->type}_{$metaboxId}";

		if($return = WpLatteObjectCache::load("metabox-{$metaboxKey}-{$key}-{$this->postId}"))
			return $return;

		$postmeta = get_post_meta($this->postId, $metaboxKey, true); // returns array of data from custom metabox

		if($postmeta === ""){
			$postmeta = get_post_meta($this->postId, $metaboxId, true); // custom meta fields metabox
		}

		$postmeta = apply_filters('wplatte-post-meta', $postmeta, $metaboxId, $metaboxKey, $key, $this->isCpt, $this->type);

		if(is_array($postmeta)){
			if($key !== null and isset($postmeta[$key]))
				$return = $postmeta[$key];
			else
				$return = (object) $postmeta;
		}else{
			$return = $postmeta;
		}

		WpLatteObjectCache::save("metabox-{$metaboxKey}-{$key}-{$this->postId}", $return);
		return $return;
	}

}
