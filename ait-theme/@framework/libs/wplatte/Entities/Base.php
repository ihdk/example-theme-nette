<?php


/**
 * The Base Entity
 *
 * All entities must extends this entity
 */
class WpLatteBaseEntity extends NObject
{

	/**
	 * Cache var for momoizing results
	 * @var array
	 */
	protected $cache = array();



	/**
	 * Magic getter for all properties in extended classes.
	 * If method with $name exists it is called otherwise if property exist with this name it is returned
	 * @param string $key Name of property or method
	 * @return mixed
	 */
	public function &__get($name)
	{
		$fn = self::camel2underscore($name);

		if(method_exists($this, $name) and is_callable(array($this, $name))){
			$return = $this->$name();
			return $return;

		}elseif(property_exists($this, $name)){
			$return = $this->$name;
			return $return;

		}elseif(substr($fn, 0, 3) == 'is_' and function_exists($fn)){ // handles all conditional tags
			$return = $fn();
			return $return;

		}else{
			trigger_error(sprintf("You maybe did a typo in template. Property or method with name '%s' doesn't exist in class '%s'.", $name, get_class($this)));
			$return = null;
			return $return;
		}
	}



	/**
	 * camelCase -> underscore_separated.
	 * @param  string
	 * @return string
	 */
	protected static function camel2underscore($s)
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1_', $s);
		$s = strtolower($s);
		return $s;
	}
}
