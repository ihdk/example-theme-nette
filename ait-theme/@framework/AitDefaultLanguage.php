<?php


/**
 * Default ("fake") class for current language in WordPress if AIT Language plugin is not active
 */
class AitDefaultLanguage
{

	public $id = 0;
	public $locale;
	public $isRtl;
	public $name;
	public $slug;
	public $code;
	public $flagUrl;
	public $flag;
	public $isDefault = true;
	public $isCurrent = true;
	public $url;
	public $hasTranslation = false;
	public $htmlClass = '';



	public function __construct()
	{
		$this->locale = get_locale();
		$this->isRtl = is_rtl();
		$this->name = __('Default language', 'ait-admin');

		if($this->locale == 'zh_CN'){
			$this->slug = $this->code = 'cn';
		}elseif($this->locale == 'zh_TW'){
			$this->slug = $this->code = 'tw';
		}elseif($this->locale == 'pt_BR'){
			$this->slug = $this->code = 'br';
		}else{
			$this->slug = $this->code = substr($this->locale, 0, 2);
		}

		$this->url = get_home_url();
		$this->flagUrl = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAIAAAD5gJpuAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAflJREFUeNpinDRzn5qN3uFDt16+YWBg+Pv339+KGN0rbVP+//2rW5tf0Hfy/2+mr99+yKpyOl3Ydt8njEWIn8f9zj639NC7j78eP//8739GVUUhNUNuhl8//ysKeZrJ/v7z10Zb2PTQTIY1XZO2Xmfad+f7XgkXxuUrVB6cjPVXef78JyMjA8PFuwyX7gAZj97+T2e9o3d4BWNp84K1NzubTjAB3fH0+fv6N3qP/ir9bW6ozNQCijB8/8zw/TuQ7r4/ndvN5mZgkpPXiis3Pv34+ZPh5t23//79Rwehof/9/NDEgMrOXHvJcrllgpoRN8PFOwy/fzP8+gUlgZI/f/5xcPj/69e/37//AUX+/mXRkN555gsOG2xt/5hZQMwF4r9///75++f3nz8nr75gSms82jfvQnT6zqvXPjC8e/srJQHo9P9fvwNtAHmG4f8zZ6dDc3bIyM2LTNlsbtfM9OPHH3FhtqUz3eXX9H+cOy9ZMB2o6t/Pn0DHMPz/b+2wXGTvPlPGFxdcD+mZyjP8+8MUE6sa7a/xo6Pykn1s4zdzIZ6///8zMGpKM2pKAB0jqy4UE7/msKat6Jw5mafrsxNtWZ6/fjvNLW29qv25pQd///n+5+/fxDDVbcc//P/zx/36m5Ub9zL8+7t66yEROcHK7q5bldMBAgwADcRBCuVLfoEAAAAASUVORK5CYII=";
		$this->flag = sprintf(
			'<img src="%s" title="%s" alt="%s">',
			$this->flagUrl,
			esc_attr(apply_filters('pll_flag_title', $this->name, $this->slug, $this->locale)),
			esc_attr($this->name)
		);

		if(aitIsPluginActive('languages')){
			$this->setFlagFromAitLanguagesPlugin();
		}
	}



	public function setFlagFromAitLanguagesPlugin()
	{
		if(file_exists(POLYLANG_DIR.($file = '/assets/flags/'.$this->locale.'.png'))){
			$url = POLYLANG_URL.$file;
		}

		// overwrite with custom flags
		// never use custom flags on admin side
		if(!PLL_ADMIN && ( file_exists(PLL_LOCAL_DIR.($file = '/'.$this->locale.'.png')) || file_exists(PLL_LOCAL_DIR.($file = '/'.$this->locale.'.jpg')) )){
			$url = PLL_LOCAL_URL.$file;
		}

		$this->flagUrl = empty($url) ? '' : esc_url($url);

		$this->flag = apply_filters('pll_get_flag', empty($this->flagUrl) ? '' :
			sprintf(
				'<img src="%s" title="%s" alt="%s" />',
				esc_url($this->flagUrl),
				esc_attr(apply_filters('pll_flag_title', $this->name, $this->slug, $this->locale)),
				esc_attr($this->name)
			));
	}
}
