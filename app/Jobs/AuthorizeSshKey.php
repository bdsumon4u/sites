<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class AuthorizeSshKey extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Authorizing SSH key for '.$this->data['uname']);
        $data = $this->server->cPanel(2, $this->data['uname'], 'SSH')->api('authkey', [
            'key' => 'GACD',
            'action' => 'authorize',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            $this->markSiteAsFailed();
            throw new \Exception($data['error']);
        }
    }
}
