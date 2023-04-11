<?php


/**
 * Iterator for WordPress Loop with nice features as isLast(), isFirst(), etc
 */
class WpLatteLoopIterator implements Iterator
{

	/** @var WP_Query|WpLatteWpQuery */
	protected $loop;

	/** @var int */
	protected $counter = 0;

	/** @var bool */
	protected $valid = true;


	/**
	 * Constructor. You don't say.
	 * @param WpLatteWpQuery $wpQuery New wp_query object
	 */
	public function __construct($wpQuery = null)
	{
		global $wp_query;

		$this->loop = $wpQuery ? $wpQuery : $wp_query;
	}


	// ============================================================
	// Iterator implementation
	// ------------------------------------------------------------

	public function valid()
	{
		if($this->loop->have_posts()){
			$this->valid = true;
			return true;
		}else{
			$this->valid = false;
			return false;
		}
	}



	public function current()
	{
		$this->loop->the_post();

		global $post;

		return WpLatte::createEntity('Post', $post);
	}



	public function rewind()
	{
		$this->loop->rewind_posts();

		$this->counter = $this->valid ? 1 : 0;
	}



	public function key()
	{
		return $this->counter;
	}



	public function next()
	{
		if($this->valid)
			$this->counter++;
	}



	// ============================================================
	// Helper counter methods
	// ------------------------------------------------------------

	/**
	 * Is the current element the first one?
	 * @param  int  grid width
	 * @return bool
	 */
	public function isFirst($width = NULL)
	{
		return $this->counter === 1 || ($width && $this->counter !== 0 && (($this->counter - 1) % $width) === 0);
	}



	/**
	 * Is the current element the last one?
	 * @param  int  grid width
	 * @return bool
	 */
	public function isLast($width = NULL)
	{
		$hasNext = (($this->counter + 1) <= $this->loop->post_count);
		return !$hasNext || ($width && ($this->counter % $width) === 0);
	}



	/**
	 * Is the counter odd?
	 * @return bool
	 */
	public function isOdd()
	{
		return $this->counter % 2 === 1;
	}



	/**
	 * Is the counter even?
	 * @return bool
	 */
	public function isEven()
	{
		return $this->counter % 2 === 0;
	}



	/**
	 * Returns the counter.
	 * @return int
	 */
	public function getCounter()
	{
		return $this->counter;
	}



	// ============================================================
	// NObject behaviour
	// ------------------------------------------------------------

	/**
	 * Call to undefined method.
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public function __call($name, $args)
	{
		return NObjectMixin::call($this, $name, $args);
	}



	/**
	 * Returns property value. Do not call directly.
	 * @param  string  property name
	 * @return mixed   property value
	 * @throws MemberAccessException if the property is not defined.
	 */
	public function &__get($name)
	{
		return NObjectMixin::get($this, $name);
	}



	/**
	 * Sets value of a property. Do not call directly.
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public function __set($name, $value)
	{
		return NObjectMixin::set($this, $name, $value);
	}



	/**
	 * Is property defined?
	 * @param  string  property name
	 * @return bool
	 */
	public function __isset($name)
	{
		return NObjectMixin::has($this, $name);
	}



	/**
	 * Access to undeclared property.
	 * @param  string  property name
	 * @return void
	 * @throws MemberAccessException
	 */
	public function __unset($name)
	{
		NObjectMixin::remove($this, $name);
	}
}
