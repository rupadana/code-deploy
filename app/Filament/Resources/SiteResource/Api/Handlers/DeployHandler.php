<?php

namespace App\Filament\Resources\SiteResource\Api\Handlers;

use App\Filament\Resources\SiteResource;
use App\Jobs\Concerns\SetSiteSha;
use App\Jobs\DeploymentJob;
use App\Services\DeployScript;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;

class DeployHandler extends Handlers
{
    public static ?string $uri = '/{id}/deploy';

    public static ?string $resource = SiteResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel(): ?string
    {
        return static::$resource::getModel();
    }

    protected function checkAuthorization(Model $record)
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($record->created_by == $user->id) {
            return true;
        }

        throw new Exception('Unauthorized');
    }

    public function handler(Request $request, $id)
    {

        try {

            if ($request->header('X-GitHub-Event') == 'ping') {
                return response()->json('pong');
            }

            $model = static::getModel()::query();

            $record = QueryBuilder::for(
                $model->where(static::getKeyName(), $id)
            )
                ->first();

            if (! $record) {
                return static::sendNotFoundResponse();
            }

            $this->checkAuthorization($record);

            if ('refs/heads/'.$record->branch !== $request->ref) {
                return response()->json([
                    'message' => 'nothing to do',
                ], 200);
            }

            if ($request->header('X-GitHub-Event') == 'push') {
                $server = $record->server;

                $process = DeployScript::make()
                    ->server($server)
                    ->site($record)
                    ->siteUser($record->site_user)
                    ->actAsSiteUser()
                    ->toSiteDirectory()
                    ->gitStash()
                    ->gitStashClear()
                    ->gitFetch()
                    ->checkoutTo($request->after)
                    ->script(explode('\n', substr(substr(json_encode($record->script), 1), 0, -1)));

                // TODO : is it right to use job here? because we can't notify to github when its failed 

                DeploymentJob::dispatch($process, $server->owner, finish: SetSiteSha::make(['sha' => $request->after]));

                return static::sendSuccessResponse(null, 'On Process');
            }

            return response()->json([
                'message' => 'Event listener not ready',
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
