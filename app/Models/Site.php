<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function deployment(): HasMany
    {
        return $this->hasMany(Deployment::class, 'site_id');
    }
}
