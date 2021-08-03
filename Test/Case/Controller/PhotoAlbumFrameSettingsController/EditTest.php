<?php
/**
 * PhotoAlbumFrameSettingsControllerEdit  Test Case
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('FrameSettingsControllerTest', 'Frames.TestSuite');
App::uses('PhotoAlbumFrameSettingFixture', 'PhotoAlbums.Test/Fixture');

/**
 * Summary for PhotoAlbumFrameSettingsControllerEdit  Test Case
 */
class PhotoAlbumFrameSettingsControllerEditTest extends FrameSettingsControllerTest {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.photo_albums.photo_album_frame_setting',
		'plugin.photo_albums.photo_album_display_album',
		'plugin.photo_albums.photo_album_photo',
		'plugin.photo_albums.photo_album',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'photo_albums';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'photo_album_frame_settings';

/**
 * テストDataの取得
 *
 * @return array
 */
	private function __data() {
		$frameId = '6';
		$frameKey = 'frame_3';
		$settingData = (new PhotoAlbumFrameSettingFixture())->records[0];
		$settingData['id'] = $frameId;
		$settingData['frame_key'] = $frameKey;

		$data = array(
			'Frame' => array(
				'id' => $frameId,
				'frame_key' => $frameKey,
			),
			'PhotoAlbumFrameSetting' => $settingData,
			'PhotoAlbumDisplayAlbum' => array()
		);

		return $data;
	}

/**
 * edit()アクションDataProvider
 *
 * ### 戻り値
 *  - method: リクエストメソッド（get or post or put）
 *  - data: 登録データ
 *  - validationError: バリデーションエラー(省略可)
 *  - exception: Exception Error(省略可)
 *
 * @return array
 */
	public function dataProviderEdit() {
		$data = $this->__data();

		//テストデータ
		$results = array();
		$results[0] = array('method' => 'get');
		$results[1] = array('method' => 'post', 'data' => $data, 'validationError' => false);
		$results[2] = array('method' => 'put', 'data' => $data, 'validationError' => false);
		$results[3] = array('method' => 'put', 'data' => $data, 'validationError' => true);

		return $results;
	}

/**
 * edit()のテスト
 *
 * @param string $method リクエストメソッド（get or post or put）
 * @param array $data 登録データ
 * @param bool $validationError バリデーションエラー
 * @param null|string $exception Exceptions Error
 * @dataProvider dataProviderEdit
 * @return void
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function testEdit($method, $data = null, $validationError = false, $exception = null) {
		if ($exception) {
			$this->setExpectedException($exception);
		}

		//ログイン
		TestAuthGeneral::login($this);

		$frameId = '6';
		if (is_array($validationError)) {
			$data = Hash::insert($data, $validationError['field'], $validationError['value']);
		}

		//アクション実行
		$url = NetCommonsUrl::actionUrl(array(
				'plugin' => $this->plugin,
				'controller' => $this->_controller,
				'action' => 'edit',
				'frame_id' => $frameId,
		));
		$params = array(
				'method' => $method,
				'return' => 'view',
				'data' => $data
		);

		$this->testAction($url, $params);

		//チェック
		if ($exception) {
			//ログアウト
			TestAuthGeneral::logout($this);
			return;
		}

		$asserts = array(
			array(
				'method' => 'assertInput', 'type' => 'form',
				'name' => null, 'value' => $url
			),
			array(
				'method' => 'assertInput', 'type' => 'input',
				'name' => 'data[Frame][id]', 'value' => $frameId
			),
		);

		//バリデーションエラー(エラー表示あり)
		if ($validationError) {
			if ($validationError['message']) {
				array_push($asserts, array(
						'method' => 'assertNotEmpty', 'value' => $this->controller->validationErrors
				));
				array_push($asserts, array(
						'method' => 'assertTextContains', 'expected' => $validationError['message']
				));
			}
		}

		//チェック
		//$this->asserts($asserts, $this->contents);

		//ログアウト
		TestAuthGeneral::logout($this);
	}

/**
 * test Post でのsavePhotoAlbumFrameSetting 失敗
 *
 * @return void
 */
	public function testSavePhotoAlbumFrameSettingFail() {
		$this->_mockForReturnFalse(
			'PhotoAlbums.PhotoAlbumFrameSetting',
			'savePhotoAlbumFrameSetting'
		);

		//ログイン
		TestAuthGeneral::login($this);
		$this->_testPostAction('put',
			$this->__data(),
			array(
				'action' => 'edit',
				'block_id' => 2,
				'frame_id' => 6

			)
		);
		//ログアウト
		TestAuthGeneral::logout($this);
	}
}
