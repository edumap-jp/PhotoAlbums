<?php
/**
 * PhotoAlbums photo slide template
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */
?>

<?php if ($frameSetting['PhotoAlbumFrameSetting']['display_type'] != PhotoAlbumFrameSetting::DISPLAY_TYPE_SLIDE): ?>
	<script type="text/ng-template" id="net-commons/template/photoalbums/carousel.html">
		<div class="carousel-inner" ng-transclude></div>
		<a role="button" href class="left carousel-control" ng-click="prev()" ng-class="{ disabled: isPrevDisabled() }" ng-show="slides.length > 1">
			<span aria-hidden="true" class="glyphicon glyphicon-chevron-left"></span>
			<span class="sr-only">previous</span>
		</a>
		<a role="button" href class="right carousel-control" ng-click="next()" ng-class="{ disabled: isNextDisabled() }" ng-show="slides.length > 1">
			<span aria-hidden="true" class="glyphicon glyphicon-chevron-right"></span>
			<span class="sr-only">next</span>
		</a>
		<ol class="carousel-indicators" ng-show="slides.length > 1">
			<li ng-repeat="slide in slides | orderBy:indexOfSlide track by $index" ng-show="$index > active - 3 && $index < active + 3" ng-class="{ active: isActive(slide) }" ng-click="select(slide)">
				<img ng-src="{{slide.slide.actual}}">
				<span class="sr-only">slide {{ $index + 1 }} of {{ slides.length }}<span ng-if="isActive(slide)">, currently active</span></span>
			</li>
			<div>
				{{active + 1}}/{{slides.length}}
			</div>
		</ol>
	</script>
<?php endif; ?>

<?php
echo $this->NetCommonsHtml->css('/photo_albums/css/photo_albums.css');

$srcPrefix = $this->Html->url(
	array(
		'plugin' => 'photo_albums',
		'controller' => 'photo_album_photos',
		'action' => 'photo',
		Current::read('Block.id'),
		$album['PhotoAlbum']['key']
	)
) . '/';
?>

<div uib-carousel
	active="<?php echo $active; ?>"
	interval="5000"
	<?php if ($frameSetting['PhotoAlbumFrameSetting']['display_type'] != PhotoAlbumFrameSetting::DISPLAY_TYPE_SLIDE): ?>
		template-url="net-commons/template/photoalbums/carousel.html"
	<?php endif; ?>
>
	<!-- Wrapper for slides -->
	<?php $height = 0; ?>
	<?php foreach ($photos as $index => $photo) : ?>
		<div uib-slide
			index="<?php echo $index ?>"
			actual="'<?php echo $srcPrefix . $photo['PhotoAlbumPhoto']['id']; ?>' + '/thumb'"
		>
			<?php
				/*  imgタグ
				echo $this->Html->image(
					array(
						'controller' => 'photo_album_photos',
						'action' => 'photo',
						Current::read('Block.id'),
						$photo['PhotoAlbumPhoto']['album_key'],
						$photo['PhotoAlbumPhoto']['id']
					),
					array(
						'alt' => __d('photo_albums', 'Photo'),
						//'style' => 'display: inline',
						'class' => 'img-responsive center-block'
					)
				);
				*/
			?>
			<div
				class="photo-albums-slide-photo"
				style="
					background-image:url('<?php echo $srcPrefix . $photo['PhotoAlbumPhoto']['id']; ?>/big');
					<?php
						if ($frameSetting['PhotoAlbumFrameSetting']['display_type'] == PhotoAlbumFrameSetting::DISPLAY_TYPE_SLIDE &&
							isset($frameSetting['PhotoAlbumFrameSetting']['slide_height'])
						) {
							if ($frameSetting['PhotoAlbumFrameSetting']['slide_height'] >= 1) {
								echo 'height:' . $frameSetting['PhotoAlbumFrameSetting']['slide_height'] . 'px; background-size:contain;';
							} else {
								if (empty($height)) {
									list($width, $height) = getimagesize( UPLOADS_ROOT . $photo['UploadFile']['photo']['path'] . $photo['UploadFile']['photo']['id'] . DS . $photo['UploadFile']['photo']['real_file_name']);
								}
								echo 'padding-top:' . ($height / $width * 100) . '%; background-size:contain;';
							}
						}
					?>
				"
			>
			</div>
			<div class="carousel-caption">
				<?php echo nl2br(h($photo['PhotoAlbumPhoto']['description'])); ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<button class="close photo-albums-slide-modal-close"
	type="button"
	tooltip="<?php echo __d('net_commons', 'Close'); ?>"
	ng-click="cancel()"
>
	<span class="glyphicon glyphicon-remove"></span>
</button>
