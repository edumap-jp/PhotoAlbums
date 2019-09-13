<?php
/**
 * PhotoAlbumPhotoPublish Test Case
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumPhoto', 'PhotoAlbums.Model');
App::uses('PhotoAlbumTestCurrentUtility', 'PhotoAlbums.Test/Case/Model');
App::uses('Current', 'NetCommons.Utility');
App::uses('NetCommonsCakeTestCase', 'NetCommons.TestSuite');

/**
 * Summary for PhotoAlbumPhotoPublish Test Case
 *
 * @property PhotoAlbumPhoto $PhotoAlbumPhoto
 */
class PhotoAlbumPhotoPublishTest extends NetCommonsCakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.photo_albums.photo_album_photo',
		'plugin.site_manager.site_setting',	// For Files plugin
		'plugin.users.user',
		'plugin.workflow.workflow_comment',
	);
	private $originPermission;

	/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Current::write('Language.id', '1');
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
 * publish test
 *
 * @return void
 */
	public function testPublish() {
		//$currentValue['Permission']['content_publishable']['value'] = true;
		//PhotoAlbumTestCurrentUtility::setValue($currentValue);

		$this->__permissionPublishable();

		$data['PhotoAlbumPhoto']['id'] = 1;
		$data['PhotoAlbumPhoto']['language_id'] = 1;
		$actual = $this->PhotoAlbumPhoto->publish([$data]);
		$this->assertTrue($actual);

		//PhotoAlbumTestCurrentUtility::setOriginValue();

		$this->__restorePermission();
	}

/**
 * publish test case of no permission
 *
 * @return void
 */
	public function testPublishNoPermission() {
		$data['PhotoAlbumPhoto']['id'] = 1;
		$data['PhotoAlbumPhoto']['language_id'] = 1;
		$actual = $this->PhotoAlbumPhoto->publish([$data]);
		$this->assertFalse($actual);
	}

/**
 * publish test case of exception
 *
 * @return void
 */
	public function testPublishException() {
		$MockPhotoAlbumPhoto = $this->getMockForModel('PhotoAlbums.PhotoAlbumPhoto', ['save']);
		$MockPhotoAlbumPhoto
			->expects($this->once())
			->method('save')
			->will($this->returnValue(false));

		//$currentValue['Permission']['content_publishable']['value'] = true;
		//PhotoAlbumTestCurrentUtility::setValue($currentValue);
		$this->__permissionPublishable();

		$this->setExpectedException('InternalErrorException');
		$data['PhotoAlbumPhoto']['id'] = 1;
		$data['PhotoAlbumPhoto']['language_id'] = 1;
		$MockPhotoAlbumPhoto->publish([$data]);

		//PhotoAlbumTestCurrentUtility::setOriginValue();
		$this->__restorePermission();
	}

/**
 * __permissionPublishable
 *
 * @return void
 */
	private function __permissionPublishable() {
		$this->originPermission = Current::permission('content_publishable');
		Current::writePermission(null, 'content_publishable', true);
	}

/**
 * __restorePermission
 *
 * @return void
 */
	private function __restorePermission() {
		Current::writePermission(null, 'content_publishable', $this->originPermission);
	}

}
