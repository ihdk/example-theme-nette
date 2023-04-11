<?php


/**
 * The Comment Entity
 */
class WpLatteCommentEntity extends WpLatteBaseEntity
{

	/**
	 * The comment ID
	 * @var int
	 */
	protected $id;

	/**
	 * The ID of the post/page that this comment responds to
	 * @var int
	 */
	protected $postId;

	/**
	 * The parent comment's ID for nested comments
	 * @var WpLatteCommentEntity
	 */
	protected $parent;

	/**
	 * The comment author's ID if s/he is registered (0 otherwise)
	 * @var type
	 */
	protected $commenterId;

	/**
	 * Post author id
	 * @var int
	 */
	protected $postAuthorId;

	/**
	 * The comment's karma
	 * @var int
	 */
	protected $karma;

	/**
	 * The comment approval level
	 * @var bool
	 */
	protected $isApproved;

	/**
	 * The commenter's user agent (browser, operating system, etc.
	 * @var string
	 */
	protected $browser;

	/**
	 * Comment Author
	 * @var WpLatteCommentAuthorEntity
	 */
	protected $author;

	protected $rawDate;

	/**
	 * Loop data for comments as $args, $depth
	 * @var array
	 */
	public $loopData;


	public function __construct($comment, $postAuthorId)
	{
		$this->postAuthorId = (int) $postAuthorId;

		$this->author = WpLatte::createEntity('CommentAuthor', $comment, $this->postAuthorId);

		$this->id           = (int) $comment->comment_ID;
		$this->postId       = (int) $comment->comment_post_ID;
		$this->parent       = (int) $comment->comment_parent;
		$this->commenterId  = (int) $comment->user_id;

		$this->rawDate      = $comment->comment_date;

		$this->karma        = (int) $comment->comment_karma;
		$this->isApproved   = (bool) $comment->comment_approved;
		$this->browser      = $comment->comment_agent;
	}



	/**
	 * Alias for comment_text()
	 */
	public function text()
	{
		$comment = get_comment($this->id);
		return apply_filters('comment_text', get_comment_text(), $comment);
	}



	public function isNormal()
	{
		switch($this->type){
			case 'pingback':
			case 'trackback':
				return false;
			default:
				return true;
		}
	}



	public function htmlClass($class = '', $withAttr = true)
	{
		$class = implode(' ', get_comment_class($class, null, null));

		if($withAttr){
			return ' class="' . $class . '" ';
		}else{
			return $class;
		}
	}


	public function htmlId($prefix = '', $withAttr = true)
	{
		$id = "{$prefix}comment-{$this->id}";

		if($withAttr){
			return ' id="' . $id . '" ';
		}else{
			return $id;
		}
	}



	public function editLink($linkText)
	{
		ob_start();
		edit_comment_link($linkText);
		return ob_get_clean();
	}



	public function replyLink($linkText)
	{
		$a = array_merge($this->loopData['args'], array(
			'reply_text' => $linkText,
			'depth' => $this->loopData['depth'],
		));

		return get_comment_reply_link($a);
	}



	public function url()
	{
		return get_comment_link($this->id);
	}



	/**
	 * Retrieve the comment time of the current comment.
	 * Alias for get_comment_time()
	 *
	 * @param  string  $d   Optional. The format of the time (defaults to user's config)
	 * @param  boolean $gmt Whether to use the GMT date
	 * @return string       The formatted time
	 */
	public function time($d = '', $gmt = false)
	{
		return get_comment_time($d, $gmt);
	}



	/**
	 * Retrieve the comment date of the current comment.
	 * Alias for get_comment_date()
	 *
	 * @param  string $d The format of the date (defaults to user's config)
	 * @return string    The comment's date
	 */
	public function date($d = '')
	{
		return get_comment_date($d);
	}



	/**
	 * The comment's type if meaningfull (pingback|trackback), empty for normal comments
	 * @return string The comment type
	 */
	public function type()
	{
		return get_comment_type();
	}

}
