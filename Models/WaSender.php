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
    
    function sendDocument() 
    {
        $response = Http::withoutVerifying()->post(config('e-office.api.wa_sender.url').'/message/send-document', [
            'session' => config('e-office.api.wa_sender.session'),
            'to' => $this->data['to'],
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
        $response = Http::withoutVerifying()->post(config('e-office.api.wa_sender.url').'/message/send-text', [
            'session' => config('e-office.api.wa_sender.session'),
            'to' => $this->data['to'],
            'text' => $this->data['text'],
            'is_group'=>false,
        ]);
        if (!$response->successful()) {
            WaSender::dispatch($this->data);
        }
    }
}
