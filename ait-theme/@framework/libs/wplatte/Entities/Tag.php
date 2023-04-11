<?php


/**
 * The Tag Entity
 */
class WpLatteTagEntity extends WpLatteBaseEntity
{

	/** @var int ID of tag */
	protected $id;

	/** @var int ID of parent tag */
	protected $parentId;

	/** @var int Number of items in this tag */
	protected $count;



	public function __construct($tag)
	{
		if(is_numeric($tag)){
			$tag = get_tag($tag);
		}

		$this->id       = (int) $tag->term_id;
		$this->parentId = (int) $tag->parent;
		$this->count    = (int) $tag->count;
	}



	public function title()
	{
		return single_tag_title('', false);
	}



	public function description()
	{
		return tag_description($this->id);
	}
}
