<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () {
    if (auth()->id() > 0) {
        return view('admin.dashboard');
    }
    return view('auth.login');
});

Auth::routes();

// activate Account
Route::get('activate/{token}', 'Admin\UserController@activate');
Route::post('password/reset', 'Admin\PasswordController@set');

Route::group(['middleware' => 'check.role:superadmin'], function () {
    Route::get('/dashboard', 'HomeController@index')->name('dashboard');

    // User Module
    Route::get('/admin/users', 'Admin\UserController@index');
    Route::post('/admin/users/list-ajax', 'Admin\UserController@listAjax');
    Route::any('/admin/users/new', 'Admin\UserController@create');
    Route::any('/admin/users/user-{encodedId}', array('as' => 'user.edit', 'uses' => 'Admin\UserController@update'));
    Route::post('/admin/users/set', 'Admin\UserController@set');

    // User Level Module
    Route::get('/admin/user-levels', 'Admin\UserLevelController@index');
    Route::post('/admin/user-levels/list-ajax', 'Admin\UserLevelController@listAjax');
    Route::any('/admin/user-levels/new', 'Admin\UserLevelController@create');
    Route::any('/admin/user-levels/user-level-{encodedId}', array('as' => 'user-level.edit', 'uses' => 'Admin\UserLevelController@update'));
    Route::post('/admin/user-levels/set', 'Admin\UserLevelController@set');

    // Video Category Module
    Route::get('/admin/video-category', 'Admin\VideoCategoryController@index');
    Route::post('/admin/video-category/list-ajax', 'Admin\VideoCategoryController@listAjax');
    Route::any('/admin/video-category/new', 'Admin\VideoCategoryController@create');
    Route::any('/admin/video-category/video-category-{encodedId}', array('as' => 'video-category.edit', 'uses' => 'Admin\VideoCategoryController@update'));
    Route::post('/admin/video-category/set', 'Admin\VideoCategoryController@set');

    // Video Book Module
    Route::get('/admin/video-books', 'Admin\VideoBookController@index');
    Route::post('/admin/video-books/list-ajax', 'Admin\VideoBookController@listAjax');
    Route::any('/admin/video-books/new', 'Admin\VideoBookController@create');
    Route::any('/admin/video-books/video-books-{encodedId}', array('as' => 'video-books.edit', 'uses' => 'Admin\VideoBookController@update'));
    Route::post('/admin/video-books/set', 'Admin\VideoBookController@set');
    Route::post('/admin/video-books/get-video-sub-category-ajax', 'Admin\VideoBookController@getVideoSubCategoryAjax');
    Route::post('/admin/video-books/upload-video-ajax', 'Admin\VideoBookController@uploadVideoAjax');
    Route::post('/admin/video-books/set-video-ajax', 'Admin\VideoBookController@setVideoAjax');
    Route::post('/admin/video-books/get-video-ajax', 'Admin\VideoBookController@getVideoAjax');
    Route::post('/admin/video-books/save-video-ajax', 'Admin\VideoBookController@saveVideoAjax');
    Route::post('/admin/video-books/remove-video-ajax', 'Admin\VideoBookController@removeVideoAjax');
    Route::post('/admin/video-books/approve-video-ajax', 'Admin\VideoBookController@approveVideoAjax');
    Route::post('/admin/video-books/reject-video-ajax', 'Admin\VideoBookController@rejectVideoAjax');
    Route::get('/admin/video-books/video/{id}','Admin\VideoBookController@getVideoSignedUrl')->name('get-video_signed-url');


    Route::get('admin/pending-video','Admin\VideoBookController@pendingVideos');
    Route::post('admin/pending-video/list-ajax','Admin\VideoBookController@pendingVideosList');

    // Video Advertisement Module
    Route::get('/admin/video-advertisement', 'Admin\VideoAdvertisementController@index');
    Route::post('/admin/video-advertisement/list-ajax', 'Admin\VideoAdvertisementController@listAjax');
    Route::any('/admin/video-advertisement/new', 'Admin\VideoAdvertisementController@create');
    Route::any('/admin/video-advertisement/video-advertisement-{encodedId}', array('as' => 'video-advertisement.edit', 'uses' => 'Admin\VideoAdvertisementController@update'));
    Route::post('/admin/video-advertisement/set', 'Admin\VideoAdvertisementController@set');

    // img Advertisement Module
    Route::get('admin/img-advertisement','Admin\ImgAdvertisementController@index');
    Route::post('/admin/img-advertisement/list-ajax', 'Admin\ImgAdvertisementController@listAjax');
    Route::any('/admin/img-advertisement/new', 'Admin\ImgAdvertisementController@create');
    Route::any('/admin/img-advertisement/img-ad-{encodedId}', array('as' => 'img-ad.edit', 'uses' => 'Admin\ImgAdvertisementController@update'));
    Route::post('/admin/img-advertisement/set', 'Admin\ImgAdvertisementController@set');
    Route::post('/admin/img-advertisement/save-order','Admin\ImgAdvertisementController@saveOrder')->name('save-image-ad-order');

    // Cms Module
    Route::get('/admin/cms', 'Admin\CmsController@index');
    Route::post('/admin/cms/list-ajax', 'Admin\CmsController@listAjax');
    Route::any('/admin/cms/new', 'Admin\CmsController@create');
    Route::any('/admin/cms/cms-{encodedId}', array('as' => 'cms.edit', 'uses' => 'Admin\CmsController@update'));
    Route::post('/admin/cms/set', 'Admin\CmsController@set');

    // Settings module
    Route::get('/admin/settings', 'Admin\SettingsController@index');
    Route::post('/admin/settings/list-ajax', 'Admin\SettingsController@listAjax');
    Route::any('/admin/settings/settings-{settingId}', array('as' => 'setting.edit', 'uses' => 'Admin\SettingsController@update'));
    Route::post('/admin/settings/set', 'Admin\SettingsController@set');

    // App version module
    Route::get('/admin/appversion', 'Admin\AppVersionController@index');
    Route::post('/admin/appversion/set', 'Admin\AppVersionController@set');
    Route::get('/admin/appversion/delete/{id}', 'Admin\AppVersionController@destroy');

    Route::get('admin/contact-us','Admin\ContactUsController@getContactUs');
    Route::post('admin/contact-us/list-ajax','Admin\ContactUsController@listAjax');

    Route::get('admin/purchase-history','Admin\PurchaseHistoryController@getPurchasedHistory');
    Route::post('admin/purchase-history/list-ajax','Admin\PurchaseHistoryController@listAjax');
});
