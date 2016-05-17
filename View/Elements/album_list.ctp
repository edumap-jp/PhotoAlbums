<?php
/**
 * PhotoAlbum album list template
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */
?>

<div class="row">
	<?php foreach ($albums as $album) : ?>
		<div class="col-md-6">
			<a href="
				<?php
					echo $this->NetCommonsHtml->url(
						array(
							'controller' => 'photo_album_photos',
							'action' => 'index',
							$album['PhotoAlbum']['key']
						)
					);
				?>
			">
				<?php echo $this->PhotoAlbums->jacketByBackground($album); ?>
				<?php //echo $this->PhotoAlbums->jacket($album); //imgタグ ?>
			</a>

			<div class="carousel-caption photo-albums-caption">
				<h4><?php echo $album['PhotoAlbum']['name']; ?></h4>
				<?php echo $this->Workflow->label($album['PhotoAlbum']['status']); ?>
				<p class="photo-albums-description">
					<?php echo $album['PhotoAlbum']['description']; ?>
				</p>

				<div class="clearfix">
					<div class='pull-left'>
						<div class="label label-info"><?php echo __d('photo_albums', '%s photos', $album['PhotoAlbum']['photo_count']); ?></div>
						<?php
							if (Current::permission('content_publishable') &&
								$album['PhotoAlbum']['pending_photo_count']
							) {
								echo '<div class="label label-warning">' .
										__d('photo_albums', '%s pending approval', $album['PhotoAlbum']['pending_photo_count']) .
										'</div>';
							}
							// 改行分の隙間空ける
						?>
						<?php
							if (Current::permission('photo_albums_photo_creatable') &&
								$album['PhotoAlbum']['disapproved_photo_count']
							) {
								echo '<div class="label label-warning">' .
										__d('photo_albums', '%s denied', $album['PhotoAlbum']['disapproved_photo_count']) .
										'</div>';
							}
						?>
					</div>
					<div class='pull-right'>
						<div class="label"><?php echo $this->DisplayUser->handleLink($album); ?></div>
						<div class="label"><?php echo $this->Date->dateFormat($album['PhotoAlbum']['modified']); ?></div>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<table class="table table-hover" ng-controller="PhotoAlbumsPhotoController as PhotoController">
	<tbody>
		<?php foreach ($albums as $album) : ?>
			<tr>
				<td>
					<!--
					<div class="media">
						<div class="media-left col-sm-2 col-md-3">
					 -->
						<div class="pull-left">
							<div class="thumbnail" style="width: 150px;height:150px;">
								<?php echo $this->PhotoAlbums->jacket($album); ?>
							</div>
						</div>

						<!-- <div class='media-body' style='padding:10px 30px;'> -->
						<div class="" style='margin:0 10px 10px 180px;'>
							<?php
								/*echo $this->NetCommonsHtml->link(
									$album['Block']['name'],
									$this->NetCommonsHtml->url(
										array(
											'plugin' => 'photo_albums',
											'controller' => 'photo_album_photos',
											'action' => 'slide'
										)
									)
								);*/
								echo '<h2 style="display: inline-block;margin:15px 10px; 0">' . $album['PhotoAlbum']['name'] . '</h2>';
								echo $this->Workflow->label($album['PhotoAlbum']['status']);
							?>
							<p style="margin: 0 0 5px 20px;height: 3em;overflow: hidden;">
								<?php echo $album['PhotoAlbum']['description']; ?>
							</p>

							<div>
								<?php if ($album['PhotoAlbum']['photo_count']): ?>
									<button type="button" class="btn btn-default" ng-click="PhotoController.slide('
										<?php
											echo $this->NetCommonsHtml->url(
												array(
													'plugin' => 'photo_albums',
													'controller' => 'photo_album_photos',
													'action' => 'slide',
													$album['PhotoAlbum']['key'],
												)
											)
										?>')">
										<span class="glyphicon glyphicon-play" aria-hidden="true"></span> <?php echo __d('photo_albums', 'Slide show'); ?>
									</button>
									<a href="<?php echo $this->NetCommonsHtml->url(array('controller' => 'photo_album_photos', 'action' => 'index', $album['PhotoAlbum']['key'])); ?>" class="btn btn-default">
										<span class="glyphicon glyphicon-th"></span> <?php echo __d('photo_albums', 'Photo list'); ?>
									</a>
								<?php elseif (Current::permission('photo_albums_photo_creatable')): ?>
									<?php
										echo $this->Button->addLink(
											__d('photo_albums', 'Add photo'),
											'#',
											array(
												'ng-click' => 'PhotoController.openAdd(\'' .
													$this->NetCommonsHtml->url(
														array(
															'plugin' => 'photo_albums',
															'controller' => 'photo_album_photos',
															'action' => 'add',
															$album['PhotoAlbum']['key'],
														)
													) .
												'\')'
											)
										);
									?>
								<?php endif; ?>
							</div>

						</div>
					</div>
				</td>

				<td style="padding:40px 20px 0 0;">
					<?php
						echo __d('photo_albums', '%s photos', $album['PhotoAlbum']['photo_count']);
						if (Current::permission('content_publishable')) {
							echo '<br><span class="label label-warning">' .
									__d('photo_albums', '%s pending approval', $album['PhotoAlbum']['pending_photo_count']) .
									'</span>';
						}
						if (Current::permission('photo_albums_photo_creatable')) {
							echo '<br><span class="label label-warning">' .
									__d('photo_albums', '%s denied', $album['PhotoAlbum']['disapproved_photo_count']) .
									'</span>';
						}
					?>
					<br>
					<br>
					<?php echo $this->DisplayUser->handleLink($album); ?>
					<br>
					<?php echo $this->Date->dateFormat($album['PhotoAlbum']['modified']); ?>
				</td>

				<td style="vertical-align:middle;">
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
									'tooltip' => __d('photo_albums', 'Edit album')
								)
							);
						}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php echo $this->element('NetCommons.paginator'); ?>
