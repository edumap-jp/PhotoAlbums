/**
 * PhotoAlbums Javascript
 *
 * @author kteraguchi@commonsnet.org (Kohei Teraguchi)
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

// Add dependency module
NetCommonsApp.requires.push('ui.bootstrap.modal');


/**
 * PhotoAlbumsPhoto Controller
 *
 * @param {string} Controller name
 * @param {'$modal', '$http'} Controller constructor function.
 */
NetCommonsApp.controller('PhotoAlbumsPhotoController', ['$uibModal', '$http',
  function($modal, $http) {
    this.openAdd = function(url) {
      $http.defaults.headers.common['Accept'] = 'text/html';
      $modal.open({
        templateUrl: url,
        controller: 'PhotoAlbumsModalController'
      });
    };

    this.slide = function(url) {
      $http.defaults.headers.common['Accept'] = 'text/html';
      $modal.open({
        templateUrl: url,
        controller: 'PhotoAlbumsModalController',
        windowClass: 'photo-albums-slide-modal'
      });
    };
  }
]);


/**
 * PhotoAlbums modal Controller
 *
 * @param {string} Controller name
 * @param {$scope, $modalInstance} Controller constructor function.
 */
NetCommonsApp.controller('PhotoAlbumsModalController', ['$scope', '$uibModalInstance',
  function($scope, $modalInstance) {
    $scope.cancel = function() {
      $modalInstance.dismiss('cancel');
    };
  }
]);


/**
 * PhotoAlbums image preview directive
 *
 * @param {string} Directive name
 */
NetCommonsApp.directive('ncPhotoAlbumsPreview', [
  function() {
    return {
      restrict: 'A',
      link: function(scope, element, attrs) {
        element.on('change', function(event) {
          scope.files = event.target.files;
          scope.$apply(attrs.ncPhotoAlbumsPreview);
        });
      }
    };
  }
]);


/**
 * PhotoAlbums image preview Controller
 *
 * @param {string} Controller name
 * @param {$scope} Controller constructor function.
 */
NetCommonsApp.controller('PhotoAlbumsPreviewController', ['$scope',
  function($scope) {
    $scope.files = [];
    $scope.fileReaderResults = {};
    $scope.fileReaderResultsCount = 0;
    $scope.selectedJacket = {};

    $scope.init = function(maxFileUploads) {
      $scope.maxFileUploads = maxFileUploads;
    };

    $scope.preview = function() {
      $scope.fileReaderResults = {};
      $scope.selectedJacket = {};

      var fileCount = 0;
      angular.forEach($scope.files, function(file, index) {
        fileCount++;
        if (fileCount > $scope.maxFileUploads) {
          return;
        }
        if (!file.type.match('image/*')) {
          return;
        }

        var fileReader = new FileReader();
        fileReader.readAsDataURL(file);

        fileReader.onload = function() {
          $scope.$apply(function() {
            $scope.fileReaderResults[index] = fileReader.result;

            if (Object.keys($scope.fileReaderResults).length == 1) {
              $scope.selectedJacket = {
                fileReaderResult: $scope.fileReaderResults[index],
                index: index
              };
            }

            $scope.fileReaderResultsCount = Object.keys($scope.fileReaderResults).length;
          });
        };
      });
    };

    $scope.selectJacket = function(index) {
      $scope.selectedJacket = {
        fileReaderResult: $scope.fileReaderResults[index],
        index: index
      };
    };

    $scope.selectJacketUrl = function(url, photo_id) {
      $scope.selectedJacket = {
        url: url,
        photo_id: photo_id
      };
    };
  }
]);
