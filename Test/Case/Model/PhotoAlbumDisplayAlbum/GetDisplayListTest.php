<?php
/**
 * PhotoAlbumGetDisplayList Test Case
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('NetCommonsModelTestCase', 'NetCommons.TestSuite');
App::uses('PhotoAlbumDisplayAlbum', 'PhotoAlbums.Model');
App::uses('PhotoAlbumTestCurrentUtility', 'PhotoAlbums.Test/Case/Model');

/**
 * Summary for PhotoAlbumGetDisplayList Test Case
 */
class PhotoAlbumGetDisplayListTest extends NetCommonsModelTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.photo_albums.photo_album_display_album',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->PhotoAlbumDisplayAlbum = ClassRegistry::init('PhotoAlbums.PhotoAlbumDisplayAlbum');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->PhotoAlbumDisplayAlbum);

		parent::tearDown();
	}

/**
 * getDisplayList method test
 *
 * @return void
 */
	public function testGetDisplayList() {
		$currentValue['Frame']['key'] = 'Lorem ipsum dolor sit amet';
		PhotoAlbumTestCurrentUtility::setValue($currentValue);

		$expected[1] = 'Lorem ipsum dolor sit amet';
		$actual = $this->PhotoAlbumDisplayAlbum->getDisplayList();

		$this->assertEquals($expected, $actual);
		PhotoAlbumTestCurrentUtility::setOriginValue();
	}
}
