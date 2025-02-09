<?php

namespace App\Jobs;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class _SiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Site $site;

    protected Server $server;

    public function __construct(
        protected array $data,
    ) {
        $this->server = Server::findOrFail($this->data['server_id']);
        $this->site = Site::query()->where('domain', $this->data['domain'])->firstOrFail();
    }

    abstract public function handle(): void;

    protected function markSiteAsActive(): void
    {
        $this->site->update(['status' => 'Active']);
    }

    protected function markSiteAsFailed(): void
    {
        Site::withoutTimestamps(fn () => $this->site->update(['status' => 'Failed']));
    }
}
