<?php
/**
 * PhotoAlbumPhotoGetWorkflowConditions Test Case
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumPhoto', 'PhotoAlbums.Model');
App::uses('PhotoAlbumTestCurrentUtility', 'PhotoAlbums.Test/Case/Model');
App::uses('NetCommonsCakeTestCase', 'NetCommons.TestSuite');

/**
 * Summary for PhotoAlbumPhotoGetWorkflowConditions Test Case
 */
class PhotoAlbumPhotoGetWorkflowConditionsTest extends NetCommonsCakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.photo_albums.photo_album_photo',
	);

	private $originPublishablePermission;
	private $originEditablePermission;
	private $originPermissions = [];
	private $originCurrent =[];

	/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->PhotoAlbumPhoto = ClassRegistry::init('PhotoAlbums.PhotoAlbumPhoto');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->PhotoAlbumPhoto);

		parent::tearDown();
	}

/**
 * getWorkflowConditions test case of has content_editable
 *
 * @return void
 */
	public function testHasContentEditable() {
		Current::write('Language.id', 99);
		$this->__permissionEditable();

		$expected = array (
			array(
				'OR' => array(
					'PhotoAlbumPhoto.language_id' => 99,
					'PhotoAlbumPhoto.is_translation' => false,
				),
			),
			array(
				'OR' => array(
					array (),
					array ('PhotoAlbumPhoto.is_latest' => true),
				)
			),
		);
		$actual = $this->PhotoAlbumPhoto->getWorkflowConditions();
		$this->assertEquals($expected, $actual);

		$this->__restorePermission();
	}

/**
 * getWorkflowConditions test case of has photo_creatable
 *
 * @return void
 */
	public function testHasPhotoCreatable() {
		//$currentValue['Permission']['content_editable']['value'] = false;
		//$currentValue['Permission']['photo_albums_photo_creatable']['value'] = true;
		$this->__setPermission('content_editable', false);
		$this->__setPermission('photo_albums_photo_creatable', true);

		//$currentValue['Language']['id'] = 99;
		//$currentValue['User']['id'] = 88;
		//PhotoAlbumTestCurrentUtility::setValue($currentValue);
		$this->__setCurrent('Language.id', 99);
		$this->__setCurrent('User.id', 88);


		$expected = array (
			array(
				'OR' => array(
					'PhotoAlbumPhoto.language_id' => 99,
					'PhotoAlbumPhoto.is_translation' => false,
				),
			),
			array(
				'OR' => array(
					array (
						'PhotoAlbumPhoto.is_active' => true,
						'PhotoAlbumPhoto.created_user !=' => 88
					),
					array (
						'PhotoAlbumPhoto.is_latest' => true,
						'PhotoAlbumPhoto.created_user' => 88
					)
				)
			),
		);
		$actual = $this->PhotoAlbumPhoto->getWorkflowConditions();
		$this->assertEquals($expected, $actual);

		//PhotoAlbumTestCurrentUtility::setOriginValue();

		$this->__restoreCurrent();
		$this->__restorePermission();
	}

/**
 * getWorkflowConditions test case of not has photo_creatable
 *
 * @return void
 */
	public function testNotHasPhotoCreatable() {
		//$currentValue['Permission']['content_editable']['value'] = false;
		//$currentValue['Permission']['photo_albums_photo_creatable']['value'] = false;
		//$currentValue['Language']['id'] = 99;
		//$currentValue['User']['id'] = 88;
		//PhotoAlbumTestCurrentUtility::setValue($currentValue);
		$this->__setPermission('content_editable', false);
		$this->__setPermission('photo_albums_photo_creatable', false);
		$this->__setCurrent('Language.id', 99);
		$this->__setCurrent('User.id', 88);

		$expected = array (
			array(
				'OR' => array(
					'PhotoAlbumPhoto.language_id' => 99,
					'PhotoAlbumPhoto.is_translation' => false,
				),
			),
			array(
				'OR' => array(
					array (
						'PhotoAlbumPhoto.is_active' => true,
					),
					array ()
				)
			),
		);
		$actual = $this->PhotoAlbumPhoto->getWorkflowConditions();
		$this->assertEquals($expected, $actual);

		//PhotoAlbumTestCurrentUtility::setOriginValue();
		$this->__restoreCurrent();
		$this->__restorePermission();
	}

/**
 * __permissionPublishable
 *
 * @return void
 */
	private function __permissionEditable() {
		$this->__setPermission('content_editable', true);
	}

/**
 * __restorePermission
 *
 * @return void
 */
	private function __restorePermission() {
		Current::writePermission(null, 'content_publishable', $this->originPublishablePermission);
		Current::writePermission(null, 'content_publishable', $this->originEditablePermission);

		foreach ($this->originPermissions as $permission => $permissionValue) {
			Current::writePermission(null, $permission, $permissionValue);
		}
	}

/**
 * __setPermission
 *
 * @param string $permissionKey
 * @param bool $allow
 * @return void
 */
	private function __setPermission($permissionKey, $allow) {
		$this->originPermissions[$permissionKey] = Current::permission($permissionKey);
		Current::writePermission(null, $permissionKey, $allow);
	}

	private function __setCurrent($key, $value) {
		$this->originCurrent[$key] = Current::read($key);
 		Current::write($key, $value);
	}

	private function __restoreCurrent() {
		foreach ($this->originCurrent as $key => $value) {
			Current::write($key, $value);
		}
	}

}
