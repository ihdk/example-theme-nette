<?php


class AitBackup
{
	const DUMMY_IMG_URL = 'https://demo.ait-themes.club/demo-images/';

	protected static $wpContentTables = array(
		'posts',
		'postmeta',
		'terms',
		'termmeta',
		'term_taxonomy',
		'term_relationships',
		'comments',
		'commentmeta'
	);

	protected static $isExportingDemoContent = false;
	protected static $isImportingDemoContent = false;
	protected static $currentOperation = '';


	// ====================================================
	// Export
	// ----------------------------------------------------



	/**
	 * Export
	 * @param  string $whatToExport What to export: all, theme-options, content, wp-options
	 * @return void
	 */
	public static function export($whatToExport)
	{
		self::$currentOperation = 'export';

		@set_time_limit(0);

		if($whatToExport == 'demo-content'){
			$whatToExport = 'all';
			self::$isExportingDemoContent = true;

			self::addOnAfterDumpAttachmentsCallback();
		}

		$method = AitUtils::id2class($whatToExport, '', 'dump');
		if(!method_exists(__CLASS__, $method)){
			throw new Exception(sprintf(__("Export method '%s' does not exist. Something is wrong.", 'ait-admin'), $method));
		}

		$content = array();
		$content[$whatToExport] = self::$method();
		$content[$whatToExport] = self::processUrls($content[$whatToExport]);

		$data = @gzcompress(base64_encode(serialize($content)), 9);
		if(!$data){
			throw new Exception(sprintf(__("Export could not be compressed via gzcompress function. Check your PHP settings.", 'ait-admin'), $method));
		}

		return $data;
	}



	protected static function addOnAfterDumpAttachmentsCallback()
	{
		add_action('ait-after-dump-attachments', function($attachments, $originalImages, $isExportingDemoContent){
			if($isExportingDemoContent and file_exists(__DIR__ . '/AitAfterDumpAttachments.php')){
				require __DIR__ . '/AitAfterDumpAttachments.php';
				$zipName = $attachments[0]['basename'];
				AitAfterDumpAttachments::run($zipName, $originalImages);
			}
		}, 10, 3);
	}



	public static function exportToFile($whatToExport, $file)
	{
		$dir = dirname($file);
		$d = AitUtils::mkdir($dir);
		if($d){
			$data = self::export($whatToExport);
			$result = @file_put_contents($file, $data);
			return $result;
		}else{
			throw new Exception(sprintf(__("Directory '%s' can not be created.", 'ait-admin'), $dir));
		}
	}



	protected static function dumpAll()
	{
		$d1 = self::dumpContent();
		$d2 = self::dumpThemeOptions();
		$d3 = self::dumpWpOptions();
		$options = array_merge_recursive($d2, $d3);

		return array_merge($d1, $options);
	}



	protected static function dumpWpOptions()
	{
		global $wpdb;

		$dump = array();

		$options = array(
			'theme_mods_' . AIT_CURRENT_THEME,
			'sidebars_widgets',
			'show_on_front',
			'page_on_front',
			'page_for_posts',
			'widget_%',
			'blogname',
			'blogdescription',
			'polylang',
			'uploads_use_yearmonth_folders',
			'permalink_structure',
		);

		$options = apply_filters('ait-backup-wpoptions', $options, self::$isExportingDemoContent);

		$where = array();

		foreach($options as $opt){
			if(AitUtils::contains($opt, '%')){
				$operator = 'LIKE';
			}else{
				$operator = '=';
			}
			$where[] = $wpdb->prepare("`option_name` $operator %s", $opt);
		}

		$where = implode(' OR ', $where);

		$sql = "SELECT `option_name`, `option_value`, `autoload` FROM `{$wpdb->options}` WHERE $where;";

		$dump['options'] = $wpdb->get_results($sql, ARRAY_A);

		if($dump['options'] === false and $wpdb->last_error){
			throw new Exception($wpdb->last_error);
		}

		// Find and rename theme_mods_<theme>
		foreach($dump['options'] as $i => $row){
			if($row['option_name'] == 'theme_mods_' . AIT_CURRENT_THEME){
				$dump['options'][$i]['option_name'] = str_replace('theme_mods_' . AIT_CURRENT_THEME, 'theme_mods_%theme%', $row['option_name']);
				break;
			}
		}

		return $dump;
	}



