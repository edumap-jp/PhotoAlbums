<?php
/**
 * PhotoAlbum Model
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppModel', 'PhotoAlbums.Model');
App::uses('Current', 'NetCommons.Utility');
App::uses('WorkflowComponent', 'Workflow.Controller/Component');
App::uses('PhotoAlbumPhoto', 'PhotoAlbums.Model');
App::uses('TemporaryFolder', 'Files.Utility');

/**
 * Summary for PhotoAlbum Model
 */
class PhotoAlbum extends PhotoAlbumsAppModel {

/**
 * Field name for attachment behavior
 *
 * @var int
 */
	const ATTACHMENT_FIELD_NAME = 'jacket';

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'NetCommons.OriginalKey',
		'Files.Attachment' => [PhotoAlbum::ATTACHMENT_FIELD_NAME],
		'Workflow.Workflow',
		'Workflow.WorkflowComment',
		//多言語
		'M17n.M17n' => array(
			//'commonFields' => array('weight'), //現状ないため。
			'associations' => array(
				'UploadFilesContent' => array(
					'class' => 'Files.UploadFilesContent',
					'foreignKey' => 'content_id',
				),
			),
		),
	);

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$this->validate = array_merge(
			$this->validate,
			array(
				'name' => array(
					'notBlank' => array(
						'rule' => array('notBlank'),
						'message' => sprintf(
							__d('net_commons', 'Please input %s.'), __d('photo_albums', 'Album Name')
						),
						'allowEmpty' => false,
						'required' => true,
					),
				),
			)
		);

		return parent::beforeValidate($options);
	}

