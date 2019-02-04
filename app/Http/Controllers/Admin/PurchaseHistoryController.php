<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserPurchasedEVideoBooks;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseHistoryController extends Controller
{
    public function getPurchasedHistory(Request $request){
        return view('admin.purchase-history.list');
    }

    public function listAjax(Request $request)
    {
        $records = array();
        $columns = array(
            0 => 'e_video_book_title',
            1 => 'author_name',
            2 => 'user_name',
            3 => 'price',
            4 => 'author_price',
            5 => 'purchase_transaction_id',
            6 => 'payout_transaction_id',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the Cms table
        $iTotalRecords = UserPurchasedEVideoBooks::all()->count();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = UserPurchasedEVideoBooks::leftJoin('e_video_book','e_video_book.id', '=', 'user_purchased_e_video_books.e_video_book_id')
            ->leftJoin('users as user', 'user.id', '=', 'user_purchased_e_video_books.user_id')
            ->leftJoin('users as author', 'author.id', '=', 'e_video_book.user_id');

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->where('user.full_name','LIKE',"%$val%")
                    ->orWhere('author.full_name','LIKE',"%$val%")
                    ->orWhere('e_video_book.title','LIKE', "%$val%")
                    ->orWhere('user_purchased_e_video_books.price',"%$val%")
                    ->orWhere('user_purchased_e_video_books.author_price',"%$val%")
                    ->orWhere('user_purchased_e_video_books.purchase_transaction_id','LIKE',"%$val%")
                    ->orWhere('user_purchased_e_video_books.payout_transaction_id', 'LIKE',"%$val%");
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->where('user.full_name','LIKE',"%$val%")
                    ->orWhere('author.full_name','LIKE',"%$val%")
                    ->orWhere('e_video_book.title','LIKE', "%$val%")
                    ->orWhere('user_purchased_e_video_books.price',"%$val%")
                    ->orWhere('user_purchased_e_video_books.author_price',"%$val%")
                    ->orWhere('user_purchased_e_video_books.purchase_transaction_id','LIKE',"%$val%")
                    ->orWhere('user_purchased_e_video_books.payout_transaction_id', 'LIKE',"%$val%");
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'user.full_name as user_name','author.full_name as author_name',
            'e_video_book.title as e_video_book_title',
            'user_purchased_e_video_books.price as price',
            'user_purchased_e_video_books.author_price as author_price',
            'user_purchased_e_video_books.purchase_transaction_id as purchase_transaction_id',
            'user_purchased_e_video_books.payout_transaction_id as payout_transaction_id',
        ]);

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
