<?php
/**
 * PhotoAlbumFrameSettings Controller
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('PhotoAlbumsAppController', 'PhotoAlbums.Controller');

/**
 * PhotoAlbumFrameSettings Controller
 *
 * @property PhotoAlbumFrameSetting $PhotoAlbumFrameSetting
 * @property PhotoAlbum $PhotoAlbum
 * @property PhotoAlbumDisplayAlbum $PhotoAlbumDisplayAlbum
 * @property PaginatorComponent $Paginator
 * @property PageLayoutComponent $PageLayout
 * @property PermissionComponent $Permission
 * @property PhotoAlbumsComponent $PhotoAlbums
 */
class PhotoAlbumFrameSettingsController extends PhotoAlbumsAppController {

/**
 * layout
 *
 * @var array
 */
	public $layout = 'NetCommons.setting';

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'PhotoAlbums.PhotoAlbum',
		'PhotoAlbums.PhotoAlbumFrameSetting',
		'PhotoAlbums.PhotoAlbumDisplayAlbum'
	);

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'Pages.PageLayout',
		'NetCommons.Permission' => array(
			'allow' => array(
				'edit' => 'page_editable',
			),
		),
		'Paginator',
		'PhotoAlbums.PhotoAlbums'
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'Blocks.BlockTabs' => array(
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
		),
		'NetCommons.DisplayNumber',
		'NetCommons.Date',
		'NetCommons.TableList',
		'Workflow.Workflow',
		'PhotoAlbums.PhotoAlbumsJson',
	);

/**
 * edit method
 *
 * @return void
 */
	public function edit() {
		$this->PhotoAlbums->initializeSetting();

		if ($this->request->is(array('post', 'put'))) {
			if ($this->PhotoAlbumFrameSetting->savePhotoAlbumFrameSetting($this->request->data)) {
				$this->redirect(NetCommonsUrl::backToPageUrl());
				return;
			}
			$this->NetCommons->handleValidationError($this->PhotoAlbumFrameSetting->validationErrors);
		} else {
			$this->request->data = $this->PhotoAlbumFrameSetting->getFrameSetting();
			$this->request->data['Frame'] = Current::read('Frame');
		}

		$query = array(
			'fields' => array(
				'PhotoAlbumDisplayAlbum.album_key'
			),
			'conditions' => array(
				'frame_key' => Current::read('Frame.key')
			),
			'recursive' => -1
		);
		$displayAlbum = $this->PhotoAlbumDisplayAlbum->find('all', $query);
		$albumKeyArr = [];
		foreach ($displayAlbum as $item) {
			$albumKeyArr[] = $item['PhotoAlbumDisplayAlbum']['album_key'];
		}

		$conditions = $this->PhotoAlbum->getWorkflowConditions();
		$conditions['PhotoAlbum.block_id'] = Current::read('Block.id');
		$this->Paginator->settings = array(
			'PhotoAlbum' => array(
				'order' => array('PhotoAlbum.id' => 'desc'),
				'conditions' => $conditions
			)
		);
		$this->set('albums', $this->Paginator->paginate('PhotoAlbum'));
		$this->set('displayAlbumKeys', $albumKeyArr);
	}
}
