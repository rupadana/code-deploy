<?php

namespace App\Jobs\Concerns\Abstracts;

use App\Services\DeployScript;

abstract class  DeploymentProcess
{
    public function __construct(protected array $data) {}

    public static function make(array $data = []) : static
    {
        return new static($data);
    }
    abstract public function handle(DeployScript $script);


}
