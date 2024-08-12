<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'team_id',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_has_teams');
    }

    public function servers(): MorphToMany
    {
        return $this->morphedByMany(Server::class, 'teamable');
    }

    public function sites(): MorphToMany
    {
        return $this->morphedByMany(Site::class, 'teamable');
    }
}
