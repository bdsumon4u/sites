<?php

namespace App\Console\Commands;

use App\Jobs\CheckSiteStatus;
use App\Models\Site;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DispatchSiteStatusChecks extends Command
{
    protected $signature = 'sites:dispatch-checks';

    protected $description = 'Dispatch jobs to check site statuses.';

    public function handle()
    {
        Site::query()->whereIn('status', ['Active', 'Outage'])->chunk(50, function (Collection $sites) {
            Bus::batch($sites->map(fn ($site) => new CheckSiteStatus($site))->toArray())
                ->then(fn (Batch $batch) => Log::info('Site status checks completed.'))
                ->catch(fn (Batch $batch, \Throwable $e) => Log::error('Error in site status checks: '.$e->getMessage()))
                ->dispatch();
        });

        $this->info('Dispatched site status check jobs.');
    }
}
