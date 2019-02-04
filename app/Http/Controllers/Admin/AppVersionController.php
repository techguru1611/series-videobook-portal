<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Validator;
use Redirect;
use Config;
use DB;
use Log;

class AppVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $iosData = AppVersion::where('device_type',Config::get('constant.IOS'))->get();
        $androidData = AppVersion::where('device_type',Config::get('constant.ANDROID'))->get();
        return view('admin.app-version.add', compact('iosData','androidData'));
    }

    /**
     * Save data
    */
    public function set(Request $request)
    {
        // Start DB transaction
    	DB::beginTransaction();
        try {
            // Rule Validation - Start
            $rule = [
            	'app_version.*' =>'required',
                'force_update.*' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            // Get old ids and delete
            $ids = AppVersion::where('device_type',$request->type)->pluck('id')->toArray();
            AppVersion::destroy($ids);

        	// Get data from request
            $postData = $request->only('app_version', 'force_update' , 'device_type');
            if($request->get('type') == Config::get('constant.ANDROID'))
            {
                $device = Config::get('constant.ANDROID');
            }
            else{
    
                $device = Config::get('constant.IOS');
            }

            for ($i = 0; $i < count($postData['app_version']); $i++) {
                    AppVersion::insert([
                        'app_version' => $postData['app_version'][$i],
                        'force_update' => $postData['force_update'][$i],
                        'device_type' => $device,
                    ]);
                }

           	// Commit to DB
            DB::commit();

            // Return success save message
            return Redirect::to("admin/appversion")->with('success', trans('admin-message.APP_VERSION_SAVED_SUCCESSFULLY_MESSAGE'));

        } catch (\Exception $e) {
            DB::rollback();
            // Log Message 
            Log::error(strtr(trans('log-messages.APP_VERSION_ADD_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/appversion")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Start transaction
        DB::beginTransaction();
        try{
            $appversion = AppVersion::findOrFail($id);
            $appversion->delete();

            // Commit to DB
            DB::commit();

            // Return Success message
            return Redirect::to("admin/appversion")->with('success', trans('admin-message.DELETE_SUCCESS_MESSAGE'));

        } catch(\Exception $e){
            DB::rollback();
            Log::error(strtr(trans('log-messages.APP_VERSION_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/appversion")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

}
