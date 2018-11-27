<?php
/**
 * PhotoAlbums Controller
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppController', 'PhotoAlbums.Controller');
App::uses('PhotoAlbumsSettingUtility', 'PhotoAlbums.Utility');

/**
 * PhotoAlbums Controller
 *
 * @property PhotoAlbum $PhotoAlbum
 * @property PhotoAlbumPhoto $PhotoAlbumPhoto
 * @property PhotoAlbumFrameSetting $PhotoAlbumFrameSetting
 * @property PhotoAlbumDisplayAlbum $PhotoAlbumDisplayAlbum
 * @property PaginatorComponent $Paginator
 * @property PageLayoutComponent $PageLayout
 * @property SecurityComponent $Security
 * @property WorkflowComponent $Workflow
 * @property DownloadComponent $Download
 */
class PhotoAlbumsController extends PhotoAlbumsAppController {

/**
 * use models
 *
 * @var array
 */
	public $uses = array(
		'PhotoAlbums.PhotoAlbum',
		'PhotoAlbums.PhotoAlbumPhoto',
		'PhotoAlbums.PhotoAlbumFrameSetting',
		'PhotoAlbums.PhotoAlbumDisplayAlbum',
	);

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'NetCommons.Permission' => array(
			'allow' => array(
				'add,edit,delete' => 'content_creatable',
				'setting' => 'page_editable'
			),
		),
		'Pages.PageLayout',
		'Paginator',
		'Security',
		'Workflow.Workflow',
		'Files.Download',
		'PhotoAlbums.PhotoAlbums',
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'Workflow.Workflow',
		'Users.DisplayUser',
		'NetCommons.DisplayNumber',
		'PhotoAlbums.PhotoAlbums',
		'PhotoAlbums.PhotoAlbumsImage',
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('jacket');
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$frameSetting = $this->PhotoAlbumFrameSetting->getFrameSetting();
		$this->set('frameSetting', $frameSetting);

		// PhotoAlbumDisplayAlbumはフレームキー毎にアルバムを管理している。
		// 本来アルバムはルーム単位で管理するもののため、PhotoAlbumDisplayAlbumは利用しない。
		// ここ以外でPhotoAlbumDisplayAlbum->getDisplayList()は利用されていない。
		//$displayAlbum = $this->PhotoAlbumDisplayAlbum->getDisplayList();

		$conditions = $this->PhotoAlbum->getWorkflowConditions();
		$conditions['PhotoAlbum.block_id'] = Current::read('Block.id');
		//$conditions['PhotoAlbum.key'] = $displayAlbum;

		$this->Paginator->settings = array(
			'PhotoAlbum' => array(
				'sort' => $frameSetting['PhotoAlbumFrameSetting']['albums_sort'],
				'direction' => $frameSetting['PhotoAlbumFrameSetting']['albums_direction'],
				'limit' => $frameSetting['PhotoAlbumFrameSetting']['albums_per_page'],
				'conditions' => $conditions
			)
		);
		$albums = $this->Paginator->paginate('PhotoAlbum');
		$this->set('albums', $albums);
		if (empty($albums)) {
			return;
		}

		$displayType = $frameSetting['PhotoAlbumFrameSetting']['display_type'];
		if ($displayType != PhotoAlbumFrameSetting::DISPLAY_TYPE_ALBUMS) {
			$this->set('album', $albums[0]);

			$conditions = $this->PhotoAlbumPhoto->getWorkflowConditions();
			$conditions['PhotoAlbumPhoto.album_key'] = $albums[0]['PhotoAlbum']['key'];

			$this->Paginator->settings = array(
				'PhotoAlbumPhoto' => array(
					'sort' => $frameSetting['PhotoAlbumFrameSetting']['photos_sort'],
					'direction' => $frameSetting['PhotoAlbumFrameSetting']['photos_direction'],
					'conditions' => $conditions
				)
			);
			$this->set('photos', $this->Paginator->paginate('PhotoAlbumPhoto'));
		}
		if ($displayType == PhotoAlbumFrameSetting::DISPLAY_TYPE_SLIDE) {
			$this->set('active', 0);
		}

		if ($displayType == PhotoAlbumFrameSetting::DISPLAY_TYPE_PHOTOS) {
			$this->view = 'PhotoAlbumPhotos/index';
		}

		if ($displayType == PhotoAlbumFrameSetting::DISPLAY_TYPE_SLIDE) {
			$this->view = 'PhotoAlbumPhotos/slide';
		}
	}

/**
 * settingList method
 *
 * @return void
 */
	public function setting() {
		$this->PhotoAlbums->initializeSetting();

		$this->helpers['Blocks.BlockTabs'] = array(
			'mainTabs' => array(
				'block_index' => array(
					'url' => array(
						'plugin' => 'photo_albums',
						'controller' => 'photo_albums',
						'action' => 'setting'
					),
					'label' => array('net_commons', 'List')
				),
				'frame_settings',
				'role_permissions'
			)
		);
		$this->helpers[] = 'NetCommons.TableList';
		$this->helpers[] = 'Blocks.BlockIndex';

		$frameSetting = $this->PhotoAlbumFrameSetting->getFrameSetting();

		$conditions = $this->PhotoAlbum->getWorkflowConditions();
		$conditions['PhotoAlbum.block_id'] = Current::read('Block.id');
		$this->Paginator->settings = array(
			'PhotoAlbum' => array(
				'sort' => $frameSetting['PhotoAlbumFrameSetting']['albums_sort'],
				'direction' => $frameSetting['PhotoAlbumFrameSetting']['albums_direction'],
				'limit' => $frameSetting['PhotoAlbumFrameSetting']['albums_per_page'],
				'conditions' => $conditions
			)
		);
		$this->set('albums', $this->Paginator->paginate('PhotoAlbum'));

		$query = array(
			'fields' => array(
				'PhotoAlbumDisplayAlbum.album_key'
			),
			'conditions' => array(
				'PhotoAlbumDisplayAlbum.frame_key' => Current::read('Frame.key')
			),
			'recursive' => -1
		);
		$this->set('displayAlbumKeys', $this->PhotoAlbumDisplayAlbum->find('list', $query));
	}

