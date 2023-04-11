<?php


/**
 * WpLatte Macros specific for WordPress environment
 */
class WpLatteMacros extends NMacroSet
{
	public $config;



	/**
	 * Install macros
	 *
	 * @param NParser $parser
	 */
	public static function install(NLatteCompiler $compiler, $config = null)
	{
		$me = new self($compiler);

		$me->config = $config;

		$me->addMacro('_', array($me, 'macroTranslate'), array($me, 'macroTranslate'));

		$me->addMacro('getHeader', 'get_header(%node.word ? %node.word : null)');
		$me->addMacro('getFooter', 'get_footer(%node.word ? %node.word : null)');
		$me->addMacro('getSidebar', 'get_sidebar(%node.word ? %node.word : null)');

		$me->addMacro('searchForm', 'get_search_form()');

		$me->addMacro('widgetArea', 'dynamic_sidebar(%node.args);');

		$me->addMacro('wpHead', 'wp_head();');
		$me->addMacro('wpFooter', 'wp_footer();');

		$me->addMacro('menu', __CLASS__ . '::menu(%node.word, %node.array);');
		$me->addMacro('wpNavMenu', 'wp_nav_menu(%node.args);');

		$me->addMacro('title', 'wp_title(%node.args)');

		$me->addMacro('commentForm', 'comment_form(%node.args);');

		$me->addMacro('prevCommentsLink', 'previous_comments_link(__(%node.word, \'wplatte\'))');
		$me->addMacro('nextCommentsLink', 'next_comments_link(__(%node.word, \'wplatte\'))');

		$me->addMacro('prevPostsLink', 'previous_posts_link(%node.args)');
		$me->addMacro('nextPostsLink', 'next_posts_link(%node.args)');

		$me->addMacro('prevPostLink', 'previous_post_link("%link", %node.args)');
		$me->addMacro('nextPostLink', 'next_post_link("%link", %node.args)');

		$me->addMacro('pagination', 'echo ' . __CLASS__ . '::pagination(%node.array);');

		$me->addMacro('prevImageLink', 'previous_image_link(%node.args)');
		$me->addMacro('nextImageLink', 'next_image_link(%node.args)');

		$me->addMacro('breadcrumbs', 'echo ' . __CLASS__ . '::breadcrumbs(%node.array)');

		$me->addMacro('includePart', array($me, 'macroIncludePart'));

		$me->addMacro('loop', array($me, 'macroLoop'), array($me, 'macroLoopEnd'));

		$me->addMacro('customQuery', array($me, 'macroCustomQuery'));

		$me->addMacro('customLoop', array($me, 'macroCustomLoop'), array($me, 'macroLoopEnd'));

		$me->addMacro('comments', 'if(is_singular() and (comments_open() or get_comments_number() != \'0\')): comments_template(%node.args); endif;');
		$me->addMacro('loopComments', '', array($me, 'macroLoopComments'));

		$me->addMacro('languageAttributes', 'language_attributes();');

		$me->addMacro('doAction', 'do_action(%node.args)');

		do_action('wplatte-macros', $compiler, $config);
	}



	/**
	 * {includePart "file", name, params}
	 * Alias for get_template_part()
	 */
	public function macroIncludePart(NMacroNode $node, NPhpWriter $writer)
	{
		$slug = $node->tokenizer->fetchWord();
		$params = self::prepareIncludePartParams($writer->formatArray());

		$slug = $writer->formatWord($slug);
		$name = $writer->formatWord($params['name']);

		return $writer->write('NCoreMacros::includeTemplate(' . __CLASS__ . '::getTemplatePart(' . $slug . ', ' . $name . '), ' . $params['params'] . ' + get_defined_vars(), $_l->templates[%var])->render()', $this->getCompiler()->getTemplateId());
	}



