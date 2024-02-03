<?php
namespace App\Filament\Resources\SiteResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\SiteResource;
use Illuminate\Routing\Router;


class SiteApiService extends ApiService
{
    protected static string | null $resource = SiteResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class,
            Handlers\DeployHandler::class
        ];
        
    }
}
