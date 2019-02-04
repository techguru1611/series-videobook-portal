<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, PATCH, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization, mimetype, Platform');
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group(['middleware' => 'jwt.auth'], function () {

    // Video Book Category API
    Route::get('video-category/{id}/series', 'Api\VideoCategoryController@getVideoCategorySeries');
    Route::get('video-category/{id}', 'Api\VideoCategoryController@getVideoCategoryDetail');
    Route::get('video-category/list', 'Api\VideoCategoryController@list');
    Route::get('video-category/trending', 'Api\VideoCategoryController@nowTrendingCategoryList');
    
    // Save user video category
    Route::post('user-video-category/save', 'Api\UserController@saveCategoryPreference');

    // Profile Route
    Route::get('user/me', 'Api\UserController@show');
    Route::post('user/me/update/photo', 'Api\UserController@update');
    Route::post('user/me/update/password', 'Api\UserController@changePassword');
    Route::post('user/me/update/profile', 'Api\UserController@updateProfileDetail');
    Route::post('updatefcmtoken','Api\UserController@saveDeviceToken');
    Route::post('logout','Api\AuthController@logout');

    // Video Book (Series) Routes
    Route::get('video-series/{id}', 'Api\VideoBookController@getVideoSeriesDetail');
    Route::get('user/me/series', 'Api\VideoBookController@getSeriesList');
    Route::get('user/me/history' ,'Api\VideoBookController@getHistory');
    Route::get('user/me/series/purchased','Api\VideoBookController@getPurchasedSeriesList');
    Route::get('video-series/list', 'Api\VideoBookController@getVideoSeriesList');
    Route::get('video-series/trending', 'Api\VideoBookController@getTrendingVideoSeriesList');
    Route::get('video-series/something-new', 'Api\VideoBookController@getSomethingNewSeriesList');
    Route::post('video-series','Api\VideoBookController@addOrUpdateVideoSeries');

    // Purchase Video Series
    Route::get('video-series/{id}/purchase', 'Api\PaymentController@purchaseVideoSeries');
    Route::post('payment','Api\PaymentController@payment');
    Route::post('deleteCard','Api\PaymentController@deleteCard');
    Route::post('user/me/save-payment-detail','Api\PaymentController@saveAccountDetails');

    // Upload Chunk Video
    Route::post('series/{id}/video','Api\VideoUploadController@addVideo');
    Route::post('video/chunk-upload','Api\VideoUploadController@uploadChunkVideo');
    Route::get('video/upload/{video_id}','Api\VideoUploadController@getVideo');


    // Video likes / unlikes
    Route::post('video-like-save' , 'Api\VideoBookVideoController@videoLikeSave');
    Route::get('video/{id}' , 'Api\VideoBookVideoController@getVideoDetails');
    Route::post('video/comment' , 'Api\VideoBookVideoController@saveVideoComment');
    Route::get('video/{videoId}/comments/{commentId}' , 'Api\VideoBookVideoController@getVideoComment');
    Route::post('video/watchhistory/save' , 'Api\VideoBookVideoController@saveVideoHistory');
    Route::post('video/{id}/increase-download', 'Api\VideoBookVideoController@increaseVideoDownloadCount');




    // Subscribed Author Routes
    Route::get('user/me/author/subscribed' , 'Api\SubscribedAuthorController@subscribedAuthorList');
    Route::get('user/me/author/subscribed/series' , 'Api\SubscribedAuthorController@subscribedAuthorVideoList');
    Route::post('user/me/author/save', 'Api\SubscribedAuthorController@subscribedAuthor');
    Route::post('user/me/author/remove', 'Api\SubscribedAuthorController@unsubscribedAuthor');


    // Author Details with video series
    Route::get('author/{id}' , 'Api\AuthorController@getAuthorDetails');
    Route::get('author/{id}/series' , 'Api\AuthorController@getAuthorSeries');

    // Upload Video
    Route::post('video-upload', 'Api\VideoUploadController@upload');

    // Home 
    Route::get('home', 'Api\HomeController@getHomeData');

    // Video Advertisement
    Route::get('video-ad','Api\HomeController@getVideoAd');
});

// Authentication Route
Route::post('register', 'Api\AuthController@register');
Route::post('auth/social', 'Api\AuthController@authenticateSocialLogin');
Route::post('auth/login', 'Api\AuthController@login');

// Route::post('forgotPassword', 'Api\AuthController@reset');

Route::post('forgotPassword', 'Api\AuthController@resetPassword');
Route::post('resetotp/verify', 'Api\AuthController@verifyResetOTP');
Route::post('password/reset', 'Api\AuthController@resetPasswordUsingOTP');

// Contact Us Route
Route::post('contactUs', 'Api\ContactUsController@submitContactRequest');

// About us
Route::get('aboutUs', 'Api\AboutUsController@aboutUs');

// Verify account
// Route::post('account/verification', 'Api\AuthController@sendEmailVerificationLink');

Route::post('account/verification', 'Api\AuthController@sendEmailVerificationOTP');
Route::post('activation/verify', 'Api\AuthController@verifyActivationOTP');


// App Version
Route::post('appversion', 'Api\AppVersionController@getAppVersionDetail');
Route::get('category/lists', 'Api\VideoCategoryController@categoryLists');

// Chunk Video Upload
/*Route::get('series/{id}/video','Api\VideoUploadController@getAllVideos');*/
//Route::get('series/{id}/video/{video_id}','Api\VideoUploadController@getVideo');

// payment webHookListen
Route::post('webHook','PaymentController@webHookListen');

