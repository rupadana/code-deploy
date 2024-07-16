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
}
