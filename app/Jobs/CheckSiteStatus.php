<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckSiteStatus implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function handle()
    {
        $status = 'Outage';

        try {
            if (Http::timeout(10)->head('https://'.$this->site->domain)->ok()) {
                $status = 'Active';
            }
        } catch (\Exception $e) {

        }

        // Log the site status
        Log::info("Site: {$this->site->url} - Status: {$status}");

        // Update the site status in the database
        if ($this->site->status != $status) {
            $this->site->update(['status' => $status]);
        }
    }
}
