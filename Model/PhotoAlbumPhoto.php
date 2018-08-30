<?php
/**
 * PhotoAlbumPhoto Model
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppModel', 'PhotoAlbums.Model');
App::uses('Current', 'NetCommons.Utility');
App::uses('UnZip', 'Files.Utility');
App::uses('File', 'Utility');
App::uses('SiteSettingUtil', 'SiteManager.Utility');

/**
 * Summary for PhotoAlbumPhoto Model
 */
class PhotoAlbumPhoto extends PhotoAlbumsAppModel {

/**
 * Field name for attachment behavior
 *
 * @var int
 */
	const ATTACHMENT_FIELD_NAME = 'photo';

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'NetCommons.OriginalKey',
		'Files.Attachment' => [PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME],
		'Workflow.Workflow',
		'Workflow.WorkflowComment',
		//多言語
		'M17n.M17n' => array(
			//'commonFields' => array('weight'), //現状ないため。
			'associations' => array(
				'UploadFilesContent' => array(
					'class' => 'Files.UploadFilesContent',
					'foreignKey' => 'content_id',
					'isM17n' => false,
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
		$field = PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME;
		$validate = array();
		if (empty($this->data['UploadFile'])) {
			$validate['isFileUpload'] = array(
				'rule' => array('isFileUpload'),
				'message' => array(__d('files', 'Please specify the file'))
			);
		}

		if (isset($this->data['PhotoAlbumPhoto'][$field]['name']) &&
			strlen($this->data['PhotoAlbumPhoto'][$field]['name'])) {
			$validate['photoExtension'] = array(
				'rule' => array(
					'extension',
					array('gif', 'jpeg', 'png', 'jpg', 'zip')
				),
				'message' => array(__d('files', 'It is upload disabled file format'))
			);

			/*
			$validate['mimetype'] = array(
				'rule' => array('mimeType', array('???')),
				'message' => array(__d('files', 'It is upload disabled file format'))
			);
			*/
		}

		$this->validate = array_merge(
			$this->validate,
			array(
				$field => $validate
			)
		);

		return parent::beforeValidate($options);
	}

/**
 * Called before each find operation. Return false if you want to halt the find
 * call, otherwise return the (modified) query data.
 *
 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified
 *  $query to continue with new $query
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforefind
 */
	public function beforeFind($query) {
		$query['order'] += array('PhotoAlbumPhoto.key' => 'asc');
		return $query;
	}

/**
 * Save photo
 *
 * @param array $data received post data
 * @return bool True on success, false on validation errors
 * @throws InternalErrorException
 */
	public function savePhoto($data) {
		$this->begin();

		$regenerateData = $this->__regenerateDataForZip($data);

		$photo = array();

		foreach ($regenerateData as $index => $data) {
			if ($index > 0) {
				$this->create();
			}

			$this->set($data);
			if (!$this->validates()) {
				$this->rollback();
				return false;
			}

			try {
				$result = $this->save(null, false);
				if (! $result) {
					throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
				}
				$photo[] = $result;

			} catch (Exception $ex) {
				$this->rollback($ex);
			}
		}

		$this->commit();

		return $photo;
	}

/**
 * Publish photo
 *
 * @param array $data photo data for publish
 * @return bool True on success, false on validation errors
 * @throws InternalErrorException
 */
	public function publish($data) {
		$this->begin();

		foreach ($data as $photo) {
			unset($photo['PhotoAlbumPhoto']['id']);
			$photo['PhotoAlbumPhoto']['status'] = WorkflowComponent::STATUS_PUBLISHED;

			$this->create();
			$this->set($photo);
			if (!$this->validates()) {
				return false;
			}

			try {
				if (!$this->save(null, false)) {
					throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
				}
			} catch (Exception $ex) {
				$this->rollback($ex);
			}
		}

		$this->commit();

		return true;
	}

/**
 * Delete photo
 *
 * @param array $data delete data
 * @return bool True on success
 * @throws InternalErrorException
 */
	public function deletePhoto($data) {
		$this->begin();

		try {
			if (!$this->deleteAll(array('PhotoAlbumPhoto.key' => $data['PhotoAlbumPhoto']['key']), false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$this->deleteCommentsByContentKey($data['PhotoAlbumPhoto']['key']);

			$this->commit();

		} catch (Exception $ex) {
			$this->rollback($ex);
		}

		return true;
	}

/**
 * Get workflow conditions
 *
 * @return array Conditions data
 */
	public function getWorkflowConditions() {
		if (Current::permission('content_editable')) {
			$activeConditions = array();
			$latestConditons = array(
				$this->alias . '.is_latest' => true,
			);
		} elseif (Current::permission('photo_albums_photo_creatable')) {
			$activeConditions = array(
				$this->alias . '.is_active' => true,
				$this->alias . '.created_user !=' => Current::read('User.id'),
			);
			// 時限公開条件追加
			/* 現状なし
			if ($this->hasField('public_type')) {
				$publicTypeConditions = $this->_getPublicTypeConditions($this);
				$activeConditions[] = $publicTypeConditions;
			}*/
			$latestConditons = array(
				$this->alias . '.is_latest' => true,
				$this->alias . '.created_user' => Current::read('User.id'),
			);
		} else {
			// 時限公開条件追加
			$activeConditions = array(
				$this->alias . '.is_active' => true,
			);
			/* 現状なし
			if ($this->hasField('public_type')) {
				$publicTypeConditions = $this->_getPublicTypeConditions($this);
				$activeConditions[] = $publicTypeConditions;
			}*/
			$latestConditons = array();
		}

		$langConditions = array(
			$this->alias . '.language_id' => Current::read('Language.id'),
			$this->alias . '.is_translation' => false,
		);

		$conditions = array(
			array('OR' => $langConditions),
			array('OR' => array($activeConditions, $latestConditons))
		);

		return $conditions;
	}

/**
 * Regenerate data for zip
 *
 * @param array $data received post data
 * @return array
 * @throws InternalErrorException
 */
	private function __regenerateDataForZip($data) {
		$files = array();
		$zipType = ['application/zip', 'application/x-zip-compressed'];
		if (in_array($data['PhotoAlbumPhoto']['photo']['type'], $zipType)) {
			$zip = new UnZip($data['PhotoAlbumPhoto']['photo']['tmp_name']);
			$unzipedFolder = $zip->extract();
			$dir = new Folder($unzipedFolder->path);
			$files = $dir->findRecursive('.*\.(jpg|jpeg)');
		}

		if (!$files) {
			return array($data);
		}

		foreach ($files as $file) {
			$file = new File($file);
			$data['PhotoAlbumPhoto']['photo'] = array_merge(
				$data['PhotoAlbumPhoto']['photo'],
				array(
					'name' => $file->name,
					'type' => $file->mime(),
					'tmp_name' => $file->path,
					'size' => $file->size()
				)
			);
			$regenerateData[] = $data;
		}

		return $regenerateData;
	}

}
