<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Jobs\AuthorizeSshKey;
use App\Jobs\ChangePhpVersion;
use App\Jobs\CreateAddonDomain;
use App\Jobs\CreateDatabaseAndUser;
use App\Jobs\CreateEmailAccount;
use App\Jobs\DeploySite;
use App\Jobs\ForceUpdateSite;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSite extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = SiteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Arr::only($data, ['service_id', 'uname', 'domain', 'directory']) + [
            'updated_at' => null,
        ];
    }

    protected function afterCreate()
    {
        // if copy_from was not set, then update the site
        if (! Arr::get($this->form->getState(), 'copy_from')) {
            Log::info('Update site because copy_from was not set');

            return ForceUpdateSite::dispatch($this->form->getState());
        }

        Bus::chain([
            new AuthorizeSshKey($this->form->getState()),
            // new ChangePhpVersion($this->form->getState()),
            new CreateAddonDomain($this->form->getState()),
            new CreateEmailAccount($this->form->getState()),
            new CreateDatabaseAndUser($this->form->getState()),
            new DeploySite($this->form->getState()),
        ])->catch(function (Throwable $e) {
            // A job within the chain has failed...
            Log::error($e->getMessage());
        })->dispatch();
    }
}
