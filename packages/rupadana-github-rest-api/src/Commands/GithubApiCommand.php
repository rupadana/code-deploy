<?php

namespace Rupadana\GithubApi\Commands;

use Illuminate\Console\Command;

class GithubApiCommand extends Command
{
    public $signature = 'github-rest-api';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
