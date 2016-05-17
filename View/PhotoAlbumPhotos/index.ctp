<?php
/**
 * photoAlbum content list view template
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
	if ($frameSetting['PhotoAlbumFrameSetting']['display_type'] == PhotoAlbumFrameSetting::DISPLAY_TYPE_ALBUMS) {
		echo $this->BackTo->pageLinkButton(__d("photo_albums", "Back to album list"), array('icon' => 'list'));
	}
?>

<div class="photo-albums-album-information">
	<div class="clearfix">
		<h1 class="pull-left" >
			<?php echo $album['PhotoAlbum']['name']; ?>
		</h1>

		<div class="pull-right photo-albums-album-edit-link">
			<?php
				if ($this->Workflow->canEdit('PhotoAlbum', $album)) {
					echo $this->LinkButton->edit(
						'',
						array(
							'plugin' => 'photo_albums',
							'controller' => 'photo_albums',
							'action' => 'edit',
							'key' =>  $album['PhotoAlbum']['key']
						),
						array(
							'tooltip' => __d('photo_albums', 'Edit album'),
						)
					);
				}
			?>
		</div>
	</div>
	<p>
	<?php echo $album['PhotoAlbum']['description']; ?>
	</p>
</div>

<?php echo $this->element('PhotoAlbums.photo_list_operation'); ?>

<div class="row" ng-controller="PhotoAlbumsPhotoController as PhotoController">
	<?php foreach ($photos as $index => $photo) : ?>
		<div class="col-sm-6 col-md-4">
			<div class="thumbnail">
				<a href="/photo_albums/photo_album_photos/slide?frame_id=37">
					<?php
						echo $this->Html->image(
							array(
								'controller' => 'photo_album_photos',
								'action' => 'photo',
								Current::read('Block.id'),
								$photo['PhotoAlbumPhoto']['album_key'],
								$photo['PhotoAlbumPhoto']['id']
							),
							array(
								'alt' => __d('photo_albums', 'Photo')
							)
						);
					?>
				</a>
				<div class="caption" style="overflow: hidden;height: 4em;">
					<?php echo nl2br($photo['PhotoAlbumPhoto']['description']) ?>
				</div>

				<?php echo $this->PhotoAlbums->photoActionBar($photo); ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<?php echo $this->PhotoAlbums->approveAllButton($photos); ?>

<?php echo $this->element('NetCommons.paginator'); ?>
