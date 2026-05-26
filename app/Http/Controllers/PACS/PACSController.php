<?php

namespace App\Http\Controllers\PACS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Auth, Storage;

class PACSController extends Controller
{
    public function getOrderRad(Request $request)
    {
        if ($request->filled('norm')) {
            if (!is_numeric($request->norm)) {
                return response()->json([
                    'success' => false,
                    'message' => 'NORM harus berupa angka',
                    'data' => null
                ], 400);
            } else if (strlen($request->norm) > 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'NORM tidak boleh lebih dari 8 digit',
                    'data' => null
                ], 400);
            }
        }

        $query = DB::table('pendaftaran.kunjungan as pk');

        $query->select([
                // 'pk.NOMOR as NOMOR',
                'pk.MASUK as MASUK',
                'pp.NORM as NORM',

                DB::raw('master.getNamaLengkap(pp.NORM) as NAMA_PASIEN'),

                'p.TANGGAL_LAHIR as TANGGAL_LAHIR',
                'r.DESKRIPSI as JENIS_KELAMIN',

                'tm.TINDAKAN as KODE_PEMERIKSAAN_RADIOLOGI',
                't.NAMA as NAMA_PEMERIKSAAN_RADIOLOGI',

                'ap.NIP as KODE_DOKTER_PERUJUK',
                DB::raw('master.getNamaLengkapPegawai(ap.NIP) as DOKTER_PERUJUK'),

                'dok.NIP as KODE_DOKTER_RADIOLOGI',
                DB::raw('master.getNamaLengkapPegawai(dok.NIP) as DOKTER_RADIOLOGI'),

                'ssp.id as ID_SATUSEHAT_PATIENT',
                'sse.id as ID_SATUSEHAT_ENCOUNTER',
            ])

            ->join('pendaftaran.pendaftaran as pp', 'pp.NOMOR', '=', 'pk.NOPEN')

            ->join('pendaftaran.tujuan_pasien as tp', 'tp.NOPEN', '=', 'pp.NOMOR')

            ->leftJoin('master.dokter as dok', 'tp.DOKTER', '=', 'dok.ID')

            ->leftJoin('master.pasien as p', 'p.NORM', '=', 'pp.NORM')

            ->leftJoin('master.referensi as r', function ($join) {
                $join->on('r.ID', '=', 'p.JENIS_KELAMIN')
                     ->where('r.JENIS', '=', 2);
            })

            ->leftJoin('master.kartu_identitas_pasien as kip', 'kip.NORM', '=', 'p.NORM')

            ->leftJoin('layanan.tindakan_medis as tm', function ($join) {
                $join->on('tm.KUNJUNGAN', '=', 'pk.NOMOR')
                     ->where('tm.STATUS', '<>', 0);
            })

            ->leftJoin('master.tindakan as t', 't.ID', '=', 'tm.TINDAKAN')

            ->leftJoin('layanan.order_rad as lor', function ($join) {
                $join->on('lor.NOMOR', '=', 'pk.REF')
                     ->where('lor.STATUS', '<>', 0);
            })

            ->join('pendaftaran.kunjungan as k', function ($join) {
                $join->on('k.NOMOR', '=', 'lor.KUNJUNGAN')
                     ->where('k.STATUS', '<>', 0);
            })

            ->leftJoin('aplikasi.pengguna as ap', 'ap.ID', '=', 'k.DPJP')

            ->leftJoin('kemkes-ihs.patient as ssp', function ($join) {
                $join->on('ssp.nik', '=', 'kip.NOMOR')
                     ->whereNotNull('kip.NOMOR')
                     ->where('kip.NOMOR', '<>', '')
                     ->where('kip.NOMOR', '<>', '0');
            })

            ->leftJoin('kemkes-ihs.encounter as sse', 'sse.refId', '=', 'pp.NOMOR')

            ->where('pk.RUANGAN', 'like', '1020501%')
            ->whereIn('pk.STATUS', [1, 2]);

            // ->whereBetween('pk.MASUK', [
            //     now()->subMonth()->startOfMonth(),
            //     now()->endOfMonth()
            // ])

        if ($request->filled('norm')) {
            $query->where('pp.NORM', $request->norm);
        }

        if ($request->filled('tgl')) {
            $query->whereDate('pk.MASUK', $request->tgl);
        } else {
            $query->whereBetween('pk.MASUK', [
                now()->subMonth()->startOfMonth(),
                now()->endOfMonth()
            ]);
        }

        $data = $query->orderByDesc('pk.MASUK')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data radiologi berhasil diambil',
            'data' => $data
        ]);
    }
}
