<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class JadwalDokterController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        // ✅ Pastikan ada pesan dari user
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return response('ok', 200);
        }

        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from']; // Nomor WhatsApp pengirim
        $text = strtolower(trim($message['text']['body'] ?? ''));

        // ===============================
        // 🔹 1. Jika user ketik "tanya jadwal"
        // ===============================
        if (strpos($text, 'tanya jadwal') !== false) {

            $poliList = DB::connection('db_regonline')
                ->table('jadwal_dokter_hfis as j')
                ->join('poli_bpjs as p', 'p.KDPOLI', '=', 'j.KD_POLI')
                ->where('j.STATUS', 1)
                ->where('p.STATUS', 1)
                ->groupBy('p.NMPOLI')
                ->orderBy('p.NMPOLI')
                ->pluck('p.NMPOLI')
                ->toArray();

            if (empty($poliList)) {
                $reply = "Maaf, data poli belum tersedia di sistem.";
            } else {
                $reply = "🩺 *Daftar Poli di Rumah Sakit Kami:*\n\n";
                foreach ($poliList as $i => $nama) {
                    $reply .= ($i + 1) . ". " . $nama . "\n";
                }
                $reply .= "\nKetik *Poli [Nama Poli]* untuk melihat jadwal dokter.\nContoh: *Poli Mata*";
            }

            $this->sendWhatsappMessage($from, $reply);
            return response('ok', 200);
        }

        // ===============================
        // 🔹 2. Jika user ketik "poli [nama]"
        // ===============================
        if (strpos($text, 'poli') !== false) {
            // Ambil nama poli sesudah kata "poli"
            preg_match('/poli\s+([a-zA-Z\s]+)/', $text, $matches);
            $namaPoli = trim($matches[1] ?? '');

            if (!$namaPoli) {
                $this->sendWhatsappMessage($from, "Ketik *Poli [Nama Poli]* untuk melihat jadwalnya.\nContoh: *Poli Mata*");
                return response('ok', 200);
            }

            // Ambil jadwal dokter dari DB
            $jadwal = DB::connection('db_regonline')
                ->table('jadwal_dokter_hfis as j')
                ->join('poli_bpjs as p', 'p.KDPOLI', '=', 'j.KD_POLI')
                ->select('j.NM_DOKTER', 'j.NM_HARI', 'j.JAM_MULAI', 'j.JAM_SELESAI', 'p.NMPOLI')
                ->where('p.NMPOLI', 'like', "%$namaPoli%")
                ->where('j.STATUS', 1)
                ->where('p.STATUS', 1)
                ->orderBy('j.NM_HARI')
                ->orderBy('j.JAM_MULAI')
                ->get();

            if ($jadwal->isEmpty()) {
                $reply = "Maaf, jadwal untuk *Poli $namaPoli* belum tersedia.";
            } else {
                $reply = "📅 *Jadwal Dokter Poli {$jadwal[0]->NMPOLI}:*\n\n";
                foreach ($jadwal as $row) {
                    $reply .= "• dr. {$row->NM_DOKTER}\n  🕓 {$row->NM_HARI}, {$row->JAM_MULAI}–{$row->JAM_SELESAI}\n\n";
                }
                $reply .= "Ketik *Tanya Jadwal* untuk lihat poli lain.";
            }

            $this->sendWhatsappMessage($from, trim($reply));
            return response('ok', 200);
        }

        // ===============================
        // 🔹 3. Jika user ketik lain-lain
        // ===============================
        $reply = "Halo! 👋\nSaya asisten jadwal dokter RS.\n\nKetik salah satu:\n- *Tanya Jadwal* → untuk daftar poli\n- *Poli [Nama Poli]* → untuk lihat jadwal dokter";
        $this->sendWhatsappMessage($from, $reply);

        return response('ok', 200);
    }

    // ======================================
    // 🔹 Fungsi kirim pesan ke WhatsApp API
    // ======================================
    private function sendWhatsappMessage($to, $message)
    {
        $token = env('WHATSAPP_TOKEN');
        $phoneId = env('WHATSAPP_PHONE_ID');

        return Http::withToken($token)->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }
}
