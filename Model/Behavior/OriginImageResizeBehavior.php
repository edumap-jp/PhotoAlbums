<?php
/**
 * アップロードされた元画像をリサイズするビヘイビア
 */

App::uses('ModelBehavior', 'Model');

/**
 * Class OriginImageResizeBehavior
 */
final class OriginImageResizeBehavior extends ModelBehavior {

/**
 * @var UploadFile
 */
	private $__uploadFileModel;

/**
 * setup
 *
 * @param Model $model model
 * @param array $config behavior setting
 * @return void
 */
	public function setup(Model $model, $config = array()) {
		$this->__guradBeforeLoadingAttachmentBehavior($model);

		$this->settings[$model->alias] = $config;
		parent::setup($model, $config);
	}

/**
 * afterSave
 *
 * @param Model $model model
 * @param bool $created created
 * @param array $options options
 * @return bool
 */
	public function afterSave(Model $model, $created, $options = array()) {
		$setting = $this->settings[$model->alias];

		$this->__uploadFileModel = ClassRegistry::init('Files.UploadFile');

		foreach ($setting as $fieldName => $fieldSetting) {
			$pluginKey = Inflector::underscore($model->plugin);
			$uploadFile = $this->__uploadFileModel->getFile($pluginKey, $model->id, $fieldName);
			$this->overwriteOriginFile($uploadFile, $fieldSetting['resizeImagePrefix'] . '_');
		}

		return parent::afterSave($model, $created, $options);
	}

/**
 * 元ファイルをリサイズしたファイルで上書き
 *
 * @param array $uploadFile UploadFileデータ
 * @param string $overwriteFilePrefix リサイズされた画像のprefix このprefixのついたファイルを元画像あつかいにする。
 * @return array|false UploadFile::save()の結果
 * @throws InternalErrorException
 */
	public function overwriteOriginFile(array $uploadFile, $overwriteFilePrefix) {
		// 元ファイル削除
		$originFilePath = $this->__uploadFileModel->getRealFilePath($uploadFile);

		//  origin_resizeからprefix削除
		$originResizePath = substr($originFilePath, 0, -1 * strlen($uploadFile['UploadFile']['real_file_name'])) .
			$overwriteFilePrefix . $uploadFile['UploadFile']['real_file_name'];

		if (! file_exists($originResizePath)) {
			//リネームするファイルがなければ、そのままuploadFileを返す。
			return $uploadFile;
		}

		unlink($originFilePath);
		rename($originResizePath, $originFilePath);

		//  uploadFileのsize更新
		$stat = stat($originFilePath);
		$uploadFile['UploadFile']['size'] = $stat['size'];
		try {
			$uploadFile = $this->__uploadFileModel->save(
				$uploadFile,
				['callbacks' => false, 'validate' => false]
			);
		} catch (Exception $e) {
			throw new InternalErrorException('Failed Update UploadFile.size');
		}
		return $uploadFile;
	}

/**
 * 事前にAttachmentBehaviorが読みこまれているか
 *
 * @param Model $model model
 * @return void
 * @throws CakeException
 */
	private function __guradBeforeLoadingAttachmentBehavior(Model $model) {
		if (!$model->Behaviors->loaded('Files.Attachment')) {
			$error = '"Files.AttachmentBehavior" not loaded in ' . $model->alias . '. ';
			$error .= 'Load "Files.AttachmentBehavior" before loading "OriginImageResizeBehavior"';
			throw new CakeException($error);
		}
	}

}