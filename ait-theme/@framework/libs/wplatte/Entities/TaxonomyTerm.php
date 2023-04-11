<?php


/**
 * The Taxonomy Term Entity
 */
class WpLatteTaxonomyTermEntity extends WpLatteBaseEntity
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

	protected $taxonomy;


	public function __construct($term, $taxonomy = 'category')
	{
		if(is_numeric($term)){
			$term = get_term($term, $taxonomy);
		}

		$this->id       = (int) $term->term_id;
		$this->parentId = (int) $term->parent;
		$this->count    = (int) $term->count;
		$this->taxonomy = $term->taxonomy;
		$this->slug     = $term->slug;
		$this->rawTitle = $term->name;
	}



	public function title()
	{
		return apply_filters('single_term_title', $this->rawTitle);
	}



	public function description()
	{
		return term_description($this->id, $this->taxonomy);
	}



	public function url()
	{
		return get_term_link($this->id, $this->taxonomy);
	}
}
