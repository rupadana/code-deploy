<?php

namespace App\Services;

use App\Models\Site;
use Filament\Facades\Filament;

class SiteService
{
    public static function makeWebHookUrl(Site $site)
    {
        $panel = Filament::getCurrentPanel();
        $tenant = Filament::getTenant()->{$panel->getTenantSlugAttribute()};

        return route('api.app.sites.deploy', [
            'id' => $site->id,
            'token' => auth()->user()->createToken('github', ['site:deploy'])->plainTextToken,
            'panel' => $panel->getId(),
            'tenant' => $tenant,
        ]);
    }
}
