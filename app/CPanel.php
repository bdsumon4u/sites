<?php

namespace App;

use App\Models\Server;
use Illuminate\Support\Facades\Http;

class CPanel
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private int $version,
        private string $user,
        private Server $server,
        private string $module,
    ) {
        //
    }

    public static function make($version, $user, $module)
    {
        return new static(...func_get_args());
    }

    public function api($action, $params = [], $key = null)
    {
        $user = $this->server->username;
        $token = $this->server->token;

        return Http::withHeader('Authorization', "whm $user:$token")
            ->get($this->server->endpoint, [
                'api.version' => 1,
                'cpanel_jsonapi_func' => $action,
                'cpanel_jsonapi_user' => $this->user,
                'cpanel_jsonapi_module' => $this->module,
                'cpanel_jsonapi_apiversion' => $this->version,
            ] + $params)
            ->json($key);
    }
}
