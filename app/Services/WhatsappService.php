<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected $url;
    protected $token;
    protected $phoneId;

    public function __construct()
    {
        $this->url = config('services.whatsapp.url', env('WHATSAPP_API_URL'));
        $this->token = env('WHATSAPP_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_ID');
    }

    public function sendMessage($to, $message)
    {
        $endpoint = "{$this->url}/{$this->phoneId}/messages";

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "text",
            "text" => [
                "body" => $message
            ]
        ];

        $response = Http::withToken($this->token)
            ->post($endpoint, $payload);

        return $response->json();
    }
}
