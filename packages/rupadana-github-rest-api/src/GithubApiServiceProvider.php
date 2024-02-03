<?php

namespace Rupadana\GithubApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Rupadana\GithubApi\Commands\GithubApiCommand;

class GithubApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('github-rest-api')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_github-rest-api_table')
            ->hasCommand(GithubApiCommand::class);
    }
}
