<?php

namespace App\Http\Controllers\Antrean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Auth, Storage;

class AntreanController extends Controller
{
    function getAntreanPoli()
    {
        $tgl = '2025-09-25';
        $ruangan = '102010105'; // POLI BEDAH

        // Ambil yang sedang dipanggil dulu
        $dipanggil = DB::table('pendaftaran.panggilan_antrian_ruangan AS par')
            ->select(
                'par.ID',
                'pp.NORM',
                'ar.NOMOR AS NOMORANTREAN','ar.STATUS AS STATUSANTREAN',
                'par.STATUS AS STATUSPANGGILAN',
                'ru.DESKRIPSI AS NAMARUANGAN',
                'ar.ID AS ANTRIAN_ID'
            )
            ->leftJoin('pendaftaran.antrian_ruangan AS ar','par.ANTRIAN_RUANGAN','=','ar.ID')
            ->leftJoin('pendaftaran.pendaftaran AS pp','pp.NOMOR','=','ar.REF')
            ->leftJoin('master.ruangan AS ru','ar.RUANGAN','=','ru.ID')
            ->where('ar.RUANGAN', $ruangan)
            ->where('par.STATUS', 2)
            ->where('ar.TANGGAL',$tgl)
            ->orderBy('par.ID', 'DESC')
            ->first();

        $antrianDipanggilId = $dipanggil->ANTRIAN_ID ?? null;

        // MENUNGGU (exclude yang dipanggil)
        $menunggu = DB::table('pendaftaran.antrian_ruangan AS ar')
            ->select(
                'pp.NORM',
                DB::raw('master.getNamaLengkap(pp.NORM) AS NAMAPASIEN'),
                'ar.NOMOR AS NOMORANTREAN','ar.STATUS AS STATUSANTREAN',
                'ru.DESKRIPSI AS NAMARUANGAN'
            )
            ->leftJoin('master.ruangan AS ru','ar.RUANGAN','=','ru.ID')
            ->leftJoin('pendaftaran.pendaftaran AS pp','pp.NOMOR','=','ar.REF')
            ->where('ar.RUANGAN', $ruangan)
            ->whereIn('ar.STATUS', [1]) // MENUNGGU
            ->where('ar.TANGGAL',$tgl)
            ->when($antrianDipanggilId, function($q) use ($antrianDipanggilId) {
                $q->where('ar.ID','!=',$antrianDipanggilId);
            })
            ->whereNotExists(function($q) {
                $q->select(DB::raw(1))
                ->from('pendaftaran.panggilan_antrian_ruangan AS par2')
                ->whereRaw('par2.ANTRIAN_RUANGAN = ar.ID');
            })
            ->orderBy('ar.NOMOR', 'ASC')
            ->get();

        // SELESAI (exclude yang dipanggil)
        $selesai = DB::table('pendaftaran.panggilan_antrian_ruangan AS par')
            ->select(
                'pp.NORM',
                'ar.NOMOR AS NOMORANTREAN','ar.STATUS AS STATUSANTREAN',
                'par.STATUS AS STATUSPANGGILAN',
                'ru.DESKRIPSI AS NAMARUANGAN',
                'ar.ID AS ANTRIAN_ID'
            )
            ->leftJoin('pendaftaran.antrian_ruangan AS ar','par.ANTRIAN_RUANGAN','=','ar.ID')
            ->leftJoin('pendaftaran.pendaftaran AS pp','pp.NOMOR','=','ar.REF')
            ->leftJoin('master.ruangan AS ru','ar.RUANGAN','=','ru.ID')
            ->where('ar.RUANGAN', $ruangan)
            ->where('par.STATUS', 2)
            ->where('ar.TANGGAL',$tgl)
            ->when($antrianDipanggilId, function($q) use ($antrianDipanggilId) {
                $q->where('ar.ID','!=',$antrianDipanggilId);
            })
            ->orderBy('par.ID', 'DESC')
            ->get();

        $poli = DB::table('master.ruangan AS ru')
            ->select('ru.DESKRIPSI AS NAMARUANGAN')
            ->where('ru.ID', $ruangan)
            ->first();

        return response()->json([
            'menunggu' => $menunggu,
            'dipanggil' => $dipanggil,
            'selesai' => $selesai,
            'poli' => $poli,
        ], 200);
    }
}
