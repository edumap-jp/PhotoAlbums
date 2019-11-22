<?php
/**
 * PhotoAlbum Config
 */


$config['PhotoAlbums'] = [
	/** 同時にアップロードできるファイル数 */
	'maxFileUploads' => min(20, ini_get('max_file_uploads')),
	/** スライドショーのリミット */
	'slideShowMaxLimit' => 1000,
	/** 1アルバムにアップロードできる最大画像数 */
	'maxFileInAlbum' => 500,
];