	/**
	 * {__ 'something'}
	 * {__ '%s something'|printf:"another"}
	 * {_n 'something', 'somethings', 3}
	 * {_x 'something', 'context'}
	 * {_nx 'something', 'somethings', 'context', 3}
	 */
	function macroTranslate(NMacroNode $node, $writer)
	{
		$fn = '__  ('; // for Theme-check

		if($node->closing){
			return $writer->write("echo  %modify({$fn}ob_get_clean(), 'wplatte'))");
		}elseif ($node->isEmpty = ($node->args !== '')){

			$name = $node->tokenizer->fetchWord();
			$args = $writer->formatArgs();

			if(!in_array($name, array('_', 'n', 'x', 'nx'))){
				if($writer->canQuote($node->tokenizer))
					$name = "'$name'";

				return $writer->write("echo %modify($fn{$name}, 'wplatte'))");
			}

			return $writer->write("echo %modify(_{$name}({$args}, 'wplatte'))");

		}else{
			return 'ob_start()';
		}
	}



	/**
	 * {loop as $post}
	 */
	public function macroLoop(NMacroNode $node, NPhpWriter $writer)
	{
		$node->tokenizer->fetchWord(); // consume 'as'

		$node->openingCode =  $writer->write('<?php foreach($iterator = new WpLatteLoopIterator() as %node.word): ?>');
	}



	/**
	 * {customLoop from $customQuery as $item}
	 */
	public function macroCustomLoop(NMacroNode $node, NPhpWriter $writer)
	{
		$node->tokenizer->fetchWord(); // consume 'from'
		$placeholder = $node->tokenizer->fetchWord(); // take $query
		$node->tokenizer->fetchWord(); // consume 'as'

		return $writer->write('foreach ($iterator = new WpLatteLoopIterator(' . $placeholder . ') as %node.word):');
	}



	/**
	 * {customQuery as $query,
	 * 		type => <cpt type>,
	 * 		tax => <taxonomy name>,
	 * 		cat => <term id>,
	 * 		limit => <limit>,
	 * 		order => <order>
	 * 	}
	 * {customQuery as $query, array(<args>)}
	 * {customQuery as $query, $args}
	 */
	public function macroCustomQuery(NMacroNode $node, NPhpWriter $writer)
	{
		$node->tokenizer->fetchWord(); // consume as 'as'
		$placeholder = $node->tokenizer->fetchWord(); // take $query
		$args = $writer->formatArray();

		return $writer->write($placeholder . ' = WpLatteMacros::prepareCustomWpQuery(' . $args . ');');
	}



	/**
	 * {/loop}
	 * {/customLoop}
	 */
	public function macroLoopEnd(NMacroNode $node, NPhpWriter $writer)
	{
		$code = 'endforeach;';

		if($node->name == 'customLoop')
			$code .= ' wp_reset_postdata();';

		return $code;
	}



