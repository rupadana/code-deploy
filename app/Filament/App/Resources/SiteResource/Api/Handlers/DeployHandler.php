<?php

namespace App\Filament\App\Resources\SiteResource\Api\Handlers;

use App\Filament\App\Resources\SiteResource;
use App\Providers\Webhook\GithubWebhookProvider;
use App\Providers\Webhook\GitlabWebhookProvider;
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
        $tenant = request()->route('tenant');
        $user = auth()->user();

        $panel = $this->getPanel();
        $tenant = $panel->getTenant($tenant);
        $tenantSlugAttribute = $panel->getTenantSlugAttribute();

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->teams->where($tenantSlugAttribute, $tenant->{$tenantSlugAttribute})->first()) {
            return true;
        }

        throw new Exception('Unauthorized');
    }

    public function handler(Request $request)
    {
        $id = $request->route('id');
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

            $provider = null;

            if ($request->header('X-GitHub-Event')) {
                $provider = GithubWebhookProvider::make($record, $request);
            } elseif ($request->header('X-Gitlab-Event')) {
                $provider = GitlabWebhookProvider::make($record, $request);
            }

            if ($provider) {
                $provider->handle();

                return response()->json([
                    'message' => 'on process',
                ]);
            } else {
                return response()->json([
                    'message' => 'Provider not supported',
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
