<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Jobs\AuthorizeSshKey;
use App\Jobs\ChangePhpVersion;
use App\Jobs\CreateDatabaseAndUser;
use App\Jobs\CreateEmailAccount;
use App\Jobs\DeploySite;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Arr::only($data, ['service_id', 'uname', 'domain', 'directory']) + [
            'updated_at' => null,
        ];
    }

    protected function afterCreate()
    {
        Bus::chain([
            new AuthorizeSshKey($this->form->getState()),
            // new ChangePhpVersion($this->form->getState()),
            new CreateEmailAccount($this->form->getState()),
            new CreateDatabaseAndUser($this->form->getState()),
            new DeploySite($this->form->getState()),
        ])->catch(function (Throwable $e) {
            // A job within the chain has failed...
            Log::error($e->getMessage());
        })->dispatch();
    }
}
