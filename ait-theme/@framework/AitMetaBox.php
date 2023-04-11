<?php


class AitMetaBox
{

	/**
	 * Metabox internal ID
	 * @var int
	 */
	protected $internalId;

	/**
	 * Metabox public humand readable ID used in public APIs
	 * @var int
	 */
	protected $id;

	/**
	 * Metabox configuration params
	 * @var array
	 */
	protected $params;

	/**
	 * Meta key
	 * @var string
	 */
	protected $metaKey;

	/**
	 * Controls Renderer
	 * @var AitFormControlsRenderer
	 */
	protected $controls;

	/**
	 * Control key for HTML template mode
	 * @var string
	 */
	protected $metaboxControlKey = '';

	/**
	 * Control subkey for HTML template mode
	 * @var string
	 */
	protected $metaboxControlSubKey = '';

	/** @internal */
	protected $storage = array();



	/**
	 * Constrcutor
	 * @param array $params Metabox configration params
	 */
	public function __construct($id, $internalId, $params)
	{
		if(is_numeric($internalId))
			wp_die('ID of metabox is not set or is numeric - must be alpha numeric string.');

		$this->id = $id;
		$this->internalId = $internalId;

		$defaultParams = array(
			'id'             => '',
			'title'          => __('Custom Meta Box', 'ait-admin'),
			'metaKey'        => '',
			'template'       => '',
			'renderCallback' => '',
			'saveCallback'   => '',
			'config'         => '',
			'js'             => '',
			'css'            => '',
			'types'          => array('page'),
			'context'        => 'advanced', //('normal', 'advanced', or 'side').
			'priority'       => 'default', // ('high', 'core', 'default' or 'low')
			'args'           => array(),
		);

		$this->params = (object) array_merge($defaultParams, $params);

		$this->metaKey = !empty($this->params->metaKey) ? $this->params->metaKey : "_{$this->internalId}"; // underscore cause invisibility of meta key

		// decide whether to create additional fields for user profile page or add metaboxes for (custom) post type
		if (in_array('user', $params['types'])) {
			add_action( 'show_user_profile', array($this, 'renderControlsContent'));
			add_action( 'edit_user_profile', array($this, 'renderControlsContent'));

			add_action( 'profile_update', array($this, 'saveUser'));

		}
		else {
			add_action('add_meta_boxes', array($this,'init'));
			add_action('save_post', array($this,'save'), 10, 3);
		}
	}



	public function init()
	{
		$config = $this->getRawConfig();
		if(empty($config)){
			return;
		}

		foreach ($this->params->types as $type){
			add_meta_box(
				$this->internalId . '-metabox',
				$this->params->title,
				array($this, 'render'),
				$type,
				$this->params->context,
				$this->params->priority,
				$this->params->args
			);
		}
	}



	public function render($post, $metabox)
	{
		if(!empty($this->params->css) and file_exists($this->params->css)){ ?>
		<style>
			<?php echo file_get_contents($this->css); ?>
		</style>
		<?php
		}

		if(!empty($this->params->js) and file_exists($this->params->js)){ ?>
		<script>
			<?php echo file_get_contents($this->params->js); ?>
		</script>
		<?php
		}

		if(!empty($this->params->template) and file_exists($this->params->template)){

			$this->renderTemplateContent($post, $metabox);

		}elseif(!empty($this->params->renderCallback) and  is_callable($this->params->renderCallback)){

			call_user_func_array($this->params->renderCallback, array($post, $metabox, $this));

		}else{

			$this->renderControlsContent();

		}

		$this->nonceField();
	}



	/**
	 * Render metabox with template
	 * @param  WP_Post $post    Post object
	 * @param  array   $metabox Args, See http://codex.wordpress.org/Function_Reference/add_meta_box#Callback_args
	 * @return void
	 */
	public function renderTemplateContent($post, $metabox)
	{
		require $this->params->template;

		$this->params->config = '';
	}



