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

<?php echo $this->NetCommonsHtml->css('/photo_albums/css/photo_albums.css'); ?>
<?php echo $this->NetCommonsHtml->script('/photo_albums/js/photo_albums.js'); ?>
<?php
echo $this->NetCommonsHtml->script('/photo_albums/js/photo_album_photo_edit.js');
?>

<div class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('PhotoAlbum', array('type' => 'file')); ?>

		<div class="panel-body">
			<?php echo $this->NetCommonsForm->hidden('PhotoAlbum.block_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('PhotoAlbum.key'); ?>
			<?php echo $this->NetCommonsForm->hidden('PhotoAlbum.language_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('PhotoAlbum.status'); ?>

			<?php
				echo $this->NetCommonsForm->input(
					'PhotoAlbum.name',
					array(
						'type' => 'text',
						'label' => __d('photo_albums', 'Album Name'),
						'required' => true,
					)
				);
			?>

			<?php
				echo $this->NetCommonsForm->input(
					'PhotoAlbum.description',
					array(
						'type' => 'textarea',
						'label' => __d('photo_albums', 'Album description'),
						'help' => __d('photo_albums', 'You can see this value on album list or top of photo list.'),
					)
				);
			?>

			<?php echo $this->element('PhotoAlbums.jacket_select_' . $this->request->params['action']); ?>

			<hr />
			<?php echo $this->Workflow->inputComment('PhotoAlbum.status'); ?>
		</div>

		<?php echo $this->Workflow->buttons('PhotoAlbum.status', $this->request->referer()); ?>
	<?php echo $this->NetCommonsForm->end(); ?>

	<?php if ($this->request->params['action'] === 'edit' && $this->Workflow->canDelete('PhotoAlbum', $this->request->data)) : ?>
		<div class="panel-footer text-right">
			<?php
				$url = PhotoAlbumsSettingUtility::settingUrl(
					array(
						//'base' => false,	// NetCommonsのUrl関連Helperを使うと必要
						'plugin' => 'photo_albums',
						'controller' => 'photo_albums',
						'action' => 'delete',
						Current::read('Block.id'),
						$this->request->data['PhotoAlbum']['key'],
						'?' => ['frame_id' => Current::read('Frame.id')],
					)
				);
				echo $this->NetCommonsForm->create(
					'PhotoAlbum',
					array(
						'type' => 'delete',
						'url' => $url
					)
				);
			?>
				<?php
					echo $this->Button->delete('',
						sprintf(__d('net_commons', 'Deleting the %s. Are you sure to proceed?'), __d('photo_albums', 'Album'))
					);
				?>
			<?php echo $this->NetCommonsForm->end();?>
		</div>
	<?php endif; ?>
</div>

<?php echo $this->Workflow->comments();
