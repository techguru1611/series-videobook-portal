<?php

namespace App\Http\Controllers\Admin;

use App\models\ContactUs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContactUsController extends Controller
{
    public function getContactUs(Request $request){
            return view('admin.contact-us.list');
    }

    public function listAjax(Request $request)
    {
        $records = array();
        $columns = array(
            0 => 'email',
            1 => 'description',
            2 => 'created_at'
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the Cms table
        $iTotalRecords = ContactUs::getContactUsCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = ContactUs::whereNotNull('email');

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchContactEmail($val)
                    ->SearchContactMessage($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchContactEmail($val)
                    ->SearchContactMessage($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get();

        /*if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('cms.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-cms" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-cms" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Cms' ><span class='menu-icon icon-pencil'></span></a>&emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-cms' title='Delete Cms' ><span class='menu-icon icon-trash'></span></a>";
            }
        }*/
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return response()->json($records);
    }
}
