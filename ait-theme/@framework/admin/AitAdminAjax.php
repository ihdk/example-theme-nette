<?php


class AitAdminAjax
{

	protected static $externalAjaxActions = array();


	/**
	 * Registers all ajax hooks
	 * The method which suppose to be wp_ajax_* callback must have @WpAjax annotation
	 */
	public static function register()
	{
		$methods = get_class_methods(__CLASS__);
		$r = new NClassReflection(__CLASS__);

		foreach($methods as $method){
			if($r->getMethod($method)->getAnnotation('WpAjax') === true)
				add_action("wp_ajax_admin:{$method}", array(__CLASS__, $method));
		}

		foreach(self::$externalAjaxActions as $action => $callback){
			add_action("wp_ajax_admin:{$action}", $callback);
		}
	}



	public static function addAjaxAction($action, $callback)
	{
		self::$externalAjaxActions[$action] = $callback;
	}



	public static function sendOk()
	{
		wp_send_json_success();
	}



	public static function sendNotOk()
	{
		wp_send_json_error();
	}



	public static function sendJson($data)
	{
		wp_send_json_success($data);
	}



	public static function sendErrorJson($data)
	{
		wp_send_json_error($data);
	}



	/**
	 * Saves Theme Options
	 * @WpAjax
	 */
	public static function saveThemeOptions()
	{
		$nonceKey = aitOptions()->getOptionKey('theme');
		AitUtils::checkAjaxNonce($nonceKey, true);

		AitLicensing::interceptSaveThemeOptions();

		self::saveOptions();
	}



	/**
	 * Saves Plugin Options
	 * @WpAjax
	 */
	public static function savePluginOptions()
	{
		$codeName = aitOptions()->getRequestedPluginCodename();
		$nonceKey = aitOptions()->getOptionKey($codeName);
		AitUtils::checkAjaxNonce($nonceKey, true);

		self::saveOptions();
	}



	/**
	 * Saves Theme Settings and Page's Settings
	 * @WpAjax
	 */
	public static function savePagesOptions()
	{
		$oid = aitOptions()->getRequestedOid();

		$nonceKey = implode(',', aitOptions()->getOptionsKeys(array('layout', 'elements'), $oid));

		AitUtils::checkAjaxNonce($nonceKey, true);

		self::saveOptions(!empty($oid), $oid);
	}



	/**
	 * Helper method for save<page>Options methods
	 * @return void
	 */
	private static function saveOptions($noAutoload = false, $oid = '')
	{
		// from: wp-admin/options.php
		$optionsKeys = explode(',', stripslashes(isset($_POST['options-keys']) ? $_POST['options-keys']: ''));

		$a = false;

		$noAutoload = $noAutoload || $a;

		if($optionsKeys){
			foreach($optionsKeys as $optionKey){
				$value = array();
				if (isset($_POST[$optionKey])){
					$value = $_POST[$optionKey];
				}
				$value = stripslashes_deep($value);
				if($noAutoload){
					delete_option($optionKey);
					$r = add_option($optionKey, $value, '', 'no'); // autoload = no
					$result = array('added' => $r);
				}else{
					$r = update_option($optionKey, $value);
					$result = array('updated' => $r);
				}
			}
		}


		if(isset($_POST['specific-post']) and isset($_POST['specific-post']['id'])){
			$p = (object) $_POST['specific-post'];

			if(isset($p->template)){
				clean_post_cache($p->id);
				update_post_meta($p->id, '_wp_page_template',  $p->template);
			}

			$post = array();
			$post['ID'] = $p->id;

			if(isset($p->comments)){
				$post['comment_status'] = $p->comments;
			}

			if(isset($p->title)){
				$post['post_title'] = $p->title;
			}

			if(isset($p->content)){
				$post['post_content'] = $p->content;
			}

			wp_update_post($post);
		}elseif($oid == '' and isset($_POST['specific-post']['comments'])){
			update_option('default_comment_status', $_POST['specific-post']['comments']);
		}

		if($oid == ''){
			// when saving global options also clean all local options cached items,
			// because they depends on global advanced options
			$register = aitOptions()->getLocalOptionsRegister();
			$tags = array_merge(array('global'), $register['special'], $register['pages']);
		}else{
			$tags = array($oid);
		}


		do_action('ait-save-options', $_POST, $optionsKeys, $oid);


		if(isset($result['added']) and !$result['added']){
			self::sendErrorJson($result);
		}else{

			AitCache::clean(array('tags' => $tags, 'less' => true));

			aitManager('assets')->compileLessFiles();

			self::sendJson($result);
		}
	}



