<?php


/**
 * Template stored in file
 */
class WpLatteFileTemplate extends NFileTemplate
{
	/**
	 * Renders template to output.
	 * @return void
	 */
	public function render()
	{
		if ($this->getFile() == NULL) { // intentionally ==
			throw new InvalidStateException("Template file name was not specified.");
		}

		$cache = new NCache($storage = $this->getCacheStorage(), 'wplatte');
		if ($storage instanceof NPhpFileStorage) {
			$storage->hint = str_replace(dirname(dirname($this->getFile())), '', $this->getFile());
		}
		$cached = $compiled = $cache->load($this->getFile());

		if ($compiled === NULL) {
			try {
				$compiled = "<?php\n\n// source file: {$this->getFile()}\n\n?>" . $this->compile();

			} catch (NTemplateException $e) {
				$e->setSourceFile($this->getFile());
				throw $e;
			}

			$cache->save($this->getFile(), $compiled, array(
				NCache::FILES => $this->getFile(),
				NCache::CONSTS => apply_filters('wplatte-cache-constants', array('NFramework::REVISION', 'WPLATTE_VERSION')),
			));
			$cached = $cache->load($this->getFile());
		}

		if ($cached !== NULL && $storage instanceof NPhpFileStorage) {
			NLimitedScope::load($cached['file'], $this->getParameters());
		} else {
			NLimitedScope::evaluate($compiled, $this->getParameters());
		}
	}
}
