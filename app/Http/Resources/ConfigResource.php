<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'konfigurasi_banner'    => $this->konfigurasi_banner ?? [],
            'konfigurasi_flashsale' => $this->konfigurasi_flashsale ?? null,
            'konfigurasi_order' => $this->konfigurasi_order['custom_notes'] ?? null,
            'website' => [
                'name_store' => $this->website->name_store ?? '',
                'header'     => $this->website->header ?? [],
                'footer'     => $this->website->footer ?? [],
                'logo'       => $this->website->logo ?? null,
            ],
        ];
    }
}
