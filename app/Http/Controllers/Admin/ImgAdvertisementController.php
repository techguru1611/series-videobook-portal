<?php

namespace App\Http\Controllers\Admin;

use App\Models\ImageAd;
use App\Services\ImageUpload;
use App\Services\UrlService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImgAdvertisementController extends Controller
{
    public function __construct()
    {
        $this->imgAdOriginalImagePath = Config::get('constant.IMG_AD_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->imgAdThumbImagePath = Config::get('constant.IMG_AD_THUMB_PHOTO_UPLOAD_PATH');
        $this->imgAdThumbImageHeight = Config::get('constant.IMG_AD_THUMB_PHOTO_HEIGHT');
        $this->imgAdThumbImageWidth = Config::get('constant.IMG_AD_THUMB_PHOTO_WIDTH');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $imgAd = ImageAd::where('status', Config::get('constant.ACTIVE_FLAG'))->orderBy('img_order')->get();
        return view('admin.img-advertisement.list', ['imgAd' => $imgAd]);
    }

    /**
     * [listAjax List VideoCategory]
     * @param  [type]       [description]
     * @return [json]       [list of VideoCategory]
     */
    public function listAjax(Request $request)
    {
        $records = array();
        if ($request->customActionType == 'groupAction') {

            $action = $request->customActionName;
            $idArray = $request->id;

            if ($action == 'delete') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    $this->deleteImageAd($_idArray);
                }
                $records["message"] = trans('admin-message.IMG_AD_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    ImageAd::updateImageAdStatus($_idArray);
                }
                $records["message"] = trans('admin-message.IMG_AD_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'title',
            2 => 'img_order',
            3 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoCategory table
        $iTotalRecords = ImageAd::getImgAdCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = ImageAd::where('status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchImgAdTitle($val)
                    ->SearchImgAdStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchImgAdTitle($val)
                    ->SearchImgAdStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'id',
            'title',
            'img_order',
            'image',
            'status'
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('img-ad.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-video-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-video-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $image = !empty($_records->image) ? Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->imgAdThumbImagePath . $_records->image)  : asset('images/default_user_profile.png');
                $records["data"][$key]['image'] = '<img src ='. $image .' height=50px width=50px>';
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Video Category' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-video-category' title='Delete Video Category' ><span class='menu-icon icon-trash'></span></a>";
            }
        }
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return response()->json($records);
    }

    /**
     * Create controller
     */
    public function create()
    {
        return $this->_update();
    }

    /**
     * Update controller
     * @param $id = VideoCategory id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $imageAd = ImageAd::find($id);
        if ($imageAd === null) {
            return redirect()->to("/admin/img-advertisement")->with('error', trans('admin-message.IMG_AD_NOT_EXIST'));
        }
        return $this->_update($imageAd);
    }

    /**
     * Create / Update controller
     * @param $user = VideoCategory object
     */
    private function _update($imageAd = null)
    {
        if ($imageAd == null) {
            $imageAd = new ImageAd();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.img-advertisement.add', compact('imageAd', 'status'));
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function set(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Rule Validation - Start
            $rule = [
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'image' => 'image',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            if ($id == 0 || empty($id)){
                $rule['image'] = 'required|image';
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return redirect()->back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End


            // Get VideoCategory
            $imgAd = ImageAd::find($id);
            $path = $imgAd['image'];

            $postData = $request->only('title','image','status');


            // Upload category Photo
            if (!empty($request->file('image')) && $request->file('image')->isValid()) {
                $params = [
                    'originalPath' => $this->imgAdOriginalImagePath,
                    'thumbPath' => $this->imgAdThumbImagePath,
                    'thumbHeight' => $this->imgAdThumbImageHeight,
                    'thumbWidth' => $this->imgAdThumbImageWidth,
                    'previousImage' => $path,
                ];
                $imgAdImage = ImageUpload::uploadWithThumbImage($request->image, $params);

                if ($imgAdImage === false) {
                    DB::rollback();

                    // Log the error
                    Log::error(strtr(trans('log-messages.IMG_AD_UPLOAD_ERROR_MESSAGE'), []));

                    return redirect()->back()->withInput($request->all())->with('error', trans('admin-message.IMG_AD_UPLOAD_ERROR_MSG'));
                } else {
                    $postData['image'] = $imgAdImage['imageName'];
                }
            }

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $imgAd->update($postData);
                DB::commit();
                return redirect()->to("admin/img-advertisement")->with('success', trans('admin-message.IMG_AD_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $imgAd = new ImageAd($postData);
                $imgAd->save();
                DB::commit();
                return redirect()->to("admin/img-advertisement")->with('success', trans('admin-message.IMG_AD_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.IMG_AD_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return redirect()->to("admin/img-advertisement")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteImageAd($id)
    {
        try {
            $imgAd = ImageAd::findOrFail($id);

            // If user not found
            if ($imgAd === null) {
                Log::error(strtr(trans('log-messages.IMG_AD_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.IMG_AD_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $imgAd->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.IMG_AD_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }

    public function saveOrder(Request $request){
        try{
            // Rule Validation - End
            $validator = Validator::make($request->all(),[
                'orderdId' => 'required'
            ]);

            if ($validator->fails()){
                echo $validator->errors()->first();
            }

            $contents = $request->input('orderdId');
            $i = 1;
            foreach ($contents as $id){
                $imgAd = ImageAd::find($id);
                $imgAd->img_order = $i;
                $imgAd->save();
                $i++;
            }

            echo "save";


        } catch (\Exception $e){
            // error Log
            Log::error($e->getMessage());

            return redirect()->to("admin/img-advertisement")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));

        }
    }

}