	/**
	 * Renders controls from config file
	 * @return void
	 */
	public function renderControlsContent()
	{
		try{
			if(!file_exists($this->params->config)){
				$f = str_replace(array('.php', '.neon'), '.[php|neon]', $this->params->config);
				throw new Exception("File {$f} doesn't exist.");
			}

			$enabledLang = ($this->id == 'user-metabox' ? '__return_true' : '__return_false');
			echo '<div data-ait-metabox=' . $this->metaKey . '>';
			add_filter('ait-langs-enabled', $enabledLang);
			AitOptionsControlsRenderer::create(array(
				'configType'    => ($this->id == 'user-metabox' ? 'user-metabox' : 'metabox'),
				'adminPageSlug' => $this->internalId,
				'fullConfig'    => $this->getFullConfig(),
				'defaults'      => $this->getConfigDefaults(),
				'options'       => $this->getOptions(),
			))->render();
			remove_filter('ait-langs-enabled', $enabledLang);
			echo "</div>";

		}catch(Exception $e){
			echo "<strong style='color:red;'>[error]</strong> {$e->getMessage()}";
		}
	}



	public function getId()
	{
		return $this->id;
	}



	public function getInternalId()
	{
		return $this->internalId;
	}



	public function getPostMetaKey()
	{
		return $this->metaKey;
	}



	public function getRawConfig()
	{
		if(!isset($this->storage['raw-config']))
			$this->storage['raw-config'] = AitConfig::loadRawConfig($this->params->config);

		return $this->storage['raw-config'];
	}



	protected function processConfig()
	{
		$c = array();
		$c['metabox'][$this->metaKey]['options'] = $this->getRawConfig();

		if(isset($this->params->textDomain)){
			$c['metabox'][$this->metaKey]['text-domain'] = $this->params->textDomain;
		}

		return aitConfig()->processConfig($c, false, $this->metaKey, array($this->params->config));
	}



	public function getFullConfig()
	{
		if(!isset($this->storage['full-config'])){
			$r = $this->processConfig();
			$this->storage['full-config'] = $r['full-config']['metabox'];
		}

		return $this->storage['full-config'];
	}



	public function getTranslatablesList()
	{
		if(!isset($this->storage['translatables-list'])){
			$r = $this->processConfig();
			$this->storage['translatables-list'] = isset($r['translatables-list']['metabox']) ? $r['translatables-list']['metabox'] : array();
		}

		return $this->storage['translatables-list'];
	}



	public function getConfigDefaults()
	{
		if(!isset($this->storage['defaults'])){
			$r = $this->processConfig();
			$this->storage['defaults'] = $r['defaults']['metabox'];
		}

		return $this->storage['defaults'];
	}



	public function getOptions()
	{
		return array(
			$this->metaKey => $this->getPostMeta(),
		);
	}



	public function getPostMeta($id = 0)
	{
		$metadataType = AitUtils::startsWith($this->metaKey, '_user') ? 'usermeta' : 'postmeta';

		if(!$id){
			// assign id according to current location: (custom)post/page or user profile
			if($metadataType === 'usermeta'){
				global $user_id;
				$id = $user_id;
			}else{
				$id = get_post()->ID;
			}
		}

		if(!isset($this->storage["{$metadataType}{$id}"])){
			$meta = '';

			if($metadataType === 'usermeta'){
				$meta = get_user_meta($id, $this->metaKey, true);
			}else{
				$meta = get_post_meta($id, $this->metaKey, true);
			}

			if($meta !== ''){
				$this->storage["{$metadataType}{$id}"] = $meta;
			}else{
				$this->storage["{$metadataType}{$id}"] = array();
			}
		}


		return $this->storage["{$metadataType}{$id}"];

	}



	public function nonceField()
	{
		wp_nonce_field($this->internalId, $this->metaKey . '_nonce');
	}



	public function verifyNonce()
	{
		$nonce = isset($_POST[$this->metaKey . '_nonce']) ? $_POST[$this->metaKey . '_nonce'] : null;

		return wp_verify_nonce($nonce, $this->internalId);
	}



