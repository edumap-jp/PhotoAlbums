<?php
/**
 * PhotoAlbumPhotos Controller
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppController', 'PhotoAlbums.Controller');
App::uses('PhotoAlbumsSettingUtility', 'PhotoAlbums.Utility');

/**
 * PhotoAlbumPhotos Controller
 *
 * @property PhotoAlbum $PhotoAlbum
 * @property PhotoAlbumPhoto $PhotoAlbumPhoto
 * @property PhotoAlbumFrameSetting $PhotoAlbumFrameSetting
 * @property PaginatorComponent $Paginator
 * @property PageLayoutComponent $PageLayout
 * @property PermissionComponent $Permission
 * @property SecurityComponent $Security
 * @property WorkflowComponent $Workflow
 * @property PhotoAlbumPhotosComponent $PhotoAlbumPhotos
 * @property DownloadComponent $Download
 */
class PhotoAlbumPhotosController extends PhotoAlbumsAppController {

/**
 * use models
 *
 * @var array
 */
	public $uses = array(
		'PhotoAlbums.PhotoAlbum',
		'PhotoAlbums.PhotoAlbumPhoto',
		'PhotoAlbums.PhotoAlbumFrameSetting',
	);

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'NetCommons.Permission' => array(
			'allow' => array(
				'add,edit,delete' => 'photo_albums_photo_creatable',
				'publish' => 'content_publishable',
			),
		),
		'Pages.PageLayout',
		'Paginator',
		'Security',
		'Workflow.Workflow',
		'PhotoAlbums.PhotoAlbumPhotos',
		'PhotoAlbums.PhotoAlbums',
		'Files.Download'
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'Workflow.Workflow',
		'NetCommons.DisplayNumber',
		'Users.DisplayUser',
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('photo', 'slide');
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$frameSetting = $this->PhotoAlbumFrameSetting->getFrameSetting();
		$this->set('frameSetting', $frameSetting);

		$conditions = $this->PhotoAlbumPhoto->getWorkflowConditions();
		$conditions['PhotoAlbumPhoto.album_key'] = $this->request->params['key'];
		$status = Hash::get($this->request->params, ['named', 'status']);
		if ($status) {
			$conditions['PhotoAlbumPhoto.status'] = $status;
		}

		$this->Paginator->settings = array(
			'PhotoAlbumPhoto' => array(
				'sort' => $frameSetting['PhotoAlbumFrameSetting']['photos_sort'],
				'direction' => $frameSetting['PhotoAlbumFrameSetting']['photos_direction'],
				'limit' => $frameSetting['PhotoAlbumFrameSetting']['photos_per_page'],
				'conditions' => $conditions
			)
		);
		$this->set('photos', $this->Paginator->paginate('PhotoAlbumPhoto'));
	}

/**
 * index method
 *
 * @return void
 */
	public function slide() {
		$frameSetting = $this->PhotoAlbumFrameSetting->getFrameSetting();
		$this->set('frameSetting', $frameSetting);

		$conditions = $this->PhotoAlbumPhoto->getWorkflowConditions();
		$conditions['PhotoAlbumPhoto.album_key'] = $this->request->params['key'];

		$this->Paginator->settings = array(
			'PhotoAlbumPhoto' => array(
				'sort' => $frameSetting['PhotoAlbumFrameSetting']['photos_sort'],
				'direction' => $frameSetting['PhotoAlbumFrameSetting']['photos_direction'],
				'limit' => $this->Paginator->settings['maxLimit'],
				'conditions' => $conditions
			)
		);
		$this->set('photos', $this->Paginator->paginate('PhotoAlbumPhoto'));
		$this->set('active', Hash::get($this->request->params['pass'], 0));
	}

