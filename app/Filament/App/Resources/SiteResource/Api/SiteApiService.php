<?php

namespace App\Filament\App\Resources\SiteResource\Api;

use App\Filament\App\Resources\SiteResource;
use Rupadana\ApiService\ApiService;

class SiteApiService extends ApiService
{
    protected static ?string $resource = SiteResource::class;

    public static function handlers(): array
    {
        return [
            Handlers\DeployHandler::class,
        ];

    }
}
