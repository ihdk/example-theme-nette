<?php


/**
 * The WpLatte has its own extended template hierarchy
 */
class WpLatteTemplateHierarchy
{

	public static function register()
	{
		add_filter('date_template', array(__CLASS__, 'dateTemplate'), 11);

		add_filter('author_template', array(__CLASS__, 'userTemplate'), 11);

		add_filter('taxonomy_template', array(__CLASS__, 'taxonomyTemplate'), 11);

		add_filter('archive_template', array(__CLASS__, 'archiveTemplate'), 11);

		add_filter('single_template', array(__CLASS__, 'singularTemplate'), 11);
		add_filter('page_template', array(__CLASS__, 'singularTemplate'), 11);
		add_filter('attachment_template', array(__CLASS__, 'singularTemplate'), 11);
	}



	/**
	 * Overrides WP's default template for date-based archives. Better abstraction of templates than
	 * is_date() allows by checking for the year, month, week, day, hour, and minute.
	 *
	 * @param string $template
	 * @return string $template Full path to file.
	 */
	public static function dateTemplate($template)
	{
		$templates = array();

		if(is_time()){

			if(get_query_var('minute'))
				$templates[] = 'minute.php';
			elseif(get_query_var('hour'))
				$templates[] = 'hour.php';

			$templates[] = 'time.php';

		}elseif(is_day())
			$templates[] = 'day.php';

		elseif(get_query_var('w'))
			$templates[] = 'week.php';

		elseif(is_month())
			$templates[] = 'month.php';

		elseif(is_year())
			$templates[] = 'year.php';


		$templates[] = 'date.php';
		$templates[] = 'archive.php';

		return locate_template($templates);
	}



	/**
	 * Overrides WP's default template for author-based archives. Better abstraction of templates than
	 * is_author() allows by allowing themes to specify templates for a specific author. The hierarchy is
	 * user-$nicename.php, $user-role-$role.php, user.php, author.php, archive.php.
	 *
	 * @param string $template
	 * @return string Full path to file.
	 */
	public static function userTemplate($template)
	{
		$templates = array();

		$name = get_the_author_meta('user_nicename', get_query_var('author'));

		$user = new WP_User(absint(get_query_var('author')));

		$templates[] = "author-{$name}.php";
		$templates[] = "user-{$name}.php";

		if(is_array($user->roles)){
			foreach($user->roles as $role){
				$templates[] = "user-role-{$role}.php";
			}
		}

		$templates[] = 'author.php';
		$templates[] = 'user.php';
		$templates[] = 'archive.php';

		return locate_template($templates);
	}



	/**
	 * Overrides WP's default template for category- and tag-based archives. This allows better
	 * organization of taxonomy template files by making categories and post tags work the same way as
	 * other taxonomies. The hierarchy is taxonomy-$taxonomy-$term.php, taxonomy-$taxonomy.php,
	 * taxonomy.php, archive.php.
	 *
	 * @param string $template
	 * @return string Full path to file.
	 */
	public static function taxonomyTemplate($template)
	{
		$term = get_queried_object();

		$slug = (($term->taxonomy == 'post_format') ? str_replace('post-format-', '', $term->slug) : $term->slug);

		$originalTax = $term->taxonomy;
		$unprefixedTax = WpLatteUtils::stripPrefix('taxonomy', $term->taxonomy);

		return locate_template(array(
			"taxonomy-{$originalTax}-{$slug}.php", // prefixed od builtin
			"taxonomy-{$originalTax}.php",
			"taxonomy-{$unprefixedTax}-{$slug}.php", // with stripped prefix
			"taxonomy-{$unprefixedTax}.php",
			'taxonomy.php',
			'archive.php',
		));
	}



	public static function archiveTemplate($template)
	{
		$postTypes = array_filter((array) get_query_var('post_type'));

		$templates = array();

		if(count($postTypes) == 1){
			$postType = reset($postTypes);

			$originalType = $postType;
			$unprefixedType = WpLatteUtils::stripPrefix('post', $postType);

			$templates[] = "archive-{$originalType}.php";
			$templates[] = "archive-{$unprefixedType}.php";
		}

		$templates[] = 'archive.php';

		return locate_template($templates);
	}



	/**
	 * Overrides the default single (singular post) template.  Post templates can be loaded using a custom
	 * post template, by slug, or by ID.
	 *
	 * Attachment templates are handled slightly differently. Rather than look for the slug
	 * or ID, templates can be loaded by attachment-$mime[0]_$mime[1].php,
	 * attachment-$mime[1].php, or attachment-$mime[0].php.
	 *
	 * @param string $template The default WordPress post template.
	 * @return string $template The theme post template after all templates have been checked for.
	 */
	public static function singularTemplate($template)
	{
		$templates = array();

		$post = get_queried_object();

		$originalType = $post->post_type;
		$unprefixedType = WpLatteUtils::stripPrefix('post', $post->post_type);

		$custom = get_post_meta(get_queried_object_id(), "_wp_{$unprefixedType}_template", true); // this also adds suport for Blog Posts templates

		if(!$custom){
			$custom = get_post_meta(get_queried_object_id(), "_wp_{$originalType}_template", true);
		}

		if($custom){
			$templates[] = $custom;
		}

		if(is_attachment()){
			$mimeType = explode('/', get_post_mime_type());

			$templates[] = "{$mimeType[0]}-{$mimeType[1]}.php";
			$templates[] = "attachment-{$mimeType[0]}-{$mimeType[1]}.php";
			$templates[] = "attachment-{$mimeType[0]}.php";
			$templates[] = "attachment-{$mimeType[1]}.php";

		}else{
			$templates[] = "{$originalType}-{$post->post_name}.php";
			$templates[] = "{$originalType}-{$post->ID}.php";
			if($originalType != $unprefixedType){
				$templates[] = "{$unprefixedType}-{$post->post_name}.php";
				$templates[] = "{$unprefixedType}-{$post->ID}.php";
			}
		}


		$templates[] = "{$originalType}.php"; // can be also post.php instead of single.php
		if($originalType != $unprefixedType){
			$templates[] = "{$unprefixedType}.php";
		}

		// Allow for WP standard 'single' templates for compatibility
		$templates[] = "single-{$originalType}.php";
		if($originalType != $unprefixedType){
			$templates[] = "single-{$unprefixedType}.php";
		}

		$templates[] = 'single.php';

		$templates[] = "singular.php";

		$templates = array_unique($templates);

		return locate_template($templates);
	}

}
