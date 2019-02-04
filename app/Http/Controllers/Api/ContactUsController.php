<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Validator;
use Log;
use DB;


class ContactUsController extends Controller
{

	/**
     * register a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitContactRequest(Request $request)
    {
        // Start DB Transaction
        DB::beginTransaction();
        try {
            // Rule Validation - Start
            $rule = [
                'email' => ['required', 'email', 'max:100'],
                'message' => 'required|max:200',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule Validation - Ends

          
            $postData = $request->only('email');

            // Add user
            $contactUs = new ContactUs(array_filter($postData));
            $contactUs['description'] = $request->message;
            $contactUs->save();

            // Commit to DB
            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' =>trans('api-message.CONTACT_ADDED_SUCCESSFULLY'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log error message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }    
}
