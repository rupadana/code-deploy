<?php

namespace Rupadana\GithubApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rupadana\GithubApi\GithubApi
 */
class GithubApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Rupadana\GithubApi\GithubApi::class;
    }
}
