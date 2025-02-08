<?php

use App\Jobs\UpdateSite;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/sites', function (Request $request) {
    return Site::all(['uname', 'domain', 'directory', 'status'])->map(function ($site) {
        return [
            'uname' => $site->uname,
            'domain' => $site->domain,
            'status' => $site->status,
            'script' => 'cd '.$site->directory.' && ./server_deploy.sh',
        ];
    });
});

Route::post('/update', function (Request $request) {
    if ($request->secret != config('services.whm.token')) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    Site::where('service_id', $request->service_id)
        ->get()->each(function ($site) use ($request) {
            $site->update(['status' => $request->status]);
            UpdateSite::dispatchIf($request->status === 'Processing', $site);
        });
});