	/**
	 * @param int     $postId  Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated or not.
	 */
	public function save($postId, $post, $update)
	{
		if(!is_object($post)){
			$post = get_post();
		}

		$data = isset($_POST[$this->metaKey]) ? $_POST[$this->metaKey] : null;

		if(!empty($this->params->saveCallback) and  is_callable($this->params->saveCallback)){

			call_user_func_array($this->params->saveCallback, array($postId, $post, $this, $data, $update));

		}else{
			$realPostId = isset($_POST['post_ID']) ? $_POST['post_ID'] : null;

			if(defined('DOING_AUTOSAVE') AND DOING_AUTOSAVE){
				return $postId;
			}

			if(!$this->verifyNonce()){
				return $postId;
			}

			if($_POST['post_type'] == 'page'){
				if(!current_user_can('edit_page', $postId))
					return $postId;
			}else{
				if(!current_user_can('edit_post', $postId)){
					return $postId;
				}
			}

			if (is_null($data)){
				delete_post_meta($postId, $this->metaKey);
			}else{
				update_post_meta($postId, $this->metaKey, $data);
			}

			return $postId;
		}
	}



	public function saveUser()
	{
		global $user_id;
		// save data from our new bio textarea into original wordpress bio
		if (Aitlangs::isEnabled()) {
			foreach (Aitlangs::getLanguagesList() as $language) {
				update_user_meta( $user_id, 'description_'.$language->slug, $_POST[$this->metaKey]['biography'][$language->description] );
			}
		}
		else {
			update_user_meta( $user_id, 'description', $_POST[$this->metaKey]['biography'][get_locale()] );
		}

		$data = isset($_POST[$this->metaKey]) ? $_POST[$this->metaKey] : null;

		if (is_null($data))
			delete_user_meta( $user_id, $this->metaKey, $data );
		else
			update_user_meta( $user_id, $this->metaKey, $data );
	}



	// ==========================================================
	// Helper methods for HTML template mode
	// ----------------------------------------------------------

	/**
	 * Sets control key and subkey
	 * @param  string $key    Key of control, will be used in id, name attribute
	 * @param  string $subKey
	 * @return void
	 */
	public function control($key, $subKey = null)
	{
		$this->metaboxControlKey = $key;
		$this->metaboxControlSubKey = $subKey;
	}



	/**
	 * Gets id of control
	 * @return string
	 */
	public function getHtmlId()
	{
		return $this->metaKey . $this->metaboxControlKey . $this->metaboxControlSubKey;
	}



	/**
	 * Prints id attribute
	 */
	public function id()
	{
		echo ' id="' . $this->getHtmlId() . '"';
	}



	/**
	 * Gets name of control
	 * @return string
	 */
	public function getHtmlName()
	{
		$n = "{$this->metaKey}[{$this->metaboxControlKey}]";

		if($this->metaboxControlSubKey)
			$n .= "[$this->metaboxControlSubKey]";

		return $n;
	}



	/**
	 * Prints name attribute of control
	 */
	public function name()
	{
		echo ' name="' . $this->getHtmlName() . '"';
	}



	/**
	 * Gets value of control
	 * @param  mix  $default Default value if there is no value
	 * @param  boolean $escape  If value is string should it be escaped?
	 * @return mix
	 */
	public function getValue($default = '')
	{
		$v = $this->getPostMeta();

		if(isset($v[$this->metaboxControlKey]))
			return $v[$this->metaboxControlKey];

		return $default;
	}



	/**
	 * Prints value attribute of control
	 * @param  string  $default
	 * @param  boolean $escape
	 */
	public function value($default = '')
	{
		echo ' value="' . $this->getValue($default, $escape) . '"';
	}



	/**
	 * Prints <label> element for control
	 * @param  string $text Label text
	 */
	public function label($text = 'Label')
	{
		echo '<label for="' . $this->getHtmlId() .  '">' . esc_html($text) . '</label>';
	}
}
