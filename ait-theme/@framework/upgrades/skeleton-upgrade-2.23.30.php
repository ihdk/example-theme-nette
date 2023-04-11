<?php


class AitSkeletonUpgrade22330
{

	protected $errors = array();



	public function execute()
	{
		// remove old .htaccess in cache dir
		@unlink(aitPaths()->dir->cache . "/.htaccess");
		// add new .htaccess in cache dir
		@copy(get_template_directory() . '/.htaccess', aitPaths()->dir->cache . "/.htaccess");
		return $this->errors;
	}
}
