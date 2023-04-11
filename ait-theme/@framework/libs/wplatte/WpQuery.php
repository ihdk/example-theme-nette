<?php


/**
 * Wrapper class for WP_Query
 */
class WpLatteWpQuery extends WP_Query
{
	/**
	 * Magic getter
	 * @param string $key Name of property or method
	 * @return mixed
	 */
	public function __get($name)
	{
		$fn = self::camel2underscore($name);

		if(isset($this->$name)){
			return $this->$name;

		}elseif(isset($this->$fn)){
			return $this->$fn;

		}elseif(method_exists($this, $fn) and is_callable(array($this, $fn))){
			return $this->$fn();

		}else{
			trigger_error(sprintf("You maybe did a typo in template. Property or method with name '%s' doesn't exist in class '%s'.", $name, get_class($this)));
			return null;
		}
	}




	/**
	 * camelCase -> underscore_separated.
	 * @param  string
	 * @return string
	 */
	private static function camel2underscore($s)
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1_', $s);
		$s = strtolower($s);
		return $s;
	}
}
