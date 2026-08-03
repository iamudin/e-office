<?php

namespace App\Models\Plugins\EOffice;

use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WaSender implements ShouldQueue
{
     use Queueable;

    /**
     * Create a new job instance.
     */
     protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if(isset($this->data['type']) && $this->data['type'] == 'file'){
            $this->sendDocument();
        }else{
        $this->sendText();

        }
  
    }
    private function formatPhoneNumber($number)
    {
        // Hapus karakter selain angka
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Ubah awalan 0 menjadi 62
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        
        return $number;
    }

    function sendDocument() 
    {
        $response = Http::withoutVerifying()->post(config('e-office.wa_host').'/message/send-document', [
            'session' => config('e-office.wa_session'),
            'to' => $this->formatPhoneNumber($this->data['to']),
            'text' => $this->data['text'],
            'document_url' => $this->data['document_url'],
            'document_name' => $this->data['document_name'],
            'is_group'=>false,
        ]);
        if (!$response->successful()) {
            WaSender::dispatch($this->data);
        }
    }
    function sendText() 
    {
        $response = Http::withoutVerifying()->post(config('e-office.wa_host').'/message/send-text', [
            'session' => config('e-office.wa_session'),
            'to' => $this->formatPhoneNumber($this->data['to']),
            'text' => $this->data['text'],
            'is_group'=>false,
        ]);
        if (!$response->successful()) {
            WaSender::dispatch($this->data);
        }
    }
}