	protected static function dumpThemeOptions()
	{
		global $wpdb;

		$dump = array();

		$theme = esc_sql(AIT_CURRENT_THEME);
		$where = " `option_name` LIKE '\_ait\_{$theme}\_%\_opts%'";
		$sql = "SELECT `option_name`, `option_value`, `autoload` FROM `{$wpdb->options}` WHERE $where;";

		$dump['options'] = $wpdb->get_results($sql, ARRAY_A);

		if($dump['options'] === false and $wpdb->last_error){
			throw new Exception($wpdb->last_error);
		}

		foreach($dump['options'] as $i => $row){
			$dump['options'][$i]['option_name'] = str_replace("_{$theme}_", '_%theme%_', $row['option_name']);
		}

		return $dump;
	}



	protected static function dumpContent()
	{
		global $wpdb;

		$dump = array();

		// Dump content from default wp tables
		foreach(self::$wpContentTables as $table){
			$where = '';
			if($table == 'posts' and self::$isExportingDemoContent){
				$where = " WHERE (post_status != 'auto-draft' and post_status !=  'trash') and post_type != 'revision'";
			}elseif($table == 'comments' and self::$isExportingDemoContent){
				$where = " WHERE comment_approved = '1'";
			}
			$result = $wpdb->get_results("SELECT * FROM {$wpdb->$table}{$where}", ARRAY_A);
			$dump[$table] = $result ? $result : array();
		}

		// Dump content from custom tables
		$customTables = apply_filters('ait-backup-content-custom-tables', array(), self::$isExportingDemoContent);

		if(!empty($customTables) and is_array($customTables)){
			foreach($customTables as $table){
				if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'") == $wpdb->prefix . $table){
					$escTable = esc_sql($table);
					$reuslt = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$escTable}", ARRAY_A);
					$dump[$table] = $reuslt ? $reuslt : array();
				}
			}
		}

		self::dumpAttachments($dump);

		return $dump;
	}



	protected static function dumpAttachments(&$dump)
	{

		$uploadsDir = realpath(aitPaths()->dir->uploads);
		$uploadsUrl = aitPaths()->url->uploads;

		$mimes = array_keys(get_allowed_mime_types());

		$exts = array();
		foreach($mimes as $mime){
			$exts = array_merge($exts, explode('|', $mime));
		}

		array_walk($exts, array(__CLASS__, 'buildExt'));

		$files = array();
		foreach($dump['posts'] as $p){
			if($p['post_type'] === 'attachment'){
				$xFile = get_attached_file($p['ID'], true);
				if(file_exists($xFile)){
					$files[] = new SplFileInfo(realpath($xFile));
				}
			}
		}

		$dump['@attachments'] = array();
		$originalImages = array();

		foreach($files as $file){
			$ext = $file->getExtension();

			if(self::$isExportingDemoContent and !in_array($ext, array('png', 'jpg', 'jpeg', 'gif'))){
				continue;
			}

			$basename = self::getRelativePathname($file->getPathname());
			$url = self::$isExportingDemoContent ? '' : "$uploadsUrl/$basename";

			$dump['@attachments'][] = array(
				'url' =>  $url,
				'basename' => $basename,
			);
			$originalImages[] = $file->getPathname();

			// All sizes of given image
			$base = substr($file->getBasename(), 0, -(strlen($ext) + 1));
			$pattern = "$base-[0-9]*x[0-9]*.$ext"; // matching e.g.: img-WxH.jpg
			$found = NFinder::findFiles($pattern)->in($uploadsDir);

			foreach($found as $sizedFile){
				$basename = self::getRelativePathname($sizedFile->getPathname());
				$url = self::$isExportingDemoContent ? '' : "$uploadsUrl/$basename";

				$dump['@attachments'][] = array(
					'url' =>  $url,
					'basename' => $basename,
				);
				$originalImages[] = $sizedFile->getPathname();
			}
		}

		if(file_exists("$uploadsDir/revslider")){
			$revsliderFiles = NFinder::findFiles($exts)->from("$uploadsDir/revslider")->exclude('templates');

			foreach($revsliderFiles as $revFile){
				if(self::$isExportingDemoContent and !in_array($revFile->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))){
					continue;
				}
				$basename = self::getRelativePathname($revFile->getPathname());
				$url = self::$isExportingDemoContent ? '' : "$uploadsUrl/$basename";

				$dump['@attachments'][] = array(
					'url' =>  $url,
					'basename' => $basename,
				);
				$originalImages[] = $revFile->getPathname();
			}
		}

		if(self::$isExportingDemoContent){
			// override with demo images zip file
			$basename = AIT_THEME_CODENAME . '-' . date('YmdHis') . '.zip';
			$dump['@attachments'] = array(array(
				'url' => self::DUMMY_IMG_URL . AIT_THEME_CODENAME . '/' . $basename,
				'basename' => $basename,
			));
		}

		do_action('ait-after-dump-attachments', $dump['@attachments'], $originalImages, self::$isExportingDemoContent);
	}



	protected static function getRelativePathname($filePath)
	{
		$uploadsDirname = basename(realpath(aitPaths()->dir->uploads));
		return str_replace('\\', '/', substr($filePath, strpos($filePath, $uploadsDirname) + strlen($uploadsDirname) + 1));
	}



	public static function filterNonMediaFiles($file)
	{
		$filePath = self::getRelativePathname(str_replace("\\", "/", $file->getRealPath()));
		if(AitUtils::contains($filePath, '/')){
			preg_match('/^[0-9]{4}\/[0-9]{2}\//', $filePath, $matches);
			return (count($matches) > 0 or AitUtils::startsWith($filePath, 'revslider'));
		}else{
			return true;
		}
	}



	public static function buildExt(&$item, $key)
	{
		$item = "*.{$item}";
	}


	// ====================================================
	// Import
	// ----------------------------------------------------



	public static function import($whatToImport, $content, $importAttachments = true)
	{
		self::$currentOperation = 'import';

		@set_time_limit(0);

		if($whatToImport == 'demo-content'){
			$whatToImport = 'all';
			self::$isImportingDemoContent = true;
		}

		$method = AitUtils::id2class($whatToImport, '', 'load');

		if(!method_exists(__CLASS__, $method)){
			throw new Exception(sprintf(__("Import method '%s' does not exist. Something is wrong.", 'ait-admin'), $method));
		}

		$decompressed = @gzuncompress($content);

		$result = array();

		if($decompressed){
			$raw = unserialize(base64_decode($decompressed));

			if(isset($raw[$whatToImport])){
				$dump = $raw[$whatToImport];
				$dump = self::processUrls($dump);
			}else{
				$result['corrupted'] = __('Content of the backup file is corrupted, can not be uncompressed.', 'ait-admin');
				return $result;
			}

			$attachments = $dump['@attachments'];
			unset($dump['@attachments']);

			if($whatToImport == 'all'){
				$result['imports'] = self::loadAll($dump);
			}else{
				$result['imports'] = self::tryLoad($whatToImport, $dump);
			}

			if(self::$isImportingDemoContent){
				$result['attachments'] = self::fetchDemoImages($attachments);
			}elseif($importAttachments){
				$result['attachments'] = self::fetchAttachments($attachments);
			}
		}else{
			$result['corrupted'] = __('Content of the backup file is corrupted, can not be uncompressed.', 'ait-admin');
		}

		return $result;
	}



	public static function importFromFile($whatToImport, $file, $importAttachments = true)
	{
		$content = @file_get_contents($file);
		if($content === false) throw new Exception(sprintf(__('Content from the file "%s" can not be read.', 'ait-admin'), $file));
		return self::import($whatToImport, $content, $importAttachments);
	}



	protected static function loadAll($dump)
	{
		$r1 = self::tryLoad('content', $dump);
		$r2 = self::tryLoad('theme-options', $dump);
		$r3 = self::tryLoad('wp-options', $dump);

		return array_merge_recursive($r1, $r2, $r3);
	}



	protected static function loadWpOptions($dump)
	{
		global $wpdb;

		$errors = array();
		$optionsCounter = 0;

		$options = array(
			'theme_mods_' . AIT_CURRENT_THEME,
			'sidebars_widgets',
			'show_on_front',
			'page_on_front',
			'page_for_posts',
			'widget_%',
			'blogname',
			'blogdescription',
			'uploads_use_yearmonth_folders',
			'permalink_structure',
		);

		$options = apply_filters('ait-backup-wpoptions', $options, self::$isImportingDemoContent);

		// Before importing, delete all old options

		$sql = array();

		foreach($options as $option){
			if(strpos($option, '%') !== FALSE){
				$operator = 'LIKE';
			}else{
				$operator = '=';
			}
			$sql[] = $wpdb->prepare("`option_name` $operator %s", $option);
		}

		$sql = implode(' OR ', $sql);
		$sql = "DELETE FROM {$wpdb->options} WHERE $sql;";
		$result = $wpdb->query($sql);

		if($result === false and $wpdb->last_error){
			$errors[] = $wpdb->last_error;
		}


		// Insert new options

		$check = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE option_id = 1", ARRAY_A);

		$options[] = 'polylang'; // add polylang option

		foreach($dump['options'] as $id => $option){
			if(!isset($check['blog_id'])){
				unset($option['blog_id']);
			}
			if($option['option_name'] == 'theme_mods_%theme%'){
				$option['option_name'] = 'theme_mods_' . AIT_CURRENT_THEME;
			}
			// only wp options
			if(in_array($option['option_name'], $options) or AitUtils::startsWith($option['option_name'], 'widget_') or AitUtils::startsWith($option['option_name'], 'reservations_') or AitUtils::contains($option['option_name'], '_category_')){
				$optionsCounter++;
				$result = $wpdb->insert($wpdb->options, $option);
				if($result === false){
					$result = $wpdb->update($wpdb->options, $option, array('option_name' => $option['option_name']));
				}
			}
		}

		if(!empty($errors)){
			// 206 - Partial Content
			$code = (count($errors) != $optionsCounter) ? 206 : 0;
			$msg = $code == 0 ? __('All inserts of theme settings to the database failed.', 'ait-admin') : implode("\n\n", $errors);
			throw new Exception($msg, $code);
		}else{
			return true;
		}
	}



	protected static function loadThemeOptions($dump)
	{
		global $wpdb;

		$errors = array();
		$optionsCounter = 0;

		// Before importing, delete all old options

		$theme = esc_sql(AIT_CURRENT_THEME);
		$where = " `option_name` LIKE '\_ait\_{$theme}\_%\_opts%'";

		$sql = "DELETE FROM {$wpdb->options} WHERE $where;";
		$result = $wpdb->query($sql);

		if($result === false and $wpdb->last_error){
			$errors[] = $wpdb->last_error;
		}

		foreach($dump['options'] as $id => $option){
			$key = &$option['option_name'];
			// only ait
			if(AitUtils::startsWith($key, "_ait_%theme%_")){
				$optionsCounter++;
				$key = str_replace('_%theme%_', "_{$theme}_", $key);
				$result = $wpdb->insert($wpdb->options, $option);
				if($result === false)
					$errors[] = sprintf(__('Inserting of the theme option "%s" failed.', 'ait-admin'), $option['option_name']);
			}
		}

		if(!empty($errors)){
			// 206 - Partial Content
			$code = (count($errors) != $optionsCounter) ? 206 : 0;
			$msg = $code == 0 ? __('All inserts of theme settings to the database failed.', 'ait-admin') : implode("\n\n", $errors);
			throw new Exception($msg, $code);
		}else{
			return true;
		}
	}



	protected static function loadContent($dump)
	{
		global $wpdb;

		$errors = array();
		$insertsCounter = 0;
		$batchInsertSql = '';

		// Import content do default WordPress tables

		foreach(self::$wpContentTables as $table){
			$batchInsertSql = '';
			$insertsCounter = 0;

			$wpdb->query("TRUNCATE TABLE {$wpdb->$table}");

			if(!empty($dump[$table])){

				$fields = implode('`, `', array_keys($dump[$table][0]));

				$rowsCount = count($dump[$table]);

				foreach($dump[$table] as $i => $row){
					$insert = self::createInsertSQL($row) . ', ';
					$insertsCounter++;

					$batchInsertSql .= $insert;

					$hasNext = (($insertsCounter + 1) <= $rowsCount);
					$canDoInsert = (!$hasNext || (($insertsCounter % 30) === 0));

					// we have to do in batches of 30, because of max_allowed_packet is 1MB
					// and one multi insert sql string can be bigger than that
					if($canDoInsert){
						// do insert
						$batchInsertSql = trim($batchInsertSql, ', ');
						$result = $wpdb->query("INSERT INTO `{$wpdb->$table}` (`$fields`) VALUES {$batchInsertSql}");

						if($result === false and $wpdb->last_error){
							$errors[$table][] = $wpdb->last_error;
						}
						// reset and do next batch
						$batchInsertSql = '';
					}
				}
			}
		}

		$customTables = apply_filters('ait-backup-content-custom-tables', array(), self::$isImportingDemoContent);

		do_action('ait-create-content-custom-tables', self::$isImportingDemoContent);

		$insertsCounter = 0;
		$batchInsertSql = '';

		if(!empty($customTables) and is_array($customTables)){
			foreach($customTables as $table){
				if(!isset($dump[$table])) continue;

				$escTable = esc_sql($table);

				$batchInsertSql = '';
				$insertsCounter = 0;

				if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$escTable}'") == $wpdb->prefix . $table){
					$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$escTable}");

					if(!empty($dump[$table])){

						$fields = implode('`, `', array_keys($dump[$table][0]));

						$rowsCount = count($dump[$table]);

						foreach($dump[$table] as $i => $row){
							$insert = self::createInsertSQL($row) . ', ';
							$insertsCounter++;

							$batchInsertSql .= $insert;

							$hasNext = (($insertsCounter + 1) <= $rowsCount);
							$canDoInsert = (!$hasNext || (($insertsCounter % 30) === 0));

							// we have to do in batches of 30, because of max_allowed_packet is 1MB
							// and one multi insert sql string can be bigger than that
							if($canDoInsert){
								// do insert
								$batchInsertSql = trim($batchInsertSql, ', ');
								$result = $wpdb->query("INSERT INTO `{$wpdb->prefix}{$escTable}` (`$fields`) VALUES {$batchInsertSql}");

								if($result === false and $wpdb->last_error){
									$errors[$table][] = $wpdb->last_error;
								}
								// reset and do next batch
								$batchInsertSql = '';
							}
						}

					}
				}
			}
		}


		if(!empty($errors)){
			$errorMsgs = '';

			foreach($errors as $table => $errs){
				$c = count($errs);
				if($c != 0){
					$errorMsgs .= "\nTable [{$table}]:\n" . implode("\n\t", $errs);
				}
			}

			$code = $errorMsgs != '' ? 206 : 0; // 206 - Code for Partial Content, borrowed from HTTP
			throw new Exception(sprintf(__('Content was partialy imported. Some errors occured. %s', 'ait-admin'), $errorMsgs), $code);
		}else{
			return true;
		}
	}



	// ====================================================
	// Helpers
	// ----------------------------------------------------

	protected static $checkFor = array();



	public static function processUrls($data)
	{
		self::$checkFor = array(
			'%child-theme-url%',
			'%parent-theme-url%',
			'%uploads-url%',
			'%site-url%',
			get_template_directory_uri(),
			get_stylesheet_directory_uri(),
			aitPaths()->url->uploads,
			site_url(),
			trim(json_encode(get_template_directory_uri()), '"'),
			trim(json_encode(get_stylesheet_directory_uri()), '"'),
			trim(json_encode(aitPaths()->url->uploads), '"'),
			trim(json_encode(site_url()), '"'),
		);

		$attachments = isset($data['@attachments']) ? $data['@attachments'] : array();
		unset($data['@attachments']);

		array_walk_recursive($data, array(__CLASS__, 'convertUrls'));

		$data['@attachments'] = $attachments;
		return $data;
	}



	protected static function containsPlaceholderOrUrl(&$haystack)
	{
		foreach(self::$checkFor as $needle){
			if(mb_strpos($haystack, $needle) !== false){
				return true;
			}
		}
		return false;
	}



	protected static function convertUrls(&$value, $key)
	{
		if(empty($value) or !is_string($value)){
			return;
		}

		$value = maybe_unserialize($value);

		if(self::$currentOperation == 'export' and !is_array($value)){
			$value = apply_filters('ait-replace-value-in-export', $value, $key, self::$isExportingDemoContent);
		}

		if(is_string($value) and !self::containsPlaceholderOrUrl($value)){
			return;
		}

		global $_aitSiteUrl, $_aitParentThemeUrl, $_aitChildThemeUrl;

		$_aitUploadsUrl = aitPaths()->url->uploads;

		if(!isset($_aitSiteUrl)){
			$_aitSiteUrl = site_url();
		}

		if(!isset($_aitParentThemeUrl)){
			$_aitParentThemeUrl = get_template_directory_uri();
			$_aitChildThemeUrl = get_stylesheet_directory_uri();
		}


		if(is_string($value)){
			if(self::$currentOperation == 'import'){
				// just trick how to test if value is a JSON string, if it's not it triggers error
				$jsonresult = json_decode($value);
				$isJson = false;
				if(version_compare(PHP_VERSION, '5.3.0', '>=') and function_exists('json_last_error') and json_last_error() === JSON_ERROR_NONE){
					$isJson = true;
				}elseif(version_compare(PHP_VERSION, '5.3.0', '<=') and $jsonresult !== null){
					$isJson = true;
				}

				if($isJson){ // it's json
					// some plugins e.g. revslider stores values as json data not php serialized data
					// thus strings must be properly jsonify
					// just use simple json_encode here instead of NJson, because there is too mutch logic, we nned simle string to be enocded
					$value = str_replace('%child-theme-url%', trim(json_encode($_aitChildThemeUrl), '"'), $value);
					$value = str_replace('%parent-theme-url%', trim(json_encode($_aitParentThemeUrl), '"'), $value);
					$value = str_replace('%uploads-url%', trim(json_encode($_aitUploadsUrl), '"'), $value);
					$value = str_replace('%site-url%', trim(json_encode($_aitSiteUrl), '"'), $value);
				}else{
					$value = str_replace('%child-theme-url%', $_aitChildThemeUrl, $value);
					$value = str_replace('%parent-theme-url%', $_aitParentThemeUrl, $value);
					$value = str_replace('%uploads-url%', $_aitUploadsUrl, $value);
					$value = str_replace('%site-url%', $_aitSiteUrl, $value);
				}

			}elseif(self::$currentOperation == 'export'){
				$value = str_replace($_aitChildThemeUrl, '%child-theme-url%', $value);
				$value = str_replace($_aitParentThemeUrl, '%parent-theme-url%', $value);
				$value = str_replace($_aitUploadsUrl, '%uploads-url%', $value);
				$value = str_replace($_aitSiteUrl, '%site-url%', $value);

				// some plugins e.g. revslider stores values as json data not php serialized data
				$value = str_replace(trim(json_encode($_aitChildThemeUrl), '"'), '%child-theme-url%', $value);
				$value = str_replace(trim(json_encode($_aitParentThemeUrl), '"'), '%parent-theme-url%', $value);
				$value = str_replace(trim(json_encode($_aitUploadsUrl), '"'), '%uploads-url%', $value);
				$value = str_replace(trim(json_encode($_aitSiteUrl), '"'), '%site-url%', $value);
			}
		}elseif(is_array($value)){
			array_walk_recursive($value, array(__CLASS__, 'convertUrls'));
			$value = serialize($value);
		}
	}



	protected static function createInsertSQL($data)
	{
		global $wpdb;

		$fields = array_keys($data);
		$formattedFields = array();

		foreach($fields as $field){
			if(isset($wpdb->field_types[$field])){
				$form = $wpdb->field_types[$field];
			}else{
				$form = '%s';
			}

			$formattedFields[] = $form;
		}

		$cols = implode(', ', $formattedFields);
		return $wpdb->prepare("($cols)", $data);
	}



	protected static function fetchDemoImages($attachments)
	{
		$failed = array();
		$ok = array();

		$uploadsDir = realpath(aitPaths()->dir->uploads);

		$zipToDownload = (isset($attachments[0]) and AitUtils::endsWith($attachments[0]['url'], '.zip')) ? $attachments[0]['url'] : '';
		$zipFile = download_url($zipToDownload);

		if(is_wp_error($zipFile)){
			$failed[] = sprintf(__('Demo images can not be fetched. Reason: %s', 'ait-admin'), implode('. ', $zipFile->get_error_messages()));
		}else{
			$cb = function($method){ return 'direct';};

			add_filter('filesystem_method', $cb);
			WP_Filesystem();

			$result = unzip_file($zipFile, $uploadsDir);

			if(is_wp_error($result)){
				$failed[] = sprintf(__('Can not extract demo images from zip file. Reason: %s', 'ait-admin'), implode('. ', $result->get_error_messages()));
			}else{
				$ok[] = __('Dummy demo images were sucessfully downloaded', 'ait-admin');
			}

			remove_filter('filesystem_method', $cb);
		}

		return compact('ok', 'failed');
	}



	protected static function fetchAttachments($attachments)
	{
		$failed = array();
		$ok = array();

		$uploadsDir = realpath(aitPaths()->dir->uploads);

		foreach($attachments as $i => $data){
			$file = "{$uploadsDir}/{$data['basename']}";
			if(file_exists($file)) continue;

			$upload = self::fetchRemoteFile($data['url'], $file);

			if(is_wp_error($upload)){
				if(!self::$isImportingDemoContent){
					$failed[] = sprintf(__('File from URL "%s" can not be fetched. Reason: %s', 'ait-admin'), $data['url'], implode('. ', $upload->get_error_messages()));
				}
			}else{
				if(!self::$isImportingDemoContent){
					$ok[] = sprintf(__('Successfully downloaded and saved file from URL "%s"', 'ait-admin'), $data['url']);
				}
			}
		}

		if(self::$isImportingDemoContent){
			$ok = array(__('Dummy demo images were sucessfully downloaded', 'ait-admin'));
		}else{
			if(empty($failed)){
				$ok = array(__('All attachments were sucessfully downloaded', 'ait-admin'));
			}else{
				$ok = array();
			}
		}

		return array('ok' => $ok, 'failed' => $failed);
	}



	/**
	 * Copy+paste from wp_generate_attachment_metadata() function
	 * @param  string $file
	 * @return
	 */
	protected static function generateAllSizesForGuidImages($file)
	{
		if ( file_is_displayable_image($file) ) {

			// make thumbnails and other intermediate sizes
			global $_wp_additional_image_sizes;

			$sizes = array();
			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => false );
				if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
					$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
				else
					$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
					$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
				else
					$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
					$sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop']; // For theme-added sizes
				else
					$sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options
			}

			/**
			 * Filter the image sizes automatically generated when uploading an image.
			 *
			 * @since 2.9.0
			 *
			 * @param array $sizes An associative array of image sizes.
			 */
			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

			if ( $sizes ) {
				$editor = wp_get_image_editor( $file );

				if ( ! is_wp_error( $editor ) )
					$editor->multi_resize( $sizes );
			}

		}
	}



	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param array $post Attachment details
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	public static function fetchRemoteFile($url, $file)
	{
		// avoid http redirections, change old url to new url, dummyimg generator is now on demo.ait-themes.com
		if(AitUtils::contains($url, 'http://ait-themes.com/dummyimg/')){
			$url = str_replace('http://ait-themes.com/dummyimg/', self::DUMMY_IMG_URL, $url);
		}

		$headers = self::httpGet($url, $file);

		if(!$headers){
			@unlink($file);
			return new WP_Error('fetch_remore_file_error', __('Remote server did not respond', 'ait-admin'));
		}

		// make sure the fetch was successful
		if($headers['response'] != '200'){
			@unlink( $file );
			return new WP_Error( 'fetch_remore_file_error', sprintf( __('Remote server returned error response %1$d %2$s', 'ait-admin'), esc_html($headers['response']), get_status_header_desc($headers['response'])));
		}

		$filesize = filesize($file);

		if(isset($headers['content-length']) and $filesize != $headers['content-length']){
			@unlink( $file );
			return new WP_Error( 'fetch_remore_file_error', __('Remote file have incorrect size', 'ait-admin'));
		}

		if($filesize == 0){
			@unlink( $file );
			return new WP_Error('fetch_remore_file_error', __('Zero size file downloaded', 'ait-admin'));
		}

		return $file;
	}



	protected static function tryLoad($whatToImport, $dump)
	{
		$return = array('ok' => array(), 'warning' => array(), 'error' => array());
		$msgs = array(
			'content' => array(
				'ok'      => __('Content was successfully imported, yay!', 'ait-admin'),
				'warning' => __('Content was imported partialy. Some data could not be imported. Here is the report: %s', 'ait-admin'),
				'error'   => __('Content could not be imported. Reason(s): %s', 'ait-admin'),
			),
			'theme-options' => array(
				'ok'      => __('All theme settings were successfully imported, yay!', 'ait-admin'),
				'warning' => __('All theme settings were imported partialy. Some data could not be imported. Here is the report: %s', 'ait-admin'),
				'error'   => __('All theme settings could not be imported. Reason(s): %s', 'ait-admin'),
			),
			'wp-options' => array(
				'ok'      => __('WordPress settings (sidebars, widgets, etc..) were successfully imported, yay!', 'ait-admin'),
				'warning' => __('WordPress settings were imported partialy. Some data could not be imported. Here is the report: %s', 'ait-admin'),
				'error'   => __('WordPress settings (sidebars, widgets, etc..) could not be imported. Reason(s): %s', 'ait-admin'),
			),
		);

		$method = AitUtils::id2class($whatToImport, '', 'load');

		if(!method_exists(__CLASS__, $method)){
			throw new Exception(sprintf(__("Import method '%s' does not exist. Something is wrong.", 'ait-admin'), $method));
		}

		try{

			self::$method($dump);

			$return['ok'][$whatToImport] = $msgs[$whatToImport]['ok'];
		}catch(Exception $e){
			if($e->getCode() == 206){
				$return['warning'][$whatToImport] = sprintf($msgs[$whatToImport]['warning'], "<pre>" . $e->getMessage() . "</pre>");
			}else{
				$return['error'][$whatToImport] = sprintf($msgs[$whatToImport]['error'], "<pre>" . $e->getMessage() . "</pre>");
			}
		}

		return $return;
	}



	/**
	 * Perform a HTTP HEAD or GET request.
	 *
	 * Taken from wp_get_http()
	 *
	 * If $filePath is a writable filename, this will do a GET request and write
	 * the file to that path.
	 *
	 * @param string      $url      URL to fetch.
	 * @param string|bool $filePath Optional. File path to write request to.
	 * @return bool|string          False on failure and string of headers if HEAD request.
	 */
	public static function httpGet($url, $filePath)
	{
		@set_time_limit(60);

		$options = array();
		$options['redirection'] = 3;
		$options['method'] = 'GET';
		$options['timeout'] = 30;

		$response = wp_remote_request($url, $options);

		if(is_wp_error($response)){
			return false;
		}

		$headers = wp_remote_retrieve_headers($response);
		$headers['response'] = wp_remote_retrieve_response_code($response);

		wp_mkdir_p(dirname($filePath));

		if(PHP_OS === 'WINNT'){
			$filePath = addslashes($filePath);
		}

		// GET request - write it to the supplied filename
		$fp = fopen($filePath, 'w');
		if(!$fp) return $headers;

		fwrite($fp,  wp_remote_retrieve_body($response));
		fclose($fp);
		clearstatcache();

		return $headers;
	}

}
