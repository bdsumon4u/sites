<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Spatie\Ssh\Ssh;

class DeploySite extends _SiteJob
{
    private Site $parent;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        parent::__construct($data);
        $this->parent = Site::with('server')->findOrFail($data['copy_from']);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Deploying site '.$this->data['site_name'].' to '.$this->data['domain']);
        $process = Ssh::create($this->parent->uname, $this->parent->server->ip)
            ->usePrivateKey(config('services.ssh.dir').'GACD')
            ->disablePasswordAuthentication()
            ->disableStrictHostKeyChecking()
            ->enableQuietMode()
            ->setTimeout(1000)
            ->execute([
                'cd '.$this->parent->directory,
                './copy.sh '.collect([
                    '-s' => $this->data['site_name'],
                    '-d' => $this->data['domain'],
                    '-h' => $this->server->ip,
                    '-u' => $this->data['uname'],
                    '-db' => $this->data['db_name'],
                    '-dbu' => $this->data['db_user'],
                    '-dbp' => $this->data['db_pass'],
                    '-mu' => $this->data['mail_user'],
                    '-mp' => $this->data['mail_pass'],
                    '-r' => $this->data['directory'],
                ])
                    ->flatMap(fn ($val, $key) => [$key, '"'.$val.'"'])
                    ->implode(' '),
            ]);

        if (! $process->isSuccessful()) {
            $this->markSiteAsFailed();
            throw new \Exception($process->getErrorOutput());
        }

        $this->markSiteAsActive();
    }
}