	/**
	 * Resets all options to default values
	 * Includes theme, layout and elements
	 * @WpAjax
	 */
	public static function resetAllOptions()
	{
		AitUtils::checkAjaxNonce('reset-all-options');

		AitCache::clean();

		aitOptions()->resetAllOptions();

		self::sendOk();
	}



	/**
	 * Resets all theme options to default values
	 * Includes theme, layout and elements
	 * @WpAjax
	 */
	public static function resetThemeOptions()
	{
		AitUtils::checkAjaxNonce('reset-theme-options');

		AitCache::clean();

		aitOptions()->resetThemeOptions();

		self::sendOk();
	}



	/**
	 * Reset Global Pages Options
	 * @WpAjax
	 */
	public static function resetGlobalPagesOptions()
	{
		AitUtils::checkAjaxNonce('reset-pages-options');

		AitCache::clean();

		aitOptions()->resetDefaultLayoutOptions();

		self::sendOk();
	}



	/**
	 * Resets theme options in given section
	 * Includes theme, layout and elements
	 * @WpAjax
	 */
	public static function resetOptionsGroup()
	{
		$configType = isset($_POST['configType']) ? $_POST['configType'] : '';
		$group = isset($_POST['group']) ? $_POST['group'] : '';
		$oid = aitOptions()->getRequestedOid();

		AitUtils::checkAjaxNonce("reset-{$configType}-{$group}-options");

		AitCache::clean();

		aitOptions()->resetOptionsGroup($configType, $group, $oid);

		self::sendOk();
	}



	/**
	 * Imports global options for specific element or layout
	 * @WpAjax
	 */
	public static function importGlobalOptions()
	{
		$configType = isset($_POST['configType']) ? $_POST['configType'] : '';
		$group = isset($_POST['group']) ? $_POST['group'] : '';
		$oid = aitOptions()->getRequestedOid();

		AitUtils::checkAjaxNonce("import-{$configType}-{$group}-options");

		AitCache::clean();

		aitOptions()->importGlobalOptions($configType, $group, $oid);

		self::sendOk();
	}



	/**
	 * Deletes local options
	 * @WpAjax
	 */
	public static function deleteLocalOptions()
	{
		check_ajax_referer('ait-delete-local-options');

		$oid = aitOptions()->getRequestedOid();
		if($oid){
			aitOptions()->deleteLocalOptions($oid);
			AitCache::clean(array('tags' => array($oid), 'less' => true));
		}

        $localOptionsRegister = aitOptions()->getLocalOptionsRegister();
        if ((isset($localOptionsRegister['special']) && $first = reset($localOptionsRegister['special'])) || (isset($localOptionsRegister['pages']) && $first = reset($localOptionsRegister['pages']))) {
            $url = AitUtils::adminPageUrl(array('page' => 'pages-options', 'oid' => $first));
        } else {
            $url = AitUtils::adminPageUrl(array('page' => 'pages-options'));
        }
        self::sendJson(array('url' => esc_url_raw($url)));
    }



