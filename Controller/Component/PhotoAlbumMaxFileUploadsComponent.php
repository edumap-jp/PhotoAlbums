<?php
/**
 * 同時にアップロードできるファイル数を制限する
 */

/**
 * Class PhotoAlbumMaxFileUploadsComponent
 */
class PhotoAlbumMaxFileUploadsComponent extends Component {

/**
 * @var int 同時にアップロードできる最大ファイル数
 */
	private $__maxFileUploads;

/**
 * @var Controller 呼び出し元コントローラ
 */
	private $__controller;

/**
 * startup
 *
 * @param Controller $controller Controller
 * @return void
 */
	public function startup(Controller $controller) {
		parent::startup($controller);
		$this->__controller = $controller;

		Configure::load('PhotoAlbums.config');
		$this->__maxFileUploads = Configure::read('PhotoAlbums.maxFileUploads');
		$this->__controller->set('maxFileUploads', $this->__maxFileUploads);
	}

/**
 * 写真追加、アルバム追加時にアップロードされるファイル数を__maxFileUploadsまでに制限する
 *
 * 超えたファイル情報は切り捨てる
 *
 * @param string $photoPathInData photoの配列パス
 * @return void
 */
	public function removeOverMaxFileUploads($photoPathInData) {
		$photo = Hash::get($this->__controller->request->data, $photoPathInData);

		if (!is_array($photo)) {
			return;
		}

		if (count($photo) <= $this->__maxFileUploads) {
			return;
		}

		$photo = array_slice($photo, 0, $this->__maxFileUploads);
		$this->__controller->request->data = Hash::insert(
			$this->__controller->request->data,
			$photoPathInData,
			$photo
		);
	}

}