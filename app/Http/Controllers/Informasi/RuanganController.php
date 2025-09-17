<?php

namespace App\Http\Controllers\Informasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\master\ruangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Auth, Storage;

class RuanganController extends Controller
{
    function getRuangan() {
        $show = DB::table('master.ruang_kamar_tidur as rkt')
                    ->leftJoin('master.ruang_kamar as rk', function($join) {
                        $join->on('rk.ID','=','rkt.RUANG_KAMAR')
                            ->where('rk.STATUS', true);
                    })
                    ->leftJoin('master.ruangan as ru', function($join) {
                        $join->on('ru.ID','=','rk.RUANGAN')
                            ->where('ru.STATUS', true)
                            ->where('ru.JENIS_KUNJUNGAN',3);
                    })
                    ->select('rkt.*','rk.KAMAR','ru.DESKRIPSI')
                    ->whereIn('rkt.STATUS',[1,2,3])
                    ->get();

        return response()->json($show, 200);
    }
}