/**
 * view method
 *
 * @param string $id id
 * @throws NotFoundException
 * @return void
 */
	public function view($id = null) {
		if (!$this->PhotoAlbum->exists($id)) {
			throw new NotFoundException(__d('photo_albums', 'Invalid photo album'));
		}
		$options = array('conditions' => array('PhotoAlbum.' . $this->PhotoAlbum->primaryKey => $id));
		$this->set('photoAlbum', $this->PhotoAlbum->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$this->PhotoAlbums->initializeSetting();
		$this->view = 'edit';
		$this->set('photos', array());

		$album = $this->PhotoAlbum->create();
		if ($this->request->is('post')) {
			$this->request->data['PhotoAlbum']['status'] = $this->Workflow->parseStatus();
			$album = $this->PhotoAlbum->saveAlbumForAdd($this->request->data);
			if ($album) {
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						'controller' => 'photo_album_photos',
						'action' => 'index',
						Current::read('Block.id'),
						$album['PhotoAlbum']['key'],
						'?' => array('frame_id' => Current::read('Frame.id'))
					)
				);
				$this->redirect($url);
			}
			$this->NetCommons->handleValidationError($this->PhotoAlbum->validationErrors);
		} else {
			$this->request->data = $album;
		}
	}

/**
 * edit method
 *
 * @param string $id id
 * @throws NotFoundException
 * @return void
 */
	public function edit($id = null) {
		$query = array(
			'conditions' => $this->PhotoAlbum->getWorkflowConditions() + array(
				'PhotoAlbum.block_id' => Current::read('Block.id'),
				'PhotoAlbum.key' => $this->request->params['key']
			),
			'recursive' => -1,
		);
		$album = $this->PhotoAlbum->find('first', $query);

		if (!$this->PhotoAlbum->canEditWorkflowContent($album)) {
			$this->throwBadRequest();
			return false;
		}

		if (!$album) {
			throw new NotFoundException(__d('photo_albums', 'Invalid photo album'));
		}

		$frameSetting = $this->PhotoAlbumFrameSetting->getFrameSetting();
		$sort = $frameSetting['PhotoAlbumFrameSetting']['photos_sort'];
		$direction = $frameSetting['PhotoAlbumFrameSetting']['photos_direction'];
		$query = array(
			'conditions' => $this->PhotoAlbumPhoto->getWorkflowConditions() + array(
				'PhotoAlbumPhoto.album_key' => $album['PhotoAlbum']['key'],
			),
			'order' => [$sort => $direction],
			'recursive' => -1,
		);
		$this->set('photos', $this->PhotoAlbumPhoto->find('all', $query));

		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;
			$data['PhotoAlbum']['status'] = $this->Workflow->parseStatus();
			if ($this->PhotoAlbum->saveAlbumForEdit($data)) {
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						'controller' => 'photo_album_photos',
						'action' => 'index',
						Current::read('Block.id'),
						$data['PhotoAlbum']['key'],
						'?' => array('frame_id' => Current::read('Frame.id'))
					)
				);
				$this->redirect($url);
			}
			$this->NetCommons->handleValidationError($this->PhotoAlbum->validationErrors);
			$this->request->data['PhotoAlbum']['id'] = $album['PhotoAlbum']['id'];
		} else {
			$this->request->data = $album;
		}

		$albumKey = $this->request->data['PhotoAlbum']['key'];
		$comments = $this->PhotoAlbumPhoto->getCommentsByContentKey($albumKey);
		$this->set('comments', $comments);
	}

/**
 * jacket method
 *
 * @throws NotFoundException
 * @return void
 */
	public function jacket() {
		App::uses('PhotoAlbum', 'PhotoAlbums.Model');
		$contentId = $this->request->params['key'];
		$options = array(
			'field' => PhotoAlbum::ATTACHMENT_FIELD_NAME,
			'size' => Hash::get($this->request->params['pass'], 0, 'medium')
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
			'conditions' => $this->PhotoAlbum->getWorkflowConditions() + array(
				'PhotoAlbum.block_id' => Current::read('Block.id'),
				'PhotoAlbum.key' => $this->request->params['key']
			),
			'recursive' => -1,
		);
		$album = $this->PhotoAlbum->find('first', $query);

		if (!$this->PhotoAlbum->canDeleteWorkflowContent($album)) {
			$this->throwBadRequest();
			return false;
		}

		if (!$this->PhotoAlbum->deleteAlbum($album)) {
			$this->throwBadRequest();
			return;
		}

		if (!PhotoAlbumsSettingUtility::isSetting()) {
			$this->redirect(NetCommonsUrl::backToPageUrl());
		}

		$this->redirect(
			array(
				'action' => 'setting',
				'?' => array('frame_id' => Current::read('Frame.id'))
			)
		);
	}
}
