<?php

namespace App\Filament\Resources\SiteResource\Api;

use App\Filament\Resources\SiteResource;
use Rupadana\ApiService\ApiService;

class SiteApiService extends ApiService
{
    protected static ?string $resource = SiteResource::class;

    public static function handlers(): array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class,
            Handlers\DeployHandler::class,
        ];

    }
}
