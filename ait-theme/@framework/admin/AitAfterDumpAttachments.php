<?php


class AitAfterDumpAttachments
{
	public static function run($zipName, $originalImages)
	{
		$uploads = wp_upload_dir();
		$uploads_basedir = realpath($uploads['basedir']);

		$payload = array(
			'theme'           => AIT_THEME_CODENAME,
			'zip'             => $zipName,
			'server'          => $_SERVER['SERVER_ADDR'],
			'uploads_basedir' => $uploads_basedir,
			'images'          => array_map(function($img) use($uploads_basedir){
				return ltrim(str_replace($uploads_basedir, '', $img), '/\\');
			}, $originalImages),
	 	);

	 	return self::sendPayload($payload);
	}



	protected static function sendPayload($payload)
	{
		global $wp_version;

		$args = array(
			'method'      => 'POST',
			'httpversion' => '1.1',
			'blocking'    => false,
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			'body'        => array(
				'payload' => json_encode($payload),
			),
		);
		$result = wp_remote_post('https://demo.ait-themes.club/blurimg/index.php', $args);

		return $result;
	}
}
