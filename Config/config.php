<?php
/**
 * PhotoAlbum Config
 */


$config['PhotoAlbums'] = [
	// TODO 20にする
	'maxFileUploads' => min(20, ini_get('max_file_uploads'))
];