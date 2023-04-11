<?php


/**
 * The Comment Author Entity
 */
class WpLatteCommentAuthorEntity extends WpLatteBaseEntity
{

	/**
	 * The comment author's ID if s/he is registered (0 otherwise)
	 * @var type
	 */
	protected $id;

	/**
	 * Post author id
	 * @var int
	 */
	protected $postAuthorId;


	public function __construct($comment, $postAuthorId)
	{
		$this->id  = (int) $comment->user_id;
		$this->postAuthorId = $postAuthorId;
	}



	/**
	 * Alias for comment_author()
	 * @return string The comment author
	 */
	public function __toString()
	{
		return apply_filters('comment_author', get_comment_author());
	}



	public function isPostAuthor()
	{
		return $this->id == $this->postAuthorId;
	}



	public function link()
	{
		return get_comment_author_link();
	}



	public function avatar($size = '96')
	{
		return get_avatar($this->email(), $size);
	}



	public function email()
	{
		return apply_filters('author_email', get_comment_author_email());
	}



	public function url()
	{
		return apply_filters('comment_url', get_comment_author_url());
	}

}
