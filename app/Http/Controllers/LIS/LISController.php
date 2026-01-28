<?php

namespace App\Http\Controllers\LIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Auth, Storage;

class LISController extends Controller
{
    function masterDokter()
    {
        $data = DB::select("
            SELECT
                dok.NIP,
                master.getNamaLengkapPegawai(dok.NIP) AS nama
            FROM master.dokter dok
            WHERE dok.`STATUS` = 1
        ");

        return response()->json([
            'response' => $data,
            'metadata' => [
                'message' => 'Ok',
                'code'    => 200
            ]
        ]);
    }

    function masterTindakan()
    {
        /** =========================
         * QUERY TINDAKAN
         * ========================= */
        $tindakan = DB::select("
            SELECT
                td.ID tindakan_id,
                td.NAMA nama_tindakan
            FROM master.tindakan td
            WHERE td.`STATUS` = 1
            AND td.JENIS = 8
            ORDER BY td.NAMA
        ");

        /** =========================
         * GABUNG DENGAN PARAMETER
         * ========================= */
        $response = collect($tindakan)->map(function ($t) {

            $parameter = DB::select("
                SELECT
                    ptl.TINDAKAN tindakan_id,
                    ptl.INDEKS `index`,
                    ptl.ID parameter_id,
                    ptl.PARAMETER nama_parameter
                FROM master.parameter_tindakan_lab ptl
                WHERE ptl.TINDAKAN = ?
                AND ptl.`STATUS` = 1
                ORDER BY ptl.INDEKS
            ", [$t->tindakan_id]);

            return [
                'tindakan_id'   => $t->tindakan_id,
                'nama_tindakan' => $t->nama_tindakan,
                'parameter'     => $parameter
            ];
        });

        return response()->json([
            'response' => $response,
            'metadata' => [
                'message' => 'Ok',
                'code'    => 200
            ]
        ]);
    }

    function getOrderLab()
    {
        /** =========================
         * QUERY LIST (HEADER)
         * ========================= */
        $list = DB::select("
            SELECT
                ol.TANGGAL tgl,
                ol.NOMOR no_lab,
                pp.NORM no_rm,
                master.getNamaLengkap(p.NORM) nama,
                DATE_FORMAT(p.TANGGAL_LAHIR,'%Y-%m-%dT00:00:00Z') tgl_lahir,
                ref.DESKRIPSI jenis_kelamin,
                master.getCariUmur(ol.TANGGAL, p.TANGGAL_LAHIR) umur,
                master.getAlamatPasienCustom(p.NORM) alamat,
                IF(jk.ID=3,CONCAT(r.DESKRIPSI,' - ',rk.KAMAR),r.DESKRIPSI) ruang,
                kls.DESKRIPSI kelas,
                crby.DESKRIPSI status,
                dok.NIP kode_dokter,
                master.getNamaLengkapPegawai(dok.NIP) dokter_pengirim,
                jk.DESKRIPSI jenis_kunjungan,
                ol.CITO cito
            FROM layanan.order_lab ol
                LEFT JOIN pendaftaran.kunjungan k ON k.NOMOR=ol.KUNJUNGAN AND k.`STATUS`!=0
                LEFT JOIN pendaftaran.pendaftaran pp ON pp.NOMOR=k.NOPEN
                LEFT JOIN pendaftaran.penjamin pj ON pj.NOPEN=pp.NOMOR
                LEFT JOIN master.pasien p ON p.NORM=pp.NORM
                LEFT JOIN master.ruangan r ON r.ID=k.RUANGAN
                LEFT JOIN master.ruang_kamar_tidur rkt ON rkt.ID=k.RUANG_KAMAR_TIDUR
                LEFT JOIN master.ruang_kamar rk ON rk.ID=rkt.RUANG_KAMAR
                LEFT JOIN master.referensi ref ON ref.ID=p.JENIS_KELAMIN AND ref.JENIS=2 AND ref.`STATUS`!=0
                LEFT JOIN master.referensi crby ON crby.ID=pj.JENIS AND crby.JENIS=10 AND crby.`STATUS`!=0
                LEFT JOIN master.referensi kls ON kls.ID=rk.KELAS AND kls.JENIS=19 AND kls.`STATUS`!=0
                LEFT JOIN master.referensi jk ON jk.ID=r.JENIS_KUNJUNGAN AND jk.JENIS=15 AND jk.`STATUS`!=0
                LEFT JOIN pendaftaran.kunjungan pk ON pk.REF=ol.NOMOR AND pk.`STATUS`!=0
                LEFT JOIN master.dokter dok ON dok.ID=ol.DOKTER_ASAL
                LEFT JOIN layanan.order_detil_lab odl ON odl.ORDER_ID=ol.NOMOR
                LEFT JOIN layanan.tindakan_medis tm ON tm.KUNJUNGAN=pk.NOMOR
                LEFT JOIN layanan.hasil_lab hl ON hl.TINDAKAN_MEDIS=tm.ID
            WHERE ol.`STATUS`=2
              AND k.`STATUS`=1
              AND pk.`STATUS`=1
              AND tm.`STATUS`!=0
              AND hl.TINDAKAN_MEDIS IS NULL
            GROUP BY ol.KUNJUNGAN
            ORDER BY ol.TANGGAL DESC
        ");
        // print_r($list);
        // die();
        /** =========================
         * LOOP & GABUNG DATA
         * ========================= */
        $response = collect($list)->map(function ($row) {

            /** -------- LIST TEST -------- */
            $listTest = DB::select("
                SELECT
                    ol.NOMOR no_lab,
                    tm.ID detail_id,
                    tm.TINDAKAN test_id,
                    td.NAMA nama_test
                FROM layanan.order_lab ol
                    LEFT JOIN pendaftaran.kunjungan k ON k.REF=ol.NOMOR
                    LEFT JOIN layanan.tindakan_medis tm ON tm.KUNJUNGAN=k.NOMOR AND tm.`STATUS`!=0
                    LEFT JOIN master.tindakan td ON td.ID=tm.TINDAKAN AND td.`STATUS`!=0
                WHERE ol.NOMOR = ?
            ", [$row->no_lab]);

            $listTest = collect($listTest)->map(function ($test) {

                /** -------- DETAIL TEST (PAKET) -------- */
                $detailTest = DB::select("
                    SELECT
                        ptl.TINDAKAN paket_id,
                        ptl.INDEKS `index`,
                        ptl.ID test_id,
                        ptl.PARAMETER nama_test
                    FROM master.parameter_tindakan_lab ptl
                    WHERE ptl.TINDAKAN = ?
                      AND ptl.`STATUS` = 1
                    ORDER BY ptl.INDEKS
                ", [$test->test_id]);

                return [
                    'detail_id'   => $test->detail_id,
                    'no_lab'      => $test->no_lab,
                    'test_id'     => $test->test_id,
                    'nama_test'   => $test->nama_test,
                    'jenis_lab'   => 'pk',
                    'jenis_test'  => count($detailTest) > 0 ? 'p' : 't',
                    'detail_test' => $detailTest
                ];
            });

            return [
                'tgl'             => $row->tgl,
                'no_lab'          => $row->no_lab,
                'no_rm'           => $row->no_rm,
                'nama'            => $row->nama,
                'tgl_lahir'       => $row->tgl_lahir,
                'jenis_kelamin'   => $row->jenis_kelamin,
                'umur'            => $row->umur,
                'alamat'          => $row->alamat,
                'ruang'           => $row->ruang,
                'kelas'           => $row->kelas,
                'status'          => $row->status,
                'kode_dokter'     => $row->kode_dokter,
                'dokter_pengirim' => $row->dokter_pengirim,
                'jenis_kunjungan' => $row->jenis_kunjungan,
                'cito'            => (bool) $row->cito,
                'list_test'       => $listTest
            ];
        });

        return response()->json([
            'response' => [
                'list' => $response
            ],
            'metadata' => [
                'message' => 'Ok',
                'code'    => 200
            ]
        ]);
    }

    // function insertHasilTestBulk(Request $request) {
    //     \Log::info('Request masuk', $request->all());
        // print_r($request->all());
        // die();

        // return response()->json(['status' => 'ok']);
    // }

    // function insertHasilTestBulk(Request $request)
    // {
    //     \Log::info('Request masuk', $request->all());

    //     $request->validate([
    //         'no_lab'      => 'required',
    //         'test_id'     => 'required',
    //         'hasil_list'  => 'required|array|min:1',
    //         'hasil_list.*.parameter_id' => 'required',
    //         'hasil_list.*.hasil'        => 'required',
    //     ]);

    //     DB::beginTransaction();
    //     try {

    //         /** =========================
    //          * AMBIL TINDAKAN_MEDIS
    //          * ========================= */
    //         $tindakanMedis = DB::selectOne("
    //             SELECT tm.ID
    //             FROM layanan.order_lab ol
    //             JOIN pendaftaran.kunjungan k ON k.REF = ol.NOMOR
    //             JOIN layanan.tindakan_medis tm ON tm.KUNJUNGAN = k.NOMOR
    //             WHERE ol.NOMOR = ?
    //             AND tm.TINDAKAN = ?
    //             AND tm.`STATUS` != 0
    //             LIMIT 1
    //         ", [
    //             $request->no_lab,
    //             $request->test_id
    //         ]);

    //         if (!$tindakanMedis) {
    //             return response()->json([
    //                 'message' => 'Tindakan medis tidak ditemukan',
    //                 'status'  => 404
    //             ], 404);
    //         }

    //         /** =========================
    //          * SIAPKAN DATA INSERT
    //          * ========================= */
    //         $insertData = [];

    //         foreach ($request->hasil_list as $row) {

    //             /** ---- CEK DUPLIKASI ---- */
    //             $exists = DB::selectOne("
    //                 SELECT 1
    //                 FROM hasil_lab
    //                 WHERE TINDAKAN_MEDIS = ?
    //                 AND PARAMETER_TINDAKAN = ?
    //                 LIMIT 1
    //             ", [
    //                 $tindakanMedis->ID,
    //                 $row['parameter_id']
    //             ]);

    //             if ($exists) {
    //                 continue; // SKIP kalau sudah ada
    //             }

    //             $insertData[] = [
    //                 'id'                 => Str::uuid()->toString(),
    //                 'tindakan_medis'     => $tindakanMedis->ID,
    //                 'parameter_tindakan' => $row['parameter_id'],
    //                 'hasil'              => $row['hasil'],
    //                 'nilai_normal'       => $row['nilai_normal'] ?? null,
    //                 'satuan'             => $row['satuan'] ?? null,
    //                 'keterangan'         => $row['keterangan'] ?? null,
    //             ];
    //         }

    //         /** =========================
    //          * JIKA SEMUA SUDAH ADA
    //          * ========================= */
    //         if (count($insertData) === 0) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'message' => 'Semua data sudah ada',
    //                 'status'  => 200
    //             ]);
    //         }

    //         /** =========================
    //          * BULK INSERT
    //          * ========================= */
    //         $values = [];
    //         $bindings = [];

    //         foreach ($insertData as $d) {
    //             $values[] = "(?, ?, ?, NOW(), ?, ?, ?, ?, 0, 1)";
    //             $bindings[] = $d['id'];
    //             $bindings[] = $d['tindakan_medis'];
    //             $bindings[] = $d['parameter_tindakan'];
    //             $bindings[] = $d['hasil'];
    //             $bindings[] = $d['nilai_normal'];
    //             $bindings[] = $d['satuan'];
    //             $bindings[] = $d['keterangan'];
    //         }

    //         DB::insert("
    //             INSERT INTO hasil_lab
    //             (
    //                 ID,
    //                 TINDAKAN_MEDIS,
    //                 PARAMETER_TINDAKAN,
    //                 TANGGAL,
    //                 HASIL,
    //                 NILAI_NORMAL,
    //                 SATUAN,
    //                 KETERANGAN,
    //                 OTOMATIS,
    //                 STATUS
    //             )
    //             VALUES " . implode(',', $values),
    //             $bindings
    //         );

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Ok',
    //             'inserted' => count($insertData),
    //             'status'  => 200
    //         ]);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Insert gagal',
    //             'error'   => $e->getMessage(),
    //             'status'  => 500
    //         ], 500);
    //     }
    // }

    function insertHasilTestBulk(Request $request)
    {
        // Logging request untuk debugging
        \Log::info('Request masuk', $request->all());

        // Validasi data dasar
        $request->validate([
            'no_lab'     => 'required',
            'hasil_list' => 'required|array|min:1',
            'hasil_list.*.parameter_id' => 'required',
            'hasil_list.*.hasil'        => 'required',
        ]);

        DB::beginTransaction();
        try {
            // Ambil tindakan medis jika ada, tapi jangan gagal kalau tidak ditemukan
            $tindakanMedis = null;

            if ($request->has('no_lab')) {
                $tindakanMedis = DB::selectOne("
                    SELECT tm.ID
                    FROM layanan.order_lab ol
                    JOIN pendaftaran.kunjungan k ON k.REF = ol.NOMOR
                    JOIN layanan.tindakan_medis tm ON tm.KUNJUNGAN = k.NOMOR
                    WHERE tm.ID = ?
                    AND tm.`STATUS` != 0
                    LIMIT 1
                ", [
                    $request->no_lab
                ]);
            }

            $lastRow = DB::table('layanan.hasil_lab')
                        ->select('ID')
                        ->orderBy('ID', 'DESC')
                        ->first();

            $lastId = $lastRow ? $lastRow->ID : null;  // ambil properti ID
            $newId = $lastId ? (string)((int)$lastId + 1) : '1';
            $idCounter = (int)$newId;

            // Jika tidak ditemukan, tetap buat dummy ID untuk testing
            $tindakanId = $tindakanMedis->ID;

            $insertData = [];
            foreach ($request->hasil_list as $row) {
                $insertData[] = [
                    'id'                 => (string)$idCounter,
                    'tindakan_medis'     => $tindakanId,
                    'parameter_tindakan' => $row['parameter_id'],
                    'hasil'              => $row['hasil'],
                    'nilai_normal'       => $row['nilai_normal'] ?? null,
                    'satuan'             => $row['satuan'] ?? null,
                    'keterangan'         => $row['keterangan'] ?? null,
                    'oleh'               => 14,
                ];
                $idCounter++;
            }

            // Bulk insert
            $values = [];
            $bindings = [];
            foreach ($insertData as $d) {
                $values[] = "(?, ?, ?, NOW(), ?, ?, ?, ?, ?, 0, 1)";
                $bindings[] = $d['id'];
                $bindings[] = $d['tindakan_medis'];
                $bindings[] = $d['parameter_tindakan'];
                $bindings[] = $d['hasil'];
                $bindings[] = $d['nilai_normal'];
                $bindings[] = $d['satuan'];
                $bindings[] = $d['keterangan'];
                $bindings[] = $d['oleh'];
            }

            DB::insert("
                INSERT INTO layanan.hasil_lab
                (
                    ID,
                    TINDAKAN_MEDIS,
                    PARAMETER_TINDAKAN,
                    TANGGAL,
                    HASIL,
                    NILAI_NORMAL,
                    SATUAN,
                    KETERANGAN,
                    OLEH,
                    OTOMATIS,
                    STATUS
                )
                VALUES " . implode(',', $values),
                $bindings
            );

            DB::commit();

            return response()->json([
                'message'  => 'Ok',
                'inserted' => count($insertData),
                'status'   => 200,
                'data'     => $insertData, // optional untuk debugging
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Insert gagal',
                'error'   => $e->getMessage(),
                'status'  => 500
            ], 500);
        }
    }

}
