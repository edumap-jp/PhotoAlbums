function photoAlbumValidateUploadFileCount(fileForm) {
  var maxFileUploads = $(fileForm).data('maxFileUploads');
  if (fileForm.files.length > maxFileUploads) {
    $('#PhotoAlbumPhotoMaxFileUploadsError').show();
    $('button[name^=save_]').prop('disabled', true);
  } else {
    $('#PhotoAlbumPhotoMaxFileUploadsError').hide();
    $('button[name^=save_]').prop('disabled', false);
  }
}
