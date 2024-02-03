<?php

namespace App\Filament\Resources\SiteResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SiteResource;
use App\Services\DeployScript;
use Spatie\QueryBuilder\QueryBuilder;

class DeployHandler extends Handlers
{
    public static string | null $uri = '/{id}/deploy';
    public static string | null $resource = SiteResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    public function handler(Request $request, $id)
    {
        if ($request->header('X-GitHub-Event') == 'ping') {
            return response()->json('pong');
        }

        $model = static::getModel()::query();

        $record = QueryBuilder::for(
            $model->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$record) return static::sendNotFoundResponse();

        if ($request->header('X-GitHub-Event') == 'push') {
            $server = $record->server;

            // dd($request->all());
            $process = DeployScript::make()
                ->server($server)
                ->domain($record->domain)
                ->actAsSiteUser()
                ->toSiteDirectory()
                ->checkoutTo($request->after)
                ->execute();


            if ($process->isSuccessful()) {
                return static::sendSuccessResponse(null, $process->getOutput());
            }
        }



        return response()->json([
            'message' => 'Event listener not ready'
        ], 401);
    }
}
