<?php
/**
 * PhotoAlbumDisplayAlbum Model
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppModel', 'PhotoAlbums.Model');
App::uses('Current', 'NetCommons.Utility');

/**
 * Summary for PhotoAlbumDisplayAlbum Model
 */
class PhotoAlbumDisplayAlbum extends PhotoAlbumsAppModel {

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$Album = ClassRegistry::init('PhotoAlbums.PhotoAlbum');
		$query = array(
			'fields' => array('PhotoAlbum.key'),
			'conditions' => array(
				'block_id' => Current::read('Block.id'),
			),
			'recursive' => -1
		);
		$list = $Album->find('list', $query);

		$this->validate = array_merge($this->validate, array(
			'album_key' => array(
				'inList' => array(
					'rule' => array('inList', $list),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
		));

		return parent::beforeValidate($options);
	}

	///**
	// * Get display album key list
	// *
	// * @return display album key list
	// */
	//	public function getDisplayList() {
	//		$query = array(
	//			'fields' => array('PhotoAlbumDisplayAlbum.album_key'),
	//			'conditions' => array(
	//				'frame_key' => Current::read('Frame.key')
	//			),
	//			'recursive' => -1
	//		);
	//
	//		return $this->find('list', $query);
	//	}
}
