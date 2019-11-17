<?php
/**
 *
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
 * @var Controller
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
 * $request->data['PhotoAlbumPhoto']['photo']を__maxFileUploadsまでに制限する
 *
 * 超えたファイル情報は切り捨てる
 *
 * @param string $requestPath photoの配列パス
 * @return void
 */
	public function removeOverMaxFileUploads(string $requestPath) {
		//// 写真の追加時はPhotoAlbumPhoto.photoが配列になる
		//// アルバムの追加時はPhotoAlbumPhotoが配列
		$photo = Hash::get($this->__controller->request->data, $requestPath);

		if (!is_array($photo)) {
			return;
		}

		if (count($photo) <= $this->__maxFileUploads) {
			return;
		}

		$photo = array_slice($photo, 0, $this->__maxFileUploads);
		$this->__controller->request->data = Hash::insert($this->__controller->request->data, $requestPath, $photo);
	}

}