	/**
	 * Uploads and imports AIT backup archive
	 * @WpAjax
	 */
	public static function uploadAndImport()
	{
		$whatToImport = isset($_POST['what-to-import']) ? $_POST['what-to-import'] : false;
		$importAttachments = isset($_POST['import-attachments']);

		if(!$whatToImport){
			self::sendErrorJson(array(
				'whatToImport' => '',
				'msg' => __('Something is wrong with import form', 'ait-admin'),
			));
		}

		$sendResults = array();
		$sendResults['whatToImport'] = $whatToImport;

		$content = array();

		// Upload OK
		if(isset($_FILES['import-file']) and $_FILES['import-file']['error'] == UPLOAD_ERR_OK){
			$gzFile = $_FILES['import-file']['tmp_name'];
			$content = file_get_contents($gzFile);
		// Error during upload
		}else{
			// demo content
			if($whatToImport == 'demo-content'){
				$p = str_replace(aitPath('theme'), '', aitPath('includes'));
				$gzFile = aitPath('includes') . "/demo-content.ait-backup";

				$importAttachments = true;

				if(file_exists($gzFile)){
					$content = file_get_contents($gzFile);
				}else{
					self::sendErrorJson(array(
						'whatToImport' => $whatToImport,
						'msg' => sprintf(__("File with demo content '%s' doesn't exists.", 'ait-admin'), $p . "/demo-content.ait-backup"),
					));
				}
			// other imports
			}else{

				// Error messages from wp_handle_upload() function
				$messages = array(false,
					__("The uploaded file exceeds the upload_max_filesize directive in php.ini.", 'default'),
					__("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.", 'default'),
					__("The uploaded file was only partially uploaded.", 'default'),
					__("No file was uploaded.", 'default'),
					'',
					__("Missing a temporary folder.", 'default'),
					__("Failed to write file to disk.", 'default'),
					__("File upload stopped by extension.", 'default'),
				);

				self::sendErrorJson(array(
					'whatToImport' => $whatToImport,
					'msg' => $messages[$_FILES['import-file']['error']]
				));
			}
		}

		$u = parse_url(get_option('siteurl'));
		$siteUrl = $u['host'];
		$siteUrl .= empty($u['path']) ? '' : str_replace(array('/', '\\'), '-', $u['path']);

		// Why name starting with '.ht-..'? Bacause Apache: <FilesMatch "^\.ht">  Deny from all ...
		$bck = aitPaths()->dir->uploads . '/backups/' . sprintf(".ht-backup-%s-%s-%s.ait-backup", $siteUrl, 'all', date('Y-m-d-H.i.s'));
		// Emergency backup export, just in case, when something will go wrong
		try{
			AitBackup::exportToFile('all', $bck);
		}catch(Exception $e){
			// do not report this
		}

		try{

			do_action('ait-before-import', $whatToImport);

			$sendResults = AitBackup::import($whatToImport, $content, $importAttachments);

			$sendResults['whatToImport'] = $whatToImport;

			if(isset($sendResults['corrupted'])){
				self::sendErrorJson(array(
					'whatToImport' => $whatToImport,
					'msg' => $sendResults['corrupted']
				));
			}else{

				AitCache::clean();

				do_action('ait-after-import', $whatToImport, $sendResults);

				wp_cache_delete('notoptions', 'options');
				wp_cache_delete('alloptions', 'options');

				global $wp_rewrite;
				$wp_rewrite->init();
				$wp_rewrite->flush_rules(true);

				self::sendJson($sendResults);
			}

		}catch(Exception $e){
			self::sendErrorJson(array(
				'whatToImport' => $whatToImport,
				'msg' => $e->getMessage(),
			));
		}
	}



	/**
	 * Generates ZIP file with exported options and content and downloads file
	 * It depends on jQuery File Download Plugin
	 * @WpAjax
	 */
	public static function exportAndDownload()
	{
		$whatToExport = isset($_POST['what-to-export']) ? $_POST['what-to-export'] : false;

		if(!$whatToExport){
			self::sendErrorJson(__('Something is wrong with export form', 'ait-admin'));
		}

		try{
			$export = AitBackup::export($whatToExport);

			$u = parse_url(get_option('siteurl'));
			$siteUrl = $u['host'];
			$siteUrl .= empty($u['path']) ? '' : str_replace(array('/', '\\'), '-', $u['path']);

			$exportFile = sprintf("%s-%s-%s.ait-backup", $siteUrl, $whatToExport, date('Y-m-d-H.i.s'));
			if($whatToExport == 'demo-content') $exportFile = "demo-content.ait-backup";

			header('Set-Cookie: fileDownload=true');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache');
			header('Pragma: hack');
			header('Content-Type: application/x-gzip');
			header('Content-Disposition: attachment; filename="' . $exportFile . '"');
			header('Content-Length: ' . strlen($export));
			header('Connection: close');

			echo $export;
			exit;
		}catch(Exception $e){
			echo $e->getMessage();
			exit;
		}
	}

	/**
	 * Render TinyMCE editor instance
	 *
	 * @WpAjax
	 */
	public static function tinyMceEditor()
	{
		$id = $_POST['id'];
		$content = stripslashes( $_POST['content'] );
		$name = stripslashes( $_POST['textarea_name'] );

		$editor = new AitEditorOptionControl(new AitOptionsControlsSection(new AitOptionsControlsGroup()), '');
		$editor->setValue($content);

		$editor->ajaxHtml($id, $name);
	}

	/**
	 * Dismiss all wp pointers from step-by-step tour
	 *
	 * @WpAjax
	 */
	public static function dismissPointers()
	{
		$pointers = $_POST['pointer'];
		$dismissed = array_filter(explode(',', (string) get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true)));

		foreach ($pointers as $pointer) {
			if ($pointer != sanitize_key($pointer))
				wp_die(0);

			if (in_array($pointer, $dismissed))
				continue;

			$dismissed[] = $pointer;
		}

		$dismissed = implode(',', $dismissed);

		update_user_meta(get_current_user_id(), 'dismissed_wp_pointers', $dismissed);

		self::sendOk();
	}
}
