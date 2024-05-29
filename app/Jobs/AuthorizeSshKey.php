<?php

namespace App\Jobs;

use App\CPanel;

class AuthorizeSshKey extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = CPanel::make(2, $this->data['uname'], 'SSH')->api('authkey', [
            'key' => 'GACD',
            'action' => 'authorize',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            $this->markSiteAsFailed();
            throw new \Exception($data['error']);
        }
    }
}
