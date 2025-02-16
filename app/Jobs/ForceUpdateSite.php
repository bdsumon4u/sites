<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Ssh\Ssh;

class ForceUpdateSite extends _SiteJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                ->setTimeout(1000)
                ->execute([
                    'cd '.$this->data['directory'],
                    'rm -rf .git',
                    'git init',
                    'git remote add origin https://github.com/bdsumon4u/HotashKom.git',
                    'git fetch',
                    'git clean -fd -e .env -e storage/app/public',
                    'git pull origin master',

                    // Check and update/add CACHE_DRIVER
                    'grep -q "^CACHE_DRIVER=" .env && sed -i "s/^CACHE_DRIVER=.*/CACHE_DRIVER=database/" .env || echo "CACHE_DRIVER=database" >> .env',
                    // Check and update/add SESSION_DRIVER
                    'grep -q "^SESSION_DRIVER=" .env && sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=custom/" .env || echo "SESSION_DRIVER=custom" >> .env',
                    // Check and update/add SCOUT_DRIVER
                    'grep -q "^SCOUT_DRIVER=" .env && sed -i "s/^SCOUT_DRIVER=.*/SCOUT_DRIVER=database/" .env || echo "SCOUT_DRIVER=database" >> .env',
                    // Check and update/add APP_TIMEZONE
                    'grep -q "^APP_TIMEZONE=" .env && sed -i "s/^APP_TIMEZONE=.*/APP_TIMEZONE=Asia\/Dhaka/" .env || echo "APP_TIMEZONE=Asia/Dhaka" >> .env',

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
