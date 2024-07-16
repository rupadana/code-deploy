<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'project-type',
        'version',
        'domain',
        'server_id',
        'repository',
        'script',
        'environment',
        'database_name',
        'database_password',
        'current_sha',
        'branch',
        'directory',
        'created_by',
        'site_user',
        'quick_deploy',
        'repository_installed',
        'webhook_url',
        'template',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function owner(): MorphToMany
    {
        return $this->morphToMany(Team::class, 'teamable');
    }

    public function deployment(): HasMany
    {
        return $this->hasMany(Deployment::class, 'site_id');
    }
}
