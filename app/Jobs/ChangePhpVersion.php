<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class ChangePhpVersion extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Changing PHP version for '.$this->data['domain']);
        $status = $this->server->cPanel(3, $this->data['uname'], 'LangPHP')->api('php_set_vhost_versions', [
            'version' => 'alt-php74',
            'vhost' => $this->data['domain'],
        ], 'result.status');

        if (! $status) {
            $this->markSiteAsFailed();
            throw new \Exception('Failed to change PHP version');
        }
    }
}
