<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServerSideController extends Controller
{
    public function table_voucher(Request $request)
    {
        $voucher = Voucher::search($request->search['value'])
            ->with('product')
            ->orderBy('id', 'desc');
        return DataTables::eloquent($voucher)
            ->editColumn('id', function ($row) use (&$start) {
                return ++$start;
            })
            ->addColumn('product', function ($voucher) {
                return $voucher->product->name ?? '<span class="text-success">All Providers</span>';
            })
            ->editColumn('amount', function ($voucher) {
                return '<div><div>Minimal: <span class="text-dark">' . format_price($voucher->minimal_amount) . '</span></div><div>Discount: <span class="text-dark">' . format_price($voucher->amount) . ($voucher->type === 'percent' ? '%' : '') . '</span></div><div>Max Fee: <span class="text-dark">' . format_price($voucher->maximal_fee) . '</span></div></div>';
            })

            ->addColumn('info_limit', function ($voucher) {
                return '<span class="text-success">' . $voucher->limit . '</span> / <span class="text-danger">' . $voucher->use_limit . '</span>';
            })

            ->addColumn('aksi', function ($voucher) {
                return '<a href="javascript:void(0)" class="btn-edit-voucher me-2 text-secondary" onclick="editVoucher(' . $voucher->id . ')"  data-bs-toggle="modal" data-bs-target="#modal" title="Edit"><i class="las la-edit fs-5"></i></a><a href="javascript:void(0)" class="btn-delete-voucher text-danger" onclick="deleteVoucher(' . $voucher->id . ')"  title="Delete"><i class="las la-trash-alt fs-5"></i> </a>';
            })


            ->rawColumns(['product', 'amount', 'info_limit', 'aksi'])
            ->make(true);
    }

    public function table_provider(Request $request)
    {
        $provider = Provider::search($request->search['value'])
            ->orderBy('id', 'desc');

        return DataTables::eloquent($provider)
            ->editColumn('id', function ($row) use (&$start) {
                return ++$start;
            })
            ->editColumn('api_key', function ($provider) {
                return '<div class="d-flex align-items-center gap-2"><code class="text-truncate d-inline-block" style="max-width: 220px">' . $provider->api_key . '</code></div>';
            })
            ->editColumn('type_api', function ($provider) {
                return '<span class="badge bg-light text-dark">Tipe ' . $provider->type_api . '</span>';
            })
            ->editColumn('status', function ($provider) {
                return '<span class="btn btn-sm '
                    . ($provider->status === 'active' ? 'btn-outline-success' : 'btn-outline-danger')
                    . '">'
                    . ($provider->status === 'active' ? 'Aktif' : 'Nonaktif')
                    . '</span>';
            })->addColumn('aksi', function ($provider) {
                return '<a href="javascript:void(0)" class="btn-edit-provider me-2 text-secondary" onclick="openEdit(' . $provider->id . ')" title="Edit"><i class="las la-edit fs-5"></i></a><a href="javascript:void(0)" class="btn-delete-provider text-danger" onclick="removeProvider(' . $provider->id . ')"  title="Delete"><i class="las la-trash-alt fs-5"></i> </a>';
            })
            ->rawColumns(['api_key', 'type_api', 'status', 'aksi'])
            ->make(true);
    }
}
