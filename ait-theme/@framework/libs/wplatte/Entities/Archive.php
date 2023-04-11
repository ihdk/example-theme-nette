<?php


/**
 * The Archive Entity
 */
class WpLatteArchiveEntity extends WpLatteBaseEntity
{


	public function date($format = '')
	{
		return get_the_date($format); // translatable by default, see mysql2date 3rd parameter
	}



	// alias just for consistency with Post entity
	public function dateI18n($format = '')
	{
		return $this->date($format);
	}



	// for consistency with Post entity
	public function rawDate()
	{
		$post = get_post(); // that's what get_the_date above is doing
		return $post->post_date;
	}



	public function title()
	{
		if(is_post_type_archive()){
			return post_type_archive_title('', false);
		}else{
			return '';
		}
	}

}
