<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class _SiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Site $site;

    public function __construct(
        protected array $data,
    ) {
        //
    }

    abstract public function handle(): void;

    protected function markSiteAsActive(): void
    {
        $this->getSite()->update(['status' => 'Active']);
    }

    protected function markSiteAsFailed(): void
    {
        Site::withoutTimestamps(fn () => $this->getSite()->update(['status' => 'Failed']));
    }

    private function getSite(): Site
    {
        return $this->site = Site::query()->where('domain', $this->data['domain'])->firstOrFail();
    }
}
