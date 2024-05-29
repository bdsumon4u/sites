<?php

namespace App\Jobs;

use Spatie\Ssh\Ssh;

class UpdateSite extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $process = Ssh::create($this->data['uname'], 'cyber32.net')
            ->usePrivateKey(config('services.ssh.dir').'GACD')
            ->disablePasswordAuthentication()
            ->disableStrictHostKeyChecking()
            ->enableQuietMode()
            ->setTimeout(120)
            ->execute([
                'cd '.$this->data['directory'],
                './server_deploy.sh',
            ]);

        if (! $process->isSuccessful()) {
            $this->markSiteAsFailed();
            throw new \Exception($process->getErrorOutput());
        }

        $this->markSiteAsActive();
    }
}
