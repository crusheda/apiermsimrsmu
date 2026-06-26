<?php

namespace App\Http\Controllers\PACS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $query->distinct()->select([
                'pk.MASUK as MASUK',
                'pp.NORM as NORM',

                DB::raw('master.getNamaLengkap(pp.NORM) as NAMA_PASIEN'),

                'p.TANGGAL_LAHIR as TANGGAL_LAHIR',
                'r.DESKRIPSI as JENIS_KELAMIN',

                'tm.ID as ID_PEMERIKSAAN_RADIOLOGI',
                'tm.TINDAKAN as KODE_PEMERIKSAAN_RADIOLOGI',
                't.NAMA as NAMA_PEMERIKSAAN_RADIOLOGI',

                'ap.NIP as KODE_DOKTER_PERUJUK',
                DB::raw('master.getNamaLengkapPegawai(ap.NIP) as DOKTER_PERUJUK'),

                'dok2.NIP as KODE_DOKTER_RADIOLOGI',
                DB::raw('master.getNamaLengkapPegawai(dok2.NIP) as DOKTER_RADIOLOGI'),

                'ssp.id as ID_SATUSEHAT_PATIENT',
                'sse.id as ID_SATUSEHAT_ENCOUNTER',
                'ssr.id as ID_SATUSEHAT_SERVICE_REQUEST',

                DB::raw("
                    JSON_UNQUOTE(
                        JSON_EXTRACT(ssr.identifier, '$[0].value')
                    ) as ACCESSION_NUMBER
                "),

                // "NOMOR_SERVICE_REQUEST": "24111300345",
                    // contoh NORM = 113320
                    // id service_request : 8e4a8463-3209-4633-a006-5a28133914ce
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

            ->leftJoin('layanan.order_detil_rad as odr', function ($join) {
                $join->on('odr.ORDER_ID', '=', 'lor.NOMOR')
                    ->where('odr.STATUS', '<>', 0);
            })

            ->leftJoin('layanan.tindakan_medis as tm2', function ($join) {
                $join->on('tm2.ID', '=', 'odr.REF')
                    ->where('tm2.STATUS', '<>', 0);
            })

            ->leftJoin('layanan.hasil_rad as hr', function ($join) {
                $join->on('hr.TINDAKAN_MEDIS', '=', 'tm2.ID')
                    ->where('hr.STATUS', '<>', 0);
            })

            ->leftJoin('master.dokter as dok2', 'hr.DOKTER', '=', 'dok2.ID')

            ->leftJoin('pendaftaran.kunjungan as k', function ($join) {
                $join->on('k.NOMOR', '=', 'lor.KUNJUNGAN')
                    ->where('k.STATUS', '<>', 0);
            })

            ->leftJoin('master.dokter as ap', 'ap.ID', '=', 'k.DPJP')

            ->leftJoin('kemkes-ihs.patient as ssp', function ($join) {
                $join->on('ssp.nik', '=', 'kip.NOMOR')
                    ->whereNotNull('kip.NOMOR')
                    ->where('kip.NOMOR', '<>', '')
                    ->where('kip.NOMOR', '<>', '0');
            })

            ->leftJoin('kemkes-ihs.encounter as sse', 'sse.refId', '=', 'pp.NOMOR')

            ->leftJoin('kemkes-ihs.service_request as ssr', function ($join) {
                $join->on(
                    'ssr.nopen',
                    '=',
                    DB::raw('COALESCE(sse.refId, pp.NOMOR)')
                );

                $join->whereRaw("
                    JSON_UNQUOTE(
                        JSON_EXTRACT(ssr.category, '$[0].coding[0].display')
                    ) = 'Imaging'
                ");
            })

            ->where('pk.RUANGAN', 'like', '1020501%')
            ->whereIn('pk.STATUS', [1, 2]);

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
        ], 200);
    }

    public function getHasilRad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'acsn' => [
                'required',
                'digits:11'
            ]
        ], [
            'acsn.required' => 'Accession Number wajib terisi',
            'acsn.digits' => 'Accession Number harus 11 digit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        $query = DB::table('layanan.hasil_rad as hr');

        $query->select([
                'hr.TINDAKAN_MEDIS as accession_number',
                'pp.NORM as no_rm',
                DB::raw('master.getNamaLengkap(pp.NORM) AS nama_pasien'),
                DB::raw('DATE(p.TANGGAL_LAHIR) as tgl_lahir_pasien'),
                DB::raw('master.getCariUmur(pp.TANGGAL,p.TANGGAL_LAHIR) AS umur_pasien'),
                DB::raw('master.getNamaLengkapPegawai(dr.NIP) AS nama_dokter'),
                DB::raw('master.getNamaLengkapPegawai(pe.NIP) AS nama_user'),
                'hr.KLINIS as expertise_klinis',
                'hr.KESAN as expertise_kesan',
                'hr.USUL as expertise_usul',
                'hr.BTK as expertise_btk',
                'hr.HASIL as expertise_hasil',
                'hr.KRITIS as expertise_kritis',
            ])

            ->join('layanan.tindakan_medis as tm', function ($join) {
                $join->on('tm.ID', '=', 'hr.TINDAKAN_MEDIS')
                    ->where('tm.STATUS', '<>', 0);
            })

            ->join('pendaftaran.kunjungan as pk', function ($join) {
                $join->on('pk.NOMOR', '=', 'tm.KUNJUNGAN')
                    ->where('pk.STATUS', '<>', 0);
            })

            ->join('pendaftaran.pendaftaran as pp', function ($join) {
                $join->on('pp.NOMOR', '=', 'pk.NOPEN')
                    ->where('pp.STATUS', '<>', 0);
            })

            ->leftJoin('master.dokter as dr', 'hr.DOKTER', '=', 'dr.ID')

            ->leftJoin('aplikasi.pengguna as pe', 'pe.ID', '=', 'hr.OLEH')

            ->leftJoin('master.pasien as p', 'p.NORM', '=', 'pp.NORM')

            ->whereIn('hr.STATUS', [1, 2])

            ->where('hr.TINDAKAN_MEDIS', $request->acsn);

        $data = $query->orderByDesc('hr.TANGGAL')->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'message' => "Data Hasil Radiologi berhasil diambil",
                'data' => $data
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Data Hasil Radiologi gagal diambil / Belum dimasukkan",
                'data' => null
            ], 404);
        }
    }
}
