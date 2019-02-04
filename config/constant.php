<?php

return [

    'SUPER_ADMIN_SLUG' => 'superadmin',
    'NORMAL_USER_SLUG' => 'appuser',

    'ANDROID' => 'android',
    'IOS' => 'ios',

    // Video Types
    'SERIES_VIDEO' => 'regular',
    'INTRO_VIDEO' => 'intro',

    'AWS_BUCKET_LINK' => env('AWS_BUCKET_LINK'),
    'REMOTELY' => env('REMOTELY'),

    // Status
    'ACTIVE_FLAG' => 'active',
    'INACTIVE_FLAG' => 'inactive',
    'DELETED_FLAG' => 'deleted',

    // Video Approval Status
    'APPROVED_VIDEO_FLAG' => 1,
    'NOT_APPROVED_VIDEO_FLAG' => 0,

    'ACTIVATION_TOKEN_LENGTH' => 36,

    // Gender
    'MALE' => 'male',
    'FEMALE' => 'female',
    'OTHER' => 'other',

    // Video Transcoding status
    'TRANSCODING_PENDING_VIDEO_STATUS' => 'pending',
    'TRANSCODING_IN_QUEUE_VIDEO_STATUS' => 'in-queue',
    'TRANSCODING_DONE_VIDEO_STATUS' => 'transacoded',
    'TRANSCODING_FAILED_VIDEO_STATUS' => 'failed',

    'AUTHOR_PROFIT_IN_PERCENTAGE' => '90.00',

    'STATUS' => [
        [
            'value' => 'active',
            'name' => 'Active',
        ],
        [
            'value' => 'inactive',
            'name' => 'Inactive',
        ],
    ],

    // User Module
    'USER_ORIGINAL_PHOTO_UPLOAD_PATH' => 'uploads/users/original/',
    'USER_THUMB_PHOTO_UPLOAD_PATH' => 'uploads/users/thumb/',
    'USER_THUMB_PHOTO_HEIGHT' => 500,
    'USER_THUMB_PHOTO_WIDTH' => 500,

    // Video Book Module
    'MAX_VIDEOS_PER_BOOK' => 7,

    // Video Extension
    'EXTENSION' => ['mp4', 'm4a', 'm4b', 'm4p', 'm4r', 'm4v', 'm1v', 'mpg', 'mpeg', 'mkv', 'ogg', 'qt', 'webm', 'flv', 'avi', 'wmv', 'mov', 'm3u8', 'ts'],


    // Video Series Module
    'SERIES_VIDEO_TEMP_UPLOAD_PATH' => 'uploads/series/temp/',
    'SERIES_VIDEO_UPLOAD_PATH' => 'uploads/series/videos/',
    'SERIES_VIDEO_THUMB_UPLOAD_PATH' => 'uploads/series/thumb/',

    'VIDEO_SERIES_ORIGINAL_PHOTO_UPLOAD_PATH' => 'uploads/videoseries/original/',
    'VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH' => 'uploads/videoseries/thumb/',
    'VIDEO_SERIES_THUMB_PHOTO_HEIGHT' => 500,
    'VIDEO_SERIES_THUMB_PHOTO_WIDTH' => 500,

    // Video Position
    'POSITION' => [
        [
            'value' => 'top',
            'name' => 'Top',
        ],
        [
            'value' => 'bottom',
            'name' => 'Bottom',
        ],
        [
            'value' => 'start_of_video',
            'name' => 'Start of video',
        ],
    ],

    // Get Api side pagination Count
    'CATEGORY_LIST_PER_PAGE' => 10,
    'SERIES_LIST_PER_PAGE' => 10,
    'SERIES_HISTORY_PER_PAGE' => 10,
    'VIDEO_HISTORY_PER_PAGE' =>10,
    'COMMENT_LIST_PER_PAGE' =>10,
    'AUTHOR_PER_PAGE'=>10,

    'VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH' => 'uploads/video/original/',
    'FILESYSTEM_DRIVER' => env('FILESYSTEM_DRIVER', 'public'),
    'AWS_URL' => 'https://s3.amazonaws.com/inexturemaincode/',

    // video upload test storing path
    'VIDEO_UPLOAD_TEST_ORIGINAL_UPLOAD_PATH' => 'uploads/videoTest/original/',

    'DEFAULT_PER_PAGE' => 10,

    //Video Category Module
    'VIDEO_CATEGORY_ORIGINAL_PHOTO_UPLOAD_PATH' => 'uploads/videocategory/original/',
    'VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH' => 'uploads/videocategory/thumb/',
    'VIDEO_CATEGORY_THUMB_PHOTO_HEIGHT' => 500,
    'VIDEO_CATEGORY_THUMB_PHOTO_WIDTH' => 500,

    'IMG_AD_ORIGINAL_PHOTO_UPLOAD_PATH' => 'uploads/imgAd/original/',
    'IMG_AD_THUMB_PHOTO_UPLOAD_PATH' => 'uploads/imgAd/thumb/',
    'IMG_AD_THUMB_PHOTO_HEIGHT' => 500,
    'IMG_AD_THUMB_PHOTO_WIDTH' => 500,

    'TOKEN_EXPIRE_HOURS' => 24,
    'FB_LOGIN' => 'facebook',
    'GOOGLE_LOGIN' => 'google',
    'DEFAULT_USER_IMAGE' => 'images/default_user_profile.png',

    'LIKE' => 'like',
    'UNLIKE' => 'unlike',
    'REVERT' => 'revert',

     // Gender
    'ANDROID' => 'android',
    'IPHONE' => 'iphone',
    'WEB' => 'web',

    // series of the day
    'SERIES_OF_THE_DAY' => 'series_of_the_day',
    'SOMETHING_NEW_EVERYDAY'=>'something_new_everyday',
    'TRENDING_LIMIT' => 9,
    'NEW_EVERYDAY_LIMIT' => 12,
    'SERIES_LIMIT' => 20,

    //about us
    'ABOUT_US' => 'about_us',

    // Comment type
    'PARENT' => 'parent',
    'REPLIED' => 'replied',
    'NONE'=>'none',

    // Reset OTP generate
    'START' => 100000,
    'END' => 999999,
    'MINUTES'=> 15,
    'RESET_PASSWORD_OTP_EXPIRE_IN_MINUTES' => 60,
    'OTP_VERIFIED_FALSE' => 0,
    'OTP_VERIFIED_TRUE' => 1,

    // Activation otp 
    'ACTIVATION_OTP_VERIFIED_FALSE' => 0,
    'ACTIVATION_OTP_VERIFIED_TRUE' => 1,
    'ACTIVATION_OTP_EXPIRE_IN_HOURS' => 48,

    // Is Completed
    'IS_COMPLETED_TRUE' => 1,
    'IS_COMPLETED_FALSE' => 0,

    // App Version
    'IOS' => 'ios',
    'ANDROID' => 'android',
    'FORCE_UPDATE_TRUE' => true,
    'FORCE_UPDATE_FALSE' => false,
    'UPDATE_AVAILABLE_TRUE' => true,
    'UPDATE_AVAILABLE_FALSE' => false,
];
