<?php
/**
 * PhotoAlbumFrameSetting Model
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppModel', 'PhotoAlbums.Model');
App::uses('Current', 'NetCommons.Utility');

/**
 * Summary for PhotoAlbumFrameSetting Model
 */
class PhotoAlbumFrameSetting extends PhotoAlbumsAppModel {

/**
 * Constant for display type
 *
 * @var int
 */
	const DISPLAY_TYPE_ALBUMS = 1;
	const DISPLAY_TYPE_PHOTOS = 2;
	const DISPLAY_TYPE_SLIDE = 3;

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
		$this->validate = array_merge($this->validate, array(
			// ＴＯＤＯ frame_keyのチェックいる？
			'frame_key' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'albums_order' => array(
				'inList' => array(
					'rule' => array(
						'inList',
						array(
							'PhotoAlbum.modified desc',
							'PhotoAlbum.created asc',
							'PhotoAlbum.name asc',
						)
					),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'albums_per_page' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'photos_order' => array(
				'inList' => array(
					'rule' => array(
						'inList',
						array(
							'PhotoAlbumPhoto.modified desc',
							'PhotoAlbumPhoto.created asc',
							'PhotoAlbumPhoto.name asc',
						)
					),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'photos_per_page' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'slide_height' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			)
		));

		return parent::beforeValidate($options);
	}

/**
 * Get PhotoAlbumFrameSetting data
 * If not exists, call create method for set default data
 *
 * @return array FrameSetting data
 */
	public function getFrameSetting() {
		$data = array(
			'frame_key' => Current::read('Frame.key'),
		);
		$query = array(
			'conditions' => $data,
			'recursive' => -1
		);
		$frameSetting = $this->find('first', $query);

		if (!$frameSetting) {
			$frameSetting = $this->create();
		}

		return $frameSetting;
	}

/**
 * Save PhotoAlbumFrameSetting
 *
 * @param array $data Data to save
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function savePhotoAlbumFrameSetting($data) {
		$this->begin();

		$this->set($data['PhotoAlbumFrameSetting']);
		if (! $this->validates()) {
			$this->rollback();
			return false;
		}

		try {
			$orderValues = explode(' ', $data['PhotoAlbumFrameSetting']['albums_order']);
			$this->set('albums_sort', $orderValues[0]);
			$this->set('albums_direction', $orderValues[1]);

			$orderValues = explode(' ', $data['PhotoAlbumFrameSetting']['photos_order']);
			$this->set('photos_sort', $orderValues[0]);
			$this->set('photos_direction', $orderValues[1]);

			if (!$photoAlbumSetting = $this->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$DisplayAlbum = ClassRegistry::init('PhotoAlbums.PhotoAlbumDisplayAlbum', true);
			$conditions = array('frame_key' => $data['PhotoAlbumFrameSetting']['frame_key']);
			if (!$DisplayAlbum->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			if (empty($data['PhotoAlbumDisplayAlbum'])) {
				$data['PhotoAlbumDisplayAlbum'] = array();
			}

			foreach ($data['PhotoAlbumDisplayAlbum'] as $displayAlbumData) {
				$displayAlbumData = $DisplayAlbum->create($displayAlbumData);
				$displayAlbumData['frame_key'] = $data['PhotoAlbumFrameSetting']['frame_key'];
				if (!$DisplayAlbum->save($displayAlbumData)) {
					throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
				}
			}

			$this->commit();

		} catch (Exception $ex) {
			$this->rollback($ex);
		}

		return $photoAlbumSetting;
	}
}
