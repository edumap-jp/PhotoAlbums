<?php
/**
 * Album add template
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */
?>

<?php $this->assign('title_for_modal', __d('photo_albums', 'Add photo')); ?>

<div class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('PhotoAlbumPhoto', array('type' => 'file')); ?>
		<div class="panel-body">
			<?php if (!empty($this->request->data['PhotoAlbumPhoto']['key'])): ?>
				<div class="thumbnail">
					<?php
						echo $this->Html->image(
							array(
								'controller' => 'photo_album_photos',
								'action' => 'photo',
								Current::read('Block.id'),
								$this->request->data['PhotoAlbumPhoto']['album_key'],
								$this->request->data['PhotoAlbumPhoto']['id']
							),
							array(
								'alt' => __d('photo_albums', 'Photo')
							)
						);
					?>
				</div>
			<?php endif; ?>

			<?php echo $this->NetCommonsForm->hidden('album_key'); ?>
			<?php echo $this->NetCommonsForm->hidden('key'); ?>
			<?php echo $this->NetCommonsForm->hidden('language_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('block_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('status'); ?>

			<?php
			if (!empty($isAdd)) {
				$this->Form->unlockField(
					'PhotoAlbumPhoto.' . PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME
				);
				// バリデーションエラーが表示されるようにフィールド名はモデルフィールド名のままで、name属性で複数可能なフィールド指定
				echo $this->NetCommonsForm->uploadFile(
					'PhotoAlbumPhoto.' . PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME,
					array(
						'label' => __d('photo_albums', 'Photo file'),
						'required' => true,
						'multiple',
						'name' => 'data[PhotoAlbumPhoto][photo][]',
					)
				);
			} else {
				echo $this->NetCommonsForm->uploadFile(
					PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME,
					array(
						'label' => __d('photo_albums', 'Photo file'),
						'remove' => false
					)
				);
			}

			?>

			<?php
				echo $this->NetCommonsForm->input(
					'description',
					array(
						'type' => 'textarea',
						'label' => __d('photo_albums', 'Photo description'),
					)
				);
			?>

			<hr />
			<?php echo $this->Workflow->inputComment('PhotoAlbumPhoto.status'); ?>
		</div>

		<?php
			// ＴＯＤＯ 追加の場合modal閉じない。リンク先のURLが同じためと思われる。
			echo $this->Workflow->buttons('PhotoAlbumPhoto.status');
		?>
	<?php echo $this->NetCommonsForm->end(); ?>

	<?php if ($this->request->params['action'] === 'edit' && $this->Workflow->canDelete('PhotoAlbumPhoto', $this->request->data)) : ?>
		<div class="panel-footer text-right">
			<?php
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						//'base' => false,	// NetCommonsのUrl関連Helperを使うと必要
						'plugin' => 'photo_albums',
						'controller' => 'photo_album_photos',
						'action' => 'delete',
						Current::read('Block.id'),
						$this->request->data['PhotoAlbumPhoto']['album_key'],
						$this->request->data['PhotoAlbumPhoto']['key'],
						'?' => ['frame_id' => Current::read('Frame.id')],
					)
				);
				echo $this->NetCommonsForm->create(
					'PhotoAlbumPhoto',
					array(
						'type' => 'delete',
						'url' => $url
					)
				);
			?>
				<?php
					echo $this->Button->delete('',
						sprintf(__d('net_commons', 'Deleting the %s. Are you sure to proceed?'), __d('photo_albums', 'Photo'))
					);
				?>
			<?php echo $this->NetCommonsForm->end(); ?>
		</div>
	<?php endif; ?>
</div>


<?php echo $this->Workflow->comments();
