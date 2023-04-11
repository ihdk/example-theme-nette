<?php


/*include*/
spl_autoload_register('WpLatteAutoload');



/**
 * Loads the given class or interface.
 *
 * @param string $class The name of the class to load.
 * @return void
 * @throws Exception
 */
function WpLatteAutoload($class)
{
	//$dir = dirname(__FILE__);
	$dir = get_template_directory()."/ait-theme/@framework/";

	if(substr($class, 0, 7) == 'WpLatte'){

		$file = $dir . "/{$class}.php";

		if(file_exists($file)){
			require_once $file;
			return;
		}

		$file = $dir . '/' . substr($class, 7) . '.php';

		if(file_exists($file)){
			require_once $file;
			return;
		}

		$file = $dir . '/Entities/' . substr($class, 7, -6) . '.php';

		if(!file_exists($file)){
			throw new Exception("Unable to find file '$file'.");
		}

		require_once $file;
		return;
	}
}
/*include*/
