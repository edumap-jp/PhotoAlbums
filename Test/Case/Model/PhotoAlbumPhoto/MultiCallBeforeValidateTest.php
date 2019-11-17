<?php
/**
 * beforeValidateを呼び出す度にvalidateが肥大化しないかのテスト
 */
App::uses('NetCommonsCakeTestCase', 'NetCommons.TestSuite');

/**
 * Class MultiCallBeforeValidateTest
 */
class MultiCallBeforeValidateTest extends NetCommonsCakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.photo_albums.photo_album_photo',
		'plugin.site_manager.site_setting',	// For Files plugin
		//'plugin.users.user',
		//'plugin.workflow.workflow_comment',
	);

/**
 * test2Call
 *
 * @return void
 */
	public function test2Call() {
		$photoModel = ClassRegistry::init('PhotoAlbums.PhotoAlbumPhoto');

		// $this->data['PhotoAlbumPhoto'][$field]['name'])
		$photoModel->data['PhotoAlbumPhoto'][PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME]['name'] = 'foo.jpg';
		$photoModel->beforeValidate();
		$call1stValidate = $photoModel->validate;

		$photoModel->beforeValidate();
		$call2ndValidate = $photoModel->validate;

		$this->assertSame($call1stValidate, $call2ndValidate);
	}
}