/**
 * view method
 *
 * @param string $id id
 * @throws NotFoundException
 * @return void
 */
	public function view($id = null) {
		if (!$this->PhotoAlbumPhoto->exists($id)) {
			throw new NotFoundException(__d('photo_albums', 'Invalid photo album photo'));
		}
		$options = array('conditions' => array(
			'PhotoAlbumPhoto.' . $this->PhotoAlbumPhoto->primaryKey => $id)
		);
		$this->set('photoAlbumPhoto', $this->PhotoAlbumPhoto->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$this->view = 'edit';

		$photo = $this->PhotoAlbumPhoto->create();
		if ($this->request->is('post')) {
			$this->request->data['PhotoAlbumPhoto']['status'] = $this->Workflow->parseStatus();
			if ($this->PhotoAlbumPhoto->savePhoto($this->request->data)) {
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						'plugin' => 'photo_albums',
						'controller' => 'photo_album_photos',
						'action' => 'index',
						Current::read('Block.id'),
						$this->request->params['key'],
						'?' => array('frame_id' => Current::read('Frame.id'))
					)
				);
				$this->redirect($url);
			}

			if (PhotoAlbumsSettingUtility::isSetting()) {
				$this->layout = 'NetCommons.setting';
			}
			$this->NetCommons->handleValidationError($this->PhotoAlbumPhoto->validationErrors);
		} else {
			$this->layout = 'NetCommons.modal';
			$this->request->data = $photo;
			$this->request->data['PhotoAlbumPhoto']['album_key'] = $this->request->params['key'];
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @return void
 */
	public function edit() {
		$query = array(
			'conditions' => $this->PhotoAlbumPhoto->getWorkflowConditions() + array(
				'PhotoAlbumPhoto.album_key' => $this->request->params['key'],
				'PhotoAlbumPhoto.key' => $this->request->params['pass'][0]
			),
			'recursive' => -1,
		);
		$photo = $this->PhotoAlbumPhoto->find('first', $query);

		if (! $this->PhotoAlbumPhoto->canEditWorkflowContent($photo)) {
			$this->throwBadRequest();
			return false;
		}

		if (!$photo) {
			throw new NotFoundException(__d('photo_albums', 'Invalid photo album photo'));
		}
		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$data['PhotoAlbumPhoto']['status'] = $this->Workflow->parseStatus();
			if ($this->PhotoAlbumPhoto->savePhoto($data)) {
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						'plugin' => 'photo_albums',
						'controller' => 'photo_album_photos',
						'action' => 'index',
						Current::read('Block.id'),
						$this->request->params['key'],
						'?' => array('frame_id' => Current::read('Frame.id'))
					)
				);
				$this->redirect($url);
			}
			$this->NetCommons->handleValidationError($this->PhotoAlbum->validationErrors);
			$this->request->data['PhotoAlbumPhoto']['id'] = $photo['PhotoAlbumPhoto']['id'];
		} else {
			$this->request->data = $photo;
		}

		$photoKey = $this->request->data['PhotoAlbumPhoto']['key'];
		$comments = $this->PhotoAlbumPhoto->getCommentsByContentKey($photoKey);
		$this->set('comments', $comments);
	}

/**
 * publish method
 *
 * @throws NotFoundException
 * @return void
 */
	public function publish() {
		if (!$this->request->is(array('post', 'put'))) {
			$this->throwBadRequest();
			return;
		}

		$photoIdArr = [];
		foreach ($this->request->data['PhotoAlbumPhoto'] as $item) {
			$photoIdArr[] = $item['id'];
		}

		$query = array(
			'conditions' => array(
				'PhotoAlbumPhoto.album_key' => $this->request->params['key'],
				'PhotoAlbumPhoto.id' => $photoIdArr
			),
			'recursive' => -1,
			//'callbacks' => 'before',
		);
		$photos = $this->PhotoAlbumPhoto->find('all', $query);

		if (!$this->PhotoAlbumPhoto->publish($photos)) {
			$this->throwBadRequest();
			return;
		}

		$url = PhotoAlbumsSettingUtility::settingUrl(
			array(
				'plugin' => 'photo_albums',
				'controller' => 'photo_album_photos',
				'action' => 'index',
				Current::read('Block.id'),
				$this->request->params['key'],
			)
		) +
		$this->request->params['named'] +
		array('?' => array('frame_id' => Current::read('Frame.id')));

		$this->redirect($url);
	}

/**
 * photo method
 *
 * @throws NotFoundException
 * @return void
 */
	public function photo() {
		App::uses('PhotoAlbumPhoto', 'PhotoAlbums.Model');
		$contentId = $this->request->params['pass'][0];
		$options = array(
			'field' => PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME,
			'size' => Hash::get($this->request->params['pass'], 1, 'medium')
		);

		return $this->Download->doDownload($contentId, $options);
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @return void
 */
	public function delete() {
		if (!$this->request->is(array('post', 'delete'))) {
			$this->throwBadRequest();
			return;
		}

		$query = array(
			'conditions' => $this->PhotoAlbumPhoto->getWorkflowConditions() + array(
				'PhotoAlbumPhoto.album_key' => $this->request->params['key'],
				'PhotoAlbumPhoto.key' => $this->request->params['pass'][0]
			),
			'recursive' => -1,
		);
		$photo = $this->PhotoAlbumPhoto->find('first', $query);

		if (!$this->PhotoAlbumPhoto->canDeleteWorkflowContent($photo)) {
			$this->throwBadRequest();
			return false;
		}

		if (!$this->PhotoAlbumPhoto->deletePhoto($photo)) {
			$this->throwBadRequest();
			return;
		}

		// アルバム一覧表示以外は、ページに戻る？？
		$url = PhotoAlbumsSettingUtility::settingUrl(
			array(
				'plugin' => 'photo_albums',
				'controller' => 'photo_album_photos',
				'action' => 'index',
				Current::read('Block.id'),
				$this->request->params['key'],
				'?' => array('frame_id' => Current::read('Frame.id'))
			)
		);
		$this->redirect($url);
	}
}
