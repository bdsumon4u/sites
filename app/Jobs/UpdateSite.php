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
        try {
            $process = Ssh::create($this->data['uname'], $this->server->ip)
                ->usePrivateKey(config('services.ssh.dir').'GACD')
                ->disablePasswordAuthentication()
                ->disableStrictHostKeyChecking()
                ->enableQuietMode()
                ->setTimeout(60)
                ->execute([
                    'cd '.$this->data['directory'],
                    './server_deploy.sh',
                ]);

            if (! $process->isSuccessful()) {
                $this->markSiteAsFailed();
                throw new \RuntimeException('SSH command failed: '.$process->getErrorOutput());
            }
            $this->markSiteAsActive();
        } catch (\Exception $e) {
            $this->markSiteAsFailed();
            // Log the exception message to capture more details
            throw new \RuntimeException('SSH connection failed, please check the server and public key setup. Error: '.$e->getMessage());
        }
    }
}
