<?php
return [
    // Common error
    'DEFAULT_SUCESS_MESSAGE' => 'Success',
    'DEFAULT_ERROR_MESSAGE' => 'Whoops! Something went wrong. Please try again.',
    'NOT_AUTHORIZED' => 'You are not authorized to do this action.',
    'DEFAULT_SUCCESS_MESSAGE' => 'Success',

    // JWT Messages
    'FAILED_TO_VALIDATE_TOKEN' => 'Failed to validate token.',
    'TOKEN_EXPIRED' => 'Token Expired.',
    'INVALID_OR_BLACKLISTED_TOKEN' => 'Invalid or blacklisted token.',
    'UNAUTHORIZED_ACTION' => 'Unauthorized action found.',

    // Social authentication error messages
    'SOCIAL_AUTHENTICATION_ERROR_MESSAGE' => 'Whoops! Something went wrong while validating detail. Please try again.',
    'INVALID_AUTH_TOKEN' => 'Invalid authentication token found!',
    'USER_ACCOUNT_DELETED_BY_ADMIN' => 'Your account has been deleted. Please contact system administrator.',
    'USER_ACCOUNT_INACTIVATED_BY_ADMIN' => 'Your account has been deactivated. Please contact system administrator.',
    'USER_ACCOUNT_EMAIL_NOT_VERIFIED' => 'You don\'t have verified your email. Please verify first.',
    'USER_ACCOUNT_EMAIL_IS_NOT_VERIFIED' => 'Your email verifiaction is pending. Please login with your existing email and verifiy',

    // Exception Message
    'RESOURCES_NOT_FOUND' => 'Resouces Not found!',

    // Video Category
    'VIDEO_CATEGORY_NOT_FOUND' => 'Video category not found!',

    // Video Book
    'VIDEO_BOOK_NOT_FOUND' => 'Video series data not found!',
    'SELECT_IMAGE' => 'select image',
    'FIRST_ACTIVE_ALREADY_CREATED_SERIES'=> 'Firstly, You have to active already created series',

    'MAIL_ALREADY_EXISTS' => 'Email already exist.',
    'MAIL_SENT' => 'Activation Mail Sent on <Mail>, Please Activate Account.',
    'EXPIRE_TOKEN' => 'Your Token is Expire, Please Send it again.',
    'TOKEN_NOT_FOUND' => 'Token Not Found, Please Send it again.',
    'EMAIL_ACTVATE' => 'You have successfully verified your email address now you can receive mail regarding to transaction.',
    'USER_NOT_FOUND' => 'There is no account for your email.',
    'INVALID_LOGIN' => 'Invalid email password combo',
    'INACTIVE_USER' => 'Inactive User',
    'LOGIN_SUCCESS' => 'Login Successfully',
    'USER_IMAGE_UPLOAD_ERROR_MSG' => 'Error while image upload .Please try again',
    'RESET_MAIL_SENT' => 'Reset Password Mail Sent on <Mail>, Please Check Your Inbox',
    'PROFILE_FETCHED_SUCCESSFULLY' => 'Profile feched successfully',
    'USER_UPDATE_SUCCESSFULLY' => 'Profile update successfully',
    'ACTIVATION_MAIL_SENT_SUCCESSFULLY' => 'Activation mail sent successfully to your email.',
    'USER_ACCOUNT_ALREADY_VERIFIED' => 'Your account is already verified.',
    'RESET_PASSWORD_LINK_SENT_SUCCESS_MESSAGE' => 'A password reset mail has been sent to your email address.',

    'PASSWORD_CHANGE_SUCESSFULLY' => 'Password changed successfully',
    'CURRENT_PASSWORD_NOT_MATCH' => 'current password does not match',
    'SERIES_LIST_FETCHED_SUCCESSFULLY' => 'Series list fetched successfully',
    'ERROR_RETRIVING_SERIES_LIST' => 'Error in retriving series list ',
    'USER_UPDATE_SUCCESSFULLY'=>'Profile update successfully',

    'NOW_TREADING_CATEGORY_LIST_FETCHED_SUCCESSFULLY' => 'Now Trending categories fetched successfully',
    'NOW_TREADING_SERIES_LIST_FETCHED_SUCCESSFULLY' => 'Now Trending series fetched successfully',

    // Subscribed module
    'SUBSCRIBED_AUTHOR_FETCHED_SUCCESSFULLY' => 'Subscribed creators fetched successfully',
    'SUBSCRIBED_AUTHOR_VIDEO_FETCHED_SUCCESSFULLY' => 'Subscribed creators videos fetched successfully',
    'SUBSCRIBED_AUTHOR_ADDED_SUCCESSFULLY'=>'Subscribed creator added sucessfully',
    'UNSUBSCRIBED_AUTHOR_SUCCESSFULLY' =>'Unsubcribed auhtor successfully',

    // Contact Us
    'CONTACT_ADDED_SUCCESSFULLY' => 'Your message submitted sucessfully',

    // user video category
    'USER_VIDEO_CATEGORY_SUCCESS_MESSAGE' => 'User Video Category added successfully.',

    // Author
    'AUTHOR_USER_NOT_FOUND' => 'This User is not Creator !',
    'AUTHOR_ALREADY_SUBSCRIBED' =>'This creator already Subscribed',
    'AUTHOR_SERIES_FETCHED_SUCCESSFULLY'=>'Creator series fetched successfully',
    'AUTHOR_DETAIL_FETCHED_SUCCESSFULLY' =>'Creator details fetched successfully',


    // Video book videos
    'VIDEO_NOT_FOUND' => 'Video not found !',
    'VIDEO_DETAILS_FETCHED_SUCCESSFULLY' => 'Video details fetched successfully',
    'USER_NOT_PURCHASED_VIDEOSERIES' => 'User has not purchased this series',
    'LIKE_SAVED_SUCCESSFULLY' => 'Like Saved sucessfully',
    'YOU_HAVE_ALREADY_LIKED_THIS_VIDEO' => 'You have already liked this video',
    'YOU_HAVE_ALREADY_UNLIKED_THIS_VIDEO' => 'You have already unlike this video',
    'UNLIKE_SAVED_SUCCESSFULLY' => 'UnLike Saved sucessfully',
    'REVERT_SUCCESSFULLY' => 'Revert successfully',
    'NOTHING_FOR_REVERT'=>'Nothing to revert',
    'SOMETHING_NEW_SERIES_LIST_FETCHED_SUCCESSFULLY'=>'Something new video series fetched successfully',

    // User Device token
    'DEVICE_TOKEN_SAVE_SUCCESSFULLY' => 'Device token saved successfully',
    'DEVICE_TOKEN_STATUS_UPDATE_SUCCESSFULLY'=>'Device token status update successfully',
    'LOGOUT_SUCCESSFULLY' =>'Logout successfully',
    'INVALID_OR_BLACKLISTED_TOKEN'=>'Invalid Device id or token',
    'INVALID_AUTHORAZATION_TOKEN'=>'Unauthorized token',
    'SERIES_OF_THE_DAY_DETAIL_FETCHED_SUCCESS'=>'Series of the day fetched sucessfully',
    'HISTORY_FETCHED_SUCCESSFULLY'=>'History fetched successfully',

    // Video Comment 
    'COMMENT_SAVED_SUCCESSFULLY' => 'Comment saved successfully',
    'INVALID_INPUT_PARAMETER'=>'Invalid input parameter',

    'COMMENT_LIST_FETCHED_SUCCESSFULLY'=>'Comment list fetched successfully',

    // Video History
    'VIDEO_HISTORY_SAVED_SUCCESSFULLY' =>'Video history saved sucessfully',
    'VIDEO_HISTORY_UPDATED_SUCCESSFULLY' => 'Video history updated sucessfully',

    // Forgot Passoword     
    'OTP_VALID_FOR_15_MINUTES' => 'Reset OTP valid for 15 minutes',
    'YOU_CANT_PERFORM_THIS_ACTION' => 'You can\'t perform this action ',
    'OTP_IS_VALID_FOR_60_MINUTES' => 'OTP valid for 60 minutes',
    'OTP_VERIFIED_SUCCESS'=>'OTP verified successfully ',
    'PLEASE_ENTER_VALID_OTP'=>'Please  enter valid OTP',
    'NO_USER_FOUND_WITH_THIS_EMAIL_AND_OTP' => 'No user found with this otp ,or may not verified otp',
    'PASSWORD_RESET_SUCCESS' => 'Password reset successfully',

    // Activation with otp
    'NO_USER_FOUND_WITH_THIS_OTP' =>'OTP does not match please enter valid OTP',
    'OTP_IS_VALID_FOR_48_HOURS' => 'Activation OTP valid for 48 hours',
    'WATCHED_TILL_IS_GREATER_THAN_TOTAL_DURATION' =>'watched till is greater than video duration',

    'ADD_VIDEO_BOOK' => 'Video Series Added Successfully',
    'UPDATE_VIDEO_BOOK' => 'Video Series Updated Successfully',

    'MAX_LIMIT_ERROR' => 'You have Exceed Your Limit to Upload Videos',
    'APPROVED_ONE_ERROR' => 'Your Last Uploaded Video is Not Approved! Please Wait till Last Video is Not Approved',

    'INVALID_FILE' => 'Invalid File Type',

    'VIDEO_ADD_SUCCESSFULLY' => 'Video Successfully Added',
    'VIDEO_UPDATE_SUCCESSFULLY' => 'Video Successfully Updated',
    'VIDEO_LIST_GET_SUCCESSFULLY' => 'Video List Get Successfully',
    'VIDEO_DETAILS_GET_SUCCESSFULLY' => 'Video Details Get Successfully',

    'VIDEO_UPLOAD_SUCCESSFULLY' => 'Video Successfully Uploaded',
    'VIDEO_UPLOAD_IS_PROGRESS' => 'Video Upload is in Progress',

    'VIDEO_NOT_APPROVED' => 'This Video is not Approved By Admin',

    'ALREADY_UPLOAD_CHUNK' => 'This Chunk is Already Upload',

    'AUTHOR_NOT_PURCHASE' => 'You can\'t purchase this Video Series Because You are the Creator of this Series.',
    'ALREADY_PURCHASED' => 'You have Already Purchase This Videos Series',
    'SERIES_DATA_GET_SUCCESSFULLY_FOR_PURCHASE' => 'Details Get Successfully for Purchase Video Series',
    'STRIPE_DEFAULT_ERROR_MESSAGE' => 'Something went wrong on processing Payment',
    'INSUFFICIENT_AMOUNT' => 'Please Enter Sufficient Amount for Purchase',
    'PAYMENT_SUCCESS' => 'Payment Success',
    'CARD_DELETE_SUCCESS' => 'Card Delete Success',

    'INVALID_AUTHOR' => 'Please Add Card or Date of Birth',
    'ACCOUNT_ADD_SUCCESS' => 'Account Details add Successfully',
    'ALREADY_ADD_PAYMENT_DETAILS' => 'Your Account Details Already Added',

    'INTRO_VIDEO_ALREADY_UPLOADED' => 'Intro Video Already Uploaded',

    'COUNT_ADD_SUCCESSFULLY' => 'Video Download Count Increase',
    'BELOW_AGE_FOR_STRIPE_ACCOUNT' => 'Must be at least 13 years of age to use Stripe'

];