	/**
	 * {loopComments as <current comment variable name>}
	 */
	public function macroLoopComments(NMacroNode $node, NPhpWriter $writer)
	{
		$node->tokenizer->fetchWord(); // consume 'as', no needed, it's just for readability

		$tpldId = $this->getCompiler()->getTemplateId() . time();

		$node->openingCode = $writer->write(
			"<?php
				global \$__wplattetemplate__;
				\$__wplattetemplate__ = \$template;
				if(!function_exists('__WpLatteListComments_{$tpldId}')):
					function __WpLatteListComments_{$tpldId}(\$__comment__, \$args, \$depth){
						global \$__wplattetemplate__, \$post;
						\$template = \$__wplattetemplate__; // bypassing \$template object
						\$GLOBALS['comment'] = \$__comment__;
						%node.word = WpLatte::createEntity('Comment', \$__comment__, \$post->post_author);
						%node.word->loopData = array('args' => \$args, 'depth' => \$depth);
			?>");

		$node->closingCode =  "
			<?php 	} /* __WpLatteListComments_{$tpldId} */
				endif;
				wp_list_comments(array('callback' => '__WpLatteListComments_{$tpldId}', 'style' => 'ol'));
			?>";
	}



	// ============================================================
	// Functions
	// ------------------------------------------------------------


	public static function menu($location, $args)
	{
		$defaults = array(
			'theme_location' => $location,
		);

		$args = apply_filters('wplatte-menu-args', $location, array_merge($args, $defaults));

		wp_nav_menu($args);
	}



	public static function breadcrumbs($args)
	{
		if(is_numeric(key($args))){
			$args = $args[0];
		}

		return WpLatteBreadcrumbs::breadcrumbs($args);
	}



	/**
	 * Loop pagination function for paginating loops with multiple posts.  This should be used on archive, blog, and
	 * search pages.  It is not for singular views.
	 *
	 * @author    Justin Tadlock <justin@justintadlock.com>
	 * @copyright Copyright (c) 2010 - 2012, Justin Tadlock
	 * @link      http://themehybrid.com/docs/tutorials/loop-pagination
	 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
	 * @uses      paginate_links() Creates a string of paginated links based on the arguments given.
	 * @param     array $args Arguments to customize how the page links are output.
	 * @return    string $pageLinks
	 */
	public static function pagination($args = array())
	{
		global $wp_rewrite, $wp_query;

		// Get the max number of pages.
		$maxNumPages = isset($args['max']) ? intval($args['max']) : intval($wp_query->max_num_pages);

		// If there's not more than one page, return nothing.
		if(1 >= $maxNumPages){
			return;
		}

		// Get the current page.
		$current = (get_query_var('paged') ? absint(get_query_var('paged')) : 1);

		// Get the pagination base.
		$paginationBase = $wp_rewrite->pagination_base;

		$defaults = array(
			'base'         => add_query_arg('paged', '%#%'),
			'format'       => '',
			'total'        => $maxNumPages,
			'current'      => $current,
			'prev_next'    => false,
			//'prev_text'  => __( '&laquo; Previous' ), // This is the WordPress default.
			//'next_text'  => __( 'Next &raquo;' ), // This is the WordPress default.
			'show_all'     => true,
			'end_size'     => 1,
			'mid_size'     => 2,
			'add_fragment' => '',
			'type'         => 'plain',

			// Begin loop_pagination() arguments.
			'before'       => '',
			'after'        => '',
			'echo'         => true,
		);

		// Add the $base argument to the array if the user is using permalinks.
		if($wp_rewrite->using_permalinks() and !is_search()){
			$pagenumLink = html_entity_decode(get_pagenum_link());
			$queryArgs = array();
			$urlParts = explode('?', $pagenumLink);

			if(isset($urlParts[1])){
				wp_parse_str($urlParts[1], $queryArgs);
			}

			$pagenumLink = remove_query_arg(array_keys($queryArgs), $pagenumLink);

			$defaults['base'] = user_trailingslashit(trailingslashit($pagenumLink) . "{$paginationBase}/%#%");
			if(!empty($queryArgs)){
				$defaults['base'] .= '?' . build_query($queryArgs);
			}
		}

		$args = apply_filters('wplatte-pagination-args', $args);

		$args = wp_parse_args($args, $defaults);

		// Don't allow the user to set this to an array.
		if($args['type'] == 'array'){
			$args['type'] = 'plain';
		}

		// Get the paginated links.
		$pageLinks = paginate_links($args);

		// Remove 'page/1' from the entire output since it's not needed.
		/*$pageLinks = str_replace(array(
				"{$paginationBase}/1/",
				"{$paginationBase}/1",
				"?paged=1",
				'&#038;paged=1',
			),
			'',
			$pageLinks
		);*/

		$pageLinks = $args['before'] . $pageLinks . $args['after'];

		// Allow devs to completely overwrite the output.
		$pageLinks = apply_filters('wplatte-pagination-output', $pageLinks);

		return $pageLinks;
	}



	// ============================================================
	// Helper methods for macros
	// ------------------------------------------------------------



	/**
	 * Prepeare WP_Query with given arguments
	 * @param  array $args    Arguments
	 * @return WP_Query       New wp_query object
	 */
	public static function prepareCustomWpQuery($args)
	{
		if(!isset($args[0])){
			$defaults = array(
				'type'    => '', // cpt name
				'tax'     => '', // taxonomy name
				'cat'     => '', // term id (category)
				'field'   => 'id',
				'limit'   => 0, // limit, 0: get_option('posts_per_page')
				'orderby' => 'menu_order', // orderby
				'order'   => 'ASC', // order
				'status'  => 'publish',
			);

			$o = (object) array_merge($defaults, $args);

			$query = array(
				'post_type'      => WpLatteUtils::addPrefix('post', $o->type),
				'post_status'    => $o->status,
			);

			if(isset($o->id)){
				// by default 0 means all posts, but we want -1 and it means none posts
				$o->id = $o->id == 0 ? -1 : $o->id;
				$query['p'] = $o->id;
				$query['limit'] = 1;
			}else{
				$query['orderby'] = $o->orderby;
				$query['order'] = $o->order;
				$query['posts_per_page'] = $o->limit;
			}

			if($o->cat){
				$query['tax_query'] = array(
					array(
						'taxonomy' => WpLatteUtils::addPrefix('taxonomy', $o->tax),
						'field' => $o->field,
						'terms' => $o->cat,
					)
				);
			}
		}else{
			$query = $args[0];

			if(isset($query['post_type']))
				$query['post_type'] = WpLatteUtils::addPrefix('post', $query['post_type']);


			if(isset($query['tax_query'])){
				foreach($query['tax_query'] as $i => $taxQuery){
					if(isset($taxQuery['taxonomy']))
						$query['tax_query'][$i]['taxonomy'] = WpLatteUtils::addPrefix('taxonomy', $taxQuery['taxonomy']);
				}
			}
		}

		$query = apply_filters('wplatte-custom-wpquery-args', $query, isset($o) ? $o : null);

		return new WpLatteWpQuery($query);
	}



	/**
	 * Load a template part into a template
	 * Alias for get_template_part()
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string $name The name of the specialised template.
	 */
	public static function getTemplatePart($slug, $name = null)
	{
		do_action("get_template_part_{$slug}", $slug, $name);

		$templates = array();

		// create name of file
		// e.g. 'parts/entry-date-format-<NAME>-loop.php'
		// e.g. 'parts/entry-date-format-<NAME>.php'
		if($name){
			if(!is_singular()) $templates[] = "{$slug}-{$name}-loop.php";
			$templates[] = "{$slug}-{$name}.php";
		}

		// e.g. 'parts/entry-date-format-loop.php'
		// e.g. 'parts/entry-date-format.php'
		if(!is_singular()) $templates[] = "{$slug}-loop.php";
		$templates[] = "{$slug}.php";

		$templates = apply_filters("wplatte-get-template-part", $templates, $slug, $name);

		// filter can return path to already existing file
		if(is_string($templates) and file_exists($templates)){
			return $templates;
		}

		return locate_template($templates, false, false);
	}



	/**
	 * Helper for macroIncludePart
	 * @param  string $params
	 * @return array
	 */
	private static function prepareIncludePartParams($params)
	{
		$return = array('name' => false, 'params' => 'array()');

		$p = explode(',', substr($params, 6, -1));

		if(count($p) >= 1 and !NStrings::contains($p[0], '=>')){
			$p = is_string($p) ? trim($p) : $p;
			if(!empty($p)){
				$return['name'] = !NStrings::startsWith($p[0], '$') ? substr($p[0], 1, -1) : $p[0];
				unset($p[0]);
				$return['params'] = implode(',', $p);
			}
		}elseif(count($p) >= 1 and NStrings::contains($p[0], '=>')){
			if(!empty($p)){
				$return['name'] = false;
				$return['params'] = implode(',', $p);
			}
		}

		$return['params'] = 'array(' . $return['params'] . ')';

		return $return;
	}


}


