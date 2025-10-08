<?php

use Illuminate\Support\Facades\DB;
use App\Models\regonline\jadwal_dokter_hfis;
use App\Models\regonline\poli_bpjs;

if (!function_exists('getJadwalDokter')) {
    function getJadwalDokter($message)
    {
        $message = strtolower(trim($message));

        // Kasus 1️⃣: Cari berdasarkan nama dokter
        if (str_contains($message, 'dr')) {
            preg_match('/dr\.?\s+([a-zA-Z]+)/', $message, $matches);
            $nama = $matches[1] ?? null;

            if (!$nama) {
                return "Ketik 'Jadwal dr. [Nama Dokter]' untuk melihat jadwal dokter.";
            }

            $jadwal = jadwal_dokter_hfis::on('db_regonline')
                ->select(
                    'jadwal_dokter_hfis.NM_DOKTER',
                    'jadwal_dokter_hfis.HARI',
                    'jadwal_dokter_hfis.JAM_MULAI',
                    'jadwal_dokter_hfis.JAM_SELESAI',
                    'poli_bpjs.NMPOLI'
                )
                ->join('poli_bpjs', 'poli_bpjs.KDPOLI', '=', 'jadwal_dokter_hfis.KD_POLI')
                ->where('jadwal_dokter_hfis.STATUS', 1)
                ->where('poli_bpjs.STATUS', 1)
                ->where('jadwal_dokter_hfis.NM_DOKTER', 'like', "%$nama%")
                ->groupBy(
                    'jadwal_dokter_hfis.NM_DOKTER',
                    'jadwal_dokter_hfis.HARI',
                    'jadwal_dokter_hfis.JAM_MULAI',
                    'jadwal_dokter_hfis.JAM_SELESAI',
                    'poli_bpjs.NMPOLI'
                )
                ->orderByRaw("FIELD(HARI, 'SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU','MINGGU')")
                ->get();

            if ($jadwal->isEmpty()) {
                return "Maaf, jadwal dr. $nama tidak ditemukan atau tidak aktif.";
            }

            $dokter = $jadwal->first()->NM_DOKTER;
            $text = "📋 *Jadwal dr. {$dokter}:*\n";
            foreach ($jadwal as $row) {
                $text .= "🕓 {$row->HARI} {$row->JAM_MULAI}-{$row->JAM_SELESAI} ({$row->NMPOLI})\n";
            }
            return trim($text);
        }

        // Kasus 2️⃣: Cari berdasarkan nama poli
        if (str_contains($message, 'poli')) {
            preg_match('/poli\s+([a-zA-Z]+)/', $message, $matches);
            $namaPoli = $matches[1] ?? null;

            if (!$namaPoli) {
                return "Ketik 'Poli [Nama Poli]' untuk melihat daftar dokter di poli tersebut.";
            }

            $jadwal = jadwal_dokter_hfis::on('db_regonline')
                ->select(
                    'jadwal_dokter_hfis.NM_DOKTER',
                    'jadwal_dokter_hfis.HARI',
                    'jadwal_dokter_hfis.JAM_MULAI',
                    'jadwal_dokter_hfis.JAM_SELESAI',
                    'poli_bpjs.NMPOLI'
                )
                ->join('poli_bpjs', 'poli_bpjs.KDPOLI', '=', 'jadwal_dokter_hfis.KD_POLI')
                ->where('jadwal_dokter_hfis.STATUS', 1)
                ->where('poli_bpjs.STATUS', 1)
                ->where('poli_bpjs.NMPOLI', 'like', "%$namaPoli%")
                ->groupBy(
                    'jadwal_dokter_hfis.NM_DOKTER',
                    'jadwal_dokter_hfis.HARI',
                    'jadwal_dokter_hfis.JAM_MULAI',
                    'jadwal_dokter_hfis.JAM_SELESAI',
                    'poli_bpjs.NMPOLI'
                )
                ->orderByRaw("FIELD(HARI, 'SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU','MINGGU')")
                ->get();

            if ($jadwal->isEmpty()) {
                return "Maaf, jadwal Poli $namaPoli tidak ditemukan atau tidak aktif.";
            }

            $text = "📋 *Jadwal Poli {$jadwal->first()->NMPOLI}:*\n";
            foreach ($jadwal as $row) {
                $text .= "👨‍⚕️ {$row->NM_DOKTER}: {$row->HARI} {$row->JAM_MULAI}-{$row->JAM_SELESAI}\n";
            }
            return trim($text);
        }

        // Kasus default (tidak dikenali)
        return "Halo! Saya asisten jadwal dokter RS 🏥\n\nSilakan ketik salah satu:\n- Jadwal dr. [Nama Dokter]\n- Poli [Nama Poli]";
    }
}
