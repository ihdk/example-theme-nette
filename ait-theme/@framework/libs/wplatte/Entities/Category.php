<?php


/**
 * The Category Entity
 */
class WpLatteCategoryEntity extends WpLatteBaseEntity
{

	/** @var int ID of category */
	protected $id;

	/** @var int ID of parent category */
	protected $parentId;

	/** @var int Number of items in this category */
	protected $count;

	/** @var string slug of category */
	protected $slug;

	protected $rawTitle;


	public function __construct($category)
	{
		if(is_numeric($category))
			$category = get_category($category);

		$this->id       = (int) $category->term_id;
		$this->parentId = (int) $category->parent;
		$this->count    = (int) $category->count;
		$this->slug     = $category->slug;
		$this->rawTitle = $category->name;
	}



	public function title()
	{
		return apply_filters('single_cat_title', $this->rawTitle);
	}



	public function description()
	{
		return category_description($this->id);
	}



	public function url()
	{
		return get_category_link($this->id);
	}



	public function id()
	{
		return $this->id;
	}
}
