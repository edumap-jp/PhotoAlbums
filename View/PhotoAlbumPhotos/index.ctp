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

<header>
	<?php echo $this->PhotoAlbums->albumListLink(); ?>
</header>

<article class="panel panel-default photo-albums-album-information">
	<div class="panel-body">
		<div class="clearfix">
			<div class="pull-right photo-albums-album-edit-link">
				<?php
					if ($this->Workflow->canEdit('PhotoAlbum', $album)) {
						$url = PhotoAlbumsSettingUtility::settingUrl(
							array(
								'base' => false,
								'plugin' => 'photo_albums',
								'controller' => 'photo_albums',
								'action' => 'edit',
								Current::read('Block.id'),
								$album['PhotoAlbum']['key'],
								'?' => ['frame_id' => Current::read('Frame.id')],
							)
						);
						echo $this->LinkButton->edit(
							__d('photo_albums', 'Edit album'),
							$this->Html->url($url),
							array(
								'tooltip' => __d('photo_albums', 'Edit album'),
							)
						);
					}
				?>
			</div>

			<div class="pull-left">
				<div class="photo-albums-album-list-status">
					<?php echo $this->Workflow->label($album['PhotoAlbum']['status']) ?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-3 hidden-xs">
				<?php //echo $this->PhotoAlbums->jacketByBackground($album); ?>
				<?php echo $this->PhotoAlbums->jacket($album); //imgタグ ?>
			</div>

			<div class="col-sm-9">
				<h1 class="photo-albums-album-list-caption">
					<?php echo h($album['PhotoAlbum']['name']); ?>
				</h1>

				<div class="photo-albums-album-note small">
					<span>
						<?php echo __d('photo_albums', '%s photos', $album['PhotoAlbum']['photo_count']); ?>
					</span>
					<span>
						<?php echo __d('photo_albums', 'Updated date:%s', $this->Date->dateFormat($album['PhotoAlbum']['modified'])); ?>
					</span>
					<span>
						<?php echo __d('photo_albums', 'Created date:%s', $this->Date->dateFormat($album['PhotoAlbum']['created'])); ?>
					</span>
					<span>
						<?php echo $this->DisplayUser->handleLink($album, array('avatar' => true)); ?>
					</span>
				</div>

				<!--
				<?php if ($album['PhotoAlbum']['description']) : ?>
					<button class="btn btn-default btn-xs" type="button" data-toggle="collapse" data-target="#photo-albums-description<?php echo $frameSetting['PhotoAlbumFrameSetting']['id']; ?>" aria-expanded="false" aria-controls="collapseExample">
						<?php echo __d('photo_albums', 'Album description'); ?>
					</button>
					<div class="collapse" id="photo-albums-description<?php echo $frameSetting['PhotoAlbumFrameSetting']['id']; ?>">
						<?php echo nl2br(h($album['PhotoAlbum']['description'])); ?>
					</div>
				<?php endif; ?>
				-->
				<div>
					<?php echo nl2br(h($album['PhotoAlbum']['description'])); ?>
				</div>
			</div>
		</div>
	</div>
</article>

<?php echo $this->element('PhotoAlbums.photo_list_operation'); ?>

<div class="row photo-albums-photo-list" ng-controller="PhotoAlbumsPhotoController as PhotoController">
	<?php foreach ($photos as $index => $photo) : ?>
		<div class="col-lg-3 col-md-4 col-sm-6">
			<div class="thumbnail">
				<div class="photo-albums-photo-thumbnail">
					<a href="#" ng-click="PhotoController.slide('
						<?php
							echo $this->NetCommonsHtml->url(
								array(
									//'plugin' => 'photo_albums',
									'controller' => 'photo_album_photos',
									'action' => 'slide',
									$album['PhotoAlbum']['key'],
									(($this->Paginator->current() - 1) * $this->Paginator->param('limit')) + $index,
									'sort' => $this->Paginator->sortKey('PhotoAlbumPhoto'),
									'direction' => $this->Paginator->sortDir('PhotoAlbumPhoto'),
								)
							)
						?>
					')">

						<div class="photo-albums-photo" style="background-image:url(<?php
						echo $this->PhotoAlbumsImage->inlinePhotoIfActiveOtherwisePhotoUrl($photo, 'small');
						//echo $this->PhotoAlbumsImage->inlinePhoto($photo, 'thumb');
						//echo $this->PhotoAlbumsImage->photoUrl($photo, 'small');
						?>);"></div>
					</a>

					<div class="photo-albums-photo-list-status">
						<?php echo $this->Workflow->label($photo['PhotoAlbumPhoto']['status']); ?>
					</div>

					<?php echo $this->PhotoAlbums->photoActionBar($photo); ?>
				</div>

				<div class="photo-albums-photo-description small">
					<?php echo h($photo['PhotoAlbumPhoto']['description']); ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<?php echo $this->PhotoAlbums->approveAllButton($photos); ?>

<?php echo $this->element('NetCommons.paginator');
