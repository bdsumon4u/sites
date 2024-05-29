<?php

namespace App\Jobs;

use App\CPanel;

class ChangePhpVersion extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $status = CPanel::make(3, $this->data['uname'], 'LangPHP')->api('php_set_vhost_versions', [
            'version' => 'alt-php74',
            'vhost' => $this->data['domain'],
        ], 'result.status');

        if (! $status) {
            $this->markSiteAsFailed();
            throw new \Exception('Failed to change PHP version');
        }
    }
}
