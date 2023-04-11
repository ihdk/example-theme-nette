<?php


if(!class_exists('NPresenter')){ class NPresenter{} }


/**
 * Fake Presenter class for auto setup layout template file
 */
class WpLatteFakePresenter extends NPresenter
{
	public $paths = array();

	/**
	 * Finds layout template file name.
	 * @throws FileNotFoundException If no layout file exists
	 * @return string
	 */
	public function findLayoutTemplateFile()
	{
		extract($this->paths);

		$prefixes = array('@', '');

		$_404 = array();

		foreach($prefixes as $prefix){
			$mask = "/%slayout.php";
			$layout = sprintf($mask, $prefix);

			// is it in child theme?
			$layoutPath = $child . $layout;
			if(file_exists($layoutPath))
				return $layoutPath;
			else
				$_404[] = $layoutPath;

			// is it in theme?
			$layoutPath = $theme . $layout;
			if(file_exists($layoutPath))
				return $layoutPath;
			else
				$_404[] = $layoutPath;
		}

		if(!empty($_404)){
			throw new FileNotFoundException(sprintf("One of these layout template files must exists:\n%s\n", implode(", \n", array_unique($_404))));
		}
	}
}
