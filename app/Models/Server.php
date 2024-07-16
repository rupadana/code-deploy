<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ssh_key_name',
        'user',
        'host',
        'ssh_port',
        'created_by',
        'notification',
    ];

    protected $casts = [
        'notification' => 'array',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'server_id');
    }

    public function owner(): MorphToMany
    {
        return $this->morphToMany(Team::class, 'teamable');
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class, 'server_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('team', function (Builder $query) {
            if (auth()->hasUser()) {
                $query
                    ->join('teamables', 'teamables.teamable_id', '=', 'servers.id')
                    ->where('teamables.teamable_type', static::class)
                    ->whereIn('teamables.team_id', auth()->user()->teams->map(fn($team) =>$team->id)->toArray());
            }
        });
    }
}
