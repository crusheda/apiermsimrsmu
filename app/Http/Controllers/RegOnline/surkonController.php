<?php

namespace App\Http\Controllers\RegOnline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\pasien;
use App\Models\mr\jadwal_kontrol;
use Carbon\Carbon;


class surkonController extends Controller
{
    function index()
    {
        return view('pages.cekSurkon');
    }

    function table()
    {
        $show = jadwal_kontrol::join('master.ruangan','master.ruangan.ID','=','medicalrecord.jadwal_kontrol.RUANGAN')
                ->join('master.dokter','master.dokter.ID','=','medicalrecord.jadwal_kontrol.DOKTER')
                ->join('master.pegawai','master.pegawai.NIP','=','master.dokter.NIP')
                ->join('aplikasi.pengguna','aplikasi.pengguna.ID','=','medicalrecord.jadwal_kontrol.OLEH')
                ->join('pendaftaran.kunjungan','pendaftaran.kunjungan.NOMOR','=','medicalrecord.jadwal_kontrol.KUNJUNGAN')
                ->join('pendaftaran.pendaftaran','pendaftaran.pendaftaran.NOMOR','=','pendaftaran.kunjungan.NOPEN')
                ->join('master.pasien','master.pasien.NORM','=','pendaftaran.pendaftaran.NORM')
                ->join('master.kartu_asuransi_pasien','master.kartu_asuransi_pasien.NORM','=','pendaftaran.pendaftaran.NORM')
                ->select('pendaftaran.kunjungan.NOPEN','master.pasien.NORM','master.pasien.NAMA as NMPASIEN','master.pasien.ALAMAT as ALPASIEN','master.kartu_asuransi_pasien.NOMOR as NOBPJS','master.pegawai.NAMA as NMDOKTER','medicalrecord.jadwal_kontrol.*','master.ruangan.DESKRIPSI as NMRUANGAN','aplikasi.pengguna.NAMA as USER')
                ->where('medicalrecord.jadwal_kontrol.STATUS',1)
                ->where('master.kartu_asuransi_pasien.JENIS',2)
                ->orderBy('medicalrecord.jadwal_kontrol.DIBUAT_TANGGAL','DESC')
                ->get();

        $data = [
            'show' => $show,
        ];

        return response()->json($data, 200);
    }

    function filter($tgl_dibuat)
    {
        $today = Carbon::now()->isoFormat('YYYY-MM-DD');
        $convert = Carbon::parse($tgl_dibuat)->isoFormat('YYYY-MM-DD');

        $show = jadwal_kontrol::where('TANGGAL',$today)->get();

        $data = [
            'show' => $show,
        ];

        return response()->json($data, 200);
    }
}
