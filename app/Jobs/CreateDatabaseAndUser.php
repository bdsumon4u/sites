<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateDatabaseAndUser extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Creating database and user for '.$this->data['domain']);
        $cPanel = $this->server->cPanel(3, $this->data['uname'], 'Mysql');

        $data = $cPanel->api('create_database', [
            'name' => $this->data['db_name'],
        ], 'result');

        if (! $data['status']) {
            throw_unless(Str::endsWith($error = current($data['errors']), 'already exists.'), $error);
        }

        $data = $cPanel->api('create_user', [
            'name' => $this->data['db_user'],
            'password' => $this->data['db_pass'],
        ], 'result');

        if (! $data['status']) {
            throw_unless(Str::endsWith($error = current($data['errors']), 'already exists.'), $error);

            $data = $cPanel->api('set_password', [
                'user' => $this->data['db_user'],
                'password' => $this->data['db_pass'],
            ], 'result');

            if (! $data['status']) {
                $this->markSiteAsFailed();
                throw new \Exception(current($data['errors']));
            }
        }

        $data = $cPanel->api('set_privileges_on_database', [
            'database' => $this->data['db_name'],
            'user' => $this->data['db_user'],
            'privileges' => 'ALL PRIVILEGES',
        ], 'result');

        if (! $data['status']) {
            $this->markSiteAsFailed();
            throw new \Exception('Failed to set privileges on database');
        }
    }
}
