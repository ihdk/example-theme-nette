<?php


/**
 * The Attachment Entity
 */
class WpLatteAttachmentEntity extends WpLatteBaseEntity
{

	protected $id;
	protected $parentId;
	protected $mimeType;

	protected $metadata;



	public function __construct($postId, $parentId, $mimeType)
	{
		$this->id = $postId;
		$this->parentId = $parentId;
		$this->mimeType = $mimeType;

		$this->metadata = wp_get_attachment_metadata($postId);
	}



	public function isImage()
	{
		return preg_match('!^image/!', $this->mimeType);
	}



	public function isVideo()
	{
		return preg_match('#^video/#', $this->mimeType);
	}



	public function isAudio()
	{
		return preg_match('#^audio/#', $this->mimeType);
	}



	public function permalink()
	{
		return get_attachment_link();
	}



	/**
	 * Retrieve the URL for an attachment.
	 * @return string|bool Attachment URL or false if url can't be retrieved
	 */
	public function url()
	{
		return wp_get_attachment_url($this->id);
	}



	public function path()
	{
		return get_attached_file($this->id);
	}



	public function filename()
	{
		return wp_basename($this->path());
	}



	public function nextAttachmentUrl()
	{
		if($return = WpLatteObjectCache::load("next-attachment-{$this->id}->{$this->parentId}")){
			return $return;
		}

		$url = wp_get_attachment_url();

		$attachments = array_values(
			get_children(array(
				'post_parent'    => $this->parentId,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => 'ASC',
				'orderby'        => 'menu_order ID'
				)
			)
		);

		if(count($attachments) > 1){
			foreach($attachments as $k => $attachment){
				if($attachment->ID == $this->id){
					break;
				}
			}

			$k++;

			if(isset($attachments[ $k ])){
				$url = get_attachment_link($attachments[$k]->ID);
			}else{
				$url = get_attachment_link($attachments[0]->ID);
			}
		}

		WpLatteObjectCache::save("next-attachment-{$this->id}->{$this->parentId}", $url);
		return $url;
	}



	public function thumbUrl()
	{
		return wp_get_attachment_thumb_url();
	}



	public function metadata()
	{
		if($this->metadata){
			return (object) $this->metadata;
		}

		return false;
	}



	public function sizes()
	{
		if($this->metadata and isset($this->metadata['sizes'])){
			return (object) $this->metadata['sizes'];
		}

		return false;
	}



	public function width()
	{
		if($this->metadata and isset($this->metadata['width'])){
			return $this->metadata['width'];
		}

		return false;
	}



	public function height()
	{
		if($this->metadata and isset($this->metadata['height'])){
			return $this->metadata['height'];
		}

		return false;
	}



	public function image($size = 'thumbnail')
	{
		return wp_get_attachment_image($this->id, $size);
	}



	/**
	 * Outputs video player
	 * Code from edit_form_image_editor()
	 * @return string HTML for video player (output from do_shortcode('[video ...]'))
	 */
	public function video()
	{
		$meta = $this->metadata;
		$w = !empty($meta['width']) ? min($meta['width'], 600) : 0;
		$h = 0;

		if(!empty($meta['height'])){
			$h = $meta['height'];
		}

		if($h && $w < $meta['width']){
			$h = round(( $meta['height'] * $w ) / $meta['width']);
		}

		$shortcode = sprintf( '[video src="%s"%s%s]',
			$this->url(),
			empty($meta['width']) ? '' : sprintf(' width="%d"', $w),
			empty($meta['height']) ? '' : sprintf(' height="%d"', $h)
		);

		return do_shortcode($shortcode);
	}



	/**
	 * Outputs audio player
	 * Code from edit_form_image_editor()
	 * @return string HTML for video player (output from do_shortcode('[audio ...]'))
	 */
	public function audio()
	{
		return do_shortcode('[audio src="' . $this->url() . '"]');
	}


}
