<?php


/**
 * The WordPress Entity
 *
 * Contains properties and (helper) methods used in all WpLatte templates.
 * In templates this entity is available under variable `$wp`.
 */
class WpLatteWpEntity extends WpLatteBaseEntity
{


	/**
	 * @internal
	 * @var WpLatteWpEntity
	 */
	private static $instance;



	/**
	 * Singleton "constructor"
	 * @return self
	 */
	public static function getInstance()
	{
		if(self::$instance === null){
			self::$instance = new self;
		}

		return self::$instance;
	}



	/**
	 * Magic getter
	 * @param string $key Name of property or method
	 * @return mixed
	 */
	public function &__get($name)
	{
		// see: http://codex.wordpress.org/Function_Reference/get_bloginfo#Parameters
		$blogInfo = array(
			'url', 'wpurl', 'description', 'rdfUrl', 'rssUrl', 'rss2Url', 'atomUrl', 'commentsAtomUrl',
			'commentsRss2Url', 'pingbackUrl', 'stylesheetUrl', 'stylesheetDirectory', 'templateDirectory',
			'templateUrl', 'adminEmail', 'charset', 'htmlType', 'version', 'language', 'textDirection', 'name'
		);

		$fn = self::camel2underscore($name);

		if(method_exists($this, $name) and is_callable(array($this, $name))){
			$return = $this->$name();
			return $return;

		}elseif(in_array($name, $blogInfo)){
			$return = get_bloginfo($fn, 'display');
			return $return;

		}elseif(substr($fn, 0, 2) == 'is' and function_exists($fn)){
			$return = $fn();
			return $return;

		}else{
			trigger_error(sprintf("Maybe you did a typo in a template. Property or method with name '%s' doesn't exist in class '%s'.", $name, get_class($this)));
			$return = null;
			return $return;
		}
	}



	// ====================================
	// Conditional tags
	// ------------------------------------

	public function is404(){ return is_404(); }

	public function isHomepage($page = ''){ return is_front_page($page); }

	public function isBlog($page = ''){ global $wp_query; return is_home() and $wp_query->is_posts_page; }

	public function isPage($page = ''){ return is_page($page); }

	public function isSingle($post = ''){ return is_single($post); }

	public function isPost(){ return is_single(); } // alias for isSingle, more readable

	public function isSingular($postTypes = ''){ return is_singular(WpLatteUtils::addPrefix('post', $postTypes)); }

	public function isPostTypeArchive($postTypes = ''){ return is_post_type_archive(WpLatteUtils::addPrefix('post', $postTypes)); }

	public function isAuthor($author = ''){ return is_author($author); }

	public function isCategory($category = ''){ return is_category($category); }

	public function isTag($slug = ''){ return is_tag($slug); }

	public function isTax($taxonomy = '', $term = ''){ return is_tax(WpLatteUtils::addPrefix('taxonomy', $taxonomy), $term); }

	public function isPaged(){ return is_paged(); }



	public function isMobile()
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		return (stripos($ua, 'safari') !== false && stripos($ua, 'mobile') !== false) || (stripos($ua,'android') !== false && stripos($ua,'mobile') !== false);
	}



	public function isIpad()
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		return strpos($ua, 'iPad') !== false;
	}



	public function isIphone()
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		return (strpos($ua,'iPhone') !== false || strpos($ua,'iPod') !== false);
	}



	public function canCurrentUser($capability)
	{
		return current_user_can($capability);
	}



	/**
	 * Is user logged in?
	 * @return bool
	 */
	public function isUserLoggedIn()
	{
		return is_user_logged_in();
	}



	// ====================================
	// Other tags
	// ------------------------------------


	public function maxNumPages($query)
	{
		if (empty($query)) {
			global $wp_query;
			return $wp_query->max_num_pages;
		}

		return $query->max_num_pages;

	}



	public function willPaginate($query = array())
	{
		return $this->maxNumPages($query) > 1;
	}



	public function hasPreviousPosts()
	{
		$prev = get_previous_posts_link();
		return $prev ? true : false;
	}



	public function hasNextPosts()
	{
		$next = get_next_posts_link();
		return $next ? true : false;
	}



	public function hasPreviousPost()
	{
		global $post;

		return (bool) ($post and is_attachment()) ? get_post($post->post_parent) : get_adjacent_post(false, '', true);
	}



	public function hasNextPost()
	{
		return (bool) get_adjacent_post(false, '', false);
	}



	/**
	 * Gets date format
	 * @return string
	 */
	public function dateFormat()
	{
		return get_option('date_format');
	}



	/**
	 * Gets time format
	 * @return string
	 */
	public function timeFormat()
	{
		return get_option('time_format');
	}



	/**
	 * Gets searched phrase
	 * @return string
	 */
	public function searchQuery()
	{
		return get_search_query();
	}



	public function adminUrl($path = '')
	{
		return admin_url($path);
	}



	public function blogUrl()
	{
		$id = get_option('page_for_posts');
		if(!$id) // '0'
			return home_url('/');
		return get_permalink($id);
	}



	public function havePosts()
	{
		return have_posts();
	}



	public function bodyHtmlClass($withAttr = true)
	{
		$c = implode(' ', get_body_class());

		if($withAttr)
			return 'class="' . $c . '"';
		else
			return $c;
	}



	/**
	 * Gets categories or terms for custom taxonomy.
	 * get_categories() function on steroids.
	 *
	 * @param  string|array $args  See get_categories() WordPress function or can be string with taxonomy unprefixed name
	 * @return array               Array of categories or slugs or ids
	 */
	public function categories($args = 'category')
	{
		$funcParams = func_get_args();
		if($return = WpLatteObjectCache::load('wp-categories', $funcParams))
			return $return;

		if(is_string($args)){
			$a = array();

			$a['taxonomy'] = WpLatteUtils::addPrefix('taxonomy', $args);
			$args = $a;
		}else{
			if(isset($args['taxonomy'])){
				$args['taxonomy'] = WpLatteUtils::addPrefix('taxonomy', $args['taxonomy']);
			}
		}

		$terms = get_categories($args);

		$return = array();

		foreach($terms as $i => $term){
			if(!is_object($term)) continue; // $term can be error array
			if(WpLatteUtils::isCustomTax($term->taxonomy)){
				$c = WpLatte::createEntity('TaxonomyTerm', $term);
			}else{
				$c = WpLatte::createEntity('Category', $term);
			}

			$return[$c->id] = $c;
		}

		unset($terms); // not sure if useful for something

		$funcParams = func_get_args();
		WpLatteObjectCache::save('wp-categories', $return, $funcParams);
		return $return;
	}



	public function isWidgetAreaActive($areaId)
	{
		return is_active_sidebar($areaId);
	}
}
