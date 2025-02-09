<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class CreateAddonDomain extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->data['directory'] === 'public_html') {
            return;
        }

        Log::info('Creating addon domain '.$this->data['domain']);
        $data = $this->server->cPanel(2, $this->data['uname'], 'AddonDomain')->api('addaddondomain', [
            'dir' => $this->data['directory'],
            'newdomain' => $this->data['domain'],
            'subdomain' => str($this->data['domain'])->before('.'),
        ], 'cpanelresult');

        if (array_key_exists('error', $data) && ! str($data['error'])->contains('already exists.')) {
            $this->markSiteAsFailed();
            throw new \Exception($data['error']);
        }
    }
}
