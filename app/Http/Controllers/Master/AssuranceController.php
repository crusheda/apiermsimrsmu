<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Auth, Storage;

class AssuranceController extends Controller
{
    function getAssurance()
    {
        $data = DB::table('master.referensi as ref')
                    ->select('ID','DESKRIPSI','STATUS')
                    ->where('JENIS',10)
                    ->where('STATUS',1)
                    ->orderBy('ID','ASC')
                    ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data master Penjamin berhasil diambil',
            'data' => $data
        ]);
    }
}
