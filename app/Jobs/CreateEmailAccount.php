<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateEmailAccount extends _SiteJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Creating email account '.$this->data['mail_user']);
        $cPanel = $this->server->cPanel(3, $this->data['uname'], 'Email');

        $data = $cPanel->api('add_pop', [
            'email' => $this->data['mail_user'],
            'password' => $this->data['mail_pass'],
        ], 'result');

        if (! $data['status']) {
            throw_unless(Str::endsWith($error = current($data['errors']), 'already exists!'), $error);

            $data = $cPanel->api('passwd_pop', [
                'domain' => $this->data['domain'],
                'email' => $this->data['mail_user'],
                'password' => $this->data['mail_pass'],
            ], 'result');

            if (! $data['status']) {
                $this->markSiteAsFailed();
                throw new \Exception(current($data['errors']));
            }
        }
    }
}
