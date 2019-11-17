<?php
/**
 * PhotoAlbum Config
 */


$config['PhotoAlbums'] = [
	/** 同時にアップロードできるファイル数 */
	'maxFileUploads' => min(20, ini_get('max_file_uploads'))
];