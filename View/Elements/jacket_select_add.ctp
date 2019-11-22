<?php
/**
 * Jacket select template for add album
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

/**
 * @var int $maxFileUploads 一度にアップロードできるファイル数
 */
?>

<div ng-controller="PhotoAlbumsPreviewController" ng-init="init(<?php echo $maxFileUploads ?>)">
	<?php
		App::uses('PhotoAlbumPhoto', 'PhotoAlbums.Model');
		$this->Form->unlockField('PhotoAlbumPhoto..' . PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME);
		$this->Form->unlockField('PhotoAlbumPhoto.' . PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME);
		$this->Form->unlockField('PhotoAlbum.selectedJacketIndex');
		echo $this->NetCommonsForm->uploadFile(
			'PhotoAlbumPhoto..' . PhotoAlbumPhoto::ATTACHMENT_FIELD_NAME,
			array(
				'label' => __d('photo_albums', 'Upload photos'),
				'help' => __d('photo_albums', 'Select photo file. You can select multiple.<br>Selected photo is disapproved and the album is published then the jacket is published.'),
				'required' => true,
				'multiple',
				'nc-photo-albums-preview' => 'preview()',
				'onChange' => 'photoAlbumValidateUploadFileCount(this)',
				'data-max-file-uploads' => $maxFileUploads,
			)
		);
	?>
	<div class="has-error" id="PhotoAlbumPhotoMaxFileUploadsError" style="display:none">
		<div class="help-block"><?php echo __d('photo_albums', 'You cannot upload more than %d photos.', $maxFileUploads)?></div>
	</div>

	<?php
		echo $this->NetCommonsForm->hidden(
			'PhotoAlbum.selectedJacketIndex',
			array('ng-value' => 'selectedJacket.index')
		);
	?>

	<div class="row">
		<div class="col-sm-3 photo-albums-selected-jacket">
			<div class="thumbnail photo-albums-thumbnail" ng-show="selectedJacket.index >= 0">
				<img ng-src="{{selectedJacket.fileReaderResult}}">
			</div>
			<div class="thumbnail photo-albums-thumbnail-not-selected" ng-show="selectedJacket.index == undefind">
			</div>
			<div class="text-center small">
				<?php echo __d('photo_albums', 'Jacket preview'); ?>
			</div>
		</div>

		<div class="col-sm-9">
			<div class="photo-albums-preview-list">
				<div class="photo-albums-thumbnail-not-selected" ng-show="selectedJacket.index == undfind">
					<?php echo __d('photo_albums', 'Select photo.'); ?>
				</div>
				<div class="clearfix" ng-show="selectedJacket.index != undfind">
					<div class="pull-left">
						<?php echo __d('photo_albums', 'Please select a photo to the cover from the following photos.'); ?>
					</div>
					<div class="pull-right">
						<?php echo __d('photo_albums', '%s photos', '{{fileReaderResultsCount}}'); ?>
					</div>
				</div>

				<div class="photo-albums-preview-photo" ng-repeat="(index, fileReaderResult) in fileReaderResults track by $index">
					<a class="thumbnail" href="#" ng-click="selectJacket(index)">
						<img ng-src="{{fileReaderResult}}">
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