/**
 * Called after each find operation. Can be used to modify any results returned by find().
 * Return value should be the (modified) results.
 * Set photo count data
 *
 * @param mixed $results The results of the find operation
 * @param bool $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed Result of the find operation
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#afterfind
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 *
 * 速度改善の修正に伴って発生したため抑制
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
	public function afterFind($results, $primary = false) {
		if ($this->recursive == -1) {
			return $results;
		}
		if (empty($results)) {
			return $results;
		}
		// いる？
		if (!isset($results[0][$this->alias]['key'])) {
			return $results;
		}
		$albumKeyArr = [];
		foreach ($results as $result) {
			$albumKeyArr[] = $result['PhotoAlbum']['key'];
		}
		$Photo = ClassRegistry::init('PhotoAlbums.PhotoAlbumPhoto');
		$Photo->virtualFields = array('photo_count' => 0);
		$query = array(
			'conditions' => $Photo->getWorkflowConditions() + array(
				'PhotoAlbumPhoto.album_key' => $albumKeyArr
			),
			'fields' => array(
				'PhotoAlbumPhoto.album_key',
				'PhotoAlbumPhoto.status',
				'COUNT(PhotoAlbumPhoto.album_key) as PhotoAlbumPhoto__photo_count'
			),
			'recursive' => -1,
			'group' => array(
				'PhotoAlbumPhoto.album_key',
				'PhotoAlbumPhoto.status',
			),
		);
		$countArr = $Photo->find('all', $query);
		$photoCount = [];
		foreach ($countArr as $result) {
			$result = $result['PhotoAlbumPhoto'];
			$photoCount[$result['album_key']][$result['status']] = $result['photo_count'];

		}

		foreach ($results as $index => $result) {
			$albumKey = $result[$this->alias]['key'];

			$publishedCount = 0;
			$pendingCount = 0;
			$draftCount = 0;
			$disapprovedCount = 0;
			if (isset($photoCount[$albumKey][WorkflowComponent::STATUS_PUBLISHED])) {
				$publishedCount = $photoCount[$albumKey][WorkflowComponent::STATUS_PUBLISHED];
			}
			if (isset($photoCount[$albumKey][WorkflowComponent::STATUS_APPROVAL_WAITING])) {
				$pendingCount = $photoCount[$albumKey][WorkflowComponent::STATUS_APPROVAL_WAITING];
			}
			if (isset($photoCount[$albumKey][WorkflowComponent::STATUS_IN_DRAFT])) {
				$draftCount = $photoCount[$albumKey][WorkflowComponent::STATUS_IN_DRAFT];
			}
			if (isset($photoCount[$albumKey][WorkflowComponent::STATUS_DISAPPROVED])) {
				$disapprovedCount = $photoCount[$albumKey][WorkflowComponent::STATUS_DISAPPROVED];
			}
			$results[$index][$this->alias] += array(
				'published_photo_count' => $publishedCount,
				'pending_photo_count' => $pendingCount,
				'draft_photo_count' => $draftCount,
				'disapproved_photo_count' => $disapprovedCount,
				'photo_count' => $publishedCount +
					$pendingCount +
					$draftCount +
					$disapprovedCount,
				// ＴＯＤＯ:Use CounterCache
				'latest_photo_count' => $publishedCount +
					$pendingCount +
					$draftCount +
					$disapprovedCount,

			);
		}

		return $results;
	}

/**
 * Save album for edit
 * When add, use saveAlbumForAdd method to save with display and photo data
 *
 * @param array $data received post data
 * @return mixed On success Model::$data, false on validation errors
 * @throws InternalErrorException
 */
	public function saveAlbumForEdit($data) {
		$this->begin();

		$albumField = PhotoAlbum::ATTACHMENT_FIELD_NAME;
		$data['PhotoAlbum'][$albumField] = $this->__generateJacketByPhotoId($data);

		$this->set($data);
		if (!$this->validates()) {
			$this->rollback();
			return false;
		}

		try {
			if (!$album = $this->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$this->commit();

		} catch (Exception $ex) {
			$this->rollback($ex);
		}

		return $album;
	}

/**
 * Save album for add
 * Save with display and photo data
 *
 * @param array $data received post data
 * @return mixed On success Model::$data, false on validation errors
 * @throws InternalErrorException
 */
	public function saveAlbumForAdd($data) {
		$this->begin();

		$albumField = PhotoAlbum::ATTACHMENT_FIELD_NAME;
		$data['PhotoAlbum'][$albumField] = $this->__generateJacketByIndex($data);

		$this->set($data);
		$this->__addUploadValidation();
		if (!$this->validates()) {
			$this->rollback();
			return false;
		}

		try {
			$doSaveDisplay = !$this->exists();
			if (!$album = $this->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$photoField = PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME;
			$Photo = ClassRegistry::init('PhotoAlbums.PhotoAlbumPhoto');
			foreach ($data['PhotoAlbumPhoto'] as $photoData) {
				// MImeTypeのValidationはPhotoAlbumPhotoモデルでおこなう。
				// 複数選択プレビュー時にJavaScriptで絞っているため、エラーにならないよう次処理へ
				if (!preg_match('#image/.+#', $photoData[$photoField]['type'])) {
					continue;
				}

				$data = $Photo->create();
				$data['PhotoAlbumPhoto'][$photoField] = $photoData[$photoField];
				$data['PhotoAlbumPhoto']['album_key'] = $album['PhotoAlbum']['key'];
				$data['PhotoAlbumPhoto']['language_id'] = $album['PhotoAlbum']['language_id'];
				$data['PhotoAlbumPhoto']['status'] = $album['PhotoAlbum']['status'];
				if (!$Photo->savePhoto($data)) {
					throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
				}
			}

			if ($doSaveDisplay) {
				$DisplayAlbum = ClassRegistry::init('PhotoAlbums.PhotoAlbumDisplayAlbum');
				$data = $DisplayAlbum->create();
				$data['PhotoAlbumDisplayAlbum']['frame_key'] = Current::read('Frame.key');
				$data['PhotoAlbumDisplayAlbum']['album_key'] = $album['PhotoAlbum']['key'];
				if (!$DisplayAlbum->save($data)) {
					throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
				}
			}

			$this->commit();

		} catch (Exception $ex) {
			$this->rollback($ex);
		}

		return $album;
	}

/**
 * generate jacket data
 *
 * @param array $data received post data
 * @return mixed On success Model::$data, false on validation errors
 */
	private function __generateJacketByPhotoId($data) {
		$jackeData = array(
			'name' => '',
			'type' => '',
			'tmp_name' => '',
			'error' => UPLOAD_ERR_NO_FILE,
			'size' => '',
		);
		$photoId = $data['PhotoAlbum']['selectedJacketPhotoId'];
		if (!$photoId) {
			return $jackeData;
		}

		$UploadFile = ClassRegistry::init('Files.UploadFile');
		$fieldName = PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME;
		$file = $UploadFile->getFile('photo_albums', $photoId, $fieldName);
		$path = $UploadFile->getRealFilePath($file);

		$Folder = new TemporaryFolder();
		$tmpName = $Folder->path . DS . $file['UploadFile']['real_file_name'];
		$jackeData = array(
			'name' => $file['UploadFile']['original_name'],
			'type' => $file['UploadFile']['mimetype'],
			'tmp_name' => $tmpName,
			'error' => UPLOAD_ERR_OK,
			'size' => $file['UploadFile']['size'],
		);
		copy($path, $tmpName);

		return $jackeData;
	}

/**
 * generate jacket data
 *
 * @param array $data received post data
 * @return mixed On success Model::$data, false on validation errors
 */
	private function __generateJacketByIndex($data) {
		$jackeData = array();
		$index = $data['PhotoAlbum']['selectedJacketIndex'];
		if (!strlen($index)) {
			return $jackeData;
		}

		$photoField = PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME;
		$jackeData = $data['PhotoAlbumPhoto'][$index][$photoField];
		$jackeData['tmp_name'] .= '_CopyForJacket';

		copy(
			$data['PhotoAlbumPhoto'][$index][$photoField]['tmp_name'],
			$jackeData['tmp_name']
		);

		return $jackeData;
	}

/**
 * Set upload validation
 *
 * @return void
 */
	private function __addUploadValidation() {
		$this->validator()->add(
			PhotoAlbum::ATTACHMENT_FIELD_NAME,
			array(
				'isFileUpload' => array(
					'rule' => array('isFileUpload'),
					'message' => array(__d('files', 'Please specify the file')),
					'required' => true,
				),
				'jacketExtension' => array(
					'rule' => array(
						'extension',
						array('gif', 'jpeg', 'png', 'jpg', 'bmp')
					),
					'message' => array(__d('files', 'It is upload disabled file format'))
				),
				'mimeType' => array(
					'rule' => array('mimeType', '#image/.+#'),
					'message' => array(__d('files', 'It is upload disabled file format'))
				),
			)
		);
	}

/**
 * Delete album
 *
 * @param array $data delete data
 * @return bool True on success
 * @throws InternalErrorException
 */
	public function deleteAlbum($data) {
		$this->begin();

		try {
			if (!$this->deleteAll(array('PhotoAlbum.key' => $data['PhotoAlbum']['key']), false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			if (!$this->__deleteJacketFile($data['PhotoAlbum']['key'])) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// アルバム内の写真削除
			$Photo = ClassRegistry::init('PhotoAlbums.PhotoAlbumPhoto');
			$conditions = array('PhotoAlbumPhoto.album_key' => $data['PhotoAlbum']['key']);
			$query = array(
				'fields' => array('PhotoAlbumPhoto.key'),
				'conditions' => $conditions,
				'recursive' => -1
			);
			$contentKeys = $Photo->find('list', $query);

			if (!$Photo->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			if (!$Photo->deleteUploadFileByContentKeys($contentKeys)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$DisplayAlbum = ClassRegistry::init('PhotoAlbums.PhotoAlbumDisplayAlbum');
			$conditions = array('PhotoAlbumDisplayAlbum.album_key' => $data['PhotoAlbum']['key']);
			if (!$DisplayAlbum->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$contentKeys[] = $data['PhotoAlbum']['key'];
			$this->deleteCommentsByContentKey($contentKeys);

			$this->commit();

		} catch (Exception $ex) {
			$this->rollback($ex);
		}

		return true;
	}

/**
 * __deleteJacketFile
 *
 * @param string $albumKey album.key
 * @return mixed
 */
	private function __deleteJacketFile($albumKey) {
		return $this->deleteUploadFileByContentKeys([$albumKey]);
	}
}
