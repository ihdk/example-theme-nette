<?php


/**
 * The Post Author Entity
 */
class WpLattePostAuthorEntity extends WpLatteBaseEntity
{

	/**
	 * The Author ID
	 * @var int
	 */
	protected $id;

	/**
	 * The Author's email
	 * @var string
	 */
	protected $email;

	/**
	 * Authors name
	 * @var string
	 */
	protected $displayName;



	/**
	 * Constructor
	 * @param int|WP_User $authorId Author's ID
	 */
	public function __construct($author)
	{
		if(is_numeric($author)){
			$a = get_userdata($author);
			if($a){
				$this->id    = (int) $a->ID;
				$this->email = $a->user_email;
				$this->displayName =  $a->display_name;
			}
		}else{
			$this->id = (int) $author->ID;
			$this->email = $author->user_email;
			$this->displayName =  $author->display_name;
		}
	}



	/**
	 * Gets URL of authors posts
	 * @return string
	 */
	public function postsUrl()
	{
		return get_author_posts_url($this->id);
	}



	/**
	 * Gets description of author with HTML
	 * @return string
	 */
	public function bio()
	{
		return get_the_author_meta('description', $this->id);
	}



	public function isMulti()
	{
		return is_multi_author();
	}



	/**
	 * Gets author's avatar
	 * @param int $size Size of avatar
	 * @return string
	 */
	public function avatar($size = '96')
	{
		return get_avatar($this->email, $size);
	}



	/**
	 * Gets author's meta
	 * @param  String  $metaboxId ID of your registered metabox
	 * @return mixed
	 */
	public function meta($metaboxId, $key = null)
	{
		$metaboxKey = "_user_{$metaboxId}";

		if($return = WpLatteObjectCache::load("metabox-{$metaboxKey}-{$key}-{$this->id}")){
			return $return;
		}

		$usermeta = get_the_author_meta($metaboxKey, $this->id); // returns array of data from custom metabox

		if($usermeta === ""){
			$usermeta = get_post_meta($metaboxId, $this->id); // default wp meta fields metabox
		}

		// merge default user meta before saved in database
		// $usermeta = apply_filters('wplatte-post-meta', $usermeta, $metaboxId, $metaboxKey, $key, $this->isCpt, $this->type);

		if(is_array($usermeta)){
			if($key !== null and isset($usermeta[$key])){
				$return = $usermeta[$key];
			}else{
				$return = (object) $usermeta;
			}
		}else{
			$return = $usermeta;
		}

		WpLatteObjectCache::save("metabox-{$metaboxKey}-{$key}-{$this->id}", $return);
		return $return;
	}



	public function __toString()
	{
		return apply_filters('the_author', $this->displayName);
	}

}
